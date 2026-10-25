<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CommissioningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// Semua halaman sudah wajib login Portal (config/sso.php, protect_all). Level akses:
//   admin     : semuanya, termasuk unit, checklist, dan semua tahap proses
//   approver  : memberi keputusan akhir laik operasi
//   safety    : verifikasi Safety, kelola temuan dan insiden, tutup temuan
//   workshop  : verifikasi Workshop, kerjakan temuan
//   inspector : membuat dan mengisi commissioning, lalu mengajukannya
//   viewer    : hanya melihat
$all = 'admin,approver,safety,workshop,inspector,viewer';

Route::redirect('/', '/commissioning')->name('home');

// ---- Melihat (semua level) ---------------------------------------------------------------------------------------
Route::middleware(['portal.role:'.$all, 'throttle:120,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::get('/', [DashboardController::class, 'home'])->name('home');

    Route::get('/unit', [UnitController::class, 'index'])->name('units');
    Route::get('/proses', [CommissioningController::class, 'index'])->name('index');
    Route::get('/temuan', [FindingController::class, 'index'])->name('findings');
    Route::get('/insiden', [IncidentController::class, 'index'])->name('incidents');
    Route::get('/alert', [AlertController::class, 'index'])->name('alerts');
    Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist');

    Route::get('/laporan', [ReportController::class, 'index'])->name('report');
    Route::get('/laporan/unit.csv', [ReportController::class, 'units'])->name('report.units');
    Route::get('/laporan/proses.csv', [ReportController::class, 'commissionings'])->name('report.commissionings');
    Route::get('/laporan/temuan.csv', [ReportController::class, 'findings'])->name('report.findings');
    Route::get('/laporan/alert.csv', [ReportController::class, 'alerts'])->name('report.alerts');
    Route::get('/laporan/insiden.csv', [ReportController::class, 'incidents'])->name('report.incidents');
    Route::get('/laporan/dokumen.csv', [ReportController::class, 'documents'])->name('report.documents');
});

// Rute tetap ("baru", "impor") dideklarasikan sebelum rute dengan parameter agar tidak tertangkap sebagai kode/nomor.
Route::middleware(['portal.role:admin', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::get('/unit/baru', [UnitController::class, 'create'])->name('units.create');
    Route::post('/unit', [UnitController::class, 'store'])->name('units.store');
    Route::get('/unit/impor', [UnitController::class, 'importForm'])->name('units.import');
    Route::get('/unit/impor/contoh.csv', [UnitController::class, 'importTemplate'])->name('units.import.template');
    Route::post('/unit/impor', [UnitController::class, 'import'])->name('units.import.store');
    Route::get('/unit/{unit:code}/ubah', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/unit/{unit:code}', [UnitController::class, 'update'])->name('units.update');
    Route::post('/unit/{unit:code}/aktif', [UnitController::class, 'toggle'])->name('units.toggle');

    Route::post('/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
    Route::put('/checklist/{item}', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::post('/checklist/{item}/aktif', [ChecklistController::class, 'toggle'])->name('checklist.toggle');
});

Route::middleware(['portal.role:'.$all, 'throttle:120,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::get('/unit/{unit:code}', [UnitController::class, 'show'])->name('units.show');
    Route::get('/foto/{item}', [CommissioningController::class, 'photo'])->name('photo');
});

// ---- Membuat dan mengisi proses ----------------------------------------------------------------------------------
Route::middleware(['portal.role:admin,inspector,safety,workshop', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::get('/proses/baru', [CommissioningController::class, 'create'])->name('create');
    Route::post('/proses', [CommissioningController::class, 'store'])->name('store');
    Route::put('/proses/{commissioning}/jawaban', [CommissioningController::class, 'answers'])->name('answers');
    Route::post('/proses/{commissioning}/batal', [CommissioningController::class, 'cancel'])->name('cancel');
});

Route::middleware(['portal.role:'.$all, 'throttle:120,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::get('/proses/{commissioning}', [CommissioningController::class, 'show'])->name('show');
    Route::get('/proses/{commissioning}/lembar', [CommissioningController::class, 'certificate'])->name('certificate');
});

// ---- Verifikasi dan keputusan ------------------------------------------------------------------------------------
Route::middleware(['portal.role:admin,safety,workshop', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/proses/{commissioning}/verifikasi/{stage}', [CommissioningController::class, 'verify'])->name('verify');
    Route::post('/unit/{unit:code}/idle', [UnitController::class, 'markIdle'])->name('units.idle');
    Route::delete('/unit/{unit:code}/idle', [UnitController::class, 'clearIdle'])->name('units.idle.clear');
    Route::post('/unit/{unit:code}/dokumen', [UnitController::class, 'storeDocument'])->name('units.docs.store');
    Route::delete('/unit/{unit:code}/dokumen/{document}', [UnitController::class, 'destroyDocument'])->name('units.docs.destroy');
    Route::put('/temuan/{finding}', [FindingController::class, 'update'])->name('findings.update');
});

Route::middleware(['portal.role:admin,safety,workshop,approver', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/proses/{commissioning}/kembalikan', [CommissioningController::class, 'sendBack'])->name('sendback');
});

Route::middleware(['portal.role:admin,approver', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/proses/{commissioning}/setujui', [CommissioningController::class, 'approve'])->name('approve');
});

Route::middleware(['portal.role:admin,safety', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/temuan/{finding}/tutup', [FindingController::class, 'close'])->name('findings.close');
    Route::post('/alert/pindai', [AlertController::class, 'scan'])->name('alerts.scan');
});

// ---- Insiden dan alert -------------------------------------------------------------------------------------------
Route::middleware(['portal.role:admin,safety,workshop,inspector', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/insiden', [IncidentController::class, 'store'])->name('incidents.store');
});

Route::middleware(['portal.role:admin,approver,safety,workshop,inspector', 'throttle:60,1'])->prefix('commissioning')->name('cms.')->group(function () {
    Route::post('/alert/semua-dilihat', [AlertController::class, 'acknowledgeAll'])->name('alerts.ackall');
    Route::post('/alert/{alert}/dilihat', [AlertController::class, 'acknowledge'])->name('alerts.ack');
});
