<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'about';
$schemaPageType  = 'about';
$pageTitle       = getSetting('meta_title_about', 'About Appsgain Technologies | Mobile App & Software Development Company');
$pageDescription = getSetting('meta_desc_about',  'Learn about Appsgain Technologies, a mobile app and software development company offering Android, iOS, cross-platform apps, custom software, web applications, ERP, CRM, SaaS and AI solutions.');
$canonicalUrl    = SITE_URL . '/about-us';

/* Canonical URL is /about-us. If someone lands on /about.php directly,
   send them on with a 301 so the two URLs never both rank.
   SITE_URL is environment-aware, which a relative .htaccess target is not. */
$_reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (str_ends_with($_reqPath, '/about.php')) {
    header('Location: ' . $canonicalUrl, true, 301);
    exit;
}

$s = getAllSettings();
$company = $s['company_name'] ?? 'Appsgain Technologies Private Limited';

$metricProjects = getSetting('metric_projects', '500');
$metricClients  = getSetting('metric_clients',  '300');
$metricYears    = getSetting('metric_years',    '8');
$metricExperts  = getSetting('metric_experts',  '50');

/* Services pulled live so this page can never drift from the Services page */
$svcList = dbFetchAll(
    "SELECT name, slug, COALESCE(icon, icon_class, 'fa-cog') AS icon
     FROM services WHERE is_active = 1 ORDER BY sort_order ASC"
);
$svcBySlug = [];
foreach ($svcList as $sv) $svcBySlug[$sv['slug']] = $sv;

/* ── What We Do ── */
$mobileCaps = [
    'Android App Development', 'iOS App Development', 'Cross-Platform App Development',
    'Flutter App Development', 'React Native App Development', 'Mobile UI/UX Design',
    'API & Backend Development', 'App Testing & Deployment', 'Mobile App Maintenance & Support',
];
$softwareCaps = [
    'Custom Business Software', 'Enterprise Applications', 'Business Management Systems',
    'Workflow Automation Software', 'Cloud-Based Software', 'SaaS Applications',
    'API Integrations', 'Database-Driven Applications',
];
$otherServices = [
    ['Web Application Development', 'web-portal-development', 'fa-window-maximize',
     'Responsive, scalable web applications for businesses, organizations and digital products.'],
    ['ERP Development', 'erp-development', 'fa-chart-line',
     'Centralized ERP solutions to manage business operations, resources, workflows and organizational processes.'],
    ['CRM Development', 'crm-development', 'fa-users-gear',
     'Customized CRM platforms for managing leads, customers, sales processes and business relationships.'],
    ['SaaS Development', 'saas-platform-development', 'fa-cloud',
     'Scalable SaaS platforms designed for subscription-based businesses and digital products.'],
    ['AI & Automation Solutions', 'ai-product-development', 'fa-brain',
     'AI and automation built into business applications to improve productivity, workflows and customer experience.'],
];

/* ── Why choose us ── */
$whyPoints = [
    ['Experienced Development Team', 'fa-users',
     'Our developers, designers and technology professionals work together to build reliable digital products.'],
    ['Business-Focused Solutions', 'fa-bullseye',
     'We focus on solving actual business challenges instead of simply delivering software features.'],
    ['Scalable Architecture', 'fa-layer-group',
     'Our applications are designed with future growth, performance and maintainability in mind.'],
    ['User-Centric Design', 'fa-wand-magic-sparkles',
     'We create simple, intuitive and responsive experiences across web and mobile platforms.'],
    ['Modern Technologies', 'fa-microchip',
     'Modern frameworks, APIs, cloud technologies and databases, chosen to fit the project rather than a house style.'],
    ['End-to-End Development', 'fa-diagram-project',
     'From idea and planning to design, development, testing, deployment and support, we manage the full lifecycle.'],
    ['Long-Term Support', 'fa-life-ring',
     'Our relationship continues after launch through maintenance, upgrades, optimization and new features.'],
];
?>
<?php $pageStyles = ['page-hero.css', 'about.css']; ?>
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
  'image'   => 'secimg_hero_about',
  'eyebrow' => 'About us',
  'lead'    => 'About Appsgain Technologies –',
  'accent'  => 'Mobile App & Software Development Company',
  'sub'     => 'Building powerful digital solutions for modern businesses.',
  'crumbs'  => [['About Us', null]],
  'actions' => [['Start Your Project', '/contact.php#enquiry', true], ['Talk to Our Experts', '/contact.php', false]],
  'stats'   => [
    [$metricProjects . '+', 'Projects delivered'],
    [$metricClients  . '+', 'Clients served'],
    [$metricYears    . '+', 'Years building'],
    [$metricExperts  . '+', 'Specialists'],
  ],
]); ?>

