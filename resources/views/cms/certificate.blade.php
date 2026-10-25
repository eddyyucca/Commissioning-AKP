<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Laik Operasi {{ $c->unit_code }} | {{ $c->number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef1f6; font-size: 13px; }
        .sheet { max-width: 820px; margin: 18px auto; background: #fff; padding: 28px 34px; border-radius: 8px; box-shadow: 0 2px 14px rgba(0,0,0,.08); }
        .head { border-bottom: 3px solid #0e2a47; padding-bottom: 10px; margin-bottom: 14px; }
        .result { border: 3px solid {{ $c->result === 'laik' ? '#16a34a' : '#f59e0b' }}; color: {{ $c->result === 'laik' ? '#166534' : '#92400e' }}; border-radius: 10px; padding: 10px 16px; text-align: center; font-size: 1.25rem; font-weight: 800; letter-spacing: .5px; }
        td, th { padding: 4px 8px !important; }
        .sign { height: 70px; border-bottom: 1px solid #333; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="no-print text-end mb-2"><button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi"></i>Cetak / simpan PDF</button></div>

    <div class="head d-flex justify-content-between align-items-center">
        <div>
            <div class="small text-muted">PT Adhi Kartiko Pratama Tbk</div>
            <h1 class="h4 mb-0">LEMBAR {{ strtoupper($c->kindLabel()) }} UNIT</h1>
            <div class="small">No. {{ $c->number }}</div>
        </div>
        <img src="{{ asset('assets/images/akp.png') }}" height="48" alt="AKP">
    </div>

    <div class="result mb-3">{{ \App\Models\Commissioning::RESULT_LABELS[$c->result] ?? '-' }}</div>

    <table class="table table-sm table-borderless mb-3">
        <tr><th style="width:160px">Unit</th><td>{{ $c->unit_code }} &mdash; {{ $c->unit_name }}</td><th style="width:150px">Kategori</th><td>{{ config('commissioning.categories.'.$c->category, $c->category) }}</td></tr>
        <tr><th>Merek / model</th><td>{{ collect([$c->unit->brand, $c->unit->model, $c->unit->year])->filter()->implode(' / ') ?: '-' }}</td><th>No. serial</th><td>{{ $c->unit->serial_number ?: '-' }}</td></tr>
        <tr><th>Area</th><td>{{ $c->area ?: '-' }}</td><th>Meter</th><td>{{ $c->meter_reading !== null ? number_format($c->meter_reading, 0, ',', '.').' '.$c->meter_unit : '-' }}</td></tr>
        <tr><th>Jenis proses</th><td>{{ $c->kindLabel() }}@if ($c->reason) ({{ $c->reasonLabel() }})@endif</td><th>Tanggal keputusan</th><td>{{ $c->decided_at?->translatedFormat('d F Y') }}</td></tr>
        <tr><th>Berlaku sampai</th><td colspan="3"><strong>{{ $c->valid_until?->translatedFormat('d F Y') }}</strong></td></tr>
        @if ($c->decision_notes)<tr><th>Catatan</th><td colspan="3">{{ $c->decision_notes }}</td></tr>@endif
    </table>

    @php($count = fn ($r) => $groups->flatten()->where('result', $r)->count())
    <p class="mb-2">Ringkasan checklist: <strong>{{ $count('ok') }}</strong> sesuai, <strong>{{ $count('bad') }}</strong> tidak sesuai, <strong>{{ $count('na') }}</strong> N/A dari {{ $groups->flatten()->count() }} item.</p>

    @if ($findings->isNotEmpty())
        <table class="table table-sm table-bordered">
            <thead class="table-light"><tr><th>Temuan</th><th>Tingkat</th><th>Status</th><th>Tenggat / penutupan</th></tr></thead>
            <tbody>@foreach ($findings as $f)
                <tr><td>{{ $f->title }}</td><td>{{ \App\Models\Finding::SEVERITY_LABELS[$f->severity] }}</td><td>{{ \App\Models\Finding::STATUS_LABELS[$f->status] }}</td>
                    <td>{{ $f->status === 'closed' ? 'Ditutup '.$f->closed_at?->translatedFormat('d M Y') : ($f->due_date?->translatedFormat('d M Y') ?? '-') }}</td></tr>
            @endforeach</tbody>
        </table>
    @endif

    <div class="row text-center mt-4 g-3">
        <div class="col-4"><div class="small">Pemeriksa</div><div class="sign"></div><div class="fw-semibold">{{ $c->requested_by_name }}</div></div>
        <div class="col-4"><div class="small">Verifikator Safety / Workshop</div><div class="sign"></div><div class="fw-semibold">{{ $c->safety_by_name }}<br>{{ $c->workshop_by_name }}</div></div>
        <div class="col-4"><div class="small">Penyetuju</div><div class="sign"></div><div class="fw-semibold">{{ $c->decided_by_name }}</div></div>
    </div>
    <div class="small text-muted mt-4">Dicetak {{ now()->translatedFormat('d F Y H:i') }} dari Commissioning AKP (AKPSatu). Status terkini unit dapat berubah (insiden, hold, kedaluwarsa); periksa sistem sebelum mengoperasikan.</div>
</div>
</body>
</html>
