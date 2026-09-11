<?php
/**
 * Our Founder — Prashant Kumar.
 *
 * Deliberately NOT linked from the header, footer or any navigation.
 * Its only internal entry point is the HTML sitemap, which is what was
 * asked for: discoverable and indexable, but not part of the site's
 * primary navigation.
 *
 * Content is editable in Admin → Settings (founder_* keys); SEO is
 * editable in Admin → SEO under the `our-founder` page key.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/founder-sections.php';
trackVisitor();

$activePage     = 'our-founder';
$metaPage       = 'our-founder';
$schemaPageType = 'person';

$_s = getAllSettings();

/* ── Admin-editable identity ── */
$fName   = trim((string)($_s['founder_name']     ?? '')) ?: 'Prashant Kumar';
$fRole   = trim((string)($_s['founder_role']     ?? '')) ?: 'Founder & CEO, Appsgain Technologies Private Limited';
$fEmail  = trim((string)($_s['founder_email']    ?? '')) ?: ($_s['site_email'] ?? 'info@appsgain.in');
$fLinked = trim((string)($_s['founder_linkedin'] ?? '')) ?: 'https://www.linkedin.com/in/prashantdevtech';
$fPhoto  = trim((string)($_s['founder_photo']    ?? ''));
$fSince  = trim((string)($_s['founder_since']    ?? '')) ?: '2018';
$fEdu    = trim((string)($_s['founder_education']?? '')) ?: 'Babasaheb Bhimrao Ambedkar Bihar University';
$company = trim((string)($_s['company_name'] ?? $_s['site_name'] ?? '')) ?: 'Appsgain Technologies Private Limited';

$photoUrl = $fPhoto !== ''
    ? (str_starts_with($fPhoto, 'http') ? $fPhoto : UPLOADS_URL . '/' . ltrim($fPhoto, '/'))
    : '';

/* Monogram stands in until a portrait is uploaded */
$mono = '';
foreach (preg_split('/\s+/', $fName) as $w) {
    if ($w !== '') $mono .= strtoupper($w[0]);
    if (strlen($mono) >= 2) break;
}

/* ── SEO ── */
$pageTitle       = $fName . ' – Founder & CEO of Appsgain Technologies';
$pageDescription = 'Meet ' . $fName . ', Founder & CEO of Appsgain Technologies. Learn about his journey, '
                 . 'leadership, expertise in software development, AI, digital marketing and digital transformation.';
$metaKeywords    = 'Prashant Kumar, Prashant Kumar Founder, Prashant Kumar Appsgain Technologies, Appsgain Founder, '
                 . 'Appsgain Technologies Founder, Appsgain CEO, software company founder India, technology entrepreneur India, '
                 . 'software development entrepreneur, digital transformation entrepreneur';
$canonicalUrl    = SITE_URL . '/our-founder.php';
$ogImage         = $photoUrl;

/* Page blocks come from `founder_sections`; expertise feeds schema too. */
$sections = fdrSections();
$tok = [
    'name'      => $fName,
    'first'     => explode(' ', $fName)[0],
    'company'   => $company,
    'since'     => $fSince,
    'email'     => $fEmail,
    'education' => $fEdu,
];

/* knowsAbout for the Person schema, taken from the chips block so the
   markup and the structured data can never drift apart. */
$expertise = [];
foreach ($sections as $_sec) {
    if ($_sec['layout'] !== 'chips') continue;
    foreach (fdrItems($_sec['items'] ?? null, $tok) as $_it) {
        if ($_it['title'] !== '') $expertise[] = $_it['title'];
    }
}

$schemaData = ['person' => [
    'name'         => $fName,
    'jobTitle'     => 'Founder & CEO',
    'description'  => $pageDescription,
    'image'        => $photoUrl,
    'email'        => $fEmail,
    'sameAs'       => [$fLinked],
    'knowsAbout'   => $expertise,
    'alumniOf'     => $fEdu,
    'foundingDate' => $fSince,
]];

/* Rail contents mirror the live sections, so a hidden or renamed block
   never leaves a dead anchor behind. */
$toc = [];
foreach ($sections as $_sec) {
    $_h = trim(strip_tags(fdrTokens((string)$_sec['heading'], $tok)));
    if ($_h === '') continue;
    $toc[preg_replace('/[^a-z0-9-]/', '', strtolower((string)$_sec['section_key']))] = $_h;
}
$toc['connect'] = 'Connect';

