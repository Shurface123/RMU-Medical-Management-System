<?php
// ============================================================
// NURSE SECURITY MIDDLEWARE
// Central security layer for the nurse dashboard
// Mirrors pharmacy_security.php architecture
// ============================================================

// ── 1. Session Hardening ──────────────────────────────────
function initSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.gc_maxlifetime', 1800);

    session_start();

    // Regenerate ID every 15 minutes
    if (!isset($_SESSION['_sec_last_regen'])) {
        $_SESSION['_sec_last_regen'] = time();
    } elseif (time() - $_SESSION['_sec_last_regen'] > 900) {
        session_regenerate_id(true);
        $_SESSION['_sec_last_regen'] = time();
    }

    // Session timeout after 30 min inactivity
    if (isset($_SESSION['_sec_last_activity']) && (time() - $_SESSION['_sec_last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: /RMU-Medical-Management-System/php/login.php?error=' . urlencode('Session expired'));
        exit;
    }
    $_SESSION['_sec_last_activity'] = time();

    // Bind session to user agent
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!isset($_SESSION['_sec_user_agent'])) {
        $_SESSION['_sec_user_agent'] = hash('sha256', $ua);
    } elseif ($_SESSION['_sec_user_agent'] !== hash('sha256', $ua)) {
        session_unset();
        session_destroy();
        header('Location: /RMU-Medical-Management-System/php/login.php?error=' . urlencode('Session invalid'));
        exit;
    }
}

// ── 2. Role-Based Access Control ──────────────────────────
function enforceNurseRole() {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    // Nurse can login as 'nurse' or 'staff' (with staff_directory.role='Nurse')
    if ($role !== 'nurse' && $role !== 'staff') {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Nurse access only']);
            exit;
        }
        header('Location: /RMU-Medical-Management-System/php/login.php?error=' . urlencode('Unauthorized'));
        exit;
    }
    return (int)$_SESSION['user_id'];
}

// ── 3. CSRF Protection ───────────────────────────────────
function generateCsrfToken() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $token ?? '')) {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please reload the page.']);
            exit;
        }
        http_response_code(403);
        die('CSRF token mismatch');
    }
    return true;
}

function csrfField() {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// ── 4. Prepared Statement Helpers ─────────────────────────
function dbSelect($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function dbRow($conn, $sql, $types = '', $params = []) {
    $rows = dbSelect($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function dbVal($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_row($result) : null;
    mysqli_stmt_close($stmt);
    return $row[0] ?? 0;
}

function dbExecute($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? $affected : false;
}

function dbInsert($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);
    return (int)$id;
}

// ── 5. Input Validation & Sanitization ────────────────────
function sanitize($value) {
    if (is_array($value)) return array_map('sanitize', $value);
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($data[$f]) || trim($data[$f]) === '') {
            $missing[] = $f;
        }
    }
    return $missing;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateInt($value, $min = null, $max = null) {
    $v = filter_var($value, FILTER_VALIDATE_INT);
    if ($v === false) return false;
    if ($min !== null && $v < $min) return false;
    if ($max !== null && $v > $max) return false;
    return $v;
}

function validateFloat($value, $min = null) {
    $v = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($v === false) return false;
    if ($min !== null && $v < $min) return false;
    return $v;
}

function validateDate($date) {
    $d = \DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ── 6. File Upload Validation ─────────────────────────────
function validateUpload($file, $allowedTypes = ['image/jpeg','image/png'], $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload failed (error code: '.$file['error'].')'];
    }
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large. Max: '.round($maxSize/1048576,1).'MB'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedTypes)) {
        return ['valid' => false, 'error' => 'File type not allowed. Allowed: '.implode(', ', $allowedTypes)];
    }
    $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (preg_match('/<\?php|<\?=/i', $content)) {
        return ['valid' => false, 'error' => 'Suspicious file content detected.'];
    }
    return ['valid' => true];
}

// ── 7. Brute Force Protection ─────────────────────────────
function checkBruteForce($conn, $nurseId, $maxAttempts = 5, $lockoutMinutes = 15) {
    $cutoff = date('Y-m-d H:i:s', strtotime("-$lockoutMinutes minutes"));
    $attempts = dbVal($conn,
        "SELECT COUNT(*) FROM nurse_activity_log WHERE nurse_id=? AND action_type='failed_login' AND created_at>?",
        "is", [$nurseId, $cutoff]
    );
    return (int)$attempts >= $maxAttempts;
}

// ── 8. Secure Activity Logging ────────────────────────────
function secureLog($conn, $nurseId, $desc, $type = 'general') {
    dbExecute($conn,
        "INSERT INTO nurse_activity_log (nurse_id, action_type, action_description, ip_address, device) VALUES (?, ?, ?, ?, ?)",
        "issss", [$nurseId, $type, $desc, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
    );
}

// ── 9. Secure Notification ────────────────────────────────
function secureNotify($conn, $userId, $msg, $type = 'system', $module = 'nurse') {
    dbExecute($conn,
        "INSERT INTO notifications (user_id, message, type, related_module, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())",
        "isss", [$userId, $msg, $type, $module]
    );
}

function secureNotifyAdmins($conn, $msg, $type = 'system', $module = 'nurse') {
    $admins = dbSelect($conn, "SELECT id FROM users WHERE user_role='admin' AND is_active=1");
    foreach ($admins as $a) {
        secureNotify($conn, $a['id'], $msg, $type, $module);
    }
}

function nurseNotify($conn, $nurseId, $msg, $type = 'System', $module = null, $relId = null) {
    dbExecute($conn,
        "INSERT INTO nurse_notifications (nurse_id, message, type, related_module, related_id, created_at) VALUES (?,?,?,?,?,NOW())",
        "isssi", [$nurseId, $msg, $type, $module, $relId]
    );
}

// ── 10. Security Headers ──────────────────────────────────
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;");
}

// ── 11. Output Encoding Helper ────────────────────────────
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// ── 12. Re-authentication ─────────────────────────────────
function verifyPassword($conn, $userId, $password) {
    $row = dbRow($conn, "SELECT password FROM users WHERE id=?", "i", [$userId]);
    if (!$row) return false;
    return password_verify($password, $row['password']);
}

// ── INIT: Called by nurse_dashboard.php & nurse_actions.php ─
initSecureSession();
setSecurityHeaders();
$user_id = enforceNurseRole();
require_once __DIR__ . '/../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();
