-- ============================================================
-- MODULE 10: ADVANCED PATIENT PROFILE — MIGRATION
-- Creates 6 new tables, ALTERs patients table for missing cols
-- Database: rmu_medical_sickbay
-- Safe to re-run: IF NOT EXISTS / IF NOT EXISTS column checks
-- ============================================================

-- ── 1. ALTER patients — add missing profile columns ────────
-- Marital status, nationality, religion, occupation, national ID,
-- secondary phone, street, city, region, country, postal code,
-- last login, online status
ALTER TABLE patients
  ADD COLUMN IF NOT EXISTS marital_status VARCHAR(30) DEFAULT NULL AFTER gender,
  ADD COLUMN IF NOT EXISTS nationality VARCHAR(80) DEFAULT NULL AFTER marital_status,
  ADD COLUMN IF NOT EXISTS religion VARCHAR(60) DEFAULT NULL AFTER nationality,
  ADD COLUMN IF NOT EXISTS occupation VARCHAR(100) DEFAULT NULL AFTER religion,
  ADD COLUMN IF NOT EXISTS national_id VARCHAR(60) DEFAULT NULL AFTER occupation,
  ADD COLUMN IF NOT EXISTS secondary_phone VARCHAR(30) DEFAULT NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS street_address VARCHAR(255) DEFAULT NULL AFTER address,
  ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL AFTER street_address,
  ADD COLUMN IF NOT EXISTS region VARCHAR(100) DEFAULT NULL AFTER city,
  ADD COLUMN IF NOT EXISTS country VARCHAR(80) DEFAULT 'Ghana' AFTER region,
  ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20) DEFAULT NULL AFTER country,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL AFTER updated_at,
  ADD COLUMN IF NOT EXISTS is_online TINYINT(1) DEFAULT 0 AFTER last_login_at,
  ADD COLUMN IF NOT EXISTS profile_completion TINYINT UNSIGNED DEFAULT 0 AFTER is_online,
  ADD COLUMN IF NOT EXISTS account_status ENUM('active','deactivation_requested','deactivated') DEFAULT 'active' AFTER profile_completion;


-- ── 2. patient_medical_profile ─────────────────────────────
CREATE TABLE IF NOT EXISTS patient_medical_profile (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  blood_type      VARCHAR(10) DEFAULT NULL,
  height_cm       DECIMAL(5,1) DEFAULT NULL,
  weight_kg       DECIMAL(5,1) DEFAULT NULL,
  bmi             DECIMAL(4,1) DEFAULT NULL,
  bmi_category    VARCHAR(30) DEFAULT NULL,
  allergies       JSON DEFAULT NULL,
  chronic_conditions JSON DEFAULT NULL,
  disabilities    TEXT DEFAULT NULL,
  current_medications JSON DEFAULT NULL,
  vaccination_history JSON DEFAULT NULL,
  family_medical_history JSON DEFAULT NULL,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmp_patient (patient_id),
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 3. patient_insurance ───────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_insurance (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  provider_name   VARCHAR(150) DEFAULT NULL,
  policy_number   VARCHAR(80) DEFAULT NULL,
  expiry_date     DATE DEFAULT NULL,
  coverage_type   ENUM('Individual','Family') DEFAULT 'Individual',
  payment_preference ENUM('Cash','Insurance','Mobile Money') DEFAULT 'Cash',
  billing_address TEXT DEFAULT NULL,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pi_patient (patient_id),
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 4. patient_documents ───────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_documents (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  file_name       VARCHAR(255) NOT NULL,
  file_path       VARCHAR(500) NOT NULL,
  file_type       VARCHAR(50) DEFAULT NULL,
  file_size       INT UNSIGNED DEFAULT 0,
  description     VARCHAR(255) DEFAULT NULL,
  document_category ENUM('Medical Report','Insurance Card','National ID','Passport','Lab Report','Other') DEFAULT 'Other',
  uploaded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 5. patient_sessions ────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_sessions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  user_id         INT NOT NULL,
  session_token   VARCHAR(128) DEFAULT NULL,
  device_info     VARCHAR(255) DEFAULT NULL,
  browser         VARCHAR(100) DEFAULT NULL,
  ip_address      VARCHAR(45) DEFAULT NULL,
  login_time      DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_active     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_current      TINYINT(1) DEFAULT 0,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 6. patient_activity_log ────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_activity_log (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  user_id         INT NOT NULL,
  action_type     VARCHAR(50) NOT NULL,
  action_description TEXT NOT NULL,
  ip_address      VARCHAR(45) DEFAULT NULL,
  device_info     VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 7. patient_profile_completeness ────────────────────────
CREATE TABLE IF NOT EXISTS patient_profile_completeness (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  personal_info   TINYINT(1) DEFAULT 0,
  medical_profile TINYINT(1) DEFAULT 0,
  emergency_contact TINYINT(1) DEFAULT 0,
  insurance_info  TINYINT(1) DEFAULT 0,
  photo_uploaded  TINYINT(1) DEFAULT 0,
  security_setup  TINYINT(1) DEFAULT 0,
  documents_uploaded TINYINT(1) DEFAULT 0,
  overall_percentage TINYINT UNSIGNED DEFAULT 0,
  last_updated    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ppc_patient (patient_id),
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 8. Seed empty rows for existing patients ───────────────
INSERT IGNORE INTO patient_medical_profile (patient_id)
  SELECT id FROM patients WHERE id NOT IN (SELECT patient_id FROM patient_medical_profile);

INSERT IGNORE INTO patient_insurance (patient_id)
  SELECT id FROM patients WHERE id NOT IN (SELECT patient_id FROM patient_insurance);

INSERT IGNORE INTO patient_profile_completeness (patient_id)
  SELECT id FROM patients WHERE id NOT IN (SELECT patient_id FROM patient_profile_completeness);
