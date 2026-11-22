-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 04:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sdm_klub`
--

-- --------------------------------------------------------

--
-- Table structure for table `cedera`
--

CREATE TABLE `cedera` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `jenis_cedera` varchar(100) NOT NULL,
  `bagian_tubuh` varchar(60) NOT NULL,
  `tingkat` enum('ringan','sedang','berat') NOT NULL DEFAULT 'ringan',
  `penyebab` enum('pertandingan','latihan','lainnya') NOT NULL DEFAULT 'latihan',
  `tanggal_cedera` date NOT NULL,
  `estimasi_pulih` date DEFAULT NULL,
  `tanggal_pulih` date DEFAULT NULL,
  `status` enum('dirawat','pemulihan','pulih') NOT NULL DEFAULT 'dirawat',
  `diagnosis` text DEFAULT NULL,
  `dicatat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cedera`
--

INSERT INTO `cedera` (`id`, `user_id`, `jenis_cedera`, `bagian_tubuh`, `tingkat`, `penyebab`, `tanggal_cedera`, `estimasi_pulih`, `tanggal_pulih`, `status`, `diagnosis`, `dicatat_oleh`, `created_at`, `updated_at`) VALUES
(1, 10, 'Robek otot hamstring grade 1', 'Paha belakang kanan', 'sedang', 'latihan', '2026-11-03', '2026-11-24', NULL, 'pemulihan', 'Nyeri saat sprint. MRI menunjukkan robekan ringan, tanpa retak tulang.', 8, '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(2, 4, 'Keseleo pergelangan kaki', 'Pergelangan kaki kiri', 'ringan', 'pertandingan', '2026-09-09', '2026-09-24', '2026-09-25', 'pulih', 'Keseleo ligamen ringan, tanpa robekan. Pulih penuh.', 3, '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(3, 10, 'Cedera lutut (MCL) grade 2', 'Lutut kanan', 'berat', 'pertandingan', '2026-03-14', '2026-05-09', '2026-05-15', 'pulih', 'Robekan sebagian ligamen kolateral medial. Tanpa operasi.', 8, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(4, 9, 'Terkilir jari tangan', 'Jari manis kiri', 'ringan', 'latihan', '2026-08-05', '2026-08-19', '2026-08-17', 'pulih', 'Terkilir sendi jari saat menangkap bola. Dibidai 10 hari.', 8, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(5, 12, 'Kram otot betis', 'Betis kanan', 'ringan', 'pertandingan', '2026-08-23', '2026-08-27', '2026-08-26', 'pulih', 'Kram akibat dehidrasi, tanpa kerusakan otot.', 8, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(6, 11, 'Memar tulang kering', 'Tulang kering kiri', 'ringan', 'pertandingan', '2026-09-13', '2026-09-20', '2026-09-19', 'pulih', 'Benturan keras tanpa retak (rontgen bersih).', 8, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(10, 4, 'Kontusio paha', 'Paha kiri', 'ringan', 'latihan', '2026-07-31', '2026-08-10', '2026-08-09', 'pulih', 'Memar otot paha akibat benturan lutut lawan saat latihan.', 3, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(12, 58, 'Cedera ligamen pergelangan kaki', 'Pergelangan kaki kiri', 'ringan', 'latihan', '2026-11-03', '2026-11-17', NULL, 'pemulihan', 'Terkilir saat latihan; ligamen tidak robek.', 8, '2026-11-10 22:04:29', '2026-11-10 22:04:29'),
(13, 53, 'Nyeri pinggang bawah', 'Punggung', 'ringan', 'latihan', '2026-08-25', '2026-09-04', '2026-09-03', 'pulih', 'Ketegangan otot; terapi manual & core stability.', 8, '2026-11-10 22:04:29', '2026-11-10 22:04:29'),
(14, 50, 'Gegar otak ringan', 'Kepala', 'sedang', 'pertandingan', '2026-06-16', '2026-06-30', '2026-06-29', 'pulih', 'Benturan kepala; protokol gegar otak bertahap.', 8, '2026-11-10 22:04:29', '2026-11-10 22:04:29'),
(15, 59, 'Cedera otot betis', 'Betis kanan', 'ringan', 'latihan', '2026-04-17', '2026-04-30', '2026-04-29', 'pulih', 'Strain grade 1.', 8, '2026-11-10 22:04:29', '2026-11-10 22:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_karyawan`
--

CREATE TABLE `dokumen_karyawan` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `jenis` enum('identitas','kontrak','medis','lisensi','sertifikat','lainnya') NOT NULL DEFAULT 'lainnya',
  `judul` varchar(150) NOT NULL,
  `nama_berkas` varchar(255) NOT NULL,
  `jalur` varchar(255) NOT NULL,
  `mime` varchar(80) NOT NULL,
  `ukuran` int(10) UNSIGNED NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `diunggah_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen_karyawan`
--

INSERT INTO `dokumen_karyawan` (`id`, `user_id`, `jenis`, `judul`, `nama_berkas`, `jalur`, `mime`, `ukuran`, `berlaku_sampai`, `catatan`, `diunggah_oleh`, `created_at`) VALUES
(2, 7, 'lisensi', 'Lisensi Kepelatihan AFC A', 'Lisensi AFC A - Agus Santoso.pdf', 'uploads/dokumen/contoh_lisensi_agus_santoso.pdf', 'application/pdf', 2047, '2026-12-31', 'No. LIC-A-2019-0421', 6, '2026-11-11 03:16:48'),
(3, 8, 'lisensi', 'Surat Tanda Registrasi (STR) Dokter', 'STR - dr. Maya Kusuma.pdf', 'uploads/dokumen/contoh_str_maya_kusuma.pdf', 'application/pdf', 2067, '2026-12-20', 'No. STR-3171-2021-0098', 6, '2026-11-11 03:16:48'),
(4, 9, 'sertifikat', 'Sertifikat Medis Pra-Musim', 'Sertifikat Medis - Budi Prasetyo.pdf', 'uploads/dokumen/contoh_sertifikat_budi.pdf', 'application/pdf', 2050, '2026-12-05', 'No. MED-2025-1187', 6, '2026-11-11 03:16:48'),
(5, 49, 'identitas', 'Paspor Brasil', 'Paspor - Lucas Ferreira.pdf', 'uploads/dokumen/contoh_paspor_lucas.pdf', 'application/pdf', 2038, '2027-03-20', 'No. BR-FX482913', 6, '2026-11-11 03:16:48');

-- --------------------------------------------------------

--
-- Table structure for table `evaluasi_performa`
--

CREATE TABLE `evaluasi_performa` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `penilai_id` int(10) UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `nilai_fisik` tinyint(3) UNSIGNED NOT NULL,
  `nilai_teknik` tinyint(3) UNSIGNED NOT NULL,
  `nilai_taktik` tinyint(3) UNSIGNED NOT NULL,
  `nilai_mental` tinyint(3) UNSIGNED NOT NULL,
  `nilai_akhir` decimal(4,2) GENERATED ALWAYS AS ((`nilai_fisik` + `nilai_teknik` + `nilai_taktik` + `nilai_mental`) / 4) STORED,
  `catatan` text DEFAULT NULL,
  `rekomendasi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `evaluasi_performa`
--

INSERT INTO `evaluasi_performa` (`id`, `user_id`, `penilai_id`, `tanggal`, `nilai_fisik`, `nilai_teknik`, `nilai_taktik`, `nilai_mental`, `catatan`, `rekomendasi`, `created_at`) VALUES
(1, 9, 7, '2026-10-29', 8, 7, 8, 8, 'Refleks bagus, distribusi bola perlu ditingkatkan.', 'Latihan umpan jauh 2x/minggu.', '2026-11-08 15:29:56'),
(2, 10, 7, '2026-10-29', 8, 7, 7, 7, 'Duel udara kuat, kadang telat menutup ruang.', 'Latihan posisi bertahan garis tinggi.', '2026-11-08 15:29:56'),
(3, 11, 7, '2026-10-29', 9, 8, 8, 8, 'Penggerak lini tengah, stamina sangat baik.', 'Pertahankan; tambah variasi tembakan jarak jauh.', '2026-11-08 15:29:56'),
(4, 12, 7, '2026-10-29', 8, 9, 7, 6, 'Finishing tajam, emosi perlu dikendalikan.', 'Sesi konseling mental; kurangi pelanggaran.', '2026-11-08 15:29:56'),
(8, 4, 7, '2026-10-29', 8, 8, 7, 8, 'Kecepatan sayap sangat baik, penyelesaian akhir meningkat.', 'Latih umpan silang dengan kaki kiri.', '2026-11-08 15:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(10) UNSIGNED NOT NULL,
  `jenis` enum('pertandingan','latihan','rapat','perjalanan','lainnya') NOT NULL DEFAULT 'latihan',
  `judul` varchar(150) NOT NULL,
  `lokasi` varchar(150) NOT NULL DEFAULT '',
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `deskripsi` varchar(500) DEFAULT NULL,
  `peserta` enum('semua','pemain','staf') NOT NULL DEFAULT 'semua',
  `wajib` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `jenis`, `judul`, `lokasi`, `waktu_mulai`, `waktu_selesai`, `deskripsi`, `peserta`, `wajib`) VALUES
(1, 'latihan', 'Latihan taktik', 'Lapangan Latihan Utama', '2026-11-09 16:00:00', '2026-11-09 18:00:00', NULL, 'semua', 1),
(2, 'pertandingan', 'Pohon Beringin FC vs Garuda Muda', 'Stadion Beringin', '2026-10-18 19:00:00', '2026-10-18 21:00:00', 'Liga Nusantara musim 2026/27 (kandang).', 'semua', 1),
(3, 'pertandingan', 'Elang Putih FC vs Pohon Beringin FC', 'Stadion Elang', '2026-10-25 19:00:00', '2026-10-25 21:00:00', 'Liga Nusantara musim 2026/27 (tandang).', 'semua', 1),
(4, 'pertandingan', 'Pohon Beringin FC vs Bintang Timur', 'Stadion Beringin', '2026-11-01 19:00:00', '2026-11-01 21:00:00', 'Liga Nusantara musim 2026/27 (kandang).', 'semua', 1),
(5, 'pertandingan', 'Macan Tutul FC vs Pohon Beringin FC', 'Stadion Macan', '2026-11-14 19:00:00', '2026-11-14 21:00:00', 'Liga Nusantara musim 2026/27 (tandang).', 'semua', 1),
(6, 'latihan', 'Latihan fisik dan stamina', 'Lapangan Latihan Utama', '2026-11-09 07:00:00', '2026-11-09 09:00:00', NULL, 'semua', 1),
(7, 'latihan', 'Latihan set-piece', 'Lapangan Latihan Utama', '2026-11-11 16:00:00', '2026-11-11 18:00:00', NULL, 'semua', 1),
(8, 'pertandingan', 'Pohon Beringin FC vs Rajawali Utara', 'Stadion Beringin', '2026-08-16 19:00:00', '2026-08-16 21:00:00', 'Liga Nusantara musim 2026/27 (kandang).', 'semua', 1),
(9, 'pertandingan', 'Banteng Merah vs Pohon Beringin FC', 'Stadion Banteng', '2026-08-23 19:00:00', '2026-08-23 21:00:00', 'Liga Nusantara musim 2026/27 (tandang).', 'semua', 1),
(10, 'pertandingan', 'Pohon Beringin FC vs Harimau Selatan', 'Stadion Beringin', '2026-08-30 19:00:00', '2026-08-30 21:00:00', 'Liga Nusantara musim 2026/27 (kandang).', 'semua', 1),
(11, 'pertandingan', 'Kuda Laut FC vs Pohon Beringin FC', 'Stadion Bahari', '2026-09-13 19:00:00', '2026-09-13 21:00:00', 'Liga Nusantara musim 2026/27 (tandang).', 'semua', 1),
(12, 'pertandingan', 'Pohon Beringin FC vs Persada Jaya', 'Stadion Beringin', '2026-09-20 15:30:00', '2026-09-20 17:30:00', 'Piala Nusantara musim 2026/27 (kandang).', 'semua', 1),
(13, 'pertandingan', 'Pohon Beringin FC vs Merpati Biru', 'Stadion Beringin', '2026-09-27 19:00:00', '2026-09-27 21:00:00', 'Liga Nusantara musim 2026/27 (kandang).', 'semua', 1),
(14, 'pertandingan', 'Singa Muda vs Pohon Beringin FC', 'Stadion Singa', '2026-10-04 19:00:00', '2026-10-04 21:00:00', 'Liga Nusantara musim 2026/27 (tandang).', 'semua', 1),
(15, 'pertandingan', 'Cendrawasih FC vs Pohon Beringin FC', 'Stadion Cendrawasih', '2026-10-11 15:30:00', '2026-10-11 17:30:00', 'Piala Nusantara musim 2026/27 (tandang).', 'semua', 1),
(16, 'pertandingan', 'Pohon Beringin FC vs Elang Putih FC', 'Stadion Beringin', '2026-03-14 19:00:00', '2026-03-14 21:00:00', 'Liga Nusantara musim 2025/26 (kandang).', 'semua', 1),
(17, 'pertandingan', 'Bintang Timur vs Pohon Beringin FC', 'Stadion Timur', '2026-04-18 19:00:00', '2026-04-18 21:00:00', 'Liga Nusantara musim 2025/26 (tandang).', 'semua', 1),
(18, 'pertandingan', 'Pohon Beringin FC vs Kuda Laut FC', 'Stadion Beringin', '2026-05-09 15:30:00', '2026-05-09 17:30:00', 'Piala Nusantara musim 2025/26 (kandang).', 'semua', 1),
(23, 'pertandingan', 'Pohon Beringin FC vs Samudra Raya', 'Stadion Beringin', '2026-11-21 19:00:00', '2026-11-21 21:00:00', 'Liga Nusantara pekan 13. Kumpul di stadion pukul 16.30.', 'semua', 1),
(24, 'pertandingan', 'Persada Jaya vs Pohon Beringin FC', 'Stadion Persada', '2026-11-28 15:30:00', '2026-11-28 17:30:00', 'Piala Nusantara babak 8 besar.', 'semua', 1),
(25, 'perjalanan', 'Perjalanan tandang ke Stadion Macan', 'Bandara Sultan Hasanuddin', '2026-11-13 06:00:00', '2026-11-15 14:00:00', 'Kumpul di mes pukul 05.00. Bawa paspor pemain, jersey tandang, dan perlengkapan pemulihan.', 'semua', 1),
(26, 'perjalanan', 'Perjalanan tandang ke Stadion Persada', 'Terminal Bus Tim', '2026-11-27 07:00:00', '2026-11-29 12:00:00', 'Perjalanan bus. Latihan ringan di lapangan lawan Jumat sore.', 'semua', 1),
(27, 'rapat', 'Briefing aturan disiplin tim', 'Ruang Media', '2026-10-30 14:00:00', '2026-10-30 15:00:00', 'Penjelasan denda kartu, jam malam, dan kode etik media sosial.', 'semua', 1),
(28, 'rapat', 'Sosialisasi sistem HRMS', 'Ruang Media', '2026-11-06 14:00:00', '2026-11-06 15:30:00', 'Pengenalan aplikasi SDM: slip gaji, pengajuan cuti, dan jadwal.', 'semua', 1),
(29, 'rapat', 'Rapat taktik pra-laga vs Macan Tutul', 'Ruang Analisis', '2026-11-12 19:00:00', '2026-11-12 20:00:00', 'Analisis video lawan dan pembagian tugas bola mati.', 'semua', 1),
(30, 'rapat', 'Rapat evaluasi staf bulanan', 'Ruang Rapat Manajemen', '2026-11-16 10:00:00', '2026-11-16 11:30:00', 'Evaluasi Oktober: performa, cedera, dan anggaran.', 'staf', 1),
(31, 'rapat', 'Rapat tim medis & kebugaran', 'Klinik Klub', '2026-11-25 13:00:00', '2026-11-25 14:00:00', 'Status pemulihan pemain cedera dan rencana tes kebugaran Desember.', 'staf', 1),
(38, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-10-01 08:00:00', '2026-10-01 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(39, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-10-02 08:00:00', '2026-10-02 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(40, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-10-05 16:00:00', '2026-10-05 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(41, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-10-06 08:00:00', '2026-10-06 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(42, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-10-07 08:00:00', '2026-10-07 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(43, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-10-08 08:00:00', '2026-10-08 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(44, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-10-09 08:00:00', '2026-10-09 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(45, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-10-12 16:00:00', '2026-10-12 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(46, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-10-13 08:00:00', '2026-10-13 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(47, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-10-14 08:00:00', '2026-10-14 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(48, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-10-15 08:00:00', '2026-10-15 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(49, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-10-16 08:00:00', '2026-10-16 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(50, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-10-19 16:00:00', '2026-10-19 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(51, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-10-20 08:00:00', '2026-10-20 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(52, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-10-21 08:00:00', '2026-10-21 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(53, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-10-22 08:00:00', '2026-10-22 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(54, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-10-23 08:00:00', '2026-10-23 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(55, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-10-26 16:00:00', '2026-10-26 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(56, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-10-27 08:00:00', '2026-10-27 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(57, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-10-28 08:00:00', '2026-10-28 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(58, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-10-29 08:00:00', '2026-10-29 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(59, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-10-30 08:00:00', '2026-10-30 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(60, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-11-02 16:00:00', '2026-11-02 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(61, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-11-03 08:00:00', '2026-11-03 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(62, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-11-04 08:00:00', '2026-11-04 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(63, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-11-05 08:00:00', '2026-11-05 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(64, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-11-06 08:00:00', '2026-11-06 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(65, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-11-10 08:00:00', '2026-11-10 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(66, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-11-12 08:00:00', '2026-11-12 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(67, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-11-16 16:00:00', '2026-11-16 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(68, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-11-17 08:00:00', '2026-11-17 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(69, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-11-18 08:00:00', '2026-11-18 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(70, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-11-19 08:00:00', '2026-11-19 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(71, 'latihan', 'Latihan simulasi pertandingan', 'Lapangan Latihan Utama', '2026-11-20 08:00:00', '2026-11-20 10:00:00', 'Permainan 11 lawan 11 dengan skenario laga.', 'pemain', 1),
(72, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-11-23 16:00:00', '2026-11-23 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1),
(73, 'latihan', 'Latihan taktik: pressing tinggi', 'Lapangan Latihan Utama', '2026-11-24 08:00:00', '2026-11-24 10:00:00', 'Pola pressing 4-3-3 dan pemicu pressing.', 'pemain', 1),
(74, 'latihan', 'Latihan fisik & kekuatan', 'Gym & Lapangan Latihan', '2026-11-25 08:00:00', '2026-11-25 10:00:00', 'Sesi gym 45 menit, lanjut sprint berulang.', 'pemain', 1),
(75, 'latihan', 'Latihan bola mati', 'Lapangan Latihan Utama', '2026-11-26 08:00:00', '2026-11-26 10:00:00', 'Sepak pojok, tendangan bebas, dan lemparan ke dalam.', 'pemain', 1),
(76, 'latihan', 'Latihan pemulihan & analisis video', 'Lapangan Latihan Utama', '2026-11-30 16:00:00', '2026-11-30 17:30:00', 'Pemulihan aktif, peregangan, lalu review video laga terakhir.', 'pemain', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kehadiran`
--

