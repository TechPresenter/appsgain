<?php
require_once dirname(__DIR__) . '/includes/admin-layout.php';
$adminPage  = 'profile';
$adminTitle = 'My Profile';

$tab = sanitizeInput($_GET['tab'] ?? 'profile');
if (!in_array($tab, ['profile','password','preferences'])) $tab = 'profile';

$errors = [];

/* ── Auto-add missing columns to users table (runs once) ── */
(function() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = array_column(db()->query("SHOW COLUMNS FROM users")->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        $adds = [];
        if (!in_array('phone',       $cols)) $adds[] = "ADD COLUMN phone       VARCHAR(30)   DEFAULT NULL AFTER email";
        if (!in_array('bio',         $cols)) $adds[] = "ADD COLUMN bio         TEXT          DEFAULT NULL";
        if (!in_array('designation', $cols)) $adds[] = "ADD COLUMN designation VARCHAR(150)  DEFAULT NULL";
        if (!in_array('avatar',      $cols)) $adds[] = "ADD COLUMN avatar      VARCHAR(255)  DEFAULT NULL";
        if (!in_array('preferences', $cols)) $adds[] = "ADD COLUMN preferences JSON          DEFAULT NULL";
        if ($adds) db()->exec("ALTER TABLE users " . implode(', ', $adds));
    } catch (\Throwable $e) {}
})();

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = sanitizeInput($_POST['action'] ?? '');

    /* ── Update profile ── */
    if ($act === 'update_profile') {
        $name   = sanitizeInput($_POST['name'] ?? '');
        $email  = sanitizeInput($_POST['email'] ?? '');
        $phone  = sanitizeInput($_POST['phone'] ?? '');
        $bio    = sanitizeInput($_POST['bio'] ?? '');

        if (!$name)  $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

        /* Check email unique (exclude self) */
        if (empty($errors)) {
            $existing = dbFetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $admin['id']]);
            if ($existing) $errors[] = 'Email already in use by another account.';
        }

        /* Handle avatar upload */
        $avatarPath = $admin['avatar'] ?? '';
        if (!empty($_FILES['avatar']['size'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $errors[] = 'Avatar must be JPG, PNG, GIF or WebP.';
            } else {
                $uploadDir = dirname(dirname(__DIR__)) . '/uploads/avatars/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fileName = 'avatar_' . $admin['id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
                    $avatarPath = 'avatars/' . $fileName;
                }
            }
        }

        if (empty($errors)) {
            try {
                dbExecute(
                    "UPDATE users SET name=?, email=?, phone=?, bio=?, avatar=?, updated_at=NOW() WHERE id=?",
                    [$name, $email, $phone, $bio, $avatarPath, $admin['id']]
                );
            } catch (\PDOException $ex) {
                /* Fallback if some columns don't exist yet */
                dbExecute("UPDATE users SET name=?, email=?, avatar=? WHERE id=?",
                    [$name, $email, $avatarPath, $admin['id']]);
            }
            /* Update session name immediately */
            $_SESSION['admin_name']  = $name;
            $_SESSION['admin_email'] = $email;
            if ($avatarPath) $_SESSION['admin_avatar'] = $avatarPath;
            logActivity('update', 'user', 'Updated own profile');
            setFlash('success', 'Profile updated successfully.');
            redirect(ADMIN_URL . '/pages/profile.php?tab=profile');
        }
    }

    /* ── Change password ── */
    if ($act === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $newPass  = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$current)             $errors[] = 'Current password is required.';
        if (strlen($newPass) < 8)  $errors[] = 'New password must be at least 8 characters.';
        if ($newPass !== $confirm)  $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            /* users table uses `password` column, not `password_hash` */
            $row = dbFetchOne("SELECT password FROM users WHERE id = ?", [$admin['id']]);
            if (!$row || !password_verify($current, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = Auth::hashPassword($newPass);
                dbExecute("UPDATE users SET password=? WHERE id=?", [$hash, $admin['id']]);
                logActivity('update', 'user', 'Changed own password');
                setFlash('success', 'Password changed successfully.');
                redirect(ADMIN_URL . '/pages/profile.php?tab=password');
            }
        }
    }

    /* ── Save preferences ── */
    if ($act === 'save_preferences') {
        $theme      = in_array($_POST['theme'] ?? '', ['light','dark','system']) ? $_POST['theme'] : 'light';
        $sidebar    = in_array($_POST['sidebar'] ?? '', ['full','mini']) ? $_POST['sidebar'] : 'full';
        $notifEmail = !empty($_POST['notif_email']) ? 1 : 0;
        $notifBell  = !empty($_POST['notif_bell'])  ? 1 : 0;
        $timezone   = sanitizeInput($_POST['timezone'] ?? 'Asia/Kolkata');

        $prefs = json_encode([
            'theme'       => $theme,
            'sidebar'     => $sidebar,
            'notif_email' => $notifEmail,
            'notif_bell'  => $notifBell,
            'timezone'    => $timezone,
        ]);
        try {
            dbExecute("UPDATE users SET preferences=? WHERE id=?", [$prefs, $admin['id']]);
        } catch (\Throwable $e) { /* preferences column not yet created — skip */ }
        logActivity('update', 'user', 'Saved preferences');
        setFlash('success', 'Preferences saved.');
        redirect(ADMIN_URL . '/pages/profile.php?tab=preferences');
    }
}

