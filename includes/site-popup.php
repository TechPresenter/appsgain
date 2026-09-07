<?php
/**
 * Appsgain — timed site popup.
 *
 * Reads `popups`: an admin picks which pages it shows on, how many
 * seconds to wait, and whether a visitor sees it once or every visit.
 *
 * Only one popup renders — the first active row whose date window is
 * open and whose page filter matches. Stacking modals on a visitor is
 * never the right answer.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

$_pgKey = $activePage ?? '';

$_pop = dbFetchRow(
    "SELECT * FROM popups
     WHERE is_active = 1
       AND (start_date IS NULL OR start_date = '0000-00-00' OR start_date <= CURDATE())
       AND (end_date   IS NULL OR end_date   = '0000-00-00' OR end_date   >= CURDATE())
     ORDER BY id ASC LIMIT 1"
);
if (!$_pop) return;

/* display_on: 'all', or a comma list of page keys such as "home,contact" */
$_on = trim((string)($_pop['display_on'] ?? 'all'));
if ($_on !== '' && strtolower($_on) !== 'all') {
    $_allowed = array_filter(array_map('trim', explode(',', strtolower($_on))));
    if ($_allowed && !in_array(strtolower($_pgKey), $_allowed, true)) return;
}

$_delay  = max(0, min(120, (int)($_pop['delay_seconds'] ?? 5)));
$_once   = (int)($_pop['show_once'] ?? 1) === 1;
$_img    = trim((string)($_pop['image'] ?? ''));
$_imgUrl = $_img === '' ? '' : (str_starts_with($_img, 'http') ? $_img : UPLOADS_URL . '/' . ltrim($_img, '/'));
$_btnT   = trim((string)($_pop['button_text'] ?? ''));
$_btnU   = trim((string)($_pop['button_url'] ?? ''));
if ($_btnU !== '' && !preg_match('~^(https?:|mailto:|tel:|#|/)~i', $_btnU)) {
    $_btnU = rtrim(SITE_URL, '/') . '/' . ltrim($_btnU, '/');
} elseif ($_btnU !== '' && str_starts_with($_btnU, '/')) {
    $_btnU = rtrim(SITE_URL, '/') . $_btnU;
}
/* A stable key so editing the popup re-shows it to people who dismissed the old one */
$_popKey = 'ag_pop_' . (int)$_pop['id'] . '_' . substr(md5((string)$_pop['title'] . $_pop['content']), 0, 8);
?>

<div class="spx" id="spx" role="dialog" aria-modal="true"
     aria-labelledby="spxTitle" hidden
     data-delay="<?= $_delay ?>" data-key="<?= e($_popKey) ?>" data-once="<?= $_once ? '1' : '0' ?>">
  <div class="spx-scrim" data-spx-close></div>

  <div class="spx-box" role="document">
    <button type="button" class="spx-x" data-spx-close aria-label="Close">
      <i class="fas fa-xmark" aria-hidden="true"></i>
    </button>

    <?php if ($_imgUrl !== ''): ?>
    <div class="spx-media">
      <img src="<?= e($_imgUrl) ?>" alt="" loading="lazy" decoding="async">
    </div>
    <?php endif; ?>

    <div class="spx-body">
      <?php if (!empty($_pop['title'])): ?>
      <h2 class="spx-title" id="spxTitle"><?= e($_pop['title']) ?></h2>
      <?php endif; ?>

      <?php if (!empty($_pop['content'])): ?>
      <div class="spx-text"><?= $_pop['content'] /* trusted admin HTML */ ?></div>
      <?php endif; ?>

      <?php if ($_btnT !== '' && $_btnU !== ''): ?>
      <a class="spx-cta" href="<?= e($_btnU) ?>"><?= e($_btnT) ?>
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
      </a>
      <?php endif; ?>

      <button type="button" class="spx-dismiss" data-spx-close>No thanks</button>
    </div>
  </div>
</div>

<style>
.spx{
  --s-ink:#11162D; --s-body:#5E6475; --s-line:#E7E9F0; --s-soft:#F7F8FC;
  --s-brand:#6A00FF; --s-solid:#8B00E0;
  --s-grad:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  --s-ease:cubic-bezier(.22,1,.36,1);
  position:fixed; inset:0; z-index:1300;
  display:flex; align-items:center; justify-content:center; padding:20px;
  font-family:'Inter',system-ui,sans-serif;
}
.spx[hidden]{ display:none; }
.spx-scrim{
  position:absolute; inset:0; background:rgba(10,14,32,.55);
  -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px);
  opacity:0; transition:opacity .3s var(--s-ease);
}
.spx.is-in .spx-scrim{ opacity:1; }

.spx-box{
  position:relative; z-index:1;
  width:min(460px,100%); max-height:88vh; overflow-y:auto;
  background:#fff; border-radius:20px;
  box-shadow:0 24px 70px rgba(10,14,32,.30);
  opacity:0; transform:translateY(20px) scale(.96);
  transition:opacity .36s var(--s-ease), transform .36s var(--s-ease);
}
.spx.is-in .spx-box{ opacity:1; transform:none; }
.spx.is-out .spx-box{ opacity:0; transform:translateY(10px) scale(.98); }

