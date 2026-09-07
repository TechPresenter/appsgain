-- ================================================================
-- APPSGAIN - Dynamic CMS migration (2026-08-29)
-- 1. Extends `seo_settings` with the fields the admin SEO manager needs
-- 2. Seeds every global/company/analytics value into `settings`
-- 3. Normalises the company name to "Appsgain Technologies Private Limited"
-- Idempotent: safe to run more than once.
-- ================================================================
SET NAMES utf8mb4;

-- ---- 1. seo_settings: add the columns the admin form expects ----
ALTER TABLE `seo_settings`
  ADD COLUMN IF NOT EXISTS `page_label`          VARCHAR(120) NULL AFTER `page_key`,
  ADD COLUMN IF NOT EXISTS `og_type`             VARCHAR(50)  NOT NULL DEFAULT 'website' AFTER `og_image`,
  ADD COLUMN IF NOT EXISTS `twitter_card`        VARCHAR(50)  NOT NULL DEFAULT 'summary_large_image' AFTER `og_type`,
  ADD COLUMN IF NOT EXISTS `twitter_title`       VARCHAR(300) NULL AFTER `twitter_card`,
  ADD COLUMN IF NOT EXISTS `twitter_description` VARCHAR(500) NULL AFTER `twitter_title`,
  ADD COLUMN IF NOT EXISTS `twitter_image`       VARCHAR(255) NULL AFTER `twitter_description`,
  ADD COLUMN IF NOT EXISTS `noindex`             TINYINT(1)   NOT NULL DEFAULT 0 AFTER `robots`,
  ADD COLUMN IF NOT EXISTS `nofollow`            TINYINT(1)   NOT NULL DEFAULT 0 AFTER `noindex`,
  ADD COLUMN IF NOT EXISTS `is_active`           TINYINT(1)   NOT NULL DEFAULT 1 AFTER `nofollow`;

-- ---- 2. Global settings ----------------------------------------
-- Existing rows keep their admin-entered values; only missing keys are added.
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES

-- Company identity
('company_name',        'Appsgain Technologies Private Limited', 'company'),
('site_name',           'Appsgain Technologies',                 'general'),
('site_tagline',        'Transforming Ideas Into Digital Reality','general'),
('site_description',    'Appsgain Technologies Private Limited is a software development company delivering custom web applications, mobile apps, ERP, CRM, CMS, SaaS platforms, AI solutions and enterprise software. We help businesses accelerate growth through innovative, scalable, technology-driven solutions.', 'general'),
('company_founded_year','2018',                                  'company'),
('company_employees',   '11-50',                                 'company'),
('company_cin',         '',                                      'company'),
('company_gstin',       '',                                      'company'),
('company_price_range', '$$$',                                   'company'),
('company_service_areas','India, United States, United Kingdom, United Arab Emirates, Australia, Canada','company'),
('company_awards',      '',                                      'company'),
('company_knows_about', 'Software Development, Mobile App Development, Web Development, AI and Machine Learning, Cloud Computing, ERP Systems, CRM Development, SaaS Platforms', 'company'),

-- Contact
('site_email',          'info@appsgain.in',                      'general'),
('site_phone',          '+91-9955446477',                        'general'),
('site_phone_alt',      '',                                      'general'),
('sales_email',         'sales@appsgain.in',                     'general'),
('support_email',       'support@appsgain.in',                   'general'),
('hr_email',            'careers@appsgain.in',                   'general'),
('google_maps_embed',   '',                                      'general'),

-- Structured postal address (drives schema.org + geo meta tags)
('address_street',      'Wave One, 36th Floor, L-2A, Pocket G, Sector 18', 'company'),
('address_locality',    'Noida',                                 'company'),
('address_region',      'Uttar Pradesh',                         'company'),
('address_region_code', 'IN-UP',                                 'company'),
('address_postal',      '201301',                                'company'),
('address_country',     'IN',                                    'company'),
('geo_latitude',        '28.5706',                               'company'),
('geo_longitude',       '77.3261',                               'company'),

-- Branding
('site_logo',           'logo/appsgain-logo.png',                'general'),
('site_favicon',        'logo/appsgain-logo.png',                'general'),
('og_default_image',    '',                                      'seo'),
('theme_color',         '#4f46e5',                               'general'),

-- Social profiles
('site_facebook',       'https://www.facebook.com/appsgain',     'social'),
('site_instagram',      'https://www.instagram.com/appsgaintechnologies','social'),
('site_linkedin',       'https://www.linkedin.com/company/appsgain-technologies','social'),
('site_twitter',        'https://x.com/appsgain_in',             'social'),
('site_youtube',        '',                                      'social'),
('site_github',         '',                                      'social'),
('site_pinterest',      '',                                      'social'),
('site_telegram',       '',                                      'social'),
('site_whatsapp',       '919955446477',                          'social'),
('twitter_handle',      '@appsgain_in',                          'social'),

