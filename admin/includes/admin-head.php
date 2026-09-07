<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= e($adminTitle) ?> — <?= e($siteName) ?> Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin-tokens.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin-modern.css">
<!-- admin-sidebar-premium.css retired: 301 !important rules pinning the old
 navy/blue look (edge glows, shimmer tracks, per-group accent tints). It held
 no layout, only decoration, and it blocked every re-skin. See admin-shell.css. -->
<?php /* The Tailwind play CDN used to load here: a ~400 KB runtime
       compiler, warned against in production, for four utility classes
       (mt-4, mb-3) that are now plain styles. */ ?>
<style>
/* ═══════════════════════════════════════════════════
   APPSGAIN ADMIN PANEL  — Premium UI v2
═══════════════════════════════════════════════════ */
/* Design tokens now live in css/admin-tokens.css — one source of truth.
   Legacy names (--violet, --blue, --gray …) are aliased there, so every
   rule below keeps working while it migrates to the new token names. */

*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-body);background:var(--canvas);color:var(--text);min-height:100vh;display:flex;font-size:14px;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
img{max-width:100%}

/* ════════════════════════════
   SIDEBAR — Creative v3
════════════════════════════ */
@keyframes iconBounce{0%,100%{transform:translateY(0) scale(1)}40%{transform:translateY(-3px) scale(1.1)}70%{transform:translateY(1px)}}
@keyframes iconSpin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
@keyframes iconPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}
@keyframes glowPulse{0%,100%{box-shadow:0 0 0 0 var(--gc-glow,rgba(124,58,237,.5))}50%{box-shadow:0 0 14px 3px transparent}}
@keyframes colonBlink{0%,49%{opacity:1}50%,100%{opacity:.2}}
@keyframes subSlide{from{opacity:0;transform:translateX(-6px)}to{opacity:1;transform:none}}

