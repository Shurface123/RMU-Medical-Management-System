<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'dispatch_cleaner') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or action.']);
    exit();
}

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
    exit();
}

// Validate required fields
$loc_type = trim($_POST['location_type'] ?? '');
$ward = trim($_POST['ward_area'] ?? '');
$clean_type = trim($_POST['cleaning_type'] ?? '');
$contam_level = trim($_POST['contamination_level'] ?? '');
$dispatch_type = trim($_POST['dispatch_type'] ?? 'immediate');
$duration = (int)($_POST['estimated_duration'] ?? 30);
$assignee = (int)($_POST['assigned_cleaner_id'] ?? 0);
$priority = trim($_POST['priority'] ?? 'Routine');

if (empty($loc_type) || empty($ward) || empty($clean_type) || empty($contam_level) || empty($assignee)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Optional fields
$room = trim($_POST['specific_room'] ?? '');
$floor = trim($_POST['floor_building'] ?? '');
$hazards_json = isset($_POST['hazard_flags']) ? json_encode($_POST['hazard_flags']) : json_encode([]);
$sched_time = null;
if ($dispatch_type === 'scheduled') {
    $d = trim($_POST['scheduled_date'] ?? date('Y-m-d'));
    $t = trim($_POST['scheduled_time'] ?? date('H:i'));
    $sched_time = "$d $t:00";
} else {
    $sched_time = date('Y-m-d H:i:s');
}
$recur = trim($_POST['recurrence_pattern'] ?? '');
$backup = !empty($_POST['backup_cleaner_id']) ? (int)$_POST['backup_cleaner_id'] : null;
$ppe_json = isset($_POST['required_ppe']) ? json_encode($_POST['required_ppe']) : json_encode([]);
$notes = trim($_POST['special_instructions'] ?? '');
$reported_by = $_SESSION['user_id'] ?? 0;

// Transactions
mysqli_begin_transaction($conn);
try {
    $sql = "INSERT INTO cleaning_schedules 
        (location_type, ward_area, specific_room, floor_building, cleaning_type, contamination_level, hazard_flags, dispatch_type, scheduled_time, estimated_duration, recurrence_pattern, assigned_cleaner_id, backup_cleaner_id, priority, required_ppe, special_instructions, reported_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Dispatched')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssisiisssi", 
        $loc_type, $ward, $room, $floor, $clean_type, $contam_level, $hazards_json, 
        $dispatch_type, $sched_time, $duration, $recur, $assignee, $backup, 
        $priority, $ppe_json, $notes, $reported_by
    );
    mysqli_stmt_execute($stmt);
    $schedule_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // If biohazard or high risk, auto-generate a contamination report
    if ($contam_level === 'Biohazard' || $clean_type === 'Biohazard Clean' || count(json_decode($hazards_json)) > 0) {
        $c_sql = "INSERT INTO contamination_reports (reported_by, location, contamination_type, severity, description, status) VALUES (?, ?, 'biohazard', 'critical', ?, 'reported')";
        $c_stmt = mysqli_prepare($conn, $c_sql);
        $full_loc = $ward . ($room ? " - $room" : "");
        $c_desc = "Auto-generated from Cleaner Dispatch: $clean_type. Hazards: " . implode(', ', json_decode($hazards_json));
        mysqli_stmt_bind_param($c_stmt, "isss", $reported_by, $full_loc, $c_desc);
        mysqli_stmt_execute($c_stmt);
        mysqli_stmt_close($c_stmt);
    }

    // Trigger Notifications
    $notif_title = "$priority Cleaning Dispatch: $ward";
    $notif_body = "Location: $ward " . ($room ? "($room)" : "") . "\nType: $clean_type\nContamination: $contam_level\nPPE: " . implode(', ', json_decode($ppe_json));
    
    // Notify Assignee
    $ns = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($ns, "iss", $assignee, $notif_title, $notif_body);
    mysqli_stmt_execute($ns);
    
    // Notify Backup
    if ($backup) {
        $backup_body = "You are designated BACKUP. $notif_body";
        mysqli_stmt_bind_param($ns, "iss", $backup, $notif_title, $backup_body);
        mysqli_stmt_execute($ns);
    }
    mysqli_stmt_close($ns);

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Cleaner Dispatched Successfully!']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
