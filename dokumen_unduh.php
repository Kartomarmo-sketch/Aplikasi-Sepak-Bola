<?php
declare(strict_types=1);

// Unduh / lihat dokumen karyawan. Hanya admin atau pemilik dokumen.
// ?id=ID[&lihat=1]  -> lihat=1 membuka di browser (PDF/gambar), selain itu diunduh.

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
try {
    $st = db()->prepare('SELECT * FROM dokumen_karyawan WHERE id = ?');
    $st->execute([(int) $id]);
    $dok = $st->fetch();
} catch (PDOException $ex) {
    error_log('Dokumen gagal dimuat: ' . $ex->getMessage());
    http_response_code(500);
    exit('Dokumen belum dapat dibuka.');
}

$boleh = $dok && (($_SESSION['role'] ?? '') === 'admin' || (int) $dok['user_id'] === (int) $_SESSION['user_id']);
// Jalur harus persis di folder dokumen (cegah path traversal) dan berkasnya ada.
$jalur = $dok ? (string) $dok['jalur'] : '';
$berkas = __DIR__ . '/' . $jalur;
if (!$boleh || !preg_match('#^uploads/dokumen/[A-Za-z0-9_.-]+$#', $jalur) || !is_file($berkas)) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

catat_aktivitas('unduh', 'dokumen_karyawan', (int) $dok['id'], (string) $dok['judul']);

$nama = (string) $dok['nama_berkas'];
$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nama) ?: 'dokumen';
header('Content-Type: ' . $dok['mime']);
header('Content-Length: ' . filesize($berkas));
header('Content-Disposition: ' . (isset($_GET['lihat']) ? 'inline' : 'attachment')
    . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($berkas);
