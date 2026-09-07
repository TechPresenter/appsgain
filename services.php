<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/service-components.php';
require_once __DIR__ . '/includes/services-page-content.php';
trackVisitor();

$activePage      = 'services';
$schemaPageType  = 'services';
$pageTitle       = getSetting('meta_title_services', 'IT Services – Appsgain Technologies');
$pageDescription = getSetting('meta_desc_services',  'Explore Appsgain\'s full range of services: custom software, mobile apps, ERP, CRM, SaaS platforms, AI products, DevOps and cloud engineering.');
$canonicalUrl    = SITE_URL . '/services.php';

/* ── Data: every service comes from the DB, nothing hardcoded ── */
$services = dbFetchAll(
    "SELECT *,
            COALESCE(icon, icon_class, 'fa-cog')         AS icon,
            COALESCE(service_group, category, 'Services') AS service_group
     FROM services WHERE is_active = 1 ORDER BY sort_order ASC"
);

$groups = [];
foreach ($services as $svc) {
    $groups[$svc['service_group'] ?: 'Services'][] = $svc;
}
$groupNames = array_keys($groups);

/* Section copy: Admin → Website Core → Services Page */
$groupCopy = [];
foreach (spList('sp_group_copy') as $g) {
    $name = trim((string)($g['group'] ?? ''));
    if ($name === '') continue;
    $groupCopy[$name] = [
        'lead'   => (string)($g['lead']   ?? $name),
        'accent' => (string)($g['accent'] ?? ''),
        'sub'    => (string)($g['sub']    ?? ''),
    ];
}

$svcCount   = count($services);
$metricYears   = getSetting('metric_years', '8');
$metricProject = getSetting('metric_projects', '500');
$metricClients = getSetting('metric_clients', '300');

$processSteps = [];
foreach (spList('sp_process_steps') as $r) {
    $processSteps[] = [(string)($r['title'] ?? ''), (string)($r['icon'] ?? 'fa-circle'), (string)($r['text'] ?? '')];
}

$techStack = [];
foreach (spList('sp_tech_stack') as $g) {
    $name = trim((string)($g['group'] ?? ''));
    if ($name === '') continue;
    $rows = [];
    foreach ((array)($g['items'] ?? []) as $it) {
        $rows[] = [(string)($it['icon'] ?? 'fas fa-cube'), (string)($it['name'] ?? '')];
    }
    $techStack[$name] = $rows;
}

$whyPoints = [];
foreach (spList('sp_why_points') as $r) {
    $whyPoints[] = [(string)($r['title'] ?? ''), (string)($r['text'] ?? '')];
}

/* Maps a visitor intent to a real service slug. */
$finder = [];
foreach (spList('sp_finder') as $r) {
    $finder[] = [(string)($r['label'] ?? ''), (string)($r['slug'] ?? '')];
}
$svcBySlug = [];
foreach ($services as $s) $svcBySlug[$s['slug']] = $s;
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

<main id="main">

