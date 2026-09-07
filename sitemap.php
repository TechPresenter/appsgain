<?php
/**
 * Appsgain Technologies — Complete XML Sitemap v2.0
 * Includes: pages, services, blogs, portfolio, products, apps, partners, clients, courses, faqs
 */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . SITE_URL . '/sitemap.xsl"?>';
echo "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

<?php
/* ── Helper: emit URL entry ── */
function sitemapUrl(string $loc, string $lastmod = '', string $changefreq = 'weekly', float $priority = 0.7, array $images = []): void {
  echo "  <url>\n";
  echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
  if ($lastmod) echo "    <lastmod>" . $lastmod . "</lastmod>\n";
  echo "    <changefreq>" . $changefreq . "</changefreq>\n";
  echo "    <priority>" . number_format($priority, 1) . "</priority>\n";
  foreach ($images as $img) {
    echo "    <image:image>\n";
    echo "      <image:loc>" . htmlspecialchars($img['loc']) . "</image:loc>\n";
    if (!empty($img['title']))   echo "      <image:title>" . htmlspecialchars($img['title']) . "</image:title>\n";
    if (!empty($img['caption'])) echo "      <image:caption>" . htmlspecialchars($img['caption']) . "</image:caption>\n";
    echo "    </image:image>\n";
  }
  echo "  </url>\n";
}

$today = date('Y-m-d');

/* ══ 1. STATIC CORE PAGES ══ */
$staticPages = [
  [SITE_URL.'/',                      $today, 'daily',   1.0, 'Homepage — Appsgain Technologies'],
  /* /about.php 301s to /about-us — a sitemap should list the destination,
     not the redirect. */
  [SITE_URL.'/about-us',              $today, 'monthly', 0.9, 'About Appsgain Technologies'],
  [SITE_URL.'/our-founder.php',       $today, 'monthly', 0.7, 'Our Founder — Prashant Kumar'],
  [SITE_URL.'/services.php',          $today, 'weekly',  0.9, 'IT Services — Appsgain Technologies'],
  [SITE_URL.'/portfolio.php',         $today, 'weekly',  0.8, 'Portfolio — Appsgain Technologies'],
  [SITE_URL.'/blog.php',              $today, 'daily',   0.8, 'Blog — Appsgain Technologies'],
  [SITE_URL.'/contact.php',           $today, 'monthly', 0.8, 'Contact Appsgain Technologies'],
  [SITE_URL.'/careers.php',           $today, 'weekly',  0.8, 'Careers — Appsgain Technologies'],
  [SITE_URL.'/faq.php',               $today, 'monthly', 0.7, 'FAQs — Appsgain Technologies'],
  [SITE_URL.'/gallery.php',           $today, 'weekly',  0.6, 'Gallery — Appsgain Technologies'],
  [SITE_URL.'/products.php',          $today, 'weekly',  0.8, 'Products — Appsgain Technologies'],
  [SITE_URL.'/apps.php',              $today, 'weekly',  0.7, 'Mobile Apps — Appsgain Technologies'],
  [SITE_URL.'/partners.php',          $today, 'monthly', 0.6, 'Partners — Appsgain Technologies'],
  [SITE_URL.'/privacy-policy.php',    $today, 'yearly',  0.3, 'Privacy Policy'],
  [SITE_URL.'/app-privacy-policy.php',$today, 'yearly',  0.3, 'App Privacy Policy'],
  [SITE_URL.'/terms-of-service.php',  $today, 'yearly',  0.3, 'Terms & Conditions'],
  [SITE_URL.'/refund-policy.php',     $today, 'yearly',  0.3, 'Refund Policy'],
  [SITE_URL.'/cookie-policy.php',     $today, 'yearly',  0.3, 'Cookie Policy'],
  [SITE_URL.'/disclaimer.php',        $today, 'yearly',  0.3, 'Disclaimer'],
  [SITE_URL.'/shipping-policy.php',   $today, 'yearly',  0.3, 'Shipping & Delivery Policy'],
  [SITE_URL.'/sitemap-html.php',      $today, 'monthly', 0.4, 'HTML Sitemap — Appsgain Technologies'],
];

foreach ($staticPages as [$url, $mod, $freq, $pri, $title]) {
  sitemapUrl($url, $mod, $freq, $pri);
}

/* ══ 2. LEGAL PAGES ══ */
$legalPages = ['privacy-policy','terms-of-service','cookie-policy','refund-policy','shipping-policy','disclaimer'];
foreach ($legalPages as $pg) {
  sitemapUrl(SITE_URL.'/'.$pg.'.php', $today, 'yearly', 0.3);
}

