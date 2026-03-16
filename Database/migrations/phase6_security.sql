-- ===========================================
-- PHASE 6: SECURITY INFRASTRUCTURE
-- Brute-Force Protection & Audit Trails
-- ===========================================

-- 1. Create global login attempts tracking table for all users
CREATE TABLE IF NOT EXISTS global_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('login_failed', 'login_success') NOT NULL,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Ensure users table has locking mechanisms
-- Add is_active column if it doesn't already exist (some previous phases might have added to specific roles, but users table needs it globally)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'is_active';

SELECT count(*) INTO @has_is_active 
FROM information_schema.columns 
WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname;

SET @query = IF(@has_is_active = 0, 
    'ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE', 
    'SELECT "is_active column already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add lockout expiration column if it doesn't exist
SET @columnname2 = 'locked_until';

SELECT count(*) INTO @has_locked_until 
FROM information_schema.columns 
WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname2;

SET @query2 = IF(@has_locked_until = 0, 
    'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL', 
    'SELECT "locked_until column already exists"');

PREPARE stmt2 FROM @query2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Verify Staff audit trail (from Phase 5, ensuring it exists for RBAC logging)
CREATE TABLE IF NOT EXISTS staff_audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    staff_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL
);
