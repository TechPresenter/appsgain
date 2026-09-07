<?php
/**
 * Appsgain — "Get Enquiry" popup.
 *
 * Four fields, one screen: name, mobile with a searchable country/dial
 * selector, email, and a short requirement. Posts to api/enquiry.php,
 * which writes to `leads` — so submissions land in Admin -> Enquiries
 * next to every other lead, with no parallel table to maintain.
 *
 * Opens from the floating button, from any [data-enquiry] element, or
 * once per session after a short dwell. Never auto-opens twice, and
 * never on the contact page where a fuller form already exists.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

/* Dial codes. Kept short and practical rather than exhaustive — the
   field accepts any code the list offers plus free search. */
$_agDial = [
    ['IN', '+91',  'India',          '🇮🇳'],
    ['US', '+1',   'United States',  '🇺🇸'],
    ['GB', '+44',  'United Kingdom', '🇬🇧'],
    ['AE', '+971', 'UAE',            '🇦🇪'],
    ['AU', '+61',  'Australia',      '🇦🇺'],
    ['CA', '+1',   'Canada',         '🇨🇦'],
    ['SG', '+65',  'Singapore',      '🇸🇬'],
    ['DE', '+49',  'Germany',        '🇩🇪'],
    ['FR', '+33',  'France',         '🇫🇷'],
    ['NL', '+31',  'Netherlands',    '🇳🇱'],
    ['ES', '+34',  'Spain',          '🇪🇸'],
    ['IT', '+39',  'Italy',          '🇮🇹'],
    ['CH', '+41',  'Switzerland',    '🇨🇭'],
    ['SE', '+46',  'Sweden',         '🇸🇪'],
    ['IE', '+353', 'Ireland',        '🇮🇪'],
    ['NZ', '+64',  'New Zealand',    '🇳🇿'],
    ['ZA', '+27',  'South Africa',   '🇿🇦'],
    ['SA', '+966', 'Saudi Arabia',   '🇸🇦'],
    ['QA', '+974', 'Qatar',          '🇶🇦'],
    ['KW', '+965', 'Kuwait',         '🇰🇼'],
    ['OM', '+968', 'Oman',           '🇴🇲'],
    ['BH', '+973', 'Bahrain',        '🇧🇭'],
    ['MY', '+60',  'Malaysia',       '🇲🇾'],
    ['ID', '+62',  'Indonesia',      '🇮🇩'],
    ['PH', '+63',  'Philippines',    '🇵🇭'],
    ['TH', '+66',  'Thailand',       '🇹🇭'],
    ['VN', '+84',  'Vietnam',        '🇻🇳'],
    ['JP', '+81',  'Japan',          '🇯🇵'],
    ['KR', '+82',  'South Korea',    '🇰🇷'],
    ['CN', '+86',  'China',          '🇨🇳'],
    ['HK', '+852', 'Hong Kong',      '🇭🇰'],
    ['BD', '+880', 'Bangladesh',     '🇧🇩'],
    ['LK', '+94',  'Sri Lanka',      '🇱🇰'],
    ['NP', '+977', 'Nepal',          '🇳🇵'],
    ['PK', '+92',  'Pakistan',       '🇵🇰'],
    ['BR', '+55',  'Brazil',         '🇧🇷'],
    ['MX', '+52',  'Mexico',         '🇲🇽'],
    ['NG', '+234', 'Nigeria',        '🇳🇬'],
    ['KE', '+254', 'Kenya',          '🇰🇪'],
    ['EG', '+20',  'Egypt',          '🇪🇬'],
];
$_agAutoOpen = ($activePage ?? '') !== 'contact';
?>

<!-- ══ Get Enquiry — floating trigger ══ -->
<button type="button" class="eqx-fab" id="eqxFab" aria-haspopup="dialog" aria-controls="eqxModal">
  <i class="fas fa-comment-dots" aria-hidden="true"></i>
  <span>Get Enquiry</span>
</button>

