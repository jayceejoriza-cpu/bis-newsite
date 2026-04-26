-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table bmis.activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=460 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table bmis.archive
CREATE TABLE IF NOT EXISTS `archive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `archive_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type: resident, official, blotter, permit, user',
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original record identifier',
  `record_data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON encoded record data',
  `deleted_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Username who deleted the record',
  `deleted_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when deleted',
  PRIMARY KEY (`id`),
  KEY `idx_archive_type` (`archive_type`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archive table for deleted records';

-- Data exporting was unselected.

-- Dumping structure for table bmis.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resident_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CREATE, UPDATE, DELETE, VERIFY, REJECT',
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON of old values',
  `new_values` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON of new values',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for changes';

-- Data exporting was unselected.

-- Dumping structure for table bmis.barangay_info
CREATE TABLE IF NOT EXISTS `barangay_info` (
  `id` int NOT NULL,
  `province_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Province Name',
  `town_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Town/City Name',
  `barangay_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Barangay Name',
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dashboard_text` text COLLATE utf8mb4_unicode_ci,
  `municipal_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to municipal/city logo',
  `barangay_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to barangay logo',
  `sk_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_emblem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to official emblem',
  `dashboard_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to dashboard background image',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  KEY `fk_barangay_info_user` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay configuration and settings';

-- Data exporting was unselected.

-- Dumping structure for table bmis.barangay_officials
CREATE TABLE IF NOT EXISTS `barangay_officials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resident_id` int DEFAULT NULL COMMENT 'Foreign key to residents table',
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Full name of the official',
  `position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Official position (e.g., Barangay Captain, Kagawad)',
  `committee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Committee assignment',
  `hierarchy_level` int DEFAULT '1' COMMENT 'Hierarchy level for org chart (1=top, 2=middle, 3=bottom)',
  `term_start` date NOT NULL COMMENT 'Start date of term',
  `term_end` date NOT NULL COMMENT 'End date of term',
  `status` enum('Active','Inactive','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Active' COMMENT 'Current status',
  `appointment_type` enum('Elected','Appointed') COLLATE utf8mb4_unicode_ci DEFAULT 'Elected',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Official photo path',
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` int DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_position` (`position`),
  KEY `idx_status` (`status`),
  KEY `idx_term_dates` (`term_start`,`term_end`),
  KEY `idx_hierarchy_level` (`hierarchy_level`),
  CONSTRAINT `fk_official_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay officials information';

-- Data exporting was unselected.

-- Dumping structure for table bmis.blotter_complainants
CREATE TABLE IF NOT EXISTS `blotter_complainants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blotter_id` int NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` int DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Complainant full name',
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `statement` text COLLATE utf8mb4_unicode_ci COMMENT 'Complainant statement',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blotter_id` (`blotter_id`),
  KEY `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_complainant_blotter` FOREIGN KEY (`blotter_id`) REFERENCES `blotter_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_complainant_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter complainants';

-- Data exporting was unselected.

-- Dumping structure for table bmis.blotter_records
CREATE TABLE IF NOT EXISTS `blotter_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: BR-YYYY-XXXXXX',
  `incident_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of incident (e.g., Theft, Assault, Noise Complaint)',
  `incident_description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Detailed description of the incident',
  `incident_date` datetime NOT NULL COMMENT 'Date and time when incident occurred',
  `incident_location` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Location where incident occurred',
  `date_reported` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date when blotter was reported',
  `reported_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Person who reported (if not complainant)',
  `status` enum('Pending','Under Investigation','Resolved','Dismissed','Scheduled for Mediation','Settled','Endorsed to Police') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `mediation_schedule` datetime DEFAULT NULL,
  `status_updated_at` datetime DEFAULT NULL COMMENT 'When status was last updated',
  `status_updated_by` int DEFAULT NULL COMMENT 'User ID who updated status',
  `resolution` text COLLATE utf8mb4_unicode_ci COMMENT 'Resolution details if resolved',
  `case_outcome` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Settled|Scheduled for Mediation|Referred to Police/Court (CFA)|Dismissed',
  `incident_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL COMMENT 'Date when case was resolved',
  `resolved_by` int DEFAULT NULL COMMENT 'User ID who resolved the case',
  `remarks` text COLLATE utf8mb4_unicode_ci COMMENT 'Additional remarks or notes',
  `attachments` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of attachment file paths',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int DEFAULT NULL COMMENT 'User ID who last updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_number` (`record_number`),
  UNIQUE KEY `unique_record_number` (`record_number`),
  KEY `idx_status` (`status`),
  KEY `idx_incident_date` (`incident_date`),
  KEY `idx_date_reported` (`date_reported`),
  KEY `idx_incident_type` (`incident_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay blotter records';

