<?php
/**
 * Dynamic Site Content Manager
 * Manage: Menu, Topbar, About page, Section visibility, Footer, etc.
 */
$adminPage  = 'site-content';
$adminTitle = 'Site Content Manager';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$tab = sanitizeInput($_GET['tab'] ?? 'topbar');

/* ── Save handler ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $saveTab = sanitizeInput($_POST['save_tab'] ?? $tab);

    if ($saveTab === 'topbar') {
        $textFields = ['site_phone','site_email','site_address','site_whatsapp',
                       'site_facebook','site_instagram','site_linkedin','site_twitter','site_youtube',
                       'topbar_availability'];
        foreach ($textFields as $f) {
            saveSetting($f, sanitizeInput($_POST[$f] ?? ''), 'general');
        }
        /* Checkbox: save 1 if checked, 0 if unchecked */
        saveSetting('topbar_show', isset($_POST['topbar_show']) ? '1' : '0', 'general');
        setFlash('success', 'Topbar settings saved!');
    }

    if ($saveTab === 'menu') {
        $menuJson = $_POST['menu_json'] ?? '[]';
        $decoded  = json_decode($menuJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            setFlash('error', 'Invalid menu data. Please try again.');
        } else {
            saveSetting('nav_menu', $menuJson, 'general');
            setFlash('success', 'Navigation menu saved!');
        }
    }

    if ($saveTab === 'about') {
        /* Plain-text fields */
        $textFields = ['about_founded_year','about_tagline','about_story_title','about_hero_title','about_hero_subtitle'];
        foreach ($textFields as $f) {
            saveSetting($f, sanitizeInput($_POST[$f] ?? ''), 'about');
        }
        /* Rich-text fields (preserve HTML/newlines) */
        $richFields = ['about_mission','about_vision','about_values','about_story_text'];
        foreach ($richFields as $f) {
            saveSetting($f, trim($_POST[$f] ?? ''), 'about');
        }
        /* Journey JSON */
        $journeyJson = $_POST['about_journey'] ?? '[]';
        if (json_decode($journeyJson) !== null) {
            saveSetting('about_journey', $journeyJson, 'about');
        }
        /* Hero image upload */
        if (!empty($_FILES['about_hero_image']['name'])) {
            $up = uploadFile($_FILES['about_hero_image'], 'about');
            if ($up['success']) {
                saveSetting('about_hero_image', $up['path'], 'about');
            } else {
                setFlash('error', 'Image upload failed: ' . ($up['error'] ?? 'Unknown error'));
                redirect(ADMIN_URL . '/pages/site-content.php?tab=about');
            }
        }
        setFlash('success', 'About page content saved!');
    }

    if ($saveTab === 'sections') {
        $sectionsJson = $_POST['sections_json'] ?? '[]';
        if (json_decode($sectionsJson) !== null) {
            saveSetting('site_sections', $sectionsJson, 'general');
        }
        setFlash('success', 'Section order saved!');
    }

    if ($saveTab === 'footer') {
        $textFields = ['footer_tagline','footer_copyright','app_google_play','app_apple_store'];
        foreach ($textFields as $f) {
            saveSetting($f, sanitizeInput($_POST[$f] ?? ''), 'general');
        }
        /* Checkboxes */
        saveSetting('footer_show_apps',       isset($_POST['footer_show_apps'])       ? '1' : '0', 'general');
        saveSetting('footer_show_newsletter', isset($_POST['footer_show_newsletter']) ? '1' : '0', 'general');
        setFlash('success', 'Footer settings saved!');
    }

    redirect(ADMIN_URL . '/pages/site-content.php?tab=' . $saveTab);
}

/* ── Load all settings ── */
$s = getAllSettings();

