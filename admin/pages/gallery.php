<?php
$adminTitle = 'Gallery Manager';
$adminPage  = 'gallery';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* ── Auto-create tables + migrate columns ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS galleries (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT,
        cover_image VARCHAR(500),
        sort_order INT UNSIGNED DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_slug (slug),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS gallery_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        gallery_id INT UNSIGNED NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        title VARCHAR(255),
        caption TEXT,
        sort_order INT UNSIGNED DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gallery (gallery_id),
        INDEX idx_sort (gallery_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    /* Migrate gallery_items — add file_path if missing, rename from image_path/path */
    $giCols = array_column(dbFetchAll("SHOW COLUMNS FROM gallery_items"), 'Field');
    if (!in_array('file_path', $giCols)) {
        if      (in_array('image_path', $giCols)) db()->exec("ALTER TABLE gallery_items CHANGE image_path file_path VARCHAR(500) NOT NULL DEFAULT ''");
        elseif  (in_array('path',       $giCols)) db()->exec("ALTER TABLE gallery_items CHANGE path file_path VARCHAR(500) NOT NULL DEFAULT ''");
        elseif  (in_array('image',      $giCols)) db()->exec("ALTER TABLE gallery_items CHANGE image file_path VARCHAR(500) NOT NULL DEFAULT ''");
        else    db()->exec("ALTER TABLE gallery_items ADD COLUMN file_path VARCHAR(500) NOT NULL DEFAULT '' AFTER gallery_id");
    }
    if (!in_array('title',      $giCols)) db()->exec("ALTER TABLE gallery_items ADD COLUMN title VARCHAR(255) DEFAULT NULL");
    if (!in_array('caption',    $giCols)) db()->exec("ALTER TABLE gallery_items ADD COLUMN caption TEXT DEFAULT NULL");
    if (!in_array('sort_order', $giCols)) db()->exec("ALTER TABLE gallery_items ADD COLUMN sort_order INT UNSIGNED DEFAULT 0");

    /* Migrate galleries — add cover_image if missing */
    $gCols = array_column(dbFetchAll("SHOW COLUMNS FROM galleries"), 'Field');
    if (!in_array('cover_image', $gCols)) db()->exec("ALTER TABLE galleries ADD COLUMN cover_image VARCHAR(500) DEFAULT NULL AFTER description");
    if (!in_array('is_active',   $gCols)) db()->exec("ALTER TABLE galleries ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");

} catch(Exception $e) {}

/* ── Determine view ── */
$view      = sanitizeInput($_GET['view'] ?? 'albums');
$albumId   = (int)($_GET['album'] ?? 0);

/* ── AJAX: Image upload into album ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    verifyCsrf();
    $gid = (int)($_POST['gallery_id'] ?? 0);
    if (!$gid) { http_response_code(400); header('Content-Type:application/json'); echo json_encode(['ok'=>false,'error'=>'No album selected']); exit; }
    $upload = uploadFile($_FILES['file'], 'gallery');
    if ($upload['success']) {
        $maxSort = (int) dbFetchValue("SELECT COALESCE(MAX(sort_order),0)+1 FROM gallery_items WHERE gallery_id = ?", [$gid]);
        $itemId  = dbInsertRow('gallery_items', [
            'gallery_id' => $gid,
            'file_path'  => $upload['path'],
            'title'      => sanitizeInput($_POST['title'] ?? ''),
            'sort_order' => $maxSort,
        ]);
        $hasCover = dbFetchValue("SELECT cover_image FROM galleries WHERE id = ? AND cover_image IS NOT NULL", [$gid]);
        if (!$hasCover) {
            dbExecute("UPDATE galleries SET cover_image = ? WHERE id = ?", [$upload['path'], $gid]);
        }
        logActivity('create', 'media', 'Gallery upload: ' . basename($upload['path']));
        header('Content-Type:application/json');
        echo json_encode(['ok'=>true,'id'=>$itemId,'url'=>UPLOADS_URL . '/' . $upload['path'],'path'=>$upload['path']]);
        exit;
    }
    http_response_code(400); header('Content-Type:application/json'); echo json_encode(['ok'=>false,'error'=>$upload['error']??'Upload failed']); exit;
}

/* ── POST Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_album') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $desc  = sanitizeInput($_POST['description'] ?? '');
        if (!$title) { setFlash('error', 'Album title is required.'); redirect(ADMIN_URL . '/pages/gallery.php'); }
        $slug = slugify($title);
        $exists = dbFetchValue("SELECT id FROM galleries WHERE slug = ?", [$slug]);
        if ($exists) $slug .= '-' . time();
        $maxSort = (int) dbFetchValue("SELECT COALESCE(MAX(sort_order),0)+1 FROM galleries");
        $id = dbInsertRow('galleries', ['title' => $title, 'slug' => $slug, 'description' => $desc, 'sort_order' => $maxSort]);
        logActivity('create', 'media', "Created gallery album: {$title}");
        setFlash('success', 'Album created!');
        redirect(ADMIN_URL . '/pages/gallery.php?view=album&album=' . $id);
    }

    if ($action === 'update_album') {
        $id    = (int)$_POST['id'];
        $title = sanitizeInput($_POST['title'] ?? '');
        $desc  = sanitizeInput($_POST['description'] ?? '');
        if (!$title) { setFlash('error', 'Title is required.'); redirect(ADMIN_URL . '/pages/gallery.php?view=album&album=' . $id); }
        $upd = ['title' => $title, 'description' => $desc];
        if (!empty($_FILES['cover']['name'])) {
            $up = uploadFile($_FILES['cover'], 'gallery');
            if ($up['success']) $upd['cover_image'] = $up['path'];
        }
        dbUpdateRow('galleries', $upd, 'id = ?', [$id]);
        logActivity('update', 'media', "Updated gallery album #{$id}");
        setFlash('success', 'Album updated!');
        redirect(ADMIN_URL . '/pages/gallery.php?view=album&album=' . $id);
    }

    if ($action === 'toggle_album') {
        $id = (int)$_POST['id'];
        $cur = (int) dbFetchValue("SELECT is_active FROM galleries WHERE id = ?", [$id]);
        dbExecute("UPDATE galleries SET is_active = ? WHERE id = ?", [$cur ? 0 : 1, $id]);
        setFlash('success', 'Album status updated.');
        redirect(ADMIN_URL . '/pages/gallery.php');
    }

    if ($action === 'delete_album') {
        $id = (int)$_POST['id'];
        $album = dbFetchOne("SELECT title FROM galleries WHERE id = ?", [$id]);
        // Delete all item files
        $items = dbFetchAll("SELECT file_path FROM gallery_items WHERE gallery_id = ?", [$id]);
        foreach ($items as $item) deleteFile($item['file_path']);
        dbExecute("DELETE FROM gallery_items WHERE gallery_id = ?", [$id]);
        dbExecute("DELETE FROM galleries WHERE id = ?", [$id]);
        logActivity('delete', 'media', "Deleted gallery album: " . ($album['title'] ?? $id));
        setFlash('success', 'Album and all its images deleted.');
        redirect(ADMIN_URL . '/pages/gallery.php');
    }

    if ($action === 'delete_item') {
        $id  = (int)$_POST['id'];
        $gid = (int)($_POST['gallery_id'] ?? 0);
        $item = dbFetchOne("SELECT file_path, gallery_id FROM gallery_items WHERE id = ?", [$id]);
        if ($item) {
            // If this was the cover, clear it
            dbExecute("UPDATE galleries SET cover_image = NULL WHERE id = ? AND cover_image = ?", [$item['gallery_id'], $item['file_path']]);
            deleteFile($item['file_path']);
            dbExecute("DELETE FROM gallery_items WHERE id = ?", [$id]);
            // Set new cover from first remaining image
            $first = dbFetchOne("SELECT file_path FROM gallery_items WHERE gallery_id = ? ORDER BY sort_order ASC LIMIT 1", [$item['gallery_id']]);
            if ($first) dbExecute("UPDATE galleries SET cover_image = ? WHERE id = ?", [$first['file_path'], $item['gallery_id']]);
        }
        // AJAX or redirect
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type:application/json'); echo json_encode(['ok'=>true]); exit;
        }
        setFlash('success', 'Image deleted.');
        redirect(ADMIN_URL . '/pages/gallery.php?view=album&album=' . ($item['gallery_id'] ?? $gid));
    }

    if ($action === 'set_cover') {
        $itemId = (int)$_POST['item_id'];
        $gid    = (int)$_POST['gallery_id'];
        $path   = dbFetchValue("SELECT file_path FROM gallery_items WHERE id = ? AND gallery_id = ?", [$itemId, $gid]);
        if ($path) dbExecute("UPDATE galleries SET cover_image = ? WHERE id = ?", [$path, $gid]);
        header('Content-Type:application/json'); echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'reorder') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        foreach ($ids as $i => $id) {
            dbExecute("UPDATE gallery_items SET sort_order = ? WHERE id = ?", [$i, $id]);
        }
        header('Content-Type:application/json'); echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'update_caption') {
        $id      = (int)$_POST['id'];
        $caption = sanitizeInput($_POST['caption'] ?? '');
        $title   = sanitizeInput($_POST['title'] ?? '');
        dbExecute("UPDATE gallery_items SET caption = ?, title = ? WHERE id = ?", [$caption, $title, $id]);
        header('Content-Type:application/json'); echo json_encode(['ok'=>true]); exit;
    }
}

/* ── Load data ── */
if ($view === 'album' && $albumId) {
    $album = dbFetchOne("SELECT * FROM galleries WHERE id = ?", [$albumId]);
    if (!$album) { setFlash('error', 'Album not found.'); redirect(ADMIN_URL . '/pages/gallery.php'); }
    $items = dbFetchAll("SELECT * FROM gallery_items WHERE gallery_id = ? ORDER BY sort_order ASC", [$albumId]);
} else {
    $view = 'albums';
    $albums = dbFetchAll("SELECT g.*, COUNT(gi.id) AS item_count
        FROM galleries g
        LEFT JOIN gallery_items gi ON gi.gallery_id = g.id
        GROUP BY g.id
        ORDER BY g.sort_order ASC, g.created_at DESC");
}

$totalAlbums = (int) dbFetchValue("SELECT COUNT(*) FROM galleries");
$totalImages = (int) dbFetchValue("SELECT COUNT(*) FROM gallery_items");

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
/* ── Gallery Albums Grid ── */
.album-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
.album-card{background:var(--white);border:1.5px solid var(--border);border-radius:16px;overflow:hidden;transition:var(--transition);position:relative}
.album-card:hover{border-color:rgba(124,58,237,.35);box-shadow:var(--shadow-md);transform:translateY(-2px)}
.album-cover{width:100%;height:180px;object-fit:cover;background:linear-gradient(135deg,var(--light),var(--light2));display:block}
.album-cover-placeholder{width:100%;height:180px;background:linear-gradient(135deg,var(--light),var(--light2));display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--gray2)}
.album-info{padding:14px 16px}
.album-title{font-size:14.5px;font-weight:700;color:var(--primary);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.album-meta{font-size:12px;color:var(--gray);display:flex;align-items:center;gap:10px}
.album-actions{display:flex;gap:6px;padding:10px 12px 12px;border-top:1px solid var(--border)}
.album-status{position:absolute;top:10px;left:10px}

/* ── Image Grid Inside Album ── */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.gallery-item{background:var(--white);border:1.5px solid var(--border);border-radius:12px;overflow:hidden;position:relative;transition:var(--transition)}
.gallery-item:hover{border-color:rgba(124,58,237,.35);box-shadow:var(--shadow)}
.gallery-item .gi-img{width:100%;height:150px;object-fit:cover;display:block;cursor:zoom-in}
.gallery-item .gi-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);opacity:0;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;border-radius:12px}
.gallery-item:hover .gi-overlay{opacity:1}
.gallery-item .gi-footer{padding:8px 10px;border-top:1px solid var(--border)}
.gallery-item .gi-title{font-size:12px;font-weight:600;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gi-action-btn{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;transition:.2s}
.gi-action-btn.del{background:var(--rose)}
.gi-action-btn.star{background:var(--amber)}
.gi-action-btn.zoom{background:var(--blue)}
.gi-cover-badge{position:absolute;top:6px;left:6px;background:var(--amber);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;pointer-events:none}
.gi-drag-handle{position:absolute;top:6px;right:6px;background:rgba(255,255,255,.85);width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:grab;font-size:11px;color:var(--gray);opacity:0;transition:.2s}
.gallery-item:hover .gi-drag-handle{opacity:1}

/* ── Upload Zone ── */
.gal-drop-zone{border:2.5px dashed var(--border);border-radius:14px;padding:32px;text-align:center;transition:.3s;cursor:pointer;background:rgba(124,58,237,.015)}
.gal-drop-zone.dragover{border-color:var(--violet);background:rgba(124,58,237,.06)}
.gal-drop-zone .dz-icon{font-size:36px;color:var(--violet);margin-bottom:12px}

/* ── Modal ── */
.gal-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:600;align-items:center;justify-content:center;padding:20px}
.gal-modal.open{display:flex}
.gal-modal-box{background:var(--white);border-radius:20px;width:100%;padding:28px;box-shadow:var(--shadow-lg)}
.gal-modal-box.sm{max-width:480px}
.gal-modal-box.md{max-width:600px}
.gal-modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
.gal-modal-header h3{font-size:17px;font-weight:700}
.gal-modal-close{width:32px;height:32px;border-radius:8px;border:none;background:var(--light);cursor:pointer;font-size:14px;color:var(--gray)}

