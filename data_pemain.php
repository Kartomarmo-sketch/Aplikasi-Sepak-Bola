<?php
declare(strict_types=1);

// Data Pemain (admin/HR): daftar skuad, tambah/ubah pemain, nonaktifkan, ekspor.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';

start_secure_session();
wajib_admin();

const POLA_TELEPON_ADMIN = '/^\+?[0-9][0-9\s\-()]{5,23}$/';
$alamat = 'data_pemain.php';
$idEdit = (int) ($_POST['id'] ?? $_GET['ubah'] ?? 0);
$kembali = alamat_detail_kembali($_POST['kembali'] ?? $_GET['kembali'] ?? null, $idEdit);
$parameterKembali = $kembali !== null ? '&kembali=' . rawurlencode($kembali) : '';

function tanggal_ok(string $t): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}

// =====================================================================
// POST
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash_set('galat', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        kembali_ke($idEdit > 0 ? $alamat . '?ubah=' . $idEdit . $parameterKembali : $alamat);
    }
    $aksi = (string) ($_POST['aksi'] ?? '');
    try {
        if ($aksi === 'simpan') {
            $id = (int) ($_POST['id'] ?? 0);
            $v = [];
            foreach (['nama_lengkap', 'username', 'nomor_punggung', 'posisi_kode', 'tanggal_lahir', 'tempat_lahir', 'kewarganegaraan',
                      'tinggi_cm', 'berat_kg', 'kaki_dominan', 'telepon', 'email', 'status', 'tanggal_bergabung',
                      'alamat', 'golongan_darah', 'kontak_darurat_nama', 'kontak_darurat_hubungan', 'kontak_darurat_telepon', 'kembali'] as $k) {
                $v[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            $g = [];
            if (mb_strlen($v['alamat']) > 255) {
                $g['alamat'] = 'Alamat maksimal 255 karakter.';
            }
            if ($v['golongan_darah'] !== '' && !in_array($v['golongan_darah'], ['A', 'B', 'AB', 'O'], true)) {
                $g['golongan_darah'] = 'Golongan darah tidak valid.';
            }
            if ($v['kontak_darurat_nama'] . $v['kontak_darurat_hubungan'] . $v['kontak_darurat_telepon'] !== '') {
                if (mb_strlen($v['kontak_darurat_nama']) < 3 || mb_strlen($v['kontak_darurat_nama']) > 100) {
                    $g['kontak_darurat_nama'] = 'Nama kontak darurat 3–100 karakter.';
                }
                if (mb_strlen($v['kontak_darurat_hubungan']) > 40) {
                    $g['kontak_darurat_hubungan'] = 'Maksimal 40 karakter.';
                }
                if (!preg_match(POLA_TELEPON_ADMIN, $v['kontak_darurat_telepon'])) {
                    $g['kontak_darurat_telepon'] = 'Telepon darurat wajib & harus valid.';
                }
            }
            if (mb_strlen($v['nama_lengkap']) < 3 || mb_strlen($v['nama_lengkap']) > 100) {
                $g['nama_lengkap'] = 'Nama 3–100 karakter.';
            }
            if ($id === 0) {
                if (!preg_match('/^[a-z0-9_.]{3,30}$/', $v['username'])) {
                    $g['username'] = 'Username 3–30 karakter: huruf kecil, angka, titik, atau garis bawah.';
                } else {
                    $st = db()->prepare('SELECT 1 FROM users WHERE username = :u');
                    $st->execute([':u' => $v['username']]);
                    if ($st->fetchColumn()) {
                        $g['username'] = 'Username sudah dipakai.';
                    }
                }
            }
            $nomor = filter_var($v['nomor_punggung'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
            if ($nomor === false) {
                $g['nomor_punggung'] = 'Nomor punggung 1–99.';
            } else {
                $st = db()->prepare("SELECT u.nama_lengkap FROM pemain p JOIN users u ON u.id = p.user_id
                                      WHERE p.nomor_punggung = :n AND u.is_active = 1 AND u.id <> :id LIMIT 1");
                $st->execute([':n' => $nomor, ':id' => $id]);
                if ($pemilik = $st->fetchColumn()) {
                    $g['nomor_punggung'] = 'Nomor ' . $nomor . ' sudah dipakai ' . $pemilik . '.';
                }
            }
            if (!isset(KODE_POSISI[$v['posisi_kode']])) {
                $g['posisi_kode'] = 'Pilih posisi.';
            }
            if (!tanggal_ok($v['tanggal_lahir'])) {
                $g['tanggal_lahir'] = 'Tanggal lahir tidak valid.';
            } elseif (($u = usia($v['tanggal_lahir'])) < 15 || $u > 45) {
                $g['tanggal_lahir'] = 'Usia pemain harus 15–45 tahun.';
            }
            if (mb_strlen($v['kewarganegaraan']) < 2 || mb_strlen($v['kewarganegaraan']) > 60) {
                $g['kewarganegaraan'] = 'Isi kewarganegaraan.';
            }
            if ($v['tinggi_cm'] !== '' && filter_var($v['tinggi_cm'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 140, 'max_range' => 220]]) === false) {
                $g['tinggi_cm'] = 'Tinggi 140–220 cm.';
            }
            if ($v['berat_kg'] !== '' && (!is_numeric($v['berat_kg']) || (float) $v['berat_kg'] < 40 || (float) $v['berat_kg'] > 130)) {
                $g['berat_kg'] = 'Berat 40–130 kg.';
            }
            if (!in_array($v['kaki_dominan'], ['kanan', 'kiri', 'kedua'], true)) {
                $g['kaki_dominan'] = 'Pilih kaki dominan.';
            }
            if (!isset(LABEL_STATUS_PEMAIN[$v['status']])) {
                $g['status'] = 'Pilih status.';
            }
            if ($v['telepon'] !== '' && !preg_match(POLA_TELEPON_ADMIN, $v['telepon'])) {
                $g['telepon'] = 'Format telepon tidak valid.';
            }
            if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
                $g['email'] = 'Format email tidak valid.';
            }
            if ($v['tanggal_bergabung'] !== '' && !tanggal_ok($v['tanggal_bergabung'])) {
                $g['tanggal_bergabung'] = 'Tanggal bergabung tidak valid.';
            }
            if ($id > 0) {
                $st = db()->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'pemain'");
                $st->execute([':id' => $id]);
                if (!$st->fetchColumn()) {
                    flash_set('galat', 'Pemain tidak ditemukan.');
                    kembali_ke($alamat);
                }
            }
            if ($g) {
                $_SESSION['form_pemain'] = ['galat' => $g, 'nilai' => $v + ['id' => $id]];
                flash_set('galat', 'Periksa kembali isian data pemain.');
                kembali_ke($alamat . '?ubah=' . $id . $parameterKembali);
            }

            [$labelPos, $lini] = KODE_POSISI[$v['posisi_kode']];
            $kosong = static fn(string $s) => $s === '' ? null : $s;
            $pdo = db();
            $pdo->beginTransaction();
            if ($id === 0) {
                $sandi = sandi_sementara();
                $pdo->prepare("INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (?, ?, ?, 'pemain')")
                    ->execute([$v['username'], password_hash($sandi, PASSWORD_DEFAULT), $v['nama_lengkap']]);
                $id = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO pemain (user_id, nomor_punggung) VALUES (?, ?)')->execute([$id, $nomor]);
                $pdo->prepare('INSERT INTO profil_pemain (user_id, status) VALUES (?, ?)')->execute([$id, $v['status']]);
            } else {
                $pdo->prepare('UPDATE users SET nama_lengkap = ? WHERE id = ?')->execute([$v['nama_lengkap'], $id]);
                $pdo->prepare('INSERT INTO pemain (user_id, nomor_punggung) VALUES (?, ?) ON DUPLICATE KEY UPDATE nomor_punggung = VALUES(nomor_punggung)')
                    ->execute([$id, $nomor]);
                $pdo->prepare('INSERT IGNORE INTO profil_pemain (user_id) VALUES (?)')->execute([$id]);
            }
            $pdo->prepare('UPDATE profil_pemain SET posisi = ?, posisi_kode = ?, posisi_detail = ?, tanggal_lahir = ?, tempat_lahir = ?,
                                  kewarganegaraan = ?, tinggi_cm = ?, berat_kg = ?, kaki_dominan = ?, telepon = ?, email = ?,
                                  status = ?, tanggal_bergabung = ?, alamat = ?, golongan_darah = ?, kontak_darurat_nama = ?,
                                  kontak_darurat_hubungan = ?, kontak_darurat_telepon = ? WHERE user_id = ?')
                ->execute([$lini, $v['posisi_kode'], $labelPos, $v['tanggal_lahir'], $kosong($v['tempat_lahir']), $v['kewarganegaraan'],
                    $kosong($v['tinggi_cm']), $kosong($v['berat_kg']), $v['kaki_dominan'], $kosong($v['telepon']), $kosong($v['email']),
                    $v['status'], $kosong($v['tanggal_bergabung']), $kosong($v['alamat']), $kosong($v['golongan_darah']),
                    $kosong($v['kontak_darurat_nama']), $kosong($v['kontak_darurat_hubungan']), $kosong($v['kontak_darurat_telepon']), $id]);
            $pdo->commit();

            if (isset($sandi)) {
                catat_aktivitas('tambah', 'pemain', $id, $v['nama_lengkap']);
                // Ditampilkan sekali sebagai kartu (bukan toast) agar sempat dicatat.
                $_SESSION['akun_baru'] = ['nama' => $v['nama_lengkap'], 'username' => $v['username'], 'sandi' => $sandi];
                flash_set('sukses', $v['nama_lengkap'] . ' ditambahkan ke skuad.');
            } else {
                catat_aktivitas('ubah', 'pemain', $id, $v['nama_lengkap']);
                flash_set('sukses', 'Data ' . $v['nama_lengkap'] . ' diperbarui.');
            }
            // Pemain baru: kembali ke daftar agar kartu kata sandi sementara terlihat. Ubah: ke halaman detail.
            kembali_ke(isset($sandi) ? $alamat : ($kembali ?? 'detail_karyawan.php?id=' . $id));
        }

        if ($aksi === 'nonaktif') {
            $id = (int) ($_POST['id'] ?? 0);
            $st = db()->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role = 'pemain' AND is_active = 1");
            $st->execute([':id' => $id]);
            if ($st->rowCount() === 1) {
                catat_aktivitas('nonaktifkan', 'pemain', $id);
                flash_set('sukses', 'Pemain dinonaktifkan dan tidak bisa login lagi. Data & riwayatnya tetap tersimpan.');
            } else {
                flash_set('galat', 'Pemain tidak ditemukan atau sudah nonaktif.');
            }
            kembali_ke($alamat);
        }
    } catch (PDOException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        error_log('Data pemain gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Data belum dapat disimpan. Coba lagi nanti.');
        kembali_ke($kembali ?? $alamat);
    }
    kembali_ke($alamat);
}

// =====================================================================
// Data tampilan
// =====================================================================
kirim_pengingat_kontrak();
$form = $_SESSION['form_pemain'] ?? null;
unset($_SESSION['form_pemain']);
$akunBaru = $_SESSION['akun_baru'] ?? null;
unset($_SESSION['akun_baru']);
$dbGagal = false;
$daftar = [];
try {
    $daftar = db()->query(
        "SELECT u.id, u.username, u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.posisi_kode, pr.posisi_detail, pr.tanggal_lahir,
                pr.tempat_lahir, pr.kewarganegaraan, pr.tinggi_cm, pr.berat_kg, pr.kaki_dominan, pr.telepon, pr.email, pr.status,
                pr.tanggal_bergabung, pr.foto, pr.alamat, pr.golongan_darah, pr.kontak_darurat_nama, pr.kontak_darurat_hubungan,
                pr.kontak_darurat_telepon, k.nomor_kontrak, k.tanggal_selesai, k.gaji_pokok, k.status AS status_k
           FROM users u
           LEFT JOIN pemain pm        ON pm.user_id = u.id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id
           LEFT JOIN kontrak k ON k.id = (SELECT k2.id FROM kontrak k2 WHERE k2.user_id = u.id AND k2.status = 'aktif'
                                          ORDER BY k2.tanggal_selesai DESC LIMIT 1)
          WHERE u.role = 'pemain' AND u.is_active = 1
          ORDER BY pm.nomor_punggung IS NULL, pm.nomor_punggung, u.nama_lengkap"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Data pemain gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

function kelompok_usia(?int $u): string
{
    return $u === null ? '' : ($u < 21 ? 'u21' : ($u <= 25 ? '21-25' : ($u <= 30 ? '26-30' : '31+')));
}

$tombol = '<a class="tombol" href="ekspor.php?jenis=pemain">' . ikon('unduh') . 'Ekspor</a>'
        . '<button type="button" class="tombol utama" ' . isi_dialog('dialogPemain', ['id' => 0], 'Tambah pemain') . '>' . ikon('tambah') . 'Tambah pemain</button>';
halaman_awal('Data Pemain', 'Data Pemain');
kepala_halaman('orang', 'Data Pemain', count($daftar) . ' pemain aktif di seluruh tim.', '<div class="aksi-kanan">' . $tombol . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data pemain belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$usiaSemua = array_filter(array_map(static fn($p) => usia($p['tanggal_lahir']), $daftar), static fn($u) => $u !== null);
$asing = count(array_filter($daftar, static fn($p) => $p['kewarganegaraan'] && strcasecmp((string) $p['kewarganegaraan'], 'Indonesia') !== 0));
$hampir = count(array_filter($daftar, static fn($p) => in_array(status_kontrak($p['status_k'] ? ['status' => $p['status_k'], 'tanggal_selesai' => $p['tanggal_selesai']] : null), ['hampir', 'kedaluwarsa'], true)));
$tersedia = count(array_filter($daftar, static fn($p) => $p['status'] === 'aktif'));
$perLini = array_fill_keys(array_keys(LABEL_LINI), 0);
foreach ($daftar as $p) {
    if (isset($perLini[$p['posisi']])) {
        $perLini[$p['posisi']]++;
    }
}
?>
            <?php if ($akunBaru): ?>
                <div class="kartu akun-baru" role="status">
                    <?= ikon('perisai') ?>
                    <div>
                        <b>Akun login untuk <?= e($akunBaru['nama']) ?> sudah dibuat.</b>
                        <p>Username <code><?= e($akunBaru['username']) ?></code> · kata sandi sementara <code><?= e($akunBaru['sandi']) ?></code></p>
                        <small>Catat dan serahkan langsung ke pemain. Kata sandi ini tidak akan ditampilkan lagi; minta pemain menggantinya lewat menu Ganti password.</small>
                    </div>
                </div>
            <?php endif; ?>

            <section class="kpi-kisi" aria-label="Ringkasan skuad">
                <?= kartu_kpi('Pemain terdaftar', angka(count($daftar)), 'orang', 'hijau', e($tersedia . ' siap main · ' . (count($daftar) - $tersedia) . ' cedera/diskors/dipinjam')) ?>
                <?= kartu_kpi('Rata-rata usia', $usiaSemua ? desimal(array_sum($usiaSemua) / count($usiaSemua)) . ' th' : '-', 'kalender', 'biru',
                    $usiaSemua ? 'Termuda ' . min($usiaSemua) . ' · tertua ' . max($usiaSemua) : '') ?>
                <?= kartu_kpi('Pemain asing', angka($asing), 'pin', 'ungu', (count($daftar) - $asing) . ' pemain lokal') ?>
                <?= kartu_kpi('Kontrak ≤ 90 hari', angka($hampir), 'peringatan', $hampir ? 'kuning' : 'hijau', '<a href="kelola_kontrak.php?hal=hampir">Lihat kontrak hampir habis</a>') ?>
            </section>

            <section class="kartu komposisi" aria-label="Komposisi skuad per lini">
                <div class="kepala-kartu baris-kepala">
                    <div><h2>Kedalaman skuad</h2><p>Jumlah pemain aktif per lini</p></div>
                </div>
                <div class="batang-lini" role="img" aria-label="<?= e(implode(', ', array_map(static fn($k, $n) => LABEL_LINI[$k] . ' ' . $n, array_keys($perLini), $perLini))) ?>">
                    <?php foreach ($perLini as $lini => $n): ?>
                        <?php if ($n === 0) { continue; } ?>
                        <span class="lini-<?= e($lini) ?>" style="flex: <?= $n ?>"><b><?= $n ?></b> <?= e(LABEL_LINI[$lini]) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="kartu tabel-kartu" data-tabel aria-label="Daftar pemain">
                <div class="kepala-tabel">
                    <?= alat_tabel('Cari nama atau ID...', [
                        'posisi' => ['label' => 'Posisi', 'opsi' => array_map(static fn($v) => $v[0], KODE_POSISI)],
                        'lini'   => ['label' => 'Lini', 'opsi' => LABEL_LINI],
                        'usia'   => ['label' => 'Usia', 'opsi' => ['u21' => 'U-21', '21-25' => '21–25', '26-30' => '26–30', '31+' => '31+']],
                        'status' => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], LABEL_STATUS_PEMAIN)],
                        'kontrak' => ['label' => 'Kontrak', 'opsi' => ['aktif' => 'Aktif', 'hampir' => 'Hampir habis', 'kedaluwarsa' => 'Kedaluwarsa', 'tidak_ada' => 'Tidak ada']],
                    ]) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar">
                        <thead>
                            <tr>
                                <?= th_urut('Pemain') ?><?= th_urut('Posisi') ?><?= th_urut('No.', 'angka', 'asc') ?><?= th_urut('Usia', 'angka') ?>
                                <?= th_urut('Negara') ?><?= th_urut('Tinggi', 'angka') ?><?= th_urut('Berat', 'angka') ?><?= th_urut('Kaki') ?>
                                <?= th_urut('Status') ?><?= th_urut('Kontrak') ?><th scope="col"><span class="sr">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $p): ?>
                                <?php
                                $u = usia($p['tanggal_lahir']);
                                $sk = status_kontrak($p['status_k'] ? ['status' => $p['status_k'], 'tanggal_selesai' => $p['tanggal_selesai']] : null);
                                $kode = (string) ($p['posisi_kode'] ?? '');
                                $json = [
                                    'id' => (int) $p['id'], 'nama_lengkap' => $p['nama_lengkap'], 'username' => $p['username'],
                                    'nomor_punggung' => $p['nomor_punggung'], 'posisi_kode' => $kode, 'tanggal_lahir' => $p['tanggal_lahir'],
                                    'tempat_lahir' => $p['tempat_lahir'], 'kewarganegaraan' => $p['kewarganegaraan'], 'tinggi_cm' => $p['tinggi_cm'],
                                    'berat_kg' => $p['berat_kg'] !== null ? rtrim(rtrim((string) $p['berat_kg'], '0'), '.') : '', 'kaki_dominan' => $p['kaki_dominan'],
                                    'telepon' => $p['telepon'], 'email' => $p['email'], 'status' => $p['status'], 'tanggal_bergabung' => $p['tanggal_bergabung'],
                                    'alamat' => $p['alamat'], 'golongan_darah' => $p['golongan_darah'], 'kontak_darurat_nama' => $p['kontak_darurat_nama'],
                                    'kontak_darurat_hubungan' => $p['kontak_darurat_hubungan'], 'kontak_darurat_telepon' => $p['kontak_darurat_telepon'],
                                ];
                                $jsonPer[(int) $p['id']] = $json;
                                ?>
                                <tr data-posisi="<?= e($kode) ?>" data-lini="<?= e((string) $p['posisi']) ?>" data-usia="<?= e(kelompok_usia($u)) ?>"
                                    data-status="<?= e((string) $p['status']) ?>" data-kontrak="<?= e($sk === 'diperbarui' ? 'aktif' : $sk) ?>">
                                    <td>
                                        <a class="sel-pemain" href="detail_karyawan.php?id=<?= (int) $p['id'] ?>">
                                            <?= avatar_kecil((string) $p['nama_lengkap'], $p['foto']) ?>
                                            <span><b><?= e((string) $p['nama_lengkap']) ?></b><small class="sub-sel"><?= e(id_pemain((int) $p['id'])) ?></small></span>
                                        </a>
                                    </td>
                                    <td class="sel-tgl" data-nilai="<?= e($kode) ?>"><span class="kode-posisi lini-<?= e((string) $p['posisi']) ?>"><?= e($kode ?: '?') ?></span> <?= e($kode ? KODE_POSISI[$kode][0] : '-') ?></td>
                                    <td data-nilai="<?= (int) $p['nomor_punggung'] ?>"><b><?= $p['nomor_punggung'] !== null ? (int) $p['nomor_punggung'] : '-' ?></b></td>
                                    <td data-nilai="<?= (int) $u ?>"><?= $u ?? '-' ?></td>
                                    <td><?= e((string) ($p['kewarganegaraan'] ?? '-')) ?></td>
                                    <td data-nilai="<?= (int) $p['tinggi_cm'] ?>"><?= $p['tinggi_cm'] ? (int) $p['tinggi_cm'] . ' cm' : '-' ?></td>
                                    <td data-nilai="<?= (float) $p['berat_kg'] ?>"><?= $p['berat_kg'] ? desimal($p['berat_kg'], 0) . ' kg' : '-' ?></td>
                                    <td><?= e(ucfirst((string) ($p['kaki_dominan'] ?? '-'))) ?></td>
                                    <td data-nilai="<?= e((string) $p['status']) ?>"><?= chip_peta(LABEL_STATUS_PEMAIN, (string) $p['status']) ?></td>
                                    <td data-nilai="<?= e($sk) ?>" title="<?= $p['tanggal_selesai'] ? 'Berakhir ' . e(tanggal_id((string) $p['tanggal_selesai'])) : '' ?>"><?= chip_peta(STATUS_KONTRAK_EFEKTIF, $sk) ?></td>
                                    <td>
                                        <?= menu_baris([
                                            [ikon('mata') . 'Lihat detail', 'detail_karyawan.php?id=' . (int) $p['id']],
                                            [ikon('dokumen') . 'Dokumen', 'detail_karyawan.php?tab=dokumen&id=' . (int) $p['id']],
                                            [ikon('pena') . 'Ubah data', null, isi_dialog('dialogPemain', $json, 'Ubah ' . $p['nama_lengkap'])],
                                            [ikon('trofi') . 'Statistik', 'statistik.php?pemain=' . (int) $p['id']],
                                            [ikon('dokumen') . 'Kontrak', 'kelola_kontrak.php?hal=riwayat&cari=' . rawurlencode((string) $p['nama_lengkap'])],
                                            '-',
                                            [ikon('silang') . 'Nonaktifkan', null, 'class="bahaya" ' . isi_dialog('dialogNonaktif', ['id' => (int) $p['id'], 'nama' => $p['nama_lengkap']])],
                                        ], 'Aksi untuk ' . $p['nama_lengkap']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
            </section>

            <?php
            // ---------- Dialog tambah / ubah ----------
            $nilai = $form['nilai'] ?? [];
            $galat = $form['galat'] ?? [];
            // ?ubah=ID (dari halaman detail): formulir langsung terbuka berisi data pemain.
            $ubah = filter_input(INPUT_GET, 'ubah', FILTER_VALIDATE_INT);
            if (!$form && $ubah && isset($jsonPer[$ubah])) {
                $nilai = $jsonPer[$ubah];
                $form = ['nilai' => $nilai];
            }
            $medan = static function (string $nama, string $label, string $input, bool $wajib = false) use ($galat): string {
                return '<div class="medan' . (isset($galat[$nama]) ? ' salah' : '') . '"><label for="f_' . $nama . '">' . e($label)
                    . ($wajib ? '' : ' <small>(opsional)</small>') . '</label><div class="kolom">' . $input . '</div>'
                    . (isset($galat[$nama]) ? '<p class="pesan-salah">' . e($galat[$nama]) . '</p>' : '') . '</div>';
            };
            $teks = static fn(string $nama, string $tipe = 'text', string $ekstra = '') =>
                '<input type="' . $tipe . '" id="f_' . $nama . '" name="' . $nama . '" value="' . e((string) ($nilai[$nama] ?? '')) . '" ' . $ekstra . '>';
            $pilih = static function (string $nama, array $opsi) use ($nilai): string {
                $h = '<select id="f_' . $nama . '" name="' . $nama . '">';
                foreach ($opsi as $k => $l) {
                    $h .= '<option value="' . e((string) $k) . '"' . ((string) ($nilai[$nama] ?? '') === (string) $k ? ' selected' : '') . '>' . e($l) . '</option>';
                }
                return $h . '</select>';
            };
            $baru = (int) ($nilai['id'] ?? 0) === 0;
            ?>
            <dialog class="modal lebar" id="dialogPemain" aria-labelledby="judulDialogPemain"<?= $form ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($alamat) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <?php if ($kembali !== null): ?><input type="hidden" name="kembali" value="<?= e($kembali) ?>"><?php endif; ?>
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('orang') ?></span>
                        <div><h2 id="judulDialogPemain" data-judul-dialog><?= $baru ? 'Tambah pemain' : 'Ubah ' . e((string) ($nilai['nama_lengkap'] ?? 'pemain')) ?></h2>
                            <p>Data profil &amp; registrasi pemain. Kontrak dibuat terpisah di menu Kontrak.</p></div>
                        <?php if ($kembali !== null && !$baru): ?>
                            <a class="tutup-toast" style="text-decoration: none;" href="<?= e($kembali) ?>" aria-label="Kembali ke detail">&times;</a>
                        <?php else: ?>
                            <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                        <?php endif; ?>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <?= $medan('nama_lengkap', 'Nama lengkap', $teks('nama_lengkap', 'text', 'maxlength="100" required'), true) ?>
                            <div data-hanya-baru<?= $baru ? '' : ' hidden' ?>><?= $medan('username', 'Username login', $teks('username', 'text', 'maxlength="30" autocomplete="off"'), true) ?></div>
                        </div>
                        <div class="baris-3">
                            <?= $medan('nomor_punggung', 'Nomor punggung', $teks('nomor_punggung', 'number', 'min="1" max="99"'), true) ?>
                            <?= $medan('posisi_kode', 'Posisi', $pilih('posisi_kode', ['' => 'Pilih...'] + array_map(static fn($v) => $v[0], KODE_POSISI)), true) ?>
                            <?= $medan('status', 'Status', $pilih('status', array_map(static fn($v) => $v[0], LABEL_STATUS_PEMAIN)), true) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('tanggal_lahir', 'Tanggal lahir', $teks('tanggal_lahir', 'date'), true) ?>
                            <?= $medan('tempat_lahir', 'Tempat lahir', $teks('tempat_lahir', 'text', 'maxlength="80"')) ?>
                            <?= $medan('kewarganegaraan', 'Kewarganegaraan', $teks('kewarganegaraan', 'text', 'maxlength="60" list="daftarNegara"'), true) ?>
                        </div>
                        <datalist id="daftarNegara"><option value="Indonesia"><option value="Brasil"><option value="Jepang"><option value="Belanda"><option value="Korea Selatan"><option value="Argentina"></datalist>
                        <div class="baris-3">
                            <?= $medan('tinggi_cm', 'Tinggi (cm)', $teks('tinggi_cm', 'number', 'min="140" max="220"')) ?>
                            <?= $medan('berat_kg', 'Berat (kg)', $teks('berat_kg', 'number', 'min="40" max="130" step="0.1"')) ?>
                            <?= $medan('kaki_dominan', 'Kaki dominan', $pilih('kaki_dominan', ['kanan' => 'Kanan', 'kiri' => 'Kiri', 'kedua' => 'Kedua kaki']), true) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('telepon', 'Telepon', $teks('telepon', 'tel', 'maxlength="25"')) ?>
                            <?= $medan('email', 'Email', $teks('email', 'email', 'maxlength="120"')) ?>
                            <?= $medan('tanggal_bergabung', 'Tanggal bergabung', $teks('tanggal_bergabung', 'date')) ?>
                        </div>
                        <div class="baris-2">
                            <?= $medan('alamat', 'Alamat', $teks('alamat', 'text', 'maxlength="255"')) ?>
                            <?= $medan('golongan_darah', 'Golongan darah', $pilih('golongan_darah', ['' => 'Belum diketahui', 'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'])) ?>
                        </div>
                        <h3 class="pemisah-bagian">Kontak darurat</h3>
                        <div class="baris-3">
                            <?= $medan('kontak_darurat_nama', 'Nama', $teks('kontak_darurat_nama', 'text', 'maxlength="100"')) ?>
                            <?= $medan('kontak_darurat_hubungan', 'Hubungan', $teks('kontak_darurat_hubungan', 'text', 'maxlength="40" placeholder="Ibu, Ayah, Istri..."')) ?>
                            <?= $medan('kontak_darurat_telepon', 'Telepon', $teks('kontak_darurat_telepon', 'tel', 'maxlength="25"')) ?>
                        </div>
                        <p class="bantuan" data-hanya-baru<?= $baru ? '' : ' hidden' ?>>Kata sandi sementara dibuat otomatis dan ditampilkan sekali setelah disimpan.</p>
                    </div>
                    <div class="modal-kaki">
                        <?php if ($kembali !== null && !$baru): ?>
                            <a class="tombol" href="<?= e($kembali) ?>">Batal</a>
                        <?php else: ?>
                            <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <?php endif; ?>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan</button>
                    </div>
                </form>
            </dialog>

            <dialog class="modal" id="dialogNonaktif" aria-labelledby="judulNonaktif">
                <form method="post" action="<?= e($alamat) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="nonaktif">
                    <input type="hidden" name="id" value="">
                    <div class="modal-kepala">
                        <span class="ikon-judul warna-netral"><?= ikon('peringatan') ?></span>
                        <div><h2 id="judulNonaktif">Nonaktifkan <span data-isi="nama"></span>?</h2>
                            <p>Pemain tidak bisa login dan hilang dari daftar skuad. Riwayat kontrak, gaji, dan statistik tetap tersimpan.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol bahaya"><?= ikon('silang') ?>Nonaktifkan</button>
                    </div>
                </form>
            </dialog>
<?php
halaman_akhir();
