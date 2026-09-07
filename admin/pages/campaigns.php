<?php
require_once dirname(__DIR__) . '/includes/admin-layout.php';
$adminPage  = 'campaigns';
$adminTitle = 'Campaign Manager';

/* Auto-create campaigns table */
try {
    dbExecute("CREATE TABLE IF NOT EXISTS campaigns (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(200) NOT NULL,
        subject      VARCHAR(255) NOT NULL,
        body         TEXT,
        status       ENUM('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
        send_to      ENUM('all','active','segment') DEFAULT 'all',
        segment_tag  VARCHAR(100) DEFAULT NULL,
        scheduled_at DATETIME DEFAULT NULL,
        sent_at      DATETIME DEFAULT NULL,
        total_sent   INT DEFAULT 0,
        total_opened INT DEFAULT 0,
        total_clicked INT DEFAULT 0,
        created_by   INT DEFAULT NULL,
        created_at   DATETIME DEFAULT NOW(),
        updated_at   DATETIME DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = sanitizeInput($_POST['action'] ?? '');

    if ($act === 'create') {
        $name     = sanitizeInput($_POST['name'] ?? '');
        $subject  = sanitizeInput($_POST['subject'] ?? '');
        $body     = $_POST['body'] ?? '';
        $sendTo   = in_array($_POST['send_to'] ?? '', ['all','active','segment']) ? $_POST['send_to'] : 'all';
        $schedAt  = !empty($_POST['scheduled_at']) ? sanitizeInput($_POST['scheduled_at']) : null;

        if ($name && $subject) {
            dbInsertRow('campaigns', [
                'name' => $name, 'subject' => $subject, 'body' => $body,
                'status' => $schedAt ? 'scheduled' : 'draft',
                'send_to' => $sendTo, 'scheduled_at' => $schedAt,
                'created_by' => $admin['id'],
            ]);
            logActivity('create', 'campaign', 'Created campaign: '.$name);
            setFlash('success', 'Campaign "'.e($name).'" created.');
        }
        header('Location: campaigns.php'); exit;
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbExecute("DELETE FROM campaigns WHERE id=?", [$id]);
            logActivity('delete', 'campaign', 'Deleted campaign #'.$id);
            setFlash('success', 'Campaign deleted.');
        }
        header('Location: campaigns.php'); exit;
    }

    if ($act === 'send_test') {
        /* Mark as sending — real SMTP send would go here */
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbExecute("UPDATE campaigns SET status='sending', sent_at=NOW() WHERE id=?", [$id]);
            setFlash('info', 'Campaign queued for sending (SMTP integration required).');
        }
        header('Location: campaigns.php'); exit;
    }

    if ($act === 'duplicate') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $id ? dbFetchOne("SELECT * FROM campaigns WHERE id=?", [$id]) : null;
        if ($row) {
            dbInsertRow('campaigns', [
                'name' => $row['name'].' (copy)', 'subject' => $row['subject'],
                'body' => $row['body'], 'status' => 'draft',
                'send_to' => $row['send_to'], 'created_by' => $admin['id'],
            ]);
            setFlash('success', 'Campaign duplicated.');
        }
        header('Location: campaigns.php'); exit;
    }
}

/* ── Stats ── */
$stats = [
    'total'    => (int) dbFetchValue("SELECT COUNT(*) FROM campaigns"),
    'sent'     => (int) dbFetchValue("SELECT COUNT(*) FROM campaigns WHERE status='sent'"),
    'draft'    => (int) dbFetchValue("SELECT COUNT(*) FROM campaigns WHERE status='draft'"),
    'scheduled'=> (int) dbFetchValue("SELECT COUNT(*) FROM campaigns WHERE status='scheduled'"),
];
$totalOpened = (int) dbFetchValue("SELECT COALESCE(SUM(total_opened),0) FROM campaigns WHERE status='sent'");
$totalSent   = (int) dbFetchValue("SELECT COALESCE(SUM(total_sent),0) FROM campaigns WHERE status='sent'");
$openRate = $totalSent > 0 ? round($totalOpened / $totalSent * 100, 1) : 0;

