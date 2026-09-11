<?php
/**
 * Appsgain — Site Footer v5 (premium light theme)
 *
 * Composition:
 *   1. Newsletter banner   — rounded, soft lavender gradient, gradient CTA
 *   2. Four-column grid    — Brand | Quick Links | Services | Get In Touch
 *   3. Dark navy bottom bar — copyright + policy links
 *
 * Every value is admin-controlled (Admin → Settings → Brand / Footer).
 * ~90% neutral surfaces; the logo gradient appears only on the CTA,
 * heading underlines, icon tiles and hover states.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }
require_once __DIR__ . '/social-links.php';

$_sf = getAllSettings();

/* ── Identity ── */
$_siteName    = $_sf['site_name']    ?? 'Appsgain Technologies';
$_companyName = trim((string)($_sf['company_name'] ?? '')) ?: $_siteName;
$_siteDesc    = $_sf['site_tagline'] ?? ($_sf['site_description'] ?? '');
$_siteUrl     = SITE_URL;
$_year        = date('Y');

/* ── Contact ── */
$_phone      = $_sf['site_phone']     ?? $_sf['phone']    ?? '+91-9955446477';
$_email      = $_sf['site_email']     ?? $_sf['email']    ?? 'info@appsgain.in';
$_address    = $_sf['site_address']   ?? $_sf['address']  ?? '';
/* Offices are a list, so a third location needs a setting, not a template edit. */
$_offices = [];
foreach ([['site_address', 'site_address_label', 'Head Office'],
          ['site_address_2', 'site_address_2_label', 'Branch Office']] as [$_ak, $_lk, $_ld]) {
    $_a = trim((string)($_sf[$_ak] ?? ($_ak === 'site_address' ? ($_sf['address'] ?? '') : '')));
    if ($_a === '') continue;
    $_offices[] = [trim((string)($_sf[$_lk] ?? '')) ?: $_ld, $_a];
}
$_whatsapp   = $_sf['site_whatsapp']  ?? $_sf['whatsapp'] ?? preg_replace('/[^0-9]/', '', $_phone);
$_phoneClean = preg_replace('/[^+0-9]/', '', $_phone);
$_waClean    = preg_replace('/[^0-9]/', '', $_whatsapp);

/* ── Social ── */
$_fb = $_sf['site_facebook']  ?? '#';
$_ig = $_sf['site_instagram'] ?? '#';
$_li = $_sf['site_linkedin']  ?? '#';
$_tw = $_sf['site_twitter']   ?? '#';
$_yt = $_sf['site_youtube']   ?? '#';

/* ── Logo. The footer body is light, so use the primary (dark) lockup. ── */
$_logoRaw = trim((string)($_sf['site_logo'] ?? ($_sf['logo'] ?? '')));
$_logo    = $_logoRaw !== ''
          ? (str_starts_with($_logoRaw, 'http') ? $_logoRaw : UPLOADS_URL . '/' . ltrim($_logoRaw, '/'))
          : '';

/* ── Footer copy ── */
$_footerAbout = trim((string)($_sf['footer_about_text'] ?? ''));
if ($_footerAbout === '') $_footerAbout = trim((string)($_sf['site_description'] ?? $_siteDesc));
$_copyright   = trim((string)($_sf['copyright_text'] ?? '')) ?: 'All rights reserved.';
$_builtWith   = trim((string)($_sf['footer_built_with'] ?? ''));

$_showBadges  = ($_sf['footer_show_badges']     ?? '1') === '1';
$_showNews    = ($_sf['footer_show_newsletter'] ?? '1') === '1';
$_showAskAi   = ($_sf['footer_show_askai']      ?? '1') === '1';

/* Trust badges: "Label|fa-icon, Label|fa-icon" */
$_footerBadges = [];
foreach (explode(',', (string)($_sf['footer_badges'] ?? '')) as $_bRaw) {
    $_bRaw = trim($_bRaw);
    if ($_bRaw === '') continue;
    $_bParts = array_map('trim', explode('|', $_bRaw, 2));
    if ($_bParts[0] === '') continue;
    $_footerBadges[] = [$_bParts[0], $_bParts[1] ?? 'fa-check-circle'];
}

/* ── Link lists ──
   One per line:  Label | url | fa-icon | #colour | badge  */
$_parseLinks = static function (string $raw) use ($_siteUrl): array {
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#!')) continue;
        $p = array_map('trim', explode('|', $line));
        $label = $p[0] ?? ''; $url = $p[1] ?? '';
        if ($label === '' || $url === '') continue;
        if (!preg_match('~^(https?:|mailto:|tel:|/)~i', $url)) $url = '/' . ltrim($url, '/');
        if (str_starts_with($url, '/')) $url = $_siteUrl . $url;
        $out[] = [
            'label' => $label,
            'url'   => $url,
            'icon'  => ($p[2] ?? '') !== '' ? $p[2] : 'fa-chevron-right',
            'badge' => $p[4] ?? '',
        ];
    }
    return $out;
};
$_quickLinksTitle = trim((string)($_sf['footer_links_title'] ?? '')) ?: 'Quick Links';
$_quickLinks = $_parseLinks((string)($_sf['footer_quick_links'] ?? ''));

/* Pay Now — same payment_link setting the header button reads, so one field
   controls both and clearing it removes both. Appended here rather than added
   to footer_quick_links so it cannot be edited into a dead link, and so the
   highlight style is not something an admin has to remember to reapply. */
$_payUrl = trim((string)($_sf['payment_link'] ?? 'https://payments.cashfree.com/forms/appsgaintechnologies'));
if ($_payUrl !== '') {
    $_quickLinks[] = [
        'label'    => trim((string)($_sf['payment_label'] ?? '')) ?: 'Pay Now',
        'url'      => $_payUrl,
        'icon'     => 'fa-bolt',
        'badge'    => '',
        'pay'      => true,
        'external' => true,
    ];
}
$_legalLinks = $_parseLinks((string)($_sf['footer_legal_links'] ?? ''));

$_footerServices = dbFetchAll(
    "SELECT name, slug FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 9"
);

