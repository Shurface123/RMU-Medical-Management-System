<?php
// ============================================================
// LAB TECHNICIAN SECURITY MIDDLEWARE
// Central security layer for the lab technician dashboard
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
    if (!isset($_SESSION['_sec_last_regen'])) {
        $_SESSION['_sec_last_regen'] = time();
    } elseif (time() - $_SESSION['_sec_last_regen'] > 900) {
        session_regenerate_id(true);
        $_SESSION['_sec_last_regen'] = time();
    }
    if (isset($_SESSION['_sec_last_activity']) && (time() - $_SESSION['_sec_last_activity'] > 1800)) {
        session_unset(); session_destroy();
        header('Location: /RMU-Medical-Management-System/php/login.php?error=' . urlencode('Session expired'));
        exit;
    }
    $_SESSION['_sec_last_activity'] = time();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!isset($_SESSION['_sec_user_agent'])) {
        $_SESSION['_sec_user_agent'] = hash('sha256', $ua);
    } elseif ($_SESSION['_sec_user_agent'] !== hash('sha256', $ua)) {
        session_unset(); session_destroy();
        header('Location: /RMU-Medical-Management-System/php/login.php?error=' . urlencode('Session invalid'));
        exit;
    }
}

// ── 2. Role-Based Access Control ──────────────────────────
function enforceLabTechRole() {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    if ($role !== 'lab_technician') {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Lab Technician access only']);
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
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please reload.']);
            exit;
        }
        header('HTTP/1.1 403 Forbidden');
        exit('CSRF validation failed');
    }
}

// ── 4. Security Headers ──────────────────────────────────
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:;");
}

// ── 5. Prepared Statement Helpers ─────────────────────────
function dbExecute($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { error_log("DB Prepare fail: " . mysqli_error($conn) . " SQL: $sql"); return false; }
    if ($types && $params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) error_log("DB Execute fail: " . mysqli_stmt_error($stmt));
    $id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);
    return $id ?: $ok;
}
function dbSelect($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { error_log("DB Prepare fail: " . mysqli_error($conn)); return []; }
    if ($types && $params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($res) while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);
    return $rows;
}
function dbRow($conn, $sql, $types = '', $params = []) {
    $rows = dbSelect($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}
function dbVal($conn, $sql, $types = '', $params = []) {
    $row = dbRow($conn, $sql, $types, $params);
    return $row ? reset($row) : null;
}

// ── 6. Input Sanitization ─────────────────────────────────
function sanitize($input) {
    if (is_array($input)) return array_map('sanitize', $input);
    return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES, 'UTF-8');
}
function e($val) { return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8'); }

// ── 7. File Upload Validation ─────────────────────────────
function validateUpload($file, $allowed_types = ['image/jpeg','image/png','application/pdf'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) return ['valid' => false, 'error' => 'Upload error code: ' . $file['error']];
    if ($file['size'] > $max_size) return ['valid' => false, 'error' => 'File too large. Max ' . round($max_size / 1048576) . 'MB'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types)) return ['valid' => false, 'error' => 'Invalid file type: ' . $mime];
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<\?=|<script|eval\s*\(/i', $content)) return ['valid' => false, 'error' => 'File contains suspicious content'];
    return ['valid' => true, 'mime' => $mime];
}

// ── 8. Activity Logging ──────────────────────────────────
function logLabActivity($conn, $tech_id, $action, $description, $module = null, $record_id = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $device = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    dbExecute($conn,
        "INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, ip_address, device_info) VALUES (?,?,?,?,?,?)",
        "ississ", [$tech_id, $action, $module, $record_id, $ip, $device]
    );
}

// ── 9. Brute Force Protection ─────────────────────────────
function checkBruteForce($conn, $user_id, $max_attempts = 5, $lockout_minutes = 15) {
    $count = (int)dbVal($conn,
        "SELECT COUNT(*) FROM lab_audit_trail WHERE technician_id=? AND action_type='login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
        "ii", [$user_id, $lockout_minutes]
    );
    return $count >= $max_attempts;
}

// ── 10. Password Strength ─────────────────────────────────
function enforcePasswordStrength($password) {
    if (strlen($password) < 8) return 'Password must be at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) return 'Must contain at least one uppercase letter';
    if (!preg_match('/[a-z]/', $password)) return 'Must contain at least one lowercase letter';
    if (!preg_match('/[0-9]/', $password)) return 'Must contain at least one digit';
    return true;
}

// ── 11. Rate Limiting ─────────────────────────────────────
function rateLimitAction($conn, $tech_id, $action, $max_per_minute = 10) {
    $count = (int)dbVal($conn,
        "SELECT COUNT(*) FROM lab_audit_trail WHERE technician_id=? AND action_type=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
        "is", [$tech_id, $action]
    );
    return $count >= $max_per_minute;
}

// ── 12. Lab Notification Helper ───────────────────────────
function labNotify($conn, $recipient_id, $message, $type = 'System', $module = null, $record_id = null, $sender_id = null, $sender_role = null) {
    dbExecute($conn,
        "INSERT INTO lab_notifications (recipient_id, recipient_role, sender_id, sender_role, message, type, related_module, related_record_id) VALUES (?,'lab_technician',?,?,?,?,?,?)",
        "iissssi", [$recipient_id, $sender_id, $sender_role, $message, $type, $module, $record_id]
    );
}

// ── 13. Secure File Download ──────────────────────────────
function serveSecureFile($conn, $tech_id, $file_id, $source_table) {
    $allowed_tables = ['lab_technician_documents','lab_technician_qualifications','lab_technician_certifications','lab_generated_reports'];
    if (!in_array($source_table, $allowed_tables)) { http_response_code(403); exit('Invalid source'); }
    $col = ($source_table === 'lab_generated_reports') ? 'file_path' : (($source_table === 'lab_technician_documents') ? 'file_path' : 'certificate_file');
    $owner_col = ($source_table === 'lab_generated_reports') ? 'generated_by' : 'technician_id';
    $row = dbRow($conn, "SELECT $col FROM $source_table WHERE id=? AND $owner_col=?", "ii", [$file_id, $tech_id]);
    if (!$row || empty($row[$col])) { http_response_code(404); exit('File not found'); }
    $path = realpath($_SERVER['DOCUMENT_ROOT'] . '/RMU-Medical-Management-System/' . $row[$col]);
    $base = realpath($_SERVER['DOCUMENT_ROOT'] . '/RMU-Medical-Management-System/');
    if (!$path || strpos($path, $base) !== 0 || !file_exists($path)) { http_response_code(404); exit('File not found'); }
    header('Content-Type: ' . mime_content_type($path));
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ── 14. Result Ownership Gate ────────────────────────────
// Ensure the calling technician owns the result (prevents cross-tech tampering)
function enforceResultOwnership($conn, $result_id, $tech_pk) {
    $result_id = (int)$result_id;
    $tech_pk   = (int)$tech_pk;
    $row = dbRow($conn, "SELECT technician_id FROM lab_results_v2 WHERE id=? LIMIT 1", "i", [$result_id]);
    if (!$row || (int)$row['technician_id'] !== $tech_pk) {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>'Access denied: you do not own this result']);
            exit;
        }
        http_response_code(403); exit('Forbidden');
    }
}