CREATE TABLE `kehadiran` (
  `jadwal_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('hadir','izin','sakit','alpa','terlambat') NOT NULL DEFAULT 'hadir',
  `catatan` varchar(255) DEFAULT NULL,
  `dicatat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kehadiran`
--

INSERT INTO `kehadiran` (`jadwal_id`, `user_id`, `status`, `catatan`, `dicatat_oleh`, `created_at`) VALUES
(1, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 8, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(1, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(1, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(1, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 53, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(1, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 59, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(1, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(1, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(2, 3, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(2, 4, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(2, 5, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(2, 9, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(2, 10, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(2, 11, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(2, 12, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(2, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(2, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 3, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(3, 4, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(3, 5, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(3, 9, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(3, 10, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(3, 11, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(3, 12, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(3, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(3, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 3, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(4, 4, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(4, 5, 'hadir', NULL, 3, '2026-11-08 15:29:56'),
(4, 9, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(4, 10, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(4, 11, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(4, 12, 'hadir', NULL, 7, '2026-11-08 15:29:56'),
(4, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(4, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(6, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(6, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(6, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 57, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(6, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 65, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(6, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(6, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(8, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(8, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(8, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(8, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(8, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(8, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(8, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(8, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(8, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(9, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(9, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(9, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(9, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(9, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(9, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(9, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(9, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(10, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(10, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(10, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(10, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(10, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(10, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(10, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(10, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(11, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(11, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(11, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(11, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(11, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(11, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(11, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(11, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(12, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(12, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(12, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(12, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(12, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(12, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(12, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(12, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(13, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(13, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(13, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(13, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(13, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(13, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(13, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(13, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(14, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(14, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(14, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(14, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(14, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(14, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(14, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(14, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(15, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(15, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(15, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(15, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(15, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(15, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(15, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 61, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(15, 62, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(16, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(16, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(16, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(16, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(16, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(16, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(16, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(16, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(17, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(17, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(17, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(17, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(17, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(17, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(17, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 57, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(17, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 3, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(18, 4, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(18, 5, 'hadir', NULL, 3, '2026-11-09 15:38:25'),
(18, 9, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(18, 10, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(18, 11, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(18, 12, 'hadir', NULL, 7, '2026-11-09 15:38:25'),
(18, 49, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 50, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 51, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 52, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 53, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 54, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 55, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 56, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 58, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 59, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(18, 60, 'hadir', NULL, 7, '2026-11-10 22:04:29'),
(27, 1, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 2, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 6, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 10, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(27, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(27, 12, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(27, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 63, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(27, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 66, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 67, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 68, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(27, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(27, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 1, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 2, 'alpa', 'Tanpa keterangan', 7, '2026-11-09 16:14:38'),
(28, 3, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(28, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 5, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(28, 6, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(28, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(28, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 54, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(28, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 63, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(28, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 66, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 67, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 68, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(28, 71, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(38, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(38, 49, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(38, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 55, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(38, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(38, 71, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(39, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 8, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(39, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(39, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 57, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(39, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(39, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 3, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(40, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(40, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 61, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(40, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 65, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(40, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(40, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 5, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(41, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(41, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 56, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(41, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(41, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 3, 'alpa', 'Tanpa keterangan', 7, '2026-11-09 16:14:38'),
(42, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(42, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 51, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(42, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 64, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(42, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(42, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 4, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(43, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(43, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 51, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(43, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 55, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(43, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(43, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(44, 12, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(44, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 63, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(44, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(44, 70, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(44, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 3, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(45, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 7, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(45, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(45, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 54, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(45, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 65, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(45, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(45, 71, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(46, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(46, 11, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(46, 12, 'alpa', 'Tanpa keterangan', 7, '2026-11-09 16:14:38'),
(46, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 60, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(46, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 62, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(46, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(46, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(47, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 53, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(47, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 55, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(47, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 60, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(47, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(47, 70, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(47, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 10, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(48, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(48, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 50, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(48, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 61, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(48, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(48, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(49, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 52, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(49, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 69, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(49, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(49, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 9, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(50, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 11, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(50, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(50, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 60, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(50, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 69, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(50, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(50, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 8, 'alpa', 'Tanpa keterangan', 7, '2026-11-09 16:14:38'),
(51, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(51, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 51, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(51, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 64, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(51, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 69, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(51, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(51, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 8, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(52, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(52, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 56, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(52, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 59, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(52, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(52, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(53, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 50, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(53, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 54, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(53, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(53, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 7, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(54, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(54, 49, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(54, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 58, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(54, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(54, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(55, 49, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(55, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(55, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 4, 'alpa', 'Tanpa keterangan', 7, '2026-11-09 16:14:38'),
(56, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 10, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(56, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(56, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 57, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(56, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 65, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(56, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(56, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 5, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(57, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(57, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 52, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(57, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 63, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(57, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 65, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(57, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(57, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 5, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38');
INSERT INTO `kehadiran` (`jadwal_id`, `user_id`, `status`, `catatan`, `dicatat_oleh`, `created_at`) VALUES
(58, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(58, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 56, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(58, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 58, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(58, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(58, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(59, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 53, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(59, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 64, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(59, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(59, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 4, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(60, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 10, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(60, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 55, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(60, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(60, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(61, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(61, 12, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(61, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 61, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(61, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 63, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(61, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(61, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 3, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(62, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 9, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(62, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(62, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(62, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 54, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(62, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(62, 71, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(63, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 4, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-09 16:14:38'),
(63, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(63, 11, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-09 16:14:38'),
(63, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(63, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 62, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(63, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 70, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(63, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 3, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 4, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 5, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 7, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 8, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 9, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-09 16:14:38'),
(64, 11, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 12, 'hadir', NULL, 7, '2026-11-09 16:14:38'),
(64, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 52, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 53, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(64, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 57, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(64, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 61, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(64, 70, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(64, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 3, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 4, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 5, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 7, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 8, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 9, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(65, 10, 'sakit', 'Cedera hamstring (rehabilitasi)', 7, '2026-11-10 15:04:52'),
(65, 11, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 12, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 49, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 50, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 51, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 52, 'izin', 'Urusan keluarga (izin disetujui)', 7, '2026-11-10 15:04:52'),
(65, 53, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 54, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 55, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 56, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 57, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 58, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 59, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 60, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 61, 'terlambat', 'Terlambat 10-20 menit', 7, '2026-11-10 15:04:52'),
(65, 62, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 63, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 64, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 65, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 69, 'hadir', NULL, 7, '2026-11-10 15:04:52'),
(65, 70, 'alpa', 'Tanpa keterangan', 7, '2026-11-10 15:04:52'),
(65, 71, 'hadir', NULL, 7, '2026-11-10 15:04:52');

-- --------------------------------------------------------

--
-- Table structure for table `kontrak`
--

CREATE TABLE `kontrak` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `nomor_kontrak` varchar(50) DEFAULT NULL,
  `jenis` enum('profesional','amatir','pinjaman','percobaan','staf') NOT NULL DEFAULT 'profesional',
  `versi` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `kontrak_sebelumnya_id` int(10) UNSIGNED DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `gaji_pokok` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tunjangan_bulanan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus_tanda_tangan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus_per_goal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus_per_main` decimal(15,2) NOT NULL DEFAULT 0.00,
  `klausul_rilis` decimal(15,2) DEFAULT NULL,
  `hak_cuti_tahunan` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `status` enum('draft','aktif','diperbarui','berakhir','dibatalkan') NOT NULL DEFAULT 'draft',
  `file_dokumen` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `dibuat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `kontrak`
--

INSERT INTO `kontrak` (`id`, `user_id`, `nomor_kontrak`, `jenis`, `versi`, `kontrak_sebelumnya_id`, `tanggal_mulai`, `tanggal_selesai`, `gaji_pokok`, `tunjangan_bulanan`, `bonus_tanda_tangan`, `bonus_per_goal`, `bonus_per_main`, `klausul_rilis`, `hak_cuti_tahunan`, `status`, `file_dokumen`, `catatan`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(1, 9, 'KTR-2024-001', 'profesional', 2, 26, '2024-07-01', '2027-06-30', 18000000.00, 1000000.00, 54000000.00, 0.00, 500000.00, 720000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(2, 11, 'KTR-2024-003', 'profesional', 2, 27, '2024-07-01', '2027-06-30', 20000000.00, 1000000.00, 60000000.00, 1000000.00, 500000.00, 800000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(3, 12, 'KTR-2025-001', 'profesional', 2, 28, '2025-01-15', '2027-01-14', 25000000.00, 1000000.00, 75000000.00, 3000000.00, 500000.00, 1000000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(4, 6, 'KTR-2020-001', 'staf', 1, NULL, '2020-01-02', '2028-01-01', 30000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(5, 7, 'KTR-2023-001', 'staf', 2, 29, '2023-07-01', '2027-06-30', 35000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(6, 8, 'KTR-2022-001', 'staf', 1, NULL, '2022-01-10', '2027-01-09', 22000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(8, 10, 'KTR-2025-002', 'profesional', 2, 30, '2025-07-01', '2027-01-07', 15000000.00, 1000000.00, 45000000.00, 750000.00, 400000.00, 600000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(9, 4, 'KTR-2026-101', 'profesional', 2, 31, '2026-07-01', '2028-06-30', 22000000.00, 1000000.00, 66000000.00, 1000000.00, 500000.00, 950000000.00, 12, 'aktif', NULL, 'Perpanjangan 2 musim, kenaikan gaji 29%.', 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(10, 1, 'KTR-2021-101', 'staf', 1, NULL, '2021-01-04', '2028-01-03', 32000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(11, 2, 'KTR-2022-101', 'staf', 1, NULL, '2022-03-01', '2027-02-28', 15000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(12, 3, 'KTR-2022-102', 'staf', 1, NULL, '2022-08-15', '2027-08-14', 21000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(13, 5, 'KTR-2023-101', 'staf', 1, NULL, '2023-02-01', '2027-01-31', 12000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(26, 9, 'KTR-2022-011', 'profesional', 1, NULL, '2022-07-01', '2024-06-30', 14500000.00, 800000.00, 29000000.00, 0.00, 400000.00, 432000000.00, 12, 'diperbarui', NULL, 'Diperbarui oleh KTR-2024-001.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(27, 11, 'KTR-2022-013', 'profesional', 1, NULL, '2022-07-01', '2024-06-30', 14000000.00, 800000.00, 28000000.00, 750000.00, 400000.00, 480000000.00, 12, 'diperbarui', NULL, 'Diperbarui oleh KTR-2024-003.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(28, 12, 'KTR-2023-014', 'profesional', 1, NULL, '2023-01-15', '2025-01-14', 15000000.00, 800000.00, 30000000.00, 2250000.00, 400000.00, 600000000.00, 12, 'diperbarui', NULL, 'Diperbarui oleh KTR-2025-001.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(29, 7, 'KTR-2021-012', 'staf', 1, NULL, '2021-07-01', '2023-06-30', 30000000.00, 1200000.00, 60000000.00, 0.00, 0.00, NULL, 14, 'diperbarui', NULL, 'Diperbarui oleh KTR-2023-001.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(30, 10, 'KTR-2024-015', 'profesional', 1, NULL, '2024-07-01', '2025-06-30', 12000000.00, 800000.00, 24000000.00, 562500.00, 320000.00, 360000000.00, 12, 'diperbarui', NULL, 'Diperbarui oleh KTR-2025-002.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(31, 4, 'KTR-2024-101', 'profesional', 1, NULL, '2024-07-01', '2026-06-30', 17000000.00, 800000.00, 34000000.00, 750000.00, 400000.00, 570000000.00, 12, 'diperbarui', NULL, 'Diperbarui oleh KTR-2026-101.', 1, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(38, 58, 'KTR-2025-020', 'profesional', 1, NULL, '2025-07-01', '2028-06-30', 55000000.00, 1000000.00, 165000000.00, 2000000.00, 500000.00, 2200000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(39, 51, 'KTR-2026-013', 'profesional', 1, NULL, '2026-01-30', '2027-01-30', 30000000.00, 1000000.00, 90000000.00, 1000000.00, 500000.00, 1200000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(40, 50, 'KTR-2024-012', 'profesional', 1, NULL, '2024-07-01', '2027-06-30', 45000000.00, 1000000.00, 135000000.00, 1000000.00, 500000.00, 1800000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(41, 62, 'KTR-2026-024', 'profesional', 1, NULL, '2026-07-01', '2028-06-30', 12000000.00, 1000000.00, 36000000.00, 750000.00, 500000.00, 480000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(42, 52, 'KTR-2025-014', 'profesional', 1, NULL, '2025-07-01', '2027-06-30', 20000000.00, 1000000.00, 60000000.00, 750000.00, 500000.00, 800000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(43, 57, 'KTR-2025-019', 'profesional', 1, NULL, '2025-07-01', '2027-06-30', 25000000.00, 1000000.00, 75000000.00, 1000000.00, 500000.00, 1000000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(44, 54, 'KTR-2025-016', 'profesional', 2, 76, '2025-11-28', '2026-11-28', 40000000.00, 1000000.00, 120000000.00, 1000000.00, 500000.00, 1600000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(45, 49, 'KTR-2025-011', 'profesional', 1, NULL, '2025-07-01', '2028-06-30', 120000000.00, 1000000.00, 360000000.00, 1500000.00, 500000.00, 4800000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(46, 59, 'KTR-2024-021', 'profesional', 1, NULL, '2024-12-31', '2026-12-31', 150000000.00, 1000000.00, 450000000.00, 5000000.00, 500000.00, 6000000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(47, 56, 'KTR-2024-018', 'profesional', 2, 78, '2024-07-01', '2027-06-30', 60000000.00, 1000000.00, 180000000.00, 2000000.00, 500000.00, 2400000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(48, 61, 'KTR-2026-023', 'profesional', 1, NULL, '2026-07-01', '2029-06-30', 18000000.00, 1000000.00, 54000000.00, 1000000.00, 500000.00, 720000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(49, 63, 'KTR-2026-025', 'profesional', 1, NULL, '2026-07-01', '2028-06-30', 15000000.00, 1000000.00, 45000000.00, 0.00, 500000.00, 600000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(50, 65, 'KTR-2026-027', 'profesional', 1, NULL, '2026-08-01', '2027-06-30', 10000000.00, 1000000.00, 30000000.00, 500000.00, 500000.00, 400000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(51, 60, 'KTR-2025-022', 'profesional', 1, NULL, '2025-07-01', '2028-06-30', 140000000.00, 1000000.00, 420000000.00, 5000000.00, 500000.00, 5600000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(52, 55, 'KTR-2025-017', 'profesional', 1, NULL, '2025-07-01', '2028-06-30', 50000000.00, 1000000.00, 150000000.00, 1500000.00, 500000.00, 2000000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(53, 64, 'KTR-2024-026', 'profesional', 1, NULL, '2024-01-26', '2027-01-26', 35000000.00, 1000000.00, 105000000.00, 0.00, 500000.00, 1400000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(69, 66, 'KTR-2026-028', 'staf', 2, 79, '2026-05-31', '2028-05-30', 21000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(70, 67, 'KTR-2026-029', 'staf', 1, NULL, '2026-05-31', '2027-05-31', 14000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(71, 68, 'KTR-2025-030', 'staf', 1, NULL, '2025-06-11', '2027-06-10', 9000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(72, 69, 'KTR-2026-031', 'staf', 2, 80, '2026-05-31', '2027-05-31', 35000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(73, 70, 'KTR-2024-032', 'staf', 1, NULL, '2024-12-13', '2026-12-13', 8000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(74, 71, 'KTR-2025-033', 'staf', 1, NULL, '2025-10-22', '2026-10-22', 7000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'aktif', NULL, NULL, 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(76, 54, 'KTR-2023-041', 'profesional', 1, NULL, '2023-11-27', '2025-11-27', 30000000.00, 1000000.00, 90000000.00, 750000.00, 400000.00, 1200000000.00, 12, 'diperbarui', NULL, 'Diperpanjang dengan kenaikan gaji.', 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(77, 53, 'KTR-2022-042', 'profesional', 1, NULL, '2022-07-01', '2024-06-30', 70000000.00, 1000000.00, 210000000.00, 1500000.00, 400000.00, 2800000000.00, 12, 'diperbarui', NULL, 'Diperpanjang dengan kenaikan gaji.', 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(78, 56, 'KTR-2022-043', 'profesional', 1, NULL, '2022-07-01', '2024-06-30', 45000000.00, 1000000.00, 135000000.00, 1500000.00, 400000.00, 1800000000.00, 12, 'diperbarui', NULL, 'Diperpanjang dengan kenaikan gaji.', 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(79, 66, 'KTR-2024-044', 'staf', 1, NULL, '2024-05-30', '2026-05-30', 17500000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'diperbarui', NULL, 'Diperpanjang dengan kenaikan gaji.', 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(80, 69, 'KTR-2024-045', 'staf', 1, NULL, '2024-05-02', '2026-05-30', 28000000.00, 1500000.00, 0.00, 0.00, 0.00, NULL, 14, 'diperbarui', NULL, 'Diperpanjang dengan kenaikan gaji.', 6, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(91, 53, 'KTR-2024-034', 'profesional', 2, 77, '2024-07-01', '2028-06-30', 95000000.00, 1000000.00, 285000000.00, 2000000.00, 500000.00, 3800000000.00, 12, 'aktif', NULL, NULL, 6, '2026-11-10 15:20:04', '2026-11-10 15:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `aksi` varchar(50) NOT NULL,
  `entitas` varchar(50) DEFAULT NULL,
  `entitas_id` int(10) UNSIGNED DEFAULT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aksi`, `entitas`, `entitas_id`, `detail`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', NULL, NULL, 'Login berhasil', '127.0.0.1', '2026-11-08 15:29:56'),
(2, 1, 'tambah', 'kontrak', NULL, 'Menambah kontrak KTR-2024-101', '127.0.0.1', '2026-11-08 15:29:56'),
(3, 1, 'ubah', 'penggajian', NULL, 'Menandai gaji bulan lalu sebagai dibayar', '127.0.0.1', '2026-11-08 15:29:56'),
(4, 2, 'ubah', 'pengajuan', NULL, 'Menyetujui perubahan data pemain1', '127.0.0.1', '2026-11-08 15:29:56'),
(5, 3, 'tambah', 'cedera', NULL, 'Mencatat cedera hamstring Andi Pratama', '127.0.0.1', '2026-11-08 15:29:56'),
(6, 4, 'login', NULL, NULL, 'Login berhasil', '127.0.0.1', '2026-11-08 15:29:56'),
(7, 4, 'tambah', 'pengajuan', NULL, 'Mengajukan cuti', '127.0.0.1', '2026-11-08 15:29:56'),
(92, 6, 'tinjau', 'pengajuan', NULL, 'Menyetujui cuti tahunan Rizky Ramadhan', '10.10.1.20', '2026-11-10 08:42:00'),
(93, 8, 'tambah', 'pemeriksaan_kebugaran', NULL, 'Mencatat hasil tes kebugaran 22 pemain', '10.10.1.20', '2026-10-27 10:30:00'),
(94, 6, 'dibayar', 'penggajian', NULL, 'Menandai gaji Oktober 2026 sebagai dibayar (35 slip)', '10.10.1.20', '2026-11-01 01:55:00'),
(95, 6, 'perpanjang', 'kontrak', NULL, 'Memperpanjang kontrak Rafael Siregar (versi 2)', '10.10.1.20', '2026-10-29 04:20:00'),
(96, 8, 'tambah', 'cedera', NULL, 'Mencatat cedera hamstring Andi Pratama', '10.10.1.20', '2026-11-03 10:40:00'),
(97, 8, 'tambah', 'cedera', NULL, 'Mencatat cedera pergelangan kaki Ahmad Fauzan', '10.10.1.20', '2026-11-03 11:00:00'),
(98, 7, 'tambah', 'program_latihan', NULL, 'Membuat program Distribusi bola dan bola atas kiper', '10.10.1.20', '2026-11-09 03:10:00'),
(99, 6, 'tambah', 'pengumuman', NULL, 'Menerbitkan pengumuman Evaluasi kinerja staf akhir tahun', '10.10.1.20', '2026-11-10 01:15:00'),
(100, 6, 'tambah', 'users', NULL, 'Membuat akun untuk pemain baru Samuel Tanoto', '10.10.1.20', '2026-08-01 02:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` varchar(500) DEFAULT NULL,
  `tautan` varchar(255) DEFAULT NULL,
  `dibaca_pada` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kategori` enum('sistem','kontrak','gaji','pengajuan','jadwal','performa','medis','dokumen') NOT NULL DEFAULT 'sistem',
  `prioritas` enum('normal','penting') NOT NULL DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `user_id`, `judul`, `pesan`, `tautan`, `dibaca_pada`, `created_at`, `kategori`, `prioritas`) VALUES
(1, 10, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(2, 9, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(3, 8, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(4, 12, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(5, 6, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(6, 7, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(7, 11, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(9, 4, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(10, 4, 'Gaji dibayar', 'Gaji bulan lalu sudah ditransfer.', NULL, '2026-11-08 22:29:56', '2026-11-08 15:29:56', 'gaji', 'normal'),
(11, 4, 'Pengajuan disetujui', 'Perubahan nomor telepon Anda telah disetujui.', NULL, NULL, '2026-11-08 15:29:56', 'pengajuan', 'normal'),
(12, 1, 'Pengajuan baru', 'pemain1 mengajukan cuti.', NULL, NULL, '2026-11-08 15:29:56', 'pengajuan', 'normal'),
(13, 2, 'Kontrak hampir habis', 'Ada kontrak yang berakhir dalam 90 hari.', NULL, NULL, '2026-11-08 15:29:56', 'kontrak', 'normal'),
(14, 3, 'Pemain cedera', 'Andi Pratama sedang dalam masa pemulihan.', NULL, '2026-11-11 10:26:50', '2026-11-08 15:29:56', 'medis', 'normal'),
(15, 5, 'Jadwal baru', 'Latihan fisik dan stamina besok pagi.', 'jadwal.php', NULL, '2026-11-08 15:29:56', 'jadwal', 'normal'),
(26, 6, 'Pengajuan baru', 'Rizky Ramadhan mengajukan izin.', NULL, NULL, '2026-11-10 15:04:52', 'pengajuan', 'normal'),
(27, 58, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(28, 51, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(29, 50, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(30, 62, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(31, 52, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(32, 70, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(33, 57, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(34, 54, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(35, 67, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(36, 53, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(37, 49, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(38, 59, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(39, 66, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(40, 68, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(41, 56, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(42, 61, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(43, 69, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(44, 63, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(45, 65, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(46, 71, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(47, 60, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(48, 55, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(49, 64, 'Jadwal baru', 'Pertandingan melawan Macan Tutul FC sudah dijadwalkan.', 'jadwal.php', NULL, '2026-11-10 15:04:52', 'jadwal', 'normal'),
(58, 1, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(59, 2, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(60, 6, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(61, 66, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(62, 67, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(63, 68, 'Kontrak Fajar Nugroho berakhir dalam 65 hari', 'KTR-2025-001 berakhir 14 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(64, 12, 'Kontrak Anda berakhir dalam 65 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2025-001.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(65, 1, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(66, 2, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(67, 6, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(68, 66, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(69, 67, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(70, 68, 'Kontrak dr. Maya Kusuma berakhir dalam 60 hari', 'KTR-2022-001 berakhir 9 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(71, 8, 'Kontrak Anda berakhir dalam 60 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2022-001.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(72, 1, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(73, 2, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(74, 6, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(75, 66, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(76, 67, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(77, 68, 'Kontrak Andi Pratama berakhir dalam 58 hari', 'KTR-2025-002 berakhir 7 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(78, 10, 'Kontrak Anda berakhir dalam 58 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2025-002.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(79, 1, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(80, 2, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(81, 6, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(82, 66, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(83, 67, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(84, 68, 'Kontrak Staf Contoh berakhir dalam 82 hari', 'KTR-2023-101 berakhir 31 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(85, 5, 'Kontrak Anda berakhir dalam 82 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2023-101.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(86, 1, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(87, 2, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(88, 6, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(89, 66, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(90, 67, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(91, 68, 'Kontrak Arif Hidayat berakhir dalam 81 hari', 'KTR-2026-013 berakhir 30 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(92, 51, 'Kontrak Anda berakhir dalam 81 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2026-013.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(93, 1, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(94, 2, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(95, 6, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(96, 66, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(97, 67, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(98, 68, 'Kontrak Hendra Gunawan berakhir dalam 18 hari', 'KTR-2025-016 berakhir 28 Nov 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(99, 54, 'Kontrak Anda berakhir dalam 18 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2025-016.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(100, 1, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(101, 2, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(102, 6, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(103, 66, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(104, 67, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(105, 68, 'Kontrak Marcelo Santos berakhir dalam 51 hari', 'KTR-2024-021 berakhir 31 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(106, 59, 'Kontrak Anda berakhir dalam 51 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2024-021.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(107, 1, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(108, 2, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(109, 6, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(110, 66, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(111, 67, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(112, 68, 'Kontrak Yusuf Habibi berakhir dalam 77 hari', 'KTR-2024-026 berakhir 26 Jan 2027.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(113, 64, 'Kontrak Anda berakhir dalam 77 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2024-026.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(114, 1, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(115, 2, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(116, 6, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(117, 66, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(118, 67, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(119, 68, 'Kontrak Eko Prasetyo berakhir dalam 33 hari', 'KTR-2024-032 berakhir 13 Des 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(120, 70, 'Kontrak Anda berakhir dalam 33 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2024-032.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(121, 1, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(122, 2, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(123, 6, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(124, 66, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(125, 67, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(126, 68, 'Kontrak Ahmad Sanusi sudah lewat 19 hari', 'KTR-2025-033 berakhir 22 Okt 2026.', 'kelola_kontrak.php?hal=hampir', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(127, 71, 'Kontrak Anda sudah lewat 19 hari', 'Hubungi HR untuk membicarakan perpanjangan KTR-2025-033.', 'kontrak.php', NULL, '2026-11-10 15:18:27', 'kontrak', 'penting'),
(197, 9, 'Rapat taktik pra-laga besok pukul 19.00', 'Rapat taktik pra-laga vs Macan Tutul di ruang analisis. Kehadiran wajib untuk seluruh skuad.', 'jadwal.php', NULL, '2026-11-11 00:45:00', 'jadwal', 'normal'),
(198, 9, 'Program latihan baru: Distribusi bola dan bola atas kiper', '9 Nov 2026 – 20 Des 2026 · 2 sesi per minggu. Lihat jadwal sesinya di menu Program Latihan.', 'latihan.php', NULL, '2026-11-09 03:12:00', 'performa', 'normal'),
(199, 9, 'Gaji Oktober 2026 sudah dibayar', 'Gaji sudah ditransfer ke rekening Anda. Slip gaji dapat diunduh di menu Slip Gaji.', 'gaji.php', '2026-11-01 12:30:00', '2026-11-01 02:00:00', 'gaji', 'normal'),
(200, 9, 'Hasil tes kebugaran: skor 84', 'Tes 27 Okt 2026 sudah dinilai tim medis. Lihat rincian VO₂max, sprint, dan Yo-Yo Anda.', 'kebugaran.php', '2026-10-28 08:10:00', '2026-10-27 08:20:00', 'performa', 'normal'),
(201, 10, 'Cedera dicatat: Robek otot hamstring grade 1', 'Paha belakang kanan · perkiraan kembali bermain 24 Nov 2026. Ikuti jadwal penanganan dari tim medis.', 'cedera.php', NULL, '2026-11-03 10:40:00', 'medis', 'normal'),
(202, 10, 'Program latihan baru: Pemulihan hamstring bertahap', '4 Nov 2026 – 24 Nov 2026 · 3 sesi per minggu. Lihat jadwal sesinya di menu Program Latihan.', 'latihan.php', NULL, '2026-11-04 02:05:00', 'performa', 'normal'),
(203, 10, 'Gaji Oktober 2026 sudah dibayar', 'Gaji sudah ditransfer ke rekening Anda. Slip gaji dapat diunduh di menu Slip Gaji.', 'gaji.php', '2026-11-02 07:00:00', '2026-11-01 02:00:00', 'gaji', 'normal'),
(204, 7, 'Ahmad Fauzan cedera saat latihan', 'Cedera ligamen pergelangan kaki (ringan) · perkiraan kembali 17 Nov 2026. Sesuaikan beban latihannya.', 'cedera.php', NULL, '2026-11-03 11:02:00', 'medis', 'normal'),
(205, 7, 'Rapat evaluasi staf bulanan', 'Senin, 16 Nov 2026 pukul 10.00 di ruang rapat utama. Siapkan laporan performa skuad.', 'jadwal.php', NULL, '2026-11-10 07:00:00', 'jadwal', 'normal'),
(206, 7, 'Pengajuan Cuti akhir tahun diterima HR', 'Pengajuan Anda sedang ditinjau. Keputusan akan dikirim lewat notifikasi.', 'pengajuan.php', '2026-11-08 09:00:00', '2026-11-07 09:25:00', 'pengajuan', 'normal'),
(207, 7, 'Gaji Oktober 2026 sudah dibayar', 'Gaji sudah ditransfer ke rekening Anda. Slip gaji dapat diunduh di menu Slip Gaji.', 'gaji.php', '2026-11-01 19:20:00', '2026-11-01 02:00:00', 'gaji', 'normal'),
(208, 8, 'Jadwal kontrol: Andi Pratama', 'Evaluasi fisioterapi hamstring dijadwalkan 12 Nov 2026 pukul 09.00 di ruang medis.', 'cedera.php', NULL, '2026-11-10 09:30:00', 'medis', 'normal'),
(209, 8, 'Tes kebugaran berkala selesai', '22 pemain sudah dites pada 27 Okt 2026; 2 pemain berlevel Kurang perlu program fisik khusus.', 'kebugaran.php', '2026-10-28 10:00:00', '2026-10-27 11:00:00', 'performa', 'normal'),
(210, 6, 'Pengajuan baru: Cuti akhir tahun', 'Agus Santoso mengajukan cuti 21 Des 2026 – 2 Jan 2027. Menunggu persetujuan Anda.', 'pengajuan.php', NULL, '2026-11-07 09:25:00', 'pengajuan', 'normal'),
(211, 6, 'Periode gaji November 2026 dibuka', 'Siapkan draf slip gaji November dan setujui sebelum 28 Nov 2026 agar transfer tepat waktu.', 'kelola_gaji.php', NULL, '2026-11-09 01:00:00', 'gaji', 'normal'),
(212, 6, 'Masuk dari perangkat baru', 'Akun Anda masuk dari Chrome di Windows pada 8 Nov 2026 pukul 20.14. Jika bukan Anda, segera ganti password.', 'ganti_password.php', '2026-11-09 07:30:00', '2026-11-08 13:14:00', 'sistem', 'penting'),
(228, 1, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(229, 2, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(230, 3, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', '2026-11-11 10:26:50', '2026-11-03 01:00:00', 'sistem', 'normal'),
(231, 4, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(232, 5, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(233, 6, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(234, 7, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(235, 8, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(236, 9, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(237, 10, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(238, 11, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(239, 12, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(240, 49, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(241, 50, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(242, 51, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(243, 52, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(244, 53, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(245, 54, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(246, 55, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(247, 56, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(248, 57, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(249, 58, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(250, 59, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(251, 60, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(252, 61, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(253, 62, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(254, 63, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(255, 64, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(256, 65, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(257, 66, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(258, 67, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(259, 68, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(260, 69, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(261, 70, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(262, 71, 'Selamat datang di Sistem SDM POHON BERINGIN FC', 'Lengkapi profil dan kontak darurat Anda agar data klub selalu mutakhir.', 'profil.php', NULL, '2026-11-03 01:00:00', 'sistem', 'normal'),
(291, 4, 'Hasil tes kebugaran: skor 90', 'Tes 27 Okt 2026 sudah dinilai tim medis. Lihat rincian VO₂max, sprint, dan Yo-Yo Anda.', 'kebugaran.php', NULL, '2026-10-27 08:30:00', 'performa', 'normal'),
(292, 4, 'Rapat taktik pra-laga besok pukul 19.00', 'Rapat taktik pra-laga vs Macan Tutul di ruang analisis. Kehadiran wajib untuk seluruh skuad.', 'jadwal.php', NULL, '2026-11-11 00:45:00', 'jadwal', 'normal'),
(293, 1, 'Periode gaji November 2026 dibuka', 'Siapkan draf slip gaji November dan setujui sebelum 28 Nov 2026 agar transfer tepat waktu.', 'kelola_gaji.php', NULL, '2026-11-09 01:00:00', 'gaji', 'normal'),
(294, 2, 'Periode gaji November 2026 dibuka', 'Siapkan draf slip gaji November dan setujui sebelum 28 Nov 2026 agar transfer tepat waktu.', 'kelola_gaji.php', NULL, '2026-11-09 01:00:00', 'gaji', 'normal'),
(295, 3, 'Jadwal kontrol: Andi Pratama', 'Evaluasi fisioterapi hamstring dijadwalkan 12 Nov 2026 pukul 09.00 di ruang medis.', 'cedera.php', '2026-11-11 10:26:46', '2026-11-10 09:30:00', 'medis', 'normal'),
(401, 1, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(402, 2, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(403, 6, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(404, 66, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(405, 67, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(406, 68, 'Lisensi Agus Santoso kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A milik Agus Santoso berlaku sampai 31 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=7&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(407, 7, 'Lisensi Anda kedaluwarsa dalam 50 hari', 'Lisensi Kepelatihan AFC A berlaku sampai 31 Des 2026. Serahkan dokumen terbaru ke HR.', 'profil.php', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(408, 1, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(409, 2, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(410, 6, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(411, 66, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(412, 67, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(413, 68, 'Lisensi dr. Maya Kusuma kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter milik dr. Maya Kusuma berlaku sampai 20 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=8&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(414, 8, 'Lisensi Anda kedaluwarsa dalam 39 hari', 'Surat Tanda Registrasi (STR) Dokter berlaku sampai 20 Des 2026. Serahkan dokumen terbaru ke HR.', 'profil.php', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(415, 1, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(416, 2, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(417, 6, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(418, 66, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(419, 67, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(420, 68, 'Sertifikat Budi Prasetyo kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim milik Budi Prasetyo berlaku sampai 5 Des 2026. Siapkan perpanjangan dokumen.', 'detail_karyawan.php?id=9&tab=dokumen', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting'),
(421, 9, 'Sertifikat Anda kedaluwarsa dalam 24 hari', 'Sertifikat Medis Pra-Musim berlaku sampai 5 Des 2026. Serahkan dokumen terbaru ke HR.', 'profil.php', NULL, '2026-11-11 03:52:20', 'dokumen', 'penting');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_preferensi`
--

CREATE TABLE `notifikasi_preferensi` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `kategori` enum('sistem','kontrak','gaji','pengajuan','jadwal','performa','medis','dokumen') NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pemain`
--

CREATE TABLE `pemain` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `nomor_punggung` tinyint(3) UNSIGNED DEFAULT NULL,
  `goal` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `assist` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_merah` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_kuning` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pemain`
--

INSERT INTO `pemain` (`user_id`, `nomor_punggung`, `goal`, `assist`, `kartu_merah`, `kartu_kuning`) VALUES
(4, 7, 7, 7, 0, 4),
(9, 1, 0, 0, 0, 1),
(10, 4, 1, 2, 0, 5),
(11, 8, 5, 9, 0, 4),
(12, 10, 12, 4, 1, 4),
(49, 3, 0, 0, 0, 3),
(50, 5, 0, 0, 0, 1),
(51, 2, 0, 0, 0, 1),
(52, 15, 0, 1, 0, 1),
(53, 6, 0, 3, 0, 0),
(54, 16, 0, 0, 0, 0),
(55, 17, 0, 1, 0, 2),
(56, 14, 0, 2, 0, 2),
(57, 18, 0, 0, 0, 2),
(58, 11, 0, 0, 0, 1),
(59, 9, 2, 0, 0, 2),
(60, 19, 1, 0, 0, 1),
(61, 20, 1, 0, 0, 0),
(62, 21, 0, 0, 0, 0),
(63, 22, 0, 0, 0, 0),
(64, 12, 0, 0, 0, 0),
(65, 23, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pemeriksaan_kebugaran`
--

CREATE TABLE `pemeriksaan_kebugaran` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `tinggi_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `berat_kg` decimal(5,2) DEFAULT NULL,
  `lemak_tubuh_persen` decimal(4,1) DEFAULT NULL,
  `denyut_istirahat` tinyint(3) UNSIGNED DEFAULT NULL,
  `tekanan_darah` varchar(10) DEFAULT NULL,
  `vo2max` decimal(4,1) DEFAULT NULL,
  `sprint_30m_detik` decimal(4,2) DEFAULT NULL,
  `yoyo_level` decimal(4,1) DEFAULT NULL,
  `skor_kebugaran` tinyint(3) UNSIGNED DEFAULT NULL,
  `status_kebugaran` enum('fit','terbatas','tidak_fit') NOT NULL DEFAULT 'fit',
  `catatan` text DEFAULT NULL,
  `pemeriksa_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `pemeriksaan_kebugaran`
--

INSERT INTO `pemeriksaan_kebugaran` (`id`, `user_id`, `tanggal`, `tinggi_cm`, `berat_kg`, `lemak_tubuh_persen`, `denyut_istirahat`, `tekanan_darah`, `vo2max`, `sprint_30m_detik`, `yoyo_level`, `skor_kebugaran`, `status_kebugaran`, `catatan`, `pemeriksa_id`, `created_at`) VALUES
(1, 9, '2026-10-27', 185, 80.50, 11.2, 54, '118/76', 52.1, 4.22, 18.7, 84, 'fit', 'Kondisi prima.', 8, '2026-11-08 15:29:56'),
(2, 10, '2026-10-27', 182, 76.00, 12.0, 56, '120/78', 54.0, 4.05, 19.9, 86, 'fit', 'Kondisi prima.', 8, '2026-11-08 15:29:56'),
(3, 11, '2026-10-27', 176, 70.20, 10.5, 50, '115/72', 60.3, 3.96, 22.4, 93, 'fit', 'VO2max tertinggi di tim.', 8, '2026-11-08 15:29:56'),
(4, 12, '2026-10-27', 178, 72.80, 11.8, 55, '119/77', 56.4, 3.85, 20.3, 88, 'fit', 'Kondisi prima.', 8, '2026-11-08 15:29:56'),
(8, 4, '2026-10-27', 174, 68.50, 10.8, 52, '116/74', 58.2, 3.91, 21.5, 90, 'fit', 'Kondisi prima.', 3, '2026-11-08 15:29:56'),
(17, 10, '2026-09-11', 182, 76.40, 12.3, 57, '120/78', 53.6, 4.08, 19.4, 85, 'fit', 'Kondisi baik.', 8, '2026-11-09 15:38:25'),
(18, 10, '2026-07-29', 182, 77.10, 12.9, 58, '121/79', 52.8, 4.12, 18.8, 82, 'fit', 'Pramusim: kondisi baik.', 8, '2026-11-09 15:38:25'),
(19, 9, '2026-09-11', 185, 81.10, 11.8, 55, '119/77', 51.4, 4.26, 18.3, 81, 'fit', 'Ada kemajuan daya tahan.', 8, '2026-11-09 15:38:25'),
(20, 9, '2026-07-29', 185, 82.00, 12.4, 57, '120/78', 50.2, 4.31, 17.6, 78, 'fit', 'Pramusim: daya tahan perlu ditingkatkan.', 8, '2026-11-09 15:38:25'),
(21, 12, '2026-09-11', 178, 73.20, 12.1, 56, '119/77', 55.6, 3.88, 19.8, 86, 'fit', 'Kondisi baik.', 8, '2026-11-09 15:38:25'),
(22, 12, '2026-07-29', 178, 73.90, 12.5, 57, '120/78', 54.7, 3.92, 19.2, 84, 'fit', 'Pramusim: sprint tercepat di tim.', 8, '2026-11-09 15:38:25'),
(23, 11, '2026-09-11', 176, 70.50, 10.7, 51, '115/72', 59.8, 3.99, 21.6, 91, 'fit', 'Stabil di level atas.', 8, '2026-11-09 15:38:25'),
(24, 11, '2026-07-29', 176, 70.90, 10.9, 52, '116/73', 58.9, 4.02, 20.9, 89, 'fit', 'Pramusim: kondisi sangat baik.', 8, '2026-11-09 15:38:25'),
(32, 4, '2026-07-29', 174, 69.40, 11.4, 54, '118/76', 56.9, 3.98, 20.6, 87, 'fit', 'Pramusim: kondisi baik.', 3, '2026-11-09 15:38:25'),
(33, 4, '2026-09-11', 174, 68.90, 11.0, 53, '117/75', 57.6, 3.94, 21.2, 89, 'fit', 'Kondisi sangat baik.', 3, '2026-11-09 15:38:25'),
(35, 49, '2026-07-29', 189, 85.00, 11.5, NULL, NULL, 53.4, 4.02, 17.6, 46, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(36, 49, '2026-09-11', 189, 85.00, 11.5, NULL, NULL, 54.4, 3.99, 18.2, 52, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(37, 49, '2026-10-27', 189, 85.00, 11.5, NULL, NULL, 55.0, 3.97, 18.6, 55, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(38, 50, '2026-07-29', 183, 78.00, 13.4, NULL, NULL, 55.3, 4.03, 19.5, 57, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(39, 50, '2026-09-11', 183, 78.00, 13.4, NULL, NULL, 56.3, 4.00, 20.1, 62, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(40, 50, '2026-10-27', 183, 78.00, 13.4, NULL, NULL, 56.9, 3.98, 20.5, 66, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(41, 51, '2026-07-29', 172, 68.00, 11.8, NULL, NULL, 56.7, 3.99, 17.9, 55, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(42, 51, '2026-09-11', 172, 68.00, 11.8, NULL, NULL, 57.7, 3.96, 18.5, 60, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(43, 51, '2026-10-27', 172, 68.00, 11.8, NULL, NULL, 58.3, 3.94, 18.9, 64, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(44, 52, '2026-07-29', 175, 70.00, 13.0, NULL, NULL, 57.4, 3.97, 19.1, 62, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(45, 52, '2026-09-11', 175, 70.00, 13.0, NULL, NULL, 58.4, 3.94, 19.7, 67, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(46, 52, '2026-10-27', 175, 70.00, 13.0, NULL, NULL, 59.0, 3.92, 20.1, 71, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(47, 53, '2026-07-29', 176, 71.00, 10.0, NULL, NULL, 54.4, 3.97, 18.6, 54, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(48, 53, '2026-09-11', 176, 71.00, 10.0, NULL, NULL, 55.4, 3.94, 19.2, 60, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(49, 53, '2026-10-27', 176, 71.00, 10.0, NULL, NULL, 56.0, 3.92, 19.6, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(50, 54, '2026-07-29', 175, 72.00, 12.2, NULL, NULL, 56.6, 4.05, 18.3, 54, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(51, 54, '2026-09-11', 175, 72.00, 12.2, NULL, NULL, 57.6, 4.02, 18.9, 59, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(52, 54, '2026-10-27', 175, 72.00, 12.2, NULL, NULL, 58.2, 4.00, 19.3, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(53, 55, '2026-07-29', 168, 63.00, 12.1, NULL, NULL, 59.0, 4.06, 18.2, 58, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(54, 55, '2026-09-11', 168, 63.00, 12.1, NULL, NULL, 60.0, 4.03, 18.8, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(55, 55, '2026-10-27', 168, 63.00, 12.1, NULL, NULL, 60.6, 4.01, 19.2, 66, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(56, 56, '2026-07-29', 169, 64.00, 10.6, NULL, NULL, 56.5, 3.91, 19.2, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(57, 56, '2026-09-11', 169, 64.00, 10.6, NULL, NULL, 57.5, 3.88, 19.8, 68, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(58, 56, '2026-10-27', 169, 64.00, 10.6, NULL, NULL, 58.1, 3.86, 20.2, 72, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(59, 57, '2026-07-29', 177, 70.00, 9.8, NULL, NULL, 55.2, 3.99, 18.4, 54, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(60, 57, '2026-09-11', 177, 70.00, 9.8, NULL, NULL, 56.2, 3.96, 19.0, 59, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(61, 57, '2026-10-27', 177, 70.00, 9.8, NULL, NULL, 56.8, 3.94, 19.4, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(62, 58, '2026-07-29', 170, 65.00, 10.4, NULL, NULL, 56.8, 4.03, 19.0, 58, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(63, 58, '2026-09-11', 170, 65.00, 10.4, NULL, NULL, 57.8, 4.00, 19.6, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(64, 58, '2026-10-27', 170, 65.00, 10.4, NULL, NULL, 58.4, 3.98, 20.0, 67, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(65, 59, '2026-07-29', 182, 80.00, 13.3, NULL, NULL, 56.7, 4.04, 19.4, 59, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(66, 59, '2026-09-11', 182, 80.00, 13.3, NULL, NULL, 57.7, 4.01, 20.0, 64, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(67, 59, '2026-10-27', 182, 80.00, 13.3, NULL, NULL, 58.3, 3.99, 20.4, 67, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(68, 60, '2026-07-29', 190, 84.00, 12.2, NULL, NULL, 55.6, 4.05, 18.3, 52, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(69, 60, '2026-09-11', 190, 84.00, 12.2, NULL, NULL, 56.6, 4.02, 18.9, 57, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(70, 60, '2026-10-27', 190, 84.00, 12.2, NULL, NULL, 57.2, 4.00, 19.3, 61, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(71, 61, '2026-07-29', 179, 73.00, 10.9, NULL, NULL, 54.3, 3.88, 19.5, 61, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(72, 61, '2026-09-11', 179, 73.00, 10.9, NULL, NULL, 55.3, 3.85, 20.1, 67, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(73, 61, '2026-10-27', 179, 73.00, 10.9, NULL, NULL, 55.9, 3.83, 20.5, 70, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(74, 62, '2026-07-29', 166, 60.00, 13.2, NULL, NULL, 60.1, 4.05, 19.3, 64, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(75, 62, '2026-09-11', 166, 60.00, 13.2, NULL, NULL, 61.1, 4.02, 19.9, 70, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(76, 62, '2026-10-27', 166, 60.00, 13.2, NULL, NULL, 61.7, 4.00, 20.3, 73, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(77, 63, '2026-07-29', 186, 79.00, 12.8, NULL, NULL, 50.7, 4.22, 16.9, 30, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(78, 63, '2026-09-11', 186, 79.00, 12.8, NULL, NULL, 51.7, 4.19, 17.5, 35, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(79, 63, '2026-10-27', 186, 79.00, 12.8, NULL, NULL, 52.3, 4.17, 17.9, 39, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(80, 64, '2026-07-29', 184, 82.00, 12.0, NULL, NULL, 49.9, 4.20, 16.1, 26, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(81, 64, '2026-09-11', 184, 82.00, 12.0, NULL, NULL, 50.9, 4.17, 16.7, 32, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(82, 64, '2026-10-27', 184, 82.00, 12.0, NULL, NULL, 51.5, 4.15, 17.1, 35, 'terbatas', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(83, 65, '2026-09-11', 174, 66.00, 13.0, NULL, NULL, 58.4, 4.04, 19.7, 63, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29'),
(84, 65, '2026-10-27', 174, 66.00, 13.0, NULL, NULL, 59.0, 4.02, 20.1, 66, 'fit', 'Tes berkala tim.', 8, '2026-11-10 22:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `jenis` enum('izin','cuti','sakit','perubahan_data','klaim_medis','perpanjangan_kontrak','lainnya') NOT NULL DEFAULT 'lainnya',
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `ditinjau_oleh` int(10) UNSIGNED DEFAULT NULL,
  `ditinjau_pada` datetime DEFAULT NULL,
  `catatan_peninjau` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan`
--

INSERT INTO `pengajuan` (`id`, `user_id`, `jenis`, `judul`, `isi`, `tanggal_mulai`, `tanggal_selesai`, `lampiran`, `status`, `ditinjau_oleh`, `ditinjau_pada`, `catatan_peninjau`, `created_at`, `updated_at`) VALUES
(1, 11, 'izin', 'Izin keluarga', 'Mohon izin tidak mengikuti latihan karena acara keluarga.', '2026-11-12', '2026-11-12', NULL, 'menunggu', NULL, NULL, NULL, '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(2, 10, 'perpanjangan_kontrak', 'Pengajuan perpanjangan kontrak', 'Saya ingin memperpanjang kontrak untuk 2 musim ke depan.', NULL, NULL, NULL, 'disetujui', 6, '2026-11-08 22:29:56', 'Akan dibahas detailnya minggu depan.', '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(3, 4, 'cuti', 'Cuti pernikahan saudara', 'Mohon cuti 2 hari untuk menghadiri pernikahan saudara kandung.', '2026-11-28', '2026-11-29', NULL, 'menunggu', NULL, NULL, NULL, '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(4, 4, 'perubahan_data', 'Perubahan nomor telepon', 'Nomor telepon saya berganti, mohon diperbarui.', NULL, NULL, NULL, 'disetujui', 2, '2026-11-05 22:29:56', 'Data sudah diperbarui.', '2026-11-08 15:29:56', '2026-11-08 15:29:56'),
(6, 10, 'sakit', 'Sakit: cedera hamstring', 'Rehabilitasi cedera hamstring sesuai rekomendasi dokter tim.', '2026-11-03', '2026-11-24', NULL, 'disetujui', 2, '2026-11-04 00:00:00', 'Surat dokter tim terlampir di rekam medis.', '2026-11-03 02:15:00', '2026-11-09 16:14:38'),
(7, 9, 'sakit', 'Sakit: terkilir jari', 'Jari dibidai, tidak bisa latihan tangkapan.', '2026-08-05', '2026-08-06', NULL, 'disetujui', 2, '2026-08-06 00:00:00', NULL, '2026-08-05 02:15:00', '2026-11-09 16:14:38'),
(8, 12, 'cuti', 'Cuti tahunan', 'Pulang kampung saat jeda kompetisi.', '2026-12-22', '2026-12-26', NULL, 'menunggu', NULL, NULL, NULL, '2026-11-05 02:15:00', '2026-11-09 16:14:38'),
(9, 12, 'cuti', 'Cuti tahunan', 'Pulang kampung.', '2026-11-20', '2026-11-22', NULL, 'ditolak', 2, '2026-11-03 00:00:00', 'Bentrok dengan laga kandang 21 Nov. Silakan ajukan di jeda kompetisi.', '2026-11-02 02:15:00', '2026-11-09 16:14:38'),
(10, 7, 'cuti', 'Cuti akhir tahun', 'Liburan keluarga.', '2026-12-28', '2027-01-02', NULL, 'menunggu', NULL, NULL, NULL, '2026-11-04 02:15:00', '2026-11-09 16:14:38'),
(11, 11, 'cuti', 'Cuti tahunan', 'Umrah bersama keluarga.', '2026-06-08', '2026-06-14', NULL, 'disetujui', 2, '2026-05-21 00:00:00', NULL, '2026-05-20 02:15:00', '2026-11-09 16:14:38'),
(12, 3, 'izin', 'Izin seminar kedokteran olahraga', 'Menjadi pembicara seminar nasional.', '2026-11-19', '2026-11-19', NULL, 'disetujui', 2, '2026-10-29 00:00:00', 'Silakan, tim medis dibackup dr. Maya.', '2026-10-28 02:15:00', '2026-11-09 16:14:38'),
(13, 4, 'izin', 'Izin wisuda adik', 'Menghadiri wisuda adik di Medan.', '2026-12-11', '2026-12-11', NULL, 'menunggu', NULL, NULL, NULL, '2026-11-01 02:15:00', '2026-11-09 16:14:38'),
(14, 4, 'izin', 'Izin perpanjangan SIM', 'Mengurus perpanjangan SIM di Samsat.', '2026-10-16', '2026-10-16', NULL, 'disetujui', 2, '2026-10-13 00:00:00', NULL, '2026-10-12 02:15:00', '2026-11-09 16:14:38'),
(15, 4, 'cuti', 'Cuti tahunan', 'Libur keluarga setelah jeda kompetisi.', '2026-07-20', '2026-07-22', NULL, 'disetujui', 2, '2026-07-07 00:00:00', 'Selamat berlibur.', '2026-07-06 02:15:00', '2026-11-09 16:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `penggajian`
--

CREATE TABLE `penggajian` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `kontrak_id` int(10) UNSIGNED DEFAULT NULL,
  `periode` date NOT NULL,
  `gaji_pokok` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tunjangan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `potongan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_bersih` decimal(15,2) GENERATED ALWAYS AS (`gaji_pokok` + `tunjangan` + `bonus` - `potongan`) STORED,
  `status` enum('draft','disetujui','dibayar') NOT NULL DEFAULT 'draft',
  `tanggal_bayar` date DEFAULT NULL,
  `rekening_tujuan` varchar(60) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `dibuat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penggajian`
--

INSERT INTO `penggajian` (`id`, `user_id`, `kontrak_id`, `periode`, `gaji_pokok`, `tunjangan`, `bonus`, `potongan`, `status`, `tanggal_bayar`, `rekening_tujuan`, `catatan`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(1, 9, 1, '2026-09-01', 18000000.00, 1000000.00, 1000000.00, 1480000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 2505', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(2, 9, 1, '2026-10-01', 18000000.00, 1000000.00, 2000000.00, 1530000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 2505', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(3, 11, 2, '2026-09-01', 20000000.00, 1000000.00, 3500000.00, 1745000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 8343', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(4, 11, 2, '2026-10-01', 20000000.00, 1000000.00, 3000000.00, 2220000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 8343', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(5, 12, 3, '2026-09-01', 25000000.00, 1000000.00, 10500000.00, 2695000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 6262', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(6, 12, 3, '2026-10-01', 25000000.00, 1000000.00, 14000000.00, 2870000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 6262', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(7, 6, 4, '2026-09-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 8748', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(8, 6, 4, '2026-10-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 8748', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(9, 7, 5, '2026-09-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 6667', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(10, 7, 5, '2026-10-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 6667', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(11, 8, 6, '2026-09-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 4586', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(12, 8, 6, '2026-10-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 4586', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(13, 10, 8, '2026-09-01', 15000000.00, 1000000.00, 1950000.00, 1317500.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 0424', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(14, 10, 8, '2026-10-01', 15000000.00, 1000000.00, 1600000.00, 2050000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 0424', NULL, 6, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(16, 4, 9, '2026-09-01', 22000000.00, 1000000.00, 3500000.00, 2135000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 2910', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(17, 4, 9, '2026-10-01', 22000000.00, 1000000.00, 3000000.00, 2360000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 2910', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(18, 1, 10, '2026-09-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 9153', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(19, 1, 10, '2026-10-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 9153', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(20, 2, 11, '2026-09-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 7072', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(21, 2, 11, '2026-10-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 7072', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(22, 3, 12, '2026-09-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 4991', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(23, 3, 12, '2026-10-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 4991', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(24, 5, 13, '2026-09-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 0829', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(25, 5, 13, '2026-10-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 0829', NULL, 1, '2026-11-08 15:29:56', '2026-11-09 16:14:38'),
(34, 9, 1, '2026-05-01', 18000000.00, 1000000.00, 500000.00, 1455000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 2505', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(35, 11, 2, '2026-05-01', 20000000.00, 1000000.00, 500000.00, 1595000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 8343', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(36, 12, 3, '2026-05-01', 25000000.00, 1000000.00, 3500000.00, 2095000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 6262', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(37, 6, 4, '2026-05-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 8748', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(38, 7, 5, '2026-05-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 6667', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(39, 8, 6, '2026-05-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 4586', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(40, 10, 8, '2026-05-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 0424', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(41, 1, 10, '2026-05-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 9153', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(42, 2, 11, '2026-05-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 7072', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(43, 3, 12, '2026-05-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 4991', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(44, 5, 13, '2026-05-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 0829', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(45, 4, 31, '2026-05-01', 17000000.00, 800000.00, 1150000.00, 1657500.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 2910', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(46, 9, 1, '2026-06-01', 18000000.00, 1000000.00, 0.00, 1430000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 2505', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(47, 11, 2, '2026-06-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 8343', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(48, 12, 3, '2026-06-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 6262', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(49, 6, 4, '2026-06-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 8748', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(50, 7, 5, '2026-06-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 6667', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(51, 8, 6, '2026-06-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 4586', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(52, 10, 8, '2026-06-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 0424', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(53, 1, 10, '2026-06-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 9153', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(54, 2, 11, '2026-06-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 7072', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(55, 3, 12, '2026-06-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 4991', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(56, 5, 13, '2026-06-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 0829', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(57, 4, 31, '2026-06-01', 17000000.00, 800000.00, 0.00, 1350000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 2910', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(58, 9, 1, '2026-07-01', 18000000.00, 1000000.00, 0.00, 1430000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 2505', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(59, 11, 2, '2026-07-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 8343', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(60, 12, 3, '2026-07-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 6262', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(61, 6, 4, '2026-07-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 8748', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(62, 7, 5, '2026-07-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 6667', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(63, 8, 6, '2026-07-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 4586', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(64, 10, 8, '2026-07-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 0424', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(65, 4, 9, '2026-07-01', 22000000.00, 1000000.00, 66000000.00, 5010000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 2910', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(66, 1, 10, '2026-07-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 9153', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(67, 2, 11, '2026-07-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 7072', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(68, 3, 12, '2026-07-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 4991', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(69, 5, 13, '2026-07-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 0829', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(70, 9, 1, '2026-08-01', 18000000.00, 1000000.00, 1500000.00, 1755000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 2505', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(71, 11, 2, '2026-08-01', 20000000.00, 1000000.00, 1500000.00, 1895000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 8343', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(72, 12, 3, '2026-08-01', 25000000.00, 1000000.00, 7500000.00, 2295000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 6262', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(73, 6, 4, '2026-08-01', 30000000.00, 1500000.00, 0.00, 2295000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 8748', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(74, 7, 5, '2026-08-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 6667', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(75, 8, 6, '2026-08-01', 22000000.00, 1500000.00, 0.00, 1735000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 4586', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(76, 10, 8, '2026-08-01', 15000000.00, 1000000.00, 1200000.00, 1780000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 0424', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(77, 4, 9, '2026-08-01', 22000000.00, 1000000.00, 2500000.00, 1835000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 2910', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(78, 1, 10, '2026-08-01', 32000000.00, 1500000.00, 0.00, 2435000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 9153', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(79, 2, 11, '2026-08-01', 15000000.00, 1500000.00, 0.00, 1245000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 7072', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(80, 3, 12, '2026-08-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 4991', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(81, 5, 13, '2026-08-01', 12000000.00, 1500000.00, 0.00, 1035000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 0829', NULL, 2, '2026-11-09 16:14:38', '2026-11-09 16:14:38'),
(102, 58, 38, '2026-05-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(103, 51, 39, '2026-05-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(104, 50, 40, '2026-05-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(105, 52, 42, '2026-05-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(106, 57, 43, '2026-05-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(107, 54, 44, '2026-05-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(108, 49, 45, '2026-05-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(109, 59, 46, '2026-05-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(110, 56, 47, '2026-05-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(111, 60, 51, '2026-05-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(112, 55, 52, '2026-05-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(113, 64, 53, '2026-05-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(114, 68, 71, '2026-05-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(115, 70, 73, '2026-05-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-05-31', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(116, 71, 74, '2026-05-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(117, 66, 79, '2026-05-01', 17500000.00, 1500000.00, 0.00, 1420000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(118, 69, 80, '2026-05-01', 28000000.00, 1500000.00, 0.00, 2155000.00, 'dibayar', '2026-05-31', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(119, 58, 38, '2026-06-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(120, 51, 39, '2026-06-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(121, 50, 40, '2026-06-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(122, 52, 42, '2026-06-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(123, 57, 43, '2026-06-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(124, 54, 44, '2026-06-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(125, 49, 45, '2026-06-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(126, 59, 46, '2026-06-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(127, 56, 47, '2026-06-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(128, 60, 51, '2026-06-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(129, 55, 52, '2026-06-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(130, 64, 53, '2026-06-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(131, 66, 69, '2026-06-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(132, 67, 70, '2026-06-01', 14000000.00, 1500000.00, 0.00, 1175000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 1807', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(133, 68, 71, '2026-06-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(134, 69, 72, '2026-06-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-06-30', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(135, 70, 73, '2026-06-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-06-30', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(136, 71, 74, '2026-06-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(137, 58, 38, '2026-07-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(138, 51, 39, '2026-07-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(139, 50, 40, '2026-07-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(140, 62, 41, '2026-07-01', 12000000.00, 1000000.00, 36000000.00, 2810000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 2212', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(141, 52, 42, '2026-07-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(142, 57, 43, '2026-07-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(143, 54, 44, '2026-07-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(144, 49, 45, '2026-07-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(145, 59, 46, '2026-07-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(146, 56, 47, '2026-07-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(147, 61, 48, '2026-07-01', 18000000.00, 1000000.00, 54000000.00, 4130000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 4293', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(148, 63, 49, '2026-07-01', 15000000.00, 1000000.00, 45000000.00, 3470000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 0131', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(149, 60, 51, '2026-07-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(150, 55, 52, '2026-07-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(151, 64, 53, '2026-07-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(152, 66, 69, '2026-07-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(153, 67, 70, '2026-07-01', 14000000.00, 1500000.00, 0.00, 1175000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 1807', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(154, 68, 71, '2026-07-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(155, 69, 72, '2026-07-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-07-31', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(156, 70, 73, '2026-07-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-07-31', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(157, 71, 74, '2026-07-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(158, 58, 38, '2026-08-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(159, 51, 39, '2026-08-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(160, 50, 40, '2026-08-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(161, 62, 41, '2026-08-01', 12000000.00, 1000000.00, 0.00, 1010000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 2212', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(162, 52, 42, '2026-08-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(163, 57, 43, '2026-08-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(164, 54, 44, '2026-08-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(165, 49, 45, '2026-08-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(166, 59, 46, '2026-08-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(167, 56, 47, '2026-08-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(168, 61, 48, '2026-08-01', 18000000.00, 1000000.00, 0.00, 1430000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 4293', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(169, 63, 49, '2026-08-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 0131', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(170, 65, 50, '2026-08-01', 10000000.00, 1000000.00, 30000000.00, 2350000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 5969', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(171, 60, 51, '2026-08-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(172, 55, 52, '2026-08-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(173, 64, 53, '2026-08-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(174, 66, 69, '2026-08-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(175, 67, 70, '2026-08-01', 14000000.00, 1500000.00, 0.00, 1175000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 1807', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(176, 68, 71, '2026-08-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(177, 69, 72, '2026-08-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-08-31', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(178, 70, 73, '2026-08-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-08-31', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(179, 71, 74, '2026-08-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(180, 58, 38, '2026-09-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(181, 51, 39, '2026-09-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(182, 50, 40, '2026-09-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(183, 62, 41, '2026-09-01', 12000000.00, 1000000.00, 0.00, 1010000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 2212', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(184, 52, 42, '2026-09-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(185, 57, 43, '2026-09-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(186, 54, 44, '2026-09-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(187, 49, 45, '2026-09-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(188, 59, 46, '2026-09-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(189, 56, 47, '2026-09-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(190, 61, 48, '2026-09-01', 18000000.00, 1000000.00, 0.00, 1430000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 4293', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(191, 63, 49, '2026-09-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 0131', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(192, 65, 50, '2026-09-01', 10000000.00, 1000000.00, 0.00, 850000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 5969', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(193, 60, 51, '2026-09-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(194, 55, 52, '2026-09-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(195, 64, 53, '2026-09-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(196, 66, 69, '2026-09-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(197, 67, 70, '2026-09-01', 14000000.00, 1500000.00, 0.00, 1175000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 1807', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(198, 68, 71, '2026-09-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(199, 69, 72, '2026-09-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-09-30', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(200, 70, 73, '2026-09-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-09-30', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(201, 71, 74, '2026-09-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(202, 58, 38, '2026-10-01', 55000000.00, 1000000.00, 0.00, 4020000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 0536', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(203, 51, 39, '2026-10-01', 30000000.00, 1000000.00, 0.00, 2270000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 5103', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(204, 50, 40, '2026-10-01', 45000000.00, 1000000.00, 0.00, 3320000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 7184', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(205, 62, 41, '2026-10-01', 12000000.00, 1000000.00, 0.00, 1010000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 2212', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(206, 52, 42, '2026-10-01', 20000000.00, 1000000.00, 0.00, 1570000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 3022', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(207, 57, 43, '2026-10-01', 25000000.00, 1000000.00, 0.00, 1920000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 2617', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(208, 54, 44, '2026-10-01', 40000000.00, 1000000.00, 0.00, 2970000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 8860', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(209, 49, 45, '2026-10-01', 120000000.00, 1000000.00, 0.00, 8570000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 9265', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(210, 59, 46, '2026-10-01', 150000000.00, 1000000.00, 0.00, 10670000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 8455', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(211, 56, 47, '2026-10-01', 60000000.00, 1000000.00, 0.00, 4370000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 4698', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(212, 61, 48, '2026-10-01', 18000000.00, 1000000.00, 0.00, 1430000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 4293', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(213, 63, 49, '2026-10-01', 15000000.00, 1000000.00, 0.00, 1220000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 0131', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(214, 65, 50, '2026-10-01', 10000000.00, 1000000.00, 0.00, 850000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 5969', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(215, 60, 51, '2026-10-01', 140000000.00, 1000000.00, 0.00, 9970000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 6374', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(216, 55, 52, '2026-10-01', 50000000.00, 1000000.00, 0.00, 3670000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 6779', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(217, 64, 53, '2026-10-01', 35000000.00, 1000000.00, 0.00, 2620000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 8050', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(218, 66, 69, '2026-10-01', 21000000.00, 1500000.00, 0.00, 1665000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 3888', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(219, 67, 70, '2026-10-01', 14000000.00, 1500000.00, 0.00, 1175000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 1807', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(220, 68, 71, '2026-10-01', 9000000.00, 1500000.00, 0.00, 795000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 9726', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(221, 69, 72, '2026-10-01', 35000000.00, 1500000.00, 0.00, 2645000.00, 'dibayar', '2026-10-31', 'Bank BCA •••• 7645', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(222, 70, 73, '2026-10-01', 8000000.00, 1500000.00, 0.00, 715000.00, 'dibayar', '2026-10-31', 'Bank Mandiri •••• 5564', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(223, 71, 74, '2026-10-01', 7000000.00, 1500000.00, 0.00, 635000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 3483', NULL, 2, '2026-11-10 15:04:52', '2026-11-10 15:04:52'),
(302, 53, 91, '2026-05-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-05-31', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05'),
(303, 53, 91, '2026-06-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-06-30', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05'),
(304, 53, 91, '2026-07-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-07-31', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05'),
(305, 53, 91, '2026-08-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-08-31', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05'),
(306, 53, 91, '2026-09-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-09-30', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05'),
(307, 53, 91, '2026-10-01', 95000000.00, 1000000.00, 0.00, 6820000.00, 'dibayar', '2026-10-31', 'Bank BNI •••• 0941', NULL, 2, '2026-11-10 15:20:04', '2026-11-10 15:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `penggajian_rincian`
--

CREATE TABLE `penggajian_rincian` (
  `id` int(10) UNSIGNED NOT NULL,
  `penggajian_id` int(10) UNSIGNED NOT NULL,
  `kode` varchar(30) NOT NULL,
  `kategori` enum('gaji_pokok','tunjangan','bonus','potongan') NOT NULL,
  `keterangan` varchar(120) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00,
  `urutan` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `penggajian_rincian`
--

INSERT INTO `penggajian_rincian` (`id`, `penggajian_id`, `kode`, `kategori`, `keterangan`, `jumlah`, `urutan`) VALUES
(1, 1, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(2, 2, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(3, 34, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(4, 46, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(5, 58, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(6, 70, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(7, 3, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(8, 4, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(9, 35, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(10, 47, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(11, 59, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(12, 71, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(13, 5, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(14, 6, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(15, 36, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(16, 48, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(17, 60, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(18, 72, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(19, 7, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(20, 8, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(21, 37, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(22, 49, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(23, 61, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(24, 73, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(25, 9, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(26, 10, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(27, 38, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(28, 50, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(29, 62, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(30, 74, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(31, 11, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(32, 12, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(33, 39, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(34, 51, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(35, 63, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(36, 75, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(37, 13, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(38, 14, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(39, 40, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(40, 52, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(41, 64, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(42, 76, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(43, 16, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(44, 17, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(45, 65, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(46, 77, 'gaji', 'gaji_pokok', 'Gaji pokok', 22000000.00, 1),
(47, 18, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(48, 19, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(49, 41, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(50, 53, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(51, 66, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(52, 78, 'gaji', 'gaji_pokok', 'Gaji pokok', 32000000.00, 1),
(53, 20, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(54, 21, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(55, 42, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(56, 54, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(57, 67, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(58, 79, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(59, 22, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(60, 23, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(61, 43, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(62, 55, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(63, 68, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(64, 80, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(65, 24, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(66, 25, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(67, 44, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(68, 56, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(69, 69, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(70, 81, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(71, 45, 'gaji', 'gaji_pokok', 'Gaji pokok', 17000000.00, 1),
(72, 57, 'gaji', 'gaji_pokok', 'Gaji pokok', 17000000.00, 1),
(128, 1, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(129, 2, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(130, 34, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(131, 46, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(132, 58, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(133, 70, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(134, 3, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(135, 4, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(136, 35, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(137, 47, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(138, 59, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(139, 71, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(140, 5, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(141, 6, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(142, 36, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(143, 48, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(144, 60, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(145, 72, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(146, 7, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(147, 8, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(148, 37, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(149, 49, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(150, 61, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(151, 73, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(152, 9, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(153, 10, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(154, 38, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(155, 50, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(156, 62, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(157, 74, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(158, 11, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(159, 12, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(160, 39, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(161, 51, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(162, 63, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(163, 75, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(164, 13, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(165, 14, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(166, 40, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(167, 52, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(168, 64, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(169, 76, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(170, 16, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(171, 17, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(172, 65, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(173, 77, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(174, 18, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(175, 19, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(176, 41, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(177, 53, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(178, 66, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(179, 78, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(180, 20, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(181, 21, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(182, 42, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(183, 54, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(184, 67, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(185, 79, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(186, 22, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(187, 23, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(188, 43, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(189, 55, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(190, 68, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(191, 80, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(192, 24, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(193, 25, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(194, 44, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(195, 56, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(196, 69, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(197, 81, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(198, 45, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 800000.00, 2),
(199, 57, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 800000.00, 2),
(255, 3, 'bonus_gol', 'bonus', 'Bonus gol (2 gol)', 2000000.00, 3),
(256, 4, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 1000000.00, 3),
(257, 5, 'bonus_gol', 'bonus', 'Bonus gol (3 gol)', 9000000.00, 3),
(258, 6, 'bonus_gol', 'bonus', 'Bonus gol (4 gol)', 12000000.00, 3),
(259, 36, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 3000000.00, 3),
(260, 72, 'bonus_gol', 'bonus', 'Bonus gol (2 gol)', 6000000.00, 3),
(261, 13, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 750000.00, 3),
(262, 16, 'bonus_gol', 'bonus', 'Bonus gol (2 gol)', 2000000.00, 3),
(263, 17, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 1000000.00, 3),
(264, 77, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 1000000.00, 3),
(265, 45, 'bonus_gol', 'bonus', 'Bonus gol (1 gol)', 750000.00, 3),
(270, 1, 'bonus_main', 'bonus', 'Bonus penampilan (2 laga)', 1000000.00, 4),
(271, 2, 'bonus_main', 'bonus', 'Bonus penampilan (4 laga)', 2000000.00, 4),
(272, 34, 'bonus_main', 'bonus', 'Bonus penampilan (1 laga)', 500000.00, 4),
(273, 70, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(274, 3, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(275, 4, 'bonus_main', 'bonus', 'Bonus penampilan (4 laga)', 2000000.00, 4),
(276, 35, 'bonus_main', 'bonus', 'Bonus penampilan (1 laga)', 500000.00, 4),
(277, 71, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(278, 5, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(279, 6, 'bonus_main', 'bonus', 'Bonus penampilan (4 laga)', 2000000.00, 4),
(280, 36, 'bonus_main', 'bonus', 'Bonus penampilan (1 laga)', 500000.00, 4),
(281, 72, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(282, 13, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1200000.00, 4),
(283, 14, 'bonus_main', 'bonus', 'Bonus penampilan (4 laga)', 1600000.00, 4),
(284, 76, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1200000.00, 4),
(285, 16, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(286, 17, 'bonus_main', 'bonus', 'Bonus penampilan (4 laga)', 2000000.00, 4),
(287, 77, 'bonus_main', 'bonus', 'Bonus penampilan (3 laga)', 1500000.00, 4),
(288, 45, 'bonus_main', 'bonus', 'Bonus penampilan (1 laga)', 400000.00, 4),
(301, 65, 'bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', 66000000.00, 5),
(302, 1, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(303, 2, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(304, 34, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(305, 46, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(306, 58, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(307, 70, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(308, 3, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(309, 4, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(310, 35, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(311, 47, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(312, 59, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(313, 71, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(314, 5, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(315, 6, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(316, 36, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(317, 48, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(318, 60, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(319, 72, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(320, 7, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(321, 8, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(322, 37, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(323, 49, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(324, 61, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(325, 73, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(326, 9, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(327, 10, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(328, 38, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(329, 50, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(330, 62, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(331, 74, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(332, 11, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(333, 12, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(334, 39, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(335, 51, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(336, 63, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(337, 75, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(338, 13, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(339, 14, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(340, 40, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(341, 52, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(342, 64, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(343, 76, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(344, 16, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(345, 17, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(346, 65, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(347, 77, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(348, 18, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(349, 19, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(350, 41, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(351, 53, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(352, 66, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(353, 78, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(354, 20, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(355, 21, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(356, 42, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(357, 54, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(358, 67, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(359, 79, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(360, 22, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(361, 23, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(362, 43, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(363, 55, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(364, 68, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(365, 80, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(366, 24, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(367, 25, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(368, 44, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(369, 56, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(370, 69, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(371, 81, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(372, 45, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(373, 57, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(429, 1, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(430, 2, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(431, 34, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(432, 46, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(433, 58, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(434, 70, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(435, 3, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(436, 4, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(437, 35, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(438, 47, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(439, 59, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(440, 71, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(441, 5, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(442, 6, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(443, 36, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(444, 48, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(445, 60, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(446, 72, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(447, 7, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(448, 8, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(449, 37, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(450, 49, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(451, 61, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(452, 73, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(453, 9, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(454, 10, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(455, 38, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(456, 50, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(457, 62, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(458, 74, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(459, 11, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(460, 12, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(461, 39, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(462, 51, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(463, 63, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(464, 75, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(465, 13, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(466, 14, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(467, 40, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(468, 52, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(469, 64, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(470, 76, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(471, 16, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(472, 17, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(473, 65, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(474, 77, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 440000.00, 12),
(475, 18, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(476, 19, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(477, 41, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(478, 53, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(479, 66, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(480, 78, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 640000.00, 12),
(481, 20, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(482, 21, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(483, 42, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(484, 54, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(485, 67, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(486, 79, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(487, 22, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(488, 23, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(489, 43, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(490, 55, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(491, 68, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(492, 80, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(493, 24, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(494, 25, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(495, 44, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(496, 56, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(497, 69, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(498, 81, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(499, 45, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 340000.00, 12),
(500, 57, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 340000.00, 12),
(556, 45, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(557, 16, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(558, 17, 'denda_kartu', 'potongan', 'Denda kartu (2 kuning)', 500000.00, 14),
(559, 70, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(560, 76, 'denda_kartu', 'potongan', 'Denda kartu (2 kuning)', 500000.00, 14),
(561, 14, 'denda_kartu', 'potongan', 'Denda kartu (3 kuning)', 750000.00, 14),
(562, 71, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(563, 4, 'denda_kartu', 'potongan', 'Denda kartu (2 kuning)', 500000.00, 14),
(564, 5, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(565, 6, 'denda_kartu', 'potongan', 'Denda kartu (1 kuning)', 250000.00, 14),
(571, 1, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1000000.00, 10),
(572, 2, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(573, 3, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1225000.00, 10),
(574, 4, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1200000.00, 10),
(575, 5, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(576, 6, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2000000.00, 10),
(577, 7, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(578, 8, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(579, 9, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(580, 10, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(581, 11, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(582, 12, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(583, 13, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 897500.00, 10),
(584, 14, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 880000.00, 10),
(585, 16, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1325000.00, 10),
(586, 17, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(587, 18, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(588, 19, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(589, 20, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(590, 21, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(591, 22, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(592, 23, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(593, 24, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(594, 25, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(595, 34, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 975000.00, 10),
(596, 35, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1075000.00, 10),
(597, 36, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1475000.00, 10),
(598, 37, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(599, 38, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(600, 39, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(601, 40, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(602, 41, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(603, 42, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(604, 43, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(605, 44, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(606, 45, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 947500.00, 10),
(607, 46, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(608, 47, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(609, 48, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(610, 49, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(611, 50, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(612, 51, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(613, 52, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(614, 53, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(615, 54, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(616, 55, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(617, 56, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(618, 57, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 890000.00, 10),
(619, 58, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(620, 59, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(621, 60, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(622, 61, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(623, 62, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(624, 63, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(625, 64, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(626, 65, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4450000.00, 10),
(627, 66, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(628, 67, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(629, 68, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(630, 69, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(631, 70, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1025000.00, 10),
(632, 71, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(633, 72, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(634, 73, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1575000.00, 10),
(635, 74, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(636, 75, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1175000.00, 10),
(637, 76, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 860000.00, 10),
(638, 77, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1275000.00, 10),
(639, 78, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1675000.00, 10),
(640, 79, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 825000.00, 10),
(641, 80, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(642, 81, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 675000.00, 10),
(707, 102, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(708, 119, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(709, 137, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(710, 158, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(711, 180, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(712, 202, 'gaji', 'gaji_pokok', 'Gaji pokok', 55000000.00, 1),
(713, 103, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(714, 120, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(715, 138, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(716, 159, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(717, 181, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(718, 203, 'gaji', 'gaji_pokok', 'Gaji pokok', 30000000.00, 1),
(719, 104, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(720, 121, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(721, 139, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(722, 160, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(723, 182, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(724, 204, 'gaji', 'gaji_pokok', 'Gaji pokok', 45000000.00, 1),
(725, 140, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(726, 161, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(727, 183, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(728, 205, 'gaji', 'gaji_pokok', 'Gaji pokok', 12000000.00, 1),
(729, 105, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(730, 122, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(731, 141, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(732, 162, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(733, 184, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(734, 206, 'gaji', 'gaji_pokok', 'Gaji pokok', 20000000.00, 1),
(735, 106, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(736, 123, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(737, 142, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(738, 163, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(739, 185, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(740, 207, 'gaji', 'gaji_pokok', 'Gaji pokok', 25000000.00, 1),
(741, 107, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(742, 124, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(743, 143, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(744, 164, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(745, 186, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(746, 208, 'gaji', 'gaji_pokok', 'Gaji pokok', 40000000.00, 1),
(747, 108, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(748, 125, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(749, 144, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(750, 165, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(751, 187, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(752, 209, 'gaji', 'gaji_pokok', 'Gaji pokok', 120000000.00, 1),
(753, 109, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(754, 126, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(755, 145, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(756, 166, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(757, 188, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(758, 210, 'gaji', 'gaji_pokok', 'Gaji pokok', 150000000.00, 1),
(759, 110, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(760, 127, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(761, 146, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(762, 167, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(763, 189, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(764, 211, 'gaji', 'gaji_pokok', 'Gaji pokok', 60000000.00, 1),
(765, 147, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(766, 168, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(767, 190, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(768, 212, 'gaji', 'gaji_pokok', 'Gaji pokok', 18000000.00, 1),
(769, 148, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(770, 169, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(771, 191, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(772, 213, 'gaji', 'gaji_pokok', 'Gaji pokok', 15000000.00, 1),
(773, 170, 'gaji', 'gaji_pokok', 'Gaji pokok', 10000000.00, 1),
(774, 192, 'gaji', 'gaji_pokok', 'Gaji pokok', 10000000.00, 1),
(775, 214, 'gaji', 'gaji_pokok', 'Gaji pokok', 10000000.00, 1),
(776, 111, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(777, 128, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(778, 149, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(779, 171, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(780, 193, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(781, 215, 'gaji', 'gaji_pokok', 'Gaji pokok', 140000000.00, 1),
(782, 112, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(783, 129, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(784, 150, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(785, 172, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(786, 194, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(787, 216, 'gaji', 'gaji_pokok', 'Gaji pokok', 50000000.00, 1),
(788, 113, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(789, 130, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(790, 151, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(791, 173, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(792, 195, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(793, 217, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(794, 131, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(795, 152, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(796, 174, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(797, 196, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(798, 218, 'gaji', 'gaji_pokok', 'Gaji pokok', 21000000.00, 1),
(799, 132, 'gaji', 'gaji_pokok', 'Gaji pokok', 14000000.00, 1),
(800, 153, 'gaji', 'gaji_pokok', 'Gaji pokok', 14000000.00, 1),
(801, 175, 'gaji', 'gaji_pokok', 'Gaji pokok', 14000000.00, 1),
(802, 197, 'gaji', 'gaji_pokok', 'Gaji pokok', 14000000.00, 1),
(803, 219, 'gaji', 'gaji_pokok', 'Gaji pokok', 14000000.00, 1),
(804, 114, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(805, 133, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(806, 154, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(807, 176, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(808, 198, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(809, 220, 'gaji', 'gaji_pokok', 'Gaji pokok', 9000000.00, 1),
(810, 134, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(811, 155, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(812, 177, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(813, 199, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(814, 221, 'gaji', 'gaji_pokok', 'Gaji pokok', 35000000.00, 1),
(815, 115, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(816, 135, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(817, 156, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(818, 178, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(819, 200, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(820, 222, 'gaji', 'gaji_pokok', 'Gaji pokok', 8000000.00, 1),
(821, 116, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(822, 136, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(823, 157, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(824, 179, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(825, 201, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(826, 223, 'gaji', 'gaji_pokok', 'Gaji pokok', 7000000.00, 1),
(827, 117, 'gaji', 'gaji_pokok', 'Gaji pokok', 17500000.00, 1),
(828, 118, 'gaji', 'gaji_pokok', 'Gaji pokok', 28000000.00, 1),
(834, 102, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(835, 119, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(836, 137, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(837, 158, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(838, 180, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(839, 202, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(840, 103, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(841, 120, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(842, 138, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(843, 159, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(844, 181, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(845, 203, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(846, 104, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(847, 121, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(848, 139, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(849, 160, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(850, 182, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(851, 204, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(852, 140, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(853, 161, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(854, 183, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(855, 205, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(856, 105, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(857, 122, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(858, 141, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(859, 162, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(860, 184, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(861, 206, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(862, 106, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(863, 123, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(864, 142, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(865, 163, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(866, 185, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(867, 207, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(868, 107, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(869, 124, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(870, 143, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(871, 164, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(872, 186, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(873, 208, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(874, 108, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(875, 125, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(876, 144, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(877, 165, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(878, 187, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(879, 209, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(880, 109, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(881, 126, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(882, 145, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(883, 166, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(884, 188, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(885, 210, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(886, 110, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(887, 127, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(888, 146, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(889, 167, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(890, 189, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(891, 211, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(892, 147, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(893, 168, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(894, 190, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(895, 212, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(896, 148, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(897, 169, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(898, 191, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(899, 213, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(900, 170, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(901, 192, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(902, 214, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(903, 111, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(904, 128, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(905, 149, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(906, 171, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(907, 193, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(908, 215, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(909, 112, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(910, 129, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(911, 150, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(912, 172, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(913, 194, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(914, 216, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(915, 113, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(916, 130, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(917, 151, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(918, 173, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(919, 195, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(920, 217, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(921, 131, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(922, 152, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(923, 174, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(924, 196, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(925, 218, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(926, 132, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(927, 153, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(928, 175, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(929, 197, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(930, 219, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(931, 114, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(932, 133, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(933, 154, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(934, 176, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(935, 198, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(936, 220, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(937, 134, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(938, 155, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(939, 177, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(940, 199, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(941, 221, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(942, 115, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(943, 135, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(944, 156, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(945, 178, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(946, 200, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(947, 222, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(948, 116, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(949, 136, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(950, 157, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(951, 179, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(952, 201, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(953, 223, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(954, 117, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(955, 118, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1500000.00, 2),
(963, 140, 'bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', 36000000.00, 5),
(964, 147, 'bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', 54000000.00, 5),
(965, 148, 'bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', 45000000.00, 5),
(966, 170, 'bonus_ttd', 'bonus', 'Bonus tanda tangan kontrak', 30000000.00, 5),
(970, 102, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(971, 119, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(972, 137, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(973, 158, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(974, 180, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(975, 202, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(976, 103, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(977, 120, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(978, 138, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(979, 159, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(980, 181, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(981, 203, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(982, 104, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(983, 121, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(984, 139, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(985, 160, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(986, 182, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(987, 204, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(988, 140, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(989, 161, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(990, 183, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(991, 205, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(992, 105, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(993, 122, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(994, 141, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(995, 162, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(996, 184, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(997, 206, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(998, 106, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(999, 123, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1000, 142, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1001, 163, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1002, 185, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1003, 207, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1004, 107, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1005, 124, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1006, 143, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1007, 164, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1008, 186, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11);
INSERT INTO `penggajian_rincian` (`id`, `penggajian_id`, `kode`, `kategori`, `keterangan`, `jumlah`, `urutan`) VALUES
(1009, 208, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1010, 108, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1011, 125, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1012, 144, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1013, 165, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1014, 187, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1015, 209, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1016, 109, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1017, 126, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1018, 145, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1019, 166, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1020, 188, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1021, 210, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1022, 110, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1023, 127, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1024, 146, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1025, 167, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1026, 189, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1027, 211, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1028, 147, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1029, 168, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1030, 190, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1031, 212, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1032, 148, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1033, 169, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1034, 191, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1035, 213, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1036, 170, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 100000.00, 11),
(1037, 192, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 100000.00, 11),
(1038, 214, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 100000.00, 11),
(1039, 111, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1040, 128, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1041, 149, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1042, 171, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1043, 193, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1044, 215, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1045, 112, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1046, 129, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1047, 150, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1048, 172, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1049, 194, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1050, 216, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1051, 113, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1052, 130, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1053, 151, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1054, 173, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1055, 195, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1056, 217, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1057, 131, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1058, 152, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1059, 174, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1060, 196, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1061, 218, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1062, 132, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1063, 153, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1064, 175, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1065, 197, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1066, 219, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1067, 114, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1068, 133, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1069, 154, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1070, 176, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1071, 198, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1072, 220, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 90000.00, 11),
(1073, 134, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1074, 155, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1075, 177, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1076, 199, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1077, 221, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1078, 115, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1079, 135, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1080, 156, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1081, 178, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1082, 200, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1083, 222, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 80000.00, 11),
(1084, 116, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1085, 136, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1086, 157, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1087, 179, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1088, 201, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1089, 223, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 70000.00, 11),
(1090, 117, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1091, 118, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1097, 102, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1098, 119, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1099, 137, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1100, 158, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1101, 180, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1102, 202, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1100000.00, 12),
(1103, 103, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1104, 120, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1105, 138, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1106, 159, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1107, 181, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1108, 203, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 600000.00, 12),
(1109, 104, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1110, 121, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1111, 139, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1112, 160, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1113, 182, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1114, 204, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 900000.00, 12),
(1115, 140, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(1116, 161, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(1117, 183, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(1118, 205, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 240000.00, 12),
(1119, 105, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1120, 122, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1121, 141, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1122, 162, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1123, 184, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1124, 206, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 400000.00, 12),
(1125, 106, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1126, 123, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1127, 142, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1128, 163, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1129, 185, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1130, 207, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 500000.00, 12),
(1131, 107, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1132, 124, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1133, 143, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1134, 164, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1135, 186, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1136, 208, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 800000.00, 12),
(1137, 108, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1138, 125, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1139, 144, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1140, 165, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1141, 187, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1142, 209, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2400000.00, 12),
(1143, 109, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1144, 126, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1145, 145, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1146, 166, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1147, 188, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1148, 210, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 3000000.00, 12),
(1149, 110, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1150, 127, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1151, 146, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1152, 167, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1153, 189, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1154, 211, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1200000.00, 12),
(1155, 147, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(1156, 168, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(1157, 190, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(1158, 212, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 360000.00, 12),
(1159, 148, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(1160, 169, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(1161, 191, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(1162, 213, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 300000.00, 12),
(1163, 170, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 200000.00, 12),
(1164, 192, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 200000.00, 12),
(1165, 214, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 200000.00, 12),
(1166, 111, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1167, 128, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1168, 149, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1169, 171, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1170, 193, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1171, 215, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 2800000.00, 12),
(1172, 112, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1173, 129, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1174, 150, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1175, 172, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1176, 194, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1177, 216, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1000000.00, 12),
(1178, 113, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1179, 130, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1180, 151, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1181, 173, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1182, 195, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1183, 217, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1184, 131, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(1185, 152, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(1186, 174, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(1187, 196, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(1188, 218, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 420000.00, 12),
(1189, 132, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 280000.00, 12),
(1190, 153, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 280000.00, 12),
(1191, 175, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 280000.00, 12),
(1192, 197, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 280000.00, 12),
(1193, 219, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 280000.00, 12),
(1194, 114, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1195, 133, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1196, 154, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1197, 176, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1198, 198, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1199, 220, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 180000.00, 12),
(1200, 134, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1201, 155, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1202, 177, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1203, 199, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1204, 221, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 700000.00, 12),
(1205, 115, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1206, 135, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1207, 156, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1208, 178, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1209, 200, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1210, 222, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 160000.00, 12),
(1211, 116, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1212, 136, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1213, 157, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1214, 179, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1215, 201, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1216, 223, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 140000.00, 12),
(1217, 117, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 350000.00, 12),
(1218, 118, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 560000.00, 12),
(1225, 102, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1226, 103, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1227, 104, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1228, 105, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1229, 106, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1230, 107, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1231, 108, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1232, 109, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1233, 110, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1234, 111, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1235, 112, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1236, 113, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1237, 114, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1238, 115, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1239, 116, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1240, 117, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(1241, 118, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1475000.00, 10),
(1242, 119, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1243, 120, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1244, 121, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1245, 122, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1246, 123, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1247, 124, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1248, 125, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1249, 126, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1250, 127, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1251, 128, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1252, 129, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1253, 130, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1254, 131, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(1255, 132, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 775000.00, 10),
(1256, 133, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1257, 134, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(1258, 135, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1259, 136, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1260, 137, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1261, 138, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1262, 139, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1263, 140, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2450000.00, 10),
(1264, 141, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1265, 142, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1266, 143, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1267, 144, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1268, 145, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1269, 146, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1270, 147, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3650000.00, 10),
(1271, 148, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1272, 149, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1273, 150, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1274, 151, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1275, 152, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(1276, 153, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 775000.00, 10),
(1277, 154, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1278, 155, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(1279, 156, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1280, 157, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1281, 158, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1282, 159, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1283, 160, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1284, 161, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 650000.00, 10),
(1285, 162, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1286, 163, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1287, 164, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1288, 165, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1289, 166, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1290, 167, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1291, 168, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(1292, 169, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(1293, 170, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1294, 171, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1295, 172, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1296, 173, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1297, 174, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(1298, 175, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 775000.00, 10),
(1299, 176, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1300, 177, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(1301, 178, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1302, 179, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1303, 180, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1304, 181, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1305, 182, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1306, 183, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 650000.00, 10),
(1307, 184, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1308, 185, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1309, 186, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1310, 187, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1311, 188, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1312, 189, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1313, 190, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(1314, 191, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(1315, 192, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 550000.00, 10),
(1316, 193, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1317, 194, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1318, 195, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1319, 196, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(1320, 197, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 775000.00, 10),
(1321, 198, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1322, 199, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(1323, 200, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1324, 201, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1325, 202, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2800000.00, 10),
(1326, 203, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1550000.00, 10),
(1327, 204, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2300000.00, 10),
(1328, 205, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 650000.00, 10),
(1329, 206, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1050000.00, 10),
(1330, 207, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1300000.00, 10),
(1331, 208, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2050000.00, 10),
(1332, 209, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 6050000.00, 10),
(1333, 210, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7550000.00, 10),
(1334, 211, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 3050000.00, 10),
(1335, 212, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 950000.00, 10),
(1336, 213, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 800000.00, 10),
(1337, 214, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 550000.00, 10),
(1338, 215, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 7050000.00, 10),
(1339, 216, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 2550000.00, 10),
(1340, 217, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1800000.00, 10),
(1341, 218, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1125000.00, 10),
(1342, 219, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 775000.00, 10),
(1343, 220, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 525000.00, 10),
(1344, 221, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 1825000.00, 10),
(1345, 222, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 475000.00, 10),
(1346, 223, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 425000.00, 10),
(1729, 302, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1730, 303, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1731, 304, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1732, 305, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1733, 306, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1734, 307, 'gaji', 'gaji_pokok', 'Gaji pokok', 95000000.00, 1),
(1736, 302, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1737, 303, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1738, 304, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1739, 305, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1740, 306, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1741, 307, 'tunjangan', 'tunjangan', 'Tunjangan transport & makan', 1000000.00, 2),
(1746, 302, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1747, 303, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1748, 304, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1749, 305, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1750, 306, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1751, 307, 'bpjs_kes', 'potongan', 'BPJS Kesehatan (1%)', 120000.00, 11),
(1753, 302, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1754, 303, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1755, 304, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1756, 305, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1757, 306, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1758, 307, 'bpjs_tk', 'potongan', 'BPJS Ketenagakerjaan JHT (2%)', 1900000.00, 12),
(1761, 302, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10),
(1762, 303, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10),
(1763, 304, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10),
(1764, 305, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10),
(1765, 306, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10),
(1766, 307, 'pph21', 'potongan', 'PPh 21 (5% dari bruto)', 4800000.00, 10);

-- --------------------------------------------------------

--
-- Table structure for table `pengingat_dokumen`
--

CREATE TABLE `pengingat_dokumen` (
  `dokumen_id` int(10) UNSIGNED NOT NULL,
  `tahap` enum('H60','H30','lewat') NOT NULL,
  `dikirim_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengingat_dokumen`
--

INSERT INTO `pengingat_dokumen` (`dokumen_id`, `tahap`, `dikirim_pada`) VALUES
(2, 'H60', '2026-11-11 10:52:20'),
(3, 'H60', '2026-11-11 10:52:20'),
(4, 'H30', '2026-11-11 10:52:20');

-- --------------------------------------------------------

--
-- Table structure for table `pengingat_kontrak`
--

CREATE TABLE `pengingat_kontrak` (
  `kontrak_id` int(10) UNSIGNED NOT NULL,
  `tahap` enum('H90','H60','H30','lewat') NOT NULL,
  `dikirim_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengingat_kontrak`
--

INSERT INTO `pengingat_kontrak` (`kontrak_id`, `tahap`, `dikirim_pada`) VALUES
(3, 'H90', '2026-11-10 22:18:27'),
(6, 'H60', '2026-11-10 22:18:27'),
(8, 'H60', '2026-11-10 22:18:27'),
(13, 'H90', '2026-11-10 22:18:27'),
(39, 'H90', '2026-11-10 22:18:27'),
(44, 'H30', '2026-11-10 22:18:27'),
(46, 'H60', '2026-11-10 22:18:27'),
(53, 'H90', '2026-11-10 22:18:27'),
(73, 'H60', '2026-11-10 22:18:27'),
(74, 'lewat', '2026-11-10 22:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(10) UNSIGNED NOT NULL,
  `judul` varchar(150) NOT NULL,
  `isi` varchar(1000) NOT NULL,
  `sasaran` enum('semua','pemain','staf') NOT NULL DEFAULT 'semua',
  `disematkan` tinyint(1) NOT NULL DEFAULT 0,
  `tampil_sampai` date DEFAULT NULL,
  `dibuat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `sasaran`, `disematkan`, `tampil_sampai`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(1, 'Peluncuran Sistem Manajemen SDM', 'Mulai bulan ini seluruh data kontrak, slip gaji, dan pengajuan cuti dikelola melalui sistem SDM klub. Silakan periksa dan lengkapi profil Anda, termasuk kontak darurat.', 'semua', 1, NULL, 6, '2026-10-28 02:00:00', '2026-11-11 03:43:06'),
(2, 'Jadwal tes kebugaran berkala', 'Tes kebugaran berkala dilaksanakan Senin, 23 November 2026 pukul 07.00 di Pusat Kebugaran Klub. Seluruh pemain wajib hadir dalam kondisi puasa 2 jam.', 'pemain', 0, '2026-11-23', 6, '2026-11-09 03:00:00', '2026-11-11 03:52:13'),
(3, 'Pengumpulan dokumen visa & KITAS', 'Pemain dan staf asing diminta menyerahkan salinan paspor terbaru ke bagian administrasi paling lambat 30 November 2026 untuk proses perpanjangan KITAS.', 'semua', 0, '2026-11-30', 6, '2026-11-05 07:30:00', '2026-11-11 03:43:06'),
(4, 'Evaluasi kinerja staf akhir tahun', 'Formulir evaluasi kinerja staf dibuka 1–15 Desember 2026. Kepala divisi menyiapkan target dan catatan capaian masing-masing anggota tim.', 'staf', 0, '2026-12-15', 6, '2026-11-10 01:15:00', '2026-11-11 03:43:06');

-- --------------------------------------------------------

--
-- Table structure for table `perawatan_cedera`
--

CREATE TABLE `perawatan_cedera` (
  `id` int(10) UNSIGNED NOT NULL,
  `cedera_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `tindakan` varchar(150) NOT NULL,
  `obat` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `petugas_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perawatan_cedera`
--

INSERT INTO `perawatan_cedera` (`id`, `cedera_id`, `tanggal`, `tindakan`, `obat`, `catatan`, `petugas_id`, `created_at`) VALUES
(1, 1, '2026-11-03', 'Terapi dingin dan kompres', 'Anti-nyeri (sesuai resep)', 'Istirahat total 3 hari.', 8, '2026-11-08 15:29:56'),
(2, 1, '2026-11-06', 'Fisioterapi (ultrasound)', NULL, 'Mulai peregangan ringan.', 8, '2026-11-08 15:29:56'),
(4, 2, '2026-09-09', 'Kompres es dan perban elastis', NULL, 'Istirahat 3 hari.', 3, '2026-11-08 15:29:56'),
(5, 2, '2026-09-17', 'Latihan keseimbangan dan penguatan', NULL, 'Mulai jogging ringan.', 3, '2026-11-08 15:29:56'),
(6, 2, '2026-09-25', 'Tes fungsional', NULL, 'Dinyatakan pulih.', 3, '2026-11-08 15:29:56'),
(13, 3, '2026-05-12', 'Latihan lapangan bertahap', NULL, 'Tes fungsional lulus.', 8, '2026-11-09 15:38:25'),
(14, 3, '2026-04-02', 'Fisioterapi penguatan quadriceps', NULL, '3x seminggu.', 8, '2026-11-09 15:38:25'),
(15, 3, '2026-03-15', 'Brace lutut dan kruk', NULL, 'Tumpuan berat dibatasi 2 minggu.', 8, '2026-11-09 15:38:25'),
(16, 4, '2026-08-16', 'Latihan tangkapan ringan', NULL, 'Siap latihan penuh.', 8, '2026-11-09 15:38:25'),
(17, 4, '2026-08-05', 'Pembidaian jari', NULL, 'Bidai 10 hari.', 8, '2026-11-09 15:38:25'),
(18, 5, '2026-08-25', 'Pijat olahraga', NULL, 'Sudah tidak nyeri.', 8, '2026-11-09 15:38:25'),
(19, 5, '2026-08-23', 'Peregangan dan rehidrasi', NULL, 'Ditarik keluar menit 70.', 8, '2026-11-09 15:38:25'),
(20, 6, '2026-09-13', 'Kompres es dan elevasi', NULL, 'Rontgen bersih.', 8, '2026-11-09 15:38:25'),
(28, 10, '2026-07-31', 'Terapi es dan istirahat', NULL, 'Istirahat 2 hari.', 3, '2026-11-09 15:38:25'),
(29, 10, '2026-08-04', 'Peregangan dan pijat ringan', NULL, 'Nyeri berkurang.', 3, '2026-11-09 15:38:25'),
(32, 12, '2026-11-03', 'RICE dan taping', NULL, 'Istirahat 3 hari.', 8, '2026-11-10 22:04:29'),
(33, 12, '2026-11-07', 'Latihan proprioseptif', NULL, 'Bengkak berkurang.', 8, '2026-11-10 22:04:29'),
(34, 13, '2026-08-26', 'Terapi manual & core stability', NULL, NULL, 8, '2026-11-10 22:04:29'),
(35, 14, '2026-06-17', 'Protokol gegar otak bertahap', NULL, 'Tes kognitif harian.', 8, '2026-11-10 22:04:29'),
(36, 15, '2026-04-18', 'Fisioterapi', NULL, NULL, 8, '2026-11-10 22:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `pertandingan`
--

CREATE TABLE `pertandingan` (
  `jadwal_id` int(10) UNSIGNED NOT NULL,
  `lawan` varchar(100) NOT NULL,
  `kandang` tinyint(1) NOT NULL DEFAULT 1,
  `kompetisi` varchar(100) DEFAULT NULL,
  `musim` varchar(7) DEFAULT NULL,
  `skor_klub` tinyint(3) UNSIGNED DEFAULT NULL,
  `skor_lawan` tinyint(3) UNSIGNED DEFAULT NULL,
  `status` enum('dijadwalkan','selesai','ditunda','dibatalkan') NOT NULL DEFAULT 'dijadwalkan',
  `catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pertandingan`
--

INSERT INTO `pertandingan` (`jadwal_id`, `lawan`, `kandang`, `kompetisi`, `musim`, `skor_klub`, `skor_lawan`, `status`, `catatan`) VALUES
(2, 'Garuda Muda', 1, 'Liga Nusantara', '2026/27', 3, 0, 'selesai', NULL),
(3, 'Elang Putih FC', 0, 'Liga Nusantara', '2026/27', 1, 1, 'selesai', NULL),
(4, 'Bintang Timur', 1, 'Liga Nusantara', '2026/27', 4, 1, 'selesai', NULL),
(5, 'Macan Tutul FC', 0, 'Liga Nusantara', '2026/27', NULL, NULL, 'dijadwalkan', NULL),
(8, 'Rajawali Utara', 1, 'Liga Nusantara', '2026/27', 2, 1, 'selesai', NULL),
(9, 'Banteng Merah', 0, 'Liga Nusantara', '2026/27', 0, 0, 'selesai', NULL),
(10, 'Harimau Selatan', 1, 'Liga Nusantara', '2026/27', 1, 2, 'selesai', NULL),
(11, 'Kuda Laut FC', 0, 'Liga Nusantara', '2026/27', 3, 1, 'selesai', NULL),
(12, 'Persada Jaya', 1, 'Piala Nusantara', '2026/27', 3, 0, 'selesai', NULL),
(13, 'Merpati Biru', 1, 'Liga Nusantara', '2026/27', 4, 1, 'selesai', NULL),
(14, 'Singa Muda', 0, 'Liga Nusantara', '2026/27', 2, 2, 'selesai', NULL),
(15, 'Cendrawasih FC', 0, 'Piala Nusantara', '2026/27', 2, 1, 'selesai', NULL),
(16, 'Elang Putih FC', 1, 'Liga Nusantara', '2025/26', 1, 0, 'selesai', NULL),
(17, 'Bintang Timur', 0, 'Liga Nusantara', '2025/26', 1, 2, 'selesai', NULL),
(18, 'Kuda Laut FC', 1, 'Piala Nusantara', '2025/26', 2, 2, 'selesai', NULL),
(23, 'Samudra Raya', 1, 'Liga Nusantara', '2026/27', NULL, NULL, 'dijadwalkan', NULL),
(24, 'Persada Jaya', 0, 'Piala Nusantara', '2026/27', NULL, NULL, 'dijadwalkan', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profil_pemain`
--

CREATE TABLE `profil_pemain` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `posisi` enum('kiper','bek','gelandang','penyerang') DEFAULT NULL,
  `posisi_detail` varchar(30) DEFAULT NULL,
  `posisi_kode` varchar(3) DEFAULT NULL,
  `kaki_dominan` enum('kanan','kiri','kedua') DEFAULT NULL,
  `tempat_lahir` varchar(80) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `kewarganegaraan` varchar(60) NOT NULL DEFAULT 'Indonesia',
  `tinggi_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `berat_kg` decimal(5,2) DEFAULT NULL,
  `telepon` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `kontak_darurat_nama` varchar(100) DEFAULT NULL,
  `kontak_darurat_hubungan` varchar(40) DEFAULT NULL,
  `kontak_darurat_telepon` varchar(25) DEFAULT NULL,
  `golongan_darah` enum('A','B','AB','O') DEFAULT NULL,
  `status` enum('aktif','cedera','dipinjamkan','diskors','pensiun') NOT NULL DEFAULT 'aktif',
  `tanggal_bergabung` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_pemain`
--

INSERT INTO `profil_pemain` (`user_id`, `posisi`, `posisi_detail`, `posisi_kode`, `kaki_dominan`, `tempat_lahir`, `tanggal_lahir`, `kewarganegaraan`, `tinggi_cm`, `berat_kg`, `telepon`, `email`, `alamat`, `kontak_darurat_nama`, `kontak_darurat_hubungan`, `kontak_darurat_telepon`, `golongan_darah`, `status`, `tanggal_bergabung`, `foto`, `created_at`, `updated_at`) VALUES
(4, 'gelandang', 'Sayap kanan', 'RW', 'kanan', 'Bogor', '2000-04-08', 'Indonesia', 174, 68.50, '081300000007', 'pemain1@example.com', 'Jl. Beringin No. 7, Bogor', 'Ibu Lestari', 'Ibu', '081311110007', 'O', 'aktif', '2024-07-01', 'uploads/foto/u4_cab6c195422b6608.webp', '2026-11-08 15:29:56', '2026-11-10 15:04:52'),
(9, 'kiper', 'Kiper', 'GK', 'kanan', 'Kediri', '1998-02-11', 'Indonesia', 185, 80.50, '081300000001', NULL, 'Jl. Sudirman No. 73, Garut', 'Ibu Siti', 'Ibu', '081311110001', 'O', 'aktif', '2024-07-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(10, 'bek', 'Bek tengah', 'CB', 'kanan', 'Madiun', '1999-06-30', 'Indonesia', 182, 76.00, '081300000002', NULL, 'Jl. Diponegoro No. 80, Bandung', 'Bapak Joko', 'Ayah', '081311110002', 'A', 'cedera', '2025-07-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(11, 'gelandang', 'Gelandang bertahan', 'DM', 'kiri', 'Semarang', '2000-09-17', 'Indonesia', 176, 70.20, '081300000003', NULL, 'Jl. Ahmad Yani No. 87, Garut', 'Ibu Rina', 'Ibu', '081311110003', 'B', 'aktif', '2024-07-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(12, 'penyerang', 'Penyerang tengah', 'ST', 'kedua', 'Medan', '2001-12-03', 'Indonesia', 178, 72.80, '081300000004', NULL, 'Jl. Gatot Subroto No. 14, Bandung', 'Bapak Dedi', 'Ayah', '081311110004', 'AB', 'aktif', '2025-01-15', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(49, 'bek', 'Bek Tengah', 'CB', 'kanan', 'Porto Alegre', '1994-04-12', 'Brasil', 189, 85.00, '081300359219', 'lucas@pohonberingin.id', 'Jl. Sudirman No. 33, Garut', 'Sari Ferreira', 'Ayah', '082104475219', 'B', 'aktif', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:35:53'),
(50, 'bek', 'Bek tengah', 'CB', 'kanan', 'Solo', '1997-02-20', 'Indonesia', 183, 78.00, '081300366550', 'bagas@pohonberingin.id', 'Jl. Diponegoro No. 40, Bandung', 'Dedi Setiawan', 'Istri', '082104566550', 'O', 'aktif', '2024-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(51, 'bek', 'Bek kiri', 'LB', 'kiri', 'Makassar', '1999-05-08', 'Indonesia', 172, 68.00, '081300373881', 'arif@pohonberingin.id', 'Jl. Ahmad Yani No. 47, Garut', 'Yanti Hidayat', 'Kakak', '082104657881', 'AB', 'aktif', '2026-01-30', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(52, 'bek', 'Bek kanan', 'RB', 'kanan', 'Bogor', '2002-10-01', 'Indonesia', 175, 70.00, '081300381212', 'dimas@pohonberingin.id', 'Jl. Gatot Subroto No. 54, Bandung', 'Bambang Kurniawan', 'Ibu', '082104749212', 'A', 'aktif', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(53, 'gelandang', 'Gelandang bertahan', 'DM', 'kanan', 'Osaka', '1996-08-14', 'Jepang', 176, 71.00, '081300388543', 'kenji@pohonberingin.id', 'Jl. Pahlawan No. 61, Garut', 'Lilis Watanabe', 'Ayah', '082104840543', 'B', 'aktif', '2022-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(54, 'gelandang', 'Gelandang bertahan', 'DM', 'kanan', 'Palembang', '1993-03-02', 'Indonesia', 175, 72.00, '081300395874', 'hendra@pohonberingin.id', 'Jl. Veteran No. 68, Bandung', 'Rina Gunawan', 'Istri', '082104931874', 'O', 'aktif', '2023-11-27', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(55, 'gelandang', 'Sayap kanan', 'RW', 'kanan', 'Pontianak', '1999-07-19', 'Indonesia', 168, 63.00, '081300403205', 'wahyu@pohonberingin.id', 'Jl. Asia Afrika No. 75, Garut', 'Sari Firmansyah', 'Kakak', '082105023205', 'AB', 'aktif', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(56, 'gelandang', 'Gelandang serang', 'AM', 'kiri', 'Pematangsiantar', '1998-01-25', 'Indonesia', 169, 64.00, '081300410536', 'rafael@pohonberingin.id', 'Jl. Merdeka No. 82, Bandung', 'Dedi Siregar', 'Ibu', '082105114536', 'A', 'aktif', '2022-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(57, 'gelandang', 'Gelandang tengah', 'CM', 'kanan', 'Cirebon', '2001-11-11', 'Indonesia', 177, 70.00, '081300417867', 'galih@pohonberingin.id', 'Jl. Sudirman No. 89, Garut', 'Yanti Permana', 'Ayah', '082105205867', 'B', 'aktif', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(58, 'penyerang', 'Sayap kiri', 'LW', 'kiri', 'Banda Aceh', '2000-04-04', 'Indonesia', 170, 65.00, '081300425198', 'ahmad@pohonberingin.id', 'Jl. Diponegoro No. 16, Bandung', 'Bambang Fauzan', 'Istri', '082105297198', 'O', 'cedera', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 22:04:29'),
(59, 'penyerang', 'Penyerang tengah', 'ST', 'kanan', 'Recife', '1993-09-30', 'Brasil', 182, 80.00, '081300432529', 'marcelo@pohonberingin.id', 'Jl. Ahmad Yani No. 23, Garut', 'Lilis Santos', 'Kakak', '082105388529', 'AB', 'aktif', '2024-12-31', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(60, 'penyerang', 'Penyerang tengah', 'ST', 'kanan', 'Utrecht', '1995-12-12', 'Belanda', 190, 84.00, '081300439860', 'thijs@pohonberingin.id', 'Jl. Gatot Subroto No. 30, Bandung', 'Rina Vermeulen', 'Ibu', '082105479860', 'A', 'aktif', '2025-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(61, 'penyerang', 'Penyerang tengah', 'ST', 'kanan', 'Padang', '2003-03-15', 'Indonesia', 179, 73.00, '081300447191', 'reza@pohonberingin.id', 'Jl. Pahlawan No. 37, Garut', 'Sari Aditya', 'Ayah', '082105571191', 'B', 'aktif', '2026-07-01', NULL, '2026-11-10 15:04:52', '2026-11-11 03:52:26'),
(62, 'gelandang', 'Sayap kanan', 'RW', 'kanan', 'Bekasi', '2004-06-06', 'Indonesia', 166, 60.00, '081300454522', 'daffa@pohonberingin.id', 'Jl. Veteran No. 44, Bandung', 'Dedi Alamsyah', 'Istri', '082105662522', 'O', 'aktif', '2026-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(63, 'kiper', 'Kiper', 'GK', 'kanan', 'Tasikmalaya', '2002-01-09', 'Indonesia', 186, 79.00, '081300461853', 'rizal@pohonberingin.id', 'Jl. Asia Afrika No. 51, Garut', 'Yanti Maulana', 'Kakak', '082105753853', 'AB', 'aktif', '2026-07-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(64, 'kiper', 'Kiper', 'GK', 'kanan', 'Jayapura', '1991-05-21', 'Indonesia', 184, 82.00, '081300469184', 'yusuf@pohonberingin.id', 'Jl. Merdeka No. 58, Bandung', 'Bambang Habibi', 'Ibu', '082105845184', 'A', 'aktif', '2024-01-26', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(65, 'bek', 'Bek kanan', 'RB', 'kanan', 'Manado', '2005-02-14', 'Indonesia', 174, 66.00, '081300476515', 'samuel@pohonberingin.id', 'Jl. Sudirman No. 65, Garut', 'Lilis Tanoto', 'Ayah', '082105936515', 'B', 'dipinjamkan', '2026-08-01', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `program_latihan`
--

CREATE TABLE `program_latihan` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode` varchar(20) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `fokus` enum('fisik','teknik','taktik','pemulihan','kiper','mental') NOT NULL DEFAULT 'fisik',
  `intensitas` enum('rendah','sedang','tinggi') NOT NULL DEFAULT 'sedang',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` enum('direncanakan','aktif','selesai','dibatalkan') NOT NULL DEFAULT 'direncanakan',
  `deskripsi` text DEFAULT NULL,
  `dibuat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `program_latihan`
--

INSERT INTO `program_latihan` (`id`, `kode`, `judul`, `fokus`, `intensitas`, `tanggal_mulai`, `tanggal_selesai`, `status`, `deskripsi`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(1, 'PL-2607-01', 'Pramusim: fondasi kebugaran', 'fisik', 'tinggi', '2026-07-01', '2026-07-31', 'selesai', 'Membangun daya tahan aerobik dan kekuatan dasar sebelum musim dimulai.', 7, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(2, 'PL-2610-01', 'Daya ledak dan sprint berulang', 'fisik', 'tinggi', '2026-10-19', '2026-11-29', 'aktif', 'Plyometrik dan sprint berulang untuk menjaga intensitas sampai akhir laga.', 7, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(3, 'PL-2611-01', 'Penyelesaian akhir di kotak penalti', 'teknik', 'sedang', '2026-11-02', '2026-12-13', 'aktif', 'Variasi tembakan satu sentuhan, sundulan, dan situasi satu lawan satu dengan kiper.', 7, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(4, 'PL-2611-02', 'Pemulihan hamstring bertahap', 'pemulihan', 'rendah', '2026-11-04', '2026-11-24', 'aktif', 'Program kembali bermain (return to play) bertahap untuk cedera hamstring grade 1.', 8, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(5, 'PL-2611-03', 'Distribusi bola dan bola atas kiper', 'kiper', 'sedang', '2026-11-09', '2026-12-20', 'aktif', 'Akurasi umpan jauh, lemparan cepat, dan antisipasi bola silang.', 7, '2026-11-09 15:38:25', '2026-11-09 15:38:25'),
(6, 'PL-2612-01', 'Pressing tinggi dan transisi', 'taktik', 'sedang', '2026-12-01', '2027-01-10', 'direncanakan', 'Pola pressing 4-3-3 dan transisi cepat setelah merebut bola.', 7, '2026-11-09 15:38:25', '2026-11-09 15:38:25');

-- --------------------------------------------------------

--
-- Table structure for table `program_latihan_peserta`
--

CREATE TABLE `program_latihan_peserta` (
  `program_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `progres_persen` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('berjalan','selesai','ditunda') NOT NULL DEFAULT 'berjalan',
  `catatan_pelatih` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `program_latihan_peserta`
--

INSERT INTO `program_latihan_peserta` (`program_id`, `user_id`, `progres_persen`, `status`, `catatan_pelatih`, `updated_at`) VALUES
(1, 4, 100, 'selesai', 'Tuntas.', '2026-11-09 15:38:25'),
(1, 9, 100, 'selesai', 'Tuntas.', '2026-11-09 15:38:25'),
(1, 10, 100, 'selesai', 'Tuntas.', '2026-11-09 15:38:25'),
(1, 11, 100, 'selesai', 'Tuntas, kebugaran terbaik di tim.', '2026-11-09 15:38:25'),
(1, 12, 100, 'selesai', 'Tuntas.', '2026-11-09 15:38:25'),
(2, 4, 65, 'berjalan', 'Akselerasi 10 m membaik.', '2026-11-09 15:38:25'),
(2, 10, 20, 'ditunda', 'Ditunda karena cedera hamstring.', '2026-11-09 15:38:25'),
(2, 11, 60, 'berjalan', 'Konsisten, pertahankan.', '2026-11-09 15:38:25'),
(2, 12, 55, 'berjalan', 'Waktu sprint membaik 0,07 detik.', '2026-11-09 15:38:25'),
(3, 4, 40, 'berjalan', 'Latih tembakan kaki kiri.', '2026-11-09 15:38:25'),
(3, 12, 35, 'berjalan', 'Fokus kaki kiri.', '2026-11-09 15:38:25'),
(4, 10, 35, 'berjalan', 'Tes nyeri hari ke-5 lulus.', '2026-11-09 15:38:25'),
(5, 9, 10, 'berjalan', 'Baru dimulai.', '2026-11-09 15:38:25'),
(6, 4, 0, 'berjalan', NULL, '2026-11-09 15:38:25'),
(6, 10, 0, 'berjalan', NULL, '2026-11-09 15:38:25'),
(6, 11, 0, 'berjalan', NULL, '2026-11-09 15:38:25'),
(6, 12, 0, 'berjalan', NULL, '2026-11-09 15:38:25');

-- --------------------------------------------------------

--
-- Table structure for table `program_latihan_sesi`
--

CREATE TABLE `program_latihan_sesi` (
  `id` int(10) UNSIGNED NOT NULL,
  `program_id` int(10) UNSIGNED NOT NULL,
  `urutan` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `judul` varchar(150) NOT NULL,
  `durasi_menit` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `intensitas` enum('rendah','sedang','tinggi') NOT NULL DEFAULT 'sedang',
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_latihan_sesi`
--

INSERT INTO `program_latihan_sesi` (`id`, `program_id`, `urutan`, `hari`, `judul`, `durasi_menit`, `intensitas`, `keterangan`) VALUES
(1, 1, 3, 'Jumat', 'Small-sided game 5v5', 45, 'tinggi', 'Lapangan 30x40 m.'),
(2, 1, 2, 'Rabu', 'Kekuatan tubuh bagian bawah', 50, 'sedang', 'Squat, lunge, deadlift ringan.'),
(3, 1, 1, 'Senin', 'Lari interval 4x4 menit', 60, 'tinggi', 'Target 90% denyut maksimal.'),
(4, 2, 3, 'Kamis', 'Mobilitas dan pemulihan aktif', 30, 'rendah', 'Foam roller dan peregangan.'),
(5, 2, 2, 'Rabu', 'Sprint berulang 6x30 m', 30, 'tinggi', 'Istirahat 25 detik per sprint.'),
(6, 2, 1, 'Senin', 'Plyometrik: box jump dan bounding', 40, 'tinggi', '4 set x 6 repetisi.'),
(7, 3, 3, 'Sabtu', 'Sundulan dari bola mati', 30, 'sedang', 'Sudut dan tendangan bebas.'),
(8, 3, 2, 'Kamis', 'Satu lawan satu dengan kiper', 40, 'sedang', 'Variasi chip dan placing.'),
(9, 3, 1, 'Selasa', 'Tembakan satu sentuhan', 45, 'sedang', 'Umpan dari sayap dan cut-back.'),
(10, 4, 3, 'Jumat', 'Sprint bertahap dan perubahan arah', 30, 'sedang', 'Hanya bila tes nyeri lulus.'),
(11, 4, 2, 'Rabu', 'Jogging lurus bertahap', 25, 'rendah', 'Mulai 60% kecepatan.'),
(12, 4, 1, 'Senin', 'Penguatan eksentrik (Nordic curl)', 30, 'rendah', 'Tanpa nyeri, 3 set x 5.'),
(13, 5, 2, 'Kamis', 'Bola silang dan keluar dari gawang', 45, 'sedang', 'Dengan penyerang aktif.'),
(14, 5, 1, 'Selasa', 'Umpan jauh ke sayap', 40, 'sedang', 'Target zona 10x10 m.'),
(15, 6, 2, 'Kamis', 'Transisi 6 detik', 45, 'tinggi', 'Permainan posisi 8v8.'),
(16, 6, 1, 'Selasa', 'Pemicu pressing', 50, 'sedang', 'Video dan latihan pola.');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_karier`
--

CREATE TABLE `riwayat_karier` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `klub` varchar(100) NOT NULL,
  `jenis` enum('akademi','permanen','pinjaman','bebas_transfer','staf') NOT NULL DEFAULT 'permanen',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `nilai_transfer` decimal(15,2) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `riwayat_karier`
--

INSERT INTO `riwayat_karier` (`id`, `user_id`, `klub`, `jenis`, `tanggal_mulai`, `tanggal_selesai`, `nilai_transfer`, `catatan`) VALUES
(1, 4, 'Pohon Beringin FC', 'permanen', '2024-07-01', NULL, 2500000000.00, 'Transfer permanen dari Borneo Lestari FC.'),
(2, 4, 'Borneo Lestari FC', 'pinjaman', '2022-08-01', '2024-06-30', NULL, 'Dipinjamkan dua musim.'),
(3, 4, 'Akademi Garuda Muda', 'akademi', '2015-07-01', '2022-07-31', NULL, 'Lulusan akademi.'),
(4, 1, 'Pohon Beringin FC', 'staf', '2021-01-04', NULL, NULL, 'Manager klub.'),
(5, 1, 'Persada Raya FC', 'staf', '2015-06-01', '2020-12-31', NULL, 'Asisten manager.'),
(6, 3, 'Pohon Beringin FC', 'staf', '2022-08-15', NULL, NULL, 'Dokter tim.'),
(7, 3, 'RS Sehat Sentosa', 'staf', '2014-03-01', '2022-07-31', NULL, 'Dokter olahraga.'),
(8, 54, 'Sriwijaya Raya', 'permanen', '2014-01-05', '2023-11-26', NULL, NULL),
(9, 54, 'Pohon Beringin FC', 'permanen', '2023-11-27', NULL, 900000000.00, NULL),
(10, 53, 'Cerezo Utama', 'permanen', '2017-01-10', '2022-06-30', NULL, NULL),
(11, 53, 'Pohon Beringin FC', 'permanen', '2022-07-01', NULL, 3200000000.00, 'Transfer dari Cerezo Utama.'),
(12, 49, 'Akademi Porto Alegre', 'akademi', '2010-02-01', '2018-12-31', NULL, NULL),
(13, 49, 'Grêmio Juventude', 'permanen', '2019-01-15', '2025-06-30', NULL, 'Kapten tim 2 musim.'),
(14, 49, 'Pohon Beringin FC', 'permanen', '2025-07-01', NULL, 6500000000.00, 'Transfer dari Grêmio Juventude.'),
(15, 59, 'Recife Atlético', 'permanen', '2016-07-01', '2024-06-30', NULL, '87 gol liga.'),
(16, 59, 'Pohon Beringin FC', 'bebas_transfer', '2024-12-31', NULL, NULL, 'Bebas transfer.'),
(17, 66, 'PT Sinar Logistik', 'staf', '2012-03-01', '2023-04-30', NULL, 'HR generalist.'),
(18, 66, 'Pohon Beringin FC', 'staf', '2023-05-18', NULL, NULL, 'Kepala HR & administrasi.'),
(19, 61, 'Pohon Beringin FC', 'akademi', '2018-07-01', '2026-06-30', NULL, 'Promosi ke tim utama Juli 2026.'),
(20, 69, 'Arema Selatan', 'staf', '2014-01-01', '2020-06-30', NULL, 'Pelatih tim U-19.'),
(21, 69, 'Pohon Beringin FC', 'staf', '2020-07-12', NULL, NULL, 'Asisten pelatih.'),
(22, 65, 'Pohon Beringin FC', 'akademi', '2019-07-01', '2026-07-31', NULL, 'Lulusan akademi klub.'),
(23, 65, 'Manado Bahari FC', 'pinjaman', '2026-08-01', '2027-05-31', NULL, 'Dipinjamkan satu musim untuk menit bermain.'),
(24, 60, 'FC Utrecht Muda', 'permanen', '2015-07-01', '2025-06-30', NULL, NULL),
(25, 60, 'Pohon Beringin FC', 'permanen', '2025-07-01', NULL, 5800000000.00, 'Transfer dari FC Utrecht Muda.');

-- --------------------------------------------------------

--
-- Table structure for table `staf`
--

CREATE TABLE `staf` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` enum('manager','admin','pelatih_kepala','asisten_pelatih','pelatih_kiper','pelatih_fisik','dokter','fisioterapis','analis','lainnya') NOT NULL DEFAULT 'lainnya',
  `jabatan_detail` varchar(80) DEFAULT NULL,
  `divisi` enum('manajemen','kepelatihan','medis','analisis','administrasi','keuangan','operasional') NOT NULL DEFAULT 'operasional',
  `atasan_id` int(10) UNSIGNED DEFAULT NULL,
  `status_kerja` enum('tetap','kontrak','paruh_waktu','magang') NOT NULL DEFAULT 'kontrak',
  `kewarganegaraan` varchar(60) DEFAULT NULL,
  `tempat_lahir` varchar(80) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `telepon` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `kontak_darurat_nama` varchar(100) DEFAULT NULL,
  `kontak_darurat_hubungan` varchar(40) DEFAULT NULL,
  `kontak_darurat_telepon` varchar(25) DEFAULT NULL,
  `lisensi` varchar(100) DEFAULT NULL,
  `nomor_lisensi` varchar(50) DEFAULT NULL,
  `lisensi_berlaku_sampai` date DEFAULT NULL,
  `status` enum('aktif','cuti','nonaktif') NOT NULL DEFAULT 'aktif',
  `tanggal_bergabung` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staf`
--

INSERT INTO `staf` (`user_id`, `nip`, `jabatan`, `jabatan_detail`, `divisi`, `atasan_id`, `status_kerja`, `kewarganegaraan`, `tempat_lahir`, `tanggal_lahir`, `telepon`, `email`, `alamat`, `kontak_darurat_nama`, `kontak_darurat_hubungan`, `kontak_darurat_telepon`, `lisensi`, `nomor_lisensi`, `lisensi_berlaku_sampai`, `status`, `tanggal_bergabung`, `foto`, `created_at`, `updated_at`) VALUES
(1, 'STF-010', 'manager', 'Direktur Klub', 'manajemen', NULL, 'tetap', 'Indonesia', 'Jakarta', '1982-05-10', '081200000010', 'admin@example.com', NULL, 'Sari Wulandari', 'Istri', '081311110010', NULL, NULL, NULL, 'aktif', '2021-01-04', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(2, 'STF-011', 'admin', 'Staf HR', 'administrasi', 1, 'tetap', 'Indonesia', 'Bandung', '1990-09-02', '081200000011', 'hr@example.com', NULL, 'Rudi Hartono', 'Suami', '081311110011', NULL, NULL, NULL, 'aktif', '2022-03-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(3, 'STF-012', 'dokter', 'Dokter Tim', 'medis', 8, 'kontrak', 'Indonesia', 'Yogyakarta', '1986-01-25', '081200000012', 'medis@example.com', NULL, 'Dewi Anggraini', 'Kakak', '081311110012', 'STR Dokter', 'STR-554433', '2028-03-31', 'aktif', '2022-08-15', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(5, 'STF-013', 'analis', 'Analis Performa', 'analisis', 7, 'kontrak', 'Indonesia', 'Solo', '1993-07-19', '081200000013', 'staf@example.com', NULL, 'Bambang Sutrisno', 'Ayah', '081311110013', 'Analis Video', 'ANL-0101', '2027-09-30', 'aktif', '2023-02-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(6, 'STF-001', 'manager', 'Manajer Umum', 'manajemen', 1, 'tetap', 'Indonesia', 'Surabaya', '1980-03-14', '081200000001', 'hendra@example.com', 'Jl. Buah Batu No. 71, Garut', 'Agus Wijaya', 'Kakak', '083900466266', NULL, NULL, NULL, 'aktif', '2020-01-02', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(7, 'STF-002', 'pelatih_kepala', 'Pelatih Kepala', 'kepelatihan', 6, 'kontrak', 'Indonesia', 'Malang', '1975-08-21', '081200000002', 'agus@example.com', 'Jl. Setiabudi No. 82, Cimahi', 'Dewi Santoso', 'Ibu', '083900543977', 'AFC A', 'AFC-A-0042', '2027-12-31', 'aktif', '2023-07-01', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(8, 'STF-003', 'dokter', 'Dokter Tim', 'medis', 7, 'kontrak', 'Indonesia', 'Bandung', '1988-11-05', '081200000003', 'maya@example.com', 'Jl. Cihampelas No. 93, Bekasi', 'Hadi Kusuma', 'Istri', '083900621688', 'STR Dokter', 'STR-998877', '2028-06-30', 'aktif', '2022-01-10', NULL, '2026-11-08 15:29:56', '2026-11-10 15:27:32'),
(66, 'STF-020', 'admin', 'Kepala HR & Administrasi', 'administrasi', 6, 'tetap', 'Indonesia', 'Yogyakarta', '1986-04-02', '082097188133', 'nur.aisyah@pohonberingin.id', 'Jl. Buah Batu No. 11, Garut', 'Agus Aisyah', 'Kakak', '083905128926', NULL, NULL, NULL, 'aktif', '2023-05-18', NULL, '2026-11-10 15:04:52', '2026-11-10 15:35:25'),
(67, 'STF-021', 'lainnya', 'Staf Keuangan', 'keuangan', 6, 'tetap', 'Indonesia', 'Klaten', '1984-09-12', '084231072177', 'joko.susilo@pohonberingin.id', 'Jl. Setiabudi No. 22, Cimahi', 'Dewi Susilo', 'Ibu', '083905206637', NULL, NULL, NULL, 'aktif', '2018-10-10', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(68, 'STF-022', 'admin', 'Staf Administrasi', 'administrasi', 66, 'kontrak', 'Indonesia', 'Bogor', '1996-12-01', '083197309641', 'putri.handayani@pohonberingin.id', 'Jl. Cihampelas No. 33, Bekasi', 'Hadi Handayani', 'Istri', '083905284348', NULL, NULL, NULL, 'aktif', '2022-06-11', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(69, 'STF-023', 'asisten_pelatih', 'Asisten Pelatih', 'kepelatihan', 7, 'kontrak', 'Indonesia', 'Malang', '1982-02-17', '085648667078', 'agus.riyanto@pohonberingin.id', 'Jl. Dago No. 44, Depok', 'Wulan Riyanto', 'Suami', '083905362059', NULL, NULL, NULL, 'aktif', '2020-07-12', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(70, 'STF-024', 'lainnya', 'Kitman / Perlengkapan', 'operasional', 6, 'kontrak', 'Indonesia', 'Sidoarjo', '1990-07-23', '085041218546', 'eko.prasetyo@pohonberingin.id', 'Jl. Pasteur No. 55, Bandung', 'Rina Prasetyo', 'Kakak', '083905439770', NULL, NULL, NULL, 'aktif', '2024-03-27', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32'),
(71, 'STF-025', 'lainnya', 'Logistik & Transportasi', 'operasional', 6, 'paruh_waktu', 'Indonesia', 'Gresik', '1979-11-30', '083383338144', 'ahmad.sanusi@pohonberingin.id', 'Jl. Soekarno-Hatta No. 66, Garut', 'Agus Sanusi', 'Ibu', '083905517481', NULL, NULL, NULL, 'aktif', '2020-01-24', NULL, '2026-11-10 15:04:52', '2026-11-10 15:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `statistik_pertandingan`
--

CREATE TABLE `statistik_pertandingan` (
  `id` int(10) UNSIGNED NOT NULL,
  `jadwal_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `starter` tinyint(1) NOT NULL DEFAULT 0,
  `menit_bermain` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `goal` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `assist` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `tembakan` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `tembakan_tepat` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `umpan` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `umpan_sukses` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `tekel` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `penyelamatan` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_kuning` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_merah` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `rating` decimal(3,1) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `dicatat_oleh` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `statistik_pertandingan`
--

INSERT INTO `statistik_pertandingan` (`id`, `jadwal_id`, `user_id`, `starter`, `menit_bermain`, `goal`, `assist`, `tembakan`, `tembakan_tepat`, `umpan`, `umpan_sukses`, `tekel`, `penyelamatan`, `kartu_kuning`, `kartu_merah`, `rating`, `catatan`, `dicatat_oleh`, `updated_at`) VALUES
(1, 2, 9, 1, 90, 0, 0, 0, 0, 30, 24, 0, 4, 0, 0, 7.5, NULL, NULL, '2026-11-10 21:51:23'),
(2, 2, 10, 1, 90, 0, 0, 1, 0, 55, 48, 5, 0, 1, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(3, 2, 11, 1, 90, 0, 1, 2, 1, 70, 62, 6, 0, 0, 0, 7.6, NULL, NULL, '2026-11-10 21:51:23'),
(4, 2, 12, 1, 85, 2, 0, 5, 3, 25, 18, 1, 0, 0, 0, 8.8, NULL, NULL, '2026-11-10 21:51:23'),
(5, 3, 9, 1, 90, 0, 0, 0, 0, 28, 20, 0, 5, 0, 0, 7.2, NULL, NULL, '2026-11-10 21:51:23'),
(6, 3, 10, 1, 90, 0, 0, 0, 0, 50, 41, 7, 0, 1, 0, 6.8, NULL, NULL, '2026-11-10 21:51:23'),
(7, 3, 11, 1, 90, 1, 0, 3, 2, 66, 57, 4, 0, 1, 0, 7.9, NULL, NULL, '2026-11-10 21:51:23'),
(8, 3, 12, 1, 78, 0, 0, 3, 1, 22, 15, 0, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 21:51:23'),
(9, 4, 9, 1, 90, 0, 0, 0, 0, 32, 27, 0, 3, 0, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(10, 4, 10, 1, 90, 0, 1, 1, 1, 58, 51, 4, 0, 0, 0, 7.4, NULL, NULL, '2026-11-10 21:51:23'),
(11, 4, 11, 1, 90, 1, 1, 4, 2, 74, 68, 5, 0, 0, 0, 8.3, NULL, NULL, '2026-11-10 21:51:23'),
(12, 4, 12, 1, 90, 1, 1, 4, 3, 27, 21, 2, 0, 1, 1, 6.9, NULL, NULL, '2026-11-10 21:51:23'),
(16, 2, 4, 1, 90, 1, 1, 4, 2, 48, 40, 2, 0, 0, 0, 8.2, NULL, NULL, '2026-11-10 21:51:23'),
(17, 3, 4, 1, 90, 0, 0, 2, 1, 41, 32, 3, 0, 1, 0, 6.7, NULL, NULL, '2026-11-10 21:51:23'),
(18, 4, 4, 1, 82, 2, 0, 5, 3, 52, 44, 1, 0, 0, 0, 8.6, NULL, NULL, '2026-11-10 21:51:23'),
(22, 8, 12, 1, 80, 1, 0, 3, 2, 24, 18, 1, 0, 0, 0, 7.6, NULL, NULL, '2026-11-10 21:51:23'),
(23, 8, 11, 1, 90, 0, 1, 2, 1, 68, 60, 5, 0, 0, 0, 7.5, NULL, NULL, '2026-11-10 21:51:23'),
(24, 8, 10, 1, 90, 0, 0, 1, 0, 52, 45, 6, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 21:51:23'),
(25, 8, 9, 1, 90, 0, 0, 0, 0, 31, 25, 0, 3, 0, 0, 7.1, NULL, NULL, '2026-11-10 21:51:23'),
(26, 9, 12, 1, 90, 0, 0, 2, 0, 20, 13, 0, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 21:51:23'),
(27, 9, 11, 1, 90, 0, 0, 1, 0, 72, 63, 6, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(28, 9, 10, 1, 90, 0, 0, 0, 0, 48, 42, 8, 0, 1, 0, 7.5, NULL, NULL, '2026-11-10 21:51:23'),
(29, 9, 9, 1, 90, 0, 0, 0, 0, 29, 22, 0, 6, 0, 0, 8.0, NULL, NULL, '2026-11-10 21:51:23'),
(30, 10, 12, 1, 90, 1, 0, 4, 2, 22, 15, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 21:51:23'),
(31, 10, 11, 1, 90, 0, 0, 2, 1, 64, 55, 4, 0, 1, 0, 6.5, NULL, NULL, '2026-11-10 21:51:23'),
(32, 10, 10, 1, 90, 0, 0, 1, 0, 45, 37, 4, 0, 1, 0, 6.3, NULL, NULL, '2026-11-10 21:51:23'),
(33, 10, 9, 1, 90, 0, 0, 0, 0, 27, 20, 0, 2, 1, 0, 6.2, NULL, NULL, '2026-11-10 21:51:23'),
(34, 11, 12, 1, 85, 1, 1, 4, 2, 26, 20, 1, 0, 1, 0, 7.8, NULL, NULL, '2026-11-10 21:51:23'),
(35, 11, 11, 1, 90, 1, 1, 3, 2, 70, 62, 5, 0, 0, 0, 8.2, NULL, NULL, '2026-11-10 21:51:23'),
(36, 11, 10, 1, 90, 0, 1, 1, 1, 57, 50, 5, 0, 0, 0, 7.6, NULL, NULL, '2026-11-10 21:51:23'),
(37, 11, 9, 1, 90, 0, 0, 0, 0, 33, 27, 0, 4, 0, 0, 7.3, NULL, NULL, '2026-11-10 21:51:23'),
(38, 12, 12, 0, 25, 0, 1, 1, 1, 8, 6, 0, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(39, 12, 11, 1, 75, 1, 0, 2, 1, 50, 45, 3, 0, 0, 0, 7.7, NULL, NULL, '2026-11-10 21:51:23'),
(40, 12, 10, 0, 30, 0, 0, 0, 0, 18, 16, 2, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 21:51:23'),
(41, 13, 12, 1, 88, 2, 1, 5, 4, 28, 22, 1, 0, 0, 0, 9.0, NULL, NULL, '2026-11-10 21:51:23'),
(42, 13, 11, 1, 90, 0, 2, 1, 0, 75, 69, 6, 0, 0, 0, 8.0, NULL, NULL, '2026-11-10 21:51:23'),
(43, 13, 10, 1, 90, 1, 0, 2, 1, 55, 49, 6, 0, 0, 0, 8.1, NULL, NULL, '2026-11-10 21:51:23'),
(44, 13, 9, 1, 90, 0, 0, 0, 0, 30, 26, 0, 2, 0, 0, 6.9, NULL, NULL, '2026-11-10 21:51:23'),
(45, 14, 12, 1, 90, 1, 0, 3, 2, 23, 17, 0, 0, 1, 0, 7.2, NULL, NULL, '2026-11-10 21:51:23'),
(46, 14, 11, 1, 90, 0, 1, 2, 1, 66, 57, 5, 0, 1, 0, 7.1, NULL, NULL, '2026-11-10 21:51:23'),
(47, 14, 10, 1, 90, 0, 0, 0, 0, 49, 40, 5, 0, 1, 0, 6.6, NULL, NULL, '2026-11-10 21:51:23'),
(48, 14, 9, 1, 90, 0, 0, 0, 0, 28, 21, 0, 5, 0, 0, 6.8, NULL, NULL, '2026-11-10 21:51:23'),
(49, 15, 12, 1, 90, 1, 0, 3, 1, 21, 15, 1, 0, 0, 0, 7.4, NULL, NULL, '2026-11-10 21:51:23'),
(50, 15, 11, 1, 90, 0, 1, 1, 1, 62, 54, 4, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 21:51:23'),
(51, 15, 10, 1, 90, 0, 0, 0, 0, 51, 44, 7, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 21:51:23'),
(52, 15, 9, 1, 90, 0, 0, 0, 0, 26, 19, 0, 4, 0, 0, 7.4, NULL, NULL, '2026-11-10 21:51:23'),
(53, 16, 12, 1, 90, 1, 0, 3, 2, 22, 16, 0, 0, 0, 0, 7.5, NULL, NULL, '2026-11-10 21:51:23'),
(54, 16, 11, 1, 90, 0, 1, 1, 0, 60, 52, 5, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 21:51:23'),
(55, 16, 10, 1, 34, 0, 0, 0, 0, 20, 17, 2, 0, 0, 0, 6.1, NULL, NULL, '2026-11-10 21:51:23'),
(56, 16, 9, 1, 90, 0, 0, 0, 0, 30, 24, 0, 3, 0, 0, 7.6, NULL, NULL, '2026-11-10 21:51:23'),
(57, 17, 12, 1, 90, 0, 0, 2, 0, 19, 13, 1, 0, 1, 0, 6.0, NULL, NULL, '2026-11-10 21:51:23'),
(58, 17, 11, 1, 90, 1, 0, 3, 2, 58, 49, 4, 0, 1, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(59, 17, 9, 1, 90, 0, 0, 0, 0, 25, 18, 0, 2, 0, 0, 6.3, NULL, NULL, '2026-11-10 21:51:23'),
(60, 18, 12, 1, 90, 1, 0, 3, 2, 24, 18, 1, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 21:51:23'),
(61, 18, 11, 0, 30, 0, 0, 1, 0, 20, 17, 1, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 21:51:23'),
(62, 18, 9, 1, 90, 0, 0, 0, 0, 27, 20, 0, 3, 0, 0, 6.6, NULL, NULL, '2026-11-10 21:51:23'),
(85, 8, 4, 1, 90, 1, 1, 3, 2, 45, 38, 2, 0, 0, 0, 8.0, NULL, NULL, '2026-11-10 21:51:23'),
(86, 9, 4, 1, 78, 0, 0, 1, 0, 38, 31, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 21:51:23'),
(87, 10, 4, 0, 25, 0, 1, 1, 0, 14, 12, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 21:51:23'),
(88, 11, 4, 1, 90, 1, 0, 3, 2, 47, 40, 3, 0, 1, 0, 7.7, NULL, NULL, '2026-11-10 21:51:23'),
(89, 12, 4, 1, 90, 1, 1, 4, 2, 51, 44, 2, 0, 0, 0, 8.1, NULL, NULL, '2026-11-10 21:51:23'),
(90, 13, 4, 1, 80, 0, 1, 2, 1, 44, 37, 2, 0, 0, 0, 7.5, NULL, NULL, '2026-11-10 21:51:23'),
(91, 14, 4, 1, 90, 0, 1, 2, 1, 42, 34, 3, 0, 1, 0, 7.0, NULL, NULL, '2026-11-10 21:51:23'),
(92, 15, 4, 0, 20, 0, 0, 0, 0, 10, 8, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 21:51:23'),
(93, 16, 4, 1, 90, 0, 0, 1, 0, 40, 33, 2, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 21:51:23'),
(94, 17, 4, 1, 70, 0, 1, 2, 1, 36, 30, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 21:51:23'),
(95, 18, 4, 1, 90, 1, 0, 3, 2, 46, 39, 2, 0, 1, 0, 7.4, NULL, NULL, '2026-11-10 21:51:23'),
(127, 16, 49, 1, 69, 0, 0, 0, 0, 52, 42, 4, 0, 1, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(128, 16, 50, 0, 35, 0, 0, 1, 0, 30, 24, 4, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(129, 16, 51, 1, 79, 0, 0, 0, 0, 44, 39, 6, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(130, 16, 52, 1, 83, 0, 0, 0, 0, 43, 37, 4, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(131, 16, 53, 1, 77, 0, 0, 1, 1, 51, 43, 2, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(132, 16, 54, 0, 16, 0, 0, 0, 0, 17, 14, 4, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(133, 16, 55, 1, 69, 0, 0, 3, 0, 32, 24, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(134, 16, 56, 1, 70, 0, 0, 1, 0, 38, 28, 0, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(135, 16, 57, 0, 22, 0, 0, 0, 0, 11, 9, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(136, 16, 58, 1, 65, 0, 0, 3, 1, 35, 29, 0, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(137, 16, 59, 1, 78, 0, 0, 1, 1, 37, 31, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(138, 16, 60, 1, 78, 0, 0, 1, 1, 42, 33, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(139, 17, 49, 1, 60, 0, 0, 0, 0, 42, 35, 6, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(140, 17, 50, 1, 80, 0, 0, 1, 0, 57, 47, 5, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(141, 17, 51, 1, 87, 0, 0, 1, 1, 41, 32, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(142, 17, 52, 1, 67, 0, 0, 0, 0, 37, 30, 4, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(143, 17, 53, 0, 35, 0, 0, 1, 1, 23, 19, 6, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(144, 17, 55, 1, 74, 0, 0, 3, 0, 37, 29, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(145, 17, 56, 1, 61, 0, 0, 3, 0, 30, 26, 0, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(146, 17, 57, 0, 12, 0, 0, 0, 0, 7, 6, 2, 0, 1, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(147, 17, 58, 0, 28, 0, 0, 3, 1, 16, 13, 0, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(148, 17, 59, 1, 60, 0, 0, 1, 1, 32, 24, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(149, 17, 60, 1, 86, 0, 0, 1, 1, 44, 35, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(150, 18, 49, 1, 62, 0, 0, 1, 0, 44, 37, 2, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(151, 18, 50, 0, 28, 0, 0, 0, 0, 19, 17, 5, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(152, 18, 51, 1, 84, 0, 0, 0, 0, 44, 33, 2, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(153, 18, 52, 1, 60, 0, 0, 1, 1, 29, 24, 5, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(154, 18, 53, 1, 77, 0, 2, 0, 0, 58, 46, 2, 0, 0, 0, 7.6, NULL, NULL, '2026-11-10 22:04:29'),
(155, 18, 54, 0, 11, 0, 0, 1, 0, 8, 6, 4, 0, 0, 0, 6.0, NULL, NULL, '2026-11-10 22:04:29'),
(156, 18, 55, 1, 81, 0, 0, 4, 0, 38, 31, 2, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(157, 18, 56, 1, 79, 0, 0, 4, 0, 37, 31, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(158, 18, 58, 0, 17, 0, 0, 4, 1, 13, 10, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(159, 18, 59, 1, 68, 0, 0, 1, 0, 31, 24, 0, 0, 1, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(160, 18, 60, 0, 15, 0, 0, 2, 1, 12, 9, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(161, 8, 49, 1, 73, 0, 0, 0, 0, 50, 37, 2, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(162, 8, 50, 1, 61, 0, 0, 1, 1, 41, 36, 5, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(163, 8, 51, 1, 61, 0, 0, 0, 0, 32, 24, 4, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(164, 8, 52, 0, 20, 0, 0, 0, 0, 15, 12, 3, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(165, 8, 53, 1, 75, 0, 0, 1, 0, 49, 42, 4, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(166, 8, 55, 1, 83, 0, 0, 1, 1, 37, 30, 0, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(167, 8, 56, 0, 11, 0, 0, 4, 0, 9, 7, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(168, 8, 57, 0, 16, 0, 0, 0, 0, 8, 6, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(169, 8, 58, 1, 78, 0, 0, 1, 0, 36, 29, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(170, 8, 59, 1, 71, 0, 0, 3, 0, 37, 28, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(171, 8, 60, 0, 10, 0, 0, 3, 0, 5, 4, 0, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(172, 8, 61, 0, 35, 0, 0, 4, 1, 17, 13, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(173, 9, 49, 1, 87, 0, 0, 0, 0, 61, 48, 6, 0, 1, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(174, 9, 50, 1, 85, 0, 0, 1, 0, 56, 49, 5, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(175, 9, 51, 1, 68, 0, 0, 1, 1, 38, 32, 2, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(176, 9, 52, 1, 70, 0, 0, 0, 0, 40, 35, 6, 0, 1, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(177, 9, 53, 1, 67, 0, 0, 1, 1, 52, 44, 3, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(178, 9, 54, 0, 35, 0, 0, 1, 0, 30, 23, 3, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(179, 9, 55, 1, 68, 0, 0, 1, 0, 35, 29, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(180, 9, 56, 1, 75, 0, 0, 4, 0, 34, 27, 0, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(181, 9, 57, 0, 10, 0, 0, 0, 0, 10, 9, 2, 0, 0, 0, 6.1, NULL, NULL, '2026-11-10 22:04:29'),
(182, 9, 58, 1, 69, 0, 0, 1, 1, 39, 34, 2, 0, 1, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(183, 9, 59, 1, 60, 0, 0, 1, 0, 35, 30, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(184, 9, 60, 1, 65, 0, 0, 3, 1, 29, 24, 0, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(185, 9, 61, 0, 32, 0, 0, 3, 1, 19, 15, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(186, 10, 49, 1, 79, 0, 0, 1, 1, 51, 39, 5, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(187, 10, 50, 0, 24, 0, 0, 0, 0, 23, 20, 5, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(188, 10, 51, 1, 69, 0, 0, 1, 0, 39, 30, 4, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(189, 10, 52, 1, 64, 0, 0, 1, 0, 36, 31, 5, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(190, 10, 53, 1, 70, 0, 0, 0, 0, 47, 41, 5, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(191, 10, 55, 0, 35, 0, 0, 2, 1, 16, 13, 0, 0, 1, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(192, 10, 56, 1, 79, 0, 0, 1, 1, 44, 33, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(193, 10, 57, 0, 21, 0, 0, 1, 1, 16, 12, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(194, 10, 58, 1, 70, 0, 0, 2, 0, 34, 29, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(195, 10, 59, 1, 63, 0, 0, 1, 0, 33, 28, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(196, 10, 60, 1, 71, 0, 0, 4, 0, 40, 34, 2, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(197, 10, 61, 0, 24, 0, 0, 3, 0, 15, 13, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(198, 10, 62, 0, 32, 0, 0, 3, 0, 15, 12, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(199, 11, 49, 1, 70, 0, 0, 0, 0, 46, 35, 5, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(200, 11, 50, 0, 21, 0, 0, 1, 0, 18, 14, 6, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(201, 11, 51, 1, 76, 0, 0, 1, 0, 42, 32, 4, 0, 1, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(202, 11, 52, 0, 26, 0, 0, 0, 0, 18, 14, 3, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(203, 11, 53, 1, 90, 0, 0, 1, 1, 67, 57, 3, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(204, 11, 54, 0, 14, 0, 0, 0, 0, 17, 13, 4, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(205, 11, 55, 1, 82, 0, 0, 1, 0, 37, 28, 0, 0, 1, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(206, 11, 56, 1, 65, 0, 0, 3, 0, 31, 24, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(207, 11, 57, 0, 16, 0, 0, 0, 0, 8, 6, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(208, 11, 58, 1, 70, 0, 0, 1, 1, 34, 30, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(209, 11, 59, 1, 84, 0, 0, 2, 0, 38, 30, 0, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(210, 11, 60, 0, 30, 0, 0, 3, 1, 15, 13, 1, 0, 1, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(211, 11, 61, 0, 34, 0, 0, 1, 0, 17, 15, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(212, 12, 49, 1, 88, 0, 0, 0, 0, 65, 53, 5, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(213, 12, 50, 1, 66, 0, 0, 1, 0, 45, 38, 3, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(214, 12, 51, 1, 82, 0, 0, 0, 0, 37, 31, 6, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(215, 12, 52, 0, 18, 0, 0, 0, 0, 15, 11, 3, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(216, 12, 53, 1, 64, 0, 0, 1, 1, 44, 35, 2, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(217, 12, 54, 0, 12, 0, 0, 0, 0, 12, 10, 4, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(218, 12, 55, 1, 78, 0, 0, 1, 0, 39, 29, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(219, 12, 56, 1, 70, 0, 1, 3, 0, 32, 26, 0, 0, 1, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(220, 12, 57, 0, 30, 0, 0, 0, 0, 19, 14, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(221, 12, 58, 1, 82, 0, 0, 1, 1, 41, 32, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(222, 12, 59, 1, 87, 0, 0, 3, 0, 39, 32, 0, 0, 1, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(223, 12, 60, 1, 62, 1, 0, 4, 2, 36, 32, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(224, 12, 62, 0, 19, 0, 0, 4, 1, 13, 11, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(225, 13, 49, 1, 84, 0, 0, 0, 0, 59, 46, 6, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(226, 13, 50, 1, 79, 0, 0, 1, 0, 57, 46, 3, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(227, 13, 51, 1, 62, 0, 0, 1, 1, 36, 27, 4, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(228, 13, 52, 0, 34, 0, 0, 0, 0, 23, 20, 3, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(229, 13, 53, 1, 79, 0, 0, 1, 1, 52, 44, 2, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(230, 13, 54, 0, 18, 0, 0, 0, 0, 14, 11, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(231, 13, 55, 1, 68, 0, 0, 3, 0, 38, 33, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(232, 13, 56, 1, 75, 0, 0, 3, 0, 36, 27, 2, 0, 1, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(233, 13, 57, 0, 10, 0, 0, 0, 0, 10, 8, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(234, 13, 58, 1, 88, 0, 0, 3, 1, 46, 38, 0, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(235, 13, 59, 0, 31, 1, 0, 3, 1, 20, 17, 0, 0, 0, 0, 7.7, NULL, NULL, '2026-11-10 22:04:29'),
(236, 13, 60, 1, 72, 0, 0, 1, 1, 36, 30, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(237, 13, 61, 0, 14, 0, 0, 3, 1, 8, 7, 2, 0, 0, 0, 6.1, NULL, NULL, '2026-11-10 22:04:29'),
(238, 13, 62, 0, 33, 0, 0, 2, 1, 20, 18, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(239, 14, 49, 1, 86, 0, 0, 1, 1, 59, 45, 5, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(240, 14, 50, 1, 90, 0, 0, 0, 0, 64, 56, 6, 0, 1, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(241, 14, 51, 1, 65, 0, 0, 0, 0, 34, 27, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(242, 14, 52, 1, 79, 0, 0, 1, 0, 43, 37, 5, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(243, 14, 53, 1, 67, 0, 0, 0, 0, 50, 37, 2, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(244, 14, 54, 0, 18, 0, 0, 0, 0, 16, 12, 3, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(245, 14, 55, 0, 35, 0, 0, 2, 1, 16, 13, 0, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(246, 14, 56, 1, 79, 0, 0, 3, 0, 43, 35, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(247, 14, 57, 0, 21, 0, 0, 1, 1, 16, 14, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(248, 14, 58, 0, 35, 0, 0, 2, 0, 19, 16, 0, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(249, 14, 59, 1, 69, 1, 0, 2, 1, 35, 28, 1, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(250, 14, 60, 1, 66, 0, 0, 4, 0, 31, 23, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(251, 14, 61, 0, 13, 0, 0, 2, 0, 7, 5, 1, 0, 0, 0, 6.0, NULL, NULL, '2026-11-10 22:04:29'),
(252, 15, 49, 1, 83, 0, 0, 0, 0, 56, 44, 2, 0, 1, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(253, 15, 50, 1, 61, 0, 0, 1, 0, 48, 39, 5, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(254, 15, 51, 1, 72, 0, 0, 1, 0, 32, 25, 5, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(255, 15, 52, 1, 83, 0, 0, 0, 0, 37, 27, 2, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(256, 15, 53, 1, 60, 0, 1, 1, 1, 47, 39, 5, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(257, 15, 54, 0, 35, 0, 0, 1, 1, 27, 20, 3, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(258, 15, 55, 1, 70, 0, 0, 3, 0, 40, 33, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(259, 15, 56, 1, 78, 0, 0, 2, 1, 39, 34, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(260, 15, 58, 1, 63, 0, 0, 3, 1, 36, 28, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(261, 15, 59, 1, 67, 0, 0, 4, 1, 38, 30, 2, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(262, 15, 60, 0, 22, 0, 0, 1, 1, 15, 12, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(263, 15, 61, 0, 16, 1, 0, 4, 1, 8, 6, 1, 0, 0, 0, 7.5, NULL, NULL, '2026-11-10 22:04:29'),
(264, 15, 62, 0, 29, 0, 0, 2, 1, 21, 17, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(265, 2, 49, 1, 84, 0, 0, 1, 0, 61, 49, 3, 0, 0, 0, 6.5, NULL, NULL, '2026-11-10 22:04:29'),
(266, 2, 50, 1, 71, 0, 0, 0, 0, 49, 42, 4, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(267, 2, 51, 1, 87, 0, 0, 0, 0, 44, 39, 6, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(268, 2, 52, 0, 27, 0, 0, 1, 1, 20, 18, 6, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(269, 2, 53, 1, 87, 0, 0, 0, 0, 63, 54, 4, 0, 0, 0, 6.8, NULL, NULL, '2026-11-10 22:04:29'),
(270, 2, 54, 0, 21, 0, 0, 1, 1, 19, 16, 3, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(271, 2, 55, 1, 84, 0, 1, 2, 0, 41, 32, 0, 0, 0, 0, 7.4, NULL, NULL, '2026-11-10 22:04:29'),
(272, 2, 56, 1, 73, 0, 0, 4, 1, 35, 29, 2, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(273, 2, 57, 0, 15, 0, 0, 1, 0, 8, 6, 1, 0, 1, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(274, 2, 58, 0, 29, 0, 0, 2, 1, 15, 13, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(275, 2, 59, 1, 85, 0, 0, 2, 1, 39, 33, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(276, 2, 60, 0, 23, 0, 0, 4, 1, 15, 13, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(277, 2, 62, 0, 26, 0, 0, 3, 1, 13, 11, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(278, 3, 49, 1, 69, 0, 0, 1, 0, 49, 43, 5, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(279, 3, 50, 0, 16, 0, 0, 0, 0, 15, 12, 5, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(280, 3, 51, 1, 60, 0, 0, 0, 0, 34, 27, 6, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(281, 3, 52, 0, 35, 0, 0, 1, 1, 24, 19, 2, 0, 0, 0, 6.7, NULL, NULL, '2026-11-10 22:04:29'),
(282, 3, 53, 1, 81, 0, 0, 0, 0, 58, 49, 3, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(283, 3, 54, 0, 17, 0, 0, 1, 0, 18, 15, 4, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(284, 3, 55, 1, 87, 0, 0, 4, 0, 43, 35, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29'),
(285, 3, 56, 0, 19, 0, 1, 2, 0, 13, 10, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(286, 3, 57, 0, 29, 0, 0, 1, 0, 17, 14, 1, 0, 0, 0, 6.0, NULL, NULL, '2026-11-10 22:04:29'),
(287, 3, 58, 0, 21, 0, 0, 4, 1, 16, 12, 1, 0, 0, 0, 6.6, NULL, NULL, '2026-11-10 22:04:29'),
(288, 3, 59, 1, 68, 0, 0, 1, 0, 33, 29, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(289, 3, 60, 0, 27, 0, 0, 2, 1, 13, 11, 1, 0, 0, 0, 6.0, NULL, NULL, '2026-11-10 22:04:29'),
(290, 3, 62, 0, 24, 0, 0, 1, 1, 12, 10, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(291, 4, 49, 1, 74, 0, 0, 1, 0, 56, 48, 3, 0, 0, 0, 7.3, NULL, NULL, '2026-11-10 22:04:29'),
(292, 4, 50, 0, 28, 0, 0, 0, 0, 26, 21, 5, 0, 0, 0, 6.1, NULL, NULL, '2026-11-10 22:04:29'),
(293, 4, 51, 1, 61, 0, 0, 0, 0, 34, 27, 6, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(294, 4, 52, 1, 73, 0, 1, 1, 1, 40, 35, 5, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(295, 4, 53, 1, 62, 0, 0, 0, 0, 41, 33, 4, 0, 0, 0, 7.2, NULL, NULL, '2026-11-10 22:04:29'),
(296, 4, 54, 0, 28, 0, 0, 0, 0, 22, 19, 5, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(297, 4, 55, 1, 89, 0, 0, 2, 0, 40, 32, 0, 0, 0, 0, 7.1, NULL, NULL, '2026-11-10 22:04:29'),
(298, 4, 56, 1, 72, 0, 0, 3, 0, 34, 26, 2, 0, 0, 0, 6.4, NULL, NULL, '2026-11-10 22:04:29'),
(299, 4, 57, 0, 19, 0, 0, 1, 0, 17, 14, 2, 0, 0, 0, 7.0, NULL, NULL, '2026-11-10 22:04:29'),
(300, 4, 58, 0, 25, 0, 0, 2, 1, 15, 12, 1, 0, 0, 0, 6.0, NULL, NULL, '2026-11-10 22:04:29'),
(301, 4, 59, 1, 64, 0, 0, 2, 0, 36, 29, 1, 0, 0, 0, 6.3, NULL, NULL, '2026-11-10 22:04:29'),
(302, 4, 60, 0, 23, 0, 0, 4, 1, 16, 13, 0, 0, 0, 0, 6.2, NULL, NULL, '2026-11-10 22:04:29'),
(303, 4, 62, 0, 26, 0, 0, 3, 1, 13, 11, 1, 0, 0, 0, 6.9, NULL, NULL, '2026-11-10 22:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','pelatih_medis','pemain') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `nama_lengkap`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$EIVMQ2njTqSaDmxrCAhPNeTCNG4OO5odcUimXNf1lqCWgDSvaeqY2', 'Admin Klub', 'admin', 1, '2026-11-10 22:56:52', '2026-11-04 03:58:05'),
(2, 'hr1', '$2y$10$HDPSjSgJPDzoIJOUpCJUFeF6t3R0QojMie4n6xOnQuZWil71ZjCC.', 'Staf HR Contoh', 'admin', 1, NULL, '2026-11-04 03:58:05'),
(3, 'medis1', '$2y$10$lv2VkzeQVVBD8cGTSxJZ5uFD/i9SAK2UOW5qBZNJioMY4lQdy.ovm', 'Dokter Tim Contoh', 'pelatih_medis', 1, '2026-11-11 10:03:20', '2026-11-04 03:58:05'),
(4, 'pemain1', '$2y$10$S8tlvfnpjyf3nDcZcSr9Eed4P8.uBkgsGbULT9x03hC0UZSzbvRoC', 'Pemain Contoh', 'pemain', 1, '2026-11-10 10:22:42', '2026-11-04 03:58:05'),
(5, 'staf1', '$2y$10$qljJJfoAHiQmeaslF8wQTedRsL8aXae7yIa579aRcWbQmIZCtBGe6', 'Staf Contoh', 'pelatih_medis', 1, '2026-11-04 20:04:26', '2026-11-04 03:58:05'),
(6, 'demo_manager', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Hendra Wijaya', 'admin', 1, '2026-11-11 10:52:27', '2026-11-08 15:29:56'),
(7, 'demo_pelatih', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Agus Santoso', 'pelatih_medis', 1, '2026-11-11 10:52:11', '2026-11-08 15:29:56'),
(8, 'demo_dokter', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'dr. Maya Kusuma', 'pelatih_medis', 1, '2026-11-11 10:52:26', '2026-11-08 15:29:56'),
(9, 'demo_budi', '$2y$10$lXYGZRKVdPTUx.0S5M9CouVgI7YMYgnqXH0WJ./ttX9LWp8EyxdlO', 'Budi Prasetyo', 'pemain', 1, '2026-11-11 10:52:21', '2026-11-08 15:29:56'),
(10, 'demo_andi', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Andi Pratama', 'pemain', 1, '2026-11-09 22:51:48', '2026-11-08 15:29:56'),
(11, 'demo_rizky', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Rizky Ramadhan', 'pemain', 1, '2026-11-10 22:34:45', '2026-11-08 15:29:56'),
(12, 'demo_fajar', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Fajar Nugroho', 'pemain', 1, '2026-11-11 05:00:50', '2026-11-08 15:29:56'),
(49, 'demo_lucas', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Lucas Ferreira', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(50, 'demo_bagas', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Bagas Setiawan', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(51, 'demo_arif', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Arif Hidayat', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(52, 'demo_dimas', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Dimas Kurniawan', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(53, 'demo_kenji', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Kenji Watanabe', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(54, 'demo_hendra', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Hendra Gunawan', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(55, 'demo_wahyu', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Wahyu Firmansyah', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(56, 'demo_rafael', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Rafael Siregar', 'pemain', 1, '2026-11-11 10:52:10', '2026-11-10 15:04:52'),
(57, 'demo_galih', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Galih Permana', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(58, 'demo_ahmad', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Ahmad Fauzan', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(59, 'demo_marcelo', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Marcelo Santos', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(60, 'demo_thijs', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Thijs Vermeulen', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(61, 'demo_reza', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Reza Aditya', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(62, 'demo_daffa', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Daffa Alamsyah', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(63, 'demo_rizal', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Rizal Maulana', 'pemain', 1, '2026-11-11 10:52:11', '2026-11-10 15:04:52'),
(64, 'demo_yusuf', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Yusuf Habibi', 'pemain', 1, NULL, '2026-11-10 15:04:52'),
(65, 'demo_samuel', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Samuel Tanoto', 'pemain', 1, '2026-11-11 10:52:11', '2026-11-10 15:04:52'),
(66, 'demo_nur', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Nur Aisyah', 'admin', 1, NULL, '2026-11-10 15:04:52'),
(67, 'demo_joko', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Joko Susilo', 'admin', 1, NULL, '2026-11-10 15:04:52'),
(68, 'demo_putri', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Putri Handayani', 'admin', 1, NULL, '2026-11-10 15:04:52'),
(69, 'demo_riyanto', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Agus Riyanto', 'pelatih_medis', 1, NULL, '2026-11-10 15:04:52'),
(70, 'demo_eko', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Eko Prasetyo', 'pelatih_medis', 1, NULL, '2026-11-10 15:04:52'),
(71, 'demo_sanusi', '$2y$10$ezhb8uPOIW.cp7dt9EIHW.Om8DfcaRDZzY2RgkODEQO/aZqavSf3W', 'Ahmad Sanusi', 'pelatih_medis', 1, NULL, '2026-11-10 15:04:52');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_cedera_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_cedera_aktif` (
`cedera_id` int(10) unsigned
,`user_id` int(10) unsigned
,`nama_lengkap` varchar(100)
,`nomor_punggung` tinyint(3) unsigned
,`jenis_cedera` varchar(100)
,`bagian_tubuh` varchar(60)
,`tingkat` enum('ringan','sedang','berat')
,`status` enum('dirawat','pemulihan','pulih')
,`tanggal_cedera` date
,`estimasi_pulih` date
,`hari_absen` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_kontrak_hampir_habis`
-- (See below for the actual view)
--
CREATE TABLE `v_kontrak_hampir_habis` (
`kontrak_id` int(10) unsigned
,`user_id` int(10) unsigned
,`nama_lengkap` varchar(100)
,`role` enum('admin','pelatih_medis','pemain')
,`tanggal_selesai` date
,`sisa_hari` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_rekap_gaji_bulanan`
-- (See below for the actual view)
--
CREATE TABLE `v_rekap_gaji_bulanan` (
`periode` date
,`jumlah_penerima` bigint(21)
,`total_bersih` decimal(37,2)
,`sudah_dibayar` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_rekap_kehadiran`
-- (See below for the actual view)
--
CREATE TABLE `v_rekap_kehadiran` (
`user_id` int(10) unsigned
,`nama_lengkap` varchar(100)
,`total_catatan` bigint(21)
,`hadir` decimal(23,0)
,`persen_hadir` decimal(28,1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_statistik_kompetisi`
-- (See below for the actual view)
--
CREATE TABLE `v_statistik_kompetisi` (
`user_id` int(10) unsigned
,`musim` varchar(7)
,`kompetisi` varchar(100)
,`main` bigint(21)
,`starter` decimal(25,0)
,`menit` decimal(25,0)
,`goal` decimal(25,0)
,`assist` decimal(25,0)
,`kartu_kuning` decimal(25,0)
,`kartu_merah` decimal(25,0)
,`rata_rating` decimal(5,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_statistik_pemain`
-- (See below for the actual view)
--
CREATE TABLE `v_statistik_pemain` (
`user_id` int(10) unsigned
,`nama_lengkap` varchar(100)
,`nomor_punggung` tinyint(3) unsigned
,`main` bigint(21)
,`menit` decimal(25,0)
,`goal` decimal(25,0)
,`assist` decimal(25,0)
,`kartu_kuning` decimal(25,0)
,`kartu_merah` decimal(25,0)
,`rata_rating` decimal(5,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_cedera_aktif`
--
DROP TABLE IF EXISTS `v_cedera_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cedera_aktif`  AS SELECT `c`.`id` AS `cedera_id`, `u`.`id` AS `user_id`, `u`.`nama_lengkap` AS `nama_lengkap`, `p`.`nomor_punggung` AS `nomor_punggung`, `c`.`jenis_cedera` AS `jenis_cedera`, `c`.`bagian_tubuh` AS `bagian_tubuh`, `c`.`tingkat` AS `tingkat`, `c`.`status` AS `status`, `c`.`tanggal_cedera` AS `tanggal_cedera`, `c`.`estimasi_pulih` AS `estimasi_pulih`, to_days(curdate()) - to_days(`c`.`tanggal_cedera`) AS `hari_absen` FROM ((`cedera` `c` join `users` `u` on(`u`.`id` = `c`.`user_id`)) left join `pemain` `p` on(`p`.`user_id` = `u`.`id`)) WHERE `c`.`status` <> 'pulih' ;

-- --------------------------------------------------------

--
-- Structure for view `v_kontrak_hampir_habis`
--
DROP TABLE IF EXISTS `v_kontrak_hampir_habis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_kontrak_hampir_habis`  AS SELECT `k`.`id` AS `kontrak_id`, `u`.`id` AS `user_id`, `u`.`nama_lengkap` AS `nama_lengkap`, `u`.`role` AS `role`, `k`.`tanggal_selesai` AS `tanggal_selesai`, to_days(`k`.`tanggal_selesai`) - to_days(curdate()) AS `sisa_hari` FROM (`kontrak` `k` join `users` `u` on(`u`.`id` = `k`.`user_id`)) WHERE `k`.`status` = 'aktif' AND `k`.`tanggal_selesai` between curdate() and curdate() + interval 90 day ;

-- --------------------------------------------------------

--
-- Structure for view `v_rekap_gaji_bulanan`
--
DROP TABLE IF EXISTS `v_rekap_gaji_bulanan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekap_gaji_bulanan`  AS SELECT `penggajian`.`periode` AS `periode`, count(0) AS `jumlah_penerima`, sum(`penggajian`.`total_bersih`) AS `total_bersih`, sum(case when `penggajian`.`status` = 'dibayar' then `penggajian`.`total_bersih` else 0 end) AS `sudah_dibayar` FROM `penggajian` GROUP BY `penggajian`.`periode` ;

-- --------------------------------------------------------

--
-- Structure for view `v_rekap_kehadiran`
--
DROP TABLE IF EXISTS `v_rekap_kehadiran`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekap_kehadiran`  AS SELECT `u`.`id` AS `user_id`, `u`.`nama_lengkap` AS `nama_lengkap`, count(0) AS `total_catatan`, sum(`k`.`status` in ('hadir','terlambat')) AS `hadir`, round(100 * sum(`k`.`status` in ('hadir','terlambat')) / count(0),1) AS `persen_hadir` FROM (`kehadiran` `k` join `users` `u` on(`u`.`id` = `k`.`user_id`)) GROUP BY `u`.`id`, `u`.`nama_lengkap` ;

-- --------------------------------------------------------

--
-- Structure for view `v_statistik_kompetisi`
--
DROP TABLE IF EXISTS `v_statistik_kompetisi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistik_kompetisi`  AS SELECT `s`.`user_id` AS `user_id`, `p`.`musim` AS `musim`, `p`.`kompetisi` AS `kompetisi`, count(0) AS `main`, sum(`s`.`starter`) AS `starter`, sum(`s`.`menit_bermain`) AS `menit`, sum(`s`.`goal`) AS `goal`, sum(`s`.`assist`) AS `assist`, sum(`s`.`kartu_kuning`) AS `kartu_kuning`, sum(`s`.`kartu_merah`) AS `kartu_merah`, round(avg(`s`.`rating`),2) AS `rata_rating` FROM (`statistik_pertandingan` `s` join `pertandingan` `p` on(`p`.`jadwal_id` = `s`.`jadwal_id`)) GROUP BY `s`.`user_id`, `p`.`musim`, `p`.`kompetisi` ;

-- --------------------------------------------------------

--
-- Structure for view `v_statistik_pemain`
--
DROP TABLE IF EXISTS `v_statistik_pemain`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistik_pemain`  AS SELECT `u`.`id` AS `user_id`, `u`.`nama_lengkap` AS `nama_lengkap`, `p`.`nomor_punggung` AS `nomor_punggung`, count(`s`.`id`) AS `main`, coalesce(sum(`s`.`menit_bermain`),0) AS `menit`, coalesce(sum(`s`.`goal`),0) AS `goal`, coalesce(sum(`s`.`assist`),0) AS `assist`, coalesce(sum(`s`.`kartu_kuning`),0) AS `kartu_kuning`, coalesce(sum(`s`.`kartu_merah`),0) AS `kartu_merah`, round(avg(`s`.`rating`),2) AS `rata_rating` FROM ((`users` `u` left join `pemain` `p` on(`p`.`user_id` = `u`.`id`)) left join `statistik_pertandingan` `s` on(`s`.`user_id` = `u`.`id`)) WHERE `u`.`role` = 'pemain' GROUP BY `u`.`id`, `u`.`nama_lengkap`, `p`.`nomor_punggung` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cedera`
--
ALTER TABLE `cedera`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cedera_user` (`user_id`,`status`),
  ADD KEY `idx_cedera_status` (`status`),
  ADD KEY `fk_cedera_oleh` (`dicatat_oleh`);

--
-- Indexes for table `dokumen_karyawan`
--
ALTER TABLE `dokumen_karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dokumen_jalur` (`jalur`),
  ADD KEY `idx_dokumen_user` (`user_id`,`jenis`),
  ADD KEY `fk_dokumen_oleh` (`diunggah_oleh`);

--
-- Indexes for table `evaluasi_performa`
--
ALTER TABLE `evaluasi_performa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evaluasi_user` (`user_id`,`tanggal`),
  ADD KEY `fk_evaluasi_penilai` (`penilai_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jadwal_waktu` (`waktu_mulai`),
  ADD KEY `idx_jadwal_jenis` (`jenis`,`waktu_mulai`);

--
-- Indexes for table `kehadiran`
--
ALTER TABLE `kehadiran`
  ADD PRIMARY KEY (`jadwal_id`,`user_id`),
  ADD KEY `idx_kehadiran_user` (`user_id`),
  ADD KEY `fk_kehadiran_oleh` (`dicatat_oleh`);

--
-- Indexes for table `kontrak`
--
ALTER TABLE `kontrak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontrak_nomor` (`nomor_kontrak`),
  ADD KEY `idx_kontrak_user` (`user_id`,`status`),
  ADD KEY `idx_kontrak_selesai` (`tanggal_selesai`),
  ADD KEY `fk_kontrak_oleh` (`dibuat_oleh`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_user` (`user_id`,`created_at`),
  ADD KEY `idx_log_entitas` (`entitas`,`entitas_id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifikasi_user` (`user_id`,`dibaca_pada`,`created_at`),
  ADD KEY `idx_notifikasi_kategori` (`user_id`,`kategori`);

--
-- Indexes for table `notifikasi_preferensi`
--
ALTER TABLE `notifikasi_preferensi`
  ADD PRIMARY KEY (`user_id`,`kategori`);

--
-- Indexes for table `pemain`
--
ALTER TABLE `pemain`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_pemain_nomor` (`nomor_punggung`);

--
-- Indexes for table `pemeriksaan_kebugaran`
--
ALTER TABLE `pemeriksaan_kebugaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kebugaran_user` (`user_id`,`tanggal`),
  ADD KEY `fk_kebugaran_pemeriksa` (`pemeriksa_id`);

--
-- Indexes for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pengajuan_user` (`user_id`,`status`),
  ADD KEY `idx_pengajuan_status` (`status`,`created_at`),
  ADD KEY `fk_pengajuan_tinjau` (`ditinjau_oleh`);

--
-- Indexes for table `penggajian`
--
ALTER TABLE `penggajian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gaji_user_periode` (`user_id`,`periode`),
  ADD KEY `idx_gaji_periode` (`periode`,`status`),
  ADD KEY `fk_gaji_kontrak` (`kontrak_id`),
  ADD KEY `fk_gaji_oleh` (`dibuat_oleh`);

--
-- Indexes for table `penggajian_rincian`
--
ALTER TABLE `penggajian_rincian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rincian_kode` (`penggajian_id`,`kode`);

--
-- Indexes for table `pengingat_dokumen`
--
ALTER TABLE `pengingat_dokumen`
  ADD PRIMARY KEY (`dokumen_id`,`tahap`);

--
-- Indexes for table `pengingat_kontrak`
--
ALTER TABLE `pengingat_kontrak`
  ADD PRIMARY KEY (`kontrak_id`,`tahap`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pengumuman_tampil` (`sasaran`,`disematkan`,`created_at`),
  ADD KEY `fk_pengumuman_oleh` (`dibuat_oleh`);

--
-- Indexes for table `perawatan_cedera`
--
ALTER TABLE `perawatan_cedera`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_perawatan_cedera` (`cedera_id`,`tanggal`),
  ADD KEY `fk_perawatan_petugas` (`petugas_id`);

--
-- Indexes for table `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD KEY `idx_pertandingan_status` (`status`),
  ADD KEY `idx_pertandingan_musim` (`musim`,`kompetisi`);

--
-- Indexes for table `profil_pemain`
--
ALTER TABLE `profil_pemain`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_profil_posisi` (`posisi`),
  ADD KEY `idx_profil_status` (`status`);

--
-- Indexes for table `program_latihan`
--
ALTER TABLE `program_latihan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_program_kode` (`kode`),
  ADD KEY `idx_program_status` (`status`,`tanggal_mulai`),
  ADD KEY `fk_program_oleh` (`dibuat_oleh`);

--
-- Indexes for table `program_latihan_peserta`
--
ALTER TABLE `program_latihan_peserta`
  ADD PRIMARY KEY (`program_id`,`user_id`),
  ADD KEY `idx_peserta_user` (`user_id`);

--
-- Indexes for table `program_latihan_sesi`
--
ALTER TABLE `program_latihan_sesi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sesi_urutan` (`program_id`,`urutan`);

--
-- Indexes for table `riwayat_karier`
--
ALTER TABLE `riwayat_karier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_karier_user` (`user_id`,`tanggal_mulai`);

--
-- Indexes for table `staf`
--
ALTER TABLE `staf`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_staf_nip` (`nip`),
  ADD KEY `idx_staf_jabatan` (`jabatan`),
  ADD KEY `idx_staf_divisi` (`divisi`),
  ADD KEY `fk_staf_atasan` (`atasan_id`);

--
-- Indexes for table `statistik_pertandingan`
--
ALTER TABLE `statistik_pertandingan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_statistik_jadwal_user` (`jadwal_id`,`user_id`),
  ADD KEY `idx_statistik_user` (`user_id`),
  ADD KEY `fk_statistik_oleh` (`dicatat_oleh`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cedera`
--
ALTER TABLE `cedera`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `dokumen_karyawan`
--
ALTER TABLE `dokumen_karyawan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `evaluasi_performa`
--
ALTER TABLE `evaluasi_performa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `kontrak`
--
ALTER TABLE `kontrak`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=428;

--
-- AUTO_INCREMENT for table `pemeriksaan_kebugaran`
--
ALTER TABLE `pemeriksaan_kebugaran`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `penggajian`
--
ALTER TABLE `penggajian`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=316;

--
-- AUTO_INCREMENT for table `penggajian_rincian`
--
ALTER TABLE `penggajian_rincian`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `perawatan_cedera`
--
ALTER TABLE `perawatan_cedera`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `program_latihan`
--
ALTER TABLE `program_latihan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program_latihan_sesi`
--
ALTER TABLE `program_latihan_sesi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `riwayat_karier`
--
ALTER TABLE `riwayat_karier`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statistik_pertandingan`
--
ALTER TABLE `statistik_pertandingan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cedera`
--
ALTER TABLE `cedera`
  ADD CONSTRAINT `fk_cedera_oleh` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cedera_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen_karyawan`
--
ALTER TABLE `dokumen_karyawan`
  ADD CONSTRAINT `fk_dokumen_oleh` FOREIGN KEY (`diunggah_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluasi_performa`
--
ALTER TABLE `evaluasi_performa`
  ADD CONSTRAINT `fk_evaluasi_penilai` FOREIGN KEY (`penilai_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_evaluasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kehadiran`
--
ALTER TABLE `kehadiran`
  ADD CONSTRAINT `fk_kehadiran_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kehadiran_oleh` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kehadiran_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kontrak`
--
ALTER TABLE `kontrak`
  ADD CONSTRAINT `fk_kontrak_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kontrak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notifikasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi_preferensi`
--
ALTER TABLE `notifikasi_preferensi`
  ADD CONSTRAINT `fk_notif_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemain`
--
ALTER TABLE `pemain`
  ADD CONSTRAINT `fk_pemain_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemeriksaan_kebugaran`
--
ALTER TABLE `pemeriksaan_kebugaran`
  ADD CONSTRAINT `fk_kebugaran_pemeriksa` FOREIGN KEY (`pemeriksa_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kebugaran_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `fk_pengajuan_tinjau` FOREIGN KEY (`ditinjau_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pengajuan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penggajian`
--
ALTER TABLE `penggajian`
  ADD CONSTRAINT `fk_gaji_kontrak` FOREIGN KEY (`kontrak_id`) REFERENCES `kontrak` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gaji_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gaji_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penggajian_rincian`
--
ALTER TABLE `penggajian_rincian`
  ADD CONSTRAINT `fk_rincian_gaji` FOREIGN KEY (`penggajian_id`) REFERENCES `penggajian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengingat_dokumen`
--
ALTER TABLE `pengingat_dokumen`
  ADD CONSTRAINT `fk_pengingat_dokumen` FOREIGN KEY (`dokumen_id`) REFERENCES `dokumen_karyawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengingat_kontrak`
--
ALTER TABLE `pengingat_kontrak`
  ADD CONSTRAINT `fk_pengingat_kontrak` FOREIGN KEY (`kontrak_id`) REFERENCES `kontrak` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `fk_pengumuman_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `perawatan_cedera`
--
ALTER TABLE `perawatan_cedera`
  ADD CONSTRAINT `fk_perawatan_cedera` FOREIGN KEY (`cedera_id`) REFERENCES `cedera` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_perawatan_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD CONSTRAINT `fk_pertandingan_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profil_pemain`
--
ALTER TABLE `profil_pemain`
  ADD CONSTRAINT `fk_profil_pemain_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_latihan`
--
ALTER TABLE `program_latihan`
  ADD CONSTRAINT `fk_program_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `program_latihan_peserta`
--
ALTER TABLE `program_latihan_peserta`
  ADD CONSTRAINT `fk_peserta_program` FOREIGN KEY (`program_id`) REFERENCES `program_latihan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_peserta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_latihan_sesi`
--
ALTER TABLE `program_latihan_sesi`
  ADD CONSTRAINT `fk_sesi_program` FOREIGN KEY (`program_id`) REFERENCES `program_latihan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_karier`
--
ALTER TABLE `riwayat_karier`
  ADD CONSTRAINT `fk_karier_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staf`
--
ALTER TABLE `staf`
  ADD CONSTRAINT `fk_staf_atasan` FOREIGN KEY (`atasan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `statistik_pertandingan`
--
ALTER TABLE `statistik_pertandingan`
  ADD CONSTRAINT `fk_statistik_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_statistik_oleh` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_statistik_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
