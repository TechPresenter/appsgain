<?php
/**
 * Shared pieces for the product listing and product detail pages.
 *
 * Products carry a `logo` column but none is set yet, so every helper here
 * degrades to a branded monogram rather than an empty box. Uploading a logo
 * under Admin -> Products replaces it with no template change.
 */

/**
 * Accent pair for a product, derived from its slug so a product keeps the
 * same colour on every page. Both stops sit on the deep half of the brand
 * ramp, where white text clears WCAG AA.
 */
function pdAccent(string $slug): array
{
    static $ramp = [
        ['#6A00FF', '#9D00D3'],
        ['#9D00D3', '#D000A8'],
        ['#7A00E8', '#B400C0'],
        ['#5B00D6', '#8B00E0'],
        ['#8B00E0', '#C0009B'],
        ['#6A00FF', '#B400C0'],
    ];
    $i = crc32($slug) % count($ramp);
    return $ramp[$i];
}

/** Up to two initials, for the monogram that stands in for a missing logo. */
function pdMonogram(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $skip  = ['and', 'the', 'for', 'of', '&'];
    $words = array_values(array_filter($words, static fn($w) => !in_array(mb_strtolower($w), $skip, true)));
    if (!$words) return '?';
    $first = mb_substr($words[0], 0, 1);
    $second = count($words) > 1 ? mb_substr($words[1], 0, 1) : '';
    return mb_strtoupper($first . $second);
}

/** Absolute URL for an uploaded image path, or '' when none is set. */
function pdUpload(?string $path): string
{
    $p = trim((string)$path);
    if ($p === '') return '';
    return str_starts_with($p, 'http') ? $p : UPLOADS_URL . '/' . ltrim($p, '/');
}

/**
 * The product mark: a real logo when one is uploaded, otherwise a monogram
 * in the product's own accent. $size drives the box in pixels.
 */
function pdLogo(array $product, int $size = 56, string $extraClass = ''): void
{
    $logo = pdUpload($product['logo'] ?? '');
    [$c1, $c2] = pdAccent((string)($product['slug'] ?? ''));
    $cls = 'pd-mark' . ($extraClass !== '' ? ' ' . $extraClass : '');
    $style = "--pm-size:{$size}px;--pm-c1:{$c1};--pm-c2:{$c2}";
    if ($logo !== '') {
        echo '<span class="' . e($cls) . ' pd-mark--img" style="' . e($style) . '">'
           . '<img src="' . e($logo) . '" alt="' . e((string)($product['name'] ?? '')) . ' logo" loading="lazy" decoding="async">'
           . '</span>';
        return;
    }
    echo '<span class="' . e($cls) . '" style="' . e($style) . '" aria-hidden="true">'
       . e(pdMonogram((string)($product['name'] ?? ''))) . '</span>';
}

/**
 * A JSON list column as a plain array. Older rows stored a comma-separated
 * string, so that shape is still accepted rather than dropped.
 */
function pdList($raw): array
{
    $raw = (string)$raw;
    if (trim($raw) === '') return [];
    $rows = json_decode($raw, true);
    if (is_array($rows)) {
        return array_values(array_filter(array_map(
            static fn($v) => is_string($v) ? trim($v) : '',
            $rows
        ), static fn($v) => $v !== ''));
    }
    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

/** An icon that suits the product category, for cards without a logo. */
function pdCategoryIcon(string $category): string
{
    $map = [
        'education'        => 'fa-graduation-cap',
        'hospitality'      => 'fa-utensils',
        'real estate'      => 'fa-building',
        'sales & crm'      => 'fa-chart-line',
        'enterprise'       => 'fa-sitemap',
        'human resources'  => 'fa-users',
        'healthcare'       => 'fa-stethoscope',
        'inventory'        => 'fa-boxes-stacked',
        'custom'           => 'fa-wand-magic-sparkles',
    ];
    return $map[mb_strtolower(trim($category))] ?? 'fa-cube';
}
