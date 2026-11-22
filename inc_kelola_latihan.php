<?php
declare(strict_types=1);

// Kelola program latihan (staf): buat, ubah (peserta & sesi), hapus, tambah progres peserta.
// Dimuat oleh latihan.php.

require_once __DIR__ . '/inc_kelola_umum.php';

const MAKS_SESI = 5;

function proses_kelola_latihan(string $kembali): never
{
    k_mulai_post($kembali);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $pdo = db();
    try {
        // ---------- +10% progres satu peserta ----------
        if ($aksi === 'progres') {
            $pid = (int) ($_POST['program_id'] ?? 0);
            $uid = (int) ($_POST['user_id'] ?? 0);
            $tambah = k_int((string) ($_POST['tambah'] ?? '10'), -100, 100) ?? 10;
            $st = $pdo->prepare("UPDATE program_latihan_peserta
                                    SET progres_persen = LEAST(100, GREATEST(0, progres_persen + ?)),
                                        status = IF(LEAST(100, GREATEST(0, progres_persen + ?)) >= 100, 'selesai', IF(status = 'selesai', 'berjalan', status))
                                  WHERE program_id = ? AND user_id = ?");
            $st->execute([$tambah, $tambah, $pid, $uid]);
            if ($st->rowCount() === 1) {
                $q = $pdo->prepare('SELECT progres_persen FROM program_latihan_peserta WHERE program_id = ? AND user_id = ?');
                $q->execute([$pid, $uid]);
                $baru = (int) $q->fetchColumn();
                catat_aktivitas('progres', 'program_latihan', $pid, 'user ' . $uid . ' -> ' . $baru . '%');
                flash_set('sukses', 'Progres diperbarui menjadi ' . $baru . '%' . ($baru >= 100 ? ' — peserta tuntas.' : '.'));
            } else {
                flash_set('galat', 'Progres sudah 100% atau peserta tidak ditemukan.');
            }
            kembali_ke($kembali);
        }

        if ($aksi === 'program_hapus') {
            $st = $pdo->prepare('DELETE FROM program_latihan WHERE id = ?');
            $st->execute([(int) ($_POST['id'] ?? 0)]);
            catat_aktivitas('hapus', 'program_latihan', (int) $_POST['id']);
            flash_set($st->rowCount() ? 'sukses' : 'galat', $st->rowCount() ? 'Program latihan dihapus.' : 'Program tidak ditemukan.');
            kembali_ke($kembali);
        }
        if ($aksi !== 'program_simpan') {
            kembali_ke($kembali);
        }

        // ---------- Buat / ubah program ----------
        $id = (int) ($_POST['id'] ?? 0);
        $v = k_ambil(['judul', 'fokus', 'intensitas', 'tanggal_mulai', 'tanggal_selesai', 'status', 'deskripsi']);
        $g = [];
        if (mb_strlen($v['judul']) < 3 || mb_strlen($v['judul']) > 150) {
            $g['judul'] = 'Judul 3–150 karakter.';
        }
        if (!isset(LABEL_FOKUS[$v['fokus']])) {
            $g['fokus'] = 'Pilih fokus.';
        }
        if (!isset(TINGKAT_INTENSITAS[$v['intensitas']])) {
            $g['intensitas'] = 'Pilih intensitas.';
        }
        if (!isset(LABEL_STATUS_PROGRAM[$v['status']])) {
            $g['status'] = 'Pilih status.';
        }
        if (!k_tanggal($v['tanggal_mulai'])) {
            $g['tanggal_mulai'] = 'Tanggal mulai tidak valid.';
        }
        if (!k_tanggal($v['tanggal_selesai'])) {
            $g['tanggal_selesai'] = 'Tanggal selesai tidak valid.';
        } elseif (!isset($g['tanggal_mulai'])) {
            $hari = selisih_hari($v['tanggal_mulai'], $v['tanggal_selesai']);
            if ($hari < 0) {
                $g['tanggal_selesai'] = 'Tanggal selesai harus setelah tanggal mulai.';
            } elseif ($hari > 366) {
                $g['tanggal_selesai'] = 'Durasi program maksimal 1 tahun.';
            }
        }
        if (mb_strlen($v['deskripsi']) > 1000) {
            $g['deskripsi'] = 'Deskripsi maksimal 1000 karakter.';
        }

        // Peserta (kotak centang).
        $peserta = array_values(array_unique(array_map('intval', array_filter((array) ($_POST['peserta'] ?? []), 'is_scalar'))));
        foreach ($peserta as $uid) {
            if (!k_pemain_sah($uid)) {
                $g['peserta'] = 'Ada peserta yang tidak valid.';
            }
        }
        if (!$peserta) {
            $g['peserta'] = 'Pilih minimal satu pemain.';
        }

        // Sesi mingguan: baris yang judulnya terisi.
        $sesi = [];
        foreach (array_slice((array) ($_POST['sesi'] ?? []), 0, MAKS_SESI) as $s) {
            if (!is_array($s)) {
                continue;
            }
            $s = array_map(static fn($x) => trim((string) $x), $s + ['hari' => '', 'judul' => '', 'durasi' => '', 'intensitas' => '', 'keterangan' => '']);
            if ($s['judul'] === '') {
                continue;
            }
            $durasi = k_int($s['durasi'], 10, 240);
            if (!in_array($s['hari'], URUTAN_HARI, true) || mb_strlen($s['judul']) > 150 || $durasi === null
                || !isset(TINGKAT_INTENSITAS[$s['intensitas']]) || mb_strlen($s['keterangan']) > 255) {
                $g['sesi'] = 'Periksa sesi: hari, judul (maks. 150), durasi 10–240 menit, dan intensitas wajib diisi.';
                continue;
            }
            $sesi[] = [$s['hari'], $s['judul'], $durasi, $s['intensitas'], $s['keterangan'] ?: null];
        }
        if ($g) {
            k_gagal('form_program', $g, $v + ['id' => $id, 'peserta' => $peserta, 'sesi' => array_values((array) ($_POST['sesi'] ?? []))], $kembali);
        }

        $pdo->beginTransaction();
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE program_latihan SET judul = ?, fokus = ?, intensitas = ?, tanggal_mulai = ?, tanggal_selesai = ?, status = ?, deskripsi = ? WHERE id = ?');
            $st->execute([$v['judul'], $v['fokus'], $v['intensitas'], $v['tanggal_mulai'], $v['tanggal_selesai'], $v['status'], $v['deskripsi'] ?: null, $id]);
            $ada = $pdo->prepare('SELECT 1 FROM program_latihan WHERE id = ?');
            $ada->execute([$id]);
            if (!$ada->fetchColumn()) {
                $pdo->rollBack();
                flash_set('galat', 'Program tidak ditemukan.');
                kembali_ke($kembali);
            }
        } else {
            // Kode: PL-YYMM-NN (urut per bulan).
            $awalan = 'PL-' . hari_ini()->format('ym') . '-';
            $q = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(kode, 9) AS UNSIGNED)), 0) FROM program_latihan WHERE kode LIKE ?");
            $q->execute([$awalan . '%']);
            $kode = $awalan . str_pad((string) ((int) $q->fetchColumn() + 1), 2, '0', STR_PAD_LEFT);
            $pdo->prepare('INSERT INTO program_latihan (kode, judul, fokus, intensitas, tanggal_mulai, tanggal_selesai, status, deskripsi, dibuat_oleh)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$kode, $v['judul'], $v['fokus'], $v['intensitas'], $v['tanggal_mulai'], $v['tanggal_selesai'], $v['status'], $v['deskripsi'] ?: null, $_SESSION['user_id']]);
            $id = (int) $pdo->lastInsertId();
        }
        // Peserta: pertahankan progres yang sudah ada, tambah yang baru, buang yang tidak dicentang.
        $tanda = implode(',', array_fill(0, count($peserta), '?'));
        $pdo->prepare("DELETE FROM program_latihan_peserta WHERE program_id = ? AND user_id NOT IN ($tanda)")->execute([$id, ...$peserta]);
        $ins = $pdo->prepare('INSERT IGNORE INTO program_latihan_peserta (program_id, user_id, progres_persen, status) VALUES (?, ?, 0, \'berjalan\')');
        $pesertaBaru = [];
        foreach ($peserta as $uid) {
            $ins->execute([$id, $uid]);
            if ($ins->rowCount() === 1) {
                $pesertaBaru[] = $uid;
            }
        }
        kirim_notifikasi($pesertaBaru, 'performa', 'Program latihan baru: ' . $v['judul'],
            tanggal_id($v['tanggal_mulai']) . ' – ' . tanggal_id($v['tanggal_selesai']) . ' · ' . count($sesi) . ' sesi per minggu. Lihat jadwal sesinya di menu Program Latihan.', 'latihan.php');
        // Sesi: ganti seluruhnya.
        $pdo->prepare('DELETE FROM program_latihan_sesi WHERE program_id = ?')->execute([$id]);
        $insSesi = $pdo->prepare('INSERT INTO program_latihan_sesi (program_id, urutan, hari, judul, durasi_menit, intensitas, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($sesi as $i => $s) {
            $insSesi->execute([$id, $i + 1, ...$s]);
        }
        $pdo->commit();
        catat_aktivitas($_POST['id'] ?? '' ? 'ubah' : 'tambah', 'program_latihan', $id, $v['judul']);
        flash_set('sukses', 'Program "' . $v['judul'] . '" tersimpan dengan ' . count($peserta) . ' peserta dan ' . count($sesi) . ' sesi per minggu.');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Program latihan gagal disimpan: ' . $ex->getMessage());
        flash_set('galat', 'Program belum dapat disimpan. Coba lagi nanti.');
    }
    kembali_ke($kembali);
}

