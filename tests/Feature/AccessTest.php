<?php

namespace Tests\Feature;

use App\Models\Commissioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_portal_and_up_stays_open(): void
    {
        $this->logoutPortal();
        $this->get('/commissioning')->assertRedirect();
        $this->get('/commissioning/unit')->assertRedirect();
        $this->get('/up')->assertOk();
    }

    public function test_unknown_level_is_refused(): void
    {
        $this->as('hacker')->get('/commissioning')->assertForbidden();
    }

    public function test_every_known_level_opens_the_read_pages(): void
    {
        $unit = $this->unit();
        foreach (['admin', 'approver', 'safety', 'workshop', 'inspector', 'viewer'] as $role) {
            foreach (['/commissioning', '/commissioning/unit', "/commissioning/unit/{$unit->code}", '/commissioning/proses', '/commissioning/temuan', '/commissioning/insiden', '/commissioning/alert', '/commissioning/checklist', '/commissioning/laporan'] as $path) {
                $this->as($role)->get($path)->assertOk();
            }
        }
    }

    public function test_creating_pages_open_for_the_levels_that_may_start_a_process(): void
    {
        $unit = $this->unit();
        foreach (['admin', 'inspector', 'safety', 'workshop'] as $role) {
            $this->as($role)->get('/commissioning/proses/baru')->assertOk()->assertSee('Mulai Commissioning');
            $this->as($role)->get('/commissioning/proses/baru?unit='.$unit->code)->assertOk()->assertSee($unit->code);
        }
        $this->as('approver')->get('/commissioning/proses/baru')->assertForbidden();
    }

    public function test_viewer_cannot_change_anything(): void
    {
        $unit = $this->unit();
        $this->as('viewer')->get('/commissioning/proses/baru')->assertForbidden();
        $this->as('viewer')->post('/commissioning/proses', ['unit' => $unit->code])->assertForbidden();
        $this->as('viewer')->post('/commissioning/insiden', [])->assertForbidden();
        $this->as('viewer')->post("/commissioning/unit/{$unit->code}/dokumen", [])->assertForbidden();
        $this->as('viewer')->post('/commissioning/alert/semua-dilihat')->assertForbidden();
        $this->assertSame(0, Commissioning::count());
    }

    public function test_master_data_is_admin_only(): void
    {
        $unit = $this->unit();
        foreach (['inspector', 'safety', 'workshop', 'approver', 'viewer'] as $role) {
            $this->as($role)->get('/commissioning/unit/baru')->assertForbidden();
            $this->as($role)->post('/commissioning/unit', ['code' => 'X', 'name' => 'x', 'category' => 'LV'])->assertForbidden();
            $this->as($role)->get("/commissioning/unit/{$unit->code}/ubah")->assertForbidden();
            $this->as($role)->post('/commissioning/checklist', [])->assertForbidden();
            $this->as($role)->get('/commissioning/unit/impor')->assertForbidden();
        }
        $this->as('admin')->get('/commissioning/unit/baru')->assertOk();
    }

    public function test_decision_stages_are_limited_to_their_levels(): void
    {
        $unit = $this->unit();
        $c = $this->readyForDecision($unit);

        $this->as('inspector', '8')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertForbidden();
        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertForbidden();
        $this->as('inspector', '8')->post("/commissioning/proses/{$c->number}/verifikasi/safety")->assertForbidden();
        $this->as('workshop', '4')->post("/commissioning/proses/{$c->number}/verifikasi/safety")->assertForbidden();
        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/verifikasi/workshop")->assertForbidden();
        $this->as('inspector', '8')->post("/commissioning/proses/{$c->number}/kembalikan", ['reason' => 'x'])->assertForbidden();
        $this->assertSame('submitted', $c->refresh()->status);
    }

    public function test_only_safety_and_admin_close_findings_and_severity_is_theirs(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $critical = $c->items()->where('is_critical', true)->first();
        $this->fill($c, 'ok', [$critical->id => 'bad'], action: 'submit');
        $f = \App\Models\Finding::firstOrFail();

        $this->as('workshop', '4')->post("/commissioning/temuan/{$f->id}/tutup", ['note' => 'x'])->assertForbidden();
        $this->as('workshop', '4')->put("/commissioning/temuan/{$f->id}", ['severity' => 'minor', 'pic_name' => 'Wawan'])->assertSessionHas('success');
        $this->assertSame('critical', $f->refresh()->severity, 'Workshop tidak boleh menurunkan tingkat temuan');
        $this->as('safety', '3')->put("/commissioning/temuan/{$f->id}", ['severity' => 'major'])->assertSessionHas('success');
        $this->assertSame('major', $f->refresh()->severity);
    }

    public function test_photos_need_a_valid_level_and_exist(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $item = Commissioning::firstOrFail()->items()->first();

        $this->as('viewer')->get("/commissioning/foto/{$item->id}")->assertNotFound();   // item ada, tetapi belum punya foto
        $this->as('viewer')->get('/commissioning/foto/99999')->assertNotFound();
        $this->logoutPortal();
        $this->get("/commissioning/foto/{$item->id}")->assertRedirect();
    }

    public function test_certificate_exists_only_for_approved_processes(): void
    {
        $unit = $this->unit();
        $c = $this->readyForDecision($unit);
        $this->as('viewer')->get("/commissioning/proses/{$c->number}/lembar")->assertNotFound();
        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik']);
        $this->as('viewer')->get("/commissioning/proses/{$c->number}/lembar")->assertOk();
    }
}
