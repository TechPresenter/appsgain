<?php
require_once dirname(__DIR__) . '/includes/admin-layout.php';
$adminPage  = 'invoices';
$adminTitle = 'Invoices';

/* Auto-create invoices table */
try {
    dbExecute("CREATE TABLE IF NOT EXISTS invoices (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        invoice_no   VARCHAR(50) NOT NULL UNIQUE,
        client_name  VARCHAR(200) NOT NULL,
        client_email VARCHAR(200) DEFAULT NULL,
        client_phone VARCHAR(50)  DEFAULT NULL,
        client_address TEXT DEFAULT NULL,
        items        JSON DEFAULT NULL,
        subtotal     DECIMAL(12,2) DEFAULT 0.00,
        tax_rate     DECIMAL(5,2) DEFAULT 18.00,
        tax_amount   DECIMAL(12,2) DEFAULT 0.00,
        discount     DECIMAL(12,2) DEFAULT 0.00,
        total        DECIMAL(12,2) DEFAULT 0.00,
        status       ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
        due_date     DATE DEFAULT NULL,
        paid_at      DATE DEFAULT NULL,
        notes        TEXT DEFAULT NULL,
        created_by   INT DEFAULT NULL,
        created_at   DATETIME DEFAULT NOW(),
        updated_at   DATETIME DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── Generate invoice number ── */
function generateInvoiceNo(): string {
    $prefix = 'AG-' . date('Y');
    $last   = dbFetchValue("SELECT invoice_no FROM invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1", ["$prefix%"]);
    $num    = $last ? ((int)substr($last, -4) + 1) : 1;
    return $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = sanitizeInput($_POST['action'] ?? '');

    if (in_array($act, ['create','update'])) {
        $id          = (int)($_POST['id'] ?? 0);
        $clientName  = sanitizeInput($_POST['client_name'] ?? '');
        $clientEmail = sanitizeInput($_POST['client_email'] ?? '');
        $clientPhone = sanitizeInput($_POST['client_phone'] ?? '');
        $clientAddr  = sanitizeInput($_POST['client_address'] ?? '');
        $notes       = sanitizeInput($_POST['notes'] ?? '');
        $dueDate     = sanitizeInput($_POST['due_date'] ?? '') ?: null;
        $status      = in_array($_POST['status'] ?? '', ['draft','sent','paid','overdue','cancelled']) ? $_POST['status'] : 'draft';
        $taxRate     = min(100, max(0, (float)($_POST['tax_rate'] ?? 18)));
        $discount    = max(0, (float)($_POST['discount'] ?? 0));

        /* Parse items */
        $descs = $_POST['item_desc'] ?? [];
        $qtys  = $_POST['item_qty'] ?? [];
        $rates = $_POST['item_rate'] ?? [];
        $items = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            if (!trim($desc)) continue;
            $qty  = max(0, (float)($qtys[$i] ?? 1));
            $rate = max(0, (float)($rates[$i] ?? 0));
            $amt  = $qty * $rate;
            $subtotal += $amt;
            $items[] = ['desc'=>$desc, 'qty'=>$qty, 'rate'=>$rate, 'amount'=>$amt];
        }
        $taxAmt = ($subtotal - $discount) * $taxRate / 100;
        $total  = $subtotal - $discount + $taxAmt;

        $data = [
            'client_name' => $clientName, 'client_email' => $clientEmail,
            'client_phone'=> $clientPhone, 'client_address' => $clientAddr,
            'items' => json_encode($items), 'subtotal' => $subtotal,
            'tax_rate' => $taxRate, 'tax_amount' => $taxAmt,
            'discount' => $discount, 'total' => $total,
            'status' => $status, 'due_date' => $dueDate,
            'notes' => $notes, 'created_by' => $admin['id'],
        ];

        if ($act === 'create') {
            $data['invoice_no'] = generateInvoiceNo();
            $newId = dbInsertRow('invoices', $data);
            logActivity('create', 'invoice', 'Created invoice '.$data['invoice_no'].' for '.$clientName);
            setFlash('success', 'Invoice '.$data['invoice_no'].' created.');
        } else {
            dbUpdateRow('invoices', $data, 'id = ?', [$id]);
            logActivity('update', 'invoice', 'Updated invoice #'.$id.' for '.$clientName);
            setFlash('success', 'Invoice updated.');
        }
        header('Location: invoices.php'); exit;
    }

    if ($act === 'mark_paid') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbExecute("UPDATE invoices SET status='paid', paid_at=NOW(), updated_at=NOW() WHERE id=?", [$id]);
            logActivity('update', 'invoice', 'Marked invoice #'.$id.' as paid');
            setFlash('success', 'Invoice marked as paid.');
        }
        header('Location: invoices.php'); exit;
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbExecute("DELETE FROM invoices WHERE id=?", [$id]);
            logActivity('delete', 'invoice', 'Deleted invoice #'.$id);
            setFlash('success', 'Invoice deleted.');
        }
        header('Location: invoices.php'); exit;
    }
}