/* ── Lightbox ── */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:700;align-items:center;justify-content:center}
.lightbox.open{display:flex}
.lightbox img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;box-shadow:0 0 60px rgba(0,0,0,.6)}
.lightbox-close{position:absolute;top:16px;right:20px;background:rgba(255,255,255,.1);border:none;color:#fff;width:40px;height:40px;border-radius:10px;cursor:pointer;font-size:18px}
.lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.1);border:none;color:#fff;width:44px;height:44px;border-radius:50%;cursor:pointer;font-size:16px}
.lightbox-nav.prev{left:16px}
.lightbox-nav.next{right:16px}
.lightbox-caption{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.7);font-size:13px;text-align:center;max-width:600px}
</style>

<?php if ($view === 'albums'): ?>

<!-- ── ALBUMS VIEW ── -->
<div class="page-header">
  <div>
    <h1>Gallery Manager</h1>
    <p><?= $totalAlbums ?> albums · <?= $totalImages ?> images total</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="openModal('createAlbumModal')">
    <i class="fas fa-plus"></i> New Album
  </button>
</div>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
  <?php
  $activeAlbums = (int) dbFetchValue("SELECT COUNT(*) FROM galleries WHERE is_active = 1");
  $thisMonth    = (int) dbFetchValue("SELECT COUNT(*) FROM gallery_items WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
  ?>
  <div class="card" style="padding:18px;display:flex;align-items:center;gap:14px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--violet),var(--blue));width:44px;height:44px;font-size:18px"><i class="fas fa-images"></i></div>
    <div><div style="font-size:11.5px;color:var(--gray);font-weight:600;text-transform:uppercase">Total Albums</div><div style="font-size:24px;font-weight:800;color:var(--primary)"><?= $totalAlbums ?></div></div>
  </div>
  <div class="card" style="padding:18px;display:flex;align-items:center;gap:14px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--emerald),#047857);width:44px;height:44px;font-size:18px"><i class="fas fa-check-circle"></i></div>
    <div><div style="font-size:11.5px;color:var(--gray);font-weight:600;text-transform:uppercase">Published</div><div style="font-size:24px;font-weight:800;color:var(--primary)"><?= $activeAlbums ?></div></div>
  </div>
  <div class="card" style="padding:18px;display:flex;align-items:center;gap:14px">
    <div class="stat-icon" style="background:linear-gradient(135deg,var(--cyan),#0e7490);width:44px;height:44px;font-size:18px"><i class="fas fa-photo-video"></i></div>
    <div><div style="font-size:11.5px;color:var(--gray);font-weight:600;text-transform:uppercase">Photos This Month</div><div style="font-size:24px;font-weight:800;color:var(--primary)"><?= $thisMonth ?></div></div>
  </div>
</div>

<?php if (empty($albums)): ?>
<div class="card" style="text-align:center;padding:60px 20px">
  <div style="width:80px;height:80px;background:var(--light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:32px;color:var(--gray2)"><i class="fas fa-images"></i></div>
  <h3 style="font-size:18px;font-weight:700;color:var(--primary);margin-bottom:8px">No albums yet</h3>
  <p style="color:var(--gray);font-size:14px;margin-bottom:20px">Create your first gallery album to start organizing your photos.</p>
  <button class="btn btn-primary" onclick="openModal('createAlbumModal')"><i class="fas fa-plus"></i> Create First Album</button>
</div>
<?php else: ?>
<div class="album-grid">
  <?php foreach ($albums as $alb): ?>
  <div class="album-card">
    <a href="?view=album&album=<?= $alb['id'] ?>" style="display:block">
      <?php if (!empty($alb['cover_image'])): ?>
      <img src="<?= UPLOADS_URL . '/' . e($alb['cover_image']) ?>" class="album-cover" alt="<?= e($alb['title']) ?>" loading="lazy">
      <?php else: ?>
      <div class="album-cover-placeholder"><i class="fas fa-images"></i></div>
      <?php endif; ?>
    </a>
    <span class="album-status"><span class="badge <?= $alb['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $alb['is_active'] ? 'Active' : 'Hidden' ?></span></span>
    <div class="album-info">
      <div class="album-title" title="<?= e($alb['title']) ?>"><?= e($alb['title']) ?></div>
      <div class="album-meta">
        <span><i class="fas fa-image" style="font-size:10px"></i> <?= $alb['item_count'] ?> image<?= $alb['item_count'] != 1 ? 's' : '' ?></span>
        <span><?= timeAgo($alb['created_at']) ?></span>
      </div>
    </div>
    <div class="album-actions">
      <a href="?view=album&album=<?= $alb['id'] ?>" class="btn btn-primary btn-sm" style="flex:1"><i class="fas fa-folder-open"></i> Open</a>
      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this album and ALL its images?')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete_album">
        <input type="hidden" name="id" value="<?= $alb['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete album"><i class="fas fa-trash"></i></button>
      </form>
      <form method="POST" style="display:inline">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="toggle_album">
        <input type="hidden" name="id" value="<?= $alb['id'] ?>">
        <button type="submit" class="btn btn-secondary btn-sm btn-icon" title="<?= $alb['is_active'] ? 'Hide' : 'Publish' ?>">
          <i class="fas fa-<?= $alb['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
        </button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($view === 'album' && $albumId): ?>

<!-- ── ALBUM DETAIL VIEW ── -->
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray);margin-bottom:6px">
      <a href="<?= ADMIN_URL ?>/pages/gallery.php" style="color:var(--gray);display:flex;align-items:center;gap:4px"><i class="fas fa-images"></i> Gallery</a>
      <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <span><?= e($album['title']) ?></span>
    </div>
    <h1><?= e($album['title']) ?></h1>
    <p><?= count($items) ?> image<?= count($items) != 1 ? 's' : '' ?><?= $album['description'] ? ' · ' . e(truncate($album['description'], 60)) : '' ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-secondary btn-sm" onclick="openModal('editAlbumModal')"><i class="fas fa-pen"></i> Edit Album</button>
    <button class="btn btn-primary btn-sm" onclick="openModal('uploadModal')"><i class="fas fa-cloud-upload-alt"></i> Upload Images</button>
  </div>
