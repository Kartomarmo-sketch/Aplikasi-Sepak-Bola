<?php
declare(strict_types=1);

// Pusat notifikasi: kategori, pengiriman (menghormati preferensi pengguna),
// pengingat otomatis dokumen kedaluwarsa, dan panel dropdown di bilah atas.
// Dimuat oleh tampilan.php sehingga tersedia di semua halaman terlindungi.

/** kode => [label, ikon, warna, keterangan untuk pengaturan] */
const KATEGORI_NOTIF = [
    'kontrak'   => ['Kontrak', 'pena', 'biru', 'Kontrak baru, perpanjangan, dan kontrak yang hampir berakhir.'],
    'gaji'      => ['Gaji', 'uang', 'hijau', 'Slip gaji terbit dan gaji sudah ditransfer.'],
    'pengajuan' => ['Pengajuan', 'kotak-masuk', 'ungu', 'Pengajuan cuti/izin baru dan keputusan HR.'],
    'jadwal'    => ['Jadwal', 'kalender', 'kuning', 'Latihan, pertandingan, rapat, dan perjalanan tandang.'],
    'performa'  => ['Performa', 'grafik-batang', 'hijau', 'Hasil tes kebugaran dan program latihan baru.'],
    'medis'     => ['Medis', 'medis', 'merah', 'Catatan cedera, penanganan, dan status pulih.'],
    'dokumen'   => ['Dokumen', 'dokumen', 'kuning', 'Lisensi, sertifikat, dan paspor yang akan kedaluwarsa.'],
    'sistem'    => ['Sistem', 'info', 'netral', 'Akun, keamanan, dan pengumuman klub.'],
];

/** Kategori yang tidak dapat dimatikan (keamanan & akun). */
const KATEGORI_NOTIF_WAJIB = ['sistem'];

/**
 * Kirim notifikasi ke satu/banyak pengguna. Pengguna yang mematikan kategori ini dilewati,
 * kecuali $penting = true. Mengembalikan jumlah notifikasi yang benar-benar dibuat.
 * PDOException diteruskan agar transaksi pemanggil dapat dibatalkan.
 */
function kirim_notifikasi(int|array $penerima, string $kategori, string $judul, ?string $pesan = null,
                          ?string $tautan = null, bool $penting = false): int
{
    $kategori = isset(KATEGORI_NOTIF[$kategori]) ? $kategori : 'sistem';
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $penerima), static fn(int $i) => $i > 0)));
    if (!$ids) {
        return 0;
    }
    if (!$penting && !in_array($kategori, KATEGORI_NOTIF_WAJIB, true)) {
        $tanda = implode(',', array_fill(0, count($ids), '?'));
        $st = db()->prepare("SELECT user_id FROM notifikasi_preferensi WHERE kategori = ? AND aktif = 0 AND user_id IN ($tanda)");
        $st->execute([$kategori, ...$ids]);
        $ids = array_values(array_diff($ids, array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN))));
    }
    $ins = db()->prepare('INSERT INTO notifikasi (user_id, judul, pesan, tautan, kategori, prioritas) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($ids as $id) {
        $ins->execute([$id, mb_substr($judul, 0, 150), $pesan === null ? null : mb_substr($pesan, 0, 500),
                       $tautan, $kategori, $penting ? 'penting' : 'normal']);
    }
    return count($ids);
}

/** ID semua admin aktif (opsional kecuali satu pengguna, mis. pengirim). */
function id_admin_aktif(?int $kecuali = null): array
{
    $ids = array_map('intval', db()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN));
    return $kecuali === null ? $ids : array_values(array_diff($ids, [$kecuali]));
}

/** Hanya tautan internal aplikasi (nama-berkas.php[?..][#..]) yang boleh dibuka dari notifikasi. */
function notif_tautan_aman(?string $tautan): ?string
{
    return $tautan !== null && preg_match('/^[a-z_]+\.php(?:\?[A-Za-z0-9_=&%.\-]*)?(?:#[A-Za-z0-9_\-]*)?$/', $tautan) ? $tautan : null;
}

/** Alamat halaman saat ini (relatif & aman) untuk kembali setelah aksi tanpa JavaScript. */
function notif_alamat_kini(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $jalur = basename((string) parse_url($uri, PHP_URL_PATH));
    $q = (string) parse_url($uri, PHP_URL_QUERY);
    return notif_tautan_aman($jalur . ($q !== '' ? '?' . $q : '')) ?? (notif_tautan_aman($jalur) ?? 'notifikasi.php');
}

