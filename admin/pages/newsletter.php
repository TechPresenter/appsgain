<?php
$adminTitle = 'Newsletter';
$adminPage  = 'newsletter';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'unsubscribe') {
        $id = (int)$_POST['id'];
        dbExecute("UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE id = ?", [$id]);
        logActivity('update', 'newsletter', "Unsubscribed subscriber #{$id}");
        setFlash('success', 'Subscriber unsubscribed.');
        redirect(ADMIN_URL . '/pages/newsletter.php');
    }
    if ($postAction === 'resubscribe') {
        $id = (int)$_POST['id'];
        dbExecute("UPDATE newsletter_subscribers SET status = 'active' WHERE id = ?", [$id]);
        logActivity('update', 'newsletter', "Re-subscribed subscriber #{$id}");
        setFlash('success', 'Subscriber re-activated.');
        redirect(ADMIN_URL . '/pages/newsletter.php');
    }
    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        dbExecute("DELETE FROM newsletter_subscribers WHERE id = ?", [$id]);
        logActivity('delete', 'newsletter', "Deleted subscriber #{$id}");
        setFlash('success', 'Subscriber deleted.');
        redirect(ADMIN_URL . '/pages/newsletter.php');
    }
    if ($postAction === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', $ids);
            dbExecute("DELETE FROM newsletter_subscribers WHERE id IN ({$in})");
            logActivity('delete', 'newsletter', 'Bulk deleted ' . count($ids) . ' subscribers');
            setFlash('success', count($ids) . ' subscribers deleted.');
        }
        redirect(ADMIN_URL . '/pages/newsletter.php');
    }
    if ($postAction === 'bulk_unsubscribe') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', $ids);
            dbExecute("UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE id IN ({$in})");
            setFlash('success', count($ids) . ' subscribers unsubscribed.');
        }
        redirect(ADMIN_URL . '/pages/newsletter.php');
    }
}

/* ── CSV Export ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    Auth::requireAdmin();
    $status = sanitizeInput($_GET['status'] ?? '');
    if ($status) {
        $rows = dbFetchAll("SELECT email, name, status, ip_address, created_at FROM newsletter_subscribers WHERE status = ? ORDER BY created_at DESC", [$status]);
    } else {
        $rows = dbFetchAll("SELECT email, name, status, ip_address, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email','Name','Status','IP','Subscribed At']);
    foreach ($rows as $r) fputcsv($out, [$r['email'], $r['name'], $r['status'], $r['ip_address'], $r['created_at']]);
    fclose($out);
    exit;
}

$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$status  = sanitizeInput($_GET['status'] ?? '');

$where  = [];
$params = [];
if ($search) { $where[] = "(email LIKE ? OR name LIKE ?)"; $like = "%{$search}%"; $params = array_merge($params, [$like, $like]); }
if ($status) { $where[] = "status = ?"; $params[] = $status; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers {$whereSQL}", $params);
$subs       = dbFetchAll("SELECT * FROM newsletter_subscribers {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/newsletter.php?' . http_build_query(array_filter(['search'=>$search,'status'=>$status])));

/* ── Stats ── */
$activeCount  = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
$unsubCount   = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed'");
$bouncedCount = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'bounced'");
$todayCount   = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE DATE(created_at) = CURDATE()");
$weekCount    = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'active'");
$lastWeekCount = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'active'");
$weekGrowth   = $lastWeekCount > 0 ? round(($weekCount - $lastWeekCount) / $lastWeekCount * 100, 1) : ($weekCount > 0 ? 100 : 0);

/* ── 30-day growth chart data ── */
$chartDays   = 30;
$chartLabels = [];
$chartData   = [];
for ($i = $chartDays - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('d M', strtotime($d));
    $chartData[]   = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE DATE(created_at) = ?", [$d]);
}

