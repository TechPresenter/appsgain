<?php
/**
 * Appsgain Technologies — Complete SEO Meta System v3.0
 * OG · Twitter · Schema · GA4 · GTM · IndexNow · Verification
 * E-E-A-T · Core Web Vitals · Rich Snippets · Knowledge Graph
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

/* ── Load schema system ── */
require_once __DIR__ . '/seo-schema.php';

/* ── Site settings ── */
$_ms = getAllSettings();

/* ── Per-page SEO from DB ─────────────────────────────────
   Resolution order for the page key:
     $metaPage (explicit) → $seoPageKey → $activePage
   Detail pages (blog post, service, product, app, project) set
   $seoIsDetail = true so the record's own SEO always wins.        */
$_seoKey = $metaPage ?? ($seoPageKey ?? ($activePage ?? ''));
$_isDetail = !empty($seoIsDetail);
$_seoRow = [];
if ($_seoKey !== '' && !$_isDetail) {
    $_seoRow = dbFetchOne(
        "SELECT * FROM seo_settings WHERE page_key = ? AND is_active = 1",
        [$_seoKey]
    ) ?: [];
}

/* Small helper: first non-empty string from the arguments. */
$_pick = static function (...$vals): string {
    foreach ($vals as $v) {
        if (is_string($v) && trim($v) !== '') return trim($v);
    }
    return '';
};

