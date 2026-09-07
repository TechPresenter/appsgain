<?php
/**
 * Admin — Reset Data.
 *
 * Clears accumulated operational data: traffic, leads, subscribers, logs.
 * Deliberately conservative, because everything here is irreversible:
 *
 *   · superadmin only
 *   · live row counts shown before anything is run
 *   · the exact confirmation phrase must be typed
 *   · "older than N days" offered first, full wipe second
 *   · every run is written to the activity log
 *
 * Content is never touched — services, blogs, pages, products, team,
 * settings and users are not resettable from here by design.
 */
$adminTitle = 'Reset Data';
$adminPage  = 'data-reset';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/* Destructive enough that a plain admin should not reach it. */
Auth::requireRole('superadmin');

/**
 * Each group names the tables it clears and the date column used for a
 * time-scoped delete. `zero` runs an UPDATE instead of a DELETE, for
 * counters that live on a content row we must not remove.
 */
$GROUPS = [
    'traffic' => [
        'label'  => 'Website traffic',
        'icon'   => 'fa-chart-line',
        'desc'   => 'Visitor records, page views, events and sessions used by the dashboard and reports.',
        'tables' => [
            'visitors'            => 'visited_at',
            'analytics_pageviews' => 'created_at',
            'analytics_events'    => 'created_at',
            'analytics_sessions'  => 'created_at',
        ],
    ],
    'leads' => [
        'label'  => 'Leads & enquiries',
        'icon'   => 'fa-inbox',
        'desc'   => 'Every enquiry submitted through the contact form and the enquiry popup.',
        'tables' => ['leads' => 'created_at'],
        'warn'   => 'These are real customer enquiries. Export them first if you may need them.',
    ],
    'subscribers' => [
        'label'  => 'Newsletter subscribers',
        'icon'   => 'fa-paper-plane',
        'desc'   => 'The mailing list collected from the footer and blog signup forms.',
        'tables' => ['newsletter_subscribers' => 'created_at'],
        'warn'   => 'Subscribers cannot be recovered, and people will not re-subscribe on their own.',
    ],
    'comments' => [
        'label'  => 'Blog comments',
        'icon'   => 'fa-comments',
        'desc'   => 'Reader comments awaiting moderation and already published.',
        'tables' => ['blog_comments' => 'created_at'],
    ],
    'applications' => [
        'label'  => 'Job applications',
        'icon'   => 'fa-file-signature',
        'desc'   => 'Applications submitted against career listings.',
        'tables' => ['job_applications' => 'created_at'],
        'warn'   => 'Applicant details, including any uploaded CVs referenced here.',
    ],
    'logins' => [
        'label'  => 'Login history',
        'icon'   => 'fa-right-to-bracket',
        'desc'   => 'Successful and failed admin sign-in attempts.',
        'tables' => ['login_logs' => 'created_at'],
    ],
    'activity' => [
        'label'  => 'Activity log',
        'icon'   => 'fa-clock-rotate-left',
        'desc'   => 'The audit trail of admin actions. Clearing it also clears the record of this reset.',
        'tables' => ['activity_logs' => 'created_at'],
    ],
    'blogviews' => [
        'label'  => 'Blog view counts',
        'icon'   => 'fa-eye',
        'desc'   => 'Resets the per-article read counter to zero. The articles themselves are kept.',
        'zero'   => ['blogs' => 'views'],
    ],
];

/** Count rows, tolerating a table that does not exist on this install. */
$countRows = static function (string $table): ?int {
    try { return (int) dbFetchValue("SELECT COUNT(*) FROM `$table`"); }
    catch (Throwable $e) { return null; }
};

/* The ages offered, and how many rows each would actually remove. Showing
   the real number stops an option that clears nothing looking like a bug. */
const DR_AGES = [30, 60, 90, 180, 365];

