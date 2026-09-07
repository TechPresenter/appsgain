<?php
/**
 * Admin — Founder Page.
 *
 * Everything on /our-founder.php in one screen, split three ways:
 *
 *   Profile   identity, portrait, contact — the founder_* settings
 *   Sections  the page blocks: bio, philosophy, achievements, education,
 *             FAQs, quote — add / edit / delete / reorder / show / hide
 *   Gallery   photographs with caption and alt text
 *
 * Tokens {name} {company} {since} {email} {education} expand at render
 * time, so copy written once stays correct when the details change.
 */
$adminTitle = 'Founder Page';
$adminPage  = 'founder';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$LAYOUTS = [
    'prose'   => 'Prose — headings and paragraphs',
    'chips'   => 'Chips — pill list of interests',
    'path'    => 'Path — arrow-linked steps',
    'cards'   => 'Cards — achievement grid with icons',
    'list'    => 'List — ticked bullet points',
    'quote'   => 'Quote — prose plus a pull-quote',
    'faq'     => 'FAQ — question / answer accordion',
    'gallery' => 'Gallery — pulls from the Gallery tab',
];
$TABS = [
    'profile'  => ['Profile',  'fa-id-card'],
    'sections' => ['Sections', 'fa-layer-group'],
    'gallery'  => ['Gallery',  'fa-images'],
];

$tab     = isset($_GET['tab']) && isset($TABS[$_GET['tab']]) ? $_GET['tab'] : 'profile';
$action  = sanitizeInput($_GET['action'] ?? 'list');
$editId  = (int)($_GET['id'] ?? 0);
$backUrl = ADMIN_URL . '/pages/founder.php?tab=' . $tab;

/** Shared image handling: verified MIME, 4MB cap, SVG sanitised upstream. */
$saveImage = static function (string $field, ?string $current, string $folder = 'team'): array {
    if (empty($_FILES[$field]['name'])) return ['ok' => true, 'path' => null];
    $f = $_FILES[$field];
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK)     return ['ok' => false, 'err' => 'Upload failed.'];
    if ($f['size'] > 4 * 1024 * 1024)             return ['ok' => false, 'err' => 'Image must be 4MB or smaller.'];
    $mime = @mime_content_type($f['tmp_name']) ?: '';
    if (!in_array($mime, ['image/png','image/jpeg','image/webp','image/gif'], true)) {
        return ['ok' => false, 'err' => 'Image must be PNG, JPG, WebP or GIF.'];
    }
    $up = uploadFile($f, $folder);
    if (!$up['success']) return ['ok' => false, 'err' => $up['error']];
    if ($current) deleteFile($current);
    return ['ok' => true, 'path' => $up['path']];
};

