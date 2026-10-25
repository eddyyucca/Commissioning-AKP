@extends('layouts.app')

@section('title', 'Daftar Proses')
@section('page-title', 'Daftar Commissioning')
@section('breadcrumb')
    <li class="breadcrumb-item active">Daftar proses</li>
@endsection

@push('topbar-actions')
    @if (portal_user()->hasRole('admin', 'inspector', 'safety', 'workshop'))
        <a href="{{ route('cms.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-plus-circle"></i><span class="d-none d-sm-inline">Mulai commissioning</span></a>
    @endif
@endpush

@section('content')
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label>
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="No., unit, pemohon"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option>
                <option value="aktif" @selected($status === 'aktif')>Sedang berjalan</option>
                @foreach (\App\Models\Commissioning::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option>
                @foreach (\App\Models\Commissioning::KIND_LABELS as $key => $label)<option value="{{ $key }}" @selected($kind === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.index') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">{{ number_format($items->total()) }} proses</h2>
        <a href="{{ route('cms.report.commissionings') }}" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>No.</th><th>Unit</th><th>Jenis</th><th>Status</th><th>Tahap</th><th>Pemohon</th><th>Dibuat</th></tr></thead>
            <tbody>
                @forelse ($items as $c)
                    @php($b = \App\Models\Commissioning::STATUS_BADGES[$c->status])
                    <tr>
                        <td><a href="{{ route('cms.show', $c) }}" class="fw-semibold text-decoration-none small">{{ $c->number }}</a></td>
                        <td><a href="{{ route('cms.units.show', $c->unit_code) }}" class="text-decoration-none fw-semibold">{{ $c->unit_code }}</a><div class="small text-muted">{{ $c->unit_name }}</div></td>
                        <td class="small">{{ $c->kindLabel() }}@if ($c->reason)<div class="text-muted">{{ $c->reasonLabel() }}</div>@endif</td>
                        <td><span class="badge bg-{{ $b }}-subtle text-{{ $b === 'dark' ? 'dark' : $b }}">{{ $c->statusLabel() }}</span>@if ($c->result)<div class="small text-muted">{{ \App\Models\Commissioning::RESULT_LABELS[$c->result] }}</div>@endif</td>
                        <td class="small">{{ $c->stage() }}</td>
                        <td class="small">{{ $c->requested_by_name }}</td>
                        <td class="small text-nowrap">{{ $c->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-journal-check fs-1 d-block mb-2"></i>Belum ada proses yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->hasPages())<div class="card-footer bg-white">{{ $items->links() }}</div>@endif
</div>
@endsection
