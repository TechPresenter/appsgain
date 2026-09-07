<?php
$adminTitle = 'SEO Manager';
$adminPage  = 'seo';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$tab = sanitizeInput($_GET['tab'] ?? 'pages');

/* Entity signals — Organization / Knowledge Panel. Kept in `settings`
   because they describe the company, not a single page. */
const SEO_ENTITY_KEYS = [
    'company_legal_name', 'company_founding_date', 'company_founder',
    'company_employees', 'company_vat_id', 'company_cin',
    'company_price_range', 'site_tagline',
    'review_rating_value', 'review_rating_count', 'review_rating_source',
    'company_wikipedia', 'company_crunchbase', 'company_clutch',
    'company_goodfirms', 'company_glassdoor',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_entity') {
    verifyCsrf();
    foreach (SEO_ENTITY_KEYS as $k) {
        saveSetting($k, sanitizeInput($_POST[$k] ?? ''), 'seo');
    }
    /* A rating without a count, or without a named source, is not
       publishable — drop both rather than emit something unverifiable. */
    $rv = trim((string)($_POST['review_rating_value'] ?? ''));
    $rc = (int)($_POST['review_rating_count'] ?? 0);
    $rs = trim((string)($_POST['review_rating_source'] ?? ''));
    if ($rv === '' || $rc <= 0 || $rs === '') {
        saveSetting('review_rating_value', '', 'seo');
        saveSetting('review_rating_count', '', 'seo');
        if ($rv !== '' || $rc > 0) {
            setFlash('error', 'A rating needs a value, a count and a named source. Nothing was published.');
            redirect(ADMIN_URL . '/pages/seo.php?tab=entity');
        }
    }
    logActivity('update', 'seo', 'Updated entity signals');
    setFlash('success', 'Entity details saved.');
    redirect(ADMIN_URL . '/pages/seo.php?tab=entity');
}
$robotsFile = ROOT_PATH . '/robots.txt';

