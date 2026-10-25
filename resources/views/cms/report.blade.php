@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan dan Ekspor')
@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    @foreach ($summary as $s)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="erp-card h-100"><div class="erp-card-body">
                <div class="kpi-label">{{ $s['label'] }}</div>
                <div class="d-flex align-items-center gap-2"><span class="dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background: {{ \App\Models\Unit::STATUS_COLORS[$s['key']] }}"></span><span class="kpi-value fs-4 fw-bold">{{ $s['n'] }}</span></div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-download me-2"></i>Unduh data (CSV, terbuka rapi di Excel)</h2></div>
    <div class="list-group list-group-flush">
        @foreach ([
            ['cms.report.units', 'Daftar unit dan status', 'Status operasional, masa berlaku, sisa hari, alasan hold, idle.', $counts['units']],
            ['cms.report.commissionings', 'Semua proses commissioning', 'Jenis, alasan, status, hasil, pemohon, verifikator, penyetuju, masa berlaku.', $counts['commissionings']],
            ['cms.report.findings', 'Temuan', 'Tingkat, status, PIC, tenggat, dan catatan penutupan.', $counts['findings']],
            ['cms.report.alerts', 'Alert', 'Alert aktif dan riwayat, siapa yang melihat dan kapan.', $counts['alerts'].' aktif'],
            ['cms.report.incidents', 'Insiden dan kejadian unit', 'Jenis, tingkat, dan apakah menahan unit.', $counts['incidents']],
            ['cms.report.documents', 'Dokumen unit', 'Sertifikat, masa berlaku, dan sisa hari.', $counts['documents']],
        ] as [$route, $title, $desc, $n])
            <a href="{{ route($route) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                <span><span class="fw-semibold">{{ $title }}</span><span class="small text-muted d-block">{{ $desc }}</span></span>
                <span class="text-nowrap small text-muted">{{ is_numeric($n) ? number_format($n).' baris' : $n }} <i class="bi bi-download ms-2"></i></span>
            </a>
        @endforeach
    </div>
</div>
@endsection
