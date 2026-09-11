<?php
/**
 * Appsgain — Site Header v6 (premium commerce layout)
 *
 * Three rows, the pattern high-end storefronts use:
 *   1. Utility bar  — contact details + availability + socials
 *   2. Main bar     — logo · site search · call + quote CTA   (sticky)
 *   3. Nav bar      — icon menu, services mega panel, company dropdown
 *
 * Mobile collapses rows 2–3 into a slide-in sidebar with search,
 * accordion sections, CTAs, contact block and socials.
 *
 * Everything is admin-controlled (Admin → Settings).
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }
require_once __DIR__ . '/social-links.php';

$_s        = getAllSettings();
$_siteUrl  = SITE_URL;
$_siteName = $_s['site_name'] ?? 'Appsgain Technologies';
$_phone    = $_s['site_phone']   ?? $_s['phone']   ?? '+91-9955446477';
$_email    = $_s['site_email']   ?? $_s['email']   ?? 'info@appsgain.in';
$_address  = $_s['site_address'] ?? $_s['address'] ?? '';
$_phoneClean = preg_replace('/[^+0-9]/', '', $_phone);

/* Payment link — Admin → Settings → Business Info. The Cashfree form is the
   default so the button works out of the box; clearing the setting removes
   the button rather than leaving a dead link in the header. */
$_payUrl   = trim((string)($_s['payment_link'] ?? 'https://payments.cashfree.com/forms/appsgaintechnologies'));
$_payLabel = trim((string)($_s['payment_label'] ?? '')) ?: 'Pay Now';

$_fb = $_s['site_facebook']  ?? '';
$_ig = $_s['site_instagram'] ?? '';
$_li = $_s['site_linkedin']  ?? '';
$_tw = $_s['site_twitter']   ?? '';
$_yt = $_s['site_youtube']   ?? '';

$_logo = !empty($_s['site_logo'])
       ? (str_starts_with($_s['site_logo'], 'http') ? $_s['site_logo'] : UPLOADS_URL . '/' . ltrim($_s['site_logo'], '/'))
       : '';

$_activePage = $activePage ?? '';

if (!function_exists('navActive')) {
    function navActive(string $page, string $active): string {
        return $page === $active ? ' is-active' : '';
    }
}

/* ── Services for the mega panel, grouped ── */
$_navServices = dbFetchAll(
    "SELECT name, slug,
            COALESCE(icon, icon_class, 'fa-cog') AS icon,
            COALESCE(service_group, category, 'Services') AS service_group
     FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 12"
);
$_svcGroups = [];
foreach ($_navServices as $_svc) {
    $_svcGroups[$_svc['service_group'] ?: 'Services'][] = $_svc;
}
if (!$_svcGroups) {
    $_svcGroups = ['Services' => [
        ['name' => 'Custom Software', 'slug' => 'custom-software-development', 'icon' => 'fa-laptop-code'],
        ['name' => 'Mobile Apps',     'slug' => 'mobile-app-development',      'icon' => 'fa-mobile-screen'],
    ]];
}

/* ── Primary nav, icons included ── */
$_navItems = [
    ['home',      'Home',      '/',             'fa-house'],
    ['about',     'About',     '/about-us',    'fa-circle-info'],
    ['portfolio', 'Portfolio', '/portfolio.php','fa-briefcase'],
    ['blog',      'Blog',      '/blog.php',     'fa-newspaper'],
    ['contact',   'Contact',   '/contact.php',  'fa-envelope'],
];

/* Company dropdown */
$_companyLinks = [
    ['About Appsgain', '/about-us',        'fa-building',    ''],
    ['Gallery',        '/gallery.php',      'fa-images',      ''],
    ['Our Products',   '/products.php',     'fa-box-open',    ''],
    ['Mobile Apps',    '/apps.php',         'fa-mobile-screen',''],
    ['Partners',       '/partners.php',     'fa-people-group',''],
    ['Careers',        '/careers.php',      'fa-rocket',      'Hiring'],
    ['FAQs',           '/faq.php',          'fa-circle-question',''],
];

$_socialLinks = agSocialLinks($_s);
?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/brand.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/hero.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/modern.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/captcha-cta.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/responsive.css">
<?php $_brandSettings = $_s; require __DIR__ . '/brand-vars.php'; ?>
<?php
/* Page-specific stylesheets load LAST so they win over the framework.
   A page sets $pageStyles = ['services.css']; before including this file. */
foreach ((array)($pageStyles ?? []) as $_css):
  $_css = basename($_css);
  if (!preg_match('/^[a-z0-9._-]+\.css$/i', $_css)) continue;
  /* Stamp the file's mtime so an edited stylesheet is fetched again.
     Without this a browser keeps serving the version it cached, which
     shows up as a page that is only partly styled. */
  $_p = ROOT_PATH . '/css/' . $_css;
  $_v = is_file($_p) ? filemtime($_p) : 1;   /* ASSET_VERSION is not defined; 1 is a safe fallback */ ?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/<?= e($_css) ?>?v=<?= $_v ?>">
<?php endforeach; ?>

<!-- ══════════════════════════════════════════════════
     ROW 1 · UTILITY BAR
