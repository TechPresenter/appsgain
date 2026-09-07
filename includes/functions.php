<?php
/**
 * APPSGAIN CMS — Core Helper Functions
 */

// ── String Helpers ────────────────────────────────────────

function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/u', '', $text);
    $text = preg_replace('/[\s_-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function truncate(string $text, int $length = 150, string $suffix = '...'): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

function timeAgo(string $datetime): string {
    $time  = strtotime($datetime);
    $diff  = time() - $time;
    $units = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
        1        => 'second',
    ];
    foreach ($units as $secs => $label) {
        $n = (int)floor($diff / $secs);
        if ($n >= 1) {
            return "$n $label" . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

function formatDate(string $datetime, string $format = 'd M Y'): string {
    return date($format, strtotime($datetime));
}

function readingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    return (int)ceil($wordCount / 200);
}

// ── Output / Security ────────────────────────────────────

if (!function_exists('e')) {
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
}

function sanitizeInput(string $input): string {
    return trim(strip_tags($input));
}

function sanitizeEmail(string $email): string|false {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validateEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone(string $phone): bool {
    return (bool) preg_match('/^\+?[0-9\s\-]{10,15}$/', trim($phone));
}

// ── Settings ─────────────────────────────────────────────

function getSetting(string $key, mixed $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $val = dbFetchValue("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        if ($val === false || $val === null || $val === '') {
            $altKey = str_starts_with($key, 'site_') ? substr($key, 5) : 'site_' . $key;
            $alt = dbFetchValue("SELECT setting_value FROM settings WHERE setting_key = ?", [$altKey]);
            if ($alt !== false && $alt !== null && $alt !== '') $val = $alt;
        }
        $cache[$key] = ($val !== false && $val !== null) ? $val : $default;
    }
    return (string)$cache[$key];
}

function getAllSettings(string $group = ''): array {
    if ($group) {
        $rows = dbFetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_group = ?", [$group]);
    } else {
        $rows = dbFetchAll("SELECT setting_key, setting_value FROM settings");
    }
    $result = [];
    foreach ($rows as $row) {
        $result[$row['setting_key']] = $row['setting_value'];
    }
    // Auto-alias: site_phone → phone, site_email → email, etc. (backward compat)
    foreach ($result as $key => $value) {
        if (str_starts_with($key, 'site_')) {
            $short = substr($key, 5);
            if (!isset($result[$short])) $result[$short] = $value;
        }
    }
    // Legacy key aliases for pages that used old key names
    $aliases = [
        'facebook_url'   => 'site_facebook',
        'instagram_url'  => 'site_instagram',
        'linkedin_url'   => 'site_linkedin',
        'twitter_url'    => 'site_twitter',
        'youtube_url'    => 'site_youtube',
        'whatsapp'       => 'site_whatsapp',
        'business_hours' => 'working_hours',
        'google_map_embed' => 'google_maps_embed',
    ];
    foreach ($aliases as $old => $new) {
        if (!isset($result[$old]) && isset($result[$new])) {
            $result[$old] = $result[$new];
        }
    }
    return $result;
}

function saveSetting(string $key, string $value, string $group = 'general'): void {
    dbExecute("INSERT INTO settings (setting_key, setting_value, setting_group)
               VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE setting_value = ?, setting_group = ?",
        [$key, $value, $group, $value, $group]
    );
}

/**
 * Get notification email recipients for a form type.
 * Returns array of valid email addresses.
 *
 * Types: 'contact' | 'application' | 'newsletter' | 'partner' | 'support' | 'enquiry' | 'all'
 */
function getNotifyEmails(string $type = 'all'): array {
    /* Type-specific list first */
    $specific = getSetting('notify_' . $type, '');
    /* Fall back to global notify_all, then contact_email, then site_email */
    $global   = getSetting('notify_all', '');
    $fallback = getSetting('contact_email', '') ?: getSetting('site_email', '');

    $combined = $specific ?: $global ?: $fallback;

    /* Split on comma/semicolon/newline, trim, validate */
    $raw    = preg_split('/[,;\n]+/', $combined);
    $emails = [];
    foreach ($raw as $e) {
        $e = trim($e);
        if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $e;
        }
    }
    return array_unique($emails);
}

// ── SEO ──────────────────────────────────────────────────

function getPageSEO(string $pageKey): array {
    $row = dbFetchOne("SELECT * FROM seo_settings WHERE page_key = ?", [$pageKey]);
    if (!$row) {
        $row = [
            'meta_title'       => getSetting('site_name', 'Appsgain Technologies'),
            'meta_description' => getSetting('site_description', ''),
            'meta_keywords'    => '',
            'og_title'         => '',
            'og_description'   => '',
            'og_image'         => '',
            'robots'           => 'index,follow',
            'canonical_url'    => '',
        ];
    }
    return $row;
}

// ── URL / Routing ─────────────────────────────────────────

function currentUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function redirect(string $url, int $code = 302): void {
    header("Location: $url", true, $code);
    exit;
}

function isCurrentPage(string $page): bool {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $check = trim($page, '/');
    return $path === $check || str_ends_with($path, $check);
}

// ── File / Upload ─────────────────────────────────────────

function uploadFile(array $file, string $folder = 'images'): array {
    if (!isset($file['tmp_name'], $file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        /* A number tells the user nothing; the sentence tells them what to do. */
        return ['success' => false, 'error' => uploadErrorMessage((int)($file['error'] ?? -1))];
    }

    // Must be a genuine POST upload, never an arbitrary server path.
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid upload'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds limit of ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB'];
    }

    // Trust the file's actual content, never the client-supplied type/name.
    $mime = mime_content_type($file['tmp_name']) ?: '';
    $allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES, ALLOWED_VIDEO_TYPES);
    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Raster images must actually decode as images.
    if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
        if (@getimagesize($file['tmp_name']) === false) {
            return ['success' => false, 'error' => 'File is not a valid image'];
        }
    }

    // SVG is markup: strip anything that could execute in a viewer's browser.
    if ($mime === 'image/svg+xml') {
        $svg = file_get_contents($file['tmp_name']);
        if ($svg === false || preg_match('/<\s*script|javascript:|on[a-z]+\s*=|<\s*foreignObject|<!ENTITY/i', $svg)) {
            return ['success' => false, 'error' => 'SVG contains scripting and was rejected'];
        }
    }

    // The extension is derived from the verified MIME type — a file named
    // "shell.php" that sniffs as image/jpeg is stored as ".jpg".
    $ext = UPLOAD_MIME_EXT[$mime] ?? null;
    if ($ext === null) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Keep $folder inside the uploads tree (no traversal, no absolute paths).
    $folder = trim(preg_replace('/[^A-Za-z0-9_\-\/]/', '', $folder), '/');
    $folder = implode('/', array_filter(explode('/', $folder), fn($seg) => $seg !== '' && $seg !== '.' && $seg !== '..'));
    if ($folder === '') $folder = 'images';

    $filename = uniqid('ag_', true) . '.' . $ext;
    $destDir  = UPLOADS_PATH . '/' . $folder;
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        return ['success' => false, 'error' => 'Could not create upload folder'];
    }

    $destPath = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Could not save file'];
    }
    @chmod($destPath, 0644);

    if (strpos($mime, 'image/') === 0) {
        optimizeImage($destPath, $mime);
    }

    $category = strpos($mime, 'image') !== false ? 'image' : (strpos($mime, 'video') !== false ? 'video' : 'document');
    return [
        'success'       => true,
        'filename'      => $filename,
        'path'          => $folder . '/' . $filename,
        'original_name' => basename($file['name']),
        'file_path'     => $destPath,
        'file_url'      => UPLOADS_URL . '/' . $folder . '/' . $filename,
        'file_type'     => $mime,
        'file_category' => $category,
        'file_size'     => $file['size'],
        'mime_type'     => $mime,
    ];
}

