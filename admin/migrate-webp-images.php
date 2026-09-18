<?php
/**
 * Convert uploaded images to WebP and repoint the database at them.
 *
 * Safe to run repeatedly. Phases are separate invocations on purpose:
 * files are converted in one run and the database is repointed in another,
 * so a row is only ever moved from a file that exists to a file that
 * exists. Originals are never deleted here — --archive-originals is a
 * separate step for after sign-off.
 *
 *   php admin/migrate-webp-images.php                 # dry run, the default
 *   php admin/migrate-webp-images.php --convert
 *   php admin/migrate-webp-images.php --rewrite-db
 *   php admin/migrate-webp-images.php --verify
 *   php admin/migrate-webp-images.php --rollback
 *   php admin/migrate-webp-images.php --archive-originals
 *
 * Flags: --force  --limit=N  --only=<dir>  --orphans=convert
 *
 * On the server without a shell, sign in as an admin and use the same names
 * as query parameters: ?convert=1&limit=20
 */

$cli = (PHP_SAPI === 'cli');
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!$cli) { Auth::requireAdmin(); header('Content-Type: text/plain; charset=utf-8'); }

@ini_set('memory_limit', '256M');
@set_time_limit($cli ? 0 : 120);

/* ── Options ───────────────────────────────────────────────────────── */
$opt = static function (string $name, $default = false) use ($cli) {
    if ($cli) {
        foreach ($_SERVER['argv'] as $a) {
            if ($a === "--$name") return true;
            if (str_starts_with($a, "--$name=")) return substr($a, strlen($name) + 3);
        }
        return $default;
    }
    return $_GET[$name] ?? $default;
};

$doConvert  = (bool)$opt('convert');
$doRewrite  = (bool)$opt('rewrite-db');
$doVerify   = (bool)$opt('verify');
$doRollback = (bool)$opt('rollback');
$doArchive  = (bool)$opt('archive-originals');
$force      = (bool)$opt('force');
$limit      = (int)$opt('limit', 0);
$only       = (string)$opt('only', '');
$orphanMode = (string)$opt('orphans', 'report');
$dryRun     = !($doConvert || $doRewrite || $doVerify || $doRollback || $doArchive);

/* ── Target box per column or setting key. Never upscale, never crop, so
      aspect ratio is invariant and every object-fit / max-height rule
      renders the same box it does today. ──────────────────────────────── */
const WEBP_BOXES = [
    'hero_slides.background_image'   => [1920, 1200],
    'blogs.featured_image'           => [1600, 1000],
    'services.image'                 => [1200,  750],
    'service_sections.image'         => [1600, 1200],
    'products.banner_image'          => [1600,  900],
    'products.logo'                  => [ 400,  400],
    'product_screenshots.screenshot_image' => [1080, 1920],
    'apps.app_icon'                  => [ 512,  512],
    'app_screenshots.screenshot_image'     => [1080, 1920],
    'team_members.photo'             => [ 800, 1000],
    'testimonials.photo'             => [ 256,  256],
    'partners.logo'                  => [ 400,  400],
    'clients.logo'                   => [ 400,  400],
    'certifications_partners.logo'   => [ 400,  400],
    'galleries.cover_image'          => [1600, 1200],
    'gallery_items.file_path'        => [1600, 1200],
    'media.file_path'                => [1600, 1200],
    'founder_gallery.image'          => [1600, 1200],
    'founder_sections.image'         => [1600, 1200],
    'projects.featured_image'        => [1200,  800],
    'projects.thumbnail'             => [1200,  800],
    'pages.banner_image'             => [1600, 1200],
    'page_sections.image'            => [1600, 1200],
    'popups.image'                   => [1200,  900],
    'users.avatar'                   => [ 256,  256],
    'courses.image'                  => [1200,  800],
    'placed_students.photo'          => [ 400,  400],
    'placed_students.company_logo'   => [ 400,  400],
    /* settings rows, keyed by setting_key */
    'settings.founder_photo'         => [ 800, 1000],
    'settings.sp_hero_image'         => [1800, 1200],
    'settings.chatbot_avatar'        => [ 128,  128],
    'settings.chatbot_launcher_icon' => [ 128,  128],
    'settings.footer_gplay_icon'     => [ 128,  128],
    'settings.footer_appstore_icon'  => [ 128,  128],
];
const WEBP_DIR_FALLBACK = [
    'hero' => [1920, 1200], 'avatars' => [256, 256], 'ai-icons' => [128, 128],
    'team' => [800, 1000], 'testimonials' => [256, 256], 'partners' => [400, 400],
    'clients' => [400, 400], 'products' => [1600, 900], 'apps' => [512, 512],
    'gallery' => [1600, 1200], 'blog' => [1600, 1000], 'services' => [1200, 750],
    'about' => [1600, 1200], 'sections' => [1800, 1200], 'settings' => [1024, 1024],
];

