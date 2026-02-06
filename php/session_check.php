<?php
// ===================================
// SESSION CHECK - Include this at the top of protected pages
// ===================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
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
$currentRole = getCurrentRole();
$currentUserId = getCurrentUserId();

?>
