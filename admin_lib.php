<?php
declare(strict_types=1);

// Fungsi bersama halaman admin/HR: Data Pemain, Data Staf, Kelola Kontrak, Data Penggajian, Ringkasan Gaji.
// Membutuhkan tampilan.php.

const KODE_POSISI = [
    'GK' => ['Kiper', 'kiper'],
    'CB' => ['Bek Tengah', 'bek'], 'LB' => ['Bek Kiri', 'bek'], 'RB' => ['Bek Kanan', 'bek'],
    'DM' => ['Gelandang Bertahan', 'gelandang'], 'CM' => ['Gelandang Tengah', 'gelandang'],
    'AM' => ['Gelandang Serang', 'gelandang'], 'LW' => ['Sayap Kiri', 'penyerang'],
    'RW' => ['Sayap Kanan', 'penyerang'], 'ST' => ['Penyerang', 'penyerang'],
];
const LABEL_LINI = ['kiper' => 'Kiper', 'bek' => 'Belakang', 'gelandang' => 'Tengah', 'penyerang' => 'Depan'];
const LABEL_STATUS_PEMAIN = [
    'aktif' => ['Aktif', 'hijau'], 'cedera' => ['Cedera', 'merah'], 'diskors' => ['Diskors', 'kuning'],
    'dipinjamkan' => ['Dipinjamkan', 'biru'], 'pensiun' => ['Pensiun', 'netral'],
];
const LABEL_DIVISI = [
    'manajemen' => 'Manajemen', 'kepelatihan' => 'Kepelatihan', 'medis' => 'Medis', 'analisis' => 'Analisis',
    'administrasi' => 'Administrasi', 'keuangan' => 'Keuangan', 'operasional' => 'Operasional',
];
const LABEL_STATUS_KERJA = [
    'tetap' => ['Tetap', 'hijau'], 'kontrak' => ['Kontrak', 'biru'],
    'paruh_waktu' => ['Paruh waktu', 'ungu'], 'magang' => ['Magang', 'netral'],
];
const LABEL_JABATAN_ENUM = [
    'manager' => 'Manajer', 'admin' => 'Administrasi / HR', 'pelatih_kepala' => 'Pelatih Kepala',
    'asisten_pelatih' => 'Asisten Pelatih', 'pelatih_kiper' => 'Pelatih Kiper', 'pelatih_fisik' => 'Pelatih Fisik',
    'dokter' => 'Dokter Tim', 'fisioterapis' => 'Fisioterapis', 'analis' => 'Analis Performa', 'lainnya' => 'Lainnya',
];
const LABEL_JENIS_KONTRAK_ADMIN = [
    'profesional' => 'Profesional', 'amatir' => 'Amatir', 'pinjaman' => 'Pinjaman', 'percobaan' => 'Percobaan', 'staf' => 'Karyawan',
];
// Status kontrak sebagaimana dilihat HR (aktif dipecah menurut sisa hari).
const STATUS_KONTRAK_EFEKTIF = [
    'aktif' => ['Aktif', 'hijau'], 'hampir' => ['Hampir habis', 'kuning'], 'kedaluwarsa' => ['Kedaluwarsa', 'merah'],
    'diperbarui' => ['Diperbarui', 'netral'], 'berakhir' => ['Berakhir', 'netral'],
    'draft' => ['Draf', 'biru'], 'dibatalkan' => ['Dibatalkan', 'merah'], 'tidak_ada' => ['Tidak ada', 'netral'],
];
const BATAS_HAMPIR_HABIS = 90;

function wajib_admin(): void
{
    require_role('admin');
}

/** Status efektif satu kontrak (atau 'tidak_ada' bila null). */
function status_kontrak(?array $k): string
{
    if ($k === null || empty($k['status'])) {
        return 'tidak_ada';
    }
    if ($k['status'] !== 'aktif') {
        return (string) $k['status'];
    }
    $sisa = selisih_hari(hari_ini()->format('Y-m-d'), (string) $k['tanggal_selesai']);
    return $sisa < 0 ? 'kedaluwarsa' : ($sisa <= BATAS_HAMPIR_HABIS ? 'hampir' : 'aktif');
}

