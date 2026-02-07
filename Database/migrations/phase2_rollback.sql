-- ===================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- DATABASE ROLLBACK SCRIPT
-- Phase 2: Schema Expansion Rollback
-- ===================================

-- WARNING: This script will DROP all tables created in Phase 2
-- Make sure to backup your database before running this script!

-- ===================================
-- DROP VIEWS
-- ===================================

DROP VIEW IF EXISTS active_prescriptions;
DROP VIEW IF EXISTS low_stock_medicines;

-- ===================================
-- DROP TABLES (in reverse order of dependencies)
-- ===================================

-- System Configuration
DROP TABLE IF EXISTS system_settings;

-- Appointment Enhancements
DROP TABLE IF EXISTS appointment_reminders;

-- Audit & Security
DROP TABLE IF EXISTS password_history;
DROP TABLE IF EXISTS two_factor_auth;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS audit_log;

-- Medical Records
DROP TABLE IF EXISTS lab_results;
DROP TABLE IF EXISTS medical_attachments;
DROP TABLE IF EXISTS medical_records;

-- Notifications
DROP TABLE IF EXISTS sms_queue;
DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS notifications;

-- Pharmacy Management
DROP TABLE IF EXISTS prescription_refills;
DROP TABLE IF EXISTS prescription_items;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS pharmacy_inventory;
DROP TABLE IF EXISTS pharmacy_suppliers;

-- ===================================
-- COMPLETION MESSAGE
-- ===================================

SELECT 'Database rollback completed successfully!' as Status;
SELECT 'All Phase 2 tables have been dropped' as Summary;
SELECT 'Please restore from backup if needed' as Warning;
