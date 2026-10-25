@extends('layouts.app')

@section('title', 'Unit')
@section('page-title', 'Unit dan Alat')
@section('breadcrumb')
    <li class="breadcrumb-item active">Unit</li>
@endsection

@push('topbar-actions')
    @if ($canEdit)
        <a href="{{ route('cms.units.import') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-upload"></i><span class="d-none d-sm-inline">Impor CSV</span></a>
        <a href="{{ route('cms.units.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Tambah unit</span></a>
    @endif
@endpush

@section('content')
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.units') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label>
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Kode, nama, plat, serial"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Kategori</label>
            <select name="kategori" class="form-select form-select-sm"><option value="">Semua</option>
                @foreach (config('commissioning.categories') as $code => $name)<option value="{{ $code }}" @selected($category === $code)>{{ $code }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Area</label>
            <select name="area" class="form-select form-select-sm"><option value="">Semua</option>
                @foreach ($areas as $a)<option value="{{ $a }}" @selected($area === $a)>{{ $a }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option>
                @foreach (\App\Models\Unit::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.units') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">{{ number_format($units->total()) }} unit</h2>
        <a href="{{ route('cms.report.units') }}" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Area</th><th>Status</th><th>Berlaku sampai</th><th></th></tr></thead>
            <tbody>
                @forelse ($units as $u)
                    @php($left = $u->daysLeft())
                    <tr class="{{ $u->is_active ? '' : 'text-muted' }}">
                        <td class="fw-semibold"><a href="{{ route('cms.units.show', $u) }}" class="text-decoration-none">{{ $u->code }}</a>@unless ($u->is_active)<span class="badge bg-secondary-subtle text-secondary ms-1">nonaktif</span>@endunless</td>
                        <td>{{ $u->name }}<div class="small text-muted">{{ collect([$u->brand, $u->model])->filter()->implode(' ') }}</div></td>
                        <td class="small">{{ $u->category }}</td>
                        <td class="small">{{ $u->area ?: '-' }}</td>
                        <td><span class="badge bg-{{ \App\Models\Unit::STATUS_BADGES[$u->ops] }}-subtle text-{{ \App\Models\Unit::STATUS_BADGES[$u->ops] === 'dark' ? 'dark' : \App\Models\Unit::STATUS_BADGES[$u->ops] }}">{{ \App\Models\Unit::STATUS_LABELS[$u->ops] }}</span>
                            @if ($u->hold_reason)<div class="small text-danger text-truncate" style="max-width:240px" title="{{ $u->hold_reason }}">{{ $u->hold_reason }}</div>@endif</td>
                        <td class="small text-nowrap">
                            @if ($u->commissioned_until){{ $u->commissioned_until->translatedFormat('d M Y') }}<div class="{{ $left < 0 ? 'text-danger' : ($left <= config('commissioning.expiring_days') ? 'text-warning' : 'text-muted') }}">{{ $left < 0 ? 'lewat '.abs($left).' hari' : $left.' hari lagi' }}</div>
                            @else - @endif
                        </td>
                        <td class="text-end"><a href="{{ route('cms.units.show', $u) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-truck-front fs-1 d-block mb-2"></i>Tidak ada unit yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($units->hasPages())<div class="card-footer bg-white">{{ $units->links() }}</div>@endif
</div>
@endsection