/* Reload fresh admin data from correct `users` table */
$adminData = dbFetchOne("SELECT * FROM users WHERE id = ?", [$admin['id']]);
$adminData = $adminData ?: $admin;

/* Parse preferences */
$prefs = [];
if (!empty($adminData['preferences'])) {
    $prefs = json_decode($adminData['preferences'], true) ?: [];
}

require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<style>
.profile-tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--border);padding-bottom:0}
.profile-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;font-size:13.5px;font-weight:600;color:var(--gray);border-radius:10px 10px 0 0;cursor:pointer;text-decoration:none;transition:var(--transition);margin-bottom:-2px;border:2px solid transparent;border-bottom:none}
.profile-tab:hover{color:var(--violet);background:var(--violet-lt)}
.profile-tab.active{color:var(--violet);background:var(--white);border-color:var(--border);border-bottom-color:var(--white)}
.avatar-upload-area{position:relative;width:100px;height:100px;flex-shrink:0}
.avatar-upload-area .avatar-img{width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--border)}
.avatar-upload-area .avatar-placeholder{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:900;color:#fff}
.avatar-upload-btn{position:absolute;bottom:2px;right:2px;width:28px;height:28px;border-radius:50%;background:var(--violet);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;border:2px solid #fff;transition:.2s}
.avatar-upload-btn:hover{background:var(--violet-d);transform:scale(1.1)}
.pref-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border2)}
.pref-row:last-child{border-bottom:none}
.pref-info .pref-label{font-size:14px;font-weight:600;color:var(--primary)}
.pref-info .pref-desc{font-size:12.5px;color:var(--gray);margin-top:3px}
.theme-btn-wrap{display:flex;gap:8px}
.theme-btn{padding:7px 16px;border-radius:9px;font-size:13px;font-weight:600;border:1.5px solid var(--border);color:var(--gray);background:#fff;cursor:pointer;transition:var(--transition);display:flex;align-items:center;gap:6px}
.theme-btn.selected{border-color:var(--violet);color:var(--violet);background:var(--violet-lt)}
</style>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-user-circle" style="color:var(--violet)"></i> My Profile</h1>
    <p class="page-subtitle">Manage your account settings, password, and preferences.</p>
  </div>
</div>

<!-- Tabs -->
<div class="profile-tabs">
  <a href="?tab=profile" class="profile-tab <?= $tab==='profile'?'active':'' ?>"><i class="fas fa-user-edit"></i> Profile Info</a>
  <a href="?tab=password" class="profile-tab <?= $tab==='password'?'active':'' ?>"><i class="fas fa-lock"></i> Change Password</a>
  <a href="?tab=preferences" class="profile-tab <?= $tab==='preferences'?'active':'' ?>"><i class="fas fa-palette"></i> Preferences</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= implode(' · ', array_map('e', $errors)) ?></div>
<?php endif; ?>

<!-- ── TAB: PROFILE ── -->
<?php if ($tab === 'profile'): ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-id-card" style="color:var(--violet)"></i> Profile Information</h3></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_profile">
      <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap;margin-bottom:28px">
        <!-- Avatar -->
        <div>
          <div class="avatar-upload-area">
            <?php if (!empty($adminData['avatar'])): ?>
              <img src="<?= getImageUrl($adminData['avatar']) ?>" alt="" class="avatar-img" id="avatarPreview">
            <?php else: ?>
              <div class="avatar-placeholder" id="avatarPreview"><?= strtoupper(substr($adminData['name'],0,1)) ?></div>
            <?php endif; ?>
            <label for="avatarInput" class="avatar-upload-btn" title="Change photo"><i class="fas fa-camera"></i></label>
          </div>
          <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
          <p style="font-size:11px;color:var(--gray);margin-top:8px;text-align:center">JPG, PNG, WebP<br>Max 2MB</p>
        </div>
        <!-- Name/Email/Bio -->
        <div style="flex:1;min-width:260px">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name <span class="required">*</span></label>
              <input type="text" class="form-control" name="name" value="<?= e($adminData['name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Email Address <span class="required">*</span></label>
              <input type="email" class="form-control" name="email" value="<?= e($adminData['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-control" name="phone" value="<?= e($adminData['phone'] ?? '') ?>" placeholder="+91 xxxxxxxxxx">
          </div>
          <div class="form-group">
            <label>Bio / About Yourself</label>
            <textarea class="form-control" name="bio" rows="3" placeholder="Write a short bio…"><?= e($adminData['bio'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
      <!-- Read-only info -->
      <div style="background:var(--light);border-radius:12px;padding:16px 20px;margin-bottom:20px">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
          <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Role</div><div style="font-size:14px;font-weight:600;color:var(--primary)"><?= ucfirst(e($adminData['role'])) ?></div></div>
          <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Member Since</div><div style="font-size:14px;font-weight:600;color:var(--primary)"><?= date('M Y', strtotime($adminData['created_at'] ?? 'now')) ?></div></div>
          <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Last Login</div><div style="font-size:14px;font-weight:600;color:var(--primary)"><?= !empty($adminData['last_login']) ? date('M d, Y H:i', strtotime($adminData['last_login'])) : 'N/A' ?></div></div>
          <div><div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Status</div><span class="badge badge-success"><i class="fas fa-circle" style="font-size:8px"></i> Active</span></div>
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        <a href="profile.php?tab=profile" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>

<!-- ── TAB: PASSWORD ── -->
<?php elseif ($tab === 'password'): ?>
<div class="card" style="max-width:580px">
  <div class="card-header"><h3><i class="fas fa-lock" style="color:var(--violet)"></i> Change Password</h3></div>
  <div class="card-body">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Current Password <span class="required">*</span></label>
        <div style="position:relative">
          <input type="password" class="form-control" name="current_password" id="curPwd" required placeholder="Your current password">
          <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray);font-size:14px" onclick="togglePwd('curPwd',this)"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="form-group">
        <label>New Password <span class="required">*</span></label>
        <div style="position:relative">
          <input type="password" class="form-control" name="new_password" id="newPwd" required placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
          <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray);font-size:14px" onclick="togglePwd('newPwd',this)"><i class="fas fa-eye"></i></button>
        </div>
        <div id="pwdStrength" style="margin-top:8px;display:none">
          <div style="height:5px;border-radius:4px;background:var(--light2);overflow:hidden">
            <div id="pwdBar" style="height:100%;border-radius:4px;transition:.3s;width:0"></div>
          </div>
          <div id="pwdLabel" style="font-size:12px;margin-top:4px;color:var(--gray)"></div>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm New Password <span class="required">*</span></label>
        <div style="position:relative">
          <input type="password" class="form-control" name="confirm_password" id="confPwd" required placeholder="Repeat new password">
          <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray);font-size:14px" onclick="togglePwd('confPwd',this)"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <!-- Password rules -->
      <div style="background:var(--light);border-radius:12px;padding:14px 18px;margin-bottom:20px">
        <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px">Password Requirements</div>
        <div style="display:grid;gap:6px">
          <?php $rules=[['fa-check-circle','At least 8 characters'],['fa-check-circle','Mix of uppercase & lowercase'],['fa-check-circle','At least one number'],['fa-check-circle','At least one special character (recommended)']]; foreach($rules as [$ri,$rl]): ?>
          <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray)"><i class="fas <?= $ri ?>" style="color:var(--emerald)"></i><?= $rl ?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
    </form>
  </div>
