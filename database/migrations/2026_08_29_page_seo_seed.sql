-- ================================================================
-- APPSGAIN - Per-page SEO seed (2026-08-29)
-- Populates seo_settings so Admin -> SEO Manager opens with real
-- values instead of blanks. Admin edits overwrite these freely.
-- ================================================================
SET NAMES utf8mb4;

UPDATE `seo_settings` SET
  meta_title = 'Appsgain Technologies - AI Powered IT Services & Software Development',
  meta_description = 'Appsgain Technologies builds AI-first apps and software - custom web applications, mobile apps, ERP, CRM, SaaS platforms and AI solutions for growing businesses.',
  meta_keywords = 'AI software development, custom software, mobile app development, ERP, CRM, SaaS, IT services India',
  og_type = 'website', twitter_card = 'summary_large_image',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1,
  canonical_url = '', og_title = '', og_description = '', og_image = '',
  twitter_title = '', twitter_description = '', twitter_image = ''
WHERE page_key = 'home';

UPDATE `seo_settings` SET
  meta_title = 'About Appsgain Technologies - Our Story, Mission & Team',
  meta_description = 'Learn about Appsgain Technologies Private Limited - our story, mission, values and the team building AI-first software for businesses across India and worldwide.',
  meta_keywords = 'about Appsgain, software company India, IT services company',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'about';

UPDATE `seo_settings` SET
  meta_title = 'IT Services - Custom Software, Mobile Apps, AI & Cloud | Appsgain',
  meta_description = 'Explore Appsgain services: custom software, mobile app development, ERP, CRM, SaaS platforms, AI products, DevOps and cloud engineering.',
  meta_keywords = 'custom software development, mobile apps, ERP development, CRM, SaaS, AI services',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'services';

UPDATE `seo_settings` SET
  meta_title = 'Contact Appsgain Technologies - Get a Free Project Quote',
  meta_description = 'Talk to Appsgain Technologies about your project. Call, email or send an enquiry - we reply within one business day.',
  meta_keywords = 'contact Appsgain, software development quote, hire developers',
  og_type = 'website', twitter_card = 'summary_large_image',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1,
  canonical_url = '', og_title = '', og_description = '', og_image = '',
  twitter_title = '', twitter_description = '', twitter_image = ''
WHERE page_key = 'contact';

UPDATE `seo_settings` SET
  meta_title = 'Portfolio - Our Work & Case Studies | Appsgain Technologies',
  meta_description = 'Browse projects delivered by Appsgain Technologies across FinTech, e-commerce, healthcare, education, AI and SaaS.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'portfolio';

UPDATE `seo_settings` SET
  meta_title = 'Blog - Technology Insights from Appsgain Technologies',
  meta_description = 'Practical articles on AI, web and mobile development, cloud, DevOps and digital product strategy from the Appsgain engineering team.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'blog';

UPDATE `seo_settings` SET
  meta_title = 'FAQs - Appsgain Technologies',
  meta_description = 'Answers to common questions about Appsgain services, pricing, project timelines, support and our delivery process.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'faq';

UPDATE `seo_settings` SET
  meta_title = 'Careers at Appsgain Technologies - Join Our Team',
  meta_description = 'We are hiring. Explore open roles at Appsgain Technologies and help build AI-first products for clients worldwide.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'careers';

UPDATE `seo_settings` SET
  meta_title = 'Our Software Products - ERP, CRM, HRMS & More | Appsgain',
  meta_description = 'Ready-to-deploy software products from Appsgain Technologies: institute management, restaurant ERP, real-estate CRM, HRMS and more.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'products';

UPDATE `seo_settings` SET
  meta_title = 'Our Mobile Apps - Download on Google Play | Appsgain',
  meta_description = 'Android and iOS apps built and published by Appsgain Technologies. Download our productivity, learning and business apps.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'apps';

UPDATE `seo_settings` SET
  meta_title = 'Our Clients - Trusted by Growing Businesses | Appsgain',
  meta_description = 'The startups, SMEs and enterprises that trust Appsgain Technologies to build and run their software.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'clients';

UPDATE `seo_settings` SET
  meta_title = 'Partners - Appsgain Technologies',
  meta_description = 'Technology and business partners powering Appsgain Technologies. Apply to join our partner ecosystem.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'partners';

UPDATE `seo_settings` SET
  meta_title = 'Gallery - Appsgain Technologies',
  meta_description = 'Photos from the Appsgain Technologies office, team and events.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'gallery';

UPDATE `seo_settings` SET
  meta_title = 'Client Testimonials - Appsgain Technologies',
  meta_description = 'What our clients say about working with Appsgain Technologies.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'testimonials';

UPDATE `seo_settings` SET
  meta_title = 'Support - Appsgain Technologies',
  meta_description = 'Get help from the Appsgain Technologies support team by chat, email or phone.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'support';

UPDATE `seo_settings` SET
  meta_title = 'Sitemap - Appsgain Technologies',
  meta_description = 'Every page on the Appsgain Technologies website, in one place.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1
WHERE page_key = 'sitemap';

-- Legal pages: indexable but low priority, no keyword stuffing
UPDATE `seo_settings` SET meta_title = 'Privacy Policy - Appsgain Technologies',
  meta_description = 'How Appsgain Technologies Private Limited collects, uses, discloses and protects your personal data.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'privacy';
UPDATE `seo_settings` SET meta_title = 'App Privacy Policy - Appsgain Technologies',
  meta_description = 'Privacy policy covering the mobile applications published by Appsgain Technologies Private Limited.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'app-privacy';
UPDATE `seo_settings` SET meta_title = 'Terms of Service - Appsgain Technologies',
  meta_description = 'The terms that govern use of the Appsgain Technologies website and services.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'terms';
UPDATE `seo_settings` SET meta_title = 'Refund & Cancellation Policy - Appsgain Technologies',
  meta_description = 'Refund and cancellation terms for Appsgain Technologies services and products.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'refund';
UPDATE `seo_settings` SET meta_title = 'Delivery Policy - Appsgain Technologies',
  meta_description = 'How Appsgain Technologies delivers software, licences and project handovers.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'shipping';
UPDATE `seo_settings` SET meta_title = 'Cookie Policy - Appsgain Technologies',
  meta_description = 'Which cookies the Appsgain Technologies website uses, why, and how to manage them.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'cookie';
UPDATE `seo_settings` SET meta_title = 'Disclaimer - Appsgain Technologies',
  meta_description = 'Important notices about website accuracy, professional advice and liability.',
  noindex = 0, nofollow = 0, robots = 'index,follow', is_active = 1 WHERE page_key = 'disclaimer';
