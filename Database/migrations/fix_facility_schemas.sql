-- =============================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- PHASE 12: FACILITY MODULE SCHEMA FIXES
-- =============================================

USE `rmu_medical_sickbay`;

-- 1. Patients Table Enhancements
ALTER TABLE `patients`
    ADD COLUMN IF NOT EXISTS `ward_department` VARCHAR(100) DEFAULT NULL AFTER `admit_date`,
    ADD COLUMN IF NOT EXISTS `assigned_doctor` INT DEFAULT NULL AFTER `ward_department`,
    ADD CONSTRAINT `fk_patient_assigned_doctor` FOREIGN KEY (`assigned_doctor`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- 2. Cleaning Schedules Table Enhancements
-- Avoid duplicate column definitions if they partially exist
ALTER TABLE `cleaning_schedules`
    ADD COLUMN IF NOT EXISTS `assigned_cleaner_id` INT DEFAULT NULL AFTER `assigned_to`,
    ADD COLUMN IF NOT EXISTS `backup_cleaner_id` INT DEFAULT NULL AFTER `assigned_cleaner_id`,
    ADD COLUMN IF NOT EXISTS `scheduled_time` DATETIME DEFAULT NULL AFTER `end_time`,
    ADD COLUMN IF NOT EXISTS `ward_area` VARCHAR(100) DEFAULT NULL AFTER `ward_room_area`,
    ADD COLUMN IF NOT EXISTS `specific_room` VARCHAR(100) DEFAULT NULL AFTER `ward_area`,
    ADD COLUMN IF NOT EXISTS `location_type` VARCHAR(100) DEFAULT NULL AFTER `specific_room`,
    ADD COLUMN IF NOT EXISTS `floor_building` VARCHAR(100) DEFAULT NULL AFTER `location_type`,
    ADD COLUMN IF NOT EXISTS `contamination_level` VARCHAR(50) DEFAULT 'Low' AFTER `cleaning_type`,
    ADD COLUMN IF NOT EXISTS `required_ppe` TEXT DEFAULT NULL AFTER `contamination_level`,
    ADD COLUMN IF NOT EXISTS `recurrence_pattern` VARCHAR(50) DEFAULT NULL AFTER `required_ppe`,
    ADD COLUMN IF NOT EXISTS `priority` VARCHAR(50) DEFAULT 'Routine' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `special_instructions` TEXT DEFAULT NULL AFTER `priority`,
    ADD CONSTRAINT `fk_clean_assigned_cleaner` FOREIGN KEY (`assigned_cleaner_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_clean_backup_cleaner` FOREIGN KEY (`backup_cleaner_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL;

-- 3. Ensure contamination_reports is fully structured for high-level dashboard
ALTER TABLE `contamination_reports`
    ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'in progress', 'resolved') DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `severity` ENUM('low', 'medium', 'high', 'biohazard') DEFAULT 'low';
