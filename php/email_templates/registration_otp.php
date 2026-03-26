<?php
/**
 * email_templates/registration_otp.php
 * Standalone OTP email template.
 * Call render_otp_email($name, $otp, $expiry_mins) to get the HTML string.
 */

function render_otp_email(string $name, string $otp, int $expiry_mins): string {
    $digits = '';
    foreach (str_split($otp) as $d) {
        $digits .= "<span style='display:inline-block;background:#EBF3FF;border:2px solid #2F80ED;"
                 . "border-radius:10px;padding:12px 16px;margin:4px;font-size:28px;font-weight:800;"
                 . "color:#1C3A6B;font-family:monospace;letter-spacing:2px;'>$d</span>";
    }
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Email Verification — RMU Medical Sickbay</title>
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
              <div style="display:inline-block;background:rgba(255,255,255,.2);border-radius:16px;padding:12px 20px;margin-bottom:16px;">
                <span style="font-size:28px;">🏥</span>
              </div>
              <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:700;letter-spacing:.5px;">
                RMU Medical Sickbay
              </h1>
              <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;">
                Email Verification Code
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 32px;">
              <h2 style="color:#1A2035;font-size:20px;font-weight:600;margin:0 0 8px;">
                Hello, {$name}! 👋
              </h2>
              <p style="color:#5A6A85;font-size:15px;line-height:1.7;margin:0 0 28px;">
                Thank you for registering with the <strong>RMU Medical Sickbay</strong> system.
                Please use the code below to verify your email address and complete your registration.
              </p>

              <!-- OTP Display -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background:#F0F7FF;border-radius:16px;padding:24px;margin-bottom:24px;text-align:center;">
                <tr>
                  <td align="center">
                    <p style="color:#2F80ED;font-size:13px;font-weight:600;text-transform:uppercase;
                               letter-spacing:1.5px;margin:0 0 16px;">Your Verification Code</p>
                    <div style="margin:0 auto;">{$digits}</div>
                    <p style="color:#8FA3BF;font-size:13px;margin:16px 0 0;">
                      This code expires in <strong style="color:#E67E22;">{$expiry_mins} minutes</strong>
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Warning -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background:#FEF9E7;border-left:4px solid #F39C12;border-radius:0 8px 8px 0;padding:14px 18px;margin-bottom:24px;">
                <tr>
                  <td>
                    <p style="margin:0;color:#7D6608;font-size:13px;line-height:1.6;">
                      ⚠️ <strong>Important:</strong> Do not share this code with anyone.
                      RMU Medical Sickbay staff will <em>never</em> ask for your OTP.
                      If you did not request this code, please ignore this email.
                    </p>
                  </td>
                </tr>
              </table>
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
