<?php
/**
 * Appsgain — social profile links.
 *
 * Single source of truth for the header top bar, the footer and anywhere
 * else profiles are listed, so the two can never drift apart.
 * Every URL is admin-controlled (Admin → Settings → Social).
 *
 * Returns only the platforms that actually have a URL set, in the order
 * defined below.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

if (!function_exists('agSocialLinks')) {
/**
 * @param array|null $settings  pass getAllSettings() to avoid a second query
 * @return array<int, array{url:string, icon:string, label:string, color:string}>
 */
function agSocialLinks(?array $settings = null): array {
    $s = $settings ?? getAllSettings();

    /* key => [icon, label, brand colour used on hover] */
    $platforms = [
        'site_facebook'          => ['fab fa-facebook-f',  'Facebook',         '#1877F2'],
        'site_instagram'         => ['fab fa-instagram',   'Instagram',        '#E4405F'],
        'site_twitter'           => ['fab fa-x-twitter',   'X',                '#000000'],
        'site_threads'           => ['fab fa-threads',     'Threads',          '#000000'],
        'site_youtube'           => ['fab fa-youtube',     'YouTube',          '#FF0000'],
        'site_gmb'               => ['fab fa-google',      'Google Business',  '#4285F4'],
        'site_linkedin'          => ['fab fa-linkedin-in', 'LinkedIn',         '#0A66C2'],
        'site_pinterest'         => ['fab fa-pinterest-p', 'Pinterest',        '#BD081C'],
        'site_github'            => ['fab fa-github',      'GitHub',           '#181717'],
        'site_whatsapp_channel'  => ['fab fa-whatsapp',    'WhatsApp Channel', '#128C7E'],
    ];

    $out = [];
    foreach ($platforms as $key => [$icon, $label, $color]) {
        $url = trim((string)($s[$key] ?? ''));
        if ($url === '' || $url === '#') continue;
        /* Allow an admin to paste a bare handle or domain */
        if (!preg_match('~^https?://~i', $url)) $url = 'https://' . ltrim($url, '/');
        $out[] = ['url' => $url, 'icon' => $icon, 'label' => $label, 'color' => $color];
    }
    return $out;
}
}
