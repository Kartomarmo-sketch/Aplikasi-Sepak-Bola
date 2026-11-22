<?php
declare(strict_types=1);

// Komponen bersama dashboard (admin/manajemen, pelatih/medis, pemain):
// sapaan, kepala kartu, agenda terdekat, pengumuman klub, dan pengelolaan pengumuman (admin).
// Membutuhkan tampilan.php.

const DASBOR_JENIS = [
    'latihan'      => ['Latihan', 'hijau', 'peluit'],
    'pertandingan' => ['Pertandingan', 'kuning', 'trofi'],
    'rapat'        => ['Rapat', 'biru', 'rapat'],
    'perjalanan'   => ['Perjalanan tandang', 'ungu', 'bus'],
    'lainnya'      => ['Lainnya', 'netral', 'kalender'],
];
const DASBOR_SASARAN = ['semua' => 'Semua', 'pemain' => 'Pemain', 'staf' => 'Staf'];

/** Level kebugaran dari skor: [label, warna]. */
function d_level(mixed $skor): array
{
    if ($skor === null) {
        return ['Belum dites', 'netral'];
    }
    $s = (int) $skor;
    return match (true) {
        $s >= 85 => ['Sangat baik', 'hijau'],
        $s >= 70 => ['Baik', 'biru'],
        $s >= 55 => ['Cukup', 'kuning'],
        default  => ['Kurang', 'merah'],
    };
}

function d_chip(string $teks, string $warna): string
{
    return '<span class="chip ' . e($warna) . ' bertitik">' . e($teks) . '</span>';
}

/** Kepala kartu: judul, keterangan, dan tautan "Lihat semua". */
function d_kepala(string $id, string $judul, string $sub = '', string $tautan = '', string $teksTautan = 'Lihat semua', string $kanan = ''): string
{
    return '<div class="kepala-kartu baris-kepala"><div><h2 id="' . e($id) . '">' . e($judul) . '</h2>'
        . ($sub !== '' ? '<p>' . $sub . '</p>' : '') . '</div>'
        . $kanan
        . ($tautan !== '' ? '<a class="tautan-kartu" href="' . e($tautan) . '">' . e($teksTautan) . ikon('panah-kanan') . '</a>' : '')
        . '</div>';
}

/** Nama sapaan: kata pertama (gelar seperti "dr." ikut kata berikutnya). */
function d_nama_depan(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama)) ?: [$nama];
    return str_ends_with($kata[0], '.') && isset($kata[1]) ? $kata[0] . ' ' . $kata[1] : $kata[0];
}

/** "Hari ini", "Besok", "3 hari lagi", "Berlangsung". */
function d_relatif_hari(string $mulai, ?string $selesai = null): string
{
    $hari = selisih_hari(hari_ini()->format('Y-m-d'), substr($mulai, 0, 10));
    if ($hari < 0) {
        return 'Berlangsung';
    }
    return match ($hari) { 0 => 'Hari ini', 1 => 'Besok', default => $hari . ' hari lagi' };
}

/** Sapaan di atas dashboard: tanggal, salam, peran, ringkasan hari ini, dan aksi cepat. */
function d_sapaan(string $nama, string $peran, array $ringkas, string $aksi): void
{
    $jam = (int) (new DateTimeImmutable('now', new DateTimeZone(ZONA_WAKTU)))->format('G');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    ?>
            <section class="sapaan" aria-label="Sapaan">
                <div class="sapaan-teks">
                    <p class="sapaan-tanggal"><?= ikon('kalender') ?><?= e(hari_tanggal(hari_ini()->format('Y-m-d'), true)) ?></p>
                    <h1><?= e($salam) ?>, <?= e(d_nama_depan($nama)) ?></h1>
                    <p class="sapaan-peran"><?= e($peran) ?></p>
                    <?php if ($ringkas): ?>
                        <ul class="sapaan-ringkas" aria-label="Ringkasan hari ini">
                            <?php foreach ($ringkas as [$ikonNama, $teks, $tautan]): ?>
                                <li><a href="<?= e($tautan) ?>"><?= ikon($ikonNama) ?><?= e($teks) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if ($aksi !== ''): ?><div class="sapaan-aksi"><?= $aksi ?></div><?php endif; ?>
            </section>
    <?php
}

