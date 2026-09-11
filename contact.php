<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/page-hero.php';
require_once __DIR__ . '/includes/social-links.php';
trackVisitor();

$activePage      = 'contact';
$schemaPageType  = 'contact';
$pageTitle       = getSetting('meta_title_contact', 'Contact Appsgain Technologies | Get a Free Software Development Quote');
$pageDescription = getSetting('meta_desc_contact',  'Get in touch with Appsgain Technologies. We respond within 24 hours with a tailored proposal. Call, email or send us your project brief.');
$canonicalUrl    = SITE_URL . '/contact.php';

$s        = getAllSettings();
$phone    = $s['phone']   ?? '+91-9955446477';
$email    = $s['email']   ?? 'info@appsgain.in';
$address  = $s['address'] ?? 'Karkardooma, Anand Vihar, New Delhi – 110092, India';
/* Same office list the footer uses, so the two can never disagree. */
$offices = [];
foreach ([['site_address', 'site_address_label', 'Head Office'],
          ['site_address_2', 'site_address_2_label', 'Branch Office']] as [$ak, $lk, $ld]) {
    $a = trim((string)($s[$ak] ?? ($ak === 'site_address' ? ($s['address'] ?? '') : '')));
    if ($a === '') continue;
    $offices[] = [trim((string)($s[$lk] ?? '')) ?: $ld, $a];
}
$mapEmbed = $s['google_map_embed'] ?? '';
$socials  = agSocialLinks($s);

$telHref  = preg_replace('/[^+0-9]/', '', $phone);
?>
<?php $pageStyles = ['page-hero.css', 'contact.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/animations.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/captcha-cta.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<?php pageHero([
  'image'   => 'secimg_hero_contact',
  'eyebrow' => 'Contact',
  'lead'    => 'Let’s Talk About',
  'accent'  => 'Your Project',
  'sub'     => 'Tell us what you are building. We reply within 24 hours with a tailored proposal — no commitment required.',
  'crumbs'  => [['Contact Us', null]],
  'actions' => [['Send a Message', '#enquiry', true], ['Call Us', 'tel:' . $telHref, false]],
]); ?>

