<?php
$adminTitle = 'Projects / Portfolio';
$adminPage  = 'projects';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

// Auto-add `status` column if missing (schema migration)
try {
    dbFetchValue("SELECT status FROM projects LIMIT 1");
} catch (Exception $e) {
    try {
        db()->exec("ALTER TABLE `projects`
            ADD COLUMN `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'published' AFTER `is_featured`,
            ADD COLUMN `client` VARCHAR(200) DEFAULT NULL AFTER `client_name`,
            ADD COLUMN `excerpt` VARCHAR(500) DEFAULT NULL AFTER `short_description`,
            ADD COLUMN `project_url` VARCHAR(300) DEFAULT NULL AFTER `live_url`");
        // Sync existing is_active → status
        db()->exec("UPDATE `projects` SET status='published' WHERE is_active=1");
        db()->exec("UPDATE `projects` SET status='draft' WHERE is_active=0");
    } catch (Exception $e2) { /* Column might already exist */ }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    $techs = array_filter(array_map('trim', explode(',', $_POST['technologies'] ?? '')));
    $data  = [
      'title'        => sanitizeInput($_POST['title'] ?? ''),
      'slug'         => slugify($_POST['slug'] ?? $_POST['title'] ?? ''),
      'excerpt'      => sanitizeInput($_POST['excerpt'] ?? ''),
      'description'  => $_POST['description'] ?? '',
      'category_id'  => (int)($_POST['category_id'] ?? 0) ?: null,
      'client'       => sanitizeInput($_POST['client'] ?? ''),
      'project_url'  => sanitizeInput($_POST['project_url'] ?? ''),
      'technologies' => json_encode($techs),
      'status'       => in_array($_POST['status'] ?? '', ['draft','published','archived']) ? $_POST['status'] : 'draft',
      'is_featured'     => isset($_POST['is_featured']) ? 1 : 0,
      'sort_order'   => (int)($_POST['sort_order'] ?? 0),
      'meta_title'   => sanitizeInput($_POST['meta_title'] ?? ''),
      'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
    ];
    if (!$data['title']) { setFlash('error', 'Title is required.'); redirect(ADMIN_URL . '/pages/projects.php'); }

    $currentImage = null;
    if ($postAction === 'update') {
      $currentImage = dbFetchValue("SELECT featured_image FROM projects WHERE id = ?", [(int)$_POST['edit_id']]);
    }

    if (!empty($_FILES['featured_image']['name'])) {
      $upload = uploadFile($_FILES['featured_image'], 'projects');
      if ($upload['success']) {
        if ($currentImage) deleteFile($currentImage);
        $data['featured_image'] = $upload['path'];
      } else { setFlash('error', $upload['error']); redirect(ADMIN_URL . '/pages/projects.php'); }
    }

    if ($postAction === 'create') {
      $id = dbInsertRow('projects', $data);
      logActivity('create', 'project', "Created project: {$data['title']}");
      setFlash('success', 'Project created!');
      redirect(ADMIN_URL . '/pages/projects.php?action=edit&id=' . $id);
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('projects', $data, 'id = ?', [$id]);
      logActivity('update', 'project', "Updated project #{$id}");
      setFlash('success', 'Project updated!');
      redirect(ADMIN_URL . '/pages/projects.php?action=edit&id=' . $id);
    }
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $p  = dbFetchOne("SELECT title, featured_image FROM projects WHERE id = ?", [$id]);
    if ($p) {
      if ($p['featured_image']) deleteFile($p['featured_image']);
      dbExecute("DELETE FROM projects WHERE id = ?", [$id]);
      logActivity('delete', 'project', "Deleted project: {$p['title']}");
      setFlash('success', 'Project deleted.');
    }
    redirect(ADMIN_URL . '/pages/projects.php');
  }
}

$categories = dbFetchAll("SELECT id, name FROM project_categories ORDER BY name ASC");

if (in_array($action, ['add','edit'])) {
  $proj = $editId ? dbFetchOne("SELECT * FROM projects WHERE id = ?", [$editId]) : null;
  $techs = $proj ? implode(', ', json_decode($proj['technologies'] ?? '[]', true) ?: []) : '';
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add Project' : 'Edit: ' . e($proj['title'] ?? '') ?></h1></div>
  <div style="display:flex;gap:10px">
    <?php if ($editId && ($proj['status'] ?? '') === 'published'): ?>
    <a href="<?= SITE_URL ?>/portfolio/<?= e($proj['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> View</a>
    <?php endif; ?>
    <a href="<?= ADMIN_URL ?>/pages/projects.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Projects</a>
  </div>
</div>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Project Details</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Title <span class="required">*</span></label><input type="text" name="title" class="form-control" value="<?= e($proj['title'] ?? '') ?>" required oninput="autoSlug(this)"></div>
          <div class="form-group">
            <label>Slug</label>
            <div class="input-group"><input type="text" name="slug" id="slugField" class="form-control" value="<?= e($proj['slug'] ?? '') ?>"><button type="button" class="btn btn-secondary" onclick="regenerateSlug()"><i class="fas fa-sync"></i></button></div>
          </div>
          <div class="form-group"><label>Short Description</label><textarea name="excerpt" class="form-control" rows="2"><?= e($proj['excerpt'] ?? '') ?></textarea></div>
          <div class="form-group"><label>Full Description</label><textarea name="description" id="projDescription" class="form-control" rows="10"><?= htmlspecialchars($proj['description'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea></div>
          <div class="form-row">
            <div class="form-group"><label>Client Name</label><input type="text" name="client" class="form-control" value="<?= e($proj['client'] ?? '') ?>"></div>
            <div class="form-group"><label>Project URL</label><input type="url" name="project_url" class="form-control" value="<?= e($proj['project_url'] ?? '') ?>"></div>
          </div>
          <div class="form-group"><label>Technologies (comma separated)</label><input type="text" name="technologies" class="form-control" value="<?= e($techs) ?>" placeholder="PHP, MySQL, React…"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>SEO</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= e($proj['meta_title'] ?? '') ?>"></div>
          <div class="form-group"><label>Meta Description</label><textarea name="meta_description" class="form-control" rows="2"><?= e($proj['meta_description'] ?? '') ?></textarea></div>
        </div>
      </div>
    </div>
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Publish</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="draft"     <?= ($proj['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= ($proj['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
              <option value="archived"  <?= ($proj['status'] ?? '') === 'archived'  ? 'selected' : '' ?>>Archived</option>
            </select>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($proj['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Featured</label><label class="toggle-switch"><input type="checkbox" name="is_featured" <?= ($proj['is_featured'] ?? 0) ? 'checked' : '' ?>><span class="toggle-slider"></span></label></div>
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $proj['sort_order'] ?? 0 ?>"></div>
          <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Save Project</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Thumbnail</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($proj['featured_image'])): ?>
          <img src="<?= getImageUrl($proj['featured_image']) ?>" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:12px">
          <?php endif; ?>
          <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImage(this,'thumbImg')">
          <img id="thumbImg" style="display:none;width:100%;height:160px;object-fit:cover;border-radius:10px;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>
<script>
function autoSlug(i){ const s=i.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-'); document.getElementById('slugField').value=s; }
function regenerateSlug(){ autoSlug(document.querySelector('input[name="title"]')); }
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initCKEditor === 'function') initCKEditor('projDescription', { height: 340, placeholder: 'Describe this project in detail…' });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

/* ── LIST ── */
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$status  = sanitizeInput($_GET['status'] ?? '');

$where  = [];
$params = [];
if ($search) { $where[] = "p.title LIKE ?"; $params[] = "%{$search}%"; }
if ($status) { $where[] = "p.status = ?"; $params[] = $status; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = (int) dbFetchValue("SELECT COUNT(*) FROM projects p {$whereSQL}", $params);
$projects   = dbFetchAll("SELECT p.*, pc.name AS cat_name FROM projects p LEFT JOIN project_categories pc ON p.category_id = pc.id {$whereSQL} ORDER BY p.sort_order ASC, p.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/projects.php?' . http_build_query(array_filter(['search'=>$search,'status'=>$status])));

$cntAll       = (int) dbFetchValue("SELECT COUNT(*) FROM projects");
$cntPublished = (int) dbFetchValue("SELECT COUNT(*) FROM projects WHERE status = 'published'");
$cntDraft     = (int) dbFetchValue("SELECT COUNT(*) FROM projects WHERE status = 'draft'");
$cntFeatured  = (int) dbFetchValue("SELECT COUNT(*) FROM projects WHERE is_featured = 1");

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Projects / Portfolio</h1><p><?= number_format($cntAll) ?> total · <?= number_format($cntPublished) ?> published</p></div>
  <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Project</a>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
  <?php
  $ptabs = ['' => ['All', $cntAll], 'published' => ['Published', $cntPublished], 'draft' => ['Drafts', $cntDraft], 'archived' => ['Archived', (int)dbFetchValue("SELECT COUNT(*) FROM projects WHERE status='archived'")]];
  foreach ($ptabs as $tv => [$tl, $tc]):
  ?>
  <a href="?status=<?= $tv ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="status-tab <?= $status === $tv ? 'active' : '' ?>">
    <?= $tl ?> <span class="tab-count"><?= $tc ?></span>
  </a>
  <?php endforeach; ?>
  <?php if ($cntFeatured > 0): ?>
  <a href="?featured=1" class="status-tab" style="margin-left:auto">
    <i class="fas fa-star" style="font-size:11px;color:var(--amber)"></i> Featured <span class="tab-count"><?= $cntFeatured ?></span>
  </a>
  <?php endif; ?>
</div>

<form method="GET" class="filter-bar">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="search-wrap"><i class="fas fa-search"></i><input type="text" name="search" class="search-input" placeholder="Search by title…" value="<?= e($search) ?>"></div>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Search</button>
  <?php if ($search): ?><a href="?status=<?= e($status) ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
</form>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th style="width:74px">Thumb</th><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if ($projects): foreach ($projects as $p):
          $pClass = ['published'=>'badge-success','draft'=>'badge-warning','archived'=>'badge-secondary'];
        ?>
        <tr>
          <td>
            <?php if ($p['featured_image']): ?>
            <img src="<?= getImageUrl($p['featured_image']) ?>" loading="lazy" style="width:64px;height:42px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border)">
            <?php else: ?>
            <div style="width:64px;height:42px;background:var(--light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--gray2)"><i class="fas fa-briefcase"></i></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:13.5px"><?= e(truncate($p['title'], 50)) ?></div>
            <?php if ($p['is_featured']): ?><span class="badge badge-violet" style="font-size:10px;margin-top:3px"><i class="fas fa-star" style="font-size:8px"></i> Featured</span><?php endif; ?>
          </td>
          <td style="font-size:13px;color:var(--gray)"><?= e($p['cat_name'] ?? '—') ?></td>
          <td><span class="badge <?= $pClass[$p['status']] ?? 'badge-secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td style="font-size:13px;font-weight:600"><?= number_format($p['views'] ?? 0) ?></td>
          <td>
            <div style="display:flex;gap:5px">
              <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
              <?php if ($p['status'] === 'published'): ?>
              <a href="<?= SITE_URL ?>/portfolio/<?= e($p['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon" title="View"><i class="fas fa-external-link-alt"></i></a>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this project?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px">
            <i class="fas fa-briefcase" style="font-size:36px;color:var(--gray2);opacity:.35;display:block;margin-bottom:12px"></i>
            <div style="font-size:14px;font-weight:600;color:var(--primary);margin-bottom:12px">No projects found</div>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add First Project</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagination): ?>
  <div class="card-footer" style="display:flex;justify-content:flex-end">
    <div class="pagination"><?= $pagination ?></div>
  </div>
  <?php endif; ?>
</div>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