<!-- ══ INTRO ═════════════════════════════════════════════ -->
<section class="ab-sec ab-bg-white">
  <div class="ab-wrap ab-intro">
    <p class="ab-lead">
      <?= e($company) ?> is a mobile app development and software development company delivering
      custom digital solutions for startups, small businesses and enterprises. We specialize in
      Android app development, iOS app development, cross-platform mobile applications, custom
      software development, web application development, ERP, CRM, SaaS and AI-powered solutions.
    </p>
    <p class="ab-lead">
      Our goal is to help businesses transform their ideas into secure, scalable and user-friendly
      digital products that support long-term growth.
    </p>
  </div>
</section>

<!-- ══ WHO WE ARE ════════════════════════════════════════ -->
<section class="ab-sec ab-bg-soft" id="who-we-are">
  <div class="ab-wrap ab-split">
    <div>
      <span class="ab-eyebrow">Who we are</span>
      <h2 class="ab-h2">A Trusted Mobile App &amp; <span class="ab-grad">Software Development Company</span></h2>
      <p>At <?= e($company) ?>, we build technology solutions that solve real business problems.</p>
      <p>
        As a mobile app development company, we help businesses create modern applications for
        Android, iOS and cross-platform environments. From initial product planning and UI/UX design
        to development, testing, deployment and ongoing maintenance, our team supports the complete
        mobile application development lifecycle.
      </p>
      <p>
        Alongside mobile development, we provide custom software development services for businesses
        that need specialized web applications, business management systems, enterprise software and
        cloud-based platforms.
      </p>
      <p>
        We combine technology, design and business strategy to create digital products that are
        reliable, scalable and easy to use.
      </p>
    </div>

    <?php /* An uploaded image replaces the drawn panel; without one the
         original illustration still renders. */ ?>
    <?php $abImg = sectionImage('secimg_about_panel'); ?>
    <?php if ($abImg !== ''): ?>
    <aside class="ab-panel ab-panel--img">
      <img src="<?= e($abImg) ?>" alt="<?= e(getSetting('site_name', 'Appsgain Technologies')) ?>" loading="lazy" decoding="async">
    </aside>
    <?php else: ?>
    <aside class="ab-panel" aria-hidden="true">
      <div class="ab-panel-bar"><i></i><i></i><i></i></div>
      <div class="ab-panel-body">
        <div class="ab-panel-kpis"><span></span><span></span><span></span></div>
        <div class="ab-panel-rows"><span></span><span></span><span></span><span></span></div>
        <div class="ab-panel-cta"></div>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</section>

