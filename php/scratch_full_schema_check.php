<?php
require_once __DIR__ . '/db_conn.php';

$tables = [
    'staff_notifications',
    'staff_messages',
    'staff_tasks',
    'staff_shifts',
    'maintenance_requests',
    'ambulance_requests',
    'ambulance_trips',
    'cleaning_schedules',
    'cleaning_logs',
    'laundry_batches',
    'visitor_logs',
    'kitchen_tasks'
];

foreach ($tables as $t) {
    echo "\n--- $t ---\n";
    $res = mysqli_query($conn, "DESC $t");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "MISSING!\n";
    }
}
?>
