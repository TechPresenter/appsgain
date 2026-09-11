<?php
require_once __DIR__ . '/includes/bootstrap.php';
trackVisitor();

$activePage      = 'home';
$schemaPageType  = 'home';
$schemaData      = ['faqs'=>$homeFaqs??[], 'testimonials'=>$testimonials??[]];
$pageTitle       = getSetting('meta_title_home', 'Appsgain Technologies – #1 Software Development Company in Bangalore | Mobile Apps, Web, AI & ERP Solutions');
$pageDescription = getSetting('meta_desc_home',  'Appsgain Technologies delivers world-class Web Development, Mobile Apps, AI Solutions, Custom Software, E-Commerce, Digital Marketing and Cloud services. New Delhi.');
$pageKeywords    = getSetting('meta_keywords_home', 'web development, mobile app, AI solutions, digital marketing, New Delhi');
$canonicalUrl    = SITE_URL . '/';

// DB data
$heroSlides  = dbFetchAll("SELECT * FROM hero_slides WHERE page_key='home' AND is_active=1 ORDER BY sort_order ASC");
$services    = dbFetchAll(
    "SELECT *,
            COALESCE(icon, icon_class, 'fa-cog') AS icon,
            COALESCE(service_group, category, '') AS service_group,
            COALESCE(color, '#6a00ff') AS accent_color
     FROM services WHERE is_active=1 ORDER BY sort_order ASC LIMIT 12"
);

/* ── "Our Services" slider data ──────────────────────────────
   Cards come straight from the services table, so adding, editing,
   reordering or disabling a service in Admin changes this section.
   Section copy is admin-editable via Settings.                      */
$svcEyebrow     = getSetting('home_services_eyebrow',  'What We Build');
$svcTitleLead   = getSetting('home_services_title',    'Our');
$svcTitleAccent = getSetting('home_services_accent',   'Services');
$svcSubtitle    = getSetting('home_services_subtitle',
    'End-to-end digital solutions engineered to accelerate your business growth.');

/* Brand ramp, cycled across the cards so the row reads as one system */
$svcRamp = [
    ['#6a00ff', '#6a00ff'],
    ['#6a00ff', '#6a00ff'],
    ['#6a00ff', '#6a00ff'],
    ['#6a00ff', '#6a00ff'],
    ['#6a00ff', '#5500cc'],
    ['#8033ff', '#6a00ff'],
];

$svcCards  = [];
$svcGroups = [];
foreach ($services as $i => $svc) {
    $group = trim((string)($svc['service_group'] ?? '')) ?: 'Services';
    if (!in_array($group, $svcGroups, true)) $svcGroups[] = $group;

    [$c1, $c2] = $svcRamp[$i % count($svcRamp)];
    $svcCards[] = [
        'name'  => $svc['name'],
        'slug'  => $svc['slug'],
        'url'   => SITE_URL . '/service/' . $svc['slug'],
        'icon'  => $svc['icon'] ?: 'fa-cog',
        'group' => $group,
        'desc'  => truncate(strip_tags((string)($svc['short_description'] ?: ($svc['excerpt'] ?: $svc['description'] ?? ''))), 118),
        'c1'    => $c1,
        'c2'    => $c2,
    ];
}
$projects    = dbFetchAll("SELECT * FROM projects WHERE is_active=1 AND is_featured=1 ORDER BY sort_order ASC LIMIT 3");
$testimonials= dbFetchAll("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order ASC");
$blogs       = dbFetchAll("SELECT b.*, c.name as cat_name FROM blogs b LEFT JOIN blog_categories c ON b.category_id=c.id WHERE b.status='published' ORDER BY b.published_at DESC LIMIT 3");
$homeFaqs    = dbFetchAll("SELECT * FROM faqs WHERE is_active=1 AND page_key IN ('home','all') ORDER BY sort_order ASC LIMIT 5");

// Settings / metrics
$s = getAllSettings();
$metric_projects  = $s['metric_projects']  ?? '200';
$metric_clients   = $s['metric_clients']   ?? '150';
$metric_years     = $s['metric_years']     ?? '8';
$metric_experts   = $s['metric_experts']   ?? '15';
$metric_retention = $s['metric_retention'] ?? '98';

// Hero — first slide for defaults, all slides for multi-slide carousel
$h          = !empty($heroSlides) ? $heroSlides[0] : [];
$heroTitle  = $h['title']     ?? "Transforming Ideas Into\nPowerful Digital Solutions";
$heroSub    = $h['subtitle']  ?? ($h['description'] ?? 'Crafting innovative software that helps businesses scale faster, operate smarter, and achieve sustainable growth.');
$heroBadge  = $h['badge_text']?? 'Custom Software Development • ERP Solutions • CRM Development • SaaS Platforms';
$heroBtn1T  = $h['btn1_text'] ?? 'Start Your Project';
$heroBtn1U  = $h['btn1_url']  ?? SITE_URL.'/contact.php#enquiry';
$heroBtn2T  = $h['btn2_text'] ?? 'View Our Work';
$heroBtn2U  = $h['btn2_url']  ?? SITE_URL.'/portfolio.php';

// Fallback slides if DB empty
if (empty($heroSlides)) {
    $heroSlides = [
        ['title'=>"Transforming Ideas Into\nPowerful Digital Solutions",
         'subtitle'=>'Crafting innovative software that helps businesses scale faster, operate smarter, and achieve sustainable growth.',
         'badge_text'=>'Custom Software Development • ERP Solutions • CRM Development • SaaS Platforms',
         'btn1_text'=>'Start Your Project','btn1_url'=>SITE_URL.'/contact.php#enquiry',
         'btn2_text'=>'View Our Work','btn2_url'=>SITE_URL.'/portfolio.php'],
        ['title'=>"Building Future-Ready Technology\nfor Modern Businesses",
         'subtitle'=>'From startups to enterprises, we create secure, scalable, and high-performance digital products that deliver results.',
         'badge_text'=>'Web Applications • Mobile Apps • Enterprise Software • Cloud Solutions',
         'btn1_text'=>'Get a Free Quote','btn1_url'=>SITE_URL.'/contact.php#enquiry',
         'btn2_text'=>'Explore Services','btn2_url'=>SITE_URL.'/services.php'],
        ['title'=>"Automate More.\nAchieve More.",
         'subtitle'=>'Leverage AI-powered solutions and intelligent automation to streamline operations and unlock new opportunities.',
         'badge_text'=>'Artificial Intelligence • Business Automation • Smart Workflows • Digital Innovation',
         'btn1_text'=>'Explore AI Solutions','btn1_url'=>SITE_URL.'/service/ai-product-development',
         'btn2_text'=>'Book Consultation','btn2_url'=>SITE_URL.'/contact.php#enquiry'],
        ['title'=>"Engineering Exceptional\nDigital Experiences",
         'subtitle'=>'Combining creativity, strategy, and technology to build products users love and businesses trust.',
         'badge_text'=>'UI/UX Design • Product Engineering • Digital Transformation • Technology Consulting',
         'btn1_text'=>'Start a Project','btn1_url'=>SITE_URL.'/contact.php#enquiry',
         'btn2_text'=>'View Portfolio','btn2_url'=>SITE_URL.'/portfolio.php'],
        ['title'=>"Your Vision. Our Technology.\nUnlimited Possibilities.",
         'subtitle'=>'Partner with a team dedicated to turning ambitious ideas into successful digital products and business platforms.',
         'badge_text'=>'Startup Solutions • Enterprise Platforms • Custom Portals • Business Growth',
         'btn1_text'=>"Let's Build Together",'btn1_url'=>SITE_URL.'/contact.php#enquiry',
         'btn2_text'=>'Our Products','btn2_url'=>SITE_URL.'/products.php'],
    ];
    $h       = $heroSlides[0];
    $heroTitle  = $h['title'];
    $heroSub    = $h['subtitle'];
    $heroBadge  = $h['badge_text'];
    $heroBtn1T  = $h['btn1_text'];
    $heroBtn1U  = $h['btn1_url'];
    $heroBtn2T  = $h['btn2_text'];
    $heroBtn2U  = $h['btn2_url'];
}

