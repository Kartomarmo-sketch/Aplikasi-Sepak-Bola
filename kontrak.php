<?php
declare(strict_types=1);

// Kontrak kerja milik pengguna yang login (pemain maupun staf).
//   kontrak.php             -> Kontrak Saya (kontrak aktif + ketentuan)
//   kontrak.php?hal=riwayat -> Riwayat Kontrak (semua versi + perubahan antarversi)

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const LABEL_JENIS_KONTRAK = [
    'profesional' => 'Profesional', 'amatir' => 'Amatir', 'pinjaman' => 'Pinjaman',
    'percobaan' => 'Percobaan', 'staf' => 'Staf',
];
const LABEL_STATUS_KONTRAK = [
    'aktif' => ['Aktif', 'hijau'], 'diperbarui' => ['Diperbarui', 'netral'], 'berakhir' => ['Berakhir', 'netral'],
    'draft' => ['Draf', 'biru'], 'dibatalkan' => ['Dibatalkan', 'merah'],
];

$hal     = ($_GET['hal'] ?? '') === 'riwayat' ? 'riwayat' : 'saya';
$uid     = (int) $_SESSION['user_id'];
$adalahPemain = ($_SESSION['role'] ?? '') === 'pemain';
$dbGagal = false;
$semua   = [];
$aktif   = null;

