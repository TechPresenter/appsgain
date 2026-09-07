/* ═══════════════════════════════════════════════════════════
   AI Chatbot widget — no dependencies.

   The shell is rendered by includes/chatbot-widget.php; this file
   wires it up. Configuration arrives on window.AG_CHAT, which
   carries the bot's name, the CSRF token and the endpoint — and
   deliberately nothing else. There is no API key in the browser.

   State that matters (the conversation, which lead question is
   next) lives in the PHP session. sessionStorage here only keeps
   the visible transcript so moving between pages does not appear
   to wipe the chat.
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var CFG = window.AG_CHAT;
  var root = document.getElementById('ag-chat');
  if (!CFG || !root) return;

  var log      = root.querySelector('.ag-chat-log');
  var chips    = root.querySelector('.ag-chat-chips');
  var form     = root.querySelector('.ag-chat-form');
  var input    = root.querySelector('.ag-chat-input');
  var sendBtn  = root.querySelector('.ag-chat-send');
  var launcher = root.querySelector('.ag-chat-launcher');
  var closeBtn = root.querySelector('.ag-chat-close');

  /* 'chat' or the key of the lead question we are waiting on. */
  var mode    = 'chat';
  var busy    = false;
  var STORE   = 'agChatLog';

  /* ── Rendering ──────────────────────────────────────── */

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function scroll() { log.scrollTop = log.scrollHeight; }

  function add(text, who, save) {
    var cls = who === 'user' ? 'ag-msg ag-msg-user'
            : who === 'err'  ? 'ag-msg ag-msg-err'
            : 'ag-msg ag-msg-bot';
    log.appendChild(el('div', cls, text));
    scroll();
    if (save !== false) remember(text, who);
  }

  /* Keep the visible transcript across page loads. Capped, because
     sessionStorage is small and a long chat is not worth losing the
     whole store over. */
  function remember(text, who) {
    try {
      var all = JSON.parse(sessionStorage.getItem(STORE) || '[]');
      all.push([who, text]);
      sessionStorage.setItem(STORE, JSON.stringify(all.slice(-40)));
    } catch (e) { /* private mode, quota — the chat still works */ }
  }

  function restore() {
    var all = [];
    try { all = JSON.parse(sessionStorage.getItem(STORE) || '[]'); } catch (e) { return false; }
    if (!all.length) return false;
    all.forEach(function (m) { add(m[1], m[0], false); });
    return true;
  }

  var typing = null;
  function showTyping(on) {
    if (on) {
      if (typing) return;
      typing = el('div', 'ag-msg ag-msg-bot ag-typing');
      typing.appendChild(el('span'));
      typing.appendChild(el('span'));
      typing.appendChild(el('span'));
      log.appendChild(typing);
      scroll();
    } else if (typing) {
      typing.remove();
      typing = null;
    }
  }

  function setChips(items) {
    chips.innerHTML = '';
    (items || []).forEach(function (c) {
      var b = el('button', 'ag-chip' + (c.cta ? ' ag-chip-cta' : ''), c.label);
      b.type = 'button';
      b.addEventListener('click', c.onClick);
      chips.appendChild(b);
    });
  }

  function starterChips() {
    if (!CFG.starters || !CFG.starters.length) { setChips([]); return; }
    setChips(CFG.starters.map(function (s) {
      return { label: s, onClick: function () { send(s); } };
    }));
  }

  function leadChip() {
    setChips([{
      label: CFG.leadCta || 'Request a callback',
      cta: true,
      onClick: function () { setChips([]); post({ action: 'lead_start' }); }
    }]);
  }

  /* ── Client-side validation ─────────────────────────────
     A mirror of the server's rules, for instant feedback only.
     api/chatbot.php validates again and is the authority; nothing
     here can wave a bad value through. */

  function clientError(field, v) {
    v = v.trim();
    if (field === 'name') {
      if (v.length < 2) return 'Please enter your full name.';
      if (!/^[\p{L}\p{M}.'\- ]+$/u.test(v)) return 'Please use letters only in your name.';
    }
    if (field === 'phone') {
      var d = v.replace(/\D+/g, '');
      if (d.length < 8 || d.length > 15) {
        return 'Please enter a valid mobile number with country code, e.g. +91 98765 43210.';
      }
    }
    if (field === 'email') {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) {
        return 'That email address does not look right — please check and try again.';
      }
    }
    if (field === 'requirement' && v.length < 5) {
      return 'Could you give me a little more detail?';
    }
    return null;
  }

  /* ── Transport ──────────────────────────────────────── */

  function post(payload) {
    if (busy) return;
    busy = true;
    sendBtn.disabled = true;
    showTyping(true);

    payload.page = location.pathname + location.search;

    fetch(CFG.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CFG.csrf },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
      .then(handle)
      .catch(function () {
        add('I could not reach the server. Please check your connection and try again.', 'err');
      })
      .finally(function () {
        busy = false;
        sendBtn.disabled = false;
        showTyping(false);
        input.focus();
      });
  }

  function handle(res) {
    showTyping(false);

    if (!res || !res.ok) {
      add((res && res.message) || 'Something went wrong. Please try again.', 'err');
      if (res && res.expired) {
        setChips([{ label: 'Reload page', cta: true, onClick: function () { location.reload(); } }]);
      }
      return;
    }

    if (res.message) add(res.message, 'bot');

    /* A lead reply carries the field it is waiting on; anything else
       puts us back in ordinary conversation. */
    if (res.mode === 'lead' && res.field) {
      mode = res.field;
      input.placeholder = placeholderFor(res.field);
      /* The composer is a textarea, so there is no `type` to switch — but
         inputMode still decides which on-screen keyboard a phone shows,
         which is the part that matters for the mobile-number step. */
      input.inputMode = res.field === 'phone' ? 'tel'
                      : res.field === 'email' ? 'email' : 'text';
      setChips([{
        label: 'Cancel',
        onClick: function () { setChips([]); post({ action: 'lead_cancel' }); }
      }]);
    } else {
      mode = 'chat';
      input.placeholder = CFG.placeholder || 'Type your message…';
      input.inputMode = 'text';
      if (res.offerLead) leadChip(); else setChips([]);
    }
    input.focus();
  }

  function placeholderFor(field) {
    return {
      name: 'Your name',
      phone: '+91 98765 43210',
      email: 'you@company.com',
      requirement: 'What do you need built?'
    }[field] || 'Type your answer…';
  }

  /* ── Sending ────────────────────────────────────────── */

  function send(text) {
    text = (text || '').trim();
    if (!text || busy) return;

    if (mode !== 'chat') {
      var err = clientError(mode, text);
      add(text, 'user');
      if (err) { add(err, 'bot'); input.value = ''; input.focus(); return; }
      input.value = '';
      post({ action: 'lead_answer', value: text });
      return;
    }

    add(text, 'user');
    input.value = '';
    input.style.height = 'auto';
    setChips([]);
    post({ action: 'chat', message: text });
  }

  /* ── Wiring ─────────────────────────────────────────── */

  function open() {
    root.classList.add('ag-open', 'ag-seen');
    try { sessionStorage.setItem('agChatSeen', '1'); } catch (e) {}
    setTimeout(function () { input.focus(); }, 180);
    scroll();
  }
  function close() { root.classList.remove('ag-open'); }

  launcher.addEventListener('click', function () {
    root.classList.contains('ag-open') ? close() : open();
  });
  closeBtn.addEventListener('click', close);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    send(input.value);
  });

  /* Enter sends, Shift+Enter makes a new line. */
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); }
  });

  /* Grow the box with the text, up to the CSS max-height. */
  input.addEventListener('input', function () {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 96) + 'px';
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('ag-open')) close();
  });

  /* ── Voice input ─────────────────────────────────────
     The browser's own speech API: audio never reaches our server, and the
     visitor sends the text like anything they typed rather than it going
     off on its own. The button is rendered hidden and only revealed where
     the API exists, so no browser shows a control that cannot work. */
  (function () {
    var micBtn = root.querySelector('.ag-chat-mic');
    if (!micBtn || !CFG.voice) return;

    var Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Rec) return;                 // stays hidden

    micBtn.hidden = false;

    var rec = new Rec();
    rec.lang = CFG.voiceLang || 'en-IN';
    rec.interimResults = true;
    rec.continuous = false;

    var listening = false;
    /* What was in the box before dictation, so interim results replace only
       the dictated tail instead of eating an edit already in progress. */
    var baseText = '';

    function setListening(on) {
      listening = on;
      micBtn.classList.toggle('is-live', on);
      micBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
      micBtn.setAttribute('aria-label', on ? 'Stop dictation' : 'Speak your message');
    }

    rec.onresult = function (ev) {
      var text = '';
      for (var i = ev.resultIndex; i < ev.results.length; i++) text += ev.results[i][0].transcript;
      input.value = (baseText ? baseText + ' ' : '') + text.trim();
      input.dispatchEvent(new Event('input'));   // keep the auto-grow honest
    };

    rec.onerror = function (ev) {
      setListening(false);
      /* A refused microphone is worth explaining; the rest are noise the
         visitor can see for themselves in the empty box. */
      if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
        add('I could not reach your microphone. Please allow microphone access, or type instead.', 'bot');
      }
    };

    rec.onend = function () {
      setListening(false);
      input.focus();
    };

    micBtn.addEventListener('click', function () {
      if (busy) return;
      if (listening) { rec.stop(); return; }
      baseText = input.value.trim();
      try {
        rec.start();
        setListening(true);
      } catch (e) {
        /* start() throws when called twice in quick succession. */
        setListening(false);
      }
    });

    /* Sending mid-sentence should not leave the mic running. */
    form.addEventListener('submit', function () { if (listening) rec.stop(); });
  })();

  /* Anything on the page can open the chat: data-ag-chat-open on a
     button, or a link to #chat. */
  document.querySelectorAll('[data-ag-chat-open]').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); open(); });
  });

  /* ── First paint ────────────────────────────────────── */
  if (!restore()) {
    add(CFG.welcome, 'bot');
    starterChips();
  }
  try {
    if (sessionStorage.getItem('agChatSeen')) root.classList.add('ag-seen');
  } catch (e) {}
})();
