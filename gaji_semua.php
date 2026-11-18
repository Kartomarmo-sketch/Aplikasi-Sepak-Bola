<?php
declare(strict_types=1);

// Bagian halaman Slip Gaji khusus admin/HR: tab "Semua slip" / "Slip saya".
// Dimuat dari gaji.php setelah halaman_awal(); variabel $tabGaji tersedia.

require_once __DIR__ . '/admin_lib.php';

if (!isset($tabGaji) || ($_SESSION['role'] ?? '') !== 'admin') {
    return;
}
?>
            <nav class="segmen tab-halaman" aria-label="Tampilan slip">
                <a href="gaji.php"<?= $tabGaji === 'semua' ? ' class="aktif" aria-current="page"' : '' ?>>Semua slip</a>
                <a href="gaji.php?tab=saya"<?= $tabGaji === 'saya' ? ' class="aktif" aria-current="page"' : '' ?>>Slip saya</a>
            </nav>
<?php
if ($tabGaji !== 'semua') {
    return;
}

try {
    $semuaSlip = db()->query(
        "SELECT p.id, p.user_id, p.periode, p.total_bersih, p.status, p.tanggal_bayar, u.nama_lengkap, u.role
           FROM penggajian p JOIN users u ON u.id = p.user_id
          WHERE p.status <> 'draft'
          ORDER BY p.periode DESC, u.nama_lengkap"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Semua slip gagal dimuat: ' . $ex->getMessage());
    echo '<p class="galat" role="alert">Data slip belum dapat dimuat.</p>';
    return;
}
$periodeAda = [];
foreach ($semuaSlip as $s) {
    $periodeAda[substr((string) $s['periode'], 0, 7)] = bulan_tahun((string) $s['periode']);
}
$terbitBulanIni = array_filter($semuaSlip, static fn($s) => substr((string) $s['periode'], 0, 7) === array_key_first($periodeAda));
?>
            <section class="kpi-kisi" aria-label="Ringkasan slip">
                <?= kartu_kpi('Slip diterbitkan', angka(count($semuaSlip)), 'dokumen', 'hijau', e(count($periodeAda) . ' periode')) ?>
                <?= kartu_kpi('Periode terbaru', $periodeAda ? (string) reset($periodeAda) : '-', 'kalender', 'biru', e(count($terbitBulanIni) . ' slip')) ?>
                <?= kartu_kpi('Total periode terbaru', rupiah_ringkas(array_sum(array_map(static fn($s) => (float) $s['total_bersih'], $terbitBulanIni))), 'uang', 'ungu', 'Gaji bersih seluruh karyawan') ?>
                <?= kartu_kpi('Menunggu dibayar', angka(count(array_filter($semuaSlip, static fn($s) => $s['status'] === 'disetujui'))), 'jam', 'kuning', '<a href="kelola_gaji.php">Buka Data Penggajian</a>') ?>
            </section>

            <section class="kartu tabel-kartu" data-tabel aria-label="Semua slip gaji">
                <div class="kepala-tabel">
                    <?= alat_tabel('Cari nama...', [
                        'periode'  => ['label' => 'Periode', 'opsi' => $periodeAda],
                        'kategori' => ['label' => 'Kategori', 'opsi' => ['pemain' => 'Pemain', 'staf' => 'Staf']],
                    ]) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data">
                        <thead><tr><?= th_urut('Periode', 'angka', 'desc') ?><?= th_urut('Nama') ?><?= th_urut('Total', 'angka') ?><?= th_urut('Status') ?><th scope="col"><span class="sr">Aksi</span></th></tr></thead>
                        <tbody>
                            <?php foreach ($semuaSlip as $s): ?>
                                <tr data-periode="<?= e(substr((string) $s['periode'], 0, 7)) ?>" data-kategori="<?= $s['role'] === 'pemain' ? 'pemain' : 'staf' ?>">
                                    <td data-nilai="<?= strtotime((string) $s['periode']) ?>"><b><?= e(bulan_tahun((string) $s['periode'])) ?></b><small class="sub-sel"><?= e(nomor_slip($s)) ?></small></td>
                                    <td><b><?= e((string) $s['nama_lengkap']) ?></b><small class="sub-sel"><?= e(ROLE_LABELS[$s['role']] ?? '') ?></small></td>
                                    <td class="uang" data-nilai="<?= (float) $s['total_bersih'] ?>"><?= e(rupiah($s['total_bersih'])) ?></td>
                                    <td><?= chip_peta(LABEL_STATUS_SLIP, (string) $s['status']) ?></td>
                                    <td class="aksi-ikon">
                                        <a class="tombol-ikon-kecil" href="gaji_pdf.php?id=<?= (int) $s['id'] ?>&amp;lihat=1" target="_blank" rel="noopener" title="Lihat PDF" aria-label="Lihat slip <?= e((string) $s['nama_lengkap']) ?>"><?= ikon('mata') ?></a>
                                        <a class="tombol-ikon-kecil" href="gaji_pdf.php?id=<?= (int) $s['id'] ?>" title="Unduh PDF" aria-label="Unduh slip <?= e((string) $s['nama_lengkap']) ?>"><?= ikon('unduh') ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
            </section>
