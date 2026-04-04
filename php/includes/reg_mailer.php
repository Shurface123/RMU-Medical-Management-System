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
            $mail->SMTPSecure = (($cfg['encryption'] ?? 'tls') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($cfg['smtp_port'] ?? 587);
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Verification Code — RMU Medical Sickbay';
            $mail->Body    =
                "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#F4F8FF;'>".                "<div style='max-width:560px;margin:40px auto;background:#fff;border-radius:24px;overflow:hidden;'>".                "<div style='background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:32px;text-align:center;'>".                "<h1 style='color:#fff;margin:0;'>&#x1F510; Two-Factor Authentication</h1></div>".                "<div style='padding:32px;'>".                "<h2 style='color:#1A2035;'>Hello, {$to_name}!</h2>".                "<p style='color:#5A6A85;'>Your one-time verification code is:</p>".                "<div style='text-align:center;margin:24px 0;'>".                "<span style='font-size:3rem;font-weight:700;letter-spacing:12px;color:#2F80ED;'>{$otp}</span></div>".                "<p style='color:#e74c3c;'><strong>This code expires in 5 minutes.</strong></p>".                "<p style='color:#5A6A85;'>If you did not attempt to log in, please change your password immediately.</p>".                "</div></div></body></html>";
            $mail->send();
        } catch (Exception $e) {
            error_log('[RMU-Sickbay] 2FA email failed to ' . $to_email . ': ' . $mail->ErrorInfo);
        }
}

/**
 * Send password reset link email.
 */
function reg_send_password_reset_email($conn, string $to_email, string $to_name, string $reset_link): array {
    $cfg  = reg_get_smtp_config($conn);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug  = 0; // Set to SMTP::DEBUG_SERVER for troubleshooting
        $mail->Host       = $cfg['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_username'] ?? '';
        $mail->Password   = $cfg['smtp_password_plain'] ?? '';
        $mail->SMTPSecure = (($cfg['encryption'] ?? 'tls') === 'ssl')
                            ? PHPMailer::ENCRYPTION_SMTPS
                            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($cfg['smtp_port'] ?? 587);
        $mail->Timeout    = 15;
        $mail->setFrom($cfg['from_email'] ?? 'sickbay.text@st.rmu.edu.gh',
                       $cfg['from_name']  ?? 'RMU Medical Sickbay');
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Request — RMU Medical Sickbay';

        // ── Rich HTML Email Body ──────────────────────────────
        $year = date('Y');
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#F4F8FF;font-family:Poppins,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding:40px 16px;">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(47,128,237,.15);">
      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#1C3A6B,#2F80ED,#56CCF2);padding:36px 32px;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🔑</div>
        <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Password Reset Request</h1>
        <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:14px;">RMU Medical Sickbay System</p>
      </td></tr>
      <!-- Body -->
      <tr><td style="padding:36px 32px;">
        <p style="color:#2c3e50;font-size:16px;font-weight:600;margin:0 0 8px;">Hello, {$to_name}!</p>
        <p style="color:#5A6A85;font-size:14px;line-height:1.7;margin:0 0 24px;">
          We received a request to reset the password for your RMU Medical Sickbay account.
          Click the button below to set a new password. This link is valid for <strong>30 minutes</strong>.
        </p>
        <!-- CTA Button -->
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
          <tr><td align="center" bgcolor="#2F80ED" style="border-radius:50px;">
            <a href="{$reset_link}"
               style="display:inline-block;padding:14px 36px;color:#fff;font-size:15px;font-weight:700;text-decoration:none;border-radius:50px;">
              Reset My Password
            </a>
          </td></tr>
        </table>
        <!-- Security Notice -->
        <table cellpadding="0" cellspacing="0" width="100%">
          <tr><td style="background:#FEF9E7;border-left:4px solid #F39C12;border-radius:8px;padding:14px 16px;">
            <p style="margin:0;color:#935116;font-size:13px;line-height:1.6;">
              <strong>⚠ Security Notice:</strong> If you did not request a password reset, please ignore this email.
              Your password will remain unchanged. Consider reviewing your account security if you did not initiate this request.
            </p>
          </td></tr>
        </table>
        <p style="color:#aaa;font-size:12px;margin:20px 0 0;word-break:break-all;">
          If the button above does not work, copy and paste this link into your browser:<br>
          <a href="{$reset_link}" style="color:#2F80ED;">{$reset_link}</a>
        </p>
      </td></tr>
      <!-- Footer -->
      <tr><td style="background:#F4F8FF;padding:20px 32px;text-align:center;border-top:1px solid #e8eef7;">
        <p style="margin:0;color:#7f8c8d;font-size:12px;">
          &copy; {$year} RMU Medical Sickbay, Regional Maritime University, Accra, Ghana.<br>
          This is an automated message. Please do not reply to this email.
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
        $mail->AltBody = "Hello {$to_name},

You requested a password reset for your RMU Medical Sickbay account.

"
                       . "Reset link (valid 30 minutes):
{$reset_link}

"
                       . "If you did not request this, please ignore this email.

"
                       . "RMU Medical Sickbay";

        $mail->send();

        // Log success
        reg_log_email_queue($conn, $to_email, 'password_reset', 'sent');
        return ['success' => true];

    } catch (Exception $e) {
        $err = $mail->ErrorInfo;
        error_log('[RMU-Sickbay] Password reset email FAILED to ' . $to_email . ' — ' . $err);
        reg_log_email_queue($conn, $to_email, 'password_reset', 'failed', $err);
        return ['success' => false, 'error' => $err];
    }
}

/**
 * Helper: log email send attempts to email_queue_log table (if available).
 */
function reg_log_email_queue($conn, string $to_email, string $type, string $status, string $error = ''): void {
    // Write to flat log file as guaranteed fallback
    $log_dir  = dirname(__DIR__, 2) . '/logs/';
    $log_file = $log_dir . 'email_queue.log';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " | $status | $type | $to_email"
          . ($error ? " | ERR: $error" : '') . PHP_EOL;
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);

    // Also attempt DB log
    @mysqli_query($conn,
        "INSERT IGNORE INTO email_queue_log (to_email, email_type, status, error_message, sent_at)
         VALUES ('" . mysqli_real_escape_string($conn, $to_email) . "',
                 '" . mysqli_real_escape_string($conn, $type)     . "',
                 '" . mysqli_real_escape_string($conn, $status)   . "',
                 '" . mysqli_real_escape_string($conn, $error)    . "',
                 NOW())"
    );
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
            $mail->SMTPSecure = (($cfg['encryption'] ?? 'tls') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
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
