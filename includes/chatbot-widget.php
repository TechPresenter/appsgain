<?php
/**
 * AI chatbot widget — the shell, rendered server-side.
 *
 * Included once from includes/layout-footer.php. Nothing renders at all when
 * the chatbot is switched off in Admin, or when no API key is configured:
 * a launcher that can only apologise is worse than no launcher.
 *
 * What reaches the browser is the bot's name, its opening line, the visitor's
 * CSRF token and the endpoint URL. The OpenAI key, the system prompt and the
 * business information stay on the server — the model is called from
 * api/chatbot.php, never from here.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

require_once __DIR__ . '/chatbot.php';

$agChat = chatbotConfig();
if (!$agChat['enabled'] || chatbotApiKey() === '') return;

/* First letter of the bot's name, for the header avatar. */
$agInitial = mb_strtoupper(mb_substr($agChat['name'], 0, 1));

/* Cache-bust on the file's own mtime, so an edited stylesheet or script
   reaches people who already have the old one — a browser will otherwise
   hold a cached copy for as long as its heuristics like, and the widget
   renders at the previous size. Unchanged files keep their cached copy. */
$agVer = static function (string $rel): string {
    $p = ROOT_PATH . '/' . ltrim($rel, '/');
    return ASSETS_URL . '/' . ltrim($rel, '/') . (is_file($p) ? '?v=' . filemtime($p) : '');
};
?>
<link rel="stylesheet" href="<?= e($agVer('css/chatbot.css')) ?>">

<?php
/* Gradient end. Falls back to the primary, so a solid colour stays solid
   rather than fading into a default nobody chose. */
$agC2 = $agChat['color2'] !== '' ? $agChat['color2'] : $agChat['color'];
?>
<div id="ag-chat"
     class="ag-<?= $agChat['position'] === 'bottom-left' ? 'left' : 'right' ?>"
     data-style="<?= e($agChat['style']) ?>"
     data-size="<?= e($agChat['size']) ?>"
     data-head="<?= e($agChat['headerStyle']) ?>"
     style="--ag-chat-c:<?= e($agChat['color']) ?>;--ag-chat-c2:<?= e($agC2) ?>">

  <div class="ag-chat-panel" role="dialog" aria-label="<?= e($agChat['name']) ?>" aria-modal="false">
    <div class="ag-chat-head">
      <?php if ($agChat['avatar'] !== ''): ?>
      <img class="ag-chat-avatar ag-chat-avatar-img" src="<?= e($agChat['avatar']) ?>"
           alt="" width="38" height="38" loading="lazy">
      <?php else: ?>
      <div class="ag-chat-avatar"><?= e($agInitial) ?></div>
      <?php endif; ?>
      <div>
        <div class="ag-chat-title"><?= e($agChat['name']) ?></div>
        <?php if ($agChat['statusText'] !== ''): ?>
        <div class="ag-chat-status"><span class="ag-chat-dot"></span> <?= e($agChat['statusText']) ?></div>
        <?php endif; ?>
      </div>
      <button type="button" class="ag-chat-close" aria-label="Close chat">&times;</button>
    </div>

    <div class="ag-chat-log" role="log" aria-live="polite"></div>
    <div class="ag-chat-chips"></div>

    <form class="ag-chat-form">
      <textarea class="ag-chat-input" rows="1" placeholder="Type your message&hellip;"
                aria-label="Message" maxlength="2000"></textarea>
      <?php /* Dictation runs in the browser's own speech API. Rendered hidden
               and revealed by the script only where that API exists, so a
               browser without it never shows a button that does nothing. */ ?>
      <?php if ($agChat['voice']): ?>
      <button type="button" class="ag-chat-mic" aria-label="Speak your message"
              aria-pressed="false" title="Speak your message" hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 14a3 3 0 003-3V5a3 3 0 00-6 0v6a3 3 0 003 3zm5-3a5 5 0 01-10 0H5a7 7 0 006 6.92V21h2v-3.08A7 7 0 0019 11h-2z"/></svg>
      </button>
      <?php endif; ?>
      <button type="submit" class="ag-chat-send" aria-label="Send message">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
      </button>
    </form>
    <?php /* Credit line. The name comes from settings rather than a literal so
             it follows a rename, and it links home with rel="noopener". */ ?>
    <div class="ag-chat-foot">
      Powered by
      <?php /* Brand name, not company_name — the latter is the full legal
               "… Private Limited", which wraps this line onto two. */ ?>
      <a href="<?= SITE_URL ?>" rel="noopener"><?= e(getSetting('site_name', 'Appsgain Technologies')) ?></a>
    </div>
  </div>

  <button type="button" class="ag-chat-launcher<?= $agChat['launcherLabel'] !== '' ? ' has-label' : '' ?>"
          aria-label="Open chat">
    <?php if ($agChat['launcherIcon'] !== ''): ?>
    <img class="ag-ic-open ag-ic-img" src="<?= e($agChat['launcherIcon']) ?>" alt=""
         width="26" height="26" loading="lazy">
    <?php else: ?>
    <svg class="ag-ic-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2zM7 9h10v2H7V9zm0 4h7v2H7v-2z"/></svg>
    <?php endif; ?>
    <?php if ($agChat['launcherLabel'] !== ''): ?>
    <span class="ag-chat-launcher-label"><?= e($agChat['launcherLabel']) ?></span>
    <?php endif; ?>
    <svg class="ag-ic-close" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <span class="ag-chat-ping"></span>
  </button>
</div>

<script>
window.AG_CHAT = <?= json_encode([
    'endpoint'    => SITE_URL . '/api/chatbot.php',
    'csrf'        => csrfToken(),
    'welcome'     => $agChat['welcome'],
    'placeholder' => 'Type your message…',
    'starters'    => $agChat['starters'],
    'leadCta'     => $agChat['leadCta'],
    'voice'       => $agChat['voice'],
    'voiceLang'   => 'en-IN',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= e($agVer('js/chatbot.js')) ?>" defer></script>
