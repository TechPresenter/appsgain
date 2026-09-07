<?php
/**
 * Appsgain Admin — navigation.
 *
 * Flat sections rather than accordions: every link is reachable in one
 * click, with uppercase micro-cap labels carrying the grouping. The tree
 * is data, so one array entry adds a link and grouping cannot drift the
 * way it did in the old hand-written markup (Invoices listed twice,
 * access control filed under "Leads").
 *
 * Link shape
 *   label  string  visible text
 *   icon   string  Font Awesome 6 solid glyph — one icon language throughout
 *   url    string  path under ADMIN_URL
 *   key    string  matches $adminPage
 *   q      array   expected query state, e.g. ['status' => 'new']
 *   badge  string  name of a count passed to agRenderNav()
 *
 * Active state: a link is current when its key matches AND every
 * discriminating query parameter equals what the link declares. That is
 * what stops "All Leads" also lighting up on ?status=new.
 */
if (!defined('ADMIN_URL')) { die('Direct access not allowed'); }

/* Query keys that distinguish sibling links pointing at the same page. */
const AG_NAV_DISCRIMINATORS = ['status', 'type', 'tab'];

$AG_NAV = [

  /* Unlabelled lead block — the screens opened most often */
  ['section' => null, 'links' => [
    ['label' => 'Dashboard',    'icon' => 'fa-gauge-high',        'url' => '/dashboard.php',                'key' => 'dashboard'],
    ['label' => 'All Leads',    'icon' => 'fa-inbox',             'url' => '/pages/leads.php',              'key' => 'leads', 'badge' => 'newLeads'],
    ['label' => 'New Leads',    'icon' => 'fa-bolt',              'url' => '/pages/leads.php?status=new',   'key' => 'leads', 'q' => ['status' => 'new']],
    ['label' => 'Converted',    'icon' => 'fa-circle-check',      'url' => '/pages/leads.php?status=converted', 'key' => 'leads', 'q' => ['status' => 'converted']],
    ['label' => 'Contact Form', 'icon' => 'fa-envelope-open-text','url' => '/pages/leads.php?type=enquiry', 'key' => 'leads', 'q' => ['type' => 'enquiry']],
    ['label' => 'Chatbot Leads','icon' => 'fa-comments',          'url' => '/pages/chatbot-leads.php',      'key' => 'chatbot-leads', 'badge' => 'newChatLeads'],
    /* Every conversation, lead or not — sits beside the leads it produced. */
    ['label' => 'Chat Sessions','icon' => 'fa-comment-dots',      'url' => '/pages/chatbot-sessions.php',   'key' => 'chatbot-sessions', 'badge' => 'newChatSessions'],
  ]],

  ['section' => 'Website Core', 'links' => [
    ['label' => 'Site Content',      'icon' => 'fa-pen-to-square',  'url' => '/pages/site-content.php', 'key' => 'site-content'],
    ['label' => 'Hero Slider',       'icon' => 'fa-images',         'url' => '/pages/hero.php',         'key' => 'hero'],
    ['label' => 'Services',          'icon' => 'fa-layer-group',    'url' => '/pages/services.php',     'key' => 'services'],
    ['label' => 'Service Sections',  'icon' => 'fa-table-cells-large', 'url' => '/pages/service-sections.php', 'key' => 'service-sections'],
    ['label' => 'Services Page', 'icon' => 'fa-file-lines', 'url' => '/pages/services-page.php', 'key' => 'services-page'],
    ['label' => 'Portfolio',         'icon' => 'fa-briefcase',      'url' => '/pages/projects.php',     'key' => 'projects'],
    ['label' => 'Testimonials',      'icon' => 'fa-quote-left',     'url' => '/pages/testimonials.php', 'key' => 'testimonials'],
    ['label' => 'FAQs',              'icon' => 'fa-circle-question','url' => '/pages/faqs.php',         'key' => 'faqs'],
    ['label' => 'Sections & Blocks', 'icon' => 'fa-puzzle-piece',   'url' => '/pages/content.php',      'key' => 'content'],
    ['label' => 'Legal Pages',       'icon' => 'fa-scale-balanced', 'url' => '/pages/legal.php',        'key' => 'legal'],
    ['label' => 'Media Library',     'icon' => 'fa-folder-open',    'url' => '/pages/media.php',        'key' => 'media'],
    ['label' => 'Section Images', 'icon' => 'fa-images', 'url' => '/pages/section-images.php', 'key' => 'section-images'],
    ['label' => 'Gallery',           'icon' => 'fa-image',          'url' => '/pages/gallery.php',      'key' => 'gallery'],
  ]],

  ['section' => 'Blog', 'links' => [
    ['label' => 'All Posts',  'icon' => 'fa-newspaper', 'url' => '/pages/blogs.php',               'key' => 'blogs', 'badge' => 'newComments'],
    ['label' => 'Write Post', 'icon' => 'fa-feather',   'url' => '/pages/blogs.php?action=create', 'key' => 'blog-add'],
    ['label' => 'Categories', 'icon' => 'fa-tags',      'url' => '/pages/blog-cats.php',           'key' => 'blog-cats'],
  ]],

  ['section' => 'Catalogue & Careers', 'links' => [
    ['label' => 'Products',     'icon' => 'fa-box',            'url' => '/pages/products.php',     'key' => 'products'],
    ['label' => 'Mobile Apps',  'icon' => 'fa-mobile-screen',  'url' => '/pages/apps.php',         'key' => 'apps'],
    ['label' => 'Job Openings', 'icon' => 'fa-bullhorn',       'url' => '/pages/jobs.php',         'key' => 'jobs'],
    ['label' => 'Applications', 'icon' => 'fa-file-signature', 'url' => '/pages/applications.php', 'key' => 'applications'],
  ]],

  ['section' => 'People', 'links' => [
    ['label' => 'Founder Page', 'icon' => 'fa-user-tie',   'url' => '/pages/founder.php',   'key' => 'founder'],
    ['label' => 'Team Members', 'icon' => 'fa-user-group', 'url' => '/pages/team.php',      'key' => 'team'],
    ['label' => 'Employees',    'icon' => 'fa-id-badge',   'url' => '/pages/employees.php', 'key' => 'employees'],
    ['label' => 'Partners',     'icon' => 'fa-handshake',  'url' => '/pages/partners.php',  'key' => 'partners'],
    ['label' => 'Clients',      'icon' => 'fa-building',   'url' => '/pages/clients.php',   'key' => 'clients'],
    ['label' => 'Certifications', 'icon' => 'fa-award', 'url' => '/pages/certifications.php', 'key' => 'certifications'],
  ]],

  /* Invoices live here only; they used to appear in two groups */
  ['section' => 'Finance & Marketing', 'links' => [
    ['label' => 'Invoices',       'icon' => 'fa-file-invoice-dollar', 'url' => '/pages/invoices.php',            'key' => 'invoices'],
    ['label' => 'Subscribers',    'icon' => 'fa-paper-plane',         'url' => '/pages/newsletter.php',          'key' => 'newsletter'],
    ['label' => 'Campaigns',      'icon' => 'fa-rocket',              'url' => '/pages/campaigns.php',           'key' => 'campaigns'],
    ['label' => 'Popups', 'icon' => 'fa-window-restore', 'url' => '/pages/popups.php', 'key' => 'popups'],
    ['label' => 'Email Designer', 'icon' => 'fa-wand-magic-sparkles', 'url' => '/pages/email-designer.php',      'key' => 'email-designer'],
    ['label' => 'Compose Email',  'icon' => 'fa-envelope',            'url' => '/pages/email-compose.php',       'key' => 'email-compose'],
    ['label' => 'Social Links',   'icon' => 'fa-share-nodes',         'url' => '/pages/settings.php?tab=social', 'key' => 'settings', 'q' => ['tab' => 'social']],
    ['label' => 'Mail Settings',  'icon' => 'fa-at',                  'url' => '/pages/settings.php?tab=mail',   'key' => 'settings', 'q' => ['tab' => 'mail']],
  ]],

  ['section' => 'Analytics', 'links' => [
    ['label' => 'Reports', 'icon' => 'fa-chart-column',           'url' => '/pages/reports.php', 'key' => 'reports'],
    ['label' => 'SEO',     'icon' => 'fa-magnifying-glass-chart', 'url' => '/pages/seo.php',     'key' => 'seo'],
  ]],

  /* Access control and audit trail together, where they belong */
  ['section' => 'System', 'links' => [
    ['label' => 'Settings',      'icon' => 'fa-gear',             'url' => '/pages/settings.php',      'key' => 'settings'],
    ['label' => 'Chatbot',       'icon' => 'fa-robot',            'url' => '/pages/chatbot.php',       'key' => 'chatbot'],
    ['label' => 'Admin Users',   'icon' => 'fa-user-shield',      'url' => '/pages/users.php',         'key' => 'users',      'roles' => ['superadmin']],
    ['label' => 'Security',      'icon' => 'fa-lock',             'url' => '/pages/security.php',      'key' => 'security'],
    ['label' => 'Activity Log',  'icon' => 'fa-clock-rotate-left','url' => '/pages/activity.php',      'key' => 'activity',   'roles' => ['superadmin']],
    ['label' => 'Login History', 'icon' => 'fa-right-to-bracket', 'url' => '/pages/login-history.php', 'key' => 'login-history'],
    ['label' => 'Reset Data',   'icon' => 'fa-eraser',          'url' => '/pages/data-reset.php',    'key' => 'data-reset', 'roles' => ['superadmin']],
  ]],

  ['section' => 'Account', 'links' => [
    ['label' => 'Profile',     'icon' => 'fa-user',     'url' => '/pages/profile.php?tab=profile',     'key' => 'profile', 'q' => ['tab' => 'profile']],
    ['label' => 'Password',    'icon' => 'fa-key',      'url' => '/pages/profile.php?tab=password',    'key' => 'profile', 'q' => ['tab' => 'password']],
    ['label' => 'Preferences', 'icon' => 'fa-toggle-on','url' => '/pages/profile.php?tab=preferences', 'key' => 'profile', 'q' => ['tab' => 'preferences']],
  ]],
];

