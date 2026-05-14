-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 14, 2026 at 04:56 PM
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
`dispensed_items` decimal(23,0)
,`doctor_name` varchar(150)
,`patient_name` varchar(150)
,`prescription_date` date
,`prescription_id` int
,`status` enum('Pending','Dispensed','Partially Dispensed','Cancelled','Expired')
,`total_items` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `active_sessions`
--

DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE IF NOT EXISTS `active_sessions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `user_role` varchar(50) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `device_info` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `last_active` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '1',
  `remember_me` tinyint(1) DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `logged_in_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session` (`session_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `active_sessions`
--

INSERT INTO `active_sessions` (`id`, `session_id`, `user_id`, `user_role`, `ip_address`, `device_info`, `browser`, `user_agent`, `last_active`, `is_current`, `remember_me`, `expires_at`, `logged_in_at`, `role`) VALUES
(9, '3pavgqcma9onk2hs17gfusefps', 29, 'lab_technician', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:13', 1, 0, NULL, '2026-03-31 13:48:13', NULL),
(10, 'pa9htmo7jukhedhcu90oedsq2e', 23, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:58', 1, 0, NULL, '2026-03-31 13:48:58', NULL),
(11, '09u4n6ach799vbrukgrkl08rd5', 17, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:57', 1, 0, NULL, '2026-03-31 13:50:57', NULL),
(15, '8eavkjpu49cjruaqg4htfndard', 21, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:21:09', 1, 0, NULL, '2026-03-31 14:21:09', NULL),
(16, 't3t5uh1c5okocr4hpq6icspa9v', 18, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:23:10', 1, 0, NULL, '2026-03-31 14:23:10', NULL),
(19, '6b2vdu4jcatifhv0bfusaii2gf', 14, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 08:58:48', 1, 0, NULL, '2026-04-04 08:58:48', NULL),
(20, 'qjovm2404or43s8t60n6qo4ds5', 16, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:03:26', 1, 0, NULL, '2026-04-04 09:03:26', NULL),
(49, 'roueba29lgo9r5ppk1fj74r2p7', 46, '', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:43:05', 1, 0, NULL, '2026-04-13 15:43:05', NULL),
(83, '4ov317m2sda8gmindbq8fapqfg', 28, 'lab_technician', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:02:53', 1, 0, NULL, '2026-04-20 04:02:53', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ambulance_requests`
--

INSERT INTO `ambulance_requests` (`id`, `request_id`, `patient_name`, `patient_phone`, `pickup_location`, `destination`, `emergency_type`, `ambulance_id`, `status`, `request_time`, `dispatch_time`, `completion_time`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'AMB-60540', 'Emmanuel Asante', '0241565000', '39 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Cardiac Arrest', NULL, 'Dispatched', '2026-04-07 01:53:27', NULL, NULL, 'Patient conscious', '2026-03-24 08:48:01', '2026-04-12 10:14:05'),
(2, 'AMB-38288', 'Kwame Acheampong', '0247681935', '26 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Stroke', NULL, 'Pending', '2026-03-22 07:00:52', NULL, NULL, 'Patient unconscious', '2026-03-31 03:49:52', '2026-04-12 10:14:05'),
(3, 'AMB-96800', 'Kwame Darko', '0248242030', '50 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Obstetric', NULL, 'Pending', '2026-03-28 15:34:43', NULL, NULL, 'Minor injury', '2026-03-07 19:26:33', '2026-04-12 10:14:05'),
(4, 'AMB-28795', 'Emmanuel Tawiah', '0246991126', '30 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Stroke', NULL, 'Pending', '2026-03-23 09:49:42', NULL, NULL, 'Minor injury', '2026-04-08 12:23:32', '2026-04-12 10:14:05'),
(5, 'AMB-40169', 'Daniel Adu', '0243965935', '50 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'General Medical', NULL, 'Dispatched', '2026-03-04 15:12:11', NULL, NULL, 'Patient conscious', '2026-03-09 06:07:37', '2026-04-12 10:14:05'),
(6, 'AMB-48152', 'Ama Amoah', '0241421215', '47 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Trauma', NULL, 'Pending', '2026-03-31 06:03:46', NULL, NULL, 'Patient unconscious', '2026-04-03 00:15:12', '2026-04-12 10:22:48'),
(7, 'AMB-65124', 'Eric Boateng', '0249519506', '20 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Obstetric', NULL, 'In Transit', '2026-03-31 18:22:54', NULL, NULL, 'Patient conscious', '2026-03-14 21:33:44', '2026-04-12 10:22:48'),
(8, 'AMB-35792', 'Daniel Bekoe', '0241061038', '34 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Obstetric', NULL, 'Pending', '2026-03-09 02:05:07', NULL, NULL, 'Critical condition', '2026-02-24 20:45:09', '2026-04-12 10:22:48'),
(9, 'AMB-18192', 'Isaac Tawiah', '0246917072', '1 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Obstetric', NULL, 'Dispatched', '2026-03-23 14:26:37', NULL, NULL, 'Critical condition', '2026-03-16 18:04:30', '2026-04-12 10:22:48'),
(10, 'AMB-86448', 'Daniel Acheampong', '0245498467', '31 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Cardiac Arrest', NULL, 'Dispatched', '2026-03-25 19:46:05', NULL, NULL, 'Patient unconscious', '2026-03-22 02:47:50', '2026-04-12 10:22:48'),
(11, 'AMB-62126', 'Michael Owusu', '0242608682', '13 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'General Medical', NULL, 'Dispatched', '2026-03-03 21:27:26', NULL, NULL, 'Minor injury', '2026-03-06 23:55:51', '2026-04-12 10:26:48'),
(12, 'AMB-82084', 'George Gyamfi', '0249190565', '27 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'General Medical', NULL, 'Dispatched', '2026-02-20 09:54:34', NULL, NULL, 'Critical condition', '2026-03-11 17:29:54', '2026-04-12 10:26:48'),
(13, 'AMB-39029', 'Nana Acheampong', '0245690453', '44 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'General Medical', NULL, 'Dispatched', '2026-02-18 09:26:09', NULL, NULL, 'Patient unconscious', '2026-02-24 09:58:35', '2026-04-12 10:26:48'),
(14, 'AMB-34676', 'Daniel Ofori', '0247893704', '36 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'Cardiac Arrest', NULL, 'Completed', '2026-03-19 06:24:47', NULL, NULL, 'Patient unconscious', '2026-02-19 13:29:09', '2026-04-12 10:26:48'),
(15, 'AMB-81995', 'Eric Adu', '0249174331', '46 Main Street, Accra, Ghana', 'RMU Medical Sickbay, Accra', 'General Medical', NULL, 'Completed', '2026-03-17 18:40:35', NULL, NULL, 'Critical condition', '2026-03-28 06:35:23', '2026-04-12 10:26:48'),
(16, 'AMB-REQ-001', 'Jane Doe', '0240000101', 'Main Gate', 'Sickbay', NULL, NULL, 'Completed', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(17, 'AMB-REQ-002', 'Clara Asante', '0000000000', 'West Campus Hostel', 'Sickbay', NULL, NULL, 'Dispatched', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(18, 'AMB-REQ-003', 'John Smith', '0240000102', 'Football Pitch', 'Sickbay', NULL, NULL, 'Dispatched', '2026-04-14 01:00:14', '2026-04-19 05:35:14', NULL, NULL, '2026-04-14 01:00:14', '2026-04-19 05:35:14'),
(19, 'AMB-REQ-004', 'Alice Wonder', '0240000103', 'Lecture Hall Block B', 'Sickbay', NULL, NULL, 'Cancelled', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(20, 'AMB-REQ-005', 'Staff Member', '0555555555', 'Staff Quarters', 'Sickbay', NULL, NULL, 'Completed', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(21, 'AMB-REQ-006', 'Bob Builder', '0240000104', 'Main Library', 'Sickbay', NULL, NULL, 'In Transit', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(22, 'AMB-REQ-007', 'Charlie Brown', '0240000105', 'Computer Lab', 'Sickbay', NULL, NULL, 'Pending', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(23, 'AMB-REQ-008', 'Diana Prince', '0240000106', 'Admin Block', 'Sickbay', NULL, NULL, 'Completed', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(24, 'AMB-REQ-009', 'Edward Eric', '0240000107', 'Canteen', 'Sickbay', NULL, NULL, 'Pending', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14'),
(25, 'AMB-REQ-010', 'Fiona Shrek', '0240000108', 'Swimming Pool', 'Sickbay', NULL, NULL, 'Completed', '2026-04-14 01:00:14', NULL, NULL, NULL, '2026-04-14 01:00:14', '2026-04-14 01:00:14');

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_trips`
--

DROP TABLE IF EXISTS `ambulance_trips`;
CREATE TABLE IF NOT EXISTS `ambulance_trips` (
  `trip_id` int NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL COMMENT 'staff ID',
  `patient_id` int DEFAULT NULL COMMENT 'nullable — may not be registered patient',
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_type` enum('emergency','scheduled') COLLATE utf8mb4_unicode_ci DEFAULT 'emergency',
  `request_source` enum('admin','doctor','nurse','walk-in') COLLATE utf8mb4_unicode_ci DEFAULT 'walk-in',
  `trip_status` enum('requested','accepted','rejected','en route','patient onboard','arrived','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'requested',
  `accepted_at` datetime DEFAULT NULL,
  `departed_at` datetime DEFAULT NULL,
  `patient_onboard_at` datetime DEFAULT NULL,
  `arrived_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `fuel_used_litres` decimal(8,2) DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `trip_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `service_type`, `symptoms`, `reason`, `reschedule_reason`, `reschedule_date`, `reschedule_time`, `cancellation_reason`, `cancelled_by`, `notification_sent`, `urgency_level`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'APT-61041', 5, 4, '2026-04-02', '09:30:00', 'Check-Up', 'Patient complains of chest pain.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', 'Confirmed', 'New patient visit', '2025-10-24 16:59:55', '2026-04-12 10:22:48'),
(2, 'APT-34093', 5, 4, '2025-10-22', '15:30:00', 'Emergency', 'Patient complains of fatigue.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Low', 'Completed', 'New patient visit', '2025-12-24 10:03:06', '2026-04-12 10:22:48'),
(3, 'APT-45142', 5, 4, '2025-11-20', '11:00:00', 'Emergency', 'Patient complains of chest pain.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Low', 'Completed', 'Routine check-up', '2026-03-02 16:22:13', '2026-04-12 10:22:48'),
(4, 'APT-64148', 5, 4, '2025-11-05', '10:00:00', 'Follow-Up', 'Patient complains of fever.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Confirmed', 'Routine check-up', '2025-11-09 08:07:50', '2026-04-12 10:22:48'),
(5, 'APT-43793', 5, 4, '2025-12-06', '16:00:00', 'Check-Up', 'Patient complains of fatigue.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Low', 'Confirmed', 'New patient visit', '2026-03-07 13:30:16', '2026-04-12 10:22:48'),
(6, 'APT-46667', 5, 4, '2026-01-07', '16:00:00', 'Consultation', 'Patient complains of fever.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', 'Completed', 'Routine check-up', '2025-11-05 23:20:39', '2026-04-12 10:22:48'),
(7, 'APT-48071', 5, 4, '2026-01-31', '09:30:00', 'Consultation', 'Patient complains of fever.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Completed', 'New patient visit', '2025-11-12 11:12:41', '2026-04-12 10:22:48'),
(8, 'APT-85873', 5, 4, '2025-10-18', '14:00:00', 'Emergency', 'Patient complains of headache.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', 'Confirmed', 'Follow-up on medication', '2025-10-18 21:50:10', '2026-04-12 10:22:48'),
(9, 'APT-50591', 5, 4, '2026-03-17', '10:00:00', 'Consultation', 'Patient complains of fever.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', 'Confirmed', 'Routine check-up', '2026-02-06 03:07:32', '2026-04-12 10:22:48'),
(10, 'APT-45560', 5, 4, '2026-02-05', '11:00:00', 'Consultation', 'Patient complains of cough.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', 'Cancelled', 'New patient visit', '2026-04-11 03:38:45', '2026-04-12 10:22:48'),
(11, 'APT-65019', 6, 4, '2026-04-27', '15:30:00', 'Follow-Up', 'Patient complains of cough.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Medium', '', 'New patient visit', '2026-02-25 08:08:59', '2026-04-12 10:26:48'),
(12, 'APT-80032', 5, 4, '2025-11-27', '11:00:00', 'Follow-Up', 'Patient complains of headache.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Low', 'Confirmed', 'New patient visit', '2025-11-17 22:44:42', '2026-04-12 10:26:48'),
(13, 'APT-85131', 5, 4, '2025-10-16', '16:00:00', 'Consultation', 'Patient complains of headache.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Completed', 'Follow-up on medication', '2026-04-08 16:53:14', '2026-04-12 10:26:48'),
(14, 'APT-53484', 7, 4, '2025-12-13', '08:00:00', 'Follow-Up', 'Patient complains of headache.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Confirmed', 'New patient visit', '2026-03-24 06:40:27', '2026-04-12 10:26:48'),
(15, 'APT-49203', 8, 4, '2025-10-15', '16:00:00', 'Follow-Up', 'Patient complains of chest pain.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Cancelled', 'Routine check-up', '2025-12-30 18:59:09', '2026-04-12 10:26:48'),
(16, 'APT-62357', 8, 4, '2025-12-31', '09:30:00', 'Emergency', 'Patient complains of chest pain.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Cancelled', 'Follow-up on medication', '2025-11-16 02:38:32', '2026-04-12 10:26:48'),
(17, 'APT-10224', 6, 4, '2026-07-06', '10:00:00', 'Consultation', 'Patient complains of chest pain.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', '', 'Routine check-up', '2025-11-29 14:10:58', '2026-04-12 10:26:48'),
(18, 'APT-57520', 10, 4, '2026-04-24', '15:30:00', 'Emergency', 'Patient complains of fever.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', '', 'Routine check-up', '2026-03-20 00:58:32', '2026-04-12 10:26:48'),
(19, 'APT-46038', 5, 4, '2025-11-10', '08:00:00', 'Consultation', 'Patient complains of fatigue.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Low', 'Cancelled', 'Follow-up on medication', '2026-01-31 22:18:54', '2026-04-12 10:26:48'),
(20, 'APT-41639', 5, 4, '2026-02-02', '09:30:00', 'Follow-Up', 'Patient complains of cough.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'High', 'Completed', 'New patient visit', '2025-12-07 22:43:30', '2026-04-12 10:26:48');

--
-- Triggers `appointments`
--
DROP TRIGGER IF EXISTS `before_insert_appointments`;
DELIMITER $$
CREATE TRIGGER `before_insert_appointments` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(appointment_id, 5) AS UNSIGNED)), 0) + 1
  INTO next_id
  FROM appointments;
  SET NEW.appointment_id = CONCAT('APT-', LPAD(next_id, 5, '0'));
END
$$
DELIMITER ;

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment_reminders`
--

INSERT INTO `appointment_reminders` (`reminder_id`, `appointment_id`, `reminder_type`, `scheduled_time`, `status`, `sent_at`, `created_at`) VALUES
(1, 1, 'notification', '2026-03-22 02:57:55', 'Sent', '2026-02-15 11:12:43', '2026-03-23 03:59:10'),
(2, 1, 'email', '2026-04-05 03:34:32', 'Failed', '2026-03-19 01:37:57', '2026-02-16 06:18:58'),
(3, 2, 'notification', '2026-03-07 08:13:24', 'Sent', '2026-03-27 14:54:24', '2026-03-03 05:14:46'),
(4, 3, 'sms', '2026-02-21 16:54:50', 'Failed', '2026-04-01 00:24:53', '2026-03-25 20:49:13'),
(5, 4, 'sms', '2026-03-16 23:00:24', 'Failed', '2026-03-06 01:10:23', '2026-03-12 21:00:15'),
(6, 5, 'sms', '2026-03-03 19:23:49', 'Sent', '2026-04-07 11:39:32', '2026-02-24 21:00:40'),
(7, 6, 'notification', '2026-03-11 23:23:38', 'Failed', '2026-02-20 22:07:01', '2026-03-23 17:47:01'),
(8, 7, 'email', '2026-02-14 12:43:35', 'Pending', '2026-03-12 04:07:47', '2026-04-05 19:22:32'),
(9, 8, 'sms', '2026-04-06 23:00:04', 'Sent', '2026-03-19 03:35:28', '2026-03-12 04:16:37'),
(10, 11, 'notification', '2026-02-25 03:15:23', 'Failed', '2026-02-27 18:31:05', '2026-02-20 21:30:54'),
(11, 12, 'sms', '2026-03-25 02:17:03', 'Failed', '2026-02-15 05:34:32', '2026-03-28 17:01:26'),
(12, 13, 'email', '2026-03-19 07:19:22', 'Failed', '2026-04-09 16:26:39', '2026-02-26 23:00:03'),
(13, 14, 'sms', '2026-03-23 06:46:27', 'Pending', '2026-03-24 06:13:02', '2026-04-11 05:55:47'),
(14, 15, 'email', '2026-03-07 14:02:18', 'Pending', '2026-03-26 21:54:57', '2026-03-04 21:09:52'),
(15, 16, 'sms', '2026-03-29 06:02:18', 'Pending', '2026-02-15 05:34:03', '2026-02-24 21:52:22'),
(16, 17, 'notification', '2026-04-07 12:02:45', 'Failed', '2026-03-01 09:57:43', '2026-03-26 09:46:42'),
(17, 18, 'notification', '2026-02-18 20:45:31', 'Sent', '2026-02-15 21:29:37', '2026-03-23 22:07:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=305 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'config_update', 'system_config', NULL, NULL, '\"Updated email settings\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 20:52:40'),
(2, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:08:15'),
(4, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:28:55'),
(5, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:29:26'),
(7, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 15:28:02'),
(8, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:42:55'),
(9, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:48:03'),
(10, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:48:03'),
(11, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:48:34'),
(12, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:48:34'),
(13, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:49:05'),
(14, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:49:05'),
(15, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:53:39'),
(16, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:53:39'),
(17, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:00:25'),
(18, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:00:25'),
(19, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:01:47'),
(20, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:01:47'),
(21, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:11:32'),
(22, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:11:32'),
(23, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:44:47'),
(24, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:44:47'),
(25, 1, 'logout_csrf_failed', 'users', NULL, NULL, '{\"ip\": \"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:46:14'),
(26, 1, 'manual_logout', 'users', '1', NULL, '{\"dashboard\": \"_sidebar.php\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 17:46:14'),
(27, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 10:43:50'),
(28, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 11:05:22'),
(29, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:47:15'),
(30, NULL, 'LOGIN_SUCCESS', 'users', '29', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:13'),
(31, NULL, 'LOGIN_SUCCESS', 'users', '23', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:58'),
(32, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:07'),
(33, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:25'),
(34, NULL, 'LOGIN_SUCCESS', 'users', '17', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:57'),
(35, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:52:58'),
(36, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:16:35'),
(37, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:17:24'),
(38, NULL, 'LOGIN_SUCCESS', 'users', '21', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:21:09'),
(39, NULL, 'LOGIN_SUCCESS', 'users', '18', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:23:10'),
(40, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:24:08'),
(41, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:37:39'),
(42, NULL, 'LOGIN_SUCCESS', 'users', '14', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 08:58:48'),
(43, NULL, 'LOGIN_SUCCESS', 'users', '16', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:03:26'),
(44, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:36:28'),
(45, NULL, 'LOGIN_SUCCESS', 'users', '15', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 05:49:25'),
(46, NULL, 'LOGIN_SUCCESS', 'users', '15', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:24:19'),
(47, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:28:57'),
(48, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:18:17'),
(49, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:19:02'),
(50, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 08:29:26'),
(51, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:05:39'),
(52, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:26:38'),
(53, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:27:29'),
(54, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:28:06'),
(55, 28, 'LOGIN_FAILED', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:05:21'),
(56, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:05:44'),
(57, 28, 'manual_logout', 'users', '28', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:21:46'),
(58, 28, 'manual_logout', 'users', '28', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:22:17'),
(59, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 15:01:50'),
(60, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:19:10'),
(61, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:19:32'),
(62, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:22:46'),
(63, 35, 'LOGIN_SUCCESS', 'users', '35', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:36:17'),
(64, 35, 'manual_logout', 'users', '35', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 18:51:30'),
(65, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-10 23:50:22'),
(66, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-10 23:59:57'),
(67, 36, 'LOGIN_FAILED', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:12:04'),
(68, 36, 'LOGIN_FAILED', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:12:27'),
(69, 36, 'LOGIN_FAILED', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:13:03'),
(70, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:14:23'),
(71, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:14:58'),
(72, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:35:47'),
(73, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:36:53'),
(74, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:37:39'),
(75, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:38:43'),
(76, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:47:10'),
(77, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 04:33:34'),
(78, 28, 'create_appointment', 'prescriptions', '24', NULL, '{\"status\": \"updated\"}', '55.1.53.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-15 07:26:08'),
(79, 36, 'modify_prescription', 'billing_invoices', '57', NULL, '{\"status\": \"updated\"}', '101.192.166.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-25 12:44:16'),
(80, 36, 'update_record', 'prescriptions', '9', NULL, '{\"status\": \"updated\"}', '104.113.137.70', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-29 02:38:50'),
(81, 28, 'update_record', 'prescriptions', '90', NULL, '{\"status\": \"updated\"}', '13.175.204.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-27 06:57:31'),
(82, 26, 'download_report', 'appointments', '71', NULL, '{\"status\": \"updated\"}', '65.194.44.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-06 15:44:12'),
(83, 26, 'generate_invoice', 'appointments', '25', NULL, '{\"status\": \"updated\"}', '159.83.93.141', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-01 03:21:35'),
(84, 26, 'update_record', 'patients', '81', NULL, '{\"status\": \"updated\"}', '116.216.226.221', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-24 14:59:28'),
(85, 20, 'download_report', 'prescriptions', '88', NULL, '{\"status\": \"updated\"}', '81.112.122.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-09 06:45:00'),
(86, 1, 'logout', 'prescriptions', '66', NULL, '{\"status\": \"updated\"}', '190.151.50.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-04-01 18:13:02'),
(87, 36, 'cancel_appointment', 'appointments', '52', NULL, '{\"status\": \"updated\"}', '118.46.173.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-23 07:22:55'),
(88, 40, 'cancel_appointment', 'appointments', '46', NULL, '{\"status\": \"updated\"}', '159.58.98.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-04-03 16:27:05'),
(89, 39, 'logout', 'patients', '32', NULL, '{\"status\": \"updated\"}', '172.246.226.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-02 21:37:29'),
(90, 28, 'logout', 'medical_records', '8', NULL, '{\"status\": \"updated\"}', '146.41.174.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-04-01 22:06:55'),
(91, 41, 'modify_prescription', 'prescriptions', '79', NULL, '{\"status\": \"updated\"}', '154.200.8.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-20 16:46:16'),
(92, 38, 'update_record', 'patients', '2', NULL, '{\"status\": \"updated\"}', '97.133.102.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-17 14:21:18'),
(93, 20, 'update_record', 'billing_invoices', '86', NULL, '{\"status\": \"updated\"}', '154.25.180.85', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-23 14:59:21'),
(94, 41, 'download_report', 'appointments', '91', NULL, '{\"status\": \"updated\"}', '93.247.183.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-18 18:13:46'),
(95, 28, 'generate_invoice', 'medical_records', '5', NULL, '{\"status\": \"updated\"}', '148.130.165.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-26 17:13:32'),
(96, 40, 'login', 'billing_invoices', '61', NULL, '{\"status\": \"updated\"}', '133.200.74.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-07 08:49:35'),
(97, 35, 'generate_invoice', 'medical_records', '98', NULL, '{\"status\": \"updated\"}', '75.101.117.91', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-26 14:35:07'),
(98, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 10:26:25'),
(99, 37, 'login', 'billing_invoices', '73', NULL, '{\"status\": \"updated\"}', '39.75.15.40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-04-12 03:29:40'),
(100, NULL, 'create_appointment', 'appointments', '81', NULL, '{\"status\": \"updated\"}', '71.102.174.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-15 16:14:01'),
(101, 43, 'view_record', 'patients', '5', NULL, '{\"status\": \"updated\"}', '137.33.121.180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-07 03:07:53'),
(102, 28, 'view_record', 'patients', '76', NULL, '{\"status\": \"updated\"}', '124.15.21.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-14 14:34:22'),
(103, NULL, 'process_payment', 'appointments', '40', NULL, '{\"status\": \"updated\"}', '48.239.176.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-01-12 14:07:33'),
(104, 43, 'update_record', 'medical_records', '37', NULL, '{\"status\": \"updated\"}', '190.113.25.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-07 00:58:34'),
(105, 42, 'login', 'prescriptions', '15', NULL, '{\"status\": \"updated\"}', '91.43.152.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-08 22:26:41'),
(106, 28, 'cancel_appointment', 'prescriptions', '1', NULL, '{\"status\": \"updated\"}', '176.79.197.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-03 21:27:09'),
(107, 40, 'logout', 'medical_records', '67', NULL, '{\"status\": \"updated\"}', '75.103.125.91', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-06 07:46:51'),
(108, 40, 'logout', 'patients', '4', NULL, '{\"status\": \"updated\"}', '36.108.1.140', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-03-26 13:52:28'),
(109, 42, 'LOGIN_SUCCESS', 'users', '42', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 08:09:03'),
(110, 42, 'manual_logout', 'users', '42', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 09:53:29'),
(111, 37, 'LOGIN_SUCCESS', 'users', '37', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 09:55:53'),
(112, 37, 'LOGIN_SUCCESS', 'users', '37', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 12:25:08'),
(113, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:25:14'),
(114, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:26:21'),
(115, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:26:42'),
(116, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:27:57'),
(117, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:28:52'),
(118, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:29:32'),
(119, 26, 'LOGIN_FAILED', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:30:04'),
(120, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:30:27'),
(121, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:30:58'),
(122, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:36:55'),
(123, 28, 'manual_logout', 'users', '28', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:37:29'),
(124, 37, 'LOGIN_SUCCESS', 'users', '37', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:38:27'),
(125, 37, 'manual_logout', 'users', '37', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:38:58'),
(126, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:40:49'),
(127, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:41:21'),
(128, NULL, 'LOGIN_SUCCESS', 'users', '46', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:43:05'),
(129, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:43:32'),
(130, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:44:34'),
(131, 35, 'LOGIN_SUCCESS', 'users', '35', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:55:46'),
(132, 35, 'manual_logout', 'users', '35', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:58:38'),
(133, 37, 'LOGIN_SUCCESS', 'users', '37', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:25:18'),
(134, 37, 'manual_logout', 'users', '37', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:25:38'),
(135, 38, 'LOGIN_SUCCESS', 'users', '38', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:26:14'),
(136, 38, 'manual_logout', 'users', '38', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:26:26'),
(137, 40, 'LOGIN_SUCCESS', 'users', '40', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:27:05'),
(138, 40, 'manual_logout', 'users', '40', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:28:46'),
(139, 101, 'LOGIN_FAILED', 'users', '101', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:29:46'),
(140, 101, 'LOGIN_SUCCESS', 'users', '101', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:54:44'),
(141, 101, 'manual_logout', 'users', '101', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:55:12'),
(142, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:55:35'),
(143, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:56:12'),
(144, 307, 'LOGIN_SUCCESS', 'users', '307', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:15:44'),
(145, 307, 'manual_logout', 'users', '307', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:16:33'),
(146, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:30:41'),
(147, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:36:30'),
(148, 36, 'LOGIN_SUCCESS', 'users', '36', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:38:04'),
(149, 36, 'manual_logout', 'users', '36', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:42:30'),
(150, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:42:54'),
(151, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:47:42'),
(152, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:08:07'),
(153, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:38:07'),
(154, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:38:37'),
(155, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:44:54'),
(156, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:45:24'),
(157, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:02:48'),
(158, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:07:51'),
(159, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:10:55'),
(160, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:46:04'),
(161, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:46:38'),
(162, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 05:22:36'),
(163, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:30:46'),
(164, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:34:08'),
(165, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:55:57'),
(166, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:57:42'),
(167, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:16:24'),
(168, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:16:46'),
(169, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:31:08'),
(170, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:32:23'),
(171, 312, 'LOGIN_SUCCESS', 'users', '312', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:32:50'),
(172, 312, 'manual_logout', 'users', '312', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 11:50:18'),
(173, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 05:31:42'),
(174, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:22:20'),
(175, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:22:40'),
(176, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:24:28'),
(177, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:24:55'),
(178, 28, 'manual_logout', 'users', '28', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:47:49'),
(179, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:48:18'),
(180, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:57:21'),
(181, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:58:16'),
(182, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:58:42'),
(183, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:59:46'),
(184, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 09:59:40'),
(185, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 15:55:28'),
(186, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 16:38:49'),
(187, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 21:19:29'),
(188, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 22:38:16'),
(189, 28, 'LOGIN_SUCCESS', 'users', '28', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:02:53'),
(190, 28, 'manual_logout', 'users', '28', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:23:10'),
(191, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:23:53'),
(192, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 05:33:37'),
(193, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:24:29'),
(194, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:53:56'),
(195, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:54:24'),
(196, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:56:03'),
(197, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:57:52'),
(198, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:02:10'),
(199, 312, 'LOGIN_SUCCESS', 'users', '312', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:02:46'),
(200, 312, 'manual_logout', 'users', '312', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:19:31'),
(201, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:20:13'),
(202, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:20:55'),
(203, 312, 'LOGIN_SUCCESS', 'users', '312', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:21:25'),
(204, 312, 'manual_logout', 'users', '312', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:28:17'),
(205, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:34:15'),
(206, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:20:21'),
(207, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:31:09'),
(208, 26, 'LOGIN_SUCCESS', 'users', '26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:31:37'),
(209, 26, 'manual_logout', 'users', '26', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:32:06'),
(210, 312, 'LOGIN_SUCCESS', 'users', '312', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:32:43'),
(211, 312, 'manual_logout', 'users', '312', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:36:29'),
(212, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:37:02'),
(213, 20, 'LOGIN_SUCCESS', 'users', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:15:03'),
(214, 20, 'manual_logout', 'users', '20', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:15:50'),
(215, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:32:28'),
(216, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:33:54'),
(217, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:35:26'),
(218, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:36:21'),
(219, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:41:04'),
(220, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:42:02'),
(221, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:53:34'),
(222, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 05:50:35'),
(223, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 07:00:24'),
(224, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 07:57:44'),
(225, 313, 'manual_logout', 'users', '313', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 08:34:55'),
(226, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 08:35:56');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(227, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 08:46:06'),
(228, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:14:31'),
(229, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:25:32'),
(230, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:25:56'),
(231, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:33:25'),
(232, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:02:39'),
(233, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:05:41'),
(234, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:41:32'),
(235, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:45:30'),
(236, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:47:50'),
(237, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 11:40:57'),
(238, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 12:33:43'),
(239, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 13:33:00'),
(240, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 16:20:25'),
(241, 315, 'manual_logout', 'users', '315', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 16:24:53'),
(242, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 16:25:18'),
(243, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:19:23'),
(244, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:20:11'),
(245, 1, 'LOGIN_FAILED', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:20:47'),
(246, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:21:09'),
(247, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:24:11'),
(248, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:04:49'),
(249, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:19:12'),
(250, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:20:39'),
(251, 313, 'manual_logout', 'users', '313', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:23:56'),
(252, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:24:19'),
(253, 315, 'manual_logout', 'users', '315', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:24:40'),
(254, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:25:22'),
(255, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:25:48'),
(256, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:28:21'),
(257, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:28:27'),
(258, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:32:04'),
(259, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:34:04'),
(260, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:45:44'),
(261, 316, 'LOGIN_SUCCESS', 'users', '316', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 09:34:49'),
(262, 316, 'manual_logout', 'users', '316', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 09:35:31'),
(263, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 09:39:45'),
(264, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 11:33:46'),
(265, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 12:04:48'),
(266, 316, 'LOGIN_SUCCESS', 'users', '316', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 08:42:11'),
(267, 316, 'manual_logout', 'users', '316', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 08:44:24'),
(268, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 07:30:09'),
(269, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 07:40:24'),
(270, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 07:40:50'),
(271, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 08:36:49'),
(272, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 08:38:27'),
(273, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 08:39:11'),
(274, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 11:09:24'),
(275, 313, 'manual_logout', 'users', '313', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 11:09:46'),
(276, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 11:10:34'),
(277, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 08:50:43'),
(278, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 08:57:55'),
(279, 313, 'LOGIN_SUCCESS', 'users', '313', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 08:58:23'),
(280, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 18:40:50'),
(281, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 19:16:09'),
(282, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 19:16:32'),
(283, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:02:59'),
(284, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:03:21'),
(285, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:06:51'),
(286, 314, 'LOGIN_SUCCESS', 'users', '314', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:07:30'),
(287, 314, 'manual_logout', 'users', '314', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:11:56'),
(288, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:12:28'),
(289, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:13:42'),
(290, 315, 'LOGIN_FAILED', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:29:05'),
(291, 316, 'LOGIN_SUCCESS', 'users', '316', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:30:06'),
(292, 316, 'manual_logout', 'users', '316', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:39:01'),
(293, 315, 'LOGIN_SUCCESS', 'users', '315', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:39:24'),
(294, 315, 'manual_logout', 'users', '315', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:39:48'),
(295, 316, 'LOGIN_SUCCESS', 'users', '316', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:40:13'),
(296, 316, 'manual_logout', 'users', '316', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:12:43'),
(297, 35, 'LOGIN_SUCCESS', 'users', '35', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:13:07'),
(298, 35, 'manual_logout', 'users', '35', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:15:58'),
(299, NULL, 'LOGIN_FAILED', 'users', '201', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:19:39'),
(300, NULL, 'LOGIN_FAILED', 'users', '201', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:19:56'),
(301, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:28:12'),
(302, 1, 'manual_logout', 'users', '1', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:29:08'),
(303, 317, 'LOGIN_SUCCESS', 'users', '317', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:29:39'),
(304, 317, 'manual_logout', 'users', '317', NULL, '{\"notes\": \"User logged out via AJAX handler.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:30:31');

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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(7, 'BED007', 'PVT-B02', 'Private Ward', 'Private', 'Available', 150.00, '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(8, 'BED-2525', 'B-29', 'ICU', 'ICU', 'Reserved', 399.00, '2024-09-20 01:54:20', '2026-04-12 10:14:05'),
(9, 'BED-2564', 'B-25', 'Ward B - Surgical', 'ICU', 'Reserved', 476.00, '2024-10-14 17:51:09', '2026-04-12 10:14:05'),
(10, 'BED-6285', 'B-24', 'Pediatric Ward', 'Private', 'Maintenance', 407.00, '2025-09-16 21:42:21', '2026-04-12 10:14:05'),
(11, 'BED-9260', 'B-38', 'Pediatric Ward', 'Private', 'Maintenance', 152.00, '2024-06-29 23:29:19', '2026-04-12 10:14:05'),
(13, 'BED-6730', 'B-43', 'Ward B - Surgical', 'Private', 'Available', 432.00, '2025-04-29 03:04:41', '2026-04-12 10:14:05'),
(14, 'BED-4417', 'B-46', 'Ward B - Surgical', 'General', 'Occupied', 477.00, '2025-05-01 05:52:17', '2026-04-12 10:14:05'),
(15, 'BED-7041', 'B-40', 'Ward A - General', 'Private', 'Occupied', 391.00, '2025-06-18 05:59:12', '2026-04-12 10:14:05'),
(17, 'BED-3067', 'B-2', 'Maternity Ward', 'Semi-Private', 'Reserved', 86.00, '2025-03-26 16:09:24', '2026-04-12 10:14:05'),
(18, 'BED-5946', 'B-28-766', 'Pediatric Ward', 'ICU', 'Maintenance', 457.00, '2025-03-03 10:17:20', '2026-04-12 10:22:48'),
(19, 'BED-5130', 'B-50-287', 'Maternity Ward', 'General', 'Maintenance', 424.00, '2025-08-01 11:51:54', '2026-04-12 10:22:48'),
(20, 'BED-2627', 'B-32-485', 'Ward B - Surgical', 'ICU', 'Available', 271.00, '2025-06-22 06:22:24', '2026-04-12 10:22:48'),
(21, 'BED-7222', 'B-20-287', 'Pediatric Ward', 'Private', 'Maintenance', 121.00, '2024-08-10 02:03:50', '2026-04-12 10:22:48'),
(22, 'BED-9454', 'B-34-649', 'Ward A - General', 'ICU', 'Reserved', 451.00, '2025-05-11 07:54:05', '2026-04-12 10:22:48'),
(23, 'BED-2523', 'B-27-652', 'Ward B - Surgical', 'Semi-Private', 'Occupied', 336.00, '2025-03-24 08:05:17', '2026-04-12 10:22:48'),
(24, 'BED-4009', 'B-46-985', 'Maternity Ward', 'General', 'Maintenance', 72.00, '2024-06-27 21:30:58', '2026-04-12 10:22:48'),
(25, 'BED-2683', 'B-39-808', 'Ward A - General', 'Private', 'Maintenance', 88.00, '2024-10-06 02:48:43', '2026-04-12 10:22:48'),
(26, 'BED-2666', 'B-20-654', 'Ward B - Surgical', 'ICU', 'Reserved', 153.00, '2024-07-29 04:43:07', '2026-04-12 10:22:48'),
(27, 'BED-9576', 'B-30-473', 'Ward B - Surgical', 'ICU', 'Reserved', 59.00, '2024-05-18 19:22:45', '2026-04-12 10:22:48'),
(28, 'BED-8884', 'B-31-599', 'Ward A - General', 'Private', 'Available', 204.00, '2025-06-11 03:35:55', '2026-04-12 10:26:48'),
(29, 'BED-4255', 'B-18-779', 'Maternity Ward', 'ICU', 'Reserved', 459.00, '2024-07-10 06:53:48', '2026-04-12 10:26:48'),
(30, 'BED-8022', 'B-29-173', 'Ward B - Surgical', 'Private', 'Occupied', 384.00, '2025-07-16 01:37:19', '2026-04-12 10:26:48'),
(31, 'BED-1123', 'B-38-902', 'ICU', 'Private', 'Occupied', 134.00, '2024-07-24 18:37:23', '2026-04-12 10:26:48'),
(32, 'BED-1617', 'B-49-981', 'Pediatric Ward', 'Private', 'Occupied', 213.00, '2024-10-07 05:11:24', '2026-04-12 10:26:48'),
(33, 'BED-6972', 'B-19-864', 'Pediatric Ward', 'Private', 'Occupied', 400.00, '2025-09-13 07:39:30', '2026-04-12 10:26:48'),
(34, 'BED-9678', 'B-32-617', 'Maternity Ward', 'Semi-Private', 'Occupied', 318.00, '2024-04-14 03:13:56', '2026-04-12 10:26:48'),
(35, 'BED-2259', 'B-28-859', 'Ward A - General', 'General', 'Reserved', 254.00, '2025-03-01 00:42:41', '2026-04-12 10:26:48'),
(36, 'BED-2764', 'B-21-763', 'Pediatric Ward', 'General', 'Reserved', 116.00, '2025-04-25 14:26:41', '2026-04-12 10:26:48'),
(37, 'BED-3264', 'B-11-262', 'Ward A - General', 'ICU', 'Reserved', 293.00, '2024-05-18 14:46:21', '2026-04-12 10:26:48');

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
  `attending_nurse_id` int DEFAULT NULL COMMENT 'nurses.id - nurse assigned to this patient',
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_id` (`assignment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `bed_id` (`bed_id`),
  KEY `idx_assignment_id` (`assignment_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bed_assignments`
--

INSERT INTO `bed_assignments` (`id`, `assignment_id`, `patient_id`, `bed_id`, `assigned_nurse_id`, `admission_date`, `discharge_date`, `reason`, `attending_nurse_notes`, `status`, `created_at`, `updated_at`, `attending_nurse_id`) VALUES
(1, 'ASSIGN-3729', 5, 15, 1, '2026-03-24 01:27:38', '2026-04-26 10:14:05', 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-20 02:40:36', '2026-04-12 10:14:05', NULL),
(2, 'ASSIGN-8486', 5, 14, 1, '2026-03-12 16:24:27', '2026-04-26 10:14:05', 'Standard admission procedure followed.', NULL, 'Active', '2026-03-26 23:19:40', '2026-04-12 10:14:05', NULL),
(3, 'ASSIGN-7373', 5, 9, 1, '2026-03-16 21:39:08', NULL, 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-18 07:57:02', '2026-04-12 10:14:05', NULL),
(4, 'ASSIGN-4795', 5, 7, 1, '2026-03-13 03:29:25', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-02 05:42:09', '2026-04-12 10:14:05', NULL),
(5, 'ASSIGN-1962', 5, 5, 1, '2026-03-26 06:59:03', '2026-04-26 10:14:05', 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-25 10:55:31', '2026-04-12 10:14:05', NULL),
(6, 'ASSIGN-6821', 5, 7, 1, '2026-02-27 08:53:56', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-23 07:15:36', '2026-04-12 10:14:05', NULL),
(7, 'ASSIGN-6297', 5, 11, 1, '2026-03-02 19:55:16', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-30 16:49:34', '2026-04-12 10:22:48', NULL),
(8, 'ASSIGN-3127', 5, 25, 1, '2026-02-27 05:57:35', NULL, 'Standard admission procedure followed.', NULL, 'Discharged', '2026-04-12 02:12:16', '2026-04-12 10:22:48', NULL),
(9, 'ASSIGN-4623', 5, 10, 1, '2026-04-01 18:56:15', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-02 13:55:03', '2026-04-12 10:22:48', NULL),
(10, 'ASSIGN-3665', 5, 15, 1, '2026-03-01 18:40:37', NULL, 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-27 13:45:09', '2026-04-12 10:22:48', NULL),
(11, 'ASSIGN-9963', 5, 17, 1, '2026-03-22 07:09:27', '2026-04-26 10:22:48', 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-16 06:58:48', '2026-04-12 10:22:48', NULL),
(12, 'ASSIGN-6155', 5, 11, 1, '2026-03-17 08:19:04', '2026-04-26 10:22:48', 'Standard admission procedure followed.', NULL, 'Active', '2026-03-10 19:10:00', '2026-04-12 10:22:48', NULL),
(13, 'ASSIGN-9979', 6, 21, 1, '2026-03-15 00:21:41', '2026-04-26 10:26:48', 'Standard admission procedure followed.', NULL, 'Active', '2026-03-20 13:51:00', '2026-04-12 10:26:48', NULL),
(14, 'ASSIGN-7463', 6, 36, 1, '2026-02-28 12:55:47', '2026-04-26 10:26:48', 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-19 08:37:21', '2026-04-12 10:26:48', NULL),
(15, 'ASSIGN-8136', 8, 24, 1, '2026-04-05 05:24:30', '2026-04-26 10:26:48', 'Standard admission procedure followed.', NULL, 'Active', '2026-02-27 15:43:03', '2026-04-12 10:26:48', NULL),
(16, 'ASSIGN-3839', 6, 1, 1, '2026-03-27 09:23:56', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-29 03:36:46', '2026-04-12 10:26:48', NULL),
(17, 'ASSIGN-2226', 8, 35, 1, '2026-03-07 20:17:52', NULL, 'Standard admission procedure followed.', NULL, 'Active', '2026-03-17 04:26:20', '2026-04-12 10:26:48', NULL),
(18, 'ASSIGN-9423', 9, 30, 1, '2026-03-11 01:34:20', NULL, 'Standard admission procedure followed.', NULL, 'Discharged', '2026-02-12 19:15:17', '2026-04-12 10:26:48', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `bed_management`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `bed_management`;
CREATE TABLE IF NOT EXISTS `bed_management` (
`admission_date` datetime
,`admission_reason` text
,`assignment_pk` int
,`assignment_status` enum('Active','Discharged','Transferred')
,`bed_id` varchar(50)
,`bed_number` varchar(50)
,`bed_pk` int
,`bed_status` enum('Available','Occupied','Maintenance','Reserved')
,`bed_type` enum('General','ICU','Private','Semi-Private')
,`daily_rate` decimal(10,2)
,`discharge_date` datetime
,`patient_id` int
,`patient_name` varchar(200)
,`patient_phone` varchar(20)
,`patient_ref_id` varchar(50)
,`ward` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `bed_transfers`
--

DROP TABLE IF EXISTS `bed_transfers`;
CREATE TABLE IF NOT EXISTS `bed_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transfer_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `from_bed_id` int DEFAULT NULL COMMENT 'FK → beds.id',
  `to_bed_id` int DEFAULT NULL COMMENT 'FK → beds.id',
  `from_ward` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_ward` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_reason` text COLLATE utf8mb4_unicode_ci,
  `transfer_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `authorized_by` int DEFAULT NULL COMMENT 'FK → doctors.id',
  `status` enum('Requested','Approved','Completed','Rejected','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Requested',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_id` (`transfer_id`),
  KEY `from_bed_id` (`from_bed_id`),
  KEY `to_bed_id` (`to_bed_id`),
  KEY `authorized_by` (`authorized_by`),
  KEY `idx_bt_transfer_id` (`transfer_id`),
  KEY `idx_bt_patient` (`patient_id`),
  KEY `idx_bt_nurse` (`nurse_id`),
  KEY `idx_bt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient bed and ward transfer requests and logging';

-- --------------------------------------------------------

--
-- Table structure for table `billing_invoices`
--

DROP TABLE IF EXISTS `billing_invoices`;
CREATE TABLE IF NOT EXISTS `billing_invoices` (
  `invoice_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: RMU-INV-YYYYMMDD-NNNN',
  `patient_id` int NOT NULL,
  `generated_by` int DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Void','Written Off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `payment_terms` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_student_invoice` tinyint(1) NOT NULL DEFAULT '0',
  `insurance_claim_id` int UNSIGNED DEFAULT NULL COMMENT 'Linked insurance claim, if any',
  `voided_reason` text COLLATE utf8mb4_unicode_ci,
  `voided_by` int DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  KEY `idx_invoice_patient` (`patient_id`),
  KEY `idx_invoice_status` (`status`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `fk_invoice_generated` (`generated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master billing invoices for all patient services';

--
-- Dumping data for table `billing_invoices`
--

INSERT INTO `billing_invoices` (`invoice_id`, `invoice_number`, `patient_id`, `generated_by`, `invoice_date`, `due_date`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `paid_amount`, `balance_due`, `currency`, `status`, `payment_terms`, `notes`, `is_student_invoice`, `insurance_claim_id`, `voided_reason`, `voided_by`, `voided_at`, `created_at`, `updated_at`) VALUES
(1, 'INV-53521', 5, NULL, '2026-03-18', '2026-04-17', 3293.00, 82.33, 0.00, 3293.00, 0.00, 3293.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-02 17:12:56', '2026-04-12 10:14:05'),
(2, 'INV-17548', 5, NULL, '2026-04-10', '2026-05-01', 1472.00, 36.80, 0.00, 1472.00, 1472.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-16 06:18:39', '2026-04-12 10:14:05'),
(3, 'INV-41400', 5, NULL, '2026-03-01', '2026-04-12', 2150.00, 53.75, 0.00, 2150.00, 0.00, 2150.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-23 02:00:08', '2026-04-12 10:14:05'),
(4, 'INV-38987', 5, NULL, '2026-03-28', '2026-04-22', 419.00, 10.48, 0.00, 419.00, 114.00, 305.00, 'GHS', 'Partially Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-12 03:08:56', '2026-04-12 10:14:05'),
(5, 'INV-11298', 5, NULL, '2026-02-18', '2026-04-23', 3930.00, 98.25, 0.00, 3930.00, 3930.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-04-11 03:07:33', '2026-04-12 10:14:05'),
(6, 'INV-63535', 5, NULL, '2026-03-01', '2026-05-04', 4260.00, 106.50, 0.00, 4260.00, 0.00, 4260.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-04 10:10:48', '2026-04-12 10:14:05'),
(7, 'INV-73764', 5, NULL, '2026-04-03', '2026-04-28', 4392.00, 109.80, 0.00, 4392.00, 0.00, 4392.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-16 08:15:53', '2026-04-12 10:14:05'),
(8, 'INV-72758', 5, NULL, '2026-03-14', '2026-04-27', 2934.00, 73.35, 0.00, 2934.00, 2934.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-14 03:42:42', '2026-04-12 10:14:05'),
(9, 'INV-31008', 5, NULL, '2026-03-09', '2026-05-05', 268.00, 6.70, 0.00, 268.00, 0.00, 268.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-07 17:52:25', '2026-04-12 10:14:05'),
(10, 'INV-73881', 5, NULL, '2026-03-16', '2026-04-17', 2131.00, 53.28, 0.00, 2131.00, 0.00, 2131.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-04-01 18:46:44', '2026-04-12 10:14:05'),
(11, 'INV-62744', 5, NULL, '2026-01-27', '2026-05-02', 960.00, 24.00, 0.00, 960.00, 960.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-04-04 05:46:51', '2026-04-12 10:22:48'),
(12, 'INV-37344', 5, NULL, '2026-03-26', '2026-04-16', 2867.00, 71.68, 0.00, 2867.00, 820.00, 2047.00, 'GHS', 'Partially Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-19 07:12:12', '2026-04-12 10:22:48'),
(13, 'INV-41897', 5, NULL, '2026-01-24', '2026-05-11', 4023.00, 100.58, 0.00, 4023.00, 4023.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-23 22:25:35', '2026-04-12 10:22:48'),
(14, 'INV-32774', 5, NULL, '2026-03-15', '2026-05-02', 3436.00, 85.90, 0.00, 3436.00, 0.00, 3436.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-05 22:44:51', '2026-04-12 10:22:48'),
(15, 'INV-15589', 5, NULL, '2026-02-19', '2026-05-03', 3847.00, 96.18, 0.00, 3847.00, 0.00, 3847.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-09 19:01:00', '2026-04-12 10:22:48'),
(16, 'INV-48015', 5, NULL, '2026-02-06', '2026-04-26', 3769.00, 94.23, 0.00, 3769.00, 0.00, 3769.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-16 16:37:52', '2026-04-12 10:22:48'),
(17, 'INV-52650', 5, NULL, '2026-03-01', '2026-04-30', 3719.00, 92.98, 0.00, 3719.00, 0.00, 3719.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-11 15:29:48', '2026-04-12 10:22:48'),
(18, 'INV-25121', 5, NULL, '2026-03-31', '2026-05-07', 1832.00, 45.80, 0.00, 1832.00, 0.00, 1832.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-23 19:11:22', '2026-04-12 10:22:48'),
(19, 'INV-13974', 5, NULL, '2026-02-22', '2026-05-08', 3975.00, 99.38, 0.00, 3975.00, 0.00, 3975.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-21 09:51:40', '2026-04-12 10:22:48'),
(20, 'INV-85413', 5, NULL, '2026-03-29', '2026-05-05', 454.00, 11.35, 0.00, 454.00, 0.00, 454.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-27 18:48:01', '2026-04-12 10:22:48'),
(21, 'INV-35481', 9, NULL, '2026-02-27', '2026-04-26', 212.00, 5.30, 0.00, 212.00, 0.00, 212.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-23 10:09:26', '2026-04-12 10:26:48'),
(22, 'INV-67867', 10, NULL, '2026-03-09', '2026-05-11', 767.00, 19.18, 0.00, 767.00, 767.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-02-18 23:28:19', '2026-04-12 10:26:48'),
(23, 'INV-61666', 9, NULL, '2026-01-24', '2026-04-19', 1785.00, 44.63, 0.00, 1785.00, 1785.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-30 07:47:35', '2026-04-12 10:26:48'),
(24, 'INV-11886', 9, NULL, '2026-03-04', '2026-05-02', 2480.00, 62.00, 0.00, 2480.00, 0.00, 2480.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-15 22:42:02', '2026-04-12 10:26:48'),
(25, 'INV-84044', 9, NULL, '2026-02-22', '2026-05-03', 832.00, 20.80, 0.00, 832.00, 832.00, 0.00, 'GHS', 'Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-25 23:41:53', '2026-04-12 10:26:48'),
(26, 'INV-65654', 9, NULL, '2026-03-25', '2026-04-28', 4255.00, 106.38, 0.00, 4255.00, 265.00, 3990.00, 'GHS', 'Partially Paid', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-14 03:25:13', '2026-04-12 10:26:48'),
(27, 'INV-22283', 10, NULL, '2026-03-11', '2026-04-24', 1186.00, 29.65, 0.00, 1186.00, 0.00, 1186.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-08 21:09:31', '2026-04-12 10:26:48'),
(28, 'INV-72085', 6, NULL, '2026-03-02', '2026-04-30', 4514.00, 112.85, 0.00, 4514.00, 0.00, 4514.00, 'GHS', 'Pending', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-17 06:50:24', '2026-04-12 10:26:48'),
(29, 'INV-28208', 7, NULL, '2026-03-07', '2026-04-25', 2202.00, 55.05, 0.00, 2202.00, 0.00, 2202.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-01-15 00:37:20', '2026-04-12 10:26:48'),
(30, 'INV-32516', 5, NULL, '2026-01-20', '2026-04-17', 4662.00, 116.55, 0.00, 4662.00, 0.00, 4662.00, 'GHS', 'Overdue', NULL, 'Invoice generated for medical services rendered.', 0, NULL, NULL, NULL, NULL, '2026-03-06 19:42:21', '2026-04-12 10:26:48'),
(31, 'INV-RMU-101', 101, NULL, '2026-04-14', NULL, 0.00, 0.00, 0.00, 450.00, 0.00, 450.00, 'GHS', 'Pending', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(32, 'INV-RMU-102', 102, NULL, '2026-04-13', NULL, 0.00, 0.00, 0.00, 1200.00, 0.00, 0.00, 'GHS', 'Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(33, 'INV-RMU-103', 103, NULL, '2026-04-14', NULL, 0.00, 0.00, 0.00, 75.00, 0.00, 75.00, 'GHS', 'Pending', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(34, 'INV-RMU-104', 104, NULL, '2026-04-09', NULL, 0.00, 0.00, 0.00, 320.00, 0.00, 100.00, 'GHS', 'Partially Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(35, 'INV-RMU-105', 105, NULL, '2026-04-04', NULL, 0.00, 0.00, 0.00, 150.00, 0.00, 0.00, 'GHS', 'Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(36, 'INV-RMU-106', 106, NULL, '2026-04-12', NULL, 0.00, 0.00, 0.00, 50.00, 0.00, 50.00, 'GHS', 'Pending', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(37, 'INV-RMU-107', 107, NULL, '2026-04-11', NULL, 0.00, 0.00, 0.00, 210.00, 0.00, 0.00, 'GHS', 'Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(38, 'INV-RMU-108', 108, NULL, '2026-04-07', NULL, 0.00, 0.00, 0.00, 890.00, 0.00, 0.00, 'GHS', 'Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(39, 'INV-RMU-109', 109, NULL, '2026-03-15', NULL, 0.00, 0.00, 0.00, 120.00, 0.00, 0.00, 'GHS', 'Paid', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17'),
(40, 'INV-RMU-110', 110, NULL, '2026-04-12', NULL, 0.00, 0.00, 0.00, 300.00, 0.00, 300.00, 'GHS', 'Overdue', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-14 01:03:17', '2026-04-14 01:03:17');

-- --------------------------------------------------------

--
-- Table structure for table `broadcasts`
--

DROP TABLE IF EXISTS `broadcasts`;
CREATE TABLE IF NOT EXISTS `broadcasts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `priority` enum('Informational','Important','Urgent','Critical') DEFAULT 'Informational',
  `sender_id` int NOT NULL,
  `audience_type` enum('Everyone','Role','Department','Individual') DEFAULT 'Everyone',
  `audience_ids` json DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `requires_acknowledgement` tinyint(1) DEFAULT '0',
  `scheduled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('Draft','Scheduled','Sent','Cancelled','Expired') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_schedule` (`status`,`scheduled_at`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `broadcasts`
--

INSERT INTO `broadcasts` (`id`, `subject`, `body`, `priority`, `sender_id`, `audience_type`, `audience_ids`, `attachment_path`, `requires_acknowledgement`, `scheduled_at`, `expires_at`, `status`, `created_at`) VALUES
(1, 'Medication service rendering ', 'Your attention will be required in 30 minutes by Dr. Eli', 'Informational', 1, 'Role', '[\"nurse\"]', NULL, 0, '2026-04-09 16:18:39', NULL, 'Sent', '2026-04-09 16:18:39'),
(2, 'System Maintenance Notice', 'The system will undergo scheduled maintenance on Saturday from 2 AM – 4 AM.', 'Important', 1, 'Role', NULL, NULL, 0, '2026-04-12 10:14:06', NULL, 'Sent', '2026-03-26 05:29:50'),
(3, 'COVID-19 Health Advisory', 'All staff and patients are reminded to wear masks in clinical areas.', 'Urgent', 35, 'Role', NULL, NULL, 0, '2026-04-12 10:14:06', NULL, 'Sent', '2026-03-18 23:55:36'),
(4, 'Pharmacy Update', 'New medications have been added to the pharmacy inventory.', 'Informational', 28, 'Department', NULL, NULL, 0, '2026-04-12 10:14:06', NULL, 'Sent', '2026-03-14 03:47:16'),
(5, 'Emergency Drill', 'A fire evacuation drill is scheduled for next Friday at noon.', 'Informational', 28, 'Department', NULL, NULL, 0, '2026-04-12 10:14:06', NULL, 'Sent', '2026-03-26 23:48:56'),
(6, 'New Lab Equipment', 'The laboratory has received new diagnostic equipment effective this week.', 'Informational', 28, 'Everyone', NULL, NULL, 0, '2026-04-12 10:14:06', NULL, 'Sent', '2026-03-18 17:25:44'),
(7, 'System Maintenance Notice', 'The system will undergo scheduled maintenance on Saturday from 2 AM – 4 AM.', 'Informational', 1, 'Department', NULL, NULL, 0, '2026-04-12 10:22:48', NULL, 'Sent', '2026-03-17 01:34:50'),
(8, 'COVID-19 Health Advisory', 'All staff and patients are reminded to wear masks in clinical areas.', 'Important', 35, 'Department', NULL, NULL, 0, '2026-04-12 10:22:48', NULL, 'Sent', '2026-04-09 04:02:40'),
(9, 'Pharmacy Update', 'New medications have been added to the pharmacy inventory.', 'Informational', 35, 'Everyone', NULL, NULL, 0, '2026-04-12 10:22:48', NULL, 'Sent', '2026-04-09 22:13:13'),
(10, 'Emergency Drill', 'A fire evacuation drill is scheduled for next Friday at noon.', 'Important', 38, 'Everyone', NULL, NULL, 0, '2026-04-12 10:22:48', NULL, 'Sent', '2026-04-02 17:53:12'),
(11, 'New Lab Equipment', 'The laboratory has received new diagnostic equipment effective this week.', 'Urgent', 42, 'Everyone', NULL, NULL, 0, '2026-04-12 10:22:48', NULL, 'Sent', '2026-03-18 19:37:12'),
(12, 'System Maintenance Notice', 'The system will undergo scheduled maintenance on Saturday from 2 AM – 4 AM.', 'Urgent', 41, 'Role', NULL, NULL, 0, '2026-04-12 10:26:48', NULL, 'Sent', '2026-04-03 10:53:43'),
(13, 'COVID-19 Health Advisory', 'All staff and patients are reminded to wear masks in clinical areas.', 'Important', 35, 'Department', NULL, NULL, 0, '2026-04-12 10:26:48', NULL, 'Sent', '2026-03-25 23:36:15'),
(14, 'Pharmacy Update', 'New medications have been added to the pharmacy inventory.', 'Urgent', 35, 'Everyone', NULL, NULL, 0, '2026-04-12 10:26:48', NULL, 'Sent', '2026-03-22 06:36:50'),
(15, 'Emergency Drill', 'A fire evacuation drill is scheduled for next Friday at noon.', 'Urgent', 20, 'Everyone', NULL, NULL, 0, '2026-04-12 10:26:48', NULL, 'Sent', '2026-03-17 01:58:49'),
(16, 'New Lab Equipment', 'The laboratory has received new diagnostic equipment effective this week.', 'Important', 36, 'Department', NULL, NULL, 0, '2026-04-12 10:26:48', NULL, 'Sent', '2026-03-27 19:36:27');

-- --------------------------------------------------------

--
-- Table structure for table `broadcast_recipients`
--

DROP TABLE IF EXISTS `broadcast_recipients`;
CREATE TABLE IF NOT EXISTS `broadcast_recipients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `broadcast_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `recipient_role` varchar(50) NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `broadcast_id` (`broadcast_id`),
  KEY `idx_recipient` (`recipient_id`,`recipient_role`),
  KEY `idx_read` (`read_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `broadcast_recipients`
--

INSERT INTO `broadcast_recipients` (`id`, `broadcast_id`, `recipient_id`, `recipient_role`, `delivered_at`, `read_at`, `acknowledged_at`) VALUES
(1, 1, 26, 'nurse', '2026-04-09 16:19:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocations`
--

DROP TABLE IF EXISTS `budget_allocations`;
CREATE TABLE IF NOT EXISTS `budget_allocations` (
  `allocation_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `fiscal_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. 2026, 2026-2027',
  `fiscal_period` enum('Annual','Q1','Q2','Q3','Q4','Monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Annual',
  `category_id` int UNSIGNED NOT NULL,
  `department` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `spent_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `status` enum('Draft','Active','Exhausted','Closed','Revised','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`allocation_id`),
  KEY `idx_budget_year` (`fiscal_year`),
  KEY `idx_budget_category` (`category_id`),
  KEY `idx_budget_status` (`status`),
  KEY `fk_budget_creator` (`created_by`),
  KEY `fk_budget_approver` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Departmental and categorical budget allocations';

--
-- Dumping data for table `budget_allocations`
--

INSERT INTO `budget_allocations` (`allocation_id`, `fiscal_year`, `fiscal_period`, `category_id`, `department`, `allocated_amount`, `spent_amount`, `remaining_amount`, `currency`, `status`, `notes`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, '2024', 'Annual', 1, 'Pharmacy', 500000.00, 150000.00, 350000.00, 'GHS', 'Active', NULL, NULL, NULL, NULL, '2026-04-14 06:55:15', '2026-04-14 06:55:15'),
(2, '2024', 'Annual', 2, 'Clinical Services', 800000.00, 200000.00, 60000.00, 'GHS', 'Active', NULL, NULL, NULL, NULL, '2026-04-14 06:55:15', '2026-04-14 06:55:15'),
(3, '2024', 'Q2', 3, 'Maintenance', 100000.00, 35000.00, 65000.00, 'GHS', 'Active', NULL, NULL, NULL, NULL, '2026-04-14 06:55:15', '2026-04-14 06:55:15'),
(4, '2024', 'Annual', 4, 'Transport', 250000.00, 45000.00, 205000.00, 'GHS', 'Active', NULL, NULL, NULL, NULL, '2026-04-14 06:55:15', '2026-04-14 06:55:15'),
(5, '2024', 'Q1', 5, 'Administration', 150000.00, 145000.00, 5000.00, 'GHS', 'Exhausted', NULL, NULL, NULL, NULL, '2026-04-14 06:55:15', '2026-04-14 06:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

DROP TABLE IF EXISTS `chatbot_conversations`;
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `conversation_id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PHP session ID or browser UUID',
  `user_id` int DEFAULT NULL COMMENT 'FK to users.id ÔÇö NULL if guest',
  `message_count` int NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_conversations`
--

INSERT INTO `chatbot_conversations` (`conversation_id`, `session_id`, `user_id`, `message_count`, `started_at`, `ended_at`) VALUES
(1, 'bot_69d88db2bbb991.63305392', NULL, 28, '2026-04-10 05:42:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_knowledge_base`
--

DROP TABLE IF EXISTS `chatbot_knowledge_base`;
CREATE TABLE IF NOT EXISTS `chatbot_knowledge_base` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. General, Appointments, Medications, Emergency',
  `intent_tag` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Machine-readable intent key, e.g. book_appointment',
  `keywords` json DEFAULT NULL COMMENT 'JSON array of trigger keywords, e.g. ["book", "schedule", "appointment"]',
  `question_variants` json DEFAULT NULL COMMENT 'JSON array of question phrasings for matching',
  `response_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `followup_suggestion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Optional follow-up prompt for the user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uq_intent` (`intent_tag`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_knowledge_base`
--

INSERT INTO `chatbot_knowledge_base` (`entry_id`, `category`, `intent_tag`, `keywords`, `question_variants`, `response_text`, `followup_suggestion`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(23, 'General', 'greeting', '[\"hi\", \"hello\", \"hey\", \"good morning\", \"good afternoon\"]', '[\"Hello there! How can I assist you at RMU Sickbay today?\", \"Hi! Welcome to RMU Sickbay. What do you need help with?\"]', 'Would you like to book an appointment or check our services?', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(24, 'General', 'about', '[\"who are you\", \"what is this\", \"chatbot\", \"system\"]', '[\"I am the RMU Medical Sickbay AI Assistant. I can help you with bookings, finding doctors, and learning about our services.\", \"I am an automated assistant for RMU Medical!\"]', 'Type \"services\" to see what we offer.', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(25, 'General', 'hours', '[\"time\", \"open\", \"close\", \"working hours\", \"when\"]', '[\"We operate from Monday to Friday, 8am to 8pm, and weekends 9am to 5pm. Emergency is 24/7.\", \"Our general outpatient hours are 8am-8pm on weekdays!\"]', 'Do you need our emergency contact?', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(26, 'General', 'location', '[\"where\", \"location\", \"address\", \"map\", \"find\"]', '[\"We are located within the Regional Maritime University campus in Nungua, Accra.\", \"You can find the sickbay right on the RMU campus.\"]', 'Want me to show you the contact numbers?', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(27, 'General', 'contact', '[\"phone\", \"number\", \"call\", \"email\", \"contact\"]', '[\"You can reach us at 0302716071 or sickbay@rmu.edu.gh.\", \"Our front desk phone is 0302716071.\"]', 'Say \"emergency\" for our rapid hotline.', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(28, 'General', 'payment', '[\"pay\", \"money\", \"cash\", \"insurance\", \"cost\"]', '[\"We accept cash, mobile money, and major insurance cards including NHIS.\", \"Payments can be made via Paystack online or at the counter.\"]', 'Would you like to speak to finance?', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(29, 'General', 'wifi', '[\"internet\", \"wifi\", \"password\"]', '[\"We offer free guest Wi-Fi in the waiting area. Please ask the front desk for the passcode.\", \"Guest WiFi is available!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(30, 'General', 'parking', '[\"park\", \"car\", \"vehicle\"]', '[\"Dedicated parking is available for patients right in front of the clinic.\", \"We have ample parking space.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(31, 'General', 'pharmacy_hours', '[\"pharmacy open\", \"drug store\"]', '[\"The pharmacy is open 24/7 alongside the emergency ward.\", \"You can get medications 24/7.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(32, 'General', 'thanks', '[\"thank\", \"thanks\", \"appreciate\", \"bye\"]', '[\"You are very welcome! Have a healthy day.\", \"Glad I could help. Goodbye!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(33, 'Appointments', 'book', '[\"book\", \"schedule\", \"appointment\", \"see doctor\"]', '[\"You can book an appointment easily by clicking the Book Appointment link at the top of the page.\", \"Head over to the Booking portal to schedule a visit!\"]', 'Click the Book Appointment button to start.', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(34, 'Appointments', 'cancel', '[\"cancel\", \"delete\", \"remove\", \"stop\"]', '[\"To cancel an appointment, please log in and visit the My Bookings section.\", \"You can cancel via your patient dashboard.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(35, 'Appointments', 'reschedule', '[\"change\", \"reschedule\", \"move\", \"postpone\"]', '[\"Currently, you must cancel your existing appointment and book a new one to reschedule.\", \"Please cancel the active one and re-book.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(36, 'Appointments', 'cost', '[\"fee\", \"consultation fee\", \"price\"]', '[\"General consultation fees start at GHS 50, but vary by specialist. Students are covered by school fees.\", \"Consultations vary by doctor.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(37, 'Appointments', 'doctors', '[\"who\", \"list doctors\", \"available doctors\"]', '[\"We have general practitioners and specialists available. Check the Our Doctors page!\", \"Our doctors schedule is posted on the Booking page.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(38, 'Appointments', 'walkin', '[\"walk\", \"walk in\", \"without appointment\"]', '[\"Yes, walk-ins are accepted, but booked appointments are prioritized.\", \"Walk-ins are fine but you might wait longer.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(39, 'Appointments', 'wait_time', '[\"long\", \"wait\", \"time\"]', '[\"Average waiting time for walk-ins is 20-30 minutes.\", \"Booked patients are seen immediately.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(40, 'Appointments', 'virtual', '[\"online\", \"video\", \"telehealth\"]', '[\"Currently we only offer in-person consultations.\", \"All visits are physical at the moment.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(41, 'Appointments', 'referral', '[\"refer\", \"transfer\"]', '[\"You will need to be seen by a general doctor first to get a specialist referral.\", \"Referrals require an initial checkup.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(42, 'Appointments', 'records', '[\"history\", \"file\", \"medical record\"]', '[\"Your medical history is completely digitized and available in your patient dashboard.\", \"Log in to view your records.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(43, 'Services', 'list', '[\"services\", \"what do you do\", \"offer\"]', '[\"We offer General Consultation, Lab Tests, Pharmacy, Ambulance, and Bed Facilities.\", \"Check our Services page for a full list!\"]', 'Want to know more about the lab?', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(44, 'Services', 'lab', '[\"laboratory\", \"blood\", \"test\", \"urine\"]', '[\"Our ultra-modern laboratory conducts blood, urine, and pathology tests.\", \"We have a full lab on-site.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(45, 'Services', 'pharmacy', '[\"drugs\", \"medicine\", \"pill\", \"prescription\"]', '[\"Our pharmacy is fully stocked. Some items may require a prescription from our doctors.\", \"Visit the Pharmacy tab to check inventory.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(46, 'Services', 'ambulance_service', '[\"ambulance info\", \"transport\"]', '[\"Our ambulance is equipped for life support and available for community dispatch.\", \"We have 24/7 ambulance services.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(47, 'Services', 'beds', '[\"ward\", \"admit\", \"admission\", \"bed\"]', '[\"We have general and semi-private wards for inpatient care.\", \"We offer comfortable admission facilities.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(48, 'Services', 'maternity', '[\"pregnant\", \"baby\", \"birth\", \"maternity\"]', '[\"We handle basic antenatal care, but specialized deliveries are referred to the General Hospital.\", \"We do antenatal checkups.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(49, 'Services', 'dental', '[\"teeth\", \"tooth\", \"dental\", \"dentist\"]', '[\"We currently do not have a dental wing on-site.\", \"Dental services are not available at this exact branch.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(50, 'Services', 'eye', '[\"eye\", \"vision\", \"optician\"]', '[\"Optometry is available on Wednesdays and Fridays by appointment.\", \"Eye clinic runs twice a week.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(51, 'Services', 'therapy', '[\"physio\", \"massage\", \"therapy\"]', '[\"Physiotherapy must be specifically booked through a specialist.\", \"We offer basic physiotherapy.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(52, 'Services', 'xray', '[\"xray\", \"scan\", \"ultrasound\"]', '[\"We have an ultrasound machine; X-Ray services are referred out.\", \"Basic scans are available.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(53, 'Emergency', 'sos', '[\"help\", \"emergency\", \"dying\", \"urgent\", \"crash\", \"accident\"]', '[\"This is an EMERGENCY! Please call our immediate hotline at 153 or 0302716071 NOW!\", \"For life threatening emergencies call 153 immediately!\"]', 'Call 153 now!', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(54, 'Emergency', 'ambulance', '[\"need ambulance\", \"send ambulance\", \"dispatch\"]', '[\"If you need an ambulance, call 153 or use the Ambulance Request portal on the website ASAP.\", \"Use the quick ambulance portal!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(55, 'Emergency', 'first_aid', '[\"bleed\", \"burn\", \"choke\", \"first aid\"]', '[\"Please do not wait for the bot. Call 153 for immediate professional guidance over the phone.\", \"Call 153 for first aid guidance.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(56, 'Emergency', 'poison', '[\"poison\", \"swallow\", \"chemical\"]', '[\"For poison control, head to the Emergency Ward immediately or call 153.\", \"Rush to the emergency room!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(57, 'Emergency', 'heart', '[\"chest pain\", \"heart\", \"attack\"]', '[\"Chest pain is a critical emergency. Please dial 153 for an ambulance immediately.\", \"Call 153 immediately!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(58, 'Emergency', 'breathing', '[\"breathe\", \"asthma\", \"choking\"]', '[\"Severe breathing difficulty requires immediate intervention. Call 153!\", \"Dial 153 immediately!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(59, 'Emergency', 'unconscious', '[\"faint\", \"passed out\", \"wake\"]', '[\"Do not move the person unless in danger. Call 153 for ambulance dispatch.\", \"Call 153!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(60, 'Emergency', 'burns', '[\"fire\", \"burn\", \"hot\"]', '[\"Run cool (not freezing) water over the burn and call 153 for severe burns.\", \"Wash with cool water and report to clinic.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(61, 'Emergency', 'allergy', '[\"swelling\", \"allergic\", \"anaphylaxis\"]', '[\"Severe allergic reactions are emergencies. Call 153 or report immediately.\", \"Use an EpiPen if available and call 153!\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34'),
(62, 'Emergency', 'seizure', '[\"fit\", \"seizure\", \"shaking\"]', '[\"Clear the area of hard objects and call 153. Do not put anything in their mouth.\", \"Call 153 immediately for seizures.\"]', '', NULL, 1, NULL, '2026-04-10 23:52:34', '2026-04-10 23:52:34');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_messages`
--

DROP TABLE IF EXISTS `chatbot_messages`;
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `sender` enum('user','bot') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent_matched` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The intent_tag matched from knowledge base',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender` (`sender`),
  KEY `idx_intent_matched` (`intent_matched`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_messages`
--

INSERT INTO `chatbot_messages` (`message_id`, `conversation_id`, `sender`, `message_text`, `intent_matched`, `sent_at`) VALUES
(1, 1, 'user', 'good morning', NULL, '2026-04-10 05:42:10'),
(2, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 05:42:10'),
(3, 1, 'user', 'Emergency contact', NULL, '2026-04-10 05:42:18'),
(4, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 05:42:18'),
(5, 1, 'user', 'medical tips', NULL, '2026-04-10 05:42:42'),
(6, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 05:42:42'),
(7, 1, 'user', 'Our services', NULL, '2026-04-10 05:42:47'),
(8, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 05:42:47'),
(9, 1, 'user', 'Lab tests', NULL, '2026-04-10 06:12:46'),
(10, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 06:12:46'),
(11, 1, 'user', 'Emergency contact', NULL, '2026-04-10 06:12:49'),
(12, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 06:12:49'),
(13, 1, 'user', 'Book an appointment', NULL, '2026-04-10 06:12:50'),
(14, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-10 06:12:50'),
(15, 1, 'user', 'Book an appointment', NULL, '2026-04-11 04:32:41'),
(16, 1, 'bot', 'Click the Book Appointment button to start.', 'book', '2026-04-11 04:32:41'),
(17, 1, 'user', 'Book an appointment', NULL, '2026-04-11 04:32:59'),
(18, 1, 'bot', 'Click the Book Appointment button to start.', 'book', '2026-04-11 04:32:59'),
(19, 1, 'user', 'Our services', NULL, '2026-04-13 07:26:32'),
(20, 1, 'bot', 'Want to know more about the lab?', 'list', '2026-04-13 07:26:32'),
(21, 1, 'user', 'yes', NULL, '2026-04-13 07:26:45'),
(22, 1, 'bot', 'I\'m not quite sure I understand what you\'re asking. Could you rephrase that for me? I\'m here to help with questions about our medical services, appointments, and more. 🏥', NULL, '2026-04-13 07:26:45'),
(23, 1, 'user', 'Emergency contact', NULL, '2026-04-13 07:26:48'),
(24, 1, 'bot', 'Say \"emergency\" for our rapid hotline.', 'contact', '2026-04-13 07:26:48'),
(25, 1, 'user', 'Emergency contact', NULL, '2026-04-13 07:27:01'),
(26, 1, 'bot', 'Say \"emergency\" for our rapid hotline.', 'contact', '2026-04-13 07:27:01'),
(27, 1, 'user', 'Book an appointment', NULL, '2026-04-13 07:27:05'),
(28, 1, 'bot', 'Click the Book Appointment button to start.', 'book', '2026-04-13 07:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_logs`
--

DROP TABLE IF EXISTS `cleaning_logs`;
CREATE TABLE IF NOT EXISTS `cleaning_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int DEFAULT NULL,
  `staff_id` int NOT NULL,
  `ward_room_area` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cleaning_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `checklist_completed` tinyint(1) DEFAULT '0',
  `sanitation_status` enum('clean','contaminated','pending inspection') COLLATE utf8mb4_unicode_ci DEFAULT 'clean',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `photo_proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issues_reported` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cleaning_logs`
--

INSERT INTO `cleaning_logs` (`log_id`, `schedule_id`, `staff_id`, `ward_room_area`, `cleaning_type`, `started_at`, `completed_at`, `checklist_completed`, `sanitation_status`, `notes`, `photo_proof_path`, `issues_reported`, `created_at`) VALUES
(1, NULL, 11, 'Ward A', 'Routine', NULL, NULL, 0, 'clean', 'Mopped and dusted surfaces.', NULL, 0, '2026-04-14 01:00:58'),
(2, NULL, 11, 'OPD Waiting Hall', 'Deep Clean', NULL, NULL, 0, 'clean', 'Floor scrubbed and chairs sanitized.', NULL, 0, '2026-04-14 01:00:58'),
(3, NULL, 11, 'Laboratory', 'Routine', NULL, NULL, 0, 'clean', 'Biohazard waste disposed.', NULL, 0, '2026-04-14 01:00:58'),
(4, NULL, 11, 'Pharmacy', 'Routine', NULL, NULL, 0, 'clean', 'Shelves dusted.', NULL, 0, '2026-04-14 01:00:58'),
(5, NULL, 11, 'Isolation Unit', 'Disinfection', NULL, NULL, 0, 'clean', 'Patient discharged; room needs UV treatment.', NULL, 0, '2026-04-14 01:00:58'),
(6, NULL, 11, 'Emergency Room', 'Emergency', NULL, NULL, 0, 'clean', 'Blood spill cleaned and floor sanitized.', NULL, 0, '2026-04-14 01:00:58'),
(7, NULL, 11, 'Doctor Office 1', 'Routine', NULL, NULL, 0, 'clean', 'Trash emptied.', NULL, 0, '2026-04-14 01:00:58'),
(8, NULL, 11, 'Staff Lounge', 'Routine', NULL, NULL, 0, 'clean', 'Table wiped.', NULL, 0, '2026-04-14 01:00:58'),
(9, NULL, 11, 'Corridor South', 'Routine', NULL, NULL, 0, 'clean', 'Floor polished.', NULL, 0, '2026-04-14 01:00:58'),
(10, NULL, 11, 'Restroom Block C', 'Routine', NULL, NULL, 0, 'clean', 'Refilled soap and tissue.', NULL, 0, '2026-04-14 01:00:58');

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_schedules`
--

DROP TABLE IF EXISTS `cleaning_schedules`;
CREATE TABLE IF NOT EXISTS `cleaning_schedules` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `assigned_cleaner_id` int DEFAULT NULL,
  `backup_cleaner_id` int DEFAULT NULL,
  `ward_room_area` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specific_room` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor_building` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `scheduled_time` datetime DEFAULT NULL,
  `cleaning_type` enum('routine','deep clean','biohazard','post-discharge') COLLATE utf8mb4_unicode_ci DEFAULT 'routine',
  `contamination_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Low',
  `required_ppe` text COLLATE utf8mb4_unicode_ci,
  `recurrence_pattern` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('scheduled','in progress','completed','missed') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `priority` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Routine',
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `fk_clean_assigned_cleaner` (`assigned_cleaner_id`),
  KEY `fk_clean_backup_cleaner` (`backup_cleaner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contamination_reports`
--

DROP TABLE IF EXISTS `contamination_reports`;
CREATE TABLE IF NOT EXISTS `contamination_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `reported_by` int NOT NULL COMMENT 'staff ID',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contamination_type` enum('biohazard','chemical','biological','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('reported','acknowledged','in progress','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'reported',
  `resolved_by` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `admin_notified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contamination_reports`
--

INSERT INTO `contamination_reports` (`report_id`, `reported_by`, `location`, `contamination_type`, `severity`, `description`, `photo_path`, `reported_at`, `status`, `resolved_by`, `resolved_at`, `admin_notified`) VALUES
(1, 19, 'General Ward B', 'biological', 'critical', 'immense damage has been caused', 'uploads/staff/contamination/stf_69e770275a12b8.00068956.jpg', '2026-04-21 12:40:07', 'reported', NULL, NULL, 0),
(2, 19, 'ICU', 'chemical', 'medium', 'Great harm has been caused, leading to the Senior nurse passing out', 'uploads/staff/contamination/stf_69e77e5a891bb7.16135744.jpg', '2026-04-21 13:40:42', 'reported', NULL, NULL, 0),
(3, 19, 'ICU', 'chemical', 'high', 'Great harm has been caused, leading to the Senior nurse passing out', 'uploads/staff/contamination/stf_69e77e78bac853.18082379.jpg', '2026-04-21 13:41:12', 'reported', NULL, NULL, 0),
(4, 19, 'OPD Waiting Hall', 'biohazard', 'low', 'Less harm was caused', 'uploads/staff/contamination/stf_69e783890973e7.28487699.jpg', '2026-04-21 14:02:49', 'reported', NULL, NULL, 0),
(5, 19, 'Emergency Room', 'general', 'medium', 'Excessive flow of waste in the emergency room', 'uploads/staff/contamination/stf_69e7a429e95659.89112324.jpg', '2026-04-21 16:22:01', 'reported', NULL, NULL, 0),
(6, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:15', 'reported', NULL, NULL, 0),
(7, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:16', 'reported', NULL, NULL, 0),
(8, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:17', 'reported', NULL, NULL, 0),
(9, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:17', 'reported', NULL, NULL, 0),
(10, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:17', 'reported', NULL, NULL, 0),
(11, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:18', 'reported', NULL, NULL, 0),
(12, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:18', 'reported', NULL, NULL, 0),
(13, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(14, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(15, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(16, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(17, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(18, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(19, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(20, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(21, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:19', 'reported', NULL, NULL, 0),
(22, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:20', 'reported', NULL, NULL, 0),
(23, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:21', 'reported', NULL, NULL, 0),
(24, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:21', 'reported', NULL, NULL, 0),
(25, 19, 'OPD Waiting Hall', 'chemical', 'high', 'Damage caused', NULL, '2026-04-21 16:24:21', 'reported', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_cash_reports`
--

DROP TABLE IF EXISTS `daily_cash_reports`;
CREATE TABLE IF NOT EXISTS `daily_cash_reports` (
  `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_cash_received` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_mobile_money` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_card_payments` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_bank_transfers` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_paystack_payments` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_insurance_claims` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_refunds_issued` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_waivers` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discrepancy` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discrepancy_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Open','Submitted','Reconciled','Flagged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Open',
  `generated_by` int DEFAULT NULL,
  `reconciled_by` int DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `uq_daily_report_date` (`report_date`),
  KEY `idx_daily_status` (`status`),
  KEY `fk_daily_generated` (`generated_by`),
  KEY `fk_daily_reconciled` (`reconciled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='End-of-day cash reconciliation reports';

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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(13, 'Laboratory', 'Diagnostic lab services', NULL, 1, '2026-03-02 09:09:24', '2026-03-02 09:09:24'),
(14, 'Emergency Dept', 'The Emergency department at RMU Medical Sickbay.', NULL, 1, '2023-11-18 02:42:32', '2026-04-12 10:22:48'),
(15, 'Cardiology Dept', 'The Cardiology department at RMU Medical Sickbay.', NULL, 1, '2024-12-04 14:08:02', '2026-04-12 10:22:48'),
(16, 'Pediatrics Dept', 'The Pediatrics department at RMU Medical Sickbay.', NULL, 1, '2023-09-14 03:13:26', '2026-04-12 10:22:48'),
(17, 'Surgery Dept', 'The Surgery department at RMU Medical Sickbay.', NULL, 1, '2025-01-02 08:49:00', '2026-04-12 10:22:48'),
(18, 'Pharmacy Dept', 'The Pharmacy department at RMU Medical Sickbay.', NULL, 1, '2024-01-27 05:49:58', '2026-04-12 10:22:48');

-- --------------------------------------------------------

--
-- Table structure for table `discharge_instructions`
--

DROP TABLE IF EXISTS `discharge_instructions`;
CREATE TABLE IF NOT EXISTS `discharge_instructions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `instruction_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `instruction_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `documents_uploaded` json DEFAULT NULL COMMENT 'Array of uploaded document paths',
  `given_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `patient_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instruction_id` (`instruction_id`),
  KEY `idx_di_instr_id` (`instruction_id`),
  KEY `idx_di_patient` (`patient_id`),
  KEY `idx_di_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Discharge instructions provided to patients by nurses';

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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dispensing_records`
--

INSERT INTO `dispensing_records` (`id`, `prescription_id`, `patient_id`, `pharmacist_id`, `medicine_id`, `quantity_dispensed`, `dispensing_date`, `selling_price`, `payment_status`, `notes`, `created_at`) VALUES
(1, 1, 101, 203, 1, 21, '2026-04-14 01:06:25', 0.00, 'unpaid', NULL, '2026-04-14 01:06:25'),
(2, 2, 102, 203, 2, 15, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(3, 4, 104, 203, 4, 30, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(4, 5, 105, 203, 5, 28, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(5, 6, 106, 203, 6, 1, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(6, 8, 108, 203, 8, 10, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(7, 9, 109, 203, 9, 30, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(8, 10, 110, 203, 10, 14, '2026-04-14 01:06:25', 0.00, 'paid', NULL, '2026-04-14 01:06:25'),
(9, 3, 103, 203, 3, 20, '2026-04-14 01:06:25', 0.00, 'unpaid', NULL, '2026-04-14 01:06:25'),
(10, 7, 107, 203, 7, 30, '2026-04-14 01:06:25', 0.00, 'insurance', NULL, '2026-04-14 01:06:25');

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
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_id` (`doctor_id`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `user_id` (`user_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_specialization` (`specialization`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `doctor_id`, `specialization`, `department_id`, `sub_specialization`, `designation`, `professional_title`, `qualifications`, `experience_years`, `license_number`, `license_issuing_body`, `license_expiry_date`, `medical_school`, `graduation_year`, `postgraduate_details`, `languages_spoken`, `consultation_fee`, `available_days`, `available_hours`, `bio`, `nationality`, `marital_status`, `religion`, `national_id`, `secondary_phone`, `personal_email`, `street_address`, `city`, `region`, `country`, `postal_code`, `office_location`, `is_available`, `availability_status`, `profile_completion_pct`, `created_at`, `updated_at`, `full_name`, `gender`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(4, 20, 'DOC-0002', '', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, NULL, 1, 'Offline', 0, '2026-03-20 18:21:21', '2026-03-31 14:39:24', 'Joyce Eli', NULL, 'approved', 1, '2026-03-31 14:39:24', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`id`, `doctor_id`, `day_of_week`, `is_available`, `start_time`, `end_time`, `max_appointments`, `slot_duration_min`, `updated_at`) VALUES
(1, 4, 'Monday', 1, '08:00:00', '17:00:00', 16, 30, '2026-04-12 10:26:49'),
(2, 4, 'Tuesday', 1, '08:00:00', '17:00:00', 16, 30, '2026-04-12 10:26:49'),
(3, 4, 'Wednesday', 1, '08:00:00', '17:00:00', 16, 30, '2026-04-12 10:26:49');

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
-- Table structure for table `email_queue_log`
--

DROP TABLE IF EXISTS `email_queue_log`;
CREATE TABLE IF NOT EXISTS `email_queue_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_reset, otp, welcome, 2fa, etc.',
  `status` enum('sent','failed','queued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_type` (`to_email`,`email_type`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all outbound email delivery attempts';

--
-- Dumping data for table `email_queue_log`
--

INSERT INTO `email_queue_log` (`id`, `to_email`, `email_type`, `status`, `error_message`, `sent_at`) VALUES
(1, 'atakorahe57@gmail.com', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-04 09:37:26'),
(2, 'jefferson.forson@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-09 18:53:03'),
(3, 'atakorahe57@gmail.com', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-09 18:57:06'),
(4, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-11 00:13:17'),
(5, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-11 21:32:00'),
(6, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-11 21:46:02'),
(7, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-17 05:23:15'),
(8, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-17 05:44:33'),
(9, 'lovelace.baidoo@st.rmu.edu.gh', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-17 07:12:12');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `verification_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID token sent in verification email',
  `user_id` int UNSIGNED DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bcrypt hash of 6-digit OTP',
  `otp_expires_at` datetime NOT NULL,
  `attempts_made` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `verification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registration',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verification_id` (`verification_id`),
  KEY `idx_ev_email` (`email`),
  KEY `idx_ev_user` (`user_id`),
  KEY `idx_ev_type` (`verification_type`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `verification_id`, `user_id`, `email`, `otp_code`, `otp_expires_at`, `attempts_made`, `is_used`, `verification_type`, `created_at`) VALUES
(1, 'f8d9f129a88fb82f90fa1f391b33ddc13824234f83408b21f2a660b098570e3c', NULL, 'atakorahe57@gmail.com', '$2y$10$zU6mgmpgbE6wFjq3OIcQyOlkjGvq.kULlufKjH3gf9uSqmnVKenSu', '2026-04-09 15:07:55', 1, 1, 'registration', '2026-04-09 14:57:55'),
(2, '8b885df0d46c4589bd7d52468337173ae4ccb960662d03d595cb67c36f3ceffa', NULL, 'lovelace.baidoo@st.rmu.edu.gh', '$2y$10$d5PEv8b7Ua43LCet.kN1n.4I0h9s9OyKyAqChlkBvzhOSGNbGsr/6', '2026-04-11 00:20:47', 1, 1, 'registration', '2026-04-11 00:10:47'),
(3, 'f4fd97328f2c82f5c456cba1c6522a57ba1a5246829127a6b583bcb3cf208c20', NULL, 'samuel.enguah@rmu.edu.gh', '$2y$10$/oLL8YYDdsduSMNnIxWuiOaUXGQ220HxdfhJHPqg9nj7XCcuxuT66', '2026-04-15 12:24:52', 1, 1, 'registration', '2026-04-15 12:14:52'),
(4, 'c751bd8dc88e7727c2038bd81ff5050f48a878bda660ac5d74b4976bd17bdad0', NULL, 'junior.barns@gmail.com', '$2y$10$XQ/InEO3PMjWXxtLEpy63.bGdksVOUL5EuvFfS3kksQMwIXvcdBX.', '2026-04-15 12:39:45', 1, 1, 'registration', '2026-04-15 12:29:45'),
(7, '4fc99ff593db9e2a75c0e6531ef7134e47bd91df50fc694c47f0e2cd29f3ce9b', NULL, 'www.lovelacejohnbaidoo@gmail.com', '$2y$10$Octg9fqR2inRp/LR8BugOe//Ayo7XikSrTsU4HnTqVMLmpFKcA8D6', '2026-04-17 09:40:01', 1, 1, 'registration', '2026-04-17 09:30:01'),
(8, 'c747ef9732555c26cbc566a21ffa1eeb8fb8cf9cce8b85195220fb18608834ce', NULL, 'joseph.agyemang@st.rmu.edu.gh', '$2y$10$SGYoqv2/.8GXXC1j5L/WE.ynFjkidiT1/IAgPrZlsVKmQK/lFwgPu', '2026-04-21 04:41:39', 1, 1, 'registration', '2026-04-21 04:31:39'),
(9, '73ccc337ff26d372d0e38ae0c065d7db90c4d0e93b68e7eaa41407072d4bdd0b', NULL, 'bernard.boateng@st.rmu.edu.gh', '$2y$10$fWlO4Nn.6dbUDBMmaIRbq.avZW24mgx6WbYZa7yj5pCI4jKwyCmji', '2026-04-21 09:21:12', 1, 1, 'registration', '2026-04-21 09:11:12'),
(10, '4da156529a9fbd53998ba2ae62f1f3a744aefe0c5dc33cbbb0c1ff3cf8a8c8de', NULL, 'gifty.asante@st.rmu.edu.gh', '$2y$10$zdtUYdPhsswerDmn0Ypr7OPXg3h/f5fQ2FkYvabVS8E0ka6fXI7ni', '2026-04-21 10:22:36', 1, 1, 'registration', '2026-04-21 10:12:36'),
(11, '16417b44a2719c6c317806f4a101857aa0c78944eadf2fedcb0c98418157e6ee', NULL, 'micheeal.asante@st.rmu.edu.gh', '$2y$10$Me5wEDUlM1.Itr6h0RxyKuIrO598A/7Qw3rjQPCE36gxTR6WbObI2', '2026-05-05 06:54:41', 1, 1, 'registration', '2026-05-05 06:44:41'),
(12, '83dfd8d22100c51b1102de1fcf0835dfcf9492e4cde236af547ddae8385adf30', NULL, 'junior.owusu@st.rmu.edu.gh', '$2y$10$R2nthXv0Y/iF3NTPX2uMZe0v9KqH3Q/3e9AqIpM0TRhvt8qU0Xn06', '2026-05-14 10:37:19', 1, 1, 'registration', '2026-05-14 10:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_alerts`
--

DROP TABLE IF EXISTS `emergency_alerts`;
CREATE TABLE IF NOT EXISTS `emergency_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `alert_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `patient_id` int DEFAULT NULL COMMENT 'FK → patients.id',
  `alert_type` enum('Code Blue','Rapid Response','Fall','Cardiac Arrest','Fire','General Emergency','Medication Error','Security') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('Critical','High','Medium','Low') COLLATE utf8mb4_unicode_ci DEFAULT 'High',
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ward and bed number',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notified_doctors` json DEFAULT NULL COMMENT 'Array of notified doctor user IDs',
  `status` enum('Active','Responded','Resolved','False Alarm') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `triggered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int DEFAULT NULL COMMENT 'FK → users.id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_id` (`alert_id`),
  KEY `resolved_by` (`resolved_by`),
  KEY `idx_ea_alert_id` (`alert_id`),
  KEY `idx_ea_nurse` (`nurse_id`),
  KEY `idx_ea_patient` (`patient_id`),
  KEY `idx_ea_status` (`status`),
  KEY `idx_ea_severity` (`severity`),
  KEY `idx_ea_triggered` (`triggered_at`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emergency alerts triggered by nurses (code blue, falls, etc.)';

--
-- Dumping data for table `emergency_alerts`
--

INSERT INTO `emergency_alerts` (`id`, `alert_id`, `nurse_id`, `patient_id`, `alert_type`, `severity`, `location`, `message`, `notified_doctors`, `status`, `triggered_at`, `responded_at`, `resolved_at`, `resolved_by`, `created_at`) VALUES
(1, 'ALT-17285', 1, 5, 'Rapid Response', 'High', NULL, 'Code Blue — cardiac arrest in Ward A.', NULL, 'Active', '2026-04-06 11:57:19', NULL, NULL, NULL, '2026-04-10 03:47:22'),
(2, 'ALT-52611', 1, 5, 'General Emergency', 'Critical', NULL, 'Patient fall reported in Room 12.', NULL, 'Responded', '2026-03-27 11:25:46', NULL, NULL, NULL, '2026-03-14 13:54:02'),
(3, 'ALT-46811', 26, 5, 'Fall', 'Medium', NULL, 'Critical lab result for patient ID P-101.', NULL, 'Responded', '2026-04-11 07:13:25', NULL, NULL, NULL, '2026-03-23 15:28:19'),
(4, 'ALT-48980', 1, 5, 'General Emergency', 'Low', NULL, 'Fire alarm activated — evacuation in progress.', NULL, 'Active', '2026-03-30 17:53:17', NULL, NULL, NULL, '2026-04-03 12:49:55'),
(5, 'ALT-67710', 1, 5, 'Fall', 'High', NULL, 'Missing patient reported — alert all staff.', NULL, 'Active', '2026-03-19 17:32:11', NULL, NULL, NULL, '2026-03-13 14:31:18'),
(6, 'ALT-15318', 43, 5, 'Rapid Response', 'High', NULL, 'Code Blue — cardiac arrest in Ward A.', NULL, 'Active', '2026-03-13 20:51:54', NULL, NULL, NULL, '2026-04-04 09:32:35'),
(7, 'ALT-35074', 1, 5, 'General Emergency', 'High', NULL, 'Patient fall reported in Room 12.', NULL, 'Resolved', '2026-03-25 08:40:08', NULL, NULL, NULL, '2026-04-02 06:18:09'),
(8, 'ALT-28101', 36, 5, 'General Emergency', 'Medium', NULL, 'Critical lab result for patient ID P-101.', NULL, 'Responded', '2026-04-08 09:38:57', NULL, NULL, NULL, '2026-04-06 03:52:42'),
(9, 'ALT-53668', 44, 5, 'Code Blue', 'High', NULL, 'Fire alarm activated — evacuation in progress.', NULL, 'Resolved', '2026-03-13 22:09:55', NULL, NULL, NULL, '2026-03-13 17:18:26'),
(10, 'ALT-23999', 1, 5, 'Code Blue', 'Medium', NULL, 'Missing patient reported — alert all staff.', NULL, 'Resolved', '2026-04-08 04:34:04', NULL, NULL, NULL, '2026-03-14 03:40:26'),
(11, 'ALT-69611', 1, 7, 'General Emergency', 'High', NULL, 'Code Blue — cardiac arrest in Ward A.', NULL, 'Active', '2026-04-11 22:34:44', NULL, NULL, NULL, '2026-04-01 21:16:16'),
(12, 'ALT-50228', 1, 8, 'Cardiac Arrest', 'Medium', NULL, 'Patient fall reported in Room 12.', NULL, 'Resolved', '2026-04-04 11:12:01', NULL, NULL, NULL, '2026-03-25 08:36:46'),
(13, 'ALT-76511', 26, 7, 'Code Blue', 'Medium', NULL, 'Critical lab result for patient ID P-101.', NULL, 'Active', '2026-04-04 12:56:03', NULL, NULL, NULL, '2026-03-12 17:10:39'),
(14, 'ALT-43787', 1, 7, 'Fall', 'High', NULL, 'Fire alarm activated — evacuation in progress.', NULL, 'Responded', '2026-03-13 11:28:54', NULL, NULL, NULL, '2026-03-14 17:02:03'),
(15, 'ALT-59073', 26, 9, 'Rapid Response', 'Low', NULL, 'Missing patient reported — alert all staff.', NULL, 'Active', '2026-03-20 00:27:49', NULL, NULL, NULL, '2026-04-06 21:47:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`id`, `patient_id`, `contact_name`, `relationship`, `phone`, `email`, `address`, `is_primary`, `created_at`, `updated_at`) VALUES
(8, 5, 'Melissa Deborah Mensah  ', 'Friend', '0549871687', 'mensahmelissa58@gmail.com', 'Tema', 1, '2026-04-11 05:00:07', '2026-04-11 05:00:07'),
(9, 5, 'Eric Bekoe', 'Friend', '0545066413', 'contact983@gmail.com', '15 Kumasi, Ghana', 1, '2025-12-29 03:52:26', '2026-04-12 10:14:05'),
(10, 5, 'Kofi Amoah', 'Child', '0547000612', 'contact677@gmail.com', '2 Kumasi, Ghana', 0, '2025-11-22 17:02:54', '2026-04-12 10:14:05'),
(11, 5, 'George Mensah', 'Child', '0546772773', 'contact213@gmail.com', '14 Kumasi, Ghana', 0, '2025-05-26 12:12:20', '2026-04-12 10:14:05'),
(12, 5, 'Bright Bekoe', 'Spouse', '0548557451', 'contact345@gmail.com', '17 Kumasi, Ghana', 0, '2026-02-04 22:53:11', '2026-04-12 10:14:05'),
(13, 5, 'Bright Gyamfi', 'Sibling', '0544662382', 'contact653@gmail.com', '4 Kumasi, Ghana', 0, '2025-05-03 08:33:11', '2026-04-12 10:14:05'),
(14, 5, 'Frank Darko', 'Parent', '0541660632', 'contact679@gmail.com', '2 Kumasi, Ghana', 0, '2026-02-24 14:01:04', '2026-04-12 10:14:05'),
(15, 5, 'Kofi Boateng', 'Sibling', '0546999339', 'contact786@gmail.com', '17 Kumasi, Ghana', 0, '2025-12-25 18:22:09', '2026-04-12 10:14:05'),
(16, 5, 'Nana Darko', 'Child', '0545949192', 'contact488@gmail.com', '16 Kumasi, Ghana', 0, '2025-06-16 00:51:20', '2026-04-12 10:14:05'),
(17, 5, 'Daniel Appiah', 'Parent', '0546811415', 'contact677@gmail.com', '11 Kumasi, Ghana', 1, '2025-07-06 05:16:38', '2026-04-12 10:22:48'),
(18, 5, 'Isaac Boateng', 'Friend', '0542115884', 'contact850@gmail.com', '11 Kumasi, Ghana', 0, '2025-10-12 03:08:45', '2026-04-12 10:22:48'),
(19, 5, 'Samuel Appiah', 'Parent', '0547882837', 'contact556@gmail.com', '20 Kumasi, Ghana', 0, '2025-07-20 17:13:42', '2026-04-12 10:22:48'),
(20, 5, 'Frank Yeboah', 'Friend', '0549853239', 'contact120@gmail.com', '17 Kumasi, Ghana', 0, '2025-06-14 17:00:01', '2026-04-12 10:22:48'),
(21, 5, 'George Appiah', 'Parent', '0547154376', 'contact452@gmail.com', '15 Kumasi, Ghana', 0, '2025-12-28 15:38:36', '2026-04-12 10:22:48'),
(22, 5, 'Eric Boateng', 'Child', '0547404359', 'contact315@gmail.com', '10 Kumasi, Ghana', 0, '2025-06-02 05:14:30', '2026-04-12 10:22:48'),
(23, 5, 'Kofi Bekoe', 'Friend', '0544432646', 'contact122@gmail.com', '16 Kumasi, Ghana', 0, '2026-01-20 10:31:40', '2026-04-12 10:22:48'),
(24, 5, 'Daniel Gyamfi', 'Friend', '0545993852', 'contact586@gmail.com', '4 Kumasi, Ghana', 0, '2025-12-19 11:29:29', '2026-04-12 10:22:48'),
(25, 5, 'Nana Mensah', 'Child', '0546101965', 'contact990@gmail.com', '6 Kumasi, Ghana', 1, '2025-06-28 22:56:00', '2026-04-12 10:26:48'),
(26, 10, 'Kwame Tawiah', 'Sibling', '0549289984', 'contact641@gmail.com', '16 Kumasi, Ghana', 0, '2025-09-04 19:08:51', '2026-04-12 10:26:48'),
(27, 6, 'Ama Mensah', 'Child', '0548390998', 'contact280@gmail.com', '12 Kumasi, Ghana', 0, '2025-09-09 06:37:08', '2026-04-12 10:26:48'),
(28, 6, 'George Owusu', 'Sibling', '0548091909', 'contact935@gmail.com', '14 Kumasi, Ghana', 0, '2025-06-18 02:29:45', '2026-04-12 10:26:48'),
(29, 9, 'George Tawiah', 'Parent', '0542660950', 'contact300@gmail.com', '7 Kumasi, Ghana', 0, '2025-06-09 19:32:44', '2026-04-12 10:26:48'),
(30, 5, 'Joseph Amoah', 'Sibling', '0547317976', 'contact436@gmail.com', '11 Kumasi, Ghana', 0, '2025-05-01 13:56:26', '2026-04-12 10:26:48'),
(31, 7, 'Ama Yeboah', 'Parent', '0545998524', 'contact589@gmail.com', '8 Kumasi, Ghana', 0, '2025-07-13 16:21:34', '2026-04-12 10:26:48'),
(32, 7, 'Daniel Owusu', 'Sibling', '0542914159', 'contact500@gmail.com', '15 Kumasi, Ghana', 0, '2025-08-04 05:09:45', '2026-04-12 10:26:48');

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
-- Table structure for table `fee_categories`
--

DROP TABLE IF EXISTS `fee_categories`;
CREATE TABLE IF NOT EXISTS `fee_categories` (
  `category_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_categories`
--

INSERT INTO `fee_categories` (`category_id`, `category_name`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Consultations', 1, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(2, 'Specialist Consultations', 2, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(3, 'Pharmacy & Medications', 3, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(4, 'Laboratory Investigations', 4, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(5, 'Radiology & Imaging', 5, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(6, 'Nursing & Ward Procedures', 6, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(7, 'Emergency & First Aid', 7, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(8, 'Health Records & Admin', 8, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44'),
(9, 'Consumables & Supplies', 9, 1, '2026-04-09 18:25:44', '2026-04-09 18:25:44');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule`
--

DROP TABLE IF EXISTS `fee_schedule`;
CREATE TABLE IF NOT EXISTS `fee_schedule` (
  `fee_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `base_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'GHS — never pesewas',
  `student_amount` decimal(15,2) DEFAULT NULL COMMENT 'Discounted rate for students',
  `insurance_amount` decimal(15,2) DEFAULT NULL COMMENT 'Rate for insured patients',
  `tax_rate_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_taxable` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fee_id`),
  UNIQUE KEY `uq_fee_service_code` (`service_code`),
  KEY `idx_fee_category` (`category_id`),
  KEY `idx_fee_active` (`is_active`,`effective_from`),
  KEY `fk_fee_created_by` (`created_by`),
  KEY `fk_fee_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master fee schedule — all billable service prices';

-- --------------------------------------------------------

--
-- Table structure for table `finance_audit_trail`
--

DROP TABLE IF EXISTS `finance_audit_trail`;
CREATE TABLE IF NOT EXISTS `finance_audit_trail` (
  `audit_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` int DEFAULT NULL,
  `action` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. payment.created, refund.approved, invoice.voided',
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. payments, invoices, refunds, waivers, budgets',
  `record_id` int UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL COMMENT 'Previous state snapshot',
  `new_values` json DEFAULT NULL COMMENT 'New state snapshot',
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_finaudit_user` (`actor_user_id`),
  KEY `idx_finaudit_action` (`action`),
  KEY `idx_finaudit_module` (`module`),
  KEY `idx_finaudit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Immutable financial audit trail — INSERT ONLY, no UPDATE/DELETE allowed';

-- --------------------------------------------------------

--
-- Table structure for table `finance_notifications`
--

DROP TABLE IF EXISTS `finance_notifications`;
CREATE TABLE IF NOT EXISTS `finance_notifications` (
  `notification_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `type` enum('Payment Received','Invoice Generated','Invoice Overdue','Refund Processed','Refund Request','Waiver Request','Budget Alert','Insurance Update','Reconciliation','System','Approval Required') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. invoices, payments, refunds',
  `related_record_id` int UNSIGNED DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_finnotif_recipient` (`recipient_id`),
  KEY `idx_finnotif_read` (`is_read`),
  KEY `idx_finnotif_type` (`type`),
  KEY `fk_finnotif_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Finance-specific notification system';

-- --------------------------------------------------------

--
-- Table structure for table `finance_settings`
--

DROP TABLE IF EXISTS `finance_settings`;
CREATE TABLE IF NOT EXISTS `finance_settings` (
  `setting_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `finance_staff_id` int UNSIGNED NOT NULL,
  `notif_new_payment` tinyint(1) NOT NULL DEFAULT '1',
  `notif_invoice_overdue` tinyint(1) NOT NULL DEFAULT '1',
  `notif_refund_request` tinyint(1) NOT NULL DEFAULT '1',
  `notif_waiver_request` tinyint(1) NOT NULL DEFAULT '1',
  `notif_budget_alert` tinyint(1) NOT NULL DEFAULT '1',
  `notif_insurance_update` tinyint(1) NOT NULL DEFAULT '1',
  `notif_reconciliation` tinyint(1) NOT NULL DEFAULT '1',
  `notif_system_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `preferred_channel` enum('dashboard','email','sms','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dashboard',
  `theme_preference` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `dashboard_preferences` json DEFAULT NULL COMMENT 'Widget visibility, layout prefs',
  `default_report_format` enum('PDF','CSV','XLSX') COLLATE utf8mb4_unicode_ci DEFAULT 'PDF',
  `auto_receipt_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `invoice_prefix` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'RMU-INV',
  `default_due_days` int UNSIGNED DEFAULT '30',
  `default_tax_rate` decimal(5,2) DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'GHS',
  `waiver_approval_threshold` decimal(15,2) DEFAULT '500.00',
  `refund_approval_threshold` decimal(15,2) DEFAULT '200.00',
  `max_refund_pct` int UNSIGNED DEFAULT '100',
  `overdue_alert_days` int UNSIGNED DEFAULT '7',
  `budget_alert_pct` int UNSIGNED DEFAULT '80',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uq_finsettings_staff` (`finance_staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-staff finance dashboard settings and notification preferences';

--
-- Dumping data for table `finance_settings`
--

INSERT INTO `finance_settings` (`setting_id`, `finance_staff_id`, `notif_new_payment`, `notif_invoice_overdue`, `notif_refund_request`, `notif_waiver_request`, `notif_budget_alert`, `notif_insurance_update`, `notif_reconciliation`, `notif_system_alerts`, `preferred_channel`, `theme_preference`, `language`, `dashboard_preferences`, `default_report_format`, `auto_receipt_enabled`, `invoice_prefix`, `default_due_days`, `default_tax_rate`, `currency`, `waiver_approval_threshold`, `refund_approval_threshold`, `max_refund_pct`, `overdue_alert_days`, `budget_alert_pct`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'dashboard', 'light', 'en', NULL, 'PDF', 1, 'RMU-INV', 30, 0.00, 'GHS', 500.00, 200.00, 100, 7, 80, '2026-04-09 17:36:26', '2026-04-09 17:36:26');

-- --------------------------------------------------------

--
-- Table structure for table `finance_staff`
--

DROP TABLE IF EXISTS `finance_staff`;
CREATE TABLE IF NOT EXISTS `finance_staff` (
  `finance_staff_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `staff_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_level` enum('finance_officer','finance_manager','cashier','accountant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'finance_officer',
  `department` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT 'Finance & Revenue',
  `can_process_refunds` tinyint(1) NOT NULL DEFAULT '0',
  `can_approve_waivers` tinyint(1) NOT NULL DEFAULT '0',
  `can_generate_reports` tinyint(1) NOT NULL DEFAULT '1',
  `can_manage_budgets` tinyint(1) NOT NULL DEFAULT '0',
  `max_refund_amount` decimal(15,2) DEFAULT NULL COMMENT 'NULL = no limit (manager only)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `hired_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`finance_staff_id`),
  UNIQUE KEY `uq_finance_staff_user` (`user_id`),
  UNIQUE KEY `uq_finance_staff_code` (`staff_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Finance department staff profiles and permissions';

--
-- Dumping data for table `finance_staff`
--

INSERT INTO `finance_staff` (`finance_staff_id`, `user_id`, `staff_code`, `role_level`, `department`, `can_process_refunds`, `can_approve_waivers`, `can_generate_reports`, `can_manage_budgets`, `max_refund_amount`, `is_active`, `hired_at`, `created_at`, `updated_at`, `approval_status`, `rejection_reason`, `approved_by`, `approved_at`) VALUES
(1, 35, 'FIN-DE23BD', 'finance_officer', 'Finance & Revenue', 0, 0, 1, 0, NULL, 1, NULL, '2026-04-09 14:58:29', '2026-04-09 15:06:46', 'approved', NULL, 1, '2026-04-09 15:06:46'),
(2, 317, 'FIN-5F7916', 'finance_manager', 'Finance & Revenue', 0, 0, 1, 0, NULL, 1, NULL, '2026-05-14 10:27:49', '2026-05-14 10:28:47', 'approved', NULL, 1, '2026-05-14 10:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

DROP TABLE IF EXISTS `financial_reports`;
CREATE TABLE IF NOT EXISTS `financial_reports` (
  `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_type` enum('Daily Summary','Weekly Summary','Monthly Summary','Quarterly Report','Annual Report','Revenue Breakdown','Outstanding Invoices','Payment Reconciliation','Insurance Claims','Custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `parameters` json DEFAULT NULL COMMENT 'Filter parameters used to generate',
  `summary_data` json DEFAULT NULL COMMENT 'Aggregated summary metrics',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to generated PDF/XLSX',
  `file_format` enum('PDF','CSV','XLSX') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_finreport_type` (`report_type`),
  KEY `idx_finreport_period` (`period_start`,`period_end`),
  KEY `fk_finreport_generator` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Generated financial report records and file references';

-- --------------------------------------------------------

--
-- Table structure for table `fluid_balance`
--

DROP TABLE IF EXISTS `fluid_balance`;
CREATE TABLE IF NOT EXISTS `fluid_balance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `balance_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `record_date` date NOT NULL,
  `total_intake` decimal(8,1) NOT NULL DEFAULT '0.0' COMMENT 'ml',
  `total_output` decimal(8,1) NOT NULL DEFAULT '0.0' COMMENT 'ml',
  `net_balance` decimal(8,1) NOT NULL DEFAULT '0.0' COMMENT 'intake - output, ml',
  `intake_sources` json DEFAULT NULL COMMENT '{oral, iv, ng_tube}',
  `output_sources` json DEFAULT NULL COMMENT '{urine, drain, emesis}',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `balance_id` (`balance_id`),
  KEY `nurse_id` (`nurse_id`),
  KEY `idx_fb_balance_id` (`balance_id`),
  KEY `idx_fb_patient` (`patient_id`),
  KEY `idx_fb_date` (`record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily fluid intake and output balance charts per patient';

-- --------------------------------------------------------

--
-- Table structure for table `forced_logout_queue`
--

DROP TABLE IF EXISTS `forced_logout_queue`;
CREATE TABLE IF NOT EXISTS `forced_logout_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reason` varchar(255) DEFAULT 'admin forced',
  `queued_by` int DEFAULT NULL COMMENT 'Admin ID or NULL for system',
  `queued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `executed_at` datetime DEFAULT NULL COMMENT 'NULL means not yet processed',
  `is_executed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_executed` (`is_executed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `global_login_attempts`
--

INSERT INTO `global_login_attempts` (`id`, `user_id`, `action_type`, `ip_address`, `created_at`) VALUES
(10, 11, 'login_failed', '::1', '2026-03-16 21:55:41'),
(9, 11, 'login_success', '::1', '2026-03-16 20:12:54'),
(8, 1, 'login_success', '::1', '2026-03-16 20:10:24'),
(7, 11, 'login_success', '::1', '2026-03-16 11:59:35'),
(6, 1, 'login_success', '::1', '2026-03-16 11:58:49'),
(11, 11, 'login_failed', '::1', '2026-03-16 21:56:07'),
(12, 11, 'login_success', '::1', '2026-03-16 21:56:30'),
(13, 11, 'login_success', '::1', '2026-03-16 23:09:05'),
(14, 1, 'login_success', '::1', '2026-03-17 00:09:35'),
(15, 1, 'login_success', '::1', '2026-03-17 11:46:13'),
(16, 1, 'login_success', '::1', '2026-03-17 11:51:54'),
(17, 12, 'login_success', '::1', '2026-03-17 12:01:56'),
(18, 1, 'login_success', '::1', '2026-03-17 12:02:23'),
(19, 1, 'login_success', '::1', '2026-03-17 12:29:40'),
(20, 14, 'login_success', '::1', '2026-03-17 12:31:23'),
(21, 14, 'login_success', '::1', '2026-03-17 17:25:46'),
(22, 1, 'login_success', '::1', '2026-03-17 17:31:04'),
(23, 15, 'login_success', '::1', '2026-03-17 17:32:13'),
(24, 15, 'login_success', '::1', '2026-03-17 17:59:22'),
(25, 1, 'login_failed', '::1', '2026-03-17 18:26:40'),
(26, 1, 'login_success', '::1', '2026-03-17 18:26:57'),
(27, 16, 'login_success', '::1', '2026-03-17 18:28:19'),
(28, 1, 'login_failed', '::1', '2026-03-17 22:51:39'),
(29, 1, 'login_success', '::1', '2026-03-17 22:51:57'),
(30, 17, 'login_success', '::1', '2026-03-17 22:53:09'),
(31, 1, 'login_success', '::1', '2026-03-17 23:08:52'),
(32, 18, 'login_success', '::1', '2026-03-17 23:10:35'),
(33, 19, 'login_success', '::1', '2026-03-18 01:40:14'),
(34, 1, 'login_success', '::1', '2026-03-18 02:03:56'),
(35, 20, 'login_success', '::1', '2026-03-18 02:05:29'),
(36, 1, 'login_success', '::1', '2026-03-18 02:06:53'),
(37, 1, 'login_success', '::1', '2026-03-18 02:50:01'),
(38, 21, 'login_success', '::1', '2026-03-18 02:51:46'),
(39, 1, 'login_success', '::1', '2026-03-18 04:01:39'),
(40, 22, 'login_success', '::1', '2026-03-18 04:02:33'),
(41, 1, 'login_success', '::1', '2026-03-18 05:02:20'),
(42, 23, 'login_success', '::1', '2026-03-18 05:03:36'),
(43, 22, 'login_success', '::1', '2026-03-18 10:56:19'),
(44, 21, 'login_failed', '::1', '2026-03-18 11:46:17'),
(45, 21, 'login_failed', '::1', '2026-03-18 11:46:41'),
(46, 21, 'login_success', '::1', '2026-03-18 11:47:55'),
(47, 22, 'login_success', '::1', '2026-03-18 14:29:06'),
(48, 22, 'login_success', '::1', '2026-03-18 14:35:35'),
(49, 22, 'login_success', '::1', '2026-03-18 14:36:13'),
(50, 22, 'login_success', '::1', '2026-03-18 17:11:52'),
(51, 22, 'login_success', '::1', '2026-03-18 17:18:21'),
(52, 22, 'login_success', '::1', '2026-03-18 18:05:56'),
(53, 22, 'login_success', '::1', '2026-03-19 05:12:40'),
(54, 21, 'login_success', '::1', '2026-03-19 06:20:50'),
(55, 22, 'login_success', '::1', '2026-03-19 07:22:15'),
(56, 1, 'login_success', '::1', '2026-03-20 03:07:08'),
(57, 1, 'login_success', '::1', '2026-03-20 03:10:24'),
(58, 1, 'login_success', '::1', '2026-03-20 03:15:49'),
(59, 26, 'login_success', '::1', '2026-03-20 03:16:53'),
(60, 26, 'login_success', '::1', '2026-03-20 04:42:35'),
(61, 26, 'login_success', '::1', '2026-03-20 04:54:01'),
(62, 26, 'login_failed', '::1', '2026-03-20 05:20:58'),
(63, 26, 'login_success', '::1', '2026-03-20 05:21:14'),
(64, 26, 'login_success', '::1', '2026-03-20 16:01:27'),
(65, 27, 'login_success', '::1', '2026-03-20 16:19:49'),
(66, 5, 'login_success', '::1', '2026-03-20 17:30:54'),
(67, 1, 'login_success', '::1', '2026-03-20 18:57:08'),
(68, 27, 'login_success', '::1', '2026-03-20 19:06:24'),
(69, 5, 'login_success', '::1', '2026-03-21 08:08:01'),
(70, 1, 'login_success', '::1', '2026-03-21 08:09:37'),
(71, 1, 'login_success', '::1', '2026-03-23 05:11:32'),
(72, 1, 'login_success', '::1', '2026-03-23 07:32:19'),
(73, 1, 'login_success', '::1', '2026-03-23 07:41:40'),
(74, 1, 'login_success', '::1', '2026-03-23 07:48:15'),
(75, 28, 'login_success', '::1', '2026-03-23 08:08:48'),
(76, 1, 'login_success', '::1', '2026-03-23 09:26:51'),
(77, 26, 'login_success', '::1', '2026-03-24 21:22:08'),
(78, 28, 'login_success', '::1', '2026-03-25 05:40:47'),
(79, 1, 'login_success', '::1', '2026-03-25 12:31:40'),
(80, 1, 'login_success', '::1', '2026-03-25 16:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `health_messages`
--

DROP TABLE IF EXISTS `health_messages`;
CREATE TABLE IF NOT EXISTS `health_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_text` text NOT NULL,
  `message_category` enum('wellness','safety','reminder','motivational','health tip') DEFAULT 'wellness',
  `target_role` varchar(50) DEFAULT NULL COMMENT 'Nullable means all roles',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `health_messages`
--

INSERT INTO `health_messages` (`id`, `message_text`, `message_category`, `target_role`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Wash your hands regularly to prevent the spread of infections.', 'safety', NULL, 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(2, 'Take a moment to stretch and hydrate during your shift.', 'wellness', 'staff', 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(3, 'Remember to log out securely when leaving your workstation.', 'reminder', NULL, 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(4, 'Your dedication to patient care makes a huge difference. Have a great day!', 'motivational', 'doctor', 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(5, 'Don\'t forget to complete your clinical notes before leaving.', 'reminder', 'doctor', 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(6, 'Ensure all patient data is securely locked away.', 'safety', 'nurse', 1, NULL, '2026-03-26 11:49:26', '2026-03-26 11:49:26'),
(7, 'Review your final patient charts before departing.', 'wellness', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(8, 'Ensure your prescription pad is secured.', 'health tip', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(9, 'Take a moment to decompress after your consulting shift.', 'motivational', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(10, 'Verify all urgent lab requests have been reviewed.', 'health tip', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(11, 'Your dedication saves lives. Have a restful evening.', 'health tip', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(12, 'Check your schedule for tomorrow\'s early appointments.', 'health tip', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(13, 'Remember to properly log out of the EMR system.', 'reminder', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(14, 'Stay hydrated! Doctors need care too.', 'safety', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(15, 'Ensure all patient handoffs are communicated to the night shift.', 'safety', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(16, 'Rest well. The clinic relies on your sharp mind tomorrow.', 'health tip', 'doctor', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(17, 'Ensure the medication cart is locked and secured.', 'wellness', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(18, 'Double-check patient vital sign logs before shift end.', 'safety', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(19, 'Thank you for your tireless care today. Rest well.', 'health tip', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(20, 'Communicate all pending IV drip changes to the next shift.', 'motivational', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(21, 'Have you submitted all incident reports?', 'safety', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(22, 'Wipe down your vitals station equipment.', 'reminder', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(23, 'Nurses are the heart of healthcare. Have a great day!', 'health tip', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(24, 'Verify all patient bedside alarms are active.', 'safety', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(25, 'Rest your feet. You\'ve earned a good break.', 'safety', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(26, 'Remember to wash your hands as you exit the ward.', 'wellness', 'nurse', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(27, 'Remember to take your prescribed medications on time.', 'reminder', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(28, 'Drink at least 8 glasses of water today.', 'safety', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(29, 'Your health is your greatest wealth.', 'reminder', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(30, 'If you feel worse, please contact the clinic immediately.', 'safety', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(31, 'A good night\'s sleep accelerates recovery.', 'wellness', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(32, 'Keep your follow-up appointment dates in mind.', 'health tip', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(33, 'Eat a balanced meal to support your immune system.', 'motivational', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(34, 'Call the emergency hotline if you experience shortness of breath.', 'motivational', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(35, 'Physical rest is just as important as medical treatment.', 'motivational', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(36, 'Thank you for choosing RMU Medical for your care.', 'motivational', 'patient', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(37, 'Ensure the controlled substance cabinet is double-locked.', 'motivational', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(38, 'Verify all pending prescription labels have been printed.', 'safety', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(39, 'Thank you for keeping our patients safely medicated.', 'motivational', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(40, 'Check refrigerator temperatures before leaving.', 'safety', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(41, 'Ensure the pharmacy counter is cleared of loose pills.', 'motivational', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(42, 'Cross-check today\'s narcotic dispensary logs.', 'reminder', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(43, 'Rest well! Accuracy requires a well-rested mind.', 'health tip', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(44, 'Please secure the main pharmacy vault.', 'reminder', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(45, 'Ensure no pending refill requests were missed.', 'health tip', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(46, 'Have a safe journey home.', 'wellness', 'pharmacist', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(47, 'Ensure all incubators are properly sealed.', 'motivational', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(48, 'Verify that the centrifuge is clean and powered down.', 'motivational', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(49, 'Thank you for providing the diagnostic clarity we need.', 'motivational', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(50, 'Check that all sensitive reagents are refrigerated.', 'motivational', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(51, 'Sanitize your lab bench before departing.', 'wellness', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(52, 'Ensure all hazardous waste is properly binned.', 'wellness', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(53, 'Double-check that all pending urgent results are broadcasted.', 'health tip', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(54, 'Secure the hematology analyzer for the night.', 'wellness', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(55, 'Rest your eyes! Microscope work is demanding.', 'reminder', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(56, 'Have a great evening.', 'reminder', 'lab_technician', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(57, 'Ensure all administrative files are locked in the cabinets.', 'motivational', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(58, 'Verify that tomorrow\'s patient rosters are printed.', 'safety', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(59, 'Thank you for keeping the clinic running smoothly.', 'reminder', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(60, 'Check that all waiting area systems are powered down.', 'reminder', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(61, 'Remember to submit your daily cash reconciliation.', 'motivational', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(62, 'Ensure the main entrance is secured if you are the last to leave.', 'safety', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(63, 'Rest well! Tomorrow is another busy day.', 'reminder', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(64, 'Verify that all incoming emails have been triaged.', 'safety', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(65, 'Ensure the reception desk is cleared and tidy.', 'safety', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(66, 'Have a wonderful and restful day off.', 'motivational', 'staff', 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(67, 'Thank you for using the RMU Medical Sickbay System.', 'motivational', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(68, 'Security is everyone\'s responsibility.', 'wellness', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(69, 'Have a wonderful and safe day.', 'wellness', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(70, 'Your session has been securely terminated.', 'reminder', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(71, 'Always remember to lock your screen when stepping away.', 'wellness', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(72, 'Stay positive, work hard, make it happen.', 'wellness', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(73, 'The system will now securely purge your local data.', 'health tip', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(74, 'Disconnecting securely from the hospital network.', 'reminder', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(75, 'Health is a state of body. Wellness is a state of being.', 'wellness', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(76, 'Goodbye! See you next time.', 'safety', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30'),
(77, 'Your health is your greatest wealth. Keep up with your appointments.', 'motivational', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(78, 'Remember to complete your prescribed medication course even if you feel better.', 'reminder', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(79, 'Hydration is key to recovery. Drink plenty of water today.', 'wellness', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(80, 'Regular check-ups help prevent major health issues. Stay proactive.', 'health tip', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(81, 'Sleep is when your body heals. Aim for 7-8 hours tonight.', 'wellness', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(82, 'Monitor your symptoms and don\'t hesitate to reach out if they worsen.', 'safety', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(83, 'A balanced diet fuels your immune system. Eat your greens!', 'health tip', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(84, 'Take a deep breath and relax. Stress management is vital for healing.', 'wellness', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(85, 'Follow your doctor\'s advice strictly for the fastest recovery.', 'motivational', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(86, 'Thank you for trusting RMU Medical System with your care.', 'motivational', 'patient', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(87, 'Thank you for your service. Rest well — your patients need you at your best.', 'motivational', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(88, 'A well-rested doctor makes the best clinical decisions. Take a break.', 'wellness', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(89, 'Review patient histories carefully before prescribing new medications.', 'safety', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(90, 'Compassion cures as much as medicine does. Great work today.', 'motivational', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(91, 'Ensure all case notes are thoroughly documented before signing off.', 'reminder', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(92, 'Your diagnostic skills save lives every single day.', 'motivational', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(93, 'Stay updated with the latest medical protocols.', 'health tip', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(94, 'Take 5 minutes to stretch between long patient consultations.', 'wellness', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(95, 'Double-check all conflicting medication alerts in the system.', 'safety', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(96, 'The medical field is demanding. Prioritize your mental health.', 'wellness', 'doctor', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(97, 'You make a difference every single day. Take care of yourself too.', 'motivational', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(98, 'Always verify patient identity before administering any treatment.', 'safety', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(99, 'Hydrate! Nursing shifts are long and your body needs water.', 'wellness', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(100, 'Ensure all vitals are recorded immediately after taking them.', 'reminder', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(101, 'Your care and empathy are the heart of this hospital.', 'motivational', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(102, 'Lift patients with proper ergonomics to avoid back injuries.', 'safety', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(103, 'Take your scheduled breaks. You deserve them.', 'wellness', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(104, 'Clear communication during shift handovers prevents critical errors.', 'safety', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(105, 'Wash hands thoroughly between every patient interaction.', 'health tip', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(106, 'You are an essential pillar of patient recovery. Thank you.', 'motivational', 'nurse', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(107, 'Accurate dispensing saves lives. Well done today.', 'motivational', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(108, 'Double-check all dosage instructions before handing over medications.', 'safety', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(109, 'Ensure all dangerous drugs are securely locked before leaving.', 'safety', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(110, 'Verify all ambiguous prescriptions directly with the prescribing doctor.', 'reminder', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(111, 'Keep the dispensary organized to prevent dispensing errors.', 'safety', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(112, 'Patient education on side effects is just as critical as the medicine.', 'health tip', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(113, 'Rest your eyes after long periods of reading labels.', 'wellness', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(114, 'Your attention to detail prevents adverse drug interactions.', 'motivational', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(115, 'Always check expiration dates when restocking shelves.', 'reminder', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(116, 'Thank you for ensuring our patients get the right treatments safely.', 'motivational', 'pharmacist', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(117, 'Precision in the lab is precision in patient care. Great work.', 'motivational', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(118, 'Always wear appropriate PPE when handling biological samples.', 'safety', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(119, 'Ensure all lab equipment is properly calibrated before your shift ends.', 'reminder', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(120, 'Accurate results start with accurately labeled samples.', 'safety', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(121, 'Your work behind the scenes is vital to accurate diagnoses.', 'motivational', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(122, 'Decontaminate workspaces thoroughly before leaving the lab.', 'safety', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(123, 'Avoid eye strain by following the 20-20-20 rule during microscope work.', 'wellness', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(124, 'Properly store all reagents according to temperature requirements.', 'reminder', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(125, 'Never rush a test. Quality always supersedes speed in the lab.', 'health tip', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(126, 'Your dedication to accuracy protects our patients. Thank you.', 'motivational', 'lab_technician', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(127, 'Behind every great hospital system is a great administrator. Thank you.', 'motivational', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(128, 'Remember to step away from the screen to rest your eyes.', 'wellness', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(129, 'Secure all sensitive data screens before leaving your desk.', 'safety', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(130, 'A well-managed hospital saves lives. Keep up the great work.', 'motivational', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(131, 'Review all pending approvals in the queue before end of day.', 'reminder', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(132, 'Data privacy is paramount. Ensure strict access controls are maintained.', 'safety', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(133, 'Take a walk. Sitting all day is harmful to your long-term health.', 'wellness', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(134, 'Your organizational skills keep the entire facility running smoothly.', 'motivational', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(135, 'Check system backup logs to ensure data integrity.', 'reminder', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(136, 'Thank you for maintaining the foundation of our healthcare delivery.', 'motivational', 'admin', 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(137, 'Remember to stay hydrated — drink at least 8 glasses of water daily.', 'wellness', NULL, 1, 1, '2026-04-09 09:30:51', '2026-04-09 09:30:51'),
(138, 'Your attention will be required in 30 minutes by Dr. Eli', 'reminder', 'nurse', 1, 1, '2026-04-09 16:18:39', '2026-04-09 16:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_settings`
--

DROP TABLE IF EXISTS `hospital_settings`;
CREATE TABLE IF NOT EXISTS `hospital_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospital_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_numbers` json DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accreditation_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facility_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contacts` json DEFAULT NULL,
  `operating_hours` json DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `maintenance_mode` tinyint(1) DEFAULT '0',
  `maintenance_message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_claims`
--

DROP TABLE IF EXISTS `insurance_claims`;
CREATE TABLE IF NOT EXISTS `insurance_claims` (
  `claim_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `claim_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RMU-CLM-YYYYMMDD-NNNN',
  `invoice_id` int UNSIGNED NOT NULL,
  `patient_id` int NOT NULL,
  `insurance_provider` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `policy_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `claim_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `patient_copay` decimal(15,2) DEFAULT NULL,
  `status` enum('Draft','Submitted','Under Review','Approved','Partially Approved','Rejected','Paid','Appealed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `submission_date` date DEFAULT NULL,
  `response_date` date DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `supporting_docs` json DEFAULT NULL COMMENT 'Array of document file paths',
  `claims_officer` int DEFAULT NULL,
  `insurer_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  UNIQUE KEY `uq_claim_number` (`claim_number`),
  KEY `idx_claim_invoice` (`invoice_id`),
  KEY `idx_claim_patient` (`patient_id`),
  KEY `idx_claim_status` (`status`),
  KEY `fk_claim_officer` (`claims_officer`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Insurance claim submissions and tracking';

--
-- Dumping data for table `insurance_claims`
--

INSERT INTO `insurance_claims` (`claim_id`, `claim_number`, `invoice_id`, `patient_id`, `insurance_provider`, `policy_number`, `claim_amount`, `approved_amount`, `patient_copay`, `status`, `submission_date`, `response_date`, `rejection_reason`, `supporting_docs`, `claims_officer`, `insurer_reference`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CLM-001', 7, 101, 'NHIS', 'NHIS-101102', 350.00, NULL, NULL, 'Submitted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(2, 'CLM-002', 10, 102, 'Star Assurance', 'SA-2023-999', 1200.00, NULL, NULL, 'Paid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(3, 'CLM-003', 16, 103, 'Glico Healthcare', 'GH-Alice-01', 75.00, NULL, NULL, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(4, 'CLM-004', 17, 104, 'NHIS', 'NHIS-888777', 100.00, NULL, NULL, 'Under Review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(5, 'CLM-005', 19, 105, 'Nationwide Medical', 'NW-105', 150.00, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(6, 'CLM-006', 21, 106, 'NHIS', 'NHIS-555444', 50.00, NULL, NULL, 'Submitted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(7, 'CLM-007', 28, 107, 'Star Assurance', 'SA-2023-888', 210.00, NULL, NULL, 'Paid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(8, 'CLM-008', 4, 108, 'Glico Healthcare', 'GH-Fiona-02', 890.00, NULL, NULL, 'Paid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(9, 'CLM-009', 12, 109, 'NHIS', 'NHIS-109', 120.00, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22'),
(10, 'CLM-010', 26, 110, 'Nationwide Medical', 'NW-110', 300.00, NULL, NULL, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:22', '2026-04-14 01:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_line_items`
--

DROP TABLE IF EXISTS `invoice_line_items`;
CREATE TABLE IF NOT EXISTS `invoice_line_items` (
  `line_item_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int UNSIGNED NOT NULL,
  `fee_id` int UNSIGNED DEFAULT NULL COMMENT 'FK to fee_schedule if from catalog',
  `service_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. appointment, prescription, lab_test, bed_charge',
  `reference_id` int DEFAULT NULL COMMENT 'PK of the source record',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`line_item_id`),
  KEY `idx_lineitem_invoice` (`invoice_id`),
  KEY `idx_lineitem_fee` (`fee_id`),
  KEY `fk_lineitem_cat` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual line items on an invoice';

--
-- Dumping data for table `invoice_line_items`
--

INSERT INTO `invoice_line_items` (`line_item_id`, `invoice_id`, `fee_id`, `service_description`, `service_code`, `category_id`, `quantity`, `unit_price`, `discount_pct`, `discount_amount`, `tax_amount`, `line_total`, `reference_type`, `reference_id`, `notes`, `created_at`) VALUES
(1, 11, NULL, 'Medication', NULL, NULL, 1, 203.00, 0.00, 0.00, 5.08, 208.08, NULL, NULL, NULL, '2026-01-23 23:37:24'),
(2, 11, NULL, 'Medication', NULL, NULL, 3, 375.00, 0.00, 0.00, 28.13, 1153.13, NULL, NULL, NULL, '2026-02-17 16:33:17'),
(3, 12, NULL, 'Ward Charges', NULL, NULL, 1, 345.00, 0.00, 0.00, 8.63, 353.63, NULL, NULL, NULL, '2026-01-19 06:30:36'),
(4, 12, NULL, 'Procedure Fee', NULL, NULL, 2, 136.00, 0.00, 0.00, 6.80, 278.80, NULL, NULL, NULL, '2026-03-05 18:08:43'),
(5, 13, NULL, 'Ward Charges', NULL, NULL, 2, 146.00, 0.00, 0.00, 7.30, 299.30, NULL, NULL, NULL, '2026-03-23 06:01:23'),
(6, 13, NULL, 'Nursing Care', NULL, NULL, 3, 476.00, 0.00, 0.00, 35.70, 1463.70, NULL, NULL, NULL, '2026-03-11 10:13:36'),
(7, 14, NULL, 'Laboratory Test', NULL, NULL, 1, 332.00, 0.00, 0.00, 8.30, 340.30, NULL, NULL, NULL, '2026-02-13 20:26:44'),
(8, 14, NULL, 'Consultation Fee', NULL, NULL, 2, 207.00, 0.00, 0.00, 10.35, 424.35, NULL, NULL, NULL, '2026-02-04 10:52:40'),
(9, 15, NULL, 'Consultation Fee', NULL, NULL, 1, 483.00, 0.00, 0.00, 12.08, 495.08, NULL, NULL, NULL, '2026-01-29 10:36:11'),
(10, 15, NULL, 'Nursing Care', NULL, NULL, 3, 500.00, 0.00, 0.00, 37.50, 1537.50, NULL, NULL, NULL, '2026-02-28 18:43:17'),
(11, 16, NULL, 'Laboratory Test', NULL, NULL, 3, 221.00, 0.00, 0.00, 16.58, 679.58, NULL, NULL, NULL, '2026-02-27 21:18:11'),
(12, 16, NULL, 'Ward Charges', NULL, NULL, 2, 94.00, 0.00, 0.00, 4.70, 192.70, NULL, NULL, NULL, '2026-02-21 13:45:11'),
(13, 17, NULL, 'Procedure Fee', NULL, NULL, 1, 445.00, 0.00, 0.00, 11.13, 456.13, NULL, NULL, NULL, '2026-01-29 23:19:54'),
(14, 17, NULL, 'Laboratory Test', NULL, NULL, 1, 352.00, 0.00, 0.00, 8.80, 360.80, NULL, NULL, NULL, '2026-01-15 12:31:42'),
(15, 18, NULL, 'Procedure Fee', NULL, NULL, 1, 126.00, 0.00, 0.00, 3.15, 129.15, NULL, NULL, NULL, '2026-02-26 02:20:09'),
(16, 18, NULL, 'Medication', NULL, NULL, 3, 193.00, 0.00, 0.00, 14.48, 593.48, NULL, NULL, NULL, '2026-04-08 04:52:38'),
(17, 21, NULL, 'Procedure Fee', NULL, NULL, 3, 196.00, 0.00, 0.00, 14.70, 602.70, NULL, NULL, NULL, '2026-02-05 20:13:41'),
(18, 21, NULL, 'Nursing Care', NULL, NULL, 2, 169.00, 0.00, 0.00, 8.45, 346.45, NULL, NULL, NULL, '2026-03-27 22:42:35'),
(19, 22, NULL, 'Medication', NULL, NULL, 3, 416.00, 0.00, 0.00, 31.20, 1279.20, NULL, NULL, NULL, '2026-01-18 14:46:41'),
(20, 22, NULL, 'Procedure Fee', NULL, NULL, 1, 220.00, 0.00, 0.00, 5.50, 225.50, NULL, NULL, NULL, '2026-02-10 10:06:11'),
(21, 23, NULL, 'Consultation Fee', NULL, NULL, 3, 196.00, 0.00, 0.00, 14.70, 602.70, NULL, NULL, NULL, '2026-03-13 10:35:10'),
(22, 23, NULL, 'Procedure Fee', NULL, NULL, 3, 430.00, 0.00, 0.00, 32.25, 1322.25, NULL, NULL, NULL, '2026-01-30 11:58:55'),
(23, 24, NULL, 'Nursing Care', NULL, NULL, 3, 78.00, 0.00, 0.00, 5.85, 239.85, NULL, NULL, NULL, '2026-03-28 09:18:37'),
(24, 24, NULL, 'Laboratory Test', NULL, NULL, 3, 167.00, 0.00, 0.00, 12.53, 513.53, NULL, NULL, NULL, '2026-04-07 15:50:11'),
(25, 25, NULL, 'Medication', NULL, NULL, 2, 103.00, 0.00, 0.00, 5.15, 211.15, NULL, NULL, NULL, '2026-01-18 11:24:11'),
(26, 25, NULL, 'Procedure Fee', NULL, NULL, 2, 264.00, 0.00, 0.00, 13.20, 541.20, NULL, NULL, NULL, '2026-03-21 00:27:47'),
(27, 26, NULL, 'Consultation Fee', NULL, NULL, 2, 195.00, 0.00, 0.00, 9.75, 399.75, NULL, NULL, NULL, '2026-01-25 21:24:39'),
(28, 26, NULL, 'Laboratory Test', NULL, NULL, 2, 276.00, 0.00, 0.00, 13.80, 565.80, NULL, NULL, NULL, '2026-01-25 12:24:23'),
(29, 27, NULL, 'Nursing Care', NULL, NULL, 1, 347.00, 0.00, 0.00, 8.68, 355.68, NULL, NULL, NULL, '2026-03-23 08:51:50'),
(30, 27, NULL, 'Consultation Fee', NULL, NULL, 2, 80.00, 0.00, 0.00, 4.00, 164.00, NULL, NULL, NULL, '2026-02-25 12:42:57'),
(31, 28, NULL, 'Consultation Fee', NULL, NULL, 3, 366.00, 0.00, 0.00, 27.45, 1125.45, NULL, NULL, NULL, '2026-02-14 22:35:29'),
(32, 28, NULL, 'Nursing Care', NULL, NULL, 1, 216.00, 0.00, 0.00, 5.40, 221.40, NULL, NULL, NULL, '2026-03-01 01:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `ip_whitelist`
--

DROP TABLE IF EXISTS `ip_whitelist`;
CREATE TABLE IF NOT EXISTS `ip_whitelist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `isolation_records`
--

DROP TABLE IF EXISTS `isolation_records`;
CREATE TABLE IF NOT EXISTS `isolation_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `isolation_type` enum('Contact','Droplet','Airborne','Protective','Reverse') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `precautions` json DEFAULT NULL COMMENT 'Array of precaution strings',
  `doctor_ordered_by` int DEFAULT NULL COMMENT 'FK → doctors.id',
  `status` enum('Active','Lifted','Pending Review') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`),
  KEY `nurse_id` (`nurse_id`),
  KEY `doctor_ordered_by` (`doctor_ordered_by`),
  KEY `idx_ir_record_id` (`record_id`),
  KEY `idx_ir_patient` (`patient_id`),
  KEY `idx_ir_status` (`status`),
  KEY `idx_ir_type` (`isolation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient isolation orders with types and precautions';

-- --------------------------------------------------------

--
-- Table structure for table `iv_fluid_records`
--

DROP TABLE IF EXISTS `iv_fluid_records`;
CREATE TABLE IF NOT EXISTS `iv_fluid_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `fluid_type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Normal Saline, D5W, RL, etc.',
  `volume_ordered` decimal(7,1) NOT NULL COMMENT 'ml',
  `volume_infused` decimal(7,1) NOT NULL DEFAULT '0.0' COMMENT 'ml',
  `infusion_rate` decimal(7,1) DEFAULT NULL COMMENT 'ml/hr',
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('Ordered','Running','Completed','Paused','Stopped') COLLATE utf8mb4_unicode_ci DEFAULT 'Ordered',
  `alert_sent` tinyint(1) NOT NULL DEFAULT '0',
  `site` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IV insertion site',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`),
  KEY `idx_ivf_record_id` (`record_id`),
  KEY `idx_ivf_patient` (`patient_id`),
  KEY `idx_ivf_nurse` (`nurse_id`),
  KEY `idx_ivf_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IV fluid orders, infusion tracking, and monitoring';

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_dietary_flags`
--

DROP TABLE IF EXISTS `kitchen_dietary_flags`;
CREATE TABLE IF NOT EXISTS `kitchen_dietary_flags` (
  `flag_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL COMMENT 'kitchen staff who flagged',
  `patient_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_type` enum('allergy','dietary_restriction','special_requirement','ingredient_shortage','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('flagged','acknowledged','resolved','escalated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flagged',
  `acknowledged_by` int DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `flagged_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`flag_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`),
  KEY `idx_flagged_at` (`flagged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_tasks`
--

DROP TABLE IF EXISTS `kitchen_tasks`;
CREATE TABLE IF NOT EXISTS `kitchen_tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `patient_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `ordered_by` int DEFAULT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_department` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bed_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dietary_requirements` json DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `priority` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Routine',
  `preparation_status` enum('pending','in preparation','ready','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `delivery_status` enum('pending','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `scheduled_time` time DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kitchen_tasks`
--

INSERT INTO `kitchen_tasks` (`task_id`, `patient_id`, `patient_name`, `assigned_to`, `ordered_by`, `meal_type`, `ward_department`, `bed_number`, `dietary_requirements`, `quantity`, `priority`, `preparation_status`, `delivery_status`, `scheduled_time`, `prepared_at`, `delivered_at`, `notes`, `created_at`) VALUES
(1, 101, NULL, 15, NULL, 'breakfast', 'Ward A', NULL, '[\"No Peanuts\"]', 1, 'Routine', 'delivered', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(2, 102, NULL, 15, NULL, 'lunch', 'Ward A', NULL, '[\"Soft Diet\"]', 1, 'Routine', 'ready', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(3, 104, NULL, 15, NULL, 'dinner', 'Ward B', NULL, '[\"Low Sodium\"]', 1, 'Routine', 'pending', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(4, 107, NULL, 15, NULL, 'breakfast', 'Ward A', NULL, '[\"Lactose Free\"]', 1, 'Routine', 'delivered', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(5, 108, NULL, 15, NULL, 'lunch', 'Emergency', NULL, '[\"Clear Fluids Only\"]', 1, 'Routine', 'delivered', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(6, 109, NULL, 15, NULL, 'snack', 'OPD', NULL, '[\"Standard\"]', 1, 'Routine', 'pending', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(7, 110, NULL, 15, NULL, 'breakfast', 'Ward B', NULL, '[\"Standard\"]', 1, 'Routine', 'delivered', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(8, 103, NULL, 15, NULL, 'lunch', 'Ward A', NULL, '[\"High Protein\"]', 1, 'Routine', 'ready', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(9, 105, NULL, 15, NULL, 'dinner', 'Ward B', NULL, '[\"Standard\"]', 1, 'Routine', 'pending', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52'),
(10, 106, NULL, 15, NULL, 'breakfast', 'Isolation', NULL, '[\"Standard\"]', 1, 'Routine', 'ready', 'pending', NULL, NULL, NULL, NULL, '2026-04-14 01:02:52');

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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_audit_trail`
--

INSERT INTO `lab_audit_trail` (`id`, `technician_id`, `user_id`, `action_type`, `module_affected`, `record_id`, `old_value`, `new_value`, `ip_address`, `device_info`, `created_at`) VALUES
(1, 1, NULL, 'login_success', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-18 11:47:55'),
(2, 1, NULL, 'login_success', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-19 06:20:51'),
(3, 2, 2, 'Result Entry', 'Lab Results', NULL, NULL, 'CBC result entered for patient 5 (ORD-2001). Hb: 8.2 g/dL.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(4, 2, 2, 'Result Released', 'Lab Results', NULL, 'Draft', 'Released', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(5, 2, 2, 'Critical Alert', 'Lab Results', NULL, NULL, 'CRITICAL FBG 18.4 mmol/L ÔÇö Dr. Joyce Eli notified via message.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(6, 2, 2, 'Sample Received', 'Specimen Management', NULL, NULL, 'Sample SAMP-2004 received for AKI STAT order ORD-2004.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(7, 2, 2, 'QC Run', 'Quality Control', NULL, NULL, 'Daily internal QC for CBC analyzer (Sysmex XN-550). Level 1 PASS.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(8, 2, 2, 'Reagent Update', 'Reagent Inventory', NULL, '120', '96', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(9, 2, 2, 'Order Accepted', 'Lab Orders', NULL, 'Pending', 'Accepted', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(10, 2, 2, 'Equipment Update', 'Equipment Fleet', NULL, 'Operational', 'Maintenance', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(11, 2, 2, 'Result Amended', 'Lab Results', NULL, 'Hb: 8.0 g/dL', 'Hb: 8.2 g/dL ÔÇö Transcription error corrected after double-check.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(12, 2, 2, 'Session Login', 'Authentication', NULL, NULL, 'Jefferson Forson logged in to Lab Dashboard. IP: 192.168.1.10.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:55:34'),
(13, 2, 2, 'Result Entry', 'Lab Results', NULL, NULL, 'CBC result entered for patient 5 (ORD-2001). Hb: 8.2 g/dL.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(14, 2, 2, 'Result Released', 'Lab Results', NULL, 'Draft', 'Released', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(15, 2, 2, 'Critical Alert', 'Lab Results', NULL, NULL, 'CRITICAL FBG 18.4 mmol/L ÔÇö Dr. Joyce Eli notified via message.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(16, 2, 2, 'Sample Received', 'Specimen Management', NULL, NULL, 'Sample SAMP-2004 received for AKI STAT order ORD-2004.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(17, 2, 2, 'QC Run', 'Quality Control', NULL, NULL, 'Daily internal QC for CBC analyzer (Sysmex XN-550). Level 1 PASS.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(18, 2, 2, 'Reagent Update', 'Reagent Inventory', NULL, '120', '96', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(19, 2, 2, 'Order Accepted', 'Lab Orders', NULL, 'Pending', 'Accepted', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(20, 2, 2, 'Equipment Update', 'Equipment Fleet', NULL, 'Operational', 'Maintenance', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(21, 2, 2, 'Result Amended', 'Lab Results', NULL, 'Hb: 8.0 g/dL', 'Hb: 8.2 g/dL ÔÇö Transcription error corrected after double-check.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46'),
(22, 2, 2, 'Session Login', 'Authentication', NULL, NULL, 'Jefferson Forson logged in to Lab Dashboard. IP: 192.168.1.10.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124', '2026-04-19 22:57:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_equipment`
--

INSERT INTO `lab_equipment` (`id`, `name`, `model`, `serial_number`, `manufacturer`, `category`, `department`, `location`, `purchase_date`, `warranty_expiry`, `status`, `last_calibration_date`, `next_calibration_date`, `last_maintenance_date`, `next_maintenance_date`, `assigned_technician_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Sysmex XN-550 Auto-Analyzer', 'XN-550', 'SYS-2024-001', 'Sysmex Corporation', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 1', '2023-01-15', '2026-01-15', 'Operational', '2026-03-01', '2026-09-01', '2025-12-15', '2026-06-15', 2, 'Primary CBC analyzer. Daily QC passing.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(2, 'Roche Cobas C111 Chemistry Analyzer', 'Cobas C111', 'RCH-2023-045', 'Roche Diagnostics', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 2', '2022-06-10', '2025-06-10', 'Operational', '2026-02-20', '2026-08-20', '2026-01-10', '2026-07-10', 2, 'Used for LFT, RFT, glucose, lipid profile.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(3, 'bio-Merieux VIDAS Immunoanalyzer', 'VIDAS 3', 'BIO-2024-112', 'bioM├®rieux', 'Immunology', 'Laboratory', 'Sector C ÔÇô Bench 1', '2024-02-01', '2027-02-01', 'Operational', '2026-01-15', '2026-07-15', '2025-11-20', '2026-05-20', 2, 'ELISA-based serology tests.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(4, 'Mindray BC-5380 Hematology Sys', 'BC-5380', 'MIN-2022-033', 'Mindray', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 3', '2022-03-15', '2025-03-15', 'Calibration Due', '2025-10-10', '2026-04-10', '2025-09-01', '2026-03-01', 2, 'Backup CBC machine. Calibration scheduled.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(5, 'Thermo Scientific Centrifuge', 'Sorvall ST8', 'THR-2021-078', 'Thermo Fisher', 'Centrifuge', 'Laboratory', 'Sector B ÔÇô Bench 4', '2021-07-20', '2024-07-20', 'Operational', '2026-03-20', '2026-09-20', '2026-02-01', '2026-08-01', 2, 'Used for serum separation.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(6, 'Beckman Coulter AU480 Chemistry', 'AU480', 'BCK-2023-091', 'Beckman Coulter', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 1', '2023-09-10', '2026-09-10', 'Operational', '2026-04-01', '2026-10-01', '2026-03-05', '2026-09-05', 2, 'High-throughput biochemistry workstation.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(7, 'Bio-Rad iQ5 PCR Cycler', 'iQ5', 'BRD-2024-055', 'Bio-Rad Laboratories', 'Molecular', 'Laboratory', 'Sector D ÔÇô PCR Room', '2024-05-01', '2027-05-01', 'Operational', '2026-04-10', '2026-10-10', '2026-03-10', '2026-09-10', 2, 'Used for DNA amplification.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(8, 'Olympus CX43 Microscope', 'CX43', 'OLY-2020-018', 'Olympus Corporation', 'Microscopy', 'Laboratory', 'Sector A ÔÇô Bench 2', '2020-11-12', '2023-11-12', 'Maintenance', '2025-08-01', '2026-02-01', '2025-12-01', '2026-06-01', 2, 'Needs binocular head replacement.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(9, 'Siemens Clinitek Status+ UA Analyzer', 'Clinitek Status+', 'SIE-2023-202', 'Siemens Healthineers', 'Urinalysis', 'Laboratory', 'Sector A ÔÇô Bench 4', '2023-04-22', '2026-04-22', 'Operational', '2026-03-15', '2026-09-15', '2026-01-20', '2026-07-20', 2, 'Automated urinalysis strips reader.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(10, 'BioSafety Cabinet Class II', 'MSC-Advantage', 'THR-2022-067', 'Thermo Fisher', 'Safety Cabinet', 'Laboratory', 'Sector D ÔÇô Micro', '2022-08-30', '2025-08-30', 'Operational', '2026-02-28', '2026-08-28', '2025-10-15', '2026-04-15', 2, 'Microbiological work cabinet. Filters replaced Jan 2026.', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(11, 'Sysmex XN-550 Auto-Analyzer', 'XN-550', 'SYS-2024-001', 'Sysmex Corporation', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 1', '2023-01-15', '2026-01-15', 'Operational', '2026-03-01', '2026-09-01', '2025-12-15', '2026-06-15', 2, 'Primary CBC analyzer. Daily QC passing.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(12, 'Roche Cobas C111 Chemistry Analyzer', 'Cobas C111', 'RCH-2023-045', 'Roche Diagnostics', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 2', '2022-06-10', '2025-06-10', 'Operational', '2026-02-20', '2026-08-20', '2026-01-10', '2026-07-10', 2, 'Used for LFT, RFT, glucose, lipid profile.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(13, 'bio-Merieux VIDAS Immunoanalyzer', 'VIDAS 3', 'BIO-2024-112', 'bioM├®rieux', 'Immunology', 'Laboratory', 'Sector C ÔÇô Bench 1', '2024-02-01', '2027-02-01', 'Operational', '2026-01-15', '2026-07-15', '2025-11-20', '2026-05-20', 2, 'ELISA-based serology tests.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(14, 'Mindray BC-5380 Hematology Sys', 'BC-5380', 'MIN-2022-033', 'Mindray', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 3', '2022-03-15', '2025-03-15', 'Calibration Due', '2025-10-10', '2026-04-10', '2025-09-01', '2026-03-01', 2, 'Backup CBC machine. Calibration scheduled.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(15, 'Thermo Scientific Centrifuge', 'Sorvall ST8', 'THR-2021-078', 'Thermo Fisher', 'Centrifuge', 'Laboratory', 'Sector B ÔÇô Bench 4', '2021-07-20', '2024-07-20', 'Operational', '2026-03-20', '2026-09-20', '2026-02-01', '2026-08-01', 2, 'Used for serum separation.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(16, 'Beckman Coulter AU480 Chemistry', 'AU480', 'BCK-2023-091', 'Beckman Coulter', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 1', '2023-09-10', '2026-09-10', 'Operational', '2026-04-01', '2026-10-01', '2026-03-05', '2026-09-05', 2, 'High-throughput biochemistry workstation.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(17, 'Bio-Rad iQ5 PCR Cycler', 'iQ5', 'BRD-2024-055', 'Bio-Rad Laboratories', 'Molecular', 'Laboratory', 'Sector D ÔÇô PCR Room', '2024-05-01', '2027-05-01', 'Operational', '2026-04-10', '2026-10-10', '2026-03-10', '2026-09-10', 2, 'Used for DNA amplification.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(18, 'Olympus CX43 Microscope', 'CX43', 'OLY-2020-018', 'Olympus Corporation', 'Microscopy', 'Laboratory', 'Sector A ÔÇô Bench 2', '2020-11-12', '2023-11-12', 'Maintenance', '2025-08-01', '2026-02-01', '2025-12-01', '2026-06-01', 2, 'Needs binocular head replacement.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(19, 'Siemens Clinitek Status+ UA Analyzer', 'Clinitek Status+', 'SIE-2023-202', 'Siemens Healthineers', 'Urinalysis', 'Laboratory', 'Sector A ÔÇô Bench 4', '2023-04-22', '2026-04-22', 'Operational', '2026-03-15', '2026-09-15', '2026-01-20', '2026-07-20', 2, 'Automated urinalysis strips reader.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(20, 'BioSafety Cabinet Class II', 'MSC-Advantage', 'THR-2022-067', 'Thermo Fisher', 'Safety Cabinet', 'Laboratory', 'Sector D ÔÇô Micro', '2022-08-30', '2025-08-30', 'Operational', '2026-02-28', '2026-08-28', '2025-10-15', '2026-04-15', 2, 'Microbiological work cabinet. Filters replaced Jan 2026.', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(21, 'Sysmex XN-550 Auto-Analyzer', 'XN-550', 'SYS-2024-001', 'Sysmex Corporation', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 1', '2023-01-15', '2026-01-15', 'Operational', '2026-03-01', '2026-09-01', '2025-12-15', '2026-06-15', 2, 'Primary CBC analyzer. Daily QC passing.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(22, 'Roche Cobas C111 Chemistry Analyzer', 'Cobas C111', 'RCH-2023-045', 'Roche Diagnostics', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 2', '2022-06-10', '2025-06-10', 'Operational', '2026-02-20', '2026-08-20', '2026-01-10', '2026-07-10', 2, 'Used for LFT, RFT, glucose, lipid profile.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(23, 'bio-Merieux VIDAS Immunoanalyzer', 'VIDAS 3', 'BIO-2024-112', 'bioM├®rieux', 'Immunology', 'Laboratory', 'Sector C ÔÇô Bench 1', '2024-02-01', '2027-02-01', 'Operational', '2026-01-15', '2026-07-15', '2025-11-20', '2026-05-20', 2, 'ELISA-based serology tests.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(24, 'Mindray BC-5380 Hematology Sys', 'BC-5380', 'MIN-2022-033', 'Mindray', 'Hematology', 'Laboratory', 'Sector A ÔÇô Bench 3', '2022-03-15', '2025-03-15', 'Calibration Due', '2025-10-10', '2026-04-10', '2025-09-01', '2026-03-01', 2, 'Backup CBC machine. Calibration scheduled.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(25, 'Thermo Scientific Centrifuge', 'Sorvall ST8', 'THR-2021-078', 'Thermo Fisher', 'Centrifuge', 'Laboratory', 'Sector B ÔÇô Bench 4', '2021-07-20', '2024-07-20', 'Operational', '2026-03-20', '2026-09-20', '2026-02-01', '2026-08-01', 2, 'Used for serum separation.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(26, 'Beckman Coulter AU480 Chemistry', 'AU480', 'BCK-2023-091', 'Beckman Coulter', 'Biochemistry', 'Laboratory', 'Sector B ÔÇô Bench 1', '2023-09-10', '2026-09-10', 'Operational', '2026-04-01', '2026-10-01', '2026-03-05', '2026-09-05', 2, 'High-throughput biochemistry workstation.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(27, 'Bio-Rad iQ5 PCR Cycler', 'iQ5', 'BRD-2024-055', 'Bio-Rad Laboratories', 'Molecular', 'Laboratory', 'Sector D ÔÇô PCR Room', '2024-05-01', '2027-05-01', 'Operational', '2026-04-10', '2026-10-10', '2026-03-10', '2026-09-10', 2, 'Used for DNA amplification.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(28, 'Olympus CX43 Microscope', 'CX43', 'OLY-2020-018', 'Olympus Corporation', 'Microscopy', 'Laboratory', 'Sector A ÔÇô Bench 2', '2020-11-12', '2023-11-12', 'Maintenance', '2025-08-01', '2026-02-01', '2025-12-01', '2026-06-01', 2, 'Needs binocular head replacement.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(29, 'Siemens Clinitek Status+ UA Analyzer', 'Clinitek Status+', 'SIE-2023-202', 'Siemens Healthineers', 'Urinalysis', 'Laboratory', 'Sector A ÔÇô Bench 4', '2023-04-22', '2026-04-22', 'Operational', '2026-03-15', '2026-09-15', '2026-01-20', '2026-07-20', 2, 'Automated urinalysis strips reader.', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(30, 'BioSafety Cabinet Class II', 'MSC-Advantage', 'THR-2022-067', 'Thermo Fisher', 'Safety Cabinet', 'Laboratory', 'Sector D ÔÇô Micro', '2022-08-30', '2025-08-30', 'Operational', '2026-02-28', '2026-08-28', '2025-10-15', '2026-04-15', 2, 'Microbiological work cabinet. Filters replaced Jan 2026.', '2026-04-19 22:57:46', '2026-04-19 22:57:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_internal_messages`
--

INSERT INTO `lab_internal_messages` (`id`, `sender_id`, `sender_role`, `receiver_id`, `receiver_role`, `patient_id`, `order_id`, `subject`, `message_content`, `is_read`, `priority`, `sent_at`, `read_at`) VALUES
(1, 2, 'lab_technician', 4, 'doctor', 6, 42, 'CRITICAL: Fasting Glucose 18.4 mmol/L ÔÇô Immediate Action', 'Dr. Joyce, the fasting blood glucose for Adjoa Yeboah (ORD-2002) has returned critically elevated at 18.4 mmol/L. Patient is still in the facility. Please advise urgently. ÔÇö Jefferson Forson, Lab Tech.', 1, 'Urgent', '2026-04-19 22:55:33', NULL),
(2, 4, 'doctor', 2, 'lab_technician', 6, 42, 'Re: CRITICAL FBG ÔÇô Action Taken', 'Thank you Jefferson. I have reviewed the result and have initiated IV insulin protocol. Please release result to patient record and document in your ledger. Follow up glucose in 4 hours. ÔÇö Dr. Joyce Eli.', 1, 'Urgent', '2026-04-19 22:55:33', NULL),
(3, 2, 'lab_technician', 4, 'doctor', 8, 44, 'CRITICAL: Creatinine 642 ┬Ámol/L ÔÇô Acute Kidney Injury', 'Dr. Joyce, Daniel Antwi (ORD-2004) creatinine is critically elevated at 642 ┬Ámol/L with eGFR of 8. This is consistent with severe AKI. Result released. Urgent nephrology consult recommended.', 1, 'Urgent', '2026-04-19 22:55:33', NULL),
(4, 4, 'doctor', 2, 'lab_technician', 8, 44, 'Re: AKI Alert ÔÇô Nephrology Referral Arranged', 'Acknowledged Jefferson. I have placed an urgent nephrology referral. Please ensure creatinine + BUN are re-checked in 6 hours and flag to me immediately. Good catch. ÔÇö Dr. Joyce.', 1, 'Urgent', '2026-04-19 22:55:33', NULL),
(5, 2, 'lab_technician', 4, 'doctor', 10, 46, 'Malaria Result ÔÇô P. falciparum +++ Confirmed', 'Dr. Joyce, Kofi Adu (ORD-2006) malaria film confirms P. falciparum heavy parasitaemia (+++ on thick film). Result released. Patient was febrile at time of collection. Artemether-lumefantrine should be initiated.', 1, 'Normal', '2026-04-19 22:55:33', NULL),
(6, 4, 'doctor', 2, 'lab_technician', 10, 46, 'Re: Malaria Result ÔÇô Treatment Started', 'Thank you Jefferson. AL course started immediately. Please also arrange a follow-up thin film on Day 3. Note the parasitaemia for your weekly QC report. ÔÇö Dr. Joyce.', 0, 'Normal', '2026-04-19 22:55:33', NULL),
(7, 4, 'doctor', 2, 'lab_technician', 5, 41, 'New Order ÔÇô Lovelace Baidoo CBC + Iron Studies', 'Jefferson, following up on Lovelace Baidoo anemia results (Hb 8.2). Please add Serum Ferritin and TIBC to the pending orders. I will send a formal order shortly. Appreciate the quick turnaround.', 0, 'Normal', '2026-04-19 22:55:33', NULL),
(8, 2, 'lab_technician', 4, 'doctor', 7, 47, 'UTI Result ÔÇô Adjoa Appiah Culture Pending', 'Dr. Joyce, urinalysis for Adjoa Appiah (ORD-2007) shows significant bacteriuria and leucocyturia. Empirical UTI. Culture and sensitivity has been set up ÔÇö results expected in 48h. Result validated awaiting your review.', 0, 'Normal', '2026-04-19 22:55:33', NULL),
(9, 4, 'doctor', 2, 'lab_technician', 9, 45, 'Query: Lipid Profile ÔÇô Missing HDL Value', 'Jefferson, I noticed the lipid profile for Adjoa Appiah (ORD-2005) does not include the HDL cholesterol value. Was it left out of the panel? Please verify and update accordingly. ÔÇö Dr. Joyce.', 1, 'Normal', '2026-04-19 22:55:33', NULL),
(10, 2, 'lab_technician', 4, 'doctor', 9, 45, 'Re: Lipid Profile ÔÇô HDL Included in Updated Report', 'Dr. Joyce, apologies for the omission. HDL was 0.9 mmol/L (Low). I have updated the result record. TC:HDL ratio = 7.6 (High cardiovascular risk). Full corrected report now available for your review.', 0, 'Normal', '2026-04-19 22:55:33', NULL),
(11, 2, 'lab_technician', 4, 'doctor', 6, 42, 'CRITICAL: Fasting Glucose 18.4 mmol/L ÔÇô Immediate Action', 'Dr. Joyce, the fasting blood glucose for Adjoa Yeboah (ORD-2002) has returned critically elevated at 18.4 mmol/L. Patient is still in the facility. Please advise urgently. ÔÇö Jefferson Forson, Lab Tech.', 1, 'Urgent', '2026-04-19 22:57:46', NULL),
(12, 4, 'doctor', 2, 'lab_technician', 6, 42, 'Re: CRITICAL FBG ÔÇô Action Taken', 'Thank you Jefferson. I have reviewed the result and have initiated IV insulin protocol. Please release result to patient record and document in your ledger. Follow up glucose in 4 hours. ÔÇö Dr. Joyce Eli.', 1, 'Urgent', '2026-04-19 22:57:46', NULL),
(13, 2, 'lab_technician', 4, 'doctor', 8, 44, 'CRITICAL: Creatinine 642 ┬Ámol/L ÔÇô Acute Kidney Injury', 'Dr. Joyce, Daniel Antwi (ORD-2004) creatinine is critically elevated at 642 ┬Ámol/L with eGFR of 8. This is consistent with severe AKI. Result released. Urgent nephrology consult recommended.', 1, 'Urgent', '2026-04-19 22:57:46', NULL),
(14, 4, 'doctor', 2, 'lab_technician', 8, 44, 'Re: AKI Alert ÔÇô Nephrology Referral Arranged', 'Acknowledged Jefferson. I have placed an urgent nephrology referral. Please ensure creatinine + BUN are re-checked in 6 hours and flag to me immediately. Good catch. ÔÇö Dr. Joyce.', 1, 'Urgent', '2026-04-19 22:57:46', NULL),
(15, 2, 'lab_technician', 4, 'doctor', 10, 46, 'Malaria Result ÔÇô P. falciparum +++ Confirmed', 'Dr. Joyce, Kofi Adu (ORD-2006) malaria film confirms P. falciparum heavy parasitaemia (+++ on thick film). Result released. Patient was febrile at time of collection. Artemether-lumefantrine should be initiated.', 1, 'Normal', '2026-04-19 22:57:46', NULL),
(16, 4, 'doctor', 2, 'lab_technician', 10, 46, 'Re: Malaria Result ÔÇô Treatment Started', 'Thank you Jefferson. AL course started immediately. Please also arrange a follow-up thin film on Day 3. Note the parasitaemia for your weekly QC report. ÔÇö Dr. Joyce.', 0, 'Normal', '2026-04-19 22:57:46', NULL),
(17, 4, 'doctor', 2, 'lab_technician', 5, 41, 'New Order ÔÇô Lovelace Baidoo CBC + Iron Studies', 'Jefferson, following up on Lovelace Baidoo anemia results (Hb 8.2). Please add Serum Ferritin and TIBC to the pending orders. I will send a formal order shortly. Appreciate the quick turnaround.', 0, 'Normal', '2026-04-19 22:57:46', NULL),
(18, 2, 'lab_technician', 4, 'doctor', 7, 47, 'UTI Result ÔÇô Adjoa Appiah Culture Pending', 'Dr. Joyce, urinalysis for Adjoa Appiah (ORD-2007) shows significant bacteriuria and leucocyturia. Empirical UTI. Culture and sensitivity has been set up ÔÇö results expected in 48h. Result validated awaiting your review.', 0, 'Normal', '2026-04-19 22:57:46', NULL),
(19, 4, 'doctor', 2, 'lab_technician', 9, 45, 'Query: Lipid Profile ÔÇô Missing HDL Value', 'Jefferson, I noticed the lipid profile for Adjoa Appiah (ORD-2005) does not include the HDL cholesterol value. Was it left out of the panel? Please verify and update accordingly. ÔÇö Dr. Joyce.', 1, 'Normal', '2026-04-19 22:57:46', NULL),
(20, 2, 'lab_technician', 4, 'doctor', 9, 45, 'Re: Lipid Profile ÔÇô HDL Included in Updated Report', 'Dr. Joyce, apologies for the omission. HDL was 0.9 mmol/L (Low). I have updated the result record. TC:HDL ratio = 7.6 (High cardiovascular risk). Full corrected report now available for your review.', 0, 'Normal', '2026-04-19 22:57:46', NULL),
(21, 28, 'lab_technician', 20, 'doctor', NULL, NULL, NULL, 'Good day Doctor\r\nThe result you requested is attached to this email', 1, 'Normal', '2026-04-20 04:16:21', NULL),
(22, 20, 'doctor', 28, 'lab_technician', NULL, NULL, 'Lab Query #1', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 1.', 0, 'Normal', '2026-04-20 04:59:51', NULL),
(23, 28, 'lab_technician', 20, 'doctor', NULL, NULL, 'Lab Query #2', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 2.', 0, 'Normal', '2026-04-20 03:59:51', NULL),
(24, 20, 'doctor', 28, 'lab_technician', NULL, NULL, 'Lab Query #3', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 3.', 0, 'Normal', '2026-04-20 02:59:51', NULL),
(25, 28, 'lab_technician', 20, 'doctor', NULL, NULL, 'Lab Query #4', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 4.', 0, 'Normal', '2026-04-20 01:59:51', NULL),
(26, 20, 'doctor', 28, 'lab_technician', NULL, NULL, 'Lab Query #5', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 5.', 0, 'Normal', '2026-04-20 00:59:51', NULL),
(27, 28, 'lab_technician', 20, 'doctor', NULL, NULL, 'Lab Query #6', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 6.', 0, 'Normal', '2026-04-19 23:59:51', NULL),
(28, 20, 'doctor', 28, 'lab_technician', NULL, NULL, 'Lab Query #7', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 7.', 0, 'Normal', '2026-04-19 22:59:51', NULL),
(29, 28, 'lab_technician', 20, 'doctor', NULL, NULL, 'Lab Query #8', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 8.', 0, 'Normal', '2026-04-19 21:59:51', NULL),
(30, 20, 'doctor', 28, 'lab_technician', NULL, NULL, 'Lab Query #9', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 9.', 0, 'Normal', '2026-04-19 20:59:51', NULL),
(31, 28, 'lab_technician', 20, 'doctor', NULL, NULL, 'Lab Query #10', 'This is a seeded message regarding patient tests. Please review as soon as possible. Message index: 10.', 0, 'Normal', '2026-04-19 19:59:51', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_notifications`
--

INSERT INTO `lab_notifications` (`id`, `recipient_id`, `recipient_role`, `sender_id`, `sender_role`, `message`, `type`, `is_read`, `related_module`, `related_record_id`, `created_at`) VALUES
(1, 2, 'lab_technician', 4, 'doctor', 'New STAT order placed by Dr. Joyce Eli: Creatinine / Renal Function (ORD-2004). Patient: Daniel Antwi. Urgency: STAT.', 'New Order', 1, 'orders', 44, '2026-04-19 22:55:33'),
(2, 2, 'lab_technician', 4, 'doctor', 'New URGENT order placed by Dr. Joyce Eli: Blood Glucose Fasting (ORD-2002). Patient: Adjoa Yeboah. Urgency: Urgent.', 'New Order', 1, 'orders', 42, '2026-04-19 22:55:33'),
(3, 2, 'lab_technician', 4, 'doctor', 'Critical value alert: Fasting Glucose 18.4 mmol/L for Adjoa Yeboah has been released to Dr. Joyce Eli. Documenting critical notification.', 'Critical Value', 1, 'results', NULL, '2026-04-19 22:55:33'),
(4, 2, 'lab_technician', NULL, NULL, 'Reagent LOW STOCK alert: Glucose Oxidase Reagent (CAT-BIO-004) is below reorder threshold (90 units remaining, min 30). Re-order recommended.', 'Reagent Alert', 0, 'inventory', NULL, '2026-04-19 22:55:33'),
(5, 2, 'lab_technician', NULL, NULL, 'Equipment alert: Mindray BC-5380 (Bench 3) is due for calibration. Last calibration: Oct 2025. Please schedule immediately.', 'Equipment Alert', 0, 'equipment', NULL, '2026-04-19 22:55:33'),
(6, 2, 'lab_technician', 4, 'doctor', 'Dr. Joyce Eli has reviewed and added notes to Result ORD-2001 (CBC ÔÇô Lovelace Baidoo). Please review doctor notes.', 'Result Ready', 1, 'results', NULL, '2026-04-19 22:55:33'),
(7, 2, 'lab_technician', NULL, NULL, 'New URGENT order placed by Dr. Joyce Eli: HIV Rapid Test (ORD-2008). Patient: Adjoa Yeboah. Pre-operative screen.', 'New Order', 0, 'orders', 48, '2026-04-19 22:55:33'),
(8, 2, 'lab_technician', NULL, NULL, 'Blood Culture bottles (CAT-MIC-008) stock is LOW (40 units remaining, min 20). Procurement has been notified.', 'Reagent Alert', 0, 'inventory', NULL, '2026-04-19 22:55:33'),
(9, 2, 'lab_technician', NULL, NULL, 'Monthly Quality Control Report is due. Please complete QC documentation for April 2026 before end of the month.', 'Quality Control', 0, 'qc', NULL, '2026-04-19 22:55:33'),
(10, 2, 'lab_technician', NULL, NULL, 'System: Lab Dashboard has been updated with new Export Hub functionality. CSV and Excel exports are now active for Reports module.', 'System', 0, 'system', NULL, '2026-04-19 22:55:33'),
(11, 2, 'lab_technician', 4, 'doctor', 'New STAT order placed by Dr. Joyce Eli: Creatinine / Renal Function (ORD-2004). Patient: Daniel Antwi. Urgency: STAT.', 'New Order', 1, 'orders', 44, '2026-04-19 22:57:46'),
(12, 2, 'lab_technician', 4, 'doctor', 'New URGENT order placed by Dr. Joyce Eli: Blood Glucose Fasting (ORD-2002). Patient: Adjoa Yeboah. Urgency: Urgent.', 'New Order', 1, 'orders', 42, '2026-04-19 22:57:46'),
(13, 2, 'lab_technician', 4, 'doctor', 'Critical value alert: Fasting Glucose 18.4 mmol/L for Adjoa Yeboah has been released to Dr. Joyce Eli. Documenting critical notification.', 'Critical Value', 1, 'results', NULL, '2026-04-19 22:57:46'),
(14, 2, 'lab_technician', NULL, NULL, 'Reagent LOW STOCK alert: Glucose Oxidase Reagent (CAT-BIO-004) is below reorder threshold (90 units remaining, min 30). Re-order recommended.', 'Reagent Alert', 0, 'inventory', NULL, '2026-04-19 22:57:46'),
(15, 2, 'lab_technician', NULL, NULL, 'Equipment alert: Mindray BC-5380 (Bench 3) is due for calibration. Last calibration: Oct 2025. Please schedule immediately.', 'Equipment Alert', 0, 'equipment', NULL, '2026-04-19 22:57:46'),
(16, 2, 'lab_technician', 4, 'doctor', 'Dr. Joyce Eli has reviewed and added notes to Result ORD-2001 (CBC ÔÇô Lovelace Baidoo). Please review doctor notes.', 'Result Ready', 1, 'results', NULL, '2026-04-19 22:57:46'),
(17, 2, 'lab_technician', NULL, NULL, 'New URGENT order placed by Dr. Joyce Eli: HIV Rapid Test (ORD-2008). Patient: Adjoa Yeboah. Pre-operative screen.', 'New Order', 0, 'orders', 48, '2026-04-19 22:57:46'),
(18, 2, 'lab_technician', NULL, NULL, 'Blood Culture bottles (CAT-MIC-008) stock is LOW (40 units remaining, min 20). Procurement has been notified.', 'Reagent Alert', 0, 'inventory', NULL, '2026-04-19 22:57:46'),
(19, 2, 'lab_technician', NULL, NULL, 'Monthly Quality Control Report is due. Please complete QC documentation for April 2026 before end of the month.', 'Quality Control', 0, 'qc', NULL, '2026-04-19 22:57:46'),
(20, 2, 'lab_technician', NULL, NULL, 'System: Lab Dashboard has been updated with new Export Hub functionality. CSV and Excel exports are now active for Reports module.', 'System', 0, 'system', NULL, '2026-04-19 22:57:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_quality_control`
--

INSERT INTO `lab_quality_control` (`id`, `technician_id`, `equipment_id`, `test_catalog_id`, `qc_date`, `qc_type`, `lot_number`, `expected_range_min`, `expected_range_max`, `result_obtained`, `passed`, `corrective_action`, `notes`, `created_at`) VALUES
(1, 2, 1, 1, '2026-04-01', 'Internal', 'LOT-SYS-2024-01', 4.5000, 5.5000, 4.9200, 1, NULL, 'Daily CBC QC Level-1 passed. CV < 2%.', '2026-04-19 22:55:33'),
(2, 2, 1, 1, '2026-04-01', 'Internal', 'LOT-SYS-2024-02', 4.5000, 5.5000, 4.8800, 1, NULL, 'Daily CBC QC Level-2 passed.', '2026-04-19 22:55:33'),
(3, 2, 2, 2, '2026-04-02', 'Internal', 'LOT-RCH-2025-10', 5.0000, 5.8000, 5.3500, 1, NULL, 'Glucose QC: Normal control passed.', '2026-04-19 22:55:33'),
(4, 2, 2, 4, '2026-04-03', 'Internal', 'LOT-RCH-2025-11', 28.0000, 38.0000, 29.5000, 1, NULL, 'LFT QC: ALT within acceptable range.', '2026-04-19 22:55:33'),
(5, 2, 2, 5, '2026-04-05', 'Internal', 'LOT-RCH-2025-12', 80.0000, 120.0000, 89.0000, 1, NULL, 'RFT QC: Creatinine normal control passed.', '2026-04-19 22:55:33'),
(6, 2, 2, 6, '2026-04-07', 'Internal', 'LOT-RCH-2025-13', 4.8000, 5.2000, 4.7500, 0, 'Reagent recalibrated and QC re-run. Second result 5.05 ÔÇô accepted.', 'Lipid QC: Initial fail due to reagent temperature. Corrected.', '2026-04-19 22:55:33'),
(7, 2, 3, 12, '2026-04-10', 'Internal', 'LOT-BIO-2025-04', 0.0000, 0.5000, 0.1000, 1, NULL, 'HIV VIDAS QC: Non-reactive control passed.', '2026-04-19 22:55:33'),
(8, 2, 9, 7, '2026-04-09', 'Internal', 'LOT-SIE-2024-07', 1.0100, 1.0300, 1.0200, 1, NULL, 'UA analyzer: Specific gravity control within range.', '2026-04-19 22:55:33'),
(9, 2, 1, 1, '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 4.6000, 5.4000, 4.9800, 1, NULL, 'External QC (proficiency testing) ÔÇô CBC panel. Acceptable performance.', '2026-04-19 22:55:33'),
(10, 2, 2, 2, '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 5.1000, 5.9000, 5.6800, 1, NULL, 'External QC ÔÇô Glucose panel. Result within target range.', '2026-04-19 22:55:33'),
(11, 2, 1, 1, '2026-04-01', 'Internal', 'LOT-SYS-2024-01', 4.5000, 5.5000, 4.9200, 1, NULL, 'Daily CBC QC Level-1 passed. CV < 2%.', '2026-04-19 22:57:46'),
(12, 2, 1, 1, '2026-04-01', 'Internal', 'LOT-SYS-2024-02', 4.5000, 5.5000, 4.8800, 1, NULL, 'Daily CBC QC Level-2 passed.', '2026-04-19 22:57:46'),
(13, 2, 2, 2, '2026-04-02', 'Internal', 'LOT-RCH-2025-10', 5.0000, 5.8000, 5.3500, 1, NULL, 'Glucose QC: Normal control passed.', '2026-04-19 22:57:46'),
(14, 2, 2, 4, '2026-04-03', 'Internal', 'LOT-RCH-2025-11', 28.0000, 38.0000, 29.5000, 1, NULL, 'LFT QC: ALT within acceptable range.', '2026-04-19 22:57:46'),
(15, 2, 2, 5, '2026-04-05', 'Internal', 'LOT-RCH-2025-12', 80.0000, 120.0000, 89.0000, 1, NULL, 'RFT QC: Creatinine normal control passed.', '2026-04-19 22:57:46'),
(16, 2, 2, 6, '2026-04-07', 'Internal', 'LOT-RCH-2025-13', 4.8000, 5.2000, 4.7500, 0, 'Reagent recalibrated and QC re-run. Second result 5.05 ÔÇô accepted.', 'Lipid QC: Initial fail due to reagent temperature. Corrected.', '2026-04-19 22:57:46'),
(17, 2, 3, 12, '2026-04-10', 'Internal', 'LOT-BIO-2025-04', 0.0000, 0.5000, 0.1000, 1, NULL, 'HIV VIDAS QC: Non-reactive control passed.', '2026-04-19 22:57:46'),
(18, 2, 9, 7, '2026-04-09', 'Internal', 'LOT-SIE-2024-07', 1.0100, 1.0300, 1.0200, 1, NULL, 'UA analyzer: Specific gravity control within range.', '2026-04-19 22:57:46'),
(19, 2, 1, 1, '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 4.6000, 5.4000, 4.9800, 1, NULL, 'External QC (proficiency testing) ÔÇô CBC panel. Acceptable performance.', '2026-04-19 22:57:46'),
(20, 2, 2, 2, '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 5.1000, 5.9000, 5.6800, 1, NULL, 'External QC ÔÇô Glucose panel. Result within target range.', '2026-04-19 22:57:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_reference_ranges`
--

INSERT INTO `lab_reference_ranges` (`id`, `test_catalog_id`, `parameter_name`, `gender`, `age_min_years`, `age_max_years`, `normal_min`, `normal_max`, `critical_low`, `critical_high`, `unit`, `updated_by`, `updated_at`) VALUES
(1, 1, 'Haemoglobin', 'Male', 18, 999, 13.5000, 17.5000, 7.0000, 20.0000, 'g/dL', 2, '2026-04-19 22:57:46'),
(2, 1, 'Haemoglobin', 'Female', 18, 999, 12.0000, 16.0000, 7.0000, 20.0000, 'g/dL', 2, '2026-04-19 22:57:46'),
(3, 1, 'White Blood Cells', 'Both', 18, 999, 4.5000, 11.0000, 2.0000, 30.0000, 'x10Ôü╣/L', 2, '2026-04-19 22:57:46'),
(4, 1, 'Platelets', 'Both', 18, 999, 150.0000, 400.0000, 50.0000, 1000.0000, 'x10Ôü╣/L', 2, '2026-04-19 22:57:46'),
(5, 2, 'Fasting Blood Glucose', 'Both', 18, 999, 3.9000, 6.1000, 2.5000, 25.0000, 'mmol/L', 2, '2026-04-19 22:57:46'),
(6, 4, 'ALT (SGPT)', 'Both', 18, 999, 7.0000, 56.0000, 0.0000, 200.0000, 'U/L', 2, '2026-04-19 22:57:46'),
(7, 4, 'Total Bilirubin', 'Both', 18, 999, 3.4000, 17.1000, 0.0000, 60.0000, '┬Ámol/L', 2, '2026-04-19 22:57:46'),
(8, 5, 'Serum Creatinine', 'Male', 18, 999, 62.0000, 115.0000, 20.0000, 800.0000, '┬Ámol/L', 2, '2026-04-19 22:57:46'),
(9, 5, 'Serum Creatinine', 'Female', 18, 999, 44.0000, 97.0000, 20.0000, 700.0000, '┬Ámol/L', 2, '2026-04-19 22:57:46'),
(10, 6, 'Total Cholesterol', 'Both', 18, 999, 0.0000, 5.2000, 0.0000, 15.0000, 'mmol/L', 2, '2026-04-19 22:57:46');

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
  `order_id` int DEFAULT NULL,
  `sample_id` int DEFAULT NULL,
  `technician_id` int DEFAULT NULL,
  `unit_of_measurement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_status` enum('Draft','Pending Validation','Validated','Released','Amended') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `released_to_doctor` tinyint(1) DEFAULT '0',
  `released_at` datetime DEFAULT NULL,
  `released_to_patient` tinyint(1) DEFAULT '0',
  `patient_release_approved_by` int DEFAULT NULL,
  `report_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`result_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_test_id` (`test_id`),
  KEY `idx_status` (`status`),
  KEY `idx_test_date` (`test_date`),
  KEY `idx_lr_patient` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_results`
--

INSERT INTO `lab_results` (`result_id`, `patient_id`, `test_id`, `doctor_id`, `test_date`, `result_date`, `status`, `results`, `normal_range`, `interpretation`, `technician_notes`, `submitted_by`, `doctor_reviewed`, `patient_accessible`, `patient_notified`, `patient_viewed_at`, `result_file_path`, `doctor_notes`, `created_at`, `updated_at`, `validated_by`, `validated_at`, `result_interpretation`, `amended_reason`, `reference_range_min`, `reference_range_max`, `order_id`, `sample_id`, `technician_id`, `unit_of_measurement`, `result_status`, `released_to_doctor`, `released_at`, `released_to_patient`, `patient_release_approved_by`, `report_file_path`) VALUES
(1, 5, 45, 4, '2026-02-20', '2026-03-31', 'Completed', '{\"unit\": \"mmol/L\", \"value\": \"11.3\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-22 16:17:11', '2026-04-12 10:22:48', NULL, NULL, 'Normal', NULL, NULL, NULL, 11, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(2, 5, 39, 4, '2026-03-19', '2026-04-08', 'Completed', '{\"unit\": \"g/dL\", \"value\": \"23.1\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-07 06:22:01', '2026-04-12 10:22:48', NULL, NULL, 'Normal', NULL, NULL, NULL, 12, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(3, 5, 1, 4, '2026-02-14', '2026-04-02', 'Completed', '{\"unit\": \"g/dL\", \"value\": \"20.0\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-18 21:59:24', '2026-04-12 10:22:48', NULL, NULL, 'Abnormal', NULL, NULL, NULL, 13, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(4, 5, 45, 4, '2026-02-23', '2026-03-07', 'Completed', '{\"unit\": \"mg/dL\", \"value\": \"12.3\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-31 23:03:48', '2026-04-12 10:22:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 14, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(5, 5, 1, 4, '2026-03-01', '2026-04-04', 'Completed', '{\"unit\": \"mg/dL\", \"value\": \"5.0\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-22 18:31:19', '2026-04-12 10:22:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 15, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(6, 5, 42, 4, '2026-03-25', '2026-03-07', 'Completed', '{\"unit\": \"mg/dL\", \"value\": \"25.7\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-26 09:04:18', '2026-04-12 10:22:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 16, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(7, 5, 36, 4, '2026-03-26', '2026-03-20', 'Completed', '{\"unit\": \"mmol/L\", \"value\": \"10.7\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-27 16:13:49', '2026-04-12 10:22:48', NULL, NULL, 'Abnormal', NULL, NULL, NULL, 17, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(8, 5, 40, 4, '2026-03-13', '2026-03-29', 'Completed', '{\"unit\": \"U/L\", \"value\": \"29.8\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-08 08:57:31', '2026-04-12 10:22:48', NULL, NULL, 'Normal', NULL, NULL, NULL, 18, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(9, 9, 1, 4, '2026-03-12', '2026-04-09', 'Completed', '{\"unit\": \"mg/dL\", \"value\": \"24.5\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-25 06:59:25', '2026-04-12 10:26:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 21, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(10, 5, 47, 4, '2026-03-28', '2026-02-22', 'Completed', '{\"unit\": \"g/dL\", \"value\": \"7.7\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-03 19:35:51', '2026-04-12 10:26:48', NULL, NULL, 'Abnormal', NULL, NULL, NULL, 22, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(11, 5, 51, 4, '2026-04-08', '2026-04-04', 'Completed', '{\"unit\": \"mmol/L\", \"value\": \"6.4\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-16 08:11:14', '2026-04-12 10:26:48', NULL, NULL, 'Normal', NULL, NULL, NULL, 23, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(12, 9, 51, 4, '2026-02-12', '2026-03-06', 'Completed', '{\"unit\": \"U/L\", \"value\": \"22.6\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-22 23:03:39', '2026-04-12 10:26:48', NULL, NULL, 'Normal', NULL, NULL, NULL, 24, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(13, 10, 50, 4, '2026-02-14', '2026-03-16', 'Completed', '{\"unit\": \"U/L\", \"value\": \"10.0\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-15 08:05:03', '2026-04-12 10:26:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 25, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(14, 7, 51, 4, '2026-03-24', '2026-03-28', 'Completed', '{\"unit\": \"mg/dL\", \"value\": \"26.6\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-16 18:51:13', '2026-04-12 10:26:48', NULL, NULL, 'Critical', NULL, NULL, NULL, 26, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(15, 9, 48, 4, '2026-02-27', '2026-02-17', 'Completed', '{\"unit\": \"mmol/L\", \"value\": \"25.4\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-03-04 22:39:03', '2026-04-12 10:26:48', NULL, NULL, 'Abnormal', NULL, NULL, NULL, 27, NULL, 1, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(16, 7, 47, 4, '2026-03-26', '2026-04-09', 'Completed', '{\"unit\": \"mmol/L\", \"value\": \"23.2\"}', NULL, 'Results reviewed and validated.', NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2026-02-24 04:39:03', '2026-04-12 10:26:48', NULL, NULL, 'Abnormal', NULL, NULL, NULL, 28, NULL, 2, NULL, 'Released', 1, NULL, 1, NULL, NULL),
(17, 101, 1, 10, '2026-04-14', NULL, 'Completed', '{\"HGB\": \"14.2 g/dL\", \"PLT\": \"250 x10^9/L\", \"WBC\": \"6.5 x10^9/L\"}', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(18, 102, 2, 10, '2026-04-13', NULL, 'Completed', '{\"Type\": \"Rings\", \"Plasmodium falciparum\": \"Positive (+++)\"}', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Abnormal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(19, 103, 3, 10, '2026-04-14', NULL, 'In Progress', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(20, 104, 4, 10, '2026-04-14', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(21, 105, 5, 10, '2026-04-12', NULL, 'Completed', '{\"HDL\": \"50 mg/dL\", \"LDL\": \"110 mg/dL\", \"Total Cholesterol\": \"180 mg/dL\"}', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(22, 106, 1, 10, '2026-04-14', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(23, 107, 7, 10, '2026-04-14', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(24, 108, 1, 10, '2026-04-14', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(25, 109, 9, 10, '2026-04-09', NULL, 'Completed', '{\"HIV 1/2\": \"Non-Reactive\"}', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(26, 110, 1, 10, '2026-04-14', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '2026-04-14 06:53:52', '2026-04-14 06:53:52', NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Draft', 0, NULL, 0, NULL, NULL),
(27, 5, 1, 4, '2026-04-01', '2026-04-01', 'Completed', NULL, NULL, NULL, 'Hb: 8.2 g/dL ÔÇô Microcytic hypochromic anaemia pattern. Suggest iron studies.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-01 14:00:00', 'Abnormal', NULL, 12.0000, 16.0000, 41, NULL, 2, 'g/dL', 'Released', 1, '2026-04-01 14:30:00', 0, NULL, NULL),
(28, 6, 2, 4, '2026-04-02', '2026-04-02', 'Completed', NULL, NULL, NULL, 'FBG: 18.4 mmol/L ÔÇô Severe hyperglycaemia. Notified Dr. Joyce immediately.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-02 10:30:00', 'Critical', NULL, 3.9000, 6.1000, 42, NULL, 2, 'mmol/L', 'Released', 1, '2026-04-02 11:00:00', 0, NULL, NULL),
(29, 7, 4, 4, '2026-04-03', '2026-04-03', 'Completed', NULL, NULL, NULL, 'ALT 92 U/L (N: 7-56), ALP 210 U/L ÔÇô Elevated. Consistent with drug-induced hepatitis.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-03 15:00:00', 'Abnormal', NULL, 7.0000, 56.0000, 43, NULL, 2, 'U/L', 'Released', 1, '2026-04-03 15:45:00', 0, NULL, NULL),
(30, 8, 5, 4, '2026-04-05', '2026-04-05', 'Completed', NULL, NULL, NULL, 'Creatinine: 642 ┬Ámol/L, eGFR: 8 mL/min ÔÇô Severe AKI. Urgent nephrology referral indicated.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-05 12:30:00', 'Critical', NULL, 44.0000, 106.0000, 44, NULL, 2, '┬Ámol/L', 'Released', 1, '2026-04-05 13:00:00', 0, NULL, NULL),
(31, 9, 6, 4, '2026-04-07', '2026-04-07', 'Completed', NULL, NULL, NULL, 'Total Chol: 6.8 mmol/L, LDL: 4.2 mmol/L ÔÇô Hypercholesterolaemia. Statin therapy recommended.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-07 11:00:00', 'Abnormal', NULL, 0.0000, 5.2000, 45, NULL, 2, 'mmol/L', 'Released', 1, '2026-04-07 11:30:00', 0, NULL, NULL),
(32, 10, 8, 4, '2026-04-08', '2026-04-08', 'Completed', NULL, NULL, NULL, 'P. falciparum +++ on thick film. Parasite density high. Anti-malarials initiated.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-08 15:15:00', 'Abnormal', NULL, 0.0000, 0.0000, 46, NULL, 2, 'parasites/┬ÁL', 'Released', 1, '2026-04-08 15:45:00', 0, NULL, NULL),
(33, 5, 7, 4, '2026-04-09', '2026-04-09', 'Completed', NULL, NULL, NULL, 'Nitrites: Positive, Leucocytes: +++, Bacteria: >100,000 CFU/mL ÔÇô UTI confirmed.', 2, 1, 0, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-09 13:00:00', 'Abnormal', NULL, 0.0000, 10000.0000, 47, NULL, 2, 'CFU/mL', 'Validated', 0, NULL, 0, NULL, NULL),
(34, 6, 12, 4, '2026-04-10', '2026-04-10', 'Completed', NULL, NULL, NULL, 'HIV RDT: Non-reactive. Pre-operative clearance granted.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', 2, '2026-04-10 10:00:00', 'Normal', NULL, 0.0000, 0.0000, 48, NULL, 2, 'N/A', 'Released', 1, '2026-04-10 10:20:00', 0, NULL, NULL),
(35, 7, 15, 4, '2026-04-12', '2026-04-13', 'In Progress', NULL, NULL, NULL, 'H antigen 1:160 borderline. Repeat serology after 5 days recommended.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', NULL, NULL, 'Inconclusive', NULL, 0.0000, 80.0000, 49, NULL, 2, 'titre', 'Pending Validation', 0, NULL, 0, NULL, NULL),
(36, 8, 10, 4, '2026-04-14', NULL, 'In Progress', NULL, NULL, NULL, 'Incubating 72h. No growth at 24h. Continue monitoring.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33', NULL, NULL, 'Normal', NULL, 0.0000, 0.0000, 50, NULL, 2, 'growth', 'Draft', 0, NULL, 0, NULL, NULL),
(37, 5, 1, 4, '2026-04-01', '2026-04-01', 'Completed', NULL, NULL, NULL, 'Hb: 8.2 g/dL ÔÇô Microcytic hypochromic anaemia pattern. Suggest iron studies.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-01 14:00:00', 'Abnormal', NULL, 12.0000, 16.0000, 41, NULL, 2, 'g/dL', 'Released', 1, '2026-04-01 14:30:00', 0, NULL, NULL),
(38, 6, 2, 4, '2026-04-02', '2026-04-02', 'Completed', NULL, NULL, NULL, 'FBG: 18.4 mmol/L ÔÇô Severe hyperglycaemia. Notified Dr. Joyce immediately.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-02 10:30:00', 'Critical', NULL, 3.9000, 6.1000, 42, NULL, 2, 'mmol/L', 'Released', 1, '2026-04-02 11:00:00', 0, NULL, NULL),
(39, 7, 4, 4, '2026-04-03', '2026-04-03', 'Completed', NULL, NULL, NULL, 'ALT 92 U/L (N: 7-56), ALP 210 U/L ÔÇô Elevated. Consistent with drug-induced hepatitis.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-03 15:00:00', 'Abnormal', NULL, 7.0000, 56.0000, 43, NULL, 2, 'U/L', 'Released', 1, '2026-04-03 15:45:00', 0, NULL, NULL),
(40, 8, 5, 4, '2026-04-05', '2026-04-05', 'Completed', NULL, NULL, NULL, 'Creatinine: 642 ┬Ámol/L, eGFR: 8 mL/min ÔÇô Severe AKI. Urgent nephrology referral indicated.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-05 12:30:00', 'Critical', NULL, 44.0000, 106.0000, 44, NULL, 2, '┬Ámol/L', 'Released', 1, '2026-04-05 13:00:00', 0, NULL, NULL),
(41, 9, 6, 4, '2026-04-07', '2026-04-07', 'Completed', NULL, NULL, NULL, 'Total Chol: 6.8 mmol/L, LDL: 4.2 mmol/L ÔÇô Hypercholesterolaemia. Statin therapy recommended.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-07 11:00:00', 'Abnormal', NULL, 0.0000, 5.2000, 45, NULL, 2, 'mmol/L', 'Released', 1, '2026-04-07 11:30:00', 0, NULL, NULL),
(42, 10, 8, 4, '2026-04-08', '2026-04-08', 'Completed', NULL, NULL, NULL, 'P. falciparum +++ on thick film. Parasite density high. Anti-malarials initiated.', 2, 1, 1, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-08 15:15:00', 'Abnormal', NULL, 0.0000, 0.0000, 46, NULL, 2, 'parasites/┬ÁL', 'Released', 1, '2026-04-08 15:45:00', 0, NULL, NULL),
(43, 5, 7, 4, '2026-04-09', '2026-04-09', 'Completed', NULL, NULL, NULL, 'Nitrites: Positive, Leucocytes: +++, Bacteria: >100,000 CFU/mL ÔÇô UTI confirmed.', 2, 1, 0, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-09 13:00:00', 'Abnormal', NULL, 0.0000, 10000.0000, 47, NULL, 2, 'CFU/mL', 'Validated', 0, NULL, 0, NULL, NULL),
(44, 6, 12, 4, '2026-04-10', '2026-04-10', 'Completed', NULL, NULL, NULL, 'HIV RDT: Non-reactive. Pre-operative clearance granted.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', 2, '2026-04-10 10:00:00', 'Normal', NULL, 0.0000, 0.0000, 48, NULL, 2, 'N/A', 'Released', 1, '2026-04-10 10:20:00', 0, NULL, NULL),
(45, 7, 15, 4, '2026-04-12', '2026-04-13', 'In Progress', NULL, NULL, NULL, 'H antigen 1:160 borderline. Repeat serology after 5 days recommended.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', NULL, NULL, 'Inconclusive', NULL, 0.0000, 80.0000, 49, NULL, 2, 'titre', 'Pending Validation', 0, NULL, 0, NULL, NULL),
(46, 8, 10, 4, '2026-04-14', NULL, 'In Progress', NULL, NULL, NULL, 'Incubating 72h. No growth at 24h. Continue monitoring.', 2, 0, 0, 0, NULL, NULL, NULL, '2026-04-19 22:57:46', '2026-04-19 22:57:46', NULL, NULL, 'Normal', NULL, 0.0000, 0.0000, 50, NULL, 2, 'growth', 'Draft', 0, NULL, 0, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_samples`
--

INSERT INTO `lab_samples` (`id`, `sample_id`, `order_id`, `patient_id`, `technician_id`, `sample_type`, `sample_code`, `collection_date`, `collection_time`, `collected_by`, `container_type`, `volume_collected`, `storage_location`, `condition_on_receipt`, `status`, `rejection_reason`, `notes`, `barcode_image_path`, `created_at`) VALUES
(1, 'SMP-001', 1, 101, NULL, 'Blood', 'B-101-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Processing', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(2, 'SMP-002', 2, 102, NULL, 'Blood', 'B-102-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Received', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(3, 'SMP-003', 3, 103, NULL, 'Urine', 'U-103-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Processing', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(4, 'SMP-004', 4, 104, NULL, 'Blood', 'B-104-001', '2026-04-15', NULL, NULL, NULL, NULL, NULL, 'Good', 'Collected', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(5, 'SMP-005', 5, 105, NULL, 'Blood', 'B-105-001', '2026-04-13', NULL, NULL, NULL, NULL, NULL, 'Good', 'Stored', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(6, 'SMP-006', 6, 106, NULL, 'Urine', 'U-106-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Received', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(7, 'SMP-007', 7, 107, NULL, 'Blood', 'B-107-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Processing', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(8, 'SMP-008', 8, 108, NULL, 'Stool', 'S-108-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Collected', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(9, 'SMP-009', 9, 109, NULL, 'Swab', 'W-109-001', '2026-04-13', NULL, NULL, NULL, NULL, NULL, 'Good', 'Disposed', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(10, 'SMP-010', 10, 110, NULL, 'Blood', 'B-110-001', '2026-04-14', NULL, NULL, NULL, NULL, NULL, 'Good', 'Received', NULL, NULL, NULL, '2026-04-14 06:53:26'),
(11, 'SAMP-2001', 41, 5, 2, 'Blood', 'BLD-20260401-001', '2026-04-01', '07:45:00', 2, 'EDTA Vacutainer 3mL', '3 mL', 'Fridge Rack A-1', 'Good', 'Collected', NULL, 'Good sample. No haemolysis.', NULL, '2026-04-19 22:55:33'),
(12, 'SAMP-2002', 42, 6, 2, 'Blood', 'BLD-20260402-001', '2026-04-02', '08:10:00', 2, 'Plain Vacutainer 5mL', '5 mL', 'Bench B centrifuge', 'Good', 'Processing', NULL, 'Patient confirmed 8h fast.', NULL, '2026-04-19 22:55:33'),
(13, 'SAMP-2003', 43, 7, 2, 'Blood', 'BLD-20260403-001', '2026-04-03', '09:00:00', 2, 'SST Vacutainer 5mL', '5 mL', 'Fridge Rack B-2', 'Good', 'Received', NULL, 'Centrifuged at 3000 rpm x 10 min.', NULL, '2026-04-19 22:55:33'),
(14, 'SAMP-2004', 44, 8, 2, 'Blood', 'BLD-20260405-001', '2026-04-05', '11:30:00', 2, 'SST Vacutainer 5mL', '5 mL', 'Bench B ÔÇô urgent rack', 'Good', 'Processing', NULL, 'STAT sample. Prioritised.', NULL, '2026-04-19 22:55:33'),
(15, 'SAMP-2005', 45, 9, 2, 'Blood', 'BLD-20260407-001', '2026-04-07', '07:55:00', 2, 'EDTA + SST Vacutainer', '8 mL', 'Fridge Rack A-3', 'Good', 'Collected', NULL, '12h fast confirmed. Lipid profile.', NULL, '2026-04-19 22:55:33'),
(16, 'SAMP-2006', 46, 10, 2, 'Blood', 'BLD-20260408-001', '2026-04-08', '14:20:00', 2, 'Thin/Thick Film Slide', '2 mL', 'Microscopy rack', 'Good', 'Processing', NULL, 'Thick and thin film prepared for MP.', NULL, '2026-04-19 22:55:33'),
(17, 'SAMP-2007', 47, 5, 2, 'Urine', 'URI-20260409-001', '2026-04-09', '10:05:00', 2, 'Universal Urine Cup', '20 mL', 'Bench A ÔÇô UA analyzer', 'Good', 'Received', NULL, 'Mid-stream clean catch sample.', NULL, '2026-04-19 22:55:33'),
(18, 'SAMP-2008', 48, 6, 2, 'Blood', 'BLD-20260410-001', '2026-04-10', '09:40:00', 2, 'Plain Vacutainer 3mL', '3 mL', 'Fridge Rack C-1', 'Good', 'Collected', NULL, 'HIV RDT strip test prepared.', NULL, '2026-04-19 22:55:33'),
(19, 'SAMP-2009', 49, 7, 2, 'Blood', 'BLD-20260412-001', '2026-04-12', '08:30:00', 2, 'Plain Vacutainer 5mL', '5 mL', 'Fridge Rack B-4', 'Good', 'Collected', NULL, 'Widal test ÔÇô serum Obtained.', NULL, '2026-04-19 22:55:33'),
(20, 'SAMP-2010', 50, 8, 2, 'Blood', 'BLD-20260414-001', '2026-04-14', '16:00:00', 2, 'Blood Culture Bottle (Aerobic)', '10 mL', 'Incubator 37┬░C', 'Good', 'Processing', NULL, 'Two blood culture sets drawn. Aerobic + anaerobic.', NULL, '2026-04-19 22:55:33');

-- --------------------------------------------------------

--
-- Table structure for table `lab_technicians`
--

DROP TABLE IF EXISTS `lab_technicians`;
CREATE TABLE IF NOT EXISTS `lab_technicians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `technician_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. LAB-TECH-001',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghanaian',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ghana',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_issuing_body` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `specialization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_specialization` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Lab Technician',
  `years_of_experience` int DEFAULT '0',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `languages_spoken` json DEFAULT NULL,
  `marital_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability_status` enum('Available','Busy','On Break','Off Duty') COLLATE utf8mb4_unicode_ci DEFAULT 'Available',
  `two_fa_enabled` tinyint(1) DEFAULT '0',
  `shift_preference_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive','On Leave','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `member_since` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `institution_attended` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgraduate_details` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lab_tech_user` (`user_id`),
  KEY `fk_lab_tech_approved_by` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_technicians`
--

INSERT INTO `lab_technicians` (`id`, `user_id`, `technician_id`, `full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `email`, `secondary_phone`, `personal_email`, `street_address`, `city`, `region`, `country`, `postal_code`, `profile_photo`, `license_number`, `license_issuing_body`, `license_expiry`, `specialization`, `sub_specialization`, `department_id`, `designation`, `years_of_experience`, `bio`, `languages_spoken`, `marital_status`, `religion`, `national_id`, `office_location`, `availability_status`, `two_fa_enabled`, `shift_preference_notes`, `status`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `member_since`, `last_login`, `created_at`, `updated_at`, `institution_attended`, `graduation_year`, `postgraduate_details`) VALUES
(2, 28, 'LT-0002', 'Jefferson Forson', NULL, NULL, 'Ghanaian', '0500168225', 'jefferson.forson@st.rmu.edu.gh', NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Lab Technician', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 0, NULL, 'Active', 'approved', 1, '2026-03-23 07:32:28', NULL, NULL, NULL, '2026-03-23 07:31:52', '2026-03-23 07:32:28', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician_activity_log`
--

DROP TABLE IF EXISTS `lab_technician_activity_log`;
CREATE TABLE IF NOT EXISTS `lab_technician_activity_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `technician_id` int UNSIGNED NOT NULL,
  `action_description` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actlog_tech` (`technician_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_technician_activity_log`
--

INSERT INTO `lab_technician_activity_log` (`id`, `technician_id`, `action_description`, `ip_address`, `device_info`, `created_at`) VALUES
(1, 2, 'Logged in to Lab Technician Dashboard.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(2, 2, 'Accepted and processed STAT order LAB-ORD-2004 (RFT ÔÇô AKI).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(3, 2, 'Entered and validated CBC result for patient Lovelace Baidoo.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(4, 2, 'Released critical glucose result to Dr. Joyce Eli for patient Adjoa Yeboah.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(5, 2, 'Performed daily internal QC for Sysmex XN-550 ÔÇô Level 1 and 2 PASS.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(6, 2, 'Updated reagent stock: EDTA Vacutainers received (800 units from LabMed Ghana).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(7, 2, 'Logged calibration for Sysmex XN-550. Next due: 2026-09-01.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(8, 2, 'Submitted malaria parasite thick film result for Kofi Adu (ORD-2006).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(9, 2, 'Set up aerobic blood culture bottles for sepsis patient Daniel Antwi.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(10, 2, 'Exported April 2026 lab results report as CSV from Reports module.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:55:34'),
(11, 2, 'Logged in to Lab Technician Dashboard.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(12, 2, 'Accepted and processed STAT order LAB-ORD-2004 (RFT ÔÇô AKI).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(13, 2, 'Entered and validated CBC result for patient Lovelace Baidoo.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(14, 2, 'Released critical glucose result to Dr. Joyce Eli for patient Adjoa Yeboah.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(15, 2, 'Performed daily internal QC for Sysmex XN-550 ÔÇô Level 1 and 2 PASS.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(16, 2, 'Updated reagent stock: EDTA Vacutainers received (800 units from LabMed Ghana).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(17, 2, 'Logged calibration for Sysmex XN-550. Next due: 2026-09-01.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(18, 2, 'Submitted malaria parasite thick film result for Kofi Adu (ORD-2006).', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(19, 2, 'Set up aerobic blood culture bottles for sepsis patient Daniel Antwi.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46'),
(20, 2, 'Exported April 2026 lab results report as CSV from Reports module.', '192.168.1.10', 'Chrome 124 / Windows 10', '2026-04-19 22:57:46');

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
-- Table structure for table `lab_technician_professional_profile`
--

DROP TABLE IF EXISTS `lab_technician_professional_profile`;
CREATE TABLE IF NOT EXISTS `lab_technician_professional_profile` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `technician_id` int UNSIGNED NOT NULL,
  `specialization` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_specialization` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int UNSIGNED DEFAULT NULL,
  `designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_of_experience` tinyint UNSIGNED DEFAULT '0',
  `license_number` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_issuing_body` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `institution_attended` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` year DEFAULT NULL,
  `postgraduate_details` text COLLATE utf8mb4_unicode_ci,
  `languages_spoken` json DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tech_prof` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `session_token` varchar(255) NOT NULL,
  `device_info` text,
  `browser` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `technician_id` (`technician_id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_technician_sessions`
--

INSERT INTO `lab_technician_sessions` (`id`, `technician_id`, `session_token`, `device_info`, `browser`, `ip_address`, `login_time`, `last_active`, `is_current`) VALUES
(1, 2, 'qt2ktorkic1378bvm2petdka87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-25 05:40:47', '2026-03-31 13:47:16', 0),
(4, 2, '4jsg8h7iie4ssq02jdguj8m95l', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-31 13:47:16', '2026-03-31 14:24:08', 0),
(6, 2, 'brn76kqeasvgcuj9opv98uvkqq', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-31 14:24:08', '2026-04-09 14:05:52', 0),
(26, 2, 'vqmkr5ivom09n3q89tli4qbc7d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-04-09 14:05:52', '2026-04-09 14:21:46', 0),
(29, 2, '1tmiete18jc6iqelum499dqpkc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-04-09 14:21:46', '2026-04-13 15:36:55', 0),
(30, 2, '3apc7i5hk4jvuhpo5dh9brkmss', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-04-13 15:36:55', '2026-04-19 07:24:56', 0),
(31, 2, 'su7t008avo4qqvkd2kod77ae2p', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 07:24:56', '2026-04-19 07:41:26', 0),
(44, 2, 'gd7evqmgvg43vu2gf5oig5d1i8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 07:41:26', '2026-04-19 07:59:47', 0),
(71, 2, '1q1ano0gj6h9imjo9kda6l7ub7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 07:59:47', '2026-04-19 08:45:06', 0),
(87, 2, 'o0ev03m2bko5fm6f6frj9j4v57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 08:45:06', '2026-04-19 08:46:38', 0),
(90, 2, '8brb6t3690ksggde26usrg9m1m', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 08:46:38', '2026-04-19 09:59:40', 0),
(115, 2, 'ipgipaamhu1vpbuk7i476v5q13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 09:59:40', '2026-04-19 15:55:28', 0),
(119, 2, 'g3s6cgijtb38g86r1ke5hh83t0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 15:55:28', '2026-04-19 16:38:49', 0),
(152, 2, '4j8b8t7bmg1tadd1ce2migab5a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 16:38:49', '2026-04-19 17:03:54', 0),
(160, 2, 'vc5hmbl0e23l3ob3kql29mcnl9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 17:03:54', '2026-04-19 17:09:48', 0),
(161, 2, 't2mj9p94da52e2s160dbapchkm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 17:09:48', '2026-04-19 21:19:29', 0),
(183, 2, '3v30oq0vlr7a82pq2btp2cmrdd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 21:19:29', '2026-04-19 21:35:10', 0),
(200, 2, '8rqhgl5s76m1qra34sp313j6b9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 21:35:10', '2026-04-19 22:38:16', 0),
(206, 2, 'fjl699rdrnkktskops7jbtvtf2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-19 22:38:16', '2026-04-20 04:02:53', 0),
(228, 2, '4ov317m2sda8gmindbq8fapqfg', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-20 04:02:53', '2026-04-20 04:17:21', 0),
(249, 2, '3830qjaf5p4sb5nl75da8hplpi', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'Safari', '::1', '2026-04-20 04:17:21', '2026-04-20 04:23:02', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_technician_settings`
--

INSERT INTO `lab_technician_settings` (`id`, `technician_id`, `notification_preferences`, `theme_preference`, `language`, `alert_sound_enabled`, `notif_new_order`, `notif_critical_result`, `notif_equipment_alert`, `notif_reagent_alert`, `notif_qc_reminder`, `notif_doctor_msg`, `notif_system`, `updated_at`, `notif_stat_order`, `notif_reagent_expiry`, `notif_result_amend`, `notif_license_expiry`, `notif_shift_reminder`, `preferred_channel`) VALUES
(1, 1, NULL, 'light', 'en', 1, 1, 1, 1, 1, 1, 1, 1, '2026-03-18 02:51:46', 1, 1, 1, 1, 1, 'In-Dashboard'),
(18, 2, NULL, 'dark', 'en', 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-19 22:57:46', 1, 1, 1, 1, 1, 'In-Dashboard');

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

DROP TABLE IF EXISTS `lab_tests`;
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `technician_id_assigned` int DEFAULT NULL,
  `test_catalog_id` int DEFAULT NULL,
  `technician_id` int DEFAULT NULL,
  `urgency_level` enum('Routine','Urgent','Critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Routine',
  `test_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `urgency` enum('Routine','Urgent','STAT','Critical') COLLATE utf8mb4_unicode_ci DEFAULT 'Routine',
  `test_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_date` date NOT NULL,
  `required_by_date` date DEFAULT NULL,
  `results` text COLLATE utf8mb4_unicode_ci,
  `clinical_notes` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `result_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Accepted','Rejected','Sample Collected','Processing','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(25, 'Sputum AFB (TB Test)', 'AFB-001', 'Microbiology', 'Sputum', 'Sputum Container', NULL, 24.0, 72.0, 60.00, 0, 1, '2026-03-05 06:27:59', '2026-03-05 06:27:59'),
(26, 'Complete Blood Count', 'CBC506', 'Microbiology', 'Stool', NULL, NULL, 1.0, 9.0, 221.00, 1, 1, '2025-08-30 18:27:25', '2026-04-12 10:14:05'),
(27, 'Malaria Parasite Test', 'MPT997', 'Microbiology', 'Blood', NULL, NULL, 1.0, 12.0, 271.00, 1, 1, '2025-10-11 12:55:37', '2026-04-12 10:14:05'),
(28, 'Lipid Profile', 'LP135', 'Biochemistry', 'Swab', NULL, NULL, 1.0, 48.0, 194.00, 1, 1, '2024-04-20 05:31:55', '2026-04-12 10:14:05'),
(29, 'Liver Function Test', 'LFT781', 'Biochemistry', 'Tissue', NULL, NULL, 1.0, 38.0, 65.00, 1, 1, '2025-09-18 00:57:16', '2026-04-12 10:14:05'),
(30, 'Renal Function Test', 'RFT416', 'Hematology', 'Tissue', NULL, NULL, 1.0, 37.0, 160.00, 1, 1, '2024-10-07 11:48:55', '2026-04-12 10:14:05'),
(31, 'Blood Glucose', 'BG905', 'Urinalysis', 'Blood', NULL, NULL, 1.0, 7.0, 180.00, 1, 1, '2025-04-18 15:57:36', '2026-04-12 10:14:05'),
(32, 'Urinalysis', 'U990', 'Biochemistry', 'Stool', NULL, NULL, 1.0, 4.0, 203.00, 1, 1, '2024-11-14 22:09:55', '2026-04-12 10:14:05'),
(33, 'HbA1c', 'H981', 'Hematology', 'Urine', NULL, NULL, 1.0, 32.0, 281.00, 0, 1, '2024-10-27 10:44:15', '2026-04-12 10:14:05'),
(34, 'Blood Culture', 'BC433', 'Immunology', 'Swab', NULL, NULL, 1.0, 2.0, 84.00, 0, 1, '2024-06-16 23:24:45', '2026-04-12 10:14:05'),
(35, 'Thyroid Function Test', 'TFT974', 'Urinalysis', 'Tissue', NULL, NULL, 1.0, 2.0, 66.00, 1, 1, '2024-06-26 09:38:56', '2026-04-12 10:14:05'),
(36, 'Complete Blood Count', 'CBC724', 'Hematology', 'Tissue', NULL, NULL, 1.0, 6.0, 179.00, 0, 1, '2024-12-07 03:55:24', '2026-04-12 10:22:48'),
(37, 'Malaria Parasite Test', 'MPT300', 'Biochemistry', 'Blood', NULL, NULL, 1.0, 45.0, 53.00, 1, 1, '2025-03-15 11:08:09', '2026-04-12 10:22:48'),
(38, 'Lipid Profile', 'LP420', 'Microbiology', 'Urine', NULL, NULL, 1.0, 17.0, 291.00, 0, 1, '2025-05-09 10:01:37', '2026-04-12 10:22:48'),
(39, 'Liver Function Test', 'LFT168', 'Urinalysis', 'Swab', NULL, NULL, 1.0, 20.0, 154.00, 0, 1, '2024-12-13 09:21:38', '2026-04-12 10:22:48'),
(40, 'Renal Function Test', 'RFT219', 'Microbiology', 'Urine', NULL, NULL, 1.0, 27.0, 124.00, 0, 1, '2024-11-27 01:01:20', '2026-04-12 10:22:48'),
(41, 'Blood Glucose', 'BG672', 'Urinalysis', 'Swab', NULL, NULL, 1.0, 31.0, 103.00, 1, 1, '2024-12-28 15:10:18', '2026-04-12 10:22:48'),
(42, 'Urinalysis', 'U631', 'Immunology', 'Urine', NULL, NULL, 1.0, 36.0, 118.00, 0, 1, '2024-06-29 22:24:59', '2026-04-12 10:22:48'),
(43, 'HbA1c', 'H569', 'Biochemistry', 'Stool', NULL, NULL, 1.0, 5.0, 105.00, 1, 1, '2025-06-01 00:25:19', '2026-04-12 10:22:48'),
(44, 'Blood Culture', 'BC783', 'Microbiology', 'Tissue', NULL, NULL, 1.0, 41.0, 149.00, 1, 1, '2024-11-25 13:22:54', '2026-04-12 10:22:48'),
(45, 'Thyroid Function Test', 'TFT119', 'Hematology', 'Tissue', NULL, NULL, 1.0, 25.0, 281.00, 1, 1, '2024-07-24 07:10:17', '2026-04-12 10:22:48'),
(46, 'Complete Blood Count', 'CBC746', 'Immunology', 'Urine', NULL, NULL, 1.0, 30.0, 207.00, 0, 1, '2025-09-07 05:00:43', '2026-04-12 10:26:48'),
(47, 'Malaria Parasite Test', 'MPT692', 'Biochemistry', 'Urine', NULL, NULL, 1.0, 22.0, 142.00, 0, 1, '2024-12-31 18:46:51', '2026-04-12 10:26:48'),
(48, 'Lipid Profile', 'LP173', 'Urinalysis', 'Swab', NULL, NULL, 1.0, 29.0, 221.00, 1, 1, '2024-12-03 19:28:13', '2026-04-12 10:26:48'),
(49, 'Liver Function Test', 'LFT302', 'Biochemistry', 'Swab', NULL, NULL, 1.0, 21.0, 50.00, 0, 1, '2024-11-04 01:29:56', '2026-04-12 10:26:48'),
(50, 'Renal Function Test', 'RFT275', 'Immunology', 'Stool', NULL, NULL, 1.0, 2.0, 268.00, 0, 1, '2025-08-25 05:24:09', '2026-04-12 10:26:48'),
(51, 'Blood Glucose', 'BG144', 'Hematology', 'Stool', NULL, NULL, 1.0, 20.0, 65.00, 0, 1, '2025-03-07 08:40:43', '2026-04-12 10:26:48'),
(52, 'Urinalysis', 'U525', 'Hematology', 'Tissue', NULL, NULL, 1.0, 27.0, 298.00, 1, 1, '2025-02-16 20:08:49', '2026-04-12 10:26:48'),
(53, 'HbA1c', 'H431', 'Hematology', 'Blood', NULL, NULL, 1.0, 6.0, 73.00, 0, 1, '2025-06-22 21:59:56', '2026-04-12 10:26:48'),
(54, 'Blood Culture', 'BC634', 'Urinalysis', 'Swab', NULL, NULL, 1.0, 32.0, 165.00, 0, 1, '2025-08-25 13:43:06', '2026-04-12 10:26:48'),
(55, 'Thyroid Function Test', 'TFT565', 'Immunology', 'Swab', NULL, NULL, 1.0, 47.0, 245.00, 0, 1, '2025-05-25 13:41:19', '2026-04-12 10:26:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_test_orders`
--

INSERT INTO `lab_test_orders` (`id`, `order_id`, `request_id`, `patient_id`, `doctor_id`, `technician_id`, `test_catalog_id`, `test_name`, `urgency`, `order_date`, `required_by_date`, `clinical_notes`, `diagnosis`, `order_status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'ORD-54761', NULL, 5, 4, NULL, NULL, 'Urinalysis', 'Urgent', '2026-04-03', NULL, 'Investigate for Appendicitis', NULL, 'Completed', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(2, 'ORD-62612', NULL, 5, 4, NULL, NULL, 'Lipid Profile', 'Routine', '2026-03-27', NULL, 'Investigate for Anemia', NULL, 'Processing', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(3, 'ORD-28487', NULL, 5, 4, NULL, NULL, 'Complete Blood Count', 'Urgent', '2026-01-25', NULL, 'Investigate for Malaria', NULL, 'Processing', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(4, 'ORD-39653', NULL, 5, 4, NULL, NULL, 'Complete Blood Count', 'STAT', '2026-03-27', NULL, 'Investigate for Anemia', NULL, 'Completed', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(5, 'ORD-20432', NULL, 5, 4, NULL, NULL, 'Blood Culture', 'Routine', '2026-01-24', NULL, 'Investigate for Sickle Cell Disease', NULL, 'Pending', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(6, 'ORD-70314', NULL, 5, 4, NULL, NULL, 'HbA1c', 'Routine', '2026-04-06', NULL, 'Investigate for Pneumonia', NULL, 'Completed', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(7, 'ORD-85055', NULL, 5, 4, NULL, NULL, 'Thyroid Function Test', 'Routine', '2026-04-02', NULL, 'Investigate for Sickle Cell Disease', NULL, 'Accepted', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(8, 'ORD-69515', NULL, 5, 4, NULL, NULL, 'Lipid Profile', 'Urgent', '2026-02-20', NULL, 'Investigate for Gastroenteritis', NULL, 'Processing', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(9, 'ORD-34884', NULL, 5, 4, NULL, NULL, 'Lipid Profile', 'STAT', '2026-02-23', NULL, 'Investigate for Hypertension', NULL, 'Pending', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(10, 'ORD-59558', NULL, 5, 4, NULL, NULL, 'Complete Blood Count', 'STAT', '2026-03-04', NULL, 'Investigate for Appendicitis', NULL, 'Completed', NULL, '2026-04-12 10:14:05', '2026-04-12 10:14:05'),
(11, 'ORD-46438', NULL, 5, 4, NULL, NULL, 'Blood Culture', 'Urgent', '2026-02-12', NULL, 'Investigate for Pneumonia', NULL, 'Accepted', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(12, 'ORD-47134', NULL, 5, 4, NULL, NULL, 'Thyroid Function Test', 'Routine', '2026-03-01', NULL, 'Investigate for Sickle Cell Disease', NULL, 'Completed', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(13, 'ORD-56227', NULL, 5, 4, NULL, NULL, 'Lipid Profile', 'Urgent', '2026-02-21', NULL, 'Investigate for Typhoid Fever', NULL, 'Processing', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(14, 'ORD-34358', NULL, 5, 4, NULL, NULL, 'HbA1c', 'STAT', '2026-01-19', NULL, 'Investigate for Appendicitis', NULL, 'Completed', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(15, 'ORD-83337', NULL, 5, 4, NULL, NULL, 'Renal Function Test', 'Routine', '2026-01-27', NULL, 'Investigate for Appendicitis', NULL, 'Pending', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(16, 'ORD-37427', NULL, 5, 4, NULL, NULL, 'Blood Glucose', 'Routine', '2026-03-19', NULL, 'Investigate for Diabetes Mellitus', NULL, 'Pending', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(17, 'ORD-96851', NULL, 5, 4, NULL, NULL, 'HbA1c', 'STAT', '2026-03-14', NULL, 'Investigate for Pneumonia', NULL, 'Accepted', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(18, 'ORD-54341', NULL, 5, 4, NULL, NULL, 'Malaria Parasite Test', 'Routine', '2026-03-15', NULL, 'Investigate for Asthma', NULL, 'Completed', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(19, 'ORD-20426', NULL, 5, 4, NULL, NULL, 'Malaria Parasite Test', 'STAT', '2026-02-02', NULL, 'Investigate for Appendicitis', NULL, 'Processing', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(20, 'ORD-74024', NULL, 5, 4, NULL, NULL, 'Blood Culture', 'Urgent', '2026-04-07', NULL, 'Investigate for Hypertension', NULL, 'Pending', NULL, '2026-04-12 10:22:48', '2026-04-12 10:22:48'),
(21, 'ORD-24872', NULL, 10, 4, NULL, NULL, 'Complete Blood Count', 'Routine', '2026-02-07', NULL, 'Investigate for Sickle Cell Disease', NULL, 'Processing', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(22, 'ORD-57998', NULL, 8, 4, NULL, NULL, 'Thyroid Function Test', 'Urgent', '2026-02-14', NULL, 'Investigate for Anemia', NULL, 'Pending', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(23, 'ORD-77564', NULL, 10, 4, NULL, NULL, 'Liver Function Test', 'STAT', '2026-03-07', NULL, 'Investigate for Asthma', NULL, 'Completed', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(24, 'ORD-73221', NULL, 6, 4, NULL, NULL, 'Malaria Parasite Test', 'STAT', '2026-04-09', NULL, 'Investigate for Asthma', NULL, 'Accepted', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(25, 'ORD-87442', NULL, 7, 4, NULL, NULL, 'Blood Glucose', 'STAT', '2026-03-31', NULL, 'Investigate for Diabetes Mellitus', NULL, 'Completed', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(26, 'ORD-54012', NULL, 10, 4, NULL, NULL, 'Complete Blood Count', 'Routine', '2026-03-26', NULL, 'Investigate for Appendicitis', NULL, 'Pending', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(27, 'ORD-86725', NULL, 7, 4, NULL, NULL, 'Renal Function Test', 'Routine', '2026-03-12', NULL, 'Investigate for Anemia', NULL, 'Processing', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(28, 'ORD-82312', NULL, 7, 4, NULL, NULL, 'Urinalysis', 'Routine', '2026-01-12', NULL, 'Investigate for Gastroenteritis', NULL, 'Accepted', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(29, 'ORD-24992', NULL, 6, 4, NULL, NULL, 'Thyroid Function Test', 'Urgent', '2026-01-16', NULL, 'Investigate for Pneumonia', NULL, 'Processing', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(30, 'ORD-31289', NULL, 8, 4, NULL, NULL, 'Lipid Profile', 'STAT', '2026-03-12', NULL, 'Investigate for Typhoid Fever', NULL, 'Processing', NULL, '2026-04-12 10:26:48', '2026-04-12 10:26:48'),
(31, 'LAB-ORD-101', NULL, 101, 10, NULL, NULL, 'Full Blood Count', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Pending', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(32, 'LAB-ORD-102', NULL, 102, 10, NULL, NULL, 'Malaria Parasites', 'Urgent', '2026-04-14', NULL, NULL, NULL, 'Processing', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(33, 'LAB-ORD-103', NULL, 103, 10, NULL, NULL, 'Typhoid Test (Widal)', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Accepted', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(34, 'LAB-ORD-104', NULL, 104, 10, NULL, NULL, 'Blood Glucose (Fasting)', 'Routine', '2026-04-15', NULL, NULL, NULL, 'Pending', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(35, 'LAB-ORD-105', NULL, 105, 10, NULL, NULL, 'Lipid Profile', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Completed', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(36, 'LAB-ORD-106', NULL, 106, 10, NULL, NULL, 'Urine Analysis', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Pending', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(37, 'LAB-ORD-107', NULL, 107, 10, NULL, NULL, 'Liver Function Test', 'Urgent', '2026-04-14', NULL, NULL, NULL, 'Processing', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(38, 'LAB-ORD-108', NULL, 108, 10, NULL, NULL, 'Kidney Function Test', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Pending', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(39, 'LAB-ORD-109', NULL, 109, 10, NULL, NULL, 'HIV Screening', 'Routine', '2026-04-13', NULL, NULL, NULL, 'Completed', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(40, 'LAB-ORD-110', NULL, 110, 10, NULL, NULL, 'Sickle Cell Screening', 'Routine', '2026-04-14', NULL, NULL, NULL, 'Pending', NULL, '2026-04-14 01:04:17', '2026-04-14 01:04:17'),
(41, 'LAB-ORD-2001', NULL, 5, 4, 2, 1, 'Complete Blood Count (CBC)', 'Routine', '2026-04-01', '2026-04-02', 'Routine annual CBC. Patient reports fatigue.', 'Anaemia query', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(42, 'LAB-ORD-2002', NULL, 6, 4, 2, 2, 'Blood Glucose (Fasting)', 'Urgent', '2026-04-02', '2026-04-02', 'Fasting glucose for DM screening. 8h fast confirmed.', 'Diabetes Mellitus T2', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(43, 'LAB-ORD-2003', NULL, 7, 4, 2, 4, 'Liver Function Test (LFT)', 'Routine', '2026-04-03', '2026-04-04', 'Monitor LFT in patient on hepatotoxic medication.', 'Drug-induced liver injury query', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(44, 'LAB-ORD-2004', NULL, 8, 4, 2, 5, 'Renal Function Test (RFT)', 'STAT', '2026-04-05', '2026-04-05', 'Acute kidney injury suspected. Creatinine critical.', 'Acute Kidney Injury', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(45, 'LAB-ORD-2005', NULL, 9, 4, 2, 6, 'Lipid Profile', 'Routine', '2026-04-07', '2026-04-08', 'Cardiovascular risk stratification. Fasting sample.', 'Dyslipidaemia query', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(46, 'LAB-ORD-2006', NULL, 10, 4, 2, 8, 'Malaria Parasite Test (MP)', 'Urgent', '2026-04-08', '2026-04-08', 'High fever, chills. Suspected uncomplicated malaria.', 'Malaria Falciparum', 'Completed', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(47, 'LAB-ORD-2007', NULL, 5, 4, 2, 7, 'Urinalysis', 'Routine', '2026-04-09', '2026-04-10', 'Dysuria + frequency. Rule out UTI.', 'UTI query', 'Processing', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(48, 'LAB-ORD-2008', NULL, 6, 4, 2, 12, 'HIV Rapid Test', 'Urgent', '2026-04-10', '2026-04-10', 'Pre-operative HIV screen. Patient consented.', 'Pre-op workup', 'Sample Collected', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(49, 'LAB-ORD-2009', NULL, 7, 4, 2, 15, 'Widal Test', 'Routine', '2026-04-12', '2026-04-13', 'Prolonged fever with GI symptoms. Typhoid query.', 'Enteric Fever query', 'Pending', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(50, 'LAB-ORD-2010', NULL, 8, 4, 2, 10, 'Blood Culture & Sensitivity', 'STAT', '2026-04-14', '2026-04-14', 'Sepsis protocol activated. Blood cultures before ABx.', 'Sepsis', 'Accepted', NULL, '2026-04-19 22:55:33', '2026-04-19 22:55:33');

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_workload_log`
--

INSERT INTO `lab_workload_log` (`id`, `technician_id`, `shift_date`, `shift_type`, `total_orders_received`, `total_completed`, `total_pending`, `total_rejected`, `total_critical_results`, `notes`, `created_at`) VALUES
(1, 2, '2026-04-01', 'Morning', 12, 11, 1, 0, 0, 'Routine day. CBC batch run completed. All QC within range.', '2026-04-19 22:55:34'),
(2, 2, '2026-04-02', 'Morning', 8, 7, 0, 0, 1, 'Critical glucose result (18.4) reported to Dr. Joyce. Immediate action taken.', '2026-04-19 22:55:34'),
(3, 2, '2026-04-03', 'Morning', 10, 9, 1, 0, 0, 'LFT batch completed. One sample pending repeat due to haemolysis.', '2026-04-19 22:55:34'),
(4, 2, '2026-04-04', 'Afternoon', 6, 6, 0, 0, 0, 'Low workload afternoon shift. Equipment maintenance checks performed.', '2026-04-19 22:55:34'),
(5, 2, '2026-04-05', 'Morning', 15, 13, 1, 1, 1, 'STAT AKI creatinine critical ÔÇô nephrology notified via Dr. Joyce. One rejected sample (haemolysed).', '2026-04-19 22:55:34'),
(6, 2, '2026-04-07', 'Morning', 11, 10, 1, 0, 0, 'Lipid profile QC initially failed ÔÇô recalibrated. All results released post-correction.', '2026-04-19 22:55:34'),
(7, 2, '2026-04-08', 'Afternoon', 9, 9, 0, 0, 1, 'Malaria thick film P. falciparum +++. Urgent result relayed.', '2026-04-19 22:55:34'),
(8, 2, '2026-04-09', 'Morning', 13, 12, 1, 0, 0, 'UTI confirmed urinalysis. Blood culture set up. Culture pending 48h.', '2026-04-19 22:55:34'),
(9, 2, '2026-04-12', 'Morning', 10, 8, 2, 0, 0, 'Widal test borderline. Two orders held for clinician clarification.', '2026-04-19 22:55:34'),
(10, 2, '2026-04-14', 'Morning', 14, 11, 3, 0, 1, 'Sepsis protocol cultures ongoing. Monthly external QC submitted.', '2026-04-19 22:55:34'),
(11, 2, '2026-04-01', 'Morning', 12, 11, 1, 0, 0, 'Routine day. CBC batch run completed. All QC within range.', '2026-04-19 22:57:46'),
(12, 2, '2026-04-02', 'Morning', 8, 7, 0, 0, 1, 'Critical glucose result (18.4) reported to Dr. Joyce. Immediate action taken.', '2026-04-19 22:57:46'),
(13, 2, '2026-04-03', 'Morning', 10, 9, 1, 0, 0, 'LFT batch completed. One sample pending repeat due to haemolysis.', '2026-04-19 22:57:46'),
(14, 2, '2026-04-04', 'Afternoon', 6, 6, 0, 0, 0, 'Low workload afternoon shift. Equipment maintenance checks performed.', '2026-04-19 22:57:46'),
(15, 2, '2026-04-05', 'Morning', 15, 13, 1, 1, 1, 'STAT AKI creatinine critical ÔÇô nephrology notified via Dr. Joyce. One rejected sample (haemolysed).', '2026-04-19 22:57:46'),
(16, 2, '2026-04-07', 'Morning', 11, 10, 1, 0, 0, 'Lipid profile QC initially failed ÔÇô recalibrated. All results released post-correction.', '2026-04-19 22:57:46'),
(17, 2, '2026-04-08', 'Afternoon', 9, 9, 0, 0, 1, 'Malaria thick film P. falciparum +++. Urgent result relayed.', '2026-04-19 22:57:46'),
(18, 2, '2026-04-09', 'Morning', 13, 12, 1, 0, 0, 'UTI confirmed urinalysis. Blood culture set up. Culture pending 48h.', '2026-04-19 22:57:46'),
(19, 2, '2026-04-12', 'Morning', 10, 8, 2, 0, 0, 'Widal test borderline. Two orders held for clinician clarification.', '2026-04-19 22:57:46'),
(20, 2, '2026-04-14', 'Morning', 14, 11, 3, 0, 1, 'Sepsis protocol cultures ongoing. Monthly external QC submitted.', '2026-04-19 22:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `landing_about`
--

DROP TABLE IF EXISTS `landing_about`;
CREATE TABLE IF NOT EXISTS `landing_about` (
  `about_id` int NOT NULL AUTO_INCREMENT,
  `section_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. Our Mission, Our Vision, Our History',
  `content_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`about_id`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_announcements`
--

DROP TABLE IF EXISTS `landing_announcements`;
CREATE TABLE IF NOT EXISTS `landing_announcements` (
  `announcement_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('news','event','alert','notice') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'news',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_from` date DEFAULT NULL,
  `display_to` date DEFAULT NULL,
  `created_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_display_range` (`display_from`,`display_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_director`
--

DROP TABLE IF EXISTS `landing_director`;
CREATE TABLE IF NOT EXISTS `landing_director` (
  `director_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medical Director',
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `message` text COLLATE utf8mb4_unicode_ci COMMENT 'Director message shown on the about/director page',
  `qualifications` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated or JSON list of qualifications',
  `updated_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`director_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_doctors`
--

DROP TABLE IF EXISTS `landing_doctors`;
CREATE TABLE IF NOT EXISTS `landing_doctors` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL COMMENT 'FK to doctors.id',
  `is_featured` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uq_doctor` (`doctor_id`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_faq`
--

DROP TABLE IF EXISTS `landing_faq`;
CREATE TABLE IF NOT EXISTS `landing_faq` (
  `faq_id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'General' COMMENT 'e.g. Appointments, Services, Billing',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`faq_id`),
  KEY `idx_category` (`category`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_faq`
--

INSERT INTO `landing_faq` (`faq_id`, `question`, `answer`, `category`, `display_order`, `is_active`, `created_by`, `updated_at`) VALUES
(1, 'What are the sickbay opening hours?', 'The RMU Medical Sickbay operates 24 hours a day, 7 days a week for emergency services. Regular consultation hours are Monday ÔÇô Friday, 8:00 AM to 5:00 PM.', 'General', 1, 1, NULL, '2026-04-09 16:51:44'),
(2, 'How do I book an appointment?', 'You can book an appointment online by logging in to your student/staff portal, or by visiting the sickbay in person during working hours.', 'Appointments', 2, 1, NULL, '2026-04-09 16:51:44'),
(3, 'Can I get my prescriptions refilled?', 'Yes. Prescription refills require a consultation with the attending physician. Please bring your original prescription or medical records.', 'Medications', 3, 1, NULL, '2026-04-09 16:51:44'),
(4, 'Is the sickbay free for students?', 'Healthcare services at the RMU Sickbay are heavily subsidised for all registered students. Some specialist services may attract a small fee.', 'Billing', 4, 1, NULL, '2026-04-09 16:51:44'),
(5, 'What should I do in a medical emergency?', 'In a life-threatening emergency, call our emergency hotline immediately or proceed directly to the sickbay. Our ambulance service is available 24/7.', 'Emergency', 5, 1, NULL, '2026-04-09 16:51:44'),
(6, 'Can staff members use the sickbay?', 'Yes. All staff members of the Regional Maritime University are entitled to use sickbay services. Some services may require valid staff ID.', 'General', 6, 1, NULL, '2026-04-09 16:51:44'),
(7, 'What are the sickbay opening hours?', 'The RMU Medical Sickbay operates 24 hours a day, 7 days a week for emergency services. Regular consultation hours are Monday – Friday, 8:00 AM to 5:00 PM.', 'General', 1, 1, NULL, '2026-04-09 18:49:52'),
(8, 'How do I book an appointment?', 'You can book an appointment online by logging in to your student/staff portal, or by visiting the sickbay in person during working hours.', 'Appointments', 2, 1, NULL, '2026-04-09 18:49:52'),
(9, 'Can I get my prescriptions refilled?', 'Yes. Prescription refills require a consultation with the attending physician. Please bring your original prescription or medical records.', 'Medications', 3, 1, NULL, '2026-04-09 18:49:52'),
(10, 'Is the sickbay free for students?', 'Healthcare services at the RMU Sickbay are heavily subsidised for all registered students. Some specialist services may attract a small fee.', 'Billing', 4, 1, NULL, '2026-04-09 18:49:52'),
(11, 'What should I do in a medical emergency?', 'In a life-threatening emergency, call our emergency hotline immediately or proceed directly to the sickbay. Our ambulance service is available 24/7.', 'Emergency', 5, 1, NULL, '2026-04-09 18:49:52'),
(12, 'Can staff members use the sickbay?', 'Yes. All staff members of the Regional Maritime University are entitled to use sickbay services. Some services may require valid staff ID.', 'General', 6, 1, NULL, '2026-04-09 18:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `landing_gallery`
--

DROP TABLE IF EXISTS `landing_gallery`;
CREATE TABLE IF NOT EXISTS `landing_gallery` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'General' COMMENT 'e.g. Facility, Events, Staff, Patients',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `uploaded_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_category` (`category`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_hero_content`
--

DROP TABLE IF EXISTS `landing_hero_content`;
CREATE TABLE IF NOT EXISTS `landing_hero_content` (
  `content_id` int NOT NULL AUTO_INCREMENT,
  `headline_text` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Your Health, Our Priority',
  `subheadline_text` text COLLATE utf8mb4_unicode_ci,
  `hero_bg_image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path relative to project root',
  `overlay_opacity` decimal(3,2) NOT NULL DEFAULT '0.55' COMMENT '0.0 = transparent to 1.0 = opaque',
  `cta1_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Book Appointment',
  `cta1_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '/RMU-Medical-Management-System/php/index.php',
  `cta2_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Learn More',
  `cta2_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '/RMU-Medical-Management-System/html/about.html',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_hero_content`
--

INSERT INTO `landing_hero_content` (`content_id`, `headline_text`, `subheadline_text`, `hero_bg_image_url`, `overlay_opacity`, `cta1_text`, `cta1_url`, `cta2_text`, `cta2_url`, `is_active`, `updated_by`, `updated_at`) VALUES
(1, 'Your Health, Our Priority', 'RMU Medical Sickbay provides comprehensive healthcare services for students and staff of the Regional Maritime University. We are here for you 24/7.', NULL, 0.55, 'Book Appointment', '/RMU-Medical-Management-System/php/index.php', 'Explore Services', '/RMU-Medical-Management-System/html/services.html', 1, NULL, '2026-04-09 16:51:44'),
(2, 'Your Health, Our Priority', 'RMU Medical Sickbay provides comprehensive healthcare services for students and staff of the Regional Maritime University. We are here for you 24/7.', NULL, 0.55, 'Book Appointment', '/RMU-Medical-Management-System/php/index.php', 'Explore Services', '/RMU-Medical-Management-System/html/services.html', 1, NULL, '2026-04-09 18:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `landing_page_config`
--

DROP TABLE IF EXISTS `landing_page_config`;
CREATE TABLE IF NOT EXISTS `landing_page_config` (
  `config_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_page_config`
--

INSERT INTO `landing_page_config` (`config_id`, `setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
(1, 'default_theme', 'light', NULL, '2026-04-09 18:49:52'),
(2, 'chatbot_greeting', 'Hello! I am the RMU Medical Assistant. How can I help you today?', NULL, '2026-04-09 18:49:52'),
(3, 'chatbot_enabled', '1', NULL, '2026-04-09 18:49:52'),
(4, 'announcements_enabled', '1', NULL, '2026-04-09 18:49:52'),
(5, 'gallery_enabled', '1', NULL, '2026-04-09 18:49:52'),
(6, 'testimonials_enabled', '1', NULL, '2026-04-09 18:49:52'),
(7, 'faq_enabled', '1', NULL, '2026-04-09 18:49:52'),
(8, 'statistics_enabled', '1', NULL, '2026-04-09 18:49:52'),
(9, 'online_booking_enabled', '1', NULL, '2026-04-09 18:49:52'),
(10, 'emergency_hotline', '153', NULL, '2026-04-09 18:49:52'),
(11, 'contact_email', 'sickbay.text@st.rmu.edu.gh', NULL, '2026-04-09 18:49:52'),
(12, 'contact_phone', '0502371207', NULL, '2026-04-09 18:49:52'),
(13, 'facility_name', 'RMU Medical Sickbay', NULL, '2026-04-09 18:49:52'),
(14, 'facility_address', 'Regional Maritime University, Nungua, Accra, Ghana', NULL, '2026-04-09 18:49:52'),
(15, 'google_maps_url', '', NULL, '2026-04-09 18:49:52'),
(16, 'facebook_url', '', NULL, '2026-04-09 18:49:52'),
(17, 'twitter_url', '', NULL, '2026-04-09 18:49:52'),
(18, 'instagram_url', '', NULL, '2026-04-09 18:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `landing_services`
--

DROP TABLE IF EXISTS `landing_services`;
CREATE TABLE IF NOT EXISTS `landing_services` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-stethoscope' COMMENT 'FontAwesome class or image path',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_services`
--

INSERT INTO `landing_services` (`service_id`, `name`, `description`, `icon_class`, `is_featured`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Consultation', 'Expert medical consultation for all general health concerns with our experienced doctors.', 'fas fa-stethoscope', 1, 1, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(2, 'Emergency Care', '24/7 emergency medical care for urgent and life-threatening conditions.', 'fas fa-ambulance', 1, 2, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(3, 'Pharmacy Services', 'Fully stocked pharmacy with prescription and over-the-counter medications.', 'fas fa-pills', 1, 3, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(4, 'Laboratory Services', 'Comprehensive diagnostic tests and laboratory analysis with quick turnaround.', 'fas fa-flask', 1, 4, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(5, 'Bed Management', 'Comfortable inpatient facilities for patients requiring extended care.', 'fas fa-bed', 0, 5, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(6, 'Health Education', 'Regular health awareness campaigns, seminars and preventive care programs.', 'fas fa-heart-pulse', 0, 6, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(7, 'Mental Health Support', 'Confidential counselling and mental health support services for students.', 'fas fa-brain', 0, 7, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(8, 'Vaccination Services', 'Immunisation programs and vaccination services for all eligible persons.', 'fas fa-syringe', 0, 8, 1, '2026-04-09 16:51:44', '2026-04-09 16:51:44'),
(9, 'General Consultation', 'Expert medical consultation for all general health concerns with our experienced doctors.', 'fas fa-stethoscope', 1, 1, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(10, 'Emergency Care', '24/7 emergency medical care for urgent and life-threatening conditions.', 'fas fa-ambulance', 1, 2, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(11, 'Pharmacy Services', 'Fully stocked pharmacy with prescription and over-the-counter medications.', 'fas fa-pills', 1, 3, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(12, 'Laboratory Services', 'Comprehensive diagnostic tests and laboratory analysis with quick turnaround.', 'fas fa-flask', 1, 4, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(13, 'Bed Management', 'Comfortable inpatient facilities for patients requiring extended care.', 'fas fa-bed', 0, 5, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(14, 'Health Education', 'Regular health awareness campaigns, seminars and preventive care programs.', 'fas fa-heart-pulse', 0, 6, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(15, 'Mental Health Support', 'Confidential counselling and mental health support services for students.', 'fas fa-brain', 0, 7, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52'),
(16, 'Vaccination Services', 'Immunisation programs and vaccination services for all eligible persons.', 'fas fa-syringe', 0, 8, 1, '2026-04-09 18:49:52', '2026-04-09 18:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `landing_staff`
--

DROP TABLE IF EXISTS `landing_staff`;
CREATE TABLE IF NOT EXISTS `landing_staff` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'FK to users.id ÔÇö nullable for manually entered staff',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. Senior Nurse, Head Pharmacist',
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  KEY `idx_department` (`department`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_statistics`
--

DROP TABLE IF EXISTS `landing_statistics`;
CREATE TABLE IF NOT EXISTS `landing_statistics` (
  `stat_id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. Patients Served, Doctors, Years of Service',
  `stat_value` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. 5000+, 15, 10+',
  `icon_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-chart-bar' COMMENT 'FontAwesome class',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stat_id`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_statistics`
--

INSERT INTO `landing_statistics` (`stat_id`, `label`, `stat_value`, `icon_class`, `display_order`, `is_active`, `updated_by`, `updated_at`) VALUES
(1, 'Patients Served', '5,000+', 'fas fa-users', 1, 1, NULL, '2026-04-09 16:51:44'),
(2, 'Qualified Doctors', '12+', 'fas fa-user-doctor', 2, 1, NULL, '2026-04-09 16:51:44'),
(3, 'Services Offered', '20+', 'fas fa-stethoscope', 3, 1, NULL, '2026-04-09 16:51:44'),
(4, 'Years of Service', '10+', 'fas fa-award', 4, 1, NULL, '2026-04-09 16:51:44'),
(5, 'Beds Available', '50', 'fas fa-bed', 5, 1, NULL, '2026-04-09 16:51:44'),
(6, 'Ambulances', '3', 'fas fa-truck-medical', 6, 1, NULL, '2026-04-09 16:51:44'),
(7, 'Patients Served', '5,000+', 'fas fa-users', 1, 1, NULL, '2026-04-09 18:49:52'),
(8, 'Qualified Doctors', '12+', 'fas fa-user-doctor', 2, 1, NULL, '2026-04-09 18:49:52'),
(9, 'Services Offered', '20+', 'fas fa-stethoscope', 3, 1, NULL, '2026-04-09 18:49:52'),
(10, 'Years of Service', '10+', 'fas fa-award', 4, 1, NULL, '2026-04-09 18:49:52'),
(11, 'Beds Available', '50', 'fas fa-bed', 5, 1, NULL, '2026-04-09 18:49:52'),
(12, 'Ambulances', '3', 'fas fa-truck-medical', 6, 1, NULL, '2026-04-09 18:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `landing_testimonials`
--

DROP TABLE IF EXISTS `landing_testimonials`;
CREATE TABLE IF NOT EXISTS `landing_testimonials` (
  `testimonial_id` int NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT 'Anonymous',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint NOT NULL DEFAULT '5' COMMENT '1 to 5 stars',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`),
  KEY `idx_is_approved` (`is_approved`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laundry_batches`
--

DROP TABLE IF EXISTS `laundry_batches`;
CREATE TABLE IF NOT EXISTS `laundry_batches` (
  `batch_id` int NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `requested_by` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ward/department',
  `batch_type` enum('bed linen','patient gown','staff uniform','theatre','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_count` int DEFAULT '0',
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `collection_status` enum('pending','collected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `washing_status` enum('pending','in progress','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `ironing_status` enum('pending','in progress','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `delivery_status` enum('pending','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `damaged_items_count` int DEFAULT '0',
  `contaminated_items_count` int DEFAULT '0',
  `collected_at` datetime DEFAULT NULL,
  `washing_started_at` datetime DEFAULT NULL,
  `washing_completed_at` datetime DEFAULT NULL,
  `ironing_completed_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  UNIQUE KEY `uk_batch_code` (`batch_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laundry_batches`
--

INSERT INTO `laundry_batches` (`batch_id`, `batch_code`, `assigned_to`, `requested_by`, `batch_type`, `item_count`, `weight_kg`, `collection_status`, `washing_status`, `ironing_status`, `delivery_status`, `damaged_items_count`, `contaminated_items_count`, `collected_at`, `washing_started_at`, `washing_completed_at`, `ironing_completed_at`, `delivered_at`, `notes`, `created_at`) VALUES
(1, 'LND-B001', 12, 'Nurse Station', 'bed linen', 25, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(2, 'LND-B002', 12, 'Operation Theatre', 'theatre', 15, NULL, 'pending', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(3, 'LND-B003', 12, 'Ward B', 'patient gown', 30, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(4, 'LND-B004', 12, 'Staff Quarters', 'staff uniform', 10, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(5, 'LND-B005', 12, 'Recovery Room', 'bed linen', 20, NULL, 'pending', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(6, 'LND-B006', 12, 'Ward A', 'patient gown', 20, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(7, 'LND-B007', 12, 'Emergency', 'theatre', 5, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(8, 'LND-B008', 12, 'Clinic Reception', 'other', 8, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(9, 'LND-B009', 12, 'Staff Quarters', 'staff uniform', 12, NULL, 'pending', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32'),
(10, 'LND-B010', 12, 'Kitchen', 'other', 15, NULL, 'collected', 'pending', 'pending', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:01:32');

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
  `item_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_quantity` int DEFAULT '0',
  `available_quantity` int DEFAULT '0',
  `in_wash_quantity` int DEFAULT '0',
  `damaged_quantity` int DEFAULT '0',
  `condemned_quantity` int DEFAULT '0',
  `reorder_level` int DEFAULT '50',
  `last_updated_by` int DEFAULT NULL COMMENT 'staff ID',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type` enum('Annual','Sick','Maternity','Paternity','Emergency','Unpaid','Other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int NOT NULL DEFAULT '1',
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL,
  `review_notes` text,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `legacy_payments`
--

DROP TABLE IF EXISTS `legacy_payments`;
CREATE TABLE IF NOT EXISTS `legacy_payments` (
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
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_success` (`success`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `username`, `ip_address`, `success`, `failure_reason`, `user_agent`, `attempted_at`, `user_id`) VALUES
(1, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-25 17:32:36', 26),
(2, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:08:15', 1),
(3, 'admin@rmu.edu', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:26:19', NULL),
(4, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:28:55', 1),
(5, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 12:29:26', 1),
(6, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 15:28:02', 1),
(7, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 16:42:55', 1),
(8, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 10:43:50', 1),
(9, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 11:05:22', 1),
(10, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:47:15', 28),
(11, 'MA', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:13', 29),
(12, 'AI', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:58', 23),
(13, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:07', 1),
(14, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:25', 1),
(15, 'GA', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:57', 17),
(16, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:52:58', 1),
(17, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:16:35', 1),
(18, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:17:24', 26),
(19, 'LJ', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:18:12', NULL),
(20, 'JJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:21:09', 21),
(21, 'EC', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:22:43', NULL),
(22, 'TF', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:23:10', 18),
(23, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:24:08', 28),
(24, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:37:39', 1),
(25, 'CE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 08:58:48', 14),
(26, 'JO', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:03:26', 16),
(27, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:36:28', 26),
(28, 'AD', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 05:49:25', 15),
(29, 'AD', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:24:19', 15),
(30, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:28:57', 1),
(31, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:14:54', NULL),
(32, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:14:58', NULL),
(33, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:15:03', NULL),
(34, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:18:16', 1),
(35, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 13:19:02', 1),
(36, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 08:16:54', NULL),
(37, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 08:29:01', NULL),
(38, 'harry-johnson.agyemang@rmu.edu.gh', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 08:29:04', NULL),
(39, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 08:29:26', 1),
(40, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:05:39', 1),
(41, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 13:27:29', 26),
(42, 'JF', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:05:04', NULL),
(43, 'FJ', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:05:21', 28),
(44, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 14:05:44', 28),
(45, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 15:01:50', 1),
(46, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:19:32', 26),
(47, 'Ahwenei', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 16:36:17', 35),
(48, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-10 23:50:22', 1),
(49, 'Lil', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:12:04', 36),
(50, 'Lil', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:12:27', 36),
(51, 'Lil', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:13:03', 36),
(52, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 00:14:23', 36),
(53, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:35:47', 36),
(54, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:37:39', 26),
(55, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-11 04:47:10', 36),
(56, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 04:33:34', 36),
(57, 'emmanuel.kofi91', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 08:09:03', 42),
(58, 'kwame.mensah.doc14', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 09:55:53', 37),
(59, 'kwame.mensah.doc14', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 12:25:08', 37),
(60, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:25:14', 1),
(61, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:26:42', 36),
(62, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:28:52', 20),
(63, 'Neils', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:30:04', 26),
(64, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:30:27', 26),
(65, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:36:55', 28),
(66, 'kwame.mensah.doc14', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:38:27', 37),
(67, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:40:49', 20),
(68, 'bright.amoah.lab26', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:43:05', 46),
(69, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:43:32', 1),
(70, 'Ahwenei', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:55:46', 35),
(71, 'kwame.mensah.doc14', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:25:18', 37),
(72, 'abena.asante.doc29', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:26:14', 38),
(73, 'efua.owusu.nurse30', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:27:05', 40),
(74, 'Jane', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 10:29:46', 101),
(75, 'Jane', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:54:44', 101),
(76, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 11:55:35', 36),
(77, 'Samuel', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:15:44', 307),
(78, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:30:41', 1),
(79, 'Lil', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:38:04', 36),
(80, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-15 12:42:54', 20),
(81, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:08:07', 26),
(82, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:38:37', 1),
(83, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 02:45:24', 26),
(84, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:02:48', 26),
(85, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:10:55', 26),
(86, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 04:46:38', 26),
(87, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:30:46', 1),
(88, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:55:57', 1),
(89, 'Jemima', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 08:58:13', NULL),
(90, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:16:24', 1),
(91, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:31:08', 1),
(92, 'Shurface', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:32:50', 312),
(93, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 05:31:42', 1),
(94, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:22:20', 1),
(95, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:22:40', 1),
(96, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:24:55', 28),
(97, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:48:18', 1),
(98, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:58:16', 1),
(99, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 07:59:47', 28),
(100, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 09:59:40', 28),
(101, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 15:55:28', 28),
(102, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 16:38:49', 28),
(103, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 21:19:29', 28),
(104, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 22:38:16', 28),
(105, 'FJ', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:02:53', 28),
(106, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 04:23:53', 20),
(107, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 05:33:37', 20),
(108, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:24:29', 20),
(109, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:54:24', 26),
(110, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 06:57:52', 1),
(111, 'Shurface', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:02:46', 312),
(112, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:20:13', 26),
(113, 'Shurface', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:21:25', 312),
(114, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 07:34:15', 20),
(115, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:20:21', 20),
(116, 'Neils', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:31:37', 26),
(117, 'Shurface', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:32:43', 312),
(118, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:37:02', 20),
(119, 'JE', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:15:03', 20),
(120, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:32:28', 1),
(121, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:35:26', 313),
(122, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:36:21', 313),
(123, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:41:04', 1),
(124, 'Chef Abbys', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:42:31', NULL),
(125, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 04:53:34', 313),
(126, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 05:50:35', 313),
(127, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 07:00:24', 313),
(128, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 07:57:44', 313),
(129, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 08:35:56', 1),
(130, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:14:31', 1),
(131, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 09:25:56', 314),
(132, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:02:39', 314),
(133, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:41:32', 1),
(134, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 10:47:50', 315),
(135, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 11:40:57', 315),
(136, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 12:33:43', 315),
(137, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 13:33:00', 315),
(138, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 16:20:25', 315),
(139, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-21 16:25:18', 314),
(140, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:19:23', 314),
(141, 'Lovelace', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:20:47', 1),
(142, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 14:21:09', 1),
(143, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:04:49', 1),
(144, 'Joseph', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:20:04', NULL),
(145, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:20:39', 313),
(146, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:24:19', 315),
(147, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:25:22', 314),
(148, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:28:21', 314),
(149, 'Lovelce', '::1', 0, 'user_not_found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:31:47', NULL),
(150, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:32:04', 1),
(151, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:45:44', 1),
(152, 'Face', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 09:34:49', 316),
(153, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 09:39:45', 1),
(154, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 11:33:46', 1),
(155, 'Face', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 08:42:12', 316),
(156, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 07:30:09', 1),
(157, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 07:40:50', 314),
(158, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 08:36:49', 1),
(159, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 08:39:11', 314),
(160, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 11:09:24', 313),
(161, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-11 11:10:34', 314),
(162, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 08:50:43', 1),
(163, 'OTFXLIMKID', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 08:58:23', 313),
(164, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 18:40:50', 1),
(165, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 19:16:32', 314),
(166, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:03:21', 1),
(167, 'Biggie', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:07:30', 314),
(168, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-13 20:12:28', 1),
(169, 'Gifty', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:29:05', 315),
(170, 'Face', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:30:06', 316),
(171, 'Gifty', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:39:24', 315),
(172, 'Face', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 09:40:13', 316),
(173, 'Ahwenei', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:13:07', 35),
(174, 'Junior', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:19:39', 201),
(175, 'Junior', '::1', 0, 'wrong_password', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:19:56', 201),
(176, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:28:12', 1),
(177, 'Junior', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-14 10:29:39', 317);

-- --------------------------------------------------------

--
-- Table structure for table `login_security_config`
--

DROP TABLE IF EXISTS `login_security_config`;
CREATE TABLE IF NOT EXISTS `login_security_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `max_attempts` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `lockout_minutes` smallint UNSIGNED NOT NULL DEFAULT '15',
  `ip_max_attempts` smallint UNSIGNED NOT NULL DEFAULT '20',
  `ip_window_minutes` smallint UNSIGNED NOT NULL DEFAULT '60',
  `session_timeout` smallint UNSIGNED NOT NULL DEFAULT '30',
  `remember_me_days` smallint UNSIGNED NOT NULL DEFAULT '30',
  `otp_expiry_minutes` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `reset_expiry_minutes` smallint UNSIGNED NOT NULL DEFAULT '30',
  `enforce_2fa_roles` varchar(500) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session_timeout_minutes` int DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_security_config`
--

INSERT INTO `login_security_config` (`id`, `max_attempts`, `lockout_minutes`, `ip_max_attempts`, `ip_window_minutes`, `session_timeout`, `remember_me_days`, `otp_expiry_minutes`, `reset_expiry_minutes`, `enforce_2fa_roles`, `updated_at`, `session_timeout_minutes`) VALUES
(1, 5, 15, 20, 60, 30, 30, 5, 30, '', '2026-03-25 16:55:03', 30);

-- --------------------------------------------------------

--
-- Table structure for table `logout_config`
--

DROP TABLE IF EXISTS `logout_config`;
CREATE TABLE IF NOT EXISTS `logout_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `countdown_duration` int DEFAULT '3',
  `confirm_dialog_enabled` tinyint(1) DEFAULT '1',
  `show_health_message` tinyint(1) DEFAULT '1',
  `redirect_url` varchar(255) DEFAULT '/RMU-Medical-Management-System/php/index.php',
  `session_cleanup` tinyint(1) DEFAULT '1',
  `force_logout_on_password_change` tinyint(1) DEFAULT '1',
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmation_dialog_enabled` tinyint(1) DEFAULT '1',
  `countdown_duration_seconds` int DEFAULT '3',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logout_config`
--

INSERT INTO `logout_config` (`id`, `countdown_duration`, `confirm_dialog_enabled`, `show_health_message`, `redirect_url`, `session_cleanup`, `force_logout_on_password_change`, `updated_by`, `updated_at`, `confirmation_dialog_enabled`, `countdown_duration_seconds`) VALUES
(1, 3, 1, 1, '/RMU-Medical-Management-System/php/index.php', 1, 1, NULL, '2026-03-26 11:49:26', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `logout_logs`
--

DROP TABLE IF EXISTS `logout_logs`;
CREATE TABLE IF NOT EXISTS `logout_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `logout_type` enum('manual','timeout','forced','admin forced','security') DEFAULT 'manual',
  `logout_confirmed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `countdown_duration` int DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `dashboard_origin` varchar(100) DEFAULT NULL,
  `health_message_shown` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `dashboard_logged_out_from` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logout_logs`
--

INSERT INTO `logout_logs` (`id`, `user_id`, `role`, `session_id`, `logout_type`, `logout_confirmed_at`, `countdown_duration`, `ip_address`, `device_info`, `browser`, `dashboard_origin`, `health_message_shown`, `created_at`, `dashboard_logged_out_from`) VALUES
(1, 1, 'admin', 'nbtcd71liagcv37gs026k4h4i1', 'manual', '2026-03-26 12:24:25', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '_sidebar.php', 'Wash your hands regularly to prevent the spread of infections.', '2026-03-26 12:24:25', NULL),
(2, 1, 'admin', 'us55vjbhk40rp51qoqpio1lrm8', 'manual', '2026-03-26 12:30:37', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '_sidebar.php', 'Wash your hands regularly to prevent the spread of infections.', '2026-03-26 12:30:37', NULL),
(3, 1, 'admin', '2bm5ure069agma7208k0omoakn', 'timeout', '2026-03-26 16:31:42', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-03-26 16:31:42', NULL),
(4, 1, 'admin', 's5c8s7dtv11rvlqt9jgtls6143', 'timeout', '2026-03-27 12:25:57', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-03-27 12:25:57', NULL),
(5, 15, 'staff', 'uco91iohbmahft6oh5lma27p1p', 'timeout', '2026-04-08 01:23:52', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-04-08 01:23:52', NULL),
(6, 1, 'admin', 's6vq5s6dhkb383pc746njb5u9n', 'manual', '2026-04-09 13:06:10', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 13:06:10', 'home.php'),
(7, 1, 'admin', 's6vq5s6dhkb383pc746njb5u9n', 'manual', '2026-04-09 13:06:44', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 13:06:44', 'home.php'),
(8, 1, 'admin', 's6vq5s6dhkb383pc746njb5u9n', 'manual', '2026-04-09 13:19:49', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 13:19:49', 'home.php'),
(9, 1, 'admin', 's6vq5s6dhkb383pc746njb5u9n', 'manual', '2026-04-09 13:26:38', 3, '::1', 'Windows', 'Chrome', NULL, 'Your session has been securely terminated.', '2026-04-09 13:26:38', 'home.php'),
(10, 26, 'nurse', 'i2c3h93bonhf1m6e7c3hpkfv2q', 'manual', '2026-04-09 13:28:06', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 13:28:06', 'nurse_dashboard.php'),
(11, 28, 'lab_technician', 'vqmkr5ivom09n3q89tli4qbc7d', 'manual', '2026-04-09 14:21:46', 3, '::1', 'Windows', 'Chrome', NULL, 'Rest your eyes! Microscope work is demanding.', '2026-04-09 14:21:46', 'lab_dashboard.php'),
(12, 28, 'lab_technician', '1tmiete18jc6iqelum499dqpkc', 'manual', '2026-04-09 14:22:17', 3, '::1', 'Windows', 'Chrome', NULL, 'Health is a state of body. Wellness is a state of being.', '2026-04-09 14:22:17', 'lab_dashboard.php'),
(13, 1, 'admin', 'd7j1avmi6j2eqoj84hh6otb5gk', 'manual', '2026-04-09 16:19:10', 3, '::1', 'Windows', 'Chrome', NULL, 'Secure all sensitive data screens before leaving your desk.', '2026-04-09 16:19:10', 'settings_health_messages.php'),
(14, 26, 'nurse', 'pjrqpe863q76rdpe5h5kq9a8gt', 'manual', '2026-04-09 16:22:46', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 16:22:46', 'nurse_dashboard.php'),
(15, 35, 'finance_officer', 'efhma0uthj0br1fmndmacd8ub7', 'manual', '2026-04-09 18:51:30', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-09 18:51:30', 'finance_dashboard.php'),
(16, 1, 'admin', 'e37jie9rbvfham81s1plrk72nb', 'manual', '2026-04-10 23:59:57', 3, '::1', 'Windows', 'Chrome', NULL, 'Your organizational skills keep the entire facility running smoothly.', '2026-04-10 23:59:57', 'admin_landing_featured.php'),
(17, 36, 'patient', 'qr22p4ejgf4kqj5dm7t84rlaqi', 'manual', '2026-04-11 00:14:58', 3, '::1', 'Windows', 'Chrome', NULL, 'Remember to take your prescribed medications on time.', '2026-04-11 00:14:58', 'patient_dashboard.php'),
(18, 36, 'patient', '85uhdto6jc2bfsh031rdl0vkkg', 'manual', '2026-04-11 04:36:53', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-11 04:36:53', 'patient_dashboard.php'),
(19, 26, 'nurse', 'd7bop5nm2dmrkpaluudleruq4q', 'manual', '2026-04-11 04:38:43', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-11 04:38:43', 'nurse_dashboard.php'),
(20, 36, 'patient', 'qomj0eivi5jfg8khsbb3624vdb', 'timeout', '2026-04-11 21:26:54', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-04-11 21:26:54', NULL),
(21, 36, 'patient', 'q5t2qrn1rell6n571nv8s7gq70', 'manual', '2026-04-12 10:26:25', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-12 10:26:25', 'patient_dashboard.php'),
(22, 42, 'patient', 'adk23sh9a0rq9s2jbl2d8mqevg', 'manual', '2026-04-13 09:53:29', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 09:53:29', 'patient_dashboard.php'),
(23, 37, 'doctor', 'uvuojjpqof5oe60od01drksm11', 'timeout', '2026-04-13 12:24:00', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-04-13 12:24:00', NULL),
(24, 37, 'doctor', 'uik9hua4qgv9jr1fht8i9niq5b', 'timeout', '2026-04-13 15:22:01', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-04-13 15:22:01', NULL),
(25, 1, 'admin', '6hijllfcm13rgc0l1otsra8972', 'manual', '2026-04-13 15:26:21', 3, '::1', 'Windows', 'Chrome', NULL, 'Thank you for maintaining the foundation of our healthcare delivery.', '2026-04-13 15:26:21', 'home.php'),
(26, 36, 'patient', '35a5a3451aptcbehb08lfid616', 'manual', '2026-04-13 15:27:57', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 15:27:57', 'patient_dashboard.php'),
(27, 20, 'doctor', '82g43atu0r6dfvnpe9h7lp2uc2', 'manual', '2026-04-13 15:29:32', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 15:29:32', 'doctor_dashboard.php'),
(28, 26, 'nurse', '42irv9vjvbqjjml21llpds0n64', 'manual', '2026-04-13 15:30:58', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 15:30:58', 'nurse_dashboard.php'),
(29, 28, 'lab_technician', '3apc7i5hk4jvuhpo5dh9brkmss', 'manual', '2026-04-13 15:37:29', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 15:37:29', 'lab_dashboard.php'),
(30, 37, 'doctor', 'n4ldlo7e7sg9l3kq9ld0l7gou8', 'manual', '2026-04-13 15:38:58', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-13 15:38:58', 'doctor_dashboard.php'),
(31, 20, 'doctor', '99qjvt68o526qsqd087a7ub2tc', 'manual', '2026-04-13 15:41:21', 3, '::1', 'Windows', 'Chrome', NULL, 'Remember to stay hydrated — drink at least 8 glasses of water daily.', '2026-04-13 15:41:21', 'doctor_dashboard.php'),
(32, 1, 'admin', '3c0uae2r760kh8quotb8gkhv17', 'manual', '2026-04-13 15:44:34', 3, '::1', 'Windows', 'Chrome', NULL, 'Remember to stay hydrated — drink at least 8 glasses of water daily.', '2026-04-13 15:44:34', 'staff_approvals.php'),
(33, 35, 'finance_officer', 'n3uqi0u41ks9gcrb9jsrvebo3r', 'manual', '2026-04-13 15:58:38', 3, '::1', 'Windows', 'Chrome', NULL, 'Always remember to lock your screen when stepping away.', '2026-04-13 15:58:38', 'finance_dashboard.php'),
(34, 37, 'doctor', '7jdmnp938kirpjnjdtldqfe2gc', 'manual', '2026-04-15 10:25:38', 3, '::1', 'Windows', 'Chrome', NULL, 'Stay updated with the latest medical protocols.', '2026-04-15 10:25:38', 'doctor_dashboard.php'),
(35, 38, 'doctor', '6fs39tvkpp8nrga9n6ko6hf1lv', 'manual', '2026-04-15 10:26:26', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-15 10:26:26', 'doctor_dashboard.php'),
(36, 40, 'nurse', 'go98ac1lspgkik23p1nvtov4ha', 'manual', '2026-04-15 10:28:46', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-15 10:28:46', 'nurse_dashboard.php'),
(37, 101, 'patient', '1tci9vg1t4l3cn45e0e8dtpl6j', 'manual', '2026-04-15 11:55:12', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-15 11:55:12', 'patient_dashboard.php'),
(38, 36, 'patient', '53oom503usj4m1al0kv4qsg3jk', 'manual', '2026-04-15 11:56:12', 3, '::1', 'Windows', 'Chrome', NULL, 'Follow your doctor\'s advice strictly for the fastest recovery.', '2026-04-15 11:56:12', 'patient_dashboard.php'),
(39, 307, 'patient', '9sqv724n1n7t6n5ulaljn9do70', 'manual', '2026-04-15 12:16:33', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-15 12:16:33', 'patient_dashboard.php'),
(40, 1, 'admin', 'fru8hq6bh2l1rr7jcsi56ggcg3', 'manual', '2026-04-15 12:36:30', 3, '::1', 'Windows', 'Chrome', NULL, 'Goodbye! See you next time.', '2026-04-15 12:36:30', 'settings_health_messages.php'),
(41, 36, 'patient', '97k9io1pv3q14g9c4si3i1jdj7', 'manual', '2026-04-15 12:42:30', 3, '::1', 'Windows', 'Chrome', NULL, 'Follow your doctor\'s advice strictly for the fastest recovery.', '2026-04-15 12:42:30', 'patient_dashboard.php'),
(42, 20, 'doctor', 'j91112auf33v27penppskau0bv', 'manual', '2026-04-15 12:47:42', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-15 12:47:42', 'doctor_dashboard.php'),
(43, 26, 'nurse', 'frar3ne5fc7rt0ekckbeplu6pm', 'manual', '2026-04-17 02:38:07', 3, '::1', 'Windows', 'Chrome', NULL, 'Disconnecting securely from the hospital network.', '2026-04-17 02:38:07', 'nurse_dashboard.php'),
(44, 1, 'admin', 'talggfr0csv1o6rajs42nijqhd', 'manual', '2026-04-17 02:44:54', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-17 02:44:54', 'staff_approvals.php'),
(45, 26, 'nurse', '0pnrdomksvbdvr65toa80k1u9s', 'manual', '2026-04-17 04:07:51', 3, '::1', 'Windows', 'Chrome', NULL, 'Wash hands thoroughly between every patient interaction.', '2026-04-17 04:07:51', 'nurse_dashboard.php'),
(46, 26, 'nurse', 'g0951m23h6phcu716vltfi3kog', 'manual', '2026-04-17 04:46:04', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-17 04:46:04', 'nurse_dashboard.php'),
(47, 26, 'nurse', '6o1e0ad15gdvh7kgfk7f50c0l1', 'manual', '2026-04-17 05:22:36', 3, '::1', 'Windows', 'Chrome', NULL, 'Health is a state of body. Wellness is a state of being.', '2026-04-17 05:22:36', 'nurse_dashboard.php'),
(48, 1, 'admin', 'vi9cfavtrkancrolob024j3t7i', 'manual', '2026-04-17 08:34:08', 3, '::1', 'Windows', 'Chrome', NULL, 'The system will now securely purge your local data.', '2026-04-17 08:34:08', 'home.php'),
(49, 1, 'admin', 'eio2eoui1jmrufjqrd45j7lcoq', 'manual', '2026-04-17 08:57:42', 3, '::1', 'Windows', 'Chrome', NULL, 'Check system backup logs to ensure data integrity.', '2026-04-17 08:57:42', 'staff_approvals.php'),
(50, 1, 'admin', 'f4i0irq5e28qo6v0cddglgll4f', 'manual', '2026-04-17 09:16:46', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-17 09:16:46', 'staff_approvals.php'),
(51, 1, 'admin', '8utui2g628j8gpp26qu5oq0mdq', 'manual', '2026-04-17 09:32:23', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-17 09:32:23', 'staff_approvals.php'),
(52, 312, 'pharmacist', 'bgv08fg1jltpj6cdokpg7v41ij', 'manual', '2026-04-17 11:50:18', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-17 11:50:18', 'pharmacy_dashboard.php'),
(53, 1, 'admin', '84m40rbnpj5c4aucdeeq3vuhnj', 'timeout', '2026-04-19 07:21:55', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-19 07:21:55', NULL),
(54, 1, 'admin', 'rlvkjd6l9l7srb2nbrk5ske8c0', 'manual', '2026-04-19 07:24:28', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-19 07:24:28', 'settings_health_messages.php'),
(55, 28, 'lab_technician', 'gd7evqmgvg43vu2gf5oig5d1i8', 'manual', '2026-04-19 07:47:49', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-19 07:47:49', 'lab_dashboard.php'),
(56, 1, 'admin', 'jiejc1e6rsmhpie62vsdodpukk', 'manual', '2026-04-19 07:57:21', 3, '::1', 'Windows', 'Chrome', NULL, 'Behind every great hospital system is a great administrator. Thank you.', '2026-04-19 07:57:21', 'settings_v2.php'),
(57, 1, 'admin', 'mbvq1bsmvt1u6ojup9g3b7n306', 'manual', '2026-04-19 07:58:42', 3, '::1', 'Windows', 'Chrome', NULL, 'Goodbye! See you next time.', '2026-04-19 07:58:42', 'home.php'),
(58, 28, 'lab_technician', '3830qjaf5p4sb5nl75da8hplpi', 'manual', '2026-04-20 04:23:10', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 04:23:10', 'lab_dashboard.php'),
(59, 20, 'doctor', '7s3mmsfjrb8c9h2f6m5uqpmaog', 'timeout', '2026-04-20 05:33:00', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-20 05:33:00', NULL),
(60, 20, 'doctor', 'pe9t3d0cb908l4kmpvlabdu5l4', 'timeout', '2026-04-20 06:23:00', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-20 06:23:00', NULL),
(61, 20, 'doctor', 'g2rlt1hcv8nm04ngrkfdgr4820', 'manual', '2026-04-20 06:53:56', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 06:53:56', 'doctor_dashboard.php'),
(62, 26, 'nurse', 'gt6f2ugi55s9d937h5tisskg1u', 'manual', '2026-04-20 06:56:03', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 06:56:03', 'nurse_dashboard.php'),
(63, 1, 'admin', 'qnbi2pbur6rpjdmsjm4evqksq5', 'manual', '2026-04-20 07:02:10', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 07:02:10', 'staff_approvals.php'),
(64, 312, 'pharmacist', 'id2i3il1704i7mhrpnljhp2mtf', 'manual', '2026-04-20 07:19:31', 3, '::1', 'Windows', 'Chrome', NULL, 'The system will now securely purge your local data.', '2026-04-20 07:19:31', 'pharmacy_dashboard.php'),
(65, 26, 'nurse', '61he39u4eovkecqs0uo50tnntc', 'manual', '2026-04-20 07:20:55', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 07:20:55', 'nurse_dashboard.php'),
(66, 312, 'pharmacist', 'pr7kq1oi8lp0tfj7vnjc5idvrf', 'manual', '2026-04-20 07:28:17', 3, '::1', 'Windows', 'Chrome', NULL, 'Verify all pending prescription labels have been printed.', '2026-04-20 07:28:17', 'pharmacy_dashboard.php'),
(67, 20, 'doctor', 'jcb5gvt8nal2a9bcegttbojh38', 'timeout', '2026-04-20 11:19:16', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-20 11:19:16', NULL),
(68, 20, 'doctor', 'j200a10jjnu8dfli5hlgh91ive', 'manual', '2026-04-20 11:31:09', 3, '::1', 'Windows', 'Chrome', NULL, 'Take 5 minutes to stretch between long patient consultations.', '2026-04-20 11:31:09', 'doctor_dashboard.php'),
(69, 26, 'nurse', 'tjh4kr3bd3rnjk4kh0meg291v9', 'manual', '2026-04-20 11:32:06', 3, '::1', 'Windows', 'Chrome', NULL, 'You are an essential pillar of patient recovery. Thank you.', '2026-04-20 11:32:06', 'nurse_dashboard.php'),
(70, 312, 'pharmacist', 'l1bcud48rsbdf0nrga146q8hjo', 'manual', '2026-04-20 11:36:29', 3, '::1', 'Windows', 'Chrome', NULL, 'Ensure the controlled substance cabinet is double-locked.', '2026-04-20 11:36:29', 'pharmacy_dashboard.php'),
(71, 20, 'doctor', '9b67kako5hg962jsu3fo2660ha', 'timeout', '2026-04-20 12:14:39', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-20 12:14:39', NULL),
(72, 20, 'doctor', 'u32mo445u5nntdv1tb9jdnk19a', 'manual', '2026-04-20 12:15:50', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-20 12:15:50', 'doctor_dashboard.php'),
(73, 1, 'admin', '993868t1or4kntdhs1o9l6glo3', 'manual', '2026-04-21 04:33:54', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 04:33:54', 'staff.php'),
(74, 1, 'admin', 'jbohelbllnesg83jp6loljddmc', 'manual', '2026-04-21 04:42:02', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 04:42:02', 'staff.php'),
(75, 313, 'maintenance', '54klku91msqfdi2o4mbrblcu1l', 'timeout', '2026-04-21 05:49:59', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 05:49:59', NULL),
(76, 313, 'maintenance', 'p19sksvo9guv3e4hqhr7727sj3', 'timeout', '2026-04-21 06:59:57', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 06:59:57', NULL),
(77, 313, 'maintenance', 'iquceki3gf65pjtg5lei4eplaf', 'timeout', '2026-04-21 07:57:20', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 07:57:20', NULL),
(78, 313, 'maintenance', 'gfov8dn5a229g3pvumer4f4g98', 'manual', '2026-04-21 08:34:55', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 08:34:55', 'staff_dashboard.php'),
(79, 1, 'admin', 'i8hj7ktr282hpn5cmaaglvacla', 'manual', '2026-04-21 08:46:06', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 08:46:06', 'staff.php'),
(80, 1, 'admin', '5br3qbt8l73fqi42f39o8k4ime', 'manual', '2026-04-21 09:25:32', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 09:25:32', 'home.php'),
(81, 314, 'security', 'eitr9ibj0mi2oqs23uen9cg5j7', 'manual', '2026-04-21 09:33:25', 3, '::1', 'Windows', 'Chrome', NULL, 'Thank you for using the RMU Medical Sickbay System.', '2026-04-21 09:33:25', 'staff_dashboard.php'),
(82, 314, 'security', 'hna8b3cpu5m5luagk4iunpqfjh', 'manual', '2026-04-21 10:05:41', 3, '::1', 'Windows', 'Chrome', NULL, 'Health is a state of body. Wellness is a state of being.', '2026-04-21 10:05:41', 'staff_dashboard.php'),
(83, 1, 'admin', 'r404rojflj1ue6f44mf15ogkv3', 'manual', '2026-04-21 10:45:30', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 10:45:30', 'staff_approvals.php'),
(84, 315, 'cleaner', 'of48kac08ldrotu35kb4iph1fd', 'timeout', '2026-04-21 11:40:37', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 11:40:37', NULL),
(85, 315, 'cleaner', '43650ovqhbmq56qrhchv7k3vkc', 'timeout', '2026-04-21 12:33:17', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 12:33:17', NULL),
(86, 315, 'cleaner', '949tin5spvu0u93fkklbicsa65', 'timeout', '2026-04-21 13:32:17', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 13:32:17', NULL),
(87, 315, 'cleaner', '3redb1rdkj2a8akg5b4231ipon', 'timeout', '2026-04-21 14:33:26', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 14:33:26', NULL),
(88, 315, 'cleaner', 'vvmddcjbt2e82c43p898h40131', 'manual', '2026-04-21 16:24:53', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-21 16:24:53', 'staff_dashboard.php'),
(89, 314, 'security', '44tsamj55ri62h5c5uc3ab6qo2', 'timeout', '2026-04-21 20:21:40', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-04-21 20:21:40', NULL),
(90, 314, 'security', '2ndrm5m8jqunb0kqm70rtbdmmn', 'manual', '2026-04-22 14:20:11', 3, '::1', 'Windows', 'Chrome', NULL, 'Health is a state of body. Wellness is a state of being.', '2026-04-22 14:20:11', 'staff_dashboard.php'),
(91, 1, 'admin', 'pqgun9hrf8dr7th579f3n66iov', 'manual', '2026-04-22 14:24:11', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-04-22 14:24:11', 'home.php'),
(92, 1, 'admin', '0tmh30olorjdsvmt6gj7ot284p', 'manual', '2026-05-05 06:19:12', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-05 06:19:12', 'home.php'),
(93, 313, 'maintenance', 'ukraduaj2dsa5ot5mhehmjea18', 'manual', '2026-05-05 06:23:56', 3, '::1', 'Windows', 'Chrome', NULL, 'Thank you for using the RMU Medical Sickbay System.', '2026-05-05 06:23:56', 'staff_dashboard.php'),
(94, 315, 'cleaner', 'haf0pjnnri9bck50j2meuqmu46', 'manual', '2026-05-05 06:24:40', 3, '::1', 'Windows', 'Chrome', NULL, 'Goodbye! See you next time.', '2026-05-05 06:24:40', 'staff_dashboard.php'),
(95, 314, 'security', 'ndtk2jjh023vos373s5hg12ms4', 'manual', '2026-05-05 06:25:48', 3, '::1', 'Windows', 'Chrome', NULL, 'Goodbye! See you next time.', '2026-05-05 06:25:48', 'staff_dashboard.php'),
(96, 314, 'security', '0psamfo9na2os4m2icjrsf4bdv', 'manual', '2026-05-05 06:28:27', 3, '::1', 'Windows', 'Chrome', NULL, 'Remember to log out securely when leaving your workstation.', '2026-05-05 06:28:27', 'staff_dashboard.php'),
(97, 1, 'admin', '0l16qgcrpjejnu3n6bmfqkg3a1', 'manual', '2026-05-05 06:34:04', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-05 06:34:04', 'staff.php'),
(98, 1, 'admin', 's5b74lmlke28hb0cb4kjjgmd6m', 'timeout', '2026-05-05 09:11:32', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-05-05 09:11:32', NULL),
(99, 316, 'ambulance_driver', '6ujf2960ne62ii4ho2k6si3gd5', 'manual', '2026-05-05 09:35:31', 3, '::1', 'Windows', 'Chrome', NULL, 'Remember to log out securely when leaving your workstation.', '2026-05-05 09:35:31', 'staff_dashboard.php'),
(100, 1, 'admin', 's3u45v3uvalvqdqnud8nmtvur6', 'timeout', '2026-05-05 11:33:26', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-05-05 11:33:26', NULL),
(101, 1, 'admin', 'dt4n4scefa3dc2pvt9t3dhdoml', 'manual', '2026-05-05 12:04:48', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-05 12:04:48', 'facility_cleaning.php'),
(102, 316, 'ambulance_driver', 'gs0njmfdi774ojaui786vfvkd6', 'manual', '2026-05-08 08:44:24', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-08 08:44:24', 'staff_dashboard.php'),
(103, 1, 'admin', 'p49kghfcc8imn0k9c65a2aan8j', 'manual', '2026-05-11 07:40:24', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-11 07:40:24', 'home.php'),
(104, 1, 'admin', 'ot8jsed41ik0do65a2vbdqrhho', 'manual', '2026-05-11 08:38:27', 3, '::1', 'Windows', 'Chrome', NULL, 'Wash your hands regularly to prevent the spread of infections.', '2026-05-11 08:38:27', 'home.php'),
(105, 314, 'security', 'qtd7g7pgeufm5qdrgv9bsjh1rf', 'timeout', '2026-05-11 11:09:01', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-05-11 11:09:01', NULL),
(106, 313, 'maintenance', 'iq6oe7m0gieepaqh0pc3uls1k2', 'manual', '2026-05-11 11:09:46', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-11 11:09:46', 'staff_dashboard.php'),
(107, 314, 'security', '49kqbj4h4bd4plk3r2ln9jfbk0', 'timeout', '2026-05-13 03:47:31', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-05-13 03:47:31', NULL),
(108, 1, 'admin', 'c6eht1v1lpp2v5dgmpp4bmfals', 'manual', '2026-05-13 08:57:55', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 08:57:55', 'home.php'),
(109, 313, 'maintenance', 'at9t12n40a8ivksfaje311clud', 'timeout', '2026-05-13 18:32:04', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'system_interceptor', NULL, '2026-05-13 18:32:04', NULL),
(110, 1, 'admin', '452nmt33ru4ha193hbm6a0ttat', 'manual', '2026-05-13 19:16:09', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 19:16:09', 'home.php'),
(111, 314, 'security', 'kkkfpmrhtpf36olnvoieuj78o4', 'manual', '2026-05-13 20:02:59', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 20:02:59', 'staff_dashboard.php'),
(112, 1, 'admin', '8vook35ha8ddsu5gacggabf89l', 'manual', '2026-05-13 20:06:51', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 20:06:51', 'home.php'),
(113, 314, 'security', 'oba3s7sol1hfdvv63ecso77csf', 'manual', '2026-05-13 20:11:56', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 20:11:56', 'staff_dashboard.php'),
(114, 1, 'admin', 'b64rj438medld97mp90ojf6gh8', 'manual', '2026-05-13 20:13:42', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-13 20:13:42', 'home.php'),
(115, 316, 'ambulance_driver', '1e8fs35bldd9f8noj4t8885qro', 'manual', '2026-05-14 09:39:01', 3, '::1', 'Windows', 'Chrome', NULL, 'Your session has been securely terminated.', '2026-05-14 09:39:01', 'staff_dashboard.php'),
(116, 315, 'cleaner', 'e9iedv9aet2r6o117vdk5u3qcs', 'manual', '2026-05-14 09:39:48', 3, '::1', 'Windows', 'Chrome', NULL, 'Thank you for using the RMU Medical Sickbay System.', '2026-05-14 09:39:48', 'staff_dashboard.php'),
(117, 316, 'ambulance_driver', '6kt2hpr911c3sortm05d00uhbf', 'manual', '2026-05-14 10:12:43', 3, '::1', 'Windows', 'Chrome', NULL, 'Your session has been securely terminated.', '2026-05-14 10:12:43', 'staff_dashboard.php'),
(118, 35, 'finance_officer', 'v8ugjmdhcs58km4ffvtv8rovv6', 'manual', '2026-05-14 10:15:58', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-14 10:15:58', 'finance_dashboard.php'),
(119, 1, 'admin', 'pkhcaq7shebq9kua2hlmkj2skg', 'manual', '2026-05-14 10:29:08', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-14 10:29:08', 'home.php'),
(120, 317, 'finance_manager', '31sn0se0673q0uap79mjqm03o5', 'manual', '2026-05-14 10:30:31', 3, '::1', 'Windows', 'Chrome', NULL, 'None', '2026-05-14 10:30:31', 'finance_dashboard.php');

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_medicines`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `low_stock_medicines`;
CREATE TABLE IF NOT EXISTS `low_stock_medicines` (
`category` varchar(100)
,`medicine_id` varchar(50)
,`medicine_name` varchar(200)
,`reorder_level` int
,`total_stock` decimal(32,0)
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
  `reported_by` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'user ID and role',
  `assigned_to` int DEFAULT NULL COMMENT 'staff ID nullable',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_or_area` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_category` enum('electrical','plumbing','structural','equipment','furniture','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('reported','assigned','in progress','on hold','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'reported',
  `images_path` json DEFAULT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `assigned_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completion_images_path` json DEFAULT NULL,
  `admin_verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`request_id`, `reported_by`, `assigned_to`, `location`, `equipment_or_area`, `issue_description`, `issue_category`, `priority`, `status`, `images_path`, `reported_at`, `assigned_at`, `started_at`, `completed_at`, `completion_notes`, `completion_images_path`, `admin_verified`) VALUES
(1, 'Admin Nurse', 17, 'Ward A - Room 3', 'AC Unit', 'AC making loud rattling noise', 'equipment', 'medium', 'assigned', NULL, '2026-04-14 01:01:55', '2026-04-21 08:02:06', NULL, NULL, NULL, NULL, 0),
(2, 'Staff Member', NULL, 'Laboratory', 'Lights', 'Two ceiling panels flickering', 'electrical', 'low', 'assigned', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(3, 'Receptionist', NULL, 'OPD Hall', 'Front Door', 'Door hinge loose and squeaky', 'structural', 'low', 'in progress', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(4, 'Lab Tech', NULL, 'Blood Bank', 'Refrigerator', 'Temperature rising above threshold', 'equipment', 'urgent', 'completed', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(5, 'Kitchen Staff', NULL, 'Mess Hall', 'Water Tap', 'Tap leaking continuously', 'plumbing', 'medium', 'on hold', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(6, 'Security', NULL, 'Main Entrance', 'Security Camera 2', 'No video feed from north-west view', 'electrical', 'high', 'reported', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(7, 'Pharmacist', NULL, 'Storage Room', 'Shelving Unit', 'Top shelf sagging under weight', 'furniture', 'medium', 'reported', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(8, 'Doctor', NULL, 'Consultation Rm 1', 'Chair', 'Adjustable lever broken', 'furniture', 'low', 'assigned', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(9, 'Laundry', NULL, 'Washroom', 'Drainage', 'Water draining very slowly', 'plumbing', 'high', 'in progress', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0),
(10, 'Admin', NULL, 'Records Office', 'Server Fan', 'Server cabinet overheating', 'equipment', 'urgent', 'completed', NULL, '2026-04-14 01:01:55', NULL, NULL, NULL, NULL, NULL, 0);

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
  `nurse_id` int DEFAULT NULL COMMENT 'nurses.id - nurse who assisted or recorded vitals',
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_visit_date` (`visit_date`),
  KEY `idx_mr_patient_date` (`patient_id`,`visit_date`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `record_id`, `patient_id`, `doctor_id`, `visit_date`, `diagnosis`, `symptoms`, `treatment`, `treatment_plan`, `attachments`, `severity`, `patient_visible`, `vital_signs`, `lab_results`, `notes`, `follow_up_required`, `follow_up_date`, `created_at`, `updated_at`, `nurse_id`) VALUES
(1, 'REC-60990', 5, 4, '2026-04-09', 'Anemia', 'Patient presents with Anemia symptoms.', 'Prescribed Omeprazole 5mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"115/75\", \"spo2\": 95, \"temp\": \"37.8\", \"pulse\": 78}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-07', '2026-01-16 22:41:02', '2026-04-12 10:22:48', NULL),
(2, 'REC-33285', 5, 4, '2025-11-06', 'Hypertension', 'Patient presents with Hypertension symptoms.', 'Prescribed Omeprazole 250mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Mild', 1, '{\"bp\": \"119/81\", \"spo2\": 98, \"temp\": \"36.9\", \"pulse\": 83}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-10', '2025-11-14 12:08:55', '2026-04-12 10:22:48', NULL),
(3, 'REC-63757', 5, 4, '2025-11-22', 'Malaria', 'Patient presents with Malaria symptoms.', 'Prescribed Paracetamol 50mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"145/78\", \"spo2\": 100, \"temp\": \"36.8\", \"pulse\": 83}', NULL, 'Patient responded well to initial treatment.', 1, '2026-06-03', '2026-02-08 02:57:47', '2026-04-12 10:22:48', NULL),
(4, 'REC-28661', 5, 4, '2026-04-07', 'Asthma', 'Patient presents with Asthma symptoms.', 'Prescribed Paracetamol 200mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"110/85\", \"spo2\": 98, \"temp\": \"36.6\", \"pulse\": 74}', NULL, 'Patient responded well to initial treatment.', 1, '2026-04-22', '2025-11-27 01:38:16', '2026-04-12 10:22:48', NULL),
(5, 'REC-54642', 5, 4, '2026-04-02', 'Gastroenteritis', 'Patient presents with Gastroenteritis symptoms.', 'Prescribed Ciprofloxacin 50mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"126/88\", \"spo2\": 97, \"temp\": \"37.9\", \"pulse\": 78}', NULL, 'Patient responded well to initial treatment.', 1, '2026-06-08', '2025-11-02 00:39:47', '2026-04-12 10:22:48', NULL),
(6, 'REC-81540', 5, 4, '2026-01-11', 'Diabetes Mellitus', 'Patient presents with Diabetes Mellitus symptoms.', 'Prescribed Azithromycin 250mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"112/92\", \"spo2\": 95, \"temp\": \"37.1\", \"pulse\": 75}', NULL, 'Patient responded well to initial treatment.', 1, '2026-04-17', '2026-02-01 10:54:06', '2026-04-12 10:22:48', NULL),
(7, 'REC-70645', 5, 4, '2026-01-31', 'Diabetes Mellitus', 'Patient presents with Diabetes Mellitus symptoms.', 'Prescribed Paracetamol 500mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Mild', 1, '{\"bp\": \"124/72\", \"spo2\": 100, \"temp\": \"36.7\", \"pulse\": 87}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-27', '2025-10-30 07:06:02', '2026-04-12 10:22:48', NULL),
(8, 'REC-68075', 5, 4, '2026-03-03', 'Gastroenteritis', 'Patient presents with Gastroenteritis symptoms.', 'Prescribed Lisinopril 500mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Mild', 1, '{\"bp\": \"135/76\", \"spo2\": 98, \"temp\": \"36.5\", \"pulse\": 94}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-21', '2025-12-29 12:24:49', '2026-04-12 10:22:48', NULL),
(9, 'REC-79722', 5, 4, '2026-04-04', 'Diabetes Mellitus', 'Patient presents with Diabetes Mellitus symptoms.', 'Prescribed Amoxicillin 200mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"140/93\", \"spo2\": 97, \"temp\": \"37.7\", \"pulse\": 94}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-02', '2026-04-06 18:15:38', '2026-04-12 10:22:48', NULL),
(10, 'REC-66113', 5, 4, '2025-10-12', 'Asthma', 'Patient presents with Asthma symptoms.', 'Prescribed Lisinopril 200mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Mild', 1, '{\"bp\": \"132/87\", \"spo2\": 100, \"temp\": \"37.4\", \"pulse\": 77}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-10', '2026-02-11 19:22:45', '2026-04-12 10:22:48', NULL),
(11, 'REC-26629', 8, 4, '2025-11-26', 'Hypertension', 'Patient presents with Hypertension symptoms.', 'Prescribed Paracetamol 50mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"135/73\", \"spo2\": 96, \"temp\": \"37.2\", \"pulse\": 68}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-16', '2025-10-17 09:08:31', '2026-04-12 10:26:48', NULL),
(12, 'REC-69400', 7, 4, '2026-03-05', 'Typhoid Fever', 'Patient presents with Typhoid Fever symptoms.', 'Prescribed Omeprazole 200mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Severe', 1, '{\"bp\": \"119/79\", \"spo2\": 100, \"temp\": \"37.9\", \"pulse\": 68}', NULL, 'Patient responded well to initial treatment.', 1, '2026-04-19', '2026-03-14 06:18:12', '2026-04-12 10:26:48', NULL),
(13, 'REC-26948', 8, 4, '2026-02-19', 'Hypertension', 'Patient presents with Hypertension symptoms.', 'Prescribed Paracetamol 20mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Severe', 1, '{\"bp\": \"142/84\", \"spo2\": 96, \"temp\": \"36.6\", \"pulse\": 97}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-11', '2025-12-21 05:26:10', '2026-04-12 10:26:48', NULL),
(14, 'REC-20195', 8, 4, '2026-01-03', 'Asthma', 'Patient presents with Asthma symptoms.', 'Prescribed Paracetamol 250mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"132/80\", \"spo2\": 95, \"temp\": \"37.3\", \"pulse\": 66}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-09', '2025-10-27 14:10:18', '2026-04-12 10:26:48', NULL),
(15, 'REC-57727', 6, 4, '2026-01-13', 'Hypertension', 'Patient presents with Hypertension symptoms.', 'Prescribed Amoxicillin 250mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Severe', 1, '{\"bp\": \"112/87\", \"spo2\": 97, \"temp\": \"37.1\", \"pulse\": 88}', NULL, 'Patient responded well to initial treatment.', 1, '2026-04-16', '2026-03-09 15:45:20', '2026-04-12 10:26:48', NULL),
(16, 'REC-18654', 10, 4, '2026-01-30', 'Hypertension', 'Patient presents with Hypertension symptoms.', 'Prescribed Omeprazole 100mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"141/95\", \"spo2\": 96, \"temp\": \"37.3\", \"pulse\": 63}', NULL, 'Patient responded well to initial treatment.', 1, '2026-04-18', '2026-01-16 09:52:10', '2026-04-12 10:26:48', NULL),
(17, 'REC-46804', 7, 4, '2025-10-21', 'Malaria', 'Patient presents with Malaria symptoms.', 'Prescribed Artemether 20mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Mild', 1, '{\"bp\": \"111/88\", \"spo2\": 100, \"temp\": \"37.4\", \"pulse\": 86}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-11', '2025-11-23 02:54:17', '2026-04-12 10:26:48', NULL),
(18, 'REC-69495', 6, 4, '2026-01-22', 'Anemia', 'Patient presents with Anemia symptoms.', 'Prescribed Omeprazole 250mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"115/90\", \"spo2\": 97, \"temp\": \"37.4\", \"pulse\": 89}', NULL, 'Patient responded well to initial treatment.', 1, '2026-06-10', '2025-12-12 12:08:57', '2026-04-12 10:26:48', NULL),
(19, 'REC-82853', 9, 4, '2025-10-27', 'Appendicitis', 'Patient presents with Appendicitis symptoms.', 'Prescribed Chloroquine 100mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"123/72\", \"spo2\": 97, \"temp\": \"36.7\", \"pulse\": 62}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-08', '2025-11-01 23:10:43', '2026-04-12 10:26:48', NULL),
(20, 'REC-22309', 6, 4, '2025-10-24', 'Malaria', 'Patient presents with Malaria symptoms.', 'Prescribed Amlodipine 10mg.', 'Take medications as directed. Rest and increase fluid intake.', NULL, 'Moderate', 1, '{\"bp\": \"134/88\", \"spo2\": 98, \"temp\": \"36.5\", \"pulse\": 91}', NULL, 'Patient responded well to initial treatment.', 1, '2026-05-27', '2025-11-04 17:07:24', '2026-04-12 10:26:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medication_administration`
--

DROP TABLE IF EXISTS `medication_administration`;
CREATE TABLE IF NOT EXISTS `medication_administration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. MED-ADM-001',
  `prescription_id` int DEFAULT NULL COMMENT 'FK → prescriptions.id',
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `medicine_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Oral, IV, IM, SQ, etc.',
  `scheduled_time` datetime NOT NULL,
  `administered_at` datetime DEFAULT NULL,
  `status` enum('Pending','Administered','Missed','Refused','Held','PRN') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `verification_method` enum('Barcode','Manual','eMAR') COLLATE utf8mb4_unicode_ci DEFAULT 'Manual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  KEY `idx_ma_admin_id` (`admin_id`),
  KEY `idx_ma_prescription` (`prescription_id`),
  KEY `idx_ma_patient` (`patient_id`),
  KEY `idx_ma_nurse` (`nurse_id`),
  KEY `idx_ma_status` (`status`),
  KEY `idx_ma_scheduled` (`scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Medication administration records (5 Rights compliance)';

-- --------------------------------------------------------

--
-- Table structure for table `medication_schedules`
--

DROP TABLE IF EXISTS `medication_schedules`;
CREATE TABLE IF NOT EXISTS `medication_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prescription_id` int DEFAULT NULL COMMENT 'FK → prescriptions.id',
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int DEFAULT NULL COMMENT 'FK → nurses.id',
  `medicine_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Once Daily, BD, TDS, QID, PRN',
  `scheduled_times` json DEFAULT NULL COMMENT '["08:00","14:00","20:00"]',
  `route` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled','On Hold') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedule_id` (`schedule_id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `idx_ms_schedule_id` (`schedule_id`),
  KEY `idx_ms_patient` (`patient_id`),
  KEY `idx_ms_nurse` (`nurse_id`),
  KEY `idx_ms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Medication administration schedules from prescriptions';

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
  `drug_interactions` json DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'tablet',
  `reorder_level` int DEFAULT '10',
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_prescription_required` tinyint(1) DEFAULT '1',
  `is_controlled` tinyint(1) DEFAULT '0',
  `status` enum('active','discontinued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_id` (`medicine_id`),
  KEY `idx_medicine_id` (`medicine_id`),
  KEY `idx_medicine_name` (`medicine_name`),
  KEY `idx_stock` (`stock_quantity`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_id`, `medicine_name`, `generic_name`, `category`, `manufacturer`, `supplier_name`, `description`, `storage_instructions`, `side_effects`, `contraindications`, `drug_interactions`, `unit_price`, `stock_quantity`, `unit`, `reorder_level`, `expiry_date`, `batch_number`, `is_prescription_required`, `is_controlled`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MED001', 'Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, NULL, 0.50, 500, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'MED002', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, NULL, 0.75, 300, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(3, 'MED003', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'MedCare', NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 200, 'tablet', 30, NULL, NULL, 1, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(4, 'MED004', 'Vitamin C 1000mg', 'Ascorbic Acid', 'Vitamin', 'HealthPlus', NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 400, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(5, 'MED005', 'Omeprazole 20mg', 'Omeprazole', 'Antacid', 'MedCare', NULL, NULL, NULL, NULL, NULL, NULL, 1.50, 150, 'tablet', 30, NULL, NULL, 1, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(6, 'MED-71304', 'Amoxicillin', 'Amoxicillin', 'Antidiabetic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 129.00, 344, 'Syrup', 44, NULL, NULL, 1, 0, 'active', '2025-03-02 11:36:25', '2026-04-12 10:22:48'),
(7, 'MED-19183', 'Paracetamol', 'Paracetamol', 'Antimalarial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50.00, 312, 'Capsule', 46, NULL, NULL, 1, 0, 'active', '2025-05-22 21:38:30', '2026-04-12 10:22:48'),
(8, 'MED-81864', 'Metformin', 'Metformin', 'Analgesic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 110.00, 130, 'Capsule', 33, NULL, NULL, 1, 0, 'active', '2024-07-20 09:11:20', '2026-04-12 10:22:48'),
(9, 'MED-62476', 'Amlodipine', 'Amlodipine', 'Antihypertensive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 90.00, 366, 'Tablet', 38, NULL, NULL, 1, 0, 'active', '2025-07-23 03:13:34', '2026-04-12 10:22:48'),
(10, 'MED-90770', 'Artemether', 'Artemether', 'Antimalarial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 97.00, 220, 'Capsule', 44, NULL, NULL, 1, 0, 'active', '2025-01-24 17:30:08', '2026-04-12 10:22:48'),
(11, 'MED-54239', 'Omeprazole', 'Omeprazole', 'Antidiabetic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 183.00, 238, 'Injection', 35, NULL, NULL, 1, 0, 'active', '2024-05-30 18:21:23', '2026-04-12 10:22:48'),
(12, 'MED-13909', 'Ciprofloxacin', 'Ciprofloxacin', 'Antimalarial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 22.00, 247, 'Capsule', 29, NULL, NULL, 1, 0, 'active', '2024-05-15 13:24:11', '2026-04-12 10:22:48'),
(13, 'MED-97372', 'Lisinopril', 'Lisinopril', 'Antimalarial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 182.00, 127, 'Capsule', 25, NULL, NULL, 1, 0, 'active', '2024-08-29 16:05:35', '2026-04-12 10:22:48'),
(14, 'MED-75668', 'Chloroquine', 'Chloroquine', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 173.00, 86, 'Injection', 26, NULL, NULL, 1, 0, 'active', '2025-03-23 17:41:55', '2026-04-12 10:22:48'),
(15, 'MED-82237', 'Azithromycin', 'Azithromycin', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24.00, 258, 'Syrup', 35, NULL, NULL, 1, 0, 'active', '2025-08-20 08:48:35', '2026-04-12 10:22:48'),
(16, 'MED-36331', 'Amoxicillin', 'Amoxicillin', 'Analgesic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17.00, 468, 'Tablet', 43, NULL, NULL, 1, 0, 'active', '2024-09-29 15:36:30', '2026-04-12 10:26:48'),
(17, 'MED-18459', 'Paracetamol', 'Paracetamol', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 85.00, 128, 'Injection', 35, NULL, NULL, 1, 0, 'active', '2025-02-26 00:42:56', '2026-04-12 10:26:48'),
(18, 'MED-83701', 'Metformin', 'Metformin', 'Analgesic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 49.00, 414, 'Tablet', 29, NULL, NULL, 1, 0, 'active', '2025-08-09 22:10:10', '2026-04-12 10:26:48'),
(19, 'MED-13711', 'Amlodipine', 'Amlodipine', 'Antidiabetic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 124.00, 97, 'Injection', 27, NULL, NULL, 1, 0, 'active', '2025-09-25 23:36:01', '2026-04-12 10:26:48'),
(20, 'MED-93629', 'Artemether', 'Artemether', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 166.00, 383, 'Tablet', 31, NULL, NULL, 1, 0, 'active', '2025-07-30 01:43:17', '2026-04-12 10:26:48'),
(21, 'MED-97371', 'Omeprazole', 'Omeprazole', 'Antimalarial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 111.00, 148, 'Capsule', 40, NULL, NULL, 1, 0, 'active', '2025-06-24 06:31:19', '2026-04-12 10:26:48'),
(22, 'MED-42872', 'Ciprofloxacin', 'Ciprofloxacin', 'Antihypertensive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 115.00, 160, 'Capsule', 49, NULL, NULL, 1, 0, 'active', '2025-06-12 22:44:14', '2026-04-12 10:26:48'),
(23, 'MED-59294', 'Lisinopril', 'Lisinopril', 'Antihypertensive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 62.00, 101, 'Injection', 32, NULL, NULL, 1, 0, 'active', '2025-04-29 22:30:17', '2026-04-12 10:26:48'),
(24, 'MED-48123', 'Chloroquine', 'Chloroquine', 'Antihypertensive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 122.00, 265, 'Capsule', 47, NULL, NULL, 1, 0, 'active', '2025-03-10 15:11:56', '2026-04-12 10:26:48'),
(25, 'MED-93221', 'Azithromycin', 'Azithromycin', 'Analgesic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 101.00, 321, 'Tablet', 49, NULL, NULL, 1, 0, 'active', '2025-01-14 22:20:26', '2026-04-12 10:26:48'),
(26, 'MED-001', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.50, 200, 'tablet', 50, '2025-12-31', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(27, 'MED-002', 'Paracetamol 500mg', 'Acetaminophen', 'Analgesic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.80, 1500, 'tablet', 200, '2026-06-30', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(28, 'MED-003', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.20, 450, 'tablet', 100, '2025-08-15', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(29, 'MED-004', 'Metformin 850mg', 'Metformin', 'Antidiabetic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.80, 300, 'tablet', 50, '2025-11-20', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(30, 'MED-005', 'Amlodipine 5mg', 'Amlodipine', 'Antihypertensive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.50, 120, 'tablet', 30, '2026-01-10', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(31, 'MED-006', 'Salbutamol Inhaler', 'Albuterol', 'Bronchodilator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12.00, 50, 'tablet', 20, '2025-05-15', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(32, 'MED-007', 'Omeprazole 20mg', 'Omeprazole', 'Proton Pump Inhibitor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.20, 220, 'tablet', 50, '2025-09-05', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(33, 'MED-008', 'Ciprofloxacin 500mg', 'Ciprofloxacin', 'Antibiotic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4.50, 80, 'tablet', 25, '2025-10-12', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(34, 'MED-009', 'Cetirizine 10mg', 'Cetirizine', 'Antihistamine', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.50, 600, 'tablet', 100, '2026-03-22', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44'),
(35, 'MED-010', 'Diazepam 5mg', 'Diazepam', 'Antianxiety', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 40, 'tablet', 15, '2025-07-01', NULL, 1, 0, 'active', '2026-04-14 00:48:19', '2026-04-14 00:58:44');

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_inventory`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `medicine_inventory`;
CREATE TABLE IF NOT EXISTS `medicine_inventory` (
`batch_number` varchar(100)
,`category` varchar(100)
,`created_at` timestamp
,`expiry_date` date
,`generic_name` varchar(200)
,`id` int
,`is_prescription_required` tinyint(1)
,`manufacturer` varchar(200)
,`medicine_id` varchar(50)
,`medicine_name` varchar(200)
,`reorder_level` int
,`stock_quantity` int
,`stock_status` varchar(13)
,`supplier_name` varchar(200)
,`unit` varchar(50)
,`unit_price` decimal(10,2)
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
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse','finance_officer','finance_manager') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `user_role`, `type`, `title`, `message`, `is_read`, `priority`, `action_url`, `related_module`, `related_id`, `created_at`, `read_at`) VALUES
(1, 1, 'admin', 'New Registration', 'New Finance Officer Registration', 'New Finance Officer registration pending approval: Dzimado Emmanuel Nana Atakorah (atakorahe57@gmail.com)', 0, 'normal', NULL, 'users', NULL, '2026-04-09 14:58:29', NULL),
(2, 1, NULL, 'billing', 'Medical Update', 'Your appointment has been confirmed.', 0, 'normal', NULL, NULL, NULL, '2026-03-25 23:19:43', NULL),
(3, 20, NULL, 'general', 'Medical Update', 'Lab results are now available.', 1, 'normal', NULL, NULL, NULL, '2026-03-19 22:49:31', NULL),
(4, 1, NULL, 'general', 'Medical Update', 'Your prescription has been dispensed.', 1, 'normal', NULL, NULL, NULL, '2026-03-13 07:08:58', NULL),
(5, 20, NULL, 'billing', 'Medical Update', 'Reminder: appointment tomorrow at 10:00 AM.', 0, 'normal', NULL, NULL, NULL, '2026-03-29 16:14:08', NULL),
(6, 35, NULL, 'prescription', 'Medical Update', 'Your invoice has been generated.', 0, 'normal', NULL, NULL, NULL, '2026-03-29 14:25:00', NULL),
(7, 20, NULL, 'appointment', 'Medical Update', 'New medical record has been updated.', 0, 'normal', NULL, NULL, NULL, '2026-04-09 08:25:39', NULL),
(8, 35, NULL, 'appointment', 'Medical Update', 'Password changed successfully.', 0, 'normal', NULL, NULL, NULL, '2026-03-25 06:53:52', NULL),
(9, 28, NULL, 'prescription', 'Medical Update', 'Your refill request has been approved.', 1, 'normal', NULL, NULL, NULL, '2026-04-02 05:40:20', NULL),
(10, 20, NULL, 'lab_result', 'Medical Update', 'Payment received successfully.', 0, 'normal', NULL, NULL, NULL, '2026-03-29 08:27:16', NULL),
(11, 20, NULL, 'general', 'Medical Update', 'Welcome to RMU Medical Sickbay!', 0, 'normal', NULL, NULL, NULL, '2026-03-30 04:21:28', NULL),
(12, 28, NULL, 'prescription', 'Medical Update', 'Your appointment has been confirmed.', 0, 'normal', NULL, NULL, NULL, '2026-04-02 04:20:37', NULL),
(13, 35, NULL, 'appointment', 'Medical Update', 'Lab results are now available.', 0, 'normal', NULL, NULL, NULL, '2026-04-05 22:24:29', NULL),
(14, 35, NULL, 'appointment', 'Medical Update', 'Your prescription has been dispensed.', 0, 'normal', NULL, NULL, NULL, '2026-04-01 19:55:09', NULL),
(15, 1, NULL, 'billing', 'Medical Update', 'Reminder: appointment tomorrow at 10:00 AM.', 0, 'normal', NULL, NULL, NULL, '2026-03-14 21:13:21', NULL),
(16, 20, NULL, 'billing', 'Medical Update', 'Your invoice has been generated.', 0, 'normal', NULL, NULL, NULL, '2026-04-03 08:12:57', NULL),
(17, 36, NULL, 'prescription', 'Medical Update', 'New medical record has been updated.', 1, 'normal', NULL, NULL, NULL, '2026-04-11 09:50:42', '2026-04-15 11:55:53'),
(18, 26, NULL, 'general', 'Medical Update', 'Password changed successfully.', 1, 'normal', NULL, NULL, NULL, '2026-03-29 19:28:16', NULL),
(19, 28, NULL, 'prescription', 'Medical Update', 'Your refill request has been approved.', 1, 'normal', NULL, NULL, NULL, '2026-03-27 16:17:37', NULL),
(20, 1, NULL, 'billing', 'Medical Update', 'Payment received successfully.', 0, 'normal', NULL, NULL, NULL, '2026-04-11 00:44:52', NULL),
(21, 35, NULL, 'general', 'Medical Update', 'Welcome to RMU Medical Sickbay!', 0, 'normal', NULL, NULL, NULL, '2026-03-14 08:46:08', NULL),
(22, 28, NULL, 'billing', 'Medical Update', 'Your appointment has been confirmed.', 0, 'normal', NULL, NULL, NULL, '2026-03-21 22:01:14', NULL),
(23, 41, NULL, 'lab_result', 'Medical Update', 'Lab results are now available.', 0, 'normal', NULL, NULL, NULL, '2026-04-10 09:36:16', NULL),
(24, 44, NULL, 'general', 'Medical Update', 'Your prescription has been dispensed.', 1, 'normal', NULL, NULL, NULL, '2026-03-20 10:38:42', NULL),
(25, 35, NULL, 'lab_result', 'Medical Update', 'Reminder: appointment tomorrow at 10:00 AM.', 0, 'normal', NULL, NULL, NULL, '2026-03-16 02:50:15', NULL),
(27, 20, NULL, 'prescription', 'Medical Update', 'New medical record has been updated.', 0, 'normal', NULL, NULL, NULL, '2026-03-24 13:38:02', NULL),
(29, 42, NULL, 'billing', 'Medical Update', 'Your refill request has been approved.', 1, 'normal', NULL, NULL, NULL, '2026-03-31 17:11:47', NULL),
(30, 43, NULL, 'lab_result', 'Medical Update', 'Payment received successfully.', 1, 'normal', NULL, NULL, NULL, '2026-03-26 09:21:59', NULL),
(31, 43, NULL, 'billing', 'Medical Update', 'Welcome to RMU Medical Sickbay!', 0, 'normal', NULL, NULL, NULL, '2026-03-15 09:02:00', NULL),
(32, 20, NULL, 'appointment', 'Medical Update', 'Your appointment has been confirmed.', 0, 'normal', NULL, NULL, NULL, '2026-03-30 15:53:03', NULL),
(33, 38, NULL, 'general', 'Medical Update', 'Lab results are now available.', 0, 'normal', NULL, NULL, NULL, '2026-04-10 16:02:00', NULL),
(34, 36, NULL, 'appointment', 'Medical Update', 'Your prescription has been dispensed.', 1, 'normal', NULL, NULL, NULL, '2026-03-23 23:34:36', '2026-04-15 12:38:30'),
(35, 20, NULL, 'billing', 'Medical Update', 'Reminder: appointment tomorrow at 10:00 AM.', 0, 'normal', NULL, NULL, NULL, '2026-03-24 17:44:35', NULL),
(36, 44, NULL, 'general', 'Medical Update', 'Your invoice has been generated.', 0, 'normal', NULL, NULL, NULL, '2026-03-27 18:12:33', NULL),
(37, 39, NULL, 'general', 'Medical Update', 'New medical record has been updated.', 0, 'normal', NULL, NULL, NULL, '2026-04-02 05:56:03', NULL),
(38, 38, NULL, 'appointment', 'Medical Update', 'Password changed successfully.', 0, 'normal', NULL, NULL, NULL, '2026-03-16 16:14:53', NULL),
(39, 44, NULL, 'appointment', 'Medical Update', 'Your refill request has been approved.', 0, 'normal', NULL, NULL, NULL, '2026-03-13 13:22:47', NULL),
(40, 41, NULL, 'prescription', 'Medical Update', 'Payment received successfully.', 0, 'normal', NULL, NULL, NULL, '2026-03-27 12:14:08', NULL),
(41, 1, NULL, 'lab_result', 'Medical Update', 'Welcome to RMU Medical Sickbay!', 0, 'normal', NULL, NULL, NULL, '2026-03-25 03:12:02', NULL),
(52, 101, 'patient', 'system', 'Welcome!', 'Welcome to the RMU Medical Portal. Please complete your profile.', 0, 'high', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(54, 202, 'nurse', 'task', 'New Task Assigned', 'Please check Vitals for Ward A Room 3.', 0, 'high', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(55, 203, 'pharmacist', 'stock', 'Low Stock Alert', 'Amoxicillin stock is below reorder level (50).', 0, 'critical', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(56, 1, 'admin', 'request', 'Ambulance Request', 'Emergency request from West Campus Hostel.', 0, 'urgent', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(57, 1, 'admin', 'security', 'Incident Logged', 'Intoxicated visitor at Main Entrance.', 0, 'high', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(58, 204, 'finance_manager', 'invoice', 'Payment Received', 'Invoice INV-RMU-102 has been fully paid.', 0, 'normal', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(59, 102, 'patient', 'lab', 'Results Available', 'Your Malaria test results are now available online.', 0, 'normal', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(60, 101, 'patient', 'appointment', 'Reminder', 'You have an appointment scheduled for tomorrow at 9:00 AM.', 0, 'normal', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(61, 202, 'nurse', 'system', 'Shift Update', 'Your shift has been updated. Please check the schedule.', 0, 'normal', NULL, NULL, NULL, '2026-04-14 06:57:46', NULL),
(62, 1, 'admin', 'New Registration', 'New Maintenance Registration', 'New Maintenance registration pending approval: Junior Barns (junior.barns@gmail.com)', 0, 'normal', NULL, 'users', NULL, '2026-04-15 12:30:00', NULL),
(63, 1, 'admin', 'New Registration', 'New Pharmacist Registration', 'New Pharmacist registration pending approval: Lil Shurface (www.lovelacejohnbaidoo@gmail.com)', 0, 'normal', NULL, 'users', NULL, '2026-04-17 09:30:44', NULL),
(64, 1, NULL, 'pharmacy_daily_summary', '', '📊 Pharmacy Daily Summary: 9 expired. Review pharmacy alerts.', 0, 'normal', NULL, 'pharmacy', NULL, '2026-04-17 09:34:44', NULL),
(65, 20, 'doctor', 'message', 'New Message from Lab', 'Lab message: Good day Doctor\r\nThe result you requested is attached to thi...', 0, 'normal', NULL, 'Messages', NULL, '2026-04-20 04:16:21', NULL),
(66, 36, 'patient', 'prescription', 'New Prescription', 'A new prescription has been issued for you: Amlodipine', 0, 'normal', NULL, 'prescriptions', 31, '2026-04-20 04:36:47', NULL),
(67, 203, 'pharmacist', 'prescription', 'New Prescription Pending', 'Dr. Joyce Eli prescribed Amlodipine (Qty: 2) — pending dispensing.', 0, 'normal', NULL, 'prescriptions', 31, '2026-04-20 04:36:47', NULL),
(68, 312, 'pharmacist', 'prescription', 'New Prescription Pending', 'Dr. Joyce Eli prescribed Amlodipine (Qty: 2) — pending dispensing.', 0, 'normal', NULL, 'prescriptions', 31, '2026-04-20 04:36:47', NULL),
(69, 36, 'patient', 'prescription', 'New Prescription', 'A new prescription has been issued for you: Amlodipine', 0, 'normal', NULL, 'prescriptions', 32, '2026-04-20 06:26:07', NULL),
(70, 203, 'pharmacist', 'prescription', 'New Prescription Pending', 'Dr. Joyce Eli prescribed Amlodipine (Qty: 1) — pending dispensing.', 0, 'normal', NULL, 'prescriptions', 32, '2026-04-20 06:26:07', NULL),
(71, 312, 'pharmacist', 'prescription', 'New Prescription Pending', 'Dr. Joyce Eli prescribed Amlodipine (Qty: 1) — pending dispensing.', 0, 'normal', NULL, 'prescriptions', 32, '2026-04-20 06:26:07', NULL),
(72, 1, NULL, 'pharmacy_daily_summary', '', '📊 Pharmacy Daily Summary: 9 expired. Review pharmacy alerts.', 0, 'normal', NULL, 'pharmacy', NULL, '2026-04-20 07:02:46', NULL),
(73, 1, 'admin', 'New Registration', 'New Maintenance Registration', 'New Maintenance registration pending approval: Joseph Agyemang (joseph.agyemang@st.rmu.edu.gh)', 0, 'normal', NULL, 'users', NULL, '2026-04-21 04:32:02', NULL),
(74, 1, 'admin', 'New Registration', 'New Security Registration', 'New Security registration pending approval: Bernard Boateng (bernard.boateng@st.rmu.edu.gh)', 0, 'normal', NULL, 'users', NULL, '2026-04-21 09:14:07', NULL),
(75, 1, 'admin', 'New Registration', 'New Cleaner Registration', 'New Cleaner registration pending approval: Gifty Asante (gifty.asante@st.rmu.edu.gh)', 0, 'normal', NULL, 'users', NULL, '2026-04-21 10:12:56', NULL),
(76, 1, 'admin', 'New Registration', 'New Ambulance Driver Registration', 'New Ambulance Driver registration pending approval: Micheal Asante (micheeal.asante@st.rmu.edu.gh)', 0, 'normal', NULL, 'users', NULL, '2026-05-05 06:45:21', NULL),
(77, 1, 'admin', 'New Registration', 'New Finance Manager Registration', 'New Finance Manager registration pending approval: Junior Owusu (junior.owusu@st.rmu.edu.gh)', 0, 'normal', NULL, 'users', NULL, '2026-05-14 10:27:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel_push` tinyint(1) DEFAULT '1',
  `channel_email` tinyint(1) DEFAULT '0',
  `channel_sms` tinyint(1) DEFAULT '0',
  `escalation_minutes` int DEFAULT '0',
  `is_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_role` (`event_type`,`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

DROP TABLE IF EXISTS `nurses`;
CREATE TABLE IF NOT EXISTS `nurses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. NRS-001',
  `user_id` int NOT NULL COMMENT 'FK → users.id',
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Female',
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `specialization` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pediatric, ICU, Surgical, etc.',
  `department_id` int DEFAULT NULL,
  `designation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Head Nurse, Staff Nurse, Senior Nurse',
  `years_of_experience` int NOT NULL DEFAULT '0',
  `shift_type` enum('Morning','Afternoon','Night','Rotating') COLLATE utf8mb4_unicode_ci DEFAULT 'Morning',
  `status` enum('Active','Inactive','On Leave','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nurse_id` (`nurse_id`),
  UNIQUE KEY `uniq_nurse_license` (`license_number`),
  KEY `idx_nurse_id` (`nurse_id`),
  KEY `idx_nurse_user` (`user_id`),
  KEY `idx_nurse_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nurse profiles linked to shared users table';

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`id`, `nurse_id`, `user_id`, `full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `email`, `address`, `profile_photo`, `license_number`, `license_expiry`, `specialization`, `department_id`, `designation`, `years_of_experience`, `shift_type`, `status`, `created_at`, `updated_at`, `approval_status`, `rejection_reason`, `approved_by`, `approved_at`) VALUES
(1, '', 26, 'Nelly Nartey', NULL, 'Female', NULL, '0272814681', 'nartey.nelly@st.rmu.edu.gh', NULL, 'default-avatar.png', NULL, NULL, NULL, NULL, NULL, 0, 'Morning', 'Active', '2026-03-20 03:10:01', '2026-03-20 03:16:00', 'approved', NULL, 1, '2026-03-20 03:16:00'),
(10, 'RMU-NUR-202', 202, 'Rita Mensah', NULL, 'Female', NULL, NULL, NULL, NULL, 'default-avatar.png', 'NMC-2023-222', NULL, NULL, NULL, NULL, 0, 'Morning', 'Active', '2026-04-14 00:22:53', '2026-04-17 08:31:14', 'approved', NULL, 1, '2026-04-17 08:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_activity_log`
--

DROP TABLE IF EXISTS `nurse_activity_log`;
CREATE TABLE IF NOT EXISTS `nurse_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `log_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `action` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Human-readable action',
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'vitals, medication, tasks, notes, etc.',
  `record_id` int DEFAULT NULL COMMENT 'Related record PK if applicable',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `log_id` (`log_id`),
  KEY `idx_nal_log_id` (`log_id`),
  KEY `idx_nal_nurse` (`nurse_id`),
  KEY `idx_nal_module` (`module`),
  KEY `idx_nal_ts` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Full audit trail of all nurse actions in the system';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_certifications`
--

DROP TABLE IF EXISTS `nurse_certifications`;
CREATE TABLE IF NOT EXISTS `nurse_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `certification_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `certification_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_body` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certificate_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Valid','Expired','Pending Renewal') COLLATE utf8mb4_unicode_ci DEFAULT 'Valid',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certification_id` (`certification_id`),
  KEY `idx_nc_cert_id` (`certification_id`),
  KEY `idx_nc_nurse` (`nurse_id`),
  KEY `idx_nc_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Professional nursing certifications with expiry tracking';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_doctor_messages`
--

DROP TABLE IF EXISTS `nurse_doctor_messages`;
CREATE TABLE IF NOT EXISTS `nurse_doctor_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` int NOT NULL COMMENT 'FK → users.id',
  `sender_role` enum('nurse','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_id` int NOT NULL COMMENT 'FK → users.id',
  `receiver_role` enum('nurse','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int DEFAULT NULL COMMENT 'FK → patients.id',
  `subject` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `idx_ndm_msg_id` (`message_id`),
  KEY `idx_ndm_sender` (`sender_id`),
  KEY `idx_ndm_receiver` (`receiver_id`),
  KEY `idx_ndm_is_read` (`is_read`),
  KEY `idx_ndm_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Direct messaging between nurses, doctors, and admins';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_documents`
--

DROP TABLE IF EXISTS `nurse_documents`;
CREATE TABLE IF NOT EXISTS `nurse_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `file_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MIME type e.g. application/pdf',
  `file_size` bigint NOT NULL DEFAULT '0' COMMENT 'Bytes',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID, Contract, Medical, Other',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `idx_nd_doc_id` (`document_id`),
  KEY `idx_nd_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='General document uploads for nurse profile';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_notifications`
--

DROP TABLE IF EXISTS `nurse_notifications`;
CREATE TABLE IF NOT EXISTS `nurse_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `user_id` int DEFAULT NULL COMMENT 'FK → users.id',
  `title` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Task','Vital Alert','Medication Reminder','Emergency','Doctor Message','Shift','System','Handover','General') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `related_module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_id` (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_nnotif_id` (`notification_id`),
  KEY `idx_nnotif_nurse` (`nurse_id`),
  KEY `idx_nnotif_is_read` (`is_read`),
  KEY `idx_nnotif_type` (`type`),
  KEY `idx_nnotif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='In-app notifications for nurses';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_professional_profile`
--

DROP TABLE IF EXISTS `nurse_professional_profile`;
CREATE TABLE IF NOT EXISTS `nurse_professional_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `sub_specialization` varchar(100) DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `years_of_experience` int DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_issuing_body` varchar(150) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `nursing_school` varchar(200) DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `postgraduate_details` text,
  `languages_spoken` json DEFAULT NULL,
  `bio` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `nurse_id` (`nurse_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nurse_professional_profile`
--

INSERT INTO `nurse_professional_profile` (`id`, `nurse_id`, `specialization`, `sub_specialization`, `department_id`, `designation`, `years_of_experience`, `license_number`, `license_issuing_body`, `license_expiry_date`, `nursing_school`, `graduation_year`, `postgraduate_details`, `languages_spoken`, `bio`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-20 03:26:01'),
(2, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-20 03:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_profile_completeness`
--

DROP TABLE IF EXISTS `nurse_profile_completeness`;
CREATE TABLE IF NOT EXISTS `nurse_profile_completeness` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL,
  `personal_info_complete` tinyint(1) NOT NULL DEFAULT '0',
  `professional_info_complete` tinyint(1) NOT NULL DEFAULT '0',
  `qualifications_complete` tinyint(1) NOT NULL DEFAULT '0',
  `documents_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `photo_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `security_setup_complete` tinyint(1) NOT NULL DEFAULT '0',
  `overall_percentage` tinyint NOT NULL DEFAULT '0' COMMENT '0-100',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nurse_id` (`nurse_id`),
  KEY `idx_npc_nurse` (`nurse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks profile completeness percentage per nurse';

--
-- Dumping data for table `nurse_profile_completeness`
--

INSERT INTO `nurse_profile_completeness` (`id`, `nurse_id`, `personal_info_complete`, `professional_info_complete`, `qualifications_complete`, `documents_uploaded`, `photo_uploaded`, `security_setup_complete`, `overall_percentage`, `last_updated`) VALUES
(1, 1, 0, 0, 0, 0, 0, 0, 0, '2026-03-20 03:26:01');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_qualifications`
--

DROP TABLE IF EXISTS `nurse_qualifications`;
CREATE TABLE IF NOT EXISTS `nurse_qualifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `qualification_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `degree_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_awarded` year DEFAULT NULL,
  `certificate_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qualification_id` (`qualification_id`),
  KEY `idx_nq_qual_id` (`qualification_id`),
  KEY `idx_nq_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nurse academic qualifications and degrees';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_sessions`
--

DROP TABLE IF EXISTS `nurse_sessions`;
CREATE TABLE IF NOT EXISTS `nurse_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `user_id` int NOT NULL COMMENT 'FK → users.id',
  `device_info` text COLLATE utf8mb4_unicode_ci,
  `browser` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `logout_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_nses_session_id` (`session_id`),
  KEY `idx_nses_nurse` (`nurse_id`),
  KEY `idx_nses_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Active and historical nurse login sessions';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_settings`
--

DROP TABLE IF EXISTS `nurse_settings`;
CREATE TABLE IF NOT EXISTS `nurse_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `notification_preferences` json DEFAULT NULL,
  `theme_preference` enum('light','dark','auto') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `alert_sound_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nurse_id` (`nurse_id`),
  KEY `idx_nset_nurse` (`nurse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nurse-specific app settings and notification preferences';

--
-- Dumping data for table `nurse_settings`
--

INSERT INTO `nurse_settings` (`id`, `nurse_id`, `notification_preferences`, `theme_preference`, `language`, `alert_sound_enabled`, `email_notifications`, `updated_at`) VALUES
(1, 1, NULL, 'light', 'en', 1, 1, '2026-03-20 03:26:01');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_shifts`
--

DROP TABLE IF EXISTS `nurse_shifts`;
CREATE TABLE IF NOT EXISTS `nurse_shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `shift_type` enum('Morning','Afternoon','Night') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Morning',
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `ward_assigned` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Scheduled','Active','Completed','Missed') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `handover_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_id` (`shift_id`),
  KEY `idx_ns_shift_id` (`shift_id`),
  KEY `idx_ns_nurse` (`nurse_id`),
  KEY `idx_ns_date` (`shift_date`),
  KEY `idx_ns_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nurse shift schedule and status tracking';

-- --------------------------------------------------------

--
-- Table structure for table `nurse_tasks`
--

DROP TABLE IF EXISTS `nurse_tasks`;
CREATE TABLE IF NOT EXISTS `nurse_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `assigned_by_id` int NOT NULL COMMENT 'FK → users.id',
  `assigned_by_role` enum('doctor','admin','nurse') COLLATE utf8mb4_unicode_ci DEFAULT 'doctor',
  `patient_id` int DEFAULT NULL COMMENT 'FK → patients.id',
  `task_title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_description` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `due_time` datetime DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `assigned_by_id` (`assigned_by_id`),
  KEY `patient_id` (`patient_id`),
  KEY `idx_nt_task_id` (`task_id`),
  KEY `idx_nt_nurse` (`nurse_id`),
  KEY `idx_nt_status` (`status`),
  KEY `idx_nt_priority` (`priority`),
  KEY `idx_nt_due` (`due_time`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task assignments from doctors/admins to nurses';

--
-- Dumping data for table `nurse_tasks`
--

INSERT INTO `nurse_tasks` (`id`, `task_id`, `nurse_id`, `assigned_by_id`, `assigned_by_role`, `patient_id`, `task_title`, `task_description`, `priority`, `due_time`, `status`, `completion_notes`, `completed_at`, `created_at`, `updated_at`) VALUES
(21, 'TASK-00001', 10, 20, 'doctor', NULL, 'Vitals Check Ward A', 'Perform morning vitals for all patients in Ward A', 'High', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(22, 'TASK-00002', 10, 20, 'doctor', NULL, 'Medication Round', 'Administer 10 AM medications', 'Urgent', '2026-04-14 00:47:53', 'In Progress', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(23, 'TASK-00003', 10, 20, 'doctor', NULL, 'Dressing Change Room 5', 'Change sterile bandage for Patient Pat-105', 'Medium', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(24, 'TASK-00004', 10, 20, 'doctor', NULL, 'Patient Education Pt 108', 'Instruct patient on post-op care', 'Low', '2026-04-14 00:47:53', 'Completed', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(25, 'TASK-00005', 10, 20, 'doctor', NULL, 'Sample Collection', 'Draw blood samples for Patient Pat-102', 'High', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(26, 'TASK-00006', 10, 20, 'doctor', NULL, 'Inventory Count', 'Check nursing station supplies for reorder', 'Medium', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(27, 'TASK-00007', 10, 20, 'doctor', NULL, 'Admission Assessment', 'Onboard newly admitted patient in Room 12', 'High', '2026-04-14 00:47:53', 'Completed', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(28, 'TASK-00008', 10, 20, 'doctor', NULL, 'Handover Report', 'Prepare detailed report for night shift', 'High', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(29, 'TASK-00009', 10, 20, 'doctor', NULL, 'Sanitization Audit', 'Verify all equipment in Room 3 is sterilized', 'Low', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53'),
(30, 'TASK-00010', 10, 20, 'doctor', NULL, 'IV Site Maintenance', 'Check IV lines for all surgical ward patients', 'High', '2026-04-14 00:47:53', 'Pending', NULL, NULL, '2026-04-14 00:47:53', '2026-04-14 00:47:53');

--
-- Triggers `nurse_tasks`
--
DROP TRIGGER IF EXISTS `before_insert_nurse_tasks`;
DELIMITER $$
CREATE TRIGGER `before_insert_nurse_tasks` BEFORE INSERT ON `nurse_tasks` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(task_id, 6) AS UNSIGNED)), 0) + 1
  INTO next_id
  FROM nurse_tasks;
  SET NEW.task_id = CONCAT('TASK-', LPAD(next_id, 5, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_notes`
--

DROP TABLE IF EXISTS `nursing_notes`;
CREATE TABLE IF NOT EXISTS `nursing_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `note_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `shift_id` int DEFAULT NULL COMMENT 'FK → nurse_shifts.id',
  `note_type` enum('General','Observation','Wound Care','Behavior','Incident','Handover','Assessment') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `note_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL COMMENT 'Array of file paths',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Locked after shift ends',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_id` (`note_id`),
  KEY `shift_id` (`shift_id`),
  KEY `idx_nn_note_id` (`note_id`),
  KEY `idx_nn_nurse` (`nurse_id`),
  KEY `idx_nn_patient` (`patient_id`),
  KEY `idx_nn_type` (`note_type`),
  KEY `idx_nn_locked` (`is_locked`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nursing clinical notes (auto-locked after shift ends)';

--
-- Dumping data for table `nursing_notes`
--

INSERT INTO `nursing_notes` (`id`, `note_id`, `nurse_id`, `patient_id`, `shift_id`, `note_type`, `note_content`, `attachments`, `is_locked`, `created_at`, `locked_at`) VALUES
(1, 'NTE-001', 10, 101, NULL, 'Observation', 'Patient stable, oxygen saturation normal.', NULL, 0, '2026-04-14 00:39:58', NULL),
(2, 'NTE-002', 10, 102, NULL, 'Assessment', 'Reported decreased lung sounds on left side.', NULL, 0, '2026-04-14 00:39:58', NULL),
(3, 'NTE-003', 10, 103, NULL, 'General', 'Temperature 37.2C, pulse 78 bpm.', NULL, 0, '2026-04-14 00:39:58', NULL),
(4, 'NTE-004', 10, 104, NULL, 'Behavior', 'Patient cooperative, adhering to medication.', NULL, 0, '2026-04-14 00:39:58', NULL),
(5, 'NTE-005', 10, 105, NULL, 'Incident', 'Minor slip in room, no injuries sustained.', NULL, 0, '2026-04-14 00:39:58', NULL),
(6, 'NTE-006', 10, 106, NULL, 'General', 'Dressing changed on right arm wound.', NULL, 0, '2026-04-14 00:39:58', NULL),
(7, 'NTE-007', 10, 107, NULL, 'Assessment', 'Complaining of mild nausea after breakfast.', NULL, 0, '2026-04-14 00:39:58', NULL),
(8, 'NTE-008', 10, 108, NULL, 'Observation', 'BP slightly elevated: 145/95.', NULL, 0, '2026-04-14 00:39:58', NULL),
(9, 'NTE-009', 10, 109, NULL, 'Handover', 'Transitioning care to night shift; stable condition.', NULL, 0, '2026-04-14 00:39:58', NULL),
(10, 'NTE-010', 10, 110, NULL, 'Observation', 'Sleeping soundly; vitals within range.', NULL, 0, '2026-04-14 00:39:58', NULL);

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
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token_hash`, `is_used`, `expires_at`, `ip_address`, `created_at`) VALUES
(1, 26, '5375c7f03b9d64b407d539635c75b3c059ce7d93bea0350ba55a33533c509afb', 0, '2026-03-25 19:56:08', '::1', '2026-03-25 19:26:08'),
(2, 15, '24bc0920c75a7d4780ae4fcef29748270a27b9d4fe611f889c9e5af33f9b30f2', 0, '2026-04-04 10:07:17', '::1', '2026-04-04 09:37:17'),
(3, 28, '20acc43bfe24a7805059a800329d4d5fee4057ff6840d4de544533eeb23c5d3b', 0, '2026-04-09 19:22:59', '::1', '2026-04-09 18:52:59'),
(4, 35, '526ca6e9aa64b55d065586678d7e827a78452aa11a4eb3fa5611f51a779e6e23', 0, '2026-04-09 19:27:04', '::1', '2026-04-09 18:57:04'),
(5, 36, '0f13ba123b54390e8b26835fbf77f2cefdae6ea1c84bd3dc83b6c12c82edaf29', 1, '2026-04-11 00:43:15', '::1', '2026-04-11 00:13:15'),
(6, 36, '7dcb8e58f35e3899781a90d9a83eda1212bff6b3d86c5e8ae9a59cab07f8ea34', 1, '2026-04-11 22:00:59', '::1', '2026-04-11 21:30:59'),
(7, 36, '5f3ba2586d7ee1841c049078c1a94f3484f1a9c2608f8bec8725ffca73d6eb36', 1, '2026-04-11 22:15:57', '::1', '2026-04-11 21:45:57'),
(8, 36, '3187792b00677584a8f10b12f47cbf8968ef71eddb2fd4afbfc58ad83e835cd6', 1, '2026-04-17 05:53:11', '::1', '2026-04-17 05:23:11'),
(9, 36, 'e1534698f79aa5e452b1e78963a943484a141ead3aefe2dad851db4f6477ec5c', 1, '2026-04-17 06:14:32', '::1', '2026-04-17 05:44:32'),
(10, 36, 'bc4e4a68dedd9f1b6e9ef4d91fa43362f10d3c19b66e1143a02eaedcf4cf254e', 0, '2026-04-17 07:42:10', '::1', '2026-04-17 07:12:10');

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
  `ward_department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_doctor` int DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `fk_patient_assigned_doctor` (`assigned_doctor`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `patient_id`, `student_id`, `is_student`, `blood_group`, `allergies`, `chronic_conditions`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `insurance_provider`, `insurance_number`, `profile_photo`, `registration_status`, `nationality`, `religion`, `marital_status`, `occupation`, `national_id`, `secondary_phone`, `personal_email`, `street_address`, `city`, `region`, `country`, `postal_code`, `created_at`, `updated_at`, `last_login_at`, `is_online`, `profile_completion`, `account_status`, `full_name`, `gender`, `age`, `patient_type`, `admit_date`, `ward_department`, `assigned_doctor`, `date_of_birth`) VALUES
(5, 36, 'PAT-46CB95', NULL, 0, 'O+', NULL, NULL, 'Francisca Baidoo', '0557303391', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-11 00:11:32', '2026-04-11 00:11:32', NULL, 0, 0, 'active', 'Lovelace John Kwaku Baidoo', 'Male', NULL, 'Student', NULL, NULL, NULL, NULL),
(6, 42, 'PAT-7587', NULL, 0, 'AB-', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, '0247756016', NULL, '20 Accra, Ghana', NULL, NULL, 'Ghana', NULL, '2025-05-21 14:03:07', '2026-04-12 10:26:48', NULL, 0, 0, 'active', 'Adjoa Yeboah', 'Female', NULL, NULL, NULL, NULL, NULL, '1967-06-24'),
(7, 35, 'PAT-8604', NULL, 0, 'O+', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, '0243260955', NULL, '23 Accra, Ghana', NULL, NULL, 'Ghana', NULL, '2025-06-07 18:32:59', '2026-04-12 10:26:48', NULL, 0, 0, 'active', 'Osei Antwi', 'Male', NULL, NULL, NULL, NULL, NULL, '1966-08-29'),
(8, 28, 'PAT-9191', NULL, 0, 'O-', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, '0243550870', NULL, '1 Accra, Ghana', NULL, NULL, 'Ghana', NULL, '2025-10-15 06:14:27', '2026-04-12 10:26:48', NULL, 0, 0, 'active', 'Daniel Antwi', 'Male', NULL, NULL, NULL, NULL, NULL, '2005-04-17'),
(9, 38, 'PAT-8061', NULL, 0, 'A+', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, '0242085266', NULL, '12 Accra, Ghana', NULL, NULL, 'Ghana', NULL, '2026-01-31 23:03:16', '2026-04-12 10:26:48', NULL, 0, 0, 'active', 'Adjoa Appiah', 'Female', NULL, NULL, NULL, NULL, NULL, '1988-01-05'),
(10, 37, 'PAT-9124', NULL, 0, 'O+', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, '0243007520', NULL, '14 Accra, Ghana', NULL, NULL, 'Ghana', NULL, '2025-07-07 10:29:52', '2026-04-12 10:26:48', NULL, 0, 0, 'active', 'Kofi Adu', 'Male', NULL, NULL, NULL, NULL, NULL, '2003-09-20'),
(101, 101, 'RMU-PAT-101', NULL, 0, 'O+', 'Peanuts', 'None', 'Peter Doe', '0550001001', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 102, 'RMU-PAT-102', NULL, 0, 'A-', 'Latex', 'Asthma', 'Mary Smith', '0550001002', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 103, 'RMU-PAT-103', NULL, 0, 'B+', 'Penicillin', 'None', 'David Wonder', '0550001003', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 104, 'RMU-PAT-104', NULL, 0, 'AB+', 'None', 'Hypertension', 'Wendy Builder', '0550001004', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 105, 'RMU-PAT-105', NULL, 0, 'O-', 'Dust', 'None', 'Sally Brown', '0550001005', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(106, 106, 'RMU-PAT-106', NULL, 0, 'A+', 'None', 'None', 'Hippolyta Prince', '0550001006', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, 107, 'RMU-PAT-107', NULL, 0, 'O+', 'Milk', 'None', 'Alphonse Elric', '0550001007', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, 108, 'RMU-PAT-108', NULL, 0, 'B-', 'None', 'None', 'King Harold', '0550001008', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(109, 109, 'RMU-PAT-109', NULL, 0, 'AB-', 'None', 'Hyperacidity', 'Jane Jetson', '0550001009', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(110, 110, 'RMU-PAT-110', NULL, 0, 'O+', 'None', 'None', 'Robby Stewart', '0550001010', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-14 00:10:52', '2026-04-14 00:10:52', NULL, 0, 0, 'active', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 307, 'PAT-F558DD', NULL, 0, 'O+', NULL, NULL, 'Baidoo Lovelace', '0257669095', NULL, NULL, NULL, NULL, 'Active', 'Ghanaian', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, '2026-04-15 12:15:15', '2026-04-15 12:15:15', NULL, 0, 0, 'active', 'Samuel Enguah', 'Male', NULL, 'Staff', NULL, NULL, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_activity_log`
--

INSERT INTO `patient_activity_log` (`id`, `patient_id`, `user_id`, `action_type`, `action_description`, `ip_address`, `device_info`, `created_at`) VALUES
(5, 5, 28, 'update', 'Changed password', '150.90.171.99', 'Chrome/120 Windows', '2026-02-20 03:28:08'),
(6, 5, 28, 'booking', 'Logged in', '152.48.222.16', 'Chrome/120 Windows', '2026-03-28 23:50:11'),
(7, 5, 36, 'login', 'Changed password', '145.178.208.216', 'Chrome/120 Windows', '2026-03-20 09:40:17'),
(8, 5, 26, 'download', 'Changed password', '152.17.216.174', 'Chrome/120 Windows', '2026-03-21 23:22:59'),
(9, 5, 20, 'update', 'Updated emergency contact', '108.188.190.181', 'Chrome/120 Windows', '2026-03-02 16:44:11'),
(10, 5, 26, 'update', 'Cancelled appointment', '124.5.89.203', 'Chrome/120 Windows', '2026-03-19 02:03:08'),
(11, 5, 35, 'booking', 'Viewed lab results', '169.179.184.131', 'Chrome/120 Windows', '2026-04-11 15:37:33'),
(12, 5, 20, 'payment', 'Submitted refill request', '114.20.108.132', 'Chrome/120 Windows', '2026-02-17 00:40:47'),
(13, 5, 1, 'download', 'Viewed prescription', '140.208.187.4', 'Chrome/120 Windows', '2026-03-29 23:48:25'),
(14, 5, 28, 'payment', 'Updated emergency contact', '137.9.15.13', 'Chrome/120 Windows', '2026-02-25 03:08:08'),
(15, 5, 37, 'update', 'Downloaded invoice', '139.14.45.1', 'Chrome/120 Windows', '2026-03-15 11:53:25'),
(16, 5, 38, 'payment', 'Changed password', '132.219.154.249', 'Chrome/120 Windows', '2026-03-20 18:12:47'),
(17, 5, 42, 'view', 'Cancelled appointment', '188.189.13.139', 'Chrome/120 Windows', '2026-04-06 07:42:33'),
(18, 5, 40, 'login', 'Viewed lab results', '165.250.216.76', 'Chrome/120 Windows', '2026-03-20 15:56:40'),
(19, 5, 46, 'login', 'Changed password', '114.217.237.42', 'Chrome/120 Windows', '2026-03-17 00:48:07'),
(20, 5, 38, 'view', 'Downloaded invoice', '150.40.32.92', 'Chrome/120 Windows', '2026-04-04 19:03:58'),
(21, 5, 35, 'payment', 'Changed password', '106.223.187.121', 'Chrome/120 Windows', '2026-03-14 22:36:35'),
(22, 5, 35, 'booking', 'Booked appointment', '113.111.176.154', 'Chrome/120 Windows', '2026-04-11 18:28:37'),
(23, 5, 44, 'login', 'Logged in', '143.174.74.224', 'Chrome/120 Windows', '2026-03-28 10:54:30'),
(24, 5, 28, 'booking', 'Viewed prescription', '156.216.53.185', 'Chrome/120 Windows', '2026-03-31 15:42:56'),
(25, 9, 43, 'booking', 'Updated medical profile', '144.46.10.128', 'Chrome/120 Windows', '2026-02-18 00:45:07'),
(26, 10, 40, 'download', 'Viewed lab results', '148.169.117.213', 'Chrome/120 Windows', '2026-04-07 18:26:18'),
(27, 10, 43, 'download', 'Updated emergency contact', '129.176.30.52', 'Chrome/120 Windows', '2026-04-12 00:42:29'),
(28, 8, 39, 'update', 'Logged in', '167.62.143.155', 'Chrome/120 Windows', '2026-02-22 15:41:33'),
(29, 7, 37, 'view', 'Viewed lab results', '166.43.89.48', 'Chrome/120 Windows', '2026-03-23 14:56:59'),
(30, 9, 36, 'update', 'Updated emergency contact', '172.2.215.17', 'Chrome/120 Windows', '2026-03-08 01:41:20'),
(31, 9, 42, 'booking', 'Logged in', '104.134.8.205', 'Chrome/120 Windows', '2026-02-13 23:11:01'),
(32, 8, 45, 'login', 'Updated medical profile', '143.153.253.9', 'Chrome/120 Windows', '2026-03-08 21:05:18'),
(33, 9, 28, 'view', 'Logged in', '105.101.57.60', 'Chrome/120 Windows', '2026-03-01 22:16:30'),
(34, 6, 44, 'payment', 'Downloaded invoice', '170.40.179.244', 'Chrome/120 Windows', '2026-03-12 09:03:47');

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
  `education_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `education_topic` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` enum('Verbal','Written','Demonstration','Video','Group Session') COLLATE utf8mb4_unicode_ci DEFAULT 'Verbal',
  `materials_provided` json DEFAULT NULL COMMENT 'Array of material descriptions/paths',
  `understanding_level` enum('Good','Fair','Poor','Unable to Assess') COLLATE utf8mb4_unicode_ci DEFAULT 'Good',
  `requires_follow_up` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `education_id` (`education_id`),
  KEY `idx_pe_edu_id` (`education_id`),
  KEY `idx_pe_patient` (`patient_id`),
  KEY `idx_pe_nurse` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient health education sessions recorded by nurses';

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Table structure for table `patient_tests`
--

DROP TABLE IF EXISTS `patient_tests`;
CREATE TABLE IF NOT EXISTS `patient_tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `test_service_id` int DEFAULT NULL,
  `test_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('routine','urgent','stat') COLLATE utf8mb4_unicode_ci DEFAULT 'routine',
  `status` enum('requested','sample_collected','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'requested',
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sample_collected_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `result` text COLLATE utf8mb4_unicode_ci,
  `result_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_range` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_critical` tinyint(1) DEFAULT '0',
  `critical_notified` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lab_technician_id` int DEFAULT NULL,
  `ward` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bed_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `lab_technician_id` (`lab_technician_id`),
  KEY `test_service_id` (`test_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_vitals`
--

DROP TABLE IF EXISTS `patient_vitals`;
CREATE TABLE IF NOT EXISTS `patient_vitals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vital_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bp_systolic` decimal(5,1) DEFAULT NULL COMMENT 'mmHg',
  `bp_diastolic` decimal(5,1) DEFAULT NULL COMMENT 'mmHg',
  `pulse_rate` decimal(5,1) DEFAULT NULL COMMENT 'bpm',
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Celsius',
  `oxygen_saturation` decimal(4,1) DEFAULT NULL COMMENT 'SpO2 %',
  `respiratory_rate` decimal(4,1) DEFAULT NULL COMMENT 'breaths/min',
  `blood_glucose` decimal(6,1) DEFAULT NULL COMMENT 'mg/dL',
  `weight` decimal(5,1) DEFAULT NULL COMMENT 'kg',
  `height` decimal(5,1) DEFAULT NULL COMMENT 'cm',
  `bmi` decimal(4,1) DEFAULT NULL COMMENT 'Auto-calculated',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `flag_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vital_id` (`vital_id`),
  KEY `idx_pv_vital_id` (`vital_id`),
  KEY `idx_pv_patient` (`patient_id`),
  KEY `idx_pv_nurse` (`nurse_id`),
  KEY `idx_pv_recorded` (`recorded_at`),
  KEY `idx_pv_flagged` (`is_flagged`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient vital signs recorded by nurses with auto-flagging';

--
-- Dumping data for table `patient_vitals`
--

INSERT INTO `patient_vitals` (`id`, `vital_id`, `patient_id`, `nurse_id`, `recorded_at`, `bp_systolic`, `bp_diastolic`, `pulse_rate`, `temperature`, `oxygen_saturation`, `respiratory_rate`, `blood_glucose`, `weight`, `height`, `bmi`, `notes`, `is_flagged`, `flag_reason`, `doctor_notified`, `created_at`) VALUES
(1, 'VIT-79764', 5, 1, '2026-04-12 09:28:31', 128.0, 90.0, 105.0, 39.0, 94.0, 17.0, 150.0, 57.0, 176.0, NULL, NULL, 0, NULL, 0, '2026-01-12 23:27:43'),
(2, 'VIT-26671', 5, 1, '2026-02-12 13:09:03', 137.0, 91.0, 75.0, 38.7, 100.0, 16.0, 109.0, 71.0, 195.0, NULL, NULL, 0, NULL, 0, '2026-03-25 08:39:10'),
(3, 'VIT-52331', 5, 1, '2026-03-15 03:37:35', 152.0, 81.0, 60.0, 37.3, 100.0, 19.0, 125.0, 66.0, 177.0, NULL, NULL, 0, NULL, 0, '2026-04-06 12:25:38'),
(4, 'VIT-35113', 5, 1, '2026-01-31 11:27:41', 138.0, 78.0, 72.0, 37.1, 99.0, 22.0, 88.0, 103.0, 175.0, NULL, NULL, 0, NULL, 0, '2026-02-24 16:20:30'),
(5, 'VIT-25880', 5, 1, '2026-03-25 04:58:40', 145.0, 67.0, 81.0, 38.4, 95.0, 18.0, 166.0, 71.0, 185.0, NULL, NULL, 0, NULL, 0, '2026-03-03 23:23:06'),
(6, 'VIT-96235', 5, 1, '2026-03-16 06:03:08', 127.0, 94.0, 87.0, 38.6, 100.0, 20.0, 170.0, 59.0, 187.0, NULL, NULL, 0, NULL, 0, '2026-04-07 02:18:08'),
(7, 'VIT-17744', 5, 1, '2026-01-23 02:53:30', 103.0, 71.0, 70.0, 38.6, 98.0, 18.0, 71.0, 51.0, 177.0, NULL, NULL, 0, NULL, 0, '2026-02-15 15:19:10'),
(8, 'VIT-57525', 5, 1, '2026-01-17 19:46:13', 113.0, 82.0, 103.0, 37.4, 96.0, 21.0, 91.0, 52.0, 193.0, NULL, NULL, 1, NULL, 0, '2026-03-27 18:54:49'),
(9, 'VIT-55457', 5, 1, '2026-03-01 18:36:44', 134.0, 68.0, 91.0, 38.5, 97.0, 18.0, 175.0, 91.0, 156.0, NULL, NULL, 1, NULL, 0, '2026-03-07 01:14:24'),
(10, 'VIT-84886', 5, 1, '2026-02-27 11:14:34', 105.0, 95.0, 88.0, 37.4, 99.0, 17.0, 176.0, 81.0, 193.0, NULL, NULL, 0, NULL, 0, '2026-02-26 02:12:01'),
(11, 'VIT-24894', 5, 1, '2026-03-22 02:33:47', 142.0, 72.0, 74.0, 38.9, 99.0, 22.0, 140.0, 109.0, 191.0, NULL, NULL, 0, NULL, 0, '2026-03-22 04:33:52'),
(12, 'VIT-73360', 9, 1, '2026-03-23 08:10:03', 109.0, 75.0, 66.0, 38.6, 95.0, 17.0, 159.0, 91.0, 190.0, NULL, NULL, 0, NULL, 0, '2026-02-21 19:45:19'),
(13, 'VIT-84868', 7, 1, '2026-03-18 15:30:57', 120.0, 77.0, 72.0, 36.8, 100.0, 18.0, 142.0, 108.0, 176.0, NULL, NULL, 0, NULL, 0, '2026-04-01 07:11:34'),
(14, 'VIT-48091', 5, 1, '2026-01-14 13:42:51', 123.0, 95.0, 84.0, 37.3, 95.0, 22.0, 138.0, 65.0, 158.0, NULL, NULL, 0, NULL, 0, '2026-01-15 13:42:50'),
(15, 'VIT-54987', 6, 1, '2026-01-26 17:41:52', 140.0, 84.0, 93.0, 37.8, 97.0, 16.0, 130.0, 104.0, 174.0, NULL, NULL, 0, NULL, 0, '2026-01-19 04:01:08'),
(16, 'VIT-48876', 6, 1, '2026-03-11 18:32:46', 135.0, 80.0, 104.0, 37.1, 98.0, 16.0, 104.0, 52.0, 161.0, NULL, NULL, 0, NULL, 0, '2026-03-31 17:23:19'),
(17, 'VIT-85265', 9, 1, '2026-03-15 07:06:12', 111.0, 80.0, 81.0, 38.6, 97.0, 22.0, 122.0, 60.0, 185.0, NULL, NULL, 1, NULL, 0, '2026-03-12 01:39:25'),
(18, 'VIT-78834', 7, 1, '2026-02-06 12:01:48', 141.0, 88.0, 62.0, 36.7, 97.0, 17.0, 85.0, 92.0, 165.0, NULL, NULL, 0, NULL, 0, '2026-03-26 04:22:20'),
(19, 'VIT-43387', 6, 1, '2026-02-19 09:45:23', 152.0, 66.0, 106.0, 38.8, 98.0, 15.0, 159.0, 104.0, 162.0, NULL, NULL, 1, NULL, 0, '2026-02-09 16:42:55'),
(20, 'VIT-51092', 8, 1, '2026-02-26 14:24:35', 109.0, 83.0, 67.0, 38.6, 99.0, 18.0, 106.0, 93.0, 155.0, NULL, NULL, 0, NULL, 0, '2026-01-28 14:43:08'),
(21, 'VIT-001', 101, 10, '2026-04-14 01:03:42', 120.0, 80.0, 72.0, 36.6, 98.5, NULL, NULL, 70.5, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(22, 'VIT-002', 102, 10, '2026-04-14 00:03:42', 140.0, 95.0, 88.0, 38.2, 94.0, NULL, NULL, 82.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(23, 'VIT-003', 103, 10, '2026-04-13 23:03:42', 115.0, 75.0, 65.0, 36.5, 99.0, NULL, NULL, 58.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(24, 'VIT-004', 104, 10, '2026-04-13 22:03:42', 130.0, 85.0, 80.0, 36.8, 97.5, NULL, NULL, 95.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(25, 'VIT-005', 105, 10, '2026-04-13 21:03:42', 110.0, 70.0, 70.0, 36.7, 98.0, NULL, NULL, 65.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(26, 'VIT-006', 106, 10, '2026-04-13 20:03:42', 125.0, 82.0, 74.0, 36.6, 99.1, NULL, NULL, 62.5, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(27, 'VIT-007', 107, 10, '2026-04-13 19:03:42', 118.0, 78.0, 76.0, 36.5, 98.8, NULL, NULL, 77.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(28, 'VIT-008', 108, 10, '2026-04-13 18:03:42', 135.0, 90.0, 85.0, 37.1, 96.5, NULL, NULL, 88.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(29, 'VIT-009', 109, 10, '2026-04-13 17:03:42', 122.0, 80.0, 72.0, 36.6, 98.2, NULL, NULL, 75.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42'),
(30, 'VIT-010', 110, 10, '2026-04-13 16:03:42', 120.0, 75.0, 68.0, 36.4, 99.5, NULL, NULL, 52.0, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14 01:03:42');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RMU-[YmdHis]-[6-char random]',
  `invoice_id` int UNSIGNED NOT NULL,
  `patient_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'GHS',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `payment_method` enum('Cash','Mobile Money','Card','Bank Transfer','Insurance','Paystack','Cheque','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` datetime NOT NULL,
  `status` enum('Pending','Completed','Failed','Refunded','Partially Refunded','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `paystack_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Paystack txn reference if online',
  `paystack_response` json DEFAULT NULL COMMENT 'Raw Paystack verification JSON',
  `receipt_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RMU-RCT-YYYYMMDD-NNNN',
  `receipt_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PDF path under /uploads/receipts/',
  `processed_by` int DEFAULT NULL COMMENT 'Finance staff user_id (NULL for online)',
  `channel` enum('Online','Counter','Mobile','Auto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Counter',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `reconciled` tinyint(1) NOT NULL DEFAULT '0',
  `reconciled_at` datetime DEFAULT NULL,
  `reconciled_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_payment_reference` (`payment_reference`),
  UNIQUE KEY `uq_receipt_number` (`receipt_number`),
  KEY `idx_payment_invoice` (`invoice_id`),
  KEY `idx_payment_patient` (`patient_id`),
  KEY `idx_payment_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_paystack` (`paystack_reference`),
  KEY `fk_payment_processor` (`processed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='All payment transactions — online (Paystack) and manual';

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `payment_reference`, `invoice_id`, `patient_id`, `amount`, `currency`, `payment_method`, `payment_date`, `status`, `paystack_reference`, `paystack_response`, `receipt_number`, `receipt_path`, `processed_by`, `channel`, `notes`, `reconciled`, `reconciled_at`, `reconciled_by`, `created_at`, `updated_at`) VALUES
(1, 'PAY-B2CA3FF474', 6, 5, 569.00, 'GHS', 'Card', '2026-03-17 18:20:13', 'Failed', NULL, NULL, 'RCP-97981', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-15 08:51:14', '2026-04-12 10:14:05'),
(2, 'PAY-AFE4DA517A', 7, 5, 1463.00, 'GHS', 'Paystack', '2026-01-23 10:58:32', 'Completed', NULL, NULL, 'RCP-76697', NULL, NULL, 'Counter', 'Payment received at cashier.', 0, NULL, NULL, '2026-01-29 15:06:47', '2026-04-12 10:14:05'),
(3, 'PAY-4662E247B9', 6, 5, 1788.00, 'GHS', 'Cash', '2026-01-12 12:44:09', 'Pending', NULL, NULL, 'RCP-29646', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-25 10:18:36', '2026-04-12 10:14:05'),
(4, 'PAY-C0957617A5', 9, 5, 451.00, 'GHS', 'Paystack', '2026-01-31 03:17:58', 'Pending', NULL, NULL, 'RCP-62796', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-03 15:25:45', '2026-04-12 10:14:05'),
(5, 'PAY-4A8618B30B', 6, 5, 476.00, 'GHS', 'Cash', '2026-03-28 09:19:42', 'Failed', NULL, NULL, 'RCP-21741', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-01-25 23:26:42', '2026-04-12 10:14:05'),
(6, 'PAY-5F8A7ED838', 9, 5, 674.00, 'GHS', 'Paystack', '2026-01-19 23:09:46', 'Failed', NULL, NULL, 'RCP-82205', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-01-24 02:14:12', '2026-04-12 10:14:05'),
(7, 'PAY-F340D7EE56', 5, 5, 668.00, 'GHS', 'Paystack', '2026-03-18 09:03:45', 'Pending', NULL, NULL, 'RCP-29958', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-19 11:27:34', '2026-04-12 10:14:05'),
(8, 'PAY-05186A9EFA', 4, 5, 1580.00, 'GHS', 'Mobile Money', '2026-01-27 17:37:00', 'Completed', NULL, NULL, 'RCP-10797', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-10 17:04:30', '2026-04-12 10:14:05'),
(9, 'PAY-0EB7AAADAA', 17, 5, 1157.00, 'GHS', 'Mobile Money', '2026-04-11 23:13:48', 'Failed', NULL, NULL, 'RCP-68614', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-01-29 20:34:04', '2026-04-12 10:22:48'),
(10, 'PAY-4753B33BCC', 19, 5, 1257.00, 'GHS', 'Cash', '2026-01-23 21:39:21', 'Completed', NULL, NULL, 'RCP-57170', NULL, NULL, 'Counter', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-27 02:17:13', '2026-04-12 10:22:48'),
(11, 'PAY-D15BC149EB', 12, 5, 1969.00, 'GHS', 'Mobile Money', '2026-01-30 23:35:27', 'Pending', NULL, NULL, 'RCP-82575', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-21 16:07:16', '2026-04-12 10:22:48'),
(12, 'PAY-0C43B78EC7', 19, 5, 1347.00, 'GHS', 'Paystack', '2026-02-07 07:52:42', 'Failed', NULL, NULL, 'RCP-56363', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-11 11:14:43', '2026-04-12 10:22:48'),
(13, 'PAY-0C4460CA97', 12, 5, 1683.00, 'GHS', 'Card', '2026-03-10 08:47:33', 'Pending', NULL, NULL, 'RCP-49633', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-07 18:50:37', '2026-04-12 10:22:48'),
(14, 'PAY-31756F0098', 17, 5, 129.00, 'GHS', 'Paystack', '2026-03-10 22:12:04', 'Completed', NULL, NULL, 'RCP-45603', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-31 10:07:44', '2026-04-12 10:22:48'),
(15, 'PAY-8E5CE0259B', 19, 5, 857.00, 'GHS', 'Cash', '2026-04-11 23:23:17', 'Failed', NULL, NULL, 'RCP-79666', NULL, NULL, 'Counter', 'Payment received at cashier.', 0, NULL, NULL, '2026-04-09 22:37:46', '2026-04-12 10:22:48'),
(16, 'PAY-D2DE47276E', 17, 5, 860.00, 'GHS', 'Cash', '2026-01-14 10:24:24', 'Failed', NULL, NULL, 'RCP-41453', NULL, NULL, 'Counter', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-14 22:33:50', '2026-04-12 10:22:48'),
(17, 'PAY-215427B5C9', 28, 6, 1961.00, 'GHS', 'Cash', '2026-03-06 00:45:52', 'Pending', NULL, NULL, 'RCP-12499', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-30 13:42:14', '2026-04-12 10:26:48'),
(18, 'PAY-9DF88D0FDE', 30, 10, 332.00, 'GHS', 'Mobile Money', '2026-03-19 00:35:09', 'Failed', NULL, NULL, 'RCP-58445', NULL, NULL, 'Counter', 'Payment received at cashier.', 0, NULL, NULL, '2026-01-31 13:25:41', '2026-04-12 10:26:48'),
(19, 'PAY-83FF6CBD27', 24, 6, 1837.00, 'GHS', 'Cash', '2026-03-25 13:37:11', 'Pending', NULL, NULL, 'RCP-34134', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-04-03 18:19:06', '2026-04-12 10:26:48'),
(20, 'PAY-DA5D2ACBD3', 1, 5, 1098.00, 'GHS', 'Cash', '2026-02-11 08:01:42', 'Failed', NULL, NULL, 'RCP-36726', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-13 02:16:25', '2026-04-12 10:26:48'),
(21, 'PAY-D68F3830B9', 25, 10, 643.00, 'GHS', 'Mobile Money', '2026-02-14 20:37:28', 'Completed', NULL, NULL, 'RCP-37130', NULL, NULL, 'Online', 'Payment received at cashier.', 0, NULL, NULL, '2026-03-03 22:09:28', '2026-04-12 10:26:48'),
(22, 'PAY-50DE55A5D9', 28, 10, 224.00, 'GHS', 'Card', '2026-02-28 00:20:11', 'Pending', NULL, NULL, 'RCP-41101', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-04-08 03:38:37', '2026-04-12 10:26:48'),
(23, 'PAY-40CCDA4285', 25, 8, 671.00, 'GHS', 'Mobile Money', '2026-01-29 03:02:06', 'Pending', NULL, NULL, 'RCP-99120', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-04-09 06:39:08', '2026-04-12 10:26:48'),
(24, 'PAY-496F528828', 21, 6, 1515.00, 'GHS', 'Cash', '2026-01-19 21:59:43', 'Failed', NULL, NULL, 'RCP-86803', NULL, NULL, 'Mobile', 'Payment received at cashier.', 0, NULL, NULL, '2026-02-17 15:48:30', '2026-04-12 10:26:48'),
(25, 'PAY-REF-001', 7, 101, 100.00, 'GHS', 'Cash', '2026-04-14 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Counter', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(26, 'PAY-REF-002', 10, 102, 1200.00, 'GHS', 'Mobile Money', '2026-04-13 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Mobile', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(27, 'PAY-REF-003', 16, 103, 75.00, 'GHS', 'Card', '2026-04-14 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Online', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(28, 'PAY-REF-004', 17, 104, 220.00, 'GHS', 'Bank Transfer', '2026-04-09 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Counter', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(29, 'PAY-REF-005', 19, 105, 150.00, 'GHS', 'Cash', '2026-04-04 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Counter', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(30, 'PAY-REF-006', 21, 106, 50.00, 'GHS', 'Paystack', '2026-04-12 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Online', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(31, 'PAY-REF-007', 28, 107, 210.00, 'GHS', 'Mobile Money', '2026-04-11 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Mobile', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(32, 'PAY-REF-008', 4, 108, 890.00, 'GHS', 'Insurance', '2026-04-07 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Counter', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(33, 'PAY-REF-009', 12, 109, 120.00, 'GHS', 'Cash', '2026-03-15 01:04:48', 'Completed', NULL, NULL, NULL, NULL, NULL, 'Counter', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48'),
(34, 'PAY-REF-010', 26, 110, 50.00, 'GHS', 'Mobile Money', '2026-04-14 01:04:48', 'Pending', NULL, NULL, NULL, NULL, NULL, 'Mobile', NULL, 0, NULL, NULL, '2026-04-14 01:04:48', '2026-04-14 01:04:48');

-- --------------------------------------------------------

--
-- Table structure for table `payment_waivers`
--

DROP TABLE IF EXISTS `payment_waivers`;
CREATE TABLE IF NOT EXISTS `payment_waivers` (
  `waiver_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `waiver_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RMU-WVR-YYYYMMDD-NNNN',
  `invoice_id` int UNSIGNED NOT NULL,
  `patient_id` int NOT NULL,
  `waiver_type` enum('Full','Partial','Student Discount','Staff Discount','Indigent','Hardship','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `waived_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supporting_docs` json DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`waiver_id`),
  UNIQUE KEY `uq_waiver_number` (`waiver_number`),
  KEY `idx_waiver_invoice` (`invoice_id`),
  KEY `idx_waiver_patient` (`patient_id`),
  KEY `idx_waiver_status` (`status`),
  KEY `fk_waiver_approved` (`approved_by`),
  KEY `fk_waiver_created` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment waiver requests and approvals';

-- --------------------------------------------------------

--
-- Table structure for table `paystack_config`
--

DROP TABLE IF EXISTS `paystack_config`;
CREATE TABLE IF NOT EXISTS `paystack_config` (
  `config_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. public_key, secret_key, webhook_secret',
  `config_value` varbinary(512) NOT NULL COMMENT 'AES-256 encrypted value',
  `environment` enum('test','live') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'test',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `uq_paystack_config_key` (`config_key`,`environment`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paystack API credentials — values AES-256 encrypted';

--
-- Dumping data for table `paystack_config`
--

INSERT INTO `paystack_config` (`config_id`, `config_key`, `config_value`, `environment`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, 'public_key', 0x322e7bd201f1e420fac8f0eb08bbe351ce5622e767c0000410b28830f769eed1, 'test', 1, 'Paystack public/publishable key', '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(2, 'secret_key', 0xfeae7285139a40265bdd21e0a08d7239ce5622e767c0000410b28830f769eed1, 'test', 1, 'Paystack secret key', '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(3, 'webhook_secret', 0x0d446813ce8dbee59816f0b6757f42b9815d418a2290e6ca76bfd2b6505842b8, 'test', 1, 'Paystack webhook signing secret', '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(4, 'callback_url', 0xd82ddd95b61e272a9edc65d5499d68bdc82d135725d7572fa473d78841868e1534cad4547948d05088d126eb073f1fa54d648dfa667e2d43bca56ff67899317f986b04125bd3098df9ef4950482423de, 'test', 1, 'Payment callback URL', '2026-04-08 02:08:30', '2026-04-08 02:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `paystack_transactions`
--

DROP TABLE IF EXISTS `paystack_transactions`;
CREATE TABLE IF NOT EXISTS `paystack_transactions` (
  `transaction_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` int UNSIGNED DEFAULT NULL,
  `paystack_reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Reference sent to Paystack',
  `paystack_access_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paystack_txn_id` bigint DEFAULT NULL COMMENT 'Paystack internal transaction ID',
  `amount_pesewas` bigint NOT NULL DEFAULT '0' COMMENT 'Amount in pesewas sent to API',
  `amount_ghs` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Equivalent GHS for DB records',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'card, bank, mobile_money, ussd, etc.',
  `gateway_response` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Initialized','Pending','Success','Failed','Abandoned','Reversed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Initialized',
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Webhook event: charge.success, etc.',
  `paystack_raw_response` json DEFAULT NULL COMMENT 'Full raw Paystack JSON payload',
  `webhook_received_at` datetime DEFAULT NULL,
  `webhook_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_signature_valid` tinyint(1) DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Custom metadata sent to Paystack',
  `paid_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `uq_paystack_ref` (`paystack_reference`),
  KEY `idx_paystack_payment` (`payment_id`),
  KEY `idx_paystack_status` (`status`),
  KEY `idx_paystack_txnid` (`paystack_txn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paystack API transaction log — every API call and webhook event';

-- --------------------------------------------------------

--
-- Table structure for table `permission_matrix`
--

DROP TABLE IF EXISTS `permission_matrix`;
CREATE TABLE IF NOT EXISTS `permission_matrix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','doctor','patient','staff','pharmacist','nurse','lab_technician','finance_officer','finance_manager') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_read` tinyint(1) DEFAULT '0',
  `can_write` tinyint(1) DEFAULT '0',
  `can_update` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0',
  `is_restricted` tinyint(1) DEFAULT '0',
  `restricted_fields` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module` (`role`,`module_name`)
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_activity_log`
--

INSERT INTO `pharmacist_activity_log` (`id`, `pharmacist_id`, `action_type`, `action_description`, `ip_address`, `device_info`, `created_at`) VALUES
(1, 2, 'report', 'Generated report: inventory_status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 13:39:55'),
(2, 6, 'report', 'Generated report: inventory_status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 09:57:39'),
(3, 6, 'alert', 'Resolved alert #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 10:02:10'),
(4, 6, 'alert', 'Resolved alert #10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 10:05:08');

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
  `pharmacy_staff_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pharm_user` (`user_id`),
  KEY `idx_pharm_license` (`license_number`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_profile`
--

INSERT INTO `pharmacist_profile` (`id`, `user_id`, `pharmacy_staff_id`, `designation`, `full_name`, `license_number`, `license_expiry`, `specialization`, `department`, `phone`, `secondary_phone`, `email`, `address`, `city`, `region`, `country`, `profile_photo`, `bio`, `years_of_experience`, `nationality`, `national_id`, `date_of_birth`, `gender`, `marital_status`, `availability_status`, `profile_completion`, `created_at`, `updated_at`, `postal_code`, `office_location`, `pharmacy_school`, `graduation_year`, `postgrad_training`, `license_issuing_body`, `personal_email`, `street_address`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(1, 6, NULL, NULL, 'Nelly Nartey', NULL, NULL, NULL, 'Pharmacy', '0501234567', NULL, 'nelly.nartey@st.rmu.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(2, 7, NULL, NULL, 'Adjei Adelaide Naa Adjeley', NULL, NULL, NULL, 'Pharmacy', '0507333138', NULL, 'es-anadjei@st.umat.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(5, 203, 'RMU-PHAR-203', 'Head Pharmacist', 'Pharm. Jemima Amanor', NULL, NULL, NULL, 'Pharmacy', '0596269993', NULL, 'jemimaamanor@gmail.com', NULL, NULL, NULL, 'Ghana', NULL, NULL, 8, NULL, NULL, NULL, 'Female', NULL, 'Offline', 0, '2026-04-14 00:27:00', '2026-04-17 09:52:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 1, '2026-04-17 09:52:57', NULL),
(6, 312, 'PHM-A0E57A', NULL, 'Lil Shurface', 'GA-HSP-458921', NULL, 'Clinical Pharmacy', '', NULL, NULL, NULL, NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, 'Male', NULL, 'Offline', 0, '2026-04-17 09:30:44', '2026-04-17 09:31:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 1, '2026-04-17 09:31:38', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacy_reports`
--

INSERT INTO `pharmacy_reports` (`id`, `generated_by`, `report_type`, `parameters`, `file_path`, `format`, `generated_at`) VALUES
(1, 7, 'inventory_status', '{\"end_date\": \"2026-03-12\", \"start_date\": \"2026-03-01\"}', 'uploads/pharmacy_reports/inventory_status_20260312_133955.csv', 'CSV', '2026-03-12 13:39:55'),
(2, 312, 'inventory_status', '{\"end_date\": \"2026-04-17\", \"start_date\": \"2026-04-01\"}', 'uploads/pharmacy_reports/inventory_status_20260417_095739.csv', 'CSV', '2026-04-17 09:57:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `prescription_id`, `patient_id`, `doctor_id`, `pharmacist_id`, `medical_record_id`, `prescription_date`, `medication_name`, `dosage`, `frequency`, `duration`, `instructions`, `quantity`, `refills_allowed`, `refill_count`, `status`, `patient_notified`, `dispensed_by`, `dispensed_date`, `created_at`, `updated_at`) VALUES
(1, 'RX-65985', 5, 4, NULL, NULL, '2026-02-12', 'Paracetamol', '500mg', 'Twice daily', '10 days', 'Take all medications as directed. Drink plenty of water.', 22, 2, 0, 'Dispensed', 0, NULL, NULL, '2026-03-08 01:10:43', '2026-04-12 10:22:48'),
(2, 'RX-81778', 5, 4, NULL, NULL, '2026-03-31', 'Omeprazole', '20mg', 'Every 8 hours', '7 days', 'Take all medications as directed. Drink plenty of water.', 12, 0, 0, 'Dispensed', 0, NULL, NULL, '2026-03-26 10:18:25', '2026-04-12 10:22:48'),
(3, 'RX-82237', 5, 4, NULL, NULL, '2026-01-16', 'Metformin', '250mg', 'Once daily', '14 days', 'Take all medications as directed. Drink plenty of water.', 21, 2, 0, 'Dispensed', 0, NULL, NULL, '2026-02-07 03:49:34', '2026-04-12 10:22:48'),
(4, 'RX-77230', 5, 4, NULL, NULL, '2026-01-16', 'Ciprofloxacin', '20mg', 'Every 12 hours', '3 days', 'Take all medications as directed. Drink plenty of water.', 15, 2, 0, 'Dispensed', 0, NULL, NULL, '2026-01-26 11:37:17', '2026-04-12 10:22:48'),
(5, 'RX-56093', 5, 4, NULL, NULL, '2026-03-19', 'Metformin', '250mg', 'Every 8 hours', '10 days', 'Take all medications as directed. Drink plenty of water.', 10, 0, 0, 'Dispensed', 0, NULL, NULL, '2026-04-07 03:23:02', '2026-04-12 10:22:48'),
(6, 'RX-24984', 5, 4, NULL, NULL, '2026-01-26', 'Chloroquine', '200mg', 'Every 12 hours', '5 days', 'Take all medications as directed. Drink plenty of water.', 18, 0, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-03-25 04:12:06', '2026-04-12 10:22:48'),
(7, 'RX-81972', 5, 4, NULL, NULL, '2026-01-20', 'Amoxicillin', '100mg', 'Every 12 hours', '3 days', 'Take all medications as directed. Drink plenty of water.', 19, 1, 0, 'Pending', 0, NULL, NULL, '2026-03-13 05:40:52', '2026-04-12 10:22:48'),
(8, 'RX-26079', 5, 4, NULL, NULL, '2026-01-23', 'Metformin', '5mg', 'Twice daily', '7 days', 'Take all medications as directed. Drink plenty of water.', 28, 2, 0, 'Pending', 0, NULL, NULL, '2026-02-13 15:28:09', '2026-04-12 10:22:48'),
(9, 'RX-81881', 5, 4, NULL, NULL, '2026-03-03', 'Chloroquine', '500mg', 'Every 8 hours', '14 days', 'Take all medications as directed. Drink plenty of water.', 13, 1, 0, 'Pending', 0, NULL, NULL, '2026-03-23 21:19:54', '2026-04-12 10:22:48'),
(10, 'RX-19560', 5, 4, NULL, NULL, '2026-01-17', 'Omeprazole', '250mg', 'Once daily', '7 days', 'Take all medications as directed. Drink plenty of water.', 12, 0, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-02-02 17:39:47', '2026-04-12 10:22:48'),
(11, 'RX-75199', 9, 4, NULL, NULL, '2026-03-22', 'Omeprazole', '250mg', 'Every 8 hours', '10 days', 'Take all medications as directed. Drink plenty of water.', 20, 1, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-02-05 14:21:05', '2026-04-12 10:26:48'),
(12, 'RX-45340', 10, 4, NULL, NULL, '2026-03-03', 'Azithromycin', '20mg', 'Every 8 hours', '10 days', 'Take all medications as directed. Drink plenty of water.', 22, 3, 0, 'Dispensed', 0, NULL, NULL, '2026-02-10 11:32:55', '2026-04-12 10:26:48'),
(13, 'RX-65636', 7, 4, NULL, NULL, '2026-03-29', 'Metformin', '500mg', 'Every 12 hours', '14 days', 'Take all medications as directed. Drink plenty of water.', 26, 3, 0, 'Dispensed', 0, NULL, NULL, '2026-01-17 05:59:42', '2026-04-12 10:26:48'),
(14, 'RX-26913', 10, 4, NULL, NULL, '2026-03-28', 'Paracetamol', '250mg', 'Three times daily', '3 days', 'Take all medications as directed. Drink plenty of water.', 29, 2, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-03-04 23:19:55', '2026-04-12 10:26:48'),
(15, 'RX-18257', 5, 4, NULL, NULL, '2026-01-19', 'Azithromycin', '50mg', 'Three times daily', '10 days', 'Take all medications as directed. Drink plenty of water.', 30, 1, 0, 'Pending', 0, NULL, NULL, '2026-03-24 03:03:14', '2026-04-12 10:26:48'),
(16, 'RX-11656', 6, 4, NULL, NULL, '2026-02-27', 'Metformin', '500mg', 'Twice daily', '3 days', 'Take all medications as directed. Drink plenty of water.', 15, 1, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-03-16 10:30:06', '2026-04-12 10:26:48'),
(17, 'RX-35022', 5, 4, NULL, NULL, '2026-02-28', 'Amlodipine', '5mg', 'Once daily', '14 days', 'Take all medications as directed. Drink plenty of water.', 22, 0, 0, 'Partially Dispensed', 0, NULL, NULL, '2026-03-21 18:24:10', '2026-04-12 10:26:48'),
(18, 'RX-32079', 5, 4, NULL, NULL, '2026-03-09', 'Artemether', '100mg', 'Once daily', '10 days', 'Take all medications as directed. Drink plenty of water.', 19, 2, 0, 'Dispensed', 0, NULL, NULL, '2026-02-19 16:51:13', '2026-04-12 10:26:48'),
(19, 'RX-24844', 10, 4, NULL, NULL, '2026-01-21', 'Metformin', '100mg', 'Every 12 hours', '10 days', 'Take all medications as directed. Drink plenty of water.', 24, 3, 0, 'Dispensed', 0, NULL, NULL, '2026-03-29 00:04:15', '2026-04-12 10:26:48'),
(20, 'RX-39161', 7, 4, NULL, NULL, '2026-01-24', 'Azithromycin', '5mg', 'Every 8 hours', '5 days', 'Take all medications as directed. Drink plenty of water.', 28, 0, 0, 'Pending', 0, NULL, NULL, '2026-04-05 07:42:06', '2026-04-12 10:26:48'),
(31, 'RX-09571', 5, 4, NULL, NULL, '2026-04-20', 'Amlodipine', '500mg', 'Twice daily', '5', 'Take in the medicine, 30 minutes after eating', 2, 0, 0, 'Pending', 0, NULL, NULL, '2026-04-20 04:36:47', '2026-04-20 04:36:47'),
(32, 'RX-09572', 5, 4, NULL, NULL, '2026-04-20', 'Amlodipine', '500mg', 'Twice daily', '5', 'Take your medicine 30 mins after eating', 1, 0, 0, 'Pending', 0, NULL, NULL, '2026-04-20 06:26:07', '2026-04-20 06:26:07');

--
-- Triggers `prescriptions`
--
DROP TRIGGER IF EXISTS `before_insert_prescriptions`;
DELIMITER $$
CREATE TRIGGER `before_insert_prescriptions` BEFORE INSERT ON `prescriptions` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(prescription_id, 5) AS UNSIGNED)), 0) + 1
  INTO next_id
  FROM prescriptions;
  SET NEW.prescription_id = CONCAT('RX-', LPAD(next_id, 5, '0'));
END
$$
DELIMITER ;

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`item_id`, `prescription_id`, `medicine_id`, `dosage`, `frequency`, `duration`, `quantity`, `dispensed_quantity`, `instructions`, `substitution_allowed`, `status`, `created_at`) VALUES
(1, 1, 12, '100mg', 'Every 8 hours', '3 days', 22, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(2, 1, 7, '20mg', 'Twice daily', '14 days', 22, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:22:48'),
(3, 2, 15, '500mg', 'Every 12 hours', '3 days', 29, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(4, 2, 10, '5mg', 'Once daily', '14 days', 28, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(5, 3, 14, '250mg', 'Once daily', '10 days', 11, 0, 'Take after food', 0, 'pending', '2026-04-12 10:22:48'),
(6, 3, 11, '100mg', 'Once daily', '3 days', 27, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(7, 4, 14, '5mg', 'Three times daily', '3 days', 29, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(8, 4, 8, '5mg', 'Twice daily', '5 days', 22, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(9, 5, 15, '10mg', 'Every 12 hours', '14 days', 11, 0, 'Take with water', 0, 'pending', '2026-04-12 10:22:48'),
(10, 5, 14, '250mg', 'Twice daily', '5 days', 16, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(11, 6, 1, '100mg', 'Every 8 hours', '7 days', 17, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(12, 6, 3, '500mg', 'Twice daily', '7 days', 26, 0, 'Take after food', 0, 'pending', '2026-04-12 10:22:48'),
(13, 7, 3, '250mg', 'Once daily', '5 days', 10, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:22:48'),
(14, 7, 9, '250mg', 'Every 8 hours', '10 days', 21, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:22:48'),
(15, 8, 12, '5mg', 'Every 12 hours', '10 days', 30, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(16, 8, 13, '10mg', 'Once daily', '14 days', 11, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:22:48'),
(17, 9, 13, '250mg', 'Every 12 hours', '3 days', 24, 0, 'Take after food', 0, 'pending', '2026-04-12 10:22:48'),
(18, 9, 1, '20mg', 'Every 8 hours', '7 days', 16, 0, 'Take with water', 0, 'pending', '2026-04-12 10:22:48'),
(19, 10, 10, '500mg', 'Three times daily', '10 days', 29, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:22:48'),
(20, 10, 11, '100mg', 'Once daily', '10 days', 17, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:22:48'),
(21, 11, 10, '250mg', 'Every 8 hours', '10 days', 14, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(22, 11, 3, '100mg', 'Every 8 hours', '5 days', 16, 0, 'Take with water', 0, 'pending', '2026-04-12 10:26:48'),
(23, 12, 22, '250mg', 'Every 8 hours', '14 days', 22, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(24, 12, 13, '5mg', 'Once daily', '7 days', 21, 0, 'Take after food', 0, 'pending', '2026-04-12 10:26:48'),
(25, 13, 21, '200mg', 'Every 12 hours', '7 days', 12, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(26, 13, 15, '100mg', 'Twice daily', '7 days', 12, 0, 'Take with water', 0, 'pending', '2026-04-12 10:26:48'),
(27, 14, 10, '200mg', 'Once daily', '5 days', 30, 0, 'Take after food', 0, 'pending', '2026-04-12 10:26:48'),
(28, 14, 1, '500mg', 'Three times daily', '10 days', 16, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(29, 15, 21, '100mg', 'Every 8 hours', '3 days', 14, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(30, 15, 23, '50mg', 'Three times daily', '3 days', 17, 0, 'Take with water', 0, 'pending', '2026-04-12 10:26:48'),
(31, 16, 23, '10mg', 'Once daily', '14 days', 22, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(32, 16, 14, '200mg', 'Every 12 hours', '7 days', 20, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(33, 17, 19, '200mg', 'Every 8 hours', '7 days', 23, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(34, 17, 16, '20mg', 'Twice daily', '5 days', 13, 0, 'Take after food', 0, 'pending', '2026-04-12 10:26:48'),
(35, 18, 9, '250mg', 'Three times daily', '10 days', 18, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(36, 18, 2, '50mg', 'Every 12 hours', '14 days', 11, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(37, 19, 11, '200mg', 'Every 12 hours', '10 days', 14, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48'),
(38, 19, 4, '250mg', 'Three times daily', '3 days', 20, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(39, 20, 7, '200mg', 'Every 8 hours', '7 days', 30, 0, 'Take before bed', 0, 'pending', '2026-04-12 10:26:48'),
(40, 20, 11, '10mg', 'Every 12 hours', '5 days', 22, 0, 'Take on empty stomach', 0, 'pending', '2026-04-12 10:26:48');

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
-- Table structure for table `public_appointment_bookings`
--

DROP TABLE IF EXISTS `public_appointment_bookings`;
CREATE TABLE IF NOT EXISTS `public_appointment_bookings` (
  `booking_id` int NOT NULL AUTO_INCREMENT,
  `patient_user_id` int NOT NULL COMMENT 'FK to users.id ÔÇö must be logged in',
  `doctor_id` int DEFAULT NULL COMMENT 'FK to doctors.id',
  `service_id` int DEFAULT NULL COMMENT 'FK to landing_services.service_id',
  `preferred_date` date NOT NULL,
  `preferred_time` time DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `idx_patient_user_id` (`patient_user_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_preferred_date` (`preferred_date`)
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reagent_inventory`
--

INSERT INTO `reagent_inventory` (`id`, `name`, `catalog_number`, `manufacturer`, `category`, `unit`, `quantity_in_stock`, `reorder_level`, `unit_cost`, `expiry_date`, `storage_conditions`, `linked_equipment_id`, `status`, `batch_number`, `date_received`, `supplier_name`, `created_at`, `updated_at`) VALUES
(1, 'Sysmex XN-550 Cell Pack DCL', 'CAT-SYS-001', 'Sysmex', 'Hematology Reagent', 'pcs', 48, 10, 120.00, '2027-01-15', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2024-0011', '2024-11-01', 'LabMed Ghana', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(2, 'Total Bilirubin Reagent (Jendrassik)', 'CAT-RCH-002', 'Roche', 'Biochemistry', 'mL', 320, 50, 45.50, '2026-10-31', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0342', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(3, 'Creatinine Jaffe Reagent', 'CAT-RCH-003', 'Roche', 'Biochemistry', 'mL', 180, 50, 38.00, '2026-12-20', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0211', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(4, 'Glucose Oxidase Reagent', 'CAT-BIO-004', 'Randox', 'Biochemistry', 'mL', 90, 30, 22.00, '2026-08-01', '15ÔÇô25┬░C', NULL, 'Low Stock', 'BT2024-0778', '2024-09-20', 'DiagLab Africa', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(5, 'VIDAS HIV Duo Ultra Reagent', 'CAT-BIO-012', 'bioM├®rieux', 'Serology', 'test', 60, 15, 250.00, '2026-06-30', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0481', '2025-02-15', 'Romedic Supplies', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(6, 'Malaria RDT Kit (CareStart)', 'CAT-MAL-005', 'Access Bio', 'Rapid Diagnostics', 'pcs', 200, 40, 8.00, '2027-03-01', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0551', '2025-03-01', 'LabMed Ghana', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(7, 'HbsAg Rapid Test Strip', 'CAT-SER-006', 'SD Bioline', 'Serology', 'pcs', 150, 30, 12.50, '2026-11-15', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0233', '2025-01-20', 'DiagLab Africa', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(8, 'Urine Dipstick (10-param)', 'CAT-URA-007', 'Siemens', 'Urinalysis', 'pcs', 500, 100, 1.80, '2026-09-30', '15ÔÇô30┬░C', NULL, 'In Stock', 'BT2024-0912', '2024-12-05', 'LabMed Ghana', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(9, 'Blood Culture Bottles (Aerobic)', 'CAT-MIC-008', 'BD Diagnostics', 'Microbiology', 'pcs', 40, 20, 35.00, '2026-07-31', '2ÔÇô25┬░C', NULL, 'Low Stock', 'BT2025-0105', '2025-01-08', 'Romedic Supplies', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(10, 'EDTA Vacutainer Tubes (3 mL)', 'CAT-CON-009', 'BD Vacutainer', 'Consumables', 'pcs', 800, 200, 0.75, '2028-01-01', '15ÔÇô25┬░C', NULL, 'In Stock', 'BT2024-0655', '2024-10-15', 'LabMed Ghana', '2026-04-19 22:55:33', '2026-04-19 22:55:33'),
(11, 'Sysmex XN-550 Cell Pack DCL', 'CAT-SYS-001', 'Sysmex', 'Hematology Reagent', 'pcs', 48, 10, 120.00, '2027-01-15', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2024-0011', '2024-11-01', 'LabMed Ghana', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(12, 'Total Bilirubin Reagent (Jendrassik)', 'CAT-RCH-002', 'Roche', 'Biochemistry', 'mL', 320, 50, 45.50, '2026-10-31', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0342', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(13, 'Creatinine Jaffe Reagent', 'CAT-RCH-003', 'Roche', 'Biochemistry', 'mL', 180, 50, 38.00, '2026-12-20', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0211', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(14, 'Glucose Oxidase Reagent', 'CAT-BIO-004', 'Randox', 'Biochemistry', 'mL', 90, 30, 22.00, '2026-08-01', '15ÔÇô25┬░C', NULL, 'Low Stock', 'BT2024-0778', '2024-09-20', 'DiagLab Africa', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(15, 'VIDAS HIV Duo Ultra Reagent', 'CAT-BIO-012', 'bioM├®rieux', 'Serology', 'test', 60, 15, 250.00, '2026-06-30', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0481', '2025-02-15', 'Romedic Supplies', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(16, 'Malaria RDT Kit (CareStart)', 'CAT-MAL-005', 'Access Bio', 'Rapid Diagnostics', 'pcs', 200, 40, 8.00, '2027-03-01', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0551', '2025-03-01', 'LabMed Ghana', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(17, 'HbsAg Rapid Test Strip', 'CAT-SER-006', 'SD Bioline', 'Serology', 'pcs', 150, 30, 12.50, '2026-11-15', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0233', '2025-01-20', 'DiagLab Africa', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(18, 'Urine Dipstick (10-param)', 'CAT-URA-007', 'Siemens', 'Urinalysis', 'pcs', 500, 100, 1.80, '2026-09-30', '15ÔÇô30┬░C', NULL, 'In Stock', 'BT2024-0912', '2024-12-05', 'LabMed Ghana', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(19, 'Blood Culture Bottles (Aerobic)', 'CAT-MIC-008', 'BD Diagnostics', 'Microbiology', 'pcs', 40, 20, 35.00, '2026-07-31', '2ÔÇô25┬░C', NULL, 'Low Stock', 'BT2025-0105', '2025-01-08', 'Romedic Supplies', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(20, 'EDTA Vacutainer Tubes (3 mL)', 'CAT-CON-009', 'BD Vacutainer', 'Consumables', 'pcs', 800, 200, 0.75, '2028-01-01', '15ÔÇô25┬░C', NULL, 'In Stock', 'BT2024-0655', '2024-10-15', 'LabMed Ghana', '2026-04-19 22:56:51', '2026-04-19 22:56:51'),
(21, 'Sysmex XN-550 Cell Pack DCL', 'CAT-SYS-001', 'Sysmex', 'Hematology Reagent', 'pcs', 48, 10, 120.00, '2027-01-15', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2024-0011', '2024-11-01', 'LabMed Ghana', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(22, 'Total Bilirubin Reagent (Jendrassik)', 'CAT-RCH-002', 'Roche', 'Biochemistry', 'mL', 320, 50, 45.50, '2026-10-31', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0342', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(23, 'Creatinine Jaffe Reagent', 'CAT-RCH-003', 'Roche', 'Biochemistry', 'mL', 180, 50, 38.00, '2026-12-20', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0211', '2025-01-10', 'Romedic Supplies', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(24, 'Glucose Oxidase Reagent', 'CAT-BIO-004', 'Randox', 'Biochemistry', 'mL', 90, 30, 22.00, '2026-08-01', '15ÔÇô25┬░C', NULL, 'Low Stock', 'BT2024-0778', '2024-09-20', 'DiagLab Africa', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(25, 'VIDAS HIV Duo Ultra Reagent', 'CAT-BIO-012', 'bioM├®rieux', 'Serology', 'test', 60, 15, 250.00, '2026-06-30', '2ÔÇô8┬░C', NULL, 'In Stock', 'BT2025-0481', '2025-02-15', 'Romedic Supplies', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(26, 'Malaria RDT Kit (CareStart)', 'CAT-MAL-005', 'Access Bio', 'Rapid Diagnostics', 'pcs', 200, 40, 8.00, '2027-03-01', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0551', '2025-03-01', 'LabMed Ghana', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(27, 'HbsAg Rapid Test Strip', 'CAT-SER-006', 'SD Bioline', 'Serology', 'pcs', 150, 30, 12.50, '2026-11-15', '2ÔÇô30┬░C', NULL, 'In Stock', 'BT2025-0233', '2025-01-20', 'DiagLab Africa', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(28, 'Urine Dipstick (10-param)', 'CAT-URA-007', 'Siemens', 'Urinalysis', 'pcs', 500, 100, 1.80, '2026-09-30', '15ÔÇô30┬░C', NULL, 'In Stock', 'BT2024-0912', '2024-12-05', 'LabMed Ghana', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(29, 'Blood Culture Bottles (Aerobic)', 'CAT-MIC-008', 'BD Diagnostics', 'Microbiology', 'pcs', 40, 20, 35.00, '2026-07-31', '2ÔÇô25┬░C', NULL, 'Low Stock', 'BT2025-0105', '2025-01-08', 'Romedic Supplies', '2026-04-19 22:57:46', '2026-04-19 22:57:46'),
(30, 'EDTA Vacutainer Tubes (3 mL)', 'CAT-CON-009', 'BD Vacutainer', 'Consumables', 'pcs', 800, 200, 0.75, '2028-01-01', '15ÔÇô25┬░C', NULL, 'In Stock', 'BT2024-0655', '2024-10-15', 'LabMed Ghana', '2026-04-19 22:57:46', '2026-04-19 22:57:46');

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
-- Table structure for table `recaptcha_logs`
--

DROP TABLE IF EXISTS `recaptcha_logs`;
CREATE TABLE IF NOT EXISTS `recaptcha_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recaptcha_score` decimal(4,3) DEFAULT NULL COMMENT 'Google reCAPTCHA v3 score 0.0-1.0',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'register',
  `passed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rl_email` (`email`),
  KEY `idx_rl_ip` (`ip_address`),
  KEY `idx_rl_time` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recaptcha_logs`
--

INSERT INTO `recaptcha_logs` (`id`, `email`, `ip_address`, `recaptcha_score`, `action`, `passed`, `created_at`) VALUES
(1, 'craig.osae@st.rmu.edu.gh', '::1', NULL, 'register', 0, '2026-04-09 10:16:59'),
(2, 'craig.osae@st.rmu.edu.gh', '::1', NULL, 'register', 0, '2026-04-09 10:43:18');

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE IF NOT EXISTS `refunds` (
  `refund_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_reference` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RMU-RFD-YYYYMMDD-NNNN',
  `payment_id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `patient_id` int NOT NULL,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `refund_method` enum('Cash','Mobile Money','Card Reversal','Bank Transfer','Paystack Refund','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending Approval','Approved','Processing','Completed','Rejected','Failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Approval',
  `paystack_refund_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Paystack refund reference if online',
  `paystack_refund_response` json DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `approval_threshold` decimal(15,2) DEFAULT NULL COMMENT 'Amount above which manager approval needed',
  `processed_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`refund_id`),
  UNIQUE KEY `uq_refund_reference` (`refund_reference`),
  KEY `idx_refund_payment` (`payment_id`),
  KEY `idx_refund_invoice` (`invoice_id`),
  KEY `idx_refund_patient` (`patient_id`),
  KEY `idx_refund_status` (`status`),
  KEY `fk_refund_processor` (`processed_by`),
  KEY `fk_refund_approver` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Refund requests, approvals, and Paystack refund tracking';

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`refund_id`, `refund_reference`, `payment_id`, `invoice_id`, `patient_id`, `refund_amount`, `currency`, `reason`, `refund_method`, `status`, `paystack_refund_reference`, `paystack_refund_response`, `requires_approval`, `approval_threshold`, `processed_by`, `approved_by`, `approved_at`, `rejection_reason`, `completed_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'REF-001', 1, 7, 101, 20.00, 'GHS', 'Overpayment on labs', 'Cash', 'Completed', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(2, 'REF-002', 2, 10, 102, 50.00, 'GHS', 'Cancelled service fee', 'Mobile Money', 'Approved', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(3, 'REF-003', 3, 16, 103, 10.00, 'GHS', 'Duplicate transaction', 'Card Reversal', 'Pending Approval', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(4, 'REF-004', 4, 17, 104, 30.00, 'GHS', 'Billed for wrong test', 'Bank Transfer', 'Processing', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(5, 'REF-005', 5, 19, 105, 5.00, 'GHS', 'Misc correction', 'Cash', 'Completed', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(6, 'REF-006', 6, 21, 106, 15.00, 'GHS', 'Waiver applied late', 'Paystack Refund', 'Approved', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(7, 'REF-007', 7, 28, 107, 40.00, 'GHS', 'Insurance fully covered after cash pay', 'Other', 'Pending Approval', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(8, 'REF-008', 8, 4, 108, 100.00, 'GHS', 'Treatment changed', 'Cash', 'Completed', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(9, 'REF-009', 9, 12, 109, 12.00, 'GHS', 'System rounding error', 'Cash', 'Completed', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44'),
(10, 'REF-010', 10, 26, 110, 25.00, 'GHS', 'Patient request', 'Mobile Money', 'Rejected', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 01:05:44', '2026-04-14 01:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `registration_sessions`
--

DROP TABLE IF EXISTS `registration_sessions`;
CREATE TABLE IF NOT EXISTS `registration_sessions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hex token held in hidden field or cookie',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `step_reached` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '1=details 2=otp_sent 3=verified',
  `temp_data` json NOT NULL COMMENT 'Serialised form fields — no plain password',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_rs_email` (`email`),
  KEY `idx_rs_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_sessions`
--

INSERT INTO `registration_sessions` (`id`, `session_token`, `email`, `role`, `step_reached`, `temp_data`, `created_at`, `expires_at`) VALUES
(1, 'abcd', 'test@test.com', '0', 2, '{}', '2026-04-09 07:52:56', '2026-04-09 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `remember_me_tokens`
--

DROP TABLE IF EXISTS `remember_me_tokens`;
CREATE TABLE IF NOT EXISTS `remember_me_tokens` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `remember_me_tokens`
--

INSERT INTO `remember_me_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `created_at`) VALUES
(7, 23, 'd11c9ae7079bbbd760d868b86cc60c8ca0a0795acca8af5b18525a82e1482741', '2026-04-30 13:48:58', '2026-03-31 13:48:58'),
(8, 17, '27af5cf60e089a14ae64ab5fb845252092f6900217405cfac7193647ff86aaa2', '2026-04-30 13:50:57', '2026-03-31 13:50:57'),
(11, 21, '9da18abc43719c62f44dd4e7decb812e3a23f7052cca522872354d1c7acfe166', '2026-04-30 14:21:09', '2026-03-31 14:21:09'),
(12, 18, 'df1aa81010e62044cc6a968b2ca57d123ad2fad0641891bfcde4ee491b3eaf61', '2026-04-30 14:23:10', '2026-03-31 14:23:10');

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

DROP TABLE IF EXISTS `report_templates`;
CREATE TABLE IF NOT EXISTS `report_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` json DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_template_user` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revenue_categories`
--

DROP TABLE IF EXISTS `revenue_categories`;
CREATE TABLE IF NOT EXISTS `revenue_categories` (
  `category_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_category_id` int UNSIGNED DEFAULT NULL COMMENT 'Self-ref FK added via ALTER',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_revenue_cat_code` (`category_code`),
  KEY `fk_revenue_parent` (`parent_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hierarchical revenue classification for all billable services';

--
-- Dumping data for table `revenue_categories`
--

INSERT INTO `revenue_categories` (`category_id`, `category_name`, `category_code`, `parent_category_id`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Consultation Fees', 'CONSULT', NULL, 'Doctor consultation and examination fees', 1, 1, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(2, 'Laboratory Services', 'LAB', NULL, 'Lab test and diagnostic fees', 1, 2, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(3, 'Pharmacy Sales', 'PHARMACY', NULL, 'Medication and pharmaceutical sales', 1, 3, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(4, 'Bed & Ward Charges', 'BED', NULL, 'Inpatient bed and ward accommodation', 1, 4, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(5, 'Procedure Fees', 'PROCEDURE', NULL, 'Medical procedures and surgeries', 1, 5, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(6, 'Emergency Services', 'EMERGENCY', NULL, 'Emergency room and urgent care fees', 1, 6, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(7, 'Ambulance Services', 'AMBULANCE', NULL, 'Ambulance transport charges', 1, 7, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(8, 'Administrative Fees', 'ADMIN_FEE', NULL, 'Registration and administrative charges', 1, 8, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(9, 'Insurance Reimbursements', 'INSURANCE', NULL, 'Payments received from insurance claims', 1, 9, '2026-04-08 02:08:30', '2026-04-08 02:08:30'),
(10, 'Miscellaneous', 'MISC', NULL, 'Other uncategorized revenue', 1, 10, '2026-04-08 02:08:30', '2026-04-08 02:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_create` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` int UNSIGNED DEFAULT NULL COMMENT 'Admin user_id who last changed this row',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rp_role_module` (`role_name`,`module_name`),
  KEY `idx_rp_role` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_name`, `module_name`, `can_view`, `can_create`, `can_edit`, `can_delete`, `updated_by`, `updated_at`) VALUES
(1, 'admin', 'registration_portal', 1, 1, 1, 1, NULL, '2026-03-25 05:53:00'),
(2, 'doctor', 'registration_portal', 0, 0, 0, 0, NULL, '2026-03-25 05:53:00'),
(3, 'patient', 'registration_portal', 1, 0, 0, 0, NULL, '2026-03-25 05:53:00'),
(4, 'nurse', 'registration_portal', 0, 0, 0, 0, NULL, '2026-03-25 05:53:00'),
(5, 'pharmacist', 'registration_portal', 0, 0, 0, 0, NULL, '2026-03-25 05:53:00'),
(6, 'lab_technician', 'registration_portal', 0, 0, 0, 0, NULL, '2026-03-25 05:53:00'),
(7, 'staff', 'registration_portal', 0, 0, 0, 0, NULL, '2026-03-25 05:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

DROP TABLE IF EXISTS `scheduled_reports`;
CREATE TABLE IF NOT EXISTS `scheduled_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` json DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_schedule_user` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incidents`
--

DROP TABLE IF EXISTS `security_incidents`;
CREATE TABLE IF NOT EXISTS `security_incidents` (
  `incident_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `incident_type` enum('visitor check','access control','incident report','patrol log','theft','trespassing','violence','medical emergency','fire','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'incident report',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `persons_involved` text COLLATE utf8mb4_unicode_ci,
  `actions_taken` text COLLATE utf8mb4_unicode_ci,
  `status` enum('reported','escalated','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `escalated_to` int DEFAULT NULL COMMENT 'admin user_id',
  `reported_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`incident_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`),
  KEY `idx_reported_at` (`reported_at`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_incidents`
--

INSERT INTO `security_incidents` (`incident_id`, `staff_id`, `incident_type`, `location`, `description`, `severity`, `persons_involved`, `actions_taken`, `status`, `escalated_to`, `reported_at`, `resolved_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 14, 'incident report', 'Main Entrance', 'Intoxicated visitor attempting entry', 'medium', NULL, NULL, 'resolved', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(2, 14, 'theft', 'Kitchen Storage', 'Discrepancy in meat inventory noted', 'high', NULL, NULL, 'escalated', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(3, 14, 'trespassing', 'Back Gate', 'Unauthorized person climbed fence', 'high', NULL, NULL, 'closed', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(4, 14, 'visitor check', 'Admin Block', 'Visitor without proper ID badge', 'low', NULL, NULL, 'closed', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(5, 14, 'medical emergency', 'OPD Waiting', 'Elderly visitor fainted', 'critical', NULL, NULL, 'resolved', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(6, 14, 'access control', 'Drug Store', 'Pharmacist reported door left unlocked', 'medium', NULL, NULL, 'reported', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(7, 14, 'patrol log', 'Perimeter Wall', 'Routine night patrol completed; all secure', 'low', NULL, NULL, 'closed', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(8, 14, 'violence', 'Ambulance Bay', 'Argument between staff and driver', 'medium', NULL, NULL, 'resolved', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(9, 14, 'fire', 'Smoking Area', 'Waste bin fire extinguished promptly', 'high', NULL, NULL, 'closed', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(10, 14, 'other', 'Parking Lot', 'Car window found broken', 'medium', NULL, NULL, 'reported', NULL, '2026-04-14 01:06:07', NULL, NULL, '2026-04-14 01:06:07', '2026-04-14 01:06:07'),
(11, 18, 'access control', 'ward room', 'Severe harm and damage have been caused in the emergency ward room', 'high', '', '', 'reported', NULL, '2026-05-13 20:11:36', NULL, NULL, '2026-05-13 20:11:36', '2026-05-13 20:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE IF NOT EXISTS `security_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `incident_type` enum('visitor check','access control','incident report','patrol log','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('logged','escalated','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'logged',
  `escalated_to` int DEFAULT NULL COMMENT 'admin ID nullable',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`log_id`, `staff_id`, `incident_type`, `location`, `description`, `reported_at`, `status`, `escalated_to`, `notes`) VALUES
(1, 14, 'patrol log', 'North Perimeter', 'Patrol completed. Fence integrity verified.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(2, 14, 'visitor check', 'Main Gate', 'Late night visitor denied entry after visiting hours.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(3, 14, 'access control', 'Drug Store', 'Routine lock check. All secure.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(4, 14, 'incident report', 'Parking Lot', 'Found car with lights on. Owner notified.', '2026-04-14 06:54:21', 'resolved', NULL, NULL),
(5, 14, 'patrol log', 'Staff Quarters', 'Quiet night. No issues reported.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(6, 14, 'visitor check', 'Main Reception', 'Contractor checked in for AC maintenance.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(7, 14, 'access control', 'Lab Rear Door', 'Door found slightly ajar. Secured and logged.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(8, 14, 'incident report', 'Main Lobby', 'Water leak reported near elevators.', '2026-04-14 06:54:21', 'escalated', NULL, NULL),
(9, 14, 'patrol log', 'West Perimeter', 'Routine patrol. All lights functioning.', '2026-04-14 06:54:21', 'logged', NULL, NULL),
(10, 14, 'other', 'Main Gate', 'Lost keys handed in by student.', '2026-04-14 06:54:21', 'resolved', NULL, NULL);

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
  `handover_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `outgoing_nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `incoming_nurse_id` int DEFAULT NULL COMMENT 'FK → nurses.id',
  `shift_id` int NOT NULL COMMENT 'FK → nurse_shifts.id',
  `ward` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_summaries` json DEFAULT NULL COMMENT 'Array of patient status objects',
  `pending_tasks` json DEFAULT NULL COMMENT 'Array of pending task objects',
  `critical_patients_noted` text COLLATE utf8mb4_unicode_ci,
  `handover_notes` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `acknowledged_by_incoming` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `handover_id` (`handover_id`),
  KEY `idx_sh_handover_id` (`handover_id`),
  KEY `idx_sh_outgoing` (`outgoing_nurse_id`),
  KEY `idx_sh_incoming` (`incoming_nurse_id`),
  KEY `idx_sh_shift` (`shift_id`),
  KEY `idx_sh_acknowledged` (`acknowledged_by_incoming`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formal shift handover documentation between nurses';

-- --------------------------------------------------------

--
-- Table structure for table `shift_types`
--

DROP TABLE IF EXISTS `shift_types`;
CREATE TABLE IF NOT EXISTS `shift_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `color_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#3498db',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `staff_id`, `department`, `position`, `hire_date`, `salary`, `shift`, `created_at`, `updated_at`, `full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `email`, `address`, `profile_photo`, `role`, `department_id`, `designation`, `employee_id`, `employment_type`, `shift_type`, `status`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `date_joined`, `emergency_contact_name`, `emergency_contact_phone`, `profile_completeness`) VALUES
(10, 301, 'STF-AMB-01', 'Transport', 'Lead Driver', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ambulance_driver', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(11, 302, 'STF-CLN-01', 'Sanitation', 'Sanitary Officer', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cleaner', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(12, 303, 'STF-LND-01', 'Laundry', 'Linen Specialist', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'laundry_staff', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(13, 304, 'STF-MNT-01', 'Facilities', 'Technician', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'maintenance', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(14, 305, 'STF-SEC-01', 'Security', 'Base Warden', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'security', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(15, 306, 'STF-KTN-01', 'Catering', 'Head Cook', NULL, 0.00, 'Morning', '2026-04-14 00:31:58', '2026-04-14 00:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kitchen_staff', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(16, 308, 'STF-F37F03', '', '', NULL, 0.00, 'Morning', '2026-04-15 12:30:00', '2026-04-15 12:33:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', 1, '2026-04-15 12:33:52', NULL, NULL, NULL, NULL, 0),
(17, 313, 'STF-6FEC1A', '', '', NULL, 0.00, 'Morning', '2026-04-21 04:32:02', '2026-04-21 04:51:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'maintenance', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', 1, '2026-04-21 04:33:15', NULL, NULL, NULL, NULL, 0),
(18, 314, 'STF-8E0A48', '', '', NULL, 0.00, 'Morning', '2026-04-21 09:14:07', '2026-04-21 09:14:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'security', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', 1, '2026-04-21 09:14:44', NULL, NULL, NULL, NULL, 0),
(19, 315, 'STF-C0A092', '', '', NULL, 0.00, 'Morning', '2026-04-21 10:12:56', '2026-04-21 10:45:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cleaner', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', 1, '2026-04-21 10:45:05', NULL, NULL, NULL, NULL, 0),
(20, 316, 'STF-5E4E11', '', '', NULL, 0.00, 'Morning', '2026-05-05 06:45:21', '2026-05-05 07:22:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ambulance_driver', NULL, NULL, NULL, 'full-time', 'morning', 'active', 'approved', 1, '2026-05-05 07:22:00', NULL, NULL, NULL, NULL, 0);

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_approval_log`
--

INSERT INTO `staff_approval_log` (`id`, `staff_id`, `admin_user_id`, `action`, `reason`, `actioned_at`) VALUES
(1, 1, 1, 'approved', NULL, '2026-03-16 20:12:03'),
(2, 4, 1, 'approved', NULL, '2026-03-17 12:30:23'),
(3, 5, 1, 'approved', NULL, '2026-03-17 17:31:39'),
(4, 6, 1, 'approved', NULL, '2026-03-17 18:27:47'),
(5, 7, 1, 'approved', NULL, '2026-03-17 22:52:28'),
(6, 8, 1, 'approved', NULL, '2026-03-17 23:09:50'),
(7, 9, 1, 'approved', NULL, '2026-03-18 02:50:54'),
(8, 10, 1, 'approved', NULL, '2026-03-18 04:02:02'),
(9, 11, 1, 'approved', NULL, '2026-03-18 05:03:03'),
(10, 16, 1, 'approved', NULL, '2026-04-15 12:33:55'),
(11, 17, 1, 'approved', NULL, '2026-04-21 04:33:16'),
(12, 18, 1, 'approved', NULL, '2026-04-21 09:14:46'),
(13, 19, 1, 'approved', NULL, '2026-04-21 10:45:09'),
(14, 20, 1, 'approved', NULL, '2026-05-05 07:22:04');

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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_audit_trail`
--

INSERT INTO `staff_audit_trail` (`id`, `user_id`, `action_type`, `module`, `description`, `ip_address`, `created_at`) VALUES
(1, 11, 'login_blocked', 'security', 'Account pending admin approval', NULL, '2026-03-16 11:59:35'),
(2, 12, 'login_blocked', 'security', 'Account pending admin approval', NULL, '2026-03-17 12:01:56'),
(3, 313, 'accept_maintenance', 'maintenance', 'Action: accept_maintenance in maintenance. Record ID: 1.', '::1', '2026-04-21 08:02:06'),
(4, 315, 'send_message', 'communication', 'Action: send_message in communication. Record ID: 2.', '::1', '2026-04-21 12:38:25'),
(5, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 1.', '::1', '2026-04-21 12:40:07'),
(6, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 2.', '::1', '2026-04-21 13:40:42'),
(7, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 3.', '::1', '2026-04-21 13:41:12'),
(8, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 4.', '::1', '2026-04-21 14:02:49'),
(9, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 5.', '::1', '2026-04-21 16:22:01'),
(10, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 6.', '::1', '2026-04-21 16:24:15'),
(11, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 7.', '::1', '2026-04-21 16:24:16'),
(12, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 8.', '::1', '2026-04-21 16:24:17'),
(13, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 9.', '::1', '2026-04-21 16:24:17'),
(14, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 10.', '::1', '2026-04-21 16:24:18'),
(15, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 11.', '::1', '2026-04-21 16:24:18'),
(16, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 12.', '::1', '2026-04-21 16:24:18'),
(17, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 13.', '::1', '2026-04-21 16:24:19'),
(18, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 14.', '::1', '2026-04-21 16:24:19'),
(19, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 15.', '::1', '2026-04-21 16:24:19'),
(20, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 16.', '::1', '2026-04-21 16:24:19'),
(21, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 17.', '::1', '2026-04-21 16:24:19'),
(22, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 18.', '::1', '2026-04-21 16:24:19'),
(23, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 19.', '::1', '2026-04-21 16:24:19'),
(24, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 20.', '::1', '2026-04-21 16:24:19'),
(25, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 21.', '::1', '2026-04-21 16:24:19'),
(26, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 22.', '::1', '2026-04-21 16:24:20'),
(27, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 23.', '::1', '2026-04-21 16:24:21'),
(28, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 24.', '::1', '2026-04-21 16:24:21'),
(29, 315, 'report_hazard', 'operations', 'Action: report_hazard in operations. Record ID: 25.', '::1', '2026-04-21 16:24:21'),
(30, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 19:59:57'),
(31, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 20:00:01'),
(32, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 20:02:29'),
(33, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 20:02:31'),
(34, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 20:02:34'),
(35, 314, 'update_task_status', 'tasks', 'Action: update_task_status in tasks. Record ID: 1. Details: {\"old\":null,\"new\":{\"status\":\"in progress\"}}', '::1', '2026-05-13 20:02:36'),
(36, 316, 'send_message', 'communication', 'Action: send_message in communication. Record ID: 3.', '::1', '2026-05-14 09:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--

DROP TABLE IF EXISTS `staff_departments`;
CREATE TABLE IF NOT EXISTS `staff_departments` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `head_of_department` int DEFAULT NULL COMMENT 'staff ID nullable',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_departments`
--

INSERT INTO `staff_departments` (`department_id`, `name`, `description`, `head_of_department`, `is_active`, `created_at`) VALUES
(1, 'Emergency Ward', 'Emergency and Trauma Unit', NULL, 1, '2026-05-13 19:15:49'),
(2, 'General Ward A', 'General inpatient ward — Block A', NULL, 1, '2026-05-13 19:15:49'),
(3, 'General Ward B', 'General inpatient ward — Block B', NULL, 1, '2026-05-13 19:15:49'),
(4, 'ICU / Critical Care', 'Intensive Care Unit', NULL, 1, '2026-05-13 19:15:49'),
(5, 'Maternity Ward', 'Obstetrics and Gynecology Unit', NULL, 1, '2026-05-13 19:15:49'),
(6, 'Pediatrics Ward', 'Children\'s Health Unit', NULL, 1, '2026-05-13 19:15:49'),
(7, 'Operating Theatre', 'Surgical Suite and Recovery', NULL, 1, '2026-05-13 19:15:49'),
(8, 'Pharmacy', 'Dispensary and Drug Store', NULL, 1, '2026-05-13 19:15:49'),
(9, 'Laboratory', 'Pathology and Diagnostics', NULL, 1, '2026-05-13 19:15:49'),
(10, 'Radiology', 'Imaging Department', NULL, 1, '2026-05-13 19:15:49'),
(11, 'Outpatient Department', 'OPD and Consultations', NULL, 1, '2026-05-13 19:15:49'),
(12, 'Cafeteria / Kitchen', 'Staff and Patient Dining', NULL, 1, '2026-05-13 19:15:49'),
(13, 'Main Gate / Reception', 'Hospital Entrance and Security Post', NULL, 1, '2026-05-13 19:15:49'),
(14, 'Parking Lot', 'Vehicle Parking and Ambulance Bay', NULL, 1, '2026-05-13 19:15:49'),
(15, 'Administrative Block', 'Admin Offices and HR', NULL, 1, '2026-05-13 19:15:49'),
(16, 'Laundry Room', 'Linen and Laundry Services', NULL, 1, '2026-05-13 19:15:49'),
(17, 'Maintenance Workshop', 'Facility Engineering and Repairs', NULL, 1, '2026-05-13 19:15:49'),
(18, 'Staff Quarters', 'On-Site Staff Accommodation', NULL, 1, '2026-05-13 19:15:49'),
(19, 'Chapel / Prayer Room', 'Interfaith Worship Space', NULL, 1, '2026-05-13 19:15:49'),
(20, 'External / Field', 'Off-site or Field Assignment', NULL, 1, '2026-05-13 19:15:49');

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
-- Table structure for table `staff_leaves`
--

DROP TABLE IF EXISTS `staff_leaves`;
CREATE TABLE IF NOT EXISTS `staff_leaves` (
  `leave_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type` enum('annual','sick','emergency','unpaid','maternity','paternity','study','compassionate','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL DEFAULT '1',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL COMMENT 'admin user_id who reviewed',
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`leave_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_leaves`
--

INSERT INTO `staff_leaves` (`leave_id`, `staff_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `applied_at`, `reviewed_by`, `reviewed_at`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 17, 'unpaid', '2026-04-21', '2026-04-30', 10, 'Unpaid Salaries and Wages for February &amp; March 2026', 'pending', '2026-04-21 08:28:39', NULL, NULL, NULL, '2026-04-21 08:28:39', '2026-04-21 08:28:39'),
(2, 19, 'annual', '2026-04-21', '2026-04-30', 10, 'Annual leave, hoping to sort out my mental and emotional health during this vacation period', 'pending', '2026-04-21 12:36:41', NULL, NULL, NULL, '2026-04-21 12:36:41', '2026-04-21 12:36:41'),
(3, 19, 'annual', '2026-04-21', '2026-04-30', 10, 'Annual Leave, hoping to restore my mental and emotional health during this break', 'pending', '2026-04-21 13:34:40', NULL, NULL, NULL, '2026-04-21 13:34:40', '2026-04-21 13:34:40'),
(4, 18, 'annual', '2026-05-15', '2026-05-30', 16, 'Annual Leave, to regain my mental and emotional health', 'approved', '2026-05-13 20:01:21', 1, '2026-05-13 20:03:49', NULL, '2026-05-13 20:01:21', '2026-05-13 20:03:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_requests`
--

DROP TABLE IF EXISTS `staff_leave_requests`;
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
  `leave_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type` enum('annual','sick','emergency','unpaid','maternity','paternity','study','compassionate','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL DEFAULT '1',
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL COMMENT 'admin ID',
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text,
  `rejection_reason` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_messages`
--

INSERT INTO `staff_messages` (`message_id`, `sender_id`, `sender_role`, `receiver_id`, `subject`, `message_content`, `is_read`, `priority`, `sent_at`, `read_at`, `is_broadcast`, `created_at`) VALUES
(1, 17, '', 11, 'Cleaning Services', 'Cleaning service required at the theatre ward for operations', 0, 'urgent', '2026-04-21 08:00:22', NULL, 0, '2026-04-21 08:00:22'),
(2, 315, '', 1, 'Cleaning Services', 'The operation theatre is well prepared to carry out today&#039;s operations', 0, 'normal', '2026-04-21 12:38:25', NULL, 0, '2026-04-21 12:38:25'),
(3, 316, '', 26, 'OPD ATTENTION', 'There is a patient currently suffocating in the OPD ward, I am currently there cleaning the facility', 0, 'urgent', '2026-05-14 09:38:35', NULL, 0, '2026-05-14 09:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `staff_notifications`
--

DROP TABLE IF EXISTS `staff_notifications`;
CREATE TABLE IF NOT EXISTS `staff_notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('task','alert','shift','emergency','system','message','maintenance','leave','incident','visitor','patrol','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `related_module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_notifications`
--

INSERT INTO `staff_notifications` (`notification_id`, `staff_id`, `message`, `type`, `is_read`, `related_module`, `related_record_id`, `created_at`) VALUES
(1, 18, 'New task assigned: Conduct Deep security check (Due: 2026-05-11 10:00)', 'task', 1, 'tasks', 1, '2026-05-11 07:38:22'),
(2, 17, 'New task assigned: Weekly Maintenance Scheduling (Due: 2026-05-20 12:00)', 'task', 0, 'tasks', 2, '2026-05-13 08:57:28'),
(3, 18, 'New shift assigned. Location: Emergency Ward.', 'shift', 0, 'shifts', NULL, '2026-05-13 20:06:31');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `role_slug` enum('ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_description` text COLLATE utf8mb4_unicode_ci,
  `icon_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-user-tie',
  `dashboard_file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_role_slug` (`role_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `theme_preference` enum('light','dark') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `notification_preferences` json DEFAULT NULL,
  `alert_sound_enabled` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`settings_id`),
  UNIQUE KEY `uk_staff_settings` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

DROP TABLE IF EXISTS `staff_shifts`;
CREATE TABLE IF NOT EXISTS `staff_shifts` (
  `shift_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `shift_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location_ward_assigned` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('scheduled','active','completed','missed','swapped','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shift_id`),
  KEY `idx_staff_shift` (`staff_id`,`shift_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_shifts`
--

INSERT INTO `staff_shifts` (`shift_id`, `staff_id`, `shift_type`, `shift_date`, `start_time`, `end_time`, `location_ward_assigned`, `status`, `notes`, `created_at`) VALUES
(1, 18, 'afternoon', '2026-05-15', '00:00:00', '18:00:00', 'Emergency Ward', 'scheduled', 'Requires diligent attention to ensure the safety and sanity of the ward', '2026-05-13 20:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

DROP TABLE IF EXISTS `staff_tasks`;
CREATE TABLE IF NOT EXISTS `staff_tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `assigned_to` int NOT NULL COMMENT 'staff ID',
  `assigned_by` int DEFAULT NULL COMMENT 'admin ID or system',
  `task_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_description` text COLLATE utf8mb4_unicode_ci,
  `task_category` enum('cleaning','laundry','maintenance','transport','security','kitchen','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ward/room/area',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `status` enum('pending','in progress','completed','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completion_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_tasks`
--

INSERT INTO `staff_tasks` (`task_id`, `assigned_to`, `assigned_by`, `task_title`, `task_description`, `task_category`, `priority`, `location`, `due_date`, `due_time`, `status`, `completion_notes`, `completion_photo_path`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 18, 1, 'Conduct Deep security check', 'Ensure the safety and sanity of the OPD Ward', 'security', 'high', NULL, '2026-05-11', '10:00:00', 'overdue', '', NULL, NULL, '2026-05-11 07:38:22', '2026-05-13 20:02:37'),
(2, 17, 1, 'Weekly Maintenance Scheduling', 'Ensure that all the equipment is securely maintained in the OPD ward and the general ward', 'maintenance', 'high', NULL, '2026-05-20', '12:00:00', 'pending', NULL, NULL, NULL, '2026-05-13 08:57:28', '2026-05-13 08:57:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_alerts`
--

INSERT INTO `stock_alerts` (`id`, `medicine_id`, `alert_type`, `threshold_value`, `current_value`, `is_resolved`, `resolved_by`, `resolved_at`, `created_at`) VALUES
(1, 26, 'expired', 0, 0, 1, 312, '2026-04-17 10:02:10', '2026-04-17 09:34:44'),
(2, 28, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(3, 29, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(4, 30, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(5, 31, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(6, 32, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(7, 33, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(8, 34, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(9, 35, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 09:34:44'),
(10, 26, 'expired', 0, 0, 1, 312, '2026-04-17 10:05:08', '2026-04-17 10:02:11'),
(11, 26, 'expired', 0, 0, 0, NULL, NULL, '2026-04-17 10:05:09');

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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `medicine_id`, `inventory_id`, `transaction_type`, `quantity`, `previous_quantity`, `new_quantity`, `performed_by`, `transaction_date`, `notes`, `created_at`) VALUES
(1, 2, NULL, 'dispensed', 99, 312, 411, 26, '2026-02-17 23:13:57', 'Dispensed to patient', '2026-03-04 23:23:30'),
(2, 3, NULL, 'dispensed', 24, 154, 178, 26, '2026-03-15 11:18:55', 'Restocking', '2026-02-15 23:42:05'),
(3, 5, NULL, 'expired', 56, 404, 460, 28, '2026-01-21 08:05:27', 'Adjustment', '2026-03-26 10:38:13'),
(4, 3, NULL, 'adjusted', 78, 457, 535, 35, '2026-02-28 02:40:52', 'Adjustment', '2026-04-02 19:24:13'),
(5, 2, NULL, 'adjusted', 84, 291, 375, 35, '2026-04-09 06:48:28', 'Expired', '2026-03-08 16:28:54'),
(6, 1, NULL, 'dispensed', 7, 364, 371, 26, '2026-02-17 17:50:32', 'Return', '2026-04-04 15:34:09'),
(7, 1, NULL, 'returned', 40, 224, 264, 26, '2026-02-07 05:31:47', 'Adjustment', '2026-01-14 02:10:22'),
(8, 2, NULL, 'expired', 60, 377, 437, 36, '2026-03-26 05:51:29', 'Expired', '2026-01-21 10:58:48'),
(9, 4, NULL, 'dispensed', 74, 380, 454, 28, '2026-02-13 15:47:50', 'Restocking', '2026-03-21 22:46:38'),
(10, 1, NULL, 'restock', 87, 390, 477, 36, '2026-02-23 17:43:57', 'Return', '2026-03-31 06:39:09'),
(11, 2, NULL, 'adjusted', 70, 217, 287, 20, '2026-01-25 14:07:44', 'Expired', '2026-03-29 01:05:05'),
(12, 13, NULL, 'expired', 93, 152, 245, 42, '2026-02-06 20:20:37', 'Restocking', '2026-02-01 02:51:57'),
(13, 2, NULL, 'expired', 7, 291, 298, 37, '2026-02-01 14:54:01', 'Dispensed to patient', '2026-01-21 19:43:09'),
(14, 15, NULL, 'dispensed', 40, 325, 365, 44, '2026-03-03 12:37:07', 'Return', '2026-03-17 01:04:46'),
(15, 1, NULL, 'adjusted', 83, 277, 360, 35, '2026-02-14 02:23:43', 'Return', '2026-03-07 02:09:04'),
(16, 9, NULL, 'dispensed', 17, 169, 186, 41, '2026-03-10 12:39:27', 'Return', '2026-04-10 08:36:09'),
(17, 15, NULL, 'adjusted', 43, 153, 196, 41, '2026-02-21 09:39:33', 'Return', '2026-01-20 05:09:28'),
(18, 4, NULL, 'dispensed', 27, 127, 154, 35, '2026-04-01 06:42:40', 'Adjustment', '2026-01-30 00:10:08'),
(19, 14, NULL, 'dispensed', 84, 472, 556, 39, '2026-03-25 04:14:23', 'Return', '2026-01-22 08:21:03'),
(20, 13, NULL, 'adjusted', 93, 167, 260, 28, '2026-02-01 01:13:18', 'Dispensed to patient', '2026-04-11 05:17:45'),
(21, 12, NULL, 'restock', 18, 132, 150, 43, '2026-03-24 10:53:00', 'Adjustment', '2026-02-07 13:39:00'),
(22, 19, NULL, 'returned', 74, 290, 364, 44, '2026-03-30 10:08:51', 'Expired', '2026-04-08 06:32:21'),
(23, 20, NULL, 'restock', 94, 347, 441, 1, '2026-03-24 15:47:05', 'Adjustment', '2026-02-18 06:20:15'),
(24, 16, NULL, 'adjusted', 52, 202, 254, 1, '2026-01-26 11:47:40', 'Dispensed to patient', '2026-03-06 07:51:59'),
(25, 4, NULL, 'adjusted', 33, 397, 430, 44, '2026-02-14 18:50:55', 'Adjustment', '2026-02-24 09:16:59'),
(26, 22, NULL, 'restock', 22, 409, 431, 42, '2026-03-26 08:36:00', 'Adjustment', '2026-02-12 20:29:52'),
(27, 11, NULL, 'restock', 37, 154, 191, 40, '2026-04-01 14:42:02', 'Restocking', '2026-02-24 19:58:22'),
(28, 17, NULL, 'returned', 7, 240, 247, 38, '2026-03-18 03:56:45', 'Restocking', '2026-02-12 17:08:29'),
(29, 4, NULL, 'restock', 71, 275, 346, 20, '2026-04-02 09:32:50', 'Return', '2026-02-11 05:58:52'),
(30, 16, NULL, 'restock', 20, 445, 465, 26, '2026-03-24 18:42:39', 'Expired', '2026-02-05 02:21:17');

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
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(17, 'smtp_password', 'aHFyciBra2F0IHJ1cWcgbnV0Zg==', '2026-02-20 20:50:38', '2026-02-20 20:52:40', 1),
(18, 'date_format', 'd M Y', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(19, 'time_format', 'H:i', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(20, 'currency_symbol', 'GHS', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(21, 'language_default', 'en', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(22, 'password_min_length', '8', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(23, 'password_require_special', '1', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(24, 'session_timeout_admin', '30', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(25, 'session_timeout_doctor', '60', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(26, 'session_timeout_nurse', '60', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(27, 'session_timeout_staff', '120', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(28, 'mfa_required_admin', '0', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(29, 'mfa_required_medical', '0', '2026-03-23 08:04:30', '2026-03-23 08:04:30', NULL),
(30, 'recaptcha_site_key', '6Lc01sYqAAAAAA5E5v1B_0L2aN9d2oM_0-E45l6k', '2026-03-25 13:02:14', '2026-03-25 13:02:14', NULL),
(31, 'recaptcha_secret_key', '6Lc01sYqAAAAAHk0K8OpxX4O86N07V0q4K9lY3-X', '2026-03-25 13:02:14', '2026-03-25 13:02:14', NULL),
(32, 'recaptcha_score_threshold', '0.5', '2026-03-25 13:02:14', '2026-03-25 13:02:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_email_config`
--

DROP TABLE IF EXISTS `system_email_config`;
CREATE TABLE IF NOT EXISTS `system_email_config` (
  `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_port` smallint NOT NULL DEFAULT '587',
  `smtp_username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `smtp_password` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AES-256-CBC encrypted with APP_SECRET',
  `encryption` enum('tls','ssl','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tls',
  `from_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `from_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RMU Medical Sickbay',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_email_config`
--

INSERT INTO `system_email_config` (`id`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `encryption`, `from_email`, `from_name`, `is_active`, `updated_by`, `updated_at`) VALUES
(1, 'smtp.gmail.com', 587, 'lovelacejohnkwakubaidoo@gmail.com', '', 'tls', 'lovelacejohnkwakubaidoo@gmail.com', 'RMU Medical Sickbay', 1, NULL, '2026-04-17 05:47:48');

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
-- Table structure for table `test_services`
--

DROP TABLE IF EXISTS `test_services`;
CREATE TABLE IF NOT EXISTS `test_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT '0.00',
  `turnaround_time` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_attempts`
--

DROP TABLE IF EXISTS `two_factor_attempts`;
CREATE TABLE IF NOT EXISTS `two_factor_attempts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts_made` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `resends_made` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `two_factor_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `emergency_contact_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse','lab_technician','finance_officer','finance_manager','ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_type` enum('student','staff') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Only for patient role',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `account_status` enum('active','inactive','suspended','pending_verification') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_verification',
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `status` enum('active','pending','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `two_fa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `accepted_terms` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`user_name`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`user_role`)
) ENGINE=InnoDB AUTO_INCREMENT=318 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `email`, `password`, `two_factor_secret`, `two_factor_enabled`, `profile_photo`, `emergency_contact_name`, `emergency_contact_phone`, `user_role`, `patient_type`, `name`, `phone`, `gender`, `date_of_birth`, `profile_image`, `account_status`, `is_active`, `is_verified`, `status`, `created_at`, `updated_at`, `last_login`, `last_active_at`, `last_login_at`, `locked_until`, `force_password_change`, `two_fa_enabled`, `last_login_ip`, `accepted_terms`) VALUES
(1, 'Lovelace', 'admin@rmu.edu.gh', '$2y$10$oPwC5CopfH8UPh6SFrpvi.hzRUTfmNpcc3ZI2Lmy9SUlTmtUJfdDK', NULL, 0, 'default-avatar.png', NULL, NULL, 'admin', NULL, 'System Administrator', '0502371207', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-02-06 05:09:21', '2026-05-14 10:28:12', '2026-03-26 16:42:55', NULL, '2026-05-14 10:28:12', NULL, 0, 0, '::1', 1),
(20, 'JE', 'eli.joyce@st.rmu.edu.gh', '$2y$10$tfaPj3KiYWTLW8smrAkUROmB5qGWGixabIKW7vM5YgVdL8PseBg7O', NULL, 0, 'default-avatar.png', NULL, NULL, 'doctor', NULL, 'Joyce Eli', '0241439494', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'pending', '2026-03-18 01:42:31', '2026-04-20 12:15:03', '2026-03-18 02:05:29', NULL, '2026-04-20 12:15:03', NULL, 0, 0, '::1', 1),
(26, 'Neils', 'nartey.nelly@st.rmu.edu.gh', '$2y$10$ORu/5fqFiwSsZNHvt/dOx.PvbQobc4QesdexhFo9ek6wdRrBdu21q', NULL, 0, 'default-avatar.png', NULL, NULL, 'nurse', NULL, 'Nelly Nartey', '0272814681', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-03-20 03:10:01', '2026-04-20 11:31:37', '2026-03-25 17:32:36', NULL, '2026-04-20 11:31:37', NULL, 0, 0, '::1', 1),
(28, 'FJ', 'jefferson.forson@st.rmu.edu.gh', '$2y$10$jcr7h1mIQH3KG6fH1ggoYe1ra.5KGmzCrv1FESAFtVcYnAll2vYhi', NULL, 0, 'default-avatar.png', NULL, NULL, 'lab_technician', NULL, 'Jefferson Forson', '0500168225', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-03-23 07:31:52', '2026-04-20 04:02:53', '2026-03-25 05:40:47', NULL, '2026-04-20 04:02:53', NULL, 0, 0, '::1', 1),
(35, 'Ahwenei', 'atakorahe57@gmail.com', '$2y$10$nKbuy7okuT2LJXaOtMKOs.Ha1z.Ou9VK1bRcio2acFzaneFCTKQDO', NULL, 0, 'default-avatar.png', NULL, NULL, 'finance_officer', NULL, 'Dzimado Emmanuel Nana Atakorah', '0244456597', 'Male', '1999-09-29', '/RMU-Medical-Management-System/uploads/profile_photos/f369fd6f9e50c7ed573d4c52.jpg', 'active', 1, 1, 'pending', '2026-04-09 14:58:29', '2026-05-14 10:13:07', NULL, NULL, '2026-05-14 10:13:07', NULL, 0, 0, '::1', 1),
(36, 'Lil', 'lovelace.baidoo@st.rmu.edu.gh', '$2y$10$6ZjeTL6onVtN1vylmlDqSOOv6t7M77Oi/vRZUpsXsapeI/FPuxeD2', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', 'student', 'Lovelace John Kwaku Baidoo', '0502371207', 'Male', '2004-03-24', '/RMU-Medical-Management-System/uploads/profile_photos/5554beb626da8b0f708b5156.jpg', 'active', 1, 1, 'pending', '2026-04-11 00:11:32', '2026-04-15 12:38:04', NULL, NULL, '2026-04-15 12:38:04', NULL, 0, 0, '::1', 1),
(37, 'kwame.mensah.doc14', 'kwame.mensah.doc@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'doctor', NULL, 'Dr. Kwame Mensah', NULL, 'Male', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-12-18 06:56:04', '2026-04-15 10:25:18', NULL, NULL, '2026-04-15 10:25:18', NULL, 0, 0, '::1', 1),
(38, 'abena.asante.doc29', 'abena.asante.doc@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'doctor', NULL, 'Dr. Abena Asante', NULL, 'Female', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-05-14 10:55:08', '2026-04-15 10:26:14', NULL, NULL, '2026-04-15 10:26:14', NULL, 0, 0, '::1', 1),
(39, 'samuel.boateng.doc80', 'samuel.boateng.doc@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'doctor', NULL, 'Dr. Samuel Boateng', NULL, 'Male', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-05-17 02:20:08', '2026-04-12 10:22:48', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(40, 'efua.owusu.nurse30', 'efua.owusu.nurse@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'nurse', NULL, 'Nurse Efua Owusu', NULL, 'Female', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-09-21 08:15:25', '2026-04-15 10:27:05', NULL, NULL, '2026-04-15 10:27:05', NULL, 0, 0, '::1', 1),
(41, 'isaac.appiah.nurse25', 'isaac.appiah.nurse@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'nurse', NULL, 'Nurse Isaac Appiah', NULL, 'Male', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-10-01 18:54:51', '2026-04-12 10:22:48', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(42, 'emmanuel.kofi91', 'emmanuel.kofi@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Patient Emmanuel Kofi', NULL, 'Male', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-11-15 21:54:29', '2026-04-13 08:09:03', NULL, NULL, '2026-04-13 08:09:03', NULL, 0, 0, '::1', 1),
(43, 'yaa.acheampong78', 'yaa.acheampong@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Patient Yaa Acheampong', NULL, 'Female', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-11-04 00:16:35', '2026-04-12 10:22:48', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(44, 'nana.gyamfi89', 'nana.gyamfi@rmu.test', '$2y$10$mUPoBezJF9Hhl9CYSYft2.D3e5YvHh/DxEbvmSIC/UOJ3NFBQxt8W', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Patient Nana Gyamfi', NULL, 'Male', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2025-04-27 20:12:43', '2026-04-12 10:22:48', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(101, 'Jane', 'jane.doe@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Jane Doe', '0240000101', 'Female', '1995-05-15', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-15 11:54:44', NULL, NULL, '2026-04-15 11:54:44', NULL, 0, 0, '::1', 1),
(102, 'John', 'john.smith@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'John Smith', '0240000102', 'Male', '1990-08-22', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(103, 'Alice', 'alice.wonder@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Alice Wonder', '0240000103', 'Female', '1988-12-01', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(104, 'Bob', 'bob.builder@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Bob Builder', '0240000104', 'Male', '1985-04-10', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(105, 'Charlie', 'charlie.brown@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Charlie Brown', '0240000105', 'Male', '2000-01-25', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(106, 'Diana', 'diana.prince@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Diana Prince', '0240000106', 'Female', '1992-06-18', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(107, 'Edward', 'edward.eric@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Edward Elric', '0240000107', 'Male', '2002-11-11', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(108, 'Fiona', 'fiona.shrek@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Fiona Shrek', '0240000108', 'Female', '1996-03-30', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(109, 'George', 'george.jetson@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'George Jetson', '0240000109', 'Male', '1975-09-09', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(110, 'Montana', 'hannah.montana@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', NULL, 'Hannah Montana', '0240000110', 'Female', '2004-07-07', 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:09:33', '2026-04-14 00:09:33', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(202, 'Adelaide', 'es-anadjei@st.umat.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'nurse', NULL, 'Nurse Adelaide Adjei Naa Adjeley', '0507333138', 'Female', NULL, 'default-avatar.png', 'active', 1, 1, 'pending', '2026-04-14 00:22:23', '2026-04-17 08:31:14', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(203, 'Jemima Amanor', 'jemimaamanor@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'pharmacist', NULL, 'Pharm. Jemima Amanor', '0596269993', 'Female', NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-04-14 00:22:23', '2026-04-17 09:50:59', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(204, 'Elton', 'elton.modern@st.rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'finance_manager', NULL, 'Finance Mgr. Elton Modern John', '0200000204', 'Male', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:22:23', '2026-04-14 00:22:23', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(301, 'Jerry', 'amb1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Jeremaiah Rockson', '0500000301', 'Male', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(302, 'Clara', 'clean1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Clara Asante', '0500000302', 'Female', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(303, 'OTF', 'laundry1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Joseph Agyemang', '0500000303', 'Male', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(304, 'Mike', 'maint1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Mike Rashford', '0500000304', 'Male', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(305, 'Taller', 'sec1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Sam Taller', '0500000305', 'Male', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(306, 'Abbys', 'kitchen1@rmu.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, 'default-avatar.png', NULL, NULL, 'staff', NULL, 'Chef Abbys', '0500000306', 'Female', NULL, 'default-avatar.png', 'active', 1, 0, 'pending', '2026-04-14 00:31:26', '2026-04-14 00:31:26', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(307, 'Samuel', 'samuel.enguah@rmu.edu.gh', '$2y$10$OLTE1RwY88mwBY8kSAgtG.gErSiVAyAKfsJJi/JgJcjcNte4c39W2', NULL, 0, 'default-avatar.png', NULL, NULL, 'patient', 'staff', 'Samuel Enguah', '0244537039', 'Male', '1979-08-05', NULL, 'active', 1, 1, 'pending', '2026-04-15 12:15:15', '2026-04-15 12:15:44', NULL, NULL, '2026-04-15 12:15:44', NULL, 0, 0, '::1', 1),
(308, 'Barns', 'junior.barns@gmail.com', '$2y$10$fzANVonLzZMkitwVmu4cCekMn2.RL.K99eE6zRmQufytfBS9VeE82', NULL, 0, 'default-avatar.png', NULL, NULL, '', NULL, 'Junior Barns', '0244537039', 'Male', '2000-03-24', NULL, 'active', 1, 1, 'pending', '2026-04-15 12:30:00', '2026-04-15 12:33:52', NULL, NULL, NULL, NULL, 0, 0, '', 1),
(312, 'Shurface', 'www.lovelacejohnbaidoo@gmail.com', '$2y$10$S26HJAH4.9tDvkh.LyKnkuGhMv5DUeStBXYxgpXIfo3AdNrNsJP.y', NULL, 0, 'default-avatar.png', NULL, NULL, 'pharmacist', NULL, 'Lil Shurface', '0247150041', 'Male', '2004-03-24', NULL, 'active', 1, 1, 'pending', '2026-04-17 09:30:44', '2026-04-20 11:32:43', NULL, NULL, '2026-04-20 11:32:43', NULL, 0, 0, '::1', 1),
(313, 'OTFXLIMKID', 'joseph.agyemang@st.rmu.edu.gh', '$2y$10$NRiVCat.jOqYd.sLwC6gBuzBIvnkwmuSzBfasNhCvSPwVEiISkjP6', NULL, 0, 'default-avatar.png', NULL, NULL, 'maintenance', NULL, 'Joseph Agyemang', '0538643962', 'Male', '2002-03-21', NULL, 'active', 1, 1, 'pending', '2026-04-21 04:32:02', '2026-05-13 08:58:23', NULL, NULL, '2026-05-13 08:58:23', NULL, 0, 0, '::1', 1),
(314, 'Biggie', 'bernard.boateng@st.rmu.edu.gh', '$2y$10$OJYmtnSvdxBqa.ZkTPB5TuhzpzdlIWf3hAAMNhNRW2oYTv9bbtUxq', NULL, 0, 'default-avatar.png', NULL, NULL, 'security', NULL, 'Bernard Boateng', '0550806918', 'Male', '2002-06-04', NULL, 'active', 1, 1, 'pending', '2026-04-21 09:14:07', '2026-05-13 20:07:30', NULL, NULL, '2026-05-13 20:07:30', NULL, 0, 0, '::1', 1),
(315, 'Gifty', 'gifty.asante@st.rmu.edu.gh', '$2y$10$mhD8lPOOSb8ArSQzfVX.8OqeB8XITyoXRGbjLbz/3gcMzaIcXj3ZK', NULL, 0, 'default-avatar.png', NULL, NULL, 'cleaner', NULL, 'Gifty Asante', '02557019833', 'Female', '2006-04-26', NULL, 'active', 1, 1, 'pending', '2026-04-21 10:12:56', '2026-05-14 09:39:24', NULL, NULL, '2026-05-14 09:39:24', NULL, 0, 0, '::1', 1),
(316, 'Face', 'micheeal.asante@st.rmu.edu.gh', '$2y$10$AsQfXwPDs5WzGJDGZ3T92.wXEeY4Lyr1jg2tHNGLsz6TB6SDXVaTW', NULL, 0, 'default-avatar.png', NULL, NULL, 'ambulance_driver', NULL, 'Micheal Asante', '0548003482', 'Male', '2004-06-15', NULL, 'active', 1, 1, 'pending', '2026-05-05 06:45:21', '2026-05-14 09:40:13', NULL, NULL, '2026-05-14 09:40:13', NULL, 0, 0, '::1', 1),
(317, 'Junior', 'junior.owusu@st.rmu.edu.gh', '$2y$10$CWLE7yod1.WS59fNJ1NQHeogNpWRN6pFqhiJuChYCaPGrjAWolKBG', NULL, 0, 'default-avatar.png', NULL, NULL, 'finance_manager', NULL, 'Junior Owusu', '0538643962', 'Male', '1967-09-30', NULL, 'active', 1, 1, 'pending', '2026-05-14 10:27:49', '2026-05-14 10:29:39', NULL, NULL, '2026-05-14 10:29:39', NULL, 0, 0, '::1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_notification_prefs`
--

DROP TABLE IF EXISTS `user_notification_prefs`;
CREATE TABLE IF NOT EXISTS `user_notification_prefs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `event_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `in_app` tinyint(1) NOT NULL DEFAULT '1',
  `email` tinyint(1) NOT NULL DEFAULT '0',
  `push` tinyint(1) NOT NULL DEFAULT '0',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_event` (`user_id`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_registration_audit`
--

DROP TABLE IF EXISTS `user_registration_audit`;
CREATE TABLE IF NOT EXISTS `user_registration_audit` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URA-{uniqid}',
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` enum('registered','otp_sent','otp_verified','otp_failed','approved','rejected','suspended','reactivated','password_reset','email_changed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self' COMMENT 'self or admin user_id as string',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_id` (`audit_id`),
  KEY `idx_ura_user` (`user_id`),
  KEY `idx_ura_action` (`action`),
  KEY `idx_ura_time` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_registration_audit`
--

INSERT INTO `user_registration_audit` (`id`, `audit_id`, `user_id`, `action`, `performed_by`, `ip_address`, `device_info`, `notes`, `created_at`) VALUES
(2, 'URA-69cbdc9c6de1f', 20, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-03-31 14:39:24'),
(3, 'URA-69d7be9565677', 35, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', NULL, '2026-04-09 14:58:29'),
(4, 'URA-69d7c08676b9d', 35, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-09 15:06:46'),
(5, 'URA-69d991b46099f', 36, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', NULL, '2026-04-11 00:11:32'),
(6, 'URA-69df81533ce85', 307, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-15 12:15:15'),
(7, 'URA-69df84c8a1b69', 308, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-15 12:30:00'),
(8, 'URA-69df85b010764', 308, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-15 12:33:52'),
(9, 'URA-69e1efd2d6810', 202, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-17 08:31:14'),
(10, 'URA-69e1efdcd0bdc', 201, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-17 08:31:24'),
(11, 'URA-69e1efebd032f', 203, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-17 08:31:39'),
(12, 'URA-TEST-69e1f9d2ee81b', 1, '', 'self', '127.0.0.1', 'Mozilla/5.0', 'Test notes', '2026-04-17 09:13:54'),
(13, 'URA-69e1fdc434c5a', 312, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-17 09:30:44'),
(14, 'URA-69e1fde59df3b', 312, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-17 09:31:17'),
(15, 'URA-69e1fdfab1f74', 312, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-17 09:31:38'),
(16, 'URA-69e6fdc26afd9', 313, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-21 04:32:02'),
(17, 'URA-69e6fe0b2b8e8', 313, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-21 04:33:15'),
(18, 'URA-69e73fdf57da0', 314, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-21 09:14:07'),
(19, 'URA-69e740049a5af', 314, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-21 09:14:44'),
(20, 'URA-69e74da8c826e', 315, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-21 10:12:56'),
(21, 'URA-69e755310b173', 315, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-04-21 10:45:05'),
(22, 'URA-69f99201774b2', 316, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-05-05 06:45:21'),
(23, 'URA-69f99a985366a', 316, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-05-05 07:22:00'),
(24, 'URA-6a05a3a50e135', 317, 'otp_verified', 'self', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-05-14 10:27:49'),
(25, 'URA-6a05a3dfd4786', 317, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-05-14 10:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse','finance_officer','finance_manager') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
('004fd6d1b9ba0d589173acc21aeb180365cf2d2186a51684f95d09d4f084bcf8', 1, 'admin', '2026-03-23 07:48:15', '2026-03-23 07:48:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-23 07:48:27'),
('0075d2deaba7c385dc3243064c57510e494b7f1ed83ad35b7537067cadbae17b', 1, 'admin', '2026-03-17 12:02:23', '2026-03-17 12:02:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 12:02:45'),
('00a1044ddb264d0892c8dba6c67693b51d58158bd57449e597d0e73cfb96c177', 1, 'admin', '2026-03-18 02:06:53', '2026-03-18 02:06:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 02:08:58'),
('071d47ff55001adf7fcb9cf9b176c2359c56d8c64f12906076d7ba530253d083', 1, 'admin', '2026-03-23 07:41:40', '2026-03-23 07:41:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-23 07:42:02'),
('11a0489b3dbe14901283fab67aa63888aab19c76d9dd042b9c6c5de4b886943a', 1, 'admin', '2026-03-12 13:16:58', '2026-03-12 13:16:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-12 13:20:31'),
('11d27fd234ffc1259dd58f07941f8daf5b845e76ca0016735ccddc46949828e0', 1, 'admin', '2026-03-25 12:31:41', '2026-03-25 12:31:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-25 16:15:17'),
('171601c0f0a3b961b5094197d3b3eabe66ed9adef933b49048d8b00ca1fdb9d9', 20, 'doctor', '2026-03-18 02:05:29', '2026-03-18 02:05:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 02:06:15'),
('19f578c5bd46f88defdec87fbcbba7d23cf26796d108ca042fc88c41e6d13477', 1, 'admin', '2026-03-14 17:41:18', '2026-03-14 17:41:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-16 11:53:57'),
('212e8ec73220ce4c67d0a55db3b2a35941a74befb997e383a39afa6110f245b2', 1, 'admin', '2026-02-16 07:51:52', '2026-02-16 07:51:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-20 18:34:22'),
('230d2754bef61069231676257436eb1b50f08524d997d3f2da872542e0d8dde8', 26, 'nurse', '2026-03-20 03:16:53', '2026-03-20 03:16:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 04:35:48'),
('27128dcb4f3d9016cc8b9f536ebee3b5aff759228d92bc13007143aab8e6a1a3', 1, 'admin', '2026-03-20 03:07:08', '2026-03-20 03:07:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 03:09:27'),
('2fd65ea74b47a8200f74682d8d61b41cd33cdc3ea49a604a3be059e212d39f65', 1, 'admin', '2026-02-14 04:25:18', '2026-02-14 04:25:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 08:09:22'),
('34b5f02399c71ca3226f7c0ccabe8c4e360ae66fc069cd38d0bf7f20e4f02441', 1, 'admin', '2026-03-14 04:38:47', '2026-03-14 04:38:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-14 04:41:33'),
('3e3995ade1fac3c0da276a6698d4e0e402b7040ad2576cb6bd95616d0ea89869', 1, 'admin', '2026-02-14 08:09:22', '2026-02-14 08:09:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-15 16:05:18'),
('40f833da13e0cb60b8cb0824b34194cb5c0028fa422e1e6aa03c78690f3557e1', 1, 'admin', '2026-03-18 05:02:20', '2026-03-18 05:02:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 05:03:11'),
('4afbd7d31b83aa9d2c11730acdaeee97df326ed2dbfff557300961f89a1dc455', 1, 'admin', '2026-03-26 12:08:15', '2026-03-26 12:08:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-26 12:24:25'),
('53747aa01eb97130a6ee826edcea4d2e823517c615ca1a05a6a02e964fd70141', 1, 'admin', '2026-02-27 02:57:24', '2026-02-27 02:57:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 03:15:38'),
('54473515a51e47fa87a9f2c7a4be140a34ca881d3b6df82c3fb09e939991efa4', 1, 'admin', '2026-02-20 20:10:45', '2026-02-20 20:10:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-20 21:34:16'),
('54ed60c03b5928b4ca4905b808249f17db312bcc5e4404e7f5882e002041ee7c', 26, 'nurse', '2026-03-20 16:01:27', '2026-03-20 16:01:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 16:03:47'),
('5667cdd0b22593e02e5d26ab53eb4641c2da1f93fa87e47fa9ae2c565eeabd75', 1, 'admin', '2026-02-20 18:34:23', '2026-02-20 18:34:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-20 20:10:45'),
('58ff3315e9eee30989b77b7dc13a57de7f76eadcc1a2daeb65298900f83c247f', 1, 'admin', '2026-02-14 03:24:15', '2026-02-14 03:24:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 04:25:18'),
('5c83b1c0da4b7c3e6e13836aca28c5a74d3d2660a44ac0c03b923fd79a3c4972', 26, 'nurse', '2026-03-20 04:54:01', '2026-03-20 04:54:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 04:54:50'),
('5db5a8d670a9212b1a6a91bbfbc2f8bcb8b5d7febbd1cc26afd3daf167578acf', 28, '', '2026-03-25 05:40:47', '2026-03-25 05:40:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 1, NULL),
('607ca6d0b58b8ffd19b83fc69281140dbd3d3a680419646d8b3acbbe9a71a211', 1, 'admin', '2026-03-16 20:10:24', '2026-03-16 20:10:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-16 20:12:33'),
('6456cb7302273c1eb5a91b7a0d8d5833c3b17a315d38104d9b9831f18542f700', 1, 'admin', '2026-02-15 16:05:18', '2026-02-15 16:05:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 05:49:20'),
('6939a131446a26d0d9e8aa1f28f7a5757214598e33c6df505c9df668044a1823', 1, 'admin', '2026-03-20 03:10:24', '2026-03-20 03:10:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 03:13:47'),
('6964d18557874f666c5a51695ad2079d2b6f0d2cc9a3afd49f3df20401613b7a', 1, 'admin', '2026-03-26 16:42:55', '2026-03-26 16:42:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-26 16:48:03'),
('6ed0bb9da5dc304d2da3cfe57bab633d3ab52bc9508a0c4139a5cf7b8aebde85', 26, 'nurse', '2026-03-25 17:32:36', '2026-03-25 17:32:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-25 17:59:56'),
('700d418783e47556dac4493edcc03c5d347fa45e4bed3196fbf70991143cdd59', 1, 'admin', '2026-03-17 00:09:35', '2026-03-17 00:09:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 11:46:13'),
('713fa499420babc299e8f09846881522c58235ba5565becd31f8cd0084d4f8e3', 1, 'admin', '2026-03-20 03:15:49', '2026-03-20 03:15:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 03:16:31'),
('8668fd0291fa50464cbbb6ece1dfed0fb563e1c45be98a975ef02fcea9ba918f', 1, 'admin', '2026-03-26 15:28:02', '2026-03-26 15:28:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-26 16:42:55'),
('8704ed8e1e16e353151ce56900491169f5b2bfb90059b4dedbcb684c31ae6798', 1, 'admin', '2026-03-17 11:46:13', '2026-03-17 11:46:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 11:47:35'),
('877f57bd64d7d04a35a551b1823d2ab6536a52233ade49565db8bafc2b6d4f05', 1, 'admin', '2026-03-14 07:01:12', '2026-03-14 07:01:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-14 17:41:18'),
('9160b645374891297205a471e8542d1f644de71960d655d009b69f0cbfcffc4f', 1, 'admin', '2026-03-26 12:29:26', '2026-03-26 12:29:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-26 12:30:37'),
('971cb21a48dfc78e8c727f39756d6b89603f430978d66e1ee79cbb25c59625ac', 1, 'admin', '2026-02-14 03:15:40', '2026-02-14 03:15:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-14 03:24:15'),
('9c22e1d2372fa03042f3c2746a123e6326800063a164393070972d7e083cb690', 26, 'nurse', '2026-03-24 21:22:08', '2026-03-24 21:22:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-25 05:06:16'),
('a4c0bb15aa7d6f2bd159dd4c9e06c410f2e0f2c505e6bccd41de91e190ccd448', 1, 'admin', '2026-03-17 18:26:57', '2026-03-17 18:26:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 18:27:57'),
('a78fee896aa0047f418451c10437d4b4c8aebb6ed7becaf5e7b68a727fbc5d87', 1, 'admin', '2026-03-17 22:51:57', '2026-03-17 22:51:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 22:52:42'),
('a79c3c0e2ed5ce5b5cc6ab01bae3b7e4683f769e87ee828478e503b7db2dceeb', 1, 'admin', '2026-03-18 02:03:56', '2026-03-18 02:03:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 02:04:27'),
('ac3a2406f545ffdc7ebc37710767b34df96123be30ef4dcab562d68e3ca5d546', 1, 'admin', '2026-02-20 22:29:44', '2026-02-20 22:29:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-02-27 02:57:24'),
('ad08bd5f245b66c290f42f4ad11732da1b474b48f11f551d0ec8e064fb8a9b7c', 1, 'admin', '2026-02-16 05:49:20', '2026-02-16 05:49:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 0, '2026-02-16 07:39:38'),
('b804e4f57391d66f84b766f06d8223c85e48ca2a2d44e895835fb36c058a2c8d', 1, 'admin', '2026-03-17 17:31:04', '2026-03-17 17:31:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 17:31:49'),
('bcc1d95ef1e3daad6a741258e380a2fc5ead12c757cc5f3975223356f305458b', 1, 'admin', '2026-03-18 04:01:39', '2026-03-18 04:01:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 04:02:09'),
('c062e5d23fe336e25a21b52fd281add4919f4180341b42d009b90cc87113eb43', 26, 'nurse', '2026-03-20 05:21:14', '2026-03-20 05:21:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 05:39:43'),
('c271a4cdb67b498a275de0adfd8464329720455ee53d6ac6fa4ffd406a4cf539', 1, 'admin', '2026-03-17 12:29:40', '2026-03-17 12:29:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 12:30:52'),
('caffd1c5bcb806b2fb46db88819df3be94b2a56f081bd280d4845aca8e7154b2', 1, 'admin', '2026-03-23 09:26:51', '2026-03-23 09:26:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-24 12:29:16'),
('ce3f7fd53fb0158276d2100b2c6c9e220f15b97ba0c858aac5814cbbd4cc1439', 1, 'admin', '2026-03-17 23:08:52', '2026-03-17 23:08:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 23:10:10'),
('ce69abe88ed7f5efda98109778e485aa80117caf1669a9d2892939a1a3139b20', 1, 'admin', '2026-03-01 09:22:47', '2026-03-01 09:22:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-01 09:24:02'),
('d2b07a9500c94dc2e6d6e72de28d8668278de9e8803db3de8f5b6bf8e9166e4a', 1, 'admin', '2026-03-21 08:09:37', '2026-03-21 08:09:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-22 13:51:02'),
('d3e163abd2f60e67ad9ac48bdee3036368bc290b28791530392d84f3071884f6', 1, 'admin', '2026-03-18 02:50:01', '2026-03-18 02:50:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-18 02:51:22'),
('d485646fa56eeaba8d6cda30d3c44db0a0da974697f25786d7bf88f8d674496e', 1, 'admin', '2026-03-16 11:58:49', '2026-03-16 11:58:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-03-16 20:10:24'),
('d65cb3cb018792188112fcfa158b35bd213ce8954dfeb8171bb3a64f01acd670', 1, 'admin', '2026-03-23 05:11:32', '2026-03-23 05:11:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-23 07:28:01'),
('d767bd08b13225444338024167ebb8df8b719a69f6a471f55293b4bf2d654898', 28, '', '2026-03-23 08:08:48', '2026-03-23 08:08:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-23 09:25:05'),
('e4e2929a2bdb2a32746e7b1a0c95361a2eef9eec8fb820530a7d2ff7009504ae', 1, 'admin', '2026-03-25 16:15:17', '2026-03-25 16:15:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-25 16:23:18'),
('e551f8af1507d2913fef999aa27c57e255030d055bd7914edb2137646ddedec3', 1, 'admin', '2026-03-25 17:24:55', '2026-03-25 17:24:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-25 17:29:40'),
('e6a2c9c74a058760e703580e0e3531aaf66430d67891fba3bacd2adebe3e9ea5', 1, 'admin', '2026-03-20 18:57:08', '2026-03-20 18:57:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 19:06:05'),
('e76bf90769f41fbb0f5d66ea16b1c546b4c2d03b5d94c0ecb43b4c1d375265b9', 26, 'nurse', '2026-03-20 04:42:35', '2026-03-20 04:42:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-20 04:43:23'),
('e85429bb7fe12cce0f57845a3d794866858f74b9f5c651493d399f199011bb8d', 1, 'admin', '2026-03-23 07:32:19', '2026-03-23 07:32:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-23 07:40:38'),
('eb7f2c9547e9a11e9d431baeb0808732caf9a71f81174b30fa7dde0b809e305f', 1, 'admin', '2026-03-17 11:51:54', '2026-03-17 11:51:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 0, '2026-03-17 11:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `vehicle_id` int NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `type` enum('ambulance','utility','other') COLLATE utf8mb4_unicode_ci DEFAULT 'ambulance',
  `fuel_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_mileage` int DEFAULT '0',
  `status` enum('available','in use','maintenance','out of service') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `assigned_driver_id` int DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `uk_reg_no` (`registration_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `registration_number`, `make`, `model`, `year`, `type`, `fuel_type`, `current_mileage`, `status`, `assigned_driver_id`, `last_service_date`, `next_service_date`, `insurance_expiry`, `notes`, `created_at`) VALUES
(1, 'GV-101-24', 'Toyota', 'Hiace Ambulance', 2024, 'ambulance', NULL, 0, 'available', 301, NULL, NULL, NULL, NULL, '2026-04-14 06:54:50'),
(2, 'GV-102-23', 'Mercedes-Benz', 'Sprinter Ambulance', 2023, 'ambulance', NULL, 0, 'in use', NULL, NULL, NULL, NULL, NULL, '2026-04-14 06:54:50'),
(3, 'GV-105-22', 'Nissan', 'Navara Utility', 2022, 'utility', NULL, 0, 'available', NULL, NULL, NULL, NULL, NULL, '2026-04-14 06:54:50'),
(4, 'GV-108-24', 'Toyota', 'Land Cruiser Ambulance', 2024, 'ambulance', NULL, 0, 'maintenance', NULL, NULL, NULL, NULL, NULL, '2026-04-14 06:54:50'),
(5, 'GV-110-21', 'Ford', 'Transit', 2021, 'utility', NULL, 0, 'available', NULL, NULL, NULL, NULL, NULL, '2026-04-14 06:54:50');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_fuel_logs`
--

DROP TABLE IF EXISTS `vehicle_fuel_logs`;
CREATE TABLE IF NOT EXISTS `vehicle_fuel_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `logged_by_staff_id` int NOT NULL,
  `fuel_litres` decimal(8,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `odometer_reading` int DEFAULT NULL,
  `notes` text,
  `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Table structure for table `visitor_logs`
--

DROP TABLE IF EXISTS `visitor_logs`;
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `logged_by` int NOT NULL COMMENT 'staff_id of security staff who logged the visitor',
  `visitor_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'National ID or passport number',
  `visitor_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_visiting` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name of staff or patient being visited',
  `ward_department` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `exit_time` datetime DEFAULT NULL,
  `badge_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_reg` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','checked_out','overstay') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_logged_by` (`logged_by`),
  KEY `idx_entry_time` (`entry_time`),
  KEY `idx_exit_time` (`exit_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visitor_logs`
--

INSERT INTO `visitor_logs` (`log_id`, `logged_by`, `visitor_name`, `visitor_id_number`, `visitor_phone`, `purpose`, `person_visiting`, `ward_department`, `entry_time`, `exit_time`, `badge_number`, `vehicle_reg`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 14, 'Samuel Mensah', 'GHA-1234567-8', NULL, 'Visit Patient Pat-101', NULL, NULL, '2026-04-13 23:02:31', NULL, NULL, NULL, NULL, 'checked_out', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(2, 14, 'Gladys Owusu', 'GHA-8765432-1', NULL, 'Delivery - Medical Supplies', NULL, NULL, '2026-04-14 00:02:31', NULL, NULL, NULL, NULL, 'active', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(3, 14, 'Ben Johnson', 'NY-5556667', NULL, 'Visit Patient Pat-105', NULL, NULL, '2026-04-14 00:32:31', NULL, NULL, NULL, NULL, 'active', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(4, 14, 'Evelyn Addo', 'GHA-9990001-2', NULL, 'Pharmaceutical Sales Representative', NULL, NULL, '2026-04-13 22:02:31', NULL, NULL, NULL, NULL, 'checked_out', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(5, 14, 'Maintenance Crew A', 'CO-55522', NULL, 'Scheduled Elevator Inspection', NULL, NULL, '2026-04-13 21:02:31', NULL, NULL, NULL, NULL, 'checked_out', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(6, 14, 'Mary Frimpong', 'GHA-7778882-3', NULL, 'Visit Patient Pat-102', NULL, NULL, '2026-04-14 00:47:31', NULL, NULL, NULL, NULL, 'active', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(7, 14, 'Official Inspector', 'GOV-IND-01', NULL, 'Sanitary Inspection', NULL, NULL, '2026-04-13 20:02:31', NULL, NULL, NULL, NULL, 'checked_out', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(8, 14, 'Kwame Nkrumah Jr.', 'GHA-1111111-1', NULL, 'Personal Consultation', NULL, NULL, '2026-04-14 00:52:31', NULL, NULL, NULL, NULL, 'active', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(9, 14, 'Lucy Smith', 'UK-PP-222333', NULL, 'International Research Student', NULL, NULL, '2026-04-13 19:02:31', NULL, NULL, NULL, NULL, 'checked_out', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(10, 14, 'Emergency Contact 108', 'GHA-2224445-6', NULL, 'Family Visitation', NULL, NULL, '2026-04-13 13:02:31', NULL, NULL, NULL, NULL, 'overstay', '2026-04-14 01:02:31', '2026-04-14 01:02:31'),
(11, 18, 'Lovelace John kwaku Baidoo', 'GHA-724127931-4', NULL, 'Patient Visit', 'Lil', 'General Ward', '2026-05-13 19:53:35', NULL, NULL, NULL, NULL, 'active', '2026-05-13 19:53:35', '2026-05-13 19:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `vital_thresholds`
--

DROP TABLE IF EXISTS `vital_thresholds`;
CREATE TABLE IF NOT EXISTS `vital_thresholds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vital_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bp_systolic, temperature, pulse_rate…',
  `patient_category` enum('Adult','Pediatric','Elderly','Pregnant','General') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `display_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_normal` decimal(8,2) DEFAULT NULL,
  `max_normal` decimal(8,2) DEFAULT NULL,
  `critical_low` decimal(8,2) DEFAULT NULL,
  `critical_high` decimal(8,2) DEFAULT NULL,
  `updated_by` int DEFAULT NULL COMMENT 'FK → users.id',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vital_type` (`vital_type`),
  KEY `updated_by` (`updated_by`),
  KEY `category_vital` (`patient_category`,`vital_type`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Normal and critical value ranges for each vital sign type';

--
-- Dumping data for table `vital_thresholds`
--

INSERT INTO `vital_thresholds` (`id`, `vital_type`, `patient_category`, `display_name`, `unit`, `min_normal`, `max_normal`, `critical_low`, `critical_high`, `updated_by`, `updated_at`) VALUES
(1, 'bp_systolic', 'General', 'Blood Pressure (Systolic)', 'mmHg', 90.00, 140.00, 70.00, 180.00, NULL, '2026-03-20 02:26:09'),
(2, 'bp_diastolic', 'General', 'Blood Pressure (Diastolic)', 'mmHg', 60.00, 90.00, 40.00, 120.00, NULL, '2026-03-20 02:26:09'),
(3, 'pulse_rate', 'General', 'Pulse Rate', 'bpm', 60.00, 100.00, 40.00, 150.00, NULL, '2026-03-20 02:26:09'),
(4, 'temperature', 'General', 'Temperature', 'C', 36.10, 37.20, 35.00, 39.50, NULL, '2026-03-20 02:26:09'),
(5, 'oxygen_saturation', 'General', 'Oxygen Saturation (SpO2)', '%', 95.00, 100.00, 88.00, 100.00, NULL, '2026-03-20 02:26:09'),
(6, 'respiratory_rate', 'General', 'Respiratory Rate', 'breaths/min', 12.00, 20.00, 8.00, 30.00, NULL, '2026-03-20 02:26:09'),
(7, 'blood_glucose', 'General', 'Blood Glucose', 'mg/dL', 70.00, 140.00, 50.00, 400.00, NULL, '2026-03-20 02:26:09'),
(8, 'bmi', 'General', 'Body Mass Index', 'kg/m2', 18.50, 24.90, 15.00, 40.00, NULL, '2026-03-20 02:26:09');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

DROP TABLE IF EXISTS `wards`;
CREATE TABLE IF NOT EXISTS `wards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ward_name` varchar(200) NOT NULL,
  `department_id` int DEFAULT NULL,
  `capacity` int DEFAULT '0',
  `status` enum('Active','Inactive','Full','Maintenance') DEFAULT 'Active',
  `current_occupancy` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `ward_name`, `department_id`, `capacity`, `status`, `current_occupancy`, `created_at`, `updated_at`) VALUES
(1, 'Emergency', NULL, 20, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(2, 'ICU', NULL, 15, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(3, 'Maternity', NULL, 30, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(4, 'Pediatrics', NULL, 25, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(5, 'General Ward A', NULL, 40, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(6, 'General Ward B', NULL, 40, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(7, 'Isolation', NULL, 10, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38'),
(8, 'Ward A - General', NULL, 38, 'Active', 1, '2025-02-09 07:28:33', '2026-04-12 10:14:05'),
(9, 'Ward B - Surgical', NULL, 37, 'Active', 2, '2025-03-29 13:50:11', '2026-04-12 10:14:05'),
(10, 'ICU', NULL, 20, 'Active', 2, '2024-07-18 02:15:05', '2026-04-12 10:14:05'),
(11, 'Maternity Ward', NULL, 14, 'Active', 0, '2024-10-22 14:19:06', '2026-04-12 10:14:05'),
(12, 'Pediatric Ward', NULL, 13, 'Active', 1, '2023-10-26 14:53:36', '2026-04-12 10:14:05'),
(13, 'Ward A - General', NULL, 27, 'Active', 0, '2025-01-22 05:31:55', '2026-04-12 10:22:48'),
(14, 'Ward B - Surgical', NULL, 21, 'Active', 5, '2024-07-01 22:17:36', '2026-04-12 10:22:48'),
(15, 'ICU', NULL, 18, 'Active', 2, '2023-07-28 03:15:01', '2026-04-12 10:22:48'),
(16, 'Maternity Ward', NULL, 36, 'Active', 3, '2024-01-03 21:11:05', '2026-04-12 10:22:48'),
(17, 'Pediatric Ward', NULL, 14, 'Active', 1, '2024-05-09 02:14:05', '2026-04-12 10:22:48'),
(18, 'Ward A - General', NULL, 28, 'Active', 0, '2024-05-04 03:21:44', '2026-04-12 10:26:48'),
(19, 'Ward B - Surgical', NULL, 28, 'Active', 5, '2023-05-25 10:29:53', '2026-04-12 10:26:48'),
(20, 'ICU', NULL, 23, 'Active', 2, '2024-07-21 13:38:40', '2026-04-12 10:26:48'),
(21, 'Maternity Ward', NULL, 28, 'Active', 2, '2024-10-23 09:24:17', '2026-04-12 10:26:48'),
(22, 'Pediatric Ward', NULL, 24, 'Active', 1, '2023-10-19 14:55:44', '2026-04-12 10:26:48');

-- --------------------------------------------------------

--
-- Table structure for table `wound_care_records`
--

DROP TABLE IF EXISTS `wound_care_records`;
CREATE TABLE IF NOT EXISTS `wound_care_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL COMMENT 'FK → patients.id',
  `nurse_id` int NOT NULL COMMENT 'FK → nurses.id',
  `wound_location` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wound_description` text COLLATE utf8mb4_unicode_ci,
  `wound_images` json DEFAULT NULL COMMENT 'Array of image file paths',
  `care_provided` text COLLATE utf8mb4_unicode_ci,
  `dressing_type` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wound_size` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. 3cm x 2cm',
  `wound_stage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Stage I-IV for pressure ulcers',
  `next_care_due` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`),
  KEY `idx_wcr_record_id` (`record_id`),
  KEY `idx_wcr_patient` (`patient_id`),
  KEY `idx_wcr_nurse` (`nurse_id`),
  KEY `idx_wcr_due` (`next_care_due`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Wound assessment and care records with image support';

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
-- Constraints for table `bed_transfers`
--
ALTER TABLE `bed_transfers`
  ADD CONSTRAINT `bed_transfers_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bed_transfers_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bed_transfers_ibfk_3` FOREIGN KEY (`from_bed_id`) REFERENCES `beds` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bed_transfers_ibfk_4` FOREIGN KEY (`to_bed_id`) REFERENCES `beds` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bed_transfers_ibfk_5` FOREIGN KEY (`authorized_by`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `billing_invoices`
--
ALTER TABLE `billing_invoices`
  ADD CONSTRAINT `fk_invoice_generated` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invoice_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `broadcast_recipients`
--
ALTER TABLE `broadcast_recipients`
  ADD CONSTRAINT `broadcast_recipients_ibfk_1` FOREIGN KEY (`broadcast_id`) REFERENCES `broadcasts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD CONSTRAINT `fk_budget_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_budget_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`conversation_id`) ON DELETE CASCADE;

--
-- Constraints for table `cleaning_schedules`
--
ALTER TABLE `cleaning_schedules`
  ADD CONSTRAINT `fk_clean_assigned_cleaner` FOREIGN KEY (`assigned_cleaner_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clean_backup_cleaner` FOREIGN KEY (`backup_cleaner_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_cash_reports`
--
ALTER TABLE `daily_cash_reports`
  ADD CONSTRAINT `fk_daily_generated` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_daily_reconciled` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `discharge_instructions`
--
ALTER TABLE `discharge_instructions`
  ADD CONSTRAINT `discharge_instructions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discharge_instructions_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `emergency_alerts_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emergency_alerts_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emergency_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `fk_ec_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_schedule`
--
ALTER TABLE `fee_schedule`
  ADD CONSTRAINT `fk_fee_category` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fee_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fee_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `finance_audit_trail`
--
ALTER TABLE `finance_audit_trail`
  ADD CONSTRAINT `fk_finaudit_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `finance_notifications`
--
ALTER TABLE `finance_notifications`
  ADD CONSTRAINT `fk_finnotif_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_finnotif_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `finance_settings`
--
ALTER TABLE `finance_settings`
  ADD CONSTRAINT `fk_finsettings_staff` FOREIGN KEY (`finance_staff_id`) REFERENCES `finance_staff` (`finance_staff_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `finance_staff`
--
ALTER TABLE `finance_staff`
  ADD CONSTRAINT `fk_finance_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD CONSTRAINT `fk_finreport_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fluid_balance`
--
ALTER TABLE `fluid_balance`
  ADD CONSTRAINT `fluid_balance_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fluid_balance_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD CONSTRAINT `fk_claim_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`invoice_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_claim_officer` FOREIGN KEY (`claims_officer`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_claim_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `invoice_line_items`
--
ALTER TABLE `invoice_line_items`
  ADD CONSTRAINT `fk_lineitem_cat` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lineitem_fee` FOREIGN KEY (`fee_id`) REFERENCES `fee_schedule` (`fee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lineitem_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`invoice_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `isolation_records`
--
ALTER TABLE `isolation_records`
  ADD CONSTRAINT `isolation_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `isolation_records_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `isolation_records_ibfk_3` FOREIGN KEY (`doctor_ordered_by`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `iv_fluid_records`
--
ALTER TABLE `iv_fluid_records`
  ADD CONSTRAINT `iv_fluid_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `iv_fluid_records_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kitchen_dietary_flags`
--
ALTER TABLE `kitchen_dietary_flags`
  ADD CONSTRAINT `fk_kitchen_flags_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_lab_tech_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_technicians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_technician_sessions`
--
ALTER TABLE `lab_technician_sessions`
  ADD CONSTRAINT `lab_technician_sessions_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `lab_technicians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `fk_labtest_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `legacy_payments`
--
ALTER TABLE `legacy_payments`
  ADD CONSTRAINT `legacy_payments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `medication_administration_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_administration_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_administration_ibfk_3` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medication_schedules`
--
ALTER TABLE `medication_schedules`
  ADD CONSTRAINT `medication_schedules_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_schedules_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_schedules_ibfk_3` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_activity_log`
--
ALTER TABLE `nurse_activity_log`
  ADD CONSTRAINT `nurse_activity_log_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_certifications`
--
ALTER TABLE `nurse_certifications`
  ADD CONSTRAINT `nurse_certifications_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_doctor_messages`
--
ALTER TABLE `nurse_doctor_messages`
  ADD CONSTRAINT `nurse_doctor_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_doctor_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_doctor_messages_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nurse_documents`
--
ALTER TABLE `nurse_documents`
  ADD CONSTRAINT `nurse_documents_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_notifications`
--
ALTER TABLE `nurse_notifications`
  ADD CONSTRAINT `nurse_notifications_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nurse_profile_completeness`
--
ALTER TABLE `nurse_profile_completeness`
  ADD CONSTRAINT `nurse_profile_completeness_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_qualifications`
--
ALTER TABLE `nurse_qualifications`
  ADD CONSTRAINT `nurse_qualifications_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_sessions`
--
ALTER TABLE `nurse_sessions`
  ADD CONSTRAINT `nurse_sessions_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_sessions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_settings`
--
ALTER TABLE `nurse_settings`
  ADD CONSTRAINT `nurse_settings_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD CONSTRAINT `nurse_shifts_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurse_tasks`
--
ALTER TABLE `nurse_tasks`
  ADD CONSTRAINT `nurse_tasks_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_tasks_ibfk_2` FOREIGN KEY (`assigned_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_tasks_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nursing_notes`
--
ALTER TABLE `nursing_notes`
  ADD CONSTRAINT `nursing_notes_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_notes_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_notes_ibfk_3` FOREIGN KEY (`shift_id`) REFERENCES `nurse_shifts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_assigned_doctor` FOREIGN KEY (`assigned_doctor`) REFERENCES `users` (`id`) ON DELETE SET NULL,
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
  ADD CONSTRAINT `patient_education_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_education_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `patient_vitals_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_vitals_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`invoice_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payment_waivers`
--
ALTER TABLE `payment_waivers`
  ADD CONSTRAINT `fk_waiver_approved` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_waiver_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_waiver_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`invoice_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_waiver_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `paystack_transactions`
--
ALTER TABLE `paystack_transactions`
  ADD CONSTRAINT `fk_paystack_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_refund_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refund_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`invoice_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refund_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refund_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD CONSTRAINT `fk_template_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `revenue_categories`
--
ALTER TABLE `revenue_categories`
  ADD CONSTRAINT `fk_revenue_parent` FOREIGN KEY (`parent_category_id`) REFERENCES `revenue_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD CONSTRAINT `fk_schedule_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD CONSTRAINT `fk_security_incidents_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shift_handover`
--
ALTER TABLE `shift_handover`
  ADD CONSTRAINT `shift_handover_ibfk_1` FOREIGN KEY (`outgoing_nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shift_handover_ibfk_2` FOREIGN KEY (`incoming_nurse_id`) REFERENCES `nurses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shift_handover_ibfk_3` FOREIGN KEY (`shift_id`) REFERENCES `nurse_shifts` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `staff_leaves`
--
ALTER TABLE `staff_leaves`
  ADD CONSTRAINT `fk_staff_leaves_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notification_prefs`
--
ALTER TABLE `user_notification_prefs`
  ADD CONSTRAINT `fk_notif_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD CONSTRAINT `fk_visitor_logs_staff` FOREIGN KEY (`logged_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `vital_thresholds`
--
ALTER TABLE `vital_thresholds`
  ADD CONSTRAINT `vital_thresholds_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wound_care_records`
--
ALTER TABLE `wound_care_records`
  ADD CONSTRAINT `wound_care_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wound_care_records_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
