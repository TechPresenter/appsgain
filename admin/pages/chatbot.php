<?php
/**
 * Admin → Chatbot settings.
 *
 * Every value the assistant uses is editable here. Two are handled with more
 * care than the rest:
 *
 *   The API key is write-only. What is stored never comes back to this page —
 *   the field shows whether a key exists and where it came from, and an empty
 *   submission leaves the saved one alone. Nothing renders the value.
 *
 *   The business information is the whole of what the bot knows. The system
 *   prompt tells it to answer from that text and to say it does not know
 *   otherwise, so an empty box makes a bot that can only refer people to the
 *   contact form — which is why it is seeded from the site's own settings.
 */
$adminTitle = 'Chatbot';
$adminPage  = 'chatbot';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireRole('superadmin', 'admin');
require_once ROOT_PATH . '/includes/chatbot.php';

$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        /* Plain text fields, saved as given. */
        $text = [
            'chatbot_name', 'chatbot_welcome', 'chatbot_business_info',
            'chatbot_system_prompt', 'chatbot_lead_questions', 'chatbot_lead_cta',
            'chatbot_starters', 'chatbot_offline_msg',
        ];
        foreach ($text as $k) {
            saveSetting($k, trim((string)($_POST[$k] ?? '')), 'chatbot');
        }

        /* Constrained fields — the same bounds chatbotConfig() applies when
           reading, so a hand-edited row and this form cannot disagree. */
        saveSetting('chatbot_enabled',  empty($_POST['chatbot_enabled']) ? '0' : '1', 'chatbot');
        saveSetting('chatbot_color',    chatbotSafeColor((string)($_POST['chatbot_color'] ?? '')), 'chatbot');
        saveSetting('chatbot_position',
            ($_POST['chatbot_position'] ?? '') === 'bottom-right' ? 'bottom-right' : 'bottom-left', 'chatbot');

        $model = (string)($_POST['chatbot_model'] ?? '');
        saveSetting('chatbot_model', isset(CHATBOT_MODELS[$model]) ? $model : array_key_first(CHATBOT_MODELS), 'chatbot');

        /* ── Appearance ──
           Each choice is checked against the same list chatbotConfig() reads,
           so a hand-edited row cannot smuggle a value into a class name. */
        saveSetting('chatbot_voice', empty($_POST['chatbot_voice']) ? '0' : '1', 'chatbot');
        saveSetting('chatbot_launcher_label', mb_substr(sanitizeInput((string)($_POST['chatbot_launcher_label'] ?? '')), 0, 40), 'chatbot');
        saveSetting('chatbot_status_text',    mb_substr(sanitizeInput((string)($_POST['chatbot_status_text'] ?? '')), 0, 40), 'chatbot');
        /* A blank second colour is meaningful: it turns the gradient off.
           The checkbox is what decides, not the disabled input — a browser
           with scripting off still submits the colour. */
        saveSetting('chatbot_color2',
            empty($_POST['chatbot_color2_off'])
                ? chatbotSafeColor((string)($_POST['chatbot_color2'] ?? ''), '')
                : '',
            'chatbot');
        foreach ([
            'chatbot_header_style' => [['solid', 'gradient'], 'gradient'],
            'chatbot_style'        => [['rounded', 'soft', 'square'], 'rounded'],
            'chatbot_size'         => [['compact', 'standard', 'large'], 'standard'],
        ] as $k => [$allowed, $default]) {
            $v = (string)($_POST[$k] ?? '');
            saveSetting($k, in_array($v, $allowed, true) ? $v : $default, 'chatbot');
        }

        /* Avatar and launcher icon. uploadFile() derives the extension from
           the verified MIME type, so the stored name never comes from the
           client. An empty file input leaves the current image alone; the
           remove checkbox is how one is cleared. */
        foreach (['chatbot_avatar', 'chatbot_launcher_icon'] as $imgKey) {
            if (!empty($_POST['remove_' . $imgKey])) {
                saveSetting($imgKey, '', 'chatbot');
                continue;
            }
            if (!empty($_FILES[$imgKey]['name'])) {
                $up = uploadFile($_FILES[$imgKey], 'settings');
                if ($up['success']) {
                    saveSetting($imgKey, $up['path'], 'chatbot');
                } else {
                    setFlash('error', 'Image upload failed: ' . $up['error']);
                    redirect(ADMIN_URL . '/pages/chatbot.php');
                }
            }
        }

        saveSetting('chatbot_max_tokens',  (string)max(80, min(1200, (int)($_POST['chatbot_max_tokens'] ?? 400))), 'chatbot');
        saveSetting('chatbot_temperature', (string)max(0, min(1, (float)($_POST['chatbot_temperature'] ?? 0.4))), 'chatbot');
        saveSetting('chatbot_lead_after',  (string)max(0, min(20, (int)($_POST['chatbot_lead_after'] ?? 3))), 'chatbot');

        /* Write-only. Blank means "leave what is stored"; it never means
           "clear it", or every save from this page would wipe the key. */
        $key = trim((string)($_POST['chatbot_api_key'] ?? ''));
        if ($key !== '') {
            saveSetting('chatbot_api_key', $key, 'chatbot');
            logActivity('update', 'chatbot', 'Replaced the OpenAI API key');
        }
        if (!empty($_POST['clear_api_key'])) {
            saveSetting('chatbot_api_key', '', 'chatbot');
            logActivity('update', 'chatbot', 'Cleared the stored OpenAI API key');
        }

        logActivity('update', 'chatbot', 'Updated chatbot settings');
        setFlash('success', 'Chatbot settings saved.');
        redirect(ADMIN_URL . '/pages/chatbot.php');
    }

    /* A live round-trip to OpenAI with the settings as they stand. Cheaper
       than switching the bot on and finding out from a visitor. */
    if ($action === 'test') {
        $cfg  = chatbotConfig();
        $t0   = microtime(true);
        $res  = chatbotComplete([['role' => 'user', 'content' => 'In one short sentence, what does this company do?']], $cfg);
        $testResult = [
            'ok'   => $res['ok'],
            'text' => $res['ok'] ? $res['reply'] : $res['error'],
            'ms'   => (int)round((microtime(true) - $t0) * 1000),
        ];
    }
}

