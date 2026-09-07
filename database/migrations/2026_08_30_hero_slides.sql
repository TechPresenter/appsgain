-- ================================================================
-- APPSGAIN - Homepage hero slides (2026-08-30)
-- Rewritten around the three things the company actually sells:
-- AI-first products, mobile app development, and custom software.
-- All of this stays editable from Admin -> Hero Slides.
--
-- title: "\n" splits the headline; the second line renders in the
-- brand gradient, so keep the strongest phrase on line two.
-- ================================================================
SET NAMES utf8mb4;

DELETE FROM `hero_slides` WHERE `page_key` = 'home';

INSERT INTO `hero_slides`
  (`page_key`, `title`, `subtitle`, `badge_text`,
   `btn1_text`, `btn1_url`, `btn2_text`, `btn2_url`, `sort_order`, `is_active`)
VALUES

('home',
 'Building AI-First Apps & Software\nfor the Future',
 'We design and engineer intelligent products - mobile apps, web platforms and enterprise software with AI built in from day one, not bolted on later.',
 'AI POWERED IT SERVICES',
 'Get a Free Quote', '/contact.php#enquiry',
 'Explore Services',  '/services.php',
 1, 1),

('home',
 'Mobile Apps That Users\nActually Keep Using',
 'Native Android and iOS, or one Flutter codebase for both. Fast, offline-ready, store-compliant apps - designed, built, launched and supported by one team.',
 'MOBILE APP DEVELOPMENT',
 'Start Your App', '/service/mobile-app-development',
 'See Our Apps',   '/apps.php',
 2, 1),

('home',
 'Custom Software Built Around\nHow Your Business Runs',
 'Off-the-shelf tools force you to change your process. We build software that fits it - web portals, internal platforms and automation your team adopts on day one.',
 'SOFTWARE DEVELOPMENT',
 'Discuss Your Project', '/contact.php#enquiry',
 'View Our Work',        '/portfolio.php',
 3, 1),

('home',
 'ERP, CRM & SaaS Platforms\nEngineered to Scale',
 'From a first paying customer to enterprise rollout - multi-tenant architecture, clean integrations and dashboards that give you real numbers, not vanity metrics.',
 'ENTERPRISE PLATFORMS',
 'Book a Consultation', '/contact.php#enquiry',
 'Our Products',        '/products.php',
 4, 1),

('home',
 'Put AI to Work Across\nYour Everyday Operations',
 'Practical AI, not demos: document processing, support automation, forecasting and workflow agents wired into the systems your team already uses.',
 'AI & AUTOMATION',
 'Talk to an Engineer', '/contact.php#enquiry',
 'AI Product Development', '/service/ai-product-development',
 5, 1);
