<?php 
require_once 'db_conn.php';
require_once 'classes/SessionManager.php';

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

        // Validation
        if (empty($uname)) {
            header("Location: index.php?error=Username is required");
            exit();
        } else if (empty($pass)) {
            header("Location: index.php?error=Password is required");
            exit();
        } else if (empty($role)) {
            header("Location: index.php?error=Please select a role");
            exit();
        } else {
            
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
                    header("Location: index.php?error=Incorrect username, password, or role");
                    exit();
                }
            } else {
                header("Location: index.php?error=Incorrect username, password, or role");
                exit();
            }
            
            mysqli_stmt_close($stmt);
        }
        
    } else {
        header("Location: index.php?error=All fields are required");
        exit();
    }
    
} else {
    header("Location: index.php");
    exit();
}