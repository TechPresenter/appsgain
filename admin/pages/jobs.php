<?php
/**
 * Admin: Jobs / Careers Management — Full CRUD
 */
$adminPage  = 'jobs';
$adminTitle = 'Jobs & Careers';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

/* ── Ensure table exists with all columns ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS `jobs` (
      `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `title`        VARCHAR(200) NOT NULL,
      `slug`         VARCHAR(200) NOT NULL DEFAULT '',
      `department`   VARCHAR(100) NOT NULL DEFAULT 'Development',
      `type`         ENUM('Full-time','Part-time','Contract','Freelance','Internship') NOT NULL DEFAULT 'Full-time',
      `work_mode`    ENUM('On-site','Remote','Hybrid') NOT NULL DEFAULT 'On-site',
      `shift`        ENUM('Morning','Evening','Night','Flexible','Any') NOT NULL DEFAULT 'Flexible',
      `location`     VARCHAR(200) NOT NULL DEFAULT 'Bangalore / Remote',
      `experience`   VARCHAR(100) NOT NULL DEFAULT '1-2 years',
      `education`    VARCHAR(200) NOT NULL DEFAULT 'Any Graduate',
      `salary_range` VARCHAR(100) DEFAULT NULL,
      `openings`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
      `skills`       TEXT DEFAULT NULL,
      `description`  LONGTEXT NOT NULL DEFAULT '',
      `requirements` LONGTEXT DEFAULT NULL,
      `benefits`     LONGTEXT DEFAULT NULL,
      `apply_url`    VARCHAR(500) DEFAULT NULL,
      `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
      `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
      `deadline`     DATE DEFAULT NULL,
      `sort_order`   INT NOT NULL DEFAULT 0,
      `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `department`(`department`), KEY `is_active`(`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Add new columns if upgrading from old schema */
    $cols = array_column(dbFetchAll("SHOW COLUMNS FROM jobs"), 'Field');
    if (!in_array('work_mode', $cols)) db()->exec("ALTER TABLE jobs ADD COLUMN work_mode ENUM('On-site','Remote','Hybrid') NOT NULL DEFAULT 'On-site' AFTER type");
    if (!in_array('shift',     $cols)) db()->exec("ALTER TABLE jobs ADD COLUMN shift ENUM('Morning','Evening','Night','Flexible','Any') NOT NULL DEFAULT 'Flexible' AFTER work_mode");
    if (!in_array('education', $cols)) db()->exec("ALTER TABLE jobs ADD COLUMN education VARCHAR(200) NOT NULL DEFAULT 'Any Graduate' AFTER experience");
} catch (Exception $e) {}

/* ── DELETE ── */
if ($action === 'delete' && $id) {
    verifyCsrf();
    dbExecute("DELETE FROM jobs WHERE id = ?", [$id]);
    setFlash('success', 'Job deleted.');
    redirect(ADMIN_URL . '/pages/jobs.php');
}

/* ── TOGGLE ACTIVE ── */
if ($action === 'toggle' && $id) {
    $j = dbFetchOne("SELECT is_active FROM jobs WHERE id = ?", [$id]);
    if ($j) dbExecute("UPDATE jobs SET is_active = ? WHERE id = ?", [1 - $j['is_active'], $id]);
    redirect(ADMIN_URL . '/pages/jobs.php');
}

/* ── TOGGLE FEATURED ── */
if ($action === 'feature' && $id) {
    $j = dbFetchOne("SELECT is_featured FROM jobs WHERE id = ?", [$id]);
    if ($j) dbExecute("UPDATE jobs SET is_featured = ? WHERE id = ?", [1 - $j['is_featured'], $id]);
    redirect(ADMIN_URL . '/pages/jobs.php');
}

