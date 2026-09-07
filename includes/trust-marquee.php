<?php
/**
 * Appsgain — Trust, Certifications & Partnerships marquee.
 *
 * Every item comes from `certifications_partners`; nothing here is
 * hardcoded. The wrapper (title, subtitle, visibility, background,
 * speed, logo treatment) is driven by settings.
 *
 * Seamlessness: the track is rendered twice and translated by exactly
 * -50%, so the second copy is under the cursor at the moment the first
 * finishes and the loop is invisible. With very few items the list is
 * repeated until it comfortably exceeds one viewport width first —
 * otherwise a short list leaves a visible gap. Repetition is purely
 * visual; the database keeps one row per organisation.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

$_tmS = getAllSettings();
if (($_tmS['trust_section_show'] ?? '1') !== '1') return;

$_tmItems = dbFetchAll(
    "SELECT name, logo, logo_url, description, website_url
     FROM certifications_partners
     WHERE section = 'trust' AND is_active = 1
     ORDER BY display_order ASC, id ASC"
);
if (!$_tmItems) return;

$_tmTitle = trim((string)($_tmS['trust_section_title'] ?? '')) ?: 'Trusted, certified and partnered';
$_tmSub   = trim((string)($_tmS['trust_section_subtitle'] ?? ''));
$_tmBg    = in_array($_tmS['trust_section_bg'] ?? 'soft', ['soft', 'white', 'tint'], true)
          ? $_tmS['trust_section_bg'] : 'soft';
$_tmSpeed = max(12, min(120, (int)($_tmS['trust_section_speed'] ?? 38)));
$_tmGray  = ($_tmS['trust_logo_style'] ?? 'grayscale') === 'grayscale';

/* Repeat until the strip is long enough to fill a wide screen, then the
   whole thing is duplicated once more for the -50% loop. */
$_tmMin  = 14;
$_tmLoop = $_tmItems;
while (count($_tmLoop) < $_tmMin) {
    $_tmLoop = array_merge($_tmLoop, $_tmItems);
}

/** One chip. $aria false on the duplicate copy so screen readers hear it once. */
$_tmChip = static function (array $it, bool $aria) use ($_tmGray): void {
    $name = (string)$it['name'];
    $url  = trim((string)$it['website_url']);
    /* a hosted badge URL wins over an upload */
    $logo = trim((string)($it['logo_url'] ?? '')) ?: trim((string)$it['logo']);
    $desc = trim((string)$it['description']);

    $tag   = $url !== '' ? 'a' : 'div';
    $attrs = $url !== ''
        ? ' href="' . e($url) . '" target="_blank" rel="noopener nofollow"'
        : '';
    /* The name is no longer drawn, so it has to carry accessibly instead —
       via alt on the logo and aria-label on the link. */
    if ($aria) { $attrs .= ' aria-label="' . e($name) . '"'; }
    else       { $attrs .= ' aria-hidden="true" tabindex="-1"'; }
    ?>
    <<?= $tag ?> class="tmq-item<?= $_tmGray ? ' is-gray' : '' ?><?= $logo === '' ? ' is-textonly' : '' ?>"<?= $attrs ?>
       <?= $desc !== '' ? 'title="' . e($name . ' — ' . $desc) . '"' : 'title="' . e($name) . '"' ?>>
      <?php if ($logo !== ''): ?>
        <?php /* Logo only — the name is deliberately not rendered. */ ?>
        <img class="tmq-logo" src="<?= e(str_starts_with($logo, 'http') ? $logo : UPLOADS_URL . '/' . ltrim($logo, '/')) ?>"
             alt="<?= e($name) ?>" loading="lazy" decoding="async">
      <?php else: ?>
        <?php /* No artwork yet: a wall of monograms would say nothing, so
                  the name stands in until a logo is uploaded. */ ?>
        <span class="tmq-name"><?= e($name) ?></span>
      <?php endif; ?>
    </<?= $tag ?>>
    <?php
};
?>

<section class="tmq tmq-bg-<?= e($_tmBg) ?>" aria-labelledby="tmqTitle">
  <div class="tmq-wrap">
    <div class="tmq-head">
      <h2 class="tmq-title" id="tmqTitle"><?= e($_tmTitle) ?></h2>
      <?php if ($_tmSub !== ''): ?>
      <p class="tmq-sub"><?= e($_tmSub) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="tmq-strip" style="--tmq-speed:<?= $_tmSpeed ?>s">
    <div class="tmq-track">
      <div class="tmq-set">
        <?php foreach ($_tmLoop as $_it) $_tmChip($_it, true); ?>
      </div>
      <?php /* identical copy: what makes the -50% loop seamless */ ?>
      <div class="tmq-set">
        <?php foreach ($_tmLoop as $_it) $_tmChip($_it, false); ?>
      </div>
    </div>
  </div>
</section>

