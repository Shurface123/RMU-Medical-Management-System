<?php
require_once 'php/db_conn.php';

$sql = "CREATE TABLE IF NOT EXISTS cleaning_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_type VARCHAR(100),
    ward_area VARCHAR(200),
    specific_room VARCHAR(200),
    floor_building VARCHAR(100),
    cleaning_type VARCHAR(100),
    contamination_level VARCHAR(50),
    hazard_flags JSON,
    dispatch_type ENUM('immediate', 'scheduled') DEFAULT 'immediate',
    scheduled_time DATETIME NULL,
    estimated_duration INT NULL,
    recurrence_pattern VARCHAR(100) NULL,
    checklist_template_id INT NULL,
    assigned_cleaner_id INT NULL,
    backup_cleaner_id INT NULL,
    supervisor_id INT NULL,
    priority VARCHAR(50) DEFAULT 'Routine',
    required_ppe JSON,
    special_instructions TEXT,
    reported_by INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Dispatched',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Successfully created table: cleaning_schedules\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

// Ensure the Wards table exists for the dropdown
$sql2 = "CREATE TABLE IF NOT EXISTS wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(200) NOT NULL,
    capacity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql2)) {
    echo "Successfully created table: wards\n";
    // Setup dummy wards if empty
    $res = mysqli_query($conn, "SELECT COUNT(*) FROM wards");
    if($res && mysqli_fetch_row($res)[0] == 0) {
        mysqli_query($conn, "INSERT INTO wards (ward_name, capacity) VALUES ('Emergency', 20), ('ICU', 15), ('Maternity', 30), ('Pediatrics', 25), ('General Ward A', 40), ('General Ward B', 40), ('Isolation', 10)");
        echo "Inserted dummy wards.\n";
    }
} else {
    echo "Error creating wards table: " . mysqli_error($conn) . "\n";
}
?>
