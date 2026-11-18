<?php
declare(strict_types=1);

// Pengajuan karyawan.
//   pengajuan.php          -> Pengajuan Cuti (cuti tahunan & sakit) + saldo cuti
//   pengajuan.php?hal=izin -> Pengajuan Izin (izin, perpanjangan kontrak, klaim medis, perubahan data, lainnya)
// Admin/HR juga mendapat bagian "Perlu ditinjau" untuk menyetujui / menolak pengajuan orang lain.

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

const GRUP_PENGAJUAN = [
    'cuti' => ['cuti' => 'Cuti tahunan', 'sakit' => 'Sakit'],
    'izin' => [
        'izin' => 'Izin', 'perpanjangan_kontrak' => 'Perpanjangan kontrak', 'klaim_medis' => 'Klaim biaya medis',
        'perubahan_data' => 'Perubahan data', 'lainnya' => 'Lainnya',
    ],
];
// Jenis yang wajib memakai rentang tanggal.
const JENIS_BERTANGGAL = ['cuti', 'sakit', 'izin'];
// perubahan_data diajukan dari halaman Profil, tidak dari formulir ini.
const JENIS_FORM_IZIN = ['izin', 'perpanjangan_kontrak', 'klaim_medis', 'lainnya'];
const LABEL_STATUS_PENGAJUAN = [
    'menunggu' => ['Menunggu', 'kuning'], 'disetujui' => ['Disetujui', 'hijau'],
    'ditolak' => ['Ditolak', 'merah'], 'dibatalkan' => ['Dibatalkan', 'netral'],
];
const MAKS_MENUNGGU = 5;

$hal   = ($_GET['hal'] ?? '') === 'izin' ? 'izin' : 'cuti';
$uid   = (int) $_SESSION['user_id'];
$admin = ($_SESSION['role'] ?? '') === 'admin';
$jenisGrup = array_keys(GRUP_PENGAJUAN[$hal]);
$jenisForm = $hal === 'cuti' ? ['cuti', 'sakit'] : JENIS_FORM_IZIN;
$alamat = 'pengajuan.php' . ($hal === 'izin' ? '?hal=izin' : '');

function kembali(string $alamat): never
{
    header('Location: ' . $alamat, true, 303);
    exit;
}

function kode_pengajuan(int $id): string
{
    return 'REQ-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/** Jumlah hari kalender (inklusif). */
function jumlah_hari(?string $mulai, ?string $selesai): int
{
    return ($mulai && $selesai) ? selisih_hari($mulai, $selesai) + 1 : 0;
}

function tanggal_valid(string $t): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}

