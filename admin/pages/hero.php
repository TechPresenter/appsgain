<?php
$adminTitle = 'Hero Banners';
$adminPage  = 'hero';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create', 'update'])) {
    $data = [
      'page_key'      => sanitizeInput($_POST['page_key'] ?? 'home'),
      'title'         => sanitizeInput($_POST['title'] ?? ''),
      'subtitle'      => sanitizeInput($_POST['subtitle'] ?? ''),
      'description'   => sanitizeInput($_POST['description'] ?? ''),
      'badge_text'    => sanitizeInput($_POST['badge_text'] ?? ''),
      'cta_text'      => sanitizeInput($_POST['cta_text'] ?? ''),
      'cta_url'       => sanitizeInput($_POST['cta_url'] ?? ''),
      'cta2_text'     => sanitizeInput($_POST['cta2_text'] ?? ''),
      'cta2_url'      => sanitizeInput($_POST['cta2_url'] ?? ''),
      /* index.php renders the btn1 and btn2 columns, so writing only the
         cta ones left the buttons on the site unchanged. Keep both in step. */
      'btn1_text'     => sanitizeInput($_POST['cta_text'] ?? ''),
      'btn1_url'      => sanitizeInput($_POST['cta_url'] ?? ''),
      'btn2_text'     => sanitizeInput($_POST['cta2_text'] ?? ''),
      'btn2_url'      => sanitizeInput($_POST['cta2_url'] ?? ''),
      'sort_order'    => (int)($_POST['sort_order'] ?? 0),
      'is_active'     => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!empty($_FILES['background_image']['name'])) {
      $upload = uploadFile($_FILES['background_image'], 'hero');
      if ($upload['success']) {
        /* The picture being replaced would otherwise sit in uploads/ forever. */
        $prev = $postAction === 'update'
          ? (string)dbFetchValue("SELECT background_image FROM hero_slides WHERE id = ?", [(int)($_POST['edit_id'] ?? 0)])
          : '';
        $data['background_image'] = $upload['path'];
        if ($prev !== '' && $prev !== $upload['path']) {
          $oldFile = rtrim(ROOT_PATH, '/') . '/uploads/' . ltrim($prev, '/');
          if (is_file($oldFile)) @unlink($oldFile);
        }
      } else {
        setFlash('error', $upload['error']);
        redirect(ADMIN_URL . '/pages/hero.php');
      }
    }
    if ($postAction === 'create') {
      dbInsertRow('hero_slides', $data);
      logActivity('create', 'hero', "Created hero slide: {$data['title']}");
      setFlash('success', 'Hero slide created!');
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('hero_slides', $data, 'id = ?', [$id]);
      logActivity('update', 'hero', "Updated hero slide #{$id}");
      setFlash('success', 'Hero slide updated!');
    }
    redirect(ADMIN_URL . '/pages/hero.php');
  }

  if ($postAction === 'delete') {
    $id   = (int)$_POST['id'];
    $hero = dbFetchOne("SELECT background_image FROM hero_slides WHERE id = ?", [$id]);
    if ($hero) {
      if ($hero['background_image']) deleteFile($hero['background_image']);
      dbExecute("DELETE FROM hero_slides WHERE id = ?", [$id]);
      logActivity('delete', 'hero', "Deleted hero slide #{$id}");
      setFlash('success', 'Slide deleted.');
    }
    redirect(ADMIN_URL . '/pages/hero.php');
  }

  if ($postAction === 'toggle') {
    $id  = (int)$_POST['id'];
    $val = (int)$_POST['value'];
    dbExecute("UPDATE hero_slides SET is_active = ? WHERE id = ?", [$val, $id]);
    header('Content-Type:application/json'); echo json_encode(['ok'=>true]); exit;
  }
}

/* ── Migrate hero_slides to ensure all columns exist ── */
try {
    $hCols = array_column(dbFetchAll("SHOW COLUMNS FROM hero_slides"), 'Field');
    $hMigrations = [
        "cta_text"         => "ALTER TABLE hero_slides ADD COLUMN cta_text VARCHAR(200) NOT NULL DEFAULT '' AFTER description",
        "cta_url"          => "ALTER TABLE hero_slides ADD COLUMN cta_url VARCHAR(500) NOT NULL DEFAULT '' AFTER cta_text",
        "cta2_text"        => "ALTER TABLE hero_slides ADD COLUMN cta2_text VARCHAR(200) NOT NULL DEFAULT '' AFTER cta_url",
        "cta2_url"         => "ALTER TABLE hero_slides ADD COLUMN cta2_url VARCHAR(500) NOT NULL DEFAULT '' AFTER cta2_text",
        "background_image" => "ALTER TABLE hero_slides ADD COLUMN background_image VARCHAR(500) DEFAULT NULL AFTER cta2_url",
        "badge_text"       => "ALTER TABLE hero_slides ADD COLUMN badge_text VARCHAR(300) DEFAULT NULL AFTER title",
    ];
    foreach ($hMigrations as $col => $sql) {
        if (!in_array($col, $hCols)) db()->exec($sql);
    }
    /* Copy btn1/btn2 → cta if old columns exist and new are empty */
    if (in_array('btn1_text', $hCols)) {
        db()->exec("UPDATE hero_slides SET cta_text=btn1_text WHERE cta_text='' AND btn1_text!=''");
        db()->exec("UPDATE hero_slides SET cta_url=btn1_url WHERE cta_url='' AND btn1_url!=''");
        db()->exec("UPDATE hero_slides SET cta2_text=btn2_text WHERE cta2_text='' AND btn2_text!=''");
        db()->exec("UPDATE hero_slides SET cta2_url=btn2_url WHERE cta2_url='' AND btn2_url!=''");
    }
    if (in_array('bg_image', $hCols)) {
        db()->exec("UPDATE hero_slides SET background_image=bg_image WHERE background_image IS NULL AND bg_image IS NOT NULL AND bg_image!=''");
    }
} catch(Exception $e) {}

