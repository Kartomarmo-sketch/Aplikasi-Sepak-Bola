<?php
declare(strict_types=1);

// Dashboard pelatih/medis: ketersediaan skuad, kebugaran, cedera aktif, agenda, performa terbaik, pengumuman.
// Dimuat oleh dashboard.php (sudah ada tampilan.php, lib_dasbor.php, sesi masuk).

$dbGagal = false;
$skuad = $tesTerakhir = $tesSebelum = $cedera = $agenda = $terbaik = $umum = [];
$kpi = ['program' => 0, 'latihan7' => 0, 'latihanBerikut' => null];
$rekor = ['m' => 0, 's' => 0, 'k' => 0, 'musim' => ''];
try {
    $skuad = db()->query(
        "SELECT u.id, u.nama_lengkap, pm.nomor_punggung, pr.posisi_kode, pr.status
           FROM users u JOIN profil_pemain pr ON pr.user_id = u.id LEFT JOIN pemain pm ON pm.user_id = u.id
          WHERE u.role = 'pemain' AND u.is_active = 1 AND pr.status <> 'pensiun'"
    )->fetchAll();
    $skuad = array_column($skuad, null, 'id');

    // Dua tes terakhir tiap pemain (untuk skor kini & perubahan).
    foreach (db()->query('SELECT user_id, tanggal, skor_kebugaran, status_kebugaran FROM pemeriksaan_kebugaran
                           WHERE skor_kebugaran IS NOT NULL ORDER BY user_id, tanggal DESC') as $t) {
        $u = (int) $t['user_id'];
        if (!isset($skuad[$u])) {
            continue;
        }
        if (!isset($tesTerakhir[$u])) {
            $tesTerakhir[$u] = $t;
        } elseif (!isset($tesSebelum[$u])) {
            $tesSebelum[$u] = $t;
        }
    }

    $cedera = db()->query(
        "SELECT c.*, u.nama_lengkap, pr.posisi_kode
           FROM cedera c JOIN users u ON u.id = c.user_id LEFT JOIN profil_pemain pr ON pr.user_id = c.user_id
          WHERE c.status <> 'pulih' AND u.is_active = 1
          ORDER BY c.estimasi_pulih IS NULL, c.estimasi_pulih"
    )->fetchAll();

    $kpi['program'] = (int) db()->query(
        "SELECT COUNT(DISTINCT ps.user_id) FROM program_latihan_peserta ps JOIN program_latihan p ON p.id = ps.program_id
          WHERE p.status = 'aktif' AND ps.status = 'berjalan'"
    )->fetchColumn();
    $r = db()->query("SELECT COUNT(*) AS n, MIN(waktu_mulai) AS berikut FROM jadwal
                       WHERE jenis = 'latihan' AND waktu_mulai >= NOW() AND waktu_mulai < NOW() + INTERVAL 7 DAY")->fetch();
    $kpi['latihan7'] = (int) $r['n'];
    $kpi['latihanBerikut'] = $r['berikut'];

    $agenda = d_agenda(4, false);

    $rekor['musim'] = (string) db()->query("SELECT MAX(musim) FROM pertandingan WHERE status = 'selesai'")->fetchColumn();
    $st = db()->prepare("SELECT SUM(skor_klub > skor_lawan) AS m, SUM(skor_klub = skor_lawan) AS s, SUM(skor_klub < skor_lawan) AS k
                           FROM pertandingan WHERE status = 'selesai' AND musim = ?");
    $st->execute([$rekor['musim']]);
    $rekor = array_merge($rekor, array_map('intval', $st->fetch() ?: []));

    $st = db()->prepare(
        "SELECT s.user_id, u.nama_lengkap, pr.posisi_kode, COUNT(*) AS main, SUM(s.menit_bermain) AS menit,
                SUM(s.goal) AS goal, SUM(s.assist) AS assist, AVG(s.rating) AS rating
           FROM statistik_pertandingan s
           JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
           JOIN users u ON u.id = s.user_id LEFT JOIN profil_pemain pr ON pr.user_id = s.user_id
          WHERE p.musim = ? AND s.rating IS NOT NULL AND u.is_active = 1
          GROUP BY s.user_id, u.nama_lengkap, pr.posisi_kode
         HAVING menit >= 270
          ORDER BY rating DESC, menit DESC LIMIT 6"
    );
    $st->execute([$rekor['musim']]);
    $terbaik = $st->fetchAll();

    $umum = d_pengumuman('staf', 3);
} catch (PDOException $ex) {
    error_log('Dashboard staf gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

// ---------- Olah data ----------
$idCedera = [];
foreach ($cedera as $c) {
    if (isset($skuad[(int) $c['user_id']])) {
        $idCedera[(int) $c['user_id']] = $c['status'];
    }
}
$jumlahSkuad = count($skuad);
$terbatas = 0;
foreach ($skuad as $id => $p) {
    if (isset($idCedera[$id])) {
        continue;
    }
    $statusFit = $tesTerakhir[$id]['status_kebugaran'] ?? 'fit';
    if ($p['status'] === 'diskors' || $p['status'] === 'dipinjamkan' || in_array($statusFit, ['terbatas', 'tidak_fit'], true)) {
        $terbatas++;
    }
}
$dirawat = count(array_filter($idCedera, static fn($s) => $s === 'dirawat'));
$pemulihan = count($idCedera) - $dirawat;
$tersedia = $jumlahSkuad - count($idCedera) - $terbatas;
$persenSedia = $jumlahSkuad ? (int) round(100 * $tersedia / $jumlahSkuad) : 0;

$skorKini = array_map(static fn($t) => (int) $t['skor_kebugaran'], $tesTerakhir);
$rataKini = $skorKini ? array_sum($skorKini) / count($skorKini) : null;
$skorLalu = array_map(static fn($t) => (int) $t['skor_kebugaran'], $tesSebelum);
$rataLalu = $skorLalu ? array_sum($skorLalu) / count($skorLalu) : null;
$distribusi = ['Sangat baik (≥85)' => [0, 'hijau'], 'Baik (70–84)' => [0, 'biru'], 'Cukup (55–69)' => [0, 'kuning'], 'Kurang (<55)' => [0, 'merah']];
foreach ($skorKini as $s) {
    $kunci = $s >= 85 ? 'Sangat baik (≥85)' : ($s >= 70 ? 'Baik (70–84)' : ($s >= 55 ? 'Cukup (55–69)' : 'Kurang (<55)'));
    $distribusi[$kunci][0]++;
}
asort($skorKini);
$perhatian = array_slice(array_filter($skorKini, static fn($s) => $s < 75), 0, 5, true);
$belumDites = $jumlahSkuad - count($tesTerakhir);

$labelTingkat = ['ringan' => 'Ringan', 'sedang' => 'Sedang', 'berat' => 'Berat'];
$labelStatusCedera = ['dirawat' => ['Perawatan', 'merah'], 'pemulihan' => ['Rehabilitasi', 'kuning']];
$cederaAktif = array_values(array_filter($cedera, static fn($c) => isset($skuad[(int) $c['user_id']])));

// ---------- Tampilan ----------
$hariIni = d_agenda_hari_ini(false);
$notifBaru = jumlah_notifikasi((int) $_SESSION['user_id']);
$ringkas = array_values(array_filter([
    $hariIni ? ['kalender', $hariIni . ' agenda hari ini', 'jadwal.php'] : null,
    $cederaAktif ? ['medis', count($cederaAktif) . ' pemain dalam penanganan', 'cedera.php'] : null,
    $notifBaru ? ['lonceng', $notifBaru . ' notifikasi baru', 'notifikasi.php'] : null,
]));
d_sapaan((string) $_SESSION['nama_lengkap'], 'Dasbor Pelatih / Medis', $ringkas,
    '<a class="tombol putih" href="cedera.php">' . ikon('medis') . 'Catat cedera</a>'
    . '<a class="tombol putih" href="kebugaran.php">' . ikon('aktivitas') . 'Input hasil tes</a>');
?>
            <?php if ($dbGagal): ?>
                <p class="galat" role="alert">Sebagian data belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
            <?php endif; ?>

            <section class="kpi-kisi kpi-6" aria-label="Ringkasan skuad">
                <?= kartu_kpi('Total skuad', angka($jumlahSkuad), 'orang', 'hijau', 'Pemain aktif terdaftar') ?>
                <?= kartu_kpi('Pemain tersedia', angka($tersedia), 'perisai', 'biru', $persenSedia . '% siap bertanding') ?>
                <?= kartu_kpi('Pemain cedera', angka(count($cederaAktif)), 'jantung', 'merah', $dirawat . ' perawatan · ' . $pemulihan . ' rehabilitasi') ?>
                <?= kartu_kpi('Rata-rata kebugaran', $rataKini !== null ? desimal($rataKini, 0) : '-', 'aktivitas', 'ungu',
                    $rataKini !== null && $rataLalu !== null
                        ? '<span class="' . ($rataKini >= $rataLalu ? 'naik' : 'turun') . '">' . ($rataKini >= $rataLalu ? '▲ ' : '▼ ') . desimal(abs($rataKini - $rataLalu), 1) . '</span> dari tes sebelumnya'
                        : 'Skor tes terakhir (0–100)') ?>
                <?= kartu_kpi('Pemain dengan program', angka($kpi['program']), 'beban', 'kuning', 'Program latihan berjalan') ?>
                <?= kartu_kpi('Latihan 7 hari ke depan', angka($kpi['latihan7']), 'peluit', 'hijau',
                    $kpi['latihanBerikut'] ? 'Berikutnya ' . e(hari_tanggal((string) $kpi['latihanBerikut'])) . ', ' . e(jam((string) $kpi['latihanBerikut'])) : 'Belum ada latihan terjadwal') ?>
            </section>

            <section class="kartu d-sedia" aria-labelledby="judulSedia">
                <div class="d-sedia-kepala">
                    <h2 id="judulSedia">Ketersediaan skuad</h2>
                    <span class="redup kecil"><?= $tersedia ?> dari <?= $jumlahSkuad ?> pemain siap dimainkan</span>
                </div>
                <div class="batang-segmen" role="img" aria-label="Tersedia <?= $tersedia ?>, terbatas <?= $terbatas ?>, rehabilitasi <?= $pemulihan ?>, perawatan <?= $dirawat ?>">
                    <?php foreach ([[$tersedia, 'hijau'], [$terbatas, 'kuning'], [$pemulihan, 'oranye'], [$dirawat, 'merah']] as [$n, $w]): ?>
                        <?php if ($n > 0): ?><span class="<?= $w ?>" style="flex: <?= $n ?>"></span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <ul class="legenda-segmen">
                    <li class="hijau"><b><?= $tersedia ?></b> Tersedia</li>
                    <li class="kuning"><b><?= $terbatas ?></b> Terbatas <small>(kebugaran/skorsing)</small></li>
                    <li class="oranye"><b><?= $pemulihan ?></b> Rehabilitasi</li>
                    <li class="merah"><b><?= $dirawat ?></b> Perawatan</li>
                </ul>
            </section>

            <div class="kisi-3">
                <section class="kartu" aria-labelledby="judulKebugaran">
                    <?= d_kepala('judulKebugaran', 'Ringkasan kebugaran skuad', 'Berdasarkan tes fisik terakhir tiap pemain', 'kebugaran.php') ?>
                    <ul class="d-distribusi">
                        <?php $maks = max(1, ...array_column($distribusi, 0)); ?>
                        <?php foreach ($distribusi as $label => [$n, $warna]): ?>
                            <li>
                                <div class="d-distribusi-teks"><span><?= e($label) ?></span><b><?= $n ?> pemain</b></div>
                                <div class="progres <?= $warna ?>"><span style="width: <?= round(100 * $n / $maks) ?>%"></span></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <h3 class="d-subjudul">Perlu perhatian</h3>
                    <?php if (!$perhatian): ?>
                        <p class="redup kecil">Semua pemain berskor 75 ke atas.</p>
                    <?php else: ?>
                        <ul class="d-perhatian">
                            <?php foreach ($perhatian as $id => $s): ?>
                                <?php [, $warna] = d_level($s); $lalu = isset($tesSebelum[$id]) ? $s - (int) $tesSebelum[$id]['skor_kebugaran'] : null; ?>
                                <li>
                                    <a href="kebugaran.php?pemain=<?= $id ?>"><?= e((string) $skuad[$id]['nama_lengkap']) ?></a>
                                    <span class="redup"><?= e((string) ($skuad[$id]['posisi_kode'] ?? '')) ?></span>
                                    <?php if ($lalu !== null && $lalu !== 0): ?><small class="<?= $lalu > 0 ? 'naik' : 'turun' ?>"><?= $lalu > 0 ? '▲' : '▼' ?><?= abs($lalu) ?></small><?php endif; ?>
                                    <b class="teks-<?= $warna ?>"><?= $s ?></b>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($belumDites > 0): ?><p class="d-catatan"><?= ikon('info') ?><?= $belumDites ?> pemain belum pernah dites.</p><?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulCedera">
                    <?= d_kepala('judulCedera', 'Daftar cedera aktif', count($cederaAktif) . ' pemain dalam penanganan tim medis', 'cedera.php') ?>
                    <?php if (!$cederaAktif): ?>
                        <div class="d-kosong">
                            <?= ikon('perisai') ?><span>Tidak ada cedera aktif. Seluruh skuad bebas cedera.</span>
                        </div>
                    <?php else: ?>
                        <ul class="d-cedera">
                            <?php foreach (array_slice($cederaAktif, 0, 4) as $c): ?>
                                <?php
                                [$labelStatus, $warnaStatus] = $labelStatusCedera[$c['status']] ?? ['Perawatan', 'merah'];
                                $rtp = null;
                                if ($c['estimasi_pulih']) {
                                    $total = max(1, selisih_hari((string) $c['tanggal_cedera'], (string) $c['estimasi_pulih']));
                                    $jalan = selisih_hari((string) $c['tanggal_cedera']);
                                    $rtp = max(0, min(100, (int) round(100 * $jalan / $total)));
                                    $sisa = selisih_hari(hari_ini()->format('Y-m-d'), (string) $c['estimasi_pulih']);
                                }
                                ?>
                                <li>
                                    <div class="d-cedera-kepala">
                                        <a href="cedera.php?pemain=<?= (int) $c['user_id'] ?>"><b><?= e((string) $c['nama_lengkap']) ?></b></a>
                                        <span class="redup">· <?= e((string) ($c['posisi_kode'] ?? '')) ?></span>
                                        <?= d_chip($labelStatus, $warnaStatus) ?>
                                    </div>
                                    <small><?= e((string) $c['jenis_cedera']) ?> · <?= e($labelTingkat[$c['tingkat']] ?? (string) $c['tingkat']) ?></small>
                                    <?php if ($rtp !== null): ?>
                                        <div class="progres <?= $warnaStatus === 'merah' ? 'merah' : 'kuning' ?>" title="Progres kembali bermain"><span style="width: <?= $rtp ?>%"></span></div>
                                        <small class="d-cedera-kaki"><span>Perkiraan kembali: <b><?= e(tanggal_id((string) $c['estimasi_pulih'])) ?></b></span>
                                            <span class="<?= $sisa < 0 ? 'teks-merah' : 'redup' ?>"><?= $sisa < 0 ? 'Lewat ' . (-$sisa) . ' hari' : ($sisa === 0 ? 'Hari ini' : $sisa . ' hari lagi') ?></span></small>
                                    <?php else: ?>
                                        <small class="redup">Perkiraan kembali belum ditentukan</small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($cederaAktif) > 4): ?><p class="d-catatan">+<?= count($cederaAktif) - 4 ?> cedera lain di halaman Riwayat Cedera.</p><?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulAgenda">
                    <?= d_kepala('judulAgenda', 'Latihan & pertandingan terdekat', 'Agenda tim berikutnya', 'jadwal.php') ?>
                    <?= d_daftar_agenda($agenda) ?>
                </section>
            </div>

            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulTerbaik">
                    <?= d_kepala('judulTerbaik', 'Performa terbaik musim ini',
                        'Rating rata-rata, minimal 270 menit · musim ' . e($rekor['musim'] ?: '-'), 'statistik.php', 'Lihat semua',
                        '<span class="d-rekor" title="Rekor tim musim ini"><b class="teks-hijau">' . $rekor['m'] . 'M</b><b class="redup">' . $rekor['s'] . 'S</b><b class="teks-merah">' . $rekor['k'] . 'K</b></span>') ?>
                    <?php if (!$terbaik): ?>
                        <div class="d-kosong"><?= ikon('trofi') ?><span>Belum ada statistik pertandingan musim ini.</span></div>
                    <?php else: ?>
                        <div class="gulir-tabel">
                            <table class="tabel-data d-tabel">
                                <thead><tr><th scope="col">#</th><th scope="col">Pemain</th><th scope="col">Pos</th><th scope="col" class="angka">Main</th><th scope="col" class="angka">Menit</th><th scope="col" class="angka">Gol</th><th scope="col" class="angka">Assist</th><th scope="col" class="angka">Rating</th></tr></thead>
                                <tbody>
                                    <?php foreach ($terbaik as $i => $t): ?>
                                        <tr>
                                            <td><span class="d-peringkat<?= $i < 3 ? ' p' . ($i + 1) : '' ?>"><?= $i + 1 ?></span></td>
                                            <td><a href="statistik.php?pemain=<?= (int) $t['user_id'] ?>"><b><?= e((string) $t['nama_lengkap']) ?></b></a></td>
                                            <td class="redup"><?= e((string) ($t['posisi_kode'] ?? '-')) ?></td>
                                            <td class="angka"><?= (int) $t['main'] ?></td>
                                            <td class="angka"><?= angka((int) $t['menit']) ?></td>
                                            <td class="angka"><?= (int) $t['goal'] ?></td>
                                            <td class="angka"><?= (int) $t['assist'] ?></td>
                                            <td class="angka"><?= chip_rating($t['rating']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="kartu" id="pengumuman" aria-labelledby="judulUmum">
                    <?= d_kepala('judulUmum', 'Pengumuman klub', 'Untuk pelatih & staf') ?>
                    <?= d_daftar_pengumuman($umum, false) ?>
                </section>
            </div>
