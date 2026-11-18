<?php
declare(strict_types=1);

// Slip gaji milik pengguna yang login: ringkasan tahunan, tren, kartu slip, detail & unduh PDF.

require __DIR__ . '/tampilan.php';
require __DIR__ . '/lib_gaji.php';

start_secure_session();
require_login();

$uid     = (int) $_SESSION['user_id'];
$dbGagal = false;
$slip    = [];
$tahunAda = [];
$tahun   = 0;

try {
    $stmt = db()->prepare("SELECT DISTINCT YEAR(periode) FROM penggajian WHERE user_id = :u AND status <> 'draft' ORDER BY 1 DESC");
    $stmt->execute([':u' => $uid]);
    $tahunAda = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $tahunUrl = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT);
    $tahun = in_array($tahunUrl, $tahunAda, true) ? (int) $tahunUrl : ($tahunAda[0] ?? (int) hari_ini()->format('Y'));

    // Draf tidak ditampilkan ke karyawan.
    $stmt = db()->prepare(
        "SELECT p.*, k.nomor_kontrak
           FROM penggajian p
           LEFT JOIN kontrak k ON k.id = p.kontrak_id
          WHERE p.user_id = :u AND p.status <> 'draft' AND YEAR(p.periode) = :t
          ORDER BY p.periode DESC"
    );
    $stmt->execute([':u' => $uid, ':t' => $tahun]);
    $slip = $stmt->fetchAll();
    $rincian = rincian_slip(array_column($slip, 'id'));
    $karyawan = data_karyawan($uid);
} catch (PDOException $ex) {
    error_log('Slip gaji gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

$opsiTahun = [];
foreach ($tahunAda as $t) {
    $opsiTahun[$t] = 'Tahun ' . $t;
}
// Admin/HR: tab "Semua slip" (seluruh karyawan) dan "Slip saya".
$adminGaji = ($_SESSION['role'] ?? '') === 'admin';
$tabGaji = $adminGaji && ($_GET['tab'] ?? 'semua') !== 'saya' ? 'semua' : 'saya';
halaman_awal('Slip Gaji', 'Slip Gaji');
kepala_halaman('uang', 'Slip Gaji', 'Unduh slip gaji dalam format PDF tanpa perlu meminta ke administrasi.',
    $tabGaji === 'saya' && count($opsiTahun) > 1 ? filter_get(['tahun' => ['label' => 'Tahun', 'opsi' => $opsiTahun, 'nilai' => $tahun]], $adminGaji ? ['tab' => 'saya'] : []) : '');
if ($adminGaji) {
    require __DIR__ . '/gaji_semua.php';
    if ($tabGaji === 'semua') {
        halaman_akhir();
        return;
    }
}

if ($dbGagal): ?>
            <p class="galat" role="alert">Data gaji belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

if (!$slip): ?>
            <div class="kosong">Belum ada slip gaji yang diterbitkan untuk tahun <?= (int) $tahun ?>.</div>
<?php
    halaman_akhir();
    return;
endif;

$terbaru  = $slip[0];
$sebelum  = $slip[1] ?? null;
$dibayar  = array_filter($slip, static fn($s) => $s['status'] === 'dibayar');
$totalBersih = array_sum(array_map(static fn($s) => (float) $s['total_bersih'], $dibayar));
$totalPotong = array_sum(array_map(static fn($s) => (float) $s['potongan'], $dibayar));
$totalBonus  = array_sum(array_map(static fn($s) => (float) $s['bonus'], $dibayar));
$pajak = 0.0;
foreach ($dibayar as $s) {
    foreach ($rincian[(int) $s['id']] ?? [] as $r) {
        if ($r['kode'] === 'pph21') {
            $pajak += (float) $r['jumlah'];
        }
    }
}

// Slip berikutnya: bulan setelah slip terbaru, dibayar akhir bulan.
$periodeBerikut = (new DateTimeImmutable((string) $terbaru['periode']))->modify('first day of next month');
$bayarBerikut   = $periodeBerikut->modify('last day of this month');
$hariBerikut    = selisih_hari(hari_ini()->format('Y-m-d'), $bayarBerikut->format('Y-m-d'));

// Grafik gaji bersih (juta rupiah), urut lama -> baru.
$urut = array_reverse($slip);
$nilaiJuta = array_map(static fn($s) => round((float) $s['total_bersih'] / 1e6, 1), $urut);
$maks = max($nilaiJuta);
$langkah = $maks > 60 ? 20 : ($maks > 30 ? 10 : 5);
$atas = (int) (ceil($maks * 1.1 / $langkah) * $langkah);
$bantu = range(0, $atas, $langkah);
$titik = [];
foreach ($urut as $i => $s) {
    $catatan = [];
    foreach ($rincian[(int) $s['id']] ?? [] as $r) {
        if ($r['kategori'] === 'bonus') {
            $catatan[] = $r['keterangan'];
        }
    }
    $titik[] = [
        'x' => mb_substr(NAMA_BULAN[(int) substr((string) $s['periode'], 5, 2)], 0, 3),
        'y' => $nilaiJuta[$i],
        't' => rupiah($s['total_bersih']) . ($catatan ? ' · ' . implode(', ', $catatan) : ''),
    ];
}
?>
            <section class="kpi-kisi" aria-label="Ringkasan gaji <?= (int) $tahun ?>">
                <?= kartu_kpi('Gaji bersih terakhir', rupiah_ringkas($terbaru['total_bersih']), 'uang', 'hijau',
                    e(bulan_tahun((string) $terbaru['periode'])) . ($sebelum ? ' · ' . selisih((float) $terbaru['total_bersih'] / 1e6, (float) $sebelum['total_bersih'] / 1e6, 1, ' jt', false, 'dari bulan lalu') : '')) ?>
                <?= kartu_kpi('Diterima tahun ' . $tahun, rupiah_ringkas($totalBersih), 'naik', 'biru',
                    e(count($dibayar) . ' slip · rata-rata ' . rupiah_ringkas(count($dibayar) ? $totalBersih / count($dibayar) : 0) . '/bln')) ?>
                <?= kartu_kpi('Bonus diterima', rupiah_ringkas($totalBonus), 'bintang', 'ungu', 'Bonus gol, penampilan & tanda tangan') ?>
                <?= kartu_kpi('Pajak & potongan', rupiah_ringkas($totalPotong), 'dokumen', 'kuning',
                    'PPh 21 ' . e(rupiah_ringkas($pajak)) . ' · sisanya BPJS & denda') ?>
            </section>

            <div class="kisi-2-1">
                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Tren gaji bersih</h2>
                        <p>Dalam juta rupiah per bulan, tahun <?= (int) $tahun ?></p>
                    </div>
                    <?= grafik_garis($titik, 0, $atas, $bantu, 'Grafik gaji bersih per bulan tahun ' . $tahun, 1) ?>
                </section>

                <section class="kartu">
                    <div class="kepala-kartu">
                        <h2>Info pembayaran</h2>
                        <p>Gaji dibayarkan setiap hari kerja terakhir</p>
                    </div>
                    <ul class="info-bayar">
                        <li><?= ikon('kalender') ?><span><small>Slip berikutnya</small><b><?= e(NAMA_BULAN[(int) $periodeBerikut->format('n')] . ' ' . $periodeBerikut->format('Y')) ?></b>
                            <?= $hariBerikut >= 0 ? 'Terbit ' . e(tanggal_id($bayarBerikut->format('Y-m-d'))) . ' · ' . angka($hariBerikut) . ' hari lagi' : 'Sedang diproses HR' ?></span></li>
                        <li><?= ikon('uang') ?><span><small>Rekening penerima</small><b><?= e((string) ($terbaru['rekening_tujuan'] ?? 'Belum diatur')) ?></b>Atas nama <?= e((string) $karyawan['nama_lengkap']) ?></span></li>
                        <li><?= ikon('pena') ?><span><small>Dasar penggajian</small><b><?= e((string) ($terbaru['nomor_kontrak'] ?? '-')) ?></b><a href="kontrak.php">Lihat kontrak</a></span></li>
                    </ul>
                </section>
            </div>

            <h2 class="judul-bagian">Slip gaji <?= (int) $tahun ?> <small><?= count($slip) ?> slip</small></h2>
            <div class="kisi-slip">
                <?php foreach ($slip as $i => $s): ?>
                    <?php [$teksStatus, $kelasStatus] = LABEL_STATUS_SLIP[$s['status']] ?? [$s['status'], 'netral']; ?>
                    <article class="kartu kartu-slip<?= $i === 0 ? ' terbaru' : '' ?>">
                        <div class="slip-atas">
                            <div>
                                <small>Periode</small>
                                <h3><?= e(bulan_tahun((string) $s['periode'])) ?></h3>
                            </div>
                            <span class="chip <?= $kelasStatus ?> bertitik"><?= e($teksStatus) ?></span>
                        </div>
                        <p class="slip-bersih"><?= e(rupiah($s['total_bersih'])) ?></p>
                        <dl class="slip-ringkas">
                            <div><dt>Gaji pokok</dt><dd><?= e(rupiah($s['gaji_pokok'])) ?></dd></div>
                            <div><dt>Tunjangan + bonus</dt><dd><?= e(rupiah((float) $s['tunjangan'] + (float) $s['bonus'])) ?></dd></div>
                            <div><dt>Potongan</dt><dd class="teks-merah">- <?= e(rupiah($s['potongan'])) ?></dd></div>
                        </dl>
                        <?php if ((float) $s['bonus'] > 0): ?>
                            <span class="pita-bonus"><?= ikon('bintang') ?>Termasuk bonus <?= e(rupiah_ringkas($s['bonus'])) ?></span>
                        <?php endif; ?>
                        <div class="slip-aksi">
                            <button type="button" class="tombol kecil" data-buka-dialog="slip-<?= (int) $s['id'] ?>"><?= ikon('mata') ?>Lihat</button>
                            <a class="tombol kecil utama" href="gaji_pdf.php?id=<?= (int) $s['id'] ?>"><?= ikon('unduh') ?>Unduh PDF</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php foreach ($slip as $s): ?>
                <?php $r = susun_rincian($s, $rincian[(int) $s['id']] ?? []); ?>
                <dialog class="modal lebar" id="slip-<?= (int) $s['id'] ?>" aria-labelledby="judul-slip-<?= (int) $s['id'] ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('uang') ?></span>
                        <div>
                            <h2 id="judul-slip-<?= (int) $s['id'] ?>">Slip gaji <?= e(bulan_tahun((string) $s['periode'])) ?></h2>
                            <p><?= e(nomor_slip($s)) ?> · <?= $s['tanggal_bayar'] ? 'Dibayar ' . e(tanggal_id((string) $s['tanggal_bayar'], true)) : 'Belum dibayar' ?></p>
                        </div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <dl class="fakta-cedera">
                            <div><dt>Nama</dt><dd><?= e((string) $karyawan['nama_lengkap']) ?></dd></div>
                            <div><dt>ID / Jabatan</dt><dd><?= e($karyawan['id_karyawan'] . ' · ' . $karyawan['jabatan_teks']) ?></dd></div>
                            <div><dt>Rekening</dt><dd><?= e((string) ($s['rekening_tujuan'] ?? '-')) ?></dd></div>
                        </dl>
                        <div class="slip-kolom">
                            <div>
                                <h3>Pendapatan</h3>
                                <ul class="rincian-uang">
                                    <?php foreach ($r['pendapatan'] as $b): ?>
                                        <li><span><?= e((string) $b['keterangan']) ?></span><b><?= e(rupiah($b['jumlah'])) ?></b></li>
                                    <?php endforeach; ?>
                                    <li class="total"><span>Total bruto</span><b><?= e(rupiah($r['bruto'])) ?></b></li>
                                </ul>
                            </div>
                            <div>
                                <h3>Potongan</h3>
                                <ul class="rincian-uang">
                                    <?php foreach ($r['potongan'] as $b): ?>
                                        <li class="kurang"><span><?= e((string) $b['keterangan']) ?></span><b><?= e(rupiah($b['jumlah'])) ?></b></li>
                                    <?php endforeach; ?>
                                    <li class="total"><span>Total potongan</span><b><?= e(rupiah($r['total_potongan'])) ?></b></li>
                                </ul>
                            </div>
                        </div>
                        <div class="slip-total">
                            <span>Gaji bersih diterima</span>
                            <b><?= e(rupiah($s['total_bersih'])) ?></b>
                            <small><?= e(terbilang_rupiah((float) $s['total_bersih'])) ?></small>
                        </div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Tutup</button>
                        <a class="tombol utama" href="gaji_pdf.php?id=<?= (int) $s['id'] ?>"><?= ikon('unduh') ?>Unduh PDF</a>
                    </div>
                </dialog>
            <?php endforeach; ?>
<?php
halaman_akhir();
