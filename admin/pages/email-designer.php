<?php
$adminPage  = 'email-designer';
$adminTitle = 'Email Designer';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
require_once dirname(dirname(__DIR__)) . '/includes/email-template.php';

/* ── Handle AJAX preview render ── */
if (($_GET['action'] ?? '') === 'preview') {
    header('Content-Type: text/html; charset=UTF-8');
    $tpl  = sanitizeInput($_GET['tpl'] ?? 'contact_reply');
    $opts = buildEmailOpts($tpl, [
        'heading'     => $_GET['heading']     ?? null,
        'subheading'  => $_GET['subheading']  ?? null,
        'body'        => $_GET['body']        ?? null,
        'cta_text'    => $_GET['cta_text']    ?? null,
        'cta_url'     => $_GET['cta_url']     ?? null,
        'accent_color'=> $_GET['accent_color']?? null,
        'type'        => $_GET['type']        ?? null,
    ]);
    echo renderEmailHtml($opts);
    exit;
}

/* ── Handle send test ── */
$sendMsg = ''; $sendOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_test') {
    verifyCsrf();
    $to   = sanitizeInput($_POST['test_email'] ?? '');
    $tpl  = sanitizeInput($_POST['template'] ?? 'contact_reply');
    if (!validateEmail($to)) {
        $sendMsg = 'Please enter a valid email address.';
    } else {
        $opts = buildEmailOpts($tpl, [
            'heading'     => sanitizeInput($_POST['heading']     ?? ''),
            'subheading'  => sanitizeInput($_POST['subheading']  ?? ''),
            'cta_text'    => sanitizeInput($_POST['cta_text']    ?? ''),
            'cta_url'     => sanitizeInput($_POST['cta_url']     ?? ''),
            'accent_color'=> sanitizeInput($_POST['accent_color']?? ''),
            'type'        => sanitizeInput($_POST['type']        ?? ''),
        ]);
        $sent = sendTemplatedEmail($to, $opts['subject'], $opts);
        $sendMsg = $sent ? "✅ Test email sent to <strong>{$to}</strong>" : '❌ Failed to send. Check server mail config.';
        $sendOk  = $sent;
    }
}

