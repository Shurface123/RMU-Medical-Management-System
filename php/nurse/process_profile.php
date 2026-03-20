<?php
// ============================================================
// PROCESS NURSE PROFILE & SECURITY (AJAX Endpoint)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_user_id = enforceNurseRole(); 
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

verifyCsrfToken($_POST['_csrf'] ?? '');
$action = sanitize($_POST['action'] ?? '');

// Primary Keys
$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_user_id LIMIT 1", "i", []);

if ($action === 'update_personal') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $dob       = sanitize($_POST['date_of_birth'] ?? '');
    $gender    = sanitize($_POST['gender'] ?? 'Female');
    $nat       = sanitize($_POST['nationality'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $addr      = sanitize($_POST['address'] ?? '');

    if (empty($full_name) || empty($dob)) {
        echo json_encode(['success' => false, 'message' => 'Full Name and Date of Birth are mandatory fields.']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE nurses SET full_name=?, date_of_birth=?, gender=?, nationality=?, phone=?, email=?, address=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssssssi", $full_name, $dob, $gender, $nat, $phone, $email, $addr, $nurse_pk);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Updated personal profile information", "profile");
        echo json_encode(['success' => true, 'message' => 'Personal information updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating profile.']);
    }
    exit;

} elseif ($action === 'update_professional') {
    $lic_num   = sanitize($_POST['license_number'] ?? '');
    $lic_exp   = sanitize($_POST['license_expiry'] ?? '');
    $desig     = sanitize($_POST['designation'] ?? '');
    $spec      = sanitize($_POST['specialization'] ?? '');
    $yrs_exp   = validateInt($_POST['years_of_experience'] ?? 0);

    // Make empty dates NULL
    $lic_exp_val = empty($lic_exp) ? null : $lic_exp;

    $stmt = mysqli_prepare($conn, "UPDATE nurses SET license_number=?, license_expiry=?, designation=?, specialization=?, years_of_experience=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssii", $lic_num, $lic_exp_val, $desig, $spec, $yrs_exp, $nurse_pk);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Updated professional nursing credentials", "profile");
        echo json_encode(['success' => true, 'message' => 'Professional credentials updated successfully.']);
    } else {
        // Handle constraint duplicate (like license numbers must be UNIQUE)
        $err = mysqli_error($conn);
        if(strpos($err, 'Duplicate') !== false) {
             echo json_encode(['success' => false, 'message' => 'License number is already registered to another nurse.']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Database error updating credentials.']);
        }
    }
    exit;

} elseif ($action === 'update_password') {
    $curr_pass = $_POST['current_password'] ?? '';
    $new_pass  = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (empty($curr_pass) || empty($new_pass)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        exit;
    }

    if ($new_pass !== $conf_pass) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit;
    }

    if (strlen($new_pass) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
        exit;
    }

    // Verify current
    $hash = dbVal($conn, "SELECT password FROM users WHERE id=?", "i", [$nurse_user_id]);
    if (!$hash || !password_verify($curr_pass, $hash)) {
        secureLogNurse($conn, $nurse_pk, "Failed password update attempt (Invalid current password)", "security");
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Update
    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    dbExecute($conn, "UPDATE users SET password=? WHERE id=?", "si", [$new_hash, $nurse_user_id]);

    secureLogNurse($conn, $nurse_pk, "Successfully changed account password.", "security");
    echo json_encode(['success' => true, 'message' => 'Password secured successfully.']);
    exit;

} elseif ($action === 'upload_photo') {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image received or upload error.']);
        exit;
    }

    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG/PNG allowed.']);
        exit;
    }

    $upload_dir = dirname(__DIR__, 2) . '/uploads/profiles/';
    // Create dir if doesn't exist
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'nurse_' . $nurse_pk . '_' . time() . '.' . $ext;
    $target = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        
        // Delete old photo if not default
        $old_photo = dbVal($conn, "SELECT profile_photo FROM nurses WHERE id=?", "i", [$nurse_pk]);
        if ($old_photo && $old_photo !== 'default-avatar.png') {
            @unlink($upload_dir . $old_photo);
        }

        // Update DB
        dbExecute($conn, "UPDATE nurses SET profile_photo=? WHERE id=?", "si", [$new_filename, $nurse_pk]);
        secureLogNurse($conn, $nurse_pk, "Updated profile photo image.", "profile");

        echo json_encode(['success' => true, 'message' => 'Photo uploaded successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'File transfer failed on server.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
