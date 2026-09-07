<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'success' => false, 'message' => 'Method not allowed']);
    exit;
}

/* ── Parse body: JSON or form-encoded ── */
$ct   = $_SERVER['CONTENT_TYPE'] ?? '';
$body = [];
if (str_contains($ct, 'application/json')) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
} else {
    $body = $_POST;
}

/* ── CSRF verification: header, body field, or POST field ── */
/* 8 enquiries an hour from one address is well past genuine use. */
rlGuardJson('api.contact', 8, 3600, 1800);

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN']
     ?? $body[CSRF_TOKEN_NAME]
     ?? $body['csrf_token']
     ?? $_POST[CSRF_TOKEN_NAME]
     ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'success' => false, 'message' => 'Security token mismatch. Please refresh the page and try again.']);
    exit;
}

/* ── CAPTCHA verification ── */
$captchaSubmitted = sanitizeInput((string)($body['captcha_code'] ?? $_POST['captcha_code'] ?? ''));
/* Was: !empty($body['captcha_skip']) — a client-supplied flag, so any
   caller could switch the CAPTCHA off entirely. Nothing in the codebase
   sends it. Trust is now server-side only. */
$captchaSkip      = defined('AG_INTERNAL_CALL') && AG_INTERNAL_CALL === true;

if (!$captchaSkip) {
    $sessionCode    = $_SESSION['captcha_code'] ?? ''; /* case-sensitive */
    $captchaExpires = (int)($_SESSION['captcha_expires'] ?? 0);

    if (empty($sessionCode)) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'success'=>false,'message'=>'CAPTCHA session expired. Please refresh the page.','field'=>'captcha_code']);
        exit;
    }
    if ($captchaExpires < time()) {
        unset($_SESSION['captcha_code'], $_SESSION['captcha_expires']);
        http_response_code(422);
        echo json_encode(['ok'=>false,'success'=>false,'message'=>'CAPTCHA has expired. Please refresh the image and try again.','field'=>'captcha_code']);
        exit;
    }
    if (empty($captchaSubmitted) || !hash_equals($sessionCode, $captchaSubmitted)) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'success'=>false,'message'=>'Incorrect CAPTCHA code. Please check the image and try again.','field'=>'captcha_code']);
        exit;
    }
    /* Valid — invalidate immediately (one-time use) */
    unset($_SESSION['captcha_code'], $_SESSION['captcha_expires']);
}

/* ── Extract fields ── */
$get = fn(string $k) => sanitizeInput((string)($body[$k] ?? ''));
$name    = $get('name');
$email   = $get('email');
$phone   = $get('phone');
$company = $get('company');
/* Accept 'service' or legacy 'subject' */
$service = $get('service') ?: $get('subject');
$budget  = $get('budget');
$message = $get('message');
$source  = $get('source') ?: 'website';

/* Phone: present and plausible. It was only checked for emptiness, so a
   lead could arrive with "abc" in the phone field and no way to call it.
   Same digit-count rule api/enquiry.php and api/apply.php already use. */
if (empty($phone)) {
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'Phone number is required.','field'=>'phone']);
    exit;
}
$phoneDigits = preg_replace('/\D/', '', $phone);
if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) {
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'Please enter a valid phone number.','field'=>'phone']);
    exit;
}

/* Honeypot */
if (!empty($body['website']) || !empty($body['honeypot'])) {
    echo json_encode(['ok' => true, 'success' => true, 'message' => 'Thank you!']);
    exit;
}

/* Default message when left blank (quick-form shortcut) */
if (empty($message) && !empty($service)) {
    $message = "Enquiry about: {$service}";
}

/* ── Validation ── */
$errors = [];
if (strlen($name) < 2)       $errors[] = 'Please enter your full name.';
if (!validateEmail($email))  $errors[] = 'Please enter a valid email address.';
if (strlen($message) < 5)    $errors[] = 'Please provide a brief message.';
if (strlen($message) > 5000) $errors[] = 'Message is too long (max 5000 chars).';

