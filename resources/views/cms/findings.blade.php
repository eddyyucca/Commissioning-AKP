@extends('layouts.app')

@section('title', 'Temuan')
@section('page-title', 'Temuan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Temuan</li>
@endsection

@section('content')
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.findings') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label><input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Temuan, unit, PIC"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="aktif" @selected($status === 'aktif')>Belum selesai</option><option value="all" @selected($status === 'all')>Semua</option>
                @foreach (\App\Models\Finding::STATUS_LABELS as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Tingkat</label>
            <select name="tingkat" class="form-select form-select-sm"><option value="">Semua</option>@foreach (\App\Models\Finding::SEVERITY_LABELS as $k => $l)<option value="{{ $k }}" @selected($severity === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.findings') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">{{ number_format($items->total()) }} temuan</h2>
        <a href="{{ route('cms.report.findings') }}" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($items as $f)
            <div class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}-subtle text-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}">{{ \App\Models\Finding::SEVERITY_LABELS[$f->severity] }}</span>
                        <a href="{{ route('cms.units.show', $f->unit) }}" class="fw-semibold text-decoration-none ms-1">{{ $f->unit->code }}</a>
                        <span class="ms-1">{{ $f->title }}</span>
                        <div class="small text-muted">
                            <a href="{{ route('cms.show', $f->commissioning->number) }}" class="text-decoration-none">{{ $f->commissioning->number }}</a>
                            @if ($f->description) &middot; {{ $f->description }}@endif
                        </div>
                        <div class="small text-muted">{{ $f->pic_name ? 'PIC '.$f->pic_name.' · ' : '' }}{{ $f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat' }}@if ($f->isOverdue()) <span class="text-danger">(terlambat)</span>@endif @if ($f->action_plan) &middot; {{ $f->action_plan }}@endif</div>
                        @if ($f->status === 'closed')<div class="small text-success"><i class="bi bi-check2-circle"></i> {{ $f->closed_by_name }} &middot; {{ $f->closed_at?->translatedFormat('d M Y') }}: {{ $f->closed_note }}</div>@endif
                    </div>
                    <div><span class="badge {{ $f->status === 'closed' ? 'bg-success-subtle text-success' : ($f->status === 'in_progress' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning') }}">{{ \App\Models\Finding::STATUS_LABELS[$f->status] }}</span></div>
                </div>
                @if ($f->isOpen() && $canUpdate)
                    <form method="POST" action="{{ route('cms.findings.update', $f) }}" class="row g-2 mt-1 align-items-end">
                        @csrf @method('PUT')
                        <div class="col-md-3"><input type="text" name="pic_name" value="{{ $f->pic_name }}" class="form-control form-control-sm" placeholder="PIC perbaikan" maxlength="120"></div>
                        <div class="col-md-2"><input type="date" name="due_date" value="{{ $f->due_date?->toDateString() }}" class="form-control form-control-sm" title="Tenggat"></div>
                        <div class="col-md-4"><input type="text" name="action_plan" value="{{ $f->action_plan }}" class="form-control form-control-sm" placeholder="Rencana tindakan" maxlength="500"></div>
                        <div class="col-md-3 d-flex gap-1">
                            @if ($canSeverity)<select name="severity" class="form-select form-select-sm">@foreach (\App\Models\Finding::SEVERITY_LABELS as $k => $l)<option value="{{ $k }}" @selected($f->severity === $k)>{{ $l }}</option>@endforeach</select>@endif
                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                        </div>
                    </form>
                    @if ($canClose)
                        <form method="POST" action="{{ route('cms.findings.close', $f) }}" class="row g-2 mt-1">
                            @csrf
                            <div class="col-md-9"><input type="text" name="note" class="form-control form-control-sm" placeholder="Bukti perbaikan / catatan penutupan" maxlength="500" required></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-success w-100"><i class="bi bi-check2 me-1"></i>Tutup temuan</button></div>
                        </form>
                    @endif
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-5"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>Tidak ada temuan yang cocok.</div>
        @endforelse
    </div>
    @if ($items->hasPages())<div class="card-footer bg-white">{{ $items->links() }}</div>@endif
</div>
@endsection
