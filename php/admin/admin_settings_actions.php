<?php
/**
 * RMU Medical Sickbay — Admin Settings Actions Handler
 * Backend for System Settings v2.0
 */
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// 1. SECURITY GATE: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// 2. CSRF PROTECTION
$headers = apache_request_headers();
$csrf_token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    // http_response_code(419);
    // echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
    // exit();
}

header('Content-Type: application/json');
$auditLogger = new AuditLogger($conn);
$user_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save_hospital_profile':
        $name = $_POST['hospital_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $website = $_POST['website'] ?? '';
        $acc_num = $_POST['accreditation_number'] ?? '';
        $lic_num = $_POST['license_number'] ?? '';
        $fac_type = $_POST['facility_type'] ?? '';
        
        // Handle Logo Upload
        $logo_path = $_POST['current_logo'] ?? '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "../../uploads/branding/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo_name = "hospital_logo_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $logo_name)) {
                $logo_path = "uploads/branding/" . $logo_name;
            }
        }

        // Using INSERT ... ON DUPLICATE KEY UPDATE for single-row settings table
        $query = "INSERT INTO hospital_settings (id, hospital_name, logo_path, address, email, website, accreditation_number, license_number, facility_type, updated_by) 
                  VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  hospital_name=?, logo_path=?, address=?, email=?, website=?, accreditation_number=?, license_number=?, facility_type=?, updated_by=?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssisssssssssi", 
            $name, $logo_path, $address, $email, $website, $acc_num, $lic_num, $fac_type, $user_id,
            $name, $logo_path, $address, $email, $website, $acc_num, $lic_num, $fac_type, $user_id
        );
        
        if ($stmt->execute()) {
            $auditLogger->log($user_id, 'update', 'hospital_settings', 1, null, 'Updated hospital profile');
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!', 'logo' => $logo_path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        break;

    case 'save_sys_appearance':
        $keys = ['site_name', 'date_format', 'time_format', 'currency_symbol', 'language_default', 'maintenance_mode'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $q = "INSERT INTO system_config (config_key, config_value, updated_by) VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE config_value=?, updated_by=?, updated_at=NOW()";
                $st = $conn->prepare($q);
                $st->bind_param("ssisi", $key, $val, $user_id, $val, $user_id);
                $st->execute();
            }
        }
        $auditLogger->log($user_id, 'config_update', 'system_config', null, null, 'Updated appearance settings');
        echo json_encode(['success' => true, 'message' => 'Appearance settings saved.']);
        break;

    case 'save_security_policy':
        $keys = ['password_min_length', 'password_require_special', 'session_timeout_admin', 'session_timeout_doctor', 'max_login_attempts', 'lockout_duration'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $q = "INSERT INTO system_config (config_key, config_value, updated_by) VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE config_value=?, updated_by=?, updated_at=NOW()";
                $st = $conn->prepare($q);
                $st->bind_param("ssisi", $key, $val, $user_id, $val, $user_id);
                $st->execute();
            }
        }
        $auditLogger->log($user_id, 'config_update', 'system_config', null, null, 'Updated security policies');
        echo json_encode(['success' => true, 'message' => 'Security policies updated.']);
        break;

    case 'add_ip_whitelist':
        $ip = $_POST['ip_address'] ?? '';
        $label = $_POST['label'] ?? '';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $q = "INSERT INTO ip_whitelist (ip_address, label, added_by) VALUES (?, ?, ?)";
            $st = $conn->prepare($q);
            $st->bind_param("ssi", $ip, $label, $user_id);
            if ($st->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'IP already exists or database error.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid IP address format.']);
        }
        break;

    case 'toggle_maintenance':
        $val = ($_POST['status'] == '1') ? '1' : '0';
        $q = "INSERT INTO system_config (config_key, config_value, updated_by) VALUES ('maintenance_mode', ?, ?) 
              ON DUPLICATE KEY UPDATE config_value=?, updated_by=?, updated_at=NOW()";
        $st = $conn->prepare($q);
        $st->bind_param("sisi", $val, $user_id, $val, $user_id);
        if ($st->execute()) {
            $msg = ($val == '1') ? 'System is now in Maintenance Mode.' : 'Maintenance Mode disabled.';
            $auditLogger->log($user_id, 'maintenance_toggle', 'system_config', null, null, $msg);
            echo json_encode(['success' => true, 'message' => $msg]);
        }
        break;

    case 'add_department':
        $name = $_POST['name'] ?? '';
        $desc = $_POST['description'] ?? '';
        $q = "INSERT INTO departments (name, description, head_doctor_id, is_active) VALUES (?, ?, NULL, 1)";
        $st = $conn->prepare($q);
        $st->bind_param("ss", $name, $desc);
        if ($st->execute()) {
            echo json_encode(['success' => true, 'message' => 'Department added successfully.']);
        }
        break;

    case 'add_ward':
        $name = $_POST['ward_name'] ?? '';
        $dept = $_POST['department_id'] ?? '';
        $cap = $_POST['capacity'] ?? 0;
        $q = "INSERT INTO wards (ward_name, department_id, capacity, status) VALUES (?, ?, ?, 'Active')";
        $st = $conn->prepare($q);
        $st->bind_param("sii", $name, $dept, $cap);
        if ($st->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ward created successfully.']);
        }
        break;

    case 'save_vital_thresholds':
        $vitals = $_POST['vitals'] ?? []; // Array of vital_id => {min, max, etc}
        foreach ($vitals as $id => $data) {
            $q = "UPDATE vital_thresholds SET min_normal=?, max_normal=?, critical_low=?, critical_high=?, updated_by=? WHERE id=?";
            $st = $conn->prepare($q);
            $st->bind_param("ddddii", $data['min'], $data['max'], $data['low'], $data['high'], $user_id, $id);
            $st->execute();
        }
        echo json_encode(['success' => true, 'message' => 'Vital thresholds updated.']);
        break;

    case 'add_medication':
        $name = $_POST['name'] ?? '';
        $cat  = $_POST['category'] ?? '';
        $ctrl = isset($_POST['is_controlled']) ? 1 : 0;
        $q = "INSERT INTO medicines (medicine_name, category, is_controlled, status) VALUES (?, ?, ?, 'active')";
        $st = $conn->prepare($q);
        $st->bind_param("ssi", $name, $cat, $ctrl);
        if ($st->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medication added to formulary.']);
        }
        break;

    case 'create_user':
        $uname = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $role  = $_POST['role'] ?? '';
        $name  = $_POST['fullname'] ?? '';
        $pass  = password_hash($_POST['password'] ?? 'Sickbay@2026', PASSWORD_DEFAULT);
        
        if (empty($uname) || empty($email) || empty($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'Username, Email and Password are required.']);
            exit();
        }

        // Check uniqueness
        $check = $conn->prepare("SELECT id FROM users WHERE user_name = ? OR email = ?");
        $check->bind_param("ss", $uname, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or Email already taken.']);
            exit();
        }

        $q = "INSERT INTO users (user_name, email, password, user_role, name, is_active, is_verified) 
              VALUES (?, ?, ?, ?, ?, 1, 1)";
        $st = $conn->prepare($q);
        $st->bind_param("sssss", $uname, $email, $pass, $role, $name);
        if ($st->execute()) {
            echo json_encode(['success' => true, 'message' => 'User account created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        break;

    case 'import_users_csv':
        if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            $header = fgetcsv($handle); // Skip header: name,email,role,username
            $count = 0;
            while (($data = fgetcsv($handle)) !== FALSE) {
                $name = $data[0]; $email = $data[1]; $role = $data[2]; $uname = $data[3];
                $pass = password_hash('Welcome@RMU', PASSWORD_DEFAULT);
                $q = "INSERT IGNORE INTO users (user_name, email, password, user_role, name, is_active, is_verified) 
                      VALUES (?, ?, ?, ?, ?, 1, 1)";
                $st = $conn->prepare($q);
                $st->bind_param("sssss", $uname, $email, $pass, $role, $name);
                if($st->execute()) $count++;
            }
            fclose($handle);
            echo json_encode(['success' => true, 'message' => "Imported $count users successfully."]);
        }
        break;

    case 'save_shift_types':
        $shifts = $_POST['shifts'] ?? [];
        foreach($shifts as $id => $s) {
            $q = "UPDATE shift_types SET start_time=?, end_time=?, color_code=? WHERE id=?";
            $st = $conn->prepare($q);
            $st->bind_param("sssi", $s['start'], $s['end'], $s['color'], $id);
            $st->execute();
        }
        echo json_encode(['success' => true, 'message' => 'Shift configurations updated.']);
        break;

    case 'save_smtp_config':
        $host = $_POST['smtp_host'] ?? '';
        $port = $_POST['smtp_port'] ?? '587';
        $user = $_POST['smtp_username'] ?? '';
        $pass = $_POST['smtp_password'] ?? '';
        $from = $_POST['from_email'] ?? '';
        $name = $_POST['from_name'] ?? '';
        $enc  = $_POST['encryption'] ?? 'tls';

        if ($pass !== '') {
            $q = "INSERT INTO system_email_config (id, smtp_host, smtp_port, smtp_username, smtp_password, encryption, from_email, from_name, is_active) 
                  VALUES (1, ?, ?, ?, AES_ENCRYPT(?, SHA2('RMU_SICKBAY_2025_SECRET',256)), ?, ?, ?, 1) 
                  ON DUPLICATE KEY UPDATE smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=AES_ENCRYPT(?, SHA2('RMU_SICKBAY_2025_SECRET',256)), encryption=?, from_email=?, from_name=?";
            $st = $conn->prepare($q);
            $st->bind_param("sissssssissssss", $host, $port, $user, $pass, $enc, $from, $name, $host, $port, $user, $pass, $enc, $from, $name);
        } else {
            // Keep existing password if blank
            $q = "UPDATE system_email_config SET smtp_host=?, smtp_port=?, smtp_username=?, encryption=?, from_email=?, from_name=? WHERE id=1";
            $st = $conn->prepare($q);
            $st->bind_param("sissss", $host, $port, $user, $enc, $from, $name);
        }
        
        if ($st->execute()) {
            $auditLogger->log($user_id, 'config_update', 'system_email_config', 1, null, 'Updated SMTP configurations');
            echo json_encode(['success' => true, 'message' => 'SMTP Configuration saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save SMTP settings.']);
        }
        break;

    case 'save_recaptcha_config':
        $keys = ['recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_score_threshold'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $q = "INSERT INTO system_config (config_key, config_value, updated_by) VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE config_value=?, updated_by=?, updated_at=NOW()";
                $st = $conn->prepare($q);
                $st->bind_param("ssisi", $key, $val, $user_id, $val, $user_id);
                $st->execute();
            }
        }
        $auditLogger->log($user_id, 'config_update', 'system_config', null, null, 'Updated reCAPTCHA configurations');
        echo json_encode(['success' => true, 'message' => 'reCAPTCHA Configuration saved successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        break;
}
