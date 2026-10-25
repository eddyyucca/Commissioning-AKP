<?php

return [
    // Kategori unit. Kode dipakai di checklist (kolom categories) dan di data unit; jangan diubah setelah ada data.
    'categories' => [
        'LV' => 'Kendaraan ringan (LV)',
        'HD' => 'Dump truck / haul truck',
        'EX' => 'Alat berat (excavator, loader, dozer, grader)',
        'CR' => 'Alat angkat (crane, forklift)',
        'PW' => 'Genset, lighting tower, pompa',
        'BS' => 'Bus / kendaraan penumpang',
        'OT' => 'Lainnya',
    ],

    // Alasan recommissioning (wajib dipilih saat membuat recommissioning).
    'reasons' => [
        'berkala' => 'Perpanjangan masa berlaku (berkala)',
        'insiden' => 'Setelah insiden / kecelakaan',
        'perbaikan_besar' => 'Setelah perbaikan besar / overhaul',
        'modifikasi' => 'Setelah modifikasi unit',
        'idle' => 'Setelah idle (tidak dipakai) lama',
        'relokasi' => 'Pindah area / site',
        'kepemilikan' => 'Pergantian pemilik / kontraktor',
    ],

    // Masa berlaku hasil commissioning (bulan). Sesuaikan dengan prosedur AKP; bisa dibedakan per kategori.
    'validity_months' => [
        'default' => 12,
        'per_category' => [
            // 'CR' => 6,
        ],
    ],

    // Ambang peringatan (hari).
    'expiring_days' => 30,            // masa berlaku commissioning hampir habis
    'critical_expiring_days' => 7,    // di bawah ini peringatan naik menjadi Tinggi
    'doc_expiring_days' => 30,        // dokumen unit hampir kedaluwarsa
    'approval_sla_days' => 3,         // proses diajukan lebih lama dari ini dianggap terlambat
    'idle_days' => 30,                // unit idle lebih lama dari ini wajib recommissioning sebelum dipakai

    // Jenis dokumen unit yang dipantau masa berlakunya.
    'document_types' => [
        'registrasi' => 'Registrasi / kepemilikan',
        'laik_operasi' => 'Sertifikat laik operasi',
        'kir' => 'Uji berkala / KIR',
        'asuransi' => 'Asuransi',
        'apar' => 'Sertifikat APAR',
        'kalibrasi' => 'Kalibrasi alat ukur',
        'lainnya' => 'Lainnya',
    ],

    // Jenis kejadian pada unit. Yang bertanda true otomatis menahan unit (hold) sampai recommissioning disetujui.
    'incident_kinds' => [
        'kecelakaan' => ['Kecelakaan / insiden', true],
        'kerusakan_besar' => ['Kerusakan besar', true],
        'overhaul' => ['Overhaul / perbaikan besar', true],
        'modifikasi' => ['Modifikasi unit', true],
        'near_miss' => ['Hampir celaka (near miss)', false],
        'relokasi' => ['Pindah area / site', false],
        'lainnya' => ['Lainnya', false],
    ],
    'incident_severities' => [
        'ringan' => 'Ringan',
        'sedang' => 'Sedang',
        'berat' => 'Berat',
        'fatal' => 'Fatal',
    ],

    // Foto bukti per item checklist (opsional).
    'photo_max_kb' => 6144,
    'photo_max_px' => 1280,
    'photo_disk' => 'local',

    // Pemindaian alert dijalankan paling sering sekali dalam sekian detik saat halaman dibuka.
    'alert_scan_ttl' => 300,
];
