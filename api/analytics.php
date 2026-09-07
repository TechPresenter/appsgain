<?php
/**
 * Appsgain Analytics — Tracking Endpoint
 * Receives POST JSON from js/analytics.js
 * Creates tables on first run automatically.
 */
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/bootstrap.php';

/* ── CORS & headers ──
   The origin used to be echoed straight back, so any site could post
   analytics into this install. Only our own origin is allowed now. */
header('Content-Type: application/json; charset=utf-8');
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$selfOrigin = rtrim(SITE_URL, '/');
if ($origin !== '' && rtrim($origin, '/') === $selfOrigin) {
    header('Access-Control-Allow-Origin: ' . $selfOrigin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/* One beacon per page view, so this only catches automated flooding. */
if (function_exists('rlGuardJson')) rlGuardJson('api.analytics', 300, 600, 300);
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo '{"ok":false}'; exit; }

/* ── Parse body ── */
$raw  = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
if (!is_array($data)) { http_response_code(400); echo '{"ok":false}'; exit; }

$type = $data['t'] ?? '';
$sid  = preg_replace('/[^a-zA-Z0-9_\-]/', '', substr($data['sid'] ?? '', 0, 64));
if (!$sid) { echo '{"ok":false}'; exit; }

/* ── Ensure tables exist (cached per process) ── */
static $tablesReady = false;
if (!$tablesReady) {
    ensureAnalyticsTables();
    $tablesReady = true;
}

/* ── IP / UA ── */
$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_REAL_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '';
$ip = trim(explode(',', $ip)[0]);

/* Skip obvious bots */
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp|archive|scan|python|curl/i', $ua)) {
    echo '{"ok":true,"skipped":"bot"}'; exit;
}

/* ── Route to handler ── */
switch ($type) {
    case 'pv':     handlePageview($data, $sid, $ip);  break;
    case 'unload': handleUnload($data, $sid);          break;
    case 'click':  handleClick($data, $sid);           break;
    case 'form_start': handleEvent($data, $sid, 'form_start'); break;
    case 'cta_click':  handleEvent($data, $sid, 'cta_click');  break;
    case 'event':      handleEvent($data, $sid, $data['event_type'] ?? 'custom'); break;
}

echo '{"ok":true}';
exit;

/* ════════════════════════════════════════════════════════
   HANDLERS
════════════════════════════════════════════════════════ */

function handlePageview(array $d, string $sid, string $ip): void
{
    $url    = clean($d['url']   ?? '', 500);
    $title  = clean($d['title'] ?? '', 300);
    $ref    = clean($d['ref']   ?? '', 500);
    $vid    = clean($d['vid']   ?? '', 64);
    $isNew  = isset($d['is_new']) ? (int)$d['is_new'] : 0;
    $pvid   = clean($d['pvid']  ?? '', 64);
    $device = in_array($d['device'] ?? '', ['desktop','mobile','tablet']) ? $d['device'] : 'desktop';
    $tz     = clean($d['tz']    ?? '', 60);
    $lang   = clean($d['lang']  ?? '', 10);
    $src    = clean($d['src']   ?? 'direct', 20);
    $utmSrc = clean($d['utm_src']  ?? '', 100);
    $utmMed = clean($d['utm_med']  ?? '', 100);
    $utmCamp= clean($d['utm_camp'] ?? '', 100);
    $utmTerm= clean($d['utm_term'] ?? '', 100);
    $utmCont= clean($d['utm_cont'] ?? '', 100);
    $sw     = (int)($d['sw'] ?? 0);
    $sh     = (int)($d['sh'] ?? 0);

    /* Detect country from Cloudflare header */
    $countryCode = strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '', 0, 2));

    /* Browser & OS detection from User-Agent (server-side) */
    $uaStr   = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browser = detectBrowserServer($uaStr);
    $os      = detectOSServer($uaStr);

    /* ── Upsert session ── */
    $existingSession = dbFetchOne(
        "SELECT id, pages_viewed FROM analytics_sessions WHERE session_id = ?",
        [$sid]
    );

    if ($existingSession) {
        /* Update session — increment pages, update exit page */
        dbExecute(
            "UPDATE analytics_sessions
             SET exit_page = ?, pages_viewed = pages_viewed + 1, updated_at = NOW()
             WHERE session_id = ?",
            [$url, $sid]
        );
    } else {
        /* New session */
        dbExecute(
            "INSERT INTO analytics_sessions
             (session_id, visitor_id, ip_address, entry_page, exit_page,
              pages_viewed, device_type, browser, os, country_code,
              traffic_source, utm_source, utm_medium, utm_campaign,
              utm_term, utm_content, referrer, is_new_visitor,
              screen_w, screen_h, timezone, language, created_at, updated_at)
             VALUES (?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [$sid, $vid ?: null, $ip, $url, $url,
             $device, $browser, $os, $countryCode ?: null,
             $src, $utmSrc ?: null, $utmMed ?: null, $utmCamp ?: null,
             $utmTerm ?: null, $utmCont ?: null, $ref ?: null, $isNew,
             $sw ?: null, $sh ?: null, $tz ?: null, $lang ?: null]
        );
    }

    /* ── Insert pageview ── */
    dbExecute(
        "INSERT INTO analytics_pageviews
         (pv_id, session_id, page_url, page_title, referrer, created_at)
         VALUES (?,?,?,?,?,NOW())",
        [$pvid ?: $sid . '_' . time(), $sid, $url, $title, $ref ?: null]
    );
}

