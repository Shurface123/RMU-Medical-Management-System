# Email Configuration Guide

## Overview
This guide will help you configure the email notification system for RMU Medical Management System.

---

## 📧 SMTP Configuration

### Option 1: Gmail SMTP (Recommended for Testing)

1. **Enable 2-Step Verification** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   
3. **Update Configuration** in `php/cron/process_email_queue.php`:
   ```php
   $emailConfig = [
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'smtp_username' => 'your-email@gmail.com',
       'smtp_password' => 'your-app-password',
       'from_email' => 'Sickbay.txt@rmu.edu.gh',
       'from_name' => 'RMU Medical Sickbay'
   ];
   ```

### Option 2: Office 365 / Outlook

```php
$emailConfig = [
    'smtp_host' => 'smtp.office365.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@outlook.com',
    'smtp_password' => 'your-password',
    'from_email' => 'Sickbay.txt@rmu.edu.gh',
    'from_name' => 'RMU Medical Sickbay'
];
```

### Option 3: Custom SMTP Server

```php
$emailConfig = [
    'smtp_host' => 'mail.yourdomain.com',
    'smtp_port' => 587, // or 465 for SSL
    'smtp_username' => 'noreply@yourdomain.com',
    'smtp_password' => 'your-password',
    'from_email' => 'Sickbay.txt@rmu.edu.gh',
    'from_name' => 'RMU Medical Sickbay'
];
```

---

## 📦 Installing PHPMailer

### Method 1: Using Composer (Recommended)

```bash
cd c:\wamp64\www\RMU-Medical-Management-System
composer require phpmailer/phpmailer
```

### Method 2: Manual Installation

1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract to: `c:\wamp64\www\RMU-Medical-Management-System\vendor\phpmailer`
3. Update autoload path in `EmailService.php`:
   ```php
   require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
   require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
   require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
   ```

---

## ⏰ Setting Up Cron Job

### On Windows (WAMP)

#### Option 1: Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. **Name**: RMU Email Queue Processor
4. **Trigger**: Daily, repeat every 5 minutes
5. **Action**: Start a program
   - **Program**: `C:\wamp64\bin\php\php8.x.x\php.exe`
   - **Arguments**: `C:\wamp64\www\RMU-Medical-Management-System\php\cron\process_email_queue.php`

#### Option 2: Using Cron for Windows

1. Install Cron for Windows: https://www.cronforwindows.com/
2. Add cron entry:
   ```
   */5 * * * * php C:\wamp64\www\RMU-Medical-Management-System\php\cron\process_email_queue.php
   ```

### On Linux/Mac

Add to crontab:
```bash
crontab -e
```

Add this line:
```
*/5 * * * * php /path/to/RMU-Medical-Management-System/php/cron/process_email_queue.php
```

---

## 🧪 Testing Email System

### Test Script

Create `php/test_email.php`:

```php
<?php
require_once 'db_conn.php';
require_once 'classes/EmailService.php';

$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-app-password',
    'from_email' => 'Sickbay.txt@rmu.edu.gh',
    'from_name' => 'RMU Medical Sickbay'
];

$emailService = new EmailService($conn, $emailConfig);

// Test immediate send
$result = $emailService->sendEmail(
    'test@example.com',
    'Test Email',
    '<h1>This is a test email</h1>',
    'Test User'
);

if ($result['success']) {
    echo "Email sent successfully!";
} else {
    echo "Error: " . $result['message'];
}
?>
```

Run: `php php/test_email.php`

---

## 📝 Email Templates

All templates are in `email_templates/` directory:

- `registration_welcome.html` - New user registration
- `appointment_confirmation.html` - Appointment booked
- `appointment_reminder.html` - 24h before appointment
- `password_reset.html` - Password reset request
- `test_result_ready.html` - Lab results available
- `prescription_ready.html` - Prescription ready for pickup

### Customizing Templates

Templates use `{{placeholder}}` syntax:

```html
<p>Dear <strong>{{name}}</strong>,</p>
```

Available placeholders vary by template. Check `EmailService.php` for details.

---

## 🔍 Monitoring & Logs

### Email Queue Status

Check queue in database:
```sql
SELECT status, COUNT(*) as count 
FROM email_queue 
GROUP BY status;
```

### Log Files

Logs are stored in: `logs/email_queue.log`

View recent logs:
```bash
tail -f logs/email_queue.log
```

---

## 🚨 Troubleshooting

### Issue: "SMTP connect() failed"

**Solution**:
- Check SMTP credentials
- Verify firewall allows outbound port 587/465
- Enable "Less secure app access" (Gmail)
- Use App Password instead of regular password

### Issue: Emails going to spam

**Solution**:
- Set up SPF record for your domain
- Set up DKIM authentication
- Use a verified sender email
- Avoid spam trigger words

### Issue: Queue not processing

**Solution**:
- Check cron job is running
- Verify PHP path in cron
- Check file permissions
- Review log files for errors

---

## 📊 Usage Examples

### Send Registration Email

```php
$emailService->sendRegistrationEmail(
    'user@example.com',
    'John Doe'
);
```

### Send Appointment Confirmation

```php
$appointmentDetails = [
    'date' => '2026-02-10',
    'time' => '10:00 AM',
    'doctor' => 'Dr. Smith',
    'type' => 'General Consultation'
];

$emailService->sendAppointmentConfirmation(
    'user@example.com',
    'John Doe',
    $appointmentDetails
);
```

### Queue Custom Email

```php
$emailService->queueEmail(
    'user@example.com',
    'Custom Subject',
    '<h1>Custom HTML Body</h1>',
    'John Doe',
    'custom_template',
    'high' // priority
);
```

---

## ✅ Checklist

Before going live:

- [ ] SMTP credentials configured
- [ ] PHPMailer installed
- [ ] Test email sent successfully
- [ ] Cron job scheduled and running
- [ ] Email templates customized
- [ ] Log directory created with write permissions
- [ ] Database tables created (email_queue, notifications)
- [ ] Spam filters configured
- [ ] Monitoring set up

---

## 📞 Support

For issues:
- Email: Sickbay.txt@rmu.edu.gh
- Phone: 0502371207
