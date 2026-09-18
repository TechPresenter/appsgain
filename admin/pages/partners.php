<?php
$adminPage  = 'partners';
$adminTitle = 'Partners Management';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = $_GET['action'] ?? 'list';
/* Delete and update post the id in the body; only the edit screen carries
   it in the query string. Reading GET alone silently broke delete. */
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

/* ── POST Handling ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $row = $id ? dbFetchOne("SELECT name, logo FROM partners WHERE id=?", [$id]) : null;
        if (!$row) {
            setFlash('error', 'That partner no longer exists.');
        } else {
            dbDelete('partners', 'id', $id);
            /* The uploaded logo would otherwise linger in uploads/ forever. */
            $logoPath = ROOT_PATH . '/uploads/' . ltrim((string)$row['logo'], '/');
            if (!empty($row['logo']) && is_file($logoPath)) @unlink($logoPath);
            logActivity('delete', 'partner', "Deleted partner #{$id} ({$row['name']})");
            setFlash('success', 'Partner deleted.');
        }
        redirect(ADMIN_URL . '/pages/partners.php');
    }

    if (in_array($postAction, ['create','update'])) {
        $name        = sanitizeInput($_POST['name'] ?? '');
        $website     = sanitizeInput($_POST['website'] ?? '');
        $partnerType = sanitizeInput($_POST['partner_type'] ?? 'Technology');
        $description = sanitizeInput($_POST['description'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active   = isset($_POST['is_active'])   ? 1 : 0;
        $sort_order  = (int)($_POST['sort_order'] ?? 0);

        if (!$name) { setFlash('error', 'Partner name is required.'); redirect(ADMIN_URL . '/pages/partners.php?action=' . ($id ? 'edit&id='.$id : 'create')); }

        $slug = $id ? (dbFetchValue("SELECT slug FROM partners WHERE id=?",[$id]) ?: slugify($name)) : slugify($name);
        // Ensure unique slug
        $existing = dbFetchValue("SELECT id FROM partners WHERE slug=? AND id!=?", [$slug, $id ?: 0]);
        if ($existing) $slug .= '-'.time();

        // Logo upload
        $logo = dbFetchValue("SELECT logo FROM partners WHERE id=?", [$id]) ?: '';
        if (!empty($_FILES['logo']['name'])) {
            $up = uploadFile($_FILES['logo'], 'partners');
            if (!empty($up['success'])) $logo = $up['path'];
        }

        $data = compact('name','slug','website','partner_type','description','logo','is_featured','is_active','sort_order');
        if ($id) { dbUpdate('partners', $data, 'id', $id); setFlash('success','Partner updated.'); }
        else     { dbInsertRow('partners', $data);           setFlash('success','Partner created.'); }
        redirect(ADMIN_URL . '/pages/partners.php');
    }

    /* Partner inquiry actions */
    if ($postAction === 'update_inquiry_status') {
        $inq_id = (int)($_POST['inq_id'] ?? 0);
        $status = in_array($_POST['status']??'',['new','reviewed','approved','rejected']) ? $_POST['status'] : 'new';
        if ($inq_id) dbExecute("UPDATE partner_inquiries SET status=?, admin_notes=? WHERE id=?", [$status, sanitizeInput($_POST['notes']??''), $inq_id]);
        setFlash('success','Inquiry status updated.');
        redirect(ADMIN_URL . '/pages/partners.php?tab=inquiries');
    }
}

$tab = $_GET['tab'] ?? 'partners';
$partners   = dbFetchAll("SELECT * FROM partners ORDER BY sort_order ASC, id ASC");
$inquiries  = [];
try { $inquiries = dbFetchAll("SELECT * FROM partner_inquiries ORDER BY created_at DESC LIMIT 50"); } catch(Exception $e){}

$editPartner = $id ? dbFetchOne("SELECT * FROM partners WHERE id=?",[$id]) : null;

require dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-handshake" style="color:var(--violet);margin-right:10px"></i>Partners Management</h1>
    <p>Manage partner companies and view partnership inquiries</p>
  </div>
  <?php if ($action==='list'): ?>
  <div class="page-header-actions">
    <a href="?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Partner</a>
    <a href="<?= SITE_URL ?>/partners.php" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View Page</a>
  </div>
  <?php endif; ?>
</div>

<?php if ($action === 'create' || ($action === 'edit' && $editPartner)): ?>
<!-- CREATE / EDIT FORM -->
<div class="card">
  <div class="card-header"><h3><?= $action==='create'?'Add New Partner':'Edit Partner: '.e($editPartner['name']) ?></h3></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $action==='create'?'create':'update' ?>">
      <div class="settings-grid-2">
        <div>
          <div class="form-group">
            <label>Partner Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($editPartner['name']??'') ?>" placeholder="Company name">
          </div>
          <div class="form-group">
            <label>Website URL</label>
            <input type="url" name="website" class="form-control" value="<?= e($editPartner['website']??'') ?>" placeholder="https://example.com">
          </div>
          <div class="form-group">
            <label>Partnership Type</label>
            <select name="partner_type" class="form-control">
              <?php foreach(['Technology','Cloud','Payments','Communication','Security','Marketing','Analytics','Infrastructure','Other'] as $t): ?>
              <option value="<?= $t ?>" <?= ($editPartner['partner_type']??'Technology')===$t?'selected':'' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."><?= e($editPartner['description']??'') ?></textarea>
          </div>
        </div>
        <div>
          <div class="form-group">
            <label>Logo / Icon</label>
            <?php if(!empty($editPartner['logo'])): ?>
            <div style="margin-bottom:10px"><img src="<?= getImageUrl($editPartner['logo']) ?>" style="height:64px;border-radius:10px;border:1px solid var(--border)"></div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="form-hint">PNG, JPG, SVG — Max 2MB. Transparent PNG recommended.</div>
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?= $editPartner['sort_order']??0 ?>">
          </div>
          <div style="display:flex;gap:24px;margin-top:12px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600">
              <input type="checkbox" name="is_featured" <?= !empty($editPartner['is_featured'])?'checked':'' ?> style="width:16px;height:16px"> Featured Partner
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600">
              <input type="checkbox" name="is_active" <?= (isset($editPartner['is_active'])?$editPartner['is_active']:1)?'checked':'' ?> style="width:16px;height:16px"> Active
            </label>
          </div>
        </div>
      </div>
      <div style="margin-top:20px;display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Partner</button>
        <a href="<?= ADMIN_URL ?>/pages/partners.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
<!-- TABS -->
<div style="display:flex;gap:8px;margin-bottom:20px">
  <a href="?tab=partners" class="btn <?= $tab==='partners'?'btn-primary':'btn-outline' ?> btn-sm">
    <i class="fas fa-handshake"></i> Partners (<?= count($partners) ?>)
  </a>
  <a href="?tab=inquiries" class="btn <?= $tab==='inquiries'?'btn-primary':'btn-outline' ?> btn-sm">
    <i class="fas fa-envelope"></i> Inquiries (<?= count($inquiries) ?>)
    <?php $newInq = count(array_filter($inquiries, function($i) { return $i['status'] === 'new'; })); if($newInq): ?>
    <span style="background:#e11d48;color:#fff;font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;margin-left:4px"><?= $newInq ?></span>
    <?php endif; ?>
  </a>
</div>

<?php if ($tab==='partners'): ?>
<!-- PARTNERS TABLE -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Logo</th><th>Name</th><th>Type</th><th>Website</th><th>Featured</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($partners as $p): ?>
        <tr>
          <td>
            <?php if(!empty($p['logo'])): ?>
            <img src="<?= getImageUrl($p['logo']) ?>" style="height:40px;width:60px;object-fit:contain;border-radius:8px;border:1px solid var(--border)">
            <?php else: ?>
            <div style="width:60px;height:40px;background:var(--light);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--violet)"><?= strtoupper(substr($p['name'],0,2)) ?></div>
            <?php endif; ?>
          </td>
          <td><strong><?= e($p['name']) ?></strong></td>
          <td><span class="badge badge-info"><?= e($p['partner_type']) ?></span></td>
          <td style="font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis">
            <?php if(!empty($p['website'])): ?><a href="<?= e($p['website']) ?>" target="_blank" style="color:var(--blue)"><?= e(parse_url($p['website'],PHP_URL_HOST)?:$p['website']) ?></a><?php else: ?>—<?php endif; ?>
          </td>
          <td><?= $p['is_featured']?'<span class="badge badge-warning">★ Featured</span>':'—' ?></td>
          <td><span class="badge <?= $p['is_active']?'badge-success':'badge-secondary' ?>"><?= $p['is_active']?'Active':'Hidden' ?></span></td>
          <td><?= $p['sort_order'] ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= e(addslashes($p['name'])) ?>?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; if(!$partners): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray)">No partners yet. <a href="?action=create">Add the first one</a>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- INQUIRIES TABLE -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Company</th><th>Contact</th><th>Type</th><th>Email</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($inquiries as $inq): ?>
        <tr>
          <td><strong><?= e($inq['company_name']) ?></strong><?php if(!empty($inq['website'])): ?><br><a href="<?= e($inq['website']) ?>" style="font-size:11px;color:var(--blue)" target="_blank"><?= e($inq['website']) ?></a><?php endif; ?></td>
          <td><?= e($inq['contact_name']) ?><br><span style="font-size:11px;color:var(--gray)"><?= e($inq['phone']??'') ?></span></td>
          <td><span class="badge badge-info"><?= e($inq['partner_type']??'—') ?></span><?php if($inq['company_size']): ?><br><span style="font-size:11px;color:var(--gray)"><?= e($inq['company_size']) ?></span><?php endif; ?></td>
          <td><a href="mailto:<?= e($inq['email']) ?>" style="color:var(--blue)"><?= e($inq['email']) ?></a></td>
          <td>
            <span class="badge <?= ['new'=>'badge-danger','reviewed'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-secondary'][$inq['status']] ?>">
              <?= ucfirst($inq['status']) ?>
            </span>
          </td>
          <td style="font-size:12px"><?= date('d M Y',strtotime($inq['created_at'])) ?></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openInqModal(<?= htmlspecialchars(json_encode($inq)) ?>)" title="Review"><i class="fas fa-eye"></i></button>
          </td>
        </tr>
        <?php endforeach; if(!$inquiries): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray)">No partnership inquiries yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Inquiry modal -->
