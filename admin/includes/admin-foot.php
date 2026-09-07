  </div><!-- /page-content -->
</div><!-- /main-area -->

<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
/* Sidebar accordion moved to the sidebarResponsive/navAccordion block
 at the bottom of this file, which also syncs aria-expanded. */

/* ── Sidebar: Toggle (desktop = mini mode, mobile = overlay) ── */
function toggleSidebar() {
  if (window.innerWidth > 768) {
    toggleMini();
  } else {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
  }
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}

/* ── Sidebar: Mini/Collapse mode ── */
(function initSidebarState() {
  if (window.innerWidth > 768) {
    const mini = localStorage.getItem('ag_sidebar_mini') === '1';
    if (mini) document.body.classList.add('sidebar-mini');
  }
})();

function toggleMini() {
  const isMini = document.body.classList.toggle('sidebar-mini');
  localStorage.setItem('ag_sidebar_mini', isMini ? '1' : '0');
}

/* ── Auto-close sidebar on resize to desktop ── */
window.addEventListener('resize', function() {
  if (window.innerWidth > 768) {
    closeSidebar();
    const mini = localStorage.getItem('ag_sidebar_mini') === '1';
    if (mini) document.body.classList.add('sidebar-mini');
  }
});

/* ── Swipe left to close sidebar on mobile ── */
(function() {
  var startX = 0, startY = 0;
  var sb = document.getElementById('sidebar');
  if (!sb) return;
  sb.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
  }, { passive: true });
  sb.addEventListener('touchend', function(e) {
    var dx = e.changedTouches[0].clientX - startX;
    var dy = Math.abs(e.changedTouches[0].clientY - startY);
    if (dx < -60 && dy < 80 && window.innerWidth <= 768) {
      closeSidebar();
    }
  }, { passive: true });
})();

/* ── Close nav links auto-close sidebar on mobile ── */
document.querySelectorAll('.nav-sub-item, .nav-item').forEach(function(link) {
  link.addEventListener('click', function() {
    if (window.innerWidth <= 768) closeSidebar();
  });
});

/* ── Profile Dropdown ── */
function toggleProfileMenu() {
  document.getElementById('profileMenu').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const wrap = document.querySelector('.profile-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('profileMenu').classList.remove('open');
  }
});

/* ── Toast Helper ── */
window.showToast = function(msg, type) {
  const gradients = {
    success: 'linear-gradient(135deg,#059669,#047857)',
    error:   'linear-gradient(135deg,#e11d48,#be123c)',
    warning: 'linear-gradient(135deg,#d97706,#b45309)',
    info:    'linear-gradient(135deg,#2563eb,#1d4ed8)'
  };
  Toastify({
    text: msg,
    duration: 3800,
    gravity: 'top',
    position: 'right',
    stopOnFocus: true,
    style: {
      background: gradients[type] || gradients.info,
      borderRadius: '12px',
      fontWeight: '600',
      fontSize: '13.5px',
      padding: '12px 18px',
      boxShadow: '0 8px 24px rgba(0,0,0,.15)',
    }
  }).showToast();
};

/* ── Confirm delete ── */
window.confirmDelete = function(msg, callback) {
  if (confirm(msg || 'Are you sure you want to delete this? This cannot be undone.')) {
    if (typeof callback === 'function') callback();
    else return true;
  }
  return false;
};

/* ── Image preview ── */
window.previewImage = function(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const el = document.getElementById(previewId);
      if (el) { el.src = e.target.result; el.style.display = 'block'; }
    };
    reader.readAsDataURL(input.files[0]);
  }
};

/* ── Auto-dismiss alerts ── */
setTimeout(function() {
  document.querySelectorAll('.alert-success,.alert-info').forEach(function(a) {
    a.style.transition = 'opacity .5s';
    a.style.opacity = '0';
    setTimeout(function() { if (a.parentNode) a.remove(); }, 500);
  });
}, 4500);

/* ── PHP flash toasts ── */
<?php
global $_adminFlashForToast;
foreach ($_adminFlashForToast ?? [] as $_f):
?>
document.addEventListener('DOMContentLoaded', function() {
  showToast(<?= json_encode($_f['msg']) ?>, '<?= $_f['type'] ?>');
});
<?php endforeach; ?>

/* ── Responsive: disable mini on small screens ── */
window.addEventListener('resize', function() {
  if (window.innerWidth <= 768) {
    document.body.classList.remove('sidebar-mini');
  }
  updateTopbarLayout();
});

