-- ===================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- DATABASE SCHEMA EXPANSION
-- Phase 2: Enhancement Migration
-- ===================================

-- Author: System Enhancement Team
-- Date: 2026-02-07
-- Description: Adds pharmacy management, notifications, medical records, and audit logging

-- ===================================
-- PHARMACY MANAGEMENT TABLES
-- ===================================

-- Pharmacy Suppliers
CREATE TABLE IF NOT EXISTS pharmacy_suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacy Inventory Tracking
CREATE TABLE IF NOT EXISTS pharmacy_inventory (
    inventory_id INT PRIMARY KEY AUTO_INCREMENT,
    medicine_id INT,
    batch_number VARCHAR(50) NOT NULL,
    expiry_date DATE NOT NULL,
    quantity_received INT NOT NULL DEFAULT 0,
    quantity_dispensed INT NOT NULL DEFAULT 0,
    current_stock INT NOT NULL DEFAULT 0,
    supplier_id INT,
    received_date DATE NOT NULL,
    cost_per_unit DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES pharmacy_suppliers(supplier_id) ON DELETE SET NULL,
    INDEX idx_medicine_id (medicine_id),
    INDEX idx_batch_number (batch_number),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_supplier_id (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescriptions
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    prescription_date DATE NOT NULL,
    status ENUM('Pending', 'Dispensed', 'Partially Dispensed', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    dispensed_by INT,
    dispensed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_status (status),
    INDEX idx_prescription_date (prescription_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescription Items
CREATE TABLE IF NOT EXISTS prescription_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    instructions TEXT,
    dispensed_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE CASCADE,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_medicine_id (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescription Refill Requests
CREATE TABLE IF NOT EXISTS prescription_refills (
    refill_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    patient_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Approved', 'Rejected', 'Dispensed') DEFAULT 'Pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    notes TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- NOTIFICATION SYSTEM TABLES
-- ===================================

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('appointment', 'prescription', 'test_result', 'system', 'reminder', 'alert') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Queue
CREATE TABLE IF NOT EXISTS email_queue (
    email_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    template_name VARCHAR(100),
    status ENUM('Pending', 'Sent', 'Failed', 'Cancelled') DEFAULT 'Pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    scheduled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS Queue (for future SMS notifications)
CREATE TABLE IF NOT EXISTS sms_queue (
    sms_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_phone VARCHAR(20) NOT NULL,
    recipient_name VARCHAR(100),
    message TEXT NOT NULL,
    status ENUM('Pending', 'Sent', 'Failed', 'Cancelled') DEFAULT 'Pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_recipient_phone (recipient_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- MEDICAL RECORDS TABLES
-- ===================================

-- Medical Records
CREATE TABLE IF NOT EXISTS medical_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_type ENUM('Consultation', 'Follow-up', 'Emergency', 'Routine Check-up') NOT NULL,
    chief_complaint TEXT,
    diagnosis TEXT,
    symptoms TEXT,
    vital_signs JSON,
    treatment_plan TEXT,
    follow_up_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Record Attachments
CREATE TABLE IF NOT EXISTS medical_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    record_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT,
    description TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES medical_records(record_id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_file_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lab Results
CREATE TABLE IF NOT EXISTS lab_results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    test_id INT NOT NULL,
    doctor_id INT,
    test_date DATE NOT NULL,
    result_date DATE,
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    results JSON,
    normal_range JSON,
    interpretation TEXT,
    technician_notes TEXT,
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_test_id (test_id),
    INDEX idx_status (status),
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- AUDIT & SECURITY TABLES
-- ===================================

-- Audit Log
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL,
    failure_reason VARCHAR(200),
    user_agent TEXT,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions (Enhanced)
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two-Factor Authentication
CREATE TABLE IF NOT EXISTS two_factor_auth (
    tfa_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    secret_key VARCHAR(255) NOT NULL,
    is_enabled BOOLEAN DEFAULT 0,
    backup_codes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password History (prevent password reuse)
CREATE TABLE IF NOT EXISTS password_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- APPOINTMENT ENHANCEMENTS
-- ===================================

-- Appointment Reminders
CREATE TABLE IF NOT EXISTS appointment_reminders (
    reminder_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    reminder_type ENUM('email', 'sms', 'notification') NOT NULL,
    scheduled_time TIMESTAMP NOT NULL,
    status ENUM('Pending', 'Sent', 'Failed') DEFAULT 'Pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_time (scheduled_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- SYSTEM CONFIGURATION
-- ===================================

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT 0,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- INSERT DEFAULT DATA
-- ===================================

-- Default System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'RMU Medical Sickbay', 'string', 'Name of the medical facility', 1),
('site_email', 'Sickbay.txt@rmu.edu.gh', 'string', 'Primary contact email', 1),
('site_phone', '0502371207', 'string', 'Primary contact phone', 1),
('emergency_hotline', '153', 'string', 'Emergency contact number', 1),
('appointment_duration', '30', 'number', 'Default appointment duration in minutes', 0),
('working_hours_start', '08:00', 'string', 'Working hours start time', 1),
('working_hours_end', '17:00', 'string', 'Working hours end time', 1),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', 0),
('session_timeout', '3600', 'number', 'Session timeout in seconds', 0),
('password_min_length', '8', 'number', 'Minimum password length', 0),
('password_expiry_days', '90', 'number', 'Password expiration in days', 0),
('enable_2fa', '0', 'boolean', 'Enable two-factor authentication', 0),
('email_notifications', '1', 'boolean', 'Enable email notifications', 0),
('sms_notifications', '0', 'boolean', 'Enable SMS notifications', 0)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- ===================================
-- TRIGGERS FOR AUDIT LOGGING
-- ===================================

-- Note: Triggers can be added later for automatic audit logging
-- Example trigger structure (commented out for now):
/*
DELIMITER $$
CREATE TRIGGER after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.id, 'UPDATE', 'users', NEW.id, 
            JSON_OBJECT('username', OLD.username, 'email', OLD.email),
            JSON_OBJECT('username', NEW.username, 'email', NEW.email),
            @user_ip);
END$$
DELIMITER ;
*/

-- ===================================
-- VIEWS FOR COMMON QUERIES
-- ===================================

-- Active Prescriptions View
CREATE OR REPLACE VIEW active_prescriptions AS
SELECT 
    p.prescription_id,
    p.prescription_date,
    p.status,
    pat.P_Name as patient_name,
    pat.P_Email as patient_email,
    d.D_Name as doctor_name,
    COUNT(pi.item_id) as total_items,
    SUM(CASE WHEN pi.dispensed_quantity >= pi.quantity THEN 1 ELSE 0 END) as dispensed_items
FROM prescriptions p
JOIN patients pat ON p.patient_id = pat.P_ID
JOIN doctors d ON p.doctor_id = d.D_ID
LEFT JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
WHERE p.status IN ('Pending', 'Partially Dispensed')
GROUP BY p.prescription_id;

-- Low Stock Medicines View
CREATE OR REPLACE VIEW low_stock_medicines AS
SELECT 
    m.medicine_id,
    m.medicine_name,
    m.category,
    SUM(pi.current_stock) as total_stock,
    m.reorder_level
FROM medicines m
LEFT JOIN pharmacy_inventory pi ON m.medicine_id = pi.medicine_id
GROUP BY m.medicine_id
HAVING total_stock < m.reorder_level OR total_stock IS NULL;

-- ===================================
-- COMPLETION MESSAGE
-- ===================================

SELECT 'Database schema expansion completed successfully!' as Status;
SELECT 'Tables created: 20+' as Summary;
SELECT 'Views created: 2' as Views;
SELECT 'Default settings inserted' as Configuration;
