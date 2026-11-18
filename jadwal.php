<?php
declare(strict_types=1);

// Jadwal klub.
//   jadwal.php                   -> Kalender (bulan/minggu, filter jenis)
//   jadwal.php?hal=latihan       -> Jadwal Latihan (agenda per minggu + kehadiran)
//   jadwal.php?hal=pertandingan  -> Jadwal Pertandingan (laga berikutnya, jadwal, hasil)
//   jadwal.php?hal=rapat         -> Rapat & perjalanan tandang
// Pemain tidak melihat agenda khusus staf (peserta = 'staf').

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const JENIS_JADWAL = [
    'latihan'      => ['Latihan', 'hijau', 'peluit'],
    'pertandingan' => ['Pertandingan', 'kuning', 'trofi'],
    'rapat'        => ['Rapat', 'biru', 'rapat'],
    'perjalanan'   => ['Perjalanan tandang', 'ungu', 'bus'],
    'lainnya'      => ['Lainnya', 'netral', 'kalender'],
];
const FILTER_JENIS = [
    'semua' => ['Semua', []], 'latihan' => ['Latihan', ['latihan']],
    'pertandingan' => ['Pertandingan', ['pertandingan']], 'rapat' => ['Rapat & tandang', ['rapat', 'perjalanan']],
];
const LABEL_KEHADIRAN = [
    'hadir' => ['Hadir', 'hijau'], 'terlambat' => ['Terlambat', 'kuning'], 'izin' => ['Izin', 'biru'],
    'sakit' => ['Sakit', 'ungu'], 'alpa' => ['Alpa', 'merah'],
];
const LABEL_PESERTA = ['semua' => 'Seluruh tim', 'pemain' => 'Pemain & pelatih', 'staf' => 'Staf saja'];

$hal = (string) ($_GET['hal'] ?? 'kalender');
if (!in_array($hal, ['kalender', 'latihan', 'pertandingan', 'rapat'], true)) {
    $hal = 'kalender';
}
$uid      = (int) $_SESSION['user_id'];
$pemain   = ($_SESSION['role'] ?? '') === 'pemain';
$zona     = new DateTimeZone(ZONA_WAKTU);
$sekarang = new DateTimeImmutable('now', $zona);
$hariIni  = hari_ini();
$dbGagal  = false;

/** Ambil agenda yang terlihat oleh pengguna dalam rentang [dari, sampai). */
function ambil_agenda(string $dari, string $sampai, array $jenis = [], string $urut = 'ASC', ?int $batas = null): array
{
    global $uid, $pemain;
    $sql = "SELECT j.*, p.lawan, p.kandang, p.kompetisi, p.musim, p.skor_klub, p.skor_lawan, p.status AS status_laga,
                   kh.status AS hadir_status, kh.catatan AS hadir_catatan
              FROM jadwal j
              LEFT JOIN pertandingan p ON p.jadwal_id = j.id
              LEFT JOIN kehadiran kh   ON kh.jadwal_id = j.id AND kh.user_id = ?
             WHERE j.waktu_mulai < ? AND COALESCE(j.waktu_selesai, j.waktu_mulai) >= ?";
    $param = [$uid, $sampai, $dari];
    if ($pemain) {
        $sql .= " AND j.peserta <> 'staf'";
    }
    if ($jenis) {
        $sql .= ' AND j.jenis IN (' . implode(',', array_fill(0, count($jenis), '?')) . ')';
        array_push($param, ...$jenis);
    }
    $sql .= ' ORDER BY j.waktu_mulai ' . ($urut === 'DESC' ? 'DESC' : 'ASC') . ($batas ? ' LIMIT ' . (int) $batas : '');
    $stmt = db()->prepare($sql);
    $stmt->execute($param);
    return $stmt->fetchAll();
}

/** Tanggal (Y-m-d) yang dicakup satu agenda (agenda beberapa hari muncul di tiap harinya). */
function hari_agenda(array $a): array
{
    $mulai = new DateTimeImmutable(substr((string) $a['waktu_mulai'], 0, 10));
    $akhir = new DateTimeImmutable(substr((string) ($a['waktu_selesai'] ?: $a['waktu_mulai']), 0, 10));
    $hasil = [];
    for ($d = $mulai; $d <= $akhir && count($hasil) < 31; $d = $d->modify('+1 day')) {
        $hasil[] = $d->format('Y-m-d');
    }
    return $hasil;
}

function teks_waktu(array $a): string
{
    $mulai = (string) $a['waktu_mulai'];
    $selesai = (string) ($a['waktu_selesai'] ?? '');
    if ($selesai !== '' && substr($selesai, 0, 10) !== substr($mulai, 0, 10)) {
        return hari_tanggal($mulai) . ' ' . jam($mulai) . ' – ' . hari_tanggal($selesai) . ' ' . jam($selesai);
    }
    return hari_tanggal($mulai) . ' · ' . jam($mulai) . ($selesai !== '' ? '–' . jam($selesai) : '') . ' WIB';
}

function badge_jenis(string $jenis): string
{
    [$teks, $kelas] = JENIS_JADWAL[$jenis] ?? JENIS_JADWAL['lainnya'];
    return '<span class="badge ' . $kelas . '">' . e($teks) . '</span>';
}

