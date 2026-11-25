<?php
declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require __DIR__ . '/auth.php';

start_secure_session();

// Sudah login? Langsung ke dashboard.
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// ===== Tampilan: ganti di sini =====
const NAMA_KLUB      = 'POHON BERINGIN FC';
const GAMBAR_LOGO    = 'erasebg-transformed.png';
const GAMBAR_STADION = 'med_width_gp10508-1.jpg';
// ===================================

const MAX_ATTEMPTS = 5;     // percobaan gagal sebelum dikunci
const LOCK_SECONDS = 300;   // lama penguncian (5 menit)

// Hash bcrypt palsu: dipakai agar waktu respons tetap mirip
// walau username tidak ditemukan (mencegah enumerasi username).
const DUMMY_HASH = '$2y$10$raSWMHruwWADM/Vm.WYAGunYxO5r9/3MR4HZq3dhwXFzMLjxVTCyC';

$error    = '';
$info     = '';
$username = '';

if (isset($_GET['expired'])) {
    $info = 'Sesi Anda telah berakhir. Silakan masuk kembali.';
} elseif (isset($_GET['logout'])) {
    $info = 'Anda telah keluar.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $lockedUntil = (int) ($_SESSION['locked_until'] ?? 0);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($lockedUntil > time()) {
        $menit = (int) ceil(($lockedUntil - time()) / 60);
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam {$menit} menit.";
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = db()->prepare(
                'SELECT id, username, password_hash, nama_lengkap, role, is_active
                   FROM users
                  WHERE username = :username
                  LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            $hash = $user['password_hash'] ?? DUMMY_HASH;
            $passwordOk = password_verify($password, $hash);

            if ($user && $passwordOk && (int) $user['is_active'] === 1) {
                // Login berhasil.
                session_regenerate_id(true); // cegah session fixation

                $_SESSION = [
                    'user_id'       => (int) $user['id'],
                    'username'      => $user['username'],
                    'nama_lengkap'  => $user['nama_lengkap'],
                    'role'          => $user['role'],
                    'last_activity' => time(),
                ];

                // Perbarui hash bila algoritma/cost default PHP berubah.
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $upd = db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                    $upd->execute([
                        ':h'  => password_hash($password, PASSWORD_DEFAULT),
                        ':id' => $user['id'],
                    ]);
                }

                $upd = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                $upd->execute([':id' => $user['id']]);

                header('Location: dashboard.php');
                exit;
            }

            // Login gagal: pesan sengaja dibuat umum.
            $_SESSION['attempts'] = (int) ($_SESSION['attempts'] ?? 0) + 1;
            if ($_SESSION['attempts'] >= MAX_ATTEMPTS) {
                $_SESSION['locked_until'] = time() + LOCK_SECONDS;
                $_SESSION['attempts'] = 0;
                $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 5 menit.';
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $ex) {
            error_log('Login error: ' . $ex->getMessage());
            $error = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | <?= e(NAMA_KLUB) ?></title>

    <!-- Pasang tema sebelum halaman tampil agar tidak berkedip -->
    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('tema'); } catch (e) {}
            // Nilai tersimpan: 'gelap' / 'terang' (sama dengan dashboard)
            if (tema !== 'gelap' && tema !== 'terang') {
                tema = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'gelap' : 'terang';
            }
            document.documentElement.setAttribute('data-bs-theme', tema === 'gelap' ? 'dark' : 'light');
        })();
    </script>

    <!-- Bootstrap 5.3 + Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        /* Warna hijau klub di atas variabel Bootstrap */
        :root {
            --klub-hijau: #0a8a1f;
            --klub-hijau-tua: #06561a;
        }
        [data-bs-theme="dark"] { --klub-hijau: #1aa535; }

        .btn-klub {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--klub-hijau);
            --bs-btn-border-color: var(--klub-hijau);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #087218;
            --bs-btn-hover-border-color: #087218;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--klub-hijau-tua);
            --bs-btn-active-border-color: var(--klub-hijau-tua);
            --bs-btn-disabled-bg: var(--klub-hijau);
            --bs-btn-disabled-border-color: var(--klub-hijau);
            --bs-btn-focus-shadow-rgb: 10, 138, 31;
        }
        .form-control:focus {
            border-color: var(--klub-hijau);
            box-shadow: 0 0 0 .25rem rgba(10, 138, 31, .25);
        }
        .link-klub { color: var(--klub-hijau); }

        /* Panel merek (kiri) */
        .sisi-merek {
            position: relative;
            overflow: hidden;
            color: #fff;
            background-color: #06561a;
            background-image:
                linear-gradient(160deg, rgba(6, 86, 26, .90) 0%, rgba(6, 60, 20, .78) 45%, rgba(4, 30, 12, .94) 100%),
                var(--gambar-stadion);
            background-size: cover;
            background-position: center;
        }
        [data-bs-theme="dark"] .sisi-merek {
            background-image:
                linear-gradient(160deg, rgba(8, 44, 17, .92) 0%, rgba(5, 28, 11, .86) 45%, rgba(2, 12, 5, .96) 100%),
                var(--gambar-stadion);
        }
        .sisi-merek::before {
            content: "";
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(90deg, rgba(255, 255, 255, .035) 0 64px, transparent 64px 128px);
            pointer-events: none;
        }
        .sisi-merek > * { position: relative; }

        .logo {
            height: 110px;
            width: auto;
            max-width: 190px;
            object-fit: contain;
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, .35));
        }
        .fitur .lingkar {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .14);
            color: #ffd54a;
            font-size: 1.2rem;
        }

        /* Sisi formulir (kanan) */
        .tombol-tema { position: absolute; top: 1rem; right: 1rem; }
        .kartu-masuk { max-width: 450px; }

        @media (max-width: 991.98px) {
            .logo { height: 72px; }
        }
    </style>
