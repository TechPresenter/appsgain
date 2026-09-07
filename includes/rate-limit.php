<?php
/**
 * Rate limiting.
 *
 * Nothing on the site was throttled: the login form, the contact/enquiry/
 * newsletter/apply endpoints and the CAPTCHA generator could all be hit as
 * fast as a script could send. Account lockout existed but was keyed on the
 * user row, which stops nobody from spraying one password across many
 * accounts — and lets anyone lock a colleague out by failing five times.
 *
 * Counters live in a table rather than the session, because an attacker
 * simply drops the cookie. Keyed on (action, identifier) where the
 * identifier is normally the client IP.
 */

/** Create the counter table once, then never again. */
function rlEnsureTable(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS rate_limits (
               id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
               bucket      VARCHAR(64)  NOT NULL,
               identifier  VARCHAR(64)  NOT NULL,
               hits        INT UNSIGNED NOT NULL DEFAULT 0,
               window_start DATETIME    NOT NULL,
               blocked_until DATETIME   NULL DEFAULT NULL,
               PRIMARY KEY (id),
               UNIQUE KEY uniq_bucket_id (bucket, identifier),
               KEY idx_window (window_start)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Exception $e) {
        /* If the table cannot be made we fail open rather than lock the site
           out; the error is worth seeing in the log though. */
        error_log('[rate-limit] table unavailable: ' . $e->getMessage());
    }
}

/**
 * The caller's IP, taking proxy headers into account only when the host is
 * actually behind a trusted proxy. Believing X-Forwarded-For unconditionally
 * would let anyone reset their own counter by forging a header.
 */
function rlClientIp(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    /* Set TRUSTED_PROXIES in config when the site sits behind Cloudflare or a
       load balancer. Until then only the socket address is believed. */
    $trusted = defined('TRUSTED_PROXIES') ? (array)TRUSTED_PROXIES : [];
    if ($trusted && in_array($remote, $trusted, true)) {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h) {
            if (empty($_SERVER[$h])) continue;
            $candidate = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
        }
    }
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

/**
 * Record one attempt and say whether it is allowed.
 *
 * @param string $bucket   what is being limited, e.g. 'login', 'api.contact'
 * @param int    $max      attempts permitted per window
 * @param int    $window   window length in seconds
 * @param int    $blockFor seconds to lock out after the limit is passed
 * @param string $id       identifier; defaults to the client IP
 *
 * @return array{allowed:bool,remaining:int,retry_after:int}
 */
function rlHit(string $bucket, int $max, int $window, int $blockFor = 0, string $id = ''): array
{
    rlEnsureTable();
    $id = $id !== '' ? $id : rlClientIp();
    $id = substr($id, 0, 64);
    $ok = ['allowed' => true, 'remaining' => $max, 'retry_after' => 0];

    try {
        $row = dbFetchOne(
            "SELECT hits, window_start, blocked_until,
                    TIMESTAMPDIFF(SECOND, window_start, NOW())        AS age,
                    TIMESTAMPDIFF(SECOND, NOW(), blocked_until)       AS block_left
               FROM rate_limits WHERE bucket = ? AND identifier = ?",
            [$bucket, $id]
        );

        if ($row && $row['blocked_until'] !== null && (int)$row['block_left'] > 0) {
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => (int)$row['block_left']];
        }

        /* No row, or the window has rolled over — start counting again. */
        if (!$row || (int)$row['age'] >= $window) {
            dbExecute(
                "INSERT INTO rate_limits (bucket, identifier, hits, window_start, blocked_until)
                 VALUES (?, ?, 1, NOW(), NULL)
                 ON DUPLICATE KEY UPDATE hits = 1, window_start = NOW(), blocked_until = NULL",
                [$bucket, $id]
            );
            return ['allowed' => true, 'remaining' => $max - 1, 'retry_after' => 0];
        }

        $hits = (int)$row['hits'] + 1;
        if ($hits > $max) {
            $until = $blockFor > 0 ? $blockFor : $window;
            dbExecute(
                "UPDATE rate_limits
                    SET hits = ?, blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
                  WHERE bucket = ? AND identifier = ?",
                [$hits, $until, $bucket, $id]
            );
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => $until];
        }

        dbExecute("UPDATE rate_limits SET hits = ? WHERE bucket = ? AND identifier = ?", [$hits, $bucket, $id]);
        return ['allowed' => true, 'remaining' => max(0, $max - $hits), 'retry_after' => 0];

    } catch (Exception $e) {
        /* A limiter that breaks the site is worse than one that misses a
           request, so failures fall open and are logged. */
        error_log('[rate-limit] ' . $bucket . ': ' . $e->getMessage());
        return $ok;
    }
}

/**
 * Is this identifier currently blocked? Peeks without counting, so a
 * check can run before an attempt is judged good or bad.
 *
 * @return array{blocked:bool,retry_after:int}
 */
function rlBlocked(string $bucket, string $id = ''): array
{
    rlEnsureTable();
    $id = substr($id !== '' ? $id : rlClientIp(), 0, 64);
    try {
        $row = dbFetchOne(
            "SELECT TIMESTAMPDIFF(SECOND, NOW(), blocked_until) AS left_s
               FROM rate_limits
              WHERE bucket = ? AND identifier = ? AND blocked_until IS NOT NULL",
            [$bucket, $id]
        );
        $left = (int)($row['left_s'] ?? 0);
        return ['blocked' => $left > 0, 'retry_after' => max(0, $left)];
    } catch (Exception $e) {
        return ['blocked' => false, 'retry_after' => 0];
    }
}

/** Clear a bucket — call after a genuine success so one slip is not punished. */
function rlClear(string $bucket, string $id = ''): void
{
    rlEnsureTable();
    $id = substr($id !== '' ? $id : rlClientIp(), 0, 64);
    try {
        dbExecute("DELETE FROM rate_limits WHERE bucket = ? AND identifier = ?", [$bucket, $id]);
    } catch (Exception $e) { /* nothing to do */ }
}

/**
 * Enforce a limit on a JSON endpoint: answers 429 and stops when exceeded.
 * Sends the standard headers so a well-behaved client can back off.
 */
function rlGuardJson(string $bucket, int $max, int $window, int $blockFor = 0): void
{
    $r = rlHit($bucket, $max, $window, $blockFor);
    header('X-RateLimit-Limit: ' . $max);
    header('X-RateLimit-Remaining: ' . $r['remaining']);
    if ($r['allowed']) return;

    header('Retry-After: ' . $r['retry_after']);
    http_response_code(429);
    echo json_encode([
        'ok'      => false,
        'success' => false,
        'message' => 'Too many requests. Please wait ' . ceil($r['retry_after'] / 60) . ' minute(s) and try again.',
    ]);
    exit;
}

/** Housekeeping: drop rows nobody is counting any more. */
function rlPrune(int $olderThanSeconds = 86400): void
{
    rlEnsureTable();
    try {
        dbExecute(
            "DELETE FROM rate_limits
              WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND (blocked_until IS NULL OR blocked_until < NOW())",
            [$olderThanSeconds]
        );
    } catch (Exception $e) { /* best effort */ }
}
