<?php
declare(strict_types=1);

/**
 * Pembuat PDF satu halaman yang sangat sederhana (tanpa library tambahan).
 * Mendukung teks Helvetica / Helvetica-Bold, garis, dan kotak berwarna.
 * Koordinat dalam point (1/72 inci), titik (0,0) di KIRI ATAS halaman agar mudah dipakai.
 */
final class PdfSederhana
{
    private const LEBAR_HELVETICA = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];
    private const LEBAR_TEBAL = [
        278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611,
        975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556,
        333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611,
        611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584,
    ];

    private string $isi = '';

    public function __construct(private float $lebar = 595.28, private float $tinggi = 841.89)
    {
    }

    /** UTF-8 -> Windows-1252 (encoding font standar PDF). */
    private static function kodekan(string $teks): string
    {
        $hasil = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $teks);
        return $hasil === false ? preg_replace('/[^\x20-\x7E]/', '?', $teks) : $hasil;
    }

    /** Lebar teks dalam point. */
    public function lebarTeks(string $teks, float $ukuran, bool $tebal = false): float
    {
        $tabel = $tebal ? self::LEBAR_TEBAL : self::LEBAR_HELVETICA;
        $total = 0;
        foreach (str_split(self::kodekan($teks)) as $c) {
            $o = ord($c);
            $total += ($o >= 32 && $o <= 126) ? $tabel[$o - 32] : 556;
        }
        return $total * $ukuran / 1000;
    }

    private static function warna(array $rgb): string
    {
        return sprintf('%.3F %.3F %.3F', $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255);
    }

    /** $rata: kiri | kanan | tengah (x = titik acuan). */
    public function teks(float $x, float $y, string $teks, float $ukuran = 10, bool $tebal = false,
                         array $warna = [20, 32, 26], string $rata = 'kiri'): void
    {
        if ($rata !== 'kiri') {
            $w = $this->lebarTeks($teks, $ukuran, $tebal);
            $x -= $rata === 'kanan' ? $w : $w / 2;
        }
        $aman = strtr(self::kodekan($teks), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
        $this->isi .= sprintf("BT %s rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            self::warna($warna), $tebal ? 'F2' : 'F1', $ukuran, $x, $this->tinggi - $y, $aman);
    }

    public function garis(float $x1, float $y1, float $x2, float $y2, array $warna = [219, 228, 221], float $tebal = 0.8): void
    {
        $this->isi .= sprintf("%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            self::warna($warna), $tebal, $x1, $this->tinggi - $y1, $x2, $this->tinggi - $y2);
    }

    public function kotak(float $x, float $y, float $w, float $h, array $isi): void
    {
        $this->isi .= sprintf("%s rg %.2F %.2F %.2F %.2F re f\n",
            self::warna($isi), $x, $this->tinggi - $y - $h, $w, $h);
    }

    /** Teks panjang dipotong per baris sesuai lebar; mengembalikan y setelah baris terakhir. */
    public function paragraf(float $x, float $y, float $lebar, string $teks, float $ukuran = 9,
                             array $warna = [90, 106, 97], float $jarak = 1.4): float
    {
        $baris = '';
        foreach (preg_split('/\s+/', trim($teks)) as $kata) {
            $coba = $baris === '' ? $kata : $baris . ' ' . $kata;
            if ($this->lebarTeks($coba, $ukuran) > $lebar && $baris !== '') {
                $this->teks($x, $y, $baris, $ukuran, false, $warna);
                $y += $ukuran * $jarak;
                $baris = $kata;
            } else {
                $baris = $coba;
            }
        }
        if ($baris !== '') {
            $this->teks($x, $y, $baris, $ukuran, false, $warna);
            $y += $ukuran * $jarak;
        }
        return $y;
    }

    /** Susun berkas PDF lengkap. */
    public function hasil(string $judul = 'Dokumen'): string
    {
        $obj = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
                $this->lebar, $this->tinggi),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Length ' . strlen($this->isi) . " >>\nstream\n" . $this->isi . "endstream",
            '<< /Title (' . strtr(self::kodekan($judul), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']) . ') /Producer (Sistem SDM Klub) >>',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posisi = [];
        foreach ($obj as $i => $o) {
            $posisi[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $o . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($obj) + 1) . "\n0000000000 65535 f \n";
        foreach ($posisi as $p) {
            $pdf .= sprintf("%010d 00000 n \n", $p);
        }
        $pdf .= "trailer\n<< /Size " . (count($obj) + 1) . " /Root 1 0 R /Info 7 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }
}
