<?php
declare(strict_types=1);

// Dashboard admin/manajemen: tenaga kerja, kontrak, beban gaji, pengajuan, aktivitas sistem, agenda, pengumuman.
// Dimuat oleh dashboard.php (sudah ada tampilan.php, lib_dasbor.php, admin_lib.php, sesi admin).

const DASBOR_ENTITAS = [
    'kontrak' => ['Kontrak', 'pena', 'biru'], 'penggajian' => ['Penggajian', 'uang', 'hijau'],
    'pengajuan' => ['Pengajuan', 'kotak-masuk', 'ungu'], 'cedera' => ['Medis', 'medis', 'merah'],
    'perawatan_cedera' => ['Medis', 'medis', 'merah'], 'pemeriksaan_kebugaran' => ['Kebugaran', 'aktivitas', 'hijau'],
    'program_latihan' => ['Latihan', 'beban', 'kuning'], 'statistik_pertandingan' => ['Statistik', 'trofi', 'kuning'],
    'users' => ['Hak akses', 'perisai', 'netral'], 'profil_pemain' => ['Profil', 'orang', 'netral'],
    'staf' => ['Profil', 'orang', 'netral'], 'dokumen_karyawan' => ['Dokumen', 'dokumen', 'kuning'],
    'pengumuman' => ['Pengumuman', 'kotak-masuk', 'biru'], 'jadwal' => ['Jadwal', 'kalender', 'kuning'],
];
const DASBOR_AKSI = [
    'tambah' => 'Menambah', 'ubah' => 'Mengubah', 'hapus' => 'Menghapus', 'pulih' => 'Menandai pulih',
    'perpanjang' => 'Memperpanjang', 'tinjau' => 'Meninjau', 'dibayar' => 'Membayar', 'unggah' => 'Mengunggah', 'unduh' => 'Mengunduh',
];

