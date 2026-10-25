@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Commissioning')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('topbar-actions')
    @if ($canStart)
        <a href="{{ route('cms.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i><span class="d-none d-sm-inline">Mulai commissioning</span>
        </a>
    @endif
    <a href="{{ route('cms.alerts') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-bell"></i><span class="d-none d-sm-inline">Alert</span></a>
@endpush

@push('styles')
<style>
    .chart-box { position: relative; height: 280px; }
    .chart-box.sm { height: 230px; }
    .chart-box.lg { height: 340px; }
    .rate-ring { --p: 0; width: 84px; height: 84px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; font-size: 1.15rem;
        background: conic-gradient(var(--c, #16a34a) calc(var(--p) * 1%), #e5eaf1 0); position: relative; }
    .rate-ring::after { content: ''; position: absolute; inset: 9px; background: #fff; border-radius: 50%; }
    .rate-ring span { position: relative; z-index: 1; }
    .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
    .sev-kritis { border-left: 4px solid #dc2626; } .sev-tinggi { border-left: 4px solid #f59e0b; }
    .sev-sedang { border-left: 4px solid #2563eb; } .sev-info { border-left: 4px solid #94a3b8; }
</style>
@endpush

@section('content')
@php
    $k = $kpi;
    $ringColor = $k['compliance'] === null ? '#94a3b8' : ($k['compliance'] >= 90 ? '#16a34a' : ($k['compliance'] >= 70 ? '#f59e0b' : '#dc2626'));
    $crit = $alerts['counts']['kritis'] ?? 0;
    $high = $alerts['counts']['tinggi'] ?? 0;
    $hasUnits = $k['total'] > 0;
    $tile = fn ($icon, $tone, $label, $value, $note = null, $href = null) => compact('icon', 'tone', 'label', 'value', 'note', 'href');
    $tiles = [
        $tile('bi-truck-front', 'primary', 'Unit aktif', $k['total'], null, route('cms.units')),
        $tile('bi-patch-check', 'success', 'Laik operasi', $k['fit'], $k['total'] ? round($k['fit'] / $k['total'] * 100).'% dari unit aktif' : null, route('cms.units', ['status' => 'commissioned'])),
        $tile('bi-hourglass-split', 'warning', 'Segera berakhir', $k['expiring'], '≤ '.config('commissioning.expiring_days').' hari', route('cms.units', ['status' => 'expiring'])),
        $tile('bi-calendar-x', 'danger', 'Kedaluwarsa', $k['expired'], 'wajib recommissioning', route('cms.units', ['status' => 'expired'])),
        $tile('bi-sign-stop', 'dark', 'Ditahan (hold)', $k['hold'], 'tidak boleh beroperasi', route('cms.units', ['status' => 'hold'])),
        $tile('bi-question-circle', 'secondary', 'Belum commissioning', $k['not_commissioned'], null, route('cms.units', ['status' => 'not_commissioned'])),
        $tile('bi-arrow-repeat', 'info', 'Dalam proses', $k['in_process'], null, route('cms.index', ['status' => 'aktif'])),
    ];
@endphp

@if ($crit + $high > 0)
    <a href="{{ route('cms.alerts', ['status' => 'baru']) }}" class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 text-decoration-none">
        <i class="bi bi-bell-fill"></i>
        <span><strong>{{ $crit }} kritis</strong> dan <strong>{{ $high }} tinggi</strong> alert aktif{{ $alerts['unacknowledged'] > 0 ? ', '.$alerts['unacknowledged'].' belum dilihat' : '' }}. Klik untuk melihat.</span>
    </a>
@endif

{{-- Angka utama --}}
<div class="row g-3 mb-4">
    @foreach ($tiles as $t)
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ $t['href'] }}" class="text-decoration-none text-reset"><div class="erp-card h-100"><div class="erp-card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-{{ $t['tone'] }}-subtle text-{{ $t['tone'] === 'dark' ? 'dark' : $t['tone'] }}"><i class="bi {{ $t['icon'] }}"></i></div>
                <div class="min-w-0"><div class="kpi-label">{{ $t['label'] }}</div><div class="kpi-value fs-5 fw-bold">{{ number_format($t['value']) }}</div>
                    @if ($t['note'])<div class="small text-muted text-truncate">{{ $t['note'] }}</div>@endif</div>
            </div></div></a>
        </div>
    @endforeach
</div>

{{-- Kepatuhan + filter --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-4">
        <div class="erp-card h-100"><div class="erp-card-body d-flex align-items-center gap-3">
            <div class="rate-ring" style="--p: {{ $k['compliance'] ?? 0 }}; --c: {{ $ringColor }}"><span>{{ $k['compliance'] === null ? '-' : $k['compliance'].'%' }}</span></div>
            <div>
                <div class="kpi-label">Kepatuhan commissioning armada</div>
                <div class="fw-bold">{{ $k['fit'] }} dari {{ $k['total'] }} unit aktif laik operasi</div>
                <div class="small text-muted">{{ $k['total'] - $k['fit'] }} unit belum laik atau perlu tindakan</div>
            </div>
        </div></div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="erp-card h-100"><div class="erp-card-body">
            <form method="GET" action="{{ route('cms.home') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <div class="kpi-label mb-1">Periode analisis</div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Periode">
                        @foreach ([90 => '3 bulan', 180 => '6 bulan', 365 => '12 bulan'] as $d => $label)
                            <a href="{{ route('cms.home', array_filter(['hari' => $d, 'kategori' => $category, 'area' => $area])) }}" class="btn {{ $days === $d ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="kpi-label mb-1">Kategori</div>
                    <select name="kategori" class="form-select form-select-sm" aria-label="Kategori">
                        <option value="">Semua kategori</option>
                        @foreach (config('commissioning.categories') as $code => $name)<option value="{{ $code }}" @selected($category === $code)>{{ $code }} &middot; {{ $name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <div class="kpi-label mb-1">Area</div>
                    <select name="area" class="form-select form-select-sm" aria-label="Area">
                        <option value="">Semua area</option>
                        @foreach ($areas as $a)<option value="{{ $a }}" @selected($area === $a)>{{ $a }}</option>@endforeach
                    </select>
                </div>
                <input type="hidden" name="hari" value="{{ $days }}">
                <button class="btn btn-sm btn-outline-primary">Terapkan</button>
                @if ($category || $area)<a href="{{ route('cms.home', ['hari' => $days]) }}" class="btn btn-sm btn-link">Atur ulang</a>@endif
            </form>
            <div class="small text-muted mt-2">Filter berlaku untuk semua angka dan grafik. Grafik tren memakai {{ $days === 365 ? '12' : ($days === 180 ? '6' : '3') }} bulan terakhir.</div>
        </div></div>
    </div>
</div>

@if (! $hasUnits)
    <div class="card shadow-sm mb-4"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-truck-front fs-1 d-block mb-2"></i>
        Belum ada unit aktif{{ $category || $area ? ' pada filter ini' : '' }}.
        @if (portal_user()->hasRole('admin'))<div class="mt-2"><a href="{{ route('cms.units.create') }}" class="btn btn-primary btn-sm">Tambah unit</a> <a href="{{ route('cms.units.import') }}" class="btn btn-outline-secondary btn-sm">Impor CSV</a></div>@endif
    </div></div>
@endif

{{-- Baris 1: status armada --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-pie-chart me-2"></i>Status armada</h2></div>
            <div class="card-body">
                @if ($hasUnits)<div class="chart-box"><canvas id="chartStatus" aria-label="Status armada" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada data.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Status per kategori unit</h2></div>
            <div class="card-body">
                @if ($hasUnits)<div class="chart-box"><canvas id="chartCategory" aria-label="Status per kategori unit" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada data.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-geo-alt me-2"></i>Kepatuhan per area</h2></div>
            <div class="card-body">
                @if (! empty($chart['byArea']))<div class="chart-box"><canvas id="chartArea" aria-label="Kepatuhan per area" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada data area.</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Baris 2: aktivitas --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0"><i class="bi bi-graph-up me-2"></i>Commissioning disetujui per bulan</h2>
                <span class="small text-muted">Batang: jumlah &middot; Garis: rata-rata hari dari pengajuan sampai keputusan</span>
            </div>
            <div class="card-body">
                @if (array_sum($chart['monthly']['commissioning']) + array_sum($chart['monthly']['recommissioning']) > 0)
                    <div class="chart-box"><canvas id="chartMonthly" aria-label="Commissioning disetujui per bulan" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5"><i class="bi bi-bar-chart fs-1 d-block mb-2"></i>Belum ada commissioning yang disetujui.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-hourglass me-2"></i>Proses berjalan menurut tahap</h2></div>
            <div class="card-body">
                @if (array_sum(array_column($chart['pipeline'], 'n')) > 0)<div class="chart-box"><canvas id="chartPipeline" aria-label="Proses berjalan menurut tahap" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5"><i class="bi bi-check2-all fs-1 d-block mb-2 text-success"></i>Tidak ada proses yang sedang berjalan.</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Baris 3: masa berlaku --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-calendar3 me-2"></i>Kalender masa berlaku (6 bulan ke depan)</h2></div>
            <div class="card-body">
                @if (array_sum($chart['expiry']['data']) > 0)<div class="chart-box"><canvas id="chartExpiry" aria-label="Kalender masa berlaku" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada unit dengan masa berlaku.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-stopwatch me-2"></i>Sisa masa berlaku (unit paling dekat habis)</h2>
                <span class="small text-muted"><span class="dot" style="background:#dc2626"></span>lewat <span class="dot ms-2" style="background:#f59e0b"></span>≤ {{ config('commissioning.expiring_days') }} hari <span class="dot ms-2" style="background:#16a34a"></span>aman</span>
            </div>
            <div class="card-body">
                @if (! empty($chart['nearest']))<div class="chart-box"><canvas id="chartNearest" aria-label="Sisa masa berlaku unit" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada unit yang laik operasi.</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Baris 4: kualitas pemeriksaan --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-wrench-adjustable me-2"></i>Item paling sering tidak sesuai</h2>
                <span class="small text-muted"><span class="dot" style="background:#dc2626"></span>kritis</span>
            </div>
            <div class="card-body">
                @if (! empty($chart['topItems']))<div class="chart-box lg"><canvas id="chartItems" aria-label="Item paling sering tidak sesuai" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>Tidak ada item tidak sesuai pada periode ini.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-diagram-3 me-2"></i>Ketidaksesuaian per kelompok</h2></div>
            <div class="card-body">
                @if (! empty($chart['byGroup']))<div class="chart-box"><canvas id="chartGroups" aria-label="Ketidaksesuaian per kelompok" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada data.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-arrow-repeat me-2"></i>Alasan recommissioning</h2></div>
            <div class="card-body">
                @if (! empty($chart['reasons']))<div class="chart-box"><canvas id="chartReasons" aria-label="Alasan recommissioning" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada recommissioning pada periode ini.</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Baris 5: temuan --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="erp-card h-100"><div class="erp-card-body">
        <div class="kpi-label">Temuan terbuka</div>
        <div class="fw-bold fs-5">{{ $findingStats['open'] }}</div>
        <div class="small {{ $findingStats['critical'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $findingStats['critical'] }} kritis</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="erp-card h-100"><div class="erp-card-body">
        <div class="kpi-label">Lewat tenggat</div>
        <div class="fw-bold fs-5 {{ $findingStats['overdue'] > 0 ? 'text-danger' : '' }}">{{ $findingStats['overdue'] }}</div>
        <div class="small text-muted">temuan terbuka</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="erp-card h-100"><div class="erp-card-body">
        <div class="kpi-label">Rata-rata waktu penutupan</div>
        <div class="fw-bold fs-5">{{ $findingStats['avg_close_days'] === null ? '-' : $findingStats['avg_close_days'].' hari' }}</div>
        <div class="small text-muted">temuan periode ini</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="erp-card h-100"><div class="erp-card-body">
        <div class="kpi-label">Dokumen unit dipantau</div>
        <div class="fw-bold fs-5">{{ number_format($docsTotal) }}</div>
        <a href="{{ route('cms.report') }}" class="small text-decoration-none">Unduh laporan &rarr;</a>
    </div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-exclamation-diamond me-2"></i>Temuan menurut tingkat dan status</h2></div>
            <div class="card-body">
                @if (collect($chart['findings'])->sum(fn ($r) => $r['open'] + $r['in_progress'] + $r['closed']) > 0)<div class="chart-box sm"><canvas id="chartFindings" aria-label="Temuan menurut tingkat dan status" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Tidak ada temuan pada periode ini.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Masa berlaku dokumen unit</h2></div>
            <div class="card-body">
                @if (array_sum(array_column($chart['docs'], 'n')) > 0)<div class="chart-box sm"><canvas id="chartDocs" aria-label="Masa berlaku dokumen unit" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5">Belum ada dokumen dengan tanggal berakhir.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-cone-striped me-2"></i>Insiden dan kejadian unit</h2>
                <a href="{{ route('cms.incidents') }}" class="small text-decoration-none">Lihat</a>
            </div>
            <div class="card-body">
                @if (array_sum($chart['incidents']['data']) > 0)
                    <div class="chart-box sm"><canvas id="chartIncidents" aria-label="Insiden per bulan" role="img"></canvas></div>
                    @if (! empty($chart['incidentKinds']))
                        <div class="small text-muted mt-2">@foreach ($chart['incidentKinds'] as $ik)<span class="me-3">{{ $ik['label'] }}: <strong>{{ $ik['n'] }}</strong></span>@endforeach</div>
                    @endif
                @else<div class="text-center text-muted py-5"><i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>Tidak ada insiden tercatat.</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Baris 6: alert --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-bell me-2"></i>Alert aktif menurut jenis</h2>
                <a href="{{ route('cms.alerts') }}" class="small text-decoration-none">Semua alert</a>
            </div>
            <div class="card-body">
                @if (! empty($chart['alertByType']))<div class="chart-box"><canvas id="chartAlertType" aria-label="Alert aktif menurut jenis" role="img"></canvas></div>
                @else<div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2 text-success"></i>Tidak ada alert aktif.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Alert yang perlu dilihat lebih dulu</h2>
                <span class="small text-muted">{{ $alerts['unacknowledged'] }} belum dilihat</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($alerts['top'] as $a)
                    <a href="{{ $a->unit ? route('cms.units.show', $a->unit) : route('cms.alerts') }}" class="list-group-item list-group-item-action sev-{{ $a->severity }}">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $a->title }}</div>
                            <span class="badge bg-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }}-subtle text-{{ \App\Models\Alert::SEVERITY_BADGES[$a->severity] }} text-nowrap align-self-start">{{ \App\Models\Alert::SEVERITY_LABELS[$a->severity] }}</span>
                        </div>
                        <div class="small text-muted">{{ $a->message }}</div>
                    </a>
                @empty
                    <div class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>Semua unit dalam kondisi aman.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Baris 7: antrian dan terbaru --}}
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-person-check me-2"></i>Menunggu tindakan Anda</h2>
                <a href="{{ route('cms.index', ['status' => 'aktif']) }}" class="small text-decoration-none">Semua proses berjalan</a>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead><tr><th>No.</th><th>Unit</th><th>Jenis</th><th>Tahap</th><th>Pemohon</th></tr></thead>
                    <tbody>
                        @forelse ($queue->take(10) as $c)
                            <tr>
                                <td><a href="{{ route('cms.show', $c) }}" class="text-decoration-none small">{{ $c->number }}</a></td>
                                <td class="fw-semibold">{{ $c->unit_code }}</td>
                                <td class="small">{{ $c->kindLabel() }}</td>
                                <td class="small">{{ $c->stage() }}</td>
                                <td class="small">{{ $c->requested_by_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-emoji-smile fs-3 d-block mb-1"></i>Tidak ada yang menunggu tindakan Anda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-patch-check me-2 text-success"></i>Disetujui terbaru</h2>
                <a href="{{ route('cms.index', ['status' => 'approved']) }}" class="small text-decoration-none">Semua</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($recent as $c)
                    <a href="{{ route('cms.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><span class="fw-semibold">{{ $c->unit_code }}</span> <span class="small text-muted">&middot; {{ $c->kindLabel() }}</span>
                            <span class="small text-muted d-block">sampai {{ $c->valid_until?->translatedFormat('d M Y') }}</span></span>
                        <span class="small text-muted text-nowrap">{{ $c->decided_at?->diffForHumans(null, true) }}</span>
                    </a>
                @empty
                    <div class="text-center text-muted py-4 small">Belum ada.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;   // pustaka grafik gagal dimuat: tabel dan angka tetap tampil
    (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(draw);

    function draw() {
        const d = @json($chart);
        const grid = 'rgba(100, 116, 139, .15)';
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#475569';
        const $ = id => document.getElementById(id);
        const short = (l, n) => l.length > n ? l.slice(0, n - 1) + '…' : l;
        const intTicks = { precision: 0 };

        if ($('chartStatus')) new Chart($('chartStatus'), {
            type: 'doughnut',
            data: { labels: d.status.map(s => s.label), datasets: [{ data: d.status.map(s => s.n), backgroundColor: d.status.map(s => s.color), borderWidth: 2 }] },
            options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
        });

        if ($('chartCategory')) new Chart($('chartCategory'), {
            type: 'bar',
            data: { labels: d.byCategory.map(r => r.label), datasets: d.status.map(s => ({ label: s.label, data: d.byCategory.map(r => r[s.key]), backgroundColor: s.color, borderRadius: 3, stack: 's' })) },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { mode: 'index', intersect: false, callbacks: { title: i => d.byCategory[i[0].dataIndex].name } } },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: intTicks, grid: { color: grid } } },
            },
        });

        if ($('chartArea')) new Chart($('chartArea'), {
            type: 'bar',
            data: { labels: d.byArea.map(r => r.label), datasets: [{ label: 'Laik operasi (%)', data: d.byArea.map(r => r.percent),
                backgroundColor: d.byArea.map(r => r.percent >= 90 ? '#16a34a' : (r.percent >= 70 ? '#f59e0b' : '#dc2626')), borderRadius: 4 }] },
            options: {
                indexAxis: 'y', maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.x + '% (' + d.byArea[c.dataIndex].fit + ' dari ' + d.byArea[c.dataIndex].total + ' unit)' } } },
                scales: { x: { min: 0, max: 100, ticks: { callback: v => v + '%' }, grid: { color: grid } }, y: { grid: { display: false }, ticks: { callback(v) { return short(this.getLabelForValue(v), 18); } } } },
            },
        });

        if ($('chartMonthly')) new Chart($('chartMonthly'), {
            data: { labels: d.monthly.labels, datasets: [
                { type: 'bar', label: 'Commissioning', data: d.monthly.commissioning, backgroundColor: '#1b4f86', borderRadius: 4, stack: 'm', yAxisID: 'y' },
                { type: 'bar', label: 'Recommissioning', data: d.monthly.recommissioning, backgroundColor: '#38bdf8', borderRadius: 4, stack: 'm', yAxisID: 'y' },
                { type: 'line', label: 'Rata-rata hari proses', data: d.monthly.lead, borderColor: '#f59e0b', backgroundColor: '#f59e0b', tension: .25, pointRadius: 3, spanGaps: true, yAxisID: 'y2' },
            ] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: intTicks, grid: { color: grid }, title: { display: true, text: 'Jumlah' } },
                    y2: { position: 'right', beginAtZero: true, grid: { display: false }, title: { display: true, text: 'Hari' } },
                },
            },
        });

        if ($('chartPipeline')) new Chart($('chartPipeline'), {
            type: 'bar',
            data: { labels: d.pipeline.map(p => p.label), datasets: [{ label: 'Proses', data: d.pipeline.map(p => p.n), backgroundColor: ['#94a3b8', '#f59e0b', '#2563eb', '#16a34a'], borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: intTicks, grid: { color: grid } } } },
        });

        if ($('chartExpiry')) new Chart($('chartExpiry'), {
            type: 'bar',
            data: { labels: d.expiry.labels, datasets: [{ label: 'Unit berakhir', data: d.expiry.data, backgroundColor: d.expiry.labels.map((l, i) => i === 0 ? '#dc2626' : (i === 1 ? '#f59e0b' : '#1b4f86')), borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: intTicks, grid: { color: grid } } } },
        });

        if ($('chartNearest')) new Chart($('chartNearest'), {
            type: 'bar',
            data: { labels: d.nearest.map(u => u.code), datasets: [{ label: 'Sisa hari', data: d.nearest.map(u => u.left),
                backgroundColor: d.nearest.map(u => u.left < 0 ? '#dc2626' : (u.left <= {{ (int) config('commissioning.expiring_days') }} ? '#f59e0b' : '#16a34a')), borderRadius: 4 }] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.y < 0 ? 'Lewat ' + Math.abs(c.parsed.y) + ' hari' : c.parsed.y + ' hari lagi' } } },
                scales: { x: { grid: { display: false } }, y: { grid: { color: grid }, title: { display: true, text: 'Hari' } } },
            },
        });

        if ($('chartItems')) new Chart($('chartItems'), {
            type: 'bar',
            data: { labels: d.topItems.map(i => i.label), datasets: [{ label: 'Kali tidak sesuai', data: d.topItems.map(i => i.n), backgroundColor: d.topItems.map(i => i.critical ? '#dc2626' : '#f59e0b'), borderRadius: 4 }] },
            options: {
                indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: intTicks, grid: { color: grid } }, y: { grid: { display: false }, ticks: { callback(v) { return short(this.getLabelForValue(v), 34); } } } },
            },
        });

        const palette = ['#1b4f86', '#38bdf8', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#94a3b8', '#0d9488'];
        if ($('chartGroups')) new Chart($('chartGroups'), {
            type: 'doughnut',
            data: { labels: d.byGroup.map(g => g.label), datasets: [{ data: d.byGroup.map(g => g.n), backgroundColor: palette, borderWidth: 2 }] },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });
        if ($('chartReasons')) new Chart($('chartReasons'), {
            type: 'doughnut',
            data: { labels: d.reasons.map(g => g.label), datasets: [{ data: d.reasons.map(g => g.n), backgroundColor: palette, borderWidth: 2 }] },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });

        if ($('chartFindings')) new Chart($('chartFindings'), {
            type: 'bar',
            data: { labels: d.findings.map(f => f.label), datasets: [
                { label: 'Terbuka', data: d.findings.map(f => f.open), backgroundColor: '#dc2626', stack: 'f' },
                { label: 'Dikerjakan', data: d.findings.map(f => f.in_progress), backgroundColor: '#f59e0b', stack: 'f' },
                { label: 'Selesai', data: d.findings.map(f => f.closed), backgroundColor: '#16a34a', stack: 'f' },
            ] },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { mode: 'index', intersect: false } }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: intTicks, grid: { color: grid } } } },
        });

        if ($('chartDocs')) new Chart($('chartDocs'), {
            type: 'bar',
            data: { labels: d.docs.map(x => x.label), datasets: [{ label: 'Dokumen', data: d.docs.map(x => x.n), backgroundColor: ['#dc2626', '#f97316', '#f59e0b', '#38bdf8', '#16a34a'], borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: intTicks, grid: { color: grid } } } },
        });

        if ($('chartIncidents')) new Chart($('chartIncidents'), {
            type: 'line',
            data: { labels: d.incidents.labels, datasets: [{ label: 'Kejadian', data: d.incidents.data, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, .12)', fill: true, tension: .25, pointRadius: 3 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: intTicks, grid: { color: grid } } } },
        });

        if ($('chartAlertType')) new Chart($('chartAlertType'), {
            type: 'bar',
            data: { labels: d.alertByType.map(a => a.label), datasets: [
                { label: 'Kritis', data: d.alertByType.map(a => a.kritis), backgroundColor: '#dc2626', stack: 'a' },
                { label: 'Tinggi', data: d.alertByType.map(a => a.tinggi), backgroundColor: '#f59e0b', stack: 'a' },
                { label: 'Sedang', data: d.alertByType.map(a => a.sedang), backgroundColor: '#2563eb', stack: 'a' },
                { label: 'Info', data: d.alertByType.map(a => a.info), backgroundColor: '#94a3b8', stack: 'a' },
            ] },
            options: {
                indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { mode: 'index', intersect: false } },
                scales: { x: { stacked: true, beginAtZero: true, ticks: intTicks, grid: { color: grid } }, y: { stacked: true, grid: { display: false }, ticks: { callback(v) { return short(this.getLabelForValue(v), 26); } } } },
            },
        });
    }
})();
</script>
@endpush