/* ── Template library ── */
function buildEmailOpts(string $tpl, array $overrides = []): array {
    $su  = defined('SITE_URL')  ? SITE_URL  : '';
    $au  = defined('ADMIN_URL') ? ADMIN_URL : '';

    $base = [
        'contact' => [
            'subject'    => '🔔 New Lead: John Doe — Custom Software Development',
            'type'       => 'info', 'accent_color' => '#4f46e5',
            'heading'    => '🔔 New Lead Received',
            'subheading' => 'A visitor just submitted the contact form',
            'body'       => '<p style="color:#334155;font-size:15px;">Review the details below and respond within 24 hours.</p>',
            'info_rows'  => [
                ['label'=>'Name',    'value'=>'John Doe'],
                ['label'=>'Email',   'value'=>'john@example.com'],
                ['label'=>'Phone',   'value'=>'+91 9876543210'],
                ['label'=>'Service', 'value'=>'Custom Software Development'],
                ['label'=>'Budget',  'value'=>'₹3,00,000 – ₹10,00,000'],
                ['label'=>'Message', 'value'=>'We need a complete ERP system for our manufacturing unit.'],
            ],
            'cta_text'   => 'View in Admin Panel',
            'cta_url'    => $au . '/pages/leads.php',
            'cta_color'  => '#4f46e5',
        ],
        'contact_reply' => [
            'subject'    => 'We received your message — Appsgain Technologies',
            'type'       => 'success', 'accent_color' => '#4f46e5',
            'heading'    => 'Thank You, John! 🎉',
            'subheading' => 'Your message has been successfully received',
            'body'       => '<p style="font-size:15px;color:#334155;line-height:1.8;">Dear <strong>John Doe</strong>,</p>'
                          . '<p style="font-size:15px;color:#334155;line-height:1.8;">Thank you for contacting <strong>Appsgain Technologies</strong>! We have received your enquiry regarding <strong>Custom Software Development</strong>.</p>'
                          . '<p style="font-size:15px;color:#334155;line-height:1.8;">We will respond within <strong>24 hours</strong> with a tailored proposal. No commitment required.</p>'
                          . '<p style="font-size:14px;color:#64748b;padding:16px;background:#f8faff;border-radius:10px;border-left:4px solid #4f46e5;">📞 Urgent? Call us: <a href="tel:+919955446477" style="color:#4f46e5;font-weight:700;">+91-9955446477</a></p>',
            'cta_text'   => 'View Our Portfolio', 'cta_url' => $su . '/portfolio.php', 'cta_color' => '#4f46e5',
            'cta2_text'  => 'Explore Services',   'cta2_url' => $su . '/services.php',
        ],
        'job_application' => [
            'subject'    => '📋 New Job Application: Priya Sharma — React Native Developer',
            'type'       => 'warning', 'accent_color' => '#d97706',
            'heading'    => '📋 New Job Application',
            'subheading' => 'A candidate just applied via your careers page',
            'body'       => '<p style="color:#334155;font-size:15px;">Review the application. Resume has been saved to your server.</p>',
            'info_rows'  => [
                ['label'=>'Applicant','value'=>'Priya Sharma'],
                ['label'=>'Email',    'value'=>'priya@example.com'],
                ['label'=>'Phone',    'value'=>'+91 9123456789'],
                ['label'=>'Position', 'value'=>'<strong>React Native Developer</strong>'],
                ['label'=>'Resume',   'value'=>'<a href="#" style="color:#059669;font-weight:700;">Download Resume →</a>'],
            ],
            'cta_text'   => 'View Applications', 'cta_url' => $au . '/pages/applications.php', 'cta_color' => '#d97706',
        ],
        'application_confirm' => [
            'subject'    => 'Application Received — Appsgain Technologies',
            'type'       => 'success', 'accent_color' => '#059669',
            'heading'    => 'Application Received, Priya! 🎯',
            'subheading' => 'Thank you for applying to Appsgain Technologies',
            'body'       => '<p style="font-size:15px;color:#334155;">Dear <strong>Priya Sharma</strong>,</p>'
                          . '<p style="font-size:15px;color:#334155;line-height:1.8;">We received your application for <strong>React Native Developer</strong>. Our HR team will review and contact you within <strong>3–5 business days</strong>.</p>'
                          . '<p style="font-size:14px;color:#64748b;padding:16px;background:#f0fdf4;border-radius:10px;border-left:4px solid #059669;line-height:1.8;">✅ <strong>Next Steps:</strong><br>• Resume review → Screening call → Technical Interview → HR Round → Offer</p>',
            'cta_text'   => 'View Open Positions', 'cta_url' => $su . '/careers.php', 'cta_color' => '#059669',
        ],
        'newsletter_welcome' => [
            'subject'    => 'Welcome to Appsgain Technologies Newsletter! 🎉',
            'type'       => 'success', 'accent_color' => '#4f46e5',
            'heading'    => 'Welcome, Rahul! 🎉',
            'subheading' => "You're now subscribed to our newsletter",
            'body'       => '<p style="font-size:15px;color:#334155;line-height:1.8;">Thank you for subscribing to <strong>Appsgain Technologies</strong> newsletter!</p>'
                          . '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">'
                          . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">🚀 &nbsp;Product launches &amp; new features</td></tr>'
                          . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">📝 &nbsp;Tech articles &amp; industry insights</td></tr>'
                          . '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;">💡 &nbsp;Case studies &amp; success stories</td></tr>'
                          . '<tr><td style="padding:8px 0;font-size:14px;color:#334155;">🎯 &nbsp;Exclusive offers &amp; early access</td></tr></table>',
            'cta_text'   => 'Read Latest Blog', 'cta_url' => $su . '/blog.php', 'cta_color' => '#4f46e5',
            'footer_extra' => 'Unsubscribe anytime by replying with "unsubscribe".',
        ],
        'custom' => [
            'subject'    => 'Important Update from Appsgain Technologies',
            'type'       => 'info', 'accent_color' => '#4f46e5',
            'heading'    => 'Hello from Appsgain Technologies!',
            'subheading' => 'We have something important to share',
            'body'       => '<p style="font-size:15px;color:#334155;line-height:1.8;">Dear Valued Client,</p>'
                          . '<p style="font-size:15px;color:#334155;line-height:1.8;">This is a sample custom email from <strong>Appsgain Technologies</strong>. Edit this template for announcements, updates, or promotions.</p>',
            'cta_text'   => 'Visit Our Website', 'cta_url' => $su, 'cta_color' => '#4f46e5',
        ],
    ];

    $opts = $base[$tpl] ?? $base['contact_reply'];
    /* Apply overrides — only non-empty values */
    foreach ($overrides as $k => $v) {
        if ($v !== null && $v !== '') $opts[$k] = $v;
    }
    return $opts;
}

