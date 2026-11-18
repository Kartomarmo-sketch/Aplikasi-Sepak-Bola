<?php
declare(strict_types=1);

// Kelola cedera (staf medis/pelatih & admin): catat, ubah, tandai pulih, hapus, tambah penanganan.
// Dimuat oleh cedera.php.

require_once __DIR__ . '/inc_kelola_umum.php';

const KOLOM_CEDERA = ['user_id', 'jenis_cedera', 'bagian_tubuh', 'tingkat', 'penyebab', 'tanggal_cedera', 'estimasi_pulih',
                      'tanggal_pulih', 'status', 'diagnosis', 'penanganan_awal'];

function proses_kelola_cedera(string $kembali): never
{
    k_mulai_post($kembali);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $pdo = db();
    $hariIni = hari_ini()->format('Y-m-d');
    try {
        // ---------- Hapus ----------
        if ($aksi === 'cedera_hapus') {
            $st = $pdo->prepare('SELECT user_id FROM cedera WHERE id = ?');
            $st->execute([(int) ($_POST['id'] ?? 0)]);
            $uid = $st->fetchColumn();
            if ($uid !== false) {
                $pdo->prepare('DELETE FROM cedera WHERE id = ?')->execute([(int) $_POST['id']]); // perawatan ikut terhapus (CASCADE)
                k_sinkron_status_cedera((int) $uid);
                catat_aktivitas('hapus', 'cedera', (int) $_POST['id']);
                flash_set('sukses', 'Catatan cedera dihapus.');
            } else {
                flash_set('galat', 'Catatan cedera tidak ditemukan.');
            }
            kembali_ke($kembali);
        }

        // ---------- Tandai pulih hari ini ----------
        if ($aksi === 'cedera_pulih') {
            $st = $pdo->prepare("UPDATE cedera SET status = 'pulih', tanggal_pulih = ? WHERE id = ? AND status <> 'pulih' AND tanggal_cedera <= ?");
            $st->execute([$hariIni, (int) ($_POST['id'] ?? 0), $hariIni]);
            if ($st->rowCount() === 1) {
                $u = $pdo->prepare('SELECT user_id FROM cedera WHERE id = ?');
                $u->execute([(int) $_POST['id']]);
                $uidPulih = (int) $u->fetchColumn();
                k_sinkron_status_cedera($uidPulih);
                kirim_notifikasi($uidPulih, 'medis', 'Anda dinyatakan pulih',
                    'Tim medis menandai cedera Anda pulih per ' . tanggal_id($hariIni) . '. Anda kembali tersedia untuk latihan dan pertandingan.', 'cedera.php');
                catat_aktivitas('pulih', 'cedera', (int) $_POST['id']);
                flash_set('sukses', 'Pemain dinyatakan pulih dan kembali tersedia.');
            } else {
                flash_set('galat', 'Cedera tidak ditemukan atau sudah pulih.');
            }
            kembali_ke($kembali);
        }

        // ---------- Tambah penanganan ----------
        if ($aksi === 'rawat_tambah') {
            $v = k_ambil(['cedera_id', 'tanggal', 'tindakan', 'obat', 'catatan']);
            $st = $pdo->prepare('SELECT tanggal_cedera FROM cedera WHERE id = ?');
            $st->execute([(int) $v['cedera_id']]);
            $mulai = $st->fetchColumn();
            $g = match (true) {
                $mulai === false => 'Cedera tidak ditemukan.',
                !k_tanggal($v['tanggal']) || $v['tanggal'] > $hariIni => 'Tanggal tindakan tidak valid atau di masa depan.',
                $v['tanggal'] < $mulai => 'Tanggal tindakan sebelum tanggal cedera.',
                mb_strlen($v['tindakan']) < 3 || mb_strlen($v['tindakan']) > 150 => 'Tindakan 3–150 karakter.',
                mb_strlen($v['obat']) > 150 || mb_strlen($v['catatan']) > 1000 => 'Obat maks. 150, catatan maks. 1000 karakter.',
                default => '',
            };
            if ($g !== '') {
                flash_set('galat', $g);
                kembali_ke($kembali);
            }
            $pdo->prepare('INSERT INTO perawatan_cedera (cedera_id, tanggal, tindakan, obat, catatan, petugas_id) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int) $v['cedera_id'], $v['tanggal'], $v['tindakan'], $v['obat'] ?: null, $v['catatan'] ?: null, $_SESSION['user_id']]);
            catat_aktivitas('tambah', 'perawatan_cedera', (int) $pdo->lastInsertId(), $v['tindakan']);
            flash_set('sukses', 'Tindakan penanganan dicatat.');
            kembali_ke($kembali);
        }

        if ($aksi !== 'cedera_simpan') {
            kembali_ke($kembali);
        }

        // ---------- Catat / ubah ----------
        $id = (int) ($_POST['id'] ?? 0);
        $v = k_ambil(KOLOM_CEDERA);
        $g = [];
        if (!k_pemain_sah((int) $v['user_id'])) {
            $g['user_id'] = 'Pilih pemain aktif.';
        }
        if (mb_strlen($v['jenis_cedera']) < 3 || mb_strlen($v['jenis_cedera']) > 100) {
            $g['jenis_cedera'] = 'Jenis cedera 3–100 karakter.';
        }
        if (mb_strlen($v['bagian_tubuh']) < 2 || mb_strlen($v['bagian_tubuh']) > 60) {
            $g['bagian_tubuh'] = 'Bagian tubuh 2–60 karakter.';
        }
        if (!isset(LABEL_TINGKAT[$v['tingkat']])) {
            $g['tingkat'] = 'Pilih tingkat keparahan.';
        }
        if (!isset(LABEL_PENYEBAB[$v['penyebab']])) {
            $g['penyebab'] = 'Pilih penyebab.';
        }
        if (!isset(LABEL_STATUS_CEDERA[$v['status']])) {
            $g['status'] = 'Pilih status.';
        }
        if (!k_tanggal($v['tanggal_cedera']) || $v['tanggal_cedera'] > $hariIni) {
            $g['tanggal_cedera'] = 'Tanggal cedera tidak valid atau di masa depan.';
        }
        if ($v['estimasi_pulih'] !== '' && (!k_tanggal($v['estimasi_pulih']) || (!isset($g['tanggal_cedera']) && $v['estimasi_pulih'] < $v['tanggal_cedera']))) {
            $g['estimasi_pulih'] = 'Perkiraan pulih harus setelah tanggal cedera.';
        }
        if ($v['status'] === 'pulih') {
            if (!k_tanggal($v['tanggal_pulih']) || $v['tanggal_pulih'] > $hariIni || (!isset($g['tanggal_cedera']) && $v['tanggal_pulih'] < $v['tanggal_cedera'])) {
                $g['tanggal_pulih'] = 'Isi tanggal pulih (antara tanggal cedera dan hari ini).';
            }
        } else {
            $v['tanggal_pulih'] = ''; // belum pulih -> tidak ada tanggal pulih
        }
        if (mb_strlen($v['diagnosis']) > 1000) {
            $g['diagnosis'] = 'Diagnosis maksimal 1000 karakter.';
        }
        if (mb_strlen($v['penanganan_awal']) > 150) {
            $g['penanganan_awal'] = 'Maksimal 150 karakter.';
        }
        $lamaUid = null;
        if ($id > 0) {
            $st = $pdo->prepare('SELECT user_id FROM cedera WHERE id = ?');
            $st->execute([$id]);
            $lamaUid = $st->fetchColumn();
            if ($lamaUid === false) {
                flash_set('galat', 'Catatan cedera tidak ditemukan.');
                kembali_ke($kembali);
            }
        }
        if ($g) {
            k_gagal('form_cedera', $g, $v + ['id' => $id], $kembali);
        }

        $kosong = static fn(string $s) => $s === '' ? null : $s;
        $data = [(int) $v['user_id'], $v['jenis_cedera'], $v['bagian_tubuh'], $v['tingkat'], $v['penyebab'], $v['tanggal_cedera'],
                 $kosong($v['estimasi_pulih']), $kosong($v['tanggal_pulih']), $v['status'], $kosong($v['diagnosis'])];
        $pdo->beginTransaction();
        if ($id > 0) {
            $pdo->prepare('UPDATE cedera SET user_id = ?, jenis_cedera = ?, bagian_tubuh = ?, tingkat = ?, penyebab = ?, tanggal_cedera = ?,
                                  estimasi_pulih = ?, tanggal_pulih = ?, status = ?, diagnosis = ? WHERE id = ?')->execute([...$data, $id]);
        } else {
            $pdo->prepare('INSERT INTO cedera (user_id, jenis_cedera, bagian_tubuh, tingkat, penyebab, tanggal_cedera, estimasi_pulih,
                                  tanggal_pulih, status, diagnosis, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$data, $_SESSION['user_id']]);
            $id = (int) $pdo->lastInsertId();
            kirim_notifikasi((int) $v['user_id'], 'medis', 'Cedera dicatat: ' . $v['jenis_cedera'],
                $v['bagian_tubuh'] . ($v['estimasi_pulih'] !== '' ? ' · perkiraan kembali bermain ' . tanggal_id($v['estimasi_pulih']) : '') . '. Ikuti jadwal penanganan dari tim medis.', 'cedera.php');
            if ($v['penanganan_awal'] !== '') {
                $pdo->prepare('INSERT INTO perawatan_cedera (cedera_id, tanggal, tindakan, petugas_id) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $v['tanggal_cedera'], $v['penanganan_awal'], $_SESSION['user_id']]);
            }
        }
        k_sinkron_status_cedera((int) $v['user_id']);
        if ($lamaUid !== null && (int) $lamaUid !== (int) $v['user_id']) {
            k_sinkron_status_cedera((int) $lamaUid);
        }
        $pdo->commit();
        catat_aktivitas($lamaUid !== null ? 'ubah' : 'tambah', 'cedera', $id, $v['jenis_cedera']);
        flash_set('sukses', $lamaUid !== null ? 'Catatan cedera diperbarui.' : 'Cedera dicatat; status pemain diperbarui.');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Cedera gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Data cedera belum dapat disimpan. Coba lagi nanti.');
    }
    kembali_ke($kembali);
}

function tombol_catat_cedera(): string
{
    return '<button type="button" class="tombol utama" ' . isi_dialog('dialogCedera', [
        'id' => 0, 'tingkat' => 'ringan', 'penyebab' => 'latihan', 'status' => 'dirawat', 'tanggal_cedera' => hari_ini()->format('Y-m-d'),
    ], 'Catat cedera') . '>' . ikon('tambah') . 'Catat cedera</button>';
}

/** Tombol ubah & hapus untuk satu baris cedera. */
function aksi_cedera(array $c, string $kembali): string
{
    $json = array_intersect_key($c, array_flip(KOLOM_CEDERA)) + ['id' => (int) $c['id'], 'penanganan_awal' => ''];
    return k_aksi_baris('dialogCedera', $json, 'Ubah cedera ' . $c['nama_lengkap'], 'cedera_hapus', (int) $c['id'], $kembali,
        'catatan cedera ' . $c['jenis_cedera'] . ' (' . $c['nama_lengkap'] . ') beserta riwayat penanganannya');
}

/** Formulir tambah penanganan + tombol tandai pulih di dialog detail cedera. */
function form_penanganan(array $c, string $kembali): string
{
    $h = '<form method="post" action="' . e($kembali) . '" class="form-rawat">'
        . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="rawat_tambah">'
        . '<input type="hidden" name="cedera_id" value="' . (int) $c['id'] . '">'
        . '<h3 class="pemisah-bagian">Tambah penanganan</h3>'
        . '<div class="baris-3">'
        . '<div class="medan"><label for="rt-' . (int) $c['id'] . '">Tanggal</label><div class="kolom"><input type="date" id="rt-' . (int) $c['id'] . '" name="tanggal" value="' . e(hari_ini()->format('Y-m-d')) . '" min="' . e((string) $c['tanggal_cedera']) . '" max="' . e(hari_ini()->format('Y-m-d')) . '" required></div></div>'
        . '<div class="medan"><label for="rk-' . (int) $c['id'] . '">Tindakan</label><div class="kolom"><input id="rk-' . (int) $c['id'] . '" name="tindakan" maxlength="150" required placeholder="mis. Fisioterapi (ultrasound)"></div></div>'
        . '<div class="medan"><label for="ro-' . (int) $c['id'] . '">Obat <small>(opsional)</small></label><div class="kolom"><input id="ro-' . (int) $c['id'] . '" name="obat" maxlength="150"></div></div>'
        . '</div>'
        . '<div class="medan"><label for="rc-' . (int) $c['id'] . '">Catatan <small>(opsional)</small></label><div class="kolom"><input id="rc-' . (int) $c['id'] . '" name="catatan" maxlength="1000" placeholder="Perkembangan, respons nyeri, rencana berikutnya"></div></div>'
        . '<div class="aksi-kanan"><button type="submit" class="tombol kecil utama">' . ikon('tambah') . 'Simpan tindakan</button></div></form>';
    if ($c['status'] !== 'pulih') {
        $h .= '<form method="post" action="' . e($kembali) . '" class="form-pulih" data-konfirmasi="Nyatakan ' . e((string) $c['nama_lengkap']) . ' pulih hari ini dan tersedia kembali?">'
            . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="cedera_pulih">'
            . '<input type="hidden" name="id" value="' . (int) $c['id'] . '">'
            . '<button type="submit" class="tombol kecil">' . ikon('tanda') . 'Tandai pulih hari ini</button></form>';
    }
    return $h;
}

/** Dialog catat / ubah cedera. */
function dialog_cedera(string $kembali, ?int $pemainTerpilih): void
{
    $form = k_form('form_cedera');
    $galat = $form['galat'];
    $nilai = $form['nilai'] + ['user_id' => $pemainTerpilih ?? '', 'tingkat' => 'ringan', 'penyebab' => 'latihan', 'status' => 'dirawat'];
    $label = static fn(array $peta) => array_map(static fn($x) => $x[0], $peta);
    ?>
            <dialog class="modal lebar" id="dialogCedera" aria-labelledby="judulDialogCedera"<?= $galat ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($kembali) ?>" novalidate data-form-cedera>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="cedera_simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul warna-netral"><?= ikon('jantung') ?></span>
                        <div><h2 id="judulDialogCedera" data-judul-dialog><?= (int) ($nilai['id'] ?? 0) ? 'Ubah cedera' : 'Catat cedera' ?></h2>
                            <p>Status pemain otomatis menjadi <b>Cedera</b> selama ada cedera yang belum pulih.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="baris-2">
                            <?= k_medan('user_id', 'Pemain', k_pilih('user_id', k_opsi_pemain(), $nilai), $galat) ?>
                            <?= k_medan('jenis_cedera', 'Jenis cedera', k_isian('jenis_cedera', $nilai, 'text', 'maxlength="100" list="daftarCedera" placeholder="mis. Robek otot hamstring grade 1"'), $galat) ?>
                        </div>
                        <datalist id="daftarCedera">
                            <option value="Robek otot hamstring grade 1"><option value="Keseleo pergelangan kaki"><option value="Cedera ligamen lutut (MCL)">
                            <option value="Kontusio paha"><option value="Kram otot betis"><option value="Gegar otak ringan"><option value="Nyeri pinggang bawah">
                        </datalist>
                        <div class="baris-3">
                            <?= k_medan('bagian_tubuh', 'Bagian tubuh', k_isian('bagian_tubuh', $nilai, 'text', 'maxlength="60" placeholder="mis. Paha belakang kanan"'), $galat) ?>
                            <?= k_medan('tingkat', 'Keparahan', k_pilih('tingkat', $label(LABEL_TINGKAT), $nilai), $galat) ?>
                            <?= k_medan('penyebab', 'Terjadi saat', k_pilih('penyebab', LABEL_PENYEBAB, $nilai), $galat) ?>
                        </div>
                        <div class="baris-3">
                            <?= k_medan('tanggal_cedera', 'Tanggal cedera', k_isian('tanggal_cedera', $nilai, 'date', 'max="' . e(hari_ini()->format('Y-m-d')) . '"'), $galat) ?>
                            <?= k_medan('estimasi_pulih', 'Perkiraan kembali', k_isian('estimasi_pulih', $nilai, 'date'), $galat, false) ?>
                            <?= k_medan('status', 'Status', k_pilih('status', $label(LABEL_STATUS_CEDERA), $nilai), $galat) ?>
                        </div>
                        <div data-hanya-pulih>
                            <?= k_medan('tanggal_pulih', 'Tanggal pulih', k_isian('tanggal_pulih', $nilai, 'date', 'max="' . e(hari_ini()->format('Y-m-d')) . '"'), $galat, true, 'Wajib bila status Pulih.') ?>
                        </div>
                        <?= k_medan('diagnosis', 'Diagnosis', '<textarea id="k_diagnosis" name="diagnosis" maxlength="1000" placeholder="Hasil pemeriksaan, MRI/rontgen, rekomendasi dokter">' . e((string) ($nilai['diagnosis'] ?? '')) . '</textarea>', $galat, false) ?>
                        <div data-hanya-baru>
                            <?= k_medan('penanganan_awal', 'Penanganan awal', k_isian('penanganan_awal', $nilai, 'text', 'maxlength="150" placeholder="mis. RICE, kompres es, istirahat 3 hari"'), $galat, false, 'Dicatat sebagai tindakan pertama di riwayat penanganan.') ?>
                        </div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan</button>
                    </div>
                </form>
            </dialog>
    <?php
}
