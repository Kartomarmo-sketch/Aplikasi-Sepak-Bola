<?php
declare(strict_types=1);

// Program latihan individu/kelompok beserta jadwal sesi mingguan.
// Pemain: program yang diikutinya. Admin & pelatih/medis: semua program, atau program satu pemain (?pemain=ID).

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const LABEL_FOKUS = [
    'fisik' => ['Fisik', 'merah'], 'teknik' => ['Teknik', 'biru'], 'taktik' => ['Taktik', 'ungu'],
    'pemulihan' => ['Pemulihan', ''], 'kiper' => ['Kiper', 'kuning'], 'mental' => ['Mental', 'netral'],
];
const LABEL_STATUS_PROGRAM = [
    'aktif' => ['Berjalan', 'hijau'], 'direncanakan' => ['Direncanakan', 'biru'],
    'selesai' => ['Selesai', 'netral'], 'dibatalkan' => ['Dibatalkan', 'merah'],
];
const LABEL_STATUS_PESERTA = ['berjalan' => ['Berjalan', 'hijau'], 'selesai' => ['Tuntas', 'netral'], 'ditunda' => ['Ditunda', 'kuning']];
const TINGKAT_INTENSITAS = ['rendah' => 1, 'sedang' => 2, 'tinggi' => 3];
const URUTAN_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

$staf    = adalah_staf();
$dbGagal = false;

// Staf (pelatih/medis & admin) dapat membuat, mengubah, dan menghapus program serta menambah progres peserta.
$kembaliProgram = 'latihan.php' . (($q = http_build_query(array_intersect_key(array_filter($_GET, 'is_string'), ['status' => 1, 'pemain' => 1]))) !== '' ? '?' . $q : '');
if ($staf) {
    require __DIR__ . '/inc_kelola_latihan.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        proses_kelola_latihan($kembaliProgram);
    }
}
$pemain  = null;
$daftar  = [];
$program = [];
$sesi    = [];
$peserta = [];

