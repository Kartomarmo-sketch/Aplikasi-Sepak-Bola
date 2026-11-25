<?php
declare(strict_types=1);

// Data Staf (admin/HR): daftar staf, struktur organisasi, tambah/ubah, nonaktifkan, ekspor.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';

start_secure_session();
wajib_admin();

$alamat = 'data_staf.php';
$idEdit = (int) ($_POST['id'] ?? $_GET['ubah'] ?? 0);
$kembali = alamat_detail_kembali($_POST['kembali'] ?? $_GET['kembali'] ?? null, $idEdit);
$parameterKembali = $kembali !== null ? '&kembali=' . rawurlencode($kembali) : '';

function tgl_ok(string $t): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}

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
            foreach (['nama_lengkap', 'username', 'role', 'nip', 'jabatan', 'jabatan_detail', 'divisi', 'atasan_id', 'status_kerja',
                      'tanggal_lahir', 'telepon', 'email', 'tanggal_bergabung', 'lisensi', 'nomor_lisensi', 'lisensi_berlaku_sampai',
                      'tempat_lahir', 'kewarganegaraan', 'alamat', 'kontak_darurat_nama', 'kontak_darurat_hubungan', 'kontak_darurat_telepon'] as $k) {
                $v[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            $passwordBaru = (string) ($_POST['password_baru'] ?? '');
            $passwordUlang = (string) ($_POST['password_ulang'] ?? '');
            $g = [];
            if ($id > 0 && ($passwordBaru !== '' || $passwordUlang !== '')) {
                $galatPassword = pesan_galat_password_baru($passwordBaru);
                if ($galatPassword !== '') {
                    $g['password_baru'] = $galatPassword;
                }
                if ($passwordUlang !== $passwordBaru) {
                    $g['password_ulang'] = 'Konfirmasi password tidak cocok.';
                }
            }
            foreach (['tempat_lahir' => 80, 'kewarganegaraan' => 60, 'alamat' => 255] as $k => $maks) {
                if (mb_strlen($v[$k]) > $maks) {
                    $g[$k] = 'Maksimal ' . $maks . ' karakter.';
                }
            }
            if ($v['kontak_darurat_nama'] . $v['kontak_darurat_hubungan'] . $v['kontak_darurat_telepon'] !== '') {
                if (mb_strlen($v['kontak_darurat_nama']) < 3 || mb_strlen($v['kontak_darurat_nama']) > 100) {
                    $g['kontak_darurat_nama'] = 'Nama kontak darurat 3–100 karakter.';
                }
                if (mb_strlen($v['kontak_darurat_hubungan']) > 40) {
                    $g['kontak_darurat_hubungan'] = 'Maksimal 40 karakter.';
                }
                if (!preg_match('/^\+?[0-9][0-9\s\-()]{5,23}$/', $v['kontak_darurat_telepon'])) {
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
                if (!in_array($v['role'], ['admin', 'pelatih_medis'], true)) {
                    $g['role'] = 'Pilih hak akses.';
                }
            }
            if ($v['nip'] !== '') {
                $st = db()->prepare('SELECT 1 FROM staf WHERE nip = :n AND user_id <> :id');
                $st->execute([':n' => $v['nip'], ':id' => $id]);
                if ($st->fetchColumn()) {
                    $g['nip'] = 'NIP sudah dipakai staf lain.';
                }
            }
            if (!isset(LABEL_JABATAN_ENUM[$v['jabatan']])) {
                $g['jabatan'] = 'Pilih kelompok jabatan.';
            }
            if (mb_strlen($v['jabatan_detail']) > 80) {
                $g['jabatan_detail'] = 'Maksimal 80 karakter.';
            }
            if (!isset(LABEL_DIVISI[$v['divisi']])) {
                $g['divisi'] = 'Pilih divisi.';
            }
            if (!isset(LABEL_STATUS_KERJA[$v['status_kerja']])) {
                $g['status_kerja'] = 'Pilih status kerja.';
            }
            if ($v['atasan_id'] !== '') {
                $st = db()->prepare("SELECT 1 FROM users WHERE id = :a AND role <> 'pemain' AND is_active = 1 AND id <> :id");
                $st->execute([':a' => (int) $v['atasan_id'], ':id' => $id]);
                if (!$st->fetchColumn()) {
                    $g['atasan_id'] = 'Atasan tidak valid.';
                }
            }
            foreach (['tanggal_lahir', 'tanggal_bergabung', 'lisensi_berlaku_sampai'] as $t) {
                if ($v[$t] !== '' && !tgl_ok($v[$t])) {
                    $g[$t] = 'Tanggal tidak valid.';
                }
            }
            if ($v['telepon'] !== '' && !preg_match('/^\+?[0-9][0-9\s\-()]{5,23}$/', $v['telepon'])) {
                $g['telepon'] = 'Format telepon tidak valid.';
            }
            if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
                $g['email'] = 'Format email tidak valid.';
            }
            if ($id > 0) {
                $st = db()->prepare("SELECT 1 FROM users WHERE id = :id AND role <> 'pemain'");
                $st->execute([':id' => $id]);
                if (!$st->fetchColumn()) {
                    flash_set('galat', 'Staf tidak ditemukan.');
                    kembali_ke($alamat);
                }
            }
            if ($g) {
                $_SESSION['form_staf'] = ['galat' => $g, 'nilai' => $v + ['id' => $id]];
                flash_set('galat', 'Periksa kembali isian data staf.');
                kembali_ke($alamat . '?ubah=' . $id . $parameterKembali);
            }

            $n = static fn(string $s) => $s === '' ? null : $s;
            $pdo = db();
            $pdo->beginTransaction();
            if ($id === 0) {
                $sandi = sandi_sementara();
                $pdo->prepare('INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (?, ?, ?, ?)')
                    ->execute([$v['username'], password_hash($sandi, PASSWORD_DEFAULT), $v['nama_lengkap'], $v['role']]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare('UPDATE users SET nama_lengkap = ? WHERE id = ?')->execute([$v['nama_lengkap'], $id]);
                if ($passwordBaru !== '') {
                    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                        ->execute([password_hash($passwordBaru, PASSWORD_DEFAULT), $id]);
                }
            }
            $pdo->prepare('INSERT IGNORE INTO staf (user_id) VALUES (?)')->execute([$id]);
            $pdo->prepare('UPDATE staf SET nip = ?, jabatan = ?, jabatan_detail = ?, divisi = ?, atasan_id = ?, status_kerja = ?, tanggal_lahir = ?,
                                  telepon = ?, email = ?, tanggal_bergabung = ?, lisensi = ?, nomor_lisensi = ?, lisensi_berlaku_sampai = ?,
                                  tempat_lahir = ?, kewarganegaraan = ?, alamat = ?, kontak_darurat_nama = ?, kontak_darurat_hubungan = ?,
                                  kontak_darurat_telepon = ?
                            WHERE user_id = ?')
                ->execute([$n($v['nip']), $v['jabatan'], $n($v['jabatan_detail']), $v['divisi'], $n($v['atasan_id']), $v['status_kerja'],
                    $n($v['tanggal_lahir']), $n($v['telepon']), $n($v['email']), $n($v['tanggal_bergabung']), $n($v['lisensi']),
                    $n($v['nomor_lisensi']), $n($v['lisensi_berlaku_sampai']), $n($v['tempat_lahir']), $n($v['kewarganegaraan']), $n($v['alamat']),
                    $n($v['kontak_darurat_nama']), $n($v['kontak_darurat_hubungan']), $n($v['kontak_darurat_telepon']), $id]);
            $pdo->commit();

            if (isset($sandi)) {
                catat_aktivitas('tambah', 'staf', $id, $v['nama_lengkap']);
                $_SESSION['akun_baru'] = ['nama' => $v['nama_lengkap'], 'username' => $v['username'], 'sandi' => $sandi];
                flash_set('sukses', $v['nama_lengkap'] . ' ditambahkan sebagai staf.');
            } else {
                catat_aktivitas('ubah', 'staf', $id, $v['nama_lengkap']);
                if ($passwordBaru !== '') {
                    catat_aktivitas('ubah', 'users', $id, 'Admin mereset password staf');
                }
                flash_set('sukses', 'Data ' . $v['nama_lengkap'] . ($passwordBaru !== '' ? ' dan password diperbarui.' : ' diperbarui.'));
            }
            // Staf baru: kembali ke daftar (kartu kata sandi). Ubah: ke halaman detail.
            kembali_ke(isset($sandi) ? $alamat : ($kembali ?? 'detail_karyawan.php?id=' . $id));
        }

        if ($aksi === 'nonaktif') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) $_SESSION['user_id']) {
                flash_set('galat', 'Anda tidak bisa menonaktifkan akun sendiri.');
                kembali_ke($alamat);
            }
            $st = db()->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role <> 'pemain' AND is_active = 1");
            $st->execute([':id' => $id]);
            if ($st->rowCount() === 1) {
                db()->prepare("UPDATE staf SET status = 'nonaktif' WHERE user_id = ?")->execute([$id]);
                catat_aktivitas('nonaktifkan', 'staf', $id);
                flash_set('sukses', 'Staf dinonaktifkan. Data dan riwayatnya tetap tersimpan.');
            } else {
                flash_set('galat', 'Staf tidak ditemukan atau sudah nonaktif.');
            }
            kembali_ke($alamat);
        }
    } catch (PDOException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        error_log('Data staf gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Data belum dapat disimpan. Coba lagi nanti.');
        kembali_ke($kembali ?? $alamat);
    }
    kembali_ke($alamat);
}

