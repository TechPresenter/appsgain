<?php
require_once __DIR__ . '/includes/bootstrap.php';
trackVisitor();

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/blog.php'); exit; }

$blog = dbFetchRow(
    "SELECT b.*, c.name AS cat_name, c.id AS cat_id
     FROM blogs b LEFT JOIN blog_categories c ON b.category_id = c.id
     WHERE b.slug = ? AND b.status = 'published'",
    [$slug]
);
if (!$blog) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

dbExecute("UPDATE blogs SET views = views + 1 WHERE id = ?", [$blog['id']]);

$activePage      = 'blog';
$schemaPageType  = 'blog_post';
$seoIsDetail     = true;
$pageTitle       = !empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title'] . ' – Appsgain Blog';
$pageDescription = !empty($blog['meta_description'])
                 ? $blog['meta_description']
                 : truncate(strip_tags($blog['excerpt'] ?: $blog['content']), 160);
$canonicalUrl    = SITE_URL . '/blog/' . $blog['slug'];
$ogImage         = !empty($blog['featured_image']) ? UPLOADS_URL . '/' . $blog['featured_image'] : '';
/* the renderer reads $pageData['post'] — a mismatched key here meant
   the article emitted only generic WebPage schema, never BlogPosting. */
$schemaData      = ['post' => $blog];

$related = dbFetchAll(
    "SELECT id, title, slug, featured_image, published_at, created_at
     FROM blogs WHERE status='published' AND id != ?
     ORDER BY (category_id = ?) DESC, published_at DESC LIMIT 3",
    [$blog['id'], $blog['cat_id']]
);

$prevPost = dbFetchOne(
    "SELECT title, slug FROM blogs WHERE status='published'
     AND (published_at < ? OR (published_at = ? AND id < ?)) AND id != ?
     ORDER BY published_at DESC, id DESC LIMIT 1",
    [$blog['published_at'], $blog['published_at'], $blog['id'], $blog['id']]
);
$nextPost = dbFetchOne(
    "SELECT title, slug FROM blogs WHERE status='published'
     AND (published_at > ? OR (published_at = ? AND id > ?)) AND id != ?
     ORDER BY published_at ASC, id ASC LIMIT 1",
    [$blog['published_at'], $blog['published_at'], $blog['id'], $blog['id']]
);

$postDate = $blog['published_at'] ?: $blog['created_at'];
$author   = $blog['author_name'] ?: 'Appsgain Team';
$shareUrl = urlencode($canonicalUrl);
$shareTxt = urlencode($blog['title']);
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

<div class="bgx-prog" id="bgxProg" role="progressbar" aria-label="Reading progress"
     aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>

<?php require __DIR__ . '/includes/layout-header.php'; ?>

