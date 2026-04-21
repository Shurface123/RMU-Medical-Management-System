<?php
chdir(__DIR__ . '/dashboards');
session_start();
$_SESSION['user_id'] = 2; 
$_SESSION['user_role'] = 'maintenance';
$_SESSION['role'] = 'maintenance';
$_SESSION['name'] = 'Joseph';

require_once '../db_conn.php';
$_SESSION['csrf_token'] = 'test_token';

// We can't easily include it because it might exit or redirect
// But we can check for common pitfalls.

echo "Checking dependencies...\n";
if (file_exists('../includes/auth_middleware.php')) echo "auth_middleware found.\n";
if (file_exists('../db_conn.php')) echo "db_conn found.\n";

echo "Checking tabs...\n";
$tabs = ['tab_overview', 'tab_tasks', 'tab_schedule', 'tab_messages', 'tab_notifications', 'tab_analytics', 'tab_reports', 'tab_profile', 'tab_settings', 'tab_maintenance'];
foreach ($tabs as $t) {
    $p = 'staff_tabs/' . $t . '.php';
    if (file_exists($p)) {
        echo "OK: $t\n";
    } else {
        echo "MISSING: $t\n";
    }
}
?>