$dbGagal = false;
$k = ['pemain' => 0, 'staf' => 0, 'kontrak' => 0, 'hampir' => 0, 'lewat' => 0, 'menunggu' => 0, 'tertua' => null];
$gaji = $posisi = $kontrakAkhir = $log = $agenda = $menunggu = $umum = [];
try {
    $r = db()->query(
        "SELECT SUM(u.role = 'pemain' AND COALESCE(pr.status, 'aktif') <> 'pensiun') AS pemain, SUM(u.role <> 'pemain') AS staf
           FROM users u LEFT JOIN profil_pemain pr ON pr.user_id = u.id WHERE u.is_active = 1"
    )->fetch();
    $k['pemain'] = (int) $r['pemain'];
    $k['staf'] = (int) $r['staf'];
    $r = db()->query(
        "SELECT COUNT(*) AS aktif,
                SUM(k.tanggal_selesai <= CURDATE() + INTERVAL " . BATAS_HAMPIR_HABIS . " DAY) AS hampir,
                SUM(k.tanggal_selesai < CURDATE()) AS lewat
           FROM kontrak k JOIN users u ON u.id = k.user_id WHERE k.status = 'aktif' AND u.is_active = 1"
    )->fetch();
    $k['kontrak'] = (int) $r['aktif'];
    $k['hampir'] = (int) $r['hampir'];
    $k['lewat'] = (int) $r['lewat'];
    $r = db()->query("SELECT COUNT(*) AS n, MIN(created_at) AS tertua FROM pengajuan WHERE status = 'menunggu'")->fetch();
    $k['menunggu'] = (int) $r['n'];
    $k['tertua'] = $r['tertua'];

    $gaji = array_reverse(db()->query(
        "SELECT p.periode, SUM(IF(u.role = 'pemain', p.total_bersih, 0)) AS pemain, SUM(IF(u.role <> 'pemain', p.total_bersih, 0)) AS staf
           FROM penggajian p JOIN users u ON u.id = p.user_id
          WHERE p.status <> 'draft' GROUP BY p.periode ORDER BY p.periode DESC LIMIT 6"
    )->fetchAll());

    $posisi = db()->query(
        "SELECT pr.posisi_kode, COUNT(*) AS n FROM profil_pemain pr JOIN users u ON u.id = pr.user_id
          WHERE u.is_active = 1 AND pr.status <> 'pensiun' AND pr.posisi_kode IS NOT NULL GROUP BY pr.posisi_kode"
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    $kontrakAkhir = db()->query(
        "SELECT k.user_id, k.nomor_kontrak, k.tanggal_selesai, u.nama_lengkap, u.role, pr.posisi_kode, s.jabatan, s.jabatan_detail,
                DATEDIFF(k.tanggal_selesai, CURDATE()) AS sisa
           FROM kontrak k JOIN users u ON u.id = k.user_id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id LEFT JOIN staf s ON s.user_id = u.id
          WHERE k.status = 'aktif' AND u.is_active = 1 AND k.tanggal_selesai <= CURDATE() + INTERVAL " . BATAS_HAMPIR_HABIS . " DAY
          ORDER BY k.tanggal_selesai LIMIT 6"
    )->fetchAll();

    $log = db()->query(
        "SELECT l.aksi, l.entitas, l.detail, l.created_at, u.nama_lengkap
           FROM log_aktivitas l LEFT JOIN users u ON u.id = l.user_id
          WHERE l.aksi NOT IN ('login', 'logout', 'gagal', 'unduh') ORDER BY l.created_at DESC, l.id DESC LIMIT 6"
    )->fetchAll();

    $menunggu = db()->query(
        "SELECT p.id, p.jenis, p.judul, p.tanggal_mulai, p.tanggal_selesai, p.created_at, u.nama_lengkap
           FROM pengajuan p JOIN users u ON u.id = p.user_id
          WHERE p.status = 'menunggu' ORDER BY p.created_at LIMIT 5"
    )->fetchAll();

    $agenda = d_agenda(4, false);
    $umum = d_pengumuman(null, 4);
} catch (PDOException $ex) {
    error_log('Dashboard admin gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

// ---------- Olah data ----------
$terakhir = $gaji ? $gaji[array_key_last($gaji)] : null;
$sebelum = count($gaji) > 1 ? $gaji[count($gaji) - 2] : null;
$totalKini = $terakhir ? (float) $terakhir['pemain'] + (float) $terakhir['staf'] : 0.0;
$totalLalu = $sebelum ? (float) $sebelum['pemain'] + (float) $sebelum['staf'] : 0.0;
$labelPeriode = static fn(string $p): string => mb_substr(NAMA_BULAN[(int) substr($p, 5, 2)], 0, 3) . ' ' . substr($p, 2, 2);
$subGaji = $terakhir ? NAMA_BULAN[(int) substr((string) $terakhir['periode'], 5, 2)] . ' ' . substr((string) $terakhir['periode'], 0, 4) : 'Belum ada slip';
if ($totalLalu > 0) {
    $ubah = 100 * ($totalKini - $totalLalu) / $totalLalu;
    $subGaji = '<span class="' . ($ubah > 0 ? 'turun' : 'naik') . '">' . ($ubah >= 0 ? '▲ ' : '▼ ') . desimal(abs($ubah), 1) . '%</span> ' . e($subGaji);
} else {
    $subGaji = e($subGaji);
}
$grafik = [
    'label' => array_map(static fn($b) => $labelPeriode((string) $b['periode']), $gaji),
    'seri' => [
        ['nama' => 'Pemain', 'kelas' => 'seri-1', 'nilai' => array_map(static fn($b) => (float) $b['pemain'], $gaji)],
        ['nama' => 'Staf', 'kelas' => 'seri-2', 'nilai' => array_map(static fn($b) => (float) $b['staf'], $gaji)],
    ],
];
$rataGaji = $gaji ? array_sum(array_map(static fn($b) => (float) $b['pemain'] + (float) $b['staf'], $gaji)) / count($gaji) : 0;
$porsiPemain = $totalKini > 0 ? round(100 * (float) $terakhir['pemain'] / $totalKini) : 0;

$lini = [];
foreach (KODE_POSISI as $kode => [$nama, $l]) {
    $lini[$l][] = [$kode, $nama, (int) ($posisi[$kode] ?? 0)];
}
$maksPosisi = max(1, ...array_values(array_map('intval', $posisi ?: [1])));

$teksJabatan = static function (array $r): string {
    if ($r['role'] === 'pemain') {
        return KODE_POSISI[$r['posisi_kode']][0] ?? 'Pemain';
    }
    return (string) ($r['jabatan_detail'] ?: (LABEL_JABATAN_ENUM[$r['jabatan']] ?? 'Staf'));
};
$hariMenunggu = $k['tertua'] ? selisih_hari(substr((string) $k['tertua'], 0, 10)) : 0;

// ---------- Tampilan ----------
$notifBaru = jumlah_notifikasi((int) $_SESSION['user_id']);
$ringkas = array_values(array_filter([
    $k['menunggu'] ? ['kotak-masuk', $k['menunggu'] . ' pengajuan menunggu', 'pengajuan.php'] : null,
    $k['hampir'] ? ['pena', $k['hampir'] . ' kontrak perlu ditinjau', 'kelola_kontrak.php?hal=hampir'] : null,
    $notifBaru ? ['lonceng', $notifBaru . ' notifikasi baru', 'notifikasi.php'] : null,
]));
d_sapaan((string) $_SESSION['nama_lengkap'], 'Dasbor Admin / Manajemen', $ringkas,
    '<a class="tombol putih" href="data_pemain.php">' . ikon('tambah') . 'Data pemain</a>'
    . '<a class="tombol putih" href="kelola_gaji.php">' . ikon('uang') . 'Penggajian</a>');
?>
            <?php if ($dbGagal): ?>
                <p class="galat" role="alert">Sebagian data belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
            <?php endif; ?>

            <section class="kpi-kisi kpi-6" aria-label="Ringkasan klub">
                <?= kartu_kpi('Pemain aktif', angka($k['pemain']), 'orang', 'hijau', 'Skuad terdaftar') ?>
                <?= kartu_kpi('Total staf', angka($k['staf']), 'rapat', 'biru', 'Pelatih, medis & manajemen') ?>
                <?= kartu_kpi('Kontrak aktif', angka($k['kontrak']), 'pena', 'ungu', $k['pemain'] + $k['staf'] > $k['kontrak'] ? ($k['pemain'] + $k['staf'] - $k['kontrak']) . ' karyawan tanpa kontrak aktif' : 'Semua karyawan terkontrak') ?>
                <?= kartu_kpi('Kontrak hampir habis', angka($k['hampir']), 'peringatan', 'kuning', $k['lewat'] ? '<span class="turun">' . $k['lewat'] . ' sudah kedaluwarsa</span>' : '≤ ' . BATAS_HAMPIR_HABIS . ' hari lagi') ?>
                <?= kartu_kpi('Beban gaji bulanan', rupiah_ringkas($totalKini), 'uang', 'hijau', $subGaji) ?>
                <?= kartu_kpi('Pengajuan menunggu', angka($k['menunggu']), 'kotak-masuk', 'merah', $k['menunggu'] ? 'Tertua ' . ($hariMenunggu > 0 ? $hariMenunggu . ' hari' : 'hari ini') : 'Tidak ada antrean') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulGaji">
                    <?= d_kepala('judulGaji', 'Pengeluaran gaji bulanan', count($gaji) . ' bulan terakhir, pemain vs staf (gaji bersih)', 'ringkasan_gaji.php') ?>
                    <?php if (!$gaji): ?>
                        <div class="d-kosong"><?= ikon('uang') ?><span>Belum ada slip gaji yang disetujui.</span></div>
                    <?php else: ?>
                        <div class="d-gaji-ringkas">
                            <ul class="legenda-seri" aria-label="Legenda"><li class="seri-1">Pemain</li><li class="seri-2">Staf</li></ul>
                            <span class="redup kecil">Rata-rata <b><?= e(rupiah_ringkas($rataGaji)) ?></b>/bulan · pemain <b><?= $porsiPemain ?>%</b> dari total</span>
                        </div>
                        <div class="grafik-batang d-grafik" tabindex="0" role="img"
                             aria-label="Grafik batang bertumpuk pengeluaran gaji <?= count($gaji) ?> bulan terakhir, pemain dan staf"
                             data-grafik-batang="<?= e((string) json_encode($grafik, JSON_UNESCAPED_UNICODE)) ?>"></div>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulPosisi">
                    <?= d_kepala('judulPosisi', 'Distribusi pemain per posisi', array_sum($posisi) . ' pemain aktif, dikelompokkan per lini', 'data_pemain.php') ?>
                    <?php foreach ($lini as $l => $daftar): ?>
                        <h3 class="d-subjudul"><?= e(LABEL_LINI[$l] ?? $l) ?> <span class="redup"><?= array_sum(array_column($daftar, 2)) ?></span></h3>
                        <ul class="d-distribusi rapat">
                            <?php foreach ($daftar as [$kode, $nama, $n]): ?>
                                <li>
                                    <div class="d-distribusi-teks"><span><?= e($nama) ?> <small class="redup"><?= e($kode) ?></small></span><b><?= $n ?></b></div>
                                    <div class="progres"><span style="width: <?= round(100 * $n / $maksPosisi) ?>%"></span></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </section>
            </div>

            <div class="kisi-3">
                <section class="kartu" aria-labelledby="judulKontrak">
                    <?= d_kepala('judulKontrak', 'Kontrak segera berakhir', 'Dalam ' . BATAS_HAMPIR_HABIS . ' hari ke depan', 'kelola_kontrak.php?hal=hampir') ?>
                    <?php if (!$kontrakAkhir): ?>
                        <div class="d-kosong"><?= ikon('pena') ?><span>Tidak ada kontrak yang segera berakhir.</span></div>
                    <?php else: ?>
                        <ul class="d-baris">
                            <?php foreach ($kontrakAkhir as $r): ?>
                                <?php $sisa = (int) $r['sisa']; ?>
                                <li>
                                    <div>
                                        <a href="detail_karyawan.php?id=<?= (int) $r['user_id'] ?>&amp;tab=kontrak"><b><?= e((string) $r['nama_lengkap']) ?></b></a>
                                        <small><?= e($teksJabatan($r)) ?> · berakhir <?= e(tanggal_id((string) $r['tanggal_selesai'])) ?></small>
                                    </div>
                                    <div class="d-baris-kanan">
                                        <?= $sisa < 0 ? d_chip('Kedaluwarsa', 'merah') : d_chip('Hampir habis', $sisa <= 30 ? 'kuning' : 'biru') ?>
                                        <small class="<?= $sisa < 0 ? 'teks-merah' : ($sisa <= 30 ? 'teks-kuning' : 'redup') ?>"><?= $sisa < 0 ? (-$sisa) . ' hari lalu' : ($sisa === 0 ? 'Hari ini' : $sisa . ' hari lagi') ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulLog">
                    <?= d_kepala('judulLog', 'Aktivitas sistem terbaru', 'Jejak audit tindakan penting') ?>
                    <?php if (!$log): ?>
                        <div class="d-kosong"><?= ikon('riwayat') ?><span>Belum ada aktivitas tercatat.</span></div>
                    <?php else: ?>
                        <ul class="d-log">
                            <?php foreach ($log as $l): ?>
                                <?php
                                [$labelEnt, $ikonEnt, $warnaEnt] = DASBOR_ENTITAS[$l['entitas']] ?? ['Sistem', 'info', 'netral'];
                                $teks = $l['detail'] ?: (DASBOR_AKSI[$l['aksi']] ?? ucfirst((string) $l['aksi'])) . ' data ' . mb_strtolower($labelEnt);
                                ?>
                                <li>
                                    <span class="d-ikon kecil <?= $warnaEnt ?>"><?= ikon($ikonEnt) ?></span>
                                    <div>
                                        <b><?= e((string) $teks) ?></b>
                                        <small><?= e((string) ($l['nama_lengkap'] ?? 'Sistem')) ?> · <span class="d-label"><?= e($labelEnt) ?></span> · <time datetime="<?= e(str_replace(' ', 'T', (string) $l['created_at'])) ?>"><?= e(waktu_notif((string) $l['created_at'])) ?></time></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="kartu" aria-labelledby="judulAgendaAdmin">
                    <?= d_kepala('judulAgendaAdmin', 'Agenda klub', 'Latihan, laga, rapat & perjalanan', 'jadwal.php') ?>
                    <?= d_daftar_agenda($agenda) ?>
                </section>
            </div>

            <div class="kisi-2">
                <section class="kartu" aria-labelledby="judulMenunggu">
                    <?= d_kepala('judulMenunggu', 'Pengajuan menunggu persetujuan', $k['menunggu'] ? 'Diurutkan dari yang paling lama menunggu' : 'Semua pengajuan sudah ditinjau', 'pengajuan.php') ?>
                    <?php if (!$menunggu): ?>
                        <div class="d-kosong"><?= ikon('centang-ganda') ?><span>Tidak ada pengajuan yang menunggu.</span></div>
                    <?php else: ?>
                        <ul class="d-baris">
                            <?php foreach ($menunggu as $p): ?>
                                <?php $lama = selisih_hari(substr((string) $p['created_at'], 0, 10)); $hal = in_array($p['jenis'], ['cuti', 'sakit'], true) ? 'pengajuan.php' : 'pengajuan.php?hal=izin'; ?>
                                <li>
                                    <div>
                                        <a href="<?= e($hal) ?>"><b><?= e((string) $p['nama_lengkap']) ?></b></a>
                                        <small><?= e((string) $p['judul']) ?><?= $p['tanggal_mulai'] ? ' · ' . e(tanggal_id((string) $p['tanggal_mulai'])) . ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai'] ? ' – ' . e(tanggal_id((string) $p['tanggal_selesai'])) : '') : '' ?></small>
                                    </div>
                                    <div class="d-baris-kanan">
                                        <?= d_chip('Menunggu', 'kuning') ?>
                                        <small class="<?= $lama >= 3 ? 'teks-merah' : 'redup' ?>"><?= $lama <= 0 ? 'Masuk hari ini' : 'Menunggu ' . $lama . ' hari' ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="kartu" id="pengumuman" aria-labelledby="judulUmumAdmin">
                    <?= d_kepala('judulUmumAdmin', 'Pengumuman klub', 'Tampil di dashboard karyawan sesuai sasaran', '', '',
                        '<button type="button" class="tombol kecil utama" data-buka-dialog="dialogPengumuman">' . ikon('tambah') . 'Tulis</button>') ?>
                    <?= d_daftar_pengumuman($umum, true) ?>
                </section>
            </div>
<?php
dialog_pengumuman();
