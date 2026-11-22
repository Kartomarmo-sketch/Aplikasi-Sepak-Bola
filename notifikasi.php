<?php
declare(strict_types=1);

// Pusat notifikasi: semua kabar untuk pengguna yang sedang masuk.
//   ?tab=semua|belum|penting  ?kat=<kategori>  ?hal=N
//   ?buka=ID&t=TOKEN  -> tandai dibaca lalu buka tujuannya
//   ?status=1         -> JSON jumlah belum dibaca (untuk lencana yang diperbarui berkala)
//   ?panel=1          -> HTML isi panel dropdown (dimuat ulang setelah aksi)
// POST aksi: baca, belum, hapus, baca_semua, urungkan, hapus_dibaca, preferensi

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

$uid  = (int) $_SESSION['user_id'];
$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
const NOTIF_PER_HAL = 15;

function balas_json(array $data, int $kode = 200): never
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Buka satu notifikasi ----------
if (isset($_GET['buka'])) {
    $id = filter_input(INPUT_GET, 'buka', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tujuan = 'notifikasi.php';
    if ($id && csrf_valid($_GET['t'] ?? null)) {
        try {
            $st = db()->prepare('SELECT tautan FROM notifikasi WHERE id = ? AND user_id = ?');
            $st->execute([$id, $uid]);
            $tautan = $st->fetchColumn();
            if ($tautan !== false) {
                db()->prepare('UPDATE notifikasi SET dibaca_pada = NOW() WHERE id = ? AND user_id = ? AND dibaca_pada IS NULL')->execute([$id, $uid]);
                $tujuan = notif_tautan_aman($tautan === null ? null : (string) $tautan) ?? 'notifikasi.php';
            }
        } catch (PDOException $ex) {
            error_log('Notifikasi gagal dibuka: ' . $ex->getMessage());
        }
    }
    header('Location: ' . $tujuan, true, 303);
    exit;
}

// ---------- Endpoint ringan untuk JavaScript ----------
if (isset($_GET['status'])) {
    session_write_close();
    balas_json(['jumlah' => jumlah_notifikasi($uid)]);
}
if (isset($_GET['panel'])) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo isi_panel_notif($uid);
    exit;
}

