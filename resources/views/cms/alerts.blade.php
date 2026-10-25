@extends('layouts.app')

@section('title', 'Alert')
@section('page-title', 'Alert Unit dan Alat')
@section('breadcrumb')
    <li class="breadcrumb-item active">Alert</li>
@endsection

@push('topbar-actions')
    @if ($canScan)
        <form method="POST" action="{{ route('cms.alerts.scan') }}">@csrf<button class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-clockwise"></i><span class="d-none d-sm-inline">Pindai sekarang</span></button></form>
    @endif
@endpush

@push('styles')
<style>
    .sev-kritis { border-left: 4px solid #dc2626; } .sev-tinggi { border-left: 4px solid #f59e0b; }
    .sev-sedang { border-left: 4px solid #2563eb; } .sev-info { border-left: 4px solid #94a3b8; }
</style>
@endpush

@section('content')
<div class="row g-3 mb-3">
    @foreach (\App\Models\Alert::SEVERITY_LABELS as $key => $label)
        <div class="col-6 col-md-3">
            <a href="{{ route('cms.alerts', ['tingkat' => $key]) }}" class="text-decoration-none text-reset"><div class="erp-card h-100"><div class="erp-card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-{{ \App\Models\Alert::SEVERITY_BADGES[$key] }}-subtle text-{{ \App\Models\Alert::SEVERITY_BADGES[$key] }}"><i class="bi bi-bell"></i></div>
                <div><div class="kpi-label">{{ $label }}</div><div class="kpi-value fs-5 fw-bold">{{ $counts[$key] ?? 0 }}</div></div>
            </div></div></a>
        </div>
    @endforeach
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.alerts') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3"><label class="form-label small mb-1">Cari</label><input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Unit atau uraian"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                @foreach (['aktif' => 'Aktif', 'baru' => 'Belum dilihat', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $k => $l)<option value="{{ $k }}" @selected($state === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Tingkat</label>
            <select name="tingkat" class="form-select form-select-sm"><option value="">Semua</option>@foreach (\App\Models\Alert::SEVERITY_LABELS as $k => $l)<option value="{{ $k }}" @selected($severity === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-12 col-md-3"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option>@foreach (\App\Models\Alert::TYPE_LABELS as $k => $l)<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.alerts') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0">{{ number_format($items->total()) }} alert</h2>
        <div class="d-flex gap-3 align-items-center">
            <a href="{{ route('cms.report.alerts') }}" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
            @if ($canAck)<form method="POST" action="{{ route('cms.alerts.ackall') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Tandai semua sudah dilihat</button></form>@endif
        </div>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($items as $a)
            <div class="list-group-item sev-{{ $a->severity }} {{ $a->resolved_at ? 'opacity-50' : '' }}">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }}-subtle text-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }}">{{ \App\Models\Alert::SEVERITY_LABELS[$a->severity] }}</span>
                        <span class="badge bg-light text-muted border">{{ $a->typeLabel() }}</span>
                        @if ($a->unit)<a href="{{ route('cms.units.show', $a->unit) }}" class="fw-semibold text-decoration-none ms-1">{{ $a->unit->code }}</a>@endif
                        <div class="fw-semibold mt-1">{{ $a->title }}</div>
                        <div class="small text-muted">{{ $a->message }}</div>
                        <div class="small text-muted">Pertama terlihat {{ $a->first_seen_at->diffForHumans() }}@if ($a->due_date) &middot; tenggat {{ $a->due_date->translatedFormat('d M Y') }}@endif
                            @if ($a->acknowledged_at) &middot; dilihat {{ $a->acknowledged_by_name }} ({{ $a->acknowledged_at->diffForHumans() }})@endif
                            @if ($a->resolved_at) &middot; <span class="text-success">selesai {{ $a->resolved_at->diffForHumans() }}</span>@endif</div>
                    </div>
                    <div class="text-nowrap">
                        @if (! $a->resolved_at && ! $a->acknowledged_at && $canAck)
                            <form method="POST" action="{{ route('cms.alerts.ack', $a) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Sudah dilihat</button></form>
                        @elseif (! $a->resolved_at && $a->acknowledged_at)<span class="badge bg-success-subtle text-success"><i class="bi bi-eye"></i> Dilihat</span>@endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2 text-success"></i>Tidak ada alert pada pilihan ini.</div>
        @endforelse
    </div>
    @if ($items->hasPages())<div class="card-footer bg-white">{{ $items->links() }}</div>@endif
</div>
<p class="small text-muted mt-3">Alert dihitung otomatis dari masa berlaku commissioning, dokumen unit, temuan, hold, dan unit idle. Alert hilang sendiri ketika kondisinya sudah beres.</p>
@endsection
