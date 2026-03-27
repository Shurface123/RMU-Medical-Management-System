<?php
/**
 * login.php — Advanced Login Handler (Phase 3)
 * Handles CSRF, auto role detection, brute-force, IP rate-limit,
 * account status checks, Remember Me, 2FA branch, force_password_change.
 */
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/login_router.php';
require_once __DIR__ . '/includes/reg_mailer.php';
require_once __DIR__ . '/classes/AuditLogger.php';

// ── Only process POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// ── Security helpers ─────────────────────────────────────────────────────────
function h(string $v): string {
    return htmlspecialchars(stripslashes(trim($v)), ENT_QUOTES, 'UTF-8');
}
function redirect_err(string $msg, array $extra = []): never {
    $q = http_build_query(array_merge(['error' => $msg], $extra));
    header("Location: index.php?$q"); exit;
}
function get_ip(): string {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return $_SERVER[$k];
    }
    return '0.0.0.0';
}

// ── 1. CSRF Validation ───────────────────────────────────────────────────────
$posted_csrf = $_POST['_csrf'] ?? '';
if (empty($_SESSION['_login_csrf']) || !hash_equals($_SESSION['_login_csrf'], $posted_csrf)) {
    redirect_err('Invalid security token. Please try again.');
}
// Rotate CSRF after validation
$_SESSION['_login_csrf'] = bin2hex(random_bytes(32));

// ── 2. Read & sanitise inputs ────────────────────────────────────────────────
$uname  = h($_POST['uname'] ?? '');
$pass   = $_POST['password'] ?? '';   // No htmlspecialchars on password
$remMe  = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

if ($uname === '' || $pass === '') {
    redirect_err('Username and password are required.');
}

// ── 3. Load security config ──────────────────────────────────────────────────
$cfg_res = mysqli_query($conn, "SELECT * FROM login_security_config WHERE id=1 LIMIT 1");
$cfg     = mysqli_fetch_assoc($cfg_res) ?: [];
$MAX_ATTEMPTS = (int)($cfg['max_attempts']        ?? 5);
$LOCK_MINS    = (int)($cfg['lockout_minutes']      ?? 15);
$IP_MAX       = (int)($cfg['ip_max_attempts']      ?? 20);
$IP_WINDOW    = (int)($cfg['ip_window_minutes']    ?? 60);
$REM_DAYS     = (int)($cfg['remember_me_days']     ?? 30);

$ip = get_ip();
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

// ── 4. IP-based Rate Limit ───────────────────────────────────────────────────
$ip_check = mysqli_prepare($conn,
    "SELECT COUNT(*) AS fails FROM login_attempts
     WHERE ip_address=? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
mysqli_stmt_bind_param($ip_check, 'si', $ip, $IP_WINDOW);
mysqli_stmt_execute($ip_check);
$ip_fails = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($ip_check))['fails'] ?? 0);
if ($ip_fails >= $IP_MAX) {
    redirect_err("Too many login attempts from your network. Please try again later.");
}

// ── 5. Fetch user by username OR email ──────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT * FROM users WHERE (user_name=? OR email=?) LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $uname, $uname);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);

// Helper: log failed attempt and possibly lock account
function log_failure(
    $conn, string $uname, string $ip, string $ua,
    string $reason, ?int $uid,
    int $MAX_ATTEMPTS, int $LOCK_MINS
): void {
    $st = mysqli_prepare($conn,
        "INSERT INTO login_attempts (username,user_id,ip_address,user_agent,failure_reason) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($st, 'sisss', $uname, $uid, $ip, $ua, $reason);
    mysqli_stmt_execute($st);

    if ($uid) {
        $audit = new AuditLogger($conn);
        $audit->logLogin($uid, false);
        // Count recent fails for THIS user
        $cf = mysqli_prepare($conn,
            "SELECT COUNT(*) AS c FROM login_attempts
             WHERE user_id=? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
               AND failure_reason NOT LIKE '%locked%'");
        mysqli_stmt_bind_param($cf, 'ii', $uid, $LOCK_MINS);
        mysqli_stmt_execute($cf);
        $fails = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cf))['c'] ?? 0);

        if ($fails >= $MAX_ATTEMPTS) {
            // Lock account
            $lu = mysqli_prepare($conn,
                "UPDATE users SET locked_until=DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id=?");
            mysqli_stmt_bind_param($lu, 'ii', $LOCK_MINS, $uid);
            mysqli_stmt_execute($lu);

            // Notify admins
            $msg = "Security Alert: Account '$uname' locked due to $fails failed login attempts from $ip.";
            $ni  = mysqli_prepare($conn,
                "INSERT INTO notifications (user_id,user_role,title,message,type,related_module,created_at)
                 SELECT id,user_role,'Security Alert',?,?,?,NOW() FROM users WHERE user_role='admin'");
            $type = 'Security Alert'; $mod = 'users';
            mysqli_stmt_bind_param($ni, 'sss', $msg, $type, $mod);
            @mysqli_stmt_execute($ni);
        }
    }
}