function chip_hadir(?string $status): string
{
    if ($status === null) {
        return '';
    }
    [$teks, $kelas] = LABEL_KEHADIRAN[$status] ?? [$status, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

/** Tombol agenda kecil di kalender (membuka dialog detail). */
function chip_agenda(array $a, bool $lengkap = false, string $hari = ''): string
{
    [, $kelas] = JENIS_JADWAL[$a['jenis']] ?? JENIS_JADWAL['lainnya'];
    // Agenda beberapa hari: jam hanya di hari pertama, hari berikutnya ditandai "lanjutan".
    $lanjutan = $hari !== '' && $hari !== substr((string) $a['waktu_mulai'], 0, 10);
    $jamTeks = jam((string) $a['waktu_mulai']);
    $label = $lanjutan ? '<span class="lanjut">↳</span>' : '<b>' . e($jamTeks) . '</b>';
    return '<button type="button" class="agenda ' . $kelas . '" data-buka-dialog="agenda-' . (int) $a['id'] . '" title="'
        . e(($lanjutan ? 'Lanjutan: ' : $jamTeks . ' ') . $a['judul']) . '">' . $label . ' ' . e((string) $a['judul'])
        . ($lengkap && $a['lokasi'] !== '' ? '<small>' . e((string) $a['lokasi']) . '</small>' : '') . '</button>';
}

/** Dialog detail untuk tiap agenda yang tampil. */
function dialog_agenda(array $daftar): void
{
    global $sekarang;
    $sudah = [];
    foreach ($daftar as $a) {
        if (isset($sudah[$a['id']])) {
            continue;
        }
        $sudah[$a['id']] = true;
        [$teks, $kelas, $ik] = JENIS_JADWAL[$a['jenis']] ?? JENIS_JADWAL['lainnya'];
        $lewat = new DateTimeImmutable((string) $a['waktu_mulai'], new DateTimeZone(ZONA_WAKTU)) < $sekarang;
        ?>
            <dialog class="modal" id="agenda-<?= (int) $a['id'] ?>" aria-labelledby="judul-agenda-<?= (int) $a['id'] ?>">
                <div class="modal-kepala">
                    <span class="ikon-judul warna-<?= $kelas ?>"><?= ikon($ik) ?></span>
                    <div>
                        <h2 id="judul-agenda-<?= (int) $a['id'] ?>"><?= e((string) $a['judul']) ?></h2>
                        <p><?= badge_jenis((string) $a['jenis']) ?><?= (int) $a['wajib'] ? '' : ' <span class="badge netral">Opsional</span>' ?></p>
                    </div>
                    <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-isi">
                    <ul class="detail-agenda">
                        <li><?= ikon('jam') ?><span><?= e(teks_waktu($a)) ?></span></li>
                        <?php if ($a['lokasi'] !== ''): ?><li><?= ikon('pin') ?><span><?= e((string) $a['lokasi']) ?></span></li><?php endif; ?>
                        <li><?= ikon('orang') ?><span><?= e(LABEL_PESERTA[$a['peserta']] ?? '') ?></span></li>
                        <?php if ($a['jenis'] === 'pertandingan' && $a['lawan'] !== null): ?>
                            <li><?= ikon('trofi') ?><span><?= e((string) $a['kompetisi']) ?> · <?= (int) $a['kandang'] ? 'Kandang' : 'Tandang' ?> vs <?= e((string) $a['lawan']) ?>
                                <?php if ($a['skor_klub'] !== null): ?> · <b>Skor <?= (int) $a['skor_klub'] ?>–<?= (int) $a['skor_lawan'] ?></b><?php endif; ?></span></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($a['deskripsi'])): ?><p class="deskripsi-agenda"><?= e((string) $a['deskripsi']) ?></p><?php endif; ?>
                    <?php if ($lewat): ?>
                        <div class="hadir-saya">
                            <span>Kehadiran Anda</span>
                            <?= $a['hadir_status'] !== null ? chip_hadir((string) $a['hadir_status']) : '<span class="redup">Tidak dicatat</span>' ?>
                            <?php if (!empty($a['hadir_catatan'])): ?><small><?= e((string) $a['hadir_catatan']) ?></small><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-kaki">
                    <button type="button" class="tombol" data-tutup-dialog>Tutup</button>
                </div>
            </dialog>
        <?php
    }
}

/** Hitung mundur teks: "5 hari 3 jam lagi". */
function hitung_mundur(string $waktu): string
{
    global $sekarang;
    $t = new DateTimeImmutable($waktu, new DateTimeZone(ZONA_WAKTU));
    if ($t <= $sekarang) {
        return 'Sedang berlangsung';
    }
    $d = $sekarang->diff($t);
    return ($d->days ? $d->days . ' hari ' : '') . $d->h . ' jam lagi';
}

// ---------- Rentang waktu ----------
$tglUrl = (string) ($_GET['tgl'] ?? '');
$acuan  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglUrl) && strtotime($tglUrl) ? new DateTimeImmutable($tglUrl) : $hariIni;
$bulanUrl = (string) ($_GET['bulan'] ?? '');
if (preg_match('/^\d{4}-\d{2}$/', $bulanUrl) && strtotime($bulanUrl . '-01')) {
    $acuan = new DateTimeImmutable($bulanUrl . '-01');
}
$mode  = ($_GET['mode'] ?? '') === 'minggu' || $hal === 'latihan' ? 'minggu' : 'bulan';
$jenisPilih = array_key_exists((string) ($_GET['jenis'] ?? ''), FILTER_JENIS) ? (string) $_GET['jenis'] : 'semua';
$senin = $acuan->modify('monday this week');

