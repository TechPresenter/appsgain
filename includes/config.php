<?php
/**
 * APPSGAIN CMS — Central Configuration
 * Auto-detects localhost vs production environment and the web base path,
 * so the site runs from any folder (htdocs/appsgain-website, htdocs/clone, /)
 * without editing this file.
 */

// ── Paths (needed early for base-path detection) ──────
/* A caller may define this before bootstrapping, so do not redefine it. */
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH',    ROOT_PATH . '/admin');
define('ASSETS_PATH',   ROOT_PATH);
define('UPLOADS_PATH',  ROOT_PATH . '/uploads');

// ── Environment Detection ─────────────────────────────
$_host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_hostOnly = strtolower(explode(':', $_host)[0]);
$_isLocal = in_array($_hostOnly, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($_hostOnly, '.local')
            || str_ends_with($_hostOnly, '.test');

define('ENV',   $_isLocal ? 'development' : 'production');
define('DEBUG', ENV === 'development');

// ── Credentials ───────────────────────────────────────
// Priority: environment variables → includes/config.secret.php → defaults.
// Keeping credentials out of this file is preferred; both overrides are
// blocked from web access by .htaccess.
$_secret = [];
if (is_file(__DIR__ . '/config.secret.php')) {
    $_loaded = require __DIR__ . '/config.secret.php';
    if (is_array($_loaded)) $_secret = $_loaded;
}
$_cfg = static function (string $key, string $default) use ($_secret): string {
    $env = getenv('APPSGAIN_' . strtoupper($key));
    if ($env !== false && $env !== '') return $env;
    if (isset($_secret[$key]) && $_secret[$key] !== '') return (string)$_secret[$key];
    return $default;
};

// ── Database ─────────────────────────────────────────
if ($_isLocal) {
    define('DB_HOST', $_cfg('db_host', 'localhost'));
    define('DB_PORT', $_cfg('db_port', '3306'));
    define('DB_NAME', $_cfg('db_name', 'appsgain'));
    define('DB_USER', $_cfg('db_user', 'root'));
    define('DB_PASS', $_cfg('db_pass', ''));
} else {
    define('DB_HOST', $_cfg('db_host', 'localhost'));
    define('DB_PORT', $_cfg('db_port', '3306'));
    /* No credential defaults here — the repo is public. Production values come
       from environment variables or includes/config.secret.php (both untracked). */
    define('DB_NAME', $_cfg('db_name', ''));
    define('DB_USER', $_cfg('db_user', ''));
    define('DB_PASS', $_cfg('db_pass', ''));
}
define('DB_CHARSET', 'utf8mb4');

// ── Base Path Detection ───────────────────────────────
// Works out the sub-directory the site is served from by comparing the
// filesystem root of this install against Apache's DOCUMENT_ROOT.
$_basePath = '';
$_docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$_appRoot  = realpath(ROOT_PATH);
if ($_docRoot && $_appRoot) {
    $_bs = chr(92); // backslash
    $_docRoot = str_replace($_bs, '/', rtrim($_docRoot, '/' . $_bs));
    $_appRoot = str_replace($_bs, '/', rtrim($_appRoot, '/' . $_bs));
    if ($_appRoot !== $_docRoot && str_starts_with($_appRoot . '/', $_docRoot . '/')) {
        $_basePath = substr($_appRoot, strlen($_docRoot));
    }
}
// Fallback: derive from the running script's URL path.
if ($_basePath === '' && !empty($_SERVER['SCRIPT_NAME'])) {
    $_scriptDir = str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME']));
    foreach (['/admin/pages', '/admin/api', '/admin', '/api'] as $_sub) {
        if (str_ends_with($_scriptDir, $_sub)) {
            $_scriptDir = substr($_scriptDir, 0, -strlen($_sub));
            break;
        }
    }
    if ($_scriptDir !== '/' && $_scriptDir !== '.') $_basePath = rtrim($_scriptDir, '/');
}
$_basePath = rtrim($_basePath, '/');
define('BASE_PATH', $_basePath);           // '' at doc-root, '/appsgain-website' in a sub-folder

// ── Scheme + Host ─────────────────────────────────────
$_https  = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
$_scheme = $_https ? 'https' : 'http';

// ── Site URLs ─────────────────────────────────────────
if ($_isLocal) {
    $_origin = $_scheme . '://' . $_host;
} else {
    // Canonical production origin — always https, always the primary domain.
    $_origin = $_cfg('site_origin', 'https://appsgain.in');
}
define('SITE_URL',    $_origin . BASE_PATH);
define('ADMIN_URL',   SITE_URL . '/admin');
define('ASSETS_URL',  SITE_URL);
define('UPLOADS_URL', SITE_URL . '/uploads');

// ── Analytics defaults ────────────────────────────────
// Used when the matching Admin setting is left blank, so tracking keeps
// working on a fresh install. Override in Admin > Settings > SEO & Analytics.
define('AG_DEFAULT_FB_PIXEL', '1070339995399476');

// ── Security ─────────────────────────────────────────
define('CSRF_TOKEN_NAME',    '_csrf_token');
define('CSRF_TOKEN_EXPIRY',  3600);
define('SESSION_LIFETIME',   7200);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION',   900);

/* Server-side session limits. SESSION_LIFETIME only governs the cookie;
   these are checked on every admin request. */
define('ADMIN_IDLE_TIMEOUT',     1800);   // 30 min with no activity
define('ADMIN_ABSOLUTE_TIMEOUT', 43200);  // 12 h regardless of activity
define('ADMIN_ID_ROTATE_EVERY',  900);    // new session id every 15 min

// ── Upload Limits ─────────────────────────────────────
define('MAX_UPLOAD_SIZE',     10 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml']);
define('ALLOWED_DOC_TYPES',   ['application/pdf']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4','video/webm']);

// Canonical extension per allowed MIME type — the ONLY extensions ever
// written to disk by uploadFile(). Never trust the client filename.
define('UPLOAD_MIME_EXT', [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'image/gif'       => 'gif',
    'image/svg+xml'   => 'svg',
    'application/pdf' => 'pdf',
    'video/mp4'       => 'mp4',
    'video/webm'      => 'webm',
]);

// ── Pagination ────────────────────────────────────────
define('ITEMS_PER_PAGE',       10);
define('ADMIN_ITEMS_PER_PAGE', 20);

// ── Email ─────────────────────────────────────────────
define('MAIL_FROM',      'noreply@appsgain.in');
define('MAIL_FROM_NAME', 'Appsgain Technologies Private Limited');
define('ADMIN_EMAIL',    'info@appsgain.in');

// ── Timezone ─────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Error Reporting ───────────────────────────────────
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');
}

// ── Session Config ────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (!DEBUG) {
    ini_set('session.cookie_secure', 1);
}
