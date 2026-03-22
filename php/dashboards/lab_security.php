<?php
// ============================================
// LAB TECHNICIAN SECURITY HANDLER
// ============================================
// Secure Session Setup
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

function initSecureSession() {
    $timeout = 1800; // 30 minutes inactivity timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: ../login.php?error=session_timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 900) { // every 15 mins
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function enforceLabTechRole() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../login.php?error=not_logged_in");
        exit();
    }
    
    // Strict RBAC: Only lab_technician and admin can access lab modules
    if ($_SESSION['user_role'] !== 'lab_technician' && $_SESSION['user_role'] !== 'admin') {
        header("Location: ../login.php?error=unauthorized_access");
        exit();
    }
    
    return (int)$_SESSION['user_id'];
}

/**
 * Phase 9: Record this device's session into lab_technician_sessions.
 * Safe to call on every page load — uses REPLACE / upsert logic.
 */
function recordLabSession(int $user_id, $conn): void {
    if (!$conn) return;
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
    $sess_key = session_id();

    // Detect simple browser label
    $browser = 'Other';
    if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Edg') === false) $browser = 'Chrome';
    elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
    elseif (stripos($ua, 'Edg') !== false) $browser = 'Edge';

    // Mark all existing sessions for this user as non-current first
    $u = $conn->prepare("UPDATE lab_technician_sessions lts
        JOIN lab_technicians lt ON lt.id = lts.technician_id
        SET lts.is_current = 0
        WHERE lt.user_id = ?");
    if ($u) { $u->bind_param('i', $user_id); $u->execute(); $u->close(); }

    // Get the technician PK
    $s = $conn->prepare("SELECT id FROM lab_technicians WHERE user_id = ? LIMIT 1");
    if (!$s) return;
    $s->bind_param('i', $user_id);
    $s->execute();
    $tk_row = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$tk_row) return;
    $tech_pk = (int)$tk_row['id'];

    // Insert or update this session token row
    $ins = $conn->prepare("
        INSERT INTO lab_technician_sessions
            (technician_id, session_token, device_info, browser, ip_address, login_time, last_active, is_current)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 1)
        ON DUPLICATE KEY UPDATE
            last_active = NOW(), is_current = 1, ip_address = VALUES(ip_address)
    ");
    if (!$ins) return;
    $ins->bind_param('issss', $tech_pk, $sess_key, $ua, $browser, $ip);
    $ins->execute();
    $ins->close();
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security Error: Invalid CSRF token.']);
            exit();
        }
        header("Location: ../login.php?error=invalid_csrf_token");
        exit();
    }
}

function setSecurityHeaders() {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    // header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.datatables.net https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;");
}
?>
