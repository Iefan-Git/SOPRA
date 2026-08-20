<?php
/**
 * includes/pdf_watermark.php
 * SOPRA — renders the login-page logo as a faint watermark for the PDF
 * exports. The exported pages are plain white, so the "transparency"
 * is faked at pixel level (blend the logo onto a white canvas at low
 * opacity, sized to exactly match the PDF page) rather than using real
 * PDF alpha — that keeps pdf_builder.php's writer simple, and the JPEG
 * can be embedded directly with the DCTDecode filter.
 *
 * Returns null (never throws) if GD isn't available or the logo is
 * missing, so exports keep working, just without the watermark.
 *
 * @return array{data:string,w:int,h:int}|null
 */
function buildPdfWatermark(float $pagePtW, float $pagePtH): ?array {
    if (!function_exists('imagecreatefrompng') || !function_exists('imagejpeg')) {
        return null;
    }
    $logoPath = __DIR__ . '/../assets/logo.png';
    if (!is_file($logoPath)) {
        return null;
    }
    $logo = @imagecreatefrompng($logoPath);
    if (!$logo) {
        return null;
    }

    $dpi = 110;
    $pxW = max(1, (int) round($pagePtW * $dpi / 72));
    $pxH = max(1, (int) round($pagePtH * $dpi / 72));

    $canvas = imagecreatetruecolor($pxW, $pxH);
    $white  = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);

    $lw = imagesx($logo);
    $lh = imagesy($logo);

    // Flatten the PNG's own alpha onto white first, so semi-transparent
    // logo pixels blend cleanly instead of turning grey/black.
    $flat = imagecreatetruecolor($lw, $lh);
    $fwhite = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $fwhite);
    imagealphablending($flat, true);
    imagecopy($flat, $logo, 0, 0, 0, 0, $lw, $lh);
    imagedestroy($logo);

    // Scale to ~55% of the smaller page dimension, centered.
    $target = min($pxW, $pxH) * 0.55;
    $scale  = $target / max($lw, $lh);
    $dw = max(1, (int) round($lw * $scale));
    $dh = max(1, (int) round($lh * $scale));
    $dx = (int) round(($pxW - $dw) / 2);
    $dy = (int) round(($pxH - $dh) / 2);

    $resized = imagecreatetruecolor($dw, $dh);
    imagecopyresampled($resized, $flat, 0, 0, 0, 0, $dw, $dh, $lw, $lh);
    imagedestroy($flat);

    // Low-opacity merge onto the white canvas — this is the "watermark" effect.
    imagecopymerge($canvas, $resized, $dx, $dy, 0, 0, $dw, $dh, 10);
    imagedestroy($resized);

    ob_start();
    imagejpeg($canvas, null, 85);
    $data = ob_get_clean();
    imagedestroy($canvas);

    if (!$data) {
        return null;
    }
    return ['data' => $data, 'w' => $pxW, 'h' => $pxH];
}
