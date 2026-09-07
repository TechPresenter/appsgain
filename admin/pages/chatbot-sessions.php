<?php
/**
 * Admin → Chatbot sessions.
 *
 * Every conversation the widget has had, including the ones that never became
 * leads. chatbot_leads answers "who do we ring"; this answers "what are people
 * actually asking" — which is where the gaps in the site's own content show up.
 *
 * Rows are written by api/chatbot.php after each exchange, so a chat still in
 * progress appears here while it is happening.
 */
$adminTitle = 'Chatbot Sessions';
$adminPage  = 'chatbot-sessions';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$perPage  = 20;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$search   = sanitizeInput($_GET['search'] ?? '');
$filter   = sanitizeInput($_GET['filter'] ?? '');   // '', 'lead', 'nolead', 'unread'
$dateFrom = sanitizeInput($_GET['from'] ?? '');
$dateTo   = sanitizeInput($_GET['to']   ?? '');
$view     = (int)($_GET['view'] ?? 0);

/** Keep the current filters on every link so paging never resets them. */
function csUrl(array $extra = []): string {
    $base = [
        'search' => $_GET['search'] ?? '', 'filter' => $_GET['filter'] ?? '',
        'from'   => $_GET['from']   ?? '', 'to'     => $_GET['to']     ?? '',
    ];
    $qs = http_build_query(array_filter(array_merge($base, $extra)));
    return ADMIN_URL . '/pages/chatbot-sessions.php' . ($qs ? '?' . $qs : '');
}

/* ── POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbExecute("DELETE FROM chatbot_sessions WHERE id = ?", [$id]);
            logActivity('delete', 'chatbot', "Deleted chatbot session #{$id}");
            setFlash('success', 'Session deleted.');
        }
        redirect(csUrl());
    }

    if ($action === 'bulk_delete') {
        /* intval on every element, so the IN list is numbers and nothing else. */
        $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
        if ($ids) {
            $in = implode(',', $ids);
            dbExecute("DELETE FROM chatbot_sessions WHERE id IN ({$in})");
            logActivity('delete', 'chatbot', 'Bulk deleted ' . count($ids) . ' chatbot sessions');
            setFlash('success', count($ids) . ' sessions deleted.');
        }
        redirect(csUrl());
    }

    if ($action === 'mark_all_read') {
        dbExecute("UPDATE chatbot_sessions SET is_read = 1 WHERE is_read = 0");
        setFlash('success', 'All sessions marked as read.');
        redirect(csUrl());
    }
}

/* ── Filters ──
   Every value is bound; only the fixed fragments are concatenated.
   Columns are qualified with the s. alias because the listing joins
   chatbot_leads, and both tables carry transcript and session_id. */
$where = '1';
$args  = [];

if ($search !== '') {
    /* The transcript is where the interesting text lives — someone searching
       "pricing" wants the chats that discussed it, not just the openers. */
    $where .= " AND (s.first_message LIKE ? OR s.transcript LIKE ? OR s.source_page LIKE ? OR s.session_id = ?)";
    $like   = '%' . $search . '%';
    array_push($args, $like, $like, $like, $search);
}
if     ($filter === 'lead')   $where .= " AND s.lead_id IS NOT NULL";
elseif ($filter === 'nolead') $where .= " AND s.lead_id IS NULL";
elseif ($filter === 'unread') $where .= " AND s.is_read = 0";

if ($dateFrom !== '') { $where .= " AND s.started_at >= ?"; $args[] = $dateFrom . ' 00:00:00'; }
if ($dateTo   !== '') { $where .= " AND s.started_at <= ?"; $args[] = $dateTo   . ' 23:59:59'; }

$total  = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_sessions s WHERE {$where}", $args);
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = dbFetchAll(
    "SELECT s.*, l.name AS lead_name, l.phone AS lead_phone, l.status AS lead_status
       FROM chatbot_sessions s
       LEFT JOIN chatbot_leads l ON l.id = s.lead_id
      WHERE {$where}
      ORDER BY COALESCE(s.last_at, s.started_at) DESC
      LIMIT {$perPage} OFFSET {$offset}",
    $args
);

/* Counters for the filter chips — unfiltered, so they read as totals. */
$cTotal  = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_sessions");
$cLead   = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_sessions WHERE lead_id IS NOT NULL");
$cUnread = (int)dbFetchValue("SELECT COUNT(*) FROM chatbot_sessions WHERE is_read = 0");

