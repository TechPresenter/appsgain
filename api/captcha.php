<?php
/**
 * Appsgain — SVG CAPTCHA Generator (No GD required)
 * Colorful, distorted SVG CAPTCHA stored in session
 */

/* Start session safely — without bootstrap to avoid conflicts */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
/* Regenerating a CAPTCHA is cheap but not free, and unbounded requests
   are a way to burn CPU. */
if (function_exists('rlHit')) {
    $rl = rlHit('api.captcha', 60, 600, 600);
    if (!$rl['allowed']) { http_response_code(429); header('Retry-After: ' . $rl['retry_after']); exit; }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

/* ── Generate random 6-char code ── */
$chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
$code   = '';
for ($i = 0; $i < 6; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha_code']    = $code; /* case-sensitive: store original case */
$_SESSION['captcha_expires'] = time() + 300;

/* ── Color palette ── */
$colors = [
    '#0f4bb5', // deep blue
    '#c45000', // orange
    '#3a6e00', // olive green
    '#8c1414', // dark red
    '#006478', // teal
    '#6a1c8c', // purple
    '#9e7200', // amber
    '#c4006e', // pink
];

/* ── Build SVG ── */
$w = 220; $h = 72;
$svg  = '<?xml version="1.0" encoding="UTF-8"?>';
$svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">';

/* Background */
$svg .= '<rect width="'.$w.'" height="'.$h.'" fill="#fefde8" rx="4"/>';

/* Grid lines */
for ($x = 0; $x <= $w; $x += 16) {
    $svg .= '<line x1="'.$x.'" y1="0" x2="'.$x.'" y2="'.$h.'" stroke="#c8c690" stroke-width="0.5" opacity="0.7"/>';
}
for ($y = 0; $y <= $h; $y += 16) {
    $svg .= '<line x1="0" y1="'.$y.'" x2="'.$w.'" y2="'.$y.'" stroke="#c8c690" stroke-width="0.5" opacity="0.7"/>';
}

/* Random distraction lines */
$lineColors = ['#f8b878','#a8c888','#c0a0d8','#d4c870'];
for ($n = 0; $n < 5; $n++) {
    $lc  = $lineColors[$n % count($lineColors)];
    $x1  = random_int(0, intval($w * 0.4));
    $y1  = random_int(5, $h - 5);
    $x2  = random_int(intval($w * 0.6), $w);
    $y2  = random_int(5, $h - 5);
    $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$lc.'" stroke-width="1.5" opacity="0.6"/>';
}

/* Characters */
$fonts = [
    "'Georgia', serif",
    "'Arial Black', sans-serif",
    "'Trebuchet MS', sans-serif",
    "'Times New Roman', serif",
    "'Verdana', sans-serif",
    "'Comic Sans MS', cursive",
];

$charWidthUnit = intval(($w - 20) / 6);
for ($i = 0; $i < 6; $i++) {
    $char      = $code[$i];
    $color     = $colors[$i % count($colors)];
    $font      = $fonts[$i % count($fonts)];
    $x         = 14 + ($i * $charWidthUnit) + random_int(-2, 4);
    $y         = random_int(42, 52);
    $fontSize  = random_int(24, 30);
    $rotate    = random_int(-22, 22);
    $cx        = $x + intval($charWidthUnit / 2);
    $cy        = $y - intval($fontSize / 2);

    $svg .= '<text'
        . ' x="'.$x.'"'
        . ' y="'.$y.'"'
        . ' font-family="'.$font.'"'
        . ' font-size="'.$fontSize.'"'
        . ' font-weight="bold"'
        . ' fill="'.$color.'"'
        . ' transform="rotate('.$rotate.','.$cx.','.$cy.')"'
        . ' letter-spacing="1"'
        . '>'
        . htmlspecialchars($char, ENT_XML1)
        . '</text>';

    /* Subtle shadow/outline for depth */
    $svg .= '<text'
        . ' x="'.($x+1).'"'
        . ' y="'.($y+1).'"'
        . ' font-family="'.$font.'"'
        . ' font-size="'.$fontSize.'"'
        . ' font-weight="bold"'
        . ' fill="rgba(0,0,0,0.15)"'
        . ' transform="rotate('.$rotate.','.$cx.','.$cy.')"'
        . ' letter-spacing="1"'
        . '>'
        . htmlspecialchars($char, ENT_XML1)
        . '</text>';
}

/* Noise dots */
for ($d = 0; $d < 30; $d++) {
    $nx  = random_int(2, $w - 2);
    $ny  = random_int(2, $h - 2);
    $nr  = random_int(1, 2);
    $dc  = $colors[random_int(0, count($colors) - 1)];
    $svg .= '<circle cx="'.$nx.'" cy="'.$ny.'" r="'.$nr.'" fill="'.$dc.'" opacity="0.35"/>';
}

$svg .= '</svg>';

/* ── Output ── */
header('Content-Type: image/svg+xml');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('X-CAPTCHA-Length: 6');

echo $svg;
