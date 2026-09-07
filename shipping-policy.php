<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Shipping & Delivery Policy – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Shipping & Delivery Policy — how we deliver digital projects, timelines, and what to expect from project handover.';
$canonicalUrl    = SITE_URL . '/shipping-policy.php';
$pageKey         = 'shipping';

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
  'lead'    => 'Shipping & Delivery',
  'accent'  => 'Policy',
  'sub'     => 'As a digital services company, all our deliverables are provided electronically. This policy explains how we deliver projects, our timelines, and the handover process.',
  'crumbs'  => [['Shipping Policy', null]],
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
        <a href="#section-overview"><i class="fas fa-circle"></i> Overview</a>
        <a href="#section-digital"><i class="fas fa-circle"></i> Digital Delivery</a>
        <a href="#section-timelines"><i class="fas fa-circle"></i> Project Timelines</a>
        <a href="#section-milestones"><i class="fas fa-circle"></i> Milestone Delivery</a>
        <a href="#section-handover"><i class="fas fa-circle"></i> Project Handover</a>
        <a href="#section-delays"><i class="fas fa-circle"></i> Delays & Force Majeure</a>
        <a href="#section-physical"><i class="fas fa-circle"></i> Physical Materials</a>
        <?php else: ?>
        <a href="#shipping-top"><i class="fas fa-circle"></i> Delivery Policy</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Questions about your project delivery? We're here to help.</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/refund-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-undo" style="color:var(--emerald);width:14px;"></i> Refund Policy</a>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
      </div>
    </aside>

    <main class="policy-main" id="shipping-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-truck"></i> Shipping & Delivery</span>
          <h1>Shipping &amp; <span>Delivery Policy</span></h1>
          <p>As a digital services company, all our deliverables are provided electronically. This policy explains how we deliver projects, our timelines, and the handover process.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fas fa-globe"></i> Digital Delivery Only</div>
          </div>
        </div>
      </div>

      <div class="policy-body">

        <?php if ($hasCustomContent): ?>
        <div class="policy-section">
          <div class="policy-content-custom"><?= $content['content'] ?></div>
        </div>
        <?php else: ?>

        <div class="policy-section" id="section-overview">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#46009f)"><i class="fas fa-info-circle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>Overview</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <div class="policy-highlight-box">
            <p><strong>Important:</strong> <?= e($siteName) ?> is a <strong>digital services company</strong>. We do not sell or ship physical products. All deliverables — including software, websites, apps, designs, reports, and digital content — are delivered electronically.</p>
          </div>
          <p>This policy applies to all service engagements and explains how we manage project delivery, communication, and handover to ensure a smooth experience for every client.</p>
        </div>

        <div class="policy-section" id="section-digital">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Digital Delivery Methods</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>All project deliverables are provided via one or more of the following digital channels:</p>
          <ul class="policy-list">
            <li><strong>Email:</strong> Design files, reports, documentation, and small deliverables sent directly to your registered email</li>
            <li><strong>Cloud Storage:</strong> Large files, source code, and assets shared via Google Drive, Dropbox, or similar platforms</li>
            <li><strong>Version Control:</strong> Source code delivered via GitHub, GitLab, or Bitbucket repositories</li>
            <li><strong>Live Deployment:</strong> Websites and applications deployed directly to your hosting server or our managed servers</li>
            <li><strong>Secure Transfer:</strong> Sensitive documents and credentials shared via encrypted channels</li>
            <li><strong>Client Dashboard / Portal:</strong> Progress updates, deliverables, and communications through our project management tools</li>
          </ul>
        </div>

        <div class="policy-section" id="section-timelines">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-clock"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Typical Project Timelines</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>Project delivery timelines vary based on scope and complexity. Below are indicative timeframes for our standard services:</p>
          <table class="cookie-table">
            <thead>
              <tr><th>Service Type</th><th>Typical Timeline</th><th>Factors Affecting Delivery</th></tr>
            </thead>
            <tbody>
              <tr><td><strong>Website Design & Development</strong></td><td>4–12 weeks</td><td>No. of pages, custom features, content readiness</td></tr>
              <tr><td><strong>Mobile Application</strong></td><td>8–20 weeks</td><td>Platform (iOS/Android/both), features, complexity</td></tr>
              <tr><td><strong>Custom Software</strong></td><td>12–24+ weeks</td><td>Scope, integrations, testing requirements</td></tr>
              <tr><td><strong>UI/UX Design</strong></td><td>2–6 weeks</td><td>No. of screens, revision cycles</td></tr>
              <tr><td><strong>Digital Marketing Setup</strong></td><td>1–3 weeks</td><td>Channels, ad creatives, accounts access</td></tr>
              <tr><td><strong>Logo / Brand Identity</strong></td><td>1–2 weeks</td><td>Revisions, feedback turnaround time</td></tr>
              <tr><td><strong>SEO Services</strong></td><td>Ongoing (3+ months)</td><td>Competition, website authority, content quality</td></tr>
            </tbody>
          </table>
          <div class="policy-highlight-box">
            <p>The specific delivery schedule for your project will be agreed upon in writing before commencement. All timelines assume <strong>prompt client response</strong> to requests for content, feedback, and approvals.</p>
          </div>
        </div>

        <div class="policy-section" id="section-milestones">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-tasks"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>Milestone-Based Delivery</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>For larger projects, we follow a milestone-based delivery approach:</p>
          <ul class="policy-list">
            <li><strong>Project Kickoff:</strong> Requirements gathering, wireframes, and project plan — delivered within the first 1–2 weeks</li>
            <li><strong>Design Phase:</strong> UI/UX mockups, style guide, and design approval</li>
            <li><strong>Development Milestones:</strong> Incremental feature delivery with review checkpoints</li>
            <li><strong>Testing & QA:</strong> Quality assurance, bug fixes, and client acceptance testing</li>
            <li><strong>Final Delivery:</strong> Full deployment, documentation, training, and handover</li>
          </ul>
          <p>Each milestone is reviewed and approved before the next phase begins, ensuring quality at every stage and giving you full visibility into project progress.</p>
        </div>

        <div class="policy-section" id="section-handover">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-handshake"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Project Handover Process</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>Upon project completion, we follow a structured handover process to ensure a smooth transition:</p>
          <ul class="policy-list">
            <li><strong>Source Code Transfer:</strong> Complete source code transferred to your chosen repository or provided as a compressed archive</li>
            <li><strong>Documentation:</strong> Technical documentation, user guides, and admin manuals delivered</li>
            <li><strong>Credentials & Access:</strong> All login credentials, API keys, and server access securely transferred</li>
            <li><strong>Training:</strong> Walkthrough session for CMS, admin panels, and key features</li>
            <li><strong>Post-Launch Support:</strong> 30-day free support period for bug fixes after project delivery (as per agreement)</li>
          </ul>
          <p>Final handover is initiated only after all outstanding payments have been received and the project has been formally accepted by the client.</p>
        </div>

        <div class="policy-section" id="section-delays">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Delays & Force Majeure</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Project timelines may be affected by the following:</p>
          <ul class="policy-list">
            <li><strong>Client-Side Delays:</strong> Late delivery of content, assets, feedback, or approvals will extend the timeline accordingly. We will notify you of any impact on the delivery date</li>
            <li><strong>Scope Changes:</strong> Changes to the agreed project scope ("scope creep") will require a revised timeline and may incur additional costs</li>
            <li><strong>Technical Dependencies:</strong> Delays caused by third-party services, APIs, or hosting providers outside our control</li>
            <li><strong>Force Majeure:</strong> Unforeseeable events including natural disasters, government actions, power outages, or major technical infrastructure failures</li>
          </ul>
          <p>We will notify you promptly of any anticipated delays and provide a revised delivery estimate. Our goal is always to deliver on time and to the highest quality standard.</p>
        </div>

        <div class="policy-section" id="section-physical">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-box-open"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Physical Materials (if applicable)</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>In certain circumstances, we may provide physical printed materials such as brochures, business cards, or other branded collateral. In such cases:</p>
          <ul class="policy-list">
            <li>Delivery within Delhi-NCR: 3–5 business days after print production completion</li>
            <li>Delivery within India: 5–10 business days via courier (actual shipper's terms apply)</li>
            <li>International delivery: By arrangement and at client's expense</li>
            <li>Tracking information will be shared once the shipment is dispatched</li>
          </ul>
          <p>Physical product delivery (if any) is handled by third-party courier services. <?= e($siteName) ?> is not responsible for delays caused by the courier once dispatched.</p>
        </div>

        <?php endif; ?>

        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-truck" style="margin-right:10px;opacity:.8;"></i> Delivery Enquiries</h3>
            <p><?= e($siteName) ?> — Get in touch to track your project progress or ask about delivery timelines.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="<?= SITE_URL ?>/contact.php" class="outline"><i class="fas fa-comments"></i> Get a Quote</a>
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
