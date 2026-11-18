<?php
declare(strict_types=1);

// Riwayat kehadiran pengguna yang login pada latihan, pertandingan, dan rapat.

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const STATUS_HADIR = [
    'hadir' => ['Hadir', 'hijau'], 'terlambat' => ['Terlambat', 'kuning'], 'izin' => ['Izin', 'biru'],
    'sakit' => ['Sakit', 'ungu'], 'alpa' => ['Alpa', 'merah'],
];
const JENIS_KEGIATAN = ['latihan' => 'Latihan', 'pertandingan' => 'Pertandingan', 'rapat' => 'Rapat', 'perjalanan' => 'Perjalanan', 'lainnya' => 'Lainnya'];
const PILIHAN_PERIODE = ['30' => '30 hari terakhir', '90' => '90 hari terakhir', 'musim' => 'Musim ini', 'semua' => 'Semua waktu'];

$uid = (int) $_SESSION['user_id'];
$periode = array_key_exists((string) ($_GET['periode'] ?? ''), PILIHAN_PERIODE) ? (string) $_GET['periode'] : '90';
$dari = match ($periode) {
    '30'    => hari_ini()->modify('-30 days')->format('Y-m-d'),
    '90'    => hari_ini()->modify('-90 days')->format('Y-m-d'),
    'musim' => awal_musim(),
    default => '2000-01-01',
};
$dbGagal = false;
$data = [];

