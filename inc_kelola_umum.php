<?php
declare(strict_types=1);

// Fungsi bersama modul kelola Performa & Medis (statistik, cedera, kebugaran, program latihan).
// Dimuat oleh inc_kelola_*.php; membutuhkan tampilan.php sudah dimuat.

if (!function_exists('db')) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/admin_lib.php';

/** POST staf: tolak bila bukan staf atau token CSRF salah. */
function k_mulai_post(string $kembali): void
{
    if (!adalah_staf()) {
        http_response_code(403);
        exit('Anda tidak memiliki akses untuk mengubah data ini.');
    }
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash_set('galat', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        kembali_ke($kembali);
    }
}

/** Ambil nilai POST yang sudah di-trim untuk daftar kunci. */
function k_ambil(array $kunci): array
{
    $v = [];
    foreach ($kunci as $k) {
        $v[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    return $v;
}

/** Bilangan bulat dalam rentang, atau null bila tidak sah. Kosong dianggap $kosong. */
function k_int(string $v, int $min, int $max, ?int $kosong = null): ?int
{
    if ($v === '') {
        return $kosong;
    }
    $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    return $n === false ? null : $n;
}

/** Desimal (titik atau koma) dalam rentang, atau null. */
function k_desimal(string $v, float $min, float $max): ?float
{
    $v = str_replace(',', '.', $v);
    if ($v === '' || !is_numeric($v)) {
        return null;
    }
    $n = (float) $v;
    return $n < $min || $n > $max ? null : $n;
}

function k_tanggal(string $t): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}

/** Simpan galat & nilai formulir agar dialog terbuka lagi berisi isian sebelumnya. */
function k_gagal(string $kunci, array $galat, array $nilai, string $kembali): never
{
    $_SESSION[$kunci] = ['galat' => $galat, 'nilai' => $nilai];
    flash_set('galat', reset($galat) ?: 'Periksa kembali isian formulir.');
    kembali_ke($kembali);
}

/** Ambil (sekali) status formulir yang gagal. */
function k_form(string $kunci): array
{
    $f = $_SESSION[$kunci] ?? null;
    unset($_SESSION[$kunci]);
    return is_array($f) ? $f : ['galat' => [], 'nilai' => []];
}

/** Bungkus satu isian formulir dengan label & pesan galat. */
function k_medan(string $nama, string $label, string $input, array $galat, bool $wajib = true, string $bantu = ''): string
{
    return '<div class="medan' . (isset($galat[$nama]) ? ' salah' : '') . '"><label for="k_' . e($nama) . '">' . e($label)
        . ($wajib ? '' : ' <small>(opsional)</small>') . '</label><div class="kolom">' . $input . '</div>'
        . (isset($galat[$nama]) ? '<p class="pesan-salah">' . e($galat[$nama]) . '</p>' : ($bantu !== '' ? '<p class="bantuan">' . e($bantu) . '</p>' : ''))
        . '</div>';
}

function k_isian(string $nama, array $nilai, string $tipe = 'text', string $ekstra = ''): string
{
    return '<input type="' . e($tipe) . '" id="k_' . e($nama) . '" name="' . e($nama) . '" value="' . e((string) ($nilai[$nama] ?? '')) . '" ' . $ekstra . '>';
}

function k_pilih(string $nama, array $opsi, array $nilai, string $ekstra = ''): string
{
    $h = '<select id="k_' . e($nama) . '" name="' . e($nama) . '" ' . $ekstra . '>';
    foreach ($opsi as $k => $l) {
        if (is_array($l)) { // optgroup
            $h .= '<optgroup label="' . e((string) $k) . '">';
            foreach ($l as $k2 => $l2) {
                $h .= '<option value="' . e((string) $k2) . '"' . ((string) ($nilai[$nama] ?? '') === (string) $k2 ? ' selected' : '') . '>' . e((string) $l2) . '</option>';
            }
            $h .= '</optgroup>';
            continue;
        }
        $h .= '<option value="' . e((string) $k) . '"' . ((string) ($nilai[$nama] ?? '') === (string) $k ? ' selected' : '') . '>' . e((string) $l) . '</option>';
    }
    return $h . '</select>';
}

/** Tombol ikon ubah (isi dialog) + hapus (dengan konfirmasi) untuk satu baris. */
function k_aksi_baris(string $idDialog, array $json, string $judulUbah, string $aksiHapus, int $id, string $kembali, string $namaHapus): string
{
    return '<span class="aksi-ikon">'
        . '<button type="button" class="tombol-ikon-kecil" ' . isi_dialog($idDialog, $json, $judulUbah) . ' title="Ubah" aria-label="' . e($judulUbah) . '">' . ikon('pena') . '</button>'
        . '<form method="post" action="' . e($kembali) . '" data-konfirmasi="Hapus ' . e($namaHapus) . '? Tindakan ini tidak dapat dibatalkan.">'
        . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="aksi" value="' . e($aksiHapus) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="tombol-ikon-kecil bahaya" title="Hapus" aria-label="Hapus ' . e($namaHapus) . '">' . ikon('silang') . '</button></form>'
        . '</span>';
}

/** Opsi pemilih pemain aktif untuk formulir: [id => '#7 Nama · POS']. */
function k_opsi_pemain(): array
{
    $o = ['' => 'Pilih pemain...'];
    foreach (daftar_pemain() as $p) {
        $o[$p['id']] = ($p['nomor_punggung'] !== null ? '#' . $p['nomor_punggung'] . ' ' : '') . $p['nama_lengkap'];
    }
    return $o;
}

function k_pemain_sah(int $id): bool
{
    $st = db()->prepare("SELECT 1 FROM users WHERE id = ? AND role = 'pemain' AND is_active = 1");
    $st->execute([$id]);
    return (bool) $st->fetchColumn();
}

/** Samakan total di tabel pemain (dipakai dashboard) dengan data per pertandingan. */
function k_sinkron_total_pemain(int $userId): void
{
    db()->prepare(
        "UPDATE pemain p
            SET p.goal = (SELECT COALESCE(SUM(goal), 0) FROM statistik_pertandingan WHERE user_id = p.user_id),
                p.assist = (SELECT COALESCE(SUM(assist), 0) FROM statistik_pertandingan WHERE user_id = p.user_id),
                p.kartu_kuning = (SELECT COALESCE(SUM(kartu_kuning), 0) FROM statistik_pertandingan WHERE user_id = p.user_id),
                p.kartu_merah = (SELECT COALESCE(SUM(kartu_merah), 0) FROM statistik_pertandingan WHERE user_id = p.user_id)
          WHERE p.user_id = ?"
    )->execute([$userId]);
}

/** Status profil pemain mengikuti cedera: ada cedera belum pulih -> 'cedera', jika tidak kembali 'aktif'. */
function k_sinkron_status_cedera(int $userId): void
{
    db()->prepare(
        "UPDATE profil_pemain pr
            SET pr.status = IF(EXISTS (SELECT 1 FROM cedera c WHERE c.user_id = pr.user_id AND c.status <> 'pulih'), 'cedera', 'aktif')
          WHERE pr.user_id = ? AND pr.status IN ('aktif', 'cedera')"
    )->execute([$userId]);
}
