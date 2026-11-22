<?php
declare(strict_types=1);

// Kelola statistik per pertandingan (staf): input, ubah, hapus, peringkat 10 besar.
// Dimuat oleh statistik.php.

require_once __DIR__ . '/inc_kelola_umum.php';

const KOLOM_STATISTIK = ['jadwal_id', 'user_id', 'starter', 'menit_bermain', 'goal', 'assist', 'tembakan', 'tembakan_tepat',
                         'umpan', 'umpan_sukses', 'tekel', 'penyelamatan', 'kartu_kuning', 'kartu_merah', 'rating', 'catatan'];

/** Proses POST input/ubah/hapus statistik lalu kembali ke $kembali. */
function proses_kelola_statistik(string $kembali): never
{
    k_mulai_post($kembali);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $pdo = db();
    try {
        if ($aksi === 'stat_hapus') {
            $st = $pdo->prepare('SELECT user_id FROM statistik_pertandingan WHERE id = ?');
            $st->execute([(int) ($_POST['id'] ?? 0)]);
            $uid = $st->fetchColumn();
            if ($uid === false) {
                flash_set('galat', 'Catatan statistik tidak ditemukan.');
                kembali_ke($kembali);
            }
            $pdo->prepare('DELETE FROM statistik_pertandingan WHERE id = ?')->execute([(int) $_POST['id']]);
            k_sinkron_total_pemain((int) $uid);
            catat_aktivitas('hapus', 'statistik_pertandingan', (int) $_POST['id']);
            flash_set('sukses', 'Catatan statistik dihapus; total pemain diperbarui.');
            kembali_ke($kembali);
        }
        if ($aksi !== 'stat_simpan') {
            kembali_ke($kembali);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $v = k_ambil(KOLOM_STATISTIK);
        $v['starter'] = isset($_POST['starter']) && $_POST['starter'] === '1' ? '1' : '0';
        $g = [];
        $n = [];

        $st = $pdo->prepare("SELECT j.id, j.waktu_mulai, p.skor_klub FROM jadwal j JOIN pertandingan p ON p.jadwal_id = j.id
                              WHERE j.id = ? AND j.jenis = 'pertandingan' AND j.waktu_mulai <= NOW()");
        $st->execute([(int) $v['jadwal_id']]);
        $laga = $st->fetch();
        if (!$laga) {
            $g['jadwal_id'] = 'Pilih pertandingan yang sudah dimainkan.';
        }
        if (!k_pemain_sah((int) $v['user_id'])) {
            $g['user_id'] = 'Pilih pemain aktif.';
        }
        $batas = ['menit_bermain' => [0, 130], 'goal' => [0, 10], 'assist' => [0, 10], 'tembakan' => [0, 40], 'tembakan_tepat' => [0, 40],
                  'umpan' => [0, 250], 'umpan_sukses' => [0, 250], 'tekel' => [0, 40], 'penyelamatan' => [0, 40],
                  'kartu_kuning' => [0, 2], 'kartu_merah' => [0, 1]];
        foreach ($batas as $k => [$min, $max]) {
            $n[$k] = k_int($v[$k], $min, $max, 0);
            if ($n[$k] === null) {
                $g[$k] = 'Isi angka ' . $min . '–' . $max . '.';
            }
        }
        if (!isset($g['tembakan_tepat']) && !isset($g['tembakan']) && $n['tembakan_tepat'] > $n['tembakan']) {
            $g['tembakan_tepat'] = 'Tembakan tepat tidak boleh melebihi total tembakan.';
        }
        if (!isset($g['umpan_sukses']) && !isset($g['umpan']) && $n['umpan_sukses'] > $n['umpan']) {
            $g['umpan_sukses'] = 'Umpan sukses tidak boleh melebihi total umpan.';
        }
        if ($n['menit_bermain'] === 0 && ($n['goal'] || $n['assist'])) {
            $g['menit_bermain'] = 'Pemain dengan 0 menit tidak bisa mencetak gol/assist.';
        }
        $rating = $v['rating'] === '' ? null : k_desimal($v['rating'], 1, 10);
        if ($v['rating'] !== '' && $rating === null) {
            $g['rating'] = 'Rating 1,0–10,0.';
        }
        if (mb_strlen($v['catatan']) > 255) {
            $g['catatan'] = 'Catatan maksimal 255 karakter.';
        }
        if (!$g) {
            // Satu catatan per pemain per pertandingan.
            $st = $pdo->prepare('SELECT id FROM statistik_pertandingan WHERE jadwal_id = ? AND user_id = ? AND id <> ?');
            $st->execute([(int) $v['jadwal_id'], (int) $v['user_id'], $id]);
            if ($st->fetchColumn()) {
                $g['user_id'] = 'Statistik pemain ini di laga tersebut sudah ada. Ubah catatan yang ada.';
            }
        }
        if (!$g && $laga['skor_klub'] !== null) {
            // Total gol pemain tidak boleh melebihi skor klub di laga itu.
            $st = $pdo->prepare('SELECT COALESCE(SUM(goal), 0) FROM statistik_pertandingan WHERE jadwal_id = ? AND id <> ?');
            $st->execute([(int) $v['jadwal_id'], $id]);
            if ((int) $st->fetchColumn() + $n['goal'] > (int) $laga['skor_klub']) {
                $g['goal'] = 'Total gol pemain melebihi skor klub (' . (int) $laga['skor_klub'] . ') di laga ini.';
            }
        }
        if ($g) {
            k_gagal('form_stat', $g, $v + ['id' => $id], $kembali);
        }

        $lamaUid = null;
        if ($id > 0) {
            $st = $pdo->prepare('SELECT user_id FROM statistik_pertandingan WHERE id = ?');
            $st->execute([$id]);
            $lamaUid = $st->fetchColumn();
            if ($lamaUid === false) {
                flash_set('galat', 'Catatan statistik tidak ditemukan.');
                kembali_ke($kembali);
            }
        }
        $data = [(int) $v['jadwal_id'], (int) $v['user_id'], (int) $v['starter'], $n['menit_bermain'], $n['goal'], $n['assist'], $n['tembakan'],
                 $n['tembakan_tepat'], $n['umpan'], $n['umpan_sukses'], $n['tekel'], $n['penyelamatan'], $n['kartu_kuning'], $n['kartu_merah'],
                 $rating, $v['catatan'] === '' ? null : $v['catatan'], $_SESSION['user_id']];
        $pdo->beginTransaction();
        if ($id > 0) {
            $pdo->prepare('UPDATE statistik_pertandingan SET jadwal_id = ?, user_id = ?, starter = ?, menit_bermain = ?, goal = ?, assist = ?, tembakan = ?,
                                  tembakan_tepat = ?, umpan = ?, umpan_sukses = ?, tekel = ?, penyelamatan = ?, kartu_kuning = ?, kartu_merah = ?,
                                  rating = ?, catatan = ?, dicatat_oleh = ? WHERE id = ?')->execute([...$data, $id]);
        } else {
            $pdo->prepare('INSERT INTO statistik_pertandingan (jadwal_id, user_id, starter, menit_bermain, goal, assist, tembakan, tembakan_tepat,
                                  umpan, umpan_sukses, tekel, penyelamatan, kartu_kuning, kartu_merah, rating, catatan, dicatat_oleh)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($data);
            $id = (int) $pdo->lastInsertId();
        }
        k_sinkron_total_pemain((int) $v['user_id']);
        if ($lamaUid !== null && (int) $lamaUid !== (int) $v['user_id']) {
            k_sinkron_total_pemain((int) $lamaUid);
        }
        $pdo->commit();
        catat_aktivitas($lamaUid !== null ? 'ubah' : 'tambah', 'statistik_pertandingan', $id);
        flash_set('sukses', 'Statistik tersimpan; total gol/assist/kartu pemain ikut diperbarui.');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Statistik gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Statistik belum dapat disimpan. Coba lagi nanti.');
    }
    kembali_ke($kembali);
}

function tombol_input_statistik(): string
{
    return '<button type="button" class="tombol utama" ' . isi_dialog('dialogStatistik', ['id' => 0, 'starter' => '1'], 'Input statistik') . '>'
        . ikon('tambah') . 'Input statistik</button>';
}

/** Data grafik batang satu seri untuk peringkat 10 besar. */
function data_peringkat(array $skuad, string $kolom, string $nama, int $digit = 0): array
{
    $baris = array_values(array_filter($skuad, static fn($s) => (int) $s['main'] > 0 && (float) $s[$kolom] > 0));
    usort($baris, static fn($a, $b) => (float) $b[$kolom] <=> (float) $a[$kolom]);
    $baris = array_slice($baris, 0, 10);
    return [
        'label' => array_map(static fn($s) => (string) preg_replace('/^.*\s/u', '', trim((string) $s['nama_lengkap'])), $baris),
        'judul' => array_map(static fn($s) => (string) $s['nama_lengkap'], $baris),
        'seri'  => [['nama' => $nama, 'kelas' => 'seri-utama', 'nilai' => array_map(static fn($s) => round((float) $s[$kolom], $digit), $baris)]],
        'format' => 'angka', 'digit' => $digit,
    ];
}

/** Bagian tambahan halaman statistik untuk staf. */
function tampil_kelola_statistik(string $musim, array $skuad, string $kembali): void
{
    $form = k_form('form_stat');
    $galat = $form['galat'];
    $nilai = $form['nilai'] + ['starter' => '1'];
    try {
        $st = db()->prepare(
            "SELECT s.*, j.waktu_mulai, p.lawan, p.kandang, p.kompetisi, p.skor_klub, p.skor_lawan,
                    u.nama_lengkap, pm.nomor_punggung, pr.posisi, pr.posisi_kode, o.nama_lengkap AS pencatat
               FROM statistik_pertandingan s
               JOIN pertandingan p ON p.jadwal_id = s.jadwal_id
               JOIN jadwal j       ON j.id = s.jadwal_id
               JOIN users u        ON u.id = s.user_id
               LEFT JOIN pemain pm        ON pm.user_id = u.id
               LEFT JOIN profil_pemain pr ON pr.user_id = u.id
               LEFT JOIN users o          ON o.id = s.dicatat_oleh
              WHERE p.musim = ?
              ORDER BY j.waktu_mulai DESC, s.goal DESC, u.nama_lengkap"
        );
        $st->execute([$musim]);
        $catatan = $st->fetchAll();
        $laga = db()->query(
            "SELECT j.id, j.waktu_mulai, p.lawan, p.kandang, p.kompetisi, p.skor_klub, p.skor_lawan
               FROM jadwal j JOIN pertandingan p ON p.jadwal_id = j.id
              WHERE j.jenis = 'pertandingan' AND j.waktu_mulai <= NOW() ORDER BY j.waktu_mulai DESC LIMIT 40"
        )->fetchAll();
    } catch (PDOException $ex) {
        error_log('Kelola statistik gagal dimuat: ' . $ex->getMessage());
        echo '<p class="galat" role="alert">Catatan statistik belum dapat dimuat.</p>';
        return;
    }

    $grafik = [
        'gol'    => data_peringkat($skuad, 'goal', 'Gol'),
        'assist' => data_peringkat($skuad, 'assist', 'Assist'),
        'rating' => data_peringkat($skuad, 'rating', 'Rating', 1),
        'menit'  => data_peringkat($skuad, 'menit', 'Menit'),
    ];
    $opsiLaga = ['' => 'Pilih pertandingan...'];
    foreach ($laga as $l) {
        $opsiLaga[$l['id']] = tanggal_id((string) $l['waktu_mulai']) . ' · ' . ((int) $l['kandang'] ? 'vs ' : '@ ') . $l['lawan']
            . ($l['skor_klub'] !== null ? ' (' . $l['skor_klub'] . '–' . $l['skor_lawan'] . ')' : '') . ' · ' . $l['kompetisi'];
    }
    $kompetisi = array_unique(array_column($catatan, 'kompetisi'));
    ?>
            <section class="kartu" aria-labelledby="judulPeringkat">
                <div class="kepala-kartu baris-kepala">
                    <div><h2 id="judulPeringkat">Peringkat 10 besar</h2><p>Musim <?= e($musim) ?> · arahkan kursor ke batang untuk nilai lengkap</p></div>
                    <nav class="segmen" data-grup-ganti aria-label="Ukuran peringkat">
                        <?php $i = 0; foreach (['gol' => 'Gol', 'assist' => 'Assist', 'rating' => 'Rating', 'menit' => 'Menit'] as $k => $l): ?>
                            <button type="button" data-ganti="peringkat-<?= $k ?>"<?= $i++ === 0 ? ' class="aktif" aria-pressed="true"' : ' aria-pressed="false"' ?>><?= e($l) ?></button>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <?php $i = 0; foreach ($grafik as $k => $d): ?>
                    <div id="peringkat-<?= $k ?>"<?= $i++ === 0 ? '' : ' hidden' ?>>
                        <?php if (!$d['label']): ?>
                            <div class="kosong kecil">Belum ada data <?= e($k) ?> musim ini.</div>
                        <?php else: ?>
                            <div class="grafik-batang peringkat" tabindex="0" role="img" aria-label="Peringkat <?= e($k) ?> 10 pemain teratas"
                                 data-grafik-batang="<?= e((string) json_encode($d, JSON_UNESCAPED_UNICODE)) ?>"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulCatatanStat">
                <div class="kepala-tabel">
                    <h2 id="judulCatatanStat">Catatan per pertandingan</h2>
                    <?= alat_tabel('Cari pemain atau lawan...', [
                        'kompetisi' => ['label' => 'Kompetisi', 'opsi' => array_combine($kompetisi, $kompetisi) ?: []],
                        'lini'      => ['label' => 'Lini', 'opsi' => LABEL_LINI],
                    ]) ?>
                </div>
                <?php if (!$catatan): ?>
                    <div class="kosong-isi"><span class="ikon-kosong"><?= ikon('trofi') ?></span><b>Belum ada catatan statistik musim ini</b><span>Klik “Input statistik” setelah pertandingan selesai.</span></div>
                <?php else: ?>
                <div class="gulir-tabel">
                    <table class="tabel-data lebar aksi-tetap">
                        <thead><tr>
                            <?= th_urut('Tanggal', 'angka', 'desc') ?><?= th_urut('Pemain') ?><?= th_urut('Laga') ?><?= th_urut('Menit', 'angka') ?>
                            <?= th_urut('Gol', 'angka') ?><?= th_urut('Assist', 'angka') ?><?= th_urut('Tembakan', 'angka') ?><?= th_urut('Umpan', 'angka') ?>
                            <th scope="col">Kartu</th><?= th_urut('Rating', 'angka') ?><th scope="col"><span class="sr">Aksi</span></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($catatan as $c): ?>
                                <?php
                                $json = array_intersect_key($c, array_flip(KOLOM_STATISTIK)) + ['id' => (int) $c['id']];
                                $json['rating'] = $c['rating'] !== null ? rtrim(rtrim((string) $c['rating'], '0'), '.') : '';
                                $pctUmpan = (int) $c['umpan'] > 0 ? round(100 * (int) $c['umpan_sukses'] / (int) $c['umpan']) : null;
                                ?>
                                <tr data-kompetisi="<?= e((string) $c['kompetisi']) ?>" data-lini="<?= e((string) $c['posisi']) ?>">
                                    <td class="sel-tgl" data-nilai="<?= strtotime((string) $c['waktu_mulai']) ?>"><b><?= e(tanggal_id((string) $c['waktu_mulai'])) ?></b><small class="sub-sel"><?= e((string) $c['kompetisi']) ?></small></td>
                                    <td><a class="sel-pemain" href="statistik.php?pemain=<?= (int) $c['user_id'] ?>&amp;musim=<?= e(rawurlencode($musim)) ?>"><span><b><?= e((string) $c['nama_lengkap']) ?></b>
                                        <small class="sub-sel"><?= $c['nomor_punggung'] !== null ? '#' . (int) $c['nomor_punggung'] . ' · ' : '' ?><?= e((string) ($c['posisi_kode'] ?? '')) ?><?= (int) $c['starter'] ? '' : ' · cadangan' ?></small></span></a></td>
                                    <td><?= (int) $c['kandang'] ? 'vs ' : '@ ' ?><?= e((string) $c['lawan']) ?><small class="sub-sel">Skor <?= $c['skor_klub'] !== null ? (int) $c['skor_klub'] . '–' . (int) $c['skor_lawan'] : '-' ?></small></td>
                                    <td data-nilai="<?= (int) $c['menit_bermain'] ?>"><?= (int) $c['menit_bermain'] ?>'</td>
                                    <td data-nilai="<?= (int) $c['goal'] ?>"><b><?= (int) $c['goal'] ?></b></td>
                                    <td data-nilai="<?= (int) $c['assist'] ?>"><?= (int) $c['assist'] ?></td>
                                    <td data-nilai="<?= (int) $c['tembakan'] ?>"><?= (int) $c['tembakan_tepat'] ?>/<?= (int) $c['tembakan'] ?><small class="sub-sel">tepat/total</small></td>
                                    <td data-nilai="<?= (int) $c['umpan'] ?>"><?= (int) $c['umpan'] ?><small class="sub-sel"><?= $pctUmpan !== null ? $pctUmpan . '% sukses' : '-' ?></small></td>
                                    <td><span class="kartu-mini kuning" title="Kartu kuning"><?= (int) $c['kartu_kuning'] ?></span><span class="kartu-mini merah" title="Kartu merah"><?= (int) $c['kartu_merah'] ?></span></td>
                                    <td data-nilai="<?= (float) $c['rating'] ?>" title="<?= $c['pencatat'] ? 'Dicatat ' . e((string) $c['pencatat']) : '' ?>"><?= chip_rating($c['rating']) ?></td>
                                    <td><?= k_aksi_baris('dialogStatistik', $json, 'Ubah statistik ' . $c['nama_lengkap'], 'stat_hapus', (int) $c['id'], $kembali,
                                        'statistik ' . $c['nama_lengkap'] . ' vs ' . $c['lawan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= kaki_tabel() ?>
                <p class="catatan-tabel">Setiap perubahan otomatis memperbarui total gol, assist, dan kartu pemain di dashboard. Total gol pemain dalam satu laga tidak boleh melebihi skor klub.</p>
                <?php endif; ?>
            </section>

            <dialog class="modal lebar" id="dialogStatistik" aria-labelledby="judulDialogStat"<?= $galat ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($kembali) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="stat_simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('trofi') ?></span>
                        <div><h2 id="judulDialogStat" data-judul-dialog><?= (int) ($nilai['id'] ?? 0) ? 'Ubah statistik' : 'Input statistik' ?></h2>
                            <p>Satu catatan per pemain per pertandingan. Kosongkan rating bila tidak dinilai.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <?= k_medan('jadwal_id', 'Pertandingan', k_pilih('jadwal_id', $opsiLaga, $nilai), $galat) ?>
                            <?= k_medan('user_id', 'Pemain', k_pilih('user_id', k_opsi_pemain(), $nilai), $galat) ?>
                        </div>
                        <div class="baris-3">
                            <?= k_medan('starter', 'Peran', k_pilih('starter', ['1' => 'Starter', '0' => 'Cadangan'], $nilai), $galat) ?>
                            <?= k_medan('menit_bermain', 'Menit bermain', k_isian('menit_bermain', $nilai, 'number', 'min="0" max="130"'), $galat) ?>
                            <?= k_medan('rating', 'Rating (1–10)', k_isian('rating', $nilai, 'number', 'min="1" max="10" step="0.1"'), $galat, false) ?>
                        </div>
                        <div class="baris-3">
                            <?= k_medan('goal', 'Gol', k_isian('goal', $nilai, 'number', 'min="0" max="10"'), $galat) ?>
                            <?= k_medan('assist', 'Assist', k_isian('assist', $nilai, 'number', 'min="0" max="10"'), $galat) ?>
                            <?= k_medan('tekel', 'Tekel', k_isian('tekel', $nilai, 'number', 'min="0" max="40"'), $galat, false) ?>
                        </div>
                        <div class="baris-3">
                            <?= k_medan('tembakan', 'Tembakan', k_isian('tembakan', $nilai, 'number', 'min="0" max="40"'), $galat, false) ?>
                            <?= k_medan('tembakan_tepat', 'Tepat sasaran', k_isian('tembakan_tepat', $nilai, 'number', 'min="0" max="40"'), $galat, false) ?>
                            <?= k_medan('penyelamatan', 'Penyelamatan (kiper)', k_isian('penyelamatan', $nilai, 'number', 'min="0" max="40"'), $galat, false) ?>
                        </div>
                        <div class="baris-3">
                            <?= k_medan('umpan', 'Umpan', k_isian('umpan', $nilai, 'number', 'min="0" max="250"'), $galat, false) ?>
                            <?= k_medan('umpan_sukses', 'Umpan sukses', k_isian('umpan_sukses', $nilai, 'number', 'min="0" max="250"'), $galat, false) ?>
                            <div class="baris-2 rapat">
                                <?= k_medan('kartu_kuning', 'Kuning', k_isian('kartu_kuning', $nilai, 'number', 'min="0" max="2"'), $galat, false) ?>
                                <?= k_medan('kartu_merah', 'Merah', k_isian('kartu_merah', $nilai, 'number', 'min="0" max="1"'), $galat, false) ?>
                            </div>
                        </div>
                        <?= k_medan('catatan', 'Catatan', k_isian('catatan', $nilai, 'text', 'maxlength="255" placeholder="mis. Man of the match, cedera ringan menit 70"'), $galat, false) ?>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan</button>
                    </div>
                </form>
            </dialog>
    <?php
}
