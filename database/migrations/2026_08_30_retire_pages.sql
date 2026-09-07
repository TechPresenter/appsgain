-- ================================================================
-- APPSGAIN - Retire testimonials / support / clients pages (2026-08-30)
--
-- The three standalone pages are removed from the site. This migration
-- strips them from the footer menu and per-page SEO, and registers 301
-- redirects so existing inbound links and search results land somewhere
-- sensible instead of a 404.
--
-- NOTE: the testimonials SECTION on the homepage is unaffected - only
-- the standalone /testimonials.php page is retired. The testimonials,
-- clients and partners TABLES are left intact so the admin panel and
-- the homepage section keep working.
-- ================================================================
SET NAMES utf8mb4;

-- ---- 1 · Footer quick links --------------------------------------
UPDATE `settings`
   SET `setting_value` =
'Home | / | fa-home | #7a00f0
About Us | /about.php | fa-building | #8b2bf5
Services | /services.php | fa-layer-group | #9b0fd6
Portfolio | /portfolio.php | fa-briefcase | #a80193
Our Products | /products.php | fa-box-open | #c801ae
Mobile Apps | /apps.php | fa-mobile-alt | #e0019b
Blog & Insights | /blog.php | fa-newspaper | #fa0e6c
Our Partners | /partners.php | fa-handshake | #ff3b0d
Careers | /careers.php | fa-rocket | #ff8a00 | Hiring
FAQs | /faq.php | fa-question-circle | #8b2bf5
Contact Us | /contact.php | fa-envelope | #7a00f0'
 WHERE `setting_key` = 'footer_quick_links';

-- ---- 2 · Per-page SEO rows ---------------------------------------
DELETE FROM `seo_settings` WHERE `page_key` IN ('testimonials', 'support', 'clients');

-- ---- 3 · 301 redirects for the retired URLs ----------------------
CREATE TABLE IF NOT EXISTS `redirects` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `from_url`   VARCHAR(500) NOT NULL,
  `to_url`     VARCHAR(500) NOT NULL,
  `type`       SMALLINT     DEFAULT 301,
  `is_active`  TINYINT(1)   DEFAULT 1,
  `hits`       INT          DEFAULT 0,
  `created_at` DATETIME     DEFAULT NOW()
) ENGINE=InnoDB;

DELETE FROM `redirects` WHERE `from_url` IN ('/testimonials.php', '/support.php', '/clients.php');

INSERT INTO `redirects` (`from_url`, `to_url`, `type`, `is_active`) VALUES
  ('/testimonials.php', '/portfolio.php', 301, 1),   -- social proof lives here now
  ('/support.php',      '/contact.php',   301, 1),   -- support requests go to contact
  ('/clients.php',      '/partners.php',  301, 1);   -- closest surviving page

-- ---- 4 · Drop retired keys from settings -------------------------
DELETE FROM `settings` WHERE `setting_key` IN
  ('meta_title_testimonials','meta_desc_testimonials',
   'meta_title_support','meta_desc_support',
   'meta_title_clients','meta_desc_clients');