<div id="inqModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:560px;width:90%;max-height:80vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById('inqModal').style.display='none'" style="position:absolute;top:14px;right:14px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray)">×</button>
    <h3 id="inqModalTitle" style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;margin-bottom:16px"></h3>
    <div id="inqModalBody"></div>
    <form method="POST" style="margin-top:20px">
      <?= csrfField() ?><input type="hidden" name="action" value="update_inquiry_status"><input type="hidden" name="inq_id" id="inqId">
      <div class="form-group">
        <label style="font-weight:700;margin-bottom:6px;display:block">Update Status</label>
        <select name="status" id="inqStatus" class="form-control">
          <option value="new">New</option><option value="reviewed">Reviewed</option>
          <option value="approved">Approved</option><option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="form-group">
        <label style="font-weight:700;margin-bottom:6px;display:block">Admin Notes</label>
        <textarea name="notes" id="inqNotes" class="form-control" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Save Status</button>
    </form>
  </div>
</div>
<script>
function openInqModal(inq) {
  document.getElementById('inqModalTitle').textContent = inq.company_name + ' — Partnership Inquiry';
  document.getElementById('inqId').value = inq.id;
  document.getElementById('inqStatus').value = inq.status;
  document.getElementById('inqNotes').value = inq.admin_notes||'';
  document.getElementById('inqModalBody').innerHTML =
    '<p><strong>Contact:</strong> '+inq.contact_name+' | <a href="mailto:'+inq.email+'">'+inq.email+'</a> | '+(inq.phone||'—')+'</p>' +
    '<p><strong>Type:</strong> '+(inq.partner_type||'—')+' | <strong>Size:</strong> '+(inq.company_size||'—')+'</p>' +
    (inq.website?'<p><strong>Website:</strong> <a href="'+inq.website+'" target="_blank">'+inq.website+'</a></p>':'')+
    '<p><strong>Message:</strong></p><p style="background:#f8fafc;padding:12px;border-radius:8px;font-size:13px;line-height:1.65">'+(inq.message||'—')+'</p>';
  document.getElementById('inqModal').style.display = 'flex';
}
</script>
<?php endif; endif; ?>

<?php require dirname(__DIR__) . '/includes/admin-foot.php'; ?>
