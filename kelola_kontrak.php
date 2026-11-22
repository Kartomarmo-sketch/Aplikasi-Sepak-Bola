<?php
declare(strict_types=1);

// Kelola kontrak (admin/HR).
//   kelola_kontrak.php             -> Daftar Kontrak (yang berlaku)
//   kelola_kontrak.php?hal=riwayat -> Riwayat semua versi
//   kelola_kontrak.php?hal=hampir  -> Kontrak hampir habis (<= 90 hari / kedaluwarsa) + pengingat
// POST: kontrak baru / perpanjangan, akhiri kontrak.

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';

start_secure_session();
wajib_admin();

$hal = (string) ($_GET['hal'] ?? 'daftar');
if (!in_array($hal, ['daftar', 'riwayat', 'hampir'], true)) {
    $hal = 'daftar';
}
$alamat = 'kelola_kontrak.php' . ($hal !== 'daftar' ? '?hal=' . $hal : '');

function tgl_valid(string $t): bool
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
        if ($aksi === 'simpan') {
            $v = [];
            foreach (['user_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'gaji_pokok', 'tunjangan_bulanan', 'bonus_tanda_tangan',
                      'bonus_per_goal', 'bonus_per_main', 'klausul_rilis', 'hak_cuti_tahunan', 'catatan'] as $k) {
                $v[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            $uang = static fn(string $s) => $s === '' ? 0.0 : (float) str_replace(['.', ' '], '', $s);
            $g = [];
            $st = db()->prepare('SELECT id, role, nama_lengkap FROM users WHERE id = :id AND is_active = 1');
            $st->execute([':id' => (int) $v['user_id']]);
            $orang = $st->fetch();
            if (!$orang) {
                $g['user_id'] = 'Pilih karyawan.';
            } elseif ($orang['role'] === 'pemain' ? $v['jenis'] === 'staf' || !isset(LABEL_JENIS_KONTRAK_ADMIN[$v['jenis']]) : $v['jenis'] !== 'staf') {
                $g['jenis'] = $orang['role'] === 'pemain' ? 'Pemain memakai kontrak profesional/amatir/pinjaman/percobaan.' : 'Staf memakai jenis Karyawan.';
            }
            if (!tgl_valid($v['tanggal_mulai'])) {
                $g['tanggal_mulai'] = 'Tanggal mulai tidak valid.';
            }
            if (!tgl_valid($v['tanggal_selesai'])) {
                $g['tanggal_selesai'] = 'Tanggal selesai tidak valid.';
            } elseif (!isset($g['tanggal_mulai'])) {
                $hari = selisih_hari($v['tanggal_mulai'], $v['tanggal_selesai']);
                if ($hari < 28) {
                    $g['tanggal_selesai'] = 'Masa kontrak minimal 1 bulan.';
                } elseif ($hari > 5 * 366) {
                    $g['tanggal_selesai'] = 'Masa kontrak maksimal 5 tahun.';
                }
            }
            $gaji = $uang($v['gaji_pokok']);
            if ($gaji < 1000000 || $gaji > 5000000000) {
                $g['gaji_pokok'] = 'Gaji pokok Rp 1 jt – Rp 5 M per bulan.';
            }
            foreach (['tunjangan_bulanan', 'bonus_tanda_tangan', 'bonus_per_goal', 'bonus_per_main', 'klausul_rilis'] as $k) {
                if ($uang($v[$k]) < 0 || $uang($v[$k]) > 1e12) {
                    $g[$k] = 'Nilai tidak valid.';
                }
            }
            $cuti = filter_var($v['hak_cuti_tahunan'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 30]]);
            if ($cuti === false) {
                $g['hak_cuti_tahunan'] = 'Hak cuti 0–30 hari.';
            }
            if (mb_strlen($v['catatan']) > 500) {
                $g['catatan'] = 'Catatan maksimal 500 karakter.';
            }
            // Kontrak aktif sebelumnya -> versi baru menggantikannya.
            $lama = null;
            if ($orang) {
                $st = db()->prepare("SELECT * FROM kontrak WHERE user_id = :u AND status = 'aktif' ORDER BY tanggal_mulai DESC LIMIT 1");
                $st->execute([':u' => $orang['id']]);
                $lama = $st->fetch() ?: null;
                if ($lama && !isset($g['tanggal_mulai']) && $v['tanggal_mulai'] <= $lama['tanggal_mulai']) {
                    $g['tanggal_mulai'] = 'Harus setelah mulai kontrak aktif ' . $lama['nomor_kontrak'] . ' (' . tanggal_id((string) $lama['tanggal_mulai']) . ').';
                }
            }
            if ($g) {
                $_SESSION['form_kontrak'] = ['galat' => $g, 'nilai' => $v];
                flash_set('galat', 'Periksa kembali isian kontrak.');
                kembali_ke($alamat);
            }

            $pdo = db();
            $pdo->beginTransaction();
            $nomor = nomor_kontrak_baru();
            $pdo->prepare('INSERT INTO kontrak (user_id, nomor_kontrak, jenis, versi, kontrak_sebelumnya_id, tanggal_mulai, tanggal_selesai, gaji_pokok,
                                  tunjangan_bulanan, bonus_tanda_tangan, bonus_per_goal, bonus_per_main, klausul_rilis, hak_cuti_tahunan,
                                  status, catatan, dibuat_oleh)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'aktif\', ?, ?)')
                ->execute([$orang['id'], $nomor, $v['jenis'], $lama ? (int) $lama['versi'] + 1 : 1, $lama['id'] ?? null,
                    $v['tanggal_mulai'], $v['tanggal_selesai'], $gaji, $uang($v['tunjangan_bulanan']), $uang($v['bonus_tanda_tangan']),
                    $uang($v['bonus_per_goal']), $uang($v['bonus_per_main']), $v['klausul_rilis'] === '' ? null : $uang($v['klausul_rilis']),
                    $cuti, $v['catatan'] === '' ? null : $v['catatan'], $_SESSION['user_id']]);
            $idBaru = (int) $pdo->lastInsertId();
            if ($lama) {
                $pdo->prepare("UPDATE kontrak SET status = 'diperbarui', catatan = CONCAT(COALESCE(catatan, ''), IF(catatan IS NULL, '', ' '), ?) WHERE id = ?")
                    ->execute(['Diperbarui oleh ' . $nomor . '.', $lama['id']]);
            }
            kirim_notifikasi((int) $orang['id'], 'kontrak', $lama ? 'Kontrak Anda diperpanjang' : 'Kontrak baru diterbitkan',
                $nomor . ' berlaku ' . tanggal_id($v['tanggal_mulai']) . ' – ' . tanggal_id($v['tanggal_selesai']) . '. Periksa ketentuannya di menu Kontrak Saya.', 'kontrak.php', true);
            $pdo->commit();
            catat_aktivitas($lama ? 'perpanjang' : 'tambah', 'kontrak', $idBaru, $nomor . ' untuk ' . $orang['nama_lengkap']);
            flash_set('sukses', $nomor . ' untuk ' . $orang['nama_lengkap'] . ' disimpan' . ($lama ? '; ' . $lama['nomor_kontrak'] . ' ditandai diperbarui.' : '.'));
            kembali_ke($alamat);
        }

        if ($aksi === 'akhiri') {
            $id = (int) ($_POST['id'] ?? 0);
            $alasan = trim((string) ($_POST['alasan'] ?? ''));
            if (mb_strlen($alasan) < 5) {
                flash_set('galat', 'Tuliskan alasan pengakhiran (minimal 5 karakter).');
                kembali_ke($alamat);
            }
            $st = db()->prepare("UPDATE kontrak SET status = 'berakhir', catatan = CONCAT(COALESCE(catatan, ''), IF(catatan IS NULL, '', ' '), ?)
                                  WHERE id = ? AND status = 'aktif'");
            $st->execute(['Diakhiri ' . tanggal_id(hari_ini()->format('Y-m-d')) . ': ' . mb_substr($alasan, 0, 300), $id]);
            if ($st->rowCount() === 1) {
                catat_aktivitas('akhiri', 'kontrak', $id, $alasan);
                flash_set('sukses', 'Kontrak diakhiri.');
            } else {
                flash_set('galat', 'Kontrak tidak ditemukan atau sudah tidak aktif.');
            }
            kembali_ke($alamat);
        }
    } catch (PDOException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        error_log('Kontrak gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Kontrak belum dapat disimpan. Coba lagi nanti.');
        kembali_ke($alamat);
    }
    kembali_ke($alamat);
}

