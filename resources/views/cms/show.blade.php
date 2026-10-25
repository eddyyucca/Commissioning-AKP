@extends('layouts.app')

@section('title', $c->number)
@section('page-title', $c->kindLabel().' '.$c->number)
@section('back-url', route('cms.index'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.index') }}">Daftar proses</a></li>
    <li class="breadcrumb-item active">{{ $c->number }}</li>
@endsection

@push('topbar-actions')
    @if ($c->status === 'approved')
        <a href="{{ route('cms.certificate', $c) }}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-printer"></i><span class="d-none d-sm-inline">Lembar laik operasi</span></a>
    @endif
@endpush

@push('styles')
<style>
    .stepper { display: flex; flex-wrap: wrap; gap: .5rem; }
    .step { flex: 1 1 140px; border: 1px solid #e5eaf1; border-radius: 12px; padding: .6rem .8rem; background: #fff; font-size: .82rem; }
    .step .t { font-weight: 600; } .step .s { color: #64748b; font-size: .76rem; }
    .step.done { border-color: #16a34a; background: #f0fdf4; } .step.now { border-color: #1b4f86; background: #eff6ff; } .step.bad { border-color: #f59e0b; background: #fffbeb; }
    .item-row { border-bottom: 1px solid #eef1f6; padding: .7rem 0; }
    .item-row:last-child { border-bottom: 0; }
    .item-row.is-bad { background: #fff5f5; margin: 0 -1rem; padding-left: 1rem; padding-right: 1rem; }
    .crit { color: #dc2626; font-size: .72rem; font-weight: 700; }
    .btn-check:checked + .btn-outline-success { background: #16a34a; color: #fff; }
    .btn-check:checked + .btn-outline-danger { background: #dc2626; color: #fff; }
    .btn-check:checked + .btn-outline-secondary { background: #64748b; color: #fff; }
</style>
@endpush

@section('content')
@php
    $b = \App\Models\Commissioning::STATUS_BADGES[$c->status];
    $answered = $items->whereNotNull('result')->count();
    $bad = $items->where('result', 'bad')->count();
    $critBad = $items->where('result', 'bad')->where('is_critical', true)->count();
    $steps = [
        ['t' => 'Diisi pemeriksa', 's' => $c->requested_by_name, 'c' => in_array($c->status, ['draft', 'rejected'], true) ? ($c->status === 'rejected' ? 'bad' : 'now') : 'done'],
        ['t' => 'Verifikasi Safety', 's' => $c->safety_at ? $c->safety_by_name.' · '.$c->safety_at->translatedFormat('d M H:i') : 'menunggu', 'c' => $c->safety_at ? 'done' : ($c->status === 'submitted' ? 'now' : '')],
        ['t' => 'Verifikasi Workshop', 's' => $c->workshop_at ? $c->workshop_by_name.' · '.$c->workshop_at->translatedFormat('d M H:i') : 'menunggu', 'c' => $c->workshop_at ? 'done' : ($c->status === 'submitted' ? 'now' : '')],
        ['t' => 'Keputusan', 's' => $c->decided_at ? $c->decided_by_name.' · '.$c->decided_at->translatedFormat('d M H:i') : ($c->status === 'submitted' && $c->bothVerified() ? 'menunggu penyetuju' : '-'),
            'c' => $c->status === 'approved' ? 'done' : ($c->status === 'submitted' && $c->bothVerified() ? 'now' : '')],
    ];
@endphp

{{-- Ringkasan --}}
<div class="card shadow-sm mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <div>
            <div class="small text-muted">{{ $c->kindLabel() }}@if ($c->reason) &middot; {{ $c->reasonLabel() }}@endif</div>
            <h2 class="h5 mb-1"><a href="{{ route('cms.units.show', $c->unit_code) }}" class="text-decoration-none">{{ $c->unit_code }}</a> <span class="fw-normal text-muted fs-6">{{ $c->unit_name }}</span></h2>
            <div class="small text-muted">Pemohon {{ $c->requested_by_name }} &middot; dibuat {{ $c->created_at->translatedFormat('d M Y H:i') }}@if ($c->area) &middot; {{ $c->area }}@endif
                @if ($c->meter_reading !== null) &middot; meter {{ number_format($c->meter_reading, 0, ',', '.') }} {{ $c->meter_unit }}@endif</div>
            @if ($c->reason_note)<div class="small mt-1">{{ $c->reason_note }}</div>@endif
        </div>
        <div class="text-end">
            <span class="badge fs-6 bg-{{ $b }}-subtle text-{{ $b === 'dark' ? 'dark' : $b }}">{{ $c->statusLabel() }}</span>
            @if ($c->result)<div class="fw-bold mt-1 {{ $c->result === 'laik' ? 'text-success' : 'text-warning' }}">{{ \App\Models\Commissioning::RESULT_LABELS[$c->result] }}</div>@endif
            @if ($c->valid_until)<div class="small">Berlaku sampai <strong>{{ $c->valid_until->translatedFormat('d M Y') }}</strong></div>@endif
        </div>
    </div>
    <div class="stepper">
        @foreach ($steps as $s)<div class="step {{ $s['c'] }}"><div class="t">{{ $loop->iteration }}. {{ $s['t'] }}</div><div class="s">{{ $s['s'] }}</div></div>@endforeach
    </div>
    @if ($c->status === 'rejected' && $c->returned_reason)
        <div class="alert alert-warning mt-3 mb-0"><strong>Dikembalikan untuk diperbaiki:</strong> {{ $c->returned_reason }}</div>
    @endif
    @if ($c->status === 'cancelled' && $c->cancelled_reason)
        <div class="alert alert-secondary mt-3 mb-0"><strong>Dibatalkan:</strong> {{ $c->cancelled_reason }}</div>
    @endif
    @if ($c->decision_notes)<div class="alert alert-info mt-3 mb-0"><strong>Catatan keputusan:</strong> {{ $c->decision_notes }}</div>@endif
    @if ($c->safety_notes || $c->workshop_notes)
        <div class="small text-muted mt-3">
            @if ($c->safety_notes)<div><strong>Catatan Safety:</strong> {{ $c->safety_notes }}</div>@endif
            @if ($c->workshop_notes)<div><strong>Catatan Workshop:</strong> {{ $c->workshop_notes }}</div>@endif
        </div>
    @endif
</div></div>

{{-- Checklist --}}
@if ($can['edit'])
<form method="POST" action="{{ route('cms.answers', $c) }}" enctype="multipart/form-data" id="answerForm">
    @csrf @method('PUT')
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-info-circle me-2"></i>Data pemeriksaan</h2></div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label small mb-1">Rencana tanggal</label><input type="date" name="planned_date" value="{{ old('planned_date', $c->planned_date?->toDateString()) }}" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label small mb-1">Area / lokasi</label><input type="text" name="area" value="{{ old('area', $c->area) }}" class="form-control form-control-sm" maxlength="80"></div>
            <div class="col-md-3"><label class="form-label small mb-1">Pembacaan meter</label>
                <div class="input-group input-group-sm"><input type="number" name="meter_reading" value="{{ old('meter_reading', $c->meter_reading) }}" class="form-control" min="0">
                    <select name="meter_unit" class="form-select" style="max-width:80px"><option value="jam" @selected(old('meter_unit', $c->meter_unit) === 'jam')>jam</option><option value="km" @selected(old('meter_unit', $c->meter_unit) === 'km')>km</option></select></div></div>
            <div class="col-md-3"><label class="form-label small mb-1">Keterangan alasan</label><input type="text" name="reason_note" value="{{ old('reason_note', $c->reason_note) }}" class="form-control form-control-sm" maxlength="255"></div>
        </div>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0"><i class="bi bi-ui-checks me-2"></i>Checklist ({{ $items->count() }} item)</h2>
        <div class="small text-muted">
            <span id="progressText">{{ $answered }} dari {{ $items->count() }} terisi</span> &middot;
            <span class="text-danger"><span id="badCount">{{ $bad }}</span> tidak sesuai</span>
            @if ($critBad > 0)<span class="badge bg-danger ms-1">{{ $critBad }} kritis</span>@endif
        </div>
    </div>
    <div class="card-body">
        @foreach ($groups as $groupName => $groupItems)
            <h3 class="h6 text-primary mt-3 mb-2">{{ $groupName }}</h3>
            @foreach ($groupItems as $item)
                @php($res = old('result.'.$item->id, $item->result))
                <div class="item-row {{ $res === 'bad' ? 'is-bad' : '' }}" data-item>
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="me-2">
                            <span class="fw-semibold">{{ $item->label }}</span>@if ($item->is_critical) <span class="crit">KRITIS</span>@endif
                            @if ($item->reference)<div class="small text-muted">Acuan: {{ $item->reference }}</div>@endif
                        </div>
                        @if ($can['edit'])
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="result[{{ $item->id }}]" id="r{{ $item->id }}ok" value="ok" @checked($res === 'ok')>
                                <label class="btn btn-outline-success" for="r{{ $item->id }}ok">Sesuai</label>
                                <input type="radio" class="btn-check" name="result[{{ $item->id }}]" id="r{{ $item->id }}bad" value="bad" @checked($res === 'bad')>
                                <label class="btn btn-outline-danger" for="r{{ $item->id }}bad">Tidak sesuai</label>
                                <input type="radio" class="btn-check" name="result[{{ $item->id }}]" id="r{{ $item->id }}na" value="na" @checked($res === 'na')>
                                <label class="btn btn-outline-secondary" for="r{{ $item->id }}na">N/A</label>
                            </div>
                        @else
                            <div>
                                @if ($item->result === 'ok')<span class="badge bg-success-subtle text-success">Sesuai</span>
                                @elseif ($item->result === 'bad')<span class="badge bg-danger-subtle text-danger">Tidak sesuai</span>
                                @elseif ($item->result === 'na')<span class="badge bg-secondary-subtle text-secondary">N/A</span>
                                @else<span class="badge bg-light text-muted">Belum diisi</span>@endif
                            </div>
                        @endif
                    </div>
                    @if ($can['edit'])
                        <div class="row g-2 mt-1">
                            <div class="col-md-8"><input type="text" name="note[{{ $item->id }}]" value="{{ old('note.'.$item->id, $item->note) }}" class="form-control form-control-sm" maxlength="500" placeholder="Keterangan (wajib bila tidak sesuai atau N/A pada item kritis)"></div>
                            <div class="col-md-4 d-flex gap-2 align-items-center">
                                <input type="file" name="photos[{{ $item->id }}]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
                                @if ($item->photo_path)<a href="{{ route('cms.photo', $item) }}" target="_blank" class="small text-nowrap">foto</a>
                                    <label class="small text-nowrap"><input type="checkbox" name="remove_photo[{{ $item->id }}]" value="1"> hapus</label>@endif
                            </div>
                        </div>
                    @else
                        @if ($item->note || $item->photo_path)
                            <div class="small mt-1 text-muted">{{ $item->note }}@if ($item->photo_path) <a href="{{ route('cms.photo', $item) }}" target="_blank"><i class="bi bi-image ms-1"></i> foto</a>@endif</div>
                        @endif
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
    @if ($can['edit'])
        <div class="card-footer bg-white d-flex flex-wrap gap-2">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i>Simpan</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Ajukan sekarang? Setelah diajukan, isian tidak bisa diubah kecuali dikembalikan.')"><i class="bi bi-send me-1"></i>Simpan dan ajukan</button>
        </div>
    @endif
</div>
@if ($can['edit'])</form>@endif

{{-- Temuan --}}
@if ($findings->isNotEmpty())
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><i class="bi bi-exclamation-diamond me-2"></i>Temuan ({{ $openFindings->count() }} terbuka dari {{ $findings->count() }})</h2>
        @if ($openBlocking->isNotEmpty())<span class="badge bg-danger">{{ $openBlocking->count() }} kritis/mayor menghalangi persetujuan</span>@endif
    </div>
    <div class="list-group list-group-flush">
        @foreach ($findings as $f)
            <div class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}-subtle text-{{ \App\Models\Finding::SEVERITY_BADGES[$f->severity] }}">{{ \App\Models\Finding::SEVERITY_LABELS[$f->severity] }}</span>
                        <span class="fw-semibold ms-1">{{ $f->title }}</span>
                        <div class="small text-muted">{{ $f->description }}</div>
                        <div class="small text-muted">{{ $f->pic_name ? 'PIC '.$f->pic_name.' · ' : '' }}{{ $f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat' }}@if ($f->isOverdue()) <span class="text-danger">(terlambat)</span>@endif
                            @if ($f->action_plan) &middot; {{ $f->action_plan }}@endif</div>
                    </div>
                    <div><span class="badge {{ $f->status === 'closed' ? 'bg-success-subtle text-success' : ($f->status === 'in_progress' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning') }}">{{ \App\Models\Finding::STATUS_LABELS[$f->status] }}</span></div>
                </div>
                @if ($f->status === 'closed' && $f->closed_note)<div class="small text-success mt-1"><i class="bi bi-check2-circle"></i> {{ $f->closed_by_name }}: {{ $f->closed_note }}</div>@endif
                @if ($f->isOpen() && $can['findings'])
                    <form method="POST" action="{{ route('cms.findings.update', $f) }}" class="row g-2 mt-1 align-items-end">
                        @csrf @method('PUT')
                        <div class="col-md-3"><input type="text" name="pic_name" value="{{ $f->pic_name }}" class="form-control form-control-sm" placeholder="PIC perbaikan" maxlength="120"></div>
                        <div class="col-md-2"><input type="date" name="due_date" value="{{ $f->due_date?->toDateString() }}" class="form-control form-control-sm" title="Tenggat"></div>
                        <div class="col-md-4"><input type="text" name="action_plan" value="{{ $f->action_plan }}" class="form-control form-control-sm" placeholder="Rencana tindakan" maxlength="500"></div>
                        <div class="col-md-3 d-flex gap-1">
                            @if ($can['closeFinding'])<select name="severity" class="form-select form-select-sm" title="Tingkat">@foreach (\App\Models\Finding::SEVERITY_LABELS as $k => $l)<option value="{{ $k }}" @selected($f->severity === $k)>{{ $l }}</option>@endforeach</select>@endif
                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                        </div>
                    </form>
                    @if ($can['closeFinding'])
                        <form method="POST" action="{{ route('cms.findings.close', $f) }}" class="row g-2 mt-1">
                            @csrf
                            <div class="col-md-9"><input type="text" name="note" class="form-control form-control-sm" placeholder="Bukti perbaikan / catatan penutupan" maxlength="500" required></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-success w-100"><i class="bi bi-check2 me-1"></i>Tutup temuan</button></div>
                        </form>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Tindakan alur --}}
@if ($can['safety'] || $can['workshop'] || $can['approve'] || $can['sendBack'] || $can['cancel'])
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-diagram-3 me-2"></i>Tindakan</h2></div>
    <div class="card-body">
        <div class="row g-4">
            @foreach (['safety' => 'Safety', 'workshop' => 'Workshop'] as $stage => $label)
                @if ($can[$stage])
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('cms.verify', [$c, $stage]) }}">
                            @csrf
                            <label class="form-label fw-semibold">Verifikasi {{ $label }}</label>
                            <input type="text" name="notes" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Catatan verifikasi (opsional)">
                            <button class="btn btn-success btn-sm"><i class="bi bi-shield-check me-1"></i>Verifikasi {{ $label }}</button>
                        </form>
                    </div>
                @endif
            @endforeach

            @if ($can['approve'])
                <div class="col-12"><hr class="my-0"></div>
                <div class="col-12">
                    @if (! $c->bothVerified())
                        <div class="alert alert-secondary py-2 small mb-3">Keputusan menunggu verifikasi Safety dan Workshop lengkap.</div>
                    @elseif ($openBlocking->isNotEmpty())
                        <div class="alert alert-danger py-2 small mb-3">Ada {{ $openBlocking->count() }} temuan kritis/mayor yang masih terbuka. Tutup dulu, atau kembalikan untuk perbaikan.</div>
                    @endif
                    <form method="POST" action="{{ route('cms.approve', $c) }}" class="row g-2">
                        @csrf
                        <div class="col-12 fw-semibold">Keputusan akhir</div>
                        <div class="col-md-5">
                            <select name="result" class="form-select form-select-sm" required>
                                <option value="laik">Laik operasi</option>
                                <option value="laik_catatan" @selected($openFindings->isNotEmpty() && $openBlocking->isEmpty())>Laik dengan catatan (ada temuan minor)</option>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="date" name="valid_until" class="form-control form-control-sm" max="{{ $maxUntil->toDateString() }}" min="{{ today()->addDay()->toDateString() }}" title="Berlaku sampai (kosong = {{ $maxUntil->translatedFormat('d M Y') }})"></div>
                        <div class="col-md-4"><input type="text" name="notes" class="form-control form-control-sm" maxlength="500" placeholder="Catatan (wajib bila laik dengan catatan)"></div>
                        <div class="col-12 form-text">Masa berlaku kosong = sampai {{ $maxUntil->translatedFormat('d M Y') }}. Pilih tanggal lebih awal bila perlu dipersingkat.</div>
                        <div class="col-12"><button class="btn btn-primary btn-sm" onclick="return confirm('Beri keputusan laik operasi untuk unit ini?')"><i class="bi bi-patch-check me-1"></i>Putuskan</button></div>
                    </form>
                </div>
            @endif

            @if ($can['sendBack'])
                <div class="col-12"><hr class="my-0"></div>
                <div class="col-md-8">
                    <form method="POST" action="{{ route('cms.sendback', $c) }}">
                        @csrf
                        <label class="form-label fw-semibold">Kembalikan untuk diperbaiki</label>
                        <div class="input-group input-group-sm"><input type="text" name="reason" class="form-control" maxlength="500" placeholder="Alasan pengembalian" required><button class="btn btn-outline-warning">Kembalikan</button></div>
                    </form>
                </div>
            @endif

            @if ($can['cancel'])
                <div class="col-md-4">
                    <form method="POST" action="{{ route('cms.cancel', $c) }}" onsubmit="return confirm('Batalkan proses ini?')">
                        @csrf
                        <label class="form-label fw-semibold">Batalkan proses</label>
                        <div class="input-group input-group-sm"><input type="text" name="reason" class="form-control" maxlength="255" placeholder="Alasan"><button class="btn btn-outline-danger">Batalkan</button></div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('answerForm');
    if (!form) return;
    const rows = form.querySelectorAll('[data-item]');
    function refresh() {
        let answered = 0, bad = 0;
        rows.forEach(r => {
            const v = r.querySelector('input[type=radio]:checked');
            if (v) { answered++; }
            if (v && v.value === 'bad') { bad++; r.classList.add('is-bad'); } else { r.classList.remove('is-bad'); }
        });
        document.getElementById('progressText').textContent = answered + ' dari ' + rows.length + ' terisi';
        document.getElementById('badCount').textContent = bad;
    }
    form.addEventListener('change', refresh);
})();
</script>
@endpush