<!-- ══ Get Enquiry — dialog ══ -->
<div class="eqx" id="eqxModal" role="dialog" aria-modal="true"
     aria-labelledby="eqxTitle" aria-describedby="eqxLede" hidden>
  <div class="eqx-scrim" data-eqx-close></div>

  <div class="eqx-panel" role="document">
    <button type="button" class="eqx-x" data-eqx-close aria-label="Close enquiry form">
      <i class="fas fa-xmark" aria-hidden="true"></i>
    </button>

    <div class="eqx-head">
      <span class="eqx-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Free consultation</span>
      <h2 class="eqx-title" id="eqxTitle">Tell us what you need</h2>
      <p class="eqx-lede" id="eqxLede">Share a few details and our team will get back to you within 24&nbsp;hours.</p>
    </div>

    <form class="eqx-form" id="eqxForm" novalidate>
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <input type="hidden" name="page" value="<?= e($activePage ?? '') ?>">
      <input type="hidden" name="t" id="eqxT" value="0">
      <!-- honeypot: off-screen, never announced, never tabbable -->
      <div class="eqx-hp" aria-hidden="true">
        <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="eqx-field">
        <label for="eqxName">Full name <span class="eqx-req" aria-hidden="true">*</span></label>
        <div class="eqx-input">
          <i class="fas fa-user" aria-hidden="true"></i>
          <input type="text" id="eqxName" name="name" placeholder="Your name"
                 autocomplete="name" required maxlength="120">
        </div>
        <p class="eqx-err" data-for="name"></p>
      </div>

      <div class="eqx-field">
        <label for="eqxPhone">Mobile number <span class="eqx-req" aria-hidden="true">*</span></label>
        <div class="eqx-phone">
          <!-- Country / dial picker -->
          <div class="eqx-cc" id="eqxCC">
            <button type="button" class="eqx-cc-btn" id="eqxCCBtn"
                    aria-haspopup="listbox" aria-expanded="false" aria-label="Select country code">
              <span class="eqx-cc-flag" id="eqxCCFlag">🇮🇳</span>
              <span class="eqx-cc-code" id="eqxCCCode">+91</span>
              <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="eqx-cc-pop" id="eqxCCPop" role="dialog" aria-label="Country codes" hidden>
              <div class="eqx-cc-search">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input type="text" id="eqxCCSearch" placeholder="Search country or code"
                       autocomplete="off" aria-label="Search countries">
              </div>
              <ul class="eqx-cc-list" id="eqxCCList" role="listbox" aria-label="Countries" tabindex="-1">
                <?php foreach ($_agDial as [$iso, $dial, $label, $flag]): ?>
                <li role="option" tabindex="-1"
                    data-iso="<?= e($iso) ?>" data-dial="<?= e($dial) ?>"
                    data-flag="<?= e($flag) ?>" data-label="<?= e($label) ?>"
                    aria-selected="<?= $iso === 'IN' ? 'true' : 'false' ?>"
                    class="<?= $iso === 'IN' ? 'is-on' : '' ?>">
                  <span class="eqx-cc-f"><?= $flag ?></span>
                  <span class="eqx-cc-n"><?= e($label) ?></span>
                  <span class="eqx-cc-d"><?= e($dial) ?></span>
                </li>
                <?php endforeach; ?>
                <li class="eqx-cc-none" hidden>No match</li>
              </ul>
            </div>
            <input type="hidden" name="dial"    id="eqxDial"    value="+91">
            <input type="hidden" name="country" id="eqxCountry" value="IN">
          </div>

          <div class="eqx-input eqx-input--tel">
            <input type="tel" id="eqxPhone" name="phone" placeholder="98765 43210"
                   autocomplete="tel-national" inputmode="numeric" required maxlength="15">
          </div>
        </div>
        <p class="eqx-err" data-for="phone"></p>
      </div>

      <div class="eqx-field">
        <label for="eqxEmail">Email address <span class="eqx-req" aria-hidden="true">*</span></label>
        <div class="eqx-input">
          <i class="fas fa-envelope" aria-hidden="true"></i>
          <input type="email" id="eqxEmail" name="email" placeholder="you@company.com"
                 autocomplete="email" inputmode="email" required maxlength="200">
        </div>
        <p class="eqx-err" data-for="email"></p>
      </div>

      <div class="eqx-field">
        <label for="eqxNeed">What do you need? <span class="eqx-req" aria-hidden="true">*</span></label>
        <div class="eqx-input eqx-input--area">
          <textarea id="eqxNeed" name="requirement" rows="3" required maxlength="1000"
                    placeholder="e.g. A mobile app for our delivery business"></textarea>
        </div>
        <div class="eqx-meta">
          <p class="eqx-err" data-for="requirement"></p>
          <span class="eqx-count"><span id="eqxCount">0</span>/1000</span>
        </div>
      </div>

      <button type="submit" class="eqx-submit" id="eqxSubmit">
        <span class="eqx-submit-label">Send Enquiry</span>
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
      </button>

      <p class="eqx-note">
        <i class="fas fa-lock" aria-hidden="true"></i>
        Your details stay private. We never share them.
      </p>
    </form>

    <!-- Success takes over the panel rather than opening a second dialog -->
    <div class="eqx-done" id="eqxDone" hidden>
      <span class="eqx-done-mark" aria-hidden="true">
        <svg viewBox="0 0 52 52"><circle class="eqx-done-c" cx="26" cy="26" r="23" fill="none"/>
        <path class="eqx-done-t" fill="none" d="M14 27l8 8 16-16"/></svg>
      </span>
      <h3>Enquiry received</h3>
      <p id="eqxDoneMsg">Thank you! Our team will contact you within 24 hours.</p>
      <button type="button" class="eqx-done-btn" data-eqx-close>Close</button>
    </div>
  </div>
