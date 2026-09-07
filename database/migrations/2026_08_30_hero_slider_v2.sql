-- ================================================================
-- APPSGAIN - Hero slider v2 (2026-08-30)
--
-- 1. Repairs hero_slides: the table had no PRIMARY KEY and no
--    AUTO_INCREMENT, so every row carried id = 0 and the admin panel
--    could not target a single slide for edit/delete.
-- 2. Seeds six service campaign slides.
--
-- extra_data holds the presentation options as JSON so no schema change
-- is needed and the admin panel keeps working:
--   visual : which device scene to render (web|mobile|software|ecommerce|uiux|marketing)
--   tint   : background wash (blue|violet|sky|amber|lavender|rose)
--
-- title supports:
--   \n      line break in the headline
--   {word}  render that word in the brand gradient
-- ================================================================
SET NAMES utf8mb4;

-- ---- 1 · Repair the table -------------------------------------
SET @rownum := 0;
UPDATE `hero_slides` SET `id` = (@rownum := @rownum + 1) ORDER BY `sort_order`, `page_key`;

ALTER TABLE `hero_slides`
  MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`);

ALTER TABLE `hero_slides` AUTO_INCREMENT = 100;

-- ---- 2 · Seed the six campaign slides --------------------------
DELETE FROM `hero_slides` WHERE `page_key` = 'home';

INSERT INTO `hero_slides`
  (`page_key`, `badge_text`, `title`, `subtitle`,
   `btn1_text`, `btn1_url`, `btn2_text`, `btn2_url`,
   `extra_data`, `sort_order`, `is_active`)
VALUES

('home', 'Web Development',
 'WE BUILD\nPOWERFUL {WEBSITES}\nTHAT PERFORM',
 'Fast, secure, SEO-ready websites and web portals - designed, engineered and shipped by one team.',
 'Start Your Project', '/contact.php#enquiry',
 'View Our Work',      '/portfolio.php',
 '{"visual":"web","tint":"blue"}', 1, 1),

('home', 'Mobile App Development',
 'MOBILE APPS\nYOUR USERS\n{ACTUALLY KEEP}',
 'Native Android and iOS, or one Flutter codebase for both. Store-ready, offline-capable, built to scale.',
 'Build Your App',  '/service/mobile-app-development',
 'See Our Apps',    '/apps.php',
 '{"visual":"mobile","tint":"violet"}', 2, 1),

('home', 'Custom Software Development',
 'SOFTWARE BUILT\nAROUND {HOW YOU}\nACTUALLY WORK',
 'Off-the-shelf tools force you to change your process. We build systems that fit the one you already have.',
 'Discuss Your Project', '/contact.php#enquiry',
 'Our Services',         '/services.php',
 '{"visual":"software","tint":"sky"}', 3, 1),

('home', 'E-Commerce Solutions',
 'ONLINE STORES\nTHAT TURN VISITS\nINTO {REVENUE}',
 'Storefronts, payments, inventory and analytics - engineered for conversion and built to handle peak traffic.',
 'Launch Your Store', '/contact.php#enquiry',
 'Our Products',      '/products.php',
 '{"visual":"ecommerce","tint":"amber"}', 4, 1),

('home', 'UI/UX Design',
 'INTERFACES\nPEOPLE FIND\n{EFFORTLESS}',
 'Research, wireframes, design systems and prototypes - the groundwork that makes a product feel obvious to use.',
 'Start With Design', '/contact.php#enquiry',
 'View Portfolio',    '/portfolio.php',
 '{"visual":"uiux","tint":"lavender"}', 5, 1),

('home', 'Digital Marketing',
 'MARKETING THAT\nMOVES THE\n{NUMBERS}',
 'SEO, paid campaigns and content built on data - measured against pipeline, not vanity metrics.',
 'Grow With Us',   '/contact.php#enquiry',
 'Talk To Us',     '/contact.php',
 '{"visual":"marketing","tint":"rose"}', 6, 1);
