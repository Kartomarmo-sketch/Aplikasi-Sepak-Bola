<?php
declare(strict_types=1);

// Kerangka halaman bersama (bilah atas, sidebar, menu akun) dan fungsi bantu tampilan.
// Pemakaian di halaman terlindungi:
//   require __DIR__ . '/tampilan.php';
//   start_secure_session(); require_login();
//   ... proses data ...
//   halaman_awal('Judul', 'Beranda');   // cetak <head>, bilah atas, sidebar
//   ... isi halaman ...
//   halaman_akhir();                    // cetak penutup + JavaScript

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib_notifikasi.php';

const NAMA_KLUB   = 'POHON BERINGIN FC';
const GAMBAR_LOGO = 'erasebg-transformed.png';
const ZONA_WAKTU  = 'Asia/Jakarta';

const NAMA_HARI  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const NAMA_BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Submenu "Performa & Medis" (sama untuk semua peran; isi halaman menyesuaikan peran).
const MENU_PERFORMA = [
    'Statistik Pemain' => 'statistik.php',
    'Riwayat Cedera'   => 'cedera.php',
    'Kebugaran Fisik'  => 'kebugaran.php',
    'Program Latihan'  => 'latihan.php',
];

const MENU_KONTRAK = ['Kontrak Saya' => 'kontrak.php', 'Riwayat Kontrak' => 'kontrak.php?hal=riwayat'];
const MENU_GAJI    = ['Slip Gaji' => 'gaji.php'];
const MENU_JADWAL  = [
    'Kalender'            => 'jadwal.php',
    'Jadwal Latihan'      => 'jadwal.php?hal=latihan',
    'Jadwal Pertandingan' => 'jadwal.php?hal=pertandingan',
    'Rapat & Tandang'     => 'jadwal.php?hal=rapat',
];
const MENU_PENGAJUAN = [
    'Pengajuan Cuti' => 'pengajuan.php',
    'Pengajuan Izin' => 'pengajuan.php?hal=izin',
    'Kehadiran'      => 'kehadiran.php',
];

// Menu sidebar per peran: label => tujuan, atau label => [submenu]. '#' = halaman belum dibuat.
// Kontrak, gaji, jadwal, dan pengajuan berlaku untuk semua karyawan klub (pemain maupun staf).
const MENU_PER_PERAN = [
    'admin' => [
        'Beranda' => 'dashboard.php',
        'Data Pemain' => 'data_pemain.php', 'Data Staf' => 'data_staf.php',
        'Kontrak' => [
            'Daftar Kontrak' => 'kelola_kontrak.php', 'Riwayat Semua Kontrak' => 'kelola_kontrak.php?hal=riwayat',
            'Kontrak Hampir Habis' => 'kelola_kontrak.php?hal=hampir', 'Kontrak Saya' => 'kontrak.php',
        ],
        'Penggajian' => [
            'Data Penggajian' => 'kelola_gaji.php', 'Slip Gaji' => 'gaji.php', 'Ringkasan Gaji' => 'ringkasan_gaji.php',
        ],
        'Performa & Medis' => MENU_PERFORMA,
        'Jadwal' => MENU_JADWAL,
        'Pengajuan' => MENU_PENGAJUAN,
        'Laporan' => '#',
    ],
    'pelatih_medis' => [
        'Beranda' => 'dashboard.php',
        'Kontrak' => MENU_KONTRAK,
        'Penggajian' => MENU_GAJI,
        'Performa & Medis' => MENU_PERFORMA,
        'Jadwal' => MENU_JADWAL,
        'Pengajuan' => MENU_PENGAJUAN,
    ],
    'pemain' => [
        'Beranda' => 'dashboard.php',
        'Kontrak' => MENU_KONTRAK,
        'Penggajian' => MENU_GAJI,
        'Performa & Medis' => MENU_PERFORMA,
        'Jadwal' => MENU_JADWAL,
        'Pengajuan' => MENU_PENGAJUAN,
    ],
];

// Peran yang boleh melihat data seluruh pemain.
const PERAN_STAF = ['admin', 'pelatih_medis'];

const LABEL_POSISI = ['kiper' => 'Kiper', 'bek' => 'Bek', 'gelandang' => 'Gelandang', 'penyerang' => 'Penyerang'];

// ---------- Fungsi bantu ----------

function angka(mixed $v): string
{
    return number_format((int) $v, 0, ',', '.');
}

