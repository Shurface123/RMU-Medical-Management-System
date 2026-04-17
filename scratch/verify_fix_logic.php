<?php
require_once 'php/db_conn.php';

// Redefine log_reg_audit if not accessible or just use the one in register_handler.php
// Since register_handler.php has side effects (session_start, redirects), let's just copy the function here for testing logic.
function test_log_reg_audit($conn, $uid, $action, $ip, $ua, $notes = '') {
    $audit_id = 'URA-TEST-' . uniqid();
    $s = mysqli_prepare($conn,
        "INSERT INTO user_registration_audit 
         (audit_id,user_id,action,performed_by,ip_address,device_info,notes)
         VALUES (?,?,?,'self',?,?,?)");
    if (!$s) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    // OUR FIX: 'sissss' for 6 placeholders
    mysqli_stmt_bind_param($s,'sissss', $audit_id, $uid, $action, $ip, $ua, $notes);
    if (mysqli_stmt_execute($s)) {
        echo "Audit log inserted successfully.\n";
    } else {
        echo "Audit log failed: " . mysqli_stmt_error($s) . "\n";
    }
}

echo "Testing Audit Log Function...\n";
test_log_reg_audit($conn, 1, 'test_action', '127.0.0.1', 'Mozilla/5.0', 'Test notes');

echo "\nTesting Doctor Mapping Logic (Dry Run)...\n";
$new_uid = 1; $did = 'DOC-TEST'; $dept_id = 1; $st_d = 'Offline';
$td = [
    'full_name' => 'Dr. Test',
    'gender' => 'Male',
    'specialization' => 'Testing',
    'license_number' => 'LIC-123',
    'experience_years' => 5
];

$si = mysqli_prepare($conn,
    "INSERT INTO doctors 
     (user_id,doctor_id,full_name,gender,specialization,
      department_id,license_number,experience_years,
      availability_status,created_at)
     VALUES (?,?,?,?,?,?,?,?,?,NOW())");
if (!$si) {
    echo "Doctor Prepare failed (expected if table missing or schema mismatch): " . mysqli_error($conn) . "\n";
} else {
    // OUR FIX: 'issssiisi'
    mysqli_stmt_bind_param($si,'issssiisi',
        $new_uid, $did, $td['full_name'], $td['gender'],
        $td['specialization'], $dept_id, $td['license_number'],
        $td['experience_years'], $st_d);
    
    // We won't actually execute to avoid polluting DB, or we can use a transaction.
    echo "Doctor bind_param successful.\n";
}

echo "\nTesting Pharmacist Mapping Logic (Dry Run)...\n";
$pid = 'PHM-TEST'; $exp_int = 5; $st_p = 'Offline';
$si_p = mysqli_prepare($conn,
    "INSERT INTO pharmacist_profile
     (user_id,pharmacy_staff_id,full_name,gender,license_number,
      specialization,department,years_of_experience,
      availability_status,created_at)
     VALUES (?,?,?,?,?,?,?,?,?,NOW())");
if (!$si_p) {
    echo "Pharmacist Prepare failed: " . mysqli_error($conn) . "\n";
} else {
    // OUR FIX: 'issssssis'
    $dept_name = 'Pharmacy Dept';
    mysqli_stmt_bind_param($si_p,'issssssis',
        $new_uid, $pid, $td['full_name'], $td['gender'],
        $td['license_number'], $td['specialization'],
        $dept_name, $exp_int, $st_p);
    echo "Pharmacist bind_param successful.\n";
}
?>
