<?php
declare(strict_types=1);

// Hasil tes fisik & tingkat kebugaran.
// Pemain: hasilnya sendiri. Admin & pelatih/medis: seluruh skuad atau satu pemain (?pemain=ID).

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const LABEL_STATUS_FIT = ['fit' => ['Fit', 'hijau'], 'terbatas' => ['Terbatas', 'kuning'], 'tidak_fit' => ['Tidak fit', 'merah']];
const LEVEL_KEBUGARAN = ['sangat_baik' => 'Sangat baik', 'baik' => 'Baik', 'cukup' => 'Cukup', 'kurang' => 'Kurang'];

$staf    = adalah_staf();
$dbGagal = false;

// Staf (pelatih/medis & admin) dapat menginput, mengubah, dan menghapus hasil tes.
$kembaliTes = 'kebugaran.php' . (isset($_GET['pemain']) && is_string($_GET['pemain']) && ctype_digit($_GET['pemain']) ? '?pemain=' . $_GET['pemain'] : '');
if ($staf) {
    require __DIR__ . '/inc_kelola_kebugaran.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        proses_kelola_kebugaran($kembaliTes);
    }
}
$pemain  = null;
$daftar  = [];
$tes     = [];

try {
    $pemain = pemain_target();
    $daftar = $staf ? daftar_pemain() : [];

    // Tes terakhir tiap pemain + skor tes sebelumnya (untuk rata-rata tim & tabel skuad).
    $terakhir = db()->query(
        "SELECT k.*, u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.foto,
                (SELECT k2.skor_kebugaran FROM pemeriksaan_kebugaran k2
                  WHERE k2.user_id = k.user_id AND k2.tanggal < k.tanggal
                  ORDER BY k2.tanggal DESC LIMIT 1) AS skor_lalu
           FROM pemeriksaan_kebugaran k
           JOIN (SELECT user_id, MAX(tanggal) AS tgl FROM pemeriksaan_kebugaran GROUP BY user_id) m
             ON m.user_id = k.user_id AND m.tgl = k.tanggal
           JOIN users u               ON u.id = k.user_id AND u.role = 'pemain' AND u.is_active = 1
           LEFT JOIN pemain pm        ON pm.user_id = u.id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id
          ORDER BY k.skor_kebugaran DESC"
    )->fetchAll();

    if ($pemain !== null) {
        $stmt = db()->prepare(
            "SELECT k.*, u.nama_lengkap AS pemeriksa
               FROM pemeriksaan_kebugaran k
               LEFT JOIN users u ON u.id = k.pemeriksa_id
              WHERE k.user_id = :u
              ORDER BY k.tanggal"
        );
        $stmt->execute([':u' => (int) $pemain['id']]);
        $tes = $stmt->fetchAll();
    } else {
        $trenTim = db()->query(
            "SELECT k.tanggal, AVG(k.skor_kebugaran) AS skor, COUNT(*) AS jumlah
               FROM pemeriksaan_kebugaran k
               JOIN users u ON u.id = k.user_id AND u.role = 'pemain'
              WHERE k.skor_kebugaran IS NOT NULL
              GROUP BY k.tanggal
              ORDER BY k.tanggal"
        )->fetchAll();
    }
} catch (PDOException $ex) {
    error_log('Kebugaran gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

/** Level dari skor 0-100: [kunci, label, kelas chip]. */
function level_kebugaran(mixed $skor): array
{
    if ($skor === null) {
        return ['', '-', 'netral'];
    }
    $s = (int) $skor;
    return match (true) {
        $s >= 85 => ['sangat_baik', 'Sangat baik', 'hijau'],
        $s >= 70 => ['baik', 'Baik', 'biru'],
        $s >= 55 => ['cukup', 'Cukup', 'kuning'],
        default  => ['kurang', 'Kurang', 'merah'],
    };
}

function chip_level(mixed $skor): string
{
    [, $teks, $kelas] = level_kebugaran($skor);
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

function chip_fit(string $status): string
{
    [$teks, $kelas] = LABEL_STATUS_FIT[$status] ?? [$status, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

function sel_skor(mixed $skor): string
{
    if ($skor === null) {
        return '<span class="redup">-</span>';
    }
    $s = max(0, min(100, (int) $skor));
    return '<span class="skor-batang"><b>' . $s . '</b><span class="batang" aria-hidden="true"><span style="width:' . $s . '%"></span></span></span>';
}

function nilai_opsional(mixed $v, int $digit, string $satuan = ''): string
{
    return $v === null ? '<span class="redup">-</span>' : e(desimal($v, $digit) . $satuan);
}

/** Rata-rata satu kolom dari tes terakhir seluruh skuad. */
function rata_tim(array $baris, string $kolom): ?float
{
    $nilai = array_filter(array_column($baris, $kolom), static fn($v) => $v !== null);
    return $nilai ? array_sum(array_map('floatval', $nilai)) / count($nilai) : null;
}

/** Batas bawah sumbu grafik skor agar garis tidak menempel di dasar. */
function sumbu_skor(array $skor): array
{
    $min = $skor ? (int) max(0, floor((min($skor) - 10) / 10) * 10) : 0;
    $langkah = (100 - $min) > 40 ? 20 : 10;
    $bantu = [];
    for ($v = $min; $v <= 100; $v += $langkah) {
        $bantu[] = $v;
    }
    if (end($bantu) !== 100) {
        $bantu[] = 100;
    }
    return [$min, $bantu];
}

$filter = $staf ? ['pemain' => ['label' => 'Pemain', 'opsi' => opsi_pemain($daftar), 'nilai' => $pemain['id'] ?? '']] : [];
$judul  = $staf ? 'Kebugaran Fisik' : 'Kebugaran Saya';
halaman_awal($judul, 'Kebugaran Fisik');
kepala_halaman('aktivitas', $judul, 'Hasil tes fisik dan tingkat kebugaran pemain.',
    '<div class="aksi-kanan">' . ($filter ? filter_get($filter) : '') . ($staf ? tombol_input_tes($pemain !== null ? (int) $pemain['id'] : null) : '') . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data kebugaran belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$opsiLevel = ['level' => ['label' => 'Level', 'opsi' => LEVEL_KEBUGARAN]];

// =====================================================================
// A. Satu pemain
// =====================================================================
if ($pemain !== null):
    if ($staf) {
        echo strip_pemain($pemain);
    }
    $kini = $tes ? $tes[array_key_last($tes)] : null;
    $lalu = count($tes) > 1 ? $tes[count($tes) - 2] : null;
    $f = static fn(?array $b, string $k): ?float => ($b !== null && $b[$k] !== null) ? (float) $b[$k] : null;

    $skorSemua = array_values(array_filter(array_map(static fn($t) => $t['skor_kebugaran'], $tes), static fn($v) => $v !== null));
    [$minSumbu, $bantu] = sumbu_skor(array_map('intval', $skorSemua));
    $titik = [];
    foreach ($tes as $t) {
        if ($t['skor_kebugaran'] === null) {
            continue;
        }
        [, $lvl] = level_kebugaran($t['skor_kebugaran']);
        $titik[] = [
            'x' => tanggal_id((string) $t['tanggal']),
            'y' => (int) $t['skor_kebugaran'],
            't' => 'Skor ' . $t['skor_kebugaran'] . ' · ' . $lvl . ($t['vo2max'] !== null ? ' · VO₂max ' . desimal($t['vo2max']) : ''),
        ];
    }
    ?>
            <section class="kpi-kisi" aria-label="Hasil tes terakhir">
                <?= kartu_kpi('Skor kebugaran', $kini ? (string) $kini['skor_kebugaran'] : '-', 'aktivitas', 'hijau',
                    selisih($f($kini, 'skor_kebugaran'), $f($lalu, 'skor_kebugaran'), 0)) ?>
                <?= kartu_kpi('VO₂max', $kini && $kini['vo2max'] !== null ? desimal($kini['vo2max']) : '-', 'angin', 'biru',
                    selisih($f($kini, 'vo2max'), $f($lalu, 'vo2max'))) ?>
                <?= kartu_kpi('Sprint 30 m', $kini && $kini['sprint_30m_detik'] !== null ? desimal($kini['sprint_30m_detik'], 2) . ' s' : '-', 'kilat', 'ungu',
                    selisih($f($kini, 'sprint_30m_detik'), $f($lalu, 'sprint_30m_detik'), 2, ' s', true)) ?>
                <?= kartu_kpi('Yo-Yo IR1', $kini && $kini['yoyo_level'] !== null ? 'Level ' . desimal($kini['yoyo_level']) : '-', 'naik', 'kuning',
                    selisih($f($kini, 'yoyo_level'), $f($lalu, 'yoyo_level'))) ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu">
                    <div class="kepala-kartu baris-kepala">
                        <div>
                            <h2>Tren skor kebugaran</h2>
                            <p>Skor 0–100 per tes</p>
                        </div>
                        <?php if ($kini): ?>
                            <div class="angka-sorot"><small>Level saat ini</small><?= chip_level($kini['skor_kebugaran']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?= grafik_garis($titik, $minSumbu, 100, $bantu, 'Grafik skor kebugaran ' . $pemain['nama_lengkap'] . ' per tes') ?>
                </section>

                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Dibanding rata-rata tim</h2>
                        <p>Tes terakhir <?= $kini ? e(tanggal_id((string) $kini['tanggal'])) : '' ?> vs tes terakhir <?= count($terakhir) ?> pemain</p>
                    </div>
                    <?php if (!$kini): ?>
                        <div class="kosong kecil">Belum ada hasil tes.</div>
                    <?php else: ?>
                        <ul class="banding-daftar">
                            <?php foreach ([
                                ['Skor kebugaran', 'skor_kebugaran', 0, '', false],
                                ['VO₂max', 'vo2max', 1, '', false],
                                ['Sprint 30 m', 'sprint_30m_detik', 2, ' s', true],
                                ['Yo-Yo IR1', 'yoyo_level', 1, '', false],
                                ['Lemak tubuh', 'lemak_tubuh_persen', 1, '%', true],
                            ] as [$nm, $kol, $dg, $sat, $kecilBaik]): ?>
                                <?php
                                $milik = $f($kini, $kol);
                                $rata  = rata_tim($terakhir, $kol);
                                if ($milik === null || $rata === null) {
                                    continue;
                                }
                                $beda = round($milik - $rata, $dg);
                                $baik = $beda == 0 ? null : ($kecilBaik ? $beda < 0 : $beda > 0);
                                ?>
                                <li>
                                    <span class="banding-nama"><?= e($nm) ?></span>
                                    <span class="banding-nilai"><b><?= e(desimal($milik, $dg) . $sat) ?></b> <small>tim <?= e(desimal($rata, $dg) . $sat) ?></small></span>
                                    <span class="tren <?= $baik === null ? 'datar' : ($baik ? 'baik' : 'buruk') ?>">
                                        <?= $baik === null ? 'Setara' : ($baik ? 'Lebih baik' : 'Perlu ditingkatkan') ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <dl class="fakta-mini">
                            <div><dt>Berat</dt><dd><?= nilai_opsional($kini['berat_kg'], 1, ' kg') ?></dd></div>
                            <div><dt>Denyut istirahat</dt><dd><?= $kini['denyut_istirahat'] !== null ? (int) $kini['denyut_istirahat'] . ' bpm' : '-' ?></dd></div>
                            <div><dt>Tekanan darah</dt><dd><?= e((string) ($kini['tekanan_darah'] ?? '-')) ?></dd></div>
                        </dl>
                    <?php endif; ?>
                </section>
            </div>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulTes">
                <div class="kepala-tabel">
                    <h2 id="judulTes">Riwayat tes fisik</h2>
                    <?= alat_tabel('Cari...', $opsiLevel) ?>
                </div>
                <?php if (!$tes): ?>
                    <div class="kosong">Belum ada hasil tes kebugaran.</div>
                <?php else: ?>
                <div class="gulir-tabel">
                    <table class="tabel-data">
                        <thead>
                            <tr>
                                <?= th_urut('Tanggal', 'angka', 'desc') ?>
                                <?= th_urut('Skor', 'angka') ?>
                                <?= th_urut('VO₂max', 'angka') ?>
                                <?= th_urut('Sprint 30 m', 'angka') ?>
                                <?= th_urut('Yo-Yo', 'angka') ?>
                                <?= th_urut('Lemak', 'angka') ?>
                                <?= th_urut('Level', 'angka') ?>
                                <?= th_urut('Status') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tes as $t): ?>
                                <?php [$kunciLevel] = level_kebugaran($t['skor_kebugaran']); ?>
                                <tr data-level="<?= e($kunciLevel) ?>">
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $t['tanggal']) ?>">
                                        <b><?= e(tanggal_id((string) $t['tanggal'])) ?></b>
                                        <?php if (!empty($t['catatan'])): ?><small class="sub-sel"><?= e((string) $t['catatan']) ?></small><?php endif; ?>
                                    </td>
                                    <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>"><?= sel_skor($t['skor_kebugaran']) ?></td>
                                    <td data-nilai="<?= (float) $t['vo2max'] ?>"><?= nilai_opsional($t['vo2max'], 1) ?></td>
                                    <td data-nilai="<?= (float) $t['sprint_30m_detik'] ?>"><?= nilai_opsional($t['sprint_30m_detik'], 2, ' s') ?></td>
                                    <td data-nilai="<?= (float) $t['yoyo_level'] ?>"><?= nilai_opsional($t['yoyo_level'], 1) ?></td>
                                    <td data-nilai="<?= (float) $t['lemak_tubuh_persen'] ?>"><?= nilai_opsional($t['lemak_tubuh_persen'], 1, '%') ?></td>
                                    <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>"><?= chip_level($t['skor_kebugaran']) ?></td>
                                    <td><?= chip_fit((string) $t['status_kebugaran']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Level: Sangat baik ≥ 85 · Baik 70–84 · Cukup 55–69 · Kurang &lt; 55. Sprint dan lemak tubuh: makin kecil makin baik.</p>
                <?php endif; ?>
            </section>
<?php

// =====================================================================
// B. Seluruh skuad (staf)
// =====================================================================
else:
    $sudahTes = array_column($terakhir, null, 'user_id');
    $fit = count(array_filter($terakhir, static fn($t) => $t['status_kebugaran'] === 'fit'));
    $teratas = static function (array $baris, string $kolom, bool $kecil) {
        $hasil = null;
        foreach ($baris as $b) {
            if ($b[$kolom] === null) { continue; }
            if ($hasil === null || ($kecil ? (float) $b[$kolom] < (float) $hasil[$kolom] : (float) $b[$kolom] > (float) $hasil[$kolom])) {
                $hasil = $b;
            }
        }
        return $hasil;
    };
    $vo2Top = $teratas($terakhir, 'vo2max', false);
    $sprintTop = $teratas($terakhir, 'sprint_30m_detik', true);
    $rataSkor = rata_tim($terakhir, 'skor_kebugaran');

    [$minSumbu, $bantu] = sumbu_skor(array_map(static fn($r) => (int) round((float) $r['skor']), $trenTim));
    $titik = array_map(static fn($r) => [
        'x' => tanggal_id((string) $r['tanggal']),
        'y' => round((float) $r['skor'], 1),
        't' => 'Rata-rata ' . desimal($r['skor']) . ' dari ' . $r['jumlah'] . ' pemain',
    ], $trenTim);
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan kebugaran skuad">
                <?= kartu_kpi('Rata-rata skor tim', $rataSkor !== null ? desimal($rataSkor) : '-', 'aktivitas', 'hijau',
                    $rataSkor !== null ? 'Level ' . e(level_kebugaran($rataSkor)[1]) : 'Belum ada tes') ?>
                <?= kartu_kpi('Pemain fit', angka($fit) . ' / ' . angka(count($daftar)), 'perisai', 'biru',
                    e((count($daftar) - count($sudahTes)) . ' pemain belum dites')) ?>
                <?= kartu_kpi('VO₂max tertinggi', $vo2Top ? desimal($vo2Top['vo2max']) : '-', 'angin', 'ungu',
                    $vo2Top ? e((string) $vo2Top['nama_lengkap']) : '') ?>
                <?= kartu_kpi('Sprint 30 m tercepat', $sprintTop ? desimal($sprintTop['sprint_30m_detik'], 2) . ' s' : '-', 'kilat', 'kuning',
                    $sprintTop ? e((string) $sprintTop['nama_lengkap']) : '') ?>
            </section>

            <?php panel_kebugaran_staf($terakhir, $trenTim, $daftar); ?>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulSkuad">
                <div class="kepala-tabel">
                    <h2 id="judulSkuad">Tes terakhir per pemain</h2>
                    <?= alat_tabel('Cari pemain...', $opsiLevel) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data aksi-tetap">
                        <thead>
                            <tr>
                                <?= th_urut('Pemain') ?>
                                <?= th_urut('Tes terakhir', 'angka') ?>
                                <?= th_urut('Skor', 'angka', 'desc') ?>
                                <?= th_urut('VO₂max', 'angka') ?>
                                <?= th_urut('Sprint 30 m', 'angka') ?>
                                <?= th_urut('Yo-Yo', 'angka') ?>
                                <?= th_urut('Lemak', 'angka') ?>
                                <?= th_urut('Level', 'angka') ?>
                                <?= th_urut('Status') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $p): ?>
                                <?php
                                $t = $sudahTes[$p['id']] ?? null;
                                [$kunciLevel] = level_kebugaran($t['skor_kebugaran'] ?? null);
                                ?>
                                <tr data-level="<?= e($kunciLevel) ?>">
                                    <td>
                                        <a class="sel-pemain" href="<?= e(url_dengan(['pemain' => $p['id']])) ?>">
                                            <?= avatar_kecil((string) $p['nama_lengkap'], $p['foto']) ?>
                                            <span><b><?= e((string) $p['nama_lengkap']) ?></b><small class="sub-sel"><?= e(keterangan_pemain($p + ['posisi_detail' => null])) ?></small></span>
                                        </a>
                                    </td>
                                    <?php if ($t === null): ?>
                                        <td data-nilai="0" colspan="1"><span class="redup">Belum dites</span></td>
                                        <td data-nilai="-1"><span class="redup">-</span></td>
                                        <td data-nilai="0"><span class="redup">-</span></td>
                                        <td data-nilai="0"><span class="redup">-</span></td>
                                        <td data-nilai="0"><span class="redup">-</span></td>
                                        <td data-nilai="0"><span class="redup">-</span></td>
                                        <td data-nilai="-1"><span class="redup">-</span></td>
                                        <td><span class="redup">-</span></td>
                                    <?php else: ?>
                                        <td class="sel-tgl" data-nilai="<?= strtotime((string) $t['tanggal']) ?>"><?= e(tanggal_id((string) $t['tanggal'])) ?></td>
                                        <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>">
                                            <?= sel_skor($t['skor_kebugaran']) ?>
                                            <?php if ($t['skor_lalu'] !== null && (int) $t['skor_lalu'] !== (int) $t['skor_kebugaran']): ?>
                                                <?php $d = (int) $t['skor_kebugaran'] - (int) $t['skor_lalu']; ?>
                                                <small class="tren <?= $d > 0 ? 'baik' : 'buruk' ?>"><?= $d > 0 ? '▲ +' . $d : '▼ ' . $d ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-nilai="<?= (float) $t['vo2max'] ?>"><?= nilai_opsional($t['vo2max'], 1) ?></td>
                                        <td data-nilai="<?= (float) $t['sprint_30m_detik'] ?>"><?= nilai_opsional($t['sprint_30m_detik'], 2, ' s') ?></td>
                                        <td data-nilai="<?= (float) $t['yoyo_level'] ?>"><?= nilai_opsional($t['yoyo_level'], 1) ?></td>
                                        <td data-nilai="<?= (float) $t['lemak_tubuh_persen'] ?>"><?= nilai_opsional($t['lemak_tubuh_persen'], 1, '%') ?></td>
                                        <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>"><?= chip_level($t['skor_kebugaran']) ?></td>
                                        <td><?= chip_fit((string) $t['status_kebugaran']) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Level: Sangat baik ≥ 85 · Baik 70–84 · Cukup 55–69 · Kurang &lt; 55. Klik nama pemain untuk melihat riwayat tesnya.</p>
            </section>
<?php
    tabel_semua_tes($kembaliTes, $opsiLevel);
endif;

if ($staf) {
    dialog_tes($kembaliTes, $pemain !== null ? (int) $pemain['id'] : null);
}
halaman_akhir();
