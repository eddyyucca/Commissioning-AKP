<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Services\CommissioningService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FindingController extends Controller
{
    public function __construct(private CommissioningService $service)
    {
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'aktif');
        $severity = (string) $request->query('tingkat');
        $q = trim((string) $request->query('q'));

        $items = Finding::query()->with(['unit:id,code,name', 'commissioning:id,number'])
            ->when($status === 'aktif', fn ($query) => $query->where('status', '!=', 'closed'))
            ->when(array_key_exists($status, Finding::STATUS_LABELS), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists($severity, Finding::SEVERITY_LABELS), fn ($query) => $query->where('severity', $severity))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('pic_name', 'like', $like)->orWhereHas('unit', fn ($u) => $u->where('code', 'like', $like)));
            })
            ->orderByRaw("case severity when 'critical' then 0 when 'major' then 1 else 2 end")
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $user = portal_user();

        return view('cms.findings', [
            'items' => $items, 'status' => $status, 'severity' => $severity, 'q' => $q,
            'canUpdate' => (bool) $user->hasRole('admin', 'safety', 'workshop'),
            'canClose' => (bool) $user->hasRole('admin', 'safety'),
            'canSeverity' => (bool) $user->hasRole('admin', 'safety'),
        ]);
    }

    public function update(Request $request, Finding $finding)
    {
        $user = portal_user();
        $data = $request->validate([
            'action_plan' => ['nullable', 'string', 'max:500'],
            'pic_name' => ['nullable', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'severity' => ['nullable', Rule::in(array_keys(Finding::SEVERITY_LABELS))],
            'status' => ['nullable', Rule::in(['open', 'in_progress'])],
        ]);
        if (! $user->hasRole('admin', 'safety')) {
            unset($data['severity']);   // hanya Safety / administrator yang menilai tingkat temuan
        }
        $data = array_filter($data, fn ($v) => $v !== null);

        try {
            $this->service->updateFinding($finding, $data + ['status' => $data['status'] ?? ($finding->status === 'open' && (filled($data['pic_name'] ?? null) || filled($data['action_plan'] ?? null)) ? 'in_progress' : $finding->status)]);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Temuan diperbarui.');
    }

    public function close(Request $request, Finding $finding)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Catatan penutupan (bukti perbaikan) wajib diisi.']);

        try {
            $this->service->closeFinding($finding, portal_user(), $data['note']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Temuan ditutup.');
    }
}
