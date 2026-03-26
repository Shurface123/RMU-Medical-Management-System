<?php
// ajax/resend_otp.php — Generate a new OTP and resend to user's email
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/db_conn.php';
require_once dirname(__DIR__) . '/includes/reg_config.php';
require_once dirname(__DIR__) . '/includes/reg_mailer.php';

// CSRF
if (empty($_POST['_csrf']) || empty($_SESSION['_otp_csrf'])
    || !hash_equals($_SESSION['_otp_csrf'], $_POST['_csrf'])) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid security token']); exit;
}

// Session guard
if (empty($_SESSION['reg_ver_id']) || empty($_SESSION['reg_email'])) {
    echo json_encode(['ok'=>false,'msg'=>'Session expired. Please register again.']); exit;
}

$email   = $_SESSION['reg_email'];
$name    = $_SESSION['reg_name'] ?? 'User';
$ver_id  = $_SESSION['reg_ver_id'];

// Check current resend count in email_verifications
$s = mysqli_prepare($conn,
    "SELECT id, attempts_made FROM email_verifications
     WHERE verification_id=? AND is_used=0 LIMIT 1");
mysqli_stmt_bind_param($s,'s',$ver_id);
mysqli_stmt_execute($s);
$res = mysqli_stmt_get_result($s);
$row = $res ? mysqli_fetch_assoc($res) : null;

if (!$row) {
    echo json_encode(['ok'=>false,'msg'=>'Verification record not found.']); exit;
}

// Generate fresh OTP
$otp_plain  = str_pad((string)random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
$otp_hash   = password_hash($otp_plain, PASSWORD_BCRYPT);
$otp_expiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
$new_ver_id = bin2hex(random_bytes(32));

// Invalidate old record, insert new one
mysqli_query($conn,
    "UPDATE email_verifications SET is_used=1
     WHERE verification_id='" . mysqli_real_escape_string($conn,$ver_id) . "'");

$si = mysqli_prepare($conn,
    "INSERT INTO email_verifications
     (verification_id,user_id,email,otp_code,otp_expires_at,verification_type)
     VALUES (?,NULL,?,?,?,'registration')");
mysqli_stmt_bind_param($si,'ssss',$new_ver_id,$email,$otp_hash,$otp_expiry);
if (!mysqli_stmt_execute($si)) {
    echo json_encode(['ok'=>false,'msg'=>'Could not generate new OTP.']); exit;
}

// Update session
$_SESSION['reg_ver_id'] = $new_ver_id;

// Send email
$result = reg_send_otp_email($conn, $email, $name, $otp_plain);
if ($result['success']) {
    echo json_encode(['ok'=>true,'msg'=>'OTP resent successfully.']);
} else {
    echo json_encode(['ok'=>false,'msg'=>'Email delivery failed. Please try again.']);
}
