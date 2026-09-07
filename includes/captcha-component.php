<?php
/**
 * Appsgain — CAPTCHA Component
 * Reusable CAPTCHA block for all forms
 * Usage: require __DIR__ . '/captcha-component.php';
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }
/* Auto-start session if needed */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
/* Pre-generate session code when component is loaded */
if (empty($_SESSION['captcha_code']) || (isset($_SESSION['captcha_expires']) && $_SESSION['captcha_expires'] < time())) {
    /* Will be generated when image is requested */
    $_SESSION['captcha_code']    = '';
    $_SESSION['captcha_expires'] = time() + 300;
}
$captchaId = 'captcha_' . uniqid(); // unique id per form instance
?>
<div class="captcha-wrap" id="<?= $captchaId ?>-wrap">
  <div class="captcha-header">
    <i class="fas fa-shield-alt captcha-shield-icon"></i>
    <label class="captcha-title">CAPTCHA Verification <span class="captcha-required">*</span></label>
  </div>

  <div class="captcha-body">
    <!-- Image display -->
    <div class="captcha-img-box">
      <img id="<?= $captchaId ?>-img"
           src="<?= SITE_URL ?>/api/captcha.php?t=<?= time() ?>"
           alt="CAPTCHA verification image"
           class="captcha-img"
           loading="eager">
    </div>

    <!-- Refresh button -->
    <button type="button"
            class="captcha-refresh-btn"
            id="<?= $captchaId ?>-refresh"
            aria-label="Refresh CAPTCHA"
            onclick="refreshCaptcha('<?= $captchaId ?>')">
      <i class="fas fa-sync-alt"></i> Refresh
    </button>
  </div>

  <!-- Input field -->
  <div class="captcha-input-wrap">
    <input type="text"
           name="captcha_code"
           id="<?= $captchaId ?>-input"
           class="captcha-input"
           placeholder="Enter 6-character code"
           maxlength="6"
           autocomplete="off"
           autocorrect="off"
           autocapitalize="none"
           spellcheck="false"
           required
           inputmode="text"
           style="text-transform:none!important;-webkit-text-transform:none!important;"
           aria-label="Enter the CAPTCHA code shown above">
  </div>

  <div class="captcha-hint">
    Type the characters shown in the image above.
    <a href="#" class="captcha-cantread" onclick="refreshCaptcha('<?= $captchaId ?>');return false;">Can't read it?</a>
  </div>

  <div class="captcha-error" id="<?= $captchaId ?>-error" style="display:none"></div>
</div>
