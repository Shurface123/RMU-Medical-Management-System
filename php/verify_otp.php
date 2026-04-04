<?php
// verify_otp.php — OTP Email Verification Screen (Phase 3)
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once 'db_conn.php';
require_once 'includes/reg_config.php';
require_once 'includes/reg_mailer.php';

// Guard: must arrive via register_handler
if (empty($_SESSION['reg_ver_id']) || empty($_SESSION['reg_email'])) {
    header('Location: register.php?error=' . urlencode('Session expired. Please register again.')); exit;
}

$email       = $_SESSION['reg_email'];
$full_name   = $_SESSION['reg_name'] ?? 'User';
$ver_id      = $_SESSION['reg_ver_id'];
$warn_email  = isset($_GET['warn']) && $_GET['warn'] === 'email';

// Regenerate OTP CSRF
if (empty($_SESSION['_otp_csrf'])) $_SESSION['_otp_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['_otp_csrf'];

// ── Handle POST (OTP submission) ──────────────────────────────
$otp_error   = '';
$otp_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_digits'])) {

    // CSRF guard
    if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['_otp_csrf'], $_POST['_csrf']))
        bail_otp('Invalid security token.');

    $otp_plain = implode('', array_map('intval', $_POST['otp_digits'] ?? []));
    $otp_plain = preg_replace('/\D/', '', substr($otp_plain, 0, OTP_LENGTH));

    // Fetch verification row
    $s = mysqli_prepare($conn,
        "SELECT id,otp_code,otp_expires_at,attempts_made,is_used
         FROM email_verifications WHERE verification_id=? LIMIT 1");
    mysqli_stmt_bind_param($s,'s',$ver_id);
    mysqli_stmt_execute($s);
    $res = mysqli_stmt_get_result($s);
    $row = $res ? mysqli_fetch_assoc($res) : null;

    if (!$row)          $otp_error = 'Verification record not found. Please register again.';
    elseif ($row['is_used']) $otp_error = 'This OTP has already been used.';
    elseif (strtotime($row['otp_expires_at']) < time()) $otp_error = 'OTP has expired. Please request a new one.';
    elseif ((int)$row['attempts_made'] >= OTP_MAX_ATTEMPTS)
        $otp_error = 'Maximum attempts exceeded. Please start registration again.';
    else {
        // Increment attempt
        mysqli_query($conn,
            "UPDATE email_verifications SET attempts_made=attempts_made+1 WHERE id={$row['id']}");

        if (!password_verify($otp_plain, $row['otp_code'])) {
            $remaining = OTP_MAX_ATTEMPTS - ((int)$row['attempts_made'] + 1);
            $otp_error = "Incorrect OTP. $remaining attempt(s) remaining.";
        } else {
            // ── OTP CORRECT — Create user account ──────────────
            $token   = $_SESSION['reg_session_token'] ?? '';
            $sess_q  = mysqli_prepare($conn,
                "SELECT temp_data FROM registration_sessions WHERE session_token=? LIMIT 1");
            mysqli_stmt_bind_param($sess_q,'s',$token);
            mysqli_stmt_execute($sess_q);
            $sess_res = mysqli_stmt_get_result($sess_q);
            $sess_row = $sess_res ? mysqli_fetch_assoc($sess_res) : null;
            $td       = $sess_row ? json_decode($sess_row['temp_data'], true) : null;

            if (!$td) {
                $otp_error = 'Registration session expired. Please register again.';
            } else {
                $needs_approval = (bool)($td['needs_approval'] ?? false);
                $is_active      = $needs_approval ? 0 : 1;
                $account_status = $needs_approval ? 'pending' : 'active';

                // Insert into users
                $s_ins = mysqli_prepare($conn,
                    "INSERT INTO users
                     (user_name,email,password,user_role,patient_type,name,phone,
                      gender,date_of_birth,profile_image,is_active,is_verified,
                      account_status,created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,1,?,NOW())");
                $role_val = $td['actual_user_role'];
                $pt_val   = $td['patient_type'] ?: null;
                mysqli_stmt_bind_param($s_ins,
                    'ssssssssssis',
                    $td['username'],$td['email'],$td['password_hash'],
                    $role_val,$pt_val,$td['full_name'],$td['phone'],
                    $td['gender'],$td['dob'],$td['profile_image'],
                    $is_active, $account_status);
                if (!mysqli_stmt_execute($s_ins)) {
                    $otp_error = 'Account creation failed: ' . mysqli_error($conn);
                } else {
                    $new_uid = (int)mysqli_insert_id($conn);

                    // ── Role-specific record ────────────────────
                    $r = $td['role'];
                    $dept_id = null;
                    if (!empty($td['department'])) {
                        // Attempt to find department ID
                        $dq = mysqli_prepare($conn, "SELECT id FROM departments WHERE name=? LIMIT 1");
                        mysqli_stmt_bind_param($dq, 's', $td['department']);
                        mysqli_stmt_execute($dq);
                        $dr = mysqli_stmt_get_result($dq);
                        if ($row_d = mysqli_fetch_assoc($dr)) {
                            $dept_id = (int)$row_d['id'];
                        }
                    }

                    if ($r === 'patient') {
                        $pat_id = 'PAT-' . strtoupper(bin2hex(random_bytes(3)));
                        $si = mysqli_prepare($conn,
                            "INSERT INTO patients
                             (user_id,patient_id,full_name,gender,patient_type,
                              blood_group,emergency_contact_name,emergency_contact_phone,
                              created_at)
                             VALUES (?,?,?,?,?,?,?,?,NOW())");
                        $p_type = ucfirst($td['patient_type']);
                        mysqli_stmt_bind_param($si,'isssssss',
                            $new_uid,$pat_id,$td['full_name'],$td['gender'],
                            $p_type,$td['blood_type'],$td['emerg_name'],$td['emerg_phone']);
                        @mysqli_stmt_execute($si);
                    } elseif ($r === 'doctor') {
                        $did = 'DOC-' . strtoupper(bin2hex(random_bytes(3)));
                        $si = mysqli_prepare($conn,
                            "INSERT INTO doctors
                             (user_id,doctor_id,full_name,gender,specialization,
                              department_id,license_number,experience_years,
                              availability_status,created_at)
                             VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                        $st_d = 'Offline';
                        mysqli_stmt_bind_param($si,'isssssiss',
                            $new_uid,$did,$td['full_name'],$td['gender'],
                            $td['specialization'],$td['license_number'],
                            $dept_id,$td['experience_years'],$st_d);
                        @mysqli_stmt_execute($si);
                    } elseif ($r === 'nurse') {
                        $nid = 'NRS-' . strtoupper(bin2hex(random_bytes(3)));
                        $si = mysqli_prepare($conn,
                            "INSERT INTO nurses
                             (user_id,nurse_id,full_name,gender,license_number,
                              specialization,department_id,years_of_experience,
                              status,approval_status,created_at)
                             VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
                        $st_n = 'Inactive'; $ap_n = 'pending';
                        $dept_id_n = (int)($dept_id ?? 0);
                        $exp_n = (int)($td['experience_years'] ?? 0);
                        mysqli_stmt_bind_param($si,'isssssiss' . 's',
                            $new_uid,$nid,$td['full_name'],$td['gender'],
                            $td['license_number'],$td['specialization'],
                            $dept_id_n,$exp_n,$st_n,$ap_n);
                        @mysqli_stmt_execute($si);
                    } elseif ($r === 'lab_technician') {
                        $lid = 'LT-' . strtoupper(bin2hex(random_bytes(3)));
                        $si = mysqli_prepare($conn,
                            "INSERT INTO lab_technicians
                             (user_id,technician_id,full_name,gender,license_number,
                              specialization,department_id,years_of_experience,
                              approval_status,created_at)
                             VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                        $ap_l = 'pending';
                        mysqli_stmt_bind_param($si,'isssssiss',
                            $new_uid,$lid,$td['full_name'],$td['gender'],
                            $td['license_number'],$td['specialization'],
                            $dept_id,$td['experience_years'],$ap_l);
                        @mysqli_stmt_execute($si);
                    } elseif ($r === 'pharmacist') {
                        $si = mysqli_prepare($conn,
                            "INSERT INTO pharmacist_profile
                             (user_id,full_name,gender,license_number,
                              specialization,department,years_of_experience,
                              availability_status,created_at)
                             VALUES (?,?,?,?,?,?,?,?,NOW())");
                        $st_p = 'Offline';
                        $exp_int = (int)($td['experience_years'] ?? 0);
                        mysqli_stmt_bind_param($si,'isssssiss',
                            $new_uid,$td['full_name'],$td['gender'],
                            $td['license_number'],$td['specialization'],
                            $td['department'],$exp_int,$st_p);
                        @mysqli_stmt_execute($si);
                        mysqli_query($conn, "UPDATE users SET is_active=0 WHERE id=$new_uid");
                    }

                    // Mark OTP used
                    mysqli_query($conn,
                        "UPDATE email_verifications SET is_used=1
                         WHERE verification_id='" .
                         mysqli_real_escape_string($conn,$ver_id) . "'");

                    // Clear reg session
                    if ($token) {
                        $sd = mysqli_prepare($conn,
                            "DELETE FROM registration_sessions WHERE session_token=?");
                        mysqli_stmt_bind_param($sd,'s',$token);
                        mysqli_stmt_execute($sd);
                    }

                    // Audit log
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
                    $audit_id = 'URA-' . uniqid();
                    $act = 'otp_verified';
                    $si = mysqli_prepare($conn,
                        "INSERT INTO user_registration_audit
                         (audit_id,user_id,action,performed_by,ip_address,device_info)
                         VALUES (?,?,?,'self',?,?)");
                    mysqli_stmt_bind_param($si,'sisss',
                        $audit_id,$new_uid,$act,$ip,$ua);
                    mysqli_stmt_execute($si);

                    // Admin notification for approval-required roles
                    if ($needs_approval) {
                        $role_label = ucwords(str_replace('_',' ',$td['role']));
                        $ntitle = "New {$role_label} Registration";
                        $msg = "New {$role_label} registration pending approval: {$td['full_name']} ({$td['email']})";
                        $ntype = 'New Registration'; $nmod = 'users';
                        $ni = mysqli_prepare($conn,
                            "INSERT INTO notifications
                             (user_id,user_role,title,message,type,related_module,created_at)
                             SELECT id,user_role,?,?,?,?,NOW()
                             FROM users WHERE user_role='admin'");
                        mysqli_stmt_bind_param($ni,'ssss',$ntitle,$msg,$ntype,$nmod);
                        @mysqli_stmt_execute($ni);
                    }

                    // Send welcome email
                    @reg_send_welcome_email($conn, $td['email'], $td['full_name'],
                        $td['role'], $needs_approval);

                    // Clear OTP session
                    unset($_SESSION['reg_ver_id'],$_SESSION['reg_email'],
                          $_SESSION['reg_name'],$_SESSION['reg_session_token'],
                          $_SESSION['_otp_csrf']);

                    $success_msg = $needs_approval
                        ? 'Registration complete! Your account is pending admin approval. You will be notified by email once approved.'
                        : 'Registration successful! Your email has been verified. You may now log in.';
                    header('Location: index.php?success=' . urlencode($success_msg)); exit;
                }
            }
        }
    }
}

function bail_otp($msg) {
    header('Location: verify_otp.php?error=' . urlencode($msg)); exit;
}

// Display any URL error
$url_err = clean($_GET['error'] ?? '');

function clean($v) {
    return htmlspecialchars(stripslashes(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}

$otp_expiry_secs = OTP_EXPIRY_MINUTES * 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Verify Email — RMU Medical Sickbay</title>
<link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#2F80ED;--secondary:#56CCF2;--success:#27ae60;--danger:#e74c3c;--text-dark:#2c3e50;--text-muted:#7f8c8d;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 55%,#56CCF2 100%);padding:2rem 1rem;position:relative;overflow-x:hidden;}
body::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.06) 1px,transparent 1px);background-size:50px 50px;animation:bgMove 25s linear infinite;pointer-events:none;}
@keyframes bgMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.card{position:relative;z-index:10;background:#fff;border-radius:28px;box-shadow:0 20px 70px rgba(47,128,237,.25);width:100%;max-width:500px;overflow:hidden;animation:slideIn .5s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-30px)}to{opacity:1;transform:translateY(0)}}
.card-header{background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:2rem 2.5rem;text-align:center;position:relative;overflow:hidden;}
.card-header::after{content:'';position:absolute;right:-30px;top:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.07);}
.lock-icon{width:72px;height:72px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.6rem;color:#fff;margin:0 auto 1rem;border:2px solid rgba(255,255,255,.3);position:relative;}
.card-header h1{color:#fff;font-size:1.6rem;font-weight:700;margin:0 0 .4rem;}
.card-header p{color:rgba(255,255,255,.85);font-size:0.95rem;margin:0;}

.card-body{padding:2.5rem;}
.email-badge{background:#EBF3FF;border-radius:10px;padding:.8rem 1.2rem;display:flex;align-items:center;gap:.8rem;margin-bottom:2rem;font-size:1rem;color:var(--primary);font-weight:600;}

.otp-row{display:flex;gap:.8rem;justify-content:center;margin:2rem 0;}
.otp-box{width:42px;height:50px;border:2px solid #e0e0e0;border-radius:12px;font-size:1.8rem;font-weight:700;text-align:center;color:var(--text-dark);transition:all .2s;font-family:'Poppins',sans-serif;background:#fff;}
.otp-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px rgba(47,128,237,.12);}
.otp-box.filled{border-color:var(--primary);background:#EBF3FF;}
@media(max-width:400px){.otp-box{width:36px;height:44px;font-size:1.4rem;gap:.5rem;}}

.timer-row{text-align:center;margin-bottom:1.5rem;}
.timer-badge{display:inline-flex;align-items:center;gap:.5rem;background:#FEF9E7;color:#E67E22;border-radius:50px;padding:.5rem 1.2rem;font-size:1rem;font-weight:600;}
.timer-badge.expired{background:#FDEDEC;color:#c0392b;}

.alert{padding:1rem 1.2rem;border-radius:10px;font-size:0.95rem;margin-bottom:1.2rem;display:flex;align-items:flex-start;gap:.7rem;}
.alert-err{background:#FDEDEC;color:#c0392b;border-left:4px solid #e74c3c;}
.alert-warn{background:#FEF9E7;color:#7D6608;border-left:4px solid #f39c12;}
.alert-ok{background:#EAFAF1;color:#1e8449;border-left:4px solid #27ae60;}

.btn{width:100%;padding:0.8rem;font-size:1rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;transition:all .25s;font-family:'Poppins',sans-serif;margin-bottom:.8rem;display:flex;align-items:center;justify-content:center;gap:.6rem;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;box-shadow:0 6px 20px rgba(47,128,237,.3);}
.btn-primary:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 28px rgba(47,128,237,.4);}
.btn-primary:disabled{background:#bdc3c7;cursor:not-allowed;box-shadow:none;}
.btn-outline{background:#fff;color:var(--primary);border:2px solid var(--primary);font-size:0.95rem;padding:0.7rem;}
.btn-outline:hover:not(:disabled){background:var(--primary);color:#fff;}
.btn-outline:disabled{color:#bdc3c7;border-color:#bdc3c7;cursor:not-allowed;}

.attempts-info{font-size:0.85rem;color:var(--text-muted);text-align:center;margin-top:.5rem;}
.footer-link{text-align:center;font-size:0.95rem;padding:1.2rem;color:var(--text-muted);border-top:1px solid #f0f0f0;}
.footer-link a{color:var(--primary);font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="lock-icon"><i class="fas fa-shield-halved"></i></div>
    <h1>Verify Your Email</h1>
    <p>We've sent a 6-digit code to your email</p>
  </div>
  <div class="card-body">

    <!-- Email indicator -->
    <div class="email-badge">
        <i class="fas fa-envelope"></i>
        <?= htmlspecialchars($email) ?>
    </div>

    <?php if ($warn_email): ?>
    <div class="alert alert-warn">
        <i class="fas fa-triangle-exclamation"></i>
        <span>The verification email could not be sent. Please use the "Resend OTP" button below.</span>
    </div>
    <?php endif; ?>

    <?php if ($url_err): ?>
    <div class="alert alert-err"><i class="fas fa-circle-xmark"></i><span><?= $url_err ?></span></div>
    <?php endif; ?>
    <?php if ($otp_error): ?>
    <div class="alert alert-err"><i class="fas fa-circle-xmark"></i><span><?= htmlspecialchars($otp_error) ?></span></div>
    <?php endif; ?>

    <!-- Timer -->
    <div class="timer-row">
        <span class="timer-badge" id="timerBadge">
            <i class="fas fa-clock"></i> <span id="timerVal">10:00</span>
        </span>
    </div>

    <!-- OTP form -->
    <form method="POST" id="otpForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="otp-row" id="otpRow">
            <?php for ($i=0;$i<OTP_LENGTH;$i++): ?>
            <input type="text" class="otp-box" name="otp_digits[]"
                   id="otp<?= $i ?>" maxlength="1" inputmode="numeric"
                   pattern="[0-9]" autocomplete="off">
            <?php endfor; ?>
        </div>
        <button type="submit" class="btn btn-primary" id="verifyBtn" disabled>
            <i class="fas fa-check-circle"></i> Verify & Complete Registration
        </button>
        <p class="attempts-info">Maximum <?= OTP_MAX_ATTEMPTS ?> attempts allowed</p>
    </form>

    <!-- Resend -->
    <form method="POST" action="ajax/resend_otp.php" id="resendForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <button type="button" class="btn btn-outline" id="resendBtn" disabled>
            <i class="fas fa-paper-plane"></i> <span id="resendLabel">Resend OTP</span>
        </button>
    </form>
    <p class="attempts-info" id="resendInfo">Resend available in <span id="resendCountdown">10:00</span></p>

  </div>
  <div class="footer-link">
    Wrong email? <a href="register.php">Start over</a>
  </div>
</div>

<script>
// ── OTP boxes: auto-advance & backspace ───────────────────────
const boxes = document.querySelectorAll('.otp-box');
const verifyBtn = document.getElementById('verifyBtn');

boxes.forEach((box, idx) => {
    box.addEventListener('input', e => {
        box.value = box.value.replace(/\D/,'').slice(-1);
        box.classList.toggle('filled', box.value !== '');
        if (box.value && idx < boxes.length - 1) boxes[idx+1].focus();
        checkAllFilled();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
            boxes[idx-1].value=''; boxes[idx-1].classList.remove('filled');
            boxes[idx-1].focus();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
        [...text].forEach((ch,i) => {
            if (boxes[idx+i]) { boxes[idx+i].value=ch; boxes[idx+i].classList.add('filled'); }
        });
        const next = Math.min(idx + text.length, boxes.length - 1);
        boxes[next].focus();
        checkAllFilled();
    });
});

function checkAllFilled() {
    const all = [...boxes].every(b => b.value !== '');
    verifyBtn.disabled = !all;
}

// Focus first box on load
boxes[0] && boxes[0].focus();

// ── Countdown timer ───────────────────────────────────────────
let totalSecs = <?= $otp_expiry_secs ?>;
let timerSecs = totalSecs;
let resendSecs = totalSecs;
let resendCount = 0;

function fmtTime(s) {
    const m = Math.floor(s/60);
    return String(m).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
}

const timerVal = document.getElementById('timerVal');
const timerBadge = document.getElementById('timerBadge');
const resendBtn = document.getElementById('resendBtn');
const resendLabel = document.getElementById('resendLabel');
const resendInfo = document.getElementById('resendInfo');
const resendCountdown = document.getElementById('resendCountdown');

const countdown = setInterval(() => {
    timerSecs--;
    resendSecs--;
    timerVal.textContent = fmtTime(Math.max(0,timerSecs));
    resendCountdown.textContent = fmtTime(Math.max(0,resendSecs));

    if (timerSecs <= 0) {
        timerBadge.classList.add('expired');
        timerVal.textContent = 'Expired';
        verifyBtn.disabled = true;
        clearInterval(countdown);
    }
    if (resendSecs <= 0 && resendCount < <?= OTP_MAX_RESENDS ?>) {
        resendBtn.disabled = false;
        resendInfo.textContent = 'Resend available';
    }
}, 1000);

// ── Resend OTP ─────────────────────────────────────────────────
resendBtn.addEventListener('click', () => {
    if (resendCount >= <?= OTP_MAX_RESENDS ?>) return;
    resendBtn.disabled = true;
    resendLabel.textContent = 'Sending...';

    fetch('ajax/resend_otp.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent('<?= addslashes($csrf) ?>')
    })
    .then(r => r.json())
    .then(d => {
        resendCount++;
        if (d.ok) {
            resendLabel.textContent = 'Resend OTP';
            resendSecs = totalSecs;
            timerSecs  = totalSecs;
            timerBadge.classList.remove('expired');
            timerVal.textContent = fmtTime(timerSecs);
            const remaining = <?= OTP_MAX_RESENDS ?> - resendCount;
            resendInfo.textContent = `OTP resent! (${remaining} resend(s) left)`;
            if (resendCount >= <?= OTP_MAX_RESENDS ?>) {
                resendBtn.disabled = true;
                resendInfo.textContent = 'Maximum resends reached.';
            }
        } else {
            resendLabel.textContent = 'Resend failed';
            resendInfo.textContent  = d.msg || 'Could not resend OTP.';
        }
    })
    .catch(() => { resendLabel.textContent = 'Resend OTP'; });
});
</script>
</body>
</html>
