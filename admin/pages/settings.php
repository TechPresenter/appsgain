<?php
$adminTitle = 'Site Settings';
$adminPage  = 'settings';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
Auth::requireRole('superadmin', 'admin');

/* ── Helper: build recipient pill preview HTML ── */
function buildNotifyPreview(string $val): string {
    if (!$val) return '';
    $emails = array_filter(array_map('trim', preg_split('/[,;\n]+/', $val)));
    if (!$emails) return '';
    $out = '<div class="notify-pills">';
    foreach ($emails as $e) {
        $valid = filter_var($e, FILTER_VALIDATE_EMAIL);
        $out  .= '<span class="notify-pill' . ($valid ? '' : ' invalid') . '">'
               . ($valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>')
               . ' ' . htmlspecialchars($e)
               . '</span>';
    }
    $out .= '</div>';
    return $out;
}

$tab = sanitizeInput($_GET['tab'] ?? 'general');

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_settings') {
        $fields = [
            // General
            'site_name','site_tagline','site_description','footer_text','maintenance_mode','items_per_page',
            // Brand — identity, palette and footer content
            'company_name','site_headline','site_eyebrow','theme_color',
            'brand_primary','brand_secondary','brand_accent','brand_red','brand_magenta','brand_violet','brand_ink','brand_gradient',
            'copyright_text','footer_about_text','footer_built_with','footer_badges',
            'footer_links_title','footer_quick_links','footer_legal_links',
            'company_founded_year','company_employees','company_cin','company_gstin',
            'address_street','address_locality','address_region','address_region_code',
            'address_postal','address_country','geo_latitude','geo_longitude',
            // Business
            'site_phone','site_phone2','site_email','site_email2','site_address',
            'site_address_label','site_address_2','site_address_2_label',
            'site_whatsapp','google_maps_embed','contact_email',
            // App Download Links + visibility toggles
            'app_google_play','app_apple_store','footer_apps_title',
            // Payment link shown as the header "Pay Now" button
            'payment_link','payment_label',
            // Founder bio page (/our-founder.php)
            'founder_name','founder_role','founder_email','founder_linkedin',
            'founder_photo','founder_since','founder_education',
            'footer_show_gplay','footer_show_appstore',
            // Social
            'site_facebook','site_instagram','site_twitter','site_threads',
            'site_youtube','site_gmb','site_linkedin','site_pinterest',
            'site_github','site_whatsapp_channel',
            // Notification recipients
            'notify_all','notify_contact','notify_application','notify_newsletter',
            'notify_partner','notify_support','notify_enquiry',
            // AI assistant show/hide toggles + custom URL overrides
            'ai_show_chatgpt','ai_url_chatgpt',
            'ai_show_gemini','ai_url_gemini',
            'ai_show_claude','ai_url_claude',
            'ai_show_perplexity','ai_url_perplexity',
            'ai_show_grok','ai_url_grok',
            // Homepage metrics
            'metric_projects','metric_clients','metric_years','metric_experts','metric_retention',
            // Why section
            'homepage_why_title','homepage_why_subtitle','homepage_why_items',
            'placement_total','placement_rate','courses_enrollment_cta',
            // SEO & Analytics
            'google_analytics','google_tag_manager','google_search_console',
            'bing_verification','yandex_verification','facebook_domain',
            'indexnow_key','og_default_image','facebook_pixel',
            'meta_description','meta_keywords',
            // SMTP
            'smtp_host','smtp_port','smtp_user','smtp_from_name','smtp_encryption',
        ];
        /* Toggle fields — scoped per tab so saving one tab never wipes another tab's checkboxes */
        $togglesByTab = [
            'general'  => ['maintenance_mode'],
            'business' => ['footer_show_gplay', 'footer_show_appstore'],
            'social'   => ['ai_show_chatgpt','ai_show_gemini','ai_show_claude','ai_show_perplexity','ai_show_grok'],
            'brand'    => ['footer_show_stats','footer_show_badges','footer_show_newsletter','footer_show_askai'],
        ];
        /* Only process toggles that belong to the current tab */
        $activeToggles = $togglesByTab[$tab] ?? [];
        foreach ($activeToggles as $tf) {
            saveSetting($tf, isset($_POST[$tf]) ? '1' : '0');
        }
        /* Build full list for skipFields (so they aren't double-processed below) */
        $toggleFields = array_merge(...array_values($togglesByTab));
        /* Google Maps embed — allow HTML/iframes, don't strip tags */
        if (isset($_POST['google_maps_embed'])) {
            saveSetting('google_maps_embed', trim($_POST['google_maps_embed']));
        }
        /* Multi-line fields kept verbatim — sanitizeInput() would collapse the
           newlines these depend on. They are escaped with e() on output. */
        foreach (['footer_quick_links','footer_legal_links','footer_about_text','footer_badges','brand_gradient'] as $raw) {
            if (isset($_POST[$raw])) saveSetting($raw, trim((string)$_POST[$raw]));
        }
        /* Colour fields must be valid hex or they are ignored */
        foreach (['brand_primary','brand_secondary','brand_accent','brand_red','brand_magenta','brand_violet','brand_ink','theme_color'] as $col) {
            if (!isset($_POST[$col])) continue;
            $v = trim((string)$_POST[$col]);
            if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v)) saveSetting($col, $v);
        }
        /* All other fields */
        $skipFields = array_merge($toggleFields, ['google_maps_embed',
            'footer_quick_links','footer_legal_links','footer_about_text','footer_badges','brand_gradient',
            'brand_primary','brand_secondary','brand_accent','brand_red','brand_magenta','brand_violet','brand_ink','theme_color']);
        foreach ($fields as $f) {
            if (!in_array($f, $skipFields) && isset($_POST[$f])) {
                saveSetting($f, sanitizeInput($_POST[$f]));
            }
        }
        if (!empty($_POST['smtp_pass'])) saveSetting('smtp_pass', $_POST['smtp_pass']);

        if (!empty($_FILES['site_logo']['name'])) {
            $up = uploadFile($_FILES['site_logo'], 'settings');
            if ($up['success']) saveSetting('site_logo', $up['path']);
            else { setFlash('error', $up['error']); redirect(ADMIN_URL . '/pages/settings.php?tab=' . $tab); }
        }
        if (!empty($_FILES['site_favicon']['name'])) {
            $up = uploadFile($_FILES['site_favicon'], 'settings');
            if ($up['success']) saveSetting('site_favicon', $up['path']);
        }
        /* Dark-background logo lockup used by the footer */
        if (!empty($_FILES['site_logo_light']['name'])) {
            $up = uploadFile($_FILES['site_logo_light'], 'logo');
            if ($up['success']) saveSetting('site_logo_light', $up['path']);
            else { setFlash('error', $up['error']); redirect(ADMIN_URL . '/pages/settings.php?tab=' . $tab); }
        }

        /* ── AI Assistant custom icon uploads + removals ── */
        $aiIconKeys = ['chatgpt','gemini','claude','perplexity','grok'];
        foreach ($aiIconKeys as $_aik) {
            /* Remove existing icon if remove button clicked */
            if (!empty($_POST['ai_icon_remove_' . $_aik])) {
                $oldPath = getSetting('ai_icon_' . $_aik, '');
                if ($oldPath) {
                    $fullPath = rtrim(UPLOADS_PATH, '/') . '/' . ltrim($oldPath, '/');
                    if (file_exists($fullPath)) @unlink($fullPath);
                }
                saveSetting('ai_icon_' . $_aik, '');
            }
            /* Upload new icon — only if a file was actually selected */
            if (isset($_FILES['ai_icon_' . $_aik])
                && $_FILES['ai_icon_' . $_aik]['error'] === UPLOAD_ERR_OK
                && !empty($_FILES['ai_icon_' . $_aik]['name'])) {
                $up = uploadFile($_FILES['ai_icon_' . $_aik], 'ai-icons');
                if ($up['success']) {
                    saveSetting('ai_icon_' . $_aik, $up['path']);
                } else {
                    setFlash('error', 'Icon upload failed for ' . ucfirst($_aik) . ': ' . ($up['error'] ?? 'Unknown error'));
                    redirect(ADMIN_URL . '/pages/settings.php?tab=social');
                }
            }
        }

        logActivity('update', 'settings', 'Updated settings — ' . $tab);
        setFlash('success', 'Settings saved successfully!');
        redirect(ADMIN_URL . '/pages/settings.php?tab=' . $tab);
    }

    if ($postAction === 'update_profile') {
        $name        = sanitizeInput($_POST['name']        ?? '');
        $email       = sanitizeInput($_POST['email']       ?? '');
        $designation = sanitizeInput($_POST['designation'] ?? '');
        $bio         = sanitizeInput($_POST['bio']         ?? '');
        $phone       = sanitizeInput($_POST['phone']       ?? '');
        if (!$name || !$email) {
            setFlash('error', 'Name and email are required.');
            redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
        }
        $exists = dbFetchValue("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $admin['id']]);
        if ($exists) {
            setFlash('error', 'That email is already in use by another account.');
            redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
        }
        $upd = ['name' => $name, 'email' => $email];
        if (!empty($_FILES['avatar']['name'])) {
            $up = uploadFile($_FILES['avatar'], 'avatars');
            if ($up['success']) $upd['avatar'] = $up['path'];
        }
        try {
            $upd['designation'] = $designation;
            $upd['bio']         = $bio;
            $upd['phone']       = $phone;
            dbUpdateRow('users', $upd, 'id = ?', [$admin['id']]);
        } catch (PDOException $ex) {
            // Columns may not exist yet; retry with base fields only
            unset($upd['designation'], $upd['bio'], $upd['phone']);
            dbUpdateRow('users', $upd, 'id = ?', [$admin['id']]);
        }
        logActivity('update', 'user', 'Updated own profile');
        setFlash('success', 'Profile updated!');
        redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
    }

    if ($postAction === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $row     = dbFetchOne("SELECT password FROM users WHERE id = ?", [$admin['id']]);
        if (!password_verify($current, $row['password'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
        }
        if (strlen($new) < 8) {
            setFlash('error', 'New password must be at least 8 characters.');
            redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
        }
        if ($new !== $confirm) {
            setFlash('error', 'Passwords do not match.');
            redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
        }
        dbUpdateRow('users', ['password' => Auth::hashPassword($new)], 'id = ?', [$admin['id']]);
        logActivity('update', 'user', 'Changed own password');
        setFlash('success', 'Password changed successfully!');
        redirect(ADMIN_URL . '/pages/settings.php?tab=profile');
    }
}

$s     = getAllSettings();

/* ── Auto-add missing profile columns to users table ── */
(function() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = array_column(db()->query("SHOW COLUMNS FROM users")->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        $adds = [];
        if (!in_array('phone',       $cols)) $adds[] = "ADD COLUMN phone       VARCHAR(30)  DEFAULT NULL";
        if (!in_array('bio',         $cols)) $adds[] = "ADD COLUMN bio         TEXT         DEFAULT NULL";
        if (!in_array('designation', $cols)) $adds[] = "ADD COLUMN designation VARCHAR(150) DEFAULT NULL";
        if (!in_array('avatar',      $cols)) $adds[] = "ADD COLUMN avatar      VARCHAR(255) DEFAULT NULL";
        if (!in_array('preferences', $cols)) $adds[] = "ADD COLUMN preferences JSON         DEFAULT NULL";
        if ($adds) db()->exec("ALTER TABLE users " . implode(', ', $adds));
    } catch (\Throwable $e) {}
})();

$adminRow = dbFetchOne("SELECT * FROM users WHERE id = ?", [$admin['id']]);
if ($adminRow) $admin = $adminRow; // use fresh data; keep session fallback if query fails
require_once dirname(__DIR__) . '/includes/admin-head.php';

$tabs = [
    'general'   => ['fas fa-globe',          'General',        'Site identity, logo & maintenance'],
    'brand'     => ['fas fa-palette',        'Brand',          'Logo, colours, company name & footer'],
    'business'  => ['fas fa-building',        'Business Info',  'Contact details & location'],
    'social'    => ['fas fa-share-alt',       'Social Media',   'Social links & notifications'],
    'homepage'  => ['fas fa-tachometer-alt',  'Homepage',       'Metrics, counters & CTA text'],
    'seo'       => ['fas fa-search',          'SEO & Analytics','Meta tags & tracking codes'],
    'mail'      => ['fas fa-envelope',        'Email / SMTP',   'Outgoing mail configuration'],
    'profile'   => ['fas fa-user-circle',     'My Profile',     'Personal info & password'],
];
?>

<style>
/* ── Settings Tab Bar ── */
.settings-tabs{display:flex;gap:6px;margin-bottom:28px;flex-wrap:wrap}
.settings-tab{
  display:flex;align-items:center;gap:9px;padding:10px 18px;border-radius:12px;
  font-size:13px;font-weight:600;cursor:pointer;transition:var(--transition);
  border:1.5px solid var(--border);background:var(--white);color:var(--gray);
  text-decoration:none;white-space:nowrap;
}
.settings-tab:hover{border-color:rgba(124,58,237,.3);color:var(--violet);background:var(--violet-lt)}
.settings-tab.active{
  background:linear-gradient(135deg,var(--violet),var(--blue));
  color:#fff;border-color:transparent;
  box-shadow:0 4px 14px rgba(124,58,237,.3);
}
.settings-tab i{font-size:13px}

/* ── Settings Grid ── */
.settings-grid{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start}
.settings-grid-full{display:grid;grid-template-columns:1fr;gap:24px}
.settings-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}

