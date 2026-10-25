<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Unit;
use App\Services\DashboardData;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_dashboard_renders_with_a_hint(): void
    {
        $this->as('admin')->get('/commissioning')->assertOk()->assertSee('Belum ada unit aktif')->assertSee('Tambah unit');
        $this->as('viewer')->get('/commissioning')->assertOk()->assertDontSee('Tambah unit');
    }

    public function test_kpis_count_units_by_operational_status(): void
    {
        $this->unit(['commissioned_until' => today()->addDays(100)]);
        $this->unit(['commissioned_until' => today()->addDays(10)]);
        $this->unit(['commissioned_until' => today()->subDays(2)]);
        $this->unit(['commissioned_until' => today()->addDays(100), 'hold_since' => now()]);
        $this->unit();

        $d = app(DashboardData::class)->build(null, null, 180);

        $this->assertSame(5, $d['kpi']['total']);
        $this->assertSame(2, $d['kpi']['fit']);
        $this->assertSame(1, $d['kpi']['expiring']);
        $this->assertSame(1, $d['kpi']['expired']);
        $this->assertSame(1, $d['kpi']['hold']);
        $this->assertSame(1, $d['kpi']['not_commissioned']);
        $this->assertSame(40, $d['kpi']['compliance']);
        $this->assertSame(array_sum(array_column($d['chart']['status'], 'n')), 5);
    }

    public function test_filters_limit_the_numbers(): void
    {
        $this->unit(['category' => 'HD', 'area' => 'Pit A', 'commissioned_until' => today()->addDays(100)]);
        $this->unit(['category' => 'LV', 'area' => 'Pit B', 'commissioned_until' => today()->addDays(100)]);
        $this->unit(['category' => 'LV', 'area' => null]);

        $svc = app(DashboardData::class);
        $this->assertSame(3, $svc->build(null, null, 90)['kpi']['total']);
        $this->assertSame(2, $svc->build('LV', null, 90)['kpi']['total']);
        $this->assertSame(1, $svc->build(null, 'Pit A', 90)['kpi']['total']);
        $this->assertSame(1, $svc->build(null, '__none', 90)['kpi']['total']);
        $this->as('viewer')->get('/commissioning?kategori=LV&hari=365&area=Pit+B')->assertOk()->assertSee('Pit B');
        $this->as('viewer')->get('/commissioning?kategori=ZZ&hari=7')->assertOk();
    }

    public function test_dashboard_with_demo_data_renders_for_every_level_and_exposes_chart_data(): void
    {
        $this->seed(DemoSeeder::class);
        $this->assertGreaterThan(30, Unit::count());
        $this->assertGreaterThan(0, Alert::active()->count());

        foreach (['admin', 'approver', 'safety', 'workshop', 'inspector', 'viewer'] as $role) {
            $html = $this->as($role)->get('/commissioning')->assertOk()->getContent();
            foreach (['chartStatus', 'chartCategory', 'chartMonthly', 'chartExpiry', 'chartAlertType', 'Kepatuhan commissioning armada'] as $needle) {
                $this->assertStringContainsString($needle, $html, "{$role}: {$needle}");
            }
        }

        $d = app(DashboardData::class)->build(null, null, 365);
        $this->assertCount(12, $d['chart']['monthly']['labels']);
        $this->assertCount(7, $d['chart']['expiry']['data']);
        $this->assertNotEmpty($d['chart']['topItems']);
        $this->assertNotEmpty($d['chart']['byArea']);
        $this->assertSame(Unit::count(), $d['kpi']['total']);
        $this->assertGreaterThan(0, $d['kpi']['hold']);
    }

    public function test_queue_depends_on_the_level(): void
    {
        $this->seed(DemoSeeder::class);
        $svc = app(DashboardData::class);
        $make = fn (string $role, string $id = '99') => \Akp\PortalSso\PortalUser::fromArray(['id' => $id, 'name' => 'x', 'role' => $role, 'level' => 10, 'is_admin' => false]);

        $all = $svc->build(null, null, 180, $make('admin'))['queue']->count();
        $this->assertGreaterThan(0, $all);
        $this->assertSame(0, $svc->build(null, null, 180, $make('viewer'))['queue']->count());
        $safety = $svc->build(null, null, 180, $make('safety'))['queue'];
        $this->assertTrue($safety->every(fn ($c) => $c->status === 'submitted' && ! $c->safety_at));
        $approver = $svc->build(null, null, 180, $make('approver'))['queue'];
        $this->assertTrue($approver->every(fn ($c) => $c->bothVerified()));
        $own = $svc->build(null, null, 180, $make('inspector', '2'))['queue'];
        $this->assertTrue($own->every(fn ($c) => $c->requested_by_id === '2'));
    }

    public function test_demo_seeder_refuses_to_run_twice(): void
    {
        $this->seed(DemoSeeder::class);
        $n = Unit::count();
        $this->seed(DemoSeeder::class);
        $this->assertSame($n, Unit::count());
    }
}