function optimizeImage(string $path, string $mime, int $quality = 82): bool {
    if (!file_exists($path) || filesize($path) < 10240) {
        return false;
    }
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    if (strpos($mime, 'svg') !== false || strpos($mime, 'gif') !== false) {
        return false;
    }

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($path);
            break;
        case 'image/webp':
            $src = @imagecreatefromwebp($path);
            break;
        default:
            return false;
    }

    if (!$src) {
        return false;
    }

    $width  = imagesx($src);
    $height = imagesy($src);
    $tmp = imagecreatetruecolor($width, $height);
    if ($tmp === false) {
        imagedestroy($src);
        return false;
    }

    if (in_array($mime, ['image/png', 'image/webp'], true)) {
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
    }

    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $width, $height, $width, $height);

    $result = false;
    if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
        $result = imagejpeg($tmp, $path, $quality);
    } elseif ($mime === 'image/png') {
        $result = imagepng($tmp, $path, 6);
    } elseif ($mime === 'image/webp') {
        $result = imagewebp($tmp, $path, $quality);
    }

    imagedestroy($src);
    imagedestroy($tmp);
    return $result;
}

function deleteFile(string $filePath): bool {
    $fullPath = $filePath;

    if (strpos($fullPath, UPLOADS_URL) === 0) {
        $fullPath = str_replace(UPLOADS_URL, UPLOADS_PATH, $fullPath);
    }

    if (preg_match('#^(?:[A-Za-z]:\\|/|\\)#', $filePath) === 1) {
        if (str_starts_with($fullPath, '/uploads/') || str_starts_with($fullPath, '\\uploads\\')) {
            $fullPath = ROOT_PATH . $filePath;
        }
    } else {
        $fullPath = UPLOADS_PATH . '/' . ltrim($filePath, '/\\');
    }

    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

function getImageUrl(string $path, string $default = ''): string {
    if (empty($path)) return $default ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><rect fill="#f1f5f9" width="400" height="300"/><text fill="#8b90a0" font-family="system-ui" font-size="14" text-anchor="middle" x="200" y="158">No Image</text></svg>');
    if (str_starts_with($path, 'http')) return $path;
    return UPLOADS_URL . '/' . ltrim($path, '/');
}

// ── Pagination ───────────────────────────────────────────

function paginate(int $total, int $perPage, int $currentPage, string $baseUrl = ''): array|string {
    $totalPages = (int)ceil($total / $perPage);
    $offset     = ($currentPage - 1) * $perPage;

    /* Without URL → return data array (legacy usage) */
    if ($baseUrl === '') {
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'total_pages'  => $totalPages,
            'offset'       => max(0, $offset),
            'has_prev'     => $currentPage > 1,
            'has_next'     => $currentPage < $totalPages,
        ];
    }

    /* With URL → return HTML pagination string */
    if ($totalPages <= 1) return '';

    $sep  = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<div class="pg-wrap">';

    $prev = $currentPage > 1
        ? '<a href="' . $baseUrl . $sep . 'page=' . ($currentPage - 1) . '" class="pg-btn"><i class="fas fa-chevron-left"></i></a>'
        : '<span class="pg-btn pg-disabled"><i class="fas fa-chevron-left"></i></span>';
    $html .= $prev;

    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . $sep . 'page=1" class="pg-btn">1</a>';
        if ($start > 2) $html .= '<span class="pg-btn pg-disabled">…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $cls = $i === $currentPage ? 'pg-btn pg-active' : 'pg-btn';
        $html .= '<a href="' . $baseUrl . $sep . 'page=' . $i . '" class="' . $cls . '">' . $i . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="pg-btn pg-disabled">…</span>';
        $html .= '<a href="' . $baseUrl . $sep . 'page=' . $totalPages . '" class="pg-btn">' . $totalPages . '</a>';
    }

    $next = $currentPage < $totalPages
        ? '<a href="' . $baseUrl . $sep . 'page=' . ($currentPage + 1) . '" class="pg-btn"><i class="fas fa-chevron-right"></i></a>'
        : '<span class="pg-btn pg-disabled"><i class="fas fa-chevron-right"></i></span>';
    $html .= $next;
    $html .= '</div>';

    return $html;
}

