<?php
require_once __DIR__ . '/includes/bootstrap.php';
trackVisitor();

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/portfolio.php'); exit; }

$project = dbFetchRow("SELECT * FROM projects WHERE slug = ? AND is_active = 1", [$slug]);
if (!$project) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$activePage      = 'portfolio';
$pageTitle       = $project['title'] . ' — Case Study | Appsgain Technologies';
$pageDescription = truncate(strip_tags($project['description'] ?? ''), 160);
$canonicalUrl    = SITE_URL . '/portfolio/' . $project['slug'];

$tags     = !empty($project['technologies']) ? (json_decode($project['technologies'], true) ?: array_map('trim', explode(',', $project['technologies']))) : [];
$related  = dbFetchAll("SELECT id, title, slug, featured_image, category, client_name FROM projects WHERE is_active=1 AND id != ? ORDER BY is_featured DESC, RAND() LIMIT 3", [$project['id']]);

/* Category → accent color */
$catColors = [
    'E-Commerce'=>'#059669','Mobile App'=>'#6a00ff','Enterprise'=>'#6a00ff',
    'AI/ML'=>'#6a00ff','SaaS'=>'#46009f','Web'=>'#5500cc','ERP'=>'#5500cc',
    'CRM'=>'#46009f','Healthcare'=>'#6a00ff','Logistics'=>'#5500cc',
    'Education'=>'#6a00ff','Finance'=>'#059669',
];
$cat    = $project['category'] ?? '';
$accent = $catColors[$cat] ?? '#6a00ff';