/* ── Section Divider ── */
.section-divider{
  display:flex;align-items:center;gap:10px;
  margin:24px 0 20px;font-size:11.5px;font-weight:700;
  color:var(--gray);text-transform:uppercase;letter-spacing:1px;
}
.section-divider::after{content:'';flex:1;height:1px;background:var(--border)}

/* ── Upload Box ── */
.upload-box{
  border:2px dashed var(--border);border-radius:14px;padding:24px;
  text-align:center;cursor:pointer;transition:var(--transition);
  background:var(--light);position:relative;
}
.upload-box:hover{border-color:var(--violet);background:var(--violet-lt)}
.upload-box input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-box .ub-icon{font-size:28px;color:var(--gray2);margin-bottom:10px}
.upload-box .ub-title{font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px}
.upload-box .ub-hint{font-size:12px;color:var(--gray)}

/* ── Preview Image ── */
.preview-img{max-height:70px;border-radius:10px;border:1.5px solid var(--border);object-fit:contain;background:#fff;padding:4px}

/* ── Info Card ── */
.info-block{
  background:linear-gradient(135deg,rgba(124,58,237,.06),rgba(37,99,235,.04));
  border:1px solid rgba(124,58,237,.15);border-radius:12px;
  padding:16px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;
}
.info-block i{color:var(--violet);font-size:15px;margin-top:1px;flex-shrink:0}
.info-block p{font-size:13px;color:var(--text);line-height:1.55}

/* ── Profile Avatar ── */
.profile-avatar-wrap{
  display:flex;flex-direction:column;align-items:center;gap:14px;
  padding:28px 20px;background:linear-gradient(135deg,var(--light),#eef2ff);
  border-radius:16px;border:1px solid var(--border);margin-bottom:20px;
  position:relative;overflow:hidden;
}
.profile-avatar-wrap::before{
  content:'';position:absolute;top:-40px;right:-40px;
  width:140px;height:140px;border-radius:50%;
  background:radial-gradient(circle,rgba(124,58,237,.1),transparent 70%);
}
.profile-avatar-img{
  width:90px;height:90px;border-radius:50%;overflow:hidden;
  border:3px solid var(--white);box-shadow:0 4px 18px rgba(124,58,237,.25);
  background:linear-gradient(135deg,var(--violet),var(--blue));
  display:flex;align-items:center;justify-content:center;
  font-size:32px;font-weight:800;color:#fff;flex-shrink:0;
}
.profile-avatar-img img{width:100%;height:100%;object-fit:cover}
.profile-avatar-name{font-size:16px;font-weight:800;color:var(--primary)}
.profile-avatar-role{font-size:12px;color:var(--gray);text-transform:capitalize;margin-top:2px}
.profile-avatar-upload{position:relative;overflow:hidden}
.profile-avatar-upload input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}

/* ── Password strength ── */
.pw-bar{height:4px;border-radius:4px;background:var(--border);margin-top:8px;overflow:hidden}
.pw-bar-fill{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0}

/* ── Social row ── */
.social-input-row{display:flex;align-items:center;gap:10px}
.social-icon-badge{
  width:44px;height:44px;min-width:44px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  color:#fff;flex-shrink:0;
  box-shadow:0 4px 12px rgba(0,0,0,.25);
  transition:transform .2s,box-shadow .2s;
}
.social-icon-badge:hover{transform:scale(1.08);box-shadow:0 6px 18px rgba(0,0,0,.3)}
.social-icon-badge svg{width:22px;height:22px;fill:currentColor;display:block;}
.social-icon-badge .si-letter{
  font-family:'Figtree',sans-serif;font-size:15px;font-weight:900;
  line-height:1;letter-spacing:-0.5px;user-select:none;
}

/* ── Metric input ── */
.metric-card{
  background:var(--white);border:1.5px solid var(--border);border-radius:14px;
  padding:18px;text-align:center;transition:var(--transition);
}
.metric-card:hover{border-color:rgba(124,58,237,.25);box-shadow:var(--shadow)}
.metric-card .mc-icon{
  width:44px;height:44px;border-radius:12px;
  background:linear-gradient(135deg,var(--violet),var(--blue));
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:#fff;margin:0 auto 12px;
}
.metric-card label{font-size:11.5px;color:var(--gray);text-transform:uppercase;letter-spacing:.6px;font-weight:700;display:block;margin-bottom:8px}
.metric-card input{text-align:center;font-size:20px;font-weight:800}
.metrics-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px}
@media(max-width:900px){.metrics-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){.metrics-grid{grid-template-columns:repeat(2,1fr)}.settings-grid,.settings-grid-2{grid-template-columns:1fr}.settings-tabs{gap:4px}.settings-tab{padding:8px 12px;font-size:12px}}
</style>

<div class="page-header">
  <div>
    <h1>Site Settings</h1>
    <p>Configure all aspects of your website</p>
  </div>
  <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-secondary btn-sm">
    <i class="fas fa-external-link-alt"></i> View Site
  </a>
</div>

<!-- Tab Navigation -->
<div class="settings-tabs">
  <?php foreach ($tabs as $key => [$icon, $label, $desc]): ?>
  <a href="?tab=<?= $key ?>" class="settings-tab <?= $tab === $key ? 'active' : '' ?>">
    <i class="<?= $icon ?>"></i> <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Tab Header Description -->
<div class="info-block" style="margin-bottom:20px">
  <i class="<?= $tabs[$tab][0] ?>"></i>
  <p><strong><?= $tabs[$tab][1] ?></strong> — <?= $tabs[$tab][2] ?></p>
</div>

