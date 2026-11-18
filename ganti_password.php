<?php
declare(strict_types=1);

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const PW_MIN          = 8;
const PW_MAKS_BYTE    = 72;      // batas bcrypt: karakter setelah 72 byte diabaikan
const PW_MAKS_GAGAL   = 5;       // percobaan password lama yang salah sebelum dikunci
const PW_DURASI_KUNCI = 300;     // 5 menit

$userId = (int) $_SESSION['user_id'];
$galat  = [];   // galat per kolom
$umum   = '';   // galat umum (mis. akun dikunci)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lama   = (string) ($_POST['password_lama'] ?? '');
    $baru   = (string) ($_POST['password_baru'] ?? '');
    $ulang  = (string) ($_POST['password_ulang'] ?? '');
    $kunci  = (int) ($_SESSION['pw_kunci_sampai'] ?? 0);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $umum = 'Formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($kunci > time()) {
        $umum = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . (int) ceil(($kunci - time()) / 60) . ' menit.';
    } else {
        if ($lama === '') {
            $galat['password_lama'] = 'Masukkan password saat ini.';
        }
        if (strlen($baru) < PW_MIN) {
            $galat['password_baru'] = 'Password baru minimal ' . PW_MIN . ' karakter.';
        } elseif (strlen($baru) > PW_MAKS_BYTE) {
            $galat['password_baru'] = 'Password baru terlalu panjang (maksimal ' . PW_MAKS_BYTE . ' karakter).';
        } elseif (!preg_match('/[A-Za-z]/', $baru) || !preg_match('/[0-9]/', $baru)) {
            $galat['password_baru'] = 'Password baru harus mengandung huruf dan angka.';
        } elseif ($baru === $lama) {
            $galat['password_baru'] = 'Password baru harus berbeda dari password saat ini.';
        }
        if ($ulang !== $baru) {
            $galat['password_ulang'] = 'Konfirmasi password tidak cocok.';
        }

        if (!$galat) {
            try {
                $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id AND is_active = 1');
                $stmt->execute([':id' => $userId]);
                $hash = $stmt->fetchColumn();

                if (!is_string($hash) || !password_verify($lama, $hash)) {
                    $_SESSION['pw_gagal'] = (int) ($_SESSION['pw_gagal'] ?? 0) + 1;
                    if ($_SESSION['pw_gagal'] >= PW_MAKS_GAGAL) {
                        $_SESSION['pw_kunci_sampai'] = time() + PW_DURASI_KUNCI;
                        $_SESSION['pw_gagal'] = 0;
                        $umum = 'Terlalu banyak percobaan gagal. Coba lagi dalam 5 menit.';
                        catat_aktivitas('gagal', 'users', $userId, 'Terlalu banyak salah password saat ganti password');
                    } else {
                        $galat['password_lama'] = 'Password saat ini salah.';
                    }
                } else {
                    $upd = db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                    $upd->execute([':h' => password_hash($baru, PASSWORD_DEFAULT), ':id' => $userId]);

                    unset($_SESSION['pw_gagal'], $_SESSION['pw_kunci_sampai']);
                    session_regenerate_id(true);   // sesi baru setelah kredensial berubah
                    catat_aktivitas('ubah', 'users', $userId, 'Mengganti password');
                    flash_set('sukses', 'Password berhasil diganti.');
                    header('Location: profil.php');
                    exit;
                }
            } catch (PDOException $ex) {
                error_log('Ganti password error: ' . $ex->getMessage());
                $umum = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
            }
        }
    }
}