/* ── SAVE (CREATE / UPDATE) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','edit'])) {
    verifyCsrf();
    $d = [
        'title'        => sanitizeInput($_POST['title']        ?? ''),
        'department'   => sanitizeInput($_POST['department']   ?? 'Development'),
        'type'         => sanitizeInput($_POST['type']         ?? 'Full-time'),
        'work_mode'    => sanitizeInput($_POST['work_mode']    ?? 'On-site'),
        'shift'        => sanitizeInput($_POST['shift']        ?? 'Flexible'),
        'location'     => sanitizeInput($_POST['location']     ?? 'Bangalore / Remote'),
        'experience'   => sanitizeInput($_POST['experience']   ?? ''),
        'education'    => sanitizeInput($_POST['education']    ?? 'Any Graduate'),
        'salary_range' => sanitizeInput($_POST['salary_range'] ?? ''),
        'openings'     => max(1, (int)($_POST['openings']      ?? 1)),
        'skills'       => sanitizeInput($_POST['skills']       ?? ''),
        'description'  => $_POST['description']  ?? '',
        'requirements' => $_POST['requirements']  ?? '',
        'benefits'     => $_POST['benefits']      ?? '',
        'apply_url'    => sanitizeInput($_POST['apply_url']    ?? ''),
        'deadline'     => sanitizeInput($_POST['deadline']     ?? '') ?: null,
        'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'    => isset($_POST['is_active'])   ? 1 : 0,
        'sort_order'   => (int)($_POST['sort_order']   ?? 0),
    ];

    if (!$d['title']) {
        setFlash('error', 'Job title is required.');
    } else {
        $slug = slugify($d['title']);
        $ex   = dbFetchOne("SELECT id FROM jobs WHERE slug = ? AND id != ?", [$slug, $id ?: 0]);
        if ($ex) $slug .= '-' . substr(time(), -4);
        $d['slug'] = $slug;

        if ($action === 'create') {
            dbInsertRow('jobs', $d);
            setFlash('success', 'Job posting created!');
        } else {
            dbUpdateRow('jobs', $d, 'id = ?', [$id]);
            setFlash('success', 'Job posting updated!');
        }
        redirect(ADMIN_URL . '/pages/jobs.php');
    }
}

/* ── LOAD JOB FOR EDIT ── */
$job = ($id && in_array($action, ['edit'])) ? dbFetchOne("SELECT * FROM jobs WHERE id = ?", [$id]) : null;

/* ── LIST DATA ── */
$filterDept = sanitizeInput($_GET['dept'] ?? '');
$filterType = sanitizeInput($_GET['type'] ?? '');
$search     = sanitizeInput($_GET['q']    ?? '');

$qStr = "SELECT * FROM jobs WHERE 1=1";
$qP   = [];
if ($filterDept) { $qStr .= " AND department=?"; $qP[] = $filterDept; }
if ($filterType) { $qStr .= " AND type=?";       $qP[] = $filterType; }
if ($search)     { $qStr .= " AND (title LIKE ? OR skills LIKE ? OR department LIKE ?)"; $like = "%$search%"; $qP = array_merge($qP,[$like,$like,$like]); }
$qStr .= " ORDER BY sort_order ASC, created_at DESC";

$jobs = []; $deptList = [];
try {
    $jobs     = dbFetchAll($qStr, $qP);
    $deptList = dbFetchAll("SELECT DISTINCT department FROM jobs ORDER BY department");
} catch(Exception $e) {}

$totalActive   = count(array_filter($jobs, function($j){ return $j['is_active']; }));
$totalFeatured = count(array_filter($jobs, function($j){ return $j['is_featured']; }));

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<?php if (in_array($action, ['create','edit'])): ?>
<!-- ══════════════════════════════════════════
     CREATE / EDIT FORM
══════════════════════════════════════════ -->
<div class="page-header">
  <div>
    <h1><?= $action === 'create' ? 'Add New Job Posting' : 'Edit Job: ' . e($job['title'] ?? '') ?></h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px;"><?= $action === 'create' ? 'Post a new career opportunity' : 'Update job details' ?></p>
  </div>
  <a href="<?= ADMIN_URL ?>/pages/jobs.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Jobs</a>
</div>
<?php renderFlash(); ?>