function chip_peta(array $peta, string $kunci): string
{
    [$teks, $kelas] = $peta[$kunci] ?? [$kunci, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

function usia(?string $lahir): ?int
{
    return $lahir ? (new DateTimeImmutable($lahir))->diff(hari_ini())->y : null;
}

function id_pemain(int $id): string
{
    return 'PMN-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

/** Lama kontrak (bulan) dan nilainya = gaji x bulan + bonus tanda tangan. */
function bulan_kontrak(string $mulai, string $selesai): int
{
    $d = (new DateTimeImmutable($mulai))->diff((new DateTimeImmutable($selesai))->modify('+1 day'));
    return $d->y * 12 + $d->m + ($d->d >= 15 ? 1 : 0);
}
function nilai_kontrak_admin(array $k): float
{
    return (float) $k['gaji_pokok'] * bulan_kontrak((string) $k['tanggal_mulai'], (string) $k['tanggal_selesai']) + (float) $k['bonus_tanda_tangan'];
}

/** Nomor kontrak berikutnya: KTR-2026-046. */
function nomor_kontrak_baru(): string
{
    $tahun = hari_ini()->format('Y');
    $maks = (int) db()->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(nomor_kontrak, '-', -1) AS UNSIGNED)), 0) FROM kontrak WHERE nomor_kontrak REGEXP '^KTR-[0-9]{4}-[0-9]+$'")->fetchColumn();
    return 'KTR-' . $tahun . '-' . str_pad((string) ($maks + 1), 3, '0', STR_PAD_LEFT);
}

/**
 * Pengingat otomatis kontrak hampir habis: notifikasi ke semua admin dan pemilik kontrak
 * saat sisa <= 90, <= 60, <= 30 hari, dan saat lewat. Tiap tahap hanya sekali (tabel pengingat_kontrak).
 */
function kirim_pengingat_kontrak(): void
{
    try {
        $kontrak = db()->query(
            "SELECT k.id, k.user_id, k.nomor_kontrak, k.tanggal_selesai, u.nama_lengkap,
                    DATEDIFF(k.tanggal_selesai, CURDATE()) AS sisa
               FROM kontrak k JOIN users u ON u.id = k.user_id
              WHERE k.status = 'aktif' AND u.is_active = 1 AND k.tanggal_selesai <= CURDATE() + INTERVAL 90 DAY"
        )->fetchAll();
        if (!$kontrak) {
            return;
        }
        $admin = db()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        $catat = db()->prepare('INSERT IGNORE INTO pengingat_kontrak (kontrak_id, tahap) VALUES (:k, :t)');
        foreach ($kontrak as $k) {
            $sisa = (int) $k['sisa'];
            $tahap = $sisa < 0 ? 'lewat' : ($sisa <= 30 ? 'H30' : ($sisa <= 60 ? 'H60' : 'H90'));
            $catat->execute([':k' => $k['id'], ':t' => $tahap]);
            if ($catat->rowCount() !== 1) {
                continue; // tahap ini sudah pernah dikirim
            }
            $teks = $sisa < 0 ? 'sudah lewat ' . (-$sisa) . ' hari' : 'berakhir dalam ' . $sisa . ' hari';
            kirim_notifikasi($admin, 'kontrak', 'Kontrak ' . $k['nama_lengkap'] . ' ' . $teks,
                $k['nomor_kontrak'] . ' berakhir ' . tanggal_id((string) $k['tanggal_selesai']) . '. Tinjau perpanjangan atau akhiri kontrak.', 'kelola_kontrak.php?hal=hampir', true);
            if (!in_array($k['user_id'], $admin, false)) {
                kirim_notifikasi((int) $k['user_id'], 'kontrak', 'Kontrak Anda ' . $teks,
                    'Hubungi HR untuk membicarakan perpanjangan ' . $k['nomor_kontrak'] . '.', 'kontrak.php', true);
            }
        }
    } catch (PDOException $ex) {
        error_log('Pengingat kontrak gagal: ' . $ex->getMessage());
    }
}

/** Kirim CSV yang langsung terbuka rapi di Excel (UTF-8 BOM, pemisah titik koma). */
function unduh_csv(string $nama, array $kepala, array $baris): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nama . '"');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $kepala, ';');
    foreach ($baris as $b) {
        // Cegah formula injection: sel yang diawali = + - @ diberi tanda kutip.
        $b = array_map(static fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false ? "'" . $v : $v, $b);
        fputcsv($out, $b, ';');
    }
    fclose($out);
    exit;
}

/**
 * Susun ulang rincian satu slip dari kontraknya: gaji, tunjangan, bonus gol/penampilan bulan itu,
 * bonus tanda tangan (bulan pertama), PPh 21 5%, BPJS, denda kartu, plus bonus/potongan manual.
 * Lalu samakan kolom ringkas penggajian. Hanya untuk slip berstatus draf.
 */
