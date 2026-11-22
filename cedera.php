<?php
declare(strict_types=1);

// Riwayat cedera & status ketersediaan pemain.
// Pemain: riwayatnya sendiri. Admin & pelatih/medis: seluruh skuad atau satu pemain (?pemain=ID).

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const LABEL_TINGKAT = ['ringan' => ['Ringan', 'netral'], 'sedang' => ['Sedang', 'kuning'], 'berat' => ['Berat', 'merah']];
const LABEL_STATUS_CEDERA = ['dirawat' => ['Dirawat', 'merah'], 'pemulihan' => ['Pemulihan', 'kuning'], 'pulih' => ['Pulih', 'hijau']];
const LABEL_PENYEBAB = ['pertandingan' => 'Pertandingan', 'latihan' => 'Latihan', 'lainnya' => 'Lainnya'];

$staf    = adalah_staf();
$dbGagal = false;

// Staf (pelatih/medis & admin) dapat mencatat, mengubah, dan menghapus cedera serta penanganannya.
$kembaliCedera = 'cedera.php' . (isset($_GET['pemain']) && is_string($_GET['pemain']) && ctype_digit($_GET['pemain']) ? '?pemain=' . $_GET['pemain'] : '');
if ($staf) {
    require __DIR__ . '/inc_kelola_cedera.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        proses_kelola_cedera($kembaliCedera);
    }
}
$pemain  = null;
$daftar  = [];
$cedera  = [];
$perawatan = [];

