<?php
$adminTitle = 'Leads & Enquiries';
$adminPage  = 'leads';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitizeInput($_GET['search']  ?? '');
$status  = sanitizeInput($_GET['status']  ?? '');
$source  = sanitizeInput($_GET['source']  ?? '');
$dateFrom= sanitizeInput($_GET['from']    ?? '');
$dateTo  = sanitizeInput($_GET['to']      ?? '');
$view    = (int)($_GET['view']            ?? 0);
$type    = sanitizeInput($_GET['type']    ?? '');
$formAction = sanitizeInput($_GET['action'] ?? '');
$editId     = (int)($_GET['id']           ?? 0);

/* Shared by the form and by validation, so the two can never drift. */
const LEAD_STATUSES = ['new','contacted','in_progress','converted','rejected','spam'];
const LEAD_SERVICES = [
    'Custom Software Development', 'ERP Development', 'CRM Development',
    'CMS Development', 'Mobile App Development (Android & iOS)',
    'AI Product Development', 'SaaS Platform Development',
    'Web Portal Development', 'Enterprise Applications',
    'Automation Solutions', 'High-End Business Websites',
    'Custom Business Platforms', 'Other / Not Sure',
];
const LEAD_BUDGETS = [
    'Under ₹50,000', '₹50,000 - ₹1,00,000', '₹1,00,000 - ₹3,00,000',
    '₹3,00,000 - ₹10,00,000', 'Above ₹10,00,000', 'Discuss with team',
];

/* ── POST Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id  = (int)$_POST['id'];
        $ns  = sanitizeInput($_POST['status']);
        $allowed = ['new','contacted','in_progress','converted','rejected','spam'];
        if ($id && in_array($ns, $allowed)) {
            dbExecute("UPDATE leads SET status=?, is_read=1 WHERE id=?", [$ns, $id]);
            logActivity('update', 'lead', "Updated lead #{$id} status to {$ns}");
            setFlash('success', 'Lead status updated.');
        }
    }

    if ($action === 'add_note') {
        $id   = (int)$_POST['id'];
        $note = sanitizeInput($_POST['note'] ?? '');
        if ($id && strlen($note) >= 1) {
            dbExecute("UPDATE leads SET admin_notes=? WHERE id=?", [$note, $id]);
            setFlash('success', 'Note saved.');
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            dbExecute("DELETE FROM leads WHERE id=?", [$id]);
            logActivity('delete', 'lead', "Deleted lead #{$id}");
            setFlash('success', 'Lead deleted.');
        }
    }

    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', $ids);
            dbExecute("DELETE FROM leads WHERE id IN ({$in})");
            logActivity('delete', 'lead', 'Bulk deleted ' . count($ids) . ' leads');
            setFlash('success', count($ids) . ' leads deleted.');
        }
    }

    if ($action === 'bulk_status') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $ns  = sanitizeInput($_POST['bulk_status_value'] ?? '');
        $allowed = ['new','contacted','in_progress','converted','rejected','spam'];
        if ($ids && in_array($ns, $allowed)) {
            $in = implode(',', $ids);
            dbExecute("UPDATE leads SET status=? WHERE id IN ({$in})", [$ns]);
            setFlash('success', count($ids) . ' leads updated to "' . ucfirst($ns) . '".');
        }
    }

    /* ── Create / update a lead by hand ──
       Sales staff take enquiries by phone and at events, so the same record
       has to be creatable without a website submission. Validation mirrors
       the public form: name, email and message are what make a lead usable. */
    if ($action === 'create' || $action === 'update') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = sanitizeInput($_POST['name'] ?? '');
        $email   = trim((string)($_POST['email'] ?? ''));
        $phone   = sanitizeInput($_POST['phone'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $service = sanitizeInput($_POST['service'] ?? '');
        $budget  = sanitizeInput($_POST['budget'] ?? '');
        $message = trim((string)($_POST['message'] ?? ''));
        $lstatus = sanitizeInput($_POST['status'] ?? 'new');
        $notes   = sanitizeInput($_POST['admin_notes'] ?? '');
        $srcPage = sanitizeInput($_POST['source_page'] ?? '');

        $errors = [];
        if ($name === '')                                  $errors[] = 'Name is required.';
        if ($email === '')                                 $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'That email address is not valid.';
        if ($message === '')                               $errors[] = 'Message is required.';
        if (!in_array($lstatus, LEAD_STATUSES, true))      $lstatus = 'new';
        /* Only a brand-new record is genuinely "added manually"; stamping
           that on an edit would relabel a website lead as a phone one. */
        if ($srcPage === '' && $action === 'create') $srcPage = 'Added manually';

        /* A duplicate is worth flagging but not worth blocking — the same
           person legitimately enquires more than once. */
        if (!$errors) {
            $dupe = $id
                ? dbFetchValue("SELECT id FROM leads WHERE email=? AND id<>? LIMIT 1", [$email, $id])
                : dbFetchValue("SELECT id FROM leads WHERE email=? LIMIT 1", [$email]);
        }

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            $back = $action === 'update'
                ? ADMIN_URL . "/pages/leads.php?action=edit&id={$id}"
                : ADMIN_URL . '/pages/leads.php?action=add';
            redirect($back);
        }

        if ($action === 'create') {
            dbExecute(
                "INSERT INTO leads (name,email,phone,company,service,budget,message,status,admin_notes,source_page,ip_address,is_read)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,1)",
                [$name,$email,$phone,$company,$service,$budget,$message,$lstatus,$notes,$srcPage,
                 $_SERVER['REMOTE_ADDR'] ?? '']
            );
            $newId = (int)db()->lastInsertId();
            logActivity('create', 'lead', "Added lead #{$newId} ({$name})");
            setFlash('success', 'Lead added.' . (!empty($dupe) ? " Note: #{$dupe} already uses this email." : ''));
            redirect(ADMIN_URL . "/pages/leads.php?view={$newId}");
        }

        if ($id) {
            dbExecute(
                "UPDATE leads SET name=?,email=?,phone=?,company=?,service=?,budget=?,message=?,status=?,admin_notes=?,source_page=? WHERE id=?",
                [$name,$email,$phone,$company,$service,$budget,$message,$lstatus,$notes,$srcPage,$id]
            );
            logActivity('update', 'lead', "Edited lead #{$id} ({$name})");
            setFlash('success', 'Lead updated.' . (!empty($dupe) ? " Note: #{$dupe} also uses this email." : ''));
        }
        redirect(ADMIN_URL . "/pages/leads.php?view={$id}");
    }

    redirect(ADMIN_URL . '/pages/leads.php' . ($view ? "?view={$view}" : ''));
}

