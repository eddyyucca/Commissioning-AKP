<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplateItem;
use App\Models\Commissioning;
use App\Models\CommissioningItem;
use App\Models\Finding;
use App\Models\Incident;
use App\Models\Unit;
use App\Models\UnitDocument;
use App\Services\AlertService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk melihat dashboard terisi: php artisan db:seed --class=DemoSeeder
 * Hanya untuk lokal / uji. Menolak berjalan di produksi dan bila sudah ada unit.
 */
class DemoSeeder extends Seeder
{
    private array $people = ['Budi Santoso', 'Sari Wulandari', 'Wawan Hermawan', 'Dedi Kurniawan', 'Rina Marlina', 'Agus Pratama', 'Yusuf Rahman'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Data contoh tidak boleh dibuat di produksi.');

            return;
        }
        if (Unit::count() > 0) {
            $this->command?->warn('Sudah ada unit; data contoh dilewati.');

            return;
        }

        mt_srand(42);
        $areas = ['Pit Utara', 'Pit Selatan', 'Hauling Road', 'Workshop', 'Kantor Site'];
        $plan = ['HD' => [10, 'Dump truck 40 ton', 'Komatsu', 'HD785-7'], 'EX' => [6, 'Excavator PC1250', 'Komatsu', 'PC1250'], 'LV' => [10, 'Double cabin 4x4', 'Toyota', 'Hilux'],
            'CR' => [3, 'Mobile crane 50 ton', 'Tadano', 'GR-500'], 'PW' => [4, 'Lighting tower', 'Generac', 'MLT'], 'BS' => [3, 'Bus karyawan', 'Hino', 'RK8']];

        $units = collect();
        foreach ($plan as $cat => [$n, $name, $brand, $model]) {
            for ($i = 1; $i <= $n; $i++) {
                $units->push(Unit::create([
                    'code' => $cat.'-'.(100 + $i), 'name' => $name, 'category' => $cat, 'brand' => $brand, 'model' => $model, 'year' => mt_rand(2017, 2025),
                    'serial_number' => strtoupper(bin2hex(random_bytes(4))), 'plate' => $cat === 'LV' || $cat === 'BS' ? 'DT '.mt_rand(1000, 9999).' KC' : null,
                    'owner' => mt_rand(1, 10) <= 8 ? 'AKP' : 'Kontraktor Mitra', 'area' => $areas[array_rand($areas)], 'is_active' => true,
                ]));
            }
        }

        // Distribusi status (dengan seed tetap supaya hasilnya sama).
        $order = $units->shuffle(mt_rand())->values();
        $slots = array_merge(
            array_fill(0, 3, 'new'), array_fill(0, 2, 'process'), array_fill(0, 2, 'hold'), array_fill(0, 3, 'expired'),
            array_fill(0, 5, 'expiring'), array_fill(0, $order->count() - 15, 'fit'),
        );

        foreach ($order as $i => $unit) {
            $state = $slots[$i];
            if ($state === 'new') {
                continue;
            }
            $this->history($unit, $state);
        }

        $this->inProcess($units);
        $this->openFindings();
        $this->documents($units);
        $this->incidents($units);
        $units->random(2)->each(fn (Unit $u) => $u->update(['idle_since' => today()->subDays(mt_rand(35, 80))]));