function handleUnload(array $d, string $sid): void
{
    $pvid   = clean($d['pvid']  ?? '', 64);
    $url    = clean($d['url']   ?? '', 500);
    $top    = min(86400, max(0, (int)($d['top'] ?? 0)));
    $scroll = min(100,   max(0, (int)($d['scroll'] ?? 0)));

    if ($pvid) {
        dbExecute(
            "UPDATE analytics_pageviews
             SET time_on_page = ?, scroll_depth = ?, is_exit = 1
             WHERE pv_id = ?",
            [$top, $scroll, $pvid]
        );
    }

    /* Update session duration */
    dbExecute(
        "UPDATE analytics_sessions
         SET duration = TIMESTAMPDIFF(SECOND, created_at, NOW()),
             exit_page = ?, updated_at = NOW()
         WHERE session_id = ?",
        [$url, $sid]
    );

    /* Mark bounce if only 1 page */
    dbExecute(
        "UPDATE analytics_sessions
         SET is_bounce = 1
         WHERE session_id = ? AND pages_viewed = 1",
        [$sid]
    );
}

function handleClick(array $d, string $sid): void
{
    dbExecute(
        "INSERT INTO analytics_events
         (session_id, event_type, page_url, x_pct, y_pct, element_tag, element_text, href, created_at)
         VALUES (?,?,?,?,?,?,?,?,NOW())",
        [
            $sid,
            'click',
            clean($d['url']  ?? '', 500),
            min(100, max(0, (int)($d['x'] ?? 0))),
            min(100, max(0, (int)($d['y'] ?? 0))),
            clean($d['tag']  ?? '', 20),
            clean($d['txt']  ?? '', 100),
            clean($d['href'] ?? '', 200),
        ]
    );
}

function handleEvent(array $d, string $sid, string $eventType): void
{
    $evData = [];
    if (!empty($d['data']) && is_array($d['data'])) $evData = $d['data'];
    if (!empty($d['label'])) $evData['label'] = $d['label'];
    if (!empty($d['href']))  $evData['href']  = $d['href'];

    dbExecute(
        "INSERT INTO analytics_events
         (session_id, event_type, page_url, event_data, created_at)
         VALUES (?,?,?,?,NOW())",
        [$sid, substr($eventType, 0, 50), clean($d['url'] ?? '', 500), $evData ? json_encode($evData) : null]
    );
}

/* ════════════════════════════════════════════════════════
   TABLE CREATION
════════════════════════════════════════════════════════ */