<!-- ══ WHAT WE DO ════════════════════════════════════════ -->
<section class="ab-sec ab-bg-white" id="services">
  <div class="ab-wrap">
    <div class="ab-head">
      <span class="ab-eyebrow">What we do</span>
      <h2 class="ab-h2">Mobile App &amp; <span class="ab-grad">Software Development Services</span></h2>
      <p class="ab-sub">Our technology services are designed to support businesses at every stage of their digital transformation.</p>
    </div>

    <div class="ab-two">
      <article class="ab-cap">
        <span class="ab-cap-ico"><i class="fas fa-mobile-screen-button" aria-hidden="true"></i></span>
        <h3>Mobile App Development</h3>
        <p>We develop feature-rich and scalable mobile applications for startups and established businesses.</p>
        <ul class="ab-caplist">
          <?php foreach ($mobileCaps as $c): ?><li><?= e($c) ?></li><?php endforeach; ?>
        </ul>
        <?php if (isset($svcBySlug['mobile-app-development'])): ?>
        <a href="<?= SITE_URL ?>/service/mobile-app-development" class="ab-link">
          Explore mobile app development <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
      </article>

      <article class="ab-cap">
        <span class="ab-cap-ico"><i class="fas fa-code" aria-hidden="true"></i></span>
        <h3>Custom Software Development</h3>
        <p>Our custom software development services help businesses replace manual processes and build technology around their specific requirements.</p>
        <ul class="ab-caplist">
          <?php foreach ($softwareCaps as $c): ?><li><?= e($c) ?></li><?php endforeach; ?>
        </ul>
        <?php if (isset($svcBySlug['custom-software-development'])): ?>
        <a href="<?= SITE_URL ?>/service/custom-software-development" class="ab-link">
          Explore custom software <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
      </article>
    </div>

    <div class="ab-grid">
      <?php foreach ($otherServices as [$name, $slug, $ico, $desc]): ?>
      <article class="ab-card">
        <span class="ab-card-ico"><i class="fas <?= e($ico) ?>" aria-hidden="true"></i></span>
        <h3><?= e($name) ?></h3>
        <p><?= e($desc) ?></p>
        <?php if (isset($svcBySlug[$slug])): ?>
        <a class="ab-hit" href="<?= SITE_URL ?>/service/<?= e($slug) ?>"
           aria-label="<?= e($name) ?> — learn more"></a>
        <span class="ab-card-link">Learn more <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ WHY CHOOSE US ═════════════════════════════════════ -->
<section class="ab-sec ab-bg-lav" id="why-choose-us">
  <div class="ab-wrap">
    <div class="ab-head">
      <span class="ab-eyebrow">Why us</span>
      <h2 class="ab-h2">Why Choose <span class="ab-grad">Appsgain Technologies?</span></h2>
      <p class="ab-sub">
        Choosing the right software development company can have a major impact on the success of your
        digital product. We focus on understanding your business before building the technology.
      </p>
    </div>

    <ol class="ab-why">
      <?php foreach ($whyPoints as $i => [$t, $ico, $d]): ?>
      <li class="ab-why-item">
        <span class="ab-why-ico"><i class="fas <?= e($ico) ?>" aria-hidden="true"></i></span>
        <div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ══ CTA ═══════════════════════════════════════════════ -->
<section class="ab-cta">
  <div class="ab-wrap ab-cta-in">
    <div>
      <h2 class="ab-cta-title">Have a Mobile App or <span class="ab-grad">Software Idea?</span></h2>
      <p class="ab-cta-sub">
        Let&rsquo;s turn your idea into a scalable digital product. Whether you need a mobile app
        development company, custom software development company, web application development partner
        or a long-term technology partner, <?= e($company) ?> can help you plan, build and scale
        your digital solution.
      </p>
    </div>
    <div class="ab-cta-actions">
      <a href="<?= SITE_URL ?>/contact.php#enquiry" class="ph-btn ph-btn-primary">Start Your Project</a>
      <a href="<?= SITE_URL ?>/services.php" class="ph-btn ph-btn-ghost">View Our Services</a>
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
    e.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('ab-in'); io.unobserve(en.target); } });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.ab-cap, .ab-card, .ab-why-item')
    .forEach(function (el) { el.classList.add('ab-anim'); io.observe(el); });
})();
</script>
</body>
</html>
