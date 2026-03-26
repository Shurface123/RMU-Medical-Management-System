<?php
/**
 * password_handler.php — Handles password reset submission (Phase 3)
 * Validates token, checks history, saves new bcrypt hash,
 * kills all sessions, invalidates remember_me tokens, sends notification.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/includes/reg_mailer.php';
require_once __DIR__ . '/classes/AuditLogger.php';

function bail(string $msg, string $page = 'index.php'): never {
    header("Location: $page?error=" . urlencode($msg)); exit;
}
function ok_redirect(string $msg): never {
    header("Location: index.php?success=" . urlencode($msg)); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$action = $_POST['action'] ?? '';

// ── RESET PASSWORD ─────────────────────────────────────────────────────────
if ($action === 'reset_password') {

    // 1. CSRF
    $posted_csrf = $_POST['_csrf'] ?? '';
    if (empty($_SESSION['_rp_csrf']) || !hash_equals($_SESSION['_rp_csrf'], $posted_csrf)) {
        bail('Invalid security token. Please try again.', 'forgot_password.php');
    }

    $plain_token = $_POST['_token'] ?? '';
    $token_hash  = hash('sha256', $plain_token);
    $new_pass    = $_POST['new_password'] ?? '';
    $conf_pass   = $_POST['confirm_password'] ?? '';
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 2. Validate token
    $st = mysqli_prepare($conn,
        "SELECT pr.id, pr.user_id, pr.expires_at, pr.is_used,
                u.email, u.name, u.user_name
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $token_hash);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

    if (!$row || $row['is_used'] || strtotime($row['expires_at']) < time()) {
        bail('Reset link is invalid or has expired. Please request a new one.', 'forgot_password.php');
    }

    $uid = (int)$row['user_id'];

    // 3. Server-side password policy
    if (strlen($new_pass) < 8)                       bail('Password must be at least 8 characters.', 'reset_password.php?token=' . urlencode($plain_token));
    if (!preg_match('/[A-Z]/', $new_pass))            bail('Password must contain at least one uppercase letter.', 'reset_password.php?token=' . urlencode($plain_token));
    if (!preg_match('/[a-z]/', $new_pass))            bail('Password must contain at least one lowercase letter.', 'reset_password.php?token=' . urlencode($plain_token));
    if (!preg_match('/[0-9]/', $new_pass))            bail('Password must contain at least one number.', 'reset_password.php?token=' . urlencode($plain_token));
    if (!preg_match('/[^A-Za-z0-9]/', $new_pass))    bail('Password must contain at least one special character.', 'reset_password.php?token=' . urlencode($plain_token));
    if ($new_pass !== $conf_pass)                     bail('Passwords do not match.', 'reset_password.php?token=' . urlencode($plain_token));

    // 4. Check password history (last 5)
    $hist = mysqli_prepare($conn,
        "SELECT password_hash FROM password_history WHERE user_id=? ORDER BY id DESC LIMIT 5");
    mysqli_stmt_bind_param($hist, 'i', $uid);
    mysqli_stmt_execute($hist);
    $hist_res = mysqli_stmt_get_result($hist);
    while ($hrow = mysqli_fetch_assoc($hist_res)) {
        if (password_verify($new_pass, $hrow['password_hash'])) {
            bail('You cannot reuse one of your last 5 passwords. Please choose a new password.', 'reset_password.php?token=' . urlencode($plain_token));
        }
    }

    // 5. Hash and save new password
    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);

    // Save old password to history first
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$uid LIMIT 1"));
    if ($cur) {
        $hi = mysqli_prepare($conn, "INSERT INTO password_history (user_id, password_hash) VALUES (?,?)");
        mysqli_stmt_bind_param($hi, 'is', $uid, $cur['password']);
        mysqli_stmt_execute($hi);
        // Keep only last 5
        mysqli_query($conn, "DELETE FROM password_history WHERE user_id=$uid AND id NOT IN
            (SELECT id FROM (SELECT id FROM password_history WHERE user_id=$uid ORDER BY id DESC LIMIT 5) AS t)");
    }

    // Update password
    $upd = mysqli_prepare($conn,
        "UPDATE users SET password=?, force_password_change=0, locked_until=NULL WHERE id=?");
    mysqli_stmt_bind_param($upd, 'si', $new_hash, $uid);
    mysqli_stmt_execute($upd);

    // 6. Mark token as used
    mysqli_query($conn, "UPDATE password_resets SET is_used=1 WHERE id={$row['id']}");

    // Log action
    $audit = new AuditLogger($conn);
    $audit->logPasswordChange($uid);

    // 7. Kill all active sessions for this user
    mysqli_query($conn, "DELETE FROM active_sessions WHERE user_id=$uid");

    // 8. Invalidate all remember_me tokens
    $del = mysqli_prepare($conn, "DELETE FROM remember_me_tokens WHERE user_id=?");
    mysqli_stmt_bind_param($del, 'i', $uid);
    mysqli_stmt_execute($del);

    // 9. Clear current session
    unset($_SESSION['_reset_uid'], $_SESSION['_reset_token'], $_SESSION['_rp_csrf']);

    // 10. Send "password changed" notification email
    $changed_at = date('D, M j, Y g:i A');
    if (function_exists('reg_send_password_changed_email')) {
        @reg_send_password_changed_email($conn, $row['email'], $row['name'], $changed_at, $ip);
    }

    ok_redirect('Your password has been reset successfully. Please log in with your new password.');
}

// ── CHANGE PASSWORD (forced at login) ───────────────────────────────────────
if ($action === 'force_change_password') {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php'); exit;
    }
    $uid      = (int)$_SESSION['user_id'];
    $new_pass = $_POST['new_password'] ?? '';
    $conf     = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 8 || !preg_match('/[A-Z]/',$new_pass)
        || !preg_match('/[a-z]/',$new_pass) || !preg_match('/[0-9]/',$new_pass)
        || !preg_match('/[^A-Za-z0-9]/',$new_pass)) {
        bail('Password does not meet the policy requirements.', 'change_password.php?forced=1');
    }
    if ($new_pass !== $conf) bail('Passwords do not match.', 'change_password.php?forced=1');

    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
    $upd = mysqli_prepare($conn, "UPDATE users SET password=?, force_password_change=0 WHERE id=?");
    mysqli_stmt_bind_param($upd, 'si', $new_hash, $uid);
    mysqli_stmt_execute($upd);

    $audit = new AuditLogger($conn);
    $audit->logPasswordChange($uid);

    require_once __DIR__ . '/login_router.php';
    login_route($_SESSION['role']);
}

bail('Unknown action.', 'index.php');
