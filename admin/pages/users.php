<?php
$adminTitle = 'Users & Roles';
$adminPage  = 'users';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireRole('superadmin');

/* ── POST Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (in_array($postAction, ['create', 'update'])) {
        $name  = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $role  = in_array($_POST['role'] ?? '', ['superadmin','admin','editor','manager','hr','counselor','staff']) ? $_POST['role'] : 'editor';
        if (!$name || !$email) { setFlash('error', 'Name and email are required.'); redirect(ADMIN_URL . '/pages/users.php'); }

        if ($postAction === 'create') {
            $pass = $_POST['password'] ?? '';
            if (strlen($pass) < 8) { setFlash('error', 'Password must be at least 8 characters.'); redirect(ADMIN_URL . '/pages/users.php?modal=add'); }
            $exists = dbFetchValue("SELECT id FROM users WHERE email = ?", [$email]);
            if ($exists) { setFlash('error', 'Email already in use.'); redirect(ADMIN_URL . '/pages/users.php?modal=add'); }
            dbInsertRow('users', ['name' => $name, 'email' => $email, 'password' => Auth::hashPassword($pass), 'role' => $role]);
            logActivity('create', 'user', "Created user: {$email} ({$role})");
            setFlash('success', "User {$name} created successfully!");
        } else {
            $id     = (int)$_POST['edit_id'];
            $exists = dbFetchValue("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
            if ($exists) { setFlash('error', 'Email already in use.'); redirect(ADMIN_URL . '/pages/users.php'); }
            $upd = ['name' => $name, 'email' => $email, 'role' => $role];
            if (!empty($_POST['password']) && strlen($_POST['password']) >= 8) {
                $upd['password'] = Auth::hashPassword($_POST['password']);
            }
            dbUpdateRow('users', $upd, 'id = ?', [$id]);
            logActivity('update', 'user', "Updated user #{$id}: {$email}");
            setFlash('success', 'User updated!');
        }
        redirect(ADMIN_URL . '/pages/users.php');
    }

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === $admin['id']) { setFlash('error', 'You cannot delete your own account.'); redirect(ADMIN_URL . '/pages/users.php'); }
        $u = dbFetchOne("SELECT name, email FROM users WHERE id = ?", [$id]);
        dbExecute("DELETE FROM users WHERE id = ?", [$id]);
        logActivity('delete', 'user', "Deleted user: " . ($u['email'] ?? $id));
        setFlash('success', 'User deleted.');
        redirect(ADMIN_URL . '/pages/users.php');
    }

    if ($postAction === 'toggle_status') {
        $id = (int)$_POST['id'];
        if ($id !== $admin['id']) {
            $cur = dbFetchValue("SELECT is_active FROM users WHERE id = ?", [$id]);
            dbExecute("UPDATE users SET is_active = ? WHERE id = ?", [$cur ? 0 : 1, $id]);
            logActivity('update', 'user', "Toggled user #{$id} status to " . ($cur ? 'inactive' : 'active'));
            setFlash('success', 'User status updated.');
        }
        redirect(ADMIN_URL . '/pages/users.php');
    }
}

$users      = dbFetchAll("SELECT id, name, email, role, is_active, last_login, created_at, avatar FROM users ORDER BY
    CASE role WHEN 'superadmin' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END, name ASC");
$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($u) { return $u['is_active']; }));
$openModal  = sanitizeInput($_GET['modal'] ?? '');

$roleConfig = [
    'superadmin' => ['#e11d48', 'fa-crown',       'Super Admin', 'Full system access'],
    'admin'      => ['#d97706', 'fa-shield-alt',   'Admin',       'Most features'],
    'editor'     => ['#2563eb', 'fa-pen',          'Editor',      'Content only'],
    'manager'    => ['#7c3aed', 'fa-briefcase',    'Manager',     'Management access'],
    'hr'         => ['#059669', 'fa-user-tie',     'HR',          'HR module access'],
    'counselor'  => ['#0891b2', 'fa-headset',      'Counselor',   'Lead counseling'],
    'staff'      => ['#6b7280', 'fa-user',         'Staff',       'Basic access'],
];

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
/* ── User Cards Grid ── */
.users-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.user-card{background:var(--white);border:1.5px solid var(--border);border-radius:18px;overflow:hidden;transition:var(--transition);position:relative}
.user-card:hover{border-color:rgba(124,58,237,.3);box-shadow:var(--shadow-md)}
.user-card.is-you{border-color:rgba(124,58,237,.4);box-shadow:0 0 0 3px rgba(124,58,237,.08)}
.user-card-top{padding:22px 18px 16px;background:linear-gradient(135deg,var(--light),var(--light2));border-bottom:1px solid var(--border);text-align:center;position:relative}
.user-avatar{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:#fff;margin:0 auto 10px;overflow:hidden;border:3px solid var(--white);box-shadow:0 4px 14px rgba(0,0,0,.12)}
.user-avatar img{width:100%;height:100%;object-fit:cover}
.user-name{font-size:15px;font-weight:800;color:var(--primary);margin-bottom:3px}
.user-email{font-size:12px;color:var(--gray);margin-bottom:10px;word-break:break-all}
.user-role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;color:#fff}
.user-you-tag{position:absolute;top:10px;right:10px;background:var(--violet);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px}
.user-card-body{padding:14px 16px}
.user-stat-row{display:flex;gap:0}
.user-stat{flex:1;text-align:center;padding:8px 0;border-right:1px solid var(--border)}
.user-stat:last-child{border-right:none}
.user-stat-val{font-size:13.5px;font-weight:700;color:var(--primary)}
.user-stat-lbl{font-size:11px;color:var(--gray);margin-top:2px}
.user-card-footer{display:flex;gap:6px;padding:10px 12px;border-top:1px solid var(--border)}
.user-status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* ── Role Info Panel ── */
.roles-table{width:100%;border-collapse:collapse}
.roles-table td,.roles-table th{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
.roles-table th{font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--gray);background:var(--light)}
.roles-table tr:last-child td{border:none}

/* ── Modals ── */
.user-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:600;align-items:center;justify-content:center;padding:20px}
.user-modal.open{display:flex}
.user-modal-box{background:var(--white);border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg)}
.user-modal-header{padding:20px 22px 0;display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.user-modal-header h3{font-size:17px;font-weight:700}
.user-modal-body{padding:0 22px 22px}
.modal-close-btn{width:32px;height:32px;border-radius:8px;border:none;background:var(--light);cursor:pointer;color:var(--gray);font-size:14px}
</style>

<div class="page-header">
  <div>
    <h1>Users & Roles</h1>
    <p><?= $activeUsers ?> active · <?= $totalUsers ?> total admin users</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="openUserModal('addModal')">
    <i class="fas fa-user-plus"></i> Add User
  </button>
</div>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
  <?php
  $roleCounts = [];
  foreach ($users as $u) $roleCounts[$u['role']] = ($roleCounts[$u['role']] ?? 0) + 1;
  $superCount   = $roleCounts['superadmin'] ?? 0;
  $adminCount   = $roleCounts['admin']      ?? 0;
  $editorCount  = $roleCounts['editor']     ?? 0;
  $othersCount  = $totalUsers - $superCount - $adminCount - $editorCount;
  ?>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--blue));width:42px;height:42px;font-size:16px"><i class="fas fa-users"></i></div>
    <div><div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Total</div><div style="font-size:22px;font-weight:800;color:var(--primary)"><?= $totalUsers ?></div></div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,#e11d48,#be123c);width:42px;height:42px;font-size:16px"><i class="fas fa-crown"></i></div>
    <div><div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Super Admins</div><div style="font-size:22px;font-weight:800;color:var(--primary)"><?= $superCount ?></div></div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--emerald),#047857);width:42px;height:42px;font-size:16px"><i class="fas fa-check-circle"></i></div>
    <div><div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Active</div><div style="font-size:22px;font-weight:800;color:var(--primary)"><?= $activeUsers ?></div></div>
  </div>
  <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--amber),#b45309);width:42px;height:42px;font-size:16px"><i class="fas fa-user-shield"></i></div>
    <div><div style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase">Admins</div><div style="font-size:22px;font-weight:800;color:var(--primary)"><?= $adminCount ?></div></div>
  </div>
