<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Commissioning;
use App\Models\Finding;
use App\Models\Incident;
use App\Models\Unit;
use App\Models\UnitDocument;
use App\Support\Csv;

/** Ekspor data (CSV, terbuka rapi di Excel) dan ringkasan laporan. */
class ReportController extends Controller
{
    public function index()
    {
        $units = Unit::query()->active()->withProcess()->get();
        $by = $units->groupBy(fn ($u) => $u->operationalStatus());

        return view('cms.report', [
            'summary' => collect(Unit::STATUS_LABELS)->map(fn ($label, $s) => ['key' => $s, 'label' => $label, 'n' => ($by[$s] ?? collect())->count()])->values(),
            'counts' => [
                'units' => Unit::count(), 'commissionings' => Commissioning::count(), 'findings' => Finding::count(),
                'incidents' => Incident::count(), 'documents' => UnitDocument::count(), 'alerts' => Alert::active()->count(),
            ],
        ]);
    }

    public function units()
    {
        $rows = Unit::query()->withProcess()->orderBy('code')->get()->map(fn (Unit $u) => [
            $u->code, $u->name, config('commissioning.categories.'.$u->category, $u->category), $u->area, $u->brand, $u->model, $u->serial_number, $u->year, $u->plate, $u->owner,
            $u->is_active ? 'Aktif' : 'Nonaktif', $u->statusLabel(), $u->commissioned_until?->toDateString(), $u->daysLeft(), $u->hold_reason, $u->idle_since?->toDateString(),
        ]);

        return Csv::download('unit-'.now()->format('Ymd').'.csv', ['Kode', 'Nama', 'Kategori', 'Area', 'Merek', 'Model', 'Serial', 'Tahun', 'Plat', 'Pemilik', 'Aktif', 'Status', 'Berlaku sampai', 'Sisa hari', 'Alasan hold', 'Idle sejak'], $rows);
    }

    public function commissionings()
    {
        $rows = Commissioning::query()->orderByDesc('id')->cursor()->map(fn (Commissioning $c) => [
            $c->number, $c->unit_code, $c->unit_name, $c->kindLabel(), $c->reasonLabel(), $c->statusLabel(), $c->result ? Commissioning::RESULT_LABELS[$c->result] : '',
            $c->requested_by_name, $c->created_at->toDateTimeString(), $c->submitted_at?->toDateTimeString(), $c->safety_by_name, $c->workshop_by_name,
            $c->decided_by_name, $c->decided_at?->toDateTimeString(), $c->valid_until?->toDateString(),
        ]);

        return Csv::download('commissioning-'.now()->format('Ymd').'.csv', ['No.', 'Unit', 'Nama unit', 'Jenis', 'Alasan', 'Status', 'Hasil', 'Pemohon', 'Dibuat', 'Diajukan', 'Verifikator Safety', 'Verifikator Workshop', 'Penyetuju', 'Diputuskan', 'Berlaku sampai'], $rows);
    }

    public function findings()
    {
        $rows = Finding::query()->with(['unit:id,code', 'commissioning:id,number'])->orderByDesc('id')->cursor()->map(fn (Finding $f) => [
            $f->commissioning->number, $f->unit->code, $f->title, Finding::SEVERITY_LABELS[$f->severity] ?? $f->severity, Finding::STATUS_LABELS[$f->status] ?? $f->status,
            $f->description, $f->action_plan, $f->pic_name, $f->due_date?->toDateString(), $f->created_at->toDateString(), $f->closed_at?->toDateString(), $f->closed_note,
        ]);

        return Csv::download('temuan-'.now()->format('Ymd').'.csv', ['No. proses', 'Unit', 'Temuan', 'Tingkat', 'Status', 'Uraian', 'Rencana tindakan', 'PIC', 'Tenggat', 'Dibuat', 'Ditutup', 'Catatan penutupan'], $rows);
    }

    public function alerts()
    {
        $rows = Alert::query()->with('unit:id,code')->orderByRaw("case severity when 'kritis' then 0 when 'tinggi' then 1 when 'sedang' then 2 else 3 end")->cursor()->map(fn (Alert $a) => [
            Alert::SEVERITY_LABELS[$a->severity] ?? $a->severity, $a->typeLabel(), $a->unit?->code, $a->title, $a->message, $a->due_date?->toDateString(),
            $a->first_seen_at->toDateTimeString(), $a->acknowledged_by_name, $a->acknowledged_at?->toDateTimeString(), $a->resolved_at ? 'Selesai' : 'Aktif',
        ]);

        return Csv::download('alert-'.now()->format('Ymd').'.csv', ['Tingkat', 'Jenis', 'Unit', 'Judul', 'Pesan', 'Tenggat', 'Pertama terlihat', 'Dilihat oleh', 'Dilihat pada', 'Status'], $rows);
    }

    public function incidents()
    {
        $rows = Incident::query()->with('unit:id,code')->orderByDesc('occurred_at')->cursor()->map(fn (Incident $i) => [
            $i->unit->code, $i->occurred_at->toDateTimeString(), $i->kindLabel(), $i->severityLabel(), $i->description,
            $i->requires_recommissioning ? 'Ya' : 'Tidak', $i->status === 'open' ? 'Terbuka' : 'Selesai', $i->reported_by_name,
        ]);

        return Csv::download('insiden-'.now()->format('Ymd').'.csv', ['Unit', 'Waktu', 'Jenis', 'Tingkat', 'Uraian', 'Wajib recommissioning', 'Status', 'Pelapor'], $rows);
    }

    public function documents()
    {
        $rows = UnitDocument::query()->with('unit:id,code')->orderBy('expires_at')->cursor()->map(fn (UnitDocument $d) => [
            $d->unit->code, $d->typeLabel(), $d->number, $d->issued_at?->toDateString(), $d->expires_at?->toDateString(), $d->daysLeft(), $d->notes,
        ]);

        return Csv::download('dokumen-unit-'.now()->format('Ymd').'.csv', ['Unit', 'Jenis', 'Nomor', 'Terbit', 'Berakhir', 'Sisa hari', 'Catatan'], $rows);
    }
}