try {
    // KPI bersama.
    $laga = ambil_agenda($sekarang->format('Y-m-d H:i:s'), '2100-01-01', ['pertandingan'], 'ASC', 1);
    $lagaBerikut = $laga[0] ?? null;

    $stmt = db()->prepare(
        "SELECT COUNT(*) AS total, SUM(k.status IN ('hadir','terlambat')) AS hadir
           FROM kehadiran k JOIN jadwal j ON j.id = k.jadwal_id
          WHERE k.user_id = :u AND j.waktu_mulai >= :dari AND j.waktu_mulai < NOW()"
    );
    $stmt->execute([':u' => $uid, ':dari' => $hariIni->modify('-30 days')->format('Y-m-d')]);
    $rekapHadir = $stmt->fetch();
    $persenHadir = (int) $rekapHadir['total'] > 0 ? (int) round(100 * (int) $rekapHadir['hadir'] / (int) $rekapHadir['total']) : null;

    if ($hal === 'kalender') {
        if ($mode === 'bulan') {
            $awalBulan = $acuan->modify('first day of this month');
            $mulaiGrid = $awalBulan->modify('monday this week');
            $akhirGrid = $awalBulan->modify('last day of this month')->modify('sunday this week')->modify('+1 day');
        } else {
            $mulaiGrid = $senin;
            $akhirGrid = $senin->modify('+7 days');
        }
        $agenda = ambil_agenda($mulaiGrid->format('Y-m-d'), $akhirGrid->format('Y-m-d'), FILTER_JENIS[$jenisPilih][1]);
        $perHari = [];
        foreach ($agenda as $a) {
            foreach (hari_agenda($a) as $d) {
                $perHari[$d][] = $a;
            }
        }
        $mendatang = ambil_agenda($sekarang->format('Y-m-d H:i:s'), '2100-01-01', FILTER_JENIS[$jenisPilih][1], 'ASC', 6);
        $mingguIni = ambil_agenda($hariIni->modify('monday this week')->format('Y-m-d'), $hariIni->modify('monday this week')->modify('+7 days')->format('Y-m-d'));
        $latihanBulan = ambil_agenda($hariIni->modify('first day of this month')->format('Y-m-d'), $hariIni->modify('first day of next month')->format('Y-m-d'), ['latihan']);
    } elseif ($hal === 'latihan') {
        $agenda = ambil_agenda($senin->format('Y-m-d'), $senin->modify('+7 days')->format('Y-m-d'), ['latihan']);
        $latihanBulan = ambil_agenda($hariIni->modify('first day of this month')->format('Y-m-d'), $hariIni->modify('first day of next month')->format('Y-m-d'), ['latihan']);
        $stmt = db()->prepare(
            "SELECT COUNT(*) AS total, SUM(k.status IN ('hadir','terlambat')) AS hadir, SUM(k.status = 'terlambat') AS telat
               FROM kehadiran k JOIN jadwal j ON j.id = k.jadwal_id
              WHERE k.user_id = :u AND j.jenis = 'latihan' AND j.waktu_mulai >= :dari"
        );
        $stmt->execute([':u' => $uid, ':dari' => $hariIni->modify('-30 days')->format('Y-m-d')]);
        $hadirLatihan = $stmt->fetch();
        $berikut = ambil_agenda($sekarang->format('Y-m-d H:i:s'), '2100-01-01', ['latihan'], 'ASC', 1)[0] ?? null;
    } elseif ($hal === 'pertandingan') {
        $mendatang = ambil_agenda($sekarang->format('Y-m-d H:i:s'), '2100-01-01', ['pertandingan']);
        $hasil = ambil_agenda('2000-01-01', $sekarang->format('Y-m-d H:i:s'), ['pertandingan'], 'DESC', 10);
        $perjalanan = ambil_agenda($sekarang->format('Y-m-d'), '2100-01-01', ['perjalanan']);
        $stmt = db()->query(
            "SELECT COUNT(*) AS main, COALESCE(SUM(skor_klub > skor_lawan),0) AS m, COALESCE(SUM(skor_klub = skor_lawan),0) AS s,
                    COALESCE(SUM(skor_klub < skor_lawan),0) AS k
               FROM pertandingan WHERE status = 'selesai' AND musim = (SELECT MAX(musim) FROM pertandingan)"
        );
        $rekor = $stmt->fetch();
    } else {
        $semuaRapat = ambil_agenda('2000-01-01', '2100-01-01', ['rapat', 'perjalanan'], 'DESC');
        $tab = ($_GET['tab'] ?? '') === 'selesai' ? 'selesai' : 'mendatang';
        $mendatang = array_reverse(array_values(array_filter($semuaRapat, static fn($a) => (string) ($a['waktu_selesai'] ?: $a['waktu_mulai']) >= $sekarang->format('Y-m-d H:i:s'))));
        $selesai = array_values(array_filter($semuaRapat, static fn($a) => (string) ($a['waktu_selesai'] ?: $a['waktu_mulai']) < $sekarang->format('Y-m-d H:i:s')));
    }
} catch (PDOException $ex) {
    error_log('Jadwal gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

$judulMenu = ['kalender' => 'Kalender', 'latihan' => 'Jadwal Latihan', 'pertandingan' => 'Jadwal Pertandingan', 'rapat' => 'Rapat & Tandang'][$hal];
$judul = ['kalender' => 'Jadwal Klub', 'latihan' => 'Jadwal Latihan', 'pertandingan' => 'Jadwal Pertandingan', 'rapat' => 'Rapat & Perjalanan Tandang'][$hal];
$sub = [
    'kalender' => 'Latihan, pertandingan, rapat, dan perjalanan tandang.',
    'latihan' => 'Sesi latihan per minggu beserta catatan kehadiran Anda.',
    'pertandingan' => 'Laga berikutnya, jadwal mendatang, dan hasil terakhir.',
    'rapat' => 'Rapat tim, briefing, dan rencana perjalanan laga tandang.',
][$hal];
halaman_awal($judul, $judulMenu);
kepala_halaman(['kalender' => 'kalender', 'latihan' => 'peluit', 'pertandingan' => 'trofi', 'rapat' => 'bus'][$hal], $judul, $sub,
    '<a class="tombol kecil" href="jadwal_ics.php" title="Unduh file .ics untuk Google Calendar, Outlook, atau kalender ponsel">' . ikon('sinkron') . 'Sinkron ke kalender</a>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Jadwal belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

// =====================================================================
// A. Kalender
// =====================================================================
if ($hal === 'kalender'):
    $param = static fn(array $ubah) => e(url_dengan($ubah));
    if ($mode === 'bulan') {
        $sebelum = $acuan->modify('first day of previous month')->format('Y-m');
        $berikut = $acuan->modify('first day of next month')->format('Y-m');
        $judulRentang = NAMA_BULAN[(int) $acuan->format('n')] . ' ' . $acuan->format('Y');
        $navSebelum = $param(['bulan' => $sebelum, 'tgl' => null]);
        $navBerikut = $param(['bulan' => $berikut, 'tgl' => null]);
    } else {
        $judulRentang = tanggal_id($senin->format('Y-m-d')) . ' – ' . tanggal_id($senin->modify('+6 days')->format('Y-m-d'));
        $navSebelum = $param(['tgl' => $senin->modify('-7 days')->format('Y-m-d'), 'bulan' => null]);
        $navBerikut = $param(['tgl' => $senin->modify('+7 days')->format('Y-m-d'), 'bulan' => null]);
    }
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan jadwal">
                <?= kartu_kpi('Agenda minggu ini', angka(count($mingguIni)), 'kalender', 'hijau',
                    e(count(array_filter($mingguIni, static fn($a) => $a['jenis'] === 'latihan')) . ' latihan · ' . count(array_filter($mingguIni, static fn($a) => $a['jenis'] !== 'latihan')) . ' lainnya')) ?>
                <?= kartu_kpi('Laga berikutnya', $lagaBerikut ? 'vs ' . (string) $lagaBerikut['lawan'] : '-', 'trofi', 'kuning',
                    $lagaBerikut ? e(tanggal_id((string) $lagaBerikut['waktu_mulai'])) . ' · ' . e(hitung_mundur((string) $lagaBerikut['waktu_mulai'])) : 'Belum dijadwalkan') ?>
                <?= kartu_kpi('Latihan bulan ini', angka(count($latihanBulan)), 'peluit', 'biru',
                    e(angka(array_sum(array_map(static fn($a) => $a['waktu_selesai'] ? (strtotime((string) $a['waktu_selesai']) - strtotime((string) $a['waktu_mulai'])) / 3600 : 0, $latihanBulan)))) . ' jam terjadwal') ?>
                <?= kartu_kpi('Kehadiran 30 hari', $persenHadir !== null ? $persenHadir . '%' : '-', 'centang-kal', 'ungu',
                    '<a href="kehadiran.php">Lihat rincian</a>') ?>
            </section>

            <section class="kartu kalender-kartu" aria-label="Kalender <?= e($judulRentang) ?>">
                <div class="kalender-alat">
                    <div class="kalender-nav">
                        <a class="tombol-ikon-kecil" href="<?= $navSebelum ?>" aria-label="Sebelumnya"><?= ikon('kiri') ?></a>
                        <a class="tombol kecil" href="<?= $param(['bulan' => null, 'tgl' => null]) ?>">Hari ini</a>
                        <a class="tombol-ikon-kecil" href="<?= $navBerikut ?>" aria-label="Berikutnya"><?= ikon('kanan') ?></a>
                        <h2><?= e($judulRentang) ?></h2>
                    </div>
                    <div class="kalender-pilihan">
                        <nav class="segmen" aria-label="Filter jenis agenda">
                            <?php foreach (FILTER_JENIS as $k => [$lbl]): ?>
                                <a href="<?= $param(['jenis' => $k === 'semua' ? null : $k]) ?>"<?= $k === $jenisPilih ? ' class="aktif" aria-current="true"' : '' ?>><?= e($lbl) ?></a>
                            <?php endforeach; ?>
                        </nav>
                        <nav class="segmen" aria-label="Tampilan">
                            <a href="<?= $param(['mode' => null, 'tgl' => null, 'bulan' => $acuan->format('Y-m')]) ?>"<?= $mode === 'bulan' ? ' class="aktif" aria-current="true"' : '' ?>>Bulan</a>
                            <a href="<?= $param(['mode' => 'minggu', 'bulan' => null, 'tgl' => ($mode === 'bulan' && $acuan->format('Y-m') !== $hariIni->format('Y-m') ? $acuan->format('Y-m-d') : $hariIni->format('Y-m-d'))]) ?>"<?= $mode === 'minggu' ? ' class="aktif" aria-current="true"' : '' ?>>Minggu</a>
                        </nav>
                    </div>
                </div>

                <div class="kalender <?= $mode ?>" role="grid">
                    <div class="kal-kepala" role="row">
                        <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $i => $h): ?>
                            <span role="columnheader"><?= $mode === 'minggu' ? e($h) . ' <b>' . $senin->modify("+$i days")->format('j') . '</b>' : e($h) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="kal-badan">
                        <?php for ($d = $mulaiGrid; $d < $akhirGrid; $d = $d->modify('+1 day')): ?>
                            <?php
                            $kunci = $d->format('Y-m-d');
                            $isi = $perHari[$kunci] ?? [];
                            $luar = $mode === 'bulan' && $d->format('m') !== $acuan->format('m');
                            $batas = $mode === 'bulan' ? 3 : 99;
                            ?>
                            <div class="kal-hari<?= $luar ? ' luar' : '' ?><?= $kunci === $hariIni->format('Y-m-d') ? ' hari-ini' : '' ?><?= $d->format('N') >= 6 ? ' akhir-pekan' : '' ?>" role="gridcell"
                                 aria-label="<?= e(hari_tanggal($kunci, true)) ?>, <?= count($isi) ?> agenda">
                                <?php if ($mode === 'bulan'): ?><span class="kal-tgl"><?= $d->format('j') ?></span><?php endif; ?>
                                <?php foreach (array_slice($isi, 0, $batas) as $a): ?>
                                    <?= chip_agenda($a, $mode === 'minggu', $kunci) ?>
                                <?php endforeach; ?>
                                <?php if (count($isi) > $batas): ?>
                                    <a class="agenda-lagi" href="<?= $param(['mode' => 'minggu', 'tgl' => $kunci, 'bulan' => null]) ?>">+<?= count($isi) - $batas ?> lainnya</a>
                                <?php endif; ?>
                                <?php if ($mode === 'minggu' && !$isi): ?><span class="istirahat">Tidak ada agenda</span><?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="kal-legenda">
                    <?php foreach (JENIS_JADWAL as $k => [$lbl, $kelas]): ?>
                        <?php if ($k === 'lainnya') { continue; } ?>
                        <span class="titik-<?= $kelas ?>"><?= e($lbl) ?></span>
                    <?php endforeach; ?>
                    <span class="redup">Klik agenda untuk melihat detail.</span>
                </div>
            </section>

            <section class="kartu" aria-labelledby="judulMendatang">
                <div class="kepala-kartu"><h2 id="judulMendatang">Agenda mendatang</h2><p>6 agenda terdekat<?= $jenisPilih !== 'semua' ? ' · ' . e(FILTER_JENIS[$jenisPilih][0]) : '' ?></p></div>
                <?php if (!$mendatang): ?>
                    <div class="kosong kecil">Tidak ada agenda mendatang.</div>
                <?php else: ?>
                    <ul class="daftar-agenda">
                        <?php foreach ($mendatang as $a): ?>
                            <?php [, $kelas, $ik] = JENIS_JADWAL[$a['jenis']] ?? JENIS_JADWAL['lainnya']; ?>
                            <li>
                                <button type="button" data-buka-dialog="agenda-<?= (int) $a['id'] ?>">
                                    <span class="tgl-mini warna-<?= $kelas ?>"><b><?= substr((string) $a['waktu_mulai'], 8, 2) ?></b><?= e(mb_substr(NAMA_BULAN[(int) substr((string) $a['waktu_mulai'], 5, 2)], 0, 3)) ?></span>
                                    <span class="isi-agenda"><b><?= e((string) $a['judul']) ?></b><small><?= e(teks_waktu($a)) ?><?= $a['lokasi'] !== '' ? ' · ' . e((string) $a['lokasi']) : '' ?></small></span>
                                    <?= badge_jenis((string) $a['jenis']) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
<?php
    dialog_agenda(array_merge($agenda, $mendatang));

// =====================================================================
// B. Jadwal Latihan
// =====================================================================
elseif ($hal === 'latihan'):
    $param = static fn(array $ubah) => e(url_dengan($ubah));
    $jamBulan = array_sum(array_map(static fn($a) => $a['waktu_selesai'] ? (strtotime((string) $a['waktu_selesai']) - strtotime((string) $a['waktu_mulai'])) / 3600 : 0, $latihanBulan));
    $pctLatihan = (int) $hadirLatihan['total'] > 0 ? (int) round(100 * (int) $hadirLatihan['hadir'] / (int) $hadirLatihan['total']) : null;
    $perHari = [];
    foreach ($agenda as $a) {
        $perHari[substr((string) $a['waktu_mulai'], 0, 10)][] = $a;
    }
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan latihan">
                <?= kartu_kpi('Latihan minggu ini', angka(count($agenda)), 'peluit', 'hijau', e(tanggal_id($senin->format('Y-m-d')) . ' – ' . tanggal_id($senin->modify('+6 days')->format('Y-m-d')))) ?>
                <?= kartu_kpi('Sesi berikutnya', $berikut ? jam((string) $berikut['waktu_mulai']) . ' WIB' : '-', 'jam', 'biru',
                    $berikut ? e(hari_tanggal((string) $berikut['waktu_mulai']) . ' · ' . $berikut['judul']) : 'Belum dijadwalkan') ?>
                <?= kartu_kpi('Volume bulan ini', desimal($jamBulan, 0) . ' jam', 'stopwatch', 'ungu', e(count($latihanBulan) . ' sesi di ' . NAMA_BULAN[(int) $hariIni->format('n')])) ?>
                <?= kartu_kpi('Kehadiran latihan', $pctLatihan !== null ? $pctLatihan . '%' : '-', 'centang-kal', 'kuning',
                    '30 hari terakhir · ' . e((int) $hadirLatihan['telat'] . ' kali terlambat')) ?>
            </section>

            <section class="kartu" aria-label="Latihan minggu ini">
                <div class="kalender-alat">
                    <div class="kalender-nav">
                        <a class="tombol-ikon-kecil" href="<?= $param(['tgl' => $senin->modify('-7 days')->format('Y-m-d')]) ?>" aria-label="Minggu sebelumnya"><?= ikon('kiri') ?></a>
                        <a class="tombol kecil" href="<?= $param(['tgl' => null]) ?>">Minggu ini</a>
                        <a class="tombol-ikon-kecil" href="<?= $param(['tgl' => $senin->modify('+7 days')->format('Y-m-d')]) ?>" aria-label="Minggu berikutnya"><?= ikon('kanan') ?></a>
                        <h2><?= e(tanggal_id($senin->format('Y-m-d')) . ' – ' . tanggal_id($senin->modify('+6 days')->format('Y-m-d'))) ?></h2>
                    </div>
                    <a class="tombol kecil" href="latihan.php"><?= ikon('beban') ?>Program latihan saya</a>
                </div>
                <ol class="agenda-minggu">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <?php $d = $senin->modify("+$i days"); $kunci = $d->format('Y-m-d'); $isi = $perHari[$kunci] ?? []; ?>
                        <li class="<?= $kunci === $hariIni->format('Y-m-d') ? 'hari-ini' : '' ?><?= $isi ? '' : ' libur' ?>">
                            <div class="hari-label">
                                <b><?= e(NAMA_HARI[(int) $d->format('w')]) ?></b>
                                <span><?= e(tanggal_id($kunci)) ?></span>
                                <?php if ($kunci === $hariIni->format('Y-m-d')): ?><small class="pil-hari-ini">Hari ini</small><?php endif; ?>
                            </div>
                            <div class="hari-isi">
                                <?php if (!$isi): ?>
                                    <span class="istirahat">Tidak ada latihan — istirahat / laga / perjalanan</span>
                                <?php endif; ?>
                                <?php foreach ($isi as $a): ?>
                                    <button type="button" class="sesi-latihan" data-buka-dialog="agenda-<?= (int) $a['id'] ?>">
                                        <span class="sesi-jam"><?= e(jam((string) $a['waktu_mulai'])) ?><small><?= e(jam((string) $a['waktu_selesai'])) ?></small></span>
                                        <span class="sesi-teks"><b><?= e((string) $a['judul']) ?></b><small><?= ikon('pin') ?><?= e((string) $a['lokasi']) ?></small>
                                            <?php if (!empty($a['deskripsi'])): ?><small class="redup"><?= e((string) $a['deskripsi']) ?></small><?php endif; ?></span>
                                        <?= chip_hadir($a['hadir_status']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php endfor; ?>
                </ol>
            </section>
<?php
    dialog_agenda($agenda);

// =====================================================================
// C. Jadwal Pertandingan
// =====================================================================
elseif ($hal === 'pertandingan'):
    $laga1 = $mendatang[0] ?? null;
    ?>
            <?php if ($laga1): ?>
                <section class="kartu laga-hero" aria-label="Laga berikutnya">
                    <div class="laga-info">
                        <small>Laga berikutnya · <?= e((string) $laga1['kompetisi']) ?></small>
                        <div class="laga-tim">
                            <?php $kandang = (int) $laga1['kandang']; ?>
                            <span class="tim<?= $kandang ? ' kita' : '' ?>"><b><?= e($kandang ? NAMA_KLUB : (string) $laga1['lawan']) ?></b><small>Tuan rumah</small></span>
                            <span class="vs">VS</span>
                            <span class="tim<?= $kandang ? '' : ' kita' ?>"><b><?= e($kandang ? (string) $laga1['lawan'] : NAMA_KLUB) ?></b><small>Tamu</small></span>
                        </div>
                        <ul class="detail-agenda sebaris">
                            <li><?= ikon('kalender') ?><span><?= e(hari_tanggal((string) $laga1['waktu_mulai'], true)) ?></span></li>
                            <li><?= ikon('jam') ?><span>Kick-off <?= e(jam((string) $laga1['waktu_mulai'])) ?> WIB</span></li>
                            <li><?= ikon('pin') ?><span><?= e((string) $laga1['lokasi']) ?></span></li>
                        </ul>
                    </div>
                    <div class="hitung-mundur" data-hitung-mundur="<?= e((new DateTimeImmutable((string) $laga1['waktu_mulai'], $zona))->format(DATE_ATOM)) ?>">
                        <small>Menuju kick-off</small>
                        <div class="angka-mundur">
                            <?php $d = $sekarang->diff(new DateTimeImmutable((string) $laga1['waktu_mulai'], $zona)); ?>
                            <span><b data-satuan="hari"><?= $d->days ?></b>hari</span>
                            <span><b data-satuan="jam"><?= $d->h ?></b>jam</span>
                            <span><b data-satuan="menit"><?= $d->i ?></b>menit</span>
                        </div>
                        <span class="badge <?= $kandang ? '' : 'ungu' ?>"><?= $kandang ? 'Laga kandang' : 'Laga tandang' ?></span>
                    </div>
                </section>
            <?php endif; ?>

            <section class="kpi-kisi" aria-label="Ringkasan pertandingan">
                <?= kartu_kpi('Rekor musim ' . ($hasil[0]['musim'] ?? ''), $rekor['m'] . '-' . $rekor['s'] . '-' . $rekor['k'], 'trofi', 'hijau', e($rekor['main'] . ' laga · menang-seri-kalah')) ?>
                <?= kartu_kpi('Laga mendatang', angka(count($mendatang)), 'kalender', 'kuning',
                    e(count(array_filter($mendatang, static fn($a) => (int) $a['kandang'])) . ' kandang · ' . count(array_filter($mendatang, static fn($a) => !(int) $a['kandang'])) . ' tandang')) ?>
                <?= kartu_kpi('Perjalanan tandang', angka(count($perjalanan)), 'bus', 'ungu', $perjalanan ? 'Berikutnya ' . e(tanggal_id((string) $perjalanan[0]['waktu_mulai'])) : 'Tidak ada') ?>
                <?php $menangTerakhir = array_slice($hasil, 0, 5); ?>
                <?= kartu_kpi('Form 5 laga', implode(' ', array_map(static fn($a) => $a['skor_klub'] === null ? '-' : ((int) $a['skor_klub'] > (int) $a['skor_lawan'] ? 'M' : ((int) $a['skor_klub'] === (int) $a['skor_lawan'] ? 'S' : 'K')), array_reverse($menangTerakhir))), 'naik', 'biru', 'Dari terlama ke terbaru') ?>
            </section>

            <section class="kartu" aria-labelledby="judulJadwalLaga">
                <div class="kepala-kartu"><h2 id="judulJadwalLaga">Jadwal mendatang</h2><p>Semua kompetisi</p></div>
                <?php if (!$mendatang): ?>
                    <div class="kosong kecil">Belum ada laga yang dijadwalkan.</div>
                <?php else: ?>
                    <ul class="daftar-laga">
                        <?php foreach ($mendatang as $a): ?>
                            <li>
                                <button type="button" data-buka-dialog="agenda-<?= (int) $a['id'] ?>">
                                    <span class="tgl-mini warna-kuning"><b><?= substr((string) $a['waktu_mulai'], 8, 2) ?></b><?= e(mb_substr(NAMA_BULAN[(int) substr((string) $a['waktu_mulai'], 5, 2)], 0, 3)) ?></span>
                                    <span class="isi-agenda"><b><?= (int) $a['kandang'] ? e(NAMA_KLUB) . ' vs ' . e((string) $a['lawan']) : e((string) $a['lawan']) . ' vs ' . e(NAMA_KLUB) ?></b>
                                        <small><?= e(NAMA_HARI[(int) date('w', strtotime((string) $a['waktu_mulai']))]) ?>, <?= e(jam((string) $a['waktu_mulai'])) ?> WIB · <?= e((string) $a['lokasi']) ?></small></span>
                                    <span class="badge netral"><?= e((string) $a['kompetisi']) ?></span>
                                    <span class="badge <?= (int) $a['kandang'] ? '' : 'ungu' ?>"><?= (int) $a['kandang'] ? 'Kandang' : 'Tandang' ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulHasil">
                <div class="kepala-tabel">
                    <h2 id="judulHasil">Hasil terakhir</h2>
                    <?= alat_tabel('Cari lawan...') ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data">
                        <thead><tr><?= th_urut('Tanggal', 'angka', 'desc') ?><?= th_urut('Laga') ?><?= th_urut('Kompetisi') ?><th scope="col">Hasil</th><th scope="col">Kehadiran Anda</th></tr></thead>
                        <tbody>
                            <?php foreach ($hasil as $a): ?>
                                <?php
                                $sk = $a['skor_klub'] !== null ? (int) $a['skor_klub'] : null;
                                $sl = $a['skor_lawan'] !== null ? (int) $a['skor_lawan'] : null;
                                [$huruf, $kelas] = $sk === null ? ['-', 'netral'] : ($sk > $sl ? ['M', 'hijau'] : ($sk === $sl ? ['S', 'netral'] : ['K', 'merah']));
                                ?>
                                <tr>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $a['waktu_mulai']) ?>"><b><?= e(tanggal_id((string) $a['waktu_mulai'])) ?></b><small class="sub-sel"><?= e(jam((string) $a['waktu_mulai'])) ?> WIB</small></td>
                                    <td><b><?= e((string) $a['lawan']) ?></b><small class="sub-sel"><?= (int) $a['kandang'] ? 'Kandang' : 'Tandang' ?> · <?= e((string) $a['lokasi']) ?></small></td>
                                    <td><?= e((string) $a['kompetisi']) ?><small class="sub-sel">Musim <?= e((string) $a['musim']) ?></small></td>
                                    <td><span class="chip-hasil <?= $kelas ?>"><b><?= $huruf ?></b> <?= $sk ?>–<?= $sl ?></span></td>
                                    <td><?= chip_hadir($a['hadir_status']) ?: '<span class="redup">-</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Statistik pribadi per laga ada di <a href="statistik.php">Statistik Pemain</a>.</p>
            </section>
<?php
    dialog_agenda(array_merge($mendatang, $hasil));

// =====================================================================
// D. Rapat & Tandang
// =====================================================================
else:
    $daftar = $tab === 'selesai' ? $selesai : $mendatang;
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan rapat dan perjalanan">
                <?= kartu_kpi('Rapat mendatang', angka(count(array_filter($mendatang, static fn($a) => $a['jenis'] === 'rapat'))), 'rapat', 'biru',
                    ($r = array_values(array_filter($mendatang, static fn($a) => $a['jenis'] === 'rapat'))) ? 'Berikutnya ' . e(tanggal_id((string) $r[0]['waktu_mulai'])) : 'Tidak ada') ?>
                <?= kartu_kpi('Perjalanan tandang', angka(count(array_filter($mendatang, static fn($a) => $a['jenis'] === 'perjalanan'))), 'bus', 'ungu',
                    ($p = array_values(array_filter($mendatang, static fn($a) => $a['jenis'] === 'perjalanan'))) ? 'Berangkat ' . e(tanggal_id((string) $p[0]['waktu_mulai']) . ' ' . jam((string) $p[0]['waktu_mulai'])) : 'Tidak ada') ?>
                <?= kartu_kpi('Hari di perjalanan', angka(array_sum(array_map(static fn($a) => count(hari_agenda($a)), array_filter($mendatang, static fn($a) => $a['jenis'] === 'perjalanan')))), 'kalender', 'kuning', 'Total hari jauh dari markas') ?>
                <?= kartu_kpi('Agenda selesai', angka(count($selesai)), 'tanda', 'hijau', 'Rapat & perjalanan yang sudah lewat') ?>
            </section>

            <nav class="tab-status" aria-label="Filter waktu">
                <a href="<?= e(url_dengan(['tab' => null])) ?>"<?= $tab === 'mendatang' ? ' class="aktif" aria-current="page"' : '' ?>>Mendatang <span class="hitung"><?= count($mendatang) ?></span></a>
                <a href="<?= e(url_dengan(['tab' => 'selesai'])) ?>"<?= $tab === 'selesai' ? ' class="aktif" aria-current="page"' : '' ?>>Selesai <span class="hitung"><?= count($selesai) ?></span></a>
            </nav>

            <?php if (!$daftar): ?>
                <div class="kosong">Tidak ada agenda <?= $tab === 'selesai' ? 'yang sudah selesai' : 'mendatang' ?>.</div>
            <?php else: ?>
                <div class="kisi-rapat">
                    <?php foreach ($daftar as $a): ?>
                        <?php [$teks, $kelas, $ik] = JENIS_JADWAL[$a['jenis']]; $hari = count(hari_agenda($a)); ?>
                        <article class="kartu kartu-rapat warna-tepi-<?= $kelas ?>">
                            <div class="rapat-atas">
                                <span class="ikon-judul warna-<?= $kelas ?>"><?= ikon($ik) ?></span>
                                <div>
                                    <?= badge_jenis((string) $a['jenis']) ?>
                                    <?php if ($a['peserta'] === 'staf'): ?><span class="badge netral">Staf saja</span><?php endif; ?>
                                    <h2><?= e((string) $a['judul']) ?></h2>
                                </div>
                            </div>
                            <ul class="detail-agenda">
                                <li><?= ikon('jam') ?><span><?= e(teks_waktu($a)) ?></span></li>
                                <?php if ($a['lokasi'] !== ''): ?><li><?= ikon('pin') ?><span><?= e((string) $a['lokasi']) ?></span></li><?php endif; ?>
                                <?php if ($a['jenis'] === 'perjalanan'): ?><li><?= ikon('kalender') ?><span><?= $hari ?> hari perjalanan</span></li><?php endif; ?>
                            </ul>
                            <?php if (!empty($a['deskripsi'])): ?><p class="deskripsi-agenda"><?= e((string) $a['deskripsi']) ?></p><?php endif; ?>
                            <div class="rapat-bawah">
                                <?php if ($tab === 'mendatang'): ?>
                                    <span class="redup"><?= e(hitung_mundur((string) $a['waktu_mulai'])) ?></span>
                                <?php else: ?>
                                    <span>Kehadiran Anda: <?= chip_hadir($a['hadir_status']) ?: '<span class="redup">tidak dicatat</span>' ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
<?php
endif;

halaman_akhir();
