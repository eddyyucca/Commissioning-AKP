<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDocument;
use App\Services\AlertService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

/** Master unit / alat dan kondisi operasionalnya. */
class UnitController extends Controller
{
    private const MESSAGES = [
        'code.required' => 'Kode unit wajib diisi.',
        'code.regex' => 'Kode unit hanya boleh huruf, angka, strip, titik, dan garis bawah (tanpa spasi).',
        'code.unique' => 'Kode unit ini sudah dipakai.',
        'name.required' => 'Nama atau jenis unit wajib diisi.',
        'category.in' => 'Kategori tidak dikenal.',
        'year.min' => 'Tahun pembuatan tidak wajar.',
        'year.max' => 'Tahun pembuatan tidak wajar.',
    ];

    private function rules(?Unit $unit): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.]*$/', Rule::unique('units', 'code')->ignore($unit?->id)],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(array_keys(config('commissioning.categories')))],
            'brand' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'serial_number' => ['nullable', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.((int) date('Y') + 1)],
            'plate' => ['nullable', 'string', 'max:30'],
            'owner' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $category = (string) $request->query('kategori');
        $area = (string) $request->query('area');
        $status = (string) $request->query('status');

        $units = Unit::query()->withProcess()->orderByDesc('is_active')->orderBy('code')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $query->where(fn ($w) => $w->where('code', 'like', $like)->orWhere('name', 'like', $like)->orWhere('plate', 'like', $like)->orWhere('serial_number', 'like', $like));
            })
            ->when(array_key_exists($category, config('commissioning.categories')), fn ($query) => $query->where('category', $category))
            ->when($area !== '', fn ($query) => $query->where('area', $area))
            ->get();

        $units->each(fn ($u) => $u->setAttribute('ops', $u->operationalStatus()));
        if (array_key_exists($status, Unit::STATUS_LABELS)) {
            $units = $units->where('ops', $status)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator($units->forPage($page, 25)->values(), $units->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('cms.units.index', [
            'units' => $paginated, 'q' => $q, 'category' => $category, 'area' => $area, 'status' => $status,
            'areas' => Unit::query()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'canEdit' => portal_user()->hasRole('admin'),
        ]);
    }

    public function create()
    {
        return view('cms.units.form', ['unit' => new Unit(['category' => 'LV', 'owner' => 'AKP', 'is_active' => true]), 'areas' => $this->areas()]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Unit::normalizeCode((string) $request->input('code', ''))]);
        $data = $request->validate($this->rules(null), self::MESSAGES);
        $data['owner'] = ($data['owner'] ?? null) ?: 'AKP';
        $unit = Unit::create($data + ['is_active' => true]);

        return redirect()->route('cms.units.show', $unit)->with('success', "Unit {$unit->code} ditambahkan. Lanjutkan dengan commissioning.");
    }

    public function show(Request $request, Unit $unit, AlertService $alerts)
    {
        $alerts->scanIfStale();
        $unit->loadMissing([]);
        $unit->setAttribute('has_process', $unit->commissionings()->whereIn('status', ['draft', 'submitted', 'rejected'])->exists());

        $user = portal_user();

        return view('cms.units.show', [
            'unit' => $unit,
            'ops' => $unit->operationalStatus(),
            'history' => $unit->commissionings()->orderByDesc('id')->get(),
            'documents' => $unit->documents()->orderByRaw('expires_at is null')->orderBy('expires_at')->get(),
            'incidents' => $unit->incidents()->orderByDesc('occurred_at')->limit(20)->get(),
            'findings' => $unit->findings()->where('status', '!=', 'closed')->orderBy('due_date')->get(),
            'alerts' => $unit->alerts()->active()->get()->sortBy(fn ($a) => \App\Models\Alert::SEVERITY_ORDER[$a->severity] ?? 9)->values(),
            'activeProcess' => $unit->commissionings()->whereIn('status', ['draft', 'submitted', 'rejected'])->first(),
            'canStart' => (bool) $user->hasRole('admin', 'inspector', 'safety', 'workshop'),
            'canDocs' => (bool) $user->hasRole('admin', 'safety', 'workshop'),
            'canIncident' => (bool) $user->hasRole('admin', 'safety', 'workshop', 'inspector'),
            'canEdit' => (bool) $user->hasRole('admin'),
        ]);
    }

    public function edit(Unit $unit)
    {
        return view('cms.units.form', ['unit' => $unit, 'areas' => $this->areas()]);
    }

    /** Kode unit terkunci setelah dibuat: kode itu tersimpan di riwayat commissioning. */
    public function update(Request $request, Unit $unit)
    {
        $rules = $this->rules($unit);
        unset($rules['code']);
        $data = $request->validate($rules, self::MESSAGES);
        $data['owner'] = ($data['owner'] ?? null) ?: 'AKP';
        $unit->update($data);

        return redirect()->route('cms.units.show', $unit)->with('success', "Unit {$unit->code} diperbarui.");
    }

    public function toggle(Unit $unit)
    {
        $unit->update(['is_active' => ! $unit->is_active]);

        return redirect()->route('cms.units')->with('success', "Unit {$unit->code} ".($unit->is_active ? 'diaktifkan.' : 'dinonaktifkan. Riwayatnya tetap tersimpan.'));
    }

    /** Menandai unit idle (tidak dipakai) sejak tanggal tertentu; lama idle memicu alert recommissioning. */
    public function markIdle(Request $request, Unit $unit)
    {
        $data = $request->validate(['idle_since' => ['required', 'date', 'before_or_equal:today']], ['idle_since.required' => 'Tanggal mulai idle wajib diisi.', 'idle_since.before_or_equal' => 'Tanggal idle tidak boleh di masa depan.']);
        $unit->update(['idle_since' => $data['idle_since']]);

        return redirect()->route('cms.units.show', $unit)->with('success', "Unit {$unit->code} ditandai idle sejak ".\Illuminate\Support\Carbon::parse($data['idle_since'])->translatedFormat('d M Y').'.');
    }

    public function clearIdle(Unit $unit)
    {
        $unit->update(['idle_since' => null]);

        return redirect()->route('cms.units.show', $unit)->with('success', "Unit {$unit->code} tidak lagi ditandai idle.");
    }

    public function storeDocument(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('commissioning.document_types')))],
            'number' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], ['type.required' => 'Jenis dokumen wajib dipilih.', 'expires_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal terbit.']);

        $unit->documents()->create($data + ['created_by_name' => portal_user()->name]);

        return redirect()->to(route('cms.units.show', $unit).'#dokumen')->with('success', 'Dokumen ditambahkan.');
    }

    public function destroyDocument(Unit $unit, UnitDocument $document)
    {
        abort_unless($document->unit_id === $unit->id, 404);
        $document->delete();

        return redirect()->to(route('cms.units.show', $unit).'#dokumen')->with('success', 'Dokumen dihapus.');
    }

    public function importForm()
    {
        return view('cms.units.import');
    }

    public function importTemplate()
    {
        return Csv::download('contoh-impor-unit.csv', ['kode', 'nama', 'kategori', 'area', 'merek', 'model', 'serial', 'tahun', 'plat', 'pemilik'], [
            ['HD-101', 'Dump truck 40 ton', 'HD', 'Pit Utara', 'Komatsu', 'HD785-7', 'SN12345', '2022', '', 'AKP'],
            ['LV-201', 'Double cabin 4x4', 'LV', 'Kantor Site', 'Toyota', 'Hilux', 'MR0FB8CD', '2023', 'DT 8201 KC', 'AKP'],
        ]);
    }

    /** Impor unit dari CSV. Kode yang sudah ada dilewati; baris bermasalah dilaporkan. */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']], [
            'file.required' => 'Pilih berkas CSV.', 'file.mimes' => 'Berkas harus berformat CSV.', 'file.max' => 'Berkas maksimum 1 MB.',
        ]);

        $path = $request->file('file')->getRealPath();
        $h = fopen($path, 'r');
        $first = (string) fgets($h);
        fclose($h);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $categories = array_keys(config('commissioning.categories'));

        $created = 0;
        $skipped = [];
        $errors = [];
        $handle = fopen($path, 'r');
        $line = 0;
        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            $cols = array_map(fn ($c) => trim(str_replace("\xEF\xBB\xBF", '', (string) $c)), $cols);
            if ($line === 1 && strtolower($cols[0] ?? '') === 'kode') {
                continue;
            }
            if (count(array_filter($cols, fn ($c) => $c !== '')) === 0) {
                continue;
            }
            if ($created + count($skipped) + count($errors) >= 500) {
                $errors[] = 'Baris '.$line.' dan seterusnya tidak diproses (maksimum 500 baris per impor).';
                break;
            }

            [$code, $name, $category, $area, $brand, $model, $serial, $year, $plate, $owner] = array_pad($cols, 10, '');
            $code = Unit::normalizeCode($code);
            $category = $category === '' ? 'LV' : strtoupper($category);

            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_.]{0,29}$/', $code)) {
                $errors[] = "Baris $line: kode unit tidak sah.";
            } elseif ($name === '' || mb_strlen($name) > 120) {
                $errors[] = "Baris $line ($code): nama kosong atau terlalu panjang.";
            } elseif (! in_array($category, $categories, true)) {
                $errors[] = "Baris $line ($code): kategori $category tidak dikenal (".implode(', ', $categories).').';
            } elseif ($year !== '' && (! ctype_digit($year) || (int) $year < 1950 || (int) $year > (int) date('Y') + 1)) {
                $errors[] = "Baris $line ($code): tahun tidak wajar.";
            } elseif (Unit::where('code', $code)->exists()) {
                $skipped[] = $code;
            } else {
                Unit::create([
                    'code' => $code, 'name' => $name, 'category' => $category, 'area' => $area !== '' ? mb_substr($area, 0, 80) : null,
                    'brand' => $brand !== '' ? mb_substr($brand, 0, 60) : null, 'model' => $model !== '' ? mb_substr($model, 0, 60) : null,
                    'serial_number' => $serial !== '' ? mb_substr($serial, 0, 60) : null, 'year' => $year !== '' ? (int) $year : null,
                    'plate' => $plate !== '' ? mb_substr($plate, 0, 30) : null, 'owner' => $owner !== '' ? mb_substr($owner, 0, 80) : 'AKP', 'is_active' => true,
                ]);
                $created++;
            }
        }
        fclose($handle);

        return redirect()->route('cms.units.import')->with('import_result', ['created' => $created, 'skipped' => $skipped, 'errors' => $errors]);
    }

    private function areas()
    {
        return Unit::query()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area');
    }
}
