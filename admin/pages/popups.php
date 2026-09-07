<?php
/**
 * Admin — Popups.
 *
 * Controls the timed modal that appears over the site: what it says,
 * which pages it shows on, how long to wait, and whether a visitor
 * sees it once or every session.
 *
 * Only the first active popup inside its date window renders, so the
 * list is ordered and the top active row wins.
 */
$adminTitle = 'Popups';
$adminPage  = 'popups';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$backUrl = ADMIN_URL . '/pages/popups.php';
$action  = sanitizeInput($_GET['action'] ?? 'list');
$editId  = (int)($_GET['id'] ?? 0);

/** Pages the popup can be limited to — keys match $activePage. */
$PAGES = [
    'home' => 'Home', 'about' => 'About', 'services' => 'Services',
    'portfolio' => 'Portfolio', 'blog' => 'Blog', 'contact' => 'Contact',
    'products' => 'Products', 'careers' => 'Careers', 'faq' => 'FAQs',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $post = $_POST['action'] ?? '';

    if ($post === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $img = dbFetchValue("SELECT image FROM popups WHERE id = ?", [$id]);
        dbExecute("DELETE FROM popups WHERE id = ?", [$id]);
        if ($img) deleteFile($img);
        logActivity('delete', 'popups', 'Deleted popup #' . $id);
        setFlash('success', 'Popup deleted.');
        redirect($backUrl);
    }

    if ($post === 'toggle') {
        dbExecute("UPDATE popups SET is_active = 1 - is_active WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        redirect($backUrl);
    }

    if ($post === 'save') {
        $id = (int)($_POST['edit_id'] ?? 0);

        /* "all" wins over any individual ticks */
        $on = $_POST['display_on'] ?? [];
        if (!is_array($on)) $on = [];
        $on = array_values(array_intersect($on, array_keys($PAGES)));
        $displayOn = (!empty($_POST['on_all']) || !$on) ? 'all' : implode(',', $on);

        $url = trim((string)($_POST['button_url'] ?? ''));
        if ($url !== '' && !preg_match('~^(https?://|/|#|mailto:|tel:)~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        $data = [
            'title'         => sanitizeInput($_POST['title'] ?? ''),
            'content'       => $_POST['content'] ?? '',      /* trusted admin HTML */
            'button_text'   => sanitizeInput($_POST['button_text'] ?? ''),
            'button_url'    => $url,
            'display_on'    => $displayOn,
            'delay_seconds' => max(0, min(120, (int)($_POST['delay_seconds'] ?? 5))),
            'show_once'     => isset($_POST['show_once']) ? 1 : 0,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'start_date'    => trim((string)($_POST['start_date'] ?? '')) ?: null,
            'end_date'      => trim((string)($_POST['end_date'] ?? ''))   ?: null,
        ];

        if ($data['title'] === '' && trim(strip_tags($data['content'])) === '') {
            setFlash('error', 'Give the popup a title or some content.');
            redirect($backUrl);
        }

        $cur = $id ? dbFetchValue("SELECT image FROM popups WHERE id = ?", [$id]) : null;
        if (!empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            if ($f['size'] > 3 * 1024 * 1024) { setFlash('error', 'Image must be 3MB or smaller.'); redirect($backUrl); }
            $mime = @mime_content_type($f['tmp_name']) ?: '';
            if (!in_array($mime, ['image/png','image/jpeg','image/webp','image/gif'], true)) {
                setFlash('error', 'Image must be PNG, JPG, WebP or GIF.'); redirect($backUrl);
            }
            $up = uploadFile($f, 'popups');
            if (!$up['success']) { setFlash('error', $up['error']); redirect($backUrl); }
            if ($cur) deleteFile($cur);
            $data['image'] = $up['path'];
        } elseif (!empty($_POST['remove_image']) && $cur) {
            deleteFile($cur);
            $data['image'] = '';
        }

        if ($id) { dbUpdateRow('popups', $data, 'id = ?', [$id]); setFlash('success', 'Popup updated.'); }
        else     { dbInsertRow('popups', $data);                  setFlash('success', 'Popup created.'); }
        logActivity('update', 'popups', 'Saved popup: ' . $data['title']);
        redirect($backUrl);
    }
}

$rows    = dbFetchAll("SELECT * FROM popups ORDER BY id ASC");
$editing = null;
if ($action === 'edit' && $editId) {
    $editing = dbFetchRow("SELECT * FROM popups WHERE id = ?", [$editId]);
    if (!$editing) { setFlash('error', 'Popup not found.'); redirect($backUrl); }
}
/* Which row the site will actually use */
$liveId = (int) dbFetchValue(
    "SELECT id FROM popups WHERE is_active = 1
       AND (start_date IS NULL OR start_date = '0000-00-00' OR start_date <= CURDATE())
       AND (end_date   IS NULL OR end_date   = '0000-00-00' OR end_date   >= CURDATE())
     ORDER BY id ASC LIMIT 1"
);

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-content">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
    <i class="fas fa-chevron-right"></i><span>Popups</span>
  </nav>

  <div class="page-header">
    <div>
      <h1 class="page-title">Popups</h1>
      <p class="page-sub">
        A timed modal shown over the site. Only the first active popup inside its
        date window is used — the rest stay as drafts.
      </p>
    </div>
    <div class="page-header-actions">
      <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> New Popup</a>
    </div>
  </div>

  <?php if ($action === 'add' || $editing): $f = $editing ?? [];
    $on = trim((string)($f['display_on'] ?? 'all'));
    $isAll = ($on === '' || strtolower($on) === 'all');
    $sel = $isAll ? [] : array_map('trim', explode(',', strtolower($on))); ?>
  <div class="card" style="padding:22px;margin-bottom:20px;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 16px;"><?= $editing ? 'Edit popup' : 'New popup' ?></h2>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="edit_id" value="<?= (int)($f['id'] ?? 0) ?>">

      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" class="form-control" maxlength="200"
               value="<?= e($f['title'] ?? '') ?>" placeholder="Let us build it with you">
      </div>

      <div class="form-group">
        <label for="content">Message</label>
        <textarea name="content" id="content" class="form-control" rows="4"><?= e($f['content'] ?? '') ?></textarea>
        <div class="form-hint">Basic HTML allowed — &lt;p&gt;, &lt;strong&gt;, &lt;a&gt;.</div>
      </div>

      <div class="pp-g2">
        <div class="form-group">
          <label for="button_text">Button label</label>
          <input type="text" name="button_text" id="button_text" class="form-control" maxlength="120"
                 value="<?= e($f['button_text'] ?? '') ?>" placeholder="Start a conversation">
        </div>
        <div class="form-group">
          <label for="button_url">Button link</label>
          <input type="text" name="button_url" id="button_url" class="form-control" maxlength="300"
                 value="<?= e($f['button_url'] ?? '') ?>" placeholder="/contact.php#enquiry">
          <div class="form-hint">Leave both blank to show no button.</div>
        </div>
      </div>

      <div class="pp-g3">
        <div class="form-group">
          <label for="delay_seconds">Delay before showing</label>
          <div style="display:flex;align-items:center;gap:9px;">
            <input type="number" name="delay_seconds" id="delay_seconds" class="form-control"
                   min="0" max="120" style="max-width:110px;"
                   value="<?= (int)($f['delay_seconds'] ?? 5) ?>">
            <span style="font-size:13px;color:var(--muted);">seconds after page load</span>
          </div>
        </div>
        <div class="form-group">
          <label for="start_date">Start date</label>
          <input type="date" name="start_date" id="start_date" class="form-control"
                 value="<?= e($f['start_date'] ?? '') ?>">
          <div class="form-hint">Optional.</div>
        </div>
        <div class="form-group">
          <label for="end_date">End date</label>
          <input type="date" name="end_date" id="end_date" class="form-control"
                 value="<?= e($f['end_date'] ?? '') ?>">
          <div class="form-hint">Optional.</div>
        </div>
      </div>

      <div class="form-group">
        <label>Show on</label>
        <label class="pp-check" style="margin-bottom:8px;">
          <input type="checkbox" name="on_all" id="onAll" value="1" <?= $isAll ? 'checked' : '' ?>
                 onchange="ppToggleAll(this)">
          <strong>Every page</strong>
        </label>
        <div class="pp-pages" id="ppPages" <?= $isAll ? 'style="opacity:.45;pointer-events:none;"' : '' ?>>
          <?php foreach ($PAGES as $k => $lbl): ?>
          <label class="pp-check">
            <input type="checkbox" name="display_on[]" value="<?= e($k) ?>"
                   <?= in_array($k, $sel, true) ? 'checked' : '' ?>>
            <?= e($lbl) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pp-g2">
        <div class="form-group">
          <label for="image">Image</label>
          <input type="file" name="image" id="image" class="form-control" accept="image/*">
          <div class="form-hint">Optional banner above the text. Max 3MB.</div>
          <?php if (!empty($f['image'])): ?>
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
              <img src="<?= UPLOADS_URL ?>/<?= e($f['image']) ?>" alt="" style="height:52px;border-radius:8px;">
              <label class="pp-check" style="margin:0;"><input type="checkbox" name="remove_image" value="1"> Remove</label>
            </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Frequency</label>
          <label class="pp-check">
            <input type="checkbox" name="show_once" value="1" <?= (!isset($f['show_once']) || $f['show_once']) ? 'checked' : '' ?>>
            Show once per visitor
          </label>
          <div class="form-hint">
            On: remembered in the browser, so it will not reappear.
            Off: shown once per browsing session.
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="pp-check">
          <input type="checkbox" name="is_active" value="1" <?= (!isset($f['is_active']) || $f['is_active']) ? 'checked' : '' ?>>
          Active
        </label>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save popup</button>
        <a href="<?= e($backUrl) ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h3>All popups</h3><span class="badge badge-secondary"><?= count($rows) ?></span>
    </div>
    <?php if (!$rows): ?>
      <div class="empty-state">
        <i class="fas fa-window-restore"></i><h3>No popups yet</h3>
        <p>Create one and it appears over the site after the delay you set.</p>
        <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> New Popup</a>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="table"><thead><tr>
        <th>Popup</th><th>Shows on</th><th style="width:90px;">Delay</th>
        <th style="width:110px;">Frequency</th><th style="width:120px;">Status</th>
        <th style="text-align:right;width:130px;">Actions</th>
      </tr></thead><tbody>
        <?php foreach ($rows as $r): $live = ((int)$r['id'] === $liveId); ?>
        <tr>
          <td>
            <strong><?= e($r['title'] ?: '(untitled)') ?></strong>
            <?php if ($live): ?>
              <span class="badge badge-success" style="margin-left:6px;">Live</span>
            <?php endif; ?>
            <?php if ($r['button_text']): ?>
              <div style="font-size:12px;color:var(--muted);"><?= e($r['button_text']) ?> &rarr; <?= e($r['button_url']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:13px;"><?= e($r['display_on'] === 'all' ? 'Every page' : $r['display_on']) ?></td>
          <td><?= (int)$r['delay_seconds'] ?>s</td>
          <td style="font-size:13px;"><?= $r['show_once'] ? 'Once' : 'Per session' ?></td>
          <td>
            <span class="badge <?= $r['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
              <?= $r['is_active'] ? 'Active' : 'Draft' ?>
            </span>
          </td>
          <td style="text-align:right;white-space:nowrap;">
            <a href="?action=edit&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Edit"><i class="fas fa-pen"></i></a>
            <button type="submit" form="pT<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Toggle"><i class="fas fa-<?= $r['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
            <button type="submit" form="pD<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm btn-icon" aria-label="Delete"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody></table>
      </div>
    <?php endif; ?>
  </div>

  <?php foreach ($rows as $r): ?>
    <form id="pT<?= (int)$r['id'] ?>" method="POST" style="display:none;"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></form>
    <form id="pD<?= (int)$r['id'] ?>" method="POST" style="display:none;" onsubmit="return confirm('Delete this popup?');"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></form>
  <?php endforeach; ?>
</div>

<style>
.pp-g2{ display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
.pp-g3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 18px; }
@media (max-width:860px){ .pp-g2,.pp-g3{ grid-template-columns:1fr; } }
.pp-check{
  display:inline-flex; align-items:center; gap:9px;
  text-transform:none; letter-spacing:0; font-weight:500;
  font-size:13.5px; color:var(--ink-2); cursor:pointer; margin:0;
}
.pp-pages{
  display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
  gap:9px; padding:12px 14px; border:1px solid var(--line); border-radius:var(--r-sm);
  background:var(--surface-2);
}
</style>

<script>
function ppToggleAll(cb) {
  var box = document.getElementById('ppPages');
  box.style.opacity = cb.checked ? '.45' : '1';
  box.style.pointerEvents = cb.checked ? 'none' : 'auto';
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
