<?php
/**
 * Appsgain — reusable Services page components.
 *
 * Each function renders one block. Service data always comes from the
 * `services` table, so names, routes, descriptions and images stay
 * admin-controlled; nothing here hardcodes a service.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

/** Per-service visual identity: accent pair + illustrative motif. */
function svcAccent(string $slug): array {
    static $map = [
        'custom-software-development' => ['#FF8A00', '#FF3030', 'fa-code'],
        'erp-development'             => ['#FF3030', '#F50072', 'fa-chart-line'],
        'crm-development'             => ['#F50072', '#D000A8', 'fa-users-gear'],
        'cms-development'             => ['#D000A8', '#A800C8', 'fa-pen-to-square'],
        'mobile-app-development'      => ['#A800C8', '#8A00E0', 'fa-mobile-screen-button'],
        'saas-platform-development'   => ['#8A00E0', '#6A00FF', 'fa-cloud'],
        'web-portal-development'      => ['#6A00FF', '#4B00CC', 'fa-window-maximize'],
        'ai-product-development'      => ['#FF8A00', '#F50072', 'fa-brain'],
        'enterprise-applications'     => ['#F50072', '#6A00FF', 'fa-building-columns'],
        'automation-solutions'        => ['#FF3030', '#D000A8', 'fa-diagram-project'],
        'devops-solutions'            => ['#D000A8', '#6A00FF', 'fa-server'],
        'database-services'           => ['#6A00FF', '#FF8A00', 'fa-database'],
    ];
    return $map[$slug] ?? ['#6A00FF', '#F50072', 'fa-cog'];
}

/** Resolve a service image to a <picture>-ready base path, or null. */
function svcImageBase(array $svc): ?string {
    $img = trim((string)($svc['image'] ?? ''));
    if ($img === '') return null;
    return preg_replace('/\.(jpg|jpeg|png|webp)$/i', '', $img);
}

/**
 * The two sources a service card needs: a webp for <source> when one was
 * generated alongside the artwork, and a real fallback file for <img>.
 * Returns null when nothing resolves, so the card renders without media
 * rather than with a broken image.
 *
 * @return array{0:?string,1:string}|null  [webp path or null, img path]
 */
function svcImagePair(array $svc): ?array {
    $img = trim((string)($svc['image'] ?? ''));
    if ($img === '') return null;
    $img  = ltrim($img, '/');
    $base = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '', $img);
    $dir  = rtrim(ROOT_PATH, '/\\') . '/uploads/';

    $webp = is_file($dir . $base . '.webp') ? $base . '.webp' : null;

    /* Prefer a jpg sibling (the seeded artwork ships one), then whatever
       was actually uploaded. */
    foreach ([$base . '.jpg', $base . '.jpeg', $base . '.png', $img] as $cand) {
        if (is_file($dir . $cand)) return [$webp, $cand];
    }
    /* Only a webp on disk: still usable as the img source. */
    if ($webp !== null) return [null, $webp];
    return null;
}

/**
 * One service card.
 * $variant: 'featured' (large, with image) | 'standard'
 */
function svcCard(array $svc, string $variant = 'standard'): void {
    [$c1, $c2, $fallbackIcon] = svcAccent($svc['slug']);
    $icon = $svc['icon'] ?: $fallbackIcon;
    $desc = truncate(strip_tags((string)($svc['short_description'] ?: ($svc['description'] ?? ''))),
                     $variant === 'featured' ? 180 : 104);
    $base = svcImageBase($svc);
    $url  = SITE_URL . '/service/' . $svc['slug'];
    ?>
    <article class="sx-card<?= $variant === 'featured' ? ' sx-card--featured' : '' ?>"
             style="--c1:<?= e($c1) ?>;--c2:<?= e($c2) ?>"
             data-group="<?= e($svc['service_group'] ?: 'Services') ?>">
        <?php $pair = $variant === 'featured' ? svcImagePair($svc) : null; ?>
        <?php if ($pair): [$webpSrc, $imgSrc] = $pair; ?>
        <div class="sx-card-media">
            <picture>
                <?php if ($webpSrc !== null): ?>
                <source srcset="<?= UPLOADS_URL ?>/<?= e($webpSrc) ?>" type="image/webp">
                <?php endif; ?>
                <img src="<?= UPLOADS_URL ?>/<?= e($imgSrc) ?>"
                     alt="<?= e($svc['name']) ?>" width="1200" height="750"
                     loading="lazy" decoding="async">
            </picture>
        </div>
        <?php endif; ?>

        <div class="sx-card-body">
            <span class="sx-card-icon"><i class="fas <?= e($icon) ?>" aria-hidden="true"></i></span>
            <h3 class="sx-card-title"><?= e($svc['name']) ?></h3>
            <p class="sx-card-desc"><?= e($desc) ?></p>
            <span class="sx-card-link">
                Explore Service <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </span>
        </div>

        <a class="sx-card-hit" href="<?= e($url) ?>"
           aria-label="<?= e($svc['name']) ?> &mdash; explore this service"></a>
    </article>
    <?php
}

/** Section heading block. */
function svcHeading(string $eyebrow, string $lead, string $accent, string $sub = '', string $align = 'center'): void {
    ?>
    <div class="sx-head<?= $align === 'left' ? ' sx-head--left' : '' ?>">
        <?php if ($eyebrow): ?><span class="sx-eyebrow"><?= e($eyebrow) ?></span><?php endif; ?>
        <h2 class="sx-h2"><?= e($lead) ?> <span class="sx-grad"><?= e($accent) ?></span></h2>
        <?php if ($sub): ?><p class="sx-sub"><?= e($sub) ?></p><?php endif; ?>
    </div>
    <?php
}

/** Grid of service cards for one group, first card featured. */
function svcGrid(array $list, bool $featureFirst = true): void {
    if (!$list) return;
    echo '<div class="sx-grid">';
    foreach ($list as $i => $svc) {
        svcCard($svc, ($featureFirst && $i === 0) ? 'featured' : 'standard');
    }
    echo '</div>';
}
