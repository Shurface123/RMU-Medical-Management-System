<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/AuditLogger.php';
header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'unknown';
$sid = session_id();
$csrf = $_POST['csrf'] ?? '';
$origin = $_POST['origin'] ?? 'unknown';

// Verifications
if (!empty($_SESSION['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success'=>false, 'error'=>'Invalid CSRF token', 'redirect'=>'/RMU-Medical-Management-System/php/index.php']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
function _os($ua) { if (preg_match('/win/i', $ua)) return 'Windows'; if (preg_match('/mac/i', $ua)) return 'Mac'; return 'Unknown'; }
function _br($ua) { if (preg_match('/chrome/i', $ua)) return 'Chrome'; if(preg_match('/safari/i', $ua)) return 'Safari'; return 'Unknown'; }

// Health Cache
$msg = $_SESSION['health_message_shown'] ?? 'None';

// Redirect config
$qCfg = mysqli_query($conn, "SELECT redirect_url, countdown_duration_seconds FROM logout_config LIMIT 1");
$cfg = mysqli_fetch_assoc($qCfg);
$redir = ($cfg && !empty($cfg['redirect_url'])) ? $cfg['redirect_url'] : '/RMU-Medical-Management-System/php/index.php';
$dur = ($cfg) ? $cfg['countdown_duration_seconds'] : 3;

if ($uid) {
    // 1. active_sessions
    mysqli_query($conn, "DELETE FROM active_sessions WHERE session_id='$sid'");
    // 2. logout_logs
    $sL = mysqli_prepare($conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, logout_confirmed_at, countdown_duration, ip_address, device_info, browser, dashboard_logged_out_from, health_message_shown) VALUES (?,?,?, 'manual', NOW(), ?, ?, ?, ?, ?, ?)");
    if ($sL) {
        $os = _os($ua); $br = _br($ua);
        mysqli_stmt_bind_param($sL, "ississsss", $uid, $role, $sid, $dur, $ip, $os, $br, $origin, $msg);
        mysqli_stmt_execute($sL);
    }
    // 3. cookies
    if (isset($_COOKIE['rmumss_remember'])) {
        $h = hash('sha256', $_COOKIE['rmumss_remember']);
        mysqli_query($conn, "DELETE FROM remember_me_tokens WHERE token_hash='$h'");
        setcookie('rmumss_remember', '', time() - 3600, '/');
    }
    // 4. Audit
    if (class_exists('AuditLogger')) {
        $audit = new AuditLogger($conn);
        $audit->log($uid, 'manual_logout', 'users', $uid, null, ['notes' => 'User logged out via AJAX handler.']);
    }
}

$_SESSION = [];
session_destroy();

echo json_encode(['success'=>true, 'redirect'=>$redir]);