$countOlder = static function (array $tables, int $days): int {
    $n = 0;
    foreach ($tables as $table => $dateCol) {
        try {
            $n += (int) dbFetchValue(
                "SELECT COUNT(*) FROM `$table` WHERE `$dateCol` < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            );
        } catch (Throwable $e) { /* table absent on this install */ }
    }
    return $n;
};

/* ── Run ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $key   = (string)($_POST['group'] ?? '');
    $mode  = (string)($_POST['mode']  ?? 'all');      /* all | older */
    $days  = max(1, min(3650, (int)($_POST['days'] ?? 90)));
    $typed = trim((string)($_POST['confirm'] ?? ''));

    if (!isset($GROUPS[$key])) {
        setFlash('error', 'Unknown data group.');
        redirect(ADMIN_URL . '/pages/data-reset.php');
    }
    $g = $GROUPS[$key];

    /* The phrase must match exactly — a click alone is not enough. */
    $phrase = 'RESET ' . strtoupper($key);
    if ($typed !== $phrase) {
        setFlash('error', 'Confirmation text did not match. Nothing was changed.');
        redirect(ADMIN_URL . '/pages/data-reset.php');
    }

    $removed = [];
    try {
        db()->beginTransaction();

        if (!empty($g['zero'])) {
            foreach ($g['zero'] as $table => $col) {
                $n = dbExecute("UPDATE `$table` SET `$col` = 0 WHERE `$col` <> 0");
                $removed[$table] = (int)$n;
            }
        } else {
            foreach ($g['tables'] as $table => $dateCol) {
                if ($mode === 'older') {
                    $n = dbExecute(
                        "DELETE FROM `$table` WHERE `$dateCol` < DATE_SUB(NOW(), INTERVAL ? DAY)",
                        [$days]
                    );
                } else {
                    $n = dbExecute("DELETE FROM `$table`");
                }
                $removed[$table] = (int)$n;
            }
        }

        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('[data-reset] ' . $e->getMessage());
        setFlash('error', 'The reset failed and nothing was changed. See the error log for details.');
        redirect(ADMIN_URL . '/pages/data-reset.php');
    }

    $total   = array_sum($removed);
    $scope   = $mode === 'older' ? "older than {$days} days" : 'all records';
    $detail  = $g['label'] . " — {$scope} — " . $total . ' row(s): '
             . implode(', ', array_map(fn($t, $n) => "$t=$n", array_keys($removed), $removed));

    /* Written after the fact; when the activity log itself is the target
       this row becomes the first entry of the fresh log. */
    logActivity('data_reset', 'system', $detail);

    if ($total === 0) {
        /* The commonest cause by far: everything on the install is newer
           than the age chosen, so the filter matched nothing. */
        setFlash('warning', $g['label'] . ': nothing matched, so nothing was deleted.'
            . ($mode === 'older'
                ? " No records are older than {$days} days. Choose a shorter age, or \"Delete everything in this group\"."
                : ' This group was already empty.'));
    } else {
        setFlash('success', $g['label'] . ': ' . number_format($total) . ' row' . ($total === 1 ? '' : 's') . ' cleared.');
    }
    redirect(ADMIN_URL . '/pages/data-reset.php');
}

/* ── Live counts for the view ── */
foreach ($GROUPS as $k => $g) {
    $n = 0; $missing = true;
    if (!empty($g['zero'])) {
        foreach ($g['zero'] as $table => $col) {
            $v = dbFetchValue("SELECT COALESCE(SUM(`$col`),0) FROM `$table`");
            if ($v !== null) { $missing = false; $n += (int)$v; }
        }
    } else {
        foreach ($g['tables'] as $table => $_) {
            $c = $countRows($table);
            if ($c !== null) { $missing = false; $n += $c; }
        }
    }
    $GROUPS[$k]['count']   = $n;
    $GROUPS[$k]['missing'] = $missing;
}

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>