function susun_rincian_slip(int $idSlip, float $bonusManual = 0, float $potonganManual = 0, string $ketManual = ''): void
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT p.*, k.gaji_pokok AS k_gaji, k.tunjangan_bulanan, k.bonus_per_goal, k.bonus_per_main,
                                  k.bonus_tanda_tangan, k.tanggal_mulai
                             FROM penggajian p JOIN kontrak k ON k.id = p.kontrak_id
                            WHERE p.id = :id AND p.status = 'draft'");
    $stmt->execute([':id' => $idSlip]);
    $s = $stmt->fetch();
    if (!$s) {
        return;
    }
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(st.goal),0) gol, COALESCE(SUM(st.menit_bermain > 0),0) laga,
                COALESCE(SUM(st.kartu_kuning),0) kuning, COALESCE(SUM(st.kartu_merah),0) merah
           FROM statistik_pertandingan st JOIN jadwal j ON j.id = st.jadwal_id
          WHERE st.user_id = :u AND DATE_FORMAT(j.waktu_mulai, '%Y-%m-01') = :p"
    );
    $stmt->execute([':u' => $s['user_id'], ':p' => $s['periode']]);
    $st = $stmt->fetch();

    $r = [];
    $r[] = ['gaji', 'gaji_pokok', 'Gaji pokok', (float) $s['k_gaji'], 1];
    if ((float) $s['tunjangan_bulanan'] > 0) {
        $r[] = ['tunjangan', 'tunjangan', 'Tunjangan transport & makan', (float) $s['tunjangan_bulanan'], 2];
    }
    if ((int) $st['gol'] > 0 && (float) $s['bonus_per_goal'] > 0) {
        $r[] = ['bonus_gol', 'bonus', 'Bonus gol (' . (int) $st['gol'] . ' gol)', (int) $st['gol'] * (float) $s['bonus_per_goal'], 3];
    }
    if ((int) $st['laga'] > 0 && (float) $s['bonus_per_main'] > 0) {
        $r[] = ['bonus_main', 'bonus', 'Bonus penampilan (' . (int) $st['laga'] . ' laga)', (int) $st['laga'] * (float) $s['bonus_per_main'], 4];
    }
    if ((float) $s['bonus_tanda_tangan'] > 0 && substr((string) $s['tanggal_mulai'], 0, 7) === substr((string) $s['periode'], 0, 7)) {
        $r[] = ['bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', (float) $s['bonus_tanda_tangan'], 5];
    }
    if ($bonusManual > 0) {
        $r[] = ['bonus_lain', 'bonus', $ketManual !== '' ? 'Bonus: ' . $ketManual : 'Bonus lainnya', $bonusManual, 6];
    }
    $bruto = array_sum(array_column($r, 3));
    $r[] = ['pph21', 'potongan', 'PPh 21 (5% dari bruto)', round(0.05 * $bruto, -2), 10];
    $r[] = ['bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', round(0.01 * min((float) $s['k_gaji'], 12000000), -2), 11];
    $r[] = ['bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', round(0.02 * (float) $s['k_gaji'], -2), 12];
    $denda = (int) $st['kuning'] * 250000 + (int) $st['merah'] * 1000000;
    if ($denda > 0) {
        $r[] = ['denda_kartu', 'potongan', 'Denda kartu (' . (int) $st['kuning'] . ' kuning, ' . (int) $st['merah'] . ' merah)', $denda, 14];
    }
    if ($potonganManual > 0) {
        $r[] = ['potongan_lain', 'potongan', $ketManual !== '' ? 'Potongan: ' . $ketManual : 'Potongan lainnya', $potonganManual, 15];
    }

    $pdo->prepare('DELETE FROM penggajian_rincian WHERE penggajian_id = :id')->execute([':id' => $idSlip]);
    $ins = $pdo->prepare('INSERT INTO penggajian_rincian (penggajian_id, kode, kategori, keterangan, jumlah, urutan) VALUES (?, ?, ?, ?, ?, ?)');
    $jumlah = ['gaji_pokok' => 0, 'tunjangan' => 0, 'bonus' => 0, 'potongan' => 0];
    foreach ($r as [$kode, $kat, $ket, $nilai, $urut]) {
        $ins->execute([$idSlip, $kode, $kat, $ket, $nilai, $urut]);
        $jumlah[$kat] += $nilai;
    }
    $pdo->prepare('UPDATE penggajian SET gaji_pokok = ?, tunjangan = ?, bonus = ?, potongan = ? WHERE id = ?')
        ->execute([$jumlah['gaji_pokok'], $jumlah['tunjangan'], $jumlah['bonus'], $jumlah['potongan'], $idSlip]);
}