<style>
/* ══════════════════════════════════════════════════════
   TRUST / CERTIFICATIONS MARQUEE
   ══════════════════════════════════════════════════════ */
.tmq{
  --t-ink:#11162D; --t-body:#5E6475; --t-mute:#7E8496;
  --t-line:#E7E9F0; --t-card:#FFFFFF;
  --t-brand:#6A00FF;
  position:relative; overflow:hidden;
  padding:clamp(30px,3.8vw,52px) 0;
  border-top:1px solid var(--t-line);
  font-family:'Plus Jakarta Sans','Inter',system-ui,sans-serif;
}
.tmq-bg-soft{ background:#F7F8FC; }
.tmq-bg-white{ background:#FFFFFF; }
.tmq-bg-tint{
  background:
    radial-gradient(80% 140% at 8% 0%, rgba(106,0,255,.05) 0%, transparent 60%),
    radial-gradient(80% 140% at 92% 100%, rgba(245,0,114,.04) 0%, transparent 60%),
    #FBFAFE;
}
.tmq-wrap{ max-width:1200px; margin-inline:auto; padding-inline:clamp(20px,4vw,32px); }

.tmq-head{ text-align:center; margin-bottom:clamp(20px,2.6vw,30px); }
.tmq-title{
  font-size:clamp(18px,2.1vw,25px); font-weight:800; letter-spacing:-.022em;
  color:var(--t-ink); margin:0;
}
.tmq-sub{
  font-family:'Inter',sans-serif;
  font-size:14px; line-height:1.65; color:var(--t-body);
  margin:8px auto 0; max-width:60ch;
}

/* ── Strip ── */
.tmq-strip{
  position:relative;
  -webkit-mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
  mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
}
.tmq-track{
  display:flex; width:max-content;
  animation:tmqScroll var(--tmq-speed,38s) linear infinite;
  will-change:transform;
}
.tmq-set{ display:flex; align-items:stretch; gap:clamp(10px,1.4vw,16px); padding-inline:clamp(5px,.7vw,8px); }
@keyframes tmqScroll{
  from{ transform:translate3d(0,0,0); }
  to  { transform:translate3d(-50%,0,0); }
}
/* Pause on hover, and whenever a chip inside has keyboard focus */
.tmq-strip:hover .tmq-track,
.tmq-track:focus-within{ animation-play-state:paused; }

/* ── Chip ── */
.tmq-item{
  flex:0 0 auto;
  display:inline-flex; align-items:center; justify-content:center; gap:0;
  height:64px; padding:0 24px; min-width:120px;
  border-radius:14px;
  background:var(--t-card); border:1px solid var(--t-line);
  text-decoration:none; white-space:nowrap;
  box-shadow:0 1px 2px rgba(16,22,47,.03);
  transition:border-color .22s ease, box-shadow .22s ease, transform .22s ease;
}
a.tmq-item:hover{
  border-color:rgba(106,0,255,.28);
  box-shadow:0 8px 22px rgba(16,22,47,.08);
  transform:translateY(-2px);
}
a.tmq-item:focus-visible{ outline:2px solid var(--t-brand); outline-offset:3px; }

/* Logo only. Given the chip carries nothing else, the artwork gets the
   whole box rather than a 34px slot beside a label. */
.tmq-logo{
  max-height:38px; max-width:150px; width:auto; height:auto;
  object-fit:contain; display:block;
  transition:filter .28s ease, opacity .28s ease;
}
.tmq-item.is-gray .tmq-logo{ filter:grayscale(1); opacity:.72; }
.tmq-item.is-gray:hover .tmq-logo{ filter:none; opacity:1; }

/* Stand-in until artwork is uploaded */
.tmq-name{
  font-size:14px; font-weight:600; letter-spacing:-.005em;
  color:var(--t-ink);
}

@media (max-width:768px){
  .tmq-item{ height:56px; padding:0 18px; border-radius:12px; min-width:100px; }
  .tmq-logo{ max-height:30px; max-width:118px; }
  .tmq-name{ font-size:13px; }
  .tmq-strip{
    -webkit-mask-image:linear-gradient(90deg, transparent, #000 5%, #000 95%, transparent);
    mask-image:linear-gradient(90deg, transparent, #000 5%, #000 95%, transparent);
  }
}
@media (max-width:480px){
  .tmq-item{ height:52px; padding:0 13px; }
  .tmq-name{ font-size:12.5px; }
}

/* Motion off: stop scrolling and let the row scroll by hand instead,
   so every item is still reachable. */
@media (prefers-reduced-motion:reduce){
  .tmq-track{ animation:none; }
  .tmq-strip{
    overflow-x:auto; scrollbar-width:thin;
    -webkit-mask-image:none; mask-image:none;
  }
  .tmq-track .tmq-set:last-child{ display:none; }
  .tmq-item{ transition:none; }
}
@media print{ .tmq{ display:none; } }
</style>
