# Security Configuration Guide

## Overview
This guide explains how to configure and use the security features implemented in Phase 4 of the RMU Medical Management System.

---

## 🔐 Two-Factor Authentication (2FA)

### Setup for Users

1. **Enable 2FA**:
   - Go to Account Settings
   - Click "Enable Two-Factor Authentication"
   - Scan QR code with authenticator app (Google Authenticator, Authy, Microsoft Authenticator)
   - Enter 6-digit code to verify
   - Save backup codes securely

2. **Login with 2FA**:
   - Enter username and password
   - Enter 6-digit code from authenticator app
   - Or use backup code if device is unavailable

### For Developers

```php
require_once 'classes/TwoFactorAuth.php';

$twoFactorAuth = new TwoFactorAuth($conn);

// Enable 2FA for user
$result = $twoFactorAuth->enableTwoFactor($userId);
// Returns: secret, backup_codes, qr_code_url

// Verify and activate
$result = $twoFactorAuth->activateTwoFactor($userId, $code);

// Verify during login
$result = $twoFactorAuth->verifyTwoFactor($userId, $code);

// Check if enabled
$enabled = $twoFactorAuth->isTwoFactorEnabled($userId);
```

---

## 🔒 Password Policies

### Requirements

- **Minimum Length**: 8 characters
- **Uppercase**: At least 1 uppercase letter
- **Lowercase**: At least 1 lowercase letter
- **Numbers**: At least 1 number
- **Special Characters**: At least 1 special character (!@#$%^&*...)
- **History**: Cannot reuse last 5 passwords
- **Expiry**: Passwords expire after 90 days

### Usage

```php
require_once 'classes/SecurityManager.php';

$securityManager = new SecurityManager($conn);

// Validate password
$result = $securityManager->validatePassword($password);
if (!$result['valid']) {
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
}

// Check password history
$isNew = $securityManager->checkPasswordHistory($userId, $newPassword);

// Add to history after password change
$securityManager->addPasswordToHistory($userId, $passwordHash);

// Generate secure password
$password = $securityManager->generateSecurePassword(12);
```

---

## 🚫 Rate Limiting & Account Lockout

### Settings

- **Max Attempts**: 5 failed login attempts
- **Lockout Duration**: 15 minutes
- **Attempt Window**: 5 minutes

### How It Works

1. User fails login → Attempt recorded
2. After 5 failed attempts in 5 minutes → Account locked for 15 minutes
3. Successful login → Failed attempts cleared

### Usage

```php
$securityManager = new SecurityManager($conn);

// Check if account is locked
$lockStatus = $securityManager->isAccountLocked($username, $ipAddress);
if ($lockStatus['locked']) {
    echo "Account locked for {$lockStatus['remaining_minutes']} minutes";
    exit;
}

// Record login attempt
$securityManager->recordLoginAttempt($username, $success, $ipAddress, $userAgent, $failureReason);

// Get remaining attempts
$remaining = $securityManager->getRemainingAttempts($username, $ipAddress);
echo "You have $remaining attempts remaining";

// Clear after successful login
$securityManager->clearFailedAttempts($username, $ipAddress);
```

---

## 📝 Audit Logging

### What Gets Logged

- User login/logout
- Password changes
- 2FA enable/disable
- Record creation/update/deletion
- Permission changes
- All administrative actions

### Usage

```php
require_once 'classes/AuditLogger.php';

$auditLogger = new AuditLogger($conn);

// Log login
$auditLogger->logLogin($userId, $success);

// Log logout
$auditLogger->logLogout($userId);

// Log record creation
$auditLogger->logCreate($userId, 'patients', $patientId, $values);

// Log record update
$auditLogger->logUpdate($userId, 'patients', $patientId, $oldValues, $newValues);

// Log record deletion
$auditLogger->logDelete($userId, 'patients', $patientId, $values);

// Log password change
$auditLogger->logPasswordChange($userId);

// Log 2FA change
$auditLogger->log2FAChange($userId, $enabled);

// Get logs with filters
$logs = $auditLogger->getLogs([
    'user_id' => $userId,
    'action' => 'LOGIN_SUCCESS',
    'date_from' => '2026-02-01',
    'date_to' => '2026-02-07'
], $limit = 100, $offset = 0);

// Get statistics
$stats = $auditLogger->getStatistics($days = 30);

// Export to CSV
$auditLogger->exportToCSV($filters, 'audit_log_export.csv');
```

---

## 🔧 Integration Example

### Enhanced Login Flow

```php
<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/SecurityManager.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'classes/AuditLogger.php';

$securityManager = new SecurityManager($conn);
$twoFactorAuth = new TwoFactorAuth($conn);
$auditLogger = new AuditLogger($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Check if account is locked
    $lockStatus = $securityManager->isAccountLocked($username, $ipAddress);
    if ($lockStatus['locked']) {
        $error = "Too many failed attempts. Account locked for {$lockStatus['remaining_minutes']} minutes.";
        $securityManager->recordLoginAttempt($username, false, $ipAddress, $userAgent, 'Account locked');
        exit;
    }
    
    // Verify credentials
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $securityManager->recordLoginAttempt($username, false, $ipAddress, $userAgent, 'Invalid username');
        $error = "Invalid credentials";
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        $securityManager->recordLoginAttempt($username, false, $ipAddress, $userAgent, 'Invalid password');
        $remaining = $securityManager->getRemainingAttempts($username, $ipAddress);
        $error = "Invalid credentials. $remaining attempts remaining.";
        exit;
    }
    
    // Check if 2FA is enabled
    if ($twoFactorAuth->isTwoFactorEnabled($user['id'])) {
        // Set 2FA pending session
        $_SESSION['2fa_pending'] = true;
        $_SESSION['2fa_user_id'] = $user['id'];
        
        $securityManager->recordLoginAttempt($username, true, $ipAddress, $userAgent);
        header("Location: verify_2fa.php");
        exit;
    }
    
    // Login successful
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['logged_in'] = true;
    $_SESSION['role'] = $user['role'];
    
    $securityManager->recordLoginAttempt($username, true, $ipAddress, $userAgent);
    $securityManager->clearFailedAttempts($username, $ipAddress);
    $auditLogger->logLogin($user['id'], true);
    
    // Check password expiry
    if ($securityManager->isPasswordExpired($user['id'])) {
        $_SESSION['password_expired'] = true;
        header("Location: change_password.php");
        exit;
    }
    
    header("Location: dashboard.php");
    exit;
}
?>
```

---

## 📊 Security Dashboard

### Monitoring

Track security metrics:
- Failed login attempts
- Locked accounts
- 2FA adoption rate
- Password expiry warnings
- Audit log activity

### SQL Queries

```sql
-- Failed login attempts today
SELECT COUNT(*) FROM login_attempts 
WHERE success = 0 
AND DATE(attempted_at) = CURDATE();

-- Currently locked accounts
SELECT DISTINCT username 
FROM login_attempts 
WHERE success = 0 
AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
GROUP BY username 
HAVING COUNT(*) >= 5;

-- 2FA adoption rate
SELECT 
    COUNT(CASE WHEN is_enabled = 1 THEN 1 END) as enabled,
    COUNT(*) as total,
    ROUND(COUNT(CASE WHEN is_enabled = 1 THEN 1 END) / COUNT(*) * 100, 2) as percentage
FROM two_factor_auth;

-- Recent audit activity
SELECT action, COUNT(*) as count 
FROM audit_log 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY action 
ORDER BY count DESC;
```

---

## ⚙️ Configuration

### System Settings

Update in `system_settings` table:

```sql
UPDATE system_settings SET setting_value = '10' WHERE setting_key = 'password_min_length';
UPDATE system_settings SET setting_value = '3' WHERE setting_key = 'max_login_attempts';
UPDATE system_settings SET setting_value = '1' WHERE setting_key = 'enable_2fa';
```

### PHP Configuration

Edit `SecurityManager.php` to customize:

```php
private $minPasswordLength = 8;
private $requireUppercase = true;
private $requireLowercase = true;
private $requireNumbers = true;
private $requireSpecialChars = true;
private $passwordHistoryCount = 5;
private $passwordExpiryDays = 90;
private $maxLoginAttempts = 5;
private $lockoutDuration = 900; // 15 minutes
private $attemptWindow = 300; // 5 minutes
```

---

## ✅ Security Checklist

Before going live:

- [ ] Database tables created (two_factor_auth, password_history, login_attempts, audit_log)
- [ ] Password policies configured
- [ ] Rate limiting tested
- [ ] 2FA tested with authenticator app
- [ ] Audit logging verified
- [ ] Security settings documented
- [ ] Admin trained on security features
- [ ] Users notified about 2FA option
- [ ] Backup codes stored securely
- [ ] Log rotation configured

---

## 🚨 Security Best Practices

1. **Passwords**:
   - Never store plain text passwords
   - Use `password_hash()` with PASSWORD_DEFAULT
   - Enforce password expiry
   - Prevent password reuse

2. **2FA**:
   - Encourage all users to enable 2FA
   - Provide backup codes
   - Support multiple authenticator apps

3. **Rate Limiting**:
   - Monitor for brute force attacks
   - Adjust thresholds as needed
   - Consider IP-based blocking

4. **Audit Logs**:
   - Review logs regularly
   - Set up alerts for suspicious activity
   - Archive old logs
   - Protect log integrity

5. **Session Management**:
   - Use secure session cookies
   - Implement session timeout
   - Regenerate session IDs
   - Clear sessions on logout

---

## 📞 Support

For security issues:
- Email: Sickbay.txt@rmu.edu.gh
- Phone: 0502371207
- Emergency: Report immediately to IT department