$cfg     = chatbotConfig();
$raw     = getAllSettings('chatbot');
$keyFrom = chatbotKeySource();
/* A key held in the environment or config.secret.php outranks this form. */
$keyLocked  = $keyFrom !== '' && $keyFrom !== 'Settings, below';
$storedKey  = trim((string)($raw['chatbot_api_key'] ?? ''));
$leadCount  = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_leads");
$newCount   = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_leads WHERE status='new'");

/** Value as stored, so an admin edit is never silently replaced by a default. */
$v = static fn(string $k, string $fallback = ''): string => (string)($raw[$k] ?? '') !== ''
    ? (string)$raw[$k] : $fallback;

/* Chrome comes last, after every redirect() above has had its chance to fire.
   admin-layout.php only does auth and page variables — it deliberately emits
   nothing — so this is what brings in <head>, the stylesheets and the sidebar. */
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.cb-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start}
.cb-status{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600}
.cb-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:11.5px;font-weight:700}
.cb-pill.on{background:#dcfce7;color:#166534}
.cb-pill.off{background:#fee2e2;color:#991b1b}
.cb-pill.warn{background:#fef3c7;color:#92400e}
.cb-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12.5px}
.cb-test{border-radius:10px;padding:12px 14px;font-size:13.5px;line-height:1.55;margin-bottom:14px}
.cb-test.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#14532d}
.cb-test.bad{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
textarea.form-control{font-family:inherit;line-height:1.55}
@media(max-width:1100px){.cb-grid{grid-template-columns:1fr}}
</style>

<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span>Chatbot</span>
    </div>
    <h1 class="page-title">AI Chatbot</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px">
      <?php if ($cfg['enabled'] && $keyFrom !== ''): ?>
        <span class="cb-pill on"><i class="fas fa-circle-check"></i> Live on the website</span>
      <?php elseif ($cfg['enabled']): ?>
        <span class="cb-pill warn"><i class="fas fa-triangle-exclamation"></i> Enabled, but no API key — nothing is rendered</span>
      <?php else: ?>
        <span class="cb-pill off"><i class="fas fa-circle-pause"></i> Switched off</span>
      <?php endif; ?>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="<?= ADMIN_URL ?>/pages/chatbot-leads.php" class="btn btn-secondary btn-sm">
      <i class="fas fa-comments"></i> Leads (<?= number_format($leadCount) ?><?= $newCount ? ', ' . $newCount . ' new' : '' ?>)
    </a>
  </div>
</div>

<?php if ($testResult): ?>
  <div class="cb-test <?= $testResult['ok'] ? 'ok' : 'bad' ?>">
    <strong><?= $testResult['ok'] ? 'The assistant replied' : 'The test failed' ?></strong>
    &middot; <?= (int)$testResult['ms'] ?> ms<br>
    <?= e($testResult['text']) ?>
  </div>
<?php endif; ?>

<?php /* multipart: the Appearance card uploads an avatar and launcher icon. */ ?>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save">

  <div class="cb-grid">
    <!-- ── LEFT: what the bot says ───────────────────── -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-robot" style="color:var(--violet)"></i> Identity</h3></div>
        <div class="card-body">
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
            <div>
              <label style="display:block;margin-bottom:4px">Enable the chatbot</label>
              <div class="form-hint" style="margin:0">Off hides it completely — no markup, no script, no cost.</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="chatbot_enabled" value="1" <?= $cfg['enabled'] ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="form-group">
            <label>Bot name</label>
            <input type="text" name="chatbot_name" class="form-control" maxlength="60"
                   value="<?= e($v('chatbot_name', 'Appsgain Assistant')) ?>">
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label>Welcome message</label>
            <textarea name="chatbot_welcome" class="form-control" rows="3"
                      maxlength="500"><?= e($v('chatbot_welcome', $cfg['welcome'])) ?></textarea>
            <div class="form-hint">The first thing a visitor sees. Free — it is not sent to OpenAI.</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-book" style="color:var(--violet)"></i> Business information</h3></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0">
            <label>What the assistant knows about you</label>
            <textarea name="chatbot_business_info" class="form-control" rows="14"
                      placeholder="Services, pricing, process, timelines, contact details&hellip;"><?= e($v('chatbot_business_info', $cfg['businessInfo'])) ?></textarea>
            <div class="form-hint">
              This is the <em>whole</em> of what the bot knows. It is told to answer only from this text and
              to say it does not have a detail rather than guess &mdash; so anything missing here is
              something it will decline to answer. Prices, timelines and policies belong in it.
              Pre-filled from your site settings; edit freely.
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-sliders" style="color:var(--violet)"></i> Extra instructions</h3></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0">
            <label>Tone and rules <small style="color:var(--gray);font-weight:400">(optional)</small></label>
            <textarea name="chatbot_system_prompt" class="form-control" rows="5"
                      placeholder="e.g. Always mention our free 30-minute consultation. Never quote a fixed price."><?= e($v('chatbot_system_prompt')) ?></textarea>
            <div class="form-hint">
              Added after the built-in rules, which it refines rather than replaces &mdash; the
              never-invent-facts and stay-brief instructions always apply.
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-user-plus" style="color:var(--violet)"></i> Lead capture</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>The four questions, one per line, in order</label>
            <textarea name="chatbot_lead_questions" class="form-control cb-mono" rows="4"
                      placeholder="<?= e(implode("\n", array_column($cfg['questions'], 'q'))) ?>"><?= e($v('chatbot_lead_questions')) ?></textarea>
            <div class="form-hint">
              Asked one at a time: <strong>name</strong>, then <strong>mobile</strong>, then
              <strong>email</strong>, then <strong>requirement</strong>. The wording is yours; the order and
              the four fields are fixed, because each is validated differently and stored in its own column.
              Leave blank for the defaults shown.
            </div>
          </div>

          <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
              <label>Button label</label>
              <input type="text" name="chatbot_lead_cta" class="form-control" maxlength="40"
                     value="<?= e($v('chatbot_lead_cta', 'Request a callback')) ?>">
            </div>
            <div class="form-group">
              <label>Offer it after this many replies</label>
              <input type="number" name="chatbot_lead_after" class="form-control" min="0" max="20"
                     value="<?= (int)$cfg['leadAfter'] ?>">
              <div class="form-hint">0 = only when the visitor asks, or the bot decides it is time.</div>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label>Opening suggestions, one per line <small style="color:var(--gray);font-weight:400">(up to 4)</small></label>
            <textarea name="chatbot_starters" class="form-control" rows="3"><?= e($v('chatbot_starters', implode("\n", $cfg['starters']))) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ── RIGHT: how it runs ────────────────────────── -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-key" style="color:var(--violet)"></i> OpenAI key</h3></div>
        <div class="card-body">
          <?php if ($keyFrom !== ''): ?>
            <p class="cb-status" style="margin:0 0 12px">
              <span class="cb-pill on"><i class="fas fa-circle-check"></i> Key configured</span>
            </p>
            <div class="form-hint" style="margin:0 0 14px">
              Read from <strong><?= e($keyFrom) ?></strong>.
              <?= $keyLocked ? 'That source wins over anything entered here.' : '' ?>
            </div>
          <?php else: ?>
            <p class="cb-status" style="margin:0 0 12px">
              <span class="cb-pill off"><i class="fas fa-circle-xmark"></i> No key</span>
            </p>
            <div class="form-hint" style="margin:0 0 14px">
              The widget will not render until a key is set.
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label><?= $storedKey !== '' ? 'Replace the stored key' : 'Paste a key' ?></label>
            <input type="password" name="chatbot_api_key" class="form-control cb-mono"
                   autocomplete="off" placeholder="sk-&hellip;">
            <div class="form-hint">
              Never displayed once saved &mdash; not here, and never in a page or an API response.
              Leaving this blank keeps the key you already have.
            </div>
          </div>

          <?php if ($storedKey !== ''): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:14px">
              <input type="checkbox" name="clear_api_key" value="1"> Delete the stored key
            </label>
          <?php endif; ?>

          <div class="form-hint" style="margin:0">
            Safer than this box: put it in <span class="cb-mono">includes/config.secret.php</span> as
            <span class="cb-mono">openai_api_key</span>, or in the
            <span class="cb-mono">APPSGAIN_OPENAI_API_KEY</span> environment variable. Neither is in the
            database, so a database dump does not leak it.
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-palette" style="color:var(--violet)"></i> Appearance</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Colour</label>
            <div style="display:flex;gap:10px;align-items:center">
              <input type="color" name="chatbot_color" value="<?= e($cfg['color']) ?>"
                     style="width:48px;height:38px;padding:2px;border-radius:8px;border:1px solid var(--border);cursor:pointer">
              <input type="text" class="form-control cb-mono" value="<?= e($cfg['color']) ?>" readonly
                     style="flex:1" aria-label="Selected colour">
            </div>
          </div>
          <div class="form-group">
            <label>Gradient second colour <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
            <div style="display:flex;gap:10px;align-items:center">
              <input type="color" name="chatbot_color2" value="<?= e($cfg['color2'] !== '' ? $cfg['color2'] : $cfg['color']) ?>"
                     style="width:48px;height:38px;padding:2px;border-radius:8px;border:1px solid var(--border);cursor:pointer">
              <label style="display:flex;align-items:center;gap:7px;font-size:13px;margin:0">
                <input type="checkbox" name="chatbot_color2_off" value="1" <?= $cfg['color2'] === '' ? 'checked' : '' ?>
                       onchange="this.form.chatbot_color2.disabled = this.checked">
                Use one flat colour
              </label>
            </div>
            <div class="form-hint">The header and launcher fade from the first colour to this one.</div>
          </div>

          <div class="form-group">
            <label>Header fill</label>
            <select name="chatbot_header_style" class="form-control">
              <option value="gradient" <?= $cfg['headerStyle'] === 'gradient' ? 'selected' : '' ?>>Gradient</option>
              <option value="solid"    <?= $cfg['headerStyle'] === 'solid'    ? 'selected' : '' ?>>Solid</option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Corner style</label>
              <select name="chatbot_style" class="form-control">
                <option value="rounded" <?= $cfg['style'] === 'rounded' ? 'selected' : '' ?>>Rounded</option>
                <option value="soft"    <?= $cfg['style'] === 'soft'    ? 'selected' : '' ?>>Soft</option>
                <option value="square"  <?= $cfg['style'] === 'square'  ? 'selected' : '' ?>>Square</option>
              </select>
            </div>
            <div class="form-group">
              <label>Panel size</label>
              <select name="chatbot_size" class="form-control">
                <option value="compact"  <?= $cfg['size'] === 'compact'  ? 'selected' : '' ?>>Compact</option>
                <option value="standard" <?= $cfg['size'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                <option value="large"    <?= $cfg['size'] === 'large'    ? 'selected' : '' ?>>Large</option>
              </select>
            </div>
          </div>

          <?php /* Avatar and launcher icon. Each shows what is stored, so an
                   admin can see there is an image without opening the site. */ ?>
          <div class="form-row">
            <?php foreach ([
              'chatbot_avatar'        => ['Header avatar', $cfg['avatar'],       'Square works best. Blank shows the first letter of the name.'],
              'chatbot_launcher_icon' => ['Launcher icon', $cfg['launcherIcon'], 'Blank shows the default speech-bubble icon.'],
            ] as $ik => [$ilabel, $iurl, $ihint]): ?>
            <div class="form-group">
              <label><?= e($ilabel) ?></label>
              <?php if ($iurl !== ''): ?>
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <img src="<?= e($iurl) ?>" alt="" style="width:40px;height:40px;border-radius:9px;object-fit:cover;border:1px solid var(--border)">
                <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;margin:0;color:#dc2626">
                  <input type="checkbox" name="remove_<?= e($ik) ?>" value="1"> Remove
                </label>
              </div>
              <?php endif; ?>
              <input type="file" name="<?= e($ik) ?>" class="form-control" accept="image/*">
              <div class="form-hint"><?= e($ihint) ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Launcher label <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
              <input type="text" name="chatbot_launcher_label" class="form-control" maxlength="40"
                     value="<?= e($cfg['launcherLabel']) ?>" placeholder="Chat with us">
              <div class="form-hint">Text beside the floating button. Blank shows the button alone.</div>
            </div>
            <div class="form-group">
              <label>Status line</label>
              <input type="text" name="chatbot_status_text" class="form-control" maxlength="40"
                     value="<?= e($cfg['statusText']) ?>" placeholder="Online now">
            </div>
          </div>

          <div class="form-group">
            <label>Position</label>
            <select name="chatbot_position" class="form-control">
              <option value="bottom-left"  <?= $cfg['position'] === 'bottom-left'  ? 'selected' : '' ?>>Bottom left</option>
              <option value="bottom-right" <?= $cfg['position'] === 'bottom-right' ? 'selected' : '' ?>>Bottom right</option>
            </select>
            <div class="form-hint">
              Bottom right already holds the WhatsApp and back-to-top buttons, so the launcher stacks
              above them there. Left is clear.
            </div>
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label style="display:flex;align-items:center;gap:8px;margin:0">
              <input type="checkbox" name="chatbot_voice" value="1" <?= $cfg['voice'] ? 'checked' : '' ?>>
              Allow voice input
            </label>
            <div class="form-hint">
              Adds a microphone to the message box. Dictation runs in the visitor's own browser —
              no audio reaches this server. Browsers without speech support simply hide the button.
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-microchip" style="color:var(--violet)"></i> Model</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Model</label>
            <select name="chatbot_model" class="form-control">
              <?php foreach (CHATBOT_MODELS as $id => $label): ?>
                <option value="<?= e($id) ?>" <?= $cfg['model'] === $id ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
              <label>Reply length</label>
              <input type="number" name="chatbot_max_tokens" class="form-control" min="80" max="1200" step="20"
                     value="<?= (int)$cfg['maxTokens'] ?>">
              <div class="form-hint">Max tokens.</div>
            </div>
            <div class="form-group">
              <label>Creativity</label>
              <input type="number" name="chatbot_temperature" class="form-control" min="0" max="1" step="0.1"
                     value="<?= e((string)$cfg['temperature']) ?>">
              <div class="form-hint">0 = factual.</div>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Message when switched off</label>
            <textarea name="chatbot_offline_msg" class="form-control" rows="3"><?= e($v('chatbot_offline_msg', $cfg['offlineMsg'])) ?></textarea>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-bottom:10px">
        <i class="fas fa-save"></i> Save settings
      </button>
    </div>
  </div>
</form>

<!-- Separate form: a test must run against what is saved, not against
     unsaved edits sitting in the boxes above. -->
<form method="POST" style="margin-top:-4px">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="test">
  <button type="submit" class="btn btn-secondary btn-sm" <?= $keyFrom === '' ? 'disabled' : '' ?>>
    <i class="fas fa-vial"></i> Send a test question to OpenAI
  </button>
  <span class="form-hint" style="margin-left:8px">
    Uses the saved settings and costs one short API call.
  </span>
</form>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