/* Defaults */
$def = [
    'topbar_availability' => 'Available for Projects',
    'topbar_show'         => '1',
    'about_founded_year'  => '2018',
    'about_tagline'       => 'Building the Future of Digital Innovation',
    'about_story_title'   => 'Turning Bold Ideas Into Powerful Digital Products',
    'about_mission'       => 'To help businesses accelerate growth through innovative, scalable, secure, and technology-driven software solutions while delivering measurable business outcomes.',
    'about_vision'        => "To be India's most trusted software development and digital transformation partner.",
    'about_values'        => 'Engineering excellence, radical transparency, client-first mindset, continuous innovation, on-time delivery.',
    'about_journey'       => json_encode([
        ['year'=>'2018','title'=>'Founded','text'=>'Appsgain Technologies was incorporated in Bangalore.'],
        ['year'=>'2019','title'=>'Mobile First','text'=>'Expanded into Flutter and native Android/iOS development.'],
        ['year'=>'2021','title'=>'AI & SaaS','text'=>'Launched dedicated AI & Data Science practice.'],
        ['year'=>'2022','title'=>'International','text'=>'Expanded to UAE, UK, and USA clients.'],
        ['year'=>'2024','title'=>'500+ Projects','text'=>'Surpassed 500+ delivered projects and 300+ clients.'],
    ]),
    'sections_json' => json_encode([
        ['id'=>'hero',          'label'=>'Hero Banner',       'visible'=>true,  'order'=>1],
        ['id'=>'stats',         'label'=>'Stats Bar',         'visible'=>true,  'order'=>2],
        ['id'=>'services',      'label'=>'Services Grid',     'visible'=>true,  'order'=>3],
        ['id'=>'why',           'label'=>'Why Choose Us',     'visible'=>true,  'order'=>4],
        ['id'=>'process',       'label'=>'Our Process',       'visible'=>true,  'order'=>5],
        ['id'=>'portfolio',     'label'=>'Portfolio/Projects','visible'=>true,  'order'=>6],
        ['id'=>'testimonials',  'label'=>'Testimonials',      'visible'=>true,  'order'=>7],
        ['id'=>'tech',          'label'=>'Tech Stack',        'visible'=>true,  'order'=>8],
        ['id'=>'blog',          'label'=>'Blog Section',      'visible'=>true,  'order'=>9],
        ['id'=>'cta',           'label'=>'CTA Banner',        'visible'=>true,  'order'=>10],
    ]),
    'nav_menu' => json_encode([
        ['label'=>'Home',      'url'=>'/',             'active'=>true,  'children'=>[]],
        ['label'=>'About',     'url'=>'/about.php',    'active'=>true,  'children'=>[]],
        ['label'=>'Services',  'url'=>'/services.php', 'active'=>true,  'children'=>[]],
        ['label'=>'Blog',      'url'=>'/blog.php',     'active'=>true,  'children'=>[]],
        ['label'=>'Contact',   'url'=>'/contact.php',  'active'=>true,  'children'=>[]],
    ]),
    'footer_tagline'  => 'Building the Future of Digital Innovation',
    'footer_copyright'=> '© 2024 Appsgain Technologies. All rights reserved.',
];
foreach ($def as $k => $v) { if (!isset($s[$k]) || $s[$k] === '') $s[$k] = $v; }

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.sc-tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:24px; overflow-x:auto; flex-wrap:nowrap; scrollbar-width:none; }
.sc-tab  { padding:10px 18px; font-size:13px; font-weight:700; color:var(--gray); border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; white-space:nowrap; font-family:inherit; transition:var(--transition); display:flex; align-items:center; gap:6px; }
.sc-tab:hover { color:var(--violet); }
.sc-tab.active { color:var(--violet); border-bottom-color:var(--violet); }
.sc-panel { display:none; }
.sc-panel.active { display:block; }
.section-item { display:flex; align-items:center; gap:14px; padding:12px 16px; background:var(--white); border:1px solid var(--border); border-radius:10px; margin-bottom:8px; cursor:grab; }
.section-item:active { cursor:grabbing; box-shadow:var(--shadow-md); }
.section-drag { color:var(--gray2); font-size:15px; cursor:grab; }
.journey-item { background:var(--white); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:10px; }
.journey-item .ji-row { display:grid; grid-template-columns:80px 1fr 1fr auto; gap:10px; align-items:start; }
.menu-item { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:12px 14px; margin-bottom:8px; }
.menu-item .mi-row { display:grid; grid-template-columns:1fr 1fr auto auto; gap:10px; align-items:center; }
</style>

