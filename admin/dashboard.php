<?php
$adminTitle = 'Dashboard';
$adminPage  = 'dashboard';
require_once __DIR__ . '/includes/admin-layout.php';

/* ── Core stats ── */
$totalVisitors  = (int) dbFetchValue("SELECT COUNT(*) FROM visitors");
$monthVisitors  = (int) dbFetchValue("SELECT COUNT(*) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$todayVisitors  = (int) dbFetchValue("SELECT COUNT(*) FROM visitors WHERE DATE(visited_at) = CURDATE()");
$totalLeads     = (int) dbFetchValue("SELECT COUNT(*) FROM leads");
$newLeadsCount  = (int) dbFetchValue("SELECT COUNT(*) FROM leads WHERE is_read = 0");
$monthLeads     = (int) dbFetchValue("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$lastMonthLeads = (int) dbFetchValue("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$totalBlogs     = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE status = 'published'");
$totalProjects  = (int) dbFetchValue("SELECT COUNT(*) FROM projects WHERE is_active = 1");
$totalSubs      = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
$pendingComments= (int) dbFetchValue("SELECT COUNT(*) FROM blog_comments WHERE status = 'pending'");
$totalServices  = (int) dbFetchValue("SELECT COUNT(*) FROM services WHERE is_active = 1");
$totalTeam      = (int) dbFetchValue("SELECT COUNT(*) FROM team_members WHERE is_active = 1");

/* Lead growth % */
$leadGrowthPct = $lastMonthLeads > 0 ? round((($monthLeads - $lastMonthLeads) / $lastMonthLeads) * 100) : ($monthLeads > 0 ? 100 : 0);

/* Visitor growth % */
$lastMonthVisitors = (int) dbFetchValue("SELECT COUNT(*) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND visited_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$visitorGrowthPct  = $lastMonthVisitors > 0 ? round((($monthVisitors - $lastMonthVisitors) / $lastMonthVisitors) * 100) : ($monthVisitors > 0 ? 100 : 0);

/* Lead status breakdown */
$leadByStatus = dbFetchAll("SELECT status, COUNT(*) AS cnt FROM leads GROUP BY status ORDER BY cnt DESC");
$lsMap = [];
foreach ($leadByStatus as $r) $lsMap[$r['status']] = (int)$r['cnt'];
$convertedLeads = $lsMap['converted'] ?? 0;
$conversionRate = $totalLeads > 0 ? round($convertedLeads / $totalLeads * 100) : 0;

/* Lead doughnut data */
$statusColorMap = ['new'=>'#D000A8','contacted'=>'#1D4FD8','in_progress'=>'#9A6207','converted'=>'#0E7C5A','rejected'=>'#5E6475','spam'=>'#9AA0B4'];
$lsLabels = []; $lsData = []; $lsColors = [];
foreach ($leadByStatus as $ls) {
    $lsLabels[] = ucfirst(str_replace('_', ' ', $ls['status']));
    $lsData[]   = (int)$ls['cnt'];
    $lsColors[] = $statusColorMap[$ls['status']] ?? '#6A00FF';
}

/* Visitor chart — 14 days */
$chartData = dbFetchAll("SELECT DATE(visited_at) AS day, COUNT(*) AS cnt FROM visitors WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(visited_at) ORDER BY day ASC");
$chartLabels = []; $chartValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('M j', strtotime($d));
    $found = false;
    foreach ($chartData as $row) {
        if ($row['day'] === $d) { $chartValues[] = (int)$row['cnt']; $found = true; break; }
    }
    if (!$found) $chartValues[] = 0;
}

/* Lead trend — 6 months */
$leadTrend = dbFetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') AS mo, COUNT(*) AS cnt FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mo ORDER BY mo ASC");
$ltLabels = []; $ltValues = [];
for ($i = 5; $i >= 0; $i--) {
    $mo = date('Y-m', strtotime("-{$i} months"));
    $ltLabels[] = date('M', strtotime($mo . '-01'));
    $found = false;
    foreach ($leadTrend as $r) {
        if ($r['mo'] === $mo) { $ltValues[] = (int)$r['cnt']; $found = true; break; }
    }
    if (!$found) $ltValues[] = 0;
}

/* Recent leads */
$recentLeads = dbFetchAll("SELECT id, name, email, phone, service, status, source_page, created_at, is_read FROM leads ORDER BY created_at DESC LIMIT 6");

/* Activity logs */
$activityLogs = dbFetchAll("SELECT al.action, al.module AS entity_type, al.description, al.created_at, u.name AS user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 8");

/* Recent blogs */
$recentBlogs = dbFetchAll("SELECT id, title, slug, status, views, created_at FROM blogs ORDER BY created_at DESC LIMIT 5");

/* Server */
$diskFree  = disk_free_space('/') ?: disk_free_space('C:\\') ?: 0;
$diskTotal = disk_total_space('/') ?: disk_total_space('C:\\') ?: 1;
$diskUsedPct = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100) : 0;

$admin = Auth::admin();
$hour  = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
?>
<?php require_once __DIR__ . '/includes/admin-head.php'; ?>

<div class="page-content">

  <!-- ── WELCOME BAR ── -->
  <div class="dash-welcome anim-fade-up" style="background-color:var(--ink);background-image:linear-gradient(120deg,#1A0B3D 0%,#3D0091 46%,#6A00FF 100%);border-radius:var(--r-lg);padding:28px 32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;margin-bottom:28px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:80px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(106,0,255,.22),transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-50px;right:-30px;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(245,0,114,.20),transparent 70%);pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
      <p style="color:rgba(255,255,255,.90);font-size:13px;margin-bottom:4px;font-family:var(--font-body);"><?= $greeting ?>, <?= e(explode(' ', $admin['name'] ?? 'Admin')[0]) ?> 👋</p>
      <h2 style="color:#fff;font-size:22px;font-weight:800;font-family:var(--font-display);letter-spacing:-.02em;margin:0 0 6px;">Welcome back to your dashboard</h2>
      <p style="color:rgba(255,255,255,.86);font-size:13px;margin:0;"><?= date('l, F j, Y') ?></p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative;z-index:1;">
      <?php if ($newLeadsCount > 0): ?>
      <a href="<?= ADMIN_URL ?>/pages/leads.php?filter=unread" class="btn btn-sm" style="background:rgba(225,29,72,.9);color:#fff;border:none;font-weight:700;">
        <i class="fas fa-bell"></i> <?= $newLeadsCount ?> Unread Lead<?= $newLeadsCount > 1 ? 's' : '' ?>
      </a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);">
        <i class="fas fa-external-link-alt"></i> View Website
      </a>
      <a href="<?= ADMIN_URL ?>/pages/leads.php?action=add" class="btn btn-sm btn-primary">
        <i class="fas fa-plus"></i> Add Lead
      </a>
    </div>
  </div>

  <!-- ── KPI CARDS ── -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;">

    <div class="kpi-card anim-fade-up anim-d1" style="--kpi-color:var(--chart-1);">
      <div class="kpi-icon"><i class="fas fa-eye"></i></div>
      <div class="kpi-label">Total Visitors</div>
      <div class="kpi-value"><?= number_format($totalVisitors) ?></div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span class="kpi-badge <?= $visitorGrowthPct >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $visitorGrowthPct >= 0 ? 'up' : 'down' ?>"></i>
          <?= abs($visitorGrowthPct) ?>%
        </span>
        <span style="font-size:12px;color:var(--gray);">vs last month</span>
      </div>
      <div style="margin-top:10px;font-size:12.5px;color:var(--gray);">
        <strong style="color:var(--text);"><?= number_format($monthVisitors) ?></strong> this month &nbsp;·&nbsp;
        <strong style="color:var(--text);"><?= number_format($todayVisitors) ?></strong> today
      </div>
    </div>

    <div class="kpi-card anim-fade-up anim-d2" style="--kpi-color:var(--chart-2);">
      <div class="kpi-icon"><i class="fas fa-inbox"></i></div>
      <div class="kpi-label">Total Leads</div>
      <div class="kpi-value"><?= number_format($totalLeads) ?></div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span class="kpi-badge <?= $leadGrowthPct >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $leadGrowthPct >= 0 ? 'up' : 'down' ?>"></i>
          <?= abs($leadGrowthPct) ?>%
        </span>
        <span style="font-size:12px;color:var(--gray);">vs last month</span>
      </div>
      <div style="margin-top:10px;font-size:12.5px;color:var(--gray);">
        <strong style="color:var(--text);"><?= number_format($monthLeads) ?></strong> this month
        <?php if ($newLeadsCount > 0): ?>
        &nbsp;·&nbsp; <strong style="color:#C02626;"><?= $newLeadsCount ?> unread</strong>
        <?php endif; ?>
      </div>
    </div>

    <div class="kpi-card anim-fade-up anim-d3" style="--kpi-color:var(--ok);">
      <div class="kpi-icon"><i class="fas fa-check-double"></i></div>
      <div class="kpi-label">Converted Leads</div>
      <div class="kpi-value"><?= number_format($convertedLeads) ?></div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span class="kpi-badge <?= $conversionRate >= 20 ? 'up' : 'flat' ?>">
          <?= $conversionRate ?>% rate
        </span>
        <span style="font-size:12px;color:var(--gray);">of all leads</span>
      </div>
      <div style="margin-top:10px;">
        <div style="height:5px;background:var(--light2);border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:<?= $conversionRate ?>%;background:linear-gradient(90deg,#0E7C5A,#10b981);border-radius:3px;transition:width 1s;"></div>
        </div>
      </div>
    </div>

    <div class="kpi-card anim-fade-up anim-d4" style="--kpi-color:var(--chart-3);">
      <div class="kpi-icon"><i class="fas fa-paper-plane"></i></div>
      <div class="kpi-label">Subscribers</div>
      <div class="kpi-value"><?= number_format($totalSubs) ?></div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span class="kpi-badge flat"><i class="fas fa-envelope"></i> Active</span>
      </div>
      <div style="margin-top:10px;font-size:12.5px;color:var(--gray);">
        Newsletter list &nbsp;·&nbsp;
        <?php if ($pendingComments > 0): ?>
        <a href="<?= ADMIN_URL ?>/pages/blogs.php" style="color:#9A6207;font-weight:700;"><?= $pendingComments ?> pending comment<?= $pendingComments > 1 ? 's' : '' ?></a>
        <?php else: ?>
        <span style="color:#0E7C5A;">0 pending</span>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- ── MINI METRICS ── -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
    <div class="mini-metric anim-fade-up anim-d1">
      <div class="mini-metric-icon" style="background:rgba(106,0,255,.10);color:#6A00FF;"><i class="fas fa-newspaper"></i></div>
      <div><div class="mini-metric-val"><?= $totalBlogs ?></div><div class="mini-metric-lbl">Published Blogs</div></div>
    </div>
    <div class="mini-metric anim-fade-up anim-d2">
      <div class="mini-metric-icon" style="background:rgba(5,150,105,.1);color:#0E7C5A;"><i class="fas fa-layer-group"></i></div>
      <div><div class="mini-metric-val"><?= $totalServices ?></div><div class="mini-metric-lbl">Active Services</div></div>
    </div>
    <div class="mini-metric anim-fade-up anim-d3">
      <div class="mini-metric-icon" style="background:rgba(124,58,237,.1);color:#6A00FF;"><i class="fas fa-briefcase"></i></div>
      <div><div class="mini-metric-val"><?= $totalProjects ?></div><div class="mini-metric-lbl">Portfolio Items</div></div>
    </div>
    <div class="mini-metric anim-fade-up anim-d4">
      <div class="mini-metric-icon" style="background:rgba(6,182,212,.1);color:#06b6d4;"><i class="fas fa-users"></i></div>
      <div><div class="mini-metric-val"><?= $totalTeam ?></div><div class="mini-metric-lbl">Team Members</div></div>
    </div>
    <div class="mini-metric anim-fade-up anim-d5">
      <div class="mini-metric-icon" style="background:rgba(217,119,6,.1);color:#9A6207;"><i class="fas fa-comment-dots"></i></div>
      <div><div class="mini-metric-val"><?= $pendingComments ?></div><div class="mini-metric-lbl">Pending Comments</div></div>
    </div>
  </div>

  <!-- ── CHARTS ROW ── -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:20px;">

    <!-- Visitor Traffic Chart -->
    <div class="card anim-fade-up">
      <div class="card-header">
        <span><i class="fas fa-chart-area" style="color:#6A00FF;margin-right:8px;"></i> Visitor Traffic <span style="font-size:11.5px;color:var(--gray);font-weight:500;">(Last 14 Days)</span></span>
        <span style="font-size:12px;color:var(--gray);"><?= number_format(array_sum($chartValues)) ?> total</span>
      </div>
      <div class="card-body" style="padding:16px 20px 20px;">
        <div class="chart-wrap" style="height:200px;">
          <canvas id="visitorChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Lead Pipeline -->
    <div class="card anim-fade-up anim-d2">
      <div class="card-header">
        <span><i class="fas fa-funnel-dollar" style="color:#6A00FF;margin-right:8px;"></i> Lead Pipeline</span>
        <a href="<?= ADMIN_URL ?>/pages/leads.php" style="font-size:12px;color:var(--blue);font-weight:600;">View all</a>
      </div>
      <div class="card-body" style="padding:14px 18px;">
        <?php
        $stages = [
          'new'         => ['New',        '#C02626'],
          'contacted'   => ['Contacted',  '#1D4FD8'],
          'in_progress' => ['In Progress','#9A6207'],
          'converted'   => ['Converted',  '#0E7C5A'],
          'rejected'    => ['Rejected',   '#6b7280'],
        ];
        $maxStage = max(array_values($lsMap) ?: [1]);
        foreach ($stages as $key => [$label, $color]):
          $cnt = $lsMap[$key] ?? 0;
          $pct = $maxStage > 0 ? round($cnt / $maxStage * 100) : 0;
        ?>
        <div class="pipeline-stage">
          <span class="pipeline-dot" style="background:<?= $color ?>;"></span>
          <span class="pipeline-label"><?= $label ?></span>
          <div class="pipeline-bar-wrap">
            <div class="pipeline-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
          </div>
          <span class="pipeline-count" style="color:<?= $color ?>;"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── RECENT LEADS + LEAD TREND ── -->
  <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;margin-bottom:20px;">

    <!-- Recent Leads Table -->
    <div class="card anim-fade-up">
      <div class="card-header">
        <span><i class="fas fa-inbox" style="color:#C02626;margin-right:8px;"></i> Recent Leads</span>
        <a href="<?= ADMIN_URL ?>/pages/leads.php" style="font-size:12px;color:var(--blue);font-weight:600;">View all</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Service</th>
              <th>Status</th>
              <th>Date</th>
              <th class="col-actions"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentLeads)): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--gray);">No leads yet</td></tr>
            <?php else: foreach ($recentLeads as $lead): ?>
            <tr <?= !$lead['is_read'] ? 'style="background:rgba(106,0,255,.03);"' : '' ?>>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <?php if (!$lead['is_read']): ?><span style="width:7px;height:7px;border-radius:50%;background:#C02626;display:inline-block;flex-shrink:0;"></span><?php endif; ?>
                  <div>
                    <div style="font-weight:600;font-size:13.5px;"><?= e($lead['name']) ?></div>
                    <div style="font-size:12px;color:var(--gray);"><?= e($lead['email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-size:13px;color:var(--gray);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($lead['service'] ?: '—') ?></td>
              <td>
                <span class="badge status-<?= e($lead['status']) ?>"><?= ucfirst(str_replace('_', ' ', $lead['status'])) ?></span>
              </td>
              <td style="font-size:12.5px;color:var(--gray);white-space:nowrap;"><?= date('M j', strtotime($lead['created_at'])) ?></td>
              <td style="text-align:right;">
                <a href="<?= ADMIN_URL ?>/pages/leads.php?action=view&id=<?= $lead['id'] ?>" class="btn btn-secondary btn-sm btn-icon" data-tooltip="View"><i class="fas fa-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lead Trend Chart -->
    <div class="card anim-fade-up anim-d2">
      <div class="card-header">
        <span><i class="fas fa-chart-bar" style="color:#9A6207;margin-right:8px;"></i> Lead Trend</span>
        <span style="font-size:11.5px;color:var(--gray);">6 months</span>
      </div>
      <div class="card-body" style="padding:16px 18px 20px;">
        <div class="chart-wrap" style="height:170px;">
          <canvas id="leadTrendChart"></canvas>
        </div>
        <!-- Doughnut status breakdown -->
        <?php if (!empty($lsData)): ?>
        <div style="margin-top:20px;">
          <div style="font-size:12.5px;font-weight:700;color:var(--text);margin-bottom:12px;">By Status</div>
          <div style="height:130px;display:flex;align-items:center;justify-content:center;">
            <canvas id="statusDoughnut"></canvas>
          </div>
          <div class="chart-legend" style="margin-top:10px;gap:8px;">
            <?php foreach ($lsLabels as $i => $lbl): ?>
            <span><span class="chart-legend-dot" style="background:<?= $lsColors[$i] ?>;"></span><?= $lbl ?> (<?= $lsData[$i] ?>)</span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- ── ACTIVITY + BLOGS + QUICK ACTIONS ── -->
  <div style="display:grid;grid-template-columns:1fr 1fr 280px;gap:20px;margin-bottom:20px;">

    <!-- Activity Feed -->
    <div class="card anim-fade-up">
      <div class="card-header">
        <span><i class="fas fa-history" style="color:#06b6d4;margin-right:8px;"></i> Recent Activity</span>
      </div>
      <div class="card-body" style="padding:8px 18px;">
        <?php if (empty($activityLogs)): ?>
        <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-history"></i></div><p>No activity logged yet.</p></div>
        <?php else: ?>
        <div class="activity-feed">
          <?php
          $actColors = ['create'=>['#0E7C5A','fa-plus-circle'],'update'=>['#1D4FD8','fa-edit'],'delete'=>['#C02626','fa-trash'],'login'=>['#6A00FF','fa-sign-in-alt'],'view'=>['#5E6475','fa-eye']];
          foreach ($activityLogs as $log):
            [$ac, $ai] = $actColors[$log['action'] ?? ''] ?? ['#6b7280','fa-circle'];
          ?>
          <div class="activity-item">
            <div class="activity-dot" style="background:<?= $ac ?>18;color:<?= $ac ?>;"><i class="fas <?= $ai ?>"></i></div>
            <div class="activity-body">
              <p><strong><?= e($log['user_name'] ?? 'System') ?></strong> <?= e($log['description'] ?? ucfirst($log['action']).' '.$log['entity_type']) ?></p>
              <div class="activity-time"><i class="fas fa-clock" style="margin-right:4px;"></i><?= timeAgo($log['created_at']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Blogs -->
    <div class="card anim-fade-up anim-d2">
      <div class="card-header">
        <span><i class="fas fa-newspaper" style="color:#6A00FF;margin-right:8px;"></i> Recent Posts</span>
        <a href="<?= ADMIN_URL ?>/pages/blogs.php" style="font-size:12px;color:var(--blue);font-weight:600;">Manage</a>
      </div>
      <div class="card-body" style="padding:8px 18px;">
        <?php if (empty($recentBlogs)): ?>
        <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-newspaper"></i></div><p>No blog posts yet.</p></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0;">
          <?php foreach ($recentBlogs as $blog): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(226,232,240,.5);">
            <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#0f2057,#5b3dee);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;color:rgba(255,255,255,.7);"><i class="fas fa-newspaper"></i></div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:13.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($blog['title']) ?></div>
              <div style="font-size:12px;color:var(--gray);margin-top:2px;"><?= $blog['views'] ?? 0 ?> views &nbsp;·&nbsp; <?= date('M j', strtotime($blog['created_at'])) ?></div>
            </div>
            <span class="badge <?= $blog['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= ucfirst($blog['status']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="<?= ADMIN_URL ?>/pages/blog-add.php" class="btn btn-secondary btn-sm" style="margin-top:14px;width:100%;justify-content:center;">
          <i class="fas fa-plus"></i> Write New Post
        </a>
      </div>
    </div>

    <!-- Quick Actions + System Status -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <div class="card anim-fade-up anim-d3">
        <div class="card-header"><span><i class="fas fa-bolt" style="color:#9A6207;margin-right:8px;"></i> Quick Actions</span></div>
        <div class="card-body" style="padding:10px 14px;display:flex;flex-direction:column;gap:6px;">
          <?php
          $qas = [
            [ADMIN_URL.'/pages/leads.php?action=add',  'fa-plus-circle',    '#C02626', 'Add New Lead'],
            [ADMIN_URL.'/pages/blog-add.php',          'fa-pen-alt',        '#6A00FF', 'Write Blog Post'],
            [ADMIN_URL.'/pages/services.php?action=add','fa-layer-group',   '#6A00FF', 'Add Service'],
            [ADMIN_URL.'/pages/projects.php?action=add','fa-briefcase',     '#0E7C5A', 'Add Project'],
            [ADMIN_URL.'/pages/settings.php',          'fa-cog',            '#6b7280', 'Site Settings'],
            [SITE_URL.'/',                             'fa-external-link-alt','#06b6d4','View Website'],
          ];
          foreach ($qas as [$url, $icon, $color, $label]):
          ?>
          <a href="<?= $url ?>" <?= str_starts_with($url, SITE_URL) ? 'target="_blank"' : '' ?> class="quick-action">
            <span class="quick-action-icon" style="background:<?= $color ?>18;color:<?= $color ?>;"><i class="fas <?= $icon ?>"></i></span>
            <span class="quick-action-label"><?= $label ?></span>
            <i class="fas fa-chevron-right quick-action-arrow"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- System Status -->
      <div class="card anim-fade-up anim-d4">
        <div class="card-header"><span><i class="fas fa-server" style="color:#06b6d4;margin-right:8px;"></i> System</span></div>
        <div class="card-body" style="padding:12px 16px;">
          <div class="ss-item">
            <span class="ss-label"><i class="fab fa-php" style="color:#818cf8;margin-right:6px;"></i> PHP</span>
            <span style="font-size:12.5px;color:var(--gray);font-weight:600;"><?= PHP_VERSION ?></span>
            <span style="width:8px;height:8px;border-radius:50%;background:#0E7C5A;margin-left:8px;"></span>
          </div>
          <div class="ss-item">
            <span class="ss-label"><i class="fas fa-hdd" style="color:#60a5fa;margin-right:6px;"></i> Disk Usage</span>
            <div style="flex:1;margin:0 8px;">
              <div class="ss-bar"><div class="ss-fill" style="width:<?= $diskUsedPct ?>%;background:<?= $diskUsedPct > 80 ? '#C02626' : ($diskUsedPct > 60 ? '#9A6207' : '#6A00FF') ?>;"></div></div>
            </div>
            <span class="ss-pct" style="color:<?= $diskUsedPct > 80 ? '#C02626' : 'var(--gray)' ?>"><?= $diskUsedPct ?>%</span>
          </div>
          <div class="ss-item" style="border-bottom:none;">
            <span class="ss-label"><i class="fas fa-clock" style="color:#9A6207;margin-right:6px;"></i> Server Time</span>
            <span style="font-size:12px;color:var(--gray);"><?= date('H:i') ?></span>
          </div>
        </div>
      </div>

    </div>
  </div>

</div><!-- /page-content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const css = getComputedStyle(document.documentElement);
 const tok = n => css.getPropertyValue(n).trim();
 const gridColor = tok('--chart-grid');
 const textColor = tok('--chart-axis');
 const c1 = tok('--chart-1'), c2 = tok('--chart-2');
 const surface = tok('--surface'), ink = tok('--ink');

  Chart.defaults.font.family = tok('--font-body') || "'Inter', sans-serif";
  Chart.defaults.font.size   = 12;

  /* ── Visitor Traffic ── */
  const vCtx = document.getElementById('visitorChart');
  if (vCtx) new Chart(vCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [{
        label: 'Visitors',
        data: <?= json_encode($chartValues) ?>,
        fill: true,
        backgroundColor: 'rgba(106,0,255,.08)',
        borderColor: '#6A00FF',
        borderWidth: 2.5,
        pointBackgroundColor: '#6A00FF',
        pointRadius: 3,
        pointHoverRadius: 5,
        tension: .4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#1e293b', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: 'rgba(255,255,255,.08)', borderWidth: 1, padding: 10, cornerRadius: 8 } },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor, maxTicksLimit: 7 } },
        y: { grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 }, beginAtZero: true }
      }
    }
  });

  /* ── Lead Trend ── */
  const ltCtx = document.getElementById('leadTrendChart');
  if (ltCtx) new Chart(ltCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($ltLabels) ?>,
      datasets: [{
        label: 'Leads',
        data: <?= json_encode($ltValues) ?>,
        backgroundColor: c2,
        borderColor: c2,
        borderWidth: 0,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: 'rgba(255,255,255,.08)', borderWidth: 1, padding: 10, cornerRadius: 8 } },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor } },
        y: { grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 }, beginAtZero: true }
      }
    }
  });

  /* ── Status Doughnut ── */
  const sdCtx = document.getElementById('statusDoughnut');
  if (sdCtx && <?= count($lsData) ?> > 0) new Chart(sdCtx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($lsLabels) ?>,
      datasets: [{ data: <?= json_encode($lsData) ?>, backgroundColor: <?= json_encode($lsColors) ?>, borderWidth: 2, borderColor: surface, hoverOffset: 4 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '68%',
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: 'rgba(255,255,255,.08)', borderWidth: 1, padding: 10, cornerRadius: 8 } }
    }
  });

  /* Animate pipeline bars on load */
  document.querySelectorAll('.pipeline-bar').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => { bar.style.transition = 'width .8s cubic-bezier(.16,1,.3,1)'; bar.style.width = w; }, 300);
  });

  /* Animate system status bars */
  document.querySelectorAll('.ss-fill').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => { bar.style.transition = 'width 1s cubic-bezier(.16,1,.3,1)'; bar.style.width = w; }, 500);
  });

})();
</script>

<?php require_once __DIR__ . '/includes/admin-foot.php'; ?>
