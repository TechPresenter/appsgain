<?php
/**
 * Admin — service page sections.
 *
 * Manages the blocks that make up every service page: headings, copy,
 * repeatable items (process steps, bullet points), images and buttons.
 *
 * Rows with service_id NULL are GLOBAL DEFAULTS shown on every service
 * page. Selecting a service lets you override a default for that service
 * alone, leaving the rest of the site untouched.
 */
$adminTitle = 'Service Page Sections';
$adminPage  = 'service-sections';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$LAYOUTS = [
    'numbered'    => 'Feature grid — numbered, pulls the service\'s own feature list',
    'process'     => 'Process — ordered steps with icons',
    'split-quote' => 'Split — bullet list beside a pull-quote card',
    'related'     => 'Related services — auto-filled cards',
    'cta'         => 'Call to action — closing banner',
    'richtext'    => 'Free text — heading, optional image, rich content',
];
$BGS = ['white' => 'White', 'soft' => 'Soft grey', 'lav' => 'Lavender tint'];

$scopeId = isset($_GET['service_id']) && $_GET['service_id'] !== '' ? (int)$_GET['service_id'] : 0;
$action  = sanitizeInput($_GET['action'] ?? 'list');
$editId  = (int)($_GET['id'] ?? 0);
$backUrl = ADMIN_URL . '/pages/service-sections.php' . ($scopeId ? '?service_id=' . $scopeId : '');

$services = dbFetchAll("SELECT id, name FROM services ORDER BY sort_order ASC, name ASC");

/* ── Save / delete / reorder ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $post = $_POST['action'] ?? '';

    if ($post === 'delete') {
        dbExecute("DELETE FROM service_sections WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        setFlash('success', 'Section deleted.');
        redirect($backUrl);
    }

    if ($post === 'toggle') {
        dbExecute("UPDATE service_sections SET is_active = 1 - is_active WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        redirect($backUrl);
    }

    if ($post === 'reorder') {
        foreach (($_POST['order'] ?? []) as $id => $pos) {
            dbExecute("UPDATE service_sections SET sort_order = ? WHERE id = ?", [(int)$pos, (int)$id]);
        }
        setFlash('success', 'Order saved.');
        redirect($backUrl);
    }

    if ($post === 'create' || $post === 'update') {
        /* Repeatable items arrive as parallel arrays */
        $items = [];
        $titles = $_POST['item_title'] ?? [];
        $icons  = $_POST['item_icon']  ?? [];
        $texts  = $_POST['item_text']  ?? [];
        foreach ($titles as $i => $t) {
            $t  = trim((string)$t);
            $tx = trim((string)($texts[$i] ?? ''));
            if ($t === '' && $tx === '') continue;      /* skip blank rows */
            $items[] = [
                'title' => sanitizeInput($t),
                'icon'  => sanitizeInput(trim((string)($icons[$i] ?? ''))),
                'text'  => sanitizeInput($tx),
            ];
        }

        /* json_encode returns false on malformed UTF-8 — which arrives more
           often than you would think, e.g. text pasted from Word. Encoding
           the failure away beats writing NULL and silently wiping every item
           the admin just typed. */
        $itemsJson = null;
        if ($items) {
            $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($itemsJson === false) {
                setFlash('error', 'Those items contained characters we could not read. Nothing was changed — please retype them.');
                redirect($backUrl);
            }
        }

        $sid = isset($_POST['service_id']) && $_POST['service_id'] !== '' ? (int)$_POST['service_id'] : null;

        $data = [
            'service_id'  => $sid,
            'section_key' => slugify($_POST['section_key'] ?? '') ?: 'section',
            'layout'      => array_key_exists($_POST['layout'] ?? '', $LAYOUTS) ? $_POST['layout'] : 'richtext',
            'eyebrow'     => sanitizeInput($_POST['eyebrow'] ?? ''),
            'lead'        => sanitizeInput($_POST['lead'] ?? ''),
            'accent'      => sanitizeInput($_POST['accent'] ?? ''),
            'subheading'  => sanitizeInput($_POST['subheading'] ?? ''),
            'body'        => $_POST['body'] ?? '',          /* trusted admin HTML */
            'items'       => $itemsJson,
            'btn1_text'   => sanitizeInput($_POST['btn1_text'] ?? ''),
            'btn1_url'    => sanitizeInput($_POST['btn1_url'] ?? ''),
            'btn2_text'   => sanitizeInput($_POST['btn2_text'] ?? ''),
            'btn2_url'    => sanitizeInput($_POST['btn2_url'] ?? ''),
            'bg'          => array_key_exists($_POST['bg'] ?? '', $BGS) ? $_POST['bg'] : 'white',
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        $id = (int)($_POST['edit_id'] ?? 0);
        $current = $id ? dbFetchValue("SELECT image FROM service_sections WHERE id = ?", [$id]) : null;

        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'services');
            if ($up['success']) {
                if ($current) deleteFile($current);
                $data['image'] = $up['path'];
            } else {
                setFlash('error', $up['error']);
                redirect($backUrl);
            }
        } elseif (!empty($_POST['remove_image']) && $current) {
            deleteFile($current);
            $data['image'] = '';
        }

        if ($post === 'create') {
            dbInsertRow('service_sections', $data);
            setFlash('success', 'Section added.');
        } else {
            dbUpdateRow('service_sections', $data, 'id = ?', [$id]);
            setFlash('success', 'Section updated.');
        }
        redirect($backUrl);
    }
}

