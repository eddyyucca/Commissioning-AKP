<?php

namespace App\Services;

use Akp\PortalSso\PortalUser;
use App\Models\ChecklistTemplateItem;
use App\Models\Commissioning;
use App\Models\CommissioningItem;
use App\Models\Finding;
use App\Models\Incident;
use App\Models\Unit;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Seluruh aturan alur commissioning / recommissioning:
 *   draf -> diajukan -> (verifikasi Safety + verifikasi Workshop) -> keputusan penyetuju -> laik operasi
 * Pelanggaran aturan dilaporkan sebagai DomainException berpesan bahasa Indonesia yang aman ditampilkan ke pengguna.
 */
class CommissioningService
{
    public function __construct(private AlertService $alerts)
    {
    }

    /** Membuat commissioning baru dan menyalin checklist yang berlaku (snapshot) untuk kategori dan alasan unit ini. */
    public function start(Unit $unit, string $kind, ?string $reason, ?string $reasonNote, PortalUser $user, array $data = []): Commissioning
    {
        if (! $unit->is_active) {
            throw new DomainException('Unit tidak aktif.');
        }
        if (! array_key_exists($kind, Commissioning::KIND_LABELS)) {
            throw new DomainException('Jenis proses tidak dikenal.');
        }
        if ($unit->commissionings()->whereIn('status', Commissioning::ACTIVE_STATUSES)->exists()) {
            throw new DomainException("Unit {$unit->code} masih punya proses yang belum selesai. Selesaikan atau batalkan dulu.");
        }
        if ($kind === 'commissioning' && $unit->last_commissioning_id) {
            throw new DomainException("Unit {$unit->code} sudah pernah commissioning. Gunakan recommissioning.");
        }
        if ($kind === 'recommissioning') {
            if (! $unit->last_commissioning_id) {
                throw new DomainException("Unit {$unit->code} belum pernah commissioning. Mulai dengan commissioning.");
            }
            if (! $reason || ! array_key_exists($reason, config('commissioning.reasons'))) {
                throw new DomainException('Alasan recommissioning wajib dipilih.');
            }
        } else {
            $reason = null;
        }

        $templates = ChecklistTemplateItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn (ChecklistTemplateItem $t) => $t->appliesTo($kind, $unit->category, $reason));

        if ($templates->isEmpty()) {
            throw new DomainException('Belum ada item checklist untuk kategori dan alasan ini. Hubungi administrator.');
        }

