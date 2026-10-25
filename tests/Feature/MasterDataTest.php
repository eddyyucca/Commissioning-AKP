<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplateItem;
use App\Models\Commissioning;
use App\Models\Unit;
use App\Models\UnitDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adds_and_edits_a_unit_with_normalized_code(): void
    {
        $this->as('admin')->post('/commissioning/unit', ['code' => ' hd-900 ', 'name' => 'Dump truck', 'category' => 'HD', 'year' => 2022, 'area' => 'Pit Utara'])
            ->assertRedirect(route('cms.units.show', 'HD-900'));
        $unit = Unit::firstOrFail();
        $this->assertSame('HD-900', $unit->code);
        $this->assertSame('AKP', $unit->owner);

        $this->as('admin')->post('/commissioning/unit', ['code' => 'hd-900', 'name' => 'Lagi', 'category' => 'HD'])->assertSessionHasErrors('code');
        $this->as('admin')->post('/commissioning/unit', ['code' => 'A B', 'name' => 'Lagi', 'category' => 'HD'])->assertSessionHasErrors('code');
        $this->as('admin')->post('/commissioning/unit', ['code' => 'X1', 'name' => 'Lagi', 'category' => 'ZZ'])->assertSessionHasErrors('category');

        $this->as('admin')->put('/commissioning/unit/HD-900', ['name' => 'Dump truck 40 ton', 'category' => 'HD', 'code' => 'IGNORED'])->assertRedirect();
        $unit->refresh();
        $this->assertSame('Dump truck 40 ton', $unit->name);
        $this->assertSame('HD-900', $unit->code, 'kode tidak boleh berubah');
    }

    public function test_deactivated_units_leave_dashboard_and_cannot_start_processes(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->addDays(100)]);
        $this->as('admin')->post("/commissioning/unit/{$unit->code}/aktif")->assertRedirect();
        $this->assertFalse($unit->refresh()->is_active);
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code])->assertSessionHas('error');
        $this->as('admin')->get('/commissioning')->assertOk()->assertSee('Belum ada unit aktif');
    }

    public function test_csv_import_creates_skips_and_reports(): void
    {
        $this->unit(['code' => 'HD-1']);
        $csv = "kode;nama;kategori;area;merek;model;serial;tahun;plat;pemilik\n"
            ."HD-1;Duplikat;HD;;;;;;;\n"
            ."HD-2;Dump truck;HD;Pit Utara;Komatsu;HD785;SN1;2021;;AKP\n"
            ."lv 3;Salah kode;LV;;;;;;;\n"
            ."LV-4;Mobil;ZZ;;;;;;;\n"
            ."LV-5;Mobil;LV;;;;;1800;;\n"
            ."LV-6;;LV;;;;;;;\n"
            ."EX-7;Excavator;;Workshop;;;;;;Mitra\n";
        $file = UploadedFile::fake()->createWithContent('unit.csv', $csv);

        $this->as('admin')->post('/commissioning/unit/impor', ['file' => $file])->assertRedirect(route('cms.units.import'));
        $result = session('import_result');

        $this->assertSame(2, $result['created']);
        $this->assertSame(['HD-1'], $result['skipped']);
        $this->assertCount(4, $result['errors']);
        $this->assertSame('LV', Unit::where('code', 'EX-7')->value('category'), 'kategori kosong dianggap LV');
        $this->assertSame('Mitra', Unit::where('code', 'EX-7')->value('owner'));
        $this->assertSame('Pit Utara', Unit::where('code', 'HD-2')->value('area'));

        $this->as('admin')->get('/commissioning/unit/impor')->assertOk();
        $this->as('admin')->get('/commissioning/unit/impor/contoh.csv')->assertOk()->assertDownload('contoh-impor-unit.csv');
        $this->as('inspector')->post('/commissioning/unit/impor', ['file' => $file])->assertForbidden();
    }

    public function test_documents_idle_and_unit_page(): void
    {
        $unit = $this->unit(['commissioned_until' => today()->addDays(200)]);
        $this->as('safety')->post("/commissioning/unit/{$unit->code}/dokumen", ['type' => 'kir', 'number' => 'K-9', 'expires_at' => today()->addDays(5)->toDateString()])->assertRedirect();
        $this->assertSame(1, UnitDocument::count());
        $this->as('safety')->post("/commissioning/unit/{$unit->code}/dokumen", ['type' => 'bukan'])->assertSessionHasErrors('type');
        $this->as('safety')->post("/commissioning/unit/{$unit->code}/dokumen", ['type' => 'kir', 'issued_at' => '2026-05-01', 'expires_at' => '2026-04-01'])->assertSessionHasErrors('expires_at');
        $this->as('inspector')->post("/commissioning/unit/{$unit->code}/dokumen", ['type' => 'kir'])->assertForbidden();

        $this->as('viewer')->get("/commissioning/unit/{$unit->code}")->assertOk()->assertSee('K-9')->assertSee('hari lagi');

        $this->as('workshop')->post("/commissioning/unit/{$unit->code}/idle", ['idle_since' => today()->subDays(40)->toDateString()])->assertRedirect();
        $this->assertNotNull($unit->refresh()->idle_since);
        $this->as('viewer')->get("/commissioning/unit/{$unit->code}")->assertSee('Unit idle sejak');
        $this->as('workshop')->post("/commissioning/unit/{$unit->code}/idle", ['idle_since' => today()->addDay()->toDateString()])->assertSessionHasErrors('idle_since');
        $this->as('workshop')->delete("/commissioning/unit/{$unit->code}/idle")->assertRedirect();
        $this->assertNull($unit->refresh()->idle_since);

        $doc = UnitDocument::firstOrFail();
        $this->as('safety')->delete("/commissioning/unit/{$unit->code}/dokumen/{$doc->id}")->assertRedirect();
        $this->assertSame(0, UnitDocument::count());
    }

    public function test_unit_list_filters_by_status_and_search(): void
    {
        $this->unit(['code' => 'HD-A', 'commissioned_until' => today()->addDays(100)]);
        $this->unit(['code' => 'HD-B', 'commissioned_until' => today()->subDays(3)]);
        $this->unit(['code' => 'LV-C', 'category' => 'LV']);

        $this->as('viewer')->get('/commissioning/unit?status=expired')->assertOk()->assertSee('HD-B')->assertDontSee('HD-A')->assertDontSee('LV-C');
        $this->as('viewer')->get('/commissioning/unit?kategori=LV')->assertSee('LV-C')->assertDontSee('HD-A');
        $this->as('viewer')->get('/commissioning/unit?q=HD-')->assertSee('HD-A')->assertSee('HD-B')->assertDontSee('LV-C');
    }

    public function test_checklist_template_changes_only_affect_new_processes(): void
    {
        $unit = $this->unit();
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $old = Commissioning::firstOrFail();
        $before = $old->items()->count();

        $this->as('admin')->post('/commissioning/checklist', ['scope' => 'both', 'categories' => ['HD'], 'group_name' => 'Khusus', 'label' => 'Pemeriksaan tambahan HD', 'is_critical' => '1'])->assertRedirect();
        $new = ChecklistTemplateItem::where('label', 'Pemeriksaan tambahan HD')->firstOrFail();
        $this->assertTrue($new->is_critical);
        $this->assertSame('HD', $new->categories);
        $this->assertSame($before, $old->items()->count());

        $this->as('admin')->post("/commissioning/proses/{$old->number}/batal");
        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $this->assertContains('Pemeriksaan tambahan HD', Commissioning::latest('id')->first()->items()->pluck('label')->all());

        $this->as('admin')->post("/commissioning/checklist/{$new->id}/aktif")->assertRedirect();
        $this->assertFalse($new->refresh()->is_active);
        $this->as('admin')->put("/commissioning/checklist/{$new->id}", ['scope' => 'commissioning', 'group_name' => 'Khusus', 'label' => 'Diubah', 'reasons' => ['idle']])->assertRedirect();
        $new->refresh();
        $this->assertSame('Diubah', $new->label);
        $this->assertNull($new->reasons, 'alasan dikosongkan untuk item khusus commissioning');
        $this->assertSame('all', $new->categories);
        $this->as('admin')->post('/commissioning/checklist', ['scope' => 'x', 'group_name' => '', 'label' => ''])->assertSessionHasErrors(['scope', 'group_name', 'label']);
    }

    public function test_default_checklist_covers_every_category_and_reason(): void
    {
        foreach (array_keys(config('commissioning.categories')) as $cat) {
            $n = ChecklistTemplateItem::all()->filter(fn ($i) => $i->appliesTo('commissioning', $cat, null))->count();
            $this->assertGreaterThan(8, $n, "kategori {$cat} punya checklist");
        }
        foreach (array_keys(config('commissioning.reasons')) as $reason) {
            $special = ChecklistTemplateItem::all()->filter(fn ($i) => $i->scope === 'recommissioning' && in_array($reason, $i->reasonList(), true))->count();
            $this->assertGreaterThan(0, $special, "alasan {$reason} punya item khusus");
        }
    }

    public function test_csv_reports_download_and_neutralise_formulas(): void
    {
        $unit = $this->unit(['name' => '=HYPERLINK("http://x")', 'commissioned_until' => today()->addDays(10)]);
        foreach (['unit', 'proses', 'temuan', 'alert', 'insiden', 'dokumen'] as $kind) {
            $this->as('viewer')->get("/commissioning/laporan/{$kind}.csv")->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
        $body = $this->as('viewer')->get('/commissioning/laporan/unit.csv')->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringContainsString($unit->code, $body);
    }
}
