<?php
/**
 * Services Page content manager.
 *
 * Everything on /services.php that is not a service record itself: hero,
 * section headings, the why/process/stack/finder lists, the closing CTA and
 * the hero image. Scalars go to `settings`; the repeatable lists go in as one
 * JSON blob each, which is how the header menu is already stored.
 *
 * The service cards themselves stay under Admin -> Services.
 */
$adminTitle = 'Services Page';
$adminPage  = 'services-page';
require_once dirname(__DIR__) . '/includes/admin-layout.php';
require_once ROOT_PATH . '/includes/services-page-content.php';

$tab = sanitizeInput($_GET['tab'] ?? 'hero');

/* Which scalar keys each tab owns, so a save only touches its own fields. */
const SP_TAB_KEYS = [
    'hero'     => ['sp_hero_eyebrow','sp_hero_title','sp_hero_title2','sp_hero_accent','sp_hero_lead',
                   'sp_hero_btn1_text','sp_hero_btn1_url','sp_hero_btn2_text','sp_hero_btn2_url'],
    'sections' => ['sp_overview_eyebrow','sp_overview_title','sp_overview_accent','sp_overview_sub',
                   'sp_why_eyebrow','sp_why_title','sp_why_accent','sp_why_sub',
                   'sp_stack_eyebrow','sp_stack_title','sp_stack_accent','sp_stack_sub',
                   'sp_process_eyebrow','sp_process_title','sp_process_accent','sp_process_sub',
                   'sp_finder_eyebrow','sp_finder_title','sp_finder_accent','sp_finder_sub'],
    'cta'      => ['sp_cta_title','sp_cta_accent','sp_cta_sub',
                   'sp_cta_btn1_text','sp_cta_btn1_url','sp_cta_btn2_text','sp_cta_btn2_url'],
];

/* Shape of each repeatable list: the columns a row may carry. */
const SP_LIST_SHAPES = [
    'sp_why_points'    => ['title','text'],
    'sp_process_steps' => ['title','icon','text'],
    'sp_finder'        => ['label','slug'],
    'sp_group_copy'    => ['group','lead','accent','sub'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    /* ── Scalars ── */
    if (isset(SP_TAB_KEYS[$action])) {
        foreach (SP_TAB_KEYS[$action] as $k) {
            saveSetting($k, sanitizeInput($_POST[$k] ?? ''), 'services_page');
        }
        logActivity('update', 'content', "Updated services page: {$action}");
        setFlash('success', 'Saved.');
        redirect(ADMIN_URL . '/pages/services-page.php?tab=' . $action);
    }

    /* ── Hero image ── */
    if ($action === 'hero_image') {
        if (!empty($_FILES['sp_hero_image']['name'])) {
            $up = uploadFile($_FILES['sp_hero_image'], 'services');
            if (!$up['success']) {
                setFlash('error', $up['error']);
                redirect(ADMIN_URL . '/pages/services-page.php?tab=hero');
            }
            saveSetting('sp_hero_image', $up['path'], 'services_page');
            setFlash('success', 'Hero image updated.');
        } elseif (!empty($_POST['remove_image'])) {
            saveSetting('sp_hero_image', '', 'services_page');
            setFlash('success', 'Hero image removed — the built-in illustration is back.');
        }
        logActivity('update', 'content', 'Updated services page hero image');
        redirect(ADMIN_URL . '/pages/services-page.php?tab=hero');
    }

    /* ── Repeatable lists ──
       Rows arrive as parallel arrays. A row is dropped when its first column
       is blank, which is how a row gets deleted without a separate action. */
    if ($action === 'save_list') {
        $key = sanitizeInput($_POST['list_key'] ?? '');
        if (!isset(SP_LIST_SHAPES[$key])) {
            setFlash('error', 'Unknown list.');
            redirect(ADMIN_URL . '/pages/services-page.php?tab=lists');
        }
        $cols  = SP_LIST_SHAPES[$key];
        $first = $cols[0];
        $rows  = [];
        foreach ((array)($_POST[$first] ?? []) as $i => $_) {
            $row = [];
            foreach ($cols as $c) $row[$c] = sanitizeInput((string)($_POST[$c][$i] ?? ''));
            if ($row[$first] === '') continue;
            $rows[] = $row;
        }
        /* Never store a false from bad UTF-8 — that would blank the section. */
        $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            setFlash('error', 'Could not save — some text had characters we could not encode.');
            redirect(ADMIN_URL . '/pages/services-page.php?tab=lists&list=' . urlencode($key));
        }
        saveSetting($key, $json, 'services_page');
        logActivity('update', 'content', "Updated services page list: {$key} (" . count($rows) . ' rows)');
        setFlash('success', count($rows) . ' item' . (count($rows) === 1 ? '' : 's') . ' saved.');
        redirect(ADMIN_URL . '/pages/services-page.php?tab=lists&list=' . urlencode($key));
    }

    /* ── Restore a list to what shipped ── */
    if ($action === 'reset_list') {
        $key = sanitizeInput($_POST['list_key'] ?? '');
        if (isset(SP_LIST_SHAPES[$key])) {
            saveSetting($key, '', 'services_page');
            logActivity('update', 'content', "Reset services page list: {$key}");
            setFlash('success', 'Restored to the original content.');
        }
        redirect(ADMIN_URL . '/pages/services-page.php?tab=lists&list=' . urlencode($key));
    }
}