/* ── Stats ── */
$totalInvoices = (int) dbFetchValue("SELECT COUNT(*) FROM invoices");
$totalPaid     = (float) dbFetchValue("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status='paid'");
$totalPending  = (float) dbFetchValue("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status='sent'");
$totalOverdue  = (int) dbFetchValue("SELECT COUNT(*) FROM invoices WHERE status='overdue' OR (status='sent' AND due_date < CURDATE())");
$countPaid     = (int) dbFetchValue("SELECT COUNT(*) FROM invoices WHERE status='paid'");

/* Update overdue automatically */
try { dbExecute("UPDATE invoices SET status='overdue' WHERE status='sent' AND due_date < CURDATE()"); } catch(Exception $e){}

/* ── List ── */
$status = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$where = ['1=1'];
$params = [];
if ($status) { $where[] = 'status=?'; $params[] = $status; }
if ($search) { $where[] = '(client_name LIKE ? OR invoice_no LIKE ? OR client_email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$whereStr = implode(' AND ', $where);
$total = (int) dbFetchValue("SELECT COUNT(*) FROM invoices WHERE $whereStr", $params);
$offset = ($page-1)*$perPage;
$invoices = dbFetchAll("SELECT * FROM invoices WHERE $whereStr ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
$totalPages = (int)ceil($total/$perPage);

/* Edit invoice load */
$editInv = null;
if (!empty($_GET['edit'])) {
    $editInv = dbFetchOne("SELECT * FROM invoices WHERE id=?", [(int)$_GET['edit']]);
    if ($editInv) $editInv['items_arr'] = json_decode($editInv['items'] ?? '[]', true) ?: [];
}

$statusBadge = ['draft'=>'badge-secondary','sent'=>'badge-info','paid'=>'badge-success','overdue'=>'badge-danger','cancelled'=>'badge-secondary'];
$statusLabel = ['draft'=>'Draft','sent'=>'Sent','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled'];
$tabCounts = [
    '' => $totalInvoices,
    'paid' => $countPaid,
    'sent' => (int)dbFetchValue("SELECT COUNT(*) FROM invoices WHERE status='sent'"),
    'overdue' => $totalOverdue,
    'draft' => (int)dbFetchValue("SELECT COUNT(*) FROM invoices WHERE status='draft'"),
];

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-file-invoice-dollar" style="color:var(--violet)"></i> Invoices</h1>
    <p class="page-subtitle">Manage client invoices and track payments.</p>
  </div>
  <div class="page-header-actions">
    <a href="?create=1" class="btn btn-primary"><i class="fas fa-plus"></i> New Invoice</a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <?php $istats=[['fa-file-invoice','#7c3aed','Total Invoices','rgba(124,58,237,.1)',$totalInvoices,'All time'],['fa-check-circle','#059669','Revenue (Paid)','rgba(5,150,105,.1)','₹'.number_format($totalPaid),'Collected'],['fa-clock','#2563eb','Pending','rgba(37,99,235,.1)','₹'.number_format($totalPending),'Awaiting payment'],['fa-exclamation-triangle','#e11d48','Overdue','rgba(225,29,72,.1)',$totalOverdue,'Needs attention']];
  foreach($istats as [$ic,$cl,$lb,$bg,$vl,$ch]): ?>
  <div class="stat-card" style="--stat-color:<?= $bg ?>;--stat-shadow:<?= $cl ?>44">
    <div class="stat-icon" style="background:<?= $cl ?>"><i class="fas <?= $ic ?>"></i></div>
    <div class="stat-info">
      <div class="label"><?= $lb ?></div>
      <div class="value"><?= $vl ?></div>
      <div class="change neutral"><?= $ch ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!empty($_GET['create']) || $editInv): ?>
<!-- Create/Edit Form -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <h3><i class="fas fa-<?= $editInv?'edit':'plus-circle' ?>" style="color:var(--violet)"></i> <?= $editInv ? 'Edit Invoice #'.e($editInv['invoice_no']) : 'New Invoice' ?></h3>
    <a href="invoices.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="POST" id="invoiceForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $editInv ? 'update' : 'create' ?>">
      <?php if ($editInv): ?><input type="hidden" name="id" value="<?= $editInv['id'] ?>"><?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>Client Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="client_name" required value="<?= e($editInv['client_name'] ?? '') ?>" placeholder="Company or individual name">
        </div>
        <div class="form-group">
          <label>Client Email</label>
          <input type="email" class="form-control" name="client_email" value="<?= e($editInv['client_email'] ?? '') ?>" placeholder="client@email.com">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="tel" class="form-control" name="client_phone" value="<?= e($editInv['client_phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" name="status">
            <?php foreach ($statusLabel as $sv => $sl): ?>
            <option value="<?= $sv ?>" <?= ($editInv['status'] ?? 'draft') === $sv ? 'selected' : '' ?>><?= $sl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Client Address</label>
          <textarea class="form-control" name="client_address" rows="2"><?= e($editInv['client_address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" class="form-control" name="due_date" value="<?= e($editInv['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
        </div>
      </div>

      <!-- Line items -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:12.5px;font-weight:700;color:var(--primary);margin-bottom:10px;text-transform:uppercase;letter-spacing:.4px">Invoice Items</label>
        <div id="itemsTable" style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden">
          <div style="display:grid;grid-template-columns:3fr 1fr 1.2fr 1.2fr auto;background:var(--light);padding:10px 14px;border-bottom:1px solid var(--border)">
            <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.6px">Description</div>
            <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.6px">Qty</div>
            <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.6px">Rate (₹)</div>
            <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.6px">Amount</div>
            <div></div>
          </div>
          <div id="itemRows">
            <?php
            $existingItems = $editInv['items_arr'] ?? [['desc'=>'','qty'=>1,'rate'=>0,'amount'=>0]];
            foreach ($existingItems as $ii => $it):
            ?>
            <div class="item-row" style="display:grid;grid-template-columns:3fr 1fr 1.2fr 1.2fr auto;gap:8px;padding:10px 14px;border-bottom:1px solid rgba(0,0,0,.04);align-items:center">
              <input type="text" class="form-control" name="item_desc[]" value="<?= e($it['desc'] ?? '') ?>" placeholder="Service description">
              <input type="number" class="form-control item-qty" name="item_qty[]" value="<?= (float)($it['qty']??1) ?>" min="0" step="0.1" oninput="calcRow(this)">
              <input type="number" class="form-control item-rate" name="item_rate[]" value="<?= (float)($it['rate']??0) ?>" min="0" step="0.01" oninput="calcRow(this)">
              <input type="text" class="form-control item-amt" name="item_amount[]" value="<?= number_format((float)($it['amount']??0),2) ?>" readonly style="background:var(--light);font-weight:700">
              <button type="button" onclick="removeRow(this)" style="background:rgba(225,29,72,.1);border:none;color:var(--rose);width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:13px"><i class="fas fa-times"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="padding:10px 14px">
            <button type="button" class="btn btn-sm btn-secondary" onclick="addRow()"><i class="fas fa-plus"></i> Add Item</button>
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div style="display:flex;justify-content:flex-end">
        <div style="width:320px;background:var(--light);border-radius:12px;padding:18px">
          <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px"><span style="color:var(--gray)">Subtotal</span><span id="displaySubtotal" style="font-weight:700">₹0.00</span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:14px">
            <span style="color:var(--gray)">Discount (₹)</span>
            <input type="number" name="discount" id="discountInput" min="0" step="0.01" value="<?= (float)($editInv['discount']??0) ?>" style="width:100px;padding:5px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;text-align:right" oninput="calcTotals()">
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:14px">
            <span style="color:var(--gray)">Tax Rate (%)</span>
            <input type="number" name="tax_rate" id="taxRateInput" min="0" max="100" step="0.01" value="<?= (float)($editInv['tax_rate']??18) ?>" style="width:70px;padding:5px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;text-align:right" oninput="calcTotals()">
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px"><span style="color:var(--gray)">GST/Tax</span><span id="displayTax" style="font-weight:700">₹0.00</span></div>
          <div style="height:1px;background:var(--border);margin:12px 0"></div>
          <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:900;color:var(--primary)"><span>Total</span><span id="displayTotal">₹0.00</span></div>
        </div>
      </div>

      <div class="form-group" style="margin-top:20px">
        <label>Notes / Terms</label>
        <textarea class="form-control" name="notes" rows="2" placeholder="Payment terms, bank details, etc."><?= e($editInv['notes'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editInv ? 'Update' : 'Create' ?> Invoice</button>
        <a href="invoices.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Status tabs -->
<div class="status-tabs">
  <?php $tabInfo=['=>All','paid'=>'Paid','sent'=>'Sent','overdue'=>'Overdue','draft'=>'Draft'];
  foreach($tabInfo as $tv=>$tl): $activeTab = $tv==='' ? (!$status) : ($status===$tv); ?>
  <a href="?status=<?= $tv ?>&search=<?= urlencode($search) ?>" class="status-tab <?= $activeTab?'active':'' ?>"><?= $tl ?> <span class="tab-count"><?= $tabCounts[$tv] ?? 0 ?></span></a>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:10px;flex:1">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <div class="search-wrap" style="flex:1"><i class="fas fa-search"></i><input class="search-input" name="search" value="<?= e($search) ?>" placeholder="Search by name, invoice no, email…"></div>
    <button class="btn btn-secondary"><i class="fas fa-filter"></i> Search</button>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($invoices)): ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-file-invoice"></i></div>
      <h3>No Invoices Found</h3>
      <p>Create your first invoice to start tracking client payments.</p>
      <a href="?create=1" class="btn btn-primary"><i class="fas fa-plus"></i> Create Invoice</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Invoice #</th><th>Client</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($invoices as $inv): $isOverdue = ($inv['status']==='sent' && $inv['due_date'] && strtotime($inv['due_date']) < time()); ?>
          <tr>
            <td><strong style="color:var(--primary);font-family:'Figtree',sans-serif"><?= e($inv['invoice_no']) ?></strong></td>
            <td>
              <div style="font-weight:700;color:var(--primary)"><?= e($inv['client_name']) ?></div>
              <?php if ($inv['client_email']): ?><div style="font-size:12px;color:var(--gray)"><?= e($inv['client_email']) ?></div><?php endif; ?>
            </td>
            <td><strong style="font-size:15px;color:var(--primary)">₹<?= number_format((float)$inv['total'],2) ?></strong></td>
            <td>
              <span class="badge <?= $statusBadge[$isOverdue?'overdue':$inv['status']] ?? 'badge-secondary' ?>">
                <?= $statusLabel[$isOverdue?'overdue':$inv['status']] ?? ucfirst($inv['status']) ?>
              </span>
            </td>
            <td style="font-size:13px;color:<?= $isOverdue?'var(--rose)':'var(--gray)' ?>;font-weight:<?= $isOverdue?'700':'400' ?>">
              <?= $inv['due_date'] ? date('M d, Y', strtotime($inv['due_date'])) : '—' ?>
              <?php if ($isOverdue): ?><span style="display:block;font-size:10.5px;color:var(--rose)">Overdue!</span><?php endif; ?>
            </td>
            <td style="font-size:12.5px;color:var(--gray)"><?= date('M d, Y', strtotime($inv['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="print-invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-sm btn-secondary" title="Print / PDF" style="color:var(--blue)"><i class="fas fa-print"></i></a>
                <a href="?edit=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                <?php if ($inv['status'] !== 'paid' && $inv['status'] !== 'cancelled'): ?>
                <form method="POST" style="display:inline"><input type="hidden" name="id" value="<?= $inv['id'] ?>"><input type="hidden" name="action" value="mark_paid"><?= csrfField() ?><button class="btn btn-sm btn-success" title="Mark Paid"><i class="fas fa-check"></i></button></form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete invoice <?= e($inv['invoice_no']) ?>?')"><input type="hidden" name="id" value="<?= $inv['id'] ?>"><input type="hidden" name="action" value="delete"><?= csrfField() ?><button class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="padding:16px 22px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <div style="font-size:13px;color:var(--gray)">Showing <?= ($offset+1) ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?></div>
      <div class="pagination">
        <?php for($p=1;$p<=$totalPages;$p++): ?>
        <a href="?p=<?= $p ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>" class="<?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
function calcRow(input) {
  var row  = input.closest('.item-row');
  var qty  = parseFloat(row.querySelector('.item-qty').value) || 0;
  var rate = parseFloat(row.querySelector('.item-rate').value) || 0;
  row.querySelector('.item-amt').value = (qty * rate).toFixed(2);
  calcTotals();
}
function calcTotals() {
  var subtotal = 0;
  document.querySelectorAll('.item-amt').forEach(function(el){ subtotal += parseFloat(el.value)||0; });
  var disc    = parseFloat(document.getElementById('discountInput').value)||0;
  var taxRate = parseFloat(document.getElementById('taxRateInput').value)||0;
  var taxAmt  = (subtotal - disc) * taxRate / 100;
  var total   = subtotal - disc + taxAmt;
  document.getElementById('displaySubtotal').textContent = '₹'+subtotal.toFixed(2);
  document.getElementById('displayTax').textContent = '₹'+taxAmt.toFixed(2);
  document.getElementById('displayTotal').textContent = '₹'+total.toFixed(2);
}
function addRow() {
  var row = document.createElement('div');
  row.className = 'item-row';
  row.style.cssText = 'display:grid;grid-template-columns:3fr 1fr 1.2fr 1.2fr auto;gap:8px;padding:10px 14px;border-bottom:1px solid rgba(0,0,0,.04);align-items:center';
  row.innerHTML = '<input type="text" class="form-control" name="item_desc[]" placeholder="Description">'
    + '<input type="number" class="form-control item-qty" name="item_qty[]" value="1" min="0" step="0.1" oninput="calcRow(this)">'
    + '<input type="number" class="form-control item-rate" name="item_rate[]" value="0" min="0" step="0.01" oninput="calcRow(this)">'
    + '<input type="text" class="form-control item-amt" name="item_amount[]" value="0.00" readonly style="background:var(--light);font-weight:700">'
    + '<button type="button" onclick="removeRow(this)" style="background:rgba(225,29,72,.1);border:none;color:var(--rose);width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:13px"><i class="fas fa-times"></i></button>';
  document.getElementById('itemRows').appendChild(row);
}
function removeRow(btn) {
  if (document.querySelectorAll('.item-row').length > 1) { btn.closest('.item-row').remove(); calcTotals(); }
}
// Init totals on load
document.addEventListener('DOMContentLoaded', function(){ calcTotals(); });
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