        return DB::transaction(function () use ($unit, $kind, $reason, $reasonNote, $user, $data, $templates) {
            $c = Commissioning::create([
                'number' => Commissioning::nextNumber(),
                'unit_id' => $unit->id, 'unit_code' => $unit->code, 'unit_name' => $unit->name, 'category' => $unit->category,
                'kind' => $kind, 'reason' => $reason, 'reason_note' => $reasonNote,
                'area' => $data['area'] ?? $unit->area, 'planned_date' => $data['planned_date'] ?? null,
                'meter_reading' => $data['meter_reading'] ?? null, 'meter_unit' => $data['meter_unit'] ?? null,
                'status' => 'draft',
                'requested_by_id' => $user->id, 'requested_by_name' => $user->name, 'requested_by_nik' => $user->employee_no,
            ]);

            $order = 0;
            foreach ($templates as $t) {
                CommissioningItem::create([
                    'commissioning_id' => $c->id, 'group_name' => $t->group_name, 'label' => $t->label,
                    'is_critical' => $t->is_critical, 'reference' => $t->reference, 'sort_order' => ++$order,
                ]);
            }

            return $c;
        });
    }

    /**
     * Menyimpan jawaban checklist (boleh sebagian; kelengkapan baru diperiksa saat diajukan).
     *
     * @param  array<int, ?string>  $results  item id => ok|bad|na|''
     * @param  array<int, ?string>  $notes
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, mixed>  $removePhotos  item id yang fotonya dihapus
     */
    public function saveAnswers(Commissioning $c, array $results, array $notes, array $photos = [], array $removePhotos = []): void
    {
        $this->assertEditable($c);

        DB::transaction(function () use ($c, $results, $notes, $photos, $removePhotos) {
            foreach ($c->items()->get() as $item) {
                $r = $results[$item->id] ?? null;
                if (! in_array($r, ['ok', 'bad', 'na'], true)) {
                    $r = null;
                }
                $item->result = $r;
                $item->note = isset($notes[$item->id]) && trim((string) $notes[$item->id]) !== '' ? mb_substr(trim((string) $notes[$item->id]), 0, 500) : null;

                if (isset($removePhotos[$item->id]) && $item->photo_path) {
                    Storage::disk(config('commissioning.photo_disk'))->delete($item->photo_path);
                    $item->photo_path = null;
                }
                if (($photos[$item->id] ?? null) instanceof UploadedFile) {
                    if ($item->photo_path) {
                        Storage::disk(config('commissioning.photo_disk'))->delete($item->photo_path);
                    }
                    $item->photo_path = $this->storePhoto($photos[$item->id]);
                }
                $item->save();
            }
        });
    }

    public function updateHeader(Commissioning $c, array $data): void
    {
        $this->assertEditable($c);
        $c->fill(array_intersect_key($data, array_flip(['area', 'planned_date', 'meter_reading', 'meter_unit', 'reason_note'])))->save();
    }

    public function submit(Commissioning $c, PortalUser $user): Commissioning
    {
        $this->assertEditable($c);
        $this->assertOwnerOrAdmin($c, $user, 'Hanya pemohon (atau administrator) yang bisa mengajukan.');

        $items = $c->items()->get();
        $blank = $items->filter(fn ($i) => $i->result === null)->count();
        if ($blank > 0) {
            throw new DomainException("Masih ada {$blank} item yang belum diisi. Pilih Sesuai, Tidak sesuai, atau N/A untuk setiap item.");
        }
        $noNote = $items->filter(fn ($i) => $i->result === 'bad' && trim((string) $i->note) === '')->count();
        if ($noNote > 0) {
            throw new DomainException("{$noNote} item Tidak sesuai belum diberi keterangan.");
        }
        $naCritical = $items->filter(fn ($i) => $i->result === 'na' && $i->is_critical && trim((string) $i->note) === '')->count();
        if ($naCritical > 0) {
            throw new DomainException("{$naCritical} item kritis dinyatakan N/A tanpa alasan. Isi keterangannya.");
        }

        DB::transaction(function () use ($c, $items) {
            $this->syncFindings($c, $items);
            $c->forceFill([
                'status' => 'submitted', 'submitted_at' => now(), 'returned_reason' => null,
                'safety_at' => null, 'safety_by_name' => null, 'safety_by_id' => null, 'safety_notes' => null,
                'workshop_at' => null, 'workshop_by_name' => null, 'workshop_by_id' => null, 'workshop_notes' => null,
            ])->save();
        });

        $this->alerts->refresh();

        return $c->refresh();
    }

    /** Verifikasi tahap Safety atau Workshop. */
    public function verify(Commissioning $c, string $stage, PortalUser $user, ?string $notes): Commissioning
    {
        if (! in_array($stage, ['safety', 'workshop'], true)) {
            throw new DomainException('Tahap verifikasi tidak dikenal.');
        }
        $this->assertSubmitted($c);
        $this->assertNotSelf($c, $user, 'Pemohon tidak boleh memverifikasi permohonannya sendiri.');

        $c->forceFill([
            "{$stage}_at" => now(), "{$stage}_by_name" => $user->name, "{$stage}_by_id" => $user->id,
            "{$stage}_notes" => $notes !== null && trim($notes) !== '' ? mb_substr(trim($notes), 0, 500) : null,
        ])->save();

        return $c;
    }

    /** Mengembalikan ke pemeriksa untuk diperbaiki (oleh verifikator atau penyetuju). */
    public function sendBack(Commissioning $c, PortalUser $user, string $reason): Commissioning
    {
        $this->assertSubmitted($c);
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Alasan pengembalian wajib diisi.');
        }
        $c->forceFill(['status' => 'rejected', 'returned_reason' => mb_substr($reason, 0, 500)])->save();
        $this->alerts->refresh();

        return $c;
    }

    /** Keputusan akhir. $result: laik | laik_catatan. */
    public function approve(Commissioning $c, PortalUser $user, string $result, ?string $notes, ?Carbon $validUntil = null): Commissioning
    {
        if (! array_key_exists($result, Commissioning::RESULT_LABELS)) {
            throw new DomainException('Keputusan tidak dikenal.');
        }
        $this->assertSubmitted($c);
        $this->assertNotSelf($c, $user, 'Pemohon tidak boleh menyetujui permohonannya sendiri.');

        if (! $c->bothVerified()) {
            throw new DomainException('Verifikasi Safety dan Workshop harus lengkap dulu.');
        }

        $open = $c->findings()->open()->get();
        $blocking = $open->whereIn('severity', ['critical', 'major']);
        if ($blocking->isNotEmpty()) {
            throw new DomainException("Masih ada {$blocking->count()} temuan kritis/mayor yang terbuka. Tutup dulu temuan itu, atau kembalikan untuk perbaikan.");
        }
        if ($result === 'laik' && $open->isNotEmpty()) {
            throw new DomainException("Masih ada {$open->count()} temuan minor. Tutup temuan itu, atau pilih \"Laik dengan catatan\".");
        }
        if ($result === 'laik_catatan') {
            if ($open->isEmpty()) {
                throw new DomainException('Tidak ada temuan terbuka; pilih "Laik operasi".');
            }
            if ($open->whereNull('due_date')->isNotEmpty()) {
                throw new DomainException('Setiap temuan minor yang masih terbuka harus punya tenggat penyelesaian.');
            }
            if (trim((string) $notes) === '') {
                throw new DomainException('Catatan wajib diisi untuk keputusan "Laik dengan catatan".');
            }
        }

        $max = now()->startOfDay()->addMonthsNoOverflow($this->validityMonths($c->category));
        $until = $validUntil ? $validUntil->copy()->startOfDay() : $max;
        if ($until->lte(today()) || $until->gt($max)) {
            throw new DomainException('Masa berlaku harus setelah hari ini dan tidak melebihi '.$max->translatedFormat('d M Y').'.');
        }

        DB::transaction(function () use ($c, $user, $result, $notes, $until) {
            $c->forceFill([
                'status' => 'approved', 'result' => $result, 'valid_until' => $until,
                'decided_by_name' => $user->name, 'decided_by_id' => $user->id, 'decided_at' => now(),
                'decision_notes' => $notes !== null && trim($notes) !== '' ? mb_substr(trim($notes), 0, 500) : null,
            ])->save();

            $unit = $c->unit()->lockForUpdate()->first();
            $unit->forceFill([
                'commissioned_until' => $until, 'last_commissioned_at' => now(), 'last_commissioning_id' => $c->id, 'idle_since' => null,
            ]);
            // Hold dicabut hanya bila commissioning ini dibuat SETELAH hold dipasang (tidak menghapus hold insiden yang lebih baru).
            if ($unit->hold_since && $c->created_at->gte($unit->hold_since->copy()->startOfSecond())) {
                $unit->forceFill(['hold_since' => null, 'hold_reason' => null]);
                Incident::query()->where('unit_id', $unit->id)->where('status', 'open')->where('requires_recommissioning', true)
                    ->where('occurred_at', '<=', $c->submitted_at ?? now())
                    ->update(['status' => 'resolved', 'resolved_by_commissioning_id' => $c->id, 'resolved_at' => now()]);
            }
            $unit->save();
        });

        $this->alerts->refresh();

        return $c->refresh();
    }

    public function cancel(Commissioning $c, PortalUser $user, ?string $reason): Commissioning
    {
        if (! $c->isActive()) {
            throw new DomainException('Hanya proses yang belum selesai yang bisa dibatalkan.');
        }
        $this->assertOwnerOrAdmin($c, $user, 'Hanya pemohon (atau administrator) yang bisa membatalkan.');
        $c->forceFill(['status' => 'cancelled', 'cancelled_reason' => $reason ? mb_substr(trim($reason), 0, 255) : null])->save();
        $c->findings()->open()->update(['status' => 'closed', 'closed_at' => now(), 'closed_by_name' => $user->name, 'closed_note' => 'Proses dibatalkan.']);
        $this->alerts->refresh();

        return $c;
    }

    // ---- Temuan -------------------------------------------------------------------------------------------------

    /** Memperbarui rencana tindakan temuan (PIC, tenggat, status dikerjakan, tingkat). */
    public function updateFinding(Finding $f, array $data): Finding
    {
        if (! $f->isOpen()) {
            throw new DomainException('Temuan ini sudah ditutup.');
        }
        $f->fill(array_intersect_key($data, array_flip(['action_plan', 'pic_name', 'due_date', 'severity', 'status'])));
        if ($f->status === 'closed') {
            $f->status = 'in_progress';
        }
        $f->save();
        $this->alerts->refresh();

        return $f;
    }

    public function closeFinding(Finding $f, PortalUser $user, string $note): Finding
    {
        if (! $f->isOpen()) {
            throw new DomainException('Temuan ini sudah ditutup.');
        }
        if (trim($note) === '') {
            throw new DomainException('Catatan penutupan (bukti perbaikan) wajib diisi.');
        }
        $f->forceFill([
            'status' => 'closed', 'closed_at' => now(), 'closed_by_name' => $user->name, 'closed_note' => mb_substr(trim($note), 0, 500),
        ])->save();
        $this->alerts->refresh();

        return $f;
    }

    // ---- Insiden / hold -----------------------------------------------------------------------------------------

    public function reportIncident(Unit $unit, PortalUser $user, array $data): Incident
    {
        $kinds = config('commissioning.incident_kinds');
        if (! array_key_exists($data['kind'], $kinds)) {
            throw new DomainException('Jenis kejadian tidak dikenal.');
        }
        $requires = (bool) ($data['requires_recommissioning'] ?? $kinds[$data['kind']][1]);

        return DB::transaction(function () use ($unit, $user, $data, $requires, $kinds) {
            $incident = Incident::create([
                'unit_id' => $unit->id, 'occurred_at' => $data['occurred_at'] ?? now(), 'kind' => $data['kind'],
                'severity' => $data['severity'] ?? 'ringan', 'description' => $data['description'],
                'requires_recommissioning' => $requires, 'reported_by_name' => $user->name, 'reported_by_id' => $user->id,
                'status' => $requires ? 'open' : 'resolved', 'resolved_at' => $requires ? null : now(),
            ]);

            if ($requires) {
                $reason = $kinds[$data['kind']][0].': '.mb_substr($data['description'], 0, 150);
                $unit->forceFill([
                    'hold_since' => $unit->hold_since ?? now(),
                    'hold_reason' => mb_substr($unit->hold_since ? $unit->hold_reason.' | '.$reason : $reason, 0, 255),
                ])->save();
            }

            $this->alerts->refresh();

            return $incident;
        });
    }

    // ---- Pembantu -----------------------------------------------------------------------------------------------

    public function validityMonths(string $category): int
    {
        return (int) (config("commissioning.validity_months.per_category.{$category}") ?? config('commissioning.validity_months.default'));
    }

    /** Membuat/menutup temuan mengikuti hasil item: item Tidak sesuai -> temuan; item yang kini Sesuai -> temuan ditutup otomatis. */
    private function syncFindings(Commissioning $c, $items): void
    {
        $existing = $c->findings()->get()->keyBy('item_id');

        foreach ($items as $item) {
            $finding = $existing->get($item->id);

            if ($item->result === 'bad') {
                if ($finding) {
                    if (! $finding->isOpen()) {
                        $finding->forceFill(['status' => 'open', 'closed_at' => null, 'closed_by_name' => null, 'closed_note' => null]);
                    }
                    $finding->fill(['description' => $item->note, 'severity' => $item->is_critical ? 'critical' : $finding->severity])->save();
                } else {
                    Finding::create([
                        'commissioning_id' => $c->id, 'item_id' => $item->id, 'unit_id' => $c->unit_id, 'title' => $item->label,
                        'severity' => $item->is_critical ? 'critical' : 'minor', 'description' => $item->note, 'status' => 'open',
                    ]);
                }
            } elseif ($finding && $finding->isOpen()) {
                $finding->forceFill([
                    'status' => 'closed', 'closed_at' => now(), 'closed_by_name' => 'Sistem',
                    'closed_note' => 'Item dinyatakan sesuai pada pemeriksaan ulang.',
                ])->save();
            }
        }
    }

    private function assertEditable(Commissioning $c): void
    {
        if (! $c->isEditable()) {
            throw new DomainException('Hanya proses berstatus Draf atau Perlu perbaikan yang bisa diubah.');
        }
    }

    private function assertSubmitted(Commissioning $c): void
    {
        if ($c->status !== 'submitted') {
            throw new DomainException('Tahap ini hanya untuk proses yang berstatus Diajukan.');
        }
    }

    private function assertOwnerOrAdmin(Commissioning $c, PortalUser $user, string $message): void
    {
        if (! $user->is_admin && $user->role !== 'admin' && (string) $c->requested_by_id !== (string) $user->id) {
            throw new DomainException($message);
        }
    }

    /** Pemisahan tugas: pemohon tidak boleh memverifikasi/menyetujui sendiri (administrator dikecualikan). */
    private function assertNotSelf(Commissioning $c, PortalUser $user, string $message): void
    {
        if (! $user->is_admin && $user->role !== 'admin' && (string) $c->requested_by_id === (string) $user->id) {
            throw new DomainException($message);
        }
    }

    /** Foto disimpan di disk privat; dikecilkan dan dikompres bila ekstensi GD tersedia. */
    private function storePhoto(UploadedFile $file): string
    {
        $disk = Storage::disk(config('commissioning.photo_disk'));
        $name = 'commissioning/photos/'.now()->format('Y/m').'/'.bin2hex(random_bytes(12)).'.jpg';
        $disk->put($name, $this->compress($file) ?? $file->get());

        return $name;
    }

    private function compress(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $image = @imagecreatefromstring($file->get());
        if (! $image) {
            return null;
        }
        $max = (int) config('commissioning.photo_max_px');
        [$w, $h] = [imagesx($image), imagesy($image)];
        if (max($w, $h) > $max) {
            $scale = $max / max($w, $h);
            $image = imagescale($image, (int) round($w * $scale), (int) round($h * $scale)) ?: $image;
        }
        ob_start();
        imagejpeg($image, null, 80);

        return ob_get_clean() ?: null;
    }
}
