<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Privacy Policy – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Privacy Policy — how we collect, use, disclose and protect your personal data.';
$canonicalUrl    = SITE_URL . '/privacy-policy.php';
$pageKey         = 'privacy';

$content  = dbFetchOne("SELECT * FROM pages WHERE page_key = ? AND is_active = 1", [$pageKey]);
$siteName = getSetting('site_name', 'Appsgain Technologies');
$email    = getSetting('site_email', getSetting('email', 'info@appsgain.in'));
$address  = getSetting('site_address', getSetting('address', 'New Delhi, India'));
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
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/policy.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- PAGE BANNER -->
<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Legal',
  'lead'    => 'Privacy',
  'accent'  => 'Policy',
  'sub'     => 'We are committed to protecting your personal information and your right to privacy. This policy explains how we collect, use and safeguard your data.',
  'crumbs'  => [['Privacy Policy', null]],
]); ?>

<!-- POLICY CONTENT -->
<div class="policy-page-wrap">
  <div class="policy-layout">

    <!-- Sidebar TOC -->
    <aside class="policy-sidebar">
      <div class="policy-sidebar-header">
        <h4><i class="fas fa-list-ul" style="margin-right:8px;opacity:.8;"></i> Table of Contents</h4>
        <p>Jump to any section</p>
      </div>
      <nav class="policy-toc" id="policyToc">
        <?php if (!$hasCustomContent): ?>
        <a href="#section-info-collect"><i class="fas fa-circle"></i> Information We Collect</a>
        <a href="#section-how-use"><i class="fas fa-circle"></i> How We Use Your Info</a>
        <a href="#section-cookies"><i class="fas fa-circle"></i> Cookies</a>
        <a href="#section-third-party"><i class="fas fa-circle"></i> Third-Party Sharing</a>
        <a href="#section-security"><i class="fas fa-circle"></i> Data Security</a>
        <a href="#section-retention"><i class="fas fa-circle"></i> Data Retention</a>
        <a href="#section-your-rights"><i class="fas fa-circle"></i> Your Rights</a>
        <a href="#section-contact"><i class="fas fa-circle"></i> Contact Us</a>
        <?php else: ?>
        <a href="#policy-top"><i class="fas fa-circle"></i> Privacy Policy</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Have questions about your data? We're happy to help.</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <!-- Related Policies -->
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/cookie-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-cookie-bite" style="color:var(--violet);width:14px;"></i> Cookie Policy</a>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
        <a href="<?= SITE_URL ?>/disclaimer.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-exclamation-circle" style="color:var(--amber);width:14px;"></i> Disclaimer</a>
        <a href="<?= SITE_URL ?>/app-privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fab fa-google-play" style="color:#01875f;width:14px;"></i> App Privacy Policy</a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="policy-main" id="policy-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-shield-alt"></i> Privacy & Data Protection</span>
          <h1>Privacy <span>Policy</span></h1>
          <p>We are committed to protecting your personal information and your right to privacy. This policy explains how we collect, use and safeguard your data.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fas fa-globe"></i> Applies to: <?= e(SITE_URL) ?></div>
            <div class="policy-meta-item"><i class="fas fa-language"></i> Version: 1.0</div>
          </div>
        </div>
      </div>

      <div class="policy-body">

        <?php if ($hasCustomContent): ?>
        <div class="policy-section" id="policy-top">
          <div class="policy-content-custom"><?= $content['content'] ?></div>
        </div>
        <?php else: ?>

        <!-- Section 1: Info We Collect -->
        <div class="policy-section" id="section-info-collect">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-database"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>Information We Collect</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>

          <div class="policy-highlight-box">
            <p><strong>Summary:</strong> We collect information you give us directly, and some data automatically when you browse our website. We never collect more than we need.</p>
          </div>

          <h3>Personal Information You Provide</h3>
          <p>We may collect personal information that you voluntarily provide when you interact with our services:</p>
          <ul class="policy-list">
            <li>Name, email address, phone number and company details when you fill out contact or enquiry forms</li>
            <li>Project requirements and budget information when requesting a quote</li>
            <li>Course enrolment details when applying for training programmes</li>
            <li>Email address when subscribing to our newsletter</li>
          </ul>

          <h3>Automatically Collected Information</h3>
          <p>When you visit our website, we automatically collect certain technical information including:</p>
          <ul class="policy-list">
            <li>IP address, browser type and version, operating system</li>
            <li>Pages visited, time spent on pages, referring URLs</li>
            <li>Date and time of visits, clickstream data</li>
            <li>Device type (desktop, mobile, tablet)</li>
          </ul>
        </div>

        <!-- Section 2: How We Use -->
        <div class="policy-section" id="section-how-use">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-cogs"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>How We Use Your Information</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>We use the information we collect for the following purposes:</p>
          <ul class="policy-list">
            <li>To respond to your enquiries and provide the services you request</li>
            <li>To send marketing communications where you have given consent</li>
            <li>To improve our website functionality, content and user experience</li>
            <li>To analyse traffic patterns and optimise our services</li>
            <li>To prevent fraudulent activity and ensure website security</li>
            <li>To comply with applicable legal obligations</li>
            <li>To send important service notifications and updates</li>
          </ul>
          <div class="policy-highlight-box">
            <p><strong>We will never sell your personal data</strong> to third parties or use it for purposes not described in this policy without your explicit consent.</p>
          </div>
        </div>

        <!-- Section 3: Cookies -->
        <div class="policy-section" id="section-cookies">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-cookie-bite"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Cookies</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>We use cookies and similar tracking technologies to enhance your browsing experience on our website. Cookies are small text files stored on your device that help us remember your preferences and understand how you use our site.</p>
          <p>You can control cookie settings through your browser. Note that disabling cookies may affect some website functionality. For complete details on the cookies we use and how to manage them, please read our <a href="<?= SITE_URL ?>/cookie-policy.php" style="color:var(--violet);font-weight:600;">Cookie Policy</a>.</p>
        </div>

        <!-- Section 4: Third Party -->
        <div class="policy-section" id="section-third-party">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-share-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>Third-Party Sharing</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>We do not sell, trade, or rent your personal information to third parties. We may share data only with trusted service partners who help us operate our website and business:</p>
          <ul class="policy-list">
            <li>Web hosting and cloud infrastructure providers</li>
            <li>Analytics platforms (e.g., Google Analytics) under data processing agreements</li>
            <li>Email delivery services for transactional and marketing emails</li>
            <li>Payment processors when applicable to service transactions</li>
          </ul>
          <p>All third-party service providers are contractually required to keep your data confidential and to use it only for the specific purposes we authorise.</p>
        </div>

        <!-- Section 5: Security -->
        <div class="policy-section" id="section-security">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-lock"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Data Security</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We implement industry-standard technical and organisational security measures to protect your personal data:</p>
          <ul class="policy-list">
            <li>SSL/TLS encryption for all data transmitted to and from our servers</li>
            <li>Secure, hashed storage of passwords (bcrypt algorithm)</li>
            <li>Regular security audits and vulnerability assessments</li>
            <li>Restricted access to personal data on a need-to-know basis</li>
            <li>Secure server infrastructure with firewall protection</li>
          </ul>
          <div class="policy-highlight-box">
            <p>While we take every reasonable precaution, no internet transmission is 100% secure. If you believe your data has been compromised, please <a href="<?= SITE_URL ?>/contact.php" style="color:var(--violet);font-weight:600;">contact us immediately</a>.</p>
          </div>
        </div>

        <!-- Section 6: Retention -->
        <div class="policy-section" id="section-retention">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#46009f)"><i class="fas fa-clock"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Data Retention</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We retain your personal data only for as long as necessary to fulfil the purposes described in this policy, or as required by applicable laws and regulations. Specifically:</p>
          <ul class="policy-list">
            <li><strong>Contact form submissions:</strong> Retained for up to 3 years for follow-up and service delivery</li>
            <li><strong>Newsletter subscribers:</strong> Until you unsubscribe</li>
            <li><strong>Website analytics data:</strong> Up to 26 months (standard Google Analytics retention)</li>
            <li><strong>Project-related communications:</strong> For the duration of the project plus 5 years</li>
          </ul>
        </div>

        <!-- Section 7: Your Rights -->
        <div class="policy-section" id="section-your-rights">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-user-shield"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Your Rights</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>You have the following rights regarding your personal data:</p>
          <div class="rights-grid">
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(106,0,255,.1)"><i class="fas fa-eye" style="color:#6a00ff;font-size:14px;"></i></div>
              <div>
                <h4>Right to Access</h4>
                <p>Request a copy of the personal data we hold about you.</p>
              </div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(85,0,204,.1)"><i class="fas fa-edit" style="color:#5500cc;font-size:14px;"></i></div>
              <div>
                <h4>Right to Rectification</h4>
                <p>Request correction of inaccurate or incomplete data.</p>
              </div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(106,0,255,.1)"><i class="fas fa-trash-alt" style="color:#6a00ff;font-size:14px;"></i></div>
              <div>
                <h4>Right to Erasure</h4>
                <p>Request deletion of your personal data where no legal obligation to retain exists.</p>
              </div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(5,150,105,.1)"><i class="fas fa-hand-paper" style="color:#059669;font-size:14px;"></i></div>
              <div>
                <h4>Right to Object</h4>
                <p>Object to processing of your data for marketing or legitimate interest purposes.</p>
              </div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(85,0,204,.1)"><i class="fas fa-download" style="color:#5500cc;font-size:14px;"></i></div>
              <div>
                <h4>Data Portability</h4>
                <p>Receive your data in a structured, machine-readable format.</p>
              </div>
            </div>
            <div class="right-card">
              <div class="right-card-icon" style="background:rgba(106,0,255,.1)"><i class="fas fa-ban" style="color:#6a00ff;font-size:14px;"></i></div>
              <div>
                <h4>Withdraw Consent</h4>
                <p>Withdraw consent for marketing communications at any time.</p>
              </div>
            </div>
          </div>
          <p>To exercise any of these rights, please contact us using the details below. We will respond to your request within 30 days.</p>
        </div>

        <!-- Section 8: Contact -->
        <div class="policy-section" id="section-contact">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#10b981)"><i class="fas fa-envelope"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 08</div>
              <h2>Contact Us</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>If you have any questions, concerns or requests regarding this Privacy Policy or your personal data, please contact our Data Protection team:</p>
        </div>

        <?php endif; ?>

        <!-- Contact Box -->
        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-shield-alt" style="margin-right:10px;opacity:.8;"></i> Privacy Enquiries</h3>
            <p><?= e($siteName) ?> — We take your privacy seriously and will respond to all requests within 30 days.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $phone)) ?>" class="outline"><i class="fas fa-phone"></i> <?= e($phone) ?></a>
            </div>
            <p style="font-size:13px;opacity:.6;margin:0;"><?= e($address) ?></p>
          </div>
        </div>

      </div><!-- /policy-body -->
    </main><!-- /policy-main -->

  </div><!-- /policy-layout -->
</div><!-- /policy-page-wrap -->

<script>
/* Smooth scroll & active TOC highlighting */
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
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - 160) current = sec.id;
    });
    tocLinks.forEach(link => {
      link.classList.toggle('active', link.getAttribute('href') === '#' + current);
    });
  }, { passive: true });
})();
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
