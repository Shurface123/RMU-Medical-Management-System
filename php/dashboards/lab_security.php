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