$pageKeys = ['home','about','services','contact','blog','portfolio'];

if (in_array($action, ['add','edit'])) {
  $slide = $editId ? dbFetchOne("SELECT * FROM hero_slides WHERE id = ?", [$editId]) : null;
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add Hero Slide' : 'Edit Hero Slide' ?></h1></div>
  <a href="<?= ADMIN_URL ?>/pages/hero.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Slides</a>
</div>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
    <div class="card">
      <div class="card-header"><h3>Slide Content</h3></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Page <span class="required">*</span></label>
            <select name="page_key" class="form-control">
              <?php foreach ($pageKeys as $k): ?>
              <option value="<?= $k ?>" <?= ($slide['page_key'] ?? 'home') === $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?= $slide['sort_order'] ?? 0 ?>">
          </div>
        </div>
        <div class="form-group"><label>Title <span class="required">*</span></label><input type="text" name="title" class="form-control" value="<?= e($slide['title'] ?? '') ?>" required></div>
        <div class="form-group"><label>Subtitle</label><input type="text" name="subtitle" class="form-control" value="<?= e($slide['subtitle'] ?? '') ?>"></div>
        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= e($slide['description'] ?? '') ?></textarea></div>
        <div class="form-row">
          <div class="form-group">
            <label>Eyebrow Badge</label>
            <input type="text" name="badge_text" class="form-control" value="<?= e($slide['badge_text'] ?? '') ?>" placeholder="Mobile App Development">
            <div class="form-hint">The small pill above the headline. Keep it short so it stays on one line.</div>
          </div>
          <div class="form-group"><label>Primary CTA Text</label><input type="text" name="cta_text" class="form-control" value="<?= e($slide['cta_text'] ?? '') ?>" placeholder="Get Started"></div>
          <div class="form-group"><label>Primary CTA URL</label><input type="text" name="cta_url" class="form-control" value="<?= e($slide['cta_url'] ?? '') ?>" placeholder="/contact.php"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Secondary CTA Text</label><input type="text" name="cta2_text" class="form-control" value="<?= e($slide['cta2_text'] ?? '') ?>" placeholder="Learn More"></div>
          <div class="form-group"><label>Secondary CTA URL</label><input type="text" name="cta2_url" class="form-control" value="<?= e($slide['cta2_url'] ?? '') ?>" placeholder="/about.php"></div>
        </div>
      </div>
    </div>
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Options</h3></div>
        <div class="card-body">
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
            <label style="margin:0">Active</label>
            <label class="toggle-switch">
              <input type="checkbox" name="is_active" <?= ($slide['is_active'] ?? 1) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px"><i class="fas fa-save"></i> Save Slide</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Background Image</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($slide['background_image'])): ?>
          <img src="<?= UPLOADS_URL . '/' . e($slide['background_image']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:12px">
          <?php endif; ?>
          <input type="file" name="background_image" class="form-control" accept="image/*" onchange="previewImage(this,'bgImg')">
          <div class="form-hint">JPG, PNG or WebP, up to <?= e(uploadLimitLabel()) ?>. Anything larger is rejected by the server before it arrives.</div>
          <img id="bgImg" style="display:none;width:100%;height:140px;object-fit:cover;border-radius:10px;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

/* ── LIST ── */
$slides = dbFetchAll("SELECT * FROM hero_slides ORDER BY page_key ASC, sort_order ASC");
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Hero Banners</h1><p><?= count($slides) ?> slides</p></div>
  <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Slide</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Preview</th><th>Page</th><th>Title</th><th>CTAs</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($slides): ?>
          <?php foreach ($slides as $s): ?>
          <tr>
            <td>
              <?php if ($s['background_image']): ?>
              <img src="<?= UPLOADS_URL . '/' . e($s['background_image']) ?>" style="width:80px;height:48px;object-fit:cover;border-radius:6px">
              <?php else: ?>
              <div style="width:80px;height:48px;background:var(--light);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--gray)"><i class="fas fa-image"></i></div>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-info"><?= ucfirst($s['page_key']) ?></span></td>
            <td><strong><?= e(truncate($s['title'], 40)) ?></strong><?php if ($s['subtitle']): ?><div style="font-size:12px;color:var(--gray)"><?= e(truncate($s['subtitle'], 40)) ?></div><?php endif; ?></td>
            <?php
              $_cta1 = $s['btn1_text'] ?? $s['cta_text']  ?? '';
              $_cta2 = $s['btn2_text'] ?? $s['cta2_text'] ?? '';
            ?>
            <td style="font-size:12px"><?= e($_cta1 ?: '—') ?><?php if ($_cta2): ?><br><span style="color:var(--gray)"><?= e($_cta2) ?></span><?php endif; ?></td>
            <td style="text-align:center"><?= $s['sort_order'] ?></td>
            <td>
              <label class="toggle-switch">
                <input type="checkbox" <?= $s['is_active'] ? 'checked' : '' ?> onchange="toggleActive(<?= $s['id'] ?>, this.checked ? 1 : 0)">
                <span class="toggle-slider"></span>
              </label>
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this slide?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-images" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No slides yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function toggleActive(id, val) {
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          || document.querySelector('[name="<?= CSRF_TOKEN_NAME ?>"]')?.value || '';
  fetch('', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'toggle', id:id, value:val, '<?= CSRF_TOKEN_NAME ?>': csrf})
  })
  .then(function(r){ return r.json(); })
  .then(function(d){ if (!d.ok) location.reload(); })
  .catch(function(){ location.reload(); });
}
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
