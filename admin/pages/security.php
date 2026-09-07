<?php
$adminPage  = 'security';
$adminTitle = 'Security Center';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireRole('superadmin', 'admin');

$tab = sanitizeInput($_GET['tab'] ?? 'overview');
if (!in_array($tab, ['overview','login-attempts','ip-blacklist','2fa'])) $tab = 'overview';

/* Auto-create security tables */
try {
    dbExecute("CREATE TABLE IF NOT EXISTS ip_blacklist (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        reason     VARCHAR(255) DEFAULT NULL,
        blocked_by INT DEFAULT NULL,
        expires_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = sanitizeInput($_POST['action'] ?? '');

    if ($act === 'block_ip') {
        $ip      = sanitizeInput($_POST['ip_address'] ?? '');
        $reason  = sanitizeInput($_POST['reason'] ?? '');
        $expires = !empty($_POST['expires_at']) ? sanitizeInput($_POST['expires_at']) : null;

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $exists = dbFetchValue("SELECT id FROM ip_blacklist WHERE ip_address=?", [$ip]);
            if (!$exists) {
                dbInsertRow('ip_blacklist', ['ip_address'=>$ip,'reason'=>$reason,'blocked_by'=>$admin['id'],'expires_at'=>$expires]);
                logActivity('create', 'security', 'Blocked IP: '.$ip);
                setFlash('success', "IP $ip has been blocked.");
            } else {
                setFlash('warning', "IP $ip is already blocked.");
            }
        } else {
            setFlash('error', 'Invalid IP address.');
        }
        redirect(ADMIN_URL . '/pages/security.php?tab=ip-blacklist');
    }

    if ($act === 'unblock_ip') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $row = dbFetchOne("SELECT ip_address FROM ip_blacklist WHERE id=?", [$id]);
            dbExecute("DELETE FROM ip_blacklist WHERE id=?", [$id]);
            logActivity('delete', 'security', 'Unblocked IP: '.($row['ip_address']??''));
            setFlash('success', 'IP unblocked.');
        }
        redirect(ADMIN_URL . '/pages/security.php?tab=ip-blacklist');
    }

    if ($act === 'clear_expired') {
        dbExecute("DELETE FROM ip_blacklist WHERE expires_at IS NOT NULL AND expires_at < NOW()");
        setFlash('success', 'Expired blocks cleared.');
        redirect(ADMIN_URL . '/pages/security.php?tab=ip-blacklist');
    }
}

/* ── Auto-create login_logs table if needed ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS `login_logs` (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL, email VARCHAR(255) NOT NULL DEFAULT '',
        user_name VARCHAR(255) NOT NULL DEFAULT '', role VARCHAR(50) NOT NULL DEFAULT '',
        ip_address VARCHAR(45) NOT NULL DEFAULT '', user_agent TEXT,
        success TINYINT(1) NOT NULL DEFAULT 1, reason VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_u(user_id), INDEX idx_c(created_at), INDEX idx_s(success)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── Security stats (uses login_logs table, success=1/0 not status string) ── */
$loginHistory = [];
try {
    $loginHistory = dbFetchAll(
        "SELECT *, IF(success=1,'success','failed') AS status FROM login_logs ORDER BY created_at DESC LIMIT 10"
    );
} catch (Exception $e) {}

