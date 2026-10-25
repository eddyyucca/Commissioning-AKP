@extends('layouts.app')

@section('title', 'Mulai Commissioning')
@section('page-title', 'Mulai Commissioning')
@section('back-url', route('cms.index'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.index') }}">Daftar proses</a></li>
    <li class="breadcrumb-item active">Baru</li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-12 col-xl-8">
<div class="card shadow-sm"><div class="card-body p-4">
    <p class="small text-muted">
        Pilih unit. Sistem menentukan jenis proses: unit yang belum pernah dinyatakan laik memakai <strong>commissioning</strong>;
        unit yang sudah pernah laik memakai <strong>recommissioning</strong> dengan alasan tertentu, dan checklist menyesuaikan kategori serta alasannya.
    </p>
    <form method="POST" action="{{ route('cms.store') }}" class="row g-3" id="startForm">
        @csrf
        <div class="col-md-6">
            <label class="form-label">Unit <span class="text-danger">*</span></label>
            <input type="text" name="unit" value="{{ old('unit', $unit?->code) }}" class="form-control @error('unit') is-invalid @enderror" list="unitList" autocomplete="off" required placeholder="Ketik kode unit" id="unitInput">
            <datalist id="unitList">
                @foreach ($units as $u)<option value="{{ $u->code }}">{{ $u->name }}</option>@endforeach
            </datalist>
            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text" id="unitHint">
                @if ($unit)
                    {{ $unit->name }} &middot; {{ $unit->statusLabel() }}
                @endif
            </div>
        </div>
        <div class="col-md-6" id="reasonBox">
            <label class="form-label">Alasan recommissioning</label>
            <select name="reason" class="form-select @error('reason') is-invalid @enderror" id="reasonSelect">
                <option value="">Pilih alasan (untuk unit yang pernah commissioning)</option>
                @foreach (config('commissioning.reasons') as $key => $label)<option value="{{ $key }}" @selected(old('reason', $suggestedReason) === $key)>{{ $label }}</option>@endforeach
            </select>
            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Wajib untuk recommissioning. Diabaikan untuk commissioning pertama.</div>
        </div>
        <div class="col-12"><label class="form-label">Keterangan alasan</label><input type="text" name="reason_note" value="{{ old('reason_note') }}" class="form-control" maxlength="255" placeholder="Contoh: setelah penggantian transmisi di workshop"></div>

        <div class="col-md-4"><label class="form-label">Rencana tanggal</label><input type="date" name="planned_date" value="{{ old('planned_date', today()->toDateString()) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Area / lokasi</label><input type="text" name="area" value="{{ old('area', $unit?->area) }}" class="form-control" maxlength="80"></div>
        <div class="col-md-4"><label class="form-label">Pembacaan meter</label>
            <div class="input-group"><input type="number" name="meter_reading" value="{{ old('meter_reading') }}" class="form-control" min="0">
                <select name="meter_unit" class="form-select" style="max-width:90px"><option value="jam" @selected(old('meter_unit') === 'jam')>jam</option><option value="km" @selected(old('meter_unit') === 'km')>km</option></select></div>
            <div class="form-text">Hour meter atau odometer saat diperiksa.</div></div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle me-1"></i>Buat dan isi checklist</button>
            <a href="{{ route('cms.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>
</div></div>
@endsection

@push('scripts')
@php
    $unitMeta = $units->mapWithKeys(function ($u) {
        return [$u->code => ['name' => $u->name, 'again' => (bool) $u->last_commissioning_id, 'hold' => (bool) $u->hold_since, 'busy' => (bool) $u->has_process]];
    });
@endphp
<script>
(function () {
    const units = @json($unitMeta);
    const input = document.getElementById('unitInput');
    const hint = document.getElementById('unitHint');
    const box = document.getElementById('reasonBox');
    function sync() {
        const u = units[(input.value || '').trim().toUpperCase()];
        box.style.opacity = u && !u.again ? .5 : 1;
        if (!u) { hint.textContent = ''; return; }
        hint.textContent = u.name + ' · ' + (u.busy ? 'sudah ada proses berjalan' : (u.again ? 'recommissioning' : 'commissioning pertama')) + (u.hold ? ' · DITAHAN (pilih alasan insiden/perbaikan)' : '');
    }
    input.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