/* ══ 3. SERVICES ══ */
try {
  $services = dbFetchAll("SELECT slug, name, updated_at, image FROM services WHERE is_active=1 ORDER BY sort_order ASC");
  foreach ($services as $s) {
    $mod  = !empty($s['updated_at']) ? date('Y-m-d', strtotime($s['updated_at'])) : $today;
    $imgs = [];
    if (!empty($s['image'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$s['image'], 'title'=>$s['name'].' — Appsgain Technologies'];
    sitemapUrl(SITE_URL.'/service/'.$s['slug'], $mod, 'monthly', 0.8, $imgs);
  }
} catch(Exception $e){}

/* ══ 4. BLOG POSTS ══ */
try {
  $blogs = dbFetchAll("SELECT slug, title, featured_image, published_at, updated_at FROM blogs WHERE status='published' ORDER BY published_at DESC");
  foreach ($blogs as $b) {
    $mod  = date('Y-m-d', strtotime($b['updated_at'] ?? $b['published_at'] ?? $today));
    $imgs = [];
    if (!empty($b['featured_image'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$b['featured_image'],'title'=>$b['title'],'caption'=>$b['title'].' — Appsgain Technologies Blog'];
    sitemapUrl(SITE_URL.'/blog/'.$b['slug'], $mod, 'monthly', 0.7, $imgs);
  }
} catch(Exception $e){}

/* ══ 5. PORTFOLIO / PROJECTS ══ */
try {
  $projects = dbFetchAll("SELECT slug, title, featured_image, updated_at FROM projects WHERE is_active=1 ORDER BY sort_order ASC");
  foreach ($projects as $p) {
    $mod  = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
    $imgs = [];
    if (!empty($p['featured_image'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$p['featured_image'],'title'=>$p['title'],'caption'=>$p['title'].' — Appsgain Portfolio'];
    sitemapUrl(SITE_URL.'/portfolio/'.$p['slug'], $mod, 'monthly', 0.6, $imgs);
  }
} catch(Exception $e){}

/* ══ 6. PRODUCTS ══ */
try {
  $products = dbFetchAll("SELECT slug, name, logo, banner_image, updated_at FROM products WHERE is_active=1 ORDER BY sort_order ASC");
  foreach ($products as $p) {
    $mod  = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
    $imgs = [];
    if (!empty($p['banner_image'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$p['banner_image'],'title'=>$p['name']];
    elseif (!empty($p['logo']))     $imgs[] = ['loc'=>UPLOADS_URL.'/'.$p['logo'],'title'=>$p['name'].' Logo'];
    sitemapUrl(SITE_URL.'/product/'.$p['slug'], $mod, 'monthly', 0.7, $imgs);
  }
} catch(Exception $e){}

/* ══ 7. APPS ══ */
try {
  $apps = dbFetchAll("SELECT app_slug, app_name, app_icon, updated_at FROM apps WHERE is_active=1 ORDER BY sort_order ASC");
  foreach ($apps as $a) {
    $mod  = !empty($a['updated_at']) ? date('Y-m-d', strtotime($a['updated_at'])) : $today;
    $imgs = [];
    if (!empty($a['app_icon'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$a['app_icon'],'title'=>$a['app_name']];
    sitemapUrl(SITE_URL.'/app/'.$a['app_slug'], $mod, 'monthly', 0.6, $imgs);
  }
} catch(Exception $e){}

/* ══ 8. COURSES ══ */
try {
  $courses = dbFetchAll("SELECT slug, title, image, updated_at FROM courses WHERE is_active=1 ORDER BY sort_order ASC");
  foreach ($courses as $c) {
    $mod  = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : $today;
    $imgs = [];
    if (!empty($c['image'])) $imgs[] = ['loc'=>UPLOADS_URL.'/'.$c['image'],'title'=>$c['title'].' — IT Course'];
    sitemapUrl(SITE_URL.'/courses/'.$c['slug'], $mod, 'monthly', 0.6, $imgs);
  }
} catch(Exception $e){}

/* ══ 9. PARTNERS ══ */
try {
  $partners = dbFetchAll("SELECT slug, name, logo, updated_at FROM partners WHERE is_active=1");
  foreach ($partners as $p) {
    $mod  = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
    sitemapUrl(SITE_URL.'/partners.php', $mod, 'monthly', 0.5);
    break; // main page only
  }
} catch(Exception $e){}

/* ══ 10. MEDIA GALLERY IMAGES ══ */
try {
  $media = dbFetchAll("SELECT file_path, alt_text, caption, created_at FROM media WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 50");
  if (!empty($media)) {
    $galleryImgs = [];
    foreach ($media as $m) {
      $galleryImgs[] = ['loc'=>UPLOADS_URL.'/'.$m['file_path'],'title'=>$m['alt_text']??'Appsgain Gallery','caption'=>$m['caption']??''];
    }
    sitemapUrl(SITE_URL.'/gallery.php', $today, 'weekly', 0.6, $galleryImgs);
  }
} catch(Exception $e){}

?>
</urlset>
