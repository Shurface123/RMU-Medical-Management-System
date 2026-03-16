-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 16, 2026 at 11:37 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rmu_medical_sickbay`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `DestroyUserSessions`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `DestroyUserSessions` (IN `p_user_id` INT)   BEGIN
    UPDATE `user_sessions` 
    SET `is_active` = FALSE, `logout_time` = NOW() 
    WHERE `user_id` = p_user_id AND `is_active` = TRUE;
END$$

DROP PROCEDURE IF EXISTS `GetAvailableBeds`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAvailableBeds` (IN `p_ward` VARCHAR(100))   BEGIN
    IF p_ward IS NULL OR p_ward = '' THEN
        SELECT * FROM `beds` WHERE `status` = 'Available' ORDER BY `ward`, `bed_number`;
    ELSE
        SELECT * FROM `beds` WHERE `status` = 'Available' AND `ward` = p_ward ORDER BY `bed_number`;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `GetLowStockMedicines`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetLowStockMedicines` ()   BEGIN
    SELECT * FROM `medicines` 
    WHERE `stock_quantity` <= `reorder_level` 
    ORDER BY `stock_quantity` ASC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_prescriptions`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `active_prescriptions`;
CREATE TABLE IF NOT EXISTS `active_prescriptions` (
`prescription_id` int
,`prescription_date` date
,`status` enum('Pending','Dispensed','Partially Dispensed','Cancelled','Expired')
,`patient_name` varchar(150)
,`doctor_name` varchar(150)
,`total_items` bigint
,`dispensed_items` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `ambulances`
--

DROP TABLE IF EXISTS `ambulances`;
CREATE TABLE IF NOT EXISTS `ambulances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ambulance_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Available','On Duty','Maintenance','Out of Service') COLLATE utf8mb4_unicode_ci DEFAULT 'Available',
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ambulance_id` (`ambulance_id`),
  UNIQUE KEY `vehicle_number` (`vehicle_number`),
  KEY `idx_ambulance_id` (`ambulance_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ambulances`
--

INSERT INTO `ambulances` (`id`, `ambulance_id`, `vehicle_number`, `driver_name`, `driver_phone`, `status`, `last_service_date`, `next_service_date`, `created_at`, `updated_at`) VALUES
(1, 'AMB001', 'GH-1234-20', 'Kwame Mensah', '0241234567', 'Available', NULL, NULL, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'AMB002', 'GH-5678-20', 'Ama Serwaa', '0245678901', 'Available', NULL, NULL, '2026-02-06 05:09:21', '2026-02-06 05:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_requests`
--

DROP TABLE IF EXISTS `ambulance_requests`;
CREATE TABLE IF NOT EXISTS `ambulance_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_location` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_type` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ambulance_id` int DEFAULT NULL,
  `status` enum('Pending','Dispatched','In Transit','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `request_time` datetime NOT NULL,
  `dispatch_time` datetime DEFAULT NULL,
  `completion_time` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `ambulance_id` (`ambulance_id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_trips`
--

DROP TABLE IF EXISTS `ambulance_trips`;
CREATE TABLE IF NOT EXISTS `ambulance_trips` (
  `trip_id` int NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL COMMENT 'staff ID',
  `patient_id` int DEFAULT NULL COMMENT 'nullable — may not be registered patient',
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
  `vehicle_id` int DEFAULT NULL,
  `trip_notes` text,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `service_type` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reschedule_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reschedule_date` date DEFAULT NULL,
  `reschedule_time` time DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by` int DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT '0',
  `urgency_level` enum('Low','Medium','High','Critical') COLLATE utf8mb4_unicode_ci DEFAULT 'Low',
  `status` enum('Pending','Confirmed','Completed','Cancelled','No-Show','Rescheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_date` (`appointment_date`),
  KEY `idx_status` (`status`),
  KEY `idx_apt_patient_date` (`patient_id`,`appointment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_reminders`
--

DROP TABLE IF EXISTS `appointment_reminders`;
CREATE TABLE IF NOT EXISTS `appointment_reminders` (
  `reminder_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int NOT NULL,
  `reminder_type` enum('email','sms','notification') COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_time` timestamp NOT NULL,
  `status` enum('Pending','Sent','Failed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reminder_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_time` (`scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'config_update', 'system_config', NULL, NULL, '\"Updated email settings\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 20:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

DROP TABLE IF EXISTS `beds`;
CREATE TABLE IF NOT EXISTS `beds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bed_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bed_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bed_type` enum('General','ICU','Private','Semi-Private') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `status` enum('Available','Occupied','Maintenance','Reserved') COLLATE utf8mb4_unicode_ci DEFAULT 'Available',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bed_id` (`bed_id`),
  UNIQUE KEY `bed_number` (`bed_number`),
  KEY `idx_bed_id` (`bed_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ward` (`ward`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beds`
--

INSERT INTO `beds` (`id`, `bed_id`, `bed_number`, `ward`, `bed_type`, `status`, `daily_rate`, `created_at`, `updated_at`) VALUES
(1, 'BED001', 'W1-B01', 'General Ward 1', 'General', 'Available', 50.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'BED002', 'W1-B02', 'General Ward 1', 'General', 'Available', 50.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(3, 'BED003', 'W1-B03', 'General Ward 1', 'General', 'Available', 50.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(4, 'BED004', 'ICU-B01', 'ICU', 'ICU', 'Available', 200.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(5, 'BED005', 'ICU-B02', 'ICU', 'ICU', 'Available', 200.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(6, 'BED006', 'PVT-B01', 'Private Ward', 'Private', 'Available', 150.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(7, 'BED007', 'PVT-B02', 'Private Ward', 'Private', 'Available', 150.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `bed_assignments`
--

DROP TABLE IF EXISTS `bed_assignments`;
CREATE TABLE IF NOT EXISTS `bed_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `bed_id` int NOT NULL,
  `assigned_nurse_id` int DEFAULT NULL,
  `admission_date` datetime NOT NULL,
  `discharge_date` datetime DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `attending_nurse_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Discharged','Transferred') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_id` (`assignment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `bed_id` (`bed_id`),
  KEY `idx_assignment_id` (`assignment_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `bed_management`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `bed_management`;
CREATE TABLE IF NOT EXISTS `bed_management` (
`bed_pk` int
,`bed_id` varchar(50)
,`bed_number` varchar(50)
,`ward` varchar(100)
,`bed_type` enum('General','ICU','Private','Semi-Private')
,`bed_status` enum('Available','Occupied','Maintenance','Reserved')
,`daily_rate` decimal(10,2)
,`assignment_pk` int
,`patient_id` int
,`admission_date` datetime
,`discharge_date` datetime
,`admission_reason` text
,`assignment_status` enum('Active','Discharged','Transferred')
,`patient_ref_id` varchar(50)
,`patient_name` varchar(200)
,`patient_phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `bed_transfers`
--

DROP TABLE IF EXISTS `bed_transfers`;
CREATE TABLE IF NOT EXISTS `bed_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `from_bed_id` int DEFAULT NULL,
  `to_bed_id` int DEFAULT NULL,
  `from_ward` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_ward` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_reason` text COLLATE utf8mb4_unicode_ci,
  `transfer_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `authorized_by` int DEFAULT NULL COMMENT 'doctor user_id',
  `status` enum('Requested','Approved','Completed','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Requested',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bt_patient` (`patient_id`),
  KEY `idx_bt_nurse` (`nurse_id`),
  KEY `idx_bt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_logs`
--

DROP TABLE IF EXISTS `cleaning_logs`;
CREATE TABLE IF NOT EXISTS `cleaning_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int DEFAULT NULL,
  `staff_id` int NOT NULL,
  `ward_room_area` varchar(255) NOT NULL,
  `cleaning_type` varchar(50) NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `checklist_completed` tinyint(1) DEFAULT '0',
  `sanitation_status` enum('clean','contaminated','pending inspection') DEFAULT 'clean',
  `notes` text,
  `photo_proof_path` varchar(255) DEFAULT NULL,
  `issues_reported` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_schedules`
--

DROP TABLE IF EXISTS `cleaning_schedules`;
CREATE TABLE IF NOT EXISTS `cleaning_schedules` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `ward_room_area` varchar(255) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `cleaning_type` enum('routine','deep clean','biohazard','post-discharge') DEFAULT 'routine',
  `status` enum('scheduled','in progress','completed','missed') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contamination_reports`
--

DROP TABLE IF EXISTS `contamination_reports`;
CREATE TABLE IF NOT EXISTS `contamination_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `reported_by` int NOT NULL COMMENT 'staff ID',
  `location` varchar(255) NOT NULL,
  `contamination_type` enum('biohazard','chemical','biological','general') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('reported','acknowledged','in progress','resolved') DEFAULT 'reported',
  `resolved_by` varchar(150) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `admin_notified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `head_doctor_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_doctor_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Medicine', 'General outpatient consultations', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(2, 'Pediatrics', 'Child and adolescent health', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(3, 'Surgery', 'Surgical procedures and pre/post-op care', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(4, 'Obstetrics & Gynecology', 'Women health and maternity', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(5, 'Emergency Medicine', 'Accident and emergency care', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(6, 'Internal Medicine', 'Diagnosis and treatment of adult diseases', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(7, 'Ophthalmology', 'Eye care and vision', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(8, 'Dermatology', 'Skin conditions and treatment', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(9, 'Psychiatry', 'Mental health and behavioral disorders', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(10, 'Radiology', 'Medical imaging and diagnostics', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(11, 'Dental', 'Oral health and dental procedures', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(12, 'Pharmacy', 'Medication management', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(13, 'Laboratory', 'Diagnostic lab services', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `discharge_instructions`
--

DROP TABLE IF EXISTS `discharge_instructions`;
CREATE TABLE IF NOT EXISTS `discharge_instructions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `instruction_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `medication_instructions` text COLLATE utf8mb4_unicode_ci,
  `follow_up_appointments` text COLLATE utf8mb4_unicode_ci,
  `warning_signs` text COLLATE utf8mb4_unicode_ci COMMENT 'When to return to hospital',
  `documents_uploaded` json DEFAULT NULL COMMENT '["discharge_summary.pdf"]',
  `given_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `patient_acknowledged` tinyint(1) DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `witness_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_di_patient` (`patient_id`),
  KEY `idx_di_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispensing_records`
--

DROP TABLE IF EXISTS `dispensing_records`;
CREATE TABLE IF NOT EXISTS `dispensing_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `pharmacist_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `quantity_dispensed` int NOT NULL DEFAULT '0',
  `dispensing_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `selling_price` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('paid','unpaid','insurance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_disp_prescription` (`prescription_id`),
  KEY `idx_disp_patient` (`patient_id`),
  KEY `idx_disp_pharmacist` (`pharmacist_id`),
  KEY `idx_disp_medicine` (`medicine_id`),
  KEY `idx_disp_date` (`dispensing_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `doctor_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int DEFAULT NULL,
  `sub_specialization` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `professional_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualifications` text COLLATE utf8mb4_unicode_ci,
  `experience_years` int DEFAULT '0',
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_issuing_body` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `medical_school` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgraduate_details` text COLLATE utf8mb4_unicode_ci,
  `languages_spoken` json DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT '0.00',
  `available_days` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `available_hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghana',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `availability_status` enum('Online','Offline','Busy') COLLATE utf8mb4_unicode_ci DEFAULT 'Offline',
  `profile_completion_pct` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_id` (`doctor_id`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `user_id` (`user_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_specialization` (`specialization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_activity_log`
--

DROP TABLE IF EXISTS `doctor_activity_log`;
CREATE TABLE IF NOT EXISTS `doctor_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `action` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dal_doctor` (`doctor_id`),
  KEY `idx_dal_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

DROP TABLE IF EXISTS `doctor_availability`;
CREATE TABLE IF NOT EXISTS `doctor_availability` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `start_time` time DEFAULT '08:00:00',
  `end_time` time DEFAULT '17:00:00',
  `max_appointments` int DEFAULT '20',
  `slot_duration_min` int DEFAULT '30',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_day` (`doctor_id`,`day_of_week`),
  KEY `idx_da_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_certifications`
--

DROP TABLE IF EXISTS `doctor_certifications`;
CREATE TABLE IF NOT EXISTS `doctor_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `cert_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_org` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cert_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dc_doctor` (`doctor_id`),
  KEY `idx_dc_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_documents`
--

DROP TABLE IF EXISTS `doctor_documents`;
CREATE TABLE IF NOT EXISTS `doctor_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `file_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT '0' COMMENT 'bytes',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dd_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_leave_exceptions`
--

DROP TABLE IF EXISTS `doctor_leave_exceptions`;
CREATE TABLE IF NOT EXISTS `doctor_leave_exceptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `exception_date` date NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_date` (`doctor_id`,`exception_date`),
  KEY `idx_dle_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_patient_notes`
--

DROP TABLE IF EXISTS `doctor_patient_notes`;
CREATE TABLE IF NOT EXISTS `doctor_patient_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `note_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `patient_id` int NOT NULL COMMENT 'patients.id',
  `appointment_id` int DEFAULT NULL COMMENT 'appointments.id ??? optional link',
  `note_type` enum('General','Follow-up','Warning','Allergy','Observation','Referral') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_private` tinyint(1) DEFAULT '1' COMMENT '1 = only this doctor can see it',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_id` (`note_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profile_completeness`
--

DROP TABLE IF EXISTS `doctor_profile_completeness`;
CREATE TABLE IF NOT EXISTS `doctor_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `personal_info` tinyint(1) DEFAULT '0',
  `professional_profile` tinyint(1) DEFAULT '0',
  `qualifications` tinyint(1) DEFAULT '0',
  `availability_set` tinyint(1) DEFAULT '0',
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `security_setup` tinyint(1) DEFAULT '0',
  `documents_uploaded` tinyint(1) DEFAULT '0',
  `overall_pct` tinyint DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dpc_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_qualifications`
--

DROP TABLE IF EXISTS `doctor_qualifications`;
CREATE TABLE IF NOT EXISTS `doctor_qualifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `degree_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_awarded` int DEFAULT NULL,
  `cert_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dq_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_reports`
--

DROP TABLE IF EXISTS `doctor_reports`;
CREATE TABLE IF NOT EXISTS `doctor_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` enum('Patient Summary','Appointment History','Prescription Report','Lab Summary','Monthly Activity','Custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Custom',
  `title` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `generated_by` int NOT NULL COMMENT 'doctors.id ??? who generated this report',
  `date_generated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `parameters` json DEFAULT NULL COMMENT 'Filters: date_from, date_to, patient_id, etc.',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_id` (`report_id`),
  KEY `idx_generated_by` (`generated_by`),
  KEY `idx_date_generated` (`date_generated`),
  KEY `idx_report_type` (`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_sessions`
--

DROP TABLE IF EXISTS `doctor_sessions`;
CREATE TABLE IF NOT EXISTS `doctor_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `session_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ds_doctor` (`doctor_id`),
  KEY `idx_ds_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_settings`
--

DROP TABLE IF EXISTS `doctor_settings`;
CREATE TABLE IF NOT EXISTS `doctor_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'doctors.id',
  `notif_new_appointment` tinyint(1) DEFAULT '1',
  `notif_appt_reminders` tinyint(1) DEFAULT '1',
  `notif_appt_cancellations` tinyint(1) DEFAULT '1',
  `notif_lab_results` tinyint(1) DEFAULT '1',
  `notif_rx_refills` tinyint(1) DEFAULT '1',
  `notif_record_updates` tinyint(1) DEFAULT '1',
  `notif_nurse_messages` tinyint(1) DEFAULT '1',
  `notif_inventory_alerts` tinyint(1) DEFAULT '1',
  `notif_license_expiry` tinyint(1) DEFAULT '1',
  `notif_system_announcements` tinyint(1) DEFAULT '1',
  `preferred_channel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'dashboard',
  `preferred_language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ds_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE IF NOT EXISTS `email_queue` (
  `email_id` int NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Sent','Failed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','normal','high') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email_id`),
  KEY `idx_status` (`status`),
  KEY `idx_recipient_email` (`recipient_email`),
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_alerts`
--

DROP TABLE IF EXISTS `emergency_alerts`;
CREATE TABLE IF NOT EXISTS `emergency_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `patient_id` int DEFAULT NULL,
  `alert_type` enum('Code Blue','Rapid Response','Fall','Fire','General Emergency','Security') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('Critical','High','Medium') COLLATE utf8mb4_unicode_ci DEFAULT 'High',
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ward/Bed',
  `message` text COLLATE utf8mb4_unicode_ci,
  `notified_doctors` json DEFAULT NULL COMMENT '[1,5,12]',
  `status` enum('Active','Responded','Resolved','False Alarm') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `triggered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `responded_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int DEFAULT NULL COMMENT 'user_id',
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_ea_nurse` (`nurse_id`),
  KEY `idx_ea_status` (`status`),
  KEY `idx_ea_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL COMMENT 'patients.id',
  `contact_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_maintenance_log`
--

DROP TABLE IF EXISTS `equipment_maintenance_log`;
CREATE TABLE IF NOT EXISTS `equipment_maintenance_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `maintenance_type` enum('Calibration','Repair','Service','Inspection','Cleaning') DEFAULT 'Service',
  `performed_by` varchar(150) DEFAULT NULL COMMENT 'Technician name or external vendor',
  `performed_by_id` int DEFAULT NULL COMMENT 'FK to lab_technicians.id if internal',
  `performed_at` datetime NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `findings` text,
  `cost` decimal(10,2) DEFAULT '0.00',
  `documents_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_equipment` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fluid_balance`
--

DROP TABLE IF EXISTS `fluid_balance`;
CREATE TABLE IF NOT EXISTS `fluid_balance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `record_date` date NOT NULL,
  `total_intake_ml` int DEFAULT '0',
  `total_output_ml` int DEFAULT '0',
  `net_balance_ml` int DEFAULT '0' COMMENT 'intake - output',
  `intake_sources` json DEFAULT NULL COMMENT '{"oral":500,"iv":1000,"blood":0}',
  `output_sources` json DEFAULT NULL COMMENT '{"urine":800,"vomit":0,"drain":100}',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fb_patient_date` (`patient_id`,`record_date`),
  KEY `idx_fb_patient` (`patient_id`),
  KEY `idx_fb_date` (`record_date`),
  KEY `fk_fb_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `global_login_attempts`
--

DROP TABLE IF EXISTS `global_login_attempts`;
CREATE TABLE IF NOT EXISTS `global_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action_type` enum('login_failed','login_success') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `isolation_records`
--

DROP TABLE IF EXISTS `isolation_records`;
CREATE TABLE IF NOT EXISTS `isolation_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `isolation_type` enum('Contact','Droplet','Airborne','Protective','Combined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `precautions` json DEFAULT NULL COMMENT '["gown","gloves","N95","face_shield"]',
  `doctor_ordered_by` int DEFAULT NULL COMMENT 'doctor user_id',
  `status` enum('Active','Lifted','Modified') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iso_patient` (`patient_id`),
  KEY `idx_iso_status` (`status`),
  KEY `fk_iso_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iv_fluid_records`
--

DROP TABLE IF EXISTS `iv_fluid_records`;
CREATE TABLE IF NOT EXISTS `iv_fluid_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `fluid_type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Normal Saline, Ringers Lactate, D5W, etc.',
  `additives` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. KCl 20mEq',
  `volume_ordered_ml` int NOT NULL,
  `volume_infused_ml` int DEFAULT '0',
  `infusion_rate_ml_hr` decimal(6,1) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('Running','Completed','Paused','Stopped','Pending') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `alert_sent` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iv_patient` (`patient_id`),
  KEY `idx_iv_nurse` (`nurse_id`),
  KEY `idx_iv_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_tasks`
--

DROP TABLE IF EXISTS `kitchen_tasks`;
CREATE TABLE IF NOT EXISTS `kitchen_tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `ward_department` varchar(150) NOT NULL,
  `dietary_requirements` json DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `preparation_status` enum('pending','in preparation','ready','delivered') DEFAULT 'pending',
  `delivery_status` enum('pending','delivered') DEFAULT 'pending',
  `scheduled_time` time DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_audit_trail`
--

DROP TABLE IF EXISTS `lab_audit_trail`;
CREATE TABLE IF NOT EXISTS `lab_audit_trail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `module_affected` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_technician` (`technician_id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `lab_audit_trail`
--
DROP TRIGGER IF EXISTS `prevent_audit_delete`;
DELIMITER $$
CREATE TRIGGER `prevent_audit_delete` BEFORE DELETE ON `lab_audit_trail` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'lab_audit_trail is immutable — DELETE is forbidden';
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `prevent_audit_update`;
DELIMITER $$
CREATE TRIGGER `prevent_audit_update` BEFORE UPDATE ON `lab_audit_trail` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'lab_audit_trail is immutable — UPDATE is forbidden';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

DROP TABLE IF EXISTS `lab_equipment`;
CREATE TABLE IF NOT EXISTS `lab_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `model` varchar(200) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Operational','Maintenance','Calibration Due','Out of Service','Decommissioned') DEFAULT 'Operational',
  `last_calibration_date` date DEFAULT NULL,
  `next_calibration_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `assigned_technician_id` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_generated_reports`
--

DROP TABLE IF EXISTS `lab_generated_reports`;
CREATE TABLE IF NOT EXISTS `lab_generated_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generated_by` int NOT NULL COMMENT 'FK to lab_technicians.id',
  `report_type` varchar(100) NOT NULL,
  `parameters` json DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `format` enum('PDF','CSV','XLSX') DEFAULT 'PDF',
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_internal_messages`
--

DROP TABLE IF EXISTS `lab_internal_messages`;
CREATE TABLE IF NOT EXISTS `lab_internal_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `sender_role` varchar(30) NOT NULL,
  `receiver_id` int NOT NULL,
  `receiver_role` varchar(30) NOT NULL,
  `patient_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('Normal','Urgent') DEFAULT 'Normal',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_receiver` (`receiver_id`,`is_read`),
  KEY `idx_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_notifications`
--

DROP TABLE IF EXISTS `lab_notifications`;
CREATE TABLE IF NOT EXISTS `lab_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient_id` int NOT NULL,
  `recipient_role` varchar(30) DEFAULT 'lab_technician',
  `sender_id` int DEFAULT NULL,
  `sender_role` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('New Order','Result Ready','Critical Value','Equipment Alert','Reagent Alert','System','Message','Quality Control') DEFAULT 'System',
  `is_read` tinyint(1) DEFAULT '0',
  `related_module` varchar(50) DEFAULT NULL,
  `related_record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_id`,`is_read`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_quality_control`
--

DROP TABLE IF EXISTS `lab_quality_control`;
CREATE TABLE IF NOT EXISTS `lab_quality_control` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `equipment_id` int DEFAULT NULL,
  `test_catalog_id` int DEFAULT NULL,
  `qc_date` date NOT NULL,
  `qc_type` enum('Internal','External') DEFAULT 'Internal',
  `lot_number` varchar(100) DEFAULT NULL,
  `expected_range_min` decimal(10,4) DEFAULT NULL,
  `expected_range_max` decimal(10,4) DEFAULT NULL,
  `result_obtained` decimal(10,4) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT '0',
  `corrective_action` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech` (`technician_id`),
  KEY `idx_date` (`qc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_reference_ranges`
--

DROP TABLE IF EXISTS `lab_reference_ranges`;
CREATE TABLE IF NOT EXISTS `lab_reference_ranges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_catalog_id` int NOT NULL,
  `parameter_name` varchar(150) NOT NULL,
  `gender` enum('Male','Female','Both') DEFAULT 'Both',
  `age_min_years` int DEFAULT '0',
  `age_max_years` int DEFAULT '999',
  `normal_min` decimal(10,4) DEFAULT NULL,
  `normal_max` decimal(10,4) DEFAULT NULL,
  `critical_low` decimal(10,4) DEFAULT NULL,
  `critical_high` decimal(10,4) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_catalog` (`test_catalog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_report_templates`
--

DROP TABLE IF EXISTS `lab_report_templates`;
CREATE TABLE IF NOT EXISTS `lab_report_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `header_content` text,
  `footer_content` text,
  `logo_path` varchar(500) DEFAULT NULL,
  `includes_digital_signature` tinyint(1) DEFAULT '0',
  `is_default` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

DROP TABLE IF EXISTS `lab_results`;
CREATE TABLE IF NOT EXISTS `lab_results` (
  `result_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `test_id` int NOT NULL,
  `doctor_id` int DEFAULT NULL,
  `test_date` date NOT NULL,
  `result_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `results` json DEFAULT NULL,
  `normal_range` json DEFAULT NULL,
  `interpretation` text COLLATE utf8mb4_unicode_ci,
  `technician_notes` text COLLATE utf8mb4_unicode_ci,
  `submitted_by` int DEFAULT NULL COMMENT 'users.id',
  `doctor_reviewed` tinyint(1) DEFAULT '0',
  `patient_accessible` tinyint(1) DEFAULT '0',
  `patient_notified` tinyint(1) DEFAULT '0',
  `patient_viewed_at` datetime DEFAULT NULL,
  `result_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `validated_by` int DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `result_interpretation` enum('Normal','Abnormal','Critical','Inconclusive') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `amended_reason` text COLLATE utf8mb4_unicode_ci,
  `reference_range_min` decimal(10,4) DEFAULT NULL,
  `reference_range_max` decimal(10,4) DEFAULT NULL,
  PRIMARY KEY (`result_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_test_id` (`test_id`),
  KEY `idx_status` (`status`),
  KEY `idx_test_date` (`test_date`),
  KEY `idx_lr_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_results_v2`
--

DROP TABLE IF EXISTS `lab_results_v2`;
CREATE TABLE IF NOT EXISTS `lab_results_v2` (
  `id` int NOT NULL AUTO_INCREMENT,
  `result_id` varchar(30) NOT NULL,
  `order_id` int NOT NULL COMMENT 'FK to lab_test_orders.id',
  `sample_id` int DEFAULT NULL COMMENT 'FK to lab_samples.id',
  `patient_id` int NOT NULL,
  `doctor_id` int DEFAULT NULL,
  `technician_id` int NOT NULL,
  `test_name` varchar(200) NOT NULL,
  `result_values` json DEFAULT NULL COMMENT 'Supports multiple parameters',
  `unit_of_measurement` varchar(50) DEFAULT NULL,
  `reference_range_min` decimal(10,4) DEFAULT NULL,
  `reference_range_max` decimal(10,4) DEFAULT NULL,
  `result_interpretation` enum('Normal','Abnormal','Critical','Inconclusive') DEFAULT 'Normal',
  `result_status` enum('Draft','Pending Validation','Validated','Released','Amended') DEFAULT 'Draft',
  `validated_by` int DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `validated_at` datetime DEFAULT NULL,
  `released_to_doctor` tinyint(1) DEFAULT '0',
  `released_at` datetime DEFAULT NULL,
  `released_to_patient` tinyint(1) DEFAULT '0',
  `patient_release_approved_by` int DEFAULT NULL COMMENT 'Doctor who approved',
  `report_file_path` varchar(500) DEFAULT NULL,
  `technician_comments` text,
  `amended_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_result_id` (`result_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_status` (`result_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_result_parameters`
--

DROP TABLE IF EXISTS `lab_result_parameters`;
CREATE TABLE IF NOT EXISTS `lab_result_parameters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `result_id` int NOT NULL COMMENT 'FK to lab_results_v2.id',
  `parameter_name` varchar(150) NOT NULL,
  `value` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `reference_range_min` decimal(10,4) DEFAULT NULL,
  `reference_range_max` decimal(10,4) DEFAULT NULL,
  `flag` enum('Normal','Low','High','Critical Low','Critical High') DEFAULT 'Normal',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_result` (`result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_samples`
--

DROP TABLE IF EXISTS `lab_samples`;
CREATE TABLE IF NOT EXISTS `lab_samples` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample_id` varchar(30) NOT NULL COMMENT 'e.g. SMP-XXXXXXXX',
  `order_id` int NOT NULL COMMENT 'FK to lab_test_orders.id',
  `patient_id` int NOT NULL,
  `technician_id` int DEFAULT NULL,
  `sample_type` enum('Blood','Urine','Stool','Swab','Tissue','CSF','Sputum','Other') DEFAULT 'Blood',
  `sample_code` varchar(50) NOT NULL COMMENT 'Unique barcode',
  `collection_date` date NOT NULL,
  `collection_time` time DEFAULT NULL,
  `collected_by` int DEFAULT NULL COMMENT 'user_id of nurse or technician',
  `container_type` varchar(100) DEFAULT NULL,
  `volume_collected` varchar(50) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `condition_on_receipt` enum('Good','Haemolysed','Clotted','Insufficient','Contaminated','Lipemic') DEFAULT 'Good',
  `status` enum('Collected','Received','Processing','Stored','Disposed','Rejected') DEFAULT 'Collected',
  `rejection_reason` text,
  `notes` text,
  `barcode_image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sample_id` (`sample_id`),
  UNIQUE KEY `uk_sample_code` (`sample_code`),
  KEY `idx_order` (`order_id`),
  KEY `idx_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technicians`
--

DROP TABLE IF EXISTS `lab_technicians`;
CREATE TABLE IF NOT EXISTS `lab_technicians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `technician_id` varchar(30) DEFAULT NULL COMMENT 'e.g. LAB-TECH-001',
  `full_name` varchar(150) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `nationality` varchar(80) DEFAULT 'Ghanaian',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `secondary_phone` varchar(20) DEFAULT NULL,
  `personal_email` varchar(150) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Ghana',
  `postal_code` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(500) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_issuing_body` varchar(200) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `sub_specialization` varchar(200) DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation` varchar(100) DEFAULT 'Lab Technician',
  `years_of_experience` int DEFAULT '0',
  `bio` text,
  `languages_spoken` json DEFAULT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `office_location` varchar(100) DEFAULT NULL,
  `availability_status` enum('Available','Busy','On Break','Off Duty') DEFAULT 'Available',
  `two_fa_enabled` tinyint(1) DEFAULT '0',
  `shift_preference_notes` text,
  `status` enum('Active','Inactive','On Leave','Suspended') DEFAULT 'Active',
  `member_since` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `institution_attended` varchar(200) DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgraduate_details` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lab_tech_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_certifications`
--

DROP TABLE IF EXISTS `lab_technician_certifications`;
CREATE TABLE IF NOT EXISTS `lab_technician_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `certification_name` varchar(200) NOT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certificate_file` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_documents`
--

DROP TABLE IF EXISTS `lab_technician_documents`;
CREATE TABLE IF NOT EXISTS `lab_technician_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `document_type` varchar(50) DEFAULT 'Other',
  `file_size` int DEFAULT '0',
  `description` text,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_profile_completeness`
--

DROP TABLE IF EXISTS `lab_technician_profile_completeness`;
CREATE TABLE IF NOT EXISTS `lab_technician_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `personal_info` tinyint(1) DEFAULT '0',
  `professional_profile` tinyint(1) DEFAULT '0',
  `qualifications` tinyint(1) DEFAULT '0',
  `documents_uploaded` tinyint(1) DEFAULT '0',
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `security_setup` tinyint(1) DEFAULT '0',
  `completeness_percentage` int DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_qualifications`
--

DROP TABLE IF EXISTS `lab_technician_qualifications`;
CREATE TABLE IF NOT EXISTS `lab_technician_qualifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `degree_name` varchar(200) NOT NULL,
  `institution` varchar(200) NOT NULL,
  `year_awarded` int DEFAULT NULL,
  `certificate_file` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_sessions`
--

DROP TABLE IF EXISTS `lab_technician_sessions`;
CREATE TABLE IF NOT EXISTS `lab_technician_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `device` varchar(200) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_settings`
--

DROP TABLE IF EXISTS `lab_technician_settings`;
CREATE TABLE IF NOT EXISTS `lab_technician_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `notification_preferences` json DEFAULT NULL,
  `theme_preference` varchar(20) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en',
  `alert_sound_enabled` tinyint(1) DEFAULT '1',
  `notif_new_order` tinyint(1) DEFAULT '1',
  `notif_critical_result` tinyint(1) DEFAULT '1',
  `notif_equipment_alert` tinyint(1) DEFAULT '1',
  `notif_reagent_alert` tinyint(1) DEFAULT '1',
  `notif_qc_reminder` tinyint(1) DEFAULT '1',
  `notif_doctor_msg` tinyint(1) DEFAULT '1',
  `notif_system` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notif_stat_order` tinyint(1) DEFAULT '1',
  `notif_reagent_expiry` tinyint(1) DEFAULT '1',
  `notif_result_amend` tinyint(1) DEFAULT '1',
  `notif_license_expiry` tinyint(1) DEFAULT '1',
  `notif_shift_reminder` tinyint(1) DEFAULT '1',
  `preferred_channel` varchar(50) DEFAULT 'In-Dashboard',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tech` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

DROP TABLE IF EXISTS `lab_tests`;
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `technician_id` int DEFAULT NULL,
  `urgency_level` enum('Routine','Urgent','Critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Routine',
  `test_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_date` date NOT NULL,
  `results` text COLLATE utf8mb4_unicode_ci,
  `result_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Submitted','In Progress','Completed','Reviewed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `cost` decimal(10,2) DEFAULT '0.00',
  `technician_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_id` (`test_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_test_id` (`test_id`),
  KEY `idx_status` (`status`),
  KEY `fk_labtest_technician` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_test_catalog`
--

DROP TABLE IF EXISTS `lab_test_catalog`;
CREATE TABLE IF NOT EXISTS `lab_test_catalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_name` varchar(200) NOT NULL,
  `test_code` varchar(50) DEFAULT NULL,
  `category` enum('Hematology','Biochemistry','Microbiology','Immunology','Urinalysis','Histology','Radiology','Parasitology','Serology','Other') DEFAULT 'Other',
  `sample_type` enum('Blood','Urine','Stool','Swab','Tissue','CSF','Sputum','Other') DEFAULT 'Blood',
  `container_type` varchar(100) DEFAULT NULL,
  `collection_instructions` text,
  `processing_time_hours` decimal(5,1) DEFAULT '1.0',
  `normal_turnaround_hours` decimal(5,1) DEFAULT '24.0',
  `price` decimal(10,2) DEFAULT '0.00',
  `requires_fasting` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_test_code` (`test_code`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_test_catalog`
--

INSERT INTO `lab_test_catalog` (`id`, `test_name`, `test_code`, `category`, `sample_type`, `container_type`, `collection_instructions`, `processing_time_hours`, `normal_turnaround_hours`, `price`, `requires_fasting`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Complete Blood Count (CBC)', 'CBC-001', 'Hematology', 'Blood', 'EDTA (Purple Top)', NULL, 1.0, 4.0, 50.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(2, 'Blood Glucose (Fasting)', 'GLU-001', 'Biochemistry', 'Blood', 'Fluoride (Grey Top)', NULL, 0.5, 2.0, 30.00, 1, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(3, 'Blood Glucose (Random)', 'GLU-002', 'Biochemistry', 'Blood', 'Fluoride (Grey Top)', NULL, 0.5, 2.0, 30.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(4, 'Liver Function Test (LFT)', 'LFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', NULL, 2.0, 6.0, 80.00, 1, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(5, 'Renal Function Test (RFT)', 'RFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', NULL, 2.0, 6.0, 80.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(6, 'Lipid Profile', 'LIP-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', NULL, 2.0, 6.0, 80.00, 1, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(7, 'Urinalysis', 'URN-001', 'Urinalysis', 'Urine', 'Sterile Container', NULL, 1.0, 3.0, 25.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(8, 'Malaria Parasite Test (MP)', 'MAL-001', 'Parasitology', 'Blood', 'EDTA (Purple Top)', NULL, 0.5, 1.0, 20.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(9, 'Stool Routine Examination', 'STL-001', 'Parasitology', 'Stool', 'Stool Container', NULL, 1.0, 4.0, 25.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(10, 'Blood Culture & Sensitivity', 'BCX-001', 'Microbiology', 'Blood', 'Blood Culture Bottle', NULL, 48.0, 72.0, 150.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(11, 'Urine Culture & Sensitivity', 'UCX-001', 'Microbiology', 'Urine', 'Sterile Container', NULL, 24.0, 48.0, 100.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(12, 'HIV Rapid Test', 'HIV-001', 'Serology', 'Blood', 'EDTA (Purple Top)', NULL, 0.5, 1.0, 40.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(13, 'Hepatitis B Surface Antigen', 'HBS-001', 'Serology', 'Blood', 'SST (Yellow Top)', NULL, 1.0, 4.0, 50.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(14, 'Hepatitis C Antibody', 'HCV-001', 'Serology', 'Blood', 'SST (Yellow Top)', NULL, 1.0, 4.0, 50.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(15, 'Widal Test', 'WDL-001', 'Serology', 'Blood', 'SST (Yellow Top)', NULL, 1.0, 4.0, 35.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(16, 'Pregnancy Test (Urine)', 'PRG-001', 'Immunology', 'Urine', 'Sterile Container', NULL, 0.3, 0.5, 15.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(17, 'Chest X-Ray', 'CXR-001', 'Radiology', 'Other', 'N/A', NULL, 0.5, 2.0, 100.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(18, 'Thyroid Function Test (TFT)', 'TFT-001', 'Biochemistry', 'Blood', 'SST (Yellow Top)', NULL, 4.0, 24.0, 120.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(19, 'Electrolytes (Na/K/Cl)', 'ELY-001', 'Biochemistry', 'Blood', 'Heparin (Green Top)', NULL, 1.0, 4.0, 60.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(20, 'Erythrocyte Sedimentation Rate', 'ESR-001', 'Hematology', 'Blood', 'Citrate (Blue Top)', NULL, 1.0, 2.0, 25.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(21, 'Prothrombin Time (PT/INR)', 'PTI-001', 'Hematology', 'Blood', 'Citrate (Blue Top)', NULL, 1.0, 4.0, 60.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(22, 'HbA1c (Glycated Hemoglobin)', 'HBA-001', 'Biochemistry', 'Blood', 'EDTA (Purple Top)', NULL, 2.0, 6.0, 90.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(23, 'Semen Analysis', 'SEM-001', 'Other', 'Other', 'Sterile Container', NULL, 2.0, 4.0, 80.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(24, 'CSF Analysis', 'CSF-001', 'Biochemistry', 'CSF', 'Sterile Tube', NULL, 2.0, 6.0, 150.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(25, 'Sputum AFB (TB Test)', 'AFB-001', 'Microbiology', 'Sputum', 'Sputum Container', NULL, 24.0, 72.0, 60.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `lab_test_orders`
--

DROP TABLE IF EXISTS `lab_test_orders`;
CREATE TABLE IF NOT EXISTS `lab_test_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` varchar(30) NOT NULL COMMENT 'e.g. LTO-A1B2C3D4',
  `request_id` int DEFAULT NULL COMMENT 'FK to lab_tests.id from doctor',
  `patient_id` int NOT NULL,
  `doctor_id` int DEFAULT NULL,
  `technician_id` int DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `test_catalog_id` int DEFAULT NULL,
  `test_name` varchar(200) NOT NULL,
  `urgency` enum('Routine','Urgent','STAT','Critical') DEFAULT 'Routine',
  `order_date` date NOT NULL,
  `required_by_date` date DEFAULT NULL,
  `clinical_notes` text,
  `diagnosis` varchar(500) DEFAULT NULL,
  `order_status` enum('Pending','Accepted','Rejected','Sample Collected','Processing','Completed','Cancelled') DEFAULT 'Pending',
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_technician` (`technician_id`),
  KEY `idx_status` (`order_status`),
  KEY `idx_urgency` (`urgency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_workload_log`
--

DROP TABLE IF EXISTS `lab_workload_log`;
CREATE TABLE IF NOT EXISTS `lab_workload_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `technician_id` int NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` varchar(30) DEFAULT NULL,
  `total_orders_received` int DEFAULT '0',
  `total_completed` int DEFAULT '0',
  `total_pending` int DEFAULT '0',
  `total_rejected` int DEFAULT '0',
  `total_critical_results` int DEFAULT '0',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech_date` (`technician_id`,`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laundry_batches`
--

DROP TABLE IF EXISTS `laundry_batches`;
CREATE TABLE IF NOT EXISTS `laundry_batches` (
  `batch_id` int NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(50) NOT NULL,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `requested_by` varchar(150) NOT NULL COMMENT 'ward/department',
  `batch_type` enum('bed linen','patient gown','staff uniform','theatre','other') NOT NULL,
  `item_count` int DEFAULT '0',
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `collection_status` enum('pending','collected') DEFAULT 'pending',
  `washing_status` enum('pending','in progress','completed') DEFAULT 'pending',
  `ironing_status` enum('pending','in progress','completed') DEFAULT 'pending',
  `delivery_status` enum('pending','delivered') DEFAULT 'pending',
  `damaged_items_count` int DEFAULT '0',
  `contaminated_items_count` int DEFAULT '0',
  `collected_at` datetime DEFAULT NULL,
  `washing_started_at` datetime DEFAULT NULL,
  `washing_completed_at` datetime DEFAULT NULL,
  `ironing_completed_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  UNIQUE KEY `uk_batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laundry_damage_reports`
--

DROP TABLE IF EXISTS `laundry_damage_reports`;
CREATE TABLE IF NOT EXISTS `laundry_damage_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `batch_id` int DEFAULT NULL,
  `staff_id` int NOT NULL,
  `item_type` varchar(100) NOT NULL,
  `quantity_damaged` int NOT NULL,
  `damage_description` text,
  `photo_path` varchar(255) DEFAULT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('reported','acknowledged','written off') DEFAULT 'reported',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laundry_inventory`
--

DROP TABLE IF EXISTS `laundry_inventory`;
CREATE TABLE IF NOT EXISTS `laundry_inventory` (
  `inventory_id` int NOT NULL AUTO_INCREMENT,
  `item_type` varchar(150) NOT NULL,
  `total_quantity` int DEFAULT '0',
  `available_quantity` int DEFAULT '0',
  `in_wash_quantity` int DEFAULT '0',
  `damaged_quantity` int DEFAULT '0',
  `condemned_quantity` int DEFAULT '0',
  `reorder_level` int DEFAULT '50',
  `last_updated_by` int DEFAULT NULL COMMENT 'staff ID',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL,
  `failure_reason` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_success` (`success`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_medicines`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `low_stock_medicines`;
CREATE TABLE IF NOT EXISTS `low_stock_medicines` (
`medicine_id` varchar(50)
,`medicine_name` varchar(200)
,`category` varchar(100)
,`total_stock` decimal(32,0)
,`reorder_level` int
);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
CREATE TABLE IF NOT EXISTS `maintenance_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `request_id` int DEFAULT NULL,
  `staff_id` int NOT NULL,
  `action_taken` text NOT NULL,
  `time_spent_hours` decimal(5,2) DEFAULT '0.00',
  `parts_used` json DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `reported_by` varchar(150) NOT NULL COMMENT 'user ID and role',
  `assigned_to` int DEFAULT NULL COMMENT 'staff ID nullable',
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
  `completion_notes` text,
  `completion_images_path` json DEFAULT NULL,
  `admin_verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_attachments`
--

DROP TABLE IF EXISTS `medical_attachments`;
CREATE TABLE IF NOT EXISTS `medical_attachments` (
  `attachment_id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `diagnosis` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `treatment` text COLLATE utf8mb4_unicode_ci,
  `treatment_plan` text COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL COMMENT 'JSON array of file paths',
  `severity` enum('Mild','Moderate','Severe','Critical') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_visible` tinyint(1) DEFAULT '1',
  `vital_signs` json DEFAULT NULL,
  `lab_results` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_visit_date` (`visit_date`),
  KEY `idx_mr_patient_date` (`patient_id`,`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medication_administration`
--

DROP TABLE IF EXISTS `medication_administration`;
CREATE TABLE IF NOT EXISTS `medication_administration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int DEFAULT NULL,
  `prescription_item_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `medicine_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'oral, IV, IM, SC, topical, etc.',
  `scheduled_time` datetime DEFAULT NULL,
  `administered_at` datetime DEFAULT NULL,
  `status` enum('Pending','Administered','Missed','Refused','Held','Late') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `reason_not_given` text COLLATE utf8mb4_unicode_ci COMMENT 'For missed/refused/held',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `verified_by` enum('Barcode','Manual','Double-Check') COLLATE utf8mb4_unicode_ci DEFAULT 'Manual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medadmin_patient` (`patient_id`),
  KEY `idx_medadmin_nurse` (`nurse_id`),
  KEY `idx_medadmin_status` (`status`),
  KEY `idx_medadmin_time` (`scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medication_schedules`
--

DROP TABLE IF EXISTS `medication_schedules`;
CREATE TABLE IF NOT EXISTS `medication_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_item_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `nurse_id_assigned` int DEFAULT NULL,
  `medicine_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. TDS, BD, OD, QID',
  `scheduled_times` json DEFAULT NULL COMMENT '["08:00","14:00","20:00"]',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled','Paused') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medsched_patient` (`patient_id`),
  KEY `idx_medsched_nurse` (`nurse_id_assigned`),
  KEY `idx_medsched_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

DROP TABLE IF EXISTS `medicines`;
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicine_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medicine_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `storage_instructions` text COLLATE utf8mb4_unicode_ci,
  `side_effects` text COLLATE utf8mb4_unicode_ci,
  `contraindications` text COLLATE utf8mb4_unicode_ci,
  `unit_price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'tablet',
  `reorder_level` int DEFAULT '10',
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_prescription_required` tinyint(1) DEFAULT '1',
  `status` enum('active','discontinued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_id` (`medicine_id`),
  KEY `idx_medicine_id` (`medicine_id`),
  KEY `idx_medicine_name` (`medicine_name`),
  KEY `idx_stock` (`stock_quantity`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_id`, `medicine_name`, `generic_name`, `category`, `manufacturer`, `supplier_name`, `description`, `storage_instructions`, `side_effects`, `contraindications`, `unit_price`, `stock_quantity`, `unit`, `reorder_level`, `expiry_date`, `batch_number`, `is_prescription_required`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MED001', 'Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, 0.50, 500, 'tablet', 50, NULL, NULL, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'MED002', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, 0.75, 300, 'tablet', 50, NULL, NULL, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(3, 'MED003', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'MedCare', NULL, NULL, NULL, NULL, NULL, 2.00, 200, 'tablet', 30, NULL, NULL, 1, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(4, 'MED004', 'Vitamin C 1000mg', 'Ascorbic Acid', 'Vitamin', 'HealthPlus', NULL, NULL, NULL, NULL, NULL, 1.00, 400, 'tablet', 50, NULL, NULL, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(5, 'MED005', 'Omeprazole 20mg', 'Omeprazole', 'Antacid', 'MedCare', NULL, NULL, NULL, NULL, NULL, 1.50, 150, 'tablet', 30, NULL, NULL, 1, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21');

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_inventory`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `medicine_inventory`;
CREATE TABLE IF NOT EXISTS `medicine_inventory` (
`id` int
,`medicine_id` varchar(50)
,`medicine_name` varchar(200)
,`generic_name` varchar(200)
,`category` varchar(100)
,`unit` varchar(50)
,`unit_price` decimal(10,2)
,`stock_quantity` int
,`reorder_level` int
,`expiry_date` date
,`supplier_name` varchar(200)
,`manufacturer` varchar(200)
,`batch_number` varchar(100)
,`is_prescription_required` tinyint(1)
,`stock_status` varchar(13)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_module` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_notif_user_read` (`user_id`,`is_read`),
  KEY `idx_notif_role_type` (`user_role`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

DROP TABLE IF EXISTS `nurses`;
CREATE TABLE IF NOT EXISTS `nurses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nurse_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Display ID e.g. NRS-001',
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `street_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghana',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_issuing_body` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `specialization` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT 'Nursing',
  `designation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Head Nurse, Staff Nurse, Charge Nurse',
  `years_of_experience` int DEFAULT '0',
  `nursing_school` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgrad_training` text COLLATE utf8mb4_unicode_ci,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `shift_type` enum('Morning','Afternoon','Night') COLLATE utf8mb4_unicode_ci DEFAULT 'Morning',
  `ward_assigned` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability_status` enum('Online','Offline','On Break','In Emergency') COLLATE utf8mb4_unicode_ci DEFAULT 'Offline',
  `status` enum('Active','Inactive','On Leave','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `national_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_completion` int DEFAULT '0' COMMENT '0-100 percentage',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `religion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `two_fa_enabled` tinyint(1) DEFAULT '0',
  `shift_preference_notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nurse_user` (`user_id`),
  UNIQUE KEY `uk_nurse_id` (`nurse_id`),
  KEY `idx_nurse_status` (`status`),
  KEY `idx_nurse_dept` (`department`),
  KEY `idx_nurse_shift` (`shift_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`id`, `user_id`, `nurse_id`, `full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `secondary_phone`, `email`, `personal_email`, `address`, `street_address`, `city`, `region`, `country`, `postal_code`, `profile_photo`, `license_number`, `license_issuing_body`, `license_expiry`, `specialization`, `department`, `designation`, `years_of_experience`, `nursing_school`, `graduation_year`, `postgrad_training`, `bio`, `shift_type`, `ward_assigned`, `availability_status`, `status`, `national_id`, `marital_status`, `office_location`, `profile_completion`, `created_at`, `updated_at`, `religion`, `member_since`, `last_login`, `two_fa_enabled`, `shift_preference_notes`) VALUES
(1, 8, 'NRS-001', 'Test Nurse', NULL, NULL, NULL, '0201234567', NULL, 'nurse@rmu.edu.gh', NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, NULL, NULL, NULL, NULL, NULL, 'Nursing', 'Staff Nurse', 0, NULL, NULL, NULL, NULL, 'Morning', NULL, 'Offline', 'Active', NULL, NULL, NULL, 0, '2026-03-03 15:38:33', '2026-03-03 15:38:33', NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nurse_activity_log`
--

DROP TABLE IF EXISTS `nurse_activity_log`;
CREATE TABLE IF NOT EXISTS `nurse_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `action_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'login, update, create, delete, etc.',
  `action_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nal_nurse` (`nurse_id`),
  KEY `idx_nal_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_certifications`
--

DROP TABLE IF EXISTS `nurse_certifications`;
CREATE TABLE IF NOT EXISTS `nurse_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `cert_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_body` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cert_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `certificate_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nc_nurse` (`nurse_id`),
  KEY `idx_nc_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_doctor_messages`
--

DROP TABLE IF EXISTS `nurse_doctor_messages`;
CREATE TABLE IF NOT EXISTS `nurse_doctor_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL COMMENT 'user_id',
  `sender_role` enum('Nurse','Doctor','Admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_id` int NOT NULL COMMENT 'user_id',
  `receiver_role` enum('Nurse','Doctor','Admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int DEFAULT NULL COMMENT 'Optional context',
  `subject` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_urgent` tinyint(1) DEFAULT '0',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  KEY `idx_msg_read` (`is_read`),
  KEY `idx_msg_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_documents`
--

DROP TABLE IF EXISTS `nurse_documents`;
CREATE TABLE IF NOT EXISTS `nurse_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `file_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL COMMENT 'bytes',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nd_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_notifications`
--

DROP TABLE IF EXISTS `nurse_notifications`;
CREATE TABLE IF NOT EXISTS `nurse_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Task','Vital Alert','Medication Reminder','Emergency','Doctor Message','Shift','System','Patient Update') COLLATE utf8mb4_unicode_ci DEFAULT 'System',
  `is_read` tinyint(1) DEFAULT '0',
  `related_module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'vitals, tasks, medications, etc.',
  `related_id` int DEFAULT NULL COMMENT 'ID in the related module',
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nn_nurse` (`nurse_id`),
  KEY `idx_nn_read` (`is_read`),
  KEY `idx_nn_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_professional_profile`
--

DROP TABLE IF EXISTS `nurse_professional_profile`;
CREATE TABLE IF NOT EXISTS `nurse_professional_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `sub_specialization` varchar(200) DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation` varchar(100) DEFAULT 'Staff Nurse',
  `years_of_experience` int DEFAULT '0',
  `license_number` varchar(100) DEFAULT NULL,
  `license_issuing_body` varchar(200) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `nursing_school` varchar(200) DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgraduate_details` text,
  `languages_spoken` json DEFAULT NULL,
  `bio` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nurse_prof` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_profile_completeness`
--

DROP TABLE IF EXISTS `nurse_profile_completeness`;
CREATE TABLE IF NOT EXISTS `nurse_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `personal_info` tinyint(1) DEFAULT '0',
  `professional_profile` tinyint(1) DEFAULT '0',
  `qualifications` tinyint(1) DEFAULT '0',
  `documents_uploaded` tinyint(1) DEFAULT '0',
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `security_setup` tinyint(1) DEFAULT '0',
  `overall_pct` int DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_npc_nurse` (`nurse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nurse_profile_completeness`
--

INSERT INTO `nurse_profile_completeness` (`id`, `nurse_id`, `personal_info`, `professional_profile`, `qualifications`, `documents_uploaded`, `photo_uploaded`, `security_setup`, `overall_pct`, `last_updated`) VALUES
(1, 1, 0, 0, 0, 0, 0, 0, 0, '2026-03-03 15:38:33');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_qualifications`
--

DROP TABLE IF EXISTS `nurse_qualifications`;
CREATE TABLE IF NOT EXISTS `nurse_qualifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `degree_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_awarded` int DEFAULT NULL,
  `cert_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `certificate_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nq_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_sessions`
--

DROP TABLE IF EXISTS `nurse_sessions`;
CREATE TABLE IF NOT EXISTS `nurse_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `device_info` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '1',
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nsess_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_settings`
--

DROP TABLE IF EXISTS `nurse_settings`;
CREATE TABLE IF NOT EXISTS `nurse_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `notification_preferences` json DEFAULT NULL COMMENT '{"tasks":true,"vitals":true,"meds":true,"emergency":true}',
  `theme_preference` enum('light','dark') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `alert_sound_enabled` tinyint(1) DEFAULT '1',
  `auto_refresh_interval` int DEFAULT '30' COMMENT 'seconds',
  `preferred_channel` enum('dashboard','email','sms','all') COLLATE utf8mb4_unicode_ci DEFAULT 'dashboard',
  `notif_new_task` tinyint(1) DEFAULT '1',
  `notif_vital_alert` tinyint(1) DEFAULT '1',
  `notif_medication` tinyint(1) DEFAULT '1',
  `notif_emergency` tinyint(1) DEFAULT '1',
  `notif_doctor_msg` tinyint(1) DEFAULT '1',
  `notif_shift_change` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notif_task_overdue` tinyint(1) DEFAULT '1',
  `notif_med_reminder` tinyint(1) DEFAULT '1',
  `notif_vital_due` tinyint(1) DEFAULT '1',
  `notif_abnormal_vital` tinyint(1) DEFAULT '1',
  `notif_shift_reminder` tinyint(1) DEFAULT '1',
  `notif_handover` tinyint(1) DEFAULT '1',
  `notif_cert_expiry` tinyint(1) DEFAULT '1',
  `notif_system` tinyint(1) DEFAULT '1',
  `preferred_notif_lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `critical_sound_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ns_nurse` (`nurse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nurse_settings`
--

INSERT INTO `nurse_settings` (`id`, `nurse_id`, `notification_preferences`, `theme_preference`, `language`, `alert_sound_enabled`, `auto_refresh_interval`, `preferred_channel`, `notif_new_task`, `notif_vital_alert`, `notif_medication`, `notif_emergency`, `notif_doctor_msg`, `notif_shift_change`, `updated_at`, `notif_task_overdue`, `notif_med_reminder`, `notif_vital_due`, `notif_abnormal_vital`, `notif_shift_reminder`, `notif_handover`, `notif_cert_expiry`, `notif_system`, `preferred_notif_lang`, `critical_sound_enabled`) VALUES
(1, 1, NULL, 'light', 'English', 1, 30, 'dashboard', 1, 1, 1, 1, 1, 1, '2026-03-03 15:38:33', 1, 1, 1, 1, 1, 1, 1, 1, 'en', 1);

-- --------------------------------------------------------

--
-- Table structure for table `nurse_shifts`
--

DROP TABLE IF EXISTS `nurse_shifts`;
CREATE TABLE IF NOT EXISTS `nurse_shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `shift_type` enum('Morning','Afternoon','Night') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `ward_assigned` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Scheduled','Active','Completed','Missed','Swapped') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `handover_submitted` tinyint(1) DEFAULT '0',
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shift_nurse` (`nurse_id`),
  KEY `idx_shift_date` (`shift_date`),
  KEY `idx_shift_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_tasks`
--

DROP TABLE IF EXISTS `nurse_tasks`;
CREATE TABLE IF NOT EXISTS `nurse_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `assigned_by` int NOT NULL COMMENT 'user_id of doctor or admin',
  `assigned_by_role` enum('Doctor','Admin','Nurse') COLLATE utf8mb4_unicode_ci DEFAULT 'Doctor',
  `patient_id` int DEFAULT NULL,
  `task_title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_description` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `due_time` datetime DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task_nurse` (`nurse_id`),
  KEY `idx_task_status` (`status`),
  KEY `idx_task_priority` (`priority`),
  KEY `idx_task_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_notes`
--

DROP TABLE IF EXISTS `nursing_notes`;
CREATE TABLE IF NOT EXISTS `nursing_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `note_type` enum('General','Observation','Wound','Behavior','Incident','Handoff','Pain','Assessment') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `note_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL COMMENT '[{"file":"path","name":"filename"}]',
  `is_locked` tinyint(1) DEFAULT '0' COMMENT 'Locked after shift ends',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_note_nurse` (`nurse_id`),
  KEY `idx_note_patient` (`patient_id`),
  KEY `idx_note_shift` (`shift_id`),
  KEY `idx_note_type` (`note_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

DROP TABLE IF EXISTS `password_history`;
CREATE TABLE IF NOT EXISTS `password_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `patient_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_student` tinyint(1) DEFAULT '0',
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `chronic_conditions` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_provider` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_status` enum('Active','Inactive','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghanaian',
  `religion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghana',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '0',
  `profile_completion` tinyint UNSIGNED DEFAULT '0',
  `account_status` enum('active','deactivation_requested','deactivated') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `patient_type` enum('Student','Teacher','Staff','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admit_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_activity_log`
--

DROP TABLE IF EXISTS `patient_activity_log`;
CREATE TABLE IF NOT EXISTS `patient_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_documents`
--

DROP TABLE IF EXISTS `patient_documents`;
CREATE TABLE IF NOT EXISTS `patient_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int UNSIGNED DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  `document_category` enum('Medical Report','Insurance Card','National ID','Passport','Lab Report','Other') DEFAULT 'Other',
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_education`
--

DROP TABLE IF EXISTS `patient_education`;
CREATE TABLE IF NOT EXISTS `patient_education` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `education_topic` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Medication, Diet, Wound Care, Exercise, Disease Management',
  `method` enum('Verbal','Written','Demonstration','Video','Combination') COLLATE utf8mb4_unicode_ci DEFAULT 'Verbal',
  `materials_provided` json DEFAULT NULL COMMENT '["pamphlet.pdf","video_link"]',
  `understanding_level` enum('Good','Fair','Poor') COLLATE utf8mb4_unicode_ci DEFAULT 'Good',
  `requires_follow_up` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edu_patient` (`patient_id`),
  KEY `idx_edu_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_insurance`
--

DROP TABLE IF EXISTS `patient_insurance`;
CREATE TABLE IF NOT EXISTS `patient_insurance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `provider_name` varchar(150) DEFAULT NULL,
  `policy_number` varchar(80) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `coverage_type` enum('Individual','Family') DEFAULT 'Individual',
  `payment_preference` enum('Cash','Insurance','Mobile Money') DEFAULT 'Cash',
  `billing_address` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pi_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_medical_profile`
--

DROP TABLE IF EXISTS `patient_medical_profile`;
CREATE TABLE IF NOT EXISTS `patient_medical_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `height_cm` decimal(5,1) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `bmi_category` varchar(30) DEFAULT NULL,
  `allergies` json DEFAULT NULL,
  `chronic_conditions` json DEFAULT NULL,
  `disabilities` text,
  `current_medications` json DEFAULT NULL,
  `vaccination_history` json DEFAULT NULL,
  `family_medical_history` json DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pmp_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_profile_completeness`
--

DROP TABLE IF EXISTS `patient_profile_completeness`;
CREATE TABLE IF NOT EXISTS `patient_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `personal_info` tinyint(1) DEFAULT '0',
  `medical_profile` tinyint(1) DEFAULT '0',
  `emergency_contact` tinyint(1) DEFAULT '0',
  `insurance_info` tinyint(1) DEFAULT '0',
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `security_setup` tinyint(1) DEFAULT '0',
  `documents_uploaded` tinyint(1) DEFAULT '0',
  `overall_percentage` tinyint UNSIGNED DEFAULT '0',
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppc_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_sessions`
--

DROP TABLE IF EXISTS `patient_sessions`;
CREATE TABLE IF NOT EXISTS `patient_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(128) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_settings`
--

DROP TABLE IF EXISTS `patient_settings`;
CREATE TABLE IF NOT EXISTS `patient_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL COMMENT 'patients.id',
  `email_notifications` tinyint(1) DEFAULT '1',
  `sms_notifications` tinyint(1) DEFAULT '0',
  `appointment_reminders` tinyint(1) DEFAULT '1',
  `prescription_alerts` tinyint(1) DEFAULT '1',
  `lab_result_alerts` tinyint(1) DEFAULT '1',
  `medical_record_alerts` tinyint(1) DEFAULT '1',
  `profile_visibility` enum('public','doctors_only','private') COLLATE utf8mb4_unicode_ci DEFAULT 'doctors_only',
  `language_preference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `preferred_channel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'dashboard',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ps_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_vitals`
--

DROP TABLE IF EXISTS `patient_vitals`;
CREATE TABLE IF NOT EXISTS `patient_vitals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bp_systolic` int DEFAULT NULL COMMENT 'mmHg',
  `bp_diastolic` int DEFAULT NULL COMMENT 'mmHg',
  `pulse_rate` int DEFAULT NULL COMMENT 'bpm',
  `temperature` decimal(4,1) DEFAULT NULL COMMENT '??C',
  `oxygen_saturation` int DEFAULT NULL COMMENT 'SpO2 %',
  `respiratory_rate` int DEFAULT NULL COMMENT 'breaths/min',
  `blood_glucose` decimal(5,1) DEFAULT NULL COMMENT 'mg/dL',
  `weight` decimal(5,1) DEFAULT NULL COMMENT 'kg',
  `height` decimal(5,1) DEFAULT NULL COMMENT 'cm',
  `bmi` decimal(4,1) DEFAULT NULL COMMENT 'Auto-calculated: weight/(height/100)^2',
  `pain_level` int DEFAULT NULL COMMENT '0-10 scale',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_flagged` tinyint(1) DEFAULT '0',
  `flag_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_notified` tinyint(1) DEFAULT '0',
  `doctor_notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vital_patient` (`patient_id`),
  KEY `idx_vital_nurse` (`nurse_id`),
  KEY `idx_vital_time` (`recorded_at`),
  KEY `idx_vital_flagged` (`is_flagged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `payment_type` enum('Consultation','Medication','Lab Test','Bed Charge','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Mobile Money','Card','Insurance','Free') COLLATE utf8mb4_unicode_ci DEFAULT 'Cash',
  `payment_date` datetime NOT NULL,
  `status` enum('Pending','Paid','Completed','Overdue','Refunded','Failed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_activity_log`
--

DROP TABLE IF EXISTS `pharmacist_activity_log`;
CREATE TABLE IF NOT EXISTS `pharmacist_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `action_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `action_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pal_pharmacist` (`pharmacist_id`),
  KEY `idx_pal_type` (`action_type`),
  KEY `idx_pal_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_activity_log`
--

INSERT INTO `pharmacist_activity_log` (`id`, `pharmacist_id`, `action_type`, `action_description`, `ip_address`, `device_info`, `created_at`) VALUES
(1, 2, 'report', 'Generated report: inventory_status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 13:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_certifications`
--

DROP TABLE IF EXISTS `pharmacist_certifications`;
CREATE TABLE IF NOT EXISTS `pharmacist_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `cert_name` varchar(300) NOT NULL,
  `issuing_body` varchar(300) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cert_file_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pharmacist_id` (`pharmacist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_documents`
--

DROP TABLE IF EXISTS `pharmacist_documents`;
CREATE TABLE IF NOT EXISTS `pharmacist_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `file_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int NOT NULL DEFAULT '0',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pd_pharmacist` (`pharmacist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_profile`
--

DROP TABLE IF EXISTS `pharmacist_profile`;
CREATE TABLE IF NOT EXISTS `pharmacist_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `specialization` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Pharmacy',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghana',
  `profile_photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `years_of_experience` int NOT NULL DEFAULT '0',
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability_status` enum('Online','Offline','Busy') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Offline',
  `profile_completion` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pharmacy_school` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgrad_training` text COLLATE utf8mb4_unicode_ci,
  `license_issuing_body` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pharm_user` (`user_id`),
  KEY `idx_pharm_license` (`license_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_profile`
--

INSERT INTO `pharmacist_profile` (`id`, `user_id`, `full_name`, `license_number`, `license_expiry`, `specialization`, `department`, `phone`, `secondary_phone`, `email`, `address`, `city`, `region`, `country`, `profile_photo`, `bio`, `years_of_experience`, `nationality`, `national_id`, `date_of_birth`, `gender`, `marital_status`, `availability_status`, `profile_completion`, `created_at`, `updated_at`, `postal_code`, `office_location`, `pharmacy_school`, `graduation_year`, `postgrad_training`, `license_issuing_body`, `personal_email`, `street_address`) VALUES
(1, 6, 'Nelly Nartey', NULL, NULL, NULL, 'Pharmacy', '0501234567', NULL, 'nelly.nartey@st.rmu.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 7, 'Adjei Adelaide Naa Adjeley', NULL, NULL, NULL, 'Pharmacy', '0507333138', NULL, 'es-anadjei@st.umat.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_profile_completeness`
--

DROP TABLE IF EXISTS `pharmacist_profile_completeness`;
CREATE TABLE IF NOT EXISTS `pharmacist_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `personal_info` tinyint(1) NOT NULL DEFAULT '0',
  `professional_profile` tinyint(1) NOT NULL DEFAULT '0',
  `qualifications` tinyint(1) NOT NULL DEFAULT '0',
  `photo_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `security_setup` tinyint(1) NOT NULL DEFAULT '0',
  `documents_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `overall_pct` int NOT NULL DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ppc_pharmacist` (`pharmacist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_profile_completeness`
--

INSERT INTO `pharmacist_profile_completeness` (`id`, `pharmacist_id`, `personal_info`, `professional_profile`, `qualifications`, `photo_uploaded`, `security_setup`, `documents_uploaded`, `overall_pct`, `last_updated`) VALUES
(1, 1, 0, 0, 0, 0, 0, 0, 0, '2026-03-02 11:25:17'),
(2, 2, 0, 0, 0, 0, 0, 0, 0, '2026-03-02 11:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_qualifications`
--

DROP TABLE IF EXISTS `pharmacist_qualifications`;
CREATE TABLE IF NOT EXISTS `pharmacist_qualifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `degree_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_awarded` int DEFAULT NULL,
  `cert_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pq_pharmacist` (`pharmacist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_sessions`
--

DROP TABLE IF EXISTS `pharmacist_sessions`;
CREATE TABLE IF NOT EXISTS `pharmacist_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ps_pharmacist` (`pharmacist_id`),
  KEY `idx_ps_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_inventory`
--

DROP TABLE IF EXISTS `pharmacy_inventory`;
CREATE TABLE IF NOT EXISTS `pharmacy_inventory` (
  `inventory_id` int NOT NULL AUTO_INCREMENT,
  `medicine_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity_received` int NOT NULL DEFAULT '0',
  `quantity_dispensed` int NOT NULL DEFAULT '0',
  `quantity_sold` int NOT NULL DEFAULT '0',
  `quantity_expired` int NOT NULL DEFAULT '0',
  `current_stock` int NOT NULL DEFAULT '0',
  `received_date` date NOT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('in_stock','low_stock','out_of_stock','expired','expiring_soon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`),
  KEY `fk_inventory_medicine` (`medicine_id`),
  KEY `fk_inventory_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_reports`
--

DROP TABLE IF EXISTS `pharmacy_reports`;
CREATE TABLE IF NOT EXISTS `pharmacy_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generated_by` int NOT NULL,
  `report_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` json DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format` enum('PDF','CSV','XLSX') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PDF',
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_user` (`generated_by`),
  KEY `idx_pr_type` (`report_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacy_reports`
--

INSERT INTO `pharmacy_reports` (`id`, `generated_by`, `report_type`, `parameters`, `file_path`, `format`, `generated_at`) VALUES
(1, 7, 'inventory_status', '{\"end_date\": \"2026-03-12\", \"start_date\": \"2026-03-01\"}', 'uploads/pharmacy_reports/inventory_status_20260312_133955.csv', 'CSV', '2026-03-12 13:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_settings`
--

DROP TABLE IF EXISTS `pharmacy_settings`;
CREATE TABLE IF NOT EXISTS `pharmacy_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `notif_new_prescription` tinyint(1) NOT NULL DEFAULT '1',
  `notif_low_stock` tinyint(1) NOT NULL DEFAULT '1',
  `notif_expiring_meds` tinyint(1) NOT NULL DEFAULT '1',
  `notif_purchase_orders` tinyint(1) NOT NULL DEFAULT '1',
  `notif_refill_requests` tinyint(1) NOT NULL DEFAULT '1',
  `notif_system_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `notification_prefs` json DEFAULT NULL,
  `preferred_channel` enum('dashboard','email','sms','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dashboard',
  `theme_preference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `language` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'English',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pharm_settings` (`pharmacist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacy_settings`
--

INSERT INTO `pharmacy_settings` (`id`, `pharmacist_id`, `notif_new_prescription`, `notif_low_stock`, `notif_expiring_meds`, `notif_purchase_orders`, `notif_refill_requests`, `notif_system_alerts`, `notification_prefs`, `preferred_channel`, `theme_preference`, `language`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, NULL, 'dashboard', 'light', 'English', '2026-03-02 11:25:17'),
(2, 2, 1, 1, 1, 1, 1, 1, NULL, 'dashboard', 'light', 'English', '2026-03-02 11:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_suppliers`
--

DROP TABLE IF EXISTS `pharmacy_suppliers`;
CREATE TABLE IF NOT EXISTS `pharmacy_suppliers` (
  `supplier_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `supply_categories` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_terms` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT 'Net 30',
  `rating` decimal(2,1) DEFAULT '0.0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`),
  KEY `idx_supplier_name` (`supplier_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `pharmacist_id` int DEFAULT NULL,
  `medical_record_id` int DEFAULT NULL,
  `prescription_date` date NOT NULL,
  `medication_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `quantity` int NOT NULL,
  `refills_allowed` int DEFAULT '0',
  `refill_count` int DEFAULT '0',
  `status` enum('Pending','Dispensed','Partially Dispensed','Cancelled','Expired') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `patient_notified` tinyint(1) DEFAULT '0',
  `dispensed_by` int DEFAULT NULL,
  `dispensed_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescription_id` (`prescription_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `medical_record_id` (`medical_record_id`),
  KEY `dispensed_by` (`dispensed_by`),
  KEY `idx_prescription_id` (`prescription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rx_patient_status` (`patient_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
CREATE TABLE IF NOT EXISTS `prescription_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `dispensed_quantity` int DEFAULT '0',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `substitution_allowed` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','dispensed','partially_dispensed','cancelled','out_of_stock') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_prescription_id` (`prescription_id`),
  KEY `idx_medicine_id` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_refills`
--

DROP TABLE IF EXISTS `prescription_refills`;
CREATE TABLE IF NOT EXISTS `prescription_refills` (
  `refill_id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Approved','Rejected','Dispensed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`refill_id`),
  KEY `idx_prescription_id` (`prescription_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `ordered_by` int NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `status` enum('draft','sent','received','partially_received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_number` (`order_number`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `idx_po_status` (`status`),
  KEY `idx_po_date` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `ordered_quantity` int NOT NULL DEFAULT '0',
  `received_quantity` int NOT NULL DEFAULT '0',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','received','partial','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_poi_order` (`order_id`),
  KEY `idx_poi_medicine` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reagent_inventory`
--

DROP TABLE IF EXISTS `reagent_inventory`;
CREATE TABLE IF NOT EXISTS `reagent_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `catalog_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'pcs',
  `quantity_in_stock` int DEFAULT '0',
  `reorder_level` int DEFAULT '5',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL,
  `storage_conditions` varchar(200) DEFAULT NULL,
  `linked_equipment_id` int DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Expiring Soon') DEFAULT 'In Stock',
  `batch_number` varchar(100) DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reagent_transactions`
--

DROP TABLE IF EXISTS `reagent_transactions`;
CREATE TABLE IF NOT EXISTS `reagent_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reagent_id` int NOT NULL,
  `transaction_type` enum('Received','Used','Disposed','Adjusted') DEFAULT 'Received',
  `quantity` int NOT NULL,
  `previous_quantity` int DEFAULT '0',
  `new_quantity` int DEFAULT '0',
  `performed_by` int DEFAULT NULL COMMENT 'FK to lab_technicians.id',
  `linked_order_id` int DEFAULT NULL COMMENT 'FK to lab_test_orders.id',
  `notes` text,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reagent` (`reagent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE IF NOT EXISTS `security_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `incident_type` enum('visitor check','access control','incident report','patrol log','other') NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('logged','escalated','resolved') DEFAULT 'logged',
  `escalated_to` int DEFAULT NULL COMMENT 'admin ID nullable',
  `notes` text,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `is_free_for_students` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_id` (`service_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_service_name` (`service_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_id`, `service_name`, `description`, `category`, `price`, `is_free_for_students`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SRV001', 'General Consultation', 'General medical consultation with a doctor', 'Consultation', 50.00, 1, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'SRV002', 'Emergency Care', '24/7 emergency medical services', 'Emergency', 0.00, 1, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(3, 'SRV003', 'Health Checkup', 'Comprehensive health screening', 'Preventive', 0.00, 1, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(4, 'SRV004', 'Laboratory Tests', 'Diagnostic laboratory services', 'Diagnostic', 30.00, 0, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(5, 'SRV005', 'Pharmacy Services', 'Medication dispensing', 'Pharmacy', 0.00, 0, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(6, 'SRV006', 'Ambulance Service', 'Emergency ambulance transport', 'Emergency', 100.00, 0, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(7, 'SRV007', 'Bed Facility', 'Inpatient bed accommodation', 'Inpatient', 80.00, 0, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(8, 'SRV008', 'Mental Health Counseling', 'Professional counseling services', 'Mental Health', 0.00, 1, 1, '2026-02-06 05:09:21', '2026-02-06 05:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `shift_handover`
--

DROP TABLE IF EXISTS `shift_handover`;
CREATE TABLE IF NOT EXISTS `shift_handover` (
  `id` int NOT NULL AUTO_INCREMENT,
  `outgoing_nurse_id` int NOT NULL,
  `incoming_nurse_id` int DEFAULT NULL,
  `shift_id` int DEFAULT NULL,
  `ward` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_summaries` json DEFAULT NULL COMMENT '[{"patient_id":1,"name":"...","status":"...","notes":"..."}]',
  `pending_tasks` json DEFAULT NULL COMMENT '[{"task":"...","priority":"High","patient":"..."}]',
  `critical_patients_noted` text COLLATE utf8mb4_unicode_ci,
  `handover_notes` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `acknowledged` tinyint(1) DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ho_outgoing` (`outgoing_nurse_id`),
  KEY `idx_ho_incoming` (`incoming_nurse_id`),
  KEY `idx_ho_shift` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_queue`
--

DROP TABLE IF EXISTS `sms_queue`;
CREATE TABLE IF NOT EXISTS `sms_queue` (
  `sms_id` int NOT NULL AUTO_INCREMENT,
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Sent','Failed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sms_id`),
  KEY `idx_status` (`status`),
  KEY `idx_recipient_phone` (`recipient_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `staff_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT '0.00',
  `shift` enum('Morning','Afternoon','Night','Rotating') COLLATE utf8mb4_unicode_ci DEFAULT 'Morning',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` enum('full-time','part-time','contract') COLLATE utf8mb4_unicode_ci DEFAULT 'full-time',
  `shift_type` enum('morning','afternoon','night','rotating') COLLATE utf8mb4_unicode_ci DEFAULT 'morning',
  `status` enum('active','inactive','on leave','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `date_joined` date DEFAULT NULL,
  `emergency_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_completeness` tinyint UNSIGNED DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  UNIQUE KEY `uk_employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_department` (`department`),
  KEY `idx_staff_approval` (`approval_status`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

DROP TABLE IF EXISTS `staff_activity_log`;
CREATE TABLE IF NOT EXISTS `staff_activity_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_approval_log`
--

DROP TABLE IF EXISTS `staff_approval_log`;
CREATE TABLE IF NOT EXISTS `staff_approval_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int UNSIGNED NOT NULL,
  `admin_user_id` int UNSIGNED NOT NULL,
  `action` enum('approved','rejected','revoked') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `actioned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sal_staff` (`staff_id`),
  KEY `idx_sal_admin` (`admin_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `check_in_method` enum('manual','system') DEFAULT 'system',
  `status` enum('present','absent','late','early departure','on leave') DEFAULT 'present',
  `notes` text,
  `recorded_by` varchar(50) DEFAULT 'system' COMMENT 'admin ID or system',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_audit_trail`
--

DROP TABLE IF EXISTS `staff_audit_trail`;
CREATE TABLE IF NOT EXISTS `staff_audit_trail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--

DROP TABLE IF EXISTS `staff_departments`;
CREATE TABLE IF NOT EXISTS `staff_departments` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text,
  `head_of_department` int DEFAULT NULL COMMENT 'staff ID nullable',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_directory`
--

DROP TABLE IF EXISTS `staff_directory`;
CREATE TABLE IF NOT EXISTS `staff_directory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL COMMENT 'users.id ??? if staff has a login account',
  `full_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Doctor','Nurse','Lab Technician','Pharmacist','Admin','Receptionist','Support Staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `status` enum('Active','On Leave','Inactive','Suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_role` (`role`),
  KEY `idx_department` (`department`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_documents`
--

DROP TABLE IF EXISTS `staff_documents`;
CREATE TABLE IF NOT EXISTS `staff_documents` (
  `document_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `description` text,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_requests`
--

DROP TABLE IF EXISTS `staff_leave_requests`;
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
  `leave_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type` enum('annual','sick','emergency','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL COMMENT 'admin ID',
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text,
  PRIMARY KEY (`leave_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_messages`
--

DROP TABLE IF EXISTS `staff_messages`;
CREATE TABLE IF NOT EXISTS `staff_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `sender_role` varchar(50) NOT NULL,
  `receiver_id` int NOT NULL COMMENT 'staff ID',
  `subject` varchar(255) DEFAULT NULL,
  `message_content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_notifications`
--

DROP TABLE IF EXISTS `staff_notifications`;
CREATE TABLE IF NOT EXISTS `staff_notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `message` text NOT NULL,
  `type` enum('task','alert','shift','emergency','system','message','maintenance') NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `related_module` varchar(100) DEFAULT NULL,
  `related_record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_performance`
--

DROP TABLE IF EXISTS `staff_performance`;
CREATE TABLE IF NOT EXISTS `staff_performance` (
  `performance_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `period` enum('daily','weekly','monthly') NOT NULL,
  `period_date` date NOT NULL,
  `tasks_assigned` int DEFAULT '0',
  `tasks_completed` int DEFAULT '0',
  `tasks_overdue` int DEFAULT '0',
  `attendance_score` decimal(5,2) DEFAULT NULL,
  `punctuality_score` decimal(5,2) DEFAULT NULL,
  `quality_score` decimal(5,2) DEFAULT NULL COMMENT 'admin rated',
  `overall_rating` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `rated_by` int DEFAULT NULL COMMENT 'admin ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` decimal(3,1) DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `review_notes` text,
  `review_date` date DEFAULT NULL,
  `kpi_score` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`performance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profile_completeness`
--

DROP TABLE IF EXISTS `staff_profile_completeness`;
CREATE TABLE IF NOT EXISTS `staff_profile_completeness` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `personal_info_complete` tinyint(1) DEFAULT '0',
  `documents_uploaded` tinyint(1) DEFAULT '0',
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `security_setup_complete` tinyint(1) DEFAULT '0',
  `overall_percentage` decimal(5,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`),
  UNIQUE KEY `uk_staff_comp` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_qualifications`
--

DROP TABLE IF EXISTS `staff_qualifications`;
CREATE TABLE IF NOT EXISTS `staff_qualifications` (
  `qualification_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `certificate_name` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `year_awarded` int NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`qualification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
CREATE TABLE IF NOT EXISTS `staff_roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_slug` enum('ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') NOT NULL,
  `role_display_name` varchar(100) NOT NULL,
  `role_description` text,
  `icon_class` varchar(100) DEFAULT 'fas fa-user-tie',
  `dashboard_file_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_role_slug` (`role_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`role_id`, `role_slug`, `role_display_name`, `role_description`, `icon_class`, `dashboard_file_path`, `is_active`, `created_at`) VALUES
(1, 'ambulance_driver', 'Ambulance Driver', 'Manages trips, vehicles, and emergency transport', 'fas fa-ambulance', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53'),
(2, 'cleaner', 'Hospital Cleaner', 'Manages ward cleaning schedules and sanitation logs', 'fas fa-broom', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53'),
(3, 'laundry_staff', 'Laundry Staff', 'Manages hospital linen, washing batches, and inventory', 'fas fa-tshirt', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53'),
(4, 'maintenance', 'Maintenance Technician', 'Handles facility repairs, equipment, and work orders', 'fas fa-tools', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53'),
(5, 'security', 'Security Personnel', 'Manages access logs, ward patrols, and incident reports', 'fas fa-shield-alt', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53'),
(6, 'kitchen_staff', 'Kitchen & Catering', 'Manages patient dietary meals and food delivery', 'fas fa-utensils', 'dashboards/staff_dashboard.php', 1, '2026-03-14 05:42:53');

-- --------------------------------------------------------

--
-- Table structure for table `staff_sessions`
--

DROP TABLE IF EXISTS `staff_sessions`;
CREATE TABLE IF NOT EXISTS `staff_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `device_info` text,
  `browser` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_settings`
--

DROP TABLE IF EXISTS `staff_settings`;
CREATE TABLE IF NOT EXISTS `staff_settings` (
  `settings_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `theme_preference` enum('light','dark') DEFAULT 'light',
  `language` varchar(50) DEFAULT 'en',
  `notification_preferences` json DEFAULT NULL,
  `alert_sound_enabled` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`settings_id`),
  UNIQUE KEY `uk_staff_settings` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

DROP TABLE IF EXISTS `staff_shifts`;
CREATE TABLE IF NOT EXISTS `staff_shifts` (
  `shift_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `shift_type` varchar(50) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location_ward_assigned` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','active','completed','missed','swapped') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shift_id`),
  KEY `idx_staff_shift` (`staff_id`,`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

DROP TABLE IF EXISTS `staff_tasks`;
CREATE TABLE IF NOT EXISTS `staff_tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `assigned_by` int DEFAULT NULL COMMENT 'admin ID or system',
  `task_title` varchar(255) NOT NULL,
  `task_description` text,
  `task_category` enum('cleaning','laundry','maintenance','transport','security','kitchen','general') NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `location` varchar(255) DEFAULT NULL COMMENT 'ward/room/area',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `status` enum('pending','in progress','completed','overdue','cancelled') DEFAULT 'pending',
  `completion_notes` text,
  `completion_photo_path` varchar(255) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_task_checklists`
--

DROP TABLE IF EXISTS `staff_task_checklists`;
CREATE TABLE IF NOT EXISTS `staff_task_checklists` (
  `checklist_id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `checklist_item` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_by` int DEFAULT NULL COMMENT 'staff ID',
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`checklist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

DROP TABLE IF EXISTS `stock_alerts`;
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicine_id` int NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','expiring_soon','expired') COLLATE utf8mb4_unicode_ci NOT NULL,
  `threshold_value` int DEFAULT '0',
  `current_value` int DEFAULT '0',
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_by` int DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alert_medicine` (`medicine_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_alert_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicine_id` int NOT NULL,
  `inventory_id` int DEFAULT NULL,
  `transaction_type` enum('restock','dispensed','expired','adjusted','returned','damaged') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `previous_quantity` int NOT NULL DEFAULT '0',
  `new_quantity` int NOT NULL DEFAULT '0',
  `performed_by` int NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stx_medicine` (`medicine_id`),
  KEY `idx_stx_type` (`transaction_type`),
  KEY `idx_stx_date` (`transaction_date`),
  KEY `idx_stx_user` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`id`, `config_key`, `config_value`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'site_name', 'RMU Medical Sickbay', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(2, 'site_email', '', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(3, 'site_phone', '', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(4, 'site_address', '', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(5, 'appointment_duration', '30', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(6, 'max_appointments_per_day', '20', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(7, 'enable_email_notifications', '1', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(8, 'enable_sms_notifications', '0', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(9, 'maintenance_mode', '0', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(10, 'session_timeout', '60', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(11, 'currency', 'GHS', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(12, 'timezone', 'Africa/Accra', '2026-02-20 20:38:46', '2026-02-20 20:38:46', NULL),
(13, 'smtp_host', 'smtp.gmail.com', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1),
(14, 'smtp_port', '587', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1),
(15, 'smtp_username', 'sickbay.text@st.rmu.edu.gh', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1),
(16, 'smtp_from', 'sickbay.txt@rmu.edu.gh', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1),
(17, 'smtp_password', 'aHFyciBra2F0IHJ1cWcgbnV0Zg==', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'RMU Medical Sickbay', 'string', 'Name of the medical facility', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(2, 'site_email', 'Sickbay.txt@rmu.edu.gh', 'string', 'Primary contact email', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(3, 'site_phone', '0502371207', 'string', 'Primary contact phone', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(4, 'emergency_hotline', '153', 'string', 'Emergency contact number', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(5, 'appointment_duration', '30', 'number', 'Default appointment duration in minutes', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(6, 'working_hours_start', '08:00', 'string', 'Working hours start time', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(7, 'working_hours_end', '17:00', 'string', 'Working hours end time', 1, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(8, 'max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(9, 'session_timeout', '3600', 'number', 'Session timeout in seconds', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(10, 'password_min_length', '8', 'number', 'Minimum password length', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(11, 'password_expiry_days', '90', 'number', 'Password expiration in days', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(12, 'enable_2fa', '0', 'boolean', 'Enable two-factor authentication', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(13, 'email_notifications', '1', 'boolean', 'Enable email notifications', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(14, 'sms_notifications', '0', 'boolean', 'Enable SMS notifications', 0, NULL, '2026-02-08 02:23:55', '2026-02-08 02:23:55'),
(15, 'system_name', 'RMU Medical Sickbay', 'string', 'System display name', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(16, 'timezone', 'Africa/Accra', 'string', 'Default timezone', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(17, 'date_format', 'd M Y', 'string', 'Date display format', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(18, 'currency', 'GHS', 'string', 'Currency code', 0, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(19, 'currency_symbol', 'GH?', 'string', 'Currency symbol', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(20, 'default_reorder_level', '10', 'number', 'Default medicine reorder threshold', 0, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(21, 'expiry_warning_days', '30', 'number', 'Days before expiry to trigger warning', 0, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(22, 'stock_alert_threshold', '5', 'number', 'Units below which stock alert fires', 0, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(23, 'password_change_days', '90', 'number', 'Force password change interval (days)', 0, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(24, 'default_theme', 'light', 'string', 'Default UI theme', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10'),
(25, 'system_language', 'English', 'string', 'Default language', 1, NULL, '2026-03-03 04:10:10', '2026-03-03 04:10:10');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
CREATE TABLE IF NOT EXISTS `tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_code` varchar(20) DEFAULT NULL,
  `category` enum('Blood','Urine','Radiology','Pathology','Cardiology','Microbiology','Other') NOT NULL DEFAULT 'Other',
  `description` text,
  `test_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `duration_mins` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `T_ID` (`id`),
  UNIQUE KEY `idx_test_code` (`test_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `test_code`, `category`, `description`, `test_name`, `price`, `duration_mins`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'STL-001', 'Pathology', NULL, 'Stool R/E', 15.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(2, 'BLD-001', 'Blood', NULL, 'Sickling Test', 20.00, 45, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(3, 'BLD-002', 'Blood', NULL, 'Blood Grouping', 15.00, 30, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(4, 'BLD-003', 'Blood', NULL, 'Widal Test (Typhoid)', 25.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(5, 'BLD-004', 'Blood', NULL, 'Hemoglobin Levels (Hb)', 20.00, 30, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(6, 'BLD-005', 'Blood', NULL, 'Fasting and Random Blood Sugar', 20.00, 30, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(7, 'MIC-001', 'Microbiology', NULL, 'Hepatitis B Screening', 30.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(8, 'MIC-002', 'Microbiology', NULL, 'Hepatitis C Screening', 30.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(9, 'MIC-003', 'Microbiology', NULL, 'HIV Screening', 35.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(10, 'MIC-004', 'Microbiology', NULL, 'Syphilis Screening', 25.00, 45, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(11, 'MIC-005', 'Microbiology', NULL, 'Hepatitis A Screening', 30.00, 60, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38'),
(12, 'URN-001', 'Urine', NULL, 'Urine Pregnancy Test', 15.00, 15, 1, '2026-02-20 19:28:38', '2026-02-20 19:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_auth`
--

DROP TABLE IF EXISTS `two_factor_auth`;
CREATE TABLE IF NOT EXISTS `two_factor_auth` (
  `tfa_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) DEFAULT '0',
  `backup_codes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tfa_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse','lab_technician','ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`user_name`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`user_role`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `email`, `password`, `user_role`, `name`, `phone`, `gender`, `date_of_birth`, `profile_image`, `is_active`, `is_verified`, `created_at`, `updated_at`, `last_login`, `last_active_at`, `last_login_at`, `locked_until`) VALUES
(1, 'admin', 'admin@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '0502371207', NULL, NULL, 'default-avatar.png', 1, 1, '2026-02-06 05:09:21', '2026-03-15 22:31:31', '2026-03-14 17:41:18', NULL, NULL, NULL),
(4, 'LJ', 'lovelace.baidoo@st.rmu.edu.gh', '$2y$10$o1PxWO6siYsmVuWdtLgpEOaijwF.wbWK4hmaNV3cGprmUNR7It5.O', 'patient', 'Lovelace John Kwaku Baidoo', '0257669095', NULL, NULL, 'default-avatar.png', 1, 0, '2026-02-06 07:01:51', '2026-02-27 03:16:10', '2026-02-27 03:16:10', NULL, NULL, NULL),
(5, 'EC', '', '$2y$10$V/IRP.0WWfBfOOxCHPO2u.ahsW/jBO8OTSg3OOrvMMHboZzor47KG', 'doctor', 'EC', '', NULL, NULL, 'default-avatar.png', 1, 0, '2026-02-06 07:18:53', '2026-03-12 13:36:51', '2026-03-12 13:36:51', NULL, NULL, NULL),
(6, 'Neils', 'nelly.nartey@st.rmu.edu.gh', '$2y$10$HnDpNL4Ct61jF96vrWCaDe0EdcM67C.jlWhZAtw66PY42a/.YLEs.', 'pharmacist', 'Nelly Nartey', '0501234567', NULL, NULL, 'default-avatar.png', 1, 0, '2026-02-06 07:25:39', '2026-02-06 07:26:46', '2026-02-06 07:26:46', NULL, NULL, NULL),
(7, 'Naa', 'es-anadjei@st.umat.edu.gh', '$2y$10$BiJxGbxJ/3VccsXMzCN2Fe.1Y8Wg/HLiJ.ci/RhXI7qbV7kqllAHa', 'pharmacist', 'Adjei Adelaide Naa Adjeley', '0507333138', NULL, NULL, 'default-avatar.png', 1, 0, '2026-02-15 09:36:42', '2026-03-12 13:39:03', '2026-03-12 13:39:03', NULL, NULL, NULL),
(8, 'nurse_test', 'nurse@rmu.edu.gh', '$2y$12$LJ3m5bHpXQ8e9Y1f8g5vGuQzW7RjE5cUvNjGfkeT8x5QfPAcjDSmK', 'nurse', 'Test Nurse', '0201234567', NULL, NULL, 'default-avatar.png', 1, 1, '2026-03-03 15:38:33', '2026-03-03 15:38:33', NULL, NULL, NULL, NULL),
(10, 'cleaner1', 'cleaner@rmu.edu', '$2y$10$J9o0KKdRTKRfiFmDSrTtFOpreCNVF6uO4Y9C.AiWrbgIVaL4elarG', 'cleaner', 'Test Cleaner', NULL, NULL, NULL, 'default-avatar.png', 1, 0, '2026-03-14 05:19:48', '2026-03-14 05:19:48', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_time` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `logout_time` datetime DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`session_id`, `user_id`, `user_role`, `login_time`, `last_activity`, `ip_address`, `user_agent`, `is_active`, `logout_time`) VALUES
('00053b852103ab812c19f76ef21d6ae12bffb242eb91d1fdc39f27dd0241a6f4', 1, 'admin', '2026-02-16 07:39:38', '2026-02-16 07:39:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 07:51:52'),
('01727bd23e38b21753096fd1a6164800ef28bfe8dd82c102eab5d0339c5acd9d', 5, 'doctor', '2026-02-15 15:52:00', '2026-02-15 15:52:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 15:54:15'),
('0701be3ffd20200b33516228d366270f0bfabbc3be5498b0bcacd28a523307fd', 4, 'patient', '2026-02-15 15:29:09', '2026-02-15 15:41:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 15:41:42'),
('0a658ddb5ff459a0d7cb2b9d1c19c1707ee890d5fb710a5ff79e5548886fe3aa', 4, 'patient', '2026-02-06 07:11:35', '2026-02-06 07:11:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-12 06:25:05'),
('0ee83b16d87a5ffd144b665c8397817859e5f884ca8a456e790648174d0eeddc', 5, 'doctor', '2026-02-27 02:54:35', '2026-02-27 02:54:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 02:57:02'),
('0f7726a6599b2fef0852c2e9452fed0119378aff2934dbc6a068a04f7c4a833a', 4, 'patient', '2026-02-16 13:13:45', '2026-02-16 13:13:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-16 13:16:31'),
('11a0489b3dbe14901283fab67aa63888aab19c76d9dd042b9c6c5de4b886943a', 1, 'admin', '2026-03-12 13:16:58', '2026-03-12 13:16:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:20:31'),
('160f7a2398d270ce69bebadccaea0503a14b8f67a62aa6d0409d74cd2992802a', 7, 'pharmacist', '2026-03-12 13:39:03', '2026-03-12 13:39:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:42:19'),
('19f578c5bd46f88defdec87fbcbba7d23cf26796d108ca042fc88c41e6d13477', 1, 'admin', '2026-03-14 17:41:18', '2026-03-14 17:41:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 1, NULL),
('1ef70e0b804e976ef4991f5b611f0b7254c5fee96b0dda3f4baa34cca11e5aa4', 5, 'doctor', '2026-02-16 12:38:47', '2026-02-16 12:38:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 12:40:59'),
('212e8ec73220ce4c67d0a55db3b2a35941a74befb997e383a39afa6110f245b2', 1, 'admin', '2026-02-16 07:51:52', '2026-02-16 07:51:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-20 18:34:22'),
('28f8e4946066b3a84065a0a4dfe2555fde921039919c7ee26888ae1839aad8d5', 7, 'pharmacist', '2026-02-16 05:44:03', '2026-02-16 05:44:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 05:48:15'),
('2fd65ea74b47a8200f74682d8d61b41cd33cdc3ea49a604a3be059e212d39f65', 1, 'admin', '2026-02-14 04:25:18', '2026-02-14 04:25:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 08:09:22'),
('33f732ba97cf57ece4509ce7bfc80c1cc36bd8cc82dec7c6c2edaafa1ef93101', 4, 'patient', '2026-02-12 06:25:05', '2026-02-12 06:25:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-12 06:27:46'),
('34b5f02399c71ca3226f7c0ccabe8c4e360ae66fc069cd38d0bf7f20e4f02441', 1, 'admin', '2026-03-14 04:38:47', '2026-03-14 04:38:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-14 04:41:33'),
('364e15bb927b3ca31793ef6982966920bc40ff89cdc3dd907a998b91170f252c', 7, 'pharmacist', '2026-02-15 09:49:36', '2026-02-15 09:55:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 09:56:46'),
('3e3995ade1fac3c0da276a6698d4e0e402b7040ad2576cb6bd95616d0ea89869', 1, 'admin', '2026-02-14 08:09:22', '2026-02-14 08:09:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 16:05:18'),
('4126dad1cdf665058b4dd4d5327945fd747e63921daf8f464de26cfa9eefecc7', 5, 'doctor', '2026-02-15 15:42:03', '2026-02-15 15:49:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 15:50:35'),
('4561f482d0a79f855433f07dd5837af4f757860e65395b4833e0f12fa06866f3', 4, 'patient', '2026-02-14 08:17:47', '2026-02-14 08:17:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 08:18:15'),
('4766b12b818862791291a58895a308694a29f7dff8c81fc8a69ec895ef2f9704', 5, 'doctor', '2026-02-07 04:21:12', '2026-02-07 04:21:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-07 04:21:16'),
('4c77d4b48813abc473bd9407e3baa2891f9cb6792207da90c0b42d64fd5eb79b', 7, 'pharmacist', '2026-02-16 13:17:57', '2026-02-16 13:17:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-16 13:20:05'),
('515b816427cc45d13a5fe538f089f3e38b89ef350b65c6ddcc6243fe9119a77f', 4, 'patient', '2026-02-16 05:42:30', '2026-02-16 05:42:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 05:42:45'),
('530a689c4d76082de45f0ab2a656f3b534bd338d68d36cdc5f53ad73a89e525c', 5, 'doctor', '2026-02-14 08:18:35', '2026-02-14 08:18:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 08:20:33'),
('53747aa01eb97130a6ee826edcea4d2e823517c615ca1a05a6a02e964fd70141', 1, 'admin', '2026-02-27 02:57:24', '2026-02-27 02:57:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 03:15:38'),
('54473515a51e47fa87a9f2c7a4be140a34ca881d3b6df82c3fb09e939991efa4', 1, 'admin', '2026-02-20 20:10:45', '2026-02-20 20:10:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-20 21:34:16'),
('5667cdd0b22593e02e5d26ab53eb4641c2da1f93fa87e47fa9ae2c565eeabd75', 1, 'admin', '2026-02-20 18:34:23', '2026-02-20 18:34:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-20 20:10:45'),
('58ff3315e9eee30989b77b7dc13a57de7f76eadcc1a2daeb65298900f83c247f', 1, 'admin', '2026-02-14 03:24:15', '2026-02-14 03:24:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 04:25:18'),
('5e99d750ef74c9c6f94a30b1a376b67194793da9f370ff5e3e1de8a8cfa85943', 7, 'pharmacist', '2026-02-16 17:19:13', '2026-02-16 17:19:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-16 17:19:49'),
('612bf464239725eae304ddf2feb3b2ee489279947f887bddf9c065694464d1b7', 5, 'doctor', '2026-03-12 13:14:22', '2026-03-12 13:14:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:16:29'),
('6456cb7302273c1eb5a91b7a0d8d5833c3b17a315d38104d9b9831f18542f700', 1, 'admin', '2026-02-15 16:05:18', '2026-02-15 16:05:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 05:49:20'),
('72648eeb9a3053f0e7cd9164a9305b86a81d3dde6fa815fb2ad3316d6431ac4c', 5, 'doctor', '2026-03-12 07:18:48', '2026-03-12 07:18:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:09:23'),
('772e55af4245051f3f2cd5f517ef06ba9c52591eb71103a6d6aafca4723a2a74', 5, 'doctor', '2026-03-01 14:04:20', '2026-03-01 14:04:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-01 14:27:26'),
('877f57bd64d7d04a35a551b1823d2ab6536a52233ade49565db8bafc2b6d4f05', 1, 'admin', '2026-03-14 07:01:12', '2026-03-14 07:01:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-14 17:41:18'),
('8bea170e89c8aa735b73f6ccb1e94127c5ba8761b87755264e1047d9bc867ec0', 7, 'pharmacist', '2026-03-03 15:24:26', '2026-03-03 15:24:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-03 15:27:14'),
('8f0de9567c4e2a55ee9e488492c25dde14bb7c796013b5c68ff166ba7a66e960', 5, 'doctor', '2026-02-06 07:20:57', '2026-02-06 07:20:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-07 04:07:26'),
('91d61c73b07a82de220db4ac646e585e1b5d8c066fa30401ee9bb3f10f15eb30', 5, 'doctor', '2026-02-16 05:48:40', '2026-02-16 05:48:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 05:48:53'),
('971cb21a48dfc78e8c727f39756d6b89603f430978d66e1ee79cbb25c59625ac', 1, 'admin', '2026-02-14 03:15:40', '2026-02-14 03:15:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 03:24:15'),
('9b9c628c7e1002e96aafea7d328232f0177f82658c50ef0f4feb23f999ae8fb8', 5, 'doctor', '2026-02-07 04:26:51', '2026-02-07 04:28:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-07 04:43:28'),
('a062cbdd0f4f2816ab4aa5847caea52680c0a4c0a587b44243e9f1033f2a7247', 7, 'pharmacist', '2026-02-27 03:52:28', '2026-02-27 03:52:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 04:53:23'),
('ac3a2406f545ffdc7ebc37710767b34df96123be30ef4dcab562d68e3ca5d546', 1, 'admin', '2026-02-20 22:29:44', '2026-02-20 22:29:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 02:57:24'),
('ad08bd5f245b66c290f42f4ad11732da1b474b48f11f551d0ec8e064fb8a9b7c', 1, 'admin', '2026-02-16 05:49:20', '2026-02-16 05:49:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 07:39:38'),
('b74231f1d5886602e9b250402ef413d77c93813cd862c57d16eacd7c9ef831ad', 4, 'patient', '2026-02-27 03:16:10', '2026-02-27 03:16:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 03:51:58'),
('bc65bd99669a089a701a2acd1ac8ef372059df06b51f82c6c14c24e97243eb50', 6, 'pharmacist', '2026-02-06 07:26:46', '2026-02-06 07:26:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-07 03:54:28'),
('ccd0443b149ff4223cda38a773081054dabfe5f712b643de919a50d010119455', 5, 'doctor', '2026-02-07 04:07:26', '2026-02-07 04:07:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-07 04:19:48'),
('ce69abe88ed7f5efda98109778e485aa80117caf1669a9d2892939a1a3139b20', 1, 'admin', '2026-03-01 09:22:47', '2026-03-01 09:22:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-01 09:24:02'),
('d0c7d44aeb68462eb1b7135ee83a7b50c007bc809f5ff9f81903f441276cf058', 5, 'doctor', '2026-02-12 06:23:03', '2026-02-12 06:23:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-12 06:24:36'),
('dee24e93838901238fed2a3b8533856965c7f69801bac8e66b457b43b6c9ea2f', 7, 'pharmacist', '2026-02-15 15:56:14', '2026-02-15 15:56:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 16:04:56'),
('e0c327bd7355782d82397f66d0c9364ee0aa2d94ec580ed366705c7a2accc717', 4, 'patient', '2026-02-14 08:21:17', '2026-02-14 08:21:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 08:23:50'),
('e80b20296538785035b9f72a66b836105935473b45f313ba1031dc3d162c666a', 7, 'pharmacist', '2026-02-16 07:53:07', '2026-02-16 07:53:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 07:53:22'),
('ead75377dd4e29a188b139ebbfce44cfcbd5bce3fb836ee34ec60d25cc5d6c1c', 7, 'pharmacist', '2026-02-16 12:36:54', '2026-02-16 12:36:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 12:38:25'),
('edc047d377517973c5c11c98c3c3d137db6d5d54855c4de5872091d557e60ccf', 7, 'pharmacist', '2026-02-15 09:37:23', '2026-02-15 09:48:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 09:49:36'),
('f77a890405ca0a2bae5b4385eb5520448db99b7eb92180dfeef54295efb3ace9', 4, 'patient', '2026-02-16 07:52:19', '2026-02-16 07:52:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 07:52:45'),
('fba5416e23165874d8333b83ec1ba1c4240d0ae8126492c8a7b1590b5729d906', 5, 'doctor', '2026-03-12 13:36:51', '2026-03-12 13:36:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:37:22'),
('fd4e7cbc858b9864d30d891344e2aebfba705f74358de131bfe52216649aa438', 7, 'pharmacist', '2026-02-19 12:52:01', '2026-02-19 12:52:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-19 12:55:24');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `vehicle_id` int NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(50) NOT NULL,
  `make` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `type` enum('ambulance','utility','other') DEFAULT 'ambulance',
  `fuel_type` varchar(50) DEFAULT NULL,
  `current_mileage` int DEFAULT '0',
  `status` enum('available','in use','maintenance','out of service') DEFAULT 'available',
  `assigned_driver_id` int DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `uk_reg_no` (`registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_maintenance_logs`
--

DROP TABLE IF EXISTS `vehicle_maintenance_logs`;
CREATE TABLE IF NOT EXISTS `vehicle_maintenance_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `reported_by` int NOT NULL COMMENT 'staff ID',
  `issue_description` text NOT NULL,
  `maintenance_type` enum('repair','service','inspection','fuel') NOT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `performed_by` varchar(150) DEFAULT NULL,
  `performed_at` datetime DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `status` enum('reported','in progress','resolved') DEFAULT 'reported',
  `images_path` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vital_thresholds`
--

DROP TABLE IF EXISTS `vital_thresholds`;
CREATE TABLE IF NOT EXISTS `vital_thresholds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vital_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bp_systolic, bp_diastolic, pulse_rate, temperature, etc.',
  `min_normal` decimal(6,1) NOT NULL,
  `max_normal` decimal(6,1) NOT NULL,
  `critical_low` decimal(6,1) DEFAULT NULL,
  `critical_high` decimal(6,1) DEFAULT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` int DEFAULT NULL COMMENT 'user_id of admin/doctor',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vital_type` (`vital_type`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vital_thresholds`
--

INSERT INTO `vital_thresholds` (`id`, `vital_type`, `min_normal`, `max_normal`, `critical_low`, `critical_high`, `unit`, `updated_by`, `updated_at`) VALUES
(1, 'bp_systolic', 90.0, 140.0, 70.0, 180.0, 'mmHg', NULL, '2026-03-03 15:37:43'),
(2, 'bp_diastolic', 60.0, 90.0, 40.0, 120.0, 'mmHg', NULL, '2026-03-03 15:37:43'),
(3, 'pulse_rate', 60.0, 100.0, 40.0, 150.0, 'bpm', NULL, '2026-03-03 15:37:43'),
(4, 'temperature', 36.1, 37.2, 35.0, 39.5, '°C', NULL, '2026-03-04 05:56:06'),
(5, 'oxygen_saturation', 95.0, 100.0, 90.0, 100.0, '%', NULL, '2026-03-03 15:37:43'),
(6, 'respiratory_rate', 12.0, 20.0, 8.0, 30.0, 'breaths/min', NULL, '2026-03-03 15:37:43'),
(7, 'blood_glucose', 70.0, 140.0, 50.0, 400.0, 'mg/dL', NULL, '2026-03-03 15:37:43'),
(8, 'pain_level', 0.0, 3.0, 0.0, 8.0, 'scale 0-10', NULL, '2026-03-03 15:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `wound_care_records`
--

DROP TABLE IF EXISTS `wound_care_records`;
CREATE TABLE IF NOT EXISTS `wound_care_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `wound_location` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wound_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'surgical, pressure, laceration, burn, etc.',
  `wound_description` text COLLATE utf8mb4_unicode_ci,
  `wound_images` json DEFAULT NULL COMMENT '["path1.jpg","path2.jpg"]',
  `wound_size_cm` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LxWxD',
  `care_provided` text COLLATE utf8mb4_unicode_ci,
  `dressing_type` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_care_due` datetime DEFAULT NULL,
  `healing_status` enum('Improving','Stable','Worsening','Healed') COLLATE utf8mb4_unicode_ci DEFAULT 'Stable',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wound_patient` (`patient_id`),
  KEY `idx_wound_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `active_prescriptions`
--
DROP TABLE IF EXISTS `active_prescriptions`;

DROP VIEW IF EXISTS `active_prescriptions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_prescriptions`  AS SELECT `p`.`id` AS `prescription_id`, `p`.`prescription_date` AS `prescription_date`, `p`.`status` AS `status`, `pat`.`full_name` AS `patient_name`, `d`.`full_name` AS `doctor_name`, count(`pi`.`item_id`) AS `total_items`, sum((case when (`pi`.`dispensed_quantity` >= `pi`.`quantity`) then 1 else 0 end)) AS `dispensed_items` FROM (((`prescriptions` `p` join `patients` `pat` on((`p`.`patient_id` = `pat`.`id`))) join `doctors` `d` on((`p`.`doctor_id` = `d`.`id`))) left join `prescription_items` `pi` on((`p`.`id` = `pi`.`prescription_id`))) WHERE (`p`.`status` in ('Pending','Partially Dispensed')) GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `bed_management`
--
DROP TABLE IF EXISTS `bed_management`;

DROP VIEW IF EXISTS `bed_management`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `bed_management`  AS SELECT `b`.`id` AS `bed_pk`, `b`.`bed_id` AS `bed_id`, `b`.`bed_number` AS `bed_number`, `b`.`ward` AS `ward`, `b`.`bed_type` AS `bed_type`, `b`.`status` AS `bed_status`, `b`.`daily_rate` AS `daily_rate`, `ba`.`id` AS `assignment_pk`, `ba`.`patient_id` AS `patient_id`, `ba`.`admission_date` AS `admission_date`, `ba`.`discharge_date` AS `discharge_date`, `ba`.`reason` AS `admission_reason`, `ba`.`status` AS `assignment_status`, `p`.`patient_id` AS `patient_ref_id`, `u`.`name` AS `patient_name`, `u`.`phone` AS `patient_phone` FROM (((`beds` `b` left join `bed_assignments` `ba` on(((`ba`.`bed_id` = `b`.`id`) and (`ba`.`status` = 'Active')))) left join `patients` `p` on((`ba`.`patient_id` = `p`.`id`))) left join `users` `u` on((`p`.`user_id` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `low_stock_medicines`
--
DROP TABLE IF EXISTS `low_stock_medicines`;

DROP VIEW IF EXISTS `low_stock_medicines`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `low_stock_medicines`  AS SELECT `m`.`medicine_id` AS `medicine_id`, `m`.`medicine_name` AS `medicine_name`, `m`.`category` AS `category`, sum(`pi`.`current_stock`) AS `total_stock`, `m`.`reorder_level` AS `reorder_level` FROM (`medicines` `m` left join `pharmacy_inventory` `pi` on((`m`.`medicine_id` = `pi`.`medicine_id`))) GROUP BY `m`.`medicine_id` HAVING ((`total_stock` < `m`.`reorder_level`) OR (`total_stock` is null)) ;

-- --------------------------------------------------------

--
-- Structure for view `medicine_inventory`
--
DROP TABLE IF EXISTS `medicine_inventory`;

DROP VIEW IF EXISTS `medicine_inventory`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_inventory`  AS SELECT `m`.`id` AS `id`, `m`.`medicine_id` AS `medicine_id`, `m`.`medicine_name` AS `medicine_name`, `m`.`generic_name` AS `generic_name`, `m`.`category` AS `category`, `m`.`unit` AS `unit`, `m`.`unit_price` AS `unit_price`, `m`.`stock_quantity` AS `stock_quantity`, `m`.`reorder_level` AS `reorder_level`, `m`.`expiry_date` AS `expiry_date`, `m`.`supplier_name` AS `supplier_name`, `m`.`manufacturer` AS `manufacturer`, `m`.`batch_number` AS `batch_number`, `m`.`is_prescription_required` AS `is_prescription_required`, (case when (`m`.`stock_quantity` = 0) then 'Out of Stock' when (`m`.`stock_quantity` <= `m`.`reorder_level`) then 'Low Stock' when ((`m`.`expiry_date` is not null) and (`m`.`expiry_date` <= (curdate() + interval 30 day)) and (`m`.`expiry_date` >= curdate())) then 'Expiring Soon' else 'In Stock' end) AS `stock_status`, `m`.`created_at` AS `created_at`, `m`.`updated_at` AS `updated_at` FROM `medicines` AS `m` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  ADD CONSTRAINT `ambulance_requests_ibfk_1` FOREIGN KEY (`ambulance_id`) REFERENCES `ambulances` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointment_reminders`
--
ALTER TABLE `appointment_reminders`
  ADD CONSTRAINT `fk_reminders_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bed_assignments`
--
ALTER TABLE `bed_assignments`
  ADD CONSTRAINT `bed_assignments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bed_assignments_ibfk_2` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discharge_instructions`
--
ALTER TABLE `discharge_instructions`
  ADD CONSTRAINT `fk_di_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_activity_log`
--
ALTER TABLE `doctor_activity_log`
  ADD CONSTRAINT `fk_dal_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `fk_da_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_certifications`
--
ALTER TABLE `doctor_certifications`
  ADD CONSTRAINT `fk_dc_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_documents`
--
ALTER TABLE `doctor_documents`
  ADD CONSTRAINT `fk_dd_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_leave_exceptions`
--
ALTER TABLE `doctor_leave_exceptions`
  ADD CONSTRAINT `fk_dle_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_patient_notes`
--
ALTER TABLE `doctor_patient_notes`
  ADD CONSTRAINT `fk_note_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_note_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_profile_completeness`
--
ALTER TABLE `doctor_profile_completeness`
  ADD CONSTRAINT `fk_dpc_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_qualifications`
--
ALTER TABLE `doctor_qualifications`
  ADD CONSTRAINT `fk_dq_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_reports`
--
ALTER TABLE `doctor_reports`
  ADD CONSTRAINT `fk_report_doctor` FOREIGN KEY (`generated_by`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_sessions`
--
ALTER TABLE `doctor_sessions`
  ADD CONSTRAINT `fk_dss_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_settings`
--
ALTER TABLE `doctor_settings`
  ADD CONSTRAINT `fk_dst_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_alerts`
--
ALTER TABLE `emergency_alerts`
  ADD CONSTRAINT `fk_ea_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `fk_ec_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fluid_balance`
--
ALTER TABLE `fluid_balance`
  ADD CONSTRAINT `fk_fb_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `isolation_records`
--
ALTER TABLE `isolation_records`
  ADD CONSTRAINT `fk_iso_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `iv_fluid_records`
--
ALTER TABLE `iv_fluid_records`
  ADD CONSTRAINT `fk_iv_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `fk_lab_results_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lab_results_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_technicians`
--
ALTER TABLE `lab_technicians`
  ADD CONSTRAINT `lab_technicians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `fk_labtest_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  ADD CONSTRAINT `fk_attachments_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medication_administration`
--
ALTER TABLE `medication_administration`
  ADD CONSTRAINT `fk_medadmin_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `fk_nurse_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_activity_log`
--
ALTER TABLE `nurse_activity_log`
  ADD CONSTRAINT `fk_nal_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_certifications`
--
ALTER TABLE `nurse_certifications`
  ADD CONSTRAINT `fk_nc_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_documents`
--
ALTER TABLE `nurse_documents`
  ADD CONSTRAINT `fk_nd_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_notifications`
--
ALTER TABLE `nurse_notifications`
  ADD CONSTRAINT `fk_nn_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_professional_profile`
--
ALTER TABLE `nurse_professional_profile`
  ADD CONSTRAINT `nurse_professional_profile_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_profile_completeness`
--
ALTER TABLE `nurse_profile_completeness`
  ADD CONSTRAINT `fk_npc_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_qualifications`
--
ALTER TABLE `nurse_qualifications`
  ADD CONSTRAINT `fk_nq_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_sessions`
--
ALTER TABLE `nurse_sessions`
  ADD CONSTRAINT `fk_nsess_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_settings`
--
ALTER TABLE `nurse_settings`
  ADD CONSTRAINT `fk_ns_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD CONSTRAINT `fk_shift_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_tasks`
--
ALTER TABLE `nurse_tasks`
  ADD CONSTRAINT `fk_task_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nursing_notes`
--
ALTER TABLE `nursing_notes`
  ADD CONSTRAINT `fk_note_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_activity_log`
--
ALTER TABLE `patient_activity_log`
  ADD CONSTRAINT `patient_activity_log_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_documents`
--
ALTER TABLE `patient_documents`
  ADD CONSTRAINT `patient_documents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_education`
--
ALTER TABLE `patient_education`
  ADD CONSTRAINT `fk_edu_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_insurance`
--
ALTER TABLE `patient_insurance`
  ADD CONSTRAINT `patient_insurance_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_medical_profile`
--
ALTER TABLE `patient_medical_profile`
  ADD CONSTRAINT `patient_medical_profile_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_profile_completeness`
--
ALTER TABLE `patient_profile_completeness`
  ADD CONSTRAINT `patient_profile_completeness_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_sessions`
--
ALTER TABLE `patient_sessions`
  ADD CONSTRAINT `patient_sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_settings`
--
ALTER TABLE `patient_settings`
  ADD CONSTRAINT `fk_ps_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_vitals`
--
ALTER TABLE `patient_vitals`
  ADD CONSTRAINT `fk_vital_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD CONSTRAINT `fk_inventory_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `pharmacy_suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_ibfk_4` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `fk_prescription_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prescription_items_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_refills`
--
ALTER TABLE `prescription_refills`
  ADD CONSTRAINT `fk_refills_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refills_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shift_handover`
--
ALTER TABLE `shift_handover`
  ADD CONSTRAINT `fk_ho_outgoing` FOREIGN KEY (`outgoing_nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_directory`
--
ALTER TABLE `staff_directory`
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wound_care_records`
--
ALTER TABLE `wound_care_records`
  ADD CONSTRAINT `fk_wound_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