<!-- ══ 1 · HERO ══════════════════════════════════════════ -->
<section class="sx-hero">
  <span class="sx-hero-glow" aria-hidden="true"></span>
  <div class="sx-wrap sx-hero-in">
    <div class="sx-hero-copy">
      <?php if (sp('sp_hero_eyebrow')): ?><span class="sx-eyebrow"><?= e(sp('sp_hero_eyebrow')) ?></span><?php endif; ?>
      <h1 class="sx-h1"><?= e(sp('sp_hero_title')) ?><br><?= e(sp('sp_hero_title2')) ?> <span class="sx-grad"><?= e(sp('sp_hero_accent')) ?></span></h1>
      <p class="sx-lead">
        <?php /* Blank hero lead falls back to the page meta description. */ ?>
        <?= e(sp('sp_hero_lead') ?: truncate($pageDescription, 180)) ?>
      </p>
      <div class="sx-actions">
        <?php foreach ([['sp_hero_btn1_text', 'sp_hero_btn1_url', 'sx-btn-primary'],
                        ['sp_hero_btn2_text', 'sp_hero_btn2_url', 'sx-btn-ghost']] as [$_bt, $_bu, $_bc]):
                $_t = sp($_bt); if ($_t === '') continue;
                $_u = sp($_bu); if (!preg_match('~^(https?:|mailto:|tel:|\#)~i', $_u)) $_u = SITE_URL . '/' . ltrim($_u, '/'); ?>
        <a href="<?= e($_u) ?>" class="sx-btn <?= $_bc ?>"><?= e($_t) ?></a>
        <?php endforeach; ?>
      </div>
      <ul class="sx-hero-facts">
        <li><strong><?= e($svcCount) ?></strong><span>Services</span></li>
        <li><strong><?= e($metricProject) ?>+</strong><span>Projects</span></li>
        <li><strong><?= e($metricYears) ?>+</strong><span>Years</span></li>
      </ul>
    </div>

    <?php $_spHeroImg = spImage('sp_hero_image'); ?>
    <?php if ($_spHeroImg): ?>
    <div class="sx-hero-visual sx-hero-visual--img">
      <img src="<?= e($_spHeroImg) ?>" alt="<?= e(sp('sp_hero_accent')) ?>" loading="eager" decoding="async" fetchpriority="high">
    </div>
    <?php else: ?>
    <div class="sx-hero-visual" aria-hidden="true">
      <div class="sx-hv-main">
        <div class="sx-hv-bar"><i></i><i></i><i></i></div>
        <div class="sx-hv-body">
          <div class="sx-hv-side"><span></span><span></span><span></span><span></span></div>
          <div class="sx-hv-content">
            <div class="sx-hv-kpis"><span></span><span></span><span></span></div>
            <svg class="sx-hv-chart" viewBox="0 0 260 90" preserveAspectRatio="none">
              <defs>
                <linearGradient id="sxLine" x1="0" y1="0" x2="1" y2="0">
                  <stop offset="0%" stop-color="#FF8A00"/><stop offset="50%" stop-color="#F50072"/>
                  <stop offset="100%" stop-color="#6A00FF"/>
                </linearGradient>
                <linearGradient id="sxFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#6A00FF" stop-opacity=".18"/>
                  <stop offset="100%" stop-color="#6A00FF" stop-opacity="0"/>
                </linearGradient>
              </defs>
              <path d="M0 72 L38 60 L76 66 L114 40 L152 48 L190 24 L228 30 L260 10 L260 90 L0 90 Z" fill="url(#sxFill)"/>
              <path d="M0 72 L38 60 L76 66 L114 40 L152 48 L190 24 L228 30 L260 10"
                    fill="none" stroke="url(#sxLine)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="sx-hv-rows"><span></span><span></span><span></span></div>
          </div>
        </div>
      </div>

      <div class="sx-hv-phone">
        <span class="sx-hv-notch"></span>
        <span class="sx-hv-tile"></span>
        <span class="sx-hv-line w70"></span>
        <span class="sx-hv-line w45"></span>
        <span class="sx-hv-cta"></span>
      </div>

      <div class="sx-hv-chip sx-hv-chip1"><i class="fas fa-cloud"></i></div>
      <div class="sx-hv-chip sx-hv-chip2"><i class="fas fa-brain"></i></div>
      <div class="sx-hv-chip sx-hv-chip3"><i class="fas fa-shield-halved"></i></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ 2 · CATEGORY NAV ══════════════════════════════════ -->
<nav class="sx-catnav" aria-label="Service categories">
  <div class="sx-wrap sx-catnav-in">
    <button class="sx-pill is-on" data-filter="all">All Services</button>
    <?php foreach ($groupNames as $g): ?>
    <button class="sx-pill" data-filter="<?= e($g) ?>"><?= e($g) ?></button>
    <?php endforeach; ?>
  </div>
</nav>

<!-- ══ 3 · OVERVIEW ══════════════════════════════════════ -->
<section class="sx-section sx-bg-white" id="overview">
  <div class="sx-wrap">
    <?php svcHeading(sp('sp_overview_eyebrow'), sp('sp_overview_title'), sp('sp_overview_accent'), sp('sp_overview_sub')); ?>
    <?php svcGrid($services, true); ?>
  </div>
</section>

