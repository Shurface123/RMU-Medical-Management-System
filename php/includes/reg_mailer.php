<?php
// ============================================================
// REGISTRATION MAILER — PHPMailer wrapper
// Sends OTP and notification emails using SMTP config from DB.
// ============================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Fetch live SMTP config from system_email_config table.
 * Falls back to db_conn $emailConfig array if table is empty.
 */
function reg_get_smtp_config($conn) {
    $r = mysqli_query($conn,
        "SELECT * FROM system_email_config WHERE is_active=1 ORDER BY id LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        // Decrypt password (AES_DECRYPT with same key used in migration)
        $pwResult = mysqli_query($conn,
            "SELECT CAST(AES_DECRYPT(smtp_password,
                SHA2('RMU_SICKBAY_2025_SECRET',256)) AS CHAR) AS pw
             FROM system_email_config WHERE id={$row['id']}");
        $pwRow = $pwResult ? mysqli_fetch_assoc($pwResult) : null;
        $row['smtp_password_plain'] = $pwRow['pw'] ?? '';
        return $row;
    }
    // Fallback to hardcoded default (from db_conn.php $emailConfig)
    return [
        'smtp_host'          => 'smtp.gmail.com',
        'smtp_port'          => 587,
        'smtp_username'      => 'sickbay.text@st.rmu.edu.gh',
        'smtp_password_plain'=> 'hqrr kkat ruqg nutf',
        'encryption'         => 'tls',
        'from_email'         => 'sickbay.text@st.rmu.edu.gh',
        'from_name'          => 'RMU Medical Sickbay',
    ];
}

/**
 * Send the OTP verification email.
 */
