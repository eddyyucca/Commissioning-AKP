<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts)
    {
        $alerts->scanIfStale();

        $state = (string) $request->query('status', 'aktif');       // aktif | baru | selesai | semua
        $severity = (string) $request->query('tingkat');
        $type = (string) $request->query('jenis');
        $q = trim((string) $request->query('q'));

        $items = Alert::query()->with('unit:id,code,name')
            ->when($state === 'aktif', fn ($query) => $query->active())
            ->when($state === 'baru', fn ($query) => $query->active()->whereNull('acknowledged_at'))
            ->when($state === 'selesai', fn ($query) => $query->whereNotNull('resolved_at'))
            ->when(array_key_exists($severity, Alert::SEVERITY_LABELS), fn ($query) => $query->where('severity', $severity))
            ->when(array_key_exists($type, Alert::TYPE_LABELS), fn ($query) => $query->where('type', $type))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('message', 'like', $like));
            })
            ->orderByRaw("case severity when 'kritis' then 0 when 'tinggi' then 1 when 'sedang' then 2 else 3 end")
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('cms.alerts', [
            'items' => $items, 'state' => $state, 'severity' => $severity, 'type' => $type, 'q' => $q,
            'counts' => Alert::query()->active()->get(['severity', 'acknowledged_at'])->groupBy('severity')->map->count(),
            'canAck' => (bool) portal_user()->hasRole('admin', 'approver', 'safety', 'workshop', 'inspector'),
            'canScan' => (bool) portal_user()->hasRole('admin', 'safety'),
        ]);
    }

    public function acknowledge(Alert $alert)
    {
        if ($alert->resolved_at === null && $alert->acknowledged_at === null) {
            $alert->update(['acknowledged_at' => now(), 'acknowledged_by_name' => portal_user()->name]);
        }

        return back()->with('success', 'Alert ditandai sudah dilihat.');
    }

    public function acknowledgeAll()
    {
        $n = Alert::query()->active()->whereNull('acknowledged_at')->update(['acknowledged_at' => now(), 'acknowledged_by_name' => portal_user()->name]);

        return back()->with('success', "{$n} alert ditandai sudah dilihat.");
    }

    public function scan(AlertService $alerts)
    {
        $r = $alerts->scan();

        return back()->with('success', "Pemindaian selesai: {$r['active']} alert aktif, {$r['created']} baru, {$r['resolved']} selesai.");
    }
}
