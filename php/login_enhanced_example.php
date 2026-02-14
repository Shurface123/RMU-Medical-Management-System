<?php 
/**
 * ENHANCED LOGIN WITH SECURITY FEATURES
 * This is an example of how to integrate all security features into your login.php
 * Copy the relevant sections to your existing login.php file
 */

session_start();
require_once 'db_conn.php';
require_once 'classes/SessionManager.php';
require_once 'classes/SecurityManager.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'classes/AuditLogger.php';
require_once 'classes/CaptchaManager.php';

// Initialize security classes
$securityManager = new SecurityManager($conn);
$twoFactorAuth = new TwoFactorAuth($conn);
$auditLogger = new AuditLogger($conn);
$captchaManager = new CaptchaManager();

$error = '';
$showCaptcha = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['uname']) && isset($_POST['password']) && isset($_POST['role'])) {

        function validate($data){
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        $uname = validate($_POST['uname']);
        $pass = validate($_POST['password']);
        $role = validate($_POST['role']);
        $identifier = $uname; // Use username as identifier

        // Validation
        if (empty($uname)) {
            $error = "Username is required";
        } else if (empty($pass)) {
            $error = "Password is required";
        } else if (empty($role)) {
            $error = "Please select a role";
        } else {
            
            // SECURITY CHECK 1: Check if account is locked
            if ($securityManager->isAccountLocked($identifier)) {
                $error = "Account is locked due to too many failed attempts. Please try again in 15 minutes.";
                $auditLogger->logLogin($uname, false, 'Account locked');
            } else {
                
                // SECURITY CHECK 2: Validate CAPTCHA if required
                if ($captchaManager->isCaptchaRequired($identifier, $conn)) {
                    $captchaInput = $_POST['captcha'] ?? '';
                    
                    if (!$captchaManager->validateCaptcha($captchaInput)) {
                        $error = "Invalid CAPTCHA. Please try again.";
                        $showCaptcha = true;
                    }
                }
                
                // Proceed with login if no CAPTCHA error
                if (empty($error)) {
                    // Query user from database
                    $sql = "SELECT * FROM users WHERE user_name = ? LIMIT 1";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $uname);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) === 1) {
                        $row = mysqli_fetch_assoc($result);
                        
                        // Verify password (support both MD5 legacy and password_hash)
                        $password_valid = false;
                        if (password_verify($pass, $row['password'])) {
                            $password_valid = true;
                        } else if (md5($pass) === $row['password']) {
                            // Legacy MD5 support
                            $password_valid = true;
                        }
                        
                        if ($password_valid && $row['user_role'] === $role) {
                            
                            // SECURITY CHECK 3: Check if 2FA is enabled
                            if ($twoFactorAuth->isTwoFactorEnabled($row['id'])) {
                                // Store user info in session for 2FA verification
                                $_SESSION['pending_2fa_user_id'] = $row['id'];
                                $_SESSION['pending_2fa_username'] = $row['user_name'];
                                $_SESSION['pending_2fa_role'] = $role;
                                
                                // Record successful password verification
                                $securityManager->recordLoginAttempt($identifier, true);
                                $auditLogger->logLogin($uname, true, 'Password verified, awaiting 2FA');
                                
                                // Redirect to 2FA verification
                                header("Location: verify_2fa.php");
                                exit();
                            }
                            
                            // No 2FA - proceed with normal login
                            // Record successful login
                            $securityManager->recordLoginAttempt($identifier, true);
                            $auditLogger->logLogin($uname, true, 'Login successful');
                            
                            // Create session using SessionManager
                            $sessionManager = new SessionManager($conn);
                            $sessionManager->startSession($row['id'], $role);

                            // Set basic session variables for compatibility
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['user_name'] = $row['user_name'];
                            $_SESSION['name'] = $row['name'];
                            $_SESSION['role'] = $role;
                            
                            // Route to appropriate dashboard based on role
                            switch ($role) {
                                case 'admin':
                                    header("Location: home.php");
                                    break;
                                case 'doctor':
                                    header("Location: dashboards/doctor_dashboard.php");
                                    break;
                                case 'patient':
                                    header("Location: dashboards/patient_dashboard.php");
                                    break;
                                case 'pharmacist':
                                    header("Location: dashboards/pharmacy_dashboard.php");
                                    break;
                                default:
                                    header("Location: index.php?error=Invalid role");
                                    break;
                            }
                            exit();
                        } else {
                            // Invalid password or role
                            $securityManager->recordLoginAttempt($identifier, false);
                            $auditLogger->logLogin($uname, false, 'Invalid password or role');
                            $error = "Incorrect username, password, or role";
                            
                            // Check if CAPTCHA should be shown
                            $showCaptcha = $captchaManager->isCaptchaRequired($identifier, $conn);
                        }
                    } else {
                        // User not found
                        $securityManager->recordLoginAttempt($identifier, false);
                        $auditLogger->logLogin($uname, false, 'User not found');
                        $error = "Incorrect username, password, or role";
                        
                        // Check if CAPTCHA should be shown
                        $showCaptcha = $captchaManager->isCaptchaRequired($identifier, $conn);
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            }
        }
        
    } else {
        $error = "All fields are required";
    }
    
} else {
    // GET request - check if we need to show CAPTCHA based on previous attempts
    if (isset($_GET['username'])) {
        $showCaptcha = $captchaManager->isCaptchaRequired($_GET['username'], $conn);
    }
}

// If there's an error, redirect back to index with error message
if (!empty($error) && !$showCaptcha) {
    header("Location: index.php?error=" . urlencode($error));
    exit();
}

// If we need to show CAPTCHA, we'll display the login form here
// Or you can redirect to index.php with a flag to show CAPTCHA
if ($showCaptcha) {
    // Store in session that CAPTCHA is required
    $_SESSION['captcha_required'] = true;
    $_SESSION['captcha_username'] = $uname ?? '';
    header("Location: index.php?error=" . urlencode($error));
    exit();
}
?>
