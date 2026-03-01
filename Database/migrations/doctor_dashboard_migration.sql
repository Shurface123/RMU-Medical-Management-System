-- ============================================================
-- RMU MEDICAL SICKBAY — DOCTOR'S DASHBOARD SCHEMA MIGRATION
-- Phase 2: Create / Alter Tables for Doctor Dashboard
-- Database: rmu_medical_sickbay
-- Date: 2026-03-01
-- ============================================================
-- Safe to re-run: uses IF NOT EXISTS / IF EXISTS guards
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- ============================================================
-- SECTION A: ALTER EXISTING TABLES
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- A1. appointments
--     Add: reason, reschedule_reason, notification_sent
--     Extend status ENUM to include 'Rescheduled'
-- ────────────────────────────────────────────────────────────
ALTER TABLE `appointments`
  MODIFY COLUMN `status`
    ENUM('Pending','Confirmed','Completed','Cancelled','No-Show','Rescheduled')
    COLLATE utf8mb4_unicode_ci DEFAULT 'Pending';

-- Add new columns only if they don't already exist (guard via INFORMATION_SCHEMA)
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME   = 'appointments'
    AND COLUMN_NAME  = 'reason'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `appointments` ADD COLUMN `reason` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `symptoms`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME   = 'appointments'
    AND COLUMN_NAME  = 'reschedule_reason'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `appointments` ADD COLUMN `reschedule_reason` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `reason`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME   = 'appointments'
    AND COLUMN_NAME  = 'notification_sent'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `appointments` ADD COLUMN `notification_sent` TINYINT(1) DEFAULT 0 AFTER `reschedule_reason`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A2. lab_tests  (repurposed as lab_requests)
