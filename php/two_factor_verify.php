<?php
/**
 * two_factor_verify.php — Two-Factor Authentication Screen (Phase 3)
 * Preserves exact login page color scheme.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/includes/reg_config.php';
require_once __DIR__ . '/login_router.php';
require_once __DIR__ . '/classes/AuditLogger.php';

// Guard: must arrive via login.php 2FA branch
if (empty($_SESSION['2fa_pending_uid'])) {
    header('Location: index.php'); exit;
}

$uid        = (int)$_SESSION['2fa_pending_uid'];
$role       = $_SESSION['2fa_pending_role'];
$otp_error  = '';
$ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua         = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

// Regenerate CSRF for this page
if (empty($_SESSION['_2fa_csrf'])) {
    $_SESSION['_2fa_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['_2fa_csrf'];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_digits'])) {
    $posted_csrf = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_2fa_csrf'], $posted_csrf)) {
        $otp_error = 'Invalid security token.';
    } else {
        $otp_plain = implode('', array_map('intval', (array)$_POST['otp_digits']));
        $otp_plain = preg_replace('/\D/', '', substr($otp_plain, 0, 6));

        // Fetch latest valid OTP
        $st = mysqli_prepare($conn,
            "SELECT id, otp_hash, expires_at, attempts_made
             FROM two_factor_attempts
             WHERE user_id=? AND is_used=0
             ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $uid);
        mysqli_stmt_execute($st);
        $tf = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

        if (!$tf) {
            $otp_error = 'No valid OTP found. Please request a new one.';
        } elseif (strtotime($tf['expires_at']) < time()) {
            $otp_error = 'OTP has expired. Please request a new one.';
        } elseif ((int)$tf['attempts_made'] >= 3) {
            // Kill session — too many bad attempts
            session_destroy();
            header('Location: index.php?error=' . urlencode('Too many incorrect OTP attempts. Please login again.'));
            exit;
        } else {
            // Increment attempt
            mysqli_query($conn,
                "UPDATE two_factor_attempts SET attempts_made=attempts_made+1 WHERE id={$tf['id']}");

            if (!password_verify($otp_plain, $tf['otp_hash'])) {
                $rem = 3 - ((int)$tf['attempts_made'] + 1);
                $otp_error = "Incorrect OTP. $rem attempt(s) remaining.";
            } else {
                // ✅ 2FA Success — restore full session
                mysqli_query($conn,
                    "UPDATE two_factor_attempts SET is_used=1 WHERE id={$tf['id']}");

                $u = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT user_name, name, email, profile_image, force_password_change
                     FROM users WHERE id=$uid LIMIT 1"));

                session_regenerate_id(true);
                $_SESSION['user_id']       = $uid;
                $_SESSION['user_name']     = $u['user_name'];
                $_SESSION['name']          = $u['name'];
                $_SESSION['role']          = $role;
                $_SESSION['user_role']     = $role;
                $_SESSION['email']         = $u['email'];
                $_SESSION['profile_image'] = $u['profile_image'] ?? 'default-avatar.png';
                $_SESSION['login_ip']      = $ip;
                unset($_SESSION['2fa_pending_uid'], $_SESSION['2fa_pending_role'],
                      $_SESSION['_2fa_csrf']);

                // Log active session
                $sid = session_id();
                $as = mysqli_prepare($conn,
                    "INSERT INTO active_sessions (session_id,user_id,user_role,ip_address,user_agent,logged_in_at)
                     VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE last_active=NOW()");
                mysqli_stmt_bind_param($as, 'sisss', $sid, $uid, $role, $ip, $ua);
                @mysqli_stmt_execute($as);

                // Log success
                $audit = new AuditLogger($conn);
                $audit->logLogin($uid, true);

                if (!empty($u['force_password_change'])) {
                    header('Location: change_password.php?forced=1'); exit;
                }
                login_route($role);
            }
        }
    }
}

// ── Resend OTP via AJAX ───────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Two-Factor Verification — RMU Medical Sickbay</title>
<link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#2F80ED;--secondary:#56CCF2;--success:#27ae60;--danger:#e74c3c;--text-dark:#2c3e50;--text-muted:#7f8c8d;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2F80ED 0%,#56CCF2 50%,#2F80ED 100%);padding:2rem 1rem;overflow-x:hidden;}
body::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.1) 1px,transparent 1px);background-size:50px 50px;animation:moveBackground 20s linear infinite;pointer-events:none;}
@keyframes moveBackground{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.card{position:relative;z-index:10;background:#fff;border-radius:24px;box-shadow:0 15px 40px rgba(47,128,237,.15);width:90%;max-width:440px;overflow:hidden;animation:slideIn .4s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.card-header{background:linear-gradient(135deg,#2F80ED,#56CCF2);padding:2.5rem 2.5rem 2rem;text-align:center;}
.shield-icon{width:72px;height:72px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.8rem;color:#fff;margin:0 auto 1rem;border:2px solid rgba(255,255,255,.3);}
.card-header h1{font-size:1.7rem;font-weight:700;color:#fff;margin-bottom:.4rem;}
.card-header p{font-size:1rem;color:rgba(255,255,255,.85);}
.card-body{padding:2.5rem;}
.alert{padding:.8rem 1rem;border-radius:8px;font-size:.9rem;margin-bottom:1.2rem;display:flex;align-items:flex-start;gap:.6rem;border-left:4px solid;}
.alert-err{background:#FDEDEC;color:#c0392b;border-color:#e74c3c;}
.otp-row{display:flex;gap:.6rem;justify-content:center;margin:1.6rem 0;}
.otp-box{width:45px;height:52px;border:2px solid #e0e0e0;border-radius:8px;font-size:1.6rem;font-weight:700;text-align:center;color:var(--text-dark);transition:all .2s;font-family:'Poppins',sans-serif;background:#fff;}
.otp-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.12);}
.otp-box.filled{border-color:var(--primary);background:#EBF3FF;}
.timer-row{text-align:center;margin-bottom:1.4rem;}
.timer-badge{display:inline-flex;align-items:center;gap:.4rem;background:#FEF9E7;color:#E67E22;border-radius:50px;padding:.4rem 1rem;font-size:.9rem;font-weight:600;}
.timer-badge.expired{background:#FDEDEC;color:#c0392b;}
.btn{width:100%;padding:1rem;font-size:1rem;font-weight:600;border:none;border-radius:12px;cursor:pointer;transition:all .2s;font-family:'Poppins',sans-serif;margin-bottom:.8rem;display:flex;align-items:center;justify-content:center;gap:.6rem;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;box-shadow:0 6px 20px rgba(47,128,237,.3);letter-spacing:.5px;text-transform:uppercase;}
.btn-primary:hover:not(:disabled){transform:translateY(-1px);}
.btn-primary:disabled{background:#bdc3c7;cursor:not-allowed;transform:none;}
.btn-outline{background:#fff;color:var(--primary);border:2px solid var(--primary);font-size:.95rem;padding:.8rem;}
.btn-outline:hover:not(:disabled){background:var(--primary);color:#fff;}
.btn-outline:disabled{color:#bdc3c7;border-color:#bdc3c7;cursor:not-allowed;}
.attempts-info{font-size:.85rem;color:var(--text-muted);text-align:center;margin-top:.5rem;}
.footer-link{text-align:center;font-size:.95rem;padding:1rem;color:var(--text-muted);border-top:1px solid #f0f0f0;}
.footer-link a{color:var(--primary);font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="shield-icon"><i class="fas fa-shield-halved"></i></div>
        <h1>Two-Factor Verification</h1>
        <p>Enter the 6-digit code sent to your email</p>
    </div>
    <div class="card-body">
        <?php if ($otp_error): ?>
        <div class="alert alert-err"><i class="fas fa-circle-xmark"></i><span><?= htmlspecialchars($otp_error) ?></span></div>
        <?php endif; ?>

        <div class="timer-row">
            <span class="timer-badge" id="timerBadge">
                <i class="fas fa-clock"></i> <span id="timerVal">05:00</span>
            </span>
        </div>

        <form method="POST" id="otpForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="otp-row" id="otpRow">
                <?php for ($i=0; $i<6; $i++): ?>
                <input type="text" class="otp-box" name="otp_digits[]"
                       id="otp<?= $i ?>" maxlength="1" inputmode="numeric"
                       pattern="[0-9]" autocomplete="off">
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn btn-primary" id="verifyBtn" disabled>
                <i class="fas fa-check-circle"></i> Verify Code
            </button>
            <p class="attempts-info">Maximum 3 attempts allowed</p>
        </form>

        <form method="POST" action="ajax/resend_2fa_otp.php" id="resendForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button type="button" class="btn btn-outline" id="resendBtn" disabled>
                <i class="fas fa-paper-plane"></i> <span id="resendLabel">Resend Code</span>
            </button>
        </form>
    </div>
    <div class="footer-link">Wrong account? <a href="index.php">Back to Login</a></div>
</div>
<script>
const OTP_SECONDS = <?= (int)(get_setting('otp_expiry_minutes', 5)) * 60 ?>;
let remaining = OTP_SECONDS;

const boxes      = document.querySelectorAll('.otp-box');
const verifyBtn  = document.getElementById('verifyBtn');
const resendBtn  = document.getElementById('resendBtn');
const timerBadge = document.getElementById('timerBadge');
const timerVal   = document.getElementById('timerVal');

function checkAllFilled() {
    const all = [...boxes].every(b => b.value !== '');
    verifyBtn.disabled = !all;
}

boxes.forEach((box, idx) => {
    box.addEventListener('input', e => {
        box.value = box.value.replace(/\D/, '').slice(-1);
        box.classList.toggle('filled', box.value !== '');
        if (box.value && idx < boxes.length - 1) boxes[idx+1].focus();
        checkAllFilled();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
            boxes[idx-1].value = ''; boxes[idx-1].classList.remove('filled');
            boxes[idx-1].focus();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
        [...text.slice(0,6)].forEach((ch, i) => {
            if (boxes[i]) { boxes[i].value = ch; boxes[i].classList.add('filled'); }
        });
        boxes[Math.min(text.length, 5)].focus();
        checkAllFilled();
    });
});

boxes[0].focus();

const timer = setInterval(() => {
    remaining--;
    const m = String(Math.floor(remaining/60)).padStart(2,'0');
    const s = String(remaining % 60).padStart(2,'0');
    timerVal.textContent = `${m}:${s}`;
    if (remaining <= 0) {
        clearInterval(timer);
        timerBadge.classList.add('expired');
        timerVal.textContent = 'Expired';
        resendBtn.disabled = false;
    }
}, 1000);

resendBtn.addEventListener('click', () => {
    resendBtn.disabled = true;
    resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    fetch('ajax/resend_2fa_otp.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf=<?= htmlspecialchars($csrf) ?>'
    }).then(r => r.json()).then(d => {
        resendBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
    }).catch(() => {
        resendBtn.disabled = false;
        resendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Resend Code';
    });
});
</script>
</body>
</html>
