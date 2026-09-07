<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $error = Auth::adminLogin($email, $password) ?? '';
        /* ── Log the attempt ── */
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS login_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL, email VARCHAR(255) NOT NULL DEFAULT '',
                user_name VARCHAR(255) NOT NULL DEFAULT '', role VARCHAR(50) NOT NULL DEFAULT '',
                ip_address VARCHAR(45) NOT NULL DEFAULT '', user_agent TEXT,
                success TINYINT(1) NOT NULL DEFAULT 1, reason VARCHAR(255) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_u(user_id), INDEX idx_c(created_at), INDEX idx_s(success)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $logUser = dbFetchOne("SELECT id, name, role FROM users WHERE email=?", [$email]);
            $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
            dbExecute("INSERT INTO login_logs (user_id,email,user_name,role,ip_address,user_agent,success,reason) VALUES (?,?,?,?,?,?,?,?)", [
                $logUser['id']   ?? null,
                $email,
                $logUser['name'] ?? '',
                $logUser['role'] ?? '',
                $ip, $ua,
                $error ? 0 : 1,
                $error ?: 'Successful login',
            ]);
        } catch(Exception $e) { /* silently fail */ }

        if (!$error) {
            $redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);
            redirect($redirect && str_starts_with($redirect, ADMIN_URL) ? $redirect : ADMIN_URL . '/dashboard.php');
        }
    }
}
$siteName = getSetting('site_name', 'Appsgain Technologies');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Sign in — <?= e($siteName) ?> Admin</title>
<link rel="icon" href="<?= UPLOADS_URL ?>/logo/appsgain-icon-64.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
/* ══════════════════════════════════════════════════════
   ADMIN SIGN-IN
   Split screen: brand on the left, form on the right.
   White text sits only on the deep half of the brand ramp
   (#D000A8 -> #6A00FF), which runs 4.94:1 to 6.87:1.
   ══════════════════════════════════════════════════════ */