</div>

<?php if (empty($users)): ?>
<div class="card" style="text-align:center;padding:60px 20px">
  <i class="fas fa-users" style="font-size:40px;color:var(--gray2);opacity:.4;display:block;margin-bottom:14px"></i>
  <h3>No users found</h3>
</div>
<?php else: ?>
<div class="users-grid">
  <?php foreach ($users as $u):
    $isYou = $u['id'] === $admin['id'];
    [$rc, $ri, $rl, $rd] = $roleConfig[$u['role']] ?? ['#6b7280','fa-user','Staff','Basic access'];
    // Avatar gradient colors per role
    $gradients = ['superadmin'=>'#e11d48,#be123c','admin'=>'#d97706,#b45309','editor'=>'#2563eb,#1d4ed8','manager'=>'#7c3aed,#5b21b6','hr'=>'#059669,#047857','counselor'=>'#0891b2,#0e7490','staff'=>'#6b7280,#4b5563'];
    $grad = $gradients[$u['role']] ?? '124,58,237,37,99,235';
  ?>
  <div class="user-card <?= $isYou ? 'is-you' : '' ?>">
    <?php if ($isYou): ?>
    <span class="user-you-tag"><i class="fas fa-user" style="font-size:8px"></i> You</span>
    <?php endif; ?>
    <div class="user-card-top">
      <div class="user-avatar" style="background:linear-gradient(135deg,<?= $grad ?>)">
        <?php if (!empty($u['avatar'])): ?>
        <img src="<?= getImageUrl($u['avatar']) ?>" alt="<?= e($u['name']) ?>">
        <?php else: ?>
        <?= strtoupper(substr($u['name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-name"><?= e($u['name']) ?></div>
      <div class="user-email"><?= e($u['email']) ?></div>
      <span class="user-role-badge" style="background:<?= $rc ?>">
        <i class="fas <?= $ri ?>" style="font-size:10px"></i> <?= $rl ?>
      </span>
    </div>
    <div class="user-card-body">
      <div class="user-stat-row">
        <div class="user-stat">
          <div class="user-stat-val">
            <span class="user-status-dot" style="display:inline-block;vertical-align:middle;margin-right:3px;background:<?= $u['is_active'] ? '#059669' : '#9ca3af' ?>"></span>
            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
          </div>
          <div class="user-stat-lbl">Status</div>
        </div>
        <div class="user-stat">
          <div class="user-stat-val"><?= $u['last_login'] ? date('d M', strtotime($u['last_login'])) : '—' ?></div>
          <div class="user-stat-lbl">Last Login</div>
        </div>
        <div class="user-stat">
          <div class="user-stat-val"><?= date('M Y', strtotime($u['created_at'])) ?></div>
          <div class="user-stat-lbl">Joined</div>
        </div>
      </div>
    </div>
    <div class="user-card-footer">
      <button class="btn btn-outline btn-sm" style="flex:1"
              onclick="openEditModal(<?= $u['id'] ?>, '<?= addslashes(e($u['name'])) ?>', '<?= addslashes(e($u['email'])) ?>', '<?= $u['role'] ?>')">
        <i class="fas fa-pen"></i> Edit
      </button>
      <?php if (!$isYou): ?>
      <form method="POST" style="display:inline">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button type="submit" class="btn btn-sm btn-icon <?= $u['is_active'] ? 'btn-warning' : 'btn-secondary' ?>"
                title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
          <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check-circle' ?>"></i>
        </button>
      </form>
      <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= addslashes(e($u['name'])) ?>? This cannot be undone.')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete user">
          <i class="fas fa-trash"></i>
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Role Permissions Reference -->
<div class="card" style="margin-top:28px">
  <div class="card-header">
    <h3><i class="fas fa-shield-alt" style="color:var(--violet);margin-right:8px"></i>Role Permissions Reference</h3>
  </div>
  <div class="card-body" style="padding:0">
    <table class="roles-table">
      <thead>
        <tr>
          <th>Role</th>
          <th>Description</th>
          <th>Content</th>
          <th>Leads</th>
          <th>Users</th>
          <th>Settings</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $perms = [
            'superadmin' => ['Full admin access, manages users and all settings', '✅', '✅', '✅', '✅'],
            'admin'      => ['Manages most features, cannot manage other admins',  '✅', '✅', '✅', '⚠️ Limited'],
            'manager'    => ['Team & operation management, can view reports',       '✅', '✅', '❌', '❌'],
            'hr'         => ['HR module: employees, attendance, payroll',           '❌', '❌', '❌', '❌'],
            'counselor'  => ['Manages assigned leads and student enquiries',        '❌', '✅ Assigned', '❌', '❌'],
            'editor'     => ['Creates and publishes website content only',          '✅', '❌', '❌', '❌'],
            'staff'      => ['Basic read access to assigned modules',               '❌', '❌', '❌', '❌'],
        ];
        foreach ($perms as $role => [$desc, $content, $leads, $users, $settings]):
            [$rc, $ri, $rl] = $roleConfig[$role];
        ?>
        <tr>
          <td>
            <span class="user-role-badge" style="background:<?= $rc ?>">
              <i class="fas <?= $ri ?>" style="font-size:10px"></i> <?= $rl ?>
            </span>
          </td>
          <td style="color:var(--text)"><?= $desc ?></td>
          <td><?= $content ?></td>
          <td><?= $leads ?></td>
          <td><?= $users ?></td>
          <td><?= $settings ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Add User Modal ── -->
<div class="user-modal" id="addModal">
  <div class="user-modal-box">
    <div class="user-modal-header">
      <h3><i class="fas fa-user-plus" style="color:var(--violet);margin-right:8px"></i>Add New User</h3>
      <button class="modal-close-btn" onclick="closeUserModal('addModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" class="user-modal-body">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Email Address <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role <span class="required">*</span></label>
          <select name="role" class="form-control">
            <?php foreach ($roleConfig as $rk => [, , $rl, $rd]): ?>
            <option value="<?= $rk ?>"><?= $rl ?> — <?= $rd ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="form-control" required minlength="8" placeholder="Min. 8 characters">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
        <button type="button" class="btn btn-secondary" onclick="closeUserModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit User Modal ── -->
<div class="user-modal" id="editModal">
  <div class="user-modal-box">
    <div class="user-modal-header">
      <h3><i class="fas fa-user-edit" style="color:var(--violet);margin-right:8px"></i>Edit User</h3>
      <button class="modal-close-btn" onclick="closeUserModal('editModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" class="user-modal-body">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="edit_id" id="editUserId">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="name" id="editUserName" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Email Address <span class="required">*</span></label>
          <input type="email" name="email" id="editUserEmail" class="form-control" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role <span class="required">*</span></label>
          <select name="role" id="editUserRole" class="form-control">
            <?php foreach ($roleConfig as $rk => [, , $rl, $rd]): ?>
            <option value="<?= $rk ?>"><?= $rl ?> — <?= $rd ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>New Password <span style="font-weight:400;color:var(--gray)">(leave blank to keep)</span></label>
          <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        <button type="button" class="btn btn-secondary" onclick="closeUserModal('editModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openUserModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeUserModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

document.querySelectorAll('.user-modal').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('open'); document.body.style.overflow = ''; } });
});

function openEditModal(id, name, email, role) {
  document.getElementById('editUserId').value   = id;
  document.getElementById('editUserName').value  = name;
  document.getElementById('editUserEmail').value = email;
  document.getElementById('editUserRole').value  = role;
  openUserModal('editModal');
}

// Auto-open modal if redirected back with ?modal=add (validation error)
<?php if ($openModal === 'add'): ?>
openUserModal('addModal');
<?php endif; ?>
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