/* ── Dark Mode ── */
(function initTheme() {
  const saved = localStorage.getItem('ag_theme');
  if (saved === 'dark') applyDark(true, false);
})();

function applyDark(on, save) {
  document.documentElement.setAttribute('data-theme', on ? 'dark' : '');
  /* topbar pill */
  const topIcon  = document.getElementById('themeIcon');
  const topLabel = document.getElementById('themeLabel');
  if (topIcon)  topIcon.className  = on ? 'fas fa-sun' : 'fas fa-moon';
  if (topLabel) topLabel.textContent = on ? 'Light' : 'Dark';
  /* sidebar footer btn */
  const sfIcon  = document.getElementById('sfThemeIcon');
  const sfLabel = document.getElementById('sfThemeLabel');
  if (sfIcon)  sfIcon.className  = on ? 'fas fa-sun' : 'fas fa-moon';
  if (sfLabel) sfLabel.textContent = on ? 'Light' : 'Dark';
  /* dropdown item */
  const ddIcon  = document.querySelector('#profileMenu [onclick="toggleTheme()"] i');
  const ddLabel = document.getElementById('themeDropLabel');
  if (ddIcon)  ddIcon.className  = on ? 'fas fa-sun' : 'fas fa-moon';
  if (ddLabel) ddLabel.textContent = on ? 'Light Mode' : 'Dark Mode';
  if (save !== false) localStorage.setItem('ag_theme', on ? 'dark' : 'light');
}

window.toggleTheme = function() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  applyDark(!isDark);
  document.getElementById('profileMenu') && document.getElementById('profileMenu').classList.remove('open');
};

/* ── Notification Panel ── */
window.toggleNotifPanel = function() {
  const panel = document.getElementById('notifPanel');
  const profileMenu = document.getElementById('profileMenu');
  if (profileMenu) profileMenu.classList.remove('open');
  if (panel) panel.classList.toggle('open');
};

document.addEventListener('click', function(e) {
  const notifWrap = document.getElementById('notifToggle');
  const notifPanel = document.getElementById('notifPanel');
  if (notifPanel && notifWrap && !notifWrap.closest('.profile-wrap').contains(e.target)) {
    notifPanel.classList.remove('open');
  }
  // Close action menus
  document.querySelectorAll('.action-menu.open,.export-menu.open').forEach(function(m) {
    if (!m.parentElement.contains(e.target)) m.classList.remove('open');
  });
});

/* ── Global Search Toggle ── */
function updateTopbarLayout() {
  const searchEl = document.getElementById('topbarSearch');
  if (!searchEl) return;
  if (window.innerWidth > 768) {
    searchEl.style.display = 'flex';
  } else {
    searchEl.style.display = 'none';
  }
}

window.toggleTopbarSearch = function() {
  const s = document.getElementById('topbarSearch');
  if (!s) return;
  if (window.innerWidth <= 768) {
    const visible = s.style.display === 'flex';
    s.style.display = visible ? 'none' : 'flex';
    if (!visible) s.querySelector('input').focus();
  } else {
    s.querySelector('input').focus();
  }
};

document.addEventListener('DOMContentLoaded', function() {
  updateTopbarLayout();

  /* Global search basic behavior */
  const gs = document.getElementById('globalSearch');
  if (gs) {
    gs.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && gs.value.trim()) {
        window.location.href = '<?= ADMIN_URL ?>/pages/leads.php?search=' + encodeURIComponent(gs.value.trim());
      }
      if (e.key === 'Escape') gs.blur();
    });
  }

  /* Action menus */
  document.querySelectorAll('[data-toggle-menu]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const menuId = btn.dataset.toggleMenu;
      const menu = document.getElementById(menuId);
      if (!menu) return;
      document.querySelectorAll('.action-menu.open,.export-menu.open').forEach(function(m) { if (m !== menu) m.classList.remove('open'); });
      menu.classList.toggle('open');
    });
  });

  /* Bulk checkbox tracker */
  const checkAll = document.getElementById('checkAll');
  if (checkAll) {
    checkAll.addEventListener('change', function() {
      document.querySelectorAll('input[name="ids[]"]').forEach(function(c) { c.checked = checkAll.checked; });
      updateBulkBar();
    });
    document.querySelectorAll('input[name="ids[]"]').forEach(function(c) {
      c.addEventListener('change', updateBulkBar);
    });
  }
});

