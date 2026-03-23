-- =============================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- FIX: UNIFY COLLATION ACROSS SYSTEM TABLES
-- =============================================

USE `rmu_medical_sickbay`;

-- Convert lab_technicians table to match the rest of the system
ALTER TABLE `lab_technicians` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Also ensure other tables are explicitly on the same collation if they aren't already
ALTER TABLE `staff` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `nurses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ambulances` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ambulance_trips` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `test_services` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `patient_tests` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