<!-- ══ 4 · CATEGORY SECTIONS ═════════════════════════════ -->
<?php $alt = false; foreach ($groups as $gName => $gItems):
  $copy = $groupCopy[$gName] ?? ['lead' => $gName, 'accent' => 'Services', 'sub' => ''];
  $alt = !$alt; ?>
<section class="sx-section <?= $alt ? 'sx-bg-soft' : 'sx-bg-white' ?>" id="<?= e(strtolower(str_replace(' ', '-', $gName))) ?>">
  <div class="sx-wrap sx-split">
    <div class="sx-split-copy">
      <?php svcHeading('', $copy['lead'], $copy['accent'], $copy['sub'], 'left'); ?>
      <ul class="sx-split-list">
        <?php foreach ($gItems as $gs): [$c1, $c2, $fi] = svcAccent($gs['slug']); ?>
        <li>
          <a href="<?= SITE_URL ?>/service/<?= e($gs['slug']) ?>">
            <span class="sx-split-ico" style="--c1:<?= e($c1) ?>;--c2:<?= e($c2) ?>">
              <i class="fas <?= e($gs['icon'] ?: $fi) ?>" aria-hidden="true"></i>
            </span>
            <span class="sx-split-name"><?= e($gs['name']) ?></span>
            <i class="fas fa-arrow-right sx-split-arrow" aria-hidden="true"></i>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <a href="<?= SITE_URL ?>/contact.php#enquiry" class="sx-btn sx-btn-primary">Discuss a <?= e($gName) ?> project</a>
    </div>

    <div class="sx-split-cards">
      <?php foreach ($gItems as $gs): svcCard($gs, 'standard'); endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<!-- ══ 5 · WHY CHOOSE APPSGAIN ═══════════════════════════ -->
<section class="sx-section sx-bg-lav" id="why">
  <div class="sx-wrap">
    <?php svcHeading(sp('sp_why_eyebrow'), sp('sp_why_title'), sp('sp_why_accent'), sp('sp_why_sub')); ?>
    <ol class="sx-why">
      <?php foreach ($whyPoints as $i => [$t, $d]): ?>
      <li class="sx-why-item">
        <span class="sx-why-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
        <div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ══ 6 · TECHNOLOGY STACK ══════════════════════════════ -->
<section class="sx-section sx-bg-white" id="stack">
  <div class="sx-wrap">
    <?php svcHeading(sp('sp_stack_eyebrow'), sp('sp_stack_title'), sp('sp_stack_accent'), sp('sp_stack_sub')); ?>
    <div class="sx-stack">
      <?php foreach ($techStack as $cat => $items): ?>
      <div class="sx-stack-group">
        <span class="sx-stack-label"><?= e($cat) ?></span>
        <div class="sx-stack-items">
          <?php foreach ($items as [$ico, $label]): ?>
          <span class="sx-chip"><i class="<?= e($ico) ?>" aria-hidden="true"></i><?= e($label) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 7 · PROCESS ═══════════════════════════════════════ -->
<section class="sx-section sx-bg-soft" id="process">
  <div class="sx-wrap">
    <?php svcHeading(sp('sp_process_eyebrow'), sp('sp_process_title'), sp('sp_process_accent'), sp('sp_process_sub')); ?>
    <ol class="sx-proc">
      <?php foreach ($processSteps as $i => [$t, $ico, $d]): ?>
      <li class="sx-proc-step">
        <span class="sx-proc-dot"><i class="fas <?= e($ico) ?>" aria-hidden="true"></i></span>
        <span class="sx-proc-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
        <h3><?= e($t) ?></h3>
        <p><?= e($d) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ══ 8 · FINDER ════════════════════════════════════════ -->