/* Auto-create redirects table */
try {
    dbExecute("CREATE TABLE IF NOT EXISTS redirects (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        from_url    VARCHAR(500) NOT NULL,
        to_url      VARCHAR(500) NOT NULL,
        type        SMALLINT DEFAULT 301,
        is_active   TINYINT(1) DEFAULT 1,
        hits        INT DEFAULT 0,
        created_at  DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB");
} catch (\Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $postAction = $_POST['action'] ?? '';

  if ($postAction === 'save_page_seo') {
    /* Column names must match the seo_settings schema exactly. An earlier
       version wrote meta_desc / canonical / og_desc / noindex, none of which
       exist, so nothing typed here ever reached the live <head>. */
    $pageKey = sanitizeInput($_POST['page_key'] ?? '');

    if ($pageKey) {
      $twCard = sanitizeInput($_POST['twitter_card'] ?? 'summary_large_image');
      if (!in_array($twCard, ['summary', 'summary_large_image'], true)) {
        $twCard = 'summary_large_image';
      }
      $ogType = sanitizeInput($_POST['og_type'] ?? 'website');
      if (!in_array($ogType, ['website', 'article', 'product', 'profile'], true)) {
        $ogType = 'website';
      }

      $noindex  = isset($_POST['noindex'])  ? 1 : 0;
      $nofollow = isset($_POST['nofollow']) ? 1 : 0;

      $data = [
        'meta_title'          => sanitizeInput($_POST['meta_title'] ?? ''),
        'meta_description'    => sanitizeInput($_POST['meta_description'] ?? ''),
        'meta_keywords'       => sanitizeInput($_POST['meta_keywords'] ?? ''),
        'canonical_url'       => trim((string)($_POST['canonical_url'] ?? '')),
        'og_title'            => sanitizeInput($_POST['og_title'] ?? ''),
        'og_description'      => sanitizeInput($_POST['og_description'] ?? ''),
        'og_image'            => trim((string)($_POST['og_image'] ?? '')),
        'og_type'             => $ogType,
        'twitter_card'        => $twCard,
        'twitter_title'       => sanitizeInput($_POST['twitter_title'] ?? ''),
        'twitter_description' => sanitizeInput($_POST['twitter_description'] ?? ''),
        'twitter_image'       => trim((string)($_POST['twitter_image'] ?? '')),
        'noindex'             => $noindex,
        'nofollow'            => $nofollow,
        'robots'              => ($noindex ? 'noindex' : 'index') . ',' . ($nofollow ? 'nofollow' : 'follow'),
        'schema_markup'       => trim((string)($_POST['schema_markup'] ?? '')),
        'is_active'           => isset($_POST['is_active']) ? 1 : 0,
      ];

      /* Refuse invalid JSON rather than shipping broken structured data */
      if ($data['schema_markup'] !== '') {
        json_decode($data['schema_markup']);
        if (json_last_error() !== JSON_ERROR_NONE) {
          setFlash('error', 'Schema JSON is invalid (' . json_last_error_msg() . ') - nothing was saved.');
          redirect(ADMIN_URL . '/pages/seo.php?tab=pages&page=' . urlencode($pageKey));
        }
      }

      $existing = dbFetchOne("SELECT id FROM seo_settings WHERE page_key = ?", [$pageKey]);
      if ($existing) {
        dbUpdateRow('seo_settings', $data, 'page_key = ?', [$pageKey]);
      } else {
        $data['page_key']   = $pageKey;
        $data['page_label'] = sanitizeInput($_POST['page_label'] ?? $pageKey);
        dbInsertRow('seo_settings', $data);
      }
      logActivity('update', 'seo', "Updated SEO for page: {$pageKey}");
      setFlash('success', 'SEO saved - it is live on the ' . $pageKey . ' page now.');
    }
    redirect(ADMIN_URL . '/pages/seo.php?tab=pages&page=' . urlencode($pageKey));
  }

  if ($postAction === 'save_robots') {
    $content = $_POST['robots_content'] ?? '';
    if (file_put_contents($robotsFile, $content) !== false) {
      logActivity('update', 'seo', 'Updated robots.txt');
      setFlash('success', 'robots.txt saved successfully!');
    } else {
      setFlash('error', 'Could not write robots.txt — check file permissions.');
    }
    redirect(ADMIN_URL . '/pages/seo.php?tab=robots');
  }

  if ($postAction === 'add_redirect') {
    $from = '/' . ltrim(sanitizeInput($_POST['from_url'] ?? ''), '/');
    $to   = sanitizeInput($_POST['to_url'] ?? '');
    $type = in_array((int)($_POST['type']??301), [301,302,307]) ? (int)$_POST['type'] : 301;
    if ($from && $to && $from !== $to) {
      dbInsertRow('redirects', ['from_url'=>$from,'to_url'=>$to,'type'=>$type]);
      setFlash('success', "Redirect added: {$from} → {$to}");
    }
    redirect(ADMIN_URL . '/pages/seo.php?tab=redirects');
  }

  if ($postAction === 'delete_redirect') {
    $rid = (int)($_POST['id'] ?? 0);
    if ($rid) dbExecute("DELETE FROM redirects WHERE id=?", [$rid]);
    setFlash('success', 'Redirect deleted.');
    redirect(ADMIN_URL . '/pages/seo.php?tab=redirects');
  }

  if ($postAction === 'toggle_redirect') {
    $rid = (int)($_POST['id'] ?? 0);
    if ($rid) dbExecute("UPDATE redirects SET is_active = 1 - is_active WHERE id=?", [$rid]);
    redirect(ADMIN_URL . '/pages/seo.php?tab=redirects');
  }
}

/* The tab list is driven by seo_settings, so every page with a row gets an
   editor here automatically. Icons are cosmetic look-ups. */
$pageIcons = [
  'home'=>'fa-home', 'about'=>'fa-building', 'services'=>'fa-layer-group',
  'contact'=>'fa-envelope', 'blog'=>'fa-newspaper', 'portfolio'=>'fa-briefcase',
  'faq'=>'fa-question-circle', 'careers'=>'fa-user-tie', 'gallery'=>'fa-images',
  'products'=>'fa-box-open', 'apps'=>'fa-mobile-alt', 'clients'=>'fa-handshake',
  'partners'=>'fa-people-arrows', 'testimonials'=>'fa-quote-right',
  'support'=>'fa-life-ring', 'sitemap'=>'fa-sitemap',
  'privacy'=>'fa-user-shield', 'app-privacy'=>'fa-mobile-screen-button',
  'terms'=>'fa-file-contract', 'refund'=>'fa-undo-alt',
  'shipping'=>'fa-truck', 'cookie'=>'fa-cookie-bite',
  'disclaimer'=>'fa-exclamation-circle',
];
$pages = [];
foreach (dbFetchAll("SELECT page_key, page_label FROM seo_settings ORDER BY id ASC") as $_pr) {
  $pages[$_pr['page_key']] = [
    $_pr['page_label'] ?: ucwords(str_replace('-', ' ', $_pr['page_key'])),
    $pageIcons[$_pr['page_key']] ?? 'fa-file-lines',
  ];
}
$redirects = dbFetchAll("SELECT * FROM redirects ORDER BY created_at DESC") ?? [];
$robotsContent = file_exists($robotsFile) ? file_get_contents($robotsFile) : "User-agent: *\nDisallow: /admin/\nDisallow: /includes/\nSitemap: " . SITE_URL . "/sitemap.php\n";

$selectedPage = sanitizeInput($_GET['page'] ?? 'home');
$seoData      = dbFetchOne("SELECT * FROM seo_settings WHERE page_key = ?", [$selectedPage]) ?: [];

$selectedPageData = $pages[$selectedPage] ?? ['Page', 'fa-file'];
require_once dirname(__DIR__) . '/includes/admin-head.php';
?>
<div class="page-header">
  <div>
    <div class="page-breadcrumb"><a href="<?= ADMIN_URL ?>/dashboard.php">Dashboard</a><span class="sep">/</span><span class="current">SEO Manager</span></div>
    <h1 class="page-title" style="margin-top:4px">SEO Manager</h1>
  </div>
  <div class="page-header-actions">
    <a href="<?= SITE_URL ?>/sitemap.php" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-sitemap"></i> View Sitemap</a>
    <a href="<?= SITE_URL ?>/robots.txt" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-robot"></i> robots.txt</a>
  </div>
</div>

<!-- SEO Tabs -->
<div class="admin-tabs" style="margin-bottom:24px">
  <?php foreach(['pages'=>['fa-file-alt','Page SEO'],'robots'=>['fa-robot','Robots.txt'],'redirects'=>['fa-directions','Redirects'],'schema'=>['fa-code','Schema Markup'],'entity'=>['fa-building-columns','Entity & Ratings']] as $t=>[$ti,$tl]): ?>
  <button class="admin-tab <?= $tab===$t?'active':'' ?>" onclick="location.href='?tab=<?= $t ?>'">
    <i class="fas <?= $ti ?>"></i> <?= $tl ?>
  </button>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'pages'): ?>
