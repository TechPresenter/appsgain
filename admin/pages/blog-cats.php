<?php
$adminTitle = 'Blog Categories';
$adminPage  = 'blog-cats';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create','update'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $slug = slugify($_POST['slug'] ?? $name);
    $desc = sanitizeInput($_POST['description'] ?? '');
    if (!$name) { setFlash('error', 'Name is required.'); redirect(ADMIN_URL . '/pages/blog-cats.php'); }
    if ($postAction === 'create') {
      $exists = dbFetchValue("SELECT id FROM blog_categories WHERE slug = ?", [$slug]);
      if ($exists) { setFlash('error', 'Slug already exists.'); redirect(ADMIN_URL . '/pages/blog-cats.php'); }
      dbInsertRow('blog_categories', ['name' => $name, 'slug' => $slug, 'description' => $desc]);
      setFlash('success', 'Category created!');
    } else {
      $id = (int)$_POST['edit_id'];
      dbUpdateRow('blog_categories', ['name' => $name, 'slug' => $slug, 'description' => $desc], 'id = ?', [$id]);
      setFlash('success', 'Category updated!');
    }
    redirect(ADMIN_URL . '/pages/blog-cats.php');
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $count = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE category_id = ?", [$id]);
    if ($count > 0) { setFlash('error', "Cannot delete — {$count} blog post(s) use this category."); redirect(ADMIN_URL . '/pages/blog-cats.php'); }
    dbExecute("DELETE FROM blog_categories WHERE id = ?", [$id]);
    setFlash('success', 'Category deleted.');
    redirect(ADMIN_URL . '/pages/blog-cats.php');
  }
}

$cats = dbFetchAll(
  "SELECT bc.*, COUNT(b.id) AS post_count
   FROM blog_categories bc
   LEFT JOIN blogs b ON b.category_id = bc.id
   GROUP BY bc.id
   ORDER BY bc.name ASC"
);
$editId = (int)($_GET['edit'] ?? 0);
$editCat = $editId ? dbFetchOne("SELECT * FROM blog_categories WHERE id = ?", [$editId]) : null;

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Blog Categories</h1><p><?= count($cats) ?> categories</p></div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">
  <!-- List -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($cats): foreach ($cats as $c): ?>
          <tr <?= $editId == $c['id'] ? 'style="background:rgba(124,58,237,.05)"' : '' ?>>
            <td><strong><?= e($c['name']) ?></strong><?php if ($c['description']): ?><div style="font-size:12px;color:var(--gray)"><?= e(truncate($c['description'],50)) ?></div><?php endif; ?></td>
            <td style="font-family:monospace;font-size:13px;color:var(--gray)"><?= e($c['slug']) ?></td>
            <td><span class="badge badge-info"><?= $c['post_count'] ?></span></td>
            <td><div style="display:flex;gap:6px">
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash"></i></button></form>
            </div></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray)">No categories yet</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add/Edit Form -->
  <div class="card">
    <div class="card-header"><h3><?= $editCat ? 'Edit Category' : 'Add Category' ?></h3></div>
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="<?= $editCat ? 'update' : 'create' ?>">
        <?php if ($editCat): ?><input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>"><?php endif; ?>
        <div class="form-group">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= e($editCat['name'] ?? '') ?>" required oninput="this.form.slug.value=slugify(this.value)">
        </div>
        <div class="form-group">
          <label>Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= e($editCat['slug'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"><?= e($editCat['description'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editCat ? 'Update' : 'Add' ?></button>
          <?php if ($editCat): ?><a href="<?= ADMIN_URL ?>/pages/blog-cats.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function slugify(s) {
  return s.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-');
}
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
