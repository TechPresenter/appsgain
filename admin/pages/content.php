<?php
$adminTitle = 'Pages & Content';
$adminPage  = 'content';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$pageKey = sanitizeInput($_GET['page'] ?? 'home');

$allowedPages = [
  'home'      => ['label' => 'Home Page',               'icon' => 'fa-home',          'color' => '#7c3aed', 'url' => '/'],
  'about'     => ['label' => 'About Us',                'icon' => 'fa-users',          'color' => '#2563eb', 'url' => '/about.php'],
  'contact'   => ['label' => 'Contact Page',            'icon' => 'fa-envelope',       'color' => '#059669', 'url' => '/contact.php'],
  'privacy'   => ['label' => 'Privacy Policy',          'icon' => 'fa-shield-alt',     'color' => '#7c3aed', 'url' => '/privacy-policy.php'],
  'terms'     => ['label' => 'Terms & Conditions',      'icon' => 'fa-file-contract',  'color' => '#2563eb', 'url' => '/terms-of-service.php'],
  'cookie'    => ['label' => 'Cookie Policy',           'icon' => 'fa-cookie-bite',    'color' => '#d97706', 'url' => '/cookie-policy.php'],
  'refund'    => ['label' => 'Refund & Cancellation',   'icon' => 'fa-undo-alt',       'color' => '#059669', 'url' => '/refund-policy.php'],
  'shipping'  => ['label' => 'Shipping & Delivery',     'icon' => 'fa-truck',          'color' => '#06b6d4', 'url' => '/shipping-policy.php'],
  'disclaimer'=> ['label' => 'Disclaimer',              'icon' => 'fa-exclamation-circle','color' => '#e11d48','url' => '/disclaimer.php'],
  'careers'   => ['label' => 'Careers Page',             'icon' => 'fa-briefcase',      'color' => '#d97706', 'url' => '/careers.php'],
  'support'   => ['label' => 'Support Page',             'icon' => 'fa-life-ring',      'color' => '#e11d48', 'url' => '/support.php'],
];

if (!array_key_exists($pageKey, $allowedPages)) $pageKey = 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  if (($_POST['action'] ?? '') === 'save_content') {
    $existing = dbFetchOne("SELECT id FROM pages WHERE page_key = ?", [$pageKey]);
    $data = [
      'title'    => sanitizeInput($_POST['title'] ?? ''),
      'subtitle' => sanitizeInput($_POST['subtitle'] ?? ''),
      'content'  => $_POST['content'] ?? '',
      'is_active'=> 1,
    ];
    if ($existing) {
      dbUpdateRow('pages', $data, 'page_key = ?', [$pageKey]);
    } else {
      $data['page_key'] = $pageKey;
      dbInsertRow('pages', $data);
    }
    logActivity('update', 'page', "Updated page content: {$pageKey}");
    setFlash('success', 'Content saved successfully!');
    redirect(ADMIN_URL . '/pages/content.php?page=' . $pageKey);
  }
}

$page = dbFetchOne("SELECT * FROM pages WHERE page_key = ?", [$pageKey]) ?: ['title' => '', 'subtitle' => '', 'content' => ''];

$legalPages = ['privacy','terms','cookie','refund','shipping','disclaimer'];
$isLegalPage = in_array($pageKey, $legalPages);

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-file-alt" style="color:var(--violet);margin-right:10px;"></i>Pages &amp; Content</h1>
    <p>Manage and edit static page content across the website</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= SITE_URL . $allowedPages[$pageKey]['url'] ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="fas fa-external-link-alt"></i> View Page
    </a>
    <a href="<?= ADMIN_URL ?>/pages/legal.php?page=<?= $pageKey ?>" class="btn btn-secondary btn-sm" <?= !$isLegalPage ? 'style="display:none"' : '' ?>>
      <i class="fas fa-edit"></i> Advanced Editor
    </a>
  </div>
</div>

