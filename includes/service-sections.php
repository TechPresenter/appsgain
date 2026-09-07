<?php
/**
 * Appsgain — service page sections.
 *
 * Loads the section rows for a service and renders them. Everything that
 * used to be literal copy in service-detail.php — process steps, why-us
 * bullets, quote, every button label and URL, every section heading —
 * now comes from `service_sections` and is editable in the admin.
 *
 * Resolution order per section_key:
 *   1. a row for this service        (an override)
 *   2. the row with service_id NULL  (the global default)
 * so an admin edits the process once and it applies everywhere, while
 * still being able to tailor one service.
 *
 * {service} in any heading, body or button label expands to the service
 * name, which is what keeps a shared default from reading generically.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

if (!function_exists('svcSectionsFor')) {
/**
 * @return array<int,array<string,mixed>> ordered, overrides already applied
 */
function svcSectionsFor(int $serviceId): array {
    $rows = dbFetchAll(
        "SELECT * FROM service_sections
         WHERE is_active = 1 AND (service_id = ? OR service_id IS NULL)
         ORDER BY sort_order ASC, id ASC",
        [$serviceId]
    );

    /* Service-specific rows win over the global default sharing a key.
       Anything with a key of its own (custom sections) is simply kept. */
    $bySlot = [];
    foreach ($rows as $r) {
        $key = $r['section_key'] !== '' ? $r['section_key'] : ('row-' . $r['id']);
        $slot = $r['service_id'] !== null ? 'own:' . $key : 'def:' . $key;
        $bySlot[$slot] = $r;
    }
    $out = [];
    foreach ($bySlot as $slot => $r) {
        if (str_starts_with($slot, 'def:')) {
            $key = substr($slot, 4);
            if (isset($bySlot['own:' . $key])) continue;   /* overridden */
        }
        $out[] = $r;
    }
    usort($out, fn($a, $b) => ($a['sort_order'] <=> $b['sort_order']) ?: ($a['id'] <=> $b['id']));
    return $out;
}}

if (!function_exists('svcTokens')) {
/** Expand {service} (and {group}) in admin-entered copy. */
function svcTokens(?string $text, array $service): string {
    if ($text === null || $text === '') return '';
    return strtr($text, [
        '{service}' => (string)($service['name'] ?? ''),
        '{group}'   => (string)($service['service_group'] ?: ($service['category'] ?? 'Services')),
    ]);
}}

if (!function_exists('svcUrl')) {
/** Admin may enter a full URL, a site-relative path, or a tel:/mailto:. */
function svcUrl(string $u): string {
    $u = trim($u);
    if ($u === '') return '';
    if (preg_match('~^(https?:|mailto:|tel:|#)~i', $u)) return $u;
    return rtrim(SITE_URL, '/') . '/' . ltrim($u, '/');
}}

if (!function_exists('svcItems')) {
/** @return array<int,array{title:string,icon:string,text:string}> */
function svcItems(?string $json): array {
    if (!$json) return [];
    $d = json_decode($json, true);
    if (!is_array($d)) return [];
    $out = [];
    foreach ($d as $it) {
        if (is_string($it)) { $out[] = ['title' => '', 'icon' => '', 'text' => $it]; continue; }
        if (!is_array($it)) continue;
        $out[] = [
            'title' => trim((string)($it['title'] ?? '')),
            'icon'  => trim((string)($it['icon']  ?? '')),
            'text'  => trim((string)($it['text']  ?? '')),
        ];
    }
    return $out;
}}

