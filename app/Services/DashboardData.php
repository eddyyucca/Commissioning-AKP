<?php

namespace App\Services;

use Akp\PortalSso\PortalUser;
use App\Models\Alert;
use App\Models\Commissioning;
use App\Models\CommissioningItem;
use App\Models\Finding;
use App\Models\Incident;
use App\Models\Unit;
use App\Models\UnitDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Menyusun semua angka dan data grafik dashboard. Filter kategori/area berlaku untuk semua bagian. */
class DashboardData
{
    public function build(?string $category, ?string $area, int $days, ?PortalUser $user = null): array
    {
        $today = today();
        $from = $today->copy()->subDays($days);

        $units = Unit::query()->active()->withProcess()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($area, fn ($q) => $area === '__none' ? $q->whereNull('area') : $q->where('area', $area))
            ->get();
        $unitIds = $units->pluck('id');

        foreach ($units as $u) {
            $u->setAttribute('ops', $u->operationalStatus($today));
        }

        // --- Status armada ---
        $statusCounts = collect(array_keys(Unit::STATUS_LABELS))->mapWithKeys(fn ($s) => [$s => $units->where('ops', $s)->count()]);
        $total = $units->count();
        $fit = $statusCounts['commissioned'] + $statusCounts['expiring'];
        $kpi = [
            'total' => $total,
            'fit' => $fit,
            'expiring' => $statusCounts['expiring'],
            'expired' => $statusCounts['expired'],
            'hold' => $statusCounts['hold'],
            'not_commissioned' => $statusCounts['not_commissioned'],
            'in_process' => $statusCounts['in_process'],
            'compliance' => $total ? (int) round($fit / $total * 100) : null,
        ];

        // --- Per kategori (bertumpuk menurut status) ---
        $byCategory = [];
        foreach (config('commissioning.categories') as $code => $name) {
            $set = $units->where('category', $code);
            if ($set->isEmpty()) {
                continue;
            }
            $row = ['label' => $code, 'name' => $name];
            foreach (array_keys(Unit::STATUS_LABELS) as $s) {
                $row[$s] = $set->where('ops', $s)->count();
            }
            $byCategory[] = $row;
        }

        // --- Kepatuhan per area ---
        $byArea = $units->groupBy(fn ($u) => $u->area ?: 'Tanpa area')->map(function (Collection $g, $name) {
            $fitN = $g->filter(fn ($u) => in_array($u->ops, ['commissioned', 'expiring'], true))->count();

            return ['label' => $name, 'total' => $g->count(), 'fit' => $fitN, 'percent' => (int) round($fitN / $g->count() * 100)];
        })->sortByDesc('total')->take(10)->values()->all();

        // --- Bulanan (12 bulan) ---
        $months = collect(range(11, 0))->map(fn ($i) => $today->copy()->startOfMonth()->subMonths($i));
        $labels = $months->map(fn (Carbon $m) => $m->translatedFormat('M y'))->all();
        $approved = Commissioning::query()->where('status', 'approved')->whereIn('unit_id', $unitIds)
            ->where('decided_at', '>=', $months->first())->get(['id', 'kind', 'decided_at', 'submitted_at']);
        $monthly = ['labels' => $labels, 'commissioning' => [], 'recommissioning' => [], 'lead' => []];
        foreach ($months as $m) {
            $set = $approved->filter(fn ($c) => $c->decided_at->isSameMonth($m));
            $monthly['commissioning'][] = $set->where('kind', 'commissioning')->count();
            $monthly['recommissioning'][] = $set->where('kind', 'recommissioning')->count();
            $lead = $set->filter(fn ($c) => $c->submitted_at)->map(fn ($c) => $c->submitted_at->diffInHours($c->decided_at) / 24);
            $monthly['lead'][] = $lead->isEmpty() ? null : round($lead->avg(), 1);
        }

        // --- Alasan recommissioning (periode) ---
        $reasons = Commissioning::query()->whereIn('unit_id', $unitIds)->where('kind', 'recommissioning')->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $from)->get(['reason'])->groupBy('reason')
            ->map(fn ($g, $r) => ['label' => config('commissioning.reasons.'.$r, $r ?: '-'), 'n' => $g->count()])->sortByDesc('n')->values()->all();

