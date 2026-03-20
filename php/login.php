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
        $pass  = validate($_POST['password']);
        $role  = validate($_POST['role']);

        if (empty($uname)) {
            header("Location: index.php?error=Username is required"); exit();
        } elseif (empty($pass)) {
            header("Location: index.php?error=Password is required"); exit();
        } elseif (empty($role)) {
            header("Location: index.php?error=Please select a role"); exit();
        }

        // Query user
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_name = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $uname);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            // ── Global Brute-Force & Lockout Check ───
            if (isset($row['is_active']) && !$row['is_active']) {
                header("Location: index.php?error=" . urlencode('Account disabled. Contact administration.'));
                exit();
            }
            if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
                $rem = ceil((strtotime($row['locked_until']) - time()) / 60);
                header("Location: index.php?error=" . urlencode("Account locked due to multiple failed attempts. Try again in $rem minutes."));
                exit();
            }

            // ── Password verification (bcrypt first, fall back to MD5 legacy) ──
            $password_valid = false;
            if (password_verify($pass, $row['password'])) {
                $password_valid = true;
            } elseif (md5($pass) === $row['password']) {
                // Legacy MD5 — silently upgrade hash to bcrypt
                $password_valid = true;
                $new_hash = password_hash($pass, PASSWORD_BCRYPT);
                $upd = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                mysqli_stmt_bind_param($upd, "si", $new_hash, $row['id']);
                mysqli_stmt_execute($upd);
            }

            if ($password_valid && $row['user_role'] === $role) {
                // Clear any previous locks and log success globally
                $upd_lock = mysqli_prepare($conn, "UPDATE users SET locked_until=NULL WHERE id=?");
                mysqli_stmt_bind_param($upd_lock, "i", $row['id']);
                mysqli_stmt_execute($upd_lock);
                
                $log_suc = mysqli_prepare($conn, "INSERT INTO global_login_attempts (user_id, action_type, ip_address) VALUES (?, 'login_success', ?)");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                mysqli_stmt_bind_param($log_suc, "is", $row['id'], $ip);
                mysqli_stmt_execute($log_suc);

                // Start session
                $sessionManager = new SessionManager($conn);
                $sessionManager->startSession($row['id'], $role);

                $_SESSION['user_id']   = $row['id'];
                $_SESSION['user_name'] = $row['user_name'];
                $_SESSION['name']      = $row['name'];
                $_SESSION['role']      = $role;
                $_SESSION['user_role'] = $role;



                // ── Staff sub-role approval gate ───────────────────────
                $STAFF_SUB_ROLES = ['ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff'];
                if (in_array($role, $STAFF_SUB_ROLES)) {
                    // Quick check directly instead of requiring external helper
                    $app_q = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM staff WHERE user_id=? LIMIT 1");
                    mysqli_stmt_bind_param($app_q, "i", $row['id']);
                    mysqli_stmt_execute($app_q);
                    $app_res = mysqli_stmt_get_result($app_q);
                    $staff_row = mysqli_fetch_assoc($app_res);
                    $approval = $staff_row['approval_status'] ?? 'pending';
                    $reason   = $staff_row['rejection_reason'] ?? 'Contact administration for details.';

                    if ($approval === 'pending') {
                        // Log attempt in audit trail
                        @mysqli_query($conn, "INSERT INTO staff_audit_trail (user_id,action_type,module,description,created_at)
                            VALUES ({$row['id']},'login_blocked','security','Account pending admin approval',NOW())");
                        header("Location: index.php?error=" . urlencode("Your account is pending admin approval. You will be notified once approved."));
                        exit();
                    }

                    if ($approval === 'rejected') {
                        header("Location: index.php?error=" . urlencode("Account rejected: $reason"));
                        exit();
                    }
                    // $approval === 'approved' → fall through to routing
                }

                // Route by role
                switch ($role) {
                    case 'admin':          header("Location: home.php"); break;
                    case 'doctor':         header("Location: dashboards/doctor_dashboard.php"); break;
                    case 'patient':        header("Location: dashboards/patient_dashboard.php"); break;
                    case 'pharmacist':     header("Location: dashboards/pharmacy_dashboard.php"); break;
                    case 'nurse':          header("Location: dashboards/nurse_dashboard.php"); break;
                    case 'ambulance_driver':
                    case 'cleaner':
                    case 'laundry_staff':
                    case 'maintenance':
                    case 'security':
                    case 'kitchen_staff':  header("Location: dashboards/staff_dashboard.php"); break;
                    default:               header("Location: index.php?error=Invalid role"); break;
                }
                exit();


            } else {
                // Failed login — log globally
                if (isset($row['id'])) {
                    $log_fail = mysqli_prepare($conn, "INSERT INTO global_login_attempts (user_id, action_type, ip_address) VALUES (?, 'login_failed', ?)");
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    mysqli_stmt_bind_param($log_fail, "is", $row['id'], $ip);
                    mysqli_stmt_execute($log_fail);

                    // Check if should lock
                    $check_fails = mysqli_prepare($conn, "SELECT COUNT(*) as fails FROM global_login_attempts WHERE user_id=? AND action_type='login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                    mysqli_stmt_bind_param($check_fails, "i", $row['id']);
                    mysqli_stmt_execute($check_fails);
                    $c_res = mysqli_stmt_get_result($check_fails);
                    $fails = mysqli_fetch_assoc($c_res)['fails'] ?? 0;

                        if ($fails >= 5) {
                            $lock_upd = mysqli_prepare($conn, "UPDATE users SET locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id=?");
                            mysqli_stmt_bind_param($lock_upd, "i", $row['id']);
                            mysqli_stmt_execute($lock_upd);

                            // Notify Admins of brute-force attack
                            $msg = "Security Alert: Account '{$row['user_name']}' has been locked due to multiple failed login attempts from IP $ip.";
                            $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, type, related_module, created_at) SELECT id, ?, 'Security Alert', 'users', NOW() FROM users WHERE user_role='admin'");
                            mysqli_stmt_bind_param($notif_stmt, "s", $msg);
                            mysqli_stmt_execute($notif_stmt);

                            header("Location: index.php?error=" . urlencode('Account locked due to multiple failed attempts. Try again in 15 minutes.'));
                            exit();
                        }
                }
                header("Location: index.php?error=Incorrect username, password, or role");
                exit();
            }

        } else {
            header("Location: index.php?error=Incorrect username, password, or role");
            exit();
        }

    } else {
        header("Location: index.php?error=All fields are required");
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}