<?php /* ══════════════════════════════════════════════
   TAB: GENERAL
══════════════════════════════════════════════ */
if ($tab === 'brand'): ?>
<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">

  <!-- ── Logo lockups ── -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-image" style="color:var(--violet);margin-right:8px"></i>Logo &amp; Favicon</span></div>
    <div class="card-body">
      <div class="settings-grid-2">
        <div class="form-group">
          <label>Primary logo <small style="color:var(--gray);font-weight:400">(used on light backgrounds)</small></label>
          <?php if (!empty($s['site_logo'])): ?>
            <div style="margin-bottom:10px;padding:14px;background:#fff;border:1px solid var(--border);border-radius:10px">
              <img src="<?= e(getImageUrl($s['site_logo'])) ?>" alt="Current logo" style="max-height:48px;max-width:100%">
            </div>
          <?php endif; ?>
          <input type="file" name="site_logo" class="form-control" accept="image/*">
        </div>
        <div class="form-group">
          <label>Logo for dark backgrounds <small style="color:var(--gray);font-weight:400">(footer)</small></label>
          <?php if (!empty($s['site_logo_light'])): ?>
            <div style="margin-bottom:10px;padding:14px;background:#0b0f1a;border:1px solid var(--border);border-radius:10px">
              <img src="<?= e(getImageUrl($s['site_logo_light'])) ?>" alt="Current dark-background logo" style="max-height:48px;max-width:100%">
            </div>
          <?php endif; ?>
          <input type="file" name="site_logo_light" class="form-control" accept="image/*">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label>Favicon <small style="color:var(--gray);font-weight:400">(square, 192&times;192 or larger)</small></label>
        <?php if (!empty($s['site_favicon'])): ?>
          <div style="margin-bottom:10px"><img src="<?= e(getImageUrl($s['site_favicon'])) ?>" alt="Current favicon" style="height:44px;width:44px;border-radius:9px;border:1px solid var(--border)"></div>
        <?php endif; ?>
        <input type="file" name="site_favicon" class="form-control" accept="image/*">
      </div>
    </div>
  </div>

  <!-- ── Palette ── -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-swatchbook" style="color:var(--violet);margin-right:8px"></i>Brand Colours</span></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--gray);margin:0 0 18px;line-height:1.65">
        These six colours drive the entire front end &mdash; buttons, headings, gradients, hover states and focus rings.
        They are sampled from the logo gradient; change one and the whole site follows.
      </p>
      <div class="settings-grid">
        <?php
        $brandFields = [
          'brand_accent'    => ['Accent (orange)',    '#FF8A00'],
          'brand_red'       => ['Red',                '#FF3B0D'],
          'brand_secondary' => ['Secondary (magenta)','#FA0E6C'],
          'brand_magenta'   => ['Deep magenta',       '#E0019B'],
          'brand_primary'   => ['Primary (violet)',   '#7A00F0'],
          'brand_violet'    => ['Violet end',         '#5701F9'],
          'brand_ink'       => ['Ink (dark ground)',  '#0B0F1A'],
          'theme_color'     => ['Browser theme',      '#7A00F0'],
        ];
        foreach ($brandFields as $bk => [$blabel, $bdef]):
          $bval = $s[$bk] ?? $bdef;
        ?>
        <div class="form-group">
          <label><?= e($blabel) ?></label>
          <div style="display:flex;gap:9px;align-items:center">
            <input type="color" name="<?= $bk ?>" value="<?= e($bval) ?>"
                   style="width:48px;height:42px;padding:3px;border:1px solid var(--border);border-radius:9px;cursor:pointer;background:#fff"
                   oninput="this.nextElementSibling.value=this.value.toUpperCase()">
            <input type="text" value="<?= e(strtoupper($bval)) ?>" readonly
                   style="flex:1;font-family:monospace;font-size:13px" class="form-control">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label>Signature gradient <small style="color:var(--gray);font-weight:400">(CSS <code>linear-gradient(...)</code> &mdash; leave blank to build it from the colours above)</small></label>
        <input type="text" name="brand_gradient" class="form-control" style="font-family:monospace;font-size:12.5px"
               value="<?= e($s['brand_gradient'] ?? '') ?>">
        <div style="margin-top:12px;height:16px;border-radius:999px;background:<?= e($s['brand_gradient'] ?: 'linear-gradient(90deg,#FF8A00,#FF3B0D,#FA0E6C,#C801AE,#5701F9)') ?>"></div>
      </div>
    </div>
  </div>

  <!-- ── Company identity ── -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-id-card" style="color:var(--violet);margin-right:8px"></i>Company Identity</span></div>
    <div class="card-body">
      <div class="settings-grid-2">
        <div class="form-group">
          <label>Brand name <small style="color:var(--gray);font-weight:400">(header, page titles)</small></label>
          <input type="text" name="site_name" class="form-control" value="<?= e($s['site_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Full legal name <small style="color:var(--gray);font-weight:400">(copyright, invoices, structured data)</small></label>
          <input type="text" name="company_name" class="form-control" value="<?= e($s['company_name'] ?? '') ?>">
        </div>
      </div>
      <div class="settings-grid-2">
        <div class="form-group">
          <label>Tagline</label>
          <input type="text" name="site_tagline" class="form-control" value="<?= e($s['site_tagline'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Positioning headline</label>
          <input type="text" name="site_headline" class="form-control" value="<?= e($s['site_headline'] ?? '') ?>">
        </div>
      </div>
      <div class="settings-grid">
        <div class="form-group"><label>Founded year</label><input type="text" name="company_founded_year" class="form-control" value="<?= e($s['company_founded_year'] ?? '') ?>"></div>
        <div class="form-group"><label>Team size</label><input type="text" name="company_employees" class="form-control" value="<?= e($s['company_employees'] ?? '') ?>" placeholder="11-50"></div>
        <div class="form-group"><label>CIN</label><input type="text" name="company_cin" class="form-control" value="<?= e($s['company_cin'] ?? '') ?>"></div>
        <div class="form-group"><label>GSTIN</label><input type="text" name="company_gstin" class="form-control" value="<?= e($s['company_gstin'] ?? '') ?>"></div>
      </div>
    </div>
  </div>

  <!-- ── Structured address (drives schema.org + geo meta) ── -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-map-location-dot" style="color:var(--violet);margin-right:8px"></i>Registered Address <small style="font-weight:400;color:var(--gray)">&mdash; drives Google structured data</small></span></div>
    <div class="card-body">
      <div class="settings-grid-2">
        <div class="form-group"><label>Street</label><input type="text" name="address_street" class="form-control" value="<?= e($s['address_street'] ?? '') ?>"></div>
        <div class="form-group"><label>City</label><input type="text" name="address_locality" class="form-control" value="<?= e($s['address_locality'] ?? '') ?>"></div>
      </div>
      <div class="settings-grid">
        <div class="form-group"><label>State</label><input type="text" name="address_region" class="form-control" value="<?= e($s['address_region'] ?? '') ?>"></div>
        <div class="form-group"><label>Region code</label><input type="text" name="address_region_code" class="form-control" value="<?= e($s['address_region_code'] ?? '') ?>" placeholder="IN-UP"></div>
        <div class="form-group"><label>PIN code</label><input type="text" name="address_postal" class="form-control" value="<?= e($s['address_postal'] ?? '') ?>"></div>
        <div class="form-group"><label>Country code</label><input type="text" name="address_country" class="form-control" value="<?= e($s['address_country'] ?? '') ?>" placeholder="IN"></div>
        <div class="form-group"><label>Latitude</label><input type="text" name="geo_latitude" class="form-control" value="<?= e($s['geo_latitude'] ?? '') ?>"></div>
        <div class="form-group"><label>Longitude</label><input type="text" name="geo_longitude" class="form-control" value="<?= e($s['geo_longitude'] ?? '') ?>"></div>
      </div>
    </div>
  </div>

  <!-- ── Footer ── -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-window-minimize" style="color:var(--violet);margin-right:8px"></i>Footer</span></div>
    <div class="card-body">
      <div class="form-group">
        <label>About text</label>
        <textarea name="footer_about_text" class="form-control" rows="4"><?= e($s['footer_about_text'] ?? '') ?></textarea>
      </div>
      <div class="settings-grid-2">
        <div class="form-group"><label>Copyright line</label><input type="text" name="copyright_text" class="form-control" value="<?= e($s['copyright_text'] ?? '') ?>" placeholder="All rights reserved."></div>
        <div class="form-group"><label>Sign-off <small style="color:var(--gray);font-weight:400">(blank to hide)</small></label><input type="text" name="footer_built_with" class="form-control" value="<?= e($s['footer_built_with'] ?? '') ?>"></div>
      </div>
      <div class="form-group">
        <label>Trust badges <small style="color:var(--gray);font-weight:400">(comma separated &mdash; <code>Label|fa-icon</code>)</small></label>
        <input type="text" name="footer_badges" class="form-control" value="<?= e($s['footer_badges'] ?? '') ?>">
      </div>
      <div class="settings-grid-2">
        <div class="form-group">
          <label>Quick links column title</label>
          <input type="text" name="footer_links_title" class="form-control" value="<?= e($s['footer_links_title'] ?? 'Quick Links') ?>">
        </div>
        <div class="form-group">
          <label>Visible sections</label>
          <div style="display:flex;flex-direction:column;gap:8px;padding-top:6px">
            <?php foreach ([
              'footer_show_stats'      => 'Stats strip above the footer',
              'footer_show_badges'     => 'Trust badges',
              'footer_show_newsletter' => 'Newsletter signup',
              'footer_show_askai'      => 'Ask AI about us',
            ] as $tk => $tlabel): ?>
            <label style="display:flex;align-items:center;gap:9px;font-size:13.5px;font-weight:600;cursor:pointer">
              <input type="checkbox" name="<?= $tk ?>" <?= (($s[$tk] ?? '1') === '1') ? 'checked' : '' ?> style="width:16px;height:16px">
              <?= e($tlabel) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Quick links <small style="color:var(--gray);font-weight:400">(one per line &mdash; <code>Label | /url | fa-icon | #colour | badge</code>)</small></label>
        <textarea name="footer_quick_links" class="form-control" rows="10" style="font-family:monospace;font-size:12.5px"><?= e($s['footer_quick_links'] ?? '') ?></textarea>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label>Legal links <small style="color:var(--gray);font-weight:400">(bottom bar &mdash; same format)</small></label>
        <textarea name="footer_legal_links" class="form-control" rows="7" style="font-family:monospace;font-size:12.5px"><?= e($s['footer_legal_links'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Brand Settings</button>
</form>

<?php
elseif ($tab === 'general'): ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid">
    <!-- LEFT: Main settings -->
    <div>
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-id-card" style="color:var(--violet)"></i> Site Identity</h3></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Site Name <span class="required">*</span></label>
              <input type="text" name="site_name" class="form-control" value="<?= e($s['site_name'] ?? 'Appsgain Technologies') ?>" required>
              <div class="form-hint">Appears in browser tabs, emails, and admin panel.</div>
            </div>
            <div class="form-group">
              <label>Tagline</label>
              <input type="text" name="site_tagline" class="form-control" value="<?= e($s['site_tagline'] ?? '') ?>" placeholder="Transforming Ideas into Digital Solutions">
            </div>
          </div>
          <div class="form-group">
            <label>Site Description</label>
            <textarea name="site_description" class="form-control" rows="3" placeholder="A brief description of your company or website..."><?= e($s['site_description'] ?? '') ?></textarea>
            <div class="form-hint">Used as the default meta description when no page-specific description is set.</div>
          </div>
          <div class="form-group">
            <label>Footer Copyright Text</label>
            <input type="text" name="footer_text" class="form-control" value="<?= e($s['footer_text'] ?? '') ?>" placeholder="© <?= date('Y') ?> Appsgain Technologies. All rights reserved.">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Items Per Page (pagination)</label>
              <input type="number" name="items_per_page" class="form-control" value="<?= e($s['items_per_page'] ?? '10') ?>" min="5" max="100">
            </div>
            <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;padding-top:28px">
              <div>
                <label style="display:block;margin-bottom:4px">Maintenance Mode</label>
                <div class="form-hint" style="margin:0">Temporarily take the site offline.</div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($s['maintenance_mode']) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save General Settings</button>
        </div>
      </div>
    </div>

    <!-- RIGHT: Logo & Favicon -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-image" style="color:var(--violet)"></i> Site Logo</h3></div>
        <div class="card-body">
          <?php if (!empty($s['site_logo'])): ?>
          <div style="margin-bottom:14px;padding:12px;background:var(--light);border-radius:10px;text-align:center">
            <img src="<?= getImageUrl($s['site_logo']) ?>" class="preview-img" alt="Logo" style="max-height:60px">
            <div style="font-size:11px;color:var(--gray);margin-top:6px">Current logo</div>
          </div>
          <?php endif; ?>
          <div class="upload-box">
            <input type="file" name="site_logo" accept="image/*">
            <div class="ub-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="ub-title">Click to upload logo</div>
            <div class="ub-hint">PNG, SVG, or WebP recommended • Max 2MB</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3><i class="fas fa-star" style="color:var(--amber)"></i> Favicon</h3></div>
        <div class="card-body">
          <?php if (!empty($s['site_favicon'])): ?>
          <div style="margin-bottom:14px;padding:12px;background:var(--light);border-radius:10px;text-align:center">
            <img src="<?= getImageUrl($s['site_favicon']) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:contain" alt="Favicon">
            <div style="font-size:11px;color:var(--gray);margin-top:6px">Current favicon</div>
          </div>
          <?php endif; ?>
          <div class="upload-box">
            <input type="file" name="site_favicon" accept="image/*,.ico">
            <div class="ub-icon"><i class="fas fa-thumbtack"></i></div>
            <div class="ub-title">Upload favicon</div>
            <div class="ub-hint">.ICO, PNG 32×32px or 64×64px</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php /* ══════════════════════════════════════════════
   TAB: BUSINESS INFO
══════════════════════════════════════════════ */
elseif ($tab === 'business'): ?>

<form method="POST">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid-2">
    <!-- Contact Details -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-phone-alt" style="color:var(--emerald)"></i> Contact Details</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label>Primary Phone</label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray)"><i class="fas fa-phone"></i></span>
            <input type="text" name="site_phone" class="form-control" style="padding-left:36px" value="<?= e($s['site_phone'] ?? '') ?>" placeholder="+91-9955446477">
          </div>
        </div>
        <div class="form-group">
          <label>Secondary Phone <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray)"><i class="fas fa-phone"></i></span>
            <input type="text" name="site_phone2" class="form-control" style="padding-left:36px" value="<?= e($s['site_phone2'] ?? '') ?>" placeholder="+91-XXXXXXXXXX">
          </div>
        </div>
        <div class="form-group">
          <label>Primary Email</label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray)"><i class="fas fa-envelope"></i></span>
            <input type="email" name="site_email" class="form-control" style="padding-left:36px" value="<?= e($s['site_email'] ?? '') ?>" placeholder="info@appsgain.in">
          </div>
        </div>
        <div class="form-group">
          <label>Support / Secondary Email <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray)"><i class="fas fa-envelope"></i></span>
            <input type="email" name="site_email2" class="form-control" style="padding-left:36px" value="<?= e($s['site_email2'] ?? '') ?>" placeholder="support@appsgain.in">
          </div>
        </div>
        <div class="form-group">
          <label>WhatsApp Number</label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#25d366"><i class="fab fa-whatsapp"></i></span>
            <input type="text" name="site_whatsapp" class="form-control" style="padding-left:36px" value="<?= e($s['site_whatsapp'] ?? '') ?>" placeholder="91XXXXXXXXXX (without + or spaces)">
          </div>
          <div class="form-hint">Used to generate WhatsApp chat links.</div>
        </div>
        <div class="form-group">
          <label>Lead Notification Email</label>
          <div style="position:relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--amber)"><i class="fas fa-bell"></i></span>
            <input type="email" name="contact_email" class="form-control" style="padding-left:36px" value="<?= e($s['contact_email'] ?? '') ?>" placeholder="leads@appsgain.in">
          </div>
          <div class="form-hint">New enquiries will be forwarded to this address.</div>
        </div>
      </div>
    </div>

    <!-- Location & Hours -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-map-marker-alt" style="color:var(--rose)"></i> Office Location</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Primary Office Label</label>
            <input type="text" name="site_address_label" class="form-control" placeholder="Head Office" value="<?= e($s['site_address_label'] ?? '') ?>">
            <div class="form-hint">Shown above the address in the footer and on the contact page.</div>
          </div>
          <div class="form-group">
            <label>Full Address</label>
            <textarea name="site_address" class="form-control" rows="3" placeholder="Karkardooma, Anand Vihar&#10;New Delhi – 110092, India"><?= e($s['site_address'] ?? '') ?></textarea>
            <div class="form-hint">This one drives the map, so keep it a mappable street address.</div>
          </div>
          <div class="form-group">
            <label>Second Office Label</label>
            <input type="text" name="site_address_2_label" class="form-control" placeholder="Bengaluru (Remote)" value="<?= e($s['site_address_2_label'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Second Address</label>
            <textarea name="site_address_2" class="form-control" rows="3" placeholder="Bengaluru, Karnataka, India&#10;(Remote)"><?= e($s['site_address_2'] ?? '') ?></textarea>
            <div class="form-hint">Leave blank to show a single office. Clearing it removes the second entry everywhere.</div>
          </div>
          <div class="form-group">
            <label>Google Maps Embed URL</label>
            <textarea name="google_maps_embed" class="form-control" rows="2" placeholder="https://www.google.com/maps/embed?pb=..."><?= e($s['google_maps_embed'] ?? '') ?></textarea>
            <div class="form-hint">Go to Google Maps → Share → Embed a map → copy the src URL.</div>
          </div>
        </div>
      </div>


      <!-- ── App Download Links ── -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-mobile-alt" style="color:var(--violet)"></i> App Download Links</h3>
        </div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--gray);margin-bottom:16px">
            These links appear in the footer "Download Our Apps" section. Leave blank to hide a badge.
          </p>

          <!-- Google Play -->
          <!-- Google Play -->
          <?php /* ── Founder bio page ── */ ?>
          <div style="margin:26px 0 14px;padding-top:18px;border-top:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin:0 0 4px;">
              <i class="fas fa-user-tie" style="color:var(--violet);margin-right:6px;"></i>Founder bio page
            </h3>
            <p style="font-size:12.5px;color:var(--gray);margin:0;">
              Drives <a href="<?= SITE_URL ?>/our-founder.php" target="_blank" rel="noopener" style="color:var(--violet);">/our-founder.php</a>.
              Reachable from the HTML sitemap only — it is intentionally not in the header or footer nav.
            </p>
          </div>
          <div class="form-group">
            <label>Founder name</label>
            <input type="text" name="founder_name" class="form-control" maxlength="120"
                   value="<?= e($s['founder_name'] ?? '') ?>" placeholder="Prashant Kumar">
          </div>
          <div class="form-group">
            <label>Role / title</label>
            <input type="text" name="founder_role" class="form-control" maxlength="160"
                   value="<?= e($s['founder_role'] ?? '') ?>" placeholder="Founder &amp; CEO, Appsgain Technologies Private Limited">
          </div>
          <div class="form-group">
            <label>Founder email</label>
            <input type="email" name="founder_email" class="form-control" maxlength="160"
                   value="<?= e($s['founder_email'] ?? '') ?>" placeholder="info@appsgain.in">
          </div>
          <div class="form-group">
            <label>LinkedIn URL</label>
            <input type="url" name="founder_linkedin" class="form-control" maxlength="300"
                   value="<?= e($s['founder_linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/prashantdevtech">
          </div>
          <div class="form-group">
            <label>Portrait</label>
            <input type="text" name="founder_photo" class="form-control" maxlength="300"
                   value="<?= e($s['founder_photo'] ?? '') ?>"
                   placeholder="team/prashant-kumar-founder-appsgain-technologies.webp">
            <div class="form-hint">Path under /uploads, or a full URL. Upload via Media Library first.
              Portrait orientation (4:5) works best. Until this is set the page shows a monogram.</div>
            <?php if (!empty($s['founder_photo'])): ?>
              <img src="<?= UPLOADS_URL ?>/<?= e($s['founder_photo']) ?>" alt=""
                   style="margin-top:8px;height:80px;border-radius:8px;object-fit:cover;">
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Founded year</label>
            <input type="text" name="founder_since" class="form-control" maxlength="4"
                   value="<?= e($s['founder_since'] ?? '') ?>" placeholder="2018">
          </div>
          <div class="form-group" style="margin-bottom:26px;">
            <label>Education</label>
            <input type="text" name="founder_education" class="form-control" maxlength="200"
                   value="<?= e($s['founder_education'] ?? '') ?>"
                   placeholder="Babasaheb Bhimrao Ambedkar Bihar University">
          </div>

          <div class="form-group">
            <label>Footer heading</label>
            <input type="text" name="footer_apps_title" class="form-control" maxlength="60"
                   value="<?= e($s['footer_apps_title'] ?? '') ?>"
                   placeholder="Download Our Apps">
            <div class="form-hint">Shown above the store badges in the footer brand column.</div>
          </div>

          <!-- Header "Pay Now" button -->
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px">
              <span style="width:28px;height:28px;background:linear-gradient(135deg,#FF8A00,#F50072,#6A00FF);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fas fa-bolt" style="color:#fff;font-size:12px"></i>
              </span>
              Payment Link <span style="font-weight:400;color:var(--gray)">(header Pay Now button)</span>
            </label>
            <input type="url" name="payment_link" class="form-control"
                   value="<?= e($s['payment_link'] ?? '') ?>"
                   placeholder="https://payments.cashfree.com/forms/appsgaintechnologies">
            <div class="form-hint">Opens in a new tab. Leave blank to remove the button from the header and mobile menu entirely.</div>
          </div>
          <div class="form-group" style="margin-bottom:18px">
            <label>Payment Button Label</label>
            <input type="text" name="payment_label" class="form-control" maxlength="24"
                   value="<?= e($s['payment_label'] ?? '') ?>"
                   placeholder="Pay Now">
            <div class="form-hint">Keep it short — the header has limited room. Defaults to &ldquo;Pay Now&rdquo;.</div>
          </div>

          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px">
              <span style="width:28px;height:28px;background:linear-gradient(135deg,#34a853,#4285f4);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fab fa-google-play" style="color:#fff;font-size:12px"></i>
              </span>
              Google Play Store URL
            </label>
            <input type="url" name="app_google_play" class="form-control"
                   value="<?= e($s['app_google_play'] ?? '') ?>"
                   placeholder="https://play.google.com/store/apps/details?id=com.appsgain.app">
            <div class="form-hint">Full URL from the Google Play store listing.</div>
          </div>
          <!-- Show/hide Play Store button -->
          <div class="form-group" style="margin-bottom:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--light);padding:10px 14px;border-radius:9px;border:1px solid var(--border);">
              <div style="font-size:13px;font-weight:600;color:var(--primary);display:flex;align-items:center;gap:7px;">
                <i class="fab fa-google-play" style="color:#34a853;font-size:13px;"></i>
                Show Google Play button in footer
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="footer_show_gplay" value="1" <?= ($s['footer_show_gplay'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <!-- App Store -->
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px">
              <span style="width:28px;height:28px;background:linear-gradient(135deg,#555,#111);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fab fa-apple" style="color:#fff;font-size:14px"></i>
              </span>
              Apple App Store URL
            </label>
            <input type="url" name="app_apple_store" class="form-control"
                   value="<?= e($s['app_apple_store'] ?? '') ?>"
                   placeholder="https://apps.apple.com/app/appsgain/idXXXXXXXXXX">
            <div class="form-hint">Full URL from the Apple App Store listing.</div>
          </div>
          <!-- Show/hide App Store button -->
          <div class="form-group" style="margin-bottom:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--light);padding:10px 14px;border-radius:9px;border:1px solid var(--border);">
              <div style="font-size:13px;font-weight:600;color:var(--primary);display:flex;align-items:center;gap:7px;">
                <i class="fab fa-apple" style="color:#555;font-size:14px;"></i>
                Show App Store button in footer
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="footer_show_appstore" value="1" <?= ($s['footer_show_appstore'] ?? '0') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div style="font-size:11.5px;color:var(--gray);margin-top:6px;padding-left:4px;"><i class="fas fa-info-circle" style="color:var(--amber);"></i> Leave the URL blank while the iOS app is unreleased — the badge shows as "Coming soon".</div>
          </div>

          <?php if (!empty($s['app_google_play']) || !empty($s['app_apple_store'])): ?>
          <div style="margin-top:16px;padding:12px 14px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);border-radius:9px;font-size:12.5px;color:var(--emerald)">
            <i class="fas fa-check-circle"></i>
            <?= !empty($s['app_google_play']) ? 'Google Play ✓' : '' ?>
            <?= (!empty($s['app_google_play']) && !empty($s['app_apple_store'])) ? ' · ' : '' ?>
            <?= !empty($s['app_apple_store']) ? 'App Store ✓' : '' ?>
            — badge(s) showing in footer.
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
  <div style="margin-top:20px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Business Info</button>
  </div>
</form>

<?php /* ══════════════════════════════════════════════
   TAB: SOCIAL MEDIA
══════════════════════════════════════════════ */
elseif ($tab === 'social'): ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid-2">
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-share-alt" style="color:var(--violet)"></i> Social Media Profiles</h3></div>
      <div class="card-body">
        <?php
        /* SVGs are inline — never depend on external font files */
        $socials = [
          'site_facebook'  => ['#1877f2','Facebook',      'https://facebook.com/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
          'site_instagram' => ['#e1306c','Instagram',     'https://instagram.com/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162S8.597 18.163 12 18.163s6.162-2.759 6.162-6.162S15.403 5.838 12 5.838zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>'],
          'site_twitter'   => ['#000000','Twitter / X',  'https://x.com/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
          'site_linkedin'  => ['#0077b5','LinkedIn',      'https://linkedin.com/company/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>'],
          'site_youtube'   => ['#ff0000','YouTube',       'https://youtube.com/@appsgain',
            '<svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'],
          'site_github'    => ['#24292e','GitHub',        'https://github.com/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>'],
          'site_threads'   => ['#101010','Threads (Meta)','https://threads.net/@appsgain',
            '<svg viewBox="0 0 192 192"><path d="M141.537 88.988a66.667 66.667 0 0 0-2.518-1.143c-1.482-27.307-16.403-42.94-41.457-43.1h-.34c-14.986 0-27.449 6.396-35.12 18.036l13.779 9.452c5.73-8.695 14.724-10.548 21.348-10.548h.229c8.249.053 14.474 2.452 18.503 7.129 2.932 3.405 4.893 8.111 5.864 14.05-7.314-1.244-15.224-1.626-23.68-1.14-23.82 1.371-39.134 15.264-38.105 34.568.522 9.792 5.4 18.216 13.735 23.719 7.047 4.652 16.124 6.927 25.557 6.412 12.458-.683 22.231-5.436 29.049-14.127 5.178-6.6 8.452-15.153 9.899-25.93 5.937 3.583 10.337 8.298 12.767 13.966 4.132 9.635 4.373 25.468-8.546 38.376-11.319 11.308-24.925 16.2-45.488 16.35-22.809-.169-40.06-7.484-51.275-21.742C35.236 139.966 29.808 120.682 29.605 96c.203-24.682 5.63-43.966 16.133-57.317C57.053 24.425 74.303 17.11 97.113 16.942c22.95.17 40.526 7.52 52.263 21.85 5.765 7.045 10.108 15.792 12.935 26.137l16.222-4.3c-3.446-12.835-8.987-23.886-16.596-33.046C147.44 9.598 125.358.2 97.2 0h-.238C68.65.2 46.513 9.65 31.3 28.2 17.77 44.674 10.804 68.054 10.55 96v.032c.254 27.946 7.22 51.327 20.73 67.8C46.513 182.35 68.649 191.8 96.962 192h.238c25.168-.18 42.87-6.768 57.444-21.332 19.653-19.64 19.074-44.116 12.6-59.204-4.972-11.587-14.028-20.556-25.707-26.476Zm-44.759 42.604c-10.426.587-21.258-4.098-21.808-14.141-.388-7.256 5.16-15.354 21.895-16.324a103.3 103.3 0 0 1 6.081-.172c5.921 0 11.541.557 16.79 1.607-1.902 23.765-12.912 28.348-22.958 29.03Z"/></svg>'],
          'site_gmb'       => ['#4285f4','Google Business Profile','https://g.page/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M12 11v2.4h5.66c-.24 1.47-1.72 4.31-5.66 4.31-3.4 0-6.18-2.82-6.18-6.29S8.6 5.13 12 5.13c1.94 0 3.24.83 3.98 1.54l2.71-2.61C16.95 2.5 14.68 1.6 12 1.6 6.66 1.6 2.34 5.92 2.34 11.26S6.66 20.92 12 20.92c5.57 0 9.27-3.92 9.27-9.44 0-.64-.07-1.12-.16-1.6H12z"/></svg>'],
          'site_pinterest' => ['#bd081c','Pinterest',     'https://pinterest.com/appsgain',
            '<svg viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146A12 12 0 0 0 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/></svg>'],
          'site_whatsapp_channel' => ['#25d366','WhatsApp Channel','https://whatsapp.com/channel/xxxxx',
            '<svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0 0 20.464 3.488"/></svg>'],
        ];
        foreach ($socials as $key => [$color, $label, $placeholder, $svg]):
        ?>
        <div class="form-group">
          <label style="text-transform:uppercase;font-size:11.5px;font-weight:800;letter-spacing:.6px;color:var(--primary);"><?= $label ?></label>
          <div class="social-input-row">
            <div class="social-icon-badge" style="background:<?= $color ?>;"><?= $svg ?></div>
            <input type="url" name="<?= $key ?>" class="form-control"
                   value="<?= e($s[$key] ?? '') ?>" placeholder="<?= $placeholder ?>">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-eye" style="color:var(--cyan)"></i> Preview</h3></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--gray);margin-bottom:16px">Links will appear in the website header and footer. Leave blank to hide.</p>
          <div style="display:flex;flex-wrap:wrap;gap:10px">
            <?php foreach ($socials as $key => [$color, $label, , $svg]): ?>
            <?php if (!empty($s[$key])): ?>
            <a href="<?= e($s[$key]) ?>" target="_blank" title="<?= e($label) ?>"
               style="width:44px;height:44px;border-radius:12px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;box-shadow:0 3px 10px rgba(0,0,0,.2);transition:transform .2s;"
               onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform=''">
              <?= $svg ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if (empty(array_filter(array_map(function($k) use ($s) { return $s[$k] ?? ''; }, array_keys($socials))))): ?>
          <p style="font-size:12px;color:var(--gray2);font-style:italic">No social links configured yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- ══ NOTIFICATION RECIPIENTS ══ -->
  <div class="card" style="margin-top:28px;">
    <div class="card-header">
      <h3><i class="fas fa-bell" style="color:var(--amber);"></i> Form Submission — Email Notification Recipients</h3>
    </div>
    <div class="card-body">
      <div class="info-block" style="margin-bottom:20px;">
        <i class="fas fa-info-circle"></i>
        <p>Configure which email addresses receive notifications when visitors submit forms. Separate multiple addresses with commas. Leave blank to use the Business Email as fallback.</p>
      </div>

      <!-- Global / All Forms -->
      <div style="background:linear-gradient(135deg,rgba(124,58,237,.06),rgba(37,99,235,.04));border:1.5px solid rgba(124,58,237,.15);border-radius:14px;padding:18px 20px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
          <span style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-globe" style="color:#fff;font-size:14px;"></i></span>
          <div>
            <div style="font-weight:800;font-size:14px;color:var(--primary);">Global Recipients (All Forms)</div>
            <div style="font-size:12px;color:var(--gray);">Receives notifications from ALL form types as a catch-all</div>
          </div>
        </div>
        <input type="text" name="notify_all" class="form-control notify-input"
          value="<?= e($s['notify_all'] ?? $s['contact_email'] ?? '') ?>"
          placeholder="admin@appsgain.in, team@appsgain.in"
          oninput="updateRecipientPreview(this, 'prev_all')">
        <div id="prev_all" class="notify-preview"><?= buildNotifyPreview($s['notify_all'] ?? $s['contact_email'] ?? '') ?></div>
      </div>

      <!-- Per-form type -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php
        $formTypes = [
          'notify_contact'     => ['fa-envelope',         '#2563eb', 'Contact Form',            'contact.php, service-detail.php'],
          'notify_application' => ['fa-file-alt',         '#d97706', 'Job Application',         'careers.php'],
          'notify_newsletter'  => ['fa-newspaper',        '#059669', 'Newsletter Signup',       'Footer newsletter form'],
          'notify_partner'     => ['fa-handshake',        '#7c3aed', 'Partnership Inquiry',     'partners.php'],
          'notify_support'     => ['fa-headset',          '#0891b2', 'Support Request',         'support.php forms'],
          'notify_enquiry'     => ['fa-comments',         '#be185d', 'Service Enquiry',         'service-detail.php sidebar'],
        ];
        foreach ($formTypes as $key => [$icon, $color, $label, $source]):
          $prevId = 'prev_' . str_replace('notify_', '', $key);
        ?>
        <div style="background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:16px;">
          <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px;">
            <span style="width:32px;height:32px;border-radius:9px;background:<?= $color ?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas <?= $icon ?>" style="color:<?= $color ?>;font-size:13px;"></i>
            </span>
            <div>
              <div style="font-weight:700;font-size:13px;color:var(--primary);"><?= $label ?></div>
              <div style="font-size:11px;color:var(--gray2);margin-top:1px;"><?= $source ?></div>
            </div>
          </div>
          <input type="text" name="<?= $key ?>" class="form-control notify-input"
            style="font-size:13px;"
            value="<?= e($s[$key] ?? '') ?>"
            placeholder="Leave blank to use Global recipients"
            oninput="updateRecipientPreview(this, '<?= $prevId ?>')">
          <div id="<?= $prevId ?>" class="notify-preview"><?= buildNotifyPreview($s[$key] ?? '') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ══ AI ASSISTANT ICONS ══ -->
  <div class="card" style="margin-top:28px;">
    <div class="card-header">
      <h3><i class="fas fa-robot" style="color:var(--violet);"></i> Footer AI Assistant Icons</h3>
    </div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--gray);margin-bottom:18px;">
        Control which AI assistant icons appear in the footer. When clicked, they open with a pre-built prompt about your company.
        Optionally override the base URL (leave blank to use the default).
      </p>
      <?php
      $aiSettingsList = [
        'chatgpt'    => ['ChatGPT',    '#10a37f', 'fab fa-robot',      'https://chatgpt.com/?q='],
        'gemini'     => ['Gemini',     '#4285f4', 'fas fa-diamond',    'https://gemini.google.com/app?q='],
        'claude'     => ['Claude',     '#e8734a', 'fas fa-asterisk',   'https://claude.ai/new?q='],
        'perplexity' => ['Perplexity', '#20d5b2', 'fas fa-wave-square','https://www.perplexity.ai/?q='],
        'grok'       => ['Grok',       '#1a1a1a', 'fab fa-x-twitter',  'https://x.com/i/grok?text='],
      ];
      foreach ($aiSettingsList as $aiKey => [$aiLabel, $aiColor, $aiIcon, $aiDefault]):
        $isOn      = ($s['ai_show_' . $aiKey] ?? '1') === '1';
        $aiUrl     = $s['ai_url_'  . $aiKey] ?? '';
        $customIco = $s['ai_icon_' . $aiKey] ?? '';
      ?>
      <div style="padding:16px 0;border-bottom:1px solid var(--border);">
        <div style="display:grid;grid-template-columns:52px 1fr auto;gap:14px;align-items:flex-start;">

          <!-- Current Icon Preview -->
          <div style="text-align:center;">
            <div id="aiIconPrev_<?= $aiKey ?>"
                 style="width:44px;height:44px;border-radius:12px;background:<?= $aiColor ?>;
                        display:flex;align-items:center;justify-content:center;overflow:hidden;
                        box-shadow:0 3px 10px rgba(0,0,0,.2);border:2px solid var(--border);">
              <?php if ($customIco): ?>
              <img src="<?= UPLOADS_URL.'/'.$customIco ?>" style="width:100%;height:100%;object-fit:cover;" id="aiIconImg_<?= $aiKey ?>">
              <?php else: ?>
              <i class="<?= $aiIcon ?>" style="color:#fff;font-size:16px;" id="aiIconImg_<?= $aiKey ?>"></i>
              <?php endif; ?>
            </div>
          </div>

          <!-- Name + URL + Upload -->
          <div>
            <div style="font-size:14px;font-weight:800;color:var(--primary);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
              <span style="width:8px;height:8px;border-radius:50%;background:<?= $aiColor ?>;display:inline-block;"></span>
              <?= $aiLabel ?>
            </div>

            <!-- Custom icon upload -->
            <div style="margin-bottom:10px;">
              <label style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;">
                Custom Icon <span style="font-weight:400;color:var(--gray2)">(PNG/JPG/SVG/WEBP · 64×64 recommended)</span>
              </label>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <label style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;background:var(--light);border:1.5px solid var(--border);cursor:pointer;font-size:12.5px;font-weight:600;color:var(--text);transition:.2s;" onmouseover="this.style.borderColor='var(--violet)'" onmouseout="this.style.borderColor='var(--border)'">
                  <i class="fas fa-upload" style="color:var(--violet);font-size:11px;"></i>
                  <?= $customIco ? 'Replace Icon' : 'Upload Icon' ?>
                  <input type="file" name="ai_icon_<?= $aiKey ?>" accept="image/*"
                         style="display:none;" onchange="previewAiIcon('<?= $aiKey ?>',this)">
                </label>
                <?php if ($customIco): ?>
                <button type="button" onclick="removeAiIcon('<?= $aiKey ?>')"
                        class="btn btn-danger btn-sm" style="font-size:12px;padding:6px 12px;">
                  <i class="fas fa-trash"></i> Remove
                </button>
                <input type="hidden" name="ai_icon_remove_<?= $aiKey ?>" id="aiRemove_<?= $aiKey ?>" value="">
                <span id="aiRemoveStatus_<?= $aiKey ?>" style="display:none;font-size:11.5px;color:var(--rose);font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Will be removed on save</span>
                <?php else: ?>
                <input type="hidden" name="ai_icon_remove_<?= $aiKey ?>" id="aiRemove_<?= $aiKey ?>" value="">
                <span id="aiRemoveStatus_<?= $aiKey ?>" style="display:none;font-size:11.5px;color:var(--gray2);">Using default icon</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- URL override -->
            <div>
              <label style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;">Custom URL Override</label>
              <input type="url" name="ai_url_<?= $aiKey ?>" class="form-control" style="font-size:13px;"
                value="<?= e($aiUrl) ?>" placeholder="Default: <?= e($aiDefault) ?>PROMPT">
              <div style="font-size:11px;color:var(--gray2);margin-top:3px;">Leave blank to use default. Prompt is auto-appended — do not include it here.</div>
            </div>
          </div>

          <!-- Toggle -->
          <div style="display:flex;flex-direction:column;align-items:center;gap:5px;padding-top:4px;">
            <label class="toggle-switch">
              <input type="checkbox" name="ai_show_<?= $aiKey ?>" value="1" <?= $isOn ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:10px;color:var(--gray2);font-weight:600;white-space:nowrap;"><?= $isOn ? 'Visible' : 'Hidden' ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:14px;padding:11px 14px;background:rgba(79,70,229,.06);border-radius:9px;border:1px solid rgba(79,70,229,.15);font-size:12.5px;color:var(--violet);display:flex;align-items:flex-start;gap:8px;">
        <i class="fas fa-info-circle" style="margin-top:1px;flex-shrink:0;"></i>
        <span>The AI prompt is automatically generated from your company name, services, phone, email and website. It updates whenever you save settings.</span>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Social Links &amp; Notification Settings</button>
    <span style="font-size:12.5px;color:var(--gray);" id="notifyTestStatus"></span>
  </div>
</form>
<script>
/* ── AI Icon live preview ── */
function previewAiIcon(key, input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var wrap = document.getElementById('aiIconPrev_' + key);
    var el   = document.getElementById('aiIconImg_' + key);
    if (!wrap || !el) return;
    /* Replace icon/img with new preview image */
    wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">';
  };
  reader.readAsDataURL(input.files[0]);
}

/* ── AI Icon remove ── */
function removeAiIcon(key) {
  if (!confirm('Remove custom icon for ' + key.toUpperCase() + '? It will revert to the default icon on save.')) return;
  document.getElementById('aiRemove_' + key).value = '1';
  var status = document.getElementById('aiRemoveStatus_' + key);
  if (status) { status.style.display = 'inline-flex'; }
  /* Show strikethrough on preview */
  var wrap = document.getElementById('aiIconPrev_' + key);
  if (wrap) wrap.style.opacity = '.4';
}
</script>
<style>
.notify-input:focus { border-color:var(--violet) !important; }
.notify-preview { margin-top:6px; min-height:4px; }
.notify-pills { display:flex; flex-wrap:wrap; gap:5px; }
.notify-pill {
  display:inline-flex; align-items:center; gap:4px;
  padding:3px 9px; border-radius:20px;
  font-size:11.5px; font-weight:600;
  background:rgba(5,150,105,.1); color:#059669;
  border:1px solid rgba(5,150,105,.2);
}
.notify-pill.invalid {
  background:rgba(225,29,72,.1); color:#e11d48;
  border-color:rgba(225,29,72,.2);
}
.notify-pill i { font-size:10px; }
</style>
<script>
function updateRecipientPreview(input, previewId) {
  var container = document.getElementById(previewId);
  if (!container) return;
  var val = input.value.trim();
  if (!val) { container.innerHTML = ''; return; }
  var emails = val.split(/[,;\n]+/);
  var html = '<div class="notify-pills">';
  emails.forEach(function(e) {
    e = e.trim();
    if (!e) return;
    var valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
    html += '<span class="notify-pill' + (valid ? '' : ' invalid') + '">'
          + (valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>')
          + ' ' + e.replace(/&/g,'&amp;').replace(/</g,'&lt;')
          + '</span>';
  });
  html += '</div>';
  container.innerHTML = html;
}
</script>

<?php /* ══════════════════════════════════════════════
   TAB: HOMEPAGE
══════════════════════════════════════════════ */
elseif ($tab === 'homepage'): ?>

<form method="POST">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">

  <!-- Metrics -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><i class="fas fa-chart-bar" style="color:var(--violet)"></i> Achievement Counters</h3></div>
    <div class="card-body">
      <div class="info-block">
        <i class="fas fa-info-circle"></i>
        <p>These numbers appear in the homepage stats section and the About page. Update them to reflect your current achievements.</p>
      </div>
      <div class="metrics-grid">
        <?php
        $metrics = [
            'metric_projects'  => ['fas fa-briefcase',    'Projects Done',    '200+'],
            'metric_clients'   => ['fas fa-smile',        'Happy Clients',    '150+'],
            'metric_years'     => ['fas fa-calendar-alt', 'Years of Exp.',    '8+'],
            'metric_experts'   => ['fas fa-users',        'Team Experts',     '15+'],
            'metric_retention' => ['fas fa-heart',        'Client Retention', '98%'],
        ];
        foreach ($metrics as $key => [$icon, $label, $placeholder]):
        ?>
        <div class="metric-card">
          <div class="mc-icon"><i class="<?= $icon ?>"></i></div>
          <label><?= $label ?></label>
          <input type="text" name="<?= $key ?>" class="form-control"
                 value="<?= e($s[$key] ?? $placeholder) ?>" placeholder="<?= $placeholder ?>">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Training / Placement Stats -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><i class="fas fa-graduation-cap" style="color:var(--emerald)"></i> Training & Placement Stats</h3></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label>Total Placed Students</label>
          <input type="text" name="placement_total" class="form-control" value="<?= e($s['placement_total'] ?? '500+') ?>" placeholder="500+">
        </div>
        <div class="form-group">
          <label>Placement Rate</label>
          <input type="text" name="placement_rate" class="form-control" value="<?= e($s['placement_rate'] ?? '95%') ?>" placeholder="95%">
        </div>
      </div>
      <div class="form-group">
        <label>Courses Enrollment CTA Text</label>
        <input type="text" name="courses_enrollment_cta" class="form-control" value="<?= e($s['courses_enrollment_cta'] ?? 'Enroll Now — Start Your Journey') ?>" placeholder="Enroll Now — Start Your Journey">
        <div class="form-hint">Button text shown on the Courses page hero section.</div>
      </div>
    </div>
  </div>

  <!-- Why Choose Us Section -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><i class="fas fa-star" style="color:var(--amber)"></i> "Why Choose Us" Section</h3></div>
    <div class="card-body">
      <div class="info-block">
        <i class="fas fa-info-circle"></i>
        <p>Controls the <strong>"Why Businesses Choose Us"</strong> section on the homepage. Edit the title, subtitle, and each advantage card.</p>
      </div>
      <div class="form-group">
        <label>Section Title</label>
        <input type="text" name="homepage_why_title" class="form-control"
               value="<?= e($s['homepage_why_title'] ?? 'Why Businesses Choose Us') ?>"
               placeholder="Why Businesses Choose Us">
      </div>
      <div class="form-group">
        <label>Section Subtitle</label>
        <input type="text" name="homepage_why_subtitle" class="form-control"
               value="<?= e($s['homepage_why_subtitle'] ?? "We're not just a vendor — we're a technology partner committed to your long-term success.") ?>"
               placeholder="We're not just a vendor — we're a technology partner...">
      </div>
      <div class="form-group">
        <label>Advantage Cards (JSON)</label>
        <?php
        $defaultWhy = json_encode([
          ['icon'=>'fa-rocket',            'color'=>'#3b82f6','title'=>'Fast Time-to-Market',   'desc'=>'Agile sprints and pre-built components deliver your MVP in weeks, not months.'],
          ['icon'=>'fa-expand-arrows-alt', 'color'=>'#8b5cf6','title'=>'Scalable Architecture',  'desc'=>'Cloud-native architecture that grows from 100 to 10 million users seamlessly.'],
          ['icon'=>'fa-shield-alt',        'color'=>'#10b981','title'=>'Security First',          'desc'=>'OWASP-compliant code, NDA protection, regular audits and penetration testing.'],
          ['icon'=>'fa-headset',           'color'=>'#f59e0b','title'=>'Dedicated Support',      'desc'=>'Dedicated project manager, daily stand-ups, and 24/7 post-launch support.'],
          ['icon'=>'fa-tags',              'color'=>'#06b6d4','title'=>'Transparent Pricing',    'desc'=>'Fixed-price or time-and-materials — no hidden costs, no billing surprises.'],
          ['icon'=>'fa-award',             'color'=>'#e11d48','title'=>'Proven Track Record',    'desc'=>'200+ successful projects across startups, SMBs and enterprises worldwide.'],
        ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        $currentWhy = $s['homepage_why_items'] ?? $defaultWhy;
        ?>
        <textarea name="homepage_why_items" class="form-control" rows="14"
                  style="font-family:monospace;font-size:12px"><?= e($currentWhy) ?></textarea>
        <div class="form-hint">
          JSON array of objects. Each item must have: <code>icon</code> (FA class), <code>color</code> (hex), <code>title</code>, <code>desc</code>.
          <button type="button" class="btn btn-secondary btn-sm" style="margin-left:8px"
            onclick="document.querySelector('[name=homepage_why_items]').value=<?= htmlspecialchars(json_encode($defaultWhy)) ?>">
            Reset to Default
          </button>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Homepage Settings</button>
</form>

<?php /* ══════════════════════════════════════════════
   TAB: SEO & ANALYTICS
══════════════════════════════════════════════ */
elseif ($tab === 'seo'): ?>

<form method="POST">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid-2">
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-tags" style="color:var(--violet)"></i> Default Meta Tags</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Default Meta Description</label>
            <textarea name="meta_description" class="form-control" rows="3" placeholder="Write a compelling description of your business (150–160 characters recommended)..."><?= e($s['meta_description'] ?? '') ?></textarea>
            <div class="form-hint" id="metaDescCount">Used on pages without a specific meta description. Keep it under 160 characters.</div>
          </div>
          <div class="form-group">
            <label>Default Meta Keywords</label>
            <input type="text" name="meta_keywords" class="form-control"
                   value="<?= e($s['meta_keywords'] ?? '') ?>"
                   placeholder="web development, mobile app, digital marketing, New Delhi">
            <div class="form-hint">Comma-separated. (Minor SEO impact, but useful for categorisation.)</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3><i class="fas fa-robot" style="color:var(--gray)"></i> Advanced SEO</h3></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--gray);margin-bottom:16px">
            For per-page SEO (title, description, OG image, canonical URL), use the
            <a href="<?= ADMIN_URL ?>/pages/seo.php" style="color:var(--violet);font-weight:600">SEO Manager <i class="fas fa-arrow-right" style="font-size:11px"></i></a> tool.
          </p>
        </div>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fab fa-google" style="color:#4285f4"></i> Google Analytics</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Measurement ID / Tracking ID</label>
            <div style="position:relative">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray);font-size:13px">G-</span>
              <input type="text" name="google_analytics" class="form-control" style="padding-left:32px"
                     value="<?= e(preg_replace('/^G-/i', '', $s['google_analytics'] ?? '')) ?>"
                     placeholder="XXXXXXXXXX">
            </div>
            <div class="form-hint">Enter just the ID suffix (without G-). Leave blank to disable tracking.</div>
          </div>
          <div style="background:var(--light);border-radius:10px;padding:12px;font-size:12px;color:var(--gray)">
            <i class="fas fa-info-circle" style="color:var(--violet)"></i>
            Current: <strong><?= !empty($s['google_analytics']) ? e($s['google_analytics']) : 'Not configured' ?></strong>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-tag" style="color:var(--amber)"></i> Google Tag Manager</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>GTM Container ID</label>
            <div style="position:relative">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray);font-size:12px">GTM-</span>
              <input type="text" name="google_tag_manager" class="form-control" style="padding-left:46px"
                     value="<?= e(preg_replace('/^GTM-/i', '', $s['google_tag_manager'] ?? '')) ?>"
                     placeholder="XXXXXXX">
            </div>
            <div class="form-hint">Google Tag Manager — add/manage all tags without code changes.</div>
          </div>
        </div>
      </div>

      <!-- Meta (Facebook) Pixel -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fab fa-facebook" style="color:#1877f2"></i> Meta Pixel</h3></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0">
            <label>Pixel / Dataset ID</label>
            <input type="text" name="facebook_pixel" class="form-control"
                   value="<?= e($s['facebook_pixel'] ?? '') ?>"
                   placeholder="1234567890123456">
            <div class="form-hint">From Meta Events Manager → Data Sources. Digits only. Blank falls back to the default pixel — type <code>off</code> to disable tracking.</div>
          </div>
          <div style="background:var(--light);border-radius:10px;padding:12px;font-size:12px;color:var(--gray);margin-top:14px">
            <i class="fas fa-info-circle" style="color:var(--violet)"></i>
            <?php $_fbNow = trim($s['facebook_pixel'] ?? ''); ?>
            <?php if ($_fbNow === 'off'): ?>
              Currently <strong>disabled</strong> — no pixel is loaded.
            <?php elseif ($_fbNow !== ''): ?>
              Active: <strong><?= e($_fbNow) ?></strong>
            <?php else: ?>
              Active: <strong><?= e(AG_DEFAULT_FB_PIXEL) ?></strong> <span style="opacity:.75">(default)</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Webmaster Verifications -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-search" style="color:var(--blue)"></i> Webmaster Verification Codes</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label><i class="fab fa-google" style="color:#4285f4;margin-right:5px"></i>Google Search Console</label>
            <input type="text" name="google_search_console" class="form-control"
                   value="<?= e($s['google_search_console'] ?? '') ?>"
                   placeholder="Verification code from Search Console property">
            <div class="form-hint">From: Search Console → Settings → Ownership verification → HTML tag → content="…"</div>
          </div>
          <div class="form-group">
            <label><i class="fab fa-microsoft" style="color:#0089d6;margin-right:5px"></i>Bing Webmaster Tools</label>
            <input type="text" name="bing_verification" class="form-control"
                   value="<?= e($s['bing_verification'] ?? '') ?>"
                   placeholder="Bing verification code">
            <div class="form-hint">From: Bing Webmaster Tools → Settings → HTML meta tag → content="…"</div>
          </div>
          <div class="form-group">
            <label><i class="fab fa-yandex" style="color:#fc3f1d;margin-right:5px"></i>Yandex Webmaster</label>
            <input type="text" name="yandex_verification" class="form-control"
                   value="<?= e($s['yandex_verification'] ?? '') ?>"
                   placeholder="Yandex verification code">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label><i class="fab fa-facebook" style="color:#1877f2;margin-right:5px"></i>Facebook Domain Verification</label>
            <input type="text" name="facebook_domain" class="form-control"
                   value="<?= e($s['facebook_domain'] ?? '') ?>"
                   placeholder="Facebook domain verification code">
          </div>
        </div>
      </div>

      <!-- IndexNow & OG Image -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-bolt" style="color:var(--emerald)"></i> IndexNow & Open Graph</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>IndexNow API Key <span style="font-weight:400;color:var(--gray)">(Bing/Yandex instant indexing)</span></label>
            <input type="text" name="indexnow_key" class="form-control"
                   value="<?= e($s['indexnow_key'] ?? '') ?>"
                   placeholder="your-indexnow-api-key">
            <div class="form-hint">Generate a key at <a href="https://www.indexnow.org" target="_blank">indexnow.org</a>. Also create a text file at /your-key.txt with the key as content.</div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Default OG Social Share Image URL</label>
            <input type="url" name="og_default_image" class="form-control"
                   value="<?= e($s['og_default_image'] ?? '') ?>"
                   placeholder="https://appsgain.in/uploads/og-default.jpg">
            <div class="form-hint">1200×630px recommended. Used when no page-specific OG image is set.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div style="margin-top:20px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save SEO Settings</button>
    <a href="<?= ADMIN_URL ?>/pages/seo.php" class="btn btn-outline" style="margin-left:8px"><i class="fas fa-search"></i> Advanced SEO Manager</a>
  </div>
</form>

<?php /* ══════════════════════════════════════════════
   TAB: EMAIL / SMTP
══════════════════════════════════════════════ */
elseif ($tab === 'mail'): ?>

<form method="POST">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid-2">
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-server" style="color:var(--violet)"></i> SMTP Server Configuration</h3></div>
      <div class="card-body">
        <div class="info-block">
          <i class="fas fa-lightbulb"></i>
          <p>Appsgain uses SMTP to send contact form replies, lead notifications, and newsletters. Configure your mail server details below.</p>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>SMTP Host</label>
            <input type="text" name="smtp_host" class="form-control" value="<?= e($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
          </div>
          <div class="form-group">
            <label>SMTP Port</label>
            <select name="smtp_port" class="form-control">
              <?php foreach (['587' => '587 (TLS)', '465' => '465 (SSL)', '25' => '25 (Plain)'] as $p => $l): ?>
              <option value="<?= $p ?>" <?= ($s['smtp_port'] ?? '587') == $p ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>SMTP Username</label>
            <input type="text" name="smtp_user" class="form-control" value="<?= e($s['smtp_user'] ?? '') ?>" placeholder="you@gmail.com">
          </div>
          <div class="form-group">
            <label>SMTP Password</label>
            <div style="position:relative">
              <input type="password" name="smtp_pass" id="smtpPass" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
              <button type="button" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer" onclick="toggleSmtp()"><i class="fas fa-eye" id="smtpEye"></i></button>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>From Name</label>
            <input type="text" name="smtp_from_name" class="form-control" value="<?= e($s['smtp_from_name'] ?? 'Appsgain Technologies') ?>" placeholder="Appsgain Technologies">
          </div>
          <div class="form-group">
            <label>Encryption</label>
            <select name="smtp_encryption" class="form-control">
              <option value="tls" <?= ($s['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
              <option value="ssl" <?= ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
              <option value=""   <?= ($s['smtp_encryption'] ?? '') === ''    ? 'selected' : '' ?>>None</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save SMTP Settings</button>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-info-circle" style="color:var(--cyan)"></i> Quick Setup Guides</h3></div>
        <div class="card-body" style="font-size:13px;color:var(--text)">
          <?php
          $guides = [
              ['fab fa-google','#4285f4','Gmail','smtp.gmail.com','587','Use an App Password (Settings → Security → App passwords)'],
              ['fas fa-envelope','#0078d4','Outlook / Office365','smtp.office365.com','587','Use your full email as username.'],
              ['fas fa-cloud','#e76b3b','Hostinger Mail','smtp.hostinger.com','587','Use your cPanel email account credentials.'],
          ];
          foreach ($guides as [$icon, $color, $name, $host, $port, $note]):
          ?>
          <div style="border:1px solid var(--border);border-radius:12px;padding:14px;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
              <span style="width:32px;height:32px;border-radius:8px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px"><i class="<?= $icon ?>"></i></span>
              <strong><?= $name ?></strong>
            </div>
            <div style="font-size:12px;color:var(--gray);line-height:1.8">
              <div>Host: <code style="background:var(--light);padding:2px 6px;border-radius:4px"><?= $host ?></code></div>
              <div>Port: <code style="background:var(--light);padding:2px 6px;border-radius:4px"><?= $port ?></code> / TLS</div>
              <div style="margin-top:4px;color:var(--text)"><?= $note ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<?php /* ══════════════════════════════════════════════
   TAB: MY PROFILE
══════════════════════════════════════════════ */
elseif ($tab === 'profile'): ?>

<div class="settings-grid-2">

  <!-- Profile Info Form -->
  <div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_profile">

      <!-- Avatar area -->
      <div class="profile-avatar-wrap">
        <div class="profile-avatar-img" id="avatarPreview">
          <?php if (!empty($admin['avatar'])): ?>
          <img src="<?= getImageUrl($admin['avatar']) ?>" alt="" id="avatarImg">
          <?php else: ?>
          <span id="avatarInitial"><?= strtoupper(substr($admin['name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div>
          <div class="profile-avatar-name"><?= e($admin['name']) ?></div>
          <div class="profile-avatar-role"><i class="fas fa-shield-alt" style="font-size:10px;color:var(--violet);margin-right:4px"></i><?= ucfirst(e($admin['role'])) ?></div>
        </div>
        <label class="btn btn-secondary btn-sm profile-avatar-upload" style="cursor:pointer">
          <i class="fas fa-camera"></i> Change Photo
          <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)">
        </label>
        <div style="font-size:11px;color:var(--gray2)">JPG, PNG or WebP • Max 2MB</div>
      </div>

      <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-edit" style="color:var(--violet)"></i> Personal Information</h3></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($admin['name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Email Address <span class="required">*</span></label>
              <input type="email" name="email" class="form-control" value="<?= e($admin['email']) ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Designation / Job Title</label>
              <input type="text" name="designation" class="form-control"
                     value="<?= e($admin['designation'] ?? '') ?>" placeholder="e.g. Lead Developer, Marketing Head">
            </div>
            <div class="form-group">
              <label>Phone / Mobile</label>
              <input type="text" name="phone" class="form-control"
                     value="<?= e($admin['phone'] ?? '') ?>" placeholder="+91-XXXXXXXXXX">
            </div>
          </div>
          <div class="form-group">
            <label>Bio / About You</label>
            <textarea name="bio" class="form-control" rows="3"
                      placeholder="Brief description about you, your role, and expertise..."><?= e($admin['bio'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" class="form-control" value="<?= ucfirst(e($admin['role'])) ?>"
                   readonly style="background:var(--light);cursor:not-allowed" title="Role is managed by a Super Admin">
            <div class="form-hint">Contact a Super Admin to change your role.</div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            <a href="<?= ADMIN_URL ?>/logout.php" class="btn btn-secondary"
               onclick="return confirm('Are you sure you want to sign out?')">
              <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Change Password Form -->
  <div>
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><h3><i class="fas fa-lock" style="color:var(--amber)"></i> Change Password</h3></div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="card-body">
          <div class="info-block">
            <i class="fas fa-shield-alt"></i>
            <p>Use a strong password with uppercase, lowercase, numbers, and symbols. Minimum 8 characters.</p>
          </div>
          <div class="form-group">
            <label>Current Password <span class="required">*</span></label>
            <div style="position:relative">
              <input type="password" name="current_password" id="curPw" class="form-control" required placeholder="Enter current password">
              <button type="button" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer" onclick="togglePw('curPw','curEye')"><i class="fas fa-eye" id="curEye"></i></button>
            </div>
          </div>
          <div class="form-group">
            <label>New Password <span class="required">*</span></label>
            <div style="position:relative">
              <input type="password" name="new_password" id="newPw" class="form-control" required minlength="8" placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
              <button type="button" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer" onclick="togglePw('newPw','newEye')"><i class="fas fa-eye" id="newEye"></i></button>
            </div>
            <div class="pw-bar"><div class="pw-bar-fill" id="pwStrBar"></div></div>
            <div style="font-size:11px;margin-top:4px;color:var(--gray)" id="pwStrLabel"></div>
          </div>
          <div class="form-group">
            <label>Confirm New Password <span class="required">*</span></label>
            <div style="position:relative">
              <input type="password" name="confirm_password" id="cfPw" class="form-control" required placeholder="Repeat new password">
              <button type="button" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer" onclick="togglePw('cfPw','cfEye')"><i class="fas fa-eye" id="cfEye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-key"></i> Change Password</button>
        </div>
      </form>
    </div>

    <!-- Account Info Card -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-info-circle" style="color:var(--cyan)"></i> Account Info</h3></div>
      <div class="card-body">
        <?php
        $infoRows = [
            ['fas fa-id-badge',    'User ID',         '#' . $admin['id']],
            ['fas fa-envelope',    'Email',           $admin['email']],
            ['fas fa-user-tag',    'Role',            ucfirst($admin['role'])],
            ['fas fa-sign-in-alt', 'Last Login',      !empty($admin['last_login']) ? formatDate($admin['last_login'], 'd M Y, h:i A') : 'N/A'],
        ];
        foreach ($infoRows as [$icon, $label, $val]):
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
          <i class="<?= $icon ?>" style="width:16px;color:var(--violet);text-align:center"></i>
          <span style="font-size:12.5px;color:var(--gray);min-width:90px"><?= $label ?></span>
          <span style="font-size:13px;font-weight:600;color:var(--primary)"><?= e($val) ?></span>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:16px">
          <a href="<?= ADMIN_URL ?>/logout.php" class="btn btn-danger" style="width:100%"
             onclick="return confirm('Sign out of the admin panel?')">
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

<?php endif; ?>

<script>
/* Password visibility toggles */
function togglePw(id, eyeId) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  document.getElementById(eyeId).className = 'fas fa-eye' + (el.type === 'text' ? '-slash' : '');
}
function toggleSmtp() {
  const el = document.getElementById('smtpPass');
  el.type = el.type === 'password' ? 'text' : 'password';
  document.getElementById('smtpEye').className = 'fas fa-eye' + (el.type === 'text' ? '-slash' : '');
}

/* Password strength meter */
function checkStrength(pw) {
  const bar = document.getElementById('pwStrBar');
  const lbl = document.getElementById('pwStrLabel');
  if (!bar) return;
  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = ['','Weak','Fair','Good','Strong','Very Strong'];
  const colors = ['','#e11d48','#d97706','#2563eb','#059669','#7c3aed'];
  bar.style.width = (score * 20) + '%';
  bar.style.background = colors[score] || '#e11d48';
  lbl.textContent = levels[score] || '';
  lbl.style.color = colors[score];
}

/* Avatar preview */
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const wrap = document.getElementById('avatarPreview');
    wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
  };
  reader.readAsDataURL(input.files[0]);
}

/* GA ID prefix handling — save with G- prefix */
(function() {
  const gaInput = document.querySelector('input[name="google_analytics"]');
  if (gaInput && gaInput.closest('form')) {
    gaInput.closest('form').addEventListener('submit', function() {
      if (gaInput.value && !gaInput.value.startsWith('G-')) {
        gaInput.value = 'G-' + gaInput.value;
      }
    });
  }
  const gtmInput = document.querySelector('input[name="google_tag_manager"]');
  if (gtmInput && gtmInput.closest('form')) {
    gtmInput.closest('form').addEventListener('submit', function() {
      if (gtmInput.value && !gtmInput.value.startsWith('GTM-')) {
        gtmInput.value = 'GTM-' + gtmInput.value;
      }
    });
  }
})();

/* Meta description character counter */
const metaTA = document.querySelector('textarea[name="meta_description"]');
const metaHint = document.getElementById('metaDescCount');
if (metaTA && metaHint) {
  function updateMeta() {
    const len = metaTA.value.length;
    const color = len > 160 ? '#e11d48' : len > 140 ? '#d97706' : '#6b7280';
    metaHint.innerHTML = '<span style="color:' + color + '">' + len + '/160 characters</span> — Keep under 160 for best results.';
  }
  metaTA.addEventListener('input', updateMeta);
  updateMeta();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