function reg_send_otp_email($conn, $to_email, $to_name, $otp_code) {
    $cfg = reg_get_smtp_config($conn);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_username'];
        $mail->Password   = $cfg['smtp_password_plain'];
        $mail->SMTPSecure = ($cfg['encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['smtp_port'];

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);

        $expiry_mins = defined('OTP_EXPIRY_MINUTES') ? OTP_EXPIRY_MINUTES : 10;
        $mail->Subject = 'Your RMU Medical Sickbay Registration OTP';
        $mail->Body    = reg_otp_email_html($to_name, $otp_code, $expiry_mins);
        $mail->AltBody = "Hello $to_name,\n\nYour registration OTP is: $otp_code\n\nThis code expires in $expiry_mins minutes. Do not share it with anyone.";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('OTP Email Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Send welcome email after successful registration.
 */
function reg_send_welcome_email($conn, $to_email, $to_name, $role, $needs_approval) {
    $cfg  = reg_get_smtp_config($conn);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_username'];
        $mail->Password   = $cfg['smtp_password_plain'];
        $mail->SMTPSecure = ($cfg['encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['smtp_port'];

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);

        $mail->Subject = 'Welcome to RMU Medical Sickbay!';
        $mail->Body    = reg_welcome_email_html($to_name, $role, $needs_approval);
        $mail->AltBody = "Welcome $to_name! " . ($needs_approval
            ? 'Your account is pending admin approval. You will be notified once approved.'
            : 'Your account is ready. You may now log in at the RMU Medical portal.');

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('Welcome Email Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Send rejection email after admin review.
 */
function reg_send_rejection_email($conn, $to_email, $to_name, $role, $reason) {
    $cfg  = reg_get_smtp_config($conn);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_username'];
        $mail->Password   = $cfg['smtp_password_plain'];
        $mail->SMTPSecure = ($cfg['encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['smtp_port'];

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);

        $mail->Subject = 'Update regarding your RMU Medical Sickbay Registration';
        $mail->Body    = reg_rejection_email_html($to_name, $role, $reason);
        $mail->AltBody = "Hello $to_name, your registration for the $role role was unfortunately rejected. Reason: $reason";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('Rejection Email Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// ── Email Templates ───────────────────────────────────────────
// Include standalone template files. These render functions produce
// table-layout HTML compatible with all major email clients.

$_tpl_otp     = dirname(__DIR__) . '/email_templates/registration_otp.php';
$_tpl_welcome = dirname(__DIR__) . '/email_templates/registration_welcome.php';

if (file_exists($_tpl_otp))     require_once $_tpl_otp;
if (file_exists($_tpl_welcome)) require_once $_tpl_welcome;

// Fallback inline templates (used only if standalone files are missing)
if (!function_exists('render_otp_email')) {
    function render_otp_email(string $name, string $otp, int $expiry_mins): string {
        $digits = implode('', array_map(fn($d) => "<span style='display:inline-block;background:#EBF3FF;border:2px solid #2F80ED;border-radius:10px;padding:12px 18px;margin:4px;font-size:28px;font-weight:800;color:#1C3A6B;font-family:monospace;'>$d</span>", str_split($otp)));
        return "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'><div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;box-shadow:0 8px 32px rgba(47,128,237,.15);overflow:hidden;'><div style='background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:40px 32px;text-align:center;'><h1 style='color:#fff;font-size:24px;margin:0;'>🏥 RMU Medical Sickbay</h1></div><div style='padding:40px 32px;'><h2 style='color:#1A2035;'>Hello, {$name}!</h2><p style='color:#5A6A85;'>Your OTP code:</p><div style='text-align:center;margin:24px 0;'>{$digits}</div><p style='color:#5A6A85;font-size:13px;text-align:center;'>Expires in <strong>{$expiry_mins} minutes</strong>. Do not share with anyone.</p></div></div></body></html>";
    }
}

if (!function_exists('render_welcome_email')) {
    function render_welcome_email(string $name, string $role, bool $needs_approval): string {
        $role_label = ucwords(str_replace('_', ' ', $role));
        $msg = $needs_approval
            ? "Your <strong>{$role_label}</strong> account is pending admin approval. You will be notified once reviewed."
            : "Your <strong>{$role_label}</strong> account is now active. You may log in to the portal.";
        return "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'><div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'><div style='background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:40px 32px;text-align:center;'><h1 style='color:#fff;margin:0;'>🎉 Welcome to RMU Medical Sickbay!</h1></div><div style='padding:40px 32px;'><h2 style='color:#1A2035;'>Hello, {$name}!</h2><p style='color:#5A6A85;'>{$msg}</p></div></div></body></html>";
    }
}

if (!function_exists('render_rejection_email')) {
    function render_rejection_email(string $name, string $role, string $reason): string {
        $role_label = ucwords(str_replace('_', ' ', $role));
        return "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'><div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'><div style='background:linear-gradient(135deg,#c0392b,#e74c3c);padding:40px 32px;text-align:center;'><h1 style='color:#fff;margin:0;'>Registration Update</h1></div><div style='padding:40px 32px;'><h2 style='color:#1A2035;'>Hello, {$name},</h2><p style='color:#5A6A85;'>We regret to inform you that your application for the <strong>{$role_label}</strong> role has been rejected.</p><div style='padding:16px;background:#FDEDEC;border-left:4px solid #c0392b;margin:20px 0;color:#c0392b;'><p style='margin:0;'><strong>Reason provided by administration:</strong><br>{$reason}</p></div><p style='color:#5A6A85;'>If you believe this is a mistake, please contact the IT department or Sickbay administration.</p></div></div></body></html>";
    }
}

function reg_otp_email_html(string $name, string $otp, int $expiry_mins): string {
    return render_otp_email($name, $otp, $expiry_mins);
}

function reg_welcome_email_html(string $name, string $role, bool $needs_approval): string {
    return render_welcome_email($name, $role, $needs_approval);
}

function reg_rejection_email_html(string $name, string $role, string $reason): string {
    return render_rejection_email($name, $role, $reason);
}

/**
 * Send 2FA OTP email.
 */
function reg_send_2fa_email($conn, string $to_email, string $to_name, string $otp): void {
        $cfg  = reg_get_smtp_config($conn);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['smtp_username'];
            $mail->Password   = $cfg['smtp_password_plain'];
            $mail->SMTPSecure = ($cfg['encryption'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
            $mail->Port       = (int)($cfg['smtp_port'] ?? 587);
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Verification Code — RMU Medical Sickbay';
            $mail->Body    =
                "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'>".                "<div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'>".                "<div style='background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:32px;text-align:center;'>".                "<h1 style='color:#fff;margin:0;'>&#x1F510; Two-Factor Authentication</h1></div>".                "<div style='padding:32px;'>".                "<h2 style='color:#1A2035;'>Hello, {$to_name}!</h2>".                "<p style='color:#5A6A85;'>Your one-time verification code is:</p>".                "<div style='text-align:center;margin:24px 0;'>".                "<span style='font-size:3rem;font-weight:700;letter-spacing:12px;color:#2F80ED;'>{$otp}</span></div>".                "<p style='color:#e74c3c;'><strong>This code expires in 5 minutes.</strong></p>".                "<p style='color:#5A6A85;'>If you did not attempt to log in, please change your password immediately.</p>".                "</div></div></body></html>";
            $mail->send();
        } catch (Exception $e) {
            error_log('2FA email failed: ' . $mail->ErrorInfo);
        }
}

/**
 * Send password reset link email.
 */
function reg_send_password_reset_email($conn, string $to_email, string $to_name, string $reset_link): void {
        $cfg  = reg_get_smtp_config($conn);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['smtp_username'];
            $mail->Password   = $cfg['smtp_password_plain'];
            $mail->SMTPSecure = ($cfg['encryption'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
            $mail->Port       = (int)($cfg['smtp_port'] ?? 587);
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request — RMU Medical Sickbay';
            $mail->Body    =
                "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'>".                "<div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'>".                "<div style='background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:32px;text-align:center;'>".                "<h1 style='color:#fff;margin:0;'>&#x1F511; Password Reset Request</h1></div>".                "<div style='padding:32px;'>".                "<h2 style='color:#1A2035;'>Hello, {$to_name}!</h2>".                "<p style='color:#5A6A85;'>Click the button below to reset your password. This link expires in 30 minutes.</p>".                "<div style='text-align:center;margin:28px 0;'>".                "<a href='{$reset_link}' style='background:linear-gradient(135deg,#2F80ED,#56CCF2);color:#fff;padding:14px 32px;border-radius:50px;text-decoration:none;font-weight:600;'>Reset My Password</a></div>".                "<p style='color:#5A6A85;'>If you did not request this, you can safely ignore this email.</p>".                "<p style='color:#aaa;font-size:.8rem;word-break:break-all;'>Direct link: {$reset_link}</p>".                "</div></div></body></html>";
            $mail->send();
        } catch (Exception $e) {
            error_log('Password reset email failed: ' . $mail->ErrorInfo);
        }
}

/**
 * Send "password was changed" security notification.
 */
function reg_send_password_changed_email($conn, string $to_email, string $to_name, string $changed_at, string $ip): void {
        $cfg  = reg_get_smtp_config($conn);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['smtp_username'];
            $mail->Password   = $cfg['smtp_password_plain'];
            $mail->SMTPSecure = ($cfg['encryption'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
            $mail->Port       = (int)($cfg['smtp_port'] ?? 587);
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Was Changed — RMU Medical Sickbay';
            $mail->Body    =
                "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'>".                "<div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'>".                "<div style='background:linear-gradient(135deg,#27ae60,#2ecc71);padding:32px;text-align:center;'>".                "<h1 style='color:#fff;margin:0;'>&#x2705; Password Changed Successfully</h1></div>".                "<div style='padding:32px;'>".                "<h2 style='color:#1A2035;'>Hello, {$to_name}!</h2>".                "<p style='color:#5A6A85;'>Your account password was changed.</p>".                "<div style='background:#F4F8FF;border-radius:10px;padding:16px;margin:20px 0;'>".                "<p style='margin:4px 0;color:#5A6A85;'><strong>Time:</strong> {$changed_at}</p>".                "<p style='margin:4px 0;color:#5A6A85;'><strong>IP Address:</strong> {$ip}</p></div>".                "<p style='color:#e74c3c;'><strong>If this was not you, contact administration immediately.</strong></p>".                "</div></div></body></html>";
            $mail->send();
        } catch (Exception $e) {
            error_log('Password changed email failed: ' . $mail->ErrorInfo);
        }
}
