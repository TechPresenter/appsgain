<?php
$adminTitle = 'Analytics';
$adminPage  = 'reports';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireAdmin();

/* ── Date range ── */
$range    = sanitizeInput($_GET['range'] ?? '30');
$validRanges = ['1','7','14','30','60','90','365','custom'];
if (!in_array($range, $validRanges)) $range = '30';

if ($range === 'custom') {
    $dateFrom = sanitizeInput($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
    $dateTo   = sanitizeInput($_GET['to']   ?? date('Y-m-d'));
    // validate
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-30 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');
    $rangeDays  = (int)ceil((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1;
    $sqlFrom    = $dateFrom . ' 00:00:00';
    $sqlTo      = $dateTo   . ' 23:59:59';
} else {
    $rangeDays  = (int)$range;
    $sqlFrom    = date('Y-m-d 00:00:00', strtotime("-{$rangeDays} days"));
    $sqlTo      = date('Y-m-d 23:59:59');
    $dateFrom   = date('Y-m-d', strtotime("-{$rangeDays} days"));
    $dateTo     = date('Y-m-d');
}

$prevFrom   = date('Y-m-d 00:00:00', strtotime($sqlFrom) - $rangeDays * 86400);
$prevTo     = date('Y-m-d 23:59:59', strtotime($sqlTo)   - $rangeDays * 86400);
$activeTab  = sanitizeInput($_GET['tab'] ?? 'overview');
$validTabs  = ['overview','realtime','audience','geography','traffic','behavior','conversions'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'overview';

$rangeLabel = [
    '1'=>'Today','7'=>'Last 7 Days','14'=>'Last 14 Days',
    '30'=>'Last 30 Days','60'=>'Last 60 Days','90'=>'Last 90 Days',
    '365'=>'Last 12 Months','custom'=>"$dateFrom → $dateTo"
][$range] ?? 'Last 30 Days';

/* ── Check if analytics tables exist ── */
$hasAnalytics = false;
try {
    db()->query("SELECT 1 FROM analytics_sessions LIMIT 1");
    $hasAnalytics = true;
} catch (\Throwable $e) {}

/* ─────────────────────────────────────────────────────
   QUERIES — only run if tables exist
───────────────────────────────────────────────────── */
function aq(string $sql, array $params = []): mixed {
    try { return dbFetchAll($sql, $params); } catch(\Throwable $e) { return []; }
}
function aqv(string $sql, array $params = []): int {
    try { return (int)dbFetchValue($sql, $params); } catch(\Throwable $e) { return 0; }
}

/* Overview KPIs */
$kpis = [];
if ($hasAnalytics) {
    $kpis['sessions']     = aqv("SELECT COUNT(*) FROM analytics_sessions WHERE created_at BETWEEN ? AND ?",         [$sqlFrom,$sqlTo]);
    $kpis['sessions_prev']= aqv("SELECT COUNT(*) FROM analytics_sessions WHERE created_at BETWEEN ? AND ?",         [$prevFrom,$prevTo]);
    $kpis['pageviews']    = aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE created_at BETWEEN ? AND ?",        [$sqlFrom,$sqlTo]);
    $kpis['pageviews_prev']= aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE created_at BETWEEN ? AND ?",       [$prevFrom,$prevTo]);
    $kpis['unique']       = aqv("SELECT COUNT(DISTINCT visitor_id) FROM analytics_sessions WHERE created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]);
    $kpis['new_visitors'] = aqv("SELECT COUNT(*) FROM analytics_sessions WHERE is_new_visitor=1 AND created_at BETWEEN ? AND ?",[$sqlFrom,$sqlTo]);
    $kpis['bounced']      = aqv("SELECT COUNT(*) FROM analytics_sessions WHERE is_bounce=1 AND created_at BETWEEN ? AND ?",   [$sqlFrom,$sqlTo]);
    $kpis['bounce_rate']  = $kpis['sessions'] > 0 ? round($kpis['bounced'] / $kpis['sessions'] * 100, 1) : 0;
    $kpis['avg_duration'] = aqv("SELECT AVG(duration) FROM analytics_sessions WHERE duration > 0 AND created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]);
    $kpis['avg_pages']    = $kpis['sessions'] > 0 ? round($kpis['pageviews'] / $kpis['sessions'], 1) : 0;
    $kpis['pv_prev']      = $kpis['pageviews_prev'];

    /* Realtime — active in last 3 min */
    $kpis['realtime']     = aqv("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)");
    $kpis['realtime_5']   = aqv("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $kpis['realtime_30']  = aqv("SELECT COUNT(DISTINCT session_id) FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
}

/* ── Lead KPIs (always available) ── */
$leads_period = aqv("SELECT COUNT(*) FROM leads WHERE created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]);
$leads_prev   = aqv("SELECT COUNT(*) FROM leads WHERE created_at BETWEEN ? AND ?", [$prevFrom,$prevTo]);
$leads_conv   = aqv("SELECT COUNT(*) FROM leads WHERE status='converted' AND created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]);
$conv_rate    = $leads_period > 0 ? round($leads_conv / $leads_period * 100, 1) : 0;

/* ── Daily trend (for chart) ── */
$trendDays  = min($rangeDays, 90);
$trendLabels = $trendSessions = $trendPV = $trendLeads = [];
$sessMap = $pvMap = $leadMap = [];
if ($hasAnalytics) {
    $sessRows = aq("SELECT DATE(created_at) d, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY d", [$sqlFrom,$sqlTo]);
    $pvRows   = aq("SELECT DATE(created_at) d, COUNT(*) c FROM analytics_pageviews WHERE created_at BETWEEN ? AND ? GROUP BY d", [$sqlFrom,$sqlTo]);
    $sessMap  = array_column($sessRows, 'c', 'd');
    $pvMap    = array_column($pvRows,   'c', 'd');
}
$leadRows   = aq("SELECT DATE(created_at) d, COUNT(*) c FROM leads WHERE created_at BETWEEN ? AND ? GROUP BY d", [$sqlFrom,$sqlTo]);
$leadMap    = array_column($leadRows, 'c', 'd');
for ($i = $trendDays - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $fmt = $trendDays <= 14 ? date('M j', strtotime($d)) : ($trendDays <= 60 ? date('M j', strtotime($d)) : date('M Y', strtotime($d)));
    $trendLabels[]   = $fmt;
    $trendSessions[] = (int)($sessMap[$d] ?? 0);
    $trendPV[]       = (int)($pvMap[$d]   ?? 0);
    $trendLeads[]    = (int)($leadMap[$d] ?? 0);
}

/* ── Audience data ── */
$byDevice  = $hasAnalytics ? aq("SELECT COALESCE(device_type,'desktop') d, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY d ORDER BY c DESC", [$sqlFrom,$sqlTo]) : [];
$byBrowser = $hasAnalytics ? aq("SELECT COALESCE(browser,'Other') b, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY b ORDER BY c DESC LIMIT 8", [$sqlFrom,$sqlTo]) : [];
$byOS      = $hasAnalytics ? aq("SELECT COALESCE(os,'Other') o, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY o ORDER BY c DESC LIMIT 8", [$sqlFrom,$sqlTo]) : [];
$byScreen  = $hasAnalytics ? aq("SELECT CONCAT(COALESCE(screen_w,0),'x',COALESCE(screen_h,0)) res, COUNT(*) c FROM analytics_sessions WHERE screen_w IS NOT NULL AND created_at BETWEEN ? AND ? GROUP BY res ORDER BY c DESC LIMIT 8", [$sqlFrom,$sqlTo]) : [];
$newVsRet  = $hasAnalytics ? [
    (int)($kpis['new_visitors'] ?? 0),
    max(0, ($kpis['sessions'] ?? 0) - ($kpis['new_visitors'] ?? 0))
] : [0,0];
$byLang    = $hasAnalytics ? aq("SELECT COALESCE(UPPER(SUBSTRING_INDEX(language,'-',1)),'?') l, COUNT(*) c FROM analytics_sessions WHERE language IS NOT NULL AND created_at BETWEEN ? AND ? GROUP BY l ORDER BY c DESC LIMIT 8", [$sqlFrom,$sqlTo]) : [];

/* ── Geography ── */
$byCountry = $hasAnalytics ? aq("SELECT COALESCE(country_code,'--') cc, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY cc ORDER BY c DESC LIMIT 15", [$sqlFrom,$sqlTo]) : [];
$byTZ      = $hasAnalytics ? aq("SELECT timezone tz, COUNT(*) c FROM analytics_sessions WHERE timezone IS NOT NULL AND created_at BETWEEN ? AND ? GROUP BY tz ORDER BY c DESC LIMIT 15", [$sqlFrom,$sqlTo]) : [];

/* ── Traffic sources ── */
$bySrc     = $hasAnalytics ? aq("SELECT COALESCE(traffic_source,'direct') s, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY s ORDER BY c DESC", [$sqlFrom,$sqlTo]) : [];
$byRef     = $hasAnalytics ? aq("SELECT referrer r, COUNT(*) c FROM analytics_sessions WHERE referrer IS NOT NULL AND referrer != '' AND traffic_source='referral' AND created_at BETWEEN ? AND ? GROUP BY r ORDER BY c DESC LIMIT 12", [$sqlFrom,$sqlTo]) : [];
$byCamp    = $hasAnalytics ? aq("SELECT utm_campaign camp, utm_source src, utm_medium med, COUNT(*) c FROM analytics_sessions WHERE utm_campaign IS NOT NULL AND created_at BETWEEN ? AND ? GROUP BY camp,src,med ORDER BY c DESC LIMIT 12", [$sqlFrom,$sqlTo]) : [];

/* ── Behavior ── */
$topPages  = $hasAnalytics ? aq("SELECT page_url, COUNT(*) views, AVG(time_on_page) avg_time, AVG(scroll_depth) avg_scroll FROM analytics_pageviews WHERE created_at BETWEEN ? AND ? GROUP BY page_url ORDER BY views DESC LIMIT 20", [$sqlFrom,$sqlTo]) : [];
$landingPages = $hasAnalytics ? aq("SELECT entry_page url, COUNT(*) sessions, SUM(is_bounce) bounces FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY entry_page ORDER BY sessions DESC LIMIT 15", [$sqlFrom,$sqlTo]) : [];
$exitPages = $hasAnalytics ? aq("SELECT p.page_url url, COUNT(*) exits FROM analytics_pageviews p WHERE p.is_exit=1 AND p.created_at BETWEEN ? AND ? GROUP BY p.page_url ORDER BY exits DESC LIMIT 15", [$sqlFrom,$sqlTo]) : [];
$slowPages = $hasAnalytics ? aq("SELECT page_url, ROUND(AVG(time_on_page)) avg_sec, COUNT(*) views FROM analytics_pageviews WHERE time_on_page > 0 AND created_at BETWEEN ? AND ? GROUP BY page_url HAVING views >= 3 ORDER BY avg_sec DESC LIMIT 10", [$sqlFrom,$sqlTo]) : [];

/* ── Peak hours ── */
$byHour    = $hasAnalytics ? aq("SELECT HOUR(created_at) h, COUNT(*) c FROM analytics_sessions WHERE created_at BETWEEN ? AND ? GROUP BY h ORDER BY h", [$sqlFrom,$sqlTo]) : [];
$hourMap   = array_column($byHour, 'c', 'h');
$hourLabels = $hourData = [];
for ($h = 0; $h < 24; $h++) { $hourLabels[] = sprintf('%02d:00', $h); $hourData[] = (int)($hourMap[$h] ?? 0); }

/* ── Conversion analytics ── */
$ctaClicks   = $hasAnalytics ? aqv("SELECT COUNT(*) FROM analytics_events WHERE event_type='cta_click' AND created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]) : 0;
$formStarts  = $hasAnalytics ? aqv("SELECT COUNT(DISTINCT session_id) FROM analytics_events WHERE event_type='form_start' AND created_at BETWEEN ? AND ?", [$sqlFrom,$sqlTo]) : 0;
$byLeadStatus = aq("SELECT status, COUNT(*) cnt FROM leads GROUP BY status ORDER BY cnt DESC");
$lsMap = array_column($byLeadStatus, 'cnt', 'status');

/* ── Heatmap data (top 200 clicks on this range) ── */
$heatmapData = $hasAnalytics ? aq("SELECT x_pct, y_pct, COUNT(*) w FROM analytics_events WHERE event_type='click' AND created_at BETWEEN ? AND ? GROUP BY x_pct, y_pct ORDER BY w DESC LIMIT 200", [$sqlFrom,$sqlTo]) : [];

/* ── Format helpers ── */
function pct(int $a, int $b): string { return $b > 0 ? round($a/$b*100,1).'%' : '0%'; }
function fmtTime(int $sec): string {
    if ($sec < 60) return $sec.'s';
    return floor($sec/60).'m '.($sec%60).'s';
}
function growth(int $now, int $prev): array {
    if ($prev === 0) return [$now > 0 ? 100 : 0, $now > 0 ? 'up' : 'flat'];
    $g = round(($now - $prev) / $prev * 100);
    return [$g, $g > 0 ? 'up' : ($g < 0 ? 'down' : 'flat')];
}
[$sessGrowth, $sessDir]  = growth($kpis['sessions']??0, $kpis['sessions_prev']??0);
[$pvGrowth,   $pvDir]    = growth($kpis['pageviews']??0,$kpis['pv_prev']??0);
[$leadGrowth, $leadDir]  = growth($leads_period, $leads_prev);

$countryNames = [
    'US'=>'United States','IN'=>'India','GB'=>'United Kingdom','CA'=>'Canada',
    'AU'=>'Australia','DE'=>'Germany','FR'=>'France','SG'=>'Singapore',
    'AE'=>'United Arab Emirates','PK'=>'Pakistan','BD'=>'Bangladesh',
    'NL'=>'Netherlands','JP'=>'Japan','BR'=>'Brazil','MX'=>'Mexico',
    '--'=>'Unknown',
];
function countryFlag(string $cc): string {
    if (strlen($cc) !== 2 || $cc === '--') return '🌐';
    $c1 = mb_chr(0x1F1E0 + ord($cc[0]) - ord('A'));
    $c2 = mb_chr(0x1F1E0 + ord($cc[1]) - ord('A'));
    return $c1 . $c2;
}

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
/* ═══════════════════════════════════════
   ANALYTICS DASHBOARD STYLES
═══════════════════════════════════════ */
.an-wrap     { min-height: calc(100vh - 120px); }
.an-tabs     { display:flex; gap:2px; border-bottom:2px solid var(--border); margin-bottom:24px; overflow-x:auto; flex-wrap:nowrap; scrollbar-width:none; }
.an-tabs::-webkit-scrollbar { display:none; }
.an-tab      { padding:10px 16px; font-size:13px; font-weight:600; color:var(--gray); border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; white-space:nowrap; transition:.2s; display:flex; align-items:center; gap:6px; text-decoration:none; }
.an-tab:hover{ color:var(--text); }
.an-tab.active{ color:var(--blue); border-bottom-color:var(--blue); }
.an-tab .rt-dot{ width:7px; height:7px; border-radius:50%; background:#22c55e; animation:rtPulse 2s ease infinite; }
@keyframes rtPulse{ 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
.an-pane     { display:none; }
.an-pane.on  { display:block; animation:fadeUp .3s ease; }
@keyframes fadeUp{ from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }

/* KPI card */
.kc          { background:var(--white); border:1px solid var(--border); border-radius:14px; padding:20px 22px; position:relative; overflow:hidden; transition:.25s; }
.kc:hover    { box-shadow:0 6px 24px rgba(37,99,235,.08); transform:translateY(-2px); }
.kc::before  { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--kc,var(--blue)); }
.kc::after   { content:''; position:absolute; bottom:-28px; right:-28px; width:80px; height:80px; border-radius:50%; background:var(--kc,var(--blue)); opacity:.06; transition:.25s; }
.kc:hover::after { opacity:.12; transform:scale(1.15); }
.kc-lbl      { font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--gray); margin-bottom:8px; }
.kc-val      { font-size:28px; font-weight:900; color:var(--text); line-height:1; letter-spacing:-.5px; margin-bottom:7px; }
.kc-sub      { font-size:12px; font-weight:600; display:flex; align-items:center; gap:4px; }
.kc-sub.up   { color:#059669; }
.kc-sub.down { color:#e11d48; }
.kc-sub.flat { color:var(--gray); }
.kc-ico      { position:absolute; top:16px; right:16px; width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:17px; background:var(--kc,var(--blue)); opacity:.13; }

/* Grid layouts */
.g-4   { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
.g-3   { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:20px; }
.g-2   { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.g-2-1 { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:20px; }
.g-1-2 { display:grid; grid-template-columns:1fr 2fr; gap:20px; margin-bottom:20px; }
.g-3-1 { display:grid; grid-template-columns:3fr 1fr; gap:20px; margin-bottom:20px; }

/* Bar row */
.br      { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid rgba(226,232,240,.5); }
.br:last-child { border-bottom:none; }
.br-lbl  { font-size:13px; font-weight:600; color:var(--text); min-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.br-track{ flex:1; height:8px; background:var(--light2); border-radius:5px; overflow:hidden; }
.br-fill { height:100%; border-radius:5px; transition:width 1s cubic-bezier(.16,1,.3,1); }
.br-cnt  { font-size:13px; font-weight:800; color:var(--text); min-width:44px; text-align:right; }
.br-pct  { font-size:11.5px; color:var(--gray); min-width:36px; text-align:right; }

/* Page row */
.pr      { display:grid; grid-template-columns:24px 1fr 60px 60px 60px; gap:8px; align-items:center; padding:8px 0; border-bottom:1px solid rgba(226,232,240,.4); font-size:13px; }
.pr:last-child { border-bottom:none; }
.pr-num  { width:24px; height:24px; border-radius:6px; background:var(--light2); font-size:11px; font-weight:800; color:var(--gray); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pr-url  { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text); font-weight:500; }
.pr-num2 { font-weight:800; color:var(--text); text-align:right; }
.pr-sm   { color:var(--gray); text-align:right; font-size:12px; }

/* Realtime */
.rt-card { background:linear-gradient(135deg,#040a1f,#0d1b3e 50%,#1a1260); border-radius:18px; padding:32px; color:#fff; position:relative; overflow:hidden; }
.rt-num  { font-size:72px; font-weight:900; line-height:1; font-family:var(--font-main,'Figtree',sans-serif); }
.rt-sub  { font-size:15px; color:rgba(255,255,255,.6); margin-top:8px; }
.rt-badge{ display:inline-flex; align-items:center; gap:6px; background:rgba(34,197,94,.18); border:1px solid rgba(34,197,94,.35); color:#86efac; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; margin-bottom:16px; }
.rt-ministat{ background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1); border-radius:12px; padding:14px 18px; text-align:center; }
.rt-ministat .val{ font-size:22px; font-weight:900; color:#fff; }
.rt-ministat .lbl{ font-size:12px; color:rgba(255,255,255,.5); margin-top:3px; }
.rt-page-row{ display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.07); font-size:13px; }
.rt-page-row:last-child{ border-bottom:none; }

/* Heatmap */
.heatmap-wrap{ position:relative; background:#f8fafc; border-radius:12px; overflow:hidden; min-height:200px; }
.heatmap-wrap canvas{ position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }
.heatmap-legend{ display:flex; align-items:center; gap:4px; font-size:11.5px; color:var(--gray); }
.heatmap-legend .grad{ width:80px; height:10px; border-radius:4px; background:linear-gradient(90deg,rgba(0,0,255,.2),rgba(255,165,0,.6),rgba(255,0,0,.9)); }

/* No data placeholder */
.no-data{ display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 20px; color:var(--gray); text-align:center; }
.no-data i{ font-size:36px; opacity:.3; margin-bottom:12px; }
.no-data p{ font-size:13.5px; }

/* Export toolbar */
.export-bar{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

@media(max-width:1100px){ .g-4{grid-template-columns:repeat(2,1fr)} .g-3{grid-template-columns:1fr 1fr} }
@media(max-width:768px){ .g-4,.g-3,.g-2,.g-2-1,.g-1-2,.g-3-1{grid-template-columns:1fr} .kc-val{font-size:22px} }
</style>

<div class="page-header">
  <div>
    <div class="page-breadcrumb"><a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a><span class="sep">/</span><span class="current">Analytics</span></div>
    <h1 class="page-title" style="margin-top:4px">Analytics Dashboard</h1>
    <p style="font-size:13px;color:var(--gray);margin-top:3px"><?= $rangeLabel ?> · Updated <?= date('M j, Y H:i') ?></p>
  </div>
  <div class="page-header-actions">
    <!-- Range picker -->
    <div style="display:flex;gap:4px;flex-wrap:wrap">
      <?php foreach(['1'=>'Today','7'=>'7D','14'=>'14D','30'=>'30D','90'=>'90D','365'=>'1Y'] as $v=>$l): ?>
      <a href="?range=<?= $v ?>&tab=<?= $activeTab ?>" class="btn btn-sm <?= $range===$v?'btn-primary':'btn-secondary' ?>"><?= $l ?></a>
      <?php endforeach; ?>
      <button class="btn btn-sm btn-secondary" onclick="toggleCustomRange()" id="customBtn">
        <i class="fas fa-calendar-alt"></i> Custom
      </button>
    </div>
    <div class="export-bar">
      <a href="?range=<?= urlencode($range) ?>&tab=<?= $activeTab ?>&export=csv" class="btn btn-sm btn-secondary"><i class="fas fa-file-csv" style="color:#059669"></i> CSV</a>
      <button class="btn btn-sm btn-secondary" onclick="window.print()"><i class="fas fa-file-pdf" style="color:#e11d48"></i> PDF</button>
    </div>
  </div>
</div>

<!-- Custom date range picker -->
<div id="customRangeBar" style="display:none;background:var(--light);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:none">
  <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <input type="hidden" name="tab" value="<?= $activeTab ?>">
    <input type="hidden" name="range" value="custom">
    <label style="font-size:13px;font-weight:600">From</label>
    <input type="date" name="from" value="<?= $dateFrom ?>" class="form-control" style="width:160px">
    <label style="font-size:13px;font-weight:600">To</label>
    <input type="date" name="to" value="<?= $dateTo ?>" class="form-control" style="width:160px" max="<?= date('Y-m-d') ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
  </form>
</div>

<?php
/* ── CSV Export ── */
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Analytics Export', $rangeLabel, date('Y-m-d H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['SESSIONS KPIs']);
    fputcsv($out, ['Sessions','Pageviews','Unique Visitors','Bounce Rate','Avg Duration','Avg Pages/Session']);
    fputcsv($out, [$kpis['sessions']??0, $kpis['pageviews']??0, $kpis['unique']??0, ($kpis['bounce_rate']??0).'%', fmtTime($kpis['avg_duration']??0), $kpis['avg_pages']??0]);
    fputcsv($out, []);
    fputcsv($out, ['TOP PAGES']);
    fputcsv($out, ['URL','Views','Avg Time (s)','Avg Scroll %']);
    foreach ($topPages as $p) fputcsv($out, [$p['page_url'], $p['views'], round($p['avg_time']), round($p['avg_scroll'])]);
    fputcsv($out, []);
    fputcsv($out, ['TRAFFIC SOURCES']);
    fputcsv($out, ['Source','Sessions','%']);
    $totSrc = array_sum(array_column($bySrc, 'c')) ?: 1;
    foreach ($bySrc as $r) fputcsv($out, [ucfirst($r['s']), $r['c'], pct($r['c'], $totSrc)]);
    fputcsv($out, []);
    fputcsv($out, ['DEVICES']);
    fputcsv($out, ['Device','Sessions','%']);
    $totDev = array_sum(array_column($byDevice, 'c')) ?: 1;
    foreach ($byDevice as $r) fputcsv($out, [ucfirst($r['d']), $r['c'], pct($r['c'], $totDev)]);
    fputcsv($out, []);
    fputcsv($out, ['COUNTRIES']);
    fputcsv($out, ['Country Code','Country','Sessions','%']);
    $totCo = array_sum(array_column($byCountry, 'c')) ?: 1;
    foreach ($byCountry as $r) fputcsv($out, [$r['cc'], $countryNames[$r['cc']] ?? $r['cc'], $r['c'], pct($r['c'], $totCo)]);
    fclose($out);
    exit;
}
?>

<!-- ── TABS ── -->
<div class="an-tabs">
  <?php $tabDefs=[
    'overview'    => ['fa-chart-area',   'Overview'],
    'realtime'    => ['fa-satellite-dish','Real-time', true],
    'audience'    => ['fa-users',        'Audience'],
    'geography'   => ['fa-globe',        'Geography'],
    'traffic'     => ['fa-road',         'Traffic Sources'],
    'behavior'    => ['fa-route',        'Behavior'],
    'conversions' => ['fa-funnel-dollar','Conversions'],
  ];
  foreach($tabDefs as $k=>$tabDef):
    $ic   = $tabDef[0] ?? '';
    $lb   = $tabDef[1] ?? '';
    $dot  = $tabDef[2] ?? false;
  ?>
  <?php /* A real link: the server renders the right pane, so this works
           without JavaScript and each tab keeps its own URL. */ ?>
  <a class="an-tab <?= $activeTab===$k?'active':'' ?>"
     href="?range=<?= urlencode($range) ?>&tab=<?= urlencode($k) ?><?= $range==='custom' ? '&from='.urlencode($dateFrom).'&to='.urlencode($dateTo) : '' ?>"
     data-tab="<?= $k ?>"<?= $activeTab===$k ? ' aria-current="page"' : '' ?>>
    <i class="fas <?= $ic ?>"></i> <?= $lb ?>
    <?php if ($dot): ?><span class="rt-dot"></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$hasAnalytics): ?>
<div style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.2);border-radius:14px;padding:24px 28px;margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:14px">
    <i class="fas fa-info-circle" style="color:#2563eb;font-size:22px;flex-shrink:0"></i>
    <div>
      <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">Analytics Tracking Starting Up</div>
      <div style="font-size:13.5px;color:var(--gray)">The analytics tracker has been installed. Visit your website to generate data — detailed reports will appear here within a few minutes of activity.</div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     TAB: OVERVIEW
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='overview'?'on':'' ?>" id="tab-overview">

  <!-- KPI Row -->
  <div class="g-4">
    <div class="kc" style="--kc:#2563eb">
      <div class="kc-ico"><i class="fas fa-mouse-pointer"></i></div>
      <div class="kc-lbl">Sessions</div>
      <div class="kc-val"><?= number_format($kpis['sessions']??0) ?></div>
      <div class="kc-sub <?= $sessDir ?>"><i class="fas fa-arrow-<?= $sessDir==='up'?'up':($sessDir==='down'?'down':'right') ?>" style="font-size:9px"></i><?= abs($sessGrowth) ?>% vs prev period</div>
    </div>
    <div class="kc" style="--kc:#7c3aed">
      <div class="kc-ico"><i class="fas fa-file-alt"></i></div>
      <div class="kc-lbl">Page Views</div>
      <div class="kc-val"><?= number_format($kpis['pageviews']??0) ?></div>
      <div class="kc-sub <?= $pvDir ?>"><i class="fas fa-arrow-<?= $pvDir==='up'?'up':($pvDir==='down'?'down':'right') ?>" style="font-size:9px"></i><?= abs($pvGrowth) ?>% vs prev period</div>
    </div>
    <div class="kc" style="--kc:#06b6d4">
      <div class="kc-ico"><i class="fas fa-user"></i></div>
      <div class="kc-lbl">Unique Visitors</div>
      <div class="kc-val"><?= number_format($kpis['unique']??0) ?></div>
      <div class="kc-sub flat"><?= number_format($kpis['new_visitors']??0) ?> new · <?= number_format(max(0,($kpis['unique']??0)-($kpis['new_visitors']??0))) ?> returning</div>
    </div>
    <div class="kc" style="--kc:#d97706">
      <div class="kc-ico"><i class="fas fa-inbox"></i></div>
      <div class="kc-lbl">Leads</div>
      <div class="kc-val"><?= number_format($leads_period) ?></div>
      <div class="kc-sub <?= $leadDir ?>"><i class="fas fa-arrow-<?= $leadDir==='up'?'up':($leadDir==='down'?'down':'right') ?>" style="font-size:9px"></i><?= abs($leadGrowth) ?>% vs prev period</div>
    </div>
  </div>

  <!-- Secondary KPI Row -->
  <div class="g-4" style="margin-bottom:22px">
    <div class="kc" style="--kc:#059669">
      <div class="kc-ico"><i class="fas fa-percentage"></i></div>
      <div class="kc-lbl">Bounce Rate</div>
      <div class="kc-val"><?= $kpis['bounce_rate']??0 ?>%</div>
      <div class="kc-sub <?= ($kpis['bounce_rate']??100) < 50 ? 'up' : 'down' ?>"><?= ($kpis['bounce_rate']??0) < 50 ? 'Good' : 'Needs work' ?></div>
    </div>
    <div class="kc" style="--kc:#e11d48">
      <div class="kc-ico"><i class="fas fa-clock"></i></div>
      <div class="kc-lbl">Avg. Session Duration</div>
      <div class="kc-val"><?= fmtTime((int)($kpis['avg_duration']??0)) ?></div>
      <div class="kc-sub flat">Per session</div>
    </div>
    <div class="kc" style="--kc:#8b5cf6">
      <div class="kc-ico"><i class="fas fa-layer-group"></i></div>
      <div class="kc-lbl">Pages / Session</div>
      <div class="kc-val"><?= $kpis['avg_pages']??0 ?></div>
      <div class="kc-sub flat"><?= $kpis['pageviews']??0 ?> total views</div>
    </div>
    <div class="kc" style="--kc:#f59e0b">
      <div class="kc-ico"><i class="fas fa-chart-line"></i></div>
      <div class="kc-lbl">Conversion Rate</div>
      <div class="kc-val"><?= $conv_rate ?>%</div>
      <div class="kc-sub <?= $conv_rate >= 10 ? 'up' : 'flat' ?>"><?= number_format($leads_conv) ?> converted</div>
    </div>
  </div>

  <!-- Trend Chart -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <span><i class="fas fa-chart-area" style="color:var(--blue);margin-right:7px"></i>Sessions, Page Views &amp; Leads Trend</span>
      <div style="display:flex;align-items:center;gap:12px;font-size:12px;font-weight:600">
        <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:3px;background:#2563eb;border-radius:2px;display:inline-block"></span>Sessions</span>
        <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:3px;background:#7c3aed;border-radius:2px;display:inline-block"></span>Page Views</span>
        <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:3px;background:#e11d48;border-radius:2px;display:inline-block"></span>Leads</span>
      </div>
    </div>
    <div class="card-body"><canvas id="trendChart" style="max-height:240px"></canvas></div>
  </div>

  <!-- Sources + Devices quick view -->
  <div class="g-2">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-road" style="color:var(--violet);margin-right:7px"></i>Traffic Sources</span><a href="?range=<?= urlencode($range) ?>&tab=traffic" style="font-size:12px;color:var(--blue);font-weight:600">Details →</a></div>
      <div class="card-body">
        <?php
        $totSrcV = array_sum(array_column($bySrc,'c')) ?: 1;
        $srcColors=['direct'=>'#2563eb','organic'=>'#059669','social'=>'#7c3aed','referral'=>'#d97706','campaign'=>'#e11d48','internal'=>'#06b6d4'];
        foreach($bySrc as $r):
          $pct2=round($r['c']/$totSrcV*100);
          $col=$srcColors[$r['s']]??'#6b7280';
        ?>
        <div class="br">
          <div class="br-lbl" style="display:flex;align-items:center;gap:7px"><span style="width:9px;height:9px;border-radius:50%;background:<?=$col?>;flex-shrink:0"></span><?=ucfirst(e($r['s']))?></div>
          <div class="br-track"><div class="br-fill" style="width:<?=$pct2?>%;background:<?=$col?>"></div></div>
          <div class="br-cnt"><?=$r['c']?></div>
          <div class="br-pct"><?=$pct2?>%</div>
        </div>
        <?php endforeach; if(!$bySrc) echo '<div class="no-data"><i class="fas fa-road"></i><p>No traffic source data yet</p></div>'; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span><i class="fas fa-mobile-alt" style="color:var(--cyan);margin-right:7px"></i>Device Breakdown</span><a href="?range=<?= urlencode($range) ?>&tab=audience" style="font-size:12px;color:var(--blue);font-weight:600">Details →</a></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <?php
        $totDevV = array_sum(array_column($byDevice,'c')) ?: 1;
        $devColors=['desktop'=>'#2563eb','mobile'=>'#7c3aed','tablet'=>'#06b6d4'];
        $devIcons=['desktop'=>'fa-desktop','mobile'=>'fa-mobile-alt','tablet'=>'fa-tablet-alt'];
        foreach($byDevice as $r):
          $dp=round($r['c']/$totDevV*100);
          $dc=$devColors[strtolower($r['d'])]??'#6b7280';
          $di=$devIcons[strtolower($r['d'])]??'fa-question';
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid var(--border);border-radius:10px">
          <div style="width:38px;height:38px;border-radius:10px;background:<?=$dc?>18;color:<?=$dc?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0"><i class="fas <?=$di?>"></i></div>
          <div style="flex:1">
            <div style="font-size:13.5px;font-weight:700"><?=ucfirst(e($r['d']))?></div>
            <div style="height:5px;background:var(--light2);border-radius:3px;margin-top:5px;overflow:hidden"><div style="width:<?=$dp?>%;height:100%;background:<?=$dc?>;border-radius:3px"></div></div>
          </div>
          <div style="font-size:15px;font-weight:900;color:var(--text)"><?=$r['c']?></div>
          <div style="font-size:12px;color:var(--gray)"><?=$dp?>%</div>
        </div>
        <?php endforeach; if(!$byDevice) echo '<div class="no-data"><i class="fas fa-mobile-alt"></i><p>No device data yet</p></div>'; ?>
      </div>
    </div>
  </div>

  <!-- Peak Hours -->
  <div class="card">
    <div class="card-header"><span><i class="fas fa-clock" style="color:var(--amber);margin-right:7px"></i>Peak Traffic Hours</span><span style="font-size:12px;color:var(--gray)">Server time (UTC)</span></div>
    <div class="card-body"><canvas id="hourChart" style="max-height:160px"></canvas></div>
  </div>

</div>

<!-- ══════════════════════════════════════
     TAB: REAL-TIME
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='realtime'?'on':'' ?>" id="tab-realtime">

  <div class="g-1-2">
    <div class="rt-card">
      <div class="rt-badge"><span class="rt-dot"></span> LIVE</div>
      <div class="rt-num" id="rt-count"><?= $kpis['realtime']??0 ?></div>
      <div class="rt-sub">Active visitors right now</div>
      <div style="margin-top:24px;display:flex;gap:12px">
        <div class="rt-ministat"><div class="val" id="rt-5"><?= $kpis['realtime_5']??0 ?></div><div class="lbl">Last 5 min</div></div>
        <div class="rt-ministat"><div class="val" id="rt-30"><?= $kpis['realtime_30']??0 ?></div><div class="lbl">Last 30 min</div></div>
        <div class="rt-ministat"><div class="val"><?= $kpis['realtime_30']>0 ? round($kpis['realtime_5']??0 / max(1,$kpis['realtime_30']??1) * 100) : 0 ?>%</div><div class="lbl">Engagement</div></div>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span><i class="fas fa-chart-line" style="color:#22c55e;margin-right:7px"></i>Sessions Last 30 min <span style="font-size:11px;color:var(--gray)">(updates every 30s)</span></span></div>
        <div class="card-body"><canvas id="rtChart" style="max-height:130px"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header"><span><i class="fas fa-fire" style="color:#f59e0b;margin-right:7px"></i>Active Pages</span></div>
        <div class="card-body" style="padding:8px 18px" id="rt-pages">
          <?php
          $rtPages = $hasAnalytics ? aq("SELECT exit_page url, COUNT(*) c FROM analytics_sessions WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 5 MINUTE) GROUP BY exit_page ORDER BY c DESC LIMIT 8") : [];
          foreach($rtPages as $rp):
            $slug = parse_url($rp['url'], PHP_URL_PATH) ?: '/';
          ?>
          <div class="rt-page-row">
            <span style="color:rgba(255,255,255,.75);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1"><?= e(strlen($slug)>50?substr($slug,0,47).'...':$slug) ?></span>
            <span style="font-size:13px;font-weight:800;color:#22c55e;flex-shrink:0;margin-left:10px"><?= $rp['c'] ?></span>
          </div>
          <?php endforeach; if(!$rtPages) echo '<div class="no-data" style="padding:20px"><i class="fas fa-satellite-dish"></i><p>No active sessions right now</p></div>'; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Real-time activity feed -->
  <div class="card">
    <div class="card-header"><span><i class="fas fa-stream" style="color:var(--cyan);margin-right:7px"></i>Recent Activity <span style="font-size:11px;color:var(--gray)">(last 50 events)</span></span></div>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead><tr><th>Time</th><th>Type</th><th>Page</th><th>Device</th><th>Source</th><th>Location</th></tr></thead>
        <tbody id="rt-feed">
        <?php
        $recentActivity = $hasAnalytics ? aq("SELECT s.updated_at, s.device_type, s.traffic_source, s.country_code, s.exit_page, s.pages_viewed, s.duration FROM analytics_sessions s ORDER BY s.updated_at DESC LIMIT 50") : [];
        foreach($recentActivity as $ra):
        ?>
        <tr>
          <td style="font-size:12px;color:var(--gray);white-space:nowrap"><?= date('H:i:s', strtotime($ra['updated_at'])) ?></td>
          <td><span class="badge badge-<?= (int)$ra['pages_viewed']>1?'blue':'gray' ?>"><?= (int)$ra['pages_viewed']>1?'Session':'Bounce' ?></span></td>
          <td style="font-size:12.5px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e(parse_url($ra['exit_page']??'/', PHP_URL_PATH)?:'/') ?></td>
          <td><span class="badge badge-<?= $ra['device_type']==='mobile'?'violet':($ra['device_type']==='tablet'?'cyan':'blue') ?>"><?= ucfirst(e($ra['device_type'])) ?></span></td>
          <td style="font-size:12.5px;color:var(--gray)"><?= ucfirst(e($ra['traffic_source']??'direct')) ?></td>
          <td style="font-size:13px"><?= $ra['country_code'] ? countryFlag($ra['country_code']).' '.(e($countryNames[$ra['country_code']]??$ra['country_code'])) : '—' ?></td>
        </tr>
        <?php endforeach; if(!$recentActivity) echo '<tr><td colspan="6" style="text-align:center;padding:28px;color:var(--gray)">No activity yet — visit the website to start tracking</td></tr>'; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: AUDIENCE
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='audience'?'on':'' ?>" id="tab-audience">

  <div class="g-4">
    <div class="kc" style="--kc:#2563eb"><div class="kc-ico"><i class="fas fa-user-plus"></i></div><div class="kc-lbl">New Visitors</div><div class="kc-val"><?=number_format($kpis['new_visitors']??0)?></div><div class="kc-sub flat"><?=pct($kpis['new_visitors']??0,$kpis['sessions']??1)?> of sessions</div></div>
    <div class="kc" style="--kc:#7c3aed"><div class="kc-ico"><i class="fas fa-redo"></i></div><div class="kc-lbl">Returning</div><div class="kc-val"><?=number_format(max(0,($kpis['sessions']??0)-($kpis['new_visitors']??0)))?></div><div class="kc-sub flat"><?=pct(max(0,($kpis['sessions']??0)-($kpis['new_visitors']??0)),$kpis['sessions']??1)?> of sessions</div></div>
    <div class="kc" style="--kc:#059669"><div class="kc-ico"><i class="fas fa-desktop"></i></div><div class="kc-lbl">Top Device</div><div class="kc-val"><?=ucfirst($byDevice[0]['d']??'—')?></div><div class="kc-sub flat"><?=pct($byDevice[0]['c']??0,$kpis['sessions']??1)?> of sessions</div></div>
    <div class="kc" style="--kc:#f59e0b"><div class="kc-ico"><i class="fas fa-globe"></i></div><div class="kc-lbl">Top Browser</div><div class="kc-val"><?=e($byBrowser[0]['b']??'—')?></div><div class="kc-sub flat"><?=pct($byBrowser[0]['c']??0,$kpis['sessions']??1)?> of sessions</div></div>
  </div>

  <div class="g-3">
    <!-- Device donut -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-mobile-alt" style="color:var(--blue);margin-right:7px"></i>Devices</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:14px">
        <div style="height:150px;width:150px"><canvas id="devDonut"></canvas></div>
        <div style="width:100%">
          <?php foreach($byDevice as $r): $dp=round($r['c']/($totDevV??1)*100); $dc=$devColors[strtolower($r['d'])]??'#6b7280'; ?>
          <div class="br"><div class="br-lbl" style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:50%;background:<?=$dc?>;flex-shrink:0"></span><?=ucfirst(e($r['d']))?></div><div class="br-track"><div class="br-fill" style="width:<?=$dp?>%;background:<?=$dc?>"></div></div><div class="br-cnt"><?=$r['c']?></div><div class="br-pct"><?=$dp?>%</div></div>
          <?php endforeach; if(!$byDevice) echo '<div class="no-data"><i class="fas fa-mobile-alt"></i><p>No data yet</p></div>'; ?>
        </div>
      </div>
    </div>

    <!-- Browser breakdown -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-globe" style="color:var(--violet);margin-right:7px"></i>Browsers</span></div>
      <div class="card-body">
        <?php
        $totBrw=array_sum(array_column($byBrowser,'c'))?:1;
        $brwCols=['Chrome'=>'#4285F4','Firefox'=>'#FF7139','Safari'=>'#007AFF','Edge'=>'#0078D4','Opera'=>'#FF1B2D','Samsung'=>'#1428A0','UC Browser'=>'#6b7280','Yandex'=>'#FFCC00','IE'=>'#0a6cb7','Other'=>'#6b7280'];
        foreach($byBrowser as $r): $bp=round($r['c']/$totBrw*100); $bc=$brwCols[$r['b']]??'#6b7280'; ?>
        <div class="br"><div class="br-lbl"><?=e($r['b'])?></div><div class="br-track"><div class="br-fill" style="width:<?=$bp?>%;background:<?=$bc?>"></div></div><div class="br-cnt"><?=$r['c']?></div><div class="br-pct"><?=$bp?>%</div></div>
        <?php endforeach; if(!$byBrowser) echo '<div class="no-data"><i class="fas fa-globe"></i><p>No browser data yet</p></div>'; ?>
      </div>
    </div>

    <!-- OS breakdown -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-laptop" style="color:var(--cyan);margin-right:7px"></i>Operating Systems</span></div>
      <div class="card-body">
        <?php
        $totOS=array_sum(array_column($byOS,'c'))?:1;
        $osCols=['Windows 10/11'=>'#0078d4','Windows'=>'#0078d4','macOS'=>'#666','Android'=>'#3ddc84','iOS'=>'#555','iPadOS'=>'#888','Linux'=>'#f59e0b','ChromeOS'=>'#4285F4','Other'=>'#6b7280'];
        foreach($byOS as $r): $op=round($r['c']/$totOS*100); $oc=$osCols[$r['o']]??'#6b7280'; ?>
        <div class="br"><div class="br-lbl"><?=e($r['o'])?></div><div class="br-track"><div class="br-fill" style="width:<?=$op?>%;background:<?=$oc?>"></div></div><div class="br-cnt"><?=$r['c']?></div><div class="br-pct"><?=$op?>%</div></div>
        <?php endforeach; if(!$byOS) echo '<div class="no-data"><i class="fas fa-laptop"></i><p>No OS data yet</p></div>'; ?>
      </div>
    </div>
  </div>

  <!-- New vs Returning + Language + Screen Res -->
  <div class="g-3">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-user-friends" style="color:var(--emerald);margin-right:7px"></i>New vs Returning</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:12px">
        <div style="height:140px;width:140px"><canvas id="newRetDonut"></canvas></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;width:100%">
          <div style="text-align:center;padding:10px;border-radius:10px;background:rgba(37,99,235,.07)"><div style="font-size:20px;font-weight:900;color:#2563eb"><?=number_format($newVsRet[0])?></div><div style="font-size:12px;color:var(--gray)">New</div></div>
          <div style="text-align:center;padding:10px;border-radius:10px;background:rgba(124,58,237,.07)"><div style="font-size:20px;font-weight:900;color:#7c3aed"><?=number_format($newVsRet[1])?></div><div style="font-size:12px;color:var(--gray)">Returning</div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span><i class="fas fa-language" style="color:var(--amber);margin-right:7px"></i>Languages</span></div>
      <div class="card-body">
        <?php $totLng=array_sum(array_column($byLang,'c'))?:1; foreach($byLang as $r): $lp=round($r['c']/$totLng*100); ?>
        <div class="br"><div class="br-lbl"><?=e($r['l'])?></div><div class="br-track"><div class="br-fill" style="width:<?=$lp?>%;background:#d97706"></div></div><div class="br-cnt"><?=$r['c']?></div><div class="br-pct"><?=$lp?>%</div></div>
        <?php endforeach; if(!$byLang) echo '<div class="no-data"><i class="fas fa-language"></i><p>No language data yet</p></div>'; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span><i class="fas fa-tv" style="color:var(--rose);margin-right:7px"></i>Screen Resolutions</span></div>
      <div class="card-body">
        <?php $totScr=array_sum(array_column($byScreen,'c'))?:1; foreach($byScreen as $r): $sp=round($r['c']/$totScr*100); ?>
        <div class="br"><div class="br-lbl"><?=e($r['res'])?></div><div class="br-track"><div class="br-fill" style="width:<?=$sp?>%;background:#e11d48"></div></div><div class="br-cnt"><?=$r['c']?></div><div class="br-pct"><?=$sp?>%</div></div>
        <?php endforeach; if(!$byScreen) echo '<div class="no-data"><i class="fas fa-tv"></i><p>No resolution data yet</p></div>'; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: GEOGRAPHY
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='geography'?'on':'' ?>" id="tab-geography">
  <div class="g-2">
    <!-- Countries -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-flag" style="color:var(--blue);margin-right:7px"></i>Countries</span><span style="font-size:12px;color:var(--gray)"><?=count($byCountry)?> countries</span></div>
      <div class="card-body" style="padding:8px 18px">
        <?php $totCo=array_sum(array_column($byCountry,'c'))?:1;
        foreach($byCountry as $r):
          $cp=round($r['c']/$totCo*100);
          $cname=$countryNames[$r['cc']]??($r['cc']!='--'?$r['cc']:'Unknown');
        ?>
        <div class="br">
          <div class="br-lbl" style="display:flex;align-items:center;gap:7px;min-width:140px">
            <span style="font-size:16px"><?=countryFlag($r['cc'])?></span>
            <?=e($cname)?>
          </div>
          <div class="br-track"><div class="br-fill" style="width:<?=$cp?>%;background:linear-gradient(90deg,#2563eb,#06b6d4)"></div></div>
          <div class="br-cnt"><?=$r['c']?></div>
          <div class="br-pct"><?=$cp?>%</div>
        </div>
        <?php endforeach;
        if(!$byCountry) echo '<div class="no-data"><i class="fas fa-globe"></i><p>Country data available after Cloudflare CDN setup, or with enough sessions</p></div>'; ?>
      </div>
    </div>

    <!-- Timezone breakdown as proxy for cities/regions -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-map-marker-alt" style="color:var(--violet);margin-right:7px"></i>Timezones / Regions</span></div>
      <div class="card-body" style="padding:8px 18px">
        <?php $totTZ=array_sum(array_column($byTZ,'c'))?:1;
        foreach($byTZ as $r):
          $tp=round($r['c']/$totTZ*100);
          $tzLabel=str_replace(['_','/'],['  ',' / '],$r['tz']??'Unknown');
        ?>
        <div class="br">
          <div class="br-lbl"><?=e($tzLabel)?></div>
          <div class="br-track"><div class="br-fill" style="width:<?=$tp?>%;background:linear-gradient(90deg,#7c3aed,#c084fc)"></div></div>
          <div class="br-cnt"><?=$r['c']?></div>
          <div class="br-pct"><?=$tp?>%</div>
        </div>
        <?php endforeach;
        if(!$byTZ) echo '<div class="no-data"><i class="fas fa-map-marker-alt"></i><p>No timezone data yet</p></div>'; ?>
      </div>
    </div>
  </div>
  <div style="background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.15);border-radius:12px;padding:14px 18px;font-size:13px;color:var(--text)">
    <i class="fas fa-info-circle" style="color:#2563eb;margin-right:6px"></i>
    <strong>For city/state-level analytics:</strong> Enable Cloudflare on your domain. The <code>CF-IPCountry</code>, <code>CF-IPCity</code>, and <code>CF-Region</code> headers are automatically forwarded and will populate detailed geo data.
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: TRAFFIC SOURCES
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='traffic'?'on':'' ?>" id="tab-traffic">
  <!-- Source KPIs -->
  <div class="g-4">
    <?php
    $srcDefs=[['direct','Direct','fa-link','#2563eb'],['organic','Organic Search','fa-search','#059669'],['social','Social Media','fa-share-alt','#7c3aed'],['referral','Referral','fa-external-link-alt','#d97706'],['campaign','Campaigns','fa-bullhorn','#e11d48']];
    $totSrcV2=array_sum(array_column($bySrc,'c'))?:1;
    $srcMap=array_column($bySrc,'c','s');
    foreach(array_slice($srcDefs,0,4) as [$key,$label,$ico,$col]):
      $cnt=(int)($srcMap[$key]??0); $pct2=round($cnt/$totSrcV2*100);
    ?>
    <div class="kc" style="--kc:<?=$col?>">
      <div class="kc-ico"><i class="fas <?=$ico?>"></i></div>
      <div class="kc-lbl"><?=$label?></div>
      <div class="kc-val"><?=number_format($cnt)?></div>
      <div class="kc-sub flat"><?=$pct2?>% of sessions</div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="g-2">
    <!-- Source chart -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-chart-bar" style="color:var(--blue);margin-right:7px"></i>Traffic Source Breakdown</span></div>
      <div class="card-body"><canvas id="srcChart" style="max-height:220px"></canvas></div>
    </div>

    <!-- Referrers -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-link" style="color:var(--violet);margin-right:7px"></i>Top Referrers</span></div>
      <div class="card-body" style="padding:8px 18px">
        <?php $totRef=array_sum(array_column($byRef,'c'))?:1;
        foreach($byRef as $r):
          $rp=round($r['c']/$totRef*100);
          try { $rHost=(new \stdClass); $rHost->host=parse_url($r['r'],PHP_URL_HOST)?:$r['r']; } catch(\Throwable $e){ $rHost=(object)['host'=>$r['r']]; }
        ?>
        <div class="br">
          <div class="br-lbl"><?=e(mb_strtolower(mb_substr((string)$rHost->host, 0, 250)))?></div>
          <div class="br-track"><div class="br-fill" style="width:<?=$rp?>%;background:#d97706"></div></div>
          <div class="br-cnt"><?=$r['c']?></div>
          <div class="br-pct"><?=$rp?>%</div>
        </div>
        <?php endforeach;
        if(!$byRef) echo '<div class="no-data"><i class="fas fa-link"></i><p>No referral traffic yet</p></div>'; ?>
      </div>
    </div>
  </div>

  <!-- UTM Campaigns -->
  <div class="card">
    <div class="card-header"><span><i class="fas fa-bullhorn" style="color:var(--rose);margin-right:7px"></i>UTM Campaigns</span></div>
    <div style="overflow-x:auto">
      <?php if($byCamp): ?>
      <table class="data-table">
        <thead><tr><th>Campaign</th><th>Source</th><th>Medium</th><th>Sessions</th></tr></thead>
        <tbody>
          <?php foreach($byCamp as $c): ?>
          <tr>
            <td style="font-weight:600"><?=e($c['camp'])?></td>
            <td><?=e($c['src'])?></td>
            <td><?=e($c['med'])?></td>
            <td style="font-weight:800"><?=$c['c']?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="no-data"><i class="fas fa-bullhorn"></i><p>No UTM campaign data. Add <code>?utm_source=&utm_medium=&utm_campaign=</code> to your marketing links.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: BEHAVIOR
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='behavior'?'on':'' ?>" id="tab-behavior">

  <div class="g-2">
    <!-- Top Pages -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-fire" style="color:var(--rose);margin-right:7px"></i>Most Viewed Pages</span></div>
      <div class="card-body" style="padding:4px 18px">
        <div style="display:grid;grid-template-columns:24px 1fr 70px 70px 70px;gap:8px;padding:8px 0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray)">
          <span>#</span><span>Page</span><span style="text-align:right">Views</span><span style="text-align:right">Avg Time</span><span style="text-align:right">Scroll%</span>
        </div>
        <?php foreach($topPages as $i=>$p):
          $slug=parse_url($p['page_url'],PHP_URL_PATH)?:'/';
          $slug=strlen($slug)>45?substr($slug,0,42).'...':$slug;
        ?>
        <div class="pr">
          <span class="pr-num"><?=$i+1?></span>
          <a href="<?=e($p['page_url'])?>" target="_blank" class="pr-url" style="color:var(--blue)"><?=e($slug)?></a>
          <span class="pr-num2"><?=number_format($p['views'])?></span>
          <span class="pr-sm"><?=fmtTime((int)$p['avg_time'])?></span>
          <span class="pr-sm"><?=round($p['avg_scroll'])?>%</span>
        </div>
        <?php endforeach;
        if(!$topPages) echo '<div class="no-data"><i class="fas fa-file-alt"></i><p>No page data yet</p></div>'; ?>
      </div>
    </div>

    <!-- Engagement -->
    <div style="display:flex;flex-direction:column;gap:18px">
      <!-- Landing pages -->
      <div class="card">
        <div class="card-header"><span><i class="fas fa-door-open" style="color:var(--emerald);margin-right:7px"></i>Landing Pages</span><span style="font-size:12px;color:var(--gray)">Entry pages</span></div>
        <div class="card-body" style="padding:4px 18px">
          <?php foreach($landingPages as $i=>$p):
            $slug=parse_url($p['url']??'/',PHP_URL_PATH)?:'/';
            $slug=strlen($slug)>40?substr($slug,0,37).'...':$slug;
            $br=$p['sessions']>0?round($p['bounces']/$p['sessions']*100):0;
          ?>
          <div style="display:grid;grid-template-columns:20px 1fr 50px 50px;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid rgba(226,232,240,.4);font-size:13px">
            <span style="font-size:11px;font-weight:800;color:var(--gray)"><?=$i+1?></span>
            <a href="<?=e($p['url']??'/')?>" target="_blank" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?=e($slug)?></a>
            <span style="font-weight:800;text-align:right"><?=$p['sessions']?></span>
            <span style="font-size:12px;text-align:right;color:<?=$br>60?'#e11d48':'#059669'?>"><?=$br?>%</span>
          </div>
          <?php endforeach;
          if(!$landingPages) echo '<div class="no-data" style="padding:20px"><i class="fas fa-door-open"></i><p>No landing page data yet</p></div>'; ?>
        </div>
      </div>

      <!-- Exit pages -->
      <div class="card">
        <div class="card-header"><span><i class="fas fa-door-closed" style="color:var(--amber);margin-right:7px"></i>Exit Pages</span></div>
        <div class="card-body" style="padding:4px 18px">
          <?php foreach(array_slice($exitPages,0,7) as $i=>$p):
            $slug=parse_url($p['url']??'/',PHP_URL_PATH)?:'/';
            $slug=strlen($slug)>40?substr($slug,0,37).'...':$slug;
          ?>
          <div style="display:grid;grid-template-columns:20px 1fr 50px;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid rgba(226,232,240,.4);font-size:13px">
            <span style="font-size:11px;font-weight:800;color:var(--gray)"><?=$i+1?></span>
            <a href="<?=e($p['url']??'/')?>" target="_blank" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?=e($slug)?></a>
            <span style="font-weight:800;text-align:right;color:#d97706"><?=$p['exits']?></span>
          </div>
          <?php endforeach;
          if(!$exitPages) echo '<div class="no-data" style="padding:20px"><i class="fas fa-door-closed"></i><p>No exit page data yet</p></div>'; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Scroll depth distribution + Avg time per page -->
  <div class="g-2">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-arrows-alt-v" style="color:var(--cyan);margin-right:7px"></i>Scroll Depth Distribution</span></div>
      <div class="card-body">
        <?php if($hasAnalytics):
          $scrollBuckets=[
            ['0–25%', aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE scroll_depth BETWEEN 0 AND 25 AND created_at BETWEEN ? AND ?",[$sqlFrom,$sqlTo]),'#e11d48'],
            ['26–50%', aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE scroll_depth BETWEEN 26 AND 50 AND created_at BETWEEN ? AND ?",[$sqlFrom,$sqlTo]),'#d97706'],
            ['51–75%', aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE scroll_depth BETWEEN 51 AND 75 AND created_at BETWEEN ? AND ?",[$sqlFrom,$sqlTo]),'#2563eb'],
            ['76–100%',aqv("SELECT COUNT(*) FROM analytics_pageviews WHERE scroll_depth BETWEEN 76 AND 100 AND created_at BETWEEN ? AND ?",[$sqlFrom,$sqlTo]),'#059669'],
          ];
          $totScroll=array_sum(array_column($scrollBuckets,1))?:1;
          foreach($scrollBuckets as [$lbl,$cnt,$col]):
            $sp=round($cnt/$totScroll*100);
        ?>
        <div class="br">
          <div class="br-lbl"><?=$lbl?></div>
          <div class="br-track"><div class="br-fill" style="width:<?=$sp?>%;background:<?=$col?>"></div></div>
          <div class="br-cnt"><?=number_format($cnt)?></div>
          <div class="br-pct"><?=$sp?>%</div>
        </div>
        <?php endforeach; else: ?>
        <div class="no-data"><i class="fas fa-arrows-alt-v"></i><p>No scroll data yet</p></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span><i class="fas fa-hourglass-half" style="color:var(--violet);margin-right:7px"></i>Longest Time-on-Page</span></div>
      <div class="card-body" style="padding:8px 18px">
        <?php foreach($slowPages as $i=>$p):
          $slug=parse_url($p['page_url'],PHP_URL_PATH)?:'/';
          $slug=strlen($slug)>40?substr($slug,0,37).'...':$slug;
        ?>
        <div style="display:grid;grid-template-columns:1fr 60px 50px;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid rgba(226,232,240,.4);font-size:13px">
          <a href="<?=e($p['page_url'])?>" target="_blank" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?=e($slug)?></a>
          <span style="font-weight:800;color:#7c3aed;text-align:right"><?=fmtTime($p['avg_sec'])?></span>
          <span style="font-size:12px;color:var(--gray);text-align:right"><?=$p['views']?> views</span>
        </div>
        <?php endforeach;
        if(!$slowPages) echo '<div class="no-data"><i class="fas fa-hourglass-half"></i><p>No time-on-page data yet</p></div>'; ?>
      </div>
    </div>
  </div>

  <!-- Heatmap -->
  <div class="card">
    <div class="card-header">
      <span><i class="fas fa-fire-alt" style="color:var(--rose);margin-right:7px"></i>Click Heatmap</span>
      <div class="heatmap-legend"><span>Low</span><span class="grad"></span><span>High</span></div>
    </div>
    <div class="card-body">
      <div class="heatmap-wrap" id="heatmapWrap" style="height:400px;background:url('<?= SITE_URL ?>/?_preview=1') center/contain no-repeat;background-color:#f8fafc">
        <canvas id="heatmapCanvas"></canvas>
        <?php if(!$heatmapData): ?>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
          <div class="no-data"><i class="fas fa-fire-alt"></i><p>Click heatmap data will appear after visitors interact with the site</p></div>
        </div>
        <?php endif; ?>
      </div>
      <p style="font-size:12px;color:var(--gray);margin-top:10px;text-align:center"><?=count($heatmapData)?> click events recorded in this period</p>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: CONVERSIONS
══════════════════════════════════════ -->
<div class="an-pane <?= $activeTab==='conversions'?'on':'' ?>" id="tab-conversions">

  <div class="g-4">
    <div class="kc" style="--kc:#e11d48"><div class="kc-ico"><i class="fas fa-inbox"></i></div><div class="kc-lbl">Leads (Period)</div><div class="kc-val"><?=number_format($leads_period)?></div><div class="kc-sub <?=$leadDir?>"><i class="fas fa-arrow-<?=$leadDir==='up'?'up':($leadDir==='down'?'down':'right')?>" style="font-size:9px"></i><?=abs($leadGrowth)?>% vs prev</div></div>
    <div class="kc" style="--kc:#059669"><div class="kc-ico"><i class="fas fa-check-double"></i></div><div class="kc-lbl">Converted</div><div class="kc-val"><?=number_format($leads_conv)?></div><div class="kc-sub <?=$conv_rate>=15?'up':'flat' ?>"><?=$conv_rate?>% rate</div></div>
    <div class="kc" style="--kc:#2563eb"><div class="kc-ico"><i class="fas fa-mouse-pointer"></i></div><div class="kc-lbl">CTA Clicks</div><div class="kc-val"><?=number_format($ctaClicks)?></div><div class="kc-sub flat">Button engagements</div></div>
    <div class="kc" style="--kc:#d97706"><div class="kc-ico"><i class="fas fa-pen-alt"></i></div><div class="kc-lbl">Form Starts</div><div class="kc-val"><?=number_format($formStarts)?></div><div class="kc-sub flat">Unique sessions</div></div>
  </div>

  <div class="g-2">
    <!-- Funnel -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-filter" style="color:var(--blue);margin-right:7px"></i>Conversion Funnel</span></div>
      <div class="card-body">
        <?php
        $funnel=[
          ['Visitors',    $kpis['sessions']??0, '#2563eb'],
          ['CTA Clicked', $ctaClicks,           '#7c3aed'],
          ['Form Started',$formStarts,           '#d97706'],
          ['Lead Created',$leads_period,         '#e11d48'],
          ['Converted',   $leads_conv,           '#059669'],
        ];
        $fMax=max(array_column($funnel,1))?:1;
        $prevStep=$fMax;
        foreach($funnel as [$fl,$fc,$fco]):
          $fp=$fMax>0?round($fc/$fMax*100):0;
          $dropPct=$prevStep>0&&$prevStep!==$fMax?round((1-$fc/$prevStep)*100):null;
          $prevStep=$fc;
        ?>
        <div style="margin-bottom:18px">
          <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px;font-weight:600;align-items:center">
            <span><?=$fl?></span>
            <div style="display:flex;align-items:center;gap:8px">
              <?php if($dropPct!==null&&$dropPct>0): ?><span style="font-size:11px;color:#e11d48;background:rgba(225,29,72,.08);padding:2px 8px;border-radius:10px">-<?=$dropPct?>% drop</span><?php endif; ?>
              <span style="color:<?=$fco?>;font-weight:800"><?=number_format($fc)?></span>
            </div>
          </div>
          <div style="height:10px;background:var(--light2);border-radius:6px;overflow:hidden">
            <div style="height:100%;width:<?=$fp?>%;background:<?=$fco?>;border-radius:6px;transition:width 1s"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Lead status donut -->
    <div class="card">
      <div class="card-header"><span><i class="fas fa-chart-pie" style="color:var(--violet);margin-right:7px"></i>Lead Status Distribution</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:14px">
        <?php
        $stColorMap=['new'=>'#e11d48','contacted'=>'#2563eb','in_progress'=>'#d97706','converted'=>'#059669','rejected'=>'#6b7280','spam'=>'#9ca3af'];
        $stLabels=$stData=$stColors=[];
        foreach($byLeadStatus as $r){
          $stLabels[]=ucfirst(str_replace('_',' ',$r['status']));
          $stData[]=(int)$r['cnt'];
          $stColors[]=$stColorMap[$r['status']]??'#7c3aed';
        }
        if($stData): ?>
        <div style="height:150px;width:150px"><canvas id="leadDonut"></canvas></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;width:100%">
          <?php foreach($stLabels as $i=>$l): ?>
          <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600">
            <span style="width:8px;height:8px;border-radius:50%;background:<?=$stColors[$i]?>;flex-shrink:0"></span>
            <?=e($l)?> <span style="color:var(--gray);font-weight:400">(<?=$stData[$i]?>)</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: echo '<div class="no-data"><i class="fas fa-inbox"></i><p>No lead data</p></div>'; endif; ?>
      </div>
    </div>
  </div>

  <!-- Monthly conversion trend -->
  <div class="card">
    <div class="card-header"><span><i class="fas fa-chart-bar" style="color:var(--emerald);margin-right:7px"></i>Monthly Lead Trend (6 Months)</span></div>
    <div class="card-body"><canvas id="convChart" style="max-height:200px"></canvas></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     CHARTS & INTERACTIONS
═══════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Chart defaults ── */
const tt = {
  backgroundColor:'#0d1535',padding:12,cornerRadius:10,
  titleFont:{weight:'700',size:12},bodyFont:{size:13},
  borderColor:'rgba(255,255,255,.07)',borderWidth:1
};
const gridC = 'rgba(0,0,0,.04)';
const tickC = '#9ca3af';
Chart.defaults.font.family = "'Figtree',sans-serif";
Chart.defaults.font.size   = 12;

/* ── Trend chart ── */
(function(){
  const ctx = document.getElementById('trendChart');
  if (!ctx) return;
  const g1 = ctx.getContext('2d').createLinearGradient(0,0,0,180);
  g1.addColorStop(0,'rgba(37,99,235,.2)'); g1.addColorStop(1,'rgba(37,99,235,.01)');
  const g2 = ctx.getContext('2d').createLinearGradient(0,0,0,180);
  g2.addColorStop(0,'rgba(124,58,237,.14)'); g2.addColorStop(1,'rgba(124,58,237,.01)');
  const g3 = ctx.getContext('2d').createLinearGradient(0,0,0,180);
  g3.addColorStop(0,'rgba(225,29,72,.12)'); g3.addColorStop(1,'rgba(225,29,72,.01)');
  new Chart(ctx,{type:'line',data:{
    labels:<?= json_encode($trendLabels) ?>,
    datasets:[
      {label:'Sessions',  data:<?=json_encode($trendSessions)?>,borderColor:'#2563eb',backgroundColor:g1,tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2.5},
      {label:'Page Views',data:<?=json_encode($trendPV)?>,      borderColor:'#7c3aed',backgroundColor:g2,tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2.5},
      {label:'Leads',     data:<?=json_encode($trendLeads)?>,   borderColor:'#e11d48',backgroundColor:g3,tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2.5},
    ]},
    options:{responsive:true,maintainAspectRatio:true,interaction:{mode:'index',intersect:false},
      plugins:{legend:{display:false},tooltip:tt},
      scales:{x:{grid:{display:false},ticks:{color:tickC,maxTicksLimit:10}},y:{beginAtZero:true,grid:{color:gridC},ticks:{color:tickC,precision:0}}}}
  });
})();

/* ── Peak hours ── */
(function(){
  const ctx = document.getElementById('hourChart');
  if (!ctx) return;
  const vals = <?=json_encode($hourData)?>;
  const max  = Math.max(...vals) || 1;
  const bgs  = vals.map(v => {
    const pct = v / max;
    if (pct > .7) return 'rgba(225,29,72,.8)';
    if (pct > .4) return 'rgba(217,119,6,.75)';
    return 'rgba(37,99,235,.6)';
  });
  new Chart(ctx,{type:'bar',data:{labels:<?=json_encode($hourLabels)?>,datasets:[{data:vals,backgroundColor:bgs,borderRadius:5,borderSkipped:false}]},
    options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{...tt,callbacks:{label:c=>'  '+c.parsed.y+' sessions'}}},
      scales:{x:{grid:{display:false},ticks:{color:tickC,maxTicksLimit:8}},y:{beginAtZero:true,grid:{color:gridC},ticks:{color:tickC,precision:0}}}}});
})();

/* ── Device donut ── */
(function(){
  const ctx = document.getElementById('devDonut');
  if (!ctx) return;
  const devCols = {desktop:'#2563eb',mobile:'#7c3aed',tablet:'#06b6d4'};
  const labels  = <?=json_encode(array_column($byDevice,'d'))?>;
  const data    = <?=json_encode(array_column($byDevice,'c'))?>;
  const colors  = labels.map(l=>devCols[l.toLowerCase()]||'#6b7280');
  new Chart(ctx,{type:'doughnut',data:{labels:labels.map(l=>l.charAt(0).toUpperCase()+l.slice(1)),datasets:[{data,backgroundColor:colors,borderWidth:3,borderColor:'#fff',hoverOffset:5}]},
    options:{cutout:'68%',plugins:{legend:{display:false},tooltip:{...tt}}}});
})();

/* ── New vs Returning donut ── */
(function(){
  const ctx = document.getElementById('newRetDonut');
  if (!ctx) return;
  new Chart(ctx,{type:'doughnut',data:{labels:['New','Returning'],datasets:[{data:<?=json_encode($newVsRet)?>,backgroundColor:['#2563eb','#7c3aed'],borderWidth:3,borderColor:'#fff',hoverOffset:5}]},
    options:{cutout:'65%',plugins:{legend:{display:false},tooltip:{...tt}}}});
})();

/* ── Traffic source bar chart ── */
(function(){
  const ctx = document.getElementById('srcChart');
  if (!ctx) return;
  const srcCols = {direct:'#2563eb',organic:'#059669',social:'#7c3aed',referral:'#d97706',campaign:'#e11d48',internal:'#06b6d4'};
  const labels = <?=json_encode(array_column($bySrc,'s'))?>;
  const data   = <?=json_encode(array_column($bySrc,'c'))?>;
  const colors = labels.map(l=>srcCols[l]||'#6b7280');
  new Chart(ctx,{type:'bar',data:{labels:labels.map(l=>l.charAt(0).toUpperCase()+l.slice(1)),datasets:[{data,backgroundColor:colors,borderRadius:8,borderSkipped:false}]},
    options:{responsive:true,maintainAspectRatio:true,indexAxis:'y',
      plugins:{legend:{display:false},tooltip:{...tt}},
      scales:{x:{beginAtZero:true,grid:{color:gridC},ticks:{color:tickC,precision:0}},y:{grid:{display:false},ticks:{color:tickC}}}}});
})();

/* ── Lead donut ── */
(function(){
  const ctx = document.getElementById('leadDonut');
  if (!ctx) return;
  new Chart(ctx,{type:'doughnut',data:{labels:<?=json_encode($stLabels)?>,datasets:[{data:<?=json_encode($stData)?>,backgroundColor:<?=json_encode($stColors)?>,borderWidth:3,borderColor:'#fff',hoverOffset:5}]},
    options:{cutout:'68%',plugins:{legend:{display:false},tooltip:{...tt,callbacks:{label:c=>{const t=c.dataset.data.reduce((a,b)=>a+b,0);return'  '+c.parsed+' ('+Math.round(c.parsed/t*100)+'%)';}}}}},});
})();

/* ── Conversion chart ── */
(function(){
  const ctx = document.getElementById('convChart');
  if (!ctx) return;
  const labels=[],data=[];
  <?php for($i=5;$i>=0;$i--): $mo=date('Y-m',strtotime("-{$i} months")); ?>
  labels.push('<?=date('M',strtotime($mo.'-01'))?>');
  data.push(<?=(int)dbFetchValue("SELECT COUNT(*) FROM leads WHERE DATE_FORMAT(created_at,'%Y-%m')=?",[$mo])?>);
  <?php endfor; ?>
  new Chart(ctx,{type:'bar',data:{labels,datasets:[{label:'Leads',data,backgroundColor:'rgba(124,58,237,.75)',borderRadius:8,borderSkipped:false}]},
    options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{...tt}},
      scales:{x:{grid:{display:false},ticks:{color:tickC}},y:{beginAtZero:true,grid:{color:gridC},ticks:{color:tickC,precision:0}}}}});
})();

/* ── Realtime chart (rolling 30 min buckets) ── */
(function(){
  const ctx = document.getElementById('rtChart');
  if (!ctx) return;
  const labels=[],data=[];
  for(let i=29;i>=0;i--){
    const d=new Date();d.setMinutes(d.getMinutes()-i);
    labels.push(d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'));
    data.push(0);
  }
  const rtChartInst = new Chart(ctx,{type:'line',data:{labels,datasets:[{data,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.1)',tension:.4,fill:true,pointRadius:0,borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{enabled:false}},
      scales:{x:{grid:{display:false},ticks:{color:'rgba(255,255,255,.3)',maxTicksLimit:6,font:{size:10}}},y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'rgba(255,255,255,.3)',precision:0,font:{size:10}}}}}});
  window._rtChart = rtChartInst;
})();

/* ── Heatmap canvas ── */
(function(){
  const wrap   = document.getElementById('heatmapWrap');
  const canvas = document.getElementById('heatmapCanvas');
  if (!wrap || !canvas) return;
  const points = <?=json_encode(array_map(function($p){return['x'=>(int)$p['x_pct'],'y'=>(int)$p['y_pct'],'w'=>(int)$p['w']];},$heatmapData))?>;
  if (!points.length) return;

  function drawHeatmap(){
    const w=wrap.offsetWidth, h=wrap.offsetHeight;
    canvas.width=w; canvas.height=h;
    const ctx=canvas.getContext('2d');
    ctx.clearRect(0,0,w,h);
    const maxW=Math.max(...points.map(p=>p.w))||1;
    points.forEach(p=>{
      const px=Math.round(p.x/100*w), py=Math.round(p.y/100*h);
      const r=Math.max(20,Math.round(p.w/maxW*60));
      const grad=ctx.createRadialGradient(px,py,0,px,py,r);
      const alpha=Math.min(.9,p.w/maxW*.8+.1);
      grad.addColorStop(0,`rgba(255,0,0,${alpha})`);
      grad.addColorStop(.5,`rgba(255,165,0,${alpha*.5})`);
      grad.addColorStop(1,'rgba(0,0,255,0)');
      ctx.globalCompositeOperation='screen';
      ctx.fillStyle=grad;
      ctx.beginPath(); ctx.arc(px,py,r,0,Math.PI*2); ctx.fill();
    });
  }
  drawHeatmap();
  window.addEventListener('resize',drawHeatmap);
})();

/* ── Tab switching ── */
function switchTab(event, name){
  /* Callers historically passed either (event, name) or just (name). Sort
     out which we got rather than hiding every pane and showing none. */
  if (typeof event === 'string' && name === undefined) { name = event; event = null; }
  if (event && typeof event.preventDefault === 'function') event.preventDefault();
  if (!name) return;
  document.querySelectorAll('.an-pane').forEach(p=>p.classList.remove('on'));
  document.querySelectorAll('.an-tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===name));
  const pane=document.getElementById('tab-'+name);
  if(pane) pane.classList.add('on');
  try {
    history.replaceState(null,'','?range=<?= urlencode($range) ?>&tab='+name+'<?=$range==='custom'?"&from={$dateFrom}&to={$dateTo}":""?>');
  } catch (e) { /* not worth failing the tab switch over */ }
}

function toggleCustomRange(){
  const el=document.getElementById('customRangeBar');
  el.style.display=el.style.display==='none'?'flex':'none';
}

/* ── Bar fill animations ── */
document.querySelectorAll('.br-fill').forEach(b=>{
  const w=b.style.width; b.style.width='0';
  requestAnimationFrame(()=>setTimeout(()=>{b.style.width=w;},80));
});

/* ── Real-time polling (every 30s) ── */
(function(){
  function fetchRT(){
    fetch('<?=ADMIN_URL?>/api/realtime-stats.php', {credentials:'same-origin'})
      .then(r=>r.json())
      .then(d=>{
        document.getElementById('rt-count') && (document.getElementById('rt-count').textContent=d.now||0);
        document.getElementById('rt-5')     && (document.getElementById('rt-5').textContent=d.five||0);
        document.getElementById('rt-30')    && (document.getElementById('rt-30').textContent=d.thirty||0);
        /* Update rolling chart */
        if(window._rtChart && d.chart){
          const c=window._rtChart;
          c.data.datasets[0].data.shift();
          c.data.datasets[0].data.push(d.now||0);
          c.update('none');
        }
      }).catch(()=>{});
  }
  setInterval(fetchRT, 30000);
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