<!-- ═══ PAGE SEO TAB ═══ -->
<div style="display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:start">
  <div class="card">
    <div class="card-header"><h3>Pages</h3></div>
    <div style="padding:8px">
      <?php foreach ($pages as $key => [$label, $ico]): ?>
      <a href="?tab=pages&page=<?= $key ?>" style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--text);margin-bottom:2px;transition:.18s;<?= $selectedPage === $key ? 'background:rgba(37,99,235,.1);color:var(--blue);' : '' ?>" onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background='<?= $selectedPage===$key?'rgba(37,99,235,.1)':'' ?>'">
        <i class="fas <?= $ico ?>" style="width:16px;font-size:12px;color:<?= $selectedPage===$key?'var(--blue)':'var(--gray)' ?>"></i>
        <?= e($label) ?>
        <?php
        $hasSeo = dbFetchValue("SELECT id FROM seo_settings WHERE page_key=?", [$key]);
        if ($hasSeo) echo '<span style="width:6px;height:6px;border-radius:50%;background:var(--emerald);margin-left:auto;flex-shrink:0"></span>';
        ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_page_seo">
      <input type="hidden" name="page_key" value="<?= e($selectedPage) ?>">

      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <span><i class="fas fa-search-plus" style="color:var(--blue);margin-right:8px"></i>Meta Tags &mdash; <?= e($selectedPageData[0]) ?></span>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
            <input type="checkbox" name="is_active" <?= (!isset($seoData['is_active']) || $seoData['is_active']) ? 'checked' : '' ?> style="width:16px;height:16px">
            Enabled
          </label>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label>Meta Title <small style="color:var(--gray);font-weight:400">(50&ndash;60 chars)</small></label>
            <input type="text" name="meta_title" id="metaTitle" class="form-control" value="<?= e($seoData['meta_title'] ?? '') ?>" oninput="uc(this,'tc1',60)" maxlength="200">
            <div class="form-hint"><span id="tc1"><?= strlen($seoData['meta_title'] ?? '') ?></span>/60</div>
          </div>
          <div class="form-group">
            <label>Meta Description <small style="color:var(--gray);font-weight:400">(150&ndash;160 chars)</small></label>
            <textarea name="meta_description" id="metaDesc" class="form-control" rows="3" oninput="uc(this,'tc2',160)" maxlength="500"><?= e($seoData['meta_description'] ?? '') ?></textarea>
            <div class="form-hint"><span id="tc2"><?= strlen($seoData['meta_description'] ?? '') ?></span>/160</div>
          </div>
          <div class="form-group">
            <label>Meta Keywords <small style="color:var(--gray);font-weight:400">(comma separated &mdash; optional)</small></label>
            <input type="text" name="meta_keywords" class="form-control" value="<?= e($seoData['meta_keywords'] ?? '') ?>" placeholder="software development, mobile apps, ERP">
          </div>
          <div style="display:flex;gap:20px;margin-bottom:4px;flex-wrap:wrap;align-items:flex-start">
            <div class="form-group" style="flex:1;min-width:260px;margin:0">
              <label>Canonical URL <small style="color:var(--gray);font-weight:400">(blank = this page&rsquo;s own URL)</small></label>
              <input type="url" name="canonical_url" class="form-control" value="<?= e($seoData['canonical_url'] ?? '') ?>" placeholder="<?= SITE_URL ?>/">
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;padding-top:26px">
              <label style="display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="noindex" <?= !empty($seoData['noindex']) ? 'checked' : '' ?> style="width:16px;height:16px">
                No-index this page
              </label>
              <label style="display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="nofollow" <?= !empty($seoData['nofollow']) ? 'checked' : '' ?> style="width:16px;height:16px">
                No-follow links
              </label>
            </div>
          </div>
          <div class="form-hint" style="margin-top:6px">
            Robots tag that will be emitted:
            <strong><?= e((!empty($seoData['noindex']) ? 'noindex' : 'index') . ',' . (!empty($seoData['nofollow']) ? 'nofollow' : 'follow')) ?></strong>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span><i class="fab fa-facebook" style="color:var(--violet);margin-right:8px"></i>Open Graph (Facebook, LinkedIn, WhatsApp)</span></div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="margin:0">
              <label>OG Title</label>
              <input type="text" name="og_title" class="form-control" value="<?= e($seoData['og_title'] ?? '') ?>" placeholder="Defaults to meta title">
            </div>
            <div class="form-group" style="margin:0">
              <label>OG Type</label>
              <select name="og_type" class="form-control">
                <?php foreach (['website'=>'Website','article'=>'Article','product'=>'Product','profile'=>'Profile'] as $ov => $ol): ?>
                <option value="<?= $ov ?>" <?= ($seoData['og_type'] ?? 'website') === $ov ? 'selected' : '' ?>><?= $ol ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-top:16px">
            <label>OG Description</label>
            <textarea name="og_description" class="form-control" rows="2" placeholder="Defaults to meta description"><?= e($seoData['og_description'] ?? '') ?></textarea>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>OG Image <small style="color:var(--gray);font-weight:400">(1200&times;630 &mdash; path under /uploads or a full URL)</small></label>
            <input type="text" name="og_image" class="form-control" value="<?= e($seoData['og_image'] ?? '') ?>" placeholder="seo/og-home.jpg">
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span><i class="fab fa-x-twitter" style="color:var(--violet);margin-right:8px"></i>X / Twitter Card</span></div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="margin:0">
              <label>Card Type</label>
              <select name="twitter_card" class="form-control">
                <option value="summary_large_image" <?= ($seoData['twitter_card'] ?? 'summary_large_image') === 'summary_large_image' ? 'selected' : '' ?>>Summary with large image</option>
                <option value="summary" <?= ($seoData['twitter_card'] ?? '') === 'summary' ? 'selected' : '' ?>>Summary</option>
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label>Card Title</label>
              <input type="text" name="twitter_title" class="form-control" value="<?= e($seoData['twitter_title'] ?? '') ?>" placeholder="Defaults to OG title">
            </div>
          </div>
          <div class="form-group" style="margin-top:16px">
            <label>Card Description</label>
            <textarea name="twitter_description" class="form-control" rows="2" placeholder="Defaults to OG description"><?= e($seoData['twitter_description'] ?? '') ?></textarea>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Card Image</label>
            <input type="text" name="twitter_image" class="form-control" value="<?= e($seoData['twitter_image'] ?? '') ?>" placeholder="Defaults to OG image">
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span><i class="fas fa-code" style="color:var(--violet);margin-right:8px"></i>Structured Data (JSON-LD)</span></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:0">
            <label>Extra schema for this page <small style="color:var(--gray);font-weight:400">(optional &mdash; Organization / WebSite / Breadcrumb schema is generated automatically)</small></label>
            <textarea name="schema_markup" class="form-control" rows="7" style="font-family:monospace;font-size:12.5px" placeholder='{"@context":"https://schema.org","@type":"Service","name":"..."}'><?= e($seoData['schema_markup'] ?? '') ?></textarea>
            <div class="form-hint">Must be valid JSON. Invalid JSON is rejected on save so broken markup never reaches the page.</div>
          </div>
        </div>
      </div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span><i class="fas fa-eye" style="color:var(--emerald);margin-right:8px"></i>Google Search Preview</span></div>
        <div class="card-body">
          <div style="border:1.5px solid var(--border);border-radius:12px;padding:18px;background:var(--light);font-family:Arial,sans-serif">
            <div style="font-size:12px;color:#202124;margin-bottom:2px"><?= SITE_URL ?> › <?= $selectedPage ?></div>
            <div id="prevTitle" style="font-size:18px;color:#1a0dab;margin-bottom:4px;cursor:pointer"><?= e($seoData['meta_title'] ?? $selectedPageData[0]) ?></div>
            <div id="prevDesc" style="font-size:13.5px;color:#4d5156;line-height:1.55"><?= e($seoData['meta_description'] ?? '') ?: 'No meta description set for this page.' ?></div>
          </div>
          <div style="margin-top:16px;background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.15);border-radius:10px;padding:14px 16px">
            <?php
            $score = 0;
            $mt = $seoData['meta_title'] ?? ''; $md = $seoData['meta_description'] ?? '';
            if (strlen($mt) >= 40 && strlen($mt) <= 65) $score += 25;
            elseif ($mt) $score += 12;
            if (strlen($md) >= 120 && strlen($md) <= 165) $score += 25;
            elseif ($md) $score += 12;
            if ($seoData['og_image'] ?? '') $score += 25;
            if ($seoData['canonical_url'] ?? '') $score += 25;
            $scoreColor = $score >= 75 ? '#059669' : ($score >= 50 ? '#d97706' : '#e11d48');
            ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
              <div style="font-size:13px;font-weight:700;color:var(--text)">SEO Score</div>
              <div style="font-size:20px;font-weight:900;color:<?= $scoreColor ?>"><?= $score ?>/100</div>
            </div>
            <div style="height:6px;background:var(--light2);border-radius:3px;overflow:hidden">
              <div style="width:<?= $score ?>%;height:100%;background:<?= $scoreColor ?>;border-radius:3px"></div>
            </div>
            <div style="margin-top:10px;font-size:12px;color:var(--gray);display:flex;flex-wrap:wrap;gap:6px">
              <?php
              $checks=[[$mt&&strlen($mt)>=40&&strlen($mt)<=65,'Meta title (40-65 chars)'],[$md&&strlen($md)>=120&&strlen($md)<=165,'Meta description (120-165 chars)'],[$seoData['og_image']??'','OG image set'],[$seoData['canonical_url']??'','Canonical URL set']];
              foreach($checks as[$ok,$label]):?>
              <span style="display:flex;align-items:center;gap:4px;background:<?=$ok?'rgba(5,150,105,.1)':'rgba(225,29,72,.08)'?>;color:<?=$ok?'#059669':'#e11d48'?>;padding:3px 8px;border-radius:20px;font-weight:600">
                <i class="fas fa-<?=$ok?'check':'times'?>" style="font-size:9px"></i> <?= $label ?>
              </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save SEO Settings</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'robots'): ?>
