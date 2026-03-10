-- Backup of maccento_mysql
-- Generated at 2026-03-10T08:10:05+00:00

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `ai_usage_logs`;
CREATE TABLE `ai_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` char(36) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `model` varchar(80) NOT NULL,
  `tokens_in` int(10) unsigned NOT NULL DEFAULT 0,
  `tokens_out` int(10) unsigned NOT NULL DEFAULT 0,
  `estimated_cost` decimal(12,6) NOT NULL DEFAULT 0.000000,
  `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_usage_logs_conversation_id_created_at_index` (`conversation_id`,`created_at`),
  CONSTRAINT `ai_usage_logs_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