<div class="page-header">
  <div>
    <h1><i class="fas fa-edit" style="color:var(--violet);"></i> Site Content Manager</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px;">Manage all dynamic content across your website</p>
  </div>
  <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> View Website</a>
</div>

<?php renderFlash(); ?>

<!-- Tabs -->
<div class="sc-tabs">
  <?php
  $tabs = [
    'topbar'   => ['fa-sliders-h',   'Topbar & Contact'],
    'menu'     => ['fa-bars',        'Navigation Menu'],
    'about'    => ['fa-building',    'About Page'],
    'sections' => ['fa-layer-group', 'Section Visibility'],
    'footer'   => ['fa-shoe-prints', 'Footer Settings'],
  ];
  foreach ($tabs as $tid => [$ico, $lbl]):
  ?>
  <button class="sc-tab <?= $tab === $tid ? 'active' : '' ?>" onclick="showTab('<?= $tid ?>')">
    <i class="fas <?= $ico ?>"></i> <?= $lbl ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- ════════════════ TAB: TOPBAR ════════════════ -->
<div class="sc-panel <?= $tab==='topbar'?'active':'' ?>" id="panel-topbar">
  <form method="POST" autocomplete="off">
    <?= csrfField() ?>
    <input type="hidden" name="save_tab" value="topbar">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-address-book" style="color:var(--blue);"></i> Contact Details</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Phone Number</label><input type="text" name="site_phone" class="form-control" value="<?= e($s['site_phone']??$s['phone']??'') ?>" placeholder="+91-9955446477"></div>
          <div class="form-group"><label>Email Address</label><input type="email" name="site_email" class="form-control" value="<?= e($s['site_email']??$s['email']??'') ?>" placeholder="info@appsgain.in"></div>
          <div class="form-group"><label>Office Address</label><textarea name="site_address" class="form-control" rows="2"><?= e($s['site_address']??$s['address']??'') ?></textarea></div>
          <div class="form-group" style="margin-bottom:0;"><label>WhatsApp Number</label><input type="text" name="site_whatsapp" class="form-control" value="<?= e($s['site_whatsapp']??$s['whatsapp']??'') ?>" placeholder="919955446477"></div>
        </div>
      </div>
      <div>
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header"><h3><i class="fas fa-share-alt" style="color:var(--violet);"></i> Social Media</h3></div>
          <div class="card-body">
            <?php
            $socials = ['site_facebook'=>['fab fa-facebook-f','Facebook URL'],
                        'site_instagram'=>['fab fa-instagram','Instagram URL'],
                        'site_linkedin'=>['fab fa-linkedin-in','LinkedIn URL'],
                        'site_twitter'=>['fab fa-x-twitter','Twitter/X URL'],
                        'site_youtube'=>['fab fa-youtube','YouTube URL']];
            foreach ($socials as $k => [$ico,$lbl]):
            ?>
            <div class="form-group" style="margin-bottom:12px;">
              <label style="display:flex;align-items:center;gap:6px;"><i class="<?= $ico ?>" style="width:16px;text-align:center;color:var(--violet);font-size:13px;"></i><?= $lbl ?></label>
              <input type="url" name="<?= $k ?>" class="form-control" value="<?= e($s[$k] ?? '') ?>" placeholder="https://…">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3><i class="fas fa-eye"></i> Topbar Options</h3></div>
          <div class="card-body">
            <div class="form-group"><label>Availability Message</label><input type="text" name="topbar_availability" class="form-control" value="<?= e($s['topbar_availability']) ?>" placeholder="Available for Projects"></div>
            <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;"><label style="margin:0;">Show Topbar</label><label class="toggle-switch"><input type="checkbox" name="topbar_show" value="1" <?= ($s['topbar_show']??'1') ? 'checked':'' ?>><span class="toggle-slider"></span></label></div>
          </div>
        </div>
      </div>
    </div>
    <div style="margin-top:16px;"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Topbar Settings</button></div>
  </form>
