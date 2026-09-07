<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false]);
  exit;
}

/* A person subscribes once; a script would try thousands. */
rlGuardJson('api.newsletter', 5, 3600, 3600);

/* Without this, any site could subscribe a visitor and send a welcome mail
   in their name. Same check api/contact.php uses, answering in JSON rather
   than the HTML that verifyCsrf() would print. */
$csrf = $_POST[CSRF_TOKEN_NAME] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Your session expired. Please reload the page and try again.']);
  exit;
}

$email = sanitizeInput($_POST['email'] ?? '');
$name  = sanitizeInput($_POST['name']  ?? '');

if (!validateEmail($email)) {
  echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
  exit;
}

/* Check if already subscribed */
$existing = dbFetchOne("SELECT id, status FROM newsletter_subscribers WHERE email = ?", [$email]);
if ($existing) {
  if ($existing['status'] === 'active') {
    echo json_encode(['ok' => true, 'message' => 'You are already subscribed!']);
  } else {
    dbExecute(
      "UPDATE newsletter_subscribers SET status = 'active', confirmed_at = NOW(), unsubscribed_at = NULL WHERE id = ?",
      [$existing['id']]
    );
    echo json_encode(['ok' => true, 'message' => 'Welcome back! You have been re-subscribed.']);
  }
  exit;
}

dbInsertRow('newsletter_subscribers', [
  'email'      => $email,
  'name'       => $name,
  'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
  'status'     => 'active',
]);

/* ── Welcome email to new subscriber ── */
require_once dirname(__DIR__) . '/includes/email-template.php';
$firstName = $name ? htmlspecialchars(explode(' ', trim($name))[0]) : 'there';
sendTemplatedEmail($email, 'Welcome to Appsgain Technologies Newsletter! 🎉', [
    'type'        => 'success',
    'accent_color'=> '#4f46e5',
    'heading'     => "Welcome, {$firstName}! 🎉",
    'subheading'  => 'You\'re now subscribed to our newsletter',
    'body'        => '<p style="font-size:15px;color:#334155;line-height:1.8;">Thank you for subscribing to the <strong>Appsgain Technologies</strong> newsletter!</p>'
                   . '<p style="font-size:15px;color:#334155;line-height:1.8;">You\'ll receive updates on:</p>'
                   . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">'
                   . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">🚀 &nbsp;<strong>Product launches</strong> &amp; new features</td></tr>'
                   . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">📝 &nbsp;<strong>Tech articles</strong> &amp; industry insights</td></tr>'
                   . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">💡 &nbsp;<strong>Case studies</strong> &amp; success stories</td></tr>'
                   . '<tr><td style="padding:8px 0;font-size:14px;color:#334155;">🎯 &nbsp;<strong>Exclusive offers</strong> &amp; early access</td></tr>'
                   . '</table>',
    'cta_text'    => 'Read Our Latest Blog',
    'cta_url'     => (defined('SITE_URL') ? SITE_URL : '') . '/blog.php',
    'cta_color'   => '#4f46e5',
    'footer_extra'=> 'You can unsubscribe at any time by replying with "unsubscribe".',
]);

/* ── Notify configured newsletter recipients ── */
$nlRecipients = getNotifyEmails('newsletter');
if (!empty($nlRecipients)) {
    $nlOpts = [
        'type'      => 'info',
        'heading'   => '📬 New Newsletter Subscriber',
        'subheading'=> 'Someone just subscribed to your newsletter',
        'info_rows' => array_values(array_filter([
            ['label'=>'Email', 'value'=> '<a href="mailto:'.htmlspecialchars($email).'" style="color:#4f46e5;">'.htmlspecialchars($email).'</a>'],
            $name ? ['label'=>'Name', 'value'=> htmlspecialchars($name)] : null,
        ])),
        'cta_text'  => 'View Subscribers',
        'cta_url'   => (defined('ADMIN_URL') ? ADMIN_URL : '') . '/pages/newsletter.php',
        'cta_color' => '#2563eb',
    ];
    foreach ($nlRecipients as $recipient) {
        sendTemplatedEmail($recipient, "📬 New Newsletter Subscriber: {$email}", $nlOpts);
    }
}

echo json_encode(['ok' => true, 'message' => 'Subscribed successfully! Check your inbox for a welcome email.']);