</div>

<?php if (empty($items)): ?>
<div class="card" style="text-align:center;padding:60px 20px">
  <div style="width:80px;height:80px;background:var(--light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:32px;color:var(--gray2)"><i class="fas fa-photo-video"></i></div>
  <h3 style="font-size:18px;font-weight:700;color:var(--primary);margin-bottom:8px">No images yet</h3>
  <p style="color:var(--gray);font-size:14px;margin-bottom:20px">Upload your first images to this album.</p>
  <button class="btn btn-primary" onclick="openModal('uploadModal')"><i class="fas fa-cloud-upload-alt"></i> Upload Images</button>
</div>
<?php else: ?>
<div class="gallery-grid" id="sortableGrid">
  <?php foreach ($items as $item): ?>
  <div class="gallery-item" data-id="<?= $item['id'] ?>">
    <?php if (!empty($album['cover_image']) && $album['cover_image'] === $item['file_path']): ?>
    <span class="gi-cover-badge"><i class="fas fa-star" style="font-size:9px"></i> Cover</span>
    <?php endif; ?>
    <div class="gi-drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></div>
    <img src="<?= UPLOADS_URL . '/' . $item['file_path'] ?>" class="gi-img" loading="lazy" alt="<?= e($item['title'] ?? '') ?>"
         onclick="openLightbox(<?= $item['id'] ?>, '<?= addslashes(UPLOADS_URL . '/' . $item['file_path']) ?>', '<?= addslashes(e($item['title'] ?? '')) ?>')">
    <div class="gi-overlay">
      <button class="gi-action-btn zoom" title="View" onclick="openLightbox(<?= $item['id'] ?>, '<?= addslashes(UPLOADS_URL . '/' . $item['file_path']) ?>', '<?= addslashes(e($item['title'] ?? '')) ?>')">
        <i class="fas fa-expand"></i>
      </button>
      <button class="gi-action-btn star" title="Set as album cover" onclick="setCover(<?= $item['id'] ?>, <?= $albumId ?>, this)">
        <i class="fas fa-star"></i>
      </button>
      <button class="gi-action-btn del" title="Delete" onclick="deleteItem(<?= $item['id'] ?>, this)">
        <i class="fas fa-trash"></i>
      </button>
    </div>
    <div class="gi-footer">
      <div class="gi-title"><?= e($item['title'] ?: basename($item['file_path'])) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ── Create Album Modal ── -->
