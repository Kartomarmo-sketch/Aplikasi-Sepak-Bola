<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

const NAMA_KLUB      = 'POHON BERINGIN FC';
const GAMBAR_LOGO    = 'erasebg-transformed.png';
const GAMBAR_STADION = 'med_width_gp10508-1.jpg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password | <?= e(NAMA_KLUB) ?></title>

    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('tema'); } catch (e) {}
            if (tema !== 'gelap' && tema !== 'terang') {
                tema = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'gelap' : 'terang';
            }
            document.documentElement.setAttribute('data-bs-theme', tema === 'gelap' ? 'dark' : 'light');
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
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
            --bs-btn-focus-shadow-rgb: 10, 138, 31;
        }
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
        .tombol-tema { position: absolute; top: 1rem; right: 1rem; }
        .kartu-bantuan { max-width: 450px; }
        .ikon-bantuan {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: rgba(10, 138, 31, .12);
            color: var(--klub-hijau);
            font-size: 1.6rem;
        }
        @media (max-width: 991.98px) {
            .logo { height: 72px; }
        }
    </style>
</head>
<body style="--gambar-stadion: url('<?= e(GAMBAR_STADION) ?>')">

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">
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

        <main class="col-lg-6 position-relative d-flex align-items-start align-items-lg-center justify-content-center px-3 px-lg-5 py-4 pt-lg-5">
            <button type="button" class="btn btn-light border tombol-tema" id="tombolTema">
                <i class="bi bi-sun-fill text-warning d-none" id="ikonMatahari"></i>
                <i class="bi bi-moon-stars-fill d-none" id="ikonBulan"></i>
            </button>

            <section class="card shadow-lg border-0 rounded-4 w-100 kartu-bantuan mt-5 mt-lg-0" aria-labelledby="judulLupa">
                <div class="card-body p-4 p-md-5">
                    <div class="ikon-bantuan mb-3" aria-hidden="true">
                        <i class="bi bi-key"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-3" id="judulLupa">Lupa password?</h2>
                    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            Hubungi administrator klub untuk meminta password diatur ulang. Setelah berhasil masuk,
                            segera buat password baru melalui menu <strong>Ganti Password</strong>.
                        </div>
                    </div>

                    <a href="https://wa.me/62XXXXXXXXXXXX" class="btn btn-success btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 mt-3">
                        <i class="bi bi-whatsapp"></i>
                        <span>Hubungi via WhatsApp</span>
                    </a>

                    <a href="mailto:adminpohonberinginfc@gmail.com" class="btn btn-primary btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 mt-2">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Hubungi via Email</span>
                    </a>
                    
                    <a href="login.php" class="btn btn-klub btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 mt-3">
                        <i class="bi bi-arrow-left"></i>
                        <span>Kembali ke halaman masuk</span>
                    </a>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    (function () {
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
    })();
</script>
</body>
</html>