</div>

<!-- ════════════════ TAB: NAVIGATION MENU ════════════════ -->
<div class="sc-panel <?= $tab==='menu'?'active':'' ?>" id="panel-menu">
  <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;">
    <div>
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-bars" style="color:var(--violet);"></i> Navigation Menu Items</h3>
          <button type="button" class="btn btn-primary btn-sm" onclick="addMenuItem()"><i class="fas fa-plus"></i> Add Item</button>
        </div>
        <div class="card-body">
          <p style="font-size:12.5px;color:var(--gray);margin-bottom:14px;">Drag to reorder. Changes are saved when you click Save.</p>
          <div id="menuItems"></div>
          <form method="POST" id="menuForm">
            <?= csrfField() ?>
            <input type="hidden" name="save_tab" value="menu">
            <textarea name="menu_json" id="menuJson" style="display:none;"></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;width:100%;"><i class="fas fa-save"></i> Save Navigation Menu</button>
          </form>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-info-circle"></i> Quick Reference</h3></div>
      <div class="card-body" style="font-size:13px;color:var(--gray);">
        <p style="margin-bottom:12px;">Common internal URLs:</p>
        <?php
        $quickUrls = ['/' => 'Home', '/about.php' => 'About', '/services.php' => 'Services',
                      '/portfolio.php' => 'Portfolio', '/blog.php' => 'Blog', '/careers.php' => 'Careers',
                      '/contact.php' => 'Contact', '/products.php' => 'Products', '/apps.php' => 'Mobile Apps',
                      '/partners.php' => 'Partners', '/clients.php' => 'Clients', '/gallery.php' => 'Gallery',
                      '/faq.php' => 'FAQs', '/testimonials.php' => 'Testimonials'];
        ?>
        <div style="display:flex;flex-direction:column;gap:4px;">
          <?php foreach ($quickUrls as $url => $lbl): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--border);">
            <span style="font-weight:600;color:var(--primary);"><?= $lbl ?></span>
            <code style="font-size:11px;background:var(--light);padding:2px 7px;border-radius:5px;cursor:pointer;" onclick="this.select()" title="Click to copy"><?= $url ?></code>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════ TAB: ABOUT PAGE ════════════════ -->
