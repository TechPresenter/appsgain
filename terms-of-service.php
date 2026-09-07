<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Terms & Conditions – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Terms & Conditions — the rules and guidelines governing use of our website and services.';
$canonicalUrl    = SITE_URL . '/terms-of-service.php';
$pageKey         = 'terms';

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
  'lead'    => 'Terms &',
  'accent'  => 'Conditions',
  'sub'     => 'Please read these Terms carefully before using our services. By accessing our website or engaging our services, you agree to be bound by these Terms.',
  'crumbs'  => [['Terms & Conditions', null]],
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
        <a href="#section-acceptance"><i class="fas fa-circle"></i> Acceptance of Terms</a>
        <a href="#section-services"><i class="fas fa-circle"></i> Services Description</a>
        <a href="#section-ip"><i class="fas fa-circle"></i> Intellectual Property</a>
        <a href="#section-user"><i class="fas fa-circle"></i> User Responsibilities</a>
        <a href="#section-payment"><i class="fas fa-circle"></i> Payment Terms</a>
        <a href="#section-confidentiality"><i class="fas fa-circle"></i> Confidentiality</a>
        <a href="#section-liability"><i class="fas fa-circle"></i> Limitation of Liability</a>
        <a href="#section-termination"><i class="fas fa-circle"></i> Termination</a>
        <a href="#section-governing"><i class="fas fa-circle"></i> Governing Law</a>
        <a href="#section-changes"><i class="fas fa-circle"></i> Changes to Terms</a>
        <?php else: ?>
        <a href="#terms-top"><i class="fas fa-circle"></i> Terms & Conditions</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Questions about our terms? Our team is here to help.</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-shield-alt" style="color:var(--violet);width:14px;"></i> Privacy Policy</a>
        <a href="<?= SITE_URL ?>/refund-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-undo" style="color:var(--emerald);width:14px;"></i> Refund Policy</a>
        <a href="<?= SITE_URL ?>/disclaimer.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-exclamation-circle" style="color:var(--amber);width:14px;"></i> Disclaimer</a>
      </div>
    </aside>

    <main class="policy-main" id="terms-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-file-contract"></i> Legal Agreement</span>
          <h1>Terms &amp; <span>Conditions</span></h1>
          <p>Please read these Terms carefully before using our services. By accessing our website or engaging our services, you agree to be bound by these Terms.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fas fa-map-marker-alt"></i> Jurisdiction: India</div>
            <div class="policy-meta-item"><i class="fas fa-language"></i> Version: 1.0</div>
          </div>
        </div>
      </div>

      <div class="policy-body">

        <?php if ($hasCustomContent): ?>
        <div class="policy-section">
          <div class="policy-content-custom"><?= $content['content'] ?></div>
        </div>
        <?php else: ?>

        <div class="policy-section" id="section-acceptance">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-handshake"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>Acceptance of Terms</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <div class="policy-highlight-box">
            <p>By accessing and using <?= e($siteName) ?>'s website and services, you acknowledge that you have read, understood, and agree to be bound by these Terms. If you do not agree to these Terms, please do not use our services.</p>
          </div>
          <p>These Terms constitute a legally binding agreement between you ("Client", "User", "you") and <?= e($siteName) ?> ("Company", "we", "us", "our"). These Terms apply to all visitors, users and others who access or use our website or services.</p>
        </div>

        <div class="policy-section" id="section-services">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-cogs"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Services Description</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p><?= e($siteName) ?> provides the following categories of professional services:</p>
          <ul class="policy-list">
            <li>Custom software development, web development, and mobile application development</li>
            <li>Digital marketing, SEO, social media management, and content marketing</li>
            <li>IT training programmes, courses, and professional development</li>
            <li>UI/UX design, branding, and creative services</li>
            <li>Cloud computing, DevOps, and infrastructure solutions</li>
            <li>AI integration, data analytics, and business intelligence solutions</li>
          </ul>
          <p>The specific scope of services for any engagement is defined in a project proposal, service agreement, or statement of work signed by both parties.</p>
        </div>

        <div class="policy-section" id="section-ip">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-copyright"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Intellectual Property</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>All content on this website — including text, graphics, logos, images, software code, and multimedia — is the intellectual property of <?= e($siteName) ?> and is protected by applicable intellectual property laws.</p>
          <ul class="policy-list">
            <li>You may not reproduce, distribute, or create derivative works without our express written consent</li>
            <li>Upon full payment for a project, client-specific deliverables (source code, designs) are transferred to the client as defined in the project agreement</li>
            <li>We retain the right to display completed work in our portfolio unless otherwise agreed in writing</li>
            <li>Third-party components incorporated in our deliverables remain subject to their respective licences</li>
          </ul>
        </div>

        <div class="policy-section" id="section-user">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-user-check"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>User Responsibilities</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>When using our services, you agree to:</p>
          <ul class="policy-list">
            <li>Provide accurate, complete and current information about yourself and your project requirements</li>
            <li>Not use our services for any illegal, unlawful, or unauthorised purposes</li>
            <li>Not attempt to disrupt, damage or gain unauthorised access to our website, servers or networks</li>
            <li>Respect the intellectual property rights of <?= e($siteName) ?> and third parties</li>
            <li>Provide timely feedback, approvals and required project assets to avoid delays</li>
            <li>Not misrepresent your identity or affiliation with any person or organisation</li>
          </ul>
        </div>

        <div class="policy-section" id="section-payment">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#10b981)"><i class="fas fa-credit-card"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Payment Terms</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>Payment terms are specified in individual project proposals and service agreements. Standard terms include:</p>
          <ul class="policy-list">
            <li>An advance payment (typically 30–50%) is required before project commencement</li>
            <li>Milestone-based payments as defined in the project schedule</li>
            <li>Final payment due upon project completion and before final delivery</li>
            <li>Late payments may attract interest charges as specified in the agreement</li>
            <li>All fees are in Indian Rupees (INR) unless otherwise stated</li>
          </ul>
          <div class="policy-highlight-box">
            <p>For our <a href="<?= SITE_URL ?>/refund-policy.php" style="color:var(--violet);font-weight:600;">Refund & Cancellation Policy</a>, please refer to the dedicated policy page.</p>
          </div>
        </div>

        <div class="policy-section" id="section-confidentiality">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-user-secret"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Confidentiality</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Both parties agree to maintain the confidentiality of proprietary information shared during the engagement. We are happy to sign a Non-Disclosure Agreement (NDA) upon request prior to detailed project discussions. Confidentiality obligations survive termination of any service agreement.</p>
        </div>

        <div class="policy-section" id="section-liability">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-balance-scale"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Limitation of Liability</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p><?= e($siteName) ?>'s total liability for any claim arising from our services is limited to the amount paid for the specific service giving rise to the claim in the preceding three months. We are not liable for:</p>
          <ul class="policy-list">
            <li>Indirect, incidental, special, consequential, or punitive damages</li>
            <li>Loss of profits, data, business opportunities, or goodwill</li>
            <li>Third-party actions, service outages, or force majeure events</li>
            <li>Damages arising from your failure to follow our recommendations or guidelines</li>
          </ul>
        </div>

        <div class="policy-section" id="section-termination">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#46009f)"><i class="fas fa-times-circle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 08</div>
              <h2>Termination</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Either party may terminate a service engagement by providing written notice as specified in the project agreement. Upon termination:</p>
          <ul class="policy-list">
            <li>All outstanding payments become immediately due and payable</li>
            <li>Work completed up to the termination date will be invoiced at the agreed rate</li>
            <li>Client shall receive all completed deliverables upon receipt of payment</li>
            <li>Confidentiality and intellectual property obligations survive termination</li>
          </ul>
        </div>

        <div class="policy-section" id="section-governing">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#6a00ff)"><i class="fas fa-gavel"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 09</div>
              <h2>Governing Law</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>These Terms are governed by and construed in accordance with the laws of India. Any dispute arising from or relating to these Terms shall be subject to the exclusive jurisdiction of the competent courts in New Delhi, India. You irrevocably consent to the personal jurisdiction of such courts.</p>
        </div>

        <div class="policy-section" id="section-changes">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-sync-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 10</div>
              <h2>Changes to Terms</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We reserve the right to modify these Terms at any time. Changes will be posted on this page with an updated effective date. Your continued use of our services after any changes constitutes your acceptance of the new Terms. We encourage you to review these Terms periodically.</p>
        </div>

        <?php endif; ?>

        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-file-contract" style="margin-right:10px;opacity:.8;"></i> Questions About Our Terms?</h3>
            <p><?= e($siteName) ?> — Our legal team is available to clarify any aspect of these Terms.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="<?= SITE_URL ?>/contact.php" class="outline"><i class="fas fa-comments"></i> Live Chat</a>
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
