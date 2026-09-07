<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (function_exists('rlGuardJson')) rlGuardJson('api.search', 60, 600, 600);
header('Cache-Control: no-store');

$q     = sanitizeInput($_GET['q'] ?? '');
$limit = min(10, max(1, (int)($_GET['limit'] ?? 8)));

if (strlen($q) < 2) {
    echo json_encode(['ok' => true, 'results' => [], 'query' => $q]);
    exit;
}

$like    = "%{$q}%";
$results = [];
$perType = max(2, (int)ceil($limit / 3));

/* ── Blog posts ── */
try {
    $blogs = dbFetchAll(
        "SELECT title, slug, excerpt AS descr FROM blogs
         WHERE status = 'published' AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)
         LIMIT {$perType}",
        [$like, $like, $like]
    );
    foreach ($blogs as $b) {
        $results[] = [
            'type'  => 'Blog',
            'title' => $b['title'],
            'url'   => SITE_URL . '/blog/' . $b['slug'],
            'desc'  => truncate($b['descr'] ?? '', 80),
        ];
    }
} catch (Exception $e) { /* table may be empty */ }

/* ── Services ── (column is `name`, not `title`) */
try {
    $services = dbFetchAll(
        "SELECT name, slug, excerpt, short_description FROM services
         WHERE is_active = 1 AND (name LIKE ? OR excerpt LIKE ? OR short_description LIKE ?)
         LIMIT {$perType}",
        [$like, $like, $like]
    );
    foreach ($services as $s) {
        $results[] = [
            'type'  => 'Service',
            'title' => $s['name'],
            'url'   => SITE_URL . '/service/' . $s['slug'],
            'desc'  => truncate($s['excerpt'] ?: $s['short_description'] ?? '', 80),
        ];
    }
} catch (Exception $e) { /* graceful */ }

/* ── Projects ── (uses `is_active`, not `status`) */
try {
    $projects = dbFetchAll(
        "SELECT title, slug, short_description AS descr FROM projects
         WHERE is_active = 1 AND (title LIKE ? OR short_description LIKE ?)
         LIMIT {$perType}",
        [$like, $like]
    );
    foreach ($projects as $p) {
        $results[] = [
            'type'  => 'Project',
            'title' => $p['title'],
            'url'   => SITE_URL . '/portfolio/' . $p['slug'],
            'desc'  => truncate($p['descr'] ?? '', 80),
        ];
    }
} catch (Exception $e) { /* graceful */ }

/* ── Products ── */
try {
    $products = dbFetchAll(
        "SELECT name, slug, short_description AS descr FROM products
         WHERE is_active = 1 AND (name LIKE ? OR short_description LIKE ?)
         LIMIT {$perType}",
        [$like, $like]
    );
    foreach ($products as $p) {
        $results[] = [
            'type'  => 'Product',
            'title' => $p['name'],
            'url'   => SITE_URL . '/product/' . $p['slug'],
            'desc'  => truncate($p['descr'] ?? '', 80),
        ];
    }
} catch (Exception $e) { /* graceful */ }

$results = array_slice($results, 0, $limit);
echo json_encode(['ok' => true, 'query' => $q, 'count' => count($results), 'results' => $results]);