</div>

<style>
/* ══════════════════════════════════════════════════════
   GET ENQUIRY POPUP
   Light theme, brand tokens. White text sits only on the
   deep half of the brand ramp (#D000A8 -> #6A00FF), which
   runs 4.94:1 to 6.87:1; the orange end is 2.36:1 and
   cannot carry a label.
   ══════════════════════════════════════════════════════ */
.eqx{
  --e-ink:#11162D; --e-body:#5E6475; --e-mute:#7E8496;
  --e-line:#E7E9F0; --e-soft:#F7F8FC;
  --e-brand:#6A00FF; --e-solid:#8B00E0;
  --e-grad:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  --e-ease:cubic-bezier(.22,1,.36,1);
  /* Above the WhatsApp float, which carries z-index 9998 !important. */
  position:fixed; inset:0; z-index:10000;
  display:flex; align-items:center; justify-content:center;
  padding:20px;
  font-family:var(--font-body),system-ui,sans-serif;
}
.eqx[hidden]{ display:none; }

.eqx-scrim{
  position:absolute; inset:0;
  background:rgba(10,14,32,.55);
  -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px);
  opacity:0; transition:opacity .28s var(--e-ease);
}
.eqx.is-in .eqx-scrim{ opacity:1; }

.eqx-panel{
  position:relative; z-index:1;
  width:min(440px, 100%);
  max-height:min(88vh, 760px); overflow-y:auto;
  padding:26px 26px 22px;
  background:#fff; border-radius:20px;
  box-shadow:0 24px 70px rgba(10,14,32,.28), 0 2px 6px rgba(10,14,32,.08);
  opacity:0; transform:translateY(18px) scale(.96);
  transition:opacity .34s var(--e-ease), transform .34s var(--e-ease);
}
.eqx.is-in .eqx-panel{ opacity:1; transform:none; }
.eqx.is-out .eqx-panel{ opacity:0; transform:translateY(10px) scale(.98); }

/* A hairline of brand across the top edge */
.eqx-panel::before{
  content:""; position:absolute; left:0; right:0; top:0; height:3px;
  border-radius:20px 20px 0 0;
  background-color:var(--e-solid); background-image:var(--e-grad);
}