/** Satu kolom password dengan tombol tampil/sembunyikan. */
function cetak_kolom_password(string $nama, string $label, array $galat, string $autocomplete, string $bantuan = ''): void
{
    $salah = isset($galat[$nama]);
    ?>
                    <div class="medan<?= $salah ? ' salah' : '' ?>">
                        <label for="<?= e($nama) ?>"><?= e($label) ?></label>
                        <div class="kolom berikon berpassword">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input type="password" id="<?= e($nama) ?>" name="<?= e($nama) ?>" autocomplete="<?= e($autocomplete) ?>"
                                   maxlength="72" required<?= $salah ? ' aria-invalid="true" aria-describedby="g_' . e($nama) . '"' : '' ?>>
                            <button type="button" class="tampil-password" data-tampil-password aria-pressed="false" aria-label="Tampilkan password">
                                <svg class="ik mata-buka" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="ik mata-tutup" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><path d="M3 3l18 18M10.600 6.100A10 10 0 0 1 12 5c6.400 0 10 7 10 7a17 17 0 0 1-3.200 4M6.600 6.600A17 17 0 0 0 2 12s3.600 7 10 7c1.700 0 3.200-.4 4.500-1M9.900 9.900a3 3 0 0 0 4.200 4.200"/></svg>
                            </button>
                        </div>
                        <?php if ($bantuan !== '' && !$salah): ?><p class="bantuan"><?= e($bantuan) ?></p><?php endif; ?>
                        <?php if ($salah): ?><p class="pesan-salah" id="g_<?= e($nama) ?>"><?= e($galat[$nama]) ?></p><?php endif; ?>
                    </div>
<?php
}

halaman_awal('Ganti Password');
?>
            <div class="judul-halaman">
                <span class="ikon-judul"><svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="m10.800 12.200 9.200-9.200M17 6l3 3M14 9l2 2"/></svg></span>
                <div>
                    <h1>Ganti Password</h1>
                    <p>Gunakan password yang kuat dan jangan dibagikan kepada siapa pun.</p>
                </div>
            </div>

            <div class="password-kisi">
                <section class="kartu" aria-labelledby="judulPw">
                    <div class="kepala-kartu">
                        <h2 id="judulPw">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            Perbarui password
                        </h2>
                        <p>Setelah diganti, Anda tetap masuk di perangkat ini.</p>
                    </div>

                    <?php if ($umum !== ''): ?>
                        <p class="alert-form" role="alert">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.500v.01"/></svg>
                            <?= e($umum) ?>
                        </p>
                    <?php endif; ?>

                    <form method="post" action="ganti_password.php" data-form-password novalidate>
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                        <?php cetak_kolom_password('password_lama', 'Password saat ini', $galat, 'current-password'); ?>

                        <div class="pemisah-bagian">Password baru</div>

                        <?php cetak_kolom_password('password_baru', 'Password baru', $galat, 'new-password'); ?>
                        <div class="medan" style="margin-top:-6px">
                            <div class="meter" id="meterPassword" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                            <p class="label-meter" id="labelMeter" aria-live="polite">Kekuatan password: Belum diisi</p>
                        </div>
                        <?php cetak_kolom_password('password_ulang', 'Ulangi password baru', $galat, 'new-password'); ?>

                        <div class="bar-simpan">
                            <a class="tombol" href="profil.php">Batal</a>
                            <div class="aksi-kanan">
                                <button type="submit" class="tombol utama" data-kirim-password disabled>
                                    <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>
                                    Simpan password
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="kartu kartu-tips" aria-labelledby="judulSyarat">
                    <div class="kepala-kartu">
                        <h2 id="judulSyarat">
                            <svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.500 8 8 9 4.500-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                            Syarat password
                        </h2>
                    </div>
                    <ul class="syarat">
                        <li data-syarat="panjang">Minimal <?= PW_MIN ?> karakter</li>
                        <li data-syarat="huruf">Mengandung huruf</li>
                        <li data-syarat="angka">Mengandung angka</li>
                        <li data-syarat="beda">Berbeda dari password saat ini</li>
                        <li data-syarat="cocok">Konfirmasi password cocok</li>
                    </ul>
                    <ul class="tips-daftar">
                        <li>Panjang 12 karakter atau lebih lebih sulit ditebak.</li>
                        <li>Campurkan huruf besar, huruf kecil, angka, dan simbol.</li>
                        <li>Jangan memakai nama, tanggal lahir, atau nomor punggung.</li>
                        <li>Jangan gunakan password yang sama dengan akun lain.</li>
                    </ul>
                </aside>
            </div>
<?php halaman_akhir(); ?>
