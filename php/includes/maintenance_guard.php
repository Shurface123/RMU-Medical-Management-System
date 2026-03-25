<?php
/**
 * RMU Medical Sickbay — Maintenance Guard
 * Redirects non-admin users if system is in maintenance mode.
 */

if (!function_exists('get_setting')) {
    /**
     * Fallback get_setting if db_conn.php wasn't loaded properly
     */
    function get_setting($key, $default = '0') {
        global $conn;
        if (!$conn) return $default;
        
        // Try system_config first (it's the new standard)
        $res = @mysqli_query($conn, "SELECT config_value FROM system_config WHERE config_key = '".mysqli_real_escape_string($conn, $key)."' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return $row['config_value'] ?? $default;
        }

        // Fallback to hospital_settings (legacy support)
        // Use @ to suppress errors if columns are still missing in some environments
        $res = @mysqli_query($conn, "SELECT `$key` FROM hospital_settings LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return $row[$key] ?? $default;
        }
        return $default;
    }
}

// Only enforce if maintenance is ON
if (get_setting('maintenance_mode', '0') === '1') {
    // Admins are EXEMPT
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Prevent redirect loop if already on maintenance.php
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'maintenance.php' && $current_page !== 'login.php' && $current_page !== 'index.php') {
            // Only redirect if NOT an AJAX request
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                header("Location: /RMU-Medical-Management-System/php/maintenance.php");
                exit();
            }
        }
    }
}
