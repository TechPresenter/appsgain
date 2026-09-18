-- ═══════════════════════════════════════════════════════════════
-- Statutory identifiers for Appsgain Technologies Private Limited
--
-- The footer falls back to these values in code, so the page is already
-- correct without this migration. Running it matters for the structured
-- data: includes/seo-schema.php builds Organization.identifier from the
-- settings table only, and company_cin / company_gstin were sitting there
-- as empty strings, so the CIN and GSTIN never reached Google.
--
-- Safe to run more than once.
-- ═══════════════════════════════════════════════════════════════

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
  ('company_cin',   'U62099BR2026PTC088106', 'company'),
  ('company_pan',   'ABGCA7436E',            'company'),
  ('company_tan',   'PTNA15135B',            'company'),
  ('company_gstin', '10ABGCA7436E1Z3',       'company')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`),
  `setting_group` = VALUES(`setting_group`);
