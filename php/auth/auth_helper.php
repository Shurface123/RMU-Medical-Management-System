<?php
/**
 * auth_helper.php — Centralized Authentication Helper Functions
 * (Phase 3: Advanced Logout System Enhancements)
 * 
 * Provides centralized wrappers for session validation, role routing, 
 * brute force lookup, and the global Timeout/Forced Logout interceptor.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates if an active session exists and matches the expected role.
 * Intercepts stale sessions (Timeouts) and Admin Forced Logouts via Active Polling.
 * 
 * @param string|null $required_role Optional role needed.
 */
function validateSession($required_role = null) {
    if (empty($_SESSION['user_id'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401); exit;
        }
        header("Location: /RMU-Medical-Management-System/php/index.php?error=" . urlencode("Please log in to continue."));
        exit;
    }
    if ($required_role && ($_SESSION['role'] ?? '') !== $required_role) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403); exit;
        }
        header("Location: /RMU-Medical-Management-System/php/index.php?error=" . urlencode("Unauthorized role access."));
        exit;
    }

    global $conn;
    if (!$conn) {
        global $sname, $unmae, $password, $db_name;
        if (!isset($sname)) {
            require_once __DIR__ . '/../db_conn.php';
        }
    }

    $user_id = (int)$_SESSION['user_id'];
    $session_id = session_id();

    // 1. Check Forced Logout Queue (Feature 5)
    $fq = mysqli_query($conn, "SELECT id, reason FROM forced_logout_queue WHERE user_id = $user_id AND is_executed = 0 LIMIT 1");
    if ($fq && mysqli_num_rows($fq) > 0) {
        $forced = mysqli_fetch_assoc($fq);
        // Mark as executed immediately
        mysqli_query($conn, "UPDATE forced_logout_queue SET is_executed = 1, executed_at = NOW() WHERE id = {$forced['id']}");
        
        // Notify Admins of successful force logout
        $admins = mysqli_query($conn, "SELECT id FROM users WHERE user_role='admin' AND is_active=1");
        if ($admins) {
            $msg = "Security Alert: User ID {$user_id} was successfully forcefully logged out. Reason: {$forced['reason']}";
            $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, type, related_module, is_read, created_at) VALUES (?, ?, 'security', 'system', 0, NOW())");
            if ($stmt) {
                while($a = mysqli_fetch_assoc($admins)) {
                    mysqli_stmt_bind_param($stmt, "is", $a['id'], $msg);
                    mysqli_stmt_execute($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        execute_system_logout($conn, $user_id, $session_id, 'forced', $forced['reason']);
        display_interceptor_modal("System Administrator Action", "You have been logged out by the system administrator.", "/RMU-Medical-Management-System/php/index.php?forced=1", 'forced');
    }

    // 2. Check Session Timeout (Feature 4)
    // Silently patch table if column is missing (safe check to avoid duplicate column error)
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM login_security_config LIKE 'session_timeout_minutes'");
    if ($col_check && mysqli_num_rows($col_check) === 0) {
        mysqli_query($conn, "ALTER TABLE login_security_config ADD COLUMN session_timeout_minutes INT DEFAULT 30");
    }
    
    $cq = mysqli_query($conn, "SELECT session_timeout_minutes FROM login_security_config LIMIT 1");
    $timeout_mins = ($cq && mysqli_num_rows($cq) > 0) ? (int)mysqli_fetch_assoc($cq)['session_timeout_minutes'] : 30;
    
    $aq = mysqli_prepare($conn, "SELECT last_active FROM active_sessions WHERE session_id = ?");
    mysqli_stmt_bind_param($aq, 's', $session_id);
    mysqli_stmt_execute($aq);
    $ares = mysqli_stmt_get_result($aq);
    if ($arow = mysqli_fetch_assoc($ares)) {
        $last_active = strtotime($arow['last_active']);
        if ((time() - $last_active) > ($timeout_mins * 60)) {
            // Timed out
            execute_system_logout($conn, $user_id, $session_id, 'timeout', 'Session inactive for ' . $timeout_mins . ' minutes.');
            display_interceptor_modal("Session Expired", "Your session has expired due to inactivity. You have been logged out for your security.", "/RMU-Medical-Management-System/php/index.php?timeout=1", 'timeout');
        }
    }

    // 3. Update last_active on every validated load
    $upd = mysqli_prepare($conn, "UPDATE active_sessions SET last_active = NOW() WHERE session_id = ?");
    mysqli_stmt_bind_param($upd, 's', $session_id);
    mysqli_stmt_execute($upd);
}

/**
 * Executes a hard teardown of the server session during a forced/timeout event.
 */
function execute_system_logout($conn, $user_id, $session_id, $type, $reason) {
    // A. Remove from active_sessions
    $ds = mysqli_prepare($conn, "DELETE FROM active_sessions WHERE session_id = ?");
    mysqli_stmt_bind_param($ds, 's', $session_id);
    @mysqli_stmt_execute($ds);
    
    // B. Log to logout_logs
    $role = $_SESSION['role'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
    $device = (strpos(strtolower($ua), 'mobile') !== false) ? 'Mobile' : 'Desktop';
    
    $ins = mysqli_prepare($conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, ip_address, device_info, browser, dashboard_origin) VALUES (?, ?, ?, ?, ?, ?, ?, 'system_interceptor')");
    if ($ins) {
        mysqli_stmt_bind_param($ins, 'issssss', $user_id, $role, $session_id, $type, $ip, $device, $ua);
        @mysqli_stmt_execute($ins);
    }
    
    // C. Remove Remember Me
    if (isset($_COOKIE['rmumss_remember'])) {
        $tokenHash = hash('sha256', $_COOKIE['rmumss_remember']);
        $dt = mysqli_prepare($conn, "DELETE FROM remember_me_tokens WHERE token_hash = ?");
        mysqli_stmt_bind_param($dt, 's', $tokenHash);
        @mysqli_stmt_execute($dt);
        setcookie('rmumss_remember', '', time() - 3600, '/');
    }

    // D. Destroy PHP session completely
    $_SESSION = [];
    session_unset();
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/**
 * Outputs a full-screen HTML modal to the user explaining the interruption.
 * Halts PHP execution immediately.
 */
function display_interceptor_modal($title, $message, $redirectUrl, $type) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['error' => $message, 'redirect' => $redirectUrl, 'type' => $type]);
        exit;
    }

    $iconColor = ($type === 'timeout') ? 'linear-gradient(135deg, #E67E22, #d35400)' : 'linear-gradient(135deg, #e74c3c, #c0392b)';
    $iconShadow = ($type === 'timeout') ? 'rgba(230, 126, 34, 0.3)' : 'rgba(231, 76, 60, 0.3)';
    $symbol = ($type === 'timeout') ? '&#9201;' : '&#9888;';

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>'.htmlspecialchars($title).'</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { margin: 0; font-family: "Poppins", sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #2F80ED 0%, #56CCF2 50%, #2F80ED 100%); }
            .rmu-modal { background: #fff; border-radius: 16px; padding: 2.5rem 2rem; width: 90%; max-width: 420px; text-align: center; box-shadow: 0 15px 40px rgba(47, 128, 237, 0.2); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
            @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
            .rmu-icon { width: 64px; height: 64px; background: '.$iconColor.'; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #fff; font-size: 1.8rem; box-shadow: 0 8px 24px '.$iconShadow.'; }
            h3 { color: #2c3e50; font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 600; }
            p { color: #7f8c8d; font-size: 0.95rem; margin-bottom: 2rem; line-height: 1.5; }
            .rmu-btn { display: inline-block; box-sizing: border-box; width: 100%; padding: 0.85rem; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; background: linear-gradient(135deg, #2F80ED, #56CCF2); color: #fff; box-shadow: 0 4px 15px rgba(47, 128, 237, 0.3); transition: all 0.2s; }
            .rmu-btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="rmu-modal">
            <div class="rmu-icon">'.$symbol.'</div>
            <h3>'.htmlspecialchars($title).'</h3>
            <p>'.htmlspecialchars($message).'</p>
            <a href="'.$redirectUrl.'" class="rmu-btn">Go to Login</a>
        </div>
        <script>setTimeout(function(){ window.location.href = "'.$redirectUrl.'"; }, 5000);</script>
    </body>
    </html>';
    exit;
}

// Keep existing routing & lock helpers below:
function routeUserByRole($role) {
    require_once __DIR__ . '/../login_router.php';
    return "/RMU-Medical-Management-System/php/login_router.php?role=" . urlencode($role);
}

function checkBruteForceLockout($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT locked_until FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row && $row['locked_until']) {
        $locked_epoch = strtotime($row['locked_until']);
        $now = time();
        if ($locked_epoch > $now) {
            return ['is_locked' => true, 'time_remaining' => $locked_epoch - $now];
        } else {
            mysqli_query($conn, "UPDATE users SET locked_until = NULL WHERE id = " . (int)$user_id);
        }
    }
    return ['is_locked' => false, 'time_remaining' => 0];
}

function logAuthenticationEvent($user_id, $action) {}
?>
