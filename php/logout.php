<?php
/**
 * logout.php — Simple Logout Handler
 * Destroys the session, clears cookies, and redirects to login.
 */
session_start();
require_once __DIR__ . '/db_conn.php';

// Remove active_sessions DB row
if (!empty($_SESSION['user_id']) && $conn) {
    $sid = session_id();
    $ds = mysqli_prepare($conn, "DELETE FROM active_sessions WHERE session_id = ?");
    mysqli_stmt_bind_param($ds, 's', $sid);
    @mysqli_stmt_execute($ds);

    // Revoke remember-me token
    if (isset($_COOKIE['rmumss_remember'])) {
        $hash = hash('sha256', $_COOKIE['rmumss_remember']);
        $dt = mysqli_prepare($conn, "DELETE FROM remember_me_tokens WHERE token_hash = ?");
        mysqli_stmt_bind_param($dt, 's', $hash);
        @mysqli_stmt_execute($dt);
        setcookie('rmumss_remember', '', time() - 3600, '/');
    }
}

// Destroy PHP session completely
session_unset();
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

header("Location: /RMU-Medical-Management-System/php/index.php?success=You+have+been+logged+out+successfully.");
exit;
?>
