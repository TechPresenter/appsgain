<?php
$adminTitle = 'Login History';
$adminPage  = 'login-history';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireAdmin();

$perPage  = 30;
$page     = max(1,(int)($_GET['page']   ?? 1));
$offset   = ($page - 1) * $perPage;
$userId   = (int)($_GET['user']         ?? 0);
$search   = sanitizeInput($_GET['search'] ?? '');
$type     = sanitizeInput($_GET['type']   ?? ''); // '' | 'failed' | 'success'
$dateFrom = sanitizeInput($_GET['from']   ?? '');
$dateTo   = sanitizeInput($_GET['to']     ?? '');

/* ── Ensure login_logs table exists ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS login_logs (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED NULL,
        email       VARCHAR(255) NOT NULL DEFAULT '',
        user_name   VARCHAR(255) NOT NULL DEFAULT '',
        role        VARCHAR(50)  NOT NULL DEFAULT '',
        ip_address  VARCHAR(45)  NOT NULL DEFAULT '',
        user_agent  TEXT,
        success     TINYINT(1)   NOT NULL DEFAULT 1,
        reason      VARCHAR(255) NOT NULL DEFAULT '',
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id  (user_id),
        INDEX idx_created  (created_at),
        INDEX idx_success  (success)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

/* ── POST: Clear logs ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'clear_logs' && Auth::isSuperAdmin()) {
        $days = (int)($_POST['days'] ?? 30);
        dbExecute("DELETE FROM login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
        logActivity('delete','security',"Cleared login logs older than {$days} days");
        setFlash('success', "Login logs older than {$days} days cleared.");
    }
    redirect(ADMIN_URL . '/pages/login-history.php');
}

/* ── Build query ── */
$where = ['1=1']; $params = [];
if ($userId)   { $where[] = "ll.user_id=?"; $params[] = $userId; }
if ($search)   { $where[] = "(ll.user_name LIKE ? OR ll.email LIKE ? OR ll.ip_address LIKE ?)"; $like = "%{$search}%"; $params = array_merge($params,[$like,$like,$like]); }
if ($type === 'failed')  { $where[] = "ll.success=0"; }
if ($type === 'success') { $where[] = "ll.success=1"; }
if ($dateFrom) { $where[] = "DATE(ll.created_at)>=?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "DATE(ll.created_at)<=?"; $params[] = $dateTo; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$total   = (int)dbFetchValue("SELECT COUNT(*) FROM login_logs ll {$whereSQL}", $params);
$logs    = dbFetchAll("SELECT ll.*, u.name AS current_name FROM login_logs ll LEFT JOIN users u ON ll.user_id=u.id {$whereSQL} ORDER BY ll.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$pagination = paginate($total, $perPage, $page, ADMIN_URL.'/pages/login-history.php');

/* ── Summary counts ── */
$totalLogins  = (int)dbFetchValue("SELECT COUNT(*) FROM login_logs");
$failedToday  = (int)dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=0 AND DATE(created_at)=CURDATE()");
$successToday = (int)dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=1 AND DATE(created_at)=CURDATE()");
$uniqueIPs    = (int)dbFetchValue("SELECT COUNT(DISTINCT ip_address) FROM login_logs WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)");

/* User filter name */
$filterUser = $userId ? dbFetchOne("SELECT name,email FROM users WHERE id=?",[$userId]) : null;

/* Parse User-Agent helper */
function parseUA(string $ua): array {
    $browser = 'Unknown'; $os = 'Unknown'; $device = 'Desktop';
    if (str_contains($ua,'Mobile') || str_contains($ua,'Android') || str_contains($ua,'iPhone')) $device = 'Mobile';
    elseif (str_contains($ua,'Tablet') || str_contains($ua,'iPad')) $device = 'Tablet';
    if (preg_match('/Chrome\/[\d.]+/i',$ua))       $browser = 'Chrome';
    elseif (preg_match('/Firefox\/[\d.]+/i',$ua))  $browser = 'Firefox';
    elseif (preg_match('/Safari\/[\d.]+/i',$ua) && !str_contains($ua,'Chrome')) $browser = 'Safari';
    elseif (preg_match('/Edge\/[\d.]+/i',$ua))     $browser = 'Edge';
    elseif (preg_match('/MSIE|Trident/i',$ua))     $browser = 'IE';
    if (preg_match('/Windows NT/i',$ua))           $os = 'Windows';
    elseif (preg_match('/Macintosh/i',$ua))        $os = 'macOS';
    elseif (preg_match('/Linux/i',$ua))            $os = 'Linux';
    elseif (preg_match('/Android/i',$ua))          $os = 'Android';
    elseif (preg_match('/iOS|iPhone|iPad/i',$ua))  $os = 'iOS';
    return compact('browser','os','device');
}

$deviceIcons = ['Desktop'=>'fa-desktop','Mobile'=>'fa-mobile-alt','Tablet'=>'fa-tablet-alt'];
$browserIcons = ['Chrome'=>'fab fa-chrome','Firefox'=>'fab fa-firefox-browser','Safari'=>'fab fa-safari','Edge'=>'fab fa-edge','IE'=>'fab fa-internet-explorer','Unknown'=>'fas fa-globe'];
?>
<?php require_once dirname(__DIR__) . '/includes/admin-head.php'; ?>

<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <?php if ($filterUser): ?>
      <a href="<?= ADMIN_URL ?>/pages/login-history.php">Login History</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span><?= e($filterUser['name']) ?></span>
      <?php else: ?>
      <span>Login History</span>
      <?php endif; ?>
    </div>
    <h1 class="page-title">Login History
      <?php if ($filterUser): ?><span style="font-size:15px;color:var(--gray);font-weight:400"> — <?= e($filterUser['name']) ?></span><?php endif; ?>
    </h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px"><?= number_format($total) ?> login record<?= $total !== 1 ? 's' : '' ?> found</p>
  </div>
  <div class="page-header-actions">
    <?php if (Auth::isSuperAdmin()): ?>
    <div class="export-btn-wrap">
      <button class="btn btn-secondary btn-sm" data-toggle-menu="clearMenu">
        <i class="fas fa-trash-alt"></i> Clear Old Logs <i class="fas fa-chevron-down" style="font-size:10px"></i>
      </button>
      <div class="export-menu" id="clearMenu">
        <?php foreach ([7,30,90,365] as $d): ?>
        <form method="POST" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="clear_logs">
          <input type="hidden" name="days" value="<?= $d ?>">
          <button type="submit" class="action-menu-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit" onclick="return confirm('Delete logs older than <?= $d ?> days?')">
            <i class="fas fa-trash" style="color:var(--rose)"></i> Older than <?= $d ?> days
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Summary Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
  <?php
  $lhStats = [
    ['Total Logins',    $totalLogins,  'fa-sign-in-alt', '#7c3aed','rgba(124,58,237,.1)','rgba(124,58,237,.07)'],
    ['Success Today',   $successToday, 'fa-check-circle','#059669','rgba(5,150,105,.1)', 'rgba(5,150,105,.07)'],
    ['Failed Today',    $failedToday,  'fa-exclamation-triangle','#e11d48','rgba(225,29,72,.1)','rgba(225,29,72,.07)'],
    ['Unique IPs (7d)', $uniqueIPs,    'fa-network-wired','#2563eb','rgba(37,99,235,.1)', 'rgba(37,99,235,.07)'],
  ];
  foreach ($lhStats as [$lbl,$val,$ic,$fg,$bg,$sc]): ?>
  <div class="stat-card" style="--sc:<?= $sc ?>">
    <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $fg ?>;box-shadow:none"><i class="fas <?= $ic ?>" style="font-size:18px"></i></div>
    <div class="stat-info">
      <div class="s-label"><?= $lbl ?></div>
      <div class="s-value" style="font-size:22px"><?= number_format($val) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Type tabs -->
<div class="status-tabs" style="margin-bottom:18px">
  <a href="?<?= http_build_query(array_merge($_GET,['type'=>'','page'=>''])) ?>" class="status-tab <?= !$type ? 'active' : '' ?>">
    <i class="fas fa-list"></i> All <span class="tab-count"><?= number_format($totalLogins) ?></span>
  </a>
  <a href="?<?= http_build_query(array_merge($_GET,['type'=>'success','page'=>''])) ?>" class="status-tab <?= $type==='success' ? 'active' : '' ?>">
    <i class="fas fa-check-circle"></i> Successful <span class="tab-count"><?= (int)dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=1") ?></span>
  </a>
  <a href="?<?= http_build_query(array_merge($_GET,['type'=>'failed','page'=>''])) ?>" class="status-tab <?= $type==='failed' ? 'active' : '' ?>">
    <i class="fas fa-times-circle"></i> Failed <span class="tab-count"><?= (int)dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=0") ?></span>
  </a>
</div>

<!-- Filter -->
<div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:18px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <?php if ($userId): ?><input type="hidden" name="user" value="<?= $userId ?>"><?php endif; ?>
    <?php if ($type):   ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <div style="flex:1;min-width:180px">
      <div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Search</div>
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="search-input" placeholder="Name, email, IP…" value="<?= e($search) ?>">
      </div>
    </div>
    <div>
      <div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">From</div>
      <input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>">
    </div>
    <div>
      <div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">To</div>
      <input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>">
    </div>
    <div style="display:flex;gap:8px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
      <a href="<?= ADMIN_URL ?>/pages/login-history.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
    </div>
  </form>
</div>

<!-- Log Table -->
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-shield-alt" style="color:var(--violet)"></i> Login Records</h3>
    <span style="font-size:13px;color:var(--gray)"><?= number_format($total) ?> records</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Status</th>
          <th>IP Address</th>
          <th>Device / Browser</th>
          <th>OS</th>
          <th>Date &amp; Time</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($logs): ?>
          <?php foreach ($logs as $log):
            $ua  = parseUA($log['user_agent'] ?? '');
            $ok  = (bool)$log['success'];
          ?>
          <tr style="<?= !$ok ? 'background:rgba(225,29,72,.025)' : '' ?>">
            <td>
              <div style="display:flex;align-items:center;gap:9px">
                <div style="width:32px;height:32px;border-radius:9px;background:<?= $ok ? 'rgba(5,150,105,.12)' : 'rgba(225,29,72,.1)' ?>;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:<?= $ok ? '#059669' : '#e11d48' ?>;flex-shrink:0">
                  <?= strtoupper(substr($log['user_name'] ?: $log['email'],0,1)) ?>
                </div>
                <div>
                  <div style="font-size:13.5px;font-weight:600"><?= e($log['user_name'] ?: 'Unknown') ?></div>
                  <div style="font-size:12px;color:var(--gray)"><?= e($log['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($ok): ?>
              <span class="badge badge-success"><i class="fas fa-check" style="font-size:9px"></i> Success</span>
              <?php else: ?>
              <span class="badge badge-danger"><i class="fas fa-times" style="font-size:9px"></i> Failed</span>
              <?php endif; ?>
            </td>
            <td>
              <code style="font-size:12.5px;background:var(--light);padding:3px 8px;border-radius:6px;font-family:monospace"><?= e($log['ip_address']) ?></code>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:7px;font-size:13px">
                <i class="fas <?= $deviceIcons[$ua['device']] ?? 'fa-desktop' ?>" style="color:var(--gray);font-size:14px"></i>
                <span><?= $ua['device'] ?></span>
                <i class="<?= $browserIcons[$ua['browser']] ?? 'fas fa-globe' ?>" style="color:var(--blue);font-size:13px"></i>
                <span><?= $ua['browser'] ?></span>
              </div>
            </td>
            <td style="font-size:13px;color:var(--gray)"><?= $ua['os'] ?></td>
            <td>
              <div style="font-size:13px;font-weight:600"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
              <div style="font-size:12px;color:var(--gray)"><?= date('g:i A', strtotime($log['created_at'])) ?></div>
            </td>
            <td style="font-size:12.5px;color:var(--gray)">
              <?= e($log['reason'] ?: ($ok ? 'Successful login' : 'Login failed')) ?>
              <?php if ($log['role']): ?>
              <div><span class="chip" style="font-size:11px"><?= ucfirst($log['role']) ?></span></div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>No login records found</h3>
                <p>Login history is recorded when employees sign in or fail to sign in.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($logs && $total > $perPage): ?>
  <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div style="font-size:13px;color:var(--gray)">
      Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?>
    </div>
    <div class="pagination"><?= $pagination ?></div>
  </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