/* ── CSV Export ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    Auth::requireAdmin();
    $rows = dbFetchAll("SELECT name, email, phone, company, service, message, status, source_page, created_at FROM leads ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['Name','Email','Phone','Company','Service','Message','Status','Source','Date']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['name'],$r['email'],$r['phone'],$r['company'],$r['service'],$r['message'],$r['status'],$r['source_page'],$r['created_at']]);
    }
    fclose($out); exit;
}

/* ── Single Lead View ── */
if ($view) {
    $lead = dbFetchOne("SELECT * FROM leads WHERE id=?", [$view]);
    if ($lead && !$lead['is_read']) {
        dbExecute("UPDATE leads SET is_read=1 WHERE id=?", [$view]);
    }
}

/* ── Counts for tabs ── */
/* One clause, reused by every tab count and by the list query below. */
$typeSQL = match ($type) {
    'enquiry' => " AND (source_page NOT LIKE 'popup%' AND source_page <> 'Added manually')",
    'popup'   => " AND source_page LIKE 'popup%'",
    'manual'  => " AND source_page = 'Added manually'",
    default   => '',
};
$tCount = fn(string $extra = ''): int =>
    (int)dbFetchValue("SELECT COUNT(*) FROM leads WHERE 1{$typeSQL}{$extra}");

$countAll       = $tCount();
$countNew       = $tCount(" AND status='new'");
$countContacted = $tCount(" AND status='contacted'");
$countProgress  = $tCount(" AND status='in_progress'");
$countConverted = $tCount(" AND status='converted'");
$countRejected  = $tCount(" AND status='rejected'");
$countSpam      = $tCount(" AND status='spam'");
$countUnread    = $tCount(" AND is_read=0");

/* ── Sources for filter ── */
$sources = dbFetchAll("SELECT DISTINCT source_page FROM leads WHERE source_page != '' ORDER BY source_page ASC");

