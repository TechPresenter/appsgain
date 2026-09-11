<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
trackVisitor();

$activePage      = '';
$pageTitle       = 'App Privacy Policy – Appsgain Technologies';
$pageDescription = 'Privacy Policy for VidPlayer, Mushroom Farming, Scout Guide Hindi, and Fish Farming Hindi mobile applications developed and published by Appsgain Technologies.';
$canonicalUrl    = SITE_URL . '/app-privacy-policy.php';

$siteName    = getSetting('site_name', 'Appsgain Technologies');
$lastUpdated = date('d F Y');
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
  <style>
    .app-card {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      background: var(--light);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 20px 22px;
      margin-bottom: 14px;
      transition: box-shadow .2s, transform .2s;
    }
    .app-card:hover { box-shadow: 0 6px 24px rgba(106,0,255,.10); transform: translateY(-2px); }
    .app-card-icon {
      width: 52px; height: 52px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 22px; color: #fff;
    }
    .app-card-body h4 { font-size: 15px; font-weight: 700; margin: 0 0 4px; color: var(--heading); }
    .app-card-body p  { font-size: 13px; color: var(--gray); margin: 0; }
    .app-card-pkg {
      display: inline-block; margin-top: 6px; font-size: 11.5px;
      font-family: monospace; background: var(--violet-bg,rgba(106,0,255,.08));
      color: var(--violet); padding: 2px 8px; border-radius: 5px; font-weight: 600;
    }
    .third-party-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px; margin-top: 16px;
    }
    .tp-card {
      display: flex; align-items: center; gap: 12px;
      background: var(--light); border: 1px solid var(--border);
      border-radius: 10px; padding: 14px 16px;
      font-size: 13.5px; font-weight: 600; color: var(--heading);
    }
    .tp-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink:0; }
    .dev-info-box {
      background: linear-gradient(135deg,#0b1026,#1e1b4b);
      border-radius: 14px; padding: 28px 28px; color: #fff; margin-top: 12px;
    }
    .dev-info-box h4 { font-size: 17px; margin: 0 0 16px; opacity: .9; }
    .dev-info-row {
      display: flex; flex-wrap: wrap; gap: 14px 28px;
    }
    .dev-info-item { display: flex; align-items: center; gap: 9px; font-size: 13.5px; opacity: .85; }
    .dev-info-item i { width: 16px; opacity: .7; }
    .dev-info-item a { color: #e0d0ff; text-decoration: none; }
    .dev-info-item a:hover { text-decoration: underline; }
    @media(max-width:600px){ .third-party-grid{grid-template-columns:1fr 1fr;} }
  </style>
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<!-- PAGE BANNER -->
<?php pageHero([
  'image'   => 'secimg_hero_legal',
  'eyebrow' => 'Legal',
  'lead'    => 'App Privacy',
  'accent'  => 'Policy',
  'sub'     => 'This Privacy Policy describes how information is collected, used, and protected when you use our mobile applications developed and published by Appsgain Technologies.',
  'crumbs'  => [['App Privacy Policy', null]],
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
        <a href="#section-apps"><i class="fas fa-circle"></i> Applications Covered</a>
        <a href="#section-developer"><i class="fas fa-circle"></i> Developer Information</a>
        <a href="#section-info-collect"><i class="fas fa-circle"></i> Information We Collect</a>
        <a href="#section-how-use"><i class="fas fa-circle"></i> How We Use Information</a>
        <a href="#section-third-party"><i class="fas fa-circle"></i> Third-Party Services</a>
        <a href="#section-security"><i class="fas fa-circle"></i> Data Security</a>
        <a href="#section-children"><i class="fas fa-circle"></i> Children's Privacy</a>
        <a href="#section-changes"><i class="fas fa-circle"></i> Policy Changes</a>
        <a href="#section-contact"><i class="fas fa-circle"></i> Contact Us</a>
      </nav>
      <div class="policy-sidebar-cta">
        <p>Questions about your data? We're happy to help.</p>
        <a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
      <!-- Related Policies -->
      <div style="padding:0 16px 20px;">
        <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);margin-bottom:10px;">Related Policies</div>
        <a href="<?= SITE_URL ?>/privacy-policy.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-shield-alt" style="color:var(--violet);width:14px;"></i> Website Privacy</a>
        <a href="<?= SITE_URL ?>/terms-of-service.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-file-contract" style="color:var(--blue);width:14px;"></i> Terms of Service</a>
        <a href="<?= SITE_URL ?>/disclaimer.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fas fa-exclamation-circle" style="color:var(--amber);width:14px;"></i> Disclaimer</a>
        <a href="<?= SITE_URL ?>/apps.php" style="display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--text);transition:var(--transition);" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background=''"><i class="fab fa-google-play" style="color:#01875f;width:14px;"></i> Our Apps</a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="policy-main" id="policy-top">
      <div class="policy-hero">
        <div class="policy-hero-grid"></div>
        <div class="policy-hero-inner">
          <span class="policy-type-badge"><i class="fab fa-google-play"></i> Mobile App Privacy</span>
          <h1>App Privacy <span>Policy</span></h1>
          <p>This Privacy Policy describes how information is collected, used, and protected when you use our mobile applications developed and published by Appsgain Technologies.</p>
          <div class="policy-meta-row">
            <div class="policy-meta-item"><i class="fas fa-calendar-alt"></i> Last Updated: <?= $lastUpdated ?></div>
            <div class="policy-meta-item"><i class="fab fa-google-play"></i> Platform: Android (Google Play)</div>
            <div class="policy-meta-item"><i class="fas fa-language"></i> Version: 1.0</div>
          </div>
        </div>
      </div>

      <div class="policy-body">

        <!-- Section 1: Applications Covered -->
        <div class="policy-section" id="section-apps">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#01875f,#00c4a0)"><i class="fab fa-google-play"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 01</div>
              <h2>Applications Covered by This Policy</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#01875f,transparent)"></div>

          <div class="policy-highlight-box">
            <p><strong>Scope:</strong> This Privacy Policy applies specifically to the following Android applications available on Google Play Store.</p>
          </div>

          <!-- App 1 -->
          <div class="app-card">
            <div class="app-card-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)">
              <i class="fas fa-play-circle"></i>
            </div>
            <div class="app-card-body">
              <h4>VidPlayer – All Video Player</h4>
              <p>A comprehensive video player supporting all popular video formats and codecs for Android devices.</p>
              <span class="app-card-pkg">com.appsgaintechnologies.vidplay</span>
            </div>
          </div>

          <!-- App 2 -->
          <div class="app-card">
            <div class="app-card-icon" style="background:linear-gradient(135deg,#059669,#10b981)">
              <i class="fas fa-seedling"></i>
            </div>
            <div class="app-card-body">
              <h4>Mushroom Farming – मशरूम खेती</h4>
              <p>A complete guide app for mushroom cultivation techniques, farming tips and agricultural best practices.</p>
              <span class="app-card-pkg">com.appsgaintechnologies.mushroomfarming</span>
            </div>
          </div>

          <!-- App 3 -->
          <div class="app-card">
            <div class="app-card-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)">
              <i class="fas fa-campground"></i>
            </div>
            <div class="app-card-body">
              <h4>Scout Guide Hindi – स्काउट गाइड</h4>
              <p>An educational app providing comprehensive scouting guidelines, activities and training resources in Hindi.</p>
              <span class="app-card-pkg">com.appsgaintechnologies.scoutguidehindiapp</span>
              <span class="app-card-pkg" style="background:rgba(106,0,255,.10);color:#5500cc;margin-left:6px;">Educational</span>
            </div>
          </div>

          <!-- App 4 -->
          <div class="app-card">
            <div class="app-card-icon" style="background:linear-gradient(135deg,#46009f,#6a00ff)">
              <i class="fas fa-fish"></i>
            </div>
            <div class="app-card-body">
              <h4>Fish Farming Hindi – मछली पालन</h4>
              <p>An educational app covering fish farming techniques, aquaculture methods and fishery management in Hindi.</p>
              <span class="app-card-pkg">com.appsgaintechnologies.fishfarmingapp</span>
              <span class="app-card-pkg" style="background:rgba(70,0,159,.10);color:#46009f;margin-left:6px;">Educational</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Developer Information -->
        <div class="policy-section" id="section-developer">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-user-tie"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 02</div>
              <h2>Developer Information</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>These applications are developed and maintained by:</p>
          <div class="dev-info-box">
            <h4><i class="fas fa-building" style="margin-right:10px;opacity:.7;"></i> Appsgain Technologies</h4>
            <div class="dev-info-row">
              <div class="dev-info-item"><i class="fas fa-user"></i> Prashant Singh Kushwaha</div>
              <div class="dev-info-item"><i class="fas fa-id-badge"></i> Founder &amp; CEO</div>
              <div class="dev-info-item"><i class="fas fa-globe"></i> <a href="https://appsgain.in" target="_blank" rel="noopener">https://appsgain.in</a></div>
              <div class="dev-info-item"><i class="fas fa-envelope"></i> <a href="mailto:info@appsgain.in">info@appsgain.in</a></div>
              <div class="dev-info-item"><i class="fas fa-phone"></i> <a href="tel:+919955446477">+91 9955446477</a></div>
            </div>
          </div>
        </div>

        <!-- Section 3: Information We Collect -->
        <div class="policy-section" id="section-info-collect">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-database"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 03</div>
              <h2>Information We Collect</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>

          <div class="policy-highlight-box">
            <p><strong>We do not sell users' personal information to third parties.</strong> Any data collected is used solely to improve app performance and user experience.</p>
          </div>

          <p>Depending on the app features, we may automatically collect the following types of information:</p>
          <ul class="policy-list">
            <li><strong>Device information</strong> — Device model, OS version, unique device identifiers, and hardware specifications needed for app compatibility and optimisation</li>
            <li><strong>App usage analytics</strong> — Feature usage patterns, session duration, screens viewed, and interaction data to help us understand how users engage with the app</li>
            <li><strong>Crash reports and diagnostics</strong> — Technical logs, error reports, and performance metrics to help us identify and resolve issues quickly</li>
            <li><strong>User preferences and settings</strong> — In-app settings and configurations saved locally on the device to remember your preferences</li>
          </ul>

          <p>We do <strong>not</strong> collect:</p>
          <ul class="policy-list">
            <li>Your name, email address, or any personally identifiable contact information unless you voluntarily provide it to contact support</li>
            <li>Location data, contacts, call logs, or SMS content</li>
            <li>Sensitive personal information such as financial, health, or biometric data</li>
          </ul>
        </div>

        <!-- Section 4: How We Use -->
        <div class="policy-section" id="section-how-use">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#46009f)"><i class="fas fa-cogs"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 04</div>
              <h2>How We Use Information</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>The information collected through our applications may be used to:</p>
          <ul class="policy-list">
            <li>Improve app performance, stability, and overall user experience</li>
            <li>Fix bugs, resolve technical issues, and optimise app functionality</li>
            <li>Develop new features and enhancements based on user behaviour patterns</li>
            <li>Provide app updates and technical support</li>
            <li>Ensure the security of the application and prevent misuse or abuse</li>
            <li>Comply with applicable laws, regulations, and Google Play Store policies</li>
          </ul>
        </div>

        <!-- Section 5: Third-Party Services -->
        <div class="policy-section" id="section-third-party">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#be123c)"><i class="fas fa-share-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 05</div>
              <h2>Third-Party Services</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Our applications may integrate third-party services that collect information independently in accordance with their own privacy policies. These include:</p>

          <div class="third-party-grid">
            <div class="tp-card">
              <div class="tp-icon" style="background:rgba(66,133,244,.12)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
              </div>
              Google Play Services
            </div>
            <div class="tp-card">
              <div class="tp-icon" style="background:rgba(0,152,116,.12)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#00a86b"><path d="M12 1L3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4z"/></svg>
              </div>
              Google AdMob
            </div>
            <div class="tp-card">
              <div class="tp-icon" style="background:rgba(255,202,40,.15)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFCA28"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
              </div>
              Firebase Analytics
            </div>
            <div class="tp-card">
              <div class="tp-icon" style="background:rgba(255,90,50,.12)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#FF5722"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
              </div>
              Firebase Crashlytics
            </div>
          </div>

          <p style="margin-top:18px;">These third-party services have their own privacy policies governing how they handle user data. We encourage you to review their policies:</p>
          <ul class="policy-list">
            <li><a href="https://policies.google.com/privacy" target="_blank" rel="noopener" style="color:var(--violet);font-weight:600;">Google Privacy Policy</a> — covers Google Play Services, AdMob, Firebase Analytics &amp; Crashlytics</li>
          </ul>
        </div>

        <!-- Section 6: Data Security -->
        <div class="policy-section" id="section-security">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#059669,#047857)"><i class="fas fa-lock"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 06</div>
              <h2>Data Security</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#059669,transparent)"></div>
          <p>We implement reasonable and industry-standard security measures to protect user information from unauthorised access, disclosure, alteration, or misuse. These include:</p>
          <ul class="policy-list">
            <li>Secure data transmission using HTTPS/TLS encryption where applicable</li>
            <li>Restricted access to collected data on a need-to-know basis within our team</li>
            <li>Regular review of data collection practices to minimise the data we store</li>
            <li>Reliance on Google's secure Firebase infrastructure for analytics and crash data</li>
          </ul>
          <div class="policy-highlight-box">
            <p>While we take every precaution to safeguard your information, no method of electronic storage or transmission is 100% secure. If you believe there has been a data incident, please <a href="<?= SITE_URL ?>/contact.php" style="color:var(--violet);font-weight:600;">contact us immediately</a>.</p>
          </div>
        </div>

        <!-- Section 7: Children's Privacy -->
        <div class="policy-section" id="section-children">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#46009f)"><i class="fas fa-child"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 07</div>
              <h2>Children's Privacy</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>Our applications are not directed at children under the age of 13 (or the minimum age required by applicable laws in your jurisdiction). We do not knowingly collect personal information from children under such ages.</p>
          <p>If you are a parent or guardian and believe that your child has provided us with personal information, please contact us at <a href="mailto:info@appsgain.in" style="color:var(--violet);font-weight:600;">info@appsgain.in</a> and we will take prompt steps to delete such information from our records.</p>
        </div>

        <!-- Section 8: Policy Changes -->
        <div class="policy-section" id="section-changes">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#6a00ff,#5500cc)"><i class="fas fa-sync-alt"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 08</div>
              <h2>Changes to This Privacy Policy</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#6a00ff,transparent)"></div>
          <p>We may update this Privacy Policy from time to time to reflect changes in our apps, practices, or applicable legal requirements. When we make changes, we will:</p>
          <ul class="policy-list">
            <li>Post the updated policy on this page with a revised "Last Updated" date</li>
            <li>Update the policy link in the Google Play Store listing for each application</li>
            <li>Notify users via an in-app notice for significant changes, where technically feasible</li>
          </ul>
          <p>We encourage you to review this page periodically to stay informed about how we protect your information.</p>
        </div>

        <!-- Section 9: Contact -->
        <div class="policy-section" id="section-contact">
          <div class="policy-section-header">
            <div class="policy-section-icon" style="background:linear-gradient(135deg,#5500cc,#6a00ff)"><i class="fas fa-envelope"></i></div>
            <div class="policy-section-title">
              <div class="policy-section-num">Section 09</div>
              <h2>Contact Us</h2>
            </div>
          </div>
          <div class="policy-section-divider" style="background:linear-gradient(90deg,#5500cc,transparent)"></div>
          <p>If you have any questions, concerns, or requests regarding this Privacy Policy or how your data is handled in our applications, please reach out to us:</p>
        </div>

        <!-- Contact Box -->
        <div class="policy-contact-box">
          <div class="policy-contact-box-inner">
            <h3><i class="fab fa-google-play" style="margin-right:10px;opacity:.8;"></i> App Privacy Enquiries</h3>
            <p>Prashant Singh Kushwaha — Founder &amp; CEO, Appsgain Technologies<br>We will respond to all privacy-related queries within 30 days.</p>
            <div class="policy-contact-links" style="justify-content:center;margin-bottom:20px;">
              <a href="mailto:info@appsgain.in" class="primary"><i class="fas fa-envelope"></i> info@appsgain.in</a>
              <a href="https://appsgain.in" target="_blank" rel="noopener" class="outline"><i class="fas fa-globe"></i> appsgain.in</a>
            </div>
            <p style="font-size:13px;opacity:.6;margin:8px 0 0;">
              By using <strong>VidPlayer – All Video Player</strong>, <strong>Mushroom Farming – मशरूम खेती</strong>, <strong>Scout Guide Hindi – स्काउट गाइड</strong>, and <strong>Fish Farming Hindi</strong>, you agree to the terms of this Privacy Policy.
            </p>
          </div>
        </div>

      </div><!-- /policy-body -->
    </main><!-- /policy-main -->

  </div><!-- /policy-layout -->
</div><!-- /policy-page-wrap -->

<script>
(function() {
  var tocLinks = document.querySelectorAll('#policyToc a[href^="#"]');
  tocLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var target = document.querySelector(link.getAttribute('href'));
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
  var sections = Array.from(document.querySelectorAll('.policy-section[id]'));
  window.addEventListener('scroll', function() {
    var current = '';
    sections.forEach(function(sec) {
      if (window.scrollY >= sec.offsetTop - 160) current = sec.id;
    });
    tocLinks.forEach(function(link) {
      link.classList.toggle('active', link.getAttribute('href') === '#' + current);
    });
  }, { passive: true });
})();
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
