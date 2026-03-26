<?php
/**
 * resend_2fa_otp.php — AJAX endpoint to resend 2FA OTP
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/../includes/reg_mailer.php';
header('Content-Type: application/json');

$uid = (int)($_SESSION['2fa_pending_uid'] ?? 0);
if (!$uid) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']); exit;
}

// CSRF
$posted_csrf = $_POST['_csrf'] ?? '';
if (empty($_SESSION['_2fa_csrf']) || !hash_equals($_SESSION['_2fa_csrf'], $posted_csrf)) {
    echo json_encode(['success' => false, 'message' => 'CSRF error.']); exit;
}

// Check resend limit
$rc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(resends_made) AS total_resends FROM two_factor_attempts WHERE user_id=$uid AND is_used=0"));
if ((int)($rc['total_resends'] ?? 0) >= 3) {
    echo json_encode(['success' => false, 'message' => 'Maximum resends reached.']); exit;
}

// Invalidate previous and issue new OTP
$otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otp_hash   = password_hash($otp, PASSWORD_BCRYPT);
$otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
$ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

mysqli_query($conn, "UPDATE two_factor_attempts SET is_used=1, resends_made=resends_made+1 WHERE user_id=$uid AND is_used=0");
$ins = mysqli_prepare($conn,
    "INSERT INTO two_factor_attempts (user_id,otp_hash,expires_at,ip_address) VALUES (?,?,?,?)");
mysqli_stmt_bind_param($ins, 'isss', $uid, $otp_hash, $otp_expiry, $ip);
mysqli_stmt_execute($ins);

// Fetch email & send
$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, name FROM users WHERE id=$uid LIMIT 1"));
if (function_exists('reg_send_2fa_email')) {
    @reg_send_2fa_email($conn, $u['email'], $u['name'], $otp);
}

echo json_encode(['success' => true, 'message' => 'OTP resent.']);