<form method="POST">
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

    <!-- ── LEFT: Main fields ── -->
    <div>

      <!-- Basic Info -->
      <div class="card" style="margin-bottom:22px;">
        <div class="card-header">
          <h3><i class="fas fa-briefcase" style="color:var(--violet)"></i> Job Details</h3>
        </div>
        <div class="card-body">

          <div class="form-group">
            <label>Job Title <span class="required">*</span></label>
            <input type="text" name="title" class="form-control" required
              value="<?= e($job['title'] ?? '') ?>" placeholder="e.g. Senior PHP Developer">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Department</label>
              <input type="text" name="department" class="form-control" list="dept-suggestions"
                value="<?= e($job['department'] ?? 'Development') ?>">
              <datalist id="dept-suggestions">
                <?php foreach(['Development','Design','Marketing','Sales','HR','Finance','Support','Operations','DevOps','AI/ML'] as $d): ?>
                <option value="<?= $d ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="form-group">
              <label>Job Type</label>
              <select name="type" class="form-control">
                <?php foreach(['Full-time','Part-time','Contract','Freelance','Internship'] as $t): ?>
                <option value="<?= $t ?>" <?= ($job['type'] ?? 'Full-time') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Work Mode</label>
              <select name="work_mode" class="form-control">
                <?php foreach(['On-site','Remote','Hybrid'] as $wm): ?>
                <option value="<?= $wm ?>" <?= ($job['work_mode'] ?? 'On-site') === $wm ? 'selected' : '' ?>><?= $wm ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Shift</label>
              <select name="shift" class="form-control">
                <?php foreach(['Morning','Evening','Night','Flexible','Any'] as $sh): ?>
                <option value="<?= $sh ?>" <?= ($job['shift'] ?? 'Flexible') === $sh ? 'selected' : '' ?>><?= $sh ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Location</label>
              <input type="text" name="location" class="form-control"
                value="<?= e($job['location'] ?? 'Bangalore / Remote') ?>" placeholder="Bangalore / Remote">
            </div>
            <div class="form-group">
              <label>Experience Required</label>
              <input type="text" name="experience" class="form-control" list="exp-list"
                value="<?= e($job['experience'] ?? '') ?>" placeholder="e.g. 2-4 years">
              <datalist id="exp-list">
                <option value="Fresher / 0-1 year">
                <option value="1-2 years">
                <option value="2-4 years">
                <option value="3-5 years">
                <option value="5-8 years">
                <option value="8+ years">
              </datalist>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Education</label>
              <input type="text" name="education" class="form-control" list="edu-list"
                value="<?= e($job['education'] ?? 'Any Graduate') ?>">
              <datalist id="edu-list">
                <option value="Any Graduate">
                <option value="B.Tech / B.E.">
                <option value="BCA / B.Sc CS">
                <option value="MCA / M.Tech">
                <option value="MBA">
                <option value="Diploma">
                <option value="10+2 / HSC">
              </datalist>
            </div>
            <div class="form-group">
              <label>Salary Range</label>
              <input type="text" name="salary_range" class="form-control" list="sal-list"
                value="<?= e($job['salary_range'] ?? '') ?>" placeholder="e.g. ₹6-10 LPA">
              <datalist id="sal-list">
                <option value="₹2-4 LPA"><option value="₹4-6 LPA">
                <option value="₹6-10 LPA"><option value="₹10-15 LPA">
                <option value="₹15-20 LPA"><option value="₹20+ LPA">
                <option value="As per industry standards">
              </datalist>
            </div>
          </div>

          <div class="form-group">
            <label>Required Skills <span style="font-weight:400;color:var(--gray)">(comma-separated)</span></label>
            <input type="text" name="skills" class="form-control"
              value="<?= e($job['skills'] ?? '') ?>" placeholder="PHP, Laravel, MySQL, REST APIs, Git">
          </div>

        </div>
      </div>

      <!-- Job Description (CKEditor) -->
      <div class="card" style="margin-bottom:22px;">
        <div class="card-header">
          <h3><i class="fas fa-file-alt" style="color:var(--blue)"></i> Job Description <span class="required">*</span></h3>
        </div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0;">
            <textarea name="description" id="jobDescription" class="form-control" rows="8" required><?= htmlspecialchars($job['description'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Requirements -->
      <div class="card" style="margin-bottom:22px;">
        <div class="card-header">
          <h3><i class="fas fa-list-check" style="color:var(--emerald)"></i> Requirements</h3>
        </div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0;">
            <label style="margin-bottom:6px;">One requirement per line</label>
            <textarea name="requirements" id="jobRequirements" class="form-control" rows="5"
              placeholder="Bachelor's degree in Computer Science&#10;3+ years of PHP / Laravel experience&#10;Strong MySQL & REST API skills&#10;Experience with Git version control"><?= htmlspecialchars($job['requirements'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Benefits -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-gift" style="color:var(--amber)"></i> Benefits & Perks</h3>
        </div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0;">
            <label style="margin-bottom:6px;">One benefit per line</label>
            <textarea name="benefits" class="form-control" rows="4"
              placeholder="5-day work week&#10;Health insurance for employee &amp; family&#10;Annual performance bonus&#10;Remote / hybrid flexibility&#10;Learning &amp; development budget"><?= e($job['benefits'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

    </div><!-- /left -->

    <!-- ── RIGHT: Sidebar options ── -->
    <div>

      <!-- Publish -->
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header"><h3><i class="fas fa-rocket" style="color:var(--violet)"></i> Publish</h3></div>
        <div class="card-body">
          <button type="submit" class="btn btn-primary" style="width:100%;margin-bottom:10px;">
            <i class="fas fa-save"></i> <?= $action === 'create' ? 'Publish Job' : 'Save Changes' ?>
          </button>
          <a href="<?= ADMIN_URL ?>/pages/jobs.php" class="btn btn-secondary" style="width:100%;">
            <i class="fas fa-times"></i> Cancel
          </a>
        </div>
      </div>

      <!-- Settings -->
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header"><h3><i class="fas fa-cog"></i> Settings</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>No. of Openings</label>
            <input type="number" name="openings" class="form-control" min="1"
              value="<?= (int)($job['openings'] ?? 1) ?>">
          </div>
          <div class="form-group">
            <label>Application Deadline</label>
            <input type="date" name="deadline" class="form-control"
              value="<?= e($job['deadline'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control"
              value="<?= (int)($job['sort_order'] ?? 0) ?>">
          </div>
          <div class="form-group">
            <label>External Apply URL <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
            <input type="url" name="apply_url" class="form-control"
              value="<?= e($job['apply_url'] ?? '') ?>" placeholder="https://…">
            <div class="form-hint">Leave blank → uses built-in form</div>
          </div>
        </div>
      </div>

      <!-- Visibility -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-eye"></i> Visibility</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13.5px;font-weight:600;color:var(--primary);">Active</div>
              <div style="font-size:12px;color:var(--gray);">Show on careers page</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="is_active" value="1" <?= !isset($job) || ($job['is_active'] ?? 1) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13.5px;font-weight:600;color:var(--primary);">Featured</div>
              <div style="font-size:12px;color:var(--gray);">Highlight as urgent/hot</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="is_featured" value="1" <?= !empty($job['is_featured']) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

    </div><!-- /right -->
  </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('jobDescription', {
        height: 380,
        placeholder: 'Describe the role, key responsibilities, day-to-day work, and what makes this opportunity exciting…'
    });
    initCKEditor('jobRequirements', {
        height: 240,
        small: true,
        placeholder: 'List candidate requirements one per line…'
    });
});
</script>

<?php else: ?>
<!-- ══════════════════════════════════════════
     JOBS LIST
══════════════════════════════════════════ -->
<div class="page-header">
  <div>
    <h1>Jobs &amp; Careers</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px;">
      <?= count($jobs) ?> postings &nbsp;·&nbsp;
      <span style="color:var(--emerald);font-weight:700;"><?= $totalActive ?> active</span> &nbsp;·&nbsp;
      <span style="color:var(--violet);font-weight:700;"><?= $totalFeatured ?> featured</span>
    </p>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= SITE_URL ?>/careers.php" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> View Live</a>
    <a href="?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Job</a>
  </div>
</div>

<?php renderFlash(); ?>

<!-- Quick stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
  <div class="stat-card" style="--stat-color:rgba(124,58,237,.08);">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--blue));"><i class="fas fa-briefcase"></i></div>
    <div class="stat-info"><div class="label">Total Jobs</div><div class="value"><?= count($jobs) ?></div></div>
  </div>
  <div class="stat-card" style="--stat-color:rgba(5,150,105,.08);">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--emerald),var(--teal));"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><div class="label">Active</div><div class="value"><?= $totalActive ?></div></div>
  </div>
  <div class="stat-card" style="--stat-color:rgba(124,58,237,.08);">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--rose));"><i class="fas fa-fire"></i></div>
    <div class="stat-info"><div class="label">Featured</div><div class="value"><?= $totalFeatured ?></div></div>
  </div>
  <div class="stat-card" style="--stat-color:rgba(37,99,235,.08);">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--blue),var(--cyan));"><i class="fas fa-file-alt"></i></div>
    <div class="stat-info">
      <div class="label">Applications</div>
      <div class="value"><?php
        try { echo (int)dbFetchValue("SELECT COUNT(*) FROM job_applications"); }
        catch(Exception $e){ echo '0'; }
      ?></div>
    </div>
  </div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <div class="search-wrap">
    <i class="fas fa-search"></i>
    <input type="text" name="q" class="search-input" placeholder="Search jobs…" value="<?= e($search) ?>">
  </div>
  <select name="dept" class="form-control" style="width:160px;">
    <option value="">All Departments</option>
    <?php foreach ($deptList as $d): ?>
    <option value="<?= e($d['department']) ?>" <?= $filterDept === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="type" class="form-control" style="width:140px;">
    <option value="">All Types</option>
    <?php foreach(['Full-time','Part-time','Contract','Freelance','Internship'] as $t): ?>
    <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Filter</button>
  <?php if ($search || $filterDept || $filterType): ?>
  <a href="<?= ADMIN_URL ?>/pages/jobs.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>
  <?php endif; ?>
