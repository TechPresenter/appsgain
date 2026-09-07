<?php
/**
 * Admin: Apps Management
 * Path: admin/pages/apps.php
 */
$adminPage  = 'apps';
$adminTitle = 'Apps Management';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? '';

// ═══════════════════════════════════════════════════════════════
// DELETE
// ═══════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        dbDelete('app_screenshots', 'app_id', $id);
        try { dbDelete('download_apps_settings', 'app_id', $id); } catch (Exception $e) {}
        dbDelete('apps', 'id', $id);
        redirect(ADMIN_URL . '/pages/apps.php?msg=deleted');
    }
    
    $app = dbFetchOne("SELECT * FROM apps WHERE id = ?", [$id]);
    if (!$app) {
        redirect("admin/pages/apps.php?msg=error");
    }
}

// ═══════════════════════════════════════════════════════════════
// EDIT/CREATE
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'edit' || $action === 'create') {
    $app = $id ? dbFetchOne("SELECT * FROM apps WHERE id = ?", [$id]) : null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $app_name = trim($_POST['app_name'] ?? '');
        $app_category = trim($_POST['app_category'] ?? '');
        $app_type = trim($_POST['app_type'] ?? 'android');
        $developer_name = trim($_POST['developer_name'] ?? '');
        $short_desc = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['app_description'] ?? '');
        $google_play_url = trim($_POST['google_play_url'] ?? '');
        $app_store_url = trim($_POST['app_store_url'] ?? '');
        $app_rating = floatval($_POST['app_rating'] ?? 0);
        $total_downloads = trim($_POST['total_downloads'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $seo_title = trim($_POST['seo_title'] ?? '');
        $seo_desc = trim($_POST['seo_description'] ?? '');
        $seo_keywords = trim($_POST['seo_keywords'] ?? '');
        
        if (!$app_name || !$app_category) {
            $msg = 'error_required';
        } else {
            $app_slug = $app['app_slug'] ?? slugify($app_name);
            
            // Handle icon upload — manual upload takes priority over auto-fetched
            $app_icon = $app['app_icon'] ?? '';
            if (!empty($_FILES['app_icon']['name'])) {
                $upload = uploadFile($_FILES['app_icon'], 'apps/icons');
                if (!empty($upload['success'])) $app_icon = $upload['path'];
            } elseif (!empty($_POST['icon_path_fetched'])) {
                /* Use the auto-downloaded icon from Play Store */
                $app_icon = sanitizeInput($_POST['icon_path_fetched']);
            }

            /* Save screenshots list fetched from Play Store */
            $screenshotsFetched = '';
            if (!empty($_POST['screenshots_fetched'])) {
                $ssArr = json_decode($_POST['screenshots_fetched'], true);
                if (is_array($ssArr)) $screenshotsFetched = json_encode($ssArr);
            }
            
            /* Auto-add columns if missing */
            try {
                $aCols = array_column(dbFetchAll("SHOW COLUMNS FROM apps"), 'Field');
                if (!in_array('package_id',      $aCols)) db()->exec("ALTER TABLE apps ADD COLUMN package_id VARCHAR(200) DEFAULT NULL");
                if (!in_array('screenshots_data',$aCols)) db()->exec("ALTER TABLE apps ADD COLUMN screenshots_data TEXT DEFAULT NULL");
                if (!in_array('last_updated',    $aCols)) db()->exec("ALTER TABLE apps ADD COLUMN last_updated DATE DEFAULT NULL");
            } catch(Exception $e) {}

            $data = [
                'app_name'          => $app_name,
                'app_slug'          => $app_slug,
                'app_category'      => $app_category,
                'app_type'          => $app_type,
                'app_icon'          => $app_icon,
                'developer_name'    => $developer_name,
                'short_description' => $short_desc,
                'app_description'   => $description,
                'google_play_url'   => $google_play_url,
                'app_store_url'     => $app_store_url,
                'app_rating'        => $app_rating,
                'total_downloads'   => $total_downloads,
                'is_featured'       => $is_featured,
                'is_active'         => $is_active,
                'seo_title'         => $seo_title,
                'seo_description'   => $seo_desc,
                'seo_keywords'      => $seo_keywords,
                'package_id'        => sanitizeInput($_POST['package_id'] ?? ''),
                'screenshots_data'  => $screenshotsFetched ?: null,
            ];
            
            if ($id) {
                dbUpdate('apps', $data, 'id', $id);
            } else {
                dbInsertRow('apps', $data);
                $id = db()->lastInsertId();
            }
            
            redirect(ADMIN_URL . '/pages/apps.php?msg=' . ($id ? 'updated' : 'created'));
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// LIST
// ═══════════════════════════════════════════════════════════════
$apps = dbFetchAll("SELECT * FROM apps ORDER BY sort_order ASC, created_at DESC");

require dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="admin-content">
    <div class="admin-header">
        <div>
            <h2><i class="fas fa-mobile-alt"></i> Apps Management</h2>
            <p>Manage mobile applications</p>
        </div>
        <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New App
        </a>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg === 'error_required' ? 'danger' : ($msg === 'deleted' ? 'warning' : 'success') ?>">
        <?php 
        $messages = [
            'created' => 'App created successfully',
            'updated' => 'App updated successfully',
            'deleted' => 'App deleted successfully',
            'error_required' => 'Please fill all required fields',
            'error' => 'An error occurred'
        ];
        echo $messages[$msg] ?? $msg;
        ?>
    </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
    <!-- LIST VIEW -->
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>App Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apps as $a): ?>
                <tr>
                    <td>
                        <div class="table-cell-main">
                            <?php if ($a['app_icon']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $a['app_icon'] ?>" alt="<?= e($a['app_name']) ?>" style="height:35px;width:35px;border-radius:8px;margin-right:10px;">
                            <?php endif; ?>
                            <strong><?= e($a['app_name']) ?></strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            <?= ucfirst($a['app_type']) ?>
                        </span>
                    </td>
                    <td><?= e($a['app_category']) ?></td>
                    <td>
                        <span class="badge badge-warning">
                            <?= $a['app_rating'] > 0 ? $a['app_rating'] . '★' : 'N/A' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $a['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $a['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $a['is_featured'] ? 'badge-primary' : 'badge-light' ?>">
                            <?= $a['is_featured'] ? 'Featured' : 'Standard' ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $a['id'] ?>" class="btn btn-sm btn-info" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="?action=delete&id=<?= $a['id'] ?>" style="display:inline" onsubmit="return confirm('Delete this app?')">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($action === 'delete'): ?>
    <!-- DELETE CONFIRMATION -->
    <div class="card">
        <div class="card-body">
            <h4 style="margin-bottom:16px"><i class="fas fa-exclamation-triangle text-danger"></i> Delete App</h4>
            <p>Are you sure you want to delete <strong><?= e($app['app_name']) ?></strong>?</p>
            <p class="text-muted">This action will also delete all associated screenshots and settings.</p>
            
            <form method="POST">
                <?= csrfField() ?>
                <div class="button-group">
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($action === 'edit' || $action === 'create'): ?>
    <!-- EDIT/CREATE FORM -->
    <div class="card">
        <div class="card-header">
            <h4><?= $action === 'create' ? 'Create New App' : 'Edit App' ?></h4>
        </div>
        <div class="card-body">

          <!-- ══ PLAY STORE AUTO-IMPORT ══ -->
          <div id="playImportBox" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:2px solid #bbf7d0;border-radius:16px;padding:20px 22px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
              <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#16a34a,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M3.18 23.76a2 2 0 0 0 2.2-.2l11.6-6.7-2.9-2.9-10.9 9.8zm-1.7-20.6a2 2 0 0 0-.48 1.32v16.96c0 .49.17.94.47 1.3L2 23l9.51-9.51V13.4L2 3.89l-.52.27zm19.3 8.5L17.93 9.4l-3.12 3.11 3.12 3.12 2.87-1.66a1.73 1.73 0 0 0 0-3.31zM5.38.44a2 2 0 0 0-2.2-.2L3.7.51l9.51 9.51 2.9-2.9L5.38.44z"/></svg>
              </div>
              <div>
                <div style="font-size:15px;font-weight:800;color:#15803d;">Import from Google Play Store</div>
                <div style="font-size:12.5px;color:#166534;margin-top:2px;">Paste a Play Store URL or package ID — all fields auto-populate instantly</div>
              </div>
              <button type="button" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#4ade80;font-size:18px;padding:4px;" onclick="document.getElementById('playImportBox').style.display='none'" title="Close">×</button>
            </div>
            <div style="display:flex;gap:10px;">
              <input type="text" id="playStoreUrlInput" placeholder="https://play.google.com/store/apps/details?id=com.example.app  OR  com.example.app"
                style="flex:1;padding:10px 14px;border:1.5px solid #86efac;border-radius:10px;font-size:13.5px;outline:none;background:#fff;color:#0f172a;"
                onkeydown="if(event.key==='Enter'){event.preventDefault();fetchPlayStoreData();}">
              <button type="button" id="playFetchBtn" onclick="fetchPlayStoreData()"
                style="padding:10px 20px;background:linear-gradient(135deg,#16a34a,#059669);color:#fff;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:7px;">
                <i class="fas fa-download"></i> <span id="fetchBtnTxt">Fetch Details</span>
              </button>
            </div>
            <div id="playFetchStatus" style="margin-top:10px;display:none;"></div>
          </div>

            <form method="POST" enctype="multipart/form-data" id="appForm">
                <?= csrfField() ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="app_name">App Name *</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" 
                               value="<?= e($app['app_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="app_category">Category *</label>
                        <input type="text" class="form-control" id="app_category" name="app_category" 
                               value="<?= e($app['app_category'] ?? '') ?>" placeholder="e.g., Business, HR, ERP" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="app_type">App Type *</label>
                        <select class="form-control" id="app_type" name="app_type" required>
                            <option value="android" <?= ($app['app_type'] ?? 'android') === 'android' ? 'selected' : '' ?>>Android</option>
                            <option value="ios" <?= ($app['app_type'] ?? '') === 'ios' ? 'selected' : '' ?>>iOS</option>
                            <option value="both" <?= ($app['app_type'] ?? '') === 'both' ? 'selected' : '' ?>>Both (Android & iOS)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="developer_name">Developer Name</label>
                        <input type="text" class="form-control" id="developer_name" name="developer_name" 
                               value="<?= e($app['developer_name'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="app_icon">App Icon</label>
                        <input type="file" class="form-control" id="app_icon" name="app_icon" accept="image/*">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="google_play_url">Google Play Store URL</label>
                        <input type="url" class="form-control" id="google_play_url" name="google_play_url" 
                               value="<?= e($app['google_play_url'] ?? '') ?>" placeholder="https://play.google.com/store/apps/details?id=...">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="app_store_url">Apple App Store URL</label>
                        <input type="url" class="form-control" id="app_store_url" name="app_store_url" 
                               value="<?= e($app['app_store_url'] ?? '') ?>" placeholder="https://apps.apple.com/app/...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="app_rating">App Rating</label>
                        <input type="number" class="form-control" id="app_rating" name="app_rating" 
                               value="<?= e($app['app_rating'] ?? '') ?>" min="0" max="5" step="0.1">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="total_downloads">Total Downloads</label>
                        <input type="text" class="form-control" id="total_downloads" name="total_downloads" 
                               value="<?= e($app['total_downloads'] ?? '') ?>" placeholder="e.g., 10K, 1M, 500K+">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="last_updated">Last Updated</label>
                        <input type="date" class="form-control" id="last_updated" name="last_updated" 
                               value="<?= e($app['last_updated'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= e($app['short_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="app_description">Full Description</label>
                    <textarea class="form-control" id="app_description" name="app_description" rows="6"><?= htmlspecialchars($app['app_description'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- SEO Section -->
                <hr>
                <h5>SEO Settings</h5>
                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input type="text" class="form-control" id="seo_title" name="seo_title" 
                           value="<?= e($app['seo_title'] ?? '') ?>" maxlength="255">
                </div>
                <div class="form-group">
                    <label for="seo_description">SEO Description</label>
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="2" maxlength="500"><?= e($app['seo_description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="seo_keywords">SEO Keywords</label>
                    <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" 
                           value="<?= e($app['seo_keywords'] ?? '') ?>">
                </div>

                <!-- Auto-fetched extras -->
                <input type="hidden" id="icon_path_fetched" name="icon_path_fetched" value="">
                <input type="hidden" id="screenshots_fetched" name="screenshots_fetched" value="">
                <input type="hidden" id="package_id" name="package_id" value="<?= e($app['package_id'] ?? '') ?>">

                <!-- App icon preview from fetch -->
                <div id="iconPreviewWrap" style="display:none;margin-bottom:16px;">
                  <div class="form-group">
                    <label>App Icon (Auto-fetched from Play Store)</label>
                    <div style="display:flex;align-items:center;gap:16px;">
                      <img id="iconPreviewImg" src="" alt="App Icon" style="width:72px;height:72px;border-radius:16px;object-fit:cover;border:2px solid var(--border);box-shadow:0 4px 12px rgba(0,0,0,.12);">
                      <div style="font-size:13px;color:var(--gray);">Icon saved automatically. You can override it by uploading a file above.</div>
                    </div>
                  </div>
                </div>

                <!-- Screenshots preview -->
                <div id="screenshotsPreviewWrap" style="display:none;margin-bottom:16px;">
                  <div class="form-group">
                    <label>Screenshots (Auto-fetched from Play Store)</label>
                    <div id="screenshotsPreviewRow" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
                    <div style="font-size:12.5px;color:var(--gray);margin-top:6px;">Screenshots saved to your server automatically.</div>
                  </div>
                </div>

                <!-- Fetched metadata display -->
                <div id="fetchedMetaWrap" style="display:none;margin-bottom:16px;">
                  <div style="background:var(--light2);border-radius:12px;padding:14px 16px;border:1px solid var(--border);">
                    <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">Additional Data from Play Store</div>
                    <div id="fetchedMetaContent" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;font-size:13px;"></div>
                  </div>
                </div>

                <!-- Options -->
                <hr>
                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured"
                           <?= ($app['is_featured'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Mark as Featured App</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                           <?= ($app['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                  </div>
                </div>

                <div class="button-group" style="display:flex;gap:10px;margin-top:24px">
                    <button type="submit" class="btn btn-success" style="padding:11px 28px;font-size:14px;">
                        <i class="fas fa-save"></i> Save App
                    </button>
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

.table-cell-main {
    display: flex;
    align-items: center;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-secondary {
    background-color: #6c757d;
    color: white;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

.badge-warning {
    background-color: #ffc107;
    color: #333;
}

.badge-primary {
    background-color: #007bff;
    color: white;
}

.badge-light {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #dee2e6;
}
</style>
<?php if (in_array($action??'', ['create','edit'])): ?>
<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('app_description', { height: 340, placeholder: 'Write the full app description here…' });
});

/* ════ PLAY STORE AUTO-FETCH ════ */
function fetchPlayStoreData() {
    var input  = document.getElementById('playStoreUrlInput');
    var btn    = document.getElementById('playFetchBtn');
    var btnTxt = document.getElementById('fetchBtnTxt');
    var status = document.getElementById('playFetchStatus');
    var url    = input ? input.value.trim() : '';

    if (!url) { showFetchStatus('error', 'Please enter a Play Store URL or package ID.'); return; }

    /* Loading state */
    btn.disabled = true;
    btnTxt.textContent = 'Fetching…';
    btn.style.opacity = '.7';
    showFetchStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Connecting to Play Store and downloading app data…');

    var csrfEl = document.querySelector('[name="<?= CSRF_TOKEN_NAME ?>"]');
    var csrf   = csrfEl ? csrfEl.value : '';
    var fd   = new FormData();
    fd.append('url', url);
    fd.append('<?= CSRF_TOKEN_NAME ?>', csrf);

    fetch('<?= ADMIN_URL ?>/api/fetch-play-store.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
    })
    .then(function(r) {
        if (!r.ok) {
            return r.text().then(function(t) {
                throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(function(d) {
        btn.disabled = false;
        btnTxt.textContent = 'Fetch Details';
        btn.style.opacity = '1';

        if (!d.ok) {
            showFetchStatus('error', '<i class="fas fa-exclamation-circle"></i> ' + (d.message || 'Fetch failed.'));
            return;
        }

        var app = d.data;
        populateField('app_name',       app.app_name);
        populateField('developer_name', app.developer_name);
        populateField('app_category',   app.app_category);
        populateField('app_rating',     app.app_rating);
        populateField('total_downloads',app.total_downloads);
        populateField('short_description', app.short_description);
        populateField('google_play_url', app.google_play_url);
        populateField('package_id',      app.package_id);
        populateField('seo_title',       app.app_name + (app.app_category ? ' - ' + app.app_category : ''));

        /* App type select */
        var typeEl = document.getElementById('app_type');
        if (typeEl && app.app_type) typeEl.value = app.app_type;

        /* CKEditor description */
        if (app.app_description) {
            var ck = window.CKEditorInstances && window.CKEditorInstances['app_description'];
            if (ck) {
                ck.setData(app.app_description);
            } else {
                var ta = document.getElementById('app_description');
                if (ta) ta.value = app.app_description;
            }
        }

        /* App icon preview + hidden path */
        if (app.icon_path) {
            document.getElementById('icon_path_fetched').value = app.icon_path;
            var iconWrap = document.getElementById('iconPreviewWrap');
            var iconImg  = document.getElementById('iconPreviewImg');
            if (iconWrap && iconImg) {
                iconImg.src = app.icon_preview_url || '';
                iconWrap.style.display = 'block';
            }
        }

        /* Screenshots */
        if (app.screenshots && app.screenshots.length > 0) {
            document.getElementById('screenshots_fetched').value = JSON.stringify(app.screenshots);
            var ssWrap = document.getElementById('screenshotsPreviewWrap');
            var ssRow  = document.getElementById('screenshotsPreviewRow');
            if (ssWrap && ssRow) {
                ssRow.innerHTML = '';
                app.screenshot_urls.forEach(function(ssUrl) {
                    var img = document.createElement('img');
                    img.src = ssUrl;
                    img.style.cssText = 'height:90px;width:auto;border-radius:8px;border:2px solid var(--border);object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,.1);';
                    ssRow.appendChild(img);
                });
                ssWrap.style.display = 'block';
            }
        }

        /* Extra metadata strip */
        var meta = [];
        if (app.rating_count) meta.push(['Reviews', app.rating_count]);
        if (app.app_version)  meta.push(['Version', app.app_version]);
        if (app.app_size)     meta.push(['Size', app.app_size]);
        if (app.os)           meta.push(['Platform', app.os]);
        if (meta.length > 0) {
            var metaWrap    = document.getElementById('fetchedMetaWrap');
            var metaContent = document.getElementById('fetchedMetaContent');
            if (metaWrap && metaContent) {
                metaContent.innerHTML = '';
                meta.forEach(function(item) {
                    var div = document.createElement('div');
                    div.style.cssText = 'background:#fff;padding:8px 12px;border-radius:9px;border:1px solid var(--border);';
                    div.innerHTML = '<div style="font-size:10.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;">' + item[0] + '</div>'
                                  + '<div style="font-size:14px;font-weight:700;color:var(--primary);margin-top:2px;">' + item[1] + '</div>';
                    metaContent.appendChild(div);
                });
                metaWrap.style.display = 'block';
            }
        }

        showFetchStatus('success',
            '<i class="fas fa-check-circle"></i> ' + d.message +
            (app.screenshots.length ? ' + ' + app.screenshots.length + ' screenshots' : '') +
            '. All fields populated — review and save.');

        /* Scroll to form */
        setTimeout(function() {
            var nameField = document.getElementById('app_name');
            if (nameField) nameField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 400);
    })
    .catch(function(err) {
        btn.disabled = false;
        btnTxt.textContent = 'Fetch Details';
        btn.style.opacity = '1';
        showFetchStatus('error', '<i class="fas fa-exclamation-triangle"></i> ' + (err.message || 'Network error — check your internet connection and that cURL is enabled.'));
        console.error('Play Store Fetch Error:', err);
    });
}

function populateField(id, val) {
    if (!val && val !== 0) return;
    var el = document.getElementById(id);
    if (el) {
        el.value = val;
        /* Flash highlight */
        el.style.transition = 'background .3s';
        el.style.background = '#f0fdf4';
        setTimeout(function() { el.style.background = ''; }, 1800);
    }
}

function showFetchStatus(type, msg) {
    var el = document.getElementById('playFetchStatus');
    if (!el) return;
    var colors = {
        loading : { bg:'rgba(37,99,235,.08)',  border:'rgba(37,99,235,.2)',  color:'#1e40af' },
        success : { bg:'rgba(5,150,105,.08)',  border:'rgba(5,150,105,.2)', color:'#065f46' },
        error   : { bg:'rgba(225,29,72,.08)', border:'rgba(225,29,72,.2)',  color:'#9f1239' },
    };
    var c = colors[type] || colors.loading;
    el.style.cssText = 'display:block;padding:10px 14px;border-radius:10px;font-size:13.5px;font-weight:600;'
        + 'background:' + c.bg + ';border:1px solid ' + c.border + ';color:' + c.color + ';';
    el.innerHTML = msg;
}
</script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin-foot.php'; ?>
