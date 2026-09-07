<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = 'careers';
$pageTitle       = getSetting('meta_title_careers', 'Careers at Appsgain Technologies — Join Our Team');
$pageDescription = getSetting('meta_desc_careers',  'We are hiring! Explore exciting career opportunities at Appsgain Technologies. Build amazing digital products with a passionate, innovative team.');
$pageKeywords    = 'careers Appsgain, jobs Appsgain Technologies, hiring Delhi, tech jobs remote';
$canonicalUrl    = SITE_URL . '/careers.php';

// ── Dynamic jobs from DB ──────────────────────────────────────
$jobs = [];
try {
    $jobs = dbFetchAll(
        "SELECT * FROM jobs WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, created_at DESC LIMIT 50"
    );
} catch (Exception $e) {
    // Table may not exist yet — run /database/jobs-migration.sql
}

// Static fallback if DB is empty / table missing
if (empty($jobs)) {
    $jobs = [
        ['id'=>1,'title'=>'Senior PHP Developer',         'department'=>'Development','type'=>'Full-time',    'location'=>'Delhi / Remote','experience'=>'3-5 years','salary_range'=>'₹8-14 LPA',  'skills'=>'PHP, Laravel, MySQL, REST APIs',                   'description'=>'We are looking for an experienced PHP developer to join our core backend team. You will architect and build scalable web applications for our global clients.','is_featured'=>0],
        ['id'=>2,'title'=>'React Native Developer',       'department'=>'Development','type'=>'Full-time',    'location'=>'Delhi / Remote','experience'=>'2-4 years','salary_range'=>'₹6-12 LPA',  'skills'=>'React Native, JavaScript, Redux, Firebase',        'description'=>'Build cross-platform mobile applications for iOS and Android. Work with modern tooling and ship code that users love.','is_featured'=>1],
        ['id'=>3,'title'=>'UI/UX Designer',               'department'=>'Design',     'type'=>'Full-time',    'location'=>'Delhi / Hybrid','experience'=>'2-3 years','salary_range'=>'₹5-9 LPA',   'skills'=>'Figma, Adobe XD, Prototyping, User Research',     'description'=>'Create stunning user interfaces and experiences for web and mobile products. Collaborate closely with developers and clients.','is_featured'=>0],
        ['id'=>4,'title'=>'Digital Marketing Manager',    'department'=>'Marketing',  'type'=>'Full-time',    'location'=>'Delhi / Hybrid','experience'=>'3-5 years','salary_range'=>'₹6-10 LPA',  'skills'=>'Google Ads, SEO, Meta Ads, Analytics',            'description'=>'Lead digital marketing campaigns for our agency and clients. Drive growth through data-driven strategies across all digital channels.','is_featured'=>0],
        ['id'=>5,'title'=>'AI/ML Engineer',               'department'=>'Development','type'=>'Full-time',    'location'=>'Delhi / Remote','experience'=>'2-4 years','salary_range'=>'₹10-18 LPA', 'skills'=>'Python, TensorFlow, PyTorch, LLMs, APIs',         'description'=>'Work on cutting-edge AI and machine learning projects. Build intelligent features into our client products.','is_featured'=>1],
        ['id'=>6,'title'=>'Business Development Executive','department'=>'Sales',     'type'=>'Full-time',    'location'=>'Delhi',         'experience'=>'1-3 years','salary_range'=>'₹4-8 LPA',   'skills'=>'Sales, CRM, Proposal Writing, Client Relations',  'description'=>'Drive business growth by identifying new clients and nurturing existing relationships.','is_featured'=>0],
        ['id'=>7,'title'=>'DevOps Engineer',              'department'=>'Development','type'=>'Full-time',    'location'=>'Delhi / Remote','experience'=>'2-4 years','salary_range'=>'₹8-14 LPA',  'skills'=>'AWS, Docker, Kubernetes, CI/CD, Linux',           'description'=>'Manage cloud infrastructure, build CI/CD pipelines, and ensure our applications scale reliably.','is_featured'=>0],
        ['id'=>8,'title'=>'Content Writer',               'department'=>'Marketing',  'type'=>'Part-time',    'location'=>'Remote',        'experience'=>'1-2 years','salary_range'=>null,          'skills'=>'SEO Writing, Blog, Copywriting, Research',        'description'=>'Create compelling content for websites, blogs, and marketing campaigns. Strong command of English required.','is_featured'=>0],
    ];
}

// Group by department
$byDept = [];
foreach ($jobs as $j) {
    $byDept[$j['department']][] = $j;
}
$featuredJobs = array_filter($jobs, fn($j) => !empty($j['is_featured']));