// ---------- Aksi ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kembali = notif_tautan_aman(is_string($_POST['kembali'] ?? null) ? $_POST['kembali'] : null) ?? 'notifikasi.php';
    $selesai = static function (bool $ok, string $pesan) use ($ajax, $kembali, $uid): never {
        if ($ajax) {
            balas_json(['ok' => $ok, 'pesan' => $pesan, 'jumlah' => jumlah_notifikasi($uid)], $ok ? 200 : 400);
        }
        flash_set($ok ? 'sukses' : 'galat', $pesan);
        header('Location: ' . $kembali, true, 303);
        exit;
    };
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $selesai(false, 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
    $aksi = (string) ($_POST['aksi'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    try {
        switch ($aksi) {
            case 'baca':
            case 'belum':
                $st = $pdo->prepare('UPDATE notifikasi SET dibaca_pada = ' . ($aksi === 'baca' ? 'NOW()' : 'NULL') . ' WHERE id = ? AND user_id = ?');
                $st->execute([$id, $uid]);
                $selesai(true, $aksi === 'baca' ? 'Notifikasi ditandai dibaca.' : 'Notifikasi ditandai belum dibaca.');
            case 'hapus':
                $st = $pdo->prepare('DELETE FROM notifikasi WHERE id = ? AND user_id = ?');
                $st->execute([$id, $uid]);
                $selesai($st->rowCount() === 1, $st->rowCount() === 1 ? 'Notifikasi dihapus.' : 'Notifikasi tidak ditemukan.');
            case 'baca_semua':
                $kat = (string) ($_POST['kat'] ?? '');
                $syarat = isset(KATEGORI_NOTIF[$kat]) ? ' AND kategori = ?' : '';
                $param = $syarat !== '' ? [$uid, $kat] : [$uid];
                $st = $pdo->prepare('SELECT id FROM notifikasi WHERE user_id = ? AND dibaca_pada IS NULL' . $syarat);
                $st->execute($param);
                $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
                if (!$ids) {
                    $selesai(true, 'Tidak ada notifikasi yang belum dibaca.');
                }
                $pdo->prepare('UPDATE notifikasi SET dibaca_pada = NOW() WHERE user_id = ? AND dibaca_pada IS NULL' . $syarat)->execute($param);
                // Simpan untuk "Urungkan" (berlaku 2 menit).
                $_SESSION['notif_urung'] = ['ids' => $ids, 'waktu' => time()];
                $selesai(true, count($ids) . ' notifikasi ditandai dibaca.');
            case 'urungkan':
                $urung = $_SESSION['notif_urung'] ?? null;
                unset($_SESSION['notif_urung']);
                if (!is_array($urung) || ($urung['waktu'] ?? 0) <= time() - 120 || empty($urung['ids'])) {
                    $selesai(false, 'Batas waktu urungkan sudah lewat.');
                }
                $ids = array_map('intval', $urung['ids']);
                $tanda = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("UPDATE notifikasi SET dibaca_pada = NULL WHERE user_id = ? AND id IN ($tanda)")->execute([$uid, ...$ids]);
                $selesai(true, 'Dibatalkan: ' . count($ids) . ' notifikasi kembali belum dibaca.');
            case 'hapus_dibaca':
                $st = $pdo->prepare('DELETE FROM notifikasi WHERE user_id = ? AND dibaca_pada IS NOT NULL');
                $st->execute([$uid]);
                $selesai(true, $st->rowCount() . ' notifikasi yang sudah dibaca dihapus.');
            case 'preferensi':
                $pilih = is_array($_POST['kat'] ?? null) ? $_POST['kat'] : [];
                $simpan = $pdo->prepare('INSERT INTO notifikasi_preferensi (user_id, kategori, aktif) VALUES (?, ?, ?)
                                         ON DUPLICATE KEY UPDATE aktif = VALUES(aktif)');
                $mati = 0;
                foreach (array_keys(KATEGORI_NOTIF) as $k) {
                    $aktif = in_array($k, KATEGORI_NOTIF_WAJIB, true) || isset($pilih[$k]) ? 1 : 0;
                    $mati += 1 - $aktif;
                    $simpan->execute([$uid, $k, $aktif]);
                }
                catat_aktivitas('ubah', 'notifikasi_preferensi', $uid, $mati . ' kategori dimatikan');
                $selesai(true, 'Pengaturan notifikasi disimpan' . ($mati ? ' (' . $mati . ' kategori dimatikan).' : '.'));
            default:
                $selesai(false, 'Aksi tidak dikenal.');
        }
    } catch (PDOException $ex) {
        error_log('Aksi notifikasi gagal: ' . $ex->getMessage());
        $selesai(false, 'Notifikasi belum dapat diperbarui. Coba lagi nanti.');
    }
}

// ---------- Data halaman ----------
$tab = in_array($_GET['tab'] ?? '', ['belum', 'penting'], true) ? (string) $_GET['tab'] : 'semua';
$kat = isset(KATEGORI_NOTIF[$_GET['kat'] ?? '']) ? (string) $_GET['kat'] : '';
$hal = max(1, (int) ($_GET['hal'] ?? 1));
$dbGagal = false;
$ringkas = ['total' => 0, 'belum' => 0, 'penting' => 0, 'minggu' => 0];
$perKat = [];
$daftar = [];
$total = 0;
$pref = [];
try {
    $st = db()->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(dibaca_pada IS NULL), 0) AS belum,
                                COALESCE(SUM(prioritas = 'penting' AND dibaca_pada IS NULL), 0) AS penting,
                                COALESCE(SUM(created_at >= NOW() - INTERVAL 7 DAY), 0) AS minggu
                           FROM notifikasi WHERE user_id = ?");
    $st->execute([$uid]);
    $ringkas = array_map('intval', $st->fetch());

    $st = db()->prepare('SELECT kategori, COUNT(*) AS total, SUM(dibaca_pada IS NULL) AS belum FROM notifikasi WHERE user_id = ? GROUP BY kategori');
    $st->execute([$uid]);
    foreach ($st->fetchAll() as $r) {
        $perKat[$r['kategori']] = ['total' => (int) $r['total'], 'belum' => (int) $r['belum']];
    }

    $syarat = 'user_id = ?';
    $param = [$uid];
    if ($tab === 'belum') {
        $syarat .= ' AND dibaca_pada IS NULL';
    } elseif ($tab === 'penting') {
        $syarat .= " AND prioritas = 'penting'";
    }
    if ($kat !== '') {
        $syarat .= ' AND kategori = ?';
        $param[] = $kat;
    }
    $st = db()->prepare("SELECT COUNT(*) FROM notifikasi WHERE $syarat");
    $st->execute($param);
    $total = (int) $st->fetchColumn();
    $jumlahHal = max(1, (int) ceil($total / NOTIF_PER_HAL));
    $hal = min($hal, $jumlahHal);
    $st = db()->prepare("SELECT id, judul, pesan, tautan, kategori, prioritas, dibaca_pada, created_at FROM notifikasi
                          WHERE $syarat ORDER BY created_at DESC, id DESC LIMIT " . NOTIF_PER_HAL . ' OFFSET ' . (($hal - 1) * NOTIF_PER_HAL));
    $st->execute($param);
    $daftar = $st->fetchAll();

    $st = db()->prepare('SELECT kategori, aktif FROM notifikasi_preferensi WHERE user_id = ?');
    $st->execute([$uid]);
    $pref = array_map('intval', $st->fetchAll(PDO::FETCH_KEY_PAIR));
} catch (PDOException $ex) {
    error_log('Notifikasi gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}
$jumlahHal = max(1, (int) ceil($total / NOTIF_PER_HAL));
$alamat = 'notifikasi.php' . (($q = http_build_query(array_filter(['tab' => $tab !== 'semua' ? $tab : '', 'kat' => $kat, 'hal' => $hal > 1 ? $hal : '']))) !== '' ? '?' . $q : '');
$urlTab = static fn(array $ubah): string => 'notifikasi.php' . (($q = http_build_query(array_filter(array_merge(['tab' => $tab !== 'semua' ? $tab : '', 'kat' => $kat], $ubah)))) !== '' ? '?' . $q : '');
$urung = $_SESSION['notif_urung'] ?? null;
$bisaUrung = is_array($urung) && ($urung['waktu'] ?? 0) > time() - 120 && !empty($urung['ids']);
$csrf = csrf_token();

halaman_awal('Notifikasi');

$kanan = '<div class="aksi-kanan">';
if ($ringkas['belum'] > 0) {
    $kanan .= '<form method="post" action="notifikasi.php"><input type="hidden" name="csrf" value="' . e($csrf) . '"><input type="hidden" name="aksi" value="baca_semua">'
        . '<input type="hidden" name="kembali" value="' . e($alamat) . '">'
        . '<button type="submit" class="tombol">' . ikon('centang-ganda') . 'Tandai semua dibaca</button></form>';
}
$kanan .= '<a class="tombol utama" href="#pengaturan">' . ikon('gerigi') . 'Pengaturan</a></div>';
kepala_halaman('lonceng', 'Notifikasi', 'Semua kabar soal kontrak, gaji, jadwal, pengajuan, dan performa Anda.', $kanan);
?>
            <?php if ($dbGagal): ?>
                <p class="galat" role="alert">Notifikasi belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
            <?php endif; ?>

            <section class="kpi-kisi" aria-label="Ringkasan notifikasi">
                <?= kartu_kpi('Belum dibaca', angka($ringkas['belum']), 'lonceng', 'hijau', $ringkas['total'] ? round(100 * $ringkas['belum'] / $ringkas['total']) . '% dari semua notifikasi' : 'Kotak masuk kosong') ?>
                <?= kartu_kpi('Penting belum dibaca', angka($ringkas['penting']), 'peringatan', 'merah', $ringkas['penting'] ? 'Perlu tindakan segera' : 'Tidak ada yang mendesak') ?>
                <?= kartu_kpi('Diterima 7 hari', angka($ringkas['minggu']), 'kalender', 'biru', 'Sejak ' . tanggal_id(hari_ini()->modify('-7 days')->format('Y-m-d'))) ?>
                <?= kartu_kpi('Total tersimpan', angka($ringkas['total']), 'kotak-masuk', 'ungu', count($perKat) . ' kategori') ?>
            </section>

            <div class="kisi-notif">
                <section class="kartu kartu-notif" aria-labelledby="judulDaftarNotif">
                    <h2 class="sr" id="judulDaftarNotif">Daftar notifikasi</h2>
                    <div class="notif-alat">
                        <nav class="tab-status" aria-label="Saring notifikasi">
                            <?php foreach (['semua' => ['Semua', $ringkas['total']], 'belum' => ['Belum dibaca', $ringkas['belum']], 'penting' => ['Penting', null]] as $t => [$label, $hitung]): ?>
                                <a href="<?= e($urlTab(['tab' => $t !== 'semua' ? $t : '', 'hal' => ''])) ?>"<?= $t === $tab ? ' class="aktif" aria-current="page"' : '' ?>>
                                    <?= e($label) ?><?php if ($hitung !== null): ?> <span class="hitung"><?= $hitung ?></span><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                        <?php if ($kat !== ''): ?>
                            <a class="chip-saring" href="<?= e($urlTab(['kat' => '', 'hal' => ''])) ?>" title="Hapus saringan kategori">
                                <?= e(KATEGORI_NOTIF[$kat][0]) ?><?= ikon('silang') ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($bisaUrung): ?>
                        <div class="notif-urung" role="status">
                            <?= ikon('centang-ganda') ?>
                            <span><?= count($urung['ids']) ?> notifikasi baru saja ditandai dibaca.</span>
                            <form method="post" action="notifikasi.php">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="urungkan">
                                <input type="hidden" name="kembali" value="<?= e($alamat) ?>">
                                <button type="submit" class="tombol-teks"><?= ikon('urung') ?>Urungkan</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if (!$daftar): ?>
                        <div class="kosong-isi notif-kosong">
                            <span class="ikon-notif hijau besar"><?= ikon($tab === 'belum' ? 'centang-ganda' : 'lonceng') ?></span>
                            <b><?= $tab === 'belum' ? 'Semua sudah dibaca' : ($tab === 'penting' ? 'Tidak ada notifikasi penting' : 'Belum ada notifikasi') ?></b>
                            <span><?= $kat !== '' ? 'Tidak ada notifikasi kategori ' . e(KATEGORI_NOTIF[$kat][0]) . ' di tab ini.' : 'Kabar baru akan muncul di sini dan di ikon lonceng.' ?></span>
                        </div>
                    <?php else: ?>
                        <?php $grupKini = null; ?>
                        <?php foreach ($daftar as $n): ?>
                            <?php $grup = kelompok_notif((string) $n['created_at']); ?>
                            <?php if ($grup !== $grupKini): ?>
                                <?php if ($grupKini !== null): ?></ul><?php endif; ?>
                                <h3 class="notif-grup"><?= e($grup) ?></h3>
                                <ul class="daftar-notif">
                                <?php $grupKini = $grup; ?>
                            <?php endif; ?>
                            <?= baris_notif($n, $alamat, false) ?>
                        <?php endforeach; ?>
                        </ul>

                        <div class="kaki-notif">
                            <span class="redup kecil"><?= ($hal - 1) * NOTIF_PER_HAL + 1 ?>–<?= min($total, $hal * NOTIF_PER_HAL) ?> dari <?= $total ?></span>
                            <?php if ($jumlahHal > 1): ?>
                                <nav class="halaman-nav" aria-label="Halaman notifikasi">
                                    <?php if ($hal > 1): ?><a class="tombol-ikon-kecil" href="<?= e($urlTab(['hal' => $hal - 1 > 1 ? $hal - 1 : ''])) ?>" aria-label="Halaman sebelumnya"><?= ikon('kiri') ?></a><?php endif; ?>
                                    <span class="kecil">Halaman <?= $hal ?> / <?= $jumlahHal ?></span>
                                    <?php if ($hal < $jumlahHal): ?><a class="tombol-ikon-kecil" href="<?= e($urlTab(['hal' => $hal + 1])) ?>" aria-label="Halaman berikutnya"><?= ikon('kanan') ?></a><?php endif; ?>
                                </nav>
                            <?php endif; ?>
                            <?php if ($ringkas['total'] > $ringkas['belum']): ?>
                                <form method="post" action="notifikasi.php" data-konfirmasi="Hapus <?= $ringkas['total'] - $ringkas['belum'] ?> notifikasi yang sudah dibaca? Tindakan ini tidak dapat dibatalkan.">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="hapus_dibaca">
                                    <input type="hidden" name="kembali" value="notifikasi.php">
                                    <button type="submit" class="tombol-teks bahaya"><?= ikon('sampah') ?>Hapus yang sudah dibaca</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="kolom-notif">
                    <section class="kartu" aria-labelledby="judulKatNotif">
                        <div class="kepala-kartu"><h2 id="judulKatNotif">Kategori</h2><p>Klik untuk menyaring daftar</p></div>
                        <ul class="daftar-kat">
                            <?php foreach (KATEGORI_NOTIF as $k => [$label, , $warna]): ?>
                                <?php $h = $perKat[$k] ?? ['total' => 0, 'belum' => 0]; ?>
                                <li>
                                    <a href="<?= e($urlTab(['kat' => $k === $kat ? '' : $k, 'hal' => ''])) ?>"<?= $k === $kat ? ' class="aktif" aria-current="true"' : '' ?>>
                                        <?= ikon_notif($k) ?>
                                        <span><?= e($label) ?></span>
                                        <?php if ($h['belum'] > 0): ?><span class="hitung baru"><?= $h['belum'] ?> baru</span><?php endif; ?>
                                        <span class="hitung"><?= $h['total'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="kartu" id="pengaturan" aria-labelledby="judulAturNotif">
                        <div class="kepala-kartu"><h2 id="judulAturNotif"><?= ikon('gerigi') ?>Pengaturan notifikasi</h2><p>Pilih kabar yang ingin Anda terima</p></div>
                        <form method="post" action="notifikasi.php" class="form-pref">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="preferensi">
                            <input type="hidden" name="kembali" value="notifikasi.php#pengaturan">
                            <?php foreach (KATEGORI_NOTIF as $k => [$label, , , $ket]): ?>
                                <?php $wajib = in_array($k, KATEGORI_NOTIF_WAJIB, true); $aktif = $wajib || ($pref[$k] ?? 1) === 1; ?>
                                <label class="baris-pref<?= $wajib ? ' terkunci' : '' ?>">
                                    <span class="teks-pref"><b><?= e($label) ?></b><small><?= e($ket) ?><?= $wajib ? ' Selalu aktif.' : '' ?></small></span>
                                    <span class="saklar">
                                        <input type="checkbox" name="kat[<?= e($k) ?>]" value="1"<?= $aktif ? ' checked' : '' ?><?= $wajib ? ' disabled' : '' ?>>
                                        <span aria-hidden="true"></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <p class="catatan-pref"><?= ikon('info') ?><span>Notifikasi berlabel <b>Penting</b> (kontrak berakhir, dokumen kedaluwarsa) tetap dikirim walau kategorinya dimatikan.</span></p>
                            <button type="submit" class="tombol utama penuh"><?= ikon('centang') ?>Simpan pengaturan</button>
                        </form>
                    </section>
                </aside>
            </div>
<?php
halaman_akhir();