/* ══ Actions ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $post = $_POST['action'] ?? '';

    /* ── Profile ── */
    if ($post === 'profile') {
        foreach (['founder_name','founder_role','founder_email','founder_linkedin',
                  'founder_since','founder_education'] as $k) {
            saveSetting($k, sanitizeInput($_POST[$k] ?? ''), 'business');
        }
        $cur = getSetting('founder_photo', '');
        $r = $saveImage('founder_photo_file', $cur ?: null);
        if (!$r['ok']) { setFlash('error', $r['err']); redirect($backUrl); }
        if ($r['path'] !== null)                 saveSetting('founder_photo', $r['path'], 'business');
        elseif (!empty($_POST['remove_photo']))  { if ($cur) deleteFile($cur); saveSetting('founder_photo', '', 'business'); }
        elseif (trim((string)($_POST['founder_photo_path'] ?? '')) !== '') {
            saveSetting('founder_photo', sanitizeInput($_POST['founder_photo_path']), 'business');
        }
        logActivity('update', 'founder', 'Updated founder profile');
        setFlash('success', 'Profile saved.');
        redirect($backUrl);
    }

    /* ── Sections ── */
    if ($post === 'sec_save') {
        $items = [];
        foreach (($_POST['item_title'] ?? []) as $i => $t) {
            $t  = trim((string)$t);
            $tx = trim((string)(($_POST['item_text'] ?? [])[$i] ?? ''));
            if ($t === '' && $tx === '') continue;
            $items[] = [
                'title' => sanitizeInput($t),
                'icon'  => sanitizeInput(trim((string)(($_POST['item_icon'] ?? [])[$i] ?? ''))),
                'text'  => sanitizeInput($tx),
            ];
        }
        /* Malformed UTF-8 makes json_encode return false; writing NULL then
           would silently wipe everything the admin just typed. */
        $itemsJson = null;
        if ($items) {
            $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($itemsJson === false) {
                setFlash('error', 'Those items contained characters we could not read. Nothing was changed.');
                redirect($backUrl);
            }
        }

        $id   = (int)($_POST['edit_id'] ?? 0);
        $data = [
            'section_key' => slugify($_POST['section_key'] ?? '') ?: 'section',
            'layout'      => array_key_exists($_POST['layout'] ?? '', $LAYOUTS) ? $_POST['layout'] : 'prose',
            'heading'     => sanitizeInput($_POST['heading'] ?? ''),
            'subheading'  => sanitizeInput($_POST['subheading'] ?? ''),
            'body'        => $_POST['body'] ?? '',   /* trusted admin HTML */
            'items'       => $itemsJson,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        $cur = $id ? dbFetchValue("SELECT image FROM founder_sections WHERE id = ?", [$id]) : null;
        $r = $saveImage('image', $cur ?: null);
        if (!$r['ok']) { setFlash('error', $r['err']); redirect($backUrl); }
        if ($r['path'] !== null)               $data['image'] = $r['path'];
        elseif (!empty($_POST['remove_image'])) { if ($cur) deleteFile($cur); $data['image'] = ''; }

        if ($id) {
            dbUpdateRow('founder_sections', $data, 'id = ?', [$id]);
            setFlash('success', 'Section updated.');
        } else {
            if (!$data['sort_order']) {
                $data['sort_order'] = (int) dbFetchValue("SELECT COALESCE(MAX(sort_order),0) + 10 FROM founder_sections");
            }
            dbInsertRow('founder_sections', $data);
            setFlash('success', 'Section added.');
        }
        logActivity('update', 'founder', 'Saved section: ' . $data['section_key']);
        redirect($backUrl);
    }

    if ($post === 'sec_delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $img = dbFetchValue("SELECT image FROM founder_sections WHERE id = ?", [$id]);
        dbExecute("DELETE FROM founder_sections WHERE id = ?", [$id]);
        if ($img) deleteFile($img);
        setFlash('success', 'Section deleted.');
        redirect($backUrl);
    }
    if ($post === 'sec_toggle') {
        dbExecute("UPDATE founder_sections SET is_active = 1 - is_active WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        redirect($backUrl);
    }
    if ($post === 'sec_reorder') {
        $i = 10;
        foreach (array_filter(array_map('intval', explode(',', (string)($_POST['order'] ?? '')))) as $id) {
            dbExecute("UPDATE founder_sections SET sort_order = ? WHERE id = ?", [$i, $id]); $i += 10;
        }
        setFlash('success', 'Order saved.');
        redirect($backUrl);
    }

    /* ── Gallery ── */
    if ($post === 'gal_add') {
        $r = $saveImage('image', null, 'team');
        if (!$r['ok'])        { setFlash('error', $r['err']); redirect($backUrl); }
        if ($r['path'] === null) { setFlash('error', 'Choose an image to upload.'); redirect($backUrl); }
        dbInsertRow('founder_gallery', [
            'image'      => $r['path'],
            'caption'    => sanitizeInput($_POST['caption'] ?? ''),
            'alt_text'   => sanitizeInput($_POST['alt_text'] ?? ''),
            'sort_order' => (int) dbFetchValue("SELECT COALESCE(MAX(sort_order),0) + 10 FROM founder_gallery"),
            'is_active'  => 1,
        ]);
        setFlash('success', 'Photo added.');
        redirect($backUrl);
    }
    if ($post === 'gal_update') {
        dbUpdateRow('founder_gallery', [
            'caption'  => sanitizeInput($_POST['caption'] ?? ''),
            'alt_text' => sanitizeInput($_POST['alt_text'] ?? ''),
        ], 'id = ?', [(int)($_POST['id'] ?? 0)]);
        setFlash('success', 'Photo updated.');
        redirect($backUrl);
    }
    if ($post === 'gal_delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $img = dbFetchValue("SELECT image FROM founder_gallery WHERE id = ?", [$id]);
        dbExecute("DELETE FROM founder_gallery WHERE id = ?", [$id]);
        if ($img) deleteFile($img);
        setFlash('success', 'Photo deleted.');
        redirect($backUrl);
    }
    if ($post === 'gal_toggle') {
        dbExecute("UPDATE founder_gallery SET is_active = 1 - is_active WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        redirect($backUrl);
    }
}

/* ══ View data ══ */
$s        = getAllSettings();
$sections = dbFetchAll("SELECT * FROM founder_sections ORDER BY sort_order ASC, id ASC");
$gallery  = dbFetchAll("SELECT * FROM founder_gallery ORDER BY sort_order ASC, id ASC");
$editing  = null;
if ($tab === 'sections' && $action === 'edit' && $editId) {
    $editing = dbFetchRow("SELECT * FROM founder_sections WHERE id = ?", [$editId]);
    if (!$editing) { setFlash('error', 'Section not found.'); redirect($backUrl); }
}
$fPhoto = (string)($s['founder_photo'] ?? '');

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-content">

  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <span>Founder Page</span>
  </nav>

  <div class="page-header">
    <div>
      <h1 class="page-title">Founder Page</h1>
      <p class="page-sub">
        Everything on <a href="<?= SITE_URL ?>/our-founder.php" target="_blank" rel="noopener"
        style="color:var(--brand);font-weight:600;">/our-founder.php</a>.
        Reachable from the HTML sitemap only — intentionally not in the site navigation.
      </p>
    </div>
    <div class="page-header-actions">
      <a href="<?= SITE_URL ?>/our-founder.php" target="_blank" rel="noopener" class="btn btn-secondary">
        <i class="fas fa-arrow-up-right-from-square"></i> View page
      </a>
      <?php if ($tab === 'sections'): ?>
        <a href="?tab=sections&action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Section</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="fd-tabs" role="tablist">
    <?php foreach ($TABS as $k => [$label, $ico]): ?>
    <a role="tab" aria-selected="<?= $tab === $k ? 'true' : 'false' ?>"
       class="fd-tab <?= $tab === $k ? 'is-on' : '' ?>" href="?tab=<?= e($k) ?>">
      <i class="fas <?= e($ico) ?>" aria-hidden="true"></i> <?= e($label) ?>
      <?php if ($k === 'sections'): ?><span><?= count($sections) ?></span><?php endif; ?>
      <?php if ($k === 'gallery'):  ?><span><?= count($gallery) ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php /* ══════════ PROFILE ══════════ */ if ($tab === 'profile'): ?>
  <div class="card" style="padding:22px;">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="profile">

      <div class="fd-profile">
        <div>
          <div class="fd-g2">
            <div class="form-group">
              <label for="founder_name">Full name</label>
              <input type="text" name="founder_name" id="founder_name" class="form-control" maxlength="120"
                     value="<?= e($s['founder_name'] ?? '') ?>" placeholder="Prashant Kumar">
            </div>
            <div class="form-group">
              <label for="founder_since">Founded year</label>
              <input type="text" name="founder_since" id="founder_since" class="form-control" maxlength="4"
                     value="<?= e($s['founder_since'] ?? '') ?>" placeholder="2018">
            </div>
          </div>
          <div class="form-group">
            <label for="founder_role">Role / title</label>
            <input type="text" name="founder_role" id="founder_role" class="form-control" maxlength="160"
                   value="<?= e($s['founder_role'] ?? '') ?>"
                   placeholder="Founder &amp; CEO, Appsgain Technologies Private Limited">
          </div>
          <div class="fd-g2">
            <div class="form-group">
              <label for="founder_email">Email</label>
              <input type="email" name="founder_email" id="founder_email" class="form-control" maxlength="160"
                     value="<?= e($s['founder_email'] ?? '') ?>" placeholder="info@appsgain.in">
            </div>
            <div class="form-group">
              <label for="founder_linkedin">LinkedIn URL</label>
              <input type="url" name="founder_linkedin" id="founder_linkedin" class="form-control" maxlength="300"
                     value="<?= e($s['founder_linkedin'] ?? '') ?>"
                     placeholder="https://www.linkedin.com/in/prashantdevtech">
            </div>
          </div>
          <div class="form-group">
            <label for="founder_education">Education</label>
            <input type="text" name="founder_education" id="founder_education" class="form-control" maxlength="200"
                   value="<?= e($s['founder_education'] ?? '') ?>"
                   placeholder="Babasaheb Bhimrao Ambedkar Bihar University">
          </div>
        </div>

        <div>
          <div class="form-group">
            <label>Portrait</label>
            <div class="fd-portrait">
              <?php if ($fPhoto !== ''): ?>
                <img id="fdPortrait" src="<?= UPLOADS_URL ?>/<?= e($fPhoto) ?>" alt="Founder portrait">
              <?php else: ?>
                <span class="fd-portrait-blank" id="fdPortraitBlank">
                  <i class="fas fa-user" aria-hidden="true"></i>
                  <small>No portrait yet</small>
                </span>
                <img id="fdPortrait" src="" alt="" hidden>
              <?php endif; ?>
            </div>
            <input type="file" name="founder_photo_file" class="form-control" accept="image/*"
                   onchange="fdPreview(this)" style="margin-top:10px;">
            <div class="form-hint">
              JPG, PNG, WebP or GIF. Max 4MB. Portrait orientation (4:5) fits the frame best.
              Suggested filename: <code>prashant-kumar-founder-appsgain-technologies.webp</code>
            </div>
            <?php if ($fPhoto !== ''): ?>
              <label class="fd-check" style="margin-top:8px;">
                <input type="checkbox" name="remove_photo" value="1"> Remove portrait
              </label>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="founder_photo_path">…or an existing path</label>
            <input type="text" name="founder_photo_path" id="founder_photo_path" class="form-control"
                   maxlength="300" placeholder="team/prashant-kumar.webp">
            <div class="form-hint">Leave blank unless referencing a file already in /uploads.</div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save profile</button>
    </form>
  </div>

  <?php /* ══════════ SECTIONS ══════════ */ elseif ($tab === 'sections'): ?>

    <?php if ($action === 'add' || $editing): $f = $editing ?? [];
      $its = $editing ? (json_decode((string)$editing['items'], true) ?: []) : []; ?>
    <div class="card" style="padding:22px;margin-bottom:20px;">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;"><?= $editing ? 'Edit section' : 'New section' ?></h2>
      <p style="font-size:12.5px;color:var(--muted);margin:0 0 16px;">
        Tokens <code>{name}</code> <code>{company}</code> <code>{since}</code>
        <code>{email}</code> <code>{education}</code> expand automatically.
      </p>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="sec_save">
        <input type="hidden" name="edit_id" value="<?= (int)($f['id'] ?? 0) ?>">

        <div class="fd-g2">
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
            <input type="text" name="section_key" id="section_key" class="form-control" required
                   value="<?= e($f['section_key'] ?? '') ?>" placeholder="e.g. milestones">
            <div class="form-hint">Used as the anchor id and the sidebar link.</div>
          </div>
        </div>

        <div class="form-group">
          <label for="heading">Heading</label>
          <input type="text" name="heading" id="heading" class="form-control" maxlength="200"
                 value="<?= e($f['heading'] ?? '') ?>" placeholder="Achievements &amp; recognition">
        </div>
        <div class="form-group">
          <label for="subheading">Sub-heading <span style="text-transform:none;font-weight:500;color:var(--muted)">— the quote text for a Quote layout</span></label>
          <input type="text" name="subheading" id="subheading" class="form-control" maxlength="300"
                 value="<?= e($f['subheading'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="body">Body</label>
          <textarea name="body" id="body" class="form-control" rows="9"><?= e($f['body'] ?? '') ?></textarea>
          <div class="form-hint">HTML allowed — &lt;p&gt;, &lt;h3&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;a&gt;.</div>
        </div>

        <div class="form-group">
          <label>Items <span style="text-transform:none;font-weight:500;color:var(--muted)">— chips, steps, cards, bullets or FAQs</span></label>
          <div id="fdItems">
            <?php foreach (($its ?: [['title'=>'','icon'=>'','text'=>'']]) as $r): ?>
            <div class="fd-item">
              <input type="text" name="item_title[]" class="form-control" placeholder="Title / question" value="<?= e($r['title'] ?? '') ?>">
              <input type="text" name="item_icon[]"  class="form-control" placeholder="fa-trophy" value="<?= e($r['icon'] ?? '') ?>">
              <input type="text" name="item_text[]"  class="form-control" placeholder="Description / answer" value="<?= e($r['text'] ?? '') ?>">
              <button type="button" class="btn btn-secondary btn-sm btn-icon" onclick="this.closest('.fd-item').remove()" aria-label="Remove"><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="fdAddItem()"><i class="fas fa-plus"></i> Add item</button>
        </div>

        <div class="fd-g2">
          <div class="form-group">
            <label for="image">Section image</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
            <?php if (!empty($f['image'])): ?>
              <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                <img src="<?= UPLOADS_URL ?>/<?= e($f['image']) ?>" alt="" style="height:52px;border-radius:8px;">
                <label class="fd-check" style="margin:0;"><input type="checkbox" name="remove_image" value="1"> Remove</label>
              </div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="sort_order">Order</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= (int)($f['sort_order'] ?? 0) ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="fd-check">
            <input type="checkbox" name="is_active" value="1" <?= (!isset($f['is_active']) || $f['is_active']) ? 'checked' : '' ?>>
            Show this section on the page
          </label>
        </div>

        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save section</button>
          <a href="<?= e($backUrl) ?>" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3>Page sections</h3>
        <span class="badge badge-secondary"><?= count($sections) ?></span>
      </div>
      <?php if (!$sections): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i><h3>No sections yet</h3>
          <p>Add the first block and it appears on the founder page.</p>
          <a href="?tab=sections&action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Section</a>
        </div>
      <?php else: ?>
        <p class="fd-hint"><i class="fas fa-up-down-left-right"></i> Drag a row to reorder — saved automatically.</p>
        <div style="overflow-x:auto;">
        <table class="table"><thead><tr>
          <th style="width:44px;"></th><th>Section</th><th>Layout</th>
          <th style="width:70px;">Items</th><th style="width:96px;">Status</th>
          <th style="text-align:right;width:130px;">Actions</th>
        </tr></thead>
        <tbody id="fdBody">
          <?php foreach ($sections as $r): $n = count(json_decode((string)$r['items'], true) ?: []); ?>
          <tr draggable="true" data-id="<?= (int)$r['id'] ?>">
            <td><span class="fd-handle"><i class="fas fa-grip-vertical"></i></span></td>
            <td>
              <strong><?= strip_tags((string)$r['heading']) ?: e($r['section_key']) ?></strong>
              <div style="font-size:12px;color:var(--muted);"><code><?= e($r['section_key']) ?></code></div>
            </td>
            <td style="font-size:13px;"><?= e($LAYOUTS[$r['layout']] ?? $r['layout']) ?></td>
            <td><?= $n ?: '—' ?></td>
            <td><span class="badge <?= $r['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $r['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
            <td style="text-align:right;white-space:nowrap;">
              <a href="?tab=sections&action=edit&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Edit"><i class="fas fa-pen"></i></a>
              <button type="submit" form="fdT<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Toggle"><i class="fas fa-<?= $r['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
              <button type="submit" form="fdD<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm btn-icon" aria-label="Delete"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody></table>
        </div>
      <?php endif; ?>
    </div>

    <?php foreach ($sections as $r): ?>
      <form id="fdT<?= (int)$r['id'] ?>" method="POST" style="display:none;"><?= csrfField() ?><input type="hidden" name="action" value="sec_toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></form>
      <form id="fdD<?= (int)$r['id'] ?>" method="POST" style="display:none;" onsubmit="return confirm('Delete this section? This cannot be undone.');"><?= csrfField() ?><input type="hidden" name="action" value="sec_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></form>
    <?php endforeach; ?>
    <form id="fdOrder" method="POST" style="display:none;"><?= csrfField() ?><input type="hidden" name="action" value="sec_reorder"><input type="hidden" name="order" id="fdOrderVal"></form>

  <?php /* ══════════ GALLERY ══════════ */ else: ?>
  <div class="card" style="padding:22px;margin-bottom:20px;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;">Add a photo</h2>
    <p style="font-size:12.5px;color:var(--muted);margin:0 0 16px;">
      Photos appear in the Gallery section of the founder page.
      That section starts hidden — enable it under <a href="?tab=sections" style="color:var(--brand);font-weight:600;">Sections</a> once you have added a few.
    </p>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="gal_add">
      <div class="fd-g3">
        <div class="form-group">
          <label for="gimg">Image</label>
          <input type="file" name="image" id="gimg" class="form-control" accept="image/*" required>
          <div class="form-hint">JPG, PNG, WebP or GIF. Max 4MB.</div>
        </div>
        <div class="form-group">
          <label for="gcap">Caption</label>
          <input type="text" name="caption" id="gcap" class="form-control" maxlength="300" placeholder="Shown under the photo">
        </div>
        <div class="form-group">
          <label for="galt">Alt text</label>
          <input type="text" name="alt_text" id="galt" class="form-control" maxlength="300" placeholder="Describes the photo for screen readers">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload photo</button>
    </form>
  </div>

  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h3>Photos</h3><span class="badge badge-secondary"><?= count($gallery) ?></span>
    </div>
    <?php if (!$gallery): ?>
      <div class="empty-state">
        <i class="fas fa-images"></i><h3>No photos yet</h3>
        <p>Upload the first photograph using the form above.</p>
      </div>
    <?php else: ?>
      <div class="fd-gal">
        <?php foreach ($gallery as $g): ?>
        <div class="fd-shot <?= $g['is_active'] ? '' : 'is-off' ?>">
          <img src="<?= UPLOADS_URL ?>/<?= e($g['image']) ?>" alt="<?= e($g['alt_text']) ?>">
          <form method="POST" class="fd-shot-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="gal_update">
            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
            <input type="text" name="caption"  class="form-control" placeholder="Caption"  value="<?= e($g['caption']) ?>">
            <input type="text" name="alt_text" class="form-control" placeholder="Alt text" value="<?= e($g['alt_text']) ?>">
            <div class="fd-shot-acts">
              <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save</button>
              <button type="submit" form="gT<?= (int)$g['id'] ?>" class="btn btn-outline btn-sm btn-icon" aria-label="Toggle"><i class="fas fa-<?= $g['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
              <button type="submit" form="gD<?= (int)$g['id'] ?>" class="btn btn-danger btn-sm btn-icon" aria-label="Delete"><i class="fas fa-trash"></i></button>
            </div>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php foreach ($gallery as $g): ?>
    <form id="gT<?= (int)$g['id'] ?>" method="POST" style="display:none;"><?= csrfField() ?><input type="hidden" name="action" value="gal_toggle"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"></form>
    <form id="gD<?= (int)$g['id'] ?>" method="POST" style="display:none;" onsubmit="return confirm('Delete this photo permanently?');"><?= csrfField() ?><input type="hidden" name="action" value="gal_delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"></form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<style>
.fd-tabs{ display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }
.fd-tab{
  display:inline-flex; align-items:center; gap:9px;
  height:40px; padding:0 16px; border-radius:var(--r-sm);
  background:var(--surface); border:1px solid var(--line);
  color:var(--body); font-size:13.5px; font-weight:600; text-decoration:none;
  transition:border-color .18s ease, color .18s ease, background .18s ease;
}
.fd-tab:hover{ border-color:var(--brand-line); color:var(--brand); }
.fd-tab.is-on{ background-color:var(--brand-solid); background-image:var(--ag-grad-btn); border-color:transparent; color:#fff; }
.fd-tab span{ padding:1px 8px; border-radius:999px; font-size:11.5px; font-weight:700; background:var(--surface-3); color:var(--muted); }
.fd-tab.is-on span{ background:rgba(255,255,255,.24); color:#fff; }

.fd-g2{ display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
.fd-g3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 18px; }
.fd-profile{ display:grid; grid-template-columns:1fr 300px; gap:0 26px; }
@media (max-width:900px){ .fd-g2,.fd-g3,.fd-profile{ grid-template-columns:1fr; } }

.fd-check{ display:inline-flex; align-items:center; gap:9px; text-transform:none; letter-spacing:0; font-weight:500; font-size:13.5px; color:var(--ink-2); cursor:pointer; }
.fd-item{ display:grid; grid-template-columns:1.2fr 130px 2fr auto; gap:8px; margin-bottom:8px; }
@media (max-width:820px){ .fd-item{ grid-template-columns:1fr; } }
.fd-hint{ display:flex; align-items:center; gap:8px; margin:0; padding:10px 16px; font-size:12.5px; color:var(--muted); border-bottom:1px solid var(--line); }
.fd-handle{ cursor:grab; color:var(--faint); }
#fdBody tr.is-drag{ opacity:.45; }
#fdBody tr.is-over{ box-shadow:inset 0 2px 0 var(--brand); }

.fd-portrait{
  width:100%; aspect-ratio:4/5; border-radius:var(--r-md); overflow:hidden;
  background:var(--surface-3); border:1px solid var(--line);
  display:grid; place-items:center;
}
.fd-portrait img{ width:100%; height:100%; object-fit:cover; }
.fd-portrait-blank{ display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--faint); }
.fd-portrait-blank i{ font-size:30px; }
.fd-portrait-blank small{ font-size:12px; }

.fd-gal{ display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:16px; padding:16px; }
.fd-shot{ border:1px solid var(--line); border-radius:var(--r-md); overflow:hidden; background:var(--surface); }
.fd-shot.is-off{ opacity:.55; }
.fd-shot img{ width:100%; aspect-ratio:4/3; object-fit:cover; display:block; }
.fd-shot-form{ padding:11px; display:flex; flex-direction:column; gap:8px; }
.fd-shot-acts{ display:flex; gap:6px; }
</style>

<script>
function fdPreview(input) {
  var f = input.files && input.files[0];
  if (!f) return;
  if (f.size > 4 * 1024 * 1024) { alert('Image must be 4MB or smaller.'); input.value = ''; return; }
  var img = document.getElementById('fdPortrait');
  var blank = document.getElementById('fdPortraitBlank');
  img.src = URL.createObjectURL(f);
  img.hidden = false;
  if (blank) blank.style.display = 'none';
}

function fdAddItem() {
  var wrap = document.getElementById('fdItems');
  var row = document.createElement('div');
  row.className = 'fd-item';
  row.innerHTML =
    '<input type="text" name="item_title[]" class="form-control" placeholder="Title / question">' +
    '<input type="text" name="item_icon[]"  class="form-control" placeholder="fa-trophy">' +
    '<input type="text" name="item_text[]"  class="form-control" placeholder="Description / answer">' +
    '<button type="button" class="btn btn-secondary btn-sm btn-icon" aria-label="Remove"><i class="fas fa-trash"></i></button>';
  row.querySelector('button').addEventListener('click', function () { row.remove(); });
  wrap.appendChild(row);
  row.querySelector('input').focus();
}

/* Drag to reorder sections */
(function () {
  var body = document.getElementById('fdBody');
  if (!body) return;
  var dragged = null;
  body.addEventListener('dragstart', function (e) {
    var tr = e.target.closest('tr'); if (!tr) return;
    dragged = tr; tr.classList.add('is-drag');
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', tr.dataset.id); } catch (err) {}
  });
  body.addEventListener('dragover', function (e) {
    e.preventDefault();
    var tr = e.target.closest('tr'); if (!tr || tr === dragged) return;
    body.querySelectorAll('.is-over').forEach(function (x) { x.classList.remove('is-over'); });
    tr.classList.add('is-over');
    var b = tr.getBoundingClientRect();
    body.insertBefore(dragged, (e.clientY - b.top) > b.height / 2 ? tr.nextSibling : tr);
  });
  body.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('is-drag');
    body.querySelectorAll('.is-over').forEach(function (x) { x.classList.remove('is-over'); });
    dragged = null;
    document.getElementById('fdOrderVal').value =
      Array.prototype.map.call(body.querySelectorAll('tr'), function (tr) { return tr.dataset.id; }).join(',');
    document.getElementById('fdOrder').submit();
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