function ensureAnalyticsTables(): void
{
    $pdo = db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS `analytics_sessions` (
        `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `session_id`     VARCHAR(64)  NOT NULL,
        `visitor_id`     VARCHAR(64)  DEFAULT NULL,
        `ip_address`     VARCHAR(45)  DEFAULT NULL,
        `entry_page`     VARCHAR(500) DEFAULT NULL,
        `exit_page`      VARCHAR(500) DEFAULT NULL,
        `pages_viewed`   SMALLINT UNSIGNED DEFAULT 1,
        `duration`       INT UNSIGNED DEFAULT 0,
        `is_bounce`      TINYINT(1)   DEFAULT 0,
        `is_new_visitor` TINYINT(1)   DEFAULT 1,
        `device_type`    VARCHAR(20)  DEFAULT 'desktop',
        `browser`        VARCHAR(50)  DEFAULT NULL,
        `os`             VARCHAR(60)  DEFAULT NULL,
        `country_code`   CHAR(2)      DEFAULT NULL,
        `traffic_source` VARCHAR(20)  DEFAULT 'direct',
        `utm_source`     VARCHAR(100) DEFAULT NULL,
        `utm_medium`     VARCHAR(100) DEFAULT NULL,
        `utm_campaign`   VARCHAR(100) DEFAULT NULL,
        `utm_term`       VARCHAR(100) DEFAULT NULL,
        `utm_content`    VARCHAR(100) DEFAULT NULL,
        `referrer`       VARCHAR(500) DEFAULT NULL,
        `screen_w`       SMALLINT UNSIGNED DEFAULT NULL,
        `screen_h`       SMALLINT UNSIGNED DEFAULT NULL,
        `timezone`       VARCHAR(60)  DEFAULT NULL,
        `language`       VARCHAR(10)  DEFAULT NULL,
        `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `ux_session` (`session_id`),
        KEY `ix_created` (`created_at`),
        KEY `ix_country` (`country_code`),
        KEY `ix_source`  (`traffic_source`),
        KEY `ix_device`  (`device_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `analytics_pageviews` (
        `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `pv_id`        VARCHAR(64)  NOT NULL,
        `session_id`   VARCHAR(64)  NOT NULL,
        `page_url`     VARCHAR(500) NOT NULL,
        `page_title`   VARCHAR(300) DEFAULT NULL,
        `referrer`     VARCHAR(500) DEFAULT NULL,
        `time_on_page` INT UNSIGNED DEFAULT 0,
        `scroll_depth` TINYINT UNSIGNED DEFAULT 0,
        `is_exit`      TINYINT(1)  DEFAULT 0,
        `created_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `ix_session` (`session_id`),
        KEY `ix_url`     (`page_url`(100)),
        KEY `ix_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `analytics_events` (
        `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `session_id`   VARCHAR(64)  NOT NULL,
        `event_type`   VARCHAR(50)  NOT NULL,
        `page_url`     VARCHAR(500) DEFAULT NULL,
        `x_pct`        TINYINT UNSIGNED DEFAULT NULL,
        `y_pct`        TINYINT UNSIGNED DEFAULT NULL,
        `element_tag`  VARCHAR(20)  DEFAULT NULL,
        `element_text` VARCHAR(100) DEFAULT NULL,
        `href`         VARCHAR(200) DEFAULT NULL,
        `event_data`   JSON         DEFAULT NULL,
        `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `ix_session`    (`session_id`),
        KEY `ix_event_type` (`event_type`),
        KEY `ix_created`    (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/* ════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════ */

function clean(string $val, int $max): string
{
    return mb_substr(trim($val), 0, $max, 'UTF-8');
}

function detectBrowserServer(string $ua): string
{
    if (preg_match('/Edg\//i', $ua))          return 'Edge';
    if (preg_match('/OPR\/|Opera/i', $ua))    return 'Opera';
    if (preg_match('/SamsungBrowser/i', $ua)) return 'Samsung';
    if (preg_match('/UCBrowser/i', $ua))      return 'UC Browser';
    if (preg_match('/YaBrowser/i', $ua))      return 'Yandex';
    if (preg_match('/Chrome\//i', $ua))       return 'Chrome';
    if (preg_match('/Firefox\//i', $ua))      return 'Firefox';
    if (preg_match('/Safari\//i', $ua))       return 'Safari';
    if (preg_match('/MSIE|Trident/i', $ua))   return 'IE';
    return 'Other';
}

function detectOSServer(string $ua): string
{
    if (preg_match('/Windows NT 1[01]\./i', $ua)) return 'Windows 10/11';
    if (preg_match('/Windows NT/i', $ua))         return 'Windows';
    if (preg_match('/Android/i', $ua))            return 'Android';
    if (preg_match('/iPhone/i', $ua))             return 'iOS';
    if (preg_match('/iPad/i', $ua))               return 'iPadOS';
    if (preg_match('/Mac OS X/i', $ua))           return 'macOS';
    if (preg_match('/CrOS/i', $ua))               return 'ChromeOS';
    if (preg_match('/Linux/i', $ua))              return 'Linux';
    return 'Other';
}