/* ── The open conversation ── */
$session = null;
if ($view) {
    $session = dbFetchOne(
        "SELECT s.*, l.name AS lead_name, l.phone AS lead_phone, l.email AS lead_email, l.status AS lead_status
           FROM chatbot_sessions s
           LEFT JOIN chatbot_leads l ON l.id = s.lead_id
          WHERE s.id = ?",
        [$view]
    );
    /* Opening it is reading it. */
    if ($session && !$session['is_read']) {
        dbExecute("UPDATE chatbot_sessions SET is_read = 1 WHERE id = ?", [$view]);
        $session['is_read'] = 1;
    }
}

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.cs-wrap    { display:grid; gap:16px; }
.cs-stats   { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; }
.cs-stat    { background:var(--card,#fff); border:1px solid var(--border,#e5e7eb); border-radius:12px; padding:14px 16px; }
.cs-stat b  { display:block; font-size:22px; font-weight:800; line-height:1.1; }
.cs-stat span { font-size:12px; color:var(--gray,#6b7280); }

.cs-filters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
.cs-chip    { padding:7px 13px; border-radius:999px; border:1px solid var(--border,#e5e7eb);
              font-size:12.5px; font-weight:600; text-decoration:none; color:var(--gray,#6b7280);
              background:var(--card,#fff); }
.cs-chip.on { background:var(--violet,#6a00ff); border-color:var(--violet,#6a00ff); color:#fff; }

.cs-row       { display:grid; grid-template-columns:28px 1fr auto; gap:12px; align-items:start;
                padding:13px 14px; border-bottom:1px solid var(--border,#e5e7eb); }
.cs-row:last-child { border-bottom:0; }
.cs-row.unread { background:color-mix(in srgb, var(--violet,#6a00ff) 4%, transparent); }
.cs-q         { font-size:14px; font-weight:600; color:var(--ink,#0b1026); line-height:1.4;
                display:block; text-decoration:none; }
.cs-q:hover   { color:var(--violet,#6a00ff); }
.cs-meta      { font-size:11.5px; color:var(--gray,#6b7280); margin-top:4px;
                display:flex; flex-wrap:wrap; gap:10px; }
.cs-tag       { font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:999px;
                text-transform:uppercase; letter-spacing:.03em; }
.cs-tag.lead  { background:#dcfce7; color:#166534; }
.cs-tag.anon  { background:#f1f5f9; color:#475569; }

/* Transcript */
.cs-thread    { display:grid; gap:10px; padding:18px; max-height:62vh; overflow-y:auto;
                background:var(--light,#f8fafc); border-radius:12px; }
.cs-turn      { max-width:76%; padding:10px 13px; border-radius:14px; font-size:13.5px; line-height:1.55;
                white-space:pre-wrap; word-wrap:break-word; }
.cs-turn.user { margin-left:auto; background:var(--violet,#6a00ff); color:#fff; border-bottom-right-radius:4px; }
.cs-turn.bot  { margin-right:auto; background:#fff; border:1px solid var(--border,#e5e7eb); border-bottom-left-radius:4px; }
.cs-at        { font-size:10.5px; opacity:.65; margin-top:5px; display:block; }

@media (max-width:640px) {
  .cs-row  { grid-template-columns:24px 1fr; }
  .cs-turn { max-width:88%; }
}
</style>

<div class="cs-wrap">

<?php if ($session): /* ═════════ ONE CONVERSATION ═════════ */
  $turns = json_decode((string)$session['transcript'], true);
  if (!is_array($turns)) $turns = [];
?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fas fa-comments" style="color:var(--violet)"></i> Conversation</h3>
      <?php if ($session['lead_id']): ?>
        <a class="cs-tag lead" style="text-decoration:none"
           href="<?= ADMIN_URL ?>/pages/chatbot-leads.php?view=<?= (int)$session['lead_id'] ?>">
          Lead: <?= e($session['lead_name'] ?: 'unnamed') ?> <?= e($session['lead_phone'] ?: '') ?>
        </a>
      <?php else: ?>
        <span class="cs-tag anon">No lead captured</span>
      <?php endif; ?>
      <a href="<?= e(csUrl()) ?>" class="btn btn-outline btn-sm" style="margin-left:auto">
        <i class="fas fa-arrow-left"></i> Back to all sessions
      </a>
    </div>
    <div class="card-body">
      <div style="display:flex;flex-wrap:wrap;gap:18px;font-size:12.5px;color:var(--gray);margin-bottom:14px">
        <span><b>Started:</b> <?= e(date('d M Y, g:i a', strtotime($session['started_at']))) ?></span>
        <?php if ($session['last_at']): ?>
        <span><b>Last message:</b> <?= e(timeAgo($session['last_at'])) ?></span>
        <?php endif; ?>
        <span><b>Messages:</b> <?= (int)$session['message_count'] ?></span>
        <?php if ($session['source_page']): ?>
        <span><b>Page:</b> <?= e($session['source_page']) ?></span>
        <?php endif; ?>
        <span><b>IP:</b> <?= e($session['ip_address'] ?: '—') ?></span>
      </div>

      <?php if (!$turns): ?>
        <p style="font-size:13px;color:var(--gray)">No messages were stored for this session.</p>
      <?php else: ?>
      <div class="cs-thread">
        <?php foreach ($turns as $t):
            $role = ($t['role'] ?? '') === 'user' ? 'user' : 'bot'; ?>
          <div class="cs-turn <?= $role ?>">
            <?= e((string)($t['content'] ?? '')) ?>
            <?php if (!empty($t['at'])): ?>
              <span class="cs-at"><?= e(date('d M, g:i a', strtotime($t['at']))) ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" style="margin-top:16px"
            onsubmit="return confirm('Delete this conversation permanently?')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$session['id'] ?>">
        <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca">
          <i class="fas fa-trash"></i> Delete conversation
        </button>
      </form>
    </div>
  </div>

<?php else: /* ═════════ LIST ═════════ */ ?>

  <div class="cs-stats">
    <div class="cs-stat"><b><?= number_format($cTotal) ?></b><span>Total conversations</span></div>
    <div class="cs-stat"><b><?= number_format($cLead) ?></b><span>Produced a lead</span></div>
    <div class="cs-stat"><b><?= number_format($cUnread) ?></b><span>Unread</span></div>
    <div class="cs-stat"><b><?= $cTotal ? round($cLead / $cTotal * 100) : 0 ?>%</b><span>Conversion</span></div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fas fa-comments" style="color:var(--violet)"></i> All conversations</h3></div>
    <div class="card-body">

      <form method="GET" class="cs-filters" style="margin-bottom:14px">
        <input type="search" name="search" value="<?= e($search) ?>" class="form-control"
               placeholder="Search what visitors said&hellip;" style="max-width:280px">
        <input type="date" name="from" value="<?= e($dateFrom) ?>" class="form-control" style="max-width:160px">
        <input type="date" name="to"   value="<?= e($dateTo) ?>"   class="form-control" style="max-width:160px">
        <?php if ($filter !== ''): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if ($search || $dateFrom || $dateTo || $filter): ?>
          <a href="<?= ADMIN_URL ?>/pages/chatbot-sessions.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <div class="cs-filters" style="margin-bottom:14px">
        <a class="cs-chip <?= $filter === ''       ? 'on' : '' ?>" href="<?= e(csUrl(['filter' => null, 'page' => null])) ?>">All <?= $cTotal ?></a>
        <a class="cs-chip <?= $filter === 'lead'   ? 'on' : '' ?>" href="<?= e(csUrl(['filter' => 'lead',   'page' => null])) ?>">With lead <?= $cLead ?></a>
        <a class="cs-chip <?= $filter === 'nolead' ? 'on' : '' ?>" href="<?= e(csUrl(['filter' => 'nolead', 'page' => null])) ?>">No lead</a>
        <a class="cs-chip <?= $filter === 'unread' ? 'on' : '' ?>" href="<?= e(csUrl(['filter' => 'unread', 'page' => null])) ?>">Unread <?= $cUnread ?></a>
        <?php if ($cUnread): ?>
        <form method="POST" style="margin-left:auto">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="mark_all_read">
          <button class="btn btn-outline btn-sm"><i class="fas fa-check-double"></i> Mark all read</button>
        </form>
        <?php endif; ?>
      </div>

      <?php if (!$rows): ?>
        <p style="font-size:13.5px;color:var(--gray);padding:24px 0;text-align:center">
          <?= $search || $filter || $dateFrom || $dateTo
              ? 'No conversations match those filters.'
              : 'No conversations yet. They appear here as soon as someone uses the chat widget.' ?>
        </p>
      <?php else: ?>

      <form method="POST" id="csBulk" onsubmit="return confirm('Delete the selected conversations permanently?')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="bulk_delete">

        <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
          <?php foreach ($rows as $r):
              $when = $r['last_at'] ?: $r['started_at']; ?>
            <div class="cs-row <?= $r['is_read'] ? '' : 'unread' ?>">
              <input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="Select conversation">
              <div>
                <a class="cs-q" href="<?= e(csUrl(['view' => $r['id']])) ?>">
                  <?= e($r['first_message'] !== '' ? truncate($r['first_message'], 110) : 'Conversation with no visitor message') ?>
                </a>
                <div class="cs-meta">
                  <span><i class="far fa-clock"></i> <?= e(timeAgo($when)) ?></span>
                  <span><i class="far fa-comment"></i> <?= (int)$r['message_count'] ?> messages</span>
                  <?php if ($r['source_page']): ?>
                    <span><i class="fas fa-link"></i> <?= e(truncate($r['source_page'], 44)) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div style="text-align:right">
                <?php if ($r['lead_id']): ?>
                  <span class="cs-tag lead">Lead</span>
                <?php else: ?>
                  <span class="cs-tag anon">Chat</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;align-items:center;gap:12px;margin-top:12px;flex-wrap:wrap">
          <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca">
            <i class="fas fa-trash"></i> Delete selected
          </button>
          <?php if ($pages > 1): ?>
          <div style="margin-left:auto;display:flex;gap:6px;align-items:center">
            <?php if ($page > 1): ?>
              <a class="btn btn-outline btn-sm" href="<?= e(csUrl(['page' => $page - 1])) ?>">Previous</a>
            <?php endif; ?>
            <span style="font-size:12.5px;color:var(--gray)">Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?>
              <a class="btn btn-outline btn-sm" href="<?= e(csUrl(['page' => $page + 1])) ?>">Next</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
