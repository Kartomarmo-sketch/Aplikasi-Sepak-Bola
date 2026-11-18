<?php
declare(strict_types=1);

// Dashboard per peran:
//   admin          -> inc_dasbor_admin.php  (tenaga kerja, kontrak, gaji, pengajuan, aktivitas, pengumuman)
//   pelatih_medis  -> inc_dasbor_staf.php   (ketersediaan skuad, kebugaran, cedera, performa)
//   pemain         -> inc_dasbor_pemain.php (profil, statistik, kontrak, slip, kebugaran, pengajuan)
// POST (admin): kelola pengumuman klub.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/lib_dasbor.php';

start_secure_session();
require_login();

$role = (string) $_SESSION['role'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    proses_pengumuman();
}

halaman_awal('Dashboard', 'Beranda');
match ($role) {
    'admin' => (static function (): void {
        require_once __DIR__ . '/admin_lib.php';
        require __DIR__ . '/inc_dasbor_admin.php';
    })(),
    'pelatih_medis' => (static function (): void { require __DIR__ . '/inc_dasbor_staf.php'; })(),
    default => (static function (): void { require __DIR__ . '/inc_dasbor_pemain.php'; })(),
};
halaman_akhir();
