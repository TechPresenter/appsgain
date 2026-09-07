<?php
/**
 * Admin — Certifications & Partners.
 *
 * Manages the trust marquee that sits directly above the site footer:
 * the organisations themselves, plus the section wrapper (title,
 * subtitle, visibility, background, speed, logo treatment).
 *
 * Ordering is drag-and-drop, persisted to display_order.
 */
$adminTitle = 'Certifications & Partners';
$adminPage  = 'certifications';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* Two groups share this table and this screen; only the rendering differs. */
$SECTIONS = [
    'trust'  => ['label' => 'Certifications & Partners', 'blurb' => 'Scrolling marquee above the footer.'],
    'listed' => ['label' => 'Our Product Listed On',      'blurb' => 'Launch-directory badges, shown as a grid.'],
];
$section = isset($_GET['section']) && isset($SECTIONS[$_GET['section']]) ? $_GET['section'] : 'trust';
$backUrl = ADMIN_URL . '/pages/certifications.php?section=' . $section;
$action  = sanitizeInput($_GET['action'] ?? 'list');
$editId  = (int)($_GET['id'] ?? 0);

$BGS = ['soft' => 'Soft grey', 'white' => 'White', 'tint' => 'Brand tint'];

/** Accept only a real http(s) URL, or nothing. $max matches the column. */
$cleanUrl = static function (string $u, int $max = 300): ?string {
    $u = trim($u);
    if ($u === '') return '';
    if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
    if (!filter_var($u, FILTER_VALIDATE_URL)) return null;
    if (mb_strlen($u) > $max) return null;
    return $u;
};

/* ── Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $post = $_POST['action'] ?? '';

    /* Section wrapper settings */
    if ($post === 'section') {
        $pfx = $section === 'listed' ? 'listed_section_' : 'trust_section_';
        saveSetting($pfx . 'show',     isset($_POST['show']) ? '1' : '0', 'trust');
        saveSetting($pfx . 'title',    sanitizeInput($_POST['title'] ?? ''), 'trust');
        saveSetting($pfx . 'subtitle', sanitizeInput($_POST['subtitle'] ?? ''), 'trust');
        saveSetting($pfx . 'bg',
            array_key_exists($_POST['bg'] ?? '', $BGS) ? $_POST['bg'] : 'soft', 'trust');
        if ($section === 'trust') {
            saveSetting('trust_section_speed',
                (string) max(12, min(120, (int)($_POST['speed'] ?? 38))), 'trust');
            saveSetting('trust_logo_style',
                ($_POST['logo_style'] ?? '') === 'color' ? 'color' : 'grayscale', 'trust');
        }
        logActivity('update', 'certifications', 'Updated trust section settings');
        setFlash('success', 'Section settings saved.');
        redirect($backUrl);
    }

    if ($post === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $old = dbFetchValue("SELECT logo FROM certifications_partners WHERE id = ?", [$id]);
        dbExecute("DELETE FROM certifications_partners WHERE id = ?", [$id]);
        if ($old) deleteFile($old);
        logActivity('delete', 'certifications', 'Deleted partner #' . $id);
        setFlash('success', 'Item deleted.');
        redirect($backUrl);
    }

    if ($post === 'toggle') {
        dbExecute("UPDATE certifications_partners SET is_active = 1 - is_active WHERE id = ?",
            [(int)($_POST['id'] ?? 0)]);
        redirect($backUrl);
    }

    /* Drag-and-drop order: ids arrive already in their new sequence */
    if ($post === 'reorder') {
        $ids = array_filter(array_map('intval', explode(',', (string)($_POST['order'] ?? ''))));
        $i = 10;
        foreach ($ids as $id) {
            dbExecute("UPDATE certifications_partners SET display_order = ? WHERE id = ?", [$i, $id]);
            $i += 10;
        }
        if (isAjax()) { jsonResponse(true, 'Order saved.'); }
        setFlash('success', 'Order saved.');
        redirect($backUrl);
    }

    if ($post === 'create' || $post === 'update') {
        $id   = (int)($_POST['edit_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $desc = sanitizeInput($_POST['description'] ?? '');
        $url  = $cleanUrl((string)($_POST['website_url'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) $errors[] = 'Name must be between 2 and 150 characters.';
        if (mb_strlen($desc) > 300) $errors[] = 'Description must be 300 characters or fewer.';
        if ($url === null) $errors[] = 'Website URL is not valid.';

        if ($errors) {
            setFlash('error', $errors[0]);
            /* backUrl already carries ?section=, so continue with & */
            redirect($backUrl . ($id ? '&action=edit&id=' . $id : '&action=add'));
        }

        $logoUrl = $cleanUrl((string)($_POST['logo_url'] ?? ''), 500);
        if ($logoUrl === null) {
            setFlash('error', 'Logo image URL is not valid.');
            redirect($backUrl);
        }

        $data = [
            'section'       => $section,
            'logo_url'      => $logoUrl,
            'name'          => $name,
            'description'   => $desc,
            'website_url'   => $url,
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        $current = $id ? dbFetchValue("SELECT logo FROM certifications_partners WHERE id = ?", [$id]) : null;

        if (!empty($_FILES['logo']['name'])) {
            /* Tighter than the shared helper: logos are images, and small. */
            $f = $_FILES['logo'];
            if (($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
                setFlash('error', 'Logo upload failed.');
                redirect($backUrl);
            }
            if ($f['size'] > 2 * 1024 * 1024) {
                setFlash('error', 'Logo must be 2MB or smaller.');
                redirect($backUrl);
            }
            $mime = @mime_content_type($f['tmp_name']) ?: '';
            $ok   = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/gif'];
            if (!in_array($mime, $ok, true)) {
                setFlash('error', 'Logo must be PNG, JPG, WebP, GIF or SVG.');
                redirect($backUrl);
            }
            /* uploadFile re-verifies the MIME and sanitises SVG markup */
            $up = uploadFile($f, 'partners');
            if (!$up['success']) {
                setFlash('error', $up['error']);
                redirect($backUrl);
            }
            if ($current) deleteFile($current);
            $data['logo'] = $up['path'];
        } elseif (!empty($_POST['remove_logo']) && $current) {
            deleteFile($current);
            $data['logo'] = '';
        }

        if ($post === 'create') {
            if (!$data['display_order']) {
                $data['display_order'] = (int) dbFetchValue(
                    "SELECT COALESCE(MAX(display_order),0) + 10 FROM certifications_partners WHERE section = ?", [$section]);
            }
            dbInsertRow('certifications_partners', $data);
            logActivity('create', 'certifications', 'Added partner: ' . $name);
            setFlash('success', 'Item added.');
        } else {
            dbUpdateRow('certifications_partners', $data, 'id = ?', [$id]);
            logActivity('update', 'certifications', 'Updated partner: ' . $name);
            setFlash('success', 'Item updated.');
        }
        redirect($backUrl);
    }
}

/* ── View data ── */
$rows = dbFetchAll("SELECT * FROM certifications_partners WHERE section = ? ORDER BY display_order ASC, id ASC", [$section]);
$editing = null;
if ($action === 'edit' && $editId) {
    $editing = dbFetchRow("SELECT * FROM certifications_partners WHERE id = ?", [$editId]);
    if (!$editing) { setFlash('error', 'Item not found.'); redirect($backUrl); }
}
$s        = getAllSettings();
$pfx      = $section === 'listed' ? 'listed_section_' : 'trust_section_';
$secShow  = ($s[$pfx . 'show'] ?? '1') === '1';
$secTitle = $s[$pfx . 'title'] ?? '';
$secSub   = $s[$pfx . 'subtitle'] ?? '';
$secBg    = $s[$pfx . 'bg'] ?? 'soft';
$secSpeed = (int)($s['trust_section_speed'] ?? 38);
$secStyle = $s['trust_logo_style'] ?? 'grayscale';

$activeCount  = 0; $noLogo = 0;
foreach ($rows as $r) { if ($r['is_active']) $activeCount++; if ($r['logo'] === '') $noLogo++; }

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-content">

  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <span>Certifications &amp; Partners</span>
  </nav>

  <div class="cp-tabs" role="tablist" aria-label="Section">
    <?php foreach ($SECTIONS as $k => $meta): ?>
    <a role="tab" aria-selected="<?= $section === $k ? 'true' : 'false' ?>"
       class="cp-tab <?= $section === $k ? 'is-on' : '' ?>"
       href="?section=<?= e($k) ?>">
      <i class="fas <?= $k === 'trust' ? 'fa-award' : 'fa-rocket' ?>" aria-hidden="true"></i>
      <?= e($meta['label']) ?>
      <span><?= (int) dbFetchValue("SELECT COUNT(*) FROM certifications_partners WHERE section = ?", [$k]) ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= e($SECTIONS[$section]['label']) ?></h1>
      <p class="page-sub">
        <?= e($SECTIONS[$section]['blurb']) ?>
        <?= $activeCount ?> of <?= count($rows) ?> visible.
      </p>
    </div>
    <div class="page-header-actions">
      <a href="<?= SITE_URL ?>/#tmqTitle" target="_blank" rel="noopener" class="btn btn-secondary">
        <i class="fas fa-arrow-up-right-from-square"></i> View on site
      </a>
      <a href="?action=add&section=<?= e($section) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</a>
    </div>
  </div>

  <?php if ($noLogo > 0): ?>
  <div class="cp-hint">
    <i class="fas fa-image" aria-hidden="true"></i>
    <div>
      <strong><?= $noLogo ?> item<?= $noLogo === 1 ? ' has' : 's have' ?> no logo yet.</strong>
      Those show a monogram on the site. Upload the official artwork for each organisation —
      third-party logos are trademarks, so they need to come from you rather than be generated.
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ Section settings ══ -->
  <div class="card" style="padding:20px;margin-bottom:20px;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 14px;">Section settings</h2>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="section">
      <div class="cp-grid2">
        <div class="form-group">
          <label for="title">Section title</label>
          <input type="text" name="title" id="title" class="form-control" value="<?= e($secTitle) ?>" maxlength="150">
        </div>
        <div class="form-group">
          <label for="subtitle">Section subtitle</label>
          <input type="text" name="subtitle" id="subtitle" class="form-control" value="<?= e($secSub) ?>" maxlength="300">
        </div>
      </div>
      <div class="<?= $section === 'trust' ? 'cp-grid3' : 'cp-grid2' ?>">
        <div class="form-group">
          <label for="bg">Background</label>
          <select name="bg" id="bg" class="form-control">
            <?php foreach ($BGS as $k => $lbl): ?>
            <option value="<?= e($k) ?>" <?= $secBg === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($section === 'trust'): ?>
        <div class="form-group">
          <label for="speed">Loop duration (seconds)</label>
          <input type="number" name="speed" id="speed" class="form-control" min="12" max="120" value="<?= $secSpeed ?>">
          <small>Higher is slower. 38 suits about a dozen items.</small>
        </div>
        <div class="form-group">
          <label for="logo_style">Logo treatment</label>
          <select name="logo_style" id="logo_style" class="form-control">
            <option value="grayscale" <?= $secStyle === 'grayscale' ? 'selected' : '' ?>>Greyscale, colour on hover</option>
            <option value="color"     <?= $secStyle === 'color' ? 'selected' : '' ?>>Full colour</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="cp-check">
          <input type="checkbox" name="show" value="1" <?= $secShow ? 'checked' : '' ?>>
          Show this section on the website
        </label>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save section settings</button>
    </form>
  </div>

  <?php if ($action === 'add' || $editing): $f = $editing ?? []; ?>
  <!-- ══ Editor ══ -->
  <div class="card" style="padding:20px;margin-bottom:20px;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 14px;">
      <?= $editing ? 'Edit item' : 'New item' ?>
    </h2>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
      <input type="hidden" name="edit_id" value="<?= (int)($f['id'] ?? 0) ?>">

      <div class="cp-grid2">
        <div class="form-group">
          <label for="name">Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="name" id="name" class="form-control" required maxlength="150"
                 value="<?= e($f['name'] ?? '') ?>" placeholder="e.g. ISO 9001:2015">
        </div>
        <div class="form-group">
          <label for="website_url">Website URL</label>
          <input type="url" name="website_url" id="website_url" class="form-control" maxlength="300"
                 value="<?= e($f['website_url'] ?? '') ?>" placeholder="https://example.com">
          <small>Optional. The chip becomes a link when set.</small>
        </div>
      </div>

      <div class="form-group">
        <label for="description">Short description</label>
        <input type="text" name="description" id="description" class="form-control" maxlength="300"
               value="<?= e($f['description'] ?? '') ?>" placeholder="Shown as the tooltip on hover">
      </div>

      <div class="cp-grid2">
        <div class="form-group">
          <label for="logo">Logo</label>
          <input type="file" name="logo" id="logo" class="form-control" accept="image/*" onchange="cpPreview(this)">
          <small>PNG, JPG, WebP, GIF or SVG. Max 2MB. Transparent PNG or SVG works best.</small>
          <div class="cp-preview" id="cpPreview" <?= empty($f['logo']) ? 'hidden' : '' ?>>
            <img id="cpPreviewImg"
                 src="<?= !empty($f['logo']) ? UPLOADS_URL . '/' . e($f['logo']) : '' ?>" alt="Logo preview">
            <?php if (!empty($f['logo'])): ?>
            <label class="cp-check" style="margin:0;">
              <input type="checkbox" name="remove_logo" value="1"> Remove logo
            </label>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-group">
          <label for="logo_url">Or paste a badge image URL</label>
          <input type="url" name="logo_url" id="logo_url" class="form-control" maxlength="500"
                 value="<?= e($f['logo_url'] ?? '') ?>" placeholder="https://…/badge.svg">
          <small>Most launch directories give you a hosted badge to link. A URL here wins over an upload.</small>
        </div>

        <div class="form-group">
          <label for="display_order">Display order</label>
          <input type="number" name="display_order" id="display_order" class="form-control"
                 value="<?= (int)($f['display_order'] ?? 0) ?>">
          <small>Leave 0 to append to the end. Drag the list below to reorder visually.</small>
        </div>
      </div>

      <div class="form-group">
        <label class="cp-check">
          <input type="checkbox" name="is_active" value="1" <?= (!isset($f['is_active']) || $f['is_active']) ? 'checked' : '' ?>>
          Visible on the website
        </label>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save item</button>
        <a href="<?= e($backUrl) ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- ══ List ══ -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h3>All items</h3>
      <span class="badge badge-secondary"><?= count($rows) ?> item<?= count($rows) === 1 ? '' : 's' ?></span>
    </div>

    <?php if (!$rows): ?>
      <div class="empty-state">
        <i class="fas fa-award"></i>
        <h3>No certifications or partners yet</h3>
        <p>Add the first organisation and it appears in the marquee above the footer.</p>
        <a href="?action=add&section=<?= e($section) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</a>
      </div>
    <?php else: ?>
      <p class="cp-draghint"><i class="fas fa-up-down-left-right"></i> Drag a row by its handle to reorder. Saved automatically.</p>
      <div style="overflow-x:auto;">
      <table class="table" id="cpTable">
        <thead>
          <tr>
            <th style="width:46px;"></th>
            <th style="width:70px;">Logo</th>
            <th>Name</th>
            <th>Link</th>
            <th style="width:90px;">Order</th>
            <th style="width:100px;">Status</th>
            <th style="text-align:right;width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody id="cpBody">
        <?php foreach ($rows as $r): ?>
          <tr draggable="true" data-id="<?= (int)$r['id'] ?>">
            <td><span class="cp-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span></td>
            <td>
              <?php $thumb = $r['logo_url'] ?: ($r['logo'] ? UPLOADS_URL . '/' . $r['logo'] : ''); ?>
              <?php if ($thumb): ?>
                <img class="cp-thumb" src="<?= e($thumb) ?>" alt="">
              <?php else: ?>
                <span class="cp-thumb cp-thumb--none" title="No logo uploaded"><i class="fas fa-image"></i></span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e($r['name']) ?></strong>
              <?php if ($r['description']): ?>
                <div style="font-size:12px;color:var(--muted);"><?= e($r['description']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['website_url']): ?>
                <a href="<?= e($r['website_url']) ?>" target="_blank" rel="noopener nofollow"
                   style="font-size:12.5px;color:var(--brand);">Visit <i class="fas fa-arrow-up-right-from-square" style="font-size:10px"></i></a>
              <?php else: ?>
                <span style="color:var(--faint);">—</span>
              <?php endif; ?>
            </td>
            <td><?= (int)$r['display_order'] ?></td>
            <td>
              <span class="badge <?= $r['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                <?= $r['is_active'] ? 'Visible' : 'Hidden' ?>
              </span>
            </td>
            <td style="text-align:right;white-space:nowrap;">
              <a href="?action=edit&id=<?= (int)$r['id'] ?>&section=<?= e($section) ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Edit"><i class="fas fa-pen"></i></a>
              <button type="submit" form="cpT<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon"
                      aria-label="<?= $r['is_active'] ? 'Hide' : 'Show' ?>">
                <i class="fas fa-<?= $r['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
              </button>
              <button type="submit" form="cpD<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm btn-icon" aria-label="Delete"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>

  <?php foreach ($rows as $r): ?>
    <form id="cpT<?= (int)$r['id'] ?>" method="POST" style="display:none;">
      <?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    </form>
    <form id="cpD<?= (int)$r['id'] ?>" method="POST" style="display:none;"
          onsubmit="return confirm('Delete &quot;<?= e(addslashes($r['name'])) ?>&quot;? This cannot be undone.');">
      <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    </form>
  <?php endforeach; ?>

  <form id="cpOrderForm" method="POST" style="display:none;">
    <?= csrfField() ?><input type="hidden" name="action" value="reorder"><input type="hidden" name="order" id="cpOrder">
  </form>
</div>

<style>
.cp-tabs{ display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }
.cp-tab{
  display:inline-flex; align-items:center; gap:9px;
  height:40px; padding:0 16px; border-radius:var(--r-sm);
  background:var(--surface); border:1px solid var(--line);
  color:var(--body); font-size:13.5px; font-weight:600; text-decoration:none;
  transition:border-color .18s ease, color .18s ease, background .18s ease;
}
.cp-tab:hover{ border-color:var(--brand-line); color:var(--brand); }
.cp-tab.is-on{
  background-color:var(--brand-solid); background-image:var(--ag-grad-btn);
  border-color:transparent; color:#fff;
}
.cp-tab span{
  padding:1px 8px; border-radius:999px; font-size:11.5px; font-weight:700;
  background:var(--surface-3); color:var(--muted);
}
.cp-tab.is-on span{ background:rgba(255,255,255,.24); color:#fff; }

.cp-grid2{ display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
.cp-grid3{ display:grid; grid-template-columns:repeat(3,1fr); gap:0 18px; }
@media (max-width:820px){ .cp-grid2,.cp-grid3{ grid-template-columns:1fr; } }

.cp-check{
  display:inline-flex; align-items:center; gap:9px;
  text-transform:none; letter-spacing:0; font-weight:500;
  font-size:13.5px; color:var(--ink-2); cursor:pointer;
}
.cp-hint{
  display:flex; gap:13px; align-items:flex-start;
  padding:14px 16px; margin-bottom:18px;
  border-radius:var(--r-md); border:1px solid var(--info-line);
  background:var(--info-bg); color:var(--ink-2);
  font-size:13.5px; line-height:1.6;
}
.cp-hint i{ color:var(--info); font-size:16px; margin-top:2px; }
.cp-hint strong{ color:var(--ink); }

.cp-preview{
  display:flex; align-items:center; gap:14px; margin-top:10px;
  padding:10px 12px; border:1px solid var(--line); border-radius:var(--r-sm);
  background:var(--surface-2);
}
.cp-preview img{ max-width:80px; max-height:44px; object-fit:contain; display:block; }

.cp-thumb{
  display:grid; place-items:center;
  width:44px; height:32px; object-fit:contain;
}
.cp-thumb--none{
  border-radius:7px; background:var(--surface-3);
  color:var(--faint); font-size:12px;
}
.cp-draghint{
  display:flex; align-items:center; gap:8px;
  margin:0; padding:10px 16px;
  font-size:12.5px; color:var(--muted); border-bottom:1px solid var(--line);
}
.cp-handle{ cursor:grab; color:var(--faint); font-size:14px; }
.cp-handle:active{ cursor:grabbing; }
#cpBody tr.cp-dragging{ opacity:.45; }
#cpBody tr.cp-over{ box-shadow:inset 0 2px 0 var(--brand); }
</style>

<script>
/* Logo preview before upload */
function cpPreview(input) {
  var box = document.getElementById('cpPreview');
  var img = document.getElementById('cpPreviewImg');
  var f = input.files && input.files[0];
  if (!f) return;
  if (f.size > 2 * 1024 * 1024) {
    alert('Logo must be 2MB or smaller.');
    input.value = '';
    return;
  }
  img.src = URL.createObjectURL(f);
  box.hidden = false;
}

/* Drag to reorder — posts the new id sequence once the row is dropped */
(function () {
  var body = document.getElementById('cpBody');
  if (!body) return;
  var dragged = null;

  body.addEventListener('dragstart', function (e) {
    var tr = e.target.closest('tr');
    if (!tr) return;
    dragged = tr;
    tr.classList.add('cp-dragging');
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', tr.dataset.id); } catch (err) {}
  });

  body.addEventListener('dragover', function (e) {
    e.preventDefault();
    var tr = e.target.closest('tr');
    if (!tr || tr === dragged) return;
    body.querySelectorAll('.cp-over').forEach(function (x) { x.classList.remove('cp-over'); });
    tr.classList.add('cp-over');
    var box = tr.getBoundingClientRect();
    var after = (e.clientY - box.top) > box.height / 2;
    body.insertBefore(dragged, after ? tr.nextSibling : tr);
  });

  body.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('cp-dragging');
    body.querySelectorAll('.cp-over').forEach(function (x) { x.classList.remove('cp-over'); });
    dragged = null;
    var ids = Array.prototype.map.call(body.querySelectorAll('tr'), function (tr) { return tr.dataset.id; });
    document.getElementById('cpOrder').value = ids.join(',');
    document.getElementById('cpOrderForm').submit();
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
