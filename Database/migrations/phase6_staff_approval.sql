-- ============================================================
--  Phase 6: Staff Approval Flow — Migration
--  RMU Medical Management System
--  Date: 2026-03-14
-- ============================================================
-- Run AFTER phase5_staff_dashboard.sql

-- 1. Widen users.user_role enum to include all 6 staff sub-roles
--    (safe to re-run; IF NOT already done by phase5)
ALTER TABLE `users`
  MODIFY COLUMN `user_role`
    ENUM(
      'admin','doctor','patient','staff','pharmacist','nurse','lab_technician',
      'ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff'
    )
    COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient';

-- 2. Add approval columns to staff table (safe; uses IF NOT EXISTS guard)
SET @dbname = DATABASE();

-- approval_status
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='staff' AND COLUMN_NAME='approval_status');
SET @sql = IF(@col=0,
  'ALTER TABLE `staff` ADD COLUMN `approval_status` ENUM(''pending'',''approved'',''rejected'') NOT NULL DEFAULT ''pending'' AFTER `status`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- approved_by
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='staff' AND COLUMN_NAME='approved_by');
SET @sql = IF(@col=0,
  'ALTER TABLE `staff` ADD COLUMN `approved_by` INT UNSIGNED NULL AFTER `approval_status`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- approved_at
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='staff' AND COLUMN_NAME='approved_at');
SET @sql = IF(@col=0,
  'ALTER TABLE `staff` ADD COLUMN `approved_at` DATETIME NULL AFTER `approved_by`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rejection_reason
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='staff' AND COLUMN_NAME='rejection_reason');
SET @sql = IF(@col=0,
  'ALTER TABLE `staff` ADD COLUMN `rejection_reason` TEXT NULL AFTER `approved_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Set existing staff records to 'approved' so current accounts are not locked out
UPDATE `staff` SET `approval_status` = 'approved'
WHERE `approval_status` = 'pending'
  AND `created_at` < NOW() - INTERVAL 1 HOUR;  -- Only pre-existing accounts

-- 4. Add index for fast approval lookups
CREATE INDEX IF NOT EXISTS `idx_staff_approval` ON `staff` (`approval_status`, `user_id`);

-- 5. Widen user_sessions.user_role to VARCHAR so it can hold sub-role strings > 13 chars
ALTER TABLE `user_sessions`
  MODIFY COLUMN `user_role` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL;

-- 6. Create staff_approval_log for admin audit trail
CREATE TABLE IF NOT EXISTS `staff_approval_log` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `staff_id`        INT UNSIGNED    NOT NULL,
  `admin_user_id`   INT UNSIGNED    NOT NULL,
  `action`          ENUM('approved','rejected','revoked') NOT NULL,
  `reason`          TEXT            NULL,
  `actioned_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sal_staff` (`staff_id`),
  INDEX `idx_sal_admin` (`admin_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
