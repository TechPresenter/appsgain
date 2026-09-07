<?php
/**
 * Content for /services.php.
 *
 * Every string and list on that page resolves through here, so the page
 * itself holds no copy. Values live in `settings` under the group
 * `services_page`; the defaults below are what shipped, so the page looks
 * identical until someone edits it and nothing breaks if a row is missing.
 *
 * Repeatable blocks (why points, process, stack, finder) are stored as JSON
 * in a single setting each, the same way the header menu is stored.
 */

/** The shipped defaults. Also drives the admin form, so the two cannot drift. */
function servicesPageDefaults(): array
{
    return [
        /* Hero */
        'sp_hero_eyebrow'   => 'What we do',
        'sp_hero_title'     => 'Technology Solutions Built',
        'sp_hero_title2'    => 'Around',
        'sp_hero_accent'    => 'Your Business',
        'sp_hero_lead'      => '',   // blank falls back to the page meta description
        'sp_hero_btn1_text' => 'Let’s Talk',
        'sp_hero_btn1_url'  => '/contact.php#enquiry',
        'sp_hero_btn2_text' => 'View Our Work',
        'sp_hero_btn2_url'  => '/portfolio.php',
        'sp_hero_image'     => '',   // blank keeps the built-in dashboard illustration

        /* Section 3 — overview grid */
        'sp_overview_eyebrow' => 'Our capabilities',
        'sp_overview_title'   => 'Everything You Need to Build, Scale &',
        'sp_overview_accent'  => 'Transform',
        'sp_overview_sub'     => 'Twelve services, one engineering team. Pick a starting point — we will tell you honestly what you actually need.',

        /* Section 5 — why us */
        'sp_why_eyebrow' => 'Why us',
        'sp_why_title'   => 'Why Businesses Choose',
        'sp_why_accent'  => 'Appsgain',
        'sp_why_sub'     => '',

        /* Section 6 — stack */
        'sp_stack_eyebrow' => 'Our toolkit',
        'sp_stack_title'   => 'The Stack We Build',
        'sp_stack_accent'  => 'With',
        'sp_stack_sub'     => 'Chosen for maintainability and hiring depth — not novelty.',

        /* Section 7 — process */
        'sp_process_eyebrow' => 'How we work',
        'sp_process_title'   => 'From First Call to',
        'sp_process_accent'  => 'Live Product',
        'sp_process_sub'     => '',

        /* Section 8 — finder */
        'sp_finder_eyebrow' => 'Not sure where to start?',
        'sp_finder_title'   => 'Tell Us What You',
        'sp_finder_accent'  => 'Need',
        'sp_finder_sub'     => '',

        /* Section 9 — closing CTA */
        'sp_cta_title'     => 'Have a Project in',
        'sp_cta_accent'    => 'Mind?',
        'sp_cta_sub'       => 'Tell us what you are trying to build. We will come back with an honest scope, a timeline and a number.',
        'sp_cta_btn1_text' => 'Start a Conversation',
        'sp_cta_btn1_url'  => '/contact.php#enquiry',
        'sp_cta_btn2_text' => 'See Our Work',
        'sp_cta_btn2_url'  => '/portfolio.php',
    ];
}

