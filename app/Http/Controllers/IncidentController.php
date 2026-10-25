<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Unit;
use App\Services\CommissioningService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    public function __construct(private CommissioningService $service)
    {
    }

    public function index(Request $request)
    {
        $kind = (string) $request->query('jenis');
        $status = (string) $request->query('status');

        $items = Incident::query()->with('unit:id,code,name')->orderByDesc('occurred_at')
            ->when(array_key_exists($kind, config('commissioning.incident_kinds')), fn ($q) => $q->where('kind', $kind))
            ->when(in_array($status, ['open', 'resolved'], true), fn ($q) => $q->where('status', $status))
            ->paginate(20)->withQueryString();

        return view('cms.incidents', [
            'items' => $items, 'kind' => $kind, 'status' => $status,
            'units' => Unit::query()->active()->orderBy('code')->get(['code', 'name']),
            'canReport' => (bool) portal_user()->hasRole('admin', 'safety', 'workshop', 'inspector'),
            'preset' => (string) $request->query('unit'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit' => ['required', 'string', 'max:30'],
            'kind' => ['required', Rule::in(array_keys(config('commissioning.incident_kinds')))],
            'severity' => ['required', Rule::in(array_keys(config('commissioning.incident_severities')))],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'description' => ['required', 'string', 'max:1000'],
            'requires_recommissioning' => ['nullable', 'boolean'],
        ], [
            'unit.required' => 'Pilih unit.', 'kind.required' => 'Pilih jenis kejadian.', 'description.required' => 'Uraian kejadian wajib diisi.',
            'occurred_at.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan.', 'occurred_at.required' => 'Waktu kejadian wajib diisi.',
        ]);

        $unit = Unit::query()->where('code', Unit::normalizeCode($data['unit']))->first();
        if (! $unit) {
            return back()->withInput()->withErrors(['unit' => 'Unit tidak ditemukan.']);
        }

        try {
            $incident = $this->service->reportIncident($unit, portal_user(), [
                'kind' => $data['kind'], 'severity' => $data['severity'], 'occurred_at' => $data['occurred_at'], 'description' => $data['description'],
                'requires_recommissioning' => $request->boolean('requires_recommissioning') || config('commissioning.incident_kinds.'.$data['kind'].'.1'),
            ]);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cms.incidents')->with('success', $incident->requires_recommissioning
            ? "Kejadian dicatat. Unit {$unit->code} DITAHAN sampai recommissioning disetujui."
            : 'Kejadian dicatat.');
    }
}
