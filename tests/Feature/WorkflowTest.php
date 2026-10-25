<?php

namespace Tests\Feature;

use App\Models\Commissioning;
use App\Models\Finding;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_makes_the_unit_fit_for_operation(): void
    {
        $unit = $this->unit(['code' => 'HD-101', 'category' => 'HD']);

        $this->as('inspector', '2')->post('/commissioning/proses', ['unit' => 'hd-101'])->assertRedirect();
        $c = Commissioning::firstOrFail();
        $this->assertSame('commissioning', $c->kind);
        $this->assertSame('draft', $c->status);
        $this->assertGreaterThan(20, $c->items()->count());

        // Checklist HD memuat rem sekunder, tidak memuat sling alat angkat.
        $labels = $c->items()->pluck('label')->all();
        $this->assertContains('Rem sekunder / darurat (retarder) berfungsi', $labels);
        $this->assertNotContains('Wire rope, hook, safety latch, dan sling dalam kondisi baik', $labels);

        $this->fill($c, 'ok', action: 'submit')->assertRedirect(route('cms.show', $c))->assertSessionHas('success');
        $c->refresh();
        $this->assertSame('submitted', $c->status);

        $this->as('safety', '3', 'Sari')->post("/commissioning/proses/{$c->number}/verifikasi/safety", ['notes' => 'aman'])->assertSessionHas('success');
        $this->as('workshop', '4', 'Wawan')->post("/commissioning/proses/{$c->number}/verifikasi/workshop")->assertSessionHas('success');
        $this->as('approver', '5', 'Pak Dedi')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertSessionHas('success');

        $c->refresh();
        $unit->refresh();
        $this->assertSame('approved', $c->status);
        $this->assertSame('laik', $c->result);
        $this->assertTrue($unit->isFit());
        $this->assertSame('commissioned', $unit->operationalStatus());
        $this->assertSame(now()->addMonthsNoOverflow(12)->toDateString(), $unit->commissioned_until->toDateString());
        $this->assertSame($c->id, $unit->last_commissioning_id);

        $this->as('viewer')->get("/commissioning/proses/{$c->number}/lembar")->assertOk()->assertSee('LAIK OPERASI');
    }

    public function test_second_pass_is_a_recommissioning_and_needs_a_reason(): void
    {
        $unit = $this->unit();
        $first = $this->readyForDecision($unit);
        $this->as('approver', '5')->post("/commissioning/proses/{$first->number}/setujui", ['result' => 'laik']);

        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code])->assertSessionHas('error');
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code, 'reason' => 'modifikasi'])->assertRedirect();

        $second = Commissioning::query()->latest('id')->first();
        $this->assertSame('recommissioning', $second->kind);
        $this->assertSame('modifikasi', $second->reason);
        $this->assertContains('Modifikasi disetujui tertulis oleh engineering / pabrikan', $second->items()->pluck('label')->all());
        $this->assertNotContains('Penyebab insiden dan rekomendasi investigasi sudah ditindaklanjuti', $second->items()->pluck('label')->all());
    }

    public function test_one_active_process_per_unit(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code])->assertRedirect();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code])->assertSessionHas('error');
        $this->assertSame(1, Commissioning::count());
    }

    public function test_cannot_submit_with_blank_items_or_missing_notes(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();

        $first = $c->items()->first();
        $this->as('inspector')->put("/commissioning/proses/{$c->number}/jawaban", ['result' => [$first->id => 'ok'], 'action' => 'submit'])->assertSessionHas('error');
        $this->assertSame('draft', $c->refresh()->status);

        $results = $c->items()->pluck('id')->mapWithKeys(fn ($id) => [$id => 'ok'])->all();
        $results[$first->id] = 'bad';
        $this->as('inspector')->put("/commissioning/proses/{$c->number}/jawaban", ['result' => $results, 'action' => 'submit'])->assertSessionHas('error');
        $this->assertSame('draft', $c->refresh()->status);
    }

    public function test_critical_bad_item_blocks_approval_until_the_finding_is_closed(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $critical = $c->items()->where('is_critical', true)->first();

        $this->fill($c, 'ok', [$critical->id => 'bad'], action: 'submit');
        $finding = Finding::firstOrFail();
        $this->assertSame('critical', $finding->severity);
        $this->assertSame('open', $finding->status);

        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/verifikasi/safety");
        $this->as('workshop', '4')->post("/commissioning/proses/{$c->number}/verifikasi/workshop");

        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertSessionHas('error');
        $this->assertSame('submitted', $c->refresh()->status);

        $this->as('safety', '3')->post("/commissioning/temuan/{$finding->id}/tutup", ['note' => 'Rem sudah diganti'])->assertSessionHas('success');
        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertSessionHas('success');
        $this->assertSame('approved', $c->refresh()->status);
    }

    public function test_minor_finding_allows_conditional_approval_only_with_due_date_and_notes(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $minor = $c->items()->where('is_critical', false)->first();
        $this->fill($c, 'ok', [$minor->id => 'bad'], action: 'submit');
        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/verifikasi/safety");
        $this->as('workshop', '4')->post("/commissioning/proses/{$c->number}/verifikasi/workshop");
        $finding = Finding::firstOrFail();
        $this->assertSame('minor', $finding->severity);

        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertSessionHas('error');
        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik_catatan', 'notes' => 'Perbaiki lampu'])->assertSessionHas('error');

        $this->as('workshop', '4')->put("/commissioning/temuan/{$finding->id}", ['pic_name' => 'Wawan', 'due_date' => now()->addDays(7)->toDateString()])->assertSessionHas('success');
        $this->assertSame('in_progress', $finding->refresh()->status);

        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik_catatan'])->assertSessionHas('error');
        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik_catatan', 'notes' => 'Perbaiki lampu'])->assertSessionHas('success');
        $this->assertSame('laik_catatan', $c->refresh()->result);
    }

    public function test_requester_cannot_verify_or_approve_own_request_but_admin_can(): void
    {
        $unit = $this->unit();
        $this->as('safety', '3')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $this->fill($c, 'ok', role: 'safety', id: '3', action: 'submit');

        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/verifikasi/safety")->assertSessionHas('error');
        $this->assertNull($c->refresh()->safety_at);

        $this->as('admin', '9')->post("/commissioning/proses/{$c->number}/verifikasi/safety")->assertSessionHas('success');
    }

    public function test_approval_needs_both_verifications(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $this->fill($c, 'ok', action: 'submit');
        $this->as('safety', '3')->post("/commissioning/proses/{$c->number}/verifikasi/safety");

        $this->as('approver', '5')->post("/commissioning/proses/{$c->number}/setujui", ['result' => 'laik'])->assertSessionHas('error');
        $this->assertSame('submitted', $c->refresh()->status);
    }

    public function test_send_back_allows_editing_again_and_resets_verifications(): void
    {
        $unit = $this->unit();
        $c = $this->readyForDecision($unit);
        $this->as('workshop', '4')->post("/commissioning/proses/{$c->number}/kembalikan", ['reason' => 'Foto kurang jelas'])->assertSessionHas('success');
        $this->assertSame('rejected', $c->refresh()->status);

        $this->fill($c, 'ok', action: 'submit')->assertSessionHas('success');
        $c->refresh();
        $this->assertSame('submitted', $c->status);
        $this->assertNull($c->safety_at);
        $this->assertNull($c->workshop_at);
    }

    public function test_cancel_closes_findings_and_frees_the_unit(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();
        $critical = $c->items()->where('is_critical', true)->first();
        $this->fill($c, 'ok', [$critical->id => 'bad'], action: 'submit');
        $this->assertSame(1, Finding::open()->count());

        $this->as('inspector')->post("/commissioning/proses/{$c->number}/batal", ['reason' => 'Salah unit'])->assertSessionHas('success');
        $this->assertSame('cancelled', $c->refresh()->status);
        $this->assertSame(0, Finding::open()->count());
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code])->assertRedirect();
    }

    public function test_only_the_requester_or_admin_can_fill_or_cancel(): void
    {
        $unit = $this->unit();
        $this->as('inspector', '2')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = Commissioning::firstOrFail();

        $this->as('inspector', '7', 'Lain')->post("/commissioning/proses/{$c->number}/batal")->assertSessionHas('error');
        $this->assertSame('draft', $c->refresh()->status);
        $this->as('inspector', '7', 'Lain')->put("/commissioning/proses/{$c->number}/jawaban", ['result' => [], 'action' => 'save'])->assertSessionHas('error');
        $this->as('admin', '9')->post("/commissioning/proses/{$c->number}/batal")->assertSessionHas('success');
    }

    public function test_incident_puts_the_unit_on_hold_until_recommissioning_is_approved(): void
    {
        $unit = $this->unit();
        $first = $this->readyForDecision($unit);
        $this->as('approver', '5')->post("/commissioning/proses/{$first->number}/setujui", ['result' => 'laik']);
        $this->assertSame('commissioned', $unit->refresh()->operationalStatus());

        $this->as('safety', '3')->post('/commissioning/insiden', [
            'unit' => $unit->code, 'kind' => 'kecelakaan', 'severity' => 'sedang', 'occurred_at' => now()->subHour()->format('Y-m-d H:i'), 'description' => 'Menabrak tanggul',
        ])->assertRedirect(route('cms.incidents'));
        $this->assertSame('hold', $unit->refresh()->operationalStatus());
        $this->assertFalse($unit->isFit());

        // recommissioning dibuat setelah hold dipasang -> hold dicabut saat disetujui
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code, 'reason' => 'insiden'])->assertRedirect();
        $re = Commissioning::query()->latest('id')->first();
        $this->assertContains('Penyebab insiden dan rekomendasi investigasi sudah ditindaklanjuti', $re->items()->pluck('label')->all());
        $this->fill($re, 'ok', action: 'submit');
        $this->as('safety', '3')->post("/commissioning/proses/{$re->number}/verifikasi/safety");
        $this->as('workshop', '4')->post("/commissioning/proses/{$re->number}/verifikasi/workshop");
        $this->as('approver', '5')->post("/commissioning/proses/{$re->number}/setujui", ['result' => 'laik'])->assertSessionHas('success');

        $unit->refresh();
        $this->assertNull($unit->hold_since);
        $this->assertSame('commissioned', $unit->operationalStatus());
        $this->assertSame('resolved', Incident::firstOrFail()->status);
    }

    public function test_near_miss_does_not_hold_the_unit(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/insiden', [
            'unit' => $unit->code, 'kind' => 'near_miss', 'severity' => 'ringan', 'occurred_at' => now()->subHour()->format('Y-m-d H:i'), 'description' => 'Hampir menyenggol',
        ])->assertRedirect();
        $this->assertNull($unit->refresh()->hold_since);
        $this->assertFalse(Incident::firstOrFail()->requires_recommissioning);
    }
}