</div>

<!-- ── TAB: PREFERENCES ── -->
<?php elseif ($tab === 'preferences'): ?>
<div class="card" style="max-width:700px">
  <div class="card-header"><h3><i class="fas fa-palette" style="color:var(--violet)"></i> Preferences</h3></div>
  <div class="card-body">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_preferences">
      <input type="hidden" name="theme" id="prefTheme" value="<?= e($prefs['theme'] ?? 'light') ?>">
      <input type="hidden" name="sidebar" id="prefSidebar" value="<?= e($prefs['sidebar'] ?? 'full') ?>">

      <div class="pref-row">
        <div class="pref-info">
          <div class="pref-label">Color Theme</div>
          <div class="pref-desc">Choose how the admin panel looks</div>
        </div>
        <div class="theme-btn-wrap">
          <?php foreach(['light'=>['fa-sun','Light'],'dark'=>['fa-moon','Dark'],'system'=>['fa-adjust','System']] as $tv=>[$ti,$tl]): ?>
          <button type="button" class="theme-btn <?= ($prefs['theme'] ?? 'light') === $tv ? 'selected' : '' ?>" onclick="setPref('theme','<?= $tv ?>',this)">
            <i class="fas <?= $ti ?>"></i> <?= $tl ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pref-row">
        <div class="pref-info">
          <div class="pref-label">Sidebar Mode</div>
          <div class="pref-desc">Default sidebar state when you log in</div>
        </div>
        <div class="theme-btn-wrap">
          <?php foreach(['full'=>['fa-indent','Full'],'mini'=>['fa-outdent','Mini']] as $sv=>[$si,$sl]): ?>
          <button type="button" class="theme-btn <?= ($prefs['sidebar'] ?? 'full') === $sv ? 'selected' : '' ?>" onclick="setPrefSidebar('<?= $sv ?>',this)">
            <i class="fas <?= $si ?>"></i> <?= $sl ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pref-row">
        <div class="pref-info">
          <div class="pref-label">Email Notifications</div>
          <div class="pref-desc">Receive summary emails for new leads and alerts</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="notif_email" <?= !empty($prefs['notif_email']) ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="pref-row">
        <div class="pref-info">
          <div class="pref-label">In-App Notifications</div>
          <div class="pref-desc">Show notification bell for new leads and comments</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="notif_bell" <?= !empty($prefs['notif_bell']) ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="pref-row">
        <div class="pref-info">
          <div class="pref-label">Timezone</div>
          <div class="pref-desc">Used for displaying activity log timestamps</div>
        </div>
        <select name="timezone" class="form-control" style="width:220px">
          <?php $tzList=['Asia/Kolkata'=>'India (IST, UTC+5:30)','Asia/Dubai'=>'Dubai (UTC+4)','America/New_York'=>'New York (EST)','America/Los_Angeles'=>'Los Angeles (PST)','Europe/London'=>'London (GMT)','Europe/Berlin'=>'Berlin (CET)','Asia/Singapore'=>'Singapore (SGT)','Asia/Tokyo'=>'Tokyo (JST)','Australia/Sydney'=>'Sydney (AEST)'];
          foreach($tzList as $tz=>$tzl): ?>
          <option value="<?= $tz ?>" <?= ($prefs['timezone'] ?? 'Asia/Kolkata') === $tz ? 'selected' : '' ?>><?= $tzl ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:24px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
      </div>
    </form>
  </div>
