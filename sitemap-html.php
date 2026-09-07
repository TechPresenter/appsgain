<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'sitemap';
$pageTitle       = 'HTML Sitemap – Appsgain Technologies | Complete Site Navigation';
$pageDescription = 'Complete HTML sitemap of Appsgain Technologies website. Find all pages, services, blog posts, portfolio projects, products, and resources.';
$canonicalUrl    = SITE_URL . '/sitemap-html.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/3d-saas.css"/>
  <style>
  .sm-hero{background:linear-gradient(175deg,#0a0616,#0b1026);padding:60px 0 50px;text-align:center;}
  .sm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px;padding:60px 0;}
  .sm-group{background:#fff;border:1.5px solid #e8ecf3;border-radius:16px;padding:24px;box-shadow:0 4px 16px rgba(17,22,45,.05);}
  .sm-group-title{display:flex;align-items:center;gap:9px;font-family:'Poppins',sans-serif;font-size:14.5px;font-weight:800;color:#0b1026;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f1f5f9;}
  .sm-group-title i{font-size:14px;}
  .sm-links{display:flex;flex-direction:column;gap:3px;}
  .sm-link{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;font-size:13px;font-weight:500;color:#475569;text-decoration:none;transition:all .2s;}
  .sm-link:hover{background:#f7f9fc;color:#6a00ff;padding-left:14px;}
  .sm-link i{font-size:10px;color:#8b90a0;}
  .sm-link:hover i{color:#6a00ff;}
  .sm-count{background:#f1f5f9;color:#8b90a0;font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:auto;}
  </style>
<?php $pageStyles = ['page-hero.css']; ?>
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Complete site map',
  'lead'    => 'HTML',
  'accent'  => 'Sitemap',
  'sub'     => 'Complete navigation of all pages, services, content and resources on our website.',
  'crumbs'  => [['Sitemap', null]],
]); ?>

<div style="background:#f7f8fc;padding:0 0 60px">
  <div class="container">
    <div class="sm-grid">

      <!-- MAIN PAGES -->
      <div class="sm-group">
        <div class="sm-group-title" style="--ct:#6a00ff"><i class="fas fa-home" style="color:#6a00ff"></i> Main Pages</div>
        <div class="sm-links">
          <?php
          /* Main navigation targets. testimonials/support/clients were
             retired; their URLs now 301 elsewhere. */
          $mainPages = [
            ['fas fa-home',        'Home',       '/'],
            ['fas fa-building',    'About Us',   '/about.php'],
            ['fas fa-user-tie',   'Our Founder','/our-founder.php'],
            ['fas fa-layer-group', 'Services',   '/services.php'],
            ['fas fa-briefcase',   'Portfolio',  '/portfolio.php'],
            ['fas fa-box-open',    'Products',   '/products.php'],
            ['fas fa-mobile-alt',  'Mobile Apps','/apps.php'],
            ['fas fa-newspaper',   'Blog',       '/blog.php'],
            ['fas fa-handshake',   'Partners',   '/partners.php'],
            ['fas fa-images',      'Gallery',    '/gallery.php'],
            ['fas fa-rocket',      'Careers',    '/careers.php'],
            ['fas fa-question-circle', 'FAQs',   '/faq.php'],
            ['fas fa-envelope',    'Contact Us', '/contact.php'],
          ];
          foreach($mainPages as [$ico,$lbl,$url]):
          ?>
          <a href="<?= SITE_URL.$url ?>" class="sm-link"><i class="<?= $ico ?>"></i><?= $lbl ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- SERVICES -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-cogs" style="color:#059669"></i> Services</div>
        <div class="sm-links">
          <?php
          $svcs=dbFetchAll("SELECT name,slug FROM services WHERE is_active=1 ORDER BY sort_order ASC LIMIT 15");
          foreach($svcs as $s):
          ?>
          <a href="<?= SITE_URL ?>/service/<?= e($s['slug']) ?>" class="sm-link"><i class="fas fa-chevron-right"></i><?= e($s['name']) ?></a>
          <?php endforeach; ?>
          <a href="<?= SITE_URL ?>/services.php" class="sm-link" style="color:#6a00ff;font-weight:700"><i class="fas fa-arrow-right"></i>View All Services</a>
        </div>
      </div>

      <!-- PRODUCTS -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-box-open" style="color:#8033ff"></i> Products & Apps</div>
        <div class="sm-links">
          <a href="<?= SITE_URL ?>/products.php" class="sm-link"><i class="fas fa-th"></i>All Products</a>
          <?php
          $prods=[];
          try{$prods=dbFetchAll("SELECT name,slug FROM products WHERE is_active=1 ORDER BY sort_order ASC LIMIT 8");}catch(Exception $e){}
          foreach($prods as $p):
          ?>
          <a href="<?= SITE_URL ?>/product/<?= e($p['slug']) ?>" class="sm-link"><i class="fas fa-chevron-right"></i><?= e($p['name']) ?></a>
          <?php endforeach; ?>
          <a href="<?= SITE_URL ?>/apps.php" class="sm-link"><i class="fas fa-mobile-alt"></i>Mobile Apps</a>
        </div>
      </div>

      <!-- BLOG POSTS -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-newspaper" style="color:#6a00ff"></i> Blog &amp; Insights</div>
        <div class="sm-links">
          <?php
          $blogs=dbFetchAll("SELECT title,slug FROM blogs WHERE status='published' ORDER BY published_at DESC LIMIT 10");
          foreach($blogs as $b):
          ?>
          <a href="<?= SITE_URL ?>/blog/<?= e($b['slug']) ?>" class="sm-link"><i class="fas fa-chevron-right"></i><?= e(truncate($b['title'],40)) ?></a>
          <?php endforeach; ?>
          <a href="<?= SITE_URL ?>/blog.php" class="sm-link" style="color:#6a00ff;font-weight:700"><i class="fas fa-arrow-right"></i>All Blog Posts</a>
        </div>
      </div>

      <!-- CAREERS -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-briefcase" style="color:#6a00ff"></i> Careers</div>
        <div class="sm-links">
          <a href="<?= SITE_URL ?>/careers.php" class="sm-link"><i class="fas fa-th"></i>All Job Openings</a>
          <?php
          try{
            $jobs=dbFetchAll("SELECT title,slug FROM jobs WHERE is_active=1 ORDER BY sort_order ASC LIMIT 6");
            foreach($jobs as $j):
          ?>
          <a href="<?= SITE_URL ?>/careers.php#<?= e($j['slug']) ?>" class="sm-link"><i class="fas fa-chevron-right"></i><?= e($j['title']) ?></a>
          <?php endforeach; }catch(Exception $e){}?>
        </div>
      </div>

      <!-- PARTNERS & CLIENTS -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-handshake" style="color:#6a00ff"></i> Partners &amp; Clients</div>
        <div class="sm-links">
          <a href="<?= SITE_URL ?>/partners.php" class="sm-link"><i class="fas fa-handshake"></i>Our Partners</a>
          <a href="<?= SITE_URL ?>/partners.php#become-partner" class="sm-link"><i class="fas fa-user-plus"></i>Become a Partner</a>
        </div>
      </div>

      <!-- LEGAL PAGES -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-gavel" style="color:#8b90a0"></i> Legal &amp; Policy</div>
        <div class="sm-links">
          <?php
          $legal=[['Privacy Policy','/privacy-policy.php'],['Terms & Conditions','/terms-of-service.php'],['Cookie Policy','/cookie-policy.php'],['Refund Policy','/refund-policy.php'],['Shipping Policy','/shipping-policy.php'],['Disclaimer','/disclaimer.php']];
          foreach($legal as [$l,$u]):
          ?>
          <a href="<?= SITE_URL.$u ?>" class="sm-link"><i class="fas fa-file-alt"></i><?= $l ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- RESOURCES -->
      <div class="sm-group">
        <div class="sm-group-title"><i class="fas fa-link" style="color:#8033ff"></i> Resources</div>
        <div class="sm-links">
          <a href="<?= SITE_URL ?>/faq.php" class="sm-link"><i class="fas fa-question-circle"></i>FAQs</a>
          <a href="<?= SITE_URL ?>/gallery.php" class="sm-link"><i class="fas fa-images"></i>Gallery</a>
          <a href="<?= SITE_URL ?>/sitemap.php" class="sm-link"><i class="fas fa-sitemap"></i>XML Sitemap</a>
          <a href="<?= SITE_URL ?>/sitemap-html.php" class="sm-link"><i class="fas fa-map"></i>HTML Sitemap</a>
          <a href="<?= SITE_URL ?>/contact.php" class="sm-link"><i class="fas fa-envelope"></i>Contact Us</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