$D        = servicesPageDefaults();
$heroImg  = trim((string)getSetting('sp_hero_image', ''));
$heroImgU = $heroImg !== '' ? (str_starts_with($heroImg, 'http') ? $heroImg : UPLOADS_URL . '/' . ltrim($heroImg, '/')) : '';

/* Current value for a field: saved, else the shipped default. */
$v = static fn(string $k): string => (string)(trim((string)getSetting($k, '')) !== ''
        ? getSetting($k, '')
        : (servicesPageDefaults()[$k] ?? ''));

$listMeta = [
    'sp_why_points'    => ['Why Choose Us',     'fa-award',        'The numbered list under "Why Businesses Choose Appsgain".'],
    'sp_process_steps' => ['Process Steps',     'fa-diagram-next', 'The delivery process timeline. Icons are Font Awesome names, e.g. fa-rocket.'],
    'sp_finder'        => ['Finder Options',    'fa-compass',      'The "what are you looking to build" chips. Each maps to a service slug.'],
    'sp_group_copy'    => ['Category Intros',   'fa-layer-group',  'The heading above each service category block. Group must match the category on the service.'],
];
$listKey = sanitizeInput($_GET['list'] ?? 'sp_why_points');
if (!isset(SP_LIST_SHAPES[$listKey])) $listKey = 'sp_why_points';

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span>Services Page</span>
    </div>
    <h1 class="page-title">Services Page</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px">
      Everything on the services page except the service cards themselves &mdash;
      those live under <a href="<?= ADMIN_URL ?>/pages/services.php" style="color:var(--violet);font-weight:600">Services</a>.
    </p>
  </div>
  <div class="page-header-actions">
    <a href="<?= SITE_URL ?>/services.php" target="_blank" class="btn btn-secondary btn-sm">
      <i class="fas fa-external-link-alt"></i> View page
    </a>
  </div>
</div>

<div class="status-tabs" style="margin-bottom:18px">
  <?php foreach (['hero'=>['fa-star','Hero'],'sections'=>['fa-heading','Section Headings'],
                  'lists'=>['fa-list-ul','Lists & Content'],'cta'=>['fa-bullhorn','Closing CTA']] as $k => [$ico,$lbl]): ?>
  <a href="?tab=<?= $k ?>" class="status-tab <?= $tab === $k ? 'active' : '' ?>">
    <i class="fas <?= $ico ?>"></i> <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php /* ══ HERO ══ */ if ($tab === 'hero'): ?>
