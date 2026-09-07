/* ================================================================
   APPSGAIN Analytics Tracker v1.0
   Tracks: sessions, pageviews, scroll depth, time-on-page,
   clicks (heatmap), UTM params, device/screen, timezone
   ================================================================ */
(function (W, D, N) {
  'use strict';

  /* ── Config ── */
  var API  = (W.__AG_ANALYTICS_API || '/api/analytics.php');
  var SESS = 'ag_sess';
  var VID  = 'ag_vid';
  var SEEN = 'ag_seen';

  /* ── Utilities ── */
  function uid(prefix) {
    return (prefix || 'id') + '_' + Date.now() + '_' +
           Math.random().toString(36).substr(2, 9);
  }
  function store(key, val) {
    try { return val === undefined ? localStorage.getItem(key) : localStorage.setItem(key, val); }
    catch(e) { return null; }
  }
  function session(key, val) {
    try { return val === undefined ? sessionStorage.getItem(key) : sessionStorage.setItem(key, val); }
    catch(e) { return null; }
  }
  function send(data, beacon) {
    var body = JSON.stringify(data);
    if (beacon && N && N.sendBeacon) {
      N.sendBeacon(API, body);
      return;
    }
    try {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', API, true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(body);
    } catch(e) {}
  }

  /* ── IDs ── */
  var sessionId = session(SESS);
  if (!sessionId) { sessionId = uid('s'); session(SESS, sessionId); }

  var visitorId = store(VID);
  if (!visitorId) { visitorId = uid('v'); store(VID, visitorId); }

  var isNew = !store(SEEN) ? 1 : 0;
  store(SEEN, '1');

  /* ── UTM / Query params ── */
  function getParam(name) {
    var m = (W.location.search || '').match(new RegExp('[?&]' + name + '=([^&]*)'));
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }
  var utmSrc  = getParam('utm_source')   || session('ag_utm_src')  || '';
  var utmMed  = getParam('utm_medium')   || session('ag_utm_med')  || '';
  var utmCamp = getParam('utm_campaign') || session('ag_utm_camp') || '';
  var utmTerm = getParam('utm_term')     || '';
  var utmCont = getParam('utm_content')  || '';
  if (utmSrc)  session('ag_utm_src',  utmSrc);
  if (utmMed)  session('ag_utm_med',  utmMed);
  if (utmCamp) session('ag_utm_camp', utmCamp);

  /* ── Traffic source ── */
  function detectSource() {
    if (utmSrc) return 'campaign';
    var ref = D.referrer || '';
    if (!ref) return 'direct';
    try {
      var h = new URL(ref).hostname.replace(/^www\./, '');
      if (/google|bing|yahoo|duckduck|yandex|baidu|ecosia/i.test(h)) return 'organic';
      if (/facebook|instagram|twitter|linkedin|youtube|tiktok|pinterest|reddit|whatsapp|telegram/i.test(h)) return 'social';
      var siteHost = (W.location.hostname || '').replace(/^www\./, '');
      if (h === siteHost) return 'internal';
      return 'referral';
    } catch(e) { return 'referral'; }
  }
  var trafficSource = detectSource();

  /* ── Device / screen ── */
  function getDevice() {
    var ua = N.userAgent || '';
    if (/Mobile|Android|iPhone/i.test(ua)) return 'mobile';
    if (/iPad|Tablet/i.test(ua))           return 'tablet';
    return 'desktop';
  }

  /* ── Timezone → rough country ── */
  function getTimezone() {
    try { return Intl.DateTimeFormat().resolvedOptions().timeZone; } catch(e) { return ''; }
  }
  function getLocale() {
    try { return N.language || N.userLanguage || ''; } catch(e) { return ''; }
  }

  /* ── Page timing & scroll ── */
  var pageStart   = Date.now();
  var maxScroll   = 0;
  var pvId        = uid('pv'); // unique pageview ID for updating on unload

  function updateScroll() {
    var el  = D.documentElement;
    var top = W.pageYOffset || el.scrollTop || 0;
    var h   = (el.scrollHeight - el.clientHeight) || 1;
    var d   = Math.min(100, Math.round((top / h) * 100));
    if (d > maxScroll) maxScroll = d;
  }
  W.addEventListener('scroll', updateScroll, {passive: true});

  /* ── Heatmap click tracking ── */
  var clickThrottle = 0;
  D.addEventListener('click', function(e) {
    var now = Date.now();
    if (now - clickThrottle < 500) return; // throttle 500ms
    clickThrottle = now;

    var xPct = Math.round((e.clientX / (W.innerWidth  || 1)) * 100);
    var yPct = Math.round((e.clientY / (W.innerHeight || 1)) * 100);
    var tgt  = e.target;
    var tag  = (tgt.tagName || '').toLowerCase();
    var text = (tgt.innerText || tgt.value || tgt.alt || '').trim().substr(0, 60);
    var href = tgt.href || (tgt.closest ? (tgt.closest('a') || {}).href : '') || '';

    send({
      t: 'click',
      sid: sessionId,
      pv:  pvId,
      url: W.location.href,
      x:   xPct,
      y:   yPct,
      tag: tag,
      txt: text,
      href: href ? href.substr(0, 200) : ''
    });
  });

  /* ── Form interaction tracking ── */
  D.addEventListener('focusin', function(e) {
    var tgt = e.target;
    if (tgt.tagName === 'INPUT' || tgt.tagName === 'TEXTAREA' || tgt.tagName === 'SELECT') {
      var formEl = tgt.closest ? tgt.closest('form') : null;
      send({
        t: 'form_start',
        sid: sessionId,
        url: W.location.href,
        form_id: formEl ? (formEl.id || formEl.action || '') : '',
        field: tgt.name || tgt.id || ''
      });
    }
  }, {once: true});

  /* ── CTA click tracking ── */
  D.addEventListener('click', function(e) {
    var tgt = e.target.closest ? e.target.closest('a,button') : e.target;
    if (!tgt) return;
    var cls = tgt.className || '';
    if (/btn-primary|btn-accent|cta/i.test(cls)) {
      send({
        t: 'cta_click',
        sid: sessionId,
        url: W.location.href,
        label: (tgt.innerText || '').trim().substr(0, 80),
        href: (tgt.href || '').substr(0, 200)
      });
    }
  });

  /* ── Send initial pageview ── */
  send({
    t:      'pv',
    pvid:   pvId,
    sid:    sessionId,
    vid:    visitorId,
    is_new: isNew,
    url:    W.location.href,
    title:  D.title,
    ref:    D.referrer,
    src:    trafficSource,
    utm_src:  utmSrc,
    utm_med:  utmMed,
    utm_camp: utmCamp,
    utm_term: utmTerm,
    utm_cont: utmCont,
    device: getDevice(),
    sw:     screen.width  || 0,
    sh:     screen.height || 0,
    tz:     getTimezone(),
    lang:   getLocale()
  });

  /* ── Send page unload (time + scroll) ── */
  function sendUnload() {
    var elapsed = Math.round((Date.now() - pageStart) / 1000);
    send({
      t:      'unload',
      pvid:   pvId,
      sid:    sessionId,
      url:    W.location.href,
      top:    elapsed,
      scroll: maxScroll
    }, true); // use sendBeacon
  }

  W.addEventListener('pagehide',     sendUnload);
  W.addEventListener('beforeunload', sendUnload);

  /* ── Expose for manual event firing ── */
  W.agTrack = function(eventType, data) {
    send({t: 'event', sid: sessionId, event_type: eventType, data: data || {}, url: W.location.href});
  };

})(window, document, navigator);