function tombol_program_baru(): string
{
    return '<button type="button" class="tombol utama" ' . isi_dialog('dialogProgram', [
        'id' => 0, 'fokus' => 'fisik', 'intensitas' => 'sedang', 'status' => 'aktif', 'peserta' => [],
        'tanggal_mulai' => hari_ini()->format('Y-m-d'), 'tanggal_selesai' => hari_ini()->modify('+4 weeks')->format('Y-m-d'),
        'sesi' => [['hari' => 'Senin', 'durasi' => 60, 'intensitas' => 'sedang']],
    ], 'Program baru') . '>' . ikon('tambah') . 'Program baru</button>';
}

/** Tombol ubah + hapus di kaki kartu program. */
function aksi_program(array $p, array $peserta, array $sesi, string $kembali): string
{
    $json = [
        'id' => (int) $p['id'], 'judul' => $p['judul'], 'fokus' => $p['fokus'], 'intensitas' => $p['intensitas'],
        'tanggal_mulai' => $p['tanggal_mulai'], 'tanggal_selesai' => $p['tanggal_selesai'], 'status' => $p['status'],
        'deskripsi' => $p['deskripsi'], 'peserta' => array_map(static fn($x) => (string) $x['user_id'], $peserta),
        'sesi' => array_map(static fn($s) => ['hari' => $s['hari'], 'judul' => $s['judul'], 'durasi' => (int) $s['durasi_menit'],
            'intensitas' => $s['intensitas'], 'keterangan' => $s['keterangan']], $sesi),
    ];
    return k_aksi_baris('dialogProgram', $json, 'Ubah program ' . $p['judul'], 'program_hapus', (int) $p['id'], $kembali,
        'program "' . $p['judul'] . '" beserta peserta dan sesinya');
}

