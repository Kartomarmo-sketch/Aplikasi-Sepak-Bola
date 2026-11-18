<?php
declare(strict_types=1);

// Ekspor jadwal 90 hari ke depan sebagai file iCalendar (.ics) untuk Google Calendar / Outlook / ponsel.

require __DIR__ . '/tampilan.php';

start_secure_session();
require_login();

$pemain = ($_SESSION['role'] ?? '') === 'pemain';
$zona = new DateTimeZone(ZONA_WAKTU);
$utc = new DateTimeZone('UTC');
$sekarang = new DateTimeImmutable('now', $zona);

try {
    $sql = "SELECT id, jenis, judul, lokasi, waktu_mulai, waktu_selesai, deskripsi
              FROM jadwal
             WHERE COALESCE(waktu_selesai, waktu_mulai) >= :dari AND waktu_mulai < :sampai"
         . ($pemain ? " AND peserta <> 'staf'" : '')
         . " ORDER BY waktu_mulai";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':dari' => $sekarang->format('Y-m-d 00:00:00'),
        ':sampai' => $sekarang->modify('+90 days')->format('Y-m-d 23:59:59'),
    ]);
    $agenda = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Ekspor ICS gagal: ' . $ex->getMessage());
    http_response_code(500);
    exit('Jadwal belum dapat diekspor.');
}

/** Escape teks iCalendar (RFC 5545) + lipat baris 75 oktet. */
function ics_teks(string $s): string
{
    return strtr($s, ['\\' => '\\\\', ';' => '\;', ',' => '\,', "\r\n" => '\n', "\n" => '\n']);
}
function ics_baris(string $baris): string
{
    $hasil = '';
    while (strlen($baris) > 75) {
        $potong = 75;
        // Jangan memotong di tengah karakter UTF-8.
        while ($potong > 0 && (ord($baris[$potong]) & 0xC0) === 0x80) {
            $potong--;
        }
        $hasil .= substr($baris, 0, $potong) . "\r\n ";
        $baris = substr($baris, $potong);
    }
    return $hasil . $baris . "\r\n";
}
function ics_waktu(string $waktu, DateTimeZone $zona, DateTimeZone $utc): string
{
    return (new DateTimeImmutable($waktu, $zona))->setTimezone($utc)->format('Ymd\THis\Z');
}

$host = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
$isi = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//" . NAMA_KLUB . "//Sistem SDM//ID\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n"
     . ics_baris('X-WR-CALNAME:' . ics_teks('Jadwal ' . NAMA_KLUB)) . "X-WR-TIMEZONE:" . ZONA_WAKTU . "\r\n";
foreach ($agenda as $a) {
    $selesai = $a['waktu_selesai'] ?: (new DateTimeImmutable((string) $a['waktu_mulai']))->modify('+2 hours')->format('Y-m-d H:i:s');
    $isi .= "BEGIN:VEVENT\r\n"
        . 'UID:jadwal-' . (int) $a['id'] . '@' . $host . "\r\n"
        . 'DTSTAMP:' . $sekarang->setTimezone($utc)->format('Ymd\THis\Z') . "\r\n"
        . 'DTSTART:' . ics_waktu((string) $a['waktu_mulai'], $zona, $utc) . "\r\n"
        . 'DTEND:' . ics_waktu((string) $selesai, $zona, $utc) . "\r\n"
        . ics_baris('SUMMARY:' . ics_teks((string) $a['judul']))
        . ($a['lokasi'] !== '' ? ics_baris('LOCATION:' . ics_teks((string) $a['lokasi'])) : '')
        . ($a['deskripsi'] ? ics_baris('DESCRIPTION:' . ics_teks((string) $a['deskripsi'])) : '')
        . 'CATEGORIES:' . strtoupper((string) $a['jenis']) . "\r\n"
        . "END:VEVENT\r\n";
}
$isi .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="jadwal-klub.ics"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
echo $isi;
