-- ============================================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- NURSE MODULE — COMPLETE TABLE MIGRATION (v1.1 — idempotent)
-- ============================================================
-- Version     : 1.1
-- Created     : 2026-03-19
-- Compatible  : MySQL 8+, MySQL 9+, MariaDB 10.5+
-- Strategy    : DROP nurse tables in reverse FK order first,
--               then CREATE fresh. Existing system tables are
--               only ALTERed (never dropped).
-- ============================================================

USE `rmu_medical_sickbay`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STEP 0: Drop any previously-created nurse tables (clean state)
--         Reverse dependency order to avoid FK errors.
-- ============================================================
DROP TABLE IF EXISTS `nurse_activity_log`;
DROP TABLE IF EXISTS `nurse_sessions`;
DROP TABLE IF EXISTS `nurse_documents`;
DROP TABLE IF EXISTS `nurse_certifications`;
DROP TABLE IF EXISTS `nurse_qualifications`;
DROP TABLE IF EXISTS `nurse_profile_completeness`;
DROP TABLE IF EXISTS `nurse_settings`;
DROP TABLE IF EXISTS `nurse_notifications`;
DROP TABLE IF EXISTS `discharge_instructions`;
DROP TABLE IF EXISTS `patient_education`;
DROP TABLE IF EXISTS `nurse_doctor_messages`;
DROP TABLE IF EXISTS `emergency_alerts`;
DROP TABLE IF EXISTS `isolation_records`;
DROP TABLE IF EXISTS `bed_transfers`;
DROP TABLE IF EXISTS `fluid_balance`;
DROP TABLE IF EXISTS `iv_fluid_records`;
DROP TABLE IF EXISTS `shift_handover`;
DROP TABLE IF EXISTS `wound_care_records`;
DROP TABLE IF EXISTS `nursing_notes`;
DROP TABLE IF EXISTS `medication_schedules`;
DROP TABLE IF EXISTS `medication_administration`;
DROP TABLE IF EXISTS `vital_thresholds`;
DROP TABLE IF EXISTS `patient_vitals`;
DROP TABLE IF EXISTS `nurse_tasks`;
DROP TABLE IF EXISTS `nurse_shifts`;
DROP TABLE IF EXISTS `nurses`;


-- ============================================================
-- TABLE 1: nurses
-- Core nurse profile — linked to the shared users table
-- ============================================================
CREATE TABLE `nurses` (
    `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
    `nurse_id`              VARCHAR(50)   NOT NULL UNIQUE COMMENT 'e.g. NRS-001',
    `user_id`               INT(11)       NOT NULL COMMENT 'FK → users.id',
    `full_name`             VARCHAR(200)  NOT NULL,
    `date_of_birth`         DATE          DEFAULT NULL,
    `gender`                ENUM('Male','Female','Other') NOT NULL DEFAULT 'Female',
    `nationality`           VARCHAR(100)  DEFAULT NULL,
    `phone`                 VARCHAR(20)   DEFAULT NULL,
    `email`                 VARCHAR(150)  DEFAULT NULL,
    `address`               TEXT          DEFAULT NULL,
    `profile_photo`         VARCHAR(255)  DEFAULT 'default-avatar.png',
    `license_number`        VARCHAR(100)  DEFAULT NULL,
    `license_expiry`        DATE          DEFAULT NULL,
    `specialization`        VARCHAR(200)  DEFAULT NULL COMMENT 'Pediatric, ICU, Surgical, etc.',
    `department_id`         INT(11)       DEFAULT NULL,
    `designation`           VARCHAR(200)  DEFAULT NULL COMMENT 'Head Nurse, Staff Nurse, Senior Nurse',
    `years_of_experience`   INT(11)       NOT NULL DEFAULT 0,
    `shift_type`            ENUM('Morning','Afternoon','Night','Rotating') DEFAULT 'Morning',
    `status`                ENUM('Active','Inactive','On Leave','Suspended') DEFAULT 'Active',
    `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_nurse_license` (`license_number`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    KEY `idx_nurse_id`     (`nurse_id`),
    KEY `idx_nurse_user`   (`user_id`),
    KEY `idx_nurse_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nurse profiles linked to shared users table';


-- ============================================================
-- TABLE 2: nurse_shifts
-- ============================================================
CREATE TABLE `nurse_shifts` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `shift_id`            VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`            INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `shift_type`          ENUM('Morning','Afternoon','Night') NOT NULL DEFAULT 'Morning',
    `shift_date`          DATE         NOT NULL,
    `start_time`          TIME         NOT NULL,
    `end_time`            TIME         NOT NULL,
    `ward_assigned`       VARCHAR(150) DEFAULT NULL,
    `status`              ENUM('Scheduled','Active','Completed','Missed') DEFAULT 'Scheduled',
    `handover_submitted`  TINYINT(1)   NOT NULL DEFAULT 0,
    `notes`               TEXT         DEFAULT NULL,
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_ns_shift_id` (`shift_id`),
    KEY `idx_ns_nurse`    (`nurse_id`),
    KEY `idx_ns_date`     (`shift_date`),
    KEY `idx_ns_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nurse shift schedule and status tracking';


-- ============================================================
-- TABLE 3: nurse_tasks
-- ============================================================
CREATE TABLE `nurse_tasks` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `task_id`           VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`          INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `assigned_by_id`    INT(11)      NOT NULL COMMENT 'FK → users.id',
    `assigned_by_role`  ENUM('doctor','admin','nurse') DEFAULT 'doctor',
    `patient_id`        INT(11)      DEFAULT NULL COMMENT 'FK → patients.id',
    `task_title`        VARCHAR(300) NOT NULL,
    `task_description`  TEXT         DEFAULT NULL,
    `priority`          ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
    `due_time`          DATETIME     DEFAULT NULL,
    `status`            ENUM('Pending','In Progress','Completed','Overdue','Cancelled') DEFAULT 'Pending',
    `completion_notes`  TEXT         DEFAULT NULL,
    `completed_at`      DATETIME     DEFAULT NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`)       REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by_id`) REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`)     REFERENCES `patients`(`id`) ON DELETE SET NULL,
    KEY `idx_nt_task_id`  (`task_id`),
    KEY `idx_nt_nurse`    (`nurse_id`),
    KEY `idx_nt_status`   (`status`),
    KEY `idx_nt_priority` (`priority`),
    KEY `idx_nt_due`      (`due_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Task assignments from doctors/admins to nurses';


