<?php
/**
 * Section images.
 *
 * Several sections drew their artwork in markup — the About "who we are"
 * panel, the services CTA, and the banner on all seventeen pages that use
 * pageHero(). Those looked fine but could not be changed without editing a
 * template, so every one of them was effectively a hardcoded image.
 *
 * Each slot now resolves through here. An uploaded file wins; when none is
 * set the original built-in artwork still renders, so nothing changes until
 * someone actually uploads something.
 *
 * Slots live in `settings` under keys prefixed `secimg_`, which is what the
 * admin screen enumerates.
 */

/**
 * Every slot the site offers, grouped for the admin screen.
 * key => [group, label, hint, recommended size]
 */
function sectionImageSlots(): array
{
    return [
        /* ── Page banners (pageHero) ── */
        'secimg_hero_about'      => ['Page Banners', 'About',            'Banner beside the About page heading.',        '900×600'],
        'secimg_hero_contact'    => ['Page Banners', 'Contact',          'Banner beside the Contact heading.',           '900×600'],
        'secimg_hero_careers'    => ['Page Banners', 'Careers',          'Banner beside the Careers heading.',           '900×600'],
        'secimg_hero_portfolio'  => ['Page Banners', 'Portfolio',        'Banner beside the Portfolio heading.',         '900×600'],
        'secimg_hero_products'   => ['Page Banners', 'Products',         'Banner beside the Products heading.',          '900×600'],
        'secimg_hero_apps'       => ['Page Banners', 'Mobile Apps',      'Banner beside the Apps heading.',              '900×600'],
        'secimg_hero_partners'   => ['Page Banners', 'Partners',         'Banner beside the Partners heading.',          '900×600'],
        'secimg_hero_faq'        => ['Page Banners', 'FAQ',              'Banner beside the FAQ heading.',               '900×600'],
        'secimg_hero_gallery'    => ['Page Banners', 'Gallery',          'Banner beside the Gallery heading.',           '900×600'],
        'secimg_hero_legal'      => ['Page Banners', 'Policy pages',     'Shared banner for privacy, terms, refunds and the other policy pages.', '900×600'],

        /* ── In-page sections ── */
        'secimg_about_panel'     => ['Sections', 'About — Who we are',   'Replaces the dashboard illustration in that section.', '900×700'],
        'secimg_services_cta'    => ['Sections', 'Services — closing CTA', 'Replaces the artwork in the final Services band.',   '800×600'],
        'secimg_services_hero'   => ['Sections', 'Services — hero',      'Set under Services Page → Hero; shown here for reference.', '1000×750'],
    ];
}

/**
 * The URL for a slot, or '' when nothing is uploaded.
 * Callers use the empty string to decide whether to fall back to the
 * built-in artwork.
 */
function sectionImage(string $key): string
{
    /* The services hero already had its own setting before this system
       existed; keep reading that one so it is not orphaned. */
    static $alias = ['secimg_services_hero' => 'sp_hero_image'];
    $lookup = $alias[$key] ?? $key;

    $v = trim((string)getSetting($lookup, ''));
    if ($v === '') return '';
    return str_starts_with($v, 'http') ? $v : UPLOADS_URL . '/' . ltrim($v, '/');
}

/** The stored path (not URL) — what the admin form needs to show and clear. */
function sectionImagePath(string $key): string
{
    static $alias = ['secimg_services_hero' => 'sp_hero_image'];
    return trim((string)getSetting($alias[$key] ?? $key, ''));
}

/**
 * Render a slot as an <img>, or nothing when it is empty.
 * Returns true when it drew something, so the caller can skip its fallback.
 */
function sectionImageTag(string $key, string $alt = '', string $class = '', bool $eager = false): bool
{
    $src = sectionImage($key);
    if ($src === '') return false;
    printf(
        '<img src="%s" alt="%s"%s loading="%s" decoding="async">',
        e($src),
        e($alt),
        $class !== '' ? ' class="' . e($class) . '"' : '',
        $eager ? 'eager' : 'lazy'
    );
    return true;
}
