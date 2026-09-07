<?php
/**
 * Admin: Products Management
 * Path: admin/pages/products.php
 */
$adminPage  = 'products';
$adminTitle = 'Products Management';
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
        try { dbDelete('product_screenshots', 'product_id', $id); } catch (Exception $e) {}
        try { dbDelete('product_modules',     'product_id', $id); } catch (Exception $e) {}
        try { dbDelete('product_pricing_plans','product_id', $id); } catch (Exception $e) {}
        try { dbDelete('product_faqs',        'product_id', $id); } catch (Exception $e) {}
        dbDelete('products', 'id', $id);
        redirect(ADMIN_URL . '/pages/products.php?msg=deleted');
    }
    $product = dbFetchOne("SELECT * FROM products WHERE id = ?", [$id]);
    if (!$product) redirect(ADMIN_URL . '/pages/products.php?msg=error');
}

// ═══════════════════════════════════════════════════════════════
// EDIT/CREATE
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'edit' || $action === 'create') {
    $product = $id ? dbFetchOne("SELECT * FROM products WHERE id = ?", [$id]) : null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $short_desc = trim($_POST['short_description'] ?? '');
        $detailed_desc = trim($_POST['detailed_description'] ?? '');
        $demo_url = trim($_POST['demo_url'] ?? '');
        $video_url = trim($_POST['video_url'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $seo_title = trim($_POST['seo_title'] ?? '');
        $seo_desc = trim($_POST['seo_description'] ?? '');
        $seo_keywords = trim($_POST['seo_keywords'] ?? '');
        
        if (!$name || !$category) {
            $msg = 'error_required';
        } else {
            $slug = $product['slug'] ?? slugify($name);
            
            // Handle logo upload
            $logo = $product['logo'] ?? '';
            if (!empty($_FILES['logo']['name'])) {
                $up = uploadFile($_FILES['logo'], 'products/logos');
                if (!empty($up['success'])) $logo = $up['path'];
            }
            // Handle banner upload
            $banner = $product['banner_image'] ?? '';
            if (!empty($_FILES['banner_image']['name'])) {
                $up = uploadFile($_FILES['banner_image'], 'products/banners');
                if (!empty($up['success'])) $banner = $up['path'];
            }
            // Handle brochure upload
            $brochure = $product['brochure_file'] ?? '';
            if (!empty($_FILES['brochure_file']['name'])) {
                $up = uploadFile($_FILES['brochure_file'], 'products/brochures');
                if (!empty($up['success'])) $brochure = $up['path'];
            }
            
            $data = [
                'name' => $name,
                'slug' => $slug,
                'category' => $category,
                'logo' => $logo,
                'banner_image' => $banner,
                'short_description' => $short_desc,
                'detailed_description' => $detailed_desc,
                'demo_url' => $demo_url,
                'video_url' => $video_url,
                'is_featured' => $is_featured,
                'is_active' => $is_active,
                'seo_title' => $seo_title,
                'seo_description' => $seo_desc,
                'seo_keywords' => $seo_keywords
            ];
            
            if ($id) {
                dbUpdate('products', $data, 'id', $id);
            } else {
                dbInsertRow('products', $data);
                $id = db()->lastInsertId();
            }
            
            /* Both list fields arrive as one item per line. */
            $lines = static function (string $raw): array {
                return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
            };
            /* JSON_INVALID_UTF8_SUBSTITUTE so a stray byte cannot make
               json_encode return false and blank the whole list. */
            $asJson = static function (array $rows): string {
                $j = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                return $j === false ? '[]' : $j;
            };

            dbUpdate('products', [
                'key_features' => $asJson($lines((string)($_POST['key_features'] ?? ''))),
                'benefits'     => $asJson($lines((string)($_POST['benefits'] ?? ''))),
            ], 'id', $id);

            /* Modules is a textarea, so it has to be split before it is looped. */
            if (isset($_POST['modules'])) {
                try { dbDelete('product_modules', 'product_id', $id); } catch (Exception $e) {}
                $sort = 0;
                foreach ($lines((string)$_POST['modules']) as $module) {
                    try {
                        dbInsertRow('product_modules', [
                            'product_id'  => $id,
                            'module_name' => $module,
                            'sort_order'  => $sort++,
                        ]);
                    } catch (Exception $e) {}
                }
            }
            redirect(ADMIN_URL . '/pages/products.php?msg=' . ($id ? 'updated' : 'created'));
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// LIST
// ═══════════════════════════════════════════════════════════════
$products = dbFetchAll("SELECT * FROM products ORDER BY sort_order ASC, created_at DESC");

require dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="admin-content">
    <div class="admin-header">
        <div>
            <h2><i class="fas fa-box"></i> Products Management</h2>
            <p>Manage all product offerings</p>
        </div>
        <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg === 'error_required' ? 'danger' : ($msg === 'deleted' ? 'warning' : 'success') ?>">
        <?php 
        $messages = [
            'created' => 'Product created successfully',
            'updated' => 'Product updated successfully',
            'deleted' => 'Product deleted successfully',
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
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="table-cell-main">
                            <?php if ($p['logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $p['logo'] ?>" alt="<?= e($p['name']) ?>" style="height:30px;margin-right:10px;">
                            <?php endif; ?>
                            <strong><?= e($p['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($p['category']) ?></td>
                    <td>
                        <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $p['is_featured'] ? 'badge-info' : 'badge-light' ?>">
                            <?= $p['is_featured'] ? 'Featured' : 'Standard' ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-info" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="?action=delete&id=<?= $p['id'] ?>" style="display:inline" onsubmit="return confirm('Delete this product?')">
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
            <h4 style="margin-bottom:16px"><i class="fas fa-exclamation-triangle text-danger"></i> Delete Product</h4>
            <p>Are you sure you want to delete <strong><?= e($product['name']) ?></strong>?</p>
            <p class="text-muted">This action will also delete all associated data (screenshots, modules, pricing plans, FAQs).</p>
            
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
            <h4><?= $action === 'create' ? 'Create New Product' : 'Edit Product' ?></h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="name">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= e($product['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="category">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Education" <?= ($product['category'] ?? '') === 'Education' ? 'selected' : '' ?>>Education</option>
                            <option value="Hospitality" <?= ($product['category'] ?? '') === 'Hospitality' ? 'selected' : '' ?>>Hospitality</option>
                            <option value="Real Estate" <?= ($product['category'] ?? '') === 'Real Estate' ? 'selected' : '' ?>>Real Estate</option>
                            <option value="Sales & CRM" <?= ($product['category'] ?? '') === 'Sales & CRM' ? 'selected' : '' ?>>Sales & CRM</option>
                            <option value="Enterprise" <?= ($product['category'] ?? '') === 'Enterprise' ? 'selected' : '' ?>>Enterprise</option>
                            <option value="Human Resources" <?= ($product['category'] ?? '') === 'Human Resources' ? 'selected' : '' ?>>Human Resources</option>
                            <option value="Healthcare" <?= ($product['category'] ?? '') === 'Healthcare' ? 'selected' : '' ?>>Healthcare</option>
                            <option value="Inventory" <?= ($product['category'] ?? '') === 'Inventory' ? 'selected' : '' ?>>Inventory</option>
                            <option value="Custom" <?= ($product['category'] ?? '') === 'Custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="logo">Product Logo</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <?php if ($product && $product['logo']): ?>
                        <small class="form-text text-muted">Current: <img src="<?= UPLOADS_URL ?>/<?= $product['logo'] ?>" style="height:30px;"></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="banner_image">Banner Image</label>
                        <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= e($product['short_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="detailed_description">Detailed Description</label>
                    <textarea class="form-control" id="detailed_description" name="detailed_description" rows="6"><?= htmlspecialchars($product['detailed_description'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
                </div>

                <?php
                /* Stored as a JSON array; shown one per line for editing. */
                $asLines = static function ($json): string {
                    $rows = json_decode((string)$json, true);
                    return is_array($rows) ? implode("\n", array_map('strval', $rows)) : '';
                };
                $curModules = [];
                if ($product) {
                    try {
                        $curModules = array_column(
                            dbFetchAll("SELECT module_name FROM product_modules WHERE product_id=? ORDER BY sort_order", [$product['id']]),
                            'module_name'
                        );
                    } catch (Exception $e) {}
                }
                ?>
                <div class="form-group">
                    <label>Key Features (one per line)</label>
                    <textarea class="form-control" name="key_features" rows="5" placeholder="Role-based access control&#10;Automated fee reminders&#10;Parent mobile app"><?= e($asLines($product['key_features'] ?? '')) ?></textarea>
                    <small class="form-text text-muted">Shown as the feature grid on the product page.</small>
                </div>

                <div class="form-group">
                    <label>Benefits (one per line)</label>
                    <textarea class="form-control" name="benefits" rows="5" placeholder="Cuts admissions paperwork by 60%&#10;One source of truth for fees"><?= e($asLines($product['benefits'] ?? '')) ?></textarea>
                    <small class="form-text text-muted">Outcomes for the buyer, rather than features.</small>
                </div>

                <div class="form-group">
                    <label>Modules (one per line)</label>
                    <textarea class="form-control" name="modules" rows="4" placeholder="Admissions&#10;Attendance&#10;Fees"><?= e(implode("\n", $curModules)) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="demo_url">Demo URL</label>
                        <input type="url" class="form-control" id="demo_url" name="demo_url" value="<?= e($product['demo_url'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="video_url">Video URL</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" value="<?= e($product['video_url'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="brochure_file">Download Brochure (PDF)</label>
                    <input type="file" class="form-control" id="brochure_file" name="brochure_file" accept=".pdf">
                </div>

                <!-- SEO Section -->
                <hr>
                <h5>SEO Settings</h5>
                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input type="text" class="form-control" id="seo_title" name="seo_title" value="<?= e($product['seo_title'] ?? '') ?>" maxlength="255">
                </div>
                <div class="form-group">
                    <label for="seo_description">SEO Description</label>
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="2" maxlength="500"><?= e($product['seo_description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="seo_keywords">SEO Keywords</label>
                    <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" value="<?= e($product['seo_keywords'] ?? '') ?>">
                </div>

                <!-- Options -->
                <hr>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" 
                           <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">
                        Mark as Featured Product
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                           <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>

                <div class="button-group" style="margin-top:24px">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Product
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

.badge-light {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #dee2e6;
}
</style>
<?php if (in_array($action??'', ['add','edit'])): ?>
<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('detailed_description', { height: 340, placeholder: 'Write the detailed product description here…' });
});
</script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin-foot.php'; ?>