/* Columns holding a relative uploads path. resumes and remote-URL columns
   are absent on purpose. */
const WEBP_COLUMNS = [
    ['blogs', 'featured_image'], ['services', 'image'], ['service_sections', 'image'],
    ['products', 'logo'], ['products', 'banner_image'],
    ['product_screenshots', 'screenshot_image'], ['apps', 'app_icon'],
    ['app_screenshots', 'screenshot_image'], ['team_members', 'photo'],
    ['testimonials', 'photo'], ['partners', 'logo'], ['clients', 'logo'],
    ['certifications_partners', 'logo'], ['galleries', 'cover_image'],
    ['gallery_items', 'file_path'], ['hero_slides', 'background_image'],
    ['founder_gallery', 'image'], ['founder_sections', 'image'],
    ['projects', 'featured_image'], ['projects', 'thumbnail'],
    ['pages', 'banner_image'], ['page_sections', 'image'], ['popups', 'image'],
    ['users', 'avatar'], ['courses', 'image'],
    ['placed_students', 'photo'], ['placed_students', 'company_logo'],
    ['media', 'file_path'],
];

/* Never converted. Logos and social images stay as they are: WhatsApp,
   Facebook and LinkedIn scrapers still handle a WebP og:image
   inconsistently, and a blank share card is a silent failure. */
const WEBP_PINNED_SETTINGS = [
    'site_logo', 'site_logo_light', 'site_logo_icon', 'site_favicon', 'og_default_image',
];
const WEBP_PINNED_DIRS = ['resumes', 'logo'];

$ROOT = rtrim(UPLOADS_PATH, '/\\');
$out  = [];
$say  = static function (string $s) { echo $s, "\n"; @flush(); };

/* ── Helpers ───────────────────────────────────────────────────────── */

function wpPinned(string $rel): bool {
    $first = strtok(ltrim($rel, '/'), '/');
    return in_array($first, WEBP_PINNED_DIRS, true);
}

/** Is this PNG animated? GD silently flattens APNG to frame one. */
function wpIsApng(string $path): bool {
    $h = @file_get_contents($path, false, null, 0, 65536);
    if ($h === false) return false;
    $idat = strpos($h, 'IDAT');
    $actl = strpos($h, 'acTL');
    return $actl !== false && ($idat === false || $actl < $idat);
}

/** Rough perceptual difference, 0 = identical. Catches rotation, alpha loss
 *  and flattened animation — the ways a conversion changes the design
 *  without anyone noticing until it is live. */