-- ============================================================
-- TABLE 4: patient_vitals
-- ============================================================
CREATE TABLE `patient_vitals` (
    `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
    `vital_id`              VARCHAR(50)   NOT NULL UNIQUE,
    `patient_id`            INT(11)       NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`              INT(11)       NOT NULL COMMENT 'FK → nurses.id',
    `recorded_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bp_systolic`           DECIMAL(5,1)  DEFAULT NULL COMMENT 'mmHg',
    `bp_diastolic`          DECIMAL(5,1)  DEFAULT NULL COMMENT 'mmHg',
    `pulse_rate`            DECIMAL(5,1)  DEFAULT NULL COMMENT 'bpm',
    `temperature`           DECIMAL(4,1)  DEFAULT NULL COMMENT 'Celsius',
    `oxygen_saturation`     DECIMAL(4,1)  DEFAULT NULL COMMENT 'SpO2 %',
    `respiratory_rate`      DECIMAL(4,1)  DEFAULT NULL COMMENT 'breaths/min',
    `blood_glucose`         DECIMAL(6,1)  DEFAULT NULL COMMENT 'mg/dL',
    `weight`                DECIMAL(5,1)  DEFAULT NULL COMMENT 'kg',
    `height`                DECIMAL(5,1)  DEFAULT NULL COMMENT 'cm',
    `bmi`                   DECIMAL(4,1)  DEFAULT NULL COMMENT 'Auto-calculated',
    `notes`                 TEXT          DEFAULT NULL,
    `is_flagged`            TINYINT(1)    NOT NULL DEFAULT 0,
    `flag_reason`           VARCHAR(500)  DEFAULT NULL,
    `doctor_notified`       TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_pv_vital_id`  (`vital_id`),
    KEY `idx_pv_patient`   (`patient_id`),
    KEY `idx_pv_nurse`     (`nurse_id`),
    KEY `idx_pv_recorded`  (`recorded_at`),
    KEY `idx_pv_flagged`   (`is_flagged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient vital signs recorded by nurses with auto-flagging';


-- ============================================================
-- TABLE 5: vital_thresholds
-- ============================================================
CREATE TABLE `vital_thresholds` (
    `id`             INT(11)       NOT NULL AUTO_INCREMENT,
    `vital_type`     VARCHAR(100)  NOT NULL UNIQUE COMMENT 'bp_systolic, temperature, pulse_rate…',
    `display_name`   VARCHAR(150)  NOT NULL,
    `unit`           VARCHAR(30)   DEFAULT NULL,
    `min_normal`     DECIMAL(8,2)  DEFAULT NULL,
    `max_normal`     DECIMAL(8,2)  DEFAULT NULL,
    `critical_low`   DECIMAL(8,2)  DEFAULT NULL,
    `critical_high`  DECIMAL(8,2)  DEFAULT NULL,
    `updated_by`     INT(11)       DEFAULT NULL COMMENT 'FK → users.id',
    `updated_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    KEY `idx_vt_type` (`vital_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Normal and critical value ranges for each vital sign type';

-- Seed default thresholds
INSERT INTO `vital_thresholds`
    (`vital_type`,`display_name`,`unit`,`min_normal`,`max_normal`,`critical_low`,`critical_high`) VALUES
    ('bp_systolic',      'Blood Pressure (Systolic)',  'mmHg',       90,   140,  70,  180),
    ('bp_diastolic',     'Blood Pressure (Diastolic)', 'mmHg',       60,    90,  40,  120),
    ('pulse_rate',       'Pulse Rate',                 'bpm',        60,   100,  40,  150),
    ('temperature',      'Temperature',                'C',         36.1,  37.2, 35.0, 39.5),
    ('oxygen_saturation','Oxygen Saturation (SpO2)',   '%',          95,   100,  88,  100),
    ('respiratory_rate', 'Respiratory Rate',           'breaths/min',12,    20,   8,   30),
    ('blood_glucose',    'Blood Glucose',              'mg/dL',      70,   140,  50,  400),
    ('bmi',              'Body Mass Index',            'kg/m2',     18.5, 24.9, 15.0, 40.0);


-- ============================================================
-- TABLE 6: medication_administration
-- ============================================================
CREATE TABLE `medication_administration` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `admin_id`            VARCHAR(50)  NOT NULL UNIQUE COMMENT 'e.g. MED-ADM-001',
    `prescription_id`     INT(11)      DEFAULT NULL COMMENT 'FK → prescriptions.id',
    `patient_id`          INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`            INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `medicine_name`       VARCHAR(200) NOT NULL,
    `dosage`              VARCHAR(100) NOT NULL,
    `route`               VARCHAR(100) DEFAULT NULL COMMENT 'Oral, IV, IM, SQ, etc.',
    `scheduled_time`      DATETIME     NOT NULL,
    `administered_at`     DATETIME     DEFAULT NULL,
    `status`              ENUM('Pending','Administered','Missed','Refused','Held','PRN') DEFAULT 'Pending',
    `notes`               TEXT         DEFAULT NULL,
    `verification_method` ENUM('Barcode','Manual','eMAR') DEFAULT 'Manual',
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`patient_id`)      REFERENCES `patients`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)        REFERENCES `nurses`(`id`)        ON DELETE CASCADE,
    KEY `idx_ma_admin_id`     (`admin_id`),
    KEY `idx_ma_prescription` (`prescription_id`),
    KEY `idx_ma_patient`      (`patient_id`),
    KEY `idx_ma_nurse`        (`nurse_id`),
    KEY `idx_ma_status`       (`status`),
    KEY `idx_ma_scheduled`    (`scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Medication administration records (5 Rights compliance)';


-- ============================================================
-- TABLE 7: medication_schedules
-- ============================================================
CREATE TABLE `medication_schedules` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `schedule_id`       VARCHAR(50)  NOT NULL UNIQUE,
    `prescription_id`   INT(11)      DEFAULT NULL COMMENT 'FK → prescriptions.id',
    `patient_id`        INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`          INT(11)      DEFAULT NULL COMMENT 'FK → nurses.id',
    `medicine_name`     VARCHAR(200) NOT NULL,
    `dosage`            VARCHAR(100) NOT NULL,
    `frequency`         VARCHAR(100) NOT NULL COMMENT 'Once Daily, BD, TDS, QID, PRN',
    `scheduled_times`   JSON         DEFAULT NULL COMMENT '["08:00","14:00","20:00"]',
    `route`             VARCHAR(100) DEFAULT NULL,
    `start_date`        DATE         NOT NULL,
    `end_date`          DATE         DEFAULT NULL,
    `status`            ENUM('Active','Completed','Cancelled','On Hold') DEFAULT 'Active',
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`patient_id`)      REFERENCES `patients`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)        REFERENCES `nurses`(`id`)        ON DELETE SET NULL,
    KEY `idx_ms_schedule_id` (`schedule_id`),
    KEY `idx_ms_patient`     (`patient_id`),
    KEY `idx_ms_nurse`       (`nurse_id`),
    KEY `idx_ms_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Medication administration schedules from prescriptions';


-- ============================================================
-- TABLE 8: nursing_notes
-- ============================================================
CREATE TABLE `nursing_notes` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `note_id`      VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`     INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `patient_id`   INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `shift_id`     INT(11)      DEFAULT NULL COMMENT 'FK → nurse_shifts.id',
    `note_type`    ENUM('General','Observation','Wound Care','Behavior','Incident','Handover','Assessment') DEFAULT 'General',
    `note_content` TEXT         NOT NULL,
    `attachments`  JSON         DEFAULT NULL COMMENT 'Array of file paths',
    `is_locked`    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Locked after shift ends',
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `locked_at`    DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`)     ON DELETE CASCADE,
    FOREIGN KEY (`shift_id`)   REFERENCES `nurse_shifts`(`id`) ON DELETE SET NULL,
    KEY `idx_nn_note_id` (`note_id`),
    KEY `idx_nn_nurse`   (`nurse_id`),
    KEY `idx_nn_patient` (`patient_id`),
    KEY `idx_nn_type`    (`note_type`),
    KEY `idx_nn_locked`  (`is_locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nursing clinical notes (auto-locked after shift ends)';


-- ============================================================
-- TABLE 9: wound_care_records
-- ============================================================
CREATE TABLE `wound_care_records` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `record_id`           VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`          INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`            INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `wound_location`      VARCHAR(300) NOT NULL,
    `wound_description`   TEXT         DEFAULT NULL,
    `wound_images`        JSON         DEFAULT NULL COMMENT 'Array of image file paths',
    `care_provided`       TEXT         DEFAULT NULL,
    `dressing_type`       VARCHAR(200) DEFAULT NULL,
    `wound_size`          VARCHAR(100) DEFAULT NULL COMMENT 'e.g. 3cm x 2cm',
    `wound_stage`         VARCHAR(50)  DEFAULT NULL COMMENT 'Stage I-IV for pressure ulcers',
    `next_care_due`       DATETIME     DEFAULT NULL,
    `notes`               TEXT         DEFAULT NULL,
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_wcr_record_id` (`record_id`),
    KEY `idx_wcr_patient`   (`patient_id`),
    KEY `idx_wcr_nurse`     (`nurse_id`),
    KEY `idx_wcr_due`       (`next_care_due`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wound assessment and care records with image support';


-- ============================================================
-- TABLE 10: shift_handover
-- ============================================================
CREATE TABLE `shift_handover` (
    `id`                       INT(11)      NOT NULL AUTO_INCREMENT,
    `handover_id`              VARCHAR(50)  NOT NULL UNIQUE,
    `outgoing_nurse_id`        INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `incoming_nurse_id`        INT(11)      DEFAULT NULL COMMENT 'FK → nurses.id',
    `shift_id`                 INT(11)      NOT NULL COMMENT 'FK → nurse_shifts.id',
    `ward`                     VARCHAR(150) DEFAULT NULL,
    `patient_summaries`        JSON         DEFAULT NULL COMMENT 'Array of patient status objects',
    `pending_tasks`            JSON         DEFAULT NULL COMMENT 'Array of pending task objects',
    `critical_patients_noted`  TEXT         DEFAULT NULL,
    `handover_notes`           TEXT         DEFAULT NULL,
    `submitted_at`             DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `acknowledged_by_incoming` TINYINT(1)   NOT NULL DEFAULT 0,
    `acknowledged_at`          DATETIME     DEFAULT NULL,
    `created_at`               TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`outgoing_nurse_id`) REFERENCES `nurses`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`incoming_nurse_id`) REFERENCES `nurses`(`id`)       ON DELETE SET NULL,
    FOREIGN KEY (`shift_id`)          REFERENCES `nurse_shifts`(`id`) ON DELETE CASCADE,
    KEY `idx_sh_handover_id`    (`handover_id`),
    KEY `idx_sh_outgoing`       (`outgoing_nurse_id`),
    KEY `idx_sh_incoming`       (`incoming_nurse_id`),
    KEY `idx_sh_shift`          (`shift_id`),
    KEY `idx_sh_acknowledged`   (`acknowledged_by_incoming`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Formal shift handover documentation between nurses';


-- ============================================================
-- TABLE 11: iv_fluid_records
-- ============================================================
CREATE TABLE `iv_fluid_records` (
    `id`              INT(11)       NOT NULL AUTO_INCREMENT,
    `record_id`       VARCHAR(50)   NOT NULL UNIQUE,
    `patient_id`      INT(11)       NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`        INT(11)       NOT NULL COMMENT 'FK → nurses.id',
    `fluid_type`      VARCHAR(200)  NOT NULL COMMENT 'Normal Saline, D5W, RL, etc.',
    `volume_ordered`  DECIMAL(7,1)  NOT NULL COMMENT 'ml',
    `volume_infused`  DECIMAL(7,1)  NOT NULL DEFAULT 0 COMMENT 'ml',
    `infusion_rate`   DECIMAL(7,1)  DEFAULT NULL COMMENT 'ml/hr',
    `start_time`      DATETIME      NOT NULL,
    `end_time`        DATETIME      DEFAULT NULL,
    `status`          ENUM('Ordered','Running','Completed','Paused','Stopped') DEFAULT 'Ordered',
    `alert_sent`      TINYINT(1)    NOT NULL DEFAULT 0,
    `site`            VARCHAR(100)  DEFAULT NULL COMMENT 'IV insertion site',
    `notes`           TEXT          DEFAULT NULL,
    `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_ivf_record_id` (`record_id`),
    KEY `idx_ivf_patient`   (`patient_id`),
    KEY `idx_ivf_nurse`     (`nurse_id`),
    KEY `idx_ivf_status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='IV fluid orders, infusion tracking, and monitoring';


-- ============================================================
-- TABLE 12: fluid_balance
-- ============================================================
CREATE TABLE `fluid_balance` (
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `balance_id`      VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`      INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`        INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `record_date`     DATE         NOT NULL,
    `total_intake`    DECIMAL(8,1) NOT NULL DEFAULT 0 COMMENT 'ml',
    `total_output`    DECIMAL(8,1) NOT NULL DEFAULT 0 COMMENT 'ml',
    `net_balance`     DECIMAL(8,1) NOT NULL DEFAULT 0 COMMENT 'intake - output, ml',
    `intake_sources`  JSON         DEFAULT NULL COMMENT '{oral, iv, ng_tube}',
    `output_sources`  JSON         DEFAULT NULL COMMENT '{urine, drain, emesis}',
    `notes`           TEXT         DEFAULT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_fb_balance_id` (`balance_id`),
    KEY `idx_fb_patient`    (`patient_id`),
    KEY `idx_fb_date`       (`record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily fluid intake and output balance charts per patient';


-- ============================================================
-- TABLE 13: bed_transfers
-- ============================================================
CREATE TABLE `bed_transfers` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `transfer_id`      VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`       INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`         INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `from_bed_id`      INT(11)      DEFAULT NULL COMMENT 'FK → beds.id',
    `to_bed_id`        INT(11)      DEFAULT NULL COMMENT 'FK → beds.id',
    `from_ward`        VARCHAR(150) DEFAULT NULL,
    `to_ward`          VARCHAR(150) DEFAULT NULL,
    `transfer_reason`  TEXT         DEFAULT NULL,
    `transfer_date`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `authorized_by`    INT(11)      DEFAULT NULL COMMENT 'FK → doctors.id',
    `status`           ENUM('Requested','Approved','Completed','Rejected','Cancelled') DEFAULT 'Requested',
    `notes`            TEXT         DEFAULT NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`)    REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)      REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`from_bed_id`)   REFERENCES `beds`(`id`)     ON DELETE SET NULL,
    FOREIGN KEY (`to_bed_id`)     REFERENCES `beds`(`id`)     ON DELETE SET NULL,
    FOREIGN KEY (`authorized_by`) REFERENCES `doctors`(`id`)  ON DELETE SET NULL,
    KEY `idx_bt_transfer_id` (`transfer_id`),
    KEY `idx_bt_patient`     (`patient_id`),
    KEY `idx_bt_nurse`       (`nurse_id`),
    KEY `idx_bt_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient bed and ward transfer requests and logging';


-- ============================================================
-- TABLE 14: isolation_records
-- ============================================================
CREATE TABLE `isolation_records` (
    `id`                 INT(11)      NOT NULL AUTO_INCREMENT,
    `record_id`          VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`         INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`           INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `isolation_type`     ENUM('Contact','Droplet','Airborne','Protective','Reverse') NOT NULL,
    `reason`             TEXT         NOT NULL,
    `start_date`         DATE         NOT NULL,
    `end_date`           DATE         DEFAULT NULL,
    `precautions`        JSON         DEFAULT NULL COMMENT 'Array of precaution strings',
    `doctor_ordered_by`  INT(11)      DEFAULT NULL COMMENT 'FK → doctors.id',
    `status`             ENUM('Active','Lifted','Pending Review') DEFAULT 'Active',
    `notes`              TEXT         DEFAULT NULL,
    `created_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`)      REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)        REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`doctor_ordered_by`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
    KEY `idx_ir_record_id` (`record_id`),
    KEY `idx_ir_patient`   (`patient_id`),
    KEY `idx_ir_status`    (`status`),
    KEY `idx_ir_type`      (`isolation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient isolation orders with types and precautions';


-- ============================================================
-- TABLE 15: emergency_alerts
-- ============================================================
CREATE TABLE `emergency_alerts` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `alert_id`         VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`         INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `patient_id`       INT(11)      DEFAULT NULL COMMENT 'FK → patients.id',
    `alert_type`       ENUM('Code Blue','Rapid Response','Fall','Cardiac Arrest','Fire','General Emergency','Medication Error','Security') NOT NULL,
    `severity`         ENUM('Critical','High','Medium','Low') DEFAULT 'High',
    `location`         VARCHAR(200) DEFAULT NULL COMMENT 'Ward and bed number',
    `message`          TEXT         NOT NULL,
    `notified_doctors` JSON         DEFAULT NULL COMMENT 'Array of notified doctor user IDs',
    `status`           ENUM('Active','Responded','Resolved','False Alarm') DEFAULT 'Active',
    `triggered_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `responded_at`     DATETIME     DEFAULT NULL,
    `resolved_at`      DATETIME     DEFAULT NULL,
    `resolved_by`      INT(11)      DEFAULT NULL COMMENT 'FK → users.id',
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`)    REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`)  REFERENCES `patients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL,
    KEY `idx_ea_alert_id`  (`alert_id`),
    KEY `idx_ea_nurse`     (`nurse_id`),
    KEY `idx_ea_patient`   (`patient_id`),
    KEY `idx_ea_status`    (`status`),
    KEY `idx_ea_severity`  (`severity`),
    KEY `idx_ea_triggered` (`triggered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Emergency alerts triggered by nurses (code blue, falls, etc.)';


-- ============================================================
-- TABLE 16: nurse_doctor_messages
-- ============================================================
CREATE TABLE `nurse_doctor_messages` (
    `id`              INT(11)       NOT NULL AUTO_INCREMENT,
    `message_id`      VARCHAR(50)   NOT NULL UNIQUE,
    `sender_id`       INT(11)       NOT NULL COMMENT 'FK → users.id',
    `sender_role`     ENUM('nurse','doctor','admin') NOT NULL,
    `receiver_id`     INT(11)       NOT NULL COMMENT 'FK → users.id',
    `receiver_role`   ENUM('nurse','doctor','admin') NOT NULL,
    `patient_id`      INT(11)       DEFAULT NULL COMMENT 'FK → patients.id',
    `subject`         VARCHAR(300)  DEFAULT NULL,
    `message_content` TEXT          NOT NULL,
    `is_read`         TINYINT(1)    NOT NULL DEFAULT 0,
    `sent_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at`         DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`)  REFERENCES `patients`(`id`) ON DELETE SET NULL,
    KEY `idx_ndm_msg_id`   (`message_id`),
    KEY `idx_ndm_sender`   (`sender_id`),
    KEY `idx_ndm_receiver` (`receiver_id`),
    KEY `idx_ndm_is_read`  (`is_read`),
    KEY `idx_ndm_patient`  (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Direct messaging between nurses, doctors, and admins';


-- ============================================================
-- TABLE 17: patient_education
-- ============================================================
CREATE TABLE `patient_education` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `education_id`        VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`          INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`            INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `education_topic`     VARCHAR(300) NOT NULL,
    `method`              ENUM('Verbal','Written','Demonstration','Video','Group Session') DEFAULT 'Verbal',
    `materials_provided`  JSON         DEFAULT NULL COMMENT 'Array of material descriptions/paths',
    `understanding_level` ENUM('Good','Fair','Poor','Unable to Assess') DEFAULT 'Good',
    `requires_follow_up`  TINYINT(1)   NOT NULL DEFAULT 0,
    `follow_up_notes`     TEXT         DEFAULT NULL,
    `recorded_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_pe_edu_id`  (`education_id`),
    KEY `idx_pe_patient` (`patient_id`),
    KEY `idx_pe_nurse`   (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient health education sessions recorded by nurses';


-- ============================================================
-- TABLE 18: discharge_instructions
-- ============================================================
CREATE TABLE `discharge_instructions` (
    `id`                    INT(11)      NOT NULL AUTO_INCREMENT,
    `instruction_id`        VARCHAR(50)  NOT NULL UNIQUE,
    `patient_id`            INT(11)      NOT NULL COMMENT 'FK → patients.id',
    `nurse_id`              INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `instruction_content`   TEXT         NOT NULL,
    `documents_uploaded`    JSON         DEFAULT NULL COMMENT 'Array of uploaded document paths',
    `given_at`              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `patient_acknowledged`  TINYINT(1)   NOT NULL DEFAULT 0,
    `acknowledged_at`       DATETIME     DEFAULT NULL,
    `notes`                 TEXT         DEFAULT NULL,
    `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nurse_id`)   REFERENCES `nurses`(`id`)   ON DELETE CASCADE,
    KEY `idx_di_instr_id` (`instruction_id`),
    KEY `idx_di_patient`  (`patient_id`),
    KEY `idx_di_nurse`    (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Discharge instructions provided to patients by nurses';


-- ============================================================
-- TABLE 19: nurse_notifications
-- ============================================================
CREATE TABLE `nurse_notifications` (
    `id`              INT(11)       NOT NULL AUTO_INCREMENT,
    `notification_id` VARCHAR(50)   NOT NULL UNIQUE,
    `nurse_id`        INT(11)       NOT NULL COMMENT 'FK → nurses.id',
    `user_id`         INT(11)       DEFAULT NULL COMMENT 'FK → users.id',
    `title`           VARCHAR(300)  DEFAULT NULL,
    `message`         TEXT          NOT NULL,
    `type`            ENUM('Task','Vital Alert','Medication Reminder','Emergency','Doctor Message','Shift','System','Handover','General') DEFAULT 'General',
    `is_read`         TINYINT(1)    NOT NULL DEFAULT 0,
    `priority`        ENUM('low','normal','high','urgent') DEFAULT 'normal',
    `related_module`  VARCHAR(100)  DEFAULT NULL,
    `related_id`      INT(11)       DEFAULT NULL,
    `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `read_at`         DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE SET NULL,
    KEY `idx_nnotif_id`      (`notification_id`),
    KEY `idx_nnotif_nurse`   (`nurse_id`),
    KEY `idx_nnotif_is_read` (`is_read`),
    KEY `idx_nnotif_type`    (`type`),
    KEY `idx_nnotif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='In-app notifications for nurses';


-- ============================================================
-- TABLE 20: nurse_settings
-- ============================================================
CREATE TABLE `nurse_settings` (
    `id`                       INT(11)      NOT NULL AUTO_INCREMENT,
    `nurse_id`                 INT(11)      NOT NULL UNIQUE COMMENT 'FK → nurses.id',
    `notification_preferences` JSON         DEFAULT NULL,
    `theme_preference`         ENUM('light','dark','auto') DEFAULT 'light',
    `language`                 VARCHAR(10)  DEFAULT 'en',
    `alert_sound_enabled`      TINYINT(1)   NOT NULL DEFAULT 1,
    `email_notifications`      TINYINT(1)   NOT NULL DEFAULT 1,
    `updated_at`               TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_nset_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nurse-specific app settings and notification preferences';


-- ============================================================
-- TABLE 21: nurse_profile_completeness
-- ============================================================
CREATE TABLE `nurse_profile_completeness` (
    `id`                         INT(11)    NOT NULL AUTO_INCREMENT,
    `nurse_id`                   INT(11)    NOT NULL UNIQUE,
    `personal_info_complete`     TINYINT(1) NOT NULL DEFAULT 0,
    `professional_info_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `qualifications_complete`    TINYINT(1) NOT NULL DEFAULT 0,
    `documents_uploaded`         TINYINT(1) NOT NULL DEFAULT 0,
    `photo_uploaded`             TINYINT(1) NOT NULL DEFAULT 0,
    `security_setup_complete`    TINYINT(1) NOT NULL DEFAULT 0,
    `overall_percentage`         TINYINT(3) NOT NULL DEFAULT 0 COMMENT '0-100',
    `last_updated`               TIMESTAMP  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_npc_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks profile completeness percentage per nurse';


-- ============================================================
-- TABLE 22: nurse_qualifications
-- ============================================================
CREATE TABLE `nurse_qualifications` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `qualification_id` VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`         INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `degree_name`      VARCHAR(300) NOT NULL,
    `institution`      VARCHAR(300) NOT NULL,
    `year_awarded`     YEAR         DEFAULT NULL,
    `certificate_path` VARCHAR(500) DEFAULT NULL,
    `uploaded_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_nq_qual_id` (`qualification_id`),
    KEY `idx_nq_nurse`   (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nurse academic qualifications and degrees';


-- ============================================================
-- TABLE 23: nurse_certifications
-- ============================================================
CREATE TABLE `nurse_certifications` (
    `id`                 INT(11)      NOT NULL AUTO_INCREMENT,
    `certification_id`   VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`           INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `certification_name` VARCHAR(300) NOT NULL,
    `issuing_body`       VARCHAR(300) DEFAULT NULL,
    `issue_date`         DATE         DEFAULT NULL,
    `expiry_date`        DATE         DEFAULT NULL,
    `certificate_path`   VARCHAR(500) DEFAULT NULL,
    `status`             ENUM('Valid','Expired','Pending Renewal') DEFAULT 'Valid',
    `uploaded_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_nc_cert_id` (`certification_id`),
    KEY `idx_nc_nurse`   (`nurse_id`),
    KEY `idx_nc_expiry`  (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Professional nursing certifications with expiry tracking';


-- ============================================================
-- TABLE 24: nurse_documents
-- ============================================================
CREATE TABLE `nurse_documents` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `document_id`  VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`     INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `file_name`    VARCHAR(300) NOT NULL,
    `file_path`    VARCHAR(500) NOT NULL,
    `file_type`    VARCHAR(100) DEFAULT NULL COMMENT 'MIME type e.g. application/pdf',
    `file_size`    BIGINT       NOT NULL DEFAULT 0 COMMENT 'Bytes',
    `description`  VARCHAR(500) DEFAULT NULL,
    `category`     VARCHAR(100) DEFAULT NULL COMMENT 'ID, Contract, Medical, Other',
    `uploaded_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_nd_doc_id` (`document_id`),
    KEY `idx_nd_nurse`  (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='General document uploads for nurse profile';


-- ============================================================
-- TABLE 25: nurse_sessions
-- ============================================================
CREATE TABLE `nurse_sessions` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `session_id`   VARCHAR(255) NOT NULL UNIQUE,
    `nurse_id`     INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `user_id`      INT(11)      NOT NULL COMMENT 'FK → users.id',
    `device_info`  TEXT         DEFAULT NULL,
    `browser`      VARCHAR(200) DEFAULT NULL,
    `ip_address`   VARCHAR(45)  DEFAULT NULL,
    `login_time`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_active`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_current`   TINYINT(1)   NOT NULL DEFAULT 1,
    `logout_time`  DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
    KEY `idx_nses_session_id` (`session_id`),
    KEY `idx_nses_nurse`      (`nurse_id`),
    KEY `idx_nses_current`    (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Active and historical nurse login sessions';


-- ============================================================
-- TABLE 26: nurse_activity_log
-- ============================================================
CREATE TABLE `nurse_activity_log` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `log_id`     VARCHAR(50)  NOT NULL UNIQUE,
    `nurse_id`   INT(11)      NOT NULL COMMENT 'FK → nurses.id',
    `action`     VARCHAR(300) NOT NULL COMMENT 'Human-readable action',
    `module`     VARCHAR(100) DEFAULT NULL COMMENT 'vitals, medication, tasks, notes, etc.',
    `record_id`  INT(11)      DEFAULT NULL COMMENT 'Related record PK if applicable',
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `device`     VARCHAR(200) DEFAULT NULL,
    `timestamp`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`nurse_id`) REFERENCES `nurses`(`id`) ON DELETE CASCADE,
    KEY `idx_nal_log_id`  (`log_id`),
    KEY `idx_nal_nurse`   (`nurse_id`),
    KEY `idx_nal_module`  (`module`),
    KEY `idx_nal_ts`      (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Full audit trail of all nurse actions in the system';


-- ============================================================
-- ALTER EXISTING SYSTEM TABLES
-- ============================================================

-- 1. Add 'nurse' to users.user_role ENUM
ALTER TABLE `users`
    MODIFY COLUMN `user_role`
        ENUM('admin','doctor','patient','staff','pharmacist','nurse')
        NOT NULL DEFAULT 'patient';

-- 2. Add 'nurse' to user_sessions.user_role ENUM
ALTER TABLE `user_sessions`
    MODIFY COLUMN `user_role`
        ENUM('admin','doctor','patient','staff','pharmacist','nurse')
        NOT NULL;

-- 3. Add attending_nurse_id to bed_assignments (MySQL 9 safe)
DROP PROCEDURE IF EXISTS `rmu_add_col_ba_nurse`;
DELIMITER $$
CREATE PROCEDURE `rmu_add_col_ba_nurse`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'bed_assignments'
          AND COLUMN_NAME  = 'attending_nurse_id'
    ) THEN
        ALTER TABLE `bed_assignments`
            ADD COLUMN `attending_nurse_id` INT(11) DEFAULT NULL
            COMMENT 'nurses.id - nurse assigned to this patient';
    END IF;
END$$
DELIMITER ;
CALL `rmu_add_col_ba_nurse`();
DROP PROCEDURE IF EXISTS `rmu_add_col_ba_nurse`;

-- 4. Add nurse_id to medical_records (MySQL 9 safe)
DROP PROCEDURE IF EXISTS `rmu_add_col_mr_nurse`;
DELIMITER $$
CREATE PROCEDURE `rmu_add_col_mr_nurse`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'medical_records'
          AND COLUMN_NAME  = 'nurse_id'
    ) THEN
        ALTER TABLE `medical_records`
            ADD COLUMN `nurse_id` INT(11) DEFAULT NULL
            COMMENT 'nurses.id - nurse who assisted or recorded vitals';
    END IF;
END$$
DELIMITER ;
CALL `rmu_add_col_mr_nurse`();
DROP PROCEDURE IF EXISTS `rmu_add_col_mr_nurse`;

-- 5. Extend notifications.user_role ENUM (only if table exists)
DROP PROCEDURE IF EXISTS `rmu_extend_notif_role`;
DELIMITER $$
CREATE PROCEDURE `rmu_extend_notif_role`()
BEGIN
    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'notifications'
    ) THEN
        ALTER TABLE `notifications`
            MODIFY COLUMN `user_role`
                ENUM('admin','doctor','patient','staff','pharmacist','nurse')
                DEFAULT NULL;
    END IF;
END$$
DELIMITER ;
CALL `rmu_extend_notif_role`();
DROP PROCEDURE IF EXISTS `rmu_extend_notif_role`;


-- ============================================================
-- VERIFY: confirm all nurse-related tables exist
-- Run manually after migration:
-- SELECT TABLE_NAME
-- FROM   INFORMATION_SCHEMA.TABLES
-- WHERE  TABLE_SCHEMA = 'rmu_medical_sickbay'
--   AND  TABLE_NAME IN (
--   'nurses','nurse_shifts','nurse_tasks','patient_vitals',
--   'vital_thresholds','medication_administration','medication_schedules',
--   'nursing_notes','wound_care_records','shift_handover',
--   'iv_fluid_records','fluid_balance','bed_transfers','isolation_records',
--   'emergency_alerts','nurse_doctor_messages','patient_education',
--   'discharge_instructions','nurse_notifications','nurse_settings',
--   'nurse_profile_completeness','nurse_qualifications',
--   'nurse_certifications','nurse_documents','nurse_sessions',
--   'nurse_activity_log')
-- ORDER BY TABLE_NAME;
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ============================================================
-- MIGRATION COMPLETE
-- New tables created : 26
-- Altered tables     : 5
--   users            (user_role ENUM extended)
--   user_sessions    (user_role ENUM extended)
--   bed_assignments  (attending_nurse_id added)
--   medical_records  (nurse_id added)
--   notifications    (user_role ENUM extended)
-- ============================================================

-- ============================================================
-- PHASE 7: MODULE 15 (ADVANCED PROFILE EXTENSIONS)
-- ============================================================

CREATE TABLE IF NOT EXISTS nurse_professional_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    specialization VARCHAR(100),
    sub_specialization VARCHAR(100),
    department_id INT(11),
    designation VARCHAR(100),
    years_of_experience INT,
    license_number VARCHAR(100),
    license_issuing_body VARCHAR(150),
    license_expiry_date DATE,
    nursing_school VARCHAR(200),
    graduation_year INT,
    postgraduate_details TEXT,
    languages_spoken JSON,
    bio TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nurse_qualifications (
    qualification_id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    degree_name VARCHAR(150),
    institution VARCHAR(200),
    year_awarded INT,
    certificate_file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nurse_certifications (
    certification_id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    certification_name VARCHAR(150),
    issuing_organization VARCHAR(200),
    issue_date DATE,
    expiry_date DATE,
    certificate_file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nurse_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    file_size INT,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nurse_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    device_info VARCHAR(255),
    browser VARCHAR(100),
    ip_address VARCHAR(45),
    login_time DATETIME,
    last_active DATETIME,
    is_current_session TINYINT(1) DEFAULT 0,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nurse_profile_completeness (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT(11) NOT NULL,
    personal_info_complete TINYINT(1) DEFAULT 0,
    professional_profile_complete TINYINT(1) DEFAULT 0,
    qualifications_complete TINYINT(1) DEFAULT 0,
    shift_profile_complete TINYINT(1) DEFAULT 0,
    photo_uploaded TINYINT(1) DEFAULT 0,
    security_setup_complete TINYINT(1) DEFAULT 0,
    documents_uploaded TINYINT(1) DEFAULT 0,
    overall_percentage INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE
);
