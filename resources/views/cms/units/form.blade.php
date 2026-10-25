@extends('layouts.app')

@php($editing = $unit->exists)
@section('title', $editing ? 'Ubah Unit' : 'Tambah Unit')
@section('page-title', $editing ? 'Ubah Unit '.$unit->code : 'Tambah Unit')
@section('back-url', $editing ? route('cms.units.show', $unit) : route('cms.units'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.units') }}">Unit</a></li>
    <li class="breadcrumb-item active">{{ $editing ? 'Ubah' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-12 col-xl-9">
<div class="card shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ $editing ? route('cms.units.update', $unit) : route('cms.units.store') }}" class="row g-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="col-md-4">
            <label class="form-label">Kode unit <span class="text-danger">*</span></label>
            <input type="text" name="code" value="{{ old('code', $unit->code) }}" class="form-control @error('code') is-invalid @enderror" maxlength="30" {{ $editing ? 'readonly' : 'required' }}>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($editing)<div class="form-text">Kode tidak bisa diubah karena tersimpan di riwayat.</div>@endif
        </div>
        <div class="col-md-8">
            <label class="form-label">Nama / jenis unit <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $unit->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="120" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Kategori <span class="text-danger">*</span></label>
            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                @foreach (config('commissioning.categories') as $code => $name)<option value="{{ $code }}" @selected(old('category', $unit->category) === $code)>{{ $code }} &middot; {{ $name }}</option>@endforeach
            </select>
            <div class="form-text">Menentukan item checklist yang dipakai.</div>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Area / lokasi</label>
            <input type="text" name="area" value="{{ old('area', $unit->area) }}" class="form-control" maxlength="80" list="areaList">
            <datalist id="areaList">@foreach ($areas as $a)<option value="{{ $a }}">@endforeach</datalist>
        </div>

        <div class="col-md-4"><label class="form-label">Merek</label><input type="text" name="brand" value="{{ old('brand', $unit->brand) }}" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Model / tipe</label><input type="text" name="model" value="{{ old('model', $unit->model) }}" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Tahun pembuatan</label>
            <input type="number" name="year" value="{{ old('year', $unit->year) }}" class="form-control @error('year') is-invalid @enderror" min="1950" max="{{ date('Y') + 1 }}">
            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

        <div class="col-md-4"><label class="form-label">Nomor serial / rangka</label><input type="text" name="serial_number" value="{{ old('serial_number', $unit->serial_number) }}" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Plat nomor</label><input type="text" name="plate" value="{{ old('plate', $unit->plate) }}" class="form-control" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label">Pemilik</label><input type="text" name="owner" value="{{ old('owner', $unit->owner) }}" class="form-control" maxlength="80"><div class="form-text">AKP atau nama kontraktor.</div></div>

        <div class="col-12"><label class="form-label">Catatan</label><input type="text" name="notes" value="{{ old('notes', $unit->notes) }}" class="form-control" maxlength="255"></div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan</button>
            <a href="{{ $editing ? route('cms.units.show', $unit) : route('cms.units') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>
@if ($editing)
    <form method="POST" action="{{ route('cms.units.toggle', $unit) }}" class="mt-3" onsubmit="return confirm('{{ $unit->is_active ? 'Nonaktifkan unit ini? Unit nonaktif tidak muncul di dashboard dan alert.' : 'Aktifkan kembali unit ini?' }}')">
        @csrf
        <button class="btn btn-sm btn-outline-{{ $unit->is_active ? 'danger' : 'success' }}">{{ $unit->is_active ? 'Nonaktifkan unit' : 'Aktifkan unit' }}</button>
    </form>
@endif
</div></div>
@endsection