/* ── Build Query ── */
$where = []; $params = [];
if ($search) {
    $where[]  = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR service LIKE ? OR message LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like,$like,$like,$like,$like]);
}
if ($status)   { $where[] = "status=?";      $params[] = $status; }
if ($source)   { $where[] = "source_page=?"; $params[] = $source; }
/* How the lead reached us. 'popup' rows are stamped by api/enquiry.php,
   manual ones by the form below; everything else came off a site form. */
if ($type === 'enquiry') {
    $where[] = "(source_page NOT LIKE 'popup%' AND source_page <> 'Added manually')";
} elseif ($type === 'popup') {
    $where[] = "source_page LIKE 'popup%'";
} elseif ($type === 'manual') {
    $where[] = "source_page = 'Added manually'";
}
if ($dateFrom) { $where[] = "DATE(created_at)>=?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "DATE(created_at)<=?"; $params[] = $dateTo; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total      = (int)dbFetchValue("SELECT COUNT(*) FROM leads {$whereSQL}", $params);
$leads      = dbFetchAll("SELECT * FROM leads {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
/* Carry the active filters into page 2, or paging silently resets them. */
$pagination = paginate($total, $perPage, $page, leadsUrl());

$statusColors = ['new'=>'badge-danger','contacted'=>'badge-info','in_progress'=>'badge-warning','converted'=>'badge-success','rejected'=>'badge-secondary','spam'=>'badge-secondary'];
$statusDots   = ['new'=>'#e11d48','contacted'=>'#2563eb','in_progress'=>'#d97706','converted'=>'#059669','rejected'=>'#9ca3af','spam'=>'#d1d5db'];
$avatarColors = ['#7c3aed','#e11d48','#059669','#2563eb','#d97706','#06b6d4','#ea580c','#8b5cf6','#f43f5e','#10b981'];

/* Base URL for pagination links with filters */
function leadsUrl(array $extra = []): string {
    $base = ['search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? '', 'source' => $_GET['source'] ?? '', 'type' => $_GET['type'] ?? '', 'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? ''];
    $merged = array_merge($base, $extra);
    $qs = http_build_query(array_filter($merged));
    return ADMIN_URL.'/pages/leads.php'.($qs ? '?'.$qs : '');
}

/* ── Add / Edit screen ──
   Its own branch rather than a modal: a lead carries enough fields that a
   full page is easier to fill in, and it gives edit a real URL to link to. */
if (in_array($formAction, ['add','edit'], true)) {
    $isEdit = $formAction === 'edit';
    $L = $isEdit ? dbFetchOne("SELECT * FROM leads WHERE id=?", [$editId]) : null;
    if ($isEdit && !$L) {
        setFlash('error', 'That lead no longer exists.');
        redirect(ADMIN_URL . '/pages/leads.php');
    }
    $adminTitle = $isEdit ? 'Edit Lead' : 'Add Lead';
    $val = static fn(string $k, string $d = ''): string => (string)($L[$k] ?? $d);
    require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <a href="<?= ADMIN_URL ?>/pages/leads.php">Leads &amp; Enquiries</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span><?= $isEdit ? 'Edit' : 'Add' ?></span>
    </div>
    <h1 class="page-title"><?= $isEdit ? 'Edit Lead #' . (int)$L['id'] : 'Add Lead' ?></h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px">
      <?= $isEdit
          ? 'Captured ' . e(date('M j, Y g:i A', strtotime($L['created_at'])))
          : 'For enquiries that arrive by phone, email or in person.' ?>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="<?= ADMIN_URL ?>/pages/leads.php<?= $isEdit ? '?view=' . (int)$L['id'] : '' ?>" class="btn btn-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Cancel
    </a>
  </div>
</div>

<form method="POST" class="lead-form">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$L['id'] ?>"><?php endif; ?>

  <div class="lf-grid">
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-address-card" style="color:var(--violet);margin-right:8px"></i>Contact Details</h3></div>
        <div class="card-body">
          <div class="lf-2">
            <div class="form-group">
              <label>Full Name <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" required maxlength="150"
                     value="<?= e($val('name')) ?>" placeholder="Rahul Sharma">
            </div>
            <div class="form-group">
              <label>Email <span class="required">*</span></label>
              <input type="email" name="email" class="form-control" required maxlength="200"
                     value="<?= e($val('email')) ?>" placeholder="rahul@company.com">
            </div>
          </div>
          <div class="lf-2">
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" maxlength="30"
                     value="<?= e($val('phone')) ?>" placeholder="+91-9955446477">
            </div>
            <div class="form-group">
              <label>Company</label>
              <input type="text" name="company" class="form-control" maxlength="200"
                     value="<?= e($val('company')) ?>" placeholder="Optional">
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-briefcase" style="color:var(--blue);margin-right:8px"></i>Requirement</h3></div>
        <div class="card-body">
          <div class="lf-2">
            <div class="form-group">
              <label>Service Interested In</label>
              <select name="service" class="form-control">
                <option value="">&mdash; Select a service &mdash;</option>
                <?php
                /* An older lead may carry a service no longer on the list;
                   keep it selectable so editing cannot silently drop it. */
                $svcList = LEAD_SERVICES;
                $cur = $val('service');
                if ($cur !== '' && !in_array($cur, $svcList, true)) $svcList[] = $cur;
                foreach ($svcList as $sv): ?>
                <option value="<?= e($sv) ?>" <?= $cur === $sv ? 'selected' : '' ?>><?= e($sv) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Budget Range</label>
              <select name="budget" class="form-control">
                <option value="">&mdash; Not discussed &mdash;</option>
                <?php
                $budList = LEAD_BUDGETS;
                $curB = $val('budget');
                if ($curB !== '' && !in_array($curB, $budList, true)) $budList[] = $curB;
                foreach ($budList as $bg): ?>
                <option value="<?= e($bg) ?>" <?= $curB === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Message / Requirement <span class="required">*</span></label>
            <textarea name="message" class="form-control" rows="6" required
                      placeholder="What did they ask for?"><?= e($val('message')) ?></textarea>
            <div class="form-hint">What the client actually wants. This is what the team reads first.</div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-tags" style="color:var(--amber);margin-right:8px"></i>Status</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Pipeline Stage</label>
            <select name="status" class="form-control">
              <?php $curS = $val('status', 'new');
              foreach (LEAD_STATUSES as $st): ?>
              <option value="<?= e($st) ?>" <?= $curS === $st ? 'selected' : '' ?>>
                <?= e(ucfirst(str_replace('_', ' ', $st))) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Source</label>
            <input type="text" name="source_page" class="form-control" maxlength="300"
                   value="<?= e($val('source_page', $isEdit ? '' : 'Added manually')) ?>"
                   placeholder="Phone call, referral, event...">
            <div class="form-hint">Where the enquiry came from. Feeds the source filter.</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><i class="fas fa-sticky-note" style="color:var(--cyan);margin-right:8px"></i>Internal Notes</h3></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0">
            <textarea name="admin_notes" class="form-control" rows="5"
                      placeholder="Only your team sees this."><?= e($val('admin_notes')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $isEdit ? 'Save Changes' : 'Add Lead' ?>
          </button>
          <a href="<?= ADMIN_URL ?>/pages/leads.php<?= $isEdit ? '?view=' . (int)$L['id'] : '' ?>"
             class="btn btn-secondary" style="text-align:center">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

<style>
.lf-grid{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start}
.lf-2{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}
@media(max-width:980px){.lf-grid{grid-template-columns:1fr}}
@media(max-width:620px){.lf-2{grid-template-columns:1fr}}
</style>
<?php
    require_once dirname(__DIR__) . '/includes/admin-foot.php';
    exit;
}
?>
<?php require_once dirname(__DIR__) . '/includes/admin-head.php'; ?>
<style>
.leads-page .page-header{margin-bottom:18px}
.leads-filter-bar{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:18px;display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap}
.filter-group{display:flex;flex-direction:column;gap:5px}
.filter-group label{font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px}
.filter-group .form-control{min-width:130px}
.lead-detail-card{background:var(--white);border-radius:var(--radius-xl);border:1px solid var(--border);overflow:hidden;margin-bottom:22px;box-shadow:var(--shadow-md)}
.ld-header{padding:20px 24px;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;gap:16px}
.ld-avatar{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;flex-shrink:0}
.ld-name{color:#fff;font-size:20px;font-weight:800}
.ld-sub{color:rgba(255,255,255,.65);font-size:13px;margin-top:3px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ld-actions{margin-left:auto;display:flex;gap:8px}
.ld-body{padding:24px;display:grid;grid-template-columns:1fr 360px;gap:24px}
.info-block{background:var(--light);border-radius:12px;padding:16px 20px}
.info-block-title{font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
@media(max-width:768px){.ld-body{grid-template-columns:1fr}.ld-actions{display:none}.leads-filter-bar{flex-direction:column}}
</style>

<div class="leads-page">
<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right" style="font-size:9px"></i></span>
      <span>Leads &amp; Enquiries</span>
    </div>
    <h1 class="page-title">Leads &amp; Enquiries</h1>
    <p style="font-size:13.5px;color:var(--gray);margin-top:4px">
      <?= number_format($countAll) ?> total leads
      <?= $countUnread > 0 ? "· <strong style='color:var(--rose)'>{$countUnread} unread</strong>" : '' ?>
    </p>
  </div>
  <div class="page-header-actions">
    <!-- Export dropdown -->
    <div class="export-btn-wrap">
      <button class="btn btn-secondary btn-sm" data-toggle-menu="exportMenu">
        <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down" style="font-size:10px"></i>
      </button>
      <div class="export-menu" id="exportMenu">
        <a href="?export=csv<?= $search ? '&search='.urlencode($search) : '' ?><?= $status ? '&status='.urlencode($status) : '' ?>">
          <i class="fas fa-file-csv" style="color:var(--emerald)"></i> Export CSV
        </a>
        <a href="javascript:window.print()">
          <i class="fas fa-print" style="color:var(--blue)"></i> Print
        </a>
      </div>
    </div>
    <a href="<?= ADMIN_URL ?>/pages/leads.php?action=add" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Add Lead
    </a>
  </div>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
  <a href="<?= leadsUrl(['status'=>'','page'=>'']) ?>" class="status-tab <?= !$status ? 'active' : '' ?>">
    <i class="fas fa-list"></i> All <span class="tab-count"><?= number_format($countAll) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'new','page'=>'']) ?>" class="status-tab <?= $status==='new' ? 'active' : '' ?>">
    <i class="fas fa-bolt"></i> New <span class="tab-count"><?= number_format($countNew) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'contacted','page'=>'']) ?>" class="status-tab <?= $status==='contacted' ? 'active' : '' ?>">
    <i class="fas fa-phone-alt"></i> Contacted <span class="tab-count"><?= number_format($countContacted) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'in_progress','page'=>'']) ?>" class="status-tab <?= $status==='in_progress' ? 'active' : '' ?>">
    <i class="fas fa-spinner"></i> In Progress <span class="tab-count"><?= number_format($countProgress) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'converted','page'=>'']) ?>" class="status-tab <?= $status==='converted' ? 'active' : '' ?>">
    <i class="fas fa-trophy"></i> Converted <span class="tab-count"><?= number_format($countConverted) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'rejected','page'=>'']) ?>" class="status-tab <?= $status==='rejected' ? 'active' : '' ?>">
    <i class="fas fa-times-circle"></i> Rejected <span class="tab-count"><?= number_format($countRejected) ?></span>
  </a>
  <a href="<?= leadsUrl(['status'=>'spam','page'=>'']) ?>" class="status-tab <?= $status==='spam' ? 'active' : '' ?>">
    <i class="fas fa-ban"></i> Spam <span class="tab-count"><?= number_format($countSpam) ?></span>
  </a>
