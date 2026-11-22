<?php
declare(strict_types=1);

// Data Penggajian (admin/HR): buat draf bulanan, tambah manual, setujui, tandai dibayar, ekspor.
// Sistem hanya mencatat; transfer bank dilakukan di luar aplikasi.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';
require __DIR__ . '/lib_gaji.php';

start_secure_session();
wajib_admin();

// ---------- Periode ----------
$periodeUrl = (string) ($_GET['periode'] ?? $_POST['periode'] ?? '');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodeUrl)) {
    try {
        $terakhir = db()->query("SELECT DATE_FORMAT(MAX(periode), '%Y-%m') FROM penggajian")->fetchColumn();
    } catch (PDOException) {
        $terakhir = null;
    }
    $periodeUrl = $terakhir ?: hari_ini()->modify('first day of previous month')->format('Y-m');
}
$batasDepan = hari_ini()->modify('first day of next month')->format('Y-m');
if ($periodeUrl > $batasDepan) {
    $periodeUrl = $batasDepan;
}
$periode = $periodeUrl . '-01';
$alamat = 'kelola_gaji.php?periode=' . $periodeUrl;
$namaPeriode = bulan_tahun($periode);

/** Kontrak yang berlaku pada periode (aktif atau sudah diperbarui setelahnya) per karyawan aktif. */
function kontrak_periode(string $periode): array
{
    $st = db()->prepare(
        "SELECT k.id AS kontrak_id, k.user_id, u.nama_lengkap FROM kontrak k JOIN users u ON u.id = k.user_id
          WHERE u.is_active = 1 AND k.status IN ('aktif','diperbarui')
            AND k.tanggal_mulai <= LAST_DAY(:p) AND k.tanggal_selesai >= :p2
            AND NOT EXISTS (SELECT 1 FROM penggajian g WHERE g.user_id = k.user_id AND g.periode = :p3)
          ORDER BY u.nama_lengkap, k.tanggal_mulai DESC"
    );
    $st->execute([':p' => $periode, ':p2' => $periode, ':p3' => $periode]);
    $hasil = [];
    foreach ($st->fetchAll() as $r) {
        $hasil[(int) $r['user_id']] ??= $r; // satu kontrak per orang (yang terbaru)
    }
    return $hasil;
}

/** Buat satu slip draf dari kontrak; kembalikan id slip. */
function buat_slip_draf(int $userId, int $kontrakId, string $periode, float $bonus = 0, float $potongan = 0, string $ket = ''): int
{
    $rek = db()->prepare('SELECT rekening_tujuan FROM penggajian WHERE user_id = ? AND rekening_tujuan IS NOT NULL ORDER BY periode DESC LIMIT 1');
    $rek->execute([$userId]);
    db()->prepare("INSERT INTO penggajian (user_id, kontrak_id, periode, status, rekening_tujuan, dibuat_oleh) VALUES (?, ?, ?, 'draft', ?, ?)")
        ->execute([$userId, $kontrakId, $periode, $rek->fetchColumn() ?: null, $_SESSION['user_id']]);
    $id = (int) db()->lastInsertId();
    susun_rincian_slip($id, $bonus, $potongan, $ket);
    return $id;
}