/** Tombol menu aksi baris (⋯). $item: [['label','href'] | ['label', null, 'atribut html'] ...] */
function menu_baris(array $item, string $label = 'Aksi'): string
{
    $html = '<details class="menu-baris"><summary aria-label="' . e($label) . '" title="' . e($label) . '">'
        . '<svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/></svg></summary><div class="isi-menu-baris">';
    foreach ($item as $i) {
        if ($i === '-') {
            $html .= '<hr>';
        } elseif ($i[1] !== null) {
            $html .= '<a href="' . e($i[1]) . '">' . $i[0] . '</a>';
        } else {
            $html .= '<button type="button" ' . $i[2] . '>' . $i[0] . '</button>';
        }
    }
    return $html . '</div></details>';
}

/** Atribut tombol yang mengisi dialog formulir/detail dari data JSON (diproses app.js). */
function isi_dialog(string $idDialog, array $data, string $judul = ''): string
{
    return 'data-isi-dialog="' . e($idDialog) . '" data-json="' . e((string) json_encode($data, JSON_UNESCAPED_UNICODE)) . '"'
        . ($judul !== '' ? ' data-judul="' . e($judul) . '"' : '');
}

/** Validasi tujuan kembali ke detail karyawan agar tidak bisa dipakai sebagai open redirect. */
function alamat_detail_kembali(mixed $tujuan, int $id): ?string
{
    if (!is_string($tujuan) || $id < 1) {
        return null;
    }

    $bagian = parse_url($tujuan);
    if ($bagian === false || ($bagian['path'] ?? '') !== 'detail_karyawan.php'
        || isset($bagian['scheme']) || isset($bagian['host']) || isset($bagian['port'])
        || isset($bagian['user']) || isset($bagian['pass']) || isset($bagian['fragment'])) {
        return null;
    }

    parse_str($bagian['query'] ?? '', $parameter);
    if (array_diff(array_keys($parameter), ['id', 'tab'])
        || !isset($parameter['id'])
        || !is_string($parameter['id'])
        || filter_var($parameter['id'], FILTER_VALIDATE_INT) !== $id) {
        return null;
    }

    $tab = $parameter['tab'] ?? 'profil';
    if (!is_string($tab) || !in_array($tab, ['profil', 'kontrak', 'dokumen', 'performa'], true)) {
        return null;
    }

    return 'detail_karyawan.php?id=' . $id . ($tab !== 'profil' ? '&tab=' . $tab : '');
}

function pesan_galat_password_baru(string $password): string
{
    if (strlen($password) < 8) {
        return 'Password baru minimal 8 karakter.';
    }
    if (strlen($password) > 72) {
        return 'Password baru maksimal 72 byte.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password baru harus mengandung huruf dan angka.';
    }

    return '';
}

/** Kembali (PRG) ke halaman yang sama dengan parameter GET semula. */
function kembali_ke(string $alamat): never
{
    header('Location: ' . $alamat, true, 303);
    exit;
}

/** Kata sandi sementara yang mudah dibaca (tanpa 0/O, 1/l). */
function sandi_sementara(): string
{
    $huruf = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $angka = '23456789';
    $s = '';
    for ($i = 0; $i < 6; $i++) {
        $s .= $huruf[random_int(0, strlen($huruf) - 1)];
    }
    return $s . $angka[random_int(0, 7)] . $angka[random_int(0, 7)] . '#K';
}

// ---------- Foto & dokumen karyawan (halaman detail) ----------

const FOTO_TIPE_ADMIN = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const DOKUMEN_TIPE = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
const DOKUMEN_MAKS_BYTE = 5 * 1024 * 1024;
const LABEL_JENIS_DOKUMEN = [
    'identitas' => 'Identitas (KTP/Paspor)', 'kontrak' => 'Kontrak bertanda tangan', 'medis' => 'Medis',
    'lisensi' => 'Lisensi', 'sertifikat' => 'Sertifikat', 'lainnya' => 'Lainnya',
];

/** Ambil berkas unggahan yang sah; kembalikan [tmp, mime] atau pesan galat (string). */
function periksa_unggahan(?array $f, int $maks, array $tipe): array|string
{
    if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $kode = is_array($f) ? (int) ($f['error'] ?? 0) : UPLOAD_ERR_NO_FILE;
        return in_array($kode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Berkas terlalu besar (maksimal ' . round($maks / 1048576) . ' MB).' : 'Pilih berkas untuk diunggah.';
    }
    $tmp = (string) $f['tmp_name'];
    if (!is_uploaded_file($tmp) || $f['size'] < 1 || $f['size'] > $maks) {
        return 'Ukuran berkas maksimal ' . round($maks / 1048576) . ' MB.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    if (!isset($tipe[$mime])) {
        return 'Jenis berkas tidak didukung. Gunakan ' . strtoupper(implode(', ', array_unique($tipe))) . '.';
    }
    return [$tmp, $mime];
}