if (!function_exists('svcSectionButtons')) {
function svcSectionButtons(array $s, array $service, string $primaryClass = 'sx-btn-primary'): void {
    $b1t = svcTokens($s['btn1_text'] ?? '', $service);
    $b2t = svcTokens($s['btn2_text'] ?? '', $service);
    if ($b1t === '' && $b2t === '') return;
    echo '<div class="sx-actions">';
    if ($b1t !== '' && ($s['btn1_url'] ?? '') !== '') {
        printf('<a href="%s" class="sx-btn %s">%s</a>',
            e(svcUrl($s['btn1_url'])), e($primaryClass), e($b1t));
    }
    if ($b2t !== '' && ($s['btn2_url'] ?? '') !== '') {
        printf('<a href="%s" class="sx-btn sx-btn-ghost">%s</a>',
            e(svcUrl($s['btn2_url'])), e($b2t));
    }
    echo '</div>';
}}

if (!function_exists('svcRenderSection')) {
/**
 * @param array $s        the section row
 * @param array $service  the service row
 * @param array $ctx      ['features'=>string[], 'related'=>rows[], 'icon'=>string]
 */
function svcRenderSection(array $s, array $service, array $ctx = []): void {
    $bgClass = match ($s['bg'] ?? 'white') {
        'soft'  => 'sx-bg-soft',
        'lav'   => 'sx-bg-lav',
        default => 'sx-bg-white',
    };
    $eyebrow = svcTokens($s['eyebrow']    ?? '', $service);
    $lead    = svcTokens($s['lead']       ?? '', $service);
    $accent  = svcTokens($s['accent']     ?? '', $service);
    $sub     = svcTokens($s['subheading'] ?? '', $service);
    $body    = svcTokens($s['body']       ?? '', $service);
    $items   = svcItems($s['items'] ?? null);
    $anchor  = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$s['section_key'])) ?: 'section';

    switch ($s['layout'] ?? 'richtext') {

        /* ── Numbered feature grid; items come from the service's own features ── */
        case 'numbered':
            $feats = $ctx['features'] ?? [];
            if (!$feats && !$items) return;
            if (!$feats) { $feats = array_map(fn($i) => $i['text'] ?: $i['title'], $items); }
            ?>
            <section class="sx-section <?= $bgClass ?>" id="<?= e($anchor) ?>">
              <div class="sx-wrap">
                <?php svcHeading($eyebrow, $lead, $accent, $sub); ?>
                <div class="sd-feats">
                  <?php foreach ($feats as $i => $f): ?>
                  <div class="sd-feat">
                    <span class="sd-feat-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <p><?= e($f) ?></p>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php svcSectionButtons($s, $service); ?>
              </div>
            </section>
            <?php
            break;

        /* ── Ordered delivery steps ── */
        case 'process':
            if (!$items) return; ?>
            <section class="sx-section <?= $bgClass ?>" id="<?= e($anchor) ?>">
              <div class="sx-wrap">
                <?php svcHeading($eyebrow, $lead, $accent, $sub); ?>
                <ol class="sx-proc">
                  <?php foreach ($items as $i => $it): ?>
                  <li class="sx-proc-step">
                    <span class="sx-proc-dot"><i class="fas <?= e($it['icon'] ?: 'fa-circle') ?>" aria-hidden="true"></i></span>
                    <span class="sx-proc-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= e($it['title']) ?></h3>
                    <p><?= e($it['text']) ?></p>
                  </li>
                  <?php endforeach; ?>
                </ol>
                <?php svcSectionButtons($s, $service); ?>
              </div>
            </section>
            <?php
            break;

        /* ── Bullets on the left, pull-quote card on the right ── */
        case 'split-quote':
            ?>
            <section class="sx-section <?= $bgClass ?>" id="<?= e($anchor) ?>">
              <div class="sx-wrap sd-why">
                <div>
                  <?php svcHeading($eyebrow, $lead, $accent, '', 'left'); ?>
                  <?php if ($items): ?>
                  <ul class="sd-why-list">
                    <?php foreach ($items as $it): ?>
                    <li><?php if ($it['title']): ?><strong><?= e($it['title']) ?></strong> <?php endif; ?><?= e($it['text']) ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <?php endif; ?>
                  <?php if (($s['btn1_text'] ?? '') !== ''): ?>
                    <a href="<?= e(svcUrl($s['btn1_url'])) ?>" class="sx-btn sx-btn-ghost"><?= e(svcTokens($s['btn1_text'], $service)) ?></a>
                  <?php endif; ?>
                </div>
                <?php if ($body || ($s['btn2_text'] ?? '') !== ''): ?>
                <aside class="sd-quote">
                  <span class="sd-quote-mark" aria-hidden="true">&rdquo;</span>
                  <?php if ($body): ?><p><?= e($body) ?></p><?php endif; ?>
                  <?php if (($s['btn2_text'] ?? '') !== ''): ?>
                    <a href="<?= e(svcUrl($s['btn2_url'])) ?>" class="sx-btn sx-btn-primary"><?= e(svcTokens($s['btn2_text'], $service)) ?></a>
                  <?php endif; ?>
                </aside>
                <?php endif; ?>
              </div>
            </section>
            <?php
            break;

        /* ── Sibling service cards ── */
        case 'related':
            $rel = $ctx['related'] ?? [];
            if (!$rel) return; ?>
            <section class="sx-section <?= $bgClass ?>" id="<?= e($anchor) ?>">
              <div class="sx-wrap">
                <?php svcHeading($eyebrow, $lead, $accent, $sub); ?>
                <div class="sx-grid sd-related">
                  <?php foreach ($rel as $r) svcCard($r, 'standard'); ?>
                </div>
                <?php if (($s['btn1_text'] ?? '') !== ''): ?>
                <div class="sd-related-all">
                  <a href="<?= e(svcUrl($s['btn1_url'])) ?>" class="sx-btn sx-btn-ghost"><?= e(svcTokens($s['btn1_text'], $service)) ?></a>
                </div>
                <?php endif; ?>
              </div>
            </section>
            <?php
            break;

        /* ── Closing call to action ── */
        case 'cta':
            $icon = $ctx['icon'] ?? 'fa-cog'; ?>
            <section class="sx-cta" id="<?= e($anchor) ?>">
              <div class="sx-wrap sx-cta-in">
                <div>
                  <h2 class="sx-h2"><?= e($lead) ?><?php if ($accent): ?> <span class="sx-grad"><?= e($accent) ?></span><?php endif; ?></h2>
                  <?php if ($sub): ?><p class="sx-sub"><?= e($sub) ?></p><?php endif; ?>
                  <?php svcSectionButtons($s, $service); ?>
                </div>
                <div class="sx-cta-visual" aria-hidden="true">
                  <span class="sx-cta-ring"></span>
                  <span class="sx-cta-ring sx-cta-ring2"></span>
                  <i class="fas <?= e($icon) ?>"></i>
                </div>
              </div>
            </section>
            <?php
            break;

        /* ── Free-form: heading, optional image, admin HTML ── */
        case 'richtext':
        default:
            if (!$lead && !$accent && !$body && !($s['image'] ?? '')) return;
            $img = trim((string)($s['image'] ?? '')); ?>
            <section class="sx-section <?= $bgClass ?>" id="<?= e($anchor) ?>">
              <div class="sx-wrap<?= $img ? ' sd-why' : '' ?>">
                <div>
                  <?php if ($lead || $accent) svcHeading($eyebrow, $lead, $accent, $sub, $img ? 'left' : 'center'); ?>
                  <?php if ($body): ?><div class="sx-lead sd-desc"><?= $body /* trusted admin HTML */ ?></div><?php endif; ?>
                  <?php svcSectionButtons($s, $service); ?>
                </div>
                <?php if ($img): ?>
                <div class="sd-hero-visual">
                  <img src="<?= e(str_starts_with($img, 'http') ? $img : UPLOADS_URL . '/' . ltrim($img, '/')) ?>"
                       alt="<?= e($lead ?: $service['name']) ?>" loading="lazy" decoding="async">
                </div>
                <?php endif; ?>
              </div>
            </section>
            <?php
            break;
    }
}}
