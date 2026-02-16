-- ============================================
-- Barangay Officials Table
-- ============================================

CREATE TABLE IF NOT EXISTS `barangay_officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) DEFAULT NULL COMMENT 'Foreign key to residents table',
  `position` varchar(100) NOT NULL COMMENT 'Official position (e.g., Barangay Captain, Kagawad)',
  `committee` varchar(255) DEFAULT NULL COMMENT 'Committee assignment',
  `hierarchy_level` int(2) DEFAULT 1 COMMENT 'Hierarchy level for org chart (1=top, 2=middle, 3=bottom)',
  `term_start` date NOT NULL COMMENT 'Start date of term',
  `term_end` date NOT NULL COMMENT 'End date of term',
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active' COMMENT 'Current status',
  `appointment_type` enum('Elected','Appointed') DEFAULT 'Elected',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Official photo path',
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_position` (`position`),
  KEY `idx_status` (`status`),
  KEY `idx_term_dates` (`term_start`, `term_end`),
  KEY `idx_hierarchy_level` (`hierarchy_level`),
  CONSTRAINT `fk_official_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay officials information';

-- ============================================
-- Sample Data for Barangay Officials
-- ============================================

INSERT INTO `barangay_officials` 
(`resident_id`, `position`, `committee`, `hierarchy_level`, `term_start`, `term_end`, `status`, `appointment_type`, `contact_number`, `email`) 
VALUES
-- Top Level (Barangay Captain)
(1, 'Barangay Captain', 'Executive', 1, '2023-01-01', '2025-12-31', 'Active', 'Elected', '+63 912 345 6789', 'captain@barangay.local'),

-- Middle Level (Kagawads)
(2, 'Kagawad', 'Health and Sanitation', 2, '2023-01-01', '2025-12-31', 'Active', 'Elected', '+63 923 456 7890', 'kagawad1@barangay.local'),
(3, 'Kagawad', 'Peace and Order', 2, '2023-01-01', '2025-12-31', 'Active', 'Elected', '+63 934 567 8901', 'kagawad2@barangay.local'),
(4, 'Kagawad', 'Infrastructure', 2, '2023-01-01', '2025-12-31', 'Active', 'Elected', '+63 945 678 9012', 'kagawad3@barangay.local'),
(5, 'Kagawad', 'Education', 2, '2023-01-01', '2025-12-31', 'Active', 'Elected', '+63 956 789 0123', 'kagawad4@barangay.local'),

-- Bottom Level (SK Chairman, Secretary, Treasurer)
(NULL, 'SK Chairman', 'Youth Development', 3, '2023-01-01', '2025-12-31', 'Active', 'Elected', NULL, NULL),
(NULL, 'Barangay Secretary', 'Administration', 3, '2023-01-01', '2025-12-31', 'Active', 'Appointed', NULL, NULL),
(NULL, 'Barangay Treasurer', 'Finance', 3, '2023-01-01', '2025-12-31', 'Active', 'Appointed', NULL, NULL);
