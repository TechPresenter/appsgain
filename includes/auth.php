<?php
/**
 * APPSGAIN CMS — Authentication (Frontend + Admin)
 */

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /** Attempt admin login; returns error string or null on success. */
    public static function adminLogin(string $email, string $password): ?string {
        $email = strtolower(trim($email));

        /* Per-IP ceiling on FAILURES. The per-account lockout below still
           applies, but on its own it never sees one password sprayed across
           many addresses, and it lets anyone lock a colleague out by failing
           on their behalf. Successes are not counted, so a shared office
           address does not throttle itself just by signing in. */
        if (function_exists('rlBlocked')) {
            $ip = rlBlocked('login.ip');
            if ($ip['blocked']) {
                $mins = max(1, (int)ceil($ip['retry_after'] / 60));
                return "Too many sign-in attempts from this network. Try again in {$mins} minute(s).";
            }
        }

        $user = dbFetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user) {
            /* Spend roughly the same time as a real check so the response
               does not reveal which addresses exist. */
            password_verify($password, '$2y$12$usesomesillystringforsalt.LqvJm0Km1a5rWJ8Y5nJ5J5J5J5J5Ju');
            if (function_exists('rlHit')) rlHit('login.ip', 10, 900, LOCKOUT_DURATION);
            return 'Invalid email or password.';
        }

        // Lockout check
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            return "Account temporarily locked. Try again in {$remaining} minute(s).";
        }

        if (!password_verify($password, $user['password'])) {
            if (function_exists('rlHit')) rlHit('login.ip', 10, 900, LOCKOUT_DURATION);
            $attempts = $user['login_attempts'] + 1;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                dbExecute(
                    "UPDATE users SET login_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = ?",
                    [$attempts, LOCKOUT_DURATION, $user['id']]
                );
                return 'Too many failed attempts. Account locked for ' . (LOCKOUT_DURATION / 60) . ' minutes.';
            }
            dbExecute("UPDATE users SET login_attempts = ? WHERE id = ?", [$attempts, $user['id']]);
            return 'Invalid email or password.';
        }

        // Success — one bad typo should not count against the next hour.
        if (function_exists('rlClear')) rlClear('login.ip');
        dbExecute(
            "UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?",
            [$user['id']]
        );
        session_regenerate_id(true);
        $_SESSION['admin_login_at']  = time();
        $_SESSION['admin_last_seen'] = time();
        $_SESSION['admin_rotated_at'] = time();
        /* Bound to the user agent so a cookie lifted from one machine is not
           silently usable on another. Deliberately not bound to the IP:
           mobile networks change address mid-session. */
        $_SESSION['admin_ua'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $_SESSION['admin_id']    = $user['id'];
        $_SESSION['admin_name']  = $user['name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['role'];
        $_SESSION['admin_avatar']= $user['avatar'] ?? '';

        logActivity('login', 'auth', 'Admin logged in');
        return null;
    }

    public static function adminLogout(): void {
        logActivity('logout', 'auth', 'Admin logged out');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isAdminLoggedIn(): bool {
        if (empty($_SESSION['admin_id'])) return false;

        $now = time();

        /* Idle timeout: an unattended desk should not stay signed in. */
        $last = (int)($_SESSION['admin_last_seen'] ?? $now);
        if ($now - $last > ADMIN_IDLE_TIMEOUT) {
            self::endSession();
            return false;
        }

        /* Absolute cap, regardless of activity. */
        $since = (int)($_SESSION['admin_login_at'] ?? $now);
        if ($now - $since > ADMIN_ABSOLUTE_TIMEOUT) {
            self::endSession();
            return false;
        }

        /* A session moved to a different browser is not the same session. */
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        if (!empty($_SESSION['admin_ua']) && !hash_equals($_SESSION['admin_ua'], $ua)) {
            self::endSession();
            return false;
        }

        /* Rotate the id periodically so a captured one has a short life. */
        $rotated = (int)($_SESSION['admin_rotated_at'] ?? 0);
        if ($now - $rotated > ADMIN_ID_ROTATE_EVERY) {
            session_regenerate_id(true);
            $_SESSION['admin_rotated_at'] = $now;
        }

        $_SESSION['admin_last_seen'] = $now;
        return true;
    }

    /** Tear a session down without the activity-log entry a real logout makes. */
    private static function endSession(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $c = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }

    public static function requireAdmin(): void {
        if (!self::isAdminLoggedIn()) {
            redirect(ADMIN_URL . '/login.php?redirect=' . urlencode(currentUrl()));
        }
    }

    public static function requireRole(string ...$roles): void {
        self::requireAdmin();
        if (!in_array($_SESSION['admin_role'], $roles)) {
            http_response_code(403);
            die('<h1>403 — Access Denied</h1><p>You do not have permission to access this page.</p>');
        }
    }

    public static function admin(): array {
        return [
            'id'     => $_SESSION['admin_id'] ?? 0,
            'name'   => $_SESSION['admin_name'] ?? '',
            'email'  => $_SESSION['admin_email'] ?? '',
            'role'   => $_SESSION['admin_role'] ?? '',
            'avatar' => $_SESSION['admin_avatar'] ?? '',
        ];
    }

    public static function isSuperAdmin(): bool {
        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}