function wpRmse($a, $b): float {
    $n = 64; $sum = 0;
    $ta = imagecreatetruecolor($n, $n); $tb = imagecreatetruecolor($n, $n);
    imagecopyresampled($ta, $a, 0, 0, 0, 0, $n, $n, imagesx($a), imagesy($a));
    imagecopyresampled($tb, $b, 0, 0, 0, 0, $n, $n, imagesx($b), imagesy($b));
    for ($y = 0; $y < $n; $y++) for ($x = 0; $x < $n; $x++) {
        $p = imagecolorat($ta, $x, $y); $q = imagecolorat($tb, $x, $y);
        $g1 = (($p >> 16 & 255) * 299 + ($p >> 8 & 255) * 587 + ($p & 255) * 114) / 1000;
        $g2 = (($q >> 16 & 255) * 299 + ($q >> 8 & 255) * 587 + ($q & 255) * 114) / 1000;
        $sum += ($g1 - $g2) ** 2;
    }
    imagedestroy($ta); imagedestroy($tb);
    return sqrt($sum / ($n * $n));
}

/** Decode, honouring EXIF orientation for JPEG. */
function wpLoad(string $path, int $type) {
    $im = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        default        => false,
    };
    if (!$im) return false;
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $e = @exif_read_data($path);
        $o = (int)($e['Orientation'] ?? 1);
        /* Browsers honour EXIF orientation on JPEG but not on WebP, so it
           has to be baked in before encoding or the photo lands sideways. */
        if ($o >= 2) {
            if (in_array($o, [2, 5, 7], true)) imageflip($im, IMG_FLIP_HORIZONTAL);
            $deg = match ($o) { 3, 4 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
            if ($deg) { $r = imagerotate($im, $deg, 0); if ($r) { imagedestroy($im); $im = $r; } }
        }
    }
    return $im;
}

/**
 * Convert one file. Returns a report row; only writes when $write is true.
 */
function wpConvert(string $abs, string $rel, array $box, bool $write, bool $force): array {
    $r = ['rel' => $rel, 'bytes' => @filesize($abs) ?: 0, 'decision' => 'skip', 'reason' => ''];

    if (wpPinned($rel))                { $r['reason'] = 'pinned directory';    return $r; }
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) { $r['reason'] = "not convertible ($ext)"; return $r; }
    if (!is_file($abs))                { $r['reason'] = 'missing on disk';     return $r; }

    $relWebp = preg_replace('/\.(png|jpe?g)$/i', '.webp', $rel);
    $absWebp = dirname($abs) . '/' . basename($relWebp);
    $r['new'] = $relWebp;

    if (!$force && is_file($absWebp) && filemtime($absWebp) >= filemtime($abs)) {
        $r['decision'] = 'done'; $r['reason'] = 'already converted';
        $r['newBytes'] = filesize($absWebp);
        return $r;
    }

    $info = @getimagesize($abs);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        $r['reason'] = 'unreadable or unexpected type'; return $r;
    }
    [$w, $h, $type] = $info;
    $r['dims'] = "{$w}x{$h}";

    if ($type === IMAGETYPE_PNG && wpIsApng($abs)) { $r['reason'] = 'animated png'; return $r; }
    if ($type === IMAGETYPE_JPEG && !function_exists('exif_read_data')) {
        $r['reason'] = 'no exif extension; refusing to guess orientation'; return $r;
    }

    /* Fit inside the box. Never upscale, never crop. */
    $scale = min(1.0, $box[0] / $w, $box[1] / $h);
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    if ($scale === 1.0 && $r['bytes'] < 20480) { $r['reason'] = 'already small'; return $r; }
    $r['newDims'] = "{$nw}x{$nh}";

    $src = wpLoad($abs, $type);
    if (!$src) { $r['reason'] = 'decode failed'; return $r; }

    try {
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));

        /* PNG: try lossy and lossless, keep the smaller. Photographic PNGs
           pick lossy; flat logos pick lossless and stay pixel-identical. */
        $cands = [];
        $tmpA = $absWebp . '.tmpA';
        if (@imagewebp($dst, $tmpA, 82)) $cands[] = $tmpA;
        if ($type === IMAGETYPE_PNG) {
            $tmpB = $absWebp . '.tmpB';
            if (@imagewebp($dst, $tmpB, IMG_WEBP_LOSSLESS)) $cands[] = $tmpB;
        }
        if (!$cands) { $r['reason'] = 'encode failed'; return $r; }

        usort($cands, static fn($x, $y) => filesize($x) <=> filesize($y));
        $best = array_shift($cands);
        foreach ($cands as $c) @unlink($c);

        $newBytes = filesize($best);
        $r['newBytes'] = $newBytes;

        if ($newBytes >= $r['bytes'] * 0.90) {
            @unlink($best); $r['reason'] = 'no worthwhile gain'; return $r;
        }

        $chk = @imagecreatefromwebp($best);
        if (!$chk) { @unlink($best); $r['reason'] = 'output not decodable'; return $r; }
        $rmse = wpRmse($src, $chk);
        imagedestroy($chk);
        $r['rmse'] = round($rmse, 2);
        if ($rmse > 6.0) { @unlink($best); $r['reason'] = "visual drift rmse {$r['rmse']}"; return $r; }

        if (!$write) { @unlink($best); $r['decision'] = 'would convert'; return $r; }

        if (!@rename($best, $absWebp)) { @unlink($best); $r['reason'] = 'rename failed'; return $r; }
        @chmod($absWebp, 0644);
        $r['decision'] = 'converted';
        return $r;
    } finally {
        if (isset($dst) && $dst) imagedestroy($dst);
        imagedestroy($src);
    }
}

