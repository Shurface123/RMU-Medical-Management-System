-- RMU Medical Sickbay - Admin Profile Schema Migration
-- Adds MFA columns to users, creates session and notification tables

-- 1. Extend Users table
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `password`,
ADD COLUMN IF NOT EXISTS `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`,
ADD COLUMN IF NOT EXISTS `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png' AFTER `two_factor_enabled`,
ADD COLUMN IF NOT EXISTS `emergency_contact_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `profile_photo`,
ADD COLUMN IF NOT EXISTS `emergency_contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `emergency_contact_name`;

-- 2. Create User Sessions table
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_token` (`session_token`),
  KEY `fk_session_user` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create Notification Preferences table
CREATE TABLE IF NOT EXISTS `user_notification_prefs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `in_app` tinyint(1) NOT NULL DEFAULT 1,
  `email` tinyint(1) NOT NULL DEFAULT 0,
  `push` tinyint(1) NOT NULL DEFAULT 0,
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_event` (`user_id`, `event_type`),
  CONSTRAINT `fk_notif_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