function currentPage(): int {
    return max(1, (int)($_GET['page'] ?? 1));
}

// ── Activity Log ─────────────────────────────────────────

function logActivity(string $action, string $module = '', string $description = ''): void {
    $userId = $_SESSION['admin_id'] ?? null;
    try {
        dbExecute(
            "INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $module,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]
        );
    } catch (PDOException $e) {
        // Silently ignore activity log failures — they must never break page flow
    }
}

// ── Visitors ─────────────────────────────────────────────

function _detectBrowser(string $ua): string {
    if (preg_match('/Edg\//i', $ua))              return 'Edge';
    if (preg_match('/OPR\/|Opera/i', $ua))        return 'Opera';
    if (preg_match('/SamsungBrowser/i', $ua))     return 'Samsung';
    if (preg_match('/UCBrowser/i', $ua))          return 'UC Browser';
    if (preg_match('/YaBrowser/i', $ua))          return 'Yandex';
    if (preg_match('/Chrome\//i', $ua))           return 'Chrome';
    if (preg_match('/Firefox\//i', $ua))          return 'Firefox';
    if (preg_match('/Safari\//i', $ua))           return 'Safari';
    if (preg_match('/MSIE|Trident/i', $ua))       return 'IE';
    return 'Other';
}

function _detectOS(string $ua): string {
    if (preg_match('/Windows NT 10/i', $ua))      return 'Windows 10/11';
    if (preg_match('/Windows NT/i', $ua))         return 'Windows';
    if (preg_match('/Android/i', $ua))            return 'Android';
    if (preg_match('/iPhone/i', $ua))             return 'iOS';
    if (preg_match('/iPad/i', $ua))               return 'iPadOS';
    if (preg_match('/Mac OS X/i', $ua))           return 'macOS';
    if (preg_match('/Linux/i', $ua))              return 'Linux';
    if (preg_match('/CrOS/i', $ua))               return 'ChromeOS';
    return 'Other';
}