/** "baru saja", "12 mnt lalu", "3 jam lalu", "kemarin 14.20", "3 hari lalu", "12 Okt 2026". */
function waktu_notif(string $waktu): string
{
    $t = new DateTimeImmutable($waktu, new DateTimeZone(ZONA_WAKTU));
    $kini = new DateTimeImmutable('now', new DateTimeZone(ZONA_WAKTU));
    $detik = $kini->getTimestamp() - $t->getTimestamp();
    $hari = selisih_hari($t->format('Y-m-d'));
    return match (true) {
        $detik < 60      => 'baru saja',
        $detik < 3600    => intdiv($detik, 60) . ' mnt lalu',
        $hari <= 0       => intdiv($detik, 3600) . ' jam lalu',
        $hari === 1      => 'kemarin ' . $t->format('H.i'),
        $hari < 7        => $hari . ' hari lalu',
        default          => tanggal_id($t->format('Y-m-d')),
    };
}

/** Kelompok tanggal untuk daftar: Hari ini, Kemarin, 7 hari terakhir, Bulan ini, Lebih lama. */
function kelompok_notif(string $waktu): string
{
    $hari = selisih_hari(substr($waktu, 0, 10));
    return match (true) {
        $hari <= 0  => 'Hari ini',
        $hari === 1 => 'Kemarin',
        $hari < 7   => '7 hari terakhir',
        $hari < 30  => '30 hari terakhir',
        default     => 'Lebih lama',
    };
}

/** Ikon kategori dalam lingkaran berwarna. */
function ikon_notif(string $kategori): string
{
    [, $ikon, $warna] = KATEGORI_NOTIF[$kategori] ?? KATEGORI_NOTIF['sistem'];
    return '<span class="ikon-notif ' . e($warna) . '">' . ikon($ikon) . '</span>';
}

/** Tautan buka notifikasi: tandai dibaca lalu arahkan ke tujuan (token CSRF mencegah penandaan paksa). */
function tautan_buka_notif(int $id): string
{
    return 'notifikasi.php?buka=' . $id . '&t=' . rawurlencode(csrf_token());
}

/**
 * Jalankan pengingat otomatis (kontrak & dokumen) paling sering sekali per jam per sesi,
 * agar pemilik kontrak/dokumen tetap mendapat kabar walau admin belum membuka halaman HR.
 */
function jalankan_pengingat(): void
{
    if (($_SESSION['pengingat_cek'] ?? 0) > time() - 3600) {
        return;
    }
    $_SESSION['pengingat_cek'] = time();
    require_once __DIR__ . '/admin_lib.php';
    kirim_pengingat_kontrak();
    kirim_pengingat_dokumen();
}

/** Dokumen (lisensi/sertifikat/paspor) yang kedaluwarsa <= 60 hari: H-60, H-30, dan saat lewat. Tiap tahap sekali. */
function kirim_pengingat_dokumen(): void
{
    try {
        $dok = db()->query(
            "SELECT d.id, d.user_id, d.jenis, d.judul, d.berlaku_sampai, u.nama_lengkap, u.role,
                    DATEDIFF(d.berlaku_sampai, CURDATE()) AS sisa
               FROM dokumen_karyawan d JOIN users u ON u.id = d.user_id
              WHERE d.berlaku_sampai IS NOT NULL AND u.is_active = 1
                AND d.berlaku_sampai <= CURDATE() + INTERVAL 60 DAY
                AND d.berlaku_sampai >= CURDATE() - INTERVAL 30 DAY"
        )->fetchAll();
        if (!$dok) {
            return;
        }
        $admin = id_admin_aktif();
        $catat = db()->prepare('INSERT IGNORE INTO pengingat_dokumen (dokumen_id, tahap) VALUES (?, ?)');
        $label = ['identitas' => 'Dokumen identitas', 'kontrak' => 'Dokumen kontrak', 'medis' => 'Dokumen medis',
                  'lisensi' => 'Lisensi', 'sertifikat' => 'Sertifikat', 'lainnya' => 'Dokumen'];
        foreach ($dok as $d) {
            $sisa = (int) $d['sisa'];
            $tahap = $sisa < 0 ? 'lewat' : ($sisa <= 30 ? 'H30' : 'H60');
            $catat->execute([(int) $d['id'], $tahap]);
            if ($catat->rowCount() !== 1) {
                continue;
            }
            $jenis = $label[$d['jenis']] ?? 'Dokumen';
            $kapan = $sisa < 0 ? 'sudah kedaluwarsa ' . (-$sisa) . ' hari' : ($sisa === 0 ? 'kedaluwarsa hari ini' : 'kedaluwarsa dalam ' . $sisa . ' hari');
            $pesan = $d['judul'] . ' milik ' . $d['nama_lengkap'] . ' berlaku sampai ' . tanggal_id((string) $d['berlaku_sampai']) . '. Siapkan perpanjangan dokumen.';
            kirim_notifikasi(array_values(array_diff($admin, [(int) $d['user_id']])), 'dokumen', $jenis . ' ' . $d['nama_lengkap'] . ' ' . $kapan,
                $pesan, 'detail_karyawan.php?id=' . (int) $d['user_id'] . '&tab=dokumen', true);
            kirim_notifikasi((int) $d['user_id'], 'dokumen', $jenis . ' Anda ' . $kapan,
                $d['judul'] . ' berlaku sampai ' . tanggal_id((string) $d['berlaku_sampai']) . '. Serahkan dokumen terbaru ke HR.',
                in_array((int) $d['user_id'], $admin, true) ? 'detail_karyawan.php?id=' . (int) $d['user_id'] . '&tab=dokumen' : 'profil.php', true);
        }
    } catch (PDOException $ex) {
        error_log('Pengingat dokumen gagal: ' . $ex->getMessage());
    }
}

