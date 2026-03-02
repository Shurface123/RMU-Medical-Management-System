-- ============================================================
-- RMU MEDICAL SICKBAY — MODULE 13: DOCTOR PROFILE MIGRATION
-- ALTER doctors table + 10 new tables
-- Database: rmu_medical_sickbay
-- Date: 2026-03-02
-- Safe to re-run: IF NOT EXISTS / IF EXISTS guards
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- ============================================================
-- SECTION A: ALTER EXISTING TABLES
-- ============================================================

-- ─── A1. ALTER doctors — add professional/personal fields ───

-- Professional fields
SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='department_id');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `department_id` INT DEFAULT NULL AFTER `specialization`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='sub_specialization');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `sub_specialization` VARCHAR(200) DEFAULT NULL AFTER `department_id`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='designation');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `designation` VARCHAR(100) DEFAULT NULL AFTER `sub_specialization`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='professional_title');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `professional_title` VARCHAR(150) DEFAULT NULL AFTER `designation`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='license_issuing_body');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `license_issuing_body` VARCHAR(200) DEFAULT NULL AFTER `license_number`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='license_expiry_date');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `license_expiry_date` DATE DEFAULT NULL AFTER `license_issuing_body`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='medical_school');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `medical_school` VARCHAR(300) DEFAULT NULL AFTER `license_expiry_date`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='graduation_year');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `graduation_year` INT DEFAULT NULL AFTER `medical_school`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='postgraduate_details');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `postgraduate_details` TEXT DEFAULT NULL AFTER `graduation_year`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='languages_spoken');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `languages_spoken` JSON DEFAULT NULL AFTER `postgraduate_details`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Personal fields
SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='nationality');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `nationality` VARCHAR(100) DEFAULT NULL AFTER `bio`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='marital_status');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `marital_status` VARCHAR(50) DEFAULT NULL AFTER `nationality`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='religion');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `religion` VARCHAR(100) DEFAULT NULL AFTER `marital_status`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='national_id');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `national_id` VARCHAR(100) DEFAULT NULL AFTER `religion`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='secondary_phone');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `secondary_phone` VARCHAR(20) DEFAULT NULL AFTER `national_id`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='personal_email');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `personal_email` VARCHAR(150) DEFAULT NULL AFTER `secondary_phone`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='street_address');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `street_address` VARCHAR(300) DEFAULT NULL AFTER `personal_email`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='city');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `street_address`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='region');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `region` VARCHAR(100) DEFAULT NULL AFTER `city`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='country');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `country` VARCHAR(100) DEFAULT \'Ghana\' AFTER `region`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='postal_code');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `postal_code` VARCHAR(20) DEFAULT NULL AFTER `country`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='office_location');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `office_location` VARCHAR(200) DEFAULT NULL AFTER `postal_code`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='availability_status');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `availability_status` ENUM(\'Online\',\'Offline\',\'Busy\') DEFAULT \'Offline\' AFTER `is_available`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctors' AND COLUMN_NAME='profile_completion_pct');
SET @s=IF(@c=0,'ALTER TABLE `doctors` ADD COLUMN `profile_completion_pct` TINYINT DEFAULT 0 AFTER `availability_status`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─── A2. ALTER users — add login tracking ───────────────────
SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='users' AND COLUMN_NAME='last_login_at');
SET @s=IF(@c=0,'ALTER TABLE `users` ADD COLUMN `last_login_at` DATETIME DEFAULT NULL','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='users' AND COLUMN_NAME='last_active_at');
SET @s=IF(@c=0,'ALTER TABLE `users` ADD COLUMN `last_active_at` DATETIME DEFAULT NULL','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ============================================================
-- SECTION B: CREATE NEW TABLES
-- ============================================================

-- ─── B1. departments ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `head_doctor_id` INT DEFAULT NULL,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed departments
INSERT IGNORE INTO `departments` (`name`,`description`) VALUES
('General Medicine','General outpatient consultations'),
('Pediatrics','Child and adolescent health'),
('Surgery','Surgical procedures and pre/post-op care'),
('Obstetrics & Gynecology','Women health and maternity'),
('Emergency Medicine','Accident and emergency care'),
('Internal Medicine','Diagnosis and treatment of adult diseases'),
('Ophthalmology','Eye care and vision'),
('Dermatology','Skin conditions and treatment'),
('Psychiatry','Mental health and behavioral disorders'),
('Radiology','Medical imaging and diagnostics'),
('Dental','Oral health and dental procedures'),
('Pharmacy','Medication management'),
('Laboratory','Diagnostic lab services');

-- ─── B2. doctor_qualifications ──────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_qualifications` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `doctor_id`       INT NOT NULL COMMENT 'doctors.id',
  `degree_name`     VARCHAR(200) NOT NULL,
  `institution`     VARCHAR(300) NOT NULL,
  `year_awarded`    INT DEFAULT NULL,
  `cert_file_path`  VARCHAR(500) DEFAULT NULL,
  `uploaded_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dq_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B3. doctor_certifications ──────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_certifications` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `doctor_id`         INT NOT NULL COMMENT 'doctors.id',
  `cert_name`         VARCHAR(200) NOT NULL,
  `issuing_org`       VARCHAR(300) NOT NULL,
  `issue_date`        DATE DEFAULT NULL,
  `expiry_date`       DATE DEFAULT NULL,
  `cert_file_path`    VARCHAR(500) DEFAULT NULL,
  `uploaded_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dc_doctor` (`doctor_id`),
  KEY `idx_dc_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B4. doctor_availability ────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_availability` (
  `id`                  INT NOT NULL AUTO_INCREMENT,
  `doctor_id`           INT NOT NULL COMMENT 'doctors.id',
  `day_of_week`         ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `is_available`        TINYINT(1) DEFAULT 1,
  `start_time`          TIME DEFAULT '08:00:00',
  `end_time`            TIME DEFAULT '17:00:00',
  `max_appointments`    INT DEFAULT 20,
  `slot_duration_min`   INT DEFAULT 30,
  `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_day` (`doctor_id`,`day_of_week`),
  KEY `idx_da_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B5. doctor_leave_exceptions ────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_leave_exceptions` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `doctor_id`       INT NOT NULL COMMENT 'doctors.id',
  `exception_date`  DATE NOT NULL,
  `reason`          VARCHAR(500) DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_date` (`doctor_id`,`exception_date`),
  KEY `idx_dle_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B6. doctor_documents ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_documents` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `doctor_id`    INT NOT NULL COMMENT 'doctors.id',
  `file_name`    VARCHAR(300) NOT NULL,
  `file_path`    VARCHAR(500) NOT NULL,
  `file_type`    VARCHAR(50)  DEFAULT NULL,
  `file_size`    INT          DEFAULT 0 COMMENT 'bytes',
  `description`  VARCHAR(500) DEFAULT NULL,
  `uploaded_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dd_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B7. doctor_sessions ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_sessions` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `doctor_id`      INT NOT NULL COMMENT 'doctors.id',
  `session_id`     VARCHAR(200) NOT NULL,
  `device_info`    VARCHAR(300) DEFAULT NULL,
  `browser`        VARCHAR(200) DEFAULT NULL,
  `ip_address`     VARCHAR(45)  DEFAULT NULL,
  `login_time`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current`     TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ds_doctor`  (`doctor_id`),
  KEY `idx_ds_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B8. doctor_activity_log ────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_activity_log` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `doctor_id`   INT NOT NULL COMMENT 'doctors.id',
  `action`      VARCHAR(500) NOT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `device`      VARCHAR(300) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dal_doctor`  (`doctor_id`),
  KEY `idx_dal_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B9. doctor_settings ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_settings` (
  `id`                        INT NOT NULL AUTO_INCREMENT,
  `doctor_id`                 INT NOT NULL COMMENT 'doctors.id',
  `notif_new_appointment`     TINYINT(1) DEFAULT 1,
  `notif_appt_reminders`      TINYINT(1) DEFAULT 1,
  `notif_appt_cancellations`  TINYINT(1) DEFAULT 1,
  `notif_lab_results`         TINYINT(1) DEFAULT 1,
  `notif_rx_refills`          TINYINT(1) DEFAULT 1,
  `notif_record_updates`      TINYINT(1) DEFAULT 1,
  `notif_nurse_messages`      TINYINT(1) DEFAULT 1,
  `notif_inventory_alerts`    TINYINT(1) DEFAULT 1,
  `notif_license_expiry`      TINYINT(1) DEFAULT 1,
  `notif_system_announcements` TINYINT(1) DEFAULT 1,
  `preferred_channel`         VARCHAR(100) DEFAULT 'dashboard',
  `preferred_language`        VARCHAR(50) DEFAULT 'English',
  `updated_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ds_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── B10. doctor_profile_completeness ───────────────────────
CREATE TABLE IF NOT EXISTS `doctor_profile_completeness` (
  `id`                    INT NOT NULL AUTO_INCREMENT,
  `doctor_id`             INT NOT NULL COMMENT 'doctors.id',
  `personal_info`         TINYINT(1) DEFAULT 0,
  `professional_profile`  TINYINT(1) DEFAULT 0,
  `qualifications`        TINYINT(1) DEFAULT 0,
  `availability_set`      TINYINT(1) DEFAULT 0,
  `photo_uploaded`        TINYINT(1) DEFAULT 0,
  `security_setup`        TINYINT(1) DEFAULT 0,
  `documents_uploaded`    TINYINT(1) DEFAULT 0,
  `overall_pct`           TINYINT DEFAULT 0,
  `last_updated`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dpc_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION C: FOREIGN KEYS
-- ============================================================

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_qualifications' AND CONSTRAINT_NAME='fk_dq_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_qualifications` ADD CONSTRAINT `fk_dq_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_certifications' AND CONSTRAINT_NAME='fk_dc_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_certifications` ADD CONSTRAINT `fk_dc_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_availability' AND CONSTRAINT_NAME='fk_da_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_availability` ADD CONSTRAINT `fk_da_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_leave_exceptions' AND CONSTRAINT_NAME='fk_dle_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_leave_exceptions` ADD CONSTRAINT `fk_dle_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_documents' AND CONSTRAINT_NAME='fk_dd_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_documents` ADD CONSTRAINT `fk_dd_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_sessions' AND CONSTRAINT_NAME='fk_dss_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_sessions` ADD CONSTRAINT `fk_dss_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_activity_log' AND CONSTRAINT_NAME='fk_dal_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_activity_log` ADD CONSTRAINT `fk_dal_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_settings' AND CONSTRAINT_NAME='fk_dst_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_settings` ADD CONSTRAINT `fk_dst_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='doctor_profile_completeness' AND CONSTRAINT_NAME='fk_dpc_doctor');
SET @s=IF(@fk=0,'ALTER TABLE `doctor_profile_completeness` ADD CONSTRAINT `fk_dpc_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF MODULE 13 MIGRATION
-- ============================================================
