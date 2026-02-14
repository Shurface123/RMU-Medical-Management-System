<?php
/**
 * ADMIN LOGIN FIX TOOL
 * This script will fix admin login issues by resetting credentials
 */

require_once 'db_conn.php';

echo "=== ADMIN LOGIN FIX TOOL ===\n\n";

// Configuration
$default_username = 'admin';
$default_password = 'admin123';
$default_name = 'System Administrator';
$default_email = 'admin@rmu.edu.gh';

echo "This tool will help you fix admin login issues.\n\n";

// Check for existing admin
$check_query = "SELECT id, user_name, name, email, user_role FROM users WHERE user_role = 'admin'";
$result = mysqli_query($conn, $check_query);

if (!$result) {
    echo "ERROR: Could not query database: " . mysqli_error($conn) . "\n";
    exit;
}

$admin_count = mysqli_num_rows($result);

if ($admin_count > 0) {
    echo "Found $admin_count existing admin user(s):\n";
    echo str_repeat("-", 80) . "\n";
    
    $admins = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $admins[] = $row;
        echo "ID: " . $row['id'] . " | Username: " . $row['user_name'] . " | Name: " . $row['name'] . "\n";
    }
    echo str_repeat("-", 80) . "\n\n";
    
    echo "OPTIONS:\n";
    echo "1. Reset password for existing admin\n";
    echo "2. Create new admin account\n";
    echo "3. Delete old admin and create new one\n\n";
    
    // For automation, let's reset the first admin's password
    $admin_to_fix = $admins[0];
    $admin_id = $admin_to_fix['id'];
    $admin_username = $admin_to_fix['user_name'];
    
    echo "SELECTED ACTION: Reset password for '$admin_username' (ID: $admin_id)\n";
    echo "New password will be: $default_password\n\n";
    
    // Generate BCrypt hash
    $new_hash = password_hash($default_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_hash, $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✓ SUCCESS: Password has been reset!\n\n";
        
        echo "=== NEW ADMIN CREDENTIALS ===\n";
        echo "Username: $admin_username\n";
        echo "Password: $default_password\n";
        echo "Role: admin\n";
        echo str_repeat("=", 40) . "\n\n";
        
        echo "You can now login with these credentials.\n";
        echo "IMPORTANT: Change this password after first login!\n";
        
    } else {
        echo "✗ ERROR: Failed to update password: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
    
} else {
    echo "No admin users found. Creating new admin account...\n\n";
    
    // Create new admin
    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO users (user_name, name, email, password, user_role) VALUES (?, ?, ?, ?, 'admin')";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if (!$stmt) {
        echo "ERROR: Could not prepare statement: " . mysqli_error($conn) . "\n";
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ssss", $default_username, $default_name, $default_email, $password_hash);
    
    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        echo "✓ SUCCESS: Admin account created!\n\n";
        
        echo "=== NEW ADMIN CREDENTIALS ===\n";
        echo "ID: $new_id\n";
        echo "Username: $default_username\n";
        echo "Password: $default_password\n";
        echo "Name: $default_name\n";
        echo "Email: $default_email\n";
        echo "Role: admin\n";
        echo str_repeat("=", 40) . "\n\n";
        
        echo "You can now login with these credentials.\n";
        echo "IMPORTANT: Change this password after first login!\n";
        
    } else {
        echo "✗ ERROR: Failed to create admin: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

// Verify the fix
echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICATION:\n";

$verify_query = "SELECT user_name, password FROM users WHERE user_role = 'admin' LIMIT 1";
$verify_result = mysqli_query($conn, $verify_query);

if ($verify_result && mysqli_num_rows($verify_result) > 0) {
    $verify_row = mysqli_fetch_assoc($verify_result);
    
    echo "Testing password verification for: " . $verify_row['user_name'] . "\n";
    
    if (password_verify($default_password, $verify_row['password'])) {
        echo "✓ Password verification: PASSED\n";
        echo "✓ Login should work now!\n";
    } else {
        echo "✗ Password verification: FAILED\n";
        echo "There may still be an issue. Please run diagnose_admin.php for more details.\n";
    }
} else {
    echo "Could not verify - no admin found.\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "NEXT STEPS:\n";
echo "1. Try logging in at: /RMU-Medical-Management-System/index.php\n";
echo "2. Use the credentials shown above\n";
echo "3. Select 'admin' as the role\n";
echo "4. Change your password after successful login\n";
