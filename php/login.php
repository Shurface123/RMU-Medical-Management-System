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

            // ── Lab-technician brute-force lockout (before password check) ───
            if ($role === 'lab_technician') {
                require_once __DIR__ . '/dashboards/lab_security.php';

                // Reject if account already locked
                if (isset($row['is_active']) && !$row['is_active']) {
                    header("Location: index.php?error=" . urlencode('Account locked. Contact administration.'));
                    exit();
                }

                // Count recent failed attempts
                $tech_uid    = (int)$row['id'];
                $recent_fails = (int)(dbVal($conn,
                    "SELECT COUNT(*) FROM lab_audit_trail
                     WHERE technician_id = (SELECT id FROM lab_technicians WHERE user_id=? LIMIT 1)
                       AND action_type = 'login_failed'
                       AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                    "i", [$tech_uid]) ?? 0);

                if ($recent_fails >= 5) {
                    enforceBruteForceLockout($conn, $tech_uid, 5, 15);
                    header("Location: index.php?error=" . urlencode('Account locked after too many failed attempts. Administration has been notified.'));
                    exit();
                }
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
                // Start session
                $sessionManager = new SessionManager($conn);
                $sessionManager->startSession($row['id'], $role);

                $_SESSION['user_id']   = $row['id'];
                $_SESSION['user_name'] = $row['user_name'];
                $_SESSION['name']      = $row['name'];
                $_SESSION['role']      = $role;
                $_SESSION['user_role'] = $role;

                // Log successful login for lab technicians
                if ($role === 'lab_technician') {
                    if (!function_exists('logLabActivity')) require_once __DIR__ . '/dashboards/lab_security.php';
                    $tp = dbVal($conn, "SELECT id FROM lab_technicians WHERE user_id=? LIMIT 1", "i", [(int)$row['id']]);
                    if ($tp) logLabActivity($conn, (int)$tp, 'login_success', 'security', null);
                }

                // ── Staff sub-role approval gate ───────────────────────
                $STAFF_SUB_ROLES = ['ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff'];
                if (in_array($role, $STAFF_SUB_ROLES)) {
                    // Load staff_security helpers (dbVal etc.) if not already loaded
                    if (!function_exists('dbVal')) require_once __DIR__ . '/dashboards/staff_security.php';

                    $approval = dbVal($conn,
                        "SELECT approval_status FROM staff WHERE user_id = ? LIMIT 1",
                        "i", [(int)$row['id']]
                    );

                    if ($approval === null || $approval === 'pending') {
                        // Log attempt in audit trail
                        $deniedName = trim($row['name'] . ' (' . $row['user_name'] . ')');
                        @mysqli_query($conn, "INSERT INTO staff_audit_trail (user_id,action_type,module,description,created_at)
                            VALUES ({$row['id']},'login_blocked','security','Account pending admin approval',NOW())");
                        header("Location: index.php?error=" . urlencode("Your account is pending admin approval. You will be notified once approved."));
                        exit();
                    }

                    if ($approval === 'rejected') {
                        $reason = dbVal($conn,
                            "SELECT COALESCE(rejection_reason,'Contact administration for details.') FROM staff WHERE user_id = ? LIMIT 1",
                            "i", [(int)$row['id']]
                        );
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
                    case 'lab_technician': header("Location: dashboards/lab_dashboard.php"); break;
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
                // Failed login — log for lab_technician, then re-check lockout
                if ($role === 'lab_technician' && isset($row['id'])) {
                    if (!function_exists('logLabActivity')) require_once __DIR__ . '/dashboards/lab_security.php';
                    $tp = dbVal($conn, "SELECT id FROM lab_technicians WHERE user_id=? LIMIT 1", "i", [(int)$row['id']]);
                    if ($tp) {
                        logLabActivity($conn, (int)$tp, 'login_failed', 'security', null);
                        enforceBruteForceLockout($conn, (int)$row['id'], 5, 15);
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