/* ── Data for the view ── */
if ($scopeId) {
    $rows = dbFetchAll("SELECT * FROM service_sections WHERE service_id = ? ORDER BY sort_order, id", [$scopeId]);
    $inherited = dbFetchAll("SELECT * FROM service_sections WHERE service_id IS NULL ORDER BY sort_order, id");
    $ownKeys = array_column($rows, 'section_key');
} else {
    $rows = dbFetchAll("SELECT * FROM service_sections WHERE service_id IS NULL ORDER BY sort_order, id");
    $inherited = [];
    $ownKeys = [];
}

$editing = null;
if ($action === 'edit' && $editId) {
    $editing = dbFetchRow("SELECT * FROM service_sections WHERE id = ?", [$editId]);
    if (!$editing) { setFlash('error', 'Section not found.'); redirect($backUrl); }
}
$scopeName = '';
foreach ($services as $s) { if ((int)$s['id'] === $scopeId) $scopeName = $s['name']; }

/* admin-layout.php only sets variables — the chrome and stylesheets come
   from admin-head.php, which every admin page must include itself. */
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-content">

  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <a href="<?= ADMIN_URL ?>/pages/services.php">Services</a>
    <i class="fas fa-chevron-right"></i>
    <span>Page Sections</span>
  </nav>

  <div class="page-header">
    <div>
      <h1 class="page-title">Service Page Sections</h1>
      <p class="page-sub">
        <?php if ($scopeId): ?>
          Overrides for <strong><?= e($scopeName) ?></strong> — anything not overridden uses the global default.
        <?php else: ?>
          Global defaults. These render on every service page unless a service overrides them.
        <?php endif; ?>
      </p>
    </div>
    <div class="page-header-actions">
      <a href="?action=add<?= $scopeId ? '&service_id=' . $scopeId : '' ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Section
      </a>
    </div>
  </div>

  <!-- Scope picker -->
  <div class="card" style="padding:14px 16px;margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <label for="scopeSel" style="margin:0;">Editing</label>
      <select name="service_id" id="scopeSel" class="form-control" style="max-width:340px;"
              onchange="this.form.submit()">
        <option value="">Global defaults (all services)</option>
        <?php foreach ($services as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= $scopeId === (int)$s['id'] ? 'selected' : '' ?>>
          <?= e($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-secondary btn-sm" type="submit">Go</button></noscript>
    </form>
  </div>

  <?php if ($action === 'add' || $editing): ?>
  <!-- ══ Editor ══ -->
  <?php $f = $editing ?? []; $it = $editing ? json_decode((string)$editing['items'], true) : []; if (!is_array($it)) $it = []; ?>
  <div class="card" style="padding:22px;margin-bottom:22px;">
    <h2 style="font-size:16px;font-weight:700;margin:0 0 16px;">
      <?= $editing ? 'Edit section' : 'New section' ?>
    </h2>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
      <input type="hidden" name="edit_id" value="<?= (int)($f['id'] ?? 0) ?>">
      <input type="hidden" name="service_id" value="<?= $scopeId ?: '' ?>">

      <div class="form-grid-2">
        <div class="form-group">
          <label for="layout">Layout</label>
          <select name="layout" id="layout" class="form-control">
            <?php foreach ($LAYOUTS as $k => $lbl): ?>
            <option value="<?= e($k) ?>" <?= ($f['layout'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="section_key">Section key</label>
          <input type="text" name="section_key" id="section_key" class="form-control"
                 value="<?= e($f['section_key'] ?? '') ?>" placeholder="e.g. process" required>
          <small>Used as the anchor id. A service row with the same key replaces the global default.</small>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="eyebrow">Eyebrow</label>
          <input type="text" name="eyebrow" id="eyebrow" class="form-control"
                 value="<?= e($f['eyebrow'] ?? '') ?>" placeholder="How we deliver">
        </div>
        <div class="form-group">
          <label for="bg">Background</label>
          <select name="bg" id="bg" class="form-control">
            <?php foreach ($BGS as $k => $lbl): ?>
            <option value="<?= e($k) ?>" <?= ($f['bg'] ?? 'white') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="lead">Heading — plain part</label>
          <input type="text" name="lead" id="lead" class="form-control"
                 value="<?= e($f['lead'] ?? '') ?>" placeholder="From Brief to">
        </div>
        <div class="form-group">
          <label for="accent">Heading — gradient part</label>
          <input type="text" name="accent" id="accent" class="form-control"
                 value="<?= e($f['accent'] ?? '') ?>" placeholder="Live Product">
        </div>
      </div>

      <div class="form-group">
        <label for="subheading">Sub-heading</label>
        <input type="text" name="subheading" id="subheading" class="form-control"
               value="<?= e($f['subheading'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="body">Body</label>
        <textarea name="body" id="body" class="form-control" rows="4"><?= e($f['body'] ?? '') ?></textarea>
        <small>Used by the free-text and split layouts. Basic HTML allowed.</small>
      </div>

      <!-- Repeatable items -->
      <div class="form-group">
        <label>Items <small style="text-transform:none;letter-spacing:0;font-weight:500;">— process steps or bullet points</small></label>
        <div id="itemRows">
          <?php $rowsToShow = $it ?: [['title'=>'','icon'=>'','text'=>'']];
                foreach ($rowsToShow as $r): ?>
          <div class="item-row">
            <input type="text" name="item_title[]" class="form-control" placeholder="Title" value="<?= e($r['title'] ?? '') ?>">
            <input type="text" name="item_icon[]"  class="form-control" placeholder="fa-rocket" value="<?= e($r['icon'] ?? '') ?>">
            <input type="text" name="item_text[]"  class="form-control" placeholder="Description" value="<?= e($r['text'] ?? '') ?>">
            <button type="button" class="btn btn-secondary btn-sm btn-icon" onclick="this.closest('.item-row').remove()"
                    aria-label="Remove item"><i class="fas fa-trash"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addItemRow()">
          <i class="fas fa-plus"></i> Add item
        </button>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="btn1_text">Button 1 — label</label>
          <input type="text" name="btn1_text" id="btn1_text" class="form-control" value="<?= e($f['btn1_text'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="btn1_url">Button 1 — link</label>
          <input type="text" name="btn1_url" id="btn1_url" class="form-control"
                 value="<?= e($f['btn1_url'] ?? '') ?>" placeholder="/contact.php#enquiry">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label for="btn2_text">Button 2 — label</label>
          <input type="text" name="btn2_text" id="btn2_text" class="form-control" value="<?= e($f['btn2_text'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="btn2_url">Button 2 — link</label>
          <input type="text" name="btn2_url" id="btn2_url" class="form-control" value="<?= e($f['btn2_url'] ?? '') ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="image">Image</label>
          <input type="file" name="image" id="image" class="form-control" accept="image/*">
          <?php if (!empty($f['image'])): ?>
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
              <img src="<?= UPLOADS_URL ?>/<?= e($f['image']) ?>" alt="" style="height:46px;border-radius:8px;">
              <label style="text-transform:none;letter-spacing:0;font-weight:500;margin:0;">
                <input type="checkbox" name="remove_image" value="1"> Remove
              </label>
            </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="sort_order">Order</label>
          <input type="number" name="sort_order" id="sort_order" class="form-control"
                 value="<?= (int)($f['sort_order'] ?? 60) ?>">
        </div>
      </div>

      <div class="form-group">
        <label style="text-transform:none;letter-spacing:0;font-weight:500;">
          <input type="checkbox" name="is_active" value="1" <?= (!isset($f['is_active']) || $f['is_active']) ? 'checked' : '' ?>>
          Show this section
        </label>
      </div>

      <p style="font-size:12.5px;color:var(--muted);margin:0 0 14px;">
        Tip: write <code>{service}</code> in any heading, body or button label and it becomes the
        service name — so one global default reads correctly on every page.
      </p>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save section</button>
        <a href="<?= e($backUrl) ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- ══ List ══ -->
  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="reorder">
    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3><?= $scopeId ? 'Overrides for this service' : 'Global default sections' ?></h3>
        <span class="badge badge-secondary"><?= count($rows) ?> section<?= count($rows) === 1 ? '' : 's' ?></span>
      </div>

      <?php if (!$rows): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i>
          <h3>No sections here yet</h3>
          <p><?= $scopeId
                ? 'This service uses the global defaults. Add a section to override one of them.'
                : 'Add a section to build the shared service page layout.' ?></p>
          <a href="?action=add<?= $scopeId ? '&service_id=' . $scopeId : '' ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Section
          </a>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="table">
          <thead>
            <tr>
              <th style="width:78px;">Order</th>
              <th>Section</th>
              <th>Layout</th>
              <th>Items</th>
              <th>Status</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r):
            $n = count(json_decode((string)$r['items'], true) ?: []); ?>
            <tr>
              <td><input type="number" name="order[<?= (int)$r['id'] ?>]" value="<?= (int)$r['sort_order'] ?>"
                         class="form-control" style="width:68px;padding:6px 8px;"></td>
              <td>
                <strong><?= e($r['lead'] ?: $r['section_key']) ?> <?= e($r['accent']) ?></strong>
                <div style="font-size:12px;color:var(--muted);">
                  <code><?= e($r['section_key']) ?></code>
                  <?= $r['eyebrow'] ? ' · ' . e($r['eyebrow']) : '' ?>
                </div>
              </td>
              <td><?= e($LAYOUTS[$r['layout']] ?? $r['layout']) ?></td>
              <td><?= $n ?: '—' ?></td>
              <td>
                <span class="badge <?= $r['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                  <?= $r['is_active'] ? 'Visible' : 'Hidden' ?>
                </span>
              </td>
              <td style="text-align:right;white-space:nowrap;">
                <a href="?action=edit&id=<?= (int)$r['id'] ?><?= $scopeId ? '&service_id=' . $scopeId : '' ?>"
                   class="btn btn-outline btn-sm btn-icon" aria-label="Edit"><i class="fas fa-pen"></i></a>
                <button type="submit" form="tg<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon"
                        aria-label="<?= $r['is_active'] ? 'Hide' : 'Show' ?>">
                  <i class="fas fa-<?= $r['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                </button>
                <button type="submit" form="dl<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm btn-icon"
                        aria-label="Delete"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <div style="padding:14px 16px;border-top:1px solid var(--line);">
          <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-down-1-9"></i> Save order</button>
        </div>
      <?php endif; ?>
    </div>
  </form>

  <?php /* Separate forms so the reorder form is never nested */ ?>
  <?php foreach ($rows as $r): ?>
    <form id="tg<?= (int)$r['id'] ?>" method="POST" style="display:none;">
      <?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    </form>
    <form id="dl<?= (int)$r['id'] ?>" method="POST" style="display:none;"
          onsubmit="return confirm('Delete this section? This cannot be undone.');">
      <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    </form>
  <?php endforeach; ?>

  <?php if ($scopeId && $inherited): ?>
  <div class="card" style="margin-top:18px;">
    <div class="card-header"><h3>Inherited from global defaults</h3></div>
    <div style="padding:6px 16px 16px;">
      <p style="font-size:13px;color:var(--muted);margin:8px 0 12px;">
        These render on this service because it has no override for them.
      </p>
      <?php foreach ($inherited as $g):
        if (in_array($g['section_key'], $ownKeys, true)) continue; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;
                    padding:9px 0;border-bottom:1px solid var(--line);">
          <div>
            <strong><?= e($g['lead'] ?: $g['section_key']) ?> <?= e($g['accent']) ?></strong>
            <code style="font-size:11.5px;color:var(--muted);"><?= e($g['section_key']) ?></code>
          </div>
          <a class="btn btn-outline btn-sm"
             href="?action=add&service_id=<?= $scopeId ?>&from=<?= e($g['section_key']) ?>">Override</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<style>
.form-grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
.item-row{ display:grid; grid-template-columns:1fr 150px 2fr auto; gap:8px; margin-bottom:8px; align-items:start; }
@media (max-width:820px){
  .form-grid-2{ grid-template-columns:1fr; }
  .item-row{ grid-template-columns:1fr; }
}
.page-sub{ font-size:13.5px; color:var(--muted); margin:4px 0 0; }
</style>

<script>
function addItemRow() {
  var wrap = document.getElementById('itemRows');
  var row  = document.createElement('div');
  row.className = 'item-row';
  row.innerHTML =
    '<input type="text" name="item_title[]" class="form-control" placeholder="Title">' +
    '<input type="text" name="item_icon[]"  class="form-control" placeholder="fa-rocket">' +
    '<input type="text" name="item_text[]"  class="form-control" placeholder="Description">' +
    '<button type="button" class="btn btn-secondary btn-sm btn-icon" aria-label="Remove item">' +
    '<i class="fas fa-trash"></i></button>';
  row.querySelector('button').addEventListener('click', function () { row.remove(); });
  wrap.appendChild(row);
  row.querySelector('input').focus();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
