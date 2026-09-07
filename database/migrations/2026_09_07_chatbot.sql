-- ═══════════════════════════════════════════════════════════════
-- AI Chatbot — leads table + default settings
-- Safe to run more than once.
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `chatbot_leads` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id`  varchar(64)  NOT NULL,
  `name`        varchar(120) NOT NULL DEFAULT '',
  `phone`       varchar(32)  NOT NULL DEFAULT '',
  `email`       varchar(200) NOT NULL DEFAULT '',
  `requirement` text         DEFAULT NULL,
  -- 'partial' is a flow the visitor abandoned part-way. Those rows are the
  -- reason the lead is written on every answered question rather than only
  -- at the end: a name and a mobile number is already a callable lead.
  `status`      enum('partial','new','contacted','in_progress','converted','rejected','spam')
                NOT NULL DEFAULT 'partial',
  `step`        tinyint(3) unsigned NOT NULL DEFAULT 0,
  `transcript`  longtext     DEFAULT NULL COMMENT 'JSON [{role,content,at}] — the conversation this lead came out of',
  `source_page` varchar(300) NOT NULL DEFAULT '',
  `ip_address`  varchar(45)  NOT NULL DEFAULT '',
  `user_agent`  varchar(255) NOT NULL DEFAULT '',
  `admin_notes` text         DEFAULT NULL,
  `is_read`     tinyint(1)   NOT NULL DEFAULT 0,
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  -- No ON UPDATE clause. It would restamp the row every time the transcript
  -- grew, and then "last updated" would mean "last thing the bot said".
  `updated_at`  timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session` (`session_id`),
  KEY `idx_status`  (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_email`   (`email`),
  KEY `idx_read`    (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Defaults ──────────────────────────────────────────────────
-- INSERT IGNORE, so re-running never overwrites what an admin has since
-- edited. The real fallbacks also live in includes/chatbot.php, which is
-- what a brand-new database with no rows at all reads from.
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('chatbot_enabled',      '0', 'chatbot'),
('chatbot_name',         'Appsgain Assistant', 'chatbot'),
('chatbot_welcome',      'Hi! I''m the Appsgain assistant. Ask me about our services, process or pricing — or tell me what you''re building and I''ll put you in touch with the team.', 'chatbot'),
('chatbot_business_info','', 'chatbot'),
('chatbot_system_prompt','', 'chatbot'),
('chatbot_lead_questions','', 'chatbot'),
('chatbot_color',        '#6A00FF', 'chatbot'),
('chatbot_position',     'bottom-right', 'chatbot'),
('chatbot_model',        'gpt-4o-mini', 'chatbot'),
('chatbot_max_tokens',   '400', 'chatbot'),
('chatbot_temperature',  '0.4', 'chatbot'),
('chatbot_lead_after',   '3', 'chatbot'),
('chatbot_offline_msg',  'Our assistant is offline right now. Please use the contact form and we will reply within 24 hours.', 'chatbot');
