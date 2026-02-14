-- ============================================
-- Migration: Add Missing Tables to Existing BMIS Database
-- ============================================
-- Description: Safely adds certificates, certificate_requests, 
--              households, and household_members tables
-- Date: February 13, 2026
-- ============================================

USE `bmis`;

-- ============================================
-- 1. Add certificates table
-- ============================================

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'Certificate title/name',
  `description` text DEFAULT NULL COMMENT 'Certificate description',
  `fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Certificate fee amount',
  `status` enum('Published','Unpublished') DEFAULT 'Unpublished' COMMENT 'Publication status',
  `template_content` text DEFAULT NULL COMMENT 'Certificate template HTML/content',
  `fields` text DEFAULT NULL COMMENT 'JSON of customizable fields',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_title` (`title`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate templates';

-- Insert sample certificates (only if table is empty)
INSERT INTO `certificates` (`title`, `description`, `fee`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Barangay Clearance' as title, 'Certificate of Barangay Clearance for various purposes' as description, 50.00 as fee, 'Published' as status, NOW() as created_at, NOW() as updated_at
  UNION ALL
  SELECT 'Certificate of Residency', 'Proof of residency in the barangay', 150.00, 'Published', NOW(), NOW()
  UNION ALL
  SELECT 'Certificate of Indigency', 'Certificate for indigent residents', 0.00, 'Published', NOW(), NOW()
  UNION ALL
  SELECT 'Business Permit', 'Barangay business permit certificate', 255.00, 'Unpublished', NOW(), NOW()
  UNION ALL
  SELECT 'Good Moral Certificate', 'Certificate of good moral character', 100.00, 'Published', NOW(), NOW()
  UNION ALL
  SELECT 'Certificate of Employment', 'Employment verification certificate', 200.00, 'Published', NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `certificates` LIMIT 1);

-- ============================================
-- 2. Add certificate_requests table
-- ============================================

CREATE TABLE IF NOT EXISTS `certificate_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(50) NOT NULL COMMENT 'Unique reference number for the request',
  `resident_id` int(11) NOT NULL COMMENT 'Foreign key to residents table',
  `certificate_id` int(11) NOT NULL COMMENT 'Foreign key to certificates table',
  `payment_status` enum('Paid','Unpaid','Waived') DEFAULT 'Unpaid' COMMENT 'Payment status',
  `certificate_fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Fee amount for this request',
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending' COMMENT 'Request status',
  `purpose` text DEFAULT NULL COMMENT 'Purpose of the certificate request',
  `field_values` text DEFAULT NULL COMMENT 'JSON of custom field values from the certificate template',
  `generated_certificate_path` varchar(255) DEFAULT NULL COMMENT 'Path to generated certificate PDF',
  `date_requested` datetime DEFAULT current_timestamp() COMMENT 'Date when request was made',
  `date_approved` datetime DEFAULT NULL COMMENT 'Date when request was approved',
  `date_completed` datetime DEFAULT NULL COMMENT 'Date when request was completed',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User ID who approved',
  `completed_by` int(11) DEFAULT NULL COMMENT 'User ID who completed',
  `remarks` text DEFAULT NULL COMMENT 'Additional remarks',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  KEY `idx_reference_no` (`reference_no`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_certificate_id` (`certificate_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_date_requested` (`date_requested`),
  CONSTRAINT `fk_request_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate requests from residents';

-- Insert sample certificate requests (only if table is empty and we have residents)
INSERT INTO `certificate_requests` (`reference_no`, `resident_id`, `certificate_id`, `payment_status`, `certificate_fee`, `status`, `purpose`, `date_requested`, `remarks`)
SELECT * FROM (
  SELECT 'CesgoQfYv1_kAzO7jk' as reference_no, 1 as resident_id, 1 as certificate_id, 'Waived' as payment_status, 0.00 as certificate_fee, 'Approved' as status, 'For employment purposes' as purpose, '2026-01-19 10:30:00' as date_requested, 'Senior citizen - fee waived' as remarks
  UNION ALL
  SELECT 'NmtBEaKSQJalcnIS', 2, 4, 'Unpaid', 255.00, 'Pending', 'For business permit application', '2025-11-03 14:15:00', NULL
  UNION ALL
  SELECT 'VuNkEswFmPmzQxYk', 3, 1, 'Unpaid', 50.00, 'Pending', 'For school requirements', '2025-11-03 09:45:00', NULL
  UNION ALL
  SELECT 'REF-2026-001', 1, 3, 'Paid', 100.00, 'Completed', 'For travel abroad', '2026-01-15 11:20:00', 'Completed and released'
  UNION ALL
  SELECT 'REF-2026-002', 2, 1, 'Waived', 0.00, 'Approved', 'For medical assistance', '2026-01-16 13:30:00', 'Indigent - fee waived'
  UNION ALL
  SELECT 'REF-2026-003', 3, 2, 'Unpaid', 150.00, 'Pending', 'For loan application', '2026-01-17 10:00:00', NULL
  UNION ALL
  SELECT 'REF-2026-004', 1, 1, 'Paid', 50.00, 'Approved', 'For employment', '2026-01-18 15:45:00', NULL
  UNION ALL
  SELECT 'REF-2026-005', 2, 3, 'Unpaid', 200.00, 'Pending', 'For visa application', '2026-01-19 09:15:00', NULL
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `certificate_requests` LIMIT 1)
  AND EXISTS (SELECT 1 FROM `residents` WHERE id IN (1, 2, 3));

-- ============================================
-- 3. Add households table
-- ============================================

CREATE TABLE IF NOT EXISTS `households` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `household_number` varchar(50) NOT NULL COMMENT 'Unique household identifier',
  `household_head_id` int(11) DEFAULT NULL COMMENT 'Foreign key to residents table',
  `household_contact` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `water_source_type` varchar(100) DEFAULT NULL,
  `toilet_facility_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `household_number` (`household_number`),
  KEY `idx_household_number` (`household_number`),
  KEY `idx_household_head_id` (`household_head_id`),
  CONSTRAINT `fk_household_head` FOREIGN KEY (`household_head_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household information';

-- ============================================
-- 4. Add household_members table
-- ============================================

CREATE TABLE IF NOT EXISTS `household_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `household_id` int(11) NOT NULL COMMENT 'Foreign key to households table',
  `resident_id` int(11) NOT NULL COMMENT 'Foreign key to residents table',
  `relationship_to_head` varchar(100) DEFAULT NULL COMMENT 'Relationship to household head',
  `is_head` tinyint(1) DEFAULT 0 COMMENT '1 if this member is the household head',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_household_resident` (`household_id`,`resident_id`),
  KEY `idx_household_id` (`household_id`),
  KEY `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household members';

-- ============================================
-- Verification Queries
-- ============================================

-- Check if all tables exist
SELECT 
  'Tables Created Successfully!' as Status,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'bmis' AND table_name = 'certificates') as certificates_exists,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'bmis' AND table_name = 'certificate_requests') as certificate_requests_exists,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'bmis' AND table_name = 'households') as households_exists,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'bmis' AND table_name = 'household_members') as household_members_exists;

-- Check sample data
SELECT 'Sample Data Summary' as Info;
SELECT COUNT(*) as total_certificates FROM certificates;
SELECT COUNT(*) as total_certificate_requests FROM certificate_requests;
SELECT COUNT(*) as total_households FROM households;
SELECT COUNT(*) as total_household_members FROM household_members;

-- ============================================
-- End of Migration
-- ============================================
