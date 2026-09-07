<?php
$adminPage  = 'email-compose';
$adminTitle = 'Compose Email';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
require_once dirname(dirname(__DIR__)) . '/includes/email-template.php';

$sent = false; $sendError = '';

/* ── Handle send ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $to      = sanitizeInput($_POST['to']      ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $body    = $_POST['body'] ?? '';
    $type    = in_array($_POST['type']??'', ['success','info','warning','error','plain']) ? $_POST['type'] : 'info';
    $ctaTxt  = sanitizeInput($_POST['cta_text'] ?? '');
    $ctaUrl  = sanitizeInput($_POST['cta_url']  ?? '');
    $accent  = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent_color'] ?? '') ? $_POST['accent_color'] : '#4f46e5';

    /* Support multiple recipients (comma-separated) */
    $recipients = array_map('trim', explode(',', $to));
    $recipients = array_filter($recipients, 'validateEmail');

    if (empty($recipients)) { $sendError = 'Please enter at least one valid email address.'; }
    elseif (!$subject)      { $sendError = 'Subject line is required.'; }
    elseif (!$body)         { $sendError = 'Email body cannot be empty.'; }
    else {
        $opts = [
            'subject'      => $subject,
            'type'         => $type,
            'accent_color' => $accent,
            'heading'      => sanitizeInput($_POST['heading'] ?? $subject),
            'subheading'   => sanitizeInput($_POST['subheading'] ?? ''),
            'body'         => $body,
            'cta_text'     => $ctaTxt,
            'cta_url'      => $ctaUrl,
            'cta_color'    => $accent,
        ];
        $failCount = 0;
        foreach ($recipients as $r) {
            if (!sendTemplatedEmail($r, $subject, $opts)) $failCount++;
        }
        if ($failCount === 0) {
            $sent = true;
            logActivity('email', 'compose', "Composed email sent to: " . implode(', ', $recipients));
            setFlash('success', 'Email sent to ' . count($recipients) . ' recipient(s).');
        } else {
            $sendError = $failCount . ' of ' . count($recipients) . ' email(s) failed. Check server mail config.';
        }
    }
}

/* ── Quick recipient lists ── */
try {
    $subscribers = dbFetchAll("SELECT email, name FROM newsletter_subscribers WHERE status='active' ORDER BY created_at DESC LIMIT 100");
    $leads       = dbFetchAll("SELECT DISTINCT email, name FROM leads WHERE email != '' ORDER BY created_at DESC LIMIT 100");
} catch(Exception $e) { $subscribers = []; $leads = []; }

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.compose-grid { display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }
.recipient-tag { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; background:var(--violet-lt); color:var(--violet); font-size:12px; font-weight:600; border:1px solid rgba(124,58,237,.2); }
.recipient-tag button { background:none; border:none; cursor:pointer; color:var(--violet); font-size:11px; padding:0; line-height:1; }
.quick-list { max-height:180px; overflow-y:auto; }
.quick-item { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid var(--border); font-size:12.5px; cursor:pointer; transition:.15s; }
.quick-item:hover { color:var(--violet); }
.quick-item:last-child { border-bottom:none; }
@media(max-width:900px) { .compose-grid { grid-template-columns:1fr; } }
</style>

<div class="page-header">
  <div>
    <h1><i class="fas fa-envelope-open-text" style="color:var(--violet);"></i> Compose Email</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px;">Send a beautifully formatted email to any recipient directly from the admin panel</p>
  </div>
  <a href="<?= ADMIN_URL ?>/pages/email-designer.php" class="btn btn-secondary btn-sm"><i class="fas fa-paint-brush"></i> Email Designer</a>
</div>

<?php if ($sent): ?>
<div class="alert-success" style="margin-bottom:20px;"><i class="fas fa-check-circle"></i> Email sent successfully!</div>
<?php endif; ?>
<?php if ($sendError): ?>
<div class="alert-error" style="margin-bottom:20px;"><i class="fas fa-exclamation-circle"></i> <?= e($sendError) ?></div>
<?php endif; ?>

