<?php
declare(strict_types=1);

// Kelola hasil tes kebugaran (staf): input, ubah, hapus, skor otomatis, distribusi level, tren per pemain.
// Dimuat oleh kebugaran.php.

require_once __DIR__ . '/inc_kelola_umum.php';

const KOLOM_TES = ['user_id', 'tanggal', 'tinggi_cm', 'berat_kg', 'lemak_tubuh_persen', 'denyut_istirahat', 'tekanan_darah',
                   'vo2max', 'sprint_30m_detik', 'yoyo_level', 'skor_kebugaran', 'status_kebugaran', 'catatan'];

/**
 * Skor 0-100 dari komponen yang tersedia (rata-rata berbobot):
 * VO2max 40% (45 -> 0, 66 -> 100), Yo-Yo IR1 30% (15 -> 0, 23 -> 100), sprint 30 m 30% (4,5 s -> 0, 3,8 s -> 100).
 * Sama persis dengan pratinjau di assets/app.js.
 */
function skor_otomatis(?float $vo2, ?float $sprint, ?float $yoyo): ?int
{
    $komponen = [];
    $jepit = static fn(float $x) => max(0.0, min(1.0, $x));
    if ($vo2 !== null) {
        $komponen[] = [0.4, $jepit(($vo2 - 45) / 21)];
    }
    if ($yoyo !== null) {
        $komponen[] = [0.3, $jepit(($yoyo - 15) / 8)];
    }
    if ($sprint !== null) {
        $komponen[] = [0.3, $jepit((4.5 - $sprint) / 0.7)];
    }
    if (!$komponen) {
        return null;
    }
    $bobot = array_sum(array_column($komponen, 0));
    $nilai = array_sum(array_map(static fn($k) => $k[0] * $k[1], $komponen));
    return (int) round(100 * $nilai / $bobot);
}

