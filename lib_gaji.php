<?php
declare(strict_types=1);

// Fungsi bersama Slip Gaji (gaji.php & gaji_pdf.php). Membutuhkan tampilan.php.

const LABEL_JABATAN = [
    'manager' => 'Manager', 'admin' => 'Administrasi / HR', 'pelatih_kepala' => 'Pelatih Kepala',
    'asisten_pelatih' => 'Asisten Pelatih', 'pelatih_kiper' => 'Pelatih Kiper', 'pelatih_fisik' => 'Pelatih Fisik',
    'dokter' => 'Dokter Tim', 'fisioterapis' => 'Fisioterapis', 'analis' => 'Analis Performa', 'lainnya' => 'Staf',
];
const LABEL_STATUS_SLIP = ['dibayar' => ['Dibayar', 'hijau'], 'disetujui' => ['Disetujui', 'biru'], 'draft' => ['Draf', 'netral']];

/** "SG-202610-0004" */
function nomor_slip(array $slip): string
{
    return 'SG-' . str_replace('-', '', substr((string) $slip['periode'], 0, 7)) . '-' . str_pad((string) $slip['user_id'], 4, '0', STR_PAD_LEFT);
}

/** Angka -> kata (bahasa Indonesia), mis. 1250000 -> "satu juta dua ratus lima puluh ribu". */
function terbilang(int $n): string
{
    $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    if ($n < 0) {
        return 'minus ' . terbilang(-$n);
    }
    return trim(match (true) {
        $n < 12             => $satuan[$n],
        $n < 20             => terbilang($n - 10) . ' belas',
        $n < 100            => terbilang(intdiv($n, 10)) . ' puluh ' . terbilang($n % 10),
        $n < 200            => 'seratus ' . terbilang($n - 100),
        $n < 1000           => terbilang(intdiv($n, 100)) . ' ratus ' . terbilang($n % 100),
        $n < 2000           => 'seribu ' . terbilang($n - 1000),
        $n < 1000000        => terbilang(intdiv($n, 1000)) . ' ribu ' . terbilang($n % 1000),
        $n < 1000000000     => terbilang(intdiv($n, 1000000)) . ' juta ' . terbilang($n % 1000000),
        $n < 1000000000000  => terbilang(intdiv($n, 1000000000)) . ' miliar ' . terbilang($n % 1000000000),
        default             => terbilang(intdiv($n, 1000000000000)) . ' triliun ' . terbilang($n % 1000000000000),
    });
}

function terbilang_rupiah(float $n): string
{
    $kata = (string) preg_replace('/\s+/', ' ', terbilang((int) round($n)));
    return ucfirst($kata === '' ? 'nol' : $kata) . ' rupiah';
}

/** Identitas karyawan untuk kepala slip. */
function data_karyawan(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT u.nama_lengkap, u.role, s.nip, s.jabatan, pm.nomor_punggung, pr.posisi
           FROM users u
           LEFT JOIN staf s           ON s.user_id = u.id
           LEFT JOIN pemain pm        ON pm.user_id = u.id
           LEFT JOIN profil_pemain pr ON pr.user_id = u.id
          WHERE u.id = :u"
    );
    $stmt->execute([':u' => $userId]);
    $d = $stmt->fetch() ?: ['nama_lengkap' => '-', 'role' => '', 'nip' => null, 'jabatan' => null, 'nomor_punggung' => null, 'posisi' => null];
    if ($d['role'] === 'pemain') {
        $d['id_karyawan'] = 'PMN-' . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
        $d['jabatan_teks'] = 'Pemain' . ($d['posisi'] ? ' · ' . (LABEL_POSISI[$d['posisi']] ?? $d['posisi']) : '')
                           . ($d['nomor_punggung'] !== null ? ' #' . $d['nomor_punggung'] : '');
    } else {
        $d['id_karyawan'] = $d['nip'] ?: 'STF-' . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
        $d['jabatan_teks'] = LABEL_JABATAN[$d['jabatan'] ?? 'lainnya'] ?? 'Staf';
    }
    return $d;
}

/** Rincian per slip: [penggajian_id => [baris, ...]] urut sesuai kolom urutan. */
function rincian_slip(array $idSlip): array
{
    if (!$idSlip) {
        return [];
    }
    $tanda = implode(',', array_fill(0, count($idSlip), '?'));
    $stmt = db()->prepare("SELECT * FROM penggajian_rincian WHERE penggajian_id IN ($tanda) ORDER BY urutan, id");
    $stmt->execute(array_map('intval', $idSlip));
    $hasil = [];
    foreach ($stmt->fetchAll() as $r) {
        $hasil[(int) $r['penggajian_id']][] = $r;
    }
    return $hasil;
}

/**
 * Pisahkan rincian menjadi pendapatan & potongan. Bila slip lama belum punya rincian,
 * dibuat dari kolom ringkas penggajian agar tetap tampil benar.
 */
function susun_rincian(array $slip, array $rincian): array
{
    if (!$rincian) {
        $rincian = [
            ['kategori' => 'gaji_pokok', 'keterangan' => 'Gaji pokok', 'jumlah' => $slip['gaji_pokok']],
            ['kategori' => 'tunjangan',  'keterangan' => 'Tunjangan',  'jumlah' => $slip['tunjangan']],
            ['kategori' => 'bonus',      'keterangan' => 'Bonus',      'jumlah' => $slip['bonus']],
            ['kategori' => 'potongan',   'keterangan' => 'Potongan',   'jumlah' => $slip['potongan']],
        ];
        $rincian = array_filter($rincian, static fn($r) => (float) $r['jumlah'] > 0);
    }
    $masuk = array_values(array_filter($rincian, static fn($r) => $r['kategori'] !== 'potongan'));
    $keluar = array_values(array_filter($rincian, static fn($r) => $r['kategori'] === 'potongan'));
    return [
        'pendapatan' => $masuk,
        'potongan'   => $keluar,
        'bruto'      => array_sum(array_map(static fn($r) => (float) $r['jumlah'], $masuk)),
        'total_potongan' => array_sum(array_map(static fn($r) => (float) $r['jumlah'], $keluar)),
    ];
}