try {
    $stmt = db()->prepare(
        "SELECT k.status, k.catatan, j.id AS jadwal_id, j.jenis, j.judul, j.lokasi, j.waktu_mulai
           FROM kehadiran k JOIN jadwal j ON j.id = k.jadwal_id
          WHERE k.user_id = :u AND j.waktu_mulai >= :dari
          ORDER BY j.waktu_mulai DESC"
    );
    $stmt->execute([':u' => $uid, ':dari' => $dari]);
    $data = $stmt->fetchAll();

    // Jejak 8 minggu terakhir (tidak terpengaruh filter periode).
    $awalJejak = hari_ini()->modify('monday this week')->modify('-7 weeks');
    $stmt = db()->prepare(
        "SELECT DATE(j.waktu_mulai) AS tgl, k.status, j.judul
           FROM kehadiran k JOIN jadwal j ON j.id = k.jadwal_id
          WHERE k.user_id = :u AND j.waktu_mulai >= :dari"
    );
    $stmt->execute([':u' => $uid, ':dari' => $awalJejak->format('Y-m-d')]);
    $jejak = [];
    $peringkat = ['alpa' => 5, 'sakit' => 4, 'izin' => 3, 'terlambat' => 2, 'hadir' => 1];
    foreach ($stmt->fetchAll() as $r) {
        // Satu hari bisa punya beberapa kegiatan: tampilkan status "terburuk".
        $lama = $jejak[$r['tgl']] ?? null;
        if ($lama === null || $peringkat[$r['status']] > $peringkat[$lama['status']]) {
            $jejak[$r['tgl']] = ['status' => $r['status'], 'judul' => $r['judul'], 'jumlah' => ($lama['jumlah'] ?? 0) + 1];
        } else {
            $jejak[$r['tgl']]['jumlah']++;
        }
    }
} catch (PDOException $ex) {
    error_log('Kehadiran gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

function chip_status_hadir(string $s): string
{
    [$teks, $kelas] = STATUS_HADIR[$s] ?? [$s, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

function persen_hadir(array $baris): ?int
{
    if (!$baris) {
        return null;
    }
    $hadir = count(array_filter($baris, static fn($r) => in_array($r['status'], ['hadir', 'terlambat'], true)));
    return (int) round(100 * $hadir / count($baris));
}

halaman_awal('Kehadiran', 'Kehadiran');
kepala_halaman('centang-kal', 'Kehadiran', 'Riwayat kehadiran Anda di latihan, pertandingan, dan rapat.',
    filter_get(['periode' => ['label' => 'Periode', 'opsi' => PILIHAN_PERIODE, 'nilai' => $periode]]));

if ($dbGagal): ?>
            <p class="galat" role="alert">Data kehadiran belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$hitung = array_fill_keys(array_keys(STATUS_HADIR), 0);
foreach ($data as $r) {
    $hitung[$r['status']]++;
}
$pct = persen_hadir($data);
?>
            <section class="kpi-kisi" aria-label="Ringkasan kehadiran">
                <?= kartu_kpi('Tingkat kehadiran', $pct !== null ? $pct . '%' : '-', 'centang-kal', $pct === null || $pct >= 90 ? 'hijau' : ($pct >= 75 ? 'kuning' : 'merah'),
                    e(count($data) . ' kegiatan · ' . PILIHAN_PERIODE[$periode])) ?>
                <?= kartu_kpi('Hadir tepat waktu', angka($hitung['hadir']), 'tanda', 'biru', e($hitung['terlambat'] . ' kali terlambat')) ?>
                <?= kartu_kpi('Izin & sakit', angka($hitung['izin'] + $hitung['sakit']), 'medis', 'ungu', e($hitung['izin'] . ' izin · ' . $hitung['sakit'] . ' sakit')) ?>
                <?= kartu_kpi('Tanpa keterangan', angka($hitung['alpa']), 'peringatan', $hitung['alpa'] ? 'merah' : 'hijau',
                    $hitung['alpa'] ? 'Dapat dikenai sanksi disiplin' : 'Bagus, tidak ada alpa') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulJejak">
                    <div class="kepala-kartu"><h2 id="judulJejak">Jejak 8 minggu terakhir</h2><p>Satu kotak = satu hari; warna menunjukkan status kehadiran</p></div>
                    <div class="jejak" role="img" aria-label="Peta kehadiran 8 minggu terakhir">
                        <div class="jejak-hari" aria-hidden="true"><span>Sen</span><span></span><span>Rab</span><span></span><span>Jum</span><span></span><span>Min</span></div>
                        <div class="jejak-kisi">
                            <?php for ($i = 0; $i < 56; $i++): ?>
                                <?php
                                $d = $awalJejak->modify("+$i days");
                                $k = $d->format('Y-m-d');
                                $isi = $jejak[$k] ?? null;
                                $mendatang = $d > hari_ini();
                                $judulSel = tanggal_id($k) . ': ' . ($isi ? STATUS_HADIR[$isi['status']][0] . ' · ' . $isi['judul'] . ($isi['jumlah'] > 1 ? ' (+' . ($isi['jumlah'] - 1) . ' kegiatan)' : '') : ($mendatang ? 'belum terjadi' : 'tidak ada kegiatan'));
                                ?>
                                <span class="sel-jejak <?= $isi ? 's-' . e($isi['status']) : ($mendatang ? 'nanti' : '') ?>" title="<?= e($judulSel) ?>"></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <ul class="legenda-jejak">
                        <?php foreach (STATUS_HADIR as $k => [$lbl]): ?><li class="s-<?= $k ?>"><?= e($lbl) ?></li><?php endforeach; ?>
                        <li class="kosong-jejak">Tidak ada kegiatan</li>
                    </ul>
                </section>

                <section class="kartu" aria-labelledby="judulPerJenis">
                    <div class="kepala-kartu"><h2 id="judulPerJenis">Per jenis kegiatan</h2><p><?= e(PILIHAN_PERIODE[$periode]) ?></p></div>
                    <ul class="meteran-daftar">
                        <?php foreach (JENIS_KEGIATAN as $j => $lbl): ?>
                            <?php $baris = array_filter($data, static fn($r) => $r['jenis'] === $j); if (!$baris) { continue; } $p = persen_hadir($baris); ?>
                            <li>
                                <div class="baris"><span><?= e($lbl) ?></span><b><?= $p ?>%</b></div>
                                <div class="progres<?= $p < 80 ? ' kuning' : '' ?>" role="progressbar" aria-label="Kehadiran <?= e($lbl) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $p ?>"><span style="width: <?= $p ?>%"></span></div>
                                <small><?= count(array_filter($baris, static fn($r) => in_array($r['status'], ['hadir', 'terlambat'], true))) ?> dari <?= count($baris) ?> kegiatan</small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                    $perBulan = [];
                    foreach ($data as $r) {
                        $perBulan[substr((string) $r['waktu_mulai'], 0, 7)][] = $r;
                    }
                    ?>
                    <?php if ($perBulan): ?>
                        <h3 class="pemisah-bagian">Per bulan</h3>
                        <ul class="rincian-uang bulanan">
                            <?php foreach (array_slice($perBulan, 0, 4, true) as $bln => $baris): ?>
                                <li><span><?= e(bulan_tahun($bln . '-01')) ?></span><b><?= persen_hadir($baris) ?>%</b></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulRiwayatHadir">
                <div class="kepala-tabel">
                    <h2 id="judulRiwayatHadir">Riwayat kehadiran</h2>
                    <?= alat_tabel('Cari kegiatan...', [
                        'status' => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], STATUS_HADIR)],
                        'jenis'  => ['label' => 'Kegiatan', 'opsi' => JENIS_KEGIATAN],
                    ]) ?>
                </div>
                <?php if (!$data): ?>
                    <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('kotak-masuk') ?></span><b>Belum ada catatan kehadiran</b><span>Coba pilih periode yang lebih panjang.</span></div>
                <?php else: ?>
                    <div class="gulir-tabel">
                        <table class="tabel-data">
                            <thead><tr><?= th_urut('Tanggal', 'angka', 'desc') ?><?= th_urut('Kegiatan') ?><?= th_urut('Status') ?><th scope="col">Keterangan</th></tr></thead>
                            <tbody>
                                <?php foreach ($data as $r): ?>
                                    <tr data-status="<?= e((string) $r['status']) ?>" data-jenis="<?= e((string) $r['jenis']) ?>">
                                        <td class="sel-tgl" data-nilai="<?= strtotime((string) $r['waktu_mulai']) ?>"><b><?= e(hari_tanggal((string) $r['waktu_mulai'])) ?></b><small class="sub-sel"><?= e(jam((string) $r['waktu_mulai'])) ?> WIB</small></td>
                                        <td><b><?= e((string) $r['judul']) ?></b><small class="sub-sel"><?= e(JENIS_KEGIATAN[$r['jenis']] ?? '') ?><?= $r['lokasi'] !== '' ? ' · ' . e((string) $r['lokasi']) : '' ?></small></td>
                                        <td data-nilai="<?= e((string) $r['status']) ?>"><?= chip_status_hadir((string) $r['status']) ?></td>
                                        <td><?= $r['catatan'] ? e((string) $r['catatan']) : '<span class="redup">-</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= kaki_tabel() ?>
                    <p class="catatan-tabel">Persentase kehadiran menghitung hadir dan terlambat. Ketidakhadiran karena izin/sakit yang disetujui tetap tercatat. Ada yang keliru? Ajukan koreksi lewat <a href="pengajuan.php?hal=izin&amp;baru=lainnya">Pengajuan Izin</a>.</p>
                <?php endif; ?>
            </section>
<?php
halaman_akhir();
