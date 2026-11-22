# Halaman Login - Sistem SDM Klub Sepak Bola

Login dengan username dan password (PHP native + MySQL), lengkap dengan dashboard per peran.

## Struktur file

| File | Fungsi |
| --- | --- |
| `login.php` | Form login dan proses autentikasi |
| `dashboard.php` | Dashboard per peran (router) + kelola pengumuman klub oleh admin |
| `inc_dasbor_admin.php` | (Admin) KPI tenaga kerja & kontrak, beban gaji 6 bulan (pemain vs staf), distribusi posisi, kontrak segera berakhir, aktivitas sistem, agenda, pengajuan menunggu, pengumuman |
| `inc_dasbor_staf.php` | (Pelatih/medis) Ketersediaan skuad, ringkasan kebugaran & perlu perhatian, cedera aktif (progres kembali bermain), agenda, performa terbaik musim |
| `inc_dasbor_pemain.php` | (Pemain) Kartu profil + laga berikutnya, statistik musim, kontrak, slip gaji terbaru, tren kebugaran, jadwal, status pengajuan, pengumuman |
| `lib_dasbor.php` | Komponen dashboard bersama: sapaan, agenda, daftar & dialog pengumuman |
| `profil.php` | Profil Saya: ubah kontak, kontak darurat, foto; ajukan perubahan data terkunci; riwayat karier |
| `ganti_password.php` | Ganti password (syarat langsung, pengukur kekuatan, penguncian setelah 5 kali salah) |
| `statistik.php` | Statistik per musim & kompetisi, tren rating, riwayat laga (pemain: miliknya; staf: seluruh skuad / `?pemain=ID`) |
| `cedera.php` | Riwayat cedera, status ketersediaan, progres kembali bermain, riwayat penanganan |
| `kebugaran.php` | Hasil tes fisik (skor, VO₂max, sprint 30 m, Yo-Yo), tren skor, perbandingan dengan rata-rata tim |
| `latihan.php` | Program latihan, jadwal sesi mingguan, progres peserta, catatan pelatih |
| `inc_kelola_umum.php` | Pembantu CRUD staf Performa & Medis: validasi input, form dialog, tombol ubah/hapus, sinkron total gol & status cedera |
| `inc_kelola_statistik.php` | (Staf) Input/ubah/hapus statistik per laga, peringkat 10 besar (Gol/Assist/Rating/Menit), catatan per pertandingan |
| `inc_kelola_cedera.php` | (Staf) Catat/ubah/hapus cedera, tambah penanganan, tandai pulih |
| `inc_kelola_kebugaran.php` | (Staf) Input hasil tes (skor otomatis), distribusi level, tren skor per pemain/tim, semua hasil tes |
| `inc_kelola_latihan.php` | (Staf) Program baru/ubah/hapus (peserta & sesi mingguan), tambah progres +10% per peserta |
| `kontrak.php` | Kontrak Saya (masa berlaku, ketentuan, bonus performa) dan `?hal=riwayat` (semua versi + perubahan) |
| `gaji.php`, `gaji_pdf.php` | Slip gaji per bulan (rincian, tren, info pembayaran) dan unduh PDF |
| `lib_gaji.php`, `pdf_sederhana.php` | Fungsi slip gaji (terbilang, rincian) dan pembuat PDF tanpa library tambahan |
| `jadwal.php`, `jadwal_ics.php` | Kalender bulan/minggu, jadwal latihan, pertandingan, rapat & tandang; ekspor `.ics` |
| `pengajuan.php` | Pengajuan cuti/sakit (saldo cuti) dan `?hal=izin`; admin dapat menyetujui/menolak |
| `kehadiran.php` | Riwayat kehadiran, jejak 8 minggu, rekap per jenis kegiatan |
| `data_pemain.php`, `data_staf.php` | (Admin) Data skuad & staf: filter, tambah/ubah, nonaktifkan, struktur organisasi |
| `kelola_kontrak.php` | (Admin) Daftar, riwayat, dan kontrak hampir habis; kontrak baru, perpanjang, akhiri; pengingat H-90/60/30 |
| `kelola_gaji.php`, `gaji_semua.php` | (Admin) Data penggajian: buat draf bulanan, setujui, tandai dibayar; tab semua slip |
| `ringkasan_gaji.php` | (Admin) Pengeluaran gaji per bulan, perbandingan kontrak, gaji per posisi |
| `detail_karyawan.php` | (Admin) Detail pemain/staf: profil, riwayat karier, kontrak & gaji, dokumen, performa & medis |
| `dokumen_unduh.php`, `uploads/dokumen/` | Unduh dokumen karyawan (hanya admin/pemilik); akses langsung folder ditolak `.htaccess`; `contoh_*.pdf` = berkas data demo |
| `notifikasi.php` | Pusat notifikasi: tab Semua/Belum dibaca/Penting, kelompok tanggal, saring kategori, tandai dibaca/belum, hapus, urungkan, pengaturan per kategori |
| `lib_notifikasi.php` | Panel lonceng (dropdown), `kirim_notifikasi()` yang menghormati preferensi, pengingat otomatis kontrak & dokumen kedaluwarsa (H-60/H-30) |
| `ekspor.php`, `admin_lib.php` | (Admin) Ekspor CSV untuk Excel; fungsi bersama halaman admin |
| `tampilan.php` | Kerangka bersama: bilah atas, sidebar, menu akun, fungsi bantu tampilan |
| `assets/app.css`, `assets/app.js` | Gaya dan skrip bersama semua halaman |
| `uploads/foto/` | Foto profil (skrip dinonaktifkan lewat `.htaccess`) |
| `logout.php` | Keluar (hanya lewat POST dengan token CSRF) |
| `auth.php` | Sesi aman, token CSRF, `require_login()`, `require_role()` |
| `koneksi.php` | Koneksi database (PDO) |
| `database.sql` | Membuat database `sdm_klub` lengkap (27 tabel + 6 view) untuk admin, pelatih/medis, dan pemain |
| `data_akun_awal.sql` | Data lengkap untuk akun awal (admin, hr1, medis1, pemain1, staf1); impor setelah `data_contoh.sql` |
| `data_contoh.sql` | Data demo opsional (akun `demo_*`, password `Demo#Klub2026`); nonaktifkan sebelum produksi |
| `seed.php` | Membuat 5 akun contoh dengan password acak (hapus setelah dipakai) |

