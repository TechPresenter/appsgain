<?php
/**
 * Appsgain — founder page section renderer.
 *
 * Every block on /our-founder.php comes from `founder_sections`; the
 * template holds no copy of its own. One layout per block type:
 *
 *   prose   free HTML          chips  pill list of interests
 *   path    arrow-linked steps cards  milestone grid
 *   list    ticked bullets     quote  prose plus a pull-quote
 *   faq     accordion          gallery photographs
 *
 * Tokens — {name} {company} {since} {email} {education} — expand from
 * the founder settings, so a heading or FAQ written once stays correct
 * if the details change.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

if (!function_exists('fdrSections')) {
function fdrSections(): array {
    return dbFetchAll(
        "SELECT * FROM founder_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    );
}}

if (!function_exists('fdrGallery')) {
function fdrGallery(): array {
    return dbFetchAll(
        "SELECT * FROM founder_gallery WHERE is_active = 1 AND image <> '' ORDER BY sort_order ASC, id ASC"
    );
}}

if (!function_exists('fdrTokens')) {
function fdrTokens(?string $t, array $v): string {
    if ($t === null || $t === '') return '';
    return strtr($t, [
        '{name}'      => $v['name'] ?? '',
        '{company}'   => $v['company'] ?? '',
        '{since}'     => $v['since'] ?? '',
        '{email}'     => $v['email'] ?? '',
        '{education}' => $v['education'] ?? '',
        '{first}'     => $v['first'] ?? '',
    ]);
}}

if (!function_exists('fdrItems')) {
/** @return array<int,array{title:string,icon:string,text:string}> */
function fdrItems(?string $json, array $v): array {
    if (!$json) return [];
    $d = json_decode($json, true);
    if (!is_array($d)) return [];
    $out = [];
    foreach ($d as $it) {
        if (is_string($it)) { $out[] = ['title' => fdrTokens($it, $v), 'icon' => '', 'text' => '']; continue; }
        if (!is_array($it)) continue;
        $out[] = [
            'title' => fdrTokens(trim((string)($it['title'] ?? '')), $v),
            'icon'  => trim((string)($it['icon'] ?? '')),
            'text'  => fdrTokens(trim((string)($it['text'] ?? '')), $v),
        ];
    }
    return $out;
}}

if (!function_exists('fdrRenderSection')) {
function fdrRenderSection(array $s, array $v): void {
    $key   = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$s['section_key'])) ?: 'section';
    $head  = fdrTokens((string)$s['heading'], $v);
    $sub   = fdrTokens((string)$s['subheading'], $v);
    $body  = fdrTokens((string)($s['body'] ?? ''), $v);
    $items = fdrItems($s['items'] ?? null, $v);
    $img   = trim((string)($s['image'] ?? ''));
    $imgUrl = $img === '' ? '' : (str_starts_with($img, 'http') ? $img : UPLOADS_URL . '/' . ltrim($img, '/'));
    ?>
    <section class="fdr-sec" id="<?= e($key) ?>">
      <?php /* Headings are plain text — escaped here and in the sidebar,
               so an ampersand cannot end up double-encoded. */ ?>
      <?php if ($head !== ''): ?><h2 class="fdr-h2"><?= e($head) ?></h2><?php endif; ?>
      <?php if ($body !== ''): ?><div class="fdr-rich"><?= $body ?></div><?php endif; ?>

      <?php switch ($s['layout']):
        case 'chips': ?>
          <?php if ($sub !== ''): ?><h3 class="fdr-h3"><?= e($sub) ?></h3><?php endif; ?>
          <?php if ($items): ?>
          <ul class="fdr-chips">
            <?php foreach ($items as $it): ?><li class="fdr-chip"><?= e($it['title']) ?></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
        <?php break;

        case 'path': ?>
          <?php if ($items): ?>
          <div class="fdr-path">
            <?php foreach ($items as $i => $it): ?>
              <?php if ($i > 0): ?><i class="fas fa-arrow-right" aria-hidden="true"></i><?php endif; ?>
              <span><?= e($it['title']) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php break;

        case 'cards': ?>
          <?php if ($items): ?>
          <div class="fdr-cards">
            <?php foreach ($items as $it): ?>
            <div class="fdr-card">
              <span class="fdr-card-ico" aria-hidden="true"><i class="fas <?= e($it['icon'] ?: 'fa-star') ?>"></i></span>
              <h4><?= e($it['title']) ?></h4>
              <?php if ($it['text'] !== ''): ?><p><?= e($it['text']) ?></p><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php break;

        case 'list': ?>
          <?php if ($items): ?>
          <ul class="fdr-list">
            <?php foreach ($items as $it): ?>
            <li><?= e($it['title']) ?><?= $it['text'] !== '' ? ' — ' . e($it['text']) : '' ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        <?php break;

        case 'quote': ?>
          <?php if ($sub !== ''): ?>
          <blockquote class="fdr-quote">
            <p><?= e($sub) ?></p>
            <cite><strong><?= e($v['name'] ?? '') ?></strong>Founder &amp; CEO, <?= e($v['company'] ?? '') ?></cite>
          </blockquote>
          <?php endif; ?>
        <?php break;

        case 'faq': ?>
          <?php if ($items): ?>
          <div class="fdr-faq">
            <?php foreach ($items as $i => $it): ?>
            <details <?= $i === 0 ? 'open' : '' ?>>
              <summary><?= e($it['title']) ?></summary>
              <p><?= e($it['text']) ?></p>
            </details>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php break;

        case 'gallery':
          $shots = fdrGallery();
          if ($shots): ?>
          <div class="fdr-gallery">
            <?php foreach ($shots as $g):
              $gi = (string)$g['image'];
              $gu = str_starts_with($gi, 'http') ? $gi : UPLOADS_URL . '/' . ltrim($gi, '/'); ?>
            <figure class="fdr-shot">
              <img src="<?= e($gu) ?>" alt="<?= e($g['alt_text'] ?: $g['caption'] ?: ($v['name'] ?? '')) ?>"
                   loading="lazy" decoding="async">
              <?php if ($g['caption'] !== ''): ?>
              <figcaption><?= e($g['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
            <?php endforeach; ?>
          </div>
          <?php endif;
        break;

        default: /* prose — body already printed above */
          if ($imgUrl !== ''): ?>
          <img class="fdr-sec-img" src="<?= e($imgUrl) ?>" alt="<?= e($head) ?>" loading="lazy" decoding="async">
          <?php endif;
        break;
      endswitch; ?>
    </section>
    <?php
}}
