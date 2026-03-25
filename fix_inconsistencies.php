<?php
require_once 'php/db_conn.php';

$tables = [
    'lab_technicians' => 'Lab Technician',
    'nurses'          => 'Nurse',
    'staff'           => 'Staff'
];

foreach ($tables as $table => $label) {
    echo "--- Checking $label ---\n";
    $q = "SELECT t.user_id, u.user_name, u.is_active 
          FROM $table t
          JOIN users u ON t.user_id = u.id 
          WHERE t.approval_status = 'approved' AND (u.is_active = 0 OR u.is_verified = 0)";
    $res = $conn->query($q);
    
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $uid = $row['user_id'];
            echo "Repairing account for {$row['user_name']} (UID: $uid)\n";
            $conn->query("UPDATE users SET is_active = 1, is_verified = 1 WHERE id = $uid");
        }
    } else {
        echo "No inconsistencies found for $label.\n";
    }
}
?>