## Cara menjalankan (XAMPP / Laragon)

1. Salin semua file ke folder web server, misalnya `htdocs/sdm-klub/`.
2. Nyalakan Apache dan MySQL.
3. Impor `database.sql` lewat phpMyAdmin (atau `mysql -u root -p < database.sql`).
4. Sesuaikan `DB_USER` dan `DB_PASS` di `koneksi.php` bila perlu.
5. Buka `http://localhost/sdm-klub/seed.php`, catat username dan password yang tampil, lalu **hapus `seed.php`**.
6. Buka `http://localhost/sdm-klub/login.php` dan masuk.

Alternatif tanpa XAMPP (butuh PHP dan MySQL terpasang): jalankan `php -S localhost:8000` di folder proyek, lalu buka `http://localhost:8000/login.php`.

## Fitur keamanan

- Query memakai prepared statement (aman dari SQL injection).
- Password disimpan dengan `password_hash()` dan dicek dengan `password_verify()`.
- `session_regenerate_id(true)` saat login berhasil (mencegah session fixation).
- Cookie sesi `HttpOnly`, `SameSite=Lax`, dan `Secure` otomatis saat HTTPS.
- Token CSRF di form login dan logout.
- Pesan galat login sengaja umum, dan waktu respons disamakan untuk username yang tidak ada.
- Penguncian sementara 5 menit setelah 5 kali gagal.
- Sesi berakhir otomatis setelah 30 menit tanpa aktivitas.
- Semua keluaran HTML di-escape.

## Catatan

- Penguncian percobaan gagal disimpan di sesi, jadi penyerang yang membuang cookie bisa menghindarinya. Untuk produksi, simpan hitungan gagal di database atau per alamat IP.
- Gunakan HTTPS di server produksi.
- Menambah halaman terlindungi: di baris awal file panggil `start_secure_session(); require_login();`. Untuk membatasi peran: `require_role('admin');`.
