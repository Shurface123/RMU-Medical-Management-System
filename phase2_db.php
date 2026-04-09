<?php
require_once 'php/db_conn.php';

function colExists($conn, $table, $column) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($res) > 0;
}

$queries = [];

// 1. active_sessions
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `active_sessions` (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT,
    user_role VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    logged_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP
)");
if (!colExists($conn, 'active_sessions', 'device_info')) $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `device_info` VARCHAR(255) NULL";
if (!colExists($conn, 'active_sessions', 'browser')) $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `browser` VARCHAR(100) NULL";
if (!colExists($conn, 'active_sessions', 'is_current')) $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `is_current` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'active_sessions', 'remember_me')) $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `remember_me` TINYINT(1) DEFAULT 0";
if (!colExists($conn, 'active_sessions', 'expires_at')) $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `expires_at` DATETIME NULL";
if (!colExists($conn, 'active_sessions', 'role')) {
    // If 'user_role' exists and request asks for 'role', we can just add 'role' or alias it. Adding to strictly follow requirement.
    $queries[] = "ALTER TABLE `active_sessions` ADD COLUMN `role` VARCHAR(50) NULL";
}

// 2. logout_logs
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `logout_logs` (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role VARCHAR(50) NULL,
    session_id VARCHAR(255) NULL,
    logout_type ENUM('manual','timeout','forced','admin forced','security') DEFAULT 'manual',
    logout_confirmed_at DATETIME NULL,
    countdown_duration INT DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    device_info VARCHAR(255) NULL,
    browser VARCHAR(100) NULL,
    dashboard_logged_out_from VARCHAR(255) NULL,
    health_message_shown TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
if (!colExists($conn, 'logout_logs', 'logout_confirmed_at')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `logout_confirmed_at` DATETIME NULL";
if (!colExists($conn, 'logout_logs', 'dashboard_logged_out_from')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `dashboard_logged_out_from` VARCHAR(255) NULL";
if (!colExists($conn, 'logout_logs', 'health_message_shown')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `health_message_shown` TEXT NULL";
if (!colExists($conn, 'logout_logs', 'device_info')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `device_info` VARCHAR(255) NULL";
if (!colExists($conn, 'logout_logs', 'browser')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `browser` VARCHAR(100) NULL";
if (!colExists($conn, 'logout_logs', 'countdown_duration')) $queries[] = "ALTER TABLE `logout_logs` ADD COLUMN `countdown_duration` INT DEFAULT 0";

// 3. health_messages
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `health_messages` (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    message_text TEXT NOT NULL,
    message_category ENUM('wellness','safety','reminder','motivational','health tip') DEFAULT 'wellness',
    target_role VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
if (!colExists($conn, 'health_messages', 'message_category')) $queries[] = "ALTER TABLE `health_messages` ADD COLUMN `message_category` ENUM('wellness','safety','reminder','motivational','health tip') DEFAULT 'wellness'";
if (!colExists($conn, 'health_messages', 'target_role')) $queries[] = "ALTER TABLE `health_messages` ADD COLUMN `target_role` VARCHAR(50) NULL";
if (!colExists($conn, 'health_messages', 'is_active')) $queries[] = "ALTER TABLE `health_messages` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'health_messages', 'created_by')) $queries[] = "ALTER TABLE `health_messages` ADD COLUMN `created_by` INT NULL";

// 4. logout_config
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `logout_config` (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    countdown_duration_seconds INT DEFAULT 3,
    confirmation_dialog_enabled TINYINT(1) DEFAULT 1,
    show_health_message TINYINT(1) DEFAULT 1,
    redirect_url VARCHAR(255) DEFAULT '/RMU-Medical-Management-System/php/index.php',
    session_cleanup TINYINT(1) DEFAULT 1,
    force_logout_on_password_change TINYINT(1) DEFAULT 1,
    updated_by INT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
if (!colExists($conn, 'logout_config', 'session_cleanup')) $queries[] = "ALTER TABLE `logout_config` ADD COLUMN `session_cleanup` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'logout_config', 'force_logout_on_password_change')) $queries[] = "ALTER TABLE `logout_config` ADD COLUMN `force_logout_on_password_change` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'logout_config', 'confirmation_dialog_enabled')) $queries[] = "ALTER TABLE `logout_config` ADD COLUMN `confirmation_dialog_enabled` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'logout_config', 'show_health_message')) $queries[] = "ALTER TABLE `logout_config` ADD COLUMN `show_health_message` TINYINT(1) DEFAULT 1";
if (!colExists($conn, 'logout_config', 'countdown_duration_seconds')) $queries[] = "ALTER TABLE `logout_config` ADD COLUMN `countdown_duration_seconds` INT DEFAULT 3";

// Ensure seed data for logout_config
$cfgCheck = mysqli_query($conn, "SELECT COUNT(*) as c FROM `logout_config`");
if ($cfgCheck && mysqli_fetch_assoc($cfgCheck)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO `logout_config` (countdown_duration_seconds, confirmation_dialog_enabled, show_health_message, redirect_url) VALUES (3, 1, 1, '/RMU-Medical-Management-System/php/index.php')");
}

// 5. forced_logout_queue
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `forced_logout_queue` (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason VARCHAR(255) NULL,
    queued_by INT NULL,
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME NULL,
    is_executed TINYINT(1) DEFAULT 0
)");
if (!colExists($conn, 'forced_logout_queue', 'queued_by')) $queries[] = "ALTER TABLE `forced_logout_queue` ADD COLUMN `queued_by` INT NULL";
if (!colExists($conn, 'forced_logout_queue', 'executed_at')) $queries[] = "ALTER TABLE `forced_logout_queue` ADD COLUMN `executed_at` DATETIME NULL";
if (!colExists($conn, 'forced_logout_queue', 'is_executed')) $queries[] = "ALTER TABLE `forced_logout_queue` ADD COLUMN `is_executed` TINYINT(1) DEFAULT 0";

foreach($queries as $q) {
    if(!mysqli_query($conn, $q)) {
        echo "Error on: $q -> " . mysqli_error($conn) . "\n";
    }
}

echo "SUCCESS";
