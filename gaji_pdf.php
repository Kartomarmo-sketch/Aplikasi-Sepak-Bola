<?php
declare(strict_types=1);

// Unduh slip gaji sebagai PDF. Hanya pemilik slip (atau admin/HR) dan bukan draf.

require __DIR__ . '/tampilan.php';
require __DIR__ . '/lib_gaji.php';
require __DIR__ . '/pdf_sederhana.php';

start_secure_session();
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$uid = (int) $_SESSION['user_id'];
$admin = ($_SESSION['role'] ?? '') === 'admin';

try {
    $stmt = db()->prepare(
        "SELECT p.*, k.nomor_kontrak FROM penggajian p LEFT JOIN kontrak k ON k.id = p.kontrak_id
          WHERE p.id = :id AND p.status <> 'draft'"
    );
    $stmt->execute([':id' => (int) $id]);
    $slip = $stmt->fetch();
    // Slip orang lain diperlakukan sama dengan slip yang tidak ada (tidak membocorkan keberadaannya).
    if (!$id || !$slip || ((int) $slip['user_id'] !== $uid && !$admin)) {
        http_response_code(404);
        exit('Slip gaji tidak ditemukan.');
    }
    $r = susun_rincian($slip, rincian_slip([(int) $slip['id']])[(int) $slip['id']] ?? []);
    $orang = data_karyawan((int) $slip['user_id']);
} catch (PDOException $ex) {
    error_log('PDF slip gagal: ' . $ex->getMessage());
    http_response_code(500);
    exit('Slip gaji belum dapat dibuat. Coba lagi nanti.');
}

$hijau = [8, 122, 27];
$hijauTua = [6, 86, 26];
$teks = [20, 32, 26];
$redup = [90, 106, 97];
$merah = [200, 40, 40];
$garis = [219, 228, 221];
$kiri = 48.0;
$kanan = 547.0;

$pdf = new PdfSederhana();

// Kepala
$pdf->kotak(0, 0, 595.28, 112, $hijauTua);
$pdf->kotak(0, 112, 595.28, 4, [255, 213, 74]);
$pdf->teks($kiri, 52, NAMA_KLUB, 19, true, [255, 255, 255]);
$pdf->teks($kiri, 74, 'SLIP GAJI KARYAWAN · RAHASIA', 9, false, [214, 240, 220]);
$pdf->teks($kanan, 46, 'Periode', 9, false, [214, 240, 220], 'kanan');
$pdf->teks($kanan, 66, bulan_tahun((string) $slip['periode']), 16, true, [255, 255, 255], 'kanan');
$pdf->teks($kanan, 86, nomor_slip($slip), 9, false, [214, 240, 220], 'kanan');

// Identitas
$y = 150;
$info = [
    ['Nama', (string) $orang['nama_lengkap'], 'Nomor kontrak', (string) ($slip['nomor_kontrak'] ?? '-')],
    ['ID karyawan', (string) $orang['id_karyawan'], 'Rekening', (string) ($slip['rekening_tujuan'] ?? '-')],
    ['Jabatan', (string) $orang['jabatan_teks'], 'Tanggal bayar', $slip['tanggal_bayar'] ? tanggal_id((string) $slip['tanggal_bayar'], true) : 'Belum dibayar'],
];
foreach ($info as [$l1, $v1, $l2, $v2]) {
    $pdf->teks($kiri, $y, $l1, 8.5, false, $redup);
    $pdf->teks($kiri + 80, $y, $v1, 10, true, $teks);
    $pdf->teks(310, $y, $l2, 8.5, false, $redup);
    $pdf->teks(390, $y, $v2, 10, true, $teks);
    $y += 20;
}
$status = LABEL_STATUS_SLIP[$slip['status']][0] ?? $slip['status'];
$pdf->kotak($kanan - 70, 128, 70, 18, [227, 244, 230]);
$pdf->teks($kanan - 35, 141, strtoupper($status), 8.5, true, $hijau, 'tengah');