        // --- Antrian proses ---
        $active = Commissioning::query()->whereIn('unit_id', $unitIds)->whereIn('status', Commissioning::ACTIVE_STATUSES)->get();
        $pipeline = [
            ['label' => 'Draf', 'n' => $active->where('status', 'draft')->count()],
            ['label' => 'Perlu perbaikan', 'n' => $active->where('status', 'rejected')->count()],
            ['label' => 'Menunggu verifikasi', 'n' => $active->where('status', 'submitted')->filter(fn ($c) => ! $c->bothVerified())->count()],
            ['label' => 'Menunggu keputusan', 'n' => $active->where('status', 'submitted')->filter(fn ($c) => $c->bothVerified())->count()],
        ];

        // --- Kalender masa berlaku (6 bulan ke depan) ---
        $calLabels = ['Lewat'];
        $calData = [$units->filter(fn ($u) => $u->commissioned_until && $u->commissioned_until->lt($today))->count()];
        for ($i = 0; $i < 6; $i++) {
            $start = $today->copy()->startOfMonth()->addMonths($i);
            $calLabels[] = $start->translatedFormat('M y');
            $calData[] = $units->filter(fn ($u) => $u->commissioned_until && ! $u->commissioned_until->lt($today)
                && $u->commissioned_until->isSameMonth($start))->count();
        }
        $expiry = ['labels' => $calLabels, 'data' => $calData];

        // --- Unit dengan masa berlaku terdekat ---
        $nearest = $units->filter(fn ($u) => $u->commissioned_until)->sortBy('commissioned_until')->take(12)->values()
            ->map(fn ($u) => ['code' => $u->code, 'left' => $u->daysLeft($today), 'status' => $u->ops])->all();

        // --- Item paling sering tidak sesuai ---
        $badItems = CommissioningItem::query()->where('result', 'bad')
            ->whereHas('commissioning', fn ($q) => $q->whereIn('unit_id', $unitIds)->where('status', '!=', 'cancelled')->where('created_at', '>=', $from))
            ->get(['label', 'group_name', 'is_critical']);
        $topItems = $badItems->groupBy('label')->map(fn ($g, $l) => ['label' => $l, 'n' => $g->count(), 'critical' => (bool) $g->first()->is_critical])
            ->sortByDesc('n')->take(10)->values()->all();
        $byGroup = $badItems->groupBy('group_name')->map(fn ($g, $l) => ['label' => $l, 'n' => $g->count()])->sortByDesc('n')->values()->all();

        // --- Temuan ---
        $findings = Finding::query()->whereIn('unit_id', $unitIds)->where('created_at', '>=', $from)->get();
        $findingChart = [];
        foreach (Finding::SEVERITY_LABELS as $sev => $name) {
            $set = $findings->where('severity', $sev);
            $findingChart[] = ['label' => $name, 'open' => $set->where('status', 'open')->count(), 'in_progress' => $set->where('status', 'in_progress')->count(), 'closed' => $set->where('status', 'closed')->count()];
        }
        $allOpen = Finding::query()->whereIn('unit_id', $unitIds)->where('status', '!=', 'closed')->get();
        $closedWithTime = $findings->where('status', 'closed')->filter(fn ($f) => $f->closed_at);
        $findingStats = [
            'open' => $allOpen->count(),
            'overdue' => $allOpen->filter(fn ($f) => $f->isOverdue())->count(),
            'critical' => $allOpen->where('severity', 'critical')->count(),
            'avg_close_days' => $closedWithTime->isEmpty() ? null : round($closedWithTime->avg(fn ($f) => $f->created_at->diffInHours($f->closed_at) / 24), 1),
        ];

        // --- Dokumen ---
        $docs = UnitDocument::query()->whereIn('unit_id', $unitIds)->get();
        $docBuckets = ['Kedaluwarsa' => 0, '0-7 hari' => 0, '8-30 hari' => 0, '31-60 hari' => 0, '> 60 hari' => 0];
        foreach ($docs->filter(fn ($d) => $d->expires_at) as $d) {
            $left = $d->daysLeft();
            $key = $left < 0 ? 'Kedaluwarsa' : ($left <= 7 ? '0-7 hari' : ($left <= 30 ? '8-30 hari' : ($left <= 60 ? '31-60 hari' : '> 60 hari')));
            $docBuckets[$key]++;
        }

