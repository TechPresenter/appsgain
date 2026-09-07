<?php
/**
 * Migration — service page sections.
 *
 * Creates `service_sections` and seeds it from the copy that was previously
 * hardcoded in service-detail.php, so switching the template over to the
 * database loses nothing and the admin immediately sees the real page
 * content rather than empty fields.
 *
 * Rows with service_id NULL are GLOBAL DEFAULTS: they render on every
 * service page. A row with a service_id overrides the global default that
 * shares its section_key, so an admin can edit the process once for the
 * whole site and still tailor it for one service.
 *
 * Safe to run repeatedly — it creates only what is missing.
 *
 * Run:  php admin/migrate-service-sections.php
 *   or: open /admin/migrate-service-sections.php while signed in as admin.
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
CREATE TABLE IF NOT EXISTS service_sections (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id    INT UNSIGNED NULL COMMENT 'NULL = global default for every service',
  section_key   VARCHAR(50)  NOT NULL COMMENT 'features|process|why|cta|richtext|…',
  layout        VARCHAR(30)  NOT NULL DEFAULT 'richtext',
  eyebrow       VARCHAR(120) NOT NULL DEFAULT '',
  lead          VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'plain part of the heading',
  accent        VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'gradient part of the heading',
  subheading    VARCHAR(500) NOT NULL DEFAULT '',
  body          MEDIUMTEXT   NULL COMMENT 'trusted admin HTML',
  items         MEDIUMTEXT   NULL COMMENT 'JSON: [{title,icon,text}]',
  image         VARCHAR(300) NOT NULL DEFAULT '',
  btn1_text     VARCHAR(120) NOT NULL DEFAULT '',
  btn1_url      VARCHAR(300) NOT NULL DEFAULT '',
  btn2_text     VARCHAR(120) NOT NULL DEFAULT '',
  btn2_url      VARCHAR(300) NOT NULL DEFAULT '',
  bg            VARCHAR(20)  NOT NULL DEFAULT 'white' COMMENT 'white|soft|lav',
  sort_order    INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_svc  (service_id),
  INDEX idx_key  (section_key),
  INDEX idx_sort (sort_order),
  CONSTRAINT fk_svcsec_service FOREIGN KEY (service_id)
    REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log('table service_sections ready');

/* ── 2 · Seed global defaults, only if none exist ── */
$have = (int) dbFetchValue("SELECT COUNT(*) FROM service_sections WHERE service_id IS NULL");
if ($have > 0) {
    $log("global defaults already present ($have rows) — nothing seeded");
} else {

    $defaults = [

        /* What's included — items come from each service's own features JSON,
           so this row only carries the heading and layout. */
        [
            'section_key' => 'features',
            'layout'      => 'numbered',
            'eyebrow'     => 'What you get',
            'lead'        => 'Included in',
            'accent'      => '{service}',
            'subheading'  => 'Every engagement covers these as standard — no line items appearing halfway through.',
            'bg'          => 'white',
            'sort_order'  => 10,
        ],

        [
            'section_key' => 'process',
            'layout'      => 'process',
            'eyebrow'     => 'How we deliver',
            'lead'        => 'From Brief to',
            'accent'      => 'Live Product',
            'bg'          => 'soft',
            'sort_order'  => 20,
            'items'       => json_encode([
                ['title' => 'Discovery',        'icon' => 'fa-magnifying-glass', 'text' => 'We map the problem, users and constraints before a line of code exists.'],
                ['title' => 'Strategy',         'icon' => 'fa-compass-drafting', 'text' => 'Scope, architecture, stack and a delivery plan you can hold us to.'],
                ['title' => 'Design',           'icon' => 'fa-pen-ruler',        'text' => 'Wireframes to a working design system — reviewed with you, not at you.'],
                ['title' => 'Development',      'icon' => 'fa-code',             'text' => 'Two-week sprints, demo builds throughout, no black-box phases.'],
                ['title' => 'Launch & Support', 'icon' => 'fa-rocket',           'text' => 'Deploy, monitor, iterate. We stay on after go-live.'],
            ], JSON_UNESCAPED_UNICODE),
        ],

        [
            'section_key' => 'why',
            'layout'      => 'split-quote',
            'lead'        => 'What Working With Us Actually',
            'accent'      => 'Looks Like',
            'bg'          => 'lav',
            'sort_order'  => 30,
            'btn1_text'   => 'See work we have shipped',
            'btn1_url'    => '/portfolio.php',
            'btn2_text'   => 'Book a scoping call',
            'btn2_url'    => '/contact.php#enquiry',
            'body'        => 'Tell us the problem you are solving. We will scope it, price it, and tell you '
                           . 'honestly whether {service} is the right answer — or whether something smaller '
                           . 'gets you there faster.',
            'items'       => json_encode([
                ['title' => 'Senior engineers on the work.', 'text' => 'The people in your kickoff call write the code.'],
                ['title' => 'Fortnightly demos.',            'text' => 'You see running software throughout, not a reveal at the end.'],
                ['title' => 'Honest scoping.',               'text' => 'If this needs less budget than you planned, we say so early.'],
                ['title' => 'You own everything.',           'text' => 'Source, infrastructure and accounts are yours from day one.'],
            ], JSON_UNESCAPED_UNICODE),
        ],

        [
            'section_key' => 'related',
            'layout'      => 'related',
            'eyebrow'     => 'Keep exploring',
            'lead'        => 'Related',
            'accent'      => 'Services',
            'bg'          => 'white',
            'sort_order'  => 40,
            'btn1_text'   => 'View all services',
            'btn1_url'    => '/services.php',
        ],

        [
            'section_key' => 'cta',
            'layout'      => 'cta',
            'lead'        => 'Ready to Start',
            'accent'      => '{service}?',
            'subheading'  => 'Send us the brief. You will hear back within one business day with questions, '
                           . 'a rough shape and an honest view on fit.',
            'sort_order'  => 50,
            'btn1_text'   => 'Start Your Project',
            'btn1_url'    => '/contact.php#enquiry',
            'btn2_text'   => 'Talk to Our Team',
            'btn2_url'    => '/contact.php',
        ],
    ];

    foreach ($defaults as $row) {
        $row['service_id'] = null;
        dbInsertRow('service_sections', $row);
        $log("  seeded default: {$row['section_key']}");
    }
    $log('seeded ' . count($defaults) . ' global defaults from the previously hardcoded copy');
}

/* ── 3 · Hero button labels live on the service row ── */
$cols = [];
foreach (dbFetchAll("SHOW COLUMNS FROM services") as $c) { $cols[$c['Field']] = true; }
$add = [];
if (!isset($cols['hero_btn_text'])) $add[] = "ADD COLUMN hero_btn_text VARCHAR(120) NOT NULL DEFAULT 'Start This Project'";
if (!isset($cols['hero_btn_url']))  $add[] = "ADD COLUMN hero_btn_url  VARCHAR(300) NOT NULL DEFAULT '/contact.php#enquiry'";
if (!isset($cols['og_image']))      $add[] = "ADD COLUMN og_image      VARCHAR(300) NOT NULL DEFAULT ''";
if (!isset($cols['canonical_url'])) $add[] = "ADD COLUMN canonical_url VARCHAR(300) NOT NULL DEFAULT ''";
if ($add) {
    db()->exec("ALTER TABLE services " . implode(', ', $add));
    $log('services: added ' . count($add) . ' column(s) — ' . implode(', ', array_map(
        fn($s) => explode(' ', trim($s))[2], $add)));
} else {
    $log('services: hero button / SEO columns already present');
}

$log('');
$log('Migration complete.');
