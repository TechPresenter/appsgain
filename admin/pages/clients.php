<?php
$adminPage  = 'clients';
$adminTitle = 'Clients Management';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete' && $id) {
        dbDelete('clients','id',$id);
        setFlash('success','Client deleted.');
        redirect(ADMIN_URL.'/pages/clients.php');
    }

    if (in_array($postAction,['create','update'])) {
        $name        = sanitizeInput($_POST['name']??'');
        $website     = sanitizeInput($_POST['website']??'');
        $industry    = sanitizeInput($_POST['industry']??'');
        $projectType = sanitizeInput($_POST['project_type']??'');
        $testimonial = sanitizeInput($_POST['testimonial']??'');
        $country     = sanitizeInput($_POST['country']??'India');
        $is_featured = isset($_POST['is_featured'])?1:0;
        $is_active   = isset($_POST['is_active'])?1:0;
        $sort_order  = (int)($_POST['sort_order']??0);

        if (!$name) { setFlash('error','Client name is required.'); redirect(ADMIN_URL.'/pages/clients.php?action='.($id?'edit&id='.$id:'create')); }

        $slug = $id ? (dbFetchValue("SELECT slug FROM clients WHERE id=?",[$id]) ?: slugify($name)) : slugify($name);
        $existing = dbFetchValue("SELECT id FROM clients WHERE slug=? AND id!=?",[$slug,$id?:0]);
        if ($existing) $slug .= '-'.time();

        $logo = dbFetchValue("SELECT logo FROM clients WHERE id=?",[$id]) ?: '';
        if (!empty($_FILES['logo']['name'])) {
            $up = uploadFile($_FILES['logo'],'clients');
            if (!empty($up['success'])) $logo = $up['path'];
        }

        $data = compact('name','slug','website','industry','project_type','testimonial','country','logo','is_featured','is_active','sort_order');
        if ($id) { dbUpdate('clients',$data,'id',$id); setFlash('success','Client updated.'); }
        else     { dbInsertRow('clients',$data);        setFlash('success','Client created.'); }
        redirect(ADMIN_URL.'/pages/clients.php');
    }
}

$clients    = dbFetchAll("SELECT * FROM clients ORDER BY sort_order ASC, id ASC");
$editClient = $id ? dbFetchOne("SELECT * FROM clients WHERE id=?",[$id]) : null;

require dirname(__DIR__).'/includes/admin-head.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-building" style="color:var(--emerald);margin-right:10px"></i>Clients Management</h1>
    <p>Manage client showcase — logos, industries and testimonials</p>
  </div>
  <?php if($action==='list'): ?>
  <div class="page-header-actions">
    <a href="?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Client</a>
    <a href="<?= SITE_URL ?>/clients.php" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View Page</a>
  </div>
  <?php endif; ?>
</div>

<?php if ($action==='create' || ($action==='edit' && $editClient)): ?>
<div class="card">
  <div class="card-header"><h3><?= $action==='create'?'Add New Client':'Edit: '.e($editClient['name']) ?></h3></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $action==='create'?'create':'update' ?>">
      <div class="settings-grid-2">
        <div>
          <div class="form-group">
            <label>Client Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($editClient['name']??'') ?>" placeholder="Company / Client name">
          </div>
          <div class="form-group">
            <label>Website</label>
            <input type="url" name="website" class="form-control" value="<?= e($editClient['website']??'') ?>" placeholder="https://example.com">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Industry</label>
              <input type="text" name="industry" class="form-control" value="<?= e($editClient['industry']??'') ?>" placeholder="e.g., FinTech, Healthcare">
            </div>
            <div class="form-group">
              <label>Project Type</label>
              <input type="text" name="project_type" class="form-control" value="<?= e($editClient['project_type']??'') ?>" placeholder="e.g., Mobile App, ERP">
            </div>
          </div>
          <div class="form-group">
            <label>Country</label>
            <input type="text" name="country" class="form-control" value="<?= e($editClient['country']??'India') ?>" placeholder="India">
          </div>
          <div class="form-group">
            <label>Testimonial / Quote <span style="font-weight:400;color:var(--gray)">(optional)</span></label>
            <textarea name="testimonial" class="form-control" rows="3" placeholder="What the client said about working with us..."><?= e($editClient['testimonial']??'') ?></textarea>
          </div>
        </div>
        <div>
          <div class="form-group">
            <label>Logo</label>
            <?php if(!empty($editClient['logo'])): ?>
            <div style="margin-bottom:10px"><img src="<?= getImageUrl($editClient['logo']) ?>" style="height:64px;border-radius:10px;border:1px solid var(--border)"></div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="form-hint">Transparent PNG or SVG recommended. Max 2MB.</div>
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?= $editClient['sort_order']??0 ?>">
          </div>
          <div style="display:flex;gap:24px;margin-top:12px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600">
              <input type="checkbox" name="is_featured" <?= !empty($editClient['is_featured'])?'checked':'' ?> style="width:16px;height:16px"> Featured Client
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600">
              <input type="checkbox" name="is_active" <?= (isset($editClient['is_active'])?$editClient['is_active']:1)?'checked':'' ?> style="width:16px;height:16px"> Active
            </label>
          </div>
          <div style="margin-top:24px;padding:14px;background:rgba(5,150,105,.06);border:1px solid rgba(5,150,105,.15);border-radius:10px;font-size:13px;color:var(--emerald)">
            <i class="fas fa-info-circle"></i> Featured clients appear in the highlighted "Success Stories" section. Regular clients appear in the logo wall.
          </div>
        </div>
      </div>
      <div style="margin-top:20px;display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Client</button>
        <a href="<?= ADMIN_URL ?>/pages/clients.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Logo</th><th>Name</th><th>Industry</th><th>Project</th><th>Country</th><th>Featured</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($clients as $c): ?>
        <tr>
          <td>
            <?php if(!empty($c['logo'])): ?>
            <img src="<?= getImageUrl($c['logo']) ?>" style="height:40px;width:60px;object-fit:contain;border-radius:8px;border:1px solid var(--border)">
            <?php else: ?>
            <div style="width:60px;height:40px;background:var(--light);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:var(--emerald)"><?= strtoupper(substr($c['name'],0,2)) ?></div>
            <?php endif; ?>
          </td>
          <td><strong><?= e($c['name']) ?></strong><?php if(!empty($c['website'])): ?><br><a href="<?= e($c['website']) ?>" style="font-size:11px;color:var(--blue)" target="_blank"><?= e(parse_url($c['website'],PHP_URL_HOST)?:$c['website']) ?></a><?php endif; ?></td>
          <td><?= $c['industry']?'<span class="badge badge-info">'.e($c['industry']).'</span>':'—' ?></td>
          <td style="font-size:12px"><?= e($c['project_type']??'—') ?></td>
          <td style="font-size:12px"><i class="fas fa-globe" style="color:var(--gray);font-size:10px"></i> <?= e($c['country']??'—') ?></td>
          <td><?= $c['is_featured']?'<span class="badge badge-warning">★ Featured</span>':'—' ?></td>
          <td><span class="badge <?= $c['is_active']?'badge-success':'badge-secondary' ?>"><?= $c['is_active']?'Active':'Hidden' ?></span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-pen"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this client?')">
                <?= csrfField() ?><input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" onclick="var h=document.createElement('input');h.type='hidden';h.name='id';h.value='<?= $c['id'] ?>';this.closest('form').appendChild(h)"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; if(!$clients): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray)">No clients yet. <a href="?action=create">Add the first one</a>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__).'/includes/admin-foot.php'; ?>
