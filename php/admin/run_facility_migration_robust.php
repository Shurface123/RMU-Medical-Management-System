<?php
require_once '../db_conn.php';

function addColumn($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        echo "Adding column $column to $table...\n";
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "Success.\n";
        } else {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column $column already exists in $table.\n";
    }
}

function columnExists($conn, $table, $column) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($res) > 0;
}

// 1. Patients Table
addColumn($conn, 'patients', 'ward_department', "VARCHAR(100) DEFAULT NULL AFTER `admit_date` ");
addColumn($conn, 'patients', 'assigned_doctor', "INT DEFAULT NULL AFTER `ward_department` ");
// Add FK if not exists
// (Skipping complex FK check for now, mysqli_query will just error if already exists)
mysqli_query($conn, "ALTER TABLE `patients` ADD CONSTRAINT `fk_patient_assigned_doctor` FOREIGN KEY (`assigned_doctor`) REFERENCES `users`(`id`) ON DELETE SET NULL");

// 2. Cleaning Schedules Table
addColumn($conn, 'cleaning_schedules', 'assigned_cleaner_id', "INT DEFAULT NULL AFTER `assigned_to` ");
addColumn($conn, 'cleaning_schedules', 'backup_cleaner_id', "INT DEFAULT NULL AFTER `assigned_cleaner_id` ");
addColumn($conn, 'cleaning_schedules', 'scheduled_time', "DATETIME DEFAULT NULL AFTER `end_time` ");
addColumn($conn, 'cleaning_schedules', 'ward_area', "VARCHAR(100) DEFAULT NULL AFTER `ward_room_area` ");
addColumn($conn, 'cleaning_schedules', 'specific_room', "VARCHAR(100) DEFAULT NULL AFTER `ward_area` ");
addColumn($conn, 'cleaning_schedules', 'location_type', "VARCHAR(100) DEFAULT NULL AFTER `specific_room` ");
addColumn($conn, 'cleaning_schedules', 'floor_building', "VARCHAR(100) DEFAULT NULL AFTER `location_type` ");
addColumn($conn, 'cleaning_schedules', 'contamination_level', "VARCHAR(50) DEFAULT 'Low' AFTER `cleaning_type` ");
addColumn($conn, 'cleaning_schedules', 'required_ppe', "TEXT DEFAULT NULL AFTER `contamination_level` ");
addColumn($conn, 'cleaning_schedules', 'recurrence_pattern', "VARCHAR(50) DEFAULT NULL AFTER `required_ppe` ");
addColumn($conn, 'cleaning_schedules', 'priority', "VARCHAR(50) DEFAULT 'Routine' AFTER `status` ");
addColumn($conn, 'cleaning_schedules', 'special_instructions', "TEXT DEFAULT NULL AFTER `priority` ");

// 3. Contamination Reports
addColumn($conn, 'contamination_reports', 'status', "ENUM('pending', 'in progress', 'resolved') DEFAULT 'pending'");
addColumn($conn, 'contamination_reports', 'severity', "ENUM('low', 'medium', 'high', 'biohazard') DEFAULT 'low'");

echo "Migration complete.\n";