.eqx-x{
  position:absolute; top:14px; right:14px;
  display:grid; place-items:center;
  width:32px; height:32px; border-radius:9px; cursor:pointer;
  background:var(--e-soft); border:1px solid var(--e-line);
  color:var(--e-body); font-size:14px;
  transition:background .18s ease, color .18s ease, border-color .18s ease;
}
.eqx-x:hover{ background:#fff; color:var(--e-brand); border-color:rgba(106,0,255,.3); }
.eqx-x:focus-visible{ outline:2px solid var(--e-brand); outline-offset:2px; }

/* ── Head ── */
.eqx-head{ margin-bottom:18px; padding-right:34px; }
.eqx-badge{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 11px; margin-bottom:11px; border-radius:999px;
  font-size:10.5px; font-weight:700; letter-spacing:.11em; text-transform:uppercase;
  color:var(--e-brand); background:rgba(106,0,255,.07);
  border:1px solid rgba(106,0,255,.16);
}
.eqx-badge i{ font-size:9px; }
.eqx-title{
  font-family:var(--font-main),sans-serif;
  font-size:21px; font-weight:800; letter-spacing:-.02em;
  color:var(--e-ink); margin:0 0 6px; line-height:1.25;
}
.eqx-lede{ font-size:13.5px; line-height:1.6; color:var(--e-body); margin:0; }

/* ── Fields ── */
.eqx-hp{ position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
.eqx-field{ margin-bottom:13px; }
.eqx-field > label{
  display:block; margin-bottom:6px;
  font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  color:var(--e-ink);
}
.eqx-req{ color:#DC2626; }

.eqx-input{
  position:relative; display:flex; align-items:center;
  background:var(--e-soft); border:1px solid var(--e-line);
  border-radius:11px;
  transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.eqx-input > i{
  flex:0 0 auto; width:38px; text-align:center;
  font-size:13px; color:var(--e-mute); pointer-events:none;
}
.eqx-input input,
.eqx-input textarea{
  flex:1 1 auto; min-width:0;
  width:100%; padding:12px 14px 12px 0;
  background:none; border:0; outline:none;
  font-family:inherit; font-size:14.5px; color:var(--e-ink);
}
.eqx-input > i + input{ padding-left:0; }
.eqx-input input:only-child,
.eqx-input textarea{ padding-left:14px; }
.eqx-input textarea{ resize:vertical; min-height:76px; line-height:1.6; padding-top:11px; }
.eqx-input input::placeholder,
.eqx-input textarea::placeholder{ color:#9AA0B4; }
.eqx-input:focus-within{
  background:#fff; border-color:var(--e-brand);
  box-shadow:0 0 0 3px rgba(106,0,255,.10);
}
.eqx-input.is-bad{ border-color:#DC2626; background:rgba(220,38,38,.03); }
.eqx-input.is-bad:focus-within{ box-shadow:0 0 0 3px rgba(220,38,38,.10); }

.eqx-err{
  display:none; margin:5px 0 0;
  font-size:12px; font-weight:500; color:#DC2626;
}
.eqx-err.is-on{ display:block; }
.eqx-meta{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.eqx-meta .eqx-err{ flex:1; }
.eqx-count{ flex:0 0 auto; margin-top:5px; font-size:11.5px; color:var(--e-mute); }

/* ── Phone row ── */
.eqx-phone{ display:flex; gap:8px; align-items:stretch; }
.eqx-input--tel{ flex:1 1 auto; min-width:0; }
.eqx-input--tel input{ padding-left:14px; }

.eqx-cc{ position:relative; flex:0 0 auto; }
.eqx-cc-btn{
  display:flex; align-items:center; gap:6px;
  height:100%; min-height:45px; padding:0 11px;
  background:var(--e-soft); border:1px solid var(--e-line);
  border-radius:11px; cursor:pointer;
  font-family:inherit; font-size:14px; color:var(--e-ink);
  transition:border-color .18s ease, background .18s ease;
}
.eqx-cc-btn:hover{ background:#fff; border-color:rgba(106,0,255,.28); }
.eqx-cc-btn:focus-visible{ outline:2px solid var(--e-brand); outline-offset:2px; }
.eqx-cc-btn i{ font-size:9px; color:var(--e-mute); transition:transform .2s ease; }
.eqx-cc-btn[aria-expanded="true"] i{ transform:rotate(180deg); }
.eqx-cc-flag{ font-size:16px; line-height:1; }
.eqx-cc-code{ font-weight:600; font-variant-numeric:tabular-nums; }

.eqx-cc-pop{
  position:absolute; z-index:5; top:calc(100% + 6px); left:0;
  width:270px; max-width:78vw;
  background:#fff; border:1px solid var(--e-line); border-radius:13px;
  box-shadow:0 14px 40px rgba(10,14,32,.16);
  overflow:hidden;
  animation:eqxPop .18s var(--e-ease) both;
}
@keyframes eqxPop{ from{ opacity:0; transform:translateY(-6px) scale(.98); } to{ opacity:1; transform:none; } }
.eqx-cc-search{
  display:flex; align-items:center; gap:8px;
  padding:9px 12px; border-bottom:1px solid var(--e-line);
}
.eqx-cc-search i{ font-size:12px; color:var(--e-mute); }
.eqx-cc-search input{
  flex:1; border:0; outline:none; background:none;
  font-family:inherit; font-size:13.5px; color:var(--e-ink);
}
.eqx-cc-list{
  list-style:none; margin:0; padding:5px;
  max-height:220px; overflow-y:auto;
  scrollbar-width:thin;
}
.eqx-cc-list li{
  display:flex; align-items:center; gap:10px;
  padding:8px 10px; border-radius:8px; cursor:pointer;
  font-size:13.5px; color:var(--e-ink);
  transition:background .14s ease;
}
.eqx-cc-list li:hover,
.eqx-cc-list li.is-cursor{ background:var(--e-soft); }
.eqx-cc-list li.is-on{ background:rgba(106,0,255,.07); color:var(--e-brand); font-weight:600; }
.eqx-cc-list li[hidden]{ display:none; }
.eqx-cc-f{ font-size:16px; line-height:1; }
.eqx-cc-n{ flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.eqx-cc-d{ color:var(--e-mute); font-variant-numeric:tabular-nums; }
.eqx-cc-none{ justify-content:center; color:var(--e-mute); cursor:default; }

/* ── Submit ── */
.eqx-submit{
  display:flex; align-items:center; justify-content:center; gap:9px;
  width:100%; height:48px; margin-top:4px;
  border:0; border-radius:12px; cursor:pointer;
  background-color:var(--e-solid); background-image:var(--e-grad);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-family:inherit; font-size:15px; font-weight:600; line-height:1;
  box-shadow:0 6px 18px rgba(106,0,255,.24);
  transition:background-position .3s ease, transform .2s ease, box-shadow .2s ease;
}
.eqx-submit:hover:not(:disabled){
  background-position:100% 50%; transform:translateY(-2px);
  box-shadow:0 10px 26px rgba(106,0,255,.3);
}
.eqx-submit:focus-visible{ outline:2px solid var(--e-brand); outline-offset:3px; }
.eqx-submit:disabled{ opacity:.72; cursor:progress; transform:none; }
.eqx-submit i{ font-size:12px; }
@media (forced-colors: active){
  .eqx-submit{ border:2px solid ButtonText; background:ButtonFace; color:ButtonText; }
}

.eqx-note{
  display:flex; align-items:center; justify-content:center; gap:7px;
  margin:11px 0 0; font-size:11.5px; color:var(--e-mute);
}
.eqx-note i{ font-size:10px; }

/* ── Success ── */
.eqx-done{ padding:16px 4px 8px; text-align:center; animation:eqxFade .3s var(--e-ease) both; }
@keyframes eqxFade{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:none; } }
.eqx-done-mark{ display:block; width:66px; height:66px; margin:0 auto 16px; }
.eqx-done-mark svg{ width:100%; height:100%; }
.eqx-done-c{ stroke:#0E9F6E; stroke-width:2.5; stroke-dasharray:145; stroke-dashoffset:145;
  animation:eqxRing .5s var(--e-ease) forwards; }
.eqx-done-t{ stroke:#0E9F6E; stroke-width:3.5; stroke-linecap:round; stroke-linejoin:round;
  stroke-dasharray:36; stroke-dashoffset:36;
  animation:eqxTick .3s var(--e-ease) .42s forwards; }
@keyframes eqxRing{ to{ stroke-dashoffset:0; } }
@keyframes eqxTick{ to{ stroke-dashoffset:0; } }
.eqx-done h3{
  font-family:var(--font-main),sans-serif;
  font-size:19px; font-weight:800; color:var(--e-ink); margin:0 0 7px;
}
.eqx-done p{ font-size:13.5px; line-height:1.6; color:var(--e-body); margin:0 0 20px; }
.eqx-done-btn{
  height:42px; padding:0 26px; border-radius:11px; cursor:pointer;
  background:#fff; border:1px solid var(--e-line); color:var(--e-ink);
  font-family:inherit; font-size:14px; font-weight:600;
  transition:border-color .18s ease, color .18s ease;
}
.eqx-done-btn:hover{ border-color:var(--e-brand); color:var(--e-brand); }

/* ── Floating trigger ── */
.eqx-fab{
  /* stacks above the WhatsApp float (right:22px, bottom:90px, 52px tall) */
  position:fixed; right:22px; bottom:154px; z-index:60;
  display:inline-flex; align-items:center; gap:9px;
  height:46px; padding:0 18px; border:0; border-radius:999px; cursor:pointer;
  background-color:#8B00E0; background-image:linear-gradient(120deg,#D000A8,#9D00D3,#6A00FF);
  color:#fff; font-family:var(--font-body),system-ui,sans-serif;
  font-size:14px; font-weight:600; line-height:1;
  box-shadow:0 6px 20px rgba(106,0,255,.34);
  transition:transform .2s var(--e-ease), box-shadow .2s ease;
}
.eqx-fab:hover{ transform:translateY(-2px); box-shadow:0 12px 28px rgba(106,0,255,.42); }
.eqx-fab:focus-visible{ outline:2px solid #6A00FF; outline-offset:3px; }
.eqx-fab i{ font-size:15px; }
/* A single soft pulse on load, then it stops — no permanent throb */
@keyframes eqxPulse{
  0%{ box-shadow:0 6px 20px rgba(106,0,255,.34), 0 0 0 0 rgba(106,0,255,.42); }
  70%{ box-shadow:0 6px 20px rgba(106,0,255,.34), 0 0 0 16px rgba(106,0,255,0); }
  100%{ box-shadow:0 6px 20px rgba(106,0,255,.34), 0 0 0 0 rgba(106,0,255,0); }
}
.eqx-fab.is-calling{ animation:eqxPulse 2.2s ease-out 3; }

/* While the cookie banner is on screen it spans the full width on a phone.
   Both floats step above it so neither is covered or blocked. */
@media (max-width:560px){
  body.ag-cookie-open .eqx-fab{
    bottom:calc(140px + var(--ag-cookie-h, 0px) + 12px);
  }
  body.ag-cookie-open .whatsapp-float{
    bottom:calc(76px + var(--ag-cookie-h, 0px) + 12px) !important;
  }
  body.ag-cookie-open .stt-btn{
    bottom:calc(20px + var(--ag-cookie-h, 0px) + 12px) !important;
  }
}
.eqx-fab, .whatsapp-float, .stt-btn{ transition:bottom .3s cubic-bezier(.4,0,.2,1); }

@media (max-width:560px){
  .eqx{ padding:0; align-items:flex-end; }
  .eqx-panel{
    width:100%; max-width:none; max-height:92vh;
    border-radius:20px 20px 0 0;
    padding:22px 18px 18px;
    transform:translateY(100%);
  }
  .eqx.is-in .eqx-panel{ transform:none; }
  .eqx.is-out .eqx-panel{ transform:translateY(60%); }
  .eqx-fab{ right:16px; bottom:140px; height:44px; padding:0 15px; font-size:13.5px; }
  .eqx-cc-pop{ width:min(300px, 84vw); }
}
@media (prefers-reduced-motion:reduce){
  .eqx-scrim, .eqx-panel, .eqx-cc-pop, .eqx-done,
  .eqx-done-c, .eqx-done-t, .eqx-fab{ animation:none !important; transition:none !important; }
  .eqx-panel{ transform:none; }
  .eqx-done-c, .eqx-done-t{ stroke-dashoffset:0; }
}
</style>

<script>
(function () {
  'use strict';
  var modal = document.getElementById('eqxModal');
  var fab   = document.getElementById('eqxFab');
  if (!modal || !fab) return;

  var panel  = modal.querySelector('.eqx-panel');
  var form   = document.getElementById('eqxForm');
  var done   = document.getElementById('eqxDone');
  var btn    = document.getElementById('eqxSubmit');
  var label  = btn.querySelector('.eqx-submit-label');
  var lastFocus = null;

  var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),' +
                  'textarea:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])';

  /* ── Open / close ─────────────────────────────────── */
  function open() {
    if (!modal.hidden) return;
    lastFocus = document.activeElement;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    document.getElementById('eqxT').value = String(Math.round(performance.now() ?
      performance.timeOrigin + performance.now() : Date.now()));
    /* next frame so the transition has a start state to animate from */
    requestAnimationFrame(function () { modal.classList.add('is-in'); });
    setTimeout(function () {
      var first = document.getElementById('eqxName');
      if (first && window.matchMedia('(hover:hover) and (pointer:fine)').matches) first.focus();
    }, 220);
    fab.classList.remove('is-calling');
  }

  function close() {
    if (modal.hidden) return;
    modal.classList.remove('is-in');
    modal.classList.add('is-out');
    setTimeout(function () {
      modal.hidden = true;
      modal.classList.remove('is-out');
      document.body.style.overflow = '';
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }, 260);
  }

  fab.addEventListener('click', open);
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-eqx-close]')) { e.preventDefault(); close(); return; }
    var t = e.target.closest('[data-enquiry]');
    if (t) { e.preventDefault(); open(); }
  });
  document.addEventListener('keydown', function (e) {
    if (modal.hidden) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;
    /* keep focus inside the dialog */
    var items = Array.prototype.filter.call(
      panel.querySelectorAll(FOCUSABLE),
      function (el) { return el.offsetParent !== null; });
    if (!items.length) return;
    var first = items[0], last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  /* One gentle invitation per session, never on the contact page */
  <?php if ($_agAutoOpen): ?>
  try {
    if (!sessionStorage.getItem('ag_eqx_seen')) {
      setTimeout(function () { fab.classList.add('is-calling'); }, 6000);
      sessionStorage.setItem('ag_eqx_seen', '1');
    }
  } catch (err) {}
  <?php endif; ?>

  /* ── Country picker ───────────────────────────────── */
  var cc      = document.getElementById('eqxCC');
  var ccBtn   = document.getElementById('eqxCCBtn');
  var ccPop   = document.getElementById('eqxCCPop');
  var ccList  = document.getElementById('eqxCCList');
  var ccSrch  = document.getElementById('eqxCCSearch');
  var ccNone  = ccList.querySelector('.eqx-cc-none');
  var options = Array.prototype.slice.call(ccList.querySelectorAll('li[data-iso]'));
  var cursor  = -1;

  function ccOpen() {
    ccPop.hidden = false;
    ccBtn.setAttribute('aria-expanded', 'true');
    ccSrch.value = '';
    filter('');
    cursor = options.findIndex(function (o) { return o.classList.contains('is-on'); });
    setTimeout(function () { ccSrch.focus(); }, 20);
  }
  function ccClose() {
    ccPop.hidden = true;
    ccBtn.setAttribute('aria-expanded', 'false');
    options.forEach(function (o) { o.classList.remove('is-cursor'); });
  }
  function filter(q) {
    q = q.trim().toLowerCase();
    var shown = 0;
    options.forEach(function (o) {
      var hit = !q ||
        o.dataset.label.toLowerCase().indexOf(q) > -1 ||
        o.dataset.dial.indexOf(q.replace(/^\+/, '')) > -1 ||
        o.dataset.iso.toLowerCase().indexOf(q) === 0;
      o.hidden = !hit;
      if (hit) shown++;
    });
    ccNone.hidden = shown > 0;
  }
  function pick(o) {
    options.forEach(function (x) {
      x.classList.remove('is-on');
      x.setAttribute('aria-selected', 'false');
    });
    o.classList.add('is-on');
    o.setAttribute('aria-selected', 'true');
    document.getElementById('eqxCCFlag').textContent = o.dataset.flag;
    document.getElementById('eqxCCCode').textContent = o.dataset.dial;
    document.getElementById('eqxDial').value    = o.dataset.dial;
    document.getElementById('eqxCountry').value = o.dataset.iso;
    ccClose();
    ccBtn.focus();
  }

  ccBtn.addEventListener('click', function () {
    ccPop.hidden ? ccOpen() : ccClose();
  });
  ccList.addEventListener('click', function (e) {
    var o = e.target.closest('li[data-iso]');
    if (o) pick(o);
  });
  ccSrch.addEventListener('input', function () { filter(this.value); cursor = -1; });
  ccSrch.addEventListener('keydown', function (e) {
    var vis = options.filter(function (o) { return !o.hidden; });
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (!vis.length) return;
      cursor = e.key === 'ArrowDown'
        ? Math.min(cursor + 1, vis.length - 1)
        : Math.max(cursor - 1, 0);
      options.forEach(function (o) { o.classList.remove('is-cursor'); });
      vis[cursor].classList.add('is-cursor');
      vis[cursor].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (vis[cursor]) pick(vis[cursor]);
      else if (vis.length === 1) pick(vis[0]);
    } else if (e.key === 'Escape') {
      e.preventDefault(); ccClose(); ccBtn.focus();
    }
  });
  document.addEventListener('click', function (e) {
    if (!ccPop.hidden && !cc.contains(e.target)) ccClose();
  });

  /* ── Character counter ────────────────────────────── */
  var need  = document.getElementById('eqxNeed');
  var count = document.getElementById('eqxCount');
  need.addEventListener('input', function () { count.textContent = this.value.length; });

  /* ── Validation + submit ──────────────────────────── */
  function showError(field, msg) {
    var p = form.querySelector('.eqx-err[data-for="' + field + '"]');
    if (p) { p.textContent = msg; p.classList.add('is-on'); }
    var input = form.querySelector('[name="' + (field === 'requirement' ? 'requirement' : field) + '"]');
    if (input) {
      var box = input.closest('.eqx-input');
      if (box) box.classList.add('is-bad');
      input.setAttribute('aria-invalid', 'true');
    }
  }
  function clearErrors() {
    form.querySelectorAll('.eqx-err').forEach(function (p) { p.textContent = ''; p.classList.remove('is-on'); });
    form.querySelectorAll('.eqx-input').forEach(function (b) { b.classList.remove('is-bad'); });
    form.querySelectorAll('[aria-invalid]').forEach(function (i) { i.removeAttribute('aria-invalid'); });
  }
  form.addEventListener('input', function (e) {
    var box = e.target.closest('.eqx-input');
    if (box && box.classList.contains('is-bad')) {
      box.classList.remove('is-bad');
      var p = form.querySelector('.eqx-err[data-for="' + e.target.name + '"]');
      if (p) { p.textContent = ''; p.classList.remove('is-on'); }
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    var name  = form.name.value.trim();
    var email = form.email.value.trim();
    var phone = form.phone.value.replace(/\D/g, '');
    var req   = form.requirement.value.trim();
    var bad   = null;

    if (name.length < 2)  { showError('name', 'Please enter your name.'); bad = bad || 'name'; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) { showError('email', 'Please enter a valid email address.'); bad = bad || 'email'; }
    if (phone.length < 6 || phone.length > 15) { showError('phone', 'Please enter a valid mobile number.'); bad = bad || 'phone'; }
    if (req.length < 5)   { showError('requirement', 'Tell us briefly what you need.'); bad = bad || 'requirement'; }
    if (bad) { var el = form.querySelector('[name="' + bad + '"]'); if (el) el.focus(); return; }

    btn.disabled = true;
    label.textContent = 'Sending…';

    var payload = {};
    new FormData(form).forEach(function (v, k) { payload[k] = v; });

    fetch('<?= SITE_URL ?>/api/enquiry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': form['<?= CSRF_TOKEN_NAME ?>'].value },
      body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Unexpected response.' }; }); })
    .then(function (d) {
      btn.disabled = false;
      label.textContent = 'Send Enquiry';
      if (d.ok || d.success) {
        form.hidden = true;
        document.querySelector('.eqx-head').hidden = true;
        document.getElementById('eqxDoneMsg').textContent = d.message || 'Thank you! Our team will contact you within 24 hours.';
        done.hidden = false;
        done.querySelector('.eqx-done-btn').focus();
      } else if (d.field) {
        showError(d.field, d.message);
        var el = form.querySelector('[name="' + d.field + '"]');
        if (el) el.focus();
      } else {
        showError('requirement', d.message || 'Something went wrong. Please try again.');
      }
    })
    .catch(function () {
      btn.disabled = false;
      label.textContent = 'Send Enquiry';
      showError('requirement', 'Network error. Please check your connection and try again.');
    });
  });
})();
</script>
