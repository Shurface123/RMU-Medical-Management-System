-- ═══════════════════════════════════════════════════════════════════
-- PHASE 2: LAB TECHNICIAN DASHBOARD — Database Migration
-- 24 tables — phpMyAdmin compatible (no DELIMITER/stored procedures)
-- Run in phpMyAdmin SQL tab — safe to re-run (IF NOT EXISTS)
-- ═══════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────
-- 1. lab_technicians
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technicians` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `technician_id` VARCHAR(30) DEFAULT NULL COMMENT 'e.g. LAB-TECH-001',
  `full_name` VARCHAR(150) NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `nationality` VARCHAR(80) DEFAULT 'Ghanaian',
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `secondary_phone` VARCHAR(20) DEFAULT NULL,
  `personal_email` VARCHAR(150) DEFAULT NULL,
  `street_address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'Ghana',
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `profile_photo` VARCHAR(500) DEFAULT NULL,
  `license_number` VARCHAR(100) DEFAULT NULL,
  `license_issuing_body` VARCHAR(200) DEFAULT NULL,
  `license_expiry` DATE DEFAULT NULL,
  `specialization` VARCHAR(150) DEFAULT NULL,
  `sub_specialization` VARCHAR(200) DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `designation` VARCHAR(100) DEFAULT 'Lab Technician',
  `years_of_experience` INT DEFAULT 0,
  `bio` TEXT DEFAULT NULL,
  `languages_spoken` JSON DEFAULT NULL,
  `marital_status` VARCHAR(30) DEFAULT NULL,
  `religion` VARCHAR(50) DEFAULT NULL,
  `national_id` VARCHAR(50) DEFAULT NULL,
  `office_location` VARCHAR(100) DEFAULT NULL,
  `availability_status` ENUM('Available','Busy','On Break','Off Duty') DEFAULT 'Available',
  `two_fa_enabled` TINYINT(1) DEFAULT 0,
  `shift_preference_notes` TEXT DEFAULT NULL,
  `status` ENUM('Active','Inactive','On Leave','Suspended') DEFAULT 'Active',
  `member_since` DATE DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_lab_tech_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 2. lab_test_catalog
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_test_catalog` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `test_name` VARCHAR(200) NOT NULL,
  `test_code` VARCHAR(50) DEFAULT NULL,
  `category` ENUM('Hematology','Biochemistry','Microbiology','Immunology','Urinalysis','Histology','Radiology','Parasitology','Serology','Other') DEFAULT 'Other',
  `sample_type` ENUM('Blood','Urine','Stool','Swab','Tissue','CSF','Sputum','Other') DEFAULT 'Blood',
  `container_type` VARCHAR(100) DEFAULT NULL,
  `collection_instructions` TEXT DEFAULT NULL,
  `processing_time_hours` DECIMAL(5,1) DEFAULT 1.0,
  `normal_turnaround_hours` DECIMAL(5,1) DEFAULT 24.0,
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `requires_fasting` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_test_code` (`test_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 3. lab_test_orders
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_test_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(30) NOT NULL COMMENT 'e.g. LTO-A1B2C3D4',
  `request_id` INT DEFAULT NULL COMMENT 'FK to lab_tests.id from doctor',
  `patient_id` INT NOT NULL,
  `doctor_id` INT DEFAULT NULL,
  `technician_id` INT DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `test_catalog_id` INT DEFAULT NULL,
  `test_name` VARCHAR(200) NOT NULL,
  `urgency` ENUM('Routine','Urgent','STAT','Critical') DEFAULT 'Routine',
  `order_date` DATE NOT NULL,
  `required_by_date` DATE DEFAULT NULL,
  `clinical_notes` TEXT DEFAULT NULL,
  `diagnosis` VARCHAR(500) DEFAULT NULL,
  `order_status` ENUM('Pending','Accepted','Rejected','Sample Collected','Processing','Completed','Cancelled') DEFAULT 'Pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_technician` (`technician_id`),
  KEY `idx_status` (`order_status`),
  KEY `idx_urgency` (`urgency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 4. lab_samples
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_samples` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sample_id` VARCHAR(30) NOT NULL COMMENT 'e.g. SMP-XXXXXXXX',
  `order_id` INT NOT NULL COMMENT 'FK to lab_test_orders.id',
  `patient_id` INT NOT NULL,
  `technician_id` INT DEFAULT NULL,
  `sample_type` ENUM('Blood','Urine','Stool','Swab','Tissue','CSF','Sputum','Other') DEFAULT 'Blood',
  `sample_code` VARCHAR(50) NOT NULL COMMENT 'Unique barcode',
  `collection_date` DATE NOT NULL,
  `collection_time` TIME DEFAULT NULL,
  `collected_by` INT DEFAULT NULL COMMENT 'user_id of nurse or technician',
  `container_type` VARCHAR(100) DEFAULT NULL,
  `volume_collected` VARCHAR(50) DEFAULT NULL,
  `storage_location` VARCHAR(100) DEFAULT NULL,
  `condition_on_receipt` ENUM('Good','Haemolysed','Clotted','Insufficient','Contaminated','Lipemic') DEFAULT 'Good',
  `status` ENUM('Collected','Received','Processing','Stored','Disposed','Rejected') DEFAULT 'Collected',
  `rejection_reason` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `barcode_image_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_sample_id` (`sample_id`),
  UNIQUE KEY `uk_sample_code` (`sample_code`),
  KEY `idx_order` (`order_id`),
  KEY `idx_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 5. lab_results_v2 (new comprehensive results table)
-- The old lab_results table is kept for backward compatibility
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_results_v2` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `result_id` VARCHAR(30) NOT NULL,
  `order_id` INT NOT NULL COMMENT 'FK to lab_test_orders.id',
  `sample_id` INT DEFAULT NULL COMMENT 'FK to lab_samples.id',
  `patient_id` INT NOT NULL,
  `doctor_id` INT DEFAULT NULL,
  `technician_id` INT NOT NULL,
  `test_name` VARCHAR(200) NOT NULL,
  `result_values` JSON DEFAULT NULL COMMENT 'Supports multiple parameters',
  `unit_of_measurement` VARCHAR(50) DEFAULT NULL,
  `reference_range_min` DECIMAL(10,4) DEFAULT NULL,
  `reference_range_max` DECIMAL(10,4) DEFAULT NULL,
  `result_interpretation` ENUM('Normal','Abnormal','Critical','Inconclusive') DEFAULT 'Normal',
  `result_status` ENUM('Draft','Pending Validation','Validated','Released','Amended') DEFAULT 'Draft',
  `validated_by` INT DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `validated_at` DATETIME DEFAULT NULL,
  `released_to_doctor` TINYINT(1) DEFAULT 0,
  `released_at` DATETIME DEFAULT NULL,
  `released_to_patient` TINYINT(1) DEFAULT 0,
  `patient_release_approved_by` INT DEFAULT NULL COMMENT 'Doctor who approved',
  `report_file_path` VARCHAR(500) DEFAULT NULL,
  `technician_comments` TEXT DEFAULT NULL,
  `amended_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_result_id` (`result_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_status` (`result_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 6. lab_result_parameters
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_result_parameters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `result_id` INT NOT NULL COMMENT 'FK to lab_results_v2.id',
  `parameter_name` VARCHAR(150) NOT NULL,
  `value` VARCHAR(100) DEFAULT NULL,
  `unit` VARCHAR(50) DEFAULT NULL,
  `reference_range_min` DECIMAL(10,4) DEFAULT NULL,
  `reference_range_max` DECIMAL(10,4) DEFAULT NULL,
  `flag` ENUM('Normal','Low','High','Critical Low','Critical High') DEFAULT 'Normal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_result` (`result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 7. lab_reference_ranges
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_reference_ranges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `test_catalog_id` INT NOT NULL,
  `parameter_name` VARCHAR(150) NOT NULL,
  `gender` ENUM('Male','Female','Both') DEFAULT 'Both',
  `age_min_years` INT DEFAULT 0,
  `age_max_years` INT DEFAULT 999,
  `normal_min` DECIMAL(10,4) DEFAULT NULL,
  `normal_max` DECIMAL(10,4) DEFAULT NULL,
  `critical_low` DECIMAL(10,4) DEFAULT NULL,
  `critical_high` DECIMAL(10,4) DEFAULT NULL,
  `unit` VARCHAR(50) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_catalog` (`test_catalog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 8. lab_equipment
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_equipment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `model` VARCHAR(200) DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `manufacturer` VARCHAR(200) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(150) DEFAULT NULL,
  `purchase_date` DATE DEFAULT NULL,
  `warranty_expiry` DATE DEFAULT NULL,
  `status` ENUM('Operational','Maintenance','Calibration Due','Out of Service','Decommissioned') DEFAULT 'Operational',
  `last_calibration_date` DATE DEFAULT NULL,
  `next_calibration_date` DATE DEFAULT NULL,
  `last_maintenance_date` DATE DEFAULT NULL,
  `next_maintenance_date` DATE DEFAULT NULL,
  `assigned_technician_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 9. equipment_maintenance_log
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `equipment_maintenance_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_id` INT NOT NULL,
  `maintenance_type` ENUM('Calibration','Repair','Service','Inspection','Cleaning') DEFAULT 'Service',
  `performed_by` VARCHAR(150) DEFAULT NULL COMMENT 'Technician name or external vendor',
  `performed_by_id` INT DEFAULT NULL COMMENT 'FK to lab_technicians.id if internal',
  `performed_at` DATETIME NOT NULL,
  `next_due_date` DATE DEFAULT NULL,
  `findings` TEXT DEFAULT NULL,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `documents_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_equipment` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 10. reagent_inventory
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reagent_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `catalog_number` VARCHAR(100) DEFAULT NULL,
  `manufacturer` VARCHAR(200) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `unit` VARCHAR(50) DEFAULT 'pcs',
  `quantity_in_stock` INT DEFAULT 0,
  `reorder_level` INT DEFAULT 5,
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00,
  `expiry_date` DATE DEFAULT NULL,
  `storage_conditions` VARCHAR(200) DEFAULT NULL,
  `linked_equipment_id` INT DEFAULT NULL,
  `status` ENUM('In Stock','Low Stock','Out of Stock','Expired','Expiring Soon') DEFAULT 'In Stock',
  `batch_number` VARCHAR(100) DEFAULT NULL,
  `date_received` DATE DEFAULT NULL,
  `supplier_name` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 11. reagent_transactions
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reagent_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reagent_id` INT NOT NULL,
  `transaction_type` ENUM('Received','Used','Disposed','Adjusted') DEFAULT 'Received',
  `quantity` INT NOT NULL,
  `previous_quantity` INT DEFAULT 0,
  `new_quantity` INT DEFAULT 0,
  `performed_by` INT DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `linked_order_id` INT DEFAULT NULL COMMENT 'FK to lab_test_orders.id',
  `notes` TEXT DEFAULT NULL,
  `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_reagent` (`reagent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 12. lab_notifications
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient_id` INT NOT NULL,
  `recipient_role` VARCHAR(30) DEFAULT 'lab_technician',
  `sender_id` INT DEFAULT NULL,
  `sender_role` VARCHAR(30) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('New Order','Result Ready','Critical Value','Equipment Alert','Reagent Alert','System','Message','Quality Control') DEFAULT 'System',
  `is_read` TINYINT(1) DEFAULT 0,
  `related_module` VARCHAR(50) DEFAULT NULL,
  `related_record_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_recipient` (`recipient_id`, `is_read`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 13. lab_internal_messages
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_internal_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `sender_role` VARCHAR(30) NOT NULL,
  `receiver_id` INT NOT NULL,
  `receiver_role` VARCHAR(30) NOT NULL,
  `patient_id` INT DEFAULT NULL,
  `order_id` INT DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message_content` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `priority` ENUM('Normal','Urgent') DEFAULT 'Normal',
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME DEFAULT NULL,
  KEY `idx_receiver` (`receiver_id`, `is_read`),
  KEY `idx_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 14. lab_audit_trail
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_audit_trail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `action_type` VARCHAR(100) NOT NULL,
  `module_affected` VARCHAR(100) DEFAULT NULL,
  `record_id` INT DEFAULT NULL,
  `old_value` JSON DEFAULT NULL,
  `new_value` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `device_info` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_technician` (`technician_id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 15. lab_report_templates
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_report_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `header_content` TEXT DEFAULT NULL,
  `footer_content` TEXT DEFAULT NULL,
  `logo_path` VARCHAR(500) DEFAULT NULL,
  `includes_digital_signature` TINYINT(1) DEFAULT 0,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 16. lab_generated_reports
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_generated_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `generated_by` INT NOT NULL COMMENT 'FK to lab_technicians.id',
  `report_type` VARCHAR(100) NOT NULL,
  `parameters` JSON DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `format` ENUM('PDF','CSV','XLSX') DEFAULT 'PDF',
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 17. lab_technician_qualifications
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_qualifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `degree_name` VARCHAR(200) NOT NULL,
  `institution` VARCHAR(200) NOT NULL,
  `year_awarded` INT DEFAULT NULL,
  `certificate_file` VARCHAR(500) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 18. lab_technician_certifications
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_certifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `certification_name` VARCHAR(200) NOT NULL,
  `issuing_body` VARCHAR(200) DEFAULT NULL,
  `issue_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `certificate_file` VARCHAR(500) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 19. lab_technician_documents
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `document_type` VARCHAR(50) DEFAULT 'Other',
  `file_size` INT DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 20. lab_technician_sessions
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `device` VARCHAR(200) DEFAULT NULL,
  `browser` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_active` DATETIME DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 21. lab_technician_settings
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `notification_preferences` JSON DEFAULT NULL,
  `theme_preference` VARCHAR(20) DEFAULT 'light',
  `language` VARCHAR(10) DEFAULT 'en',
  `alert_sound_enabled` TINYINT(1) DEFAULT 1,
  `notif_new_order` TINYINT(1) DEFAULT 1,
  `notif_critical_result` TINYINT(1) DEFAULT 1,
  `notif_equipment_alert` TINYINT(1) DEFAULT 1,
  `notif_reagent_alert` TINYINT(1) DEFAULT 1,
  `notif_qc_reminder` TINYINT(1) DEFAULT 1,
  `notif_doctor_msg` TINYINT(1) DEFAULT 1,
  `notif_system` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 22. lab_technician_profile_completeness
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_technician_profile_completeness` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `personal_info` TINYINT(1) DEFAULT 0,
  `professional_profile` TINYINT(1) DEFAULT 0,
  `qualifications` TINYINT(1) DEFAULT 0,
  `documents_uploaded` TINYINT(1) DEFAULT 0,
  `photo_uploaded` TINYINT(1) DEFAULT 0,
  `security_setup` TINYINT(1) DEFAULT 0,
  `completeness_percentage` INT DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 23. lab_quality_control
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_quality_control` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `equipment_id` INT DEFAULT NULL,
  `test_catalog_id` INT DEFAULT NULL,
  `qc_date` DATE NOT NULL,
  `qc_type` ENUM('Internal','External') DEFAULT 'Internal',
  `lot_number` VARCHAR(100) DEFAULT NULL,
  `expected_range_min` DECIMAL(10,4) DEFAULT NULL,
  `expected_range_max` DECIMAL(10,4) DEFAULT NULL,
  `result_obtained` DECIMAL(10,4) DEFAULT NULL,
  `passed` TINYINT(1) DEFAULT 0,
  `corrective_action` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tech` (`technician_id`),
  KEY `idx_date` (`qc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────
-- 24. lab_workload_log
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lab_workload_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `technician_id` INT NOT NULL,
  `shift_date` DATE NOT NULL,
  `shift_type` VARCHAR(30) DEFAULT NULL,
  `total_orders_received` INT DEFAULT 0,
  `total_completed` INT DEFAULT 0,
  `total_pending` INT DEFAULT 0,
  `total_rejected` INT DEFAULT 0,
  `total_critical_results` INT DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tech_date` (`technician_id`, `shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════
-- ALTER existing lab_results table — add missing columns for
-- backward compatibility with doctor/patient dashboards
-- ═══════════════════════════════════════════════════════════════════

ALTER TABLE `lab_results` ADD COLUMN `validated_by` INT DEFAULT NULL;
ALTER TABLE `lab_results` ADD COLUMN `validated_at` DATETIME DEFAULT NULL;
ALTER TABLE `lab_results` ADD COLUMN `result_interpretation` ENUM('Normal','Abnormal','Critical','Inconclusive') DEFAULT 'Normal';
ALTER TABLE `lab_results` ADD COLUMN `amended_reason` TEXT DEFAULT NULL;
ALTER TABLE `lab_results` ADD COLUMN `reference_range_min` DECIMAL(10,4) DEFAULT NULL;
ALTER TABLE `lab_results` ADD COLUMN `reference_range_max` DECIMAL(10,4) DEFAULT NULL;

-- ═══════════════════════════════════════════════════════════════════
-- Seed lab_test_catalog with common hospital lab tests
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO `lab_test_catalog` (`test_name`, `test_code`, `category`, `sample_type`, `container_type`, `processing_time_hours`, `normal_turnaround_hours`, `price`, `requires_fasting`) VALUES
('Complete Blood Count (CBC)', 'CBC-001', 'Hematology', 'Blood', 'EDTA (Purple Top)', 1.0, 4.0, 50.00, 0),
('Blood Glucose (Fasting)', 'GLU-001', 'Biochemistry', 'Blood', 'Fluoride (Grey Top)', 0.5, 2.0, 30.00, 1),
('Blood Glucose (Random)', 'GLU-002', 'Biochemistry', 'Blood', 'Fluoride (Grey Top)', 0.5, 2.0, 30.00, 0),
('Liver Function Test (LFT)', 'LFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', 2.0, 6.0, 80.00, 1),
('Renal Function Test (RFT)', 'RFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', 2.0, 6.0, 80.00, 0),
('Lipid Profile', 'LIP-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', 2.0, 6.0, 80.00, 1),
('Urinalysis', 'URN-001', 'Urinalysis', 'Urine', 'Sterile Container', 1.0, 3.0, 25.00, 0),
('Malaria Parasite Test (MP)', 'MAL-001', 'Parasitology', 'Blood', 'EDTA (Purple Top)', 0.5, 1.0, 20.00, 0),
('Stool Routine Examination', 'STL-001', 'Parasitology', 'Stool', 'Stool Container', 1.0, 4.0, 25.00, 0),
('Blood Culture & Sensitivity', 'BCX-001', 'Microbiology', 'Blood', 'Blood Culture Bottle', 48.0, 72.0, 150.00, 0),
('Urine Culture & Sensitivity', 'UCX-001', 'Microbiology', 'Urine', 'Sterile Container', 24.0, 48.0, 100.00, 0),
('HIV Rapid Test', 'HIV-001', 'Serology', 'Blood', 'EDTA (Purple Top)', 0.5, 1.0, 40.00, 0),
('Hepatitis B Surface Antigen', 'HBS-001', 'Serology', 'Blood', 'SST (Yellow Top)', 1.0, 4.0, 50.00, 0),
('Hepatitis C Antibody', 'HCV-001', 'Serology', 'Blood', 'SST (Yellow Top)', 1.0, 4.0, 50.00, 0),
('Widal Test', 'WDL-001', 'Serology', 'Blood', 'SST (Yellow Top)', 1.0, 4.0, 35.00, 0),
('Pregnancy Test (Urine)', 'PRG-001', 'Immunology', 'Urine', 'Sterile Container', 0.3, 0.5, 15.00, 0),
('Chest X-Ray', 'CXR-001', 'Radiology', 'Other', 'N/A', 0.5, 2.0, 100.00, 0),
('Thyroid Function Test (TFT)', 'TFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', 4.0, 24.0, 120.00, 0),
('Electrolytes (Na/K/Cl)', 'ELY-001', 'Biochemistry', 'Blood', 'Heparin (Green Top)', 1.0, 4.0, 60.00, 0),
('Erythrocyte Sedimentation Rate', 'ESR-001', 'Hematology', 'Blood', 'Citrate (Blue Top)', 1.0, 2.0, 25.00, 0),
('Prothrombin Time (PT/INR)', 'PTI-001', 'Hematology', 'Blood', 'Citrate (Blue Top)', 1.0, 4.0, 60.00, 0),
('HbA1c (Glycated Hemoglobin)', 'HBA-001', 'Biochemistry', 'Blood', 'EDTA (Purple Top)', 2.0, 6.0, 90.00, 0),
('Semen Analysis', 'SEM-001', 'Other', 'Other', 'Sterile Container', 2.0, 4.0, 80.00, 0),
('CSF Analysis', 'CSF-001', 'Biochemistry', 'CSF', 'Sterile Tube', 2.0, 6.0, 150.00, 0),
('Sputum AFB (TB Test)', 'AFB-001', 'Microbiology', 'Sputum', 'Sputum Container', 24.0, 72.0, 60.00, 0);
