<?php
require_once dirname(__DIR__) . '/includes/admin-layout.php';
$adminPage = 'coming-soon';
$pageTitle = 'Coming Soon';

$module = trim(strip_tags($_GET['module'] ?? 'This Feature'));

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-rocket" style="color:var(--cyan)"></i> <?= htmlspecialchars($module) ?></h1>
    <p class="page-subtitle">This module is under development and will be available soon.</p>
  </div>
</div>

<div style="display:flex;align-items:center;justify-content:center;min-height:52vh">
  <div style="text-align:center;max-width:480px;padding:2rem">
    <div style="width:96px;height:96px;background:linear-gradient(135deg,var(--primary),var(--cyan));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;box-shadow:0 12px 40px rgba(6,182,212,.25)">
      <i class="fas fa-tools" style="font-size:2.4rem;color:#fff"></i>
    </div>
    <h2 style="font-size:1.7rem;font-weight:800;color:var(--dark);margin-bottom:.75rem">
      <?= htmlspecialchars($module) ?> — Coming Soon
    </h2>
    <p style="color:var(--muted);line-height:1.7;margin-bottom:2rem">
      We're building something great. This feature is currently in development and will be rolled out in a future update.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <a href="<?= ADMIN_URL ?>/dashboard.php" class="btn btn-primary">
        <i class="fas fa-home"></i> Back to Dashboard
      </a>
      <a href="javascript:history.back()" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Go Back
      </a>
    </div>
    <p style="margin-top:2.5rem;font-size:12px;color:var(--muted);opacity:.6">
      Need this urgently? Contact your system administrator.
    </p>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
