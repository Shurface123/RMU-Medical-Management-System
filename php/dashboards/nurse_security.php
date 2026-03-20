<?php
// ============================================================
// NURSE SECURITY MIDDLEWARE
// Central security layer for the nurse dashboard
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
    ini_set('session.gc_maxlifetime', 1800); // 30 min

    session_start();

    // Regenerate ID every 15 minutes to prevent session fixation
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

    // Bind session to user agent to detect hijacking
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
    if ($role !== 'nurse') {
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
    // Check for PHP in file content (polyglot prevention)
    $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (preg_match('/<\?php|<\?=/i', $content)) {
        return ['valid' => false, 'error' => 'Suspicious file content detected.'];
    }
    return ['valid' => true];
}

// ── 7. Secure Activity Logging ────────────────────────────
function secureLogNurse($conn, $nurseId, $desc, $module = 'general') {
    $log_id = 'NLG-' . strtoupper(uniqid());
    dbExecute($conn,
        "INSERT INTO nurse_activity_log (log_id, nurse_id, action, module, ip_address, device) VALUES (?, ?, ?, ?, ?, ?)",
        "sissss", [$log_id, $nurseId, $desc, $module, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
    );
}

// ── 8. Secure Notification ────────────────────────────────
function secureNotifyNurse($conn, $nurseId, $msg, $type = 'General', $module = 'nurse_dashboard') {
    $notif_id = 'NNOT-' . strtoupper(uniqid());
    dbExecute($conn,
        "INSERT INTO nurse_notifications (notification_id, nurse_id, message, type, related_module, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())",
        "sisss", [$notif_id, $nurseId, $msg, $type, $module]
    );
}

// ── 9. Security Headers ──────────────────────────────────
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.datatables.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:;");
}

// ── 10. Output Encoding Helper ────────────────────────────
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
