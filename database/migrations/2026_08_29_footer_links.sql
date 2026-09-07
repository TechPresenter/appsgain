-- ================================================================
-- APPSGAIN - Footer link lists (2026-08-29)
-- Footer Quick Links and Legal Links become admin-editable.
-- Format, one link per line:  Label | url | fa-icon | #colour | badge
-- The old hardcoded admin-panel link in the footer is deliberately
-- not carried over.
-- ================================================================
SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('footer_links_title', 'Quick Links', 'footer'),
('footer_quick_links',
'Home | / | fa-home | #7a00f0
About Us | /about.php | fa-building | #8b2bf5
Services | /services.php | fa-layer-group | #9b0fd6
Portfolio | /portfolio.php | fa-briefcase | #a80193
Our Products | /products.php | fa-box-open | #c801ae
Mobile Apps | /apps.php | fa-mobile-alt | #e0019b
Blog & Insights | /blog.php | fa-newspaper | #fa0e6c
Our Clients | /clients.php | fa-handshake | #ff3b0d
Careers | /careers.php | fa-rocket | #ff8a00 | Hiring
Support | /support.php | fa-life-ring | #9b0fd6
FAQs | /faq.php | fa-question-circle | #8b2bf5
Contact Us | /contact.php | fa-envelope | #7a00f0',
'footer'),
('footer_legal_links',
'Privacy | /privacy-policy.php | fa-shield-alt
App Privacy | /app-privacy-policy.php | fa-mobile-alt
Terms | /terms-of-service.php | fa-file-contract
Refunds | /refund-policy.php | fa-undo-alt
Cookies | /cookie-policy.php | fa-cookie-bite
Disclaimer | /disclaimer.php | fa-circle-exclamation
Sitemap | /sitemap-html.php | fa-sitemap',
'footer')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `setting_group` = VALUES(`setting_group`);
