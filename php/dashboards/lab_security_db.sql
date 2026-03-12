-- ============================================================
-- PHASE 5: DB-LEVEL SECURITY — Immutable Audit Trail
-- RMU Medical Management System / Lab Technician Dashboard
-- Run this once against your MySQL database as root/admin.
-- ============================================================

-- 1. Make lab_audit_trail INSERT-ONLY via triggers
--    (prevents anyone — including admin — from editing or deleting records)

DELIMITER $$

DROP TRIGGER IF EXISTS prevent_audit_update$$
CREATE TRIGGER prevent_audit_update
BEFORE UPDATE ON lab_audit_trail
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'lab_audit_trail is immutable — UPDATE is forbidden';
END$$

DROP TRIGGER IF EXISTS prevent_audit_delete$$
CREATE TRIGGER prevent_audit_delete
BEFORE DELETE ON lab_audit_trail
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'lab_audit_trail is immutable — DELETE is forbidden';
END$$

DELIMITER ;


-- 2. Result Validation Gate (trigger-level)
--    Prevents setting result_status='Released' unless current status='Validated'

DELIMITER $$

DROP TRIGGER IF EXISTS enforce_result_validated_gate$$
CREATE TRIGGER enforce_result_validated_gate
BEFORE UPDATE ON lab_results_v2
FOR EACH ROW
BEGIN
    IF NEW.result_status = 'Released' AND OLD.result_status != 'Validated' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Result must be Validated before it can be Released';
    END IF;
END$$

DELIMITER ;


-- 3. Revoke direct UPDATE/DELETE on audit trail from the application DB user
--    Replace 'rmu_app_user'@'localhost' with your actual application DB username.
--    (Run as MySQL root)

-- REVOKE UPDATE, DELETE ON `rmu_medical`.`lab_audit_trail` FROM 'rmu_app_user'@'localhost';
-- FLUSH PRIVILEGES;
-- NOTE: Uncomment and run the above two lines once you know your app DB username.


-- 4. Add index for brute-force lookups (performance)
ALTER TABLE lab_audit_trail
    ADD INDEX IF NOT EXISTS idx_tech_action_created (technician_id, action_type, created_at);

-- 5. Add index for cross-dashboard notification lookups
ALTER TABLE notifications
    ADD INDEX IF NOT EXISTS idx_user_role_read (user_id, user_role, is_read);

ALTER TABLE lab_notifications
    ADD INDEX IF NOT EXISTS idx_recipient_read (recipient_id, is_read);

-- 6. Add dedup index for TAT alerts
ALTER TABLE lab_notifications
    ADD INDEX IF NOT EXISTS idx_type_related (type, related_id, created_at);
