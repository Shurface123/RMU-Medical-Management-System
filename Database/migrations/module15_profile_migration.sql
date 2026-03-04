-- ═══════════════════════════════════════════════════════════
-- Module 15: Advanced Nurse Profile — Database Migration
-- phpMyAdmin compatible — no DELIMITER or stored procedures
-- Run each statement individually (phpMyAdmin handles this)
-- ═══════════════════════════════════════════════════════════

-- 1. Nurse Professional Profile (new table)
CREATE TABLE IF NOT EXISTS `nurse_professional_profile` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nurse_id` INT NOT NULL,
  `specialization` VARCHAR(100) DEFAULT NULL,
  `sub_specialization` VARCHAR(200) DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `designation` VARCHAR(100) DEFAULT 'Staff Nurse',
  `years_of_experience` INT DEFAULT 0,
  `license_number` VARCHAR(100) DEFAULT NULL,
  `license_issuing_body` VARCHAR(200) DEFAULT NULL,
  `license_expiry_date` DATE DEFAULT NULL,
  `nursing_school` VARCHAR(200) DEFAULT NULL,
  `graduation_year` INT DEFAULT NULL,
  `postgraduate_details` TEXT DEFAULT NULL,
  `languages_spoken` JSON DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_nurse_prof` (`nurse_id`),
  FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Nurse Profile Completeness (create if not exists)
CREATE TABLE IF NOT EXISTS `nurse_profile_completeness` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nurse_id` INT NOT NULL,
  `personal_info` TINYINT(1) DEFAULT 0,
  `professional_profile` TINYINT(1) DEFAULT 0,
  `qualifications` TINYINT(1) DEFAULT 0,
  `shift_profile` TINYINT(1) DEFAULT 0,
  `profile_photo` TINYINT(1) DEFAULT 0,
  `security_setup` TINYINT(1) DEFAULT 0,
  `documents_uploaded` TINYINT(1) DEFAULT 0,
  `completeness_percentage` INT DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_nurse_complete` (`nurse_id`),
  FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- 3. ALTER nurses table — add missing columns
-- NOTE: If a column already exists, that statement will error
-- but phpMyAdmin will continue executing the rest. This is safe.
-- ═══════════════════════════════════════════════════════════

ALTER TABLE `nurses` ADD COLUMN `availability_status` ENUM('Available','Busy','On Break','Off Duty') DEFAULT 'Available';
ALTER TABLE `nurses` ADD COLUMN `secondary_phone` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `personal_email` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `marital_status` VARCHAR(30) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `religion` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `national_id` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `street_address` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `city` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `region` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `country` VARCHAR(100) DEFAULT 'Ghana';
ALTER TABLE `nurses` ADD COLUMN `postal_code` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `office_location` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `member_since` DATE DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `last_login` DATETIME DEFAULT NULL;
ALTER TABLE `nurses` ADD COLUMN `two_fa_enabled` TINYINT(1) DEFAULT 0;
ALTER TABLE `nurses` ADD COLUMN `shift_preference_notes` TEXT DEFAULT NULL;

-- ═══════════════════════════════════════════════════════════
-- 4. ALTER related tables — add missing columns
-- ═══════════════════════════════════════════════════════════

ALTER TABLE `nurse_qualifications` ADD COLUMN `certificate_file` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `nurse_certifications` ADD COLUMN `certificate_file` VARCHAR(500) DEFAULT NULL;

ALTER TABLE `nurse_sessions` ADD COLUMN `browser` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `nurse_sessions` ADD COLUMN `is_current` TINYINT(1) DEFAULT 0;
ALTER TABLE `nurse_sessions` ADD COLUMN `session_id` VARCHAR(128) DEFAULT NULL;

-- ═══════════════════════════════════════════════════════════
-- 5. ALTER nurse_settings — add notification toggle columns
-- ═══════════════════════════════════════════════════════════

ALTER TABLE `nurse_settings` ADD COLUMN `notif_new_task` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_task_overdue` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_med_reminder` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_vital_due` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_abnormal_vital` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_shift_reminder` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_handover` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_doctor_msg` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_emergency` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_cert_expiry` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `notif_system` TINYINT(1) DEFAULT 1;
ALTER TABLE `nurse_settings` ADD COLUMN `preferred_channel` VARCHAR(50) DEFAULT 'dashboard';
ALTER TABLE `nurse_settings` ADD COLUMN `preferred_notif_lang` VARCHAR(10) DEFAULT 'en';
ALTER TABLE `nurse_settings` ADD COLUMN `critical_sound_enabled` TINYINT(1) DEFAULT 1;