// ── 6. User not found ────────────────────────────────────────────────────────
if (!$row) {
    log_failure($conn, $uname, $ip, $ua, 'user_not_found', null, $MAX_ATTEMPTS, $LOCK_MINS);
    redirect_err('Incorrect username or password.');
}

$uid  = (int)$row['id'];
$role = $row['user_role'];

// ── 7. Account Status Checks BEFORE password ────────────────────────────────

// a) is_active
if (!$row['is_active']) {
    redirect_err('Your account is inactive. Please contact the administrator.');
}

// b) Email verification (if column exists)
if (isset($row['is_verified']) && !(int)$row['is_verified']) {
    redirect_err('Please verify your email before logging in. Check your inbox for the verification email.');
}

// c) locked_until
if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
    $remaining_epoch = strtotime($row['locked_until']);
    $rem_mins = ceil(($remaining_epoch - time()) / 60);
    header("Location: index.php?error=" . urlencode("Account locked due to multiple failed attempts. Try again in {$rem_mins} minute(s).")
         . "&locked_until={$remaining_epoch}");
    exit;
}

// Auto-clear expired lockout
if (!empty($row['locked_until']) && strtotime($row['locked_until']) <= time()) {
    mysqli_query($conn, "UPDATE users SET locked_until=NULL WHERE id={$uid}");
}

// d) account_status checks
$status = $row['account_status'] ?? 'active';
switch ($status) {
    case 'pending':
        redirect_err('Your account is pending administrator approval. You will be notified by email once approved.');
    case 'suspended':
        redirect_err('Your account has been suspended. Please contact the administrator.');
    case 'inactive':
        redirect_err('Your account is currently inactive. Please contact the administrator.');
    case 'rejected':
        $reason = ($row['rejection_reason'] ?? '') ?: 'Contact administration for details.';
        redirect_err("Your account was rejected: $reason");
}

// ── 8. Password verification ─────────────────────────────────────────────────
$password_valid = false;
if (password_verify($pass, $row['password'])) {
    $password_valid = true;
} elseif (strlen($row['password']) === 32 && md5($pass) === $row['password']) {
    // Legacy MD5 — upgrade silently
    $password_valid = true;
    $new_hash = password_hash($pass, PASSWORD_BCRYPT);
    $upd = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
    mysqli_stmt_bind_param($upd, 'si', $new_hash, $uid);
    mysqli_stmt_execute($upd);
}

if (!$password_valid) {
    log_failure($conn, $uname, $ip, $ua, 'wrong_password', $uid, $MAX_ATTEMPTS, $LOCK_MINS);

    // Check if NOW locked after this failure
    $check = mysqli_prepare($conn, "SELECT locked_until FROM users WHERE id=?");
    mysqli_stmt_bind_param($check, 'i', $uid);
    mysqli_stmt_execute($check);
    $ck = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    if (!empty($ck['locked_until']) && strtotime($ck['locked_until']) > time()) {
        $ep = strtotime($ck['locked_until']);
        header("Location: index.php?error=" . urlencode("Account locked due to multiple failed attempts. Try again later.") . "&locked_until=$ep");
        exit;
    }
    redirect_err('Incorrect username or password.');
}

// ── 9. Role-specific approval checks ─────────────────────────────────────────
$APPROVAL_ROLES = ['nurse', 'lab_technician', 'doctor', 'pharmacist'];
$STAFF_SUB_ROLES = ['ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff'];

if (in_array($role, $STAFF_SUB_ROLES)) {
    $aq = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM staff WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($aq, 'i', $uid);
    mysqli_stmt_execute($aq);
    $arow = mysqli_fetch_assoc(mysqli_stmt_get_result($aq));
    $appr = $arow['approval_status'] ?? 'pending';
    if ($appr === 'pending')  redirect_err('Your account is pending admin approval. You will be notified once approved.');
    if ($appr === 'rejected') redirect_err('Account rejected: ' . ($arow['rejection_reason'] ?? 'Contact administration.'));
}

if ($role === 'nurse') {
    $aq = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM nurses WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($aq, 'i', $uid);
    mysqli_stmt_execute($aq);
    $arow = mysqli_fetch_assoc(mysqli_stmt_get_result($aq));
    if ($arow) {
        if ($arow['approval_status'] === 'pending')  redirect_err('Your nursing account is pending admin approval.');
        if ($arow['approval_status'] === 'rejected') redirect_err('Nursing account rejected: ' . ($arow['rejection_reason'] ?? 'Contact administration.'));
    }
}

if ($role === 'lab_technician') {
    $aq = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM lab_technicians WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($aq, 'i', $uid);
    mysqli_stmt_execute($aq);
    $arow = mysqli_fetch_assoc(mysqli_stmt_get_result($aq));
    if ($arow) {
        if ($arow['approval_status'] === 'pending')  redirect_err('Your lab technician account is pending admin approval.');
        if ($arow['approval_status'] === 'rejected') redirect_err('Lab technician account rejected: ' . ($arow['rejection_reason'] ?? 'Contact administration.'));
    }
}

