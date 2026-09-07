<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
require_once __DIR__ . '/includes/product-components.php';
trackVisitor();

$activePage      = 'products';
$pageTitle       = getSetting('meta_title_products', 'Our Software Products – ERP, CRM, HRMS & More | Appsgain Technologies');
$pageDescription = getSetting('meta_desc_products',  'Explore Appsgain\'s comprehensive software products — Institute Management, Restaurant ERP, Real Estate, HRMS, CRM, Hospital Management and more.');
$pageKeywords    = 'software products, ERP, CRM, HRMS, management software, Appsgain products';
$canonicalUrl    = SITE_URL . '/products.php';

$category = trim($_GET['category'] ?? '');
$featured = $_GET['featured'] ?? '';

// Fetch products safely (table may not exist yet)
$products = $categories = [];
try {
    $q = "SELECT * FROM products WHERE is_active = 1";
    $p = [];
    if ($category) {
        $q .= " AND category = ?"; $p[] = $category;
    }
    if ($featured === '1') {
        $q .= " AND is_featured = 1";
    }
    $q .= " ORDER BY is_featured DESC, sort_order ASC";
    $products   = dbFetchAll($q, $p);
    $categories = dbFetchAll("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
} catch (Exception $e) {
    // Table doesn't exist yet — show empty state
}

// Category icon mapping
$catIcons = [
    'Education'       => ['fa-graduation-cap','#6a00ff','rgba(106,0,255,.1)'],
    'Hospitality'     => ['fa-utensils',       '#6a00ff','rgba(106,0,255,.1)'],
    'Real Estate'     => ['fa-building',        '#10b981','rgba(16,185,129,.1)'],
    'Sales & CRM'     => ['fa-users',           '#8033ff','rgba(128,51,255,.1)'],
    'Enterprise'      => ['fa-network-wired',   '#6a00ff','rgba(106,0,255,.1)'],
    'Human Resources' => ['fa-user-tie',        '#6a00ff','rgba(106,0,255,.1)'],
    'Healthcare'      => ['fa-heartbeat',       '#6a00ff','rgba(106,0,255,.1)'],
    'Inventory'       => ['fa-boxes',           '#5500cc','rgba(85,0,204,.1)'],
    'Custom'          => ['fa-cogs',            '#8033ff','rgba(128,51,255,.1)'],
];
$cardColors = [
    ['#6a00ff','rgba(106,0,255,.08)','linear-gradient(90deg,#6a00ff,#8033ff)'],
    ['#8033ff','rgba(128,51,255,.08)','linear-gradient(90deg,#8033ff,#6a00ff)'],
    ['#10b981','rgba(16,185,129,.08)','linear-gradient(90deg,#10b981,#6a00ff)'],
    ['#6a00ff','rgba(106,0,255,.08)','linear-gradient(90deg,#6a00ff,#f06a00)'],
    ['#6a00ff','rgba(106,0,255,.08)', 'linear-gradient(90deg,#6a00ff,#6a00ff)'],
    ['#6a00ff','rgba(106,0,255,.08)', 'linear-gradient(90deg,#6a00ff,#6a00ff)'],
    ['#8033ff','rgba(128,51,255,.08)','linear-gradient(90deg,#8033ff,#8033ff)'],
    ['#46009f','rgba(70,0,159,.08)', 'linear-gradient(90deg,#46009f,#10b981)'],
    ['#6a00ff','rgba(106,0,255,.08)','linear-gradient(90deg,#6a00ff,#6a00ff)'],
    ['#5500cc','rgba(85,0,204,.08)', 'linear-gradient(90deg,#5500cc,#6a00ff)'],
    ['#059669','rgba(5,150,105,.08)', 'linear-gradient(90deg,#059669,#10b981)'],
];
?>
<?php $pageStyles = ['page-hero.css', 'product.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/3d-saas.css" />
  <style>
  /* ── Products Page Styles ── */

  /* Hero */
  .prod-hero {
    position: relative;
    background: radial-gradient(ellipse 70% 60% at 50% -5%, rgba(128,51,255,.2) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 90% 60%, rgba(106,0,255,.12) 0%, transparent 55%),
                linear-gradient(175deg, #0a0616 0%, #0b1026 50%, #0a0616 100%);
    padding: 90px 0 70px; overflow: hidden;
  }
  .prod-hero-glow {
    position: absolute; border-radius: 50%; pointer-events: none;
  }
  .prod-hero-glow-1 {
    width: 500px; height: 500px; top: -180px; right: -100px;
    background: radial-gradient(circle, rgba(128,51,255,.12) 0%, transparent 70%);
    animation: saasFloat2 20s ease-in-out infinite;
  }
  .prod-hero-glow-2 {
    width: 350px; height: 350px; bottom: -100px; left: -80px;
    background: radial-gradient(circle, rgba(106,0,255,.1) 0%, transparent 70%);
    animation: saasFloat2 25s ease-in-out 8s infinite reverse;
  }
  .prod-hero-inner { position: relative; z-index: 2; }
  .prod-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; border-radius: 30px;
    background: rgba(128,51,255,.1); border: 1px solid rgba(128,51,255,.25);
    color: #e0d0ff; font-size: 11.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase; margin-bottom: 18px;
  }
  .prod-hero-title {
    font-family: 'Poppins','Inter',sans-serif;
    font-size: clamp(32px,4.5vw,56px); font-weight: 900;
    color: #fff; line-height: 1.08; letter-spacing: -1.5px; margin-bottom: 18px;
  }
  .prod-hero-title .ht-grad {
    background: linear-gradient(90deg, #e0d0ff, #c4a5ff, #a16bff);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    animation: saasGradShift 4s ease infinite;
  }
  .prod-hero-sub {
    font-size: 16px; color: rgba(255,255,255,.65); line-height: 1.75; max-width: 540px; margin-bottom: 30px;
  }
  .prod-hero-btns { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 30px; }
  .prod-hero-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 12.5px; color: rgba(255,255,255,.45);
  }
  .prod-hero-breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; transition: color .2s; }
  .prod-hero-breadcrumb a:hover { color: #e0d0ff; }
  .prod-hero-breadcrumb i { font-size: 9px; }
  /* Hero right: product count pills */
  .prod-hero-counts {
    display: flex; flex-direction: column; gap: 10px; flex-shrink: 0;
  }
  .prod-count-pill {
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    border-radius: 14px; padding: 14px 20px; min-width: 130px;
    backdrop-filter: blur(12px); text-align: center; transition: background .2s;
  }
  .prod-count-pill:hover { background: rgba(255,255,255,.1); }
  .prod-count-num {
    font-family: 'Poppins',sans-serif; font-size: 24px; font-weight: 900;
    background: linear-gradient(135deg, #fff, var(--pc, #e0d0ff));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    line-height: 1;
  }
  .prod-count-lbl { font-size: 10.5px; color: rgba(255,255,255,.5); font-weight: 600; margin-top: 3px; }

  /* Category filter tabs */
  .prod-filters { background: #fff; padding: 28px 0; border-bottom: 1px solid #e7e9f0; }
  .prod-filter-inner {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  }
  .prod-filter-label {
    font-size: 12px; font-weight: 800; color: #8b90a0;
    text-transform: uppercase; letter-spacing: .8px; white-space: nowrap; margin-right: 4px;
  }
  .prod-filter-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 30px; font-size: 12.5px; font-weight: 700;
    border: 1.5px solid #e7e9f0; background: #fff; color: #475569;
    text-decoration: none; transition: all .22s; font-family: 'Poppins',sans-serif;
    white-space: nowrap;
  }
  .prod-filter-tab:hover { border-color: #e0d0ff; color: #6a00ff; }
  .prod-filter-tab.active {
    background: linear-gradient(135deg, #6a00ff, #8033ff);
    border-color: transparent; color: #fff;
    box-shadow: 0 3px 12px rgba(106,0,255,.25);
  }
  .prod-filter-tab i { font-size: 11px; }

  /* Products grid */
  .prod-section { background: #f7f8fc; padding: 80px 0; }
  .prod-section:empty { display: none; }
  .prod-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
  }

  /* Product card */
  .prod-card {
    background: #fff;
    border: 1.5px solid #e8ecf3; border-radius: 20px; overflow: hidden;
    display: flex; flex-direction: column; position: relative;
    box-shadow: 0 4px 20px rgba(17,22,45,.05);
    transition: border-color .3s;
    transform-style: preserve-3d; will-change: transform;
  }
  .prod-card:hover {
    border-color: transparent;
    box-shadow: 0 20px 60px rgba(17,22,45,.1);
  }
  .prod-card-accentbar {
    height: 3px; transform: scaleX(0); transform-origin: left;
    transition: transform .4s cubic-bezier(.4,0,.2,1);
  }
  .prod-card:hover .prod-card-accentbar { transform: scaleX(1); }
  .prod-card-featured {
    position: absolute; top: 38px; right: 0; z-index: 5;
    background: linear-gradient(135deg, #6a00ff, #f06a00);
    color: #fff; font-size: 10px; font-weight: 800;
    padding: 4px 12px 4px 10px; border-radius: 20px 0 0 20px;
    letter-spacing: .5px; text-transform: uppercase;
    box-shadow: 0 3px 10px rgba(106,0,255,.35);
  }
  .prod-card-thumb {
    height: 120px; position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .prod-card-thumb img {
    max-height: 70px; max-width: 140px; object-fit: contain;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,.15));
    transition: transform .4s cubic-bezier(.4,0,.2,1);
  }
  .prod-card:hover .prod-card-thumb img { transform: scale(1.08) translateY(-4px); }
  .prod-card-icon {
    width: 70px; height: 70px; border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: #fff;
    transition: transform .4s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 6px 20px rgba(0,0,0,.2);
  }
  .prod-card:hover .prod-card-icon { transform: scale(1.1) translateY(-4px); }
  .prod-card-body { padding: 20px 22px; flex: 1; display: flex; flex-direction: column; }
  .prod-card-cat {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .7px; padding: 3px 10px; border-radius: 20px;
    margin-bottom: 8px; width: fit-content;
  }
  .prod-card-name {
    font-family: 'Poppins','Inter',sans-serif; font-size: 17px; font-weight: 800;
    color: #0b1026; margin-bottom: 8px; line-height: 1.25; transition: color .2s;
  }
  .prod-card:hover .prod-card-name { color: var(--cc, #6a00ff); }
  .prod-card-desc {
    font-size: 13.5px; color: #5e6475; line-height: 1.65; flex: 1; margin-bottom: 14px;
  }
  /* Key features */
  .prod-card-features { margin-bottom: 14px; }
  .prod-card-features-title {
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .6px; color: #8b90a0; margin-bottom: 7px;
  }
  .prod-card-feat {
    display: flex; align-items: center; gap: 7px;
    font-size: 12.5px; color: #475569; padding: 3px 0;
  }
  .prod-card-feat i { font-size: 10px; flex-shrink: 0; }
  .prod-card-footer {
    display: flex; gap: 8px;
    padding-top: 14px; border-top: 1px solid #f1f5f9;
  }
  .prod-card-footer .btn-saas-primary {
    flex: 1; justify-content: center;
    padding: 9px 14px !important; font-size: 12.5px !important;
  }
  .prod-card-footer .btn-saas-secondary {
    flex: 1; justify-content: center;
    padding: 9px 14px !important; font-size: 12.5px !important;
    color: #475569 !important; border-color: #e7e9f0 !important;
  }

  /* Empty state */
  .prod-empty {
    text-align: center; padding: 80px 20px;
    background: #fff; border: 1.5px dashed #e7e9f0; border-radius: 20px;
    max-width: 500px; margin: 0 auto;
  }
  .prod-empty-icon {
    width: 80px; height: 80px; border-radius: 20px; margin: 0 auto 20px;
    background: linear-gradient(135deg, rgba(106,0,255,.1), rgba(128,51,255,.1));
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #8033ff;
  }
  .prod-empty h3 { font-family: 'Poppins',sans-serif; font-size: 20px; font-weight: 800; color: #0b1026; margin-bottom: 10px; }
  .prod-empty p { font-size: 14px; color: #5e6475; margin-bottom: 24px; }

  /* Responsive */
  @media (max-width: 1100px) {
    .prod-hero-inner { grid-template-columns: 1fr; }
    .prod-hero-counts { flex-direction: row; flex-wrap: wrap; }
    .prod-count-pill { min-width: 100px; }
  }
  @media (max-width: 768px) {
    .prod-grid { grid-template-columns: 1fr; }
    .prod-filter-inner { gap: 7px; }
  }
  </style>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  HERO                                ║
     ╚═══════════════════════════════════════╝ -->
<?php pageHero([
  'image'   => 'secimg_hero_products',
  'eyebrow' => 'Products',
  'lead'    => 'Software Products,',
  'accent'  => 'Ready to Deploy',
  'sub'     => 'Proven platforms we have already built and refined — deployed faster and priced lower than starting from scratch.',
  'crumbs'  => [['Our Products', null]],
  'actions' => [['Request a Demo', '/contact.php#enquiry', true], ['Talk to Us', '/contact.php', false]],
]); ?>

<!-- ╔═══════════════════════════════════════╗
     ║  FILTER TABS                         ║
     ╚═══════════════════════════════════════╝ -->
<?php if (!empty($categories)): ?>
<div class="prod-filters">
  <div class="container">
    <div class="prod-filter-inner">
      <span class="prod-filter-label"><i class="fas fa-filter"></i> Filter:</span>
      <a href="<?= SITE_URL ?>/products.php" class="prod-filter-tab<?= !$category ? ' active' : '' ?>">
        <i class="fas fa-th"></i> All Products
      </a>
      <?php foreach ($categories as $cat):
        $ci = $catIcons[$cat['category']] ?? ['fa-box','#6a00ff','rgba(106,0,255,.1)'];
      ?>
      <a href="?category=<?= urlencode($cat['category']) ?>"
         class="prod-filter-tab<?= $category === $cat['category'] ? ' active' : '' ?>">
        <i class="fas <?= $ci[0] ?>"></i> <?= e($cat['category']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  PRODUCTS GRID                       ║
     ╚═══════════════════════════════════════╝ -->
<section id="products-section" class="prod-section">
  <div class="container">

    <?php if ($category || !empty($products)): ?>
    <div class="text-center saas-reveal" style="margin-bottom:44px">
      <span class="saas-label light-violet">
        <i class="fas fa-box-open"></i>
        <?= $category ? e($category) : 'All Products' ?>
      </span>
      <h2 class="saas-heading dark-text">
        <?= $category ? e($category) . ' <span class="grad">Solutions</span>' : 'Our Complete <span class="grad">Product Suite</span>' ?>
      </h2>
      <?php if (!empty($products)): ?>
      <p class="saas-section-sub dark-sub"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> available<?= $category ? ' in ' . e($category) : '' ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($products)): ?>
    <div class="pr-grid">
      <?php foreach ($products as $product):
        $feats = array_slice(pdList($product['key_features'] ?? ''), 0, 3);
      ?>
      <article class="pr-card">
        <div class="pr-card-top">
          <?php pdLogo($product, 52); ?>
          <?php if (!empty($product['category'])): ?>
          <span class="pr-card-cat">
            <i class="fas <?= e(pdCategoryIcon($product['category'])) ?>" aria-hidden="true"></i>
            <?= e($product['category']) ?>
          </span>
          <?php endif; ?>
        </div>

        <h3><?= e($product['name']) ?></h3>
        <p class="pr-card-desc"><?= e(truncate((string)($product['short_description'] ?? ''), 118)) ?></p>

        <?php if ($feats): ?>
        <ul class="pr-card-feats">
          <?php foreach ($feats as $f): ?>
          <li><i class="fas fa-check" aria-hidden="true"></i><span><?= e(truncate($f, 52)) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="pr-card-foot">
          <span class="pr-card-link">View details <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
          <?php $allFeats = pdList($product['key_features'] ?? ''); ?>
          <?php if (count($allFeats) > 3): ?>
          <span class="pr-card-count">+<?= count($allFeats) - 3 ?> more</span>
          <?php endif; ?>
        </div>

        <?php /* The whole card is the target, so there is no low-contrast button. */ ?>
        <a class="pr-card-stretch" href="<?= SITE_URL ?>/product/<?= e($product['slug']) ?>">
          <span class="sr-only">View <?= e($product['name']) ?></span>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php /* end grid */ ?>


    <?php else: ?>
    <div class="prod-empty saas-reveal">
      <div class="prod-empty-icon"><i class="fas fa-box-open"></i></div>
      <h3><?= $category ? 'No ' . e($category) . ' Products' : 'No Products Yet' ?></h3>
      <p><?= $category ? 'No products found in this category. Try a different filter.' : 'Our product catalogue is being set up. Check back soon!' ?></p>
      <?php if ($category): ?>
      <a href="<?= SITE_URL ?>/products.php" class="pr-btn pr-btn-primary">View All Products</a>
      <?php else: ?>
      <a href="<?= SITE_URL ?>/contact.php" class="pr-btn pr-btn-primary">
        <i class="fas fa-envelope"></i> Request a Custom Product
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- CTA -->
<section class="saas-cta">
  <div class="saas-cta-bubble"></div>
  <div class="saas-cta-bubble"></div>
  <div class="container">
    <div class="saas-cta-inner saas-reveal">
      <h2 class="saas-cta-title">
        Didn't Find What
        <span style="background:linear-gradient(90deg,#e0d0ff,#c4a5ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"> You Need?</span>
      </h2>
      <p class="saas-cta-sub">
        We build fully custom software solutions tailored to your industry, workflow, and budget. Let's discuss your requirements.
      </p>
      <div class="saas-cta-btns">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="pr-btn pr-btn-primary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-paper-plane"></i> Get a Custom Quote
        </a>
        <a href="<?= SITE_URL ?>/service/custom-software-development" class="btn-saas-secondary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-cogs"></i> Custom Software
        </a>
      </div>
      <div class="saas-cta-phone">
        <i class="fas fa-phone-alt"></i>
        <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', getSetting('phone', '+919955446477'))) ?>">
          <?= e(getSetting('phone', '+91-9955446477')) ?>
        </a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script src="<?= ASSETS_URL ?>/js/3d-saas.js"></script>
</body>
</html>