<div class="gal-modal" id="createAlbumModal">
  <div class="gal-modal-box sm">
    <div class="gal-modal-header">
      <h3><i class="fas fa-plus-circle" style="color:var(--violet);margin-right:8px"></i>New Album</h3>
      <button class="gal-modal-close" onclick="closeModal('createAlbumModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create_album">
      <div class="form-group">
        <label>Album Title <span class="required">*</span></label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Campus Life 2024" required>
      </div>
      <div class="form-group">
        <label>Description <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this album…"></textarea>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Album</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('createAlbumModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Upload Modal ── -->
<?php if ($view === 'album' && $albumId): ?>
<div class="gal-modal" id="uploadModal">
  <div class="gal-modal-box md">
    <div class="gal-modal-header">
      <h3><i class="fas fa-cloud-upload-alt" style="color:var(--violet);margin-right:8px"></i>Upload Images</h3>
      <button class="gal-modal-close" onclick="closeModal('uploadModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="gal-drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
      <div class="dz-icon"><i class="fas fa-images"></i></div>
      <div style="font-size:15px;font-weight:700;color:var(--primary);margin-bottom:6px">Drop images here or click to browse</div>
      <div style="font-size:12.5px;color:var(--gray)">PNG, JPG, WebP, GIF — max 10MB per file</div>
    </div>
    <input type="file" id="fileInput" multiple accept="image/*" style="display:none" onchange="uploadFiles(this.files)">
    <div id="uploadProgress" style="margin-top:16px;display:none">
      <div style="height:6px;background:var(--border);border-radius:6px;overflow:hidden;margin-bottom:8px">
        <div id="progressBar" style="height:100%;background:linear-gradient(90deg,var(--violet),var(--blue));width:0%;transition:.3s;border-radius:6px"></div>
      </div>
      <div id="uploadStatus" style="font-size:13px;color:var(--gray);text-align:center"></div>
    </div>
    <div id="uploadedPreviews" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;margin-top:16px"></div>
  </div>