// =====================================================================
// POST
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash_set('galat', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        kembali_ke($alamat);
    }
    $aksi = (string) ($_POST['aksi'] ?? '');
    $pdo = db();
    try {
        if ($aksi === 'buat_draf') {
            $daftar = kontrak_periode($periode);
            $pdo->beginTransaction();
            foreach ($daftar as $r) {
                buat_slip_draf((int) $r['user_id'], (int) $r['kontrak_id'], $periode);
            }
            $pdo->commit();
            catat_aktivitas('buat_draf_gaji', 'penggajian', null, $namaPeriode . ': ' . count($daftar) . ' slip');
            flash_set($daftar ? 'sukses' : 'info', $daftar ? count($daftar) . ' draf slip ' . $namaPeriode . ' dibuat. Periksa lalu setujui.' : 'Semua karyawan sudah punya slip ' . $namaPeriode . '.');
            kembali_ke($alamat);
        }

        if ($aksi === 'tambah') {
            $uid = (int) ($_POST['user_id'] ?? 0);
            $bonus = max(0, (float) ($_POST['bonus'] ?? 0));
            $potongan = max(0, (float) ($_POST['potongan'] ?? 0));
            $ket = mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 80);
            $tersedia = kontrak_periode($periode);
            if (!isset($tersedia[$uid])) {
                flash_set('galat', 'Karyawan tidak punya kontrak yang berlaku di ' . $namaPeriode . ' atau slipnya sudah ada.');
                kembali_ke($alamat);
            }
            if (($bonus > 0 || $potongan > 0) && mb_strlen($ket) < 3) {
                flash_set('galat', 'Isi keterangan untuk bonus/potongan tambahan.');
                kembali_ke($alamat);
            }
            $pdo->beginTransaction();
            $id = buat_slip_draf($uid, (int) $tersedia[$uid]['kontrak_id'], $periode, $bonus, $potongan, $ket);
            $pdo->commit();
            catat_aktivitas('tambah', 'penggajian', $id, $tersedia[$uid]['nama_lengkap'] . ' ' . $namaPeriode);
            flash_set('sukses', 'Draf slip ' . $tersedia[$uid]['nama_lengkap'] . ' dibuat.');
            kembali_ke($alamat);
        }

        // Transisi status: draft <-> disetujui -> dibayar (dibayar final).
        $aturan = ['disetujui' => ['draft'], 'draft' => ['disetujui'], 'dibayar' => ['disetujui']];
        if ($aksi === 'status' || $aksi === 'massal') {
            $ke = (string) ($_POST['ke'] ?? '');
            if (!isset($aturan[$ke])) {
                flash_set('galat', 'Status tidak valid.');
                kembali_ke($alamat);
            }
            $dari = $aturan[$ke][0];
            $sql = "UPDATE penggajian SET status = :ke" . ($ke === 'dibayar' ? ', tanggal_bayar = CURDATE()' : '')
                 . " WHERE status = :dari AND periode = :p" . ($aksi === 'status' ? ' AND id = :id' : '');
            $param = [':ke' => $ke, ':dari' => $dari, ':p' => $periode];
            if ($aksi === 'status') {
                $param[':id'] = (int) ($_POST['id'] ?? 0);
            }
            // Ambil dulu yang akan berubah (untuk notifikasi).
            $sel = $pdo->prepare('SELECT id, user_id FROM penggajian WHERE status = :dari AND periode = :p' . ($aksi === 'status' ? ' AND id = :id' : ''));
            $sel->execute(array_diff_key($param, [':ke' => 1]));
            $kena = $sel->fetchAll();
            $pdo->beginTransaction();
            $st = $pdo->prepare($sql);
            $st->execute($param);
            if ($ke === 'dibayar') {
                kirim_notifikasi(array_column($kena, 'user_id'), 'gaji', 'Gaji ' . $namaPeriode . ' sudah dibayar',
                    'Gaji sudah ditransfer ke rekening Anda. Slip gaji dapat diunduh di menu Slip Gaji.', 'gaji.php');
            }
            $pdo->commit();
            $jumlah = $st->rowCount();
            catat_aktivitas('gaji_' . $ke, 'penggajian', $aksi === 'status' ? (int) $param[':id'] : null, $namaPeriode . ': ' . $jumlah . ' slip');
            $teks = ['disetujui' => 'disetujui', 'draft' => 'dikembalikan ke draf', 'dibayar' => 'ditandai dibayar'][$ke];
            flash_set($jumlah ? 'sukses' : 'galat', $jumlah ? $jumlah . ' slip ' . $teks . '.' : 'Tidak ada slip yang bisa diubah (status sudah berbeda).');
            kembali_ke($alamat);
        }

        if ($aksi === 'hitung_ulang' || $aksi === 'hapus') {
            $id = (int) ($_POST['id'] ?? 0);
            $st = $pdo->prepare("SELECT id FROM penggajian WHERE id = ? AND status = 'draft'");
            $st->execute([$id]);
            if (!$st->fetchColumn()) {
                flash_set('galat', 'Hanya slip berstatus draf yang dapat diubah.');
                kembali_ke($alamat);
            }
            if ($aksi === 'hapus') {
                $pdo->prepare("DELETE FROM penggajian WHERE id = ? AND status = 'draft'")->execute([$id]);
                catat_aktivitas('hapus', 'penggajian', $id);
                flash_set('sukses', 'Draf slip dihapus.');
            } else {
                // Pertahankan bonus/potongan manual yang sudah ada.
                $m = $pdo->prepare("SELECT kode, jumlah, keterangan FROM penggajian_rincian WHERE penggajian_id = ? AND kode IN ('bonus_lain','potongan_lain')");
                $m->execute([$id]);
                $manual = array_column($m->fetchAll(), null, 'kode');
                $ket = (string) preg_replace('/^(Bonus|Potongan): /', '', (string) ($manual['bonus_lain']['keterangan'] ?? $manual['potongan_lain']['keterangan'] ?? ''));
                $pdo->beginTransaction();
                susun_rincian_slip($id, (float) ($manual['bonus_lain']['jumlah'] ?? 0), (float) ($manual['potongan_lain']['jumlah'] ?? 0), $ket);
                $pdo->commit();
                flash_set('sukses', 'Rincian slip dihitung ulang dari kontrak & statistik terbaru.');
            }
            kembali_ke($alamat);
        }
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Penggajian gagal diproses: ' . $ex->getMessage());
        flash_set('galat', 'Penggajian belum dapat diproses. Coba lagi nanti.');
        kembali_ke($alamat);
    }
    kembali_ke($alamat);
}