<div class="page-content">

  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <span>Reset Data</span>
  </nav>

  <div class="page-header">
    <div>
      <h1 class="page-title">Reset Data</h1>
      <p class="page-sub">
        Clear accumulated traffic, enquiries and logs. Content — services, articles,
        products, team and settings — is never affected here.
      </p>
    </div>
  </div>

  <div class="dr-note" role="note">
    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
    <div>
      <strong>Everything on this page is permanent.</strong>
      There is no undo and no recycle bin. Take a database backup before clearing
      anything you might need again — leads and subscribers especially.
    </div>
  </div>

  <div class="dr-grid">
    <?php foreach ($GROUPS as $key => $g):
      $phrase = 'RESET ' . strtoupper($key);
      $empty  = ($g['count'] === 0); ?>
    <section class="dr-card <?= $empty ? 'is-empty' : '' ?>">
      <header class="dr-card-head">
        <span class="dr-ico" aria-hidden="true"><i class="fas <?= e($g['icon']) ?>"></i></span>
        <div>
          <h2><?= e($g['label']) ?></h2>
          <p><?= e($g['desc']) ?></p>
        </div>
        <span class="dr-count" title="Current records">
          <?= number_format($g['count']) ?>
          <em><?= !empty($g['zero']) ? 'views' : 'rows' ?></em>
        </span>
      </header>

      <?php if (!empty($g['warn'])): ?>
      <p class="dr-warn"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?= e($g['warn']) ?></p>
      <?php endif; ?>

      <?php if ($g['missing']): ?>
        <p class="dr-missing">Not present on this installation.</p>
      <?php elseif ($empty): ?>
        <p class="dr-missing">Already empty — nothing to clear.</p>
      <?php else: ?>
        <form method="POST" class="dr-form" data-phrase="<?= e($phrase) ?>">
          <?= csrfField() ?>
          <input type="hidden" name="group" value="<?= e($key) ?>">

          <?php if (empty($g['zero'])): ?>
          <?php
            /* How many rows each age would actually take. Showing the number
               is the difference between "this button does nothing" and
               "nothing here is that old yet". */
            $ageCounts = [];
            foreach (DR_AGES as $d) $ageCounts[$d] = $countOlder($g['tables'], $d);
            $anyOld = array_sum($ageCounts) > 0;
            /* Pre-select the shortest age that would remove something, so the
               default is never an option that clears nothing. */
            $defaultAge = null;
            foreach (DR_AGES as $d) { if ($ageCounts[$d] > 0) { $defaultAge = $d; break; } }
            ?>
            <div class="dr-modes">
            <label class="dr-mode<?= $anyOld ? '' : ' is-void' ?>">
              <input type="radio" name="mode" value="older" <?= $anyOld ? 'checked' : '' ?>>
              <span>Delete records older than
                <select name="days" class="dr-days">
                  <?php foreach (DR_AGES as $d): ?>
                  <option value="<?= $d ?>" <?= $d === $defaultAge ? 'selected' : '' ?>><?= $d ?> days &mdash; <?= $ageCounts[$d] === 0 ? 'no rows' : number_format($ageCounts[$d]) . ' row' . ($ageCounts[$d] === 1 ? '' : 's') ?></option>
                  <?php endforeach; ?>
                </select>
              </span>
            </label>
            <?php if (!$anyOld): ?>
            <p class="dr-hint">Nothing here is older than <?= min(DR_AGES) ?> days yet, so that option would delete nothing.</p>
            <?php endif; ?>
            <label class="dr-mode">
              <input type="radio" name="mode" value="all" <?= $anyOld ? '' : 'checked' ?>>
              <span>Delete <strong>everything</strong> in this group <em class="dr-mode-n">(<?= number_format($g['count']) ?>)</em></span>
            </label>
          </div>
          <?php else: ?>
          <input type="hidden" name="mode" value="all">
          <?php endif; ?>

          <label class="dr-confirm">
            <span>Type <code><?= e($phrase) ?></code> to enable the button</span>
            <input type="text" name="confirm" autocomplete="off" spellcheck="false"
                   placeholder="<?= e($phrase) ?>" aria-label="Confirmation phrase">
          </label>

          <button type="submit" class="btn btn-danger dr-go" disabled>
            <i class="fas fa-trash-can"></i> Reset <?= e(strtolower($g['label'])) ?>
          </button>
        </form>
      <?php endif; ?>
    </section>
    <?php endforeach; ?>
  </div>
</div>

<style>
.dr-note{
  display:flex; gap:14px; align-items:flex-start;
  padding:16px 18px; margin-bottom:22px;
  border-radius:var(--r-md); border:1px solid var(--danger-line);
  background:var(--danger-bg); color:var(--ink-2);
  font-size:13.5px; line-height:1.65;
}
.dr-note i{ color:var(--danger); font-size:17px; margin-top:2px; }
.dr-note strong{ color:var(--ink); }