</div>

<!-- ── Edit Album Modal ── -->
<div class="gal-modal" id="editAlbumModal">
  <div class="gal-modal-box sm">
    <div class="gal-modal-header">
      <h3><i class="fas fa-pen" style="color:var(--violet);margin-right:8px"></i>Edit Album</h3>
      <button class="gal-modal-close" onclick="closeModal('editAlbumModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_album">
      <input type="hidden" name="id" value="<?= $albumId ?>">
      <div class="form-group">
        <label>Album Title <span class="required">*</span></label>
        <input type="text" name="title" class="form-control" value="<?= e($album['title']) ?>" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3"><?= e($album['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Cover Image <span style="font-weight:400;color:var(--gray)">(optional override)</span></label>
        <div style="border:2px dashed var(--border);border-radius:10px;padding:16px;text-align:center;cursor:pointer;position:relative;background:var(--light)">
          <input type="file" name="cover" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          <i class="fas fa-image" style="font-size:20px;color:var(--gray);display:block;margin-bottom:6px"></i>
          <div style="font-size:12px;color:var(--gray)">Click to upload new cover</div>
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('editAlbumModal')">Cancel</button>
        <form method="POST" style="margin-left:auto" onsubmit="return confirm('Delete this album and all its images?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete_album">
          <input type="hidden" name="id" value="<?= $albumId ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete Album</button>
        </form>
      </div>
    </form>
  </div>
