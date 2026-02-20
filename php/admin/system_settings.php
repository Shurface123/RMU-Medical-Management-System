<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$auditLogger = new AuditLogger($conn);

$message = '';
$error = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_general':
            $siteName = $_POST['site_name'];
            $siteEmail = $_POST['site_email'];
            $timezone = $_POST['timezone'];
            
            // Update or insert settings
            $settings = [
                'site_name' => $siteName,
                'site_email' => $siteEmail,
                'timezone' => $timezone
            ];
            
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO system_config (config_key, config_value, updated_by) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()";
                $stmt = $conn->prepare($query);
                $userId = $_SESSION['user_id'];
                $stmt->bind_param("ssisi", $key, $value, $userId, $value, $userId);
                $stmt->execute();
            }
            
            $auditLogger->log($_SESSION['user_id'], 'config_update', 'system_config', null, null, 'Updated general settings');
            $message = "General settings updated successfully!";
            break;
            
        case 'update_security':
            $sessionTimeout = $_POST['session_timeout'];
            $maxLoginAttempts = $_POST['max_login_attempts'];
            $lockoutDuration = $_POST['lockout_duration'];
            $passwordExpiry = $_POST['password_expiry'];
            $require2FA = isset($_POST['require_2fa']) ? '1' : '0';
            
            $settings = [
                'session_timeout' => $sessionTimeout,
                'max_login_attempts' => $maxLoginAttempts,
                'lockout_duration' => $lockoutDuration,
                'password_expiry_days' => $passwordExpiry,
                'require_2fa' => $require2FA
            ];
            
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO system_config (config_key, config_value, updated_by) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()";
                $stmt = $conn->prepare($query);
                $userId = $_SESSION['user_id'];
                $stmt->bind_param("ssisi", $key, $value, $userId, $value, $userId);
                $stmt->execute();
            }
            
            $auditLogger->log($_SESSION['user_id'], 'config_update', 'system_config', null, null, 'Updated security settings');
            $message = "Security settings updated successfully!";
            break;
            
        case 'update_email':
            $smtpHost = $_POST['smtp_host'];
            $smtpPort = $_POST['smtp_port'];
            $smtpUsername = $_POST['smtp_username'];
            $smtpPassword = $_POST['smtp_password'];
            $smtpFrom = $_POST['smtp_from'];
            
            $settings = [
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_username' => $smtpUsername,
                'smtp_from' => $smtpFrom
            ];
            
            // Only update password if provided
            if (!empty($smtpPassword)) {
                $settings['smtp_password'] = base64_encode($smtpPassword); // Basic encoding
            }
            
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO system_config (config_key, config_value, updated_by) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()";
                $stmt = $conn->prepare($query);
                $userId = $_SESSION['user_id'];
                $stmt->bind_param("ssisi", $key, $value, $userId, $value, $userId);
                $stmt->execute();
            }
            
            $auditLogger->log($_SESSION['user_id'], 'config_update', 'system_config', null, null, 'Updated email settings');
            $message = "Email settings updated successfully!";
            break;
    }
}

// Get current settings
function getSetting($conn, $key, $default = '') {
    $query = "SELECT config_value FROM system_config WHERE config_key = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['config_value'];
    }
    return $default;
}

$settings = [
    'site_name' => getSetting($conn, 'site_name', 'RMU Medical Sickbay'),
    'site_email' => getSetting($conn, 'site_email', 'sickbay.txt@rmu.edu.gh'),
    'timezone' => getSetting($conn, 'timezone', 'Africa/Accra'),
    'session_timeout' => getSetting($conn, 'session_timeout', '30'),
    'max_login_attempts' => getSetting($conn, 'max_login_attempts', '5'),
    'lockout_duration' => getSetting($conn, 'lockout_duration', '15'),
    'password_expiry_days' => getSetting($conn, 'password_expiry_days', '90'),
    'require_2fa' => getSetting($conn, 'require_2fa', '0'),
    'smtp_host' => getSetting($conn, 'smtp_host', 'smtp.gmail.com'),
    'smtp_port' => getSetting($conn, 'smtp_port', '587'),
    'smtp_username' => getSetting($conn, 'smtp_username', ''),
    'smtp_from' => getSetting($conn, 'smtp_from', 'sickbay.txt@rmu.edu.gh')
];

