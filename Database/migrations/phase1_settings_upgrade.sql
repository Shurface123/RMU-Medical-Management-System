-- Phase 1: Admin Dashboard System Settings Full Upgrade
-- SQL Migration Script

-- 1. Hospital Profile Settings
CREATE TABLE IF NOT EXISTS `hospital_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hospital_name` VARCHAR(255) NOT NULL,
    `logo_path` VARCHAR(500),
    `address` TEXT,
    `contact_numbers` JSON, -- List of phone numbers
    `email` VARCHAR(150),
    `website` VARCHAR(255),
    `accreditation_number` VARCHAR(100),
    `license_number` VARCHAR(100),
    `facility_type` VARCHAR(100),
    `emergency_contacts` JSON, -- List of objects {name, phone, role}
    `operating_hours` JSON, -- {department_id: "08:00 - 17:00"}
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Wards Enhancement
ALTER TABLE `wards` 
    ADD COLUMN `department_id` INT AFTER `ward_name`,
    ADD COLUMN `status` ENUM('Active', 'Inactive', 'Full', 'Maintenance') DEFAULT 'Active' AFTER `capacity`,
    ADD COLUMN `current_occupancy` INT DEFAULT 0 AFTER `status`,
    ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 3. Permission Matrix
CREATE TABLE IF NOT EXISTS `permission_matrix` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role` ENUM('admin', 'doctor', 'patient', 'staff', 'pharmacist', 'nurse', 'lab_technician') NOT NULL,
    `module_name` VARCHAR(100) NOT NULL,
    `can_read` TINYINT(1) DEFAULT 0,
    `can_write` TINYINT(1) DEFAULT 0,
    `can_update` TINYINT(1) DEFAULT 0,
    `can_delete` TINYINT(1) DEFAULT 0,
    `is_restricted` TINYINT(1) DEFAULT 0,
    `restricted_fields` JSON, -- List of columns restricted for this role
    UNIQUE KEY `role_module` (`role`, `module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Shift Types
CREATE TABLE IF NOT EXISTS `shift_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shift_name` VARCHAR(100) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `color_code` VARCHAR(20) DEFAULT '#3498db',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Vital Thresholds (Enhanced Categories)
ALTER TABLE `vital_thresholds` 
    ADD COLUMN `patient_category` ENUM('Adult', 'Pediatric', 'Elderly', 'Pregnant', 'General') DEFAULT 'General' AFTER `vital_type`,
    DROP INDEX `id`, -- If index exists
    ADD INDEX `category_vital` (`patient_category`, `vital_type`);

-- 6. Notification Settings
CREATE TABLE IF NOT EXISTS `notification_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `channel_push` TINYINT(1) DEFAULT 1,
    `channel_email` TINYINT(1) DEFAULT 0,
    `channel_sms` TINYINT(1) DEFAULT 0,
    `escalation_minutes` INT DEFAULT 0,
    `is_enabled` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `event_role` (`event_type`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Security & IP Whitelist
CREATE TABLE IF NOT EXISTS `ip_whitelist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `label` VARCHAR(100),
    `added_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Medication Enhancement
ALTER TABLE `medicines` 
    ADD COLUMN `is_controlled` TINYINT(1) DEFAULT 0 AFTER `is_prescription_required`,
    ADD COLUMN `drug_interactions` JSON AFTER `contraindications`;

-- 9. System Config Expansions (Insert default keys if not exist)
INSERT IGNORE INTO `system_config` (config_key, config_value) VALUES 
('date_format', 'd M Y'),
('time_format', 'H:i'),
('currency_symbol', 'GHS'),
('language_default', 'en'),
('maintenance_mode', '0'),
('password_min_length', '8'),
('password_require_special', '1'),
('session_timeout_admin', '30'),
('session_timeout_doctor', '60'),
('session_timeout_nurse', '60'),
('session_timeout_staff', '120'),
('mfa_required_admin', '0'),
('mfa_required_medical', '0');
