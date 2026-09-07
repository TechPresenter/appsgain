<?php
$adminTitle = 'FAQs';
$adminPage  = 'faqs';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    $catId = (int)($_POST['category_id'] ?? 0);
    $data  = [
      'category_id' => $catId ?: null,
      'question'    => sanitizeInput($_POST['question'] ?? ''),
      'answer'      => sanitizeInput($_POST['answer'] ?? ''),
      'page_key'    => sanitizeInput($_POST['page_key'] ?? ''),
      'sort_order'  => (int)($_POST['sort_order'] ?? 0),
      'is_active'   => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!$data['question'] || !$data['answer']) { setFlash('error', 'Question and answer are required.'); redirect(ADMIN_URL . '/pages/faqs.php'); }
    if ($postAction === 'create') {
      dbInsertRow('faqs', $data);
      logActivity('create', 'faq', "Added FAQ: {$data['question']}");
      setFlash('success', 'FAQ added!');
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('faqs', $data, 'id = ?', [$id]);
      logActivity('update', 'faq', "Updated FAQ #{$id}");
      setFlash('success', 'FAQ updated!');
    }
    redirect(ADMIN_URL . '/pages/faqs.php');
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    dbExecute("DELETE FROM faqs WHERE id = ?", [$id]);
    logActivity('delete', 'faq', "Deleted FAQ #{$id}");
    setFlash('success', 'FAQ deleted.');
    redirect(ADMIN_URL . '/pages/faqs.php');
  }

  if ($postAction === 'add_category') {
    $name = sanitizeInput($_POST['cat_name'] ?? '');
    if ($name) {
      dbInsertRow('faq_categories', ['name' => $name, 'slug' => slugify($name)]);
      setFlash('success', 'Category added!');
    }
    redirect(ADMIN_URL . '/pages/faqs.php');
  }

  if ($postAction === 'delete_category') {
    $id = (int)$_POST['id'];
    dbExecute("UPDATE faqs SET category_id = NULL WHERE category_id = ?", [$id]);
    dbExecute("DELETE FROM faq_categories WHERE id = ?", [$id]);
    setFlash('success', 'Category deleted.');
    redirect(ADMIN_URL . '/pages/faqs.php');
  }
}

$categories = dbFetchAll("SELECT * FROM faq_categories ORDER BY name ASC");
$pageKeys   = ['','home','about','services','contact','courses','placement'];

if (in_array($action, ['add','edit'])) {
  $item = $editId ? dbFetchOne("SELECT * FROM faqs WHERE id = ?", [$editId]) : null;
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1><?= $action === 'add' ? 'Add FAQ' : 'Edit FAQ' ?></h1></div>
  <a href="<?= ADMIN_URL ?>/pages/faqs.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All FAQs</a>
</div>
<form method="POST">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">
    <div class="card">
      <div class="card-header"><h3>FAQ Content</h3></div>
      <div class="card-body">
        <div class="form-group"><label>Question <span class="required">*</span></label><input type="text" name="question" class="form-control" value="<?= e($item['question'] ?? '') ?>" required></div>
        <div class="form-group"><label>Answer <span class="required">*</span></label><textarea name="answer" id="faqAnswer" class="form-control" rows="6" required><?= htmlspecialchars($item['answer'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3>Options</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Page Scope</label>
          <select name="page_key" class="form-control">
            <?php foreach ($pageKeys as $k): ?>
            <option value="<?= $k ?>" <?= ($item['page_key'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?: 'All Pages' ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-hint">Restrict this FAQ to a specific page.</div>
        </div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $item['sort_order'] ?? 0 ?>"></div>
        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between"><label style="margin:0">Active</label><label class="toggle-switch"><input type="checkbox" name="is_active" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>><span class="toggle-slider"></span></label></div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px"><i class="fas fa-save"></i> Save FAQ</button>
      </div>
    </div>
  </div>
</form>
<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('faqAnswer', { height: 240, placeholder: 'Write the FAQ answer here…', small: true });
});
</script>
<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

/* ── LIST ── */
$filterCat  = (int)($_GET['cat'] ?? 0);
$whereClause = $filterCat ? "WHERE f.category_id = ?" : '';
$whereParams = $filterCat ? [$filterCat] : [];
$faqs        = dbFetchAll(
  "SELECT f.*, fc.name AS cat_name FROM faqs f
   LEFT JOIN faq_categories fc ON f.category_id = fc.id
   {$whereClause}
   ORDER BY f.sort_order ASC, f.id ASC",
  $whereParams
);
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>FAQs</h1><p><?= count($faqs) ?> questions</p></div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('catModal').style.display='flex'"><i class="fas fa-tags"></i> Manage Categories</button>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add FAQ</a>
  </div>
</div>

<!-- Category Filter -->
<?php if ($categories): ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
  <a href="<?= ADMIN_URL ?>/pages/faqs.php" class="btn <?= !$filterCat ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All</a>
  <?php foreach ($categories as $cat): ?>
  <a href="?cat=<?= $cat['id'] ?>" class="btn <?= $filterCat == $cat['id'] ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= e($cat['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Question</th><th>Category</th><th>Page</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($faqs): foreach ($faqs as $i => $f): ?>
        <tr>
          <td style="font-size:12px;color:var(--gray)"><?= $f['sort_order'] ?></td>
          <td><strong><?= e(truncate($f['question'], 70)) ?></strong></td>
          <td style="font-size:13px"><?= e($f['cat_name'] ?: '—') ?></td>
          <td style="font-size:13px"><?= $f['page_key'] ? '<span class="badge badge-info">'.e($f['page_key']).'</span>' : '—' ?></td>
          <td><span class="badge <?= $f['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $f['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td><div style="display:flex;gap:6px">
            <a href="?action=edit&id=<?= $f['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $f['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-question-circle" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No FAQs yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Category Modal -->
<div id="catModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;width:480px;max-width:95vw;padding:28px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:700">FAQ Categories</h3>
      <button onclick="document.getElementById('catModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray)">&times;</button>
    </div>
    <form method="POST" style="display:flex;gap:10px;margin-bottom:16px">
      <?= csrfField() ?><input type="hidden" name="action" value="add_category">
      <input type="text" name="cat_name" class="form-control" placeholder="New category name" required>
      <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap"><i class="fas fa-plus"></i> Add</button>
    </form>
    <?php if ($categories): ?>
    <div style="max-height:240px;overflow-y:auto">
      <?php foreach ($categories as $cat): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--light);border-radius:8px;margin-bottom:6px">
        <span style="font-size:13.5px;font-weight:600"><?= e($cat['name']) ?></span>
        <form method="POST" onsubmit="return confirm('Delete this category?')" style="display:inline">
          <?= csrfField() ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= $cat['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
