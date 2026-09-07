<?php
/**
 * Section Images.
 *
 * One screen for every image slot on the site that is not already attached to
 * a record. Page banners and in-page illustrations used to be fixed markup;
 * each one here can now be replaced with an upload, and cleared again to fall
 * back to the original built-in artwork.
 *
 * Images that belong to a record — a service, product, blog post, team member
 * — stay on that record's own edit screen, which is where people look for them.
 */
$adminTitle = 'Section Images';
$adminPage  = 'section-images';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* Slots that live under another screen are shown read-only here so the page
   is a complete picture without giving two places to edit the same value. */
const SECIMG_MANAGED_ELSEWHERE = [
    'secimg_services_hero' => ['Services Page → Hero', '/pages/services-page.php?tab=hero'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $key    = sanitizeInput($_POST['slot'] ?? '');
    $slots  = sectionImageSlots();

    if (!isset($slots[$key]) || isset(SECIMG_MANAGED_ELSEWHERE[$key])) {
        setFlash('error', 'Unknown image slot.');
        redirect(ADMIN_URL . '/pages/section-images.php');
    }

    if ($action === 'upload') {
        if (empty($_FILES['image']['name'])) {
            setFlash('error', 'Choose a file first.');
            redirect(ADMIN_URL . '/pages/section-images.php');
        }
        $up = uploadFile($_FILES['image'], 'sections');
        if (!$up['success']) {
            setFlash('error', $up['error']);
            redirect(ADMIN_URL . '/pages/section-images.php');
        }
        /* Replacing an image leaves the old file behind otherwise. */
        $old = sectionImagePath($key);
        saveSetting($key, $up['path'], 'section_images');
        if ($old !== '' && $old !== $up['path']) {
            $oldFile = rtrim(ROOT_PATH, '/\\') . '/uploads/' . ltrim($old, '/');
            if (is_file($oldFile)) @unlink($oldFile);
        }
        logActivity('update', 'content', "Set section image: {$key}");
        setFlash('success', $slots[$key][1] . ' image updated.');
    }

    if ($action === 'clear') {
        $old = sectionImagePath($key);
        saveSetting($key, '', 'section_images');
        if ($old !== '') {
            $oldFile = rtrim(ROOT_PATH, '/\\') . '/uploads/' . ltrim($old, '/');
            if (is_file($oldFile)) @unlink($oldFile);
        }
        logActivity('update', 'content', "Cleared section image: {$key}");
        setFlash('success', $slots[$key][1] . ' image removed — the built-in artwork is back.');
    }

    redirect(ADMIN_URL . '/pages/section-images.php');
}

$slots = sectionImageSlots();
$groups = [];
foreach ($slots as $key => [$group, $label, $hint, $size]) {
    $groups[$group][$key] = [$label, $hint, $size];
}
$setCount = 0;
foreach ($slots as $key => $_) { if (sectionImagePath($key) !== '') $setCount++; }

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span>Section Images</span>
    </div>
    <h1 class="page-title">Section Images</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px">
      <?= $setCount ?> of <?= count($slots) ?> slots have an image.
      Any slot left empty keeps the built-in artwork, so nothing breaks if you clear one.
    </p>
  </div>
</div>

<div class="si-note">
  <i class="fas fa-circle-info" aria-hidden="true"></i>
  <div>
    Images that belong to a record &mdash; a service, product, blog post, team member or partner &mdash;
    are edited on that record, not here.
  </div>
</div>

<?php foreach ($groups as $groupName => $items): ?>
<h2 class="si-group"><?= e($groupName) ?></h2>
<div class="si-grid">
  <?php foreach ($items as $key => [$label, $hint, $size]):
    $url      = sectionImage($key);
    $path     = sectionImagePath($key);
    $elsewhere = SECIMG_MANAGED_ELSEWHERE[$key] ?? null;
  ?>
  <div class="card si-card">
    <div class="si-preview">
      <?php if ($url !== ''): ?>
        <img src="<?= e($url) ?>" alt="<?= e($label) ?>">
      <?php else: ?>
        <div class="si-empty">
          <i class="fas fa-image"></i>
          <span>Built-in artwork</span>
        </div>
      <?php endif; ?>
    </div>
    <div class="si-body">
      <h3><?= e($label) ?></h3>
      <p><?= e($hint) ?></p>
      <div class="si-meta">
        <span><i class="fas fa-expand" aria-hidden="true"></i> <?= e($size) ?></span>
        <?php if ($path !== ''): ?>
        <span class="si-on"><i class="fas fa-check" aria-hidden="true"></i> custom</span>
        <?php endif; ?>
      </div>

      <?php if ($elsewhere): ?>
        <a href="<?= ADMIN_URL . e($elsewhere[1]) ?>" class="btn btn-secondary btn-sm" style="width:100%">
          <i class="fas fa-arrow-up-right-from-square"></i> Edit in <?= e($elsewhere[0]) ?>
        </a>
      <?php else: ?>
        <form method="POST" enctype="multipart/form-data" class="si-form">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="upload">
          <input type="hidden" name="slot" value="<?= e($key) ?>">
          <input type="file" name="image" class="form-control" accept="image/*" required>
          <button class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> Upload</button>
        </form>
        <?php if ($path !== ''): ?>
        <form method="POST" onsubmit="return confirm('Remove this image and go back to the built-in artwork?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="clear">
          <input type="hidden" name="slot" value="<?= e($key) ?>">
          <button class="btn btn-secondary btn-sm" style="width:100%;margin-top:8px">
            <i class="fas fa-rotate-left"></i> Remove
          </button>
        </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<style>
.si-note{
  display:flex; gap:12px; align-items:flex-start;
  padding:13px 16px; margin-bottom:22px; border-radius:var(--radius-lg);
  background:var(--light); border:1px solid var(--border);
  font-size:13px; line-height:1.6; color:var(--gray2);
}
.si-note i{ color:var(--violet); font-size:15px; margin-top:2px; }
.si-group{
  font-size:12px; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  color:var(--gray); margin:26px 0 12px;
}
.si-group:first-of-type{ margin-top:0; }
.si-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
.si-card{ display:flex; flex-direction:column; overflow:hidden; margin-bottom:0; }
.si-preview{
  height:150px; background:var(--light); border-bottom:1px solid var(--border);
  display:flex; align-items:center; justify-content:center; overflow:hidden;
}
.si-preview img{ width:100%; height:100%; object-fit:cover; display:block; }
.si-empty{
  display:flex; flex-direction:column; align-items:center; gap:7px;
  color:var(--gray); font-size:12.5px;
}
.si-empty i{ font-size:22px; color:var(--violet); opacity:.45; }
.si-body{ padding:16px; display:flex; flex-direction:column; flex:1; }
.si-body h3{ font-size:14.5px; font-weight:700; color:var(--dark); margin:0 0 5px; }
.si-body p{ font-size:12.5px; color:var(--gray); line-height:1.55; margin:0 0 10px; flex:1; }
.si-meta{ display:flex; gap:10px; flex-wrap:wrap; font-size:11.5px; color:var(--gray); margin-bottom:12px; }
.si-on{ color:var(--emerald); font-weight:700; }
.si-form{ display:flex; flex-direction:column; gap:8px; }
.si-form .form-control{ font-size:12.5px; padding:7px 9px; }
@media (max-width:600px){ .si-grid{ grid-template-columns:1fr; } }
</style>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
