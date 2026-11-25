<?php
declare(strict_types=1);

// Detail pemain / staf (admin/HR): profil, kontrak & gaji, dokumen, performa & medis (pemain).
// detail_karyawan.php?id=ID[&tab=profil|kontrak|dokumen|performa]

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';
require_once __DIR__ . '/lib_gaji.php';

start_secure_session();
wajib_admin();

const LABEL_JENIS_KARIER = ['akademi' => 'Akademi', 'permanen' => 'Permanen', 'pinjaman' => 'Pinjaman', 'bebas_transfer' => 'Bebas transfer', 'staf' => 'Staf'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
try {
    $st = db()->prepare('SELECT id, username, nama_lengkap, role, is_active, last_login, created_at FROM users WHERE id = ?');
    $st->execute([$id]);
    $orang = $st->fetch();
} catch (PDOException $ex) {
    error_log('Detail karyawan gagal: ' . $ex->getMessage());
    $orang = false;
}
if (!$orang) {
    http_response_code(404);
    halaman_awal('Tidak ditemukan', 'Data Pemain');
    echo '<div class="kosong">Karyawan tidak ditemukan. <a href="data_pemain.php">Kembali ke Data Pemain</a></div>';
    halaman_akhir();
    return;
}
$pemain = $orang['role'] === 'pemain';
$daftarUrl = $pemain ? 'data_pemain.php' : 'data_staf.php';
$menuAktif = $pemain ? 'Data Pemain' : 'Data Staf';
$tabSah = $pemain ? ['profil', 'kontrak', 'dokumen', 'performa'] : ['profil', 'kontrak', 'dokumen'];
$tab = in_array($_GET['tab'] ?? '', $tabSah, true) ? (string) $_GET['tab'] : 'profil';
$alamat = 'detail_karyawan.php?id=' . $id . ($tab !== 'profil' ? '&tab=' . $tab : '');

function tgl_benar(string $t): bool
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
        kembali_ke($alamat);
    }
    $aksi = (string) ($_POST['aksi'] ?? '');
    try {
        if ($aksi === 'foto') {
            $g = ganti_foto_karyawan($id, (string) $orang['role'], $_FILES['foto'] ?? null);
            flash_set($g === '' ? 'sukses' : 'galat', $g === '' ? 'Foto profil diperbarui.' : $g);
        } elseif ($aksi === 'hapus_foto') {
            $lama = foto_pengguna($id, (string) $orang['role']);
            db()->prepare('UPDATE ' . tabel_profil((string) $orang['role']) . ' SET foto = NULL WHERE user_id = ?')->execute([$id]);
            if ($lama !== null) {
                @unlink(__DIR__ . '/' . $lama);
            }
            catat_aktivitas('hapus', tabel_profil((string) $orang['role']), $id, 'Admin menghapus foto profil');
            flash_set('sukses', 'Foto profil dihapus.');
        } elseif ($aksi === 'karier_tambah') {
            $v = array_map(static fn($k) => trim((string) ($_POST[$k] ?? '')), array_combine($k = ['klub', 'jenis', 'mulai', 'selesai', 'nilai', 'catatan'], $k));
            $g = match (true) {
                mb_strlen($v['klub']) < 2 || mb_strlen($v['klub']) > 100 => 'Nama klub/instansi 2–100 karakter.',
                !isset(LABEL_JENIS_KARIER[$v['jenis']]) => 'Pilih jenis.',
                !tgl_benar($v['mulai']) => 'Tanggal mulai tidak valid.',
                $v['selesai'] !== '' && (!tgl_benar($v['selesai']) || $v['selesai'] < $v['mulai']) => 'Tanggal selesai tidak valid atau sebelum tanggal mulai.',
                $v['nilai'] !== '' && (!is_numeric($v['nilai']) || (float) $v['nilai'] < 0) => 'Nilai transfer tidak valid.',
                mb_strlen($v['catatan']) > 255 => 'Catatan maksimal 255 karakter.',
                default => '',
            };
            if ($g === '') {
                db()->prepare('INSERT INTO riwayat_karier (user_id, klub, jenis, tanggal_mulai, tanggal_selesai, nilai_transfer, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$id, $v['klub'], $v['jenis'], $v['mulai'], $v['selesai'] ?: null, $v['nilai'] === '' ? null : (float) $v['nilai'], $v['catatan'] ?: null]);
                catat_aktivitas('tambah', 'riwayat_karier', (int) db()->lastInsertId(), $v['klub']);
            }
            flash_set($g === '' ? 'sukses' : 'galat', $g === '' ? 'Riwayat karier ditambahkan.' : $g);
        } elseif ($aksi === 'karier_hapus') {
            $st = db()->prepare('DELETE FROM riwayat_karier WHERE id = ? AND user_id = ?');
            $st->execute([(int) ($_POST['karier_id'] ?? 0), $id]);
            flash_set($st->rowCount() ? 'sukses' : 'galat', $st->rowCount() ? 'Riwayat karier dihapus.' : 'Data tidak ditemukan.');
        } elseif ($aksi === 'dokumen_unggah') {
            $g = unggah_dokumen($id, (string) ($_POST['jenis'] ?? ''), trim((string) ($_POST['judul'] ?? '')), trim((string) ($_POST['berlaku_sampai'] ?? '')), $_FILES['berkas'] ?? null);
            flash_set($g === '' ? 'sukses' : 'galat', $g === '' ? 'Dokumen diunggah.' : $g);
        } elseif ($aksi === 'dokumen_hapus') {
            $st = db()->prepare('SELECT id, jalur, judul FROM dokumen_karyawan WHERE id = ? AND user_id = ?');
            $st->execute([(int) ($_POST['dokumen_id'] ?? 0), $id]);
            if ($d = $st->fetch()) {
                db()->prepare('DELETE FROM dokumen_karyawan WHERE id = ?')->execute([$d['id']]);
                if (preg_match('#^uploads/dokumen/[A-Za-z0-9_.-]+$#', (string) $d['jalur'])) {
                    @unlink(__DIR__ . '/' . $d['jalur']);
                }
                catat_aktivitas('hapus', 'dokumen_karyawan', (int) $d['id'], (string) $d['judul']);
                flash_set('sukses', 'Dokumen dihapus.');
            } else {
                flash_set('galat', 'Dokumen tidak ditemukan.');
            }
        }
    } catch (PDOException $ex) {
        error_log('Aksi detail karyawan gagal: ' . $ex->getMessage());
        flash_set('galat', 'Perubahan belum dapat disimpan. Coba lagi nanti.');
    }
    kembali_ke($alamat);
}

