<?php
require_once dirname(__DIR__) . '/db_conn.php';

$queries = [
    "ALTER TABLE doctors
     ADD COLUMN approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
     ADD COLUMN approved_by INT NULL,
     ADD COLUMN approved_at DATETIME NULL,
     ADD COLUMN rejection_reason TEXT NULL",
    "ALTER TABLE pharmacist_profile
     ADD COLUMN approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
     ADD COLUMN approved_by INT NULL,
     ADD COLUMN approved_at DATETIME NULL,
     ADD COLUMN rejection_reason TEXT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on $q\n";
    }
}
