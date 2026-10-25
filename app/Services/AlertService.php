<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Commissioning;
use App\Models\Finding;
use App\Models\Unit;
use App\Models\UnitDocument;
use Illuminate\Support\Facades\Cache;

/**
 * Menghitung ulang semua alert dari kondisi data saat ini. Idempoten: tiap kondisi punya dedupe_key tetap,
 * alert yang kondisinya sudah hilang otomatis ditandai selesai (resolved), dan yang muncul lagi dibuka kembali.
 */
class AlertService
{
    /** Dipanggil saat halaman dibuka: memindai paling sering sekali per alert_scan_ttl detik. */
    public function scanIfStale(): void
    {
        if (Cache::add('commissioning.alert_scan', 1, (int) config('commissioning.alert_scan_ttl'))) {
            $this->scan();
        }
    }

    /** Dipanggil setelah perubahan penting (persetujuan, insiden, temuan) agar alert langsung mutakhir. */
    public function refresh(): void
    {
        Cache::put('commissioning.alert_scan', 1, (int) config('commissioning.alert_scan_ttl'));
        $this->scan();
    }

    /** @return array{active:int, created:int, resolved:int} */
    public function scan(): array
    {
        $now = now();
        $today = today();
        $seen = [];
        $created = 0;

        $put = function (string $key, ?int $unitId, string $type, string $severity, string $title, string $message, $due = null) use (&$seen, &$created, $now) {
            $seen[] = $key;
            $alert = Alert::where('dedupe_key', $key)->first();
            if ($alert) {
                $reopened = $alert->resolved_at !== null;
                $alert->fill([
                    'unit_id' => $unitId, 'type' => $type, 'severity' => $severity, 'title' => $title, 'message' => $message,
                    'due_date' => $due, 'last_seen_at' => $now, 'resolved_at' => null,
                ]);
                if ($reopened) {
                    $alert->fill(['first_seen_at' => $now, 'acknowledged_at' => null, 'acknowledged_by_name' => null]);
                    $created++;
                }
                $alert->save();

                return;
            }
            Alert::create([
                'unit_id' => $unitId, 'dedupe_key' => $key, 'type' => $type, 'severity' => $severity, 'title' => $title, 'message' => $message,
                'due_date' => $due, 'first_seen_at' => $now, 'last_seen_at' => $now,
            ]);
            $created++;
        };

        $expiringDays = (int) config('commissioning.expiring_days');
        $criticalDays = (int) config('commissioning.critical_expiring_days');
        $idleDays = (int) config('commissioning.idle_days');

        // Unit
        foreach (Unit::query()->active()->withProcess()->get() as $unit) {
            $code = $unit->code;

            if ($unit->hold_since) {
                $put("unit_hold:{$unit->id}", $unit->id, 'unit_hold', 'kritis', "Unit {$code} ditahan (hold)",
                    'Unit tidak boleh dioperasikan sampai recommissioning disetujui. '.($unit->hold_reason ?: ''), null);
            }

            $left = $unit->daysLeft($today);
            if ($left !== null && $left < 0) {
                $put("commissioning_expired:{$unit->id}", $unit->id, 'commissioning_expired', 'kritis', "Commissioning unit {$code} kedaluwarsa",
                    'Masa berlaku berakhir '.$unit->commissioned_until->translatedFormat('d M Y').' ('.abs($left).' hari lalu). Lakukan recommissioning sebelum unit dipakai.', $unit->commissioned_until);
            } elseif ($left !== null && $left <= $expiringDays) {
                $put("commissioning_expiring:{$unit->id}", $unit->id, 'commissioning_expiring', $left <= $criticalDays ? 'tinggi' : 'sedang', "Commissioning unit {$code} segera berakhir",
                    'Berlaku sampai '.$unit->commissioned_until->translatedFormat('d M Y').' ('.($left === 0 ? 'hari ini' : "{$left} hari lagi").'). Jadwalkan recommissioning berkala.', $unit->commissioned_until);
            } elseif ($left === null && ! $unit->hold_since && ! $unit->has_process) {
                $put("not_commissioned:{$unit->id}", $unit->id, 'not_commissioned', 'tinggi', "Unit {$code} belum commissioning",
                    'Unit aktif ini belum pernah dinyatakan laik operasi. Buat commissioning sebelum dipakai di area tambang.', null);
            }

            if ($unit->idle_since && ! $unit->hold_since && ! $unit->has_process) {
                $idle = (int) $unit->idle_since->diffInDays($today);
                if ($idle >= $idleDays) {
                    $put("idle_recommission:{$unit->id}", $unit->id, 'idle_recommission', $idle >= $idleDays * 2 ? 'tinggi' : 'sedang', "Unit {$code} idle {$idle} hari",
                        'Unit lama tidak dipakai (sejak '.$unit->idle_since->translatedFormat('d M Y').'). Lakukan recommissioning (alasan: idle) sebelum dioperasikan lagi.', null);
                }
            }
        }

        // Dokumen unit
        $docDays = (int) config('commissioning.doc_expiring_days');
        UnitDocument::query()->with('unit')->whereNotNull('expires_at')->whereHas('unit', fn ($q) => $q->where('is_active', true))->get()
            ->each(function (UnitDocument $doc) use ($put, $docDays, $criticalDays) {
                $left = $doc->daysLeft();
                $label = $doc->typeLabel().($doc->number ? " {$doc->number}" : '');
                if ($left < 0) {
                    $put("doc_expired:{$doc->id}", $doc->unit_id, 'doc_expired', 'tinggi', "Dokumen {$doc->unit->code} kedaluwarsa",
                        "{$label} berakhir ".$doc->expires_at->translatedFormat('d M Y').'. Perbarui dokumen.', $doc->expires_at);
                } elseif ($left <= $docDays) {
                    $put("doc_expiring:{$doc->id}", $doc->unit_id, 'doc_expiring', $left <= $criticalDays ? 'tinggi' : 'sedang', "Dokumen {$doc->unit->code} segera berakhir",
                        "{$label} berakhir ".$doc->expires_at->translatedFormat('d M Y')." ({$left} hari lagi).", $doc->expires_at);
                }
            });

        // Temuan
        Finding::query()->with('unit')->where('status', '!=', 'closed')->whereHas('unit', fn ($q) => $q->where('is_active', true))->get()
            ->each(function (Finding $f) use ($put, $today) {
                if ($f->severity === 'critical') {
                    $put("finding_critical:{$f->id}", $f->unit_id, 'finding_critical', 'kritis', "Temuan kritis unit {$f->unit->code}",
                        "{$f->title}. Harus ditutup sebelum unit dinyatakan laik.", $f->due_date);
                } elseif ($f->due_date && $f->due_date->lt($today)) {
                    $put("finding_overdue:{$f->id}", $f->unit_id, 'finding_overdue', 'tinggi', "Temuan unit {$f->unit->code} lewat tenggat",
                        "{$f->title} (tenggat ".$f->due_date->translatedFormat('d M Y').').', $f->due_date);
                }
            });

        // Persetujuan terlambat
        $sla = (int) config('commissioning.approval_sla_days');
        Commissioning::query()->where('status', 'submitted')->whereNotNull('submitted_at')->where('submitted_at', '<', now()->subDays($sla))->get()
            ->each(function (Commissioning $c) use ($put, $sla) {
                $days = (int) $c->submitted_at->diffInDays(now());
                $put("approval_overdue:{$c->id}", $c->unit_id, 'approval_overdue', 'sedang', "{$c->number} menunggu {$days} hari",
                    "{$c->kindLabel()} unit {$c->unit_code}: {$c->stage()} (batas {$sla} hari).", null);
            });

        $resolved = Alert::active()->whereNotIn('dedupe_key', $seen ?: ['-'])->update(['resolved_at' => $now]);

        return ['active' => count($seen), 'created' => $created, 'resolved' => $resolved];
    }
}