/** Repeatable lists, stored one JSON blob per key. */
function servicesPageListDefaults(): array
{
    return [
        'sp_why_points' => [
            ['title' => 'One team, end to end',          'text' => 'Discovery, design, build and support come from the same people. Nothing gets lost in a handover.'],
            ['title' => 'We scope honestly',             'text' => 'If your idea needs half the budget you planned, we will say so. If it needs more, you hear that early.'],
            ['title' => 'Built on your process',         'text' => 'We adapt the software to how your business already runs, rather than forcing a workflow on your team.'],
            ['title' => 'Senior engineers on the work',  'text' => 'The people in your kickoff call are the people writing the code.'],
            ['title' => 'You own everything',            'text' => 'Source, infrastructure, documentation and accounts are yours from day one.'],
            ['title' => 'We stay after launch',          'text' => 'Monitoring, iteration and support — a release is a milestone, not an exit.'],
        ],
        'sp_process_steps' => [
            ['title' => 'Discovery',        'icon' => 'fa-magnifying-glass', 'text' => 'We map the problem, users and constraints before a line of code exists.'],
            ['title' => 'Strategy',         'icon' => 'fa-compass-drafting', 'text' => 'Scope, architecture, stack and a delivery plan you can hold us to.'],
            ['title' => 'Design',           'icon' => 'fa-pen-ruler',        'text' => 'Wireframes to a working design system — reviewed with you, not at you.'],
            ['title' => 'Development',      'icon' => 'fa-code',             'text' => 'Two-week sprints, demo builds throughout, no black-box phases.'],
            ['title' => 'Launch & Support', 'icon' => 'fa-rocket',           'text' => 'Deploy, monitor, iterate. We stay on after go-live.'],
        ],
        'sp_tech_stack' => [
            ['group' => 'Frontend', 'items' => [['icon' => 'fab fa-react', 'name' => 'React'], ['icon' => 'fab fa-js', 'name' => 'Next.js'], ['icon' => 'fab fa-vuejs', 'name' => 'Vue'], ['icon' => 'fas fa-code', 'name' => 'TypeScript']]],
            ['group' => 'Backend',  'items' => [['icon' => 'fab fa-node-js', 'name' => 'Node.js'], ['icon' => 'fab fa-python', 'name' => 'Python'], ['icon' => 'fab fa-php', 'name' => 'Laravel'], ['icon' => 'fas fa-mug-hot', 'name' => 'Java']]],
            ['group' => 'Mobile',   'items' => [['icon' => 'fas fa-mobile-screen', 'name' => 'Flutter'], ['icon' => 'fab fa-android', 'name' => 'Kotlin'], ['icon' => 'fab fa-apple', 'name' => 'Swift'], ['icon' => 'fab fa-react', 'name' => 'RN']]],
            ['group' => 'Database', 'items' => [['icon' => 'fas fa-database', 'name' => 'PostgreSQL'], ['icon' => 'fas fa-leaf', 'name' => 'MongoDB'], ['icon' => 'fas fa-bolt', 'name' => 'Redis'], ['icon' => 'fas fa-server', 'name' => 'MySQL']]],
            ['group' => 'Cloud',    'items' => [['icon' => 'fab fa-aws', 'name' => 'AWS'], ['icon' => 'fab fa-google', 'name' => 'GCP'], ['icon' => 'fab fa-microsoft', 'name' => 'Azure'], ['icon' => 'fab fa-docker', 'name' => 'Docker']]],
            ['group' => 'AI',       'items' => [['icon' => 'fas fa-brain', 'name' => 'PyTorch'], ['icon' => 'fas fa-diagram-project', 'name' => 'TensorFlow'], ['icon' => 'fas fa-robot', 'name' => 'OpenAI'], ['icon' => 'fas fa-wand-magic-sparkles', 'name' => 'LangChain']]],
        ],
        'sp_finder' => [
            ['label' => 'Website',             'slug' => 'web-portal-development'],
            ['label' => 'Mobile App',          'slug' => 'mobile-app-development'],
            ['label' => 'Business Software',   'slug' => 'custom-software-development'],
            ['label' => 'ERP',                 'slug' => 'erp-development'],
            ['label' => 'CRM',                 'slug' => 'crm-development'],
            ['label' => 'SaaS Product',        'slug' => 'saas-platform-development'],
            ['label' => 'AI Product',          'slug' => 'ai-product-development'],
            ['label' => 'Automation',          'slug' => 'automation-solutions'],
            ['label' => 'Enterprise Platform', 'slug' => 'enterprise-applications'],
        ],
        /* Intro copy per service category, keyed by the group name on the
           service record so a new category can be described without code. */
        'sp_group_copy' => [
            ['group' => 'Development', 'lead' => 'Development',   'accent' => 'Services',            'sub' => 'Product engineering from first prototype to enterprise rollout — built on your process, not a template.'],
            ['group' => 'Technology',  'lead' => 'Technology &',  'accent' => 'Advanced Solutions',  'sub' => 'AI, automation, infrastructure and data work for teams that have outgrown off-the-shelf tooling.'],
        ],
    ];
}

/** One scalar setting, falling back to the shipped default. */
function sp(string $key, string $fallback = ''): string
{
    static $defaults = null;
    $defaults ??= servicesPageDefaults();
    $v = trim((string)getSetting($key, ''));
    if ($v !== '') return $v;
    return $fallback !== '' ? $fallback : (string)($defaults[$key] ?? '');
}

/**
 * One repeatable list. Returns the stored rows, or the shipped defaults when
 * nothing is saved. Malformed JSON falls back rather than emptying a section.
 */
function spList(string $key): array
{
    $defaults = servicesPageListDefaults();
    $raw = trim((string)getSetting($key, ''));
    if ($raw === '') return $defaults[$key] ?? [];
    $rows = json_decode($raw, true);
    if (!is_array($rows)) return $defaults[$key] ?? [];
    /* An empty saved list is a deliberate "hide this section", so it is kept. */
    return $rows;
}

/** Resolve an uploaded path to a URL; absolute URLs pass through. */
function spImage(string $key): string
{
    $v = trim((string)getSetting($key, ''));
    if ($v === '') return '';
    return str_starts_with($v, 'http') ? $v : UPLOADS_URL . '/' . ltrim($v, '/');
}