/** Ringkasan untuk panel & halaman: [jumlah belum dibaca, daftar terbaru]. */
function notifikasi_terbaru(int $userId, int $batas = 8): array
{
    try {
        $st = db()->prepare('SELECT id, judul, pesan, tautan, kategori, prioritas, dibaca_pada, created_at
                               FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $batas));
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

/** Satu baris notifikasi (dipakai panel dropdown dan halaman notifikasi). */
function baris_notif(array $n, string $kembali, bool $ringkas): string
{
    $belum = $n['dibaca_pada'] === null;
    $id = (int) $n['id'];
    [$labelKat] = KATEGORI_NOTIF[$n['kategori']] ?? KATEGORI_NOTIF['sistem'];
    $waktuPenuh = hari_tanggal(substr((string) $n['created_at'], 0, 10)) . ', ' . jam((string) $n['created_at']);
    $penting = $n['prioritas'] === 'penting' ? '<span class="chip merah bertitik notif-penting">Penting</span>' : '';
    $tujuan = notif_tautan_aman($n['tautan']);
    $aksiForm = static function (string $aksi, string $ikonNama, string $judul, string $kelas = '') use ($id, $kembali, $ringkas, $n): string {
        return '<form method="post" action="notifikasi.php"' . ($ringkas ? ' data-notif-aksi' : '') . '>'
            . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
            . '<input type="hidden" name="aksi" value="' . $aksi . '"><input type="hidden" name="id" value="' . $id . '">'
            . '<input type="hidden" name="kembali" value="' . e($kembali) . '">'
            . '<button type="submit" class="tombol-ikon-kecil ' . $kelas . '" title="' . e($judul) . '" aria-label="' . e($judul) . ': ' . e((string) $n['judul']) . '">' . ikon($ikonNama) . '</button></form>';
    };

    $isi = ikon_notif((string) $n['kategori'])
        . '<span class="notif-teks"><b class="notif-judul">' . e((string) $n['judul']) . '</b>'
        . ($n['pesan'] !== null && $n['pesan'] !== '' ? '<span class="notif-pesan">' . e((string) $n['pesan']) . '</span>' : '')
        . '<small class="notif-meta">' . $penting . '<span>' . e($labelKat) . '</span><span aria-hidden="true">·</span>'
        . '<time datetime="' . e(str_replace(' ', 'T', (string) $n['created_at'])) . '" title="' . e($waktuPenuh) . '">' . e(waktu_notif((string) $n['created_at'])) . '</time></small></span>';

    $html = '<li class="notif-item' . ($belum ? ' belum' : '') . '"' . ($belum ? ' data-belum' : '') . '>'
        . '<a class="notif-tautan" href="' . e($tujuan !== null || $belum ? tautan_buka_notif($id) : 'notifikasi.php') . '">'
        . ($belum ? '<span class="sr">Belum dibaca: </span>' : '') . $isi . '</a>'
        . '<span class="notif-aksi">'
        . ($belum ? $aksiForm('baca', 'centang', 'Tandai dibaca') : $aksiForm('belum', 'titik-buka', 'Tandai belum dibaca'))
        . (!$ringkas ? $aksiForm('hapus', 'sampah', 'Hapus notifikasi', 'bahaya') : '')
        . '</span>'
        . ($belum ? '<span class="titik-belum" aria-hidden="true"></span>' : '')
        . '</li>';
    return $html;
}

/** Isi panel dropdown lonceng (juga dimuat ulang lewat notifikasi.php?panel=1). */
function isi_panel_notif(int $userId): string
{
    $jumlah = jumlah_notifikasi($userId);
    $daftar = notifikasi_terbaru($userId, 8);
    $kembali = notif_alamat_kini();
    $urung = $_SESSION['notif_urung'] ?? null;
    $bisaUrung = is_array($urung) && ($urung['waktu'] ?? 0) > time() - 120 && !empty($urung['ids']);
    $belumTampil = count(array_filter($daftar, static fn($n) => $n['dibaca_pada'] === null));

    $h = '<div class="pn-kepala"><h2 id="judulPanelNotif">Notifikasi</h2>'
        . ($jumlah > 0 ? '<span class="pn-baru">' . $jumlah . ' baru</span>' : '')
        . ($jumlah > 0
            ? '<form method="post" action="notifikasi.php" data-notif-aksi class="pn-semua"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
              . '<input type="hidden" name="aksi" value="baca_semua"><input type="hidden" name="kembali" value="' . e($kembali) . '">'
              . '<button type="submit" class="tombol-teks">' . ikon('centang-ganda') . 'Tandai semua dibaca</button></form>'
            : '')
        . '</div>';
    $h .= '<div class="pn-tab" role="tablist" aria-label="Saring notifikasi">'
        . '<button type="button" role="tab" data-pn-tab="semua" aria-selected="true">Semua</button>'
        . '<button type="button" role="tab" data-pn-tab="belum" aria-selected="false">Belum dibaca' . ($jumlah > 0 ? ' <span class="hitung">' . ($jumlah > 99 ? '99+' : $jumlah) . '</span>' : '') . '</button>'
        . '</div>';
    if ($bisaUrung) {
        $h .= '<div class="pn-urung" role="status"><span>' . count($urung['ids']) . ' notifikasi ditandai dibaca.</span>'
            . '<form method="post" action="notifikasi.php" data-notif-aksi><input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
            . '<input type="hidden" name="aksi" value="urungkan"><input type="hidden" name="kembali" value="' . e($kembali) . '">'
            . '<button type="submit" class="tombol-teks">' . ikon('urung') . 'Urungkan</button></form></div>';
    }
    $h .= '<ul class="pn-daftar">';
    foreach ($daftar as $n) {
        $h .= baris_notif($n, $kembali, true);
    }
    $h .= '</ul>';
    $kosongSemua = !$daftar;
    $h .= '<div class="pn-kosong" data-pn-kosong="semua"' . ($kosongSemua ? '' : ' hidden') . '>' . ikon('lonceng') . '<b>Belum ada notifikasi</b><span>Kabar soal kontrak, gaji, jadwal, dan pengajuan akan muncul di sini.</span></div>';
    $h .= '<div class="pn-kosong" data-pn-kosong="belum" hidden>' . ikon('centang-ganda') . '<b>Semua sudah dibaca</b><span>'
        . ($jumlah > $belumTampil ? 'Masih ada ' . ($jumlah - $belumTampil) . ' notifikasi lama yang belum dibaca di halaman notifikasi.' : 'Tidak ada notifikasi baru untuk Anda.') . '</span></div>';
    $h .= '<div class="pn-kaki"><a href="notifikasi.php">Lihat semua notifikasi' . ikon('panah-kanan') . '</a>'
        . '<a class="pn-atur" href="notifikasi.php#pengaturan" title="Pengaturan notifikasi" aria-label="Pengaturan notifikasi">' . ikon('gerigi') . '</a></div>';
    return $h;
}

/** Tombol lonceng + panel dropdown untuk bilah atas. */
function lonceng_notifikasi(int $userId): string
{
    $jumlah = jumlah_notifikasi($userId);
    return '<div class="notif">'
        . '<button type="button" class="tombol-ikon" id="tombolNotif" aria-haspopup="dialog" aria-expanded="false" aria-controls="panelNotif"'
        . ' aria-label="Notifikasi' . ($jumlah > 0 ? ', ' . $jumlah . ' belum dibaca' : '') . '" data-jumlah="' . $jumlah . '">'
        . ikon('lonceng')
        . ($jumlah > 0 ? '<span class="lencana">' . ($jumlah > 99 ? '99+' : $jumlah) . '</span>' : '')
        . '</button>'
        . '<div class="panel-notif" id="panelNotif" role="dialog" aria-labelledby="judulPanelNotif" hidden>' . isi_panel_notif($userId) . '</div>'
        . '</div>';
}