--     Add: technician_id, urgency_level, result_file_path, request_notes
--     Extend status ENUM to include 'Submitted', 'Reviewed'
-- ────────────────────────────────────────────────────────────
ALTER TABLE `lab_tests`
  MODIFY COLUMN `status`
    ENUM('Pending','Submitted','In Progress','Completed','Reviewed','Cancelled')
    COLLATE utf8mb4_unicode_ci DEFAULT 'Pending';

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_tests' AND COLUMN_NAME='technician_id');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_tests` ADD COLUMN `technician_id` INT DEFAULT NULL AFTER `doctor_id`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_tests' AND COLUMN_NAME='urgency_level');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_tests` ADD COLUMN `urgency_level` ENUM(\'Routine\',\'Urgent\',\'Critical\') COLLATE utf8mb4_unicode_ci DEFAULT \'Routine\' AFTER `technician_id`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_tests' AND COLUMN_NAME='result_file_path');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_tests` ADD COLUMN `result_file_path` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `results`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_tests' AND COLUMN_NAME='request_notes');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_tests` ADD COLUMN `request_notes` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `result_file_path`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A3. lab_results
--     Add: submitted_by, doctor_reviewed, result_file_path
-- ────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='submitted_by');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_results` ADD COLUMN `submitted_by` INT DEFAULT NULL COMMENT \'users.id\' AFTER `technician_notes`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='doctor_reviewed');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_results` ADD COLUMN `doctor_reviewed` TINYINT(1) DEFAULT 0 AFTER `submitted_by`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='lab_results' AND COLUMN_NAME='result_file_path');
SET @sql = IF(@col_exists=0,'ALTER TABLE `lab_results` ADD COLUMN `result_file_path` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `doctor_reviewed`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A4. notifications
--     Add: user_role, related_module, related_id
-- ────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='notifications' AND COLUMN_NAME='user_role');
SET @sql = IF(@col_exists=0,'ALTER TABLE `notifications` ADD COLUMN `user_role` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `user_id`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='notifications' AND COLUMN_NAME='related_module');
SET @sql = IF(@col_exists=0,'ALTER TABLE `notifications` ADD COLUMN `related_module` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `action_url`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='notifications' AND COLUMN_NAME='related_id');
SET @sql = IF(@col_exists=0,'ALTER TABLE `notifications` ADD COLUMN `related_id` INT DEFAULT NULL AFTER `related_module`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A5. medicines  (inventory enhancements)
--     Add: unit, supplier_name
-- ────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medicines' AND COLUMN_NAME='unit');
SET @sql = IF(@col_exists=0,'ALTER TABLE `medicines` ADD COLUMN `unit` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT \'tablet\' AFTER `stock_quantity`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='medicines' AND COLUMN_NAME='supplier_name');
SET @sql = IF(@col_exists=0,'ALTER TABLE `medicines` ADD COLUMN `supplier_name` VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `manufacturer`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A6. prescriptions  — add refills_allowed column used in prescription_refills.php
-- ────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='prescriptions' AND COLUMN_NAME='refills_allowed');
SET @sql = IF(@col_exists=0,'ALTER TABLE `prescriptions` ADD COLUMN `refills_allowed` INT DEFAULT 0 AFTER `quantity`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- SECTION B: CREATE NEW TABLES
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- B1. doctor_reports
--     Stores reports generated by doctors (PDF/CSV exports)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_reports` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `report_id`       VARCHAR(50)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type`     ENUM(
                      'Patient Summary',
                      'Appointment History',
                      'Prescription Report',
                      'Lab Summary',
                      'Monthly Activity',
                      'Custom'
                    ) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Custom',
  `title`           VARCHAR(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generated_by`    INT NOT NULL  COMMENT 'doctors.id — who generated this report',
  `date_generated`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `parameters`      JSON         DEFAULT NULL COMMENT 'Filters: date_from, date_to, patient_id, etc.',
  `file_path`       VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description`     TEXT         COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at`      TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_id` (`report_id`),
  KEY `idx_generated_by`   (`generated_by`),
  KEY `idx_date_generated` (`date_generated`),
  KEY `idx_report_type`    (`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- B2. staff_directory
--     Central directory of all clinic staff; optionally links
--     to a system user account via user_id
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `staff_directory` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `staff_id`         VARCHAR(50)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id`          INT          DEFAULT NULL COMMENT 'users.id — if staff has a login account',
  `full_name`        VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role`             ENUM(
                       'Doctor',
                       'Nurse',
                       'Lab Technician',
                       'Pharmacist',
                       'Admin',
                       'Receptionist',
                       'Support Staff'
                     ) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department`       VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization`   VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone`            VARCHAR(20)  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email`            VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location`  VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image`    VARCHAR(300) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `status`           ENUM('Active','On Leave','Inactive','Suspended')
                       COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `hire_date`        DATE         DEFAULT NULL,
  `created_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id`  (`staff_id`),
  KEY `user_id`          (`user_id`),
  KEY `idx_staff_id`     (`staff_id`),
  KEY `idx_role`         (`role`),
  KEY `idx_department`   (`department`),
  KEY `idx_status`       (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- B3. doctor_patient_notes
--     Quick, private notes a doctor writes about a patient
--     (separate from formal medical_records)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_patient_notes` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `note_id`         VARCHAR(50)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_id`       INT NOT NULL  COMMENT 'doctors.id',
  `patient_id`      INT NOT NULL  COMMENT 'patients.id',
  `appointment_id`  INT          DEFAULT NULL COMMENT 'appointments.id — optional link',
  `note_type`       ENUM(
                      'General',
                      'Follow-up',
                      'Warning',
                      'Allergy',
                      'Observation',
                      'Referral'
                    ) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `note`            TEXT         COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_private`      TINYINT(1)   DEFAULT 1 COMMENT '1 = only this doctor can see it',
  `created_at`      TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_id`         (`note_id`),
  KEY `idx_doctor_id`          (`doctor_id`),
  KEY `idx_patient_id`         (`patient_id`),
  KEY `idx_appointment_id`     (`appointment_id`),
  KEY `idx_created_at`         (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION C: VIEWS
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- C1. bed_management  (VIEW — joins beds + bed_assignments)
--     Provides a single query point for bed status + patient
-- ────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW `bed_management` AS
SELECT
  b.id                AS bed_pk,
  b.bed_id,
  b.bed_number,
  b.ward,
  b.bed_type,
  b.status            AS bed_status,
  b.daily_rate,
  ba.id               AS assignment_pk,
  ba.patient_id,
  ba.admission_date,
  ba.discharge_date,
  ba.reason           AS admission_reason,
  ba.status           AS assignment_status,
  p.patient_id        AS patient_ref_id,
  u.name              AS patient_name,
  u.phone             AS patient_phone
FROM `beds` b
LEFT JOIN `bed_assignments` ba
       ON ba.bed_id = b.id AND ba.status = 'Active'
LEFT JOIN `patients` p
       ON ba.patient_id = p.id
LEFT JOIN `users` u
       ON p.user_id = u.id;

-- ────────────────────────────────────────────────────────────
-- C2. medicine_inventory  (VIEW — enriches medicines table)
--     Calculates live stock_status so PHP never needs to
-- ────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW `medicine_inventory` AS
SELECT
  m.id,
  m.medicine_id,
  m.medicine_name,
  m.generic_name,
  m.category,
  m.unit,
  m.unit_price,
  m.stock_quantity,
  m.reorder_level,
  m.expiry_date,
  m.supplier_name,
  m.manufacturer,
  m.batch_number,
  m.is_prescription_required,
  CASE
    WHEN m.stock_quantity = 0                          THEN 'Out of Stock'
    WHEN m.stock_quantity <= m.reorder_level           THEN 'Low Stock'
    WHEN m.expiry_date IS NOT NULL
      AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
      AND m.expiry_date >= CURDATE()                   THEN 'Expiring Soon'
    ELSE 'In Stock'
  END AS stock_status,
  m.created_at,
  m.updated_at
FROM `medicines` m;

-- ============================================================
-- SECTION D: FOREIGN KEY CONSTRAINTS (safe, idempotent)
-- ============================================================

-- doctor_reports → doctors
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME         = 'doctor_reports'
    AND CONSTRAINT_NAME    = 'fk_report_doctor'
    AND CONSTRAINT_TYPE    = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists=0,
  'ALTER TABLE `doctor_reports` ADD CONSTRAINT `fk_report_doctor` FOREIGN KEY (`generated_by`) REFERENCES `doctors`(`id`) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- staff_directory → users
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME         = 'staff_directory'
    AND CONSTRAINT_NAME    = 'fk_staff_user'
    AND CONSTRAINT_TYPE    = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists=0,
  'ALTER TABLE `staff_directory` ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- doctor_patient_notes → doctors
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME         = 'doctor_patient_notes'
    AND CONSTRAINT_NAME    = 'fk_note_doctor'
    AND CONSTRAINT_TYPE    = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists=0,
  'ALTER TABLE `doctor_patient_notes` ADD CONSTRAINT `fk_note_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- doctor_patient_notes → patients
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME         = 'doctor_patient_notes'
    AND CONSTRAINT_NAME    = 'fk_note_patient'
    AND CONSTRAINT_TYPE    = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists=0,
  'ALTER TABLE `doctor_patient_notes` ADD CONSTRAINT `fk_note_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lab_tests.technician_id → users
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'rmu_medical_sickbay'
    AND TABLE_NAME         = 'lab_tests'
    AND CONSTRAINT_NAME    = 'fk_labtest_technician'
    AND CONSTRAINT_TYPE    = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists=0,
  'ALTER TABLE `lab_tests` ADD CONSTRAINT `fk_labtest_technician` FOREIGN KEY (`technician_id`) REFERENCES `users`(`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICATION QUERIES (run manually to confirm all is good)
-- ============================================================
-- SELECT TABLE_NAME, TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES
-- WHERE TABLE_SCHEMA = 'rmu_medical_sickbay'
-- ORDER BY TABLE_TYPE DESC, TABLE_NAME;
--
-- SHOW COLUMNS FROM appointments;
-- SHOW COLUMNS FROM lab_tests;
-- SHOW COLUMNS FROM lab_results;
-- SHOW COLUMNS FROM notifications;
-- SHOW COLUMNS FROM medicines;
-- SHOW COLUMNS FROM prescriptions;
-- DESCRIBE doctor_reports;
-- DESCRIBE staff_directory;
-- DESCRIBE doctor_patient_notes;
-- SELECT * FROM bed_management LIMIT 1;
-- SELECT * FROM medicine_inventory LIMIT 1;
-- ============================================================
-- END OF MIGRATION
-- ============================================================
