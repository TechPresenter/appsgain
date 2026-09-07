<?php
/**
 * Appsgain — Google Play Store App Data Fetcher
 * Scrapes Play Store page and returns structured app data as JSON
 */
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

/* ── Auth ── */
if (!Auth::isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

/* ── Check cURL ── */
if (!function_exists('curl_init')) {
    echo json_encode(['ok' => false, 'message' => 'cURL extension is not enabled on this server. Enable it in php.ini.']);
    exit;
}

$url = trim($_POST['url'] ?? '');

/* ── Validate URL ── */
if (!$url) {
    echo json_encode(['ok' => false, 'message' => 'Play Store URL is required.']);
    exit;
}

/* Accept package ID or full URL */
if (preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/i', $url)) {
    $packageId = $url;
    $url = 'https://play.google.com/store/apps/details?id=' . $url . '&hl=en';
} elseif (preg_match('/play\.google\.com\/store\/apps\/details\?.*id=([^&]+)/i', $url, $m)) {
    $packageId = $m[1];
    $url = 'https://play.google.com/store/apps/details?id=' . $packageId . '&hl=en';
} else {
    echo json_encode(['ok' => false, 'message' => 'Invalid Google Play Store URL or package ID.']);
    exit;
}

/* ── Fetch the page ── */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Cache-Control: no-cache',
    ],
    CURLOPT_ENCODING       => '',
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || !$html) {
    echo json_encode(['ok' => false, 'message' => 'Could not fetch Play Store page. ' . ($curlErr ?: 'Empty response.')]);
    exit;
}
if ($httpCode === 404) {
    echo json_encode(['ok' => false, 'message' => 'App not found on Play Store. Check the URL/package ID.']);
    exit;
}
if ($httpCode !== 200) {
    echo json_encode(['ok' => false, 'message' => "Play Store returned HTTP {$httpCode}."]);
    exit;
}

/* ── Parse JSON-LD (most reliable) ── */
$jsonLd = [];
if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/si', $html, $jm)) {
    $decoded = json_decode(trim($jm[1]), true);
    if ($decoded) $jsonLd = $decoded;
}