// =====================================================================
// Data tampilan
// =====================================================================
kirim_pengingat_kontrak();
$dbGagal = false;
$slip = [];
try {
    $st = db()->prepare(
        "SELECT p.*, u.nama_lengkap, u.role, pr.posisi_kode, s.jabatan_detail, s.jabatan, k.nomor_kontrak
           FROM penggajian p JOIN users u ON u.id = p.user_id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id LEFT JOIN staf s ON s.user_id = u.id
           LEFT JOIN kontrak k ON k.id = p.kontrak_id
          WHERE p.periode = :p ORDER BY u.nama_lengkap"
    );
    $st->execute([':p' => $periode]);
    $slip = $st->fetchAll();
    $rincian = rincian_slip(array_column($slip, 'id'));
    $belum = kontrak_periode($periode);
    $st = db()->prepare("SELECT SUM(total_bersih) FROM penggajian WHERE periode = DATE_SUB(:p, INTERVAL 1 MONTH)");
    $st->execute([':p' => $periode]);
    $totalLalu = (float) $st->fetchColumn();
} catch (PDOException $ex) {
    error_log('Penggajian gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

$hitung = ['draft' => 0, 'disetujui' => 0, 'dibayar' => 0];
foreach ($slip as $s) {
    $hitung[$s['status']]++;
}
$total = array_sum(array_map(static fn($s) => (float) $s['total_bersih'], $slip));
$totalBonus = array_sum(array_map(static fn($s) => (float) $s['bonus'], $slip));

$aksiKepala = filter_get(['periode' => ['label' => 'Periode', 'opsi' => (static function () use ($periodeUrl): array {
        $o = [];
        for ($i = -1; $i < 12; $i++) {
            $b = hari_ini()->modify('first day of this month')->modify((-$i) . ' month');
            $o[$b->format('Y-m')] = NAMA_BULAN[(int) $b->format('n')] . ' ' . $b->format('Y');
        }
        if (!isset($o[$periodeUrl])) {
            $o[$periodeUrl] = bulan_tahun($periodeUrl . '-01');
        }
        return $o;
    })(), 'nilai' => $periodeUrl]])
    . '<a class="tombol" href="ekspor.php?jenis=gaji&amp;periode=' . e($periodeUrl) . '">' . ikon('unduh') . 'Excel</a>'
    . '<button type="button" class="tombol" data-buka-dialog="dialogTambahGaji"' . ($belum ? '' : ' disabled title="Semua karyawan sudah punya slip"') . '>' . ikon('tambah') . 'Tambah</button>'
    . '<form method="post" action="' . e($alamat) . '" class="sebaris"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="buat_draf">'
    . '<button type="submit" class="tombol utama"' . ($belum ? '' : ' disabled') . '>' . ikon('bintang') . 'Buat draf ' . e($namaPeriode) . ($belum ? ' (' . count($belum) . ')' : '') . '</button></form>';
halaman_awal('Data Penggajian', 'Data Penggajian');
kepala_halaman('uang', 'Data Penggajian', 'Catat gaji bulanan dan terbitkan slip. Sistem tidak melakukan transfer bank.', '<div class="aksi-kanan">' . $aksiKepala . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data penggajian belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;
?>
            <section class="kpi-kisi" aria-label="Ringkasan <?= e($namaPeriode) ?>">
                <?= kartu_kpi('Total ' . $namaPeriode, rupiah_ringkas($total), 'uang', 'hijau',
                    e(count($slip) . ' orang') . ($totalLalu > 0 ? ' · ' . selisih($total / 1e6, $totalLalu / 1e6, 1, ' jt', false, 'dari bulan lalu') : '')) ?>
                <?= kartu_kpi('Total bonus', rupiah_ringkas($totalBonus), 'bintang', 'ungu', 'Gol, penampilan, tanda tangan & manual') ?>
                <?= kartu_kpi('Draf', angka($hitung['draft']), 'pena', 'kuning', $hitung['draft'] ? 'Perlu diperiksa & disetujui' : 'Tidak ada draf') ?>
                <?= kartu_kpi('Disetujui / dibayar', angka($hitung['disetujui']) . ' / ' . angka($hitung['dibayar']), 'tanda', 'biru', e(count($slip) . ' slip total')) ?>
            </section>

            <?php if ($belum || $hitung['draft'] || $hitung['disetujui']): ?>
                <ol class="alur-gaji" aria-label="Alur penggajian <?= e($namaPeriode) ?>">
                    <li class="<?= $belum ? 'sekarang' : 'selesai' ?>"><b>1. Buat draf</b><small><?= $belum ? count($belum) . ' karyawan belum punya slip' : 'Semua slip sudah dibuat' ?></small></li>
                    <li class="<?= $hitung['draft'] ? 'sekarang' : ($belum ? '' : 'selesai') ?>"><b>2. Periksa & setujui</b><small><?= $hitung['draft'] ?> draf</small>
                        <?php if ($hitung['draft']): ?>
                            <form method="post" action="<?= e($alamat) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="massal"><input type="hidden" name="ke" value="disetujui">
                                <button type="submit" class="tombol kecil utama"><?= ikon('tanda') ?>Setujui semua (<?= $hitung['draft'] ?>)</button></form>
                        <?php endif; ?></li>
                    <li class="<?= $hitung['disetujui'] ? 'sekarang' : ($hitung['dibayar'] && !$hitung['draft'] && !$belum ? 'selesai' : '') ?>"><b>3. Tandai dibayar</b><small><?= $hitung['disetujui'] ?> menunggu transfer</small>
                        <?php if ($hitung['disetujui']): ?>
                            <form method="post" action="<?= e($alamat) ?>" data-konfirmasi="Tandai <?= $hitung['disetujui'] ?> slip sebagai sudah dibayar? Karyawan akan menerima notifikasi."><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="massal"><input type="hidden" name="ke" value="dibayar">
                                <button type="submit" class="tombol kecil utama"><?= ikon('uang') ?>Tandai dibayar (<?= $hitung['disetujui'] ?>)</button></form>
                        <?php endif; ?></li>
                </ol>
            <?php endif; ?>

            <section class="kartu tabel-kartu" data-tabel aria-label="Slip <?= e($namaPeriode) ?>">
                <div class="kepala-tabel">
                    <?= alat_tabel('Cari...', [
                        'status'   => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], LABEL_STATUS_SLIP)],
                        'kategori' => ['label' => 'Kategori', 'opsi' => ['pemain' => 'Pemain', 'staf' => 'Staf']],
                    ]) ?>
                </div>
                <?php if (!$slip): ?>
                    <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('uang') ?></span><b>Belum ada slip untuk <?= e($namaPeriode) ?></b><span>Klik “Buat draf <?= e($namaPeriode) ?>” untuk membuat slip semua karyawan sekaligus.</span></div>
                <?php else: ?>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar">
                        <thead>
                            <tr><?= th_urut('Nama', 'teks', 'asc') ?><?= th_urut('Gaji pokok', 'angka') ?><?= th_urut('Tunjangan', 'angka') ?><?= th_urut('Bonus', 'angka') ?>
                                <?= th_urut('Potongan', 'angka') ?><?= th_urut('Total', 'angka') ?><?= th_urut('Status') ?><th scope="col"><span class="sr">Aksi</span></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slip as $s): ?>
                                <?php
                                $r = susun_rincian($s, $rincian[(int) $s['id']] ?? []);
                                $htmlRincian = '<div class="slip-kolom"><div><h3>Pendapatan</h3><ul class="rincian-uang">';
                                foreach ($r['pendapatan'] as $b) {
                                    $htmlRincian .= '<li><span>' . e((string) $b['keterangan']) . '</span><b>' . e(rupiah($b['jumlah'])) . '</b></li>';
                                }
                                $htmlRincian .= '<li class="total"><span>Bruto</span><b>' . e(rupiah($r['bruto'])) . '</b></li></ul></div><div><h3>Potongan</h3><ul class="rincian-uang">';
                                foreach ($r['potongan'] as $b) {
                                    $htmlRincian .= '<li class="kurang"><span>' . e((string) $b['keterangan']) . '</span><b>' . e(rupiah($b['jumlah'])) . '</b></li>';
                                }
                                $htmlRincian .= '<li class="total"><span>Total potongan</span><b>' . e(rupiah($r['total_potongan'])) . '</b></li></ul></div></div>';
                                $peran = $s['role'] === 'pemain' ? (KODE_POSISI[$s['posisi_kode']][0] ?? 'Pemain') : ($s['jabatan_detail'] ?: (LABEL_JABATAN_ENUM[$s['jabatan']] ?? 'Staf'));
                                $detail = [
                                    'nama' => $s['nama_lengkap'], 'nomor' => nomor_slip($s) . ' · ' . ($s['nomor_kontrak'] ?? '-'),
                                    'rincian_html' => $htmlRincian, 'total' => rupiah($s['total_bersih']), 'terbilang' => terbilang_rupiah((float) $s['total_bersih']),
                                ];
                                $form = static fn(string $aksi, string $label, string $ekstra = '', string $kelas = '') =>
                                    '<form method="post" action="' . e($alamat) . '"' . ($aksi === 'hapus' ? ' data-konfirmasi="Hapus draf slip ' . e((string) $s['nama_lengkap']) . '?"' : '') . '>'
                                    . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="id" value="' . (int) $s['id'] . '">'
                                    . '<input type="hidden" name="aksi" value="' . $aksi . '">' . $ekstra . '<button type="submit"' . ($kelas ? ' class="' . $kelas . '"' : '') . '>' . $label . '</button></form>';
                                $menu = '<details class="menu-baris"><summary aria-label="Aksi untuk ' . e((string) $s['nama_lengkap']) . '"><svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/></svg></summary><div class="isi-menu-baris">'
                                    . '<button type="button" ' . isi_dialog('dialogRincian', $detail) . '>' . ikon('mata') . 'Lihat rincian</button>';
                                if ($s['status'] === 'draft') {
                                    $menu .= $form('status', ikon('tanda') . 'Setujui', '<input type="hidden" name="ke" value="disetujui">')
                                        . $form('hitung_ulang', ikon('sinkron') . 'Hitung ulang') . '<hr>' . $form('hapus', ikon('silang') . 'Hapus draf', '', 'bahaya');
                                } elseif ($s['status'] === 'disetujui') {
                                    $menu .= $form('status', ikon('uang') . 'Tandai dibayar', '<input type="hidden" name="ke" value="dibayar">')
                                        . $form('status', ikon('kiri') . 'Kembalikan ke draf', '<input type="hidden" name="ke" value="draft">');
                                }
                                if ($s['status'] !== 'draft') {
                                    $menu .= '<a href="gaji_pdf.php?id=' . (int) $s['id'] . '">' . ikon('unduh') . 'Unduh PDF</a>';
                                }
                                $menu .= '</div></details>';
                                ?>
                                <tr data-status="<?= e((string) $s['status']) ?>" data-kategori="<?= $s['role'] === 'pemain' ? 'pemain' : 'staf' ?>">
                                    <td><b><?= e((string) $s['nama_lengkap']) ?></b><small class="sub-sel"><?= e((string) $peran) ?></small></td>
                                    <td class="uang" data-nilai="<?= (float) $s['gaji_pokok'] ?>"><?= e(rupiah($s['gaji_pokok'])) ?></td>
                                    <td class="uang" data-nilai="<?= (float) $s['tunjangan'] ?>"><?= e(rupiah($s['tunjangan'])) ?></td>
                                    <td class="uang" data-nilai="<?= (float) $s['bonus'] ?>"><?= (float) $s['bonus'] > 0 ? e(rupiah($s['bonus'])) : '<span class="redup">Rp 0</span>' ?></td>
                                    <td class="uang teks-merah" data-nilai="<?= (float) $s['potongan'] ?>">- <?= e(rupiah($s['potongan'])) ?></td>
                                    <td class="uang" data-nilai="<?= (float) $s['total_bersih'] ?>"><b><?= e(rupiah($s['total_bersih'])) ?></b></td>
                                    <td data-nilai="<?= e((string) $s['status']) ?>"><?= chip_peta(LABEL_STATUS_SLIP, (string) $s['status']) ?></td>
                                    <td><?= $menu ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Rincian dihitung otomatis: gaji & tunjangan dari kontrak, bonus gol/penampilan dari statistik laga bulan itu, PPh 21 5% dari bruto, BPJS, dan denda kartu. Slip yang sudah dibayar tidak dapat diubah.</p>
                <?php endif; ?>
            </section>

            <dialog class="modal lebar" id="dialogRincian" aria-labelledby="judulRincian">
                <div class="modal-kepala">
                    <span class="ikon-judul"><?= ikon('uang') ?></span>
                    <div><h2 id="judulRincian" data-isi="nama">Rincian</h2><p><?= e($namaPeriode) ?> · <span data-isi="nomor"></span></p></div>
                    <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-isi">
                    <div data-isi-html="rincian_html"></div>
                    <div class="slip-total"><span>Gaji bersih</span><b data-isi="total"></b><small data-isi="terbilang"></small></div>
                </div>
                <div class="modal-kaki"><button type="button" class="tombol" data-tutup-dialog>Tutup</button></div>
            </dialog>

            <dialog class="modal" id="dialogTambahGaji" aria-labelledby="judulTambahGaji">
                <form method="post" action="<?= e($alamat) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('tambah') ?></span>
                        <div><h2 id="judulTambahGaji">Tambah slip <?= e($namaPeriode) ?></h2><p>Rincian dasar dihitung otomatis dari kontrak; tambahkan bonus atau potongan khusus bila perlu.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="medan"><label for="gUser">Karyawan</label>
                            <select id="gUser" name="user_id" required>
                                <?php foreach ($belum as $b): ?><option value="<?= (int) $b['user_id'] ?>"><?= e((string) $b['nama_lengkap']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="baris-2">
                            <div class="medan"><label for="gBonus">Bonus tambahan <small>(opsional)</small></label><div class="kolom"><input type="number" id="gBonus" name="bonus" min="0" step="50000" value="0"></div></div>
                            <div class="medan"><label for="gPotong">Potongan tambahan <small>(opsional)</small></label><div class="kolom"><input type="number" id="gPotong" name="potongan" min="0" step="50000" value="0"></div></div>
                        </div>
                        <div class="medan"><label for="gKet">Keterangan <small>(wajib bila ada bonus/potongan)</small></label><div class="kolom"><input type="text" id="gKet" name="keterangan" maxlength="80" placeholder="mis. Bonus juara turnamen pramusim"></div></div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Buat draf</button>
                    </div>
                </form>
            </dialog>
<?php
halaman_akhir();
