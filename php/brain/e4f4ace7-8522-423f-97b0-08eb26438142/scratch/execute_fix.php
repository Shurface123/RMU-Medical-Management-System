<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$user_id = 203;

// 1. Fetch user info
$res = mysqli_query($conn, "SELECT name, gender, phone, email FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($res);
if (!$user) die("User ID $user_id not found.");

echo "Found user: " . $user['name'] . "\n";

// 2. Clear status in users table
$upd_user = "UPDATE users SET status='active', account_status='active', is_active=1, is_verified=1 WHERE id=$user_id";
if (mysqli_query($conn, $upd_user)) {
    echo "Updated users table for ID $user_id.\n";
} else {
    echo "Error updating users: " . mysqli_error($conn) . "\n";
}

// 3. Create pharmacist_profile
$pid = 'PHM-' . strtoupper(bin2hex(random_bytes(3)));
$full_name = $user['name'];
$gender = $user['gender'];
$phone = $user['phone'];
$email = $user['email'];

$check_profile = mysqli_query($conn, "SELECT id, full_name FROM pharmacist_profile WHERE user_id=$user_id");
if (mysqli_num_rows($check_profile) == 0) {
    $ins_profile = "INSERT INTO pharmacist_profile 
        (user_id, pharmacy_staff_id, full_name, gender, phone, email, approval_status, approved_at, availability_status, created_at) 
        VALUES 
        ($user_id, '$pid', '$full_name', '$gender', '$phone', '$email', 'approved', NOW(), 'Offline', NOW())";
    
    if (mysqli_query($conn, $ins_profile)) {
        echo "Created pharmacist_profile for ID $user_id with Staff ID $pid.\n";
    } else {
        echo "Error creating profile: " . mysqli_error($conn) . "\n";
    }
} else {
    $row = mysqli_fetch_assoc($check_profile);
    if (empty($row['full_name'])) {
        $upd_profile = "UPDATE pharmacist_profile SET 
            full_name = '$full_name', 
            gender = '$gender', 
            phone = '$phone', 
            email = '$email',
            approval_status = 'approved',
            approved_at = NOW()
            WHERE user_id = $user_id";
        if (mysqli_query($conn, $upd_profile)) {
            echo "Updated existing blank pharmacist_profile for ID $user_id.\n";
        } else {
            echo "Error updating existing profile: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Profile already exists and is not blank for ID $user_id.\n";
    }
}

?>