/** Agenda mendatang (termasuk yang sedang berlangsung). Pemain tidak melihat agenda khusus staf. */
function d_agenda(int $batas, bool $untukPemain): array
{
    $st = db()->prepare(
        "SELECT j.id, j.jenis, j.judul, j.lokasi, j.waktu_mulai, j.waktu_selesai, p.lawan, p.kompetisi
           FROM jadwal j LEFT JOIN pertandingan p ON p.jadwal_id = j.id
          WHERE COALESCE(j.waktu_selesai, j.waktu_mulai) >= NOW()" . ($untukPemain ? " AND j.peserta <> 'staf'" : '') . '
          ORDER BY j.waktu_mulai LIMIT ' . max(1, $batas)
    );
    $st->execute();
    return $st->fetchAll();
}

/** Jumlah agenda yang berlangsung hari ini. */
function d_agenda_hari_ini(bool $untukPemain): int
{
    try {
        return (int) db()->query(
            "SELECT COUNT(*) FROM jadwal WHERE DATE(waktu_mulai) <= CURDATE() AND DATE(COALESCE(waktu_selesai, waktu_mulai)) >= CURDATE()"
            . ($untukPemain ? " AND peserta <> 'staf'" : '')
        )->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

function d_daftar_agenda(array $agenda): string
{
    if (!$agenda) {
        return '<div class="d-kosong">' . ikon('kalender') . '<span>Belum ada agenda mendatang.</span></div>';
    }
    $h = '<ul class="d-agenda">';
    foreach ($agenda as $a) {
        [$label, $warna, $ikonNama] = DASBOR_JENIS[$a['jenis']] ?? DASBOR_JENIS['lainnya'];
        $mulai = (string) $a['waktu_mulai'];
        $t = new DateTimeImmutable(substr($mulai, 0, 10));
        $tglPendek = mb_substr(NAMA_HARI[(int) $t->format('w')], 0, 3) . ', ' . $t->format('j') . ' ' . mb_substr(NAMA_BULAN[(int) $t->format('n')], 0, 3);
        $relatif = d_relatif_hari($mulai);
        $h .= '<li><span class="d-ikon ' . $warna . '" title="' . e($label) . '">' . ikon($ikonNama) . '</span>'
            . '<div class="d-agenda-teks"><b title="' . e((string) $a['judul']) . '">' . e((string) $a['judul']) . '</b>'
            . '<small title="' . e((string) $a['lokasi']) . '"><span class="d-jenis ' . $warna . '">' . e($label) . '</span> · ' . e($tglPendek) . ', ' . e(jam($mulai))
            . ($a['lokasi'] ? ' · ' . e((string) $a['lokasi']) : '') . '</small></div>'
            . '<span class="d-hari' . (in_array($relatif, ['Hari ini', 'Besok', 'Berlangsung'], true) ? ' dekat' : '') . '">' . e($relatif) . '</span></li>';
    }
    return $h . '</ul>';
}

/** Pengumuman yang masih tampil untuk sasaran ini ('pemain' | 'staf' | null = semua untuk admin). */
function d_pengumuman(?string $sasaran, int $batas): array
{
    $syarat = '(p.tampil_sampai IS NULL OR p.tampil_sampai >= CURDATE())';
    $param = [];
    if ($sasaran !== null) {
        $syarat .= " AND p.sasaran IN ('semua', ?)";
        $param[] = $sasaran;
    }
    $st = db()->prepare("SELECT p.*, u.nama_lengkap AS penulis FROM pengumuman p LEFT JOIN users u ON u.id = p.dibuat_oleh
                          WHERE $syarat ORDER BY p.disematkan DESC, p.created_at DESC LIMIT " . max(1, $batas));
    $st->execute($param);
    return $st->fetchAll();
}

function d_daftar_pengumuman(array $daftar, bool $kelola): string
{
    if (!$daftar) {
        return '<div class="d-kosong">' . ikon('kotak-masuk') . '<span>Belum ada pengumuman.</span></div>';
    }
    $h = '<div class="d-umum-daftar">';
    foreach ($daftar as $p) {
        $id = (int) $p['id'];
        $h .= '<article class="d-umum' . ($p['disematkan'] ? ' sematkan' : '') . '">'
            . '<div class="d-umum-kepala"><h3>' . e((string) $p['judul']) . '</h3>'
            . ($p['disematkan'] ? '<span class="chip kuning bertitik">Disematkan</span>' : '') . '</div>'
            . '<p>' . e((string) $p['isi']) . '</p>'
            . '<small class="d-umum-meta"><span>' . e(waktu_notif((string) $p['created_at'])) . '</span>'
            . ($kelola ? '<span aria-hidden="true">·</span><span>Untuk ' . e(mb_strtolower(DASBOR_SASARAN[$p['sasaran']] ?? 'semua')) . '</span>'
                . ($p['tampil_sampai'] ? '<span aria-hidden="true">·</span><span>s.d. ' . e(tanggal_id((string) $p['tampil_sampai'])) . '</span>' : '') : '')
            . '</small>';
        if ($kelola) {
            $form = static fn(string $aksi, string $ikonNama, string $judul, string $kelas = '', string $konfirmasi = '') =>
                '<form method="post" action="dashboard.php"' . ($konfirmasi !== '' ? ' data-konfirmasi="' . e($konfirmasi) . '"' : '') . '>'
                . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="' . $aksi . '">'
                . '<input type="hidden" name="id" value="' . $id . '">'
                . '<button type="submit" class="tombol-ikon-kecil ' . $kelas . '" title="' . e($judul) . '" aria-label="' . e($judul . ': ' . $p['judul']) . '">' . ikon($ikonNama) . '</button></form>';
            $h .= '<div class="d-umum-aksi">'
                . $form('umum_sematkan', 'pin', $p['disematkan'] ? 'Lepas sematan' : 'Sematkan', $p['disematkan'] ? 'aktif' : '')
                . $form('umum_hapus', 'sampah', 'Hapus pengumuman', 'bahaya', 'Hapus pengumuman "' . $p['judul'] . '"?')
                . '</div>';
        }
        $h .= '</article>';
    }
    return $h . '</div>';
}

/** Proses POST pengumuman dari dashboard admin (PRG). */
function proses_pengumuman(): never
{
    $kembali = static function (string $tipe, string $pesan): never {
        flash_set($tipe, $pesan);
        header('Location: dashboard.php#pengumuman', true, 303);
        exit;
    };
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Hanya admin yang dapat mengelola pengumuman.');
    }
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $kembali('galat', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
    $aksi = (string) ($_POST['aksi'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    try {
        if ($aksi === 'umum_sematkan') {
            $st = db()->prepare('UPDATE pengumuman SET disematkan = 1 - disematkan WHERE id = ?');
            $st->execute([$id]);
            $kembali($st->rowCount() ? 'sukses' : 'galat', $st->rowCount() ? 'Sematan pengumuman diperbarui.' : 'Pengumuman tidak ditemukan.');
        }
        if ($aksi === 'umum_hapus') {
            $st = db()->prepare('DELETE FROM pengumuman WHERE id = ?');
            $st->execute([$id]);
            if ($st->rowCount()) {
                catat_aktivitas('hapus', 'pengumuman', $id);
            }
            $kembali($st->rowCount() ? 'sukses' : 'galat', $st->rowCount() ? 'Pengumuman dihapus.' : 'Pengumuman tidak ditemukan.');
        }
        if ($aksi !== 'umum_simpan') {
            $kembali('galat', 'Aksi tidak dikenal.');
        }
        $judul = trim((string) ($_POST['judul'] ?? ''));
        $isi = trim((string) ($_POST['isi'] ?? ''));
        $sasaran = (string) ($_POST['sasaran'] ?? 'semua');
        $sampai = trim((string) ($_POST['tampil_sampai'] ?? ''));
        $tgl = $sampai === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $sampai);
        $galat = match (true) {
            mb_strlen($judul) < 3 || mb_strlen($judul) > 150 => 'Judul pengumuman 3–150 karakter.',
            mb_strlen($isi) < 10 || mb_strlen($isi) > 1000   => 'Isi pengumuman 10–1000 karakter.',
            !isset(DASBOR_SASARAN[$sasaran])                 => 'Pilih sasaran pengumuman.',
            $tgl === false || ($tgl && $tgl->format('Y-m-d') !== $sampai) => 'Tanggal tampil sampai tidak valid.',
            $tgl && $sampai < hari_ini()->format('Y-m-d')     => 'Tanggal tampil sampai tidak boleh di masa lalu.',
            default => '',
        };
        if ($galat !== '') {
            $_SESSION['form_umum'] = ['judul' => $judul, 'isi' => $isi, 'sasaran' => $sasaran, 'tampil_sampai' => $sampai, 'galat' => $galat];
            $kembali('galat', $galat);
        }
        db()->prepare('INSERT INTO pengumuman (judul, isi, sasaran, disematkan, tampil_sampai, dibuat_oleh) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$judul, $isi, $sasaran, isset($_POST['disematkan']) ? 1 : 0, $tgl ? $sampai : null, $_SESSION['user_id']]);
        $idBaru = (int) db()->lastInsertId();
        $terkirim = 0;
        if (isset($_POST['kirim_notif'])) {
            $syarat = $sasaran === 'pemain' ? " AND role = 'pemain'" : ($sasaran === 'staf' ? " AND role <> 'pemain'" : '');
            $penerima = db()->query('SELECT id FROM users WHERE is_active = 1' . $syarat)->fetchAll(PDO::FETCH_COLUMN);
            $penerima = array_values(array_diff(array_map('intval', $penerima), [(int) $_SESSION['user_id']]));
            $terkirim = kirim_notifikasi($penerima, 'sistem', 'Pengumuman: ' . $judul,
                mb_strlen($isi) > 180 ? mb_substr($isi, 0, 177) . '...' : $isi, 'dashboard.php#pengumuman');
        }
        catat_aktivitas('tambah', 'pengumuman', $idBaru, 'Menerbitkan pengumuman ' . $judul);
        $kembali('sukses', 'Pengumuman diterbitkan' . ($terkirim ? ' dan dikirim ke ' . $terkirim . ' orang.' : '.'));
    } catch (PDOException $ex) {
        error_log('Pengumuman gagal diproses: ' . $ex->getMessage());
        $kembali('galat', 'Pengumuman belum dapat disimpan. Coba lagi nanti.');
    }
}

/** Dialog tulis pengumuman (admin). */
function dialog_pengumuman(): void
{
    $f = $_SESSION['form_umum'] ?? null;
    unset($_SESSION['form_umum']);
    $v = static fn(string $k, string $bawaan = '') => (string) ($f[$k] ?? $bawaan);
    ?>
            <dialog class="modal" id="dialogPengumuman" aria-labelledby="judulDialogUmum"<?= $f ? ' data-buka-otomatis' : '' ?>>
                <form method="post" action="dashboard.php">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="umum_simpan">
                    <div class="modal-kepala">
                        <span class="ikon-judul"><?= ikon('kotak-masuk') ?></span>
                        <div><h2 id="judulDialogUmum">Tulis pengumuman</h2><p>Tampil di dashboard sesuai sasaran yang dipilih.</p></div>
                        <button type="button" class="tutup-toast" data-tutup-dialog aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-isi">
                        <?php if ($f): ?><p class="galat" role="alert"><?= e($v('galat')) ?></p><?php endif; ?>
                        <div class="medan"><label for="umumJudul">Judul</label><div class="kolom"><input id="umumJudul" name="judul" maxlength="150" required value="<?= e($v('judul')) ?>" placeholder="mis. Jadwal tes kebugaran berkala"></div></div>
                        <div class="medan"><label for="umumIsi">Isi pengumuman</label><div class="kolom"><textarea id="umumIsi" name="isi" maxlength="1000" required rows="5" placeholder="Tulis informasi lengkap: apa, kapan, di mana, dan siapa yang perlu bertindak."><?= e($v('isi')) ?></textarea></div></div>
                        <div class="baris-2">
                            <div class="medan"><label for="umumSasaran">Sasaran</label><div class="kolom"><select id="umumSasaran" name="sasaran">
                                <?php foreach (DASBOR_SASARAN as $k => $label): ?><option value="<?= $k ?>"<?= $v('sasaran', 'semua') === $k ? ' selected' : '' ?>><?= e($label === 'Semua' ? 'Semua karyawan' : 'Hanya ' . mb_strtolower($label)) ?></option><?php endforeach; ?>
                            </select></div></div>
                            <div class="medan"><label for="umumSampai">Tampil sampai <small class="redup">(opsional)</small></label><div class="kolom"><input id="umumSampai" type="date" name="tampil_sampai" min="<?= e(hari_ini()->format('Y-m-d')) ?>" value="<?= e($v('tampil_sampai')) ?>"></div></div>
                        </div>
                        <label class="cek-baris"><input type="checkbox" name="disematkan" value="1"> Sematkan di urutan teratas</label>
                        <label class="cek-baris"><input type="checkbox" name="kirim_notif" value="1" checked> Kirim juga sebagai notifikasi ke penerima</label>
                    </div>
                    <div class="modal-kaki">
                        <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                        <button type="submit" class="tombol utama"><?= ikon('kirim') ?>Terbitkan</button>
                    </div>
                </form>
            </dialog>
    <?php
}
