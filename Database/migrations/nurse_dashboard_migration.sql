-- ============================================================
-- NURSE DASHBOARD — DATABASE MIGRATION
-- RMU Medical Sickbay · 26 New Tables
-- Run against: rmu_medical_sickbay
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES';

-- ════════════════════════════════════════════════════════════
-- 1. nurses — Core nurse profile (links to users & staff_directory)
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `nurse_id` VARCHAR(50) NOT NULL COMMENT 'Display ID e.g. NRS-001',
  `full_name` VARCHAR(200) NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `nationality` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `secondary_phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `personal_email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `street_address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'Ghana',
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `profile_photo` VARCHAR(300) DEFAULT NULL,
  `license_number` VARCHAR(100) DEFAULT NULL,
  `license_issuing_body` VARCHAR(200) DEFAULT NULL,
  `license_expiry` DATE DEFAULT NULL,
  `specialization` VARCHAR(200) DEFAULT NULL,
  `department` VARCHAR(200) DEFAULT 'Nursing',
  `designation` VARCHAR(200) DEFAULT NULL COMMENT 'e.g. Head Nurse, Staff Nurse, Charge Nurse',
  `years_of_experience` INT DEFAULT 0,
  `nursing_school` VARCHAR(200) DEFAULT NULL,
  `graduation_year` INT DEFAULT NULL,
  `postgrad_training` TEXT DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `shift_type` ENUM('Morning','Afternoon','Night') DEFAULT 'Morning',
  `ward_assigned` VARCHAR(200) DEFAULT NULL,
  `availability_status` ENUM('Online','Offline','On Break','In Emergency') DEFAULT 'Offline',
  `status` ENUM('Active','Inactive','On Leave','Suspended') DEFAULT 'Active',
  `national_id` VARCHAR(100) DEFAULT NULL,
  `marital_status` ENUM('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `office_location` VARCHAR(200) DEFAULT NULL,
  `profile_completion` INT DEFAULT 0 COMMENT '0-100 percentage',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nurse_user` (`user_id`),
  UNIQUE KEY `uk_nurse_id` (`nurse_id`),
  KEY `idx_nurse_status` (`status`),
  KEY `idx_nurse_dept` (`department`),
  KEY `idx_nurse_shift` (`shift_type`),
  CONSTRAINT `fk_nurse_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 2. nurse_shifts — Shift scheduling and tracking
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_shifts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `shift_type` ENUM('Morning','Afternoon','Night') NOT NULL,
  `shift_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `ward_assigned` VARCHAR(200) DEFAULT NULL,
  `status` ENUM('Scheduled','Active','Completed','Missed','Swapped') DEFAULT 'Scheduled',
  `handover_submitted` TINYINT(1) DEFAULT 0,
  `check_in_time` DATETIME DEFAULT NULL,
  `check_out_time` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shift_nurse` (`nurse_id`),
  KEY `idx_shift_date` (`shift_date`),
  KEY `idx_shift_status` (`status`),
  CONSTRAINT `fk_shift_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 3. nurse_tasks — Doctor/admin task assignments
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_tasks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `assigned_by` INT NOT NULL COMMENT 'user_id of doctor or admin',
  `assigned_by_role` ENUM('Doctor','Admin','Nurse') DEFAULT 'Doctor',
  `patient_id` INT DEFAULT NULL,
  `task_title` VARCHAR(300) NOT NULL,
  `task_description` TEXT DEFAULT NULL,
  `priority` ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `due_time` DATETIME DEFAULT NULL,
  `status` ENUM('Pending','In Progress','Completed','Overdue','Cancelled') DEFAULT 'Pending',
  `completion_notes` TEXT DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task_nurse` (`nurse_id`),
  KEY `idx_task_status` (`status`),
  KEY `idx_task_priority` (`priority`),
  KEY `idx_task_patient` (`patient_id`),
  CONSTRAINT `fk_task_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 4. patient_vitals — Vital signs recorded by nurses
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `patient_vitals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bp_systolic` INT DEFAULT NULL COMMENT 'mmHg',
  `bp_diastolic` INT DEFAULT NULL COMMENT 'mmHg',
  `pulse_rate` INT DEFAULT NULL COMMENT 'bpm',
  `temperature` DECIMAL(4,1) DEFAULT NULL COMMENT '°C',
  `oxygen_saturation` INT DEFAULT NULL COMMENT 'SpO2 %',
  `respiratory_rate` INT DEFAULT NULL COMMENT 'breaths/min',
  `blood_glucose` DECIMAL(5,1) DEFAULT NULL COMMENT 'mg/dL',
  `weight` DECIMAL(5,1) DEFAULT NULL COMMENT 'kg',
  `height` DECIMAL(5,1) DEFAULT NULL COMMENT 'cm',
  `bmi` DECIMAL(4,1) DEFAULT NULL COMMENT 'Auto-calculated: weight/(height/100)^2',
  `pain_level` INT DEFAULT NULL COMMENT '0-10 scale',
  `notes` TEXT DEFAULT NULL,
  `is_flagged` TINYINT(1) DEFAULT 0,
  `flag_reason` VARCHAR(500) DEFAULT NULL,
  `doctor_notified` TINYINT(1) DEFAULT 0,
  `doctor_notified_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vital_patient` (`patient_id`),
  KEY `idx_vital_nurse` (`nurse_id`),
  KEY `idx_vital_time` (`recorded_at`),
  KEY `idx_vital_flagged` (`is_flagged`),
  CONSTRAINT `fk_vital_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 5. vital_thresholds — Normal/critical ranges per vital type
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `vital_thresholds` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `vital_type` VARCHAR(50) NOT NULL COMMENT 'bp_systolic, bp_diastolic, pulse_rate, temperature, etc.',
  `min_normal` DECIMAL(6,1) NOT NULL,
  `max_normal` DECIMAL(6,1) NOT NULL,
  `critical_low` DECIMAL(6,1) DEFAULT NULL,
  `critical_high` DECIMAL(6,1) DEFAULT NULL,
  `unit` VARCHAR(20) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL COMMENT 'user_id of admin/doctor',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vital_type` (`vital_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default thresholds
INSERT INTO `vital_thresholds` (`vital_type`,`min_normal`,`max_normal`,`critical_low`,`critical_high`,`unit`) VALUES
('bp_systolic',    90, 140,   70, 180, 'mmHg'),
('bp_diastolic',   60,  90,   40, 120, 'mmHg'),
('pulse_rate',     60, 100,   40, 150, 'bpm'),
('temperature',  36.1,37.2, 35.0,39.5, '°C'),
('oxygen_saturation', 95,100, 90, 100, '%'),
('respiratory_rate', 12, 20,   8,  30, 'breaths/min'),
('blood_glucose',  70, 140,   50, 400, 'mg/dL'),
('pain_level',      0,   3,    0,   8, 'scale 0-10');

-- ════════════════════════════════════════════════════════════
-- 6. medication_administration — Medication admin records
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `medication_administration` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `prescription_id` INT DEFAULT NULL,
  `prescription_item_id` INT DEFAULT NULL,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `medicine_name` VARCHAR(255) NOT NULL,
  `dosage` VARCHAR(100) DEFAULT NULL,
  `route` VARCHAR(50) DEFAULT NULL COMMENT 'oral, IV, IM, SC, topical, etc.',
  `scheduled_time` DATETIME DEFAULT NULL,
  `administered_at` DATETIME DEFAULT NULL,
  `status` ENUM('Pending','Administered','Missed','Refused','Held','Late') DEFAULT 'Pending',
  `reason_not_given` TEXT DEFAULT NULL COMMENT 'For missed/refused/held',
  `notes` TEXT DEFAULT NULL,
  `verified_by` ENUM('Barcode','Manual','Double-Check') DEFAULT 'Manual',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medadmin_patient` (`patient_id`),
  KEY `idx_medadmin_nurse` (`nurse_id`),
  KEY `idx_medadmin_status` (`status`),
  KEY `idx_medadmin_time` (`scheduled_time`),
  CONSTRAINT `fk_medadmin_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 7. medication_schedules — Recurring medication timetables
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `medication_schedules` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `prescription_item_id` INT DEFAULT NULL,
  `patient_id` INT NOT NULL,
  `nurse_id_assigned` INT DEFAULT NULL,
  `medicine_name` VARCHAR(255) NOT NULL,
  `frequency` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. TDS, BD, OD, QID',
  `scheduled_times` JSON DEFAULT NULL COMMENT '["08:00","14:00","20:00"]',
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Completed','Cancelled','Paused') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medsched_patient` (`patient_id`),
  KEY `idx_medsched_nurse` (`nurse_id_assigned`),
  KEY `idx_medsched_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 8. nursing_notes — Shift notes and observations
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nursing_notes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `shift_id` INT DEFAULT NULL,
  `note_type` ENUM('General','Observation','Wound','Behavior','Incident','Handoff','Pain','Assessment') DEFAULT 'General',
  `note_content` TEXT NOT NULL,
  `attachments` JSON DEFAULT NULL COMMENT '[{"file":"path","name":"filename"}]',
  `is_locked` TINYINT(1) DEFAULT 0 COMMENT 'Locked after shift ends',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `locked_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_note_nurse` (`nurse_id`),
  KEY `idx_note_patient` (`patient_id`),
  KEY `idx_note_shift` (`shift_id`),
  KEY `idx_note_type` (`note_type`),
  CONSTRAINT `fk_note_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 9. wound_care_records — Wound management tracking
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `wound_care_records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `wound_location` VARCHAR(200) NOT NULL,
  `wound_type` VARCHAR(100) DEFAULT NULL COMMENT 'surgical, pressure, laceration, burn, etc.',
  `wound_description` TEXT DEFAULT NULL,
  `wound_images` JSON DEFAULT NULL COMMENT '["path1.jpg","path2.jpg"]',
  `wound_size_cm` VARCHAR(50) DEFAULT NULL COMMENT 'LxWxD',
  `care_provided` TEXT DEFAULT NULL,
  `dressing_type` VARCHAR(200) DEFAULT NULL,
  `next_care_due` DATETIME DEFAULT NULL,
  `healing_status` ENUM('Improving','Stable','Worsening','Healed') DEFAULT 'Stable',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wound_patient` (`patient_id`),
  KEY `idx_wound_nurse` (`nurse_id`),
  CONSTRAINT `fk_wound_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 10. shift_handover — End-of-shift handover reports
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `shift_handover` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `outgoing_nurse_id` INT NOT NULL,
  `incoming_nurse_id` INT DEFAULT NULL,
  `shift_id` INT DEFAULT NULL,
  `ward` VARCHAR(200) DEFAULT NULL,
  `patient_summaries` JSON DEFAULT NULL COMMENT '[{"patient_id":1,"name":"...","status":"...","notes":"..."}]',
  `pending_tasks` JSON DEFAULT NULL COMMENT '[{"task":"...","priority":"High","patient":"..."}]',
  `critical_patients_noted` TEXT DEFAULT NULL,
  `handover_notes` TEXT DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `acknowledged` TINYINT(1) DEFAULT 0,
  `acknowledged_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ho_outgoing` (`outgoing_nurse_id`),
  KEY `idx_ho_incoming` (`incoming_nurse_id`),
  KEY `idx_ho_shift` (`shift_id`),
  CONSTRAINT `fk_ho_outgoing` FOREIGN KEY (`outgoing_nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 11. iv_fluid_records — IV infusion tracking
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `iv_fluid_records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `fluid_type` VARCHAR(200) NOT NULL COMMENT 'Normal Saline, Ringers Lactate, D5W, etc.',
  `additives` VARCHAR(200) DEFAULT NULL COMMENT 'e.g. KCl 20mEq',
  `volume_ordered_ml` INT NOT NULL,
  `volume_infused_ml` INT DEFAULT 0,
  `infusion_rate_ml_hr` DECIMAL(6,1) DEFAULT NULL,
  `start_time` DATETIME DEFAULT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `status` ENUM('Running','Completed','Paused','Stopped','Pending') DEFAULT 'Pending',
  `alert_sent` TINYINT(1) DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iv_patient` (`patient_id`),
  KEY `idx_iv_nurse` (`nurse_id`),
  KEY `idx_iv_status` (`status`),
  CONSTRAINT `fk_iv_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 12. fluid_balance — Daily intake/output tracking
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `fluid_balance` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `record_date` DATE NOT NULL,
  `total_intake_ml` INT DEFAULT 0,
  `total_output_ml` INT DEFAULT 0,
  `net_balance_ml` INT DEFAULT 0 COMMENT 'intake - output',
  `intake_sources` JSON DEFAULT NULL COMMENT '{"oral":500,"iv":1000,"blood":0}',
  `output_sources` JSON DEFAULT NULL COMMENT '{"urine":800,"vomit":0,"drain":100}',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fb_patient` (`patient_id`),
  KEY `idx_fb_date` (`record_date`),
  UNIQUE KEY `uk_fb_patient_date` (`patient_id`, `record_date`),
  CONSTRAINT `fk_fb_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 13. bed_transfers — Patient bed/ward transfers
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `bed_transfers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `from_bed_id` INT DEFAULT NULL,
  `to_bed_id` INT DEFAULT NULL,
  `from_ward` VARCHAR(200) DEFAULT NULL,
  `to_ward` VARCHAR(200) DEFAULT NULL,
  `transfer_reason` TEXT DEFAULT NULL,
  `transfer_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `authorized_by` INT DEFAULT NULL COMMENT 'doctor user_id',
  `status` ENUM('Requested','Approved','Completed','Rejected') DEFAULT 'Requested',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bt_patient` (`patient_id`),
  KEY `idx_bt_nurse` (`nurse_id`),
  KEY `idx_bt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 14. isolation_records — Patient isolation management
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `isolation_records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `isolation_type` ENUM('Contact','Droplet','Airborne','Protective','Combined') NOT NULL,
  `reason` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `precautions` JSON DEFAULT NULL COMMENT '["gown","gloves","N95","face_shield"]',
  `doctor_ordered_by` INT DEFAULT NULL COMMENT 'doctor user_id',
  `status` ENUM('Active','Lifted','Modified') DEFAULT 'Active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iso_patient` (`patient_id`),
  KEY `idx_iso_status` (`status`),
  CONSTRAINT `fk_iso_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 15. emergency_alerts — Code blue / rapid response
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `emergency_alerts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `patient_id` INT DEFAULT NULL,
  `alert_type` ENUM('Code Blue','Rapid Response','Fall','Fire','General Emergency','Security') NOT NULL,
  `severity` ENUM('Critical','High','Medium') DEFAULT 'High',
  `location` VARCHAR(200) DEFAULT NULL COMMENT 'Ward/Bed',
  `message` TEXT DEFAULT NULL,
  `notified_doctors` JSON DEFAULT NULL COMMENT '[1,5,12]',
  `status` ENUM('Active','Responded','Resolved','False Alarm') DEFAULT 'Active',
  `triggered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `resolved_by` INT DEFAULT NULL COMMENT 'user_id',
  `resolution_notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ea_nurse` (`nurse_id`),
  KEY `idx_ea_status` (`status`),
  KEY `idx_ea_severity` (`severity`),
  CONSTRAINT `fk_ea_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 16. nurse_doctor_messages — Direct messaging
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_doctor_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL COMMENT 'user_id',
  `sender_role` ENUM('Nurse','Doctor','Admin') NOT NULL,
  `receiver_id` INT NOT NULL COMMENT 'user_id',
  `receiver_role` ENUM('Nurse','Doctor','Admin') NOT NULL,
  `patient_id` INT DEFAULT NULL COMMENT 'Optional context',
  `subject` VARCHAR(300) DEFAULT NULL,
  `message_content` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `is_urgent` TINYINT(1) DEFAULT 0,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  KEY `idx_msg_read` (`is_read`),
  KEY `idx_msg_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 17. patient_education — Health education records
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `patient_education` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `education_topic` VARCHAR(300) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL COMMENT 'Medication, Diet, Wound Care, Exercise, Disease Management',
  `method` ENUM('Verbal','Written','Demonstration','Video','Combination') DEFAULT 'Verbal',
  `materials_provided` JSON DEFAULT NULL COMMENT '["pamphlet.pdf","video_link"]',
  `understanding_level` ENUM('Good','Fair','Poor') DEFAULT 'Good',
  `requires_follow_up` TINYINT(1) DEFAULT 0,
  `follow_up_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `recorded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edu_patient` (`patient_id`),
  KEY `idx_edu_nurse` (`nurse_id`),
  CONSTRAINT `fk_edu_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 18. discharge_instructions — Patient discharge prep
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `discharge_instructions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `nurse_id` INT NOT NULL,
  `instruction_content` TEXT NOT NULL,
  `medication_instructions` TEXT DEFAULT NULL,
  `follow_up_appointments` TEXT DEFAULT NULL,
  `warning_signs` TEXT DEFAULT NULL COMMENT 'When to return to hospital',
  `documents_uploaded` JSON DEFAULT NULL COMMENT '["discharge_summary.pdf"]',
  `given_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `patient_acknowledged` TINYINT(1) DEFAULT 0,
  `acknowledged_at` DATETIME DEFAULT NULL,
  `witness_name` VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_di_patient` (`patient_id`),
  KEY `idx_di_nurse` (`nurse_id`),
  CONSTRAINT `fk_di_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 19. nurse_notifications — Nurse-specific notification hub
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('Task','Vital Alert','Medication Reminder','Emergency','Doctor Message','Shift','System','Patient Update') DEFAULT 'System',
  `is_read` TINYINT(1) DEFAULT 0,
  `related_module` VARCHAR(100) DEFAULT NULL COMMENT 'vitals, tasks, medications, etc.',
  `related_id` INT DEFAULT NULL COMMENT 'ID in the related module',
  `action_url` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nn_nurse` (`nurse_id`),
  KEY `idx_nn_read` (`is_read`),
  KEY `idx_nn_type` (`type`),
  CONSTRAINT `fk_nn_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 20. nurse_settings — Per-nurse preferences
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `notification_preferences` JSON DEFAULT NULL COMMENT '{"tasks":true,"vitals":true,"meds":true,"emergency":true}',
  `theme_preference` ENUM('light','dark') DEFAULT 'light',
  `language` VARCHAR(50) DEFAULT 'English',
  `alert_sound_enabled` TINYINT(1) DEFAULT 1,
  `auto_refresh_interval` INT DEFAULT 30 COMMENT 'seconds',
  `preferred_channel` ENUM('dashboard','email','sms','all') DEFAULT 'dashboard',
  `notif_new_task` TINYINT(1) DEFAULT 1,
  `notif_vital_alert` TINYINT(1) DEFAULT 1,
  `notif_medication` TINYINT(1) DEFAULT 1,
  `notif_emergency` TINYINT(1) DEFAULT 1,
  `notif_doctor_msg` TINYINT(1) DEFAULT 1,
  `notif_shift_change` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ns_nurse` (`nurse_id`),
  CONSTRAINT `fk_ns_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 21. nurse_profile_completeness — Completion tracker
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_profile_completeness` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `personal_info` TINYINT(1) DEFAULT 0,
  `professional_profile` TINYINT(1) DEFAULT 0,
  `qualifications` TINYINT(1) DEFAULT 0,
  `documents_uploaded` TINYINT(1) DEFAULT 0,
  `photo_uploaded` TINYINT(1) DEFAULT 0,
  `security_setup` TINYINT(1) DEFAULT 0,
  `overall_pct` INT DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_npc_nurse` (`nurse_id`),
  CONSTRAINT `fk_npc_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 22. nurse_qualifications — Degrees and training
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_qualifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `degree_name` VARCHAR(200) NOT NULL,
  `institution` VARCHAR(200) NOT NULL,
  `year_awarded` INT DEFAULT NULL,
  `cert_file_path` VARCHAR(500) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nq_nurse` (`nurse_id`),
  CONSTRAINT `fk_nq_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 23. nurse_certifications — Professional certifications
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_certifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `cert_name` VARCHAR(200) NOT NULL,
  `issuing_body` VARCHAR(200) DEFAULT NULL,
  `issue_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `cert_file_path` VARCHAR(500) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nc_nurse` (`nurse_id`),
  KEY `idx_nc_expiry` (`expiry_date`),
  CONSTRAINT `fk_nc_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 24. nurse_documents — General document uploads
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_documents` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `file_name` VARCHAR(300) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT DEFAULT NULL COMMENT 'bytes',
  `description` VARCHAR(500) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nd_nurse` (`nurse_id`),
  CONSTRAINT `fk_nd_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 25. nurse_sessions — Active login sessions
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `device_info` VARCHAR(300) DEFAULT NULL,
  `browser` VARCHAR(200) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_active` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_current` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_nsess_nurse` (`nurse_id`),
  CONSTRAINT `fk_nsess_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 26. nurse_activity_log — Audit trail
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `nurse_activity_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nurse_id` INT NOT NULL,
  `action_type` VARCHAR(100) DEFAULT NULL COMMENT 'login, update, create, delete, etc.',
  `action_description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `device` VARCHAR(300) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nal_nurse` (`nurse_id`),
  KEY `idx_nal_time` (`created_at`),
  CONSTRAINT `fk_nal_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- ALTER: Add 'nurse' to users.user_role enum
-- ════════════════════════════════════════════════════════════
ALTER TABLE `users`
  MODIFY COLUMN `user_role` ENUM('admin','doctor','patient','staff','pharmacist','nurse') NOT NULL DEFAULT 'patient';

-- ════════════════════════════════════════════════════════════
-- ALTER: Add nurse-related columns to bed_assignments
-- ════════════════════════════════════════════════════════════
-- Check and add columns conditionally (MySQL 9 compatible)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema='rmu_medical_sickbay' AND table_name='bed_assignments' AND column_name='assigned_nurse_id');
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `bed_assignments` ADD COLUMN `assigned_nurse_id` INT DEFAULT NULL AFTER `bed_id`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists2 = (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema='rmu_medical_sickbay' AND table_name='bed_assignments' AND column_name='attending_nurse_notes');
SET @sql2 = IF(@col_exists2 = 0,
  'ALTER TABLE `bed_assignments` ADD COLUMN `attending_nurse_notes` TEXT DEFAULT NULL AFTER `reason`',
  'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- ════════════════════════════════════════════════════════════
-- Seed: Test nurse user (for development)
-- ════════════════════════════════════════════════════════════
INSERT INTO `users` (`user_name`,`email`,`password`,`user_role`,`name`,`phone`,`is_active`,`is_verified`)
VALUES ('nurse_test','nurse@rmu.edu.gh','$2y$12$LJ3m5bHpXQ8e9Y1f8g5vGuQzW7RjE5cUvNjGfkeT8x5QfPAcjDSmK','nurse','Test Nurse','0201234567',1,1)
ON DUPLICATE KEY UPDATE `user_role`='nurse';

SET @nurse_uid = (SELECT id FROM users WHERE user_name='nurse_test' LIMIT 1);

INSERT INTO `nurses` (`user_id`,`nurse_id`,`full_name`,`email`,`phone`,`department`,`designation`,`shift_type`,`status`)
VALUES (@nurse_uid,'NRS-001','Test Nurse','nurse@rmu.edu.gh','0201234567','Nursing','Staff Nurse','Morning','Active')
ON DUPLICATE KEY UPDATE `full_name`='Test Nurse';

SET @nurse_pk = (SELECT id FROM nurses WHERE user_id=@nurse_uid LIMIT 1);

INSERT INTO `nurse_settings` (`nurse_id`) VALUES (@nurse_pk)
ON DUPLICATE KEY UPDATE `nurse_id`=@nurse_pk;

INSERT INTO `nurse_profile_completeness` (`nurse_id`) VALUES (@nurse_pk)
ON DUPLICATE KEY UPDATE `nurse_id`=@nurse_pk;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════
-- MIGRATION COMPLETE — 26 tables + 2 ALTER + seed data
-- ════════════════════════════════════════════════════════════
