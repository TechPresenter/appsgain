<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/product-components.php';
trackVisitor();

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/products.php'); exit; }

$product = dbFetchRow("SELECT * FROM products WHERE slug = ? AND is_active = 1", [$slug]);
if (!$product) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

// Increment view count
try { dbExecute("UPDATE products SET view_count = view_count + 1 WHERE id = ?", [$product['id']]); } catch (Exception $e) {}

$activePage      = 'products';
$pageTitle       = !empty($product['seo_title']) ? $product['seo_title'] : $product['name'] . ' – Appsgain Technologies';
$pageDescription = !empty($product['seo_description']) ? $product['seo_description'] : truncate($product['short_description'] ?? '', 160);
$pageKeywords    = $product['seo_keywords'] ?? '';
$canonicalUrl    = SITE_URL . '/product/' . $product['slug'];

$keyFeatures = [];
if (!empty($product['key_features'])) {
    $decoded = json_decode($product['key_features'], true);
    $keyFeatures = is_array($decoded) ? $decoded : array_map('trim', explode(',', $product['key_features']));
}

$benefits = [];
if (!empty($product['benefits'])) {
    $decoded = json_decode($product['benefits'], true);
    $benefits = is_array($decoded) ? $decoded : array_map('trim', explode(',', $product['benefits']));
}

/* Same category first, then topped up from elsewhere. Falling back only
   when the category is empty left categories of two showing one lonely card. */
$related = dbFetchAll(
    "SELECT id, name, slug, short_description, category, key_features, logo
       FROM products WHERE is_active=1 AND id != ? AND category = ? ORDER BY sort_order ASC LIMIT 3",
    [$product['id'], $product['category']]
);
if (count($related) < 3) {
    $have = array_column($related, 'id');
    $have[] = (int)$product['id'];
    $ph = implode(',', array_fill(0, count($have), '?'));
    $more = dbFetchAll(
        "SELECT id, name, slug, short_description, category, key_features, logo
           FROM products WHERE is_active=1 AND id NOT IN ($ph) ORDER BY sort_order ASC LIMIT " . (3 - count($related)),
        $have
    );
    $related = array_merge($related, $more);
}

// Modules
$modules = [];
try { $modules = dbFetchAll("SELECT * FROM product_modules WHERE product_id = ? ORDER BY sort_order ASC", [$product['id']]); } catch (Exception $e) {}

// Pricing plans
$pricingPlans = [];
try { $pricingPlans = dbFetchAll("SELECT * FROM product_pricing_plans WHERE product_id = ? ORDER BY sort_order ASC", [$product['id']]); } catch (Exception $e) {}

// Screenshots
$screenshots = [];
try { $screenshots = dbFetchAll("SELECT * FROM product_screenshots WHERE product_id = ? ORDER BY sort_order ASC", [$product['id']]); } catch (Exception $e) {}

// FAQs
$faqs = [];
try { $faqs = dbFetchAll("SELECT * FROM product_faqs WHERE product_id = ? AND is_active = 1 ORDER BY sort_order ASC", [$product['id']]); } catch (Exception $e) {}

$catColors = [
    'Education'=>'#6a00ff','Hospitality'=>'#6a00ff','Real Estate'=>'#10b981',
    'Sales & CRM'=>'#8033ff','Enterprise'=>'#6a00ff','Human Resources'=>'#6a00ff',
    'Healthcare'=>'#6a00ff','Inventory'=>'#5500cc','Custom'=>'#8033ff',
];
$catColor = $catColors[$product['category']] ?? '#6a00ff';

$pageStyles = ['product.css'];
?>
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
  
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main">