</div>

<!-- ── Lightbox ── -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <button class="lightbox-nav prev" id="lbPrev" onclick="lbNav(-1)"><i class="fas fa-chevron-left"></i></button>
  <img src="" id="lightboxImg" alt="">
  <button class="lightbox-nav next" id="lbNext" onclick="lbNav(1)"><i class="fas fa-chevron-right"></i></button>
  <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<script>
const CSRF     = document.querySelector('input[name="_csrf_token"]')?.value || '';
const ALBUM_ID = <?= $albumId ?>;

/* ── Modal helpers ── */
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.gal-modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('open'); document.body.style.overflow=''; } }));

/* ── Drag & Drop Upload ── */
const dz = document.getElementById('dropZone');
if (dz) {
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
  dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('dragover'); uploadFiles(e.dataTransfer.files); });
}

function uploadFiles(files) {
  if (!files.length) return;
  const prog = document.getElementById('uploadProgress');
  const bar  = document.getElementById('progressBar');
  const stat = document.getElementById('uploadStatus');
  const prev = document.getElementById('uploadedPreviews');
  prog.style.display = 'block';
  let done = 0, failed = 0;
  Array.from(files).forEach(file => {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('gallery_id', ALBUM_ID);
    fd.append('_csrf_token', CSRF);
    fetch('', { method:'POST', body:fd })
      .then(r => r.json())
      .then(data => {
        done++;
        bar.style.width = ((done + failed) / files.length * 100) + '%';
        stat.textContent = 'Uploading ' + (done + failed) + ' of ' + files.length + '…';
        if (data.ok) {
          insertGridItem(data);
          // Small preview in modal
          const thumb = document.createElement('img');
          thumb.src = data.url;
          thumb.style.cssText = 'width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:2px solid var(--emerald)';
          prev.appendChild(thumb);
        } else {
          failed++;
          showToast('Upload failed: ' + data.error, 'error');
        }
        if ((done + failed) === files.length) {
          stat.innerHTML = '<span style="color:var(--emerald)"><i class="fas fa-check"></i> ' + done + ' image' + (done !== 1 ? 's' : '') + ' uploaded!</span>';
          setTimeout(() => { prog.style.display='none'; bar.style.width='0%'; }, 2000);
        }
      })
      .catch(() => { failed++; showToast('Upload error', 'error'); });
  });
}