/** Huruf awal nama (maks. 2) untuk avatar. */
function inisial(string $nama): string
{
    $kata = preg_split('/\s+/u', trim($nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= function_exists('mb_substr') ? mb_substr($k, 0, 1, 'UTF-8') : substr($k, 0, 1);
    }
    $hasil = function_exists('mb_strtoupper') ? mb_strtoupper($hasil, 'UTF-8') : strtoupper($hasil);
    return $hasil !== '' ? $hasil : '?';
}

/** "22 Apr 1998" (pendek) atau "22 April 1998" (panjang). Kosong bila tanggal tidak valid. */
function tanggal_id(?string $tgl, bool $panjang = false): string
{
    if ($tgl === null || $tgl === '' || str_starts_with($tgl, '0000')) {
        return '';
    }
    try {
        $d = new DateTimeImmutable($tgl);
    } catch (Exception) {
        return '';
    }
    $bulan = NAMA_BULAN[(int) $d->format('n')];
    return $d->format('j') . ' ' . ($panjang ? $bulan : mb_substr($bulan, 0, 3)) . ' ' . $d->format('Y');
}

/** "Rp 2,5 M", "Rp 750 jt", "Rp 12.000". */
function rupiah_ringkas(mixed $v): string
{
    $n = (float) $v;
    $fmt = static fn(float $x): string => rtrim(rtrim(number_format($x, 2, ',', '.'), '0'), ',');
    if ($n >= 1_000_000_000) {
        return 'Rp ' . $fmt($n / 1_000_000_000) . ' M';
    }
    if ($n >= 1_000_000) {
        return 'Rp ' . $fmt($n / 1_000_000) . ' jt';
    }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

/** Ikon menu (SVG stroke) berdasarkan kata di label. */
function ikon_menu(string $label): string
{
    $ikon = [
        'rumah'   => '<path d="M3 11.5 12 4l9 7.500V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'dokumen' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'uang'    => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 9v.01M17.5 15v.01"/>',
        'grafik'  => '<path d="M4 20V11M10 20V4M16 20v-6M2 20h20"/>',
        'kalender'=> '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'kirim'   => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
        'orang'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6M17 4.5a3.5 3.5 0 0 1 0 7M22 20c0-2.5-1.5-4.5-4-5.5"/>',
        'medis'   => '<path d="M12 21s-7-4.6-9-9.5C1.7 8 4 5 7 5c2 0 3.5 1.2 5 3 1.5-1.8 3-3 5-3 3 0 5.300 3 4 6.500C19 16.400 12 21 12 21z"/>',
        'aktivitas' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'titik'   => '<circle cx="12" cy="12" r="3"/>',
    ];
    $peta = [
        'Performa' => 'aktivitas',
        'Beranda' => 'rumah',
        'Kontrak' => 'dokumen', 'Dokumen' => 'dokumen', 'Audit' => 'dokumen',
        'Gaji' => 'uang', 'Penggajian' => 'uang',
        'Statistik' => 'grafik', 'Laporan' => 'grafik',
        'Jadwal' => 'kalender', 'Kehadiran' => 'kalender', 'Latihan' => 'kalender',
        'Pengajuan' => 'kirim', 'Persetujuan' => 'kirim',
        'Data' => 'orang', 'User' => 'orang', 'Profil' => 'orang',
        'Cedera' => 'medis',
    ];
    foreach ($peta as $kata => $nama) {
        if (str_contains($label, $kata)) {
            return $ikon[$nama];
        }
    }
    return $ikon['titik'];
}

/** "Rp 116.000.000" (lengkap, untuk slip & kontrak). */
function rupiah(mixed $v): string
{
    $n = (float) $v;
    return ($n < 0 ? '- ' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
}

/** "Oktober 2026" dari tanggal YYYY-MM-DD. */
function bulan_tahun(string $tgl): string
{
    $d = new DateTimeImmutable(substr($tgl, 0, 10));
    return NAMA_BULAN[(int) $d->format('n')] . ' ' . $d->format('Y');
}

/** "Senin, 2 Nov 2026". */
function hari_tanggal(string $tgl, bool $panjang = false): string
{
    $d = new DateTimeImmutable(substr($tgl, 0, 10));
    return NAMA_HARI[(int) $d->format('w')] . ', ' . tanggal_id($d->format('Y-m-d'), $panjang);
}

/** "19:00" dari DATETIME. */
function jam(?string $waktu): string
{
    return $waktu ? substr($waktu, 11, 5) : '';
}

/** "3 hari lalu", "kemarin", "hari ini" dari DATETIME. */
function waktu_relatif(string $waktu): string
{
    $hari = selisih_hari(substr($waktu, 0, 10));
    return match (true) {
        $hari <= 0  => 'hari ini',
        $hari === 1 => 'kemarin',
        $hari < 30  => $hari . ' hari lalu',
        default     => tanggal_id(substr($waktu, 0, 10)),
    };
}

/** Simpan pesan sekali tampil (tipe: sukses | galat | info). */
function flash_set(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_ambil(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($f) ? $f : null;
}

/** Jejak audit; kegagalan mencatat tidak boleh mengganggu halaman. */
function catat_aktivitas(string $aksi, ?string $entitas = null, ?int $entitasId = null, ?string $detail = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO log_aktivitas (user_id, aksi, entitas, entitas_id, detail, ip_address)
             VALUES (:u, :a, :e, :i, :d, :ip)'
        );
        $stmt->execute([
            ':u'  => $_SESSION['user_id'] ?? null,
            ':a'  => $aksi,
            ':e'  => $entitas,
            ':i'  => $entitasId,
            ':d'  => $detail !== null ? mb_substr($detail, 0, 500) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (PDOException $ex) {
        error_log('Log aktivitas gagal: ' . $ex->getMessage());
    }
}

/** Jalur foto yang aman ditampilkan (hanya uploads/foto/*, file harus ada), atau null. */
function jalur_foto(?string $jalur): ?string
{
    if ($jalur === null || !preg_match('#^uploads/foto/[A-Za-z0-9_.-]+$#', $jalur)) {
        return null;
    }
    return is_file(__DIR__ . '/' . $jalur) ? $jalur : null;
}

/** Tabel profil tempat foto & kontak pengguna disimpan menurut peran. */
function tabel_profil(string $role): string
{
    return $role === 'pemain' ? 'profil_pemain' : 'staf';
}

function foto_pengguna(int $userId, string $role): ?string
{
    try {
        // Nama tabel berasal dari daftar tetap di atas, bukan dari masukan pengguna.
        $stmt = db()->prepare('SELECT foto FROM ' . tabel_profil($role) . ' WHERE user_id = :id');
        $stmt->execute([':id' => $userId]);
        $foto = $stmt->fetchColumn();
        return jalur_foto(is_string($foto) ? $foto : null);
    } catch (PDOException) {
        return null;
    }
}

function jumlah_notifikasi(int $userId): int
{
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifikasi WHERE user_id = :id AND dibaca_pada IS NULL');
        $stmt->execute([':id' => $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/** Isi avatar: foto bila ada, jika tidak huruf inisial. */
function avatar_isi(string $nama, ?string $foto): string
{
    return $foto !== null
        ? '<img src="' . e($foto) . '" alt="">'
        : e(inisial($nama));
}

function versi_aset(string $berkas): string
{
    $waktu = @filemtime(__DIR__ . '/' . $berkas);
    return e($berkas) . '?v=' . ($waktu !== false ? $waktu : '1');
}

// ---------- Fungsi bantu halaman Performa & Medis ----------

/** Ikon SVG siap cetak (garis 24x24). */
function ikon(string $nama, string $kelas = 'ik'): string
{
    $jalur = [
        'trofi'     => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
        'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
        'bintang'   => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9z"/>',
        'stopwatch' => '<circle cx="12" cy="14" r="7"/><path d="M12 10.5V14l2.5 2M10 2.5h4M12 2.5V7M18.5 6.5 20 5"/>',
        'jantung'   => '<path d="M12 20s-8-4.7-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.3-8 11-8 11z"/><path d="M5 12h3.5l1.5-2.5 2.5 5 1.5-2.5H19"/>',
        'aktivitas' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'beban'     => '<path d="M6.5 6.5v11M17.5 6.5v11M3.5 9.5v5M20.5 9.5v5M6.5 12h11"/>',
        'kilat'     => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'angin'     => '<path d="M3 8h10a3 3 0 1 0-3-3M3 12h15a3 3 0 1 1-3 3M3 16h6"/>',
        'naik'      => '<path d="m3 17 6-6 4 4 8-8M15 7h6v6"/>',
        'kalender'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'jam'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'kiri'      => '<path d="m15 18-6-6 6-6"/>',
        'kanan'     => '<path d="m9 18 6-6-6-6"/>',
        'bawah'     => '<path d="m6 9 6 6 6-6"/>',
        'cari'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'perisai'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'orang'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6M17 4.5a3.5 3.5 0 0 1 0 7M22 20c0-2.5-1.5-4.5-4-5.5"/>',
        'urut'      => '<path d="M8 5v14M4.5 8.5 8 5l3.5 3.5M16 19V5M12.5 15.5 16 19l3.5-3.5"/>',
        'meteran'   => '<path d="M4 18a8 8 0 1 1 16 0"/><path d="m12 18 4-5"/>',
        'api'       => '<path d="M12 22c4 0 7-3 7-7 0-5-4-7-5-12-3 2-6 6-6 9-1-1-1.5-2-1.5-3.5C5 10 5 12 5 15c0 4 3 7 7 7z"/>',
        'daftar'    => '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'kartu'     => '<rect x="6" y="3" width="12" height="18" rx="2"/>',
        'tanda'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'medis'     => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M12 10.5v6M9 13.5h6"/>',
        'dokumen'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'pena'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L13 14l-4 1 1-4z"/>',
        'riwayat'   => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'uang'      => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 9v.01M17.5 15v.01"/>',
        'unduh'     => '<path d="M12 3v12M7 10l5 5 5-5M4 19h16"/>',
        'mata'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'tambah'    => '<path d="M12 5v14M5 12h14"/>',
        'silang'    => '<path d="M6 6l12 12M18 6 6 18"/>',
        'kirim'     => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
        'papan'     => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 10h6M9 14h6M9 18h3"/>',
        'centang-kal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15.5l2 2 4-4"/>',
        'pin'       => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'bus'       => '<rect x="4" y="3" width="16" height="14" rx="2"/><path d="M4 11h16M8 21v-4M16 21v-4M7.5 14h.01M16.5 14h.01"/>',
        'rapat'     => '<circle cx="12" cy="7" r="3"/><circle cx="5" cy="10" r="2"/><circle cx="19" cy="10" r="2"/><path d="M7 20c0-3 2.2-5 5-5s5 2 5 5M1.5 19c.3-2 1.7-3.5 3.5-3.5M22.5 19c-.3-2-1.7-3.5-3.5-3.5"/>',
        'peluit'    => '<circle cx="9" cy="14" r="6"/><path d="M14 10l7-5M9 14h.01"/>',
        'kotak-masuk' => '<path d="M3 13h5l2 3h4l2-3h5"/><path d="M5 5h14l2 8v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6z"/>',
        'peringatan' => '<path d="M12 8v5M12 16.5v.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'grafik-batang' => '<path d="M4 20V10M10 20V4M16 20v-8M2 20h20"/>',
        'kamera'    => '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'sinkron'   => '<path d="M21 12a9 9 0 0 1-15.5 6.2M3 12A9 9 0 0 1 18.5 5.8"/><path d="M21 4v5h-5M3 20v-5h5"/>',
        'lonceng'   => '<path d="M6 10a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 16 6 10Z"/><path d="M10 21a2.2 2.2 0 0 0 4 0"/>',
        'lonceng-mati' => '<path d="M8.7 3.9A6 6 0 0 1 18 10c0 2.4.4 4.1.9 5.3M17 17.5H3.5S6 16 6 10c0-.6.1-1.2.3-1.8"/><path d="M10 21a2.2 2.2 0 0 0 4 0M3 3l18 18"/>',
        'centang'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'centang-ganda' => '<path d="m2 12.5 4.5 4.5L16 7.5M12 16l1 1 9.5-9.5"/>',
        'titik-buka' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor"/>',
        'sampah'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/>',
        'gerigi'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'urung'     => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        'panah-kanan' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    return '<svg class="' . e($kelas) . '" viewBox="0 0 24 24" aria-hidden="true">' . ($jalur[$nama] ?? $jalur['info']) . '</svg>';
}

function hari_ini(): DateTimeImmutable
{
    return new DateTimeImmutable('today', new DateTimeZone(ZONA_WAKTU));
}

/** Jumlah hari dari $dari ke $ke (YYYY-MM-DD); $ke null = hari ini. Negatif bila $ke lebih awal. */
function selisih_hari(string $dari, ?string $ke = null): int
{
    $zona = new DateTimeZone(ZONA_WAKTU);
    $a = new DateTimeImmutable(substr($dari, 0, 10), $zona);
    $b = $ke !== null ? new DateTimeImmutable(substr($ke, 0, 10), $zona) : hari_ini();
    return (int) $a->diff($b)->format('%r%a');
}

/** Awal musim berjalan (1 Juli). */
function awal_musim(): string
{
    $h = hari_ini();
    $tahun = (int) $h->format('n') >= 7 ? (int) $h->format('Y') : (int) $h->format('Y') - 1;
    return $tahun . '-07-01';
}

/**
 * Saldo cuti tahun berjalan dari kontrak aktif.
 * ['hak' => 12, 'terpakai' => 3, 'menunggu' => 2, 'sisa' => 7, 'tahun' => 2026]
 * Cuti yang menyeberang tahun hanya dihitung bagian di tahun ini.
 */
function saldo_cuti(int $userId): array
{
    $tahun = (int) hari_ini()->format('Y');
    $awal  = $tahun . '-01-01';
    $akhir = $tahun . '-12-31';
    $stmt = db()->prepare("SELECT hak_cuti_tahunan FROM kontrak WHERE user_id = :u AND status = 'aktif' ORDER BY tanggal_mulai DESC LIMIT 1");
    $stmt->execute([':u' => $userId]);
    $hak = (int) ($stmt->fetchColumn() ?: 12);

    $stmt = db()->prepare(
        "SELECT status, SUM(DATEDIFF(LEAST(tanggal_selesai, :akhir), GREATEST(tanggal_mulai, :awal)) + 1) AS hari
           FROM pengajuan
          WHERE user_id = :u AND jenis = 'cuti' AND status IN ('disetujui','menunggu')
            AND tanggal_selesai >= :awal2 AND tanggal_mulai <= :akhir2
          GROUP BY status"
    );
    $stmt->execute([':u' => $userId, ':awal' => $awal, ':akhir' => $akhir, ':awal2' => $awal, ':akhir2' => $akhir]);
    $hari = ['disetujui' => 0, 'menunggu' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $hari[$r['status']] = (int) $r['hari'];
    }
    return [
        'hak' => $hak, 'terpakai' => $hari['disetujui'], 'menunggu' => $hari['menunggu'],
        'sisa' => max(0, $hak - $hari['disetujui'] - $hari['menunggu']), 'tahun' => $tahun,
    ];
}

function adalah_staf(): bool
{
    return in_array($_SESSION['role'] ?? '', PERAN_STAF, true);
}

/** Desimal gaya Indonesia: 7,9 / 0,66. */
function desimal(mixed $v, int $digit = 1): string
{
    return number_format((float) $v, $digit, ',', '.');
}

/** URL halaman ini dengan parameter GET diubah (null/'' = dihapus). */
function url_dengan(array $ubah): string
{
    $param = array_merge($_GET, $ubah);
    $param = array_filter($param, static fn($v) => $v !== null && $v !== '' && !is_array($v));
    $dasar = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'));
    return $dasar . ($param ? '?' . http_build_query($param) : '');
}

/** Semua pemain (untuk pemilih pemain & tabel skuad). */
function daftar_pemain(): array
{
    $sql = "SELECT u.id, u.nama_lengkap, p.nomor_punggung, pr.posisi, pr.status, pr.foto
              FROM users u
              LEFT JOIN pemain p         ON p.user_id = u.id
              LEFT JOIN profil_pemain pr ON pr.user_id = u.id
             WHERE u.role = 'pemain' AND u.is_active = 1
             ORDER BY p.nomor_punggung IS NULL, p.nomor_punggung, u.nama_lengkap";
    return db()->query($sql)->fetchAll();
}

/**
 * Pemain yang sedang dilihat.
 * - Pemain: selalu dirinya sendiri (parameter ?pemain diabaikan, tidak bisa mengintip data orang lain).
 * - Staf: ?pemain=ID, atau null = tampilan seluruh skuad.
 */
function pemain_target(): ?array
{
    if (adalah_staf()) {
        $id = filter_input(INPUT_GET, 'pemain', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            return null;
        }
    } elseif (($_SESSION['role'] ?? '') === 'pemain') {
        $id = (int) $_SESSION['user_id'];
    } else {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT u.id, u.nama_lengkap, p.nomor_punggung, pr.posisi, pr.posisi_detail, pr.status, pr.foto
           FROM users u
           LEFT JOIN pemain p         ON p.user_id = u.id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id
          WHERE u.id = :id AND u.role = 'pemain'"
    );
    $stmt->execute([':id' => $id]);
    $baris = $stmt->fetch();
    if (!$baris && !adalah_staf()) {
        // Pemain tanpa data pemain tidak boleh jatuh ke tampilan "seluruh skuad".
        http_response_code(403);
        exit('Data pemain tidak ditemukan.');
    }
    return $baris ?: null;
}

/** "#7 · Gelandang (Sayap kanan)" */
function keterangan_pemain(array $p): string
{
    $bagian = [];
    if ($p['nomor_punggung'] !== null) {
        $bagian[] = '#' . $p['nomor_punggung'];
    }
    if (!empty($p['posisi'])) {
        $pos = LABEL_POSISI[$p['posisi']] ?? $p['posisi'];
        if (!empty($p['posisi_detail'])) {
            $pos .= ' (' . $p['posisi_detail'] . ')';
        }
        $bagian[] = $pos;
    }
    return implode(' · ', $bagian);
}

/** Judul halaman dengan ikon + slot kanan (filter). */
function kepala_halaman(string $ikon, string $judul, string $sub, string $kanan = ''): void
{
    ?>
            <div class="judul-halaman">
                <span class="ikon-judul"><?= ikon($ikon) ?></span>
                <div>
                    <h1><?= e($judul) ?></h1>
                    <p><?= e($sub) ?></p>
                </div>
                <?php if ($kanan !== ''): ?><div class="aksi-judul"><?= $kanan ?></div><?php endif; ?>
            </div>
    <?php
}

/**
 * Formulir filter GET yang terkirim otomatis saat pilihan berubah.
 * $pilihan: [nama => ['label' => ..., 'opsi' => [nilai => teks], 'nilai' => terpilih]]
 */
function filter_get(array $pilihan, array $tersembunyi = []): string
{
    $html = '<form method="get" class="filter-get" data-kirim-otomatis>';
    foreach ($tersembunyi as $nama => $nilai) {
        $html .= '<input type="hidden" name="' . e((string) $nama) . '" value="' . e((string) $nilai) . '">';
    }
    foreach ($pilihan as $nama => $p) {
        $html .= '<label class="pilih"><span class="sr">' . e($p['label']) . '</span><select name="' . e($nama) . '" aria-label="' . e($p['label']) . '">';
        foreach ($p['opsi'] as $nilai => $teks) {
            $pilih = (string) $nilai === (string) ($p['nilai'] ?? '') ? ' selected' : '';
            $html .= '<option value="' . e((string) $nilai) . '"' . $pilih . '>' . e($teks) . '</option>';
        }
        $html .= '</select>' . ikon('bawah', 'ik panah-pilih') . '</label>';
    }
    return $html . '<noscript><button type="submit" class="tombol kecil">Terapkan</button></noscript></form>';
}

/** Opsi pemilih pemain untuk staf: '' = semua pemain. */
function opsi_pemain(array $daftar): array
{
    $opsi = ['' => 'Semua pemain'];
    foreach ($daftar as $p) {
        $opsi[$p['id']] = ($p['nomor_punggung'] !== null ? '#' . $p['nomor_punggung'] . ' ' : '') . $p['nama_lengkap'];
    }
    return $opsi;
}

/** Kartu ringkas angka utama. $warna: hijau | biru | ungu | kuning | merah. */
function kartu_kpi(string $label, string $nilai, string $ikon, string $warna = 'hijau', string $sub = '', string $kelasSub = ''): string
{
    return '<div class="kpi ' . e($warna) . '">'
        . '<div class="kpi-teks"><span class="kpi-label">' . e($label) . '</span>'
        . '<b class="kpi-nilai' . (mb_strlen($nilai) > 11 ? ' panjang' : '') . '">' . e($nilai) . '</b>'
        . ($sub !== '' ? '<span class="kpi-sub ' . e($kelasSub) . '">' . $sub . '</span>' : '')
        . '</div><span class="kpi-ikon">' . ikon($ikon) . '</span></div>';
}

/** Chip rating ala aplikasi statistik: hijau >= 7, biru >= 6.5, kuning >= 6, merah < 6. */
function chip_rating(mixed $r): string
{
    if ($r === null || $r === '') {
        return '<span class="chip netral">-</span>';
    }
    $r = (float) $r;
    $kelas = $r >= 7 ? 'hijau' : ($r >= 6.5 ? 'biru' : ($r >= 6 ? 'kuning' : 'merah'));
    return '<span class="chip ' . $kelas . '">' . desimal($r) . '</span>';
}

/** Perubahan dibanding nilai sebelumnya. $lebihKecilLebihBaik untuk waktu sprint, lemak, dst. */
function selisih(?float $kini, ?float $lalu, int $digit = 1, string $satuan = '', bool $lebihKecilLebihBaik = false,
                 string $pembanding = 'dari tes lalu'): string
{
    if ($kini === null || $lalu === null) {
        return '<span class="tren datar">Belum ada pembanding</span>';
    }
    $d = round($kini - $lalu, $digit);
    if ($d == 0) {
        return '<span class="tren datar">Sama dengan tes sebelumnya</span>';
    }
    $baik = $lebihKecilLebihBaik ? $d < 0 : $d > 0;
    $tanda = $d > 0 ? '▲ +' : '▼ ';
    return '<span class="tren ' . ($baik ? 'baik' : 'buruk') . '">' . $tanda . desimal($d, $digit) . $satuan
        . '</span> <span class="tren datar">' . e($pembanding) . '</span>';
}

/** Badge status pemain dari profil_pemain.status. */
function badge_status_pemain(?string $status): string
{
    $peta = [
        'aktif'       => ['Tersedia', ''],
        'cedera'      => ['Cedera', 'merah'],
        'diskors'     => ['Diskors', 'kuning'],
        'dipinjamkan' => ['Dipinjamkan', 'biru'],
        'pensiun'     => ['Pensiun', 'netral'],
    ];
    [$teks, $kelas] = $peta[$status ?? 'aktif'] ?? ['Tidak diketahui', 'netral'];
    return '<span class="badge ' . $kelas . '">' . e($teks) . '</span>';
}

/** Kartu identitas pemain yang sedang dilihat staf + tautan kembali ke seluruh skuad. */
function strip_pemain(array $p): string
{
    return '<div class="strip-pemain">'
        . '<a class="tombol kecil" href="' . e(url_dengan(['pemain' => null])) . '">' . ikon('kiri') . 'Semua pemain</a>'
        . avatar_kecil((string) $p['nama_lengkap'], $p['foto'] ?? null)
        . '<div class="strip-teks"><b>' . e((string) $p['nama_lengkap']) . '</b><small>' . e(keterangan_pemain($p)) . '</small></div>'
        . badge_status_pemain($p['status'] ?? null)
        . '</div>';
}

/** Avatar kecil pemain (foto atau inisial). */
function avatar_kecil(string $nama, ?string $foto): string
{
    return '<span class="avatar-kecil" aria-hidden="true">' . avatar_isi($nama, jalur_foto($foto)) . '</span>';
}

/**
 * Grafik garis satu seri; digambar oleh assets/app.js sesuai lebar layar.
 * $titik: [['x' => label sumbu, 'y' => nilai, 't' => teks tooltip], ...]
 */
function grafik_garis(array $titik, float $min, float $max, array $garisBantu, string $label, int $digit = 0): string
{
    if ($titik === []) {
        return '<div class="kosong kecil">Belum ada data untuk grafik.</div>';
    }
    $data = ['titik' => array_values($titik), 'min' => $min, 'max' => $max, 'bantu' => $garisBantu, 'digit' => $digit];
    return '<div class="grafik" tabindex="0" role="img" aria-label="' . e($label) . '" data-grafik="'
        . e((string) json_encode($data, JSON_UNESCAPED_UNICODE)) . '"></div>';
}

/** Bagian atas tabel data: kotak cari + filter (diproses assets/app.js). */
function alat_tabel(string $placeholder = 'Cari...', array $saring = [], string $cariAwal = ''): string
{
    $html = '<div class="alat-tabel"><label class="kolom-cari">' . ikon('cari')
        . '<input type="search" placeholder="' . e($placeholder) . '" aria-label="' . e($placeholder) . '" value="' . e($cariAwal) . '" data-cari></label>';
    foreach ($saring as $kunci => $s) {
        $html .= '<label class="pilih"><select data-saring="' . e($kunci) . '" aria-label="' . e($s['label']) . '">'
            . '<option value="">' . e($s['label']) . ': Semua</option>';
        foreach ($s['opsi'] as $nilai => $teks) {
            $html .= '<option value="' . e((string) $nilai) . '">' . e($s['label']) . ': ' . e($teks) . '</option>';
        }
        $html .= '</select>' . ikon('bawah', 'ik panah-pilih') . '</label>';
    }
    return $html . '</div>';
}

/** Bagian bawah tabel data: info baris, jumlah per halaman, navigasi halaman. */
function kaki_tabel(): string
{
    return '<p class="tabel-kosong" data-kosong hidden>Tidak ada data yang cocok dengan pencarian.</p>'
        . '<div class="kaki-tabel"><div class="info-baris"><span data-info></span>'
        . '<label class="pilih kecil"><select data-per-halaman aria-label="Baris per halaman">'
        . '<option>10</option><option>25</option><option>50</option></select>' . ikon('bawah', 'ik panah-pilih') . '</label></div>'
        . '<nav class="halaman" aria-label="Halaman tabel" data-halaman></nav></div>';
}

/** Kepala kolom yang bisa diurutkan. $jenis: teks | angka. $awal: asc | desc untuk urutan bawaan. */
function th_urut(string $label, string $jenis = 'teks', string $awal = ''): string
{
    return '<th scope="col" data-urut="' . e($jenis) . '"' . ($awal !== '' ? ' data-urut-awal="' . e($awal) . '"' : '')
        . ' aria-sort="none"><button type="button">' . e($label) . ikon('urut', 'ik ik-urut') . '</button></th>';
}

// ---------- Kerangka halaman ----------

function halaman_awal(string $judul, string $menuAktif = ''): void
{
    $role  = (string) $_SESSION['role'];
    $nama  = (string) $_SESSION['nama_lengkap'];
    $menu  = MENU_PER_PERAN[$role] ?? [];
    $label = ROLE_LABELS[$role] ?? $role;
    $foto  = foto_pengguna((int) $_SESSION['user_id'], $role);
    jalankan_pengingat();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> | <?= e(NAMA_KLUB) ?></title>

    <!-- Pasang tema sebelum halaman tampil agar tidak berkedip (sama dengan login) -->
    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('tema'); } catch (e) {}
            if (tema !== 'gelap' && tema !== 'terang') {
                tema = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'gelap' : 'terang';
            }
            document.documentElement.setAttribute('data-tema', tema);
        })();
    </script>
    <link rel="stylesheet" href="<?= versi_aset('assets/app.css') ?>">
</head>
<body class="menu-tutup">

    <header class="bilah-atas">
        <a class="merek" href="dashboard.php" title="Ke dashboard">
            <img class="logo" src="<?= e(GAMBAR_LOGO) ?>" alt="Logo <?= e(NAMA_KLUB) ?>">
            <span class="teks-merek">
                <span class="nama-klub"><?= e(NAMA_KLUB) ?></span>
            </span>
        </a>

        <button type="button" class="tombol-ikon tombol-menu" id="tombolMenu"
                aria-label="Buka atau tutup menu" aria-controls="sidebar" aria-expanded="false">
            <svg class="ik" viewBox="0 0 24 24" style="stroke-width:2.6" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <div class="kanan">
            <button type="button" class="tombol-ikon" id="tombolTema" aria-label="Ganti ke tema gelap">
                <!-- Matahari: tampil di tema terang -->
                <svg class="ik ikon-matahari" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4.5" fill="#ffd54a" stroke="#ffd54a"/>
                    <path stroke="#ffd54a" d="M12 1.5v2.5M12 20v2.5M1.5 12H4M20 12h2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/>
                </svg>
                <!-- Bulan: tampil di tema gelap -->
                <svg class="ik ikon-bulan" viewBox="0 0 24 24" fill="#e8eefc" stroke="#e8eefc" stroke-width="1.5" aria-hidden="true">
                    <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>
                </svg>
            </button>

            <?= lonceng_notifikasi((int) $_SESSION['user_id']) ?>

            <span class="pemisah" aria-hidden="true"></span>

            <div class="akun">
                <button type="button" class="chip-akun" id="tombolAkun"
                        aria-label="Menu akun" aria-haspopup="true" aria-expanded="false" aria-controls="menuAkun">
                    <span class="inisial" aria-hidden="true"><?= avatar_isi($nama, $foto) ?></span>
                    <span class="chip-teks"><b><?= e($nama) ?></b><small><?= e($label) ?></small></span>
                    <svg class="ik panah" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="menu-akun" id="menuAkun" hidden>
                    <div class="profil-mini">
                        <span class="inisial" aria-hidden="true"><?= avatar_isi($nama, $foto) ?></span>
                        <div>
                            <strong><?= e($nama) ?></strong>
                            <span class="peran-pil"><?= e($label) ?></span>
                        </div>
                    </div>
                    <a class="item-akun" href="profil.php">
                        <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.200 18.500c1.400-2.200 3.400-3.200 5.800-3.200s4.400 1 5.800 3.200"/></svg>
                        Profil saya
                    </a>
                    <a class="item-akun" href="ganti_password.php">
                        <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="m10.800 12.200 9.200-9.200M17 6l3 3M14 9l2 2"/></svg>
                        Ganti password
                    </a>
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="item-akun bahaya">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="badan">
        <nav class="sidebar" id="sidebar" aria-label="Menu utama">
            <p class="judul-menu">Menu</p>
            <ul>
                <?php $nomorGrup = 0; ?>
                <?php foreach ($menu as $namaMenu => $tujuan): ?>
                    <?php if (is_array($tujuan)): ?>
                        <?php
                        $nomorGrup++;
                        $grupAktif = array_key_exists($menuAktif, $tujuan);
                        $idSub = 'submenu-' . $nomorGrup;
                        ?>
                        <li class="grup<?= $grupAktif ? ' terbuka' : '' ?>">
                            <button type="button" class="kepala-grup<?= $grupAktif ? ' berisi-aktif' : '' ?>"
                                    aria-expanded="<?= $grupAktif ? 'true' : 'false' ?>" aria-controls="<?= $idSub ?>">
                                <span class="ik-kotak"><svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><?= ikon_menu($namaMenu) ?></svg></span>
                                <span><?= e($namaMenu) ?></span>
                                <?= ikon('bawah', 'ik panah-grup') ?>
                            </button>
                            <ul class="sub-menu" id="<?= $idSub ?>"<?= $grupAktif ? '' : ' hidden' ?>>
                                <?php foreach ($tujuan as $namaSub => $tujuanSub): ?>
                                    <?php $aktifSub = $namaSub === $menuAktif; ?>
                                    <li>
                                        <a href="<?= e($tujuanSub) ?>"<?= $aktifSub ? ' class="aktif" aria-current="page"' : '' ?>><?= e($namaSub) ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php $aktif = $namaMenu === $menuAktif; ?>
                    <li>
                        <a href="<?= e($tujuan) ?>"<?= $aktif ? ' class="aktif" aria-current="page"' : '' ?>>
                            <span class="ik-kotak"><svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><?= ikon_menu($namaMenu) ?></svg></span>
                            <span><?= e($namaMenu) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="kaki"><b><?= e(NAMA_KLUB) ?></b>&copy; <?= e(date('Y')) ?> &middot; <?= e($label) ?></p>
        </nav>
        <div class="latar" id="latar"></div>

        <main class="isi">
<?php
    $flash = flash_ambil();
    if ($flash !== null) {
        $ikon = $flash['tipe'] === 'galat' ? 'M12 8v5M12 16.500v.01M10.300 3.900 2.400 18a2 2 0 0 0 1.700 3h15.800a2 2 0 0 0 1.700-3L13.700 3.900a2 2 0 0 0-3.400 0z'
                                           : 'm5 12.500 4.500 4.500L19 7.500';
        ?>
            <div class="toast <?= e((string) $flash['tipe']) ?>" role="status" data-otomatis>
                <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $ikon ?>"/></svg>
                <span><?= e((string) $flash['pesan']) ?></span>
                <button type="button" class="tutup-toast" aria-label="Tutup pesan">&times;</button>
            </div>
<?php
    }
}

function halaman_akhir(): void
{
    ?>
        </main>
    </div>

    <script src="<?= versi_aset('assets/app.js') ?>"></script>
</body>
</html>
<?php
}