<!-- ═══ ROBOTS.TXT TAB ═══ -->
<div style="max-width:800px">
  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_robots">
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <span><i class="fas fa-robot" style="color:var(--blue);margin-right:8px"></i>robots.txt Editor</span>
        <span style="font-size:12.5px;color:var(--gray)">📍 <?= $robotsFile ?></span>
      </div>
      <div class="card-body">
        <div style="background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.15);border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:var(--text)">
          <i class="fas fa-info-circle" style="color:var(--blue);margin-right:6px"></i>
          This file controls which pages search engine crawlers can access. Changes take effect immediately.
        </div>
        <textarea name="robots_content" class="form-control" rows="18" style="font-family:monospace;font-size:13.5px;background:#0f172a;color:#e2e8f0;border-color:#334155;border-radius:10px;resize:vertical"><?= htmlspecialchars($robotsContent) ?></textarea>
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
          <button type="button" class="btn btn-secondary btn-sm" onclick="appendRobots('User-agent: *\nDisallow: /admin/\n')"><i class="fas fa-plus"></i> Block /admin/</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="appendRobots('Disallow: /api/\n')"><i class="fas fa-plus"></i> Block /api/</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="appendRobots('Allow: /uploads/\n')"><i class="fas fa-plus"></i> Allow /uploads/</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="appendRobots('Sitemap: <?= SITE_URL ?>/sitemap.php\n')"><i class="fas fa-sitemap"></i> Add Sitemap</button>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save robots.txt</button>
      <a href="<?= SITE_URL ?>/robots.txt" target="_blank" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> View Live File</a>
    </div>
  </form>