function proses_kelola_kebugaran(string $kembali): never
{
    k_mulai_post($kembali);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $pdo = db();
    try {
        if ($aksi === 'tes_hapus') {
            $st = $pdo->prepare('DELETE FROM pemeriksaan_kebugaran WHERE id = ?');
            $st->execute([(int) ($_POST['id'] ?? 0)]);
            catat_aktivitas('hapus', 'pemeriksaan_kebugaran', (int) $_POST['id']);
            flash_set($st->rowCount() ? 'sukses' : 'galat', $st->rowCount() ? 'Hasil tes dihapus.' : 'Hasil tes tidak ditemukan.');
            kembali_ke($kembali);
        }
        if ($aksi !== 'tes_simpan') {
            kembali_ke($kembali);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $v = k_ambil(KOLOM_TES);
        $g = [];
        if (!k_pemain_sah((int) $v['user_id'])) {
            $g['user_id'] = 'Pilih pemain aktif.';
        }
        if (!k_tanggal($v['tanggal']) || $v['tanggal'] > hari_ini()->format('Y-m-d')) {
            $g['tanggal'] = 'Tanggal tes tidak valid atau di masa depan.';
        }
        // [kolom, min, max, desimal?]
        $angka = [];
        foreach ([['tinggi_cm', 140, 220, false], ['berat_kg', 40, 130, true], ['lemak_tubuh_persen', 3, 35, true],
                  ['denyut_istirahat', 30, 110, false], ['vo2max', 20, 90, true], ['sprint_30m_detik', 3, 7, true],
                  ['yoyo_level', 5, 25, true], ['skor_kebugaran', 0, 100, false]] as [$k, $min, $max, $des]) {
            if ($v[$k] === '') {
                $angka[$k] = null;
                continue;
            }
            $angka[$k] = $des ? k_desimal($v[$k], $min, $max) : k_int($v[$k], $min, $max);
            if ($angka[$k] === null) {
                $g[$k] = 'Isi ' . $min . '–' . $max . '.';
            }
        }
        if ($v['tekanan_darah'] !== '' && !preg_match('/^\d{2,3}\/\d{2,3}$/', $v['tekanan_darah'])) {
            $g['tekanan_darah'] = 'Format sistolik/diastolik, mis. 120/80.';
        }
        if (!isset(LABEL_STATUS_FIT[$v['status_kebugaran']])) {
            $g['status_kebugaran'] = 'Pilih status kebugaran.';
        }
        if (mb_strlen($v['catatan']) > 1000) {
            $g['catatan'] = 'Catatan maksimal 1000 karakter.';
        }
        if (!$g && $angka['skor_kebugaran'] === null) {
            $angka['skor_kebugaran'] = skor_otomatis($angka['vo2max'], $angka['sprint_30m_detik'], $angka['yoyo_level']);
            if ($angka['skor_kebugaran'] === null) {
                $g['skor_kebugaran'] = 'Isi skor, atau isi minimal satu dari VO₂max, sprint, Yo-Yo agar skor dihitung otomatis.';
            }
        }
        if (!$g) {
            $st = $pdo->prepare('SELECT 1 FROM pemeriksaan_kebugaran WHERE user_id = ? AND tanggal = ? AND id <> ?');
            $st->execute([(int) $v['user_id'], $v['tanggal'], $id]);
            if ($st->fetchColumn()) {
                $g['tanggal'] = 'Pemain ini sudah punya hasil tes pada tanggal tersebut.';
            }
        }
        if ($g) {
            k_gagal('form_tes', $g, $v + ['id' => $id], $kembali);
        }

        $data = [(int) $v['user_id'], $v['tanggal'], $angka['tinggi_cm'], $angka['berat_kg'], $angka['lemak_tubuh_persen'], $angka['denyut_istirahat'],
                 $v['tekanan_darah'] ?: null, $angka['vo2max'], $angka['sprint_30m_detik'], $angka['yoyo_level'], $angka['skor_kebugaran'],
                 $v['status_kebugaran'], $v['catatan'] ?: null, $_SESSION['user_id']];
        $ubah = $id > 0;
        if ($ubah) {
            $st = $pdo->prepare('UPDATE pemeriksaan_kebugaran SET user_id = ?, tanggal = ?, tinggi_cm = ?, berat_kg = ?, lemak_tubuh_persen = ?,
                                        denyut_istirahat = ?, tekanan_darah = ?, vo2max = ?, sprint_30m_detik = ?, yoyo_level = ?,
                                        skor_kebugaran = ?, status_kebugaran = ?, catatan = ?, pemeriksa_id = ? WHERE id = ?');
            $st->execute([...$data, $id]);
        } else {
            $pdo->prepare('INSERT INTO pemeriksaan_kebugaran (user_id, tanggal, tinggi_cm, berat_kg, lemak_tubuh_persen, denyut_istirahat,
                                  tekanan_darah, vo2max, sprint_30m_detik, yoyo_level, skor_kebugaran, status_kebugaran, catatan, pemeriksa_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($data);
            $id = (int) $pdo->lastInsertId();
            kirim_notifikasi((int) $v['user_id'], 'performa', 'Hasil tes kebugaran: skor ' . $angka['skor_kebugaran'],
                'Tes ' . tanggal_id($v['tanggal']) . ' sudah dinilai tim medis. Lihat rincian VO₂max, sprint, dan Yo-Yo Anda.', 'kebugaran.php');
        }
        // Tinggi/berat terbaru ikut memperbarui profil pemain.
        if ($angka['tinggi_cm'] !== null || $angka['berat_kg'] !== null) {
            $pdo->prepare('UPDATE profil_pemain SET tinggi_cm = COALESCE(?, tinggi_cm), berat_kg = COALESCE(?, berat_kg)
                            WHERE user_id = ? AND NOT EXISTS (SELECT 1 FROM pemeriksaan_kebugaran k WHERE k.user_id = ? AND k.tanggal > ?)')
                ->execute([$angka['tinggi_cm'], $angka['berat_kg'], (int) $v['user_id'], (int) $v['user_id'], $v['tanggal']]);
        }
        catat_aktivitas($ubah ? 'ubah' : 'tambah', 'pemeriksaan_kebugaran', $id);
        flash_set('sukses', 'Hasil tes tersimpan (skor ' . $angka['skor_kebugaran'] . ').');
    } catch (PDOException $ex) {
        error_log('Tes kebugaran gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Hasil tes belum dapat disimpan. Coba lagi nanti.');
    }
    kembali_ke($kembali);
}

function tombol_input_tes(?int $pemainId): string
{
    return '<button type="button" class="tombol utama" ' . isi_dialog('dialogTes', [
        'id' => 0, 'user_id' => $pemainId ?? '', 'tanggal' => hari_ini()->format('Y-m-d'), 'status_kebugaran' => 'fit',
    ], 'Input hasil tes') . '>' . ikon('tambah') . 'Input hasil tes</button>';
}

/** Panel distribusi level + tren skor per pemain (atau rata-rata tim). */
function panel_kebugaran_staf(array $terakhir, array $trenTim, array $daftar): void
{
    $pilih = filter_input(INPUT_GET, 'tren', FILTER_VALIDATE_INT) ?: 0;
    $distribusi = array_fill_keys(array_keys(LEVEL_KEBUGARAN), 0);
    foreach ($terakhir as $t) {
        [$k] = level_kebugaran($t['skor_kebugaran']);
        if ($k !== '') {
            $distribusi[$k]++;
        }
    }
    $maks = max(1, ...array_values($distribusi));
    $warna = ['sangat_baik' => '', 'baik' => 'biru', 'cukup' => 'kuning', 'kurang' => 'merah'];

    $namaTren = 'Rata-rata tim';
    if ($pilih > 0) {
        $st = db()->prepare("SELECT k.tanggal, k.skor_kebugaran AS skor, k.vo2max, u.nama_lengkap FROM pemeriksaan_kebugaran k
                              JOIN users u ON u.id = k.user_id AND u.role = 'pemain' WHERE k.user_id = ? AND k.skor_kebugaran IS NOT NULL ORDER BY k.tanggal");
        $st->execute([$pilih]);
        $baris = $st->fetchAll();
        if ($baris) {
            $namaTren = (string) $baris[0]['nama_lengkap'];
            $titik = array_map(static fn($r) => ['x' => tanggal_id((string) $r['tanggal']), 'y' => (int) $r['skor'],
                't' => 'Skor ' . $r['skor'] . ' · ' . level_kebugaran($r['skor'])[1] . ($r['vo2max'] !== null ? ' · VO₂max ' . desimal($r['vo2max']) : '')], $baris);
        }
    }
    if (!isset($titik)) {
        $pilih = 0;
        $titik = array_map(static fn($r) => ['x' => tanggal_id((string) $r['tanggal']), 'y' => round((float) $r['skor'], 1),
            't' => 'Rata-rata ' . desimal($r['skor']) . ' dari ' . $r['jumlah'] . ' pemain'], $trenTim);
    }
    [$minSumbu, $bantu] = sumbu_skor(array_map(static fn($t) => (int) round((float) $t['y']), $titik));
    $opsi = [0 => 'Rata-rata tim'];
    foreach ($daftar as $p) {
        $opsi[$p['id']] = $p['nama_lengkap'];
    }
    ?>
            <div class="kisi-1-2">
                <section class="kartu" aria-labelledby="judulDistribusi">
                    <div class="kepala-kartu"><h2 id="judulDistribusi">Distribusi level</h2><p>Tes terakhir tiap pemain (<?= count($terakhir) ?> pemain)</p></div>
                    <ul class="meteran-daftar">
                        <?php foreach ($distribusi as $k => $n): ?>
                            <li><div class="baris"><span><?= e(LEVEL_KEBUGARAN[$k]) ?></span><b><?= $n ?> pemain</b></div>
                                <div class="progres <?= $warna[$k] ?>"><span style="width: <?= round(100 * $n / $maks) ?>%"></span></div></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="bantuan">Pemain berlevel Kurang sebaiknya mendapat <a href="latihan.php">program latihan</a> fisik khusus.</p>
                </section>
                <section class="kartu" aria-labelledby="judulTrenPemain">
                    <div class="kepala-kartu baris-kepala">
                        <div><h2 id="judulTrenPemain">Tren skor kebugaran</h2><p><?= e($namaTren) ?> · skor 0–100 per tes</p></div>
                        <?= filter_get(['tren' => ['label' => 'Tampilkan tren', 'opsi' => $opsi, 'nilai' => $pilih]]) ?>
                    </div>
                    <?= grafik_garis($titik, $minSumbu, 100, $bantu, 'Grafik tren skor kebugaran ' . $namaTren, $pilih ? 0 : 1) ?>
                </section>
            </div>
    <?php
}

/** Tabel semua hasil tes dengan ubah/hapus. */
function tabel_semua_tes(string $kembali, array $opsiLevel): void
{
    $semua = db()->query(
        "SELECT k.*, u.nama_lengkap, pr.posisi_kode, o.nama_lengkap AS pemeriksa
           FROM pemeriksaan_kebugaran k JOIN users u ON u.id = k.user_id AND u.role = 'pemain'
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id LEFT JOIN users o ON o.id = k.pemeriksa_id
          ORDER BY k.tanggal DESC, u.nama_lengkap"
    )->fetchAll();
    ?>
            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulSemuaTes">
                <div class="kepala-tabel">
                    <h2 id="judulSemuaTes">Semua hasil tes</h2>
                    <?= alat_tabel('Cari pemain...', $opsiLevel + ['status' => ['label' => 'Status', 'opsi' => array_map(static fn($x) => $x[0], LABEL_STATUS_FIT)]]) ?>
                </div>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar aksi-tetap">
                        <thead><tr>
                            <?= th_urut('Pemain') ?><?= th_urut('Tanggal', 'angka', 'desc') ?><?= th_urut('Skor', 'angka') ?><?= th_urut('VO₂max', 'angka') ?>
                            <?= th_urut('Sprint 30 m', 'angka') ?><?= th_urut('Yo-Yo', 'angka') ?><?= th_urut('Lemak', 'angka') ?><?= th_urut('Level', 'angka') ?>
                            <?= th_urut('Status') ?><th scope="col"><span class="sr">Aksi</span></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($semua as $t): ?>
                                <?php
                                [$kunciLevel] = level_kebugaran($t['skor_kebugaran']);
                                $json = array_intersect_key($t, array_flip(KOLOM_TES)) + ['id' => (int) $t['id']];
                                foreach (['berat_kg', 'lemak_tubuh_persen', 'vo2max', 'sprint_30m_detik', 'yoyo_level'] as $k) {
                                    $json[$k] = $t[$k] !== null ? rtrim(rtrim((string) $t[$k], '0'), '.') : '';
                                }
                                ?>
                                <tr data-level="<?= e($kunciLevel) ?>" data-status="<?= e((string) $t['status_kebugaran']) ?>">
                                    <td><a class="sel-pemain" href="kebugaran.php?pemain=<?= (int) $t['user_id'] ?>"><span><b><?= e((string) $t['nama_lengkap']) ?></b><small class="sub-sel"><?= e((string) ($t['posisi_kode'] ?? '')) ?></small></span></a></td>
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $t['tanggal']) ?>"><?= e(tanggal_id((string) $t['tanggal'])) ?><small class="sub-sel"><?= e((string) ($t['pemeriksa'] ?? '')) ?></small></td>
                                    <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>"><?= sel_skor($t['skor_kebugaran']) ?></td>
                                    <td data-nilai="<?= (float) $t['vo2max'] ?>"><?= nilai_opsional($t['vo2max'], 1) ?></td>
                                    <td data-nilai="<?= (float) $t['sprint_30m_detik'] ?>"><?= nilai_opsional($t['sprint_30m_detik'], 2, ' s') ?></td>
                                    <td data-nilai="<?= (float) $t['yoyo_level'] ?>"><?= nilai_opsional($t['yoyo_level'], 1) ?></td>
                                    <td data-nilai="<?= (float) $t['lemak_tubuh_persen'] ?>"><?= nilai_opsional($t['lemak_tubuh_persen'], 1, '%') ?></td>
                                    <td data-nilai="<?= (int) $t['skor_kebugaran'] ?>"><?= chip_level($t['skor_kebugaran']) ?></td>
                                    <td><?= chip_fit((string) $t['status_kebugaran']) ?></td>
                                    <td><?= k_aksi_baris('dialogTes', $json, 'Ubah tes ' . $t['nama_lengkap'], 'tes_hapus', (int) $t['id'], $kembali,
                                        'hasil tes ' . $t['nama_lengkap'] . ' tanggal ' . tanggal_id((string) $t['tanggal'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
            </section>
    <?php
}

/** Dialog input / ubah hasil tes. */
function dialog_tes(string $kembali, ?int $pemainId): void
{
    $form = k_form('form_tes');
    $galat = $form['galat'];
    $nilai = $form['nilai'] + ['user_id' => $pemainId ?? '', 'tanggal' => hari_ini()->format('Y-m-d'), 'status_kebugaran' => 'fit'];
    ?>
            <dialog class="modal lebar" id="dialogTes" aria-labelledby="judulDialogTes"<?= $galat ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($kembali) ?>" novalidate data-form-tes>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="tes_simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('aktivitas') ?></span>
                        <div><h2 id="judulDialogTes" data-judul-dialog><?= (int) ($nilai['id'] ?? 0) ? 'Ubah hasil tes' : 'Input hasil tes' ?></h2>
                            <p>Kosongkan skor agar dihitung otomatis dari VO₂max, Yo-Yo, dan sprint.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-3">
                            <?= k_medan('user_id', 'Pemain', k_pilih('user_id', k_opsi_pemain(), $nilai), $galat) ?>
                            <?= k_medan('tanggal', 'Tanggal tes', k_isian('tanggal', $nilai, 'date', 'max="' . e(hari_ini()->format('Y-m-d')) . '"'), $galat) ?>
                            <?= k_medan('status_kebugaran', 'Status', k_pilih('status_kebugaran', array_map(static fn($x) => $x[0], LABEL_STATUS_FIT), $nilai), $galat) ?>
                        </div>
                        <h3 class="pemisah-bagian">Tes lapangan</h3>
                        <div class="baris-3">
                            <?= k_medan('vo2max', 'VO₂max (ml/kg/mnt)', k_isian('vo2max', $nilai, 'number', 'min="20" max="90" step="0.1"'), $galat, false) ?>
                            <?= k_medan('sprint_30m_detik', 'Sprint 30 m (detik)', k_isian('sprint_30m_detik', $nilai, 'number', 'min="3" max="7" step="0.01"'), $galat, false) ?>
                            <?= k_medan('yoyo_level', 'Yo-Yo IR1 (level)', k_isian('yoyo_level', $nilai, 'number', 'min="5" max="25" step="0.1"'), $galat, false) ?>
                        </div>
                        <div class="baris-2">
                            <?= k_medan('skor_kebugaran', 'Skor (0–100)', k_isian('skor_kebugaran', $nilai, 'number', 'min="0" max="100" placeholder="Otomatis"'), $galat, false) ?>
                            <p class="info-hari pratinjau-skor" data-pratinjau-skor aria-live="polite"></p>
                        </div>
                        <h3 class="pemisah-bagian">Komposisi tubuh &amp; vital</h3>
                        <div class="baris-3">
                            <?= k_medan('tinggi_cm', 'Tinggi (cm)', k_isian('tinggi_cm', $nilai, 'number', 'min="140" max="220"'), $galat, false) ?>
                            <?= k_medan('berat_kg', 'Berat (kg)', k_isian('berat_kg', $nilai, 'number', 'min="40" max="130" step="0.1"'), $galat, false) ?>
                            <?= k_medan('lemak_tubuh_persen', 'Lemak tubuh (%)', k_isian('lemak_tubuh_persen', $nilai, 'number', 'min="3" max="35" step="0.1"'), $galat, false) ?>
                        </div>
                        <div class="baris-2">
                            <?= k_medan('denyut_istirahat', 'Denyut istirahat (bpm)', k_isian('denyut_istirahat', $nilai, 'number', 'min="30" max="110"'), $galat, false) ?>
                            <?= k_medan('tekanan_darah', 'Tekanan darah', k_isian('tekanan_darah', $nilai, 'text', 'maxlength="7" placeholder="120/80"'), $galat, false) ?>
                        </div>
                        <?= k_medan('catatan', 'Catatan', k_isian('catatan', $nilai, 'text', 'maxlength="1000" placeholder="mis. Daya tahan meningkat, fokus sprint berulang"'), $galat, false) ?>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan</button>
                    </div>
                </form>
            </dialog>
    <?php
}
