<?php
/**
 * ADMIN LOGIN DIAGNOSTIC TOOL
 * This script will check the admin credentials and help identify login issues
 */

require_once 'db_conn.php';

echo "=== ADMIN LOGIN DIAGNOSTIC TOOL ===\n\n";

// Check if admin user exists
echo "STEP 1: Checking for admin users...\n";
$query = "SELECT id, user_name, name, email, user_role, password FROM users WHERE user_role = 'admin'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "ERROR: Could not query database: " . mysqli_error($conn) . "\n";
    exit;
}

$admin_count = mysqli_num_rows($result);
echo "Found $admin_count admin user(s)\n\n";

if ($admin_count == 0) {
    echo "WARNING: No admin users found in database!\n";
    echo "You need to create an admin account.\n\n";
    
    echo "Would you like to create an admin account? (This will be done via the fix script)\n";
    exit;
}

// Display admin users
echo "STEP 2: Admin user details:\n";
echo str_repeat("-", 80) . "\n";

$admin_users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $admin_users[] = $row;
    
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['user_name'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    
    // Analyze password hash
    $password_hash = $row['password'];
    $hash_length = strlen($password_hash);
    
    echo "Password Hash Length: " . $hash_length . " characters\n";
    
    if ($hash_length == 32) {
        echo "Password Type: MD5 (Legacy)\n";
        echo "Hash Format: ✓ Valid MD5 format\n";
    } elseif ($hash_length == 60 && substr($password_hash, 0, 4) == '$2y$') {
        echo "Password Type: BCrypt (Modern)\n";
        echo "Hash Format: ✓ Valid BCrypt format\n";
    } elseif ($hash_length == 60 && substr($password_hash, 0, 4) == '$2a$') {
        echo "Password Type: BCrypt (Alternative)\n";
        echo "Hash Format: ✓ Valid BCrypt format\n";
    } else {
        echo "Password Type: UNKNOWN\n";
        echo "Hash Format: ⚠ Unrecognized format\n";
        echo "First 10 chars: " . substr($password_hash, 0, 10) . "...\n";
    }
    
    echo str_repeat("-", 80) . "\n";
}

// Test with common admin passwords
echo "\nSTEP 3: Testing common admin passwords...\n";
$test_passwords = ['admin', 'admin123', 'password', 'Admin123', '12345678'];

foreach ($admin_users as $user) {
    echo "\nTesting user: " . $user['user_name'] . "\n";
    
    $password_hash = $user['password'];
    $found_match = false;
    
    foreach ($test_passwords as $test_pass) {
        // Test BCrypt
        if (password_verify($test_pass, $password_hash)) {
            echo "  ✓ PASSWORD FOUND: '$test_pass' (BCrypt verified)\n";
            $found_match = true;
            break;
        }
        
        // Test MD5
        if (md5($test_pass) === $password_hash) {
            echo "  ✓ PASSWORD FOUND: '$test_pass' (MD5 verified)\n";
            $found_match = true;
            break;
        }
    }
    
    if (!$found_match) {
        echo "  ✗ None of the common passwords matched\n";
        echo "  → You'll need to reset the password using the fix script\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "DIAGNOSIS COMPLETE\n\n";

echo "RECOMMENDATIONS:\n";
echo "1. If password was found above, try logging in with that password\n";
echo "2. If password was NOT found, run fix_admin_login.php to reset it\n";
echo "3. Make sure you're selecting 'admin' as the role when logging in\n";
echo "4. Check that the account status is 'active' if status column exists\n";
