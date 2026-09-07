<?php
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
}
Auth::requireAdmin();
$admin    = Auth::admin();
$siteName = getSetting('site_name', 'Appsgain Technologies');
$siteEmail= getSetting('contact_email', '');
$sitePhone= getSetting('contact_phone', '');
$siteAddr = getSetting('contact_address', '');
$siteLogo = getSetting('site_logo', '');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: invoices.php'); exit; }

$inv = dbFetchOne("SELECT * FROM invoices WHERE id = ?", [$id]);
if (!$inv) { header('Location: invoices.php'); exit; }

$items    = json_decode($inv['items'] ?? '[]', true) ?: [];
$statusColors = [
    'draft'     => ['#6b7280','#f3f4f6'],
    'sent'      => ['#2563eb','#eff6ff'],
    'paid'      => ['#059669','#ecfdf5'],
    'overdue'   => ['#e11d48','#fff1f2'],
    'cancelled' => ['#6b7280','#f3f4f6'],
];
[$stColor, $stBg] = $statusColors[$inv['status']] ?? ['#6b7280','#f3f4f6'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice <?= htmlspecialchars($inv['invoice_no']) ?> — <?= htmlspecialchars($siteName) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b;-webkit-print-color-adjust:exact;print-color-adjust:exact}

/* ── Toolbar (hidden on print) ── */
.inv-toolbar{
  background:#0d1e3d;padding:14px 40px;display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 12px rgba(0,0,0,.25);
}
.inv-toolbar .tb-left{display:flex;align-items:center;gap:16px}
.inv-toolbar .tb-title{color:#fff;font-size:15px;font-weight:700;letter-spacing:.2px}
.inv-toolbar .tb-sub{color:rgba(255,255,255,.45);font-size:12px;margin-top:2px}
.tb-btn{
  display:inline-flex;align-items:center;gap:8px;
  padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;
  cursor:pointer;border:none;font-family:inherit;text-decoration:none;
  transition:all .2s;
}
.tb-btn-pdf{background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.4)}
.tb-btn-pdf:hover{box-shadow:0 6px 20px rgba(37,99,235,.55);transform:translateY(-1px)}
.tb-btn-back{background:rgba(255,255,255,.08);color:rgba(255,255,255,.75);border:1px solid rgba(255,255,255,.12)}
.tb-btn-back:hover{background:rgba(255,255,255,.14);color:#fff}

/* ── Page wrapper ── */
.inv-page{max-width:900px;margin:36px auto;padding:0 24px 48px}

/* ── Invoice card ── */
.inv-card{
  background:#fff;border-radius:20px;overflow:hidden;
  box-shadow:0 8px 40px rgba(0,0,0,.1),0 1px 0 rgba(0,0,0,.04);
}

/* ── Header ── */
.inv-header{
  padding:40px 48px 32px;
  background:linear-gradient(135deg,#0d1e3d 0%,#1e3a8a 60%,#1d4ed8 100%);
  position:relative;overflow:hidden;
}
.inv-header::before{
  content:'';position:absolute;top:-60px;right:-60px;
  width:280px;height:280px;border-radius:50%;
  background:rgba(255,255,255,.04);
}
.inv-header::after{
  content:'';position:absolute;bottom:-80px;right:40px;
  width:200px;height:200px;border-radius:50%;
  background:rgba(59,130,246,.12);
}
.inv-header-top{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;position:relative;z-index:1}
.inv-brand{display:flex;align-items:center;gap:14px}
.inv-brand-icon{
  width:52px;height:52px;background:rgba(255,255,255,.15);
  border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:22px;font-weight:900;color:#fff;border:2px solid rgba(255,255,255,.2);
  letter-spacing:-1px;
}
.inv-brand-logo{max-height:44px;max-width:160px;object-fit:contain;border-radius:8px}
.inv-brand-info h2{color:#fff;font-size:18px;font-weight:800}
.inv-brand-info p{color:rgba(255,255,255,.55);font-size:12px;margin-top:3px}
.inv-title-block{text-align:right}
.inv-title-block h1{color:#fff;font-size:36px;font-weight:900;letter-spacing:-1px;line-height:1}
.inv-title-block .inv-no{color:rgba(255,255,255,.7);font-size:14px;font-weight:600;margin-top:6px;letter-spacing:.5px}
.inv-status-badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 14px;border-radius:20px;margin-top:8px;
  font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;
  background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);
}
.inv-status-badge i{font-size:9px}
.inv-header-bottom{
  margin-top:28px;padding-top:24px;
  border-top:1px solid rgba(255,255,255,.12);
  display:flex;gap:40px;flex-wrap:wrap;position:relative;z-index:1;
}
.inv-meta-item label{color:rgba(255,255,255,.45);font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:4px;display:block}
.inv-meta-item span{color:#fff;font-size:14px;font-weight:600}

/* ── Body ── */
.inv-body{padding:40px 48px}

/* ── From / To ── */
.inv-parties{display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-bottom:36px}
.inv-party-block label{
  font-size:9.5px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;
  color:#94a3b8;margin-bottom:10px;display:flex;align-items:center;gap:6px;
}
.inv-party-block label::after{content:'';flex:1;height:1px;background:#e2e8f0}
.inv-party-block h3{font-size:16px;font-weight:800;color:#0f172a;margin-bottom:6px}
.inv-party-block p{font-size:13px;color:#64748b;line-height:1.6;margin-bottom:3px}

/* ── Items table ── */
.inv-table-wrap{margin-bottom:32px;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0}
.inv-table{width:100%;border-collapse:collapse}
.inv-table thead tr{background:linear-gradient(90deg,#0d1e3d,#1e3a8a)}
.inv-table thead th{
  padding:13px 18px;text-align:left;
  font-size:10.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
  color:rgba(255,255,255,.7);white-space:nowrap;
}
.inv-table thead th:last-child{text-align:right}
.inv-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .15s}
.inv-table tbody tr:last-child{border-bottom:none}
.inv-table tbody tr:nth-child(even){background:#fafbff}
.inv-table tbody td{padding:13px 18px;font-size:13.5px;color:#334155;vertical-align:middle}
.inv-table tbody td:last-child{text-align:right;font-weight:700;color:#0f172a}
.inv-table tbody .td-desc{font-weight:600;color:#0f172a}
.inv-table tfoot tr{background:#f8faff;border-top:2px solid #e2e8f0}
.inv-table tfoot td{padding:11px 18px;font-size:13px;color:#64748b}
.inv-table tfoot td:last-child{text-align:right;font-weight:700;color:#334155}

/* ── Totals ── */
.inv-totals{display:flex;justify-content:flex-end;margin-bottom:32px}
.inv-totals-box{width:320px}
.inv-totals-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px;color:#64748b}
.inv-totals-row .tl{font-weight:500}
.inv-totals-row .tv{font-weight:700;color:#334155}
.inv-totals-row.discount .tv{color:#059669}
.inv-totals-grand{
  display:flex;justify-content:space-between;align-items:center;
  margin-top:12px;padding:16px 20px;border-radius:14px;
  background:linear-gradient(135deg,#0d1e3d,#1e3a8a);
  color:#fff;
}
.inv-totals-grand .tl{font-size:14px;font-weight:700;color:rgba(255,255,255,.8)}
.inv-totals-grand .tv{font-size:22px;font-weight:900;color:#fff;letter-spacing:-.5px}

/* ── Notes ── */
.inv-notes{
  background:#f8faff;border-radius:14px;padding:20px 24px;margin-bottom:32px;
  border-left:4px solid #3b82f6;
}
.inv-notes h4{font-size:11px;font-weight:800;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px}
.inv-notes p{font-size:13.5px;color:#475569;line-height:1.7}

/* ── Footer ── */
.inv-footer{
  border-top:1px solid #e2e8f0;padding:24px 48px 32px;
  display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;
  background:#fafbff;
}
.inv-footer-left p{font-size:12px;color:#94a3b8;line-height:1.7}
.inv-footer-left strong{color:#64748b}
.inv-footer-stamp{
  text-align:center;
}
.inv-footer-stamp .stamp{
  display:inline-flex;align-items:center;justify-content:center;flex-direction:column;
  width:90px;height:90px;border-radius:50%;
  border:3px solid <?= $stColor ?>;
  color:<?= $stColor ?>;font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;
  background:<?= $stBg ?>;
  line-height:1.3;
}
.inv-footer-stamp .stamp i{font-size:20px;margin-bottom:4px}

/* ── PRINT ── */
@media print{
  .inv-toolbar{display:none!important}
  body{background:#fff}
  .inv-page{margin:0;padding:0;max-width:100%}
  .inv-card{box-shadow:none;border-radius:0}
  .inv-table tbody tr:nth-child(even){background:#f8f8f8!important}
  .inv-header,.inv-table thead tr,.inv-totals-grand{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
@media(max-width:680px){
  .inv-header{padding:28px 24px 22px}
  .inv-header-top{flex-direction:column;gap:16px}
  .inv-title-block{text-align:left}
  .inv-parties{grid-template-columns:1fr}
  .inv-body{padding:28px 24px}
  .inv-footer{padding:20px 24px 24px}
  .inv-toolbar{padding:12px 20px;flex-wrap:wrap;gap:8px}
}
</style>
</head>
<body>

<!-- Toolbar -->
<div class="inv-toolbar">
  <div class="tb-left">
    <div>
      <div class="tb-title">Invoice <?= htmlspecialchars($inv['invoice_no']) ?></div>
      <div class="tb-sub"><?= htmlspecialchars($inv['client_name']) ?> &middot; <?= ucfirst($inv['status']) ?></div>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <a href="invoices.php" class="tb-btn tb-btn-back">
      <i class="fas fa-arrow-left" style="font-size:12px"></i> Back
    </a>
    <!-- Template Selector -->
    <div style="position:relative">
      <button class="tb-btn" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.75);border:1px solid rgba(255,255,255,.15)" onclick="toggleTplMenu()">
        <i class="fas fa-palette" style="font-size:12px"></i> <span id="tplName">Classic Blue</span> <i class="fas fa-chevron-down" style="font-size:10px;margin-left:4px"></i>
      </button>
      <div id="tplMenu" style="display:none;position:absolute;top:calc(100%+6px);right:0;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,.12);min-width:200px;z-index:200;overflow:hidden">
        <?php foreach(['classic-blue'=>['Classic Blue','#1d4ed8'],'midnight'=>['Midnight Dark','#1e293b'],'emerald'=>['Emerald Pro','#059669'],'rose'=>['Rose Premium','#e11d48'],'minimal'=>['Clean Minimal','#334155']] as $k=>[$n,$c]): ?>
        <button onclick="switchTemplate('<?= $k ?>','<?= $n ?>','<?= $c ?>')" style="display:flex;align-items:center;gap:10px;padding:11px 16px;width:100%;border:none;background:none;cursor:pointer;font-size:13.5px;font-weight:600;color:#334155;transition:.15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='none'">
          <span style="width:14px;height:14px;border-radius:50%;background:<?= $c ?>;flex-shrink:0"></span><?= $n ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- WhatsApp Share -->
    <?php $waMsg=urlencode("Hi! Please find attached your invoice {$inv['invoice_no']} for ₹".number_format((float)$inv['total'],2)." from {$siteName}. View/download: ".ADMIN_URL."/pages/print-invoice.php?id={$inv['id']}"); ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$inv['client_phone']??'') ?>?text=<?= $waMsg ?>" target="_blank" class="tb-btn" style="background:#25d366;color:#fff" <?= empty($inv['client_phone'])?'title="No phone on invoice"':'target=_blank rel=noopener"' ?>>
      <i class="fab fa-whatsapp" style="font-size:14px"></i> WhatsApp
    </a>
    <?php if($inv['client_email']): ?>
    <a href="mailto:<?= htmlspecialchars($inv['client_email']) ?>?subject=Invoice <?= htmlspecialchars($inv['invoice_no']) ?> from <?= htmlspecialchars($siteName) ?>&body=Dear <?= htmlspecialchars($inv['client_name']) ?>,%0A%0APlease find your invoice attached. View online: <?= urlencode(ADMIN_URL.'/pages/print-invoice.php?id='.$inv['id']) ?>" class="tb-btn" style="background:rgba(37,99,235,.8);color:#fff">
      <i class="fas fa-envelope" style="font-size:12px"></i> Email
    </a>
    <?php endif; ?>
    <button onclick="window.print()" class="tb-btn tb-btn-pdf">
      <i class="fas fa-print" style="font-size:13px"></i> Print / PDF
    </button>
  </div>
</div>
<script>
/* Template switcher */
const tplGradients={
  'classic-blue':'linear-gradient(135deg,#0d1e3d 0%,#1e3a8a 60%,#1d4ed8 100%)',
  'midnight':'linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#334155 100%)',
  'emerald':'linear-gradient(135deg,#064e3b 0%,#065f46 60%,#059669 100%)',
  'rose':'linear-gradient(135deg,#4c0519 0%,#881337 60%,#e11d48 100%)',
  'minimal':'linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)',
};
const tplTextColor={'minimal':'#1e293b'};
function switchTemplate(key,name,color){
  document.getElementById('tplName').textContent=name;
  document.getElementById('tplMenu').style.display='none';
  const hdr=document.querySelector('.inv-header');
  if(hdr){hdr.style.background=tplGradients[key]||tplGradients['classic-blue'];}
  const tbl=document.querySelector('.inv-table thead tr');
  if(tbl){tbl.style.background=color;}
  const totals=document.querySelector('.inv-totals');
  if(totals){totals.style.background=color;}
  document.querySelectorAll('.inv-total-line.grand').forEach(el=>{if(el)el.style.background=color;});
}
function toggleTplMenu(){
  const m=document.getElementById('tplMenu');
  m.style.display=m.style.display==='none'?'block':'none';
}
document.addEventListener('click',function(e){
  if(!e.target.closest('#tplMenu')&&!e.target.closest('[onclick="toggleTplMenu()"]'))
    document.getElementById('tplMenu').style.display='none';
});
</script>

<!-- Invoice Page -->
<div class="inv-page">
<div class="inv-card">

  <!-- Header -->
  <div class="inv-header">
    <div class="inv-header-top">
      <div class="inv-brand">
        <?php if ($siteLogo): ?>
          <img src="<?= htmlspecialchars(getImageUrl($siteLogo)) ?>" alt="" class="inv-brand-logo">
        <?php else: ?>
          <div class="inv-brand-icon">AG</div>
        <?php endif; ?>
        <div class="inv-brand-info">
          <h2><?= htmlspecialchars($siteName) ?></h2>
          <p>Professional Invoice</p>
        </div>
      </div>
      <div class="inv-title-block">
        <h1>INVOICE</h1>
        <div class="inv-no"><?= htmlspecialchars($inv['invoice_no']) ?></div>
        <div class="inv-status-badge">
          <?php
          $statusIcons = ['draft'=>'fa-pencil','sent'=>'fa-paper-plane','paid'=>'fa-check-circle','overdue'=>'fa-exclamation-circle','cancelled'=>'fa-times-circle'];
          ?>
          <i class="fas <?= $statusIcons[$inv['status']] ?? 'fa-circle' ?>"></i>
          <?= ucfirst($inv['status']) ?>
        </div>
      </div>
    </div>
    <div class="inv-header-bottom">
      <div class="inv-meta-item">
        <label>Invoice Date</label>
        <span><?= date('d M Y', strtotime($inv['created_at'])) ?></span>
      </div>
      <?php if ($inv['due_date']): ?>
      <div class="inv-meta-item">
        <label>Due Date</label>
        <span style="<?= ($inv['status']==='overdue')?'color:#fca5a5':'' ?>"><?= date('d M Y', strtotime($inv['due_date'])) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($inv['paid_at']): ?>
      <div class="inv-meta-item">
        <label>Paid On</label>
        <span style="color:#86efac"><?= date('d M Y', strtotime($inv['paid_at'])) ?></span>
      </div>
      <?php endif; ?>
      <div class="inv-meta-item">
        <label>Amount Due</label>
        <span style="font-size:18px;font-weight:900">₹<?= number_format($inv['total'], 2) ?></span>
      </div>
    </div>
  </div>

  <!-- Body -->
  <div class="inv-body">

    <!-- From / To -->
    <div class="inv-parties">
      <div class="inv-party-block">
        <label><i class="fas fa-building" style="color:#3b82f6"></i> From</label>
        <h3><?= htmlspecialchars($siteName) ?></h3>
        <?php if ($siteAddr): ?><p><?= nl2br(htmlspecialchars($siteAddr)) ?></p><?php endif; ?>
        <?php if ($siteEmail): ?><p><i class="fas fa-envelope" style="color:#94a3b8;width:14px"></i> <?= htmlspecialchars($siteEmail) ?></p><?php endif; ?>
        <?php if ($sitePhone): ?><p><i class="fas fa-phone" style="color:#94a3b8;width:14px"></i> <?= htmlspecialchars($sitePhone) ?></p><?php endif; ?>
      </div>
      <div class="inv-party-block">
        <label><i class="fas fa-user" style="color:#3b82f6"></i> Bill To</label>
        <h3><?= htmlspecialchars($inv['client_name']) ?></h3>
        <?php if ($inv['client_address']): ?><p><?= nl2br(htmlspecialchars($inv['client_address'])) ?></p><?php endif; ?>
        <?php if ($inv['client_email']): ?><p><i class="fas fa-envelope" style="color:#94a3b8;width:14px"></i> <?= htmlspecialchars($inv['client_email']) ?></p><?php endif; ?>
        <?php if ($inv['client_phone']): ?><p><i class="fas fa-phone" style="color:#94a3b8;width:14px"></i> <?= htmlspecialchars($inv['client_phone']) ?></p><?php endif; ?>
      </div>
    </div>

    <!-- Items Table -->
    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>Description</th>
            <th style="text-align:center;width:80px">Qty</th>
            <th style="text-align:right;width:120px">Rate</th>
            <th style="width:130px">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $item): ?>
          <tr>
            <td style="color:#94a3b8;font-size:12px"><?= ($i+1) ?></td>
            <td class="td-desc"><?= htmlspecialchars($item['desc'] ?? '') ?></td>
            <td style="text-align:center;color:#64748b"><?= number_format((float)($item['qty'] ?? 1), 2) ?></td>
            <td style="text-align:right">₹<?= number_format((float)($item['rate'] ?? 0), 2) ?></td>
            <td>₹<?= number_format((float)($item['amount'] ?? 0), 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
          <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px">No items</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div class="inv-totals">
      <div class="inv-totals-box">
        <div class="inv-totals-row">
          <span class="tl">Subtotal</span>
          <span class="tv">₹<?= number_format($inv['subtotal'], 2) ?></span>
        </div>
        <?php if ($inv['discount'] > 0): ?>
        <div class="inv-totals-row discount">
          <span class="tl">Discount</span>
          <span class="tv">−₹<?= number_format($inv['discount'], 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="inv-totals-row">
          <span class="tl">GST / Tax (<?= number_format($inv['tax_rate'], 1) ?>%)</span>
          <span class="tv">₹<?= number_format($inv['tax_amount'], 2) ?></span>
        </div>
        <div class="inv-totals-grand">
          <span class="tl">Total Amount</span>
          <span class="tv">₹<?= number_format($inv['total'], 2) ?></span>
        </div>
      </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($inv['notes'])): ?>
    <div class="inv-notes">
      <h4><i class="fas fa-sticky-note"></i> Notes &amp; Terms</h4>
      <p><?= nl2br(htmlspecialchars($inv['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Default payment note -->
    <div class="inv-notes" style="background:#f0fdf4;border-left-color:#059669">
      <h4 style="color:#86efac"><i class="fas fa-info-circle"></i> Payment Information</h4>
      <p style="color:#166534">Please make payment by the due date. For queries, contact us at <?= htmlspecialchars($siteEmail ?: 'our support email') ?>. Thank you for your business!</p>
    </div>

  </div><!-- /inv-body -->

  <!-- Footer -->
  <div class="inv-footer">
    <div class="inv-footer-left">
      <p><strong><?= htmlspecialchars($siteName) ?></strong></p>
      <?php if ($siteEmail): ?><p><?= htmlspecialchars($siteEmail) ?></p><?php endif; ?>
      <?php if ($sitePhone): ?><p><?= htmlspecialchars($sitePhone) ?></p><?php endif; ?>
      <p style="margin-top:6px;color:#cbd5e1">This is a computer-generated invoice and does not require a physical signature.</p>
    </div>
    <div class="inv-footer-stamp">
      <div class="stamp">
        <i class="fas <?= $statusIcons[$inv['status']] ?? 'fa-circle' ?>"></i>
        <?= strtoupper($inv['status']) ?>
      </div>
    </div>
  </div>

</div><!-- /inv-card -->
</div><!-- /inv-page -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</body>
</html>