/* ── App store badges ── */
$_googlePlay   = $_sf['app_google_play'] ?? '';
$_appleStore   = $_sf['app_apple_store'] ?? '';
$_showGplay    = ($_sf['footer_show_gplay']    ?? '1') === '1';
$_showAppStore = ($_sf['footer_show_appstore'] ?? '0') === '1';
$_appsTitle    = trim((string)($_sf['footer_apps_title'] ?? '')) ?: 'Download Our Apps';
$_gplayIcon    = trim((string)($_sf['footer_gplay_icon']    ?? '')) ?: 'ai-icons/Google_Play.webp';
$_appleIcon    = trim((string)($_sf['footer_appstore_icon'] ?? '')) ?: 'ai-icons/App_Store_(iOS).svg.webp';

/** uploads path -> URL, encoding each segment so names with ( ) still resolve */
$_iconUrl = static function (string $p): string {
    if ($p === '') return '';
    if (str_starts_with($p, 'http')) return $p;
    return UPLOADS_URL . '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($p, '/'))));
};
?>

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<?php /* Timed popup — Admin -> Marketing -> Popups */ ?>
<?php require __DIR__ . '/site-popup.php'; ?>

<?php /* "Our Product Listed On" badges — DB-driven */ ?>
<?php require __DIR__ . '/product-listings.php'; ?>

<?php /* Trust / certifications marquee — DB-driven, sits directly above the footer */ ?>
<?php require __DIR__ . '/trust-marquee.php'; ?>