<form method="POST" id="composeForm">
  <?= csrfField() ?>
  <div class="compose-grid">

    <!-- ── LEFT: Compose ── -->
    <div>

      <!-- Recipients -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3><i class="fas fa-users" style="color:var(--blue);"></i> Recipients</h3></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0;">
            <label>To: (comma-separate multiple emails) <span class="required">*</span></label>
            <input type="text" name="to" id="toField" class="form-control"
              value="" placeholder="email@example.com, another@example.com" required
              style="font-family:monospace;">
            <div class="form-hint">Enter email addresses separated by commas. Or use Quick Recipients below →</div>
          </div>
        </div>
      </div>

      <!-- Subject & Header -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3><i class="fas fa-heading" style="color:var(--violet);"></i> Subject &amp; Header</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Subject Line <span class="required">*</span></label>
            <input type="text" name="subject" class="form-control" required
              placeholder="e.g. Important update from Appsgain Technologies">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Heading</label>
              <input type="text" name="heading" class="form-control"
                placeholder="Main heading inside email">
            </div>
            <div class="form-group">
              <label>Sub-Heading</label>
              <input type="text" name="subheading" class="form-control"
                placeholder="Optional sub-heading">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Type</label>
              <select name="type" class="form-control">
                <option value="info">ℹ️ Info (Blue)</option>
                <option value="success">✅ Success (Green)</option>
                <option value="warning">⚠️ Warning (Amber)</option>
                <option value="error">❌ Alert (Red)</option>
                <option value="plain">⬜ Plain (Indigo)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Accent Color</label>
              <div style="display:flex;gap:8px;">
                <input type="color" name="accent_color" id="accentPicker" value="#4f46e5" style="width:44px;height:36px;padding:2px;border-radius:8px;border:1.5px solid var(--border);cursor:pointer;">
                <input type="text" id="accentHex" class="form-control" value="#4f46e5" placeholder="#4f46e5" oninput="document.getElementById('accentPicker').value=this.value">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3><i class="fas fa-align-left" style="color:var(--emerald);"></i> Email Body <span class="required">*</span></h3></div>
        <div class="card-body" style="padding-bottom:0;">
          <textarea name="body" id="composeBody" class="form-control" rows="10" required
            placeholder="Write your email content here. HTML is supported."><?= htmlspecialchars(isset($_POST['body']) ? $_POST['body'] : '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <!-- CTA Button -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3><i class="fas fa-mouse-pointer" style="color:var(--amber);"></i> CTA Button <span style="font-size:12px;font-weight:400;color:var(--gray);">(optional)</span></h3></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label>Button Text</label>
              <input type="text" name="cta_text" class="form-control" placeholder="e.g. View Now →">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Button URL</label>
              <input type="url" name="cta_url" class="form-control" placeholder="https://…">
            </div>
          </div>
        </div>
      </div>

      <!-- Send button -->
      <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:15px;">
        <i class="fas fa-paper-plane"></i> Send Email
      </button>
    </div>

    <!-- ── RIGHT: Quick recipients ── -->
    <div>

      <!-- Quick fill recipients -->
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header"><h3><i class="fas fa-bolt" style="color:var(--amber);"></i> Quick Recipients</h3></div>
        <div class="card-body" style="padding:0;">
          <!-- Tabs -->
          <div style="display:flex;border-bottom:1px solid var(--border);">
            <button type="button" style="flex:1;padding:9px;font-size:12.5px;font-weight:700;border:none;background:none;cursor:pointer;color:var(--violet);border-bottom:2px solid var(--violet);" onclick="showList('subscribers',this)">Newsletter</button>
            <button type="button" style="flex:1;padding:9px;font-size:12.5px;font-weight:700;border:none;background:none;cursor:pointer;color:var(--gray);border-bottom:2px solid transparent;" onclick="showList('leads',this)">Leads</button>
          </div>
          <div style="padding:10px 14px;">
            <div id="qlist-subscribers" class="quick-list">
              <?php if ($subscribers): ?>
              <?php foreach ($subscribers as $s): ?>
              <div class="quick-item" onclick="addRecipient('<?= e($s['email']) ?>')">
                <i class="fas fa-envelope" style="font-size:11px;color:var(--gray2);"></i>
                <span><?= e($s['email']) ?></span>
                <?php if ($s['name']): ?><span style="color:var(--gray2);font-size:11px;">— <?= e($s['name']) ?></span><?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php else: ?><p style="font-size:12.5px;color:var(--gray);padding:8px 0;">No subscribers yet.</p><?php endif; ?>
            </div>
            <div id="qlist-leads" class="quick-list" style="display:none;">
              <?php if ($leads): ?>
              <?php foreach ($leads as $l): ?>
              <div class="quick-item" onclick="addRecipient('<?= e($l['email']) ?>')">
                <i class="fas fa-user" style="font-size:11px;color:var(--gray2);"></i>
                <span><?= e($l['email']) ?></span>
                <?php if ($l['name']): ?><span style="color:var(--gray2);font-size:11px;">— <?= e($l['name']) ?></span><?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php else: ?><p style="font-size:12.5px;color:var(--gray);padding:8px 0;">No leads yet.</p><?php endif; ?>
            </div>
            <!-- Send to all in list -->
            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="addAllRecipients('subscribers')">+ All Newsletter</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="addAllRecipients('leads')">+ All Leads</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview info -->
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header"><h3><i class="fas fa-eye"></i> Live Preview</h3></div>
        <div class="card-body" style="padding:12px;">
          <a href="<?= ADMIN_URL ?>/pages/email-designer.php" target="_blank" class="btn btn-outline" style="width:100%;justify-content:center;">
            <i class="fas fa-paint-brush"></i> Open Email Designer
          </a>
        </div>
      </div>

      <!-- Tips -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-lightbulb" style="color:var(--amber);"></i> Tips</h3></div>
        <div class="card-body" style="font-size:12.5px;color:var(--gray);line-height:1.8;">
          <ul style="padding-left:16px;margin:0;">
            <li>Emails use your branded template automatically</li>
            <li>HTML is supported in the body field</li>
            <li>Use comma to separate multiple recipients</li>
            <li>Test first by sending to your own email</li>
            <li>Check spam folder if email doesn't arrive</li>
            <li>Configure SMTP in <a href="<?= ADMIN_URL ?>/pages/settings.php?tab=mail" style="color:var(--violet);">Settings → Mail</a></li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/ckeditor-assets.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor('composeBody', { height: 380, placeholder: 'Write your email content here. Supports rich text formatting.' });
});