:root{
  --ink:#11162D; --body:#5E6475; --mute:#7E8496; --faint:#9AA0B4;
  --line:#E7E9F0; --soft:#F7F8FC; --card:#FFFFFF;
  --brand:#6A00FF; --solid:#8B00E0;
  --grad:linear-gradient(120deg,#FF8A00 0%,#FF3030 26%,#F50072 50%,#D000A8 70%,#6A00FF 100%);
  --grad-btn:linear-gradient(120deg,#D000A8 0%,#9D00D3 45%,#6A00FF 100%);
  --danger:#C02626; --danger-bg:rgba(192,38,38,.08); --danger-line:rgba(192,38,38,.22);
  --ease:cubic-bezier(.22,1,.36,1);
}
*{ box-sizing:border-box; margin:0; padding:0; }
html,body{ height:100%; }
body{
  font-family:'Inter',system-ui,-apple-system,sans-serif;
  color:var(--body); background:var(--soft);
  -webkit-font-smoothing:antialiased;
}
.sr-only{
  position:absolute; width:1px; height:1px; padding:0; margin:-1px;
  overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; border:0;
}

.lg{ display:grid; grid-template-columns:1.05fr 1fr; min-height:100vh; }

/* ══ Brand panel ══ */
.lg-brand{
  position:relative; overflow:hidden;
  display:flex; flex-direction:column; justify-content:space-between;
  padding:clamp(34px,4vw,60px);
  background-color:#0E0524;
  background-image:
    radial-gradient(90% 70% at 15% 0%, rgba(106,0,255,.42) 0%, transparent 62%),
    radial-gradient(80% 70% at 90% 100%, rgba(208,0,168,.30) 0%, transparent 60%),
    linear-gradient(160deg,#150733 0%,#0E0524 55%,#0A0418 100%);
  color:#fff;
}
/* Slow drift keeps it alive without distracting from the form */
.lg-brand::before{
  content:""; position:absolute; inset:-40%;
  background:radial-gradient(closest-side, rgba(245,0,114,.16), transparent 70%);
  animation:lgDrift 22s ease-in-out infinite alternate;
  pointer-events:none;
}
@keyframes lgDrift{
  from{ transform:translate3d(-8%,-6%,0) scale(1); }
  to  { transform:translate3d(10%,8%,0) scale(1.18); }
}
.lg-brand > *{ position:relative; z-index:1; }

.lg-logo img{ height:38px; width:auto; display:block; }

.lg-pitch{ max-width:30ch; }
.lg-eyebrow{
  display:inline-flex; align-items:center; gap:7px;
  padding:5px 12px; margin-bottom:18px; border-radius:999px;
  background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.18);
  font-size:11px; font-weight:700; letter-spacing:.13em; text-transform:uppercase;
  color:#E9DDFF;
}
.lg-pitch h1{
  font-family:'Plus Jakarta Sans','Inter',sans-serif;
  font-size:clamp(26px,2.8vw,38px); font-weight:800; letter-spacing:-.03em;
  line-height:1.15; color:#fff; margin:0 0 14px;
}
.lg-pitch p{ font-size:15px; line-height:1.75; color:rgba(255,255,255,.72); margin:0; }

.lg-points{ list-style:none; display:flex; flex-direction:column; gap:11px; margin-top:26px; }
.lg-points li{
  display:flex; align-items:center; gap:11px;
  font-size:14px; color:rgba(255,255,255,.86);
}
.lg-points i{
  flex:0 0 auto; display:grid; place-items:center;
  width:26px; height:26px; border-radius:8px;
  background:rgba(255,255,255,.10); color:#D6BBFF; font-size:11px;
}
.lg-foot{ font-size:12.5px; color:rgba(255,255,255,.5); }
.lg-foot a{ color:rgba(255,255,255,.78); text-decoration:none; }
.lg-foot a:hover{ color:#fff; text-decoration:underline; }

/* ══ Form panel ══ */
.lg-form{
  display:flex; align-items:center; justify-content:center;
  padding:clamp(28px,4vw,56px); background:var(--card);
  /* A grid item defaults to min-width:auto, which refuses to shrink below
     its content and pushed this column 8px past a 390px viewport. */
  min-width:0;
}
.lg-box{ width:100%; max-width:392px; }

.lg-mark{ display:none; margin-bottom:22px; }
.lg-mark img{ height:34px; width:auto; }

.lg-h2{
  font-family:'Plus Jakarta Sans','Inter',sans-serif;
  font-size:26px; font-weight:800; letter-spacing:-.025em;
  color:var(--ink); margin:0 0 7px;
}
.lg-sub{ font-size:14px; line-height:1.6; color:var(--body); margin:0 0 26px; }

.lg-alert{
  display:flex; align-items:flex-start; gap:10px;
  padding:12px 14px; margin-bottom:20px; border-radius:11px;
  background:var(--danger-bg); border:1px solid var(--danger-line);
  color:var(--ink); font-size:13.5px; line-height:1.55;
}
.lg-alert i{ color:var(--danger); font-size:14px; margin-top:1px; }

.lg-field{ margin-bottom:16px; }
.lg-field label{
  display:block; margin-bottom:7px;
  font-size:11.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  color:var(--ink);
}
.lg-input{
  position:relative; display:flex; align-items:center;
  background:var(--soft); border:1px solid var(--line); border-radius:11px;
  transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.lg-input > i{
  flex:0 0 auto; width:42px; text-align:center;
  font-size:13px; color:var(--mute); pointer-events:none;
}
.lg-input input{
  flex:1 1 auto; min-width:0;
  height:48px; padding:0 14px 0 0;
  background:none; border:0; outline:none;
  font-family:inherit; font-size:14.5px; color:var(--ink);
}
.lg-input input::placeholder{ color:var(--faint); }
.lg-input:focus-within{
  background:var(--card); border-color:var(--brand);
  box-shadow:0 0 0 3px rgba(106,0,255,.10);
}
.lg-eye{
  flex:0 0 auto; width:42px; height:48px;
  background:none; border:0; cursor:pointer;
  color:var(--mute); font-size:13.5px;
}
.lg-eye:hover{ color:var(--brand); }
.lg-eye:focus-visible{ outline:2px solid var(--brand); outline-offset:-3px; border-radius:8px; }

.lg-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin:4px 0 22px; }
.lg-check{
  display:inline-flex; align-items:center; gap:9px;
  font-size:13.5px; color:var(--body); cursor:pointer; user-select:none;
}
.lg-check input{ width:16px; height:16px; accent-color:var(--brand); cursor:pointer; }

.lg-btn{
  display:flex; align-items:center; justify-content:center; gap:9px;
  width:100%; height:50px; border:0; border-radius:12px; cursor:pointer;
  background-color:var(--solid); background-image:var(--grad-btn);
  background-size:150% 150%; background-position:0% 50%;
  color:#fff; font-family:inherit; font-size:15px; font-weight:600; line-height:1;
  box-shadow:0 6px 18px rgba(106,0,255,.24);
  transition:background-position .3s ease, transform .2s ease, box-shadow .2s ease;
}
.lg-btn:hover:not(:disabled){
  background-position:100% 50%; transform:translateY(-2px);
  box-shadow:0 10px 26px rgba(106,0,255,.30);
}
.lg-btn:focus-visible{ outline:2px solid var(--brand); outline-offset:3px; }
.lg-btn:disabled{ opacity:.72; cursor:progress; transform:none; }
@media (forced-colors: active){
  .lg-btn{ border:2px solid ButtonText; background:ButtonFace; color:ButtonText; }
}

.lg-back{
  display:flex; align-items:center; justify-content:center; gap:8px;
  margin-top:24px; padding-top:20px; border-top:1px solid var(--line);
  font-size:13.5px;
}
.lg-back a{ color:var(--body); text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
.lg-back a:hover{ color:var(--brand); }

/* ══ Responsive ══ */
@media (max-width:900px){
  .lg{ grid-template-columns:1fr; }
  .lg-brand{ display:none; }
  .lg-mark{ display:block; }
  .lg-form{ align-items:flex-start; padding-top:clamp(40px,10vh,80px); background:var(--soft); }
  .lg-box{
    background:var(--card); border:1px solid var(--line); border-radius:20px;
    padding:clamp(24px,5vw,34px);
    box-shadow:0 1px 2px rgba(16,22,47,.03), 0 14px 40px rgba(16,22,47,.07);
  }
}
@media (max-width:420px){
  .lg-h2{ font-size:23px; }
  .lg-row{ flex-direction:column; align-items:flex-start; gap:8px; }
}
@media (prefers-reduced-motion:reduce){
  .lg-brand::before{ animation:none; }
  .lg-btn, .lg-input{ transition:none; }
  .lg-btn:hover:not(:disabled){ transform:none; }
}
</style>
</head>
<body>

<main class="lg">

  <!-- ══ Brand ══ -->
  <aside class="lg-brand">
    <a class="lg-logo" href="<?= SITE_URL ?>" aria-label="<?= e($siteName) ?>">
      <img src="<?= UPLOADS_URL ?>/logo/appsgain-logo-light.png" alt="<?= e($siteName) ?>" width="200" height="38">
    </a>

    <div class="lg-pitch">
      <span class="lg-eyebrow"><i class="fas fa-shield-halved" aria-hidden="true"></i> Secure area</span>
      <h1>Your workspace, ready when you are.</h1>
      <p>Manage content, enquiries, media and settings for <?= e($siteName) ?> from one place.</p>
      <ul class="lg-points">
        <li><i class="fas fa-bolt" aria-hidden="true"></i> Leads and enquiries in real time</li>
        <li><i class="fas fa-pen-to-square" aria-hidden="true"></i> Every page editable without code</li>
        <li><i class="fas fa-lock" aria-hidden="true"></i> Sign-in attempts are logged</li>
      </ul>
    </div>

    <p class="lg-foot">
      &copy; <?= date('Y') ?> <?= e($siteName) ?> &middot;
      <a href="<?= SITE_URL ?>">Visit website</a>
    </p>
  </aside>

  <!-- ══ Form ══ -->
  <section class="lg-form">
    <div class="lg-box">
      <div class="lg-mark">
        <img src="<?= UPLOADS_URL ?>/logo/appsgain-logo.png" alt="<?= e($siteName) ?>" width="180" height="34">
      </div>

      <h2 class="lg-h2">Sign in</h2>
      <p class="lg-sub">Enter your credentials to reach the control panel.</p>

      <?php if ($error): ?>
      <div class="lg-alert" role="alert">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <span><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off" novalidate id="lgForm">
        <?= csrfField() ?>

        <div class="lg-field">
          <label for="email">Email address</label>
          <div class="lg-input">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" placeholder="you@appsgain.in"
                   value="<?= e($_POST['email'] ?? '') ?>" required autofocus
                   autocomplete="username" inputmode="email">
          </div>
        </div>

        <div class="lg-field">
          <label for="password">Password</label>
          <div class="lg-input">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <input type="password" id="password" name="password" placeholder="Your password"
                   required autocomplete="current-password">
            <button type="button" class="lg-eye" id="lgEye"
                    aria-label="Show password" aria-pressed="false">
              <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="lg-row">
          <label class="lg-check">
            <input type="checkbox" name="remember" value="1">
            <span>Keep me signed in</span>
          </label>
        </div>

        <button type="submit" class="lg-btn" id="lgBtn">
          <span class="lg-btn-txt">Sign in</span>
          <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </button>
      </form>

      <p class="lg-back">
        <a href="<?= SITE_URL ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to website</a>
      </p>
    </div>
  </section>
</main>

<script>
/* Password visibility — aria-pressed keeps the state announced. */
(function () {
  var eye = document.getElementById('lgEye');
  var pw  = document.getElementById('password');
  if (!eye || !pw) return;
  eye.addEventListener('click', function () {
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    eye.setAttribute('aria-pressed', String(show));
    eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    eye.querySelector('i').className = 'fas fa-eye' + (show ? '-slash' : '');
    pw.focus();
  });
})();

/* Guard against a double submit creating two login-log rows. */
(function () {
  var form = document.getElementById('lgForm');
  var btn  = document.getElementById('lgBtn');
  if (!form || !btn) return;
  form.addEventListener('submit', function () {
    if (!form.checkValidity || form.checkValidity()) {
      btn.disabled = true;
      btn.querySelector('.lg-btn-txt').textContent = 'Signing in…';
    }
  });
})();
</script>
</body>
</html>
