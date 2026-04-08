<?php
/**
 * forgot_password.php — Secure Password Reset Request Page (Phase 3)
 * Anti-enumeration: always shows same neutral message.
 * Visual identity matches existing login page exactly.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/includes/reg_mailer.php';

if (empty($_SESSION['_fp_csrf'])) {
    $_SESSION['_fp_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['_fp_csrf'];

$sent    = false;
$fp_err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_csrf = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_fp_csrf'], $posted_csrf)) {
        $fp_err = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        // Always show same message (anti-enumeration)
        $sent = true;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $st = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email=? AND account_status != 'rejected' LIMIT 1");
            mysqli_stmt_bind_param($st, 's', $email);
            mysqli_stmt_execute($st);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

            if ($user) {
                $uid = (int)$user['id'];

                // Invalidate old tokens
                $del = mysqli_prepare($conn, "UPDATE password_resets SET is_used=1 WHERE user_id=? AND is_used=0");
                mysqli_stmt_bind_param($del, 'i', $uid);
                mysqli_stmt_execute($del);

                // Generate secure token
                $plainToken = bin2hex(random_bytes(32));
                $tokenHash  = hash('sha256', $plainToken);
                $expires    = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

                $ins = mysqli_prepare($conn,
                    "INSERT INTO password_resets (user_id,token_hash,expires_at,ip_address) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($ins, 'isss', $uid, $tokenHash, $expires, $ip);
                mysqli_stmt_execute($ins);

                // Build reset URL
                $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $domain    = $_SERVER['HTTP_HOST'];
                $resetLink = "$protocol://$domain/RMU-Medical-Management-System/php/reset_password.php?token=$plainToken";

                // Send email — capture result for logging
                $mail_sent = false;
                if (function_exists('reg_send_password_reset_email')) {
                    $reset_result = reg_send_password_reset_email($conn, $email, $user['name'], $resetLink);
                    $mail_sent = $reset_result['success'] ?? false;
                    if (!$mail_sent) {
                        error_log('[RMU-Sickbay] Password reset email failed for user ' . $uid
                            . ': ' . ($reset_result['error'] ?? 'unknown error'));
                    }
                }
            }
        }
        // Rotate CSRF
        $_SESSION['_fp_csrf'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['_fp_csrf'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Forgot Password — RMU Medical Sickbay</title>
<link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#2F80ED;--primary-dark:#2366CC;--success:#27ae60;--danger:#e74c3c;--text-dark:#2c3e50;--text-muted:#7f8c8d;--white:#fff;--border:#e0e0e0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2F80ED 0%,#56CCF2 50%,#2F80ED 100%);padding:2rem 1rem;overflow-x:hidden;position:relative;}
body::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.1) 1px,transparent 1px);background-size:50px 50px;animation:bgMove 20s linear infinite;pointer-events:none;}
@keyframes bgMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.login-container{position:relative;z-index:10;background:var(--white);padding:2.5rem 2rem;border-radius:20px;box-shadow:0 15px 40px rgba(47,128,237,.15);width:90%;max-width:400px;animation:slideIn .4s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.login-header{text-align:center;margin-bottom:2rem;}
.logo-icon{width:64px;height:64px;background:linear-gradient(135deg,#2F80ED,#56CCF2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;box-shadow:0 8px 24px rgba(47,128,237,.25);}
.logo-icon i{font-size:2.4rem;color:#fff;}
.login-header h1{font-size:1.6rem;font-weight:700;color:var(--text-dark);margin-bottom:.3rem;}
.login-header p{font-size:.95rem;color:var(--text-muted);}
.msg-box{border-radius:8px;padding:.8rem 1rem;margin-bottom:1.2rem;font-size:.9rem;display:flex;align-items:flex-start;gap:.6rem;border-left:4px solid;}
.msg-box.err{background:#FDEDEC;color:#c0392b;border-color:var(--danger);}
.msg-box.ok {background:#EAFAF1;color:#1e8449;border-color:var(--success);}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;font-size:.95rem;font-weight:600;color:var(--text-dark);margin-bottom:.5rem;}
.input-wrapper{position:relative;}
.input-wrapper .fi{position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);font-size:1.1rem;color:var(--text-muted);pointer-events:none;}
.form-control{width:100%;padding:.8rem 1rem .8rem 3rem;font-size:.95rem;border:2px solid var(--border);border-radius:12px;font-family:'Poppins',sans-serif;transition:all .2s;}
.form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.1);}
.btn-login{width:100%;padding:1rem;font-size:1rem;font-weight:600;background:linear-gradient(135deg,#2F80ED,#56CCF2);color:#fff;border:none;border-radius:12px;cursor:pointer;transition:all .2s;text-transform:uppercase;letter-spacing:1px;box-shadow:0 8px 20px rgba(47,128,237,.25);display:flex;align-items:center;justify-content:center;gap:.6rem;}
.btn-login:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 10px 24px rgba(47,128,237,.3);}
.btn-login:disabled{opacity:.7;cursor:not-allowed;transform:none;}
.login-footer{text-align:center;margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--border);}
.login-footer a{color:var(--primary);font-weight:600;font-size:.95rem;text-decoration:none;}
.login-footer a:hover{text-decoration:underline;}
.back-to-login{display:flex;align-items:center;justify-content:center;gap:.5rem;margin-bottom:.4rem;font-size:.95rem;color:var(--text-muted);}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="logo-icon"><i class="fas fa-key"></i></div>
        <h1>Forgot Password?</h1>
        <p>Enter your email to receive a reset link</p>
    </div>

    <?php if ($fp_err): ?>
    <div class="msg-box err"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($fp_err) ?></span></div>
    <?php endif; ?>

    <?php if ($sent): ?>
    <div class="msg-box ok">
        <i class="fas fa-check-circle"></i>
        <span>If an account with that email exists, a password reset link has been sent. Please check your inbox (and spam folder).</span>
    </div>
    <div class="login-footer"><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    <?php else: ?>
    <form method="POST" id="fpForm" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="form-group">
            <label for="email">Registered Email Address</label>
            <div class="input-wrapper">
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="Enter your email address" required autocomplete="email">
                <i class="fas fa-envelope fi"></i>
            </div>
        </div>
        <button type="submit" class="btn-login" id="fpBtn">
            <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
    </form>
    <div class="login-footer">
        <p class="back-to-login"><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
    </div>
    <?php endif; ?>
</div>
<script>
document.getElementById('fpForm')?.addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    if (!email) { e.preventDefault(); return; }
    const btn = document.getElementById('fpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
});
</script>
</body>
</html>
