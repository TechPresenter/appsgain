<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/service-components.php';
require_once __DIR__ . '/includes/service-sections.php';
trackVisitor();

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
if (!$slug) { header('Location: ' . SITE_URL . '/services.php'); exit; }

$service = dbFetchRow("SELECT * FROM services WHERE slug = ? AND is_active = 1", [$slug]);
if (!$service) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

/* ── Page meta ── */
$activePage      = 'services';
$schemaPageType  = 'service';
$seoIsDetail     = true;                 /* record SEO wins over the page key */
$_svcName        = (string)($service['name'] ?? '');
$_svcGroup       = (string)($service['service_group'] ?: ($service['category'] ?? 'Services'));
$_svcSub         = trim((string)($service['subtitle'] ?? ''));
$pageTitle       = !empty($service['meta_title']) ? $service['meta_title'] : $_svcName . ' – Appsgain Technologies';
$pageDescription = !empty($service['meta_description'])
                 ? $service['meta_description']
                 : truncate(strip_tags((string)($service['short_description'] ?: $service['description'] ?? '')), 160);
$canonicalUrl    = trim((string)($service['canonical_url'] ?? '')) !== ''
                 ? svcUrl((string)$service['canonical_url'])
                 : SITE_URL . '/service/' . $service['slug'];
if (trim((string)($service['og_image'] ?? '')) !== '') {
    $ogImage = str_starts_with($service['og_image'], 'http')
             ? $service['og_image']
             : UPLOADS_URL . '/' . ltrim((string)$service['og_image'], '/');
}
$schemaData      = ['service' => $service];

/* ── Content ── */
$features = [];
if (!empty($service['features'])) {
    $decoded = json_decode((string)$service['features'], true);
    if (is_array($decoded)) $features = array_values(array_filter(array_map('trim', $decoded), 'strlen'));
}
$descHtml = trim((string)($service['description'] ?? ''));
$imgBase  = svcImageBase($service);
[$c1, $c2, $fallbackIcon] = svcAccent($service['slug']);
$icon = $service['icon'] ?: $fallbackIcon;

/* Siblings in the same category, then anything else, to fill three slots */
$related = dbFetchAll(
    "SELECT *, COALESCE(icon, icon_class, 'fa-cog') AS icon,
            COALESCE(service_group, category, 'Services') AS service_group
     FROM services
     WHERE is_active = 1 AND id <> ?
     ORDER BY (service_group = ?) DESC, sort_order ASC
     LIMIT 3",
    [$service['id'], $_svcGroup]
);

$_settings = getAllSettings();
$_phone = $_settings['site_phone'] ?? '+91-9955446477';
$_ph    = preg_replace('/[^+0-9]/', '', $_phone);

/* Page sections — admin-managed, with global defaults per section key */
$sections = svcSectionsFor((int)$service['id']);

/* Hero CTA is part of the service record so it can differ per service */
$heroBtnText = trim((string)($service['hero_btn_text'] ?? '')) ?: 'Start This Project';
$heroBtnUrl  = trim((string)($service['hero_btn_url']  ?? '')) ?: '/contact.php#enquiry';
?>
<?php $pageStyles = ['services.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require __DIR__ . '/includes/meta.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main" style="--c1:<?= e($c1) ?>;--c2:<?= e($c2) ?>">

<!-- ══ HERO ══════════════════════════════════════════════ -->
<section class="sx-hero sd-hero">
  <span class="sx-hero-glow" aria-hidden="true"></span>
  <div class="sx-wrap">
    <nav class="sd-crumb" aria-label="Breadcrumb">
      <a href="<?= SITE_URL ?>/">Home</a>
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
      <a href="<?= SITE_URL ?>/services.php">Services</a>
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
      <span aria-current="page"><?= e($_svcName) ?></span>
    </nav>

    <div class="sx-hero-in sd-hero-in">
      <div class="sx-hero-copy">
        <span class="sx-eyebrow"><?= e($_svcGroup) ?></span>
        <h1 class="sx-h1"><?= e($_svcName) ?></h1>
        <?php if ($_svcSub): ?>
        <p class="sd-sub"><?= e($_svcSub) ?></p>
        <?php endif; ?>
        <?php if ($descHtml): ?>
        <div class="sx-lead sd-desc"><?= $descHtml /* trusted admin HTML */ ?></div>
        <?php endif; ?>
        <div class="sx-actions">
          <a href="<?= e(svcUrl($heroBtnUrl)) ?>" class="sx-btn sx-btn-primary"><?= e($heroBtnText) ?></a>
          <a href="tel:<?= e($_ph) ?>" class="sx-btn sx-btn-ghost">
            <i class="fas fa-phone" aria-hidden="true"></i> <?= e($_phone) ?>
          </a>
        </div>
      </div>

      <div class="sd-hero-visual">
        <?php $imgPair = svcImagePair($service); ?>
        <?php if ($imgPair): [$webpSrc, $imgSrc] = $imgPair; ?>
        <picture>
          <?php /* Only offer a webp when one is actually on disk. */ ?>
          <?php if ($webpSrc !== null): ?>
          <source srcset="<?= UPLOADS_URL ?>/<?= e($webpSrc) ?>" type="image/webp">
          <?php endif; ?>
          <img src="<?= UPLOADS_URL ?>/<?= e($imgSrc) ?>"
               alt="<?= e($_svcName) ?>" width="1200" height="750"
               loading="eager" decoding="async">
        </picture>
        <?php else: ?>
        <span class="sd-hero-icon"><i class="fas <?= e($icon) ?>" aria-hidden="true"></i></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php
/* Sections come from `service_sections`. Headings, copy, items,
   images, buttons and order are all editable in the admin. */
$svcCtx = ['features' => $features, 'related' => $related, 'icon' => $icon];
foreach ($sections as $_sec) { svcRenderSection($_sec, $service, $svcCtx); }
?>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
(function () {
  if (!('IntersectionObserver' in window) ||
      window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('sx-in'); io.unobserve(en.target); }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.sd-feat, .sx-proc-step, .sx-card')
    .forEach(function (el) { el.classList.add('sx-anim'); io.observe(el); });
})();
</script>
</body>
</html>