/* Accent color sync */
document.getElementById('accentPicker').addEventListener('input', function() {
    document.getElementById('accentHex').value = this.value;
});

/* Add single recipient */
function addRecipient(email) {
    var field = document.getElementById('toField');
    var current = field.value.trim();
    if (current && !current.endsWith(',')) current += ', ';
    if (current.includes(email)) return; /* already added */
    field.value = current + email;
    field.focus();
}

/* Tab switch */
function showList(id, btn) {
    ['subscribers','leads'].forEach(function(k) {
        var el = document.getElementById('qlist-' + k);
        if (el) el.style.display = (k === id) ? 'block' : 'none';
    });
    document.querySelectorAll('[onclick*="showList"]').forEach(function(b) {
        b.style.color = 'var(--gray)';
        b.style.borderBottomColor = 'transparent';
    });
    btn.style.color = 'var(--violet)';
    btn.style.borderBottomColor = 'var(--violet)';
}

/* Add all from a list */
var allData = {
    subscribers: <?= json_encode(array_column($subscribers, 'email')) ?>,
    leads:       <?= json_encode(array_column($leads, 'email')) ?>,
};
function addAllRecipients(type) {
    var field   = document.getElementById('toField');
    var current = field.value.split(',').map(function(e){ return e.trim(); }).filter(Boolean);
    (allData[type] || []).forEach(function(email) {
        if (!current.includes(email)) current.push(email);
    });
    field.value = current.join(', ');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