// Get PHP info
$phpVersion = phpversion();
$maxUploadSize = ini_get('upload_max_filesize');
$maxPostSize = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - RMU Medical Sickbay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 12px 24px;
            background: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab.active {
            background: #3498db;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .settings-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group small {
            color: #7f8c8d;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> System Settings</h1>
            <p style="color: #7f8c8d; margin-top: 5px;">Configure system-wide settings and preferences</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('general')">
                <i class="fas fa-info-circle"></i> General
            </button>
            <button class="tab" onclick="switchTab('security')">
                <i class="fas fa-shield-alt"></i> Security
            </button>
            <button class="tab" onclick="switchTab('email')">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button class="tab" onclick="switchTab('system')">
                <i class="fas fa-server"></i> System Info
            </button>
        </div>
        
        <!-- General Settings -->
        <div id="general" class="tab-content active">
            <div class="settings-card">
                <h2>General Settings</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Site Email</label>
                        <input type="email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                        <small>Main contact email for the system</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Timezone</label>
                        <select name="timezone" required>
                            <option value="Africa/Accra" <?php echo $settings['timezone'] === 'Africa/Accra' ? 'selected' : ''; ?>>Africa/Accra (GMT)</option>
                            <option value="Africa/Lagos" <?php echo $settings['timezone'] === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                            <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                            <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Security Settings -->
        <div id="security" class="tab-content">
            <div class="settings-card">
                <h2>Security Settings</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_security">
                    
                    <div class="form-group">
                        <label>Session Timeout (minutes)</label>
                        <input type="number" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout']); ?>" min="5" max="1440" required>
                        <small>How long before inactive users are logged out</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Max Login Attempts</label>
                        <input type="number" name="max_login_attempts" value="<?php echo htmlspecialchars($settings['max_login_attempts']); ?>" min="3" max="10" required>
                        <small>Number of failed login attempts before account lockout</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Lockout Duration (minutes)</label>
                        <input type="number" name="lockout_duration" value="<?php echo htmlspecialchars($settings['lockout_duration']); ?>" min="5" max="60" required>
                        <small>How long accounts remain locked after max failed attempts</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Password Expiry (days)</label>
                        <input type="number" name="password_expiry" value="<?php echo htmlspecialchars($settings['password_expiry_days']); ?>" min="0" max="365" required>
                        <small>Set to 0 to disable password expiry</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="require_2fa" id="require_2fa" <?php echo $settings['require_2fa'] === '1' ? 'checked' : ''; ?>>
                            <label for="require_2fa" style="margin: 0;">Require 2FA for all users</label>
                        </div>
                        <small>Force all users to enable two-factor authentication</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Security Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div id="email" class="tab-content">
            <div class="settings-card">
                <h2>Email Settings</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_email">
                    
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                        <small>e.g., smtp.gmail.com, smtp.office365.com</small>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" required>
                        <small>Usually 587 for TLS or 465 for SSL</small>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" required>
                        <small>Your email address</small>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_password" placeholder="Leave blank to keep current password">
                        <small>App password for Gmail, or regular password for other providers</small>
                    </div>
                    
                    <div class="form-group">
                        <label>From Email Address</label>
                        <input type="email" name="smtp_from" value="<?php echo htmlspecialchars($settings['smtp_from']); ?>" required>
                        <small>Email address shown as sender</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Email Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- System Info -->
        <div id="system" class="tab-content">
            <div class="settings-card">
                <h2>System Information</h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>PHP Version</strong>
                        <span><?php echo $phpVersion; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Max Upload Size</strong>
                        <span><?php echo $maxUploadSize; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Max POST Size</strong>
                        <span><?php echo $maxPostSize; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Memory Limit</strong>
                        <span><?php echo $memoryLimit; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Server Software</strong>
                        <span><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Database</strong>
                        <span>MySQL <?php echo mysqli_get_server_info($conn); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.closest('.tab').classList.add('active');
        }
    </script>
</body>
</html>