</div>

<?php elseif ($tab === 'redirects'): ?>
<!-- ═══ REDIRECTS TAB ═══ -->
<div class="card" style="margin-bottom:20px;max-width:700px">
  <div class="card-header"><span><i class="fas fa-directions" style="color:var(--violet);margin-right:8px"></i>Add Redirect Rule</span></div>
  <div class="card-body">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_redirect">
      <div style="display:grid;grid-template-columns:1fr 1fr 120px;gap:12px;align-items:end">
        <div class="form-group" style="margin:0">
          <label>From URL <small>(e.g. /old-page)</small></label>
          <input type="text" class="form-control" name="from_url" placeholder="/old-page" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>To URL <small>(full URL or /path)</small></label>
          <input type="text" class="form-control" name="to_url" placeholder="/new-page or https://..." required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Type</label>
          <select class="form-control" name="type">
            <option value="301">301 Permanent</option>
            <option value="302">302 Temporary</option>
            <option value="307">307 Temp (method)</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:14px"><i class="fas fa-plus"></i> Add Redirect</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span><i class="fas fa-list" style="color:var(--blue);margin-right:8px"></i>Redirect Rules (<?= count($redirects) ?>)</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>From</th><th>To</th><th>Type</th><th>Status</th><th>Hits</th><th class="col-actions">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($redirects)): ?>
        <tr><td colspan="6" style="text-align:center;padding:28px;color:var(--gray)">No redirects configured yet.</td></tr>
        <?php else: foreach ($redirects as $r): ?>
        <tr>
          <td><code style="font-size:13px;background:var(--light);padding:3px 8px;border-radius:5px"><?= e($r['from_url']) ?></code></td>
          <td style="font-size:13px;color:var(--blue);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['to_url']) ?></td>
          <td><span class="badge badge-<?= $r['type']==301?'violet':($r['type']==302?'amber':'cyan') ?>"><?= $r['type'] ?></span></td>
          <td>
            <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="toggle_redirect"><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="badge <?= $r['is_active']?'badge-green':'badge-gray' ?>" style="border:none;cursor:pointer"><?= $r['is_active']?'Active':'Paused' ?></button>
            </form>
          </td>
          <td style="font-size:13.5px;font-weight:700"><?= number_format($r['hits']) ?></td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this redirect?')"><?= csrfField() ?><input type="hidden" name="action" value="delete_redirect"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'schema'): ?>