        // --- Alert aktif ---
        $alerts = Alert::query()->active()->where(fn ($q) => $q->whereIn('unit_id', $unitIds)->orWhereNull('unit_id'))->get();
        $alertByType = $alerts->groupBy('type')->map(fn ($g, $t) => ['label' => Alert::TYPE_LABELS[$t] ?? $t, 'n' => $g->count(),
            'kritis' => $g->where('severity', 'kritis')->count(), 'tinggi' => $g->where('severity', 'tinggi')->count(),
            'sedang' => $g->where('severity', 'sedang')->count(), 'info' => $g->where('severity', 'info')->count()])->sortByDesc('n')->values()->all();
        $alertBySeverity = collect(Alert::SEVERITY_LABELS)->map(fn ($name, $sev) => ['label' => $name, 'n' => $alerts->where('severity', $sev)->count(), 'color' => Alert::SEVERITY_COLORS[$sev]])->values()->all();

        // --- Insiden ---
        $incidents = Incident::query()->whereIn('unit_id', $unitIds)->where('occurred_at', '>=', $months->first())->get(['kind', 'occurred_at', 'severity']);
        $incidentChart = ['labels' => $labels, 'data' => $months->map(fn ($m) => $incidents->filter(fn ($i) => $i->occurred_at->isSameMonth($m))->count())->all()];
        $incidentKinds = $incidents->where('occurred_at', '>=', $from)->groupBy('kind')
            ->map(fn ($g, $k) => ['label' => config('commissioning.incident_kinds.'.$k.'.0', $k), 'n' => $g->count()])->sortByDesc('n')->values()->all();

        return [
            'kpi' => $kpi,
            'units' => $units,
            'chart' => [
                'status' => collect(Unit::STATUS_LABELS)->map(fn ($name, $s) => ['key' => $s, 'label' => $name, 'n' => $statusCounts[$s], 'color' => Unit::STATUS_COLORS[$s]])->values()->all(),
                'byCategory' => $byCategory,
                'byArea' => $byArea,
                'monthly' => $monthly,
                'reasons' => $reasons,
                'pipeline' => $pipeline,
                'expiry' => $expiry,
                'nearest' => $nearest,
                'topItems' => $topItems,
                'byGroup' => $byGroup,
                'findings' => $findingChart,
                'docs' => collect($docBuckets)->map(fn ($n, $l) => ['label' => $l, 'n' => $n])->values()->all(),
                'alertByType' => $alertByType,
                'alertBySeverity' => $alertBySeverity,
                'incidents' => $incidentChart,
                'incidentKinds' => $incidentKinds,
            ],
            'findingStats' => $findingStats,
            'docsTotal' => $docs->count(),
            'alerts' => [
                'top' => $alerts->sortBy(fn ($a) => [Alert::SEVERITY_ORDER[$a->severity] ?? 9, $a->due_date?->timestamp ?? PHP_INT_MAX])->take(8)->values(),
                'counts' => $alerts->groupBy('severity')->map->count(),
                'unacknowledged' => $alerts->whereNull('acknowledged_at')->count(),
            ],
            'queue' => $this->queueFor($user, $active),
            'recent' => Commissioning::query()->where('status', 'approved')->whereIn('unit_id', $unitIds)->orderByDesc('decided_at')->limit(6)->get(),
            'period' => ['days' => $days, 'from' => $from],
        ];
    }

    /** Proses yang menunggu tindakan pengguna ini, menurut levelnya. */
    private function queueFor(?PortalUser $user, Collection $active): Collection
    {
        if (! $user) {
            return collect();
        }
        $isAdmin = $user->is_admin || $user->role === 'admin';

        return $active->filter(function ($c) use ($user, $isAdmin) {
            if ($isAdmin) {
                return true;
            }

            return match ($user->role) {
                'inspector' => in_array($c->status, ['draft', 'rejected'], true) && (string) $c->requested_by_id === (string) $user->id,
                'safety' => $c->status === 'submitted' && ! $c->safety_at,
                'workshop' => $c->status === 'submitted' && ! $c->workshop_at,
                'approver' => $c->status === 'submitted' && $c->bothVerified(),
                default => false,
            };
        })->sortBy('created_at')->values();
    }
}
