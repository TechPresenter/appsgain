<?php
require_once __DIR__ . '/includes/bootstrap.php';
trackVisitor();

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/apps.php'); exit; }

$app = dbFetchRow("SELECT * FROM apps WHERE app_slug = ? AND is_active = 1", [$slug]);
if (!$app) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

try { dbExecute("UPDATE apps SET view_count = view_count + 1 WHERE id = ?", [$app['id']]); } catch (Exception $e) {}

$activePage      = 'apps';
$pageTitle       = !empty($app['seo_title']) ? $app['seo_title'] : $app['app_name'] . ' – Download | Appsgain Technologies';
$pageDescription = !empty($app['seo_description']) ? $app['seo_description'] : truncate(strip_tags($app['short_description'] ?? ''), 160);
$canonicalUrl    = SITE_URL . '/app/' . $app['app_slug'];

/* ── App data ── */
$appFeatures = [];
if (!empty($app['app_features'])) {
    $decoded = json_decode($app['app_features'], true);
    $appFeatures = is_array($decoded) ? $decoded : array_map('trim', explode(',', $app['app_features']));
}

$screenshots = [];
try { $screenshots = dbFetchAll("SELECT * FROM app_screenshots WHERE app_id = ? ORDER BY sort_order ASC", [$app['id']]); } catch (Exception $e) {}

/* Also check screenshots_data saved by play store importer */
if (empty($screenshots) && !empty($app['screenshots_data'])) {
    $ssArr = json_decode($app['screenshots_data'], true);
    if (is_array($ssArr)) {
        foreach ($ssArr as $i => $path) {
            $screenshots[] = ['screenshot_image' => $path, 'screenshot_title' => $app['app_name'] . ' Screenshot ' . ($i + 1)];
        }
    }
}

$relatedApps = dbFetchAll(
    "SELECT id, app_name, app_slug, app_category, app_icon, app_rating, app_type FROM apps WHERE is_active=1 AND id != ? ORDER BY sort_order ASC LIMIT 4",
    [$app['id']]
);

$pt = $app['app_type'] ?? 'android';
$platformColor = ($pt === 'android') ? '#22c55e' : (($pt === 'ios') ? '#0b1026' : '#6a00ff');
$platformLabel = ($pt === 'android') ? 'Android' : (($pt === 'ios') ? 'iOS' : 'Android & iOS');
$platformIcon  = ($pt === 'android') ? 'fab fa-android' : (($pt === 'ios') ? 'fab fa-apple' : 'fas fa-mobile-alt');

