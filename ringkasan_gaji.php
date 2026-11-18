<?php
declare(strict_types=1);

// Ringkasan Gaji (admin/HR): pengeluaran gaji per bulan (pemain vs staf), komitmen kontrak,
// perbandingan kontrak pemain, dan gaji pokok per posisi.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';

start_secure_session();
wajib_admin();
kirim_pengingat_kontrak();

$dbGagal = false;
try {
    // 12 periode terakhir yang sudah disetujui/dibayar.
    $bulanan = db()->query(
        "SELECT p.periode,
                SUM(IF(u.role = 'pemain', p.total_bersih, 0)) AS pemain,
                SUM(IF(u.role <> 'pemain', p.total_bersih, 0)) AS staf,
                COUNT(*) AS orang
           FROM penggajian p JOIN users u ON u.id = p.user_id
          WHERE p.status <> 'draft'
          GROUP BY p.periode ORDER BY p.periode DESC LIMIT 12"
    )->fetchAll();
    $bulanan = array_reverse($bulanan);

    $aktif = db()->query(
        "SELECT k.*, u.nama_lengkap, u.role, pr.posisi_kode
           FROM kontrak k JOIN users u ON u.id = k.user_id LEFT JOIN profil_pemain pr ON pr.user_id = u.id
          WHERE k.status = 'aktif' AND u.is_active = 1"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Ringkasan gaji gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

halaman_awal('Ringkasan Gaji', 'Ringkasan Gaji');
kepala_halaman('grafik-batang', 'Ringkasan Gaji', 'Total pengeluaran gaji dan perbandingan nilai kontrak antar pemain.');

if ($dbGagal || !$bulanan): ?>
            <p class="<?= $dbGagal ? 'galat' : 'kosong' ?>" role="alert"><?= $dbGagal ? 'Data belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.' : 'Belum ada slip gaji yang disetujui.' ?></p>
<?php
    halaman_akhir();
    return;
endif;

$terakhir = $bulanan[array_key_last($bulanan)];
$sebelum = $bulanan[count($bulanan) - 2] ?? null;
$totalTerakhir = (float) $terakhir['pemain'] + (float) $terakhir['staf'];
$tahun = substr((string) $terakhir['periode'], 0, 4);
$tahunBerjalan = array_sum(array_map(static fn($b) => substr((string) $b['periode'], 0, 4) === $tahun ? (float) $b['pemain'] + (float) $b['staf'] : 0, $bulanan));
$komitmen = array_sum(array_map(static fn($k) => (float) $k['gaji_pokok'], $aktif));
$nilaiAktif = array_sum(array_map('nilai_kontrak_admin', $aktif));

$pemain = array_values(array_filter($aktif, static fn($k) => $k['role'] === 'pemain'));
$staf = array_values(array_filter($aktif, static fn($k) => $k['role'] !== 'pemain'));
$gajiPemain = array_sum(array_column($pemain, 'gaji_pokok'));
$gajiStaf = array_sum(array_column($staf, 'gaji_pokok'));
$urutGaji = array_map('floatval', array_column($aktif, 'gaji_pokok'));
rsort($urutGaji);
$top5 = $komitmen > 0 ? 100 * array_sum(array_slice($urutGaji, 0, 5)) / $komitmen : 0;

// Data grafik batang bertumpuk.
$grafik = [
    'label' => array_map(static fn($b) => mb_substr(NAMA_BULAN[(int) substr((string) $b['periode'], 5, 2)], 0, 3) . ' ' . substr((string) $b['periode'], 2, 2), $bulanan),
    'seri' => [
        ['nama' => 'Pemain', 'kelas' => 'seri-1', 'nilai' => array_map(static fn($b) => (float) $b['pemain'], $bulanan)],
        ['nama' => 'Staf', 'kelas' => 'seri-2', 'nilai' => array_map(static fn($b) => (float) $b['staf'], $bulanan)],
    ],
];

// Perbandingan kontrak pemain (12 teratas) & gaji per posisi.
foreach ($pemain as &$p) {
    $p['nilai'] = nilai_kontrak_admin($p);
}
unset($p);
$byNilai = $pemain;
usort($byNilai, static fn($a, $b) => $b['nilai'] <=> $a['nilai']);
$byGaji = $pemain;
usort($byGaji, static fn($a, $b) => (float) $b['gaji_pokok'] <=> (float) $a['gaji_pokok']);
$perPosisi = [];
foreach ($pemain as $p) {
    $kode = (string) ($p['posisi_kode'] ?: '?');
    $perPosisi[$kode] ??= ['jumlah' => 0, 'n' => 0];
    $perPosisi[$kode]['jumlah'] += (float) $p['gaji_pokok'];
    $perPosisi[$kode]['n']++;
}
uasort($perPosisi, static fn($a, $b) => $b['jumlah'] <=> $a['jumlah']);

function daftar_batang(array $baris, string $kolom, bool $rupiahPenuh = false): string
{
    $maks = max(1, ...array_values(array_map(static fn($b) => (float) $b[$kolom], $baris)));
    $h = '<ul class="daftar-batang">';
    foreach (array_slice($baris, 0, 12) as $b) {
        $nilai = (float) $b[$kolom];
        $h .= '<li><div class="baris"><span>' . e((string) $b['nama_lengkap']) . ' <small class="redup">· ' . e((string) ($b['posisi_kode'] ?? '')) . '</small></span><b title="'
            . e(rupiah($nilai)) . '">' . e(rupiah_ringkas($nilai)) . '</b></div><div class="progres"><span style="width:' . round(100 * $nilai / $maks, 1) . '%"></span></div></li>';
    }
    return $h . '</ul>';
}
?>
            <section class="kpi-kisi" aria-label="Ringkasan gaji">
                <?= kartu_kpi('Gaji ' . bulan_tahun((string) $terakhir['periode']), rupiah_ringkas($totalTerakhir), 'uang', 'hijau',
                    e($terakhir['orang'] . ' orang') . ($sebelum ? ' · ' . selisih($totalTerakhir / 1e6, ((float) $sebelum['pemain'] + (float) $sebelum['staf']) / 1e6, 0, ' jt', true, 'dari bulan lalu') : '')) ?>
                <?= kartu_kpi('Total tahun ' . $tahun, rupiah_ringkas($tahunBerjalan), 'naik', 'biru', 'Slip disetujui & dibayar') ?>
                <?= kartu_kpi('Komitmen gaji pokok / bulan', rupiah_ringkas($komitmen), 'pena', 'ungu', 'Dari ' . count($aktif) . ' kontrak yang berlaku') ?>
                <?= kartu_kpi('Total nilai kontrak aktif', rupiah_ringkas($nilaiAktif), 'perisai', 'kuning', 'Seluruh sisa & masa kontrak') ?>
            </section>

            <section class="kartu" aria-labelledby="judulPengeluaran">
                <div class="kepala-kartu baris-kepala">
                    <div><h2 id="judulPengeluaran">Pengeluaran gaji per bulan</h2><p><?= count($bulanan) ?> bulan terakhir, pemain vs staf (gaji bersih)</p></div>
                    <nav class="segmen" data-grup-ganti aria-label="Tampilan data">
                        <button type="button" class="aktif" data-ganti="grafikPengeluaran" aria-pressed="true">Grafik</button>
                        <button type="button" data-ganti="tabelPengeluaran" aria-pressed="false">Tabel</button>
                    </nav>
                </div>
                <ul class="legenda-seri" aria-label="Legenda"><li class="seri-1">Pemain</li><li class="seri-2">Staf</li></ul>
                <div id="grafikPengeluaran" class="grafik-batang" tabindex="0" role="img"
                     aria-label="Grafik batang bertumpuk pengeluaran gaji per bulan, pemain dan staf"
                     data-grafik-batang="<?= e((string) json_encode($grafik, JSON_UNESCAPED_UNICODE)) ?>"></div>
                <div id="tabelPengeluaran" class="gulir-tabel" hidden>
                    <table class="tabel-data">
                        <thead><tr><th scope="col">Bulan</th><th scope="col">Pemain</th><th scope="col">Staf</th><th scope="col">Total</th><th scope="col">Porsi pemain</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse($bulanan) as $b): $t = (float) $b['pemain'] + (float) $b['staf']; ?>
                                <tr><td><b><?= e(bulan_tahun((string) $b['periode'])) ?></b></td><td class="uang"><?= e(rupiah($b['pemain'])) ?></td>
                                    <td class="uang"><?= e(rupiah($b['staf'])) ?></td><td class="uang"><b><?= e(rupiah($t)) ?></b></td>
                                    <td><?= $t > 0 ? round(100 * (float) $b['pemain'] / $t) . '%' : '-' ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulBanding">
                    <div class="kepala-kartu baris-kepala">
                        <div><h2 id="judulBanding">Perbandingan kontrak pemain</h2><p>12 tertinggi</p></div>
                        <nav class="segmen" data-grup-ganti aria-label="Urutkan menurut">
                            <button type="button" class="aktif" data-ganti="bandingNilai" aria-pressed="true">Nilai kontrak</button>
                            <button type="button" data-ganti="bandingGaji" aria-pressed="false">Gaji / bulan</button>
                        </nav>
                    </div>
                    <div id="bandingNilai"><?= daftar_batang($byNilai, 'nilai') ?></div>
                    <div id="bandingGaji" hidden><?= daftar_batang($byGaji, 'gaji_pokok') ?></div>
                </section>

                <div class="tumpuk-kartu">
                    <section class="kartu" aria-labelledby="judulPosisi">
                        <div class="kepala-kartu"><h2 id="judulPosisi">Gaji pokok per posisi</h2><p>Total per bulan, kontrak aktif</p></div>
                        <?php $maksPos = max(1, ...array_values(array_map(static fn($x) => $x["jumlah"], $perPosisi))); ?>
                        <ul class="daftar-batang">
                            <?php foreach ($perPosisi as $kode => $x): ?>
                                <li><div class="baris"><span><?= e(KODE_POSISI[$kode][0] ?? $kode) ?> <small class="redup">(<?= $x['n'] ?>)</small></span><b><?= e(rupiah_ringkas($x['jumlah'])) ?></b></div>
                                    <div class="progres"><span style="width:<?= round(100 * $x['jumlah'] / $maksPos, 1) ?>%"></span></div></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <section class="kartu" aria-labelledby="judulWawasan">
                        <div class="kepala-kartu"><h2 id="judulWawasan">Wawasan biaya skuad</h2><p>Dari kontrak yang berlaku</p></div>
                        <ul class="rincian-uang">
                            <li><span>Porsi gaji pemain</span><b><?= $komitmen > 0 ? round(100 * $gajiPemain / $komitmen) : 0 ?>%</b></li>
                            <li><span>Rata-rata gaji pemain</span><b><?= e(rupiah_ringkas($pemain ? $gajiPemain / count($pemain) : 0)) ?></b></li>
                            <li><span>Rata-rata gaji staf</span><b><?= e(rupiah_ringkas($staf ? $gajiStaf / count($staf) : 0)) ?></b></li>
                            <li><span>5 gaji tertinggi menyerap</span><b><?= round($top5) ?>%</b></li>
                        </ul>
                        <p class="bantuan">Konsentrasi gaji tinggi berarti risiko besar bila pemain kunci cedera atau pergi. Pantau juga <a href="kelola_kontrak.php?hal=hampir">kontrak hampir habis</a>.</p>
                    </section>
                </div>
            </div>
<?php
halaman_akhir();
