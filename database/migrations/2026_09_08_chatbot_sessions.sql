-- ═══════════════════════════════════════════════════════════════
-- Chatbot — every conversation, voice input, and appearance controls
-- Safe to run more than once.
-- ═══════════════════════════════════════════════════════════════

-- ── Sessions ──────────────────────────────────────────────────
-- chatbot_leads only ever held conversations that reached the lead flow or
-- ran past two replies, so a visitor who asked one question and left was
-- never recorded. This table holds every conversation. Leads stay in
-- chatbot_leads and are linked by lead_id, which keeps the leads screen a
-- list of people to ring rather than a mixed pile.
CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
  `id`            int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id`    varchar(64)  NOT NULL,
  -- Shown as the row title, so the list reads as questions, not ids.
  `first_message` varchar(300) NOT NULL DEFAULT '',
  `message_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `transcript`    longtext     DEFAULT NULL COMMENT 'JSON [{role,content,at}]',
  `lead_id`       int(10) unsigned DEFAULT NULL COMMENT 'chatbot_leads.id when this chat produced one',
  `source_page`   varchar(300) NOT NULL DEFAULT '',
  `ip_address`    varchar(45)  NOT NULL DEFAULT '',
  `user_agent`    varchar(255) NOT NULL DEFAULT '',
  `is_read`       tinyint(1)   NOT NULL DEFAULT 0,
  `started_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  -- Set explicitly on each write. An ON UPDATE clause would make this mean
  -- "row last touched" rather than "visitor last spoke".
  `last_at`       timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session`  (`session_id`),
  KEY `idx_last`    (`last_at`),
  KEY `idx_started` (`started_at`),
  KEY `idx_lead`    (`lead_id`),
  KEY `idx_read`    (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Appearance + voice defaults ───────────────────────────────
-- INSERT IGNORE so re-running never overwrites what an admin has edited.
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
-- Voice
('chatbot_voice',          '1',        'chatbot'),
-- Imagery: paths under /uploads, blank falls back to the letter avatar
('chatbot_avatar',         '',         'chatbot'),
('chatbot_launcher_icon',  '',         'chatbot'),
-- Colour: color is the primary, color2 the gradient end
('chatbot_color2',         '',         'chatbot'),
('chatbot_header_style',   'gradient', 'chatbot'),
-- Shape and scale
('chatbot_style',          'rounded',  'chatbot'),
('chatbot_size',           'standard', 'chatbot'),
-- Text beside the floating button; blank shows the button alone
('chatbot_launcher_label', '',         'chatbot'),
('chatbot_status_text',    'Online now','chatbot');
