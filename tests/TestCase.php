<?php

namespace Tests;

use App\Models\Commissioning;
use App\Models\Unit;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Akp\PortalSso\Testing\ActsAsPortalUser;

    /** Masuk sebagai pengguna Portal dengan level tertentu. */
    protected function as(string $role, string $id = '2', string $name = 'Budi'): static
    {
        return $this->loginPortal(['id' => $id, 'name' => $name, 'role' => $role, 'level' => 10, 'employee_no' => 'N'.$id]);
    }

    protected function unit(array $attributes = []): Unit
    {
        static $n = 0;
        $n++;

        return Unit::create($attributes + ['code' => 'U-'.$n, 'name' => 'Unit uji '.$n, 'category' => 'HD', 'owner' => 'AKP', 'is_active' => true]);
    }

    /** Mengisi seluruh checklist dengan jawaban yang sama (kecuali yang ditentukan) lewat HTTP, sebagai pemohon. */
    protected function fill(Commissioning $c, string $result = 'ok', array $overrides = [], string $role = 'inspector', string $id = '2', string $action = 'save'): \Illuminate\Testing\TestResponse
    {
        $results = [];
        $notes = [];
        foreach ($c->items()->get() as $item) {
            $results[$item->id] = $overrides[$item->id] ?? $result;
            if (($overrides[$item->id] ?? $result) === 'bad') {
                $notes[$item->id] = 'Ada kerusakan';
            }
        }

        return $this->as($role, $id)->put("/commissioning/proses/{$c->number}/jawaban", ['result' => $results, 'note' => $notes, 'action' => $action]);
    }

    /** Alur lengkap sampai siap diputuskan: dibuat -> diisi -> diajukan -> verifikasi Safety dan Workshop. */
    protected function readyForDecision(Unit $unit, string $result = 'ok', array $overrides = []): Commissioning
    {
        $this->as('inspector', '2')->post('/commissioning/proses', ['unit' => $unit->code, 'reason' => 'berkala'])->assertRedirect();
        $c = Commissioning::query()->where('unit_id', $unit->id)->latest('id')->firstOrFail();
        $this->fill($c, $result, $overrides, action: 'submit');
        $this->as('safety', '3', 'Sari')->post("/commissioning/proses/{$c->number}/verifikasi/safety", ['notes' => 'aman']);
        $this->as('workshop', '4', 'Wawan')->post("/commissioning/proses/{$c->number}/verifikasi/workshop", ['notes' => 'baik']);

        return $c->refresh();
    }
}
