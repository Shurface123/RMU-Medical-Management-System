<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/TwoFactorAuth.php';

// Check if user is in 2FA verification state
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_pending'])) {
    header("Location: index.php");
    exit();
}

$twoFactorAuth = new TwoFactorAuth($conn);
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $userId = $_SESSION['2fa_user_id'];
    
    if (empty($code)) {
        $error = 'Please enter the verification code';
    } else {
        $result = $twoFactorAuth->verifyTwoFactor($userId, $code);
        
        if ($result['success']) {
            // 2FA successful - complete login
            unset($_SESSION['2fa_pending']);
            unset($_SESSION['2fa_user_id']);
            
            // Set user session
            $_SESSION['user_id'] = $userId;
            $_SESSION['logged_in'] = true;
            
            // Get user role and redirect
            $query = "SELECT role FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: home.php");
                    break;
                case 'doctor':
                    header("Location: dashboards/doctor_dashboard.php");
                    break;
                case 'patient':
                    header("Location: dashboards/patient_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - RMU Medical Sickbay</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .code-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .code-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .help-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        
        .help-text a {
            color: #667eea;
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
        
        .info-box i {
            color: #667eea;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-shield-alt"></i>
            <h1>Two-Factor Authentication</h1>
            <p>Enter the 6-digit code from your authenticator app</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Open your authenticator app (Google Authenticator, Authy, etc.) and enter the 6-digit code.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    class="code-input" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    placeholder="000000"
                    autocomplete="off"
                    autofocus
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Verify
            </button>
        </form>
        
        <div class="help-text">
            <p>Lost your device? Use a <a href="#" onclick="showBackupCodeInput()">backup code</a></p>
            <p style="margin-top: 10px;">
                <a href="logout.php">Cancel and logout</a>
            </p>
        </div>
    </div>
    
    <script>
        // Auto-format code input
        const codeInput = document.getElementById('code');
        
        codeInput.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
        
        // Paste handling
        codeInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/[^0-9]/g, '').substring(0, 6);
            this.value = numbers;
            
            if (numbers.length === 6) {
                this.form.submit();
            }
        });
        
        function showBackupCodeInput() {
            const label = document.querySelector('label[for="code"]');
            const input = document.getElementById('code');
            
            label.textContent = 'Backup Code';
            input.placeholder = 'XXXXXXXX';
            input.maxLength = 8;
            input.pattern = '[A-Za-z0-9]{8}';
            input.style.letterSpacing = '4px';
            input.value = '';
            input.focus();
        }
    </script>
</body>
</html>
