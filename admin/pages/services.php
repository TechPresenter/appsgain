<?php
$adminTitle = 'Services';
$adminPage  = 'services';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    $features = array_values(array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))));
    $svcGroup = sanitizeInput($_POST['service_group'] ?? '');
    $data     = [
      'name'              => sanitizeInput($_POST['title'] ?? ''),
      'slug'              => slugify($_POST['slug'] ?? $_POST['title'] ?? ''),
      'subtitle'          => sanitizeInput($_POST['subtitle'] ?? ''),
      'excerpt'           => sanitizeInput($_POST['excerpt'] ?? ''),
      'description'       => $_POST['description'] ?? '',
      'icon'              => sanitizeInput($_POST['icon'] ?? ''),
      'color'             => sanitizeInput($_POST['color'] ?? '#7c3aed'),
      'category'          => $svcGroup,
      'features'          => json_encode($features),
      'sort_order'        => (int)($_POST['sort_order'] ?? 0),
      'is_active'         => isset($_POST['is_active']) ? 1 : 0,
      'meta_title'        => sanitizeInput($_POST['meta_title'] ?? ''),
      'meta_description'  => sanitizeInput($_POST['meta_desc'] ?? ''),
    ];
    if (!$data['name']) { setFlash('error', 'Title required.'); redirect(ADMIN_URL . '/pages/services.php'); }
    $currentImage = null;
    if ($postAction === 'update') {
      $currentImage = dbFetchValue("SELECT image FROM services WHERE id = ?", [(int)$_POST['edit_id']]);
    }
    if (!empty($_FILES['image']['name'])) {
      $upload = uploadFile($_FILES['image'], 'services');
      if ($upload['success']) {
        if ($currentImage) deleteFile($currentImage);
        $data['image'] = $upload['path'];
      } else { setFlash('error', $upload['error']); redirect(ADMIN_URL . '/pages/services.php'); }
    }
    // New columns exist only after migration; strip them gracefully if not yet migrated
    $newCols = ['subtitle','excerpt','icon','color'];
    $saveData = $data;
    $doSave = function(string $action, int $id = 0) use (&$saveData, &$newCols, &$data) {
      try {
        if ($action === 'create') return dbInsertRow('services', $saveData);
        dbUpdateRow('services', $saveData, 'id = ?', [$id]); return $id;
      } catch (PDOException $ex) {
        if (strpos($ex->getMessage(), 'Unknown column') !== false) {
          foreach ($newCols as $c) unset($saveData[$c]);
          if ($action === 'create') return dbInsertRow('services', $saveData);
          dbUpdateRow('services', $saveData, 'id = ?', [$id]); return $id;
        }
        throw $ex;
      }
    };
    if ($postAction === 'create') {
      $id = $doSave('create');
      logActivity('create', 'service', "Created service: {$data['name']}");
      setFlash('success', 'Service created!');
      redirect(ADMIN_URL . '/pages/services.php?action=edit&id=' . $id);
    } else {
      $id = (int)$_POST['edit_id'];
      $doSave('update', $id);
      logActivity('update', 'service', "Updated service #{$id}");
      setFlash('success', 'Service updated!');
      redirect(ADMIN_URL . '/pages/services.php?action=edit&id=' . $id);
    }
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $s  = dbFetchOne("SELECT name, image FROM services WHERE id = ?", [$id]);
    if ($s) {
      if ($s['image']) deleteFile($s['image']);
      dbExecute("DELETE FROM services WHERE id = ?", [$id]);
      logActivity('delete', 'service', "Deleted service: {$s['name']}");
      setFlash('success', 'Service deleted.');
    }
    redirect(ADMIN_URL . '/pages/services.php');
  }
}

