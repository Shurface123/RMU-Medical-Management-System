-- ===================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- PHASE 2: LAB TECHNICIAN DASHBOARD DATABASE
-- ===================================

USE `rmu_medical_sickbay`;

-- 1. Expand User Roles
ALTER TABLE `users` MODIFY COLUMN `user_role` ENUM('admin', 'doctor', 'patient', 'staff', 'pharmacist', 'nurse', 'lab_technician') NOT NULL DEFAULT 'patient';

-- 2. Lab Technicians Profile
CREATE TABLE IF NOT EXISTS `lab_technicians` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE,
    `gender` ENUM('Male', 'Female', 'Other'),
    `nationality` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(150),
    `address` TEXT,
    `profile_photo` VARCHAR(255) DEFAULT 'default-avatar.png',
    `license_number` VARCHAR(100) UNIQUE,
    `license_expiry` DATE,
    `specialization` VARCHAR(200),
    `department_id` INT,
    `designation` VARCHAR(100),
    `years_of_experience` INT DEFAULT 0,
    `status` ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Lab Test Catalog
CREATE TABLE IF NOT EXISTS `lab_test_catalog` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `test_id` VARCHAR(50) UNIQUE NOT NULL,
    `test_name` VARCHAR(255) NOT NULL,
    `test_code` VARCHAR(50) UNIQUE NOT NULL,
    `category` ENUM('hematology', 'biochemistry', 'microbiology', 'immunology', 'urinalysis', 'histology', 'radiology', 'other'),
    `sample_type_required` ENUM('blood', 'urine', 'stool', 'swab', 'tissue', 'other'),
    `container_type` VARCHAR(100),
    `collection_instructions` TEXT,
    `processing_time_hours` DECIMAL(5,2),
    `turnaround_time_hours` DECIMAL(5,2),
    `price` DECIMAL(10, 2) DEFAULT 0.00,
    `requires_fasting` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Lab Test Orders (ALTER Existing lab_tests)
-- Checking if request_id, test_catalog_id, etc. exist.
ALTER TABLE `lab_tests` 
    ADD COLUMN IF NOT EXISTS `request_id` INT DEFAULT NULL AFTER `test_id`,
    ADD COLUMN IF NOT EXISTS `technician_id_assigned` INT DEFAULT NULL AFTER `doctor_id`,
    ADD COLUMN IF NOT EXISTS `test_catalog_id` INT DEFAULT NULL AFTER `technician_id_assigned`,
    ADD COLUMN IF NOT EXISTS `urgency` ENUM('Routine', 'Urgent', 'STAT', 'Critical') DEFAULT 'Routine' AFTER `test_name`,
    ADD COLUMN IF NOT EXISTS `required_by_date` DATE DEFAULT NULL AFTER `test_date`,
    ADD COLUMN IF NOT EXISTS `clinical_notes` TEXT AFTER `results`,
    ADD COLUMN IF NOT EXISTS `diagnosis` TEXT AFTER `clinical_notes`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT AFTER `status`,
    MODIFY COLUMN `status` ENUM('Pending', 'Accepted', 'Rejected', 'Sample Collected', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending';

-- 5. Lab Samples
CREATE TABLE IF NOT EXISTS `lab_samples` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sample_id` VARCHAR(50) UNIQUE NOT NULL,
    `order_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `technician_id` INT,
    `sample_type` VARCHAR(100),
    `sample_code` VARCHAR(100) UNIQUE NOT NULL, -- Barcode ID
    `collection_date` DATE,
    `collection_time` TIME,
    `collected_by` VARCHAR(100), -- Nurse or Tech ID
    `container_type` VARCHAR(100),
    `volume_collected` VARCHAR(50),
    `storage_location` VARCHAR(100),
    `condition_on_receipt` ENUM('Good', 'Haemolysed', 'Clotted', 'Insufficient', 'Contaminated'),
    `status` ENUM('Collected', 'Received', 'Processing', 'Stored', 'Disposed', 'Rejected') DEFAULT 'Collected',
    `notes` TEXT,
    `barcode_image_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `lab_tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Lab Results (ALTER Existing lab_results)
ALTER TABLE `lab_results` 
    ADD COLUMN IF NOT EXISTS `order_id` INT DEFAULT NULL AFTER `result_id`,
    ADD COLUMN IF NOT EXISTS `sample_id` INT DEFAULT NULL AFTER `order_id`,
    ADD COLUMN IF NOT EXISTS `technician_id` INT DEFAULT NULL AFTER `doctor_id`,
    ADD COLUMN IF NOT EXISTS `result_values_json` JSON DEFAULT NULL AFTER `test_name`,
    ADD COLUMN IF NOT EXISTS `unit_of_measurement` VARCHAR(50) AFTER `result_values_json`,
    ADD COLUMN IF NOT EXISTS `reference_range_min` VARCHAR(50) AFTER `unit_of_measurement`,
    ADD COLUMN IF NOT EXISTS `reference_range_max` VARCHAR(50) AFTER `reference_range_min`,
    ADD COLUMN IF NOT EXISTS `result_interpretation` ENUM('Normal', 'Abnormal', 'Critical') AFTER `reference_range_max`,
    ADD COLUMN IF NOT EXISTS `result_status` ENUM('Draft', 'Pending Validation', 'Validated', 'Released', 'Amended') DEFAULT 'Draft' AFTER `result_interpretation`,
    ADD COLUMN IF NOT EXISTS `validated_by` INT DEFAULT NULL AFTER `result_status`,
    ADD COLUMN IF NOT EXISTS `validated_at` DATETIME DEFAULT NULL AFTER `validated_by`,
    ADD COLUMN IF NOT EXISTS `released_to_doctor` BOOLEAN DEFAULT FALSE AFTER `validated_at`,
    ADD COLUMN IF NOT EXISTS `released_at` DATETIME DEFAULT NULL AFTER `released_to_doctor`,
    ADD COLUMN IF NOT EXISTS `released_to_patient` BOOLEAN DEFAULT FALSE AFTER `released_at`,
    ADD COLUMN IF NOT EXISTS `patient_release_approved_by` INT DEFAULT NULL AFTER `released_to_patient`,
    ADD COLUMN IF NOT EXISTS `report_file_path` VARCHAR(255) AFTER `patient_release_approved_by`,
    ADD COLUMN IF NOT EXISTS `technician_comments` TEXT AFTER `report_file_path`,
    ADD COLUMN IF NOT EXISTS `amended_reason` TEXT AFTER `technician_comments`,
    MODIFY COLUMN `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending';

-- 7. Lab Result Parameters
CREATE TABLE IF NOT EXISTS `lab_result_parameters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `result_id` INT NOT NULL,
    `parameter_name` VARCHAR(255) NOT NULL,
    `value` VARCHAR(100),
    `unit` VARCHAR(50),
    `reference_range_min` VARCHAR(50),
    `reference_range_max` VARCHAR(50),
    `flag` ENUM('Normal', 'Low', 'High', 'Critical Low', 'Critical High'),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`result_id`) REFERENCES `lab_results`(`result_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Lab Reference Ranges
CREATE TABLE IF NOT EXISTS `lab_reference_ranges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `test_catalog_id` INT NOT NULL,
    `parameter_name` VARCHAR(255) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Both') DEFAULT 'Both',
    `age_min_years` INT DEFAULT 0,
    `age_max_years` INT DEFAULT 150,
    `normal_min` VARCHAR(50),
    `normal_max` VARCHAR(50),
    `critical_low` VARCHAR(50),
    `critical_high` VARCHAR(50),
    `unit` VARCHAR(50),
    `updated_by` INT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`test_catalog_id`) REFERENCES `lab_test_catalog`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Lab Equipment
