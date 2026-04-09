-- Migration to create missing fee_categories table
-- Phase 2 Finance Fix

CREATE TABLE IF NOT EXISTS `fee_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories
INSERT IGNORE INTO `fee_categories` (`category_id`, `category_name`, `display_order`) VALUES
(1, 'General Consultations', 1),
(2, 'Specialist Consultations', 2),
(3, 'Pharmacy & Medications', 3),
(4, 'Laboratory Investigations', 4),
(5, 'Radiology & Imaging', 5),
(6, 'Nursing & Ward Procedures', 6),
(7, 'Emergency & First Aid', 7),
(8, 'Health Records & Admin', 8),
(9, 'Consumables & Supplies', 9);

-- Update fee_schedule to ensure some logic matches if needed
-- (Assuming category_id will be mapped manually or default to null)