/* ── Parse meta tags ── */
function getMeta(string $html, string $property): string {
    if (preg_match('/<meta\s+(?:property|name)=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $m)
     || preg_match('/<meta\s+content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']' . preg_quote($property, '/') . '["\'][^>]*>/i', $html, $m)) {
        return html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }
    return '';
}

/* ── Extract Google Play data from JS bundles ── */
function extractPlayData(string $html): array {
    $data = [];

    /* App name */
    if (preg_match('/<h1[^>]*itemprop="name"[^>]*>([^<]+)<\/h1>/i', $html, $m)) {
        $data['name'] = html_entity_decode(trim($m[1]), ENT_QUOTES);
    }
    if (empty($data['name']) && preg_match('/<title>([^<]+) - Apps on Google Play<\/title>/i', $html, $m)) {
        $data['name'] = html_entity_decode(trim($m[1]), ENT_QUOTES);
    }

    /* Developer */
    if (preg_match('/itemprop="author"[^>]*>[^<]*<[^>]*itemprop="name"[^>]*>([^<]+)<\/span>/i', $html, $m)) {
        $data['developer'] = html_entity_decode(trim($m[1]), ENT_QUOTES);
    }

    /* Installs / Downloads — look for common patterns */
    $installPatterns = [
        '/(\d[\d,]*\+?)\s*downloads/i',
        '/"installs","([^"]+)"/i',
        '/installs":"([^"]+)"/i',
        '/([0-9,]+\+)\s*<\/div>[^<]*<div[^>]*>[^<]*[Ii]nstalls/s',
        '/([[0-9,M+BK]+)\s*downloads/i',
    ];
    foreach ($installPatterns as $pat) {
        if (preg_match($pat, $html, $m)) {
            $data['installs'] = trim($m[1]);
            break;
        }
    }

    /* Size */
    if (preg_match('/(\d+(?:\.\d+)?\s*(?:MB|KB|GB))/i', $html, $m)) {
        $data['size'] = trim($m[1]);
    }

    /* Version */
    if (preg_match('/[Vv]ersion[^>]*>([0-9][0-9.a-z_-]+)/i', $html, $m)) {
        $data['version'] = trim($m[1]);
    }

    /* Content rating */
    if (preg_match('/content_rating["\s]*[:=]["\s]*([^"<,\n]+)/i', $html, $m)) {
        $data['content_rating'] = trim($m[1], '"\'');
    }

    /* Screenshots — og:image:secure_url or data-src in screenshot container */
    $screenshots = [];
    preg_match_all('/srcset="(https:\/\/play-lh\.googleusercontent\.com\/[^"\s]+)"/i', $html, $sm);
    if (!empty($sm[1])) {
        $seen = [];
        foreach ($sm[1] as $src) {
            $clean = preg_replace('/=w\d+-h\d+.*$/', '', $src);
            if (!in_array($clean, $seen) && count($screenshots) < 6) {
                $screenshots[] = $src;
                $seen[] = $clean;
            }
        }
    }
    if (!empty($screenshots)) $data['screenshots'] = $screenshots;

    return $data;
}

$playData = extractPlayData($html);

/* ── Build result ── */
$appName      = $jsonLd['name'] ?? $playData['name'] ?? getMeta($html, 'og:title') ?? '';
$description  = $jsonLd['description'] ?? getMeta($html, 'og:description') ?? '';
$iconUrl      = $jsonLd['image'] ?? getMeta($html, 'og:image') ?? '';
$category     = $jsonLd['applicationCategory'] ?? '';
$ratingValue  = $jsonLd['aggregateRating']['ratingValue'] ?? '';
$ratingCount  = $jsonLd['aggregateRating']['reviewCount'] ?? $jsonLd['aggregateRating']['ratingCount'] ?? '';
$developer    = $jsonLd['author']['name'] ?? $playData['developer'] ?? '';
$os           = $jsonLd['operatingSystem'] ?? 'Android';
$installs     = $playData['installs'] ?? '';
$version      = $playData['version'] ?? '';
$size         = $playData['size'] ?? '';
$screenshots  = $playData['screenshots'] ?? [];

/* Clean app name: remove " - Apps on Google Play" suffix */
$appName = preg_replace('/\s*[-–]\s*Apps on Google Play$/i', '', $appName);
$appName = trim($appName);

/* Determine app type from OS */
$appType = 'android';
if (stripos($os, 'ios') !== false || stripos($os, 'iphone') !== false) $appType = 'ios';
if (stripos($os, 'android') !== false && (stripos($os, 'ios') !== false || stripos($os, 'iphone') !== false)) $appType = 'both';

/* ── Download and save app icon ── */
$savedIconPath = '';
if ($iconUrl) {
    /* Use the largest available icon */
    $iconFetchUrl = preg_replace('/=s\d+(-rw)?(-no)?$/', '=s512-rw', $iconUrl);
    $iconFetchUrl = preg_replace('/=w\d+-h\d+/', '=s512', $iconFetchUrl);

    $iconCh = curl_init();
    curl_setopt_array($iconCh, [
        CURLOPT_URL            => $iconFetchUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AppsgainBot/1.0)',
        CURLOPT_ENCODING       => '',
    ]);
    $iconData = curl_exec($iconCh);
    $iconMime = curl_getinfo($iconCh, CURLINFO_CONTENT_TYPE);
    curl_close($iconCh);

    if ($iconData && stripos($iconMime, 'image') !== false) {
        $ext      = (stripos($iconMime, 'png') !== false) ? 'png' : 'jpg';
        $filename = 'apps/' . preg_replace('/[^a-z0-9]/', '_', strtolower($packageId)) . '_icon.' . $ext;
        $dir      = __DIR__ . '/../../uploads/apps/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fullPath = __DIR__ . '/../../uploads/' . $filename;
        if (file_put_contents($fullPath, $iconData)) {
            $savedIconPath = $filename;
        }
    }
}

/* ── Download screenshots ── */
$savedScreenshots = [];
foreach (array_slice($screenshots, 0, 5) as $si => $ssUrl) {
    $ssCh = curl_init();
    curl_setopt_array($ssCh, [
        CURLOPT_URL            => $ssUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
        CURLOPT_ENCODING       => '',
    ]);
    $ssData = curl_exec($ssCh);
    $ssMime = curl_getinfo($ssCh, CURLINFO_CONTENT_TYPE);
    curl_close($ssCh);

    if ($ssData && stripos($ssMime, 'image') !== false) {
        $ext      = (stripos($ssMime, 'png') !== false) ? 'png' : 'jpg';
        $ssFile   = 'apps/' . preg_replace('/[^a-z0-9]/', '_', strtolower($packageId)) . '_ss' . ($si + 1) . '.' . $ext;
        $ssPath   = __DIR__ . '/../../uploads/' . $ssFile;
        if (file_put_contents($ssPath, $ssData)) {
            $savedScreenshots[] = $ssFile;
        }
    }
}

/* ── Return result ── */
echo json_encode([
    'ok'          => true,
    'data'        => [
        'app_name'        => $appName,
        'developer_name'  => $developer,
        'app_category'    => $category,
        'app_type'        => $appType,
        'app_rating'      => $ratingValue ? round((float)$ratingValue, 1) : '',
        'rating_count'    => $ratingCount ? number_format((int)str_replace(',', '', $ratingCount)) : '',
        'total_downloads' => $installs,
        'app_version'     => $version,
        'app_size'        => $size,
        'short_description' => mb_substr(strip_tags($description), 0, 200),
        'app_description'   => nl2br(htmlspecialchars(strip_tags($description), ENT_NOQUOTES, 'UTF-8')),
        'google_play_url'   => $url,
        'package_id'        => $packageId,
        'icon_url'          => $iconUrl,
        'icon_path'         => $savedIconPath,
        'icon_preview_url'  => $savedIconPath ? UPLOADS_URL . '/' . $savedIconPath : '',
        'screenshots'       => $savedScreenshots,
        'screenshot_urls'   => array_map(function($p) { return UPLOADS_URL . '/' . $p; }, $savedScreenshots),
        'os'                => $os,
    ],
    'message' => "Fetched: {$appName}" . ($savedIconPath ? ' (icon saved)' : ''),
]);
