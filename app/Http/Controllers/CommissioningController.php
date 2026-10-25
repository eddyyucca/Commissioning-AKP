<?php

namespace App\Http\Controllers;

use App\Models\Commissioning;
use App\Models\CommissioningItem;
use App\Models\Unit;
use App\Services\CommissioningService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Daftar proses, pengisian checklist, dan seluruh tahap persetujuan. */
class CommissioningController extends Controller
{
    public function __construct(private CommissioningService $service)
    {
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status');
        $kind = (string) $request->query('jenis');
        $q = trim((string) $request->query('q'));

        $items = Commissioning::query()->with('unit:id,code,name')->orderByDesc('id')
            ->when($status === 'aktif', fn ($query) => $query->whereIn('status', Commissioning::ACTIVE_STATUSES))
            ->when(array_key_exists($status, Commissioning::STATUS_LABELS), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists($kind, Commissioning::KIND_LABELS), fn ($query) => $query->where('kind', $kind))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $query->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('unit_code', 'like', $like)->orWhere('unit_name', 'like', $like)->orWhere('requested_by_name', 'like', $like));
            })
            ->paginate(20)->withQueryString();

        return view('cms.index', ['items' => $items, 'status' => $status, 'kind' => $kind, 'q' => $q]);
    }

    public function create(Request $request)
    {
        $unit = null;
        if (filled($code = $request->query('unit'))) {
            $unit = Unit::query()->where('code', Unit::normalizeCode((string) $code))->first();
        }

        return view('cms.create', [
            'unit' => $unit,
            'units' => Unit::query()->active()->withProcess()->orderBy('code')->get(['id', 'code', 'name', 'category', 'last_commissioning_id', 'hold_since', 'commissioned_until']),
            'suggestedReason' => $unit?->hold_since ? 'insiden' : ($unit?->idle_since ? 'idle' : 'berkala'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit' => ['required', 'string', 'max:30'],
            'reason' => ['nullable', Rule::in(array_keys(config('commissioning.reasons')))],
            'reason_note' => ['nullable', 'string', 'max:255'],
            'planned_date' => ['nullable', 'date'],
            'area' => ['nullable', 'string', 'max:80'],
            'meter_reading' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'meter_unit' => ['nullable', Rule::in(['km', 'jam'])],
        ], ['unit.required' => 'Pilih unit.', 'reason.in' => 'Alasan tidak dikenal.']);

        $unit = Unit::query()->where('code', Unit::normalizeCode($data['unit']))->first();
        if (! $unit) {
            return back()->withInput()->withErrors(['unit' => 'Unit tidak ditemukan.']);
        }

        $kind = $unit->last_commissioning_id ? 'recommissioning' : 'commissioning';

        try {
            $c = $this->service->start($unit, $kind, $data['reason'] ?? null, $data['reason_note'] ?? null, portal_user(), $data);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cms.show', $c)->with('success', "{$c->kindLabel()} {$c->number} dibuat. Isi checklist lalu ajukan.");
    }

    public function show(Commissioning $commissioning)
    {
        $c = $commissioning->load('unit');
        $user = portal_user();
        $isAdmin = (bool) ($user->is_admin || $user->role === 'admin');
        $self = (string) $c->requested_by_id === (string) $user->id;
        $items = $c->items()->get();
        $findings = $c->findings()->orderBy('severity')->get();
        $openFindings = $findings->where('status', '!=', 'closed');
        $openBlocking = $openFindings->whereIn('severity', ['critical', 'major']);

        return view('cms.show', [
            'c' => $c,
            'groups' => $items->groupBy('group_name'),
            'items' => $items,
            'findings' => $findings,
            'openFindings' => $openFindings,
            'openBlocking' => $openBlocking,
            'maxUntil' => now()->startOfDay()->addMonthsNoOverflow($this->service->validityMonths($c->category)),
            'can' => [
                'edit' => $c->isEditable() && ($isAdmin || ($self && $user->hasRole('inspector', 'safety', 'workshop'))),
                'cancel' => $c->isActive() && ($isAdmin || $self),
                'safety' => $c->status === 'submitted' && ! $c->safety_at && $user->hasRole('admin', 'safety') && ($isAdmin || ! $self),
                'workshop' => $c->status === 'submitted' && ! $c->workshop_at && $user->hasRole('admin', 'workshop') && ($isAdmin || ! $self),
                'sendBack' => $c->status === 'submitted' && $user->hasRole('admin', 'safety', 'workshop', 'approver'),
                'approve' => $c->status === 'submitted' && $user->hasRole('admin', 'approver') && ($isAdmin || ! $self),
                'findings' => $user->hasRole('admin', 'safety', 'workshop'),
                'closeFinding' => $user->hasRole('admin', 'safety'),
            ],
        ]);
    }

    /** Menyimpan jawaban; tombol "Simpan dan ajukan" langsung mengajukan bila semua syarat terpenuhi. */
    public function answers(Request $request, Commissioning $commissioning)
    {
        $max = (int) config('commissioning.photo_max_kb');
        $request->validate([
            'result' => ['array'], 'result.*' => ['nullable', Rule::in(['ok', 'bad', 'na', ''])],
            'note' => ['array'], 'note.*' => ['nullable', 'string', 'max:500'],
            'photos' => ['array'], 'photos.*' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$max],
            'reason_note' => ['nullable', 'string', 'max:255'], 'planned_date' => ['nullable', 'date'], 'area' => ['nullable', 'string', 'max:80'],
            'meter_reading' => ['nullable', 'integer', 'min:0', 'max:99999999'], 'meter_unit' => ['nullable', Rule::in(['km', 'jam'])],
        ], [
            'photos.*.image' => 'Foto harus berupa gambar (JPG, PNG, atau WEBP).', 'photos.*.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photos.*.max' => 'Foto terlalu besar (maksimum '.round($max / 1024).' MB).', 'note.*.max' => 'Keterangan maksimum 500 karakter.',
        ]);

        try {
            $this->assertCanEdit($commissioning);
            $this->service->updateHeader($commissioning, $request->only(['reason_note', 'planned_date', 'area', 'meter_reading', 'meter_unit']));
            $this->service->saveAnswers(
                $commissioning,
                (array) $request->input('result', []),
                (array) $request->input('note', []),
                array_filter((array) $request->file('photos', [])),
                (array) $request->input('remove_photo', []),
            );

            if ($request->input('action') === 'submit') {
                $this->service->submit($commissioning, portal_user());

                return redirect()->route('cms.show', $commissioning)->with('success', 'Diajukan. Menunggu verifikasi Safety dan Workshop.');
            }
        } catch (DomainException $e) {
            return redirect()->route('cms.show', $commissioning)->with('error', $e->getMessage());
        }

        return redirect()->route('cms.show', $commissioning)->with('success', 'Jawaban disimpan.');
    }

    public function verify(Request $request, Commissioning $commissioning, string $stage)
    {
        abort_unless(in_array($stage, ['safety', 'workshop'], true), 404);
        abort_unless(portal_user()->hasRole('admin', $stage), 403);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        return $this->run(fn () => $this->service->verify($commissioning, $stage, portal_user(), $data['notes'] ?? null),
            $commissioning, 'Verifikasi '.($stage === 'safety' ? 'Safety' : 'Workshop').' dicatat.');
    }

    public function sendBack(Request $request, Commissioning $commissioning)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Alasan pengembalian wajib diisi.']);

        return $this->run(fn () => $this->service->sendBack($commissioning, portal_user(), $data['reason']), $commissioning, 'Dikembalikan ke pemeriksa untuk diperbaiki.');
    }

    public function approve(Request $request, Commissioning $commissioning)
    {
        $data = $request->validate([
            'result' => ['required', Rule::in(array_keys(Commissioning::RESULT_LABELS))],
            'notes' => ['nullable', 'string', 'max:500'],
            'valid_until' => ['nullable', 'date'],
        ], ['result.required' => 'Pilih keputusan.']);

        $until = filled($data['valid_until'] ?? null) ? Carbon::parse($data['valid_until']) : null;

        return $this->run(fn () => $this->service->approve($commissioning, portal_user(), $data['result'], $data['notes'] ?? null, $until),
            $commissioning, "Disetujui: unit {$commissioning->unit_code} dinyatakan ".strtolower(Commissioning::RESULT_LABELS[$data['result']]).'.');
    }

    public function cancel(Request $request, Commissioning $commissioning)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        return $this->run(fn () => $this->service->cancel($commissioning, portal_user(), $data['reason'] ?? null), $commissioning, 'Proses dibatalkan.');
    }

    /** Lembar laik operasi untuk dicetak / disimpan sebagai PDF (hanya proses yang sudah disetujui). */
    public function certificate(Commissioning $commissioning)
    {
        abort_unless($commissioning->status === 'approved', 404);

        return view('cms.certificate', ['c' => $commissioning->load('unit'), 'groups' => $commissioning->items()->get()->groupBy('group_name'), 'findings' => $commissioning->findings()->get()]);
    }

    public function photo(CommissioningItem $item)
    {
        $disk = Storage::disk(config('commissioning.photo_disk'));
        abort_unless($item->photo_path && $disk->exists($item->photo_path), 404);

        return $disk->response($item->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    private function assertCanEdit(Commissioning $c): void
    {
        $user = portal_user();
        $isAdmin = (bool) ($user->is_admin || $user->role === 'admin');
        if (! $isAdmin && (string) $c->requested_by_id !== (string) $user->id) {
            throw new DomainException('Hanya pemohon (atau administrator) yang bisa mengisi proses ini.');
        }
    }

    private function run(callable $action, Commissioning $c, string $success)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('cms.show', $c)->with('error', $e->getMessage());
        }

        return redirect()->route('cms.show', $c)->with('success', $success);
    }
}