$activeTpl  = sanitizeInput($_GET['tpl'] ?? 'contact_reply');
$activeOpts = buildEmailOpts($activeTpl);

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
/* ═══ Email Designer Layout ═══ */
.ed-wrap {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 20px;
  height: calc(100vh - 120px);
  min-height: 600px;
}
/* Left Panel */
.ed-left {
  display: flex; flex-direction: column; gap: 0;
  background: #fff; border-radius: 16px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}
/* Tabs */
.ed-tabs {
  display: flex; border-bottom: 1px solid var(--border);
  background: var(--light); flex-shrink: 0;
}
.ed-tab {
  flex: 1; padding: 11px 8px; font-size: 12px; font-weight: 700;
  color: var(--gray); cursor: pointer; border: none; background: none;
  border-bottom: 2px solid transparent; transition: var(--transition);
  display: flex; align-items: center; justify-content: center; gap: 5px;
  font-family: inherit;
}
.ed-tab:hover { color: var(--violet); }
.ed-tab.active {
  color: var(--violet); background: #fff;
  border-bottom-color: var(--violet);
}
/* Tab panels */
.ed-panel { display: none; flex: 1; overflow-y: auto; padding: 16px; }
.ed-panel.active { display: block; }
/* Right Panel — Preview */
.ed-right {
  display: flex; flex-direction: column;
  background: #fff; border-radius: 16px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.ed-preview-bar {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; border-bottom: 1px solid var(--border);
  background: var(--light); flex-shrink: 0;
}
.ed-dots { display: flex; gap: 5px; }
.ed-dot  { width: 11px; height: 11px; border-radius: 50%; }
.ed-preview-title { flex: 1; text-align: center; font-size: 12.5px; color: var(--gray); font-weight: 600; }
.ed-device { display: flex; gap: 5px; }
.ed-device-btn {
  padding: 4px 10px; border-radius: 6px;
  border: 1.5px solid var(--border); background: #fff;
  color: var(--gray); font-size: 11.5px; font-weight: 700;
  cursor: pointer; transition: var(--transition);
  display: flex; align-items: center; gap: 4px; font-family: inherit;
}
.ed-device-btn.on, .ed-device-btn:hover { border-color: var(--violet); color: var(--violet); background: var(--violet-lt); }
.ed-frame-wrap {
  flex: 1; overflow: auto; background: #e8ecf8;
  display: flex; justify-content: center; align-items: flex-start;
  padding: 16px;
}
#emailFrame {
  background: #fff; border: none;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0,0,0,.12);
  width: 100%; max-width: 660px;
  min-height: 500px;
  transition: max-width .3s ease;
}
/* Template buttons */
.tpl-card {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  border: 1.5px solid var(--border); background: var(--light);
  cursor: pointer; transition: var(--transition);
  margin-bottom: 8px; text-decoration: none; color: inherit;
}
.tpl-card:hover { border-color: var(--violet); background: var(--violet-lt); }
.tpl-card.on { border-color: var(--violet); background: var(--violet-lt); box-shadow: 0 0 0 3px rgba(124,58,237,.08); }
.tpl-card-ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.tpl-card-name { font-size: 13px; font-weight: 700; color: var(--primary); line-height: 1.2; }
.tpl-card-desc { font-size: 11px; color: var(--gray); margin-top: 2px; }
/* Form fields in customize */
.ed-field { margin-bottom: 14px; }
.ed-field label { display: block; font-size: 11.5px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
.ed-field input, .ed-field textarea, .ed-field select { width: 100%; padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; color: var(--text); background: var(--light); outline: none; transition: var(--transition); }
.ed-field input:focus, .ed-field textarea:focus { border-color: var(--violet); background: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,.08); }
.ed-field textarea { resize: vertical; min-height: 80px; }
.ed-field input[type="color"] { width: 44px; height: 36px; padding: 2px; cursor: pointer; border-radius: 8px; }
.color-row { display: flex; align-items: center; gap: 8px; }
.color-row input[type="text"] { flex: 1; }
/* Send result */
.send-result { padding: 12px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-top: 10px; display: none; }
.send-result.ok  { background: rgba(5,150,105,.1); border: 1px solid rgba(5,150,105,.25); color: #065f46; }
.send-result.err { background: rgba(225,29,72,.1);  border: 1px solid rgba(225,29,72,.25);  color: #9f1239; }
/* Action bar */
.ed-actions { display: flex; gap: 8px; flex-shrink: 0; }
@media(max-width:1100px){ .ed-wrap { grid-template-columns:1fr; height:auto; } .ed-right { height:560px; } }
@media(max-width:640px)  { .ed-left { display:none; } }
</style>

<div class="page-header" style="margin-bottom:16px;">
  <div>
    <h1><i class="fas fa-envelope-open-text" style="color:var(--violet)"></i> Email Designer</h1>
    <p style="font-size:13px;color:var(--gray);margin-top:3px;">Preview, customize, and test all email templates</p>
  </div>
  <div class="ed-actions">
    <button class="btn btn-secondary btn-sm" onclick="doFullscreen()"><i class="fas fa-expand"></i> Fullscreen</button>
    <button class="btn btn-secondary btn-sm" onclick="doCopyHtml()"><i class="fas fa-copy"></i> Copy HTML</button>
  </div>
</div>

<div class="ed-wrap">

  <!-- ══════════════ LEFT PANEL ══════════════ -->
  <div class="ed-left">

    <!-- Tabs -->
    <div class="ed-tabs">
      <button class="ed-tab active" onclick="switchTab('templates',this)">
        <i class="fas fa-layer-group"></i> Templates
      </button>
      <button class="ed-tab" onclick="switchTab('customize',this)">
        <i class="fas fa-sliders-h"></i> Customize
      </button>
      <button class="ed-tab" onclick="switchTab('sendtest',this)">
        <i class="fas fa-paper-plane"></i> Send Test
      </button>
    </div>

    <!-- ── TAB 1: Templates ── -->
    <div class="ed-panel active" id="tab-templates">
      <p style="font-size:12px;color:var(--gray);margin-bottom:12px;">Click a template to preview it in the panel →</p>

      <?php
      $tplMeta = [
        'contact'             => ['fa-user-plus',     '#4f46e5','rgba(79,70,229,.1)',  'New Lead Alert',        'Sent to admin when contact form submitted'],
        'contact_reply'       => ['fa-reply',         '#059669','rgba(5,150,105,.1)', 'Lead Auto-Reply',       'Sent to visitor after contact form'],
        'job_application'     => ['fa-file-alt',      '#d97706','rgba(217,119,6,.1)', 'Job Application',       'Admin alert for new career application'],
        'application_confirm' => ['fa-check-circle',  '#059669','rgba(5,150,105,.1)', 'Application Confirm',   'Sent to applicant after applying'],
        'newsletter_welcome'  => ['fa-envelope-open', '#4f46e5','rgba(79,70,229,.1)', 'Newsletter Welcome',    'Sent on newsletter signup'],
        'custom'              => ['fa-edit',           '#7c3aed','rgba(124,58,237,.1)','Custom Template',       'Generic template for any purpose'],
      ];
      foreach ($tplMeta as $key => [$ico, $col, $bg, $name, $desc]):
      ?>
      <div class="tpl-card <?= $activeTpl === $key ? 'on' : '' ?>"
           onclick="loadTemplate('<?= $key ?>')" data-tpl="<?= $key ?>">
        <div class="tpl-card-ic" style="background:<?= $bg ?>;color:<?= $col ?>;">
          <i class="fas <?= $ico ?>"></i>
        </div>
        <div>
          <div class="tpl-card-name"><?= $name ?></div>
          <div class="tpl-card-desc"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>

      <div style="margin-top:16px;padding:12px;background:var(--light);border-radius:10px;font-size:12px;color:var(--gray);line-height:1.7;">
        <strong style="color:var(--primary);display:block;margin-bottom:4px;">💡 How it works</strong>
        Emails are sent automatically when visitors submit forms. Use <em>Customize</em> tab to edit, and <em>Send Test</em> to verify in your inbox.
      </div>
    </div>

    <!-- ── TAB 2: Customize ── -->
    <div class="ed-panel" id="tab-customize">
      <p style="font-size:12px;color:var(--gray);margin-bottom:14px;">Edit template fields below. Preview updates live.</p>

      <div class="ed-field">
        <label>Template</label>
        <select id="cust_tpl" onchange="loadTemplate(this.value)">
          <?php foreach ($tplMeta as $k => [,,,  $n, ]): ?>
          <option value="<?= $k ?>" <?= $activeTpl === $k ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="ed-field">
        <label>Email Type / Color</label>
        <select id="cust_type" onchange="livePreview()">
          <option value="success" <?= ($activeOpts['type']??'') === 'success' ? 'selected':'' ?>>✅ Success (Green)</option>
          <option value="info"    <?= ($activeOpts['type']??'info') === 'info'    ? 'selected':'' ?>>ℹ️ Info (Blue)</option>
          <option value="warning" <?= ($activeOpts['type']??'') === 'warning' ? 'selected':'' ?>>⚠️ Warning (Amber)</option>
          <option value="error"   <?= ($activeOpts['type']??'') === 'error'   ? 'selected':'' ?>>❌ Error (Red)</option>
          <option value="plain"   <?= ($activeOpts['type']??'') === 'plain'   ? 'selected':'' ?>>⬜ Plain (Indigo)</option>
        </select>
      </div>

      <div class="ed-field">
        <label>Accent Color</label>
        <div class="color-row">
          <input type="color"  id="cust_color_picker" value="<?= $activeOpts['accent_color'] ?? '#4f46e5' ?>" oninput="document.getElementById('cust_color').value=this.value;livePreview()">
          <input type="text"   id="cust_color"        value="<?= $activeOpts['accent_color'] ?? '#4f46e5' ?>" placeholder="#4f46e5" oninput="document.getElementById('cust_color_picker').value=this.value;livePreview()">
        </div>
      </div>

      <div class="ed-field">
        <label>Heading</label>
        <input type="text" id="cust_heading" value="<?= htmlspecialchars($activeOpts['heading'] ?? '') ?>" oninput="livePreview()" placeholder="Email main heading">
      </div>

      <div class="ed-field">
        <label>Sub-Heading</label>
        <input type="text" id="cust_subheading" value="<?= htmlspecialchars($activeOpts['subheading'] ?? '') ?>" oninput="livePreview()" placeholder="Optional sub-heading">
      </div>

      <div class="ed-field">
        <label>Body Message <span style="font-weight:400;color:var(--gray)">(HTML allowed)</span></label>
        <textarea id="cust_body" rows="5" oninput="livePreview()"><?= htmlspecialchars($activeOpts['body'] ?? '') ?></textarea>
      </div>

      <div class="ed-field">
        <label>CTA Button Text</label>
        <input type="text" id="cust_cta" value="<?= htmlspecialchars($activeOpts['cta_text'] ?? '') ?>" oninput="livePreview()" placeholder="e.g. View Now →">
      </div>

      <div class="ed-field">
        <label>CTA Button URL</label>
        <input type="url" id="cust_cta_url" value="<?= htmlspecialchars($activeOpts['cta_url'] ?? '') ?>" oninput="livePreview()" placeholder="https://…">
      </div>

      <button class="btn btn-primary" style="width:100%;margin-top:4px;" onclick="switchTab('sendtest',document.querySelector('[onclick*=sendtest]'))">
        <i class="fas fa-paper-plane"></i> Send This Test
      </button>
    </div>

    <!-- ── TAB 3: Send Test ── -->
    <div class="ed-panel" id="tab-sendtest">
      <p style="font-size:12px;color:var(--gray);margin-bottom:14px;">Send the current template to an email address to verify it in a real inbox.</p>

      <?php if ($sendMsg): ?>
      <div class="send-result <?= $sendOk ? 'ok' : 'err' ?>" style="display:block;margin-bottom:14px;">
        <?= $sendMsg ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="sendTestForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="send_test">
        <input type="hidden" name="template" id="st_tpl" value="<?= e($activeTpl) ?>">
        <!-- Pass customize values along -->
        <input type="hidden" name="heading"      id="st_heading"     value="<?= htmlspecialchars($activeOpts['heading'] ?? '') ?>">
        <input type="hidden" name="subheading"   id="st_subheading"  value="<?= htmlspecialchars($activeOpts['subheading'] ?? '') ?>">
        <input type="hidden" name="cta_text"     id="st_cta"         value="<?= htmlspecialchars($activeOpts['cta_text'] ?? '') ?>">
        <input type="hidden" name="cta_url"      id="st_cta_url"     value="<?= htmlspecialchars($activeOpts['cta_url'] ?? '') ?>">
        <input type="hidden" name="accent_color" id="st_color"       value="<?= htmlspecialchars($activeOpts['accent_color'] ?? '#4f46e5') ?>">
        <input type="hidden" name="type"         id="st_type"        value="<?= htmlspecialchars($activeOpts['type'] ?? 'info') ?>">

        <div class="ed-field">
          <label>Recipient Email <span style="color:var(--rose)">*</span></label>
          <input type="email" name="test_email" id="st_email"
            placeholder="your@email.com"
            value="<?= e($admin['email'] ?? '') ?>" required>
        </div>

        <div class="ed-field">
          <label>Template to Send</label>
          <select name="template" id="st_tpl_sel" onchange="document.getElementById('st_tpl').value=this.value;loadTemplate(this.value)">
            <?php foreach ($tplMeta as $k => [,,,  $n, ]): ?>
            <option value="<?= $k ?>" <?= $activeTpl === $k ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">
          <i class="fas fa-paper-plane"></i> Send Test Email Now
        </button>
      </form>

      <div style="margin-top:18px;padding:14px;background:var(--light);border-radius:10px;font-size:12.5px;color:var(--gray);line-height:1.75;">
        <strong style="color:var(--primary);">📬 What to check:</strong><br>
        ✅ Subject line visible in inbox<br>
        ✅ Logo / brand displays correctly<br>
        ✅ Colors and fonts look right<br>
        ✅ CTA button works and links correctly<br>
        ✅ Looks good on mobile<br>
        ✅ Doesn't go to spam folder
      </div>

      <div style="margin-top:12px;padding:12px;background:rgba(37,99,235,.06);border-radius:10px;font-size:12px;color:var(--blue);border:1px solid rgba(37,99,235,.15);">
        <i class="fas fa-info-circle"></i> Test with <strong>Gmail, Outlook</strong>, and <strong>Apple Mail</strong> for full compatibility check.
      </div>
    </div>

  </div><!-- /left -->

  <!-- ══════════════ RIGHT PREVIEW ══════════════ -->
  <div class="ed-right">

    <!-- Preview toolbar -->
    <div class="ed-preview-bar">
      <div class="ed-dots">
        <div class="ed-dot" style="background:#ff5f57"></div>
        <div class="ed-dot" style="background:#febc2e"></div>
        <div class="ed-dot" style="background:#28c840"></div>
      </div>
      <div class="ed-preview-title" id="previewTitle">
        📧 <?= e($tplMeta[$activeTpl][3] ?? 'Email Preview') ?>
      </div>
      <!-- Subject line display -->
      <div style="font-size:11.5px;color:var(--gray);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" id="previewSubject" title="<?= e($activeOpts['subject'] ?? '') ?>">
        Subject: <?= e($activeOpts['subject'] ?? '') ?>
      </div>
      <div class="ed-device">
        <button class="ed-device-btn on" id="dBtn_desktop" onclick="setDevice('desktop',this)">
          <i class="fas fa-desktop"></i> Desktop
        </button>
        <button class="ed-device-btn" id="dBtn_mobile" onclick="setDevice('mobile',this)">
          <i class="fas fa-mobile-alt"></i> Mobile
        </button>
      </div>
    </div>

    <!-- Preview iframe -->
    <div class="ed-frame-wrap">
      <iframe id="emailFrame" title="Email Preview"></iframe>
    </div>

  </div><!-- /right -->

</div><!-- /ed-wrap -->

<!-- Hidden HTML source for copy -->
<textarea id="htmlSource" style="position:fixed;left:-9999px;opacity:0;"></textarea>

<script>
/* ══ State ══ */
var currentTpl = '<?= $activeTpl ?>';
var previewDebounce;

var tplNames = {
  contact:             'New Lead Alert',
  contact_reply:       'Lead Auto-Reply',
  job_application:     'Job Application',
  application_confirm: 'Application Confirm',
  newsletter_welcome:  'Newsletter Welcome',
  custom:              'Custom Template'
};

/* ══ Tab switching ══ */
function switchTab(id, btn) {
  document.querySelectorAll('.ed-panel').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.ed-tab').forEach(function(b){ b.classList.remove('active'); });
  var panel = document.getElementById('tab-' + id);
  if (panel) panel.classList.add('active');
  if (btn) btn.classList.add('active');
}

/* ══ Load template ══ */
function loadTemplate(tpl) {
  currentTpl = tpl;

  /* Highlight active card */
  document.querySelectorAll('.tpl-card').forEach(function(c){
    c.classList.toggle('on', c.dataset.tpl === tpl);
  });

  /* Update selects */
  ['cust_tpl', 'st_tpl_sel'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) el.value = tpl;
  });
  var stTpl = document.getElementById('st_tpl');
  if (stTpl) stTpl.value = tpl;

  /* Update preview title */
  var pt = document.getElementById('previewTitle');
  if (pt) pt.textContent = '📧 ' + (tplNames[tpl] || tpl);

  /* Reload preview */
  refreshPreview();
}