function insertGridItem(data) {
  const grid = document.getElementById('sortableGrid');
  if (!grid) return;
  const div = document.createElement('div');
  div.className = 'gallery-item';
  div.dataset.id = data.id;
  const fname = data.path.split('/').pop();
  div.innerHTML = `
    <div class="gi-drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></div>
    <img src="${data.url}" class="gi-img" loading="lazy" alt="${fname}" onclick="openLightbox(${data.id},'${data.url}','${fname}')">
    <div class="gi-overlay">
      <button class="gi-action-btn zoom" onclick="openLightbox(${data.id},'${data.url}','${fname}')"><i class="fas fa-expand"></i></button>
      <button class="gi-action-btn star" onclick="setCover(${data.id},${ALBUM_ID},this)"><i class="fas fa-star"></i></button>
      <button class="gi-action-btn del" onclick="deleteItem(${data.id},this)"><i class="fas fa-trash"></i></button>
    </div>
    <div class="gi-footer"><div class="gi-title">${fname}</div></div>`;
  grid.appendChild(div);
  // Remove empty state if present
  const empty = grid.querySelector('[style*="text-align:center"]');
  if (empty) empty.remove();
}

/* ── Delete image ── */
function deleteItem(id, btn) {
  if (!confirm('Delete this image?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_item');
  fd.append('id', id);
  fd.append('gallery_id', ALBUM_ID);
  fd.append('_csrf', CSRF);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        btn.closest('.gallery-item')?.remove();
        showToast('Image deleted.', 'success');
      }
    });
}