═══════════════════════════════════════════════════ -->
<div class="hx-util" id="hxUtil">
  <div class="hx-wrap hx-util-in">
    <div class="hx-util-left">
      <a href="tel:<?= e($_phoneClean) ?>" class="hx-util-item hx-u-phone">
        <i class="fas fa-phone" aria-hidden="true"></i><?= e($_phone) ?>
      </a>
      <?php /* hides with the email it separates, or it is left dangling */ ?>
      <span class="hx-util-div hx-hide-md" aria-hidden="true"></span>
      <a href="mailto:<?= e($_email) ?>" class="hx-util-item hx-hide-md hx-u-mail">
        <i class="fas fa-envelope" aria-hidden="true"></i><?= e($_email) ?>
      </a>
      <?php if ($_address): ?>
      <span class="hx-util-div hx-hide-lg" aria-hidden="true"></span>
      <span class="hx-util-item hx-hide-lg hx-u-addr">
        <i class="fas fa-location-dot" aria-hidden="true"></i><?= e($_address) ?>
      </span>
      <?php endif; ?>
    </div>
    <div class="hx-util-right">
      <span class="hx-avail hx-hide-md"><span class="hx-avail-dot" aria-hidden="true"></span>Available for Projects</span>
      <?php if ($_socialLinks): ?>
      <span class="hx-util-div hx-hide-md" aria-hidden="true"></span>
      <div class="hx-util-social">
        <?php foreach ($_socialLinks as $_sl): ?>
        <a href="<?= e($_sl['url']) ?>" target="_blank" rel="noopener me"
           aria-label="<?= e($_sl['label']) ?>" title="<?= e($_sl['label']) ?>"
           style="--sc:<?= e($_sl['color']) ?>">
          <i class="<?= e($_sl['icon']) ?>" aria-hidden="true"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     ROW 2 + 3 · STICKY HEADER
═══════════════════════════════════════════════════ -->
<header class="hx" id="hxHeader">

  <!-- Main bar -->
  <div class="hx-main">
    <div class="hx-wrap hx-main-in">

      <a href="<?= $_siteUrl ?>/" class="hx-logo" aria-label="<?= e($_siteName) ?> home">
        <?php if ($_logo): ?>
          <img src="<?= e($_logo) ?>" alt="<?= e($_siteName) ?>" width="200" height="52">
        <?php else: ?>
          <span class="hx-logo-txt"><?= e($_siteName) ?></span>
        <?php endif; ?>
      </a>

      <!-- Primary menu -->
      <nav class="hx-nav" aria-label="Main navigation">
        <ul class="hx-menu">

          <?php foreach (array_slice($_navItems, 0, 2) as [$_key, $_label, $_url, $_ico]): ?>
          <li class="hx-item">
            <a href="<?= $_siteUrl . $_url ?>" class="hx-link<?= navActive($_key, $_activePage) ?>">
              <i class="fas <?= $_ico ?>" aria-hidden="true"></i><?= e($_label) ?>
            </a>
          </li>
          <?php endforeach; ?>

          <!-- Services mega -->
          <li class="hx-item hx-has-panel">
            <button class="hx-link hx-trigger<?= navActive('services', $_activePage) ?>"
                    aria-expanded="false" aria-haspopup="true">
              <i class="fas fa-layer-group" aria-hidden="true"></i>Services
              <i class="fas fa-chevron-down hx-caret" aria-hidden="true"></i>
            </button>
            <div class="hx-mega" role="menu">
              <div class="hx-wrap hx-mega-in">
                <div class="hx-mega-cols">
                  <?php foreach ($_svcGroups as $_gName => $_gItems): ?>
                  <div class="hx-mega-col">
                    <span class="hx-mega-label"><?= e($_gName) ?></span>
                    <ul>
                      <?php foreach ($_gItems as $_si): ?>
                      <li>
                        <a href="<?= $_siteUrl ?>/service/<?= e($_si['slug']) ?>">
                          <span class="hx-mega-ico"><i class="fas <?= e($_si['icon'] ?: 'fa-cog') ?>" aria-hidden="true"></i></span>
                          <?= e($_si['name']) ?>
                        </a>
                      </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="hx-mega-foot">
                  <a href="<?= $_siteUrl ?>/services.php" class="hx-mega-all">
                    View all services <i class="fas fa-arrow-right" aria-hidden="true"></i>
                  </a>
                </div>
              </div>
            </div>
          </li>

          <!-- Company dropdown -->
          <li class="hx-item hx-has-panel">
            <button class="hx-link hx-trigger<?= in_array($_activePage, ["clients","partners","testimonials","gallery","careers","support","faq","products","apps"], true) ? " is-active" : "" ?>"
                    aria-expanded="false" aria-haspopup="true">
              <i class="fas fa-building" aria-hidden="true"></i>Company
              <i class="fas fa-chevron-down hx-caret" aria-hidden="true"></i>
            </button>
            <div class="hx-drop" role="menu">
              <?php foreach ($_companyLinks as [$_cLabel, $_cUrl, $_cIco, $_cBadge]): ?>
              <a href="<?= $_siteUrl . $_cUrl ?>" class="hx-drop-link">
                <span class="hx-drop-ico"><i class="fas <?= $_cIco ?>" aria-hidden="true"></i></span>
                <?= e($_cLabel) ?>
                <?php if ($_cBadge): ?><em class="hx-badge"><?= e($_cBadge) ?></em><?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
          </li>

          <?php foreach (array_slice($_navItems, 2) as [$_key, $_label, $_url, $_ico]): ?>
          <li class="hx-item">
            <a href="<?= $_siteUrl . $_url ?>" class="hx-link<?= navActive($_key, $_activePage) ?>">
              <i class="fas <?= $_ico ?>" aria-hidden="true"></i><?= e($_label) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="hx-actions">
        <a href="tel:<?= e($_phoneClean) ?>" class="hx-icon-btn hx-hide-sm" aria-label="Call us" title="Call us">
          <i class="fas fa-phone" aria-hidden="true"></i>
        </a>
        <?php if ($_payUrl !== ''): ?>
        <?php /* Payments leave the site, so this opens in a new tab and drops
                 the referrer. The sheen is a child element rather than a
                 pseudo-element so it can be clipped without a second stack. */ ?>
        <a href="<?= e($_payUrl) ?>" class="hx-pay" target="_blank" rel="noopener noreferrer">
          <span class="hx-pay-sheen" aria-hidden="true"></span>
          <i class="fas fa-bolt" aria-hidden="true"></i><span><?= e($_payLabel) ?></span>
        </a>
        <?php endif; ?>
        <a href="<?= $_siteUrl ?>/contact.php#enquiry" class="hx-cta">
          <i class="fas fa-paper-plane" aria-hidden="true"></i><span>Get a Quote</span>
        </a>
        <button class="hx-burger" id="hxBurger" aria-label="Open menu" aria-expanded="false" aria-controls="hxDrawer">
          <span></span><span></span><span></span>
        </button>
      </div>

    </div>
  </div>

