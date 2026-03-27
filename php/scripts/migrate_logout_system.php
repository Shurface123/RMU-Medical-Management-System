<?php
/**
 * migrate_logout_system.php
 * Creates or alters the database tables needed for the Advanced Logout System.
 */
require_once __DIR__ . '/../db_conn.php';

echo "<h2>RMU Advanced Logout System Migration</h2>";

// Helper to safely execute queries and echo result
function executeMigration($conn, $sql, $successMsg) {
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green;'>[SUCCESS] $successMsg</p>";
    } else {
        echo "<p style='color:red;'>[ERROR] Failed: " . mysqli_error($conn) . "</p>";
    }
}

// Helper to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE `$table` ADD `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green;'>[ALTER] Added $column to $table</p>";
        } else {
            echo "<p style='color:red;'>[ERROR] Failed to add $column to $table: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>[SKIP] Column $column already exists in $table</p>";
    }
}

// ── 1. active_sessions (Alter existing or Create) ──────────────────────────
$sql_active_sessions = "
CREATE TABLE IF NOT EXISTS active_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    device_info VARCHAR(255),
    browser VARCHAR(255),
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_current BOOLEAN DEFAULT 1,
    remember_me BOOLEAN DEFAULT 0,
    expires_at DATETIME NULL,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
executeMigration($conn, $sql_active_sessions, "Table active_sessions ensured.");

// Add missing columns to active_sessions if it already existed
addColumnIfNotExists($conn, 'active_sessions', 'device_info', 'VARCHAR(255) NULL AFTER ip_address');
addColumnIfNotExists($conn, 'active_sessions', 'browser', 'VARCHAR(255) NULL AFTER device_info');
// Note: login_time vs logged_in_at. If logged_in_at exists, we use it. If not, add login_time
$q = mysqli_query($conn, "SHOW COLUMNS FROM `active_sessions` LIKE 'login_time'");
if(mysqli_num_rows($q) == 0) {
    $q2 = mysqli_query($conn, "SHOW COLUMNS FROM `active_sessions` LIKE 'logged_in_at'");
    if(mysqli_num_rows($q2) == 0) {
        addColumnIfNotExists($conn, 'active_sessions', 'login_time', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
    }
}
addColumnIfNotExists($conn, 'active_sessions', 'is_current', 'BOOLEAN DEFAULT 1 AFTER last_active');
addColumnIfNotExists($conn, 'active_sessions', 'remember_me', 'BOOLEAN DEFAULT 0 AFTER is_current');
addColumnIfNotExists($conn, 'active_sessions', 'expires_at', 'DATETIME NULL AFTER remember_me');


// ── 2. logout_logs ──────────────────────────────────────────────────────────
$sql_logout_logs = "
CREATE TABLE IF NOT EXISTS logout_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(50),
    session_id VARCHAR(128),
    logout_type ENUM('manual','timeout','forced','admin forced','security') DEFAULT 'manual',
    logout_confirmed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    countdown_duration INT DEFAULT 0,
    ip_address VARCHAR(45),
    device_info VARCHAR(255),
    browser VARCHAR(255),
    dashboard_origin VARCHAR(100),
    health_message_shown TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
executeMigration($conn, $sql_logout_logs, "Table logout_logs ensured.");


// ── 3. health_messages ──────────────────────────────────────────────────────
$sql_health_messages = "
CREATE TABLE IF NOT EXISTS health_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_text TEXT NOT NULL,
    message_category ENUM('wellness','safety','reminder','motivational','health tip') DEFAULT 'wellness',
    target_role VARCHAR(50) NULL COMMENT 'Nullable means all roles',
    is_active BOOLEAN DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
executeMigration($conn, $sql_health_messages, "Table health_messages ensured.");

// Insert default health messages if empty
$chk = mysqli_query($conn, "SELECT COUNT(*) as c FROM health_messages");
if ($chk && mysqli_fetch_assoc($chk)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO health_messages (message_text, message_category, target_role) VALUES 
        ('Wash your hands regularly to prevent the spread of infections.', 'safety', NULL),
        ('Take a moment to stretch and hydrate during your shift.', 'wellness', 'staff'),
        ('Remember to log out securely when leaving your workstation.', 'reminder', NULL),
        ('Your dedication to patient care makes a huge difference. Have a great day!', 'motivational', 'doctor'),
        ('Don\'t forget to complete your clinical notes before leaving.', 'reminder', 'doctor'),
        ('Ensure all patient data is securely locked away.', 'safety', 'nurse')
    ");
    echo "<p style='color:green;'>[DATA] Default health messages inserted.</p>";
}


// ── 4. logout_config ────────────────────────────────────────────────────────
$sql_logout_config = "
CREATE TABLE IF NOT EXISTS logout_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    countdown_duration INT DEFAULT 3,
    confirm_dialog_enabled BOOLEAN DEFAULT 1,
    show_health_message BOOLEAN DEFAULT 1,
    redirect_url VARCHAR(255) DEFAULT '/RMU-Medical-Management-System/php/index.php',
    session_cleanup BOOLEAN DEFAULT 1,
    force_logout_on_password_change BOOLEAN DEFAULT 1,
    updated_by INT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
executeMigration($conn, $sql_logout_config, "Table logout_config ensured.");

// Insert default config if empty
$chk_cfg = mysqli_query($conn, "SELECT COUNT(*) as c FROM logout_config");
if ($chk_cfg && mysqli_fetch_assoc($chk_cfg)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO logout_config (id, countdown_duration) VALUES (1, 3)");
    echo "<p style='color:green;'>[DATA] Default logout configuration inserted.</p>";
}


// ── 5. forced_logout_queue ──────────────────────────────────────────────────
$sql_forced_qty = "
CREATE TABLE IF NOT EXISTS forced_logout_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason VARCHAR(255) DEFAULT 'admin forced',
    queued_by INT NULL COMMENT 'Admin ID or NULL for system',
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME NULL COMMENT 'NULL means not yet processed',
    is_executed BOOLEAN DEFAULT 0,
    INDEX (user_id),
    INDEX(is_executed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
executeMigration($conn, $sql_forced_qty, "Table forced_logout_queue ensured.");

echo "<h3>Migration Complete.</h3>";
?>
