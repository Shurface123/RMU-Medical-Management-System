-- ============================================================
-- PHARMACY DASHBOARD — DATABASE MIGRATION
-- RMU Medical Management System
-- Idempotent: safe to re-run. ALTERs existing tables, CREATEs new ones.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ════════════════════════════════════════════════════════════
-- 1. ALTER `medicines` — add missing columns
-- ════════════════════════════════════════════════════════════

-- storage_instructions
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicines' AND COLUMN_NAME='storage_instructions');
SET @sql = IF(@col_exists=0, 'ALTER TABLE medicines ADD COLUMN storage_instructions TEXT NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- side_effects
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicines' AND COLUMN_NAME='side_effects');
SET @sql = IF(@col_exists=0, 'ALTER TABLE medicines ADD COLUMN side_effects TEXT NULL AFTER storage_instructions', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- contraindications
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicines' AND COLUMN_NAME='contraindications');
SET @sql = IF(@col_exists=0, 'ALTER TABLE medicines ADD COLUMN contraindications TEXT NULL AFTER side_effects', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- status (active/discontinued)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicines' AND COLUMN_NAME='status');
SET @sql = IF(@col_exists=0, "ALTER TABLE medicines ADD COLUMN status ENUM('active','discontinued') NOT NULL DEFAULT 'active' AFTER is_prescription_required", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ════════════════════════════════════════════════════════════
-- 2. ALTER `pharmacy_inventory` — add missing columns
-- ════════════════════════════════════════════════════════════

-- quantity_sold
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_inventory' AND COLUMN_NAME='quantity_sold');
SET @sql = IF(@col_exists=0, 'ALTER TABLE pharmacy_inventory ADD COLUMN quantity_sold INT NOT NULL DEFAULT 0 AFTER quantity_dispensed', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- quantity_expired
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_inventory' AND COLUMN_NAME='quantity_expired');
SET @sql = IF(@col_exists=0, 'ALTER TABLE pharmacy_inventory ADD COLUMN quantity_expired INT NOT NULL DEFAULT 0 AFTER quantity_sold', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- location (shelf/rack)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_inventory' AND COLUMN_NAME='location');
SET @sql = IF(@col_exists=0, "ALTER TABLE pharmacy_inventory ADD COLUMN location VARCHAR(100) NULL AFTER selling_price", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- status
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_inventory' AND COLUMN_NAME='status');
SET @sql = IF(@col_exists=0, "ALTER TABLE pharmacy_inventory ADD COLUMN status ENUM('in_stock','low_stock','out_of_stock','expired','expiring_soon') NOT NULL DEFAULT 'in_stock' AFTER location", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ════════════════════════════════════════════════════════════
-- 3. ALTER `pharmacy_suppliers` — add missing columns
-- ════════════════════════════════════════════════════════════

-- supply_categories
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_suppliers' AND COLUMN_NAME='supply_categories');
SET @sql = IF(@col_exists=0, 'ALTER TABLE pharmacy_suppliers ADD COLUMN supply_categories VARCHAR(500) NULL AFTER address', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- payment_terms
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_suppliers' AND COLUMN_NAME='payment_terms');
SET @sql = IF(@col_exists=0, "ALTER TABLE pharmacy_suppliers ADD COLUMN payment_terms VARCHAR(200) NULL DEFAULT 'Net 30' AFTER supply_categories", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rating
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pharmacy_suppliers' AND COLUMN_NAME='rating');
SET @sql = IF(@col_exists=0, 'ALTER TABLE pharmacy_suppliers ADD COLUMN rating DECIMAL(2,1) NULL DEFAULT 0.0 AFTER payment_terms', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ════════════════════════════════════════════════════════════
-- 4. ALTER `prescriptions` — ensure pharmacist/dispensing columns
-- ════════════════════════════════════════════════════════════

-- pharmacist_id (dispensed_by already exists but let's add explicit pharmacist_id)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='prescriptions' AND COLUMN_NAME='pharmacist_id');
SET @sql = IF(@col_exists=0, 'ALTER TABLE prescriptions ADD COLUMN pharmacist_id INT NULL AFTER doctor_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expand status enum to include 'Partially Dispensed' and 'Expired'
-- We do this safely: only if the column doesn't already have the values
SET @col_type = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='prescriptions' AND COLUMN_NAME='status');
SET @sql = IF(@col_type NOT LIKE '%Partially Dispensed%', "ALTER TABLE prescriptions MODIFY COLUMN status ENUM('Pending','Dispensed','Partially Dispensed','Cancelled','Expired') DEFAULT 'Pending'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ════════════════════════════════════════════════════════════
-- 5. ALTER `prescription_items` — add missing columns
-- ════════════════════════════════════════════════════════════

-- substitution_allowed
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='prescription_items' AND COLUMN_NAME='substitution_allowed');
SET @sql = IF(@col_exists=0, 'ALTER TABLE prescription_items ADD COLUMN substitution_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER instructions', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- status
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='prescription_items' AND COLUMN_NAME='status');
SET @sql = IF(@col_exists=0, "ALTER TABLE prescription_items ADD COLUMN status ENUM('pending','dispensed','partially_dispensed','cancelled','out_of_stock') NOT NULL DEFAULT 'pending' AFTER substitution_allowed", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ════════════════════════════════════════════════════════════
-- 6. CREATE `dispensing_records`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS dispensing_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    patient_id      INT NOT NULL,
    pharmacist_id   INT NOT NULL,
    medicine_id     INT NOT NULL,
    quantity_dispensed INT NOT NULL DEFAULT 0,
    dispensing_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    selling_price   DECIMAL(10,2) NULL DEFAULT 0.00,
    payment_status  ENUM('paid','unpaid','insurance') NOT NULL DEFAULT 'unpaid',
    notes           TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_disp_prescription (prescription_id),
    INDEX idx_disp_patient (patient_id),
    INDEX idx_disp_pharmacist (pharmacist_id),
    INDEX idx_disp_medicine (medicine_id),
    INDEX idx_disp_date (dispensing_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 7. CREATE `stock_transactions`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS stock_transactions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id       INT NOT NULL,
    inventory_id      INT NULL,
    transaction_type  ENUM('restock','dispensed','expired','adjusted','returned','damaged') NOT NULL,
    quantity          INT NOT NULL DEFAULT 0,
    previous_quantity INT NOT NULL DEFAULT 0,
    new_quantity      INT NOT NULL DEFAULT 0,
    performed_by      INT NOT NULL,
    transaction_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes             TEXT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stx_medicine (medicine_id),
    INDEX idx_stx_type (transaction_type),
    INDEX idx_stx_date (transaction_date),
    INDEX idx_stx_user (performed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 8. CREATE `stock_alerts`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS stock_alerts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id     INT NOT NULL,
    alert_type      ENUM('low_stock','out_of_stock','expiring_soon','expired') NOT NULL,
    threshold_value INT NULL DEFAULT 0,
    current_value   INT NULL DEFAULT 0,
    is_resolved     TINYINT(1) NOT NULL DEFAULT 0,
    resolved_by     INT NULL,
    resolved_at     DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_medicine (medicine_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_alert_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 9. CREATE `purchase_orders`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS purchase_orders (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    order_number          VARCHAR(50) NOT NULL,
    supplier_id           INT NOT NULL,
    ordered_by            INT NOT NULL,
    order_date            DATE NOT NULL,
    expected_delivery_date DATE NULL,
    actual_delivery_date  DATE NULL,
    status                ENUM('draft','sent','received','partially_received','cancelled') NOT NULL DEFAULT 'draft',
    total_amount          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes                 TEXT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_order_number (order_number),
    INDEX idx_po_supplier (supplier_id),
    INDEX idx_po_status (status),
    INDEX idx_po_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 10. CREATE `purchase_order_items`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    medicine_id     INT NOT NULL,
    ordered_quantity INT NOT NULL DEFAULT 0,
    received_quantity INT NOT NULL DEFAULT 0,
    unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status          ENUM('pending','received','partial','cancelled') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_poi_order (order_id),
    INDEX idx_poi_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 11. CREATE `pharmacy_reports`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacy_reports (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    generated_by  INT NOT NULL,
    report_type   VARCHAR(100) NOT NULL,
    parameters    JSON NULL,
    file_path     VARCHAR(500) NULL,
    format        ENUM('PDF','CSV','XLSX') NOT NULL DEFAULT 'PDF',
    generated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pr_user (generated_by),
    INDEX idx_pr_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 12. CREATE `pharmacy_settings`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacy_settings (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id           INT NOT NULL,
    notif_new_prescription  TINYINT(1) NOT NULL DEFAULT 1,
    notif_low_stock         TINYINT(1) NOT NULL DEFAULT 1,
    notif_expiring_meds     TINYINT(1) NOT NULL DEFAULT 1,
    notif_purchase_orders   TINYINT(1) NOT NULL DEFAULT 1,
    notif_refill_requests   TINYINT(1) NOT NULL DEFAULT 1,
    notif_system_alerts     TINYINT(1) NOT NULL DEFAULT 1,
    notification_prefs      JSON NULL,
    preferred_channel       ENUM('dashboard','email','sms','all') NOT NULL DEFAULT 'dashboard',
    theme_preference        VARCHAR(20) NOT NULL DEFAULT 'light',
    language                VARCHAR(30) NOT NULL DEFAULT 'English',
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pharm_settings (pharmacist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 13. CREATE `pharmacist_profile`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_profile (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    full_name           VARCHAR(200) NOT NULL,
    license_number      VARCHAR(100) NULL,
    license_expiry      DATE NULL,
    specialization      VARCHAR(200) NULL,
    department          VARCHAR(100) NULL DEFAULT 'Pharmacy',
    phone               VARCHAR(20) NULL,
    secondary_phone     VARCHAR(20) NULL,
    email               VARCHAR(150) NULL,
    address             TEXT NULL,
    city                VARCHAR(100) NULL,
    region              VARCHAR(100) NULL,
    country             VARCHAR(100) NULL DEFAULT 'Ghana',
    profile_photo       VARCHAR(500) NULL,
    bio                 TEXT NULL,
    years_of_experience INT NOT NULL DEFAULT 0,
    nationality         VARCHAR(100) NULL,
    national_id         VARCHAR(50) NULL,
    date_of_birth       DATE NULL,
    gender              ENUM('Male','Female','Other') NULL,
    marital_status      VARCHAR(30) NULL,
    availability_status ENUM('Online','Offline','Busy') NOT NULL DEFAULT 'Offline',
    profile_completion  INT NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pharm_user (user_id),
    INDEX idx_pharm_license (license_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 14. CREATE `pharmacist_qualifications`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_qualifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id   INT NOT NULL,
    degree_name     VARCHAR(200) NOT NULL,
    institution     VARCHAR(300) NOT NULL,
    year_awarded    INT NULL,
    cert_file_path  VARCHAR(500) NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pq_pharmacist (pharmacist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 15. CREATE `pharmacist_documents`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_documents (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id   INT NOT NULL,
    file_name       VARCHAR(300) NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    file_type       VARCHAR(50) NULL,
    file_size       INT NOT NULL DEFAULT 0,
    description     VARCHAR(500) NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pd_pharmacist (pharmacist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 16. CREATE `pharmacist_sessions`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_sessions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id   INT NOT NULL,
    session_token   VARCHAR(255) NULL,
    device_info     VARCHAR(300) NULL,
    browser         VARCHAR(200) NULL,
    ip_address      VARCHAR(45) NULL,
    login_time      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active     DATETIME NULL,
    is_current      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_ps_pharmacist (pharmacist_id),
    INDEX idx_ps_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 17. CREATE `pharmacist_activity_log`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_activity_log (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id       INT NOT NULL,
    action_type         VARCHAR(100) NOT NULL DEFAULT 'general',
    action_description  TEXT NOT NULL,
    ip_address          VARCHAR(45) NULL,
    device_info         VARCHAR(300) NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pal_pharmacist (pharmacist_id),
    INDEX idx_pal_type (action_type),
    INDEX idx_pal_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 18. CREATE `pharmacist_profile_completeness`
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pharmacist_profile_completeness (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id         INT NOT NULL,
    personal_info         TINYINT(1) NOT NULL DEFAULT 0,
    professional_profile  TINYINT(1) NOT NULL DEFAULT 0,
    qualifications        TINYINT(1) NOT NULL DEFAULT 0,
    photo_uploaded        TINYINT(1) NOT NULL DEFAULT 0,
    security_setup        TINYINT(1) NOT NULL DEFAULT 0,
    documents_uploaded    TINYINT(1) NOT NULL DEFAULT 0,
    overall_pct           INT NOT NULL DEFAULT 0,
    last_updated          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ppc_pharmacist (pharmacist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 19. Seed pharmacist_profile for existing pharmacist users
-- ════════════════════════════════════════════════════════════
INSERT IGNORE INTO pharmacist_profile (user_id, full_name, email, phone)
SELECT u.id, u.name, u.email, u.phone
FROM users u
WHERE u.user_role = 'pharmacist';

-- Seed profile completeness
INSERT IGNORE INTO pharmacist_profile_completeness (pharmacist_id)
SELECT pp.id FROM pharmacist_profile pp
WHERE pp.id NOT IN (SELECT pharmacist_id FROM pharmacist_profile_completeness);

-- Seed settings
INSERT IGNORE INTO pharmacy_settings (pharmacist_id)
SELECT pp.id FROM pharmacist_profile pp
WHERE pp.id NOT IN (SELECT pharmacist_id FROM pharmacy_settings);


-- ════════════════════════════════════════════════════════════
-- 20. CREATE OR REPLACE `medicine_inventory` VIEW
-- (Updated to include new status column)
-- ════════════════════════════════════════════════════════════
-- Note: Only run if medicine_inventory is a VIEW not a table
-- Check manually; the existing system uses this view for the pharmacy/doctor dashboards

-- Done. All tables created/altered.
-- Run this script in phpMyAdmin or MySQL CLI.