try {
    $pemain = pemain_target();
    $daftar = $staf ? daftar_pemain() : [];

    $sql = "SELECT c.*, u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.foto, pc.nama_lengkap AS pencatat,
                   (SELECT p.tindakan FROM perawatan_cedera p WHERE p.cedera_id = c.id
                     ORDER BY p.tanggal DESC, p.id DESC LIMIT 1) AS penanganan,
                   (SELECT COUNT(*) FROM perawatan_cedera p WHERE p.cedera_id = c.id) AS jumlah_perawatan
              FROM cedera c
              JOIN users u               ON u.id = c.user_id
              LEFT JOIN pemain pm        ON pm.user_id = u.id
              LEFT JOIN profil_pemain pr ON pr.user_id = u.id
              LEFT JOIN users pc         ON pc.id = c.dicatat_oleh
             WHERE u.role = 'pemain'";
    $param = [];
    if ($pemain !== null) {
        $sql .= ' AND c.user_id = :u';
        $param[':u'] = (int) $pemain['id'];
    }
    $sql .= " ORDER BY c.tanggal_cedera DESC, c.id DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute($param);
    $cedera = $stmt->fetchAll();

    if ($cedera) {
        $ids = array_map('intval', array_column($cedera, 'id'));
        $tanda = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT p.*, u.nama_lengkap AS petugas
               FROM perawatan_cedera p
               LEFT JOIN users u ON u.id = p.petugas_id
              WHERE p.cedera_id IN ($tanda)
              ORDER BY p.tanggal, p.id"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $p) {
            $perawatan[(int) $p['cedera_id']][] = $p;
        }
    }

    // Program pemulihan yang sedang diikuti (untuk tautan di kartu cedera aktif).
    $programPulih = [];
    $stmt = db()->query(
        "SELECT ps.user_id, pl.judul, ps.progres_persen
           FROM program_latihan_peserta ps
           JOIN program_latihan pl ON pl.id = ps.program_id
          WHERE pl.fokus = 'pemulihan' AND pl.status = 'aktif' AND ps.status = 'berjalan'"
    );
    foreach ($stmt->fetchAll() as $r) {
        $programPulih[(int) $r['user_id']] = $r;
    }
} catch (PDOException $ex) {
    error_log('Cedera gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

/** Lama absen (hari) satu cedera: sampai pulih, atau sampai hari ini bila masih aktif. */
function lama_absen(array $c): int
{
    return max(0, selisih_hari((string) $c['tanggal_cedera'], $c['tanggal_pulih'] ?? null));
}

function chip_label(array $peta, string $kunci): string
{
    [$teks, $kelas] = $peta[$kunci] ?? [$kunci, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

$aktif   = array_values(array_filter($cedera, static fn($c) => $c['status'] !== 'pulih'));
$pulih   = array_values(array_filter($cedera, static fn($c) => $c['status'] === 'pulih'));
$musimIni = array_filter($cedera, static fn($c) => $c['tanggal_cedera'] >= awal_musim());
$totalAbsen = array_sum(array_map('lama_absen', $cedera));

$filter = $staf ? ['pemain' => ['label' => 'Pemain', 'opsi' => opsi_pemain($daftar), 'nilai' => $pemain['id'] ?? '']] : [];
$judul  = $staf ? 'Riwayat Cedera' : 'Riwayat Cedera Saya';
halaman_awal($judul, 'Riwayat Cedera');
kepala_halaman('jantung', $judul, 'Catatan cedera, masa pemulihan, dan status ketersediaan pemain.',
    '<div class="aksi-kanan">' . ($filter ? filter_get($filter) : '') . ($staf ? tombol_catat_cedera() : '') . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data cedera belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

if ($staf && $pemain !== null) {
    echo strip_pemain($pemain);
}

// ---------- Kartu angka ----------
echo '<section class="kpi-kisi" aria-label="Ringkasan cedera">';
if ($pemain !== null) {
    if ($aktif) {
        $dirawat = in_array('dirawat', array_column($aktif, 'status'), true);
        echo kartu_kpi('Status ketersediaan', $dirawat ? 'Tidak tersedia' : 'Dalam pemulihan', 'perisai', $dirawat ? 'merah' : 'kuning',
            e(count($aktif) . ' cedera belum pulih'));
    } else {
        echo kartu_kpi('Status ketersediaan', 'Tersedia', 'perisai', 'hijau', 'Siap dipilih untuk latihan & laga');
    }
    echo kartu_kpi('Total cedera', angka(count($cedera)), 'medis', 'biru', e(count($musimIni) . ' di musim ini'));
    echo kartu_kpi('Hari absen', angka($totalAbsen), 'kalender', 'ungu',
        $cedera ? 'Rata-rata ' . e(angka(round($totalAbsen / count($cedera)))) . ' hari per cedera' : 'Belum pernah absen');
    if ($aktif) {
        $hariKe = selisih_hari((string) $aktif[0]['tanggal_cedera']) + 1;
        echo kartu_kpi('Hari pemulihan', 'Hari ke-' . angka($hariKe), 'jam', 'kuning', 'Sejak ' . e(tanggal_id((string) $aktif[0]['tanggal_cedera'])));
    } else {
        $terakhirPulih = $pulih ? max(array_column($pulih, 'tanggal_pulih')) : null;
        echo kartu_kpi('Bebas cedera', $terakhirPulih ? angka(selisih_hari((string) $terakhirPulih)) . ' hari' : 'Sejak bergabung', 'naik', 'hijau',
            $terakhirPulih ? 'Pulih terakhir ' . e(tanggal_id((string) $terakhirPulih)) : 'Tidak ada catatan cedera');
    }
} else {
    $jumlahPemain = count($daftar);
    $pemainCedera = count(array_unique(array_column($aktif, 'user_id')));
    $durasiPulih = array_map(static fn($c) => selisih_hari((string) $c['tanggal_cedera'], (string) $c['tanggal_pulih']), $pulih);
    echo kartu_kpi('Cedera aktif', angka(count($aktif)), 'jantung', count($aktif) ? 'merah' : 'hijau',
        count($aktif) ? 'Perlu pemantauan tim medis' : 'Tidak ada pemain cedera');
    echo kartu_kpi('Pemain tersedia', angka($jumlahPemain - $pemainCedera) . ' / ' . angka($jumlahPemain), 'perisai', 'hijau',
        $jumlahPemain ? angka(round(100 * ($jumlahPemain - $pemainCedera) / $jumlahPemain)) . '% skuad siap' : '');
    echo kartu_kpi('Cedera musim ini', angka(count($musimIni)), 'medis', 'biru', 'Sejak ' . e(tanggal_id(awal_musim())));
    echo kartu_kpi('Rata-rata masa pulih', $durasiPulih ? angka(round(array_sum($durasiPulih) / count($durasiPulih))) . ' hari' : '-', 'jam', 'ungu',
        e(count($pulih) . ' cedera sudah pulih'));
}
echo '</section>';

// ---------- Cedera aktif: lini waktu kembali bermain ----------
if ($aktif): ?>
            <section class="kisi-aktif" aria-label="Cedera yang sedang ditangani">
                <?php foreach ($aktif as $c): ?>
                    <?php
                    $berjalan = max(0, selisih_hari((string) $c['tanggal_cedera']));
                    $total = $c['estimasi_pulih'] ? max(1, selisih_hari((string) $c['tanggal_cedera'], (string) $c['estimasi_pulih'])) : null;
                    $sisa  = $c['estimasi_pulih'] ? selisih_hari(hari_ini()->format('Y-m-d'), (string) $c['estimasi_pulih']) : null;
                    $pct   = $total ? min(100, (int) round(100 * $berjalan / $total)) : 0;
                    $prog  = $programPulih[(int) $c['user_id']] ?? null;
                    ?>
                    <article class="kartu kartu-aktif">
                        <div class="aktif-atas">
                            <span class="denyut" aria-hidden="true"></span>
                            <div class="aktif-judul">
                                <small>Sedang ditangani<?= $pemain === null ? ' · ' . e((string) $c['nama_lengkap']) : '' ?></small>
                                <h2><?= e((string) $c['jenis_cedera']) ?></h2>
                                <p><?= e((string) $c['bagian_tubuh']) ?> · <?= chip_label(LABEL_TINGKAT, (string) $c['tingkat']) ?> <?= chip_label(LABEL_STATUS_CEDERA, (string) $c['status']) ?></p>
                            </div>
                        </div>
                        <div class="rtp">
                            <div class="baris">
                                <span>Kembali bermain (return to play)</span>
                                <b><?= $pct ?>%</b>
                            </div>
                            <div class="progres kuning" role="progressbar" aria-label="Kemajuan pemulihan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>">
                                <span style="width: <?= $pct ?>%"></span>
                            </div>
                            <div class="rtp-ujung">
                                <span>Cedera <?= e(tanggal_id((string) $c['tanggal_cedera'])) ?></span>
                                <span>
                                    <?php if ($sisa === null): ?>
                                        Perkiraan kembali belum ditentukan
                                    <?php elseif ($sisa >= 0): ?>
                                        Perkiraan <?= e(tanggal_id((string) $c['estimasi_pulih'])) ?> · <b><?= angka($sisa) ?> hari lagi</b>
                                    <?php else: ?>
                                        <span class="teks-merah">Lewat <?= angka(-$sisa) ?> hari dari perkiraan</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <ul class="aktif-fakta">
                            <li><?= ikon('medis') ?><span>Penanganan terakhir: <b><?= e((string) ($c['penanganan'] ?? 'Belum ada')) ?></b></span></li>
                            <?php if ($prog): ?>
                                <li><?= ikon('aktivitas') ?><span>Program: <a href="latihan.php"><?= e((string) $prog['judul']) ?></a> (<?= (int) $prog['progres_persen'] ?>%)</span></li>
                            <?php endif; ?>
                        </ul>
                        <button type="button" class="tombol kecil" data-buka-dialog="cedera-<?= (int) $c['id'] ?>"><?= ikon('daftar') ?>Lihat riwayat penanganan</button>
                    </article>
                <?php endforeach; ?>
            </section>
<?php endif; ?>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulRiwayat">
                <div class="kepala-tabel">
                    <h2 id="judulRiwayat">Riwayat cedera</h2>
                    <?= alat_tabel('Cari cedera...', [
                        'status'  => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], LABEL_STATUS_CEDERA)],
                        'tingkat' => ['label' => 'Keparahan', 'opsi' => array_map(static fn($v) => $v[0], LABEL_TINGKAT)],
                    ]) ?>
                </div>
                <?php if (!$cedera): ?>
                    <div class="kosong">Belum ada catatan cedera. Semoga tetap begitu!</div>
                <?php else: ?>
                <div class="gulir-tabel">
                    <table class="tabel-data aksi-tetap<?= $pemain === null ? ' lebar' : '' ?>">
                        <thead>
                            <tr>
                                <?= th_urut('Cedera') ?>
                                <?php if ($pemain === null): ?><?= th_urut('Pemain') ?><?php endif; ?>
                                <?= th_urut('Keparahan', 'angka') ?>
                                <?= th_urut('Tanggal', 'angka', 'desc') ?>
                                <?= th_urut('Perkiraan kembali', 'angka') ?>
                                <th scope="col">Penanganan</th>
                                <?= th_urut('Status', 'angka') ?>
                                <th scope="col"><span class="sr">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cedera as $c): ?>
                                <?php
                                $kembali = $c['tanggal_pulih'] ?: $c['estimasi_pulih'];
                                $sisa = ($c['status'] !== 'pulih' && $c['estimasi_pulih']) ? selisih_hari(hari_ini()->format('Y-m-d'), (string) $c['estimasi_pulih']) : null;
                                ?>
                                <tr data-status="<?= e((string) $c['status']) ?>" data-tingkat="<?= e((string) $c['tingkat']) ?>">
                                    <td><b><?= e((string) $c['jenis_cedera']) ?></b><small class="sub-sel"><?= e((string) $c['bagian_tubuh']) ?></small></td>
                                    <?php if ($pemain === null): ?>
                                        <td>
                                            <a class="sel-pemain" href="<?= e(url_dengan(['pemain' => $c['user_id']])) ?>">
                                                <?= avatar_kecil((string) $c['nama_lengkap'], $c['foto']) ?>
                                                <span><b><?= e((string) $c['nama_lengkap']) ?></b><small class="sub-sel"><?= e(keterangan_pemain($c + ['posisi_detail' => null])) ?></small></span>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                    <td data-nilai="<?= array_search($c['tingkat'], array_keys(LABEL_TINGKAT), true) ?>"><?= chip_label(LABEL_TINGKAT, (string) $c['tingkat']) ?></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $c['tanggal_cedera']) ?>"><b><?= e(tanggal_id((string) $c['tanggal_cedera'])) ?></b><small class="sub-sel"><?= e(LABEL_PENYEBAB[$c['penyebab']] ?? '') ?></small></td>
                                    <td data-nilai="<?= $kembali ? strtotime((string) $kembali) : 0 ?>">
                                        <?php if ($c['status'] === 'pulih'): ?>
                                            <span class="redup">Pulih <?= e(tanggal_id((string) $c['tanggal_pulih'])) ?></span>
                                            <small class="sub-sel"><?= angka(lama_absen($c)) ?> hari absen</small>
                                        <?php elseif ($c['estimasi_pulih']): ?>
                                            <?= e(tanggal_id((string) $c['estimasi_pulih'])) ?>
                                            <small class="sub-sel <?= $sisa < 0 ? 'teks-merah' : '' ?>"><?= $sisa >= 0 ? angka($sisa) . ' hari lagi' : 'Lewat ' . angka(-$sisa) . ' hari' ?></small>
                                        <?php else: ?>
                                            <span class="redup">Belum ditentukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= e((string) ($c['penanganan'] ?? '-')) ?>
                                        <?php if ((int) $c['jumlah_perawatan'] > 1): ?>
                                            <small class="sub-sel">+<?= (int) $c['jumlah_perawatan'] - 1 ?> tindakan sebelumnya</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-nilai="<?= array_search($c['status'], array_keys(LABEL_STATUS_CEDERA), true) ?>"><?= chip_label(LABEL_STATUS_CEDERA, (string) $c['status']) ?></td>
                                    <td>
                                        <span class="aksi-ikon">
                                        <button type="button" class="tombol-ikon-kecil" data-buka-dialog="cedera-<?= (int) $c['id'] ?>"
                                                aria-label="Detail <?= e((string) $c['jenis_cedera']) ?>" title="Detail & penanganan">
                                            <?= ikon('mata') ?>
                                        </button>
                                        <?php if ($staf): ?><?= aksi_cedera($c, $kembaliCedera) ?><?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <?php endif; ?>
            </section>

            <?php foreach ($cedera as $c): ?>
                <dialog class="modal lebar" id="cedera-<?= (int) $c['id'] ?>" aria-labelledby="judul-cedera-<?= (int) $c['id'] ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul kecil"><?= ikon('jantung') ?></span>
                        <div>
                            <h2 id="judul-cedera-<?= (int) $c['id'] ?>"><?= e((string) $c['jenis_cedera']) ?></h2>
                            <p><?= e((string) $c['nama_lengkap']) ?> · <?= e((string) $c['bagian_tubuh']) ?></p>
                        </div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <dl class="fakta-cedera">
                            <div><dt>Keparahan</dt><dd><?= chip_label(LABEL_TINGKAT, (string) $c['tingkat']) ?></dd></div>
                            <div><dt>Status</dt><dd><?= chip_label(LABEL_STATUS_CEDERA, (string) $c['status']) ?></dd></div>
                            <div><dt>Terjadi saat</dt><dd><?= e(LABEL_PENYEBAB[$c['penyebab']] ?? '-') ?></dd></div>
                            <div><dt>Tanggal cedera</dt><dd><?= e(tanggal_id((string) $c['tanggal_cedera'], true)) ?></dd></div>
                            <div><dt><?= $c['status'] === 'pulih' ? 'Tanggal pulih' : 'Perkiraan kembali' ?></dt>
                                <dd><?= e(tanggal_id((string) ($c['tanggal_pulih'] ?: $c['estimasi_pulih']), true) ?: '-') ?></dd></div>
                            <div><dt>Lama absen</dt><dd><?= angka(lama_absen($c)) ?> hari<?= $c['status'] !== 'pulih' ? ' (berjalan)' : '' ?></dd></div>
                        </dl>
                        <?php if (!empty($c['diagnosis'])): ?>
                            <div class="diagnosis">
                                <b>Diagnosis</b>
                                <p><?= e((string) $c['diagnosis']) ?></p>
                                <?php if (!empty($c['pencatat'])): ?><small>Dicatat oleh <?= e((string) $c['pencatat']) ?></small><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="pemisah-bagian">Riwayat penanganan</h3>
                        <?php if (empty($perawatan[(int) $c['id']])): ?>
                            <p class="redup">Belum ada tindakan yang dicatat.</p>
                        <?php else: ?>
                            <ol class="garis-waktu">
                                <?php $daftarRawat = $perawatan[(int) $c['id']]; ?>
                                <?php foreach ($daftarRawat as $i => $p): ?>
                                    <li<?= $i === array_key_last($daftarRawat) ? ' class="sekarang"' : '' ?>>
                                        <div class="kartu-rawat">
                                            <small><?= e(tanggal_id((string) $p['tanggal'], true)) ?><?= !empty($p['petugas']) ? ' · ' . e((string) $p['petugas']) : '' ?></small>
                                            <b><?= e((string) $p['tindakan']) ?></b>
                                            <?php if (!empty($p['obat'])): ?><span>Obat: <?= e((string) $p['obat']) ?></span><?php endif; ?>
                                            <?php if (!empty($p['catatan'])): ?><span class="redup"><?= e((string) $p['catatan']) ?></span><?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                        <?php if ($staf): ?><?= form_penanganan($c, $kembaliCedera) ?><?php endif; ?>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Tutup</button>
                    </div>
                </dialog>
            <?php endforeach; ?>
<?php
if ($staf) {
    dialog_cedera($kembaliCedera, $pemain !== null ? (int) $pemain['id'] : null);
}
halaman_akhir();