<main id="main" class="bgx">

  <!-- ══ Header ══ -->
  <header class="bgx-art-head">
    <div class="bgx-wrap">
      <nav class="bgx-crumb" aria-label="Breadcrumb">
        <a href="<?= SITE_URL ?>/">Home</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <a href="<?= SITE_URL ?>/blog.php">Blog</a>
        <?php if ($blog['cat_name']): ?>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <a href="<?= SITE_URL ?>/blog.php?cat=<?= (int)$blog['cat_id'] ?>"><?= e($blog['cat_name']) ?></a>
        <?php endif; ?>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <span aria-current="page"><?= e(truncate($blog['title'], 42)) ?></span>
      </nav>

      <h1 class="bgx-art-title"><?= e($blog['title']) ?></h1>

      <div class="bgx-art-meta">
        <span class="bgx-author">
          <span class="bgx-author-av" aria-hidden="true"><?= strtoupper(substr($author, 0, 1)) ?></span>
          <?= e($author) ?>
        </span>
        <span class="bgx-dot">&middot;</span>
        <time datetime="<?= e(date('Y-m-d', strtotime($postDate))) ?>"><?= date('F j, Y', strtotime($postDate)) ?></time>
        <span class="bgx-dot">&middot;</span>
        <span><?= readingTime($blog['content']) ?> min read</span>
        <span class="bgx-dot">&middot;</span>
        <span><?= number_format((int)$blog['views']) ?> views</span>
      </div>

      <?php if (!empty($blog['featured_image'])): ?>
      <figure class="bgx-hero-img">
        <img src="<?= UPLOADS_URL ?>/<?= e($blog['featured_image']) ?>"
             alt="<?= e($blog['title']) ?>" loading="eager" decoding="async">
      </figure>
      <?php endif; ?>
    </div>
  </header>

  <!-- ══ Body ══ -->
  <div class="bgx-wrap">
    <div class="bgx-art">

      <article>
        <?php /* Listen, reading mode and text size — see includes/blog-reader.php */ ?>
        <?php require __DIR__ . '/includes/blog-reader.php'; ?>

        <div class="bgx-prose">
          <?php if (!empty($blog['excerpt'])): ?>
          <p class="bgx-lead-para"><?= e($blog['excerpt']) ?></p>
          <?php endif; ?>
          <div id="articleBody"><?= $blog['content'] /* trusted admin HTML */ ?></div>
        </div>

        <?php if ($prevPost || $nextPost): ?>
        <nav class="bgx-pn" aria-label="More articles">
          <?php if ($prevPost): ?>
          <a href="<?= SITE_URL ?>/blog/<?= e($prevPost['slug']) ?>">
            <span class="lbl"><i class="fas fa-arrow-left" aria-hidden="true"></i> Previous</span>
            <span class="ttl"><?= e($prevPost['title']) ?></span>
          </a>
          <?php else: ?><span></span><?php endif; ?>
          <?php if ($nextPost): ?>
          <a class="next" href="<?= SITE_URL ?>/blog/<?= e($nextPost['slug']) ?>">
            <span class="lbl">Next <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            <span class="ttl"><?= e($nextPost['title']) ?></span>
          </a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>
      </article>

      <!-- ══ Sidebar ══ -->
      <aside class="bgx-aside">
        <div class="bgx-widget">
          <h4><i class="fas fa-list-ul" aria-hidden="true"></i> On this page</h4>
          <ul class="bgx-toc" id="bgxToc"></ul>
          <p class="bgx-toc-empty" id="bgxTocEmpty" hidden>This article has no sections.</p>
        </div>

        <div class="bgx-widget">
          <h4><i class="fas fa-share-nodes" aria-hidden="true"></i> Share</h4>
          <div class="bgx-share">
            <a href="https://www.linkedin.com/shareArticle?url=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTxt ?>" target="_blank" rel="noopener" aria-label="Share on X"><i class="fab fa-x-twitter" aria-hidden="true"></i></a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
            <a href="https://wa.me/?text=<?= $shareTxt ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
            <button type="button" id="bgxCopy" aria-label="Copy link"><i class="fas fa-link" aria-hidden="true"></i></button>
          </div>
        </div>

        <?php if ($related): ?>
        <div class="bgx-widget">
          <h4><i class="fas fa-newspaper" aria-hidden="true"></i> Keep reading</h4>
          <div class="bgx-mini">
            <?php foreach ($related as $r):
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
                <span class="bgx-mini-d"><?= date('M j, Y', strtotime($rd)) ?></span>
              </span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="bgx-widget" style="text-align:center;">
          <h4 style="justify-content:center;"><i class="fas fa-comments" aria-hidden="true"></i> Have a project?</h4>
          <p style="font-family:'Inter',sans-serif;font-size:13.5px;line-height:1.65;color:var(--b-body);margin:0 0 14px;">
            Tell us what you are building and we will come back within a day.
          </p>
          <a href="<?= SITE_URL ?>/contact.php#enquiry" class="bgx-btn bgx-btn-primary" style="width:100%;">Start a conversation</a>
        </div>
      </aside>

    </div>
  </div>

  <?php if ($related): ?>
  <!-- ══ Related ══ -->
  <section class="bgx-related">
    <div class="bgx-wrap">
      <h2>Related articles</h2>
      <div class="bgx-grid">
        <?php foreach ($related as $r):
          $rd = $r['published_at'] ?: $r['created_at']; ?>
        <article class="bgx-card">
          <a class="bgx-card-media" href="<?= SITE_URL ?>/blog/<?= e($r['slug']) ?>" tabindex="-1" aria-hidden="true">
            <?php if (!empty($r['featured_image'])): ?>
              <img src="<?= UPLOADS_URL ?>/<?= e($r['featured_image']) ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="bgx-card-fallback"><?= strtoupper(substr($r['title'], 0, 1)) ?></span>
            <?php endif; ?>
          </a>
          <div class="bgx-card-body">
            <div class="bgx-card-meta">
              <time datetime="<?= e(date('Y-m-d', strtotime($rd))) ?>"><?= date('M j, Y', strtotime($rd)) ?></time>
            </div>
            <h3><a href="<?= SITE_URL ?>/blog/<?= e($r['slug']) ?>"><?= e($r['title']) ?></a></h3>
            <a class="bgx-more" href="<?= SITE_URL ?>/blog/<?= e($r['slug']) ?>">
              Read more <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>

