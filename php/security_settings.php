<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'classes/SecurityManager.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$twoFactorAuth = new TwoFactorAuth($conn);
$securityManager = new SecurityManager($conn);

$message = '';
$error = '';

// Handle 2FA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'enable_2fa':
            $result = $twoFactorAuth->enableTwoFactor($userId);
            if ($result['success']) {
                $_SESSION['2fa_setup'] = $result;
                $message = '2FA setup initiated. Scan the QR code with your authenticator app.';
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'verify_2fa':
            $code = $_POST['verification_code'] ?? '';
            $result = $twoFactorAuth->activateTwoFactor($userId, $code);
            if ($result['success']) {
                unset($_SESSION['2fa_setup']);
                $message = '2FA activated successfully!';
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'disable_2fa':
            $result = $twoFactorAuth->disableTwoFactor($userId);
            if ($result['success']) {
                $message = '2FA disabled successfully.';
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'regenerate_backup_codes':
            $result = $twoFactorAuth->regenerateBackupCodes($userId);
            if ($result['success']) {
                $_SESSION['new_backup_codes'] = $result['backup_codes'];
                $message = 'Backup codes regenerated. Please save them securely.';
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// Get current 2FA status
$twoFactorEnabled = $twoFactorAuth->isTwoFactorEnabled($userId);
$backupCodes = $twoFactorAuth->getBackupCodes($userId);

// Get login statistics
$loginStats = $securityManager->getLoginStatistics($userId, 30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - RMU Medical Sickbay</title>
    
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
            margin-bottom: 5px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-header i {
            font-size: 24px;
            color: #3498db;
        }
        
        .card-header h2 {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
        
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
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
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .qr-code {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .qr-code img {
            max-width: 200px;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .backup-codes {
            background: #fff3cd;
            border: 2px dashed #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        
        .backup-codes h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .codes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .code-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            text-align: center;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .stats-table th,
        .stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stats-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Security Settings</h1>
            <p>Manage your account security and authentication settings</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Two-Factor Authentication -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-mobile-alt"></i>
                    <h2>Two-Factor Authentication</h2>
                    <span class="status-badge <?php echo $twoFactorEnabled ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $twoFactorEnabled ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                
                <?php if (!$twoFactorEnabled && !isset($_SESSION['2fa_setup'])): ?>
                    <p style="margin-bottom: 15px; color: #7f8c8d;">
                        Add an extra layer of security to your account by enabling two-factor authentication.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="enable_2fa">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock"></i> Enable 2FA
                        </button>
                    </form>
                <?php elseif (isset($_SESSION['2fa_setup'])): ?>
                    <div class="qr-code">
                        <p style="margin-bottom: 15px;"><strong>Scan this QR code with your authenticator app:</strong></p>
                        <img src="<?php echo $_SESSION['2fa_setup']['qr_code_url']; ?>" alt="QR Code">
                        <p style="margin-top: 15px; font-size: 12px; color: #7f8c8d;">
                            Google Authenticator, Authy, Microsoft Authenticator
                        </p>
                    </div>
                    
                    <div class="backup-codes">
                        <h3><i class="fas fa-key"></i> Backup Codes</h3>
                        <p style="font-size: 13px; color: #856404; margin-bottom: 15px;">
                            Save these codes securely. Each can be used once if you lose access to your device.
                        </p>
                        <div class="codes-grid">
                            <?php foreach ($_SESSION['2fa_setup']['backup_codes'] as $code): ?>
                                <div class="code-item"><?php echo $code; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="verify_2fa">
                        <div class="form-group">
                            <label>Enter 6-digit code to verify:</label>
                            <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Verify & Activate
                        </button>
                    </form>
                <?php else: ?>
                    <div class="info-box">
                        <i class="fas fa-check-circle"></i> Two-factor authentication is active and protecting your account.
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <form method="POST" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="action" value="regenerate_backup_codes">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Regenerate Backup Codes
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to disable 2FA?');">
                            <input type="hidden" name="action" value="disable_2fa">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times"></i> Disable 2FA
                            </button>
                        </form>
                    </div>
                    
                    <?php if (isset($_SESSION['new_backup_codes'])): ?>
                        <div class="backup-codes" style="margin-top: 20px;">
                            <h3><i class="fas fa-key"></i> New Backup Codes</h3>
                            <div class="codes-grid">
                                <?php foreach ($_SESSION['new_backup_codes'] as $code): ?>
                                    <div class="code-item"><?php echo $code; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php unset($_SESSION['new_backup_codes']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Login Activity -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h2>Recent Login Activity</h2>
                </div>
                
                <?php if (empty($loginStats)): ?>
                    <p style="color: #7f8c8d;">No recent login activity.</p>
                <?php else: ?>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Successful</th>
                                <th>Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($loginStats, 0, 10) as $stat): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($stat['date'])); ?></td>
                                    <td style="color: #27ae60;"><?php echo $stat['successful']; ?></td>
                                    <td style="color: #e74c3c;"><?php echo $stat['failed']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Password Security -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-key"></i>
                    <h2>Password Security</h2>
                </div>
                
                <div class="info-box">
                    <strong>Password Requirements:</strong>
                    <ul style="margin: 10px 0 0 20px; font-size: 13px;">
                        <li>Minimum 8 characters</li>
                        <li>At least one uppercase letter</li>
                        <li>At least one lowercase letter</li>
                        <li>At least one number</li>
                        <li>At least one special character</li>
                    </ul>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="change_password.php" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Change Password
                    </a>
                </div>
            </div>
            
            <!-- Account Security Tips -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightbulb"></i>
                    <h2>Security Tips</h2>
                </div>
                
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i>
                        Enable two-factor authentication
                    </li>
                    <li style="padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i>
                        Use a strong, unique password
                    </li>
                    <li style="padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i>
                        Never share your password
                    </li>
                    <li style="padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i>
                        Review login activity regularly
                    </li>
                    <li style="padding: 12px 0;">
                        <i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i>
                        Log out from shared devices
                    </li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'home.php' : 'dashboards/' . $_SESSION['role'] . '_dashboard.php'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