-- Data exporting was unselected.

-- Dumping structure for table bmis.blotter_respondents
CREATE TABLE IF NOT EXISTS `blotter_respondents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blotter_id` int NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` int DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Respondent full name',
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `statement` text COLLATE utf8mb4_unicode_ci COMMENT 'Respondent statement',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blotter_id` (`blotter_id`),
  KEY `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_respondent_blotter` FOREIGN KEY (`blotter_id`) REFERENCES `blotter_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_respondent_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter respondents';

-- Data exporting was unselected.

-- Dumping structure for table bmis.certificate_requests
CREATE TABLE IF NOT EXISTS `certificate_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resident_id` int NOT NULL COMMENT 'For Resident ID and Name',
  `certificate_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Purpose of Request',
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'System',
  `date_requested` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date Request',
  `reference_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  KEY `certificate_id` (`certificate_name`),
  CONSTRAINT `fk_cert_req_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=373 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table bmis.events
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` enum('Barangay','Resident') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Barangay',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resident_id` int DEFAULT NULL COMMENT 'Link to residents.id if event_type is Resident',
  `organizer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'Link to users.id',
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_event_resident` (`resident_id`),
  KEY `fk_event_creator` (`created_by`),
  CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_event_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table bmis.households
CREATE TABLE IF NOT EXISTS `households` (
  `id` int NOT NULL AUTO_INCREMENT,
  `household_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique household identifier',
  `household_head_id` int DEFAULT NULL COMMENT 'Foreign key to residents table',
  `household_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `water_source_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toilet_facility_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ownership_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Owned',
  `landlord_resident_id` int DEFAULT NULL,
  `landlord_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `property_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Owned' COMMENT 'Owned or Rent',
  `renter_resident_id` int DEFAULT NULL COMMENT 'Resident ID of renter',
  PRIMARY KEY (`id`),
  UNIQUE KEY `household_number` (`household_number`),
  KEY `idx_household_number` (`household_number`),
  KEY `idx_household_head_id` (`household_head_id`),
  KEY `renter_resident_id` (`renter_resident_id`),
  CONSTRAINT `fk_household_head` FOREIGN KEY (`household_head_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `households_ibfk_1` FOREIGN KEY (`renter_resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household information';

-- Data exporting was unselected.

-- Dumping structure for table bmis.household_members
CREATE TABLE IF NOT EXISTS `household_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `household_id` int NOT NULL COMMENT 'Foreign key to households table',
  `resident_id` int NOT NULL COMMENT 'Foreign key to residents table',
  `relationship_to_head` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Relationship to household head',
  `is_head` tinyint(1) DEFAULT '0' COMMENT '1 if this member is the household head',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_household_resident` (`household_id`,`resident_id`),
  KEY `idx_household_id` (`household_id`),
  KEY `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household members';

-- Data exporting was unselected.

-- Dumping structure for table bmis.residents
CREATE TABLE IF NOT EXISTS `residents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resident_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Auto-generated resident ID (Format: W-XXXXX)',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to resident photo',
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jr., Sr., III, etc.',
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int DEFAULT NULL COMMENT 'Calculated from date_of_birth',
  `place_of_birth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ethnicity` enum('IPS','Non-IPS','') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Indigenous People Status',
  `mobile_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `purok` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') COLLATE utf8mb4_unicode_ci NOT NULL,
  `spouse_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spouse_resident_id` int DEFAULT NULL,
  `father_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_resident_id` int DEFAULT NULL,
  `mother_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_resident_id` int DEFAULT NULL,
  `legal_guardian_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_children` int DEFAULT '0',
  `educational_attainment` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fourps_member` enum('Yes','No') COLLATE utf8mb4_unicode_ci DEFAULT 'No' COMMENT '4Ps Beneficiary',
  `fourps_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '4Ps ID Number',
  `voter_status` enum('Yes','No','') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precinct_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwd_status` enum('Yes','No') COLLATE utf8mb4_unicode_ci DEFAULT 'No' COMMENT 'Person with Disability',
  `pwd_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwd_id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senior_citizen` enum('Yes','No') COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `philhealth_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membership_type` enum('Member','Dependent','None','') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `philhealth_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age_health_group` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_history` text COLLATE utf8mb4_unicode_ci,
  `lmp_date` date DEFAULT NULL COMMENT 'Last Menstrual Period',
  `using_fp_method` enum('Yes','No','') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family Planning Method',
  `fp_methods_used` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family Planning Methods',
  `fp_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family Planning Status',
  `is_house_occupied` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'Yes',
  `caretaker_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caretaker_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `activity_status` enum('Alive','Deceased') COLLATE utf8mb4_unicode_ci DEFAULT 'Alive' COMMENT 'Resident activity status',
  `status_changed_at` datetime DEFAULT NULL COMMENT 'When status was last changed',
  `status_changed_by` int DEFAULT NULL COMMENT 'User ID who changed status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `guardian_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_resident_id` int DEFAULT NULL,
  `guardian_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `renter_resident_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  KEY `idx_last_name` (`last_name`),
  KEY `idx_first_name` (`first_name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main residents information table';

-- Data exporting was unselected.

-- Dumping structure for table bmis.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e5e7eb',
  `text_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#374151',
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table bmis.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hashed password',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to user profile image/avatar',
  `role` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Staff',
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_profile_image` (`profile_image`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System users';

-- Data exporting was unselected.

-- Dumping structure for table bmis.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for view bmis.vw_blotter_complete
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `vw_blotter_complete` (
	`id` INT NOT NULL,
	`record_number` VARCHAR(1) NOT NULL COMMENT 'Format: BR-YYYY-XXXXXX' COLLATE 'utf8mb4_unicode_ci',
	`incident_type` VARCHAR(1) NOT NULL COMMENT 'Type of incident (e.g., Theft, Assault, Noise Complaint)' COLLATE 'utf8mb4_unicode_ci',
	`incident_description` TEXT NOT NULL COMMENT 'Detailed description of the incident' COLLATE 'utf8mb4_unicode_ci',
	`incident_date` DATETIME NOT NULL COMMENT 'Date and time when incident occurred',
	`incident_location` TEXT NOT NULL COMMENT 'Location where incident occurred' COLLATE 'utf8mb4_unicode_ci',
	`date_reported` DATETIME NOT NULL COMMENT 'Date when blotter was reported',
	`status` ENUM('Pending','Under Investigation','Resolved','Dismissed','Scheduled for Mediation','Settled','Endorsed to Police') NULL COLLATE 'utf8mb4_unicode_ci',
	`resolution` TEXT NULL COMMENT 'Resolution details if resolved' COLLATE 'utf8mb4_unicode_ci',
	`resolved_date` DATETIME NULL COMMENT 'Date when case was resolved',
	`remarks` TEXT NULL COMMENT 'Additional remarks or notes' COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NOT NULL,
	`updated_at` TIMESTAMP NOT NULL,
	`complainant_count` BIGINT NOT NULL,
	`respondent_count` BIGINT NOT NULL,
	`complainant_names` TEXT NULL COLLATE 'utf8mb4_unicode_ci',
	`respondent_names` TEXT NULL COLLATE 'utf8mb4_unicode_ci'
) ENGINE=MyISAM;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `vw_blotter_complete`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vw_blotter_complete` AS select `br`.`id` AS `id`,`br`.`record_number` AS `record_number`,`br`.`incident_type` AS `incident_type`,`br`.`incident_description` AS `incident_description`,`br`.`incident_date` AS `incident_date`,`br`.`incident_location` AS `incident_location`,`br`.`date_reported` AS `date_reported`,`br`.`status` AS `status`,`br`.`resolution` AS `resolution`,`br`.`resolved_date` AS `resolved_date`,`br`.`remarks` AS `remarks`,`br`.`created_at` AS `created_at`,`br`.`updated_at` AS `updated_at`,count(distinct `bc`.`id`) AS `complainant_count`,count(distinct `brd`.`id`) AS `respondent_count`,group_concat(distinct `bc`.`name` order by `bc`.`id` ASC separator ', ') AS `complainant_names`,group_concat(distinct `brd`.`name` order by `brd`.`id` ASC separator ', ') AS `respondent_names` from ((`blotter_records` `br` left join `blotter_complainants` `bc` on((`br`.`id` = `bc`.`blotter_id`))) left join `blotter_respondents` `brd` on((`br`.`id` = `brd`.`blotter_id`))) group by `br`.`id` order by `br`.`date_reported` desc;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
