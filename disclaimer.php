<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Disclaimer – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Disclaimer — important notices about website accuracy, professional advice limitations, and liability.';
$canonicalUrl    = SITE_URL . '/disclaimer.php';
$pageKey         = 'disclaimer';

$content  = dbFetchOne("SELECT * FROM pages WHERE page_key = ? AND is_active = 1", [$pageKey]);
$siteName = getSetting('site_name', 'Appsgain Technologies');
$email    = getSetting('site_email', getSetting('email', 'info@appsgain.in'));
$phone    = getSetting('site_phone', getSetting('phone', '+91-9955446477'));
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
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/policy.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Legal',
  'lead'    => 'Legal',
  'accent'  => 'Disclaimer',
  'sub'     => 'The information on this website is provided in good faith. Please read this disclaimer carefully to understand the limitations of the information provided and your rights.',
  'crumbs'  => [['Disclaimer', null]],
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
        <a href="#section-general"><i class="fas fa-circle"></i> General Disclaimer</a>
        <a href="#section-accuracy"><i class="fas fa-circle"></i> Website Accuracy</a>
        <a href="#section-professional"><i class="fas fa-circle"></i> Professional Advice</a>
        <a href="#section-external"><i class="fas fa-circle"></i> External Links</a>
        <a href="#section-liability"><i class="fas fa-circle"></i> Limitation of Liability</a>
        <a href="#section-testimonials"><i class="fas fa-circle"></i> Testimonials</a>
        <a href="#section-ip"><i class="fas fa-circle"></i> Intellectual Property</a>
        <?php else: ?>
        <a href="#disclaimer-top"><i class="fas fa-circle"></i> Disclaimer</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Have legal questions or concerns about this disclaimer?</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-shield-alt" style="color:var(--violet);width:14px;"></i> Privacy Policy</a>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
        <a href="<?= SITE_URL ?>/refund-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-undo" style="color:var(--emerald);width:14px;"></i> Refund Policy</a>
      </div>
    </aside>

    <main class="policy-main" id="disclaimer-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-exclamation-circle"></i> Legal Notice</span>
          <h1>Legal <span>Disclaimer</span></h1>
          <p>The information on this website is provided in good faith. Please read this disclaimer carefully to understand the limitations of the information provided and your rights.</p>
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

        <div class="policy-section" id="section-general">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>General Disclaimer</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <div class="policy-highlight-box">
            <p>The information contained on the <?= e($siteName) ?> website (<a href="<?= SITE_URL ?>" style="color:var(--violet);"><?= SITE_URL ?></a>) is provided for <strong>general informational purposes only</strong>. While we endeavour to keep the information up to date and correct, we make no representations or warranties of any kind, express or implied, about the completeness, accuracy, reliability, suitability, or availability of the information, products, services, or related graphics on the website.</p>
          </div>
          <p>By using this website, you hereby consent to our disclaimer and agree to its terms. Any reliance you place on such information is therefore strictly at your own risk.</p>
        </div>

        <div class="policy-section" id="section-accuracy">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-check-double"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Website Accuracy</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We strive to ensure that the information on our website is accurate and up to date. However:</p>
          <ul class="policy-list">
            <li>Pricing, service details, and availability information may change without notice</li>
            <li>Portfolio, case studies, and statistics are based on real data but may not reflect current performance</li>
            <li>Technology recommendations and best practices evolve rapidly; information may become outdated</li>
            <li>Blog posts and articles represent the views and opinions of the author at the time of writing</li>
          </ul>
          <p>We reserve the right to make additions, deletions, or modifications to the content at any time without prior notice.</p>
        </div>

        <div class="policy-section" id="section-professional">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-user-tie"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Not Professional Advice</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>The content on this website is intended for <strong>general informational and marketing purposes</strong> only. It does not constitute:</p>
          <ul class="policy-list">
            <li>Legal, financial, investment, or accounting advice</li>
            <li>Medical, health, or psychological advice or diagnosis</li>
            <li>Guaranteed business outcomes or returns on investment</li>
            <li>A substitute for professional consultation with qualified experts</li>
          </ul>
          <p>Before making any business, technology, or financial decisions based on information found on this website, we strongly recommend consulting with qualified professionals. <?= e($siteName) ?> is not liable for any decisions made based on website content.</p>
        </div>

        <div class="policy-section" id="section-external">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-external-link-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>External Links</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>Through this website, you may be able to link to other websites which are not under the control of <?= e($siteName) ?>. We have no control over the nature, content, and availability of those sites. The inclusion of any links does not necessarily imply a recommendation or endorsement of the views expressed within them.</p>
          <p>We are not responsible for:</p>
          <ul class="policy-list">
            <li>The privacy practices or content of linked third-party websites</li>
            <li>Any products, services, or information offered by external websites</li>
            <li>Any damages or losses resulting from visiting external websites</li>
            <li>Any broken, outdated, or redirected external links</li>
          </ul>
        </div>

        <div class="policy-section" id="section-liability">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-balance-scale"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Limitation of Liability</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>In no event shall <?= e($siteName) ?>, its directors, employees, partners, agents, suppliers, or affiliates, be liable for any:</p>
          <ul class="policy-list">
            <li>Indirect, incidental, special, consequential, or punitive damages</li>
            <li>Loss of profits, data, use, goodwill, or other intangible losses</li>
            <li>Damages resulting from your use of or inability to use our website</li>
            <li>Damages resulting from any errors or omissions in the website's content</li>
            <li>Damages resulting from third-party conduct or website outages</li>
          </ul>
          <div class="policy-highlight-box">
            <p>To the extent permitted by applicable law, <?= e($siteName) ?>'s total liability for any claim related to this website shall not exceed INR 10,000 (Ten Thousand Rupees).</p>
          </div>
        </div>

        <div class="policy-section" id="section-testimonials">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#6a00ff)"><i class="fas fa-quote-right"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Testimonials & Case Studies</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>Testimonials, reviews, and case studies featured on this website reflect the individual experiences of real clients. However:</p>
          <ul class="policy-list">
            <li>Individual results may vary based on project scope, client participation, and market conditions</li>
            <li>Testimonials are not a guarantee of similar results for your specific situation</li>
            <li>Performance metrics and ROI figures are examples and do not guarantee future performance</li>
            <li>We have permission from all featured clients to display their testimonials</li>
          </ul>
        </div>

        <div class="policy-section" id="section-ip">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-copyright"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Intellectual Property Notice</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>All content on this website — including but not limited to text, images, graphics, logos, icons, audio clips, and software — is the intellectual property of <?= e($siteName) ?> or its content suppliers, and is protected by Indian and international copyright laws.</p>
          <p>Unauthorised use, reproduction, distribution, or modification of any content from this website is strictly prohibited and may result in legal action. You may view, download, and print content for personal, non-commercial use only, provided you do not modify the content and include our copyright notice.</p>
          <p>For permission to use our content commercially or for media enquiries, please contact us at <a href="mailto:<?= e($email) ?>" style="color:var(--violet);"><?= e($email) ?></a>.</p>
        </div>

        <?php endif; ?>

        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-exclamation-circle" style="margin-right:10px;opacity:.8;"></i> Legal Enquiries</h3>
            <p><?= e($siteName) ?> — For questions about this disclaimer or legal matters, please contact us.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="<?= SITE_URL ?>/contact.php" class="outline"><i class="fas fa-comments"></i> Contact Us</a>
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
