<?php
// ===================================
// SESSION CHECK - Include this at the top of protected pages
// ===================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Include database connection
require_once __DIR__ . '/db_conn.php';

// Include SessionManager class
require_once __DIR__ . '/classes/SessionManager.php';

// Create SessionManager instance
$sessionManager = new SessionManager($conn);

// Clean up expired sessions (run occasionally)
if (rand(1, 100) === 1) { // 1% chance on each page load
    $sessionManager->cleanupExpiredSessions();
}

// Validate session
if (!$sessionManager->validateSession()) {
    // Session invalid - redirect to login
    header("Location: /RMU-Medical-Management-System/php/index.php?error=session_expired");
    exit();
}

// Session is valid - continue with page
$currentUser = $sessionManager->getCurrentUser();
if (!$currentUser) {
    $sessionManager->destroyCurrentSession();
    header("Location: /RMU-Medical-Management-System/php/index.php?error=account_not_found");
    exit();
}

// ── 1. ACCOUNT STATUS CHECK ──────────────────────────────────────────────────
$status = $currentUser['account_status'] ?? 'active';
if ($status !== 'active' || !$currentUser['is_active']) {
    $sessionManager->destroyCurrentSession();
    $msg = "Your account is currently " . ($status === 'pending' ? "pending approval" : $status) . ".";
    header("Location: /RMU-Medical-Management-System/php/index.php?error=" . urlencode($msg));
    exit();
}

// ── 2. LOCKOUT CHECK ─────────────────────────────────────────────────────────
if (!empty($currentUser['locked_until']) && strtotime($currentUser['locked_until']) > time()) {
    $sessionManager->destroyCurrentSession();
    header("Location: /RMU-Medical-Management-System/php/index.php?error=account_security_lock");
    exit();
}

$currentRole   = $currentUser['user_role'] ?? null;
$currentUserId = $currentUser['id'] ?? null;
$currentName   = $currentUser['name'] ?? 'User';

?>