if (in_array($action, ['add','edit'])) {
  $svc      = $editId ? dbFetchOne("SELECT * FROM services WHERE id = ?", [$editId]) : null;
  $features = $svc ? implode("\n", json_decode($svc['features'] ?? '[]', true) ?: []) : '';
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add Service' : 'Edit: '.e($svc['name']??'') ?></h1></div>
  <div style="display:flex;gap:10px">
    <?php if ($editId): ?><a href="<?= SITE_URL ?>/service/<?= e($svc['slug']??'') ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> View</a><?php endif; ?>
    <a href="<?= ADMIN_URL ?>/pages/services.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Services</a>
  </div>
</div>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Service Details</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Title <span class="required">*</span></label><input type="text" name="title" class="form-control" value="<?= e($svc['name']??'') ?>" required oninput="autoSlug(this)"></div>
          <div class="form-group"><label>Slug</label><div class="input-group"><input type="text" name="slug" id="slugField" class="form-control" value="<?= e($svc['slug']??'') ?>"><button type="button" class="btn btn-secondary" onclick="regenerateSlug()"><i class="fas fa-sync"></i></button></div></div>
          <div class="form-group"><label>Subtitle / Tagline</label><input type="text" name="subtitle" class="form-control" value="<?= e($svc['subtitle'] ?? $svc['short_description'] ?? '') ?>"></div>
          <div class="form-group"><label>Short Excerpt</label><textarea name="excerpt" class="form-control" rows="2"><?= e($svc['excerpt'] ?? '') ?></textarea></div>
          <div class="form-group"><label>Full Description</label><textarea name="description" id="svcDescription" class="form-control" rows="10"><?= htmlspecialchars($svc['description']??'', ENT_NOQUOTES, 'UTF-8') ?></textarea></div>
          <div class="form-group">
            <label>Key Features (one per line)</label>
            <textarea name="features" class="form-control" rows="6" placeholder="Responsive Design&#10;Cross-platform Support&#10;..."><?= e($features) ?></textarea>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>SEO</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= e($svc['meta_title']??'') ?>"></div>
          <div class="form-group"><label>Meta Description</label><textarea name="meta_desc" class="form-control" rows="2"><?= e($svc['meta_description']??'') ?></textarea></div>
        </div>
      </div>
    </div>
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Options</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Service Group / Category</label>
            <input type="text" name="service_group" class="form-control" value="<?= e($svc['service_group']??$svc['category']??'') ?>" placeholder="e.g. Development, Technology, Design & Growth">
            <div class="form-hint"><i class="fas fa-info-circle"></i> Used to group services in navigation dropdown</div>
          </div>
          <div class="form-group">
            <label>Icon Class (Font Awesome)</label>
            <input type="text" name="icon" class="form-control" value="<?= e($svc['icon']??$svc['icon_class']??'fa-cog') ?>" placeholder="fa-cog">
            <div class="form-hint"><i class="fas fa-info-circle"></i> e.g. fa-mobile-alt, fa-code, fa-robot</div>
          </div>
          <div class="form-group"><label>Accent Color</label><div style="display:flex;gap:8px;align-items:center"><input type="color" name="color" value="<?= e($svc['color']??'#7c3aed') ?>" style="width:44px;height:36px;border:none;border-radius:8px;cursor:pointer"><input type="text" id="colorHex" class="form-control" value="<?= e($svc['color']??'#7c3aed') ?>" style="flex:1" oninput="document.querySelector('input[name=color]').value=this.value"></div></div>
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $svc['sort_order']??0 ?>"></div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Active</label><label class="toggle-switch"><input type="checkbox" name="is_active" <?= ($svc['is_active']??1)?'checked':'' ?>><span class="toggle-slider"></span></label></div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px"><i class="fas fa-save"></i> Save Service</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Image</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($svc['image'])): ?>
          <img src="<?= UPLOADS_URL . '/' . e($svc['image']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:12px">
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this,'svcImg')">
          <img id="svcImg" style="display:none;width:100%;height:140px;object-fit:cover;border-radius:10px;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>
<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('svcDescription', { height: 380, placeholder: 'Write the full service description here…' });
});
function autoSlug(i){ var s=i.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-'); document.getElementById('slugField').value=s; }
function regenerateSlug(){ autoSlug(document.querySelector('input[name="title"]')); }
var colorInput = document.querySelector('input[name="color"]');
if (colorInput) colorInput.addEventListener('input', function(){ document.getElementById('colorHex').value = this.value; });
</script>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

$services = dbFetchAll("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Services</h1><p><?= count($services) ?> services</p></div>
  <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Service</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Icon</th><th>Title</th><th>Slug</th><th>Features</th><th>Active</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($services): foreach ($services as $s): ?>
        <tr>
          <td><div style="width:40px;height:40px;border-radius:10px;background:<?= e($s['color']??$s['accent_color']??'#7c3aed') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px"><i class="fas <?= e($s['icon']??$s['icon_class']??'fa-cog') ?>"></i></div></td>
          <td><strong><?= e($s['name']) ?></strong><?php if (!empty($s['subtitle']??$s['short_description']??'')): ?><div style="font-size:12px;color:var(--gray)"><?= e(truncate($s['subtitle']??$s['short_description']??'',45)) ?></div><?php endif; ?></td>
          <td style="font-family:monospace;font-size:12px;color:var(--gray)"><?= e($s['slug']) ?></td>
          <td style="font-size:12px"><?= count(json_decode($s['features']??'[]',true)?:[]) ?> features</td>
          <td><span class="badge <?= $s['is_active']?'badge-success':'badge-secondary' ?>"><?= $s['is_active']?'Active':'Hidden' ?></span></td>
          <td style="font-size:13px;text-align:center"><?= $s['sort_order'] ?></td>
          <td><div style="display:flex;gap:6px">
            <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
            <a href="<?= SITE_URL ?>/service/<?= e($s['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-external-link-alt"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-cogs" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No services yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
