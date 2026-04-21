<?php
/**
 * staff_security.php  — RMU Medical Sickbay
 * Session guard + global DB helpers for the Staff Dashboard.
 * Mirrors nurse_security.php / lab_security.php patterns.
 */
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../db_conn.php';

date_default_timezone_set('Africa/Accra');

/* ─── Role Guard ──────────────────────────────────────────── */
$STAFF_ROLES = ['staff', 'ambulance_driver', 'cleaner', 'laundry_staff', 'maintenance', 'security', 'kitchen_staff'];

if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) {
    if (defined('AJAX_REQUEST')) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please login to access this resource']);
        exit;
    }
    $redirect = '../index.php?error=' . urlencode('Please login to access the Staff Dashboard');
    header("Location: $redirect");
    exit();
}
if (!in_array($_SESSION['user_role'], $STAFF_ROLES)) {
    if (defined('AJAX_REQUEST')) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access Denied']);
        exit;
    }
    header("Location: ../index.php?error=" . urlencode('Access Denied'));
    exit();
}

/* ─── Approval Gate (secondary defence) ──────────────────── */
// Skip for generic 'staff' role (admin-created accounts) — check only sub-roles
$STAFF_SUB_ROLES = ['ambulance_driver', 'cleaner', 'laundry_staff', 'maintenance', 'security', 'kitchen_staff'];
if (in_array($_SESSION['user_role'], $STAFF_SUB_ROLES)) {
    // We need dbVal — define a local inline version since helpers below aren't loaded yet
    $approvalStmt = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM staff WHERE user_id = ? LIMIT 1");
    if ($approvalStmt) {
        $uid_for_approval = (int)$_SESSION['user_id'];
        mysqli_stmt_bind_param($approvalStmt, "i", $uid_for_approval);
        mysqli_stmt_execute($approvalStmt);
        $approvalRow = mysqli_fetch_assoc(mysqli_stmt_get_result($approvalStmt));
        mysqli_stmt_close($approvalStmt);

        $approvalStatus = $approvalRow['approval_status'] ?? 'pending';
        $rejectionReason = $approvalRow['rejection_reason'] ?? 'Please contact administration.';

        if ($approvalStatus === 'pending') {
            if (defined('AJAX_REQUEST')) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Account pending admin approval']);
                exit;
            }
            session_write_close(); // don't destroy — let them retry after approval
            include __DIR__ . '/staff_pending_approval.php';
            exit();
        }
        if ($approvalStatus === 'rejected') {
            if (defined('AJAX_REQUEST')) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Account rejected: ' . $rejectionReason]);
                exit;
            }
            $_SESSION['_rejection_reason'] = $rejectionReason;
            session_write_close();
            include __DIR__ . '/staff_pending_approval.php';
            exit();
        }
    }
}

/* ─── CSRF Token ──────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function verifyCsrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* ─── Sanitize / Escape ───────────────────────────────────── */
function sanitize($v)
{
    return htmlspecialchars(trim(stripslashes((string)$v)), ENT_QUOTES, 'UTF-8');
}
function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function confirmAction($msg = 'Are you sure?')
{
    return true;
} // JS-side

/* ─── DB Helper Wrappers ──────────────────────────────────── */

/** Run a prepared statement and return number of affected rows */
function dbExecute($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt)
        return false;
    if ($types && $params)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

/** Return a single scalar value */
function dbVal($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt)
        return null;
    if ($types && $params)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($res);
    mysqli_stmt_close($stmt);
    return $row ? $row[0] : null;
}

/** Return a single associative row */
function dbRow($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt)
        return null;
    if ($types && $params)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/** Return an array of associative rows */
function dbSelect($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt)
        return [];
    if ($types && $params)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res))
        $rows[] = $r;
    mysqli_stmt_close($stmt);
    return $rows;
}

/** Return the inserted row ID after an insert */
function dbInsert($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt)
        return false;
    if ($types && $params)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

/* ─── Staff-Specific Helpers ──────────────────────────────── */

/** Get primary key from `staff` table for this user */
function getStaffId($conn, $user_id)
{
    $row = dbRow($conn, "SELECT id FROM staff WHERE user_id=? LIMIT 1", "i", [$user_id]);
    return $row ? (int)$row['id'] : 0;
}

/** Log to staff_audit_trail */
function logStaffActivity($conn, $staff_id, $action_type, $module, $record_id = null, $old = null, $new = null)
{
    // Use session user_id for the audit trail as per schema
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$user_id) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Construct a descriptive message from the parameters
    $desc = "Action: $action_type in $module.";
    if ($record_id) $desc .= " Record ID: $record_id.";
    if ($old || $new) {
        $desc .= " Details: " . json_encode(['old' => $old, 'new' => $new]);
    }

    dbInsert($conn,
        "INSERT INTO staff_audit_trail (user_id, action_type, module, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
        "issss", 
        [$user_id, $action_type, $module, $desc, $ip]
    );
}


/** Send a staff notification */
function notifyStaff($conn, $staff_id, $type, $message, $link = '')
{
    dbInsert($conn,
        "INSERT INTO staff_notifications (staff_id,type,message,link,is_read,created_at) VALUES (?,?,?,?,0,NOW())",
        "isss", [$staff_id, $type, $message, $link]
    );
}

/* ─── Upload Helper ───────────────────────────────────────── */
function handleUpload($fileKey, $subDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif'], $maxMB = 5)
{
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK)
        return null;
    $file = $_FILES[$fileKey];
    if ($file['size'] > $maxMB * 1024 * 1024)
        return ['error' => "File must be under {$maxMB}MB"];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes))
        return ['error' => 'File type not allowed'];
    $dir = __DIR__ . "/../../uploads/staff/$subDir/";
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    $fname = uniqid('stf_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $fname))
        return ['error' => 'Upload failed'];
    return "uploads/staff/$subDir/$fname";
}

/* ─── JSON Response Helper (for AJAX endpoints) ───────────── */
function json_ok($msg = 'Success', $data = [])
{
    echo json_encode(['success' => true, 'message' => $msg] + $data);
    exit();
}
function json_err($msg = 'Error', $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

/* ─── Modal JS Helpers (echoed into HTML) ─────────────────── */
function modalHelpers()
{
    return "
function openModal(id){document.getElementById(id).style.display='flex';}
function closeModal(id){document.getElementById(id).style.display='none';}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-bg').forEach(m=>m.style.display='none');});
";
}