/* ── Gather every referenced image path ────────────────────────────── */
function wpGatherRefs(): array {
    $refs = [];
    foreach (WEBP_COLUMNS as [$t, $c]) {
        try {
            $rows = dbFetchAll("SELECT id, `$c` AS v FROM `$t` WHERE `$c` IS NOT NULL AND `$c` <> ''");
        } catch (Throwable $e) { continue; }
        foreach ($rows as $row) {
            $v = trim((string)$row['v']);
            if ($v === '' || str_starts_with($v, 'http') || str_starts_with($v, 'data:')) continue;
            $refs[] = ['table' => $t, 'col' => $c, 'pk' => (int)$row['id'], 'val' => $v,
                       'key' => "$t.$c"];
        }
    }
    /* settings rows are keyed by name, and there are 300+ of them, so match
       on the value looking like a relative image path rather than on a list
       somebody has to remember to update. */
    try {
        $rows = dbFetchAll("SELECT id, setting_key, setting_value FROM settings
                            WHERE setting_value REGEXP '\\\\.(png|jpe?g)$'");
    } catch (Throwable $e) { $rows = []; }
    foreach ($rows as $row) {
        $v = trim((string)$row['setting_value']);
        if ($v === '' || str_starts_with($v, 'http')) continue;
        if (in_array($row['setting_key'], WEBP_PINNED_SETTINGS, true)) continue;
        $refs[] = ['table' => 'settings', 'col' => 'setting_value', 'pk' => (int)$row['id'],
                   'val' => $v, 'key' => 'settings.' . $row['setting_key']];
    }
    return $refs;
}

function wpBox(string $key, string $rel): array {
    if (isset(WEBP_BOXES[$key])) return WEBP_BOXES[$key];
    $dir = strtok(ltrim($rel, '/'), '/');
    return WEBP_DIR_FALLBACK[$dir] ?? [1600, 1600];
}

function wpFmt(int $b): string {
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : round($b / 1024) . ' KB';
}

/* ═══════════════════════════════════════════════════════════════════ */

$say(str_repeat('=', 74));
$say('WebP migration  ·  ' . date('Y-m-d H:i:s') . '  ·  ' . ($cli ? 'cli' : 'web'));
$mode = $dryRun ? 'DRY RUN (nothing is written)'
      : ($doConvert ? 'CONVERT' : ($doRewrite ? 'REWRITE DB' : ($doVerify ? 'VERIFY'
      : ($doRollback ? 'ROLLBACK' : 'ARCHIVE ORIGINALS'))));
$say('Mode: ' . $mode);
$say(str_repeat('=', 74));

if (!function_exists('imagewebp')) {
    $say('FATAL: this PHP has no GD WebP support. Nothing to do.');
    exit(1);
}

$journalDir = ROOT_PATH . '/logs';
if (!is_dir($journalDir)) @mkdir($journalDir, 0755, true);
$journalLatest = $journalDir . '/webp-migration-latest.json';

/* ── ROLLBACK ──────────────────────────────────────────────────────── */
if ($doRollback) {
    $file = is_string($opt('rollback')) && $opt('rollback') !== '1'
          ? $journalDir . '/' . basename((string)$opt('rollback')) : $journalLatest;
    if (!is_file($file)) { $say("No journal at $file"); exit(1); }
    $j = json_decode((string)file_get_contents($file), true);
    $writes = $j['writes'] ?? [];
    if (!$writes) { $say('Journal holds no database writes; nothing to undo.'); exit(0); }
    db()->beginTransaction();
    try {
        foreach ($writes as $w) {
            dbExecute("UPDATE `{$w['table']}` SET `{$w['col']}` = ? WHERE id = ?", [$w['old'], $w['pk']]);
        }
        db()->commit();
        $say('Restored ' . count($writes) . ' rows. Files were left alone.');
    } catch (Throwable $e) { db()->rollBack(); $say('ROLLED BACK: ' . $e->getMessage()); exit(1); }
    exit(0);
}

/* ── Build the work list ───────────────────────────────────────────── */
$refs = wpGatherRefs();
if ($only !== '') {
    $refs = array_values(array_filter($refs, static fn($r) =>
        str_starts_with(ltrim($r['val'], '/'), $only . '/') || $r['key'] === $only));
}
$say('Referenced images in the database: ' . count($refs));

/* ── VERIFY ────────────────────────────────────────────────────────── */
if ($doVerify) {
    $missing = 0; $totalBytes = 0; $byDir = [];
    foreach ($refs as $r) {
        $abs = $ROOT . '/' . ltrim($r['val'], '/');
        if (!is_file($abs)) { $say("  MISSING  {$r['key']}#{$r['pk']}  {$r['val']}"); $missing++; continue; }
        $b = filesize($abs); $totalBytes += $b;
        $byDir[strtok(ltrim($r['val'], '/'), '/')] ??= 0;
        $byDir[strtok(ltrim($r['val'], '/'), '/')] += $b;
    }
    $say('');
    foreach ($byDir as $d => $b) $say(sprintf('  %-14s %10s', $d, wpFmt($b)));
    $say(sprintf('  %-14s %10s', 'TOTAL', wpFmt($totalBytes)));
    $say('');
    $say($missing === 0
        ? 'Every referenced image resolves to a file on disk.'
        : "FAIL: $missing rows point at a file that does not exist.");
    exit($missing === 0 ? 0 : 1);
}

/* ── ARCHIVE ORIGINALS ─────────────────────────────────────────────── */
if ($doArchive) {
    $j = is_file($journalLatest) ? json_decode((string)file_get_contents($journalLatest), true) : [];
    $moved = 0;
    foreach (($j['converted'] ?? []) as $c) {
        $src = $ROOT . '/' . ltrim($c['rel'], '/');
        if (!is_file($src)) continue;
        $dst = $ROOT . '/_originals/' . ltrim($c['rel'], '/');
        if (!is_dir(dirname($dst))) @mkdir(dirname($dst), 0755, true);
        if (@rename($src, $dst)) $moved++;
    }
    $say("Archived $moved originals to uploads/_originals/.");
    $say('Add a .htaccess rule blocking uploads/_originals/ before deploying.');
    exit(0);
}

/* ── CONVERT / DRY RUN ─────────────────────────────────────────────── */
if ($dryRun || $doConvert) {
    $converted = []; $skipped = []; $saved = 0; $n = 0;
    $seen = [];
    foreach ($refs as $r) {
        $rel = ltrim($r['val'], '/');
        if (isset($seen[$rel])) continue;          /* one file, many rows */
        $seen[$rel] = true;
        if ($limit && $n >= $limit) break;
        $row = wpConvert($ROOT . '/' . $rel, $rel, wpBox($r['key'], $rel), $doConvert, $force);
        $n++;
        if (in_array($row['decision'], ['converted', 'would convert', 'done'], true)) {
            $converted[] = $row;
            $saved += max(0, $row['bytes'] - ($row['newBytes'] ?? $row['bytes']));
            $say(sprintf('  %-11s %-46s %8s -> %-8s %s',
                $row['decision'], substr($rel, 0, 46), wpFmt($row['bytes']),
                wpFmt($row['newBytes'] ?? 0),
                isset($row['newDims']) ? "({$row['dims']} -> {$row['newDims']})" : ''));
        } else {
            $skipped[] = $row;
            $say(sprintf('  %-11s %-46s %8s   %s', 'skip', substr($rel, 0, 46),
                wpFmt($row['bytes']), $row['reason']));
        }
    }
    $say('');
    $say('Converted/ready: ' . count($converted) . '   Skipped: ' . count($skipped));
    $say('Bytes saved:     ' . wpFmt($saved));
    if ($doConvert) {
        file_put_contents($journalLatest, json_encode(
            ['at' => date('c'), 'converted' => $converted, 'writes' => []],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $say('');
        $say('Files written. The database still points at the originals —');
        $say('run --rewrite-db next, then --verify.');
    }
    exit(0);
}

/* ── REWRITE DB ────────────────────────────────────────────────────── */
if ($doRewrite) {
    $writes = []; $skipped = 0;
    foreach ($refs as $r) {
        $rel  = ltrim($r['val'], '/');
        $new  = preg_replace('/\.(png|jpe?g)$/i', '.webp', $rel);
        if ($new === $rel) continue;
        $absNew = $ROOT . '/' . $new;
        $absOld = $ROOT . '/' . $rel;

        /* Re-assert everything now, rather than trusting the journal. */
        if (wpPinned($rel))     { $skipped++; continue; }
        if (!is_file($absNew))  { $skipped++; continue; }
        if (!is_file($absOld))  { $skipped++; continue; }
        $i = @getimagesize($absNew);
        if (!$i || $i[2] !== IMAGETYPE_WEBP || $i[0] < 1) { $skipped++; continue; }
        if (filesize($absNew) < 1 || filesize($absNew) >= filesize($absOld)) { $skipped++; continue; }

        $writes[] = ['table' => $r['table'], 'col' => $r['col'], 'pk' => $r['pk'],
                     'old' => $r['val'], 'new' => $new, 'key' => $r['key']];
    }

    $say('Rows to repoint: ' . count($writes) . '   Skipped: ' . $skipped);
    if (!$writes) exit(0);

    db()->beginTransaction();
    try {
        foreach ($writes as $w) {
            dbExecute("UPDATE `{$w['table']}` SET `{$w['col']}` = ? WHERE id = ?", [$w['new'], $w['pk']]);
        }
        /* Post-condition: every mapped column must still resolve to a file. */
        $bad = 0;
        foreach (wpGatherRefs() as $r) {
            $v = ltrim($r['val'], '/');
            if ($v === '' || str_starts_with($v, 'http')) continue;
            if (!is_file($ROOT . '/' . $v)) { $say("  DANGLING {$r['key']}#{$r['pk']} $v"); $bad++; }
        }
        if ($bad > 0) throw new RuntimeException("$bad rows would point at a missing file");

        db()->commit();
        $j = is_file($journalLatest) ? json_decode((string)file_get_contents($journalLatest), true) : [];
        $j['writes'] = $writes;
        file_put_contents($journalLatest, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $say('Committed ' . count($writes) . ' rows. Journal: logs/webp-migration-latest.json');
        $say('Run --verify next. Originals are still on disk; --rollback undoes this.');
    } catch (Throwable $e) {
        db()->rollBack();
        $say('ROLLED BACK, nothing changed: ' . $e->getMessage());
        exit(1);
    }
}