</div>

<!-- Single Lead Detail Panel -->
<?php if ($view && !empty($lead)): ?>
<div class="lead-detail-card">
  <div class="ld-header">
    <div class="ld-avatar"><?= strtoupper(substr($lead['name'], 0, 1)) ?></div>
    <div>
      <div class="ld-name"><?= e($lead['name']) ?></div>
      <div class="ld-sub">
        <?php if ($lead['company']): ?><span><i class="fas fa-building" style="font-size:11px"></i> <?= e($lead['company']) ?></span><?php endif; ?>
        <span><i class="fas fa-clock" style="font-size:11px"></i> <?= timeAgo($lead['created_at']) ?></span>
        <span class="badge <?= $statusColors[$lead['status']] ?? 'badge-secondary' ?>"><?= ucfirst(str_replace('_',' ',$lead['status'])) ?></span>
      </div>
    </div>
    <div class="ld-actions">
      <a href="mailto:<?= e($lead['email']) ?>" class="btn btn-ghost" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
        <i class="fas fa-envelope"></i> Email
      </a>
      <?php if ($lead['phone']): ?>
      <a href="tel:<?= e($lead['phone']) ?>" class="btn btn-ghost" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
        <i class="fas fa-phone"></i> Call
      </a>
      <?php endif; ?>
      <a href="<?= ADMIN_URL ?>/pages/leads.php?action=edit&id=<?= (int)$lead['id'] ?>" class="btn btn-ghost" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
        <i class="fas fa-pen"></i> Edit
      </a>
      <a href="<?= ADMIN_URL ?>/pages/leads.php" class="btn" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;padding:9px 14px">
        <i class="fas fa-times"></i>
      </a>
    </div>
  </div>
  <div class="ld-body">
    <!-- Left: contact info + message -->
    <div>
      <div class="info-block" style="margin-bottom:16px">
        <div class="info-block-title"><i class="fas fa-address-card" style="color:var(--violet)"></i> Contact Information</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><a href="mailto:<?= e($lead['email']) ?>" style="color:var(--violet)"><?= e($lead['email']) ?></a></div>
          </div>
          <div class="info-item">
            <div class="info-label">Phone</div>
            <div class="info-value"><?= e($lead['phone'] ?: '—') ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Company</div>
            <div class="info-value"><?= e($lead['company'] ?: '—') ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Service Interest</div>
            <div class="info-value"><?= e($lead['service'] ?: '—') ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Source</div>
            <div class="info-value"><?= e($lead['source_page'] ?: 'Website') ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">IP Address</div>
            <div class="info-value"><?= e($lead['ip_address'] ?? '—') ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Submitted</div>
            <div class="info-value"><?= date('M j, Y g:i A', strtotime($lead['created_at'])) ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Lead ID</div>
            <div class="info-value" style="color:var(--gray2)">#<?= $lead['id'] ?></div>
          </div>
        </div>
      </div>
      <?php if ($lead['message']): ?>
      <div class="info-block" style="margin-bottom:16px">
        <div class="info-block-title"><i class="fas fa-comment-alt" style="color:var(--blue)"></i> Message</div>
        <p style="line-height:1.75;font-size:14px;color:var(--text)"><?= nl2br(e($lead['message'])) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: status + notes -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="info-block">
        <div class="info-block-title"><i class="fas fa-tags" style="color:var(--amber)"></i> Update Status</div>
        <form method="POST" style="display:flex;flex-direction:column;gap:10px">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="id" value="<?= $lead['id'] ?>">
          <?php foreach(['new','contacted','in_progress','converted','rejected','spam'] as $s): ?>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 12px;border-radius:9px;border:1.5px solid <?= $lead['status']===$s ? $statusDots[$s].'44' : 'var(--border)' ?>;background:<?= $lead['status']===$s ? $statusDots[$s].'0d' : 'transparent' ?>;transition:var(--transition)">
            <input type="radio" name="status" value="<?= $s ?>" <?= $lead['status']===$s ? 'checked' : '' ?> style="accent-color:<?= $statusDots[$s] ?>">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $statusDots[$s] ?>;flex-shrink:0"></span>
            <span style="font-size:13px;font-weight:600"><?= ucfirst(str_replace('_',' ',$s)) ?></span>
          </label>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-top:4px"><i class="fas fa-check"></i> Save Status</button>
        </form>
      </div>
      <div class="info-block">
        <div class="info-block-title"><i class="fas fa-sticky-note" style="color:var(--cyan)"></i> Notes</div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_note">
          <input type="hidden" name="id" value="<?= $lead['id'] ?>">
          <textarea name="note" class="form-control" style="min-height:80px;margin-bottom:10px;font-size:13px" placeholder="Add a note about this lead…"><?= e($lead['admin_notes'] ?? '') ?></textarea>
          <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center"><i class="fas fa-save"></i> Save Note</button>
        </form>
      </div>
      <div style="display:flex;gap:8px">
        <a href="mailto:<?= e($lead['email']) ?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center">
          <i class="fas fa-envelope"></i> Email
        </a>
        <form method="POST" onsubmit="return confirm('Permanently delete this lead?')" style="flex:1">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $lead['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm" style="width:100%;justify-content:center">
            <i class="fas fa-trash"></i> Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<form method="GET" id="filterForm" class="leads-filter-bar">
  <div class="filter-group" style="flex:1;min-width:200px">
    <label>Search</label>
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" name="search" class="search-input" placeholder="Name, email, phone, service…" value="<?= e($search) ?>">
    </div>
  </div>
  <?php if ($sources): ?>
  <div class="filter-group">
    <label>Source</label>
    <select name="source" class="form-control">
      <option value="">All Sources</option>
      <?php foreach ($sources as $src): ?>
      <option value="<?= e($src['source_page']) ?>" <?= $source === $src['source_page'] ? 'selected' : '' ?>>
        <?= e($src['source_page']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="filter-group">
    <label>From Date</label>
    <input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>">
  </div>
  <div class="filter-group">
    <label>To Date</label>
    <input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>">
  </div>
  <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <div style="display:flex;gap:8px;align-self:flex-end">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
    <?php if ($search || $source || $dateFrom || $dateTo): ?>
    <a href="<?= leadsUrl(['search'=>'','source'=>'','from'=>'','to'=>'']) ?>" class="btn btn-secondary btn-sm">
      <i class="fas fa-times"></i> Clear
    </a>
    <?php endif; ?>
  </div>
</form>

<!-- Bulk Action Bar -->
<form method="POST" id="bulkForm">
  <?= csrfField() ?>
  <input type="hidden" name="action" id="bulkActionType" value="bulk_delete">
  <div class="bulk-bar" id="bulkActionBar">
    <span class="bulk-bar-count" id="bulkCount">0 items selected</span>
    <select name="bulk_status_value" class="form-control" style="width:auto;padding:6px 12px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:13px;border-radius:8px" id="bulkStatusSelect">
      <option value="" style="color:#000">— Change Status —</option>
      <?php foreach(['new','contacted','in_progress','converted','rejected','spam'] as $s): ?>
      <option value="<?= $s ?>" style="color:#000"><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-sm" onclick="bulkChangeStatus()">
      <i class="fas fa-check"></i> Apply Status
    </button>
    <button type="button" class="btn btn-sm btn-danger-ghost" onclick="bulkDeleteSelected()">
      <i class="fas fa-trash"></i> Delete Selected
    </button>
  </div>

  <!-- Leads Table -->
  <div class="card">
    <div class="card-header">
      <h3>
        <?php if ($status): ?>
          <span style="color:<?= $statusDots[$status] ?? 'var(--violet)' ?>">&#9679;</span>
          <?= ucfirst(str_replace('_',' ',$status)) ?> Leads
        <?php else: ?>
          All Leads
        <?php endif; ?>
      </h3>
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:13px;color:var(--gray)"><?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?></span>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:40px"><input type="checkbox" id="checkAll" style="cursor:pointer"></th>
            <th>Contact</th>
            <th>Service / Subject</th>
            <th>Source</th>
            <th>Status</th>
            <th>Received</th>
            <th style="width:90px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($leads): ?>
            <?php foreach ($leads as $idx => $lead): ?>
            <tr style="<?= !$lead['is_read'] ? 'background:rgba(225,29,72,.025)' : '' ?>">
              <td><input type="checkbox" name="ids[]" value="<?= $lead['id'] ?>" onchange="updateBulkBar()"></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:34px;height:34px;border-radius:50%;background:<?= $avatarColors[$idx % count($avatarColors)] ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0">
                    <?= strtoupper(substr($lead['name'],0,1)) ?>
                  </div>
                  <div style="min-width:0">
                    <div style="display:flex;align-items:center;gap:5px">
                      <strong style="font-size:13.5px"><?= e($lead['name']) ?></strong>
                      <?php if (!$lead['is_read']): ?>
                        <span style="width:6px;height:6px;background:var(--rose);border-radius:50%;flex-shrink:0" title="Unread"></span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:var(--gray)"><?= e($lead['email']) ?></div>
                    <?php if ($lead['phone']): ?>
                    <div style="font-size:12px;color:var(--gray2)"><?= e($lead['phone']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="max-width:200px">
                <div style="font-size:13.5px;font-weight:600;color:var(--text)"><?= e(truncate($lead['service'] ?? '', 35)) ?></div>
                <?php if ($lead['company']): ?>
                <div style="font-size:12px;color:var(--gray);margin-top:2px"><i class="fas fa-building" style="font-size:10px"></i> <?= e($lead['company']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="chip"><i class="fas fa-globe"></i> <?= e($lead['source_page'] ?: 'website') ?></span>
              </td>
              <td>
                <span class="badge <?= $statusColors[$lead['status']] ?? 'badge-secondary' ?>" style="display:inline-flex;align-items:center;gap:5px">
                  <span style="width:6px;height:6px;border-radius:50%;background:<?= $statusDots[$lead['status']] ?? '#9ca3af' ?>"></span>
                  <?= ucfirst(str_replace('_',' ',$lead['status'])) ?>
                </span>
              </td>
              <td style="font-size:12px;color:var(--gray);white-space:nowrap">
                <?= timeAgo($lead['created_at']) ?>
                <div style="font-size:11px;color:var(--gray2)"><?= date('M j', strtotime($lead['created_at'])) ?></div>
              </td>
              <td>
                <div style="display:flex;gap:5px">
                  <a href="?view=<?= $lead['id'] ?><?= $status ? '&status='.urlencode($status) : '' ?>" class="btn btn-outline btn-sm btn-icon" title="View Details">
                    <i class="fas fa-eye"></i>
                  </a>
                  <div class="action-menu-wrap">
                    <button type="button" class="btn btn-secondary btn-sm btn-icon" data-toggle-menu="am-<?= $lead['id'] ?>" title="More">
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="action-menu" id="am-<?= $lead['id'] ?>">
                      <a href="?action=edit&id=<?= (int)$lead['id'] ?>" class="action-menu-item"><i class="fas fa-pen"></i> Edit Lead</a>
                      <a href="mailto:<?= e($lead['email']) ?>" class="action-menu-item"><i class="fas fa-envelope"></i> Send Email</a>
                      <?php if ($lead['phone']): ?>
                      <a href="tel:<?= e($lead['phone']) ?>" class="action-menu-item"><i class="fas fa-phone"></i> Call</a>
                      <?php endif; ?>
                      <div class="action-menu-divider"></div>
                      <?php foreach(['contacted','in_progress','converted'] as $qs): ?>
                      <button type="button" onclick="quickStatus(<?= $lead['id'] ?>,'<?= $qs ?>')" class="action-menu-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit">
                        <span style="width:7px;height:7px;border-radius:50%;background:<?= $statusDots[$qs] ?>;display:inline-block"></span>
                        Mark as <?= ucfirst(str_replace('_',' ',$qs)) ?>
                      </button>
                      <?php endforeach; ?>
                      <div class="action-menu-divider"></div>
                      <button type="button" onclick="deleteLead(<?= $lead['id'] ?>)" class="action-menu-item danger" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                  <h3>No leads found</h3>
                  <p><?= ($search || $status || $source) ? 'Try adjusting your filters.' : 'Leads submitted from your website will appear here.' ?></p>
                  <?php if ($search || $status || $source): ?>
                  <a href="<?= ADMIN_URL ?>/pages/leads.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-times"></i> Clear Filters
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($leads): ?>
    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div style="font-size:13px;color:var(--gray)">
        Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $total)) ?> of <?= number_format($total) ?>
      </div>
      <div class="pagination"><?= $pagination ?></div>
    </div>
    <?php endif; ?>
  </div>
</form>
</div>

<!-- Quick Status Form (hidden) -->
<form method="POST" id="quickStatusForm" style="display:none">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="update_status">
  <input type="hidden" name="id" id="qsLeadId">
  <input type="hidden" name="status" id="qsStatus">
</form>
<!-- Quick Delete Form (hidden) -->
<form method="POST" id="quickDeleteForm" style="display:none">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="qdLeadId">
</form>

<script>
function quickStatus(id, status) {
  document.getElementById('qsLeadId').value = id;
  document.getElementById('qsStatus').value = status;
  document.getElementById('quickStatusForm').submit();
}
function deleteLead(id) {
  if (!confirm('Permanently delete this lead? This cannot be undone.')) return;
  document.getElementById('qdLeadId').value = id;
  document.getElementById('quickDeleteForm').submit();
}
function bulkChangeStatus() {
  const ids = [...document.querySelectorAll('input[name="ids[]"]:checked')].map(c=>c.value);
  const ns  = document.getElementById('bulkStatusSelect').value;
  if (!ids.length)  { showToast('Select at least one lead.','warning'); return; }
  if (!ns)          { showToast('Please choose a status.','warning'); return; }
  document.getElementById('bulkActionType').value = 'bulk_status';
  document.getElementById('bulkForm').submit();
}
function bulkDeleteSelected() {
  const ids = [...document.querySelectorAll('input[name="ids[]"]:checked')].map(c=>c.value);
  if (!ids.length) { showToast('Select at least one lead.','warning'); return; }
  if (!confirm('Delete ' + ids.length + ' lead(s)? This cannot be undone.')) return;
  document.getElementById('bulkActionType').value = 'bulk_delete';
  document.getElementById('bulkForm').submit();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
