<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'gallery';
$pageTitle       = getSetting('meta_title_gallery', 'Gallery – Appsgain Technologies');
$pageDescription = getSetting('meta_desc_gallery', 'Browse Appsgain\'s photo gallery — our office, team, events and project showcases.');
$canonicalUrl    = SITE_URL . '/gallery.php';

$mediaItems = dbFetchAll(
    "SELECT * FROM media WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 48"
);
?>
<?php $pageStyles = ['page-hero.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <style>
    .gallery-grid{columns:3;gap:16px;column-gap:16px}
    .gallery-item{break-inside:avoid;margin-bottom:16px;border-radius:var(--radius-lg);overflow:hidden;position:relative;cursor:pointer}
    .gallery-item img{width:100%;display:block;transition:transform .4s}
    .gallery-item:hover img{transform:scale(1.05)}
    .gallery-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s}
    .gallery-item:hover .gallery-overlay{opacity:1}
    .lightbox{position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;display:flex;align-items:center;justify-content:center;display:none}
    .lightbox.active{display:flex}
    .lightbox img{max-width:90vw;max-height:90vh;border-radius:var(--radius-lg);}
    .lightbox-close{position:absolute;top:24px;right:24px;width:44px;height:44px;background:rgba(255,255,255,.15);border:none;border-radius:50%;color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center}
    @media(max-width:768px){.gallery-grid{columns:2}}
    @media(max-width:480px){.gallery-grid{columns:1}}
  </style>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_gallery',
  'eyebrow' => 'Gallery',
  'lead'    => 'Inside',
  'accent'  => 'Appsgain',
  'sub'     => 'Our workspace, our team and the projects we have shipped.',
  'crumbs'  => [['Gallery', null]],
]); ?>

<section style="padding:80px 0;background:var(--light);">
  <div class="container">
    <?php if (!empty($mediaItems)): ?>
    <div class="gallery-grid reveal">
      <?php foreach ($mediaItems as $media): ?>
      <div class="gallery-item" onclick="openLightbox('<?= UPLOADS_URL ?>/<?= e($media['file_path']) ?>')">
        <img src="<?= UPLOADS_URL ?>/<?= e($media['file_path']) ?>" alt="<?= e($media['alt_text'] ?: $media['original_name']) ?>" loading="lazy">
        <div class="gallery-overlay"><i class="fas fa-search-plus" style="color:#fff;font-size:28px;"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:80px 20px;">
      <i class="fas fa-images" style="font-size:64px;color:var(--gray);opacity:.25;margin-bottom:20px;display:block;"></i>
      <h3 style="font-family:var(--font-main);font-size:20px;color:var(--primary);margin-bottom:10px;">Gallery Coming Soon</h3>
      <p style="color:var(--gray);font-size:15px;">We're uploading photos from our office, team events and project showcases. Check back soon!</p>
      <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="margin-top:20px;"><i class="fas fa-envelope"></i> Get in Touch</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
  <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <img id="lightboxImg" src="" alt="Gallery image">
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
function openLightbox(src) {
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightbox').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeLightbox(e) {
  if (!e || e.target === document.getElementById('lightbox') || e.target.closest('.lightbox-close')) {
    document.getElementById('lightbox').classList.remove('active');
    document.getElementById('lightboxImg').src = '';
    document.body.style.overflow = '';
  }
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeLightbox(); });
</script>
</body>
</html>
