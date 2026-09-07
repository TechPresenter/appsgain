<?php
/**
 * Migration — certifications & partners marquee.
 *
 * Creates `certifications_partners` and the settings that drive the
 * section wrapper (title, subtitle, visibility, background).
 *
 * Seeds the twelve requested organisations by NAME ONLY. No logo files
 * are shipped: these are third-party trademarks and the real artwork has
 * to come from the admin. Until a logo is uploaded the marquee renders a
 * clean monogram, so the section looks finished either way.
 *
 * Safe to run repeatedly.
 *
 * Run:  php admin/migrate-certifications.php
 *   or: open /admin/migrate-certifications.php while signed in as admin.
 */
$cli = (PHP_SAPI === 'cli');
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!$cli) {
    Auth::requireAdmin();
    header('Content-Type: text/plain; charset=utf-8');
}
$log = static function (string $m): void { echo $m . "\n"; };

/* ── 1 · Table ── */
db()->exec("
CREATE TABLE IF NOT EXISTS certifications_partners (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  logo          VARCHAR(300) NOT NULL DEFAULT '',
  description   VARCHAR(300) NOT NULL DEFAULT '',
  website_url   VARCHAR(300) NOT NULL DEFAULT '',
  display_order INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order  (display_order),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log('table certifications_partners ready');

/* ── 2 · Seed ── */
$have = (int) dbFetchValue("SELECT COUNT(*) FROM certifications_partners");
if ($have > 0) {
    $log("already holds $have row(s) — nothing seeded");
} else {
    $seed = [
        ['ISO 9001:2015',        'Quality management certified'],
        ['MSME',                 'Registered micro, small & medium enterprise'],
        ['Clutch',               'Verified client reviews'],
        ['GoodFirms',            'Top-rated development company'],
        ['G2',                   'Software marketplace listing'],
        ['DesignRush',           'Accredited agency listing'],
        ['Microsoft Partner',    'Microsoft partner network'],
        ['Google Cloud Partner', 'Google Cloud partner'],
        ['Shopify Partner',      'Shopify solutions partner'],
        ['NASSCOM',              'Industry association member'],
        ['Glassdoor',            'Employer reviews'],
        ['Dun & Bradstreet',     'D&B verified business'],
    ];
    $i = 10;
    foreach ($seed as [$name, $desc]) {
        dbInsertRow('certifications_partners', [
            'name'          => $name,
            'description'   => $desc,
            'display_order' => $i,
            'is_active'     => 1,
        ]);
        $i += 10;
    }
    $log('seeded ' . count($seed) . ' organisations (names only — upload logos in the admin)');
}

/* ── 3 · Section settings ── */
$defaults = [
    'trust_section_show'     => '1',
    'trust_section_title'    => 'Trusted, certified and partnered',
    'trust_section_subtitle' => 'Independently verified, and working with the platforms our clients rely on.',
    'trust_section_bg'       => 'soft',   /* soft | white | tint */
    'trust_section_speed'    => '38',     /* seconds for one full loop */
    'trust_logo_style'       => 'color',     /* color | grayscale */
];
$added = 0;
foreach ($defaults as $k => $v) {
    $exists = dbFetchValue("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$k]);
    if (!$exists) { saveSetting($k, $v); $added++; }
}
$log($added ? "added $added section setting(s)" : 'section settings already present');

$log('');
$log('Migration complete.');