if ($errors) {
    echo json_encode(['ok' => false, 'success' => false, 'errors' => $errors, 'message' => $errors[0]]);
    exit;
}

/* ── Save lead ── */
$leadId = dbInsertRow('leads', [
    'name'        => $name,
    'email'       => $email,
    'phone'       => $phone,
    'company'     => $company,
    'service'     => $service ?: 'General Enquiry',
    'message'     => $message,
    'source_page' => $source,
    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
]);

/* ── Beautiful emails via template ── */
require_once dirname(__DIR__) . '/includes/email-template.php';

/* Admin notification — send to all configured contact recipients */
$notifyRecipients = getNotifyEmails('contact');
if (!empty($notifyRecipients)) {
    $rows = array_values(array_filter([
        ['label'=>'Name',    'value'=> htmlspecialchars($name)],
        ['label'=>'Email',   'value'=> '<a href="mailto:'.htmlspecialchars($email).'" style="color:#4f46e5;font-weight:700;">'.htmlspecialchars($email).'</a>'],
        ['label'=>'Phone',   'value'=> htmlspecialchars($phone)],
        $company ? ['label'=>'Company','value'=> htmlspecialchars($company)] : null,
        $service ? ['label'=>'Service','value'=> htmlspecialchars($service)] : null,
        $budget  ? ['label'=>'Budget', 'value'=> htmlspecialchars($budget)]  : null,
        $message ? ['label'=>'Message','value'=> nl2br(htmlspecialchars($message))] : null,
    ]));
    $notifSubject = "🔔 New Lead: {$name} — " . ($service ?: 'General Enquiry');
    $notifOpts = [
        'type'       => 'info',
        'heading'    => '🔔 New Lead Received',
        'subheading' => 'A visitor just submitted the contact form on your website',
        'body'       => '<p style="color:#334155;font-size:15px;margin:0 0 4px;">Review the details below and respond within 24 hours.</p>',
        'info_rows'  => $rows,
        'cta_text'   => 'View in Admin Panel',
        'cta_url'    => ADMIN_URL . '/pages/leads.php',
        'cta_color'  => '#4f46e5',
    ];
    foreach ($notifyRecipients as $recipient) {
        sendTemplatedEmail($recipient, $notifSubject, $notifOpts);
    }
}

/* Auto-reply to submitter */
$firstName = htmlspecialchars(explode(' ', trim($name))[0]);
sendTemplatedEmail($email, 'We received your message — Appsgain Technologies', [
    'type'        => 'success',
    'heading'     => "Thank You, {$firstName}! 🎉",
    'subheading'  => 'Your message has been successfully received',
    'body'        => '<p style="font-size:15px;color:#334155;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>'
                   . '<p style="font-size:15px;color:#334155;line-height:1.8;">Thank you for reaching out to <strong>Appsgain Technologies</strong>! We have received your enquiry'
                   . ($service ? ' regarding <strong>' . htmlspecialchars($service) . '</strong>' : '') . ' and our team will review your requirements promptly.</p>'
                   . '<p style="font-size:15px;color:#334155;line-height:1.8;">We will get back to you within <strong>24 hours</strong> with a tailored proposal. No commitment required.</p>'
                   . '<p style="font-size:14px;color:#64748b;padding:16px;background:#f8faff;border-radius:10px;border-left:4px solid #4f46e5;">'
                   . '📞 Need immediate assistance? Call us: <a href="tel:+919955446477" style="color:#4f46e5;font-weight:700;">+91-9955446477</a></p>',
    'cta_text'    => 'View Our Portfolio',
    'cta_url'     => SITE_URL . '/portfolio.php',
    'cta_color'   => '#4f46e5',
    'cta2_text'   => 'Explore All Services',
    'cta2_url'    => SITE_URL . '/services.php',
    'footer_extra'=> 'This auto-reply confirms we received your message. Our team will follow up personally.',
]);

$response = ['ok' => true, 'success' => true, 'message' => 'Thank you! We will get back to you within 24 hours.', 'id' => $leadId];
echo json_encode($response);
