<?php
declare(strict_types=1);

// Dashboard pemain: kartu profil + laga berikutnya, statistik musim, kontrak, slip gaji, tren kebugaran,
// agenda, status pengajuan, dan pengumuman klub. Dimuat oleh dashboard.php.

$uid = (int) $_SESSION['user_id'];
$dbGagal = false;
$profil = $stat = $kontrak = $slip = $laga = null;
$tes = $program = $agenda = $pengajuan = $umum = [];
$musim = '';
$peringkat = null;
$jumlahDinilai = 0;
try {
    $st = db()->prepare(
        "SELECT u.nama_lengkap, pm.nomor_punggung, pm.kartu_kuning, pm.kartu_merah, pr.*
           FROM users u LEFT JOIN pemain pm ON pm.user_id = u.id LEFT JOIN profil_pemain pr ON pr.user_id = u.id WHERE u.id = ?"
    );
    $st->execute([$uid]);
    $profil = $st->fetch() ?: null;

    $musim = (string) db()->query("SELECT MAX(musim) FROM pertandingan WHERE status = 'selesai'")->fetchColumn();
    $st = db()->prepare(
        "SELECT COUNT(*) AS main, COALESCE(SUM(s.starter), 0) AS starter, COALESCE(SUM(s.menit_bermain), 0) AS menit,
                COALESCE(SUM(s.goal), 0) AS goal, COALESCE(SUM(s.assist), 0) AS assist, AVG(s.rating) AS rating,
                COALESCE(SUM(s.kartu_kuning), 0) AS kuning, COALESCE(SUM(s.kartu_merah), 0) AS merah
           FROM statistik_pertandingan s JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
          WHERE s.user_id = ? AND p.musim = ?"
    );
    $st->execute([$uid, $musim]);
    $stat = $st->fetch();

    // Peringkat rating di skuad (pemain dengan minimal 270 menit).
    $st = db()->prepare(
        "SELECT s.user_id FROM statistik_pertandingan s JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
          WHERE p.musim = ? AND s.rating IS NOT NULL GROUP BY s.user_id HAVING SUM(s.menit_bermain) >= 270 ORDER BY AVG(s.rating) DESC"
    );
    $st->execute([$musim]);
    $urutan = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    $jumlahDinilai = count($urutan);
    $posisiSaya = array_search($uid, $urutan, true);
    $peringkat = $posisiSaya === false ? null : $posisiSaya + 1;

    $st = db()->prepare("SELECT * FROM kontrak WHERE user_id = ? AND status = 'aktif' ORDER BY tanggal_selesai DESC LIMIT 1");
    $st->execute([$uid]);
    $kontrak = $st->fetch() ?: null;

    $st = db()->prepare("SELECT * FROM penggajian WHERE user_id = ? AND status <> 'draft' ORDER BY periode DESC LIMIT 1");
    $st->execute([$uid]);
    $slip = $st->fetch() ?: null;

    $st = db()->prepare('SELECT tanggal, skor_kebugaran, vo2max FROM pemeriksaan_kebugaran
                          WHERE user_id = ? AND skor_kebugaran IS NOT NULL ORDER BY tanggal DESC LIMIT 6');
    $st->execute([$uid]);
    $tes = array_reverse($st->fetchAll());

    $st = db()->prepare(
        "SELECT p.judul, p.tanggal_selesai, ps.progres_persen FROM program_latihan_peserta ps JOIN program_latihan p ON p.id = ps.program_id
          WHERE ps.user_id = ? AND p.status = 'aktif' AND ps.status = 'berjalan' ORDER BY p.tanggal_selesai LIMIT 2"
    );
    $st->execute([$uid]);
    $program = $st->fetchAll();

    $st = db()->prepare(
        "SELECT j.judul, j.waktu_mulai, j.lokasi, p.lawan, p.kandang, p.kompetisi FROM jadwal j JOIN pertandingan p ON p.jadwal_id = j.id
          WHERE j.jenis = 'pertandingan' AND j.waktu_mulai >= NOW() ORDER BY j.waktu_mulai LIMIT 1"
    );
    $st->execute();
    $laga = $st->fetch() ?: null;

    $st = db()->prepare("SELECT id, jenis, judul, tanggal_mulai, tanggal_selesai, status, created_at FROM pengajuan
                          WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $st->execute([$uid]);
    $pengajuan = $st->fetchAll();

    $agenda = d_agenda(4, true);
    $umum = d_pengumuman('pemain', 3);
} catch (PDOException $ex) {
    error_log('Dashboard pemain gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

// ---------- Olah data ----------
$nama = (string) $_SESSION['nama_lengkap'];
$foto = foto_pengguna($uid, 'pemain');
$statusPemain = ['aktif' => ['Aktif', 'hijau'], 'cedera' => ['Cedera', 'merah'], 'diskors' => ['Diskors', 'kuning'],
                 'dipinjamkan' => ['Dipinjamkan', 'biru'], 'pensiun' => ['Pensiun', 'netral']][$profil['status'] ?? 'aktif'] ?? ['Aktif', 'hijau'];
$posisiTeks = ['GK' => 'Kiper', 'CB' => 'Bek Tengah', 'LB' => 'Bek Kiri', 'RB' => 'Bek Kanan', 'DM' => 'Gelandang Bertahan',
               'CM' => 'Gelandang Tengah', 'AM' => 'Gelandang Serang', 'LW' => 'Sayap Kiri', 'RW' => 'Sayap Kanan', 'ST' => 'Penyerang'][$profil['posisi_kode'] ?? ''] ?? (string) ($profil['posisi_detail'] ?? $profil['posisi'] ?? 'Pemain');
$main = (int) ($stat['main'] ?? 0);
$menit = (int) ($stat['menit'] ?? 0);
$ga90 = $menit > 0 ? desimal(90 * ((int) $stat['goal'] + (int) $stat['assist']) / $menit, 2) : '0';

$kini = $tes ? $tes[array_key_last($tes)] : null;
$lalu = count($tes) > 1 ? $tes[count($tes) - 2] : null;
[$levelKini, $warnaLevel] = d_level($kini['skor_kebugaran'] ?? null);
$titik = array_map(static fn($t) => ['x' => tanggal_id((string) $t['tanggal']), 'y' => (int) $t['skor_kebugaran'],
    't' => 'Skor ' . $t['skor_kebugaran'] . ' · ' . d_level($t['skor_kebugaran'])[0]], $tes);
$minSumbu = $tes ? max(0, (int) (floor((min(array_column($tes, 'skor_kebugaran')) - 10) / 10) * 10)) : 40;

$kontrakInfo = null;
if ($kontrak) {
    $mulai = (string) $kontrak['tanggal_mulai'];
    $akhir = (string) $kontrak['tanggal_selesai'];
    $totalHari = max(1, selisih_hari($mulai, $akhir));
    $jalan = max(0, min($totalHari, selisih_hari($mulai)));
    $d = (new DateTimeImmutable($mulai))->diff((new DateTimeImmutable($akhir))->modify('+1 day'));
    $bulan = max(1, $d->y * 12 + $d->m + ($d->d >= 15 ? 1 : 0));
    $kontrakInfo = [
        'persen' => (int) round(100 * $jalan / $totalHari),
        'sisa' => selisih_hari(hari_ini()->format('Y-m-d'), $akhir),
        'nilai' => (float) $kontrak['gaji_pokok'] * $bulan + (float) $kontrak['bonus_tanda_tangan'],
        'bulan' => $bulan,
    ];
}
$jenisKontrak = ['profesional' => 'Profesional', 'amatir' => 'Amatir', 'pinjaman' => 'Pinjaman', 'percobaan' => 'Percobaan', 'staf' => 'Staf'];
$statusAjuan = ['menunggu' => ['Menunggu', 'kuning'], 'disetujui' => ['Disetujui', 'hijau'], 'ditolak' => ['Ditolak', 'merah'], 'dibatalkan' => ['Dibatalkan', 'netral']];
$statusSlip = ['dibayar' => ['Dibayar', 'hijau'], 'disetujui' => ['Disetujui', 'biru']];

// ---------- Tampilan ----------
$hariIni = d_agenda_hari_ini(true);
$notifBaru = jumlah_notifikasi($uid);
$ringkas = array_values(array_filter([
    $hariIni ? ['kalender', $hariIni . ' agenda hari ini', 'jadwal.php'] : null,
    $program ? ['beban', count($program) . ' program latihan berjalan', 'latihan.php'] : null,
    $notifBaru ? ['lonceng', $notifBaru . ' notifikasi baru', 'notifikasi.php'] : null,
]));
d_sapaan($nama, 'Dasbor Pemain', $ringkas, '');
?>
            <?php if ($dbGagal): ?>
                <p class="galat" role="alert">Sebagian data belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
            <?php endif; ?>

            <section class="kartu d-profil" aria-label="Profil pemain">
                <div class="d-profil-utama">
                    <span class="d-avatar"><?= avatar_isi($nama, $foto) ?></span>
                    <div class="d-profil-teks">
                        <h2><?= e($nama) ?>
                            <?php if (($profil['nomor_punggung'] ?? null) !== null): ?><span class="d-nomor">#<?= (int) $profil['nomor_punggung'] ?></span><?php endif; ?>
                            <?= d_chip($statusPemain[0], $statusPemain[1]) ?>
                        </h2>
                        <p><?= e($posisiTeks) ?> · Tim Utama · <?= e('PMN-' . str_pad((string) $uid, 3, '0', STR_PAD_LEFT)) ?></p>
                        <small><?= $profil['tanggal_bergabung'] ? 'Bergabung ' . e(tanggal_id((string) $profil['tanggal_bergabung'])) : 'Tanggal bergabung belum diisi' ?><?= $profil['kewarganegaraan'] ? ' · ' . e((string) $profil['kewarganegaraan']) : '' ?></small>
                        <div class="d-profil-aksi">
                            <a class="tombol kecil utama" href="pengajuan.php"><?= ikon('kirim') ?>Ajukan izin / cuti</a>
                            <a class="tombol kecil" href="profil.php"><?= ikon('orang') ?>Profil saya</a>
                        </div>
                    </div>
                </div>
                <?php if ($laga): ?>
                    <a class="d-laga" href="jadwal.php?jenis=pertandingan">
                        <small>Laga berikutnya</small>
                        <b><?= $laga['lawan'] ? 'vs ' . e((string) $laga['lawan']) : e((string) $laga['judul']) ?></b>
                        <span><?= e(hari_tanggal((string) $laga['waktu_mulai'])) ?>, <?= e(jam((string) $laga['waktu_mulai'])) ?> WIB<?= $laga['kandang'] !== null ? ' · ' . ((int) $laga['kandang'] ? 'Kandang' : 'Tandang') : '' ?></span>
                        <span class="d-hitung"><?= e(d_relatif_hari((string) $laga['waktu_mulai'])) ?></span>
                    </a>
                <?php endif; ?>
            </section>

            <section class="kpi-kisi" aria-label="Statistik musim">
                <?= kartu_kpi('Pertandingan ' . ($musim ?: 'musim ini'), angka($main), 'trofi', 'hijau', (int) ($stat['starter'] ?? 0) . ' starter · ' . angka($menit) . ' menit') ?>
                <?= kartu_kpi('Gol / Assist', (int) ($stat['goal'] ?? 0) . ' / ' . (int) ($stat['assist'] ?? 0), 'target', 'biru', 'G+A per 90 menit: ' . $ga90) ?>
                <?= kartu_kpi('Rating rata-rata', ($stat['rating'] ?? null) !== null ? desimal($stat['rating'], 1) : '-', 'bintang', 'ungu',
                    ($peringkat ? 'Peringkat #' . $peringkat . ' dari ' . $jumlahDinilai . ' · ' : '') . 'Kartu: ' . (int) ($stat['kuning'] ?? 0) . ' kuning, ' . (int) ($stat['merah'] ?? 0) . ' merah') ?>
                <?= kartu_kpi('Status kebugaran', $kini ? (string) (int) $kini['skor_kebugaran'] : '-', 'jantung', 'merah',
                    d_chip($levelKini, $warnaLevel) . ($kini && $lalu ? ' <span class="' . ($kini['skor_kebugaran'] >= $lalu['skor_kebugaran'] ? 'naik' : 'turun') . '">'
                        . ($kini['skor_kebugaran'] >= $lalu['skor_kebugaran'] ? '▲ ' : '▼ ') . abs((int) $kini['skor_kebugaran'] - (int) $lalu['skor_kebugaran']) . '</span>' : '')) ?>
            </section>

            <div class="kisi-3">
                <section class="kartu" aria-labelledby="judulKontrakSaya">
                    <?= d_kepala('judulKontrakSaya', 'Kontrak saya', $kontrak ? e((string) $kontrak['nomor_kontrak']) . ' · ' . e($jenisKontrak[$kontrak['jenis']] ?? (string) $kontrak['jenis']) : 'Belum ada kontrak aktif') ?>
                    <?php if (!$kontrak): ?>
                        <div class="d-kosong"><?= ikon('pena') ?><span>Hubungi HR bila kontrak Anda belum tercatat.</span></div>
                    <?php else: ?>
                        <div class="d-kontrak-judul">
                            <b>Berakhir <?= e(tanggal_id((string) $kontrak['tanggal_selesai'], true)) ?></b>
                            <?= $kontrakInfo['sisa'] < 0 ? d_chip('Kedaluwarsa', 'merah') : ($kontrakInfo['sisa'] <= 90 ? d_chip('Hampir habis', 'kuning') : d_chip('Aktif', 'hijau')) ?>
                        </div>
                        <div class="d-progres-teks"><span><?= e(tanggal_id((string) $kontrak['tanggal_mulai'])) ?></span><span><?= $kontrakInfo['sisa'] >= 0 ? angka($kontrakInfo['sisa']) . ' hari tersisa' : 'Lewat ' . (-$kontrakInfo['sisa']) . ' hari' ?></span></div>
                        <div class="progres<?= $kontrakInfo['sisa'] <= 90 ? ' kuning' : '' ?>" role="progressbar" aria-valuenow="<?= $kontrakInfo['persen'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Masa kontrak berjalan"><span style="width: <?= $kontrakInfo['persen'] ?>%"></span></div>
                        <div class="d-dua">
                            <div><small>Nilai kontrak</small><b><?= e(rupiah_ringkas($kontrakInfo['nilai'])) ?></b><small class="redup"><?= $kontrakInfo['bulan'] ?> bulan</small></div>
                            <div><small>Gaji pokok / bulan</small><b><?= e(rupiah_ringkas($kontrak['gaji_pokok'])) ?></b><small class="redup">Cuti <?= (int) $kontrak['hak_cuti_tahunan'] ?> hari/tahun</small></div>
                        </div>
                        <a class="tombol penuh" href="kontrak.php">Detail kontrak</a>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulSlip">
                    <?= d_kepala('judulSlip', 'Slip gaji terbaru', $slip ? 'Periode ' . e(NAMA_BULAN[(int) substr((string) $slip['periode'], 5, 2)] . ' ' . substr((string) $slip['periode'], 0, 4)) : 'Belum ada slip gaji') ?>
                    <?php if (!$slip): ?>
                        <div class="d-kosong"><?= ikon('uang') ?><span>Slip akan muncul setelah gaji pertama disetujui.</span></div>
                    <?php else: ?>
                        <?php [$labelSlip, $warnaSlip] = $statusSlip[$slip['status']] ?? ['Disetujui', 'biru']; ?>
                        <div class="d-slip-total">
                            <b><?= e(rupiah($slip['total_bersih'])) ?></b>
                            <span><?= d_chip($labelSlip, $warnaSlip) ?><?= $slip['tanggal_bayar'] ? '<small class="redup">' . e(tanggal_id((string) $slip['tanggal_bayar'])) . '</small>' : '' ?></span>
                        </div>
                        <dl class="d-rincian">
                            <div><dt>Gaji pokok</dt><dd><?= e(rupiah($slip['gaji_pokok'])) ?></dd></div>
                            <div><dt>Tunjangan</dt><dd><?= e(rupiah($slip['tunjangan'])) ?></dd></div>
                            <div><dt>Bonus</dt><dd><?= e(rupiah($slip['bonus'])) ?></dd></div>
                            <div class="potong"><dt>Potongan</dt><dd>− <?= e(rupiah($slip['potongan'])) ?></dd></div>
                        </dl>
                        <a class="tombol penuh" href="gaji.php"><?= ikon('unduh') ?>Lihat &amp; unduh slip</a>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulTren">
                    <?= d_kepala('judulTren', 'Tren skor kebugaran', 'Skor tes fisik (0–100)', 'kebugaran.php') ?>
                    <?= grafik_garis($titik, (float) $minSumbu, 100, array_values(array_filter([55, 70, 85, 100], static fn($v) => $v > $minSumbu)), 'Grafik tren skor kebugaran ' . $nama) ?>
                    <?php if ($program): ?>
                        <ul class="d-program">
                            <?php foreach ($program as $p): ?>
                                <li>
                                    <div class="d-progres-teks"><a href="latihan.php"><?= e((string) $p['judul']) ?></a><b><?= (int) $p['progres_persen'] ?>%</b></div>
                                    <div class="progres"><span style="width: <?= (int) $p['progres_persen'] ?>%"></span></div>
                                    <small class="redup">Selesai <?= e(tanggal_id((string) $p['tanggal_selesai'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <div class="kisi-3">
                <section class="kartu" aria-labelledby="judulAgendaPemain">
                    <?= d_kepala('judulAgendaPemain', 'Jadwal latihan & pertandingan', 'Agenda Anda berikutnya', 'jadwal.php') ?>
                    <?= d_daftar_agenda($agenda) ?>
                </section>

                <section class="kartu" aria-labelledby="judulAjuan">
                    <?= d_kepala('judulAjuan', 'Status pengajuan', 'Cuti, izin & pengajuan lain', 'pengajuan.php') ?>
                    <?php if (!$pengajuan): ?>
                        <div class="d-kosong"><?= ikon('kotak-masuk') ?><span>Belum ada pengajuan. <a href="pengajuan.php">Ajukan cuti atau izin</a>.</span></div>
                    <?php else: ?>
                        <ul class="d-baris">
                            <?php foreach ($pengajuan as $p): ?>
                                <?php [$labelA, $warnaA] = $statusAjuan[$p['status']] ?? [$p['status'], 'netral']; ?>
                                <li>
                                    <div>
                                        <b><?= e((string) $p['judul']) ?></b>
                                        <small><?= $p['tanggal_mulai'] ? e(tanggal_id((string) $p['tanggal_mulai'])) . ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai'] ? ' – ' . e(tanggal_id((string) $p['tanggal_selesai'])) : '') : 'Diajukan ' . e(waktu_notif((string) $p['created_at'])) ?></small>
                                    </div>
                                    <div class="d-baris-kanan"><?= d_chip($labelA, $warnaA) ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="kartu" id="pengumuman" aria-labelledby="judulUmumPemain">
                    <?= d_kepala('judulUmumPemain', 'Pengumuman klub', 'Informasi terbaru dari manajemen') ?>
                    <?= d_daftar_pengumuman($umum, false) ?>
                </section>
            </div>