.sidebar{
  width:var(--sidebar-w);
  background:linear-gradient(175deg,#0d1e3d 0%,#091832 55%,#05101e 100%);
  height:100vh;position:fixed;top:0;left:0;z-index:200;
  display:flex;flex-direction:column;
  transition:width var(--transition);
  overflow-x:hidden;overflow-y:hidden;
  box-shadow:4px 0 40px rgba(0,0,0,.3);
}
.sidebar::after{
  content:'';position:absolute;top:0;right:0;bottom:0;width:1px;
  background:linear-gradient(180deg,transparent,rgba(37,99,235,.55) 30%,rgba(59,130,246,.3) 70%,transparent);
  pointer-events:none;
}

/* ── Sidebar Logo ── */
.sidebar-logo{
  padding:0 16px;
  height:var(--topbar-h);
  border-bottom:1px solid rgba(255,255,255,.05);
  display:flex;align-items:center;gap:12px;
  flex-shrink:0;overflow:hidden;white-space:nowrap;
  background:rgba(255,255,255,.015);
}
.sidebar-logo .logo-icon{
  width:38px;height:38px;min-width:38px;
  background:linear-gradient(135deg,#1d4ed8,#3b82f6);
  border-radius:11px;display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:900;color:#fff;
  box-shadow:0 4px 18px rgba(37,99,235,.5),0 0 0 2px rgba(37,99,235,.25);
  transition:var(--transition);flex-shrink:0;
  --gc-glow:rgba(37,99,235,.55);
  animation:glowPulse 3s ease-in-out infinite;
}
.sidebar-logo:hover .logo-icon{transform:rotate(-8deg) scale(1.08)}
.sidebar-logo .logo-text{color:#fff;font-size:14.5px;font-weight:800;line-height:1.25;opacity:1;transition:var(--transition)}
.sidebar-logo .logo-text span{color:rgba(255,255,255,.32);font-size:10px;display:block;font-weight:500;margin-top:1px;letter-spacing:.6px;text-transform:uppercase}

/* ── Nav ── */
.sidebar-nav{
  flex:1;min-height:0;
  overflow-y:auto;overflow-x:hidden;
  padding:10px 0 8px;
  scrollbar-width:thin;scrollbar-color:rgba(37,99,235,.35) transparent;
}
.sidebar-nav::-webkit-scrollbar{width:4px}
.sidebar-nav::-webkit-scrollbar-track{background:rgba(255,255,255,.02)}
.sidebar-nav::-webkit-scrollbar-thumb{background:linear-gradient(180deg,rgba(37,99,235,.5),rgba(59,130,246,.3));border-radius:4px}
.sidebar-nav::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,rgba(37,99,235,.8),rgba(59,130,246,.6))}
.nav-section{padding:0 10px;margin-bottom:2px}

.nav-section-label{
  font-size:8.5px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
  color:rgba(255,255,255,.18);padding:14px 10px 5px;
  white-space:nowrap;overflow:hidden;transition:var(--transition);
  display:flex;align-items:center;gap:8px;
}
.nav-section-label::before{
  content:'';display:inline-block;width:16px;height:2px;border-radius:2px;
  background:rgba(255,255,255,.12);flex-shrink:0;transition:width .3s;
}
/* ── Icon Box ── */
.nav-icon-box{
  width:34px;height:34px;min-width:34px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;font-size:14px;
  background:var(--gc,rgba(37,99,235,.18));
  transition:var(--transition);flex-shrink:0;color:rgba(255,255,255,.7);
}

/* ── Nav Item (direct links like Dashboard) ── */
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:10px;
  color:rgba(255,255,255,.5);font-size:13px;font-weight:500;
  transition:var(--transition);cursor:pointer;
  position:relative;white-space:nowrap;overflow:hidden;
  margin-bottom:2px;
}
.nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.9)}
.nav-item:hover .nav-icon-box{background:var(--gc,rgba(37,99,235,.4));box-shadow:0 4px 14px var(--gc,rgba(37,99,235,.3));color:#fff}
.nav-item.active{
  background:linear-gradient(135deg,rgba(37,99,235,.25) 0%,rgba(37,99,235,.12) 100%);
  color:#fff;border:1px solid rgba(37,99,235,.25);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
}
.nav-item.active::before{
  content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
  width:3px;height:55%;background:linear-gradient(180deg,#2563eb,#60a5fa);
  border-radius:0 3px 3px 0;
}
.nav-item.active .nav-icon-box{background:linear-gradient(135deg,#1d4ed8,#3b82f6);box-shadow:0 3px 12px rgba(37,99,235,.4);color:#fff}
.nav-item .nav-label{flex:1;overflow:hidden;text-overflow:ellipsis;transition:var(--transition)}
.nav-item .badge{
  margin-left:auto;background:var(--rose);color:#fff;
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;
  line-height:1.5;flex-shrink:0;transition:var(--transition);
}
.nav-item .badge.amber{background:var(--amber)}
.nav-item .badge.violet{background:var(--violet)}

/* ── Sidebar Footer ── */
.sidebar-footer{padding:10px;border-top:1px solid rgba(255,255,255,.05);flex-shrink:0;background:rgba(0,0,0,.12)}
.sidebar-footer-actions{display:flex;gap:6px;margin-bottom:8px}
.sf-btn{
  flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
  padding:8px 10px;border-radius:9px;
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
  color:rgba(255,255,255,.32);font-size:11px;font-weight:600;
  cursor:pointer;transition:var(--transition);white-space:nowrap;overflow:hidden;
  font-family:inherit;
}
.sf-btn:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)}
.sf-btn.sf-theme:hover{background:rgba(37,99,235,.18);color:#60a5fa;border-color:rgba(37,99,235,.3)}
.sf-btn.sf-collapse:hover{background:rgba(59,130,246,.12);color:#93c5fd;border-color:rgba(59,130,246,.25)}
.sf-btn i{font-size:12px;flex-shrink:0;transition:transform var(--transition)}
.sf-btn.sf-collapse i{transition:transform var(--transition)}
body.sidebar-mini .sf-btn.sf-collapse i{transform:rotate(180deg)}
.sf-btn .sf-label{transition:var(--transition)}
.sidebar-user{display:flex;align-items:center;gap:10px;padding:8px;border-radius:10px;overflow:hidden;white-space:nowrap;transition:var(--transition)}
.sidebar-user:hover{background:rgba(255,255,255,.05)}
.sidebar-avatar{
  width:34px;height:34px;min-width:34px;border-radius:50%;
  background:linear-gradient(135deg,#1d4ed8,#3b82f6);
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:14px;font-weight:700;overflow:hidden;
  box-shadow:0 0 0 2px rgba(37,99,235,.35);
}
.sidebar-avatar img{width:100%;height:100%;object-fit:cover}
.sidebar-user-info .name{color:#fff;font-size:12.5px;font-weight:700;line-height:1.2}
.sidebar-user-info .role{color:rgba(255,255,255,.28);font-size:10.5px;text-transform:capitalize;margin-top:2px}

/* ════════════════════════════
   SIDEBAR MINI (Collapsed)
════════════════════════════ */
body.sidebar-mini .sidebar{width:var(--sidebar-mini)}
body.sidebar-mini .sidebar-logo .logo-text{opacity:0;width:0;overflow:hidden}
body.sidebar-mini .nav-item{padding:9px;justify-content:center;gap:0}
body.sidebar-mini .nav-item .nav-label,
body.sidebar-mini .nav-item .badge{opacity:0;width:0;overflow:hidden;padding:0;margin:0}
body.sidebar-mini .nav-item.active::before{display:none}
body.sidebar-mini .nav-item .nav-icon-box{width:32px;height:32px;border-radius:9px}
body.sidebar-mini .nav-item .nav-icon-box i{font-size:14px}
body.sidebar-mini .sf-btn .sf-label{opacity:0;width:0;overflow:hidden;padding:0}
body.sidebar-mini .sf-btn{padding:8px;justify-content:center}
body.sidebar-mini .main-area{margin-left:var(--sidebar-mini)}

/* Tooltips in mini mode — nav-item (Dashboard) */
body.sidebar-mini .nav-item{position:relative}
body.sidebar-mini .nav-item::after{
  content:attr(data-tip);
  position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);
  background:#0d1e3d;color:#fff;font-size:12px;font-weight:600;
  padding:6px 12px;border-radius:9px;white-space:nowrap;
  opacity:0;pointer-events:none;transition:opacity .18s;
  box-shadow:0 4px 20px rgba(0,0,0,.4);z-index:300;
  border:1px solid rgba(37,99,235,.2);
}
body.sidebar-mini .nav-item:hover::after{opacity:1}

/* ════════════════════════════
   MAIN AREA
════════════════════════════ */
.main-area{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left var(--transition)}

/* ════════════════════════════
   TOPBAR
════════════════════════════ */
.topbar{
  height:var(--topbar-h);background:var(--white);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 20px 0 20px;gap:10px;
  position:sticky;top:0;z-index:150;
  box-shadow:0 1px 0 var(--border),0 4px 16px rgba(0,0,0,.04);
}
.topbar-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0}

/* Hamburger — always visible */
.hamburger-admin{
  background:none;border:none;cursor:pointer;
  width:38px;height:38px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  color:var(--gray);font-size:17px;transition:var(--transition);flex-shrink:0;
}
.hamburger-admin:hover{background:var(--light2);color:var(--violet)}

.topbar-brand{display:none;align-items:center;gap:10px}
.topbar-brand .tb-icon{width:32px;height:32px;background:linear-gradient(135deg,var(--violet),var(--blue));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;color:#fff}
.topbar-brand .tb-name{font-size:14px;font-weight:800;color:var(--primary)}

/* Page title + clock block */
.topbar-title-wrap{display:flex;flex-direction:column;justify-content:center;gap:2px}
.topbar .page-title{font-size:17px;font-weight:800;color:var(--primary);line-height:1.2;white-space:nowrap}
/* Page headings are their own thing, and must wrap */
.page-content .page-title{font-family:var(--font-display);font-size:clamp(20px,2.2vw,26px);font-weight:800;letter-spacing:-.02em;color:var(--ink);line-height:1.2;white-space:normal;margin:0}
.topbar-clock{display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--gray);font-weight:500;white-space:nowrap}
.topbar-clock i{font-size:10px;color:var(--violet);opacity:.7}
.tb-colon{animation:colonBlink 1s step-start infinite;display:inline-block}

.topbar-right{display:flex;align-items:center;gap:6px;flex-shrink:0}

/* Theme toggle pill in topbar */
.topbar-theme-pill{
  display:flex;align-items:center;gap:7px;
  padding:6px 12px;border-radius:20px;
  background:var(--light);border:1.5px solid var(--border);
  color:var(--gray);font-size:12px;font-weight:600;cursor:pointer;
  transition:var(--transition);white-space:nowrap;
}
.topbar-theme-pill:hover{background:var(--violet-lt);border-color:rgba(124,58,237,.25);color:var(--violet)}
.topbar-theme-pill i{font-size:13px;transition:transform .4s ease}
[data-theme="dark"] .topbar-theme-pill i{transform:rotate(180deg)}

.topbar-btn{
  width:38px;height:38px;border-radius:10px;
  background:var(--light);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--gray);font-size:15px;cursor:pointer;
  transition:var(--transition);position:relative;
}
.topbar-btn:hover{background:var(--violet-lt);border-color:rgba(124,58,237,.2);color:var(--violet)}
.notif-dot{position:absolute;top:7px;right:7px;width:7px;height:7px;background:var(--rose);border-radius:50%;border:2px solid var(--white)}

.topbar-profile{
  display:flex;align-items:center;gap:9px;
  padding:5px 14px 5px 5px;
  background:var(--light);border:1.5px solid var(--border);
  border-radius:40px;cursor:pointer;transition:var(--transition);
  margin-left:4px;
}
.topbar-profile:hover{border-color:var(--violet);background:var(--violet-lt)}
.topbar-profile .tp-avatar{
  width:30px;height:30px;border-radius:50%;
  background:linear-gradient(135deg,var(--violet),var(--blue));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:12px;font-weight:700;overflow:hidden;
}
.topbar-profile .tp-avatar img{width:100%;height:100%;object-fit:cover}
.topbar-profile .tp-info .tp-name{font-size:13px;font-weight:700;color:var(--primary);line-height:1.2}
.topbar-profile .tp-info .tp-role{font-size:11px;color:var(--gray);text-transform:capitalize}

/* ── Profile Dropdown ── */
.dropdown-menu{
  position:absolute;top:calc(100% + 10px);right:0;
  background:var(--white);border:1px solid var(--border);
  border-radius:14px;box-shadow:var(--shadow-lg);
  min-width:210px;padding:6px;z-index:500;
  display:none;animation:dropIn .18s ease;
}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
.dropdown-menu.open{display:block}
.dropdown-profile-header{padding:14px 14px 10px;border-bottom:1px solid var(--border);margin-bottom:4px}
.dropdown-profile-header .dp-name{font-size:14px;font-weight:700;color:var(--primary)}
.dropdown-profile-header .dp-email{font-size:12px;color:var(--gray);margin-top:2px}
.dropdown-item{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:9px;
  color:var(--text);font-size:13.5px;transition:var(--transition);cursor:pointer;
}
.dropdown-item:hover{background:var(--light2);color:var(--violet)}
.dropdown-item.danger:hover{background:rgba(225,29,72,.07);color:var(--rose)}
.dropdown-item i{width:16px;text-align:center;font-size:13px}
.dropdown-divider{height:1px;background:var(--border);margin:4px 0}
.profile-wrap{position:relative}

/* ════════════════════════════
   PAGE CONTENT
════════════════════════════ */
.page-content{padding:26px;flex:1}
.page-header{
  display:flex;align-items:flex-start;justify-content:space-between;
  margin-bottom:24px;gap:16px;flex-wrap:wrap;
}
.page-header h1{font-size:22px;font-weight:800;color:var(--primary);line-height:1.2}
.page-header p{font-size:13.5px;color:var(--gray);margin-top:4px}
.page-header-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex-shrink:0}

/* ════════════════════════════
   CARDS
════════════════════════════ */
.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--border);box-shadow:var(--shadow)}
.card:hover{box-shadow:var(--shadow-md)}
.card-header{
  padding:16px 22px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.card-header h3{font-size:15px;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:8px}
.card-body{padding:22px}
.card-footer{
  padding:13px 22px;border-top:1px solid var(--border);
  background:rgba(244,247,255,.6);border-radius:0 0 var(--radius-lg) var(--radius-lg);
}

/* ════════════════════════════
   STAT CARDS
════════════════════════════ */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:26px}
.stat-card{
  background:var(--white);border-radius:var(--radius-lg);padding:20px;
  border:1px solid var(--border);box-shadow:var(--shadow);
  display:flex;align-items:center;gap:14px;transition:var(--transition);
  position:relative;overflow:hidden;
}
.stat-card::after{
  content:'';position:absolute;top:0;right:0;
  width:80px;height:80px;border-radius:50%;
  background:radial-gradient(circle,var(--stat-color,rgba(124,58,237,.07)) 0%,transparent 70%);
  transform:translate(20px,-20px);
}
.stat-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.stat-icon{
  width:50px;height:50px;min-width:50px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;color:#fff;box-shadow:0 4px 14px var(--stat-shadow,rgba(124,58,237,.3));
  transition:var(--transition);
}
.stat-card:hover .stat-icon{transform:scale(1.08)}
.stat-info .label{font-size:11.5px;color:var(--gray);font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px}
.stat-info .value{font-size:26px;font-weight:900;color:var(--primary);line-height:1;letter-spacing:-.5px}
.stat-info .change{font-size:12px;margin-top:5px;font-weight:600}
.stat-info .change.up{color:var(--emerald)}
.stat-info .change.down{color:var(--rose)}
.stat-info .change.neutral{color:var(--gray)}