<div class="sp-cols">
  <form method="POST" class="card">
    <?= csrfField() ?><input type="hidden" name="action" value="hero">
    <div class="card-header"><h3><i class="fas fa-star" style="color:var(--amber);margin-right:8px"></i>Hero Copy</h3></div>
    <div class="card-body">
      <div class="form-group">
        <label>Eyebrow</label>
        <input type="text" name="sp_hero_eyebrow" class="form-control" value="<?= e($v('sp_hero_eyebrow')) ?>">
      </div>
      <div class="sp-2">
        <div class="form-group">
          <label>Headline &mdash; line 1</label>
          <input type="text" name="sp_hero_title" class="form-control" value="<?= e($v('sp_hero_title')) ?>">
        </div>
        <div class="form-group">
          <label>Headline &mdash; line 2</label>
          <input type="text" name="sp_hero_title2" class="form-control" value="<?= e($v('sp_hero_title2')) ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Gradient words</label>
        <input type="text" name="sp_hero_accent" class="form-control" value="<?= e($v('sp_hero_accent')) ?>">
        <div class="form-hint">Shown in the brand gradient at the end of line 2.</div>
      </div>
      <div class="form-group">
        <label>Supporting line</label>
        <textarea name="sp_hero_lead" class="form-control" rows="3"><?= e((string)getSetting('sp_hero_lead', '')) ?></textarea>
        <div class="form-hint">Leave blank to reuse the page meta description.</div>
      </div>
      <div class="sp-2">
        <div class="form-group">
          <label>Button 1 label</label>
          <input type="text" name="sp_hero_btn1_text" class="form-control" value="<?= e($v('sp_hero_btn1_text')) ?>">
        </div>
        <div class="form-group">
          <label>Button 1 link</label>
          <input type="text" name="sp_hero_btn1_url" class="form-control" value="<?= e($v('sp_hero_btn1_url')) ?>">
        </div>
      </div>
      <div class="sp-2">
        <div class="form-group">
          <label>Button 2 label</label>
          <input type="text" name="sp_hero_btn2_text" class="form-control" value="<?= e($v('sp_hero_btn2_text')) ?>">
          <div class="form-hint">Blank hides the button.</div>
        </div>
        <div class="form-group">
          <label>Button 2 link</label>
          <input type="text" name="sp_hero_btn2_url" class="form-control" value="<?= e($v('sp_hero_btn2_url')) ?>">
        </div>
      </div>
      <button class="btn btn-primary"><i class="fas fa-check"></i> Save hero</button>
    </div>
  </form>

  <form method="POST" enctype="multipart/form-data" class="card" style="align-self:start">
    <?= csrfField() ?><input type="hidden" name="action" value="hero_image">
    <div class="card-header"><h3><i class="fas fa-image" style="color:var(--blue);margin-right:8px"></i>Hero Image</h3></div>
    <div class="card-body">
      <div class="sp-preview">
        <?php if ($heroImgU): ?>
          <img src="<?= e($heroImgU) ?>" alt="Current services hero">
        <?php else: ?>
          <div class="sp-preview-empty">
            <i class="fas fa-chart-line"></i>
            <span>Using the built-in dashboard illustration</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Upload a replacement</label>
        <input type="file" name="sp_hero_image" class="form-control" accept="image/*">
        <div class="form-hint">Landscape works best, around 1000&times;750. An upload replaces the illustration.</div>
      </div>
      <?php if ($heroImgU): ?>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:14px">
        <input type="checkbox" name="remove_image" value="1"> Remove image and go back to the illustration
      </label>
      <?php endif; ?>
      <button class="btn btn-primary"><i class="fas fa-upload"></i> Save image</button>
    </div>
  </form>
</div>

<?php /* ══ SECTION HEADINGS ══ */ elseif ($tab === 'sections'): ?>
<form method="POST">
  <?= csrfField() ?><input type="hidden" name="action" value="sections">
  <div class="sp-cols">
    <?php foreach ([
      ['overview', 'Capabilities Grid', 'fa-th-large', 'var(--violet)'],
      ['why',      'Why Choose Us',     'fa-award',    'var(--amber)'],
      ['stack',    'Technology Stack',  'fa-layer-group', 'var(--blue)'],
      ['process',  'Process',           'fa-diagram-next', 'var(--cyan)'],
      ['finder',   'Finder',            'fa-compass',  'var(--emerald)'],
    ] as [$k, $label, $ico, $col]): ?>
    <div class="card" style="margin-bottom:0">
      <div class="card-header"><h3><i class="fas <?= $ico ?>" style="color:<?= $col ?>;margin-right:8px"></i><?= e($label) ?></h3></div>
      <div class="card-body">
        <div class="form-group">
          <label>Eyebrow</label>
          <input type="text" name="sp_<?= $k ?>_eyebrow" class="form-control" value="<?= e($v("sp_{$k}_eyebrow")) ?>">
        </div>
        <div class="sp-2">
          <div class="form-group">
            <label>Heading</label>
            <input type="text" name="sp_<?= $k ?>_title" class="form-control" value="<?= e($v("sp_{$k}_title")) ?>">
          </div>
          <div class="form-group">
            <label>Gradient words</label>
            <input type="text" name="sp_<?= $k ?>_accent" class="form-control" value="<?= e($v("sp_{$k}_accent")) ?>">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>Sub-heading</label>
          <textarea name="sp_<?= $k ?>_sub" class="form-control" rows="2"><?= e((string)getSetting("sp_{$k}_sub", $D["sp_{$k}_sub"] ?? '')) ?></textarea>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:18px"><button class="btn btn-primary"><i class="fas fa-check"></i> Save all headings</button></div>
</form>

