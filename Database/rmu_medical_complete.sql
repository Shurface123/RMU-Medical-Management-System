-- ===================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- COMPLETE DATABASE SCHEMA
-- ===================================
-- Version: 2.0
-- Created: February 2026
-- Authors: Lovelace & Craig (Group Six)

-- Create Database
CREATE DATABASE IF NOT EXISTS `rmu_medical_sickbay` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rmu_medical_sickbay`;

-- ===================================
-- TABLE 1: USERS (All System Users)
-- ===================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_name` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `user_role` ENUM('admin', 'doctor', 'patient', 'staff', 'pharmacist') NOT NULL DEFAULT 'patient',
    `name` VARCHAR(200) NOT NULL,
    `phone` VARCHAR(20),
    `date_of_birth` DATE,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `address` TEXT,
    `profile_image` VARCHAR(255) DEFAULT 'default-avatar.png',
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`user_name`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`user_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 2: USER SESSIONS (Session Management)
-- ===================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `session_id` VARCHAR(255) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `user_role` ENUM('admin', 'doctor', 'patient', 'staff', 'pharmacist') NOT NULL,
    `login_time` DATETIME NOT NULL,
    `last_activity` DATETIME NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `logout_time` DATETIME NULL,
    PRIMARY KEY (`session_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 3: PATIENTS (Patient-Specific Data)
-- ===================================
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `patient_id` VARCHAR(50) NOT NULL UNIQUE,
    `student_id` VARCHAR(50) UNIQUE,
    `is_student` BOOLEAN DEFAULT FALSE,
    `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') DEFAULT NULL,
    `allergies` TEXT,
    `chronic_conditions` TEXT,
    `emergency_contact_name` VARCHAR(200),
    `emergency_contact_phone` VARCHAR(20),
    `emergency_contact_relationship` VARCHAR(100),
    `insurance_provider` VARCHAR(200),
    `insurance_number` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 4: DOCTORS (Doctor Profiles)
-- ===================================
CREATE TABLE IF NOT EXISTS `doctors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `doctor_id` VARCHAR(50) NOT NULL UNIQUE,
    `specialization` VARCHAR(200) NOT NULL,
    `qualifications` TEXT,
    `experience_years` INT(11) DEFAULT 0,
    `license_number` VARCHAR(100) UNIQUE,
    `consultation_fee` DECIMAL(10, 2) DEFAULT 0.00,
    `available_days` VARCHAR(100),
    `available_hours` VARCHAR(100),
    `bio` TEXT,
    `is_available` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_specialization` (`specialization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 5: STAFF (Medical Staff)
-- ===================================
CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `staff_id` VARCHAR(50) NOT NULL UNIQUE,
    `department` VARCHAR(200) NOT NULL,
    `position` VARCHAR(200) NOT NULL,
    `hire_date` DATE,
    `salary` DECIMAL(10, 2) DEFAULT 0.00,
    `shift` ENUM('Morning', 'Afternoon', 'Night', 'Rotating') DEFAULT 'Morning',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 6: APPOINTMENTS (Booking Records)
-- ===================================
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `appointment_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `service_type` VARCHAR(200),
    `symptoms` TEXT,
    `status` ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-Show') DEFAULT 'Pending',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
    INDEX `idx_appointment_id` (`appointment_id`),
    INDEX `idx_date` (`appointment_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 7: MEDICAL RECORDS (Patient Medical History)
-- ===================================
CREATE TABLE IF NOT EXISTS `medical_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `record_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `visit_date` DATE NOT NULL,
    `diagnosis` TEXT NOT NULL,
    `symptoms` TEXT,
    `treatment` TEXT,
    `vital_signs` JSON,
    `lab_results` TEXT,
    `notes` TEXT,
    `follow_up_required` BOOLEAN DEFAULT FALSE,
    `follow_up_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
    INDEX `idx_record_id` (`record_id`),
    INDEX `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 8: PRESCRIPTIONS (Medication Prescriptions)