/* ══ Build preview URL ══ */
function buildPreviewUrl() {
  var params = new URLSearchParams({
    action:       'preview',
    tpl:          currentTpl,
    heading:      val('cust_heading'),
    subheading:   val('cust_subheading'),
    body:         val('cust_body'),
    cta_text:     val('cust_cta'),
    cta_url:      val('cust_cta_url'),
    accent_color: val('cust_color'),
    type:         val('cust_type'),
  });
  return window.location.pathname + '?' + params.toString();
}

/* ══ Refresh iframe ══ */
function refreshPreview() {
  var frame = document.getElementById('emailFrame');
  if (!frame) return;
  frame.src = buildPreviewUrl();
  frame.onload = function() {
    /* Sync HTML source for copy button */
    try {
      var src = frame.contentDocument || frame.contentWindow.document;
      document.getElementById('htmlSource').value = src.documentElement.outerHTML;
    } catch(e) {}
  };
}

/* ══ Live preview (debounced) ══ */
function livePreview() {
  /* Sync send test hidden fields */
  syncSendFields();
  clearTimeout(previewDebounce);
  previewDebounce = setTimeout(refreshPreview, 600);
}

function syncSendFields() {
  var map = {
    'cust_heading':    'st_heading',
    'cust_subheading': 'st_subheading',
    'cust_cta':        'st_cta',
    'cust_cta_url':    'st_cta_url',
    'cust_color':      'st_color',
    'cust_type':       'st_type',
  };
  Object.keys(map).forEach(function(from) {
    var src = document.getElementById(from);
    var dst = document.getElementById(map[from]);
    if (src && dst) dst.value = src.value;
  });
}

