<?php
declare(strict_types=1);

// Ekspor data admin ke CSV (Excel): ?jenis=pemain | staf | kontrak[&hal=] | gaji&periode=YYYY-MM

require __DIR__ . '/tampilan.php';
require_once __DIR__ . '/admin_lib.php';

start_secure_session();
wajib_admin();

$jenis = (string) ($_GET['jenis'] ?? '');
$tgl = hari_ini()->format('Y-m-d');

try {
    if ($jenis === 'pemain') {
        $data = db()->query(
            "SELECT u.id, u.nama_lengkap, pm.nomor_punggung, pr.posisi_kode, pr.tanggal_lahir, pr.kewarganegaraan, pr.tinggi_cm, pr.berat_kg,
                    pr.kaki_dominan, pr.status, pr.telepon, pr.email, pr.tanggal_bergabung, k.nomor_kontrak, k.tanggal_selesai, k.gaji_pokok, k.status AS sk
               FROM users u LEFT JOIN pemain pm ON pm.user_id = u.id LEFT JOIN profil_pemain pr ON pr.user_id = u.id
               LEFT JOIN kontrak k ON k.id = (SELECT id FROM kontrak WHERE user_id = u.id AND status = 'aktif' ORDER BY tanggal_selesai DESC LIMIT 1)
              WHERE u.role = 'pemain' AND u.is_active = 1 ORDER BY pm.nomor_punggung"
        )->fetchAll();
        catat_aktivitas('ekspor', 'pemain', null, count($data) . ' baris');
        unduh_csv('data-pemain-' . $tgl . '.csv',
            ['ID', 'Nama', 'No', 'Posisi', 'Usia', 'Negara', 'Tinggi (cm)', 'Berat (kg)', 'Kaki', 'Status', 'Telepon', 'Email', 'Bergabung', 'Kontrak', 'Berakhir', 'Gaji/bulan', 'Status kontrak'],
            array_map(static fn($p) => [id_pemain((int) $p['id']), $p['nama_lengkap'], $p['nomor_punggung'], KODE_POSISI[$p['posisi_kode']][0] ?? '',
                usia($p['tanggal_lahir']), $p['kewarganegaraan'], $p['tinggi_cm'], $p['berat_kg'], $p['kaki_dominan'], $p['status'], $p['telepon'],
                $p['email'], $p['tanggal_bergabung'], $p['nomor_kontrak'], $p['tanggal_selesai'], $p['gaji_pokok'],
                STATUS_KONTRAK_EFEKTIF[status_kontrak($p['sk'] ? ['status' => $p['sk'], 'tanggal_selesai' => $p['tanggal_selesai']] : null)][0]], $data));
    }

    if ($jenis === 'staf') {
        $data = db()->query(
            "SELECT u.nama_lengkap, s.*, a.nama_lengkap AS atasan, k.nomor_kontrak, k.tanggal_selesai AS kontrak_selesai
               FROM users u JOIN staf s ON s.user_id = u.id LEFT JOIN users a ON a.id = s.atasan_id
               LEFT JOIN kontrak k ON k.id = (SELECT id FROM kontrak WHERE user_id = u.id AND status = 'aktif' ORDER BY tanggal_selesai DESC LIMIT 1)
              WHERE u.role <> 'pemain' AND u.is_active = 1 ORDER BY u.nama_lengkap"
        )->fetchAll();
        catat_aktivitas('ekspor', 'staf', null, count($data) . ' baris');
        unduh_csv('data-staf-' . $tgl . '.csv',
            ['NIP', 'Nama', 'Jabatan', 'Divisi', 'Atasan', 'Status kerja', 'Bergabung', 'Telepon', 'Email', 'Lisensi', 'Lisensi berlaku', 'Kontrak', 'Kontrak berakhir'],
            array_map(static fn($s) => [$s['nip'], $s['nama_lengkap'], $s['jabatan_detail'] ?: (LABEL_JABATAN_ENUM[$s['jabatan']] ?? ''),
                LABEL_DIVISI[$s['divisi']] ?? '', $s['atasan'], LABEL_STATUS_KERJA[$s['status_kerja']][0] ?? '', $s['tanggal_bergabung'],
                $s['telepon'], $s['email'], $s['lisensi'], $s['lisensi_berlaku_sampai'], $s['nomor_kontrak'], $s['kontrak_selesai']], $data));
    }

    if ($jenis === 'kontrak') {
        $hal = (string) ($_GET['hal'] ?? 'daftar');
        $data = db()->query(
            "SELECT k.*, u.nama_lengkap, u.role FROM kontrak k JOIN users u ON u.id = k.user_id ORDER BY k.tanggal_selesai"
        )->fetchAll();
        $data = array_values(array_filter($data, static function ($k) use ($hal) {
            $s = status_kontrak($k);
            return match ($hal) {
                'hampir' => in_array($s, ['hampir', 'kedaluwarsa'], true),
                'riwayat' => true,
                default => in_array($k['status'], ['aktif', 'draft'], true),
            };
        }));
        catat_aktivitas('ekspor', 'kontrak', null, $hal . ': ' . count($data) . ' baris');
        unduh_csv('kontrak-' . $hal . '-' . $tgl . '.csv',
            ['Kode', 'Versi', 'Jenis', 'Nama', 'Kategori', 'Mulai', 'Berakhir', 'Gaji/bulan', 'Tunjangan', 'Bonus tanda tangan', 'Bonus/gol', 'Bonus/laga', 'Release clause', 'Nilai kontrak', 'Status'],
            array_map(static fn($k) => [$k['nomor_kontrak'], $k['versi'], LABEL_JENIS_KONTRAK_ADMIN[$k['jenis']] ?? $k['jenis'], $k['nama_lengkap'],
                $k['role'] === 'pemain' ? 'Pemain' : 'Staf', $k['tanggal_mulai'], $k['tanggal_selesai'], $k['gaji_pokok'], $k['tunjangan_bulanan'],
                $k['bonus_tanda_tangan'], $k['bonus_per_goal'], $k['bonus_per_main'], $k['klausul_rilis'], nilai_kontrak_admin($k),
                STATUS_KONTRAK_EFEKTIF[status_kontrak($k)][0] ?? $k['status']], $data));
    }

    if ($jenis === 'gaji') {
        $periode = (string) ($_GET['periode'] ?? '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            http_response_code(400);
            exit('Periode tidak valid.');
        }
        $st = db()->prepare(
            "SELECT p.*, u.nama_lengkap, u.role, k.nomor_kontrak FROM penggajian p JOIN users u ON u.id = p.user_id
               LEFT JOIN kontrak k ON k.id = p.kontrak_id WHERE p.periode = :p ORDER BY u.nama_lengkap"
        );
        $st->execute([':p' => $periode . '-01']);
        $data = $st->fetchAll();
        catat_aktivitas('ekspor', 'penggajian', null, $periode . ': ' . count($data) . ' baris');
        unduh_csv('penggajian-' . $periode . '.csv',
            ['Periode', 'Nama', 'Kategori', 'Kontrak', 'Gaji pokok', 'Tunjangan', 'Bonus', 'Potongan', 'Total bersih', 'Status', 'Tanggal bayar', 'Rekening'],
            array_map(static fn($p) => [$periode, $p['nama_lengkap'], $p['role'] === 'pemain' ? 'Pemain' : 'Staf', $p['nomor_kontrak'], $p['gaji_pokok'],
                $p['tunjangan'], $p['bonus'], $p['potongan'], $p['total_bersih'], $p['status'], $p['tanggal_bayar'], $p['rekening_tujuan']], $data));
    }
} catch (PDOException $ex) {
    error_log('Ekspor gagal: ' . $ex->getMessage());
    http_response_code(500);
    exit('Ekspor belum dapat dibuat. Coba lagi nanti.');
}

http_response_code(400);
exit('Jenis ekspor tidak dikenal.');
