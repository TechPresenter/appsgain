<?php
$adminTitle = 'Job Applications';
$adminPage  = 'applications';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* ── Status update ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';

    if ($act === 'update_status') {
        $id     = (int)$_POST['id'];
        $status = in_array($_POST['status'] ?? '', ['new','reviewed','shortlisted','rejected'])
                ? $_POST['status'] : 'new';
        dbExecute("UPDATE job_applications SET status = ? WHERE id = ?", [$status, $id]);
        setFlash('success', 'Status updated.');
        redirect(ADMIN_URL . '/pages/applications.php');
    }

    if ($act === 'delete') {
        $id  = (int)$_POST['id'];
        $app = dbFetchOne("SELECT resume_path FROM job_applications WHERE id = ?", [$id]);
        if ($app && $app['resume_path']) {
            $fp = __DIR__ . '/../../uploads/' . $app['resume_path'];
            if (file_exists($fp)) @unlink($fp);
        }
        dbExecute("DELETE FROM job_applications WHERE id = ?", [$id]);
        setFlash('success', 'Application deleted.');
        redirect(ADMIN_URL . '/pages/applications.php');
    }
}

/* ── Ensure table exists ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL DEFAULT '',
        email VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(50) NOT NULL DEFAULT '',
        position VARCHAR(200) NOT NULL DEFAULT '',
        message TEXT,
        linkedin VARCHAR(500) NOT NULL DEFAULT '',
        resume_path VARCHAR(500) NOT NULL DEFAULT '',
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        status ENUM('new','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status(status), INDEX idx_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── Filters ── */
$status = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = []; $params = [];
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR position LIKE ?)'; $like = "%$search%"; $params = array_merge($params, [$like,$like,$like]); }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications $whereSQL", $params);
$apps  = dbFetchAll("SELECT * FROM job_applications $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/applications.php?' . http_build_query(array_filter(['status'=>$status,'search'=>$search])));

$cntAll         = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications");
$cntNew         = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications WHERE status='new'");
$cntReviewed    = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications WHERE status='reviewed'");
$cntShortlisted = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications WHERE status='shortlisted'");
$cntRejected    = (int)dbFetchValue("SELECT COUNT(*) FROM job_applications WHERE status='rejected'");

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <h1>Job Applications</h1>
    <p><?= number_format($cntAll) ?> total · <span style="color:var(--rose);font-weight:700;"><?= $cntNew ?> new</span></p>
  </div>
  <a href="<?= ADMIN_URL ?>/pages/jobs.php" class="btn btn-secondary btn-sm"><i class="fas fa-briefcase"></i> Manage Jobs</a>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
  <?php
  $tabs = ['' => ['All', $cntAll], 'new' => ['New', $cntNew], 'reviewed' => ['Reviewed', $cntReviewed], 'shortlisted' => ['Shortlisted', $cntShortlisted], 'rejected' => ['Rejected', $cntRejected]];
  foreach ($tabs as $tv => [$tl, $tc]):
  ?>
  <a href="?status=<?= $tv ?><?= $search ? '&search='.urlencode($search) : '' ?>"
     class="status-tab <?= $status === $tv ? 'active' : '' ?>">
    <?= $tl ?> <span class="tab-count"><?= $tc ?></span>
  </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="filter-bar">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="search-wrap">
    <i class="fas fa-search"></i>
    <input type="text" name="search" class="search-input" placeholder="Search by name, email, position…" value="<?= e($search) ?>">
  </div>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Search</button>
  <?php if ($search): ?><a href="?status=<?= e($status) ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
</form>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Applicant</th>
          <th>Position</th>
          <th>Phone</th>
          <th>Resume</th>
          <th>Status</th>
          <th>Applied</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($apps): foreach ($apps as $app):
          $statusColors = ['new'=>'badge-danger','reviewed'=>'badge-info','shortlisted'=>'badge-success','rejected'=>'badge-secondary'];
        ?>
        <tr>
          <td style="font-size:12px;color:var(--gray)">#<?= $app['id'] ?></td>
          <td>
            <div style="font-weight:700;font-size:13.5px;color:var(--primary)"><?= e($app['name']) ?></div>
            <div style="font-size:12px;color:var(--gray)"><a href="mailto:<?= e($app['email']) ?>" style="color:var(--violet)"><?= e($app['email']) ?></a></div>
            <?php if ($app['linkedin']): ?>
            <div style="font-size:11.5px;margin-top:2px;"><a href="<?= e($app['linkedin']) ?>" target="_blank" rel="noopener" style="color:var(--blue)"><i class="fab fa-linkedin"></i> LinkedIn</a></div>
            <?php endif; ?>
          </td>
          <td style="font-size:13px;font-weight:600;color:var(--primary)"><?= e($app['position'] ?: '—') ?></td>
          <td style="font-size:13px"><?= e($app['phone'] ?: '—') ?></td>
          <td>
            <?php if ($app['resume_path']): ?>
            <?php /* Served by an authenticated handler — /uploads/resumes/ is
                     blocked at the web root so CVs are not public files. */ ?>
            <?php $resumeUrl = ADMIN_URL . '/download-resume.php?id=' . (int)$app['id']; ?>
            <a href="<?= e($resumeUrl) ?>" target="_blank" rel="noopener"
               class="btn btn-outline btn-sm" style="font-size:12px;gap:5px;">
              <i class="fas fa-file-pdf"></i> Download
            </a>
            <?php else: ?>
            <span style="font-size:12px;color:var(--gray)">—</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" value="<?= $app['id'] ?>">
              <select name="status" class="form-control"
                      style="padding:5px 10px;font-size:12px;border-radius:8px;width:auto;min-width:100px;"
                      onchange="this.form.submit()">
                <option value="new"         <?= $app['status']==='new'?'selected':'' ?>>🔴 New</option>
                <option value="reviewed"    <?= $app['status']==='reviewed'?'selected':'' ?>>🔵 Reviewed</option>
                <option value="shortlisted" <?= $app['status']==='shortlisted'?'selected':'' ?>>🟢 Shortlisted</option>
                <option value="rejected"    <?= $app['status']==='rejected'?'selected':'' ?>>⚫ Rejected</option>
              </select>
            </form>
          </td>
          <td style="font-size:12px;color:var(--gray)" title="<?= e($app['created_at']) ?>"><?= timeAgo($app['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <?php if ($app['message']): ?>
              <button class="btn btn-secondary btn-sm btn-icon" title="<?= e($app['message']) ?>"
                      onclick="alert('Message:\n\n<?= e(addslashes($app['message'])) ?>')">
                <i class="fas fa-comment"></i>
              </button>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this application and resume?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $app['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:48px 20px;">
            <i class="fas fa-file-alt" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;color:var(--gray2)"></i>
            <div style="font-size:14px;font-weight:600;color:var(--primary);margin-bottom:4px;">No applications yet</div>
            <div style="font-size:13px;color:var(--gray);">Applications from the careers page will appear here.</div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagination): ?>
  <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <span style="font-size:13px;color:var(--gray)">Showing <?= number_format(($page-1)*$perPage+1) ?>–<?= number_format(min($page*$perPage,$total)) ?> of <?= number_format($total) ?></span>
    <div class="pagination"><?= $pagination ?></div>
  </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