// =====================================================================
// Data tampilan
// =====================================================================
kirim_pengingat_kontrak();
$form = $_SESSION['form_kontrak'] ?? null;
unset($_SESSION['form_kontrak']);
$dbGagal = false;
$kontrak = [];
$karyawan = [];
$pengingat = [];
try {
    $kontrak = db()->query(
        "SELECT k.*, u.nama_lengkap, u.role, pr.posisi_kode, s.jabatan_detail, s.jabatan, p.nama_lengkap AS pembuat,
                DATEDIFF(k.tanggal_selesai, CURDATE()) AS sisa
           FROM kontrak k
           JOIN users u ON u.id = k.user_id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id
           LEFT JOIN staf s ON s.user_id = u.id
           LEFT JOIN users p ON p.id = k.dibuat_oleh
          ORDER BY k.tanggal_selesai, k.id"
    )->fetchAll();
    $karyawan = db()->query(
        "SELECT u.id, u.nama_lengkap, u.role, pr.posisi_kode, s.jabatan_detail FROM users u
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id LEFT JOIN staf s ON s.user_id = u.id
          WHERE u.is_active = 1 ORDER BY u.role = 'pemain' DESC, u.nama_lengkap"
    )->fetchAll();
    $pengingat = db()->query(
        "SELECT pk.*, k.nomor_kontrak, u.nama_lengkap FROM pengingat_kontrak pk
           JOIN kontrak k ON k.id = pk.kontrak_id JOIN users u ON u.id = k.user_id
          ORDER BY pk.dikirim_pada DESC LIMIT 8"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Kontrak gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

foreach ($kontrak as &$k) {
    $k['efektif'] = status_kontrak($k);
    $k['nilai'] = nilai_kontrak_admin($k);
    $k['peran_teks'] = $k['role'] === 'pemain'
        ? ($k['posisi_kode'] ? KODE_POSISI[$k['posisi_kode']][0] ?? $k['posisi_kode'] : 'Pemain')
        : ($k['jabatan_detail'] ?: (LABEL_JABATAN_ENUM[$k['jabatan']] ?? 'Staf'));
}
unset($k);

$tampil = match ($hal) {
    'daftar'  => array_values(array_filter($kontrak, static fn($k) => in_array($k['status'], ['aktif', 'draft'], true))),
    'hampir'  => array_values(array_filter($kontrak, static fn($k) => in_array($k['efektif'], ['hampir', 'kedaluwarsa'], true))),
    default   => array_reverse($kontrak),
};
if ($hal === 'riwayat') {
    usort($tampil, static fn($a, $b) => [$a['user_id'], $a['tanggal_mulai']] <=> [$b['user_id'], $b['tanggal_mulai']]);
}

// Prefill: perpanjangan dari kontrak tertentu, atau nilai formulir yang gagal divalidasi.
$nilai = $form['nilai'] ?? [];
$galat = $form['galat'] ?? [];
$perpanjang = filter_input(INPUT_GET, 'perpanjang', FILTER_VALIDATE_INT);
$bukaForm = (bool) $form;
if (!$form && $perpanjang) {
    foreach ($kontrak as $k) {
        if ((int) $k['id'] === $perpanjang && $k['status'] === 'aktif') {
            $mulai = (new DateTimeImmutable((string) $k['tanggal_selesai']))->modify('+1 day');
            $nilai = [
                'user_id' => $k['user_id'], 'jenis' => $k['jenis'], 'tanggal_mulai' => $mulai->format('Y-m-d'),
                'tanggal_selesai' => $mulai->modify('+2 years -1 day')->format('Y-m-d'),
                'gaji_pokok' => (int) $k['gaji_pokok'], 'tunjangan_bulanan' => (int) $k['tunjangan_bulanan'],
                'bonus_tanda_tangan' => 0, 'bonus_per_goal' => (int) $k['bonus_per_goal'], 'bonus_per_main' => (int) $k['bonus_per_main'],
                'klausul_rilis' => $k['klausul_rilis'] !== null ? (int) $k['klausul_rilis'] : '', 'hak_cuti_tahunan' => (int) $k['hak_cuti_tahunan'],
                'catatan' => 'Perpanjangan dari ' . $k['nomor_kontrak'] . '.',
            ];
            $bukaForm = true;
        }
    }
}

// ?baru=USER_ID (dari halaman detail karyawan): kontrak pertama untuk orang itu.
$baruUntuk = filter_input(INPUT_GET, 'baru', FILTER_VALIDATE_INT);
if (!$form && !$bukaForm && $baruUntuk) {
    foreach ($karyawan as $o) {
        if ((int) $o['id'] === $baruUntuk) {
            $staf = $o['role'] !== 'pemain';
            $nilai = [
                'user_id' => $baruUntuk, 'jenis' => $staf ? 'staf' : 'profesional',
                'tanggal_mulai' => hari_ini()->format('Y-m-d'), 'tanggal_selesai' => hari_ini()->modify('+2 years -1 day')->format('Y-m-d'),
                'hak_cuti_tahunan' => $staf ? 14 : 12,
            ];
            $bukaForm = true;
        }
    }
}

$judulHal = ['daftar' => 'Daftar Kontrak', 'riwayat' => 'Riwayat Kontrak', 'hampir' => 'Kontrak Hampir Habis'][$hal];
$sub = [
    'daftar' => 'Kontrak yang berlaku saat ini untuk seluruh pemain dan staf.',
    'riwayat' => 'Semua versi kontrak, termasuk yang sudah diperbarui atau berakhir.',
    'hampir' => 'Kontrak yang berakhir ≤ 90 hari atau sudah kedaluwarsa. Pengingat otomatis dikirim pada H-90, H-60, H-30.',
][$hal];
$tombol = '<a class="tombol" href="ekspor.php?jenis=kontrak&amp;hal=' . e($hal) . '">' . ikon('unduh') . 'Ekspor</a>'
        . '<button type="button" class="tombol utama" ' . isi_dialog('dialogKontrak', ['user_id' => '', 'hak_cuti_tahunan' => 12], 'Kontrak baru') . '>' . ikon('tambah') . 'Kontrak baru</button>';
halaman_awal($judulHal, $hal === 'riwayat' ? 'Riwayat Semua Kontrak' : $judulHal);
kepala_halaman(['daftar' => 'pena', 'riwayat' => 'riwayat', 'hampir' => 'jam'][$hal], $judulHal, $sub, '<div class="aksi-kanan">' . $tombol . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data kontrak belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$aktif = array_filter($kontrak, static fn($k) => $k['status'] === 'aktif');

// ---------- Ringkasan per halaman ----------
if ($hal === 'daftar'):
    $komitmen = array_sum(array_map(static fn($k) => (float) $k['gaji_pokok'] + (float) $k['tunjangan_bulanan'], $aktif));
    $nilaiTotal = array_sum(array_column($aktif, 'nilai'));
    $hampir = count(array_filter($aktif, static fn($k) => in_array($k['efektif'], ['hampir', 'kedaluwarsa'], true)));
    $pemainAktif = count(array_filter($aktif, static fn($k) => $k['role'] === 'pemain'));
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan kontrak">
                <?= kartu_kpi('Kontrak berlaku', angka(count($aktif)), 'pena', 'hijau', e($pemainAktif . ' pemain · ' . (count($aktif) - $pemainAktif) . ' staf')) ?>
                <?= kartu_kpi('Komitmen per bulan', rupiah_ringkas($komitmen), 'uang', 'biru', 'Gaji pokok + tunjangan') ?>
                <?= kartu_kpi('Total nilai kontrak', rupiah_ringkas($nilaiTotal), 'naik', 'ungu', 'Seluruh masa kontrak berlaku') ?>
                <?= kartu_kpi('Perlu tindakan', angka($hampir), 'peringatan', $hampir ? 'kuning' : 'hijau', '<a href="kelola_kontrak.php?hal=hampir">≤ 90 hari / kedaluwarsa</a>') ?>
            </section>
<?php elseif ($hal === 'riwayat'):
    // Rata-rata kenaikan gaji saat perpanjangan.
    $byId = array_column($kontrak, null, 'id');
    $naik = [];
    foreach ($kontrak as $k) {
        if ($k['kontrak_sebelumnya_id'] && isset($byId[$k['kontrak_sebelumnya_id']]) && (float) $byId[$k['kontrak_sebelumnya_id']]['gaji_pokok'] > 0) {
            $naik[] = ((float) $k['gaji_pokok'] / (float) $byId[$k['kontrak_sebelumnya_id']]['gaji_pokok'] - 1) * 100;
        }
    }
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan riwayat">
                <?= kartu_kpi('Total versi kontrak', angka(count($kontrak)), 'dokumen', 'hijau', e(count(array_unique(array_column($kontrak, 'user_id'))) . ' karyawan')) ?>
                <?= kartu_kpi('Diperpanjang', angka(count($naik)), 'riwayat', 'biru', 'Versi yang menggantikan kontrak lama') ?>
                <?= kartu_kpi('Rata-rata kenaikan gaji', $naik ? '+' . desimal(array_sum($naik) / count($naik), 0) . '%' : '-', 'naik', 'ungu', 'Saat perpanjangan kontrak') ?>
                <?= kartu_kpi('Berakhir / batal', angka(count(array_filter($kontrak, static fn($k) => in_array($k['status'], ['berakhir', 'dibatalkan'], true)))), 'silang', 'kuning', 'Tidak diperpanjang') ?>
            </section>
<?php else:
    $lewat = count(array_filter($tampil, static fn($k) => $k['efektif'] === 'kedaluwarsa'));
    $d30 = count(array_filter($tampil, static fn($k) => $k['efektif'] === 'hampir' && (int) $k['sisa'] <= 30));
    $d90 = count($tampil) - $lewat - $d30;
    // Peta 12 bulan ke depan: jumlah kontrak aktif yang berakhir per bulan.
    $bulanan = [];
    for ($i = 0; $i < 12; $i++) {
        $bulanan[hari_ini()->modify('first day of this month')->modify("+$i month")->format('Y-m')] = [];
    }
    foreach ($aktif as $k) {
        $b = substr((string) $k['tanggal_selesai'], 0, 7);
        if (isset($bulanan[$b])) {
            $bulanan[$b][] = $k['nama_lengkap'];
        }
    }
    $maks = max(1, max(array_map('count', $bulanan)));
    ?>
            <div class="ringkas-chip">
                <span class="chip merah bertitik"><?= $lewat ?> kedaluwarsa</span>
                <span class="chip kuning bertitik"><?= $d30 ?> ≤ 30 hari</span>
                <span class="chip biru bertitik"><?= $d90 ?> 31–90 hari</span>
            </div>
            <div class="kisi-2-1">
                <section class="kartu" aria-labelledby="judulPeta">
                    <div class="kepala-kartu"><h2 id="judulPeta">Peta kedaluwarsa 12 bulan</h2><p>Jumlah kontrak berlaku yang berakhir per bulan — rencanakan negosiasi lebih awal</p></div>
                    <ol class="peta-bulan">
                        <?php foreach ($bulanan as $b => $nama): ?>
                            <?php $n = count($nama); ?>
                            <li class="tingkat-<?= $n === 0 ? 0 : (int) ceil(4 * $n / $maks) ?>" title="<?= e(bulan_tahun($b . '-01') . ': ' . ($n ? implode(', ', $nama) : 'tidak ada')) ?>">
                                <b><?= $n ?></b><small><?= e(mb_substr(NAMA_BULAN[(int) substr($b, 5, 2)], 0, 3)) ?> <?= e(substr($b, 2, 2)) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
                <section class="kartu" aria-labelledby="judulPengingat">
                    <div class="kepala-kartu"><h2 id="judulPengingat">Pengingat terkirim</h2><p>Notifikasi otomatis ke HR & karyawan</p></div>
                    <?php if (!$pengingat): ?>
                        <div class="kosong kecil">Belum ada pengingat.</div>
                    <?php else: ?>
                        <ul class="rincian-uang">
                            <?php foreach ($pengingat as $p): ?>
                                <li><span><?= e((string) $p['nama_lengkap']) ?> <small class="redup">· <?= e((string) $p['nomor_kontrak']) ?></small></span>
                                    <b><span class="badge <?= $p['tahap'] === 'lewat' ? 'merah' : ($p['tahap'] === 'H30' ? 'kuning' : 'biru') ?>"><?= $p['tahap'] === 'lewat' ? 'Lewat' : e(str_replace('H', 'H-', (string) $p['tahap'])) ?></span></b></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
<?php endif; ?>

            <section class="kartu tabel-kartu" data-tabel aria-label="<?= e($judulHal) ?>">
                <div class="kepala-tabel">
                    <?= alat_tabel('Cari kode atau nama...', [
                        'status'   => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], array_diff_key(STATUS_KONTRAK_EFEKTIF, ['tidak_ada' => 1]))],
                        'jenis'    => ['label' => 'Jenis', 'opsi' => LABEL_JENIS_KONTRAK_ADMIN],
                        'kategori' => ['label' => 'Kategori', 'opsi' => ['pemain' => 'Pemain', 'staf' => 'Staf']],
                    ], (string) ($_GET['cari'] ?? '')) ?>
                </div>
                <?php if (!$tampil): ?>
                    <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('tanda') ?></span><b>Tidak ada kontrak di sini</b><span>Semua kontrak masih panjang masa berlakunya.</span></div>
                <?php else: ?>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar">
                        <thead>
                            <tr>
                                <?= th_urut('Kode') ?><?= th_urut('Nama') ?><?= th_urut('Mulai', 'angka') ?><?= th_urut('Berakhir', 'angka', $hal === 'riwayat' ? '' : 'asc') ?>
                                <?= th_urut('Nilai kontrak', 'angka') ?><?= th_urut('Gaji / bln', 'angka') ?><?= th_urut('Bonus', 'angka') ?>
                                <?= th_urut('Release clause', 'angka') ?><?= th_urut('Status') ?><th scope="col"><span class="sr">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tampil as $k): ?>
                                <?php
                                $sisa = (int) $k['sisa'];
                                $detail = [
                                    'nomor_kontrak' => $k['nomor_kontrak'], 'nama_lengkap' => $k['nama_lengkap'], 'peran_teks' => $k['peran_teks'],
                                    'versi_teks' => 'v' . $k['versi'] . ' · ' . (LABEL_JENIS_KONTRAK_ADMIN[$k['jenis']] ?? $k['jenis']),
                                    'masa' => tanggal_id((string) $k['tanggal_mulai'], true) . ' – ' . tanggal_id((string) $k['tanggal_selesai'], true)
                                        . ' (' . bulan_kontrak((string) $k['tanggal_mulai'], (string) $k['tanggal_selesai']) . ' bulan)',
                                    'gaji' => rupiah($k['gaji_pokok']) . ' + tunjangan ' . rupiah($k['tunjangan_bulanan']),
                                    'bonus' => 'TTD ' . rupiah($k['bonus_tanda_tangan']) . ' · per gol ' . rupiah($k['bonus_per_goal']) . ' · per laga ' . rupiah($k['bonus_per_main']),
                                    'nilai' => rupiah($k['nilai']), 'rilis' => $k['klausul_rilis'] !== null ? rupiah($k['klausul_rilis']) : 'Tidak ada',
                                    'cuti' => $k['hak_cuti_tahunan'] . ' hari / tahun', 'pembuat' => $k['pembuat'] ?? '-',
                                    'catatan' => $k['catatan'] ?: '-', 'status_teks' => STATUS_KONTRAK_EFEKTIF[$k['efektif']][0] ?? $k['efektif'],
                                ];
                                $item = [[ikon('mata') . 'Lihat detail', null, isi_dialog('dialogDetailKontrak', $detail)]];
                                if ($k['status'] === 'aktif') {
                                    $item[] = [ikon('riwayat') . 'Perpanjang', 'kelola_kontrak.php?' . ($hal !== 'daftar' ? 'hal=' . $hal . '&' : '') . 'perpanjang=' . (int) $k['id']];
                                    $item[] = '-';
                                    $item[] = [ikon('silang') . 'Akhiri kontrak', null, 'class="bahaya" ' . isi_dialog('dialogAkhiri', ['id' => (int) $k['id'], 'nama' => $k['nama_lengkap'] . ' (' . $k['nomor_kontrak'] . ')'])];
                                }
                                ?>
                                <tr data-status="<?= e($k['efektif']) ?>" data-jenis="<?= e((string) $k['jenis']) ?>" data-kategori="<?= $k['role'] === 'pemain' ? 'pemain' : 'staf' ?>">
                                    <td><b><?= e((string) $k['nomor_kontrak']) ?></b><small class="sub-sel">v<?= (int) $k['versi'] ?> · <?= e(LABEL_JENIS_KONTRAK_ADMIN[$k['jenis']] ?? '') ?></small></td>
                                    <td><b><?= e((string) $k['nama_lengkap']) ?></b><small class="sub-sel"><?= e($k['peran_teks']) ?></small></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $k['tanggal_mulai']) ?>"><?= e(tanggal_id((string) $k['tanggal_mulai'])) ?></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $k['tanggal_selesai']) ?>">
                                        <?= e(tanggal_id((string) $k['tanggal_selesai'])) ?>
                                        <?php if ($k['status'] === 'aktif'): ?>
                                            <small class="sub-sel <?= $sisa < 0 ? 'teks-merah' : ($sisa <= 30 ? 'teks-kuning' : '') ?>"><?= $sisa < 0 ? 'lewat ' . angka(-$sisa) . ' hari' : angka($sisa) . ' hari lagi' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="uang" data-nilai="<?= $k['nilai'] ?>" title="<?= e(rupiah($k['nilai'])) ?>"><b><?= e(rupiah_ringkas($k['nilai'])) ?></b></td>
                                    <td class="uang" data-nilai="<?= (float) $k['gaji_pokok'] ?>"><?= e(rupiah_ringkas($k['gaji_pokok'])) ?></td>
                                    <td class="uang" data-nilai="<?= (float) $k['bonus_tanda_tangan'] ?>"><?= (float) $k['bonus_tanda_tangan'] > 0 ? e(rupiah_ringkas($k['bonus_tanda_tangan'])) : '<span class="redup">-</span>' ?></td>
                                    <td class="uang" data-nilai="<?= (float) $k['klausul_rilis'] ?>"><?= $k['klausul_rilis'] !== null ? e(rupiah_ringkas($k['klausul_rilis'])) : '<span class="redup">-</span>' ?></td>
                                    <td data-nilai="<?= e($k['efektif']) ?>"><?= chip_peta(STATUS_KONTRAK_EFEKTIF, $k['efektif']) ?></td>
                                    <td><?= menu_baris($item, 'Aksi untuk ' . $k['nomor_kontrak']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <?php endif; ?>
            </section>

            <?php
            $medan = static function (string $nama, string $label, string $input, bool $wajib = false, string $bantu = '') use ($galat): string {
                return '<div class="medan' . (isset($galat[$nama]) ? ' salah' : '') . '"><label for="k_' . $nama . '">' . e($label)
                    . ($wajib ? '' : ' <small>(opsional)</small>') . '</label><div class="kolom">' . $input . '</div>'
                    . (isset($galat[$nama]) ? '<p class="pesan-salah">' . e($galat[$nama]) . '</p>' : ($bantu !== '' ? '<p class="bantuan">' . e($bantu) . '</p>' : '')) . '</div>';
            };
            $isian = static fn(string $nama, string $tipe = 'text', string $ekstra = '') =>
                '<input type="' . $tipe . '" id="k_' . $nama . '" name="' . $nama . '" value="' . e((string) ($nilai[$nama] ?? '')) . '" ' . $ekstra . '>';
            ?>
            <dialog class="modal lebar" id="dialogKontrak" aria-labelledby="judulDialogKontrak"<?= $bukaForm ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($alamat) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('pena') ?></span>
                        <div><h2 id="judulDialogKontrak" data-judul-dialog><?= $perpanjang && $bukaForm ? 'Perpanjang kontrak' : 'Kontrak baru' ?></h2>
                            <p>Bila karyawan masih punya kontrak aktif, kontrak itu otomatis ditandai <b>diperbarui</b> dan versi naik.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <?php
                            $opsi = '<select id="k_user_id" name="user_id"><option value="">Pilih karyawan...</option>';
                            foreach (['pemain' => 'Pemain', 'staf' => 'Staf'] as $grup => $lbl) {
                                $opsi .= '<optgroup label="' . $lbl . '">';
                                foreach ($karyawan as $o) {
                                    if (($o['role'] === 'pemain') !== ($grup === 'pemain')) {
                                        continue;
                                    }
                                    $opsi .= '<option value="' . (int) $o['id'] . '" data-peran="' . ($o['role'] === 'pemain' ? 'pemain' : 'staf') . '"'
                                        . ((string) ($nilai['user_id'] ?? '') === (string) $o['id'] ? ' selected' : '') . '>' . e((string) $o['nama_lengkap'])
                                        . ' — ' . e($o['role'] === 'pemain' ? (string) ($o['posisi_kode'] ?? 'Pemain') : (string) ($o['jabatan_detail'] ?? 'Staf')) . '</option>';
                                }
                                $opsi .= '</optgroup>';
                            }
                            echo $medan('user_id', 'Karyawan', $opsi . '</select>', true);
                            $jenis = '<select id="k_jenis" name="jenis">';
                            foreach (LABEL_JENIS_KONTRAK_ADMIN as $kj => $lj) {
                                $jenis .= '<option value="' . $kj . '"' . (($nilai['jenis'] ?? 'profesional') === $kj ? ' selected' : '') . '>' . e($lj) . '</option>';
                            }
                            echo $medan('jenis', 'Jenis kontrak', $jenis . '</select>', true, 'Staf memakai jenis Karyawan.');
                            ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('tanggal_mulai', 'Mulai', $isian('tanggal_mulai', 'date'), true) ?>
                            <?= $medan('tanggal_selesai', 'Berakhir', $isian('tanggal_selesai', 'date'), true) ?>
                            <?= $medan('hak_cuti_tahunan', 'Hak cuti / tahun', $isian('hak_cuti_tahunan', 'number', 'min="0" max="30"'), true) ?>
                        </div>
                        <h3 class="pemisah-bagian">Nilai (rupiah)</h3>
                        <div class="baris-3">
                            <?= $medan('gaji_pokok', 'Gaji pokok / bulan', $isian('gaji_pokok', 'number', 'min="0" step="100000"'), true) ?>
                            <?= $medan('tunjangan_bulanan', 'Tunjangan / bulan', $isian('tunjangan_bulanan', 'number', 'min="0" step="50000"')) ?>
                            <?= $medan('bonus_tanda_tangan', 'Bonus tanda tangan', $isian('bonus_tanda_tangan', 'number', 'min="0" step="100000"')) ?>
                        </div>
                        <div class="baris-3">
                            <?= $medan('bonus_per_goal', 'Bonus per gol', $isian('bonus_per_goal', 'number', 'min="0" step="50000"')) ?>
                            <?= $medan('bonus_per_main', 'Bonus per penampilan', $isian('bonus_per_main', 'number', 'min="0" step="50000"')) ?>
                            <?= $medan('klausul_rilis', 'Release clause', $isian('klausul_rilis', 'number', 'min="0" step="1000000"')) ?>
                        </div>
                        <p class="info-hari" data-ringkas-kontrak aria-live="polite"></p>
                        <?= $medan('catatan', 'Catatan', '<textarea id="k_catatan" name="catatan" maxlength="500">' . e((string) ($nilai['catatan'] ?? '')) . '</textarea>') ?>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan kontrak</button>
                    </div>
                </form>
            </dialog>

            <dialog class="modal lebar" id="dialogDetailKontrak" aria-labelledby="judulDetailKontrak">
                <div class="modal-kepala">
                    <span class="ikon-judul"><?= ikon('pena') ?></span>
                    <div><h2 id="judulDetailKontrak" data-isi="nomor_kontrak">Kontrak</h2><p><span data-isi="nama_lengkap"></span> · <span data-isi="peran_teks"></span></p></div>
                    <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-isi">
                    <dl class="daftar-ketentuan">
                        <div><dt>Versi & jenis</dt><dd data-isi="versi_teks"></dd></div>
                        <div><dt>Status</dt><dd data-isi="status_teks"></dd></div>
                        <div><dt>Masa berlaku</dt><dd data-isi="masa"></dd></div>
                        <div><dt>Gaji</dt><dd data-isi="gaji"></dd></div>
                        <div><dt>Bonus</dt><dd data-isi="bonus"></dd></div>
                        <div><dt>Nilai kontrak</dt><dd data-isi="nilai"></dd></div>
                        <div><dt>Release clause</dt><dd data-isi="rilis"></dd></div>
                        <div><dt>Hak cuti</dt><dd data-isi="cuti"></dd></div>
                        <div><dt>Disusun oleh</dt><dd data-isi="pembuat"></dd></div>
                        <div><dt>Catatan</dt><dd data-isi="catatan"></dd></div>
                    </dl>
                </div>
                <div class="modal-kaki"><button type="button" class="tombol" data-tutup-dialog>Tutup</button></div>
            </dialog>

            <dialog class="modal" id="dialogAkhiri" aria-labelledby="judulAkhiri">
                <form method="post" action="<?= e($alamat) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="akhiri">
                    <input type="hidden" name="id" value="">
                    <div class="modal-kepala">
                        <span class="ikon-judul warna-netral"><?= ikon('peringatan') ?></span>
                        <div><h2 id="judulAkhiri">Akhiri kontrak <span data-isi="nama"></span>?</h2><p>Status berubah menjadi Berakhir. Tindakan ini tercatat di log audit.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="medan"><label for="alasanAkhiri">Alasan</label><div class="kolom"><textarea id="alasanAkhiri" name="alasan" maxlength="300" required placeholder="mis. Kesepakatan bersama / transfer ke klub lain"></textarea></div></div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol bahaya"><?= ikon('silang') ?>Akhiri kontrak</button>
                    </div>
                </form>
            </dialog>
<?php
halaman_akhir();