<div class="sc-panel <?= $tab==='about'?'active':'' ?>" id="panel-about">
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <input type="hidden" name="save_tab" value="about">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

      <!-- Left -->
      <div>
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header"><h3><i class="fas fa-star" style="color:var(--amber);"></i> Hero Section</h3></div>
          <div class="card-body">
            <div class="form-group"><label>Hero Title</label><input type="text" name="about_hero_title" class="form-control" value="<?= e($s['about_hero_title']??'Building the Future of Digital Innovation') ?>"></div>
            <div class="form-group"><label>Hero Subtitle</label><textarea name="about_hero_subtitle" class="form-control" rows="2"><?= e($s['about_hero_subtitle']??'') ?></textarea></div>
            <div class="form-group" style="margin-bottom:0;"><label>Hero Background Image</label>
              <?php if (!empty($s['about_hero_image'])): ?>
              <img src="<?= UPLOADS_URL.'/'.$s['about_hero_image'] ?>" style="width:100%;height:100px;object-fit:cover;border-radius:10px;margin-bottom:8px;">
              <?php endif; ?>
              <input type="file" name="about_hero_image" class="form-control" accept="image/*">
            </div>
          </div>
        </div>
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header"><h3><i class="fas fa-history" style="color:var(--blue);"></i> Our Story</h3></div>
          <div class="card-body">
            <div class="form-group"><label>Founded Year</label><input type="text" name="about_founded_year" class="form-control" value="<?= e($s['about_founded_year']) ?>" placeholder="2018"></div>
            <div class="form-group"><label>Company Tagline</label><input type="text" name="about_tagline" class="form-control" value="<?= e($s['about_tagline']) ?>"></div>
            <div class="form-group"><label>Story Section Title</label><input type="text" name="about_story_title" class="form-control" value="<?= e($s['about_story_title']) ?>"></div>
            <div class="form-group" style="margin-bottom:0;"><label>Story Text</label><textarea name="about_story_text" class="form-control" rows="5"><?= e($s['about_story_text']??'') ?></textarea></div>
          </div>
        </div>
      </div>

      <!-- Right -->
      <div>
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header"><h3><i class="fas fa-compass" style="color:var(--violet);"></i> Mission, Vision &amp; Values</h3></div>
          <div class="card-body">
            <div class="form-group"><label>Our Mission</label><textarea name="about_mission" class="form-control" rows="4"><?= e($s['about_mission']) ?></textarea></div>
            <div class="form-group"><label>Our Vision</label><textarea name="about_vision" class="form-control" rows="4"><?= e($s['about_vision']) ?></textarea></div>
            <div class="form-group" style="margin-bottom:0;"><label>Core Values</label><textarea name="about_values" class="form-control" rows="4"><?= e($s['about_values']) ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Journey Milestones -->
    <div class="card" style="margin-top:4px;">
      <div class="card-header">
        <h3><i class="fas fa-map-signs" style="color:var(--emerald);"></i> Journey / Timeline Milestones</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="addJourneyItem()"><i class="fas fa-plus"></i> Add Milestone</button>
      </div>
      <div class="card-body">
        <div id="journeyItems"></div>
        <input type="hidden" name="about_journey" id="journeyJson" value="<?= e($s['about_journey']) ?>">
      </div>
    </div>

    <div style="margin-top:16px;"><button type="submit" class="btn btn-primary" style="padding:12px 28px;"><i class="fas fa-save"></i> Save About Page Content</button></div>
  </form>
</div>

<!-- ════════════════ TAB: SECTIONS ════════════════ -->
<div class="sc-panel <?= $tab==='sections'?'active':'' ?>" id="panel-sections">
  <form method="POST" id="sectionsForm">
    <?= csrfField() ?>
    <input type="hidden" name="save_tab" value="sections">
    <textarea name="sections_json" id="sectionsJson" style="display:none;"></textarea>
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-sort" style="color:var(--violet);"></i> Homepage Section Visibility &amp; Order</h3>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Order</button>
      </div>
      <div class="card-body">
        <p style="font-size:13px;color:var(--gray);margin-bottom:16px;">Drag sections to reorder. Toggle visibility. Changes apply to the homepage.</p>
        <div id="sectionsList"></div>
      </div>
    </div>
  </form>
</div>

<!-- ════════════════ TAB: FOOTER ════════════════ -->
<div class="sc-panel <?= $tab==='footer'?'active':'' ?>" id="panel-footer">
  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="save_tab" value="footer">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-shoe-prints" style="color:var(--violet);"></i> Footer Text</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Footer Tagline</label><input type="text" name="footer_tagline" class="form-control" value="<?= e($s['footer_tagline']) ?>" placeholder="Building the Future of Digital Innovation"></div>
          <div class="form-group"><label>Copyright Text</label><input type="text" name="footer_copyright" class="form-control" value="<?= e($s['footer_copyright']) ?>" placeholder="© 2024 Appsgain Technologies"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-mobile-alt" style="color:var(--blue);"></i> App Store Links</h3></div>
        <div class="card-body">
          <div class="form-group"><label>Google Play URL</label><input type="url" name="app_google_play" class="form-control" value="<?= e($s['app_google_play']??'') ?>" placeholder="https://play.google.com/store/apps/…"></div>
          <div class="form-group"><label>Apple App Store URL</label><input type="url" name="app_apple_store" class="form-control" value="<?= e($s['app_apple_store']??'') ?>" placeholder="https://apps.apple.com/…"></div>
          <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;"><label style="margin:0;">Show Download Our Apps section</label><label class="toggle-switch"><input type="checkbox" name="footer_show_apps" value="1" <?= !empty($s['footer_show_apps']) ? 'checked':'' ?>><span class="toggle-slider"></span></label></div>
        </div>
      </div>
    </div>
    <div style="margin-top:16px;"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Footer Settings</button></div>
  </form>
