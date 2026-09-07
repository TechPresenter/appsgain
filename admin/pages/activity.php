<?php
$adminTitle = 'Activity Logs';
$adminPage  = 'activity';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireRole('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'clear_old') {
        $days    = max(7, (int)($_POST['days'] ?? 30));
        $deleted = dbExecute("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
        logActivity('delete', 'system', "Cleared activity logs older than {$days} days ({$deleted} entries)");
        setFlash('success', "{$deleted} old log entries cleared.");
        redirect(ADMIN_URL . '/pages/activity.php');
    }
}

$perPage = 40;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$userId  = (int)($_GET['user'] ?? 0);
$type    = sanitizeInput($_GET['type'] ?? '');
$action  = sanitizeInput($_GET['act'] ?? '');
$from    = sanitizeInput($_GET['from'] ?? '');
$to      = sanitizeInput($_GET['to']   ?? '');
$viewMode = sanitizeInput($_GET['mode'] ?? 'timeline');

$where  = [];
$params = [];
if ($search) { $where[] = "(al.action LIKE ? OR al.description LIKE ?)"; $like = "%{$search}%"; $params = array_merge($params, [$like, $like]); }
if ($userId)  { $where[] = "al.user_id = ?";       $params[] = $userId; }
if ($type)    { $where[] = "al.module = ?";   $params[] = $type; }
if ($action)  { $where[] = "al.action = ?";        $params[] = $action; }
if ($from)    { $where[] = "DATE(al.created_at) >= ?"; $params[] = $from; }
if ($to)      { $where[] = "DATE(al.created_at) <= ?"; $params[] = $to; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = (int) dbFetchValue("SELECT COUNT(*) FROM activity_logs al {$whereSQL}", $params);
$logs       = dbFetchAll(
    "SELECT al.*, al.module AS entity_type, u.name AS user_name, u.role AS user_role FROM activity_logs al
     LEFT JOIN users u ON al.user_id = u.id
     {$whereSQL}
     ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params
);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/activity.php?' . http_build_query(array_filter(['search'=>$search,'user'=>$userId,'act'=>$action,'type'=>$type,'from'=>$from,'to'=>$to,'mode'=>$viewMode])));
$admins     = dbFetchAll("SELECT id, name FROM users ORDER BY name ASC");

/* ── Stats ── */
$totalToday  = (int) dbFetchValue("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$totalWeek   = (int) dbFetchValue("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$totalMonth  = (int) dbFetchValue("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$uniqueUsers = (int) dbFetchValue("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

/* ── Action type counts ── */
$actionCounts = [];
$acRows = dbFetchAll("SELECT action, COUNT(*) AS cnt FROM activity_logs GROUP BY action");
foreach ($acRows as $r) $actionCounts[$r['action']] = $r['cnt'];

$entityTypes = ['page','blog','project','lead','user','service','team','testimonial','faq','settings','hero','media','system','gallery','course','newsletter'];

$actionConfig = [
    'create' => ['fa-plus-circle',   '#059669', 'bg:#d1fae5', 'badge-success'],
    'update' => ['fa-edit',          '#2563eb', 'bg:#dbeafe', 'badge-info'],
    'delete' => ['fa-trash',         '#e11d48', 'bg:#fee2e2', 'badge-danger'],
    'login'  => ['fa-sign-in-alt',   '#7c3aed', 'bg:#ede9fe', 'badge-violet'],
    'logout' => ['fa-sign-out-alt',  '#6b7280', 'bg:#f3f4f6', 'badge-secondary'],
    'view'   => ['fa-eye',           '#0891b2', 'bg:#cffafe', 'badge-secondary'],
];

$moduleIcons = [
    'blog'        => 'fa-newspaper',
    'page'        => 'fa-file-alt',
    'lead'        => 'fa-funnel-dollar',
    'user'        => 'fa-user',
    'service'     => 'fa-cogs',
    'team'        => 'fa-users',
    'testimonial' => 'fa-quote-right',
    'faq'         => 'fa-question-circle',
    'settings'    => 'fa-sliders-h',
    'hero'        => 'fa-star',
    'media'       => 'fa-photo-video',
    'gallery'     => 'fa-images',
    'system'      => 'fa-server',
    'project'     => 'fa-briefcase',
    'course'      => 'fa-graduation-cap',
    'newsletter'  => 'fa-envelope',
];

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
/* ── Timeline ── */
.timeline{position:relative;padding-left:28px}
.timeline::before{content:'';position:absolute;left:11px;top:0;bottom:0;width:2px;background:var(--border)}
.tl-date-group{margin-bottom:28px}
.tl-date-label{
  font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;
  color:var(--gray);background:var(--light);padding:4px 12px;border-radius:20px;
  display:inline-block;margin-bottom:14px;border:1px solid var(--border);
  position:sticky;top:8px;z-index:10;
}
.tl-item{display:flex;align-items:flex-start;gap:14px;margin-bottom:10px;position:relative}
.tl-dot{
  width:24px;height:24px;min-width:24px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;color:#fff;
  position:absolute;left:-28px;top:0;
  box-shadow:0 0 0 3px var(--light);
}
.tl-card{
  flex:1;background:var(--white);border:1.5px solid var(--border);border-radius:12px;
  padding:12px 16px;transition:var(--transition);cursor:default;
}
.tl-card:hover{border-color:rgba(124,58,237,.25);box-shadow:var(--shadow)}
.tl-card-header{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.tl-action-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:700}
.tl-module-tag{font-size:11px;font-weight:600;color:var(--gray);background:var(--light);padding:2px 8px;border-radius:20px;border:1px solid var(--border)}
.tl-time{font-size:11px;color:var(--gray2);margin-left:auto;white-space:nowrap}
.tl-desc{font-size:13px;color:var(--text);margin-top:6px;line-height:1.5}
.tl-meta{display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap}
.tl-user-chip{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;color:var(--gray)}
.tl-user-chip .avatar{width:18px;height:18px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;font-size:8px;color:#fff;font-weight:700}
.tl-ip{font-size:11px;color:var(--gray2);font-family:monospace}

/* ── View Mode Switcher ── */
.view-switcher{display:flex;background:var(--light);border-radius:10px;padding:3px;gap:2px}
.view-btn{padding:6px 14px;border-radius:8px;border:none;cursor:pointer;font-size:12.5px;font-weight:600;color:var(--gray);background:none;transition:.2s;display:flex;align-items:center;gap:6px}
.view-btn.active{background:var(--white);color:var(--violet);box-shadow:0 1px 4px rgba(0,0,0,.1)}

/* ── Action Filter Chips ── */
.action-chips{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.action-chip{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;transition:.2s;border:1.5px solid var(--border);background:var(--white);color:var(--gray);text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.action-chip:hover{border-color:rgba(124,58,237,.3);color:var(--violet)}
.action-chip.active{background:var(--violet);color:#fff;border-color:var(--violet)}
.action-chip .chip-count{background:rgba(255,255,255,.25);padding:1px 6px;border-radius:10px;font-size:10.5px}

/* ── Stat mini cards ── */
.activity-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
@media(max-width:768px){.activity-stats{grid-template-columns:repeat(2,1fr)}}
.act-stat{background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.act-stat-icon{width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff}
.act-stat-label{font-size:11.5px;color:var(--gray);font-weight:600;text-transform:uppercase}
.act-stat-val{font-size:20px;font-weight:800;color:var(--primary)}
</style>

<div class="page-header">
  <div><h1>Activity Logs</h1><p><?= number_format($total) ?> log entries found</p></div>
  <div style="display:flex;gap:10px;align-items:center">
    <!-- View mode switcher -->
    <div class="view-switcher">
      <a href="?<?= http_build_query(array_merge(array_filter(['search'=>$search,'user'=>$userId,'act'=>$action,'type'=>$type,'from'=>$from,'to'=>$to]), ['mode'=>'timeline'])) ?>"
         class="view-btn <?= $viewMode === 'timeline' ? 'active' : '' ?>">
        <i class="fas fa-stream"></i> Timeline
      </a>
      <a href="?<?= http_build_query(array_merge(array_filter(['search'=>$search,'user'=>$userId,'act'=>$action,'type'=>$type,'from'=>$from,'to'=>$to]), ['mode'=>'table'])) ?>"
         class="view-btn <?= $viewMode === 'table' ? 'active' : '' ?>">
        <i class="fas fa-table"></i> Table
      </a>
    </div>
    <button class="btn btn-warning btn-sm" onclick="document.getElementById('clearModal').style.display='flex'">
      <i class="fas fa-broom"></i> Clear Old Logs
    </button>
  </div>
</div>

<!-- Stats Row -->
<div class="activity-stats">
  <div class="act-stat">
    <div class="act-stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--blue))"><i class="fas fa-history"></i></div>
    <div><div class="act-stat-label">Total Logs</div><div class="act-stat-val"><?= number_format($total) ?></div></div>
  </div>
  <div class="act-stat">
    <div class="act-stat-icon" style="background:linear-gradient(135deg,var(--cyan),#0e7490)"><i class="fas fa-calendar-day"></i></div>
    <div><div class="act-stat-label">Today</div><div class="act-stat-val"><?= number_format($totalToday) ?></div></div>
  </div>
  <div class="act-stat">
    <div class="act-stat-icon" style="background:linear-gradient(135deg,var(--emerald),#047857)"><i class="fas fa-calendar-week"></i></div>
    <div><div class="act-stat-label">This Week</div><div class="act-stat-val"><?= number_format($totalWeek) ?></div></div>
  </div>
  <div class="act-stat">
    <div class="act-stat-icon" style="background:linear-gradient(135deg,var(--amber),#b45309)"><i class="fas fa-users"></i></div>
    <div><div class="act-stat-label">Active Users (7d)</div><div class="act-stat-val"><?= number_format($uniqueUsers) ?></div></div>
  </div>
</div>

<!-- Action Quick-Filter Chips -->
<div class="action-chips">
  <a href="?<?= http_build_query(array_filter(['search'=>$search,'user'=>$userId,'type'=>$type,'from'=>$from,'to'=>$to,'mode'=>$viewMode])) ?>"
     class="action-chip <?= !$action ? 'active' : '' ?>">
    <i class="fas fa-list"></i> All Actions
    <span class="chip-count"><?= number_format($total) ?></span>
  </a>
  <?php foreach ($actionConfig as $act => [$icon, $color, , $badge]): ?>
  <?php $cnt = $actionCounts[$act] ?? 0; if (!$cnt) continue; ?>
  <a href="?<?= http_build_query(array_filter(['search'=>$search,'user'=>$userId,'type'=>$type,'from'=>$from,'to'=>$to,'mode'=>$viewMode,'act'=>$act])) ?>"
     class="action-chip <?= $action === $act ? 'active' : '' ?>" style="<?= $action === $act ? "background:{$color};border-color:{$color}" : '' ?>">
    <i class="fas <?= $icon ?>"></i> <?= ucfirst($act) ?>
    <span class="chip-count"><?= number_format($cnt) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar" style="margin-bottom:20px">
  <input type="hidden" name="mode" value="<?= e($viewMode) ?>">
  <div class="search-wrap"><i class="fas fa-search"></i><input type="text" name="search" class="search-input" placeholder="Search descriptions…" value="<?= e($search) ?>"></div>
  <select name="user" class="form-control" style="width:auto">
    <option value="">All Users</option>
    <?php foreach ($admins as $u): ?><option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="type" class="form-control" style="width:auto">
    <option value="">All Modules</option>
    <?php foreach ($entityTypes as $et): ?><option value="<?= $et ?>" <?= $type === $et ? 'selected' : '' ?>><?= ucfirst($et) ?></option><?php endforeach; ?>
  </select>
  <input type="date" name="from" class="form-control" style="width:auto" value="<?= e($from) ?>" title="From date">
  <input type="date" name="to"   class="form-control" style="width:auto" value="<?= e($to) ?>"   title="To date">
  <?php if ($action): ?><input type="hidden" name="act" value="<?= e($action) ?>"><?php endif; ?>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
  <?php if ($search || $userId || $type || $action || $from || $to): ?>
  <a href="?mode=<?= $viewMode ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>
  <?php endif; ?>
</form>

<?php if (!$logs): ?>
<div class="card" style="text-align:center;padding:60px 20px">
  <i class="fas fa-history" style="font-size:40px;color:var(--gray2);opacity:.4;display:block;margin-bottom:14px"></i>
  <h3 style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:6px">No activity logs found</h3>
  <p style="font-size:13.5px;color:var(--gray)">Try adjusting your filters or date range.</p>
</div>
<?php elseif ($viewMode === 'timeline'): ?>

<!-- ────────── TIMELINE VIEW ────────── -->
<?php
$grouped = [];
foreach ($logs as $log) {
    $date = date('Y-m-d', strtotime($log['created_at']));
    $grouped[$date][] = $log;
}
?>
<div class="timeline">
<?php foreach ($grouped as $date => $dayLogs): ?>
  <div class="tl-date-group">
    <div class="tl-date-label">
      <?php
      $d = new DateTime($date);
      $today     = new DateTime('today');
      $yesterday = new DateTime('yesterday');
      if ($d == $today)     echo 'Today';
      elseif ($d == $yesterday) echo 'Yesterday';
      else echo $d->format('D, d M Y');
      echo ' <span style="font-weight:400;opacity:.7">(' . count($dayLogs) . ')</span>';
      ?>
    </div>
    <?php foreach ($dayLogs as $log):
      [$icon, $color, $bg, $badge] = $actionConfig[$log['action']] ?? ['fa-circle','#6b7280','bg:#f3f4f6','badge-secondary'];
      $bgColor = ltrim($bg, 'bg:');
      $modIcon = $moduleIcons[$log['entity_type']] ?? 'fa-circle';
    ?>
    <div class="tl-item">
      <div class="tl-dot" style="background:<?= $color ?>">
        <i class="fas <?= $icon ?>" style="font-size:9px"></i>
      </div>
      <div class="tl-card">
        <div class="tl-card-header">
          <span class="tl-action-badge" style="background:<?= $bgColor ?>;color:<?= $color ?>">
            <i class="fas <?= $icon ?>"></i> <?= ucfirst($log['action']) ?>
          </span>
          <?php if ($log['entity_type']): ?>
          <span class="tl-module-tag"><i class="fas <?= $modIcon ?>" style="font-size:10px"></i> <?= ucfirst($log['entity_type']) ?></span>
          <?php endif; ?>
          <span class="tl-time" title="<?= e($log['created_at']) ?>"><?= date('h:i A', strtotime($log['created_at'])) ?></span>
        </div>
        <div class="tl-desc"><?= e($log['description'] ?? '—') ?></div>
        <div class="tl-meta">
          <?php if ($log['user_name']): ?>
          <span class="tl-user-chip">
            <span class="avatar"><?= strtoupper(substr($log['user_name'], 0, 1)) ?></span>
            <?= e($log['user_name']) ?>
            <?php if ($log['user_role']): ?>
            <span style="font-weight:400;color:var(--gray2)">(<?= $log['user_role'] ?>)</span>
            <?php endif; ?>
          </span>
          <?php endif; ?>
          <?php if (!empty($log['ip_address'])): ?>
          <span class="tl-ip"><i class="fas fa-network-wired" style="font-size:10px"></i> <?= e($log['ip_address']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>

<?php else: ?>

<!-- ────────── TABLE VIEW ────────── -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Action</th>
          <th>User</th>
          <th>Module</th>
          <th>Description</th>
          <th>IP</th>
          <th>Time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log):
          [$icon, $color, $bg, $badge] = $actionConfig[$log['action']] ?? ['fa-circle','#6b7280','bg:#f3f4f6','badge-secondary'];
          $modIcon = $moduleIcons[$log['entity_type']] ?? 'fa-circle';
        ?>
        <tr>
          <td>
            <span class="badge <?= $badge ?>"><i class="fas <?= $icon ?>"></i> <?= ucfirst($log['action']) ?></span>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700;flex-shrink:0">
                <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
              </div>
              <span style="font-size:13px;font-weight:600"><?= e($log['user_name'] ?? 'System') ?></span>
            </div>
          </td>
          <td>
            <?php if ($log['entity_type']): ?>
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--gray);background:var(--light);padding:3px 9px;border-radius:20px">
              <i class="fas <?= $modIcon ?>" style="font-size:10px"></i> <?= ucfirst($log['entity_type']) ?>
            </span>
            <?php else: ?><span style="color:var(--gray2)">—</span><?php endif; ?>
          </td>
          <td style="font-size:13px;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($log['description'] ?? '') ?>">
            <?= e(truncate($log['description'] ?? '', 70)) ?>
          </td>
          <td style="font-size:12px;color:var(--gray);font-family:monospace"><?= e($log['ip_address'] ?? '—') ?></td>
          <td style="font-size:12px;color:var(--gray);white-space:nowrap" title="<?= e($log['created_at']) ?>"><?= timeAgo($log['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagination): ?>
  <div class="card-footer" style="display:flex;justify-content:flex-end"><div class="pagination"><?= $pagination ?></div></div>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php if ($viewMode === 'timeline' && $pagination): ?>
<div style="margin-top:24px;display:flex;justify-content:center"><div class="pagination"><?= $pagination ?></div></div>
<?php endif; ?>

<!-- ── Clear Modal ── -->
<div id="clearModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center">
  <div style="background:var(--white);border-radius:20px;width:440px;max-width:95vw;padding:28px;box-shadow:var(--shadow-lg)">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
      <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--amber),#b45309);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px">
        <i class="fas fa-broom"></i>
      </div>
      <div>
        <h3 style="font-size:16px;font-weight:700;color:var(--primary)">Clear Old Logs</h3>
        <p style="font-size:12.5px;color:var(--gray)">This action cannot be undone.</p>
      </div>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="clear_old">
      <div class="form-group">
        <label>Delete logs older than:</label>
        <select name="days" class="form-control">
          <option value="7">7 days (keep last week)</option>
          <option value="30" selected>30 days (keep last month)</option>
          <option value="60">60 days</option>
          <option value="90">90 days (keep 3 months)</option>
          <option value="180">180 days (keep 6 months)</option>
          <option value="365">365 days (keep last year)</option>
        </select>
        <div class="form-hint">Current total: <?= number_format($total) ?> entries</div>
      </div>
      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-warning"><i class="fas fa-broom"></i> Clear Logs</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('clearModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