/** Admin mengganti foto profil karyawan. Mengembalikan '' bila sukses, atau pesan galat. */
function ganti_foto_karyawan(int $userId, string $role, ?array $f): string
{
    $cek = periksa_unggahan($f, 2 * 1024 * 1024, FOTO_TIPE_ADMIN);
    if (is_string($cek)) {
        return $cek;
    }
    [$tmp, $mime] = $cek;
    $info = @getimagesize($tmp);
    if ($info === false || $info['mime'] !== $mime || $info[0] < 100 || $info[1] < 100 || $info[0] > 6000 || $info[1] > 6000) {
        return 'Berkas bukan foto yang valid (100–6000 piksel, JPG/PNG/WebP).';
    }
    $folder = __DIR__ . '/uploads/foto';
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        return 'Folder foto tidak dapat dibuat di server.';
    }
    $jalur = 'uploads/foto/u' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . FOTO_TIPE_ADMIN[$mime];
    if (!move_uploaded_file($tmp, __DIR__ . '/' . $jalur)) {
        return 'Foto tidak dapat disimpan di server.';
    }
    $tabel = tabel_profil($role);
    $lama = foto_pengguna($userId, $role);
    try {
        db()->prepare("INSERT IGNORE INTO $tabel (user_id) VALUES (?)")->execute([$userId]);
        db()->prepare("UPDATE $tabel SET foto = ? WHERE user_id = ?")->execute([$jalur, $userId]);
    } catch (PDOException $ex) {
        @unlink(__DIR__ . '/' . $jalur);
        throw $ex;
    }
    if ($lama !== null && jalur_foto($lama) !== null) {
        @unlink(__DIR__ . '/' . $lama);
    }
    catat_aktivitas('ubah', $tabel, $userId, 'Admin mengganti foto profil');
    return '';
}

/** Simpan dokumen karyawan. Mengembalikan '' bila sukses, atau pesan galat. */
function unggah_dokumen(int $userId, string $jenis, string $judul, string $berlaku, ?array $f): string
{
    if (!isset(LABEL_JENIS_DOKUMEN[$jenis])) {
        return 'Pilih jenis dokumen.';
    }
    if (mb_strlen($judul) < 3 || mb_strlen($judul) > 150) {
        return 'Judul dokumen 3–150 karakter.';
    }
    if ($berlaku !== '') {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $berlaku);
        if ($d === false || $d->format('Y-m-d') !== $berlaku) {
            return 'Tanggal berlaku tidak valid.';
        }
    }
    $cek = periksa_unggahan($f, DOKUMEN_MAKS_BYTE, DOKUMEN_TIPE);
    if (is_string($cek)) {
        return $cek;
    }
    [$tmp, $mime] = $cek;
    $folder = __DIR__ . '/uploads/dokumen';
    if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
        return 'Folder dokumen tidak dapat dibuat di server.';
    }
    $jalur = 'uploads/dokumen/d' . $userId . '_' . bin2hex(random_bytes(12)) . '.' . DOKUMEN_TIPE[$mime];
    if (!move_uploaded_file($tmp, __DIR__ . '/' . $jalur)) {
        return 'Dokumen tidak dapat disimpan di server.';
    }
    // Nama asli hanya untuk tampilan & nama unduhan; dibersihkan dari karakter berbahaya.
    $asli = mb_substr((string) preg_replace('/[^\p{L}\p{N}._ -]/u', '_', basename((string) $f['name'])), 0, 200) ?: 'dokumen';
    try {
        db()->prepare('INSERT INTO dokumen_karyawan (user_id, jenis, judul, nama_berkas, jalur, mime, ukuran, berlaku_sampai, diunggah_oleh)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$userId, $jenis, $judul, $asli, $jalur, $mime, (int) $f['size'], $berlaku === '' ? null : $berlaku, $_SESSION['user_id']]);
    } catch (PDOException $ex) {
        @unlink(__DIR__ . '/' . $jalur);
        throw $ex;
    }
    catat_aktivitas('unggah', 'dokumen_karyawan', (int) db()->lastInsertId(), $judul);
    return '';
}

function ukuran_berkas(int $b): string
{
    return $b >= 1048576 ? desimal($b / 1048576) . ' MB' : max(1, (int) round($b / 1024)) . ' KB';
}
