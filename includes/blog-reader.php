<?php
/**
 * Appsgain — article reading tools.
 *
 * Three things, one bar:
 *   · Listen      — text-to-speech over the article, sentence by sentence,
 *                   with the spoken sentence highlighted so the eye can
 *                   follow. Uses the browser's own speechSynthesis, so no
 *                   audio files, no API, no cost.
 *   · Reading mode — hides site chrome and widens the column.
 *   · Text size    — three steps, remembered per reader.
 *
 * The TOC is built from the article's own h2/h3 with scroll-spy.
 *
 * Requires a container with id="articleBody" holding the post HTML.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }
?>

<!-- ══ Reading toolbar ══ -->
<div class="rdx-bar" id="rdxBar" role="toolbar" aria-label="Reading tools" aria-controls="articleBody">
  <div class="rdx-group rdx-listen">
    <button type="button" class="rdx-btn rdx-btn--play" id="rdxPlay"
            aria-label="Listen to this article">
      <i class="fas fa-play" aria-hidden="true"></i>
      <span class="rdx-btn-txt">Listen</span>
    </button>
    <button type="button" class="rdx-btn rdx-icon" id="rdxStop" aria-label="Stop reading" hidden>
      <i class="fas fa-stop" aria-hidden="true"></i>
    </button>
    <div class="rdx-speed" id="rdxSpeedWrap" hidden>
      <label class="rdx-sr" for="rdxSpeed">Reading speed</label>
      <select id="rdxSpeed" class="rdx-speed-sel">
        <option value="0.75">0.75&times;</option>
        <option value="1" selected>1&times;</option>
        <option value="1.25">1.25&times;</option>
        <option value="1.5">1.5&times;</option>
      </select>
    </div>
  </div>

  <div class="rdx-group">
    <button type="button" class="rdx-btn rdx-icon" id="rdxFontDown" aria-label="Decrease text size">
      <i class="fas fa-minus" aria-hidden="true"></i>
    </button>
    <span class="rdx-size" id="rdxSizeLabel" aria-live="polite">A</span>
    <button type="button" class="rdx-btn rdx-icon" id="rdxFontUp" aria-label="Increase text size">
      <i class="fas fa-plus" aria-hidden="true"></i>
    </button>
  </div>

  <div class="rdx-group">
    <button type="button" class="rdx-btn" id="rdxFocus" aria-pressed="false"
            aria-label="Toggle reading mode">
      <i class="fas fa-book-open-reader" aria-hidden="true"></i>
      <span class="rdx-btn-txt">Reading mode</span>
    </button>
  </div>

  <p class="rdx-status rdx-sr" id="rdxStatus" role="status" aria-live="polite"></p>
</div>

<!-- Floating exit for reading mode -->
<button type="button" class="rdx-exit" id="rdxExit" hidden>
  <i class="fas fa-xmark" aria-hidden="true"></i> Exit reading mode
</button>

<style>
/* ══════════════════════════════════════════════════════
   ARTICLE READING TOOLS
   ══════════════════════════════════════════════════════ */
.rdx-bar{
  --r-ink:#11162D; --r-body:#5E6475; --r-mute:#7E8496;
  --r-line:#E7E9F0; --r-soft:#F7F8FC;
  --r-brand:#6A00FF; --r-solid:#8B00E0;
  --r-grad:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  display:flex; align-items:center; flex-wrap:wrap; gap:10px;
  padding:10px 12px; margin:0 0 24px;
  background:#fff; border:1px solid var(--r-line); border-radius:14px;
  box-shadow:0 1px 2px rgba(16,22,47,.04), 0 6px 20px rgba(16,22,47,.04);
  font-family:var(--font-body),system-ui,sans-serif;
}
.rdx-group{ display:flex; align-items:center; gap:6px; }
.rdx-group + .rdx-group{ padding-left:10px; border-left:1px solid var(--r-line); }
.rdx-listen{ margin-right:auto; }
.rdx-sr{
  position:absolute; width:1px; height:1px; padding:0; margin:-1px;
  overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; border:0;
}