</div>

<script>
var currentTab = '<?= $tab ?>';
var siteUrl = '<?= SITE_URL ?>';

/* ── Tab switching ── */
function showTab(id) {
  document.querySelectorAll('.sc-panel').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.sc-tab').forEach(function(b){ b.classList.remove('active'); });
  document.getElementById('panel-'+id).classList.add('active');
  document.querySelector('[onclick*="'+id+'"]').classList.add('active');
  history.pushState(null,'','?tab='+id);
  currentTab = id;
  if (id === 'menu')     initMenuEditor();
  if (id === 'about')    initJourneyEditor();
  if (id === 'sections') initSectionsEditor();
}

/* ════ MENU EDITOR ════ */
var menuData = <?= $s['nav_menu'] ?>;

function initMenuEditor() {
  renderMenuItems();
}

function renderMenuItems() {
  var container = document.getElementById('menuItems');
  if (!container) return;
  container.innerHTML = '';
  menuData.forEach(function(item, i) {
    var div = document.createElement('div');
    div.className = 'menu-item';
    div.innerHTML =
      '<div class="mi-row">' +
        '<div><label style="font-size:11px;color:var(--gray);margin-bottom:4px;display:block;">Label</label>' +
          '<input type="text" class="form-control" value="'+escHtml(item.label)+'" onchange="updateMenuItem('+i+',\'label\',this.value)" placeholder="Menu Label"></div>' +
        '<div><label style="font-size:11px;color:var(--gray);margin-bottom:4px;display:block;">URL</label>' +
          '<input type="text" class="form-control" value="'+escHtml(item.url)+'" onchange="updateMenuItem('+i+',\'url\',this.value)" placeholder="/page.php"></div>' +
        '<div style="padding-top:18px;"><label class="toggle-switch"><input type="checkbox" '+(item.active?'checked':'')+' onchange="updateMenuItem('+i+',\'active\',this.checked)"><span class="toggle-slider"></span></label></div>' +
        '<div style="padding-top:18px;"><button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeMenuItem('+i+')" title="Remove"><i class="fas fa-trash"></i></button></div>' +
      '</div>';
    container.appendChild(div);
  });
  syncMenuJson();
}

function updateMenuItem(i, key, val) { menuData[i][key] = val; syncMenuJson(); }
function removeMenuItem(i) { menuData.splice(i,1); renderMenuItems(); }
function addMenuItem() {
  menuData.push({label:'New Item', url:'/', active:true, children:[]});
  renderMenuItems();
}
function syncMenuJson() {
  var t = document.getElementById('menuJson');
  if (t) t.value = JSON.stringify(menuData);
}

/* ════ JOURNEY EDITOR ════ */
var journeyData = <?= $s['about_journey'] ?>;

function initJourneyEditor() {
  renderJourneyItems();
}