<div class="cx">

  <!-- ══ 1 · Quick contact ══ -->
  <section class="cx-quick">
    <div class="cx-wrap">
      <div class="cx-quick-grid">

        <div class="cx-qcard">
          <span class="cx-qico" aria-hidden="true"><i class="fas fa-phone-alt"></i></span>
          <h3>Call Us</h3>
          <a href="tel:<?= e($telHref) ?>"><?= e($phone) ?></a>
          <p>Speak to our team directly</p>
        </div>

        <div class="cx-qcard">
          <span class="cx-qico" aria-hidden="true"><i class="fas fa-envelope"></i></span>
          <h3>Email Us</h3>
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
          <p>We reply within 24 hours</p>
        </div>

        <div class="cx-qcard">
          <span class="cx-qico" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
          <h3>Visit Us</h3>
          <?php foreach ($offices as [$_qLabel, $_qAddr]): ?>
          <p><strong class="cx-qoff"><?= e($_qLabel) ?></strong><?= nl2br(e($_qAddr)) ?></p>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ══ 2 · Info + form ══ -->
  <section class="cx-main" id="enquiry">
    <div class="cx-wrap">
      <div class="cx-layout">

        <!-- ── Info ── -->
        <aside class="cx-info">
          <span class="cx-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Free consultation</span>
          <h2>Let’s build something <span class="cx-grad">worth shipping</span></h2>
          <p>
            Whether you have a full specification or just a rough idea, we would like to hear
            about it. Our team reads every brief and comes back with a tailored proposal.
          </p>

          <div class="cx-item">
            <span class="cx-item-ico" aria-hidden="true"><i class="fas fa-phone-alt"></i></span>
            <div>
              <h3>Phone</h3>
              <a href="tel:<?= e($telHref) ?>"><?= e($phone) ?></a>
            </div>
          </div>

          <div class="cx-item">
            <span class="cx-item-ico" aria-hidden="true"><i class="fas fa-envelope"></i></span>
            <div>
              <h3>Email</h3>
              <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            </div>
          </div>

          <div class="cx-item">
            <span class="cx-item-ico" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
            <div>
              <h3><?= e($offices[0][0] ?? 'Office') ?></h3>
              <p><?= nl2br(e($offices[0][1] ?? $address)) ?></p>
            </div>
          </div>

          <?php /* Any further offices repeat the same row */ ?>
          <?php foreach (array_slice($offices, 1) as [$_oL, $_oA]): ?>
          <div class="cx-item">
            <span class="cx-item-ico" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
            <div>
              <h3><?= e($_oL) ?></h3>
              <p><?= nl2br(e($_oA)) ?></p>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if ($socials): ?>
          <hr class="cx-rule">
          <span class="cx-follow-label">Follow us</span>
          <div class="cx-socials">
            <?php foreach ($socials as $sc): ?>
            <a href="<?= e($sc['url']) ?>" target="_blank" rel="noopener"
               aria-label="<?= e($sc['label']) ?>" title="<?= e($sc['label']) ?>">
              <i class="<?= e($sc['icon']) ?>" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </aside>

        <!-- ── Form ── -->
        <div class="cx-form-card">
          <h2>Send us a message</h2>
          <p>Fill in the form below and we will respond within 24 hours with a tailored proposal.</p>

          <div id="form-alert" class="cx-alert" role="status" aria-live="polite"></div>

          <form id="contactForm" onsubmit="submitContactForm(event)" novalidate>
            <input type="hidden" id="cf_csrf" value="<?= csrfToken() ?>">
            <input type="text" name="honeypot" style="display:none;" tabindex="-1" autocomplete="off">

            <div class="cx-row">
              <div class="cx-field">
                <label for="cf_name">Full name <span class="cx-req">*</span></label>
                <input type="text" id="cf_name" placeholder="Your full name" required autocomplete="name">
              </div>
              <div class="cx-field">
                <label for="cf_email">Email address <span class="cx-req">*</span></label>
                <input type="email" id="cf_email" placeholder="your@email.com" required autocomplete="email">
              </div>
            </div>

            <div class="cx-row">
              <div class="cx-field">
                <label for="cf_phone">Phone number <span class="cx-req">*</span></label>
                <div class="cx-phone-row">
                  <label class="sr-only" for="cf_country">Country code</label>
                  <select id="cf_country">
                    <option value="+91" selected>🇮🇳 +91</option>
                    <option value="+1">🇺🇸 +1</option>
                    <option value="+44">🇬🇧 +44</option>
                    <option value="+61">🇦🇺 +61</option>
                    <option value="+971">🇦🇪 +971</option>
                    <option value="+65">🇸🇬 +65</option>
                    <option value="+60">🇲🇾 +60</option>
                    <option value="+880">🇧🇩 +880</option>
                  </select>
                  <input type="tel" id="cf_phone" placeholder="Mobile number" required autocomplete="tel">
                </div>
              </div>
              <div class="cx-field">
                <label for="cf_company">Company name</label>
                <input type="text" id="cf_company" placeholder="Your company (optional)" autocomplete="organization">
              </div>
            </div>

            <div class="cx-field">
              <label for="cf_service">Service interested in</label>
              <select id="cf_service">
                <option value="">— Select a service —</option>
                <option>Custom Software Development</option>
                <option>ERP Development</option>
                <option>CRM Development</option>
                <option>CMS Development</option>
                <option>Mobile App Development (Android &amp; iOS)</option>
                <option>AI Product Development</option>
                <option>SaaS Platform Development</option>
                <option>Web Portal Development</option>
                <option>Enterprise Applications</option>
                <option>Automation Solutions</option>
                <option>High-End Business Websites</option>
                <option>Custom Business Platforms</option>
                <option>Other / Not Sure</option>
              </select>
            </div>

            <div class="cx-field">
              <label for="cf_budget">Budget range</label>
              <select id="cf_budget">
                <option value="">— Select budget range —</option>
                <option>Under ₹50,000</option>
                <option>₹50,000 – ₹1,00,000</option>
                <option>₹1,00,000 – ₹3,00,000</option>
                <option>₹3,00,000 – ₹10,00,000</option>
                <option>Above ₹10,00,000</option>
                <option>Discuss with team</option>
              </select>
            </div>

            <div class="cx-field">
              <label for="cf_message">Your message <span class="cx-req">*</span></label>
              <textarea id="cf_message" placeholder="Tell us about your project — goals, timeline, any specific requirements..." required></textarea>
            </div>

            <?php require __DIR__ . '/includes/captcha-component.php'; ?>

            <button type="submit" class="cx-submit" id="cf_submit">
              <i class="fas fa-paper-plane" aria-hidden="true"></i>
              <span class="cx-submit-label">Send Message</span>
            </button>
          </form>
        </div>

      </div>

      <!-- ══ 3 · Map ══ -->
      <div class="cx-map">
        <?php if ($mapEmbed): ?>
          <div style="height:380px;"><?= $mapEmbed ?></div>
        <?php else: ?>
          <?php
          /* Key-less Google Maps embed built from the admin address */
          $mapSrc = 'https://maps.google.com/maps?q=' . urlencode($address)
                  . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
          ?>
          <iframe src="<?= e($mapSrc) ?>" height="380" loading="lazy"
                  allowfullscreen referrerpolicy="no-referrer-when-downgrade"
                  title="Appsgain Technologies office location"></iframe>
        <?php endif; ?>

        <div class="cx-map-foot">
          <div class="cx-map-addr">
            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
            <span><?= e($address) ?></span>
          </div>
          <a class="cx-map-btn" href="https://maps.google.com/?q=<?= urlencode($address) ?>"
             target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i> Open in Google Maps
          </a>
        </div>
      </div>

    </div>
  </section>

