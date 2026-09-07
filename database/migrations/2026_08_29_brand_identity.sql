-- ================================================================
-- APPSGAIN - Brand identity migration (2026-08-29)
-- New logo lockup, brand palette sampled from the logo gradient,
-- and the current company naming / taglines.
-- Every value here is editable from Admin -> Settings.
-- Idempotent.
-- ================================================================
SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
  ('brand_primary',    '#7A00F0', 'brand'),
  ('brand_secondary',  '#FA0E6C', 'brand'),
  ('brand_accent',     '#FF8A00', 'brand'),
  ('brand_red',        '#FF3B0D', 'brand'),
  ('brand_magenta',    '#E0019B', 'brand'),
  ('brand_violet',     '#5701F9', 'brand'),
  ('brand_ink',        '#0B0F1A', 'brand'),
  ('brand_gradient',   'linear-gradient(90deg,#FF8A00 0%,#FF3B0D 22%,#FA0E6C 48%,#C801AE 72%,#5701F9 100%)', 'brand'),
  ('site_logo_light',  'logo/appsgain-logo-light.png', 'general'),
  ('site_logo_icon',   'logo/appsgain-icon-192.png',   'general')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `setting_group` = VALUES(`setting_group`);

-- Brand naming + taglines supplied by the client
UPDATE `settings` SET `setting_value` = 'Appsgain Technologies'                        WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'Appsgain Technologies Private Limited'        WHERE `setting_key` = 'company_name';
UPDATE `settings` SET `setting_value` = 'AI Powered IT Services'                       WHERE `setting_key` = 'site_tagline';

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
  ('site_headline', 'Building AI-First Apps & Software for the Future', 'general'),
  ('site_eyebrow',  'AI Powered IT Services', 'general')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- New logo + favicon assets
UPDATE `settings` SET `setting_value` = 'logo/appsgain-logo.png'     WHERE `setting_key` = 'site_logo';
UPDATE `settings` SET `setting_value` = 'logo/appsgain-icon-192.png' WHERE `setting_key` = 'site_favicon';

-- Browser/theme colour follows the brand primary
UPDATE `settings` SET `setting_value` = '#7A00F0' WHERE `setting_key` = 'theme_color';

-- Description + OG alt reflect the new positioning
UPDATE `settings`
   SET `setting_value` = 'Appsgain Technologies Private Limited builds AI-first apps and software - custom web applications, mobile apps, ERP, CRM, SaaS platforms and AI solutions that help businesses grow.'
 WHERE `setting_key` = 'site_description';
UPDATE `settings`
   SET `setting_value` = 'Appsgain Technologies - AI Powered IT Services'
 WHERE `setting_key` = 'seo_og_image_alt';