</div>

<!-- Danger zone -->
<div class="card" style="max-width:700px;margin-top:20px;border-color:rgba(225,29,72,.25)">
  <div class="card-header" style="border-color:rgba(225,29,72,.15)"><h3 style="color:var(--rose)"><i class="fas fa-exclamation-triangle"></i> Session &amp; Security</h3></div>
  <div class="card-body">
    <div class="pref-row">
      <div class="pref-info">
        <div class="pref-label">Sign Out All Devices</div>
        <div class="pref-desc">Invalidates all active admin sessions (you will be signed out)</div>
      </div>
      <a href="<?= ADMIN_URL ?>/logout.php" onclick="return confirm('Sign out of all sessions?')" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Sign Out All</a>
    </div>
    <div class="pref-row" style="border-bottom:none">
      <div class="pref-info">
        <div class="pref-label">View Login History</div>
        <div class="pref-desc">See all recent login attempts for your account</div>
      </div>
      <a href="<?= ADMIN_URL ?>/pages/login-history.php" class="btn btn-secondary btn-sm"><i class="fas fa-history"></i> View History</a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function previewAvatar(input) {
  if (!input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var prev = document.getElementById('avatarPreview');
    if (prev.tagName === 'IMG') {
      prev.src = e.target.result;
    } else {
      var img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'avatar-img';
      img.id = 'avatarPreview';
      prev.replaceWith(img);
    }
  };
  reader.readAsDataURL(input.files[0]);
}
function togglePwd(id, btn) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = 'fas fa-' + (el.type === 'password' ? 'eye' : 'eye-slash');
}
function checkStrength(val) {
  var bar = document.getElementById('pwdBar');
  var lbl = document.getElementById('pwdLabel');
  document.getElementById('pwdStrength').style.display = val ? '' : 'none';
  var score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  var colors = ['#ef4444','#f97316','#eab308','#22c55e'];
  var labels = ['Weak','Fair','Good','Strong'];
  bar.style.width = (score*25)+'%';
  bar.style.background = colors[score-1] || '#ef4444';
  lbl.textContent = labels[score-1] || 'Very Weak';
  lbl.style.color = colors[score-1] || '#ef4444';
}
function setPref(type, val, btn) {
  document.getElementById('prefTheme').value = val;
  btn.closest('.theme-btn-wrap').querySelectorAll('.theme-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  if (val === 'dark') document.documentElement.setAttribute('data-theme','dark');
  else document.documentElement.removeAttribute('data-theme');
}
function setPrefSidebar(val, btn) {
  document.getElementById('prefSidebar').value = val;
  btn.closest('.theme-btn-wrap').querySelectorAll('.theme-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
