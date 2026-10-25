<?php

namespace App\Support;

/**
 * Checklist standar commissioning / recommissioning. Ini USULAN berdasarkan praktik umum pertambangan; administrator
 * wajib menyesuaikannya dengan prosedur AKP dan manual pabrikan (menu Checklist).
 *
 * Tiap baris: [scope, kategori, alasan, kelompok, item, kritis, acuan]
 *   scope    : both | commissioning | recommissioning
 *   kategori : 'all' atau daftar kode dipisah koma (LV,HD,EX,CR,PW,BS,OT)
 *   alasan   : null (berlaku untuk semua) atau daftar alasan recommissioning dipisah koma
 *   kritis   : item gagal = unit tidak boleh dinyatakan laik sebelum diperbaiki
 */
class DefaultChecklist
{
    private const MOBILE = 'LV,HD,EX,CR,BS';

    /** @return array<int, array{scope:string, categories:string, reasons:?string, group:string, label:string, critical:bool, reference:?string}> */
    public static function rows(): array
    {
        $m = self::MOBILE;
        $r = [
            // Dokumen dan administrasi
            ['both', 'all', null, 'Dokumen dan administrasi', 'Identitas unit (nomor rangka/mesin/serial) sesuai dokumen', false],
            ['both', 'all', null, 'Dokumen dan administrasi', 'Dokumen kepemilikan / registrasi unit lengkap dan sesuai', false],
            ['both', 'all', null, 'Dokumen dan administrasi', 'Sertifikat laik operasi / hasil uji kelayakan (bila dipersyaratkan) tersedia dan berlaku', true],
            ['both', 'CR', null, 'Dokumen dan administrasi', 'Sertifikat uji kelayakan alat angkat dari pihak berwenang berlaku', true],
            ['both', 'all', null, 'Dokumen dan administrasi', 'Manual operasi dan perawatan pabrikan tersedia', false],
            ['both', 'all', null, 'Dokumen dan administrasi', 'Buku riwayat servis / log book tersedia dan terisi', false],
            ['both', 'all', null, 'Dokumen dan administrasi', 'Operator berlisensi (SIMPER) yang akan mengoperasikan unit sudah ditetapkan', true],

            // Kondisi fisik dan struktur
            ['both', 'all', null, 'Kondisi fisik dan struktur', 'Rangka / chassis / struktur tidak retak, bengkok, atau berkarat parah', true],
            ['both', 'HD,EX,CR', null, 'Kondisi fisik dan struktur', 'Tangga, pijakan, dan pegangan (handrail) kokoh dan anti selip', true],
            ['both', $m, null, 'Kondisi fisik dan struktur', 'Sabuk pengaman (seat belt) lengkap dan berfungsi', true],
            ['both', 'HD,EX', null, 'Kondisi fisik dan struktur', 'Struktur pelindung ROPS/FOPS utuh dan tidak dimodifikasi', true],
            ['both', $m, null, 'Kondisi fisik dan struktur', 'Kaca, spion, dan wiper dalam kondisi baik', false],
            ['both', 'LV,HD,BS,CR', null, 'Kondisi fisik dan struktur', 'Ban/roda: kondisi, tekanan angin, dan baut roda sesuai standar', true],
            ['both', 'EX', null, 'Kondisi fisik dan struktur', 'Undercarriage / track dan roller dalam batas keausan', true],
            ['both', 'all', null, 'Kondisi fisik dan struktur', 'Kabin / area unit bersih dan tidak ada barang lepas', false],

            // Sistem keselamatan
            ['both', $m, null, 'Sistem keselamatan', 'Rem servis berfungsi baik', true],
            ['both', $m, null, 'Sistem keselamatan', 'Rem parkir berfungsi baik', true],
            ['both', 'HD', null, 'Sistem keselamatan', 'Rem sekunder / darurat (retarder) berfungsi', true],
            ['both', $m, null, 'Sistem keselamatan', 'Klakson dan alarm mundur berfungsi', true],
            ['both', 'LV,HD,BS', null, 'Sistem keselamatan', 'Lampu utama, lampu rem, lampu sein, dan lampu mundur berfungsi', true],
            ['both', 'EX,CR', null, 'Sistem keselamatan', 'Lampu kerja dan lampu penerangan unit berfungsi', false],
            ['both', $m, null, 'Sistem keselamatan', 'Lampu putar (rotator / strobe) berfungsi', true],
            ['both', 'LV,BS', null, 'Sistem keselamatan', 'Bendera pengaman (whip flag) terpasang', true],
            ['both', 'HD,EX,CR,PW', null, 'Sistem keselamatan', 'Tombol henti darurat (emergency stop) berfungsi saat diuji', true],
            ['both', 'all', null, 'Sistem keselamatan', 'APAR tersedia, terisi, segel utuh, dan masa berlaku belum habis', true],
            ['both', 'HD,EX', null, 'Sistem keselamatan', 'Sistem pemadam kebakaran otomatis (bila terpasang) berfungsi', true],
            ['both', 'LV,HD,BS', null, 'Sistem keselamatan', 'Kotak P3K tersedia dan lengkap', false],
            ['both', 'LV,HD,EX,BS', null, 'Sistem keselamatan', 'Segitiga pengaman dan pengganjal roda (wheel chock) tersedia', false],
            ['both', $m, null, 'Sistem keselamatan', 'Radio komunikasi berfungsi dan tanda panggil unit sesuai', true],
            ['both', 'LV,HD,EX,BS', null, 'Sistem keselamatan', 'Pelacak / fleet management / GPS terpasang dan aktif (bila dipersyaratkan)', false],
            ['both', 'LV,HD,EX', null, 'Sistem keselamatan', 'Kamera, sensor proximity, atau pendeteksi kelelahan operator (bila dipersyaratkan) berfungsi', false],
            ['both', 'LV,HD,BS', null, 'Sistem keselamatan', 'Pembatas / alarm kecepatan (bila dipersyaratkan) berfungsi', false],
            ['both', $m, null, 'Sistem keselamatan', 'Nomor lambung dan stiker reflektif identitas unit terpasang', false],
            ['both', 'PW', null, 'Sistem keselamatan', 'Pembumian (grounding) dan pengaman arus bocor terpasang dan baik', true],
            ['both', 'PW,CR', null, 'Sistem keselamatan', 'Pelindung bagian bergerak (guard) terpasang', true],

            // Mesin dan fluida
            ['both', 'all', null, 'Mesin dan fluida', 'Level oli mesin, oli hidrolik, coolant, dan BBM sesuai', false],
            ['both', 'all', null, 'Mesin dan fluida', 'Tidak ada kebocoran oli, BBM, coolant, atau cairan hidrolik', true],
            ['both', 'all', null, 'Mesin dan fluida', 'Aki / baterai terpasang kuat dan terminal bersih', false],
            ['both', 'all', null, 'Mesin dan fluida', 'Sabuk kipas, selang radiator, dan pendingin dalam kondisi baik', false],
            ['both', 'all', null, 'Mesin dan fluida', 'Saluran buang (exhaust) tidak bocor dan ada pelindung panas', false],

            // Kelistrikan dan instrumen
            ['both', $m, null, 'Kelistrikan dan instrumen', 'Panel instrumen normal dan tidak ada kode gangguan (fault) aktif', true],
            ['both', 'all', null, 'Kelistrikan dan instrumen', 'Kabel dan konektor tidak terkelupas atau terbuka', false],

            // Hidrolik dan attachment
            ['both', 'HD,EX,CR', null, 'Hidrolik dan attachment', 'Selang dan silinder hidrolik tidak retak atau bocor', true],
            ['both', 'HD,EX', null, 'Hidrolik dan attachment', 'Bucket / blade / bak dump dan attachment tanpa retak; pin dan bushing normal', true],
            ['both', 'CR', null, 'Hidrolik dan attachment', 'Wire rope, hook, safety latch, dan sling dalam kondisi baik', true],
            ['both', 'CR', null, 'Hidrolik dan attachment', 'Limit switch, indikator beban, dan outrigger berfungsi', true],

            // Uji fungsi
            ['both', 'all', null, 'Uji fungsi', 'Uji start dan idle minimal 15 menit tanpa gangguan', true],
            ['both', $m, null, 'Uji fungsi', 'Uji pengereman (statis dan dinamis) memenuhi standar', true],
            ['both', $m, null, 'Uji fungsi', 'Uji kemudi (steering) berfungsi baik', true],
            ['both', 'HD,EX,CR', null, 'Uji fungsi', 'Uji fungsi hidrolik (dump / boom / swing) berjalan normal', true],
            ['both', 'CR', null, 'Uji fungsi', 'Uji beban (load test) sesuai kapasitas', true],
            ['both', 'PW', null, 'Uji fungsi', 'Tegangan dan frekuensi keluaran stabil; breaker pengaman bekerja', true],
            ['both', 'all', null, 'Uji fungsi', 'Kebisingan dan emisi dalam batas yang ditetapkan site', false],

            // Serah terima
            ['both', 'all', null, 'Serah terima', 'Operator mendapat briefing keselamatan dan familiarisasi unit', false],
            ['both', 'all', null, 'Serah terima', 'Data unit tercatat dan stiker / tanda laik operasi siap ditempel', false],

            // Khusus recommissioning (per alasan)
            ['recommissioning', 'all', 'berkala', 'Khusus: berkala', 'Temuan dari commissioning sebelumnya sudah tertutup', true],
            ['recommissioning', 'all', 'berkala', 'Khusus: berkala', 'Riwayat servis sejak commissioning terakhir sudah ditinjau', false],
            ['recommissioning', 'all', 'insiden', 'Khusus: setelah insiden', 'Penyebab insiden dan rekomendasi investigasi sudah ditindaklanjuti', true],
            ['recommissioning', 'all', 'insiden', 'Khusus: setelah insiden', 'Semua komponen yang rusak akibat insiden sudah diperbaiki atau diganti', true],
            ['recommissioning', 'all', 'insiden', 'Khusus: setelah insiden', 'Pemeriksaan tidak merusak (NDT) pada struktur terdampak dilakukan bila diperlukan', false],
            ['recommissioning', 'all', 'perbaikan_besar', 'Khusus: setelah perbaikan besar', 'Laporan perbaikan dan daftar komponen yang diganti tersedia', false],
            ['recommissioning', 'all', 'perbaikan_besar', 'Khusus: setelah perbaikan besar', 'Komponen pengganti sesuai spesifikasi pabrikan (asli atau setara yang disetujui)', true],
            ['recommissioning', 'all', 'perbaikan_besar', 'Khusus: setelah perbaikan besar', 'Uji ulang sistem yang diperbaiki memberi hasil baik', true],
            ['recommissioning', 'all', 'modifikasi', 'Khusus: setelah modifikasi', 'Modifikasi disetujui tertulis oleh engineering / pabrikan', true],
            ['recommissioning', 'all', 'modifikasi', 'Khusus: setelah modifikasi', 'Gambar dan dokumen unit sudah diperbarui sesuai modifikasi', false],
            ['recommissioning', 'all', 'modifikasi', 'Khusus: setelah modifikasi', 'Uji fungsi / uji beban setelah modifikasi memberi hasil baik', true],
            ['recommissioning', 'all', 'idle', 'Khusus: setelah idle lama', 'Oli, ban, aki, seal, dan korosi diperiksa setelah lama tidak dipakai', true],
            ['recommissioning', 'all', 'idle', 'Khusus: setelah idle lama', 'Mesin dihidupkan bertahap dan diuji tanpa beban', false],
            ['recommissioning', 'all', 'relokasi', 'Khusus: pindah area', 'Unit diperiksa terhadap kondisi area baru (jalan, tanjakan, beban)', false],
            ['recommissioning', 'all', 'relokasi', 'Khusus: pindah area', 'Registrasi area / site baru sudah tercatat', false],
            ['recommissioning', 'all', 'kepemilikan', 'Khusus: pergantian pemilik', 'Dokumen serah terima kepemilikan lengkap', false],
            ['recommissioning', 'all', 'kepemilikan', 'Khusus: pergantian pemilik', 'Operator baru dilatih dan memiliki SIMPER yang sesuai', true],
        ];

        $out = [];
        foreach ($r as $i => [$scope, $cats, $reasons, $group, $label, $critical]) {
            $out[] = [
                'scope' => $scope, 'categories' => $cats, 'reasons' => $reasons, 'group' => $group,
                'label' => $label, 'critical' => $critical, 'reference' => null, 'sort_order' => ($i + 1) * 10,
            ];
        }

        return $out;
    }
}