function _isBot(string $ua): bool {
    return (bool) preg_match('/bot|crawl|spider|slurp|search|index|archive|scan/i', $ua);
}

function _getCountryFromHeaders(): string {
    /* Cloudflare, AWS, or other CDN country header */
    foreach (['HTTP_CF_IPCOUNTRY','HTTP_X_COUNTRY_CODE','HTTP_X_GEO_COUNTRY'] as $h) {
        if (!empty($_SERVER[$h]) && strlen($_SERVER[$h]) === 2) {
            return strtoupper($_SERVER[$h]);
        }
    }
    return '';
}

function trackVisitor(): void {
    if (!isset($_SESSION)) session_start();
    $sessionId = session_id();
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
    $url       = currentUrl();
    $ua        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $referrer  = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);

    /* Skip bots */
    if (_isBot($ua)) return;

    /* Device */
    $device = 'desktop';
    if (preg_match('/Mobile|Android|iPhone/i', $ua)) $device = 'mobile';
    elseif (preg_match('/iPad|Tablet/i', $ua))       $device = 'tablet';

    /* Browser & OS */
    $browser = _detectBrowser($ua);
    $os      = _detectOS($ua);
    $country = _getCountryFromHeaders();

    /* Parse traffic source */
    $source = 'direct';
    if ($referrer) {
        $host = parse_url($referrer, PHP_URL_HOST) ?? '';
        $host = strtolower(preg_replace('/^www\./', '', $host));
        if (preg_match('/google\.|bing\.|yahoo\.|duckduck/i', $host))   $source = 'organic';
        elseif (preg_match('/facebook|instagram|twitter|linkedin|youtube|tiktok/i', $host)) $source = 'social';
        elseif ($host && strpos($url, $host) === false)                  $source = 'referral';
    } elseif (isset($_GET['utm_source'])) {
        $source = 'campaign';
    }

    /* Ensure analytics columns exist (runs once then is cached) */
    static $_analyticsColumnsChecked = false;
    if (!$_analyticsColumnsChecked) {
        $_analyticsColumnsChecked = true;
        try {
            $cols = array_column(db()->query("SHOW COLUMNS FROM visitors")->fetchAll(\PDO::FETCH_ASSOC), 'Field');
            $adds = [];
            if (!in_array('browser', $cols))       $adds[] = "ADD COLUMN browser VARCHAR(50) DEFAULT NULL";
            if (!in_array('os', $cols))            $adds[] = "ADD COLUMN os VARCHAR(60) DEFAULT NULL";
            if (!in_array('country_code', $cols))  $adds[] = "ADD COLUMN country_code CHAR(2) DEFAULT NULL";
            if (!in_array('traffic_source', $cols))$adds[] = "ADD COLUMN traffic_source VARCHAR(20) DEFAULT 'direct'";
            if (!in_array('is_bot', $cols))        $adds[] = "ADD COLUMN is_bot TINYINT(1) DEFAULT 0";
            if ($adds) db()->exec("ALTER TABLE visitors " . implode(', ', $adds));
        } catch (\Throwable $e) {}
    }

    try {
        dbExecute(
            "INSERT INTO visitors (session_id, ip_address, page_url, referrer, user_agent, device_type, browser, os, country_code, traffic_source, is_bot)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
            [$sessionId, $ip, $url, $referrer, $ua, $device, $browser, $os, $country ?: null, $source]
        );
    } catch (\Throwable $e) {
        /* Fallback if new columns don't exist yet */
        try {
            dbExecute(
                "INSERT INTO visitors (session_id, ip_address, page_url, referrer, user_agent, device_type) VALUES (?, ?, ?, ?, ?, ?)",
                [$sessionId, $ip, $url, $referrer, $ua, $device]
            );
        } catch (\Throwable $e2) {}
    }
}

