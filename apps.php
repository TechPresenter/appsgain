<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'apps';
$pageTitle       = getSetting('meta_title_apps', 'Our Mobile Apps – Download from Google Play & App Store | Appsgain Technologies');
$pageDescription = getSetting('meta_desc_apps',  'Download Appsgain\'s powerful Android & iOS business apps. Mobile-first solutions for HR, ERP, productivity and more.');
$pageKeywords    = 'mobile apps, Android apps, iOS apps, business apps, Appsgain app download';
$canonicalUrl    = SITE_URL . '/apps.php';

$app_type = in_array($_GET['type'] ?? '', ['android','ios','both']) ? $_GET['type'] : '';

// Fetch apps safely (table may not exist yet)
$apps = $featured_apps = [];
try {
    $q = "SELECT * FROM apps WHERE is_active = 1";
    $p = [];
    if ($app_type === 'android') {
        $q .= " AND app_type IN ('android','both')";
    } elseif ($app_type === 'ios') {
        $q .= " AND app_type IN ('ios','both')";
    } elseif ($app_type === 'both') {
        $q .= " AND app_type = 'both'";
    }
    $q .= " ORDER BY is_featured DESC, sort_order ASC";
    $apps = dbFetchAll($q, $p);
    $featured_apps = array_filter($apps, function($a) { return !empty($a['is_featured']); });
} catch (Exception $e) {
    // Table doesn't exist yet — show empty state
}
?>
<?php $pageStyles = ['page-hero.css']; ?>
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
  /* ── Apps Page Styles ── */

  /* Hero */
  .apps-hero {
    position: relative;
    background: radial-gradient(ellipse 70% 60% at 50% -5%, rgba(106,0,255,.2) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 90% 60%, rgba(106,0,255,.12) 0%, transparent 55%),
                linear-gradient(175deg, #0a0616 0%, #0b1026 50%, #0a0616 100%);
    padding: 90px 0 70px;
    overflow: hidden;
  }
  .apps-hero-glow {
    position: absolute; border-radius: 50%; pointer-events: none;
    background: radial-gradient(circle, rgba(106,0,255,.1) 0%, transparent 70%);
  }
  .apps-hero-glow-1 { width: 500px; height: 500px; top: -200px; right: -100px;
    animation: saasFloat2 20s ease-in-out infinite; }
  .apps-hero-glow-2 { width: 350px; height: 350px; bottom: -100px; left: -80px;
    animation: saasFloat2 25s ease-in-out 8s infinite reverse; }
  .apps-hero-inner { position: relative; z-index: 2; }
  .apps-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; border-radius: 30px;
    background: rgba(106,0,255,.1); border: 1px solid rgba(106,0,255,.25);
    color: #c4a5ff; font-size: 11.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase; margin-bottom: 18px;
  }
  .apps-hero-title {
    font-family: 'Poppins','Inter',sans-serif;
    font-size: clamp(32px,4.5vw,56px); font-weight: 900;
    color: #fff; line-height: 1.08; letter-spacing: -1.5px; margin-bottom: 18px;
  }
  .apps-hero-title .ht-grad {
    background: linear-gradient(90deg, #c4a5ff, #c4a5ff, #c4a5ff);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    animation: saasGradShift 4s ease infinite;
  }
  .apps-hero-sub {
    font-size: 16px; color: rgba(255,255,255,.65); line-height: 1.75; max-width: 520px; margin-bottom: 30px;
  }
  .apps-hero-btns { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 30px; }
  .apps-hero-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 12.5px; color: rgba(255,255,255,.45);
  }
  .apps-hero-breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; transition: color .2s; }
  .apps-hero-breadcrumb a:hover { color: #c4a5ff; }
  .apps-hero-breadcrumb i { font-size: 9px; }

  /* App type badges in hero */
  .apps-hero-badges { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 24px; }
  .apps-type-pill {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 30px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.7); font-size: 13px; font-weight: 600;
    font-family: 'Poppins',sans-serif; text-decoration: none;
    transition: all .2s; backdrop-filter: blur(8px);
  }
  .apps-type-pill:hover, .apps-type-pill.active {
    background: rgba(106,0,255,.15); border-color: rgba(106,0,255,.35);
    color: #c4a5ff;
  }
  .apps-type-pill i { font-size: 14px; }

  /* Stats bar */
  .apps-stats-bar {
    background: linear-gradient(135deg, #0a0616, #0b1026);
    border-top: 1px solid rgba(106,0,255,.12);
    border-bottom: 1px solid rgba(255,255,255,.05);
    padding: 0;
  }
  .apps-stats-inner { display: flex; justify-content: center; }
  .apps-stat {
    text-align: center; padding: 24px 40px;
    border-right: 1px solid rgba(255,255,255,.07);
    transition: background .2s;
  }
  .apps-stat:last-child { border-right: none; }
  .apps-stat:hover { background: rgba(255,255,255,.03); }
  .apps-stat-num {
    font-family: 'Poppins',sans-serif; font-size: 28px; font-weight: 900;
    background: linear-gradient(135deg, #fff, #c4a5ff);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    line-height: 1; margin-bottom: 4px;
  }
  .apps-stat-lbl { font-size: 11.5px; color: rgba(255,255,255,.5); font-weight: 600; letter-spacing: .5px; }

  /* Featured apps */
  .apps-featured { background: #f7f8fc; padding: 80px 0; }
  .apps-featured-grid { display: flex; flex-direction: column; gap: 28px; }
  .apps-featured-card {
    background: #fff;
    border: 1.5px solid #e8ecf3;
    border-radius: 20px; overflow: hidden;
    display: grid; grid-template-columns: 260px 1fr;
    box-shadow: 0 4px 24px rgba(17,22,45,.06);
    transition: all .35s cubic-bezier(.4,0,.2,1);
    transform-style: preserve-3d;
  }
  .apps-featured-card:hover {
    box-shadow: 0 20px 60px rgba(17,22,45,.1);
    transform: translateY(-4px);
    border-color: transparent;
  }
  .apps-fc-thumb {
    background: linear-gradient(135deg, #6a00ff 0%, #6a00ff 100%);
    display: flex; align-items: center; justify-content: center;
    padding: 40px; position: relative; overflow: hidden;
  }
  .apps-fc-thumb::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.15), transparent 60%);
  }
  .apps-fc-thumb img {
    width: 110px; height: 110px; border-radius: 24px;
    object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,.25);
    position: relative; z-index: 1;
  }
  .apps-fc-icon {
    width: 110px; height: 110px; border-radius: 24px;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 48px; color: #fff;
    position: relative; z-index: 1;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
  }
  .apps-fc-body { padding: 32px 36px; }
  .apps-fc-cat {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 10px;
    background: rgba(106,0,255,.08); color: #46009f; border: 1px solid rgba(106,0,255,.15);
  }
  .apps-fc-name {
    font-family: 'Poppins',sans-serif; font-size: 22px; font-weight: 900;
    color: #0b1026; margin-bottom: 4px;
  }
  .apps-fc-dev { font-size: 13px; color: #8b90a0; margin-bottom: 12px; }
  .apps-fc-rating {
    display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
  }
  .apps-fc-stars { display: flex; gap: 2px; }
  .apps-fc-stars i { font-size: 13px; color: #6a00ff; }
  .apps-fc-stars i.empty { color: #e7e9f0; }
  .apps-fc-rating-val { font-size: 13px; font-weight: 700; color: #0b1026; }
  .apps-fc-downloads {
    display: flex; align-items: center; gap: 5px;
    font-size: 12px; color: #5e6475; font-weight: 600;
  }
  .apps-fc-desc { font-size: 14px; color: #475569; line-height: 1.7; margin-bottom: 20px; }
  .apps-fc-btns { display: flex; gap: 10px; flex-wrap: wrap; }
  .apps-dl-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all .25s; font-family: 'Poppins',sans-serif;
    white-space: nowrap;
  }
  .apps-dl-btn.gplay { background: #22c55e; color: #fff; box-shadow: 0 3px 10px rgba(34,197,94,.3); }
  .apps-dl-btn.gplay:hover { background: #16a34a; box-shadow: 0 5px 16px rgba(34,197,94,.4); transform: translateY(-1px); }
  .apps-dl-btn.astore { background: #0b1026; color: #fff; box-shadow: 0 3px 10px rgba(0,0,0,.2); }
  .apps-dl-btn.astore:hover { background: #10142b; transform: translateY(-1px); }
  .apps-dl-btn.details {
    background: rgba(106,0,255,.07); color: #46009f;
    border: 1.5px solid rgba(106,0,255,.2);
  }
  .apps-dl-btn.details:hover { background: rgba(106,0,255,.12); transform: translateY(-1px); }

  /* All apps grid */
  .apps-all { background: #fff; padding: 80px 0; }
  .apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 22px;
  }
  .app-card-3d {
    background: #fff;
    border: 1.5px solid #e8ecf3; border-radius: 20px; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 4px 20px rgba(17,22,45,.05);
    transition: border-color .3s;
    transform-style: preserve-3d; will-change: transform;
  }
  .app-card-3d:hover {
    border-color: transparent;
    box-shadow: 0 20px 60px rgba(17,22,45,.1);
  }
  .app-card-accentbar {
    height: 3px; transform: scaleX(0); transform-origin: left;
    transition: transform .4s cubic-bezier(.4,0,.2,1);
    background: linear-gradient(90deg, #6a00ff, #6a00ff);
  }
  .app-card-3d:hover .app-card-accentbar { transform: scaleX(1); }
  .app-card-thumb {
    height: 130px;
    background: linear-gradient(135deg, #6a00ff 0%, #6a00ff 100%);
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden; flex-shrink: 0;
  }
  .app-card-thumb img {
    width: 80px; height: 80px; border-radius: 16px; object-fit: cover;
    box-shadow: 0 6px 20px rgba(0,0,0,.2);
    transition: transform .4s cubic-bezier(.4,0,.2,1);
  }
  .app-card-3d:hover .app-card-thumb img { transform: scale(1.08) translateY(-4px); }
  .app-card-thumb-icon {
    width: 80px; height: 80px; border-radius: 16px;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #fff;
    transition: transform .4s cubic-bezier(.4,0,.2,1);
  }
  .app-card-3d:hover .app-card-thumb-icon { transform: scale(1.1) translateY(-4px); }
  .app-card-platform {
    position: absolute; top: 10px; right: 12px;
    display: flex; gap: 5px;
    background: rgba(0,0,0,.3); backdrop-filter: blur(6px);
    border-radius: 20px; padding: 4px 10px;
    font-size: 11px; color: #fff;
  }
  .app-card-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; }
  .app-card-cat {
    font-size: 10.5px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .7px; color: #46009f; margin-bottom: 6px;
  }
  .app-card-name {
    font-family: 'Poppins','Inter',sans-serif; font-size: 16px; font-weight: 800;
    color: #0b1026; margin-bottom: 6px; line-height: 1.25;
    transition: color .2s;
  }
  .app-card-3d:hover .app-card-name { color: #46009f; }
  .app-card-rating { display: flex; align-items: center; gap: 4px; margin-bottom: 8px; }
  .app-card-rating i { font-size: 11px; color: #6a00ff; }
  .app-card-rating i.empty { color: #e7e9f0; }
  .app-card-rating span { font-size: 12px; color: #5e6475; font-weight: 600; margin-left: 3px; }
  .app-card-desc { font-size: 13px; color: #5e6475; line-height: 1.6; flex: 1; margin-bottom: 14px; }
  .app-card-footer {
    display: flex; gap: 8px;
    padding-top: 12px; border-top: 1px solid #f1f5f9;
  }
  .app-card-dl {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; text-decoration: none; transition: all .2s;
  }
  .app-card-dl.gplay { background: #22c55e; color: #fff; }
  .app-card-dl.gplay:hover { background: #16a34a; transform: scale(1.05); }
  .app-card-dl.astore { background: #0b1026; color: #fff; }
  .app-card-dl.astore:hover { background: #10142b; transform: scale(1.05); }
  .app-card-more {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;
    padding: 8px; border-radius: 9px;
    font-size: 12.5px; font-weight: 700; text-decoration: none;
    background: rgba(106,0,255,.07); color: #46009f;
    border: 1px solid rgba(106,0,255,.15); transition: all .2s;
  }
  .app-card-more:hover { background: rgba(106,0,255,.12); }

  /* Empty state */
  .apps-empty {
    text-align: center; padding: 80px 20px;
    background: #fff; border: 1.5px dashed #e7e9f0; border-radius: 20px;
    max-width: 500px; margin: 0 auto;
  }
  .apps-empty-icon {
    width: 80px; height: 80px; border-radius: 20px; margin: 0 auto 20px;
    background: linear-gradient(135deg, rgba(106,0,255,.1), rgba(106,0,255,.1));
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #6a00ff;
  }
  .apps-empty h3 { font-family: 'Poppins',sans-serif; font-size: 20px; font-weight: 800; color: #0b1026; margin-bottom: 10px; }
  .apps-empty p { font-size: 14px; color: #5e6475; margin-bottom: 24px; }

  /* Responsive */
  @media (max-width: 900px) {
    .apps-featured-card { grid-template-columns: 1fr; }
    .apps-fc-thumb { height: 200px; }
    .apps-stats-inner { flex-wrap: wrap; }
    .apps-stat { flex: 1 1 40%; border-bottom: 1px solid rgba(255,255,255,.07); }
  }
  @media (max-width: 600px) {
    .apps-grid { grid-template-columns: 1fr; }
    .apps-type-pill span { display: none; }
    .apps-stat { flex: 1 1 100%; }
  }
  </style>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  HERO                                ║
     ╚═══════════════════════════════════════╝ -->
<?php pageHero([
  'image'   => 'secimg_hero_apps',
  'eyebrow' => 'Mobile Apps',
  'lead'    => 'Apps We Have Built &',
  'accent'  => 'Published',
  'sub'     => 'Android and iOS applications designed, engineered and shipped to the stores by our team.',
  'crumbs'  => [['Mobile Apps', null]],
  'actions' => [['Build Your App', '/service/mobile-app-development', true], ['Get a Quote', '/contact.php#enquiry', false]],
]); ?>

<!-- Stats bar -->
<div class="apps-stats-bar">
  <div class="container" style="padding:0">
    <div class="apps-stats-inner">
      <div class="apps-stat">
        <div class="apps-stat-num"><?= count($apps) ?>+</div>
        <div class="apps-stat-lbl">Applications</div>
      </div>
      <div class="apps-stat">
        <div class="apps-stat-num">50K+</div>
        <div class="apps-stat-lbl">Total Downloads</div>
      </div>
      <div class="apps-stat">
        <div class="apps-stat-num">4.8★</div>
        <div class="apps-stat-lbl">Avg. Rating</div>
      </div>
      <div class="apps-stat">
        <div class="apps-stat-num">2</div>
        <div class="apps-stat-lbl">Platforms</div>
      </div>
    </div>
  </div>
</div>

<!-- ╔═══════════════════════════════════════╗
     ║  FEATURED APPS                       ║
     ╚═══════════════════════════════════════╝ -->
<?php if (!empty($featured_apps)): ?>
<section class="apps-featured" id="featured-apps">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:48px">
      <span class="saas-label" style="background:rgba(106,0,255,.08);color:#46009f;border:1px solid rgba(106,0,255,.15)">
        <i class="fas fa-star"></i> Featured
      </span>
      <h2 class="saas-heading dark-text">Featured <span class="grad">Applications</span></h2>
      <p class="saas-section-sub dark-sub">Our most downloaded and highest-rated mobile apps</p>
    </div>
    <div class="apps-featured-grid">
      <?php foreach ($featured_apps as $app):
        $colors = ['linear-gradient(135deg,#6a00ff,#6a00ff)','linear-gradient(135deg,#8033ff,#6a00ff)','linear-gradient(135deg,#10b981,#6a00ff)'];
        static $fi = 0;
        $grad = $colors[$fi % count($colors)]; $fi++;
      ?>
      <div class="apps-featured-card saas-reveal" data-tilt data-tilt-strength="4">
        <div class="apps-fc-thumb" style="background:<?= $grad ?>">
          <?php if (!empty($app['app_icon'])): ?>
          <img src="<?= UPLOADS_URL ?>/<?= e($app['app_icon']) ?>" alt="<?= e($app['app_name']) ?>">
          <?php else: ?>
          <div class="apps-fc-icon"><i class="fas fa-mobile-alt"></i></div>
          <?php endif; ?>
        </div>
        <div class="apps-fc-body">
          <span class="apps-fc-cat"><i class="fas fa-tag"></i> <?= e($app['app_category'] ?? 'Business') ?></span>
          <h3 class="apps-fc-name"><?= e($app['app_name']) ?></h3>
          <p class="apps-fc-dev"><i class="fas fa-code" style="color:#6a00ff;margin-right:5px"></i><?= e($app['developer_name'] ?? 'Appsgain Technologies') ?></p>
          <?php if ($app['app_rating'] > 0): ?>
          <div class="apps-fc-rating">
            <div class="apps-fc-stars">
              <?php for ($i = 0; $i < 5; $i++): ?>
              <i class="fas fa-star <?= $i < floor($app['app_rating']) ? '' : 'empty' ?>"></i>
              <?php endfor; ?>
            </div>
            <span class="apps-fc-rating-val"><?= number_format((float)$app['app_rating'],1) ?></span>
            <?php if (!empty($app['total_downloads'])): ?>
            <span class="apps-fc-downloads"><i class="fas fa-download"></i> <?= e($app['total_downloads']) ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <p class="apps-fc-desc"><?= e(truncate($app['short_description'] ?? '', 160)) ?></p>
          <div class="apps-fc-btns">
            <?php if (!empty($app['google_play_url'])): ?>
            <a href="<?= e($app['google_play_url']) ?>" class="apps-dl-btn gplay" target="_blank" rel="noopener">
              <i class="fab fa-google-play"></i> Google Play
            </a>
            <?php endif; ?>
            <?php if (!empty($app['app_store_url'])): ?>
            <a href="<?= e($app['app_store_url']) ?>" class="apps-dl-btn astore" target="_blank" rel="noopener">
              <i class="fab fa-apple"></i> App Store
            </a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/app/<?= e($app['app_slug']) ?>" class="apps-dl-btn details">
              <i class="fas fa-info-circle"></i> Details
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  ALL APPS GRID                        ║
     ╚═══════════════════════════════════════╝ -->
<section id="all-apps" class="apps-all">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:44px">
      <span class="saas-label light-blue"><i class="fas fa-mobile-alt"></i> All Apps</span>
      <h2 class="saas-heading dark-text">Complete App <span class="grad">Collection</span></h2>
      <p class="saas-section-sub dark-sub">Find the right mobile app for every business need</p>
    </div>

    <?php if (!empty($apps)): ?>
    <div class="apps-grid">
      <?php
      $appColors = ['#6a00ff','#6a00ff','#8033ff','#10b981','#6a00ff','#6a00ff'];
      $appGrads  = [
        'linear-gradient(135deg,#6a00ff,#6a00ff)',
        'linear-gradient(135deg,#8033ff,#6a00ff)',
        'linear-gradient(135deg,#10b981,#6a00ff)',
        'linear-gradient(135deg,#6a00ff,#f06a00)',
        'linear-gradient(135deg,#6a00ff,#8033ff)',
        'linear-gradient(135deg,#6a00ff,#8033ff)',
      ];
      $ai = 0;
      foreach ($apps as $app):
        $ac = $appColors[$ai % count($appColors)];
        $ag = $appGrads[$ai % count($appGrads)];
        $ai++;
      ?>
      <div class="app-card-3d saas-reveal saas-delay-<?= ($ai % 3) + 1 ?>" data-tilt data-tilt-strength="10">
        <div class="app-card-accentbar" style="background:<?= $ag ?>"></div>
        <div class="app-card-thumb" style="background:<?= $ag ?>">
          <?php if (!empty($app['app_icon'])): ?>
          <img src="<?= UPLOADS_URL ?>/<?= e($app['app_icon']) ?>" alt="<?= e($app['app_name']) ?>">
          <?php else: ?>
          <div class="app-card-thumb-icon"><i class="fas fa-mobile-alt"></i></div>
          <?php endif; ?>
          <span class="app-card-platform">
            <?php if ($app['app_type'] === 'both'): ?>
              <i class="fab fa-android"></i><i class="fab fa-apple"></i>
            <?php elseif ($app['app_type'] === 'android'): ?>
              <i class="fab fa-android"></i>
            <?php else: ?>
              <i class="fab fa-apple"></i>
            <?php endif; ?>
          </span>
        </div>
        <div class="app-card-body">
          <div class="app-card-cat" style="color:<?= $ac ?>"><?= e($app['app_category'] ?? 'Business') ?></div>
          <h3 class="app-card-name"><?= e($app['app_name']) ?></h3>
          <?php if ($app['app_rating'] > 0): ?>
          <div class="app-card-rating">
            <?php for ($i = 0; $i < 5; $i++): ?>
            <i class="fas fa-star <?= $i < floor($app['app_rating']) ? '' : 'empty' ?>"></i>
            <?php endfor; ?>
            <span><?= number_format((float)$app['app_rating'],1) ?></span>
          </div>
          <?php endif; ?>
          <p class="app-card-desc"><?= e(truncate($app['short_description'] ?? '', 100)) ?></p>
          <div class="app-card-footer">
            <?php if (!empty($app['google_play_url'])): ?>
            <a href="<?= e($app['google_play_url']) ?>" class="app-card-dl gplay" target="_blank" rel="noopener" title="Google Play">
              <i class="fab fa-google-play"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($app['app_store_url'])): ?>
            <a href="<?= e($app['app_store_url']) ?>" class="app-card-dl astore" target="_blank" rel="noopener" title="App Store">
              <i class="fab fa-apple"></i>
            </a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/app/<?= e($app['app_slug']) ?>" class="app-card-more">
              Details <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="apps-empty saas-reveal">
      <div class="apps-empty-icon"><i class="fas fa-mobile-alt"></i></div>
      <h3>No Apps Found</h3>
      <p><?= $app_type ? 'No ' . ucfirst($app_type) . ' apps available yet.' : 'No applications have been published yet.' ?></p>
      <?php if ($app_type): ?>
      <a href="<?= SITE_URL ?>/apps.php" class="btn-saas-primary">View All Apps</a>
      <?php else: ?>
      <a href="<?= SITE_URL ?>/contact.php" class="btn-saas-primary">
        <i class="fas fa-mobile-alt"></i> Request a Custom App
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
        Need a <span style="background:linear-gradient(90deg,#c4a5ff,#c4a5ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"> Custom Mobile App?</span>
      </h2>
      <p class="saas-cta-sub">
        We build native Android &amp; iOS apps and cross-platform solutions tailored to your exact business requirements.
      </p>
      <div class="saas-cta-btns">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="btn-saas-primary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-paper-plane"></i> Start Your App
        </a>
        <a href="<?= SITE_URL ?>/service/mobile-app-development" class="btn-saas-secondary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-mobile-alt"></i> Our Mobile Services
        </a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script src="<?= ASSETS_URL ?>/js/3d-saas.js"></script>
</body>
</html>