// Color maps
$deptColors = [
    'Development' => ['#6a00ff','rgba(106,0,255,.1)'],
    'Design'      => ['#8033ff','rgba(128,51,255,.1)'],
    'Marketing'   => ['#10b981','rgba(16,185,129,.1)'],
    'Sales'       => ['#6a00ff','rgba(106,0,255,.1)'],
    'HR'          => ['#5500cc','rgba(85,0,204,.1)'],
    'Support'     => ['#6a00ff','rgba(106,0,255,.1)'],
    'Finance'     => ['#8033ff','rgba(128,51,255,.1)'],
    'Operations'  => ['#059669','rgba(5,150,105,.1)'],
];
$typeColors  = [
    'Full-time'  => '#059669',
    'Part-time'  => '#46009f',
    'Contract'   => '#6a00ff',
    'Freelance'  => '#5500cc',
    'Internship' => '#5500cc',
];
?>
<?php $pageStyles = ['page-hero.css']; ?>
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
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/captcha-cta.css" />
  <style>
  /* ── Careers Page ── */

  /* Hero */
  .careers-hero {
    position: relative;
    background: radial-gradient(ellipse 70% 60% at 50% -5%, rgba(106,0,255,.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 90% 60%, rgba(128,51,255,.12) 0%, transparent 55%),
                linear-gradient(175deg, #0a0616 0%, #0b1026 50%, #0a0616 100%);
    padding: 90px 0 70px; overflow: hidden;
  }
  .careers-hero-glow {
    position: absolute; border-radius: 50%; pointer-events: none;
  }
  .careers-hero-glow-1 { width: 500px; height: 500px; top: -180px; right: -100px;
    background: radial-gradient(circle, rgba(106,0,255,.12) 0%, transparent 70%);
    animation: saasFloat2 20s ease-in-out infinite; }
  .careers-hero-glow-2 { width: 350px; height: 350px; bottom: -100px; left: -80px;
    background: radial-gradient(circle, rgba(128,51,255,.1) 0%, transparent 70%);
    animation: saasFloat2 25s ease-in-out 8s infinite reverse; }
  .careers-hero-inner { position: relative; z-index: 2; text-align: center; }
  .careers-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; border-radius: 30px; margin-bottom: 18px;
    background: rgba(106,0,255,.1); border: 1px solid rgba(106,0,255,.25);
    color: #fca5a5; font-size: 11.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase;
  }
  .careers-hero-title {
    font-family: 'Poppins','Inter',sans-serif;
    font-size: clamp(32px,4.5vw,56px); font-weight: 900;
    color: #fff; line-height: 1.08; letter-spacing: -1.5px; margin-bottom: 18px;
  }
  .careers-hero-title .ht-grad {
    background: linear-gradient(90deg, #fca5a5, #8033ff, #c4a5ff);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    animation: saasGradShift 4s ease infinite;
  }
  .careers-hero-sub {
    font-size: 16px; color: rgba(255,255,255,.65); line-height: 1.75;
    max-width: 560px; margin: 0 auto 28px;
  }
  .careers-hero-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 36px; }
  .careers-hero-perks { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
  .careers-perk {
    display: flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    padding: 7px 14px; border-radius: 30px; font-size: 13px; font-weight: 600;
    color: rgba(255,255,255,.8); backdrop-filter: blur(8px);
  }
  .careers-perk i { font-size: 12px; }

  /* Stats bar */
  .careers-stats-bar {
    background: linear-gradient(135deg, #0a0616, #0b1026);
    border-bottom: 1px solid rgba(255,255,255,.05);
  }
  .careers-stats-inner {
    display: flex; justify-content: center;
  }
  .careers-stat {
    text-align: center; padding: 22px 40px;
    border-right: 1px solid rgba(255,255,255,.07);
  }
  .careers-stat:last-child { border-right: none; }
  .careers-stat-num {
    font-family: 'Poppins',sans-serif; font-size: 26px; font-weight: 900;
    background: linear-gradient(135deg, #fff, var(--cs-c,#8033ff));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    line-height: 1; margin-bottom: 4px;
  }
  .careers-stat-lbl { font-size: 11px; color: rgba(255,255,255,.5); font-weight: 600; letter-spacing: .5px; }

  /* Culture / perks grid */
  .careers-culture { background: #f7f8fc; padding: 80px 0; }
  .culture-cards-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(230px,1fr)); gap: 20px;
  }
  .culture-card {
    background: #fff; border: 1.5px solid #e8ecf3; border-radius: 18px; padding: 26px;
    text-align: center; box-shadow: 0 4px 20px rgba(17,22,45,.05);
    transition: all .3s cubic-bezier(.4,0,.2,1);
    transform-style: preserve-3d;
  }
  .culture-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(17,22,45,.1);
    border-color: transparent;
  }
  .culture-icon {
    width: 56px; height: 56px; border-radius: 15px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff; margin: 0 auto 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
    transition: transform .3s;
  }
  .culture-card:hover .culture-icon { transform: scale(1.1) rotate(-3deg); }
  .culture-card h4 { font-family: 'Poppins',sans-serif; font-size: 16px; font-weight: 800; color: #0b1026; margin-bottom: 8px; }
  .culture-card p { font-size: 13.5px; color: #5e6475; line-height: 1.65; }

  /* Featured openings (dark cards) */
  .careers-featured { background: linear-gradient(175deg, #0a0616, #0b1026); padding: 80px 0; }
  .featured-jobs-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 20px; }
  .featured-job-card {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
    border-radius: 18px; padding: 24px; backdrop-filter: blur(10px);
    transition: all .3s; position: relative; overflow: hidden;
  }
  .featured-job-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6a00ff, #8033ff, #8033ff);
  }
  .featured-job-card:hover {
    background: rgba(255,255,255,.09);
    border-color: rgba(106,0,255,.3);
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,.4);
  }
  .fj-dept {
    font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px;
    margin-bottom: 8px; color: #8033ff;
  }
  .fj-title { font-family: 'Poppins',sans-serif; font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 10px; }
  .fj-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
  .fj-tag {
    display: flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 600; color: rgba(255,255,255,.65);
    background: rgba(255,255,255,.08); padding: 3px 9px; border-radius: 20px;
  }
  .fj-tag i { font-size: 9px; }
  .fj-salary { font-size: 13px; font-weight: 700; color: #34d399; margin-bottom: 12px; }
  .fj-desc { font-size: 13px; color: rgba(255,255,255,.6); line-height: 1.65; margin-bottom: 16px; }
  .fj-btns { display: flex; gap: 8px; }

  /* All openings */
  .careers-openings { background: #fff; padding: 80px 0; }
  .dept-group { margin-bottom: 48px; }
  .dept-group-header {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px; padding-bottom: 14px;
  }
  .dept-group-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
  .dept-group-name {
    font-family: 'Poppins',sans-serif; font-size: 20px; font-weight: 900; color: #0b1026;
    flex: 1;
  }
  .dept-group-count {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 50%;
    font-size: 12px; font-weight: 800; color: #fff;
  }

  .job-card-3d {
    background: #fff; border: 1.5px solid #e8ecf3; border-radius: 18px;
    padding: 22px 24px; margin-bottom: 12px;
    box-shadow: 0 2px 12px rgba(17,22,45,.04);
    transition: all .25s cubic-bezier(.4,0,.2,1);
    cursor: pointer;
  }
  .job-card-3d:hover {
    border-color: rgba(106,0,255,.2);
    box-shadow: 0 8px 28px rgba(17,22,45,.1);
    transform: translateX(4px);
  }
  .jc-top {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    margin-bottom: 10px;
  }
  .jc-title {
    font-family: 'Poppins',sans-serif; font-size: 17px; font-weight: 800; color: #0b1026;
    transition: color .2s;
  }
  .job-card-3d:hover .jc-title { color: #5500cc; }
  .jc-type {
    padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    color: #fff; white-space: nowrap; flex-shrink: 0;
  }
  .jc-featured-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 800; color: #6a00ff;
    background: rgba(106,0,255,.08); padding: 2px 8px; border-radius: 20px;
    margin-left: 8px; vertical-align: middle;
    border: 1px solid rgba(106,0,255,.2);
  }
  .jc-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
  .jc-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600; color: #475569;
    background: #f7f8fc; border: 1px solid #e7e9f0;
  }
  .jc-tag i { font-size: 9px; color: #8b90a0; }
  .jc-salary { font-size: 13px; font-weight: 700; color: #059669; margin-bottom: 8px; }
  .jc-desc { font-size: 13.5px; color: #5e6475; line-height: 1.65; margin-bottom: 12px; }
  .jc-skills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
  .jc-skill {
    font-size: 11.5px; font-weight: 600;
    background: rgba(106,0,255,.06); color: #6a00ff;
    border: 1px solid rgba(106,0,255,.12);
    padding: 3px 10px; border-radius: 20px;
  }
  .jc-apply {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; background: linear-gradient(135deg, #5500cc, #6a00ff);
    color: #fff; border-radius: 9px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all .22s;
  }
  .jc-apply:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(85,0,204,.3); color: #fff; }

  /* Apply section */
  .careers-apply {
    background: linear-gradient(175deg, #0a0616 0%, #0b1026 50%, #0a0616 100%);
    padding: 90px 0; position: relative; overflow: hidden;
  }
  .careers-apply::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse 60% 60% at 50% 50%, rgba(106,0,255,.08) 0%, transparent 70%);
    pointer-events: none;
  }
  .apply-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
    position: relative; z-index: 2;
  }
  .apply-left h2 {
    font-family: 'Poppins',sans-serif; font-size: 36px; font-weight: 900; color: #fff;
    margin-bottom: 14px; line-height: 1.1;
  }
  .apply-left p { font-size: 15px; color: rgba(255,255,255,.65); line-height: 1.75; margin-bottom: 24px; }
  .apply-perks { display: flex; flex-direction: column; gap: 12px; }
  .apply-perk {
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; color: rgba(255,255,255,.75);
  }
  .apply-perk i { font-size: 14px; width: 20px; flex-shrink: 0; }
  .apply-form-box {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
    border-radius: 20px; padding: 30px; backdrop-filter: blur(10px);
  }
  .apply-form-box h3 {
    font-family: 'Poppins',sans-serif; font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
  }
  .af-group { margin-bottom: 14px; }
  .af-label {
    display: block; font-size: 11.5px; font-weight: 700; color: rgba(255,255,255,.55);
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px;
  }
  .af-input {
    width: 100%; padding: 10px 14px;
    background: rgba(255,255,255,.08); border: 1.5px solid rgba(255,255,255,.15);
    border-radius: 10px; color: #fff; font-size: 13.5px; font-family: inherit;
    outline: none; transition: border-color .2s, background .2s;
  }
  .af-input::placeholder { color: rgba(255,255,255,.35); }
  .af-input:focus { border-color: rgba(106,0,255,.5); background: rgba(255,255,255,.1); }
  .af-input option { background: #0b1026; }
  .af-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .af-submit {
    width: 100%; padding: 13px; margin-top: 4px;
    background: linear-gradient(135deg, #6a00ff, #6a00ff);
    color: #fff; border: none; border-radius: 10px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    font-family: 'Poppins',sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .25s;
  }
  .af-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(106,0,255,.4); }
  .af-msg { margin-top: 10px; font-size: 13px; text-align: center; display: none; }

  /* ── Resume upload drop zone ── */
  .af-resume-wrap {
    border: 2px dashed rgba(255,255,255,.2); border-radius: 12px;
    background: rgba(255,255,255,.04); cursor: pointer;
    transition: all .25s; overflow: hidden;
  }
  .af-resume-wrap:hover, .af-resume-wrap.drag-over {
    border-color: #8033ff; background: rgba(128,51,255,.08);
  }
  .af-resume-wrap.has-file {
    border-color: #34d399; background: rgba(52,211,153,.07);
  }
  .af-resume-inner {
    padding: 20px 16px; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
  }
  .af-resume-inner i {
    font-size: 28px; color: rgba(255,255,255,.3); transition: .25s;
  }
  .af-resume-wrap:hover .af-resume-inner i { color: #8033ff; transform: translateY(-3px); }
  .af-resume-wrap.has-file .af-resume-inner i { color: #34d399; }
  #resumeLabel {
    font-size: 13px; color: rgba(255,255,255,.6); margin: 0;
    font-weight: 500; transition: .2s;
  }
  .af-resume-wrap.has-file #resumeLabel { color: #34d399; font-weight: 700; }

  /* Responsive */
  @media(max-width:768px) {
    .apply-grid { grid-template-columns:1fr !important; gap:36px !important; }
    .af-row-2 { grid-template-columns:1fr !important; }
    .apply-form-box { padding:22px !important; }
  }

  /* No jobs empty state */
  .no-jobs-state {
    text-align: center; padding: 80px 20px;
    background: #f7f8fc; border: 1.5px dashed #e7e9f0; border-radius: 20px;
    max-width: 480px; margin: 0 auto;
  }

  /* Responsive */
  @media (max-width: 900px) {
    .apply-grid { grid-template-columns: 1fr; gap: 40px; }
    .careers-stats-inner { flex-wrap: wrap; }
    .careers-stat { flex: 1 1 40%; border-bottom: 1px solid rgba(255,255,255,.07); }
  }
  @media (max-width: 768px) {
    .jc-top { flex-direction: column; }
    .featured-jobs-grid { grid-template-columns: 1fr; }
    .culture-cards-grid { grid-template-columns: repeat(2,1fr); }
    .af-row-2 { grid-template-columns: 1fr; }
  }
  @media (max-width: 480px) {
    .culture-cards-grid { grid-template-columns: 1fr; }
    .careers-stat { flex: 1 1 100%; }
  }
  </style>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  HERO                                ║
     ╚═══════════════════════════════════════╝ -->
<?php pageHero([
  'image'   => 'secimg_hero_careers',
  'eyebrow' => 'Careers',
  'lead'    => 'Build Your Career at',
  'accent'  => 'Appsgain',
  'badge'   => 'Hiring',
  'sub'     => 'Senior engineers on real products, not ticket queues. If you want ownership over what you build, read on.',
  'crumbs'  => [['Careers', null]],
  'actions' => [['View Open Roles', '#openings', true], ['Life at Appsgain', '/about.php', false]],
]); ?>

<!-- Stats bar -->
<div class="careers-stats-bar">
  <div class="container" style="padding:0">
    <div class="careers-stats-inner">
      <div class="careers-stat" style="--cs-c:#8033ff">
        <div class="careers-stat-num"><?= count($jobs) ?>+</div>
        <div class="careers-stat-lbl">Open Positions</div>
      </div>
      <div class="careers-stat" style="--cs-c:#c4a5ff">
        <div class="careers-stat-num"><?= count($byDept) ?></div>
        <div class="careers-stat-lbl">Departments</div>
      </div>
      <div class="careers-stat" style="--cs-c:#34d399">
        <div class="careers-stat-num">15+</div>
        <div class="careers-stat-lbl">Team Members</div>
      </div>
      <div class="careers-stat" style="--cs-c:#8033ff">
        <div class="careers-stat-num">8+</div>
        <div class="careers-stat-lbl">Years Strong</div>
      </div>
    </div>
  </div>
</div>

<!-- ╔═══════════════════════════════════════╗
     ║  CULTURE / WHY WORK HERE             ║
     ╚═══════════════════════════════════════╝ -->
<section class="careers-culture">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:48px">
      <span class="saas-label" style="background:rgba(106,0,255,.08);color:#6a00ff;border:1px solid rgba(106,0,255,.15)">
        <i class="fas fa-heart"></i> Why Appsgain
      </span>
      <h2 class="saas-heading dark-text">Life at <span class="grad">Appsgain</span></h2>
      <p class="saas-section-sub dark-sub">Great work comes from happy, empowered teams. Here's what makes working here special.</p>
    </div>
    <div class="culture-cards-grid">
      <?php
      $cultureItems = [
        ['#5500cc','linear-gradient(135deg,#5500cc,#8033ff)','fa-laptop-code', 'Remote & Hybrid Work',   'Flexible work from home or office with all the tools you need to do your best work.'],
        ['#6a00ff','linear-gradient(135deg,#6a00ff,#a855f7)','fa-graduation-cap','Learning & Development','Annual learning budget, certifications, tech conferences and internal knowledge-sharing.'],
        ['#059669','linear-gradient(135deg,#059669,#10b981)','fa-chart-line',  'Fast Career Growth',     'Clear growth paths, regular performance reviews and the opportunity to lead from day one.'],
        ['#46009f','linear-gradient(135deg,#46009f,#6a00ff)','fa-users',       'Collaborative Culture',  'Talented people who love what they do. No politics, just results and great vibes.'],
        ['#5500cc','linear-gradient(135deg,#5500cc,#6a00ff)','fa-rupee-sign',  'Competitive Pay',        'Market-competitive salaries with performance bonuses and equity for senior roles.'],
        ['#6a00ff','linear-gradient(135deg,#6a00ff,#8033ff)','fa-gamepad',     'Work-Life Balance',      'Flexible hours, generous leave policy, team outings and zero crunch-culture.'],
      ];
      foreach ($cultureItems as [$color, $grad, $icon, $title, $desc]):
      ?>
      <div class="culture-card saas-reveal" data-tilt data-tilt-strength="8">
        <div class="culture-icon" style="background:<?= $grad ?>"><i class="fas <?= $icon ?>"></i></div>
        <h4><?= $title ?></h4>
        <p><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ╔═══════════════════════════════════════╗
     ║  FEATURED / URGENT OPENINGS          ║
     ╚═══════════════════════════════════════╝ -->
<?php if (!empty($featuredJobs)): ?>
<section class="careers-featured">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:44px">
      <span class="saas-label" style="background:rgba(106,0,255,.1);color:#fca5a5;border:1px solid rgba(106,0,255,.2)">
        <i class="fas fa-fire"></i> Urgent Hiring
      </span>
      <h2 class="saas-heading light-text">Featured <span class="grad">Openings</span></h2>
    </div>
    <div class="featured-jobs-grid">
      <?php foreach ($featuredJobs as $job):
        $dc = $deptColors[$job['department'] ?? 'Development'] ?? ['#6a00ff','rgba(106,0,255,.1)'];
      ?>
      <div class="featured-job-card saas-reveal">
        <div class="fj-dept"><?= e($job['department']) ?></div>
        <div class="fj-title"><?= e($job['title']) ?></div>
        <div class="fj-meta">
          <span class="fj-tag"><i class="fas fa-map-marker-alt"></i><?= e($job['location']) ?></span>
          <span class="fj-tag"><i class="fas fa-clock"></i><?= e($job['type']) ?></span>
          <span class="fj-tag"><i class="fas fa-briefcase"></i><?= e($job['experience']) ?></span>
        </div>
        <?php if (!empty($job['salary_range'])): ?>
        <div class="fj-salary"><i class="fas fa-rupee-sign"></i> <?= e($job['salary_range']) ?></div>
        <?php endif; ?>
        <p class="fj-desc"><?= e(truncate($job['description'], 130)) ?></p>
        <div class="fj-btns">
          <?php
          $applyUrl = !empty($job['apply_url'])
            ? e($job['apply_url'])
            : SITE_URL . '/contact.php?subject=' . urlencode('Job Application: ' . $job['title']);
          ?>
          <?php if (!empty($job['apply_url'])): ?>
          <a href="<?= e($job['apply_url']) ?>" class="btn-saas-primary" style="padding:9px 18px;font-size:13px" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> Apply Externally
          </a>
          <?php else: ?>
          <a href="#apply-section" class="btn-saas-primary" style="padding:9px 18px;font-size:13px"
             onclick="applyForJob('<?= e(addslashes($job['title'])) ?>', this)">
            <i class="fas fa-paper-plane"></i> Apply Now
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ╔═══════════════════════════════════════╗
     ║  ALL OPEN POSITIONS                  ║
     ╚═══════════════════════════════════════╝ -->
<section id="openings" class="careers-openings">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:50px">
      <span class="saas-label light-blue"><i class="fas fa-list-ul"></i> All Openings</span>
      <h2 class="saas-heading dark-text"><?= count($jobs) ?> Open <span class="grad">Positions</span></h2>
      <p class="saas-section-sub dark-sub">Browse all current opportunities across <?= count($byDept) ?> departments</p>
    </div>

    <?php if (!empty($byDept)): ?>
    <?php foreach ($byDept as $dept => $deptJobs):
      [$dc, $dcbg] = $deptColors[$dept] ?? ['#6a00ff','rgba(106,0,255,.1)'];
    ?>
    <div class="dept-group saas-reveal">
      <div class="dept-group-header" style="border-bottom:2px solid <?= $dcbg ?>">
        <span class="dept-group-dot" style="background:<?= $dc ?>"></span>
        <span class="dept-group-name"><?= e($dept) ?></span>
        <span class="dept-group-count" style="background:<?= $dc ?>1a;color:<?= $dc ?>;font-size:13px;padding:2px 10px;border-radius:20px">
          <?= count($deptJobs) ?>
        </span>
      </div>

      <?php foreach ($deptJobs as $job):
        $tc = $typeColors[$job['type'] ?? 'Full-time'] ?? '#6a00ff';
        $skills = !empty($job['skills']) ? array_map('trim', explode(',', $job['skills'])) : [];
        $applyUrl = !empty($job['apply_url'])
          ? e($job['apply_url'])
          : SITE_URL . '/contact.php?subject=' . urlencode('Job Application: ' . $job['title']);
        $isExternal = !empty($job['apply_url']);
      ?>
      <div class="job-card-3d">
        <div class="jc-top">
          <div>
            <span class="jc-title"><?= e($job['title']) ?></span>
            <?php if (!empty($job['is_featured'])): ?>
            <span class="jc-featured-badge"><i class="fas fa-fire"></i> Urgent</span>
            <?php endif; ?>
          </div>
          <span class="jc-type" style="background:<?= $tc ?>"><?= e($job['type']) ?></span>
        </div>
        <div class="jc-meta">
          <span class="jc-tag"><i class="fas fa-map-marker-alt"></i><?= e($job['location']) ?></span>
          <span class="jc-tag"><i class="fas fa-clock"></i><?= e($job['experience']) ?> exp</span>
          <?php if (!empty($job['openings']) && intval($job['openings']) > 1): ?>
          <span class="jc-tag"><i class="fas fa-users"></i><?= intval($job['openings']) ?> seats</span>
          <?php endif; ?>
          <?php if (!empty($job['deadline'])): ?>
          <span class="jc-tag" style="color:#6a00ff;border-color:rgba(106,0,255,.2)">
            <i class="fas fa-calendar-times"></i>
            Apply by <?= date('M d, Y', strtotime($job['deadline'])) ?>
          </span>
          <?php endif; ?>
        </div>
        <?php if (!empty($job['salary_range'])): ?>
        <p class="jc-salary"><i class="fas fa-rupee-sign"></i> <?= e($job['salary_range']) ?></p>
        <?php endif; ?>
        <p class="jc-desc"><?= e($job['description']) ?></p>
        <?php if (!empty($skills)): ?>
        <div class="jc-skills">
          <?php foreach (array_slice($skills, 0, 6) as $sk): ?>
          <span class="jc-skill"><?= e($sk) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($isExternal): ?>
        <a href="<?= $applyUrl ?>" class="jc-apply" target="_blank" rel="noopener">
          <i class="fas fa-external-link-alt"></i> Apply Externally
        </a>
        <?php else: ?>
        <a href="#apply-section" class="jc-apply"
           onclick="applyForJob('<?= e(addslashes($job['title'])) ?>', this)">
          <i class="fas fa-paper-plane"></i> Apply Now
        </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php else: ?>
    <div class="no-jobs-state">
      <div style="font-size:48px;margin-bottom:16px">🚀</div>
      <h3 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:#0b1026;margin-bottom:10px">No Open Positions Right Now</h3>
      <p style="color:#5e6475;margin-bottom:24px">We don't have any open roles at the moment, but we're always looking for great talent. Send us your CV!</p>
      <a href="#apply" class="btn-saas-primary"><i class="fas fa-paper-plane"></i> Send Your CV</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ╔═══════════════════════════════════════╗
     ║  APPLY SECTION                       ║
     ╚═══════════════════════════════════════╝ -->
<section id="apply-section" class="careers-apply">
  <div class="container">
    <div class="apply-grid">
      <div class="apply-left saas-reveal-left">
        <div style="display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:30px;background:rgba(106,0,255,.1);border:1px solid rgba(106,0,255,.25);color:#fca5a5;font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;margin-bottom:20px">
          <i class="fas fa-envelope"></i> Apply Now
        </div>
        <h2>Don't See Your <span style="color:#8033ff">Role?</span></h2>
        <p>We're always on the lookout for exceptional talent. Send us your CV and we'll keep you in mind for future opportunities that match your skills.</p>
        <div class="apply-perks">
          <div class="apply-perk"><i class="fas fa-check-circle" style="color:#34d399"></i> Quick response within 2 business days</div>
          <div class="apply-perk"><i class="fas fa-lock" style="color:#c4a5ff"></i> Confidential application process</div>
          <div class="apply-perk"><i class="fas fa-smile" style="color:#8033ff"></i> Friendly hiring team, no unnecessary rounds</div>
          <div class="apply-perk"><i class="fas fa-users" style="color:#c4a5ff"></i> Internship &amp; fresher positions also available</div>
        </div>
      </div>
      <div class="apply-form-box saas-reveal-right">
        <h3><i class="fas fa-paper-plane" style="color:#8033ff"></i> Send Your Application</h3>
        <form id="careerForm" onsubmit="submitCareerForm(event)" enctype="multipart/form-data">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" id="careerCsrf" value="<?= csrfToken() ?>">
          <div class="af-row-2">
            <div class="af-group">
              <label class="af-label">Full Name <span style="color:#8033ff">*</span></label>
              <input type="text" class="af-input" name="name" placeholder="Your name" required>
            </div>
            <div class="af-group">
              <label class="af-label">Email <span style="color:#8033ff">*</span></label>
              <input type="email" class="af-input" name="email" placeholder="you@email.com" required>
            </div>
          </div>
          <div class="af-group">
            <label class="af-label">Phone Number</label>
            <input type="tel" class="af-input" name="phone" placeholder="+91 9xxxxxxxxx">
          </div>
          <div class="af-group">
            <label class="af-label">Position You're Interested In</label>
            <select class="af-input" name="position">
              <option value="">— Select Role —</option>
              <?php foreach ($jobs as $j): ?>
              <option value="<?= e($j['title']) ?>"><?= e($j['title']) ?> (<?= e($j['department']) ?>)</option>
              <?php endforeach; ?>
              <option value="Other / General Application">Other / General Application</option>
            </select>
          </div>
          <div class="af-group">
            <label class="af-label">Cover Letter / Message</label>
            <textarea class="af-input" name="message" rows="3"
              placeholder="Tell us about yourself and why you'd like to join Appsgain…"></textarea>
          </div>
          <div class="af-group">
            <label class="af-label">LinkedIn Profile URL</label>
            <input type="url" class="af-input" name="linkedin" placeholder="https://linkedin.com/in/yourprofile">
          </div>

          <!-- Resume Upload — Mandatory -->
          <div class="af-group">
            <label class="af-label">
              Resume / CV <span style="color:#8033ff">*</span>
              <span style="font-weight:400;font-size:11px;color:rgba(255,255,255,.4);margin-left:4px;">(PDF, DOC, DOCX · Max 5 MB)</span>
            </label>
            <div class="af-resume-wrap" id="resumeDropZone">
              <input type="file" name="resume" id="resumeFile" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required style="display:none;">
              <div class="af-resume-inner" onclick="document.getElementById('resumeFile').click()">
                <i class="fas fa-cloud-upload-alt" id="resumeIcon"></i>
                <p id="resumeLabel">Click to upload or drag &amp; drop your resume</p>
                <p style="font-size:11px;color:rgba(255,255,255,.35);margin:4px 0 0;">PDF, DOC, DOCX supported</p>
              </div>
            </div>
          </div>

          <?php require __DIR__ . '/includes/captcha-component.php'; ?>
          <button type="submit" class="af-submit btn-cta-animated btn-cta-primary" style="width:100%">
            <i class="fas fa-paper-plane"></i> Send Application
            <i class="fas fa-arrow-right cta-arrow"></i>
          </button>
          <p id="careerMsg" class="af-msg"></p>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
/* ── Resume drag & drop ── */
(function() {
  var dz  = document.getElementById('resumeDropZone');
  var inp = document.getElementById('resumeFile');
  var lbl = document.getElementById('resumeLabel');
  var ico = document.getElementById('resumeIcon');
  if (!dz || !inp) return;

  function setFile(name) {
    dz.classList.add('has-file');
    ico.className = 'fas fa-check-circle';
    lbl.textContent = name;
  }

  inp.addEventListener('change', function() {
    if (this.files && this.files[0]) setFile(this.files[0].name);
  });

  dz.addEventListener('dragover', function(e) {
    e.preventDefault(); dz.classList.add('drag-over');
  });
  dz.addEventListener('dragleave', function() { dz.classList.remove('drag-over'); });
  dz.addEventListener('drop', function(e) {
    e.preventDefault(); dz.classList.remove('drag-over');
    var f = e.dataTransfer.files[0];
    if (!f) return;
    inp.files = e.dataTransfer.files;
    setFile(f.name);
  });
})();

function submitCareerForm(e) {
  e.preventDefault();
  var form = e.target;
  var btn  = form.querySelector('.af-submit');
  var msg  = document.getElementById('careerMsg');

  function showErr(text) {
    msg.style.display = 'block';
    msg.style.color   = '#8033ff';
    msg.textContent   = text;
  }

  var phone     = form.querySelector('[name="phone"]').value.trim();
  var captchaEl = form.querySelector('.captcha-input');
  var resumeInp = document.getElementById('resumeFile');

  /* Validate phone */
  if (!phone || phone.replace(/\D/g,'').length < 7) {
    showErr('Phone number is required.'); return;
  }
  /* Validate resume */
  if (!resumeInp || !resumeInp.files || !resumeInp.files[0]) {
    showErr('Please upload your resume (PDF, DOC, or DOCX).'); return;
  }
  var f = resumeInp.files[0];
  var allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
  var ext = f.name.split('.').pop().toLowerCase();
  if (!['pdf','doc','docx'].includes(ext) && !allowed.includes(f.type)) {
    showErr('Only PDF, DOC, and DOCX files are allowed.'); return;
  }
  if (f.size > 5 * 1024 * 1024) {
    showErr('Resume file size must be under 5 MB.'); return;
  }
  /* Validate CAPTCHA */
  if (captchaEl && captchaEl.value.trim().length !== 6) {
    showErr('Please enter the 6-character CAPTCHA code.'); return;
  }

  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
  btn.disabled  = true;
  msg.style.display = 'none';

  var fd = new FormData(form);
  fetch('<?= SITE_URL ?>/api/apply.php', {
    method: 'POST',
    body: fd
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    msg.style.display = 'block';
    if (d.ok) {
      msg.style.color = '#34d399';
      msg.textContent = d.message || 'Application sent! We\'ll be in touch soon.';
      form.reset();
      var dz = document.getElementById('resumeDropZone');
      if (dz) {
        dz.classList.remove('has-file');
        document.getElementById('resumeIcon').className = 'fas fa-cloud-upload-alt';
        document.getElementById('resumeLabel').textContent = 'Click to upload or drag & drop your resume';
      }
    } else {
      msg.style.color = '#8033ff';
      msg.textContent = d.message || 'Something went wrong. Please try again.';
    }
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Application';
    btn.disabled  = false;
  })
  .catch(function() {
    msg.style.display = 'block';
    msg.style.color   = '#8033ff';
    msg.textContent   = 'Network error. Please email us at <?= e(getSetting('email','info@appsgain.in')) ?>.';
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Application';
    btn.disabled  = false;
  });
}

/* ── Apply Now: scroll to form + pre-fill position ── */
function applyForJob(title, link) {
  /* Set position select */
  var posEl = document.querySelector('#careerForm [name="position"]');
  if (posEl) {
    for (var i = 0; i < posEl.options.length; i++) {
      if (posEl.options[i].value === title) {
        posEl.selectedIndex = i; break;
      }
    }
  }
  /* Smooth scroll */
  var sec = document.getElementById('apply-section');
  if (sec) {
    sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  /* Highlight form with pulse */
  var box = document.querySelector('.apply-form-box');
  if (box) {
    box.style.transition = 'box-shadow .4s ease';
    box.style.boxShadow  = '0 0 0 3px #8033ff, 0 8px 32px rgba(128,51,255,.3)';
    setTimeout(function() { box.style.boxShadow = ''; }, 2000);
  }
  /* Focus on name field */
  setTimeout(function() {
    var nameEl = document.querySelector('#careerForm [name="name"]');
    if (nameEl) nameEl.focus();
  }, 600);
}
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script src="<?= ASSETS_URL ?>/js/3d-saas.js"></script>
</body>
</html>
