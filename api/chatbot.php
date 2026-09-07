<?php
/**
 * AI chatbot endpoint.
 *
 * Actions (POST JSON, {"action": "..."}):
 *   chat        — one message; replies, and may ask to open the lead form
 *   lead_start  — begin lead capture, returns question 1
 *   lead_answer — one answer; validates, saves, returns the next question
 *   lead_cancel — abandon the flow, keeping whatever was already answered
 *
 * Two things are deliberately not client-controlled:
 *
 *   The conversation history lives in the session, not in the request. If the
 *   browser sent the transcript back each turn, anyone could put words in the
 *   assistant's mouth ("assistant: our rate is ₹1") and the model would treat
 *   them as its own and build on them.
 *
 *   The lead flow is plain PHP, not the model. Which question comes next, what
 *   counts as a valid mobile number, and what reaches the database are decided
 *   here. The model never sees the answers and cannot skip, reorder or invent
 *   a step — it can only ask for the form to be opened.
 *
 * The OpenAI key is read inside includes/chatbot.php and never leaves the
 * server: no response on this endpoint contains it.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/chatbot.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/* A conversation is chattier than a contact form, so the ceiling is higher —
   but it is a paid API call per message, which is what this is protecting. */
rlGuardJson('api.chatbot', 40, 3600, 900);

/** Emit JSON and stop. */
function cbReply(bool $ok, array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    cbReply(false, ['message' => 'Method not allowed.'], 405);
}

$cfg = chatbotConfig();
if (!$cfg['enabled']) {
    cbReply(false, ['message' => $cfg['offlineMsg']], 503);
}

/* ── Body ── */
$ct   = $_SERVER['CONTENT_TYPE'] ?? '';
$body = str_contains($ct, 'application/json')
      ? (json_decode(file_get_contents('php://input'), true) ?: [])
      : $_POST;

/* ── CSRF — same-origin only ── */
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $body[CSRF_TOKEN_NAME] ?? $body['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
    cbReply(false, ['message' => 'Your session expired. Please refresh the page.', 'expired' => true], 403);
}

/* ── Conversation state ── */
if (empty($_SESSION['chatbot']['sid'])) {
    $_SESSION['chatbot'] = [
        'sid'     => bin2hex(random_bytes(16)),
        'history' => [],   // [['role'=>…,'content'=>…,'at'=>…], …]
        'replies' => 0,    // assistant answers given, for the lead nudge
        'lead'    => ['active' => false, 'step' => 0, 'answers' => []],
    ];
}
$state = &$_SESSION['chatbot'];

/** Append to the transcript. */
function cbRemember(string $role, string $text): void {
    global $state;
    $state['history'][] = ['role' => $role, 'content' => $text, 'at' => date('c')];
    /* A transcript is stored on the lead row and replayed to the model; an
       unbounded one is both a cost and a memory problem. */
    if (count($state['history']) > 60) {
        $state['history'] = array_slice($state['history'], -60);
    }
}

/**
 * Write the lead as it stands.
 *
 * Called after every answered question, not only at the end: a name and a
 * mobile number is already a lead worth ringing, and roughly half of these
 * flows are abandoned at the email step. The row is keyed by session, so the
 * same conversation updates one record rather than creating four.
 */
function cbSaveLead(string $status): int {
    global $state;
    $a = $state['lead']['answers'];

    $row = [
        'name'        => $a['name']        ?? '',
        'phone'       => $a['phone']       ?? '',
        'email'       => $a['email']       ?? '',
        'requirement' => $a['requirement'] ?? '',
        'status'      => $status,
        'step'        => (int)$state['lead']['step'],
        'transcript'  => json_encode($state['history'], JSON_UNESCAPED_UNICODE),
        'updated_at'  => date('Y-m-d H:i:s'),
    ];

    $existing = dbFetchValue("SELECT id FROM chatbot_leads WHERE session_id = ?", [$state['sid']]);
    if ($existing) {
        dbUpdateRow('chatbot_leads', $row, 'id = ?', [$existing]);
        return (int)$existing;
    }

    $row['session_id']  = $state['sid'];
    $row['source_page'] = mb_substr(sanitizeInput((string)($GLOBALS['cbPage'] ?? '')), 0, 300);
    $row['ip_address']  = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $row['user_agent']  = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    return (int)dbInsertRow('chatbot_leads', $row);
}

