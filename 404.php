<?php
require_once __DIR__ . '/includes/bootstrap.php';

/* ── Admin-managed redirects ─────────────────────────────
   Unknown URLs are routed here by .htaccess, so this is where the
   redirect table defined in Admin → SEO → Redirects gets applied.
   Nothing consumed that table before, so every redirect an admin
   created silently did nothing. */
$_reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE_PATH !== '' && str_starts_with($_reqPath, BASE_PATH)) {
    $_reqPath = substr($_reqPath, strlen(BASE_PATH));
}
$_reqPath = '/' . ltrim($_reqPath, '/');

try {
    $_rule = dbFetchOne(
        "SELECT id, to_url, type FROM redirects
         WHERE is_active = 1 AND (from_url = ? OR from_url = ?)
         LIMIT 1",
        [$_reqPath, rtrim($_reqPath, '/')]
    );
    if ($_rule) {
        dbExecute("UPDATE redirects SET hits = hits + 1 WHERE id = ?", [$_rule['id']]);
        $_to = trim($_rule['to_url']);
        if (!preg_match('~^https?://~i', $_to)) {
            $_to = rtrim(SITE_URL, '/') . '/' . ltrim($_to, '/');
        }
        $_code = in_array((int)$_rule['type'], [301, 302, 307], true) ? (int)$_rule['type'] : 301;
        header('Location: ' . $_to, true, $_code);
        exit;
    }
} catch (\Throwable $e) {
    /* redirects table missing or unreadable — fall through to the 404 page */
}

http_response_code(404);

$activePage      = '';
$metaPage        = '';                       /* no seo_settings row for 404 */
$pageTitle       = '404 — Page Not Found';
$pageDescription = 'The page you are looking for could not be found. Browse our services, products or get in touch.';
$canonicalUrl    = SITE_URL . '/404.php';

$s        = getAllSettings();
$siteName = $s['site_name'] ?? 'Appsgain Technologies';

/* Offer a few genuinely useful destinations instead of a dead end */
$suggestedServices = dbFetchAll(
    "SELECT name, slug FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/includes/meta.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/layout-header.php'; ?>

<section class="nf-wrap">
  <div class="container nf-inner">
    <div class="nf-code" aria-hidden="true">404</div>
    <h1 class="nf-title">This page has moved on</h1>
    <p class="nf-sub">
      The page you were looking for doesn&rsquo;t exist, was renamed, or has been retired.
      Here&rsquo;s where most people go next.
    </p>

    <div class="nf-actions">
      <a href="<?= SITE_URL ?>/" class="nf-btn nf-btn-primary"><i class="fas fa-home"></i> Go to homepage</a>
      <a href="<?= SITE_URL ?>/contact.php" class="nf-btn nf-btn-ghost"><i class="fas fa-envelope"></i> Contact us</a>
    </div>

    <?php if ($suggestedServices): ?>
    <div class="nf-links">
      <span class="nf-links-label">Popular services</span>
      <ul>
        <?php foreach ($suggestedServices as $svc): ?>
        <li><a href="<?= SITE_URL ?>/service/<?= e($svc['slug']) ?>"><?= e($svc['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
.nf-wrap{
  min-height:62vh; display:flex; align-items:center;
  padding:90px 0 100px;
  background:
    radial-gradient(900px 420px at 18% 0%, color-mix(in srgb, var(--brand-primary) 10%, transparent), transparent 70%),
    radial-gradient(700px 380px at 88% 20%, color-mix(in srgb, var(--brand-secondary) 9%, transparent), transparent 70%),
    var(--ink-050, #f7f8fc);
}
.nf-inner{ max-width:720px; text-align:center; }
.nf-code{
  font-size:clamp(96px,17vw,168px); font-weight:900; line-height:.9;
  letter-spacing:-.05em; margin-bottom:10px;
  background:var(--brand-gradient);
  -webkit-background-clip:text; background-clip:text;
  -webkit-text-fill-color:transparent; color:transparent;
}
.nf-title{ font-size:clamp(24px,4vw,34px); font-weight:800; color:var(--ink-900,#0b1026); margin:0 0 12px; }
.nf-sub{ font-size:16px; line-height:1.7; color:var(--ink-500,#5e6475); margin:0 auto 30px; max-width:520px; }
.nf-actions{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:38px; }
.nf-btn{
  display:inline-flex; align-items:center; gap:9px;
  padding:13px 26px; border-radius:999px; font-weight:700; font-size:14.5px;
  text-decoration:none; transition:transform .18s ease, box-shadow .18s ease;
}
.nf-btn:hover{ transform:translateY(-2px); }
.nf-btn-primary{
  background:linear-gradient(120deg,var(--brand-secondary),var(--brand-primary));
  color:#fff; box-shadow:0 6px 20px color-mix(in srgb, var(--brand-primary) 32%, transparent);
}
.nf-btn-ghost{
  background:#fff; color:var(--brand-primary);
  border:2px solid color-mix(in srgb, var(--brand-primary) 35%, transparent);
}
.nf-links-label{
  display:block; font-size:11.5px; font-weight:800; letter-spacing:.16em;
  text-transform:uppercase; color:var(--ink-400,#8b90a0); margin-bottom:14px;
}
.nf-links ul{ list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:9px; justify-content:center; }
.nf-links a{
  display:inline-block; padding:8px 16px; border-radius:999px;
  background:#fff; border:1px solid var(--ink-200,#e7e9f0);
  color:var(--ink-700,#242a42); font-size:13.5px; font-weight:600; text-decoration:none;
  transition:border-color .18s ease, color .18s ease;
}
.nf-links a:hover{ border-color:var(--brand-primary); color:var(--brand-primary); }
@media (max-width:560px){ .nf-wrap{ padding:60px 0 70px; } }
</style>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
</body>
</html>
