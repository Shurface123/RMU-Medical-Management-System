-- =========================================================================================
-- RMU MEDICAL SICKBAY - PHASE 2 MIGRATION SCRIPT
-- STAFF DASHBOARD TABLES & SCHEMA EXPANSION
-- =========================================================================================

-- 1. UPDATE EXISTING USERS TABLE ROLE ENUM
-- We alter the column to append the new staff-specific roles.
ALTER TABLE `users`
MODIFY COLUMN `user_role` ENUM('admin','doctor','patient','staff','pharmacist','nurse','lab_technician','ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') NOT NULL;

-- 2. UPDATE EXISTING STAFF TABLE
-- Denormalizing specific fields here as requested, preserving existing data (id, user_id, etc.)
DROP PROCEDURE IF EXISTS add_column_if_missing;

DELIMITER $$
CREATE PROCEDURE add_column_if_missing(
    IN tbl VARCHAR(100),
    IN col VARCHAR(100),
    IN col_def TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tbl
          AND COLUMN_NAME = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', col_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL add_column_if_missing('staff', 'full_name',                "VARCHAR(200) DEFAULT NULL");
CALL add_column_if_missing('staff', 'date_of_birth',            "DATE DEFAULT NULL");
CALL add_column_if_missing('staff', 'gender',                   "ENUM('Male','Female','Other') DEFAULT NULL");
CALL add_column_if_missing('staff', 'nationality',              "VARCHAR(100) DEFAULT NULL");
CALL add_column_if_missing('staff', 'phone',                    "VARCHAR(20) DEFAULT NULL");
CALL add_column_if_missing('staff', 'email',                    "VARCHAR(150) DEFAULT NULL");
CALL add_column_if_missing('staff', 'address',                  "TEXT DEFAULT NULL");
CALL add_column_if_missing('staff', 'profile_photo',            "VARCHAR(255) DEFAULT NULL");
CALL add_column_if_missing('staff', 'role',                     "ENUM('ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') DEFAULT NULL");
CALL add_column_if_missing('staff', 'department_id',            "INT(11) DEFAULT NULL");
CALL add_column_if_missing('staff', 'designation',              "VARCHAR(100) DEFAULT NULL");
CALL add_column_if_missing('staff', 'employee_id',              "VARCHAR(50) DEFAULT NULL");
CALL add_column_if_missing('staff', 'employment_type',          "ENUM('full-time','part-time','contract') DEFAULT 'full-time'");
CALL add_column_if_missing('staff', 'shift_type',               "ENUM('morning','afternoon','night','rotating') DEFAULT 'morning'");
CALL add_column_if_missing('staff', 'status',                   "ENUM('active','inactive','on leave','suspended') DEFAULT 'active'");
CALL add_column_if_missing('staff', 'date_joined',              "DATE DEFAULT NULL");
CALL add_column_if_missing('staff', 'emergency_contact_name',   "VARCHAR(150) DEFAULT NULL");
CALL add_column_if_missing('staff', 'emergency_contact_phone',  "VARCHAR(20) DEFAULT NULL");

DROP PROCEDURE IF EXISTS add_column_if_missing;

-- Make employee_id unique if it exists
DROP PROCEDURE IF EXISTS add_unique_if_missing;

DELIMITER $$
CREATE PROCEDURE add_unique_if_missing(
    IN tbl VARCHAR(100),
    IN idx_name VARCHAR(100),
    IN col VARCHAR(100)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tbl
          AND INDEX_NAME = idx_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD UNIQUE KEY `', idx_name, '` (`', col, '`)');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL add_unique_if_missing('staff', 'uk_employee_id', 'employee_id');

DROP PROCEDURE IF EXISTS add_unique_if_missing;

-- 3. CREATE NEW TABLES

-- staff_roles
CREATE TABLE IF NOT EXISTS `staff_roles` (
    `role_id` int(11) NOT NULL AUTO_INCREMENT,
    `role_slug` enum('ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') NOT NULL,
    `role_display_name` varchar(100) NOT NULL,
    `role_description` text DEFAULT NULL,
    `icon_class` varchar(100) DEFAULT 'fas fa-user-tie',
    `dashboard_file_path` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `uk_role_slug` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-populate staff_roles
INSERT IGNORE INTO `staff_roles` (`role_slug`, `role_display_name`, `role_description`, `icon_class`, `dashboard_file_path`) VALUES
('ambulance_driver', 'Ambulance Driver', 'Manages trips, vehicles, and emergency transport', 'fas fa-ambulance', 'dashboards/staff_dashboard.php'),
('cleaner', 'Hospital Cleaner', 'Manages ward cleaning schedules and sanitation logs', 'fas fa-broom', 'dashboards/staff_dashboard.php'),
('laundry_staff', 'Laundry Staff', 'Manages hospital linen, washing batches, and inventory', 'fas fa-tshirt', 'dashboards/staff_dashboard.php'),
('maintenance', 'Maintenance Technician', 'Handles facility repairs, equipment, and work orders', 'fas fa-tools', 'dashboards/staff_dashboard.php'),
('security', 'Security Personnel', 'Manages access logs, ward patrols, and incident reports', 'fas fa-shield-alt', 'dashboards/staff_dashboard.php'),
('kitchen_staff', 'Kitchen & Catering', 'Manages patient dietary meals and food delivery', 'fas fa-utensils', 'dashboards/staff_dashboard.php');

-- staff_departments
CREATE TABLE IF NOT EXISTS `staff_departments` (
    `department_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `description` text DEFAULT NULL,
    `head_of_department` int(11) DEFAULT NULL COMMENT 'staff ID nullable',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_shifts
CREATE TABLE IF NOT EXISTS `staff_shifts` (
    `shift_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `shift_type` varchar(50) NOT NULL,
    `shift_date` date NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `location_ward_assigned` varchar(255) DEFAULT NULL,
    `status` enum('scheduled','active','completed','missed','swapped') DEFAULT 'scheduled',
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`shift_id`),
    KEY `idx_staff_shift` (`staff_id`, `shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_tasks
CREATE TABLE IF NOT EXISTS `staff_tasks` (
    `task_id` int(11) NOT NULL AUTO_INCREMENT,
    `assigned_to` int(11) NOT NULL COMMENT 'staff ID',
    `assigned_by` int(11) DEFAULT NULL COMMENT 'admin ID or system',
    `task_title` varchar(255) NOT NULL,
    `task_description` text DEFAULT NULL,
    `task_category` enum('cleaning','laundry','maintenance','transport','security','kitchen','general') NOT NULL,
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `location` varchar(255) DEFAULT NULL COMMENT 'ward/room/area',
    `due_date` date DEFAULT NULL,
    `due_time` time DEFAULT NULL,
    `status` enum('pending','in progress','completed','overdue','cancelled') DEFAULT 'pending',
    `completion_notes` text DEFAULT NULL,
    `completion_photo_path` varchar(255) DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_task_checklists
CREATE TABLE IF NOT EXISTS `staff_task_checklists` (
    `checklist_id` int(11) NOT NULL AUTO_INCREMENT,
    `task_id` int(11) NOT NULL,
    `checklist_item` varchar(255) NOT NULL,
    `is_completed` tinyint(1) DEFAULT 0,
    `completed_by` int(11) DEFAULT NULL COMMENT 'staff ID',
    `completed_at` datetime DEFAULT NULL,
    PRIMARY KEY (`checklist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ambulance_trips
CREATE TABLE IF NOT EXISTS `ambulance_trips` (
    `trip_id` int(11) NOT NULL AUTO_INCREMENT,
    `driver_id` int(11) NOT NULL COMMENT 'staff ID',
    `patient_id` int(11) DEFAULT NULL COMMENT 'nullable — may not be registered patient',
    `pickup_location` varchar(255) NOT NULL,
    `destination` varchar(255) NOT NULL,
    `request_type` enum('emergency','scheduled') DEFAULT 'emergency',
    `request_source` enum('admin','doctor','nurse','walk-in') DEFAULT 'walk-in',
    `trip_status` enum('requested','accepted','rejected','en route','patient onboard','arrived','completed','cancelled') DEFAULT 'requested',
    `accepted_at` datetime DEFAULT NULL,
    `departed_at` datetime DEFAULT NULL,
    `patient_onboard_at` datetime DEFAULT NULL,
    `arrived_at` datetime DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    `distance_km` decimal(8,2) DEFAULT NULL,
    `fuel_used_litres` decimal(8,2) DEFAULT NULL,
    `vehicle_id` int(11) DEFAULT NULL,
    `trip_notes` text DEFAULT NULL,
    `rejection_reason` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ambulance_requests
CREATE TABLE IF NOT EXISTS `ambulance_requests` (
    `request_id` int(11) NOT NULL AUTO_INCREMENT,
    `requested_by` varchar(100) DEFAULT NULL COMMENT 'user ID and role',
    `patient_name` varchar(200) DEFAULT NULL,
    `patient_condition` text DEFAULT NULL,
    `pickup_address` text NOT NULL,
    `destination` varchar(255) NOT NULL,
    `urgency` enum('routine','urgent','emergency') DEFAULT 'routine',
    `assigned_driver_id` int(11) DEFAULT NULL COMMENT 'nullable',
    `status` enum('pending','assigned','accepted','rejected','completed','cancelled') DEFAULT 'pending',
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- vehicles
CREATE TABLE IF NOT EXISTS `vehicles` (
    `vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
    `registration_number` varchar(50) NOT NULL,
    `make` varchar(100) DEFAULT NULL,
    `model` varchar(100) DEFAULT NULL,
    `year` int(4) DEFAULT NULL,
    `type` enum('ambulance','utility','other') DEFAULT 'ambulance',
    `fuel_type` varchar(50) DEFAULT NULL,
    `current_mileage` int(11) DEFAULT 0,
    `status` enum('available','in use','maintenance','out of service') DEFAULT 'available',
    `assigned_driver_id` int(11) DEFAULT NULL,
    `last_service_date` date DEFAULT NULL,
    `next_service_date` date DEFAULT NULL,
    `insurance_expiry` date DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`vehicle_id`),
    UNIQUE KEY `uk_reg_no` (`registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- vehicle_maintenance_logs
CREATE TABLE IF NOT EXISTS `vehicle_maintenance_logs` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `vehicle_id` int(11) NOT NULL,
    `reported_by` int(11) NOT NULL COMMENT 'staff ID',
    `issue_description` text NOT NULL,
    `maintenance_type` enum('repair','service','inspection','fuel') NOT NULL,
    `cost` decimal(10,2) DEFAULT 0.00,
    `performed_by` varchar(150) DEFAULT NULL,
    `performed_at` datetime DEFAULT NULL,
    `next_due_date` date DEFAULT NULL,
    `status` enum('reported','in progress','resolved') DEFAULT 'reported',
    `images_path` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cleaning_schedules
CREATE TABLE IF NOT EXISTS `cleaning_schedules` (
    `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
    `assigned_to` int(11) NOT NULL COMMENT 'staff ID',
    `ward_room_area` varchar(255) NOT NULL,
    `schedule_date` date NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `cleaning_type` enum('routine','deep clean','biohazard','post-discharge') DEFAULT 'routine',
    `status` enum('scheduled','in progress','completed','missed') DEFAULT 'scheduled',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cleaning_logs
CREATE TABLE IF NOT EXISTS `cleaning_logs` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `schedule_id` int(11) DEFAULT NULL,
    `staff_id` int(11) NOT NULL,
    `ward_room_area` varchar(255) NOT NULL,
    `cleaning_type` varchar(50) NOT NULL,
    `started_at` datetime DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    `checklist_completed` tinyint(1) DEFAULT 0,
    `sanitation_status` enum('clean','contaminated','pending inspection') DEFAULT 'clean',
    `notes` text DEFAULT NULL,
    `photo_proof_path` varchar(255) DEFAULT NULL,
    `issues_reported` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contamination_reports
CREATE TABLE IF NOT EXISTS `contamination_reports` (
    `report_id` int(11) NOT NULL AUTO_INCREMENT,
    `reported_by` int(11) NOT NULL COMMENT 'staff ID',
    `location` varchar(255) NOT NULL,
    `contamination_type` enum('biohazard','chemical','biological','general') NOT NULL,
    `severity` enum('low','medium','high','critical') DEFAULT 'medium',
    `description` text NOT NULL,
    `photo_path` varchar(255) DEFAULT NULL,
    `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `status` enum('reported','acknowledged','in progress','resolved') DEFAULT 'reported',
    `resolved_by` varchar(150) DEFAULT NULL,
    `resolved_at` datetime DEFAULT NULL,
    `admin_notified` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- laundry_batches
CREATE TABLE IF NOT EXISTS `laundry_batches` (
    `batch_id` int(11) NOT NULL AUTO_INCREMENT,
    `batch_code` varchar(50) NOT NULL,
    `assigned_to` int(11) NOT NULL COMMENT 'staff ID',
    `requested_by` varchar(150) NOT NULL COMMENT 'ward/department',
    `batch_type` enum('bed linen','patient gown','staff uniform','theatre','other') NOT NULL,
    `item_count` int(11) DEFAULT 0,
    `weight_kg` decimal(6,2) DEFAULT NULL,
    `collection_status` enum('pending','collected') DEFAULT 'pending',
    `washing_status` enum('pending','in progress','completed') DEFAULT 'pending',
    `ironing_status` enum('pending','in progress','completed') DEFAULT 'pending',
    `delivery_status` enum('pending','delivered') DEFAULT 'pending',
    `damaged_items_count` int(11) DEFAULT 0,
    `contaminated_items_count` int(11) DEFAULT 0,
    `collected_at` datetime DEFAULT NULL,
    `washing_started_at` datetime DEFAULT NULL,
    `washing_completed_at` datetime DEFAULT NULL,
    `ironing_completed_at` datetime DEFAULT NULL,
    `delivered_at` datetime DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`batch_id`),
    UNIQUE KEY `uk_batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- laundry_inventory
CREATE TABLE IF NOT EXISTS `laundry_inventory` (
    `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
    `item_type` varchar(150) NOT NULL,
    `total_quantity` int(11) DEFAULT 0,
    `available_quantity` int(11) DEFAULT 0,
    `in_wash_quantity` int(11) DEFAULT 0,
    `damaged_quantity` int(11) DEFAULT 0,
    `condemned_quantity` int(11) DEFAULT 0,
    `reorder_level` int(11) DEFAULT 50,
    `last_updated_by` int(11) DEFAULT NULL COMMENT 'staff ID',
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- laundry_damage_reports
CREATE TABLE IF NOT EXISTS `laundry_damage_reports` (
    `report_id` int(11) NOT NULL AUTO_INCREMENT,
    `batch_id` int(11) DEFAULT NULL,
    `staff_id` int(11) NOT NULL,
    `item_type` varchar(100) NOT NULL,
    `quantity_damaged` int(11) NOT NULL,
    `damage_description` text DEFAULT NULL,
    `photo_path` varchar(255) DEFAULT NULL,
    `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `status` enum('reported','acknowledged','written off') DEFAULT 'reported',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- maintenance_requests
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
    `request_id` int(11) NOT NULL AUTO_INCREMENT,
    `reported_by` varchar(150) NOT NULL COMMENT 'user ID and role',
    `assigned_to` int(11) DEFAULT NULL COMMENT 'staff ID nullable',
    `location` varchar(255) NOT NULL,
    `equipment_or_area` varchar(255) NOT NULL,
    `issue_description` text NOT NULL,
    `issue_category` enum('electrical','plumbing','structural','equipment','furniture','other') NOT NULL,
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `status` enum('reported','assigned','in progress','on hold','completed','cancelled') DEFAULT 'reported',
    `images_path` json DEFAULT NULL,
    `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `assigned_at` datetime DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    `completion_notes` text DEFAULT NULL,
    `completion_images_path` json DEFAULT NULL,
    `admin_verified` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- maintenance_logs
CREATE TABLE IF NOT EXISTS `maintenance_logs` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `request_id` int(11) DEFAULT NULL,
    `staff_id` int(11) NOT NULL,
    `action_taken` text NOT NULL,
    `time_spent_hours` decimal(5,2) DEFAULT 0.00,
    `parts_used` json DEFAULT NULL,
    `cost` decimal(10,2) DEFAULT 0.00,
    `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `notes` text DEFAULT NULL,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- security_logs
CREATE TABLE IF NOT EXISTS `security_logs` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `incident_type` enum('visitor check','access control','incident report','patrol log','other') NOT NULL,
    `location` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `status` enum('logged','escalated','resolved') DEFAULT 'logged',
    `escalated_to` int(11) DEFAULT NULL COMMENT 'admin ID nullable',
    `notes` text DEFAULT NULL,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- kitchen_tasks
CREATE TABLE IF NOT EXISTS `kitchen_tasks` (
    `task_id` int(11) NOT NULL AUTO_INCREMENT,
    `assigned_to` int(11) NOT NULL COMMENT 'staff ID',
    `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
    `ward_department` varchar(150) NOT NULL,
    `dietary_requirements` json DEFAULT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `preparation_status` enum('pending','in preparation','ready','delivered') DEFAULT 'pending',
    `delivery_status` enum('pending','delivered') DEFAULT 'pending',
    `scheduled_time` time DEFAULT NULL,
    `prepared_at` datetime DEFAULT NULL,
    `delivered_at` datetime DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_notifications
CREATE TABLE IF NOT EXISTS `staff_notifications` (
    `notification_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `type` enum('task','alert','shift','emergency','system','message','maintenance') NOT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `related_module` varchar(100) DEFAULT NULL,
    `related_record_id` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_messages
CREATE TABLE IF NOT EXISTS `staff_messages` (
    `message_id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `sender_role` varchar(50) NOT NULL,
    `receiver_id` int(11) NOT NULL COMMENT 'staff ID',
    `subject` varchar(255) DEFAULT NULL,
    `message_content` text NOT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `priority` enum('normal','urgent') DEFAULT 'normal',
    `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `read_at` datetime DEFAULT NULL,
    PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_performance
CREATE TABLE IF NOT EXISTS `staff_performance` (
    `performance_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `period` enum('daily','weekly','monthly') NOT NULL,
    `period_date` date NOT NULL,
    `tasks_assigned` int(11) DEFAULT 0,
    `tasks_completed` int(11) DEFAULT 0,
    `tasks_overdue` int(11) DEFAULT 0,
    `attendance_score` decimal(5,2) DEFAULT NULL,
    `punctuality_score` decimal(5,2) DEFAULT NULL,
    `quality_score` decimal(5,2) DEFAULT NULL COMMENT 'admin rated',
    `overall_rating` decimal(5,2) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `rated_by` int(11) DEFAULT NULL COMMENT 'admin ID',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`performance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_attendance
CREATE TABLE IF NOT EXISTS `staff_attendance` (
    `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `shift_id` int(11) DEFAULT NULL,
    `check_in_time` datetime DEFAULT NULL,
    `check_out_time` datetime DEFAULT NULL,
    `check_in_method` enum('manual','system') DEFAULT 'system',
    `status` enum('present','absent','late','early departure','on leave') DEFAULT 'present',
    `notes` text DEFAULT NULL,
    `recorded_by` varchar(50) DEFAULT 'system' COMMENT 'admin ID or system',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`attendance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_leave_requests
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
    `leave_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `leave_type` enum('annual','sick','emergency','unpaid') NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `reason` text NOT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `applied_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `reviewed_by` int(11) DEFAULT NULL COMMENT 'admin ID',
    `reviewed_at` datetime DEFAULT NULL,
    `admin_notes` text DEFAULT NULL,
    PRIMARY KEY (`leave_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_audit_trail
CREATE TABLE IF NOT EXISTS `staff_audit_trail` (
    `trail_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `action_type` varchar(150) NOT NULL,
    `module` varchar(100) NOT NULL,
    `record_id_affected` int(11) DEFAULT NULL,
    `old_value` json DEFAULT NULL,
    `new_value` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `device` varchar(255) DEFAULT NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`trail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_settings
CREATE TABLE IF NOT EXISTS `staff_settings` (
    `settings_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `theme_preference` enum('light','dark') DEFAULT 'light',
    `language` varchar(50) DEFAULT 'en',
    `notification_preferences` json DEFAULT NULL,
    `alert_sound_enabled` tinyint(1) DEFAULT 1,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`settings_id`),
    UNIQUE KEY `uk_staff_settings` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_documents
CREATE TABLE IF NOT EXISTS `staff_documents` (
    `document_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_type` varchar(50) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_qualifications
CREATE TABLE IF NOT EXISTS `staff_qualifications` (
    `qualification_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `certificate_name` varchar(255) NOT NULL,
    `institution` varchar(255) NOT NULL,
    `year_awarded` int(4) NOT NULL,
    `file_path` varchar(500) DEFAULT NULL,
    `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`qualification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_profile_completeness
CREATE TABLE IF NOT EXISTS `staff_profile_completeness` (
    `record_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `personal_info_complete` tinyint(1) DEFAULT 0,
    `documents_uploaded` tinyint(1) DEFAULT 0,
    `photo_uploaded` tinyint(1) DEFAULT 0,
    `security_setup_complete` tinyint(1) DEFAULT 0,
    `overall_percentage` decimal(5,2) DEFAULT 0.00,
    `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `uk_staff_comp` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_sessions
CREATE TABLE IF NOT EXISTS `staff_sessions` (
    `session_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `device_info` text DEFAULT NULL,
    `browser` varchar(150) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `login_time` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_active` datetime DEFAULT NULL,
    `is_current` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_activity_log
CREATE TABLE IF NOT EXISTS `staff_activity_log` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `action` varchar(255) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `device` varchar(255) DEFAULT NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- END OF SCRIPT.