try {
    $stmt = db()->prepare(
        "SELECT k.*, u.nama_lengkap AS pembuat
           FROM kontrak k
           LEFT JOIN users u ON u.id = k.dibuat_oleh
          WHERE k.user_id = :u AND k.status <> 'draft'
          ORDER BY k.tanggal_mulai, k.versi"
    );
    $stmt->execute([':u' => $uid]);
    $semua = $stmt->fetchAll();
    foreach ($semua as $k) {
        if ($k['status'] === 'aktif') {
            $aktif = $k;
        }
    }

    $cuti = saldo_cuti($uid);

    // Bonus performa musim berjalan (pemain): dari statistik sejak awal musim / awal kontrak.
    $bonusMusim = null;
    if ($aktif && $adalahPemain) {
        $sejak = max(awal_musim(), (string) $aktif['tanggal_mulai']);
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(s.goal), 0) AS gol, COALESCE(SUM(s.menit_bermain > 0), 0) AS laga,
                    COALESCE(SUM(s.kartu_kuning), 0) AS kuning, COALESCE(SUM(s.kartu_merah), 0) AS merah
               FROM statistik_pertandingan s JOIN jadwal j ON j.id = s.jadwal_id
              WHERE s.user_id = :u AND j.waktu_mulai >= :sejak"
        );
        $stmt->execute([':u' => $uid, ':sejak' => $sejak]);
        $bonusMusim = $stmt->fetch() + ['sejak' => $sejak];
    }
} catch (PDOException $ex) {
    error_log('Kontrak gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

/** Lama kontrak dalam bulan (dibulatkan). */
function lama_bulan(array $k): int
{
    $a = new DateTimeImmutable((string) $k['tanggal_mulai']);
    $b = (new DateTimeImmutable((string) $k['tanggal_selesai']))->modify('+1 day');
    $d = $a->diff($b);
    return $d->y * 12 + $d->m + ($d->d >= 15 ? 1 : 0);
}

function teks_durasi(int $bulan): string
{
    $t = intdiv($bulan, 12);
    $b = $bulan % 12;
    return trim(($t ? $t . ' tahun ' : '') . ($b ? $b . ' bulan' : ''));
}

/** Nilai kontrak = gaji pokok x lama (bulan) + bonus tanda tangan. */
function nilai_kontrak(array $k): float
{
    return (float) $k['gaji_pokok'] * lama_bulan($k) + (float) $k['bonus_tanda_tangan'];
}

function chip_status_kontrak(string $s): string
{
    [$teks, $kelas] = LABEL_STATUS_KONTRAK[$s] ?? [$s, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

/** Persentase perubahan dengan tanda, mis. "+29%". */
function persen_naik(float $baru, float $lama): string
{
    if ($lama <= 0) {
        return '-';
    }
    $p = ($baru - $lama) / $lama * 100;
    return ($p >= 0 ? '+' : '') . desimal($p, 0) . '%';
}

/** Tabel kontrak (dipakai di kedua halaman). */
function tabel_kontrak(array $daftar, string $judul): void
{
    ?>
            <section class="kartu tabel-kartu" data-tabel aria-label="<?= e($judul) ?>">
                <div class="kepala-tabel">
                    <h2><?= e($judul) ?></h2>
                    <?= alat_tabel('Cari kode kontrak...') ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar">
                        <thead>
                            <tr>
                                <?= th_urut('Kode') ?>
                                <?= th_urut('Mulai', 'angka') ?>
                                <?= th_urut('Berakhir', 'angka', 'desc') ?>
                                <?= th_urut('Nilai kontrak', 'angka') ?>
                                <?= th_urut('Gaji / bln', 'angka') ?>
                                <?= th_urut('Bonus', 'angka') ?>
                                <?= th_urut('Release clause', 'angka') ?>
                                <?= th_urut('Status') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $k): ?>
                                <?php $sisa = selisih_hari(hari_ini()->format('Y-m-d'), (string) $k['tanggal_selesai']); ?>
                                <tr>
                                    <td><b><?= e((string) $k['nomor_kontrak']) ?></b><small class="sub-sel">v<?= (int) $k['versi'] ?> · <?= e(LABEL_JENIS_KONTRAK[$k['jenis']] ?? $k['jenis']) ?></small></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $k['tanggal_mulai']) ?>"><?= e(tanggal_id((string) $k['tanggal_mulai'])) ?></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $k['tanggal_selesai']) ?>">
                                        <?= e(tanggal_id((string) $k['tanggal_selesai'])) ?>
                                        <?php if ($k['status'] === 'aktif'): ?>
                                            <small class="sub-sel <?= $sisa <= 90 ? 'teks-merah' : '' ?>"><?= $sisa >= 0 ? angka($sisa) . ' hari lagi' : 'Lewat ' . angka(-$sisa) . ' hari' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="uang" data-nilai="<?= nilai_kontrak($k) ?>" title="<?= e(rupiah(nilai_kontrak($k))) ?>"><b><?= e(rupiah_ringkas(nilai_kontrak($k))) ?></b></td>
                                    <td class="uang" data-nilai="<?= (float) $k['gaji_pokok'] ?>" title="<?= e(rupiah($k['gaji_pokok'])) ?>"><?= e(rupiah_ringkas($k['gaji_pokok'])) ?></td>
                                    <td class="uang" data-nilai="<?= (float) $k['bonus_tanda_tangan'] ?>" title="Bonus tanda tangan: <?= e(rupiah($k['bonus_tanda_tangan'])) ?>"><?= (float) $k['bonus_tanda_tangan'] > 0 ? e(rupiah_ringkas($k['bonus_tanda_tangan'])) : '<span class="redup">-</span>' ?></td>
                                    <td class="uang" data-nilai="<?= (float) $k['klausul_rilis'] ?>"><?= $k['klausul_rilis'] !== null ? e(rupiah_ringkas($k['klausul_rilis'])) : '<span class="redup">-</span>' ?></td>
                                    <td data-nilai="<?= e((string) $k['status']) ?>"><?= chip_status_kontrak((string) $k['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Nilai kontrak = gaji pokok × lama kontrak + bonus tanda tangan (belum termasuk bonus performa dan tunjangan). Arahkan kursor ke angka untuk melihat nilai lengkap.</p>
            </section>
    <?php
}

$judul = $hal === 'riwayat' ? 'Riwayat Kontrak Saya' : 'Kontrak Saya';
halaman_awal($judul, $hal === 'riwayat' ? 'Riwayat Kontrak' : 'Kontrak Saya');
kepala_halaman($hal === 'riwayat' ? 'riwayat' : 'pena', $judul,
    'Kontrak Anda bersama klub. Hubungi HR untuk pertanyaan seputar kontrak.');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data kontrak belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

if (!$semua): ?>
            <div class="kosong">Belum ada kontrak yang tercatat. Silakan hubungi HR.</div>
<?php
    halaman_akhir();
    return;
endif;

// =====================================================================
// A. Kontrak Saya
// =====================================================================
if ($hal === 'saya'):
    if (!$aktif): ?>
            <div class="kosong">Tidak ada kontrak aktif saat ini. Lihat <a href="kontrak.php?hal=riwayat">riwayat kontrak</a>.</div>
<?php
        halaman_akhir();
        return;
    endif;

    $total    = max(1, selisih_hari((string) $aktif['tanggal_mulai'], (string) $aktif['tanggal_selesai']));
    $berjalan = min($total, max(0, selisih_hari((string) $aktif['tanggal_mulai'])));
    $sisa     = selisih_hari(hari_ini()->format('Y-m-d'), (string) $aktif['tanggal_selesai']);
    $pct      = (int) round(100 * $berjalan / $total);
    $bulan    = lama_bulan($aktif);
    ?>
            <section class="kartu kontrak-hero" aria-label="Kontrak aktif">
                <div class="kontrak-identitas">
                    <span class="ikon-kontrak"><?= ikon('pena') ?></span>
                    <div>
                        <small>Kontrak aktif</small>
                        <h2><?= e((string) $aktif['nomor_kontrak']) ?></h2>
                        <p>Versi <?= (int) $aktif['versi'] ?> · <?= e(LABEL_JENIS_KONTRAK[$aktif['jenis']] ?? '') ?> · <?= e(teks_durasi($bulan)) ?></p>
                    </div>
                    <?= chip_status_kontrak('aktif') ?>
                </div>
                <div class="masa-kontrak">
                    <div class="baris">
                        <span>Masa berlaku</span>
                        <b><?= $sisa >= 0 ? angka($sisa) . ' hari lagi' : 'Sudah berakhir' ?></b>
                    </div>
                    <div class="progres <?= $sisa <= 180 ? 'kuning' : '' ?>" role="progressbar" aria-label="Masa kontrak berjalan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>">
                        <span style="width: <?= $pct ?>%"></span>
                    </div>
                    <div class="rtp-ujung">
                        <span><?= e(tanggal_id((string) $aktif['tanggal_mulai'], true)) ?></span>
                        <span><?= $pct ?>% berjalan</span>
                        <span><?= e(tanggal_id((string) $aktif['tanggal_selesai'], true)) ?></span>
                    </div>
                </div>
                <?php if ($sisa <= 180): ?>
                    <div class="peringatan-kontrak" role="note">
                        <?= ikon('peringatan') ?>
                        <span>Kontrak Anda berakhir dalam <b><?= angka(max(0, $sisa)) ?> hari</b>. Bicarakan perpanjangan dengan manajemen sebelum jendela transfer dibuka.</span>
                        <a class="tombol kecil" href="pengajuan.php?hal=izin&amp;baru=perpanjangan_kontrak"><?= ikon('kirim') ?>Ajukan perpanjangan</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="kpi-kisi" aria-label="Nilai kontrak">
                <?= kartu_kpi('Nilai kontrak', rupiah_ringkas(nilai_kontrak($aktif)), 'pena', 'hijau', e(teks_durasi($bulan)) . ' · di luar bonus performa') ?>
                <?= kartu_kpi('Gaji / bulan', rupiah_ringkas($aktif['gaji_pokok']), 'uang', 'biru', '+ tunjangan ' . e(rupiah_ringkas($aktif['tunjangan_bulanan']))) ?>
                <?= kartu_kpi('Bonus tanda tangan', (float) $aktif['bonus_tanda_tangan'] > 0 ? rupiah_ringkas($aktif['bonus_tanda_tangan']) : '-', 'bintang', 'ungu', 'Dibayar di bulan pertama kontrak') ?>
                <?= kartu_kpi('Release clause', $aktif['klausul_rilis'] !== null ? rupiah_ringkas($aktif['klausul_rilis']) : '-', 'perisai', 'kuning', $aktif['klausul_rilis'] !== null ? 'Nilai pelepasan minimum' : 'Tidak ada klausul') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Ketentuan kontrak</h2>
                        <p>Rincian hak dan bonus yang berlaku</p>
                    </div>
                    <dl class="daftar-ketentuan">
                        <div><dt>Jenis kontrak</dt><dd><?= e(LABEL_JENIS_KONTRAK[$aktif['jenis']] ?? '') ?></dd></div>
                        <div><dt>Gaji pokok</dt><dd><?= e(rupiah($aktif['gaji_pokok'])) ?> / bulan</dd></div>
                        <div><dt>Tunjangan transport & makan</dt><dd><?= e(rupiah($aktif['tunjangan_bulanan'])) ?> / bulan</dd></div>
                        <?php if ($aktif['jenis'] !== 'staf'): ?>
                            <div><dt>Bonus per gol</dt><dd><?= (float) $aktif['bonus_per_goal'] > 0 ? e(rupiah($aktif['bonus_per_goal'])) : '-' ?></dd></div>
                            <div><dt>Bonus per penampilan</dt><dd><?= (float) $aktif['bonus_per_main'] > 0 ? e(rupiah($aktif['bonus_per_main'])) : '-' ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Hak cuti tahunan</dt><dd><?= (int) $aktif['hak_cuti_tahunan'] ?> hari · <a href="pengajuan.php">sisa <?= (int) $cuti['sisa'] ?> hari di <?= (int) $cuti['tahun'] ?></a></dd></div>
                        <div><dt>Disusun oleh</dt><dd><?= e((string) ($aktif['pembuat'] ?? 'HR klub')) ?></dd></div>
                        <?php if (!empty($aktif['catatan'])): ?>
                            <div><dt>Catatan</dt><dd><?= e((string) $aktif['catatan']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </section>

                <section class="kartu">
                    <?php if ($bonusMusim !== null): ?>
                        <?php
                        $bGol  = (int) $bonusMusim['gol'] * (float) $aktif['bonus_per_goal'];
                        $bMain = (int) $bonusMusim['laga'] * (float) $aktif['bonus_per_main'];
                        $denda = (int) $bonusMusim['kuning'] * 250000 + (int) $bonusMusim['merah'] * 1000000;
                        ?>
                        <div class="kepala-kartu">
                            <h2>Bonus performa musim ini</h2>
                            <p>Sejak <?= e(tanggal_id((string) $bonusMusim['sejak'])) ?>, dari statistik pertandingan</p>
                        </div>
                        <ul class="rincian-uang">
                            <li><span><?= angka($bonusMusim['gol']) ?> gol × <?= e(rupiah_ringkas($aktif['bonus_per_goal'])) ?></span><b><?= e(rupiah($bGol)) ?></b></li>
                            <li><span><?= angka($bonusMusim['laga']) ?> penampilan × <?= e(rupiah_ringkas($aktif['bonus_per_main'])) ?></span><b><?= e(rupiah($bMain)) ?></b></li>
                            <li class="kurang"><span>Denda kartu (<?= (int) $bonusMusim['kuning'] ?> kuning, <?= (int) $bonusMusim['merah'] ?> merah)</span><b><?= e(rupiah(-$denda)) ?></b></li>
                            <li class="total"><span>Perkiraan bersih</span><b><?= e(rupiah($bGol + $bMain - $denda)) ?></b></li>
                        </ul>
                        <p class="bantuan">Dibayarkan bersama <a href="gaji.php">slip gaji</a> bulan berjalan. Denda kartu: kuning Rp 250 rb, merah Rp 1 jt.</p>
                    <?php else: ?>
                        <?php $tahunan = ((float) $aktif['gaji_pokok'] + (float) $aktif['tunjangan_bulanan']) * 12; ?>
                        <div class="kepala-kartu">
                            <h2>Perkiraan penghasilan tahunan</h2>
                            <p>Sebelum pajak dan potongan BPJS</p>
                        </div>
                        <ul class="rincian-uang">
                            <li><span>Gaji pokok × 12</span><b><?= e(rupiah((float) $aktif['gaji_pokok'] * 12)) ?></b></li>
                            <li><span>Tunjangan × 12</span><b><?= e(rupiah((float) $aktif['tunjangan_bulanan'] * 12)) ?></b></li>
                            <li class="total"><span>Total setahun</span><b><?= e(rupiah($tahunan)) ?></b></li>
                        </ul>
                        <p class="bantuan">Rincian per bulan tersedia di <a href="gaji.php">Slip Gaji</a>.</p>
                    <?php endif; ?>
                </section>
            </div>

            <?php tabel_kontrak([$aktif], 'Kontrak berjalan'); ?>
<?php

// =====================================================================
// B. Riwayat Kontrak
// =====================================================================
else:
    $pertama = $semua[0];
    $terbaru = $aktif ?? $semua[array_key_last($semua)];
    $sejak   = new DateTimeImmutable((string) $pertama['tanggal_mulai']);
    $lama    = $sejak->diff(hari_ini());
    $totalNilai = array_sum(array_map('nilai_kontrak', $semua));
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan riwayat kontrak">
                <?= kartu_kpi('Jumlah kontrak', angka(count($semua)), 'dokumen', 'hijau', 'Versi terbaru v' . (int) $terbaru['versi']) ?>
                <?= kartu_kpi('Bersama klub', ($lama->y ? $lama->y . ' thn ' : '') . $lama->m . ' bln', 'kalender', 'biru', 'Sejak ' . e(tanggal_id((string) $pertama['tanggal_mulai']))) ?>
                <?= kartu_kpi('Kenaikan gaji', persen_naik((float) $terbaru['gaji_pokok'], (float) $pertama['gaji_pokok']), 'naik', 'ungu',
                    e(rupiah_ringkas($pertama['gaji_pokok']) . ' → ' . rupiah_ringkas($terbaru['gaji_pokok']))) ?>
                <?= kartu_kpi('Total nilai kontrak', rupiah_ringkas($totalNilai), 'uang', 'kuning', 'Akumulasi semua versi') ?>
            </section>

            <section class="kartu" aria-labelledby="judulLini">
                <div class="kepala-kartu">
                    <h2 id="judulLini">Lini masa kontrak</h2>
                    <p>Versi terbaru di atas, lengkap dengan perubahan dari versi sebelumnya</p>
                </div>
                <ol class="garis-waktu lini-kontrak">
                    <?php $urut = array_reverse($semua); ?>
                    <?php foreach ($urut as $i => $k): ?>
                        <?php $sebelum = $urut[$i + 1] ?? null; ?>
                        <li<?= $k['status'] === 'aktif' ? ' class="sekarang"' : '' ?>>
                            <div class="kartu-versi">
                                <div class="versi-atas">
                                    <b><?= e((string) $k['nomor_kontrak']) ?></b>
                                    <span class="badge netral">v<?= (int) $k['versi'] ?></span>
                                    <?= chip_status_kontrak((string) $k['status']) ?>
                                </div>
                                <p class="redup"><?= e(tanggal_id((string) $k['tanggal_mulai'], true)) ?> – <?= e(tanggal_id((string) $k['tanggal_selesai'], true)) ?> · <?= e(teks_durasi(lama_bulan($k))) ?></p>
                                <?php if ($sebelum): ?>
                                    <ul class="perubahan">
                                        <li>Gaji <b><?= e(rupiah_ringkas($sebelum['gaji_pokok'])) ?> → <?= e(rupiah_ringkas($k['gaji_pokok'])) ?></b> <span class="tren baik"><?= e(persen_naik((float) $k['gaji_pokok'], (float) $sebelum['gaji_pokok'])) ?></span></li>
                                        <?php if ($k['klausul_rilis'] !== null && $sebelum['klausul_rilis'] !== null): ?>
                                            <li>Release clause <b><?= e(rupiah_ringkas($sebelum['klausul_rilis'])) ?> → <?= e(rupiah_ringkas($k['klausul_rilis'])) ?></b> <span class="tren baik"><?= e(persen_naik((float) $k['klausul_rilis'], (float) $sebelum['klausul_rilis'])) ?></span></li>
                                        <?php endif; ?>
                                        <?php if ((float) $k['bonus_per_goal'] !== (float) $sebelum['bonus_per_goal']): ?>
                                            <li>Bonus per gol <b><?= e(rupiah_ringkas($sebelum['bonus_per_goal'])) ?> → <?= e(rupiah_ringkas($k['bonus_per_goal'])) ?></b></li>
                                        <?php endif; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="redup kecil">Kontrak pertama bersama <?= e(NAMA_KLUB) ?>.</p>
                                <?php endif; ?>
                                <?php if (!empty($k['catatan'])): ?><p class="catatan-versi"><?= e((string) $k['catatan']) ?></p><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <?php tabel_kontrak($semua, 'Semua versi kontrak'); ?>
<?php
endif;

halaman_akhir();