CREATE TABLE IF NOT EXISTS `lab_equipment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `model` VARCHAR(100),
    `serial_number` VARCHAR(100),
    `manufacturer` VARCHAR(150),
    `category` VARCHAR(100),
    `department` VARCHAR(100),
    `location` VARCHAR(100),
    `purchase_date` DATE,
    `warranty_expiry` DATE,
    `status` ENUM('Operational', 'Maintenance', 'Calibration Due', 'Out of Service', 'Decommissioned') DEFAULT 'Operational',
    `last_calibration_date` DATE,
    `next_calibration_date` DATE,
    `last_maintenance_date` DATE,
    `next_maintenance_date` DATE,
    `assigned_technician_id` INT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Equipment Maintenance Log
CREATE TABLE IF NOT EXISTS `equipment_maintenance_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT NOT NULL,
    `maintenance_type` ENUM('Calibration', 'Repair', 'Service', 'Inspection'),
    `performed_by` VARCHAR(255), -- Tech ID or External Name
    `performed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `next_due_date` DATE,
    `findings` TEXT,
    `cost` DECIMAL(10, 2) DEFAULT 0.00,
    `documents_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`equipment_id`) REFERENCES `lab_equipment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Reagent Inventory
CREATE TABLE IF NOT EXISTS `reagent_inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reagent_id` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `catalog_number` VARCHAR(50),
    `manufacturer` VARCHAR(150),
    `category` VARCHAR(100),
    `unit` VARCHAR(50), -- e.g. Pack, Bottle, Vial
    `quantity_in_stock` INT DEFAULT 0,
    `reorder_level` INT DEFAULT 5,
    `unit_cost` DECIMAL(10, 2) DEFAULT 0.00,
    `expiry_date` DATE,
    `storage_conditions` TEXT,
    `linked_equipment_id` INT,
    `status` ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired', 'Expiring Soon'),
    `batch_number` VARCHAR(100),
    `date_received` DATE,
    `supplier_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`linked_equipment_id`) REFERENCES `lab_equipment`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Reagent Transactions
CREATE TABLE IF NOT EXISTS `reagent_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reagent_id` INT NOT NULL,
    `transaction_type` ENUM('Received', 'Used', 'Disposed', 'Adjusted'),
    `quantity` INT NOT NULL,
    `previous_quantity` INT,
    `new_quantity` INT,
    `performed_by` INT, -- Technician ID
    `linked_test_order_id` INT, -- Optional
    `notes` TEXT,
    `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reagent_id`) REFERENCES `reagent_inventory`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Lab Notifications
