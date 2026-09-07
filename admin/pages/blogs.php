<?php
$adminTitle = 'Blog Posts';
$adminPage  = 'blogs';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if (in_array($postAction, ['create', 'update'])) {
    $data = [
      'title'        => sanitizeInput($_POST['title'] ?? ''),
      'slug'         => slugify($_POST['slug'] ?? $_POST['title'] ?? ''),
      'excerpt'      => sanitizeInput($_POST['excerpt'] ?? ''),
      'content'      => $_POST['content'] ?? '',
      'category_id'  => (int)($_POST['category_id'] ?? 0) ?: null,
      'status'            => in_array($_POST['status'] ?? '', ['draft','published','archived']) ? $_POST['status'] : 'draft',
      'is_featured'       => isset($_POST['featured']) ? 1 : 0,
      'meta_title'        => sanitizeInput($_POST['meta_title'] ?? ''),
      'meta_description'  => sanitizeInput($_POST['meta_desc'] ?? ''),
      'tags'              => sanitizeInput($_POST['focus_keyword'] ?? $_POST['tags'] ?? ''),
      'author'            => sanitizeInput($_POST['author_name'] ?? ''),
      'author_bio'        => sanitizeInput($_POST['author_bio']  ?? ''),
    ];

    if (!$data['title']) {
      setFlash('error', 'Title is required.');
      redirect(ADMIN_URL . '/pages/blogs.php?action=' . ($postAction === 'update' ? 'edit&id=' . (int)$_POST['edit_id'] : 'add'));
    }

    $currentImage = null;
    if ($postAction === 'update') {
      $currentImage = dbFetchValue("SELECT featured_image FROM blogs WHERE id = ?", [(int)$_POST['edit_id']]);
    }

    /* Handle featured image upload */
    if (!empty($_FILES['featured_image']['name'])) {
      $upload = uploadFile($_FILES['featured_image'], 'blog');
      if ($upload['success']) {
        if ($currentImage) deleteFile($currentImage);
        $data['featured_image'] = $upload['path'];
      } else { setFlash('error', $upload['error']); redirect(ADMIN_URL . '/pages/blogs.php'); }
    }

    if ($postAction === 'create') {
      $data['author_id'] = $admin['id'];
      if ($data['status'] === 'published') $data['published_at'] = date('Y-m-d H:i:s');
      $id = dbInsertRow('blogs', $data);
      logActivity('create', 'blog', "Created blog post: {$data['title']}");
      setFlash('success', 'Blog post created!');
      redirect(ADMIN_URL . '/pages/blogs.php?action=edit&id=' . $id);
    } else {
      $id = (int)$_POST['edit_id'];
      $old = dbFetchOne("SELECT status, published_at FROM blogs WHERE id = ?", [$id]);
      if ($old && $old['status'] !== 'published' && $data['status'] === 'published') {
        $data['published_at'] = date('Y-m-d H:i:s');
      }
      dbUpdateRow('blogs', $data, 'id = ?', [$id]);
      logActivity('update', 'blog', "Updated blog post #{$id}: {$data['title']}");
      setFlash('success', 'Blog post updated!');
      redirect(ADMIN_URL . '/pages/blogs.php?action=edit&id=' . $id);
    }
  }

  if ($postAction === 'delete') {
    $id = (int)$_POST['id'];
    $b  = dbFetchOne("SELECT title, featured_image FROM blogs WHERE id = ?", [$id]);
    if ($b) {
      if ($b['featured_image']) deleteFile($b['featured_image']);
      dbExecute("DELETE FROM blogs WHERE id = ?", [$id]);
      logActivity('delete', 'blog', "Deleted blog post: {$b['title']}");
      setFlash('success', 'Post deleted.');
    }
    redirect(ADMIN_URL . '/pages/blogs.php');
  }

  if ($postAction === 'approve_comment') {
    $cid = (int)$_POST['comment_id'];
    dbExecute("UPDATE blog_comments SET status = 'approved' WHERE id = ?", [$cid]);
    setFlash('success', 'Comment approved.');
    redirect(ADMIN_URL . '/pages/blogs.php?action=edit&id=' . (int)$_POST['blog_id']);
  }

  if ($postAction === 'delete_comment') {
    $cid = (int)$_POST['comment_id'];
    dbExecute("DELETE FROM blog_comments WHERE id = ?", [$cid]);
    setFlash('success', 'Comment deleted.');
    redirect(ADMIN_URL . '/pages/blogs.php?action=edit&id=' . (int)$_POST['blog_id']);
  }
}