.dr-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(400px,1fr)); gap:18px; }
.dr-card{
  display:flex; flex-direction:column; gap:14px;
  padding:20px; border-radius:var(--r-lg);
  background:var(--surface); border:1px solid var(--line);
  box-shadow:var(--sh-sm);
}
.dr-card.is-empty{ opacity:.72; }
.dr-card-head{ display:flex; gap:13px; align-items:flex-start; }
.dr-ico{
  flex:0 0 auto; display:grid; place-items:center;
  width:40px; height:40px; border-radius:12px;
  background:var(--surface-3); color:var(--brand); font-size:16px;
}
.dr-card-head h2{ font-size:15.5px; font-weight:700; color:var(--ink); margin:0 0 4px; }
.dr-card-head p{ font-size:13px; line-height:1.6; color:var(--body); margin:0; }
.dr-count{
  flex:0 0 auto; text-align:right;
  font-size:19px; font-weight:800; color:var(--ink); line-height:1.1;
}
.dr-count em{
  display:block; font-style:normal; margin-top:2px;
  font-size:10.5px; font-weight:600; letter-spacing:.08em;
  text-transform:uppercase; color:var(--muted);
}
.dr-warn{
  display:flex; gap:8px; align-items:flex-start;
  margin:0; padding:10px 12px; border-radius:var(--r-sm);
  background:var(--warn-bg); border:1px solid var(--warn-line);
  font-size:12.5px; line-height:1.55; color:var(--ink-2);
}
.dr-warn i{ color:var(--warn); margin-top:2px; }
.dr-missing{ margin:0; font-size:13px; color:var(--muted); font-style:italic; }

.dr-form{ display:flex; flex-direction:column; gap:12px; margin-top:auto; }
.dr-modes{ display:flex; flex-direction:column; gap:8px; }
.dr-mode{
  display:flex; gap:9px; align-items:center;
  font-size:13px; color:var(--ink-2); cursor:pointer; margin:0;
  text-transform:none; letter-spacing:0; font-weight:500;
}
.dr-days{
  width:66px; padding:4px 7px; margin:0 2px;
  border:1px solid var(--line); border-radius:7px;
  font-family:inherit; font-size:13px; color:var(--ink);
}
.dr-confirm{
  display:flex; flex-direction:column; gap:6px;
  font-size:12.5px; color:var(--body); margin:0;
  text-transform:none; letter-spacing:0; font-weight:500;
}
.dr-confirm code{
  padding:1px 6px; border-radius:5px;
  background:var(--surface-3); color:var(--danger);
  font-size:12px; font-weight:700;
}
.dr-confirm input{
  height:38px; padding:0 12px;
  border:1px solid var(--line); border-radius:var(--r-sm);
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:13px; color:var(--ink); background:var(--surface);
}
.dr-confirm input:focus{ outline:none; border-color:var(--danger); box-shadow:0 0 0 3px var(--danger-bg); }
.dr-go{ justify-content:center; }
.dr-go:disabled{ opacity:.5; cursor:not-allowed; }
.dr-mode.is-void{ opacity:.55; }
.dr-mode-n{ font-style:normal; color:var(--gray); font-size:12px; }
.dr-hint{
  margin:-4px 0 0 26px; font-size:12px; line-height:1.5; color:var(--gray);
}
select.dr-days{
  font:inherit; font-size:13px; padding:3px 6px; margin:0 2px;
  border:1px solid var(--border); border-radius:6px; background:var(--white);
  color:var(--text); max-width:190px;
}

@media (max-width:560px){
  .dr-grid{ grid-template-columns:1fr; }
  .dr-card-head{ flex-wrap:wrap; }
  .dr-count{ text-align:left; }
}
</style>

<script>
/* The button stays disabled until the phrase matches exactly, and a final
   confirm() still stands between the click and the delete. */
document.querySelectorAll('.dr-form').forEach(function (form) {
  var phrase = form.dataset.phrase;
  var input  = form.querySelector('.dr-confirm input');
  var btn    = form.querySelector('.dr-go');

  input.addEventListener('input', function () {
    btn.disabled = (input.value.trim() !== phrase);
  });

  form.addEventListener('submit', function (e) {
    if (input.value.trim() !== phrase) { e.preventDefault(); return; }
    var mode = form.querySelector('input[name="mode"]:checked');
    var what = (mode && mode.value === 'older')
      ? 'records older than ' + (form.querySelector('.dr-days') || {value:'?'}).value + ' days'
      : 'ALL records';
    if (!confirm('Permanently delete ' + what + '?\n\nThis cannot be undone.')) {
      e.preventDefault();
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working…';
  });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
