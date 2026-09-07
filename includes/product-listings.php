<?php
/**
 * Appsgain — "Our Product Listed On" badges.
 *
 * A static wrapping grid rather than a marquee: these are wide,
 * full-colour launch-directory badges, and they read better held still
 * than scrolled past.
 *
 * Every row comes from `certifications_partners` where section='listed'.
 * The badge image may be an upload (`logo`) or a hosted URL (`logo_url`)
 * — most directories publish a badge you are meant to hotlink, so the
 * URL takes precedence when both are set.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

$_plS = getAllSettings();
if (($_plS['listed_section_show'] ?? '1') !== '1') return;

$_plItems = dbFetchAll(
    "SELECT name, logo, logo_url, website_url
     FROM certifications_partners
     WHERE section = 'listed' AND is_active = 1
     ORDER BY display_order ASC, id ASC"
);
if (!$_plItems) return;

$_plTitle = trim((string)($_plS['listed_section_title'] ?? '')) ?: 'Our Product Listed On';
$_plSub   = trim((string)($_plS['listed_section_subtitle'] ?? ''));
$_plBg    = in_array($_plS['listed_section_bg'] ?? 'white', ['soft', 'white', 'tint'], true)
          ? $_plS['listed_section_bg'] : 'white';
?>

<section class="plx plx-bg-<?= e($_plBg) ?>" aria-labelledby="plxTitle">
  <div class="plx-wrap">
    <div class="plx-head">
      <h2 class="plx-title" id="plxTitle"><?= e($_plTitle) ?></h2>
      <?php if ($_plSub !== ''): ?>
      <p class="plx-sub"><?= e($_plSub) ?></p>
      <?php endif; ?>
    </div>

    <ul class="plx-grid">
      <?php foreach ($_plItems as $_it):
        $name = (string)$_it['name'];
        $url  = trim((string)$_it['website_url']);
        /* hosted badge wins over an upload; either may be absent */
        $img  = trim((string)$_it['logo_url']);
        if ($img === '' && trim((string)$_it['logo']) !== '') {
            $lg  = trim((string)$_it['logo']);
            $img = str_starts_with($lg, 'http') ? $lg : UPLOADS_URL . '/' . ltrim($lg, '/');
        }
        $tag = $url !== '' ? 'a' : 'span';
        ?>
      <li>
        <<?= $tag ?> class="plx-badge<?= $img === '' ? ' is-blank' : '' ?>"
          <?= $url !== '' ? 'href="' . e($url) . '" target="_blank" rel="noopener"' : '' ?>
          title="<?= e($name) ?>">
          <?php if ($img !== ''): ?>
            <img src="<?= e($img) ?>" alt="<?= e($name) ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <?php /* Until the badge artwork is added, a legible text badge
                      keeps the row looking deliberate rather than broken. */ ?>
            <span class="plx-txt">
              <em>Listed on</em>
              <strong><?= e($name) ?></strong>
            </span>
          <?php endif; ?>
        </<?= $tag ?>>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<style>
/* ══════════════════════════════════════════════════════
   PRODUCT LISTING BADGES
   ══════════════════════════════════════════════════════ */
.plx{
  --p-ink:#11162D; --p-body:#5E6475; --p-mute:#7E8496;
  --p-line:#E7E9F0; --p-card:#FFFFFF; --p-brand:#6A00FF;
  padding:clamp(30px,3.8vw,52px) 0;
  border-top:1px solid var(--p-line);
  font-family:var(--font-main),system-ui,sans-serif;
}
.plx-bg-white{ background:#FFFFFF; }
.plx-bg-soft{ background:#F7F8FC; }
.plx-bg-tint{
  background:
    radial-gradient(80% 140% at 92% 0%, rgba(106,0,255,.05) 0%, transparent 60%),
    #FBFAFE;
}
.plx-wrap{ max-width:1200px; margin-inline:auto; padding-inline:clamp(20px,4vw,32px); }

.plx-head{ text-align:center; margin-bottom:clamp(20px,2.6vw,30px); }
.plx-title{
  font-size:clamp(18px,2.1vw,25px); font-weight:800; letter-spacing:-.022em;
  color:var(--p-ink); margin:0;
}
.plx-sub{
  font-family:var(--font-body),sans-serif;
  font-size:14px; line-height:1.65; color:var(--p-body);
  margin:8px auto 0; max-width:60ch;
}

/* Badges vary in aspect ratio, so the row centres and wraps rather than
   forcing every one into an identical cell. */
.plx-grid{
  list-style:none; margin:0; padding:0;
  display:flex; flex-wrap:wrap; justify-content:center;
  gap:clamp(12px,1.6vw,18px);
}
.plx-badge{
  display:inline-flex; align-items:center; justify-content:center;
  height:62px; padding:8px 10px;
  border-radius:12px;
  background:var(--p-card); border:1px solid var(--p-line);
  text-decoration:none;
  transition:border-color .22s ease, box-shadow .22s ease, transform .22s ease;
}
a.plx-badge:hover{
  border-color:rgba(106,0,255,.28);
  box-shadow:0 8px 22px rgba(16,22,47,.09);
  transform:translateY(-2px);
}
a.plx-badge:focus-visible{ outline:2px solid var(--p-brand); outline-offset:3px; }
.plx-badge img{
  max-height:46px; max-width:220px; width:auto; height:auto;
  object-fit:contain; display:block;
}

/* Text stand-in until the real badge image is set */
.plx-badge.is-blank{ padding:0 18px; }
.plx-txt{ display:flex; flex-direction:column; line-height:1.15; text-align:left; }
.plx-txt em{
  font-style:normal; font-size:9.5px; font-weight:700;
  letter-spacing:.14em; text-transform:uppercase; color:var(--p-mute);
}
.plx-txt strong{
  font-size:15px; font-weight:800; letter-spacing:-.015em; color:var(--p-ink);
  margin-top:2px;
}
a.plx-badge:hover .plx-txt strong{ color:var(--p-brand); }

@media (max-width:768px){
  .plx-badge{ height:54px; }
  .plx-badge img{ max-height:40px; max-width:180px; }
  .plx-txt strong{ font-size:14px; }
}
@media (max-width:480px){
  .plx-grid{ gap:10px; }
  .plx-badge{ height:50px; padding:6px 8px; }
  .plx-badge.is-blank{ padding:0 14px; }
  .plx-badge img{ max-height:36px; max-width:150px; }
}
@media (prefers-reduced-motion:reduce){
  .plx-badge{ transition:none; }
  a.plx-badge:hover{ transform:none; }
}
</style>
