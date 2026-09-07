<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'partners';
$pageTitle       = getSetting('meta_title_partners', 'Our Partners – Appsgain Technologies');
$pageDescription = getSetting('meta_desc_partners',  'Meet the technology and business partners powering Appsgain Technologies. Interested in partnership? Apply to join our ecosystem.');
$canonicalUrl    = SITE_URL . '/partners.php';

// Fetch partners from DB
$partners         = dbFetchAll("SELECT * FROM partners WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
$featuredPartners = array_values(array_filter($partners, fn($p) => !empty($p['is_featured'])));
$partnerTypes     = array_unique(array_column($partners, 'partner_type'));

// Partner type icons
$typeIcons = [
    'Cloud'         => ['fa-cloud',         '#6a00ff'],
    'Technology'    => ['fa-microchip',      '#8033ff'],
    'Payments'      => ['fa-credit-card',    '#10b981'],
    'Communication' => ['fa-comments',       '#6a00ff'],
    'Security'      => ['fa-shield-alt',     '#6a00ff'],
    'Marketing'     => ['fa-bullhorn',       '#8033ff'],
    'Analytics'     => ['fa-chart-bar',      '#6a00ff'],
    'Infrastructure'=> ['fa-server',         '#46009f'],
];

// CSRF for the form
$csrfToken = csrfToken();
?>
<?php $pageStyles = ['page-hero.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/3d-saas.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/captcha-cta.css"/>
  <style>
  /* ── Partners Page ── */
  .partners-hero {
    background:
      radial-gradient(ellipse 70% 55% at 20% -5%, rgba(106,0,255,.18) 0%, transparent 55%),
      radial-gradient(ellipse 55% 50% at 85% 60%, rgba(128,51,255,.14) 0%, transparent 50%),
      linear-gradient(175deg, #0a0616 0%, #0b1026 50%, #0a0616 100%);
    padding: 88px 0 68px; overflow: hidden; position: relative;
  }
  .partners-hero::before {
    content:''; position:absolute; inset:0;
    background-image: radial-gradient(rgba(128,51,255,.1) 1px, transparent 1px);
    background-size: 44px 44px; opacity:.4; pointer-events:none;
  }

  /* Featured partners carousel */
  .partners-featured {
    background: linear-gradient(180deg,#f7f8fc,#fff); padding: 60px 0;
  }
  .featured-scroll {
    display: flex; gap: 24px; overflow-x: auto; padding-bottom: 8px;
    scrollbar-width: none;
  }
  .featured-scroll::-webkit-scrollbar { display:none; }
  .featured-card {
    min-width: 240px; background: #fff; border-radius: 20px;
    border: 1.5px solid #e8ecf3; padding: 28px 24px; text-align: center;
    box-shadow: 0 4px 20px rgba(17,22,45,.05);
    transition: all .35s cubic-bezier(.4,0,.2,1); flex-shrink: 0;
  }
  .featured-card:hover {
    border-color: transparent; transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(128,51,255,.12);
  }
  .featured-logo-wrap {
    width: 90px; height: 90px; border-radius: 18px; margin: 0 auto 14px;
    background: linear-gradient(135deg, #f7f9fc, #f6f1ff);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; font-weight: 900; color: #8033ff;
    border: 2px solid #e8ecf3; overflow: hidden;
  }
  .featured-logo-wrap img { width:100%;height:100%;object-fit:contain;padding:10px; }
  .featured-card h4 { font-family:'Poppins',sans-serif; font-size:15px;font-weight:800;color:#0b1026;margin-bottom:4px; }
  .featured-card .ftype { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#8b90a0; }
  .featured-card .fsite {
    display:inline-flex;align-items:center;gap:4px;margin-top:10px;
    font-size:12px;font-weight:600;color:#8033ff;text-decoration:none;
    transition:gap .2s;
  }
  .featured-card .fsite:hover { gap:7px; }

  /* All partners grid */
  .all-partners { background:#fff; padding:70px 0; }
  .partner-type-section { margin-bottom:48px; }
  .type-header {
    display:flex;align-items:center;gap:12px;margin-bottom:24px;
    padding-bottom:14px;border-bottom:1px solid #f1f5f9;
  }
  .type-icon-wrap {
    width:42px;height:42px;border-radius:11px;
    display:flex;align-items:center;justify-content:center;font-size:17px;
  }
  .type-label { font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0b1026; }
  .partners-grid {
    display: grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap: 16px;
  }
  .partner-tile {
    background:#fff; border:1.5px solid #e8ecf3; border-radius:16px;
    padding:22px 16px; text-align:center;
    transition:all .3s cubic-bezier(.4,0,.2,1);
    box-shadow:0 2px 12px rgba(17,22,45,.04);
  }
  .partner-tile:hover {
    border-color:rgba(128,51,255,.3); transform:translateY(-4px);
    box-shadow:0 14px 36px rgba(128,51,255,.1);
  }
  .partner-tile .logo-box {
    width:72px;height:72px;border-radius:14px;margin:0 auto 12px;
    background:linear-gradient(135deg,#f7f9fc,#f6f1ff);
    display:flex;align-items:center;justify-content:center;
    font-size:26px;font-weight:900;color:#8033ff;overflow:hidden;
    border:1px solid #e8ecf3;
  }
  .partner-tile .logo-box img{width:100%;height:100%;object-fit:contain;padding:8px;}
  .partner-tile h5{font-family:'Poppins',sans-serif;font-size:13.5px;font-weight:700;color:#0b1026;margin-bottom:4px;}
  .partner-tile .ptype-badge{
    display:inline-block;padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;
    background:rgba(128,51,255,.08);color:#8033ff;border:1px solid rgba(128,51,255,.15);
  }
  .partner-tile a.psite{display:block;font-size:11px;color:#8b90a0;margin-top:7px;text-decoration:none;}
  .partner-tile a.psite:hover{color:#8033ff;}

  /* Become a Partner form */
  .become-partner { background:linear-gradient(180deg,#f7f8fc,#f7f9fc);padding:80px 0; }
  .bp-inner {
    display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start;
  }
  .bp-left { }
  .bp-eyebrow {
    display:inline-flex;align-items:center;gap:7px;padding:5px 14px;border-radius:30px;
    background:rgba(128,51,255,.1);border:1px solid rgba(128,51,255,.22);
    color:#8033ff;font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;margin-bottom:16px;
  }
  .bp-title {
    font-family:'Poppins',sans-serif;font-size:clamp(26px,3.5vw,40px);font-weight:900;
    color:#0b1026;line-height:1.1;letter-spacing:-1px;margin-bottom:16px;
  }
  .bp-title span{
    background:linear-gradient(90deg,#8033ff,#8033ff,#6a00ff);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  }
  .bp-sub{font-size:15px;color:#5e6475;line-height:1.75;margin-bottom:28px;}
  .bp-benefits{display:flex;flex-direction:column;gap:12px;}
  .bp-benefit{
    display:flex;align-items:flex-start;gap:12px;
    background:#fff;border:1px solid #e8ecf3;border-radius:12px;padding:14px 16px;
    box-shadow:0 2px 10px rgba(17,22,45,.04);transition:all .25s;
  }
  .bp-benefit:hover{border-color:rgba(128,51,255,.25);transform:translateX(4px);}
  .bp-benefit-icon{
    width:36px;height:36px;border-radius:9px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:15px;
  }
  .bp-benefit-text h5{font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:#0b1026;margin-bottom:3px;}
  .bp-benefit-text p{font-size:12.5px;color:#5e6475;line-height:1.55;}

  /* Form card */
  .bp-form-card {
    background:#fff;border-radius:22px;padding:36px;
    border:1.5px solid #e8ecf3;
    box-shadow:0 8px 40px rgba(17,22,45,.08);
    position:sticky;top:100px;
  }
  .bp-form-title{font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#0b1026;margin-bottom:6px;}
  .bp-form-sub{font-size:13px;color:#8b90a0;margin-bottom:24px;}
  .bp-form .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  .bp-form .fg{margin-bottom:14px;}
  .bp-form label{display:block;font-size:12.5px;font-weight:700;color:#374151;margin-bottom:5px;}
  .bp-form label .req{color:#6a00ff;margin-left:2px;}
  .bp-form input,.bp-form select,.bp-form textarea{
    width:100%;padding:11px 14px;border:1.5px solid #e7e9f0;border-radius:10px;
    font-size:13.5px;font-family:'Inter',sans-serif;color:#0b1026;
    background:#fafbff;outline:none;
    transition:border-color .2s,box-shadow .2s;
  }
  .bp-form input:focus,.bp-form select:focus,.bp-form textarea:focus{
    border-color:#8033ff;box-shadow:0 0 0 3px rgba(128,51,255,.1);background:#fff;
  }
  .bp-form textarea{min-height:90px;resize:vertical;}
  .bp-form .submit-btn{
    width:100%;padding:13px;border-radius:12px;font-family:'Poppins',sans-serif;
    font-size:14px;font-weight:700;background:linear-gradient(135deg,#6a00ff,#6a00ff);
    color:#fff;border:none;cursor:pointer;
    box-shadow:0 4px 18px rgba(106,0,255,.4);
    transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;
    margin-top:6px;
  }
  .bp-form .submit-btn:hover{box-shadow:0 6px 24px rgba(106,0,255,.55);transform:translateY(-2px);}
  .bp-form-msg{display:none;padding:13px 16px;border-radius:10px;font-size:13.5px;margin-top:12px;font-weight:600;}
  .bp-form-msg.success{background:rgba(16,185,129,.1);color:#059669;border:1px solid rgba(16,185,129,.25);}
  .bp-form-msg.error  {background:rgba(239,68,68,.1); color:#dc2626;border:1px solid rgba(239,68,68,.25);}

  /* No partners empty state */
  .partners-empty{text-align:center;padding:64px 20px;color:#8b90a0;}
  .partners-empty i{font-size:48px;margin-bottom:16px;display:block;opacity:.4;}

  @media(max-width:900px){ .bp-inner{grid-template-columns:1fr;gap:40px;} .bp-form-card{position:static;} }
  @media(max-width:600px){ .partners-grid{grid-template-columns:repeat(2,1fr);} .bp-form .form-row{grid-template-columns:1fr;} }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- HERO -->
<?php pageHero([
  'image'   => 'secimg_hero_partners',
  'eyebrow' => 'Partners',
  'lead'    => 'The Network Behind',
  'accent'  => 'Our Delivery',
  'sub'     => 'Technology and business partners we build alongside. Interested in joining the ecosystem? We would like to hear from you.',
  'crumbs'  => [['Partners', null]],
  'actions' => [['Become a Partner', '#partner-form', true]],
]); ?>

<!-- ═══ PREMIUM PARTNER MARQUEE ═══ -->
<style>
/* ── Partner Marquee ── */
@keyframes marqueeLeft  { from{transform:translateX(0)} to{transform:translateX(-50%)} }
@keyframes marqueeRight { from{transform:translateX(-50%)} to{transform:translateX(0)} }
@keyframes cardFloat    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }
@keyframes glowPulse    { 0%,100%{box-shadow:0 0 0 0 rgba(128,51,255,0)} 50%{box-shadow:0 0 24px rgba(128,51,255,.2)} }

.partner-marquee-section {
  background: linear-gradient(180deg,#f7f8fc 0%,#fff 100%);
  padding: 70px 0 60px;
  overflow: hidden;
}
.partner-marquee-section .container { overflow: visible; }

.pm-header { text-align:center; margin-bottom:48px; }
.pm-label {
  display:inline-flex; align-items:center; gap:8px;
  padding:5px 16px; border-radius:30px; font-size:11px; font-weight:700;
  letter-spacing:.8px; text-transform:uppercase; margin-bottom:14px;
  background:rgba(128,51,255,.08); color:#6a00ff; border:1px solid rgba(128,51,255,.18);
}
.pm-title {
  font-family:'Poppins',sans-serif; font-size:clamp(26px,4vw,42px); font-weight:900;
  color:#0b1026; letter-spacing:-1px; margin-bottom:12px; line-height:1.1;
}
.pm-title .gt {
  background:linear-gradient(90deg,#6a00ff,#6a00ff,#6a00ff);
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.pm-sub { font-size:15px; color:#5e6475; max-width:480px; margin:0 auto; line-height:1.7; }

/* Row wrapper */
.pm-row-wrap {
  overflow:hidden;
  -webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
  mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
  margin-bottom:18px;
}
.pm-row-wrap:last-child { margin-bottom:0; }
.pm-track {
  display:flex; gap:18px; align-items:center;
  width:max-content;
}
.pm-track.left  { animation:marqueeLeft  35s linear infinite; }
.pm-track.right { animation:marqueeRight 40s linear infinite; }
.pm-track:hover { animation-play-state:paused; }

/* Individual partner card */
/* Logo-only tile. The mark is the card, so it is sized for artwork of
   any aspect and the name lives in the title/alt for anyone who needs it. */
.pm-card {
  display:flex; align-items:center; justify-content:center;
  background:#fff; border:1.5px solid #e8ecf3;
  border-radius:16px; padding:18px 26px;
  width:180px; height:96px;
  flex-shrink:0; overflow:hidden; position:relative;
  text-decoration:none;
  transition:transform .35s cubic-bezier(.4,0,.2,1), box-shadow .35s ease, border-color .35s ease;
  box-shadow:0 2px 12px rgba(17,22,45,.05);
}
a.pm-card { cursor:pointer; }
span.pm-card { cursor:default; }
.pm-card::before {
  content:''; position:absolute; inset:0; border-radius:16px;
  background:linear-gradient(135deg,var(--pc,rgba(128,51,255,.06)),transparent);
  opacity:0; transition:opacity .3s;
}
.pm-card:hover::before { opacity:1; }
.pm-card:hover {
  border-color:var(--pc-border,rgba(128,51,255,.3));
  transform:translateY(-4px);
  box-shadow:0 12px 36px var(--pc-shadow,rgba(128,51,255,.12));
}
/* Logos arrive at every aspect ratio, so contain rather than crop */
.pm-card img {
  max-width:100%; max-height:100%;
  width:auto; height:auto; object-fit:contain;
  position:relative; z-index:1;
  transition:transform .3s ease;
}
.pm-card:hover img { transform:scale(1.06); }
/* Only used when a partner has no logo uploaded yet */
.pm-init {
  font-family:'Poppins',sans-serif; font-size:24px; font-weight:900;
  letter-spacing:.02em; position:relative; z-index:1;
}
@media (max-width:640px){
  .pm-card { width:142px; height:80px; padding:14px 18px; }
}

/* Stats bar */
.pm-stats {
  display:flex; gap:0; justify-content:center;
  background:linear-gradient(135deg,#0a0616,#0b1026);
  border-radius:20px; margin:48px auto 0; max-width:700px;
  overflow:hidden;
}
.pm-stat {
  flex:1; text-align:center; padding:22px 16px;
  border-right:1px solid rgba(255,255,255,.07);
}
.pm-stat:last-child { border-right:none; }
.pm-stat-val {
  font-family:'Poppins',sans-serif; font-size:26px; font-weight:900;
  color:#fff; line-height:1; margin-bottom:5px;
}
.pm-stat-lbl { font-size:11.5px; color:rgba(255,255,255,.5); font-weight:600; }
@media(max-width:640px){ .pm-stats{flex-direction:column;} .pm-stat{border-right:none;border-bottom:1px solid rgba(255,255,255,.07);} .pm-stat:last-child{border-bottom:none;} .pm-card{min-width:170px;padding:12px 16px;} }
</style>

<?php
/* Build display list — if DB has partners use them, else use these */
$allDisplayPartners = !empty($partners) ? $partners : [
  ['name'=>'Amazon Web Services',  'partner_type'=>'Cloud',       'logo'=>'', 'website'=>'https://aws.amazon.com'],
  ['name'=>'Google Cloud',         'partner_type'=>'Cloud',       'logo'=>'', 'website'=>'https://cloud.google.com'],
  ['name'=>'Microsoft Azure',      'partner_type'=>'Cloud',       'logo'=>'', 'website'=>'https://azure.microsoft.com'],
  ['name'=>'Razorpay',             'partner_type'=>'Payments',    'logo'=>'', 'website'=>'https://razorpay.com'],
  ['name'=>'Stripe',               'partner_type'=>'Payments',    'logo'=>'', 'website'=>'https://stripe.com'],
  ['name'=>'Twilio',               'partner_type'=>'Communication','logo'=>'','website'=>'https://twilio.com'],
  ['name'=>'Docker',               'partner_type'=>'Technology',  'logo'=>'', 'website'=>'https://docker.com'],
  ['name'=>'GitHub',               'partner_type'=>'Technology',  'logo'=>'', 'website'=>'https://github.com'],
  ['name'=>'Cloudflare',           'partner_type'=>'Security',    'logo'=>'', 'website'=>'https://cloudflare.com'],
  ['name'=>'MongoDB',              'partner_type'=>'Technology',  'logo'=>'', 'website'=>'https://mongodb.com'],
];

/* Color themes per type */
$pmColors = [
  'Cloud'         => ['#46009f','rgba(70,0,159,.08)',  'rgba(70,0,159,.25)',  'rgba(70,0,159,.1)'],
  'Payments'      => ['#059669','rgba(5,150,105,.08)',  'rgba(5,150,105,.25)',  'rgba(5,150,105,.1)'],
  'Technology'    => ['#6a00ff','rgba(106,0,255,.08)',  'rgba(106,0,255,.25)',  'rgba(106,0,255,.1)'],
  'Communication' => ['#6a00ff','rgba(106,0,255,.08)', 'rgba(106,0,255,.25)', 'rgba(106,0,255,.1)'],
  'Security'      => ['#6a00ff','rgba(106,0,255,.08)',  'rgba(106,0,255,.25)',  'rgba(106,0,255,.1)'],
  'Marketing'     => ['#8033ff','rgba(128,51,255,.08)', 'rgba(128,51,255,.25)', 'rgba(128,51,255,.1)'],
  'Analytics'     => ['#6a00ff','rgba(106,0,255,.08)', 'rgba(106,0,255,.25)', 'rgba(106,0,255,.1)'],
  'Infrastructure'=> ['#46009f','rgba(2,132,199,.08)',  'rgba(2,132,199,.25)',  'rgba(2,132,199,.1)'],
];

/* Build two rows by splitting the list */
$half1 = array_slice($allDisplayPartners, 0, (int)ceil(count($allDisplayPartners)/2));
$half2 = array_slice($allDisplayPartners, (int)ceil(count($allDisplayPartners)/2));
/* Duplicate each row for seamless looping */
/* Repeat until the track is wide enough to loop without a visible gap;
   three copies of one partner left most of the row empty. */
$fill = static function (array $items, int $min = 12): array {
    if (!$items) return [];
    $out = [];
    while (count($out) < $min) $out = array_merge($out, $items);
    return $out;
};
$row1 = $fill($half1);
$row2 = $fill($half2);
?>
<section class="partner-marquee-section">
  <div class="container">
    <div class="pm-header saas-reveal">
      <div class="pm-label"><i class="fas fa-handshake"></i> Our Partners</div>
      <h2 class="pm-title">Strategic <span class="gt">Partnerships</span></h2>
      <p class="pm-sub">Collaborating with world-class technology and business leaders to deliver the best solutions for our clients.</p>
    </div>
  </div>

  <!-- Row 1 — scrolls left -->
  <div class="pm-row-wrap">
    <div class="pm-track left">
      <?php foreach ($row1 as $p):
        $tc  = $pmColors[$p['partner_type']] ?? $pmColors['Technology'];
        $url = trim((string)($p['website'] ?? ''));
        if ($url !== '' && !preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
        $tag = $url !== '' ? 'a' : 'span';
      ?>
      <<?= $tag ?> class="pm-card"
         <?= $url !== '' ? 'href="' . e($url) . '" target="_blank" rel="noopener"' : '' ?>
         title="<?= e($p['name']) ?>"
         style="--pc:<?= $tc[1] ?>;--pc-border:<?= $tc[2] ?>;--pc-shadow:<?= $tc[3] ?>;--pc-color:<?= $tc[0] ?>">
        <?php if (!empty($p['logo'])): ?>
        <img src="<?= UPLOADS_URL ?>/<?= e($p['logo']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" decoding="async">
        <?php else: ?>
        <span class="pm-init" style="color:<?= $tc[0] ?>"><?= e(strtoupper(mb_substr($p['name'], 0, 2))) ?></span>
        <?php endif; ?>
      </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Row 2 — scrolls right (reverse) -->
  <?php if (!empty($row2)): ?>
  <div class="pm-row-wrap">
    <div class="pm-track right">
      <?php foreach ($row2 as $p):
        $tc  = $pmColors[$p['partner_type']] ?? $pmColors['Technology'];
        $url = trim((string)($p['website'] ?? ''));
        if ($url !== '' && !preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
        $tag = $url !== '' ? 'a' : 'span';
      ?>
      <<?= $tag ?> class="pm-card"
         <?= $url !== '' ? 'href="' . e($url) . '" target="_blank" rel="noopener"' : '' ?>
         title="<?= e($p['name']) ?>"
         style="--pc:<?= $tc[1] ?>;--pc-border:<?= $tc[2] ?>;--pc-shadow:<?= $tc[3] ?>;--pc-color:<?= $tc[0] ?>">
        <?php if (!empty($p['logo'])): ?>
        <img src="<?= UPLOADS_URL ?>/<?= e($p['logo']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" decoding="async">
        <?php else: ?>
        <span class="pm-init" style="color:<?= $tc[0] ?>"><?= e(strtoupper(mb_substr($p['name'], 0, 2))) ?></span>
        <?php endif; ?>
      </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats strip -->
  <div class="container">
    <div class="pm-stats saas-reveal" style="margin-top:44px">
      <div class="pm-stat"><div class="pm-stat-val" style="color:#c4a5ff"><?= count($allDisplayPartners) ?>+</div><div class="pm-stat-lbl">Partner Companies</div></div>
      <div class="pm-stat"><div class="pm-stat-val" style="color:#34d399"><?= count(array_unique(array_column($allDisplayPartners,'partner_type'))) ?>+</div><div class="pm-stat-lbl">Partnership Categories</div></div>
      <div class="pm-stat"><div class="pm-stat-val" style="color:#8033ff">5★</div><div class="pm-stat-lbl">Partner Satisfaction</div></div>
      <div class="pm-stat"><div class="pm-stat-val" style="color:#8033ff">20+</div><div class="pm-stat-lbl">Countries Served</div></div>
    </div>
  </div>
</section>

<!-- BECOME A PARTNER -->
<section class="become-partner" id="become-partner">
  <div class="container">
    <div class="bp-inner">

      <!-- Left: benefits -->
      <div class="bp-left saas-reveal-right">
        <div class="bp-eyebrow"><i class="fas fa-handshake"></i> Join Our Network</div>
        <h2 class="bp-title">Become a <span>Partner</span></h2>
        <p class="bp-sub">
          Join Appsgain's growing partner ecosystem and unlock new business opportunities, co-marketing benefits, and technical collaboration.
        </p>
        <div class="bp-benefits">
          <?php
          $benefits=[
            ['fa-rocket','rgba(128,51,255,.1)','#8033ff','Co-Marketing Opportunities','Joint webinars, case studies, and featured placement on our website and marketing materials.'],
            ['fa-handshake','rgba(16,185,129,.1)','#10b981','Referral Revenue','Earn competitive referral commissions for every client you introduce to Appsgain.'],
            ['fa-code','rgba(106,0,255,.1)','#6a00ff','Technical Integration','Priority access to our APIs, sandbox environments, and dedicated technical support team.'],
            ['fa-certificate','rgba(106,0,255,.1)','#6a00ff','Certified Partner Badge','Showcase your Appsgain partnership with a verified badge for your website and materials.'],
          ];
          foreach($benefits as [$ic,$bg,$col,$title,$desc]):
          ?>
          <div class="bp-benefit">
            <div class="bp-benefit-icon" style="background:<?= $bg ?>;color:<?= $col ?>">
              <i class="fas <?= $ic ?>"></i>
            </div>
            <div class="bp-benefit-text">
              <h5><?= $title ?></h5>
              <p><?= $desc ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right: form -->
      <div class="saas-reveal">
        <div class="bp-form-card">
          <h3 class="bp-form-title">Partner Application</h3>
          <p class="bp-form-sub">Fill out the form and our partnerships team will get back to you within 48 hours.</p>
          <form class="bp-form" id="partnerForm" onsubmit="submitPartnerForm(event)">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <input type="hidden" name="form_type" value="partner">
            <div class="form-row">
              <div class="fg">
                <label>Company Name <span class="req">*</span></label>
                <input type="text" name="company_name" placeholder="Your company name" required>
              </div>
              <div class="fg">
                <label>Your Name <span class="req">*</span></label>
                <input type="text" name="contact_name" placeholder="Full name" required>
              </div>
            </div>
            <div class="form-row">
              <div class="fg">
                <label>Work Email <span class="req">*</span></label>
                <input type="email" name="email" placeholder="you@company.com" required>
              </div>
              <div class="fg">
                <label>Phone</label>
                <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX">
              </div>
            </div>
            <div class="fg">
              <label>Company Website</label>
              <input type="url" name="website" placeholder="https://yourcompany.com">
            </div>
            <div class="form-row">
              <div class="fg">
                <label>Partnership Type <span class="req">*</span></label>
                <select name="partner_type" required>
                  <option value="">Select type...</option>
                  <option>Technology</option>
                  <option>Cloud</option>
                  <option>Payments</option>
                  <option>Reseller</option>
                  <option>Referral</option>
                  <option>Integration</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="fg">
                <label>Company Size</label>
                <select name="company_size">
                  <option value="">Select size...</option>
                  <option>1–10 employees</option>
                  <option>11–50 employees</option>
                  <option>51–200 employees</option>
                  <option>201–1000 employees</option>
                  <option>1000+ employees</option>
                </select>
              </div>
            </div>
            <div class="fg">
              <label>Why do you want to partner with us?</label>
              <textarea name="message" placeholder="Tell us about your company and how you'd like to collaborate..."></textarea>
            </div>
            <?php require __DIR__ . '/includes/captcha-component.php'; ?>
            <button type="submit" class="submit-btn btn-cta-animated btn-cta-primary btn-cta-full" id="partnerSubmitBtn">
              <i class="fas fa-paper-plane"></i> Submit Application
            </button>
            <div class="bp-form-msg" id="partnerFormMsg"></div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="saas-cta">
  <div class="saas-cta-bubble"></div><div class="saas-cta-bubble"></div>
  <div class="container">
    <div class="saas-cta-inner saas-reveal">
      <h2 class="saas-cta-title">
        Ready to <span style="background:linear-gradient(90deg,#c4a5ff,#c4a5ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Partner With Us?</span>
      </h2>
      <p class="saas-cta-sub">Fill out the partnership form above or contact our team directly to explore collaboration opportunities.</p>
      <div class="saas-cta-btns">
        <a href="#become-partner" class="btn-saas-primary" style="font-size:15px;padding:15px 30px">
          <i class="fas fa-handshake"></i> Apply Now
        </a>
        <a href="<?= SITE_URL ?>/contact.php" class="btn-saas-secondary" style="font-size:15px;padding:15px 30px">
          <i class="fas fa-envelope"></i> Contact Us
        </a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script src="<?= ASSETS_URL ?>/js/3d-saas.js"></script>
<script src="<?= ASSETS_URL ?>/js/captcha-cta.js"></script>
<script>
function submitPartnerForm(e) {
  e.preventDefault();
  var form = document.getElementById('partnerForm');
  var btn  = document.getElementById('partnerSubmitBtn');
  var msg  = document.getElementById('partnerFormMsg');
  var fd   = new FormData(form);
  var phone    = fd.get('phone') || '';
  var captchaEl= form.querySelector('.captcha-input');

  if (!phone || phone.replace(/\D/g,'').length < 7) {
    msg.style.display='block'; msg.className='bp-form-msg error';
    msg.innerHTML='<i class="fas fa-exclamation-circle"></i> Phone number is required.'; return;
  }
  if (captchaEl && captchaEl.value.trim().length !== 6) {
    msg.style.display='block'; msg.className='bp-form-msg error';
    msg.innerHTML='<i class="fas fa-shield-alt"></i> Please complete the CAPTCHA verification.'; return;
  }

  var data = {
    name:         fd.get('contact_name'),
    email:        fd.get('email'),
    phone:        phone,
    company:      fd.get('company_name'),
    service:      'Partnership: ' + (fd.get('partner_type') || 'General'),
    message:      (fd.get('message') || '') + '\n\nWebsite: ' + (fd.get('website')||'') + ' | Size: ' + (fd.get('company_size')||''),
    source:       'partners-page',
    form_type:    'partner',
    csrf_token:   fd.get('<?= CSRF_TOKEN_NAME ?>'),
    captcha_code: captchaEl ? captchaEl.value.trim() : '',
  };
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
  fetch('<?= SITE_URL ?>/api/contact.php', {
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)
  })
  .then(r=>r.json())
  .then(d=>{
    msg.style.display = 'block';
    if(d.ok||d.success){
      msg.className = 'bp-form-msg success';
      msg.innerHTML = '<i class="fas fa-check-circle"></i> Application received! Our partnerships team will contact you within 48 hours.';
      form.reset();
    } else {
      msg.className = 'bp-form-msg error';
      msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (d.message||'Something went wrong. Please try again.');
    }
    btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Application';
  })
  .catch(()=>{
    msg.style.display='block'; msg.className='bp-form-msg error';
    msg.innerHTML='<i class="fas fa-exclamation-circle"></i> Network error. Please email us at <?= e(getSetting("site_email","info@appsgain.in")) ?>';
    btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Application';
  });
}
</script>
</body>
</html>
