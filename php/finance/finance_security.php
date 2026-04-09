<?php
/**
 * Finance Security Middleware
 * php/finance/finance_security.php
 */

require_once dirname(__DIR__).'/includes/auth_middleware.php';

// ── SESSION HIJACKING & TIMEOUT PROTECTIONS ───────────────
$timeout_duration = 3600; // 60 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: /RMU-Medical-Management-System/php/login.php?error=Session+expired");
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} elseif ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: /RMU-Medical-Management-System/php/login.php?error=Security+Validation+Failed");
    exit;
}

// ── CSRF PROTECTION ───────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── ROLE-BASED ACCESS CONTROL ─────────────────────────────
$allowed_roles = ['finance_officer', 'finance_manager', 'admin'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_SERVER['HTTP_CONTENT_TYPE']) && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please login.']);
        exit;
    } else {
        header("Location: /RMU-Medical-Management-System/php/login.php");
        exit;
    }
}

if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_SERVER['HTTP_CONTENT_TYPE']) && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Financial oversight required.']);
        exit;
    } else {
        // Redirect non-finance staff out
        $role = $_SESSION['user_role'];
        $dashboards = [
            'doctor' => '/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php',
            'nurse' => '/RMU-Medical-Management-System/php/dashboards/nurse_dashboard.php',
            'patient' => '/RMU-Medical-Management-System/php/dashboards/patient_dashboard.php',
            'admin' => '/RMU-Medical-Management-System/php/admin/home.php',
            'lab_tech' => '/RMU-Medical-Management-System/php/dashboards/lab_dashboard.php',
            'pharmacist' => '/RMU-Medical-Management-System/php/dashboards/pharmacy_dashboard.php',
            'staff' => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php'
        ];
        $dest = $dashboards[$role] ?? '/RMU-Medical-Management-System/php/login.php';
        header("Location: $dest");
        exit;
    }
}
?>
