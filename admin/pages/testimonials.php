<?php
$adminTitle = 'Testimonials';
$adminPage  = 'testimonials';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    /* Column names, not form field names: the table uses client_name,
       designation and photo. */
    $data = [
      'client_name'  => sanitizeInput($_POST['name'] ?? ''),
      'designation'  => sanitizeInput($_POST['position'] ?? ''),
      'company'      => sanitizeInput($_POST['company'] ?? ''),
      'project_type' => sanitizeInput($_POST['project_type'] ?? ''),
      'content'      => sanitizeInput($_POST['content'] ?? ''),
      'rating'       => min(5, max(1, (int)($_POST['rating'] ?? 5))),
      'sort_order'   => (int)($_POST['sort_order'] ?? 0),
      'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
      'is_active'    => isset($_POST['is_active']) ? 1 : 0,
    ];
    $currentAvatar = null;
    if ($postAction === 'update') {
      $currentAvatar = dbFetchValue("SELECT photo FROM testimonials WHERE id = ?", [(int)$_POST['edit_id']]);
    }
    if (!empty($_FILES['avatar']['name'])) {
      $upload = uploadFile($_FILES['avatar'], 'testimonials');
      if ($upload['success']) {
        if ($currentAvatar) deleteFile($currentAvatar);
        $data['photo'] = $upload['path'];
      } else { setFlash('error', $upload['error']); redirect(ADMIN_URL . '/pages/testimonials.php'); }
    }
    if ($postAction === 'create') {
      dbInsertRow('testimonials', $data);
      logActivity('create', 'testimonial', "Added testimonial from {$data['client_name']}");
      setFlash('success', 'Testimonial added!');
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('testimonials', $data, 'id = ?', [$id]);
      logActivity('update', 'testimonial', "Updated testimonial #{$id}");
      setFlash('success', 'Testimonial updated!');
    }
    redirect(ADMIN_URL . '/pages/testimonials.php');
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $t  = dbFetchOne("SELECT photo FROM testimonials WHERE id = ?", [$id]);
    if ($t) {
      if (!empty($t['photo'])) deleteFile($t['photo']);
      dbExecute("DELETE FROM testimonials WHERE id = ?", [$id]);
      logActivity('delete', 'testimonial', "Deleted testimonial #{$id}");
      setFlash('success', 'Testimonial deleted.');
    }
    redirect(ADMIN_URL . '/pages/testimonials.php');
  }
}

if (in_array($action, ['add','edit'])) {
  $item = $editId ? dbFetchOne("SELECT * FROM testimonials WHERE id = ?", [$editId]) : null;
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add Testimonial' : 'Edit Testimonial' ?></h1></div>
  <a href="<?= ADMIN_URL ?>/pages/testimonials.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Testimonials</a>
</div>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">
    <div class="card">
      <div class="card-header"><h3>Testimonial Details</h3></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group"><label>Name <span class="required">*</span></label><input type="text" name="name" class="form-control" value="<?= e($item['client_name'] ?? '') ?>" required></div>
          <div class="form-group"><label>Position</label><input type="text" name="position" class="form-control" value="<?= e($item['designation'] ?? '') ?>" placeholder="CEO, Developer…"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Company</label><input type="text" name="company" class="form-control" value="<?= e($item['company'] ?? '') ?>"></div>
          <div class="form-group">
            <label>Rating (1–5)</label>
            <select name="rating" class="form-control">
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= ($item['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> ★</option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Project Type</label><input type="text" name="project_type" class="form-control" value="<?= e($item['project_type'] ?? '') ?>" placeholder="Mobile App, ERP, Website…"><div class="form-hint">Shown with the quote on the site.</div></div>
        <div class="form-group"><label>Testimonial Content <span class="required">*</span></label><textarea name="content" class="form-control" rows="5" required><?= e($item['content'] ?? '') ?></textarea></div>
      </div>
    </div>
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Options</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $item['sort_order'] ?? 0 ?>"></div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Active</label><label class="toggle-switch"><input type="checkbox" name="is_active" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>><span class="toggle-slider"></span></label></div>
          <?php /* is_featured is a real column the form never covered. */ ?>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Featured</label><label class="toggle-switch"><input type="checkbox" name="is_featured" <?= ($item['is_featured'] ?? 0) ? 'checked' : '' ?>><span class="toggle-slider"></span></label></div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px"><i class="fas fa-save"></i> Save</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Photo</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($item['photo'])): ?>
          <img src="<?= getImageUrl($item['photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:12px">
          <?php endif; ?>
          <input type="file" name="avatar" class="form-control" accept="image/*" onchange="previewImage(this,'avImg')">
          <img id="avImg" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

$items = dbFetchAll("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC");
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Testimonials</h1><p><?= count($items) ?> testimonials</p></div>
  <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Testimonial</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Photo</th><th>Name</th><th>Position / Company</th><th>Rating</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($items): foreach ($items as $t):
          /* Normalise column names (DB uses client_name / designation / photo) */
          $_tName  = $t['client_name'] ?? $t['name']        ?? '';
          $_tDesig = $t['designation'] ?? $t['position']    ?? '';
          $_tPhoto = $t['photo']       ?? $t['avatar']      ?? '';
        ?>
        <tr>
          <td><?php if ($_tPhoto): ?><img src="<?= getImageUrl($_tPhoto) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover"><?php else: ?><div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700"><?= strtoupper(substr($_tName ?: 'C',0,1)) ?></div><?php endif; ?></td>
          <td><strong><?= e($_tName) ?></strong><div style="font-size:12px;color:var(--gray);margin-top:2px"><?= e(truncate($t['content'] ?? '',60)) ?></div></td>
          <td style="font-size:13px"><?= e($_tDesig ? $_tDesig.($t['company']?' @ '.$t['company']:'') : ($t['company'] ?: '—')) ?></td>
          <td style="color:#f59e0b"><?= str_repeat('★', (int)$t['rating']) ?><span style="color:var(--gray)"><?= str_repeat('☆', 5-(int)$t['rating']) ?></span></td>
          <td><span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $t['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td><div style="display:flex;gap:6px">
            <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-quote-left" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No testimonials yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