/* Add author_bio column if missing */
try {
    $bCols = array_column(dbFetchAll("SHOW COLUMNS FROM blogs"), 'Field');
    /* author has to exist before author_bio can be placed after it.
       In the old order the first statement threw, the catch swallowed it,
       and the second never ran - so neither column was ever created. */
    if (!in_array('author', $bCols))     db()->exec("ALTER TABLE blogs ADD COLUMN author VARCHAR(200) NOT NULL DEFAULT '' AFTER author_id");
    if (!in_array('author_bio', $bCols)) db()->exec("ALTER TABLE blogs ADD COLUMN author_bio TEXT NULL AFTER author");
} catch(Exception $e) {}

$categories = dbFetchAll("SELECT id, name FROM blog_categories ORDER BY name ASC");

/* ── Add / Edit Form ── */
if (in_array($action, ['add', 'edit'])) {
  $adminTitle = $action === 'add' ? 'New Blog Post' : 'Edit Blog Post';
  $post       = $editId ? dbFetchOne("SELECT * FROM blogs WHERE id = ?", [$editId]) : null;
  $comments   = $editId ? dbFetchAll("SELECT * FROM blog_comments WHERE blog_id = ? ORDER BY created_at DESC", [$editId]) : [];
  require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <h1><?= $action === 'add' ? 'New Blog Post' : 'Edit: ' . e($post['title'] ?? '') ?></h1>
  </div>
  <div style="display:flex;gap:10px">
    <?php if ($editId && ($post['status'] ?? '') === 'published'): ?>
    <a href="<?= SITE_URL ?>/blog/<?= e($post['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> View</a>
    <?php endif; ?>
    <a href="<?= ADMIN_URL ?>/pages/blogs.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All Posts</a>
  </div>
</div>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $action === 'add' ? 'create' : 'update' ?>">
  <?php if ($editId): ?><input type="hidden" name="edit_id" value="<?= $editId ?>"><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
    <div>
      <!-- Main Content Card -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Post Content</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Title <span class="required">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= e($post['title'] ?? '') ?>" required oninput="autoSlug(this)">
          </div>
          <div class="form-group">
            <label>Slug (URL)</label>
            <div class="input-group">
              <input type="text" name="slug" id="slugField" class="form-control" value="<?= e($post['slug'] ?? '') ?>">
              <button type="button" class="btn btn-secondary" onclick="regenerateSlug()"><i class="fas fa-sync"></i></button>
            </div>
            <div class="form-hint"><?= SITE_URL ?>/blog/<span id="slugPreview"><?= e($post['slug'] ?? '') ?></span></div>
          </div>
          <div class="form-group">
            <label>Excerpt</label>
            <textarea name="excerpt" class="form-control" rows="2" placeholder="Short summary for list pages…"><?= e($post['excerpt'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Content <span class="required">*</span></label>
            <textarea name="content" id="blogContent" class="form-control" rows="16"><?= htmlspecialchars($post['content'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Author Card -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3><i class="fas fa-user-edit" style="color:var(--blue);margin-right:8px;"></i>Author Details</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Author Name</label>
            <input type="text" name="author_name" class="form-control"
              value="<?= e($post['author'] ?? $post['author_name'] ?? $admin['name'] ?? '') ?>"
              placeholder="e.g. Appsgain Team">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Author Bio <span style="font-weight:400;color:var(--gray)">(shows on blog post)</span></label>
            <textarea name="author_bio" class="form-control" rows="3"
              placeholder="Short bio about the author…"><?= e($post['author_bio'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- SEO Card -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-search" style="color:var(--violet);margin-right:8px;"></i>SEO &amp; Schema</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Meta Title <span style="font-weight:400;color:var(--gray)">(50–60 chars)</span></label>
            <input type="text" name="meta_title" class="form-control" id="metaTitleField"
              value="<?= e($post['meta_title'] ?? '') ?>" placeholder="Defaults to post title"
              oninput="updateMetaCount(this,'metaTitleCount',60)">
            <div style="font-size:11.5px;color:var(--gray);margin-top:4px;display:flex;justify-content:space-between;">
              <span>Recommended: 50–60 characters</span>
              <span id="metaTitleCount" style="font-weight:700;"><?= strlen($post['meta_title'] ?? '') ?>/60</span>
            </div>
          </div>
          <div class="form-group">
            <label>Meta Description <span style="font-weight:400;color:var(--gray)">(150–160 chars)</span></label>
            <textarea name="meta_desc" class="form-control" rows="3" id="metaDescField"
              placeholder="150–160 characters for Google search snippet"
              oninput="updateMetaCount(this,'metaDescCount',160)"><?= e($post['meta_description'] ?? '') ?></textarea>
            <div style="font-size:11.5px;color:var(--gray);margin-top:4px;display:flex;justify-content:space-between;">
              <span>Google shows ~160 chars</span>
              <span id="metaDescCount" style="font-weight:700;"><?= strlen($post['meta_description'] ?? '') ?>/160</span>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Focus Keyword <span style="font-weight:400;color:var(--gray)">(for SEO schema)</span></label>
            <input type="text" name="focus_keyword" class="form-control"
              value="<?= e($post['tags'] ?? '') ?>"
              placeholder="e.g. flutter mobile app development">
            <div style="font-size:11.5px;color:var(--gray);margin-top:4px;">Used in Article schema &amp; stored in tags field</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Sidebar -->
    <div>
      <!-- Publish Card -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Publish</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="draft"     <?= ($post['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
              <option value="archived"  <?= ($post['status'] ?? '') === 'archived'  ? 'selected' : '' ?>>Archived</option>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
            <label style="margin:0">Featured Post</label>
            <label class="toggle-switch">
              <input type="checkbox" name="featured" <?= ($post['is_featured'] ?? 0) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="form-group">
            <label>Tags (comma separated)</label>
            <input type="text" name="tags" class="form-control" value="<?= e($post['tags'] ?? '') ?>" placeholder="php, web, tutorial">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">
            <i class="fas fa-save"></i> <?= $action === 'add' ? 'Publish Post' : 'Save Changes' ?>
          </button>
        </div>
      </div>

      <!-- Category Card -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>Category</h3></div>
        <div class="card-body">
          <select name="category_id" class="form-control">
            <option value="">— Uncategorised —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Featured Image Card -->
      <div class="card">
        <div class="card-header"><h3>Featured Image</h3></div>
        <div class="card-body" style="text-align:center">
          <?php if (!empty($post['featured_image'])): ?>
          <div class="img-preview-wrap" style="margin-bottom:12px">
            <img src="<?= UPLOADS_URL . '/' . e($post['featured_image']) ?>" class="img-preview" style="width:100%;height:160px">
          </div>
          <?php endif; ?>
          <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImage(this,'featImg')">
          <img id="featImg" style="display:none;width:100%;height:160px;object-fit:cover;border-radius:10px;margin-top:10px">
        </div>
      </div>
    </div>
  </div>
</form>

<?php if ($editId && $comments): ?>
<!-- Comments -->
<div class="card" style="margin-top:24px">
  <div class="card-header"><h3><i class="fas fa-comments" style="color:var(--blue);margin-right:8px"></i>Comments (<?= count($comments) ?>)</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Author</th><th>Comment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($comments as $c): ?>
        <tr>
          <td><strong><?= e($c['author_name']) ?></strong><div style="font-size:12px;color:var(--gray)"><?= e($c['author_email']) ?></div></td>
          <td style="max-width:300px;font-size:13px"><?= e(truncate($c['content'], 100)) ?></td>
          <td><span class="badge <?= $c['status'] === 'approved' ? 'badge-success' : ($c['status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?>"><?= ucfirst($c['status']) ?></span></td>
          <td style="font-size:12px;color:var(--gray)"><?= timeAgo($c['created_at']) ?></td>
          <td style="display:flex;gap:6px">
            <?php if ($c['status'] !== 'approved'): ?>
            <form method="POST"><<?= csrfField() ?>
              <input type="hidden" name="action" value="approve_comment">
              <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="blog_id" value="<?= $editId ?>">
              <button type="submit" class="btn btn-success btn-sm btn-icon" title="Approve"><i class="fas fa-check"></i></button>
            </form>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Delete comment?')"><?= csrfField() ?>
              <input type="hidden" name="action" value="delete_comment">
              <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="blog_id" value="<?= $editId ?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
/* Init CKEditor on blog content textarea */
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('blogContent', { height: 520, placeholder: 'Write your blog post content here…' });
});

/* Slug helpers */
function autoSlug(input) {
    var slug = input.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-');
    document.getElementById('slugField').value = slug;
    var prev = document.getElementById('slugPreview');
    if (prev) prev.textContent = slug;
}
function regenerateSlug() {
    autoSlug(document.querySelector('input[name="title"]'));
}
var slugF = document.getElementById('slugField');
if (slugF) slugF.addEventListener('input', function() {
    var prev = document.getElementById('slugPreview');
    if (prev) prev.textContent = this.value;
});

/* Meta character counter */
function updateMetaCount(el, countId, max) {
    var cnt = document.getElementById(countId);
    if (!cnt) return;
    var len = el.value.length;
    cnt.textContent = len + '/' + max;
    cnt.style.color = len > max ? 'var(--rose)' : len > max * 0.9 ? 'var(--amber)' : 'var(--emerald)';
}
/* Init counters */
['metaTitleField','metaDescField'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.dispatchEvent(new Event('input'));
});
</script>

<?php
  require_once dirname(__DIR__) . '/includes/admin-foot.php';
  exit;
}

/* ── LIST VIEW ── */
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$status  = sanitizeInput($_GET['status'] ?? '');

$where  = [];
$params = [];
if ($search) {
  $where[] = "(b.title LIKE ? OR b.excerpt LIKE ?)";
  $like    = "%{$search}%";
  $params  = array_merge($params, [$like, $like]);
}
if ($status) { $where[] = "b.status = ?"; $params[] = $status; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) dbFetchValue("SELECT COUNT(*) FROM blogs b {$whereSQL}", $params);
$blogs = dbFetchAll(
  "SELECT b.*, bc.name AS cat_name, u.name AS author_name
   FROM blogs b
   LEFT JOIN blog_categories bc ON b.category_id = bc.id
   LEFT JOIN users u ON b.author_id = u.id
   {$whereSQL}
   ORDER BY b.created_at DESC
   LIMIT {$perPage} OFFSET {$offset}", $params
);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/blogs.php?' . http_build_query(array_filter(['search'=>$search,'status'=>$status])));

/* Tab counts */
$cntAll       = (int) dbFetchValue("SELECT COUNT(*) FROM blogs");
$cntPublished = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE status = 'published'");
$cntDraft     = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE status = 'draft'");
$cntArchived  = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE status = 'archived'");
$cntFeatured  = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE is_featured = 1");
$totalViews   = (int) dbFetchValue("SELECT COALESCE(SUM(views),0) FROM blogs");

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div><h1>Blog Posts</h1><p><?= number_format($cntAll) ?> total posts · <?= number_format($totalViews) ?> total views</p></div>
  <div style="display:flex;gap:8px">
    <a href="<?= ADMIN_URL ?>/pages/blog-cats.php" class="btn btn-secondary btn-sm"><i class="fas fa-tags"></i> Categories</a>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
  </div>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
  <?php
  $tabs = ['' => ["All Posts", $cntAll, ''], 'published' => ['Published', $cntPublished, '#059669'], 'draft' => ['Drafts', $cntDraft, '#d97706'], 'archived' => ['Archived', $cntArchived, '#6b7280']];
  foreach ($tabs as $tv => [$tl, $tc, $tc_color]):
  ?>
  <a href="?status=<?= $tv ?><?= $search ? '&search='.urlencode($search) : '' ?>"
     class="status-tab <?= $status === $tv ? 'active' : '' ?>">
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
  <div class="search-wrap">
    <i class="fas fa-search"></i>
    <input type="text" name="search" class="search-input" placeholder="Search posts…" value="<?= e($search) ?>">
  </div>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Search</button>
  <?php if ($search): ?><a href="?status=<?= e($status) ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
</form>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:40px"></th>
          <th>Title</th>
          <th>Category</th>
          <th>Author</th>
          <th>Status</th>
          <th>Views</th>
          <th>Date</th>
          <th style="width:100px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($blogs): ?>
          <?php foreach ($blogs as $b):
            $bClass = ['published' => 'badge-success', 'draft' => 'badge-warning', 'archived' => 'badge-secondary'];
          ?>
          <tr>
            <td>
              <?php if (!empty($b['featured_image'])): ?>
              <img src="<?= UPLOADS_URL . '/' . e($b['featured_image']) ?>" alt="" loading="lazy"
                   style="width:36px;height:36px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border)">
              <?php else: ?>
              <div style="width:36px;height:36px;background:var(--light);border-radius:8px;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-image" style="color:var(--gray2);font-size:14px"></i>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight:600;font-size:13.5px;line-height:1.3"><?= e(truncate($b['title'], 55)) ?></div>
              <?php if ($b['is_featured']): ?><span class="badge badge-violet" style="font-size:10px;margin-top:3px"><i class="fas fa-star" style="font-size:8px"></i> Featured</span><?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--gray)"><?= e($b['cat_name'] ?? '—') ?></td>
            <td style="font-size:13px"><?= e($b['author_name'] ?? '—') ?></td>
            <td><span class="badge <?= $bClass[$b['status']] ?? 'badge-secondary' ?>"><?= ucfirst($b['status']) ?></span></td>
            <td style="font-size:13px;font-weight:600"><?= number_format($b['views'] ?? 0) ?></td>
            <td style="font-size:12px;color:var(--gray)" title="<?= e($b['created_at']) ?>"><?= timeAgo($b['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <a href="?action=edit&id=<?= $b['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                <?php if ($b['status'] === 'published'): ?>
                <a href="<?= SITE_URL ?>/blog/<?= e($b['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon" title="View on site"><i class="fas fa-external-link-alt"></i></a>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post and its featured image?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:48px 20px">
              <i class="fas fa-file-alt" style="font-size:36px;color:var(--gray2);opacity:.35;display:block;margin-bottom:12px"></i>
              <div style="font-size:14px;font-weight:600;color:var(--primary);margin-bottom:4px">No posts found</div>
              <div style="font-size:13px;color:var(--gray);margin-bottom:16px">
                <?= $search ? 'No results for "' . e($search) . '"' : ($status ? "No {$status} posts yet." : 'Create your first blog post.') ?>
              </div>
              <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagination): ?>
  <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <span style="font-size:13px;color:var(--gray)">Showing <?= number_format(($page-1)*$perPage+1) ?>–<?= number_format(min($page*$perPage,$total)) ?> of <?= number_format($total) ?></span>
    <div class="pagination"><?= $pagination ?></div>
  </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
