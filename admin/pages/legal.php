<?php
$adminTitle = 'Legal Pages Manager';
$adminPage  = 'legal';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$pageKey = sanitizeInput($_GET['page'] ?? 'privacy');

$legalPages = [
  'privacy'   => [
    'label'    => 'Privacy Policy',
    'icon'     => 'fa-shield-alt',
    'color'    => '#7c3aed',
    'url'      => '/privacy-policy.php',
    'desc'     => 'How you collect, use, and protect personal data',
  ],
  'terms'     => [
    'label'    => 'Terms & Conditions',
    'icon'     => 'fa-file-contract',
    'color'    => '#2563eb',
    'url'      => '/terms-of-service.php',
    'desc'     => 'Rules governing use of your website and services',
  ],
  'cookie'    => [
    'label'    => 'Cookie Policy',
    'icon'     => 'fa-cookie-bite',
    'color'    => '#d97706',
    'url'      => '/cookie-policy.php',
    'desc'     => 'Types of cookies used and how users can manage them',
  ],
  'refund'    => [
    'label'    => 'Refund & Cancellation',
    'icon'     => 'fa-undo-alt',
    'color'    => '#059669',
    'url'      => '/refund-policy.php',
    'desc'     => 'Project cancellation terms, refund eligibility and process',
  ],
  'shipping'  => [
    'label'    => 'Shipping & Delivery',
    'icon'     => 'fa-truck',
    'color'    => '#06b6d4',
    'url'      => '/shipping-policy.php',
    'desc'     => 'Digital delivery methods, timelines and project handover',
  ],
  'disclaimer'=> [
    'label'    => 'Disclaimer',
    'icon'     => 'fa-exclamation-circle',
    'color'    => '#e11d48',
    'url'      => '/disclaimer.php',
    'desc'     => 'Website accuracy, liability limitations, and legal notices',
  ],
];

if (!array_key_exists($pageKey, $legalPages)) $pageKey = 'privacy';

// Handle POST save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'save_legal') {
    $existing = dbFetchOne("SELECT id FROM pages WHERE page_key = ?", [$pageKey]);
    $data = [
      'title'    => sanitizeInput($_POST['title'] ?? ''),
      'subtitle' => sanitizeInput($_POST['subtitle'] ?? ''),
      'content'  => $_POST['content'] ?? '',
      'is_active'=> isset($_POST['is_active']) ? 1 : 1,
    ];
    if ($existing) {
      dbUpdateRow('pages', $data, 'page_key = ?', [$pageKey]);
    } else {
      $data['page_key'] = $pageKey;
      dbInsertRow('pages', $data);
    }
    // Save SEO settings if provided
    if (!empty($_POST['meta_title']) || !empty($_POST['meta_description'])) {
      $seoData = [
        'meta_title'       => sanitizeInput($_POST['meta_title'] ?? ''),
        'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
        'meta_keywords'    => sanitizeInput($_POST['meta_keywords'] ?? ''),
        'robots'           => sanitizeInput($_POST['robots'] ?? 'index,follow'),
      ];
      $existSeo = dbFetchOne("SELECT id FROM seo_settings WHERE page_key = ?", [$pageKey]);
      if ($existSeo) {
        dbUpdateRow('seo_settings', $seoData, 'page_key = ?', [$pageKey]);
      } else {
        $seoData['page_key'] = $pageKey;
        dbInsertRow('seo_settings', $seoData);
      }
    }
    logActivity('update', 'legal', "Updated legal page: {$pageKey}");
    setFlash('success', 'Legal page saved successfully!');
    redirect(ADMIN_URL . '/pages/legal.php?page=' . $pageKey);
  }

  if ($action === 'reset_content') {
    dbExecute("UPDATE pages SET content = '', subtitle = '' WHERE page_key = ?", [$pageKey]);
    logActivity('reset', 'legal', "Reset legal page to default: {$pageKey}");
    setFlash('info', 'Page reset to default built-in content.');
    redirect(ADMIN_URL . '/pages/legal.php?page=' . $pageKey);
  }
}