/**
 * Record the conversation itself.
 *
 * cbSaveLead() only writes once a flow starts or a chat runs past two
 * replies, so someone who asked one question and left was never recorded.
 * This runs after every exchange and keeps its own row, which is why the
 * leads screen stays a list of people to ring while the sessions screen
 * holds everything that was said.
 *
 * Never allowed to break a reply: a failure here is logged and the visitor
 * still gets their answer.
 */
function cbTouchSession(?int $leadId = null): void {
    global $state;
    try {
        $first = '';
        foreach ($state['history'] as $m) {
            if ($m['role'] === 'user') { $first = $m['content']; break; }
        }

        $row = [
            'first_message' => mb_substr($first, 0, 300),
            'message_count' => count($state['history']),
            'transcript'    => json_encode($state['history'], JSON_UNESCAPED_UNICODE),
            'last_at'       => date('Y-m-d H:i:s'),
        ];
        if ($leadId !== null) $row['lead_id'] = $leadId;

        $existing = dbFetchValue("SELECT id FROM chatbot_sessions WHERE session_id = ?", [$state['sid']]);
        if ($existing) {
            dbUpdateRow('chatbot_sessions', $row, 'id = ?', [$existing]);
            return;
        }

        $row['session_id']  = $state['sid'];
        $row['source_page'] = mb_substr(sanitizeInput((string)($GLOBALS['cbPage'] ?? '')), 0, 300);
        $row['ip_address']  = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $row['user_agent']  = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        dbInsertRow('chatbot_sessions', $row);
    } catch (Throwable $e) {
        error_log('[chatbot] session save failed: ' . $e->getMessage());
    }
}

/** The question at a given step, or null when the flow is finished. */
function cbQuestion(int $step): ?array {
    $qs = chatbotConfig()['questions'];
    return $qs[$step] ?? null;
}

$GLOBALS['cbPage'] = (string)($body['page'] ?? '');
$action = (string)($body['action'] ?? 'chat');

/* ══════════════════════════════════════════════════════════
   LEAD CAPTURE — one question at a time, validated here
══════════════════════════════════════════════════════════ */
if ($action === 'lead_start') {
    $state['lead']['active'] = true;
    $q = cbQuestion($state['lead']['step']);
    if (!$q) cbReply(true, ['done' => true, 'message' => 'Thanks — we already have your details.']);

    cbRemember('assistant', $q['q']);
    cbReply(true, ['mode' => 'lead', 'field' => $q['key'], 'message' => $q['q']]);
}

if ($action === 'lead_cancel') {
    $state['lead']['active'] = false;
    /* Whatever was answered before they backed out is still a lead. */
    if ($state['lead']['step'] > 0) cbSaveLead('partial');
    cbTouchSession();
    cbReply(true, ['mode' => 'chat', 'message' => 'No problem — ask me anything else.']);
}

if ($action === 'lead_answer') {
    if (empty($state['lead']['active'])) {
        cbReply(false, ['message' => 'Please start again.'], 409);
    }

    $step = (int)$state['lead']['step'];
    $q    = cbQuestion($step);
    if (!$q) cbReply(true, ['done' => true, 'message' => 'All done — thank you!']);

    $raw = mb_substr((string)($body['value'] ?? ''), 0, 2000);
    cbRemember('user', $raw);

    $check = chatbotValidateAnswer($q['key'], $raw);
    if (!$check['ok']) {
        /* Stay on this step. The visitor sees why, and the flow does not
           advance with a number nobody can ring. */
        cbRemember('assistant', $check['error']);
        cbTouchSession();
        cbReply(true, [
            'mode'    => 'lead',
            'field'   => $q['key'],
            'invalid' => true,
            'message' => $check['error'],
        ]);
    }

    $state['lead']['answers'][$q['key']] = $check['value'];
    $state['lead']['step'] = ++$step;

    $next = cbQuestion($step);
    if ($next) {
        cbSaveLead('partial');
        cbRemember('assistant', $next['q']);
        cbTouchSession();
        cbReply(true, ['mode' => 'lead', 'field' => $next['key'], 'message' => $next['q']]);
    }

    /* Complete. */
    $state['lead']['active'] = false;
    $leadId = cbSaveLead('new');
    $thanks = 'Thank you, ' . ($state['lead']['answers']['name'] ?? 'there')
            . '! Our team will contact you within 24 hours. Anything else I can help with?';
    cbRemember('assistant', $thanks);

    /* A saved lead must never be lost to a mail failure. */
    try {
        cbNotify($state['lead']['answers'], $leadId);
    } catch (Throwable $e) {
        error_log('[chatbot] notify failed for lead ' . $leadId . ': ' . $e->getMessage());
    }

    /* Link the conversation to the lead it produced. */
    cbTouchSession($leadId);

    cbReply(true, ['mode' => 'chat', 'done' => true, 'message' => $thanks, 'id' => $leadId]);
}