</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
<script src="<?= ASSETS_URL ?>/js/captcha-cta.js"></script>
<script>
function submitContactForm(e) {
  e.preventDefault();
  var btn       = document.getElementById('cf_submit');
  var label     = btn.querySelector('.cx-submit-label') || btn;
  var alrt      = document.getElementById('form-alert');
  var phoneEl   = document.getElementById('cf_phone');
  var phone     = phoneEl ? phoneEl.value.trim() : '';
  var captchaEl = document.querySelector('#contactForm .captcha-input');

  function fail(icon, text, focusEl) {
    alrt.className = 'cx-alert is-err';
    alrt.innerHTML = '<i class="fas ' + icon + '"></i> ' + text;
    if (focusEl) focusEl.focus();
  }

  var name    = document.getElementById('cf_name').value.trim();
  var email   = document.getElementById('cf_email').value.trim();
  var message = document.getElementById('cf_message').value.trim();

  if (!name)  { return fail('fa-exclamation-circle', 'Please enter your name.', document.getElementById('cf_name')); }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
    return fail('fa-exclamation-circle', 'Please enter a valid email address.', document.getElementById('cf_email'));
  }
  if (!phone || phone.replace(/\D/g, '').length < 7) {
    return fail('fa-exclamation-circle', 'Phone number is required.', phoneEl);
  }
  if (!message) {
    return fail('fa-exclamation-circle', 'Please tell us a little about your project.', document.getElementById('cf_message'));
  }
  if (captchaEl && captchaEl.value.trim().length !== 6) {
    return fail('fa-shield-alt', 'Please complete the CAPTCHA verification.', captchaEl);
  }

  alrt.className = 'cx-alert';
  btn.disabled = true;
  label.textContent = 'Sending…';

  var data = {
    csrf_token:   document.getElementById('cf_csrf').value,
    name:         name,
    email:        email,
    phone:        document.getElementById('cf_country').value + ' ' + phone,
    company:      document.getElementById('cf_company')?.value.trim() || '',
    service:      document.getElementById('cf_service')?.value || '',
    budget:       document.getElementById('cf_budget')?.value || '',
    message:      message,
    captcha_code: captchaEl ? captchaEl.value.trim() : '',
    honeypot:     ''
  };

  fetch('<?= SITE_URL ?>/api/contact.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    if (d.success) {
      alrt.className = 'cx-alert is-ok';
      alrt.innerHTML = '<i class="fas fa-check-circle"></i> ' + d.message;
      document.getElementById('contactForm').reset();
      /* reset() blanks the field but not the server-side challenge, so
         pull a fresh image — the component ids are "<id>-input" etc. */
      if (window.refreshCaptcha && captchaEl && captchaEl.id) {
        window.refreshCaptcha(captchaEl.id.replace(/-input$/, ''));
      }
    } else {
      alrt.className = 'cx-alert is-err';
      alrt.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' +
        (d.message || 'Something went wrong. Please try again.');
    }
    btn.disabled = false;
    label.textContent = 'Send Message';
  })
  .catch(function () {
    alrt.className = 'cx-alert is-err';
    alrt.innerHTML = '<i class="fas fa-exclamation-circle"></i> Network error. Please try again.';
    btn.disabled = false;
    label.textContent = 'Send Message';
  });
}
</script>
</body>
</html>