$rating = (float)($app['app_rating'] ?? 0);
$ratingFull = floor($rating);
$ratingHalf = ($rating - $ratingFull) >= 0.5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css"/>
  <style>
  /* ══ CORE ══ */
  :root{--ac:<?= $platformColor ?>;--ac2:<?= $platformColor ?>dd;--font:'Poppins','Inter',system-ui,sans-serif;}
  *{box-sizing:border-box;}
  body{font-family:'Inter',system-ui,sans-serif;color:#0b1026;
    background:linear-gradient(160deg,<?= $platformColor ?>08 0%,#f7f8fc 30%,#fff 60%);}

  /* ══ HERO — colorful gradient banner ══ */
  .ad-hero{
    background:linear-gradient(135deg,<?= $platformColor ?>18 0%,<?= $platformColor ?>08 40%,#fff 100%);
    border-bottom:1px solid <?= $platformColor ?>22;
    padding:36px 0 28px;position:relative;overflow:hidden;
  }
  .ad-hero::before{
    content:'';position:absolute;top:-80px;right:-80px;width:320px;height:320px;
    border-radius:50%;background:radial-gradient(circle,<?= $platformColor ?>18,transparent 65%);
    pointer-events:none;
  }
  .ad-hero::after{
    content:'';position:absolute;bottom:-60px;left:-40px;width:200px;height:200px;
    border-radius:50%;background:radial-gradient(circle,<?= $platformColor ?>10,transparent 65%);
    pointer-events:none;
  }
  .ad-hero-inner{display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:flex-start;position:relative;z-index:1;}

  /* Icon with glowing ring */
  .ad-icon{width:100px;height:100px;border-radius:24px;overflow:hidden;flex-shrink:0;
    background:linear-gradient(135deg,<?= $platformColor ?>,<?= $platformColor ?>bb);
    display:flex;align-items:center;justify-content:center;font-size:44px;color:#fff;
    box-shadow:0 0 0 4px <?= $platformColor ?>22,0 8px 28px <?= $platformColor ?>44;}
  .ad-icon img{width:100%;height:100%;object-fit:cover;}

  /* Breadcrumb */
  .ad-crumbs{display:flex;align-items:center;gap:5px;font-size:12px;color:#8b90a0;margin-bottom:10px;}
  .ad-crumbs a{color:var(--ac);text-decoration:none;font-weight:500;transition:.15s;}
  .ad-crumbs a:hover{opacity:.75;}
  .ad-crumbs i{font-size:8px;color:#cbd5e1;}

  .ad-name{font-family:var(--font);font-size:clamp(20px,3vw,32px);font-weight:900;color:#0b1026;line-height:1.15;margin-bottom:5px;}
  .ad-dev{font-size:13px;font-weight:600;margin-bottom:10px;
    background:linear-gradient(135deg,<?= $platformColor ?>,<?= $platformColor ?>99);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

  /* Meta chips */
  .ad-meta-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;}
  .ad-stars{display:flex;gap:2px;}
  .ad-stars i{font-size:13px;color:#6a00ff;}
  .ad-stars i.e{color:#e7e9f0;}
  .ad-rating-num{font-size:13.5px;font-weight:800;color:#0b1026;}
  .ad-reviews{font-size:12px;color:#8b90a0;}
  .ad-platform-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;
    font-size:12px;font-weight:700;
    background:linear-gradient(135deg,<?= $platformColor ?>20,<?= $platformColor ?>10);
    color:<?= $platformColor ?>;border:1px solid <?= $platformColor ?>35;}
  .ad-cat-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;
    font-size:12px;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e7e9f0;}

  .ad-short{font-size:14px;color:#475569;line-height:1.7;max-width:560px;margin-bottom:18px;}

  /* Download buttons */
  .ad-btns{display:flex;gap:10px;flex-wrap:wrap;}
  .ad-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;transition:all .25s;font-family:var(--font);}
  .ad-btn-play{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;box-shadow:0 4px 16px rgba(34,197,94,.4);}
  .ad-btn-play:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(34,197,94,.45);color:#fff;}
  .ad-btn-ios{background:linear-gradient(135deg,#10142b,#0b1026);color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.25);}
  .ad-btn-ios:hover{transform:translateY(-2px);color:#fff;}
  .ad-btn-ghost{background:<?= $platformColor ?>15;color:var(--ac);border:2px solid <?= $platformColor ?>40;}
  .ad-btn-ghost:hover{background:<?= $platformColor ?>25;border-color:var(--ac);}

  /* ══ STATS STRIP — colorful cards ══ */
  .ad-stats{background:#fff;border-bottom:1px solid #f1f5f9;padding:0;}
  .ad-stats-row{display:flex;align-items:stretch;overflow-x:auto;scrollbar-width:none;}
  .ad-stats-row::-webkit-scrollbar{display:none;}
  .ad-stat{display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:16px 22px;border-right:1px solid #f1f5f9;flex-shrink:0;min-width:100px;gap:4px;
    transition:.2s;cursor:default;}
  .ad-stat:last-child{border-right:none;}
  .ad-stat:hover{background:var(--ac)05;}
  .ad-stat-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;margin-bottom:4px;}
  .ad-stat-val{font-family:var(--font);font-size:15px;font-weight:900;color:#0b1026;line-height:1;}
  .ad-stat-lbl{font-size:10.5px;color:#8b90a0;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}

  /* ══ BODY ══ */
  .ad-body{padding:32px 0 48px;}
  .ad-body-grid{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;}

  /* Section titles with gradient left border */
  .ad-sec-title{font-family:var(--font);font-size:16px;font-weight:800;color:#0b1026;
    margin-bottom:16px;padding:12px 16px;
    background:linear-gradient(135deg,<?= $platformColor ?>10,transparent);
    border-left:4px solid var(--ac);border-radius:0 10px 10px 0;
    display:flex;align-items:center;gap:9px;}
  .ad-sec-title i{font-size:15px;color:var(--ac);}

  /* Description */
  .ad-desc{font-size:14.5px;color:#242a42;line-height:1.9;}
  .ad-desc p{margin:0 0 12px;}
  .ad-desc p:last-child{margin:0;}

  /* Features — colorful gradient icons */
  .ad-features{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
  .ad-feat{display:flex;align-items:center;gap:10px;padding:11px 14px;
    background:#fff;border-radius:11px;
    border:1.5px solid <?= $platformColor ?>18;
    transition:.2s;box-shadow:0 1px 4px <?= $platformColor ?>0a;}
  .ad-feat:hover{border-color:<?= $platformColor ?>40;background:<?= $platformColor ?>06;transform:translateY(-1px);}
  .ad-feat-ic{width:28px;height:28px;border-radius:7px;flex-shrink:0;
    background:linear-gradient(135deg,<?= $platformColor ?>,<?= $platformColor ?>99);
    display:flex;align-items:center;justify-content:center;}
  .ad-feat-ic i{font-size:11px;color:#fff;}
  .ad-feat span{font-size:13px;color:#0b1026;font-weight:600;line-height:1.3;}

  /* Screenshots */
  .ad-screenshots{display:flex;gap:10px;overflow-x:auto;padding-bottom:6px;
    scrollbar-width:thin;scrollbar-color:<?= $platformColor ?> #f1f5f9;-webkit-overflow-scrolling:touch;}
  .ad-screenshots::-webkit-scrollbar{height:4px;}
  .ad-screenshots::-webkit-scrollbar-thumb{background:<?= $platformColor ?>;border-radius:4px;}
  .ad-ss-img{height:220px;width:auto;border-radius:14px;
    border:2px solid <?= $platformColor ?>20;flex-shrink:0;cursor:pointer;
    transition:.3s;object-fit:cover;box-shadow:0 4px 16px rgba(0,0,0,.08);}
  .ad-ss-img:hover{transform:scale(1.03) translateY(-3px);box-shadow:0 12px 28px rgba(0,0,0,.15);border-color:var(--ac);}

  /* ══ SIDEBAR ══ */
  .ad-sidebar-card{background:#fff;border:1.5px solid #f1f5f9;border-radius:16px;padding:20px;margin-bottom:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.04);}
  .ad-sidebar-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07);}
  .ad-sidebar-card-title{font-family:var(--font);font-size:13.5px;font-weight:800;color:#0b1026;
    margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid <?= $platformColor ?>20;
    display:flex;align-items:center;gap:7px;}

  .ad-info-rows{display:flex;flex-direction:column;}
  .ad-info-row{display:flex;align-items:center;justify-content:space-between;
    padding:9px 0;border-bottom:1px solid #f7f8fc;font-size:13px;}
  .ad-info-row:last-child{border-bottom:none;padding-bottom:0;}
  .ad-info-row-lbl{color:#8b90a0;font-weight:600;display:flex;align-items:center;gap:6px;}
  .ad-info-row-lbl i{font-size:10px;color:var(--ac);}
  .ad-info-row-val{color:#0b1026;font-weight:700;text-align:right;}

  /* Download CTA card */
  .ad-dl-card{
    background:linear-gradient(135deg,<?= $platformColor ?> 0%,<?= $platformColor ?>cc 100%);
    border-radius:16px;padding:22px 20px;text-align:center;color:#fff;
    box-shadow:0 8px 24px <?= $platformColor ?>44;position:relative;overflow:hidden;
  }
  .ad-dl-card::before{content:'';position:absolute;top:-30px;right:-30px;width:120px;height:120px;
    border-radius:50%;background:rgba(255,255,255,.1);pointer-events:none;}
  .ad-dl-card::after{content:'';position:absolute;bottom:-20px;left:-20px;width:80px;height:80px;
    border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none;}
  .ad-dl-card-name{font-family:var(--font);font-size:16px;font-weight:900;margin-bottom:4px;position:relative;z-index:1;}
  .ad-dl-card-sub{font-size:12.5px;color:rgba(255,255,255,.7);margin-bottom:16px;position:relative;z-index:1;}

  /* Related apps */
  .ad-related-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
  .ad-rel-card{display:flex;align-items:center;gap:10px;background:#fff;
    border:1.5px solid #f1f5f9;border-radius:12px;padding:12px;text-decoration:none;color:inherit;
    transition:.25s;box-shadow:0 1px 6px rgba(0,0,0,.04);}
  .ad-rel-card:hover{border-color:var(--ac);box-shadow:0 6px 18px <?= $platformColor ?>20;transform:translateY(-2px);}
  .ad-rel-icon{width:44px;height:44px;border-radius:11px;overflow:hidden;flex-shrink:0;
    background:linear-gradient(135deg,<?= $platformColor ?>,<?= $platformColor ?>88);
    display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;}
  .ad-rel-icon img{width:100%;height:100%;object-fit:cover;}
  .ad-rel-name{font-family:var(--font);font-size:13px;font-weight:700;color:#0b1026;line-height:1.3;}
  .ad-rel-cat{font-size:11px;color:#8b90a0;margin-top:2px;}

  /* Content section wrapper with subtle bg */
  .ad-content-section{background:#fff;border-radius:16px;padding:22px;
    border:1px solid #f1f5f9;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.03);}

  /* Read more button */
  .ad-readmore-btn{
    display:inline-flex;align-items:center;gap:7px;
    margin-top:10px;padding:8px 18px;border-radius:30px;border:none;cursor:pointer;
    background:linear-gradient(135deg,<?= $platformColor ?>18,<?= $platformColor ?>10);
    color:var(--ac);font-size:13.5px;font-weight:700;font-family:inherit;
    transition:.2s;border:1.5px solid <?= $platformColor ?>30;
  }
  .ad-readmore-btn:hover{background:linear-gradient(135deg,<?= $platformColor ?>28,<?= $platformColor ?>18);transform:translateY(-1px);}
  .ad-readmore-btn i{font-size:11px;transition:transform .25s;}
  .ad-readmore-btn.expanded i{transform:rotate(180deg);}

  /* ══ RESPONSIVE ══ */
  @media(max-width:900px){
    .ad-body-grid{grid-template-columns:1fr;}
    .ad-sidebar-order{order:-1;}
    .ad-related-grid{grid-template-columns:repeat(2,1fr);}
    .ad-icon{width:84px;height:84px;border-radius:20px;font-size:36px;}
    .ad-name{font-size:22px;}
    .ad-features{grid-template-columns:1fr;}
  }
  @media(max-width:600px){
    .ad-hero-inner{gap:14px;}
    .ad-icon{width:68px;height:68px;border-radius:16px;font-size:28px;}
    .ad-name{font-size:19px;}
    .ad-btn{padding:9px 16px;font-size:13px;}
    .ad-ss-img{height:170px;}
    .ad-related-grid{grid-template-columns:1fr 1fr;}
    .ad-stat{padding:11px 14px;min-width:80px;}
    .ad-stat-val{font-size:14px;}
    .ad-features{grid-template-columns:1fr;}
    .ad-content-section{padding:16px;}
  }

  /* ══ LIGHTBOX ══ */
  .ad-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px;}
  .ad-lightbox.open{display:flex;}
  .ad-lightbox img{max-width:100%;max-height:92vh;border-radius:14px;object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,.5);}
  .ad-lightbox-close{position:fixed;top:16px;right:16px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;}
  .ad-lightbox-close:hover{background:rgba(255,255,255,.25);}
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ── HERO ── -->
<div class="ad-hero">
  <div class="container">
    <div class="ad-hero-inner">

      <!-- Icon -->
      <div class="ad-icon">
        <?php if (!empty($app['app_icon'])): ?>
        <img src="<?= UPLOADS_URL ?>/<?= e($app['app_icon']) ?>" alt="<?= e($app['app_name']) ?>">
        <?php else: ?>
        <i class="<?= $platformIcon ?>"></i>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div>
        <!-- Breadcrumb -->
        <div class="ad-crumbs">
          <a href="<?= SITE_URL ?>/">Home</a><i class="fas fa-chevron-right"></i>
          <a href="<?= SITE_URL ?>/apps.php">Apps</a><i class="fas fa-chevron-right"></i>
          <span><?= e($app['app_name']) ?></span>
        </div>

        <h1 class="ad-name"><?= e($app['app_name']) ?></h1>
        <div class="ad-dev"><i class="<?= $platformIcon ?>" style="margin-right:4px;"></i><?= e($app['developer_name'] ?? 'Appsgain Technologies') ?></div>

        <!-- Meta row -->
        <div class="ad-meta-row">
          <?php if ($rating > 0): ?>
          <div class="ad-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star <?= $i <= $ratingFull ? '' : ($i == $ratingFull+1 && $ratingHalf ? 'fa-star-half-alt' : 'e') ?>"></i>
            <?php endfor; ?>
          </div>
          <span class="ad-rating-num"><?= number_format($rating, 1) ?></span>
          <?php if (!empty($app['total_downloads'])): ?>
          <span class="ad-reviews"><?= e($app['total_downloads']) ?> downloads</span>
          <?php endif; ?>
          <?php endif; ?>
          <span class="ad-platform-chip"><i class="<?= $platformIcon ?>"></i><?= $platformLabel ?></span>
          <?php if (!empty($app['app_category'])): ?>
          <span class="ad-cat-chip"><?= e($app['app_category']) ?></span>
          <?php endif; ?>
        </div>

        <?php if (!empty($app['short_description'])): ?>
        <p class="ad-short"><?= e($app['short_description']) ?></p>
        <?php endif; ?>

        <!-- Download buttons -->
        <div class="ad-btns">
          <?php if (!empty($app['google_play_url'])): ?>
          <a href="<?= e($app['google_play_url']) ?>" class="ad-btn ad-btn-play" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76a2 2 0 0 0 2.2-.2l11.6-6.7-2.9-2.9-10.9 9.8zm-1.7-20.6a2 2 0 0 0-.48 1.32v16.96c0 .49.17.94.47 1.3L2 23l9.51-9.51V13.4L2 3.89l-.52.27zm19.3 8.5L17.93 9.4l-3.12 3.11 3.12 3.12 2.87-1.66a1.73 1.73 0 0 0 0-3.31zM5.38.44a2 2 0 0 0-2.2-.2L3.7.51l9.51 9.51 2.9-2.9L5.38.44z"/></svg>
            Get on Google Play
          </a>
          <?php endif; ?>
          <?php if (!empty($app['app_store_url'])): ?>
          <a href="<?= e($app['app_store_url']) ?>" class="ad-btn ad-btn-ios" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
            Download on App Store
          </a>
          <?php endif; ?>
          <?php if (empty($app['google_play_url']) && empty($app['app_store_url'])): ?>
          <a href="<?= SITE_URL ?>/contact.php?subject=<?= urlencode($app['app_name'] . ' Download') ?>#enquiry" class="ad-btn ad-btn-ghost">
            <i class="fas fa-envelope"></i> Request Access
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── STATS STRIP ── -->
<?php
$stats = [];
if ($rating > 0)                       $stats[] = ['val'=>number_format($rating,1).'★', 'lbl'=>'Rating',    'ic'=>'fas fa-star',     'col'=>'#6a00ff'];
if (!empty($app['total_downloads']))   $stats[] = ['val'=>e($app['total_downloads']),     'lbl'=>'Downloads', 'ic'=>'fas fa-download', 'col'=>$platformColor];
if (!empty($app['app_category']))      $stats[] = ['val'=>e($app['app_category']),         'lbl'=>'Category',  'ic'=>'fas fa-tag',      'col'=>'#8033ff'];
if (!empty($app['app_version']??$app['version']??'')) $stats[] = ['val'=>e($app['app_version']??$app['version']), 'lbl'=>'Version', 'ic'=>'fas fa-code-branch','col'=>'#46009f'];
if (!empty($app['app_size']??''))      $stats[] = ['val'=>e($app['app_size']??''),         'lbl'=>'Size',      'ic'=>'fas fa-hdd',      'col'=>'#059669'];
$stats[] = ['val'=>$platformLabel,                                                          'lbl'=>'Platform',  'ic'=>$platformIcon,     'col'=>$platformColor];
if (!empty($app['last_updated']))      $stats[] = ['val'=>date('M Y', strtotime($app['last_updated'])), 'lbl'=>'Updated','ic'=>'fas fa-calendar-alt','col'=>'#5e6475'];
?>
<?php if (!empty($stats)): ?>
<div class="ad-stats">
  <div class="container" style="padding:0;">
    <div class="ad-stats-row">
      <?php foreach ($stats as $st): ?>
      <div class="ad-stat">
        <div class="ad-stat-icon" style="background:<?= $st['col'] ?>18;">
          <i class="<?= $st['ic'] ?>" style="color:<?= $st['col'] ?>;font-size:14px;"></i>
        </div>
        <div class="ad-stat-val"><?= $st['val'] ?></div>
        <div class="ad-stat-lbl"><?= $st['lbl'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── BODY ── -->
<div class="ad-body">
  <div class="container">
    <div class="ad-body-grid">

      <!-- ─ LEFT: Main content ─ -->
      <div>

        <!-- Screenshots -->
        <?php if (!empty($screenshots)): ?>
        <div class="ad-content-section" style="margin-bottom:14px;">
          <div class="ad-sec-title"><i class="fas fa-images"></i> Screenshots</div>
          <div class="ad-screenshots">
            <?php foreach ($screenshots as $ss): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e($ss['screenshot_image'] ?? $ss['file_path'] ?? '') ?>"
                 alt="<?= e($ss['screenshot_title'] ?? $app['app_name']) ?>"
                 class="ad-ss-img"
                 onclick="openLightbox(this.src)">
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- About / Description -->
        <?php $desc = strip_tags($app['app_description'] ?? ''); if ($desc): ?>
        <div class="ad-content-section" style="margin-bottom:0;">
          <div class="ad-sec-title"><i class="fas fa-info-circle"></i> About this App</div>
          <div class="ad-desc" id="descText" style="overflow:hidden;max-height:160px;transition:max-height .4s ease;" data-expanded="0">
            <?= nl2br(e($desc)) ?>
          </div>
          <div id="descFade" style="height:40px;margin-top:-40px;background:linear-gradient(transparent,#fff);position:relative;pointer-events:none;"></div>
          <button class="ad-readmore-btn" id="descToggle" onclick="toggleDesc()">
            <span id="descToggleTxt">Read more</span>
            <i class="fas fa-chevron-down" id="descToggleIc"></i>
          </button>
        </div>
        <?php endif; ?>

        <!-- Features -->
        <?php if (!empty($appFeatures)): ?>
        <div class="ad-content-section" style="margin-top:14px;">
          <div class="ad-sec-title"><i class="fas fa-check-circle"></i> Key Features</div>
          <div class="ad-features">
            <?php
            $featIcons = ['fa-check','fa-star','fa-bolt','fa-shield-alt','fa-magic','fa-rocket','fa-chart-line','fa-lock','fa-sync','fa-globe'];
            $fi = 0;
            foreach ($appFeatures as $feat): if (!trim($feat)) continue; ?>
            <div class="ad-feat">
              <div class="ad-feat-ic"><i class="fas <?= $featIcons[$fi % count($featIcons)] ?>"></i></div>
              <span><?= e(trim($feat)) ?></span>
            </div>
            <?php $fi++; endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Related Apps -->
        <?php if (!empty($relatedApps)): ?>
        <div>
          <div class="ad-sec-title"><i class="fas fa-th-large"></i> More Apps by Appsgain</div>
          <div class="ad-related-grid">
            <?php foreach ($relatedApps as $ra): ?>
            <a href="<?= SITE_URL ?>/app/<?= e($ra['app_slug']) ?>" class="ad-rel-card">
              <div class="ad-rel-icon">
                <?php if (!empty($ra['app_icon'])): ?>
                <img src="<?= UPLOADS_URL ?>/<?= e($ra['app_icon']) ?>" alt="<?= e($ra['app_name']) ?>">
                <?php else: ?>
                <i class="fas fa-mobile-alt"></i>
                <?php endif; ?>
              </div>
              <div>
                <div class="ad-rel-name"><?= e(truncate($ra['app_name'], 24)) ?></div>
                <div class="ad-rel-cat"><?= e($ra['app_category'] ?? '') ?></div>
                <?php if (!empty($ra['app_rating']) && $ra['app_rating'] > 0): ?>
                <div style="font-size:11.5px;color:#6a00ff;margin-top:2px;font-weight:700;">★ <?= number_format((float)$ra['app_rating'],1) ?></div>
                <?php endif; ?>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- ─ RIGHT: Sidebar ─ -->
      <aside class="ad-sidebar-order">

        <!-- Download card -->
        <div class="ad-dl-card" style="margin-bottom:16px;">
          <div style="width:56px;height:56px;border-radius:14px;overflow:hidden;margin:0 auto 12px;background:<?= $platformColor ?>;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;">
            <?php if (!empty($app['app_icon'])): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e($app['app_icon']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?><i class="<?= $platformIcon ?>"></i><?php endif; ?>
          </div>
          <div class="ad-dl-card-name"><?= e($app['app_name']) ?></div>
          <div class="ad-dl-card-sub">Free · <?= $platformLabel ?></div>
          <?php if (!empty($app['google_play_url'])): ?>
          <a href="<?= e($app['google_play_url']) ?>" target="_blank" rel="noopener"
             style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;background:#22c55e;color:#fff;border-radius:9px;font-size:13.5px;font-weight:700;text-decoration:none;margin-bottom:8px;transition:.2s;"
             onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76a2 2 0 0 0 2.2-.2l11.6-6.7-2.9-2.9-10.9 9.8zm-1.7-20.6a2 2 0 0 0-.48 1.32v16.96c0 .49.17.94.47 1.3L2 23l9.51-9.51V13.4L2 3.89l-.52.27zm19.3 8.5L17.93 9.4l-3.12 3.11 3.12 3.12 2.87-1.66a1.73 1.73 0 0 0 0-3.31zM5.38.44a2 2 0 0 0-2.2-.2L3.7.51l9.51 9.51 2.9-2.9L5.38.44z"/></svg>
            Google Play
          </a>
          <?php endif; ?>
          <?php if (!empty($app['app_store_url'])): ?>
          <a href="<?= e($app['app_store_url']) ?>" target="_blank" rel="noopener"
             style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;background:rgba(255,255,255,.1);color:#fff;border-radius:9px;font-size:13.5px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,.2);transition:.2s;"
             onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
            App Store
          </a>
          <?php endif; ?>
          <?php if (empty($app['google_play_url']) && empty($app['app_store_url'])): ?>
          <a href="<?= SITE_URL ?>/contact.php#enquiry"
             style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;background:<?= $platformColor ?>;color:#fff;border-radius:9px;font-size:13.5px;font-weight:700;text-decoration:none;">
            <i class="fas fa-envelope"></i> Request Access
          </a>
          <?php endif; ?>
        </div>

        <!-- App Info -->
        <div class="ad-sidebar-card">
          <div class="ad-sidebar-card-title"><i class="fas fa-info-circle" style="color:var(--ac);font-size:13px;"></i> App Information</div>
          <div class="ad-info-rows">
            <?php
            $infoRows = array_filter([
              ['ic'=>'fas fa-mobile-alt','lbl'=>'Platform',  'val'=> $platformLabel],
              !empty($app['app_category']) ? ['ic'=>'fas fa-tag','lbl'=>'Category','val'=> e($app['app_category'])] : null,
              !empty($app['developer_name']) ? ['ic'=>'fas fa-user','lbl'=>'Developer','val'=> e($app['developer_name'])] : null,
              !empty($app['app_version']??$app['version']??'') ? ['ic'=>'fas fa-code-branch','lbl'=>'Version','val'=> e($app['app_version']??$app['version']??'')] : null,
              !empty($app['app_size']??'') ? ['ic'=>'fas fa-hdd','lbl'=>'Size','val'=> e($app['app_size']??'')] : null,
              !empty($app['total_downloads']) ? ['ic'=>'fas fa-download','lbl'=>'Downloads','val'=> e($app['total_downloads'])] : null,
              $rating > 0 ? ['ic'=>'fas fa-star','lbl'=>'Rating','val'=> number_format($rating,1).' / 5.0'] : null,
              !empty($app['last_updated']) ? ['ic'=>'fas fa-calendar-alt','lbl'=>'Updated','val'=> date('d M Y', strtotime($app['last_updated']))] : null,
            ]);
            foreach ($infoRows as $row):
            ?>
            <div class="ad-info-row">
              <span class="ad-info-row-lbl"><i class="<?= $row['ic'] ?>"></i><?= $row['lbl'] ?></span>
              <span class="ad-info-row-val"><?= $row['val'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Share -->
        <div class="ad-sidebar-card">
          <div class="ad-sidebar-card-title"><i class="fas fa-share-alt" style="color:var(--ac);font-size:13px;"></i> Share</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php $shareUrl = urlencode($canonicalUrl); $shareTitle = urlencode($app['app_name']); ?>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener"
               style="width:36px;height:36px;border-radius:9px;background:#1877f2;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener"
               style="width:36px;height:36px;border-radius:9px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener"
               style="width:36px;height:36px;border-radius:9px;background:#25d366;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
            </a>
            <a href="https://www.linkedin.com/shareArticle?url=<?= $shareUrl ?>&title=<?= $shareTitle ?>" target="_blank" rel="noopener"
               style="width:36px;height:36px;border-radius:9px;background:#0077b5;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
            <button onclick="navigator.clipboard.writeText('<?= $canonicalUrl ?>');this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-link\'></i>',1500)"
              style="width:36px;height:36px;border-radius:9px;background:#f1f5f9;border:1px solid #e7e9f0;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#5e6475;font-size:13px;" title="Copy link">
              <i class="fas fa-link"></i>
            </button>
          </div>
        </div>

      </aside>
    </div>
  </div>
</div>

<!-- Screenshot lightbox -->
<div class="ad-lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="ad-lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <img id="lightboxImg" src="" alt="Screenshot">
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script>
/* ── Description expand/collapse — fixed ── */
function toggleDesc() {
  var txt  = document.getElementById('descText');
  var btn  = document.getElementById('descToggle');
  var fade = document.getElementById('descFade');
  var lbl  = document.getElementById('descToggleTxt');
  var ic   = document.getElementById('descToggleIc');

  var isExpanded = txt.getAttribute('data-expanded') === '1';

  if (isExpanded) {
    /* COLLAPSE */
    txt.style.maxHeight = '160px';
    txt.setAttribute('data-expanded', '0');
    if (fade) fade.style.display = 'block';
    if (lbl) lbl.textContent = 'Read more';
    if (ic)  { ic.style.transform = 'rotate(0deg)'; }
    btn.classList.remove('expanded');
    /* Smooth scroll back if needed */
    txt.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  } else {
    /* EXPAND */
    txt.style.maxHeight = txt.scrollHeight + 'px';
    txt.setAttribute('data-expanded', '1');
    if (fade) fade.style.display = 'none';
    if (lbl) lbl.textContent = 'Show less';
    if (ic)  { ic.style.transform = 'rotate(180deg)'; }
    btn.classList.add('expanded');
  }
}

/* Auto-hide "Read more" if content is short */
document.addEventListener('DOMContentLoaded', function() {
  var txt  = document.getElementById('descText');
  var btn  = document.getElementById('descToggle');
  var fade = document.getElementById('descFade');
  if (!txt || !btn) return;
  /* Give browser time to paint */
  setTimeout(function() {
    if (txt.scrollHeight <= 175) {
      btn.style.display  = 'none';
      if (fade) fade.style.display = 'none';
      txt.style.maxHeight = 'none';
    }
  }, 100);
});

/* Screenshot lightbox */
function openLightbox(src) {
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLightbox(); });
</script>
</body>
</html>
