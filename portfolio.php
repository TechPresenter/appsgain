<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'portfolio';
$pageTitle       = getSetting('meta_title_portfolio', 'Portfolio – Appsgain | Our Work & Case Studies');
$pageDescription = getSetting('meta_desc_portfolio', 'Projects delivered by Appsgain Technologies across FinTech, e-commerce, healthcare, education, AI and SaaS.');
$canonicalUrl    = SITE_URL . '/portfolio.php';

$currentPage = max(1, (int)($_GET['page'] ?? 1));
$catFilter   = (int)($_GET['cat'] ?? 0);
$perPage     = 9;

$where  = "p.is_active = 1";
$params = [];
if ($catFilter) { $where .= " AND p.category_id = ?"; $params[] = $catFilter; }

$total  = (int) dbFetchValue("SELECT COUNT(*) FROM projects p WHERE $where", $params);
$offset = ($currentPage - 1) * $perPage;

$projects = dbFetchAll(
    "SELECT p.*, pc.name AS category_name
     FROM projects p
     LEFT JOIN project_categories pc ON p.category_id = pc.id
     WHERE $where
     ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);
/* Only offer filters for categories that actually have live projects */
$categories = dbFetchAll(
    "SELECT pc.id, pc.name, COUNT(p.id) AS n
     FROM project_categories pc
     INNER JOIN projects p ON p.category_id = pc.id AND p.is_active = 1
     GROUP BY pc.id, pc.name ORDER BY pc.name ASC"
);
$totalPages = (int) ceil($total / $perPage);

/* Accent pairs cycled across cards so the grid reads as one system */
$accents = [
    ['#FF8A00','#FF3030'], ['#FF3030','#F50072'], ['#F50072','#D000A8'],
    ['#D000A8','#8A00E0'], ['#8A00E0','#6A00FF'], ['#6A00FF','#FF8A00'],
];

$metricProjects = getSetting('metric_projects', '500');
$metricClients  = getSetting('metric_clients', '300');
$metricYears    = getSetting('metric_years', '8');
?>
<?php $pageStyles = ['page-hero.css', 'portfolio.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require __DIR__ . '/includes/meta.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main">

<?php pageHero([
  'image'   => 'secimg_hero_portfolio',
  'eyebrow' => 'Our work',
  'lead'    => 'Products We Have',
  'accent'  => 'Designed & Shipped',
  'sub'     => 'Case studies from the software we have built — what the problem was, what we made, and what changed after launch.',
  'crumbs'  => [['Portfolio', null]],
  'actions' => [['Start Your Project', '/contact.php#enquiry', true], ['Our Services', '/services.php', false]],
  'stats'   => [
    [$metricProjects . '+', 'Projects delivered'],
    [$metricClients . '+',  'Clients served'],
    [$metricYears . '+',    'Years building'],
  ],
]); ?>

<section class="pf">
  <div class="pf-wrap">

    <?php if ($categories): ?>
    <nav class="pf-filters" aria-label="Filter projects by category">
      <a href="<?= SITE_URL ?>/portfolio.php" class="pf-pill<?= !$catFilter ? ' is-on' : '' ?>">
        All Projects <span><?= $total ?></span>
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="<?= SITE_URL ?>/portfolio.php?cat=<?= (int)$cat['id'] ?>"
         class="pf-pill<?= $catFilter === (int)$cat['id'] ? ' is-on' : '' ?>">
        <?= e($cat['name']) ?> <span><?= (int)$cat['n'] ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($projects): ?>
    <div class="pf-grid">
      <?php foreach ($projects as $i => $p):
        [$c1, $c2] = $accents[$i % count($accents)];
        $tags = !empty($p['technologies']) ? (json_decode((string)$p['technologies'], true) ?: []) : [];
        $img  = trim((string)($p['featured_image'] ?: $p['thumbnail'] ?? ''));
        $desc = truncate(strip_tags((string)($p['short_description'] ?: $p['description'] ?? '')), 118);
      ?>
      <article class="pf-card" style="--c1:<?= e($c1) ?>;--c2:<?= e($c2) ?>">
        <div class="pf-thumb">
          <?php if ($img): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e(ltrim($img, '/')) ?>" alt="<?= e($p['title']) ?>"
                 width="800" height="500" loading="lazy" decoding="async">
          <?php else: ?>
            <span class="pf-thumb-fallback" aria-hidden="true"><i class="fas fa-diagram-project"></i></span>
          <?php endif; ?>
          <?php if (!empty($p['category_name'])): ?>
          <span class="pf-tag"><?= e($p['category_name']) ?></span>
          <?php endif; ?>
          <?php if (!empty($p['is_featured'])): ?>
          <span class="pf-star" title="Featured project"><i class="fas fa-star" aria-hidden="true"></i></span>
          <?php endif; ?>
        </div>

        <div class="pf-body">
          <h2 class="pf-title"><?= e($p['title']) ?></h2>
          <?php if (!empty($p['client_name'] ?: $p['client'])): ?>
          <span class="pf-client"><?= e($p['client_name'] ?: $p['client']) ?></span>
          <?php endif; ?>
          <?php if ($desc): ?><p class="pf-desc"><?= e($desc) ?></p><?php endif; ?>

          <?php if ($tags): ?>
          <ul class="pf-tags">
            <?php foreach (array_slice($tags, 0, 4) as $t): ?>
            <li><?= e($t) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <span class="pf-link">View case study <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
        </div>

        <a class="pf-hit" href="<?= SITE_URL ?>/portfolio/<?= e($p['slug']) ?>"
           aria-label="<?= e($p['title']) ?> — view case study"></a>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pf-pager" aria-label="Pagination">
      <?php
      $base = SITE_URL . '/portfolio.php?' . ($catFilter ? 'cat=' . $catFilter . '&' : '');
      for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="<?= $base ?>page=<?= $i ?>" class="pf-page<?= $i === $currentPage ? ' is-on' : '' ?>"
         <?= $i === $currentPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <!-- No published projects yet: keep the page useful rather than blank -->
    <div class="pf-empty">
      <span class="pf-empty-ico"><i class="fas fa-folder-open" aria-hidden="true"></i></span>
      <h2>Case studies are being written up</h2>
      <p>
        We are preparing detailed write-ups of recent work. In the meantime, the
        quickest way to see whether we are a fit is to tell us what you are building —
        we will walk you through comparable projects on a call.
      </p>
      <div class="pf-empty-actions">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="ph-btn ph-btn-primary">Tell Us About Your Project</a>
        <a href="<?= SITE_URL ?>/services.php" class="ph-btn ph-btn-ghost">Browse Our Services</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- CTA -->
<section class="pf-cta">
  <div class="pf-wrap pf-cta-in">
    <div>
      <h2 class="pf-cta-title">Have Something Similar in <span class="pf-grad">Mind?</span></h2>
      <p class="pf-cta-sub">Send us the brief. You will get questions, a rough shape and an honest
         view on fit within one business day.</p>
    </div>
    <div class="pf-cta-actions">
      <a href="<?= SITE_URL ?>/contact.php#enquiry" class="ph-btn ph-btn-primary">Start Your Project</a>
      <a href="<?= SITE_URL ?>/contact.php" class="ph-btn ph-btn-ghost">Talk to Our Team</a>
    </div>
  </div>
</section>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
(function () {
  if (!('IntersectionObserver' in window) ||
      window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var io = new IntersectionObserver(function (e) {
    e.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('pf-in'); io.unobserve(en.target); } });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.pf-card').forEach(function (el) { el.classList.add('pf-anim'); io.observe(el); });
})();
</script>
</body>
</html>