/* ── Tab counts ── */
$tabCounts = [
    ''             => (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers"),
    'active'       => $activeCount,
    'unsubscribed' => $unsubCount,
    'bounced'      => $bouncedCount,
];

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.nl-tab-bar{display:flex;gap:0;background:var(--white);border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.nl-tab{flex:1;padding:10px 16px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:var(--gray);transition:var(--transition);text-align:center;text-decoration:none;display:block}
.nl-tab:hover{background:var(--light);color:var(--primary)}
.nl-tab.active{background:linear-gradient(135deg,var(--violet),var(--blue));color:#fff}
.nl-tab .tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:18px;border-radius:9px;background:rgba(255,255,255,.25);font-size:10.5px;padding:0 5px;margin-left:5px}
.nl-tab:not(.active) .tab-count{background:var(--border);color:var(--gray)}
</style>

<div class="page-header">
  <div>
    <h1>Newsletter Subscribers</h1>
    <p><?= number_format($activeCount) ?> active subscribers</p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="?export=csv<?= $status ? '&status='.$status : '' ?>" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Export CSV</a>
    <a href="?export=csv&status=active" class="btn btn-outline btn-sm"><i class="fas fa-user-check"></i> Active Only</a>
  </div>
</div>

<!-- Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px">
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--emerald),#047857);width:42px;height:42px;font-size:16px"><i class="fas fa-check-circle"></i></div>
    <div>
      <div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Active</div>
      <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($activeCount) ?></div>
    </div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--rose),#be123c);width:42px;height:42px;font-size:16px"><i class="fas fa-user-minus"></i></div>
    <div>
      <div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Unsubscribed</div>
      <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($unsubCount) ?></div>
    </div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--amber),#b45309);width:42px;height:42px;font-size:16px"><i class="fas fa-exclamation-circle"></i></div>
    <div>
      <div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Bounced</div>
      <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($bouncedCount) ?></div>
    </div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--blue),#1d4ed8);width:42px;height:42px;font-size:16px"><i class="fas fa-calendar-day"></i></div>
    <div>
      <div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Today</div>
      <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($todayCount) ?></div>
    </div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--blue));width:42px;height:42px;font-size:16px"><i class="fas fa-chart-line"></i></div>
    <div>
      <div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">This Week</div>
      <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($weekCount) ?></div>
      <?php if ($weekGrowth != 0): ?>
      <div style="font-size:11px;font-weight:600;color:<?= $weekGrowth > 0 ? 'var(--emerald)' : 'var(--rose)' ?>">
        <i class="fas fa-arrow-<?= $weekGrowth > 0 ? 'up' : 'down' ?>"></i> <?= abs($weekGrowth) ?>%
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Growth Chart -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <h3><i class="fas fa-chart-area" style="color:var(--violet);margin-right:8px"></i>30-Day Subscriber Growth</h3>
  </div>
  <div class="card-body">
    <canvas id="growthChart" height="80"></canvas>
  </div>
</div>