<footer class="ftx" aria-label="Site footer">
  <div class="container">

    <?php if ($_showNews): ?>
    <!-- ── 1 · Newsletter ── -->
    <section class="nlx" aria-labelledby="nlxHeading">
      <div class="nlx-copy">
        <span class="nlx-badge" aria-hidden="true"><i class="fas fa-envelope-open-text"></i></span>
        <div>
          <span class="nlx-eyebrow">Newsletter</span>
          <h2 class="nlx-title" id="nlxHeading">Notes from our engineering team</h2>
          <p class="nlx-sub">
            Product updates, build notes and the occasional lesson learned.
            Sent when we have something worth saying &mdash; unsubscribe in one click.
          </p>
        </div>
      </div>

      <form class="nlx-form" id="footerNewsletterForm" onsubmit="submitNewsletter(event)" novalidate>
        <div class="nlx-row">
          <div class="nlx-field">
            <i class="fas fa-envelope nlx-field-ico" aria-hidden="true"></i>
            <label class="sr-only" for="nl_email">Email address</label>
            <input type="email" id="nl_email" name="email" required
                   autocomplete="email" inputmode="email"
                   placeholder="you@company.com" aria-describedby="nl_msg">
          </div>
          <button type="submit" class="nlx-btn" id="nl_btn">
            <span class="nlx-btn-label">Subscribe</span>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </button>
        </div>

        <label class="sr-only" for="nl_name">Your name</label>
        <input type="text" id="nl_name" name="name" class="nlx-off" tabindex="-1" autocomplete="name">

        <p class="nlx-note">
          <i class="fas fa-lock" aria-hidden="true"></i>
          <span>We never share your address. See our <a href="<?= $_siteUrl ?>/privacy-policy.php">Privacy Policy</a>.</span>
        </p>
        <p id="nl_msg" class="nlx-msg" role="status" aria-live="polite"></p>
      </form>
    </section>
    <?php endif; ?>

    <!-- ── 2 · Main grid ── -->
    <div class="ftx-grid">

      <!-- Column 1 — Brand -->
      <div class="ftx-brand">
        <a href="<?= $_siteUrl ?>/" class="ftx-logo" aria-label="<?= e($_siteName) ?> home">
          <?php if ($_logo): ?>
            <img src="<?= e($_logo) ?>" alt="<?= e($_companyName) ?>" width="220" height="57">
          <?php else: ?>
            <span class="ftx-logo-text"><?= e($_siteName) ?></span>
          <?php endif; ?>
        </a>

        <?php if ($_footerAbout): ?>
        <p class="ftx-about"><?= e($_footerAbout) ?></p>
        <?php endif; ?>

        <?php if ($_showBadges && $_footerBadges): ?>
        <ul class="ftx-badges">
          <?php foreach (array_slice($_footerBadges, 0, 4) as [$_bLabel, $_bIcon]): ?>
          <li class="ftx-badge">
            <span class="ftx-badge-ico"><i class="fas <?= e($_bIcon) ?>" aria-hidden="true"></i></span>
            <span class="ftx-badge-txt">
              <strong><?= e($_bLabel) ?></strong>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="ftx-socials">
          <?php
          foreach (agSocialLinks($_sf) as $_sl): ?>
          <a href="<?= e($_sl['url']) ?>" class="ftx-social" target="_blank" rel="noopener me"
             aria-label="<?= e($_siteName) ?> on <?= e($_sl['label']) ?>" title="<?= e($_sl['label']) ?>"
             style="--sc:<?= e($_sl['color']) ?>">
            <i class="<?= e($_sl['icon']) ?>" aria-hidden="true"></i>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if ($_showGplay || $_showAppStore): ?>
        <div class="ftx-apps">
          <h4 class="ftx-apps-h"><?= e($_appsTitle) ?></h4>
          <div class="ftx-stores">
            <?php
            /* [store icon, top line, bottom line, url] — a badge with no URL
               yet renders as a span, never as a link to nowhere. */
            $_stores = [];
            if ($_showGplay)    $_stores[] = [$_gplayIcon, 'GET IT ON',       'Google Play', $_googlePlay];
            if ($_showAppStore) $_stores[] = [$_appleIcon, 'DOWNLOAD ON THE', 'App Store',   $_appleStore];
            foreach ($_stores as [$_ico, $_top, $_btm, $_href]):
              $_tag = $_href !== '' ? 'a' : 'span';
            ?>
            <<?= $_tag ?> class="ftx-store<?= $_href === '' ? ' is-soon' : '' ?>"
              <?= $_href !== '' ? 'href="' . e($_href) . '" target="_blank" rel="noopener"' : '' ?>
              <?= $_href === '' ? 'title="Coming soon"' : '' ?>>
              <img class="ftx-store-ico" src="<?= e($_iconUrl($_ico)) ?>" alt="" width="26" height="26" loading="lazy">
              <span><small><?= $_href === '' ? 'COMING SOON' : e($_top) ?></small><strong><?= e($_btm) ?></strong></span>
            </<?= $_tag ?>>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Column 2 — Quick Links -->
      <?php if ($_quickLinks): ?>
      <nav class="ftx-col" aria-labelledby="ftxQuick">
        <h3 class="ftx-h" id="ftxQuick"><?= e($_quickLinksTitle) ?></h3>
        <ul class="ftx-list ftx-list-divided ftx-list-2col">
          <?php foreach ($_quickLinks as $_ql): ?>
          <?php $_isPay = !empty($_ql['pay']); ?>
          <li>
            <a href="<?= e($_ql['url']) ?>"<?= $_isPay ? ' class="ftx-pay"' : '' ?><?php
               /* Payment leaves the site — new tab, no referrer. */
               if (!empty($_ql['external'])) echo ' target="_blank" rel="noopener noreferrer"'; ?>>
              <i class="fas <?= $_isPay ? e($_ql['icon']) : 'fa-angle-right' ?> ftx-list-ico" aria-hidden="true"></i>
              <span><?= e($_ql['label']) ?></span>
              <?php if ($_isPay): ?><em class="ftx-pay-tag">Secure</em><?php endif; ?>
              <?php if ($_ql['badge']): ?><em class="ftx-pill"><?= e($_ql['badge']) ?></em><?php endif; ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <!-- Column 3 — Services -->
      <?php if ($_footerServices): ?>
      <nav class="ftx-col" aria-labelledby="ftxServices">
        <h3 class="ftx-h" id="ftxServices">Our Services</h3>
        <ul class="ftx-list ftx-list-divided">
          <?php foreach ($_footerServices as $_fs): ?>
          <li>
            <a href="<?= $_siteUrl ?>/service/<?= e($_fs['slug']) ?>">
              <i class="fas fa-chevron-right ftx-list-ico" aria-hidden="true"></i>
              <span><?= e($_fs['name']) ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <!-- Column 4 — Get In Touch -->
      <div class="ftx-col">
        <h3 class="ftx-h">Get In Touch</h3>

        <ul class="ftx-contact">
          <?php foreach ($_offices as [$_oLabel, $_oAddr]): ?>
          <li class="ftx-cc">
            <span class="ftx-cc-ico"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
            <span class="ftx-cc-body">
              <small><?= e($_oLabel) ?></small>
              <span><?= nl2br(e($_oAddr)) ?></span>
            </span>
          </li>
          <?php endforeach; ?>
          <li class="ftx-cc">
            <span class="ftx-cc-ico"><i class="fas fa-phone" aria-hidden="true"></i></span>
            <span class="ftx-cc-body">
              <small>Phone</small>
              <a href="tel:<?= e($_phoneClean) ?>"><?= e($_phone) ?></a>
            </span>
          </li>
          <li class="ftx-cc">
            <span class="ftx-cc-ico"><i class="fas fa-envelope" aria-hidden="true"></i></span>
            <span class="ftx-cc-body">
              <small>Email</small>
              <a href="mailto:<?= e($_email) ?>"><?= e($_email) ?></a>
            </span>
          </li>

        </ul>

        <?php
        /* ── Ask AI (admin-controlled platforms) ── */
        $_aiPrompt = 'Tell me about ' . $_companyName . ' — an AI-first software development company. '
                   . 'Services: custom software, mobile apps, ERP, CRM, SaaS platforms, AI products, cloud and DevOps. '
                   . 'Contact: ' . $_phone . ' | ' . $_email . ' | ' . SITE_URL . '. '
                   . 'What services do they provide and how can they help my business?';
        $_aiEnc = rawurlencode($_aiPrompt);

        $_aiDefs = [
          'chatgpt'    => ['ChatGPT',    'https://chatgpt.com/?q=',          '<svg viewBox="0 0 41 41" fill="#10A37F" width="20" height="20"><path d="M37.532 16.87a9.963 9.963 0 0 0-.856-8.184 10.078 10.078 0 0 0-10.855-4.835 9.964 9.964 0 0 0-6.525-3.499 10.079 10.079 0 0 0-10.42 4.958 9.967 9.967 0 0 0-6.664 4.834 10.08 10.08 0 0 0 1.24 11.817 9.965 9.965 0 0 0 .856 8.185 10.079 10.079 0 0 0 10.855 4.835 9.965 9.965 0 0 0 6.526 3.499 10.079 10.079 0 0 0 10.42-4.958 9.967 9.967 0 0 0 6.663-4.834 10.079 10.079 0 0 0-1.24-11.817zm-17.47 24.288a7.46 7.46 0 0 1-4.795-1.735c.061-.033.168-.091.237-.134l7.964-4.6a1.294 1.294 0 0 0 .655-1.134V19.054l3.366 1.944a.12.12 0 0 1 .066.092v9.299a7.505 7.505 0 0 1-7.493 7.769zm-16.134-6.9a7.461 7.461 0 0 1-.894-5.023c.06.036.162.099.237.141l7.964 4.6a1.297 1.297 0 0 0 1.308 0l9.724-5.614v3.888a.12.12 0 0 1-.048.103l-8.051 4.649a7.504 7.504 0 0 1-10.24-2.744zm-2.063-17.48a7.462 7.462 0 0 1 3.901-3.282c0 .068-.004.19-.004.274v9.201a1.294 1.294 0 0 0 .654 1.132l9.723 5.614-3.366 1.944a.12.12 0 0 1-.114.012L10.074 28.91a7.505 7.505 0 0 1-8.209-12.112zm27.555 6.443l-9.724-5.615 3.367-1.943a.121.121 0 0 1 .114-.012l8.048 4.648a7.498 7.498 0 0 1-1.158 13.528v-9.476a1.293 1.293 0 0 0-.647-1.13zm3.35-5.043c-.059-.037-.162-.099-.236-.141l-7.965-4.6a1.298 1.298 0 0 0-1.308 0l-9.723 5.614v-3.888a.12.12 0 0 1 .048-.103l8.05-4.645a7.497 7.497 0 0 1 11.135 7.763zm-21.063 6.929l-3.367-1.944a.12.12 0 0 1-.065-.092v-9.299a7.497 7.497 0 0 1 12.293-5.756 6.94 6.94 0 0 0-.236.134l-7.965 4.6a1.294 1.294 0 0 0-.654 1.132l-.006 11.225zm1.829-3.943l4.33-2.501 4.332 2.498v4.996l-4.331 2.5-4.331-2.5V21.184z"/></svg>'],
          'gemini'     => ['Gemini',     'https://gemini.google.com/app?q=', '<svg viewBox="0 0 192 192" fill="none" width="20" height="20"><path d="M96 2C97.659 56.401 135.599 94.341 190 96C135.599 97.659 97.659 135.599 96 190C94.341 135.599 56.401 97.659 2 96C56.401 94.341 94.341 56.401 96 2Z" fill="#4285F4"/></svg>'],
          'claude'     => ['Claude',     'https://claude.ai/new?q=',         '<svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M12 2L8 8H2L7 13L5 20L12 16L19 20L17 13L22 8H16L12 2Z" stroke="#D97757" stroke-width="2" stroke-linejoin="round" fill="rgba(217,119,87,.16)"/></svg>'],
          'perplexity' => ['Perplexity', 'https://www.perplexity.ai/?q=',    '<svg viewBox="0 0 24 24" fill="none" stroke="#20808D" stroke-width="2.5" width="20" height="20"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>'],
          'grok'       => ['Grok',       'https://x.com/i/grok?text=',       '<svg viewBox="0 0 24 24" fill="#11162D" width="18" height="18"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
        ];
        $_aiVisible = [];
        foreach ($_aiDefs as $_aiId => [$_aN, $_aBaseUrl, $_aSvg]) {
            if (($_sf['ai_show_' . $_aiId] ?? '1') !== '1') continue;
            $_custIcon = trim((string)($_sf['ai_icon_' . $_aiId] ?? ''));
            $_custUrl  = trim((string)($_sf['ai_url_'  . $_aiId] ?? ''));
            $_aiVisible[] = [$_aN, ($_custUrl ?: $_aBaseUrl) . $_aiEnc, $_aSvg, $_custIcon];
        }
        ?>
        <?php if ($_showAskAi && $_aiVisible): ?>
        <div class="ftx-ai">
          <h4 class="ftx-h ftx-h-sm"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> Ask AI About Us</h4>
          <div class="ftx-ai-row">
            <?php foreach ($_aiVisible as [$_aN, $_aUrl, $_aSvg, $_aCustIco]): ?>
            <a href="<?= e($_aUrl) ?>" class="ftx-ai-card" target="_blank" rel="noopener"
               title="Ask <?= e($_aN) ?> about <?= e($_companyName) ?>">
              <span class="ftx-ai-ico">
                <?php if ($_aCustIco): ?>
                  <img src="<?= e(UPLOADS_URL . '/' . ltrim($_aCustIco, '/')) ?>" alt="" width="20" height="20">
                <?php else: ?>
                  <?= $_aSvg ?>
                <?php endif; ?>
              </span>
              <small><?= e($_aN) ?></small>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /ftx-grid -->
  </div><!-- /container -->

  <!-- ── 3 · Copyright bar ── -->
  <div class="ftx-bottom">
    <div class="container ftx-bottom-inner">
      <p class="ftx-copy">&copy; <?= $_year ?>
        <a href="<?= $_siteUrl ?>/"><?= e($_companyName) ?></a><span
        class="ftx-dot" aria-hidden="true">·</span><?= e($_copyright) ?></p>
      <?php if ($_legalLinks): ?>
      <nav class="ftx-legal" aria-label="Legal">
        <?php foreach ($_legalLinks as $_i => $_ll): ?><?php if ($_i > 0): ?><span class="ftx-sep" aria-hidden="true"></span><?php endif; ?><a href="<?= e($_ll['url']) ?>"><?= e($_ll['label']) ?></a><?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</footer>

<style>
/* ══════════════════════════════════════════════════════
   FOOTER — premium light theme
   navy #10162F · deep navy #080D25 · body #667085
   surface #FFF / #F8F8FC · border #E7E8F0
   ══════════════════════════════════════════════════════ */
.ftx{
  --f-navy:#10162F; --f-deep:#080D25; --f-body:#667085;
  --f-soft:#F8F8FC; --f-line:#E7E8F0;
  --f-grad:linear-gradient(135deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  --f-grad-btn:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  --f-grad-90:linear-gradient(90deg,#FF8A00 0%,#FF3030 28%,#F50072 52%,#D000A8 72%,#6A00FF 100%);
  position:relative;
  background:linear-gradient(180deg,#FFFFFF 0%, #FBFAFE 42%, #F8F8FC 100%);
  border-top:1px solid var(--f-line);
  font-family:var(--font-body),var(--font-main),system-ui,sans-serif;
  color:var(--f-body);
  padding-top:clamp(40px,5vw,64px);
}
.ftx .container{ max-width:1200px; margin-inline:auto; padding-inline:clamp(20px,4vw,32px); }
.ftx .sr-only{
  position:absolute; width:1px; height:1px; padding:0; margin:-1px;
  overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; border:0;
}

/* ── 1 · Newsletter ──────────────────────────────────────
   The button carries a solid background-color beneath the gradient, and
   the gradient itself is the deep half of the brand ramp (4.94:1 to
   6.87:1 against white). The full ramp starts at #FF8A00, which is only
   2.36:1 — that is what made the label look like it was missing. */
.nlx{
  position:relative; overflow:hidden;
  display:grid; grid-template-columns:1.05fr .95fr;
  align-items:center; gap:clamp(24px,4vw,52px);
  padding:clamp(26px,3.4vw,42px) clamp(24px,3.4vw,46px);
  border-radius:22px;
  background:
    radial-gradient(120% 160% at 0% 0%, rgba(106,0,255,.055) 0%, transparent 58%),
    radial-gradient(120% 160% at 100% 100%, rgba(245,0,114,.045) 0%, transparent 58%),
    #FFFFFF;
  box-shadow:0 2px 4px rgba(16,22,47,.03), 0 12px 32px rgba(16,22,47,.05);
}
/* hairline gradient border, no extra element */
.nlx::before{
  content:""; position:absolute; inset:0; border-radius:22px; padding:1px;
  background:linear-gradient(120deg, rgba(255,138,0,.34), rgba(245,0,114,.28), rgba(106,0,255,.34));
  -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite:xor; mask-composite:exclude;
  pointer-events:none;
}
.nlx-off{ position:absolute; left:-9999px; width:1px; height:1px; opacity:0; }

.nlx-copy{ position:relative; z-index:1; display:flex; gap:18px; align-items:flex-start; }
.nlx-badge{
  flex:0 0 auto; display:grid; place-items:center;
  width:52px; height:52px; border-radius:15px;
  background-color:#6A00FF; background-image:var(--f-grad);
  color:#fff; font-size:20px;
  box-shadow:0 6px 18px rgba(106,0,255,.22);
}
.nlx-eyebrow{
  display:block; font-size:11.5px; font-weight:700; letter-spacing:.14em;
  text-transform:uppercase; color:#6A00FF; margin-bottom:7px;
}
.nlx-title{
  font-family:var(--font-main),sans-serif;
  font-size:clamp(20px,2.2vw,26px); font-weight:800; letter-spacing:-.02em;
  color:var(--f-navy); margin:0 0 8px; line-height:1.25;
}
.nlx-sub{ font-size:14px; line-height:1.68; color:var(--f-body); margin:0; max-width:44ch; }

.nlx-form{ position:relative; z-index:1; min-width:0; }
.nlx-row{ display:flex; gap:10px; }
.nlx-field{ position:relative; flex:1 1 auto; min-width:0; }
.nlx-field-ico{
  position:absolute; left:16px; top:50%; transform:translateY(-50%);
  font-size:14px; color:#6E7386; pointer-events:none;
}
.nlx-field input{
  width:100%; height:52px; padding:0 16px 0 42px;
  border:1px solid var(--f-line); border-radius:12px;
  background:#fff; color:var(--f-navy);
  font-family:inherit; font-size:14.5px;
  transition:border-color .2s ease, box-shadow .2s ease;
}
.nlx-field input::placeholder{ color:#8A8FA0; }
.nlx-field input:focus{
  outline:none; border-color:#6A00FF; box-shadow:0 0 0 3px rgba(106,0,255,.10);
}
.nlx-field input[aria-invalid="true"]{ border-color:#DC2626; }

.nlx-btn{
  flex:0 0 auto;
  display:inline-flex; align-items:center; justify-content:center; gap:9px;
  height:52px; padding:0 26px; border:0; border-radius:12px; cursor:pointer;
  background-color:#8B00E0;
  background-image:var(--f-grad-btn);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-family:inherit; font-size:14.5px; font-weight:600;
  line-height:1; white-space:nowrap;
  box-shadow:0 4px 14px rgba(106,0,255,.24);
  transition:background-position .3s ease, transform .2s ease, box-shadow .2s ease;
}
.nlx-btn-label{ display:inline-block; }
.nlx-btn:hover:not(:disabled){
  background-position:100% 50%; transform:translateY(-2px);
  box-shadow:0 8px 22px rgba(106,0,255,.30);
}
.nlx-btn:focus-visible{ outline:2px solid #6A00FF; outline-offset:3px; }
.nlx-btn:disabled{ opacity:.75; cursor:progress; transform:none; }
.nlx-btn i{ font-size:12px; }
/* Windows high-contrast: hand the button back to the system palette */
@media (forced-colors: active){
  .nlx-btn{ border:2px solid ButtonText; background:ButtonFace; color:ButtonText; }
}

.nlx-note{
  display:flex; align-items:center; gap:8px;
  margin:12px 0 0; font-size:12.5px; line-height:1.55; color:#6E7386;
}
.nlx-note i{ font-size:11px; }
.nlx-note a{ color:#6A00FF; font-weight:600; text-decoration:none; }
.nlx-note a:hover{ text-decoration:underline; }

.nlx-msg{ margin:10px 0 0; font-size:13px; font-weight:600; display:none; }
.nlx-msg.is-ok{ display:block; color:#0E9F6E; }
.nlx-msg.is-err{ display:block; color:#DC2626; }
/* ── 2 · Main grid ── */
.ftx-grid{
  display:grid;
  grid-template-columns:1.55fr 1fr 1fr 1.25fr;
  gap:clamp(28px,3.6vw,56px);
  padding-block:clamp(44px,5.5vw,76px);
}

/* Headings + gradient underline */
.ftx-h{
  position:relative;
  font-family:var(--font-main),sans-serif;
  font-size:15.5px; font-weight:700; letter-spacing:-.01em;
  color:var(--f-navy); margin:0 0 26px; padding-bottom:12px;
}
.ftx-h::after{
  content:""; position:absolute; left:0; bottom:0;
  width:38px; height:3px; border-radius:999px; background:var(--f-grad-90);
}
.ftx-h-sm{ font-size:13.5px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.ftx-h-sm i{ color:#6A00FF; font-size:12px; }

/* Column 1 — brand */
.ftx-logo{ display:inline-block; margin-bottom:18px; }
.ftx-logo img{ height:auto; max-height:46px; width:auto; object-fit:contain; }
.ftx-logo-text{ font-size:20px; font-weight:800; color:var(--f-navy); }
.ftx-about{ font-size:14px; line-height:1.75; color:var(--f-body); margin:0 0 22px; max-width:38ch; }

.ftx-badges{
  list-style:none; margin:0 0 22px; padding:0;
  display:grid; grid-template-columns:1fr 1fr; gap:10px; max-width:340px;
}
.ftx-badge{
  display:flex; align-items:center; gap:10px;
  padding:10px 12px; border-radius:12px;
  background:#fff; border:1px solid var(--f-line);
  box-shadow:0 1px 2px rgba(16,22,47,.04);
}
.ftx-badge-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:30px; height:30px; border-radius:9px;
  background:rgba(106,0,255,.07); color:#6A00FF; font-size:12px;
}
.ftx-badge-txt{ display:flex; flex-direction:column; line-height:1.3; min-width:0; }
.ftx-badge-txt strong{ font-size:12.5px; font-weight:600; color:var(--f-navy); }
.ftx-badge-txt small{ font-size:10.5px; color:#6E7386; }

.ftx-apps{ margin-top:20px; }
.ftx-apps-h{
  font-family:var(--font-body),sans-serif;
  font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
  color:var(--f-body); margin:0 0 10px;
}
/* A badge without a listing yet reads as pending, not broken */
.ftx-store.is-soon{ opacity:.6; cursor:default; }
.ftx-store.is-soon:hover{ transform:none; }

.ftx-socials{ display:flex; gap:10px; flex-wrap:wrap; }
.ftx-social{
  display:grid; place-items:center;
  width:46px; height:46px; border-radius:50%;
  background:#fff; border:1px solid var(--f-line);
  color:var(--f-navy); font-size:15px; text-decoration:none;
  transition:border-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
}
.ftx-social:hover{
  color:#6A00FF; border-color:rgba(106,0,255,.42);
  transform:translateY(-3px);
  box-shadow:0 6px 16px rgba(106,0,255,.14);
}

.ftx-stores{ display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }
.ftx-store{
  display:inline-flex; align-items:center; gap:10px;
  padding:9px 16px; border-radius:12px;
  background:#fff; border:1px solid var(--f-line);
  color:var(--f-navy); text-decoration:none;
  transition:border-color .2s ease, transform .2s ease;
}
.ftx-store:hover{ border-color:rgba(106,0,255,.4); transform:translateY(-2px); }
.ftx-store i{ font-size:19px; color:#6A00FF; }
.ftx-store span{ display:flex; flex-direction:column; line-height:1.25; }
.ftx-store small{ font-size:9.5px; letter-spacing:.08em; color:#6E7386; }
.ftx-store strong{ font-size:13px; font-weight:700; }

/* Columns 2 & 3 — link lists.
   Right-angle marker on every row, hairline divider between rows. */
.ftx-list{ list-style:none; margin:0; padding:0; }
/* Two columns: eleven links in one stack made the footer very tall,
   especially on phones. The divider now runs per column, so it never
   draws a line above the first item of the second column. */
/* Only once the footer grid has collapsed and this column is wide.
   On desktop it is ~222px, so two columns forced "Mobile Apps" and
   "Our Partners" onto two lines with ragged row heights. */
@media (max-width:900px){
  .ftx-list-2col{
    display:grid; grid-template-columns:1fr 1fr;
    column-gap:clamp(12px,2.5vw,24px);
  }
  .ftx-list-2col.ftx-list-divided li:nth-child(-n+2){ border-top:0; }
}
.ftx-list-divided li + li{ border-top:1px solid rgba(231,232,240,.85); }
.ftx-list a{
  display:flex; align-items:center; gap:9px;
  padding:10px 0; font-size:14px; color:var(--f-navy);
  text-decoration:none; transition:color .2s ease, padding-left .2s ease;
}
.ftx-list-ico{
  font-size:13px; color:#B6BAC8; width:10px; text-align:center; flex:0 0 auto;
  transition:color .2s ease, transform .2s ease;
}
.ftx-list a span{ flex:1 1 auto; min-width:0; }
.ftx-list a:hover{ color:#6A00FF; padding-left:3px; }
.ftx-list a:hover .ftx-list-ico{ color:#F50072; transform:translateX(2px); }
.ftx-pill{
  font-style:normal; font-size:9.5px; font-weight:700; letter-spacing:.05em;
  padding:2px 8px; border-radius:999px; color:#fff;
  background-color:#8B00E0; background-image:var(--f-grad-btn);
}

/* ── Pay Now in the quick links ───────────────────────────
   A gradient-text row rather than a button: the header already carries the
   button, and a second one here would compete with the newsletter CTA. The
   weight, the gradient and the bolt lift it out of a plain link list. */
.ftx-pay{ font-weight:800 !important; }
.ftx-pay span{
  background-image:var(--f-grad-90, linear-gradient(90deg,#FF8A00,#F50072 55%,#6A00FF));
  background-size:200% 100%;
  -webkit-background-clip:text; background-clip:text;
  -webkit-text-fill-color:transparent; color:transparent;
  animation:ftxPayShift 6s ease-in-out infinite;
}
.ftx-pay .ftx-list-ico{ color:#F50072 !important; }
.ftx-pay:hover span{ background-position:100% 50%; }
.ftx-pay:hover .ftx-list-ico{ transform:translateX(2px) scale(1.12); }
.ftx-pay-tag{
  font-style:normal; font-size:9px; font-weight:700; letter-spacing:.06em;
  text-transform:uppercase; padding:2px 7px; border-radius:999px;
  color:#0F9D58; background:rgba(15,157,88,.10);
  border:1px solid rgba(15,157,88,.22); flex:0 0 auto;
}
@keyframes ftxPayShift{ 0%,100%{ background-position:0% 50%; } 50%{ background-position:100% 50%; } }
@media (prefers-reduced-motion:reduce){
  .ftx-pay span{ animation:none; }
  .ftx-pay:hover .ftx-list-ico{ transform:none; }
}

/* Column 4 — contact cards */
.ftx-contact{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
.ftx-cc{
  display:flex; align-items:flex-start; gap:12px;
  padding:13px 14px; border-radius:13px;
  background:#fff; border:1px solid rgba(231,232,240,.9);
  box-shadow:0 1px 2px rgba(16,22,47,.03);
  transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.ftx-cc:hover{
  border-color:rgba(106,0,255,.26);
  box-shadow:0 6px 18px rgba(16,22,47,.06);
  transform:translateY(-2px);
}
.ftx-cc-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:34px; height:34px; border-radius:10px;
  background:rgba(106,0,255,.07); color:#6A00FF; font-size:13px;
}
.ftx-cc-body{ display:flex; flex-direction:column; gap:3px; min-width:0; }
.ftx-cc-body small{
  font-size:10.5px; font-weight:600; letter-spacing:.1em;
  text-transform:uppercase; color:#6E7386;
}
.ftx-cc-body span,
.ftx-cc-body a{
  font-size:13.5px; line-height:1.55; color:var(--f-navy);
  text-decoration:none; word-break:break-word;
}
.ftx-cc-body a:hover{ color:#6A00FF; }

/* Ask AI */
.ftx-ai{ margin-top:26px; }
/* All five platforms share one row at any column width: equal flex
   basis rather than a fixed 56px, which used to overflow and wrap. */
.ftx-ai-row{ display:flex; gap:6px; flex-wrap:nowrap; }
.ftx-ai-card{
  flex:1 1 0; min-width:0;
  display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px;
  padding:9px 2px; border-radius:11px;
  background:#fff; border:1px solid var(--f-line);
  text-decoration:none;
  transition:border-color .2s ease, transform .2s ease, box-shadow .2s ease;
}
.ftx-ai-card:hover{
  border-color:rgba(106,0,255,.38); transform:translateY(-3px);
  box-shadow:0 6px 16px rgba(16,22,47,.07);
}
.ftx-ai-ico{ display:grid; place-items:center; height:22px; }
.ftx-ai-card small{
  font-size:9px; font-weight:600; color:#5E6475;
  max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}

/* ── 3 · Copyright bar ──────────────────────────────────
   Mirrors .hx-util at the top of the page. The ramp stops at #E0006E
   because white text drops to 3.67:1 on #FF3030 and 2.36:1 on #FF8A00,
   which is where a label stops being readable. */
.ftx-bottom{
  position:relative; overflow:hidden;
  color:#fff;
  background:#6A00FF;
  background-image:linear-gradient(90deg,
    #4A00B0 0%, #6A00FF 26%, #9D00D3 52%, #D000A8 78%, #E0006E 100%);
  background-size:180% 100%;
  animation:ftxBarDrift 22s ease-in-out infinite;
}
@keyframes ftxBarDrift{
  0%,100%{ background-position:0% 50%; }
  50%    { background-position:100% 50%; }
}
/* Hairline highlight along the top edge, matching the utility bar */
.ftx-bottom::before{
  content:""; position:absolute; left:0; right:0; top:0; height:1px;
  background:rgba(255,255,255,.18); pointer-events:none;
}

/* One compact line: copyright left, legal links right, nothing wraps. */
.ftx-bottom-inner{
  position:relative; z-index:1;
  display:flex; align-items:center; justify-content:space-between;
  gap:clamp(14px,2.5vw,32px);
  flex-wrap:nowrap;
  padding-block:15px;
  /* leave room for the floating WhatsApp / back-to-top buttons */
  padding-right:clamp(20px,7vw,96px);
}
.ftx-copy{
  margin:0; flex:0 1 auto; min-width:0;
  font-size:13px; line-height:1.5; color:rgba(255,255,255,.92);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.ftx-copy a{ color:#fff; font-weight:700; text-decoration:none; }
.ftx-copy a:hover{ color:#fff; text-decoration:underline; }
.ftx-dot{ margin:0 7px; color:rgba(255,255,255,.45); }

.ftx-legal{
  display:flex; align-items:center; flex:0 1 auto;
  flex-wrap:nowrap; min-width:0;
}
.ftx-legal a{
  flex:0 0 auto;
  padding:3px 2px;
  font-size:12.5px; font-weight:500;
  color:rgba(255,255,255,.90); text-decoration:none;
  white-space:nowrap;
  transition:color .2s ease, text-shadow .2s ease;
}
.ftx-legal a:hover{ color:#fff; text-shadow:0 0 12px rgba(255,255,255,.55); }
.ftx-legal a:focus-visible{ outline:2px solid #fff; outline-offset:2px; border-radius:4px; }
.ftx-sep{
  flex:0 0 auto; width:1px; height:11px; margin:0 11px;
  background:rgba(255,255,255,.30);
}

/* ── Responsive ── */
/* Tablet: tighten the bar so it still holds one line */
@media (max-width:1080px){
  .ftx-copy{ font-size:12.5px; }
  .ftx-legal a{ font-size:12px; }
  .ftx-sep{ margin:0 8px; }
  .ftx-bottom-inner{ gap:16px; padding-right:clamp(16px,5vw,72px); }
  .ftx-grid{ grid-template-columns:1.4fr 1fr 1fr; }
  .ftx-grid > :last-child{ grid-column:1/-1; }
  .ftx-contact{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
}
@media (max-width:860px){
  .nlx{ grid-template-columns:1fr; }
  .ftx-grid{ grid-template-columns:1fr 1fr; }
  .ftx-grid > :first-child{ grid-column:1/-1; }
}
@media (max-width:620px){
  .ftx-grid{ grid-template-columns:1fr; gap:34px; }
  .ftx-grid > :last-child,
  .ftx-grid > :first-child{ grid-column:auto; }
  .nlx{ padding:24px 20px; border-radius:18px; }
  .nlx::before{ border-radius:18px; }
  .nlx-row{ flex-direction:column; }
  .nlx-btn{ width:100%; }
  .nlx-copy{ gap:14px; }
  .ftx-badges{ max-width:none; }
  .ftx-contact{ grid-template-columns:1fr; }
  /* Only once the line genuinely cannot fit: stack the two halves and let
     the legal row scroll sideways rather than wrap into ragged rows. */
  .ftx-bottom-inner{
    flex-direction:column; align-items:flex-start; gap:10px;
    padding-right:20px; padding-block:14px;
  }
  .ftx-copy{ white-space:normal; font-size:12.5px; }
  .ftx-legal{
    width:100%; overflow-x:auto; scrollbar-width:none;
    -webkit-overflow-scrolling:touch;
    padding-bottom:2px;
  }
  .ftx-legal::-webkit-scrollbar{ display:none; }
  .ftx-sep{ margin:0 9px; }
}
@media (prefers-reduced-motion:reduce){
  .ftx *,.ftx *::before,.ftx *::after{ transition:none !important; animation:none !important; }
  .ftx-bottom{ animation:none; background-size:100% 100%; }
}
</style>

<!-- ══════════════════════════════════════
     FLOATING ELEMENTS
══════════════════════════════════════ -->
<?php if ($_waClean): ?>
<?php /* Get Enquiry popup — writes to `leads`, shows in Admin -> Enquiries */ ?>
<?php require __DIR__ . '/enquiry-popup.php'; ?>

<a href="https://wa.me/<?= e($_waClean) ?>" class="whatsapp-float" title="Chat on WhatsApp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <i class="fab fa-whatsapp" aria-hidden="true"></i>
</a>
<?php endif; ?>

<?php /* AI chatbot — outside the WhatsApp gate above, which it has nothing
         to do with. Renders nothing unless it is switched on in Admin and an
         OpenAI key is configured. Writes to `chatbot_leads`, shown in
         Admin -> Chatbot Leads. */ ?>
<?php require __DIR__ . '/chatbot-widget.php'; ?>

<button class="stt-btn" id="sttBtn" aria-label="Back to top" title="Back to top">
  <i class="fas fa-chevron-up" aria-hidden="true"></i>
</button>

<style>
.whatsapp-float{
  position:fixed; right:22px; bottom:90px; z-index:60;
  width:52px; height:52px; border-radius:50%;
  display:grid; place-items:center;
  background:#25D366; color:#fff; font-size:25px; text-decoration:none;
  box-shadow:0 6px 20px rgba(37,211,102,.34);
  transition:transform .2s ease, box-shadow .2s ease;
}
.whatsapp-float:hover{ transform:translateY(-3px); color:#fff; box-shadow:0 10px 26px rgba(37,211,102,.42); }
.stt-btn{
  position:fixed; right:22px; bottom:28px; z-index:60;
  width:48px; height:48px; border-radius:50%; cursor:pointer; border:0;
  display:grid; place-items:center;
  background:linear-gradient(135deg,#F50072,#6A00FF); color:#fff; font-size:15px;
  box-shadow:0 6px 18px rgba(106,0,255,.28);
  opacity:0; visibility:hidden; transform:translateY(10px);
  transition:opacity .25s ease, transform .25s ease, visibility .25s ease, box-shadow .2s ease;
}
.stt-btn.show{ opacity:1; visibility:visible; transform:translateY(0); }
.stt-btn:hover{ box-shadow:0 10px 26px rgba(106,0,255,.36); }
@media (max-width:560px){
  .whatsapp-float{ width:48px; height:48px; font-size:22px; bottom:82px; right:16px; }
  .stt-btn{ width:44px; height:44px; right:16px; bottom:24px; }
}
</style>

<!-- Cookie consent -->
<div class="agc" id="agCookie" role="dialog" aria-modal="false"
     aria-labelledby="agCookieTitle" aria-describedby="agCookieText" hidden>
  <div class="agc-body">
    <span class="agc-ico" aria-hidden="true"><i class="fas fa-cookie-bite"></i></span>
    <div>
      <strong class="agc-title" id="agCookieTitle">We use cookies</strong>
      <p class="agc-text" id="agCookieText">
        Analytics cookies help us understand how the site is used. They only load if you accept.
        <a href="<?= $_siteUrl ?>/cookie-policy.php">Cookie Policy</a>
      </p>
    </div>
  </div>
  <div class="agc-actions">
    <button type="button" class="agc-btn agc-decline" id="agCookieDecline">Decline</button>
    <button type="button" class="agc-btn agc-accept"  id="agCookieAccept">Accept</button>
  </div>
</div>

<style>
/* Compact card, bottom-left, clear of the WhatsApp / back-to-top floats */
.agc{
  position:fixed; left:20px; bottom:20px; z-index:70;
  width:min(420px, calc(100vw - 40px));
  display:flex; flex-direction:column; gap:14px;
  padding:18px 20px; border-radius:16px;
  background:#fff; border:1px solid #E7E9F0;
  box-shadow:0 16px 44px rgba(16,22,47,.16);
  font-family:var(--font-body),system-ui,sans-serif;
  opacity:0; transform:translateY(14px);
  transition:opacity .3s ease, transform .3s cubic-bezier(.4,0,.2,1);
}
.agc.is-in{ opacity:1; transform:none; }
.agc-body{ display:flex; gap:13px; align-items:flex-start; }
.agc-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:38px; height:38px; border-radius:11px; font-size:16px; color:#fff;
  background:linear-gradient(135deg,#FF8A00,#F50072,#6A00FF);
}
.agc-title{ display:block; font-size:14.5px; font-weight:700; color:#10162F; margin-bottom:4px; }
.agc-text{ margin:0; font-size:13px; line-height:1.6; color:#667085; }
.agc-text a{ color:#6A00FF; font-weight:600; text-decoration:none; }
.agc-text a:hover{ text-decoration:underline; }
.agc-actions{ display:flex; gap:9px; }
.agc-btn{
  flex:1; height:40px; border-radius:10px; cursor:pointer;
  font-family:inherit; font-size:13.5px; font-weight:600;
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease, color .18s ease;
}
.agc-btn:hover{ transform:translateY(-1px); }
.agc-accept{
  border:0; color:#fff;
  background:linear-gradient(120deg,#F50072,#6A00FF);
  box-shadow:0 4px 12px rgba(106,0,255,.24);
}
.agc-decline{ background:#fff; border:1px solid #E7E9F0; color:#10162F; }
.agc-decline:hover{ border-color:#C9CCD6; }
@media (max-width:560px){
  .agc{ left:12px; right:12px; bottom:12px; width:auto; padding:16px; }
  .agc-actions{ flex-direction:row; }
}
@media (prefers-reduced-motion:reduce){
  .agc{ transition:none; }
}
</style>

<?php
/* ── Google Tag Manager noscript fallback ── */
$_gtmId = trim((string)($_sf['google_tag_manager'] ?? ''));
if ($_gtmId): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($_gtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<!-- Scripts -->
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<script src="<?= ASSETS_URL ?>/js/captcha-cta.js" defer></script>
<script>window.__AG_ANALYTICS_API='<?= SITE_URL ?>/api/analytics.php';</script>
<script src="<?= ASSETS_URL ?>/js/analytics.js" defer></script>

<script>
/* ── Back to top ── */
(function () {
  var btn = document.getElementById('sttBtn');
  if (!btn) return;
  function sync() { btn.classList.toggle('show', window.scrollY > 420); }
  window.addEventListener('scroll', sync, { passive: true });
  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  sync();
})();

/* ── Newsletter ── */
function submitNewsletter(e) {
  e.preventDefault();
  var nameEl  = document.getElementById('nl_name');
  var emailEl = document.getElementById('nl_email');
  var msg     = document.getElementById('nl_msg');
  var btn     = document.getElementById('nl_btn');
  var email   = emailEl ? emailEl.value.trim() : '';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
    emailEl.setAttribute('aria-invalid', 'true');
    msg.className = 'nlx-msg is-err';
    msg.textContent = 'Please enter a valid email address.';
    emailEl.focus();
    return;
  }
  emailEl.removeAttribute('aria-invalid');

  /* Swap the label only. Replacing innerHTML would throw away the icon
     and, if a request never settles, leave the button visibly empty. */
  var label    = btn.querySelector('.nlx-btn-label') || btn;
  var original = label.textContent;
  btn.disabled = true;
  label.textContent = 'Subscribing…';

  var params = new URLSearchParams();
  params.append('email', email);
  params.append('name', nameEl ? nameEl.value.trim() : '');
  params.append('<?= CSRF_TOKEN_NAME ?>', '<?= e(csrfToken()) ?>');

  fetch('<?= $_siteUrl ?>/api/newsletter.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    btn.disabled = false;
    if (d.ok || d.success) {
      label.textContent = 'Subscribed';
      msg.className = 'nlx-msg is-ok';
      msg.textContent = d.message || 'Thanks — you are on the list.';
      document.getElementById('footerNewsletterForm').reset();
      setTimeout(function () { label.textContent = original; msg.className = 'nlx-msg'; }, 4000);
    } else {
      label.textContent = original;
      msg.className = 'nlx-msg is-err';
      msg.textContent = d.error || d.message || 'Something went wrong. Please try again.';
    }
  })
  .catch(function () {
    btn.disabled = false;
    label.textContent = original;
    msg.className = 'nlx-msg is-err';
    msg.textContent = 'Network error. Please try again.';
  });
}
</script>
<?php
/* Google Analytics is loaded once, in includes/meta.php (inside <head>).
   Loading it again here produced duplicate page_view hits, so this block
   intentionally emits nothing. */
?>