<!-- ══ 1 · HERO ══════════════════════════════════════════ -->
<section class="pdx-hero">
  <div class="pr-wrap">
    <nav class="pdx-crumb" aria-label="Breadcrumb">
      <a href="<?= SITE_URL ?>/">Home</a>
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
      <a href="<?= SITE_URL ?>/products.php">Products</a>
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
      <span><?= e($product['name']) ?></span>
    </nav>

    <div class="pdx-head">
      <?php pdLogo($product, 84); ?>
      <div class="pdx-head-copy">
        <?php if (!empty($product['category'])): ?>
        <span class="pdx-cat">
          <i class="fas <?= e(pdCategoryIcon($product['category'])) ?>" aria-hidden="true"></i>
          <?= e($product['category']) ?>
        </span>
        <?php endif; ?>

        <h1 class="pdx-title"><?= e($product['name']) ?></h1>

        <?php if (!empty($product['short_description'])): ?>
        <p class="pdx-lead"><?= e($product['short_description']) ?></p>
        <?php endif; ?>

        <?php
        /* Only counts we actually have; an empty stat row looks worse than none. */
        $stats = [];
        if ($keyFeatures) $stats[] = [count($keyFeatures), 'Features'];
        if ($modules)     $stats[] = [count($modules),     'Modules'];
        if ($benefits)    $stats[] = [count($benefits),    'Outcomes'];
        ?>
        <?php if ($stats): ?>
        <ul class="pdx-stats">
          <?php foreach ($stats as [$n, $label]): ?>
          <li><strong><?= (int)$n ?></strong><span><?= e($label) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="pdx-actions">
          <a href="<?= SITE_URL ?>/contact.php#enquiry" class="pr-btn pr-btn-primary" data-enquiry>
            <i class="fas fa-calendar-check" aria-hidden="true"></i> Request a demo
          </a>
          <?php if (!empty($product['demo_url'])): ?>
          <a href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener" class="pr-btn pr-btn-ghost">
            <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Live demo
          </a>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/contact.php#enquiry" class="pr-btn pr-btn-ghost">
            <i class="fas fa-file-invoice" aria-hidden="true"></i> Get a quote
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ 2 · SECTION NAV ═══════════════════════════════════ -->
<?php
$navLinks = [];
if (!empty($product['detailed_description'])) $navLinks['overview'] = 'Overview';
if ($keyFeatures)  $navLinks['features'] = 'Features';
if ($modules)      $navLinks['modules']  = 'Modules';
if ($benefits)     $navLinks['benefits'] = 'Benefits';
if ($screenshots)  $navLinks['gallery']  = 'Screenshots';
if ($faqs)         $navLinks['faqs']     = 'FAQs';
?>
<?php if (count($navLinks) > 1): ?>
<nav class="pdx-nav" aria-label="Sections on this page">
  <div class="pr-wrap pdx-nav-in">
    <?php foreach ($navLinks as $id => $label): ?>
    <a href="#<?= e($id) ?>" data-pdx-nav="<?= e($id) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<!-- ══ 3 · BODY ══════════════════════════════════════════ -->
<section class="pdx-body">
  <div class="pr-wrap pdx-cols">
    <div>
      <?php if (!empty($product['detailed_description'])): ?>
      <div class="pdx-sec" id="overview">
        <h2 class="pdx-h2">About <?= e($product['name']) ?></h2>
        <div class="pdx-prose">
          <?php foreach (preg_split('/\n\s*\n/', trim((string)$product['detailed_description'])) as $para):
                  $para = trim($para); if ($para === '') continue; ?>
          <p><?= nl2br(e($para)) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($keyFeatures): ?>
      <div class="pdx-sec" id="features">
        <h2 class="pdx-h2">What it does</h2>
        <p class="pdx-sec-sub">The capabilities included as standard.</p>
        <ul class="pdx-feats">
          <?php foreach ($keyFeatures as $f): ?>
          <li class="pdx-feat">
            <span class="pdx-feat-ico" aria-hidden="true"><i class="fas fa-check"></i></span>
            <span><?= e($f) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($modules): ?>
      <div class="pdx-sec" id="modules">
        <h2 class="pdx-h2">Modules</h2>
        <p class="pdx-sec-sub">Take the whole platform, or start with the modules you need.</p>
        <ul class="pdx-mods">
          <?php foreach ($modules as $m): ?>
          <li><i class="fas fa-cube" aria-hidden="true"></i> <?= e($m['module_name'] ?? '') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($benefits): ?>
      <div class="pdx-sec" id="benefits">
        <h2 class="pdx-h2">What changes for you</h2>
        <p class="pdx-sec-sub">The outcomes teams report after moving onto it.</p>
        <ul class="pdx-bens">
          <?php foreach ($benefits as $b): ?>
          <li class="pdx-ben"><?= e($b) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($screenshots): ?>
      <div class="pdx-sec" id="gallery">
        <h2 class="pdx-h2">Screenshots</h2>
        <ul class="pdx-feats" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
          <?php foreach ($screenshots as $sh):
                  $img = pdUpload($sh['image'] ?? $sh['image_path'] ?? ''); if ($img === '') continue; ?>
          <li class="pdx-feat" style="padding:0;overflow:hidden">
            <img src="<?= e($img) ?>" alt="<?= e($sh['caption'] ?? $product['name']) ?>"
                 style="width:100%;display:block" loading="lazy" decoding="async">
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($faqs): ?>
      <div class="pdx-sec" id="faqs">
        <h2 class="pdx-h2">Common questions</h2>
        <ul class="pdx-bens" style="grid-template-columns:1fr">
          <?php foreach ($faqs as $fq): ?>
          <li class="pdx-ben">
            <strong style="display:block;margin-bottom:6px"><?= e($fq['question'] ?? '') ?></strong>
            <span style="color:var(--pr-ink-2)"><?= nl2br(e($fq['answer'] ?? '')) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Sidebar ── -->
    <aside class="pdx-side">
      <div class="pdx-card">
        <h2 class="pdx-card-t"><i class="fas fa-calendar-check" aria-hidden="true"></i> Request a demo</h2>
        <p>See <?= e($product['name']) ?> running against your own workflow. We will walk you through it and answer whatever comes up.</p>
        <div style="display:flex;flex-direction:column;gap:9px">
          <a href="<?= SITE_URL ?>/contact.php#enquiry" class="pr-btn pr-btn-primary pr-btn-wide" data-enquiry>
            <i class="fas fa-paper-plane" aria-hidden="true"></i> Book a demo
          </a>
          <?php if (!empty($phone ?? getSetting('site_phone', ''))): $_ph = $phone ?? getSetting('site_phone', ''); ?>
          <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $_ph)) ?>" class="pr-btn pr-btn-ghost pr-btn-wide">
            <i class="fas fa-phone" aria-hidden="true"></i> <?= e($_ph) ?>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="pdx-card">
        <h2 class="pdx-card-t"><i class="fas fa-circle-info" aria-hidden="true"></i> Product details</h2>
        <ul class="pdx-facts">
          <?php if (!empty($product['category'])): ?>
          <li><span class="k">Category</span><span class="v"><?= e($product['category']) ?></span></li>
          <?php endif; ?>
          <?php if ($keyFeatures): ?>
          <li><span class="k">Features</span><span class="v"><?= count($keyFeatures) ?></span></li>
          <?php endif; ?>
          <?php if ($modules): ?>
          <li><span class="k">Modules</span><span class="v"><?= count($modules) ?></span></li>
          <?php endif; ?>
          <li><span class="k">Deployment</span><span class="v">Cloud or on-premise</span></li>
          <li><span class="k">Customisation</span><span class="v">Available</span></li>
          <li><span class="k">Support</span><span class="v">Included after launch</span></li>
          <?php if (!empty($product['demo_url'])): ?>
          <li><span class="k">Live demo</span><span class="v"><a href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener">Open</a></span></li>
          <?php endif; ?>
          <?php if (!empty($product['brochure_file'])): ?>
          <li><span class="k">Brochure</span><span class="v"><a href="<?= e(pdUpload($product['brochure_file'])) ?>" target="_blank" rel="noopener">Download</a></span></li>
          <?php endif; ?>
        </ul>
      </div>

      <?php if (!empty($product['video_url'])): ?>
      <div class="pdx-card">
        <h2 class="pdx-card-t"><i class="fas fa-circle-play" aria-hidden="true"></i> Product walkthrough</h2>
        <a href="<?= e($product['video_url']) ?>" target="_blank" rel="noopener" class="pr-btn pr-btn-ghost pr-btn-wide">
          <i class="fas fa-play" aria-hidden="true"></i> Watch the video
        </a>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<!-- ══ 4 · RELATED ═══════════════════════════════════════ -->
