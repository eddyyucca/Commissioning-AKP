@php
    $cats = $item ? $item->categoryList() : [];
    $reasons = $item ? $item->reasonList() : [];
@endphp
<form method="POST" action="{{ $action }}" class="row g-3">
    @csrf
    @if ($item) @method('PUT') @endif
    <div class="col-md-3"><label class="form-label small mb-1">Kelompok</label><input type="text" name="group_name" value="{{ $item?->group_name }}" class="form-control form-control-sm" maxlength="80" required list="groupList{{ $prefix }}"></div>
    <div class="col-md-6"><label class="form-label small mb-1">Isi item</label><input type="text" name="label" value="{{ $item?->label }}" class="form-control form-control-sm" maxlength="255" required></div>
    <div class="col-md-3"><label class="form-label small mb-1">Berlaku untuk</label>
        <select name="scope" class="form-select form-select-sm">
            @foreach (['both' => 'Commissioning dan recommissioning', 'commissioning' => 'Commissioning saja', 'recommissioning' => 'Recommissioning saja'] as $k => $l)<option value="{{ $k }}" @selected(($item?->scope ?? 'both') === $k)>{{ $l }}</option>@endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label small mb-1">Kategori unit (kosong = semua)</label>
        <div class="d-flex flex-wrap gap-3">@foreach (config('commissioning.categories') as $code => $name)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="categories[]" value="{{ $code }}" id="{{ $prefix }}c{{ $code }}" @checked(in_array($code, $cats, true))><label class="form-check-label small" for="{{ $prefix }}c{{ $code }}" title="{{ $name }}">{{ $code }}</label></div>
        @endforeach</div></div>
    <div class="col-md-6"><label class="form-label small mb-1">Alasan recommissioning (kosong = semua alasan)</label>
        <div class="d-flex flex-wrap gap-3">@foreach (config('commissioning.reasons') as $key => $label)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="reasons[]" value="{{ $key }}" id="{{ $prefix }}r{{ $key }}" @checked(in_array($key, $reasons, true))><label class="form-check-label small" for="{{ $prefix }}r{{ $key }}">{{ $label }}</label></div>
        @endforeach</div></div>
    <div class="col-md-6"><label class="form-label small mb-1">Acuan (prosedur / standar, opsional)</label><input type="text" name="reference" value="{{ $item?->reference }}" class="form-control form-control-sm" maxlength="120"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_critical" value="1" id="{{ $prefix }}crit" @checked($item?->is_critical)><label class="form-check-label" for="{{ $prefix }}crit">Item kritis (gagal = tidak boleh laik)</label></div></div>
    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Simpan</button></div>
</form>
<datalist id="groupList{{ $prefix }}">@foreach (\App\Models\ChecklistTemplateItem::query()->distinct()->orderBy('group_name')->pluck('group_name') as $g)<option value="{{ $g }}">@endforeach</datalist>
