<?php
/**
 * Appsgain — Job Application API
 * Handles career form submission with mandatory resume upload
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

/* Applications carry an upload, so abuse is expensive to absorb. */
rlGuardJson('api.apply', 5, 3600, 1800);
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

/* ── CSRF ── */
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN']
     ?? $_POST[CSRF_TOKEN_NAME]
     ?? $_POST['csrf_token']
     ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Security token mismatch. Please refresh the page.']);
    exit;
}

/* ── CAPTCHA ── */
$captchaSubmitted = sanitizeInput($_POST['captcha_code'] ?? '');
$sessionCode      = $_SESSION['captcha_code'] ?? '';
$captchaExpires   = (int)($_SESSION['captcha_expires'] ?? 0);

if (empty($sessionCode)) {
    echo json_encode(['ok' => false, 'message' => 'CAPTCHA session expired. Please refresh.']);
    exit;
}
if ($captchaExpires < time()) {
    unset($_SESSION['captcha_code'], $_SESSION['captcha_expires']);
    echo json_encode(['ok' => false, 'message' => 'CAPTCHA expired. Please refresh and try again.']);
    exit;
}
if (empty($captchaSubmitted) || !hash_equals($sessionCode, $captchaSubmitted)) {
    echo json_encode(['ok' => false, 'message' => 'Incorrect CAPTCHA. Please check the image and try again.']);
    exit;
}
unset($_SESSION['captcha_code'], $_SESSION['captcha_expires']);

/* ── Fields ── */
$name     = sanitizeInput($_POST['name']     ?? '');
$email    = sanitizeInput($_POST['email']    ?? '');
$phone    = sanitizeInput($_POST['phone']    ?? '');
$position = sanitizeInput($_POST['position'] ?? '');
$message  = sanitizeInput($_POST['message']  ?? '');
$linkedin = sanitizeInput($_POST['linkedin'] ?? '');

/* ── Validate ── */
$errors = [];
if (strlen($name) < 2)      $errors[] = 'Full name is required.';
if (!validateEmail($email))  $errors[] = 'Valid email address is required.';
if (empty($phone) || strlen(preg_replace('/\D/', '', $phone)) < 7) $errors[] = 'Phone number is required.';

if ($errors) {
    echo json_encode(['ok' => false, 'message' => $errors[0], 'errors' => $errors]);
    exit;
}

/* ── Resume upload (mandatory) ── */
if (empty($_FILES['resume']['name'])) {
    echo json_encode(['ok' => false, 'message' => 'Resume is required. Please upload your CV (PDF, DOC, DOCX).']);
    exit;
}

$file    = $_FILES['resume'];
$origName = basename($file['name']);
$ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed = ['pdf', 'doc', 'docx'];
$maxSize = 5 * 1024 * 1024; /* 5 MB */

if (!in_array($ext, $allowed)) {
    echo json_encode(['ok' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed.']);
    exit;
}
if ($file['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'message' => 'Resume file size must be under 5 MB.']);
    exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'message' => 'File upload failed. Please try again.']);
    exit;
}

/* ── Save file ── */
$uploadDir = __DIR__ . '/../uploads/resumes/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* Safe filename: timestamp_name_hash.ext */
$safeName  = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($origName, PATHINFO_FILENAME));
$filename  = date('Ymd_His') . '_' . substr($safeName, 0, 40) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath  = $uploadDir . $filename;
$dbPath    = 'resumes/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'message' => 'Could not save your resume. Please try again.']);
    exit;
}

