@extends('layouts.app')

@section('title', 'Insiden')
@section('page-title', 'Insiden dan Kejadian Unit')
@section('breadcrumb')
    <li class="breadcrumb-item active">Insiden</li>
@endsection

@section('content')
@if ($canReport)
<div class="card shadow-sm mb-4" id="form">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-cone-striped me-2 text-danger"></i>Lapor kejadian pada unit</h2></div>
    <div class="card-body">
        <p class="small text-muted mb-3">Kecelakaan, kerusakan besar, overhaul, dan modifikasi <strong>otomatis menahan unit (hold)</strong> sampai recommissioning disetujui. Near miss, pindah area, dan lainnya hanya dicatat.</p>
        <form method="POST" action="{{ route('cms.incidents.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3"><label class="form-label small mb-1">Unit <span class="text-danger">*</span></label>
                <input type="text" name="unit" value="{{ old('unit', $preset) }}" list="incUnits" class="form-control form-control-sm @error('unit') is-invalid @enderror" required autocomplete="off">
                <datalist id="incUnits">@foreach ($units as $u)<option value="{{ $u->code }}">{{ $u->name }}</option>@endforeach</datalist>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-3"><label class="form-label small mb-1">Jenis <span class="text-danger">*</span></label>
                <select name="kind" class="form-select form-select-sm" required>@foreach (config('commissioning.incident_kinds') as $key => [$label, $hold])<option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}{{ $hold ? ' (hold otomatis)' : '' }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small mb-1">Tingkat</label>
                <select name="severity" class="form-select form-select-sm">@foreach (config('commissioning.incident_severities') as $key => $label)<option value="{{ $key }}" @selected(old('severity') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small mb-1">Waktu kejadian <span class="text-danger">*</span></label>
                <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="form-control form-control-sm @error('occurred_at') is-invalid @enderror" required>
                @error('occurred_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label small mb-1">Uraian <span class="text-danger">*</span></label>
                <textarea name="description" rows="2" class="form-control form-control-sm @error('description') is-invalid @enderror" maxlength="1000" required>{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="requires_recommissioning" value="1" id="reqRe" @checked(old('requires_recommissioning'))><label class="form-check-label small" for="reqRe">Tahan unit (wajib recommissioning) walau jenisnya tidak otomatis hold</label></div>
                <button class="btn btn-danger btn-sm"><i class="bi bi-send me-1"></i>Catat kejadian</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.incidents') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-4"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option>@foreach (config('commissioning.incident_kinds') as $key => [$label])<option value="{{ $key }}" @selected($kind === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option><option value="open" @selected($status === 'open')>Menunggu recommissioning</option><option value="resolved" @selected($status === 'resolved')>Selesai</option></select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.incidents') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">{{ number_format($items->total()) }} kejadian</h2>
        <a href="{{ route('cms.report.incidents') }}" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Waktu</th><th>Unit</th><th>Jenis</th><th>Tingkat</th><th>Uraian</th><th>Status</th><th>Pelapor</th></tr></thead>
            <tbody>
                @forelse ($items as $i)
                    <tr>
                        <td class="small text-nowrap">{{ $i->occurred_at->translatedFormat('d M Y H:i') }}</td>
                        <td><a href="{{ route('cms.units.show', $i->unit) }}" class="fw-semibold text-decoration-none">{{ $i->unit->code }}</a></td>
                        <td class="small">{{ $i->kindLabel() }}</td>
                        <td class="small">{{ $i->severityLabel() }}</td>
                        <td class="small">{{ $i->description }}</td>
                        <td class="small">@if ($i->requires_recommissioning)<span class="badge {{ $i->status === 'open' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">{{ $i->status === 'open' ? 'Hold: wajib recommissioning' : 'Sudah recommissioning' }}</span>@else<span class="text-muted">Dicatat</span>@endif</td>
                        <td class="small">{{ $i->reported_by_name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>Tidak ada kejadian tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->hasPages())<div class="card-footer bg-white">{{ $items->links() }}</div>@endif
</div>
@endsection
