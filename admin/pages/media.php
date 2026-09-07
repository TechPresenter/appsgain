<?php
$adminTitle = 'Media Library';
$adminPage  = 'media';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* ── AJAX Upload ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
  verifyCsrf();
  $folder = sanitizeInput($_POST['folder'] ?? 'general') ?: 'general';
  $alt    = sanitizeInput($_POST['alt'] ?? '');
  $upload = uploadFile($_FILES['file'], 'library');
  if ($upload['success']) {
    $size = filesize(ROOT_PATH . '/uploads/' . $upload['path']);
    [$w, $h] = @getimagesize(ROOT_PATH . '/uploads/' . $upload['path']) ?: [null, null];
    $mediaId = dbInsertRow('media', [
      'filename'      => basename($upload['path']),
      'original_name' => $upload['original_name'],
      'file_path'     => $upload['path'],
      'file_url'      => $upload['file_url'],
      'file_type'     => $upload['file_type'],
      'mime_type'     => $upload['mime_type'],
      'file_size'     => $size,
      'width'         => $w,
      'height'        => $h,
      'alt_text'      => $alt,
      'folder'        => $folder,
      'uploaded_by'   => $admin['id'],
    ]);
    logActivity('create', 'media', 'Uploaded file: ' . basename($upload['path']));
    header('Content-Type:application/json');
    echo json_encode([
      'ok'      => true,
      'id'      => $mediaId,
      'url'     => getImageUrl($upload['path']),
      'path'    => $upload['path'],
      'name'    => $upload['filename'],
      'alt'     => $alt,
      'folder'  => $folder,
      'mime'    => $upload['mime_type'],
      'type'    => $upload['file_category'],
      'size'    => $size,
      'width'   => $w,
      'height'  => $h,
    ]);
    exit;
  }
  http_response_code(400); header('Content-Type:application/json'); echo json_encode(['ok'=>false,'error'=>$upload['error'] ?? 'Upload failed']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'delete') {
    $id  = (int)$_POST['id'];
    $med = dbFetchOne("SELECT file_path FROM media WHERE id = ?", [$id]);
    if ($med) {
      deleteFile($med['file_path']);
      dbExecute("DELETE FROM media WHERE id = ?", [$id]);
      logActivity('delete', 'media', "Deleted media #{$id}");
      setFlash('success', 'File deleted.');
    }
    redirect(ADMIN_URL . '/pages/media.php');
  }

  if ($action === 'update_meta') {
    $id      = (int)$_POST['id'];
    $alt     = sanitizeInput($_POST['alt_text'] ?? '');
    $caption = sanitizeInput($_POST['caption'] ?? '');
    $folder  = sanitizeInput($_POST['folder'] ?? 'general') ?: 'general';
    dbExecute("UPDATE media SET alt_text = ?, caption = ?, folder = ? WHERE id = ?", [$alt, $caption, $folder, $id]);
    logActivity('update', 'media', "Updated metadata for media #{$id}");
    jsonResponse(true, 'Media metadata updated.', ['id' => $id, 'alt_text' => $alt, 'caption' => $caption, 'folder' => $folder]);
  }

  if ($action === 'replace') {
    $id       = (int)$_POST['id'];
    $media    = dbFetchOne("SELECT * FROM media WHERE id = ?", [$id]);
    if (!$media) {
      jsonResponse(false, 'Media item not found.', [], 404);
    }
    if (empty($_FILES['replace_file']['name'])) {
      jsonResponse(false, 'Please select a replacement file.', [], 400);
    }
    $folder   = sanitizeInput($_POST['folder'] ?? $media['folder'] ?? 'general') ?: 'general';
    $alt      = sanitizeInput($_POST['alt_text'] ?? $media['alt_text'] ?? '');
    $caption  = sanitizeInput($_POST['caption'] ?? $media['caption'] ?? '');
    $upload   = uploadFile($_FILES['replace_file'], 'library');
    if (!$upload['success']) {
      jsonResponse(false, $upload['error'] ?? 'Replace upload failed', [], 400);
    }
    deleteFile($media['file_path']);
    $size = filesize(ROOT_PATH . '/uploads/' . $upload['path']);
    [$w, $h] = @getimagesize(ROOT_PATH . '/uploads/' . $upload['path']) ?: [null, null];
    dbExecute(
      "UPDATE media SET filename = ?, original_name = ?, file_path = ?, file_url = ?, file_type = ?, mime_type = ?, file_size = ?, width = ?, height = ?, alt_text = ?, caption = ?, folder = ? WHERE id = ?",
      [basename($upload['path']), $upload['original_name'], $upload['path'], $upload['file_url'], $upload['file_type'], $upload['mime_type'], $size, $w, $h, $alt, $caption, $folder, $id]
    );
    logActivity('update', 'media', "Replaced file for media #{$id}");
    jsonResponse(true, 'File replaced successfully.', ['id' => $id, 'url' => $upload['file_url'], 'path' => $upload['path'], 'alt' => $alt, 'caption' => $caption, 'folder' => $folder, 'mime' => $upload['mime_type'], 'type' => $upload['file_category'], 'size' => $size, 'width' => $w, 'height' => $h]);
  }
}