kirim_pengingat_kontrak();
$form = $_SESSION['form_staf'] ?? null;
unset($_SESSION['form_staf']);
$akunBaru = $_SESSION['akun_baru'] ?? null;
unset($_SESSION['akun_baru']);
$dbGagal = false;
$daftar = [];
try {
    $daftar = db()->query(
        "SELECT u.id, u.username, u.nama_lengkap, u.role, s.*, a.nama_lengkap AS atasan,
                k.nomor_kontrak, k.tanggal_selesai, k.status AS status_k
           FROM users u
           JOIN staf s ON s.user_id = u.id
           LEFT JOIN users a ON a.id = s.atasan_id
           LEFT JOIN kontrak k ON k.id = (SELECT k2.id FROM kontrak k2 WHERE k2.user_id = u.id AND k2.status = 'aktif'
                                          ORDER BY k2.tanggal_selesai DESC LIMIT 1)
          WHERE u.role <> 'pemain' AND u.is_active = 1
          ORDER BY u.nama_lengkap"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Data staf gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

$tombol = '<a class="tombol" href="ekspor.php?jenis=staf">' . ikon('unduh') . 'Ekspor</a>'
        . '<button type="button" class="tombol utama" ' . isi_dialog('dialogStaf', ['id' => 0], 'Tambah staf') . '>' . ikon('tambah') . 'Tambah staf</button>';
halaman_awal('Data Staf', 'Data Staf');
kepala_halaman('rapat', 'Data Staf', count($daftar) . ' staf aktif.', '<div class="aksi-kanan">' . $tombol . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data staf belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$perDivisi = [];
foreach ($daftar as $s) {
    $perDivisi[$s['divisi']] = ($perDivisi[$s['divisi']] ?? 0) + 1;
}
arsort($perDivisi);
$lisensi = array_values(array_filter($daftar, static fn($s) => $s['lisensi_berlaku_sampai'] && selisih_hari(hari_ini()->format('Y-m-d'), (string) $s['lisensi_berlaku_sampai']) <= 180));
$tetap = count(array_filter($daftar, static fn($s) => $s['status_kerja'] === 'tetap'));
$masaKerja = array_filter(array_map(static fn($s) => $s['tanggal_bergabung'] ? (new DateTimeImmutable((string) $s['tanggal_bergabung']))->diff(hari_ini())->y : null, $daftar), static fn($x) => $x !== null);

// Pohon organisasi: atasan -> bawahan.
$anak = [];
$ids = array_column($daftar, 'id');
foreach ($daftar as $s) {
    $induk = $s['atasan_id'] !== null && in_array($s['atasan_id'], $ids, false) ? (int) $s['atasan_id'] : 0;
    $anak[$induk][] = $s;
}
function cetak_pohon(array $anak, int $induk, int $kedalaman = 0): string
{
    if (empty($anak[$induk]) || $kedalaman > 6) {
        return '';
    }
    $h = '<ul>';
    foreach ($anak[$induk] as $s) {
        $h .= '<li><span class="simpul-org"><b>' . e((string) $s['nama_lengkap']) . '</b><small>' . e((string) ($s['jabatan_detail'] ?: LABEL_JABATAN_ENUM[$s['jabatan']] ?? '')) . '</small></span>'
            . cetak_pohon($anak, (int) $s['id'], $kedalaman + 1) . '</li>';
    }
    return $h . '</ul>';
}
?>
            <?php if ($akunBaru): ?>
                <div class="kartu akun-baru" role="status">
                    <?= ikon('perisai') ?>
                    <div>
                        <b>Akun login untuk <?= e($akunBaru['nama']) ?> sudah dibuat.</b>
                        <p>Username <code><?= e($akunBaru['username']) ?></code> · kata sandi sementara <code><?= e($akunBaru['sandi']) ?></code></p>
                        <small>Catat dan serahkan langsung. Kata sandi ini tidak akan ditampilkan lagi.</small>
                    </div>
                </div>
            <?php endif; ?>

            <section class="kpi-kisi" aria-label="Ringkasan staf">
                <?= kartu_kpi('Staf aktif', angka(count($daftar)), 'rapat', 'hijau', e(count($perDivisi) . ' divisi')) ?>
                <?= kartu_kpi('Karyawan tetap', angka($tetap), 'perisai', 'biru', e((count($daftar) - $tetap) . ' kontrak / paruh waktu')) ?>
                <?= kartu_kpi('Rata-rata masa kerja', $masaKerja ? desimal(array_sum($masaKerja) / count($masaKerja)) . ' th' : '-', 'jam', 'ungu', 'Sejak tanggal bergabung') ?>
                <?= kartu_kpi('Lisensi ≤ 6 bulan', angka(count($lisensi)), 'dokumen', count($lisensi) ? 'kuning' : 'hijau',
                    $lisensi ? e($lisensi[0]['nama_lengkap'] . ' · ' . tanggal_id((string) $lisensi[0]['lisensi_berlaku_sampai'])) : 'Semua lisensi/STR masih berlaku') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulOrg">
                    <div class="kepala-kartu"><h2 id="judulOrg">Struktur organisasi</h2><p>Dari atasan langsung tiap staf</p></div>
                    <div class="pohon-org"><?= cetak_pohon($anak, 0) ?></div>
                </section>
                <section class="kartu" aria-labelledby="judulDivisi">
                    <div class="kepala-kartu"><h2 id="judulDivisi">Staf per divisi</h2><p><?= count($daftar) ?> orang</p></div>
                    <ul class="meteran-daftar">
                        <?php foreach ($perDivisi as $d => $n): ?>
                            <li><div class="baris"><span><?= e(LABEL_DIVISI[$d] ?? $d) ?></span><b><?= $n ?></b></div>
                                <div class="progres"><span style="width: <?= round(100 * $n / max($perDivisi)) ?>%"></span></div></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>

            <section class="kartu tabel-kartu" data-tabel aria-label="Daftar staf">
                <div class="kepala-tabel">
                    <?= alat_tabel('Cari nama atau ID...', [
                        'divisi'  => ['label' => 'Divisi', 'opsi' => LABEL_DIVISI],
                        'status'  => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], LABEL_STATUS_KERJA)],
                        'kontrak' => ['label' => 'Kontrak', 'opsi' => ['aktif' => 'Aktif', 'hampir' => 'Hampir habis', 'kedaluwarsa' => 'Kedaluwarsa', 'tidak_ada' => 'Tidak ada']],
                    ]) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar">
                        <thead>
                            <tr>
                                <?= th_urut('Staf', 'teks', 'asc') ?><?= th_urut('Jabatan') ?><?= th_urut('Divisi') ?><?= th_urut('Atasan') ?>
                                <?= th_urut('Bergabung', 'angka') ?><th scope="col">Kontak</th><?= th_urut('Status') ?><?= th_urut('Kontrak') ?>
                                <th scope="col"><span class="sr">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $s): ?>
                                <?php
                                $sk = status_kontrak($s['status_k'] ? ['status' => $s['status_k'], 'tanggal_selesai' => $s['tanggal_selesai']] : null);
                                $nip = (string) ($s['nip'] ?: 'STF-' . str_pad((string) $s['id'], 3, '0', STR_PAD_LEFT));
                                $jab = (string) ($s['jabatan_detail'] ?: LABEL_JABATAN_ENUM[$s['jabatan']] ?? '-');
                                $json = [
                                    'id' => (int) $s['id'], 'nama_lengkap' => $s['nama_lengkap'], 'username' => $s['username'], 'role' => $s['role'],
                                    'nip' => $s['nip'], 'jabatan' => $s['jabatan'], 'jabatan_detail' => $s['jabatan_detail'], 'divisi' => $s['divisi'],
                                    'atasan_id' => $s['atasan_id'], 'status_kerja' => $s['status_kerja'], 'tanggal_lahir' => $s['tanggal_lahir'],
                                    'telepon' => $s['telepon'], 'email' => $s['email'], 'tanggal_bergabung' => $s['tanggal_bergabung'],
                                    'lisensi' => $s['lisensi'], 'nomor_lisensi' => $s['nomor_lisensi'], 'lisensi_berlaku_sampai' => $s['lisensi_berlaku_sampai'],
                                    'tempat_lahir' => $s['tempat_lahir'], 'kewarganegaraan' => $s['kewarganegaraan'], 'alamat' => $s['alamat'],
                                    'kontak_darurat_nama' => $s['kontak_darurat_nama'], 'kontak_darurat_hubungan' => $s['kontak_darurat_hubungan'],
                                    'kontak_darurat_telepon' => $s['kontak_darurat_telepon'],
                                ];
                                $jsonPer[(int) $s['id']] = $json;
                                ?>
                                <tr data-divisi="<?= e((string) $s['divisi']) ?>" data-status="<?= e((string) $s['status_kerja']) ?>" data-kontrak="<?= e($sk) ?>">
                                    <td><a class="sel-pemain" href="detail_karyawan.php?id=<?= (int) $s['id'] ?>"><?= avatar_kecil((string) $s['nama_lengkap'], $s['foto']) ?><span><b><?= e((string) $s['nama_lengkap']) ?></b><small class="sub-sel"><?= e($nip) ?></small></span></a></td>
                                    <td><?= e($jab) ?><?php if ($s['lisensi']): ?><small class="sub-sel"><?= e((string) $s['lisensi']) ?></small><?php endif; ?></td>
                                    <td><?= e(LABEL_DIVISI[$s['divisi']] ?? '-') ?></td>
                                    <td><?= $s['atasan'] ? e((string) $s['atasan']) : '<span class="redup">-</span>' ?></td>
                                    <td class="sel-tgl" data-nilai="<?= $s['tanggal_bergabung'] ? strtotime((string) $s['tanggal_bergabung']) : 0 ?>"><?= $s['tanggal_bergabung'] ? e(tanggal_id((string) $s['tanggal_bergabung'])) : '-' ?></td>
                                    <td class="sel-kontak"><?= e((string) ($s['telepon'] ?? '-')) ?><small class="sub-sel"><?= e((string) ($s['email'] ?? '')) ?></small></td>
                                    <td data-nilai="<?= e((string) $s['status_kerja']) ?>"><?= chip_peta(LABEL_STATUS_KERJA, (string) $s['status_kerja']) ?></td>
                                    <td data-nilai="<?= e($sk) ?>" title="<?= $s['tanggal_selesai'] ? 'Berakhir ' . e(tanggal_id((string) $s['tanggal_selesai'])) : '' ?>"><?= chip_peta(STATUS_KONTRAK_EFEKTIF, $sk) ?></td>
                                    <td>
                                        <?php
                                        $item = [
                                            [ikon('mata') . 'Lihat detail', 'detail_karyawan.php?id=' . (int) $s['id']],
                                            [ikon('pena') . 'Ubah data', null, isi_dialog('dialogStaf', $json, 'Ubah ' . $s['nama_lengkap'])],
                                            [ikon('dokumen') . 'Dokumen', 'detail_karyawan.php?tab=dokumen&id=' . (int) $s['id']],
                                            [ikon('dokumen') . 'Kontrak', 'kelola_kontrak.php?hal=riwayat&cari=' . rawurlencode((string) $s['nama_lengkap'])],
                                        ];
                                        if ((int) $s['id'] !== (int) $_SESSION['user_id']) {
                                            $item[] = '-';
                                            $item[] = [ikon('silang') . 'Nonaktifkan', null, 'class="bahaya" ' . isi_dialog('dialogNonaktif', ['id' => (int) $s['id'], 'nama' => $s['nama_lengkap']])];
                                        }
                                        echo menu_baris($item, 'Aksi untuk ' . $s['nama_lengkap']);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
            </section>

            <?php
            $nilai = $form['nilai'] ?? [];
            $galat = $form['galat'] ?? [];
            // ?ubah=ID (dari halaman detail): formulir langsung terbuka berisi data staf.
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
            $opsiAtasan = ['' => 'Tidak ada (pimpinan tertinggi)'];
            foreach ($daftar as $s) {
                $opsiAtasan[$s['id']] = $s['nama_lengkap'] . ' — ' . ($s['jabatan_detail'] ?: LABEL_JABATAN_ENUM[$s['jabatan']] ?? '');
            }
            $baru = (int) ($nilai['id'] ?? 0) === 0;
            ?>
            <dialog class="modal lebar" id="dialogStaf" aria-labelledby="judulDialogStaf"<?= $form ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($alamat) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <?php if ($kembali !== null): ?><input type="hidden" name="kembali" value="<?= e($kembali) ?>"><?php endif; ?>
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('rapat') ?></span>
                        <div><h2 id="judulDialogStaf" data-judul-dialog><?= $baru ? 'Tambah staf' : 'Ubah ' . e((string) ($nilai['nama_lengkap'] ?? 'staf')) ?></h2>
                            <p>Data kepegawaian staf. Kontrak dibuat terpisah di menu Kontrak.</p></div>
                        <?php if ($kembali !== null && !$baru): ?>
                            <a class="tutup-toast" style="text-decoration: none;" href="<?= e($kembali) ?>" aria-label="Kembali ke detail">&times;</a>
                        <?php else: ?>
                            <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                        <?php endif; ?>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <?= $medan('nama_lengkap', 'Nama lengkap', $teks('nama_lengkap', 'text', 'maxlength="100"'), true) ?>
                            <?= $medan('nip', 'NIP', $teks('nip', 'text', 'maxlength="30" placeholder="STF-026"')) ?>
                        </div>
                        <div class="baris-2" data-hanya-baru<?= $baru ? '' : ' hidden' ?>>
                            <?= $medan('username', 'Username login', $teks('username', 'text', 'maxlength="30" autocomplete="off"'), true) ?>
                            <?= $medan('role', 'Hak akses', $pilih('role', ['pelatih_medis' => 'Pelatih / Medis / Teknis', 'admin' => 'Admin / Manager (akses HR)']), true) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('jabatan', 'Kelompok jabatan', $pilih('jabatan', LABEL_JABATAN_ENUM), true) ?>
                            <?= $medan('jabatan_detail', 'Nama jabatan', $teks('jabatan_detail', 'text', 'maxlength="80" placeholder="mis. Kitman / Perlengkapan"')) ?>
                            <?= $medan('divisi', 'Divisi', $pilih('divisi', LABEL_DIVISI), true) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('atasan_id', 'Atasan langsung', $pilih('atasan_id', $opsiAtasan)) ?>
                            <?= $medan('status_kerja', 'Status kerja', $pilih('status_kerja', array_map(static fn($v) => $v[0], LABEL_STATUS_KERJA)), true) ?>
                            <?= $medan('tanggal_bergabung', 'Tanggal bergabung', $teks('tanggal_bergabung', 'date')) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('tanggal_lahir', 'Tanggal lahir', $teks('tanggal_lahir', 'date')) ?>
                            <?= $medan('telepon', 'Telepon', $teks('telepon', 'tel', 'maxlength="25"')) ?>
                            <?= $medan('email', 'Email', $teks('email', 'email', 'maxlength="120"')) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('tempat_lahir', 'Tempat lahir', $teks('tempat_lahir', 'text', 'maxlength="80"')) ?>
                            <?= $medan('kewarganegaraan', 'Kewarganegaraan', $teks('kewarganegaraan', 'text', 'maxlength="60" placeholder="Indonesia"')) ?>
                            <?= $medan('alamat', 'Alamat', $teks('alamat', 'text', 'maxlength="255"')) ?>
                        </div>
                        <div data-hanya-ubah<?= $baru ? ' hidden' : '' ?>>
                            <h3 class="pemisah-bagian">Password akun</h3>
                            <div class="baris-2">
                                <div class="medan<?= isset($galat['password_baru']) ? ' salah' : '' ?>">
                                    <label for="passwordBaru">Password baru</label>
                                    <div class="kolom berpassword">
                                        <input type="password" id="passwordBaru" name="password_baru" autocomplete="new-password" maxlength="72"<?= isset($galat['password_baru']) ? ' aria-invalid="true" aria-describedby="galatPasswordBaru"' : '' ?>>
                                        <button type="button" class="tampil-password" data-tampil-password aria-pressed="false" aria-label="Tampilkan password">
                                            <svg class="ik mata-buka" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg class="ik mata-tutup" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><path d="M3 3l18 18M10.600 6.100A10 10 0 0 1 12 5c6.400 0 10 7 10 7a17 17 0 0 1-3.200 4M6.600 6.600A17 17 0 0 0 2 12s3.600 7 10 7c1.700 0 3.200-.4 4.500-1M9.900 9.900a3 3 0 0 1 4.200 4.200"/></svg>
                                        </button>
                                    </div>
                                    <?php if (isset($galat['password_baru'])): ?><p class="pesan-salah" id="galatPasswordBaru"><?= e($galat['password_baru']) ?></p><?php endif; ?>
                                </div>
                                <div class="medan<?= isset($galat['password_ulang']) ? ' salah' : '' ?>">
                                    <label for="passwordUlang">Ulangi password baru</label>
                                    <div class="kolom berpassword">
                                        <input type="password" id="passwordUlang" name="password_ulang" autocomplete="new-password" maxlength="72"<?= isset($galat['password_ulang']) ? ' aria-invalid="true" aria-describedby="galatPasswordUlang"' : '' ?>>
                                        <button type="button" class="tampil-password" data-tampil-password aria-pressed="false" aria-label="Tampilkan password">
                                            <svg class="ik mata-buka" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg class="ik mata-tutup" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><path d="M3 3l18 18M10.600 6.100A10 10 0 0 1 12 5c6.400 0 10 7 10 7a17 17 0 0 1-3.200 4M6.600 6.600A17 17 0 0 0 2 12s3.600 7 10 7c1.700 0 3.200-.4 4.500-1M9.900 9.900a3 3 0 0 1 4.200 4.200"/></svg>
                                        </button>
                                    </div>
                                    <?php if (isset($galat['password_ulang'])): ?><p class="pesan-salah" id="galatPasswordUlang"><?= e($galat['password_ulang']) ?></p><?php endif; ?>
                                </div>
                            </div>
                            <p class="bantuan">Kosongkan jika tidak ingin mengganti password. Password lama tidak dapat ditampilkan karena tersimpan dengan hash.</p>
                        </div>
                        <h3 class="pemisah-bagian">Kontak darurat</h3>
                        <div class="baris-3">
                            <?= $medan('kontak_darurat_nama', 'Nama', $teks('kontak_darurat_nama', 'text', 'maxlength="100"')) ?>
                            <?= $medan('kontak_darurat_hubungan', 'Hubungan', $teks('kontak_darurat_hubungan', 'text', 'maxlength="40" placeholder="Istri, Suami, Ibu..."')) ?>
                            <?= $medan('kontak_darurat_telepon', 'Telepon', $teks('kontak_darurat_telepon', 'tel', 'maxlength="25"')) ?>
                        </div>
                        <h3 class="pemisah-bagian">Lisensi / sertifikasi</h3>
                        <div class="baris-3">
                            <?= $medan('lisensi', 'Lisensi', $teks('lisensi', 'text', 'maxlength="100" placeholder="AFC A, STR Dokter, ..."')) ?>
                            <?= $medan('nomor_lisensi', 'Nomor lisensi', $teks('nomor_lisensi', 'text', 'maxlength="50"')) ?>
                            <?= $medan('lisensi_berlaku_sampai', 'Berlaku sampai', $teks('lisensi_berlaku_sampai', 'date')) ?>
                        </div>
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
                            <p>Akun tidak bisa login dan staf hilang dari daftar. Riwayat kontrak dan gaji tetap tersimpan.</p></div>
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
