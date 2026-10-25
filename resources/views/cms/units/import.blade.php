@extends('layouts.app')

@section('title', 'Impor Unit')
@section('page-title', 'Impor Unit')
@section('back-url', route('cms.units'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.units') }}">Unit</a></li>
    <li class="breadcrumb-item active">Impor</li>
@endsection

@section('content')
@php($result = session('import_result'))
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        @if ($result)
            <div class="alert alert-{{ $result['errors'] ? 'warning' : 'success' }}">
                <div class="fw-semibold">{{ $result['created'] }} unit ditambahkan.</div>
                @if ($result['skipped'])<div class="small">Dilewati karena kode sudah ada: {{ implode(', ', $result['skipped']) }}.</div>@endif
                @if ($result['errors'])<ul class="small mb-0 mt-2 ps-3">@foreach ($result['errors'] as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
            </div>
        @endif

        <div class="card shadow-sm"><div class="card-body p-4">
            <p class="small text-muted mb-3">
                Berkas CSV dengan kolom <strong>kode, nama, kategori, area, merek, model, serial, tahun, plat, pemilik</strong>
                (pemisah koma atau titik koma; baris pertama boleh judul). Yang wajib hanya kode dan nama; kategori kosong dianggap LV.
                Kategori: <strong>{{ implode(', ', array_keys(config('commissioning.categories'))) }}</strong>. Maksimum 500 baris.
                <a href="{{ route('cms.units.import.template') }}">Unduh contoh</a>.
            </p>
            <form method="POST" action="{{ route('cms.units.import.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv,text/plain" class="form-control mb-3 @error('file') is-invalid @enderror" required>
                @error('file')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Impor</button>
                    <a href="{{ route('cms.units') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div></div>
    </div>
</div>
@endsection
