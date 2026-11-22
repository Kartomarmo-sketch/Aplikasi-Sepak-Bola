<?php
declare(strict_types=1);

// Statistik pemain per musim & kompetisi.
// Pemain: hanya statistiknya sendiri. Admin & pelatih/medis: seluruh skuad atau satu pemain (?pemain=ID).

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

$staf    = adalah_staf();
$dbGagal = false;

// Staf (pelatih/medis & admin) dapat menginput, mengubah, dan menghapus statistik per laga.
$kembaliStat = 'statistik.php' . (isset($_GET['musim']) && is_string($_GET['musim']) ? '?musim=' . rawurlencode($_GET['musim']) : '');
if ($staf) {
    require __DIR__ . '/inc_kelola_statistik.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        proses_kelola_statistik($kembaliStat);
    }
}

$daftarMusim = [];
$musim       = '';
$pemain      = null;
$daftar      = [];

try {
    $daftarMusim = db()->query(
        "SELECT DISTINCT musim FROM pertandingan WHERE musim IS NOT NULL ORDER BY musim DESC"
    )->fetchAll(PDO::FETCH_COLUMN);

    // Musim dari URL hanya dipakai bila memang ada di database.
    $musimUrl = (string) ($_GET['musim'] ?? '');
    $musim    = in_array($musimUrl, $daftarMusim, true) ? $musimUrl : (string) ($daftarMusim[0] ?? '');

    $pemain = pemain_target();
    $daftar = $staf ? daftar_pemain() : [];

    // Rekor tim pada musim terpilih.
    $stmt = db()->prepare(
        "SELECT COUNT(*) AS main,
                COALESCE(SUM(skor_klub > skor_lawan), 0) AS menang,
                COALESCE(SUM(skor_klub = skor_lawan), 0) AS seri,
                COALESCE(SUM(skor_klub < skor_lawan), 0) AS kalah,
                COALESCE(SUM(skor_klub), 0)  AS gol_masuk,
                COALESCE(SUM(skor_lawan), 0) AS gol_kemasukan
           FROM pertandingan
          WHERE musim = :m AND status = 'selesai'"
    );
    $stmt->execute([':m' => $musim]);
    $tim = $stmt->fetch();

    if ($pemain !== null) {
        $pid = (int) $pemain['id'];

        $stmt = db()->prepare(
            "SELECT COUNT(*) AS main, COALESCE(SUM(s.starter), 0) AS starter,
                    COALESCE(SUM(s.menit_bermain), 0) AS menit,
                    COALESCE(SUM(s.goal), 0) AS goal, COALESCE(SUM(s.assist), 0) AS assist,
                    COALESCE(SUM(s.tembakan), 0) AS tembakan, COALESCE(SUM(s.tembakan_tepat), 0) AS tepat,
                    COALESCE(SUM(s.umpan), 0) AS umpan, COALESCE(SUM(s.umpan_sukses), 0) AS umpan_sukses,
                    COALESCE(SUM(s.tekel), 0) AS tekel, COALESCE(SUM(s.penyelamatan), 0) AS penyelamatan,
                    COALESCE(SUM(s.kartu_kuning), 0) AS kuning, COALESCE(SUM(s.kartu_merah), 0) AS merah,
                    AVG(s.rating) AS rating
               FROM statistik_pertandingan s
               JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
              WHERE s.user_id = :u AND p.musim = :m"
        );
        $stmt->execute([':u' => $pid, ':m' => $musim]);
        $total = $stmt->fetch();

        $stmt = db()->prepare(
            "SELECT * FROM v_statistik_kompetisi WHERE user_id = :u AND musim = :m ORDER BY goal DESC, main DESC"
        );
        $stmt->execute([':u' => $pid, ':m' => $musim]);
        $perKompetisi = $stmt->fetchAll();

        $stmt = db()->prepare(
            "SELECT j.waktu_mulai, p.lawan, p.kandang, p.kompetisi, p.skor_klub, p.skor_lawan,
                    s.starter, s.menit_bermain, s.goal, s.assist, s.kartu_kuning, s.kartu_merah, s.rating
               FROM statistik_pertandingan s
               JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
               JOIN jadwal j       ON j.id = s.jadwal_id
              WHERE s.user_id = :u AND p.musim = :m
              ORDER BY j.waktu_mulai"
        );
        $stmt->execute([':u' => $pid, ':m' => $musim]);
        $laga = $stmt->fetchAll();
    } else {
        // Ringkasan seluruh skuad (khusus staf).
        $stmt = db()->prepare(
            "SELECT u.id, u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.foto, pr.status,
                    COUNT(s.id) AS main, COALESCE(SUM(s.menit_bermain), 0) AS menit,
                    COALESCE(SUM(s.goal), 0) AS goal, COALESCE(SUM(s.assist), 0) AS assist,
                    COALESCE(SUM(s.kartu_kuning), 0) AS kuning, COALESCE(SUM(s.kartu_merah), 0) AS merah,
                    AVG(s.rating) AS rating
               FROM users u
               LEFT JOIN pemain pm        ON pm.user_id = u.id
               LEFT JOIN profil_pemain pr ON pr.user_id = u.id
               LEFT JOIN (statistik_pertandingan s
                          JOIN pertandingan pt ON pt.jadwal_id = s.jadwal_id AND pt.musim = :m)
                      ON s.user_id = u.id
              WHERE u.role = 'pemain' AND u.is_active = 1
              GROUP BY u.id, u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.foto, pr.status
              ORDER BY goal DESC, assist DESC, u.nama_lengkap"
        );
        $stmt->execute([':m' => $musim]);
        $skuad = $stmt->fetchAll();

        $stmt = db()->prepare(
            "SELECT j.waktu_mulai, p.lawan, p.kandang, p.skor_klub, p.skor_lawan, p.kompetisi
               FROM pertandingan p JOIN jadwal j ON j.id = p.jadwal_id
              WHERE p.musim = :m AND p.status = 'selesai'
              ORDER BY j.waktu_mulai DESC LIMIT 5"
        );
        $stmt->execute([':m' => $musim]);
        $limaTerakhir = array_reverse($stmt->fetchAll());
    }
} catch (PDOException $ex) {
    error_log('Statistik gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

/** Hasil dari sisi klub: [huruf, kelas chip, teks lengkap]. */
function hasil_laga(?int $kita, ?int $lawan): array
{
    if ($kita === null || $lawan === null) {
        return ['-', 'netral', 'Belum ada skor'];
    }
    if ($kita > $lawan) {
        return ['M', 'hijau', 'Menang'];
    }
    return $kita === $lawan ? ['S', 'netral', 'Seri'] : ['K', 'merah', 'Kalah'];
}

function per90(int|string $nilai, int|string $menit): string
{
    return (int) $menit > 0 ? desimal((int) $nilai * 90 / (int) $menit, 2) : '0';
}

function persen(int|string $a, int|string $b): ?int
{
    return (int) $b > 0 ? (int) round(100 * (int) $a / (int) $b) : null;
}

// ---------- Filter di kanan judul ----------
$opsiMusim = [];
foreach ($daftarMusim as $m) {
    $opsiMusim[$m] = 'Musim ' . $m;
}
$filter = [];
if ($staf) {
    $filter['pemain'] = ['label' => 'Pemain', 'opsi' => opsi_pemain($daftar), 'nilai' => $pemain['id'] ?? ''];
}
if ($opsiMusim) {
    $filter['musim'] = ['label' => 'Musim', 'opsi' => $opsiMusim, 'nilai' => $musim];
}

$judul = $staf ? 'Statistik Pemain' : 'Statistik Saya';
halaman_awal($judul, 'Statistik Pemain');

kepala_halaman('trofi', $judul,
    $pemain === null && $staf
        ? 'Rekap pertandingan, gol, assist, kartu, dan rating seluruh skuad per musim.'
        : 'Pertandingan, menit bermain, gol, assist, kartu, dan rating per kompetisi.',
    '<div class="aksi-kanan">' . ($filter ? filter_get($filter) : '') . ($staf && $pemain === null ? tombol_input_statistik() : '') . '</div>');

if ($dbGagal): ?>
            <p class="galat" role="alert">Data statistik belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

// =====================================================================
// A. Satu pemain
// =====================================================================
if ($pemain !== null):
    $main   = (int) $total['main'];
    $menit  = (int) $total['menit'];
    $lagaTim = (int) $tim['main'];
    $akurasiUmpan = persen($total['umpan_sukses'], $total['umpan']);
    $akurasiTembak = persen($total['tepat'], $total['tembakan']);
    $porsiMenit = persen($menit, $lagaTim * 90);
    $porsiStarter = persen($total['starter'], $main);
    $adalahKiper = ($pemain['posisi'] ?? '') === 'kiper';

    // Titik grafik rating per laga.
    $titik = [];
    foreach ($laga as $l) {
        if ($l['rating'] === null) {
            continue;
        }
        [$h] = hasil_laga($l['skor_klub'] !== null ? (int) $l['skor_klub'] : null, $l['skor_lawan'] !== null ? (int) $l['skor_lawan'] : null);
        $kontribusi = [];
        if ((int) $l['goal'] > 0) { $kontribusi[] = $l['goal'] . ' gol'; }
        if ((int) $l['assist'] > 0) { $kontribusi[] = $l['assist'] . ' assist'; }
        $titik[] = [
            'x' => tanggal_id(substr((string) $l['waktu_mulai'], 0, 10)),
            'y' => (float) $l['rating'],
            't' => 'vs ' . $l['lawan'] . ' (' . ((int) $l['kandang'] ? 'kandang' : 'tandang') . ') · ' . $h . ' '
                 . $l['skor_klub'] . '–' . $l['skor_lawan'] . ($kontribusi ? ' · ' . implode(', ', $kontribusi) : ''),
        ];
    }
    ?>
            <?php if ($staf): ?><?= strip_pemain($pemain) ?><?php endif; ?>

            <section class="kpi-kisi" aria-label="Ringkasan musim <?= e($musim) ?>">
                <?= kartu_kpi('Pertandingan', angka($main), 'trofi', 'hijau',
                    e(angka($total['starter'])) . ' sebagai starter') ?>
                <?= kartu_kpi('Total gol', angka($total['goal']), 'target', 'biru',
                    e(per90($total['goal'], $menit)) . ' per 90 menit') ?>
                <?= kartu_kpi('Total assist', angka($total['assist']), 'bintang', 'ungu',
                    e(per90($total['assist'], $menit)) . ' per 90 menit') ?>
                <?= kartu_kpi('Menit bermain', angka($menit), 'stopwatch', 'kuning',
                    $main > 0 ? e(angka(round($menit / $main))) . ' menit per laga' : 'Belum bermain') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu">
                    <div class="kepala-kartu baris-kepala">
                        <div>
                            <h2>Tren rating per pertandingan</h2>
                            <p>Skala 1–10 · musim <?= e($musim) ?></p>
                        </div>
                        <?php if ($total['rating'] !== null): ?>
                            <div class="angka-sorot"><small>Rata-rata</small><?= chip_rating($total['rating']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?= grafik_garis($titik, 5, 10, [5, 6, 7, 8, 9, 10], 'Grafik rating ' . $pemain['nama_lengkap'] . ' per pertandingan musim ' . $musim, 1) ?>
                </section>

                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Ringkasan performa</h2>
                        <p>Dihitung dari seluruh laga musim ini</p>
                    </div>
                    <ul class="meteran-daftar">
                        <?php
                        $meteran = [
                            ['Menit dimainkan', $porsiMenit, angka($menit) . ' dari ' . angka($lagaTim * 90) . ' menit tim'],
                            ['Laga sebagai starter', $porsiStarter, angka($total['starter']) . ' dari ' . angka($main) . ' laga'],
                            ['Akurasi umpan', $akurasiUmpan, angka($total['umpan_sukses']) . ' dari ' . angka($total['umpan']) . ' umpan'],
                        ];
                        if (!$adalahKiper) {
                            $meteran[] = ['Tembakan tepat sasaran', $akurasiTembak, angka($total['tepat']) . ' dari ' . angka($total['tembakan']) . ' tembakan'];
                        }
                        foreach ($meteran as [$nm, $nilai, $ket]): ?>
                            <li>
                                <div class="baris"><span><?= e($nm) ?></span><b><?= $nilai === null ? '-' : $nilai . '%' ?></b></div>
                                <div class="progres" role="progressbar" aria-label="<?= e($nm) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $nilai ?>">
                                    <span style="width: <?= (int) $nilai ?>%"></span>
                                </div>
                                <small><?= e($ket) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="fakta-mini">
                        <div><dt>Kontribusi gol</dt><dd><?= angka((int) $total['goal'] + (int) $total['assist']) ?></dd></div>
                        <?php if ($adalahKiper): ?>
                            <div><dt>Penyelamatan</dt><dd><?= angka($total['penyelamatan']) ?></dd></div>
                        <?php else: ?>
                            <div><dt>Tekel</dt><dd><?= angka($total['tekel']) ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Kartu</dt><dd><span class="kartu-mini kuning" title="Kartu kuning"><?= (int) $total['kuning'] ?></span><span class="kartu-mini merah" title="Kartu merah"><?= (int) $total['merah'] ?></span></dd></div>
                    </dl>
                </section>
            </div>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulKompetisi">
                <div class="kepala-tabel">
                    <h2 id="judulKompetisi">Per kompetisi</h2>
                    <?= alat_tabel('Cari kompetisi...') ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data">
                        <thead>
                            <tr>
                                <?= th_urut('Kompetisi') ?>
                                <?= th_urut('Main', 'angka') ?>
                                <?= th_urut('Menit', 'angka') ?>
                                <?= th_urut('Gol', 'angka', 'desc') ?>
                                <?= th_urut('Assist', 'angka') ?>
                                <?= th_urut('Kartu', 'angka') ?>
                                <?= th_urut('Rating', 'angka') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perKompetisi as $k): ?>
                                <tr>
                                    <td><b><?= e((string) $k['kompetisi']) ?></b><small class="sub-sel"><?= e((string) $k['musim']) ?></small></td>
                                    <td data-nilai="<?= (int) $k['main'] ?>"><?= angka($k['main']) ?> <small class="redup">(<?= angka($k['starter']) ?> starter)</small></td>
                                    <td data-nilai="<?= (int) $k['menit'] ?>"><?= angka($k['menit']) ?></td>
                                    <td data-nilai="<?= (int) $k['goal'] ?>"><b><?= angka($k['goal']) ?></b></td>
                                    <td data-nilai="<?= (int) $k['assist'] ?>"><?= angka($k['assist']) ?></td>
                                    <td data-nilai="<?= (int) $k['kartu_kuning'] + 3 * (int) $k['kartu_merah'] ?>">
                                        <span class="kartu-mini kuning" title="Kartu kuning"><?= (int) $k['kartu_kuning'] ?></span><span class="kartu-mini merah" title="Kartu merah"><?= (int) $k['kartu_merah'] ?></span>
                                    </td>
                                    <td data-nilai="<?= (float) $k['rata_rating'] ?>"><?= chip_rating($k['rata_rating']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
            </section>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulLaga">
                <div class="kepala-tabel">
                    <h2 id="judulLaga">Riwayat pertandingan</h2>
                    <?php
                    $opsiKomp = [];
                    foreach ($perKompetisi as $k) {
                        $opsiKomp[(string) $k['kompetisi']] = (string) $k['kompetisi'];
                    }
                    echo alat_tabel('Cari lawan...', [
                        'kompetisi' => ['label' => 'Kompetisi', 'opsi' => $opsiKomp],
                        'hasil'     => ['label' => 'Hasil', 'opsi' => ['M' => 'Menang', 'S' => 'Seri', 'K' => 'Kalah']],
                    ]);
                    ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data">
                        <thead>
                            <tr>
                                <?= th_urut('Tanggal', 'angka', 'desc') ?>
                                <?= th_urut('Lawan') ?>
                                <?= th_urut('Hasil') ?>
                                <?= th_urut('Menit', 'angka') ?>
                                <?= th_urut('Gol', 'angka') ?>
                                <?= th_urut('Assist', 'angka') ?>
                                <th scope="col">Kartu</th>
                                <?= th_urut('Rating', 'angka') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laga as $l): ?>
                                <?php
                                $sk = $l['skor_klub'] !== null ? (int) $l['skor_klub'] : null;
                                $sl = $l['skor_lawan'] !== null ? (int) $l['skor_lawan'] : null;
                                [$huruf, $kelas, $teksHasil] = hasil_laga($sk, $sl);
                                ?>
                                <tr data-kompetisi="<?= e((string) $l['kompetisi']) ?>" data-hasil="<?= e($huruf) ?>">
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $l['waktu_mulai']) ?>"><b><?= e(tanggal_id((string) $l['waktu_mulai'])) ?></b><small class="sub-sel"><?= e((string) $l['kompetisi']) ?></small></td>
                                    <td>
                                        <b><?= e((string) $l['lawan']) ?></b>
                                        <small class="sub-sel"><?= (int) $l['kandang'] ? 'Kandang' : 'Tandang' ?></small>
                                    </td>
                                    <td data-nilai="<?= e($huruf) ?>"><span class="chip-hasil <?= $kelas ?>" title="<?= e($teksHasil) ?>"><b><?= e($huruf) ?></b> <?= $sk ?>–<?= $sl ?></span></td>
                                    <td data-nilai="<?= (int) $l['menit_bermain'] ?>"><?= (int) $l['menit_bermain'] ?>'<?= (int) $l['starter'] ? '' : ' <small class="redup">(cadangan)</small>' ?></td>
                                    <td data-nilai="<?= (int) $l['goal'] ?>"><?= (int) $l['goal'] ?></td>
                                    <td data-nilai="<?= (int) $l['assist'] ?>"><?= (int) $l['assist'] ?></td>
                                    <td>
                                        <?php if ((int) $l['kartu_kuning'] + (int) $l['kartu_merah'] === 0): ?>
                                            <span class="redup">-</span>
                                        <?php else: ?>
                                            <?php for ($i = 0; $i < (int) $l['kartu_kuning']; $i++): ?><span class="kartu-ikon kuning" title="Kartu kuning"></span><?php endfor; ?>
                                            <?php for ($i = 0; $i < (int) $l['kartu_merah']; $i++): ?><span class="kartu-ikon merah" title="Kartu merah"></span><?php endfor; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td data-nilai="<?= (float) $l['rating'] ?>"><?= chip_rating($l['rating']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Hasil: M = menang, S = seri, K = kalah (dari sisi <?= e(NAMA_KLUB) ?>). Rating: hijau ≥ 7,0 · biru ≥ 6,5 · kuning ≥ 6,0 · merah &lt; 6,0.</p>
            </section>
<?php

// =====================================================================
// B. Seluruh skuad (staf)
// =====================================================================
elseif ($staf):
    $mainTim = (int) $tim['main'];
    $ratingSkuad = array_filter(array_column($skuad, 'rating'), static fn($r) => $r !== null);
    $rataSkuad = $ratingSkuad ? array_sum($ratingSkuad) / count($ratingSkuad) : null;

    // Pemimpin kategori.
    $pemimpin = static function (array $baris, string $kolom) {
        $terbaik = null;
        foreach ($baris as $b) {
            if ($b[$kolom] !== null && (float) $b[$kolom] > 0 && (int) $b['main'] > 0 &&($terbaik === null || (float) $b[$kolom] > (float) $terbaik[$kolom])) {
                $terbaik = $b;
            }
        }
        return $terbaik;
    };
    $topGol    = $pemimpin($skuad, 'goal');
    $topAssist = $pemimpin($skuad, 'assist');
    $topRating = $pemimpin($skuad, 'rating');
    ?>
            <section class="kpi-kisi" aria-label="Ringkasan tim musim <?= e($musim) ?>">
                <?= kartu_kpi('Pertandingan tim', angka($mainTim), 'trofi', 'hijau',
                    e($tim['menang'] . ' M · ' . $tim['seri'] . ' S · ' . $tim['kalah'] . ' K')) ?>
                <?= kartu_kpi('Gol dicetak', angka($tim['gol_masuk']), 'target', 'biru',
                    $mainTim > 0 ? e(desimal($tim['gol_masuk'] / $mainTim, 2)) . ' per laga' : '') ?>
                <?= kartu_kpi('Gol kemasukan', angka($tim['gol_kemasukan']), 'perisai', 'ungu',
                    'Selisih ' . e(sprintf('%+d', (int) $tim['gol_masuk'] - (int) $tim['gol_kemasukan']))) ?>
                <?= kartu_kpi('Rata-rata rating skuad', $rataSkuad !== null ? desimal($rataSkuad) : '-', 'bintang', 'kuning',
                    e(count($ratingSkuad) . ' pemain sudah bermain')) ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Pemimpin kategori</h2>
                        <p>Musim <?= e($musim) ?></p>
                    </div>
                    <div class="pemimpin-kisi">
                        <?php foreach ([
                            ['Top skor', $topGol, 'goal', 'gol', 'target'],
                            ['Assist terbanyak', $topAssist, 'assist', 'assist', 'bintang'],
                            ['Rating tertinggi', $topRating, 'rating', '', 'naik'],
                        ] as [$lbl, $orang, $kol, $satuan, $ik]): ?>
                            <?php if ($orang === null): continue; endif; ?>
                            <a class="kartu-pemimpin" href="<?= e(url_dengan(['pemain' => $orang['id']])) ?>">
                                <span class="label-pemimpin"><?= ikon($ik) ?><?= e($lbl) ?></span>
                                <span class="isi-pemimpin">
                                    <?= avatar_kecil((string) $orang['nama_lengkap'], $orang['foto']) ?>
                                    <span><b><?= e((string) $orang['nama_lengkap']) ?></b><small><?= e(keterangan_pemain($orang + ['posisi_detail' => null])) ?></small></span>
                                </span>
                                <span class="nilai-pemimpin"><?= $kol === 'rating' ? desimal($orang[$kol]) : angka($orang[$kol]) ?> <small><?= e($satuan) ?></small></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>5 laga terakhir</h2>
                        <p>Hasil dari kiri (terlama) ke kanan (terbaru)</p>
                    </div>
                    <?php if (empty($limaTerakhir)): ?>
                        <div class="kosong kecil">Belum ada laga selesai di musim ini.</div>
                    <?php else: ?>
                        <ol class="form-tim">
                            <?php foreach ($limaTerakhir as $l): ?>
                                <?php [$huruf, $kelas, $teksHasil] = hasil_laga((int) $l['skor_klub'], (int) $l['skor_lawan']); ?>
                                <li title="<?= e($teksHasil . ' vs ' . $l['lawan'] . ', ' . tanggal_id((string) $l['waktu_mulai'])) ?>">
                                    <span class="bulatan-hasil <?= $kelas ?>"><?= e($huruf) ?></span>
                                    <small><?= (int) $l['skor_klub'] ?>–<?= (int) $l['skor_lawan'] ?></small>
                                    <small class="redup"><?= e((string) $l['lawan']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </section>
            </div>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulSkuad">
                <div class="kepala-tabel">
                    <h2 id="judulSkuad">Statistik skuad</h2>
                    <?= alat_tabel('Cari pemain...', ['posisi' => ['label' => 'Posisi', 'opsi' => LABEL_POSISI]]) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data baris-tautan">
                        <thead>
                            <tr>
                                <?= th_urut('Pemain') ?>
                                <?= th_urut('Main', 'angka') ?>
                                <?= th_urut('Menit', 'angka') ?>
                                <?= th_urut('Gol', 'angka', 'desc') ?>
                                <?= th_urut('Assist', 'angka') ?>
                                <?= th_urut('G+A /90', 'angka') ?>
                                <?= th_urut('Kartu', 'angka') ?>
                                <?= th_urut('Rating', 'angka') ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($skuad as $s): ?>
                                <?php $ga90 = (int) $s['menit'] > 0 ? ((int) $s['goal'] + (int) $s['assist']) * 90 / (int) $s['menit'] : 0; ?>
                                <tr data-posisi="<?= e((string) $s['posisi']) ?>">
                                    <td>
                                        <a class="sel-pemain" href="<?= e(url_dengan(['pemain' => $s['id']])) ?>">
                                            <?= avatar_kecil((string) $s['nama_lengkap'], $s['foto']) ?>
                                            <span><b><?= e((string) $s['nama_lengkap']) ?></b><small class="sub-sel"><?= e(keterangan_pemain($s + ['posisi_detail' => null])) ?></small></span>
                                        </a>
                                    </td>
                                    <td data-nilai="<?= (int) $s['main'] ?>"><?= angka($s['main']) ?></td>
                                    <td data-nilai="<?= (int) $s['menit'] ?>"><?= angka($s['menit']) ?></td>
                                    <td data-nilai="<?= (int) $s['goal'] ?>"><b><?= angka($s['goal']) ?></b></td>
                                    <td data-nilai="<?= (int) $s['assist'] ?>"><?= angka($s['assist']) ?></td>
                                    <td data-nilai="<?= round($ga90, 3) ?>"><?= desimal($ga90, 2) ?></td>
                                    <td data-nilai="<?= (int) $s['kuning'] + 3 * (int) $s['merah'] ?>">
                                        <span class="kartu-mini kuning" title="Kartu kuning"><?= (int) $s['kuning'] ?></span><span class="kartu-mini merah" title="Kartu merah"><?= (int) $s['merah'] ?></span>
                                    </td>
                                    <td data-nilai="<?= (float) $s['rating'] ?>"><?= chip_rating($s['rating']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">G+A /90 = jumlah gol dan assist per 90 menit bermain. Klik nama pemain untuk melihat rinciannya.</p>
            </section>
<?php
    tampil_kelola_statistik($musim, $skuad, $kembaliStat);
endif;

halaman_akhir();