</header>

<!-- ══════════════════════════════════════════════════
     MOBILE SIDEBAR
═══════════════════════════════════════════════════ -->
<div class="hx-scrim" id="hxScrim" hidden></div>

<aside class="hx-drawer" id="hxDrawer" aria-hidden="true" aria-label="Menu">
  <div class="hx-drawer-head">
    <a href="<?= $_siteUrl ?>/" class="hx-drawer-logo">
      <?php if ($_logo): ?>
        <img src="<?= e($_logo) ?>" alt="<?= e($_siteName) ?>" width="150" height="39">
      <?php else: ?>
        <span class="hx-logo-txt"><?= e($_siteName) ?></span>
      <?php endif; ?>
    </a>
    <button class="hx-drawer-close" id="hxDrawerClose" aria-label="Close menu">
      <i class="fas fa-xmark" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="hx-drawer-body" aria-label="Mobile navigation">

    <?php foreach (array_slice($_navItems, 0, 2) as [$_key, $_label, $_url, $_ico]): ?>
    <a href="<?= $_siteUrl . $_url ?>" class="hx-m-link<?= navActive($_key, $_activePage) ?>">
      <span class="hx-m-ico"><i class="fas <?= $_ico ?>" aria-hidden="true"></i></span>
      <span><?= e($_label) ?></span>
      <i class="fas fa-angle-right hx-m-arrow" aria-hidden="true"></i>
    </a>
    <?php endforeach; ?>

    <!-- Services accordion -->
    <div class="hx-acc">
      <button class="hx-acc-btn" aria-expanded="false">
        <span class="hx-m-ico"><i class="fas fa-layer-group" aria-hidden="true"></i></span>
        <span>Services</span>
        <i class="fas fa-chevron-down hx-acc-caret" aria-hidden="true"></i>
      </button>
      <div class="hx-acc-body">
        <?php foreach ($_navServices as $_si): ?>
        <a href="<?= $_siteUrl ?>/service/<?= e($_si['slug']) ?>">
          <i class="fas <?= e($_si['icon'] ?: 'fa-cog') ?>" aria-hidden="true"></i><?= e($_si['name']) ?>
        </a>
        <?php endforeach; ?>
        <a href="<?= $_siteUrl ?>/services.php" class="hx-acc-all">
          <i class="fas fa-grip" aria-hidden="true"></i>View all services
        </a>
      </div>
    </div>

    <!-- Company accordion -->
    <div class="hx-acc">
      <button class="hx-acc-btn" aria-expanded="false">
        <span class="hx-m-ico"><i class="fas fa-building" aria-hidden="true"></i></span>
        <span>Company</span>
        <i class="fas fa-chevron-down hx-acc-caret" aria-hidden="true"></i>
      </button>
      <div class="hx-acc-body">
        <?php foreach ($_companyLinks as [$_cLabel, $_cUrl, $_cIco, $_cBadge]): ?>
        <a href="<?= $_siteUrl . $_cUrl ?>">
          <i class="fas <?= $_cIco ?>" aria-hidden="true"></i><?= e($_cLabel) ?>
          <?php if ($_cBadge): ?><em class="hx-badge"><?= e($_cBadge) ?></em><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php foreach (array_slice($_navItems, 2) as [$_key, $_label, $_url, $_ico]): ?>
    <a href="<?= $_siteUrl . $_url ?>" class="hx-m-link<?= navActive($_key, $_activePage) ?>">
      <span class="hx-m-ico"><i class="fas <?= $_ico ?>" aria-hidden="true"></i></span>
      <span><?= e($_label) ?></span>
      <i class="fas fa-angle-right hx-m-arrow" aria-hidden="true"></i>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="hx-drawer-foot">
    <?php if ($_payUrl !== ''): ?>
    <a href="<?= e($_payUrl) ?>" class="hx-drawer-cta hx-drawer-pay" target="_blank" rel="noopener noreferrer">
      <span class="hx-pay-sheen" aria-hidden="true"></span>
      <i class="fas fa-bolt" aria-hidden="true"></i> <?= e($_payLabel) ?>
    </a>
    <?php endif; ?>
    <a href="<?= $_siteUrl ?>/contact.php#enquiry" class="hx-drawer-cta">
      <i class="fas fa-paper-plane" aria-hidden="true"></i> Get a Free Quote
    </a>
    <div class="hx-drawer-contact">
      <a href="tel:<?= e($_phoneClean) ?>"><i class="fas fa-phone" aria-hidden="true"></i><?= e($_phone) ?></a>
      <a href="mailto:<?= e($_email) ?>"><i class="fas fa-envelope" aria-hidden="true"></i><?= e($_email) ?></a>
    </div>
    <?php if ($_socialLinks): ?>
    <div class="hx-drawer-social">
      <?php foreach ($_socialLinks as $_sl): ?>
      <a href="<?= e($_sl['url']) ?>" target="_blank" rel="noopener me"
         aria-label="<?= e($_sl['label']) ?>" style="--sc:<?= e($_sl['color']) ?>">
        <i class="<?= e($_sl['icon']) ?>" aria-hidden="true"></i></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</aside>

<style>
/* ══════════════════════════════════════════════════════
   HEADER — premium commerce layout
   navy #10142B · ink #11162D · body #5E6475 · line #E7E9F0
   ══════════════════════════════════════════════════════ */