// Tabel pendapatan & potongan berdampingan
$y = 232;
$kolom = [
    ['Pendapatan', $r['pendapatan'], $kiri, 287.0, 'Total bruto', $r['bruto'], $teks],
    ['Potongan', $r['potongan'], 308.0, $kanan, 'Total potongan', $r['total_potongan'], $merah],
];
$yAkhir = $y;
foreach ($kolom as [$judul, $baris, $x1, $x2, $lblTotal, $total, $warnaTotal]) {
    $yy = $y;
    $pdf->kotak($x1, $yy - 14, $x2 - $x1, 22, [238, 243, 239]);
    $pdf->teks($x1 + 8, $yy, strtoupper($judul), 9, true, $hijauTua);
    $pdf->teks($x2 - 8, $yy, 'JUMLAH (Rp)', 8, true, $redup, 'kanan');
    $yy += 24;
    foreach ($baris as $b) {
        $pdf->teks($x1 + 8, $yy, (string) $b['keterangan'], 9.5, false, $teks);
        $pdf->teks($x2 - 8, $yy, number_format((float) $b['jumlah'], 0, ',', '.'), 9.5, false, $teks, 'kanan');
        $pdf->garis($x1, $yy + 7, $x2, $yy + 7, $garis, 0.6);
        $yy += 20;
    }
    $yAkhir = max($yAkhir, $yy);
}
// Baris total sejajar di bawah kolom terpanjang.
foreach ($kolom as [$judul, , $x1, $x2, $lblTotal, $total, $warnaTotal]) {
    $pdf->garis($x1, $yAkhir - 4, $x2, $yAkhir - 4, [180, 192, 184], 1);
    $pdf->teks($x1 + 8, $yAkhir + 10, $lblTotal, 10, true, $teks);
    $pdf->teks($x2 - 8, $yAkhir + 10, number_format($total, 0, ',', '.'), 10, true, $warnaTotal, 'kanan');
}

// Gaji bersih
$y = $yAkhir + 40;
$pdf->kotak($kiri, $y, $kanan - $kiri, 78, [227, 244, 230]);
$pdf->kotak($kiri, $y, 4, 78, $hijau);
$pdf->teks($kiri + 18, $y + 24, 'GAJI BERSIH DITERIMA', 9.5, true, $hijauTua);
$pdf->teks($kanan - 16, $y + 30, rupiah($slip['total_bersih']), 20, true, $hijauTua, 'kanan');
$pdf->paragraf($kiri + 18, $y + 52, $kanan - $kiri - 36, 'Terbilang: ' . terbilang_rupiah((float) $slip['total_bersih']), 9, $redup);

// Catatan kaki
$y += 112;
$y = $pdf->paragraf($kiri, $y, $kanan - $kiri,
    'PPh 21 dipotong dengan tarif efektif dari penghasilan bruto bulan berjalan. Bonus gol dan penampilan dihitung dari statistik pertandingan resmi; denda kartu sesuai peraturan disiplin tim.', 8.5);
$pdf->garis($kiri, 790, $kanan, 790, $garis, 0.8);
$pdf->teks($kiri, 806, 'Dokumen dibuat otomatis oleh Sistem SDM ' . NAMA_KLUB . ' pada '
    . tanggal_id((new DateTimeImmutable('now', new DateTimeZone(ZONA_WAKTU)))->format('Y-m-d'), true)
    . ' dan sah tanpa tanda tangan.', 7.5, false, $redup);
$pdf->teks($kiri, 818, 'Bersifat rahasia. Pertanyaan seputar slip gaji dapat diajukan ke bagian HR.', 7.5, false, $redup);

catat_aktivitas('unduh_slip', 'penggajian', (int) $slip['id'], nomor_slip($slip));

$nama = 'slip-gaji-' . substr((string) $slip['periode'], 0, 7) . '.pdf';
$isi = $pdf->hasil('Slip Gaji ' . bulan_tahun((string) $slip['periode']));
header('Content-Type: application/pdf');
// ?lihat=1 membuka PDF di tab browser (pratinjau), selain itu diunduh.
header('Content-Disposition: ' . (isset($_GET['lihat']) ? 'inline' : 'attachment') . '; filename="' . $nama . '"');
header('Content-Length: ' . strlen($isi));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
echo $isi;
