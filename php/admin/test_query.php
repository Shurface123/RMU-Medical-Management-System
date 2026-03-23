<?php
require_once '../db_conn.php';

$query = "
    SELECT s.id as staff_id, s.employee_id, s.role, u.name, u.email, u.phone, u.created_at, CAST('staff' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM staff s
    JOIN users u ON s.user_id = u.id
    WHERE s.approval_status = 'pending'
    
    UNION ALL
    
    SELECT n.id as staff_id, n.nurse_id as employee_id, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM nurses n
    JOIN users u ON n.user_id = u.id
    WHERE n.approval_status = 'pending'
    
    UNION ALL
    
    SELECT lt.id as staff_id, lt.technician_id as employee_id, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM lab_technicians lt
    JOIN users u ON lt.user_id = u.id
    WHERE lt.approval_status = 'pending'
    
    ORDER BY created_at DESC
";

echo "Running query...\n";
if (mysqli_query($conn, $query)) {
    echo "Success!\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
    
    // Check connection collation
    $res = mysqli_query($conn, "SHOW VARIABLES LIKE 'collation_connection'");
    $row = mysqli_fetch_assoc($res);
    echo "Connection Collation: " . $row['Value'] . "\n";
}
