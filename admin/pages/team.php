<?php
$adminTitle = 'Team Members';
$adminPage  = 'team';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    $data = [
      'name'        => sanitizeInput($_POST['name'] ?? ''),
      'designation' => sanitizeInput($_POST['designation'] ?? ''),
      'department'  => sanitizeInput($_POST['department'] ?? ''),
      'bio'         => sanitizeInput($_POST['bio'] ?? ''),
      'email'       => sanitizeInput($_POST['email'] ?? ''),
      'linkedin'    => sanitizeInput($_POST['linkedin'] ?? ''),
      'twitter'     => sanitizeInput($_POST['twitter'] ?? ''),
      'sort_order'  => (int)($_POST['sort_order'] ?? 0),
      'is_active'   => isset($_POST['is_active']) ? 1 : 0,
    ];
    $currentPhoto = null;
    if ($postAction === 'update') {
      $currentPhoto = dbFetchValue("SELECT photo FROM team_members WHERE id = ?", [(int)$_POST['edit_id']]);
    }
    if (!empty($_FILES['photo']['name'])) {
      $upload = uploadFile($_FILES['photo'], 'team');
      if ($upload['success']) {
        if ($currentPhoto) deleteFile($currentPhoto);
        $data['photo'] = $upload['path'];
      } else { setFlash('error', $upload['error']); redirect(ADMIN_URL . '/pages/team.php'); }
    }
    if ($postAction === 'create') {
      dbInsertRow('team_members', $data);
      logActivity('create', 'team', "Added team member: {$data['name']}");
      setFlash('success', 'Team member added!');
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('team_members', $data, 'id = ?', [$id]);
      logActivity('update', 'team', "Updated team member #{$id}");
      setFlash('success', 'Team member updated!');
    }
    redirect(ADMIN_URL . '/pages/team.php');
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $m  = dbFetchOne("SELECT photo FROM team_members WHERE id = ?", [$id]);
    if ($m) {
      if ($m['photo']) deleteFile($m['photo']);
      dbExecute("DELETE FROM team_members WHERE id = ?", [$id]);
      logActivity('delete', 'team', "Deleted team member #{$id}");
      setFlash('success', 'Team member deleted.');
    }
    redirect(ADMIN_URL . '/pages/team.php');
  }
}

if (in_array($action, ['add','edit'])) {
  $item = $editId ? dbFetchOne("SELECT * FROM team_members WHERE id = ?", [$editId]) : null;
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add Team Member' : 'Edit Team Member' ?></h1></div>
  <a href="<?= ADMIN_URL ?>/pages/team.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Members</a>
</div>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">
    <div class="card">
      <div class="card-header"><h3>Member Details</h3></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group"><label>Name <span class="required">*</span></label><input type="text" name="name" class="form-control" value="<?= e($item['name'] ?? '') ?>" required></div>
          <div class="form-group"><label>Designation <span class="required">*</span></label><input type="text" name="designation" class="form-control" value="<?= e($item['designation'] ?? $item['position'] ?? '') ?>" required placeholder="Senior Developer"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" value="<?= e($item['department'] ?? '') ?>" placeholder="Engineering"></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($item['email'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Bio</label><textarea name="bio" class="form-control" rows="4"><?= e($item['bio'] ?? '') ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label><i class="fab fa-linkedin-in" style="color:#0077b5"></i> LinkedIn</label><input type="url" name="linkedin" class="form-control" value="<?= e($item['linkedin'] ?? '') ?>"></div>
          <div class="form-group"><label><i class="fab fa-twitter" style="color:#1da1f2"></i> Twitter</label><input type="url" name="twitter" class="form-control" value="<?= e($item['twitter'] ?? '') ?>"></div>
        </div>
      </div>
    </div>
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Options</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $item['sort_order'] ?? 0 ?>"></div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Active</label><label class="toggle-switch"><input type="checkbox" name="is_active" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>><span class="toggle-slider"></span></label></div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px"><i class="fas fa-save"></i> Save</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Photo</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($item['photo'])): ?>
          <img src="<?= UPLOADS_URL . '/' . e($item['photo']) ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-bottom:12px">
          <?php endif; ?>
          <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewImage(this,'teamImg')">
          <img id="teamImg" style="display:none;width:100px;height:100px;border-radius:50%;object-fit:cover;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

$members = dbFetchAll("SELECT * FROM team_members ORDER BY sort_order ASC, id ASC");
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Team Members</h1><p><?= count($members) ?> members</p></div>
  <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Member</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Photo</th><th>Name</th><th>Position</th><th>Department</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($members): foreach ($members as $m): ?>
        <tr>
          <td><?php if ($m['photo']): ?><img src="<?= UPLOADS_URL . '/' . e($m['photo']) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover"><?php else: ?><div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700"><?= strtoupper(substr($m['name'],0,1)) ?></div><?php endif; ?></td>
          <td><strong><?= e($m['name']) ?></strong><?php if ($m['email']): ?><div style="font-size:12px;color:var(--gray)"><?= e($m['email']) ?></div><?php endif; ?></td>
          <td style="font-size:13px"><?= e($m['designation'] ?? $m['position'] ?? '—') ?></td>
          <td style="font-size:13px"><?= e($m['department'] ?: '—') ?></td>
          <td><span class="badge <?= $m['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $m['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td><div style="display:flex;gap:6px">
            <a href="?action=edit&id=<?= $m['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-users" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No team members yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
