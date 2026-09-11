<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'faq';
$schemaPageType  = 'faq';
$pageTitle       = getSetting('meta_title_faq', 'FAQs – Appsgain Technologies | Frequently Asked Questions');
$pageDescription = getSetting('meta_desc_faq', 'Get answers to common questions about Appsgain\'s services, pricing, timelines, support and working process.');
$canonicalUrl    = SITE_URL . '/faq.php';

$catFilter = sanitizeInput($_GET['cat'] ?? '');
$search    = sanitizeInput($_GET['q'] ?? '');

$where  = "is_active = 1";
$params = [];
if ($catFilter) { $where .= " AND page_key = ?"; $params[] = $catFilter; }
if ($search)    { $where .= " AND (question LIKE ? OR answer LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$faqs     = dbFetchAll("SELECT * FROM faqs WHERE $where ORDER BY sort_order ASC, id ASC", $params);
$allFaqs  = dbFetchAll("SELECT COUNT(*) as cnt FROM faqs WHERE is_active=1");
$totalFaqs= (int)($allFaqs[0]['cnt'] ?? 0);
$schemaData = ['faqs' => $faqs];

$pageKeys  = dbFetchAll("SELECT DISTINCT page_key, COUNT(*) as cnt FROM faqs WHERE is_active=1 GROUP BY page_key ORDER BY page_key ASC");
$tabLabels = ['home'=>'General','about'=>'About Us','services'=>'Services','courses'=>'Courses','placement'=>'Placement'];
$tabIcons  = ['home'=>'fa-home','about'=>'fa-building','services'=>'fa-cogs','courses'=>'fa-graduation-cap','placement'=>'fa-briefcase'];
?>
<?php $pageStyles = ['page-hero.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <!-- Typeface loads once via css/style.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
  /* ══ BASE ══ */
  *{box-sizing:border-box;}
  :root{--v:#6a00ff;--b:#5500cc;--c:#6a00ff;--font:var(--font-main);}
  body{font-family:var(--font-body);background:#fbfcfe;}

  /* ══ KEYFRAMES ══ */
  @keyframes heroFloat{0%,100%{transform:translateY(0) rotate(0deg)}33%{transform:translateY(-12px) rotate(3deg)}66%{transform:translateY(-6px) rotate(-2deg)}}
  @keyframes questionPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(106,0,255,.4)}50%{transform:scale(1.04);box-shadow:0 0 0 18px rgba(106,0,255,0)}}
  @keyframes gradFlow{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
  @keyframes sparkle{0%{transform:scale(0) rotate(0deg);opacity:1}100%{transform:scale(1.5) rotate(180deg);opacity:0}}
  @keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
  @keyframes slideRight{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
  @keyframes orbitSpin{to{--oa:360deg}}
  @keyframes dotPing{0%{transform:scale(1);opacity:1}100%{transform:scale(2.5);opacity:0}}
  @keyframes shimmer{0%{left:-100%}100%{left:200%}}
  @keyframes countUp{from{opacity:0;transform:scale(.6)}to{opacity:1;transform:scale(1)}}
  @keyframes tabSlide{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  @keyframes iconSpin{to{transform:rotate(360deg)}}

  /* ══ HERO ══ */
  .fq-hero{
    background:linear-gradient(145deg,#080c1e 0%,#0d1b3e 45%,#1a1260 100%);
    padding:72px 0 56px;position:relative;overflow:hidden;text-align:center;
  }
  .fq-hero::before{
    content:'';position:absolute;inset:0;
    background-image:radial-gradient(rgba(128,51,255,.08) 1.5px,transparent 1.5px);
    background-size:40px 40px;
  }
  .fq-hero-orb{position:absolute;border-radius:50%;filter:blur(70px);pointer-events:none;}
  .fq-hero-orb-1{width:420px;height:420px;top:-160px;right:-80px;background:rgba(106,0,255,.18);}
  .fq-hero-orb-2{width:320px;height:320px;bottom:-100px;left:-60px;background:rgba(106,0,255,.12);}
  .fq-hero-orb-3{width:240px;height:240px;top:30%;left:50%;transform:translateX(-50%);background:rgba(85,0,204,.1);}

  /* Animated Q icon */
  .fq-q-icon{
    width:80px;height:80px;border-radius:50%;
    background:linear-gradient(135deg,var(--v),var(--b));
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 24px;font-size:32px;color:#fff;font-weight:900;
    font-family:var(--font);
    animation:questionPulse 3s ease-in-out infinite;
    box-shadow:0 8px 32px rgba(106,0,255,.4);
    position:relative;z-index:2;
  }
  .fq-q-icon::after{
    content:'?';font-size:32px;font-weight:900;
    animation:heroFloat 4s ease-in-out infinite;
    display:inline-block;
  }

  .fq-hero-badge{
    display:inline-flex;align-items:center;gap:7px;
    background:rgba(128,51,255,.15);border:1px solid rgba(128,51,255,.3);
    color:#e0d0ff;padding:5px 16px;border-radius:30px;
    font-size:11.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;
    margin-bottom:16px;animation:fadeUp .5s ease both;position:relative;z-index:2;
  }
  .fq-hero h1{
    font-family:var(--font);font-size:clamp(26px,4.5vw,48px);font-weight:900;
    color:#fff;line-height:1.15;margin-bottom:14px;letter-spacing:-.5px;
    animation:fadeUp .55s .08s ease both;position:relative;z-index:2;
  }
  .fq-hero h1 span{
    background:linear-gradient(90deg,#c4a5ff,#c4a5ff,#8033ff,#34d399,#c4a5ff);
    background-size:300% auto;-webkit-background-clip:text;
    -webkit-text-fill-color:transparent;background-clip:text;
    animation:gradFlow 5s ease infinite;
  }
  .fq-hero-sub{
    font-size:16px;color:rgba(255,255,255,.6);max-width:500px;margin:0 auto 32px;
    line-height:1.75;animation:fadeUp .6s .16s ease both;position:relative;z-index:2;
  }

  /* Stats */
  .fq-hero-stats{
    display:flex;align-items:center;justify-content:center;gap:32px;
    margin-bottom:32px;flex-wrap:wrap;position:relative;z-index:2;
    animation:fadeUp .6s .24s ease both;
  }
  .fq-stat{text-align:center;}
  .fq-stat-num{font-family:var(--font);font-size:28px;font-weight:900;color:#fff;line-height:1;}
  .fq-stat-num em{font-style:normal;color:#c4a5ff;}
  .fq-stat-lbl{font-size:11.5px;color:rgba(255,255,255,.45);font-weight:600;margin-top:3px;text-transform:uppercase;letter-spacing:.5px;}
  .fq-stat-sep{width:1px;height:40px;background:rgba(255,255,255,.12);}

  /* Search bar */
  .fq-search-wrap{
    max-width:580px;margin:0 auto;position:relative;z-index:3;
    animation:fadeUp .6s .32s ease both;
  }
  .fq-search-inner{
    display:flex;align-items:center;gap:0;
    background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.18);
    border-radius:50px;backdrop-filter:blur(20px);overflow:hidden;
    transition:border-color .25s,box-shadow .25s;
  }
  .fq-search-inner:focus-within{
    border-color:rgba(196,165,255,.5);
    box-shadow:0 0 0 4px rgba(106,0,255,.15);
  }
  .fq-search-icon{padding:0 16px;color:rgba(255,255,255,.45);font-size:15px;flex-shrink:0;}
  .fq-search-input{
    flex:1;background:none;border:none;outline:none;color:#fff;font-size:14.5px;
    padding:14px 0;font-family:inherit;
  }
  .fq-search-input::placeholder{color:rgba(255,255,255,.35);}
  .fq-search-btn{
    padding:10px 22px;background:linear-gradient(135deg,var(--v),var(--b));
    color:#fff;border:none;font-size:13.5px;font-weight:700;cursor:pointer;
    font-family:inherit;margin:5px;border-radius:40px;transition:.2s;white-space:nowrap;
  }
  .fq-search-btn:hover{opacity:.88;}

  /* Breadcrumb */
  .fq-crumbs{
    display:flex;align-items:center;justify-content:center;gap:7px;
    font-size:12.5px;color:rgba(255,255,255,.35);margin-top:24px;
    animation:fadeUp .6s .4s ease both;position:relative;z-index:2;
  }
  .fq-crumbs a{color:rgba(255,255,255,.5);text-decoration:none;transition:.2s;}
  .fq-crumbs a:hover{color:#c4a5ff;}
  .fq-crumbs i{font-size:9px;}

  /* ══ TABS ══ */
  .fq-tabs-section{background:#fff;border-bottom:1px solid #e8ecf3;padding:0;position:sticky;top:72px;z-index:90;}
  .fq-tabs-inner{display:flex;overflow-x:auto;scrollbar-width:none;gap:0;}
  .fq-tabs-inner::-webkit-scrollbar{display:none;}
  .fq-tab{
    display:inline-flex;align-items:center;gap:7px;padding:16px 20px;
    font-size:13.5px;font-weight:700;color:#8b90a0;text-decoration:none;
    border-bottom:3px solid transparent;white-space:nowrap;transition:all .25s;
    position:relative;
  }
  .fq-tab:hover{color:#6a00ff;}
  .fq-tab.active{color:#6a00ff;border-bottom-color:#6a00ff;}
  .fq-tab .fq-tab-count{
    font-size:10.5px;font-weight:800;padding:2px 7px;border-radius:20px;
    background:rgba(128,51,255,.1);color:#6a00ff;
    transition:.25s;
  }
  .fq-tab.active .fq-tab-count{background:#6a00ff;color:#fff;}

  /* ══ FAQ BODY ══ */
  .fq-body{padding:48px 0 64px;background:#fbfcfe;}
  .fq-layout{display:grid;grid-template-columns:1fr 280px;gap:32px;align-items:start;}

  /* ══ FAQ ITEMS ══ */
  .fq-list{display:flex;flex-direction:column;gap:12px;}
  .fq-item{
    background:#fff;border-radius:16px;border:1.5px solid #e8ecf3;
    overflow:hidden;transition:all .35s cubic-bezier(.4,0,.2,1);
    box-shadow:0 2px 8px rgba(0,0,0,.04);
    opacity:0;transform:translateY(20px);
  }
  .fq-item.visible{opacity:1;transform:translateY(0);}
  .fq-item.open{
    border-color:#6a00ff;
    box-shadow:0 8px 32px rgba(128,51,255,.12);
  }
  .fq-item-q{
    display:flex;align-items:center;gap:14px;padding:18px 20px;
    cursor:pointer;user-select:none;transition:background .2s;
  }
  .fq-item:hover .fq-item-q{background:rgba(128,51,255,.025);}
  .fq-item.open .fq-item-q{background:linear-gradient(135deg,rgba(128,51,255,.06),rgba(85,0,204,.03));}

  /* Number badge */
  .fq-item-num{
    width:32px;height:32px;border-radius:9px;flex-shrink:0;
    background:linear-gradient(135deg,rgba(128,51,255,.1),rgba(85,0,204,.08));
    color:#6a00ff;font-size:12.5px;font-weight:800;
    display:flex;align-items:center;justify-content:center;
    font-family:var(--font);transition:.25s;
  }
  .fq-item.open .fq-item-num{background:linear-gradient(135deg,#6a00ff,#5500cc);color:#fff;}

  .fq-item-q-text{flex:1;font-family:var(--font);font-size:15px;font-weight:700;color:#0b1026;line-height:1.45;}
  .fq-item.open .fq-item-q-text{color:#6a00ff;}

  /* Animated chevron */
  .fq-chevron{
    width:28px;height:28px;border-radius:8px;flex-shrink:0;
    background:rgba(128,51,255,.08);color:#6a00ff;
    display:flex;align-items:center;justify-content:center;font-size:12px;
    transition:transform .35s cubic-bezier(.34,1.56,.64,1),background .25s;
  }
  .fq-item.open .fq-chevron{transform:rotate(180deg);background:#6a00ff;color:#fff;}

  /* Answer panel with smooth height animation */
  .fq-item-a{
    max-height:0;overflow:hidden;
    transition:max-height .45s cubic-bezier(.4,0,.2,1);
  }
  .fq-item.open .fq-item-a{max-height:800px;}
  .fq-item-a-inner{
    padding:0 20px 20px 66px;font-size:14.5px;color:#475569;line-height:1.85;
    border-top:1px solid rgba(128,51,255,.08);padding-top:16px;
    position:relative;
  }
  .fq-item-a-inner::before{
    content:'';position:absolute;left:32px;top:0;bottom:0;
    width:2px;background:linear-gradient(180deg,#6a00ff,transparent);
    border-radius:2px;
  }

  /* ══ SIDEBAR ══ */
  .fq-sidebar-card{
    background:#fff;border:1.5px solid #e8ecf3;border-radius:16px;
    padding:22px;margin-bottom:16px;box-shadow:0 2px 12px rgba(0,0,0,.04);
  }
  .fq-sidebar-title{font-family:var(--font);font-size:14px;font-weight:800;color:#0b1026;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid rgba(128,51,255,.15);display:flex;align-items:center;gap:7px;}
  .fq-sidebar-title i{font-size:13px;color:#6a00ff;}

  /* Quick links */
  .fq-quick-link{
    display:flex;align-items:center;gap:10px;padding:9px 0;
    border-bottom:1px solid #f7f8fc;text-decoration:none;transition:.2s;
  }
  .fq-quick-link:last-child{border-bottom:none;padding-bottom:0;}
  .fq-quick-link:hover{padding-left:4px;}
  .fq-quick-link-ic{width:28px;height:28px;border-radius:8px;background:rgba(128,51,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:11px;color:#6a00ff;transition:.2s;}
  .fq-quick-link:hover .fq-quick-link-ic{background:#6a00ff;color:#fff;}
  .fq-quick-link span{font-size:13px;font-weight:600;color:#242a42;transition:.2s;}
  .fq-quick-link:hover span{color:#6a00ff;}

  /* ══ CTA SECTION ══ */
  .fq-cta{
    margin-top:48px;
    background:linear-gradient(135deg,#0b1026 0%,#1e1b4b 50%,#0b1026 100%);
    border-radius:24px;padding:44px 40px;text-align:center;
    position:relative;overflow:hidden;
  }
  .fq-cta::before{
    content:'';position:absolute;inset:0;
    background-image:radial-gradient(rgba(128,51,255,.08) 1px,transparent 1px);
    background-size:36px 36px;
  }
  .fq-cta-orb{position:absolute;border-radius:50%;filter:blur(50px);pointer-events:none;}
  .fq-cta-orb-1{width:250px;height:250px;top:-80px;right:-60px;background:rgba(106,0,255,.2);}
  .fq-cta-orb-2{width:180px;height:180px;bottom:-60px;left:-40px;background:rgba(106,0,255,.15);}
  .fq-cta-icon{
    width:64px;height:64px;border-radius:50%;
    background:linear-gradient(135deg,var(--v),var(--b));
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 18px;font-size:26px;color:#fff;
    box-shadow:0 8px 24px rgba(106,0,255,.4);
    position:relative;z-index:1;
    animation:questionPulse 3s 1s ease-in-out infinite;
  }
  .fq-cta h3{font-family:var(--font);font-size:22px;font-weight:800;color:#fff;margin-bottom:10px;position:relative;z-index:1;}
  .fq-cta p{font-size:14.5px;color:rgba(255,255,255,.6);margin-bottom:22px;line-height:1.7;position:relative;z-index:1;max-width:400px;margin-left:auto;margin-right:auto;}
  .fq-cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1;}
  .fq-cta-btn{
    display:inline-flex;align-items:center;gap:7px;padding:11px 22px;
    border-radius:12px;font-size:13.5px;font-weight:700;text-decoration:none;transition:all .25s;font-family:inherit;
  }
  .fq-cta-btn-primary{background:#fff;color:#6a00ff;box-shadow:0 4px 16px rgba(255,255,255,.15);}
  .fq-cta-btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,255,255,.2);}
  .fq-cta-btn-wa{background:#25d366;color:#fff;box-shadow:0 4px 16px rgba(37,211,102,.3);}
  .fq-cta-btn-wa:hover{transform:translateY(-2px);background:#1eb855;}

  /* ══ EMPTY STATE ══ */
  .fq-empty{
    text-align:center;padding:64px 32px;background:#fff;border-radius:20px;border:2px dashed #e7e9f0;
  }
  .fq-empty i{font-size:48px;color:#cbd5e1;display:block;margin-bottom:16px;}
  .fq-empty h3{font-family:var(--font);font-size:18px;font-weight:800;color:#0b1026;margin-bottom:8px;}
  .fq-empty p{font-size:14px;color:#8b90a0;margin-bottom:20px;}

  /* ══ FLOATING DECORATIONS ══ */
  .fq-deco{position:absolute;pointer-events:none;opacity:.12;}
  .fq-deco-1{top:15%;left:3%;font-size:48px;color:#6a00ff;animation:heroFloat 7s ease-in-out infinite;}
  .fq-deco-2{top:25%;right:4%;font-size:32px;color:#6a00ff;animation:heroFloat 9s 2s ease-in-out infinite reverse;}
  .fq-deco-3{bottom:20%;left:6%;font-size:24px;color:#6a00ff;animation:heroFloat 6s 1s ease-in-out infinite;}

  /* ══ RESPONSIVE ══ */
  @media(max-width:900px){.fq-layout{grid-template-columns:1fr;}.fq-sidebar{display:grid;grid-template-columns:1fr 1fr;gap:16px;}}
  @media(max-width:640px){
    .fq-hero{padding:52px 0 40px;}
    .fq-hero h1{font-size:26px;}
    .fq-item-a-inner{padding-left:20px;padding-bottom:16px;}
    .fq-item-a-inner::before{display:none;}
    .fq-hero-stats{gap:20px;}
    .fq-cta{padding:32px 24px;}
    .fq-sidebar{grid-template-columns:1fr !important;}
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ══ HERO ══ -->
<?php pageHero([
  'image'   => 'secimg_hero_faq',
  'eyebrow' => 'Support',
  'lead'    => 'Frequently Asked',
  'accent'  => 'Questions',
  'sub'     => 'Everything worth knowing before starting a project with us — scope, pricing, timelines and support.',
  'crumbs'  => [['FAQs', null]],
  'actions' => [['Ask Us Directly', '/contact.php#enquiry', true]],
]); ?>

<!-- ══ TAB FILTER ══ -->
<?php if (!empty($pageKeys)): ?>
<div class="fq-tabs-section">
  <div class="container" style="padding:0 16px;">
    <div class="fq-tabs-inner">
      <a href="<?= SITE_URL ?>/faq.php<?= $search ? '?q='.urlencode($search) : '' ?>"
         class="fq-tab <?= !$catFilter ? 'active' : '' ?>">
        <i class="fas fa-border-all"></i> All Questions
        <span class="fq-tab-count"><?= $totalFaqs ?></span>
      </a>
      <?php foreach ($pageKeys as $pk):
        $label = $tabLabels[$pk['page_key']] ?? ucfirst($pk['page_key']);
        $icon  = $tabIcons[$pk['page_key']]  ?? 'fa-tag';
      ?>
      <a href="<?= SITE_URL ?>/faq.php?cat=<?= urlencode($pk['page_key']) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
         class="fq-tab <?= $catFilter === $pk['page_key'] ? 'active' : '' ?>">
        <i class="fas <?= $icon ?>"></i> <?= e($label) ?>
        <span class="fq-tab-count"><?= $pk['cnt'] ?></span>
      </a>
      <?php endforeach; ?>
      <?php if ($search || $catFilter): ?>
      <a href="<?= SITE_URL ?>/faq.php" class="fq-tab" style="color:#6a00ff;margin-left:auto;"><i class="fas fa-times"></i> Clear</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══ BODY ══ -->
<section class="fq-body">
  <div class="container">
    <div class="fq-layout">

      <!-- FAQ List -->
      <div>
        <?php if ($search): ?>
        <p style="font-size:13.5px;color:#5e6475;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-search" style="color:#6a00ff;"></i>
          <span><?= count($faqs) ?> result<?= count($faqs) !== 1 ? 's' : '' ?> for <strong style="color:#0b1026;">"<?= e($search) ?>"</strong></span>
          <a href="<?= SITE_URL ?>/faq.php" style="color:#6a00ff;font-weight:700;text-decoration:none;margin-left:4px;"><i class="fas fa-times"></i> Clear</a>
        </p>
        <?php endif; ?>

        <?php if (!empty($faqs)): ?>
        <div class="fq-list" id="faqList">
          <?php foreach ($faqs as $idx => $faq): ?>
          <div class="fq-item" data-idx="<?= $idx ?>">
            <div class="fq-item-q" onclick="toggleFaq(this)">
              <div class="fq-item-num"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></div>
              <span class="fq-item-q-text"><?= e($faq['question']) ?></span>
              <span class="fq-chevron"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="fq-item-a">
              <div class="fq-item-a-inner"><?= nl2br(e($faq['answer'])) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="fq-empty">
          <i class="fas fa-search"></i>
          <h3>No Questions Found</h3>
          <p><?= $search ? 'No results for "'.e($search).'". Try different keywords.' : 'No FAQs in this category yet.' ?></p>
          <a href="<?= SITE_URL ?>/faq.php" class="btn-cta-animated btn-cta-primary" style="display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:12px;background:linear-gradient(135deg,#6a00ff,#5500cc);color:#fff;text-decoration:none;font-weight:700;font-size:13.5px;">
            <i class="fas fa-list"></i> View All FAQs
          </a>
        </div>
        <?php endif; ?>

        <!-- CTA Card -->
        <div class="fq-cta">
          <div class="fq-cta-orb fq-cta-orb-1"></div>
          <div class="fq-cta-orb fq-cta-orb-2"></div>
          <div class="fq-cta-icon"><i class="fas fa-headset"></i></div>
          <h3>Still Have Questions?</h3>
          <p>Can't find what you're looking for? Our team responds within 24 hours — no jargon, no pressure.</p>
          <div class="fq-cta-btns">
            <a href="<?= SITE_URL ?>/contact.php#enquiry" class="fq-cta-btn fq-cta-btn-primary"><i class="fas fa-envelope"></i> Email Us</a>
            <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', getSetting('site_whatsapp', getSetting('whatsapp', getSetting('phone','919955446477'))))) ?>" target="_blank" rel="noopener" class="fq-cta-btn fq-cta-btn-wa"><i class="fab fa-whatsapp"></i> WhatsApp</a>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <aside class="fq-sidebar">
        <!-- Popular topics -->
        <div class="fq-sidebar-card">
          <div class="fq-sidebar-title"><i class="fas fa-fire"></i> Quick Topics</div>
          <?php
          $quickTopics = [
            ['How long does a project take?',          'fa-clock'],
            ['What is your pricing model?',            'fa-tags'],
            ['Do you sign NDAs?',                      'fa-shield-alt'],
            ['Will I own the source code?',            'fa-code'],
            ['Do you provide post-launch support?',    'fa-headset'],
            ['Can you work with our existing team?',   'fa-users'],
          ];
          foreach ($quickTopics as [$ql, $qi]):
          ?>
          <div class="fq-quick-link" style="cursor:pointer;" onclick="searchFaq('<?= e(addslashes($ql)) ?>')">
            <div class="fq-quick-link-ic"><i class="fas <?= $qi ?>"></i></div>
            <span><?= e($ql) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Contact sidebar -->
        <div class="fq-sidebar-card" style="background:linear-gradient(135deg,#0b1026,#1e1b4b);border-color:rgba(128,51,255,.2);">
          <div class="fq-sidebar-title" style="color:#fff;border-bottom-color:rgba(255,255,255,.1);">
            <i class="fas fa-comments" style="color:#c4a5ff;"></i>
            <span style="color:#fff;">Need Personalised Help?</span>
          </div>
          <p style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:16px;line-height:1.6;">Our team is here to answer your specific questions and build a proposal just for you.</p>
          <a href="<?= SITE_URL ?>/contact.php#enquiry"
             style="display:flex;align-items:center;justify-content:center;gap:7px;padding:11px 16px;background:linear-gradient(135deg,var(--v),var(--b));color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;margin-bottom:10px;transition:.2s;"
             onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-paper-plane"></i> Start a Conversation
          </a>
          <?php $ph = getSetting('site_phone', getSetting('phone','+91-9955446477')); ?>
          <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $ph)) ?>"
             style="display:flex;align-items:center;justify-content:center;gap:7px;font-size:13px;font-weight:700;color:rgba(255,255,255,.7);text-decoration:none;transition:.2s;"
             onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.7)'">
            <i class="fas fa-phone-alt" style="color:#c4a5ff;font-size:12px;"></i> <?= e($ph) ?>
          </a>
        </div>

        <!-- Stats card -->
        <div class="fq-sidebar-card" style="background:linear-gradient(135deg,rgba(128,51,255,.06),rgba(85,0,204,.04));border-color:rgba(128,51,255,.15);">
          <div class="fq-sidebar-title"><i class="fas fa-chart-line"></i> By the Numbers</div>
          <?php
          $sideStats = [
            ['500+',   'Projects Delivered', '#6a00ff'],
            ['98%',    'Client Satisfaction','#059669'],
            ['8+ Yrs', 'Industry Experience','#5500cc'],
            ['24h',    'Support Response',   '#46009f'],
          ];
          foreach ($sideStats as [$sv, $sl, $sc]):
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(128,51,255,.07);font-size:13px;">
            <span style="color:#5e6475;font-weight:600;"><?= $sl ?></span>
            <span style="font-family:var(--font);font-weight:900;color:<?= $sc ?>;"><?= $sv ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script>
/* ── FAQ accordion toggle ── */
function toggleFaq(btn) {
  var item = btn.closest('.fq-item');
  var wasOpen = item.classList.contains('open');
  /* Close all open items */
  document.querySelectorAll('.fq-item.open').forEach(function(el) {
    el.classList.remove('open');
  });
  /* Open clicked if it was closed */
  if (!wasOpen) item.classList.add('open');
}

/* ── Quick topic search ── */
function searchFaq(q) {
  var input = document.querySelector('.fq-search-input');
  if (input) {
    input.value = q;
    input.closest('form').submit();
  }
}

/* ── Staggered entrance animations ── */
(function() {
  var items = document.querySelectorAll('.fq-item');
  var io = new IntersectionObserver(function(entries) {
    entries.forEach(function(en) {
      if (en.isIntersecting) {
        var idx = parseInt(en.target.dataset.idx || 0);
        setTimeout(function() {
          en.target.classList.add('visible');
        }, idx * 60);
        io.unobserve(en.target);
      }
    });
  }, {threshold: 0.05, rootMargin: '0px 0px -30px 0px'});
  items.forEach(function(item) { io.observe(item); });
})();

/* ── Auto-open first item ── */
document.addEventListener('DOMContentLoaded', function() {
  var first = document.querySelector('.fq-item');
  if (first) {
    setTimeout(function() { first.classList.add('open'); }, 600);
  }
});

/* ── Keyboard: press Enter on focused question to toggle ── */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && e.target.classList.contains('fq-item-q')) {
    toggleFaq(e.target);
  }
});
</script>
</body>
</html>