.hx-util, .hx, .hx-drawer{
  --h-navy:#10142B; --h-ink:#11162D; --h-body:#5E6475;
  --h-mute:#6E7386; --h-line:#E7E9F0; --h-soft:#F7F8FC;
  --h-grad:linear-gradient(135deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  --h-grad-btn:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  --h-grad-90:linear-gradient(90deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  font-family:var(--font-body),var(--font-main),system-ui,sans-serif;
}
.hx-wrap{
  width:100%; max-width:1280px; margin-inline:auto;
  padding-inline:clamp(16px,3vw,28px);
}
.hx-sr{
  position:absolute; width:1px; height:1px; padding:0; margin:-1px;
  overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; border:0;
}

/* ── Row 1 · utility ── */
/* ══ Utility bar ═══════════════════════════════════════
   The ramp deliberately stops at #E0006E. White text clears 4.5:1 on
   every stop up to there; past it (#FF3030 -> #FF8A00) it drops to
   3.67 and then 2.36, which is how a label goes missing. */
.hx-util{
  position:relative;
  font-size:12.5px; color:#fff;
  background:#6A00FF;
  background-image:linear-gradient(90deg,
    #4A00B0 0%, #6A00FF 26%, #9D00D3 52%, #D000A8 78%, #E0006E 100%);
  background-size:180% 100%;
  animation:hxUtilDrift 22s ease-in-out infinite;
}
@keyframes hxUtilDrift{
  0%,100%{ background-position:0% 50%; }
  50%    { background-position:100% 50%; }
}
@media (prefers-reduced-motion:reduce){
  .hx-util{ animation:none; background-size:100% 100%; }
}
/* Hairline highlight along the top edge */
.hx-util::after{
  content:""; position:absolute; left:0; right:0; bottom:0; height:1px;
  background:rgba(255,255,255,.16); pointer-events:none;
}

.hx-util-in{ display:flex; align-items:center; justify-content:space-between; gap:16px; height:38px; }
.hx-util-left, .hx-util-right{ display:flex; align-items:center; gap:12px; min-width:0; }

.hx-util-item{
  display:inline-flex; align-items:center; gap:7px;
  color:rgba(255,255,255,.94); font-weight:500;
  text-decoration:none; white-space:nowrap;
  overflow:hidden; text-overflow:ellipsis;
  transition:color .18s ease;
}
.hx-util-item:hover{ color:#fff; }
/* Icons sit on the violet half of the ramp, where these tints run
   4.2:1 or better — comfortably past the 3:1 graphics threshold. */
.hx-util-item i{ font-size:11px; transition:transform .18s ease; }
.hx-u-phone i{ color:#FFC24B; }
.hx-u-mail  i{ color:#7CF5C4; }
.hx-u-addr  i{ color:#8FD6FF; }
.hx-util-item:hover i{ transform:scale(1.12); }

.hx-util-div{ width:1px; height:14px; background:rgba(255,255,255,.26); flex:0 0 auto; }

.hx-avail{
  display:inline-flex; align-items:center; gap:7px;
  padding:3px 11px 3px 9px; border-radius:999px;
  color:#fff; font-weight:600; white-space:nowrap;
  background:rgba(255,255,255,.14);
  border:1px solid rgba(255,255,255,.22);
}
.hx-avail-dot{
  width:7px; height:7px; border-radius:50%; background:#3DF08A;
  box-shadow:0 0 0 3px rgba(61,240,138,.24);
  animation:hxPulse 2.4s ease-in-out infinite;
}
@keyframes hxPulse{
  0%,100%{ box-shadow:0 0 0 3px rgba(61,240,138,.24); }
  50%    { box-shadow:0 0 0 5px rgba(61,240,138,.10); }
}
@media (prefers-reduced-motion:reduce){ .hx-avail-dot{ animation:none; } }

/* Social chips invert: a white tile carries each platform's own colour,
   which stays legible over the magenta end of the bar. */
.hx-util-social{ display:flex; gap:5px; }
.hx-util-social a{
  width:26px; height:26px; border-radius:8px;
  display:grid; place-items:center; font-size:12px;
  color:var(--sc,#6A00FF); background:#fff; text-decoration:none;
  box-shadow:0 1px 3px rgba(16,22,47,.18);
  transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
}
.hx-util-social a:hover{
  transform:translateY(-2px);
  background:var(--sc,#6A00FF); color:#fff;
  box-shadow:0 5px 14px rgba(16,22,47,.28);
}
.hx-util-social a:focus-visible{ outline:2px solid #fff; outline-offset:2px; }
/* ── Sticky shell ── */
.hx{
  position:sticky; top:0; z-index:900;
  background:#fff; border-bottom:1px solid var(--h-line);
  transition:box-shadow .2s ease, background .2s ease;
  will-change:box-shadow;
}
/* Slight translucency + blur once it detaches from the page top */
.hx.is-stuck{
  box-shadow:0 2px 16px rgba(17,22,45,.10);
  background:rgba(255,255,255,.94);
  backdrop-filter:saturate(1.4) blur(10px);
  -webkit-backdrop-filter:saturate(1.4) blur(10px);
}
/* Anchor targets clear the pinned bar */
:target{ scroll-margin-top:96px; }
html{ scroll-behavior:smooth; }
@media (prefers-reduced-motion:reduce){ html{ scroll-behavior:auto; } }

/* ── Row 2 · main (logo · menu · actions) ── */
.hx-main-in{
  display:flex; align-items:center; gap:clamp(14px,2.4vw,32px);
  height:76px; transition:height .2s ease;
}
.hx.is-stuck .hx-main-in{ height:64px; }
.hx-logo{ flex:0 0 auto; display:inline-flex; align-items:center; }
.hx-logo img{ height:auto; max-height:44px; width:auto; object-fit:contain; display:block; }
.hx.is-stuck .hx-logo img{ max-height:38px; }
.hx-logo-txt{ font-size:19px; font-weight:800; color:var(--h-ink); }

/* Search */
.hx-search{ position:relative; flex:1 1 auto; max-width:520px; }
.hx-search-box{
  display:flex; align-items:center;
  height:46px; padding-left:42px; padding-right:4px;
  background:var(--h-soft); border:1px solid var(--h-line); border-radius:11px;
  transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.hx-search-box:focus-within{
  background:#fff; border-color:#6A00FF; box-shadow:0 0 0 3px rgba(106,0,255,.10);
}
.hx-search-ico{
  position:absolute; left:16px; top:50%; transform:translateY(-50%);
  font-size:14px; color:var(--h-mute); pointer-events:none;
}
.hx-search-box input{
  flex:1 1 auto; min-width:0; height:100%;
  border:0; background:transparent; outline:none;
  font-family:inherit; font-size:14px; color:var(--h-ink);
}
.hx-search-box input::placeholder{ color:var(--h-mute); }
.hx-search-box input::-webkit-search-cancel-button{ -webkit-appearance:none; }
.hx-search-btn{
  flex:0 0 auto; height:38px; padding:0 18px; border:0; border-radius:9px; cursor:pointer;
  background-color:#8B00E0; background-image:var(--h-grad-btn);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-family:inherit; font-size:13.5px; font-weight:600;
  transition:background-position .3s ease;
}
.hx-search-btn:hover{ background-position:100% 50%; }

.hx-search-panel{
  position:absolute; top:calc(100% + 8px); left:0; right:0; z-index:40;
  max-height:60vh; overflow-y:auto;
  background:#fff; border:1px solid var(--h-line); border-radius:12px;
  box-shadow:0 14px 40px rgba(17,22,45,.13);
  padding:6px;
}
.hx-search-panel a{
  display:block; padding:10px 12px; border-radius:9px;
  text-decoration:none; transition:background .15s ease;
}
.hx-search-panel a:hover, .hx-search-panel a.is-cursor{ background:var(--h-soft); }
.hx-sr-type{
  display:inline-block; margin-bottom:3px;
  font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#6A00FF;
}
.hx-sr-title{ display:block; font-size:14px; font-weight:600; color:var(--h-ink); }
.hx-sr-desc{ display:block; font-size:12.5px; color:var(--h-mute); margin-top:2px; }
.hx-sr-empty{ padding:16px 12px; font-size:13.5px; color:var(--h-mute); text-align:center; }

/* Actions */
.hx-actions{ flex:0 0 auto; display:flex; align-items:center; gap:10px; margin-left:auto; }
.hx-icon-btn{
  width:44px; height:44px; border-radius:11px;
  display:grid; place-items:center; font-size:14.5px;
  color:var(--h-ink); background:#fff; border:1px solid var(--h-line); text-decoration:none;
  transition:border-color .18s ease, color .18s ease;
}
.hx-icon-btn:hover{ border-color:rgba(106,0,255,.38); color:#6A00FF; }
.hx-cta{
  display:inline-flex; align-items:center; gap:9px;
  height:44px; padding:0 22px; border-radius:11px;
  background-color:#8B00E0; background-image:var(--h-grad-btn);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-size:14.5px; font-weight:600; text-decoration:none; white-space:nowrap;
  box-shadow:0 4px 14px rgba(106,0,255,.22);
  transition:background-position .3s ease, transform .18s ease, box-shadow .18s ease;
}
.hx-cta:hover{
  background-position:100% 50%; color:#fff;
  transform:translateY(-2px); box-shadow:0 8px 22px rgba(106,0,255,.28);
}

/* ── Pay Now ──────────────────────────────────────────────
   Carries the full brand gradient rather than the violet button ramp, so it
   reads as a different action from "Get a Quote" sitting beside it. Three
   things move: the gradient drifts, a sheen crosses every few seconds, and
   the glow breathes. All of it stops under prefers-reduced-motion. */
.hx-pay{
  position:relative; overflow:hidden; isolation:isolate;
  display:inline-flex; align-items:center; gap:9px;
  height:44px; padding:0 22px; border-radius:11px;
  background-color:#F50072; background-image:var(--h-grad);
  background-size:260% 100%;
  color:#fff; font-size:14.5px; font-weight:700; text-decoration:none; white-space:nowrap;
  box-shadow:0 4px 16px rgba(245,0,114,.30);
  animation:hxPayDrift 7s ease-in-out infinite, hxPayGlow 3.2s ease-in-out infinite;
  transition:transform .18s ease, box-shadow .18s ease;
}
.hx-pay i{ font-size:13px; }
.hx-pay:hover{
  color:#fff; transform:translateY(-2px) scale(1.02);
  box-shadow:0 10px 26px rgba(245,0,114,.42);
}
.hx-pay:active{ transform:translateY(0) scale(.99); }
.hx-pay:focus-visible{ outline:3px solid rgba(255,255,255,.85); outline-offset:2px; }

/* The travelling highlight. Skewed so the edge reads as a light sweep
   rather than a moving bar. */
.hx-pay-sheen{
  position:absolute; top:-60%; left:-70%; width:45%; height:220%;
  background:linear-gradient(90deg,
    rgba(255,255,255,0) 0%, rgba(255,255,255,.55) 50%, rgba(255,255,255,0) 100%);
  transform:skewX(-22deg); pointer-events:none; z-index:-1;
  animation:hxPaySheen 4.5s ease-in-out infinite;
}
@keyframes hxPayDrift{ 0%,100%{ background-position:0% 50%; } 50%{ background-position:100% 50%; } }
@keyframes hxPayGlow{
  0%,100%{ box-shadow:0 4px 16px rgba(245,0,114,.30); }
  50%    { box-shadow:0 6px 22px rgba(208,0,168,.48); }
}
/* Idle for most of the cycle, then one quick pass — a sweep that never rests
   reads as a loading bar. */
@keyframes hxPaySheen{ 0%,55%{ left:-70%; } 85%,100%{ left:130%; } }

@media (prefers-reduced-motion:reduce){
  .hx-pay{ animation:none; background-position:50% 50%; }
  .hx-pay-sheen{ animation:none; opacity:0; }
  .hx-pay:hover{ transform:none; }
}
.hx-burger{
  display:none; width:44px; height:44px; border-radius:11px; cursor:pointer;
  background:#fff; border:1px solid var(--h-line);
  flex-direction:column; align-items:center; justify-content:center; gap:4px;
}
.hx-burger span{
  width:18px; height:2px; border-radius:2px; background:var(--h-ink);
  transition:transform .22s ease, opacity .22s ease;
}
.hx-burger.is-open span:nth-child(1){ transform:translateY(6px) rotate(45deg); }
.hx-burger.is-open span:nth-child(2){ opacity:0; }
.hx-burger.is-open span:nth-child(3){ transform:translateY(-6px) rotate(-45deg); }

/* ── Primary menu, inline in the sticky bar ── */
.hx-nav{ flex:1 1 auto; display:flex; justify-content:center; min-width:0; }
.hx-menu{
  display:flex; align-items:center; gap:2px;
  list-style:none; margin:0; padding:0;
}
.hx-item{ position:relative; }
.hx-link{
  position:relative;
  display:inline-flex; align-items:center; gap:7px;
  height:76px; padding:0 13px;
  border:0; background:transparent; cursor:pointer;
  font-family:inherit; font-size:14px; font-weight:500; color:var(--h-ink);
  text-decoration:none; white-space:nowrap;
  transition:color .18s ease;
}
.hx-link i:first-child{ font-size:13px; color:var(--h-mute); transition:color .18s ease, transform .18s ease; }
.hx-link:hover{ color:#6A00FF; }
.hx-link:hover i:first-child{ color:#6A00FF; transform:translateY(-1px); }
.hx-link.is-active{ color:#6A00FF; font-weight:600; }
.hx-link.is-active i:first-child{ color:#6A00FF; }
.hx-link.is-active::after,
.hx-item:hover > .hx-link::after{
  content:""; position:absolute; left:14px; right:14px; bottom:0;
  height:2px; border-radius:2px 2px 0 0; background:var(--h-grad-90);
}
.hx.is-stuck .hx-link{ height:64px; }
.hx-caret{ font-size:9px !important; color:var(--h-mute) !important; transition:transform .2s ease; }
.hx-item.is-open .hx-caret{ transform:rotate(180deg); }


/* ── Mega panel — sized to its content, anchored under its own item ── */
.hx-has-panel{ position:relative; }
.hx-mega{
  position:absolute; top:100%; left:0;
  width:max-content;
  max-width:min(860px, calc(100vw - 32px));
  background:#fff;
  border:1px solid var(--h-line); border-top:0;
  border-radius:0 0 14px 14px;
  box-shadow:0 18px 44px rgba(17,22,45,.13);
  opacity:0; visibility:hidden; transform:translateY(-8px);
  transition:opacity .2s ease, transform .2s ease, visibility .2s ease;
}
.hx-item.is-open .hx-mega{ opacity:1; visibility:visible; transform:translateY(0); }
/* The panel is its own container now — cancel the page-width wrapper */
.hx-mega .hx-wrap{ max-width:none; margin:0; padding-inline:0; }
.hx-mega-in{ padding:24px 26px 18px; }
.hx-mega-cols{
  display:grid;
  grid-auto-flow:column;
  grid-auto-columns:minmax(180px,220px);
  gap:0 30px;
}
.hx-mega-label{
  display:block; margin-bottom:12px; padding-bottom:9px;
  border-bottom:1px solid var(--h-line);
  font-size:11px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:var(--h-mute);
}
.hx-mega-col ul{ list-style:none; margin:0; padding:0; }
.hx-mega-col li + li{ margin-top:2px; }
.hx-mega-col a{
  display:flex; align-items:center; gap:10px;
  padding:8px 8px 8px 6px; border-radius:9px;
  font-size:14px; font-weight:500; color:var(--h-ink); text-decoration:none;
  transition:background .16s ease, color .16s ease;
}
.hx-mega-col a:hover{ background:var(--h-soft); color:#6A00FF; }
.hx-mega-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:30px; height:30px; border-radius:8px;
  background:rgba(106,0,255,.06); color:#6A00FF; font-size:12.5px;
  transition:background .16s ease;
}
.hx-mega-col a:hover .hx-mega-ico{ background:rgba(106,0,255,.13); }


.hx-mega-foot{
  margin-top:18px; padding-top:14px;
  border-top:1px solid var(--h-line);
  display:flex; justify-content:flex-end;
}
.hx-mega-all{
  display:inline-flex; align-items:center; gap:8px;
  font-size:13.5px; font-weight:600; color:#6A00FF; text-decoration:none;
  transition:gap .18s ease;
}
.hx-mega-all:hover{ gap:12px; color:#6A00FF; }
.hx-mega-all i{ font-size:11px; }

/* ── Company dropdown ── */
.hx-drop{
  position:absolute; top:100%; left:0; z-index:30;
  display:grid; grid-template-columns:repeat(2,minmax(160px,190px)); gap:2px 12px;
  width:max-content; max-width:calc(100vw - 32px); padding:14px;
  background:#fff; border:1px solid var(--h-line); border-radius:0 0 14px 14px;
  box-shadow:0 16px 38px rgba(17,22,45,.12);
  opacity:0; visibility:hidden; transform:translateY(-8px);
  transition:opacity .2s ease, transform .2s ease, visibility .2s ease;
}
.hx-item.is-open .hx-drop{ opacity:1; visibility:visible; transform:translateY(0); }
.hx-drop-link{
  display:flex; align-items:center; gap:10px;
  padding:9px 10px; border-radius:9px;
  font-size:14px; font-weight:500; color:var(--h-ink); text-decoration:none;
  transition:background .16s ease, color .16s ease;
}
.hx-drop-link:hover{ background:var(--h-soft); color:#6A00FF; }
.hx-drop-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:28px; height:28px; border-radius:8px;
  background:rgba(106,0,255,.06); color:#6A00FF; font-size:12px;
}
.hx-badge{
  font-style:normal; font-size:9.5px; font-weight:700; letter-spacing:.05em;
  padding:2px 7px; border-radius:999px; color:#fff;
  background-color:#8B00E0; background-image:var(--h-grad-btn);
}

/* ── Mobile sidebar ── */
.hx-scrim{
  position:fixed; inset:0; z-index:950;
  background:rgba(11,16,38,.55); backdrop-filter:blur(2px);
  opacity:0; transition:opacity .25s ease;
}
.hx-scrim.is-on{ opacity:1; }
.hx-drawer{
  position:fixed; top:0; right:0; bottom:0; z-index:960;
  width:min(360px,88vw);
  display:flex; flex-direction:column;
  background:#fff; box-shadow:-8px 0 32px rgba(17,22,45,.16);
  transform:translateX(102%);
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  overscroll-behavior:contain;
}
.hx-drawer.is-open{ transform:translateX(0); }
.hx-drawer-head{
  display:flex; align-items:center; justify-content:space-between; gap:12px;
  padding:16px 18px; border-bottom:1px solid var(--h-line); flex:0 0 auto;
}
.hx-drawer-logo img{ height:auto; max-height:36px; width:auto; display:block; }
.hx-drawer-close{
  width:38px; height:38px; border-radius:10px; cursor:pointer;
  display:grid; place-items:center; font-size:16px;
  background:#fff; border:1px solid var(--h-line); color:var(--h-ink);
  transition:border-color .18s ease, color .18s ease;
}
.hx-drawer-close:hover{ border-color:#6A00FF; color:#6A00FF; }

.hx-drawer-body{ flex:1 1 auto; overflow-y:auto; padding:8px 12px 16px; }
.hx-m-link, .hx-acc-btn{
  width:100%; display:flex; align-items:center; gap:12px;
  padding:12px 10px; border:0; background:transparent; cursor:pointer;
  font-family:inherit; font-size:15px; font-weight:500; color:var(--h-ink);
  text-decoration:none; text-align:left; border-radius:10px;
  transition:background .16s ease, color .16s ease;
}
.hx-m-link:hover, .hx-acc-btn:hover{ background:var(--h-soft); color:#6A00FF; }
.hx-m-link.is-active{ background:rgba(106,0,255,.06); color:#6A00FF; font-weight:600; }
.hx-m-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:34px; height:34px; border-radius:9px;
  background:rgba(106,0,255,.06); color:#6A00FF; font-size:13px;
}
.hx-m-link span:nth-of-type(2){ flex:1 1 auto; }
.hx-acc-btn > span:nth-of-type(2){ flex:1 1 auto; }
.hx-m-arrow{ font-size:14px; color:#C9CCD6; }
.hx-acc-caret{ font-size:11px; color:var(--h-mute); transition:transform .22s ease; }
.hx-acc-btn[aria-expanded="true"] .hx-acc-caret{ transform:rotate(180deg); }
.hx-acc-body{
  display:grid; grid-template-rows:0fr;
  transition:grid-template-rows .26s cubic-bezier(.4,0,.2,1);
}
.hx-acc.is-open .hx-acc-body{ grid-template-rows:1fr; }
.hx-acc-body > *{ overflow:hidden; }
.hx-acc-body{ overflow:hidden; }
.hx-acc-body a{
  display:flex; align-items:center; gap:10px;
  margin-left:22px; padding:10px 10px; border-radius:9px;
  border-left:2px solid var(--h-line);
  font-size:14px; color:var(--h-body); text-decoration:none;
  transition:color .16s ease, border-color .16s ease, background .16s ease;
}
.hx-acc-body a:hover{ color:#6A00FF; border-color:#6A00FF; background:var(--h-soft); }
.hx-acc-body a i{ font-size:12px; color:var(--h-mute); width:16px; text-align:center; }
.hx-acc-body a:hover i{ color:#6A00FF; }
.hx-acc-all{ font-weight:600; color:#6A00FF !important; }

.hx-drawer-foot{ flex:0 0 auto; padding:16px 18px; border-top:1px solid var(--h-line); background:var(--h-soft); }
.hx-drawer-cta{
  display:flex; align-items:center; justify-content:center; gap:9px;
  height:48px; border-radius:11px; margin-bottom:14px;
  background-color:#8B00E0; background-image:var(--h-grad-btn);
  color:#fff; font-size:15px; font-weight:600; text-decoration:none;
  box-shadow:0 4px 14px rgba(106,0,255,.22);
}
.hx-drawer-cta:hover{ color:#fff; }
.hx-drawer-pay{
  position:relative; overflow:hidden; isolation:isolate; font-weight:700;
  background-color:#F50072; background-image:var(--h-grad);
  background-size:260% 100%;
  box-shadow:0 4px 16px rgba(245,0,114,.30);
  animation:hxPayDrift 7s ease-in-out infinite, hxPayGlow 3.2s ease-in-out infinite;
}
@media (prefers-reduced-motion:reduce){ .hx-drawer-pay{ animation:none; } }
.hx-drawer-contact{ display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
.hx-drawer-contact a{
  display:flex; align-items:center; gap:9px;
  font-size:13.5px; color:var(--h-body); text-decoration:none;
}
.hx-drawer-contact a:hover{ color:#6A00FF; }
.hx-drawer-contact i{ font-size:12px; color:#6A00FF; width:15px; }
.hx-drawer-social{ display:flex; gap:8px; }
.hx-drawer-social a{
  width:38px; height:38px; border-radius:50%;
  display:grid; place-items:center; font-size:14px;
  background:#fff; border:1px solid var(--h-line); color:var(--h-ink); text-decoration:none;
  transition:border-color .18s ease, color .18s ease;
}
.hx-drawer-social a:hover{ border-color:#6A00FF; color:#6A00FF; }

/* ── Responsive ── */
.hx-hide-sm{ }
@media (max-width:1100px){
  .hx-hide-lg{ display:none !important; }
  .hx-search{ max-width:340px; }
}
@media (max-width:980px){
  .hx-nav{ display:none; }
  .hx-burger{ display:flex; }
  .hx-hide-md{ display:none !important; }
}
@media (max-width:820px){
  .hx-search{ display:none; }              /* search lives in the drawer */
  .hx-main-in{ height:66px; }
}
@media (max-width:820px){
  /* Two gradient buttons plus a burger crowds the bar. Pay keeps its label
     because it is the action being highlighted; Quote drops to its icon. */
  .hx-cta span{ display:none; }
  .hx-cta{ padding:0 14px; }
}
@media (max-width:560px){
  .hx-hide-sm{ display:none !important; }
  .hx-cta span{ display:none; }
  .hx-cta{ padding:0 16px; }
  /* Below this the drawer carries Pay, so the bar keeps only one button. */
  .hx-pay{ display:none; }
  .hx-util-in{ height:34px; font-size:11.5px; }
  .hx-logo img{ max-height:34px; }
}
@media (prefers-reduced-motion:reduce){
  .hx-drawer, .hx-scrim, .hx-mega, .hx-drop, .hx-acc-body{ transition:none !important; }
}
</style>

<script>
(function () {
  var header = document.getElementById('hxHeader');

  /* ── Sticky condense ── */
  if (header) {
    var onScroll = function () { header.classList.toggle('is-stuck', window.scrollY > 60); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Desktop panels: hover on pointer devices, click/keys always ── */
  var panelItems = Array.prototype.slice.call(document.querySelectorAll('.hx-has-panel'));
  /* Pointer must be able to travel from the trigger down into the panel,
     which lives outside the header box — so closing is driven by the
     item's own mouseleave (the panel is a descendant, so it does not
     fire while the pointer is inside it) plus a short grace period. */
  var hoverable = window.matchMedia('(hover:hover) and (pointer:fine)').matches;
  var closeTimer = null;

  function openPanel(li) {
    clearTimeout(closeTimer);
    panelItems.forEach(function (other) {
      if (other === li) return;
      other.classList.remove('is-open');
      var ot = other.querySelector('.hx-trigger');
      if (ot) ot.setAttribute('aria-expanded', 'false');
    });
    li.classList.add('is-open');
    var t = li.querySelector('.hx-trigger');
    if (t) t.setAttribute('aria-expanded', 'true');
  }

  function closeAll() {
    clearTimeout(closeTimer);
    panelItems.forEach(function (li) {
      li.classList.remove('is-open');
      var t = li.querySelector('.hx-trigger');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  }

  function scheduleClose() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(closeAll, 220);
  }

  panelItems.forEach(function (li) {
    var trigger = li.querySelector('.hx-trigger');
    if (!trigger) return;

    /* Click always toggles — and because hover no longer fights it,
       clicking an already-hovered item closes it as expected. */
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      li.classList.contains('is-open') ? closeAll() : openPanel(li);
    });

    if (hoverable) {
      li.addEventListener('mouseenter', function () { openPanel(li); });
      li.addEventListener('mouseleave', scheduleClose);
    }

    /* Keyboard: opening on focus, closing when focus leaves the item */
    li.addEventListener('focusin',  function () { openPanel(li); });
    li.addEventListener('focusout', function (e) {
      if (!li.contains(e.relatedTarget)) scheduleClose();
    });

    /* Following a link inside the panel closes it */
    li.addEventListener('click', function (e) {
      if (e.target.closest('a')) closeAll();
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.hx-has-panel')) closeAll();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeAll(); closeDrawer(); }
  });
  /* ── Keep an open panel inside the viewport ──
     Panels are content-width and anchored to their item's left edge, so
     an item near the right can overflow. Shift it back just enough. */
  function fitPanel(li) {
    var panel = li.querySelector('.hx-mega, .hx-drop');
    if (!panel) return;
    panel.style.left = '0px';
    var rect = panel.getBoundingClientRect();
    var pad  = 16;
    var over = rect.right - (window.innerWidth - pad);
    if (over > 0) panel.style.left = (-over) + 'px';
    /* never push it off the left edge either */
    var newRect = panel.getBoundingClientRect();
    if (newRect.left < pad) panel.style.left = (parseFloat(panel.style.left) + (pad - newRect.left)) + 'px';
  }
  panelItems.forEach(function (li) {
    var obs = new MutationObserver(function () {
      if (li.classList.contains('is-open')) fitPanel(li);
    });
    obs.observe(li, { attributes: true, attributeFilter: ['class'] });
  });
  window.addEventListener('resize', function () {
    panelItems.forEach(function (li) { if (li.classList.contains('is-open')) fitPanel(li); });
  });
  /* ── Mobile drawer ── */
  var burger = document.getElementById('hxBurger');
  var drawer = document.getElementById('hxDrawer');
  var scrim  = document.getElementById('hxScrim');
  var closeB = document.getElementById('hxDrawerClose');
  var lastFocus = null;

  function openDrawer() {
    if (!drawer) return;
    lastFocus = document.activeElement;
    scrim.hidden = false;
    requestAnimationFrame(function () { scrim.classList.add('is-on'); });
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    burger.classList.add('is-open');
    burger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    if (closeB) closeB.focus();
  }
  function closeDrawer() {
    if (!drawer || !drawer.classList.contains('is-open')) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    scrim.classList.remove('is-on');
    setTimeout(function () { scrim.hidden = true; }, 250);
    burger.classList.remove('is-open');
    burger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }
  if (burger) burger.addEventListener('click', function () {
    drawer.classList.contains('is-open') ? closeDrawer() : openDrawer();
  });
  if (closeB) closeB.addEventListener('click', closeDrawer);
  if (scrim)  scrim.addEventListener('click', closeDrawer);

  /* Keep focus inside the open drawer */
  if (drawer) drawer.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab') return;
    var f = drawer.querySelectorAll('a[href], button, input, [tabindex]:not([tabindex="-1"])');
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  /* ── Drawer accordions ── */
  document.querySelectorAll('.hx-acc-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var acc  = btn.parentElement;
      var open = acc.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
})();
</script>