-- Analytics / tracking (admin-editable, nothing hardcoded)
('google_analytics',       '', 'analytics'),
('google_tag_manager',     '', 'analytics'),
('facebook_pixel',         '', 'analytics'),
('microsoft_clarity',      '', 'analytics'),
('hotjar_id',              '', 'analytics'),
('linkedin_insight',       '', 'analytics'),
('google_search_console',  '', 'analytics'),
('bing_verification',      '', 'analytics'),
('facebook_domain',        '', 'analytics'),
('yandex_verification',    '', 'analytics'),
('pinterest_verification', '', 'analytics'),
('norton_verification',    '', 'analytics'),
('indexnow_key',           '', 'analytics'),

-- Default SEO
('meta_description', 'Appsgain Technologies Private Limited delivers custom software development, mobile apps, ERP, CRM, AI solutions, SaaS platforms and enterprise software to clients across India, USA, UK and UAE.', 'seo'),
('meta_keywords',    'software development,mobile app development,web development,ERP,CRM,AI solutions,SaaS,Appsgain Technologies', 'seo'),
('seo_twitter_domain','appsgain.in', 'seo'),
('seo_article_tags', 'Software Development, IT Services, Mobile Apps, Enterprise Software', 'seo'),
('seo_og_image_alt', 'Appsgain Technologies Private Limited', 'seo'),

-- Footer
('copyright_text',         'All rights reserved.', 'footer'),
('footer_about_text',      'Appsgain Technologies Private Limited is a software development company delivering custom web applications, mobile apps, ERP, CRM, CMS, SaaS platforms, AI solutions and enterprise software. We help businesses accelerate growth through innovative, scalable, technology-driven solutions.', 'footer'),
('footer_built_with',      'Built with passion in India', 'footer'),
('footer_badges',          'ISO Certified|fa-shield-alt,SSL Secure|fa-lock,MSME Registered|fa-check-circle', 'footer'),
('footer_show_stats',      '1', 'footer'),
('footer_show_newsletter', '1', 'footer'),
('footer_show_badges',     '1', 'footer'),
('footer_show_askai',      '1', 'footer'),

-- Metrics (footer strip + about page, single source of truth)
('metric_projects',  '500', 'metrics'),
('metric_clients',   '300', 'metrics'),
('metric_countries', '15',  'metrics'),
('metric_years',     '8',   'metrics'),
('metric_experts',   '50',  'metrics'),
('metric_retention', '98',  'metrics'),
('metric_rating',    '5.0', 'metrics')

ON DUPLICATE KEY UPDATE `setting_key` = `settings`.`setting_key`;

-- ---- 3. Corrections to existing rows ----------------------------
-- Repair the mis-encoded dash in the office address (cp1252 -> UTF-8 damage)
UPDATE `settings`
   SET `setting_value` = 'Wave One, 36th Floor, L-2A, Pocket G, Sector 18, Noida, Uttar Pradesh - 201301'
 WHERE `setting_key` = 'site_address'
   AND `setting_value` LIKE '%Wave One%';

-- Footer copyright is composed at render time from company_name + year;
-- retire the stale baked-in string.
UPDATE `settings`
   SET `setting_value` = 'All rights reserved.', `setting_group` = 'footer'
 WHERE `setting_key` = 'footer_text';

-- Working hours: keep one canonical key
UPDATE `settings`
   SET `setting_value` = 'Mon - Sat: 9:00 AM - 7:00 PM'
 WHERE `setting_key` = 'business_hours';

-- ---- 4. Retire settings for pages that no longer exist ----------
DELETE FROM `settings` WHERE `setting_group` IN ('courses','placement');
DELETE FROM `settings` WHERE `setting_key` IN
  ('meta_title_courses','meta_desc_courses','meta_title_placement','meta_desc_placement');

-- ---- 5. Per-page SEO rows for every live page -------------------
INSERT INTO `seo_settings` (`page_key`, `page_label`, `robots`) VALUES
  ('home',         'Home Page',         'index,follow'),
  ('about',        'About Us',          'index,follow'),
  ('services',     'Services',          'index,follow'),
  ('portfolio',    'Portfolio',         'index,follow'),
  ('blog',         'Blog',              'index,follow'),
  ('contact',      'Contact',           'index,follow'),
  ('faq',          'FAQ',               'index,follow'),
  ('careers',      'Careers',           'index,follow'),
  ('products',     'Products',          'index,follow'),
  ('apps',         'Mobile Apps',       'index,follow'),
  ('clients',      'Clients',           'index,follow'),
  ('partners',     'Partners',          'index,follow'),
  ('gallery',      'Gallery',           'index,follow'),
  ('testimonials', 'Testimonials',      'index,follow'),
  ('support',      'Support',           'index,follow'),
  ('sitemap',      'HTML Sitemap',      'index,follow'),
  ('privacy',      'Privacy Policy',    'index,follow'),
  ('app-privacy',  'App Privacy Policy','index,follow'),
  ('terms',        'Terms of Service',  'index,follow'),
  ('refund',       'Refund Policy',     'index,follow'),
  ('shipping',     'Delivery Policy',   'index,follow'),
  ('cookie',       'Cookie Policy',     'index,follow'),
  ('disclaimer',   'Disclaimer',        'index,follow')
ON DUPLICATE KEY UPDATE `page_label` = VALUES(`page_label`);