// ── 15. Validated Gate (DB-level) before release ───────────────
// Result CANNOT be released to doctor unless result_status='Validated'
function enforceValidatedGate($conn, $result_id) {
    $result_id = (int)$result_id;
    $status = dbVal($conn, "SELECT result_status FROM lab_results_v2 WHERE id=? LIMIT 1", "i", [$result_id]);
    if ($status !== 'Validated') {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>'Result must be in Validated status before release. Current status: '.$status]);
            exit;
        }
        http_response_code(403); exit('Validation gate: result not validated');
    }
}

// ── 16. Amendment Gate (DB-level) ─────────────────────────
// ONLY Released or Validated results can be amended (not Drafts)
function enforceAmendGate($conn, $result_id) {
    $result_id = (int)$result_id;
    $status = dbVal($conn, "SELECT result_status FROM lab_results_v2 WHERE id=? LIMIT 1", "i", [$result_id]);
    if (!in_array($status, ['Validated','Released','Amended'])) {
        if (defined('AJAX_REQUEST')) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>'Only Validated or Released results can be amended. Current status: '.$status]);
            exit;
        }
        http_response_code(403); exit('Amendment gate: invalid status');
    }
}

// ── 17. Critical Value Acknowledgement Logger ──────────────
// Logs that the technician confirmed doctor was notified before proceeding
function acknowledgeCriticalValue($conn, $tech_pk, $result_id, $doctor_notified_method) {
    $tech_pk   = (int)$tech_pk;
    $result_id = (int)$result_id;
    $method    = substr($doctor_notified_method, 0, 100);
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
    $device    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    dbExecute($conn,
        "INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, old_value, new_value, ip_address, device_info)
         VALUES (?, 'critical_value_acknowledged', 'results', ?, 'CRITICAL', ?, ?, ?)",
        "iisss", [$tech_pk, $result_id, $method, $ip, $device]
    );
}

// ── 18. Brute Force Lockout Enforcer (with admin notification) ──
// Call AFTER verifying login failure. If locked, notifies admins.
function enforceBruteForceLockout($conn, $user_id, $max_attempts = 5, $lockout_minutes = 15) {
    $user_id = (int)$user_id;
    $locked  = checkBruteForce($conn, $user_id, $max_attempts, $lockout_minutes);
    if ($locked) {
        // Auto-disable the account temporarily
        dbExecute($conn, "UPDATE users SET is_active=0 WHERE id=? AND user_role='lab_technician'", "i", [$user_id]);
        dbExecute($conn,
            "INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, ip_address, device_info)
             VALUES (?, 'account_locked_brute_force', 'security', ?, ?)",
            "iss", [$user_id, $_SERVER['REMOTE_ADDR']??'', substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]
        );
        // Notify all admins
        require_once __DIR__ . '/cross_notify.php';
        notifyAllAdmins($conn, 'security',
            'Lab Account Locked: Brute Force Detected',
            "Lab technician account (user_id: $user_id) was locked after $max_attempts failed login attempts from IP: ".($_SERVER['REMOTE_ADDR']??'unknown'),
            'security', $user_id, 'high'
        );
        return true;
    }
    return false;
}

// ── 19. Immutable Audit Trail Validator ───────────────────
// Use this to verify no tampering of audit trail (call at admin panel level)
function verifyAuditTrailIntegrity($conn, $entry_id) {
    // Audit trail is INSERT-only. Any UPDATE/DELETE detected = integrity breach.
    // This function is a placeholder for a future hash-chain verification system.
    // For now, enforce at SQL privilege level: REVOKE UPDATE, DELETE on lab_audit_trail FROM 'app_user'@'localhost';
    return true; // Integrity assumed — enforce via DB grants
}
