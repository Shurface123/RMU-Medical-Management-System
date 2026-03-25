<?php
require_once dirname(__DIR__, 2) . '/db_conn.php';

function columnExists($conn, $table, $column) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($res) > 0;
}

$queries = [];
if (!columnExists($conn, 'users', 'two_factor_secret')) {
    $queries[] = "ALTER TABLE users ADD COLUMN two_factor_secret varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER password";
}
if (!columnExists($conn, 'users', 'two_factor_enabled')) {
    $queries[] = "ALTER TABLE users ADD COLUMN two_factor_enabled tinyint(1) NOT NULL DEFAULT 0 AFTER two_factor_secret";
}
if (!columnExists($conn, 'users', 'profile_photo')) {
    $queries[] = "ALTER TABLE users ADD COLUMN profile_photo varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png' AFTER two_factor_enabled";
}
if (!columnExists($conn, 'users', 'emergency_contact_name')) {
    $queries[] = "ALTER TABLE users ADD COLUMN emergency_contact_name varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER profile_photo";
}
if (!columnExists($conn, 'users', 'emergency_contact_phone')) {
    $queries[] = "ALTER TABLE users ADD COLUMN emergency_contact_phone varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER emergency_contact_name";
}

$success = 0;
$errors = 0;
foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        $success++;
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
        $errors++;
    }
}
echo "Patch complete. Success: $success, Errors: $errors\n";
