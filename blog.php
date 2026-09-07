<?php
require_once __DIR__ . '/includes/bootstrap.php';
trackVisitor();

$activePage      = 'blog';
$pageTitle       = getSetting('meta_title_blog', 'Blog – Appsgain | Tech Insights, Dev Tips & Digital Trends');
$pageDescription = getSetting('meta_desc_blog', 'Stay ahead with Appsgain\'s blog — practical articles on web development, mobile apps, AI, cloud, UI/UX, and digital marketing.');
$canonicalUrl    = SITE_URL . '/blog.php';

$currentPage = max(1, (int)($_GET['page'] ?? 1));
$catId       = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search      = sanitizeInput($_GET['q'] ?? '');
$perPage     = ITEMS_PER_PAGE;
$isFiltered  = ($catId > 0 || $search !== '');

/* ── Posts ── */
$where  = "b.status = 'published'";
$params = [];
if ($catId)  { $where .= " AND b.category_id = ?"; $params[] = $catId; }
if ($search) { $where .= " AND (b.title LIKE ? OR b.excerpt LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total       = (int) dbFetchValue("SELECT COUNT(*) FROM blogs b WHERE $where", $params);
$totalPages  = max(1, (int)ceil($total / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset      = ($currentPage - 1) * $perPage;

$blogs = dbFetchAll(
    "SELECT b.*, c.name AS cat_name, c.id AS cat_id, c.color AS cat_color
     FROM blogs b LEFT JOIN blog_categories c ON b.category_id = c.id
     WHERE $where ORDER BY b.published_at DESC, b.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

/* ── Topics: every active category, with its real post count ── */
$categories = dbFetchAll(
    "SELECT c.*, COUNT(b.id) AS post_count
     FROM blog_categories c
     LEFT JOIN blogs b ON b.category_id = c.id AND b.status = 'published'
     WHERE c.is_active = 1
     GROUP BY c.id ORDER BY post_count DESC, c.sort_order ASC, c.name ASC"
);

/* ── Site-wide figures for the hero strip ── */
$allPosts  = (int) dbFetchValue("SELECT COUNT(*) FROM blogs WHERE status='published'");
$allViews  = (int) dbFetchValue("SELECT COALESCE(SUM(views),0) FROM blogs WHERE status='published'");
$liveCats  = 0;
foreach ($categories as $c) { if ((int)$c['post_count'] > 0) $liveCats++; }

/* ── Most read ── */
$popular = dbFetchAll(
    "SELECT id, title, slug, featured_image, views, published_at, created_at
     FROM blogs WHERE status='published' ORDER BY views DESC LIMIT 4"
);

/* ── Tags, from the posts themselves ── */
$tagRows = dbFetchAll("SELECT tags FROM blogs WHERE status='published' AND tags IS NOT NULL AND tags <> ''");
$tags = [];
foreach ($tagRows as $r) {
    $d = json_decode((string)$r['tags'], true);
    if (!is_array($d)) { $d = array_map('trim', explode(',', (string)$r['tags'])); }
    foreach ($d as $t) {
        $t = trim((string)$t);
        if ($t !== '') { $tags[$t] = ($tags[$t] ?? 0) + 1; }
    }
}
arsort($tags);
$tags = array_slice($tags, 0, 12, true);

/* The lead card only makes sense unfiltered — promoting a post inside a
   filtered result would misrepresent the filter. */
$featured = null;
if ($currentPage === 1 && !$isFiltered && $blogs) {
    $featured = array_shift($blogs);
}

$catName = '';
foreach ($categories as $c) { if ((int)$c['id'] === $catId) { $catName = $c['name']; break; } }

/** Build a listing URL that keeps the current filters. */
$pageUrl = static function (int $p) use ($catId, $search): string {
    $q = array_filter([
        'cat'  => $catId ?: null,
        'q'    => $search ?: null,
        'page' => $p > 1 ? $p : null,
    ]);
    return SITE_URL . '/blog.php' . ($q ? '?' . http_build_query($q) : '');
};
?>
<?php $pageStyles = ['blog.css']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . '/includes/meta.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css" />
</head>
<body>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main" class="bgx">

  <!-- ══ Hero ══ -->
  <section class="bgx-hero">
    <div class="bgx-wrap">
      <span class="bgx-eyebrow"><i class="fas fa-feather" aria-hidden="true"></i> Insights</span>
      <h1 class="bgx-h1">Notes from the <span class="bgx-grad">engineering team</span></h1>
      <p class="bgx-lede">
        Practical writing on building software — architecture decisions, delivery lessons
        and the occasional strong opinion. No filler.
      </p>

      <div class="bgx-tools">
        <form class="bgx-search" method="GET" role="search" action="<?= SITE_URL ?>/blog.php">
          <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
          <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
          <label class="sr-only" for="bgxQ">Search articles</label>
          <input type="search" id="bgxQ" name="q" value="<?= e($search) ?>"
                 placeholder="Search articles…" autocomplete="off">
          <button type="submit" aria-label="Search"><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </form>

        <nav class="bgx-pills" aria-label="Filter by topic">
          <a href="<?= SITE_URL ?>/blog.php<?= $search ? '?q=' . urlencode($search) : '' ?>"
             class="bgx-pill <?= !$catId ? 'is-on' : '' ?>">All <span><?= $allPosts ?></span></a>
          <?php foreach ($categories as $c): if ((int)$c['post_count'] === 0) continue; ?>
          <a href="<?= SITE_URL ?>/blog.php?cat=<?= (int)$c['id'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
             class="bgx-pill <?= $catId === (int)$c['id'] ? 'is-on' : '' ?>">
            <?= e($c['name']) ?> <span><?= (int)$c['post_count'] ?></span>
          </a>
          <?php endforeach; ?>
        </nav>
      </div>

      <div class="bgx-stats">
        <div class="bgx-stat"><b><?= $allPosts ?></b><span>Article<?= $allPosts === 1 ? '' : 's' ?></span></div>
        <div class="bgx-stat"><b><?= count($categories) ?></b><span>Topics</span></div>
        <div class="bgx-stat"><b><?= number_format($allViews) ?></b><span>Reads</span></div>
      </div>
    </div>
  </section>

  <section class="bgx-section">
    <div class="bgx-wrap">

      <?php if ($isFiltered): ?>
      <p style="font-family:var(--font-body),sans-serif;font-size:13.5px;color:var(--b-mute);margin:0 0 18px;">
        <?= $total ?> article<?= $total === 1 ? '' : 's' ?>
        <?php if ($search): ?> matching &ldquo;<strong style="color:var(--b-ink)"><?= e($search) ?></strong>&rdquo;<?php endif; ?>
        <?php if ($catName): ?> in <strong style="color:var(--b-ink)"><?= e($catName) ?></strong><?php endif; ?>
        &nbsp;<a href="<?= SITE_URL ?>/blog.php" style="color:var(--b-brand);font-weight:600;text-decoration:none;">Clear</a>
      </p>
      <?php endif; ?>

      <?php if ($featured): ?>
      <!-- ══ Lead article ══ -->
      <article class="bgx-feat" style="margin-bottom:clamp(22px,3vw,34px);">
        <div>
          <div class="bgx-card-meta">
            <?php if ($featured['cat_name']): ?>
            <a class="bgx-cat" href="<?= SITE_URL ?>/blog.php?cat=<?= (int)$featured['cat_id'] ?>"><?= e($featured['cat_name']) ?></a>
            <?php endif; ?>
            <time datetime="<?= e(date('Y-m-d', strtotime($featured['published_at'] ?: $featured['created_at']))) ?>">
              <?= date('M j, Y', strtotime($featured['published_at'] ?: $featured['created_at'])) ?>
            </time>
            <span class="bgx-dot">&middot;</span>
            <span><?= readingTime($featured['content']) ?> min read</span>
          </div>
          <h2><a href="<?= SITE_URL ?>/blog/<?= e($featured['slug']) ?>"><?= e($featured['title']) ?></a></h2>
          <?php if ($featured['excerpt']): ?>
          <p><?= e(truncate($featured['excerpt'], 200)) ?></p>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/blog/<?= e($featured['slug']) ?>" class="bgx-btn bgx-btn-primary">
            Read article <i class="fas fa-arrow-right" aria-hidden="true" style="font-size:11px"></i>
          </a>
        </div>
        <div class="bgx-feat-media">
          <span class="bgx-feat-tag">Latest</span>
          <?php if (!empty($featured['featured_image'])): ?>
            <img src="<?= UPLOADS_URL ?>/<?= e($featured['featured_image']) ?>"
                 alt="<?= e($featured['title']) ?>" loading="eager" decoding="async">
          <?php else: ?>
            <span class="bgx-card-fallback" aria-hidden="true"><?= strtoupper(substr($featured['title'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
      </article>
      <?php endif; ?>

      <!-- ══ Grid + rail ══ -->
      <?php /* No grid posts: collapse the split so the rail does not
               sit beside an empty column. */ ?>
      <div class="bgx-main<?= $blogs ? '' : ' is-sparse' ?>">

        <div>
          <?php if ($blogs): ?>
            <div class="bgx-sec-head">
              <div>
                <h2><?= $isFiltered ? 'Results' : 'More articles' ?></h2>
                <p>Sorted by newest first.</p>
              </div>
            </div>
            <div class="bgx-grid">
              <?php foreach ($blogs as $b):
                $date = $b['published_at'] ?: $b['created_at']; ?>
              <article class="bgx-card">
                <a class="bgx-card-media" href="<?= SITE_URL ?>/blog/<?= e($b['slug']) ?>" tabindex="-1" aria-hidden="true">
                  <?php if (!empty($b['featured_image'])): ?>
                    <img src="<?= UPLOADS_URL ?>/<?= e($b['featured_image']) ?>" alt="" loading="lazy" decoding="async">
                  <?php else: ?>
                    <span class="bgx-card-fallback"><?= strtoupper(substr($b['title'], 0, 1)) ?></span>
                  <?php endif; ?>
                </a>
                <div class="bgx-card-body">
                  <div class="bgx-card-meta">
                    <?php if ($b['cat_name']): ?>
                    <a class="bgx-cat" href="<?= SITE_URL ?>/blog.php?cat=<?= (int)$b['cat_id'] ?>"><?= e($b['cat_name']) ?></a>
                    <?php endif; ?>
                    <time datetime="<?= e(date('Y-m-d', strtotime($date))) ?>"><?= date('M j, Y', strtotime($date)) ?></time>
                    <span class="bgx-dot">&middot;</span>
                    <span><?= readingTime($b['content']) ?> min</span>
                  </div>
                  <h3><a href="<?= SITE_URL ?>/blog/<?= e($b['slug']) ?>"><?= e($b['title']) ?></a></h3>
                  <?php if ($b['excerpt']): ?>
                  <p><?= e(truncate($b['excerpt'], 118)) ?></p>
                  <?php endif; ?>
                  <a class="bgx-more" href="<?= SITE_URL ?>/blog/<?= e($b['slug']) ?>">
                    Read more <i class="fas fa-arrow-right" aria-hidden="true"></i>
                  </a>
                </div>
              </article>
              <?php endforeach; ?>
            </div>

          <?php elseif ($isFiltered): ?>
            <div class="bgx-empty">
              <span class="bgx-empty-ico" aria-hidden="true"><i class="fas fa-magnifying-glass"></i></span>
              <h2>No articles match that</h2>
              <p>Try a different search term, or browse everything we have written.</p>
              <a href="<?= SITE_URL ?>/blog.php" class="bgx-btn bgx-btn-primary">Clear filters</a>
            </div>

          <?php elseif ($featured): ?>
            <?php /* One post total: say so plainly rather than leaving a hole */ ?>
            <div class="bgx-soon">
              <i class="fas fa-pen-nib" aria-hidden="true"></i>
              <div>
                <h3>More on the way</h3>
                <p>We publish when we have something genuinely useful to say. Subscribe and we will send the next one.</p>
              </div>
            </div>

          <?php else: ?>
            <div class="bgx-empty">
              <span class="bgx-empty-ico" aria-hidden="true"><i class="fas fa-file-lines"></i></span>
              <h2>Nothing published yet</h2>
              <p>We are working on the first articles. Check back shortly.</p>
              <a href="<?= SITE_URL ?>/services.php" class="bgx-btn bgx-btn-primary">Explore our services</a>
            </div>
          <?php endif; ?>

          <?php if ($totalPages > 1): ?>
          <nav class="bgx-pager" aria-label="Pagination">
            <a class="bgx-page" href="<?= e($pageUrl(max(1, $currentPage - 1))) ?>"
               <?= $currentPage <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?> aria-label="Previous page">
              <i class="fas fa-chevron-left" aria-hidden="true" style="font-size:11px"></i>
            </a>
            <?php
            $from = max(1, $currentPage - 2);
            $to   = min($totalPages, $currentPage + 2);
            if ($from > 1): ?>
              <a class="bgx-page" href="<?= e($pageUrl(1)) ?>">1</a>
              <?php if ($from > 2): ?><span class="bgx-page" aria-hidden="true" style="border:0;background:none">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $from; $i <= $to; $i++): ?>
              <a class="bgx-page <?= $i === $currentPage ? 'is-on' : '' ?>" href="<?= e($pageUrl($i)) ?>"
                 <?= $i === $currentPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($to < $totalPages): ?>
              <?php if ($to < $totalPages - 1): ?><span class="bgx-page" aria-hidden="true" style="border:0;background:none">…</span><?php endif; ?>
              <a class="bgx-page" href="<?= e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            <a class="bgx-page" href="<?= e($pageUrl(min($totalPages, $currentPage + 1))) ?>"
               <?= $currentPage >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?> aria-label="Next page">
              <i class="fas fa-chevron-right" aria-hidden="true" style="font-size:11px"></i>
            </a>
          </nav>
          <?php endif; ?>
        </div>

        <!-- ══ Rail ══ -->
        <aside class="bgx-side">

          <div class="bgx-widget">
            <h4><i class="fas fa-layer-group" aria-hidden="true"></i> Topics</h4>
            <ul class="bgx-topics">
              <?php foreach ($categories as $c):
                $n = (int)$c['post_count'];
                $col = e($c['color'] ?: '#6A00FF'); ?>
                <?php if ($n > 0): ?>
                <li>
                  <a href="<?= SITE_URL ?>/blog.php?cat=<?= (int)$c['id'] ?>"
                     class="<?= $catId === (int)$c['id'] ? 'is-on' : '' ?>" style="--tc:<?= $col ?>">
                    <span class="bgx-topic-dot" aria-hidden="true"></span>
                    <span class="nm"><?= e($c['name']) ?></span>
                    <span class="ct"><?= $n ?></span>
                  </a>
                </li>
                <?php else: ?>
                <li>
                  <span class="dead" style="--tc:<?= $col ?>">
                    <span class="bgx-topic-dot" aria-hidden="true"></span>
                    <span class="nm"><?= e($c['name']) ?></span>
                    <span class="ct">Soon</span>
                  </span>
                </li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>

          <?php if ($popular): ?>
          <div class="bgx-widget">
            <h4><i class="fas fa-fire" aria-hidden="true"></i> Most read</h4>
            <div class="bgx-mini">
              <?php foreach ($popular as $r):
                $rd = $r['published_at'] ?: $r['created_at']; ?>
              <a href="<?= SITE_URL ?>/blog/<?= e($r['slug']) ?>">
                <span class="bgx-mini-img">
                  <?php if (!empty($r['featured_image'])): ?>
                    <img src="<?= UPLOADS_URL ?>/<?= e($r['featured_image']) ?>" alt="" loading="lazy">
                  <?php else: ?>
                    <span class="bgx-mini-fb" aria-hidden="true"><?= strtoupper(substr($r['title'], 0, 1)) ?></span>
                  <?php endif; ?>
                </span>
                <span>
                  <span class="bgx-mini-t"><?= e($r['title']) ?></span>
                  <span class="bgx-mini-d"><?= number_format((int)$r['views']) ?> reads &middot; <?= date('M j, Y', strtotime($rd)) ?></span>
                </span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($tags): ?>
          <div class="bgx-widget">
            <h4><i class="fas fa-hashtag" aria-hidden="true"></i> Tags</h4>
            <div class="bgx-tags">
              <?php foreach (array_keys($tags) as $t): ?>
              <a class="bgx-tag" href="<?= SITE_URL ?>/blog.php?q=<?= urlencode($t) ?>"><?= e($t) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="bgx-nl">
            <span class="bgx-nl-ico" aria-hidden="true"><i class="fas fa-envelope-open-text"></i></span>
            <h4>Get the next one</h4>
            <p>Occasional notes from the team. No spam, unsubscribe in one click.</p>
            <form id="bgxNlForm">
              <label class="sr-only" for="bgxNlEmail">Email address</label>
              <input type="email" id="bgxNlEmail" name="email" placeholder="you@company.com" required autocomplete="email">
              <button type="submit" id="bgxNlBtn">Subscribe</button>
            </form>
            <p class="bgx-nl-msg" id="bgxNlMsg" role="status" aria-live="polite"></p>
          </div>

        </aside>
      </div>
    </div>
  </section>

  <!-- ══ Topics ══ -->
  <?php if ($categories): ?>
  <section class="bgx-topics-band">
    <div class="bgx-wrap">
      <div class="bgx-sec-head">
        <div>
          <h2>Browse by topic</h2>
          <p>What we write about, and what is coming next.</p>
        </div>
      </div>
      <div class="bgx-tgrid">
        <?php foreach ($categories as $c):
          $n = (int)$c['post_count'];
          $col = e($c['color'] ?: '#6A00FF');
          $tag = $n > 0 ? 'a' : 'div'; ?>
        <<?= $tag ?> class="bgx-tcard <?= $n ? '' : 'is-empty' ?>" style="--tc:<?= $col ?>"
          <?= $n ? 'href="' . SITE_URL . '/blog.php?cat=' . (int)$c['id'] . '"' : '' ?>>
          <h3>
            <span class="bgx-topic-dot" aria-hidden="true"></span>
            <?= e($c['name']) ?>
          </h3>
          <?php if (!empty($c['description'])): ?>
          <p><?= e($c['description']) ?></p>
          <?php endif; ?>
          <span class="n"><?= $n ? $n . ' article' . ($n === 1 ? '' : 's') : 'Coming soon' ?></span>
        </<?= $tag ?>>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══ CTA ══ -->
  <div class="bgx-wrap">
    <section class="bgx-cta">
      <div>
        <h2>Have a project rather than a question?</h2>
        <p>
          Reading is one thing — building is another. Tell us what you are trying to make
          and we will come back within a day with an honest view on fit.
        </p>
      </div>
      <div class="bgx-cta-actions">
        <a href="<?= SITE_URL ?>/contact.php#enquiry" class="bgx-btn bgx-btn-primary">Start a conversation</a>
        <a href="<?= SITE_URL ?>/services.php" class="bgx-btn bgx-btn-ghost">See our services</a>
      </div>
    </section>
  </div>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
/* Rail newsletter — posts to the same endpoint as the footer form. */
(function () {
  var f = document.getElementById('bgxNlForm');
  if (!f) return;
  var btn = document.getElementById('bgxNlBtn');
  var msg = document.getElementById('bgxNlMsg');

  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('bgxNlEmail').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      msg.className = 'bgx-nl-msg is-err';
      msg.textContent = 'Please enter a valid email address.';
      return;
    }
    btn.disabled = true;
    var original = btn.textContent;
    btn.textContent = 'Subscribing…';

    var body = new URLSearchParams();
    body.append('email', email);
    body.append('name', '');
    body.append('<?= CSRF_TOKEN_NAME ?>', '<?= e(csrfToken()) ?>');

    fetch('<?= SITE_URL ?>/api/newsletter.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      btn.disabled = false;
      btn.textContent = original;
      if (d.ok || d.success) {
        msg.className = 'bgx-nl-msg is-ok';
        msg.textContent = d.message || 'Thanks — you are on the list.';
        f.reset();
      } else {
        msg.className = 'bgx-nl-msg is-err';
        msg.textContent = d.error || d.message || 'Something went wrong.';
      }
    })
    .catch(function () {
      btn.disabled = false;
      btn.textContent = original;
      msg.className = 'bgx-nl-msg is-err';
      msg.textContent = 'Network error. Please try again.';
    });
  });
})();
</script>
</body>
</html>
