<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Cookie Policy – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Cookie Policy — what cookies we use, why, and how you can manage your cookie preferences.';
$canonicalUrl    = SITE_URL . '/cookie-policy.php';
$pageKey         = 'cookie';

$content  = dbFetchOne("SELECT * FROM pages WHERE page_key = ? AND is_active = 1", [$pageKey]);
$siteName = getSetting('site_name', 'Appsgain Technologies');
$email    = getSetting('site_email', getSetting('email', 'info@appsgain.in'));
$lastUpdated = !empty($content['updated_at']) ? date('d F Y', strtotime($content['updated_at'])) : date('d F Y');
$hasCustomContent = !empty($content['content']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <?php $pageStyles = ['page-hero.css']; ?>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/policy.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Legal',
  'lead'    => 'Cookie',
  'accent'  => 'Policy',
  'sub'     => 'This policy explains how ' . e($siteName) . ' uses cookies and similar tracking technologies on our website, and how you can manage your preferences.',
  'crumbs'  => [['Cookie Policy', null]],
]); ?>

<div class="policy-page-wrap">
  <div class="policy-layout">

    <aside class="policy-sidebar">
      <div class="policy-sidebar-header">
        <h4><i class="fas fa-list-ul" style="margin-right:8px;opacity:.8;"></i> Table of Contents</h4>
        <p>Jump to any section</p>
      </div>
      <nav class="policy-toc" id="policyToc">
        <?php if (!$hasCustomContent): ?>
        <a href="#section-what"><i class="fas fa-circle"></i> What Are Cookies?</a>
        <a href="#section-types"><i class="fas fa-circle"></i> Types of Cookies</a>
        <a href="#section-specific"><i class="fas fa-circle"></i> Specific Cookies Used</a>
        <a href="#section-third-party"><i class="fas fa-circle"></i> Third-Party Cookies</a>
        <a href="#section-manage"><i class="fas fa-circle"></i> Managing Cookies</a>
        <a href="#section-updates"><i class="fas fa-circle"></i> Policy Updates</a>
        <?php else: ?>
        <a href="#cookie-top"><i class="fas fa-circle"></i> Cookie Policy</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Need help managing your cookie preferences?</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-shield-alt" style="color:var(--violet);width:14px;"></i> Privacy Policy</a>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
      </div>
    </aside>

    <main class="policy-main" id="cookie-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-cookie-bite"></i> Cookie & Tracking Policy</span>
          <h1>Cookie <span>Policy</span></h1>
          <p>This policy explains how <?= e($siteName) ?> uses cookies and similar tracking technologies on our website, and how you can manage your preferences.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fas fa-globe"></i> Applies to: <?= e(SITE_URL) ?></div>
          </div>
        </div>
      </div>

      <div class="policy-body">

        <?php if ($hasCustomContent): ?>
        <div class="policy-section">
          <div class="policy-content-custom"><?= $content['content'] ?></div>
        </div>
        <?php else: ?>

        <div class="policy-section" id="section-what">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-cookie-bite"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>What Are Cookies?</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>Cookies are small text files that are placed on your device (computer, smartphone, or tablet) when you visit a website. They are widely used to make websites work more efficiently, provide a better user experience, and give website owners useful information about how their site is being used.</p>
          <div class="policy-highlight-box">
            <p><strong>Cookies do not give us access to your device</strong> and do not contain personal information unless you have provided it to us. They are stored on your device and sent back to our website each time you visit.</p>
          </div>
        </div>

        <div class="policy-section" id="section-types">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-layer-group"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Types of Cookies We Use</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We use the following categories of cookies on our website:</p>

          <table class="cookie-table">
            <thead>
              <tr>
                <th>Cookie Category</th>
                <th>Purpose</th>
                <th>Duration</th>
                <th>Required?</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="cookie-type-badge essential">Essential</span><br><strong>Strictly Necessary</strong></td>
                <td>Required for the website to function. These enable core features such as security, session management and accessibility. Cannot be disabled.</td>
                <td>Session to 1 year</td>
                <td style="text-align:center;"><i class="fas fa-check-circle" style="color:#059669;"></i> Yes</td>
              </tr>
              <tr>
                <td><span class="cookie-type-badge analytics">Analytics</span><br><strong>Performance</strong></td>
                <td>Help us understand how visitors interact with our website by collecting anonymous statistical information such as page views, bounce rates and traffic sources.</td>
                <td>Up to 2 years</td>
                <td style="text-align:center;"><i class="fas fa-times-circle" style="color:#9ca3af;"></i> Optional</td>
              </tr>
              <tr>
                <td><span class="cookie-type-badge functional">Functional</span><br><strong>Preference</strong></td>
                <td>Remember your choices and personalise your experience, such as language preferences and your cookie consent status.</td>
                <td>1 year</td>
                <td style="text-align:center;"><i class="fas fa-times-circle" style="color:#9ca3af;"></i> Optional</td>
              </tr>
              <tr>
                <td><span class="cookie-type-badge marketing">Marketing</span><br><strong>Targeting</strong></td>
                <td>Used to deliver relevant advertisements and track the effectiveness of our marketing campaigns across websites.</td>
                <td>Up to 90 days</td>
                <td style="text-align:center;"><i class="fas fa-times-circle" style="color:#9ca3af;"></i> Optional</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="policy-section" id="section-specific">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-search-plus"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Specific Cookies We Use</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <table class="cookie-table">
            <thead>
              <tr>
                <th>Cookie Name</th>
                <th>Provider</th>
                <th>Category</th>
                <th>Purpose</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>PHPSESSID</code></td>
                <td><?= e($siteName) ?></td>
                <td><span class="cookie-type-badge essential">Essential</span></td>
                <td>PHP session management — maintains your session state while browsing</td>
              </tr>
              <tr>
                <td><code>cookie_accepted</code></td>
                <td><?= e($siteName) ?></td>
                <td><span class="cookie-type-badge functional">Functional</span></td>
                <td>Stores your cookie consent preference so the banner isn't shown repeatedly</td>
              </tr>
              <tr>
                <td><code>_ga</code>, <code>_gid</code></td>
                <td>Google Analytics</td>
                <td><span class="cookie-type-badge analytics">Analytics</span></td>
                <td>Distinguishes users and records session data for traffic analysis</td>
              </tr>
              <tr>
                <td><code>_gcl_au</code></td>
                <td>Google Ads</td>
                <td><span class="cookie-type-badge marketing">Marketing</span></td>
                <td>Stores and tracks conversions from Google Ads campaigns</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="policy-section" id="section-third-party">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-external-link-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>Third-Party Cookies</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Some cookies on our website are set by third-party services. These third parties have their own privacy and cookie policies:</p>
          <ul class="policy-list">
            <li><strong>Google Analytics:</strong> We use Google Analytics to understand website traffic and user behaviour. Google's privacy policy applies to data processed by Google.</li>
            <li><strong>Google Ads:</strong> We may use Google Ads for remarketing and conversion tracking.</li>
            <li><strong>Social Media Platforms:</strong> Pages with social sharing buttons may set cookies from Facebook, LinkedIn, Twitter, and YouTube.</li>
          </ul>
          <p>We do not control these third-party cookies and recommend reviewing their respective privacy policies for details on how your data is handled.</p>
        </div>

        <div class="policy-section" id="section-manage">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-sliders-h"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Managing Your Cookie Preferences</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>You have several options to control and manage cookies:</p>
          <ul class="policy-list">
            <li>Use our cookie consent banner to accept or decline non-essential cookies when you first visit our site</li>
            <li>Delete all cookies already stored on your device through your browser settings</li>
            <li>Configure your browser to block or alert you when cookies are being set</li>
            <li>Use browser extensions or privacy tools to manage third-party tracking</li>
          </ul>
          <p>For browser-specific cookie management instructions:</p>
          <div class="rights-grid">
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(66,133,244,.1)"><i class="fab fa-chrome" style="color:#4285f4;font-size:18px;"></i></div>
              <div><h4>Google Chrome</h4><p><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener" style="color:var(--violet);">Manage cookies in Chrome</a></p></div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(255,107,0,.1)"><i class="fab fa-firefox-browser" style="color:#ff6900;font-size:18px;"></i></div>
              <div><h4>Mozilla Firefox</h4><p><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener" style="color:var(--violet);">Manage cookies in Firefox</a></p></div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(0,122,255,.1)"><i class="fab fa-safari" style="color:#007aff;font-size:18px;"></i></div>
              <div><h4>Safari</h4><p><a href="https://support.apple.com/en-us/105082" target="_blank" rel="noopener" style="color:var(--violet);">Manage cookies in Safari</a></p></div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(0,120,212,.1)"><i class="fab fa-edge" style="color:#0078d4;font-size:18px;"></i></div>
              <div><h4>Microsoft Edge</h4><p><a href="https://support.microsoft.com/en-us/windows/manage-cookies-in-microsoft-edge-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener" style="color:var(--violet);">Manage cookies in Edge</a></p></div>
            </div>
          </div>
          <div class="policy-highlight-box">
            <p><strong>Note:</strong> Disabling certain cookies may affect the functionality and user experience of our website. Essential cookies cannot be disabled as they are required for the site to work.</p>
          </div>
        </div>

        <div class="policy-section" id="section-updates">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-sync-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Updates to This Policy</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We may update this Cookie Policy from time to time to reflect changes in technology, regulation, or our business practices. All changes will be posted on this page with an updated effective date. We encourage you to check this page periodically.</p>
        </div>

        <?php endif; ?>

        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-cookie-bite" style="margin-right:10px;opacity:.8;"></i> Cookie Preferences</h3>
            <p><?= e($siteName) ?> — Questions about our cookies? Get in touch and we'll be happy to help.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="<?= SITE_URL ?>/privacy-policy.php" class="outline"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
            </div>
          </div>
        </div>

      </div>
    </main>

  </div>
</div>

<script>
(function() {
  const tocLinks = document.querySelectorAll('#policyToc a[href^="#"]');
  tocLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const target = document.querySelector(link.getAttribute('href'));
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
  const sections = Array.from(document.querySelectorAll('.policy-section[id]'));
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(sec => { if (window.scrollY >= sec.offsetTop - 160) current = sec.id; });
    tocLinks.forEach(link => { link.classList.toggle('active', link.getAttribute('href') === '#' + current); });
  }, { passive: true });
})();
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
