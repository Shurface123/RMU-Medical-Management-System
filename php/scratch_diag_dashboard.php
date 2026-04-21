<?php
session_start();
$_SESSION['user_id'] = 2; // Assuming Joseph's ID
$_SESSION['user_role'] = 'maintenance';
$_SESSION['role'] = 'maintenance';
$_SESSION['name'] = 'Joseph';

// Mock DB
require_once __DIR__ . '/db_conn.php';

// Try to include the dashboard
try {
    include __DIR__ . '/dashboards/staff_dashboard.php';
} catch (Throwable $e) {
    echo "CAUGHT FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
?>