/* ── Resolve final values ── */
$_siteName = $_pick($_ms['site_name'] ?? '', 'Appsgain Technologies');
$_company  = $_pick($_ms['company_name'] ?? '', $_siteName);
$_siteUrl  = rtrim(SITE_URL, '/');
$_curUrl   = $canonicalUrl ?? (SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/'));
$_curUrl   = strtok($_curUrl, '?'); // strip query string from canonical
/* An explicit canonical set in Admin overrides everything. */
$_curUrl   = $_pick($_seoRow['canonical_url'] ?? '', $_curUrl);

/* Title — Admin value wins on static pages, page value wins on detail pages */
$_title = $_isDetail
    ? $_pick($pageTitle ?? '', $_seoRow['meta_title'] ?? '', $_siteName)
    : $_pick($_seoRow['meta_title'] ?? '', $pageTitle ?? '', $_siteName);
/* Append brand if not already there and the title is short enough */
if (!str_contains($_title, $_siteName) && strlen($_title) < 52) {
    $_title .= ' | ' . $_siteName;
}

/* Description — 150–160 chars ideal */
$_desc = $_isDetail
    ? $_pick($pageDescription ?? '', $_seoRow['meta_description'] ?? '', $_ms['meta_description'] ?? '')
    : $_pick($_seoRow['meta_description'] ?? '', $pageDescription ?? '', $_ms['meta_description'] ?? '');
if ($_desc === '') {
    $_desc = 'Appsgain Technologies Private Limited delivers custom software development, mobile apps, ERP, CRM, AI solutions, SaaS platforms and enterprise software.';
}
if (strlen($_desc) > 160) $_desc = rtrim(substr($_desc, 0, 157)) . '...';

/* Keywords */
$_kw = $_pick($_seoRow['meta_keywords'] ?? '', $pageKeywords ?? '', $_ms['meta_keywords'] ?? '');

/* OG / Twitter image — 1200×630 recommended */
$_imgUrl = static function (string $v): string {
    if ($v === '') return '';
    if (str_starts_with($v, 'http')) return $v;
    return rtrim(UPLOADS_URL, '/') . '/' . ltrim($v, '/');
};
$_ogImg = $_imgUrl($_pick($_seoRow['og_image'] ?? '', $_ms['og_default_image'] ?? '', $_ms['site_logo'] ?? ''));
if (!$_ogImg) $_ogImg = $_siteUrl . '/og-default.jpg';

$_ogTitle = $_pick($_seoRow['og_title'] ?? '', $pageTitle ?? '', explode(' | ', $_title)[0]);
$_ogDesc  = $_pick($_seoRow['og_description'] ?? '', $_desc);
$_ogType  = $_pick($_seoRow['og_type'] ?? '', $ogType ?? '', 'website');

$_twCard  = $_pick($_seoRow['twitter_card'] ?? '', 'summary_large_image');
$_twTitle = $_pick($_seoRow['twitter_title'] ?? '', $_ogTitle);
$_twDesc  = $_pick($_seoRow['twitter_description'] ?? '', $_ogDesc);
$_twImg   = $_imgUrl($_pick($_seoRow['twitter_image'] ?? '', '')) ?: $_ogImg;

/* Robots directive — Admin noindex/nofollow checkboxes win */
$_robots = $_pick($_seoRow['robots'] ?? '', 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1');
if (!empty($_seoRow['noindex']) || !empty($_seoRow['nofollow'])) {
    $_robots = (!empty($_seoRow['noindex']) ? 'noindex' : 'index')
             . ',' . (!empty($_seoRow['nofollow']) ? 'nofollow' : 'follow');
}
$_robotsIndexable = !str_contains($_robots, 'noindex');

/* Author / Published / Modified */
$_author   = $_company;
$_pubDate  = defined('PAGE_PUBLISHED') ? PAGE_PUBLISHED : ($_ms['company_founded_year'] ?? '2018') . '-01-01';
$_modDate  = defined('PAGE_MODIFIED')  ? PAGE_MODIFIED  : date('Y-m-d');

/* Analytics IDs — every one of these is Admin-editable */
$_gaId     = trim($_ms['google_analytics']      ?? '');
$_gtmId    = trim($_ms['google_tag_manager']    ?? '');
$_gscId    = trim($_ms['google_search_console'] ?? '');
$_bingId   = trim($_ms['bing_verification']     ?? '');
$_fbDomain = trim($_ms['facebook_domain']       ?? '');
$_yandexId = trim($_ms['yandex_verification']   ?? '');
$_pinId    = trim($_ms['pinterest_verification']?? '');
$_nortonId = trim($_ms['norton_verification']   ?? '');
$_indexNow = trim($_ms['indexnow_key']          ?? '');
$_fbPixel  = trim($_ms['facebook_pixel']        ?? '');
$_clarity  = trim($_ms['microsoft_clarity']     ?? '');
$_hotjar   = trim($_ms['hotjar_id']             ?? '');
$_liInsight= trim($_ms['linkedin_insight']      ?? '');

/* Mark GA/GTM as emitted so the footer never loads them a second time */
if (!defined('AG_ANALYTICS_RENDERED')) define('AG_ANALYTICS_RENDERED', true);

/* Language / locale */
$_lang     = 'en-IN';
$_locale   = 'en_IN';
$_themeCol = $_pick($_ms['theme_color'] ?? '', '#6a00ff');

/* Brand handles + geo — all Admin-controlled */
$_twitterHandle = $_pick($_ms['twitter_handle'] ?? '', '');
$_twDomain      = $_pick($_ms['seo_twitter_domain'] ?? '', parse_url(SITE_URL, PHP_URL_HOST) ?: '');
$_fbProfile     = $_pick($_ms['site_facebook'] ?? '', '');
$_geoRegion     = $_pick($_ms['address_region_code'] ?? '', '');
$_geoPlace      = trim(implode(', ', array_filter([
                    $_ms['address_locality'] ?? '', $_ms['address_region'] ?? '', $_ms['address_country_name'] ?? 'India'
                  ])), ', ');
$_geoLat        = $_pick($_ms['geo_latitude']  ?? '', '');
$_geoLng        = $_pick($_ms['geo_longitude'] ?? '', '');
$_ogImgAlt      = $_pick($_ms['seo_og_image_alt'] ?? '', $_company);
$_articleTags   = $_pick($_ms['seo_article_tags'] ?? '', '');
?>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="<?= e($_themeCol) ?>">
<meta name="color-scheme" content="light dark">

<!-- ══ TITLE & DESCRIPTION ══ -->
<title><?= e($_title) ?></title>
<meta name="description" content="<?= e($_desc) ?>">
<meta name="keywords" content="<?= e($_kw) ?>">
<meta name="author" content="<?= e($_company) ?>">
<meta name="copyright" content="© <?= date('Y') ?> <?= e($_company) ?>">
<meta name="language" content="en">
<meta name="revisit-after" content="7 days">
<meta name="robots" content="<?= e($_robots) ?>">
<meta name="googlebot" content="<?= e($_robots) ?>">
<meta name="bingbot" content="<?= $_robotsIndexable ? 'index,follow' : 'noindex,nofollow' ?>">

<!-- ══ CANONICAL ══ -->
<link rel="canonical" href="<?= e($_curUrl) ?>">
<link rel="alternate" hreflang="en" href="<?= e($_curUrl) ?>">
<link rel="alternate" hreflang="en-in" href="<?= e($_curUrl) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e($_siteUrl) ?>/">

<!-- ══ OPEN GRAPH ══ -->
<meta property="og:type"                content="<?= e($_ogType) ?>">
<meta property="og:site_name"           content="<?= e($_siteName) ?>">
<meta property="og:title"              content="<?= e($_ogTitle) ?>">
<meta property="og:description"        content="<?= e($_ogDesc) ?>">
<meta property="og:url"                content="<?= e($_curUrl) ?>">
<meta property="og:image"              content="<?= e($_ogImg) ?>">
<meta property="og:image:width"        content="1200">
<meta property="og:image:height"       content="630">
<meta property="og:image:type"         content="image/jpeg">
<meta property="og:image:alt"          content="<?= e($_ogImgAlt) ?>">
<meta property="og:locale"             content="<?= $_locale ?>">
<meta property="og:updated_time"       content="<?= date('c') ?>">
<?php if ($_fbProfile): ?>
<meta property="article:author"        content="<?= e($_fbProfile) ?>">
<meta property="article:publisher"     content="<?= e($_fbProfile) ?>">
<?php endif; ?>

<!-- ══ TWITTER CARD ══ -->
<meta name="twitter:card"              content="<?= e($_twCard) ?>">
<?php if ($_twitterHandle): ?>
<meta name="twitter:site"              content="<?= e($_twitterHandle) ?>">
<meta name="twitter:creator"           content="<?= e($_twitterHandle) ?>">
<?php endif; ?>
<meta name="twitter:title"             content="<?= e($_twTitle) ?>">
<meta name="twitter:description"       content="<?= e($_twDesc) ?>">
<meta name="twitter:image"             content="<?= e($_twImg) ?>">
<meta name="twitter:image:alt"         content="<?= e($_ogImgAlt) ?>">
<?php if ($_twDomain): ?><meta name="twitter:domain"            content="<?= e($_twDomain) ?>"><?php endif; ?>

<!-- ══ SOCIAL / BRAND VERIFICATION ══ -->
<?php if ($_gscId):  ?><meta name="google-site-verification"   content="<?= e($_gscId) ?>"><?php endif; ?>
<?php if ($_bingId): ?><meta name="msvalidate.01"               content="<?= e($_bingId) ?>"><?php endif; ?>
<?php if ($_fbDomain): ?><meta name="facebook-domain-verification" content="<?= e($_fbDomain) ?>"><?php endif; ?>
<?php if ($_yandexId): ?><meta name="yandex-verification"        content="<?= e($_yandexId) ?>"><?php endif; ?>
<?php if ($_pinId):    ?><meta name="p:domain_verify"            content="<?= e($_pinId) ?>"><?php endif; ?>
<?php if ($_nortonId): ?><meta name="norton-safeweb-site-verification" content="<?= e($_nortonId) ?>"><?php endif; ?>

<!-- ══ GEO & LOCAL SEO ══ -->
<?php if ($_geoRegion): ?><meta name="geo.region"               content="<?= e($_geoRegion) ?>"><?php endif; ?>
<?php if ($_geoPlace):  ?><meta name="geo.placename"            content="<?= e($_geoPlace) ?>"><?php endif; ?>
<?php if ($_geoLat && $_geoLng): ?>
<meta name="geo.position"             content="<?= e($_geoLat) ?>;<?= e($_geoLng) ?>">
<meta name="ICBM"                     content="<?= e($_geoLat) ?>, <?= e($_geoLng) ?>">
<?php endif; ?>
<meta name="DC.Language"              content="en">
<meta name="DC.Publisher"             content="<?= e($_company) ?>">

<!-- ══ MOBILE / PWA ══ -->
<meta name="mobile-web-app-capable"   content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title"   content="<?= e($_siteName) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection"         content="telephone=yes">
<meta name="HandheldFriendly"         content="True">
<meta name="MobileOptimized"          content="320">

<!-- ══ PRECONNECT / PERFORMANCE ══ -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com"   crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net"    crossorigin>
<link rel="dns-prefetch" href="https://www.google-analytics.com">
<link rel="dns-prefetch" href="https://www.googletagmanager.com">
<link rel="dns-prefetch" href="https://fonts.googleapis.com">

<!-- ══ FAVICON ══ -->
<?php $fav = $_ms['site_favicon'] ?? ''; $logo = $_ms['site_logo'] ?? ''; ?>
<?php if ($fav): ?>
<link rel="icon" type="image/x-icon"   href="<?= e(getImageUrl($fav)) ?>">
<link rel="apple-touch-icon"           href="<?= e(getImageUrl($fav)) ?>">
<?php elseif ($logo): ?>
<link rel="icon" type="image/png"      href="<?= e(getImageUrl($logo)) ?>">
<link rel="apple-touch-icon"           href="<?= e(getImageUrl($logo)) ?>">
<?php else: ?>
<link rel="icon" href="<?= $_siteUrl ?>/favicon.ico" sizes="any">
<link rel="icon" href="<?= $_siteUrl ?>/icon.svg"    type="image/svg+xml">
<link rel="apple-touch-icon"           href="<?= $_siteUrl ?>/apple-touch-icon.png">
<?php endif; ?>
<meta name="msapplication-TileColor"  content="<?= e($_themeCol) ?>">
<?php /* Reuse the admin-set favicon rather than a fixed file that may not exist. */
$_tile = $fav ?: $logo; if ($_tile): ?>
<meta name="msapplication-TileImage"  content="<?= e(getImageUrl($_tile)) ?>">
<?php endif; ?>

<!-- ══ SITEMAP DISCOVERY ══ -->
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?= $_siteUrl ?>/sitemap.php">

<!-- ══ RSS FEED ══ -->
<?php /* Only advertise the feed if it actually exists — a dead <link rel=alternate>
         is a crawl error. */ if (is_file(ROOT_PATH . '/feed.php')): ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($_siteName) ?> Blog" href="<?= $_siteUrl ?>/feed.php">
<?php endif; ?>

<!-- ══ INDEXNOW (Bing/Yandex instant indexing) ══ -->
<?php if ($_indexNow): ?>
<meta name="indexnow-key" content="<?= e($_indexNow) ?>">
<?php endif; ?>

<!-- ══ E-E-A-T SIGNALS ══ -->
<meta name="article:author"   content="<?= e($_company) ?>">
<meta name="article:modified" content="<?= e($_modDate) ?>">
<meta name="article:published_time" content="<?= e($_pubDate) ?>">
<meta name="article:section" content="Technology">
<?php if ($_articleTags): ?><meta name="article:tag"     content="<?= e($_articleTags) ?>"><?php endif; ?>

<!-- ══ STRUCTURED DATA (JSON-LD) ══ -->
<?php
/* Load page-specific data for schema */
$_schemaPageType = $schemaPageType ?? ($activePage ?? 'home');
$_schemaData     = $schemaData     ?? [];
if (!isset($_schemaData['url']))   $_schemaData['url']   = $_curUrl;
if (!isset($_schemaData['title'])) $_schemaData['title'] = $_title;
if (!isset($_schemaData['desc']))  $_schemaData['desc']  = $_desc;

/* Map $activePage to schema type */
$_typeMap = [
  'home'         => 'home',
  'about'        => 'about',
  'services'     => 'services',
  'service'      => 'service',
  'blog'         => 'blog',
  'blog_post'    => 'blog_post',
  'contact'      => 'contact',
  'faq'          => 'faq',
  'portfolio'    => 'portfolio',
  'careers'      => 'careers',
  'products'     => 'products',
  'product'      => 'product',
  'courses'      => 'courses',
  'course'       => 'course',
  'partners'     => 'partners',
  'clients'      => 'clients',
  'gallery'      => 'gallery',
  'testimonials' => 'testimonials',
  'apps'         => 'apps',
];
$_resolvedType = $_typeMap[$_schemaPageType] ?? $_schemaPageType;

renderPageSchema($_resolvedType, $_schemaData);
?>

<!-- ══════════════════════════════════════════════════════
     TRACKING — every ID below comes from Admin → Settings →
     Analytics. Nothing is hardcoded, and each block renders
     exactly once per page (guarded by AG_ANALYTICS_RENDERED).
══════════════════════════════════════════════════════ -->

<?php
/* Consent gate: analytics only loads once the visitor has accepted.
   The banner writes the ag_consent cookie, which PHP can read on the
   next request; on the click itself the banner injects GA directly. */
$_consent = $_COOKIE['ag_consent'] ?? '';
$_mayTrack = ($_consent === 'accepted');
?>
<script>
/* IDs handed to the consent banner so it can start analytics the moment
   the visitor accepts, without waiting for the next page load. */
window.__agConsent = <?= json_encode($_consent ?: 'unset') ?>;
window.__agAnalytics = <?= json_encode(['ga' => $_gaId, 'gtm' => $_gtmId]) ?>;
</script>
<?php /* ── Google Tag Manager ── */ if ($_mayTrack && $_gtmId): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($_gtmId) ?>');</script>
<?php endif; ?>

<?php /* ── Google Analytics 4 ──
   Skipped when GTM is configured AND the admin has chosen to let GTM
   own GA4, which prevents double page_view hits.                    */
$_gaViaGtm = ($_ms['ga_via_gtm'] ?? '0') === '1';
if ($_mayTrack && $_gaId && !($_gtmId && $_gaViaGtm)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($_gaId) ?>"></script>
<script>
window.dataLayer=window.dataLayer||[];
function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());
gtag('config','<?= e($_gaId) ?>',{
  page_title:<?= json_encode($_title, JSON_UNESCAPED_UNICODE) ?>,
  page_location:<?= json_encode($_curUrl, JSON_UNESCAPED_SLASHES) ?>,
  send_page_view:true,
  anonymize_ip:false,
  allow_google_signals:true,
  allow_ad_personalization_signals:true
});
</script>
<?php endif; ?>

<?php /* ── Meta (Facebook) Pixel ── */ if ($_mayTrack && $_fbPixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($_fbPixel) ?>');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=<?= e($_fbPixel) ?>&ev=PageView&noscript=1"></noscript>
<?php endif; ?>

<?php /* ── Microsoft Clarity ── */ if ($_mayTrack && $_clarity): ?>
<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","<?= e($_clarity) ?>");</script>
<?php endif; ?>

<?php /* ── Hotjar ── */ if ($_mayTrack && $_hotjar): ?>
<script>(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:<?= (int)$_hotjar ?>,hjsv:6};a=o.getElementsByTagName('head')[0];r=o.createElement('script');r.async=1;r.src=t+h._hjSettings.hjid+j;a.appendChild(r);})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');</script>
<?php endif; ?>

<?php /* ── LinkedIn Insight Tag ── */ if ($_mayTrack && $_liInsight): ?>
<script>_linkedin_partner_id="<?= e($_liInsight) ?>";window._linkedin_data_partner_ids=window._linkedin_data_partner_ids||[];window._linkedin_data_partner_ids.push(_linkedin_partner_id);(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}var s=document.getElementsByTagName("script")[0];var b=document.createElement("script");b.type="text/javascript";b.async=true;b.src="https://snap.licdn.com/li.lms-analytics/insight.min.js";s.parentNode.insertBefore(b,s);})(window.lintrk);</script>
<?php endif; ?>