?>
<?php $pageStyles = ['founder.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main" class="fdr">

  <!-- ══ Hero ══ -->
  <section class="fdr-hero">
    <div class="fdr-wrap">
      <nav class="fdr-crumb" aria-label="Breadcrumb">
        <a href="<?= SITE_URL ?>/">Home</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Our Founder</span>
      </nav>

      <div class="fdr-hero-in">
        <div>
          <span class="fdr-eyebrow"><i class="fas fa-user-tie" aria-hidden="true"></i> Our Founder</span>
          <h1 class="fdr-h1"><?= e($fName) ?> — <span class="fdr-grad">Founder &amp; CEO</span></h1>
          <p class="fdr-role"><?= e($fRole) ?></p>
          <p class="fdr-lede">
            <?= e($fName) ?> is the Founder and CEO of <?= e($company) ?>, a software development and
            digital innovation company focused on helping businesses build, launch, automate and grow
            through technology.
          </p>
          <div class="fdr-actions">
            <a href="mailto:<?= e($fEmail) ?>" class="fdr-btn fdr-btn-primary">
              <i class="fas fa-envelope" aria-hidden="true"></i> Email <?= e(explode(' ', $fName)[0]) ?>
            </a>
            <a href="<?= e($fLinked) ?>" target="_blank" rel="noopener me" class="fdr-btn fdr-btn-ghost">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn
            </a>
          </div>
        </div>

        <figure style="margin:0;">
          <div class="fdr-photo">
            <?php if ($photoUrl !== ''): ?>
              <img src="<?= e($photoUrl) ?>"
                   alt="<?= e($fName) ?>, Founder and CEO of Appsgain Technologies"
                   title="<?= e($fName) ?> – Founder &amp; CEO"
                   width="640" height="800" loading="eager" decoding="async">
            <?php else: ?>
              <?php /* No portrait uploaded yet — a monogram keeps the layout
                        intact rather than leaving a broken image frame. */ ?>
              <span class="fdr-photo-blank">
                <span class="fdr-photo-mono" aria-hidden="true"><?= e($mono) ?></span>
                <small>Portrait to be added in Admin&nbsp;→&nbsp;Settings</small>
              </span>
            <?php endif; ?>
          </div>
          <figcaption class="fdr-cap"><?= e($fName) ?>, Founder &amp; CEO of <?= e($company) ?></figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- ══ Body ══ -->
  <div class="fdr-wrap">
    <div class="fdr-body">
      <article class="fdr-main">

        <?php
        /* Blocks are admin-managed — see Admin -> Founder Page. */
        foreach ($sections as $_sec) fdrRenderSection($_sec, $tok);
        ?>

        <section class="fdr-sec" id="connect">
          <h2 class="fdr-h2">Connect with <?= e($fName) ?></h2>
          <p class="fdr-p">
            For business enquiries, technology partnerships, software development projects and digital
            transformation requirements, connect with <?= e(explode(' ', $fName)[0]) ?> and the Appsgain
            Technologies team.
          </p>
          <div class="fdr-contact" style="max-width:420px;">
            <a href="mailto:<?= e($fEmail) ?>"><i class="fas fa-envelope" aria-hidden="true"></i> <?= e($fEmail) ?></a>
            <a href="<?= e($fLinked) ?>" target="_blank" rel="noopener me"><i class="fab fa-linkedin-in" aria-hidden="true"></i> <?= e(preg_replace('~^https?://(www\.)?~', '', $fLinked)) ?></a>
            <a href="<?= SITE_URL ?>/"><i class="fas fa-globe" aria-hidden="true"></i> <?= e(preg_replace('~^https?://(www\.)?~', '', SITE_URL)) ?></a>
          </div>
        </section>

      </article>

      <!-- ══ Rail ══ -->
      <aside class="fdr-rail">
        <div class="fdr-widget">
          <h4><i class="fas fa-list-ul" aria-hidden="true"></i> On this page</h4>
          <ul class="fdr-toc" id="fdrToc">
            <?php foreach ($toc as $id => $label): ?>
            <li><a href="#<?= e($id) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="fdr-widget">
          <h4><i class="fas fa-address-card" aria-hidden="true"></i> Contact</h4>
          <div class="fdr-contact">
            <a href="mailto:<?= e($fEmail) ?>"><i class="fas fa-envelope" aria-hidden="true"></i> <?= e($fEmail) ?></a>
            <a href="<?= e($fLinked) ?>" target="_blank" rel="noopener me"><i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn</a>
          </div>
        </div>
      </aside>
    </div>

    <!-- ══ CTA ══ -->
    <section class="fdr-cta">
      <div>
        <h2>Have a project in mind?</h2>
        <p>
          Tell us what you are trying to build and the team will come back within a business day with
          questions, a rough shape and an honest view on fit.
        </p>
      </div>
      <div class="fdr-cta-actions">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="fdr-btn fdr-btn-primary">Start a conversation</a>
        <a href="<?= SITE_URL ?>/services.php" class="fdr-btn fdr-btn-ghost">See our services</a>
      </div>
    </section>
  </div>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
/* Scroll-spy for the rail: the last heading above the fold is current. */
(function () {
  var toc = document.getElementById('fdrToc');
  if (!toc) return;
  var links = toc.querySelectorAll('a');
  var secs  = Array.prototype.map.call(links, function (a) {
    return document.querySelector(a.getAttribute('href'));
  }).filter(Boolean);
  if (!secs.length) return;

  function spy() {
    var cur = null;
    secs.forEach(function (s) { if (s.getBoundingClientRect().top <= 120) cur = s.id; });
    links.forEach(function (a) {
      a.classList.toggle('is-on', a.getAttribute('href') === '#' + cur);
    });
  }
  window.addEventListener('scroll', spy, { passive: true });
  spy();
})();
</script>
</body>
</html>