/** Tombol +10% progres untuk satu peserta. */
function tombol_progres(int $programId, array $ps, string $kembali): string
{
    if ((int) $ps['progres_persen'] >= 100) {
        return '<span class="badge">Tuntas</span>';
    }
    return '<form method="post" action="' . e($kembali) . '" class="form-progres">'
        . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="progres">'
        . '<input type="hidden" name="program_id" value="' . $programId . '"><input type="hidden" name="user_id" value="' . (int) $ps['user_id'] . '">'
        . '<input type="hidden" name="tambah" value="10">'
        . '<button type="submit" class="tombol kecil" title="Tambah progres 10% untuk ' . e((string) $ps['nama_lengkap']) . '">+10%</button></form>';
}

/** Dialog buat / ubah program. */
function dialog_program(string $kembali): void
{
    $form = k_form('form_program');
    $galat = $form['galat'];
    $nilai = $form['nilai'] + ['fokus' => 'fisik', 'intensitas' => 'sedang', 'status' => 'aktif', 'peserta' => [], 'sesi' => []];
    $label = static fn(array $peta) => array_map(static fn($x) => $x[0], $peta);
    $opsiIntensitas = ['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi'];
    $pemain = daftar_pemain();
    ?>
            <dialog class="modal lebar" id="dialogProgram" aria-labelledby="judulDialogProgram"<?= $galat ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="<?= e($kembali) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="program_simpan">
                    <input type="hidden" name="id" value="<?= (int) ($nilai['id'] ?? 0) ?>">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('beban') ?></span>
                        <div><h2 id="judulDialogProgram" data-judul-dialog><?= (int) ($nilai['id'] ?? 0) ? 'Ubah program' : 'Program baru' ?></h2>
                            <p>Peserta yang tetap dicentang mempertahankan progresnya. Kode program dibuat otomatis.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <?= k_medan('judul', 'Judul program', k_isian('judul', $nilai, 'text', 'maxlength="150" placeholder="mis. Rehabilitasi hamstring fase 2"'), $galat) ?>
                        <div class="baris-3">
                            <?= k_medan('fokus', 'Fokus', k_pilih('fokus', $label(LABEL_FOKUS), $nilai), $galat) ?>
                            <?= k_medan('intensitas', 'Intensitas', k_pilih('intensitas', $opsiIntensitas, $nilai), $galat) ?>
                            <?= k_medan('status', 'Status', k_pilih('status', $label(LABEL_STATUS_PROGRAM), $nilai), $galat) ?>
                        </div>
                        <div class="baris-2">
                            <?= k_medan('tanggal_mulai', 'Mulai', k_isian('tanggal_mulai', $nilai, 'date'), $galat) ?>
                            <?= k_medan('tanggal_selesai', 'Selesai', k_isian('tanggal_selesai', $nilai, 'date'), $galat) ?>
                        </div>
                        <?= k_medan('deskripsi', 'Deskripsi', '<textarea id="k_deskripsi" name="deskripsi" maxlength="1000" placeholder="Tujuan, metode, dan target program">' . e((string) ($nilai['deskripsi'] ?? '')) . '</textarea>', $galat, false) ?>

                        <h3 class="pemisah-bagian">Peserta</h3>
                        <div class="medan<?= isset($galat['peserta']) ? ' salah' : '' ?>">
                            <div class="pilih-peserta" role="group" aria-label="Pilih peserta program">
                                <?php foreach ($pemain as $p): ?>
                                    <label class="cek-peserta">
                                        <input type="checkbox" name="peserta[]" value="<?= (int) $p['id'] ?>"<?= in_array((string) $p['id'], array_map('strval', (array) $nilai['peserta']), true) ? ' checked' : '' ?>>
                                        <span><?= $p['nomor_punggung'] !== null ? '#' . (int) $p['nomor_punggung'] . ' ' : '' ?><?= e((string) $p['nama_lengkap']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($galat['peserta'])): ?><p class="pesan-salah"><?= e($galat['peserta']) ?></p><?php endif; ?>
                        </div>

                        <h3 class="pemisah-bagian">Sesi per minggu <small class="redup">(maks. <?= MAKS_SESI ?>, baris tanpa judul diabaikan)</small></h3>
                        <div class="medan<?= isset($galat['sesi']) ? ' salah' : '' ?>">
                            <div class="tabel-sesi">
                                <span>Hari</span><span>Judul sesi</span><span>Menit</span><span>Intensitas</span><span>Keterangan</span>
                                <?php for ($i = 0; $i < MAKS_SESI; $i++): $s = (array) ($nilai['sesi'][$i] ?? []); ?>
                                    <select name="sesi[<?= $i ?>][hari]" aria-label="Hari sesi <?= $i + 1 ?>">
                                        <?php foreach (URUTAN_HARI as $h): ?><option<?= ($s['hari'] ?? 'Senin') === $h ? ' selected' : '' ?>><?= $h ?></option><?php endforeach; ?>
                                    </select>
                                    <input name="sesi[<?= $i ?>][judul]" maxlength="150" value="<?= e((string) ($s['judul'] ?? '')) ?>" aria-label="Judul sesi <?= $i + 1 ?>" placeholder="<?= $i === 0 ? 'mis. Sprint berulang 6x30 m' : '' ?>">
                                    <input type="number" name="sesi[<?= $i ?>][durasi]" min="10" max="240" value="<?= e((string) ($s['durasi'] ?? '60')) ?>" aria-label="Durasi sesi <?= $i + 1 ?> (menit)">
                                    <select name="sesi[<?= $i ?>][intensitas]" aria-label="Intensitas sesi <?= $i + 1 ?>">
                                        <?php foreach ($opsiIntensitas as $k => $l): ?><option value="<?= $k ?>"<?= ($s['intensitas'] ?? 'sedang') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                                    </select>
                                    <input name="sesi[<?= $i ?>][keterangan]" maxlength="255" value="<?= e((string) ($s['keterangan'] ?? '')) ?>" aria-label="Keterangan sesi <?= $i + 1 ?>">
                                <?php endfor; ?>
                            </div>
                            <?php if (isset($galat['sesi'])): ?><p class="pesan-salah"><?= e($galat['sesi']) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('tanda') ?>Simpan program</button>
                    </div>
                </form>
            </dialog>
    <?php
}