/* ── Set cover ── */
function setCover(itemId, galleryId, btn) {
  const fd = new FormData();
  fd.append('action', 'set_cover');
  fd.append('item_id', itemId);
  fd.append('gallery_id', galleryId);
  fd.append('_csrf', CSRF);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        // Remove all cover badges
        document.querySelectorAll('.gi-cover-badge').forEach(b => b.remove());
        const card = btn.closest('.gallery-item');
        const badge = document.createElement('span');
        badge.className = 'gi-cover-badge';
        badge.innerHTML = '<i class="fas fa-star" style="font-size:9px"></i> Cover';
        card.prepend(badge);
        showToast('Cover image set!', 'success');
      }
    });
}

/* ── Lightbox ── */
let lbItems = [];
let lbIndex = 0;

document.querySelectorAll('.gallery-item').forEach((item, i) => {
  const img = item.querySelector('.gi-img');
  if (img) lbItems.push({ url: img.src, title: img.alt, id: +item.dataset.id });
});

function openLightbox(id, url, title) {
  lbIndex = lbItems.findIndex(it => it.id === id);
  if (lbIndex < 0) { lbIndex = 0; lbItems.unshift({id, url, title}); }
  document.getElementById('lightboxImg').src = url;
  document.getElementById('lightboxCaption').textContent = title || '';
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
  updateLbNav();
}

function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}

function lbNav(dir) {
  lbIndex = (lbIndex + dir + lbItems.length) % lbItems.length;
  const item = lbItems[lbIndex];
  document.getElementById('lightboxImg').src = item.url;
  document.getElementById('lightboxCaption').textContent = item.title || '';
  updateLbNav();
}

function updateLbNav() {
  document.getElementById('lbPrev').style.display = lbItems.length > 1 ? '' : 'none';
  document.getElementById('lbNext').style.display = lbItems.length > 1 ? '' : 'none';
}

document.getElementById('lightbox')?.addEventListener('click', e => {
  if (e.target === document.getElementById('lightbox')) closeLightbox();
});
document.addEventListener('keydown', e => {
  if (!document.getElementById('lightbox').classList.contains('open')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft')  lbNav(-1);
  if (e.key === 'ArrowRight') lbNav(1);
});

/* ── Sortable drag reorder ── */
(function initSortable() {
  const grid = document.getElementById('sortableGrid');
  if (!grid) return;
  let dragging = null;
  grid.addEventListener('mousedown', e => {
    const handle = e.target.closest('.gi-drag-handle');
    if (!handle) return;
    dragging = handle.closest('.gallery-item');
    dragging.style.opacity = '.5';
  });
  grid.addEventListener('dragstart', e => {
    if (!dragging) return;
    e.dataTransfer.effectAllowed = 'move';
  });
  grid.querySelectorAll('.gallery-item').forEach(item => {
    item.setAttribute('draggable', 'true');
    item.addEventListener('dragover', e => {
      e.preventDefault();
      const after = item !== dragging;
      if (after) grid.insertBefore(dragging, item.nextSibling);
    });
    item.addEventListener('dragend', () => {
      if (dragging) { dragging.style.opacity = ''; dragging = null; saveOrder(); }
    });
  });
})();

function saveOrder() {
  const ids = [...document.querySelectorAll('#sortableGrid .gallery-item')].map(el => +el.dataset.id);
  const fd = new FormData();
  fd.append('action', 'reorder');
  ids.forEach(id => fd.append('ids[]', id));
  fd.append('_csrf', CSRF);
  fetch('', { method:'POST', body:fd }).then(r => r.json()).then(d => { if (d.ok) showToast('Order saved.', 'success'); });
}
</script>
<?php endif; ?>

<?php if ($view === 'albums'): ?>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.gal-modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('open'); document.body.style.overflow=''; } }));
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