.rdx-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  height:38px; padding:0 14px; border-radius:10px; cursor:pointer;
  background:var(--r-soft); border:1px solid var(--r-line);
  color:var(--r-ink); font-family:inherit; font-size:13.5px; font-weight:600;
  transition:background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
}
.rdx-btn:hover{ background:#fff; border-color:rgba(106,0,255,.3); color:var(--r-brand); }
.rdx-btn:focus-visible{ outline:2px solid var(--r-brand); outline-offset:2px; }
.rdx-btn.rdx-icon{ width:38px; padding:0; }
.rdx-btn i{ font-size:13px; }

/* Listen is the primary action, so it carries the brand */
.rdx-btn--play{
  background-color:var(--r-solid); background-image:var(--r-grad);
  border-color:transparent; color:#fff;
}
.rdx-btn--play:hover{ color:#fff; transform:translateY(-1px); }
.rdx-btn--play.is-playing i::before{ content:"\f04c"; }  /* pause glyph */

.rdx-speed-sel{
  height:38px; padding:0 8px; border-radius:10px;
  background:var(--r-soft); border:1px solid var(--r-line);
  color:var(--r-ink); font-family:inherit; font-size:13px; font-weight:600;
  cursor:pointer;
}
.rdx-size{
  min-width:26px; text-align:center;
  font-size:13px; font-weight:700; color:var(--r-mute);
}

/* ── Spoken-sentence highlight ── */
.rdx-speaking{
  background:linear-gradient(180deg, rgba(106,0,255,.10), rgba(208,0,168,.10));
  box-shadow:0 0 0 3px rgba(106,0,255,.08);
  border-radius:4px;
  transition:background .2s ease;
}

/* ── Text size steps, applied to the article ── */
/* [style] raises specificity over the theme's .blog-content rule without
   resorting to !important; the variable is always set inline by the JS. */
#articleBody{ font-size:16.5px; transition:font-size .18s ease; }
#articleBody p, #articleBody li{ line-height:1.8; }

/* ══ Reading mode ══
   Hides site chrome rather than opening a new view, so scroll position,
   the TOC and the speech queue all survive the toggle. */
body.rdx-reading .hx-util,
body.rdx-reading .hx,
body.rdx-reading .ftx,
body.rdx-reading .eqx-fab,
body.rdx-reading .whatsapp-float,
body.rdx-reading .blog-sidebar,
body.rdx-reading .back-to-top,
body.rdx-reading .rdx-bar .rdx-group:last-child{ display:none !important; }

body.rdx-reading .blog-layout{ grid-template-columns:1fr !important; }
body.rdx-reading #articleBody,
body.rdx-reading .blog-content-box{
  max-width:74ch; margin-inline:auto;
}
body.rdx-reading{ background:#FBFAF7; }        /* a touch warmer than white */
body.rdx-reading .blog-content-box{ background:#FBFAF7; border-color:transparent; box-shadow:none; }


/* [hidden] loses to an explicit display, so the exit button showed even
   when reading mode was off. Display now comes only from .rdx-reading. */
.rdx-exit[hidden]{ display:none; }
.rdx-exit{
  position:fixed; right:20px; bottom:20px; z-index:80;
  display:none; align-items:center; gap:8px;
  height:42px; padding:0 18px; border:0; border-radius:999px; cursor:pointer;
  background:#11162D; color:#fff;
  font-family:var(--font-body),sans-serif; font-size:13.5px; font-weight:600;
  box-shadow:0 8px 24px rgba(16,22,47,.28);
}
.rdx-exit:hover{ background:#000; }
body.rdx-reading .rdx-exit{ display:inline-flex; }

@media (max-width:640px){
  .rdx-bar{ gap:8px; padding:9px 10px; }
  .rdx-btn-txt{ display:none; }
  .rdx-btn{ width:38px; padding:0; }
  .rdx-btn--play{ width:auto; padding:0 14px; }
  .rdx-btn--play .rdx-btn-txt{ display:inline; }
  .rdx-group + .rdx-group{ padding-left:8px; }
}
@media (prefers-reduced-motion:reduce){
  .rdx-btn, #articleBody, .rdx-speaking{ transition:none; }
}
@media print{ .rdx-bar, .rdx-exit{ display:none !important; } }
</style>

<script>
/* The toolbar is printed above the article, so at parse time #articleBody
   does not exist yet. Waiting for DOM ready is what makes the controls
   bind at all — without it every handler silently never attaches. */
function rdxInit() {
  'use strict';
  var body = document.getElementById('articleBody');
  var bar  = document.getElementById('rdxBar');
  if (!body || !bar) return;

  var statusEl = document.getElementById('rdxStatus');
  function say(msg) { if (statusEl) statusEl.textContent = msg; }

  /* ══ 1 · Text size ══════════════════════════════════ */
  var SIZES = [15, 16.5, 18.5, 20.5];
  var LABEL = ['A', 'A', 'A', 'A'];
  var sizeIdx = 1;
  try {
    var st = parseInt(localStorage.getItem('ag_read_size'), 10);
    if (!isNaN(st) && st >= 0 && st < SIZES.length) sizeIdx = st;
  } catch (e) {}

  function applySize() {
    /* Set font-size outright rather than through a custom property: an
       inline declaration beats the theme's .blog-content rule at every
       breakpoint, with no specificity guessing. */
    body.style.fontSize = SIZES[sizeIdx] + 'px';
    var lbl = document.getElementById('rdxSizeLabel');
    if (lbl) {
      lbl.textContent = LABEL[sizeIdx];
      lbl.style.fontSize = (11 + sizeIdx * 1.5) + 'px';
    }
    try { localStorage.setItem('ag_read_size', String(sizeIdx)); } catch (e) {}
  }
  document.getElementById('rdxFontUp').addEventListener('click', function () {
    if (sizeIdx < SIZES.length - 1) { sizeIdx++; applySize(); say('Text size increased'); }
  });
  document.getElementById('rdxFontDown').addEventListener('click', function () {
    if (sizeIdx > 0) { sizeIdx--; applySize(); say('Text size decreased'); }
  });
  applySize();

  /* ══ 2 · Reading mode ═══════════════════════════════ */
  var focusBtn = document.getElementById('rdxFocus');
  var exitBtn  = document.getElementById('rdxExit');

  function setReading(on) {
    document.body.classList.toggle('rdx-reading', on);
    focusBtn.setAttribute('aria-pressed', String(on));
    exitBtn.hidden = !on;
    say(on ? 'Reading mode on' : 'Reading mode off');
    try { localStorage.setItem('ag_read_focus', on ? '1' : '0'); } catch (e) {}
  }
  focusBtn.addEventListener('click', function () {
    setReading(!document.body.classList.contains('rdx-reading'));
  });
  exitBtn.addEventListener('click', function () { setReading(false); focusBtn.focus(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('rdx-reading')) setReading(false);
  });

  /* ══ 3 · Listen ═════════════════════════════════════
     Long text is split into sentences and spoken one at a time. Chrome
     truncates utterances beyond roughly 200-300 characters and stalls on
     very long ones, so chunking is what makes this reliable — and it
     gives a natural unit to highlight as it is read. */
  var playBtn  = document.getElementById('rdxPlay');
  var stopBtn  = document.getElementById('rdxStop');
  var speedSel = document.getElementById('rdxSpeed');
  var speedWrap= document.getElementById('rdxSpeedWrap');

  var synth = window.speechSynthesis;
  if (!synth || typeof SpeechSynthesisUtterance === 'undefined') {
    /* No support: hide rather than offer a button that does nothing */
    playBtn.closest('.rdx-listen').hidden = true;
    return;
  }

  var chunks = [];      /* [{text, node}] */
  var idx = 0;
  var playing = false;
  var voice = null;

  /* Pick an English voice once the list is populated (async in Chrome) */
  function pickVoice() {
    var vs = synth.getVoices();
    if (!vs.length) return;
    var lang = (document.documentElement.lang || 'en').toLowerCase();
    voice = vs.find(function (v) { return v.lang.toLowerCase().indexOf(lang) === 0 && v.localService; })
         || vs.find(function (v) { return v.lang.toLowerCase().indexOf(lang) === 0; })
         || vs.find(function (v) { return v.lang.toLowerCase().indexOf('en') === 0; })
         || vs[0];
  }
  pickVoice();
  if (typeof synth.onvoiceschanged !== 'undefined') synth.onvoiceschanged = pickVoice;

  /* Wrap each sentence so it can be highlighted while spoken. Only text
     nodes inside block elements are touched — markup is left intact. */
  function buildChunks() {
    if (chunks.length) return;
    var blocks = body.querySelectorAll('h1,h2,h3,h4,p,li,blockquote');
    blocks.forEach(function (el) {
      if (el.closest('pre,code,figure')) return;
      var text = el.textContent.replace(/\s+/g, ' ').trim();
      if (text.length < 2) return;
      /* split on sentence ends, keeping them under ~220 chars */
      var parts = text.match(/[^.!?]+[.!?]*\s*/g) || [text];
      var buf = '';
      var flush = function () {
        if (buf.trim()) chunks.push({ text: buf.trim(), node: el });
        buf = '';
      };
      parts.forEach(function (p) {
        if ((buf + p).length > 220) flush();
        buf += p;
      });
      flush();
    });
  }

  function clearHighlight() {
    body.querySelectorAll('.rdx-speaking').forEach(function (n) { n.classList.remove('rdx-speaking'); });
  }

  function speakFrom(i) {
    if (i >= chunks.length) { stop(); say('Finished reading'); return; }
    idx = i;
    var c = chunks[idx];
    var u = new SpeechSynthesisUtterance(c.text);
    if (voice) u.voice = voice;
    u.rate = parseFloat(speedSel.value) || 1;
    u.pitch = 1;
    u.onstart = function () {
      clearHighlight();
      c.node.classList.add('rdx-speaking');
      /* keep the spoken line in view without yanking the page around */
      var r = c.node.getBoundingClientRect();
      if (r.top < 80 || r.bottom > window.innerHeight - 80) {
        c.node.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    };
    u.onend = function () { if (playing) speakFrom(idx + 1); };
    u.onerror = function (ev) {
      /* 'interrupted' and 'canceled' are normal when the reader stops */
      if (ev.error && ev.error !== 'interrupted' && ev.error !== 'canceled') {
        stop();
        say('Could not read this article aloud.');
      }
    };
    synth.speak(u);
  }

  function play() {
    buildChunks();
    if (!chunks.length) { say('Nothing to read'); return; }
    playing = true;
    playBtn.classList.add('is-playing');
    playBtn.setAttribute('aria-label', 'Pause reading');
    playBtn.querySelector('.rdx-btn-txt').textContent = 'Pause';
    stopBtn.hidden = false;
    speedWrap.hidden = false;
    say('Reading aloud');
    speakFrom(idx);
  }

  function pause() {
    playing = false;
    synth.cancel();                       /* pause() is unreliable across browsers */
    playBtn.classList.remove('is-playing');
    playBtn.setAttribute('aria-label', 'Resume reading');
    playBtn.querySelector('.rdx-btn-txt').textContent = 'Resume';
    say('Paused');
  }

  function stop() {
    playing = false;
    idx = 0;
    synth.cancel();
    clearHighlight();
    playBtn.classList.remove('is-playing');
    playBtn.setAttribute('aria-label', 'Listen to this article');
    playBtn.querySelector('.rdx-btn-txt').textContent = 'Listen';
    stopBtn.hidden = true;
    speedWrap.hidden = true;
  }

  playBtn.addEventListener('click', function () { playing ? pause() : play(); });
  stopBtn.addEventListener('click', function () { stop(); say('Stopped'); });
  speedSel.addEventListener('change', function () {
    if (playing) { synth.cancel(); speakFrom(idx); }   /* restart the current sentence at the new rate */
  });

  /* Clicking a paragraph while listening jumps the reader there */
  body.addEventListener('click', function (e) {
    if (!playing) return;
    var el = e.target.closest('h1,h2,h3,h4,p,li,blockquote');
    if (!el) return;
    var i = chunks.findIndex(function (c) { return c.node === el; });
    if (i > -1) { synth.cancel(); speakFrom(i); }
  });

  /* Speech keeps running after navigation otherwise */
  window.addEventListener('beforeunload', function () { synth.cancel(); });
  document.addEventListener('visibilitychange', function () {
    if (document.hidden && playing) pause();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', rdxInit);
} else {
  rdxInit();
}
</script>
