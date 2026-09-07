<?php
/**
 * Appsgain — shared page hero + breadcrumb.
 *
 * One premium light-theme header used across every inner page, so the
 * breadcrumbs and titles stop looking different on each one.
 *
 * pageHero([
 *   'eyebrow'  => 'About us',
 *   'lead'     => 'Building the Future of',      // plain part of the H1
 *   'accent'   => 'Digital Innovation',          // gradient part
 *   'sub'      => 'Supporting sentence.',
 *   'crumbs'   => [['Services','/services.php'], ['ERP Development', null]],
 *   'badge'    => 'Hiring',                      // optional pill beside the title
 *   'actions'  => [['Label','/url', true], ...], // true = primary
 *   'stats'    => [['500+','Projects'], ...],
 * ]);
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

if (!function_exists('pageHero')) {
function pageHero(array $o): void {
    $crumbs  = $o['crumbs']  ?? [];
    $actions = $o['actions'] ?? [];
    $stats   = $o['stats']   ?? [];
    $url = static function (?string $u): string {
        $u = trim((string)$u);
        if ($u === '') return '';
        if (preg_match('~^(https?:|mailto:|tel:|\#)~i', $u)) return $u;
        return rtrim(SITE_URL, '/') . '/' . ltrim($u, '/');
    };
    /* An uploaded banner for this page, if the caller named a slot. */
    $heroImg = !empty($o['image']) && function_exists('sectionImage')
        ? sectionImage($o['image'])
        : '';
    ?>
    <section class="ph<?= $heroImg !== '' ? ' ph--has-img' : '' ?>">
      <span class="ph-glow" aria-hidden="true"></span>
      <div class="ph-wrap">

        <nav class="ph-crumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="<?= SITE_URL ?>/"><i class="fas fa-house" aria-hidden="true"></i> Home</a></li>
            <?php foreach ($crumbs as $i => [$label, $href]):
              $isLast = ($i === count($crumbs) - 1); ?>
            <li aria-hidden="true" class="ph-crumb-sep"><i class="fas fa-chevron-right"></i></li>
            <li>
              <?php if ($href && !$isLast): ?>
                <a href="<?= e($url($href)) ?>"><?= e($label) ?></a>
              <?php else: ?>
                <span aria-current="page"><?= e($label) ?></span>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </nav>

        <div class="ph-body">
          <?php if (!empty($o['eyebrow'])): ?>
          <span class="ph-eyebrow"><?= e($o['eyebrow']) ?></span>
          <?php endif; ?>

          <h1 class="ph-title">
            <?= e($o['lead'] ?? '') ?><?php if (!empty($o['accent'])): ?>
            <span class="ph-grad"><?= e($o['accent']) ?></span><?php endif; ?>
            <?php if (!empty($o['badge'])): ?><em class="ph-badge"><?= e($o['badge']) ?></em><?php endif; ?>
          </h1>

          <?php if (!empty($o['sub'])): ?>
          <p class="ph-sub"><?= e($o['sub']) ?></p>
          <?php endif; ?>

          <?php if ($actions): ?>
          <div class="ph-actions">
            <?php foreach ($actions as [$label, $href, $primary]): ?>
            <a href="<?= e($url($href)) ?>" class="ph-btn <?= $primary ? 'ph-btn-primary' : 'ph-btn-ghost' ?>">
              <?= e($label) ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($stats): ?>
          <ul class="ph-stats">
            <?php foreach ($stats as [$val, $label]): ?>
            <li><strong><?= e($val) ?></strong><span><?= e($label) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>

        <?php if ($heroImg !== ''): ?>
        <div class="ph-media">
          <img src="<?= e($heroImg) ?>"
               alt="<?= e($o['imageAlt'] ?? $o['lead'] ?? '') ?>"
               loading="eager" decoding="async" fetchpriority="high">
        </div>
        <?php endif; ?>

      </div>
    </section>
    <?php
}
}
