<?php
/**
 * staff_actions.php — RMU Medical Sickbay
 * Central AJAX handler for the complete Staff Dashboard.
 * All 15 modules handled here. POST only.
 */
define('AJAX_REQUEST', true);
require_once 'staff_security.php';

// Prevent any accidental output (notices/warnings) from corrupting the JSON response
ob_start();

// ── Export requests arrive via GET (browser download link) ──
$is_export = (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'export_report'
);

if ($is_export) {
    // Export mode — read params from GET, skip CSRF (download link)
    $action    = 'export_report';
    $user_id   = (int)$_SESSION['user_id'];
    $staffRole = $_SESSION['user_role'] ?? 'staff';
    // skip JSON header — will be overridden by download headers
} else {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method Not Allowed', 405);

    $action   = sanitize($_POST['action'] ?? '');
    $user_id  = (int)$_SESSION['user_id'];
    $staffRole = $_SESSION['user_role'] ?? 'staff';

    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        json_err('Invalid Security Token (CSRF). Refresh page and try again.', 403);
    }
} // end else (non-export POST block)

// Get staff record (required for most actions)
$staff = dbRow($conn, "SELECT s.*, r.role_display_name, r.icon_class FROM staff s LEFT JOIN staff_roles r ON s.role=r.role_slug WHERE s.user_id=? LIMIT 1", "i", [$user_id]);
$staff_id = $staff ? (int)$staff['id'] : 0;

if (!$staff_id && !in_array($action, ['create_staff_profile', 'health_check'])) {
    json_err('Staff profile not found. Contact admin.', 403);
}

// ── Modular Action Dispatcher ────────────────────────────────
$module_map = [
    // Identity & Profile
    'update_personal_info'       => 'actions_profile.php',
    'upload_photo'               => 'actions_profile.php',
    'save_qualification'         => 'actions_profile.php',
    'delete_qualification'       => 'actions_profile.php',
    'upload_document'            => 'actions_profile.php',
    'delete_document'            => 'actions_profile.php',
    'compute_completeness'       => 'actions_profile.php',

    // Logistics & Transportation
    'submit_leave_request'       => 'actions_logistics.php',
    'accept_trip_request'        => 'actions_logistics.php',
    'reject_trip_request'        => 'actions_logistics.php',
    'update_trip_status'         => 'actions_logistics.php',
    'log_fuel'                   => 'actions_logistics.php',
    'report_vehicle_issue'       => 'actions_logistics.php',

    // Facility, Maintenance & Security
    'accept_maintenance_request'  => 'actions_facility.php',
    'update_maintenance_status'  => 'actions_facility.php',
    'log_patrol_checkin'         => 'actions_facility.php',
    'report_incident'            => 'actions_facility.php',
    'log_visitor'                => 'actions_facility.php',
    'log_visitor_exit'           => 'actions_facility.php',

    // Operations (Sanitation & Laundry)
    'start_cleaning'             => 'actions_operations.php',
    'complete_cleaning'          => 'actions_operations.php',
    'report_contamination'       => 'actions_operations.php',
    'register_laundry_batch'     => 'actions_operations.php',
    'update_batch_status'        => 'actions_operations.php',
    'report_laundry_damage'      => 'actions_operations.php',

    // Clinical & Kitchen Support
    'update_kitchen_task_status' => 'actions_clinical_support.php',
    'report_dietary_issue'       => 'actions_clinical_support.php',

    // Communication, Tasks & Security Settings
    'update_task_status'         => 'actions_communication.php',
    'complete_task_checklist'    => 'actions_communication.php',
    'mark_notification_read'     => 'actions_communication.php',
    'send_message'               => 'actions_communication.php',
    'get_available_recipients'   => 'actions_communication.php',
    'mark_message_read'          => 'actions_communication.php',
    'update_password'            => 'actions_communication.php',
    'save_settings'              => 'actions_communication.php',
    'toggle_2fa'                 => 'actions_communication.php',
    'logout_session'             => 'actions_communication.php',
    'logout_all_sessions'        => 'actions_communication.php',

    // Reporting Engine
    'export_report'              => 'actions_reports.php',
    'get_report'                 => 'actions_reports.php',
];

if (isset($module_map[$action])) {
    require_once "actions_staff/{$module_map[$action]}";
    exit;
}

// Default Fallback
json_err("Invalid or unrecognized action: $action", 400);