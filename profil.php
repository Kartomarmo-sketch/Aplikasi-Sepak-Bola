<?php
declare(strict_types=1);

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const FOTO_MAKS_BYTE = 2 * 1024 * 1024;   // 2 MB
const FOTO_FOLDER    = 'uploads/foto';
const FOTO_JENIS     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const POLA_TELEPON   = '/^\+?[0-9][0-9\s\-()]{5,23}$/';
const MAKS_PENGAJUAN_TERBUKA = 3;

$userId       = (int) $_SESSION['user_id'];
$role         = (string) $_SESSION['role'];
$adalahPemain = $role === 'pemain';
$tabel        = tabel_profil($role);   // nama tabel dari daftar tetap, aman disisipkan ke SQL

// Bidang "data terkunci" yang bisa diajukan perubahannya ke admin.
$bidangPengajuan = $adalahPemain
    ? ['Nama lengkap', 'Tanggal lahir', 'Tempat lahir', 'Kewarganegaraan', 'Tinggi / berat badan',
       'Kaki dominan', 'Posisi bermain', 'Nomor punggung', 'Golongan darah']
    : ['Nama lengkap', 'Tanggal lahir', 'Tempat lahir', 'NIP', 'Jabatan', 'Lisensi'];

$labelJabatan = [
    'manager' => 'Manager', 'admin' => 'Administrasi', 'pelatih_kepala' => 'Pelatih kepala',
    'asisten_pelatih' => 'Asisten pelatih', 'pelatih_kiper' => 'Pelatih kiper',
    'pelatih_fisik' => 'Pelatih fisik', 'dokter' => 'Dokter tim', 'fisioterapis' => 'Fisioterapis',
    'analis' => 'Analis', 'lainnya' => 'Staf',
];

/** Kembali ke halaman ini dengan pesan (pola Post-Redirect-Get). */
function kembali(string $tipe, string $pesan): never
{
    flash_set($tipe, $pesan);
    header('Location: profil.php');
    exit;
}

function simpan_foto_db(string $tabel, int $userId, ?string $jalur): void
{
    $stmt = db()->prepare(
        "INSERT INTO {$tabel} (user_id, foto) VALUES (:id, :foto)
         ON DUPLICATE KEY UPDATE foto = VALUES(foto)"
    );
    $stmt->execute([':id' => $userId, ':foto' => $jalur]);
}

function hapus_berkas_foto(?string $jalur): void
{
    $aman = jalur_foto($jalur);
    if ($aman !== null) {
        @unlink(__DIR__ . '/' . $aman);
    }
}

