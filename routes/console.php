<?php

use App\Services\AlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Memindai ulang semua alert (masa berlaku, dokumen, temuan, hold, idle). Dijalankan tiap jam oleh kontainer penjadwal
// (php artisan schedule:work); halaman juga memicunya secara berkala saat dibuka.
Artisan::command('commissioning:scan', function (AlertService $alerts) {
    $r = $alerts->scan();
    $this->info("Alert aktif {$r['active']}, baru {$r['created']}, selesai {$r['resolved']}.");
})->purpose('Pindai ulang alert unit dan alat');

Schedule::command('commissioning:scan')->hourly()->withoutOverlapping();