<!-- ═══ SCHEMA MARKUP TAB ═══ -->
<div style="max-width:800px">
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span><i class="fas fa-code" style="color:var(--emerald);margin-right:8px"></i>Organization Schema</span></div>
    <div class="card-body">
      <p style="font-size:13.5px;color:var(--gray);margin-bottom:16px;line-height:1.7">This JSON-LD schema is automatically injected on every page to help Google understand your organization.</p>
      <?php
      $s = getAllSettings();
      $schema = json_encode([
        '@context'=>'https://schema.org','@type'=>'Organization',
        'name'=>$s['site_name']??'Appsgain Technologies',
        'url'=>SITE_URL,'logo'=>SITE_URL.'/uploads/'.($s['site_logo']??''),
        'contactPoint'=>[['@type'=>'ContactPoint','telephone'=>$s['phone']??'','contactType'=>'customer service']],
        'sameAs'=>array_filter([$s['facebook_url']??'',$s['twitter_url']??'',$s['linkedin_url']??'',$s['instagram_url']??''])
      ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
      ?>
      <pre style="background:#0f172a;color:#e2e8f0;border-radius:10px;padding:16px;font-size:13px;overflow-x:auto"><?= htmlspecialchars($schema) ?></pre>
      <div style="margin-top:14px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);border-radius:10px;padding:12px 16px;font-size:13px;color:#047857">
        <i class="fas fa-check-circle" style="margin-right:6px"></i>
        This schema is auto-generated from your <a href="settings.php" style="color:#059669;font-weight:600">Site Settings</a>. Update business details there.
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span><i class="fas fa-sitemap" style="color:var(--blue);margin-right:8px"></i>XML Sitemap</span></div>
    <div class="card-body">
      <p style="font-size:13.5px;color:var(--gray);margin-bottom:16px;line-height:1.7">Your sitemap is dynamically generated and includes all active pages, services, blog posts, and projects.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="<?= SITE_URL ?>/sitemap.php" target="_blank" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> View Sitemap</a>
        <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="btn btn-secondary"><i class="fab fa-google"></i> Submit to Google</a>
        <a href="https://www.bing.com/webmaster" target="_blank" rel="noopener" class="btn btn-secondary"><i class="fab fa-microsoft"></i> Submit to Bing</a>
      </div>
    </div>
  </div>
</div>
<?php elseif ($tab === 'entity'): ?>
<?php
$S = getAllSettings();
$ent = static fn(string $k, string $d = ''): string => (string)($S[$k] ?? $d);
$socials = [
  'site_facebook' => 'Facebook', 'site_instagram' => 'Instagram', 'site_twitter' => 'X',
  'site_linkedin' => 'LinkedIn', 'site_youtube' => 'YouTube', 'site_threads' => 'Threads',
  'site_gmb' => 'Google Business Profile',
];
$live = 0; foreach ($socials as $k => $_) { if (trim($ent($k)) !== '') $live++; }
?>
<div class="card" style="padding:22px;">
  <h2 style="font-size:15px;font-weight:700;margin:0 0 6px;">Organization &amp; entity signals</h2>
  <p style="font-size:13px;color:var(--muted);margin:0 0 18px;">
    These feed the Organization and LocalBusiness schema that Google reads to build a
    Knowledge Panel. Enter only details you can verify — unsupported claims can cost
    the whole entity its trust.
  </p>

  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_entity">

    <div class="se-g2">
      <div class="form-group">
        <label for="company_legal_name">Registered legal name</label>
        <input type="text" name="company_legal_name" id="company_legal_name" class="form-control"
               value="<?= e($ent('company_legal_name')) ?>" placeholder="Appsgain Technologies Private Limited">
      </div>
      <div class="form-group">
        <label for="company_founding_date">Founding date</label>
        <input type="text" name="company_founding_date" id="company_founding_date" class="form-control"
               value="<?= e($ent('company_founding_date')) ?>" placeholder="2018 or 2018-04-01">
      </div>
    </div>

    <div class="se-g2">
      <div class="form-group">
        <label for="company_founder">Founder</label>
        <input type="text" name="company_founder" id="company_founder" class="form-control"
               value="<?= e($ent('company_founder')) ?>" placeholder="Prashant Kumar">
      </div>
      <div class="form-group">
        <label for="company_employees">Employee count</label>
        <input type="number" name="company_employees" id="company_employees" class="form-control" min="0"
               value="<?= e($ent('company_employees')) ?>" placeholder="12">
        <div class="form-hint">Approximate is fine. Leave blank rather than guess high.</div>
      </div>
    </div>

    <div class="se-g2">
      <div class="form-group">
        <label for="company_cin">CIN / registration number</label>
        <input type="text" name="company_cin" id="company_cin" class="form-control"
               value="<?= e($ent('company_cin')) ?>" placeholder="U72900BR2018PTC0XXXXX">
        <div class="form-hint">Strong verification signal for an Indian private limited company.</div>
      </div>
      <div class="form-group">
        <label for="company_vat_id">GSTIN</label>
        <input type="text" name="company_vat_id" id="company_vat_id" class="form-control"
               value="<?= e($ent('company_vat_id')) ?>" placeholder="10ABCDE1234F1Z5">
      </div>
    </div>

    <div class="se-g2">
      <div class="form-group">
        <label for="site_tagline">Slogan</label>
        <input type="text" name="site_tagline" id="site_tagline" class="form-control"
               value="<?= e($ent('site_tagline')) ?>">
      </div>
      <div class="form-group">
        <label for="company_price_range">Price range</label>
        <input type="text" name="company_price_range" id="company_price_range" class="form-control"
               value="<?= e($ent('company_price_range')) ?>" placeholder="$$">
      </div>
    </div>

    <h3 style="font-size:14px;font-weight:700;margin:24px 0 6px;padding-top:18px;border-top:1px solid var(--line);">
      Third-party profiles
    </h3>
    <p style="font-size:12.5px;color:var(--muted);margin:0 0 14px;">
      Added to <code>sameAs</code>. Independent listings are what let Google
      corroborate the entity — they carry more weight than anything on this site.
      <?= $live ?> social profile<?= $live === 1 ? '' : 's' ?> already connected under Settings&nbsp;→&nbsp;Social.
    </p>
    <div class="se-g2">
      <?php foreach ([
        'company_wikipedia'  => ['Wikipedia / Wikidata', 'https://www.wikidata.org/wiki/Q…'],
        'company_crunchbase' => ['Crunchbase', 'https://www.crunchbase.com/organization/…'],
        'company_clutch'     => ['Clutch', 'https://clutch.co/profile/…'],
        'company_goodfirms'  => ['GoodFirms', 'https://www.goodfirms.co/company/…'],
        'company_glassdoor'  => ['Glassdoor', 'https://www.glassdoor.co.in/Overview/…'],
      ] as $k => [$lbl, $ph]): ?>
      <div class="form-group">
        <label for="<?= e($k) ?>"><?= e($lbl) ?></label>
        <input type="url" name="<?= e($k) ?>" id="<?= e($k) ?>" class="form-control"
               value="<?= e($ent($k)) ?>" placeholder="<?= e($ph) ?>">
      </div>
      <?php endforeach; ?>
    </div>

    <h3 style="font-size:14px;font-weight:700;margin:24px 0 6px;padding-top:18px;border-top:1px solid var(--line);">
      Aggregate rating
    </h3>
    <div class="se-warn">
      <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
      <div>
        <strong>Only publish a rating you can point to.</strong>
        Ratings a business publishes about itself are not eligible for review rich
        results, and invented figures can trigger a spam manual action. The site
        previously emitted <code>5.0 / 150 reviews</code> with nothing behind it —
        that has been removed. All three fields are required together, or nothing
        is published.
      </div>
    </div>
    <div class="se-g3">
      <div class="form-group">
        <label for="review_rating_value">Rating</label>
        <input type="number" step="0.1" min="1" max="5" name="review_rating_value" id="review_rating_value"
               class="form-control" value="<?= e($ent('review_rating_value')) ?>" placeholder="4.8">
      </div>
      <div class="form-group">
        <label for="review_rating_count">Number of reviews</label>
        <input type="number" min="1" name="review_rating_count" id="review_rating_count"
               class="form-control" value="<?= e($ent('review_rating_count')) ?>" placeholder="27">
      </div>
      <div class="form-group">
        <label for="review_rating_source">Source platform</label>
        <input type="text" name="review_rating_source" id="review_rating_source" class="form-control"
               value="<?= e($ent('review_rating_source')) ?>" placeholder="Google Business Profile">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save entity details</button>
  </form>
</div>

<style>
.se-g2{ display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
.se-g3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 18px; }
@media (max-width:860px){ .se-g2,.se-g3{ grid-template-columns:1fr; } }
.se-warn{
  display:flex; gap:12px; align-items:flex-start;
  padding:13px 15px; margin-bottom:16px; border-radius:var(--r-md);
  background:var(--warn-bg); border:1px solid var(--warn-line);
  font-size:13px; line-height:1.6; color:var(--ink-2);
}
.se-warn i{ color:var(--warn); font-size:15px; margin-top:2px; }
.se-warn strong{ color:var(--ink); }
</style>

<?php endif; ?>

<script>
function uc(el, id, max) {
  const n = el.value.length;
  const c = document.getElementById(id);
  c.textContent = n;
  c.style.color = n > max ? '#e11d48' : n > max*.9 ? '#d97706' : 'var(--gray)';
}
document.getElementById('metaTitle')?.addEventListener('input', function() {
  const el = document.getElementById('prevTitle');
  if (el) el.textContent = this.value || '<?= addslashes(htmlspecialchars($selectedPageData[0])) ?>';
});
document.getElementById('metaDesc')?.addEventListener('input', function() {
  const el = document.getElementById('prevDesc');
  if (el) el.textContent = this.value || 'No meta description set.';
});
function appendRobots(text) {
  const ta = document.querySelector('textarea[name="robots_content"]');
  if (ta) ta.value += '\n' + text;
}
</script>
<?php require_once dirname(__DIR__) . '/includes/admin-foot.php'; ?>
