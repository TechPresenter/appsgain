<?php
/**
 * Appsgain Admin — Real-time new form submission checker
 * Called every 20s by admin panel JS to show popup notifications
 */
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

/* Must be logged-in admin */
if (!Auth::isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$since = isset($_GET['since']) ? (int)$_GET['since'] : (time() - 30);
$sinceDate = date('Y-m-d H:i:s', max($since, time() - 300)); /* cap at 5 min */

$notifications = [];

/* ── New Leads (contact + service quote forms) ── */
try {
    $leads = dbFetchAll(
        "SELECT id, name, email, phone, service, source_page, created_at
         FROM leads
         WHERE created_at > ?
         ORDER BY created_at DESC
         LIMIT 5",
        [$sinceDate]
    );
    foreach ($leads as $lead) {
        $src = $lead['source_page'] ?? 'website';
        $svc = $lead['service']     ?? 'General Enquiry';
        $notifications[] = [
            'id'      => 'lead_' . $lead['id'],
            'type'    => 'lead',
            'icon'    => 'fa-user-plus',
            'color'   => '#4f46e5',
            'bg'      => 'rgba(79,70,229,.12)',
            'title'   => 'New Lead: ' . ($lead['name'] ?: 'Unknown'),
            'message' => $svc . ' · ' . ucwords(str_replace(['-','_'], ' ', $src)),
            'email'   => $lead['email'] ?? '',
            'phone'   => $lead['phone'] ?? '',
            'time'    => $lead['created_at'],
            'url'     => ADMIN_URL . '/pages/leads.php',
        ];
    }
} catch (Exception $e) {}

/* ── Newsletter Signups ── */
try {
    $subs = dbFetchAll(
        "SELECT id, email, name, created_at
         FROM newsletter_subscribers
         WHERE created_at > ?
         ORDER BY created_at DESC
         LIMIT 3",
        [$sinceDate]
    );
    foreach ($subs as $sub) {
        $notifications[] = [
            'id'      => 'nl_' . $sub['id'],
            'type'    => 'newsletter',
            'icon'    => 'fa-envelope-open-text',
            'color'   => '#059669',
            'bg'      => 'rgba(5,150,105,.12)',
            'title'   => 'Newsletter Signup',
            'message' => $sub['email'] . ($sub['name'] ? ' — ' . $sub['name'] : ''),
            'email'   => $sub['email'] ?? '',
            'phone'   => '',
            'time'    => $sub['created_at'],
            'url'     => ADMIN_URL . '/pages/newsletter.php',
        ];
    }
} catch (Exception $e) {}

/* ── Partner Inquiries ── */
try {
    $partners = dbFetchAll(
        "SELECT id, contact_name, company_name, email, created_at
         FROM partner_inquiries
         WHERE created_at > ?
         ORDER BY created_at DESC
         LIMIT 3",
        [$sinceDate]
    );
    foreach ($partners as $p) {
        $notifications[] = [
            'id'      => 'partner_' . $p['id'],
            'type'    => 'partner',
            'icon'    => 'fa-handshake',
            'color'   => '#d97706',
            'bg'      => 'rgba(217,119,6,.12)',
            'title'   => 'Partner Inquiry',
            'message' => ($p['contact_name'] ?: 'Unknown') . ($p['company_name'] ? ' · ' . $p['company_name'] : ''),
            'email'   => $p['email'] ?? '',
            'phone'   => '',
            'time'    => $p['created_at'],
            'url'     => ADMIN_URL . '/pages/partners.php',
        ];
    }
} catch (Exception $e) {}

/* ── Sort newest first ── */
usort($notifications, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

echo json_encode([
    'ok'            => true,
    'notifications' => $notifications,
    'server_time'   => time(),
    'count'         => count($notifications),
]);