if ($role === 'doctor') {
    $aq = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM doctors WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($aq, 'i', $uid);
    mysqli_stmt_execute($aq);
    $arow = mysqli_fetch_assoc(mysqli_stmt_get_result($aq));
    if ($arow) {
        if ($arow['approval_status'] === 'pending')  redirect_err('Your doctor account is pending admin approval.');
        if ($arow['approval_status'] === 'rejected') redirect_err('Doctor account rejected: ' . ($arow['rejection_reason'] ?? 'Contact administration.'));
    }
}

if ($role === 'pharmacist') {
    $aq = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM pharmacist_profile WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($aq, 'i', $uid);
    mysqli_stmt_execute($aq);
    $arow = mysqli_fetch_assoc(mysqli_stmt_get_result($aq));
    if ($arow) {
        if ($arow['approval_status'] === 'pending')  redirect_err('Your pharmacist account is pending admin approval.');
        if ($arow['approval_status'] === 'rejected') redirect_err('Pharmacist account rejected: ' . ($arow['rejection_reason'] ?? 'Contact administration.'));
    }
}

// ── 10. Password correct — start session & set variables ─────────────────────
$sessionManager = new SessionManager($conn);
$sessionManager->startSession($uid, $role);
// NOTE: session_regenerate_id(true) is called inside startSession() above.
// Do NOT call it again here — it would change the PHP session ID after startSession
// has already recorded the correct ID in active_sessions and $_SESSION['session_id'].

$_SESSION['user_id']       = $uid;
$_SESSION['user_name']     = $row['user_name'];
$_SESSION['name']          = $row['name'];
$_SESSION['role']          = $role;
$_SESSION['user_role']     = $role;
$_SESSION['profile_image'] = $row['profile_image'] ?? 'default-avatar.png';
$_SESSION['email']         = $row['email'];
$_SESSION['login_ip']      = $ip;

// Update last login info
$upd = mysqli_prepare($conn,
    "UPDATE users SET locked_until=NULL, last_login_at=NOW(), last_login_ip=? WHERE id=?");
mysqli_stmt_bind_param($upd, 'si', $ip, $uid);
@mysqli_stmt_execute($upd);

// Log success
$audit = new AuditLogger($conn);
$audit->logLogin($uid, true);

$log_suc = mysqli_prepare($conn,
    "INSERT INTO login_attempts (username,user_id,ip_address,user_agent,failure_reason) VALUES (?,?,'".
    mysqli_real_escape_string($conn, $ip)."',?,'login_success')");
mysqli_stmt_bind_param($log_suc, 'sis', $row['user_name'], $uid, $ua);
mysqli_stmt_execute($log_suc);

// NOTE: active_sessions INSERT is handled inside SessionManager::startSession() above.
// Removed duplicate INSERT to prevent conflicting session_id rows in the database.

// ── 11. Remember Me Token ────────────────────────────────────────────────────
if ($remMe) {
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash  = hash('sha256', $plainToken);
    $expires    = date('Y-m-d H:i:s', strtotime("+{$REM_DAYS} days"));

    // Clear old tokens for this user
    $del = mysqli_prepare($conn, "DELETE FROM remember_me_tokens WHERE user_id=?");
    mysqli_stmt_bind_param($del, 'i', $uid);
    mysqli_stmt_execute($del);

    $ins = mysqli_prepare($conn,
        "INSERT INTO remember_me_tokens (user_id,token_hash,expires_at) VALUES (?,?,?)");
    mysqli_stmt_bind_param($ins, 'iss', $uid, $tokenHash, $expires);
    mysqli_stmt_execute($ins);

    setcookie('rmumss_remember', $plainToken, [
        'expires'  => strtotime("+{$REM_DAYS} days"),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ── 12. 2FA Check ────────────────────────────────────────────────────────────
if (!empty($row['two_fa_enabled'])) {
    // Store minimal state for 2FA screen; clear full session role until verified
    $_SESSION['2fa_pending_uid']  = $uid;
    $_SESSION['2fa_pending_role'] = $role;
    $_SESSION['2fa_remember']     = $remMe;
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user_role']);

    // Generate and send OTP
    $otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_hash   = password_hash($otp, PASSWORD_BCRYPT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Invalidate old attempts
    mysqli_query($conn, "UPDATE two_factor_attempts SET is_used=1 WHERE user_id=$uid AND is_used=0");
    $oi = mysqli_prepare($conn,
        "INSERT INTO two_factor_attempts (user_id,otp_hash,expires_at,ip_address) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($oi, 'isss', $uid, $otp_hash, $otp_expiry, $ip);
    mysqli_stmt_execute($oi);

    // Send OTP email
    if (function_exists('reg_send_2fa_email')) {
        @reg_send_2fa_email($conn, $row['email'], $row['name'], $otp);
    }

    header('Location: two_factor_verify.php');
    exit;
}

// ── 13. Force Password Change ─────────────────────────────────────────────────
if (!empty($row['force_password_change'])) {
    header('Location: change_password.php?forced=1');
    exit;
}

// ── 14. Route to dashboard ────────────────────────────────────────────────────
login_route($role);