/* ── Topbar Live Clock ── */
(function initClock() {
  var days    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  var months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  function pad(n){ return String(n).padStart(2,'0'); }
  function tick() {
    var now = new Date();
    var h = document.getElementById('tbH');
    var m = document.getElementById('tbM');
    var s = document.getElementById('tbS');
    var d = document.getElementById('tbDate');
    if (h) h.textContent = pad(now.getHours());
    if (m) m.textContent = pad(now.getMinutes());
    if (s) s.textContent = pad(now.getSeconds());
    if (d) d.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
  }
  tick();
  setInterval(tick, 1000);
})();

function updateBulkBar() {
  const checked = document.querySelectorAll('input[name="ids[]"]:checked').length;
  const bar = document.getElementById('bulkActionBar');
  const countEl = document.getElementById('bulkCount');
  if (!bar) return;
  if (checked > 0) {
    bar.classList.add('visible');
    if (countEl) countEl.textContent = checked + ' item' + (checked > 1 ? 's' : '') + ' selected';
  } else {
    bar.classList.remove('visible');
  }
}
</script>

<!-- ══════════════════════════════════════════════
     REAL-TIME FORM SUBMISSION NOTIFICATIONS
══════════════════════════════════════════════ -->
<div id="agNotifStack" aria-live="polite" aria-label="Form submission notifications"></div>

<style>
/* ── Notification Stack Container ── */
#agNotifStack {
  position: fixed;
  top: 80px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 360px;
  width: calc(100vw - 32px);
  pointer-events: none;
}

/* ── Single Notification Card ── */
.ag-notif {
  background: #fff;
  border-radius: 16px;
  box-shadow:
    0 4px 20px rgba(0,0,0,.12),
    0 12px 40px rgba(0,0,0,.08),
    0 0 0 1px rgba(0,0,0,.05);
  overflow: hidden;
  pointer-events: all;
  transform: translateX(120%);
  opacity: 0;
  transition: transform .4s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
  position: relative;
}
.ag-notif.show {
  transform: translateX(0);
  opacity: 1;
}
.ag-notif.hide {
  transform: translateX(120%);
  opacity: 0;
  transition: transform .3s ease, opacity .25s ease;
}

/* ── Accent top bar ── */
.ag-notif-accent {
  height: 3px;
  background: linear-gradient(90deg, var(--nc, #4f46e5), var(--nc2, #8b5cf6));
  background-size: 200% 100%;
  animation: ncGrad 3s linear infinite;
}
@keyframes ncGrad { to { background-position: -200% 0; } }

/* ── Inner layout ── */
.ag-notif-inner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 14px 10px;
}

/* ── Icon ── */
.ag-notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--nb, rgba(79,70,229,.1));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 17px;
  color: var(--nc, #4f46e5);
  transition: transform .3s cubic-bezier(.34,1.56,.64,1);
}
.ag-notif:hover .ag-notif-icon { transform: scale(1.1) rotate(-5deg); }

/* ── Body ── */
.ag-notif-body { flex: 1; min-width: 0; }

.ag-notif-type {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--nc, #4f46e5);
  margin-bottom: 2px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.ag-notif-type::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--nc, #4f46e5);
  flex-shrink: 0;
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--nc, #4f46e5) 25%, transparent);
  animation: ncPing 1.5s ease-in-out infinite;
}
@keyframes ncPing {
  0%,100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: .6; }
}

