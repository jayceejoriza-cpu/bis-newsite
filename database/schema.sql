-- ============================================
-- Barangay Management System - Database Schema
-- ============================================
-- Version: 1.0.0
-- Created: 2024
-- Description: Complete database structure for resident management
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `bmis` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `bmis`;

-- ============================================
-- Table: residents
-- Description: Main table storing all resident information
-- ============================================
CREATE TABLE IF NOT EXISTS `residents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `resident_id` VARCHAR(20) UNIQUE DEFAULT NULL COMMENT 'Auto-generated resident ID (Format: W-XXXXX)',
  
  -- Personal Details
  `photo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to resident photo',
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) DEFAULT NULL COMMENT 'Jr., Sr., III, etc.',
  `sex` ENUM('Male', 'Female', 'Other') NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `age` INT(3) DEFAULT NULL COMMENT 'Calculated from date_of_birth',
  `place_of_birth` VARCHAR(255) DEFAULT NULL,
  `religion` VARCHAR(100) DEFAULT NULL,
  `ethnicity` ENUM('IPS', 'Non-IPS', '') DEFAULT NULL COMMENT 'Indigenous People Status',
  
  -- Contact Information
  `mobile_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `current_address` TEXT NOT NULL,
  `household_no` VARCHAR(50) DEFAULT NULL,
  `household_contact` VARCHAR(20) DEFAULT NULL,
  `purok` VARCHAR(50) DEFAULT NULL,
  
  -- Family Information
  `civil_status` ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced') NOT NULL,
  `spouse_name` VARCHAR(255) DEFAULT NULL,
  `father_name` VARCHAR(255) DEFAULT NULL,
  `mother_name` VARCHAR(255) DEFAULT NULL,
  `number_of_children` INT(2) DEFAULT 0,
  `household_head` VARCHAR(255) DEFAULT NULL,
  
  -- Education & Employment
  `educational_attainment` VARCHAR(100) DEFAULT NULL,
  `employment_status` VARCHAR(50) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `monthly_income` VARCHAR(50) DEFAULT NULL,
  
  -- Government Programs
  `fourps_member` ENUM('Yes', 'No') DEFAULT 'No' COMMENT '4Ps Beneficiary',
  `fourps_id` VARCHAR(50) DEFAULT NULL COMMENT '4Ps ID Number',
  `voter_status` ENUM('Yes', 'No', '') DEFAULT NULL,
  `precinct_number` VARCHAR(50) DEFAULT NULL,
  `pwd_status` ENUM('Yes', 'No') DEFAULT 'No' COMMENT 'Person with Disability',
  `senior_citizen` ENUM('Yes', 'No') DEFAULT 'No',
  `indigent` ENUM('Yes', 'No') DEFAULT 'No',
  
  -- Health Information
  `philhealth_id` VARCHAR(50) DEFAULT NULL,
  `membership_type` ENUM('Member', 'Dependent', 'None', '') DEFAULT NULL,
  `philhealth_category` VARCHAR(100) DEFAULT NULL,
  `age_health_group` VARCHAR(100) DEFAULT NULL,
  `medical_history` TEXT DEFAULT NULL,
  
  -- Women's Reproductive Health (WRA) - For females only
  `lmp_date` DATE DEFAULT NULL COMMENT 'Last Menstrual Period',
  `using_fp_method` ENUM('Yes', 'No', '') DEFAULT NULL COMMENT 'Family Planning Method',
  `fp_methods_used` VARCHAR(100) DEFAULT NULL COMMENT 'Family Planning Methods',
  `fp_status` VARCHAR(50) DEFAULT NULL COMMENT 'Family Planning Status',
  
  -- Living Conditions
  `water_source_type` VARCHAR(100) DEFAULT NULL,
  `toilet_facility_type` VARCHAR(100) DEFAULT NULL,
  
  -- Additional Information
  `remarks` TEXT DEFAULT NULL,
  
  -- Verification Status
  `verification_status` ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending' COMMENT 'Resident verification status',
  `verified_by` INT(11) DEFAULT NULL COMMENT 'User ID who verified',
  `verified_at` DATETIME DEFAULT NULL COMMENT 'Verification timestamp',
  `rejection_reason` TEXT DEFAULT NULL COMMENT 'Reason if rejected',
  
  -- Activity Status
  `activity_status` ENUM('Active', 'Inactive', 'Deceased') DEFAULT 'Active' COMMENT 'Resident activity status',
  `status_changed_at` DATETIME DEFAULT NULL COMMENT 'When status was last changed',
  `status_changed_by` INT(11) DEFAULT NULL COMMENT 'User ID who changed status',
  `status_remarks` TEXT DEFAULT NULL COMMENT 'Remarks about status change',
  
  -- Record Metadata
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
  
  PRIMARY KEY (`id`),
  INDEX `idx_last_name` (`last_name`),
  INDEX `idx_first_name` (`first_name`),
  INDEX `idx_verification_status` (`verification_status`),
  INDEX `idx_household_no` (`household_no`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main residents information table';

-- ============================================
-- Table: households
-- Description: Household information
-- ============================================
CREATE TABLE IF NOT EXISTS `households` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `household_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique household identifier',
  `household_head_id` INT(11) DEFAULT NULL COMMENT 'Foreign key to residents table',
  `household_contact` VARCHAR(20) DEFAULT NULL,
  `address` TEXT NOT NULL,
  `water_source_type` VARCHAR(100) DEFAULT NULL,
  `toilet_facility_type` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  INDEX `idx_household_number` (`household_number`),
  INDEX `idx_household_head_id` (`household_head_id`),
  CONSTRAINT `fk_household_head` 
    FOREIGN KEY (`household_head_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household information';

-- ============================================
-- Table: household_members
-- Description: Members of each household
-- ============================================
CREATE TABLE IF NOT EXISTS `household_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `household_id` INT(11) NOT NULL COMMENT 'Foreign key to households table',
  `resident_id` INT(11) NOT NULL COMMENT 'Foreign key to residents table',
  `relationship_to_head` VARCHAR(100) DEFAULT NULL COMMENT 'Relationship to household head',
  `is_head` TINYINT(1) DEFAULT 0 COMMENT '1 if this member is the household head',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_household_resident` (`household_id`, `resident_id`),
  INDEX `idx_household_id` (`household_id`),
  INDEX `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_hm_household` 
    FOREIGN KEY (`household_id`) 
    REFERENCES `households` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household members';

-- ============================================
-- Table: emergency_contacts
-- Description: Emergency contact information for residents
-- ============================================
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `resident_id` INT(11) NOT NULL COMMENT 'Foreign key to residents table',
  `contact_name` VARCHAR(255) NOT NULL,
  `relationship` VARCHAR(100) NOT NULL,
  `contact_number` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `priority` INT(2) DEFAULT 1 COMMENT 'Contact priority order (1=primary, 2=secondary, etc.)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  INDEX `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_emergency_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emergency contacts for residents';

-- ============================================
-- Table: users (Optional - for user management)
-- Description: System users who can manage residents
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('Admin', 'Staff', 'Viewer') DEFAULT 'Staff',
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System users';

-- ============================================
-- Table: audit_logs (Optional - for tracking changes)
-- Description: Audit trail for resident record changes
-- ============================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `resident_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, VERIFY, REJECT',
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT(11) NOT NULL,
  `old_values` TEXT DEFAULT NULL COMMENT 'JSON of old values',
  `new_values` TEXT DEFAULT NULL COMMENT 'JSON of new values',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  INDEX `idx_resident_id` (`resident_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for changes';

-- ============================================
-- Insert default admin user (Optional)
-- Password: admin123 (Please change after first login)
-- ============================================
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@barangay.local', 'Admin', 'Active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- ============================================
-- Views for easier data retrieval
-- ============================================

-- View: Complete resident information with emergency contacts
CREATE OR REPLACE VIEW `vw_residents_complete` AS
SELECT 
  r.*,
  CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
  TIMESTAMPDIFF(YEAR, r.date_of_birth, CURDATE()) AS calculated_age,
  GROUP_CONCAT(
    CONCAT(ec.contact_name, ' (', ec.relationship, '): ', ec.contact_number) 
    SEPARATOR '; '
  ) AS emergency_contacts_list
FROM residents r
LEFT JOIN emergency_contacts ec ON r.id = ec.resident_id
GROUP BY r.id;

-- View: Pending verification residents
CREATE OR REPLACE VIEW `vw_pending_residents` AS
SELECT 
  r.id,
  CONCAT(r.first_name, ' ', r.last_name) AS full_name,
  r.mobile_number,
  r.current_address,
  r.verification_status,
  r.created_at
FROM residents r
WHERE r.verification_status = 'Pending'
ORDER BY r.created_at DESC;

-- View: Statistics summary
CREATE OR REPLACE VIEW `vw_resident_statistics` AS
SELECT 
  COUNT(*) AS total_residents,
  SUM(CASE WHEN verification_status = 'Verified' THEN 1 ELSE 0 END) AS verified_residents,
  SUM(CASE WHEN verification_status = 'Pending' THEN 1 ELSE 0 END) AS pending_residents,
  SUM(CASE WHEN verification_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_residents,
  SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) AS male_count,
  SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) AS female_count,
  SUM(CASE WHEN fourps_member = 'Yes' THEN 1 ELSE 0 END) AS fourps_members,
  SUM(CASE WHEN senior_citizen = 'Yes' THEN 1 ELSE 0 END) AS senior_citizens,
  SUM(CASE WHEN pwd_status = 'Yes' THEN 1 ELSE 0 END) AS pwd_count
