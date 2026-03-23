<?php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate all required fields
    $required_fields = ['fullname', 'email', 'phone', 'username', 'password', 'confirm_password', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            header("Location: register.php?error=All fields are required");
            exit();
        }
    }

    // Sanitize inputs
    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $fullname = validate($_POST['fullname']);
    $email = validate($_POST['email']);
    $phone = validate($_POST['phone']);
    $username = validate($_POST['username']);
    $password = validate($_POST['password']);
    $confirm_password = validate($_POST['confirm_password']);
    $role = validate($_POST['role']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=Invalid email format");
        exit();
    }

    // Validate password length
    if (strlen($password) < 8) {
        header("Location: register.php?error=Password must be at least 8 characters");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    // Validate role
    $valid_roles = [
        'patient', 'doctor', 'pharmacist', 'nurse', 'lab_technician',
        // All staff sub-roles (registered directly — no generic 'staff' login)
        'ambulance_driver', 'cleaner', 'laundry_staff', 'maintenance', 'security', 'kitchen_staff'
    ];
    if (!in_array($role, $valid_roles)) {
        header("Location: register.php?error=Invalid role selected");
        exit();
    }
    // Normalise: any staff sub-role maps to the 'staff' group
    $STAFF_SUB_ROLES = ['ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff'];
    $APPROVAL_ROLES  = ['doctor', 'pharmacist', 'nurse', 'lab_technician'];
    $needs_approval  = in_array($role, $APPROVAL_ROLES);
    $is_staff_role   = in_array($role, $STAFF_SUB_ROLES);

    // FIX: Define $is_active based on approval requirements
    $is_active = ($needs_approval || $is_staff_role) ? 0 : 1;

    // Check if username already exists
    $sql = "SELECT id FROM users WHERE user_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        header("Location: register.php?error=Username already exists");
        exit();
    }
    mysqli_stmt_close($stmt);

    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        header("Location: register.php?error=Email already registered");
        exit();
    }
    mysqli_stmt_close($stmt);

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Normalize role for users table enum
    $user_table_role = $role;
    if (in_array($role, $STAFF_SUB_ROLES)) {
        $user_table_role = 'staff';
    }

    // Insert new user
    $sql = "INSERT INTO users (name, email, phone, user_name, password, user_role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $fullname, $email, $phone, $username, $hashed_password, $user_table_role, $is_active);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        
        // Create role-specific record based on user type
        if ($role === 'patient') {
            // Generate unique patient_id
            $last_p_res = mysqli_query($conn, "SELECT COUNT(*) FROM patients");
            $last_p = mysqli_fetch_row($last_p_res)[0] ?? 0;
            $patient_id_val = 'PAT-' . str_pad($last_p + 1, 5, '0', STR_PAD_LEFT);

            $patient_sql = "INSERT INTO patients (user_id, patient_id, full_name, created_at) VALUES (?, ?, ?, NOW())";
            $patient_stmt = mysqli_prepare($conn, $patient_sql);
            mysqli_stmt_bind_param($patient_stmt, "iss", $user_id, $patient_id_val, $fullname);
            mysqli_stmt_execute($patient_stmt);
            mysqli_stmt_close($patient_stmt);
        } elseif ($role === 'doctor') {
            // Generate unique doctor_id
            $last_d_res = mysqli_query($conn, "SELECT COUNT(*) FROM doctors");
            $last_d = mysqli_fetch_row($last_d_res)[0] ?? 0;
            $doctor_id_val = 'DOC-' . str_pad($last_d + 1, 4, '0', STR_PAD_LEFT);

            $doctor_sql = "INSERT INTO doctors (user_id, doctor_id, specialization, full_name, created_at) VALUES (?, ?, '', ?, NOW())";
            $doctor_stmt = mysqli_prepare($conn, $doctor_sql);
            mysqli_stmt_bind_param($doctor_stmt, "iss", $user_id, $doctor_id_val, $fullname);
            mysqli_stmt_execute($doctor_stmt);
            mysqli_stmt_close($doctor_stmt);
        } elseif ($role === 'nurse') {
            // Generate unique nurse_id
            $last_n_res = mysqli_query($conn, "SELECT COUNT(*) FROM nurses");
            $last_n = ($last_n_res) ? (mysqli_fetch_row($last_n_res)[0] ?? 0) : 0;
            $nurse_id_val = 'NRS-' . str_pad($last_n + 1, 4, '0', STR_PAD_LEFT);

            $nurse_sql = "INSERT INTO nurses (user_id, nurse_id, full_name, email, phone, status, years_of_experience, created_at) VALUES (?, ?, ?, ?, ?, 'Active', 0, NOW())";
            $nurse_stmt = mysqli_prepare($conn, $nurse_sql);
            mysqli_stmt_bind_param($nurse_stmt, "issss", $user_id, $nurse_id_val, $fullname, $email, $phone);
            mysqli_stmt_execute($nurse_stmt);
            mysqli_stmt_close($nurse_stmt);
        } elseif ($role === 'lab_technician') {
            // Generate unique technician_id
            $last_lt_res = mysqli_query($conn, "SELECT COUNT(*) FROM lab_technicians");
            $last_lt = ($last_lt_res) ? (mysqli_fetch_row($last_lt_res)[0] ?? 0) : 0;
            $tech_id_val = 'LT-' . str_pad($last_lt + 1, 4, '0', STR_PAD_LEFT);

            $lt_sql = "INSERT INTO lab_technicians (user_id, technician_id, full_name, email, phone, status, approval_status, created_at) VALUES (?, ?, ?, ?, ?, 'Active', 'pending', NOW())";
            $lt_stmt = mysqli_prepare($conn, $lt_sql);
            mysqli_stmt_bind_param($lt_stmt, "issss", $user_id, $tech_id_val, $fullname, $email, $phone);
            mysqli_stmt_execute($lt_stmt);
            mysqli_stmt_close($lt_stmt);
        } elseif ($is_staff_role || ($needs_approval && !in_array($role, ['doctor', 'nurse', 'lab_technician']))) {
            // Generate unique employee_id — retry until unique
            $attempt = 0;
            do {
                $emp_id = 'STF-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $chk = mysqli_prepare($conn, "SELECT id FROM staff WHERE staff_id = ? LIMIT 1"); // Note: schema matches staff_id better
                // Actually staff table might have staff_id but maybe not employee_id in old schema
                // But register_handler.php used both. I'll stick to a safe set.
                mysqli_stmt_bind_param($chk, "s", $emp_id);
                mysqli_stmt_execute($chk);
                mysqli_stmt_store_result($chk);
                $emp_exists = mysqli_stmt_num_rows($chk) > 0;
                mysqli_stmt_close($chk);
                $attempt++;
            } while ($emp_exists && $attempt < 10);

            // Generate unique staff_id — satisfies the UNIQUE constraint on staff.staff_id
            $attempt2 = 0;
            do {
                $staff_id_val = 'STF' . strtoupper(substr(md5($user_id . microtime() . rand()), 0, 8));
                $chk2 = mysqli_prepare($conn, "SELECT id FROM staff WHERE staff_id = ? LIMIT 1");
                mysqli_stmt_bind_param($chk2, "s", $staff_id_val);
                mysqli_stmt_execute($chk2);
                mysqli_stmt_store_result($chk2);
                $sid_exists = mysqli_stmt_num_rows($chk2) > 0;
                mysqli_stmt_close($chk2);
                $attempt2++;
            } while ($sid_exists && $attempt2 < 10);

            // Insert staff record - removing full_name, email, phone as they belong in users
            $staff_sql = "INSERT INTO staff (user_id, staff_id, role, created_at, department, position)
                          VALUES (?, ?, ?, NOW(), 'General', 'Staff')";
            $staff_stmt = mysqli_prepare($conn, $staff_sql);
            mysqli_stmt_bind_param($staff_stmt, "iss", $user_id, $staff_id_val, $role);
            if (!mysqli_stmt_execute($staff_stmt)) {
                mysqli_stmt_close($staff_stmt);
                mysqli_stmt_close($stmt);
                header("Location: register.php?error=" . urlencode("Staff registration failed. Please try again."));
                exit();
            }
            mysqli_stmt_close($staff_stmt);
        }
        
        mysqli_stmt_close($stmt);

        // Redirect with appropriate message
        if ($is_staff_role || $needs_approval) {
            header("Location: index.php?info=" . urlencode("Registration successful! Your account is pending admin approval. You will be notified once approved."));
        } else {
            header("Location: index.php?success=Registration successful! Please login");
        }
        exit();
    } else {
        mysqli_stmt_close($stmt);
        header("Location: register.php?error=Registration failed. Please try again");
        exit();
    }

} else {
    header("Location: register.php");
    exit();
}
?>
