<?php

/*
 * Login terpusat lewat AKPSatu (Portal). Nilai PORTAL_* diisi di .env.
 *
 * Aplikasi ini tidak punya akun sendiri: identitas dan level datang dari token Portal.
 * Kode level (kiri) dipakai di routes/web.php: ->middleware('portal.role:admin,safety').
 * Jangan ubah kode setelah ada pengguna yang memakainya. Portal membacanya otomatis (Modul > Atur > Deteksi level).
 */
return [
    'portal_url' => env('PORTAL_URL', 'http://localhost:8000'),

    'app_code' => env('PORTAL_APP_CODE'),

    'secret' => env('PORTAL_SSO_SECRET'),

    'leeway' => 5,

    'max_session_minutes' => (int) env('PORTAL_MAX_SESSION_MINUTES', 480),

    // Semua halaman wajib login Portal; hanya /up (cek kesehatan Docker) yang terbuka.
    'protect_all' => true,

    'except' => ['up'],

    'roles' => [
        'admin' => ['Administrator', 'Mengelola unit, checklist, dan semua data commissioning; bisa melakukan semua tahap.'],
        'approver' => ['Penyetuju (Superintendent)', 'Memberi keputusan akhir laik operasi setelah verifikasi Safety dan Workshop lengkap.'],
        'safety' => ['Petugas Safety (HSE)', 'Memverifikasi aspek keselamatan, mengelola temuan dan insiden, serta menutup temuan.'],
        'workshop' => ['Petugas Workshop / Teknik', 'Memverifikasi aspek teknis unit dan mengerjakan perbaikan temuan.'],
        'inspector' => ['Pemeriksa', 'Membuat dan mengisi commissioning / recommissioning lalu mengajukannya.'],
        'viewer' => ['Hanya lihat', 'Melihat dashboard, status unit, dan semua data tanpa bisa mengubahnya.'],
    ],

    'home' => '/commissioning',
];