.ag-notif-title {
  font-size: 13.5px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.3;
  margin-bottom: 3px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ag-notif-msg {
  font-size: 12px;
  color: #64748b;
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ag-notif-meta {
  font-size: 10.5px;
  color: #94a3b8;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.ag-notif-meta i { font-size: 9px; }

/* ── Actions ── */
.ag-notif-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.ag-notif-view {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 8px;
  background: var(--nc, #4f46e5);
  color: #fff !important;
  font-size: 11.5px;
  font-weight: 700;
  text-decoration: none !important;
  transition: all .2s;
  white-space: nowrap;
}
.ag-notif-view:hover { filter: brightness(1.1); transform: translateY(-1px); }
.ag-notif-close {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  background: #f1f5f9;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 12px;
  transition: all .2s;
  flex-shrink: 0;
}
.ag-notif-close:hover { background: #fee2e2; color: #e11d48; }

/* ── Progress bar ── */
.ag-notif-progress {
  height: 3px;
  background: linear-gradient(90deg, var(--nc, #4f46e5), var(--nc2, #8b5cf6));
  transform-origin: left;
  transition: transform linear;
  margin: 0 14px 10px;
  border-radius: 2px;
  opacity: .5;
}

/* ── Sound wave indicator ── */
.ag-notif-waves {
  display: flex;
  align-items: center;
  gap: 2px;
  height: 14px;
}
.ag-notif-wave {
  width: 3px;
  border-radius: 2px;
  background: var(--nc, #4f46e5);
  opacity: .6;
  animation: waveAnim .8s ease-in-out infinite;
}
.ag-notif-wave:nth-child(2) { animation-delay: .1s; height: 8px; }
.ag-notif-wave:nth-child(3) { animation-delay: .2s; height: 14px; }
.ag-notif-wave:nth-child(4) { animation-delay: .1s; height: 8px; }
.ag-notif-wave:nth-child(5) { animation-delay: .2s; height: 5px; }
@keyframes waveAnim {
  0%,100% { transform: scaleY(.4); }
  50% { transform: scaleY(1); }
}

/* Dark mode */
[data-theme="dark"] .ag-notif {
  background: #1e2540;
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 12px 40px rgba(0,0,0,.3);
}
[data-theme="dark"] .ag-notif-title { color: #e2e8f0; }
[data-theme="dark"] .ag-notif-msg { color: #8892a4; }
[data-theme="dark"] .ag-notif-close { background: #252d45; color: #5a6580; }
[data-theme="dark"] .ag-notif-close:hover { background: rgba(225,29,72,.2); color: #f43f5e; }

/* Mobile */
@media (max-width: 480px) {
  #agNotifStack { top: 68px; right: 10px; left: 10px; width: auto; max-width: 100%; }
  .ag-notif-title { font-size: 12.5px; }
  .ag-notif-msg { font-size: 11px; }
}
</style>

<script>
(function() {
  'use strict';

  var POLL_INTERVAL  = 20000;  /* 20 seconds */
  var AUTO_DISMISS   = 9000;   /* 9 seconds */
  var STORAGE_KEY    = 'ag_seen_notifs';
  var API_URL        = '<?= ADMIN_URL ?>/api/new-submissions.php';
  var lastPollTime   = Math.floor(Date.now() / 1000) - 15; /* check last 15s on load */
  var serverTimeOffset = 0;
  var pollTimer;

  /* ── Type display labels ── */
  var typeLabels = {
    lead:       'New Lead',
    newsletter: 'Newsletter',
    partner:    'Partnership',
  };

  /* ── Get/save seen IDs ── */
  function getSeenIds() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
    catch(e) { return []; }
  }
  function markSeen(id) {
    var seen = getSeenIds();
    if (seen.indexOf(id) === -1) {
      seen.push(id);
      /* keep last 200 */
      if (seen.length > 200) seen = seen.slice(-200);
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(seen)); }
      catch(e) {}
    }
  }

  /* ── Time ago helper ── */
  function timeAgo(dateStr) {
    var diff = Math.floor((Date.now() / 1000 + serverTimeOffset) - new Date(dateStr).getTime() / 1000);
    if (diff < 10) return 'Just now';
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    return Math.floor(diff / 3600) + 'h ago';
  }

  /* ── Build notification card ── */
  function buildCard(n) {
    var card = document.createElement('div');
    card.className = 'ag-notif';
    card.dataset.id = n.id;
    card.style.setProperty('--nc',  n.color || '#4f46e5');
    card.style.setProperty('--nc2', n.color === '#4f46e5' ? '#8b5cf6' : n.color);
    card.style.setProperty('--nb',  n.bg    || 'rgba(79,70,229,.1)');

    var label = typeLabels[n.type] || 'Notification';
    var ago   = timeAgo(n.time);

    card.innerHTML =
      '<div class="ag-notif-accent"></div>' +
      '<div class="ag-notif-inner">' +
        '<div class="ag-notif-icon"><i class="fas ' + (n.icon || 'fa-bell') + '"></i></div>' +
        '<div class="ag-notif-body">' +
          '<div class="ag-notif-type">' +
            '<div class="ag-notif-waves">' +
              '<div class="ag-notif-wave" style="height:5px"></div>' +
              '<div class="ag-notif-wave"></div>' +
              '<div class="ag-notif-wave"></div>' +
              '<div class="ag-notif-wave"></div>' +
              '<div class="ag-notif-wave" style="height:5px"></div>' +
            '</div>' +
            label +
          '</div>' +
          '<div class="ag-notif-title">' + escHtml(n.title) + '</div>' +
          '<div class="ag-notif-msg">'   + escHtml(n.message) + '</div>' +
          '<div class="ag-notif-meta">' +
            '<i class="fas fa-clock"></i>' + ago +
            (n.email ? ' · <i class="fas fa-envelope"></i>' + escHtml(n.email) : '') +
          '</div>' +
        '</div>' +
        '<div class="ag-notif-actions">' +
          '<a href="' + n.url + '" class="ag-notif-view"><i class="fas fa-arrow-right"></i> View</a>' +
          '<button class="ag-notif-close" title="Dismiss"><i class="fas fa-times"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="ag-notif-progress"></div>';

    return card;
  }

  /* ── Show a notification ── */
  function showNotif(n) {
    var seen = getSeenIds();
    if (seen.indexOf(n.id) !== -1) return;
    markSeen(n.id);

    var stack = document.getElementById('agNotifStack');
    if (!stack) return;

    var card = buildCard(n);
    stack.appendChild(card);

    /* Animate in */
    requestAnimationFrame(function() {
      requestAnimationFrame(function() { card.classList.add('show'); });
    });

    /* Progress bar countdown */
    var prog = card.querySelector('.ag-notif-progress');
    if (prog) {
      prog.style.transition = 'none';
      prog.style.transform  = 'scaleX(1)';
      requestAnimationFrame(function() {
        prog.style.transition = 'transform ' + (AUTO_DISMISS / 1000) + 's linear';
        prog.style.transform  = 'scaleX(0)';
      });
    }

    /* Close button */
    var closeBtn = card.querySelector('.ag-notif-close');
    if (closeBtn) closeBtn.addEventListener('click', function() { dismissCard(card); });

    /* View link also closes */
    var viewLink = card.querySelector('.ag-notif-view');
    if (viewLink) viewLink.addEventListener('click', function() {
      setTimeout(function() { dismissCard(card); }, 300);
    });

    /* Auto-dismiss */
    var timer = setTimeout(function() { dismissCard(card); }, AUTO_DISMISS);

    /* Pause on hover */
    card.addEventListener('mouseenter', function() {
      clearTimeout(timer);
      if (prog) prog.style.animationPlayState = 'paused';
    });
    card.addEventListener('mouseleave', function() {
      timer = setTimeout(function() { dismissCard(card); }, 2500);
    });
  }

  /* ── Dismiss a card ── */
  function dismissCard(card) {
    card.classList.remove('show');
    card.classList.add('hide');
    setTimeout(function() {
      if (card.parentNode) card.parentNode.removeChild(card);
    }, 350);
  }

  /* ── HTML escape ── */
  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Poll the API ── */
  function poll() {
    fetch(API_URL + '?since=' + lastPollTime, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.ok) return;

      /* Update server time offset */
      if (data.server_time) {
        serverTimeOffset = data.server_time - Math.floor(Date.now() / 1000);
      }

      /* Update lastPollTime to now */
      lastPollTime = data.server_time || Math.floor(Date.now() / 1000);

      /* Show unseen notifications */
      if (data.notifications && data.notifications.length) {
        /* Stagger multiple notifications */
        data.notifications.forEach(function(n, i) {
          setTimeout(function() { showNotif(n); }, i * 350);
        });
      }
    })
    .catch(function() { /* silently fail — no network errors shown */ });
  }

  /* ── Start polling ── */
  function startPolling() {
    poll(); /* immediate first check */
    pollTimer = setInterval(poll, POLL_INTERVAL);
  }

  /* ── Stop polling when tab hidden (save bandwidth) ── */
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      clearInterval(pollTimer);
    } else {
      lastPollTime = Math.floor(Date.now() / 1000) - 5;
      startPolling();
    }
  });

  /* ── Boot after DOM ready ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startPolling);
  } else {
    startPolling();
  }

})();

/* ── Sidebar: responsive tiers, drawer a11y ──────────────
   Adds what the original toggles lacked:
   · an auto-collapse band (769-1199px) where the full rail crowded the
     content area, applied as a separate class so an explicit user
     choice is never overwritten
   · Escape closes the drawer and returns focus to the burger
   · body scroll lock while the drawer is open
   · aria-expanded kept in sync on the burger                        */
(function sidebarResponsive() {
  var BREAK_DRAWER = 768;    /* at or below: off-canvas */
  var BREAK_AUTO   = 1199;   /* at or below: collapse to icons */

  var body    = document.body;
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var burger  = document.getElementById('hxBurger') ||
                document.querySelector('.hamburger-admin');
  if (!sidebar) return;

  function isDrawer() { return window.innerWidth <= BREAK_DRAWER; }

  function applyTier() {
    var w = window.innerWidth;
    if (w > BREAK_DRAWER && w <= BREAK_AUTO) {
      body.classList.add('sidebar-auto-mini');
    } else {
      body.classList.remove('sidebar-auto-mini');
    }
    if (isDrawer()) {
      /* the collapsed rail has no meaning inside a drawer */
      body.classList.remove('sidebar-mini');
    } else {
      try {
        if (localStorage.getItem('ag_sidebar_mini') === '1') {
          body.classList.add('sidebar-mini');
        }
      } catch (e) {}
      closeDrawer(false);
    }
  }

  function openDrawer() {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    body.classList.add('drawer-open');
    sidebar.setAttribute('aria-hidden', 'false');
    if (burger) burger.setAttribute('aria-expanded', 'true');
  }

  function closeDrawer(restoreFocus) {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    body.classList.remove('drawer-open');
    sidebar.setAttribute('aria-hidden', isDrawer() ? 'true' : 'false');
    if (burger) {
      burger.setAttribute('aria-expanded', 'false');
      if (restoreFocus) burger.focus();
    }
  }

  /* Wrap the existing globals so callers and inline onclick keep working */
  var priorToggle = window.toggleSidebar;
  window.toggleSidebar = function () {
    if (isDrawer()) {
      sidebar.classList.contains('open') ? closeDrawer(true) : openDrawer();
    } else if (typeof window.toggleMini === 'function') {
      window.toggleMini();
    } else if (typeof priorToggle === 'function') {
      priorToggle();
    }
  };
  window.closeSidebar = function () { closeDrawer(false); };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) closeDrawer(true);
  });

  /* Following a link inside the drawer should close it */
  sidebar.addEventListener('click', function (e) {
    if (isDrawer() && e.target.closest('a[href]')) closeDrawer(false);
  });

  var t;
  window.addEventListener('resize', function () {
    clearTimeout(t);
    t = setTimeout(applyTier, 120);
  });

  applyTier();
})();

/* ── Accordion: one group open at a time, height animated ── */
(function navAccordion() {
  var nav = document.getElementById('sidebarNav');
  if (!nav) return;

  /* The CSS animates grid-template-rows, which needs a single child to
     collapse against. Wrap the links once, here, rather than in markup. */
  nav.querySelectorAll('.nav-sub').forEach(function (sub) {
    if (sub.firstElementChild && sub.firstElementChild.classList.contains('nav-sub-inner')) return;
    var inner = document.createElement('div');
    inner.className = 'nav-sub-inner';
    while (sub.firstChild) inner.appendChild(sub.firstChild);
    sub.appendChild(inner);
  });

  window.toggleGroup = function (btn) {
    var sub  = btn.nextElementSibling;
    var open = btn.classList.contains('open');

    if (!open) {
      nav.querySelectorAll('.nav-parent.open').forEach(function (other) {
        if (other === btn) return;
        other.classList.remove('open');
        other.setAttribute('aria-expanded', 'false');
        if (other.nextElementSibling) other.nextElementSibling.classList.remove('open');
      });
    }
    btn.classList.toggle('open', !open);
    btn.setAttribute('aria-expanded', String(!open));
    if (sub) sub.classList.toggle('open', !open);
  };
})();

</script>
</body>
</html>

<script>
/* Section collapse in the rail. Sections start open; the chevron is for
   tidying a long list, not a gate in front of links. Choice persists. */
(function () {
  var KEY = 'ag_nav_sections';
  function read() { try { return JSON.parse(localStorage.getItem(KEY) || '{}'); } catch (e) { return {}; } }
  function write(o) { try { localStorage.setItem(KEY, JSON.stringify(o)); } catch (e) {} }

  window.toggleNavSection = function (btn) {
    var body = document.getElementById(btn.getAttribute('aria-controls'));
    if (!body) return;
    var open = btn.getAttribute('aria-expanded') !== 'false';
    btn.setAttribute('aria-expanded', String(!open));
    body.classList.toggle('is-collapsed', open);
    var st = read(); st[body.id] = !open; write(st);
  };

  var st = read();
  document.querySelectorAll('.nav-sec-label').forEach(function (btn) {
    var id = btn.getAttribute('aria-controls');
    var body = document.getElementById(id);
    if (!body) return;
    /* never hide the section holding the current page */
    if (body.querySelector('.nav-link.active')) return;
    if (st[id] === false) {
      btn.setAttribute('aria-expanded', 'false');
      body.classList.add('is-collapsed');
    }
  });
})();
</script>
