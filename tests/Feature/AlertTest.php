<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Commissioning;
use App\Models\Finding;
use App\Models\Unit;
use App\Models\UnitDocument;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private function scan(): void
    {
        app(AlertService::class)->scan();
    }

    private function alert(string $type, Unit $unit): ?Alert
    {
        return Alert::query()->where('type', $type)->where('unit_id', $unit->id)->first();
    }

    public function test_unit_status_is_computed_from_the_validity_date(): void
    {
        $this->assertSame('not_commissioned', $this->unit()->operationalStatus());
        $this->assertSame('commissioned', $this->unit(['commissioned_until' => today()->addDays(120)])->operationalStatus());
        $this->assertSame('expiring', $this->unit(['commissioned_until' => today()->addDays(30)])->operationalStatus());
        $this->assertSame('expiring', $this->unit(['commissioned_until' => today()])->operationalStatus());
        $this->assertSame('expired', $this->unit(['commissioned_until' => today()->subDay()])->operationalStatus());
        $this->assertSame('hold', $this->unit(['commissioned_until' => today()->addDays(120), 'hold_since' => now()])->operationalStatus());

        $busy = $this->unit();
        Commissioning::create(['number' => 'CMS-T-1', 'unit_id' => $busy->id, 'unit_code' => $busy->code, 'unit_name' => $busy->name, 'category' => 'HD', 'kind' => 'commissioning', 'requested_by_name' => 'x']);
        $this->assertSame('in_process', $busy->refresh()->operationalStatus());
    }

    public function test_expired_expiring_and_never_commissioned_units_raise_alerts(): void
    {
        $expired = $this->unit(['commissioned_until' => today()->subDays(3)]);
        $soon = $this->unit(['commissioned_until' => today()->addDays(5)]);
        $later = $this->unit(['commissioned_until' => today()->addDays(25)]);
        $fine = $this->unit(['commissioned_until' => today()->addDays(200)]);
        $new = $this->unit();

        $this->scan();

        $this->assertSame('kritis', $this->alert('commissioning_expired', $expired)->severity);
        $this->assertSame('tinggi', $this->alert('commissioning_expiring', $soon)->severity);
        $this->assertSame('sedang', $this->alert('commissioning_expiring', $later)->severity);
        $this->assertNull(Alert::query()->where('unit_id', $fine->id)->first());
        $this->assertSame('tinggi', $this->alert('not_commissioned', $new)->severity);
    }

    public function test_hold_idle_and_inactive_units(): void
    {
        $hold = $this->unit(['commissioned_until' => today()->addDays(100), 'hold_since' => now(), 'hold_reason' => 'Kecelakaan']);
        $idle = $this->unit(['commissioned_until' => today()->addDays(100), 'idle_since' => today()->subDays(45)]);
        $recent = $this->unit(['commissioned_until' => today()->addDays(100), 'idle_since' => today()->subDays(10)]);
        $off = $this->unit(['commissioned_until' => today()->subDays(10), 'is_active' => false]);

        $this->scan();

        $this->assertSame('kritis', $this->alert('unit_hold', $hold)->severity);
        $this->assertSame('sedang', $this->alert('idle_recommission', $idle)->severity);
        $this->assertNull($this->alert('idle_recommission', $recent));
        $this->assertNull(Alert::query()->where('unit_id', $off->id)->first());
    }

    public function test_documents_raise_alerts(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->addDays(200)]);
        UnitDocument::create(['unit_id' => $unit->id, 'type' => 'kir', 'number' => 'K-1', 'expires_at' => today()->subDay()]);
        UnitDocument::create(['unit_id' => $unit->id, 'type' => 'apar', 'expires_at' => today()->addDays(10)]);
        UnitDocument::create(['unit_id' => $unit->id, 'type' => 'asuransi', 'expires_at' => today()->addDays(100)]);
        UnitDocument::create(['unit_id' => $unit->id, 'type' => 'lainnya']);

        $this->scan();

        $this->assertSame(1, Alert::query()->where('type', 'doc_expired')->count());
        $this->assertSame(1, Alert::query()->where('type', 'doc_expiring')->count());
        $this->assertSame(2, Alert::count());
    }

    public function test_findings_raise_alerts_critical_first_overdue_otherwise(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->addDays(200)]);
        $c = Commissioning::create(['number' => 'CMS-T-2', 'unit_id' => $unit->id, 'unit_code' => $unit->code, 'unit_name' => 'x', 'category' => 'HD', 'kind' => 'commissioning', 'requested_by_name' => 'x', 'status' => 'approved']);
        $base = ['commissioning_id' => $c->id, 'unit_id' => $unit->id, 'title' => 'T', 'status' => 'open'];
        Finding::create($base + ['severity' => 'critical', 'due_date' => today()->subDay()]);
        Finding::create($base + ['severity' => 'minor', 'due_date' => today()->subDays(2)]);
        Finding::create($base + ['severity' => 'minor', 'due_date' => today()->addDays(2)]);
        Finding::create(array_merge($base, ['severity' => 'minor', 'due_date' => today()->subDays(2), 'status' => 'closed']));

        $this->scan();

        $this->assertSame(1, Alert::query()->where('type', 'finding_critical')->count());
        $this->assertSame(1, Alert::query()->where('type', 'finding_overdue')->count());
    }

    public function test_overdue_approval_raises_an_alert(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->addDays(200)]);
        Commissioning::create(['number' => 'CMS-T-3', 'unit_id' => $unit->id, 'unit_code' => $unit->code, 'unit_name' => 'x', 'category' => 'HD', 'kind' => 'recommissioning',
            'requested_by_name' => 'x', 'status' => 'submitted', 'submitted_at' => now()->subDays(5)]);
        $this->scan();
        $this->assertNotNull($this->alert('approval_overdue', $unit));
    }

    public function test_scan_is_idempotent_and_resolves_alerts_when_the_condition_clears(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->subDay()]);
        $this->scan();
        $this->scan();
        $this->assertSame(1, Alert::count());
        $this->assertNull(Alert::first()->resolved_at);

        $unit->update(['commissioned_until' => today()->addDays(300)]);
        $this->scan();
        $this->assertNotNull(Alert::first()->resolved_at);
        $this->assertSame(0, Alert::active()->count());

        // muncul lagi: dibuka kembali, bukan duplikat, dan penandaan "dilihat" direset
        Alert::first()->update(['acknowledged_at' => now(), 'acknowledged_by_name' => 'Budi']);
        $unit->update(['commissioned_until' => today()->subDay()]);
        $this->scan();
        $a = Alert::first();
        $this->assertSame(1, Alert::count());
        $this->assertNull($a->resolved_at);
        $this->assertNull($a->acknowledged_at);
    }

    public function test_approval_resolves_the_expiry_alert_immediately(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->subDays(2), 'last_commissioning_id' => 1]);
        $this->scan();
        $this->assertSame(1, Alert::active()->count());

        $c = $this->readyForDecision($unit);
        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik']);

        $this->assertSame(0, Alert::active()->where('type', 'commissioning_expired')->count());
    }

    public function test_acknowledging_alerts_and_the_alert_page(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->subDays(3)]);
        $this->scan();
        $alert = Alert::firstOrFail();

        $this->as('viewer')->post("/commissioning/alert/{$alert->id}/dilihat")->assertForbidden();
        $this->as('inspector', '2', 'Budi')->post("/commissioning/alert/{$alert->id}/dilihat")->assertSessionHas('success');
        $alert->refresh();
        $this->assertSame('Budi', $alert->acknowledged_by_name);

        $this->as('viewer')->get('/commissioning/alert')->assertOk()->assertSee($unit->code)->assertSee('Kritis');
        $this->as('viewer')->get('/commissioning/alert?status=baru')->assertOk()->assertDontSee('kedaluwarsa</div>', false);
        $this->as('safety')->post('/commissioning/alert/pindai')->assertSessionHas('success');
        $this->as('inspector')->post('/commissioning/alert/pindai')->assertForbidden();
    }

    public function test_scan_command_runs(): void
    {
        $this->unit(['commissioned_until' => today()->subDay()]);
        $this->artisan('commissioning:scan')->expectsOutputToContain('Alert aktif 1')->assertSuccessful();
    }
}