<section class="sx-section sx-bg-white" id="finder">
  <div class="sx-wrap">
    <?php svcHeading(sp('sp_finder_eyebrow'), sp('sp_finder_title'), sp('sp_finder_accent'), sp('sp_finder_sub')); ?>
    <div class="sx-finder" id="sxFinder">
      <?php foreach ($finder as $i => [$label, $slug]):
        if (!isset($svcBySlug[$slug])) continue; ?>
      <button class="sx-pill sx-finder-pill<?= $i === 0 ? ' is-on' : '' ?>" data-slug="<?= e($slug) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="sx-finder-out" id="sxFinderOut">
      <?php foreach ($finder as $i => [$label, $slug]):
        if (!isset($svcBySlug[$slug])) continue;
        $fs = $svcBySlug[$slug]; [$c1, $c2, $fi] = svcAccent($slug); ?>
      <div class="sx-finder-panel<?= $i === 0 ? ' is-on' : '' ?>" data-slug="<?= e($slug) ?>"
           style="--c1:<?= e($c1) ?>;--c2:<?= e($c2) ?>">
        <span class="sx-finder-ico"><i class="fas <?= e($fs['icon'] ?: $fi) ?>" aria-hidden="true"></i></span>
        <div class="sx-finder-body">
          <span class="sx-finder-tag"><?= e($fs['service_group']) ?></span>
          <h3><?= e($fs['name']) ?></h3>
          <p><?= e(truncate(strip_tags((string)($fs['short_description'] ?: $fs['description'] ?? '')), 190)) ?></p>
          <a href="<?= SITE_URL ?>/service/<?= e($fs['slug']) ?>" class="sx-btn sx-btn-primary">
            Explore <?= e($fs['name']) ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 9 · CTA ═══════════════════════════════════════════ -->
<section class="sx-cta">
  <div class="sx-wrap sx-cta-in">
    <div>
      <h2 class="sx-h2"><?= e(sp('sp_cta_title')) ?> <span class="sx-grad"><?= e(sp('sp_cta_accent')) ?></span></h2>
      <p class="sx-sub"><?= e(sp('sp_cta_sub')) ?></p>
      <div class="sx-actions">
        <?php foreach ([['sp_cta_btn1_text', 'sp_cta_btn1_url', 'sx-btn-primary'],
                        ['sp_cta_btn2_text', 'sp_cta_btn2_url', 'sx-btn-ghost']] as [$_bt, $_bu, $_bc]):
                $_t = sp($_bt); if ($_t === '') continue;
                $_u = sp($_bu); if (!preg_match('~^(https?:|mailto:|tel:|\#)~i', $_u)) $_u = SITE_URL . '/' . ltrim($_u, '/'); ?>
        <a href="<?= e($_u) ?>" class="sx-btn <?= $_bc ?>"><?= e($_t) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $ctaImg = sectionImage('secimg_services_cta'); ?>
    <?php if ($ctaImg !== ''): ?>
    <div class="sx-cta-visual sx-cta-visual--img">
      <img src="<?= e($ctaImg) ?>" alt="" loading="lazy" decoding="async">
    </div>
    <?php else: ?>
    <div class="sx-cta-visual" aria-hidden="true">
      <span class="sx-cta-ring"></span>
      <span class="sx-cta-ring sx-cta-ring2"></span>
      <i class="fas fa-comments-question-check"></i>
    </div>
    <?php endif; ?>
  </div>
</section>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
(function () {
  /* ── Category filter: hides cards that do not match ── */
  var pills = document.querySelectorAll('.sx-catnav .sx-pill');
  var cards = document.querySelectorAll('#overview .sx-card');
  pills.forEach(function (p) {
    p.addEventListener('click', function () {
      pills.forEach(function (o) { o.classList.remove('is-on'); });
      p.classList.add('is-on');
      var f = p.dataset.filter;
      cards.forEach(function (c) {
        c.style.display = (f === 'all' || c.dataset.group === f) ? '' : 'none';
      });
    });
  });

  /* ── Finder: swaps to the matching real service ── */
  var fPills  = document.querySelectorAll('.sx-finder-pill');
  var fPanels = document.querySelectorAll('.sx-finder-panel');
  fPills.forEach(function (p) {
    p.addEventListener('click', function () {
      fPills.forEach(function (o) { o.classList.remove('is-on'); });
      p.classList.add('is-on');
      fPanels.forEach(function (panel) {
        panel.classList.toggle('is-on', panel.dataset.slug === p.dataset.slug);
      });
    });
  });

  /* ── Reveal on scroll ── */
  if ('IntersectionObserver' in window &&
      !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('sx-in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.sx-card, .sx-why-item, .sx-proc-step, .sx-stack-group')
      .forEach(function (el) { el.classList.add('sx-anim'); io.observe(el); });
  }
})();
</script>
</body>
</html>
