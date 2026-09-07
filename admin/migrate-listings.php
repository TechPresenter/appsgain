<?php
/**
 * Migration — "Our Product Listed On" badges.
 *
 * Reuses `certifications_partners` rather than adding a parallel table:
 * the fields are identical (name, logo, link, order, active), only the
 * rendering differs. A `section` column separates the two groups:
 *
 *   trust   → certifications / partners marquee (scrolling chips)
 *   listed  → launch-directory badges (static grid)
 *
 * Also adds `logo_url`, because these directories publish a hosted badge
 * image you are meant to hotlink rather than re-upload.
 *
 * Seeds the nine directories by NAME ONLY. Their badge artwork and
 * listing URLs belong to them — paste the real badge URL and link in the
 * admin. A wrong outbound link is worse than a blank one.
 *
 * Safe to run repeatedly.
 */
$cli = (PHP_SAPI === 'cli');
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!$cli) {
    Auth::requireAdmin();
    header('Content-Type: text/plain; charset=utf-8');
}
$log = static function (string $m): void { echo $m . "\n"; };

/* ── 1 · Columns ── */
$cols = [];
foreach (dbFetchAll("SHOW COLUMNS FROM certifications_partners") as $c) { $cols[$c['Field']] = true; }

$add = [];
if (!isset($cols['section'])) {
    $add[] = "ADD COLUMN section VARCHAR(20) NOT NULL DEFAULT 'trust' AFTER id";
}
if (!isset($cols['logo_url'])) {
    $add[] = "ADD COLUMN logo_url VARCHAR(500) NOT NULL DEFAULT '' AFTER logo";
}
if ($add) {
    db()->exec("ALTER TABLE certifications_partners " . implode(', ', $add));
    $log('added column(s): ' . implode(', ', array_map(fn($s) => explode(' ', trim($s))[2], $add)));
} else {
    $log('columns already present');
}
if (!isset($cols['section'])) {
    db()->exec("CREATE INDEX idx_section ON certifications_partners (section)");
    dbExecute("UPDATE certifications_partners SET section = 'trust' WHERE section = '' OR section IS NULL");
    $log('existing rows assigned to the trust section');
}

/* ── 2 · Seed the directories ── */
$have = (int) dbFetchValue("SELECT COUNT(*) FROM certifications_partners WHERE section = 'listed'");
if ($have > 0) {
    $log("listed section already holds $have row(s) — nothing seeded");
} else {
    /* Only the two domains legible in the supplied artwork are filled in;
       the rest are left blank rather than guessed. */
    $seed = [
        ['Fazier',        ''],
        ['FoundrList',    ''],
        ['ToolFame',      ''],
        ['Tiny Startups', 'https://tinystartups.com'],
        ['Turbo0',        'https://turbo0.com'],
        ['SaaSFame',      ''],
        ['SaaSHub',       ''],
        ['ShowMeBest.ai', ''],
        ['NoonLaunch',    ''],
    ];
    $i = 10;
    foreach ($seed as [$name, $url]) {
        dbInsertRow('certifications_partners', [
            'section'       => 'listed',
            'name'          => $name,
            'website_url'   => $url,
            'display_order' => $i,
            'is_active'     => 1,
        ]);
        $i += 10;
    }
    $log('seeded ' . count($seed) . ' directories (add each badge image + listing URL in the admin)');
}

/* ── 3 · Section settings ── */
$defaults = [
    'listed_section_show'     => '1',
    'listed_section_title'    => 'Our Product Listed On',
    'listed_section_subtitle' => 'Find us on the platforms where builders discover new software.',
    'listed_section_bg'       => 'white',
];
$added = 0;
foreach ($defaults as $k => $v) {
    if (!dbFetchValue("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$k])) {
        saveSetting($k, $v, 'trust');
        $added++;
    }
}
$log($added ? "added $added setting(s)" : 'settings already present');

$log('');
$log('Migration complete.');
