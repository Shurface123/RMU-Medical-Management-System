-- ============================================================
-- PHASE 9: ADVANCED LAB TECHNICIAN PROFILE — DATABASE SCHEMA
-- RMU Medical Sickbay System
-- All tables use IF NOT EXISTS — safe to run on existing databases.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. PROFESSIONAL PROFILE
CREATE TABLE IF NOT EXISTS `lab_technician_professional_profile` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `specialization` VARCHAR(120) DEFAULT NULL,
    `sub_specialization` VARCHAR(120) DEFAULT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `designation` VARCHAR(120) DEFAULT NULL,
    `years_of_experience` TINYINT UNSIGNED DEFAULT 0,
    `license_number` VARCHAR(80) DEFAULT NULL,
    `license_issuing_body` VARCHAR(150) DEFAULT NULL,
    `license_expiry_date` DATE DEFAULT NULL,
    `institution_attended` VARCHAR(200) DEFAULT NULL,
    `graduation_year` YEAR DEFAULT NULL,
    `postgraduate_details` TEXT DEFAULT NULL,
    `languages_spoken` JSON DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tech_prof` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. QUALIFICATIONS
CREATE TABLE IF NOT EXISTS `lab_technician_qualifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `degree_name` VARCHAR(200) NOT NULL,
    `institution_name` VARCHAR(200) NOT NULL,
    `year_awarded` YEAR DEFAULT NULL,
    `certificate_file_path` VARCHAR(500) DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qual_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CERTIFICATIONS
CREATE TABLE IF NOT EXISTS `lab_technician_certifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `certification_name` VARCHAR(200) NOT NULL,
    `issuing_organization` VARCHAR(200) DEFAULT NULL,
    `issue_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `certificate_file_path` VARCHAR(500) DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cert_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. DOCUMENTS (safe ADD if not exists)
CREATE TABLE IF NOT EXISTS `lab_technician_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(300) NOT NULL,
    `file_path` VARCHAR(600) NOT NULL,
    `file_type` VARCHAR(80) DEFAULT NULL,
    `file_size` INT UNSIGNED DEFAULT 0,
    `description` VARCHAR(300) DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_doc_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SESSIONS
CREATE TABLE IF NOT EXISTS `lab_technician_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) DEFAULT NULL,
    `device_info` VARCHAR(300) DEFAULT NULL,
    `browser` VARCHAR(150) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `location` VARCHAR(200) DEFAULT NULL,
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_current` TINYINT(1) DEFAULT 0,
    INDEX `idx_sess_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ACTIVITY LOG
CREATE TABLE IF NOT EXISTS `lab_technician_activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `action_description` VARCHAR(400) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `device_info` VARCHAR(300) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_actlog_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. SETTINGS / NOTIFICATION PREFERENCES
CREATE TABLE IF NOT EXISTS `lab_technician_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `notif_new_order` TINYINT(1) DEFAULT 1,
    `notif_urgent_order` TINYINT(1) DEFAULT 1,
    `notif_critical_value` TINYINT(1) DEFAULT 1,
    `notif_equipment_due` TINYINT(1) DEFAULT 1,
    `notif_reagent_low` TINYINT(1) DEFAULT 1,
    `notif_reagent_expiry` TINYINT(1) DEFAULT 1,
    `notif_result_amendment` TINYINT(1) DEFAULT 1,
    `notif_doctor_message` TINYINT(1) DEFAULT 1,
    `notif_qc_failure` TINYINT(1) DEFAULT 1,
    `notif_license_expiry` TINYINT(1) DEFAULT 1,
    `notif_shift_reminder` TINYINT(1) DEFAULT 1,
    `notif_system_announcements` TINYINT(1) DEFAULT 1,
    `preferred_channel` SET('dashboard','email','sms') DEFAULT 'dashboard',
    `preferred_language` VARCHAR(30) DEFAULT 'English',
    `alert_sound_enabled` TINYINT(1) DEFAULT 1,
    `theme_preference` ENUM('light','dark') DEFAULT 'light',
    `default_view` VARCHAR(30) DEFAULT 'overview',
    `email_notifications` TINYINT(1) DEFAULT 1,
    `sms_notifications` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tech_settings` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PROFILE COMPLETENESS
CREATE TABLE IF NOT EXISTS `lab_technician_profile_completeness` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT UNSIGNED NOT NULL,
    `personal_info_complete` TINYINT(1) DEFAULT 0,
    `professional_profile_complete` TINYINT(1) DEFAULT 0,
    `qualifications_complete` TINYINT(1) DEFAULT 0,
    `equipment_assigned` TINYINT(1) DEFAULT 0,
    `shift_profile_complete` TINYINT(1) DEFAULT 0,
    `photo_uploaded` TINYINT(1) DEFAULT 0,
    `security_setup_complete` TINYINT(1) DEFAULT 0,
    `documents_uploaded` TINYINT(1) DEFAULT 0,
    `overall_percentage` TINYINT UNSIGNED DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tech_completeness` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe column additions to lab_technicians (if not already present)
ALTER TABLE `lab_technicians`
    CHANGE COLUMN IF EXISTS `profile_photo` `profile_photo` VARCHAR(400) DEFAULT NULL;

-- Ensure availability_status column exists
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'lab_technicians'
      AND COLUMN_NAME = 'availability_status'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE lab_technicians ADD COLUMN availability_status ENUM(''Available'',''Busy'',''On Break'',''Off Duty'') DEFAULT ''Available''',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
