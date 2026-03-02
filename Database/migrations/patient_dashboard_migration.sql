-- ============================================================
-- RMU MEDICAL SICKBAY — PATIENT DASHBOARD MIGRATION
-- ALTER 5 existing tables + CREATE 2 new tables
-- Database: rmu_medical_sickbay
-- Date: 2026-03-02
-- Safe to re-run: IF NOT EXISTS / column-check guards
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- ============================================================
-- SECTION A: ALTER EXISTING TABLES
-- ============================================================

-- ─── A1. ALTER patients — add profile/status fields ──────────

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='profile_photo');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `profile_photo` VARCHAR(500) DEFAULT NULL AFTER `insurance_number`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='registration_status');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `registration_status` ENUM(\'Active\',\'Inactive\',\'Suspended\') DEFAULT \'Active\' AFTER `profile_photo`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='nationality');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `nationality` VARCHAR(100) DEFAULT \'Ghanaian\' AFTER `registration_status`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='religion');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `religion` VARCHAR(100) DEFAULT NULL AFTER `nationality`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='marital_status');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `marital_status` VARCHAR(50) DEFAULT NULL AFTER `religion`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='occupation');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `occupation` VARCHAR(200) DEFAULT NULL AFTER `marital_status`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='secondary_phone');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `secondary_phone` VARCHAR(20) DEFAULT NULL AFTER `occupation`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='personal_email');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `personal_email` VARCHAR(200) DEFAULT NULL AFTER `secondary_phone`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='street_address');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `street_address` VARCHAR(300) DEFAULT NULL AFTER `personal_email`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='city');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `street_address`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='region');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `region` VARCHAR(100) DEFAULT NULL AFTER `city`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='country');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `country` VARCHAR(100) DEFAULT \'Ghana\' AFTER `region`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patients' AND COLUMN_NAME='postal_code');
SET @s=IF(@c=0,'ALTER TABLE `patients` ADD COLUMN `postal_code` VARCHAR(20) DEFAULT NULL AFTER `country`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─── A2. ALTER appointments — add reschedule/cancel fields ───

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='appointments' AND COLUMN_NAME='reschedule_date');
SET @s=IF(@c=0,'ALTER TABLE `appointments` ADD COLUMN `reschedule_date` DATE DEFAULT NULL AFTER `reschedule_reason`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='appointments' AND COLUMN_NAME='reschedule_time');
SET @s=IF(@c=0,'ALTER TABLE `appointments` ADD COLUMN `reschedule_time` TIME DEFAULT NULL AFTER `reschedule_date`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='appointments' AND COLUMN_NAME='cancellation_reason');
SET @s=IF(@c=0,'ALTER TABLE `appointments` ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL AFTER `reschedule_time`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='appointments' AND COLUMN_NAME='cancelled_by');
SET @s=IF(@c=0,'ALTER TABLE `appointments` ADD COLUMN `cancelled_by` INT DEFAULT NULL AFTER `cancellation_reason`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─── A3. ALTER prescriptions — add patient notification flag ─

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='prescriptions' AND COLUMN_NAME='patient_notified');
SET @s=IF(@c=0,'ALTER TABLE `prescriptions` ADD COLUMN `patient_notified` TINYINT(1) DEFAULT 0 AFTER `status`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='prescriptions' AND COLUMN_NAME='refill_count');
SET @s=IF(@c=0,'ALTER TABLE `prescriptions` ADD COLUMN `refill_count` INT DEFAULT 0 AFTER `refills_allowed`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─── A4. ALTER lab_results — add patient visibility fields ───

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='patient_accessible');
SET @s=IF(@c=0,'ALTER TABLE `lab_results` ADD COLUMN `patient_accessible` TINYINT(1) DEFAULT 0 AFTER `doctor_reviewed` COMMENT \'Doctor has released results to patient\'','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='patient_notified');
SET @s=IF(@c=0,'ALTER TABLE `lab_results` ADD COLUMN `patient_notified` TINYINT(1) DEFAULT 0 AFTER `patient_accessible`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='patient_viewed_at');
SET @s=IF(@c=0,'ALTER TABLE `lab_results` ADD COLUMN `patient_viewed_at` DATETIME DEFAULT NULL AFTER `patient_notified`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─── A5. ALTER medical_records — add treatment plan & attachments ─

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medical_records' AND COLUMN_NAME='treatment_plan');
SET @s=IF(@c=0,'ALTER TABLE `medical_records` ADD COLUMN `treatment_plan` TEXT DEFAULT NULL AFTER `treatment`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medical_records' AND COLUMN_NAME='attachments');
SET @s=IF(@c=0,'ALTER TABLE `medical_records` ADD COLUMN `attachments` JSON DEFAULT NULL COMMENT \'JSON array of file paths\' AFTER `treatment_plan`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medical_records' AND COLUMN_NAME='severity');
SET @s=IF(@c=0,'ALTER TABLE `medical_records` ADD COLUMN `severity` ENUM(\'Mild\',\'Moderate\',\'Severe\',\'Critical\') DEFAULT NULL AFTER `attachments`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medical_records' AND COLUMN_NAME='patient_visible');
SET @s=IF(@c=0,'ALTER TABLE `medical_records` ADD COLUMN `patient_visible` TINYINT(1) DEFAULT 1 AFTER `severity`','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


-- ============================================================
-- SECTION B: CREATE NEW TABLES
-- ============================================================

-- ─── B1. emergency_contacts ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `patient_id`      INT NOT NULL COMMENT 'patients.id',
  `contact_name`    VARCHAR(200) NOT NULL,
  `relationship`    VARCHAR(100) NOT NULL,
  `phone`           VARCHAR(20) NOT NULL,
  `email`           VARCHAR(200) DEFAULT NULL,
  `address`         VARCHAR(500) DEFAULT NULL,
  `is_primary`      TINYINT(1) DEFAULT 0,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing emergency contacts from patients table into the new table
-- (Only runs for rows not yet migrated)
INSERT IGNORE INTO `emergency_contacts` (`patient_id`, `contact_name`, `relationship`, `phone`, `is_primary`)
SELECT p.id, p.emergency_contact_name, COALESCE(p.emergency_contact_relationship,'Family'), p.emergency_contact_phone, 1
FROM patients p
WHERE p.emergency_contact_name IS NOT NULL
  AND p.emergency_contact_name != ''
  AND p.id NOT IN (SELECT patient_id FROM emergency_contacts);


-- ─── B2. patient_settings ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `patient_settings` (
  `id`                      INT NOT NULL AUTO_INCREMENT,
  `patient_id`              INT NOT NULL COMMENT 'patients.id',
  `email_notifications`     TINYINT(1) DEFAULT 1,
  `sms_notifications`       TINYINT(1) DEFAULT 0,
  `appointment_reminders`   TINYINT(1) DEFAULT 1,
  `prescription_alerts`     TINYINT(1) DEFAULT 1,
  `lab_result_alerts`       TINYINT(1) DEFAULT 1,
  `medical_record_alerts`   TINYINT(1) DEFAULT 1,
  `profile_visibility`      ENUM('public','doctors_only','private') DEFAULT 'doctors_only',
  `language_preference`     VARCHAR(50) DEFAULT 'English',
  `preferred_channel`       VARCHAR(100) DEFAULT 'dashboard',
  `updated_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ps_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION C: FOREIGN KEYS
-- ============================================================

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='emergency_contacts' AND CONSTRAINT_NAME='fk_ec_patient');
SET @s=IF(@fk=0,'ALTER TABLE `emergency_contacts` ADD CONSTRAINT `fk_ec_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @fk=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='patient_settings' AND CONSTRAINT_NAME='fk_ps_patient');
SET @s=IF(@fk=0,'ALTER TABLE `patient_settings` ADD CONSTRAINT `fk_ps_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


-- ============================================================
-- SECTION D: INDEXES FOR PERFORMANCE
-- ============================================================

-- Patient-focused composite indexes for dashboard queries
SET @ix=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='appointments' AND INDEX_NAME='idx_apt_patient_date');
SET @s=IF(@ix=0,'ALTER TABLE `appointments` ADD INDEX `idx_apt_patient_date` (`patient_id`,`appointment_date`)','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @ix=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='prescriptions' AND INDEX_NAME='idx_rx_patient_status');
SET @s=IF(@ix=0,'ALTER TABLE `prescriptions` ADD INDEX `idx_rx_patient_status` (`patient_id`,`status`)','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @ix=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND INDEX_NAME='idx_lr_patient');
SET @s=IF(@ix=0,'ALTER TABLE `lab_results` ADD INDEX `idx_lr_patient` (`patient_id`)','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @ix=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medical_records' AND INDEX_NAME='idx_mr_patient_date');
SET @s=IF(@ix=0,'ALTER TABLE `medical_records` ADD INDEX `idx_mr_patient_date` (`patient_id`,`visit_date`)','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @ix=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='notifications' AND INDEX_NAME='idx_notif_user_read');
SET @s=IF(@ix=0,'ALTER TABLE `notifications` ADD INDEX `idx_notif_user_read` (`user_id`,`is_read`)','SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF PATIENT DASHBOARD MIGRATION
-- ============================================================