</head>
<body style="--gambar-stadion: url('<?= e(GAMBAR_STADION) ?>')">

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        <!-- ===== Sisi merek ===== -->
        <aside class="col-lg-6 sisi-merek d-flex flex-column justify-content-between gap-4 p-4 p-lg-5">
            <img class="logo" src="<?= e(GAMBAR_LOGO) ?>" alt="Logo <?= e(NAMA_KLUB) ?>">

            <div>
                <h1 class="display-5 fw-bold"><?= e(NAMA_KLUB) ?></h1>
                <h1 class="fw-bold mb-3" style="font-size: larger;">SIKLUB
                    <span style="letter-spacing: -3px;">---------------------------------------------------------------------------------------</span>
                </h1>
                <ul class="fitur list-unstyled d-none d-lg-grid gap-3 mb-0">
                    <li class="d-flex align-items-center gap-3 fw-semibold">
                        <span class="lingkar"><i class="bi bi-file-earmark-text"></i></span>
                        Kontrak dan penggajian
                    </li>
                    <li class="d-flex align-items-center gap-3 fw-semibold">
                        <span class="lingkar"><i class="bi bi-bar-chart-line"></i></span>
                        Statistik dan performa pemain
                    </li>
                    <li class="d-flex align-items-center gap-3 fw-semibold">
                        <span class="lingkar"><i class="bi bi-calendar-event"></i></span>
                        Jadwal latihan dan pertandingan
                    </li>
                </ul>
            </div>

            <p class="small text-white-50 mb-0 d-none d-lg-block">
                &copy; <?= date('Y') ?> <?= e(NAMA_KLUB) ?>. Hanya untuk pengguna terdaftar.
            </p>
        </aside>

        <!-- ===== Sisi formulir ===== -->
        <main class="col-lg-6 position-relative d-flex align-items-start align-items-lg-center justify-content-center px-3 px-lg-5 py-4 pt-lg-5">
            <button type="button" class="btn btn-body-secondary btn-light border tombol-tema" id="tombolTema">
                <i class="bi bi-sun-fill text-warning d-none" id="ikonMatahari"></i>
                <i class="bi bi-moon-stars-fill d-none" id="ikonBulan"></i>
            </button>

            <section class="card shadow-lg border-0 rounded-4 w-100 kartu-masuk mt-5 mt-lg-0" aria-labelledby="judulMasuk">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h3 fw-bold mb-1" id="judulMasuk">Selamat datang</h2>
                    <p class="text-body-secondary mb-4">Masuk dengan akun Anda untuk melanjutkan.</p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php elseif ($info !== ''): ?>
                        <div class="alert alert-info d-flex align-items-start gap-2" role="status">
                            <i class="bi bi-info-circle-fill"></i>
                            <div><?= e($info) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="login.php" autocomplete="on" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?= e($username) ?>" placeholder="Masukkan username"
                                       autocomplete="username" autocapitalize="none" spellcheck="false"
                                       maxlength="50" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Masukkan password" autocomplete="current-password" required>
                                <button type="button" class="btn btn-outline-secondary" id="tampil"
                                        aria-controls="password" aria-pressed="false" aria-label="Tampilkan password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-klub btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2" id="tombolMasuk">
                            <span>Masuk</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <p class="text-center text-body-secondary small mt-4 mb-0">
                        <a href="lupa_password.php">Lupa password?</a>
                    </p>
                </div>
            </section>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        // Tampilkan / sembunyikan password
        var tombol = document.getElementById('tampil');
        var kolom = document.getElementById('password');
        tombol.addEventListener('click', function () {
            var terlihat = kolom.type === 'text';
            kolom.type = terlihat ? 'password' : 'text';
            tombol.firstElementChild.className = terlihat ? 'bi bi-eye' : 'bi bi-eye-slash';
            tombol.setAttribute('aria-pressed', terlihat ? 'false' : 'true');
            tombol.setAttribute('aria-label', terlihat ? 'Tampilkan password' : 'Sembunyikan password');
        });

        // Ganti tema gelap / terang (disimpan di browser)
        var root = document.documentElement;
        var tombolTema = document.getElementById('tombolTema');
        var matahari = document.getElementById('ikonMatahari');
        var bulan = document.getElementById('ikonBulan');

        function perbarui() {
            var gelap = root.getAttribute('data-bs-theme') === 'dark';
            matahari.classList.toggle('d-none', gelap);
            bulan.classList.toggle('d-none', !gelap);
            tombolTema.setAttribute('aria-label', gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap');
            tombolTema.title = gelap ? 'Tema terang' : 'Tema gelap';
        }

        tombolTema.addEventListener('click', function () {
            var baru = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', baru);
            try { localStorage.setItem('tema', baru === 'dark' ? 'gelap' : 'terang'); } catch (e) {}
            perbarui();
        });
        perbarui();

        // Cegah kirim ganda
        var form = document.querySelector('form');
        var tombolMasuk = document.getElementById('tombolMasuk');
        form.addEventListener('submit', function () {
            tombolMasuk.disabled = true;
            tombolMasuk.firstElementChild.textContent = 'Memproses...';
        });
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) {
                tombolMasuk.disabled = false;
                tombolMasuk.firstElementChild.textContent = 'Masuk';
            }
        });
    })();
</script>
</body>
</html>
