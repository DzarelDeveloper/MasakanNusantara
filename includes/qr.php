<?php
/**
 * Local QR code rendering — no external API calls (works offline, no
 * network dependency), and no GD/XML PHP extensions required (this
 * environment has neither installed).
 *
 * Uses bacon/bacon-qr-code (via composer) purely for the QR *encoding*
 * math (Reed-Solomon error correction, masking, etc. — the part that's
 * genuinely risky to hand-roll). The actual SVG markup is built here as
 * a plain string, bypassing the library's own SVG/PNG renderers, which
 * require the xmlwriter/GD extensions this server doesn't have.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

function qrSvg(string $content, int $moduleSize = 6, int $margin = 4): string
{
    $qrCode = Encoder::encode($content, ErrorCorrectionLevel::M());
    $matrix = $qrCode->getMatrix();
    $size = $matrix->getWidth();

    $px = $size * $moduleSize + $margin * 2 * $moduleSize;

    $rects = '';
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($matrix->get($x, $y) === 1) {
                $rx = ($x + $margin) * $moduleSize;
                $ry = ($y + $margin) * $moduleSize;
                $rects .= "<rect x=\"$rx\" y=\"$ry\" width=\"$moduleSize\" height=\"$moduleSize\"/>";
            }
        }
    }

    return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 $px $px\" width=\"$px\" height=\"$px\" shape-rendering=\"crispEdges\">"
        . "<rect width=\"$px\" height=\"$px\" fill=\"#fff\"/>"
        . "<g fill=\"#000\">$rects</g>"
        . '</svg>';
}

function qrDataUri(string $content, int $moduleSize = 6, int $margin = 4): string
{
    $svg = qrSvg($content, $moduleSize, $margin);
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