        app(AlertService::class)->scan();
        $this->command?->info('Data contoh dibuat: '.Unit::count().' unit, '.Commissioning::count().' proses, '.Finding::count().' temuan.');
    }

    private function person(): string
    {
        return $this->people[array_rand($this->people)];
    }

    /** Riwayat commissioning yang sudah disetujui, sehingga status unit sesuai $state. */
    private function history(Unit $unit, string $state): void
    {
        $validity = match ($state) {
            'expired' => -mt_rand(3, 40),
            'expiring' => mt_rand(2, 28),
            'process' => mt_rand(20, 90),
            'hold' => mt_rand(60, 200),
            default => mt_rand(40, 340),
        };
        $until = today()->addDays($validity);
        $lastDecided = $until->copy()->subMonthsNoOverflow(12)->addDays(mt_rand(0, 3));
        if ($lastDecided->gt(now())) {
            $lastDecided = now()->subDays(mt_rand(1, 20));
        }

        // 0-2 proses lama (sebelum yang terakhir) untuk grafik bulanan.
        $older = mt_rand(0, 2);
        $first = null;
        for ($k = $older; $k >= 0; $k--) {
            $decided = $k === 0 ? $lastDecided->copy() : $lastDecided->copy()->subMonthsNoOverflow(12 * $k)->startOfDay();
            if ($decided->lt(now()->subMonths(11)->startOfMonth()) && $k > 0 && mt_rand(0, 1)) {
                continue;
            }
            $kind = $first === null ? 'commissioning' : 'recommissioning';
            $reason = $kind === 'recommissioning' ? $this->reason() : null;
            $c = $this->approved($unit, $kind, $reason, $decided, $k === 0 ? $until : $decided->copy()->addMonthsNoOverflow(12));
            $first ??= $c;
            if ($k === 0) {
                $unit->update(['commissioned_until' => $until, 'last_commissioned_at' => $decided, 'last_commissioning_id' => $c->id]);
            }
        }

        if ($state === 'hold') {
            $when = now()->subDays(mt_rand(2, 25));
            Incident::create([
                'unit_id' => $unit->id, 'occurred_at' => $when, 'kind' => ['kecelakaan', 'kerusakan_besar', 'overhaul'][mt_rand(0, 2)], 'severity' => 'sedang',
                'description' => ['Menabrak tanggul di hauling road', 'Transmisi rusak berat, unit ditarik ke workshop', 'Overhaul mesin di workshop'][mt_rand(0, 2)],
                'requires_recommissioning' => true, 'reported_by_name' => $this->person(), 'status' => 'open',
            ]);
            $unit->update(['hold_since' => $when, 'hold_reason' => 'Insiden pada '.$when->translatedFormat('d M Y')]);
        }
    }

    private function reason(): string
    {
        $weights = ['berkala' => 6, 'insiden' => 2, 'perbaikan_besar' => 3, 'modifikasi' => 1, 'idle' => 2, 'relokasi' => 2, 'kepemilikan' => 1];
        $bag = [];
        foreach ($weights as $k => $w) {
            $bag = array_merge($bag, array_fill(0, $w, $k));
        }

        return $bag[array_rand($bag)];
    }

    private function approved(Unit $unit, string $kind, ?string $reason, Carbon $decided, Carbon $until): Commissioning
    {
        $submitted = $decided->copy()->subDays(mt_rand(1, 6))->subHours(mt_rand(0, 10));
        $created = $submitted->copy()->subDays(mt_rand(0, 3));
        $c = Commissioning::create([
            'number' => 'CMS-'.$decided->format('ym').'-'.str_pad((string) (Commissioning::count() + 1), 4, '0', STR_PAD_LEFT),
            'unit_id' => $unit->id, 'unit_code' => $unit->code, 'unit_name' => $unit->name, 'category' => $unit->category, 'kind' => $kind, 'reason' => $reason,
            'area' => $unit->area, 'planned_date' => $created->toDateString(), 'meter_reading' => mt_rand(500, 22000), 'meter_unit' => 'jam',
            'status' => 'approved', 'result' => 'laik', 'valid_until' => $until->toDateString(),
            'requested_by_id' => (string) mt_rand(10, 30), 'requested_by_name' => $this->person(), 'submitted_at' => $submitted,
            'safety_by_name' => 'Sari Wulandari', 'safety_by_id' => '3', 'safety_at' => $submitted->copy()->addHours(mt_rand(3, 20)),
            'workshop_by_name' => 'Wawan Hermawan', 'workshop_by_id' => '4', 'workshop_at' => $submitted->copy()->addHours(mt_rand(5, 30)),
            'decided_by_name' => 'Dedi Kurniawan', 'decided_by_id' => '5', 'decided_at' => $decided,
        ]);
        $c->forceFill(['created_at' => $created, 'updated_at' => $decided])->save();

        $this->items($c, $created, closeFindings: true);

        return $c;
    }

    /** Menyalin checklist untuk proses ini; sebagian kecil item tidak sesuai (dengan temuan yang sudah ditutup bila $closeFindings). */
    private function items(Commissioning $c, Carbon $when, bool $closeFindings, bool $answer = true): void
    {
        $templates = ChecklistTemplateItem::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->filter(fn ($t) => $t->appliesTo($c->kind, $c->category, $c->reason));

        $order = 0;
        foreach ($templates as $t) {
            $bad = $answer && mt_rand(1, 100) <= (str_contains($t->label, 'Lampu') || str_contains($t->label, 'Ban') || str_contains($t->label, 'APAR') ? 9 : 3);
            $item = CommissioningItem::create([
                'commissioning_id' => $c->id, 'group_name' => $t->group_name, 'label' => $t->label, 'is_critical' => $t->is_critical, 'reference' => $t->reference,
                'result' => $answer ? ($bad ? 'bad' : (mt_rand(1, 100) <= 4 ? 'na' : 'ok')) : null, 'note' => $bad ? 'Perlu perbaikan sebelum dipakai' : null, 'sort_order' => ++$order,
            ]);
            if ($bad) {
                $f = Finding::create([
                    'commissioning_id' => $c->id, 'item_id' => $item->id, 'unit_id' => $c->unit_id, 'title' => $t->label, 'severity' => $t->is_critical ? 'critical' : 'minor',
                    'description' => $item->note, 'status' => 'open', 'pic_name' => 'Workshop', 'due_date' => $when->copy()->addDays(7)->toDateString(),
                ]);
                if ($closeFindings) {
                    $f->forceFill(['status' => 'closed', 'closed_at' => $when->copy()->addDays(mt_rand(1, 6)), 'closed_by_name' => 'Sari Wulandari', 'closed_note' => 'Sudah diperbaiki dan diuji ulang'])->save();
                    $f->forceFill(['created_at' => $when])->save();
                }
            }
        }
    }

    private function inProcess($units): void
    {
        // Unit yang belum pernah commissioning dan belum punya proses: 1 draf, 1 diajukan menunggu Safety, 1 menunggu keputusan, 1 perlu perbaikan.
        $free = Unit::query()->whereNull('last_commissioning_id')->whereNull('hold_since')->get();
        $extra = $units->filter(fn ($u) => $u->last_commissioning_id && ! $u->hold_since && $u->commissioned_until?->gt(today()->addDays(60)))->take(3)->values();
        $targets = $free->concat($extra)->take(5)->values();

        $specs = [['draft', false, false], ['submitted', false, false], ['submitted', true, true], ['rejected', false, false], ['submitted', true, false]];
        foreach ($targets as $i => $unit) {
            [$status, $safety, $workshop] = $specs[$i % count($specs)];
            $kind = $unit->last_commissioning_id ? 'recommissioning' : 'commissioning';
            $created = now()->subDays(mt_rand(1, 6));
            $c = Commissioning::create([
                'number' => 'CMS-'.now()->format('ym').'-'.str_pad((string) (Commissioning::count() + 1), 4, '0', STR_PAD_LEFT),
                'unit_id' => $unit->id, 'unit_code' => $unit->code, 'unit_name' => $unit->name, 'category' => $unit->category, 'kind' => $kind,
                'reason' => $kind === 'recommissioning' ? 'berkala' : null, 'area' => $unit->area, 'status' => $status,
                'requested_by_id' => '2', 'requested_by_name' => 'Budi Santoso', 'submitted_at' => $status === 'submitted' ? $created->copy()->addHours(2) : null,
                'safety_by_name' => $safety ? 'Sari Wulandari' : null, 'safety_by_id' => $safety ? '3' : null, 'safety_at' => $safety ? $created->copy()->addHours(6) : null,
                'workshop_by_name' => $workshop ? 'Wawan Hermawan' : null, 'workshop_by_id' => $workshop ? '4' : null, 'workshop_at' => $workshop ? $created->copy()->addHours(9) : null,
                'returned_reason' => $status === 'rejected' ? 'Foto bukti rem kurang jelas, mohon ulangi' : null,
            ]);
            $c->forceFill(['created_at' => $created])->save();
            $this->items($c, $created, closeFindings: false, answer: $status !== 'draft');
        }
    }

    private function openFindings(): void
    {
        // Sebagian temuan dari proses yang sedang berjalan dibuat terlambat agar alert dan grafik temuan terlihat.
        Finding::query()->where('status', 'open')->get()->each(function (Finding $f, int $i) {
            if ($i % 3 === 0) {
                $f->update(['due_date' => today()->subDays(mt_rand(1, 6))]);
            } elseif ($i % 3 === 1) {
                $f->update(['status' => 'in_progress', 'due_date' => today()->addDays(mt_rand(2, 10))]);
            }
        });
    }

    private function documents($units): void
    {
        $types = ['laik_operasi', 'kir', 'asuransi', 'apar', 'registrasi'];
        foreach ($units as $unit) {
            foreach (array_rand(array_flip($types), 3) as $type) {
                $roll = mt_rand(1, 100);
                $days = $roll <= 6 ? -mt_rand(1, 40) : ($roll <= 20 ? mt_rand(1, 30) : mt_rand(40, 400));
                UnitDocument::create([
                    'unit_id' => $unit->id, 'type' => $type, 'number' => strtoupper(substr($type, 0, 3)).'/'.mt_rand(100, 999).'/'.date('Y'),
                    'issued_at' => today()->addDays($days)->subYear()->toDateString(), 'expires_at' => today()->addDays($days)->toDateString(), 'created_by_name' => 'Sari Wulandari',
                ]);
            }
        }
    }

    private function incidents($units): void
    {
        $kinds = [['near_miss', 'ringan', 'Hampir menyenggol unit lain di persimpangan'], ['kecelakaan', 'ringan', 'Menyenggol rambu di area parkir'], ['kerusakan_besar', 'sedang', 'Final drive rusak'],
            ['overhaul', 'ringan', 'Overhaul rutin hidrolik'], ['near_miss', 'ringan', 'Rem terasa blong saat turun tanjakan'], ['relokasi', 'ringan', 'Pindah dari Pit Utara ke Pit Selatan']];
        foreach ($kinds as $i => [$kind, $sev, $desc]) {
            $unit = $units->random();
            Incident::create([
                'unit_id' => $unit->id, 'occurred_at' => now()->subMonths(mt_rand(0, 9))->subDays(mt_rand(0, 20)), 'kind' => $kind, 'severity' => $sev, 'description' => $desc,
                'requires_recommissioning' => in_array($kind, ['kecelakaan', 'kerusakan_besar', 'overhaul'], true), 'reported_by_name' => $this->person(),
                'status' => 'resolved', 'resolved_at' => now(),
            ]);
        }
    }
}