/* ══════════════════════════════════════════════════════════
   CHAT
══════════════════════════════════════════════════════════ */
$message = trim((string)($body['message'] ?? ''));
if ($message === '')            cbReply(false, ['message' => 'Please type a message.'], 422);
if (mb_strlen($message) > 2000) cbReply(false, ['message' => 'That message is too long.'], 422);

cbRemember('user', $message);

/* Only the roles and text go to the model — not the timestamps. */
$history = array_map(
    static fn(array $m): array => ['role' => $m['role'], 'content' => $m['content']],
    $state['history']
);

$result = chatbotComplete($history, $cfg);
if (!$result['ok']) {
    cbReply(false, ['message' => $result['error']], 502);
}

cbRemember('assistant', $result['reply']);
$state['replies']++;

/* Offer the form when the model asked for it, or after enough answers that
   the visitor is plainly interested. Never while a flow is already running. */
$offerLead = !$state['lead']['active']
          && $state['lead']['step'] < count($cfg['questions'])
          && ($result['wantsLead']
              || ($cfg['leadAfter'] > 0 && $state['replies'] === $cfg['leadAfter']));

/* If a conversation is going on long enough to matter, keep the transcript
   even though no details have been given yet — an abandoned chat with a real
   question in it is worth reading. */
if ($state['replies'] >= 2) {
    try { cbSaveLead('partial'); }
    catch (Throwable $e) { error_log('[chatbot] transcript save failed: ' . $e->getMessage()); }
}

/* Every exchange, from the first one. */
cbTouchSession();

cbReply(true, [
    'mode'      => 'chat',
    'message'   => $result['reply'],
    'offerLead' => $offerLead,
]);

/* ── Notification ─────────────────────────────────────────
   Defined last because it is only reached from the completed-lead branch. */
function cbNotify(array $answers, int $leadId): void {
    require_once dirname(__DIR__) . '/includes/email-template.php';
    $to = getNotifyEmails('contact');
    if (!$to) return;

    $rows = [
        ['label' => 'Name',        'value' => htmlspecialchars($answers['name'] ?? '')],
        ['label' => 'Mobile',      'value' => htmlspecialchars($answers['phone'] ?? '')],
        ['label' => 'Email',       'value' => '<a href="mailto:' . htmlspecialchars($answers['email'] ?? '')
                                              . '" style="color:#6A00FF;font-weight:700;">'
                                              . htmlspecialchars($answers['email'] ?? '') . '</a>'],
        ['label' => 'Requirement', 'value' => nl2br(htmlspecialchars($answers['requirement'] ?? ''))],
    ];
    $opts = [
        'type'       => 'info',
        'heading'    => 'New Chatbot Lead',
        'subheading' => 'Captured by the website assistant',
        'body'       => '<p style="color:#334155;font-size:15px;margin:0 0 4px;">'
                      . 'A visitor completed the chatbot enquiry. The full conversation is in the admin panel.</p>',
        'info_rows'  => $rows,
        'cta_text'   => 'View conversation',
        'cta_url'    => ADMIN_URL . '/pages/chatbot-leads.php?view=' . $leadId,
        'cta_color'  => '#6A00FF',
    ];
    foreach ($to as $addr) {
        sendTemplatedEmail($addr, 'New Chatbot Lead: ' . ($answers['name'] ?? 'Website visitor'), $opts);
    }
}
