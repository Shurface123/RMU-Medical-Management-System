<?php
// Test script to verify admin credentials
require_once 'db_conn.php';

echo "<h2>Admin User Verification</h2>";

// Query for admin user
$sql = "SELECT id, user_name, email, user_role, password, name FROM users WHERE user_role = 'admin'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<h3>Admin users found:</h3>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<pre>";
        echo "ID: " . $row['id'] . "\n";
        echo "Username: " . $row['user_name'] . "\n";
        echo "Email: " . $row['email'] . "\n";
        echo "Role: " . $row['user_role'] . "\n";
        echo "Name: " . $row['name'] . "\n";
        echo "Password Hash: " . substr($row['password'], 0, 20) . "...\n";
        echo "Hash Type: ";
        
        // Check if it's MD5 (32 chars) or bcrypt (60 chars starting with $2y$)
        if (strlen($row['password']) == 32) {
            echo "MD5\n";
            echo "Test MD5('admin123'): " . md5('admin123') . "\n";
            echo "Match: " . (md5('admin123') === $row['password'] ? 'YES' : 'NO') . "\n";
        } else if (strpos($row['password'], '$2y$') === 0) {
            echo "BCrypt\n";
            echo "Test password_verify('admin123'): " . (password_verify('admin123', $row['password']) ? 'YES' : 'NO') . "\n";
        } else {
            echo "Unknown\n";
        }
        echo "</pre><hr>";
    }
} else {
    echo "<p style='color: red;'>No admin users found in database!</p>";
    echo "<p>Creating default admin user...</p>";
    
    // Create admin user
    $username = 'admin';
    $email = 'admin@rmu.edu.gh';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';
    $name = 'System Administrator';
    $phone = '0502371207';
    $gender = 'Male';
    
    $insert_sql = "INSERT INTO users (user_name, email, password, user_role, name, phone, gender, is_active, is_verified) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $username, $email, $password, $role, $name, $phone, $gender);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>Admin user created successfully!</p>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><strong>Role:</strong> admin</p>";
    } else {
        echo "<p style='color: red;'>Error creating admin user: " . mysqli_error($conn) . "</p>";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