<?php /* ══ LISTS ══ */ elseif ($tab === 'lists'):
  $cols  = SP_LIST_SHAPES[$listKey];
  $rows  = spList($listKey);
  [$lTitle, $lIcon, $lHint] = $listMeta[$listKey];
  $labels = ['title'=>'Title','text'=>'Description','icon'=>'Icon','label'=>'Label','slug'=>'Service slug',
             'group'=>'Category','lead'=>'Heading','accent'=>'Gradient words','sub'=>'Sub-heading'];
  $slugs  = array_column(dbFetchAll("SELECT slug FROM services WHERE is_active=1 ORDER BY sort_order"), 'slug');
?>
<div class="sp-list-wrap">
  <div class="sp-list-nav">
    <?php foreach ($listMeta as $k => [$t, $ico, $hint]): ?>
    <a href="?tab=lists&list=<?= e($k) ?>" class="sp-list-link <?= $listKey === $k ? 'is-on' : '' ?>">
      <i class="fas <?= $ico ?>"></i>
      <span><?= e($t) ?></span>
      <em><?= count(spList($k)) ?></em>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card" style="margin-bottom:0">
    <div class="card-header">
      <h3><i class="fas <?= $lIcon ?>" style="color:var(--violet);margin-right:8px"></i><?= e($lTitle) ?></h3>
      <form method="POST" onsubmit="return confirm('Restore the original content for this list?')" style="margin:0">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="reset_list">
        <input type="hidden" name="list_key" value="<?= e($listKey) ?>">
        <button class="btn btn-secondary btn-sm"><i class="fas fa-rotate-left"></i> Restore original</button>
      </form>
    </div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--gray);margin:0 0 16px"><?= e($lHint) ?></p>

      <form method="POST" id="listForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_list">
        <input type="hidden" name="list_key" value="<?= e($listKey) ?>">

        <div id="rows">
          <?php foreach ($rows as $r): ?>
          <div class="sp-row">
            <span class="sp-row-grip"><i class="fas fa-grip-vertical"></i></span>
            <div class="sp-row-fields">
              <?php foreach ($cols as $c): ?>
              <div class="form-group" style="margin-bottom:0">
                <label><?= e($labels[$c] ?? ucfirst($c)) ?></label>
                <?php if ($c === 'slug'): ?>
                  <select name="slug[]" class="form-control">
                    <option value="">&mdash; none &mdash;</option>
                    <?php foreach ($slugs as $sl): ?>
                    <option value="<?= e($sl) ?>" <?= ($r['slug'] ?? '') === $sl ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif (in_array($c, ['text','sub'], true)): ?>
                  <textarea name="<?= e($c) ?>[]" class="form-control" rows="2"><?= e((string)($r[$c] ?? '')) ?></textarea>
                <?php else: ?>
                  <input type="text" name="<?= e($c) ?>[]" class="form-control" value="<?= e((string)($r[$c] ?? '')) ?>">
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="sp-row-del" onclick="this.closest('.sp-row').remove()" title="Remove">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px">
          <button type="button" class="btn btn-secondary" onclick="addRow()"><i class="fas fa-plus"></i> Add item</button>
          <button class="btn btn-primary"><i class="fas fa-check"></i> Save list</button>
        </div>
        <p style="font-size:12.5px;color:var(--gray);margin:12px 0 0">
          Clearing the first field of a row deletes it on save.
        </p>
      </form>
    </div>
  </div>
</div>

<template id="rowTpl">
  <div class="sp-row">
    <span class="sp-row-grip"><i class="fas fa-grip-vertical"></i></span>
    <div class="sp-row-fields">
      <?php foreach ($cols as $c): ?>
      <div class="form-group" style="margin-bottom:0">
        <label><?= e($labels[$c] ?? ucfirst($c)) ?></label>
        <?php if ($c === 'slug'): ?>
          <select name="slug[]" class="form-control">
            <option value="">&mdash; none &mdash;</option>
            <?php foreach ($slugs as $sl): ?><option value="<?= e($sl) ?>"><?= e($sl) ?></option><?php endforeach; ?>
          </select>
        <?php elseif (in_array($c, ['text','sub'], true)): ?>
          <textarea name="<?= e($c) ?>[]" class="form-control" rows="2"></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($c) ?>[]" class="form-control">
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="sp-row-del" onclick="this.closest('.sp-row').remove()" title="Remove">
      <i class="fas fa-trash"></i>
    </button>
  </div>
</template>
<script>
function addRow() {
  var t = document.getElementById('rowTpl');
  document.getElementById('rows').appendChild(t.content.cloneNode(true));
}
</script>

