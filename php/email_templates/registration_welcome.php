<?php
/**
 * email_templates/registration_welcome.php
 * Standalone welcome/confirmation email template.
 * Call render_welcome_email($name, $role, $needs_approval) to get the HTML string.
 */

function render_welcome_email(string $name, string $role, bool $needs_approval): string {
    $role_label = ucwords(str_replace('_', ' ', $role));
    $year = date('Y');

    if ($needs_approval) {
        $status_icon    = '⏳';
        $status_color   = '#E67E22';
        $status_bg      = '#FEF9E7';
        $status_border  = '#F39C12';
        $status_heading = 'Account Pending Approval';
        $status_body    = "Your <strong>{$role_label}</strong> account has been created and your email has been verified. "
                        . "However, it requires <strong>administrator approval</strong> before you can log in. "
                        . "You will receive another email once your account has been reviewed and activated.";
    } else {
        $status_icon    = '✅';
        $status_color   = '#27AE60';
        $status_bg      = '#EAFAF1';
        $status_border  = '#27AE60';
        $status_heading = 'Account Activated!';
        $status_body    = "Your account is <strong>fully active</strong>. You can now log in to the "
                        . "<strong>RMU Medical Sickbay</strong> portal using your registered credentials.";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Welcome to RMU Medical Sickbay</title>
</head>
<body style="margin:0;padding:0;background:#F4F8FF;font-family:'Segoe UI',Poppins,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F4F8FF;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" border="0"
               style="background:#ffffff;border-radius:24px;box-shadow:0 8px 32px rgba(47,128,237,.15);overflow:hidden;max-width:560px;width:100%;">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 60%,#56CCF2 100%);padding:40px 32px;text-align:center;">
              <div style="font-size:48px;margin-bottom:12px;">🎉</div>
              <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:700;">
                Welcome to RMU Medical Sickbay!
              </h1>
              <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;">
                Registration Successful
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 32px;">
              <h2 style="color:#1A2035;font-size:20px;font-weight:600;margin:0 0 8px;">
                Hello, {$name}! 👋
              </h2>
              <p style="color:#5A6A85;font-size:15px;line-height:1.7;margin:0 0 24px;">
                Your registration as a <strong>{$role_label}</strong> has been processed successfully.
              </p>

              <!-- Status card -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background:{$status_bg};border-left:4px solid {$status_border};
                            border-radius:0 12px 12px 0;padding:20px;margin-bottom:28px;">
                <tr>
                  <td>
                    <p style="color:{$status_color};font-size:16px;font-weight:700;margin:0 0 8px;">
                      {$status_icon} {$status_heading}
                    </p>
                    <p style="color:#5A6A85;font-size:14px;line-height:1.6;margin:0;">
                      {$status_body}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Next steps -->
              <p style="color:#5A6A85;font-size:14px;line-height:1.7;margin:0;">
                If you have any questions or need assistance, please contact the RMU Medical Sickbay
                administration team. Keep your login credentials safe and do not share them with anyone.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#F4F8FF;padding:20px 32px;text-align:center;border-top:1px solid #E1EAFF;">
              <p style="color:#8FA3BF;font-size:12px;margin:0;">
                © {$year} RMU Medical Sickbay. All rights reserved.<br>
                This is an automated message — please do not reply to this email.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
