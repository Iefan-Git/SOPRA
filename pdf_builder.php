<?php
/**
 * includes/pdf_builder.php
 * SOPRA — a tiny, dependency-free PDF writer used by exports/duty_export.php
 * and exports/payment_export.php.
 *
 * It only implements the small slice of the PDF spec those reports need:
 * multi-page documents, Helvetica / Helvetica-Bold text (standard fonts,
 * no embedding required), straight lines/filled rectangles for table
 * grids, and a single shared JPEG image reused as a background watermark
 * on every page. It is not a general-purpose PDF library.
 */
class MiniPdf
{
    /** @var array<int,string> finished PDF object bodies, keyed by object id */
    private array $objects = [];
    private int $nextId = 1;

    /** @var array<int,array{w:float,h:float,content:string}> */
    private array $pages = [];
    private int $activePage = -1;

    private int $fontRegId;
    private int $fontBoldId;

    private ?int $wmId = null;

    public function __construct()
    {
        $this->fontRegId  = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->fontBoldId = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
    }

    private function addObject(string $body): int
    {
        $id = $this->nextId++;
        $this->objects[$id] = $body;
        return $id;
    }

    /** Registers the pre-rendered watermark JPEG once; every page added after this gets it stamped automatically. */
    public function setWatermark(string $jpegData, int $pxW, int $pxH): void
    {
        $body = "<< /Type /XObject /Subtype /Image /Width $pxW /Height $pxH "
              . '/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpegData) . " >>\nstream\n"
              . $jpegData . "\nendstream";
        $this->wmId = $this->addObject($body);
    }

    public function addPage(float $w = 595.28, float $h = 841.89): void
    {
        $this->pages[] = ['w' => $w, 'h' => $h, 'content' => ''];
        $this->activePage = count($this->pages) - 1;
        if ($this->wmId !== null) {
            $this->pages[$this->activePage]['content'] .= "q $w 0 0 $h 0 0 cm /Wm Do Q\n";
        }
    }

    public function pageWidth(): float
    {
        return $this->activePage >= 0 ? $this->pages[$this->activePage]['w'] : 0.0;
    }

    public function pageHeight(): float
    {
        return $this->activePage >= 0 ? $this->pages[$this->activePage]['h'] : 0.0;
    }

    private function esc(string $s): string
    {
        // Bring UTF-8 input down to a byte string the standard
        // WinAnsiEncoding font can render, then escape the PDF
        // string-literal special characters.
        $converted = false;
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            if ($conv !== false) { $s = $conv; $converted = true; }
        }
        if (!$converted && function_exists('mb_convert_encoding')) {
            $conv = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if ($conv !== false) { $s = $conv; }
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    /** @param array{0:float,1:float,2:float}|null $rgb 0..1 components; null = black */
    public function text(float $x, float $y, string $str, float $size = 9, bool $bold = false, ?array $rgb = null): void
    {
        if ($this->activePage < 0 || $str === '') return;
        $font = $bold ? 'F2' : 'F1';
        $c = '';
        if ($rgb !== null) { $c = sprintf('%.3F %.3F %.3F rg ', $rgb[0], $rgb[1], $rgb[2]); }
        $this->pages[$this->activePage]['content'] .=
            "BT $c/$font $size Tf $x $y Td (" . $this->esc($str) . ") Tj ET\n";
        if ($rgb !== null) {
            // Reset fill colour to black so it doesn't bleed into whatever draws next.
            $this->pages[$this->activePage]['content'] .= "0 0 0 rg\n";
        }
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5, float $gray = 0.6): void
    {
        if ($this->activePage < 0) return;
        $this->pages[$this->activePage]['content'] .=
            sprintf("%.2F w %.2F G %.2F %.2F m %.2F %.2F l S\n", $width, $gray, $x1, $y1, $x2, $y2);
    }

    public function rectFill(float $x, float $y, float $w, float $h, float $gray = 0.9): void
    {
        if ($this->activePage < 0) return;
        $this->pages[$this->activePage]['content'] .=
            sprintf("%.2F g %.2F %.2F %.2F %.2F re f\n0 g\n", $gray, $x, $y, $w, $h);
    }

    public function output(string $filename): void
    {
        // Build the Pages tree + per-page content-stream objects now
        // that every page is finished.
        $pageIds = [];
        $pagesObjId = $this->nextId++; // reserved now, filled in below

        foreach ($this->pages as $p) {
            $contentId = $this->addObject('<< /Length ' . strlen($p['content']) . " >>\nstream\n" . $p['content'] . "\nendstream");
            $resDict = '<< /Font << /F1 ' . $this->fontRegId . ' 0 R /F2 ' . $this->fontBoldId . ' 0 R >>'
                     . ($this->wmId !== null ? ' /XObject << /Wm ' . $this->wmId . ' 0 R >>' : '')
                     . ' >>';
            $pageId = $this->addObject(
                '<< /Type /Page /Parent ' . $pagesObjId . ' 0 R /MediaBox [0 0 ' . $p['w'] . ' ' . $p['h'] . '] '
                . '/Resources ' . $resDict . ' /Contents ' . $contentId . ' 0 R >>'
            );
            $pageIds[] = $pageId;
        }

        $kids = implode(' ', array_map(fn($id) => "$id 0 R", $pageIds));
        $this->objects[$pagesObjId] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';

        $catalogId = $this->addObject('<< /Type /Catalog /Pages ' . $pagesObjId . ' 0 R >>');

        // ---- Assemble the file ----
        $out = "%PDF-1.4\n";
        ksort($this->objects);
        $offsets = [];
        foreach ($this->objects as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= "$id 0 obj\n$body\nendobj\n";
        }
        $xrefStart = strlen($out);
        $maxId = max(array_keys($this->objects));
        $out .= "xref\n0 " . ($maxId + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $out .= "trailer\n<< /Size " . ($maxId + 1) . ' /Root ' . $catalogId . " 0 R >>\n";
        $out .= "startxref\n$xrefStart\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($out));
        echo $out;
        exit;
    }
}
