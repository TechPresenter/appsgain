<?php
/**
 * Migration — founder bio page content.
 *
 * Moves /our-founder.php off hardcoded arrays and onto two tables:
 *
 *   founder_sections — the page blocks (prose, chips, path, cards,
 *                      list, quote, faq, gallery), each with its own
 *                      layout, heading, body and repeatable items
 *   founder_gallery  — photographs, each with caption and alt text
 *
 * Seeded from the copy currently in the template, so switching the page
 * over loses nothing and the admin opens on the real content.
 *
 * Safe to run repeatedly.
 */
$cli = (PHP_SAPI === 'cli');
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!$cli) { Auth::requireAdmin(); header('Content-Type: text/plain; charset=utf-8'); }
$log = static function (string $m): void { echo $m . "\n"; };

/* ── 1 · Tables ── */
db()->exec("
CREATE TABLE IF NOT EXISTS founder_sections (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key  VARCHAR(50)  NOT NULL,
  layout       VARCHAR(20)  NOT NULL DEFAULT 'prose',
  heading      VARCHAR(200) NOT NULL DEFAULT '',
  subheading   VARCHAR(300) NOT NULL DEFAULT '',
  body         MEDIUMTEXT   NULL COMMENT 'trusted admin HTML',
  items        MEDIUMTEXT   NULL COMMENT 'JSON: [{title,icon,text}]',
  image        VARCHAR(300) NOT NULL DEFAULT '',
  sort_order   INT          NOT NULL DEFAULT 0,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sort (sort_order), INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
db()->exec("
CREATE TABLE IF NOT EXISTS founder_gallery (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image        VARCHAR(300) NOT NULL DEFAULT '',
  caption      VARCHAR(300) NOT NULL DEFAULT '',
  alt_text     VARCHAR(300) NOT NULL DEFAULT '',
  sort_order   INT          NOT NULL DEFAULT 0,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log('tables founder_sections + founder_gallery ready');

/* ── 2 · Seed sections ── */
if ((int) dbFetchValue("SELECT COUNT(*) FROM founder_sections") > 0) {
    $log('sections already seeded — skipped');
} else {
    $J = fn(array $a) => json_encode($a, JSON_UNESCAPED_UNICODE);

    $sections = [
      ['about', 'prose', 'About {name}', '', 10,
       "<p>{name} is an entrepreneur, technology professional, digital growth strategist and founder with a strong interest in building technology-driven businesses.</p>"
     . "<p>His professional journey combines software development, website development, mobile applications, digital marketing, search engine optimization, lead generation, performance marketing, AI technologies and business growth.</p>"
     . "<p>Rather than viewing technology as an isolated technical function, he believes software should <strong>solve real business problems</strong>, improve operational efficiency, create better customer experiences and contribute to measurable growth. That philosophy became one of the foundations behind Appsgain Technologies.</p>"
     . "<p>Since founding the company in {since}, he has worked at the intersection of technology, digital marketing, software development, business growth and digital transformation — combining technical understanding with a focus on business outcomes, helping organisations turn ideas into practical digital products and scalable online solutions.</p>", null],

      ['focus', 'chips', 'A founder focused on technology and growth', 'Areas of professional interest', 20,
       "<p>His work focuses on helping businesses move from ideas and traditional processes toward modern, scalable digital systems. Under his leadership, Appsgain Technologies has grown from a focused technology startup into a full-service digital solutions company serving startups, SMEs, educational organisations, enterprises and businesses across multiple markets.</p>",
       $J([
         ['title'=>'Custom Software Development'], ['title'=>'Website Development'], ['title'=>'Mobile App Development'],
         ['title'=>'SaaS Product Development'], ['title'=>'E-commerce Development'], ['title'=>'ERP & CRM Development'],
         ['title'=>'AI & Generative AI Solutions'], ['title'=>'Digital Transformation'], ['title'=>'UI/UX Design'],
         ['title'=>'Search Engine Optimization'], ['title'=>'Performance Marketing'], ['title'=>'Lead Generation'],
         ['title'=>'Google & Meta Advertising'], ['title'=>'Cloud Solutions'], ['title'=>'Business Automation'],
         ['title'=>'Digital Product Strategy'], ['title'=>'Online Brand Growth'],
       ])],

      ['story', 'path', 'The story behind Appsgain Technologies', '', 30,
       "<p>{name} founded Appsgain Technologies in {since}, with the objective of creating technology solutions that help businesses establish a stronger digital presence and use software as a growth engine.</p>"
     . "<p>The company started with a focused approach toward website and mobile development, then expanded into broader digital transformation and technology services — working with startups, SMEs, educational institutions, NGOs and enterprises across India and international markets.</p>",
       $J([
         ['title'=>'Web Development'], ['title'=>'Mobile Apps'], ['title'=>'E-commerce'],
         ['title'=>'Enterprise Software'], ['title'=>'ERP & CRM'], ['title'=>'SaaS'],
         ['title'=>'AI Solutions'], ['title'=>'Cloud & DevOps'], ['title'=>'Digital Marketing'],
       ])],

      ['philosophy', 'prose', 'Leadership philosophy', '', 40,
       "<h3>Technology should solve business problems</h3>"
     . "<p>Successful software development is not simply about writing code. A good digital product needs to understand business goals, user needs, technology, design, marketing and scalability together — producing products that are not only technically functional but useful, accessible, scalable and commercially viable.</p>"
     . "<h3>Build with purpose</h3><p>Every product starts with a problem. The approach is to first understand the business requirement, target audience, operational challenges and long-term objectives before deciding on the technology or development approach.</p>"
     . "<h3>Think long term</h3><p>Digital products should be built with future growth in mind. Scalability, security, performance, maintainability and integration are treated as core parts of a modern software strategy.</p>"
     . "<h3>Technology plus growth</h3><p>Building a product is only one part of digital transformation. A successful product also needs visibility, users, customer acquisition and continuous optimisation — which is why his interests extend beyond development into SEO, digital marketing, lead generation and growth strategy.</p>", null],

      ['expertise', 'prose', 'Areas of expertise', '', 50,
       "<h3>Software &amp; web development</h3><p>Professional experience built around digital products and software solutions — websites, web applications, e-commerce platforms, enterprise systems and customised business applications.</p>"
     . "<h3>Mobile app development</h3><p>Mobile technology is a major area of Appsgain's work, with solutions for businesses building Android, iOS and cross-platform applications.</p>"
     . "<h3>Digital marketing &amp; growth</h3><p>An emphasis on digital marketing, lead generation, performance marketing, SEO, advertising and brand growth — connecting development with acquisition, so businesses create digital ecosystems rather than isolated websites or applications.</p>"
     . "<h3>Artificial intelligence</h3><p>AI and Generative AI are increasingly central. Recent activity includes AI-related projects and experimentation with AI-powered products and developer tools, alongside an Introduction to Generative AI certification from Google.</p>"
     . "<h3>Product development</h3><p>Turning ideas into practical digital products — from initial concept and UX planning through development, launch, optimisation and growth.</p>", null],

      ['products', 'prose', 'Building products, not just projects', '', 60,
       "<p>One core belief is that a software company should think beyond project delivery. <strong>A project has a deadline. A product has a lifecycle.</strong></p>"
     . "<p>A successful technology partner therefore needs to understand what happens after launch — performance, customer acquisition, analytics, security, maintenance, automation and future product development. This product-oriented mindset shapes how Appsgain approaches software and digital transformation work.</p>"
     . "<p>He continues to explore emerging technologies, particularly artificial intelligence, generative AI, automation and AI-powered productivity tools — reflecting a broader interest in applying emerging technology to practical, real-world problems.</p>", null],

      ['education', 'list', 'Education & continuous learning', '', 70,
       "<p>Education at {education}, alongside continuing professional learning across technology and digital disciplines. Certifications and learning experiences include:</p>",
       $J([
         ['title'=>'Introduction to Generative AI — Google'],
         ['title'=>'CSS (Basic) — HackerRank'],
         ['title'=>'Microsoft AI Classroom Series'],
         ['title'=>'Kushal Yuva Program (KYP)'],
       ])],

      ['milestones', 'cards', 'Achievements & recognition', '', 80, null,
       $J([
         ['title'=>'Founder of Appsgain Technologies','icon'=>'fa-flag','text'=>'Founded the company in {since} and continues to lead it.'],
         ['title'=>'GenAI Hackathon recognition','icon'=>'fa-trophy','text'=>'2nd Prize in the GenAI Hackathon 2025 — AgriTech Theme, for Kisan Mitra AI, an AI-powered agricultural advisory concept.'],
         ['title'=>'Generative AI certification','icon'=>'fa-certificate','text'=>'Completed Introduction to Generative AI from Google.'],
         ['title'=>'Digital growth experience','icon'=>'fa-chart-line','text'=>'Experience spanning website development, digital marketing, SEO, lead generation, advertising and performance marketing.'],
         ['title'=>'International perspective','icon'=>'fa-globe','text'=>'Works toward serving startups, SMEs and businesses across India and international markets.'],
       ])],

      ['gallery', 'gallery', 'Gallery', '', 90, null, null],

      ['vision', 'quote', 'Vision & mission', 'Technology should not simply make a business digital. It should make the business better.', 100,
       "<h3>Building the future of digital innovation</h3><p>The vision for Appsgain Technologies is a company that combines innovation, engineering, design, AI, marketing and business strategy — creating digital solutions that help businesses launch faster, automate operations, reach more customers, improve productivity, make better decisions and scale sustainably.</p>"
     . "<h3>Mission</h3><p>Help businesses turn ideas into reliable digital products, and turn technology investments into measurable business outcomes. From websites and mobile applications to enterprise software, SaaS platforms, AI solutions and digital growth systems, the focus is technology that delivers practical value.</p>"
     . "<h3>On entrepreneurship</h3><p>Entrepreneurship, for him, is about continuously solving problems. The technology industry changes quickly — customer expectations shift, search engines change, and AI is transforming how products are built and marketed. A technology entrepreneur has to remain adaptable, building teams, capabilities and solutions that evolve alongside those changes.</p>", null],

      ['principles', 'list', 'Connecting technology with business', '', 110,
       "<p>The best technology solution is not necessarily the one with the most features — it is the one that solves the right problem. A successful project should answer three questions:</p>",
       $J([
         ['title'=>"Does it solve the customer's problem?"],
         ['title'=>'Does it create measurable business value?'],
         ['title'=>'Can it scale with the business?'],
       ])],

      ['faq', 'faq', 'Frequently asked questions', '', 120, null,
       $J([
         ['title'=>'Who is {name}?','text'=>'{name} is the Founder and CEO of {company}, a software development and digital innovation company founded in {since}.'],
         ['title'=>'What does {name} do?','text'=>'He works across technology entrepreneurship, software development, digital marketing, SEO, lead generation, performance marketing, AI and digital business growth.'],
         ['title'=>'When was Appsgain Technologies founded?','text'=>'Appsgain Technologies was founded in {since}.'],
         ['title'=>"What is {name}'s role at Appsgain Technologies?",'text'=>'{name} is the Founder and CEO of Appsgain Technologies.'],
         ['title'=>'What are his areas of expertise?','text'=>'Software development, website development, mobile applications, digital marketing, SEO, lead generation, performance marketing, AI, automation and digital transformation.'],
         ['title'=>'How can I contact {name}?','text'=>'For business and technology enquiries, contact Appsgain Technologies at {email} or connect through his LinkedIn profile.'],
       ])],
    ];

    foreach ($sections as [$key, $layout, $heading, $sub, $order, $body, $items]) {
        dbInsertRow('founder_sections', [
            'section_key' => $key, 'layout' => $layout, 'heading' => $heading,
            'subheading'  => $sub,  'body'   => $body,   'items'   => $items,
            'sort_order'  => $order, 'is_active' => $key === 'gallery' ? 0 : 1,
        ]);
    }
    $log('seeded ' . count($sections) . ' sections (gallery starts hidden until photos are added)');
}

$log('');
$log('Migration complete.');