<?php if ($related): ?>
<section class="pdx-rel">
  <div class="pr-wrap">
    <div class="pdx-rel-head">
      <h2 class="pdx-h2">Related products</h2>
      <p class="pdx-sec-sub" style="margin-bottom:0">Other platforms we have already built and refined.</p>
    </div>
    <div class="pdx-rel-grid<?= count($related) < 3 ? ' pdx-rel-grid--few' : '' ?>">
      <?php foreach ($related as $r):
        $rFeats = array_slice(pdList($r['key_features'] ?? ''), 0, 3); ?>
      <article class="pr-card">
        <div class="pr-card-top">
          <?php pdLogo($r, 46); ?>
          <?php if (!empty($r['category'])): ?>
          <span class="pr-card-cat"><?= e($r['category']) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= e($r['name']) ?></h3>
        <p class="pr-card-desc"><?= e(truncate((string)($r['short_description'] ?? ''), 120)) ?></p>
        <div class="pr-card-foot">
          <span class="pr-card-link">View details <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
        </div>
        <a class="pr-card-stretch" href="<?= SITE_URL ?>/product/<?= e($r['slug']) ?>">
          <span class="sr-only">View <?= e($r['name']) ?></span>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 5 · CTA ═══════════════════════════════════════════ -->
<section class="pdx-cta">
  <div class="pr-wrap">
    <h2>Ready to see <?= e($product['name']) ?> in action?</h2>
    <p>Book a walkthrough with the team that built it. We will show you the parts that matter for your operation and tell you honestly what it would take to roll out.</p>
    <div class="pdx-actions" style="justify-content:center">
      <a href="<?= SITE_URL ?>/contact.php#enquiry" class="pr-btn pr-btn-primary" data-enquiry>
        <i class="fas fa-calendar-check" aria-hidden="true"></i> Book a free demo
      </a>
      <a href="<?= SITE_URL ?>/products.php" class="pr-btn pr-btn-ghost">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> All products
      </a>
    </div>
  </div>
</section>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script>
/* Highlight the section nav entry for whatever is on screen. */
(function () {
  var links = document.querySelectorAll('[data-pdx-nav]');
  if (!links.length || !('IntersectionObserver' in window)) return;
  var byId = {};
  links.forEach(function (a) { byId[a.getAttribute('data-pdx-nav')] = a; });

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      links.forEach(function (a) { a.classList.remove('is-on'); });
      var a = byId[en.target.id];
      if (a) a.classList.add('is-on');
    });
  }, { rootMargin: '-70px 0px -65% 0px', threshold: 0 });

  Object.keys(byId).forEach(function (id) {
    var sec = document.getElementById(id);
    if (sec) io.observe(sec);
  });
})();
</script>
</body>
</html>