$folders = dbFetchAll("SELECT DISTINCT COALESCE(NULLIF(folder,''),'general') AS folder FROM media ORDER BY folder ASC");

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search'] ?? '');
$type    = sanitizeInput($_GET['type'] ?? '');
$folder  = sanitizeInput($_GET['folder'] ?? '');

$where  = [];
$params = [];
if ($search) {
  $where[] = "(filename LIKE ? OR alt_text LIKE ? OR caption LIKE ? OR folder LIKE ?)";
  $like = "%{$search}%";
  $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($type) {
  if ($type === 'image') {
    $where[] = "file_type LIKE ?";
    $params[] = 'image/%';
  } elseif ($type === 'video') {
    $where[] = "file_type LIKE ?";
    $params[] = 'video/%';
  } elseif ($type === 'document') {
    $where[] = "file_type NOT LIKE 'image/%' AND file_type NOT LIKE 'video/%'";
  }
}
if ($folder) {
  $where[] = "folder = ?";
  $params[] = $folder;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = (int) dbFetchValue("SELECT COUNT(*) FROM media {$whereSQL}", $params);
$files      = dbFetchAll("SELECT * FROM media {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$pagination = paginate($total, $perPage, $page, ADMIN_URL . '/pages/media.php');

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px}
.media-item{background:#fff;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;transition:var(--transition);cursor:pointer;position:relative}
.media-item:hover{border-color:var(--violet);box-shadow:var(--shadow-hover)}
.media-item .thumb{width:100%;aspect-ratio:1;object-fit:cover}
.media-item .thumb-doc{width:100%;aspect-ratio:1;background:var(--light);display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--gray)}
.media-item .info{padding:8px 10px;border-top:1px solid var(--border)}
.media-item .info .name{font-size:11.5px;font-weight:600;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.media-item .info .meta{font-size:11px;color:var(--gray)}
.media-item .del-btn{position:absolute;top:6px;right:6px;width:24px;height:24px;background:var(--rose);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer;opacity:0;transition:.2s;border:none}
.media-item:hover .del-btn{opacity:1}
.drop-zone{border:2.5px dashed var(--border);border-radius:14px;padding:40px;text-align:center;transition:.3s;cursor:pointer;background:rgba(124,58,237,.015)}
.drop-zone.dragover{border-color:var(--violet);background:rgba(124,58,237,.05)}
.media-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.78);padding:24px;z-index:2000}
.media-modal.open{display:flex}
.media-modal-card{position:relative;width:100%;max-width:960px;background:#fff;border-radius:18px;box-shadow:0 32px 90px rgba(15,23,42,.28);overflow:hidden;display:grid;grid-template-columns:1fr 360px;max-height:90vh}
.media-modal-card .modal-sidebar{padding:24px;overflow:auto;border-left:1px solid rgba(15,23,42,.06);background:#fff}
.media-modal-card .modal-preview{padding:24px;background:var(--light);display:flex;align-items:center;justify-content:center;min-height:280px}
.media-modal-card .modal-preview img{max-width:100%;max-height:100%;border-radius:16px;object-fit:contain}
.media-modal-card .modal-meta{display:grid;gap:14px}
.media-modal-card .close-btn{position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:none;background:rgba(15,23,42,.08);color:var(--dark);font-size:18px;cursor:pointer}
.media-meta-label{font-size:12px;color:var(--gray);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;display:block}
.media-meta-actions{display:flex;gap:10px;flex-wrap:wrap}
.media-meta-actions button{flex:1;min-width:120px}
@media(max-width:980px){.media-modal-card{grid-template-columns:1fr;max-height:95vh}}
</style>

<div class="page-header">
  <div><h1>Media Library</h1><p><?= number_format($total) ?> files</p></div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><h3><i class="fas fa-cloud-upload-alt" style="color:var(--violet);margin-right:8px"></i>Upload Files</h3></div>
  <div class="card-body">
    <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
      <i class="fas fa-cloud-upload-alt" style="font-size:32px;color:var(--violet);margin-bottom:12px;display:block"></i>
      <strong>Click to upload</strong> or drag & drop
      <div style="font-size:12px;color:var(--gray);margin-top:6px">PNG, JPG, GIF, WebP, PDF, SVG — max 10MB</div>
    </div>
    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:18px">
      <input type="text" id="uploadAlt" class="form-control" placeholder="Alt text (accessibility)">
      <input type="text" id="uploadFolder" class="form-control" placeholder="Folder / category" value="<?= e($folder ?: 'general') ?>" style="max-width:240px;flex:1">
    </div>
    <input type="file" id="fileInput" multiple accept="image/*,.pdf,.svg" style="display:none" onchange="uploadFiles(this.files)">
    <div id="uploadProgress" style="margin-top:16px;display:none">
      <div style="height:4px;background:var(--border);border-radius:4px;overflow:hidden"><div id="progressBar" style="height:100%;background:linear-gradient(90deg,var(--violet),var(--blue));width:0%;transition:.3s"></div></div>
      <div id="uploadStatus" style="font-size:12px;color:var(--gray);margin-top:6px;text-align:center"></div>
    </div>
  </div>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar">
  <div class="search-wrap"><i class="fas fa-search"></i><input type="text" name="search" class="search-input" placeholder="Search files…" value="<?= e($search) ?>"></div>
  <select name="type" class="form-control" style="width:auto">
    <option value="">All Types</option>
    <option value="image" <?= $type==='image'?'selected':'' ?>>Images</option>
    <option value="video" <?= $type==='video'?'selected':'' ?>>Video</option>
    <option value="document" <?= $type==='document'?'selected':'' ?>>Documents</option>
  </select>
  <select name="folder" class="form-control" style="width:auto">
    <option value="">All Folders</option>
    <?php foreach ($folders as $folderItem): ?>
    <option value="<?= e($folderItem['folder']) ?>" <?= $folder === $folderItem['folder'] ? 'selected' : '' ?>><?= e($folderItem['folder']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
  <?php if ($search||$type||$folder): ?><a href="<?= ADMIN_URL ?>/pages/media.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
</form>

<div class="media-grid" id="mediaGrid">
  <?php foreach ($files as $f): ?>
  <div class="media-item" id="media-<?= $f['id'] ?>"
       data-id="<?= $f['id'] ?>"
       data-path="<?= e($f['file_path']) ?>"
       data-url="<?= e(getImageUrl($f['file_path'])) ?>"
       data-filename="<?= e($f['filename']) ?>"
       data-alt="<?= e($f['alt_text']) ?>"
       data-caption="<?= e($f['caption']) ?>"
       data-folder="<?= e($f['folder'] ?: 'general') ?>"
       data-mime="<?= e($f['mime_type'] ?? $f['file_type']) ?>"
       onclick="openMediaDetails(<?= $f['id'] ?>, event)">
    <?php if (str_starts_with($f['file_type'], 'image/')): ?>
    <img src="<?= getImageUrl($f['file_path']) ?>" class="thumb" loading="lazy" alt="<?= e($f['alt_text'] ?: $f['filename']) ?>">
    <?php else: ?>
    <div class="thumb-doc"><i class="fas fa-file-pdf" style="color:var(--rose)"></i></div>
    <?php endif; ?>
    <div class="info">
      <div class="name" title="<?= e($f['filename']) ?>"><?= e($f['filename']) ?></div>
      <div class="meta"><?= e($f['folder'] ?: 'general') ?> · <?= $f['width'] ? $f['width'].'×'.$f['height'].' · ' : '' ?><?= round($f['file_size'] / 1024) ?>KB</div>
    </div>
    <form method="POST" class="del-form" style="display:inline">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $f['id'] ?>">
      <button type="button" class="del-btn" onclick="deleteMedia(<?= $f['id'] ?>, this, event)" title="Delete"><i class="fas fa-times"></i></button>
    </form>
  </div>
  <?php endforeach; ?>
  <?php if (!$files): ?>
  <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--gray)"><i class="fas fa-photo-video" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>No files uploaded yet</div>
  <?php endif; ?>
</div>

<div class="media-modal" id="mediaModal" aria-hidden="true">
  <div class="media-modal-card">
    <button type="button" class="close-btn" onclick="closeMediaModal()" title="Close">×</button>
    <div class="modal-preview" id="mediaPreview"></div>
    <div class="modal-sidebar">
      <div class="media-meta">
        <span class="media-meta-label">File details</span>
        <div><strong id="mediaFilename"></strong></div>
        <div id="mediaFolderText" style="font-size:13px;color:var(--gray);margin-top:4px"></div>
        <div id="mediaCaptionText" style="font-size:13px;color:var(--gray);margin-top:4px;white-space:pre-wrap;"></div>
        <div id="mediaDimensions" style="font-size:13px;color:var(--gray);margin-top:4px"></div>
      </div>
      <form id="mediaMetaForm" onsubmit="return false;">
        <input type="hidden" name="action" value="update_meta">
        <input type="hidden" name="id" id="mediaId">
        <div class="media-meta">
          <label class="media-meta-label" for="metaAlt">Alt text</label>
          <input id="metaAlt" name="alt_text" class="form-control" type="text" placeholder="Alt text">
        </div>
        <div class="media-meta">
          <label class="media-meta-label" for="metaCaption">Caption</label>
          <textarea id="metaCaption" name="caption" class="form-control" rows="3" placeholder="Optional caption"></textarea>
        </div>
        <div class="media-meta">
          <label class="media-meta-label" for="metaFolder">Folder</label>
          <input id="metaFolder" name="folder" class="form-control" type="text" placeholder="Folder / category">
        </div>
        <div class="media-meta-actions">
          <button type="button" class="btn btn-primary btn-sm" onclick="saveMediaMeta()"><i class="fas fa-save"></i> Save Metadata</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="copyMediaUrl()"><i class="fas fa-link"></i> Copy URL</button>
        </div>
      </form>
      <hr style="margin:18px 0">
      <div class="media-meta">
        <label class="media-meta-label">Replace file</label>
        <input type="file" id="replaceFileInput" class="form-control" accept="image/*,.pdf,.svg" onchange="replaceMediaFile(this)">
      </div>
    </div>
  </div>
</div>

<?php if ($pagination): ?>
<div style="margin-top:24px;display:flex;justify-content:flex-end"><div class="pagination"><?= $pagination ?></div></div>
<?php endif; ?>

<script>
const CSRF = document.querySelector('input[name="<?= CSRF_TOKEN_NAME ?>"]')?.value || '';

/* ── Drag & Drop ── */
const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('dragover'); uploadFiles(e.dataTransfer.files); });

/* ── Upload ── */
function uploadFiles(files) {
  if (!files.length) return;
  const prog = document.getElementById('uploadProgress');
  const bar  = document.getElementById('progressBar');
  const stat = document.getElementById('uploadStatus');
  const alt  = document.getElementById('uploadAlt')?.value.trim() || '';
  const folder = document.getElementById('uploadFolder')?.value.trim() || 'general';
  prog.style.display = 'block';
  let done = 0;
  Array.from(files).forEach(file => {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('alt', alt);
    fd.append('folder', folder);
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    fetch('', { method:'POST', body:fd })
      .then(r => r.json())
      .then(data => {
        done++;
        bar.style.width = (done / files.length * 100) + '%';
        stat.textContent = `Uploaded ${done} of ${files.length}`;
        if (data.ok) insertMedia(data);
        else showToast(data.error || 'Upload failed.', 'error');
        if (done === files.length) setTimeout(() => { prog.style.display='none'; bar.style.width='0%'; }, 1200);
      })
      .catch(() => {
        done++;
        if (done === files.length) setTimeout(() => { prog.style.display='none'; bar.style.width='0%'; }, 1200);
        showToast('Upload failed.', 'error');
      });
  });
}

function insertMedia(data) {
  const div = document.createElement('div');
  div.className = 'media-item';
  div.id = 'media-' + data.id;
  div.dataset.id = data.id;
  div.dataset.path = data.path;
  div.dataset.url = data.url;
  div.dataset.filename = data.name;
  div.dataset.alt = data.alt || '';
  div.dataset.caption = data.caption || '';
  div.dataset.folder = data.folder || 'general';
  div.dataset.mime = data.mime || data.type || 'image';
  div.innerHTML = `
    ${data.type === 'image' ? `<img src="${data.url}" class="thumb" loading="lazy" alt="${data.alt || data.name}">` : `<div class="thumb-doc"><i class="fas fa-file-pdf" style="color:var(--rose)"></i></div>`}
    <div class="info">
      <div class="name">${data.name}</div>
      <div class="meta">${data.folder || 'general'} · just now</div>
    </div>
    <button type="button" class="del-btn" onclick="deleteMedia(${data.id}, this, event)" title="Delete"><i class="fas fa-times"></i></button>
  `;
  div.addEventListener('click', e => openMediaDetails(data.id, e));
  document.getElementById('mediaGrid').prepend(div);
}

function openMediaDetails(id, event) {
  if (event && event.target.closest('.del-btn')) return;
  const item = document.getElementById('media-' + id);
  if (!item) return;
  const preview = document.getElementById('mediaPreview');
  const filename = document.getElementById('mediaFilename');
  const folderText = document.getElementById('mediaFolderText');
  const captionText = document.getElementById('mediaCaptionText');
  const dims = document.getElementById('mediaDimensions');
  const mediaId = document.getElementById('mediaId');
  const metaAlt = document.getElementById('metaAlt');
  const metaCaption = document.getElementById('metaCaption');
  const metaFolder = document.getElementById('metaFolder');

  const mime = item.dataset.mime || '';
  const url = item.dataset.url || '';
  const alt = item.dataset.alt || '';
  const caption = item.dataset.caption || '';
  const folder = item.dataset.folder || 'general';
  const name = item.dataset.filename || '';

  filename.textContent = name;
  folderText.textContent = `Folder: ${folder}`;
  captionText.textContent = caption ? `Caption: ${caption}` : '';
  dims.textContent = mime.startsWith('image/') ? '' : mime;
  metaAlt.value = alt;
  metaCaption.value = caption;
  metaFolder.value = folder;
  mediaId.value = id;

  if (mime.startsWith('image/')) {
    preview.innerHTML = `<img src="${url}" alt="${escapeHtml(alt || name)}">`;
  } else {
    preview.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:48px;color:var(--gray)"><i class="fas fa-file"></i></div>`;
  }

  document.getElementById('mediaModal').classList.add('open');
  document.getElementById('mediaModal').setAttribute('aria-hidden', 'false');
}

function closeMediaModal() {
  document.getElementById('mediaModal').classList.remove('open');
  document.getElementById('mediaModal').setAttribute('aria-hidden', 'true');
}

function saveMediaMeta() {
  const form = document.getElementById('mediaMetaForm');
  const id = document.getElementById('mediaId').value;
  const fd = new FormData(form);
  fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const item = document.getElementById('media-' + id);
        if (item) {
          item.dataset.alt = data.data.alt_text || '';
          item.dataset.caption = data.data.caption || '';
          item.dataset.folder = data.data.folder || 'general';
          item.querySelector('.meta').textContent = `${item.dataset.folder} · ${item.dataset.mime.startsWith('image/') ? '' : item.dataset.mime}`;
        }
        showToast(data.message, 'success');
      } else {
        showToast(data.message || 'Could not save metadata.', 'error');
      }
    });
}

