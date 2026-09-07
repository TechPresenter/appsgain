<?php
/**
 * Quick-enquiry endpoint for the site-wide popup.
 *
 * Separate from api/contact.php because the popup is deliberately
 * frictionless — four fields, no CAPTCHA. The spam defence is layered
 * instead of visible:
 *
 *   1. CSRF token          — same-origin only
 *   2. honeypot field      — hidden input a human never fills
 *   3. time trap           — a form submitted in under 2.5s is scripted
 *   4. per-IP rate limit   — counted from the leads table, no new schema
 *   5. strict validation   — length and format bounds on every field
 *
 * Rows land in `leads` with source_page = 'popup', so they appear in
 * Admin -> Enquiries alongside everything else. No parallel table, no
 * second admin screen to maintain.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

rlGuardJson('api.enquiry', 8, 3600, 1800);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/** Emit JSON and stop. */
function agReply(bool $ok, string $message, array $extra = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'success' => $ok, 'message' => $message] + $extra);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    agReply(false, 'Method not allowed.', [], 405);
}

/* ── Body: JSON or form-encoded ── */
$ct   = $_SERVER['CONTENT_TYPE'] ?? '';
$body = str_contains($ct, 'application/json')
      ? (json_decode(file_get_contents('php://input'), true) ?: [])
      : $_POST;

/* ── 1 · CSRF ── */
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN']
     ?? $body[CSRF_TOKEN_NAME]
     ?? $body['csrf_token']
     ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
    agReply(false, 'Your session expired. Please refresh the page and try again.', [], 403);
}

/* ── 2 · Honeypot — answer normally so a bot learns nothing ── */
if (!empty($body['website']) || !empty($body['company_url'])) {
    agReply(true, 'Thank you! We will be in touch shortly.');
}

/* ── 3 · Time trap ── */
$openedAt = (int)($body['t'] ?? 0);
if ($openedAt > 0 && (microtime(true) * 1000 - $openedAt) < 2500) {
    agReply(false, 'That was a little too quick — please try again.', [], 422);
}

/* ── 4 · Rate limit, counted from leads themselves ── */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip !== '') {
    $recent = (int) dbFetchValue(
        "SELECT COUNT(*) FROM leads
         WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
        [$ip]
    );
    if ($recent >= 3) {
        agReply(false, 'You have already sent us a few messages. We will reply shortly.', [], 429);
    }
    $today = (int) dbFetchValue(
        "SELECT COUNT(*) FROM leads
         WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
        [$ip]
    );
    if ($today >= 10) {
        agReply(false, 'Daily enquiry limit reached. Please email us directly.', [], 429);
    }
}

/* ── 5 · Fields ── */
$get     = fn(string $k) => sanitizeInput((string)($body[$k] ?? ''));
$name    = $get('name');
$email   = $get('email');
$dial    = preg_replace('/[^+0-9]/', '', (string)($body['dial'] ?? '+91'));
$phoneNo = preg_replace('/[^0-9]/', '', (string)($body['phone'] ?? ''));
$country = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)($body['country'] ?? '')));
$need    = $get('requirement');

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
    $errors['name'] = 'Please enter your name.';
} elseif (!preg_match('/^[\p{L}\p{M}\.\'\- ]+$/u', $name)) {
    $errors['name'] = 'Please use letters only in your name.';
}
if (!validateEmail($email) || mb_strlen($email) > 200) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (strlen($phoneNo) < 6 || strlen($phoneNo) > 15) {
    $errors['phone'] = 'Please enter a valid mobile number.';
}
if (!preg_match('/^\+\d{1,4}$/', $dial)) {
    $dial = '+91';
}
if (mb_strlen($need) < 5) {
    $errors['requirement'] = 'Tell us briefly what you need.';
} elseif (mb_strlen($need) > 1000) {
    $errors['requirement'] = 'Please keep it under 1000 characters.';
}

if ($errors) {
    agReply(false, reset($errors), ['errors' => $errors, 'field' => array_key_first($errors)], 422);
}

/* ── Store ── */
$phone  = $dial . ' ' . $phoneNo;
$source = 'popup';
$page   = sanitizeInput((string)($body['page'] ?? ''));
if ($page !== '') { $source = 'popup: ' . mb_substr($page, 0, 180); }
if ($country !== '') { $source .= ' [' . mb_substr($country, 0, 2) . ']'; }

$leadId = dbInsertRow('leads', [
    'name'        => $name,
    'email'       => $email,
    'phone'       => $phone,
    'service'     => 'Quick Enquiry',
    'message'     => $need,
    'source_page' => mb_substr($source, 0, 300),
    'ip_address'  => $ip,
]);

if (!$leadId) {
    agReply(false, 'We could not save your enquiry. Please try again or email us directly.', [], 500);
}

/* ── Notify — never let a mail failure lose a saved lead ── */
try {
    require_once dirname(__DIR__) . '/includes/email-template.php';
    $to = getNotifyEmails('contact');
    if (!empty($to)) {
        $rows = [
            ['label' => 'Name',        'value' => htmlspecialchars($name)],
            ['label' => 'Email',       'value' => '<a href="mailto:' . htmlspecialchars($email) . '" style="color:#6A00FF;font-weight:700;">' . htmlspecialchars($email) . '</a>'],
            ['label' => 'Mobile',      'value' => htmlspecialchars($phone)],
            ['label' => 'Requirement', 'value' => nl2br(htmlspecialchars($need))],
        ];
        $opts = [
            'type'       => 'info',
            'heading'    => 'New Quick Enquiry',
            'subheading' => 'Submitted from the enquiry popup',
            'body'       => '<p style="color:#334155;font-size:15px;margin:0 0 4px;">A visitor asked to be contacted. Details below.</p>',
            'info_rows'  => $rows,
            'cta_text'   => 'View in Admin Panel',
            'cta_url'    => ADMIN_URL . '/pages/leads.php',
            'cta_color'  => '#6A00FF',
        ];
        foreach ($to as $addr) {
            sendTemplatedEmail($addr, 'New Quick Enquiry: ' . $name, $opts);
        }
    }
} catch (Throwable $e) {
    error_log('[enquiry] notification failed for lead ' . $leadId . ': ' . $e->getMessage());
}

agReply(true, 'Thank you! Our team will contact you within 24 hours.', ['id' => (int)$leadId]);