<div style="display:grid;grid-template-columns:260px 1fr;gap:24px;align-items:start">

  <!-- Page Selector -->
  <div class="card" style="position:sticky;top:84px;">
    <div class="card-header"><h3><i class="fas fa-sitemap" style="color:var(--violet);margin-right:8px;"></i>All Pages</h3></div>

    <!-- General Pages -->
    <div style="padding:10px 10px 4px;">
      <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--gray2);padding:4px 8px 8px;">General Pages</div>
      <?php foreach (['home','about','contact','careers','support'] as $k): ?>
      <?php if (!isset($allowedPages[$k])) continue; $pg = $allowedPages[$k]; ?>
      <a href="?page=<?= $k ?>" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;font-size:13px;font-weight:500;color:var(--text);margin-bottom:2px;transition:var(--transition);<?= $pageKey === $k ? 'background:rgba(124,58,237,.1);color:var(--violet);font-weight:700;' : '' ?>" onmouseover="if('<?= $pageKey ?>'!='<?= $k ?>'){this.style.background='var(--light)'}" onmouseout="if('<?= $pageKey ?>'!='<?= $k ?>'){this.style.background=''}">
        <span style="width:28px;height:28px;background:<?= $pageKey === $k ? 'rgba(124,58,237,.15)' : 'var(--light)' ?>;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas <?= $pg['icon'] ?>" style="font-size:11px;color:<?= $pageKey === $k ? 'var(--violet)' : 'var(--gray)' ?>;"></i>
        </span>
        <span><?= e($pg['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Legal Pages -->
    <div style="padding:10px 10px 12px;border-top:1px solid var(--border);margin-top:4px;">
      <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--gray2);padding:4px 8px 8px;">Legal & Policy Pages</div>
      <?php foreach ($legalPages as $k): ?>
      <?php $pg = $allowedPages[$k]; ?>
      <a href="?page=<?= $k ?>" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;font-size:13px;font-weight:500;color:var(--text);margin-bottom:2px;transition:var(--transition);<?= $pageKey === $k ? 'background:rgba(124,58,237,.1);color:var(--violet);font-weight:700;' : '' ?>" onmouseover="if('<?= $pageKey ?>'!='<?= $k ?>'){this.style.background='var(--light)'}" onmouseout="if('<?= $pageKey ?>'!='<?= $k ?>'){this.style.background=''}">
        <span style="width:28px;height:28px;background:<?= $pageKey === $k ? 'rgba(124,58,237,.15)' : 'var(--light)' ?>;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas <?= $pg['icon'] ?>" style="font-size:11px;color:<?= $pageKey === $k ? 'var(--violet)' : 'var(--gray)' ?>;"></i>
        </span>
        <span><?= e($pg['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Editor -->
  <div>
    <form method="POST" id="contentForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_content">

      <!-- Page Info Banner -->
      <?php $cp = $allowedPages[$pageKey]; ?>
      <div style="background:linear-gradient(135deg,<?= $cp['color'] ?>15,<?= $cp['color'] ?>08);border:1px solid <?= $cp['color'] ?>25;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;background:<?= $cp['color'] ?>15;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas <?= $cp['icon'] ?>" style="color:<?= $cp['color'] ?>;font-size:18px;"></i>
        </div>
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;color:var(--primary);"><?= e($cp['label']) ?></div>
          <div style="font-size:12px;color:var(--gray);margin-top:2px;">Editing page content — changes take effect immediately</div>
        </div>
        <?php if ($isLegalPage): ?>
        <a href="<?= ADMIN_URL ?>/pages/legal.php?page=<?= $pageKey ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-magic"></i> Advanced Editor
        </a>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-pencil-alt" style="color:var(--violet);margin-right:8px;"></i>Page Content Editor</h3>
          <div style="display:flex;gap:8px;">
            <a href="<?= SITE_URL . $cp['url'] ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Preview</a>
            <a href="<?= ADMIN_URL ?>/pages/seo.php?page=<?= $pageKey ?>" class="btn btn-secondary btn-sm"><i class="fas fa-search-plus"></i> SEO</a>
          </div>
        </div>
        <div class="card-body">
          <div class="form-row" style="margin-bottom:20px;">
            <div class="form-group" style="margin-bottom:0;">
              <label>Page Title / H1 <span class="required">*</span></label>
              <input type="text" name="title" class="form-control" value="<?= e($page['title']) ?>" placeholder="Enter page title...">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Subtitle / Tagline</label>
              <input type="text" name="subtitle" class="form-control" value="<?= e($page['subtitle'] ?? '') ?>" placeholder="Optional subtitle...">
            </div>
          </div>

          <div class="form-group">
            <label>Page Content
              <?php if ($isLegalPage): ?>
              <span style="font-size:11px;font-weight:400;color:var(--gray);margin-left:8px;">(Leave empty to use the built-in default legal content)</span>
              <?php endif; ?>
            </label>
            <textarea name="content" id="pageContent" class="form-control" rows="24" placeholder="Enter HTML content..."><?= htmlspecialchars($page['content'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
            <div class="form-hint">
              <i class="fas fa-info-circle" style="color:var(--violet);"></i>
              You can use HTML tags for formatting (h2, h3, p, ul, li, strong, a, etc.).
              <?php if ($isLegalPage): ?>
              For the legal pages, leaving this empty will display the professionally written default content. Override only when needed.
              <?php endif; ?>
            </div>
          </div>

          <div style="display:flex;gap:10px;align-items:center;padding-top:4px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Content</button>
            <?php if (!empty($page['content'])): ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="if(confirm('This will clear the custom content and restore default. Continue?')){document.getElementById('pageContent').value='';document.getElementById('contentForm').submit();}">
              <i class="fas fa-undo"></i> Reset to Default
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </form>
  </div>

</div>

<script>
/* Basic syntax hint for textarea */
document.getElementById('pageContent').addEventListener('keydown', function(e) {
  if (e.key === 'Tab') {
    e.preventDefault();
    const start = this.selectionStart;
    const end   = this.selectionEnd;
    this.value  = this.value.substring(0, start) + '  ' + this.value.substring(end);
    this.selectionStart = this.selectionEnd = start + 2;
  }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('pageContent', { height: 500, placeholder: 'Enter page content here (HTML supported)…' });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
