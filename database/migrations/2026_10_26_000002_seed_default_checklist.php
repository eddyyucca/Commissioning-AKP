<?php

use App\Support\DefaultChecklist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Checklist standar sebagai titik awal; administrator menyesuaikannya di menu Checklist. */
    public function up(): void
    {
        if (DB::table('checklist_template_items')->exists()) {
            return;
        }

        $now = now();
        foreach (array_chunk(DefaultChecklist::rows(), 50) as $chunk) {
            DB::table('checklist_template_items')->insert(array_map(fn ($r) => [
                'scope' => $r['scope'], 'categories' => $r['categories'], 'reasons' => $r['reasons'],
                'group_name' => $r['group'], 'label' => $r['label'], 'is_critical' => $r['critical'],
                'reference' => $r['reference'], 'sort_order' => $r['sort_order'], 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
    }

    public function down(): void
    {
        DB::table('checklist_template_items')->delete();
    }
};
