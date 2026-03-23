-- =============================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- PHASE 10: LAB TECHNICIAN APPROVAL COLUMNS
-- =============================================

USE `rmu_medical_sickbay`;

-- Add approval columns to lab_technicians table
ALTER TABLE `lab_technicians`
    ADD COLUMN IF NOT EXISTS `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT DEFAULT NULL AFTER `approval_status`,
    ADD COLUMN IF NOT EXISTS `approved_at` DATETIME DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT DEFAULT NULL AFTER `approved_at`,
    ADD CONSTRAINT `fk_lab_tech_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