$failedLogins  = 0; $successLogins = 0; $uniqueIPs = 0;
try { $failedLogins  = (int) dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"); } catch (Exception $e) {}
try { $successLogins = (int) dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=1 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"); } catch (Exception $e) {}
try { $uniqueIPs     = (int) dbFetchValue("SELECT COUNT(DISTINCT ip_address) FROM login_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"); } catch (Exception $e) {}
$blockedIPs    = 0; $totalAdmins = 0;
try { $blockedIPs = (int) dbFetchValue("SELECT COUNT(*) FROM ip_blacklist WHERE (expires_at IS NULL OR expires_at > NOW())"); } catch (Exception $e) {}
try { $totalAdmins = (int) dbFetchValue("SELECT COUNT(*) FROM users WHERE is_active=1 AND role IN ('superadmin','admin','editor')"); } catch (Exception $e) {}

/* Login attempts data for chart (last 7 days) */
$chartDays = []; $chartFail = []; $chartSucc = [];
for ($d = 6; $d >= 0; $d--) {
    $day = date('Y-m-d', strtotime("-{$d} days"));
    $chartDays[] = date('M d', strtotime($day));
    $cf = 0; $cs = 0;
    try { $cf = (int) dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=0 AND DATE(created_at)=?", [$day]); } catch (Exception $e) {}
    try { $cs = (int) dbFetchValue("SELECT COUNT(*) FROM login_logs WHERE success=1 AND DATE(created_at)=?", [$day]); } catch (Exception $e) {}
    $chartFail[] = $cf; $chartSucc[] = $cs;
}

/* Recent failed attempts */
$recentFailed = [];
try {
    $recentFailed = dbFetchAll(
        "SELECT *, IF(success=1,'success','failed') AS status,
         (SELECT COUNT(*) FROM login_logs l2 WHERE l2.ip_address=lh.ip_address AND l2.success=0 AND l2.created_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)) AS hour_count
         FROM login_logs lh WHERE success=0 ORDER BY created_at DESC LIMIT 20"
    );
} catch (Exception $e) {}

/* IP blacklist */
$blacklist = dbFetchAll("SELECT *, (expires_at IS NOT NULL AND expires_at < NOW()) AS is_expired FROM ip_blacklist ORDER BY created_at DESC");

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.sec-tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--border);padding-bottom:0}
.sec-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;font-size:13.5px;font-weight:600;color:var(--gray);border-radius:10px 10px 0 0;cursor:pointer;text-decoration:none;transition:var(--transition);margin-bottom:-2px;border:2px solid transparent;border-bottom:none}
.sec-tab:hover{color:var(--violet);background:var(--violet-lt)}
.sec-tab.active{color:var(--violet);background:var(--white);border-color:var(--border);border-bottom-color:var(--white)}
.threat-level{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}
.threat-low{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.2)}
.threat-medium{background:rgba(217,119,6,.1);color:#d97706;border:1px solid rgba(217,119,6,.2)}
.threat-high{background:rgba(225,29,72,.1);color:#e11d48;border:1px solid rgba(225,29,72,.2)}
</style>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-shield-alt" style="color:var(--violet)"></i> Security Center</h1>
    <p class="page-subtitle">Monitor login activity, manage IP blacklist, and configure security settings.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= ADMIN_URL ?>/pages/activity.php" class="btn btn-secondary"><i class="fas fa-history"></i> Activity Logs</a>
    <a href="<?= ADMIN_URL ?>/pages/login-history.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Login History</a>
  </div>
</div>

<!-- Tabs -->
<div class="sec-tabs">
  <a href="?tab=overview" class="sec-tab <?= $tab==='overview'?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> Overview</a>
  <a href="?tab=login-attempts" class="sec-tab <?= $tab==='login-attempts'?'active':'' ?>"><i class="fas fa-lock"></i> Login Attempts</a>
  <a href="?tab=ip-blacklist" class="sec-tab <?= $tab==='ip-blacklist'?'active':'' ?>"><i class="fas fa-ban"></i> IP Blacklist <?php if ($blockedIPs > 0): ?><span style="background:var(--rose);color:#fff;font-size:10px;padding:1px 7px;border-radius:20px"><?= $blockedIPs ?></span><?php endif; ?></a>
  <a href="?tab=2fa" class="sec-tab <?= $tab==='2fa'?'active':'' ?>"><i class="fas fa-mobile-alt"></i> 2FA Settings</a>
</div>

<!-- ── OVERVIEW ── -->
<?php if ($tab === 'overview'): ?>
<div class="stats-grid">
  <?php $sstats=[['fa-shield-alt','#059669','Active Admins','rgba(5,150,105,.1)',$totalAdmins,'Logged-in accounts'],['fa-check-circle','#2563eb','Successful Logins','rgba(37,99,235,.1)',$successLogins,'Last 24 hours'],['fa-exclamation-triangle','#e11d48','Failed Attempts','rgba(225,29,72,.1)',$failedLogins,'Last 24 hours'],['fa-ban','#d97706','Blocked IPs','rgba(217,119,6,.1)',$blockedIPs,'Currently blocked']];
  foreach($sstats as [$ic,$cl,$lb,$bg,$vl,$ch]): ?>
  <div class="stat-card" style="--stat-color:<?= $bg ?>;--stat-shadow:<?= $cl ?>44">
    <div class="stat-icon" style="background:<?= $cl ?>"><i class="fas <?= $ic ?>"></i></div>
    <div class="stat-info"><div class="label"><?= $lb ?></div><div class="value"><?= $vl ?></div><div class="change neutral"><?= $ch ?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Login Activity Chart -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><h3><i class="fas fa-chart-line" style="color:var(--violet)"></i> Login Activity — Last 7 Days</h3></div>
  <div class="card-body">
    <canvas id="loginChart" height="80"></canvas>
  </div>
</div>

<!-- Security checklist -->
<div class="card">
  <div class="card-header"><h3><i class="fas fa-clipboard-check" style="color:var(--violet)"></i> Security Checklist</h3></div>
  <div class="card-body">
    <?php $checks=[['https' === (($_SERVER['HTTPS']??'')?'https':'http'),'fa-lock','HTTPS Enabled','All traffic encrypted via SSL certificate'],[$totalAdmins <= 3,'fa-user-shield','Limited Admin Accounts','Fewer admins = smaller attack surface'],[$failedLogins < 10,'fa-shield-alt','Low Failed Login Rate','Less than 10 failed attempts in last 24 hours'],[$blockedIPs >= 0,'fa-ban','IP Blacklist Active','Security blacklist is operational'],[(PHP_MAJOR_VERSION >= 8),'fa-code','PHP 8+ Runtime','Modern PHP version with security fixes'],];
    foreach($checks as [$pass,$ic,$title,$desc]): ?>
    <div style="display:flex;align-items:center;gap:14px;padding:13px 0;border-bottom:1px solid var(--border2)">
      <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:<?= $pass?'rgba(5,150,105,.1)':'rgba(225,29,72,.08)' ?>;color:<?= $pass?'#059669':'#e11d48' ?>;font-size:16px">
        <i class="fas <?= $pass?'fa-check':'fa-times' ?>"></i>
      </div>
      <div style="flex:1">
        <div style="font-size:14px;font-weight:700;color:var(--primary)"><?= $title ?></div>
        <div style="font-size:12.5px;color:var(--gray);margin-top:2px"><?= $desc ?></div>
      </div>
      <span class="<?= $pass?'badge-success':'badge-danger' ?> badge"><?= $pass?'Pass':'Review' ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── LOGIN ATTEMPTS ── -->
<?php elseif ($tab === 'login-attempts'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-lock" style="color:var(--rose)"></i> Recent Failed Login Attempts</h3>
    <span class="badge badge-danger"><?= $failedLogins ?> in last 24h</span>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($recentFailed)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" style="color:var(--emerald)"><i class="fas fa-check-shield"></i></div>
      <h3 style="color:var(--emerald)">All Clear!</h3>
      <p>No failed login attempts recorded recently.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>IP Address</th><th>Username Attempted</th><th>Attempts (1h)</th><th>User Agent</th><th>Time</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($recentFailed as $lh):
            $threatLevel = (int)$lh['hour_count'] >= 10 ? 'threat-high' : ((int)$lh['hour_count'] >= 5 ? 'threat-medium' : 'threat-low');
          ?>
          <tr>
            <td><code style="background:var(--light);padding:3px 8px;border-radius:6px;font-size:13px"><?= e($lh['ip_address'] ?? '—') ?></code></td>
            <td style="font-size:13px"><?= e($lh['email'] ?? $lh['username'] ?? '—') ?></td>
            <td><span class="threat-level <?= $threatLevel ?>"><?= $lh['hour_count'] ?> attempts</span></td>
            <td style="font-size:11px;color:var(--gray);max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= e(substr($lh['user_agent'] ?? '—', 0, 80)) ?></td>
            <td style="font-size:12px;color:var(--gray)"><?= $lh['created_at'] ? date('M d H:i', strtotime($lh['created_at'])) : '—' ?></td>
            <td>
              <?php if (!empty($lh['ip_address'])): ?>
              <form method="POST" action="?tab=ip-blacklist" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="block_ip">
                <input type="hidden" name="ip_address" value="<?= e($lh['ip_address']) ?>">
                <input type="hidden" name="reason" value="Multiple failed login attempts">
                <button class="btn btn-sm btn-danger" title="Block IP"><i class="fas fa-ban"></i> Block</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<div style="margin-top:16px">
  <a href="<?= ADMIN_URL ?>/pages/login-history.php?type=failed" class="btn btn-secondary"><i class="fas fa-history"></i> View Full Login History</a>
</div>

<!-- ── IP BLACKLIST ── -->
<?php elseif ($tab === 'ip-blacklist'): ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
  <div class="card">
    <div class="card-header">
      <h3><i class="fas fa-ban" style="color:var(--rose)"></i> Blocked IP Addresses</h3>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="clear_expired"><?= csrfField() ?><button class="btn btn-sm btn-secondary"><i class="fas fa-broom"></i> Clear Expired</button></form>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($blacklist)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-shield-alt"></i></div>
        <h3>No Blocked IPs</h3>
        <p>Add IP addresses to block access to the admin panel.</p>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>IP Address</th><th>Reason</th><th>Expires</th><th>Blocked By</th><th>Created</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($blacklist as $bl): $expired = !empty($bl['is_expired']); ?>
            <tr style="<?= $expired?'opacity:.55':'' ?>">
              <td><code style="background:<?= $expired?'var(--light)':'rgba(225,29,72,.07)' ?>;color:<?= $expired?'var(--gray)':'var(--rose)' ?>;padding:3px 8px;border-radius:6px;font-size:13px"><?= e($bl['ip_address']) ?></code></td>
              <td style="font-size:13px;color:var(--text)"><?= e($bl['reason'] ?: '—') ?></td>
              <td style="font-size:12.5px;color:var(--gray)"><?= $bl['expires_at'] ? date('M d, Y', strtotime($bl['expires_at'])).($expired?' <span style="color:var(--rose);font-size:10px">(expired)</span>':'') : 'Permanent' ?></td>
              <td style="font-size:12.5px;color:var(--gray)"><?= $bl['blocked_by'] ? 'Admin #'.$bl['blocked_by'] : 'System' ?></td>
              <td style="font-size:12px;color:var(--gray)"><?= date('M d, Y', strtotime($bl['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline" onsubmit="return confirm('Unblock this IP?')">
                  <?= csrfField() ?><input type="hidden" name="action" value="unblock_ip"><input type="hidden" name="id" value="<?= $bl['id'] ?>">
                  <button class="btn btn-sm btn-success" title="Unblock"><i class="fas fa-check"></i> Unblock</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Block IP form -->
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-plus" style="color:var(--violet)"></i> Block IP Address</h3></div>
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="block_ip">
        <div class="form-group">
          <label>IP Address <span class="required">*</span></label>
          <input type="text" class="form-control" name="ip_address" required placeholder="e.g. 192.168.1.100">
        </div>
        <div class="form-group">
          <label>Reason</label>
          <input type="text" class="form-control" name="reason" placeholder="Spam, brute-force, etc.">
        </div>
        <div class="form-group">
          <label>Expires At (optional)</label>
          <input type="datetime-local" class="form-control" name="expires_at">
          <p class="form-hint">Leave blank for permanent block.</p>
        </div>
        <button type="submit" class="btn btn-danger" style="width:100%"><i class="fas fa-ban"></i> Block IP</button>
      </form>
    </div>
  </div>
</div>

<!-- ── 2FA ── -->
<?php elseif ($tab === '2fa'): ?>
<div class="card" style="max-width:640px">
  <div class="card-header"><h3><i class="fas fa-mobile-alt" style="color:var(--violet)"></i> Two-Factor Authentication</h3></div>
  <div class="card-body">
    <div style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:18px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px">
      <i class="fas fa-info-circle" style="color:#2563eb;font-size:18px;margin-top:2px"></i>
      <div>
        <div style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:4px">2FA Requires SMTP Configuration</div>
        <p style="font-size:13px;color:var(--gray);line-height:1.65;margin:0">To enable two-factor authentication, you first need to configure your SMTP email settings in <a href="<?= ADMIN_URL ?>/pages/settings.php?tab=email" style="color:var(--violet);font-weight:600">Email Settings</a>. Once SMTP is active, 2FA will send OTP codes to admin email addresses on login.</p>
      </div>
    </div>

    <div class="pref-row" style="padding:14px 0;border-bottom:1px solid var(--border2)">
      <div>
        <div style="font-size:14px;font-weight:700;color:var(--primary)">Email OTP (2FA)</div>
        <div style="font-size:12.5px;color:var(--gray);margin-top:3px">Send a one-time code to admin email on each login</div>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" disabled>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div style="margin-top:24px">
      <div style="font-size:13px;font-weight:700;color:var(--primary);margin-bottom:14px">Trusted IP Addresses (skip 2FA)</div>
      <div style="background:var(--light);border-radius:10px;padding:14px;font-size:13px;color:var(--gray)">
        Current IP: <code style="background:#fff;padding:3px 8px;border-radius:6px"><?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?></code>
        <br><br>
        Trusted IPs bypass 2FA on login. Add your office/static IP to avoid 2FA prompts from trusted locations.
      </div>
      <div style="margin-top:14px;display:flex;gap:10px">
        <a href="<?= ADMIN_URL ?>/pages/settings.php?tab=email" class="btn btn-primary"><i class="fas fa-envelope"></i> Configure SMTP First</a>
        <a href="?tab=ip-blacklist" class="btn btn-secondary"><i class="fas fa-ban"></i> Manage IP Blacklist</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'overview'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var ctx = document.getElementById('loginChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($chartDays) ?>,
    datasets: [
      {label:'Successful', data:<?= json_encode($chartSucc) ?>, borderColor:'#059669', backgroundColor:'rgba(5,150,105,.1)', tension:.4, fill:true, pointRadius:4},
      {label:'Failed', data:<?= json_encode($chartFail) ?>, borderColor:'#e11d48', backgroundColor:'rgba(225,29,72,.08)', tension:.4, fill:true, pointRadius:4}
    ]
  },
  options: {
    responsive:true, interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'top'},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+ctx.parsed.y+' logins'}}}},
    scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}
  }
});
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
