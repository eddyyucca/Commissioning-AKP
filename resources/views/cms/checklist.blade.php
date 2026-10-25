@extends('layouts.app')

@section('title', 'Checklist')
@section('page-title', 'Template Checklist')
@section('breadcrumb')
    <li class="breadcrumb-item active">Checklist</li>
@endsection

@section('content')
<div class="alert alert-info small">
    Template ini menjadi <strong>salinan</strong> saat proses commissioning dibuat. Mengubahnya hanya memengaruhi proses <strong>baru</strong>; proses lama tetap memakai checklist saat dibuat.
    Isi bawaan adalah usulan berdasarkan praktik umum pertambangan: <strong>sesuaikan dengan prosedur AKP dan manual pabrikan</strong> sebelum dipakai sebagai dasar keputusan laik operasi.
    {{ $active }} dari {{ $total }} item aktif.
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="{{ route('cms.checklist') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Berlaku untuk</label>
            <select name="scope" class="form-select form-select-sm"><option value="">Semua</option>
                <option value="both" @selected($scope === 'both')>Keduanya</option><option value="commissioning" @selected($scope === 'commissioning')>Commissioning</option><option value="recommissioning" @selected($scope === 'recommissioning')>Recommissioning</option></select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Kategori unit</label>
            <select name="kategori" class="form-select form-select-sm"><option value="">Semua</option>@foreach (config('commissioning.categories') as $code => $name)<option value="{{ $code }}" @selected($category === $code)>{{ $code }} &middot; {{ $name }}</option>@endforeach</select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="{{ route('cms.checklist') }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

@if ($canEdit)
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white"><button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newItem"><i class="bi bi-plus-lg me-1"></i>Tambah item</button></div>
    <div class="collapse" id="newItem"><div class="card-body">
        @include('cms.partials.checklist-form', ['action' => route('cms.checklist.store'), 'item' => null, 'prefix' => 'new'])
    </div></div>
</div>
@endif

@forelse ($groups as $groupName => $list)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><h2 class="h6 mb-0">{{ $groupName }} <span class="text-muted fw-normal">({{ $list->count() }})</span></h2></div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead><tr><th>Item</th><th>Berlaku</th><th>Kategori</th><th>Alasan</th><th></th></tr></thead>
                <tbody>
                    @foreach ($list as $i)
                        <tr class="{{ $i->is_active ? '' : 'text-muted' }}">
                            <td>{{ $i->label }}@if ($i->is_critical) <span class="badge bg-danger-subtle text-danger">Kritis</span>@endif @unless ($i->is_active)<span class="badge bg-secondary-subtle text-secondary">nonaktif</span>@endunless
                                @if ($i->reference)<div class="small text-muted">Acuan: {{ $i->reference }}</div>@endif</td>
                            <td class="small">{{ ['both' => 'Keduanya', 'commissioning' => 'Commissioning', 'recommissioning' => 'Recommissioning'][$i->scope] }}</td>
                            <td class="small">{{ $i->categories === 'all' ? 'Semua' : $i->categories }}</td>
                            <td class="small">{{ $i->reasons ? collect($i->reasonList())->map(fn ($r) => config('commissioning.reasons.'.$r, $r))->implode(', ') : '-' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($canEdit)
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit{{ $i->id }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('cms.checklist.toggle', $i) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-{{ $i->is_active ? 'danger' : 'success' }}">{{ $i->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                @endif
                            </td>
                        </tr>
                        @if ($canEdit)
                            <tr class="collapse" id="edit{{ $i->id }}"><td colspan="5" class="bg-light">
                                @include('cms.partials.checklist-form', ['action' => route('cms.checklist.update', $i), 'item' => $i, 'prefix' => 'e'.$i->id])
                            </td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-5">Tidak ada item pada pilihan ini.</div>
@endforelse
@endsection
