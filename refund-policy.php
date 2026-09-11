<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'Refund & Cancellation Policy – Appsgain Technologies';
$pageDescription = 'Read Appsgain Technologies\' Refund & Cancellation Policy — our guidelines for project cancellations, refunds, and dispute resolution.';
$canonicalUrl    = SITE_URL . '/refund-policy.php';
$pageKey         = 'refund';

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
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/policy.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Legal',
  'lead'    => 'Refund & Cancellation',
  'accent'  => 'Policy',
  'sub'     => 'At ' . e($siteName) . ', we strive for complete client satisfaction. This policy outlines the terms for project cancellations, refunds, and how we handle disputes fairly.',
  'crumbs'  => [['Refund Policy', null]],
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
        <a href="#section-overview"><i class="fas fa-circle"></i> Policy Overview</a>
        <a href="#section-cancellation"><i class="fas fa-circle"></i> Project Cancellation</a>
        <a href="#section-refund-eligibility"><i class="fas fa-circle"></i> Refund Eligibility</a>
        <a href="#section-non-refundable"><i class="fas fa-circle"></i> Non-Refundable Items</a>
        <a href="#section-process"><i class="fas fa-circle"></i> Refund Process</a>
        <a href="#section-courses"><i class="fas fa-circle"></i> Training Courses</a>
        <a href="#section-disputes"><i class="fas fa-circle"></i> Dispute Resolution</a>
        <?php else: ?>
        <a href="#refund-top"><i class="fas fa-circle"></i> Refund Policy</a>
        <?php endif; ?>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Have a refund request or query? Contact us directly.</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
        <a href="<?= SITE_URL ?>/shipping-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-truck" style="color:var(--cyan);width:14px;"></i> Delivery Policy</a>
        <a href="<?= SITE_URL ?>/privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-shield-alt" style="color:var(--violet);width:14px;"></i> Privacy Policy</a>
      </div>
    </aside>

    <main class="policy-main" id="refund-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fas fa-undo-alt"></i> Refund & Cancellation</span>
          <h1>Refund &amp; <span>Cancellation Policy</span></h1>
          <p>At <?= e($siteName) ?>, we strive for complete client satisfaction. This policy outlines the terms for project cancellations, refunds, and how we handle disputes fairly.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fas fa-map-marker-alt"></i> Jurisdiction: India</div>
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
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-info-circle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>Policy Overview</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <div class="policy-highlight-box">
            <p><strong>Our Commitment:</strong> We are dedicated to delivering high-quality work and maintaining transparent, fair business practices. If you are unsatisfied with our services, we will work with you to find a resolution before considering any refund.</p>
          </div>
          <p>Since <?= e($siteName) ?> provides <strong>custom digital services</strong> (software development, design, digital marketing, training), all work is bespoke and involves substantial time and resources. Our refund policy reflects this nature while aiming to be fair and transparent to all clients.</p>
          <p>This policy applies to all services provided by <?= e($siteName) ?> including web development, mobile applications, digital marketing, UI/UX design, IT training courses, and other professional services.</p>
        </div>

        <div class="policy-section" id="section-cancellation">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-times-circle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Project Cancellation Terms</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>If you wish to cancel a project or service engagement, the following terms apply based on the stage of work:</p>

          <div style="display:grid;gap:14px;margin-bottom:20px;">
            <div style="background:rgba(5,150,105,.05);border:1px solid rgba(5,150,105,.2);border-left:4px solid #059669;border-radius:10px;padding:18px 22px;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div style="width:32px;height:32px;background:rgba(5,150,105,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-hourglass-start" style="color:#059669;font-size:13px;"></i></div><strong style="font-size:14px;color:var(--primary);">Before Work Commences (within 24 hours of agreement)</strong></div>
              <p style="font-size:14px;color:var(--text);margin:0;line-height:1.6;">Full refund of any advance payment, minus a 5% administrative processing fee. Cancellation must be submitted in writing.</p>
            </div>
            <div style="background:rgba(85,0,204,.05);border:1px solid rgba(85,0,204,.2);border-left:4px solid #5500cc;border-radius:10px;padding:18px 22px;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div style="width:32px;height:32px;background:rgba(85,0,204,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-hourglass-half" style="color:#5500cc;font-size:13px;"></i></div><strong style="font-size:14px;color:var(--primary);">After Work Has Commenced (within 30% project completion)</strong></div>
              <p style="font-size:14px;color:var(--text);margin:0;line-height:1.6;">50% refund of the advance payment. Work completed to date will be billed at the pro-rated project rate.</p>
            </div>
            <div style="background:rgba(234,88,12,.05);border:1px solid rgba(234,88,12,.2);border-left:4px solid #f06a00;border-radius:10px;padding:18px 22px;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div style="width:32px;height:32px;background:rgba(234,88,12,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-hourglass-three-quarters" style="color:#f06a00;font-size:13px;"></i></div><strong style="font-size:14px;color:var(--primary);">30%–50% Project Completion</strong></div>
              <p style="font-size:14px;color:var(--text);margin:0;line-height:1.6;">No refund on advance payment. Any additional amounts paid for completed milestones are non-refundable. All completed work will be handed over.</p>
            </div>
            <div style="background:rgba(106,0,255,.05);border:1px solid rgba(106,0,255,.2);border-left:4px solid #6a00ff;border-radius:10px;padding:18px 22px;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div style="width:32px;height:32px;background:rgba(106,0,255,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-hourglass-end" style="color:#6a00ff;font-size:13px;"></i></div><strong style="font-size:14px;color:var(--primary);">More Than 50% Complete</strong></div>
              <p style="font-size:14px;color:var(--text);margin:0;line-height:1.6;">No refund applicable. All outstanding payments up to the cancellation date remain due. Completed deliverables will be provided upon receipt of all outstanding payments.</p>
            </div>
          </div>
        </div>

        <div class="policy-section" id="section-refund-eligibility">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-check-circle"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Refund Eligibility</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>Refunds may be considered in the following circumstances:</p>
          <ul class="policy-list">
            <li>We are unable to commence work within the agreed timeline due to circumstances on our side</li>
            <li>Deliverables do not meet the agreed specifications outlined in the project proposal, and we are unable to correct them within a reasonable revision period</li>
            <li>We fail to deliver the project within the extended agreed timeframe through no fault of the client</li>
            <li>Duplicate payments made in error will be refunded in full</li>
            <li>Overpayments or billing errors will be corrected and refunded promptly</li>
          </ul>
        </div>

        <div class="policy-section" id="section-non-refundable">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6b7280,#4b5563)"><i class="fas fa-ban"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>Non-Refundable Items</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6b7280,transparent)"></div>
          <p>The following are non-refundable under all circumstances:</p>
          <ul class="policy-list">
            <li>Third-party costs and licences already procured on your behalf (domain registration, hosting, stock images, premium plugins, APIs)</li>
            <li>Work completed and approved by the client at any stage</li>
            <li>Consultation fees and discovery session charges</li>
            <li>Expenses incurred for travel, meetings, or site visits</li>
            <li>Cancellations made after 50% or more of the project is complete</li>
            <li>Services where work has already been published or deployed to production</li>
          </ul>
        </div>

        <div class="policy-section" id="section-process">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-clipboard-list"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>How to Request a Refund</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>To initiate a refund or cancellation request, please follow these steps:</p>
          <ul class="policy-list">
            <li><strong>Step 1:</strong> Contact us at <a href="mailto:<?= e($email) ?>" style="color:var(--violet);"><?= e($email) ?></a> or call <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $phone)) ?>" style="color:var(--violet);"><?= e($phone) ?></a> within the applicable refund window</li>
            <li><strong>Step 2:</strong> Provide your project name, agreement/invoice number, the reason for your request, and supporting documentation</li>
            <li><strong>Step 3:</strong> Our team will acknowledge your request within 2 business days</li>
            <li><strong>Step 4:</strong> We will review the request and respond with our decision within 7 business days</li>
            <li><strong>Step 5:</strong> If approved, refunds will be processed to the original payment method within 7–14 business days</li>
          </ul>
          <div class="policy-highlight-box">
            <p><strong>Refund Timeline:</strong> Approved refunds will be processed within <strong>7–14 business days</strong>. Bank processing times may vary and are outside our control.</p>
          </div>
        </div>

        <div class="policy-section" id="section-courses">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-graduation-cap"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Training Course Cancellations</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>For IT training courses and programmes offered by <?= e($siteName) ?>:</p>
          <ul class="policy-list">
            <li><strong>7+ days before course start:</strong> Full refund minus a ₹500 administrative fee, or free transfer to a future batch</li>
            <li><strong>3–6 days before course start:</strong> 75% refund or transfer to a future batch of the same course</li>
            <li><strong>Less than 72 hours before start:</strong> No refund, but you may transfer your enrolment to a future batch (subject to availability)</li>
            <li><strong>After course commencement:</strong> No refund. If you miss more than 20% of sessions, you may audit the course at no additional cost</li>
          </ul>
          <p>If <?= e($siteName) ?> cancels a course, all enrolled students will receive a full refund or the option to enrol in a future batch.</p>
        </div>

        <div class="policy-section" id="section-disputes">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-balance-scale"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Dispute Resolution</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We are committed to resolving any disputes amicably and professionally. Our resolution process:</p>
          <ul class="policy-list">
            <li><strong>Informal Resolution:</strong> Contact our team directly. Most issues are resolved at this stage within 48–72 hours</li>
            <li><strong>Escalation:</strong> If unresolved, the dispute will be escalated to senior management for review within 5 business days</li>
            <li><strong>Mediation:</strong> We are open to third-party mediation if informal resolution fails</li>
            <li><strong>Legal Recourse:</strong> As a last resort, disputes will be governed by Indian law and subject to the jurisdiction of courts in New Delhi</li>
          </ul>
          <p>We strongly prefer resolution through open communication. Please reach out to us first — we are here to find a fair solution for both parties.</p>
        </div>

        <?php endif; ?>

        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fas fa-undo-alt" style="margin-right:10px;opacity:.8;"></i> Refund Enquiries</h3>
            <p><?= e($siteName) ?> — Contact our support team for refund requests, cancellations, or any payment-related queries.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:<?= e($email) ?>" class="primary"><i class="fas fa-envelope"></i> <?= e($email) ?></a>
              <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $phone)) ?>" class="outline"><i class="fas fa-phone"></i> <?= e($phone) ?></a>
            </div>
            <p style="font-size:13px;opacity:.6;margin:0;">Response time: Within 2 business days</p>
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