.spx-x{
  position:absolute; top:12px; right:12px; z-index:2;
  display:grid; place-items:center;
  width:32px; height:32px; border-radius:9px; cursor:pointer;
  background:rgba(255,255,255,.9); border:1px solid var(--s-line);
  color:var(--s-body); font-size:14px;
}
.spx-x:hover{ color:var(--s-brand); border-color:rgba(106,0,255,.3); }
.spx-x:focus-visible{ outline:2px solid var(--s-brand); outline-offset:2px; }

.spx-media{ overflow:hidden; border-radius:20px 20px 0 0; background:var(--s-soft); }
.spx-media img{ width:100%; height:auto; max-height:230px; object-fit:cover; display:block; }

.spx-body{ padding:24px 26px 26px; text-align:center; }
.spx-title{
  font-family:'Plus Jakarta Sans','Inter',sans-serif;
  font-size:21px; font-weight:800; letter-spacing:-.02em;
  color:var(--s-ink); margin:0 0 10px; line-height:1.28;
}
.spx-text{ font-size:14.5px; line-height:1.7; color:var(--s-body); margin:0 0 20px; }
.spx-text p{ margin:0 0 10px; }
.spx-text p:last-child{ margin:0; }
.spx-text a{ color:var(--s-brand); font-weight:600; }

.spx-cta{
  display:inline-flex; align-items:center; justify-content:center; gap:9px;
  width:100%; height:48px; border-radius:12px;
  background-color:var(--s-solid); background-image:var(--s-grad);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-size:15px; font-weight:600; text-decoration:none;
  box-shadow:0 6px 18px rgba(106,0,255,.24);
  transition:background-position .3s ease, transform .2s ease;
}
.spx-cta:hover{ color:#fff; background-position:100% 50%; transform:translateY(-2px); }
.spx-cta:focus-visible{ outline:2px solid var(--s-brand); outline-offset:3px; }
.spx-cta i{ font-size:12px; }

.spx-dismiss{
  display:block; width:100%; margin-top:12px;
  background:none; border:0; cursor:pointer;
  font-family:inherit; font-size:13px; color:#7E8496;
}
.spx-dismiss:hover{ color:var(--s-ink); text-decoration:underline; }

@media (max-width:520px){
  .spx{ padding:0; align-items:flex-end; }
  .spx-box{ width:100%; border-radius:20px 20px 0 0; transform:translateY(100%); }
  .spx.is-in .spx-box{ transform:none; }
  .spx.is-out .spx-box{ transform:translateY(60%); }
  .spx-media{ border-radius:20px 20px 0 0; }
  .spx-body{ padding:20px 20px 22px; }
}
@media (prefers-reduced-motion:reduce){
  .spx-scrim, .spx-box, .spx-cta{ transition:none; }
  .spx-box{ transform:none; }
}
@media print{ .spx{ display:none !important; } }
</style>

<script>
(function () {
  var m = document.getElementById('spx');
  if (!m) return;

  var delay = parseInt(m.dataset.delay, 10);
  if (isNaN(delay)) delay = 5;
  var key   = m.dataset.key;
  var once  = m.dataset.once === '1';
  var last  = null;

  /* "Show once" is per browser and survives a reload; otherwise it is
     per tab, so the visitor is not hit again on every internal click. */
  function seen() {
    try { return once ? localStorage.getItem(key) === '1' : sessionStorage.getItem(key) === '1'; }
    catch (e) { return false; }
  }
  function markSeen() {
    try { (once ? localStorage : sessionStorage).setItem(key, '1'); } catch (e) {}
  }

  function open() {
    if (seen() || !m.hidden) return;
    last = document.activeElement;
    m.hidden = false;
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () { m.classList.add('is-in'); });
    var first = m.querySelector('.spx-cta, .spx-x');
    if (first) setTimeout(function () { first.focus(); }, 260);
  }

  function close() {
    if (m.hidden) return;
    markSeen();
    m.classList.remove('is-in');
    m.classList.add('is-out');
    setTimeout(function () {
      m.hidden = true;
      m.classList.remove('is-out');
      document.body.style.overflow = '';
      if (last && last.focus) last.focus();
    }, 300);
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-spx-close]')) { e.preventDefault(); close(); }
  });
  document.addEventListener('keydown', function (e) {
    if (m.hidden) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;
    var items = Array.prototype.filter.call(
      m.querySelectorAll('a[href],button:not([disabled])'),
      function (el) { return el.offsetParent !== null; });
    if (!items.length) return;
    var f = items[0], l = items[items.length - 1];
    if (e.shiftKey && document.activeElement === f) { e.preventDefault(); l.focus(); }
    else if (!e.shiftKey && document.activeElement === l) { e.preventDefault(); f.focus(); }
  });

  /* Clicking the CTA counts as engagement, so it should not reappear */
  var cta = m.querySelector('.spx-cta');
  if (cta) cta.addEventListener('click', markSeen);

  if (!seen()) setTimeout(open, delay * 1000);
})();
</script>