// ---------- Proses formulir ----------
$galat       = [];          // galat per kolom (form kontak)
$galatAjukan = [];          // galat dialog pengajuan
$masukan     = null;        // nilai yang dikirim ulang bila ada galat
$masukanAjukan = ['bidang' => '', 'nilai_baru' => '', 'alasan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        kembali('galat', 'Formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
    $aksi = (string) ($_POST['aksi'] ?? '');

    try {
        // ----- Simpan kontak & kontak darurat -----
        if ($aksi === 'simpan') {
            $masukan = [];
            foreach (['email', 'telepon', 'alamat', 'kd_nama', 'kd_hubungan', 'kd_telepon'] as $k) {
                $masukan[$k] = trim((string) ($_POST[$k] ?? ''));
            }

            if ($masukan['email'] !== '' &&
                (!filter_var($masukan['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($masukan['email']) > 120)) {
                $galat['email'] = 'Format email tidak valid.';
            }
            if ($masukan['telepon'] === '') {
                $galat['telepon'] = 'Nomor telepon wajib diisi.';
            } elseif (!preg_match(POLA_TELEPON, $masukan['telepon'])) {
                $galat['telepon'] = 'Gunakan angka saja (boleh diawali +, spasi, atau tanda hubung).';
            }
            if (mb_strlen($masukan['alamat']) > 255) {
                $galat['alamat'] = 'Alamat maksimal 255 karakter.';
            }
            if (mb_strlen($masukan['kd_nama']) > 100) {
                $galat['kd_nama'] = 'Nama maksimal 100 karakter.';
            }
            if (mb_strlen($masukan['kd_hubungan']) > 40) {
                $galat['kd_hubungan'] = 'Hubungan maksimal 40 karakter.';
            }
            if ($masukan['kd_telepon'] !== '' && !preg_match(POLA_TELEPON, $masukan['kd_telepon'])) {
                $galat['kd_telepon'] = 'Nomor telepon darurat tidak valid.';
            }
            // Kontak darurat: bila salah satu diisi, nama dan telepon wajib ada.
            if ($masukan['kd_nama'] . $masukan['kd_hubungan'] . $masukan['kd_telepon'] !== '') {
                if ($masukan['kd_nama'] === '' && !isset($galat['kd_nama'])) {
                    $galat['kd_nama'] = 'Nama kontak darurat wajib diisi.';
                }
                if ($masukan['kd_telepon'] === '' && !isset($galat['kd_telepon'])) {
                    $galat['kd_telepon'] = 'Telepon darurat wajib diisi.';
                }
            }

            if ($galat) {
                flash_set('galat', 'Periksa kembali isian yang ditandai.');
            } else {
                $nul = static fn(string $v): ?string => $v === '' ? null : $v;
                $stmt = db()->prepare(
                    "INSERT INTO {$tabel}
                        (user_id, email, telepon, alamat, kontak_darurat_nama, kontak_darurat_hubungan, kontak_darurat_telepon)
                     VALUES (:id, :email, :telepon, :alamat, :kdn, :kdh, :kdt)
                     ON DUPLICATE KEY UPDATE
                        email = VALUES(email), telepon = VALUES(telepon), alamat = VALUES(alamat),
                        kontak_darurat_nama = VALUES(kontak_darurat_nama),
                        kontak_darurat_hubungan = VALUES(kontak_darurat_hubungan),
                        kontak_darurat_telepon = VALUES(kontak_darurat_telepon)"
                );
                $stmt->execute([
                    ':id' => $userId, ':email' => $nul($masukan['email']), ':telepon' => $masukan['telepon'],
                    ':alamat' => $nul($masukan['alamat']), ':kdn' => $nul($masukan['kd_nama']),
                    ':kdh' => $nul($masukan['kd_hubungan']), ':kdt' => $nul($masukan['kd_telepon']),
                ]);
                catat_aktivitas('ubah', $tabel, $userId, 'Memperbarui kontak dan kontak darurat');
                kembali('sukses', 'Profil berhasil diperbarui.');
            }
        }

        // ----- Unggah foto -----
        elseif ($aksi === 'foto') {
            $f = $_FILES['foto'] ?? null;
            if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $kode = is_array($f) ? (int) ($f['error'] ?? 0) : UPLOAD_ERR_NO_FILE;
                kembali('galat', in_array($kode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                    ? 'Ukuran foto terlalu besar (maksimal 2 MB).'
                    : 'Foto gagal diunggah. Silakan coba lagi.');
            }
            $tmp = (string) $f['tmp_name'];
            if (!is_uploaded_file($tmp) || $f['size'] < 1 || $f['size'] > FOTO_MAKS_BYTE) {
                kembali('galat', 'Ukuran foto maksimal 2 MB.');
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
            $info = @getimagesize($tmp);
            if (!isset(FOTO_JENIS[$mime]) || $info === false || $info['mime'] !== $mime) {
                kembali('galat', 'Berkas bukan foto yang valid. Gunakan JPG, PNG, atau WebP.');
            }
            if ($info[0] < 100 || $info[1] < 100 || $info[0] > 6000 || $info[1] > 6000) {
                kembali('galat', 'Dimensi foto harus antara 100 dan 6000 piksel.');
            }

            $folder = __DIR__ . '/' . FOTO_FOLDER;
            if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
                kembali('galat', 'Folder foto tidak dapat dibuat di server.');
            }
            $nama  = 'u' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . FOTO_JENIS[$mime];
            $jalur = FOTO_FOLDER . '/' . $nama;
            if (!move_uploaded_file($tmp, __DIR__ . '/' . $jalur)) {
                kembali('galat', 'Foto tidak dapat disimpan di server.');
            }

            $lama = foto_pengguna($userId, $role);
            try {
                simpan_foto_db($tabel, $userId, $jalur);
            } catch (PDOException $ex) {
                @unlink(__DIR__ . '/' . $jalur);   // jangan tinggalkan berkas yatim
                throw $ex;
            }
            hapus_berkas_foto($lama);
            catat_aktivitas('ubah', $tabel, $userId, 'Mengganti foto profil');
            kembali('sukses', 'Foto profil berhasil diperbarui.');
        }

        // ----- Hapus foto -----
        elseif ($aksi === 'hapus_foto') {
            $lama = foto_pengguna($userId, $role);
            simpan_foto_db($tabel, $userId, null);
            hapus_berkas_foto($lama);
            catat_aktivitas('hapus', $tabel, $userId, 'Menghapus foto profil');
            kembali('sukses', 'Foto profil dihapus.');
        }

        // ----- Ajukan perubahan data terkunci -----
        elseif ($aksi === 'ajukan') {
            $masukanAjukan = [
                'bidang'     => trim((string) ($_POST['bidang'] ?? '')),
                'nilai_baru' => trim((string) ($_POST['nilai_baru'] ?? '')),
                'alasan'     => trim((string) ($_POST['alasan'] ?? '')),
            ];
            if (!in_array($masukanAjukan['bidang'], $bidangPengajuan, true)) {
                $galatAjukan['bidang'] = 'Pilih data yang ingin diubah.';
            }
            if ($masukanAjukan['nilai_baru'] === '' || mb_strlen($masukanAjukan['nilai_baru']) > 150) {
                $galatAjukan['nilai_baru'] = 'Isi nilai baru (maksimal 150 karakter).';
            }
            if (mb_strlen($masukanAjukan['alasan']) < 10 || mb_strlen($masukanAjukan['alasan']) > 500) {
                $galatAjukan['alasan'] = 'Jelaskan alasan perubahan (10-500 karakter).';
            }

            if (!$galatAjukan) {
                $stmt = db()->prepare(
                    "SELECT COUNT(*) FROM pengajuan
                      WHERE user_id = :id AND jenis = 'perubahan_data' AND status = 'menunggu'"
                );
                $stmt->execute([':id' => $userId]);
                if ((int) $stmt->fetchColumn() >= MAKS_PENGAJUAN_TERBUKA) {
                    $galatAjukan['umum'] = 'Anda masih memiliki ' . MAKS_PENGAJUAN_TERBUKA
                        . ' pengajuan yang menunggu. Tunggu hingga ditinjau.';
                }
            }

            if (!$galatAjukan) {
                $isi = "Data: {$masukanAjukan['bidang']}\nNilai baru: {$masukanAjukan['nilai_baru']}\n"
                     . "Alasan: {$masukanAjukan['alasan']}";
                $stmt = db()->prepare(
                    "INSERT INTO pengajuan (user_id, jenis, judul, isi)
                     VALUES (:id, 'perubahan_data', :judul, :isi)"
                );
                $stmt->execute([
                    ':id'    => $userId,
                    ':judul' => 'Perubahan data: ' . $masukanAjukan['bidang'],
                    ':isi'   => $isi,
                ]);
                $idBaru = (int) db()->lastInsertId();

                // Beri tahu semua admin aktif.
                kirim_notifikasi(id_admin_aktif((int) $_SESSION['user_id']), 'pengajuan', 'Pengajuan perubahan data',
                    $_SESSION['nama_lengkap'] . ' mengajukan perubahan data: ' . $masukanAjukan['bidang'] . '.');

                catat_aktivitas('tambah', 'pengajuan', $idBaru, 'Mengajukan perubahan data: ' . $masukanAjukan['bidang']);
                kembali('sukses', 'Pengajuan terkirim. Admin akan meninjaunya.');
            }
        }
    } catch (PDOException $ex) {
        error_log('Profil error: ' . $ex->getMessage());
        kembali('galat', 'Terjadi kesalahan pada server. Silakan coba lagi nanti.');
    }
}

// ---------- Muat data profil ----------
$d = null;
$riwayat = [];
$dbGagal = false;

try {
    if ($adalahPemain) {
        $stmt = db()->prepare(
            'SELECT u.username, u.nama_lengkap, u.last_login, u.created_at,
                    p.nomor_punggung, pr.posisi, pr.posisi_detail, pr.kaki_dominan,
                    pr.tempat_lahir, pr.tanggal_lahir, pr.kewarganegaraan, pr.tinggi_cm, pr.berat_kg,
                    pr.telepon, pr.email, pr.alamat,
                    pr.kontak_darurat_nama AS kd_nama, pr.kontak_darurat_hubungan AS kd_hubungan,
                    pr.kontak_darurat_telepon AS kd_telepon,
                    pr.golongan_darah, pr.status, pr.tanggal_bergabung, pr.foto
               FROM users u
               LEFT JOIN pemain p ON p.user_id = u.id
               LEFT JOIN profil_pemain pr ON pr.user_id = u.id
              WHERE u.id = :id'
        );
    } else {
        $stmt = db()->prepare(
            'SELECT u.username, u.nama_lengkap, u.last_login, u.created_at,
                    s.nip, s.jabatan, s.tempat_lahir, s.tanggal_lahir, s.telepon, s.email, s.alamat,
                    s.kontak_darurat_nama AS kd_nama, s.kontak_darurat_hubungan AS kd_hubungan,
                    s.kontak_darurat_telepon AS kd_telepon,
                    s.lisensi, s.nomor_lisensi, s.lisensi_berlaku_sampai, s.status,
                    s.tanggal_bergabung, s.foto
               FROM users u
               LEFT JOIN staf s ON s.user_id = u.id
              WHERE u.id = :id'
        );
    }
    $stmt->execute([':id' => $userId]);
    $d = $stmt->fetch() ?: null;

    $stmt = db()->prepare(
        'SELECT klub, jenis, tanggal_mulai, tanggal_selesai, nilai_transfer
           FROM riwayat_karier WHERE user_id = :id ORDER BY tanggal_mulai DESC'
    );
    $stmt->execute([':id' => $userId]);
    $riwayat = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Profil muat error: ' . $ex->getMessage());
    $dbGagal = true;
}

if ($d === null) {
    $d = ['username' => $_SESSION['username'] ?? '', 'nama_lengkap' => $_SESSION['nama_lengkap'], 'last_login' => null,
          'created_at' => null, 'telepon' => null, 'email' => null, 'alamat' => null, 'kd_nama' => null,
          'kd_hubungan' => null, 'kd_telepon' => null, 'foto' => null, 'status' => null,
          'tanggal_bergabung' => null, 'tanggal_lahir' => null, 'tempat_lahir' => null];
}

// ---------- Nilai turunan untuk tampilan ----------
$nama   = (string) $d['nama_lengkap'];
$label  = ROLE_LABELS[$role] ?? $role;
$foto   = jalur_foto(is_string($d['foto'] ?? null) ? $d['foto'] : null);
$status = (string) ($d['status'] ?? '');
$kelasStatus = ['cedera' => 'merah', 'dipinjamkan' => 'biru', 'diskors' => 'kuning',
                'pensiun' => 'netral', 'cuti' => 'kuning', 'nonaktif' => 'netral'][$status] ?? '';

$nilai = $masukan ?? [
    'email' => (string) ($d['email'] ?? ''), 'telepon' => (string) ($d['telepon'] ?? ''),
    'alamat' => (string) ($d['alamat'] ?? ''), 'kd_nama' => (string) ($d['kd_nama'] ?? ''),
    'kd_hubungan' => (string) ($d['kd_hubungan'] ?? ''), 'kd_telepon' => (string) ($d['kd_telepon'] ?? ''),
];

// Kelengkapan profil dihitung dari data tersimpan (bukan dari isian yang belum disimpan).
$syaratLengkap = [
    'foto profil' => $foto !== null, 'email' => !empty($d['email']), 'telepon' => !empty($d['telepon']),
    'alamat' => !empty($d['alamat']), 'nama kontak darurat' => !empty($d['kd_nama']),
    'hubungan kontak darurat' => !empty($d['kd_hubungan']), 'telepon darurat' => !empty($d['kd_telepon']),
];
$terisi   = count(array_filter($syaratLengkap));
$persen   = (int) round($terisi / count($syaratLengkap) * 100);
$kurang   = array_keys(array_filter($syaratLengkap, static fn(bool $ok): bool => !$ok));

$zona = new DateTimeZone(ZONA_WAKTU);
$kini = new DateTimeImmutable('now', $zona);

/** "3 thn", "8 bln", "Baru bergabung". */
function lama_bergabung(?string $tgl, DateTimeImmutable $kini): string
{
    if (!$tgl) {
        return '-';
    }
    try {
        $selisih = (new DateTimeImmutable($tgl))->diff($kini);
    } catch (Exception) {
        return '-';
    }
    if ($selisih->y >= 1) {
        return $selisih->y . ' thn';
    }
    return $selisih->m >= 1 ? $selisih->m . ' bln' : 'Baru';
}

/** "Okt 2024" */
function bulan_tahun_pendek(?string $tgl): string
{
    if (!$tgl) {
        return '';
    }
    $d = new DateTimeImmutable($tgl);
    return mb_substr(NAMA_BULAN[(int) $d->format('n')], 0, 3) . ' ' . $d->format('Y');
}

/** "1 thn 4 bln" untuk rentang karier. */
function durasi(string $mulai, ?string $selesai, DateTimeImmutable $kini): string
{
    $a = new DateTimeImmutable($mulai);
    $b = $selesai ? new DateTimeImmutable($selesai) : $kini;
    $s = $a->diff($b);
    $bagian = [];
    if ($s->y > 0) { $bagian[] = $s->y . ' thn'; }
    if ($s->m > 0) { $bagian[] = $s->m . ' bln'; }
    return $bagian ? implode(' ', $bagian) : '< 1 bln';
}

$jenisKarier = ['akademi' => 'Akademi', 'permanen' => 'Permanen', 'pinjaman' => 'Pinjaman',
                'bebas_transfer' => 'Bebas transfer', 'staf' => 'Staf'];

// Subjudul & ringkasan kartu profil
if ($adalahPemain) {
    $posisiTeks = (string) ($d['posisi_detail'] ?? '') ?: ucfirst((string) ($d['posisi'] ?? ''));
    $subjudul   = ($posisiTeks !== '' ? $posisiTeks . ' · ' : '') . 'PLY-' . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
    $ringkas = [
        [isset($d['nomor_punggung']) ? '#' . $d['nomor_punggung'] : '-', 'Nomor'],
        [ucfirst((string) ($d['posisi'] ?? '')) ?: '-', 'Posisi'],
        [ucfirst((string) ($d['kaki_dominan'] ?? '')) ?: '-', 'Kaki'],
    ];
} else {
    $subjudul = ($labelJabatan[$d['jabatan'] ?? 'lainnya'] ?? 'Staf')
              . (!empty($d['nip']) ? ' · ' . $d['nip'] : '');
    $ringkas = [
        [lama_bergabung($d['tanggal_bergabung'] ?? null, $kini), 'Masa kerja'],
        [(string) count($riwayat), 'Klub'],
        [!empty($d['lisensi']) ? 'Ada' : '-', 'Lisensi'],
    ];
}

// ---------- Fungsi cetak kolom formulir ----------
$ikonKolom = [
    'email'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.500 7 8.500 6 8.500-6"/>',
    'telepon' => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    'orang'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.500-6.500 8-6.500s8 2.500 8 6.500"/>',
    'hati'    => '<path d="M12 20.500s-7.500-4.700-9-10C2.200 7 4.500 4.500 7.500 4.500c1.900 0 3.500 1 4.500 2.700 1-1.700 2.600-2.700 4.500-2.700 3 0 5.300 2.500 4.500 6C19.500 15.800 12 20.500 12 20.500z"/>',
];

function cetak_medan(string $nama, string $label, string $nilai, array $galat, string $ikon, array $o = []): void
{
    global $ikonKolom;
    $id    = 'f_' . $nama;
    $salah = isset($galat[$nama]);
    $wajib = !empty($o['wajib']);
    $area  = !empty($o['textarea']);
    ?>
                            <div class="medan<?= $salah ? ' salah' : '' ?>">
                                <label for="<?= e($id) ?>"><?= e($label) ?><?= $wajib ? '' : ' <small>(opsional)</small>' ?></label>
                                <div class="kolom<?= $area ? '' : ' berikon' ?>">
                                    <?php if (!$area): ?>
                                        <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><?= $ikonKolom[$ikon] ?></svg>
                                        <input type="<?= e($o['tipe'] ?? 'text') ?>" id="<?= e($id) ?>" name="<?= e($nama) ?>"
                                               value="<?= e($nilai) ?>" maxlength="<?= (int) ($o['maks'] ?? 100) ?>"
                                               <?= isset($o['placeholder']) ? 'placeholder="' . e($o['placeholder']) . '"' : '' ?>
                                               <?= isset($o['autocomplete']) ? 'autocomplete="' . e($o['autocomplete']) . '"' : '' ?>
                                               <?= isset($o['mode']) ? 'inputmode="' . e($o['mode']) . '"' : '' ?>
                                               <?= $salah ? 'aria-invalid="true" aria-describedby="g_' . e($nama) . '"' : '' ?>>
                                    <?php else: ?>
                                        <textarea id="<?= e($id) ?>" name="<?= e($nama) ?>" maxlength="255" rows="3"
                                                  placeholder="<?= e($o['placeholder'] ?? '') ?>"
                                                  <?= $salah ? 'aria-invalid="true" aria-describedby="g_' . e($nama) . '"' : '' ?>><?= e($nilai) ?></textarea>
                                    <?php endif; ?>
                                </div>
                                <?php if ($salah): ?><p class="pesan-salah" id="g_<?= e($nama) ?>"><?= e($galat[$nama]) ?></p><?php endif; ?>
                            </div>
<?php
}

halaman_awal('Profil Saya');
?>
            <div class="judul-halaman">
                <span class="ikon-judul"><svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.200 18.500c1.400-2.200 3.400-3.200 5.800-3.200s4.400 1 5.800 3.200"/></svg></span>
                <div>
                    <h1>Profil Saya</h1>
                    <p>Perbarui kontak, alamat, kontak darurat, dan foto Anda secara langsung.</p>
                </div>
            </div>

            <?php if ($dbGagal): ?>
                <p class="galat" role="alert">Data profil belum dapat dimuat. Pastikan <code>database.sql</code> sudah diimpor ulang.</p>
            <?php endif; ?>

            <div class="profil-kisi">
                <!-- ===== Kolom kiri ===== -->
                <div class="profil-kolom">
                    <section class="kartu kartu-profil" aria-label="Ringkasan profil">
                        <div class="sampul"></div>
                        <div class="avatar-besar"><?= avatar_isi($nama, $foto) ?></div>
                        <div class="profil-badan">
                            <h2><?= e($nama) ?></h2>
                            <p class="profil-sub"><?= e($subjudul) ?></p>
                            <div class="lencana-baris">
                                <?php if ($status !== ''): ?>
                                    <span class="badge <?= e($kelasStatus) ?>"><?= e($status) ?></span>
                                <?php endif; ?>
                                <span class="badge biru"><?= e($label) ?></span>
                            </div>

                            <div class="ringkas">
                                <?php foreach ($ringkas as [$nilaiR, $labelR]): ?>
                                    <div><b><?= e($nilaiR) ?></b><small><?= e($labelR) ?></small></div>
                                <?php endforeach; ?>
                            </div>

                            <div class="berkas-foto">
                                <form method="post" action="profil.php" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="aksi" value="foto">
                                    <input type="file" id="berkasFoto" name="foto" accept="image/jpeg,image/png,image/webp" data-foto-otomatis>
                                    <label for="berkasFoto" class="tombol kecil">
                                        <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.500-2.500h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.500" r="3.500"/></svg>
                                        Ganti foto
                                    </label>
                                </form>
                                <?php if ($foto !== null): ?>
                                    <form method="post" action="profil.php" onsubmit="return confirm('Hapus foto profil?');">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="aksi" value="hapus_foto">
                                        <button type="submit" class="tombol kecil">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="kelengkapan">
                                <div class="baris"><span>Kelengkapan profil</span><b><?= $persen ?>%</b></div>
                                <div class="progres" role="progressbar" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Kelengkapan profil">
                                    <span style="width: <?= $persen ?>%"></span>
                                </div>
                                <p><?= $kurang ? 'Lengkapi: ' . e(implode(', ', $kurang)) . '.' : 'Profil Anda sudah lengkap. Terima kasih!' ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="kartu" aria-labelledby="judulKunci">
                        <div class="kepala-kartu">
                            <h2 id="judulKunci">
                                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                Data terkunci
                            </h2>
                            <p>Perubahan bidang ini diajukan ke admin.</p>
                        </div>
                        <dl class="data-kunci">
                            <?php if ($adalahPemain): ?>
                                <div><dt>Tanggal lahir</dt><dd><?= e(tanggal_id($d['tanggal_lahir'] ?? null) ?: '-') ?></dd></div>
                                <div><dt>Tempat lahir</dt><dd><?= e((string) ($d['tempat_lahir'] ?? '') ?: '-') ?></dd></div>
                                <div><dt>Kewarganegaraan</dt><dd><?= e((string) ($d['kewarganegaraan'] ?? '') ?: '-') ?></dd></div>
                                <div><dt>Bergabung</dt><dd><?= e(tanggal_id($d['tanggal_bergabung'] ?? null) ?: '-') ?></dd></div>
                                <div><dt>Tinggi / berat</dt><dd><?= !empty($d['tinggi_cm']) ? e($d['tinggi_cm'] . ' cm / ' . rtrim(rtrim(number_format((float) $d['berat_kg'], 1, ',', ''), '0'), ',') . ' kg') : '-' ?></dd></div>
                                <div><dt>Golongan darah</dt><dd><?= e((string) ($d['golongan_darah'] ?? '') ?: '-') ?></dd></div>
                                <div><dt>Kaki dominan</dt><dd><?= e(ucfirst((string) ($d['kaki_dominan'] ?? '')) ?: '-') ?></dd></div>
                                <div><dt>Nomor punggung</dt><dd><?= isset($d['nomor_punggung']) ? e((string) $d['nomor_punggung']) : '-' ?></dd></div>
                            <?php else: ?>
                                <div><dt>NIP</dt><dd><?= e((string) ($d['nip'] ?? '') ?: '-') ?></dd></div>
                                <div><dt>Jabatan</dt><dd><?= e($labelJabatan[$d['jabatan'] ?? 'lainnya'] ?? 'Staf') ?></dd></div>
                                <div><dt>Tanggal lahir</dt><dd><?= e(tanggal_id($d['tanggal_lahir'] ?? null) ?: '-') ?></dd></div>
                                <div><dt>Tempat lahir</dt><dd><?= e((string) ($d['tempat_lahir'] ?? '') ?: '-') ?></dd></div>
                                <div><dt>Bergabung</dt><dd><?= e(tanggal_id($d['tanggal_bergabung'] ?? null) ?: '-') ?></dd></div>
                                <div><dt>Lisensi berlaku</dt><dd><?= e(tanggal_id($d['lisensi_berlaku_sampai'] ?? null) ?: '-') ?></dd></div>
                                <div class="lebar"><dt>Lisensi</dt><dd><?= e(trim((string) ($d['lisensi'] ?? '') . ' ' . (string) ($d['nomor_lisensi'] ?? '')) ?: '-') ?></dd></div>
                            <?php endif; ?>
                        </dl>
                        <button type="button" class="tombol penuh" data-buka-dialog="dialogAjukan">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                            Ajukan perubahan data
                        </button>
                    </section>

                    <section class="kartu" aria-labelledby="judulAkun">
                        <div class="kepala-kartu">
                            <h2 id="judulAkun">
                                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.500 8 8 9 4.500-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                                Informasi akun
                            </h2>
                        </div>
                        <dl class="info-akun">
                            <div><dt>Username</dt><dd><?= e((string) $d['username']) ?></dd></div>
                            <div><dt>Peran</dt><dd><?= e($label) ?></dd></div>
                            <div><dt>Login terakhir</dt><dd><?php
                                $ll = $d['last_login'] ?? null;
                                echo $ll ? e(tanggal_id($ll) . ', ' . (new DateTimeImmutable($ll))->format('H:i')) : '-';
                            ?></dd></div>
                            <div><dt>Akun dibuat</dt><dd><?= e(tanggal_id($d['created_at'] ?? null) ?: '-') ?></dd></div>
                        </dl>
                        <a class="tombol penuh" href="ganti_password.php" style="margin-top:16px">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="m10.800 12.200 9.200-9.200M17 6l3 3M14 9l2 2"/></svg>
                            Ganti password
                        </a>
                    </section>
                </div>

                <!-- ===== Kolom kanan ===== -->
                <div class="profil-kolom">
                    <section class="kartu" aria-labelledby="judulKontak">
                        <div class="kepala-kartu">
                            <h2 id="judulKontak">
                                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>
                                Kontak &amp; kontak darurat
                            </h2>
                            <p>
                                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                                Pastikan kontak darurat selalu benar agar klub dapat menghubungi keluarga Anda saat dibutuhkan.
                            </p>
                        </div>

                        <form method="post" action="profil.php" data-pantau<?= $galat ? ' data-mulai-kotor' : '' ?> novalidate>
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="simpan">

                            <div class="pemisah-bagian">Kontak pribadi</div>
                            <div class="baris-2">
                                <?php cetak_medan('email', 'Email pribadi', $nilai['email'], $galat, 'email', ['tipe' => 'email', 'maks' => 120, 'autocomplete' => 'email', 'placeholder' => 'nama@email.com']); ?>
                                <?php cetak_medan('telepon', 'Telepon', $nilai['telepon'], $galat, 'telepon', ['tipe' => 'tel', 'maks' => 25, 'wajib' => true, 'autocomplete' => 'tel', 'mode' => 'tel', 'placeholder' => '08xxxxxxxxxx']); ?>
                            </div>
                            <?php cetak_medan('alamat', 'Alamat', $nilai['alamat'], $galat, 'email', ['textarea' => true, 'placeholder' => 'Jalan, nomor, kota']); ?>

                            <div class="pemisah-bagian">Kontak darurat</div>
                            <div class="baris-3">
                                <?php cetak_medan('kd_nama', 'Nama kontak darurat', $nilai['kd_nama'], $galat, 'orang', ['maks' => 100]); ?>
                                <?php cetak_medan('kd_hubungan', 'Hubungan', $nilai['kd_hubungan'], $galat, 'hati', ['maks' => 40, 'placeholder' => 'Ibu, Istri, ...']); ?>
                                <?php cetak_medan('kd_telepon', 'Telepon darurat', $nilai['kd_telepon'], $galat, 'telepon', ['tipe' => 'tel', 'maks' => 25, 'mode' => 'tel']); ?>
                            </div>

                            <div class="bar-simpan">
                                <span class="status-ubah" aria-live="polite">Tidak ada perubahan</span>
                                <div class="aksi-kanan">
                                    <button type="reset" class="tombol">Batalkan</button>
                                    <button type="submit" class="tombol utama" data-simpan>
                                        <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h11l4 4v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M8 3v5h7V3M8 21v-7h8v7"/></svg>
                                        Simpan perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <section class="kartu" aria-labelledby="judulKarier">
                        <div class="kepala-kartu">
                            <h2 id="judulKarier">
                                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.700L3 8M3 3v5h5M12 7v5l3 2"/></svg>
                                Riwayat karier &amp; transfer
                            </h2>
                        </div>
                        <?php if ($riwayat): ?>
                            <ol class="garis-waktu">
                                <?php foreach ($riwayat as $r): ?>
                                    <?php $berjalan = $r['tanggal_selesai'] === null; ?>
                                    <li<?= $berjalan ? ' class="sekarang"' : '' ?>>
                                        <div class="kartu-karier">
                                            <div>
                                                <h3><?= e((string) $r['klub']) ?></h3>
                                                <p class="periode">
                                                    <?= e(bulan_tahun_pendek($r['tanggal_mulai'])) ?> &ndash;
                                                    <?= $berjalan ? 'sekarang' : e(bulan_tahun_pendek($r['tanggal_selesai'])) ?>
                                                    &middot; <?= e(durasi($r['tanggal_mulai'], $r['tanggal_selesai'], $kini)) ?>
                                                </p>
                                            </div>
                                            <div class="kanan-karier">
                                                <span class="badge <?= $berjalan ? '' : 'netral' ?>"><?= e($jenisKarier[$r['jenis']] ?? (string) $r['jenis']) ?></span>
                                                <?php if ($r['nilai_transfer'] !== null && (float) $r['nilai_transfer'] > 0): ?>
                                                    <span class="nilai-transfer"><?= e(rupiah_ringkas($r['nilai_transfer'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <p class="kosong-kecil">Belum ada riwayat karier yang tercatat.</p>
                        <?php endif; ?>
                    </section>
                </div>
            </div>

            <!-- ===== Dialog: ajukan perubahan data ===== -->
            <dialog class="modal" id="dialogAjukan" aria-labelledby="judulAjukan"<?= $galatAjukan ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="profil.php" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="ajukan">
                    <div class="modal-kepala">
                        <div>
                            <h2 id="judulAjukan">Ajukan perubahan data</h2>
                            <p>Admin akan meninjau pengajuan Anda sebelum data diubah.</p>
                        </div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <?php if (isset($galatAjukan['umum'])): ?>
                            <p class="alert-form" role="alert"><?= e($galatAjukan['umum']) ?></p>
                        <?php endif; ?>

                        <div class="medan<?= isset($galatAjukan['bidang']) ? ' salah' : '' ?>">
                            <label for="a_bidang">Data yang ingin diubah</label>
                            <div class="kolom">
                                <select id="a_bidang" name="bidang" autofocus>
                                    <option value="">Pilih data...</option>
                                    <?php foreach ($bidangPengajuan as $b): ?>
                                        <option value="<?= e($b) ?>"<?= $masukanAjukan['bidang'] === $b ? ' selected' : '' ?>><?= e($b) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (isset($galatAjukan['bidang'])): ?><p class="pesan-salah"><?= e($galatAjukan['bidang']) ?></p><?php endif; ?>
                        </div>

                        <div class="medan<?= isset($galatAjukan['nilai_baru']) ? ' salah' : '' ?>">
                            <label for="a_nilai">Nilai yang benar</label>
                            <div class="kolom">
                                <input type="text" id="a_nilai" name="nilai_baru" maxlength="150"
                                       value="<?= e($masukanAjukan['nilai_baru']) ?>" placeholder="Mis. 22 April 1998">
                            </div>
                            <?php if (isset($galatAjukan['nilai_baru'])): ?><p class="pesan-salah"><?= e($galatAjukan['nilai_baru']) ?></p><?php endif; ?>
                        </div>

                        <div class="medan<?= isset($galatAjukan['alasan']) ? ' salah' : '' ?>">
                            <label for="a_alasan">Alasan</label>
                            <div class="kolom">
                                <textarea id="a_alasan" name="alasan" maxlength="500" rows="3"
                                          placeholder="Jelaskan mengapa data perlu diubah"><?= e($masukanAjukan['alasan']) ?></textarea>
                            </div>
                            <?php if (isset($galatAjukan['alasan'])): ?><p class="pesan-salah"><?= e($galatAjukan['alasan']) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama">Kirim pengajuan</button>
                    </div>
                </form>
            </dialog>
<?php halaman_akhir(); ?>