FROM residents;

-- ============================================
-- Table: certificates
-- Description: Certificate templates and management
-- ============================================
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Certificate title/name',
  `description` TEXT DEFAULT NULL COMMENT 'Certificate description',
  `fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Certificate fee amount',
  `status` ENUM('Published', 'Unpublished') DEFAULT 'Unpublished' COMMENT 'Publication status',
  `template_content` TEXT DEFAULT NULL COMMENT 'Certificate template HTML/content',
  `fields` TEXT DEFAULT NULL COMMENT 'JSON of customizable fields',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
  
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_title` (`title`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate templates';

-- ============================================
-- Table: certificate_requests
-- Description: Certificate requests from residents
-- ============================================
CREATE TABLE IF NOT EXISTS `certificate_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique reference number for the request',
  `resident_id` INT(11) NOT NULL COMMENT 'Foreign key to residents table',
  `certificate_id` INT(11) NOT NULL COMMENT 'Foreign key to certificates table',
  `payment_status` ENUM('Paid', 'Unpaid', 'Waived') DEFAULT 'Unpaid' COMMENT 'Payment status',
  `certificate_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Fee amount for this request',
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending' COMMENT 'Request status',
  `purpose` TEXT DEFAULT NULL COMMENT 'Purpose of the certificate request',
  `field_values` TEXT DEFAULT NULL COMMENT 'JSON of custom field values from the certificate template',
  `generated_certificate_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to generated certificate PDF',
  `date_requested` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date when request was made',
  `date_approved` DATETIME DEFAULT NULL COMMENT 'Date when request was approved',
  `date_completed` DATETIME DEFAULT NULL COMMENT 'Date when request was completed',
  `approved_by` INT(11) DEFAULT NULL COMMENT 'User ID who approved',
  `completed_by` INT(11) DEFAULT NULL COMMENT 'User ID who completed',
  `remarks` TEXT DEFAULT NULL COMMENT 'Additional remarks',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
  
  PRIMARY KEY (`id`),
  INDEX `idx_reference_no` (`reference_no`),
  INDEX `idx_resident_id` (`resident_id`),
  INDEX `idx_certificate_id` (`certificate_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_payment_status` (`payment_status`),
  INDEX `idx_date_requested` (`date_requested`),
  CONSTRAINT `fk_request_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_request_certificate` 
    FOREIGN KEY (`certificate_id`) 
    REFERENCES `certificates` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate requests from residents';

-- ============================================
-- End of Schema
-- ============================================