function replaceMediaFile(input) {
  const id = document.getElementById('mediaId').value;
  if (!input.files || !input.files[0]) return;
  const fd = new FormData();
  fd.append('action', 'replace');
  fd.append('id', id);
  fd.append('replace_file', input.files[0]);
  fd.append('alt_text', document.getElementById('metaAlt').value.trim());
  fd.append('caption', document.getElementById('metaCaption').value.trim());
  fd.append('folder', document.getElementById('metaFolder').value.trim() || 'general');
  fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const item = document.getElementById('media-' + id);
        if (item) {
          item.dataset.path = data.data.path;
          item.dataset.url = data.data.url;
          item.dataset.alt = data.data.alt || '';
          item.dataset.caption = data.data.caption || '';
          item.dataset.folder = data.data.folder || 'general';
          item.dataset.mime = data.data.mime || item.dataset.mime;
          item.querySelector('.name').textContent = data.data.path.split('/').pop();
          item.querySelector('.meta').textContent = `${item.dataset.folder} · ${data.data.width ? data.data.width + '×' + data.data.height + ' · ' : ''}${Math.round(data.data.size / 1024)}KB`;
          const oldThumb = item.querySelector('.thumb');
          const oldDoc = item.querySelector('.thumb-doc');
          if (oldThumb) oldThumb.remove();
          if (oldDoc) oldDoc.remove();
          if (item.dataset.mime.startsWith('image/')) {
            item.insertAdjacentHTML('afterbegin', `<img src="${data.data.url}" class="thumb" loading="lazy" alt="${escapeHtml(item.dataset.alt || item.dataset.filename)}">`);
          } else {
            item.insertAdjacentHTML('afterbegin', `<div class="thumb-doc"><i class="fas fa-file-pdf" style="color:var(--rose)"></i></div>`);
          }
          if (document.getElementById('mediaModal').classList.contains('open')) {
            document.getElementById('mediaPreview').innerHTML = item.dataset.mime.startsWith('image/') ? `<img src="${data.data.url}" alt="${escapeHtml(item.dataset.alt || item.dataset.filename)}">` : `<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:48px;color:var(--gray)"><i class="fas fa-file"></i></div>`;
          }
        }
        showToast(data.message, 'success');
      } else {
        showToast(data.message || 'Could not replace file.', 'error');
      }
    });
}

function copyMediaUrl() {
  const url = document.getElementById('mediaPreview').querySelector('img')?.src || '';
  if (!url) return;
  navigator.clipboard.writeText(url).then(() => showToast('URL copied to clipboard.', 'success'));
}

function deleteMedia(id, btn, event) {
  if (event) event.stopPropagation();
  if (!confirm('Delete this file?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
  fetch('', { method:'POST', body:fd })
    .then(() => {
      document.getElementById('media-' + id)?.remove();
      closeMediaModal();
      showToast('File deleted.', 'success');
    });
}

function escapeHtml(text) {
  return text.replace(/[&"'<>]/g, function(m) { return {'&':'&amp;','"':'&quot;','\'':'&#39;','<':'&lt;','>':'&gt;'}[m]; });
}
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