<?php /* ══ CTA ══ */ else: ?>
<form method="POST" class="card" style="max-width:760px">
  <?= csrfField() ?><input type="hidden" name="action" value="cta">
  <div class="card-header"><h3><i class="fas fa-bullhorn" style="color:var(--rose);margin-right:8px"></i>Closing CTA</h3></div>
  <div class="card-body">
    <div class="sp-2">
      <div class="form-group">
        <label>Heading</label>
        <input type="text" name="sp_cta_title" class="form-control" value="<?= e($v('sp_cta_title')) ?>">
      </div>
      <div class="form-group">
        <label>Gradient words</label>
        <input type="text" name="sp_cta_accent" class="form-control" value="<?= e($v('sp_cta_accent')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Sub-heading</label>
      <textarea name="sp_cta_sub" class="form-control" rows="3"><?= e($v('sp_cta_sub')) ?></textarea>
    </div>
    <div class="sp-2">
      <div class="form-group">
        <label>Button 1 label</label>
        <input type="text" name="sp_cta_btn1_text" class="form-control" value="<?= e($v('sp_cta_btn1_text')) ?>">
      </div>
      <div class="form-group">
        <label>Button 1 link</label>
        <input type="text" name="sp_cta_btn1_url" class="form-control" value="<?= e($v('sp_cta_btn1_url')) ?>">
      </div>
    </div>
    <div class="sp-2">
      <div class="form-group">
        <label>Button 2 label</label>
        <input type="text" name="sp_cta_btn2_text" class="form-control" value="<?= e($v('sp_cta_btn2_text')) ?>">
        <div class="form-hint">Blank hides the button.</div>
      </div>
      <div class="form-group">
        <label>Button 2 link</label>
        <input type="text" name="sp_cta_btn2_url" class="form-control" value="<?= e($v('sp_cta_btn2_url')) ?>">
      </div>
    </div>
    <button class="btn btn-primary"><i class="fas fa-check"></i> Save CTA</button>
  </div>
</form>
<?php endif; ?>

<style>
.sp-cols{ display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
.sp-2{ display:grid; grid-template-columns:1fr 1fr; gap:0 16px; }
.sp-preview{
  border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden;
  background:var(--light); margin-bottom:16px; min-height:170px;
  display:flex; align-items:center; justify-content:center;
}
.sp-preview img{ width:100%; display:block; }
.sp-preview-empty{
  display:flex; flex-direction:column; align-items:center; gap:10px;
  color:var(--gray); font-size:13px; padding:34px 20px; text-align:center;
}
.sp-preview-empty i{ font-size:26px; color:var(--violet); opacity:.6; }
.sp-list-wrap{ display:grid; grid-template-columns:236px 1fr; gap:20px; align-items:start; }
.sp-list-nav{ display:flex; flex-direction:column; gap:6px; }
.sp-list-link{
  display:flex; align-items:center; gap:10px; padding:11px 13px;
  border-radius:var(--radius-md); border:1px solid transparent;
  font-size:13.5px; font-weight:600; color:var(--gray2); text-decoration:none;
}
.sp-list-link:hover{ background:var(--light); color:var(--dark); }
.sp-list-link.is-on{ background:var(--white); border-color:var(--border); color:var(--violet); box-shadow:var(--shadow-sm); }
.sp-list-link i{ width:16px; text-align:center; }
.sp-list-link em{
  margin-left:auto; font-style:normal; font-size:11px; font-weight:700;
  background:var(--light); color:var(--gray); border-radius:20px; padding:2px 8px;
}
.sp-list-link.is-on em{ background:var(--violet); color:#fff; }
.sp-row{
  display:flex; gap:12px; align-items:flex-start;
  padding:14px; margin-bottom:10px;
  border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--white);
}
.sp-row-grip{ color:var(--gray3,#c3c7d4); padding-top:26px; cursor:grab; }
.sp-row-fields{ flex:1; display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; }
.sp-row-del{
  background:none; border:1px solid var(--border); color:var(--rose);
  border-radius:var(--radius-md); width:34px; height:34px; margin-top:22px;
  cursor:pointer; flex-shrink:0;
}
.sp-row-del:hover{ background:#fff1f4; }
@media(max-width:1080px){ .sp-cols{ grid-template-columns:1fr; } }
@media(max-width:900px){ .sp-list-wrap{ grid-template-columns:1fr; }
  .sp-list-nav{ flex-direction:row; flex-wrap:wrap; } }
@media(max-width:620px){ .sp-2{ grid-template-columns:1fr; } }
</style>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
