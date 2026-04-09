<?php
/**
 * logout.php - Advanced Logout Processor (Phase 3)
 */
session_start();

require_once 'db_conn.php';
require_once 'classes/AuditLogger.php';

$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'unknown';
$sid = session_id();
$csrf = $_GET['csrf'] ?? '';
$origin = $_GET['origin'] ?? 'unknown';

// Verify CSRF Token (Wait to fail until we ensure we actually have one stored)
// Exception: if forced by admin queue, CSRF won't be passed via JS. Allow system overrides if a flag is passed internally, but direct web calls need CSRF.
if (!empty($_SESSION['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $csrf)) {
    // Soft fail: Just destroy everything anyway to safely err on caution? 
    // Spec says: validate CSRF token sent with logout confirmation to prevent CSRF-based forced logouts.
    // If it fails, we redirect them back without logging out.
    header("Location: index.php?error=" . urlencode("Invalid security token."));
    exit;
}

// Get browser/device info natively
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
function getOS($ua) {
    if (preg_match('/windows/i', $ua)) return 'Windows';
    if (preg_match('/macintosh|mac os x/i', $ua)) return 'Mac';
    if (preg_match('/linux/i', $ua)) return 'Linux';
    return 'Unknown';
}
function getBrowser($ua) {
    if (preg_match('/edg/i', $ua)) return 'Edge';
    if (preg_match('/chrome/i', $ua)) return 'Chrome';
    if (preg_match('/safari/i', $ua)) return 'Safari';
    if (preg_match('/firefox/i', $ua)) return 'Firefox';
    return 'Unknown';
}
$device = getOS($ua);
$browser = getBrowser($ua);

// Get Health Msg Cached
$healthMsgShown = $_SESSION['health_message_shown'] ?? 'None';

// Get redirect from config
$redirectUrl = '/RMU-Medical-Management-System/php/index.php?success=' . urlencode("You have been successfully logged out.");
$qCfg = mysqli_query($conn, "SELECT redirect_url, countdown_duration_seconds FROM logout_config LIMIT 1");
if ($cfg = mysqli_fetch_assoc($qCfg)) {
    $redirectUrl = $cfg['redirect_url'] . '?success=' . urlencode("You have been successfully logged out.");
    $duration = $cfg['countdown_duration_seconds'];
} else {
    $duration = 3;
}

// Log into logout_logs
$stmtL = mysqli_prepare($conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, logout_confirmed_at, countdown_duration, ip_address, device_info, browser, dashboard_logged_out_from, health_message_shown) VALUES (?, ?, ?, 'manual', NOW(), ?, ?, ?, ?, ?, ?)");
if ($stmtL) {
    mysqli_stmt_bind_param($stmtL, 'ississsss', $uid, $role, $sid, $duration, $ip, $device, $browser, $origin, $healthMsgShown);
    mysqli_stmt_execute($stmtL);
    mysqli_stmt_close($stmtL);
}

// Delete from active_sessions
$stmtD = mysqli_prepare($conn, "DELETE FROM active_sessions WHERE session_id = ?");
if ($stmtD) {
    mysqli_stmt_bind_param($stmtD, 's', $sid);
    mysqli_stmt_execute($stmtD);
    mysqli_stmt_close($stmtD);
}

// Delete Remember me tokens
// Read the cookie if it exists to delete the specific token
$cookieName = "rmumss_remember";
if (isset($_COOKIE[$cookieName])) {
    $tokenHash = hash('sha256', $_COOKIE[$cookieName]);
    $stmtR = mysqli_prepare($conn, "DELETE FROM remember_me_tokens WHERE token_hash = ?");
    if ($stmtR) {
        mysqli_stmt_bind_param($stmtR, 's', $tokenHash);
        mysqli_stmt_execute($stmtR);
        mysqli_stmt_close($stmtR);
    }
    setcookie($cookieName, '', time() - 3600, '/');
}

// Audit Log (Using existing system audit logic if available)
if ($uid) {
    AuditLogger::log($conn, $uid, 'manual_logout', 'User logged out manually.', json_encode(['ip'=>$ip,'device'=>$device,'browser'=>$browser]));
}

// Destroy session
$_SESSION = [];
session_unset();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: $redirectUrl");
exit();