$page = dbFetchOne("SELECT * FROM pages WHERE page_key = ?", [$pageKey]) ?: ['title' => '', 'subtitle' => '', 'content' => ''];
$seo  = dbFetchOne("SELECT * FROM seo_settings WHERE page_key = ?", [$pageKey]) ?: [];
$cp   = $legalPages[$pageKey];

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<style>
.legal-tab-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:24px;background:var(--white);padding:6px;border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow);}
.legal-tab{display:flex;align-items:center;gap:8px;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:600;color:var(--gray);transition:var(--transition);cursor:pointer;white-space:nowrap;}
.legal-tab:hover{background:var(--light);color:var(--primary);}
.legal-tab.active{color:#fff;}
.legal-tab i{font-size:13px;}

.editor-panel{display:none;}
.editor-panel.active{display:block;}

.legal-status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
.status-default{background:rgba(107,114,128,.1);color:#6b7280;border:1px solid rgba(107,114,128,.2);}
.status-custom{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.2);}
</style>

<div class="page-header">
  <div>
    <h1><i class="fas fa-gavel" style="color:var(--violet);margin-right:10px;"></i>Legal Pages Manager</h1>
    <p>Manage all legal and policy pages with a rich content editor. Leave content empty to use professionally written defaults.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= SITE_URL . $cp['url'] ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-external-link-alt"></i> View Page</a>
    <a href="<?= ADMIN_URL ?>/pages/content.php" class="btn btn-secondary btn-sm"><i class="fas fa-file-alt"></i> Pages Editor</a>
  </div>
</div>

<!-- Legal Page Tabs -->
<div class="legal-tab-bar">
  <?php foreach ($legalPages as $key => $pg): ?>
  <a href="?page=<?= $key ?>" class="legal-tab <?= $pageKey === $key ? 'active' : '' ?>"
     style="<?= $pageKey === $key ? "background:linear-gradient(135deg,{$pg['color']},{$pg['color']}cc);" : '' ?>">
    <i class="fas <?= $pg['icon'] ?>"></i>
    <span><?= e($pg['label']) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Current Page Header -->
<div style="background:linear-gradient(135deg,<?= $cp['color'] ?>12,<?= $cp['color'] ?>06);border:1px solid <?= $cp['color'] ?>20;border-radius:var(--radius-lg);padding:20px 24px;margin-bottom:24px;display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
  <div style="width:52px;height:52px;background:<?= $cp['color'] ?>15;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <i class="fas <?= $cp['icon'] ?>" style="color:<?= $cp['color'] ?>;font-size:22px;"></i>
  </div>
  <div style="flex:1;min-width:200px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;flex-wrap:wrap;">
      <h2 style="font-size:18px;font-weight:800;color:var(--primary);"><?= e($cp['label']) ?></h2>
      <?php if (!empty($page['content'])): ?>
      <span class="legal-status-badge status-custom"><i class="fas fa-check-circle"></i> Custom Content Active</span>
      <?php else: ?>
      <span class="legal-status-badge status-default"><i class="fas fa-magic"></i> Using Built-in Default</span>
      <?php endif; ?>
    </div>
    <p style="font-size:13.5px;color:var(--gray);margin:0;"><?= e($cp['desc']) ?></p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0;">
    <a href="<?= SITE_URL . $cp['url'] ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Preview</a>
    <?php if (!empty($page['content'])): ?>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset to built-in default content? Your custom content will be cleared.');">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="reset_content">
      <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Reset to Default</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<form method="POST" id="legalForm">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_legal">

  <div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start;">

    <!-- Main Editor Column -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Content Card -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-edit" style="color:var(--violet);margin-right:8px;"></i>Page Content</h3>
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:12px;color:var(--gray);">
              <?php if (empty($page['content'])): ?>
              <i class="fas fa-info-circle" style="color:var(--violet);"></i> Empty = show default legal text
              <?php else: ?>
              <i class="fas fa-check-circle" style="color:var(--emerald);"></i> Custom content overrides default
              <?php endif; ?>
            </span>
          </div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label>Page Title / H1</label>
            <input type="text" name="title" class="form-control" value="<?= e($page['title']) ?>"
                   placeholder="e.g., Privacy Policy — <?= e(getSetting('site_name','Appsgain Technologies')) ?>">
          </div>
          <div class="form-group">
            <label>Subtitle / Tagline <span style="font-size:11px;font-weight:400;color:var(--gray);">(optional)</span></label>
            <input type="text" name="subtitle" class="form-control" value="<?= e($page['subtitle'] ?? '') ?>"
                   placeholder="Optional descriptive subtitle...">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Custom Content
              <span style="font-size:11px;font-weight:400;color:var(--gray);margin-left:6px;">— Leave empty to use professionally written default content</span>
            </label>
            <textarea name="content" id="richEditor" class="form-control" rows="28"
                      placeholder="Leave empty to use the built-in professional legal content for this page. Or enter your custom HTML here..."><?= htmlspecialchars($page['content'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
            <div class="form-hint">
              <i class="fas fa-lightbulb" style="color:var(--amber);"></i>
              <strong>Tip:</strong> Leaving this blank will display professional, SEO-friendly default legal content. Override only if you have specific customisations. Supports full HTML.
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right Sidebar Column -->
    <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:84px;">

      <!-- Save Card -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-save" style="color:var(--emerald);margin-right:8px;"></i>Save Changes</h3></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:8px;">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
              <i class="fas fa-save"></i> Save Legal Page
            </button>
            <a href="<?= SITE_URL . $cp['url'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">
              <i class="fas fa-external-link-alt"></i> View Live Page
            </a>
          </div>
          <div style="margin-top:14px;padding:12px;background:var(--light);border-radius:9px;font-size:12.5px;color:var(--gray);line-height:1.6;">
            <i class="fas fa-info-circle" style="color:var(--violet);margin-right:5px;"></i>
            Changes are published immediately. No approval workflow is required.
          </div>
        </div>
      </div>

      <!-- SEO Card -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-search" style="color:var(--blue);margin-right:8px;"></i>SEO Settings</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Meta Title</label>
            <input type="text" name="meta_title" class="form-control" value="<?= e($seo['meta_title'] ?? '') ?>"
                   placeholder="<?= e($cp['label']) ?> — <?= e(getSetting('site_name','Appsgain Technologies')) ?>">
            <div class="form-hint">Recommended: 50–60 characters</div>
          </div>
          <div class="form-group">
            <label>Meta Description</label>
            <textarea name="meta_description" class="form-control" rows="3"
                      placeholder="Brief description for search engine results..."><?= e($seo['meta_description'] ?? '') ?></textarea>
            <div class="form-hint">Recommended: 150–160 characters</div>
          </div>
          <div class="form-group">
            <label>Keywords <span style="font-size:11px;font-weight:400;color:var(--gray);">(comma separated)</span></label>
            <input type="text" name="meta_keywords" class="form-control" value="<?= e($seo['meta_keywords'] ?? '') ?>"
                   placeholder="keyword1, keyword2, keyword3">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Robots</label>
            <select name="robots" class="form-control">
              <option value="index,follow" <?= ($seo['robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>index, follow</option>
              <option value="noindex,follow" <?= ($seo['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>noindex, follow</option>
              <option value="noindex,nofollow" <?= ($seo['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
            </select>
          </div>
        </div>
      </div>

      <!-- All Legal Pages Quick Nav -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-gavel" style="color:var(--amber);margin-right:8px;"></i>All Legal Pages</h3></div>
        <div style="padding:8px;">
          <?php foreach ($legalPages as $key => $pg): ?>
          <a href="?page=<?= $key ?>" style="display:flex;align-items:center;gap:10px;padding:10px 10px;border-radius:9px;transition:var(--transition);margin-bottom:2px;<?= $pageKey === $key ? "background:linear-gradient(135deg,{$pg['color']}15,{$pg['color']}08);border:1px solid {$pg['color']}20;" : '' ?>">
            <div style="width:30px;height:30px;background:<?= $pg['color'] ?>12;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas <?= $pg['icon'] ?>" style="color:<?= $pg['color'] ?>;font-size:12px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:<?= $pageKey === $key ? 'var(--primary)' : 'var(--text)' ?>"><?= e($pg['label']) ?></div>
            </div>
            <?php
            $lStatus = dbFetchValue("SELECT content FROM pages WHERE page_key = ? AND content != '' AND content IS NOT NULL LIMIT 1", [$key]);
            ?>
            <span style="width:7px;height:7px;border-radius:50%;background:<?= $lStatus ? '#059669' : '#9ca3af' ?>;flex-shrink:0;" title="<?= $lStatus ? 'Custom content' : 'Default content' ?>"></span>
          </a>
          <?php endforeach; ?>
          <div style="padding:10px 10px 4px;font-size:11px;color:var(--gray2);">
            <i class="fas fa-circle" style="font-size:7px;color:#059669;"></i> Custom &nbsp;&nbsp;
            <i class="fas fa-circle" style="font-size:7px;color:#9ca3af;"></i> Default built-in
          </div>
        </div>
      </div>

    </div>
  </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('richEditor', { height: 560, placeholder: 'Enter your custom page content here (HTML supported)…' });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
