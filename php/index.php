<?php
/**
 * index.php — Advanced Login Page (Phase 3)
 * Preserves existing color scheme exactly. Removes role dropdown.
 * Adds: Show/Hide PW, Remember Me, Forgot Password link, CSRF, URL messages.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Redirect already-logged-in users
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    require_once __DIR__ . '/login_router.php';
    login_route($_SESSION['role']);
    exit;
}

// CSRF token
if (empty($_SESSION['_login_csrf'])) {
    $_SESSION['_login_csrf'] = bin2hex(random_bytes(32));
}

// Remember Me auto-login
if (empty($_SESSION['user_id']) && isset($_COOKIE['rmumss_remember'])) {
    require_once __DIR__ . '/db_conn.php';
    $plainToken = $_COOKIE['rmumss_remember'];
    $tokenHash  = hash('sha256', $plainToken);
    $stmt = mysqli_prepare($conn,
        "SELECT rt.user_id, u.user_role, u.is_active, u.account_status
         FROM remember_me_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.token_hash = ? AND rt.expires_at > NOW()
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $tokenHash);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        if ($row['is_active'] && $row['account_status'] === 'active') {
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['role']      = $row['user_role'];
            $_SESSION['user_role'] = $row['user_role'];
            // Refresh DB for name/username
            $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_name, name, profile_image FROM users WHERE id={$row['user_id']}"));
            $_SESSION['user_name']     = $u['user_name'] ?? '';
            $_SESSION['name']          = $u['name'] ?? '';
            $_SESSION['profile_image'] = $u['profile_image'] ?? 'default-avatar.png';
            require_once __DIR__ . '/login_router.php';
            login_route($row['user_role']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMU Medical Sickbay - Login</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="shortcut icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">

    <style>
        :root {
            --primary-color: #2F80ED;
            --primary-dark:  #2366CC;
            --accent-color:  #e74c3c;
            --success-color: #27ae60;
            --warning-color: #E67E22;
            --text-dark:     #2c3e50;
            --text-muted:    #7f8c8d;
            --white:         #ffffff;
            --border:        #e0e0e0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2F80ED 0%, #56CCF2 50%, #2F80ED 100%);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        @keyframes moveBackground {
            0%   { transform: translate(0,0); }
            100% { transform: translate(50px,50px); }
        }

        /* Floating shapes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            animation: floatShape 8s ease-in-out infinite;
        }
        .bg-shape:nth-child(1) { width:320px; height:320px; top:-80px; right:-80px; animation-delay:0s; }
        .bg-shape:nth-child(2) { width:200px; height:200px; bottom:-40px; left:-40px; animation-delay:3s; }
        .bg-shape:nth-child(3) { width:150px; height:150px; top:40%; right:5%; animation-delay:6s; }
        @keyframes floatShape {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-20px) rotate(5deg); }
        }

        /* Container */
        .login-container {
            position: relative; z-index: 10;
            background: var(--white);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 15px 40px rgba(47,128,237,.15);
            width: 90%; max-width: 440px;
            animation: slideIn .4s ease-out;
        }
        @keyframes slideIn {
            from { opacity:0; transform: translateY(-20px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* Header */
        .login-header { text-align: center; margin-bottom: 2rem; }
        .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg,#2F80ED,#56CCF2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 8px 24px rgba(47,128,237,.25);
        }
        .logo-icon i { font-size: 2.8rem; color: var(--white); }
        .login-header h1 { font-size: 2.8rem; color: var(--text-dark); font-weight: 700; margin-bottom: .3rem; }
        .login-header p  { font-size: 1.6rem; color: var(--text-muted); }
        /* Alert banners */
        .msg-box {
            border-radius: 8px; padding: 0.8rem 1rem;
            margin-bottom: 1.2rem; font-size: 0.9rem;
            display: none; align-items: flex-start; gap: .6rem;
            border-left: 4px solid;
        }
        .msg-box.show { display: flex; animation: shake .4s; }
        .msg-box.err  { background:#FDEDEC; color:#c0392b; border-color:#e74c3c; }
        .msg-box.ok   { background:#EAFAF1; color:#1e8449; border-color:#27ae60; }
        .msg-box.info { background:#FEF9E7; color:#935116; border-color:#E67E22; }
        .msg-box.warn { background:#fff8e1; color:#935116; border-color:#F39C12; }
        @keyframes shake {
            0%,100% { transform:translateX(0); }
            25%      { transform:translateX(-6px); }
            75%      { transform:translateX(6px); }
        }

        /* Form */
        .form-group { margin-bottom: 1.6rem; position: relative; }
        .form-group label {
            display: block; font-size: 1.6rem; font-weight: 600;
            color: var(--text-dark); margin-bottom: .6rem;
        }
        .input-wrapper { position: relative; }
        .input-wrapper .fi {
            position: absolute; left: 1.25rem; top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem; color: var(--text-muted);
            transition: color .3s; pointer-events: none;
        }
        .form-control {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.8rem;
            font-size: 1.6rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            transition: all .2s;
            font-family: 'Poppins', sans-serif;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(47,128,237,.1);
        }
        .form-control:focus ~ .fi { color: var(--primary-color); }
        .form-control.is-valid   { border-color: var(--success-color); }
        .form-control.is-invalid { border-color: var(--accent-color); }

        /* Show/hide PW toggle */
        .pw-toggle {
            position: absolute; right: 1.2rem; top: 50%;
            transform: translateY(-50%);
            cursor: pointer; color: var(--text-muted);
            font-size: 1.6rem; transition: color .2s;
            background: none; border: none; padding: 0;
        }
        .pw-toggle:hover { color: var(--primary-color); }

        /* Field inline message */
        .field-msg { font-size: 0.85rem; margin-top: .3rem; min-height: 1.2em; }
        .field-msg.ok  { color: var(--success-color); }
        .field-msg.err { color: var(--accent-color); }

        /* Remember Me row */
        .remember-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.4rem; font-size: 1.4rem; color: var(--text-muted);
        }
        .remember-row label {
            display: flex; align-items: center; gap: .4rem; cursor: pointer;
        }
        .remember-row input[type="checkbox"] {
            width: 17px; height: 17px; accent-color: var(--primary-color); cursor: pointer;
        }
        .remember-row a {
            color: var(--primary-color); text-decoration: none; font-weight: 500;
        }
        .remember-row a:hover { text-decoration: underline; }

        /* Submit button */
        .btn-login {
            width: 100%; padding: 1.2rem;
            font-size: 1.6rem; font-weight: 600;
            background: linear-gradient(135deg,#2F80ED,#56CCF2);
            color: var(--white);
            border: none; border-radius: 12px;
            cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-transform: uppercase; letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(47,128,237,.25);
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            position: relative;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(47,128,237,.3);
        }
        .btn-login:disabled { opacity: .7; cursor: not-allowed; transform: none; }
        .btn-login .spinner { display: none; align-items: center; justify-content: center; gap: .6rem; }
        .btn-login.loading {
            animation: advancedRotate 0.8s ease-in-out forwards;
        }
        @keyframes advancedRotate {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(0.9) rotate(45deg); }
            100% { transform: scale(1) rotate(90deg); opacity: 0.8; }
        }
        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .spinner { display: flex; }

        /* Premium CSS Spinner */
        .custom-loader {
            width: 20px; height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Lockout countdown */
        .lockout-box {
            background: #FDEDEC; border-left: 4px solid #e74c3c;
            border-radius: 12px; padding: 1rem 1.3rem;
            margin-bottom: 1.5rem; font-size: 1.3rem;
            color: #c0392b; display: none;
        }
        .lockout-box.show { display: block; }
        #lockCountdown { font-weight: 700; }

        /* Footer */
        .login-footer {
            text-align: center; margin-top: 2rem;
            padding-top: 1.8rem; border-top: 1px solid var(--border);
        }
        .login-footer p { font-size: 1.5rem; color: var(--text-muted); margin-bottom: .8rem; }
        .login-footer a { color: var(--primary-color); font-weight: 600; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }

        /* Back-to-home */
        .back-home { position: absolute; top: 2rem; left: 2rem; z-index: 100; }
        .back-home a {
            display: flex; align-items: center; gap: .8rem;
            padding: 1rem 2rem;
            background: rgba(255,255,255,.95);
            color: var(--primary-color); text-decoration: none;
            border-radius: 50px; font-size: 1.6rem; font-weight: 600;
            box-shadow: 0 10px 30px rgba(47,128,237,.2); transition: all .3s;
        }
        .back-home a:hover { background: var(--white); transform: translateY(-2px); }

        @media(max-width:600px) {
            .login-container { padding: 2rem 1.5rem; }
            .login-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

    <div class="back-home">
        <a href="/RMU-Medical-Management-System/html/index.html">
            <i class="fas fa-arrow-left"></i><span>Back to Home</span>
        </a>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
            <h1>Welcome Back</h1>
            <p>Login to RMU Medical Sickbay</p>
        </div>

        <!-- Error message -->
        <div class="msg-box err" id="errBox"><i class="fas fa-exclamation-circle"></i><span id="errText"></span></div>
        <!-- Success message -->
        <div class="msg-box ok"  id="okBox"><i class="fas fa-check-circle"></i><span id="okText"></span></div>
        <!-- Info (pending approval) -->
        <div class="msg-box info" id="infoBox"><i class="fas fa-clock"></i><span id="infoText"></span></div>

        <!-- Lockout countdown -->
        <div class="lockout-box" id="lockoutBox">
            <i class="fas fa-lock"></i>
            Account temporarily locked. Try again in <span id="lockCountdown">--:--</span>
        </div>

        <!-- Login Form -->
        <form id="loginForm" action="login.php" method="POST" autocomplete="off" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_login_csrf']) ?>">
            <input type="hidden" name="_lockout_until" id="lockoutUntilField" value="">

            <div class="form-group">
                <label for="uname">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="uname" name="uname" class="form-control"
                           placeholder="Enter your username" required autocomplete="username">
                    <i class="fas fa-user fi"></i>
                </div>
                <div class="field-msg" id="unameMsg"></div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password" required autocomplete="current-password">
                    <i class="fas fa-lock fi"></i>
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="pwEyeIcon"></i>
                    </button>
                </div>
                <div class="field-msg" id="passMsg"></div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; font-size:.9rem; color:var(--text-muted);">
                    <input type="checkbox" name="remember_me" id="rememberMe" value="1" style="width:16px; height:16px; accent-color:var(--primary);">
                    Remember me
                </label>
                <a href="forgot_password.php" style="font-size:.9rem; color:var(--primary); text-decoration:none; font-weight:600;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Login</span>
                <span class="spinner"><div class="custom-loader"></div> Verifying...</span>
            </button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="/RMU-Medical-Management-System/html/index.html">Return to Homepage</a></p>
        </div>
    </div>

<script>
// ── Read URL params ─────────────────────────────────────────────────────────
const params = new URLSearchParams(window.location.search);
const qs_error   = params.get('error');
const qs_success = params.get('success');
const qs_info    = params.get('info');
const qs_lock    = params.get('locked_until'); // epoch seconds from server

function showBox(id, text) {
    const el = document.getElementById(id);
    document.getElementById(id.replace('Box','Text')).textContent = text;
    el.classList.add('show');
}

if (qs_error)   showBox('errBox',  qs_error);
if (qs_success) showBox('okBox',   qs_success);
if (qs_info)    showBox('infoBox', qs_info);

// ── Lockout countdown ───────────────────────────────────────────────────────
if (qs_lock) {
    const lockoutBox = document.getElementById('lockoutBox');
    const lockCountdown = document.getElementById('lockCountdown');
    const loginBtn = document.getElementById('loginBtn');
    lockoutBox.classList.add('show');
    loginBtn.disabled = true;

    function tick() {
        const rem = parseInt(qs_lock) - Math.floor(Date.now()/1000);
        if (rem <= 0) {
            lockoutBox.classList.remove('show');
            loginBtn.disabled = false;
            clearInterval(timer);
        } else {
            const m = String(Math.floor(rem/60)).padStart(2,'0');
            const s = String(rem % 60).padStart(2,'0');
            lockCountdown.textContent = `${m}:${s}`;
        }
    }
    tick();
    const timer = setInterval(tick, 1000);
}

// ── Show/Hide password ──────────────────────────────────────────────────────
document.getElementById('pwToggle').addEventListener('click', function() {
    const pw  = document.getElementById('password');
    const ico = document.getElementById('pwEyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        ico.className = 'fas fa-eye-slash';
    } else {
        pw.type = 'password';
        ico.className = 'fas fa-eye';
    }
});

// ── Inline validation ───────────────────────────────────────────────────────
const uname = document.getElementById('uname');
const pass  = document.getElementById('password');

uname.addEventListener('blur', () => {
    const msg = document.getElementById('unameMsg');
    if (!uname.value.trim()) {
        uname.classList.replace('is-valid','is-invalid');
        msg.textContent = 'Username is required.'; msg.className='field-msg err';
    } else {
        uname.classList.replace('is-invalid','is-valid');
        msg.textContent = ''; msg.className='field-msg';
    }
});

pass.addEventListener('blur', () => {
    const msg = document.getElementById('passMsg');
    if (!pass.value) {
        pass.classList.replace('is-valid','is-invalid');
        msg.textContent = 'Password is required.'; msg.className='field-msg err';
    } else {
        pass.classList.replace('is-invalid','is-valid');
        msg.textContent = ''; msg.className='field-msg';
    }
});

// Clear error on any input
document.querySelectorAll('.form-control').forEach(el => {
    el.addEventListener('input', () => {
        document.getElementById('errBox').classList.remove('show');
    });
});

// ── Form submit with loading state ──────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const u = uname.value.trim();
    const p = pass.value;
    if (!u || !p) {
        e.preventDefault();
        showBox('errBox','Please fill in all fields.');
        return;
    }
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.classList.add('loading');
});
</script>
</body>
</html>