function renderJourneyItems() {
  var container = document.getElementById('journeyItems');
  if (!container) return;
  container.innerHTML = '';
  journeyData.forEach(function(item, i) {
    var div = document.createElement('div');
    div.className = 'journey-item';
    div.innerHTML =
      '<div class="ji-row">' +
        '<div><label style="font-size:11px;color:var(--gray);">Year</label>' +
          '<input type="text" class="form-control" value="'+escHtml(item.year||'')+'" onchange="updateJourney('+i+',\'year\',this.value)" placeholder="2018" style="margin-top:4px;"></div>' +
        '<div><label style="font-size:11px;color:var(--gray);">Milestone Title</label>' +
          '<input type="text" class="form-control" value="'+escHtml(item.title||'')+'" onchange="updateJourney('+i+',\'title\',this.value)" placeholder="Founded" style="margin-top:4px;"></div>' +
        '<div><label style="font-size:11px;color:var(--gray);">Description</label>' +
          '<input type="text" class="form-control" value="'+escHtml(item.text||'')+'" onchange="updateJourney('+i+',\'text\',this.value)" placeholder="What happened…" style="margin-top:4px;"></div>' +
        '<div style="padding-top:18px;"><button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeJourney('+i+')" title="Remove"><i class="fas fa-trash"></i></button></div>' +
      '</div>';
    container.appendChild(div);
  });
  syncJourneyJson();
}

function updateJourney(i, key, val) { journeyData[i][key] = val; syncJourneyJson(); }
function removeJourney(i) { journeyData.splice(i,1); renderJourneyItems(); }
function addJourneyItem() {
  journeyData.push({year: new Date().getFullYear()+'', title:'New Milestone', text:''});
  renderJourneyItems();
}
function syncJourneyJson() {
  var t = document.getElementById('journeyJson');
  if (t) t.value = JSON.stringify(journeyData);
}

/* ════ SECTIONS EDITOR ════ */
var sectionsData = <?= $s['sections_json'] ?>;

function initSectionsEditor() {
  renderSections();
}

function renderSections() {
  var container = document.getElementById('sectionsList');
  if (!container) return;
  /* Sort by order */
  sectionsData.sort(function(a,b){ return (a.order||0)-(b.order||0); });
  container.innerHTML = '';
  sectionsData.forEach(function(sec, i) {
    var div = document.createElement('div');
    div.className = 'section-item';
    div.setAttribute('draggable','true');
    div.dataset.idx = i;
    div.innerHTML =
      '<span class="section-drag"><i class="fas fa-grip-vertical"></i></span>' +
      '<span style="flex:1;font-weight:700;font-size:13.5px;color:var(--primary);">'+ escHtml(sec.label||sec.id) +'</span>' +
      '<span style="font-size:12px;color:var(--gray);font-family:monospace;background:var(--light);padding:2px 8px;border-radius:6px;">#'+escHtml(sec.id)+'</span>' +
      '<label class="toggle-switch" style="flex-shrink:0;">' +
        '<input type="checkbox" '+(sec.visible!==false?'checked':'')+' onchange="updateSection('+i+',\'visible\',this.checked)">' +
        '<span class="toggle-slider"></span>' +
      '</label>';
    container.appendChild(div);
    /* Drag events */
    div.addEventListener('dragstart', function(e){ e.dataTransfer.setData('text',i); this.style.opacity='.4'; });
    div.addEventListener('dragend',   function()  { this.style.opacity=''; });
    div.addEventListener('dragover',  function(e) { e.preventDefault(); this.style.borderColor='var(--violet)'; });
    div.addEventListener('dragleave', function()  { this.style.borderColor=''; });
    div.addEventListener('drop',      function(e) {
      e.preventDefault(); this.style.borderColor='';
      var from = parseInt(e.dataTransfer.getData('text'));
      var to   = parseInt(this.dataset.idx);
      if (from !== to) {
        var moved = sectionsData.splice(from,1)[0];
        sectionsData.splice(to,0,moved);
        sectionsData.forEach(function(s,i){ s.order = i+1; });
        renderSections();
      }
    });
  });
  syncSectionsJson();
}

function updateSection(i, key, val) { sectionsData[i][key] = val; syncSectionsJson(); }
function syncSectionsJson() {
  var t = document.getElementById('sectionsJson');
  if (t) t.value = JSON.stringify(sectionsData);
}

/* Helper */
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* Auto-init current tab */
document.addEventListener('DOMContentLoaded', function() {
  if (currentTab === 'menu')     initMenuEditor();
  if (currentTab === 'about')    initJourneyEditor();
  if (currentTab === 'sections') initSectionsEditor();
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