<script>
(function () {
  'use strict';

  /* ── Reading progress ── */
  var prog = document.getElementById('bgxProg');
  var art  = document.getElementById('articleBody');
  if (prog && art) {
    var tick = function () {
      var r = art.getBoundingClientRect();
      var total = r.height - window.innerHeight;
      var pct = total <= 0 ? (r.bottom <= window.innerHeight ? 100 : 0)
                           : Math.min(100, Math.max(0, (-r.top / total) * 100));
      prog.style.width = pct + '%';
      prog.setAttribute('aria-valuenow', Math.round(pct));
    };
    window.addEventListener('scroll', tick, { passive: true });
    window.addEventListener('resize', tick);
    tick();
  }

  /* ── Table of contents, built from the article's own headings ── */
  var toc = document.getElementById('bgxToc');
  if (toc && art) {
    var heads = art.querySelectorAll('h2, h3');
    if (!heads.length) {
      toc.hidden = true;
      document.getElementById('bgxTocEmpty').hidden = false;
    } else {
      heads.forEach(function (h, i) {
        if (!h.id) {
          h.id = 'sec-' + (h.textContent.trim().toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').slice(0, 40) || i);
        }
        var li = document.createElement('li');
        li.className = 'lvl-' + h.tagName.charAt(1);
        var a = document.createElement('a');
        a.href = '#' + h.id;
        a.textContent = h.textContent.trim();
        li.appendChild(a);
        toc.appendChild(li);
      });

      /* Scroll-spy: the last heading above the fold is the current one */
      var links = toc.querySelectorAll('a');
      var spy = function () {
        var cur = null;
        heads.forEach(function (h) {
          if (h.getBoundingClientRect().top <= 110) cur = h.id;
        });
        links.forEach(function (a) {
          a.classList.toggle('is-on', a.getAttribute('href') === '#' + cur);
        });
      };
      window.addEventListener('scroll', spy, { passive: true });
      spy();
    }
  }

  /* ── Copy link ── */
  var copy = document.getElementById('bgxCopy');
  if (copy) {
    copy.addEventListener('click', function () {
      var done = function () {
        copy.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function () { copy.innerHTML = '<i class="fas fa-link"></i>'; }, 1600);
      };
      var url = <?= json_encode($canonicalUrl) ?>;
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done, done);
      } else {
        /* http:// on a LAN has no clipboard API */
        var t = document.createElement('textarea');
        t.value = url; t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t); done();
      }
    });
  }
})();
</script>
</body>
</html>
