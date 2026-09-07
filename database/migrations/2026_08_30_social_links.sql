-- ================================================================
-- APPSGAIN - Social profile links (2026-08-30)
-- Adds the platforms that had no setting yet: Threads, Google Business
-- Profile and the WhatsApp Channel. Existing values are left untouched.
-- ================================================================
SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
  ('site_facebook',         '', 'social'),
  ('site_instagram',        '', 'social'),
  ('site_twitter',          '', 'social'),
  ('site_threads',          '', 'social'),
  ('site_youtube',          '', 'social'),
  ('site_gmb',              '', 'social'),
  ('site_linkedin',         '', 'social'),
  ('site_pinterest',        '', 'social'),
  ('site_github',           '', 'social'),
  ('site_whatsapp_channel', '', 'social')
ON DUPLICATE KEY UPDATE `setting_group` = 'social';

-- Seed the handles we already know, without clobbering anything set in Admin
UPDATE `settings` SET `setting_value` = 'https://www.threads.net/@appsgaintechnologies'
 WHERE `setting_key` = 'site_threads' AND (`setting_value` IS NULL OR `setting_value` = '');
