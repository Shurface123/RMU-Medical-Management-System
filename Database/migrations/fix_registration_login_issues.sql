-- ============================================================
-- FIX: Registration & Login Portal Issues
-- RMU Medical Sickbay System — April 2026
-- ============================================================
-- Run this migration ONCE on your database before deploying
-- the fixed PHP files.
-- ============================================================

-- 1. Fix existing users with broken account_status
--    Users who completed OTP but got stuck with 'inactive' status
UPDATE users
SET account_status = 'active',
    is_active = 1,
    is_verified = 1
WHERE is_verified = 1
  AND account_status IN ('inactive', 'pending_verification')
  AND user_role NOT IN ('doctor', 'nurse', 'lab_technician', 'pharmacist');

-- 2. Fix clinical staff (doctors/nurses/lab/pharmacy) who completed OTP
--    They should be 'pending' (awaiting admin approval), not 'inactive'
UPDATE users u
SET u.account_status = 'pending'
WHERE u.is_verified = 1
  AND u.account_status = 'inactive'
  AND u.user_role IN ('doctor', 'nurse', 'lab_technician', 'pharmacist');

-- 3. Ensure admin-created users are always active
UPDATE users
SET account_status = 'active',
    is_active = 1,
    is_verified = 1
WHERE account_status IS NULL OR account_status = '';

-- 4. Create email_queue_log table for tracking email delivery
CREATE TABLE IF NOT EXISTS `email_queue_log` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `to_email`      VARCHAR(255) NOT NULL,
    `email_type`    VARCHAR(80)  NOT NULL COMMENT 'password_reset, otp, welcome, 2fa, etc.',
    `status`        ENUM('sent','failed','queued') NOT NULL DEFAULT 'queued',
    `error_message` TEXT         DEFAULT NULL,
    `sent_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email_type` (`to_email`, `email_type`),
    INDEX `idx_sent_at`    (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks all outbound email delivery attempts';

-- 5. Ensure password_resets table exists with correct schema
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `token_hash`  VARCHAR(64)  NOT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `is_used`     TINYINT(1)   NOT NULL DEFAULT 0,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token_hash` (`token_hash`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_expires`   (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Ensure registration_sessions table exists
CREATE TABLE IF NOT EXISTS `registration_sessions` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_token` VARCHAR(64)  NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `role`          VARCHAR(50)  NOT NULL,
    `step_reached`  TINYINT      NOT NULL DEFAULT 1,
    `temp_data`     LONGTEXT     DEFAULT NULL,
    `expires_at`    DATETIME     NOT NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_session_token` (`session_token`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Ensure email_verifications table has all required columns
ALTER TABLE `email_verifications`
    MODIFY COLUMN `user_id` INT UNSIGNED DEFAULT NULL,
    MODIFY COLUMN `verification_type` VARCHAR(50) NOT NULL DEFAULT 'registration';

-- Done
SELECT 'Migration fix_registration_login_issues.sql applied successfully.' AS migration_status;
