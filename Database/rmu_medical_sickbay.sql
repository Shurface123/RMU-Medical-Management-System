-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 08, 2026 at 01:35 AM
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session` (`session_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `active_sessions`
--

INSERT INTO `active_sessions` (`id`, `session_id`, `user_id`, `user_role`, `ip_address`, `device_info`, `browser`, `user_agent`, `last_active`, `is_current`, `remember_me`, `expires_at`, `logged_in_at`) VALUES
(9, '3pavgqcma9onk2hs17gfusefps', 29, 'lab_technician', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:13', 1, 0, NULL, '2026-03-31 13:48:13'),
(10, 'pa9htmo7jukhedhcu90oedsq2e', 23, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:48:58', 1, 0, NULL, '2026-03-31 13:48:58'),
(11, '09u4n6ach799vbrukgrkl08rd5', 17, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 13:50:57', 1, 0, NULL, '2026-03-31 13:50:57'),
(15, '8eavkjpu49cjruaqg4htfndard', 21, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:21:09', 1, 0, NULL, '2026-03-31 14:21:09'),
(16, 't3t5uh1c5okocr4hpq6icspa9v', 18, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:23:10', 1, 0, NULL, '2026-03-31 14:23:10'),
(17, 'q4neqlr9jp24vkkamq85vhfhlf', 28, 'lab_technician', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 14:24:08', 1, 0, NULL, '2026-03-31 14:24:08'),
(19, '6b2vdu4jcatifhv0bfusaii2gf', 14, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 08:58:48', 1, 0, NULL, '2026-04-04 08:58:48'),
(20, 'qjovm2404or43s8t60n6qo4ds5', 16, 'staff', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 09:03:26', 1, 0, NULL, '2026-04-04 09:03:26'),
(24, 'og2iigmbc7ahg67tdo3ucd38m0', 1, 'admin', '::1', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:30:12', 1, 0, NULL, '2026-04-08 01:28:57');

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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(47, 1, 'LOGIN_SUCCESS', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:28:57');

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
  `attending_nurse_id` int DEFAULT NULL COMMENT 'nurses.id - nurse assigned to this patient',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all outbound email delivery attempts';

--
-- Dumping data for table `email_queue_log`
--

INSERT INTO `email_queue_log` (`id`, `to_email`, `email_type`, `status`, `error_message`, `sent_at`) VALUES
(1, 'atakorahe57@gmail.com', 'password_reset', 'failed', 'SMTP Error: Could not authenticate.', '2026-04-04 09:37:26');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emergency alerts triggered by nurses (code blue, falls, etc.)';

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(76, 'Goodbye! See you next time.', 'safety', NULL, 1, NULL, '2026-03-26 12:57:30', '2026-03-26 12:57:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_audit_trail`
--

INSERT INTO `lab_audit_trail` (`id`, `technician_id`, `user_id`, `action_type`, `module_affected`, `record_id`, `old_value`, `new_value`, `ip_address`, `device_info`, `created_at`) VALUES
(1, 1, NULL, 'login_success', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-18 11:47:55'),
(2, 1, NULL, 'login_success', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-19 06:20:51');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_technician_sessions`
--

INSERT INTO `lab_technician_sessions` (`id`, `technician_id`, `session_token`, `device_info`, `browser`, `ip_address`, `login_time`, `last_active`, `is_current`) VALUES
(1, 2, 'qt2ktorkic1378bvm2petdka87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-25 05:40:47', '2026-03-31 13:47:16', 0),
(4, 2, '4jsg8h7iie4ssq02jdguj8m95l', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-31 13:47:16', '2026-03-31 14:24:08', 0),
(6, 2, 'brn76kqeasvgcuj9opv98uvkqq', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'Safari', '::1', '2026-03-31 14:24:08', '2026-03-31 14:32:20', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_technician_settings`
--

INSERT INTO `lab_technician_settings` (`id`, `technician_id`, `notification_preferences`, `theme_preference`, `language`, `alert_sound_enabled`, `notif_new_order`, `notif_critical_result`, `notif_equipment_alert`, `notif_reagent_alert`, `notif_qc_reminder`, `notif_doctor_msg`, `notif_system`, `updated_at`, `notif_stat_order`, `notif_reagent_expiry`, `notif_result_amend`, `notif_license_expiry`, `notif_shift_reminder`, `preferred_channel`) VALUES
(1, 1, NULL, 'light', 'en', 1, 1, 1, 1, 1, 1, 1, 1, '2026-03-18 02:51:46', 1, 1, 1, 1, 1, 'In-Dashboard');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(30, 'Lovelace', '::1', 0, 'login_success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-08 01:28:57', 1);

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logout_config`
--

INSERT INTO `logout_config` (`id`, `countdown_duration`, `confirm_dialog_enabled`, `show_health_message`, `redirect_url`, `session_cleanup`, `force_logout_on_password_change`, `updated_by`, `updated_at`) VALUES
(1, 3, 1, 1, '/RMU-Medical-Management-System/php/index.php', 1, 1, NULL, '2026-03-26 11:49:26');

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
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logout_logs`
--

INSERT INTO `logout_logs` (`id`, `user_id`, `role`, `session_id`, `logout_type`, `logout_confirmed_at`, `countdown_duration`, `ip_address`, `device_info`, `browser`, `dashboard_origin`, `health_message_shown`, `created_at`) VALUES
(1, 1, 'admin', 'nbtcd71liagcv37gs026k4h4i1', 'manual', '2026-03-26 12:24:25', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '_sidebar.php', 'Wash your hands regularly to prevent the spread of infections.', '2026-03-26 12:24:25'),
(2, 1, 'admin', 'us55vjbhk40rp51qoqpio1lrm8', 'manual', '2026-03-26 12:30:37', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '_sidebar.php', 'Wash your hands regularly to prevent the spread of infections.', '2026-03-26 12:30:37'),
(3, 1, 'admin', '2bm5ure069agma7208k0omoakn', 'timeout', '2026-03-26 16:31:42', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-03-26 16:31:42'),
(4, 1, 'admin', 's5c8s7dtv11rvlqt9jgtls6143', 'timeout', '2026-03-27 12:25:57', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-03-27 12:25:57'),
(5, 15, 'staff', 'uco91iohbmahft6oh5lma27p1p', 'timeout', '2026-04-08 01:23:52', 0, '::1', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'system_interceptor', NULL, '2026-04-08 01:23:52');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_id`, `medicine_name`, `generic_name`, `category`, `manufacturer`, `supplier_name`, `description`, `storage_instructions`, `side_effects`, `contraindications`, `drug_interactions`, `unit_price`, `stock_quantity`, `unit`, `reorder_level`, `expiry_date`, `batch_number`, `is_prescription_required`, `is_controlled`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MED001', 'Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, NULL, 0.50, 500, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(2, 'MED002', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Pharma Ltd', NULL, NULL, NULL, NULL, NULL, NULL, 0.75, 300, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(3, 'MED003', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'MedCare', NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 200, 'tablet', 30, NULL, NULL, 1, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(4, 'MED004', 'Vitamin C 1000mg', 'Ascorbic Acid', 'Vitamin', 'HealthPlus', NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 400, 'tablet', 50, NULL, NULL, 0, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21'),
(5, 'MED005', 'Omeprazole 20mg', 'Omeprazole', 'Antacid', 'MedCare', NULL, NULL, NULL, NULL, NULL, NULL, 1.50, 150, 'tablet', 30, NULL, NULL, 1, 0, 'active', '2026-02-06 05:09:21', '2026-02-06 05:09:21');

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
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nurse profiles linked to shared users table';

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`id`, `nurse_id`, `user_id`, `full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `email`, `address`, `profile_photo`, `license_number`, `license_expiry`, `specialization`, `department_id`, `designation`, `years_of_experience`, `shift_type`, `status`, `created_at`, `updated_at`, `approval_status`, `rejection_reason`, `approved_by`, `approved_at`) VALUES
(1, '', 26, 'Nelly Nartey', NULL, 'Female', NULL, '0272814681', 'nartey.nelly@st.rmu.edu.gh', NULL, 'default-avatar.png', NULL, NULL, NULL, NULL, NULL, 0, 'Morning', 'Active', '2026-03-20 03:10:01', '2026-03-20 03:16:00', 'approved', NULL, 1, '2026-03-20 03:16:00');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task assignments from doctors/admins to nurses';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nursing clinical notes (auto-locked after shift ends)';

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token_hash`, `is_used`, `expires_at`, `ip_address`, `created_at`) VALUES
(1, 26, '5375c7f03b9d64b407d539635c75b3c059ce7d93bea0350ba55a33533c509afb', 0, '2026-03-25 19:56:08', '::1', '2026-03-25 19:26:08'),
(2, 15, '24bc0920c75a7d4780ae4fcef29748270a27b9d4fe611f889c9e5af33f9b30f2', 0, '2026-04-04 10:07:17', '::1', '2026-04-04 09:37:17');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient vital signs recorded by nurses with auto-flagging';

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
-- Table structure for table `permission_matrix`
--

DROP TABLE IF EXISTS `permission_matrix`;
CREATE TABLE IF NOT EXISTS `permission_matrix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','doctor','patient','staff','pharmacist','nurse','lab_technician') COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pharm_user` (`user_id`),
  KEY `idx_pharm_license` (`license_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacist_profile`
--

INSERT INTO `pharmacist_profile` (`id`, `user_id`, `full_name`, `license_number`, `license_expiry`, `specialization`, `department`, `phone`, `secondary_phone`, `email`, `address`, `city`, `region`, `country`, `profile_photo`, `bio`, `years_of_experience`, `nationality`, `national_id`, `date_of_birth`, `gender`, `marital_status`, `availability_status`, `profile_completion`, `created_at`, `updated_at`, `postal_code`, `office_location`, `pharmacy_school`, `graduation_year`, `postgrad_training`, `license_issuing_body`, `personal_email`, `street_address`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(1, 6, 'Nelly Nartey', NULL, NULL, NULL, 'Pharmacy', '0501234567', NULL, 'nelly.nartey@st.rmu.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(2, 7, 'Adjei Adelaide Naa Adjeley', NULL, NULL, NULL, 'Pharmacy', '0507333138', NULL, 'es-anadjei@st.umat.edu.gh', NULL, NULL, NULL, 'Ghana', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'Offline', 0, '2026-03-02 11:25:17', '2026-03-02 11:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(9, 11, 1, 'approved', NULL, '2026-03-18 05:03:03');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_audit_trail`
--

INSERT INTO `staff_audit_trail` (`id`, `user_id`, `action_type`, `module`, `description`, `ip_address`, `created_at`) VALUES
(1, 11, 'login_blocked', 'security', 'Account pending admin approval', NULL, '2026-03-16 11:59:35'),
(2, 12, 'login_blocked', 'security', 'Account pending admin approval', NULL, '2026-03-17 12:01:56');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `leave_type` enum('annual','sick','emergency','unpaid','maternity','paternity','study','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'annual',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
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
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('task','alert','shift','emergency','system','message','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `related_module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('scheduled','active','completed','missed','swapped') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shift_id`),
  KEY `idx_staff_shift` (`staff_id`,`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('pending','in progress','completed','overdue','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completion_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'smtp.gmail.com', 587, 'sickbay.text@st.rmu.edu.gh', '', 'tls', 'sickbay.text@st.rmu.edu.gh', 'RMU Medical Sickbay', 1, NULL, '2026-03-25 05:53:00');

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
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse','lab_technician') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `email`, `password`, `two_factor_secret`, `two_factor_enabled`, `profile_photo`, `emergency_contact_name`, `emergency_contact_phone`, `user_role`, `patient_type`, `name`, `phone`, `gender`, `date_of_birth`, `profile_image`, `account_status`, `is_active`, `is_verified`, `status`, `created_at`, `updated_at`, `last_login`, `last_active_at`, `last_login_at`, `locked_until`, `force_password_change`, `two_fa_enabled`, `last_login_ip`, `accepted_terms`) VALUES
(1, 'Lovelace', 'admin@rmu.edu.gh', '$2y$10$oPwC5CopfH8UPh6SFrpvi.hzRUTfmNpcc3ZI2Lmy9SUlTmtUJfdDK', NULL, 0, 'default-avatar.png', NULL, NULL, 'admin', NULL, 'System Administrator', '0502371207', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-02-06 05:09:21', '2026-04-08 01:29:05', '2026-03-26 16:42:55', NULL, '2026-04-08 01:28:57', NULL, 0, 0, '::1', 1),
(20, 'JE', 'eli.joyce@st.rmu.edu.gh', '$2y$10$tfaPj3KiYWTLW8smrAkUROmB5qGWGixabIKW7vM5YgVdL8PseBg7O', NULL, 0, 'default-avatar.png', NULL, NULL, 'doctor', NULL, 'Joyce Eli', '0241439494', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'pending', '2026-03-18 01:42:31', '2026-04-05 05:43:53', '2026-03-18 02:05:29', NULL, NULL, NULL, 0, 0, '', 0),
(26, 'Neils', 'nartey.nelly@st.rmu.edu.gh', '$2y$10$ORu/5fqFiwSsZNHvt/dOx.PvbQobc4QesdexhFo9ek6wdRrBdu21q', NULL, 0, 'default-avatar.png', NULL, NULL, 'nurse', NULL, 'Nelly Nartey', '0272814681', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-03-20 03:10:01', '2026-04-05 05:43:53', '2026-03-25 17:32:36', NULL, '2026-04-04 09:36:28', NULL, 0, 0, '::1', 0),
(28, 'FJ', 'jefferson.forson@st.rmu.edu.gh', '$2y$10$jcr7h1mIQH3KG6fH1ggoYe1ra.5KGmzCrv1FESAFtVcYnAll2vYhi', NULL, 0, 'default-avatar.png', NULL, NULL, 'lab_technician', NULL, 'Jefferson Forson', '0500168225', NULL, NULL, 'default-avatar.png', 'active', 1, 1, 'active', '2026-03-23 07:31:52', '2026-04-05 05:43:53', '2026-03-25 05:40:47', NULL, '2026-03-31 14:24:08', NULL, 0, 0, '::1', 0);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_registration_audit`
--

INSERT INTO `user_registration_audit` (`id`, `audit_id`, `user_id`, `action`, `performed_by`, `ip_address`, `device_info`, `notes`, `created_at`) VALUES
(2, 'URA-69cbdc9c6de1f', 20, 'approved', '1', '::1', NULL, 'Approved via Admin Dashboard', '2026-03-31 14:39:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `user_role` enum('admin','doctor','patient','staff','pharmacist','nurse') COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(7, 'Isolation', NULL, 10, 'Active', 0, '2026-03-16 22:30:55', '2026-03-23 07:57:38');

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
-- Constraints for table `broadcast_recipients`
--
ALTER TABLE `broadcast_recipients`
  ADD CONSTRAINT `broadcast_recipients_ibfk_1` FOREIGN KEY (`broadcast_id`) REFERENCES `broadcasts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cleaning_schedules`
--
ALTER TABLE `cleaning_schedules`
  ADD CONSTRAINT `fk_clean_assigned_cleaner` FOREIGN KEY (`assigned_cleaner_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clean_backup_cleaner` FOREIGN KEY (`backup_cleaner_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `fluid_balance`
--
ALTER TABLE `fluid_balance`
  ADD CONSTRAINT `fluid_balance_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fluid_balance_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD CONSTRAINT `fk_template_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