$phone = getSetting('site_phone', getSetting('phone', '+91-9955446477'));
$phoneClean = preg_replace('/[^+0-9]/', '', $phone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <!-- Typeface loads once via css/style.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css"/>
  <style>
  :root{--ac:<?= $accent ?>;--font:var(--font-main);}
  *{box-sizing:border-box;}
  body{font-family:var(--font-body);color:#0b1026;background:#fff;}

  /* ── Banner ── */
  .ps-banner{background:#fff;border-bottom:1px solid #e8ecf3;padding:24px 0 20px;}
  .ps-crumbs{display:flex;align-items:center;gap:5px;font-size:12px;color:#8b90a0;margin-bottom:10px;}
  .ps-crumbs a{color:#5e6475;text-decoration:none;transition:.15s;}
  .ps-crumbs a:hover{color:var(--ac);}
  .ps-crumbs i{font-size:8px;}
  .ps-title{font-family:var(--font);font-size:clamp(22px,3.5vw,34px);font-weight:900;color:#0b1026;line-height:1.2;margin-bottom:10px;}
  .ps-meta-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
  .ps-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:700;}
  .ps-chip-cat{background:var(--ac)15;color:var(--ac);border:1px solid var(--ac)30;}
  .ps-chip-feat{background:rgba(106,0,255,.1);color:#5500cc;border:1px solid rgba(106,0,255,.2);}
  .ps-chip-status{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.2);}

  /* ── Layout ── */
  .ps-body{padding:32px 0 48px;}
  .ps-grid{display:grid;grid-template-columns:1fr 300px;gap:28px;align-items:start;}

  /* ── Hero image ── */
  .ps-hero-img{width:100%;border-radius:14px;overflow:hidden;margin-bottom:24px;background:linear-gradient(135deg,var(--ac),var(--ac)88);aspect-ratio:16/8;display:flex;align-items:center;justify-content:center;}
  .ps-hero-img img{width:100%;height:100%;object-fit:cover;display:block;}
  .ps-hero-img i{font-size:64px;color:rgba(255,255,255,.2);}

  /* ── Content ── */
  .ps-sec-title{font-family:var(--font);font-size:16px;font-weight:800;color:#0b1026;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--ac);display:flex;align-items:center;gap:8px;}
  .ps-sec-title i{font-size:13px;color:var(--ac);}
  .ps-desc{font-size:14.5px;color:#242a42;line-height:1.85;}
  .ps-desc p{margin:0 0 12px;}
  .ps-desc p:last-child{margin:0;}

  /* Tech tags */
  .ps-tags{display:flex;flex-wrap:wrap;gap:8px;}
  .ps-tag{padding:5px 14px;background:#fbfcfe;border:1.5px solid #e7e9f0;border-radius:30px;font-size:13px;font-weight:600;color:#242a42;transition:.2s;}
  .ps-tag:hover{border-color:var(--ac);color:var(--ac);}

  /* Related */
  .ps-related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
  .ps-rel-card{display:block;text-decoration:none;border-radius:12px;overflow:hidden;border:1.5px solid #e8ecf3;transition:.25s;}
  .ps-rel-card:hover{border-color:var(--ac);box-shadow:0 6px 20px rgba(0,0,0,.08);transform:translateY(-2px);}
  .ps-rel-img{height:90px;background:linear-gradient(135deg,#6a00ff,#8033ff);overflow:hidden;position:relative;}
  .ps-rel-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
  .ps-rel-body{padding:10px 12px;}
  .ps-rel-name{font-family:var(--font);font-size:13px;font-weight:700;color:#0b1026;line-height:1.35;}
  .ps-rel-cat{font-size:11px;color:#8b90a0;margin-top:2px;}

  /* ── Sidebar ── */
  .ps-sidebar-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:20px;margin-bottom:14px;}
  .ps-sidebar-card-title{font-family:var(--font);font-size:13.5px;font-weight:800;color:#0b1026;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:6px;}
  .ps-info-row{display:flex;align-items:flex-start;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f7f8fc;font-size:13px;gap:8px;}
  .ps-info-row:last-child{border-bottom:none;padding-bottom:0;}
  .ps-info-lbl{color:#8b90a0;font-weight:600;display:flex;align-items:center;gap:5px;white-space:nowrap;}
  .ps-info-lbl i{font-size:10px;color:var(--ac);}
  .ps-info-val{color:#0b1026;font-weight:700;text-align:right;word-break:break-word;}

  /* CTA card */
  .ps-cta-card{background:linear-gradient(135deg,#0b1026,#10142b);border-radius:14px;padding:20px;color:#fff;}
  .ps-cta-title{font-family:var(--font);font-size:15px;font-weight:800;margin-bottom:6px;}
  .ps-cta-sub{font-size:13px;color:rgba(255,255,255,.55);margin-bottom:16px;line-height:1.6;}
  .ps-cta-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 16px;background:var(--ac);color:#fff;border-radius:10px;font-size:13.5px;font-weight:700;text-decoration:none;margin-bottom:10px;transition:.2s;}
  .ps-cta-btn:hover{opacity:.9;transform:translateY(-1px);color:#fff;}
  .ps-cta-phone{display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);}
  .ps-cta-phone a{color:#fff;font-weight:600;text-decoration:none;}
  .ps-cta-phone a:hover{color:var(--ac);}

  /* ── Responsive ── */
  @media(max-width:900px){
    .ps-grid{grid-template-columns:1fr;}
    .ps-related-grid{grid-template-columns:repeat(2,1fr);}
    .ps-sidebar-order{order:-1;}
  }
  @media(max-width:600px){
    .ps-related-grid{grid-template-columns:1fr 1fr;}
    .ps-title{font-size:20px;}
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ── Page Banner ── -->
<div class="ps-banner">
  <div class="container">
    <div class="ps-crumbs">
      <a href="<?= SITE_URL ?>/">Home</a><i class="fas fa-chevron-right"></i>
      <a href="<?= SITE_URL ?>/portfolio.php">Portfolio</a><i class="fas fa-chevron-right"></i>
      <span><?= e(truncate($project['title'], 45)) ?></span>
    </div>
    <h1 class="ps-title"><?= e($project['title']) ?></h1>
    <div class="ps-meta-row">
      <?php if ($cat): ?><span class="ps-chip ps-chip-cat"><i class="fas fa-tag"></i><?= e($cat) ?></span><?php endif; ?>
      <?php if (!empty($project['client_name'] ?? $project['client'] ?? '')): ?><span class="ps-chip" style="background:#fbfcfe;color:#475569;border:1px solid #e7e9f0;"><i class="fas fa-building" style="font-size:10px;color:#8b90a0;"></i><?= e($project['client_name'] ?? $project['client'] ?? '') ?></span><?php endif; ?>
      <?php if ($project['is_featured']): ?><span class="ps-chip ps-chip-feat"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
      <?php $status = $project['status'] ?? 'published'; if ($status === 'published'): ?><span class="ps-chip ps-chip-status"><i class="fas fa-check-circle"></i> Delivered</span><?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Body ── -->
<div class="ps-body">
  <div class="container">
    <div class="ps-grid">

      <!-- Main Content -->
      <div>
        <!-- Hero image -->
        <div class="ps-hero-img">
          <?php if (!empty($project['featured_image'])): ?>
          <img src="<?= UPLOADS_URL ?>/<?= e($project['featured_image']) ?>" alt="<?= e($project['title']) ?>">
          <?php else: ?>
          <i class="fas fa-briefcase"></i>
          <?php endif; ?>
        </div>

        <!-- Overview -->
        <?php $desc = strip_tags($project['description'] ?? ''); if ($desc): ?>
        <div style="margin-bottom:28px;">
          <div class="ps-sec-title"><i class="fas fa-file-alt"></i> Project Overview</div>
          <div class="ps-desc"><?= nl2br(e($desc)) ?></div>
        </div>
        <?php endif; ?>

        <!-- Technologies -->
        <?php if (!empty($tags)): ?>
        <div style="margin-bottom:28px;">
          <div class="ps-sec-title"><i class="fas fa-code"></i> Technologies Used</div>
          <div class="ps-tags">
            <?php foreach ($tags as $tag): if (!trim($tag)) continue; ?>
            <span class="ps-tag"><?= e(trim($tag)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Live URL -->
        <?php $liveUrl = $project['project_url'] ?? $project['live_url'] ?? ''; if ($liveUrl): ?>
        <div style="margin-bottom:28px;">
          <a href="<?= e($liveUrl) ?>" target="_blank" rel="noopener"
             style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:var(--ac);color:#fff;border-radius:10px;font-size:13.5px;font-weight:700;text-decoration:none;transition:.2s;"
             onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-external-link-alt"></i> View Live Project
          </a>
        </div>
        <?php endif; ?>

        <!-- Related Projects -->
        <?php if (!empty($related)): ?>
        <div>
          <div class="ps-sec-title"><i class="fas fa-th-large"></i> More Projects</div>
          <div class="ps-related-grid">
            <?php
            $relGrads = ['linear-gradient(135deg,#6a00ff,#8033ff)','linear-gradient(135deg,#059669,#6a00ff)','linear-gradient(135deg,#5500cc,#6a00ff)'];
            foreach ($related as $ri => $rp):
            ?>
            <a href="<?= SITE_URL ?>/portfolio/<?= e($rp['slug']) ?>" class="ps-rel-card">
              <div class="ps-rel-img" style="background:<?= $relGrads[$ri % 3] ?>;">
                <?php if (!empty($rp['featured_image'])): ?>
                <img src="<?= UPLOADS_URL ?>/<?= e($rp['featured_image']) ?>" alt="<?= e($rp['title']) ?>">
                <?php else: ?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><i class="fas fa-briefcase" style="font-size:24px;color:rgba(255,255,255,.3);"></i></div><?php endif; ?>
              </div>
              <div class="ps-rel-body">
                <div class="ps-rel-name"><?= e(truncate($rp['title'], 55)) ?></div>
                <div class="ps-rel-cat"><?= e($rp['category'] ?? '') ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <aside class="ps-sidebar-order">

        <!-- Project Details -->
        <div class="ps-sidebar-card">
          <div class="ps-sidebar-card-title"><i class="fas fa-info-circle" style="color:var(--ac);font-size:12px;"></i> Project Details</div>
          <div>
            <?php
            $infoRows = array_filter([
              $cat ? ['ic'=>'fas fa-tag','lbl'=>'Category','val'=>e($cat)] : null,
              !empty($project['client_name']??$project['client']??'') ? ['ic'=>'fas fa-building','lbl'=>'Client','val'=>e($project['client_name']??$project['client']??'')] : null,
              !empty($project['status']) ? ['ic'=>'fas fa-circle','lbl'=>'Status','val'=>ucfirst(e($project['status']))] : null,
              !empty($project['excerpt']??$project['short_description']??'') ? ['ic'=>'fas fa-align-left','lbl'=>'Summary','val'=>e(truncate($project['excerpt']??$project['short_description']??'',80))] : null,
              !empty($project['created_at']) ? ['ic'=>'fas fa-calendar-alt','lbl'=>'Year','val'=>date('Y', strtotime($project['created_at']))] : null,
            ]);
            foreach ($infoRows as $row):
            ?>
            <div class="ps-info-row">
              <span class="ps-info-lbl"><i class="<?= $row['ic'] ?>"></i><?= $row['lbl'] ?></span>
              <span class="ps-info-val"><?= $row['val'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (!empty($tags)): ?>
            <div style="padding:10px 0 0;">
              <div style="font-size:12px;font-weight:600;color:#8b90a0;margin-bottom:8px;">Technologies</div>
              <div style="display:flex;flex-wrap:wrap;gap:5px;">
                <?php foreach (array_slice($tags, 0, 8) as $tag): if (!trim($tag)) continue; ?>
                <span style="padding:3px 9px;background:#fbfcfe;border-radius:20px;font-size:11.5px;font-weight:600;color:#242a42;border:1px solid #e7e9f0;"><?= e(trim($tag)) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- CTA -->
        <div class="ps-cta-card">
          <div class="ps-cta-title">Start a Similar Project</div>
          <div class="ps-cta-sub">Want something like this built for your business? Let's discuss your requirements.</div>
          <a href="<?= SITE_URL ?>/contact.php?subject=<?= urlencode('Project Enquiry: ' . $project['title']) ?>#enquiry" class="ps-cta-btn">
            <i class="fas fa-paper-plane"></i> Get a Free Quote
          </a>
          <div class="ps-cta-phone">
            <i class="fas fa-phone-alt" style="color:var(--ac);font-size:12px;"></i>
            <a href="tel:<?= e($phoneClean) ?>"><?= e($phone) ?></a>
          </div>
        </div>

        <!-- Share -->
        <div class="ps-sidebar-card">
          <div class="ps-sidebar-card-title"><i class="fas fa-share-alt" style="color:var(--ac);font-size:12px;"></i> Share Project</div>
          <div style="display:flex;gap:8px;">
            <?php $su = urlencode($canonicalUrl); $st = urlencode($project['title']); ?>
            <a href="https://www.linkedin.com/shareArticle?url=<?= $su ?>&title=<?= $st ?>" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:8px;background:#0077b5;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= $su ?>&text=<?= $st ?>" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:8px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;" title="Twitter/X"><i class="fab fa-x-twitter"></i></a>
            <a href="https://wa.me/?text=<?= $st ?>%20<?= $su ?>" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:8px;background:#25d366;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:13px;" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <button onclick="navigator.clipboard.writeText('<?= $canonicalUrl ?>');this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-link\'></i>',1500)" style="width:34px;height:34px;border-radius:8px;background:#f1f5f9;border:1px solid #e7e9f0;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#5e6475;font-size:13px;" title="Copy link"><i class="fas fa-link"></i></button>
          </div>
        </div>

      </aside>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
