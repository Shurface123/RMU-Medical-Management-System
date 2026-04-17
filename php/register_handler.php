<?php
// register_handler.php — Advanced Registration Handler (Phase 3)
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once 'db_conn.php';
require_once 'includes/reg_config.php';
require_once 'includes/reg_mailer.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php'); exit;
}

// ── CSRF ─────────────────────────────────────────────────────
$csrf_ok = isset($_POST['_csrf'], $_SESSION['_reg_csrf'])
        && hash_equals($_SESSION['_reg_csrf'], $_POST['_csrf']);
if (!$csrf_ok) bail('Invalid security token. Please reload and try again.', 'register.php');

// ── Helpers ──────────────────────────────────────────────────
function bail($msg, $dest = 'register.php') {
    header('Location: ' . $dest . '?error=' . urlencode($msg)); exit;
}
function clean($v) {
    return htmlspecialchars(stripslashes(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}
function log_reg_audit($conn, $uid, $action, $ip, $ua, $notes = '') {
    $audit_id = 'URA-' . uniqid();
    $s = mysqli_prepare($conn,
        "INSERT INTO user_registration_audit 
         (audit_id,user_id,action,performed_by,ip_address,device_info,notes)
         VALUES (?,?,?,'self',?,?,?)");
    mysqli_stmt_bind_param($s,'sissss', $audit_id,$uid,$action,$ip,$ua,$notes);
    mysqli_stmt_execute($s);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

// ── IP Rate limiting ─────────────────────────────────────────
$rl = mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM user_registration_audit
     WHERE ip_address='$ip' AND action='registered'
     AND created_at > DATE_SUB(NOW(), INTERVAL ".REG_LOCKOUT_MINUTES." MINUTE)");
if ($rl && (int)mysqli_fetch_assoc($rl)['n'] >= REG_MAX_ATTEMPTS_PER_HOUR) {
    bail('Too many registration attempts from your IP. Please wait 1 hour before trying again.');
}

// ── Collect & sanitise inputs ─────────────────────────────────
$role         = clean($_POST['selected_role'] ?? '');
$patient_type = clean($_POST['patient_type'] ?? '');
$first_name   = clean($_POST['first_name'] ?? '');
$last_name    = clean($_POST['last_name']  ?? '');
$email        = strtolower(clean($_POST['email'] ?? ''));
$phone        = clean($_POST['phone'] ?? '');
$dob          = clean($_POST['dob']   ?? '');
$gender       = clean($_POST['gender'] ?? '');
$username     = clean($_POST['username'] ?? '');
$password     = $_POST['password']         ?? '';
$confirm_pw   = $_POST['confirm_password'] ?? '';
$department   = clean($_POST['department']    ?? '');
$specialization = clean($_POST['specialization'] ?? '');
$license_number = clean($_POST['license_number'] ?? '');
$experience_years = (int)($_POST['experience_years'] ?? 0);
$patient_id_number = clean($_POST['patient_id_number'] ?? '');
$blood_type   = clean($_POST['blood_type']     ?? '');
$emerg_name   = clean($_POST['emergency_name'] ?? '');
$emerg_phone  = clean($_POST['emergency_phone']?? '');
$recaptcha_token = clean($_POST['g-recaptcha-response'] ?? '');
$full_name    = "$first_name $last_name";

// ── Validate role ─────────────────────────────────────────────
$valid_roles = array_keys(REGISTERABLE_ROLES);
if (!in_array($role, $valid_roles)) bail('Invalid role selected.');

if ($role === 'patient' && !in_array($patient_type, ['student','staff']))
    bail('Please select a patient type (Student or Staff/Lecturer).');

// ── Required fields ───────────────────────────────────────────
if (!$first_name || !$last_name) bail('Full name is required.');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) bail('Invalid email address.');
if (!$phone || strlen(preg_replace('/\D/','',$phone)) < 10) bail('Invalid phone number.');
if (!$dob) bail('Date of birth is required.');
if (!$gender) bail('Gender is required.');
if (!$username || strlen($username) < 3) bail('Username must be at least 3 characters.');

// ── Email domain rules ─────────────────────────────────────────
$domain_rules = EMAIL_DOMAIN_RULES;
if ($role === 'patient') {
    $key = 'patient_' . $patient_type;
    if (isset($domain_rules[$key])) {
        $req_domain = $domain_rules[$key]['domain'];
        if (!str_ends_with($email, strtolower($req_domain)))
            bail($domain_rules[$key]['message']);
    }
}

// ── Password validation ───────────────────────────────────────
if (strlen($password) < PASSWORD_MIN_LENGTH)
    bail('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
if (PASSWORD_REQUIRE_UPPER && !preg_match('/[A-Z]/', $password))
    bail('Password must contain at least one uppercase letter.');
if (PASSWORD_REQUIRE_LOWER && !preg_match('/[a-z]/', $password))
    bail('Password must contain at least one lowercase letter.');
if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password))
    bail('Password must contain at least one number.');
if (PASSWORD_REQUIRE_SYMBOL && !preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:\'",.\\/<>?`~]/', $password))
    bail('Password must contain at least one special character.');
if ($password !== $confirm_pw)
    bail('Passwords do not match.');
if (stripos($password, $first_name) !== false || stripos($password, $last_name) !== false ||
    stripos($password, explode('@',$email)[0]) !== false)
    bail('Password must not contain your name or email address.');

// ── Duplicate email / username check ─────────────────────────
$s = mysqli_prepare($conn, 'SELECT id FROM users WHERE email=? LIMIT 1');
mysqli_stmt_bind_param($s,'s',$email);
mysqli_stmt_execute($s); mysqli_stmt_store_result($s);
if (mysqli_stmt_num_rows($s) > 0) bail('This email address is already registered.');

$s = mysqli_prepare($conn, 'SELECT id FROM users WHERE user_name=? LIMIT 1');
mysqli_stmt_bind_param($s,'s',$username);
mysqli_stmt_execute($s); mysqli_stmt_store_result($s);
if (mysqli_stmt_num_rows($s) > 0) bail('This username is already taken. Please choose another.');

// ── Duplicate license check (for clinical roles) ─────────────
if ($license_number && in_array($role, APPROVAL_REQUIRED_ROLES)) {
    $tbl_map = [
        'doctor'=>'doctors','nurse'=>'nurses',
        'lab_technician'=>'lab_technicians','pharmacist'=>'pharmacist_profile'
    ];
    if (isset($tbl_map[$role])) {
        $t = $tbl_map[$role];
        $col = 'license_number';
        $chk = @mysqli_query($conn,
            "SELECT id FROM `$t` WHERE `$col`='" .
            mysqli_real_escape_string($conn,$license_number) . "' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0)
            bail('This license number is already registered in our system.');
    }
}

// ── Profile photo upload ──────────────────────────────────────
$profile_image = null;
if (!empty($_FILES['profile_photo']['tmp_name'])) {
    $file = $_FILES['profile_photo'];
    if ($file['size'] > UPLOAD_MAX_SIZE)  bail('Profile photo must be under 2MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED_TYPES)) bail('Only JPG, PNG, or WebP images are allowed.');
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $ext  = ($mime === 'image/png') ? 'png' : (($mime === 'image/webp') ? 'webp' : 'jpg');
    $fname = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $fname))
        bail('Failed to upload profile photo. Please try again.');
    $profile_image = UPLOAD_URL_PATH . $fname;
}

// ── Hash password & store registration session ────────────────
$hash = password_hash($password, PASSWORD_BCRYPT);

// Determine activation state
$needs_approval = in_array($role, APPROVAL_REQUIRED_ROLES);
$is_active      = $needs_approval ? 0 : 1;
$account_status = $needs_approval ? 'pending_verification' : 'pending_verification';
// Both wait for OTP first; approval is a secondary gate for clinical roles.
$actual_user_role = $role;

// Store temp data in registration_sessions
$session_token = bin2hex(random_bytes(32));
$expires_at    = date('Y-m-d H:i:s', time() + REG_SESSION_EXPIRY_MINUTES * 60);
$temp_data     = json_encode([
    'first_name'=>$first_name,'last_name'=>$last_name,'full_name'=>$full_name,
    'email'=>$email,'phone'=>$phone,'dob'=>$dob,'gender'=>$gender,
    'username'=>$username,'password_hash'=>$hash,
    'role'=>$role,'actual_user_role'=>$actual_user_role,
    'patient_type'=>$patient_type,'department'=>$department,
    'specialization'=>$specialization,'license_number'=>$license_number,
    'experience_years'=>$experience_years,'patient_id_number'=>$patient_id_number,
    'blood_type'=>$blood_type,'emerg_name'=>$emerg_name,'emerg_phone'=>$emerg_phone,
    'profile_image'=>$profile_image,'needs_approval'=>$needs_approval,
    'is_active'=>$is_active,'ip'=>$ip,'ua'=>$ua,
]);

try {
    $s = mysqli_prepare($conn,
        "INSERT INTO registration_sessions
         (session_token,email,role,step_reached,temp_data,expires_at)
         VALUES (?,?,?,2,?,?)");
    mysqli_stmt_bind_param($s,'sssss', $session_token,$email,$role,$temp_data,$expires_at);
    if (!mysqli_stmt_execute($s)) throw new Exception('Session creation failed.');

    // ── Generate & send OTP ───────────────────────────────────────
    $otp_plain  = str_pad((string)random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    $otp_hash   = password_hash($otp_plain, PASSWORD_BCRYPT);
    $otp_expiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
    $ver_id     = bin2hex(random_bytes(32));

    $s = mysqli_prepare($conn,
        "INSERT INTO email_verifications
         (verification_id,user_id,email,otp_code,otp_expires_at,verification_type)
         VALUES (?,NULL,?,?,?,'registration')");
    mysqli_stmt_bind_param($s,'ssss', $ver_id,$email,$otp_hash,$otp_expiry);
    if (!mysqli_stmt_execute($s)) throw new Exception('Could not create verification record.');

    $mail_result = reg_send_otp_email($conn, $email, $full_name, $otp_plain);
    if (!$mail_result['success']) {
        error_log('OTP email failed for ' . $email . ': ' . ($mail_result['error'] ?? 'unknown'));
    }

    // Store session info for OTP page
    $_SESSION['reg_session_token'] = $session_token;
    $_SESSION['reg_ver_id']        = $ver_id;
    $_SESSION['reg_email']         = $email;
    $_SESSION['reg_name']          = $full_name;
    $_SESSION['_reg_csrf']         = bin2hex(random_bytes(32));

    // Safe local fallback for testing
    $redirectQuery = '';
    if (!$mail_result['success']) {
        $redirectQuery = '?warn=email';
        if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
            $redirectQuery .= '&dev_otp=' . urlencode($otp_plain);
        }
    }
    header('Location: verify_otp.php' . $redirectQuery); exit;
} catch (Exception $e) {
    bail('Error: ' . $e->getMessage());
}