try {
    $pemain = pemain_target();
    $daftar = $staf ? daftar_pemain() : [];

    if ($pemain !== null) {
        $stmt = db()->prepare(
            "SELECT pl.*, u.nama_lengkap AS pembuat,
                    ps.progres_persen, ps.status AS status_peserta, ps.catatan_pelatih
               FROM program_latihan pl
               JOIN program_latihan_peserta ps ON ps.program_id = pl.id AND ps.user_id = :u
               LEFT JOIN users u ON u.id = pl.dibuat_oleh
              ORDER BY FIELD(pl.status, 'aktif', 'direncanakan', 'selesai', 'dibatalkan'), pl.tanggal_mulai DESC"
        );
        $stmt->execute([':u' => (int) $pemain['id']]);
    } else {
        $stmt = db()->query(
            "SELECT pl.*, u.nama_lengkap AS pembuat,
                    (SELECT AVG(ps.progres_persen) FROM program_latihan_peserta ps WHERE ps.program_id = pl.id) AS progres_persen,
                    NULL AS status_peserta, NULL AS catatan_pelatih
               FROM program_latihan pl
               LEFT JOIN users u ON u.id = pl.dibuat_oleh
              ORDER BY FIELD(pl.status, 'aktif', 'direncanakan', 'selesai', 'dibatalkan'), pl.tanggal_mulai DESC"
        );
    }
    $program = $stmt->fetchAll();

    if ($program) {
        $ids   = array_map('intval', array_column($program, 'id'));
        $tanda = implode(',', array_fill(0, count($ids), '?'));

        $stmt = db()->prepare("SELECT * FROM program_latihan_sesi WHERE program_id IN ($tanda) ORDER BY program_id, urutan");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $s) {
            $sesi[(int) $s['program_id']][] = $s;
        }

        $stmt = db()->prepare(
            "SELECT ps.*, u.nama_lengkap, pm.nomor_punggung, pr.foto
               FROM program_latihan_peserta ps
               JOIN users u               ON u.id = ps.user_id
               LEFT JOIN pemain pm        ON pm.user_id = u.id
               LEFT JOIN profil_pemain pr ON pr.user_id = u.id
              WHERE ps.program_id IN ($tanda)
              ORDER BY ps.progres_persen DESC, u.nama_lengkap"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $p) {
            $peserta[(int) $p['program_id']][] = $p;
        }
    }
} catch (PDOException $ex) {
    error_log('Program latihan gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

function badge_peta(array $peta, string $kunci): string
{
    [$teks, $kelas] = $peta[$kunci] ?? [$kunci, 'netral'];
    return '<span class="badge ' . $kelas . '">' . e($teks) . '</span>';
}

/** Tiga batang intensitas (rendah/sedang/tinggi) + teks. */
function intensitas(string $tingkat): string
{
    $n = TINGKAT_INTENSITAS[$tingkat] ?? 1;
    $html = '<span class="intensitas i' . $n . '" title="Intensitas ' . e($tingkat) . '"><span class="batang-int" aria-hidden="true">';
    for ($i = 1; $i <= 3; $i++) {
        $html .= '<i' . ($i <= $n ? ' class="nyala"' : '') . '></i>';
    }
    return $html . '</span>' . e(ucfirst($tingkat)) . '</span>';
}

/** Keterangan waktu program relatif hari ini. */
function waktu_program(array $p): string
{
    $mulai = selisih_hari(hari_ini()->format('Y-m-d'), (string) $p['tanggal_mulai']);
    $sisa  = selisih_hari(hari_ini()->format('Y-m-d'), (string) $p['tanggal_selesai']);
    return match (true) {
        $p['status'] === 'selesai' || $sisa < 0 => 'Berakhir ' . tanggal_id((string) $p['tanggal_selesai']),
        $mulai > 0 => 'Mulai dalam ' . angka($mulai) . ' hari',
        default    => angka($sisa) . ' hari tersisa',
    };
}

// ---------- Filter status (tab) ----------
$hitung = ['semua' => count($program)];
foreach ($program as $p) {
    $hitung[$p['status']] = ($hitung[$p['status']] ?? 0) + 1;
}
$tabStatus = ['aktif' => 'Berjalan', 'direncanakan' => 'Direncanakan', 'selesai' => 'Selesai', 'semua' => 'Semua'];
$status = (string) ($_GET['status'] ?? '');
if (!array_key_exists($status, $tabStatus)) {
    $status = !empty($hitung['aktif']) ? 'aktif' : 'semua';
}
$tampil = $status === 'semua' ? $program : array_values(array_filter($program, static fn($p) => $p['status'] === $status));

// ---------- Jadwal mingguan dari program berjalan ----------
$aktif = array_values(array_filter($program, static fn($p) => $p['status'] === 'aktif' && ($p['status_peserta'] ?? 'berjalan') === 'berjalan'));
$minggu = array_fill_keys(URUTAN_HARI, []);
$menitMinggu = 0;
foreach ($aktif as $p) {
    foreach ($sesi[(int) $p['id']] ?? [] as $s) {
        $minggu[$s['hari']][] = $s + ['program' => $p['judul'], 'fokus' => $p['fokus']];
        $menitMinggu += (int) $s['durasi_menit'];
    }
}
$jumlahSesi = array_sum(array_map('count', $minggu));
$hariIni = URUTAN_HARI[((int) hari_ini()->format('N')) - 1];

$filter = $staf ? ['pemain' => ['label' => 'Pemain', 'opsi' => opsi_pemain($daftar), 'nilai' => $pemain['id'] ?? '']] : [];
$judul  = $staf && $pemain === null ? 'Program Latihan' : ($staf ? 'Program Latihan Pemain' : 'Program Latihan Saya');
halaman_awal($judul, 'Program Latihan');
kepala_halaman('beban', $judul,
    $pemain === null ? 'Semua program latihan tim, peserta, dan kemajuannya.' : 'Program yang diikuti, sesi mingguan, dan catatan dari pelatih.',
    '<div class="aksi-kanan">' . ($filter ? filter_get($filter) : '') . ($staf ? tombol_program_baru() : '') . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data program latihan belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

if ($staf && $pemain !== null) {
    echo strip_pemain($pemain);
}

$progresAktif = array_map(static fn($p) => (float) $p['progres_persen'], $aktif);
$rataProgres = $progresAktif ? array_sum($progresAktif) / count($progresAktif) : null;
?>
            <section class="kpi-kisi" aria-label="Ringkasan program latihan">
                <?= kartu_kpi('Program berjalan', angka(count($aktif)), 'beban', 'hijau',
                    e(($hitung['direncanakan'] ?? 0) . ' direncanakan · ' . ($hitung['selesai'] ?? 0) . ' selesai')) ?>
                <?= kartu_kpi('Rata-rata progres', $rataProgres !== null ? round($rataProgres) . '%' : '-', 'naik', 'biru',
                    $pemain === null ? 'Dari semua peserta program berjalan' : 'Program yang sedang diikuti') ?>
                <?php if ($pemain === null): ?>
                    <?php
                    $terlibat = [];
                    foreach ($aktif as $p) {
                        foreach ($peserta[(int) $p['id']] ?? [] as $ps) {
                            $terlibat[(int) $ps['user_id']] = true;
                        }
                    }
                    ?>
                    <?= kartu_kpi('Pemain terlibat', angka(count($terlibat)) . ' / ' . angka(count($daftar)), 'orang', 'ungu', 'Ikut minimal satu program berjalan') ?>
                <?php else: ?>
                    <?= kartu_kpi('Sesi per minggu', angka($jumlahSesi), 'kalender', 'ungu', 'Dari program yang sedang berjalan') ?>
                <?php endif; ?>
                <?= kartu_kpi('Beban mingguan', angka($menitMinggu) . ' mnt', 'stopwatch', 'kuning',
                    $jumlahSesi ? e(angka($jumlahSesi)) . ' sesi terjadwal' : 'Belum ada sesi') ?>
            </section>

            <section class="kartu" aria-labelledby="judulMinggu">
                <div class="kepala-kartu baris-kepala">
                    <div>
                        <h2 id="judulMinggu">Jadwal sesi mingguan</h2>
                        <p>Gabungan sesi dari program yang sedang berjalan<?= $pemain === null ? ' (seluruh tim)' : '' ?></p>
                    </div>
                </div>
                <?php if ($jumlahSesi === 0): ?>
                    <div class="kosong kecil">Tidak ada sesi terjadwal minggu ini.</div>
                <?php else: ?>
                    <ol class="minggu">
                        <?php foreach ($minggu as $hari => $isi): ?>
                            <li class="<?= $hari === $hariIni ? 'hari-ini' : '' ?><?= $isi ? '' : ' libur' ?>">
                                <span class="nama-hari"><?= e($hari) ?><?= $hari === $hariIni ? ' <small>Hari ini</small>' : '' ?></span>
                                <?php if (!$isi): ?>
                                    <span class="istirahat">Istirahat</span>
                                <?php endif; ?>
                                <?php foreach ($isi as $s): ?>
                                    <div class="sesi fokus-<?= e((string) $s['fokus']) ?>" title="<?= e((string) $s['program']) ?>">
                                        <b><?= e((string) $s['judul']) ?></b>
                                        <small><?= (int) $s['durasi_menit'] ?> mnt · <?= intensitas((string) $s['intensitas']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>

            <div class="baris-tab">
            <nav class="tab-status" aria-label="Filter status program">
                <?php foreach ($tabStatus as $kunci => $label): ?>
                    <a href="<?= e(url_dengan(['status' => $kunci])) ?>"<?= $kunci === $status ? ' class="aktif" aria-current="page"' : '' ?>>
                        <?= e($label) ?> <span class="hitung"><?= (int) ($hitung[$kunci] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <label class="kolom-cari"><?= ikon('cari') ?><input type="search" placeholder="Cari pemain atau program..." aria-label="Cari pemain atau program" data-cari-kartu="kisiProgram"></label>
            </div>

            <?php if (!$tampil): ?>
                <div class="kosong">Tidak ada program dengan status ini.</div>
            <?php else: ?>
            <div class="kisi-program" id="kisiProgram">
            <p class="kosong kecil" data-kosong-kartu hidden>Tidak ada program yang cocok dengan pencarian.</p>
                <?php foreach ($tampil as $p): ?>
                    <?php
                    $daftarSesi = $sesi[(int) $p['id']] ?? [];
                    $menit = array_sum(array_map(static fn($s) => (int) $s['durasi_menit'], $daftarSesi));
                    $prog  = $p['progres_persen'] !== null ? (int) round((float) $p['progres_persen']) : 0;
                    ?>
                    <article class="kartu kartu-program fokus-<?= e((string) $p['fokus']) ?>">
                        <div class="program-atas">
                            <?= badge_peta(LABEL_FOKUS, (string) $p['fokus']) ?>
                            <?= badge_peta(LABEL_STATUS_PROGRAM, (string) $p['status']) ?>
                            <small class="kode"><?= e((string) $p['kode']) ?></small>
                        </div>
                        <h2><?= e((string) $p['judul']) ?></h2>
                        <?php if (!empty($p['deskripsi'])): ?><p class="deskripsi"><?= e((string) $p['deskripsi']) ?></p><?php endif; ?>

                        <ul class="program-meta">
                            <li><?= ikon('kalender') ?><?= e(tanggal_id((string) $p['tanggal_mulai'])) ?> – <?= e(tanggal_id((string) $p['tanggal_selesai'])) ?></li>
                            <li><?= ikon('jam') ?><?= e(waktu_program($p)) ?></li>
                            <li><?= ikon('api') ?><?= intensitas((string) $p['intensitas']) ?></li>
                            <?php if (!empty($p['pembuat'])): ?><li><?= ikon('orang') ?><?= e((string) $p['pembuat']) ?></li><?php endif; ?>
                        </ul>

                        <div class="program-progres">
                            <div class="baris">
                                <span><?= $pemain === null ? 'Rata-rata progres peserta' : 'Progres' ?>
                                    <?php if ($p['status_peserta']): ?> · <?= badge_peta(LABEL_STATUS_PESERTA, (string) $p['status_peserta']) ?><?php endif; ?>
                                </span>
                                <b><?= $prog ?>%</b>
                            </div>
                            <div class="progres" role="progressbar" aria-label="Progres <?= e((string) $p['judul']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $prog ?>">
                                <span style="width: <?= $prog ?>%"></span>
                            </div>
                        </div>

                        <?php if (!empty($p['catatan_pelatih'])): ?>
                            <blockquote class="catatan-pelatih"><?= e((string) $p['catatan_pelatih']) ?></blockquote>
                        <?php endif; ?>

                        <?php if ($pemain === null && !empty($peserta[(int) $p['id']])): ?>
                            <ul class="peserta-daftar" aria-label="Peserta">
                                <?php foreach ($peserta[(int) $p['id']] as $ps): ?>
                                    <li>
                                        <a href="<?= e(url_dengan(['pemain' => $ps['user_id']])) ?>" title="<?= e($ps['nama_lengkap'] . ' · ' . $ps['progres_persen'] . '%') ?>">
                                            <?= avatar_kecil((string) $ps['nama_lengkap'], $ps['foto']) ?>
                                            <span class="peserta-nama"><?= e((string) $ps['nama_lengkap']) ?></span>
                                            <span class="peserta-progres <?= $ps['status'] === 'ditunda' ? 'ditunda' : '' ?>"><?= $ps['status'] === 'ditunda' ? 'Ditunda' : (int) $ps['progres_persen'] . '%' ?></span>
                                        </a>
                                        <?php if ($staf && $p['status'] === 'aktif' && $ps['status'] !== 'ditunda'): ?><?= tombol_progres((int) $p['id'], $ps, $kembaliProgram) ?><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($daftarSesi): ?>
                            <details class="rincian-sesi">
                                <summary><?= ikon('daftar') ?><?= count($daftarSesi) ?> sesi per minggu · <?= angka($menit) ?> menit <?= ikon('bawah', 'ik panah-rincian') ?></summary>
                                <ol>
                                    <?php foreach ($daftarSesi as $s): ?>
                                        <li>
                                            <span class="hari-sesi"><?= e(mb_substr((string) $s['hari'], 0, 3)) ?></span>
                                            <span class="isi-sesi"><b><?= e((string) $s['judul']) ?></b>
                                                <?php if (!empty($s['keterangan'])): ?><small><?= e((string) $s['keterangan']) ?></small><?php endif; ?>
                                            </span>
                                            <span class="durasi-sesi"><?= (int) $s['durasi_menit'] ?> mnt<?= intensitas((string) $s['intensitas']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </details>
                        <?php endif; ?>
                        <?php if ($staf): ?>
                            <div class="kaki-program">
                                <span class="redup"><?= count($peserta[(int) $p['id']] ?? []) ?> peserta</span>
                                <?= aksi_program($p, $peserta[(int) $p['id']] ?? [], $daftarSesi, $kembaliProgram) ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
<?php
if ($staf) {
    dialog_program($kembaliProgram);
}
halaman_akhir();