/* ════════════════════════════
   TABLE
════════════════════════════ */
.table-wrap{overflow-x:auto;border-radius:var(--radius-lg)}
table{width:100%;border-collapse:collapse}
thead th{
  background:var(--light);padding:11px 16px;
  text-align:left;font-size:11.5px;font-weight:700;
  color:var(--gray);text-transform:uppercase;letter-spacing:.8px;
  border-bottom:1px solid var(--border);white-space:nowrap;
}
thead th:first-child{border-radius:var(--radius-lg) 0 0 0}
thead th:last-child{border-radius:0 var(--radius-lg) 0 0}
tbody td{padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.035);font-size:13.5px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(124,58,237,.018)}

/* ════════════════════════════
   BADGES
════════════════════════════ */
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;line-height:1;white-space:nowrap}
.badge-success{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.2)}
.badge-warning{background:rgba(217,119,6,.1);color:#d97706;border:1px solid rgba(217,119,6,.2)}
.badge-danger{background:rgba(225,29,72,.1);color:#e11d48;border:1px solid rgba(225,29,72,.2)}
.badge-info{background:rgba(37,99,235,.1);color:#2563eb;border:1px solid rgba(37,99,235,.15)}
.badge-secondary{background:rgba(107,114,128,.1);color:#6b7280;border:1px solid rgba(107,114,128,.15)}
.badge-violet{background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.15)}

/* ════════════════════════════
   BUTTONS
════════════════════════════ */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 18px;border-radius:10px;font-size:13.5px;font-weight:600;
  cursor:pointer;transition:var(--transition);border:none;white-space:nowrap;
  font-family:inherit;letter-spacing:.1px;
}
.btn-primary{background:linear-gradient(135deg,var(--violet),var(--blue));color:#fff;box-shadow:0 4px 14px rgba(124,58,237,.3)}
.btn-primary:hover{box-shadow:0 6px 20px rgba(124,58,237,.45);transform:translateY(-1px)}
.btn-success{background:var(--emerald);color:#fff;box-shadow:0 4px 12px rgba(5,150,105,.25)}
.btn-success:hover{background:#047857;transform:translateY(-1px)}
.btn-danger{background:var(--rose);color:#fff;box-shadow:0 4px 12px rgba(225,29,72,.25)}
.btn-danger:hover{background:#be123c;transform:translateY(-1px)}
.btn-warning{background:var(--amber);color:#fff}
.btn-warning:hover{background:#b45309}
.btn-secondary{background:var(--light);color:var(--text);border:1.5px solid var(--border)}
.btn-secondary:hover{background:var(--light2);border-color:rgba(124,58,237,.2);color:var(--violet)}
.btn-outline{background:transparent;border:1.5px solid var(--violet);color:var(--violet)}
.btn-outline:hover{background:var(--violet);color:#fff}
.btn-sm{padding:6px 13px;font-size:12.5px;border-radius:8px}
.btn-icon{width:34px;height:34px;padding:0;justify-content:center}

/* ════════════════════════════
   FORMS
════════════════════════════ */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:12.5px;font-weight:700;color:var(--primary);margin-bottom:7px;text-transform:uppercase;letter-spacing:.4px}
.form-group label .required{color:var(--rose)}
.form-control{
  width:100%;padding:10px 14px;border:1.5px solid var(--border);
  border-radius:10px;font-size:13.5px;color:var(--text);
  background:#fff;outline:none;transition:var(--transition);font-family:inherit;
}
.form-control:focus{border-color:var(--violet);box-shadow:0 0 0 4px rgba(124,58,237,.08)}
.form-control:hover:not(:focus){border-color:#c4cfe4}
select.form-control{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;background-size:12px;padding-right:38px}
textarea.form-control{resize:vertical;min-height:100px}
.form-hint{font-size:12px;color:var(--gray);margin-top:6px}
.form-error{font-size:12px;color:var(--rose);margin-top:5px;display:flex;align-items:center;gap:4px}
.input-group{display:flex;gap:0}
.input-group .form-control{border-radius:10px 0 0 10px;border-right:none}
.input-group .btn{border-radius:0 10px 10px 0}
.toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#d1d5db;border-radius:24px;transition:.3s;cursor:pointer}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.toggle-switch input:checked+.toggle-slider{background:var(--violet)}
.toggle-switch input:checked+.toggle-slider::before{transform:translateX(20px)}

/* ════════════════════════════
   ALERTS
════════════════════════════ */
.alert-success,.alert-error,.alert-warning,.alert-info{
  padding:13px 16px;border-radius:12px;font-size:13.5px;
  margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:500;
}
.alert-success{background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.25);color:#065f46}
.alert-error{background:rgba(225,29,72,.08);border:1px solid rgba(225,29,72,.25);color:#9f1239}
.alert-warning{background:rgba(217,119,6,.08);border:1px solid rgba(217,119,6,.25);color:#92400e}
.alert-info{background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);color:#1e3a8a}

/* ════════════════════════════
   PAGINATION
════════════════════════════ */
.pagination{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
.pagination a,.pagination span{padding:7px 13px;border-radius:9px;font-size:13px;font-weight:600;border:1.5px solid var(--border);transition:var(--transition);color:var(--text)}
.pagination a:hover{border-color:var(--violet);color:var(--violet);background:var(--violet-lt)}
.pagination .active{background:var(--violet);border-color:var(--violet);color:#fff;box-shadow:0 3px 10px rgba(124,58,237,.3)}
.pagination .disabled{opacity:.4;cursor:default;pointer-events:none}

/* ════════════════════════════
   SEARCH / FILTER
════════════════════════════ */
.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.search-wrap{position:relative;flex:1;min-width:200px}
.search-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--gray2);font-size:14px}
.search-input{
  width:100%;padding:9px 14px 9px 40px;
  border:1.5px solid var(--border);border-radius:10px;
  font-size:13.5px;outline:none;transition:var(--transition);font-family:inherit;
  background:#fff;
}
.search-input:focus{border-color:var(--violet);box-shadow:0 0 0 4px rgba(124,58,237,.07)}

/* ════════════════════════════
   IMAGE PREVIEW
════════════════════════════ */
.img-preview-wrap{position:relative;display:inline-block}
.img-preview{width:90px;height:90px;object-fit:cover;border-radius:12px;border:2px solid var(--border)}
.img-preview-remove{
  position:absolute;top:-8px;right:-8px;
  width:22px;height:22px;background:var(--rose);
  color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:10px;cursor:pointer;border:2px solid #fff;
  box-shadow:0 2px 6px rgba(225,29,72,.3);
}

/* ════════════════════════════
   SIDEBAR OVERLAY
════════════════════════════ */
.sidebar-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.45);
  z-index:299;
  display:none;
  /* No backdrop-filter blur — keeps main content readable */
}
.sidebar-overlay.active{display:block}

/* ════════════════════════════
   RESPONSIVE
════════════════════════════ */
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .form-row{grid-template-columns:1fr}
  .form-row-3{grid-template-columns:1fr}
}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);height:100dvh}
  .sidebar.open{transform:none}
  body.sidebar-mini .sidebar{width:var(--sidebar-w);transform:translateX(-100%)}
  body.sidebar-mini .sidebar.open{transform:none}
  .main-area,.body.sidebar-mini .main-area{margin-left:0}
  .topbar-brand{display:flex}
  /* Hide clock text + search bar on mobile to save space */
  .topbar-clock{display:none}
  .topbar-search{display:none!important}
  .topbar-theme-pill span{display:none}
  .topbar-theme-pill{padding:7px}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .page-content{padding:14px}
  .topbar{padding:0 12px;gap:6px}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .page-title{font-size:15px}
}

/* ════════════════════════════
   SIDEBAR PROFILE CARD
════════════════════════════ */
.sidebar-profile-card{
  padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.07);
  display:flex;align-items:center;gap:11px;flex-shrink:0;overflow:hidden;
  background:rgba(37,99,235,.06);transition:var(--transition);
  white-space:nowrap;
}
.spc-avatar{
  width:40px;height:40px;min-width:40px;border-radius:50%;
  background:linear-gradient(135deg,#1d4ed8,#3b82f6);
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:16px;font-weight:800;overflow:hidden;flex-shrink:0;
  box-shadow:0 0 0 2px rgba(37,99,235,.5),0 3px 14px rgba(37,99,235,.3);
}
.spc-avatar img{width:100%;height:100%;object-fit:cover}
.spc-info{flex:1;overflow:hidden;transition:var(--transition)}
.spc-name{color:#fff;font-size:13px;font-weight:700;line-height:1.2;overflow:hidden;text-overflow:ellipsis}
.spc-sub{color:rgba(255,255,255,.3);font-size:10px;font-weight:500;margin-top:1px;overflow:hidden;text-overflow:ellipsis}
.spc-role{
  margin-top:5px;display:inline-flex;align-items:center;gap:4px;
  padding:2px 8px;border-radius:20px;
  background:rgba(37,99,235,.22);border:1px solid rgba(37,99,235,.35);
  color:#93c5fd;font-size:9px;font-weight:800;letter-spacing:.4px;text-transform:capitalize;
}

/* ════════════════════════════
   COLLAPSIBLE NAV GROUPS
════════════════════════════ */
.nav-group{padding:0 10px;margin-bottom:2px}

.nav-group-label{
  font-size:8.5px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
  color:rgba(255,255,255,.18);padding:12px 10px 4px;
  white-space:nowrap;overflow:hidden;transition:var(--transition);
  display:flex;align-items:center;gap:8px;
}
.nav-group-label::before{
  content:'';display:inline-block;width:16px;height:2px;border-radius:2px;
  background:rgba(255,255,255,.12);flex-shrink:0;
}

/* Nav parent button (accordion trigger) */
.nav-parent{
  width:100%;display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:10px;
  color:rgba(255,255,255,.55);font-size:13px;font-weight:600;
  background:transparent;border:none;cursor:pointer;
  transition:var(--transition);position:relative;
  text-align:left;white-space:nowrap;overflow:hidden;font-family:inherit;
  margin-bottom:1px;
}
.nav-parent:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.05)}
.nav-parent.open{
  color:#fff;
  background:linear-gradient(90deg,var(--gc,rgba(37,99,235,.22)),rgba(37,99,235,.05));
}
.nav-parent .nav-icon-box{
  width:34px;height:34px;min-width:34px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  background:var(--gc,rgba(37,99,235,.15));
  transition:var(--transition);flex-shrink:0;color:rgba(255,255,255,.65);font-size:14px;
}
.nav-parent:hover .nav-icon-box,.nav-parent.open .nav-icon-box{
  background:var(--gc,rgba(37,99,235,.42));color:#fff;
  box-shadow:0 4px 14px var(--gc,rgba(37,99,235,.28));
}
.nav-parent .nav-label{flex:1;overflow:hidden;text-overflow:ellipsis}
.nav-parent .nav-badge{
  background:var(--rose);color:#fff;font-size:9.5px;font-weight:700;
  padding:2px 6px;border-radius:20px;flex-shrink:0;line-height:1.4;
}
.nav-arrow{
  font-size:10px;color:rgba(255,255,255,.22);
  transition:transform var(--transition);flex-shrink:0;
}
.nav-parent.open .nav-arrow{transform:rotate(180deg);color:rgba(255,255,255,.55)}

/* Collapsible sub-list */
.nav-sub{
  max-height:0;overflow:hidden;
  transition:max-height .35s cubic-bezier(.4,0,.2,1);
}
.nav-sub.open{max-height:900px}

/* Sub-items */
.nav-sub-item{
  display:flex;align-items:center;gap:9px;
  padding:6px 10px 6px 20px;border-radius:8px;
  color:rgba(255,255,255,.38);font-size:12.5px;font-weight:500;
  transition:var(--transition);text-decoration:none;
  margin-bottom:1px;white-space:nowrap;position:relative;overflow:hidden;
}
.nav-sub-item::before{
  content:'';position:absolute;left:9px;top:50%;transform:translateY(-50%);
  width:4px;height:4px;border-radius:50%;
  background:rgba(255,255,255,.12);transition:var(--transition);
}
.nav-sub-item:hover{color:rgba(255,255,255,.88);background:rgba(255,255,255,.05)}
.nav-sub-item:hover::before{background:var(--gc,rgba(59,130,246,.85));transform:translateY(-50%) scale(1.4)}
.nav-sub-item.active{
  color:#fff;
  background:linear-gradient(90deg,rgba(37,99,235,.2),rgba(37,99,235,.06));
}
.nav-sub-item.active::before{
  background:var(--gc,#60a5fa);width:5px;height:5px;
}
.nav-sub-item i{width:14px;min-width:14px;text-align:center;font-size:11px;opacity:.5;transition:var(--transition)}
.nav-sub-item:hover i,.nav-sub-item.active i{opacity:.85}
.nav-sub-item .badge{margin-left:auto;color:#fff;font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:20px;flex-shrink:0;background:var(--rose)}

/* Utility items (View Website / Logout) */
.nav-util-item{
  display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;
  color:rgba(255,255,255,.35);font-size:12.5px;font-weight:500;
  transition:var(--transition);text-decoration:none;margin-bottom:2px;white-space:nowrap;
}
.nav-util-item:hover{background:rgba(255,255,255,.05);color:rgba(255,255,255,.72)}
.nav-util-item.danger{color:rgba(225,29,72,.5)}
.nav-util-item.danger:hover{background:rgba(225,29,72,.08);color:#f43f5e}
.nav-util-item i{width:15px;text-align:center;font-size:12px;flex-shrink:0}

/* Divider */
.nav-divider{height:1px;background:rgba(255,255,255,.06);margin:8px 10px}

/* ════════════════════════════
   MINI MODE (collapsible sidebar)
════════════════════════════ */
body.sidebar-mini .nav-group{padding:0 6px}
body.sidebar-mini .nav-sub{display:none!important}
body.sidebar-mini .nav-group-label{opacity:0;height:0;padding:0;overflow:hidden;margin:0}
body.sidebar-mini .nav-parent{padding:8px;justify-content:center;gap:0;margin-bottom:2px}
body.sidebar-mini .nav-parent .nav-label,
body.sidebar-mini .nav-parent .nav-badge,
body.sidebar-mini .nav-parent .nav-arrow{display:none}
body.sidebar-mini .nav-parent .nav-icon-box{width:36px;height:36px;border-radius:10px}
body.sidebar-mini .nav-divider{margin:4px 6px}
body.sidebar-mini .nav-util-item{padding:9px;justify-content:center;gap:0}
body.sidebar-mini .nav-util-item span{display:none}
body.sidebar-mini .nav-util-item i{width:auto;font-size:15px}
body.sidebar-mini .sidebar-profile-card .spc-info{opacity:0;width:0;overflow:hidden;padding:0}
body.sidebar-mini .spc-avatar{box-shadow:0 0 0 2px rgba(37,99,235,.5)}

/* Mini tooltips for nav-parent */
body.sidebar-mini .nav-parent{position:relative}
body.sidebar-mini .nav-parent::after{
  content:attr(data-tip);
  position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);
  background:#0d1e3d;color:#fff;font-size:12px;font-weight:600;
  padding:6px 12px;border-radius:9px;white-space:nowrap;
  opacity:0;pointer-events:none;transition:opacity .18s;
  box-shadow:0 4px 20px rgba(0,0,0,.4);z-index:300;border:1px solid rgba(37,99,235,.2);
}
body.sidebar-mini .nav-parent:hover::after{opacity:1}

/* Mini tooltips for util items */
body.sidebar-mini .nav-util-item{position:relative}
body.sidebar-mini .nav-util-item::after{
  content:attr(data-tip);
  position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);
  background:#0d1e3d;color:#fff;font-size:12px;font-weight:600;
  padding:6px 12px;border-radius:9px;white-space:nowrap;
  opacity:0;pointer-events:none;transition:opacity .18s;
  box-shadow:0 4px 20px rgba(0,0,0,.4);z-index:300;border:1px solid rgba(37,99,235,.2);
}
body.sidebar-mini .nav-util-item:hover::after{opacity:1}

.sidebar-nav{padding-bottom:16px!important}

/* ════════════════════════════
   DARK MODE
════════════════════════════ */
/* Dark palette also lives in css/admin-tokens.css. */

[data-theme="dark"] body{background:var(--light)}
[data-theme="dark"] .card{background:var(--white);border-color:var(--border)}
[data-theme="dark"] .topbar{background:#161b2e;border-color:var(--border)}
[data-theme="dark"] .form-control{background:#1e2540;border-color:var(--border);color:var(--text)}
[data-theme="dark"] .form-control:focus{background:#1e2540}
[data-theme="dark"] .search-input{background:#1e2540;border-color:var(--border);color:var(--text)}
[data-theme="dark"] thead th{background:#1e2540;color:var(--gray)}
[data-theme="dark"] tbody tr:hover td{background:rgba(124,58,237,.05)}
[data-theme="dark"] .dropdown-menu{background:#1a2035;border-color:var(--border)}
[data-theme="dark"] .dropdown-item:hover{background:#252d45}
[data-theme="dark"] .btn-secondary{background:#1e2540;border-color:var(--border);color:var(--text)}
[data-theme="dark"] .btn-secondary:hover{background:#252d45}
[data-theme="dark"] .topbar-btn{background:#1e2540;border-color:var(--border)}
[data-theme="dark"] .topbar-profile{background:#1e2540;border-color:var(--border)}

/* ════════════════════════════
   DARK MODE TOGGLE BUTTON
════════════════════════════ */
.theme-toggle{
  width:38px;height:38px;border-radius:10px;cursor:pointer;
  background:var(--light);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--gray);font-size:15px;transition:var(--transition);
}
.theme-toggle:hover{background:var(--violet-lt);color:var(--violet)}

/* ════════════════════════════
   STATUS TABS
════════════════════════════ */
.status-tabs{display:flex;gap:6px;margin-bottom:20px;overflow-x:auto;padding-bottom:2px;scrollbar-width:none}
.status-tabs::-webkit-scrollbar{display:none}
.status-tab{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;
  border:1.5px solid var(--border);color:var(--gray);
  transition:var(--transition);cursor:pointer;white-space:nowrap;
  background:var(--white);text-decoration:none;
}
.status-tab:hover{border-color:var(--violet);color:var(--violet);background:var(--violet-lt)}
.status-tab.active{
  background:linear-gradient(135deg,var(--violet),var(--blue));
  border-color:transparent;color:#fff;
  box-shadow:0 4px 14px rgba(124,58,237,.3);
}
.status-tab .tab-count{
  font-size:11px;font-weight:700;padding:2px 7px;border-radius:10px;
  background:rgba(0,0,0,.08);
}
.status-tab.active .tab-count{background:rgba(255,255,255,.2)}

/* ════════════════════════════
   MODAL
════════════════════════════ */
.modal-backdrop{
  position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:900;
  display:flex;align-items:center;justify-content:center;
  padding:20px;backdrop-filter:blur(4px);
  opacity:0;pointer-events:none;transition:opacity .2s;
}
.modal-backdrop.open{opacity:1;pointer-events:all}
.modal{
  background:var(--white);border-radius:var(--radius-xl);
  width:100%;max-width:680px;max-height:90vh;overflow-y:auto;
  box-shadow:0 24px 80px rgba(0,0,0,.2);
  transform:translateY(16px) scale(.98);transition:transform .22s cubic-bezier(.34,1.56,.64,1);
  scrollbar-width:thin;
}
.modal-backdrop.open .modal{transform:none}
.modal-header{
  padding:20px 24px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;background:var(--white);z-index:1;
}
.modal-header h3{font-size:17px;font-weight:800;color:var(--primary)}
.modal-close{
  width:34px;height:34px;border-radius:9px;background:var(--light);
  border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--gray);font-size:16px;transition:var(--transition);
}
.modal-close:hover{background:rgba(225,29,72,.1);color:var(--rose)}
.modal-body{padding:24px}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}

/* ════════════════════════════
   SKELETON LOADERS
════════════════════════════ */
@keyframes shimmer{0%{background-position:-400px 0}100%{background-position:400px 0}}
.skeleton{
  background:linear-gradient(90deg,var(--light) 25%,var(--light2) 50%,var(--light) 75%);
  background-size:800px 100%;animation:shimmer 1.6s infinite;border-radius:6px;
}
.skeleton-text{height:14px;margin-bottom:8px}
.skeleton-title{height:20px;width:60%;margin-bottom:12px}
.skeleton-circle{border-radius:50%;flex-shrink:0}
.skeleton-card{padding:20px;border-radius:var(--radius-lg);border:1px solid var(--border);background:var(--white)}

/* ════════════════════════════
   PROGRESS BARS
════════════════════════════ */
.progress{height:8px;background:var(--light2);border-radius:20px;overflow:hidden;margin-top:8px}
.progress-bar{height:100%;border-radius:20px;transition:width .8s cubic-bezier(.4,0,.2,1)}
.progress-bar.violet{background:linear-gradient(90deg,var(--violet),var(--blue))}
.progress-bar.emerald{background:linear-gradient(90deg,#059669,#06b6d4)}
.progress-bar.rose{background:linear-gradient(90deg,#e11d48,#f97316)}
.progress-bar.amber{background:linear-gradient(90deg,#d97706,#f59e0b)}
.progress-bar.striped{
  background-image:linear-gradient(45deg,rgba(255,255,255,.12) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.12) 50%,rgba(255,255,255,.12) 75%,transparent 75%,transparent);
  background-size:16px 16px;animation:progress-stripes .8s linear infinite;
}
@keyframes progress-stripes{0%{background-position:0 0}100%{background-position:16px 0}}

/* ════════════════════════════
   ACTIVITY TIMELINE
════════════════════════════ */
.timeline{padding:0;list-style:none}
.timeline-item{display:flex;gap:14px;padding-bottom:20px;position:relative}
.timeline-item:not(:last-child)::before{
  content:'';position:absolute;left:17px;top:38px;bottom:0;
  width:2px;background:var(--border);
}
.timeline-dot{
  width:36px;height:36px;min-width:36px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:13px;
  position:relative;z-index:1;
}
.timeline-content{flex:1;min-width:0;padding-top:4px}
.timeline-title{font-size:13.5px;font-weight:600;color:var(--primary);line-height:1.35}
.timeline-desc{font-size:12px;color:var(--gray);margin-top:3px;line-height:1.5}
.timeline-time{font-size:11px;color:var(--gray2);margin-top:4px}

/* ════════════════════════════
   AVATAR STACK
════════════════════════════ */
.avatar{
  width:36px;height:36px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0;
}
.avatar img{width:100%;height:100%;object-fit:cover}
.avatar-sm{width:28px;height:28px;font-size:11px}
.avatar-lg{width:52px;height:52px;font-size:18px}
.avatar-stack{display:flex}
.avatar-stack .avatar{border:2px solid var(--white);margin-left:-8px}
.avatar-stack .avatar:first-child{margin-left:0}

/* ════════════════════════════
   MINI STAT WIDGET
════════════════════════════ */
.mini-stat{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 16px;border-radius:12px;
  border:1px solid var(--border);background:var(--white);
  transition:var(--transition);
}
.mini-stat:hover{border-color:rgba(124,58,237,.2);background:var(--violet-lt)}
.mini-stat-label{font-size:12px;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.mini-stat-value{font-size:20px;font-weight:900;color:var(--primary);margin-top:2px}
.mini-stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}

/* ════════════════════════════
   LEAD SOURCE / INFO CHIPS
════════════════════════════ */
.chip{
  display:inline-flex;align-items:center;gap:5px;
  padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:600;
  border:1.5px solid var(--border);background:var(--light);color:var(--gray);
}
.chip i{font-size:10px}

/* ════════════════════════════
   CONTEXT MENU / ACTION DROPDOWN
════════════════════════════ */
.action-menu-wrap{position:relative;display:inline-block}
.action-menu{
  position:absolute;top:calc(100% + 6px);right:0;
  background:var(--white);border:1px solid var(--border);
  border-radius:12px;box-shadow:var(--shadow-md);
  min-width:170px;padding:5px;z-index:400;
  display:none;animation:dropIn .15s ease;
}
.action-menu.open{display:block}
.action-menu-item{
  display:flex;align-items:center;gap:9px;padding:8px 12px;
  border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;
  transition:var(--transition);color:var(--text);
}
.action-menu-item:hover{background:var(--light2);color:var(--violet)}
.action-menu-item.danger:hover{background:rgba(225,29,72,.07);color:var(--rose)}
.action-menu-item i{width:15px;text-align:center;font-size:12px;opacity:.7}
.action-menu-divider{height:1px;background:var(--border);margin:4px 0}

/* ════════════════════════════
   BULK ACTIONS BAR
════════════════════════════ */
.bulk-bar{
  display:none;align-items:center;gap:12px;
  padding:12px 18px;background:linear-gradient(135deg,var(--violet),var(--blue));
  border-radius:12px;margin-bottom:16px;
  box-shadow:0 4px 18px rgba(124,58,237,.3);
}
.bulk-bar.visible{display:flex}
.bulk-bar-count{color:#fff;font-size:13.5px;font-weight:700;flex:1}
.bulk-bar .btn-sm{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff}
.bulk-bar .btn-sm:hover{background:rgba(255,255,255,.25)}
.bulk-bar .btn-danger-ghost{background:rgba(225,29,72,.35);border:1px solid rgba(225,29,72,.4);color:#fff}

/* ════════════════════════════
   EMPTY STATE
════════════════════════════ */
.empty-state{
  padding:64px 24px;text-align:center;
}
.empty-state-icon{
  width:72px;height:72px;border-radius:24px;
  background:var(--light2);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;font-size:28px;color:var(--gray2);
}
.empty-state h3{font-size:17px;font-weight:700;color:var(--primary);margin-bottom:8px}
.empty-state p{font-size:14px;color:var(--gray);max-width:320px;margin:0 auto 20px;line-height:1.6}

/* ════════════════════════════
   NOTIFICATION PANEL
════════════════════════════ */
.notif-panel{
  position:absolute;top:calc(100% + 10px);right:0;
  background:var(--white);border:1px solid var(--border);
  border-radius:16px;box-shadow:var(--shadow-lg);
  width:360px;max-height:480px;overflow:hidden;
  display:none;animation:dropIn .18s ease;z-index:500;
}
.notif-panel.open{display:block}
.notif-panel-header{
  padding:16px 18px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
}
.notif-panel-header h4{font-size:15px;font-weight:800;color:var(--primary)}
.notif-list{overflow-y:auto;max-height:360px}
.notif-item{
  display:flex;gap:12px;padding:13px 18px;
  border-bottom:1px solid var(--border2);
  transition:var(--transition);cursor:pointer;
}
.notif-item:hover{background:var(--light)}
.notif-item.unread{background:rgba(124,58,237,.03)}
.notif-item .notif-icon{
  width:36px;height:36px;min-width:36px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:14px;
}
.notif-item-content .notif-title{font-size:13.5px;font-weight:600;color:var(--primary);line-height:1.3}
.notif-item-content .notif-meta{font-size:11.5px;color:var(--gray);margin-top:3px}
.notif-item .unread-dot{
  width:7px;height:7px;border-radius:50%;background:var(--violet);
  margin-top:4px;flex-shrink:0;
}

/* ════════════════════════════
   LEAD PIPELINE / FUNNEL
════════════════════════════ */
.pipeline-stages{display:flex;gap:8px;margin-bottom:24px;overflow-x:auto}
.pipeline-stage{
  flex:1;min-width:110px;padding:14px 16px;border-radius:12px;
  border:1.5px solid var(--border);background:var(--white);
  text-align:center;cursor:pointer;transition:var(--transition);position:relative;
}
.pipeline-stage:hover{border-color:var(--violet);transform:translateY(-2px)}
.pipeline-stage.active{border-color:var(--violet);background:var(--violet-lt)}
.pipeline-stage .ps-count{font-size:24px;font-weight:900;color:var(--primary)}
.pipeline-stage .ps-label{font-size:11.5px;color:var(--gray);font-weight:600;margin-top:4px}
.pipeline-stage .ps-bar{position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:0 0 10px 10px}

/* ════════════════════════════
   SYSTEM STATUS INDICATORS
════════════════════════════ */
.sys-status-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border2)}
.sys-status-row:last-child{border-bottom:none}
.status-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.status-dot.online{background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.status-dot.warning{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.2)}
.status-dot.offline{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.2)}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.status-dot.online{animation:pulse-dot 2s infinite}

/* ════════════════════════════
   KANBAN-STYLE LEAD CARD
════════════════════════════ */
.lead-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:14px;padding:16px;margin-bottom:10px;
  transition:var(--transition);cursor:pointer;
  border-left:3px solid transparent;
}
.lead-card:hover{box-shadow:var(--shadow-md);transform:translateX(2px)}
.lead-card.status-new{border-left-color:var(--rose)}
.lead-card.status-contacted{border-left-color:var(--blue)}
.lead-card.status-in_progress{border-left-color:var(--amber)}
.lead-card.status-converted{border-left-color:var(--emerald)}
.lead-card.status-rejected{border-left-color:var(--gray)}
.lead-card-top{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.lead-avatar{
  width:38px;height:38px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:800;color:#fff;flex-shrink:0;
}

/* ════════════════════════════
   EXPORT DROPDOWN
════════════════════════════ */
.export-btn-wrap{position:relative;display:inline-block}
.export-menu{
  position:absolute;top:calc(100% + 6px);right:0;
  background:var(--white);border:1px solid var(--border);
  border-radius:12px;box-shadow:var(--shadow-md);
  min-width:160px;padding:5px;z-index:400;
  display:none;animation:dropIn .15s ease;
}
.export-menu.open{display:block}
.export-menu a{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--text);font-weight:500;transition:var(--transition)}
.export-menu a:hover{background:var(--light2);color:var(--violet)}
.export-menu a i{width:16px;text-align:center;font-size:13px}

/* ════════════════════════════
   DATA STAT ROW
════════════════════════════ */
.data-row{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border2)}
.data-row:last-child{border-bottom:none}
.data-row-label{font-size:13px;color:var(--text);font-weight:500;display:flex;align-items:center;gap:8px}
.data-row-value{font-size:14px;font-weight:700;color:var(--primary)}

/* ════════════════════════════
   BREADCRUMB
════════════════════════════ */
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--gray);margin-bottom:4px;flex-wrap:wrap}
.breadcrumb a{color:var(--violet);font-weight:500}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb-sep{color:var(--gray2)}

/* ════════════════════════════
   FLOATING ACTION BUTTON
════════════════════════════ */
.fab{
  position:fixed;bottom:28px;right:28px;z-index:150;
  width:52px;height:52px;border-radius:50%;
  background:linear-gradient(135deg,var(--violet),var(--blue));
  color:#fff;font-size:20px;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 24px rgba(124,58,237,.45);transition:var(--transition);
}
.fab:hover{transform:scale(1.1) rotate(15deg);box-shadow:0 8px 30px rgba(124,58,237,.55)}

/* ════════════════════════════
   SEARCH HIGHLIGHT
════════════════════════════ */
mark.hl{background:rgba(124,58,237,.15);color:var(--violet);border-radius:3px;padding:1px 2px}

/* ════════════════════════════
   INFO ROW GRID
════════════════════════════ */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.info-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.info-item{}
.info-item .info-label{font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px}
.info-item .info-value{font-size:14px;font-weight:600;color:var(--primary)}

/* ════════════════════════════
   TOPBAR SEARCH
════════════════════════════ */
.topbar-search{
  display:flex;align-items:center;gap:8px;
  background:var(--light);border:1.5px solid var(--border);
  border-radius:10px;padding:7px 14px;flex:1;max-width:360px;
  transition:var(--transition);
}
.topbar-search:focus-within{border-color:var(--violet);background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.07)}
.topbar-search input{background:none;border:none;outline:none;font-size:13.5px;color:var(--text);width:100%;font-family:inherit}
.topbar-search i{color:var(--gray2);font-size:14px;flex-shrink:0}
.topbar-search input::placeholder{color:var(--gray2)}

/* ════════════════════════════
   TAGS INPUT
════════════════════════════ */
.tag{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;
  background:var(--violet-lt);color:var(--violet);border:1px solid rgba(124,58,237,.2);
}
.tag-remove{cursor:pointer;opacity:.6;font-size:10px}
.tag-remove:hover{opacity:1}

/* ════════════════════════════
   PRINT STYLES
════════════════════════════ */
@media print{
  .sidebar,.topbar,.sidebar-overlay,.fab,.bulk-bar,.filter-bar,.page-header-actions{display:none!important}
  .main-area{margin-left:0!important}
  .card{box-shadow:none!important;border:1px solid #ddd!important}
}

/* ════════════════════════════
   ADDITIONAL RESPONSIVE
════════════════════════════ */
@media(max-width:768px){
  .info-grid{grid-template-columns:1fr}
  .info-grid-3{grid-template-columns:1fr 1fr}
  .pipeline-stages{gap:6px}
  .pipeline-stage{min-width:90px;padding:10px 12px}
  .pipeline-stage .ps-count{font-size:20px}
  .modal{border-radius:14px}
  .notif-panel{width:calc(100vw - 32px);right:-80px}
  .status-tabs{gap:4px}
  .status-tab{padding:7px 12px;font-size:12px}
}
@media(max-width:480px){
  .info-grid-3{grid-template-columns:1fr}
  .stats-grid{grid-template-columns:1fr 1fr}
}

/* ════════════════════════════════════════════
   COMPREHENSIVE MOBILE / TABLET RESPONSIVE
════════════════════════════════════════════ */

/* ── Tablet wide (≤1200px) ── */
@media(max-width:1200px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .topbar-search{max-width:220px}
}

/* ── Tablet (≤1024px) ── */
@media(max-width:1024px){
  :root{--sidebar-w:230px}
  .topbar-clock{display:none!important}
  .topbar-search{max-width:180px}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .page-content{padding:20px}

  /* Stack 2-col admin edit page layouts (inline styles) */
  div[style*="grid-template-columns:1fr 300px"],
  div[style*="grid-template-columns:1fr 280px"],
  div[style*="grid-template-columns:1fr 320px"],
  div[style*="grid-template-columns:1fr 360px"],
  div[style*="grid-template-columns:1fr 340px"],
  div[style*="grid-template-columns:1fr 400px"]{
    grid-template-columns:1fr!important;
  }
}

/* ── Tablet narrow (≤900px) ── */
@media(max-width:900px){
  .form-row{grid-template-columns:1fr!important}
  .form-row-3{grid-template-columns:1fr!important}
  .stats-grid{grid-template-columns:repeat(2,1fr)!important}
  .card-body{padding:16px}
  .card-header{padding:13px 16px}
  .page-content{padding:16px}

  /* Topbar: hide profile text, keep avatar */
  .topbar-profile .tp-info{display:none}
  .topbar-profile{padding:4px;border-radius:50%;border-color:transparent;background:transparent}
  .topbar-search{max-width:150px}

  /* Stack all 2-col edit layouts */
  div[style*="grid-template-columns:1fr 3"],
  div[style*="grid-template-columns:1fr 2"]{
    grid-template-columns:1fr!important;
  }

  /* Page header: wrap actions below title */
  .page-header{flex-wrap:wrap}
  .page-header>div:last-child{margin-top:0}

  /* Filter bar: wrap nicely */
  .filter-bar{flex-wrap:wrap;gap:8px}
  .search-wrap{min-width:0;flex:1 1 200px}

  /* Table horizontal scroll with shadow hint */
  .table-wrap{
    border-radius:var(--radius-lg);
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  .table-wrap::after{
    content:'';position:sticky;right:0;top:0;
    width:32px;height:100%;
    background:linear-gradient(to left,rgba(255,255,255,.8),transparent);
    pointer-events:none;display:block;
    margin-top:-100%;float:right;
  }

  /* Notification panel full width */
  .notif-panel{width:calc(100vw - 32px);right:-10px}
}

/* ── Mobile (≤768px) ── */
@media(max-width:768px){
  /* Fix existing CSS typo: .body.sidebar-mini → body.sidebar-mini */
  body.sidebar-mini .main-area{margin-left:0!important}
  .main-area{margin-left:0!important}

  /* Sidebar: hidden by default on mobile, slides in as overlay */
  .sidebar{
    /* width and slide distance are token-driven; -100% keeps the panel
       off-canvas whatever width the tier gives it. */
    height:100dvh;
    z-index:300;
    transform:translateX(-100%);
  }
  .sidebar.open{
    transform:translateX(0);
  }
  /* Override mini mode on mobile — always full width when open */
  body.sidebar-mini .sidebar{ transform:translateX(-100%); }
  body.sidebar-mini .sidebar.open{ transform:translateX(0); }
  /* Restore mini labels inside open mobile sidebar */
  body.sidebar-mini .sidebar.open .nav-label,
  body.sidebar-mini .sidebar.open .nav-badge,
  body.sidebar-mini .sidebar.open .nav-arrow,
  body.sidebar-mini .sidebar.open .spc-info,
  body.sidebar-mini .sidebar.open .logo-text,
  body.sidebar-mini .sidebar.open .sf-label{
    opacity:1!important;width:auto!important;overflow:visible!important;
    padding:initial!important;margin:initial!important;
  }
  body.sidebar-mini .sidebar.open .nav-item{
    padding:8px 10px!important;justify-content:flex-start!important;gap:10px!important;
  }
  body.sidebar-mini .sidebar.open .nav-parent{
    padding:8px 10px!important;justify-content:flex-start!important;gap:10px!important;
  }
  body.sidebar-mini .sidebar.open .nav-group{padding:0 10px!important;}
  body.sidebar-mini .sidebar.open .nav-group-label{
    opacity:1!important;height:auto!important;padding:12px 10px 4px!important;overflow:visible!important;
  }
  body.sidebar-mini .sidebar.open .nav-sub{
    display:block!important;
  }

  /* Topbar */
  .topbar{padding:0 12px;gap:6px;height:56px}
  :root{--topbar-h:56px}
  .topbar-brand{display:flex}
  .topbar-search{display:none!important}
  .topbar-theme-pill span{display:none}
  .topbar-theme-pill{padding:7px;border-radius:10px}
  .page-title{font-size:15px}
  .topbar-profile .tp-info{display:none}
  .topbar-profile{padding:4px;border-radius:50%;background:var(--light)}

  /* Topbar right: compress */
  .topbar-right{gap:4px}
  .topbar-btn{width:34px;height:34px;font-size:14px}

  /* Page content */
  .page-content{padding:12px}
  .page-header{margin-bottom:16px;gap:10px}
  .page-header h1{font-size:18px}
  .page-header p{font-size:12.5px}

  /* Cards */
  .card-body{padding:14px}
  .card-header{padding:12px 14px}
  .card-footer{padding:10px 14px}

  /* Stats */
  .stats-grid{grid-template-columns:repeat(2,1fr)!important;gap:12px}
  .stat-card{padding:14px;gap:10px}
  .stat-icon{width:42px;height:42px;min-width:42px;font-size:17px}
  .stat-info .value{font-size:22px}
  .stat-info .label{font-size:10.5px}

  /* Page header actions wrap */
  .page-header{flex-direction:row;align-items:flex-start}
  .page-header>div:first-child{flex:1;min-width:0}
  .page-header>*:last-child{display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0}

  /* Forms stack */
  .form-row{grid-template-columns:1fr!important}
  .form-row-3{grid-template-columns:1fr!important}
  .input-group .form-control{border-radius:10px!important;border-right:1.5px solid var(--border)!important}
  .input-group .btn{border-radius:10px!important;margin-left:6px}

  /* Filter bar */
  .filter-bar{flex-direction:column;gap:8px}
  .search-wrap{width:100%!important;flex:none}

  /* Tables: touch-scroll */
  .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  thead th{padding:9px 12px;font-size:10.5px}
  tbody td{padding:10px 12px;font-size:12.5px}

  /* Status tabs: scroll */
  .status-tabs{overflow-x:auto;flex-wrap:nowrap;padding-bottom:4px}
  .status-tab{padding:6px 12px;font-size:11.5px;flex-shrink:0}

  /* Modals: bottom sheet on mobile */
  .modal-backdrop{padding:0;align-items:flex-end}
  .modal{
    border-radius:20px 20px 0 0!important;
    max-height:92dvh;
    max-width:100%;
    width:100%;
  }
  .modal-header{padding:16px 18px}
  .modal-body{padding:16px 18px}
  .modal-footer{padding:12px 18px;flex-wrap:wrap}

  /* Notification panel: full-width bottom */
  .notif-panel{
    width:100vw;right:0;
    border-radius:20px 20px 0 0;
    top:auto;bottom:0;position:fixed;
    max-height:70dvh;
  }

  /* Dropdown: full width on small screens */
  .dropdown-menu{width:calc(100vw - 24px);right:-10px}

  /* Stack all inline-styled grids */
  div[style*="grid-template-columns:1fr 3"],
  div[style*="grid-template-columns:1fr 2"],
  div[style*="grid-template-columns:1fr 1"]{
    grid-template-columns:1fr!important;
  }

  /* Buttons: slightly larger tap targets */
  .btn{min-height:38px}
  .btn-sm{min-height:32px;padding:5px 11px;font-size:12px}
  .btn-icon{width:36px;height:36px;min-height:36px}

  /* Pagination wrap */
  .pagination{justify-content:center;gap:3px}
  .pagination a,.pagination span{padding:6px 10px;font-size:12px}

  /* Info grid */
  .info-grid,.info-grid-3{grid-template-columns:1fr!important}

  /* Badges: no wrapping */
  .badge{font-size:10.5px;padding:3px 8px}
}

/* ── Mobile small (≤480px) ── */
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr 1fr!important;gap:10px}
  .stat-card{padding:12px;gap:8px}
  .stat-icon{width:38px;height:38px;min-width:38px;font-size:15px}
  .stat-info .value{font-size:20px}
  .page-content{padding:10px}
  .topbar-theme-pill{display:none}
  .page-title{font-size:14px}
  .card-body{padding:12px}
  .page-header h1{font-size:16px}

  /* Table font */
  thead th{font-size:10px;padding:8px 10px}
  tbody td{font-size:12px;padding:9px 10px}

  /* Btn text shrink */
  .btn-sm{font-size:11.5px;padding:5px 10px}

  /* Notification panel */
  .notif-panel{max-height:80dvh}

  /* Status tab compact */
  .status-tab{padding:5px 10px;font-size:11px}
  .tab-count{display:none}
}

/* ── Mobile XS (≤360px) ── */
@media(max-width:360px){
  .topbar-right .topbar-btn:not(:last-child){display:none}
  .stats-grid{grid-template-columns:1fr!important}
}

/* ── Sidebar close button on mobile ── */
@media(max-width:768px){
  .sidebar-close-btn{
    display:flex!important;
    position:absolute;top:12px;right:-14px;
    width:28px;height:28px;border-radius:50%;
    background:#fff;border:none;cursor:pointer;
    align-items:center;justify-content:center;
    color:var(--text);font-size:12px;
    box-shadow:2px 2px 8px rgba(0,0,0,.15);
    z-index:10;
  }
}
.sidebar-close-btn{display:none}

/* ── CKEditor toolbar wraps on narrow screens ── */
@media(max-width:768px){
  .ck.ck-toolbar{flex-wrap:wrap!important}
  .ck.ck-toolbar .ck-toolbar__items{flex-wrap:wrap!important}
  .ck.ck-editor__main .ck-editor__editable{min-height:280px!important}
}
</style>
<!-- Shell layer: loaded after the inline styles above so it wins on
     document order rather than on !important. -->
<link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin-shell.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin-dashboard.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin-rail.css">
</head>
<body>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">

  <!-- Mobile close button -->
  <button class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Close menu">
    <i class="fas fa-times"></i>
  </button>

  <!-- Logo / Brand -->
  <a class="sidebar-logo" href="<?= ADMIN_URL ?>/dashboard.php" aria-label="<?= e($siteName) ?> admin home">
    <?php /* the rail is dark, so the light logo variant is the readable one */ ?>
    <img class="logo-full" src="<?= UPLOADS_URL ?>/logo/appsgain-logo-light.png"
         alt="<?= e($siteName) ?>" width="180" height="32">
    <img class="logo-mark" src="<?= UPLOADS_URL ?>/logo/appsgain-icon-64.png"
         alt="" aria-hidden="true" width="32" height="32">
  </a>

  <!-- Navigation (collapsible accordion) -->
  <nav class="sidebar-nav" id="sidebarNav" aria-label="Admin sections">
    <?php
    /* The tree lives in admin-nav.php. Adding a link is one array entry —
       the old hand-written markup was where grouping drifted (Invoices
       listed twice, access control filed under "Leads"). */
    require_once __DIR__ . "/admin-nav.php";
    agRenderNav($AG_NAV, $adminPage, [
        "newLeads"    => (int)($newLeads    ?? 0),
        "newComments" => (int)($newComments ?? 0),
        "newChatLeads" => (int)($newChatLeads ?? 0),
        "newChatSessions" => (int)($newChatSessions ?? 0),
    ]);
    ?>
  </nav>

  <!-- Footer: Theme toggle + Collapse -->
  <div class="sidebar-foot">
    <a class="rail-user" href="<?= ADMIN_URL ?>/pages/profile.php?tab=profile">
      <span class="rail-user-av">
        <?php if (!empty($admin['avatar'])): ?>
          <img src="<?= UPLOADS_URL . '/' . e($admin['avatar']) ?>" alt="">
        <?php else: ?>
          <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
        <?php endif; ?>
      </span>
      <span class="rail-user-txt">
        <span class="rail-user-name"><?= e($admin['name'] ?? 'Admin') ?></span>
        <span class="rail-user-role"><?= strtoupper(e(str_replace('_', ' ', $admin['role'] ?? 'admin'))) ?></span>
      </span>
    </a>

    <a class="rail-signout" href="<?= ADMIN_URL ?>/logout.php">
      <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
      <span>Sign Out</span>
    </a>
  </div>

</aside>

<!-- ═══ MAIN AREA ═══ -->
<div class="main-area" id="mainArea">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <!-- Sidebar toggle (desktop=mini, mobile=overlay) -->
      <button class="hamburger-admin" onclick="toggleSidebar()" title="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <!-- Brand (mobile only) -->
      <div class="topbar-brand">
        <img src="<?= UPLOADS_URL ?>/logo/appsgain-icon-64.png" alt="" aria-hidden="true"
             width="30" height="30" style="border-radius:8px">
        <div class="tb-name"><?= e($siteName) ?></div>
      </div>
      <!-- Page title + live clock -->
      <div class="topbar-title-wrap" id="topbarTitleWrap">
        <div class="page-title"><?= e($adminTitle) ?></div>
        <div class="topbar-clock">
          <i class="fas fa-clock"></i>
          <span id="tbDate">--</span>
          &middot;
          <span id="tbH">--</span><span class="tb-colon">:</span><span id="tbM">--</span><span class="tb-colon">:</span><span id="tbS">--</span>
        </div>
      </div>
      <!-- Global topbar search -->
      <div class="topbar-search" id="topbarSearch">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search leads, blog, services…" id="globalSearch" autocomplete="off">
      </div>
    </div>

    <div class="topbar-right">
      <!-- Global Search toggle -->
      <button class="topbar-btn" title="Search" onclick="toggleTopbarSearch()" id="searchToggleBtn">
        <i class="fas fa-search"></i>
      </button>
      <!-- Dark / Light mode pill -->
      <div class="topbar-theme-pill" id="themeToggle" onclick="toggleTheme()" title="Toggle dark/light mode">
        <i class="fas fa-moon" id="themeIcon"></i>
        <span id="themeLabel">Dark</span>
      </div>
      <a href="<?= SITE_URL ?>" target="_blank" class="topbar-btn" title="View Website">
        <i class="fas fa-external-link-alt"></i>
      </a>
      <!-- Notification Bell -->
      <div class="profile-wrap">
        <button class="topbar-btn" id="notifToggle" onclick="toggleNotifPanel()" title="Notifications" style="position:relative">
          <i class="fas fa-bell"></i>
          <?php if ($newLeads > 0): ?><span class="notif-dot"></span><?php endif; ?>
        </button>
        <div class="notif-panel" id="notifPanel">
          <div class="notif-panel-header">
            <h4><i class="fas fa-bell" style="color:var(--violet)"></i> Notifications</h4>
            <?php if ($newLeads > 0): ?>
            <span class="badge badge-violet"><?= $newLeads ?> new</span>
            <?php endif; ?>
          </div>
          <div class="notif-list">
            <?php if ($newLeads > 0): ?>
            <a href="<?= ADMIN_URL ?>/pages/leads.php?status=new" class="notif-item unread" style="text-decoration:none">
              <div class="notif-icon" style="background:rgba(225,29,72,.1);color:var(--rose)">
                <i class="fas fa-envelope-open-text"></i>
              </div>
              <div class="notif-item-content">
                <div class="notif-title"><?= $newLeads ?> New Lead<?= $newLeads > 1 ? 's' : '' ?> Received</div>
                <div class="notif-meta">Click to view and respond</div>
              </div>
              <div class="unread-dot"></div>
            </a>
            <?php endif; ?>
            <?php if (!empty($pendingComments ?? 0) && ($pendingComments ?? 0) > 0): ?>
            <a href="<?= ADMIN_URL ?>/pages/blogs.php" class="notif-item unread" style="text-decoration:none">
              <div class="notif-icon" style="background:rgba(217,119,6,.1);color:var(--amber)">
                <i class="fas fa-comment"></i>
              </div>
              <div class="notif-item-content">
                <div class="notif-title"><?= $pendingComments ?> Pending Comment<?= $pendingComments > 1 ? 's' : '' ?></div>
                <div class="notif-meta">Awaiting moderation</div>
              </div>
              <div class="unread-dot"></div>
            </a>
            <?php endif; ?>
            <?php if (($newLeads ?? 0) === 0 && ($pendingComments ?? 0) === 0): ?>
            <div style="padding:32px;text-align:center;color:var(--gray)">
              <i class="fas fa-check-circle" style="font-size:28px;color:var(--emerald);display:block;margin-bottom:10px"></i>
              All caught up!
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <!-- Profile -->
      <div class="profile-wrap">
        <div class="topbar-profile" onclick="toggleProfileMenu()" id="profileToggle">
          <div class="tp-avatar">
            <?php if (!empty($admin['avatar'])): ?>
              <img src="<?= getImageUrl($admin['avatar']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($admin['name'], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <div class="tp-info">
            <div class="tp-name"><?= e($admin['name']) ?></div>
            <div class="tp-role"><?= e($admin['role']) ?></div>
          </div>
          <i class="fas fa-chevron-down" style="font-size:10px;color:var(--gray2);margin-left:4px"></i>
        </div>
        <div class="dropdown-menu" id="profileMenu">
          <div class="dropdown-profile-header">
            <div class="dp-name"><?= e($admin['name']) ?></div>
            <div class="dp-email"><?= e($admin['email'] ?? '') ?></div>
          </div>
          <a href="<?= ADMIN_URL ?>/pages/profile.php" class="dropdown-item">
            <i class="fas fa-user-edit"></i> My Profile
          </a>
          <a href="<?= ADMIN_URL ?>/pages/settings.php" class="dropdown-item">
            <i class="fas fa-sliders-h"></i> Site Settings
          </a>
          <a href="<?= SITE_URL ?>" target="_blank" class="dropdown-item">
            <i class="fas fa-globe"></i> View Website
          </a>
          <div class="dropdown-divider"></div>
          <div class="dropdown-item" onclick="toggleTheme()">
            <i class="fas fa-moon"></i> <span id="themeDropLabel">Dark Mode</span>
          </div>
          <div class="dropdown-divider"></div>
          <a href="<?= ADMIN_URL ?>/logout.php" class="dropdown-item danger"
             onclick="return confirm('Sign out of the admin panel?')">
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </div>
      </div>
    </div>
  </header><!-- /topbar -->

  <!-- Page Content -->
  <div class="page-content">
    <?php
    /* Capture flash before renderFlash() consumes them, so admin-foot can toast them */
    global $_adminFlashForToast;
    $_adminFlashForToast = [];
    foreach (['success','error','warning','info'] as $_t) {
        foreach ($_SESSION['flash'][$_t] ?? [] as $_m) {
            $_adminFlashForToast[] = ['type' => $_t, 'msg' => $_m];
        }
    }
    renderFlash();
    ?>