// ── Flash Messages ────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type][] = $message;
}

function getFlash(string $type): array {
    $msgs = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $msgs;
}

function hasFlash(string $type): bool {
    return !empty($_SESSION['flash'][$type]);
}

function renderFlash(): void {
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        foreach (getFlash($type) as $msg) {
            $icon = ['success' => 'check-circle', 'error' => 'times-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'][$type];
            echo "<div class=\"alert-{$type}\"><i class=\"fas fa-{$icon}\"></i> " . e($msg) . "</div>";
        }
    }
}

// ── JSON Response ────────────────────────────────────────

function jsonResponse(bool $success, string $message = '', array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_filter([
        'success' => $success,
        'message' => $message,
        'data'    => $data ?: null,
    ], fn($v) => $v !== null));
    exit;
}

// ── CSRF ─────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        if (isAjax()) {
            jsonResponse(false, 'CSRF token validation failed.', [], 403);
        }
        http_response_code(403);
        die('CSRF validation failed. Please go back and try again.');
    }
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// ── Stats helpers ─────────────────────────────────────────

function countTable(string $table, string $where = '1=1', array $params = []): int {
    return (int)dbFetchValue("SELECT COUNT(*) FROM `$table` WHERE $where", $params);
}

function todayVisitors(): int {
    return (int)dbFetchValue(
        "SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE DATE(visited_at) = CURDATE()"
    );
}

function monthVisitors(): int {
    return (int)dbFetchValue(
        "SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE MONTH(visited_at) = MONTH(CURDATE()) AND YEAR(visited_at) = YEAR(CURDATE())"
    );
}

// ── Upload limits ─────────────────────────────────────

/** The smallest of the limits that actually govern an upload, in bytes. */
function uploadLimitBytes(): int
{
    $toBytes = static function (string $v): int {
        $v = trim($v);
        if ($v === '') return 0;
        $unit = strtolower(substr($v, -1));
        $n = (int)$v;
        return match ($unit) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n };
    };
    $limits = array_filter([
        $toBytes((string)ini_get('upload_max_filesize')),
        $toBytes((string)ini_get('post_max_size')),
        defined('MAX_UPLOAD_SIZE') ? (int)MAX_UPLOAD_SIZE : 0,
    ]);
    return $limits ? min($limits) : 2097152;
}

/** That limit as something to print, e.g. "8 MB". */
function uploadLimitLabel(): string
{
    $b = uploadLimitBytes();
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : round($b / 1024) . ' KB';
}

/**
 * PHP discards $_POST and $_FILES when the request body exceeds
 * post_max_size, so a form arrives looking empty. Detect that and tell the
 * user, rather than redirecting as though nothing was submitted.
 */
function guardOversizedPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    if (!empty($_POST) || !empty($_FILES)) return;
    $len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len <= 0) return;

    $mb = round($len / 1048576, 1);
    setFlash('error',
        "That upload was too large and the server rejected it before it arrived ({$mb} MB sent, "
        . uploadLimitLabel() . ' allowed). Nothing was saved. Please use a smaller image, '
        . 'or ask your host to raise post_max_size and upload_max_filesize.');
    redirect($_SERVER['HTTP_REFERER'] ?? (defined('ADMIN_URL') ? ADMIN_URL . '/dashboard.php' : '/'));
}

/** PHP's numeric upload errors, in words. */
function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE   => 'The file is larger than the server allows (' . uploadLimitLabel() . ').',
        UPLOAD_ERR_FORM_SIZE  => 'The file is larger than this form allows.',
        UPLOAD_ERR_PARTIAL    => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was chosen.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder for uploads. Contact your host.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the file to disk. Check folder permissions.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
        default               => 'The upload failed (code ' . $code . ').',
    };
}