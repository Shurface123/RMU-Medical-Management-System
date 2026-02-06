<?php
require_once 'db_conn.php';
require_once 'classes/SessionManager.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info before destroying session
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// End session using SessionManager if user is logged in
if ($user_id) {
    $sessionManager = new SessionManager($conn);
    $sessionManager->endSession();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header("Location: index.php?success=You have been logged out successfully");
exit();
?>