// =====================================================================
// Proses POST (PRG)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash_set('galat', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        kembali($alamat);
    }
    $aksi = (string) ($_POST['aksi'] ?? '');

    try {
        // ---------- Ajukan ----------
        if ($aksi === 'ajukan') {
            $jenis   = (string) ($_POST['jenis'] ?? '');
            $mulai   = trim((string) ($_POST['tanggal_mulai'] ?? ''));
            $selesai = trim((string) ($_POST['tanggal_selesai'] ?? ''));
            $alasan  = trim((string) ($_POST['alasan'] ?? ''));
            $galat   = [];
            $hariIni = hari_ini()->format('Y-m-d');

            if (!in_array($jenis, $jenisForm, true)) {
                $galat['jenis'] = 'Pilih jenis pengajuan.';
            }
            $bertanggal = in_array($jenis, JENIS_BERTANGGAL, true);
            if ($mulai === '' && $selesai === '' && !$bertanggal) {
                $mulai = $selesai = null;
            } else {
                if (!tanggal_valid($mulai)) {
                    $galat['tanggal_mulai'] = 'Tanggal mulai tidak valid.';
                }
                if ($selesai === '') {
                    $selesai = $mulai;
                }
                if (!tanggal_valid($selesai)) {
                    $galat['tanggal_selesai'] = 'Tanggal selesai tidak valid.';
                } elseif (!isset($galat['tanggal_mulai'])) {
                    $hari = jumlah_hari($mulai, $selesai);
                    if ($hari < 1) {
                        $galat['tanggal_selesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                    } elseif ($hari > 30) {
                        $galat['tanggal_selesai'] = 'Maksimal 30 hari per pengajuan.';
                    } elseif ($jenis === 'sakit') {
                        if (selisih_hari($mulai, $hariIni) > 14) {
                            $galat['tanggal_mulai'] = 'Laporan sakit paling lambat 14 hari setelah tanggal mulai sakit.';
                        } elseif (selisih_hari($hariIni, $mulai) > 7) {
                            $galat['tanggal_mulai'] = 'Laporan sakit tidak dapat diajukan lebih dari 7 hari ke depan.';
                        }
                    } elseif ($mulai < $hariIni) {
                        $galat['tanggal_mulai'] = 'Tanggal mulai tidak boleh di masa lalu.';
                    }
                }
            }
            $panjang = mb_strlen($alasan);
            if ($panjang < 10 || $panjang > 500) {
                $galat['alasan'] = 'Alasan wajib diisi, 10–500 karakter.';
            }

            if (!$galat) {
                $stmt = db()->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = :u AND status = 'menunggu'");
                $stmt->execute([':u' => $uid]);
                if ((int) $stmt->fetchColumn() >= MAKS_MENUNGGU) {
                    $galat['umum'] = 'Anda masih memiliki ' . MAKS_MENUNGGU . ' pengajuan yang menunggu. Tunggu keputusan HR terlebih dahulu.';
                }
            }
            if (!$galat && $mulai !== null && $bertanggal) {
                // Tidak boleh tumpang tindih dengan cuti/sakit/izin lain yang masih berlaku.
                $stmt = db()->prepare(
                    "SELECT id, jenis, tanggal_mulai, tanggal_selesai FROM pengajuan
                      WHERE user_id = :u AND status IN ('menunggu','disetujui') AND jenis IN ('cuti','sakit','izin')
                        AND tanggal_mulai <= :selesai AND tanggal_selesai >= :mulai LIMIT 1"
                );
                $stmt->execute([':u' => $uid, ':mulai' => $mulai, ':selesai' => $selesai]);
                if ($bentrok = $stmt->fetch()) {
                    $galat['tanggal_mulai'] = 'Tanggal bertabrakan dengan ' . kode_pengajuan((int) $bentrok['id']) . ' ('
                        . tanggal_id((string) $bentrok['tanggal_mulai']) . ' – ' . tanggal_id((string) $bentrok['tanggal_selesai']) . ').';
                }
            }
            if (!$galat && $jenis === 'cuti') {
                $saldo = saldo_cuti($uid);
                $hariCuti = jumlah_hari($mulai, $selesai);
                $sisa = substr((string) $mulai, 0, 4) === (string) $saldo['tahun'] ? $saldo['sisa'] : $saldo['hak'];
                if ($hariCuti > $sisa) {
                    $galat['tanggal_selesai'] = 'Sisa cuti Anda ' . $sisa . ' hari, sedangkan pengajuan ini ' . $hariCuti . ' hari.';
                }
            }

            if ($galat) {
                $_SESSION['form_pengajuan'] = ['galat' => $galat, 'nilai' => compact('jenis', 'mulai', 'selesai', 'alasan'), 'hal' => $hal];
                flash_set('galat', $galat['umum'] ?? 'Periksa kembali isian pengajuan.');
                kembali($alamat);
            }

            $judulJenis = GRUP_PENGAJUAN[$hal][$jenis];
            $stmt = db()->prepare(
                'INSERT INTO pengajuan (user_id, jenis, judul, isi, tanggal_mulai, tanggal_selesai, status)
                 VALUES (:u, :j, :judul, :isi, :m, :s, \'menunggu\')'
            );
            $stmt->execute([':u' => $uid, ':j' => $jenis, ':judul' => $judulJenis, ':isi' => $alasan, ':m' => $mulai, ':s' => $selesai]);
            $idBaru = (int) db()->lastInsertId();

            // Beri tahu admin/HR yang aktif.
            $nama = (string) $_SESSION['nama_lengkap'];
            kirim_notifikasi(id_admin_aktif($uid), 'pengajuan', 'Pengajuan baru: ' . $judulJenis,
                $nama . ' mengajukan ' . mb_strtolower($judulJenis) . ($mulai ? ' ' . tanggal_id($mulai) . ($selesai !== $mulai ? ' – ' . tanggal_id((string) $selesai) : '') : '') . '. Menunggu persetujuan Anda.',
                $alamat);
            catat_aktivitas('ajukan', 'pengajuan', $idBaru, $jenis);

            // Peringatan bila bentrok dengan laga (pemain).
            $pesan = kode_pengajuan($idBaru) . ' terkirim dan menunggu persetujuan HR.';
            if ($mulai !== null && ($_SESSION['role'] ?? '') === 'pemain' && in_array($jenis, ['cuti', 'izin'], true)) {
                $stmt = db()->prepare(
                    "SELECT judul, waktu_mulai FROM jadwal WHERE jenis = 'pertandingan'
                      AND DATE(waktu_mulai) BETWEEN :m AND :s ORDER BY waktu_mulai LIMIT 1"
                );
                $stmt->execute([':m' => $mulai, ':s' => $selesai]);
                if ($laga = $stmt->fetch()) {
                    $pesan .= ' Perhatian: tanggal ini bentrok dengan laga ' . $laga['judul'] . ' (' . tanggal_id((string) $laga['waktu_mulai']) . ').';
                }
            }
            flash_set('sukses', $pesan);
            kembali($alamat);
        }

        // ---------- Batalkan milik sendiri ----------
        if ($aksi === 'batal') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare("UPDATE pengajuan SET status = 'dibatalkan' WHERE id = :id AND user_id = :u AND status = 'menunggu'");
            $stmt->execute([':id' => $id, ':u' => $uid]);
            if ($stmt->rowCount() === 1) {
                catat_aktivitas('batal', 'pengajuan', $id);
                flash_set('sukses', kode_pengajuan($id) . ' dibatalkan.');
            } else {
                flash_set('galat', 'Pengajuan tidak dapat dibatalkan (mungkin sudah ditinjau).');
            }
            kembali($alamat);
        }

        // ---------- Tinjau (admin/HR) ----------
        if ($aksi === 'tinjau') {
            if (!$admin) {
                http_response_code(403);
                exit('Akses ditolak.');
            }
            $id = (int) ($_POST['id'] ?? 0);
            $keputusan = (string) ($_POST['keputusan'] ?? '');
            $catatan = trim((string) ($_POST['catatan'] ?? ''));
            if (!in_array($keputusan, ['disetujui', 'ditolak'], true)) {
                flash_set('galat', 'Keputusan tidak valid.');
                kembali($alamat);
            }
            if ($keputusan === 'ditolak' && mb_strlen($catatan) < 5) {
                flash_set('galat', 'Tuliskan alasan penolakan (minimal 5 karakter) agar karyawan memahami keputusannya.');
                kembali($alamat);
            }
            $catatan = $catatan === '' ? null : mb_substr($catatan, 0, 255);
            // Tidak boleh meninjau pengajuan sendiri.
            $stmt = db()->prepare(
                "UPDATE pengajuan SET status = :s, ditinjau_oleh = :oleh, ditinjau_pada = NOW(), catatan_peninjau = :c
                  WHERE id = :id AND status = 'menunggu' AND user_id <> :oleh2"
            );
            $stmt->execute([':s' => $keputusan, ':oleh' => $uid, ':c' => $catatan, ':id' => $id, ':oleh2' => $uid]);
            if ($stmt->rowCount() !== 1) {
                flash_set('galat', 'Pengajuan tidak dapat ditinjau (sudah diproses atau milik Anda sendiri).');
                kembali($alamat);
            }
            $stmt = db()->prepare('SELECT user_id, judul FROM pengajuan WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $p = $stmt->fetch();
            kirim_notifikasi((int) $p['user_id'], 'pengajuan', $p['judul'] . ' ' . ($keputusan === 'disetujui' ? 'disetujui' : 'ditolak'),
                kode_pengajuan($id) . ($catatan ? ': ' . $catatan : '.'), $alamat);
            catat_aktivitas($keputusan === 'disetujui' ? 'setujui' : 'tolak', 'pengajuan', $id, $catatan);
            flash_set('sukses', kode_pengajuan($id) . ' ' . $keputusan . '.');
            kembali($alamat);
        }
    } catch (PDOException $ex) {
        error_log('Pengajuan gagal diproses: ' . $ex->getMessage());
        flash_set('galat', 'Pengajuan belum dapat diproses. Coba lagi nanti.');
        kembali($alamat);
    }
    kembali($alamat);
}

// =====================================================================
// Data tampilan
// =====================================================================
$form = $_SESSION['form_pengajuan'] ?? null;
unset($_SESSION['form_pengajuan']);
if ($form && ($form['hal'] ?? '') !== $hal) {
    $form = null;
}
$nilai = $form['nilai'] ?? [];
$galat = $form['galat'] ?? [];
$baruUrl = (string) ($_GET['baru'] ?? '');
if (!$form && in_array($baruUrl, $jenisForm, true)) {
    $nilai['jenis'] = $baruUrl;
}

$dbGagal = false;
$milik = [];
$tinjau = [];
try {
    $tanda = implode(',', array_fill(0, count($jenisGrup), '?'));
    $stmt = db()->prepare(
        "SELECT p.*, r.nama_lengkap AS peninjau FROM pengajuan p LEFT JOIN users r ON r.id = p.ditinjau_oleh
          WHERE p.user_id = ? AND p.jenis IN ($tanda) ORDER BY p.created_at DESC, p.id DESC"
    );
    $stmt->execute([$uid, ...$jenisGrup]);
    $milik = $stmt->fetchAll();

    if ($admin) {
        $stmt = db()->prepare(
            "SELECT p.*, u.nama_lengkap, u.role FROM pengajuan p JOIN users u ON u.id = p.user_id
              WHERE p.status = 'menunggu' AND p.user_id <> ? AND p.jenis IN ($tanda)
              ORDER BY p.created_at"
        );
        $stmt->execute([$uid, ...$jenisGrup]);
        $tinjau = $stmt->fetchAll();
    }
    $saldo = saldo_cuti($uid);
} catch (PDOException $ex) {
    error_log('Pengajuan gagal dimuat: ' . $ex->getMessage());
    $dbGagal = true;
}

function chip_status_pengajuan(string $s): string
{
    [$teks, $kelas] = LABEL_STATUS_PENGAJUAN[$s] ?? [$s, 'netral'];
    return '<span class="chip ' . $kelas . ' bertitik">' . e($teks) . '</span>';
}

function sel_tanggal_pengajuan(array $p): string
{
    if (!$p['tanggal_mulai']) {
        return '<span class="redup">-</span>';
    }
    return '<b>' . e(tanggal_id((string) $p['tanggal_mulai'])) . '</b>';
}

$judul = $hal === 'cuti' ? 'Pengajuan Cuti' : 'Pengajuan Izin';
$tombolBaru = '<button type="button" class="tombol utama" data-buka-dialog="dialogPengajuan">' . ikon('tambah') . 'Pengajuan baru</button>';
halaman_awal($judul, $judul);
kepala_halaman('papan', $judul,
    $hal === 'cuti' ? 'Cuti dan sakit milik Anda beserta status persetujuannya.' : 'Izin dan pengajuan lain (perpanjangan kontrak, klaim medis, perubahan data).',
    $tombolBaru);

if ($dbGagal): ?>
            <p class="galat" role="alert">Data pengajuan belum dapat dimuat. Pastikan database.sql sudah diimpor ulang.</p>
<?php
    halaman_akhir();
    return;
endif;

$tahunIni = (string) hari_ini()->format('Y');
$diTahunIni = array_filter($milik, static fn($p) => substr((string) ($p['tanggal_mulai'] ?? $p['created_at']), 0, 4) === $tahunIni);
$hitungStatus = static fn(string $s) => count(array_filter($milik, static fn($p) => $p['status'] === $s));
?>
            <?php if ($hal === 'cuti'): ?>
                <?php
                $hariSakit = array_sum(array_map(static fn($p) => jumlah_hari($p['tanggal_mulai'], $p['tanggal_selesai']),
                    array_filter($diTahunIni, static fn($p) => $p['jenis'] === 'sakit' && $p['status'] === 'disetujui')));
                $pTerpakai = $saldo['hak'] ? 100 * $saldo['terpakai'] / $saldo['hak'] : 0;
                $pMenunggu = $saldo['hak'] ? 100 * $saldo['menunggu'] / $saldo['hak'] : 0;
                ?>
                <div class="kisi-2-1 saldo-kisi">
                    <section class="kartu saldo-cuti" aria-label="Saldo cuti <?= (int) $saldo['tahun'] ?>">
                        <div class="kepala-kartu baris-kepala">
                            <div><h2>Saldo cuti <?= (int) $saldo['tahun'] ?></h2><p>Hak <?= (int) $saldo['hak'] ?> hari dari kontrak aktif · reset 1 Januari</p></div>
                            <div class="angka-sorot"><small>Sisa</small><b class="angka-besar"><?= (int) $saldo['sisa'] ?> <small>hari</small></b></div>
                        </div>
                        <div class="batang-saldo" role="img" aria-label="Terpakai <?= (int) $saldo['terpakai'] ?> hari, menunggu <?= (int) $saldo['menunggu'] ?> hari, sisa <?= (int) $saldo['sisa'] ?> hari">
                            <span class="terpakai" style="width: <?= round($pTerpakai, 1) ?>%"></span>
                            <span class="menunggu" style="width: <?= round($pMenunggu, 1) ?>%"></span>
                        </div>
                        <ul class="legenda-saldo">
                            <li class="terpakai"><b><?= (int) $saldo['terpakai'] ?> hari</b> terpakai</li>
                            <li class="menunggu"><b><?= (int) $saldo['menunggu'] ?> hari</b> menunggu persetujuan</li>
                            <li class="sisa"><b><?= (int) $saldo['sisa'] ?> hari</b> tersedia</li>
                        </ul>
                    </section>
                    <section class="kpi-kisi kpi-tumpuk" aria-label="Ringkasan">
                        <?= kartu_kpi('Menunggu', angka($hitungStatus('menunggu')), 'jam', 'kuning', 'Pengajuan belum diputuskan') ?>
                        <?= kartu_kpi('Hari sakit ' . $tahunIni, angka($hariSakit), 'medis', 'ungu', 'Tidak memotong saldo cuti') ?>
                    </section>
                </div>
            <?php else: ?>
                <?php
                $hariIzin = array_sum(array_map(static fn($p) => jumlah_hari($p['tanggal_mulai'], $p['tanggal_selesai']),
                    array_filter($diTahunIni, static fn($p) => $p['jenis'] === 'izin' && $p['status'] === 'disetujui')));
                ?>
                <section class="kpi-kisi" aria-label="Ringkasan izin">
                    <?= kartu_kpi('Hari izin ' . $tahunIni, angka($hariIzin), 'kalender', 'hijau', 'Izin yang disetujui') ?>
                    <?= kartu_kpi('Menunggu', angka($hitungStatus('menunggu')), 'jam', 'kuning', 'Belum diputuskan HR') ?>
                    <?= kartu_kpi('Disetujui', angka($hitungStatus('disetujui')), 'tanda', 'biru', 'Sepanjang waktu') ?>
                    <?= kartu_kpi('Ditolak / batal', angka($hitungStatus('ditolak') + $hitungStatus('dibatalkan')), 'silang', 'merah', 'Lihat catatan peninjau') ?>
                </section>
            <?php endif; ?>

            <?php if ($admin): ?>
                <section class="kartu tabel-kartu sorot-tinjau" aria-labelledby="judulTinjau">
                    <div class="kepala-tabel">
                        <h2 id="judulTinjau"><?= ikon('kotak-masuk') ?> Perlu ditinjau <span class="hitung"><?= count($tinjau) ?></span></h2>
                        <span class="redup">Pengajuan <?= $hal === 'cuti' ? 'cuti & sakit' : 'izin & lainnya' ?> dari karyawan lain</span>
                    </div>
                    <?php if (!$tinjau): ?>
                        <p class="tabel-kosong">Tidak ada pengajuan yang menunggu. Semua sudah ditinjau.</p>
                    <?php else: ?>
                        <div class="gulir-tabel">
                            <table class="tabel-data">
                                <thead><tr><th scope="col">Karyawan</th><th scope="col">Jenis</th><th scope="col">Tanggal</th><th scope="col">Alasan</th><th scope="col">Diajukan</th><th scope="col"><span class="sr">Aksi</span></th></tr></thead>
                                <tbody>
                                    <?php foreach ($tinjau as $p): ?>
                                        <tr>
                                            <td><span class="sel-pemain"><?= avatar_kecil((string) $p['nama_lengkap'], null) ?><span><b><?= e((string) $p['nama_lengkap']) ?></b><small class="sub-sel"><?= e(ROLE_LABELS[$p['role']] ?? '') ?></small></span></span></td>
                                            <td><b><?= e(GRUP_PENGAJUAN[$hal][$p['jenis']] ?? $p['jenis']) ?></b><small class="sub-sel"><?= e(kode_pengajuan((int) $p['id'])) ?></small></td>
                                            <td class="sel-tgl"><?= $p['tanggal_mulai'] ? e(tanggal_id((string) $p['tanggal_mulai'])) . ($p['tanggal_selesai'] !== $p['tanggal_mulai'] ? ' – ' . e(tanggal_id((string) $p['tanggal_selesai'])) : '') . '<small class="sub-sel">' . jumlah_hari($p['tanggal_mulai'], $p['tanggal_selesai']) . ' hari</small>' : '<span class="redup">-</span>' ?></td>
                                            <td class="sel-alasan"><?= e((string) $p['isi']) ?></td>
                                            <td><small class="redup"><?= e(waktu_relatif((string) $p['created_at'])) ?></small></td>
                                            <td><button type="button" class="tombol kecil utama" data-buka-dialog="tinjau-<?= (int) $p['id'] ?>">Tinjau</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
                <?php foreach ($tinjau as $p): ?>
                    <dialog class="modal" id="tinjau-<?= (int) $p['id'] ?>" aria-labelledby="judul-tinjau-<?= (int) $p['id'] ?>">
                        <form method="post" action="<?= e($alamat) ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="tinjau">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <div class="modal-kepala">
                                <span class="ikon-judul"><?= ikon('papan') ?></span>
                                <div>
                                    <h2 id="judul-tinjau-<?= (int) $p['id'] ?>">Tinjau <?= e(kode_pengajuan((int) $p['id'])) ?></h2>
                                    <p><?= e((string) $p['nama_lengkap']) ?> · <?= e(GRUP_PENGAJUAN[$hal][$p['jenis']] ?? $p['jenis']) ?></p>
                                </div>
                                <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                            </div>
                            <div class="modal-isi">
                                <?php if ($p['tanggal_mulai']): ?>
                                    <p><b><?= e(tanggal_id((string) $p['tanggal_mulai'], true)) ?></b><?= $p['tanggal_selesai'] !== $p['tanggal_mulai'] ? ' – <b>' . e(tanggal_id((string) $p['tanggal_selesai'], true)) . '</b>' : '' ?> (<?= jumlah_hari($p['tanggal_mulai'], $p['tanggal_selesai']) ?> hari)</p>
                                <?php endif; ?>
                                <p class="deskripsi-agenda"><?= e((string) $p['isi']) ?></p>
                                <div class="medan">
                                    <label for="catatan-<?= (int) $p['id'] ?>">Catatan untuk karyawan <small>(wajib bila ditolak)</small></label>
                                    <div class="kolom"><textarea id="catatan-<?= (int) $p['id'] ?>" name="catatan" maxlength="255" placeholder="Mis. Disetujui, selamat beristirahat."></textarea></div>
                                </div>
                            </div>
                            <div class="modal-kaki">
                                <button type="submit" class="tombol bahaya" name="keputusan" value="ditolak"><?= ikon('silang') ?>Tolak</button>
                                <button type="submit" class="tombol utama" name="keputusan" value="disetujui"><?= ikon('tanda') ?>Setujui</button>
                            </div>
                        </form>
                    </dialog>
                <?php endforeach; ?>
            <?php endif; ?>

            <section class="kartu tabel-kartu" data-tabel aria-labelledby="judulMilik">
                <div class="kepala-tabel">
                    <h2 id="judulMilik">Pengajuan saya</h2>
                    <?= alat_tabel('Cari alasan...', ['status' => ['label' => 'Status', 'opsi' => array_map(static fn($v) => $v[0], LABEL_STATUS_PENGAJUAN)]]) ?>
                </div>
                <?php if (!$milik): ?>
                    <div class="kosong-isi">
                        <span class="ikon-kosong"><?= ikon('kotak-masuk') ?></span>
                        <b>Belum ada pengajuan</b>
                        <span>Klik “Pengajuan baru” untuk mengajukan.</span>
                    </div>
                <?php else: ?>
                    <div class="gulir-tabel">
                        <table class="tabel-data">
                            <thead>
                                <tr>
                                    <?= th_urut('Jenis') ?>
                                    <?= th_urut('Mulai', 'angka') ?>
                                    <?= th_urut('Selesai', 'angka') ?>
                                    <th scope="col">Alasan</th>
                                    <?= th_urut('Diajukan', 'angka', 'desc') ?>
                                    <?= th_urut('Status') ?>
                                    <th scope="col"><span class="sr">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($milik as $p): ?>
                                    <tr data-status="<?= e((string) $p['status']) ?>">
                                        <td><b><?= e(GRUP_PENGAJUAN[$hal][$p['jenis']] ?? (string) $p['judul']) ?></b><small class="sub-sel"><?= e(kode_pengajuan((int) $p['id'])) ?></small></td>
                                        <td class="sel-tgl" data-nilai="<?= $p['tanggal_mulai'] ? strtotime((string) $p['tanggal_mulai']) : 0 ?>"><?= sel_tanggal_pengajuan($p) ?></td>
                                        <td class="sel-tgl" data-nilai="<?= $p['tanggal_selesai'] ? strtotime((string) $p['tanggal_selesai']) : 0 ?>">
                                            <?php if ($p['tanggal_selesai']): ?>
                                                <b><?= e(tanggal_id((string) $p['tanggal_selesai'])) ?></b> <small class="redup">(<?= jumlah_hari($p['tanggal_mulai'], $p['tanggal_selesai']) ?> hr)</small>
                                            <?php else: ?><span class="redup">-</span><?php endif; ?>
                                        </td>
                                        <td class="sel-alasan"><?= e((string) $p['isi']) ?></td>
                                        <td data-nilai="<?= strtotime((string) $p['created_at']) ?>"><small class="redup"><?= e(waktu_relatif((string) $p['created_at'])) ?></small></td>
                                        <td data-nilai="<?= e((string) $p['status']) ?>">
                                            <?= chip_status_pengajuan((string) $p['status']) ?>
                                            <?php if (!empty($p['catatan_peninjau'])): ?><small class="sub-sel" title="<?= e((string) $p['peninjau']) ?>">“<?= e((string) $p['catatan_peninjau']) ?>”</small><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['status'] === 'menunggu'): ?>
                                                <form method="post" action="<?= e($alamat) ?>" data-konfirmasi="Batalkan <?= e(kode_pengajuan((int) $p['id'])) ?>?">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="aksi" value="batal">
                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                    <button type="submit" class="tombol-ikon-kecil" aria-label="Batalkan <?= e(kode_pengajuan((int) $p['id'])) ?>" title="Batalkan"><?= ikon('silang') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= kaki_tabel() ?>
                <?php endif; ?>
            </section>

            <dialog class="modal" id="dialogPengajuan" aria-labelledby="judulDialogPengajuan"<?= $form ? ' data-buka-otomatis' : ($baruUrl !== '' && isset($nilai['jenis']) ? ' data-buka-otomatis' : '') ?>>
                <form method="post" action="<?= e($alamat) ?>" data-form-pengajuan data-sisa-cuti="<?= (int) $saldo['sisa'] ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="ajukan">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('papan') ?></span>
                        <div>
                            <h2 id="judulDialogPengajuan">Pengajuan baru</h2>
                            <p><?= $hal === 'cuti' ? 'Cuti tahunan memotong saldo; sakit tidak.' : 'HR akan meninjau dan memberi kabar lewat notifikasi.' ?></p>
                        </div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <div class="medan<?= isset($galat['jenis']) ? ' salah' : '' ?>">
                            <label for="jenisPengajuan">Jenis</label>
                            <select id="jenisPengajuan" name="jenis" required autofocus>
                                <?php foreach ($jenisForm as $j): ?>
                                    <option value="<?= e($j) ?>"<?= ($nilai['jenis'] ?? '') === $j ? ' selected' : '' ?>><?= e(GRUP_PENGAJUAN[$hal][$j]) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($galat['jenis'])): ?><p class="pesan-salah"><?= e($galat['jenis']) ?></p><?php endif; ?>
                        </div>
                        <div class="baris-2">
                            <div class="medan<?= isset($galat['tanggal_mulai']) ? ' salah' : '' ?>">
                                <label for="tglMulai">Tanggal mulai</label>
                                <div class="kolom"><input type="date" id="tglMulai" name="tanggal_mulai" value="<?= e((string) ($nilai['mulai'] ?? '')) ?>"></div>
                                <?php if (isset($galat['tanggal_mulai'])): ?><p class="pesan-salah"><?= e($galat['tanggal_mulai']) ?></p><?php endif; ?>
                            </div>
                            <div class="medan<?= isset($galat['tanggal_selesai']) ? ' salah' : '' ?>">
                                <label for="tglSelesai">Tanggal selesai</label>
                                <div class="kolom"><input type="date" id="tglSelesai" name="tanggal_selesai" value="<?= e((string) ($nilai['selesai'] ?? '')) ?>"></div>
                                <?php if (isset($galat['tanggal_selesai'])): ?><p class="pesan-salah"><?= e($galat['tanggal_selesai']) ?></p><?php endif; ?>
                            </div>
                        </div>
                        <p class="info-hari" data-info-hari aria-live="polite"></p>
                        <div class="medan<?= isset($galat['alasan']) ? ' salah' : '' ?>">
                            <label for="alasanPengajuan">Alasan <small>(10–500 karakter)</small></label>
                            <div class="kolom"><textarea id="alasanPengajuan" name="alasan" maxlength="500" required placeholder="Jelaskan keperluan Anda."><?= e((string) ($nilai['alasan'] ?? '')) ?></textarea></div>
                            <?php if (isset($galat['alasan'])): ?><p class="pesan-salah"><?= e($galat['alasan']) ?></p><?php endif; ?>
                        </div>
                        <?php if ($hal === 'izin'): ?>
                            <p class="bantuan">Tanggal boleh dikosongkan untuk perpanjangan kontrak, klaim medis, dan lainnya. Perubahan data pribadi diajukan dari <a href="profil.php">Profil Saya</a>.</p>
                        <?php else: ?>
                            <p class="bantuan">Laporan sakit dapat diajukan hingga 14 hari setelah tanggal mulai sakit. Lampirkan surat dokter ke tim medis.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('kirim') ?>Kirim pengajuan</button>
                    </div>
                </form>
            </dialog>
<?php
halaman_akhir();
