<?php
/**
 * Admin — Real-time visitor stats (polled every 30s by reports.php)
 */
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';
Auth::requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$now    = 0;
$five   = 0;
$thirty = 0;
$chart  = [];
$pages  = [];

try {
    $now    = (int) dbFetchValue("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 3 MINUTE)");
    $five   = (int) dbFetchValue("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 5 MINUTE)");
    $thirty = (int) dbFetchValue("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 30 MINUTE)");

    /* Top active pages */
    $rows   = dbFetchAll("SELECT exit_page url, COUNT(*) c FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 5 MINUTE) AND exit_page IS NOT NULL GROUP BY exit_page ORDER BY c DESC LIMIT 6");
    foreach ($rows as $r) {
        $slug = parse_url($r['url'] ?? '/', PHP_URL_PATH) ?: '/';
        $pages[] = ['url' => substr($slug, 0, 60), 'count' => (int)$r['c']];
    }
} catch (\Throwable $e) {}

echo json_encode(['now'=>$now,'five'=>$five,'thirty'=>$thirty,'chart'=>$chart,'pages'=>$pages]);