<!-- Status Tabs -->
<div class="nl-tab-bar">
  <?php
  $tabLabels = ['' => 'All', 'active' => 'Active', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced'];
  foreach ($tabLabels as $tv => $tl):
  ?>
  <a href="?status=<?= $tv ?><?= $search ? '&search='.urlencode($search) : '' ?>"
     class="nl-tab <?= $status === $tv ? 'active' : '' ?>">
    <?= $tl ?><span class="tab-count"><?= number_format($tabCounts[$tv]) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filter + Bulk -->
<form method="GET" class="filter-bar" id="filterForm">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="search-wrap"><i class="fas fa-search"></i><input type="text" name="search" class="search-input" placeholder="Search email or name…" value="<?= e($search) ?>"></div>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
  <?php if ($search): ?><a href="?status=<?= e($status) ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
</form>

<div class="card">
  <form method="POST" id="bulkForm">
    <?= csrfField() ?>
    <input type="hidden" name="action" id="bulkAction" value="bulk_delete">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="checkAll" onchange="toggleAll(this)" title="Select all"></th>
            <th>Subscriber</th>
            <th>Status</th>
            <th>Source IP</th>
            <th>Subscribed</th>
            <th style="width:100px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($subs): foreach ($subs as $s): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>" onchange="updateBulkBar()"></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0">
                  <?= strtoupper(substr($s['email'], 0, 1)) ?>
                </div>
                <div>
                  <div style="font-size:13.5px;font-weight:700;color:var(--primary)"><?= e($s['email']) ?></div>
                  <?php if ($s['name']): ?><div style="font-size:12px;color:var(--gray)"><?= e($s['name']) ?></div><?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $s['status'] === 'active' ? 'badge-success' : ($s['status'] === 'bounced' ? 'badge-danger' : 'badge-secondary') ?>">
                <?= ucfirst($s['status']) ?>
              </span>
            </td>
            <td style="font-size:12px;color:var(--gray);font-family:monospace"><?= e($s['ip_address'] ?: '—') ?></td>
            <td style="font-size:12px;color:var(--gray)" title="<?= e($s['created_at']) ?>"><?= timeAgo($s['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <?php if ($s['status'] === 'active'): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Unsubscribe this email?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="unsubscribe">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-warning btn-sm btn-icon" title="Unsubscribe"><i class="fas fa-user-minus"></i></button>
                </form>
                <?php elseif ($s['status'] === 'unsubscribed'): ?>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="resubscribe">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-sm btn-icon" title="Re-subscribe"><i class="fas fa-redo"></i></button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this subscriber?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:48px 20px">
              <i class="fas fa-paper-plane" style="font-size:36px;color:var(--gray2);opacity:.35;display:block;margin-bottom:12px"></i>
              <div style="font-size:14px;font-weight:600;color:var(--primary);margin-bottom:4px">No subscribers found</div>
              <div style="font-size:13px;color:var(--gray)">Try clearing the filters or check a different status tab.</div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($subs && $pagination): ?>
    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div style="display:flex;gap:6px">
        <button type="button" class="btn btn-danger btn-sm" onclick="bulkSubmit('bulk_delete')"><i class="fas fa-trash"></i> Delete Selected</button>
        <button type="button" class="btn btn-warning btn-sm" onclick="bulkSubmit('bulk_unsubscribe')"><i class="fas fa-user-minus"></i> Unsubscribe Selected</button>
      </div>
      <div class="pagination"><?= $pagination ?></div>
    </div>
    <?php elseif ($subs): ?>
    <div class="card-footer">
      <div style="display:flex;gap:6px">
        <button type="button" class="btn btn-danger btn-sm" onclick="bulkSubmit('bulk_delete')"><i class="fas fa-trash"></i> Delete Selected</button>
        <button type="button" class="btn btn-warning btn-sm" onclick="bulkSubmit('bulk_unsubscribe')"><i class="fas fa-user-minus"></i> Unsubscribe Selected</button>
      </div>
    </div>
    <?php endif; ?>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Growth Chart ── */
(function() {
  const labels = <?= json_encode($chartLabels) ?>;
  const data   = <?= json_encode($chartData) ?>;
  const ctx    = document.getElementById('growthChart').getContext('2d');
  const grad   = ctx.createLinearGradient(0, 0, 0, 240);
  grad.addColorStop(0, 'rgba(124,58,237,.25)');
  grad.addColorStop(1, 'rgba(124,58,237,0)');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'New Subscribers',
        data,
        borderColor: '#7c3aed',
        backgroundColor: grad,
        fill: true,
        tension: .4,
        pointRadius: 3,
        pointBackgroundColor: '#7c3aed',
        borderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
      scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { precision: 0, font: { size: 11 } }, beginAtZero: true }
      }
    }
  });
})();

/* ── Bulk actions ── */
function toggleAll(cb) {
  document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = cb.checked);
  updateBulkBar();
}
function updateBulkBar() {
  const checked = document.querySelectorAll('input[name="ids[]"]:checked');
  document.getElementById('checkAll').indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('input[name="ids[]"]').length;
}
function bulkSubmit(action) {
  const checked = document.querySelectorAll('input[name="ids[]"]:checked');
  if (!checked.length) { showToast('Select at least one subscriber.', 'warning'); return; }
  const label = action === 'bulk_delete' ? 'delete ' + checked.length + ' subscriber(s)' : 'unsubscribe ' + checked.length + ' subscriber(s)';
  if (!confirm('Are you sure you want to ' + label + '?')) return;
  document.getElementById('bulkAction').value = action;
  document.getElementById('bulkForm').submit();
}
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