// Service icon → color mapping
$svcColorMap = [
  'fa-laptop-code'  => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-mobile-alt'   => ['#8033ff','rgba(128,51,255,.1)'],
  'fa-globe'        => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-cloud'        => ['#46009f','rgba(70,0,159,.1)'],
  'fa-robot'        => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-brain'        => ['#a855f7','rgba(168,85,247,.1)'],
  'fa-network-wired'=> ['#8033ff','rgba(128,51,255,.1)'],
  'fa-user-cog'     => ['#10b981','rgba(16,185,129,.1)'],
  'fa-layer-group'  => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-building'     => ['#46009f','rgba(70,0,159,.1)'],
  'fa-cogs'         => ['#5e6475','rgba(94,100,117,.1)'],
  'fa-paint-brush'  => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-th-large'     => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-chart-line'   => ['#059669','rgba(5,150,105,.1)'],
  'fa-shopping-cart'=> ['#f06a00','rgba(234,88,12,.1)'],
  'fa-plug'         => ['#46009f','rgba(2,132,199,.1)'],
  'fa-code'         => ['#6a00ff','rgba(106,0,255,.1)'],
  'fa-cog'          => ['#5e6475','rgba(94,100,117,.1)'],
];
function getSvcStyle(string $icon, array $map): array {
  foreach ($map as $k => $v) {
    if (str_contains($icon, $k)) return $v;
  }
  return ['#6a00ff','rgba(106,0,255,.1)'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/3d-saas.css" />
  <?php /* Three.js is loaded once, just above 3d-saas.js which consumes it.
         A second deferred copy here made the library warn about multiple
         instances and cost an extra 600 KB download. */ ?>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  HERO — 3D IMMERSIVE + MULTI-SLIDE CAROUSEL         ║
     ╚══════════════════════════════════════════════════════╝ -->
<style>
/* ══════════════════════════════════════════════════
   HERO CAROUSEL — Full slide system
══════════════════════════════════════════════════ */
@keyframes hsSlideUp   { from { opacity:0; transform:translateY(32px); } to { opacity:1; transform:none; } }
@keyframes hsSlideDown { from { opacity:0; transform:translateY(-24px);} to { opacity:1; transform:none; } }
@keyframes saasBlinkCaret { 0%,49%{opacity:1} 50%,100%{opacity:0} }
@keyframes colorCycle {
  0%,100% { background-position: 0% 50%; }
  50%     { background-position: 100% 50%; }
}
@keyframes gradPulse {
  0%,100%{ opacity:.7; }
  50%    { opacity:1; }
}
@keyframes dotFlow {
  0%   { background-position: 0% 0%; }
  100% { background-position: 200% 0%; }
}
@keyframes heroFadeInUp {
  from { opacity:0; transform:translateY(22px) scale(.98); }
  to   { opacity:1; transform:none; }
}

/* Base: all slides hidden */
.hero-slide {
  display: none;
  position: relative;
}
/* Active slide fades/slides in */
.hero-slide.hs-active {
  display: block;
  animation: hsSlideUp .65s cubic-bezier(.16,1,.3,1) both;
}
/* Exiting slide (if needed for future cross-fade) */
.hero-slide.hs-exit {
  display: block;
  animation: hsSlideDown .4s ease forwards;
  pointer-events: none;
}

/* ── Slide dot nav ── */
.hero-slide-dots {
  display: flex; gap: 10px; align-items: center;
  margin-top: 28px;
}
.hero-slide-dot {
  width: 8px; height: 8px; border-radius: 20px;
  background: rgba(255,255,255,.25);
  border: 1px solid rgba(255,255,255,.15);
  cursor: pointer; padding: 0;
  transition: all .4s cubic-bezier(.34,1.56,.64,1);
}
.hero-slide-dot.active {
  width: 32px;
  background: linear-gradient(90deg, #c4a5ff, #c4a5ff, #c4a5ff);
  background-size: 200% 100%;
  animation: dotFlow 2s linear infinite;
  border-color: transparent;
  box-shadow: 0 0 10px rgba(128,51,255,.6);
}

/* ── Typing badge ── */
#heroTypingText {
  display: inline;
}
#heroCaret {
  display: inline-block;
  width: 2px; height: 1em;
  background: #c4a5ff;
  margin-left: 2px; vertical-align: middle;
  animation: saasBlinkCaret .9s step-end infinite;
}

/* ══════════════════════════════════════════════════
   HOMEPAGE DESIGN ENHANCEMENTS — Color & Motion
══════════════════════════════════════════════════ */

/* ── Hero — light premium composition ──
   White/off-white is the primary surface; the brand gradient appears
   only as a soft glow behind the product visual. */
.saas-hero {
  background:
    radial-gradient(ellipse 70% 60% at 78% 22%, rgba(106,0,255,.09) 0%, transparent 62%),
    radial-gradient(ellipse 55% 50% at 12% 8%,  rgba(245,0,114,.05) 0%, transparent 58%),
    linear-gradient(180deg, #FFFFFF 0%, #F7F8FC 62%, #FFFFFF 100%) !important;
  color: #5E6475 !important;
}
.saas-hero-title { color: #11162D !important; }
.saas-hero-sub   { color: #5E6475 !important; }

/* ── Floating tech icons background ── */
.hero-tech-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 1;
}
.htb-icon {
  position: absolute;
  font-size: var(--sz, 42px);
  color: var(--ic, #fff);
  opacity: var(--op, .06);
  filter: blur(var(--bl, 0px));
  animation: var(--an, htbFloat1) var(--dur, 9s) ease-in-out infinite;
  animation-delay: var(--dl, 0s);
  will-change: transform;
  line-height: 1;
}
@keyframes htbFloat1 {
  0%,100% { transform: translateY(0)     rotate(0deg); }
  50%     { transform: translateY(-22px) rotate(8deg); }
}
@keyframes htbFloat2 {
  0%,100% { transform: translateY(0)     rotate(0deg); }
  50%     { transform: translateY(18px)  rotate(-6deg); }
}
@keyframes htbFloat3 {
  0%,100% { transform: translate(0,0)         rotate(0deg); }
  33%     { transform: translate(14px,-18px)   rotate(10deg); }
  66%     { transform: translate(-10px,10px)   rotate(-8deg); }
}
@keyframes htbFloat4 {
  0%,100% { transform: translate(0,0)         scale(1)    rotate(0deg); }
  50%     { transform: translate(-16px,-14px) scale(1.1) rotate(-12deg); }
}
@keyframes htbFloat5 {
  0%,100% { transform: translateY(0)   scale(1); }
  50%     { transform: translateY(28px) scale(.92); }
}
@keyframes htbSpin {
  0%   { transform: rotate(0deg)   scale(1); }
  50%  { transform: rotate(180deg) scale(1.08); }
  100% { transform: rotate(360deg) scale(1); }
}

/* ── Hero title gradient — more colorful ── */
.saas-hero-title .ht-grad {
  background: linear-gradient(90deg,
    #c4a5ff 0%, #c4a5ff 25%, #8033ff 50%, #34d399 75%, #c4a5ff 100%) !important;
  background-size: 300% auto !important;
  -webkit-background-clip: text !important;
  -webkit-text-fill-color: transparent !important;
  background-clip: text !important;
  animation: colorCycle 6s ease infinite !important;
}

/* ── Section heading grads — vibrant ── */
.saas-heading .grad {
  background: linear-gradient(135deg, #6a00ff, #8033ff, #6a00ff) !important;
  background-size: 200% auto !important;
  -webkit-background-clip: text !important;
  -webkit-text-fill-color: transparent !important;
  background-clip: text !important;
  animation: colorCycle 5s ease infinite !important;
}

/* ── Services section — softer light bg ── */
.saas-services {
  background: linear-gradient(180deg, #f7f8fc 0%, #f7f9fc 100%) !important;
}

/* ── Service card accent bars — colorful ── */
.saas-svc-card:hover {
  box-shadow: 0 24px 64px rgba(128,51,255,.15) !important;
}

/* ── Stats — light section, restrained figures ── */
.saas-stats {
  background: #FFFFFF !important;
  position: relative;
}
.saas-stats::before { content: none !important; }
.saas-stat-num {
  font-size: clamp(34px, 4vw, 48px) !important;
  color: #11162D !important;
  -webkit-text-fill-color: #11162D !important;
  background: none !important;
}
.saas-stat-lbl, .saas-stat-label { color: #5E6475 !important; }
.saas-stat-card {
  background: #FFFFFF !important;
  border: 1px solid #E7E9F0 !important;
  box-shadow: 0 1px 3px rgba(17,22,45,.06) !important;
}
.saas-stat-card:hover .saas-stat-num {
  animation: gradPulse .8s ease;
}

/* ── Process — light, thin connecting line ── */
.saas-process {
  background: #F7F8FC !important;
}
.saas-process h2, .saas-process h3, .saas-process h4 { color: #11162D !important; }
.saas-process p, .saas-process li { color: #5E6475 !important; }
.saas-process-step {
  background: #FFFFFF !important;
  border: 1px solid #E7E9F0 !important;
}
.saas-process-step:hover {
  background: #FFFFFF !important;
  box-shadow: 0 12px 32px rgba(17,22,45,.09) !important;
  transform: translateY(-3px);
}

/* ── Why section — light gradient ── */
.saas-why {
  background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%) !important;
}
.saas-why-card:hover {
  box-shadow: 0 28px 64px rgba(128,51,255,.14) !important;
}

/* ── Technology stack — minimal, plenty of whitespace ── */
.saas-tech {
  background: #FFFFFF !important;
}
.saas-tech h2, .saas-tech h3, .saas-tech h4 { color: #11162D !important; }
.saas-tech p { color: #5E6475 !important; }
.saas-tech-cat {
  background: #FFFFFF !important;
  border: 1px solid #E7E9F0 !important;
}
.saas-tech-cat:hover {
  background: #FFFFFF !important;
  border-color: rgba(106,0,255,.22) !important;
  box-shadow: 0 8px 24px rgba(17,22,45,.07) !important;
}
.saas-tech-item, .tech-pill {
  background: #FFFFFF !important;
  border: 1px solid #E7E9F0 !important;
  color: #11162D !important;
}

/* ── Projects ── */
.saas-projects {
  background: linear-gradient(180deg, #f7f8fc 0%, #f7f8fc 100%) !important;
}

/* ── Testimonials — light, elegant white cards ── */
.saas-testimonials {
  background: #F7F8FC !important;
}
.saas-testimonials h2, .saas-testimonials h3 { color: #11162D !important; }
.saas-testimonials p { color: #5E6475 !important; }

/* ── Blog section ── */
.saas-blog {
  background: linear-gradient(180deg, #f7f8fc 0%, #f7f9fc 100%) !important;
}

/* ── FAQ ── */
.saas-faq {
  background: linear-gradient(180deg, #f7f8fc 0%, #f7f9fc 100%) !important;
}
.saas-faq-item:hover {
  box-shadow: 0 4px 20px rgba(128,51,255,.1) !important;
}

/* ── CTA — the single deep-navy contrast band ── */
.saas-cta {
  background:
    radial-gradient(ellipse 60% 70% at 50% 0%, rgba(106,0,255,.16) 0%, transparent 62%),
    linear-gradient(160deg, #0B1026 0%, #10142B 55%, #0B1026 100%) !important;
}

/* ── Hero eyebrow — restrained pill ── */
.saas-hero-eyebrow {
  background: rgba(106,0,255,.05) !important;
  border: 1px solid rgba(106,0,255,.16) !important;
  color: #6A00FF !important;
  box-shadow: none !important;
}

/* ── Metrics values — solid ink, gradient reserved for CTAs ── */
.saas-hm-val {
  background: none !important;
  -webkit-text-fill-color: #11162D !important;
  color: #11162D !important;
}
.saas-hm-lbl { color: #8B90A0 !important; }
.saas-hm-sep { background: #E7E9F0 !important; }

/* ── Hero buttons — the brand signature gradient ── */
.btn-saas-primary {
  background: linear-gradient(135deg, #FF8A00 0%, #FF3030 28%, #F50072 52%, #D000A8 72%, #6A00FF 100%) !important;
  box-shadow: 0 8px 32px rgba(128,51,255,.45) !important;
}
.btn-saas-primary:hover {
  box-shadow: 0 12px 40px rgba(128,51,255,.6) !important;
}

/* ── Saas label variants ── */
.saas-label.blue {
  background: rgba(128,51,255,.1) !important;
  color: #e0d0ff !important;
  border-color: rgba(128,51,255,.2) !important;
}

/* ── Blog card category — vibrant ── */
.saas-blog-cat {
  background: linear-gradient(135deg, #8033ff, #8033ff) !important;
}

/* ── 3D tech slider brighter ── */
.tech3d-section {
  background: linear-gradient(180deg, #FFFFFF 0%, #F7F8FC 50%, #FFFFFF 100%) !important;
}

/* ══ Tech Stack — single continuous row ══════════════════ */
.tstack{
  padding-block:clamp(56px,6vw,88px);
  background:#FFFFFF;
  overflow:hidden;
}
.tstack-head{ text-align:center; max-width:640px; margin:0 auto clamp(30px,4vw,46px); }
.tstack-eyebrow{
  display:inline-flex; align-items:center; gap:8px;
  padding:6px 14px; margin-bottom:16px; border-radius:999px;
  font-size:11.5px; font-weight:600; letter-spacing:.12em; text-transform:uppercase;
  color:#6A00FF; background:rgba(106,0,255,.06);
  border:1px solid rgba(106,0,255,.14);
}
.tstack-title{
  font-family:var(--font-main),sans-serif;
  font-size:clamp(26px,3.2vw,38px); font-weight:800; letter-spacing:-.02em;
  color:#11162D; margin:0 0 12px; line-height:1.2;
}
.tstack-title span{
  background:linear-gradient(90deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  -webkit-background-clip:text; background-clip:text;
  -webkit-text-fill-color:transparent; color:transparent;
}
.tstack-sub{ font-size:15.5px; line-height:1.7; color:#5E6475; margin:0; }

/* The rail: full-bleed, faded at both edges */
.tstack-rail{
  position:relative;
  -webkit-mask-image:linear-gradient(90deg,transparent,#000 9%,#000 91%,transparent);
          mask-image:linear-gradient(90deg,transparent,#000 9%,#000 91%,transparent);
}
.tstack-track{
  display:flex; gap:14px; width:max-content;
  padding-block:6px;
  animation:tstackScroll 52s linear infinite;
  will-change:transform;
}
.tstack-rail:hover .tstack-track,
.tstack-rail:focus-within .tstack-track{ animation-play-state:paused; }
@keyframes tstackScroll{
  from{ transform:translate3d(0,0,0); }
  to  { transform:translate3d(-50%,0,0); }   /* list is duplicated once */
}

.tstack-item{
  flex:0 0 auto;
  display:inline-flex; align-items:center; gap:10px;
  height:52px; padding:0 20px;
  border-radius:12px;
  background:#FFFFFF; border:1px solid #E7E9F0;
  box-shadow:0 1px 2px rgba(17,22,45,.04);
  font-size:14px; font-weight:600; color:#11162D; white-space:nowrap;
  transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.tstack-item:hover{
  border-color:rgba(106,0,255,.28);
  box-shadow:0 6px 18px rgba(17,22,45,.07);
  transform:translateY(-2px);
}
.tstack-item i{
  font-size:18px;
  color:#9AA0B4;                    /* neutral by default */
  transition:color .2s ease;
}
.tstack-item:hover i{ color:var(--tc,#6A00FF); }   /* true brand colour on hover */

@media (max-width:640px){
  .tstack-item{ height:46px; padding:0 16px; font-size:13px; gap:8px; }
  .tstack-item i{ font-size:16px; }
  .tstack-track{ gap:10px; animation-duration:38s; }
}

/* No motion: drop the marquee, show a calm wrapped grid */
@media (prefers-reduced-motion:reduce){
  .tstack-rail{ -webkit-mask-image:none; mask-image:none; }
  .tstack-track{
    animation:none; width:auto; flex-wrap:wrap; justify-content:center;
    max-width:1200px; margin-inline:auto; padding-inline:24px;
  }
  .tstack-item[aria-hidden="true"]{ display:none; }
}

/* ══ Testimonials — balanced 3-column grid ═══════════════ */
.tsm{ padding-block:var(--section-y,64px); background:#F7F8FC; }
.tsm-head{ text-align:center; max-width:660px; margin:0 auto clamp(34px,4vw,52px); }
.tsm-eyebrow{
  display:inline-flex; align-items:center; gap:8px;
  padding:6px 14px; margin-bottom:16px; border-radius:999px;
  font-size:11.5px; font-weight:600; letter-spacing:.12em; text-transform:uppercase;
  color:#6A00FF; background:rgba(106,0,255,.06); border:1px solid rgba(106,0,255,.14);
}
.tsm-title{
  font-family:var(--font-main),sans-serif;
  font-size:clamp(26px,3.2vw,38px); font-weight:800; letter-spacing:-.02em;
  color:#11162D; margin:0 0 12px; line-height:1.2;
}
.tsm-title span{
  background:linear-gradient(90deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  -webkit-background-clip:text; background-clip:text;
  -webkit-text-fill-color:transparent; color:transparent;
}
.tsm-sub{ font-size:15.5px; line-height:1.7; color:#5E6475; margin:0 0 18px; }
.tsm-score{
  display:inline-flex; align-items:center; gap:10px;
  padding:8px 16px; border-radius:999px;
  background:#fff; border:1px solid #E7E9F0;
  box-shadow:0 1px 2px rgba(17,22,45,.04);
}
.tsm-score-stars{ display:inline-flex; gap:2px; color:#FF8A00; font-size:12.5px; }
.tsm-score-txt{ font-size:13px; color:#5E6475; }
.tsm-score-txt strong{ color:#11162D; font-weight:700; }

/* Grid: 3 across, last row centres so an uneven count still reads balanced */
.tsm-grid{
  display:flex; flex-wrap:wrap; justify-content:center;
  gap:clamp(18px,2vw,26px);
}
.tsm-card{
  position:relative; flex:1 1 320px; max-width:380px;
  display:flex; flex-direction:column;
  padding:30px 28px 24px;
  background:#FFFFFF; border:1px solid #E7E9F0; border-radius:16px;
  box-shadow:0 1px 3px rgba(17,22,45,.05);
  transition:transform .22s cubic-bezier(.4,0,.2,1),
             box-shadow .22s cubic-bezier(.4,0,.2,1),
             border-color .22s cubic-bezier(.4,0,.2,1);
}
.tsm-card:hover{
  transform:translateY(-4px);
  border-color:rgba(106,0,255,.20);
  box-shadow:0 14px 34px rgba(17,22,45,.09);
}
/* Subtle quotation glyph, top-right */
.tsm-mark{
  position:absolute; top:14px; right:24px;
  font-family:Georgia,'Times New Roman',serif;
  font-size:76px; line-height:1; color:#11162D; opacity:.06;
  pointer-events:none; user-select:none;
}
.tsm-stars{ display:flex; gap:3px; color:#FF8A00; font-size:13px; margin-bottom:16px; }
.tsm-stars .far{ color:#D8DBE4; }
.tsm-quote{
  flex:1 1 auto; margin:0 0 22px; padding:0; border:0;
  font-size:14.8px; line-height:1.78; color:#5E6475;
}
.tsm-author{
  display:flex; align-items:center; gap:13px;
  padding-top:19px; border-top:1px solid #EFF0F5;
}
.tsm-avatar{
  flex:0 0 auto; display:grid; place-items:center; overflow:hidden;
  width:46px; height:46px; border-radius:50%;
  background:linear-gradient(135deg,#F50072,#6A00FF);
  color:#fff; font-size:14.5px; font-weight:700; letter-spacing:.02em;
}
.tsm-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.tsm-who{ display:flex; flex-direction:column; gap:2px; min-width:0; }
.tsm-who strong{
  font-family:var(--font-main),sans-serif;
  font-size:14.5px; font-weight:700; color:#11162D; letter-spacing:-.01em;
}
.tsm-who small{ font-size:12.5px; color:#6E7386; }

@media (max-width:1024px){ .tsm-card{ flex-basis:300px; } }
@media (max-width:680px){
  .tsm-grid{ gap:16px; }
  .tsm-card{ flex:1 1 100%; max-width:none; padding:26px 22px 20px; }
  .tsm-mark{ font-size:62px; top:10px; right:18px; }
}
</style>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  HERO — campaign banner carousel                     ║
     ║  Slides come from hero_slides; extra_data JSON picks ║
     ║  the device scene and background tint per slide.     ║
     ╚══════════════════════════════════════════════════════╝ -->
<?php
require_once __DIR__ . '/includes/hero-visuals.php';

/* Normalise each DB row into the shape the slide component expects. */
$_slides = [];
foreach ($heroSlides as $_row) {
    $_extra = json_decode((string)($_row['extra_data'] ?? ''), true) ?: [];
    $_url   = static function (?string $u, string $fallback): string {
        $u = trim((string)$u);
        if ($u === '') return $fallback;
        if (preg_match('~^(https?:|mailto:|tel:|\#)~i', $u)) return $u;
        return rtrim(SITE_URL, '/') . '/' . ltrim($u, '/');
    };
    $_slides[] = [
        'eyebrow' => trim((string)($_row['badge_text'] ?? '')),
        'lines'   => preg_split('/\\\\n|\r\n|\n/', (string)($_row['title'] ?? '')),
        'sub'     => trim((string)($_row['subtitle'] ?? '')),
        'visual'  => $_extra['visual'] ?? 'web',
        'image'   => trim((string)($_row['background_image'] ?? '')),
        'imgalt'  => trim((string)($_row['description'] ?? '')),
        'tint'    => $_extra['tint']   ?? 'blue',
        'b1t'     => trim((string)($_row['btn1_text'] ?? '')) ?: 'Get a Quote',
        'b1u'     => $_url($_row['btn1_url'] ?? '', SITE_URL . '/contact.php#enquiry'),
        'b2t'     => trim((string)($_row['btn2_text'] ?? '')),
        'b2u'     => $_url($_row['btn2_url'] ?? '', SITE_URL . '/services.php'),
    ];
}

/* {word} in a headline line renders in the brand gradient. */
$_heroLine = static function (string $line): string {
    $out = '';
    foreach (preg_split('/(\{[^}]*\})/', $line, -1, PREG_SPLIT_DELIM_CAPTURE) as $chunk) {
        if ($chunk === '') continue;
        if ($chunk[0] === '{') {
            $out .= '<span class="hb-grad">' . e(trim($chunk, '{}')) . '</span>';
        } else {
            $out .= e($chunk);
        }
    }
    return $out;
};

$_heroLogo = !empty($s['site_logo'])
    ? (str_starts_with($s['site_logo'], 'http') ? $s['site_logo'] : UPLOADS_URL . '/' . ltrim($s['site_logo'], '/'))
    : '';
?>
<?php if ($_slides): ?>
<section class="hb" id="hero" aria-roledescription="carousel" aria-label="Appsgain services">
  <div class="hb-track" id="hbTrack">
    <?php foreach ($_slides as $_i => $_sl): ?>
    <article class="hb-slide<?= $_i === 0 ? ' is-active' : '' ?>"
             data-tint="<?= e($_sl['tint']) ?>"
             role="group" aria-roledescription="slide"
             aria-label="<?= $_i + 1 ?> of <?= count($_slides) ?>"
             <?= $_i === 0 ? '' : 'aria-hidden="true"' ?>>
      <span class="hb-wash" aria-hidden="true"></span>
      <span class="hb-mesh" aria-hidden="true"></span>

      <div class="hb-inner">
        <div class="hb-copy">
          <?php if ($_sl['eyebrow']): ?>
          <span class="hb-eyebrow"><?= e($_sl['eyebrow']) ?></span>
          <?php endif; ?>

          <?php /* Only the first slide carries the page h1. All six slides live in
         the DOM at once, so six h1 tags were competing for the primary
         heading signal even though one is visible. */ ?>
          <<?= $_i === 0 ? 'h1' : 'h2' ?> class="hb-title">
            <?php foreach ($_sl['lines'] as $_ln): if (trim($_ln) === '') continue; ?>
            <span class="hb-line"><?= $_heroLine($_ln) ?></span>
            <?php endforeach; ?>
          </h1>

          <?php if ($_sl['sub']): ?>
          <p class="hb-sub"><?= e($_sl['sub']) ?></p>
          <?php endif; ?>

          <div class="hb-actions">
            <a href="<?= e($_sl['b1u']) ?>" class="hb-btn hb-btn-primary"><?= e($_sl['b1t']) ?></a>
            <?php if ($_sl['b2t']): ?>
            <a href="<?= e($_sl['b2u']) ?>" class="hb-btn hb-btn-ghost"><?= e($_sl['b2t']) ?></a>
            <?php endif; ?>
          </div>
        </div>

        <div class="hb-visual">
          <?php if ($_sl['image']): ?>
            <?php /* Admin-uploaded artwork wins over the built-in scene */ ?>
            <img class="hb-img"
                 src="<?= e(str_starts_with($_sl['image'], 'http') ? $_sl['image'] : UPLOADS_URL . '/' . ltrim($_sl['image'], '/')) ?>"
                 alt="<?= e($_sl['imgalt'] ?: $_sl['eyebrow']) ?>"
                 <?php /* Carousel slides are off-screen but rotate in within
                          seconds; lazy meant slide 2 was still downloading when
                          it became active, so mobile saw an empty panel. */ ?>
                 loading="eager" decoding="async"
                 <?= $_i === 0 ? 'fetchpriority="high"' : '' ?>>
          <?php else: ?>
            <span aria-hidden="true"><?php heroVisual($_sl['visual']); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?php if (count($_slides) > 1): ?>
  <div class="hb-ctrl">
    <button type="button" class="hb-arrow" id="hbPrev" aria-label="Previous slide" aria-controls="hbTrack">
      <i class="fas fa-arrow-left" aria-hidden="true"></i>
    </button>

  <div class="hb-dots" id="hbDots" role="tablist" aria-label="Choose slide">
    <?php foreach ($_slides as $_i => $_sl): ?>
    <button class="hb-dot<?= $_i === 0 ? ' is-on' : '' ?>" role="tab"
            aria-selected="<?= $_i === 0 ? 'true' : 'false' ?>"
            aria-label="<?= e($_sl['eyebrow'] ?: 'Slide ' . ($_i + 1)) ?>"></button>
    <?php endforeach; ?>
  </div>

    <button type="button" class="hb-arrow" id="hbNext" aria-label="Next slide" aria-controls="hbTrack">
      <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </button>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  3D TECHNOLOGY SLIDER                              ║
     ╚══════════════════════════════════════════════════════╝ -->
<style>
/* ── 3D Tech Slider ── */
@keyframes techSlide  { from { transform: translateX(0); }    to { transform: translateX(-50%); } }
@keyframes techSlide2 { from { transform: translateX(-50%); } to { transform: translateX(0); } }
@keyframes techFloat  {
  0%,100% { transform: translateY(0) rotateX(0deg); }
  50%     { transform: translateY(-6px) rotateX(4deg); }
}
@keyframes techGlow {
  0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,.3), 0 0 0 0 var(--tc, rgba(128,51,255,0)); }
  50%     { box-shadow: 0 8px 32px rgba(0,0,0,.4), 0 0 20px 3px var(--tc, rgba(128,51,255,.3)); }
}
@keyframes techShimmer {
  0%   { left: -80%; }
  100% { left: 150%; }
}

.tech3d-section {
  background: linear-gradient(180deg, #0a0616 0%, #0b1026 100%);
  padding: 52px 0 56px;
  overflow: hidden;
  position: relative;
}
.tech3d-section::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 80% 60% at 50% 100%,
    rgba(128,51,255,.1) 0%, transparent 65%);
  pointer-events: none;
}

/* Label */
.tech3d-label {
  text-align: center;
  font-size: 11px; font-weight: 700; letter-spacing: 3px;
  text-transform: uppercase; color: rgba(255,255,255,.3);
  margin-bottom: 32px;
  display: flex; align-items: center; justify-content: center; gap: 12px;
}
.tech3d-label::before, .tech3d-label::after {
  content: ''; flex: 1; max-width: 80px; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15));
}
.tech3d-label::after { transform: scaleX(-1); }

/* Perspective wrapper */
.tech3d-viewport {
  perspective: 1200px;
  perspective-origin: 50% 50%;
  overflow: hidden;
  -webkit-mask-image: linear-gradient(90deg,
    transparent 0%, #000 12%, #000 88%, transparent 100%);
  mask-image: linear-gradient(90deg,
    transparent 0%, #000 12%, #000 88%, transparent 100%);
}

/* Row containers */
.tech3d-row {
  display: flex; align-items: center;
  gap: 18px; margin-bottom: 18px;
  width: max-content;
}
.tech3d-row:nth-child(1) { animation: techSlide  38s linear infinite; }
.tech3d-row:nth-child(2) { animation: techSlide2 32s linear infinite; margin-bottom: 0; }

/* Pause on hover */
.tech3d-viewport:hover .tech3d-row { animation-play-state: paused; }

/* Individual tech card */
.tc3d {
  display: flex; align-items: center; gap: 11px;
  padding: 12px 18px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 14px;
  cursor: default;
  white-space: nowrap;
  position: relative; overflow: hidden;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
  transform-style: preserve-3d;
  --tc: rgba(128,51,255,.35);
}
/* Shimmer sweep */
.tc3d::before {
  content: '';
  position: absolute; top: 0; left: -80%;
  width: 50%; height: 100%;
  background: linear-gradient(90deg,
    transparent, rgba(255,255,255,.07), transparent);
  transform: skewX(-20deg);
  transition: none;
}
.tc3d:hover::before { animation: techShimmer .6s ease forwards; }

/* Float + glow on hover */
.tc3d:hover {
  background: rgba(255,255,255,.08);
  border-color: rgba(255,255,255,.2);
  transform: translateY(-6px) rotateX(6deg) scale(1.05);
  animation: techGlow .8s ease-in-out;
  box-shadow: 0 12px 36px rgba(0,0,0,.35),
              0 0 20px 3px var(--tc);
}

/* Icon circle */
.tc3d-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
}
.tc3d:hover .tc3d-icon {
  transform: scale(1.15) rotate(-8deg);
  background: var(--ic-bg, rgba(128,51,255,.2));
  border-color: var(--ic-border, rgba(128,51,255,.35));
  box-shadow: 0 0 14px var(--tc);
}

/* Text */
.tc3d-name {
  font-family: var(--font-main),sans-serif;
  font-size: 13px; font-weight: 600;
  color: rgba(255,255,255,.72);
  transition: color .25s;
}
.tc3d:hover .tc3d-name { color: #fff; }

/* Tech-specific accent colors via CSS var */
.tc3d.t-react   { --tc: rgba(97,218,251,.35);  --ic-bg: rgba(97,218,251,.1);  --ic-border: rgba(97,218,251,.3); }
.tc3d.t-node    { --tc: rgba(104,194,74,.35);  --ic-bg: rgba(104,194,74,.1);  --ic-border: rgba(104,194,74,.3); }
.tc3d.t-py      { --tc: rgba(55,118,171,.35);  --ic-bg: rgba(55,118,171,.1);  --ic-border: rgba(55,118,171,.3); }
.tc3d.t-aws     { --tc: rgba(255,153,0,.35);   --ic-bg: rgba(255,153,0,.1);   --ic-border: rgba(255,153,0,.3);  }
.tc3d.t-docker  { --tc: rgba(36,150,237,.35);  --ic-bg: rgba(36,150,237,.1);  --ic-border: rgba(36,150,237,.3); }
.tc3d.t-figma   { --tc: rgba(242,78,30,.35);   --ic-bg: rgba(242,78,30,.1);   --ic-border: rgba(242,78,30,.3);  }
.tc3d.t-db      { --tc: rgba(51,103,145,.35);  --ic-bg: rgba(51,103,145,.1);  --ic-border: rgba(51,103,145,.3); }
.tc3d.t-azure   { --tc: rgba(0,137,214,.35);   --ic-bg: rgba(0,137,214,.1);   --ic-border: rgba(0,137,214,.3);  }
.tc3d.t-gcp     { --tc: rgba(66,133,244,.35);  --ic-bg: rgba(66,133,244,.1);  --ic-border: rgba(66,133,244,.3); }
.tc3d.t-k8s     { --tc: rgba(50,108,229,.35);  --ic-bg: rgba(50,108,229,.1);  --ic-border: rgba(50,108,229,.3); }
.tc3d.t-ai      { --tc: rgba(106,0,255,.35);  --ic-bg: rgba(106,0,255,.1);  --ic-border: rgba(106,0,255,.3); }
.tc3d.t-vue     { --tc: rgba(79,192,141,.35);  --ic-bg: rgba(79,192,141,.1);  --ic-border: rgba(79,192,141,.3); }
.tc3d.t-laravel { --tc: rgba(248,72,54,.35);   --ic-bg: rgba(248,72,54,.1);   --ic-border: rgba(248,72,54,.3);  }
.tc3d.t-flutter { --tc: rgba(2,116,200,.35);   --ic-bg: rgba(2,116,200,.1);   --ic-border: rgba(2,116,200,.3);  }
.tc3d.t-tf      { --tc: rgba(255,111,0,.35);   --ic-bg: rgba(255,111,0,.1);   --ic-border: rgba(255,111,0,.3);  }
.tc3d.t-mongo   { --tc: rgba(71,162,72,.35);   --ic-bg: rgba(71,162,72,.1);   --ic-border: rgba(71,162,72,.3);  }
.tc3d.t-ts      { --tc: rgba(49,120,198,.35);  --ic-bg: rgba(49,120,198,.1);  --ic-border: rgba(49,120,198,.3); }
.tc3d.t-redis   { --tc: rgba(215,55,42,.35);   --ic-bg: rgba(215,55,42,.1);   --ic-border: rgba(215,55,42,.3);  }
.tc3d.t-next    { --tc: rgba(255,255,255,.25); --ic-bg: rgba(255,255,255,.06); --ic-border:rgba(255,255,255,.2); }
.tc3d.t-swift   { --tc: rgba(240,81,35,.35);   --ic-bg: rgba(240,81,35,.1);   --ic-border: rgba(240,81,35,.3);  }

@media (max-width: 600px) {
  .tc3d { padding: 10px 13px; }
  .tc3d-icon { width: 32px; height: 32px; font-size: 15px; border-radius: 8px; }
  .tc3d-name { font-size: 12px; }
}
</style>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  SERVICES — REDESIGNED PREMIUM SECTION             ║
     ╚══════════════════════════════════════════════════════╝ -->
<style>
/* ══ Services Section Redesign ══ */
@keyframes svGlow{0%,100%{box-shadow:0 0 0 0 var(--sc,rgba(106,0,255,.4))}50%{box-shadow:0 0 0 8px transparent}}
@keyframes svFloat{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-8px) rotate(4deg)}}
@keyframes svShimmer{0%{left:-100%}100%{left:200%}}
@keyframes svBorderFlow{to{background-position:-300% 0}}
@keyframes svPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}

.hs-services{padding:80px 0;background:linear-gradient(160deg,#fbfcfe 0%,#f7f9fc 50%,#f7f9fc 100%);}

/* ── Section header ── */
.hs-svc-header{text-align:center;margin-bottom:48px;}
.hs-svc-label{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,rgba(106,0,255,.12),rgba(85,0,204,.08));color:#6a00ff;border:1px solid rgba(106,0,255,.2);padding:6px 16px;border-radius:30px;font-size:11.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;}
.hs-svc-title{font-family:var(--font-main),sans-serif;font-size:clamp(26px,4vw,40px);font-weight:900;color:#0b1026;line-height:1.15;margin-bottom:12px;letter-spacing:-.5px;}
.hs-svc-title span{background:linear-gradient(135deg,#6a00ff,#8033ff,#6a00ff);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:svBorderFlow 4s linear infinite;}
.hs-svc-sub{font-size:16px;color:#5e6475;max-width:540px;margin:0 auto;line-height:1.75;}

/* ── Filter tabs ── */
.hs-filter-wrap{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-bottom:44px;}
.hs-filter-btn{padding:9px 22px;border-radius:30px;font-size:13px;font-weight:700;cursor:pointer;background:#fff;color:#5e6475;border:2px solid #e7e9f0;transition:all .25s;font-family:var(--font-main),sans-serif;}
.hs-filter-btn:hover{border-color:#6a00ff;color:#6a00ff;}
.hs-filter-btn.on{background:linear-gradient(135deg,#6a00ff,#8033ff);color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(106,0,255,.35);}

/* ══ CARDS GRID — Asymmetric Layout ══ */
.hs-svc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}

/* ── Base card ── */
.hs-svc-card{
  position:relative;border-radius:20px;padding:28px 24px;overflow:hidden;
  background:#fff;border:1.5px solid #e8ecf3;
  transition:all .35s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
  cursor:pointer;
  text-decoration:none;color:inherit;
}
/* FIX: ensure ::before overlay doesn't eat clicks */
.hs-svc-card::before{
  content:'';position:absolute;inset:0;border-radius:inherit;
  background:linear-gradient(135deg,var(--sc,rgba(106,0,255,.06)),transparent);
  opacity:0;transition:opacity .35s;
  pointer-events:none; /* ← KEY FIX */
  z-index:0;
}
.hs-svc-card:hover::before{opacity:1;}
.hs-svc-card:hover{
  border-color:var(--sc-raw,#6a00ff);
  transform:translateY(-6px);
  box-shadow:0 20px 48px var(--sc,rgba(106,0,255,.18));
}
/* All inner content above overlay */
.hs-svc-card>*{position:relative;z-index:1;}

/* Top gradient bar */
.hs-svc-bar{
  position:absolute;top:0;left:0;right:0;height:4px;border-radius:20px 20px 0 0;
  background:linear-gradient(90deg,var(--sc-raw,#6a00ff),var(--sc2,#8033ff));
  background-size:200% 100%;
  transform:scaleX(0);transform-origin:left;
  transition:transform .4s cubic-bezier(.4,0,.2,1);
  pointer-events:none;z-index:2;
}
.hs-svc-card:hover .hs-svc-bar{transform:scaleX(1);}

/* Icon */
.hs-svc-icon{
  width:60px;height:60px;border-radius:16px;margin-bottom:18px;
  display:flex;align-items:center;justify-content:center;font-size:24px;
  background:linear-gradient(135deg,var(--sc,rgba(106,0,255,.12)),var(--sc2,rgba(128,51,255,.08)));
  color:var(--sc-raw,#6a00ff);
  transition:transform .4s cubic-bezier(.34,1.56,.64,1),box-shadow .3s;
  position:relative;z-index:1;
}
.hs-svc-card:hover .hs-svc-icon{
  transform:translateY(-4px) scale(1.1) rotate(-5deg);
  box-shadow:0 8px 24px var(--sc,rgba(106,0,255,.3));
}

/* Category pill */
.hs-svc-cat{
  display:inline-flex;align-items:center;gap:5px;
  font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;
  color:var(--sc-raw,#6a00ff);margin-bottom:8px;
  background:var(--sc,rgba(106,0,255,.08));
  padding:3px 10px;border-radius:20px;width:fit-content;
}

/* Title */
.hs-svc-card h3{font-family:var(--font-main),sans-serif;font-size:16.5px;font-weight:800;color:#0b1026;margin-bottom:10px;line-height:1.3;}
.hs-svc-card:hover h3{color:var(--sc-raw,#6a00ff);}

/* Description */
.hs-svc-card p{font-size:13.5px;color:#5e6475;line-height:1.7;margin-bottom:20px;flex:1;}

/* ── LEARN MORE BUTTON — working, no pointer block ── */
.hs-learn-btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;
  background:linear-gradient(135deg,var(--sc-raw,#6a00ff),var(--sc2,#8033ff));
  color:#fff;text-decoration:none;border:none;cursor:pointer;
  transition:all .28s cubic-bezier(.34,1.56,.64,1);
  position:relative;overflow:hidden;
  align-self:flex-start;
  box-shadow:0 3px 12px var(--sc,rgba(106,0,255,.3));
  z-index:2; /* above ::before overlay */
}
.hs-learn-btn::after{
  content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);
  transform:skewX(-18deg);
}
.hs-learn-btn:hover::after{left:180%;transition:left .5s ease;}
.hs-learn-btn:hover{transform:translateY(-2px) scale(1.03);box-shadow:0 8px 24px var(--sc,rgba(106,0,255,.45));color:#fff;}
.hs-learn-btn i{font-size:11px;transition:transform .25s cubic-bezier(.34,1.56,.64,1);}
.hs-learn-btn:hover i{transform:translateX(4px);}

/* ══ FEATURED CARD (first card) ── */
.hs-svc-card.featured{
  grid-column:span 2;
  background:linear-gradient(135deg,#0b1026 0%,#1e1b4b 60%,#0b1026 100%);
  border-color:rgba(128,51,255,.3);
  color:#fff;
}
.hs-svc-card.featured::before{background:radial-gradient(ellipse at 30% 50%,rgba(128,51,255,.18),transparent 60%);}
.hs-svc-card.featured h3{color:#fff;font-size:22px;}
.hs-svc-card.featured p{color:rgba(255,255,255,.65);font-size:14.5px;}
.hs-svc-card.featured:hover{border-color:rgba(128,51,255,.6);box-shadow:0 24px 56px rgba(106,0,255,.3);}
.hs-svc-card.featured .hs-svc-icon{
  width:72px;height:72px;font-size:28px;
  background:linear-gradient(135deg,rgba(128,51,255,.25),rgba(128,51,255,.15));
  color:#e0d0ff;border:1px solid rgba(128,51,255,.25);
}
.hs-svc-card.featured .hs-svc-cat{background:rgba(128,51,255,.2);color:#e0d0ff;}
.hs-svc-card.featured .hs-learn-btn{
  background:linear-gradient(135deg,#6a00ff,#8033ff);
  box-shadow:0 4px 18px rgba(106,0,255,.5);
}
/* Decorative orb for featured */
.hs-svc-card.featured::after{
  content:'';position:absolute;right:-40px;top:-40px;
  width:180px;height:180px;border-radius:50%;
  background:radial-gradient(circle,rgba(128,51,255,.15),transparent 65%);
  pointer-events:none;
}

/* ── TALL CARD (3rd position) ── */
.hs-svc-card.tall{grid-row:span 2;}

/* ══ STAGGER animation ══ */
.hs-svc-card{opacity:0;transform:translateY(24px);}
.hs-svc-card.sv-in{opacity:1;transform:translateY(0);transition:opacity .55s ease,transform .55s ease;}

/* ══ RESPONSIVE ══ */
@media(max-width:1024px){.hs-svc-grid{grid-template-columns:repeat(2,1fr);}.hs-svc-card.featured{grid-column:span 2;}.hs-svc-card.tall{grid-row:span 1;}}
@media(max-width:640px){.hs-svc-grid{grid-template-columns:1fr;}.hs-svc-card.featured{grid-column:span 1;}.hs-svc-card.featured h3{font-size:18px;}}
</style>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  OUR SERVICES — 3D CARD SLIDER                       ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="svc3d" id="services">
  <div class="svc3d-glow" aria-hidden="true"></div>
  <div class="container">

    <!-- Header -->
    <div class="svc3d-head">
      <div class="svc3d-eyebrow"><i class="fas fa-layer-group"></i> <?= e($svcEyebrow) ?></div>
      <h2 class="svc3d-title"><?= e($svcTitleLead) ?> <span><?= e($svcTitleAccent) ?></span></h2>
      <p class="svc3d-sub"><?= e($svcSubtitle) ?></p>
    </div>

    <!-- Controls: category filter + slider arrows -->
    <div class="svc3d-bar">
      <div class="svc3d-filters" role="tablist" aria-label="Filter services by category">
        <button class="svc3d-chip on" data-hcat="all" role="tab" aria-selected="true">All</button>
        <?php foreach ($svcGroups as $g): ?>
        <button class="svc3d-chip" data-hcat="<?= e($g) ?>" role="tab" aria-selected="false"><?= e($g) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="svc3d-nav">
        <button class="svc3d-arrow" id="svc3dPrev" aria-label="Previous services" disabled>
          <i class="fas fa-arrow-left"></i>
        </button>
        <button class="svc3d-arrow" id="svc3dNext" aria-label="Next services">
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- Slider -->
    <div class="svc3d-viewport">
      <div class="svc3d-track" id="svc3dTrack" tabindex="0" role="region"
           aria-label="Services carousel — use arrow keys to scroll">
        <?php foreach ($svcCards as $i => $c): ?>
        <article class="svc3d-card" data-hcat="<?= e($c['group']) ?>"
                 style="--c1:<?= e($c['c1']) ?>;--c2:<?= e($c['c2']) ?>;--d:<?= $i * 55 ?>ms">
          <div class="svc3d-box">
            <span class="svc3d-face" aria-hidden="true"></span>
            <div class="svc3d-body">
              <div class="svc3d-icon">
                <i class="fas <?= e($c['icon']) ?>"></i>
                <span class="svc3d-icon-shine" aria-hidden="true"></span>
              </div>
              <span class="svc3d-tag"><?= e($c['group']) ?></span>
              <h3 class="svc3d-name"><?= e($c['name']) ?></h3>
              <p class="svc3d-desc"><?= e($c['desc']) ?></p>
              <a class="svc3d-link" href="<?= e($c['url']) ?>">
                <span>Learn more</span>
                <i class="fas fa-arrow-right"></i>
                <span class="svc3d-stretch"></span>
              </a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Progress + CTA -->
    <div class="svc3d-foot">
      <div class="svc3d-progress" aria-hidden="true"><span id="svc3dBar"></span></div>
      <div class="svc3d-cta-wrap">
        <a href="<?= SITE_URL ?>/services.php" class="svc3d-cta">
          <i class="fas fa-th-large"></i> Explore all services
          <i class="fas fa-arrow-right svc3d-cta-arrow"></i>
        </a>
        <p class="svc3d-note">
          <?= count($svcCards) ?> services · <?= e($metric_projects ?? '500') ?>+ projects delivered · Free consultation
        </p>
      </div>
    </div>

  </div>
</section>

<style>
/* ══ Our Services — 3D card slider ══════════════════════ */
.svc3d{
  position:relative; padding:var(--section-y,64px) 0; overflow:hidden;
  background:
    linear-gradient(180deg,#ffffff 0%, var(--ink-050,#f7f8fc) 46%, #ffffff 100%);
}
.svc3d-glow{
  position:absolute; inset:0; pointer-events:none;
  background:
    radial-gradient(760px 340px at 12% 6%,  color-mix(in srgb, var(--brand-accent) 12%, transparent), transparent 70%),
    radial-gradient(680px 320px at 88% 30%, color-mix(in srgb, var(--brand-primary) 13%, transparent), transparent 70%),
    radial-gradient(520px 260px at 50% 100%,color-mix(in srgb, var(--brand-secondary) 9%, transparent), transparent 72%);
}
.svc3d>.container{ position:relative; z-index:1; }

/* ── Header ── */
.svc3d-head{ max-width:660px; margin-bottom:34px; }
.svc3d-eyebrow{
  display:inline-flex; align-items:center; gap:8px;
  padding:7px 15px; margin-bottom:16px; border-radius:999px;
  font-size:11.5px; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
  color:var(--brand-primary);
  background:color-mix(in srgb, var(--brand-primary) 8%, #fff);
  border:1px solid color-mix(in srgb, var(--brand-primary) 20%, transparent);
}
.svc3d-title{
  font-family:var(--font-main),system-ui,sans-serif;
  font-size:clamp(28px,4.4vw,44px); font-weight:900; line-height:1.12;
  letter-spacing:-.02em; color:var(--ink-900,#0b1026); margin:0 0 14px;
}
.svc3d-title span{
  background:var(--brand-gradient); background-size:200% auto;
  -webkit-background-clip:text; background-clip:text;
  -webkit-text-fill-color:transparent; color:transparent;
  animation:agBrandFlow 8s linear infinite;
}
.svc3d-sub{ font-size:16px; line-height:1.75; color:var(--ink-500,#5e6475); margin:0; }

/* ── Control bar ── */
.svc3d-bar{
  display:flex; align-items:center; justify-content:space-between;
  gap:20px; flex-wrap:wrap; margin-bottom:26px;
}
.svc3d-filters{ display:flex; gap:8px; flex-wrap:wrap; }
.svc3d-chip{
  padding:9px 18px; border-radius:999px; cursor:pointer;
  font-size:13px; font-weight:700; font-family:inherit;
  color:var(--ink-600,#5e6475); background:#fff;
  border:1.5px solid var(--ink-200,#e7e9f0);
  transition:color .2s ease, border-color .2s ease, background .2s ease, transform .2s ease;
}
.svc3d-chip:hover{ border-color:color-mix(in srgb, var(--brand-primary) 45%, transparent); color:var(--brand-primary); }
.svc3d-chip.on{
  color:#fff; border-color:transparent;
  background:linear-gradient(120deg,var(--brand-secondary),var(--brand-primary));
  box-shadow:0 5px 16px color-mix(in srgb, var(--brand-primary) 30%, transparent);
}
.svc3d-nav{ display:flex; gap:9px; }
.svc3d-arrow{
  width:46px; height:46px; border-radius:50%; cursor:pointer;
  display:grid; place-items:center; font-size:14px;
  color:var(--ink-700,#242a42); background:#fff;
  border:1.5px solid var(--ink-200,#e7e9f0);
  box-shadow:0 3px 10px rgba(11,15,26,.06);
  transition:transform .2s ease, box-shadow .2s ease, color .2s ease, border-color .2s ease, opacity .2s ease;
}
.svc3d-arrow:hover:not(:disabled){
  color:#fff; border-color:transparent; transform:translateY(-2px);
  background:linear-gradient(120deg,var(--brand-secondary),var(--brand-primary));
  box-shadow:0 8px 20px color-mix(in srgb, var(--brand-primary) 34%, transparent);
}
.svc3d-arrow:disabled{ opacity:.35; cursor:default; }

/* ── Slider viewport ── */
.svc3d-viewport{ position:relative; margin:0 -14px; padding:0 14px; }
.svc3d-viewport::before,
.svc3d-viewport::after{
  content:""; position:absolute; top:0; bottom:22px; width:56px; z-index:3; pointer-events:none;
}
.svc3d-viewport::before{ left:0;  background:linear-gradient(90deg,var(--ink-050,#f7f8fc),transparent); }
.svc3d-viewport::after { right:0; background:linear-gradient(270deg,var(--ink-050,#f7f8fc),transparent); }

.svc3d-track{
  display:flex; gap:26px;
  padding:34px 6px 30px;
  overflow-x:auto; overflow-y:hidden;
  scroll-snap-type:x mandatory;
  scroll-behavior:smooth;
  perspective:1400px;
  scrollbar-width:none;
  -webkit-overflow-scrolling:touch;
}
.svc3d-track::-webkit-scrollbar{ display:none; }
.svc3d-track:focus-visible{ outline:3px solid color-mix(in srgb, var(--brand-primary) 55%, transparent); outline-offset:4px; border-radius:20px; }

/* ── The 3D box ── */
.svc3d-card{
  flex:0 0 clamp(268px, 31%, 344px);
  scroll-snap-align:start;
  transition:opacity .55s ease var(--d,0ms), transform .55s cubic-bezier(.22,1,.36,1) var(--d,0ms);
}
/* Entrance runs straight off a CSS animation with a per-card delay, so it
   never depends on JS or an IntersectionObserver firing. Cards always end
   up visible - animation-fill-mode:both holds the final frame. */
.svc3d-card{ animation:svc3dIn .6s cubic-bezier(.22,1,.36,1) var(--d,0ms) both; }
@keyframes svc3dIn{ from{ opacity:0; transform:translateY(26px); } to{ opacity:1; transform:none; } }

.svc3d-box{
  position:relative; height:100%;
  transform-style:preserve-3d;
  transition:transform .45s cubic-bezier(.22,1,.36,1);
}
.svc3d-card:hover .svc3d-box,
.svc3d-card:focus-within .svc3d-box{
  transform:rotateX(4deg) rotateY(-7deg) translateY(-10px);
}

/* The extruded side/bottom face that gives the card real thickness */
.svc3d-face{
  position:absolute; inset:0; border-radius:22px; z-index:0;
  transform:translate3d(13px,13px,-26px);
  background:linear-gradient(135deg,var(--c1),var(--c2));
  opacity:.34; filter:blur(.4px);
  transition:transform .45s cubic-bezier(.22,1,.36,1), opacity .45s ease;
}
.svc3d-card:hover .svc3d-face,
.svc3d-card:focus-within .svc3d-face{ transform:translate3d(20px,20px,-38px); opacity:.5; }

.svc3d-body{
  position:relative; z-index:1; height:100%;
  display:flex; flex-direction:column;
  padding:28px 26px 26px; border-radius:22px;
  background:#fff;
  border:1px solid var(--ink-200,#e7e9f0);
  box-shadow:0 2px 4px rgba(11,15,26,.04), 0 14px 34px rgba(11,15,26,.07);
  transition:box-shadow .45s ease, border-color .45s ease;
}
.svc3d-card:hover .svc3d-body,
.svc3d-card:focus-within .svc3d-body{
  border-color:color-mix(in srgb, var(--c2) 42%, transparent);
  box-shadow:0 4px 8px rgba(11,15,26,.05), 0 26px 56px color-mix(in srgb, var(--c2) 26%, transparent);
}
/* Gradient hairline along the top edge */
.svc3d-body::before{
  content:""; position:absolute; inset:0 0 auto 0; height:4px;
  border-radius:22px 22px 0 0;
  background:linear-gradient(90deg,var(--c1),var(--c2));
  opacity:.9;
}

/* ── Icon tile, treated as a small 3D block ── */
.svc3d-icon{
  position:relative; width:60px; height:60px; border-radius:17px;
  display:grid; place-items:center; margin-bottom:20px; overflow:hidden;
  font-size:24px; color:#fff;
  background:linear-gradient(135deg,var(--c1),var(--c2));
  box-shadow:
    0 8px 18px color-mix(in srgb, var(--c2) 36%, transparent),
    inset 0 1px 0 rgba(255,255,255,.45),
    inset 0 -2px 6px rgba(0,0,0,.16);
  transform:translateZ(28px);
  transition:transform .45s cubic-bezier(.22,1,.36,1);
}
.svc3d-card:hover .svc3d-icon{ transform:translateZ(46px) rotate(-4deg); }
.svc3d-icon-shine{
  position:absolute; inset:0;
  background:linear-gradient(150deg,rgba(255,255,255,.5) 0%,rgba(255,255,255,0) 46%);
}

.svc3d-tag{
  display:inline-block; align-self:flex-start;
  padding:5px 11px; margin-bottom:12px; border-radius:999px;
  font-size:10.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
  color:var(--c2); background:color-mix(in srgb, var(--c2) 10%, #fff);
  transform:translateZ(16px);
}
.svc3d-name{
  font-family:var(--font-main),system-ui,sans-serif;
  font-size:17.5px; font-weight:800; line-height:1.32;
  color:var(--ink-900,#0b1026); margin:0 0 10px;
  transform:translateZ(20px);
}
.svc3d-desc{
  font-size:13.8px; line-height:1.72; color:var(--ink-500,#5e6475);
  margin:0 0 20px; flex:1;
  transform:translateZ(12px);
}
.svc3d-link{
  display:inline-flex; align-items:center; gap:8px; align-self:flex-start;
  font-size:13.5px; font-weight:800; text-decoration:none; color:var(--c2);
  transform:translateZ(18px);
}
.svc3d-link i{ font-size:11px; transition:transform .25s ease; }
.svc3d-card:hover .svc3d-link i{ transform:translateX(5px); }
/* Makes the whole card clickable without nesting interactive elements */
.svc3d-stretch{ position:absolute; inset:0; border-radius:22px; }

/* ── Footer: progress + CTA ── */
.svc3d-foot{ margin-top:30px; }
.svc3d-progress{
  height:4px; border-radius:999px; overflow:hidden;
  background:var(--ink-200,#e7e9f0); max-width:220px; margin-bottom:34px;
}
.svc3d-progress span{
  display:block; height:100%; width:24%; border-radius:999px;
  background:var(--brand-gradient);
  transition:width .25s ease, transform .25s ease;
}
.svc3d-cta-wrap{ text-align:center; }
.svc3d-cta{
  display:inline-flex; align-items:center; gap:10px;
  padding:15px 34px; border-radius:999px;
  font-size:15px; font-weight:800; text-decoration:none; color:#fff;
  font-family:var(--font-main),system-ui,sans-serif;
  background:linear-gradient(120deg,var(--brand-secondary),#6a00ff,var(--brand-primary));
  background-size:180% auto;
  box-shadow:0 8px 26px color-mix(in srgb, var(--brand-primary) 34%, transparent);
  transition:background-position .5s ease, transform .22s ease, box-shadow .22s ease;
}
.svc3d-cta:hover{
  background-position:100% 50%; transform:translateY(-3px); color:#fff;
  box-shadow:0 14px 36px color-mix(in srgb, var(--brand-primary) 44%, transparent);
}
.svc3d-cta-arrow{ font-size:12px; }
.svc3d-note{ font-size:13px; color:var(--ink-400,#8b90a0); margin:14px 0 0; }

/* ── Responsive ── */
@media (max-width:900px){
  .svc3d{ padding:70px 0 76px; }
  .svc3d-card{ flex-basis:clamp(250px,72vw,300px); }
  .svc3d-nav{ display:none; }          /* touch users swipe instead */
  .svc3d-viewport::before,
  .svc3d-viewport::after{ display:none; }
}
@media (max-width:560px){
  .svc3d-track{ gap:18px; padding:26px 4px 24px; }
  .svc3d-body{ padding:24px 22px 22px; }
  .svc3d-progress{ max-width:none; }
}

/* Flat, still-legible fallback when motion is reduced */
@media (prefers-reduced-motion:reduce){
  .svc3d-card{ animation:none; opacity:1; transform:none; }
  .svc3d-card:hover .svc3d-box,
  .svc3d-card:focus-within .svc3d-box{ transform:translateY(-4px); }
  .svc3d-icon, .svc3d-tag, .svc3d-name, .svc3d-desc, .svc3d-link{ transform:none; }
  .svc3d-track{ scroll-behavior:auto; }
}
</style>

<script>
(function () {
  var track = document.getElementById('svc3dTrack');
  if (!track) return;
  var prev = document.getElementById('svc3dPrev');
  var next = document.getElementById('svc3dNext');
  var bar  = document.getElementById('svc3dBar');

  function visibleCards() {
    return Array.prototype.filter.call(track.children, function (c) {
      return c.style.display !== 'none';
    });
  }
  function step() {
    var card = visibleCards()[0];
    if (!card) return track.clientWidth;
    var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 26;
    return card.getBoundingClientRect().width + gap;
  }
  function sync() {
    var max = track.scrollWidth - track.clientWidth;
    var pct = max > 4 ? track.scrollLeft / max : 0;
    if (prev) prev.disabled = track.scrollLeft <= 4;
    if (next) next.disabled = track.scrollLeft >= max - 4;
    if (bar) {
      var ratio = max > 4 ? Math.max(0.14, track.clientWidth / track.scrollWidth) : 1;
      bar.style.width = (ratio * 100) + '%';
      bar.style.transform = 'translateX(' + (pct * (100 / ratio - 100)) + '%)';
    }
  }
  if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
  if (next) next.addEventListener('click', function () { track.scrollBy({ left:  step(), behavior: 'smooth' }); });
  track.addEventListener('scroll', sync, { passive: true });
  window.addEventListener('resize', sync);

  /* Keyboard support on the focused track */
  track.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { e.preventDefault(); track.scrollBy({ left:  step(), behavior: 'smooth' }); }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); track.scrollBy({ left: -step(), behavior: 'smooth' }); }
  });

  /* Drag to scroll on pointer devices */
  var down = false, startX = 0, startLeft = 0, moved = false;
  track.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'touch') return;          /* native touch scrolling is better */
    down = true; moved = false;
    startX = e.clientX; startLeft = track.scrollLeft;
    track.style.scrollBehavior = 'auto';
  });
  window.addEventListener('pointermove', function (e) {
    if (!down) return;
    var dx = e.clientX - startX;
    if (Math.abs(dx) > 4) moved = true;
    track.scrollLeft = startLeft - dx;
  });
  window.addEventListener('pointerup', function () {
    if (!down) return;
    down = false;
    track.style.scrollBehavior = '';
    if (moved) {
      /* swallow the click that ends a drag */
      track.addEventListener('click', function once(ev) { ev.preventDefault(); ev.stopPropagation(); }, { capture: true, once: true });
    }
  });

  /* Category filter */
  document.querySelectorAll('.svc3d-chip').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.svc3d-chip').forEach(function (b) {
        b.classList.remove('on');
        b.setAttribute('aria-selected', 'false');
      });
      this.classList.add('on');
      this.setAttribute('aria-selected', 'true');
      var cat = this.dataset.hcat;
      Array.prototype.forEach.call(track.children, function (card) {
        var match = cat === 'all' || card.dataset.hcat === cat;
        card.style.display = match ? '' : 'none';
      });
      track.scrollTo({ left: 0, behavior: 'smooth' });
      sync();
    });
  });

  sync();
})();
</script>
<!-- ╔══════════════════════════════════════════════════════╗
     ║  STATS — DARK GLOWING COUNTERS                      ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-stats">
  <div class="container">
    <div class="saas-stats-grid">
      <div class="saas-stat-card saas-reveal" style="--stat-color:#c4a5ff">
        <div class="saas-stat-icon" style="color:#c4a5ff"><i class="fas fa-rocket"></i></div>
        <div class="saas-stat-num">
          <span class="saas-counter" data-target="<?= e($metric_projects) ?>" data-suffix="+">0+</span>
        </div>
        <div class="saas-stat-label">Projects Delivered</div>
      </div>
      <div class="saas-stat-card saas-reveal saas-delay-1" style="--stat-color:#c4a5ff">
        <div class="saas-stat-icon" style="color:#c4a5ff"><i class="fas fa-smile"></i></div>
        <div class="saas-stat-num">
          <span class="saas-counter" data-target="<?= e($metric_clients) ?>" data-suffix="+">0+</span>
        </div>
        <div class="saas-stat-label">Happy Clients</div>
      </div>
      <div class="saas-stat-card saas-reveal saas-delay-2" style="--stat-color:#34d399">
        <div class="saas-stat-icon" style="color:#34d399"><i class="fas fa-calendar-check"></i></div>
        <div class="saas-stat-num">
          <span class="saas-counter" data-target="<?= e($metric_years) ?>" data-suffix="+">0+</span>
        </div>
        <div class="saas-stat-label">Years Experience</div>
      </div>
      <div class="saas-stat-card saas-reveal saas-delay-3" style="--stat-color:#8033ff">
        <div class="saas-stat-icon" style="color:#8033ff"><i class="fas fa-users"></i></div>
        <div class="saas-stat-num">
          <span class="saas-counter" data-target="<?= e($metric_experts) ?>" data-suffix="+">0+</span>
        </div>
        <div class="saas-stat-label">Tech Experts</div>
      </div>
      <div class="saas-stat-card saas-reveal saas-delay-4" style="--stat-color:#8033ff">
        <div class="saas-stat-icon" style="color:#8033ff"><i class="fas fa-star"></i></div>
        <div class="saas-stat-num"><span class="saas-counter" data-target="<?= e($metric_retention) ?>" data-suffix="%">0%</span></div>
        <div class="saas-stat-label">Client Retention</div>
      </div>
    </div>
  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  PROCESS — DARK GLASS TIMELINE                      ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-process">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:34px">
      <span class="saas-label blue"><i class="fas fa-route"></i> Our Approach</span>
      <h2 class="saas-heading light-text">How We <span class="grad">Work</span></h2>
      <p class="saas-section-sub light-sub">
        A proven, agile process that keeps you in control while we build fast, iterate quickly and deliver exceptional results.
      </p>
    </div>
    <div class="saas-process-grid">
      <div class="saas-process-step saas-reveal saas-delay-1">
        <div class="saas-step-num">01</div>
        <h4>Discovery &amp; Planning</h4>
        <p>We deep-dive into your goals, audience and technical requirements to create a solid roadmap and detailed scope of work.</p>
      </div>
      <div class="saas-process-step saas-reveal saas-delay-2">
        <div class="saas-step-num">02</div>
        <h4>Design &amp; Prototype</h4>
        <p>Our designers craft high-fidelity wireframes and interactive prototypes, validated with real user feedback before a single line of code is written.</p>
      </div>
      <div class="saas-process-step saas-reveal saas-delay-3">
        <div class="saas-step-num">03</div>
        <h4>Agile Development</h4>
        <p>2-week sprints with full transparency — live demos, daily updates and continuous integration keep delivery smooth and predictable.</p>
      </div>
      <div class="saas-process-step saas-reveal saas-delay-4">
        <div class="saas-step-num">04</div>
        <h4>Launch &amp; Support</h4>
        <p>Rigorous QA, smooth deployment and dedicated post-launch support ensure your product stays live, fast and evolving with your needs.</p>
      </div>
    </div>
  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  WHY CHOOSE US — DYNAMIC COLORFUL PREMIUM           ║
     ╚══════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════
   WHY SECTION — Full Redesign
═══════════════════════════════════════════════════ */
@keyframes whyFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
@keyframes whyGlow   { 0%,100%{opacity:.6} 50%{opacity:1} }
@keyframes whyCount  { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
@keyframes whyBadge  { 0%{transform:scale(0) rotate(-20deg)} 70%{transform:scale(1.12) rotate(3deg)} 100%{transform:scale(1) rotate(0)} }
@keyframes whyShimmer{ 0%{left:-80%} 100%{left:150%} }

.why-section {
  position: relative;
  padding: 96px 0 100px;
  overflow: hidden;
  background:
    radial-gradient(ellipse 60% 50% at 10% 20%, rgba(128,51,255,.08) 0%, transparent 55%),
    radial-gradient(ellipse 50% 45% at 90% 80%, rgba(106,0,255,.07) 0%, transparent 55%),
    radial-gradient(ellipse 70% 60% at 50% 50%, rgba(106,0,255,.04) 0%, transparent 60%),
    linear-gradient(175deg, #fff 0%, #f7f9fc 50%, #fdf4ff 100%);
}
/* Decorative dots grid */
.why-section::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(128,51,255,.12) 1px, transparent 1px);
  background-size: 40px 40px;
  opacity: .35;
  pointer-events: none;
}

/* ── Section header ── */
.why-header {
  text-align: center; margin-bottom: 60px;
  position: relative; z-index: 2;
}
.why-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 16px; border-radius: 30px;
  font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  background: linear-gradient(135deg, rgba(128,51,255,.1), rgba(128,51,255,.07));
  border: 1px solid rgba(128,51,255,.2);
  color: #8033ff; margin-bottom: 18px;
}
.why-title {
  font-family: var(--font-main),sans-serif;
  font-size: clamp(28px,4vw,46px); font-weight: 900;
  color: #0b1026; letter-spacing: -1.2px; margin-bottom: 14px;
  line-height: 1.1;
}
.why-title .wt {
  background: linear-gradient(90deg, #8033ff, #8033ff, #6a00ff, #6a00ff);
  background-size: 300% auto;
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text; animation: colorCycle 5s ease infinite;
}
.why-sub {
  font-size: 15.5px; color: #5e6475; max-width: 540px;
  margin: 0 auto; line-height: 1.75;
}

/* ── Hero highlight strip (top 3 big stats) ── */
.why-highlights {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 20px; margin-bottom: 40px;
  position: relative; z-index: 2;
}
.why-hl {
  border-radius: 20px; padding: 28px 26px;
  position: relative; overflow: hidden;
  transition: transform .35s cubic-bezier(.34,1.56,.64,1), box-shadow .35s ease;
}
.why-hl::before {
  content: '';
  position: absolute; top: 0; left: -80%; width: 45%; height: 100%;
  background: linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
  transform: skewX(-18deg);
}
.why-hl:hover::before { animation: whyShimmer .6s ease forwards; }
.why-hl:hover { transform: translateY(-5px); }
.why-hl-num {
  font-family: var(--font-main),sans-serif; font-size: 44px; font-weight: 900;
  line-height: 1; margin-bottom: 6px; color: #fff;
}
.why-hl-label {
  font-size: 14px; font-weight: 700; color: rgba(255,255,255,.85);
  margin-bottom: 4px;
}
.why-hl-sub { font-size: 12.5px; color: rgba(255,255,255,.65); line-height: 1.5; }
.why-hl-icon {
  position: absolute; right: 20px; top: 20px;
  font-size: 42px; opacity: .2;
}
/* Highlight color variants */
.why-hl-1 {
  background: linear-gradient(135deg, #6a00ff, #6a00ff);
  box-shadow: 0 12px 40px rgba(106,0,255,.35);
}
.why-hl-1:hover { box-shadow: 0 20px 50px rgba(106,0,255,.45); }
.why-hl-2 {
  background: linear-gradient(135deg, #46009f, #6a00ff);
  box-shadow: 0 12px 40px rgba(70,0,159,.35);
}
.why-hl-2:hover { box-shadow: 0 20px 50px rgba(70,0,159,.45); }
.why-hl-3 {
  background: linear-gradient(135deg, #059669, #10b981);
  box-shadow: 0 12px 40px rgba(5,150,105,.35);
}
.why-hl-3:hover { box-shadow: 0 20px 50px rgba(5,150,105,.45); }

/* ── Advantage cards grid ── */
.why-adv-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 20px; margin-bottom: 40px;
  position: relative; z-index: 2;
}
.why-adv {
  background: #fff;
  border: 1.5px solid #e8ecf3;
  border-radius: 18px; padding: 26px 24px;
  position: relative; overflow: hidden;
  transition: all .35s cubic-bezier(.4,0,.2,1);
  box-shadow: 0 4px 18px rgba(17,22,45,.05);
  cursor: default;
}
.why-adv::before {
  content: '';
  position: absolute; inset: 0; border-radius: 18px;
  background: linear-gradient(135deg, var(--wa-grad-a,rgba(128,51,255,.05)), transparent);
  opacity: 0; transition: opacity .35s;
}
.why-adv:hover::before { opacity: 1; }
/* Top accent bar */
.why-adv::after {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  border-radius: 18px 18px 0 0;
  background: var(--wa-accent, linear-gradient(90deg,#8033ff,#8033ff));
  transform: scaleX(0); transform-origin: left;
  transition: transform .4s cubic-bezier(.4,0,.2,1);
}
.why-adv:hover::after { transform: scaleX(1); }
.why-adv:hover {
  border-color: rgba(255,255,255,.05);
  transform: translateY(-6px);
  box-shadow: 0 24px 56px rgba(17,22,45,.1);
}

/* Icon wrapper */
.why-adv-icon {
  width: 54px; height: 54px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; margin-bottom: 18px;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
  position: relative; z-index: 1;
}
.why-adv:hover .why-adv-icon {
  transform: scale(1.12) rotate(-5deg);
  box-shadow: 0 6px 20px var(--wa-glow, rgba(128,51,255,.3));
}
/* Number badge */
.why-adv-num {
  position: absolute; top: 20px; right: 22px;
  font-family: var(--font-main),sans-serif; font-size: 38px; font-weight: 900;
  color: rgba(17,22,45,.05); line-height: 1; pointer-events: none;
  transition: color .35s;
}
.why-adv:hover .why-adv-num { color: var(--wa-num-color, rgba(128,51,255,.08)); }

.why-adv-title {
  font-family: var(--font-main),sans-serif; font-size: 17px; font-weight: 800;
  color: #0b1026; margin-bottom: 10px; line-height: 1.2;
  position: relative; z-index: 1; transition: color .25s;
}
.why-adv:hover .why-adv-title { color: var(--wa-color, #8033ff); }

.why-adv-desc {
  font-size: 13.5px; color: #5e6475; line-height: 1.7;
  position: relative; z-index: 1;
}

/* ── Trust bar ── */
.why-trust {
  display: flex; align-items: center; justify-content: center;
  flex-wrap: wrap; gap: 10px;
  padding: 24px 28px; border-radius: 16px;
  background: linear-gradient(135deg, rgba(128,51,255,.06), rgba(128,51,255,.04));
  border: 1px solid rgba(128,51,255,.12);
  position: relative; z-index: 2;
}
.why-trust-item {
  display: flex; align-items: center; gap: 7px;
  padding: 7px 14px; border-radius: 30px; font-size: 13px; font-weight: 600;
  color: #475569; background: #fff;
  border: 1px solid #e7e9f0;
  transition: all .25s;
}
.why-trust-item:hover {
  border-color: rgba(128,51,255,.3);
  color: #8033ff; transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(128,51,255,.12);
}
.why-trust-item i { font-size: 14px; }

/* ── Responsive ── */
@media (max-width: 1000px) {
  .why-highlights { grid-template-columns: repeat(2,1fr); }
  .why-adv-grid   { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 640px) {
  .why-highlights { grid-template-columns: 1fr; }
  .why-adv-grid   { grid-template-columns: 1fr; }
  .why-trust      { flex-direction: column; align-items: flex-start; gap: 8px; }
}
</style>

<?php
/* ── Load why items from settings (DB) or use defaults ── */
$_whyTitle    = getSetting('homepage_why_title',    'Why Businesses Choose Us');
$_whySub      = getSetting('homepage_why_subtitle', "We're not just a vendor — we're a technology partner committed to your long-term success.");
$_whyRaw      = getSetting('homepage_why_items', '');
$_whyItems    = [];
if ($_whyRaw) {
    $decoded = json_decode($_whyRaw, true);
    if (is_array($decoded)) $_whyItems = $decoded;
}
if (empty($_whyItems)) {
    $_whyItems = [
        ['icon'=>'fa-rocket',            'color'=>'#6a00ff','title'=>'Fast Time-to-Market',   'desc'=>'Agile sprints and pre-built components deliver your MVP in weeks, not months.'],
        ['icon'=>'fa-expand-arrows-alt', 'color'=>'#8033ff','title'=>'Scalable Architecture',  'desc'=>'Cloud-native architecture that grows from 100 to 10 million users seamlessly.'],
        ['icon'=>'fa-shield-alt',        'color'=>'#10b981','title'=>'Security First',          'desc'=>'OWASP-compliant code, NDA protection, regular audits and penetration testing.'],
        ['icon'=>'fa-headset',           'color'=>'#6a00ff','title'=>'Dedicated Support',      'desc'=>'Dedicated project manager, daily stand-ups, and 24/7 post-launch support.'],
        ['icon'=>'fa-tags',              'color'=>'#46009f','title'=>'Transparent Pricing',    'desc'=>'Fixed-price or time-and-materials — no hidden costs, no billing surprises.'],
        ['icon'=>'fa-award',             'color'=>'#6a00ff','title'=>'Proven Track Record',    'desc'=>'200+ successful projects across startups, SMBs and enterprises worldwide.'],
    ];
}
/* Color→gradient mapping */
$_whyAccents = [
    '#6a00ff' => ['linear-gradient(90deg,#6a00ff,#8033ff)', 'rgba(106,0,255,.08)',  'rgba(106,0,255,.2)',  'rgba(106,0,255,.08)'],
    '#8033ff' => ['linear-gradient(90deg,#8033ff,#6a00ff)', 'rgba(128,51,255,.08)', 'rgba(128,51,255,.2)', 'rgba(128,51,255,.08)'],
    '#10b981' => ['linear-gradient(90deg,#10b981,#6a00ff)', 'rgba(16,185,129,.08)', 'rgba(16,185,129,.2)', 'rgba(16,185,129,.08)'],
    '#6a00ff' => ['linear-gradient(90deg,#6a00ff,#f06a00)', 'rgba(106,0,255,.08)', 'rgba(106,0,255,.2)', 'rgba(106,0,255,.08)'],
    '#46009f' => ['linear-gradient(90deg,#46009f,#6a00ff)', 'rgba(70,0,159,.08)',  'rgba(70,0,159,.2)',  'rgba(70,0,159,.08)'],
    '#6a00ff' => ['linear-gradient(90deg,#6a00ff,#6a00ff)', 'rgba(106,0,255,.08)',  'rgba(106,0,255,.2)',  'rgba(106,0,255,.08)'],
    '#8033ff' => ['linear-gradient(90deg,#8033ff,#8033ff)', 'rgba(128,51,255,.08)', 'rgba(128,51,255,.2)', 'rgba(128,51,255,.08)'],
];
function getWhyAccent(string $color, array $map): array {
    return $map[$color] ?? ['linear-gradient(90deg,#8033ff,#8033ff)','rgba(128,51,255,.08)','rgba(128,51,255,.2)','rgba(128,51,255,.08)'];
}
?>

<section class="why-section" id="why">
  <div class="container">

    <!-- Header -->
    <div class="why-header saas-reveal">
      <div class="why-eyebrow">
        <i class="fas fa-trophy"></i> Why Appsgain?
      </div>
      <h2 class="why-title">
        <?php
        $titleParts = explode(' ', trim($_whyTitle), -0);
        $half = ceil(count($titleParts) / 2);
        $first = implode(' ', array_slice($titleParts, 0, $half));
        $rest  = implode(' ', array_slice($titleParts, $half));
        echo e($first) . ' <span class="wt">' . e($rest) . '</span>';
        ?>
      </h2>
      <p class="why-sub"><?= e($_whySub) ?></p>
    </div>

    <!-- Highlight stat strip -->
    <div class="why-highlights">
      <div class="why-hl why-hl-1 saas-reveal saas-delay-1">
        <div class="why-hl-icon"><i class="fas fa-rocket"></i></div>
        <div class="why-hl-num"><?= e($metric_projects) ?>+</div>
        <div class="why-hl-label">Projects Delivered</div>
        <div class="why-hl-sub">Across 20+ industries globally</div>
      </div>
      <div class="why-hl why-hl-2 saas-reveal saas-delay-2">
        <div class="why-hl-icon"><i class="fas fa-users"></i></div>
        <div class="why-hl-num"><?= e($metric_clients) ?>+</div>
        <div class="why-hl-label">Happy Clients</div>
        <div class="why-hl-sub">Startups to Fortune 500s</div>
      </div>
      <div class="why-hl why-hl-3 saas-reveal saas-delay-3">
        <div class="why-hl-icon"><i class="fas fa-star"></i></div>
        <div class="why-hl-num"><?= e($metric_retention) ?>%</div>
        <div class="why-hl-label">Client Retention Rate</div>
        <div class="why-hl-sub">Long-term technology partners</div>
      </div>
    </div>

    <!-- Advantage cards grid (dynamic from settings) -->
    <div class="why-adv-grid">
      <?php foreach ($_whyItems as $wi => $w):
        $col    = $w['color'] ?? '#8033ff';
        $acc    = getWhyAccent($col, $_whyAccents);
        $bg     = $acc[1]; $glow = $acc[2]; $numClr = $acc[3];
        $accent = $acc[0];
        $icon   = $w['icon'] ?? 'fa-check';
        $title  = $w['title'] ?? '';
        $desc   = $w['desc'] ?? '';
      ?>
      <div class="why-adv saas-reveal saas-delay-<?= ($wi % 3) + 1 ?>"
           style="--wa-color:<?= $col ?>;--wa-accent:<?= $accent ?>;--wa-glow:<?= $glow ?>;--wa-grad-a:<?= $bg ?>;--wa-num-color:<?= $numClr ?>"
           data-tilt data-tilt-strength="7">
        <div class="why-adv-num"><?= str_pad($wi + 1, 2, '0', STR_PAD_LEFT) ?></div>
        <div class="why-adv-icon" style="background:<?= $bg ?>;color:<?= $col ?>">
          <i class="fas <?= e($icon) ?>"></i>
        </div>
        <div class="why-adv-title"><?= e($title) ?></div>
        <div class="why-adv-desc"><?= e($desc) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Trust badges -->
    <div class="why-trust saas-reveal">
      <span class="why-trust-item"><i class="fas fa-certificate" style="color:#6a00ff"></i> ISO 9001 Certified</span>
      <span class="why-trust-item"><i class="fas fa-lock" style="color:#10b981"></i> NDA Protected</span>
      <span class="why-trust-item"><i class="fas fa-star" style="color:#6a00ff"></i> 4.9★ Average Rating</span>
      <span class="why-trust-item"><i class="fas fa-clock" style="color:#46009f"></i> 2-Hour Response</span>
      <span class="why-trust-item"><i class="fas fa-headset" style="color:#8033ff"></i> 24/7 Support</span>
      <span class="why-trust-item"><i class="fas fa-globe" style="color:#6a00ff"></i> 20+ Countries</span>
      <span class="why-trust-item"><i class="fas fa-shield-check" style="color:#059669"></i> MSME Registered</span>
    </div>

  </div>
</section>



<!-- ╔══════════════════════════════════════════════════════╗
     ║  FEATURED PROJECTS — 3D CARDS                       ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-projects">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:34px">
      <span class="saas-label light-violet"><i class="fas fa-briefcase"></i> Our Work</span>
      <h2 class="saas-heading dark-text">Featured <span class="grad">Projects</span></h2>
      <p class="saas-section-sub dark-sub">
        A snapshot of our recent work across industries — each project a story of innovation, precision and client success.
      </p>
    </div>
    <div class="saas-projects-grid">
      <?php if (!empty($projects)):
        $pGrads = [
          'linear-gradient(135deg,#0f2057,#5b3dee)',
          'linear-gradient(135deg,#125a3c,#00b86b)',
          'linear-gradient(135deg,#5b3dee,#c084fc)',
          'linear-gradient(135deg,#0f4c7a,#00b4d8)',
          'linear-gradient(135deg,#7b1f3a,#e05a8a)',
          'linear-gradient(135deg,#2d1b4e,#8033ff)',
        ];
        foreach ($projects as $pi => $proj):
          $tags = !empty($proj['technologies']) ? json_decode($proj['technologies'], true) : [];
          if (!is_array($tags)) $tags = [];
          $grad = $pGrads[$pi % count($pGrads)];
      ?>
      <div class="saas-proj-card saas-reveal saas-delay-<?= ($pi % 3) + 1 ?>" data-tilt data-tilt-strength="8">
        <div class="saas-proj-thumb" style="background:<?= e($grad) ?>">
          <?php if (!empty($proj['featured_image'])): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e($proj['featured_image']) ?>" alt="<?= e($proj['title']) ?>">
          <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;opacity:.2">
              <i class="fas fa-briefcase" style="font-size:50px;color:#fff"></i>
            </div>
          <?php endif; ?>
          <span class="saas-proj-cat"><?= e($proj['category'] ?? $proj['client_name'] ?? 'Project') ?></span>
          <div class="saas-proj-overlay">
            <a href="<?= SITE_URL ?>/portfolio/<?= e($proj['slug']) ?>" class="btn-saas-primary" style="padding:9px 20px;font-size:12px">
              <i class="fas fa-eye"></i> View Case Study
            </a>
          </div>
        </div>
        <div class="saas-proj-body">
          <h3><?= e($proj['title']) ?></h3>
          <p><?= e(truncate($proj['short_description'] ?: $proj['description'] ?? '', 130)) ?></p>
          <?php if (!empty($tags)): ?>
          <div class="saas-proj-tags">
            <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
            <span class="saas-proj-tag"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; else: ?>
      <!-- Empty state — no dummy data shown -->
      <div class="proj-empty" style="grid-column:1/-1;text-align:center;padding:64px 20px">
        <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,rgba(128,51,255,.12),rgba(128,51,255,.08));display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:34px;color:#8033ff">
          <i class="fas fa-folder-open"></i>
        </div>
        <h3 style="font-family:var(--font-main),sans-serif;font-size:20px;font-weight:800;color:#0b1026;margin-bottom:10px">
          Portfolio Coming Soon
        </h3>
        <p style="color:#5e6475;font-size:14.5px;margin-bottom:24px;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.65">
          Our case studies are being curated. In the meantime, <a href="<?= SITE_URL ?>/contact.php" style="color:#8033ff;font-weight:600">get in touch</a> to discuss your project.
        </p>
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="btn-saas-primary" style="font-size:14px;padding:12px 26px">
          <i class="fas fa-paper-plane"></i> Start Your Project
        </a>
      </div>
      <?php endif; ?>
    </div>
    <div class="text-center saas-reveal" style="margin-top:48px">
      <a href="<?= SITE_URL ?>/portfolio.php" class="btn-saas-primary">
        <i class="fas fa-folder-open"></i> View All Projects
      </a>
    </div>
  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  TESTIMONIALS — 3-GRID DESKTOP / 1-SLIDER MOBILE    ║
     ╚══════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════
   TESTIMONIALS — Full Redesign
═══════════════════════════════════════════════════ */
@keyframes tcFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes tcGlow   { 0%,100%{opacity:.5} 50%{opacity:1} }
@keyframes tcShine  { 0%{left:-80%} 100%{left:150%} }
@keyframes starPop  { 0%{transform:scale(0) rotate(-30deg)} 60%{transform:scale(1.2) rotate(5deg)} 100%{transform:scale(1) rotate(0)} }

.testi-section {
  position: relative;
  padding: var(--section-y,64px) 0;
  overflow: hidden;
  background:
    radial-gradient(ellipse 70% 50% at 15% 30%, rgba(128,51,255,.14) 0%, transparent 55%),
    radial-gradient(ellipse 60% 50% at 85% 70%, rgba(128,51,255,.12) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 50% 100%, rgba(106,0,255,.08) 0%, transparent 50%),
    linear-gradient(160deg, #0b1026 0%, #10142b 40%, #0b1026 100%);
}
/* Decorative floating circles */
.testi-blob {
  position: absolute; border-radius: 50%;
  pointer-events: none; filter: blur(60px);
}
.testi-blob-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(128,51,255,.18), transparent 65%);
  top: -150px; left: -100px;
  animation: tcFloat 18s ease-in-out infinite;
}
.testi-blob-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(128,51,255,.15), transparent 65%);
  bottom: -100px; right: -80px;
  animation: tcFloat 22s ease-in-out 6s infinite reverse;
}
.testi-blob-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(106,0,255,.1), transparent 65%);
  top: 50%; left: 50%;
  animation: tcFloat 26s ease-in-out 12s infinite;
}

/* ── Section header ── */
.testi-head { text-align: center; margin-bottom: 56px; position: relative; z-index: 2; }
.testi-label {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 16px; border-radius: 30px; font-size: 11px;
  font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  margin-bottom: 16px;
  background: linear-gradient(135deg, rgba(106,0,255,.15), rgba(128,51,255,.08));
  border: 1px solid rgba(106,0,255,.3);
  color: #c4a5ff;
}
.testi-label i { font-size: 13px; }
.testi-h2 {
  font-family: var(--font-main),sans-serif;
  font-size: clamp(28px,4vw,44px); font-weight: 900;
  color: #fff; letter-spacing: -1px; margin-bottom: 14px;
  line-height: 1.1;
}
.testi-h2 .tg {
  background: linear-gradient(90deg, #6a00ff, #8033ff, #c4a5ff, #c4a5ff);
  background-size: 300% auto;
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: colorCycle 5s ease infinite;
}
.testi-sub {
  font-size: 15px; color: rgba(255,255,255,.55); max-width: 520px; margin: 0 auto;
  line-height: 1.7;
}

/* ── 5-star rating badge ── */
.testi-rating-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(106,0,255,.1); border: 1px solid rgba(106,0,255,.2);
  border-radius: 30px; padding: 6px 14px; margin-top: 14px;
  font-size: 13px; font-weight: 700; color: #c4a5ff;
}
.testi-rating-badge i { font-size: 11px; }

/* ── Unified slider ── */
.testi-grid-outer { position: relative; z-index: 2; }
.testi-slider-outer {
  overflow: hidden;
  border-radius: 4px;
  position: relative; z-index: 2;
}
.testi-slider-track {
  display: flex;
  gap: 24px;
  will-change: transform;
}
/* Card dim transition */
.tc-slide {
  transition: opacity .4s ease, transform .4s ease;
  flex-shrink: 0;
}

/* ── Single testimonial card ── */
.tc {
  position: relative; overflow: hidden;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 22px;
  padding: 30px;
  display: flex; flex-direction: column;
  transition: all .38s cubic-bezier(.4,0,.2,1);
  backdrop-filter: blur(16px);
  cursor: default;
}
.tc::before {
  content: '';
  position: absolute; inset: 0; border-radius: 22px;
  background: linear-gradient(135deg,
    var(--tc-grad-a, rgba(128,51,255,.1)),
    var(--tc-grad-b, rgba(128,51,255,.05)));
  opacity: 0; transition: opacity .35s;
}
.tc:hover::before { opacity: 1; }
.tc:hover {
  border-color: rgba(255,255,255,.18);
  transform: translateY(-8px);
  box-shadow: 0 24px 60px rgba(0,0,0,.4),
              0 0 0 1px rgba(255,255,255,.06),
              0 0 40px var(--tc-glow, rgba(128,51,255,.2));
}
/* Shimmer on hover */
.tc::after {
  content: '';
  position: absolute; top: 0; left: -80%; width: 45%; height: 100%;
  background: linear-gradient(90deg,transparent,rgba(255,255,255,.05),transparent);
  transform: skewX(-18deg);
  transition: none;
}
.tc:hover::after { animation: tcShine .65s ease forwards; }

/* Card top accent bar */
.tc-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  border-radius: 22px 22px 0 0;
  background: var(--tc-accent, linear-gradient(90deg,#8033ff,#8033ff));
  transform: scaleX(0); transform-origin: left;
  transition: transform .4s cubic-bezier(.4,0,.2,1);
}
.tc:hover .tc-accent { transform: scaleX(1); }

/* Big quote mark */
.tc-quote {
  font-size: 52px; line-height: 1;
  background: var(--tc-accent, linear-gradient(135deg,#8033ff,#8033ff));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 14px; font-family: Georgia,serif;
  opacity: .7; transition: opacity .3s;
}
.tc:hover .tc-quote { opacity: 1; }

/* Stars */
.tc-stars { display: flex; gap: 4px; margin-bottom: 14px; }
.tc-stars i {
  font-size: 14px; color: #6a00ff;
  transition: transform .2s;
}
.tc:hover .tc-stars i {
  animation: starPop .4s cubic-bezier(.34,1.56,.64,1) both;
}
.tc:hover .tc-stars i:nth-child(2) { animation-delay: .05s; }
.tc:hover .tc-stars i:nth-child(3) { animation-delay: .10s; }
.tc:hover .tc-stars i:nth-child(4) { animation-delay: .15s; }
.tc:hover .tc-stars i:nth-child(5) { animation-delay: .20s; }

/* Review text */
.tc-text {
  font-size: 14.5px; color: rgba(255,255,255,.72);
  line-height: 1.8; flex: 1;
  margin-bottom: 22px; font-style: italic;
  position: relative; z-index: 1;
}

/* Divider */
.tc-div {
  height: 1px;
  background: linear-gradient(90deg, transparent,
    rgba(255,255,255,.12) 30%, rgba(255,255,255,.12) 70%, transparent);
  margin-bottom: 20px;
}

/* Author row */
.tc-author { display: flex; align-items: center; gap: 13px; position: relative; z-index: 1; }
.tc-avatar {
  width: 50px; height: 50px; border-radius: 50%; flex-shrink: 0;
  overflow: hidden; display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800; color: #fff;
  background: var(--tc-accent, linear-gradient(135deg,#8033ff,#8033ff));
  border: 2px solid rgba(255,255,255,.15);
  box-shadow: 0 0 0 3px var(--tc-glow, rgba(128,51,255,.25));
  transition: all .3s;
}
.tc:hover .tc-avatar {
  border-color: rgba(255,255,255,.3);
  box-shadow: 0 0 0 4px var(--tc-glow, rgba(128,51,255,.35)),
              0 4px 12px rgba(0,0,0,.3);
}
.tc-avatar img { width:100%;height:100%;object-fit:cover; }
.tc-name {
  font-family: var(--font-main),sans-serif;
  font-size: 15px; font-weight: 800; color: #fff;
  margin-bottom: 3px; line-height: 1.2;
}
.tc-role { font-size: 12px; color: rgba(255,255,255,.45); font-weight: 500; }
.tc-project-tag {
  margin-left: auto; flex-shrink: 0;
  font-size: 10px; font-weight: 700; padding: 3px 10px;
  border-radius: 20px; text-transform: uppercase; letter-spacing: .5px;
  background: var(--tc-tag-bg, rgba(128,51,255,.15));
  color: var(--tc-tag-color, #e0d0ff);
  border: 1px solid var(--tc-tag-border, rgba(128,51,255,.25));
}

/* ── Navigation (shown by JS after init) ── */
.testi-nav {
  display: none; /* JS sets to flex after calcLayout() */
  align-items: center; justify-content: center; gap: 12px;
  margin-top: 32px;
}
.testi-nav-btn {
  width: 46px; height: 46px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.14);
  color: rgba(255,255,255,.7); font-size: 15px; cursor: pointer;
  transition: all .25s cubic-bezier(.34,1.56,.64,1);
}
.testi-nav-btn:hover {
  background: rgba(255,255,255,.14); color: #fff;
  border-color: rgba(255,255,255,.3);
  transform: scale(1.1);
  box-shadow: 0 4px 16px rgba(128,51,255,.3);
}
.testi-dots { display: flex; gap: 8px; }
.testi-dot {
  width: 9px; height: 9px; border-radius: 20px;
  background: rgba(255,255,255,.22); border: none; cursor: pointer; padding: 0;
  transition: all .38s cubic-bezier(.34,1.56,.64,1);
}
.testi-dot.active {
  width: 28px;
  background: linear-gradient(90deg, #6a00ff, #8033ff, #c4a5ff);
  background-size: 200% 100%;
  animation: dotFlow 2s linear infinite;
  box-shadow: 0 0 12px rgba(106,0,255,.55);
}
</style>

<?php
/* Build testimonials list (DB or fallback) */
$_testiList = !empty($testimonials) ? $testimonials : [
  ['client_name'=>'Vikram Singh',  'designation'=>'CEO',          'company'=>'TechVentures Pvt Ltd',
   'rating'=>5,'content'=>'Appsgain delivered our mobile app 2 weeks ahead of schedule with exceptional quality. The team was proactive, transparent, and genuinely invested in our success.',
   'photo'=>'','project_type'=>'Mobile App'],
  ['client_name'=>'Ananya Gupta',  'designation'=>'Founder',      'company'=>'ShopEasy',
   'rating'=>5,'content'=>'Our e-commerce platform saw a 3× increase in conversions after Appsgain redesigned the UX. They understood our business goals and delivered beyond expectations.',
   'photo'=>'','project_type'=>'E-Commerce'],
  ['client_name'=>'Mohammed Raza', 'designation'=>'IT Manager',   'company'=>'GlobalTrade Co.',
   'rating'=>5,'content'=>'Their DevOps setup reduced our deployment time from hours to minutes. The technical depth, clear communication, and on-time delivery made this a seamless experience.',
   'photo'=>'','project_type'=>'DevOps / Cloud'],
  ['client_name'=>'Lakshmi Nair',  'designation'=>'HR Director',  'company'=>'BrightFuture Edtech',
   'rating'=>5,'content'=>'The AI chatbot they built handles 80% of our student queries automatically. Saved us thousands in support costs while dramatically improving response times.',
   'photo'=>'','project_type'=>'AI Chatbot'],
];

/* Per-card color themes */
$_tcThemes = [
  ['--tc-grad-a:rgba(128,51,255,.12)',  '--tc-grad-b:rgba(128,51,255,.06)',  '--tc-glow:rgba(128,51,255,.25)',  '--tc-accent:linear-gradient(90deg,#8033ff,#8033ff)', '--tc-tag-bg:rgba(128,51,255,.15)',  '--tc-tag-color:#e0d0ff', '--tc-tag-border:rgba(128,51,255,.25)'],
  ['--tc-grad-a:rgba(106,0,255,.12)',  '--tc-grad-b:rgba(106,0,255,.06)',  '--tc-glow:rgba(106,0,255,.25)',  '--tc-accent:linear-gradient(90deg,#6a00ff,#6a00ff)', '--tc-tag-bg:rgba(106,0,255,.15)',  '--tc-tag-color:#c4a5ff', '--tc-tag-border:rgba(106,0,255,.25)'],
  ['--tc-grad-a:rgba(106,0,255,.12)',   '--tc-grad-b:rgba(106,0,255,.06)',  '--tc-glow:rgba(106,0,255,.25)',   '--tc-accent:linear-gradient(90deg,#6a00ff,#6a00ff)', '--tc-tag-bg:rgba(106,0,255,.15)',   '--tc-tag-color:#c4a5ff', '--tc-tag-border:rgba(106,0,255,.25)'],
  ['--tc-grad-a:rgba(16,185,129,.12)',  '--tc-grad-b:rgba(128,51,255,.06)',  '--tc-glow:rgba(16,185,129,.25)',  '--tc-accent:linear-gradient(90deg,#10b981,#8033ff)', '--tc-tag-bg:rgba(16,185,129,.15)',  '--tc-tag-color:#6ee7b7', '--tc-tag-border:rgba(16,185,129,.25)'],
];
$_avatarGrads = [
  'linear-gradient(135deg,#8033ff,#8033ff)',
  'linear-gradient(135deg,#6a00ff,#6a00ff)',
  'linear-gradient(135deg,#6a00ff,#6a00ff)',
  'linear-gradient(135deg,#10b981,#8033ff)',
];
?>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  TESTIMONIALS — balanced 3-column grid              ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="tsm" id="testimonials">
  <div class="container">

    <div class="tsm-head">
      <span class="tsm-eyebrow"><i class="fas fa-quote-left" aria-hidden="true"></i> Client Stories</span>
      <h2 class="tsm-title">What Our <span>Clients Say</span></h2>
      <p class="tsm-sub">
        Don&rsquo;t just take our word for it &mdash; here&rsquo;s what the businesses we&rsquo;ve worked with have to say.
      </p>
      <?php
      /* Average rating computed from the actual rows, not a hardcoded figure */
      $_tRated = array_filter($_testiList, fn($t) => (int)($t['rating'] ?? 0) > 0);
      $_tAvg   = $_tRated ? array_sum(array_map(fn($t) => (int)$t['rating'], $_tRated)) / count($_tRated) : 0;
      ?>
      <?php if ($_tAvg > 0): ?>
      <div class="tsm-score">
        <span class="tsm-score-stars" aria-hidden="true">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa<?= $i <= round($_tAvg) ? 's' : 'r' ?> fa-star"></i>
          <?php endfor; ?>
        </span>
        <span class="tsm-score-txt">
          <strong><?= number_format($_tAvg, 1) ?></strong> out of 5
          &middot; <?= count($_testiList) ?> client reviews
        </span>
      </div>
      <?php endif; ?>
    </div>

    <div class="tsm-grid">
      <?php foreach ($_testiList as $_ti => $_t):
        $_name     = trim((string)($_t['client_name'] ?? ''));
        $_initials = strtoupper(mb_substr($_name !== '' ? $_name : 'C', 0, 1));
        if (preg_match('/\s(\S)/u', $_name, $_m)) $_initials .= strtoupper($_m[1]);
        $_role  = trim((string)($_t['designation'] ?? ''));
        $_co    = trim((string)($_t['company'] ?? ''));
        $_stars = max(0, min(5, (int)($_t['rating'] ?? 5)));
      ?>
      <article class="tsm-card">
        <span class="tsm-mark" aria-hidden="true">&rdquo;</span>

        <div class="tsm-stars" role="img" aria-label="<?= $_stars ?> out of 5 stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa<?= $i <= $_stars ? 's' : 'r' ?> fa-star" aria-hidden="true"></i>
          <?php endfor; ?>
        </div>

        <blockquote class="tsm-quote"><?= e($_t['content'] ?? '') ?></blockquote>

        <footer class="tsm-author">
          <span class="tsm-avatar">
            <?php if (!empty($_t['photo'])): ?>
              <img src="<?= e(UPLOADS_URL . '/' . ltrim($_t['photo'], '/')) ?>" alt="<?= e($_name) ?>" loading="lazy" width="46" height="46">
            <?php else: ?>
              <?= e($_initials) ?>
            <?php endif; ?>
          </span>
          <span class="tsm-who">
            <strong><?= e($_name) ?></strong>
            <small><?= e($_role) ?><?= ($_role && $_co) ? ' · ' : '' ?><?= e($_co) ?></small>
          </span>
        </footer>
      </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  BLOG                                               ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-blog" style="background:#f7f8fc">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:34px">
      <span class="saas-label" style="background:rgba(234,88,12,.1);color:#fb923c;border:1px solid rgba(234,88,12,.2)">
        <i class="fas fa-newspaper"></i> Insights
      </span>
      <h2 class="saas-heading dark-text">Latest from Our <span class="grad">Blog</span></h2>
    </div>
    <div class="saas-blog-grid">
      <?php if (!empty($blogs)):
        $bGrads = ['linear-gradient(135deg,#0f2057,#5b3dee)','linear-gradient(135deg,#125a3c,#00b86b)','linear-gradient(135deg,#7b3a1e,#e07b39)'];
        foreach ($blogs as $bi => $blog):
          $bg = $bGrads[$bi % count($bGrads)];
      ?>
      <div class="saas-blog-card saas-reveal saas-delay-<?= $bi + 1 ?>">
        <div class="saas-blog-img" style="background:<?= e($bg) ?>">
          <?php if (!empty($blog['featured_image'])): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e($blog['featured_image']) ?>" alt="<?= e($blog['title']) ?>">
          <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;opacity:.2">
              <i class="fas fa-newspaper" style="font-size:40px;color:#fff"></i>
            </div>
          <?php endif; ?>
          <span class="saas-blog-cat"><?= e($blog['cat_name'] ?? 'Blog') ?></span>
        </div>
        <div class="saas-blog-body">
          <div class="saas-blog-meta">
            <span><i class="fas fa-calendar"></i> <?= formatDate($blog['published_at'] ?? $blog['created_at']) ?></span>
            <span><i class="fas fa-clock"></i> <?= readingTime($blog['content']) ?> min</span>
          </div>
          <h3><?= e($blog['title']) ?></h3>
          <p><?= e(truncate($blog['excerpt'] ?: $blog['content'], 120)) ?></p>
          <a href="<?= SITE_URL ?>/blog/<?= e($blog['slug']) ?>" class="saas-blog-link">
            Read Article <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="saas-blog-card saas-reveal">
        <div class="saas-blog-img" style="background:linear-gradient(135deg,#0f2057,#5b3dee)">
          <span class="saas-blog-cat">AI &amp; Tech</span>
        </div>
        <div class="saas-blog-body">
          <div class="saas-blog-meta"><span><i class="fas fa-calendar"></i> Coming Soon</span></div>
          <h3>How AI is Reshaping Software Development</h3>
          <p>From AI-assisted coding to autonomous testing — explore how artificial intelligence is fundamentally changing how software is built.</p>
          <a href="<?= SITE_URL ?>/blog.php" class="saas-blog-link">Read Article <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="text-center saas-reveal" style="margin-top:48px">
      <a href="<?= SITE_URL ?>/blog.php" class="btn-saas-primary">
        <i class="fas fa-newspaper"></i> View All Articles
      </a>
    </div>
  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  FAQ                                                ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-faq">
  <div class="container">
    <div class="text-center saas-reveal" style="margin-bottom:34px">
      <span class="saas-label" style="background:rgba(106,0,255,.1);color:#a16bff;border:1px solid rgba(106,0,255,.2)">
        <i class="fas fa-circle-question"></i> Common Questions
      </span>
      <h2 class="saas-heading dark-text">Frequently Asked <span class="grad">Questions</span></h2>
      <p class="saas-section-sub dark-sub">Everything you need to know before starting your project with us.</p>
    </div>
    <div style="max-width:780px;margin:0 auto" class="saas-reveal saas-delay-1">
      <?php if (!empty($homeFaqs)): foreach ($homeFaqs as $faq): ?>
      <div class="saas-faq-item">
        <div class="saas-faq-q">
          <?= e($faq['question']) ?>
          <span class="saas-faq-q-icon"><i class="fas fa-chevron-down"></i></span>
        </div>
        <div class="saas-faq-a"><?= e($faq['answer']) ?></div>
      </div>
      <?php endforeach; else: ?>
      <?php
      $fallbackFaqs = [
        ['How long does it take to build a custom web or mobile app?', 'Timelines vary by scope, but most projects land between 6 to 16 weeks. A simple landing site can ship in 2 weeks; a full-featured SaaS platform typically takes 3–4 months.'],
        ['Do you provide post-launch support and maintenance?', 'Yes — every project includes 30 days of free post-launch support. We also offer flexible retainer packages for ongoing feature development, performance monitoring and security patches.'],
        ['Can you work with our existing tech stack or team?', 'Absolutely. We integrate seamlessly with existing codebases and embed within your team\'s sprint workflow. We do a quick tech-audit in week one to flag risks and align on standards.'],
        ['What does your pricing model look like?', 'We offer fixed-price engagements for well-defined projects and time-and-material billing for evolving product work. You get a detailed estimate broken down by phase — no surprise invoices.'],
        ['Are my ideas and data safe with Appsgain?', 'Completely. Every engagement is covered by a strict NDA and our team follows enterprise-grade security protocols. Your intellectual property stays yours.'],
      ];
      foreach ($fallbackFaqs as $fq): ?>
      <div class="saas-faq-item">
        <div class="saas-faq-q">
          <?= $fq[0] ?>
          <span class="saas-faq-q-icon"><i class="fas fa-chevron-down"></i></span>
        </div>
        <div class="saas-faq-a"><?= $fq[1] ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="text-center saas-reveal" style="margin-top:36px">
      <a href="<?= SITE_URL ?>/faq.php" class="btn-saas-secondary" style="color:#242a42;border-color:#e7e9f0">
        <i class="fas fa-list-ul"></i> View All FAQs
      </a>
    </div>
  </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗
     ║  CTA — DARK GLASSMORPHISM                           ║
     ╚══════════════════════════════════════════════════════╝ -->
<section class="saas-cta">
  <div class="saas-cta-bubble"></div>
  <div class="saas-cta-bubble"></div>
  <div class="container">
    <div class="saas-cta-inner saas-reveal">
      <h2 class="saas-cta-title">
        Ready to Build Something
        <span class="ht-grad" style="background:linear-gradient(90deg,#c4a5ff,#c4a5ff,#a16bff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"> Amazing?</span>
      </h2>
      <p class="saas-cta-sub">
        Let's turn your idea into a product that users love and investors notice.
        Get a free consultation and project estimate today.
      </p>
      <div class="saas-cta-btns">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="btn-saas-primary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-paper-plane"></i> Start Your Project
        </a>
        <a href="<?= SITE_URL ?>/portfolio.php" class="btn-saas-secondary" style="font-size:15px;padding:16px 32px">
          <i class="fas fa-eye"></i> See Our Work
        </a>
      </div>
      <div class="saas-cta-phone">
        <i class="fas fa-phone-alt"></i>
        <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', getSetting('phone', '+919955446477'))) ?>">
          Call Us: <?= e(getSetting('phone', '+91-9955446477')) ?>
        </a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<!-- 3D SaaS JS engine (loads Three.js then inits everything) -->
<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/3d-saas.js"></script>

<script>
/* ══════════════════════════════════════════════════════
   HERO CAROUSEL — Complete init with typing animation
══════════════════════════════════════════════════════ */
(function () {
  var slides  = document.querySelectorAll('.hero-slide');
  var dots    = document.querySelectorAll('.hero-slide-dot');
  var typingEl= document.getElementById('heroTypingText');
  var caretEl = document.getElementById('heroCaret');

  if (!slides.length) return;

  var current      = 0;
  var autoTimer    = null;
  var typingTimer  = null;
  var INTERVAL     = 5500; // ms per slide

  /* ── Slide accent colors per slide index ── */
  var slideAccents = [
    ['#c4a5ff','#c4a5ff'],  /* blue  → violet  */
    ['#34d399','#c4a5ff'],  /* green → blue    */
    ['#8033ff','#6a00ff'],  /* pink  → amber   */
    ['#c4a5ff','#6a00ff'],  /* violet→ pink    */
    ['#c4a5ff','#34d399'],  /* blue  → green   */
  ];

  /* ── Typing animation ── */
  function typeText(text, cb) {
    if (!typingEl) return;
    clearInterval(typingTimer);
    typingEl.textContent = '';
    var i = 0;
    typingTimer = setInterval(function () {
      if (i < text.length) {
        typingEl.textContent += text[i++];
      } else {
        clearInterval(typingTimer);
        if (cb) cb();
      }
    }, 35);
  }

  /* ── Show a slide ── */
  function goTo(idx, dir) {
    var from = current;
    idx = ((idx % slides.length) + slides.length) % slides.length;

    /* Deactivate current */
    if (slides[from]) {
      slides[from].classList.remove('hs-active');
    }
    dots.forEach(function(d){ d.classList.remove('active'); });

    /* Activate new */
    current = idx;
    slides[idx].classList.add('hs-active');
    if (dots[idx]) dots[idx].classList.add('active');

    /* Update eyebrow accent gradient */
    var acc = slideAccents[idx] || slideAccents[0];
    var eyebrow = document.querySelector('.saas-hero-eyebrow');
    if (eyebrow) {
      eyebrow.style.background = 'linear-gradient(135deg, ' +
        acc[0].replace('#','rgba(') + '20), ' +
        acc[1].replace('#','rgba(') + '10))';
      /* Simple fallback */
      eyebrow.style.background = 'linear-gradient(135deg, rgba(128,51,255,.15), rgba(128,51,255,.1))';
    }

    /* Typing animation for this slide */
    var badge = slides[idx].dataset.badge || '';
    if (badge && typingEl) {
      typeText(badge);
    }
  }

  /* ── Auto advance ── */
  function startAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(function () {
      goTo(current + 1);
    }, INTERVAL);
  }

  /* ── Dot click handlers ── */
  dots.forEach(function (dot, i) {
    dot.addEventListener('click', function () {
      goTo(i);
      startAuto(); /* reset timer */
    });
  });

  /* ── Swipe support for mobile ── */
  var touchStartX = 0;
  var heroSection = document.querySelector('.saas-hero');
  if (heroSection) {
    heroSection.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].clientX;
    }, { passive: true });
    heroSection.addEventListener('touchend', function(e) {
      var dx = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(dx) > 50) {
        goTo(dx < 0 ? current + 1 : current - 1);
        startAuto();
      }
    }, { passive: true });
  }

  /* ── Keyboard navigation ── */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight') { goTo(current + 1); startAuto(); }
    if (e.key === 'ArrowLeft')  { goTo(current - 1); startAuto(); }
  });

  /* ── Init: show first slide & start typing ── */
  goTo(0);
  startAuto();

})();

/* ══════════════════════════════════════════════════════
   HOMEPAGE MICRO-ANIMATIONS
══════════════════════════════════════════════════════ */
(function () {
  /* Add saas-reveal to major section headings if not already present */
  document.querySelectorAll('.saas-heading, .saas-section-sub').forEach(function(el) {
    if (!el.closest('[class*="saas-reveal"]') && !el.classList.contains('saas-reveal')) {
      el.classList.add('saas-reveal');
    }
  });

  /* Stat card hover — number glow */
  document.querySelectorAll('.saas-stat-card').forEach(function(card) {
    card.addEventListener('mouseenter', function () {
      var num = this.querySelector('.saas-stat-num');
      if (num) { num.style.textShadow = '0 0 30px currentColor'; }
    });
    card.addEventListener('mouseleave', function () {
      var num = this.querySelector('.saas-stat-num');
      if (num) { num.style.textShadow = ''; }
    });
  });
})();
</script>

</body>
</html>