/* ── Save to DB ── */
try {
    /* Auto-create table if missing */
    db()->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(200) NOT NULL DEFAULT '',
        email       VARCHAR(255) NOT NULL DEFAULT '',
        phone       VARCHAR(50)  NOT NULL DEFAULT '',
        position    VARCHAR(200) NOT NULL DEFAULT '',
        message     TEXT,
        linkedin    VARCHAR(500) NOT NULL DEFAULT '',
        resume_path VARCHAR(500) NOT NULL DEFAULT '',
        ip_address  VARCHAR(45)  NOT NULL DEFAULT '',
        status      ENUM('new','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'new',
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email(email),
        INDEX idx_status(status),
        INDEX idx_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $appId = dbInsertRow('job_applications', [
        'name'        => $name,
        'email'       => $email,
        'phone'       => $phone,
        'position'    => $position ?: 'General Application',
        'message'     => $message,
        'linkedin'    => $linkedin,
        'resume_path' => $dbPath,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
} catch (Exception $ex) {
    /* DB failed but file was uploaded — still notify admin */
    $appId = null;
}

/* ── Beautiful emails via template ── */
require_once dirname(__DIR__) . '/includes/email-template.php';

/* Admin notification — send to all configured application recipients */
$appRecipients = getNotifyEmails('application');
if (!empty($appRecipients)) {
    $rows = array_values(array_filter([
        ['label'=>'Applicant', 'value'=> htmlspecialchars($name)],
        ['label'=>'Email',     'value'=> '<a href="mailto:'.htmlspecialchars($email).'" style="color:#4f46e5;font-weight:700;">'.htmlspecialchars($email).'</a>'],
        ['label'=>'Phone',     'value'=> htmlspecialchars($phone)],
        ['label'=>'Position',  'value'=> '<strong>'.htmlspecialchars($position ?: 'General Application').'</strong>'],
        $linkedin ? ['label'=>'LinkedIn','value'=> '<a href="'.htmlspecialchars($linkedin).'" style="color:#0a66c2;font-weight:700;">View LinkedIn Profile →</a>'] : null,
        $message  ? ['label'=>'Message', 'value'=> nl2br(htmlspecialchars($message))] : null,
        ['label'=>'Resume',    'value'=> '<a href="'.UPLOADS_URL.'/'.htmlspecialchars($dbPath).'" style="color:#059669;font-weight:700;">Download Resume →</a>'],
    ]));
    $appOpts = [
        'type'        => 'warning',
        'accent_color'=> '#d97706',
        'heading'     => '📋 New Job Application',
        'subheading'  => 'A candidate just applied via your careers page',
        'body'        => '<p style="color:#334155;font-size:15px;">Review the application details below. The resume has been attached and saved to your server.</p>',
        'info_rows'   => $rows,
        'cta_text'    => 'View All Applications',
        'cta_url'     => ADMIN_URL . '/pages/applications.php',
        'cta_color'   => '#d97706',
    ];
    foreach ($appRecipients as $recipient) {
        sendTemplatedEmail($recipient, "📋 New Job Application: {$name} — " . ($position ?: 'General'), $appOpts);
    }
}

/* Auto-confirm to applicant */
$firstName = htmlspecialchars(explode(' ', trim($name))[0]);
sendTemplatedEmail($email, 'Application Received — Appsgain Technologies', [
    'type'        => 'success',
    'accent_color'=> '#059669',
    'heading'     => "Application Received, {$firstName}! 🎯",
    'subheading'  => 'Thank you for applying to Appsgain Technologies',
    'body'        => '<p style="font-size:15px;color:#334155;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>'
                   . '<p style="font-size:15px;color:#334155;line-height:1.8;">We have successfully received your application for <strong>' . htmlspecialchars($position ?: 'a position') . '</strong> at <strong>Appsgain Technologies</strong>.</p>'
                   . '<p style="font-size:15px;color:#334155;line-height:1.8;">Our HR team will carefully review your resume and qualifications. If your profile matches our requirements, we will contact you within <strong>3–5 business days</strong> to schedule the next steps.</p>'
                   . '<p style="font-size:14px;color:#64748b;padding:16px;background:#f0fdf4;border-radius:10px;border-left:4px solid #059669;line-height:1.7;">'
                   . '✅ <strong>What happens next?</strong><br>'
                   . '• Our team reviews your resume<br>'
                   . '• If shortlisted, we\'ll reach out for a screening call<br>'
                   . '• Interview process (Technical + HR rounds)<br>'
                   . '• Offer letter and onboarding</p>',
    'cta_text'    => 'View Open Positions',
    'cta_url'     => SITE_URL . '/careers.php',
    'cta_color'   => '#059669',
    'footer_extra'=> 'Appsgain Technologies — Building the Future of Digital Innovation',
]);

echo json_encode([
    'ok'      => true,
    'message' => 'Your application has been submitted successfully! We\'ll review your resume and be in touch within 3–5 business days.',
    'id'      => $appId,
]);