/* ── Helpers ─────────────────────────────────────────── */

if (!function_exists('agNavIsCurrent')) {
/** Current when the page matches and every discriminating query
 *  parameter equals what the link declares. */
function agNavIsCurrent(array $link, string $adminPage): bool {
    if (($link['key'] ?? '') !== $adminPage) return false;
    foreach (AG_NAV_DISCRIMINATORS as $param) {
        $want = (string)(($link['q'] ?? [])[$param] ?? '');
        $have = (string)($_GET[$param] ?? '');
        if ($want !== $have) return false;
    }
    return true;
}}

if (!function_exists('agRenderNav')) {
/** @param array<string,int> $counts e.g. ['newLeads' => 3, 'newComments' => 0] */
function agRenderNav(array $tree, string $adminPage, array $counts = []): void {
    foreach ($tree as $i => $section) {

        $sid = 'navsec-' . $i;

        /* Hide what this role would only be bounced from. */
        $visible = array_values(array_filter($section['links'], static function (array $l): bool {
            $need = $l['roles'] ?? null;
            if (!$need) return true;
            return in_array($_SESSION['admin_role'] ?? '', (array)$need, true);
        }));
        if (!$visible) continue;

        /* Labelled sections collapse; the unlabelled lead block never does.
           They start open — the chevron is for tidying a long rail, not a
           gate the user has to pass through to reach a link. */
        if (!empty($section['section'])) { ?>
            <button type="button" class="nav-sec-label" onclick="toggleNavSection(this)"
                    aria-expanded="true" aria-controls="<?= e($sid) ?>">
              <span><?= e($section['section']) ?></span>
              <i class="fas fa-chevron-up nav-sec-arrow" aria-hidden="true"></i>
            </button>
        <?php }

        echo '<div class="nav-sec" id="' . e($sid) . '">';
        foreach ($visible as $l) {
            $on  = agNavIsCurrent($l, $adminPage);
            $n   = (int)($counts[$l['badge'] ?? ''] ?? 0);
            $url = ADMIN_URL . $l['url'];
            ?>
            <a href="<?= e($url) ?>" class="nav-link<?= $on ? ' active' : '' ?>"
               data-tip="<?= e($l['label']) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
              <span class="nav-ico"><i class="fas <?= e($l['icon']) ?>" aria-hidden="true"></i></span>
              <span class="nav-txt"><?= e($l['label']) ?></span>
              <?php if ($n > 0): ?>
                <span class="nav-count"><?= $n > 99 ? '99+' : $n ?><span class="sr-only"> unread</span></span>
              <?php endif; ?>
            </a>
            <?php
        }
        echo '</div>';
    }
}}