CREATE TABLE IF NOT EXISTS `lab_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient_id` INT NOT NULL,
    `recipient_role` VARCHAR(50),
    `sender_id` INT,
    `sender_role` VARCHAR(50),
    `message` TEXT NOT NULL,
    `type` ENUM('New Order', 'Result Ready', 'Critical Value', 'Equipment Alert', 'Reagent Alert', 'System', 'Message'),
    `is_read` BOOLEAN DEFAULT FALSE,
    `related_module` VARCHAR(100),
    `related_record_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Lab Internal Messages
CREATE TABLE IF NOT EXISTS `lab_internal_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `sender_role` VARCHAR(50),
    `receiver_id` INT NOT NULL,
    `receiver_role` VARCHAR(50),
    `patient_id` INT,
    `order_id` INT,
    `subject` VARCHAR(255),
    `message_content` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `priority` ENUM('Normal', 'Urgent') DEFAULT 'Normal',
    `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `read_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Lab Audit Trail
CREATE TABLE IF NOT EXISTS `lab_audit_trail` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT,
    `action_type` VARCHAR(100),
    `module_affected` VARCHAR(100),
    `record_id_affected` INT,
    `old_value` JSON,
    `new_value` JSON,
    `ip_address` VARCHAR(45),
    `device_info` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Lab Report Templates
CREATE TABLE IF NOT EXISTS `lab_report_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `header_content` TEXT,
    `footer_content` TEXT,
    `logo_path` VARCHAR(255),
    `includes_digital_signature` BOOLEAN DEFAULT FALSE,
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Lab Generated Reports
CREATE TABLE IF NOT EXISTS `lab_generated_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `generated_by` INT, -- Technician ID
    `report_type` VARCHAR(100),
    `parameters` JSON,
    `file_path` VARCHAR(255),
    `format` ENUM('PDF', 'CSV', 'XLSX'),
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Lab Technician Qualifications
CREATE TABLE IF NOT EXISTS `lab_technician_qualifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `degree_name` VARCHAR(255) NOT NULL,
    `institution` VARCHAR(255) NOT NULL,
    `year_awarded` INT,
    `certificate_file_path` VARCHAR(255),
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Lab Technician Certifications
CREATE TABLE IF NOT EXISTS `lab_technician_certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `certification_name` VARCHAR(255) NOT NULL,
    `issuing_body` VARCHAR(255),
    `issue_date` DATE,
    `expiry_date` DATE,
    `certificate_file_path` VARCHAR(255),
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. Lab Technician Documents
CREATE TABLE IF NOT EXISTS `lab_technician_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50),
    `file_size` INT,
    `description` TEXT,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Lab Technician Sessions
CREATE TABLE IF NOT EXISTS `lab_technician_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `session_id` VARCHAR(255) UNIQUE NOT NULL,
    `device_info` TEXT,
    `browser` VARCHAR(100),
    `ip_address` VARCHAR(45),
    `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_active` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_current_session` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. Lab Technician Settings
CREATE TABLE IF NOT EXISTS `lab_technician_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `notification_preferences` JSON,
    `theme_preference` ENUM('Light', 'Dark') DEFAULT 'Light',
    `language` VARCHAR(50) DEFAULT 'English',
    `alert_sound_enabled` BOOLEAN DEFAULT TRUE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. Lab Technician Profile Completeness
CREATE TABLE IF NOT EXISTS `lab_technician_profile_completeness` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `personal_info_complete` BOOLEAN DEFAULT FALSE,
    `professional_profile_complete` BOOLEAN DEFAULT FALSE,
    `qualifications_complete` BOOLEAN DEFAULT FALSE,
    `documents_uploaded` BOOLEAN DEFAULT FALSE,
    `photo_uploaded` BOOLEAN DEFAULT FALSE,
    `security_setup_complete` BOOLEAN DEFAULT FALSE,
    `overall_percentage` DECIMAL(5,2) DEFAULT 0.00,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. Lab Quality Control
CREATE TABLE IF NOT EXISTS `lab_quality_control` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `equipment_id` INT,
    `test_catalog_id` INT,
    `qc_date` DATE,
    `qc_type` ENUM('Internal', 'External'),
    `lot_number` VARCHAR(100),
    `expected_range_min` VARCHAR(50),
    `expected_range_max` VARCHAR(50),
    `result_obtained` VARCHAR(50),
    `passed` BOOLEAN DEFAULT FALSE,
    `corrective_action_taken` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`equipment_id`) REFERENCES `lab_equipment`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`test_catalog_id`) REFERENCES `lab_test_catalog`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. Lab Workload Log
CREATE TABLE IF NOT EXISTS `lab_workload_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `technician_id` INT NOT NULL,
    `shift_date` DATE,
    `shift_type` VARCHAR(50),
    `total_orders_received` INT DEFAULT 0,
    `total_completed` INT DEFAULT 0,
    `total_pending` INT DEFAULT 0,
    `total_rejected` INT DEFAULT 0,
    `total_critical_results` INT DEFAULT 0,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
