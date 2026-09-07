<?php
$adminTitle = 'Employee Management';
$adminPage  = 'employees';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* ── Schema migrations (safe, run once) ── */
try {
    /* Add department column to users if missing */
    db()->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) NULL AFTER role");
} catch(Exception $e) {
    /* Older MySQL — check manually */
    $hasDept = dbFetchAll("SHOW COLUMNS FROM users LIKE 'department'");
    if (empty($hasDept)) {
        try { db()->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) NULL AFTER role"); } catch(Exception $e2){}
    }
}
try {
    /* Extend role ENUM to include more roles */
    db()->exec("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','editor','manager','hr','counselor','staff') DEFAULT 'editor'");
} catch(Exception $e) { /* ignore if already extended */ }

/* ── Valid roles (must match ENUM above) ── */
$validRoles = ['admin','editor','manager','hr','counselor','staff'];

/* ── Load employees (from users table) ── */
$search  = sanitizeInput($_GET['search'] ?? '');
$role    = sanitizeInput($_GET['role']   ?? '');
$dept    = sanitizeInput($_GET['dept']   ?? '');
$viewId  = (int)($_GET['view']           ?? 0);

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_access') {
        $id     = (int)$_POST['id'];
        $enable = (int)$_POST['enable'];
        if ($id && $id !== ($admin['id'] ?? 0)) {
            dbExecute("UPDATE users SET is_active=? WHERE id=?", [$enable, $id]);
            logActivity($enable ? 'enable' : 'disable', 'user', ($enable ? 'Enabled' : 'Disabled') . " employee #{$id} login access");
            setFlash('success', 'Employee access ' . ($enable ? 'enabled' : 'disabled') . '.');
        } else {
            setFlash('error', 'Cannot modify your own account.');
        }
    }

    if ($action === 'update_role') {
        $id  = (int)$_POST['id'];
        $nr  = sanitizeInput($_POST['role']);
        if ($id && in_array($nr, $validRoles)) {
            dbExecute("UPDATE users SET role=? WHERE id=?", [$nr, $id]);
            logActivity('update', 'user', "Updated user #{$id} role to {$nr}");
            setFlash('success', 'Role updated.');
        }
    }

    if ($action === 'add_employee') {
        $name  = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $role  = sanitizeInput($_POST['role'] ?? 'staff');
        $dept  = sanitizeInput($_POST['department'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $allowed = ['admin','editor','manager','hr','counselor','staff'];

        $errors = [];
        if (strlen($name) < 2)       $errors[] = 'Name is required.';
        if (!validateEmail($email))  $errors[] = 'Valid email required.';
        if (strlen($pass) < 6)       $errors[] = 'Password must be at least 6 characters.';
        if (!in_array($role, $allowed)) $errors[] = 'Invalid role.';

        if (!$errors) {
            $exists = dbFetchValue("SELECT id FROM users WHERE email=?", [$email]);
            if ($exists) {
                setFlash('error', 'An account with this email already exists.');
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                dbInsertRow('users', ['name'=>$name,'email'=>$email,'password'=>$hash,'role'=>$role,'department'=>$dept,'is_active'=>1]);
                logActivity('create', 'user', "Added new employee: {$name} ({$role})");
                setFlash('success', "Employee {$name} added successfully.");
            }
        } else {
            setFlash('error', implode(' ', $errors));
        }
    }

    if ($action === 'delete_employee') {
        $id = (int)$_POST['id'];
        if ($id && $id !== ($admin['id'] ?? 0)) {
            dbExecute("DELETE FROM users WHERE id=?", [$id]);
            logActivity('delete', 'user', "Deleted employee #{$id}");
            setFlash('success', 'Employee removed.');
        }
    }

    redirect(ADMIN_URL . '/pages/employees.php');
}

/* ── Build query ── */
$where = ['id > 0'];
$params = [];
if ($search) {
    $where[]  = "(name LIKE ? OR email LIKE ? OR department LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like,$like,$like]);
}
if ($role)   { $where[] = "role=?";       $params[] = $role; }
if ($dept)   { $where[] = "department=?"; $params[] = $dept; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$employees = dbFetchAll("SELECT * FROM users {$whereSQL} ORDER BY name ASC", $params);
$total     = count($employees);

/* single view */
if ($viewId) {
    $emp = dbFetchOne("SELECT * FROM users WHERE id=?", [$viewId]);
}

/* Dept list */
try { $depts = dbFetchAll("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department"); } catch(Exception $e){ $depts = []; }

$roles      = $validRoles;
$roleColors = ['admin'=>'#7c3aed','editor'=>'#2563eb','manager'=>'#059669','hr'=>'#d97706','counselor'=>'#06b6d4','staff'=>'#6b7280','superadmin'=>'#e11d48'];
$roleBg     = ['admin'=>'rgba(124,58,237,.1)','editor'=>'rgba(37,99,235,.1)','manager'=>'rgba(5,150,105,.1)','hr'=>'rgba(217,119,6,.1)','counselor'=>'rgba(6,182,212,.1)','staff'=>'rgba(107,114,128,.1)','superadmin'=>'rgba(225,29,72,.1)'];

$avatarColors = ['#7c3aed','#e11d48','#059669','#2563eb','#d97706','#06b6d4','#ea580c','#8b5cf6'];
?>
<?php require_once dirname(__DIR__) . '/includes/admin-head.php'; ?>
<style>
.emp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:var(--transition);display:flex;flex-direction:column;gap:14px}
.emp-card:hover{box-shadow:var(--shadow-md);border-color:rgba(124,58,237,.2)}
.emp-top{display:flex;align-items:center;gap:12px}
.emp-avatar{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0}
.emp-name{font-size:15px;font-weight:800;color:var(--primary)}
.emp-email{font-size:12.5px;color:var(--gray);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.emp-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.emp-actions{display:flex;gap:8px;margin-top:auto}
.emp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
.status-active{background:rgba(5,150,105,.1);color:#059669}
.status-inactive{background:rgba(225,29,72,.1);color:#e11d48}

/* Add modal */
.modal-sm{max-width:500px}
</style>

<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span>Employee Management</span>
    </div>
    <h1 class="page-title">Employee Management</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px"><?= number_format($total) ?> employees in system</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addEmpModal').classList.add('open')">
      <i class="fas fa-user-plus"></i> Add Employee
    </button>
  </div>
</div>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
  <?php
  try {
    $totalEmp    = (int)dbFetchValue("SELECT COUNT(*) FROM users");
    $activeEmp   = (int)dbFetchValue("SELECT COUNT(*) FROM users WHERE is_active=1");
    $inactiveEmp = (int)dbFetchValue("SELECT COUNT(*) FROM users WHERE is_active=0");
    $adminCount  = (int)dbFetchValue("SELECT COUNT(*) FROM users WHERE role='admin' OR role='superadmin'");
  } catch(Exception $e) { $totalEmp=$activeEmp=$inactiveEmp=$adminCount=0; }
  $empStats = [
    ['Total Staff',   $totalEmp,    'fa-id-badge',   '#7c3aed', 'rgba(124,58,237,.1)','rgba(124,58,237,.07)'],
    ['Active',        $activeEmp,   'fa-check-circle','#059669', 'rgba(5,150,105,.1)', 'rgba(5,150,105,.07)'],
    ['Inactive',      $inactiveEmp, 'fa-lock',        '#e11d48', 'rgba(225,29,72,.1)', 'rgba(225,29,72,.07)'],
    ['Admins',        $adminCount,  'fa-user-shield', '#d97706', 'rgba(217,119,6,.1)', 'rgba(217,119,6,.07)'],
  ];
  foreach ($empStats as [$lbl,$val,$ic,$fg,$bg,$sc]): ?>
  <div class="stat-card" style="--sc:<?= $sc ?>">
    <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $fg ?>;box-shadow:none">
      <i class="fas <?= $ic ?>" style="font-size:18px"></i>
    </div>
    <div class="stat-info">
      <div class="s-label"><?= $lbl ?></div>
      <div class="s-value" style="font-size:22px"><?= number_format($val) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <div class="search-wrap" style="flex:1;min-width:200px">
    <i class="fas fa-search"></i>
    <input type="text" name="search" class="search-input" placeholder="Search employees…" value="<?= e($search) ?>">
  </div>
  <select name="role" class="form-control" style="width:auto">
    <option value="">All Roles</option>
    <?php foreach ($roles as $r): ?>
    <option value="<?= $r ?>" <?= $role===$r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($depts): ?>
  <select name="dept" class="form-control" style="width:auto">
    <option value="">All Departments</option>
    <?php foreach ($depts as $d): ?>
    <option value="<?= e($d['department']) ?>" <?= $dept===$d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
  <?php if ($search||$role||$dept): ?>
  <a href="<?= ADMIN_URL ?>/pages/employees.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
  <?php endif; ?>
</form>

<!-- View toggle: Table vs Cards -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
  <div style="font-size:13px;color:var(--gray)"><?= number_format(count($employees)) ?> employees shown</div>
  <div style="display:flex;gap:6px">
    <button onclick="setView('grid')" id="btnGrid" class="btn btn-secondary btn-sm btn-icon active" title="Card view"><i class="fas fa-th-large"></i></button>
    <button onclick="setView('table')" id="btnTable" class="btn btn-secondary btn-sm btn-icon" title="Table view"><i class="fas fa-table"></i></button>
  </div>
</div>

<!-- CARD VIEW -->
<div id="viewGrid">
  <?php if ($employees): ?>
  <div class="emp-grid">
    <?php foreach ($employees as $idx => $emp):
      $isActive = (bool)($emp['is_active'] ?? 1);
      $color = $avatarColors[$idx % count($avatarColors)];
      $r = $emp['role'] ?? 'staff';
    ?>
    <div class="emp-card">
      <div class="emp-top">
        <div class="emp-avatar" style="background:<?= $color ?>">
          <?php if (!empty($emp['avatar'])): ?>
            <img src="<?= getImageUrl($emp['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:14px" alt="">
          <?php else: ?>
            <?= strtoupper(substr($emp['name'],0,1)) ?>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
          <div class="emp-name"><?= e($emp['name']) ?></div>
          <div class="emp-email"><?= e($emp['email']) ?></div>
        </div>
        <span class="status-pill <?= $isActive ? 'status-active' : 'status-inactive' ?>">
          <i class="fas fa-<?= $isActive ? 'check' : 'lock' ?>" style="font-size:9px"></i>
          <?= $isActive ? 'Active' : 'Disabled' ?>
        </span>
      </div>
      <div class="emp-meta">
        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $roleBg[$r] ?? 'rgba(107,114,128,.1)' ?>;color:<?= $roleColors[$r] ?? '#6b7280' ?>">
          <i class="fas fa-shield-alt" style="font-size:10px"></i>
          <?= ucfirst($r) ?>
        </span>
        <?php if (!empty($emp['department'])): ?>
        <span class="chip"><i class="fas fa-building"></i> <?= e($emp['department']) ?></span>
        <?php endif; ?>
        <span class="chip"><i class="fas fa-calendar"></i> Joined <?= date('M Y', strtotime($emp['created_at'] ?? 'now')) ?></span>
      </div>
      <div class="emp-actions">
        <!-- Toggle access -->
        <form method="POST" style="flex:1">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="toggle_access">
          <input type="hidden" name="id" value="<?= $emp['id'] ?>">
          <input type="hidden" name="enable" value="<?= $isActive ? '0' : '1' ?>">
          <button type="submit"
            class="btn btn-sm <?= $isActive ? 'btn-warning' : 'btn-success' ?>"
            style="width:100%;justify-content:center"
            onclick="return confirm('<?= $isActive ? 'Disable' : 'Enable' ?> login access for <?= e(addslashes($emp['name'])) ?>?')"
            <?= $emp['id'] === ($admin['id'] ?? 0) ? 'disabled title="Cannot modify yourself"' : '' ?>>
            <i class="fas fa-<?= $isActive ? 'lock' : 'unlock' ?>"></i>
            <?= $isActive ? 'Disable Access' : 'Enable Access' ?>
          </button>
        </form>
        <!-- Role change -->
        <div class="action-menu-wrap">
          <button type="button" class="btn btn-secondary btn-sm btn-icon" data-toggle-menu="emp-menu-<?= $emp['id'] ?>">
            <i class="fas fa-ellipsis-v"></i>
          </button>
          <div class="action-menu" id="emp-menu-<?= $emp['id'] ?>">
            <div style="padding:6px 12px;font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Change Role</div>
            <?php foreach ($roles as $ro): ?>
            <button type="button" onclick="changeRole(<?= $emp['id'] ?>,'<?= $ro ?>')"
              class="action-menu-item <?= $r===$ro ? 'active' : '' ?>"
              style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit<?= $r===$ro ? ';color:var(--violet)' : '' ?>">
              <span style="width:8px;height:8px;border-radius:50%;background:<?= $roleColors[$ro] ?? '#6b7280' ?>"></span>
              <?= ucfirst($ro) ?> <?= $r===$ro ? '✓' : '' ?>
            </button>
            <?php endforeach; ?>
            <div class="action-menu-divider"></div>
            <a href="<?= ADMIN_URL ?>/pages/login-history.php?user=<?= $emp['id'] ?>" class="action-menu-item">
              <i class="fas fa-history"></i> Login History
            </a>
            <div class="action-menu-divider"></div>
            <?php if ($emp['id'] !== ($admin['id'] ?? 0)): ?>
            <button type="button" onclick="deleteEmployee(<?= $emp['id'] ?>, '<?= e(addslashes($emp['name'])) ?>')" class="action-menu-item danger" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit">
              <i class="fas fa-trash"></i> Remove Employee
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-users"></i></div>
      <h3>No employees found</h3>
      <p><?= ($search||$role||$dept) ? 'Try adjusting your filters.' : 'Add your first team member to get started.' ?></p>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addEmpModal').classList.add('open')">
        <i class="fas fa-user-plus"></i> Add Employee
      </button>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- TABLE VIEW (hidden by default) -->
<div id="viewTable" style="display:none">
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Employee</th><th>Role</th><th>Department</th><th>Status</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($employees): ?>
          <?php foreach ($employees as $idx => $emp):
            $isActive = (bool)($emp['is_active'] ?? 1);
            $color = $avatarColors[$idx % count($avatarColors)];
            $r = $emp['role'] ?? 'staff';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:10px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0">
                  <?= strtoupper(substr($emp['name'],0,1)) ?>
                </div>
                <div>
                  <strong style="font-size:13.5px"><?= e($emp['name']) ?></strong>
                  <div style="font-size:12px;color:var(--gray)"><?= e($emp['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $roleBg[$r] ?? 'rgba(107,114,128,.1)' ?>;color:<?= $roleColors[$r] ?? '#6b7280' ?>">
                <?= ucfirst($r) ?>
              </span>
            </td>
            <td style="font-size:13px;color:var(--gray)"><?= e($emp['department'] ?? '—') ?></td>
            <td>
              <span class="status-pill <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                <i class="fas fa-<?= $isActive ? 'check-circle' : 'lock' ?>" style="font-size:9px"></i>
                <?= $isActive ? 'Active' : 'Disabled' ?>
              </span>
            </td>
            <td style="font-size:12.5px;color:var(--gray)"><?= date('M j, Y', strtotime($emp['created_at'] ?? 'now')) ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle_access">
                  <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                  <input type="hidden" name="enable" value="<?= $isActive ? '0' : '1' ?>">
                  <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-warning' : 'btn-success' ?> btn-icon"
                    title="<?= $isActive ? 'Disable' : 'Enable' ?> access"
                    onclick="return confirm('<?= $isActive ? 'Disable' : 'Enable' ?> this user?')"
                    <?= $emp['id'] === ($admin['id'] ?? 0) ? 'disabled' : '' ?>>
                    <i class="fas fa-<?= $isActive ? 'lock' : 'unlock' ?>"></i>
                  </button>
                </form>
                <a href="<?= ADMIN_URL ?>/pages/login-history.php?user=<?= $emp['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Login History">
                  <i class="fas fa-history"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-users"></i></div><h3>No employees found</h3></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="modal-backdrop" id="addEmpModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal modal-sm">
    <div class="modal-header">
      <h3><i class="fas fa-user-plus" style="color:var(--violet)"></i> Add New Employee</h3>
      <button class="modal-close" onclick="document.getElementById('addEmpModal').classList.remove('open')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_employee">
      <div class="modal-body">
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Email Address <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select name="role" class="form-control" required>
              <?php foreach($roles as $r): ?>
              <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" class="form-control" placeholder="e.g. Sales, Tech">
          </div>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required minlength="6">
        </div>
        <div class="alert-info" style="font-size:13px;padding:10px 14px">
          <i class="fas fa-info-circle"></i> The employee will be able to log in with this email and password.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addEmpModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Add Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden role change form -->
<form method="POST" id="roleChangeForm" style="display:none">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="update_role">
  <input type="hidden" name="id" id="rcEmpId">
  <input type="hidden" name="role" id="rcRole">
</form>
<!-- Hidden delete form -->
<form method="POST" id="empDeleteForm" style="display:none">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="delete_employee">
  <input type="hidden" name="id" id="edEmpId">
</form>

<script>
function changeRole(id, role) {
  if (!confirm('Change role to "' + role.charAt(0).toUpperCase() + role.slice(1) + '"?')) return;
  document.getElementById('rcEmpId').value  = id;
  document.getElementById('rcRole').value   = role;
  document.getElementById('roleChangeForm').submit();
}
function deleteEmployee(id, name) {
  if (!confirm('Permanently remove ' + name + '? This cannot be undone.')) return;
  document.getElementById('edEmpId').value = id;
  document.getElementById('empDeleteForm').submit();
}
function setView(mode) {
  document.getElementById('viewGrid').style.display  = mode === 'grid'  ? 'block' : 'none';
  document.getElementById('viewTable').style.display = mode === 'table' ? 'block' : 'none';
  document.getElementById('btnGrid').classList.toggle('btn-primary', mode === 'grid');
  document.getElementById('btnGrid').classList.toggle('btn-secondary', mode !== 'grid');
  document.getElementById('btnTable').classList.toggle('btn-primary', mode === 'table');
  document.getElementById('btnTable').classList.toggle('btn-secondary', mode !== 'table');
  localStorage.setItem('ag_emp_view', mode);
}
document.addEventListener('DOMContentLoaded', function() {
  const saved = localStorage.getItem('ag_emp_view');
  if (saved) setView(saved);
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