-- ===================================
CREATE TABLE IF NOT EXISTS `prescriptions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `prescription_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `medical_record_id` INT(11),
    `prescription_date` DATE NOT NULL,
    `medication_name` VARCHAR(200) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL,
    `frequency` VARCHAR(100) NOT NULL,
    `duration` VARCHAR(100) NOT NULL,
    `instructions` TEXT,
    `quantity` INT(11) NOT NULL,
    `status` ENUM('Pending', 'Dispensed', 'Cancelled') DEFAULT 'Pending',
    `dispensed_by` INT(11) NULL,
    `dispensed_date` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`dispensed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_prescription_id` (`prescription_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 9: MEDICINES (Pharmacy Inventory)
-- ===================================
CREATE TABLE IF NOT EXISTS `medicines` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `medicine_id` VARCHAR(50) NOT NULL UNIQUE,
    `medicine_name` VARCHAR(200) NOT NULL,
    `generic_name` VARCHAR(200),
    `category` VARCHAR(100),
    `manufacturer` VARCHAR(200),
    `description` TEXT,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT(11) NOT NULL DEFAULT 0,
    `reorder_level` INT(11) DEFAULT 10,
    `expiry_date` DATE,
    `batch_number` VARCHAR(100),
    `is_prescription_required` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_medicine_id` (`medicine_id`),
    INDEX `idx_medicine_name` (`medicine_name`),
    INDEX `idx_stock` (`stock_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 10: SERVICES (Medical Services Offered)
-- ===================================
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `service_id` VARCHAR(50) NOT NULL UNIQUE,
    `service_name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `category` VARCHAR(100),
    `price` DECIMAL(10, 2) DEFAULT 0.00,
    `is_free_for_students` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_service_id` (`service_id`),
    INDEX `idx_service_name` (`service_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 11: AMBULANCES (Ambulance Fleet)
-- ===================================
CREATE TABLE IF NOT EXISTS `ambulances` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ambulance_id` VARCHAR(50) NOT NULL UNIQUE,
    `vehicle_number` VARCHAR(50) NOT NULL UNIQUE,
    `driver_name` VARCHAR(200),
    `driver_phone` VARCHAR(20),
    `status` ENUM('Available', 'On Duty', 'Maintenance', 'Out of Service') DEFAULT 'Available',
    `last_service_date` DATE,
    `next_service_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ambulance_id` (`ambulance_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 12: AMBULANCE REQUESTS (Emergency Calls)
-- ===================================
CREATE TABLE IF NOT EXISTS `ambulance_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `request_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_name` VARCHAR(200) NOT NULL,
    `patient_phone` VARCHAR(20) NOT NULL,
    `pickup_location` TEXT NOT NULL,
    `destination` TEXT NOT NULL,
    `emergency_type` VARCHAR(200),
    `ambulance_id` INT(11) NULL,
    `status` ENUM('Pending', 'Dispatched', 'In Transit', 'Completed', 'Cancelled') DEFAULT 'Pending',
    `request_time` DATETIME NOT NULL,
    `dispatch_time` DATETIME NULL,
    `completion_time` DATETIME NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`ambulance_id`) REFERENCES `ambulances`(`id`) ON DELETE SET NULL,
    INDEX `idx_request_id` (`request_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 13: BEDS (Inpatient Bed Facilities)
-- ===================================
CREATE TABLE IF NOT EXISTS `beds` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bed_id` VARCHAR(50) NOT NULL UNIQUE,
    `bed_number` VARCHAR(50) NOT NULL UNIQUE,
    `ward` VARCHAR(100) NOT NULL,
    `bed_type` ENUM('General', 'ICU', 'Private', 'Semi-Private') DEFAULT 'General',
    `status` ENUM('Available', 'Occupied', 'Maintenance', 'Reserved') DEFAULT 'Available',
    `daily_rate` DECIMAL(10, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_bed_id` (`bed_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_ward` (`ward`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 14: BED ASSIGNMENTS (Patient Bed Allocation)
-- ===================================
CREATE TABLE IF NOT EXISTS `bed_assignments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `assignment_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `bed_id` INT(11) NOT NULL,
    `admission_date` DATETIME NOT NULL,
    `discharge_date` DATETIME NULL,
    `reason` TEXT,
    `status` ENUM('Active', 'Discharged', 'Transferred') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`) ON DELETE CASCADE,
    INDEX `idx_assignment_id` (`assignment_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 15: LAB TESTS (Laboratory Tests)
-- ===================================
CREATE TABLE IF NOT EXISTS `lab_tests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `test_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `test_name` VARCHAR(200) NOT NULL,
    `test_category` VARCHAR(100),
    `test_date` DATE NOT NULL,
    `results` TEXT,
    `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    `cost` DECIMAL(10, 2) DEFAULT 0.00,
    `technician_name` VARCHAR(200),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
    INDEX `idx_test_id` (`test_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 16: PAYMENTS (Financial Transactions)
-- ===================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payment_id` VARCHAR(50) NOT NULL UNIQUE,
    `patient_id` INT(11) NOT NULL,
    `payment_type` ENUM('Consultation', 'Medication', 'Lab Test', 'Bed Charge', 'Other') NOT NULL,
    `reference_id` VARCHAR(50),
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` ENUM('Cash', 'Mobile Money', 'Card', 'Insurance', 'Free') DEFAULT 'Cash',
    `payment_date` DATETIME NOT NULL,
    `status` ENUM('Pending', 'Completed', 'Refunded', 'Failed') DEFAULT 'Pending',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    INDEX `idx_payment_id` (`payment_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLE 17: AUDIT LOG (System Activity Tracking)
-- ===================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `action` VARCHAR(200) NOT NULL,
    `table_name` VARCHAR(100),
    `record_id` VARCHAR(50),
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- INSERT DEFAULT ADMIN USER
-- ===================================
-- Password: admin123 (hashed with password_hash in PHP)
INSERT INTO `users` (`user_name`, `email`, `password`, `user_role`, `name`, `phone`, `gender`, `is_active`, `is_verified`) 
VALUES 
('admin', 'admin@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '0502371207', 'Male', TRUE, TRUE);

-- ===================================
-- INSERT SAMPLE SERVICES
-- ===================================
INSERT INTO `services` (`service_id`, `service_name`, `description`, `category`, `price`, `is_free_for_students`, `is_active`) VALUES
('SRV001', 'General Consultation', 'General medical consultation with a doctor', 'Consultation', 50.00, TRUE, TRUE),
('SRV002', 'Emergency Care', '24/7 emergency medical services', 'Emergency', 0.00, TRUE, TRUE),
('SRV003', 'Health Checkup', 'Comprehensive health screening', 'Preventive', 0.00, TRUE, TRUE),
('SRV004', 'Laboratory Tests', 'Diagnostic laboratory services', 'Diagnostic', 30.00, FALSE, TRUE),
('SRV005', 'Pharmacy Services', 'Medication dispensing', 'Pharmacy', 0.00, FALSE, TRUE),
('SRV006', 'Ambulance Service', 'Emergency ambulance transport', 'Emergency', 100.00, FALSE, TRUE),
('SRV007', 'Bed Facility', 'Inpatient bed accommodation', 'Inpatient', 80.00, FALSE, TRUE),
('SRV008', 'Mental Health Counseling', 'Professional counseling services', 'Mental Health', 0.00, TRUE, TRUE);

-- ===================================
-- INSERT SAMPLE BEDS
-- ===================================
INSERT INTO `beds` (`bed_id`, `bed_number`, `ward`, `bed_type`, `status`, `daily_rate`) VALUES
('BED001', 'W1-B01', 'General Ward 1', 'General', 'Available', 50.00),
('BED002', 'W1-B02', 'General Ward 1', 'General', 'Available', 50.00),
('BED003', 'W1-B03', 'General Ward 1', 'General', 'Available', 50.00),
('BED004', 'ICU-B01', 'ICU', 'ICU', 'Available', 200.00),
('BED005', 'ICU-B02', 'ICU', 'ICU', 'Available', 200.00),
('BED006', 'PVT-B01', 'Private Ward', 'Private', 'Available', 150.00),
('BED007', 'PVT-B02', 'Private Ward', 'Private', 'Available', 150.00);

-- ===================================
-- INSERT SAMPLE AMBULANCES
-- ===================================
INSERT INTO `ambulances` (`ambulance_id`, `vehicle_number`, `driver_name`, `driver_phone`, `status`) VALUES
('AMB001', 'GH-1234-20', 'Kwame Mensah', '0241234567', 'Available'),
('AMB002', 'GH-5678-20', 'Ama Serwaa', '0245678901', 'Available');

-- ===================================
-- INSERT SAMPLE MEDICINES
-- ===================================
INSERT INTO `medicines` (`medicine_id`, `medicine_name`, `generic_name`, `category`, `manufacturer`, `unit_price`, `stock_quantity`, `reorder_level`, `is_prescription_required`) VALUES
('MED001', 'Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Pharma Ltd', 0.50, 500, 50, FALSE),
('MED002', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Pharma Ltd', 0.75, 300, 50, FALSE),
('MED003', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'MedCare', 2.00, 200, 30, TRUE),
('MED004', 'Vitamin C 1000mg', 'Ascorbic Acid', 'Vitamin', 'HealthPlus', 1.00, 400, 50, FALSE),
('MED005', 'Omeprazole 20mg', 'Omeprazole', 'Antacid', 'MedCare', 1.50, 150, 30, TRUE);

-- ===================================
-- STORED PROCEDURES
-- ===================================

-- Procedure to check and destroy existing user sessions
DELIMITER $$
CREATE PROCEDURE `DestroyUserSessions`(IN p_user_id INT)
BEGIN
    UPDATE `user_sessions` 
    SET `is_active` = FALSE, `logout_time` = NOW() 
    WHERE `user_id` = p_user_id AND `is_active` = TRUE;
END$$
DELIMITER ;

-- Procedure to get available beds
DELIMITER $$
CREATE PROCEDURE `GetAvailableBeds`(IN p_ward VARCHAR(100))
BEGIN
    IF p_ward IS NULL OR p_ward = '' THEN
        SELECT * FROM `beds` WHERE `status` = 'Available' ORDER BY `ward`, `bed_number`;
    ELSE
        SELECT * FROM `beds` WHERE `status` = 'Available' AND `ward` = p_ward ORDER BY `bed_number`;
    END IF;
END$$
DELIMITER ;

-- Procedure to get low stock medicines
DELIMITER $$
CREATE PROCEDURE `GetLowStockMedicines`()
BEGIN
    SELECT * FROM `medicines` 
    WHERE `stock_quantity` <= `reorder_level` 
    ORDER BY `stock_quantity` ASC;
END$$
DELIMITER ;

-- ===================================
-- TRIGGERS
-- ===================================

-- Trigger to log user creation
DELIMITER $$
CREATE TRIGGER `after_user_insert` 
AFTER INSERT ON `users`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`user_id`, `action`, `table_name`, `record_id`, `new_values`)
    VALUES (NEW.id, 'CREATE', 'users', NEW.id, JSON_OBJECT('username', NEW.user_name, 'user_role', NEW.role));
END$$
DELIMITER ;

-- Trigger to update bed status when assigned
DELIMITER $$
CREATE TRIGGER `after_bed_assignment_insert`
AFTER INSERT ON `bed_assignments`
FOR EACH ROW
BEGIN
    UPDATE `beds` SET `status` = 'Occupied' WHERE `id` = NEW.bed_id;
END$$
DELIMITER ;

-- Trigger to update bed status when discharged
DELIMITER $$
CREATE TRIGGER `after_bed_assignment_update`
AFTER UPDATE ON `bed_assignments`
FOR EACH ROW
BEGIN
    IF NEW.status = 'Discharged' OR NEW.status = 'Transferred' THEN
        UPDATE `beds` SET `status` = 'Available' WHERE `id` = NEW.bed_id;
    END IF;
END$$
DELIMITER ;

-- ===================================
-- VIEWS FOR COMMON QUERIES
-- ===================================

-- View for active appointments
CREATE OR REPLACE VIEW `active_appointments` AS
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    p.patient_id,
    u_patient.full_name AS patient_name,
    u_patient.phone AS patient_phone,
    d.doctor_id,
    u_doctor.full_name AS doctor_name,
    d.specialization
FROM `appointments` a
JOIN `patients` p ON a.patient_id = p.id
JOIN `users` u_patient ON p.user_id = u_patient.id
JOIN `doctors` d ON a.doctor_id = d.id
JOIN `users` u_doctor ON d.user_id = u_doctor.id
WHERE a.status IN ('Pending', 'Confirmed')
ORDER BY a.appointment_date, a.appointment_time;

-- View for patient medical summary
CREATE OR REPLACE VIEW `patient_medical_summary` AS
SELECT 
    p.patient_id,
    u.full_name,
    u.phone,
    u.email,
    p.blood_group,
    p.allergies,
    p.chronic_conditions,
    COUNT(DISTINCT mr.id) AS total_visits,
    COUNT(DISTINCT pr.id) AS total_prescriptions,
    MAX(mr.visit_date) AS last_visit_date
FROM `patients` p
JOIN `users` u ON p.user_id = u.id
LEFT JOIN `medical_records` mr ON p.id = mr.patient_id
LEFT JOIN `prescriptions` pr ON p.id = pr.patient_id
GROUP BY p.id;

-- View for medicine inventory status
CREATE OR REPLACE VIEW `medicine_inventory_status` AS
SELECT 
    medicine_id,
    medicine_name,
    category,
    stock_quantity,
    reorder_level,
    CASE 
        WHEN stock_quantity = 0 THEN 'Out of Stock'
        WHEN stock_quantity <= reorder_level THEN 'Low Stock'
        ELSE 'In Stock'
    END AS stock_status,
    unit_price,
    (stock_quantity * unit_price) AS total_value
FROM `medicines`
ORDER BY stock_quantity ASC;

-- ===================================
-- GRANT PERMISSIONS (Optional)
-- ===================================
-- GRANT ALL PRIVILEGES ON rmu_medical.* TO 'rmu_admin'@'localhost' IDENTIFIED BY 'secure_password';
-- FLUSH PRIVILEGES;

-- ===================================
-- DATABASE SCHEMA COMPLETE
-- ===================================
-- Total Tables: 17
-- Total Stored Procedures: 3
-- Total Triggers: 3
-- Total Views: 3
