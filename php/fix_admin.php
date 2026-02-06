<?php
// Quick script to verify and fix admin user
require_once 'db_conn.php';

echo "<h2>Database Admin User Check & Fix</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }</style>";

// First, check if admin user exists
$check_sql = "SELECT id, user_name, email, user_role, name FROM users WHERE user_name = 'admin' LIMIT 1";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_assoc($result);
    echo "<h3 style='color: green;'>✓ Admin user found!</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // Verify the password
    $verify_sql = "SELECT password FROM users WHERE user_name = 'admin'";
    $pass_result = mysqli_query($conn, $verify_sql);
    $pass_row = mysqli_fetch_assoc($pass_result);
    
    echo "<h3>Password Verification:</h3>";
    echo "<pre>";
    echo "Testing password: admin123\n";
    echo "Password hash in DB: " . substr($pass_row['password'], 0, 30) . "...\n";
    echo "BCrypt verify result: " . (password_verify('admin123', $pass_row['password']) ? 'PASS ✓' : 'FAIL ✗') . "\n";
    echo "MD5 verify result: " . (md5('admin123') === $pass_row['password'] ? 'PASS ✓' : 'FAIL ✗') . "\n";
    echo "</pre>";
    
} else {
    echo "<h3 style='color: orange;'>⚠ No admin user found. Creating one...</h3>";
    
    // Create admin user with correct column name
    $username = 'admin';
    $email = 'admin@rmu.edu.gh';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $user_role = 'admin';  // Note: using user_role, not role
    $name = 'System Administrator';
    $phone = '0502371207';
    $gender = 'Male';
    
    $insert_sql = "INSERT INTO users (user_name, email, password, user_role, name, phone, gender, is_active, is_verified) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $username, $email, $password, $user_role, $name, $phone, $gender);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<h3 style='color: green;'>✓ Admin user created successfully!</h3>";
        echo "<pre>";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Role: admin\n";
        echo "Email: admin@rmu.edu.gh\n";
        echo "</pre>";
    } else {
        echo "<h3 style='color: red;'>✗ Error creating admin user:</h3>";
        echo "<pre>" . mysqli_error($conn) . "</pre>";
    }
    mysqli_stmt_close($stmt);
}

echo "<hr>";
echo "<h3>Login Instructions:</h3>";
echo "<ol>";
echo "<li>Go to: <a href='index.php'>index.php</a></li>";
echo "<li>Username: <strong>admin</strong></li>";
echo "<li>Password: <strong>admin123</strong></li>";
echo "<li>Role: Select <strong>Administrator</strong> from dropdown (value = 'admin')</li>";
echo "</ol>";

mysqli_close($conn);
?>
