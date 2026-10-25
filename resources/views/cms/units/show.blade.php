@extends('layouts.app')

@section('title', 'Unit '.$unit->code)
@section('page-title', 'Unit '.$unit->code)
@section('back-url', route('cms.units'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.units') }}">Unit</a></li>
    <li class="breadcrumb-item active">{{ $unit->code }}</li>
@endsection

@section('content')
@php
    $left = $unit->daysLeft();
    $badge = \App\Models\Unit::STATUS_BADGES[$ops];
    $total = max((int) ($unit->last_commissioned_at && $unit->commissioned_until ? $unit->last_commissioned_at->startOfDay()->diffInDays($unit->commissioned_until) : 0), 1);
    $pct = $left === null ? 0 : max(0, min(100, round($left / $total * 100)));
    $barColor = $left === null ? '#94a3b8' : ($left < 0 ? '#dc2626' : ($left <= config('commissioning.expiring_days') ? '#f59e0b' : '#16a34a'));
@endphp

@if ($unit->hold_since)
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-sign-stop-fill fs-4"></i>
        <div><strong>Unit ditahan (hold) sejak {{ $unit->hold_since->translatedFormat('d M Y H:i') }}.</strong> Tidak boleh dioperasikan sampai recommissioning disetujui.
            <div class="small">{{ $unit->hold_reason }}</div></div>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <div class="small text-muted">{{ $unit->categoryName() }}@if ($unit->area) &middot; {{ $unit->area }}@endif</div>
                    <h2 class="h4 mb-1">{{ $unit->code }} <span class="fw-normal text-muted fs-6">{{ $unit->name }}</span></h2>
                    <div class="small text-muted">{{ collect([$unit->brand, $unit->model, $unit->year])->filter()->implode(' · ') }}@if ($unit->serial_number) &middot; SN {{ $unit->serial_number }}@endif @if ($unit->plate) &middot; {{ $unit->plate }}@endif &middot; Pemilik {{ $unit->owner }}</div>
                    @unless ($unit->is_active)<span class="badge bg-secondary-subtle text-secondary mt-1">Unit nonaktif</span>@endunless
                </div>
                <div class="text-end">
                    <span class="badge fs-6 bg-{{ $badge }}-subtle text-{{ $badge === 'dark' ? 'dark' : $badge }}">{{ \App\Models\Unit::STATUS_LABELS[$ops] }}</span>
                    @if ($unit->commissioned_until)
                        <div class="small mt-1">Berlaku sampai <strong>{{ $unit->commissioned_until->translatedFormat('d M Y') }}</strong></div>
                    @endif
                </div>
            </div>

            @if ($unit->commissioned_until)
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Terakhir laik {{ $unit->last_commissioned_at?->translatedFormat('d M Y') }}</span>
                        <span>{{ $left < 0 ? 'Lewat '.abs($left).' hari' : $left.' hari lagi' }}</span>
                    </div>
                    <div class="progress" style="height:10px"><div class="progress-bar" role="progressbar" style="width: {{ $pct }}%; background: {{ $barColor }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>
            @endif

            @if ($unit->idle_since)
                <div class="alert alert-warning py-2 mt-3 mb-0 small d-flex justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-pause-circle me-1"></i>Unit idle sejak {{ $unit->idle_since->translatedFormat('d M Y') }} ({{ $unit->idle_since->diffInDays(today()) }} hari). Lakukan recommissioning sebelum dipakai lagi.</span>
                    @if ($canDocs)<form method="POST" action="{{ route('cms.units.idle.clear', $unit) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Hapus tanda idle</button></form>@endif
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if ($activeProcess)
                    <a href="{{ route('cms.show', $activeProcess) }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-check me-1"></i>Lanjutkan {{ $activeProcess->number }} ({{ $activeProcess->statusLabel() }})</a>
                @elseif ($canStart && $unit->is_active)
                    <a href="{{ route('cms.create', ['unit' => $unit->code]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>{{ $unit->last_commissioning_id ? 'Mulai recommissioning' : 'Mulai commissioning' }}</a>
                @endif
                @if ($canIncident)<a href="{{ route('cms.incidents', ['unit' => $unit->code]) }}#form" class="btn btn-outline-danger btn-sm"><i class="bi bi-cone-striped me-1"></i>Lapor kejadian</a>@endif
                @if ($canEdit)<a href="{{ route('cms.units.edit', $unit) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Ubah</a>@endif
                @if ($canDocs && ! $unit->idle_since)
                    <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#idleForm"><i class="bi bi-pause-circle me-1"></i>Tandai idle</button>
                @endif
            </div>
            @if ($canDocs && ! $unit->idle_since)
                <div class="collapse mt-2" id="idleForm">
                    <form method="POST" action="{{ route('cms.units.idle', $unit) }}" class="d-flex gap-2 align-items-end">
                        @csrf
                        <div><label class="form-label small mb-1">Idle sejak</label><input type="date" name="idle_since" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div>
                        <button class="btn btn-sm btn-warning">Simpan</button>
                    </form>
                    <div class="form-text">Setelah {{ config('commissioning.idle_days') }} hari idle, sistem mengirim alert agar unit di-recommissioning sebelum dioperasikan.</div>
                </div>
            @endif
        </div></div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-bell me-2"></i>Alert aktif ({{ $alerts->count() }})</h2></div>
            <div class="list-group list-group-flush">
                @forelse ($alerts as $a)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2"><span class="fw-semibold small">{{ $a->title }}</span>
                            <span class="badge bg-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }}-subtle text-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }} align-self-start">{{ \App\Models\Alert::SEVERITY_LABELS[$a->severity] }}</span></div>
                        <div class="small text-muted">{{ $a->message }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small"><i class="bi bi-check2-circle fs-3 d-block text-success"></i>Tidak ada alert untuk unit ini.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Riwayat --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat commissioning</h2></div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>No.</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Hasil</th><th>Berlaku sampai</th><th>Pemohon</th></tr></thead>
            <tbody>
                @forelse ($history as $h)
                    <tr>
                        <td><a href="{{ route('cms.show', $h) }}" class="text-decoration-none small">{{ $h->number }}</a><div class="small text-muted">{{ $h->created_at->translatedFormat('d M Y') }}</div></td>
                        <td class="small">{{ $h->kindLabel() }}</td>
                        <td class="small">{{ $h->reasonLabel() ?? '-' }}</td>
                        <td><span class="badge bg-{{ \App\Models\Commissioning::STATUS_BADGES[$h->status] }}-subtle text-{{ \App\Models\Commissioning::STATUS_BADGES[$h->status] === 'dark' ? 'dark' : \App\Models\Commissioning::STATUS_BADGES[$h->status] }}">{{ $h->statusLabel() }}</span></td>
                        <td class="small">{{ $h->result ? \App\Models\Commissioning::RESULT_LABELS[$h->result] : '-' }}</td>
                        <td class="small">{{ $h->valid_until?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="small">{{ $h->requested_by_name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada commissioning untuk unit ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0"><i class="bi bi-exclamation-diamond me-2"></i>Temuan terbuka ({{ $findings->count() }})</h2><a href="{{ route('cms.findings', ['q' => $unit->code]) }}" class="small text-decoration-none">Lihat</a></div>
            <div class="list-group list-group-flush">
                @forelse ($findings as $f)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2"><span class="fw-semibold small">{{ $f->title }}</span>
                            <span class="badge bg-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}-subtle text-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}">{{ \App\Models\Finding::SEVERITY_LABELS[$f->severity] }}</span></div>
                        <div class="small text-muted">{{ $f->pic_name ? 'PIC '.$f->pic_name.' · ' : '' }}{{ $f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat' }}@if ($f->isOverdue()) <span class="text-danger">(terlambat)</span>@endif</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">Tidak ada temuan terbuka.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6" id="dokumen">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Dokumen unit ({{ $documents->count() }})</h2>
                @if ($canDocs)<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#docForm"><i class="bi bi-plus-lg"></i> Tambah</button>@endif
            </div>
            @if ($canDocs)
                <div class="collapse border-bottom" id="docForm"><form method="POST" action="{{ route('cms.units.docs.store', $unit) }}" class="p-3 row g-2">
                    @csrf
                    <div class="col-6"><label class="form-label small mb-1">Jenis</label>
                        <select name="type" class="form-select form-select-sm" required>@foreach (config('commissioning.document_types') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small mb-1">Nomor</label><input type="text" name="number" class="form-control form-control-sm" maxlength="80"></div>
                    <div class="col-6"><label class="form-label small mb-1">Terbit</label><input type="date" name="issued_at" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small mb-1">Berakhir</label><input type="date" name="expires_at" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small mb-1">Catatan</label><input type="text" name="notes" class="form-control form-control-sm" maxlength="255"></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary">Simpan dokumen</button></div>
                </form></div>
            @endif
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <tbody>
                        @forelse ($documents as $d)
                            @php($state = $d->state())
                            <tr>
                                <td><div class="fw-semibold small">{{ $d->typeLabel() }}</div><div class="small text-muted">{{ $d->number ?: '-' }}</div></td>
                                <td class="small text-nowrap">
                                    @if ($d->expires_at){{ $d->expires_at->translatedFormat('d M Y') }}
                                        <div class="{{ $state === 'expired' ? 'text-danger' : ($state === 'expiring' ? 'text-warning' : 'text-muted') }}">{{ $d->daysLeft() < 0 ? 'lewat '.abs($d->daysLeft()).' hari' : $d->daysLeft().' hari lagi' }}</div>
                                    @else <span class="text-muted">tanpa tanggal berakhir</span>@endif
                                </td>
                                <td class="text-end">@if ($canDocs)<form method="POST" action="{{ route('cms.units.docs.destroy', [$unit, $d]) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form>@endif</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-4 small">Belum ada dokumen. Catat sertifikat dan masa berlakunya agar dipantau otomatis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if ($incidents->isNotEmpty())
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-cone-striped me-2"></i>Kejadian pada unit</h2></div>
    <div class="table-responsive"><table class="table table-modern align-middle mb-0">
        <thead><tr><th>Waktu</th><th>Jenis</th><th>Tingkat</th><th>Uraian</th><th>Status</th></tr></thead>
        <tbody>@foreach ($incidents as $i)
            <tr><td class="small text-nowrap">{{ $i->occurred_at->translatedFormat('d M Y H:i') }}</td><td class="small">{{ $i->kindLabel() }}</td><td class="small">{{ $i->severityLabel() }}</td>
                <td class="small">{{ $i->description }}</td>
                <td class="small">@if ($i->requires_recommissioning)<span class="badge {{ $i->status === 'open' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">{{ $i->status === 'open' ? 'Wajib recommissioning' : 'Sudah recommissioning' }}</span>@else<span class="text-muted">Dicatat</span>@endif</td></tr>
        @endforeach</tbody>
    </table></div>
</div>
@endif
@endsection