/* ══ Device toggle ══ */
function setDevice(device, btn) {
  document.querySelectorAll('.ed-device-btn').forEach(function(b){ b.classList.remove('on'); });
  btn.classList.add('on');
  document.getElementById('emailFrame').style.maxWidth = (device === 'mobile') ? '390px' : '660px';
}

/* ══ Copy HTML ══ */
function doCopyHtml() {
  var ta = document.getElementById('htmlSource');
  /* If empty, fetch first */
  if (!ta.value) {
    fetch(buildPreviewUrl())
      .then(function(r){ return r.text(); })
      .then(function(h){
        ta.value = h;
        copyAndNotify(ta);
      });
  } else {
    copyAndNotify(ta);
  }
}
function copyAndNotify(ta) {
  ta.style.position = 'static';
  ta.style.opacity  = '1';
  ta.select();
  document.execCommand('copy');
  ta.style.position = 'fixed';
  ta.style.opacity  = '0';
  var btns = document.querySelectorAll('.ed-actions button');
  btns.forEach(function(b){
    if (b.textContent.includes('Copy')) {
      var orig = b.innerHTML;
      b.innerHTML = '<i class="fas fa-check"></i> Copied!';
      b.style.color = 'var(--emerald)';
      b.style.borderColor = 'var(--emerald)';
      setTimeout(function(){ b.innerHTML = orig; b.style.color=''; b.style.borderColor=''; }, 2000);
    }
  });
}

/* ══ Fullscreen ══ */
function doFullscreen() {
  fetch(buildPreviewUrl())
    .then(function(r){ return r.text(); })
    .then(function(h){
      var w = window.open('', '_blank');
      w.document.write(h);
      w.document.close();
    });
}

/* ══ Helper ══ */
function val(id) {
  var el = document.getElementById(id);
  return el ? el.value : '';
}

/* ══ Boot ══ */
document.addEventListener('DOMContentLoaded', function() {
  refreshPreview();
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