</form>

<?php if (empty($jobs)): ?>
<div class="card">
  <div style="text-align:center;padding:56px 20px;">
    <i class="fas fa-briefcase" style="font-size:40px;color:var(--gray2);opacity:.3;display:block;margin-bottom:14px;"></i>
    <div style="font-size:15px;font-weight:700;color:var(--primary);margin-bottom:6px;">No job postings yet</div>
    <div style="font-size:13px;color:var(--gray);margin-bottom:18px;"><?= $search || $filterDept || $filterType ? 'No results for your filters.' : 'Create your first job posting.' ?></div>
    <a href="?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add First Job</a>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Job Title</th>
          <th>Department</th>
          <th>Type / Mode</th>
          <th>Location</th>
          <th>Experience</th>
          <th>Salary</th>
          <th>Openings</th>
          <th>Deadline</th>
          <th>Status</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $typeColor = ['Full-time'=>'#059669','Part-time'=>'#0891b2','Contract'=>'#7c3aed','Freelance'=>'#d97706','Internship'=>'#2563eb'];
        $modeIcon  = ['On-site'=>'fa-building','Remote'=>'fa-laptop-house','Hybrid'=>'fa-code-branch'];
        foreach ($jobs as $j):
          $tc  = $typeColor[$j['type']] ?? '#7c3aed';
          $mi  = $modeIcon[$j['work_mode'] ?? 'On-site'] ?? 'fa-building';
          $exp = (new DateTime())->diff(new DateTime($j['deadline'] ?? 'today'))->days ?? null;
          $expired = $j['deadline'] && strtotime($j['deadline']) < time();
        ?>
        <tr>
          <td>
            <div style="font-weight:700;font-size:13.5px;color:var(--primary);"><?= e($j['title']) ?></div>
            <?php if ($j['is_featured']): ?>
            <span class="badge badge-violet" style="font-size:10px;margin-top:2px;"><i class="fas fa-fire" style="font-size:9px;"></i> Featured</span>
            <?php endif; ?>
            <?php if ($j['skills']): ?>
            <div style="font-size:11px;color:var(--gray2);margin-top:3px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($j['skills']) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-info"><?= e($j['department']) ?></span></td>
          <td>
            <div><span class="badge" style="background:<?= $tc ?>1a;color:<?= $tc ?>;border-color:<?= $tc ?>33;"><?= e($j['type']) ?></span></div>
            <div style="margin-top:4px;font-size:11.5px;color:var(--gray);"><i class="fas <?= $mi ?>" style="font-size:10px;"></i> <?= e($j['work_mode'] ?? 'On-site') ?></div>
          </td>
          <td style="font-size:13px;color:var(--gray);"><?= e($j['location']) ?></td>
          <td style="font-size:13px;color:var(--gray);"><?= e($j['experience']) ?></td>
          <td style="font-size:13px;font-weight:600;color:var(--primary);"><?= e($j['salary_range'] ?: '—') ?></td>
          <td style="text-align:center;font-weight:700;font-size:15px;color:var(--primary);"><?= (int)$j['openings'] ?></td>
          <td style="font-size:12px;">
            <?php if ($j['deadline']): ?>
            <span style="color:<?= $expired ? 'var(--rose)' : 'var(--emerald)' ?>;font-weight:600;">
              <?= $expired ? '⚠ Expired' : date('d M Y', strtotime($j['deadline'])) ?>
            </span>
            <?php else: ?>
            <span style="color:var(--gray2);">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="?action=toggle&id=<?= $j['id'] ?>" class="badge <?= $j['is_active'] ? 'badge-success' : 'badge-secondary' ?>"
               title="Click to toggle" style="cursor:pointer;">
              <?= $j['is_active'] ? 'Active' : 'Hidden' ?>
            </a>
          </td>
          <td>
            <div style="display:flex;gap:5px;align-items:center;">
              <a href="?action=edit&id=<?= $j['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
              <a href="?action=feature&id=<?= $j['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="<?= $j['is_featured'] ? 'Remove Featured' : 'Set Featured' ?>" style="<?= $j['is_featured'] ? 'color:var(--violet);' : '' ?>"><i class="fas fa-fire"></i></a>
              <form method="POST" action="<?= ADMIN_URL ?>/pages/jobs.php?action=delete&id=<?= $j['id'] ?>" style="display:inline;" onsubmit="return confirm('Delete this job posting?')">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