/* ── Newsletter subscriber count ── */
$subCount = (int) dbFetchValue("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='active'");

/* ── List ── */
$status = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$where = ['1=1'];
$params = [];
if ($status) { $where[] = 'status=?'; $params[] = $status; }
if ($search) { $where[] = '(name LIKE ? OR subject LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);
$total = (int) dbFetchValue("SELECT COUNT(*) FROM campaigns WHERE $whereStr", $params);
$offset = ($page-1)*$perPage;
$campaigns = dbFetchAll("SELECT * FROM campaigns WHERE $whereStr ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
$totalPages = (int) ceil($total / $perPage);

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-mail-bulk" style="color:var(--violet)"></i> Campaign Manager</h1>
    <p class="page-subtitle">Create and manage email marketing campaigns.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('open')">
      <i class="fas fa-plus"></i> New Campaign
    </button>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr)">
  <?php $cstats=[['fa-mail-bulk','#7c3aed','Total','rgba(124,58,237,.1)',$stats['total'],'All campaigns'],['fa-check-circle','#059669','Sent','rgba(5,150,105,.1)',$stats['sent'],'Delivered'],['fa-edit','#2563eb','Drafts','rgba(37,99,235,.1)',$stats['draft'],'Not sent yet'],['fa-clock','#d97706','Scheduled','rgba(217,119,6,.1)',$stats['scheduled'],'Pending'],['fa-users','#0891b2','Subscribers','rgba(8,145,178,.1)',$subCount,'Active list']];
  foreach($cstats as [$ic,$cl,$lb,$bg,$vl,$ch]): ?>
  <div class="stat-card" style="--stat-color:<?= $bg ?>;--stat-shadow:<?= $cl ?>44">
    <div class="stat-icon" style="background:<?= $cl ?>"><i class="fas <?= $ic ?>"></i></div>
    <div class="stat-info">
      <div class="label"><?= $lb ?></div>
      <div class="value"><?= number_format($vl) ?></div>
      <div class="change neutral"><?= $ch ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Open rate banner -->
<?php if ($totalSent > 0): ?>
<div style="background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(37,99,235,.05));border:1px solid rgba(124,58,237,.15);border-radius:14px;padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
  <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Total Emails Sent</div><div style="font-size:22px;font-weight:900;color:var(--primary)"><?= number_format($totalSent) ?></div></div>
  <div style="width:1px;height:44px;background:var(--border)"></div>
  <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Opened</div><div style="font-size:22px;font-weight:900;color:var(--emerald)"><?= number_format($totalOpened) ?></div></div>
  <div style="width:1px;height:44px;background:var(--border)"></div>
  <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Open Rate</div><div style="font-size:22px;font-weight:900;color:var(--violet)"><?= $openRate ?>%</div></div>
  <div style="flex:1;min-width:160px">
    <div style="height:6px;background:var(--light2);border-radius:4px;overflow:hidden">
      <div style="width:<?= $openRate ?>%;height:100%;background:linear-gradient(90deg,var(--violet),var(--blue));border-radius:4px;transition:.8s"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filter bar -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
    <div class="search-wrap"><i class="fas fa-search"></i><input class="search-input" name="search" value="<?= e($search) ?>" placeholder="Search campaigns…"></div>
    <select class="form-control" name="status" style="width:160px" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach(['draft'=>'Draft','scheduled'=>'Scheduled','sent'=>'Sent','sending'=>'Sending'] as $sv=>$sl): ?>
      <option value="<?= $sv ?>" <?= $status===$sv?'selected':'' ?>><?= $sl ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-mail-bulk"></i></div>
      <h3>No Campaigns Yet</h3>
      <p>Create your first email campaign to start engaging with your subscribers.</p>
      <button class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('open')"><i class="fas fa-plus"></i> Create Campaign</button>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Campaign</th><th>Status</th><th>Recipients</th><th>Sent</th><th>Opened</th><th>Open Rate</th><th>Scheduled</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php $statusBadge=['draft'=>'badge-secondary','scheduled'=>'badge-warning','sending'=>'badge-info','sent'=>'badge-success','paused'=>'badge-danger'];
          foreach ($campaigns as $c):
            $sent = (int)$c['total_sent'];
            $opn  = (int)$c['total_opened'];
            $oRate = $sent > 0 ? round($opn/$sent*100,1) : 0;
          ?>
          <tr>
            <td>
              <div style="font-weight:700;color:var(--primary);margin-bottom:3px"><?= e($c['name']) ?></div>
              <div style="font-size:12px;color:var(--gray)"><?= e(substr($c['subject'],0,60)) ?><?= strlen($c['subject'])>60?'…':'' ?></div>
            </td>
            <td><span class="badge <?= $statusBadge[$c['status']] ?? 'badge-secondary' ?>"><?= ucfirst($c['status']) ?></span></td>
            <td><?= e(ucfirst($c['send_to'])) ?></td>
            <td><?= number_format($sent) ?></td>
            <td><?= number_format($opn) ?></td>
            <td>
              <?php if ($sent > 0): ?>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="width:50px;height:5px;background:var(--light2);border-radius:4px;overflow:hidden">
                  <div style="width:<?= $oRate ?>%;height:100%;background:var(--emerald);border-radius:4px"></div>
                </div>
                <span style="font-size:12px;font-weight:700;color:var(--primary)"><?= $oRate ?>%</span>
              </div>
              <?php else: ?><span style="color:var(--gray2);font-size:12px">—</span><?php endif; ?>
            </td>
            <td style="font-size:12.5px;color:var(--gray)"><?= $c['scheduled_at'] ? date('M d, Y H:i', strtotime($c['scheduled_at'])) : '—' ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <?php if ($c['status'] === 'draft'): ?>
                <form method="POST" style="display:inline"><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="action" value="send_test"><?= csrfField() ?><button class="btn btn-sm btn-success" title="Send Now"><i class="fas fa-paper-plane"></i></button></form>
                <?php endif; ?>
                <form method="POST" style="display:inline"><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="action" value="duplicate"><?= csrfField() ?><button class="btn btn-sm btn-secondary" title="Duplicate"><i class="fas fa-copy"></i></button></form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this campaign?')"><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="action" value="delete"><?= csrfField() ?><button class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
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

<!-- ═══ Email Builder Modal ═══ -->
<div class="modal-backdrop" id="createModal">
  <div class="modal" style="max-width:900px;width:95vw">
    <div class="modal-header">
      <h3><i class="fas fa-envelope-open-text" style="color:var(--violet)"></i> Email Campaign Builder</h3>
      <button class="modal-close" onclick="closeCampaignModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" id="campaignForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="body" id="finalBody">
      <div class="modal-body" style="padding:0;overflow:auto;max-height:75vh">

        <!-- Builder Tabs -->
        <div style="display:flex;border-bottom:1px solid var(--border);background:var(--light);">
          <button type="button" class="bdr-tab active" data-bdr="details" onclick="switchBuilderTab('details')"><i class="fas fa-info-circle"></i> Details</button>
          <button type="button" class="bdr-tab" data-bdr="template" onclick="switchBuilderTab('template')"><i class="fas fa-palette"></i> Template</button>
          <button type="button" class="bdr-tab" data-bdr="content" onclick="switchBuilderTab('content')"><i class="fas fa-edit"></i> Content</button>
          <button type="button" class="bdr-tab" data-bdr="preview" onclick="switchBuilderTab('preview')"><i class="fas fa-eye"></i> Preview</button>
        </div>

        <!-- ── Step 1: Details ── -->
        <div class="bdr-pane active" id="bdr-details" style="padding:24px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
              <label>Campaign Name <span style="color:var(--rose)">*</span></label>
              <input type="text" class="form-control" name="name" id="cmpName" required placeholder="June Newsletter 2026">
            </div>
            <div class="form-group">
              <label>Email Subject Line <span style="color:var(--rose)">*</span></label>
              <input type="text" class="form-control" name="subject" id="cmpSubject" required placeholder="🚀 See what's new at Appsgain…">
              <p class="form-hint" id="subjectCount" style="font-size:12px;color:var(--gray)">0 / 60 characters</p>
            </div>
            <div class="form-group">
              <label>Send To</label>
              <select class="form-control" name="send_to">
                <option value="all">All Subscribers (<?= number_format($subCount) ?> total)</option>
                <option value="active">Active Subscribers Only</option>
              </select>
            </div>
            <div class="form-group">
              <label>Schedule (leave blank to save as draft)</label>
              <input type="datetime-local" class="form-control" name="scheduled_at" min="<?= date('Y-m-d\TH:i') ?>">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Pre-header Text <span style="font-size:11.5px;color:var(--gray);font-weight:400">(preview text shown after subject in inbox)</span></label>
            <input type="text" class="form-control" id="cmpPreheader" placeholder="Here's a quick update from the Appsgain team…" maxlength="140">
          </div>
        </div>

        <!-- ── Step 2: Template ── -->
        <div class="bdr-pane" id="bdr-template" style="padding:24px">
          <p style="font-size:13.5px;color:var(--gray);margin-bottom:16px">Choose a template to get started. You can customise it in the Content step.</p>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
            <?php
            $tpls=[
              ['newsletter', 'Newsletter',    'fa-newspaper',    '#7c3aed', 'Weekly/monthly updates with articles and highlights'],
              ['promo',      'Promotional',   'fa-tags',         '#e11d48', 'Special offers, discounts and sale announcements'],
              ['announcement','Announcement', 'fa-bullhorn',     '#2563eb', 'Product launches, events and company news'],
              ['welcome',    'Welcome',       'fa-hand-sparkles','#059669', 'New subscriber welcome email with intro'],
              ['followup',   'Follow-Up',     'fa-reply',        '#d97706', 'Lead nurture and sales follow-up sequence'],
              ['minimal',    'Minimal',       'fa-align-left',   '#6b7280', 'Clean plain-style email for quick updates'],
            ];
            foreach($tpls as [$key,$name,$ico,$col,$desc]):
            ?>
            <div class="tpl-tile" data-tpl="<?= $key ?>" onclick="selectTemplate('<?= $key ?>')"
                 style="border:2px solid var(--border);border-radius:12px;padding:18px;cursor:pointer;transition:.2s;text-align:center">
              <div style="width:48px;height:48px;border-radius:12px;background:<?= $col ?>18;color:<?= $col ?>;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px">
                <i class="fas <?= $ico ?>"></i>
              </div>
              <div style="font-weight:700;font-size:14px;color:var(--text);margin-bottom:5px"><?= $name ?></div>
              <div style="font-size:12px;color:var(--gray);line-height:1.5"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ── Step 3: Content ── -->
        <div class="bdr-pane" id="bdr-content" style="padding:24px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="form-group" style="margin-bottom:0">
              <label>Headline</label>
              <input type="text" class="form-control" id="cmpHeadline" placeholder="Your Email Headline Here">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label>CTA Button Text</label>
              <input type="text" class="form-control" id="cmpCtaText" placeholder="Get Started →">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="form-group" style="margin-bottom:0">
              <label>CTA Button URL</label>
              <input type="url" class="form-control" id="cmpCtaUrl" placeholder="https://appsgain.in">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label>Accent Colour</label>
              <input type="color" class="form-control" id="cmpColor" value="#2563eb" style="height:42px;padding:4px 8px">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label>Email Body Content</label>
            <div style="display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap">
              <?php foreach(['{{name}}'=>'Name','{{email}}'=>'Email','{{unsubscribe}}'=>'Unsubscribe','{{site_name}}'=>'Company','{{year}}'=>'Year'] as $var=>$label): ?>
              <button type="button" class="btn btn-secondary btn-sm" onclick="insertVar('<?= $var ?>')" style="font-size:11.5px"><?= $label ?></button>
              <?php endforeach; ?>
            </div>
            <textarea class="form-control" id="cmpBodyText" rows="8" placeholder="Write your email body here. Use {{name}} for personalization. HTML is supported.

Example:
Hi {{name}},

We're excited to share some great news with you..."></textarea>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Footer Note <span style="font-size:11.5px;color:var(--gray);font-weight:400">(unsubscribe info, address)</span></label>
            <input type="text" class="form-control" id="cmpFooterNote" value="© <?= date('Y') ?> Appsgain Technologies. You're receiving this because you subscribed.">
          </div>
        </div>

        <!-- ── Step 4: Preview ── -->
        <div class="bdr-pane" id="bdr-preview" style="padding:24px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
            <div style="font-size:13.5px;font-weight:600;color:var(--text)"><i class="fas fa-eye" style="color:var(--blue);margin-right:6px"></i> Email Preview</div>
            <div style="display:flex;gap:8px">
              <button type="button" class="btn btn-secondary btn-sm" onclick="setPreviewWidth(600)"><i class="fas fa-desktop"></i> Desktop</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="setPreviewWidth(375)"><i class="fas fa-mobile-alt"></i> Mobile</button>
              <button type="button" class="btn btn-primary btn-sm" onclick="generatePreview()"><i class="fas fa-sync"></i> Refresh Preview</button>
            </div>
          </div>
          <div style="background:var(--light);border-radius:12px;padding:20px;display:flex;justify-content:center">
            <iframe id="emailPreview" style="width:600px;max-width:100%;height:520px;border:1px solid var(--border);border-radius:10px;background:#fff;transition:width .3s"></iframe>
          </div>
        </div>

      </div>
      <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div style="display:flex;gap:8px">
          <button type="button" class="btn btn-secondary" onclick="closeCampaignModal()"><i class="fas fa-times"></i> Cancel</button>
          <button type="button" class="btn btn-secondary" onclick="prevBuilderTab()"><i class="fas fa-chevron-left"></i> Back</button>
        </div>
        <div style="display:flex;gap:8px">
          <button type="button" class="btn btn-secondary" id="nextBtn" onclick="nextBuilderTab()">Next <i class="fas fa-chevron-right"></i></button>
          <button type="submit" class="btn btn-primary" id="saveBtn" style="display:none" onclick="prepareSave()"><i class="fas fa-save"></i> Save Campaign</button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
.bdr-tab{padding:12px 18px;border:none;background:none;font-size:13.5px;font-weight:600;color:var(--gray);cursor:pointer;border-bottom:2px solid transparent;transition:.2s;display:flex;align-items:center;gap:6px;white-space:nowrap}
.bdr-tab:hover{color:var(--text)}
.bdr-tab.active{color:var(--blue);border-bottom-color:var(--blue);background:var(--white)}
.bdr-pane{display:none}
.bdr-pane.active{display:block;animation:fadeSlideUp .25s ease}
.tpl-tile.selected{border-color:var(--blue)!important;background:rgba(37,99,235,.03)}
.tpl-tile:hover{border-color:var(--blue);background:rgba(37,99,235,.02);transform:translateY(-2px)}
</style>

<script>
const bdrTabs=['details','template','content','preview'];
let curBdrIdx=0;
let selectedTpl='newsletter';

function switchBuilderTab(name){
  curBdrIdx=bdrTabs.indexOf(name);
  document.querySelectorAll('.bdr-tab').forEach(t=>t.classList.toggle('active',t.dataset.bdr===name));
  document.querySelectorAll('.bdr-pane').forEach(p=>p.classList.remove('active'));
  document.getElementById('bdr-'+name).classList.add('active');
  document.getElementById('nextBtn').style.display=curBdrIdx<bdrTabs.length-1?'':'none';
  document.getElementById('saveBtn').style.display=curBdrIdx===bdrTabs.length-1?'':'none';
  if(name==='preview') generatePreview();
}
function nextBuilderTab(){if(curBdrIdx<bdrTabs.length-1)switchBuilderTab(bdrTabs[curBdrIdx+1]);}
function prevBuilderTab(){if(curBdrIdx>0)switchBuilderTab(bdrTabs[curBdrIdx-1]);}
function closeCampaignModal(){document.getElementById('createModal').classList.remove('open');}

function selectTemplate(key){
  selectedTpl=key;
  document.querySelectorAll('.tpl-tile').forEach(t=>t.classList.toggle('selected',t.dataset.tpl===key));
}

function insertVar(v){
  const ta=document.getElementById('cmpBodyText');
  const s=ta.selectionStart,e=ta.selectionEnd;
  ta.value=ta.value.substring(0,s)+v+ta.value.substring(e);
  ta.selectionStart=ta.selectionEnd=s+v.length;
  ta.focus();
}

function setPreviewWidth(w){document.getElementById('emailPreview').style.width=w+'px';}

function buildEmailHTML(){
  const headline=document.getElementById('cmpHeadline').value||'Welcome to our newsletter';
  const body=document.getElementById('cmpBodyText').value||'Your email content goes here.';
  const ctaText=document.getElementById('cmpCtaText').value||'Learn More';
  const ctaUrl=document.getElementById('cmpCtaUrl').value||'<?= SITE_URL ?>';
  const color=document.getElementById('cmpColor').value||'#2563eb';
  const footer=document.getElementById('cmpFooterNote').value;
  const preheader=document.getElementById('cmpPreheader').value;
  const bodyHtml=body.replace(/\n/g,'<br>');

  return `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Email</title><style>body{margin:0;padding:0;background:#f4f6fb;font-family:Inter,'Segoe UI',Arial,sans-serif}a{color:`+color+`}.email-wrap{max-width:600px;margin:0 auto;padding:24px 16px}.email-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06)}.header{background:linear-gradient(135deg,`+color+`,`+color+`dd);padding:36px 40px;text-align:center}.header h1{color:#fff;margin:0;font-size:26px;font-weight:800;line-height:1.3}`+(preheader?`<span style="display:none">`+preheader+`</span>`:``)+`</style></head><body><div class="email-wrap"><div class="email-card"><div class="header"><h1>`+headline+`</h1></div><div style="padding:36px 40px;color:#334155;font-size:15px;line-height:1.8">`+bodyHtml+`<div style="text-align:center;margin:32px 0"><a href="`+ctaUrl+`" style="background:`+color+`;color:#fff;padding:14px 36px;border-radius:9px;font-size:15px;font-weight:700;text-decoration:none;display:inline-block">`+ctaText+`</a></div></div><div style="padding:20px 40px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8">`+footer+`</div></div></div></body></html>`;
}

function generatePreview(){
  const html=buildEmailHTML();
  const iframe=document.getElementById('emailPreview');
  const doc=iframe.contentDocument||iframe.contentWindow.document;
  doc.open();doc.write(html);doc.close();
}

function prepareSave(){
  document.getElementById('finalBody').value=buildEmailHTML();
}

/* Subject char count */
document.getElementById('cmpSubject')?.addEventListener('input',function(){
  const n=this.value.length;
  const el=document.getElementById('subjectCount');
  el.textContent=n+' / 60 characters';
  el.style.color=n>60?'#e11d48':n>50?'#d97706':'var(--gray)';
});

/* Init */
selectTemplate('newsletter');
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
