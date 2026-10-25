<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    private function sidebar(string $html): string
    {
        preg_match('/<aside class="sidebar-desktop".*?<\/aside>/s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_sidebar_follows_the_hris_pattern(): void
    {
        $html = $this->sidebar($this->as('admin')->get('/commissioning')->getContent());

        $this->assertStringNotContainsString('sidebar-title', $html, 'logo saja, seperti HRIS');
        $this->assertStringNotContainsString('Menu Utama', $html);
        foreach (['Dashboard', 'Mulai Commissioning', 'Daftar Proses', 'Temuan', 'Insiden', 'Alert', 'Laporan', 'Unit', 'Checklist', 'Data Master'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_the_group_with_the_active_page_is_open_and_the_others_are_collapsed(): void
    {
        $home = $this->sidebar($this->as('admin')->get('/commissioning')->getContent());
        $this->assertSame(3, substr_count($home, 'sidebar-group-toggle collapsed'));
        $this->assertSame(0, substr_count($home, 'has-active'));

        $alerts = $this->sidebar($this->as('admin')->get('/commissioning/alert')->getContent());
        $this->assertSame(1, substr_count($alerts, 'has-active'));
        $this->assertSame(2, substr_count($alerts, 'sidebar-group-toggle collapsed'));

        $units = $this->sidebar($this->as('admin')->get('/commissioning/unit')->getContent());
        $this->assertSame(1, substr_count($units, 'has-active'));
    }

    public function test_menu_items_depend_on_the_level(): void
    {
        $this->assertStringContainsString('Mulai Commissioning', $this->sidebar($this->as('inspector')->get('/commissioning')->getContent()));
        $viewer = $this->sidebar($this->as('viewer')->get('/commissioning')->getContent());
        $this->assertStringNotContainsString('Mulai Commissioning', $viewer);
        $this->assertStringContainsString('Daftar Proses', $viewer);
        $this->assertStringNotContainsString('Mulai Commissioning', $this->sidebar($this->as('approver')->get('/commissioning')->getContent()));
    }

    public function test_declared_levels_are_exactly_the_ones_the_routes_use(): void
    {
        $declared = array_keys(config('sso.roles'));
        sort($declared);
        $used = [];
        foreach (app('router')->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'portal.role:')) {
                    $used = array_merge($used, explode(',', substr($mw, strlen('portal.role:'))));
                }
            }
        }
        $used = array_values(array_unique($used));
        sort($used);
        $this->assertSame($declared, $used);
    }

    public function test_header_has_a_back_button_on_inner_pages_but_not_on_the_dashboard_and_no_hero(): void
    {
        $unit = $this->unit();
        $home = $this->as('admin')->get('/commissioning')->getContent();
        $this->assertStringNotContainsString('aria-label="Kembali"', $home);
        $this->assertStringNotContainsString('class="hero', $home);
        $this->assertStringNotContainsString('Halo,', $home);

        $units = $this->as('admin')->get('/commissioning/unit')->getContent();
        $this->assertStringContainsString('aria-label="Kembali"', $units);
        $this->assertDoesNotMatchRegularExpression('/data-back\s+data-back-explicit/', $units, 'tanpa induk tetap: kembali ke halaman sebelumnya');

        $show = $this->as('admin')->get('/commissioning/unit/'.$unit->code)->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*\/commissioning\/unit"[^>]*data-back\s+data-back-explicit/', $show);

        $this->as('inspector')->post('/commissioning/proses', ['unit' => $unit->code]);
        $c = \App\Models\Commissioning::firstOrFail();
        $this->assertMatchesRegularExpression('/href="[^"]*\/commissioning\/proses"[^>]*data-back\s+data-back-explicit/', $this->as('admin')->get('/commissioning/proses/'.$c->number)->getContent());
    }
}
