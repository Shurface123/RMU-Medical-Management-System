<?php
require_once '../db_conn.php';

echo "Testing Kitchen query...\n";
$res1 = mysqli_query($conn, "SELECT id, full_name, patient_id, ward_department, allergies, assigned_doctor FROM patients WHERE registration_status='Active'");
if ($res1) echo "Kitchen query successful.\n";
else echo "Kitchen query failed: " . mysqli_error($conn) . "\n";

echo "Testing Cleaning query...\n";
$res2 = mysqli_query($conn, "
    SELECT cs.*, u1.name as primary_cleaner, u2.name as backup_cleaner
    FROM cleaning_schedules cs
    LEFT JOIN users u1 ON cs.assigned_cleaner_id = u1.id
    LEFT JOIN users u2 ON cs.backup_cleaner_id = u2.id
    LIMIT 1
");
if ($res2) echo "Cleaning query successful.\n";
else echo "Cleaning query failed: " . mysqli_error($conn) . "\n";