// =====================================================================
// Data
// =====================================================================
$dbGagal = false;
try {
    if ($pemain) {
        $st = db()->prepare('SELECT pm.nomor_punggung, pr.* FROM users u LEFT JOIN pemain pm ON pm.user_id = u.id
                               LEFT JOIN profil_pemain pr ON pr.user_id = u.id WHERE u.id = ?');
    } else {
        $st = db()->prepare('SELECT s.*, a.nama_lengkap AS atasan,
                                    (SELECT COUNT(*) FROM staf b JOIN users ub ON ub.id = b.user_id WHERE b.atasan_id = u.id AND ub.is_active = 1) AS bawahan
                               FROM users u LEFT JOIN staf s ON s.user_id = u.id LEFT JOIN users a ON a.id = s.atasan_id WHERE u.id = ?');
    }
    $st->execute([$id]);
    $p = $st->fetch() ?: [];

    $st = db()->prepare('SELECT k.*, b.nama_lengkap AS pembuat FROM kontrak k LEFT JOIN users b ON b.id = k.dibuat_oleh
                          WHERE k.user_id = ? ORDER BY k.tanggal_mulai DESC, k.versi DESC');
    $st->execute([$id]);
    $kontrak = $st->fetchAll();
    $aktif = null;
    foreach ($kontrak as $k) {
        if ($k['status'] === 'aktif') {
            $aktif = $k;
            break;
        }
    }
    $st = db()->prepare("SELECT * FROM penggajian WHERE user_id = ? ORDER BY periode DESC LIMIT 6");
    $st->execute([$id]);
    $slip = $st->fetchAll();
    $st = db()->prepare('SELECT * FROM riwayat_karier WHERE user_id = ? ORDER BY tanggal_selesai IS NOT NULL, tanggal_mulai DESC');
    $st->execute([$id]);
    $karier = $st->fetchAll();
    $st = db()->prepare('SELECT d.*, u.nama_lengkap AS pengunggah FROM dokumen_karyawan d LEFT JOIN users u ON u.id = d.diunggah_oleh
                          WHERE d.user_id = ? ORDER BY d.created_at DESC');
    $st->execute([$id]);
    $dokumen = $st->fetchAll();

    if ($pemain) {
        $musim = (string) db()->query('SELECT MAX(musim) FROM pertandingan')->fetchColumn();
        $st = db()->prepare('SELECT COALESCE(SUM(main),0) main, COALESCE(SUM(goal),0) goal, COALESCE(SUM(assist),0) assist,
                                    COALESCE(SUM(menit),0) menit, ROUND(SUM(rata_rating * main) / NULLIF(SUM(main),0), 2) rating
                               FROM v_statistik_kompetisi WHERE user_id = ? AND musim = ?');
        $st->execute([$id, $musim]);
        $stat = $st->fetch();
        $st = db()->prepare('SELECT j.waktu_mulai, p.lawan, p.kandang, p.skor_klub, p.skor_lawan, p.kompetisi, s.menit_bermain, s.goal, s.assist, s.rating
                               FROM statistik_pertandingan s JOIN pertandingan p ON p.jadwal_id = s.jadwal_id JOIN jadwal j ON j.id = s.jadwal_id
                              WHERE s.user_id = ? ORDER BY j.waktu_mulai DESC LIMIT 5');
        $st->execute([$id]);
        $lagaTerakhir = $st->fetchAll();
        $st = db()->prepare("SELECT * FROM cedera WHERE user_id = ? ORDER BY tanggal_cedera DESC");
        $st->execute([$id]);
        $cedera = $st->fetchAll();
        $st = db()->prepare('SELECT * FROM pemeriksaan_kebugaran WHERE user_id = ? ORDER BY tanggal DESC LIMIT 1');
        $st->execute([$id]);
        $kebugaran = $st->fetch() ?: null;
        $st = db()->prepare("SELECT pl.judul, ps.progres_persen FROM program_latihan_peserta ps JOIN program_latihan pl ON pl.id = ps.program_id
                              WHERE ps.user_id = ? AND pl.status = 'aktif' AND ps.status = 'berjalan'");
        $st->execute([$id]);
        $program = $st->fetchAll();
    }
} catch (PDOException $ex) {
    error_log('Detail karyawan gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

$nama = (string) $orang['nama_lengkap'];
$foto = jalur_foto($p['foto'] ?? null);
$idTampil = $pemain ? id_pemain($id) : (string) (($p['nip'] ?? '') ?: 'STF-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT));
$peran = $pemain
    ? (isset(KODE_POSISI[$p['posisi_kode'] ?? '']) ? KODE_POSISI[$p['posisi_kode']][0] : 'Pemain')
    : (($p['jabatan_detail'] ?? '') ?: (LABEL_JABATAN_ENUM[$p['jabatan'] ?? 'lainnya'] ?? 'Staf'));
$usiaOrang = usia($p['tanggal_lahir'] ?? null);
$sk = status_kontrak($aktif);
$sisa = $aktif ? selisih_hari(hari_ini()->format('Y-m-d'), (string) $aktif['tanggal_selesai']) : null;

halaman_awal($nama, $menuAktif);
if ($dbGagal): ?>
            <p class="galat" role="alert">Data belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

// Satu sel informasi.
$info = static fn(string $label, ?string $nilai): string => '<div><dt>' . e($label) . '</dt><dd>' . ($nilai !== null && $nilai !== '' ? e($nilai) : '<span class="redup">-</span>') . '</dd></div>';
?>
            <nav class="remah" aria-label="Lokasi halaman">
                <a href="dashboard.php">Beranda</a><span aria-hidden="true">›</span>
                <a href="<?= e($daftarUrl) ?>"><?= e($menuAktif) ?></a><span aria-hidden="true">›</span>
                <span aria-current="page">Detail</span>
            </nav>
            <a class="tautan-kembali" href="<?= e($daftarUrl) ?>"><?= ikon('kiri') ?>Kembali</a>

            <section class="kartu hero-karyawan" aria-label="Identitas <?= e($nama) ?>">
                <div class="banner-karyawan" aria-hidden="true"></div>
                <div class="hero-isi">
                    <span class="avatar-hero"><?= avatar_isi($nama, $foto) ?></span>
                    <div class="hero-teks">
                        <h1><?= e($nama) ?>
                            <?php if ($pemain && ($p['nomor_punggung'] ?? null) !== null): ?><span class="nomor-hero">#<?= (int) $p['nomor_punggung'] ?></span><?php endif; ?>
                            <?= $pemain ? chip_peta(LABEL_STATUS_PEMAIN, (string) ($p['status'] ?? 'aktif')) : chip_peta(LABEL_STATUS_KERJA, (string) ($p['status_kerja'] ?? 'kontrak')) ?>
                            <?php if (!(int) $orang['is_active']): ?><span class="chip merah bertitik">Nonaktif</span><?php endif; ?>
                        </h1>
                        <p><?= e(implode(' · ', array_filter([
                            $peran,
                            $pemain ? ($p['posisi_kode'] ?? null) : (LABEL_DIVISI[$p['divisi'] ?? ''] ?? null),
                            $usiaOrang !== null ? $usiaOrang . ' tahun' : null,
                            $idTampil,
                        ]))) ?></p>
                    </div>
                    <div class="hero-aksi">
                        <form method="post" action="<?= e($alamat) ?>" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="foto">
                            <input type="file" id="fotoKaryawan" name="foto" accept="image/jpeg,image/png,image/webp" data-foto-otomatis class="sr">
                            <label for="fotoKaryawan" class="tombol kecil"><?= ikon('kamera') ?>Ganti foto</label>
                        </form>
                        <?php if ($foto): ?>
                            <form method="post" action="<?= e($alamat) ?>" data-konfirmasi="Hapus foto profil <?= e($nama) ?>?">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="hapus_foto">
                                <button type="submit" class="tombol-ikon-kecil" title="Hapus foto" aria-label="Hapus foto"><?= ikon('silang') ?></button>
                            </form>
                        <?php endif; ?>
                        <a class="tombol kecil utama" href="<?= e($daftarUrl) ?>?ubah=<?= $id ?>&amp;kembali=<?= e(rawurlencode($alamat)) ?>"><?= ikon('pena') ?>Ubah</a>
                    </div>
                </div>
            </section>

            <section class="kpi-kisi ringkas-karyawan" aria-label="Ringkasan cepat">
                <?= kartu_kpi('Kontrak', STATUS_KONTRAK_EFEKTIF[$sk][0], 'pena', match ($sk) { 'aktif' => 'hijau', 'hampir' => 'kuning', 'kedaluwarsa' => 'merah', default => 'biru' },
                    $aktif ? e($aktif['nomor_kontrak']) . ' · ' . ($sisa >= 0 ? angka($sisa) . ' hari lagi' : 'lewat ' . angka(-$sisa) . ' hari') : 'Belum ada kontrak aktif') ?>
                <?= kartu_kpi('Gaji pokok / bulan', $aktif ? rupiah_ringkas($aktif['gaji_pokok']) : '-', 'uang', 'biru', $aktif ? '+ tunjangan ' . e(rupiah_ringkas($aktif['tunjangan_bulanan'])) : '') ?>
                <?php if ($pemain): ?>
                    <?= kartu_kpi('Musim ' . $musim, angka($stat['main']) . ' laga', 'trofi', 'ungu', e($stat['goal'] . ' gol · ' . $stat['assist'] . ' assist')) ?>
                    <?= kartu_kpi('Kebugaran', $kebugaran ? (string) $kebugaran['skor_kebugaran'] : '-', 'aktivitas', 'kuning',
                        $kebugaran ? 'Tes ' . e(tanggal_id((string) $kebugaran['tanggal'])) : 'Belum dites') ?>
                <?php else: ?>
                    <?php $masa = ($p['tanggal_bergabung'] ?? null) ? (new DateTimeImmutable((string) $p['tanggal_bergabung']))->diff(hari_ini()) : null; ?>
                    <?= kartu_kpi('Masa kerja', $masa ? $masa->y . ' th ' . $masa->m . ' bln' : '-', 'jam', 'ungu', ($p['tanggal_bergabung'] ?? null) ? 'Sejak ' . e(tanggal_id((string) $p['tanggal_bergabung'])) : '') ?>
                    <?= kartu_kpi('Bawahan langsung', angka($p['bawahan'] ?? 0), 'rapat', 'kuning', ($p['atasan'] ?? null) ? 'Melapor ke ' . e((string) $p['atasan']) : 'Pimpinan tertinggi') ?>
                <?php endif; ?>
            </section>

            <?php
            $label = ['profil' => [ikon('orang') . 'Profil', null], 'kontrak' => [ikon('pena') . 'Kontrak & gaji', count($kontrak)],
                      'dokumen' => [ikon('dokumen') . 'Dokumen', count($dokumen)], 'performa' => [ikon('trofi') . 'Performa & Medis', null]];
            ?>
            <nav class="tab-status tab-detail" aria-label="Bagian detail">
                <?php foreach ($tabSah as $t): ?>
                    <a href="detail_karyawan.php?id=<?= $id ?><?= $t !== 'profil' ? '&amp;tab=' . $t : '' ?>"<?= $t === $tab ? ' class="aktif" aria-current="page"' : '' ?>>
                        <?= $label[$t][0] ?><?php if ($label[$t][1] !== null): ?> <span class="hitung"><?= (int) $label[$t][1] ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

<?php if ($tab === 'profil'): ?>
            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulInfo">
                    <div class="kepala-kartu"><h2 id="judulInfo">Informasi <?= $pemain ? 'pemain' : 'staf' ?></h2></div>
                    <dl class="kisi-info">
                        <?= $info('Usia', $usiaOrang !== null ? $usiaOrang . ' tahun' : null) ?>
                        <?= $info('Tanggal lahir', ($p['tanggal_lahir'] ?? null) ? tanggal_id((string) $p['tanggal_lahir'], true) . (($p['tempat_lahir'] ?? null) ? ', ' . $p['tempat_lahir'] : '') : null) ?>
                        <?= $info('Kewarganegaraan', $p['kewarganegaraan'] ?? null) ?>
                        <?= $info('Bergabung', ($p['tanggal_bergabung'] ?? null) ? tanggal_id((string) $p['tanggal_bergabung'], true) : null) ?>
                        <?php if ($pemain): ?>
                            <?= $info('Posisi', isset(KODE_POSISI[$p['posisi_kode'] ?? '']) ? KODE_POSISI[$p['posisi_kode']][0] . ' (' . $p['posisi_kode'] . ')' : null) ?>
                            <?= $info('Kaki dominan', ($p['kaki_dominan'] ?? null) ? ucfirst((string) $p['kaki_dominan']) : null) ?>
                            <?= $info('Tinggi / berat', (($p['tinggi_cm'] ?? null) ? $p['tinggi_cm'] . ' cm' : '-') . ' / ' . (($p['berat_kg'] ?? null) ? desimal($p['berat_kg'], 0) . ' kg' : '-')) ?>
                            <?= $info('Golongan darah', $p['golongan_darah'] ?? null) ?>
                            <?= $info('Nomor punggung', ($p['nomor_punggung'] ?? null) !== null ? '#' . $p['nomor_punggung'] : null) ?>
                        <?php else: ?>
                            <?= $info('Jabatan', $peran) ?>
                            <?= $info('Divisi', LABEL_DIVISI[$p['divisi'] ?? ''] ?? null) ?>
                            <?= $info('Atasan langsung', $p['atasan'] ?? null) ?>
                            <?= $info('Status kerja', LABEL_STATUS_KERJA[$p['status_kerja'] ?? ''][0] ?? null) ?>
                            <?= $info('Lisensi', trim(($p['lisensi'] ?? '') . (($p['nomor_lisensi'] ?? null) ? ' · ' . $p['nomor_lisensi'] : '')) ?: null) ?>
                            <?= $info('Lisensi berlaku', ($p['lisensi_berlaku_sampai'] ?? null) ? tanggal_id((string) $p['lisensi_berlaku_sampai'], true) : null) ?>
                        <?php endif; ?>
                    </dl>
                    <h3 class="pemisah-bagian">Kontak</h3>
                    <dl class="kisi-info">
                        <?= $info('Telepon', $p['telepon'] ?? null) ?>
                        <?= $info('Email', $p['email'] ?? null) ?>
                        <?= $info('Alamat', $p['alamat'] ?? null) ?>
                        <?= $info('Kontak darurat', ($p['kontak_darurat_nama'] ?? null) ? $p['kontak_darurat_nama'] . (($p['kontak_darurat_hubungan'] ?? null) ? ' (' . $p['kontak_darurat_hubungan'] . ')' : '') : null) ?>
                        <?= $info('Telp. darurat', $p['kontak_darurat_telepon'] ?? null) ?>
                        <div><dt>Akun login</dt><dd><?= e((string) $orang['username']) ?> <span class="badge netral"><?= e(ROLE_LABELS[$orang['role']] ?? $orang['role']) ?></span>
                            <small class="sub-sel"><?= $orang['last_login'] ? 'Login terakhir ' . e(waktu_relatif((string) $orang['last_login'])) : 'Belum pernah login' ?></small></dd></div>
                    </dl>
                </section>

                <section class="kartu" aria-labelledby="judulKarier">
                    <div class="kepala-kartu baris-kepala">
                        <h2 id="judulKarier"><?= ikon('riwayat') ?> Riwayat karier &amp; transfer</h2>
                        <button type="button" class="tombol kecil" data-buka-dialog="dialogKarier"><?= ikon('tambah') ?>Tambah</button>
                    </div>
                    <?php if (!$karier): ?>
                        <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('kotak-masuk') ?></span><b>Belum ada riwayat karier</b><span>Catat klub, akademi, pinjaman, atau transfer.</span></div>
                    <?php else: ?>
                        <ol class="garis-waktu">
                            <?php foreach ($karier as $r): ?>
                                <li<?= $r['tanggal_selesai'] === null ? ' class="sekarang"' : '' ?>>
                                    <div class="kartu-rawat karier-item">
                                        <small><?= e(tanggal_id((string) $r['tanggal_mulai'])) ?> – <?= $r['tanggal_selesai'] ? e(tanggal_id((string) $r['tanggal_selesai'])) : 'sekarang' ?></small>
                                        <b><?= e((string) $r['klub']) ?> <span class="badge netral"><?= e(LABEL_JENIS_KARIER[$r['jenis']] ?? $r['jenis']) ?></span></b>
                                        <?php if ($r['nilai_transfer'] !== null): ?><span>Nilai transfer <?= e(rupiah_ringkas($r['nilai_transfer'])) ?></span><?php endif; ?>
                                        <?php if ($r['catatan']): ?><span class="redup"><?= e((string) $r['catatan']) ?></span><?php endif; ?>
                                        <form method="post" action="<?= e($alamat) ?>" data-konfirmasi="Hapus riwayat <?= e((string) $r['klub']) ?>?" class="hapus-karier">
                                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="karier_hapus"><input type="hidden" name="karier_id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" class="tombol-ikon-kecil" title="Hapus" aria-label="Hapus riwayat <?= e((string) $r['klub']) ?>"><?= ikon('silang') ?></button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </section>
            </div>

            <dialog class="modal" id="dialogKarier" aria-labelledby="judulDialogKarier">
                <form method="post" action="<?= e($alamat) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="karier_tambah">
                    <div class="modal-kepala"><span class="ikon-judul"><?= ikon('riwayat') ?></span>
                        <div><h2 id="judulDialogKarier">Tambah riwayat karier</h2><p><?= e($nama) ?></p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button></div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <div class="medan"><label for="kKlub">Klub / instansi</label><div class="kolom"><input id="kKlub" name="klub" maxlength="100" required></div></div>
                            <div class="medan"><label for="kJenis">Jenis</label><select id="kJenis" name="jenis">
                                <?php foreach (LABEL_JENIS_KARIER as $k => $l): ?><option value="<?= $k ?>"<?= (!$pemain && $k === 'staf') || ($pemain && $k === 'permanen') ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                            </select></div>
                        </div>
                        <div class="baris-3">
                            <div class="medan"><label for="kMulai">Mulai</label><div class="kolom"><input type="date" id="kMulai" name="mulai" required></div></div>
                            <div class="medan"><label for="kSelesai">Selesai <small>(kosong = sekarang)</small></label><div class="kolom"><input type="date" id="kSelesai" name="selesai"></div></div>
                            <div class="medan"><label for="kNilai">Nilai transfer <small>(opsional)</small></label><div class="kolom"><input type="number" id="kNilai" name="nilai" min="0" step="1000000"></div></div>
                        </div>
                        <div class="medan"><label for="kCatatan">Catatan <small>(opsional)</small></label><div class="kolom"><input id="kCatatan" name="catatan" maxlength="255"></div></div>
                    </div>
                    <div class="modal-kaki"><button type="button" class="tombol" data-tutup-dialog>Batal</button><button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan</button></div>
                </form>
            </dialog>

<?php elseif ($tab === 'kontrak'): ?>
            <div class="aksi-kanan baris-aksi-tab">
                <?php if ($aktif): ?>
                    <a class="tombol" href="kelola_kontrak.php?perpanjang=<?= (int) $aktif['id'] ?>"><?= ikon('riwayat') ?>Perpanjang kontrak</a>
                <?php else: ?>
                    <a class="tombol utama" href="kelola_kontrak.php?baru=<?= $id ?>"><?= ikon('tambah') ?>Buat kontrak</a>
                <?php endif; ?>
            </div>
            <section class="kartu tabel-kartu" aria-label="Semua kontrak">
                <div class="kepala-tabel"><h2>Semua versi kontrak</h2></div>
                <?php if (!$kontrak): ?>
                    <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('pena') ?></span><b>Belum ada kontrak</b><span>Klik “Buat kontrak” untuk menerbitkan kontrak pertama.</span></div>
                <?php else: ?>
                    <div class="gulir-tabel"><table class="tabel-data">
                        <thead><tr><th scope="col">Kode</th><th scope="col">Masa berlaku</th><th scope="col">Gaji / bln</th><th scope="col">Nilai kontrak</th><th scope="col">Release clause</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($kontrak as $k): ?>
                                <tr>
                                    <td><b><?= e((string) $k['nomor_kontrak']) ?></b><small class="sub-sel">v<?= (int) $k['versi'] ?> · <?= e(LABEL_JENIS_KONTRAK_ADMIN[$k['jenis']] ?? '') ?></small></td>
                                    <td class="sel-tgl"><?= e(tanggal_id((string) $k['tanggal_mulai'])) ?> – <?= e(tanggal_id((string) $k['tanggal_selesai'])) ?><small class="sub-sel"><?= bulan_kontrak((string) $k['tanggal_mulai'], (string) $k['tanggal_selesai']) ?> bulan · <?= e((string) ($k['pembuat'] ?? 'HR')) ?></small></td>
                                    <td class="uang"><?= e(rupiah_ringkas($k['gaji_pokok'])) ?></td>
                                    <td class="uang"><b><?= e(rupiah_ringkas(nilai_kontrak_admin($k))) ?></b></td>
                                    <td class="uang"><?= $k['klausul_rilis'] !== null ? e(rupiah_ringkas($k['klausul_rilis'])) : '<span class="redup">-</span>' ?></td>
                                    <td><?= chip_peta(STATUS_KONTRAK_EFEKTIF, status_kontrak($k)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
            <section class="kartu tabel-kartu" aria-label="Riwayat gaji">
                <div class="kepala-tabel"><h2>Riwayat gaji (6 bulan terakhir)</h2><a class="tombol kecil" href="kelola_gaji.php">Data Penggajian</a></div>
                <?php if (!$slip): ?>
                    <p class="tabel-kosong">Belum ada slip gaji.</p>
                <?php else: ?>
                    <div class="gulir-tabel"><table class="tabel-data">
                        <thead><tr><th scope="col">Periode</th><th scope="col">Gaji pokok</th><th scope="col">Tunjangan + bonus</th><th scope="col">Potongan</th><th scope="col">Bersih</th><th scope="col">Status</th><th scope="col"><span class="sr">PDF</span></th></tr></thead>
                        <tbody>
                            <?php foreach ($slip as $s): ?>
                                <tr>
                                    <td><b><?= e(bulan_tahun((string) $s['periode'])) ?></b></td>
                                    <td class="uang"><?= e(rupiah($s['gaji_pokok'])) ?></td>
                                    <td class="uang"><?= e(rupiah((float) $s['tunjangan'] + (float) $s['bonus'])) ?></td>
                                    <td class="uang teks-merah">- <?= e(rupiah($s['potongan'])) ?></td>
                                    <td class="uang"><b><?= e(rupiah($s['total_bersih'])) ?></b></td>
                                    <td><?= chip_peta(LABEL_STATUS_SLIP, (string) $s['status']) ?></td>
                                    <td><?php if ($s['status'] !== 'draft'): ?><a class="tombol-ikon-kecil" href="gaji_pdf.php?id=<?= (int) $s['id'] ?>&amp;lihat=1" target="_blank" rel="noopener" title="Lihat PDF" aria-label="Lihat slip <?= e(bulan_tahun((string) $s['periode'])) ?>"><?= ikon('mata') ?></a><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>

<?php elseif ($tab === 'dokumen'):
    // Dokumen wajib menurut peran; ditandai bila sudah ada.
    $wajib = $pemain ? ['identitas', 'kontrak', 'medis'] : ['identitas', 'kontrak'];
    if (!$pemain && !empty($p['lisensi'])) {
        $wajib[] = 'lisensi';
    }
    $adaJenis = array_unique(array_column($dokumen, 'jenis'));
    ?>
            <div class="kisi-2-1">
                <section class="kartu tabel-kartu" aria-label="Daftar dokumen">
                    <div class="kepala-tabel"><h2>Dokumen</h2><span class="redup"><?= count($dokumen) ?> berkas · PDF/JPG/PNG maks. 5 MB</span></div>
                    <?php if (!$dokumen): ?>
                        <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('dokumen') ?></span><b>Belum ada dokumen</b><span>Unggah KTP/paspor, kontrak bertanda tangan, atau dokumen medis.</span></div>
                    <?php else: ?>
                        <div class="gulir-tabel"><table class="tabel-data">
                            <thead><tr><th scope="col">Dokumen</th><th scope="col">Jenis</th><th scope="col">Berlaku sampai</th><th scope="col">Diunggah</th><th scope="col"><span class="sr">Aksi</span></th></tr></thead>
                            <tbody>
                                <?php foreach ($dokumen as $d): ?>
                                    <?php $sisaDok = $d['berlaku_sampai'] ? selisih_hari(hari_ini()->format('Y-m-d'), (string) $d['berlaku_sampai']) : null; ?>
                                    <tr>
                                        <td><b><?= e((string) $d['judul']) ?></b><small class="sub-sel"><?= e((string) $d['nama_berkas']) ?> · <?= e(ukuran_berkas((int) $d['ukuran'])) ?></small></td>
                                        <td><?= e(LABEL_JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?></td>
                                        <td class="sel-tgl"><?php if ($sisaDok === null): ?><span class="redup">-</span>
                                            <?php else: ?><?= e(tanggal_id((string) $d['berlaku_sampai'])) ?>
                                                <small class="sub-sel <?= $sisaDok < 0 ? 'teks-merah' : ($sisaDok <= 60 ? 'teks-kuning' : '') ?>"><?= $sisaDok < 0 ? 'Kedaluwarsa' : angka($sisaDok) . ' hari lagi' ?></small><?php endif; ?></td>
                                        <td><small><?= e(tanggal_id(substr((string) $d['created_at'], 0, 10))) ?><span class="sub-sel"><?= e((string) ($d['pengunggah'] ?? '-')) ?></span></small></td>
                                        <td class="aksi-ikon">
                                            <a class="tombol-ikon-kecil" href="dokumen_unduh.php?id=<?= (int) $d['id'] ?>&amp;lihat=1" target="_blank" rel="noopener" title="Lihat" aria-label="Lihat <?= e((string) $d['judul']) ?>"><?= ikon('mata') ?></a>
                                            <a class="tombol-ikon-kecil" href="dokumen_unduh.php?id=<?= (int) $d['id'] ?>" title="Unduh" aria-label="Unduh <?= e((string) $d['judul']) ?>"><?= ikon('unduh') ?></a>
                                            <form method="post" action="<?= e($alamat) ?>" data-konfirmasi="Hapus dokumen <?= e((string) $d['judul']) ?>?">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="dokumen_hapus"><input type="hidden" name="dokumen_id" value="<?= (int) $d['id'] ?>">
                                                <button type="submit" class="tombol-ikon-kecil" title="Hapus" aria-label="Hapus <?= e((string) $d['judul']) ?>"><?= ikon('silang') ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php endif; ?>
                </section>

                <div class="tumpuk-kartu">
                    <section class="kartu" aria-labelledby="judulUnggah">
                        <div class="kepala-kartu"><h2 id="judulUnggah">Unggah dokumen</h2><p>Berkas disimpan privat; hanya HR dan pemiliknya yang bisa membuka.</p></div>
                        <form method="post" action="<?= e($alamat) ?>" enctype="multipart/form-data" class="form-unggah">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="aksi" value="dokumen_unggah">
                            <div class="medan"><label for="dJenis">Jenis</label><select id="dJenis" name="jenis">
                                <?php foreach (LABEL_JENIS_DOKUMEN as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
                            <div class="medan"><label for="dJudul">Judul</label><div class="kolom"><input id="dJudul" name="judul" maxlength="150" required placeholder="mis. Paspor, STR Dokter, Kontrak v2"></div></div>
                            <div class="medan"><label for="dBerlaku">Berlaku sampai <small>(opsional)</small></label><div class="kolom"><input type="date" id="dBerlaku" name="berlaku_sampai"></div></div>
                            <div class="medan"><label for="dBerkas">Berkas</label><input type="file" id="dBerkas" name="berkas" accept="application/pdf,image/jpeg,image/png" required class="input-berkas"></div>
                            <button type="submit" class="tombol utama penuh"><?= ikon('unduh', 'ik putar') ?>Unggah</button>
                        </form>
                    </section>
                    <section class="kartu" aria-labelledby="judulLengkap">
                        <div class="kepala-kartu"><h2 id="judulLengkap">Kelengkapan dokumen</h2><p><?= count(array_intersect($wajib, $adaJenis)) ?> dari <?= count($wajib) ?> dokumen wajib</p></div>
                        <ul class="syarat">
                            <?php foreach ($wajib as $w): ?><li<?= in_array($w, $adaJenis, true) ? ' class="ok"' : '' ?>><?= e(LABEL_JENIS_DOKUMEN[$w]) ?></li><?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </div>

<?php else: /* performa */ ?>
            <?php
            $cederaAktif = array_values(array_filter($cedera, static fn($c) => $c['status'] !== 'pulih'));
            ?>
            <section class="kpi-kisi" aria-label="Performa musim">
                <?= kartu_kpi('Gol + assist', angka((int) $stat['goal'] + (int) $stat['assist']), 'target', 'hijau', e($stat['goal'] . ' gol · ' . $stat['assist'] . ' assist · ' . angka($stat['menit']) . ' menit')) ?>
                <?= kartu_kpi('Rata-rata rating', $stat['rating'] !== null ? desimal($stat['rating']) : '-', 'bintang', 'biru', 'Musim ' . e($musim)) ?>
                <?= kartu_kpi('Status medis', $cederaAktif ? 'Cedera' : 'Fit', 'jantung', $cederaAktif ? 'merah' : 'hijau',
                    $cederaAktif ? e($cederaAktif[0]['jenis_cedera']) : e(count($cedera) . ' cedera tercatat, semua pulih')) ?>
                <?= kartu_kpi('Program latihan', angka(count($program)), 'beban', 'ungu', $program ? e($program[0]['judul'] . ' (' . $program[0]['progres_persen'] . '%)') : 'Tidak ada program berjalan') ?>
            </section>
            <div class="kisi-2-1">
                <section class="kartu tabel-kartu" aria-label="Laga terakhir">
                    <div class="kepala-tabel"><h2>5 laga terakhir</h2><a class="tombol kecil" href="statistik.php?pemain=<?= $id ?>">Statistik lengkap</a></div>
                    <?php if (!$lagaTerakhir): ?>
                        <p class="tabel-kosong">Belum ada catatan pertandingan.</p>
                    <?php else: ?>
                        <div class="gulir-tabel"><table class="tabel-data">
                            <thead><tr><th scope="col">Tanggal</th><th scope="col">Lawan</th><th scope="col">Hasil</th><th scope="col">Menit</th><th scope="col">G/A</th><th scope="col">Rating</th></tr></thead>
                            <tbody>
                                <?php foreach ($lagaTerakhir as $l): ?>
                                    <?php $sk1 = (int) $l['skor_klub']; $sl1 = (int) $l['skor_lawan']; [$h, $kh] = $sk1 > $sl1 ? ['M', 'hijau'] : ($sk1 === $sl1 ? ['S', 'netral'] : ['K', 'merah']); ?>
                                    <tr>
                                        <td class="sel-tgl"><?= e(tanggal_id((string) $l['waktu_mulai'])) ?></td>
                                        <td><b><?= e((string) $l['lawan']) ?></b><small class="sub-sel"><?= (int) $l['kandang'] ? 'Kandang' : 'Tandang' ?> · <?= e((string) $l['kompetisi']) ?></small></td>
                                        <td><span class="chip-hasil <?= $kh ?>"><b><?= $h ?></b> <?= $sk1 ?>–<?= $sl1 ?></span></td>
                                        <td><?= (int) $l['menit_bermain'] ?>'</td>
                                        <td><?= (int) $l['goal'] ?> / <?= (int) $l['assist'] ?></td>
                                        <td><?= chip_rating($l['rating']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php endif; ?>
                </section>
                <section class="kartu" aria-labelledby="judulMedis">
                    <div class="kepala-kartu"><h2 id="judulMedis">Medis &amp; kebugaran</h2></div>
                    <ul class="rincian-uang">
                        <li><span>Tes kebugaran terakhir</span><b><?= $kebugaran ? e(tanggal_id((string) $kebugaran['tanggal'])) : '-' ?></b></li>
                        <li><span>Skor · VO₂max</span><b><?= $kebugaran ? (int) $kebugaran['skor_kebugaran'] . ' · ' . e(desimal($kebugaran['vo2max'])) : '-' ?></b></li>
                        <li><span>Sprint 30 m</span><b><?= $kebugaran && $kebugaran['sprint_30m_detik'] !== null ? e(desimal($kebugaran['sprint_30m_detik'], 2)) . ' s' : '-' ?></b></li>
                        <li><span>Riwayat cedera</span><b><?= count($cedera) ?> kali</b></li>
                        <li><span>Hari absen karena cedera</span><b><?= angka(array_sum(array_map(static fn($c) => max(0, selisih_hari((string) $c['tanggal_cedera'], $c['tanggal_pulih'] ?? null)), $cedera))) ?> hari</b></li>
                    </ul>
                    <div class="aksi-kanan tautan-medis">
                        <a class="tombol kecil" href="cedera.php?pemain=<?= $id ?>"><?= ikon('jantung') ?>Riwayat cedera</a>
                        <a class="tombol kecil" href="kebugaran.php?pemain=<?= $id ?>"><?= ikon('aktivitas') ?>Kebugaran</a>
                    </div>
                </section>
            </div>
<?php endif; ?>
<?php
halaman_akhir();
