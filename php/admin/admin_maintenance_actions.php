<?php
require_once '../includes/auth_middleware.php';

// Only admins can report bulk issues directly via this specific panel
enforceSingleDashboard('admin');
require_once '../db_conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Refresh and try again.']);
    exit;
}

$reported_by       = $_SESSION['user_name'] ?? 'System Admin'; // Using Admin's session name
$location_select   = trim($_POST['location_select'] ?? '');
$location_override = trim($_POST['location_override'] ?? '');
$location          = ($location_select === 'other' && $location_override !== '') ? $location_override : $location_select;

$equipment_or_area = trim($_POST['equipment_or_area'] ?? '');
$title             = trim($_POST['title'] ?? '');
$issue_category    = trim($_POST['issue_category'] ?? 'other');
$priority          = trim($_POST['priority'] ?? 'medium');
$description       = trim($_POST['description'] ?? '');
$assigned_to       = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

if (!$location || !$equipment_or_area || !$title || !$description) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields. Please fill out all required details.']);
    exit;
}

// 1. Process File Uploads (Evidence)
$upload_dir = '../../uploads/maintenance/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    $total_files = count($_FILES['evidence']['name']);
    for ($i = 0; $i < $total_files; $i++) {
        $tmp_name = $_FILES['evidence']['tmp_name'][$i];
        $name     = $_FILES['evidence']['name'][$i];
        $size     = $_FILES['evidence']['size'][$i];
        $type     = mime_content_type($tmp_name);

        if (!in_array($type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => "File '$name' is not a valid JPG, PNG, or PDF."]);
            exit;
        }

        if ($size > $max_size) {
            echo json_encode(['success' => false, 'message' => "File '$name' exceeds the 5MB size limit."]);
            exit;
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $new_filename = 'maint_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $upload_dir . $new_filename;

        if (move_uploaded_file($tmp_name, $dest)) {
            $uploaded_files[] = 'uploads/maintenance/' . $new_filename;
        }
    }
}

$images_path_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
$initial_status = $assigned_to ? 'assigned' : 'reported';

// 2. Perform Transaction
mysqli_begin_transaction($conn);

try {
    // Insert the Maintenance Request
    $sql = "INSERT INTO maintenance_requests (
                reported_by, assigned_to, location, equipment_or_area, issue_description, 
                issue_category, priority, status, images_path, reported_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sisssssss", 
        $reported_by, $assigned_to, $location, $equipment_or_area, $description, 
        $issue_category, $priority, $initial_status, $images_path_json
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database insertion failed for maintenance request.");
    }
    
    $request_id = mysqli_insert_id($conn);

    // 3. Dispatch Notifications
    $msg = mysqli_real_escape_string($conn, "Maintenance Alert [$priority]: $equipment_or_area at $location. $title.");
    
    if ($priority === 'urgent' || $priority === 'high' || !$assigned_to) {
        // Broadcast to all active maintenance staff
        $q_staff = mysqli_query($conn, "SELECT s.id FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'maintenance'");
        if ($q_staff) {
            $notif_sql = "INSERT INTO staff_notifications (staff_id, message, type, related_module, related_record_id) VALUES (?, ?, 'maintenance', 'repairs', ?)";
            $ns = mysqli_prepare($conn, $notif_sql);
            while ($row = mysqli_fetch_assoc($q_staff)) {
                $sid = $row['id'];
                mysqli_stmt_bind_param($ns, "isi", $sid, $msg, $request_id);
                mysqli_stmt_execute($ns);
            }
        }
    } else {
        // Notify strictly the assigned person
        $notif_sql = "INSERT INTO staff_notifications (staff_id, message, type, related_module, related_record_id) VALUES (?, ?, 'maintenance', 'repairs', ?)";
        $ns = mysqli_prepare($conn, $notif_sql);
        mysqli_stmt_bind_param($ns, "isi", $assigned_to, $msg, $request_id);
        mysqli_stmt_execute($ns);
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => "Issue #$request_id reported successfully."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    // Cleanup any uploaded files if database failed
    foreach ($uploaded_files as $f) {
        $path = '../../' . $f;
        if (file_exists($path)) {
            unlink($path);
        }
    }
    echo json_encode(['success' => false, 'message' => "System error completing report workflow: " . $e->getMessage()]);
}
