<?php
/**
 * staff_actions.php
 * Main AJAX handler for the General Staff Dashboard.
 * Interacts with staff, staff_sessions, tasks, cleaning, etc.
 */
require_once 'staff_security.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ensure the action parameter exists
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = sanitize($_POST['action']);
$user_id = (int)$_SESSION['user_id'];
$staff_id = getStaffId($conn, $user_id);

if (!$staff_id && $action !== 'create_staff_profile') {
    echo json_encode(['success' => false, 'message' => 'Staff profile not found. Please contact admin.']);
    exit();
}

// Secure JSON response
header('Content-Type: application/json');

switch ($action) {

    // ==========================================
    // MODULE 1: PROFILE MANAGEMENT
    // ==========================================
    case 'update_personal_info':
        $full_name = sanitize($_POST['full_name'] ?? '');
        $dob = sanitize($_POST['date_of_birth'] ?? '');
        $gender = sanitize($_POST['gender'] ?? '');
        $nationality = sanitize($_POST['nationality'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $emergency_name = sanitize($_POST['emergency_contact_name'] ?? '');
        $emergency_phone = sanitize($_POST['emergency_contact_phone'] ?? '');

        if (!$full_name || !$email) {
            echo json_encode(['success' => false, 'message' => 'Name and Email are required.']);
            exit();
        }

        // Update `staff` table
        $sql = "UPDATE staff SET full_name=?, date_of_birth=?, gender=?, nationality=?, phone=?, email=?, address=?, emergency_contact_name=?, emergency_contact_phone=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssssi", $full_name, $dob, $gender, $nationality, $phone, $email, $address, $emergency_name, $emergency_phone, $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Sync with users table
            $sql_u = "UPDATE users SET name=?, phone=?, email=?, date_of_birth=?, gender=? WHERE id=?";
            $stmt_u = mysqli_prepare($conn, $sql_u);
            mysqli_stmt_bind_param($stmt_u, "sssssi", $full_name, $phone, $email, $dob, $gender, $user_id);
            mysqli_stmt_execute($stmt_u);

            logStaffActivity($conn, $staff_id, 'update_personal_info', 'profile');
            echo json_encode(['success' => true, 'message' => 'Personal information updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        break;

    // ==========================================
    // MODULE 2: SECURITY & SESSIONS
    // ==========================================
    case 'logout_session':
        $session_id = (int)($_POST['session_id'] ?? 0);
        if ($session_id) {
            $sql = "DELETE FROM staff_sessions WHERE session_id=? AND staff_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $session_id, $staff_id);
            mysqli_stmt_execute($stmt);
            logStaffActivity($conn, $staff_id, 'terminate_session', 'security', $session_id);
            echo json_encode(['success' => true, 'message' => 'Session terminated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID.']);
        }
        break;

    case 'logout_all_sessions':
        $sql = "DELETE FROM staff_sessions WHERE staff_id=? AND is_current=0";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        logStaffActivity($conn, $staff_id, 'terminate_all_sessions', 'security');
        echo json_encode(['success' => true, 'message' => 'All other sessions terminated.']);
        break;

    // ==========================================
    // MODULE 3: DOCUMENTS & QUALIFICATIONS
    // ==========================================
    case 'save_qualification':
        $cert_name = sanitize($_POST['certificate_name'] ?? '');
        $institution = sanitize($_POST['institution'] ?? '');
        $year = (int)($_POST['year_awarded'] ?? 0);
        $file = $_FILES['document'] ?? null;

        if (!$cert_name || !$institution || !$year) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        $file_path = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            // Note: validateUpload exists if included from similar dashboard scripts, ensuring standard implementation.
            $uploadDir = '../uploads/staff/qualifications/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('qual_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $file_path = 'uploads/staff/qualifications/' . $fileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
                exit();
            }
        }

        $sql = "INSERT INTO staff_qualifications (staff_id, certificate_name, institution, year_awarded, file_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issis", $staff_id, $cert_name, $institution, $year, $file_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $qual_id = mysqli_insert_id($conn);
            logStaffActivity($conn, $staff_id, 'add_qualification', 'profile', $qual_id);
            echo json_encode(['success' => true, 'message' => 'Qualification added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        break;


    // ==========================================
    // MODULE 4: TASKS & CHECKLISTS
    // ==========================================
    case 'complete_task_checklist':
        $checklist_id = (int)($_POST['checklist_id'] ?? 0);
        $task_id = (int)($_POST['task_id'] ?? 0);
        $state = (int)($_POST['state'] ?? 0); // 1 = checked, 0 = unchecked

        $sql = "UPDATE staff_task_checklists SET is_completed=?, completed_by=?, completed_at=NOW() WHERE checklist_id=? AND task_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $state, $staff_id, $checklist_id, $task_id);
        if(mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update checklist item.']);
        }
        break;

    case 'update_task_status':
        $task_id = (int)($_POST['task_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        // Verify task belongs to this staff
        $stmt = mysqli_prepare($conn, "SELECT id FROM staff_tasks WHERE task_id=? AND assigned_to=?");
        mysqli_stmt_bind_param($stmt, "ii", $task_id, $staff_id);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_get_result($stmt)->num_rows === 0) {
            echo json_encode(['success'=>false, 'message'=>'Unauthorized or task not found.']);
            exit;
        }

        $sql = "UPDATE staff_tasks SET status=?, completion_notes=?, updated_at=NOW() " . ($status === 'completed' ? ", completed_at=NOW()" : "") . " WHERE task_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $notes, $task_id);
        if(mysqli_stmt_execute($stmt)) {
            logStaffActivity($conn, $staff_id, 'update_task_status', 'tasks', $task_id, null, ['status'=>$status]);
            echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
        }
        break;


    // ==========================================
    // HELPER: Input Sanitization Function
    // ==========================================
    default:
        // Use a simple sanitize function if not loaded from security
        function sanitize_local($data) {
            return htmlspecialchars(trim(stripslashes($data)));
        }
        
        // This will only hit if action doesn't match above cases
        if(!function_exists('sanitize')){
             function sanitize($data) { return sanitize_local($data); }
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
