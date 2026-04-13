-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 06:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bmis`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user`, `action`, `description`, `timestamp`) VALUES
(1, 'admin', 'Update Barangay Info', 'Updated barangay information: Barangay Wawandue, Subic, Zambales', '2026-02-23 15:04:31'),
(2, 'admin', 'Update Resident', 'Updated resident record: Danica Aguilar (W-00053)', '2026-03-20 14:46:20'),
(3, 'admin', 'Update Resident', 'Updated resident record: Danica Aguilar (W-00053)', '2026-03-20 14:46:23'),
(4, 'admin', 'Archive Household', 'Moved household to archive: HH-47534', '2026-03-20 14:46:54'),
(5, 'admin', 'Add Resident', 'Added new resident: Zack Tabudlo (W-00225)', '2026-03-20 15:30:37'),
(6, 'admin', 'Transfer Household Head', 'Transferred household head for Household ID 7 to Resident ID 163', '2026-03-20 15:36:19'),
(7, 'admin', 'Update Resident', 'Updated resident record: Danica Aguilar (W-00053)', '2026-03-20 16:07:45'),
(8, 'admin', 'Add Resident', 'Added new resident: Juan Dela Cruz (W-00226)', '2026-03-20 16:10:16'),
(9, 'admin', 'Add Resident', 'Added new resident: Juan Dela Cruz (W-00227)', '2026-03-20 16:16:09'),
(10, 'admin', 'Login', 'User logged in successfully', '2026-03-20 16:36:55'),
(11, 'admin', 'Login', 'User logged in successfully', '2026-03-21 06:04:43'),
(12, 'admin', 'Login', 'User logged in successfully', '2026-03-21 07:01:43'),
(13, 'admin', 'Login', 'User logged in successfully', '2026-03-21 14:22:32'),
(14, 'admin', 'Update Barangay Info', 'Updated barangay information: Barangay Wawandue, Subic, Zambales', '2026-03-21 17:43:23'),
(15, 'admin', 'Update Barangay Info', 'Updated barangay information: Barangay Wawandue, Subic, Zambales', '2026-03-21 17:43:24'),
(16, 'admin', 'Logout', 'User logged out successfully', '2026-03-21 17:43:27'),
(17, 'admin', 'Login', 'User logged in successfully', '2026-03-21 17:49:39'),
(18, 'admin', 'Login', 'User logged in successfully', '2026-03-22 06:34:10'),
(19, 'admin', 'Login', 'User logged in successfully', '2026-03-25 07:04:42'),
(20, 'admin', 'Login', 'User logged in successfully', '2026-03-25 08:20:11'),
(21, 'admin', 'Update Profile Image', 'User updated their profile image', '2026-03-25 08:42:33'),
(22, 'admin', 'Archive Resident', 'Moved resident to archive: Cedric Aguilar (W-00052)', '2026-03-25 08:43:29'),
(23, 'admin', 'Transfer Household Head', 'Transferred household head for HH-47553 to Juan Dela Cruz', '2026-03-25 09:09:40'),
(24, 'admin', 'Restore Archive', 'Restored resident record: W-00052', '2026-03-25 09:18:08'),
(25, 'admin', 'Backup Database', 'Generated database backup for bmis', '2026-03-25 09:18:26'),
(26, 'admin', 'Update Household Members', 'Updated members for household HH-65423. Members: Kiko Castro , Quico Bautista , Pia Bautista', '2026-03-25 09:31:54'),
(27, 'admin', 'Create Household', 'Created new household ', '2026-03-25 09:31:54'),
(28, 'admin', 'Update Household Members', 'Updated members for household HH-65423. Members: Kiko Castro , Quico Bautista , Pia Bautista', '2026-03-25 09:31:56'),
(29, 'admin', 'Create Household', 'Created new household ', '2026-03-25 09:31:56'),
(30, 'admin', 'Update Household Members', 'Updated members for household HH-65423. Members: Kiko Castro , Quico Bautista , Pia Bautista ', '2026-03-25 09:33:53'),
(31, 'admin', 'Create Household', 'Created new household ', '2026-03-25 09:33:53'),
(32, 'admin', 'Update Household Members', 'Updated members for household HH-65423. Members: Kiko Castro , Quico Bautista , Pia Bautista ', '2026-03-25 09:35:29'),
(33, 'admin', 'Update Household', 'Updated household details for HH-65423', '2026-03-25 09:35:29'),
(34, 'admin', 'Delete Household Members', 'Deleted Pia Bautista from household HH-65423', '2026-03-25 09:43:22'),
(35, 'admin', 'Update Household', 'Updated household details for HH-65423', '2026-03-25 09:43:22'),
(36, 'admin', 'Generate Certificate', 'Generate a certificate of Indigency to Cedric Aguilar', '2026-03-25 09:44:26'),
(37, 'admin', 'Update Resident Status', 'Changed activity status of Cedric Aguilar to Deceased', '2026-03-25 10:05:49'),
(38, 'admin', 'Update Resident Status', 'Changed activity status of Edgar Aguilar to Deceased', '2026-03-25 10:06:01'),
(39, 'admin', 'Update Resident Status', 'Changed activity status of Danica Aguilar to Deceased', '2026-03-25 10:06:10'),
(40, 'admin', 'Add Barangay Official', 'Added new barangay official: Hilda Aguilar as Kagawad', '2026-03-25 10:07:59'),
(41, 'admin', 'Update Barangay Info', 'Updated barangay information: Barangay Wawandue, Subic, Zambales', '2026-03-25 12:29:46'),
(42, 'admin', 'Print Masterlist', 'Printed the residents masterlist', '2026-03-25 12:31:13'),
(43, 'admin', 'Print Masterlist', 'Printed the residents masterlist', '2026-03-25 16:43:56'),
(44, 'admin', 'Login', 'User logged in successfully', '2026-03-25 17:04:26'),
(45, 'admin', 'Logout', 'User logged out successfully', '2026-03-25 18:13:13'),
(46, 'admin', 'Login', 'User logged in successfully', '2026-03-26 09:54:12'),
(47, 'admin', 'Print Masterlist', 'Printed the residents masterlist', '2026-03-26 11:26:10'),
(48, 'admin', 'Print Masterlist', 'Printed the residents masterlist', '2026-03-26 12:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `archive`
--

CREATE TABLE `archive` (
  `id` int(11) NOT NULL,
  `archive_type` varchar(50) DEFAULT NULL COMMENT 'Type: resident, official, blotter, permit, user',
  `record_id` varchar(50) DEFAULT NULL COMMENT 'Original record identifier',
  `record_data` longtext DEFAULT NULL COMMENT 'JSON encoded record data',
  `deleted_by` varchar(100) DEFAULT NULL COMMENT 'Username who deleted the record',
  `deleted_at` datetime DEFAULT current_timestamp() COMMENT 'Timestamp when deleted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archive table for deleted records';

--
-- Dumping data for table `archive`
--

INSERT INTO `archive` (`id`, `archive_type`, `record_id`, `record_data`, `deleted_by`, `deleted_at`) VALUES
(1, 'resident', 'W-00001', '{\"id\":115,\"resident_id\":\"W-00001\",\"photo\":null,\"first_name\":\"Mateo\",\"middle_name\":null,\"last_name\":\"Santos\",\"suffix\":null,\"sex\":\"Male\",\"date_of_birth\":\"1980-01-15\",\"age\":46,\"place_of_birth\":null,\"religion\":null,\"ethnicity\":null,\"mobile_number\":\"973 523 1601\",\"email\":null,\"current_address\":\"Purok 1, Barangay Sample\",\"household_no\":null,\"household_contact\":null,\"purok\":null,\"civil_status\":\"Married\",\"spouse_name\":null,\"father_name\":null,\"mother_name\":null,\"number_of_children\":0,\"household_head\":null,\"educational_attainment\":null,\"employment_status\":null,\"occupation\":null,\"monthly_income\":null,\"fourps_member\":\"No\",\"fourps_id\":null,\"voter_status\":null,\"precinct_number\":null,\"pwd_status\":\"No\",\"senior_citizen\":\"No\",\"indigent\":\"No\",\"philhealth_id\":null,\"membership_type\":null,\"philhealth_category\":null,\"age_health_group\":null,\"medical_history\":null,\"lmp_date\":null,\"using_fp_method\":null,\"fp_methods_used\":null,\"fp_status\":null,\"water_source_type\":null,\"toilet_facility_type\":null,\"remarks\":null,\"verification_status\":\"Verified\",\"verified_by\":null,\"verified_at\":null,\"rejection_reason\":null,\"activity_status\":\"Active\",\"status_changed_at\":null,\"status_changed_by\":null,\"status_remarks\":null,\"created_at\":\"2026-02-23 23:23:12\",\"updated_at\":\"2026-02-23 23:23:12\",\"created_by\":null,\"updated_by\":null,\"emergency_contacts\":[]}', 'admin', '2026-02-24 00:12:07'),
(2, 'household', 'HH-47534', '{\"id\":10,\"household_number\":\"HH-47534\",\"household_head_id\":217,\"household_contact\":\"973 514 2112\",\"address\":\"Purok 2, Street 621\",\"water_source_type\":\"Level I (Point Spring)\",\"toilet_facility_type\":\"OH - Overhung Latrine\",\"notes\":\"\",\"created_at\":\"2026-03-01 02:43:37\",\"updated_at\":\"2026-03-02 14:38:05\",\"created_by\":null,\"updated_by\":null,\"head_name\":\"Silvaaaaa Silvaaaaa\",\"members\":[{\"id\":30,\"household_id\":10,\"resident_id\":218,\"relationship_to_head\":\"Son\",\"is_head\":0,\"created_at\":\"2026-03-02 14:38:05\",\"member_name\":\"Madwen Santiagie\"}]}', 'admin', '2026-03-20 22:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, VERIFY, REJECT',
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for changes';

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `resident_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 1, 'CREATE', 'residents', 1, NULL, '{\"first_name\":\"Juan\",\"last_name\":\"Dela Cruz\"}', '127.0.0.1', NULL, '2026-02-12 15:55:48'),
(2, 1, 1, 'VERIFY', 'residents', 1, NULL, '{\"verification_status\":\"Verified\"}', '127.0.0.1', NULL, '2026-02-12 15:55:48'),
(3, 2, 1, 'CREATE', 'residents', 2, NULL, '{\"first_name\":\"Maria\",\"last_name\":\"Santos\"}', '127.0.0.1', NULL, '2026-02-12 15:55:48'),
(4, 3, 1, 'CREATE', 'residents', 3, NULL, '{\"first_name\":\"Pedro\",\"last_name\":\"Reyes\"}', '127.0.0.1', NULL, '2026-02-12 15:55:48'),
(5, 3, 1, 'VERIFY', 'residents', 3, NULL, '{\"verification_status\":\"Verified\"}', '127.0.0.1', NULL, '2026-02-12 15:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `barangay_info`
--

CREATE TABLE `barangay_info` (
  `id` int(11) NOT NULL,
  `province_name` varchar(100) NOT NULL DEFAULT 'Province Name',
  `town_name` varchar(100) NOT NULL DEFAULT 'Town/City Name',
  `barangay_name` varchar(100) NOT NULL DEFAULT 'Barangay Name',
  `contact_number` varchar(20) DEFAULT NULL,
  `dashboard_text` text DEFAULT NULL,
  `municipal_logo` varchar(255) DEFAULT NULL COMMENT 'Path to municipal/city logo',
  `barangay_logo` varchar(255) DEFAULT NULL COMMENT 'Path to barangay logo',
  `official_emblem` varchar(255) DEFAULT NULL COMMENT 'Path to official emblem',
  `dashboard_image` varchar(255) DEFAULT NULL COMMENT 'Path to dashboard background image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay configuration and settings';

--
-- Dumping data for table `barangay_info`
--

INSERT INTO `barangay_info` (`id`, `province_name`, `town_name`, `barangay_name`, `contact_number`, `dashboard_text`, `municipal_logo`, `barangay_logo`, `official_emblem`, `dashboard_image`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Zambales', 'Subic', 'Barangay Wawandue', '09XXXXXXXXXX', '', 'assets/uploads/barangay/logos/municipal_logo_1771859071.png', 'assets/uploads/barangay/logos/barangay_logo_1771859071.png', 'assets/uploads/barangay/logos/official_emblem_1773409306.png', 'assets/uploads/barangay/dashboard/dashboard_1774115003.png', '2026-02-23 14:59:41', '2026-03-25 12:29:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `barangay_officials`
--

CREATE TABLE `barangay_officials` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL COMMENT 'Foreign key to residents table',
  `fullname` varchar(255) DEFAULT NULL COMMENT 'Full name of the official',
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
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay officials information';

--
-- Dumping data for table `barangay_officials`
--

INSERT INTO `barangay_officials` (`id`, `resident_id`, `fullname`, `position`, `committee`, `hierarchy_level`, `term_start`, `term_end`, `status`, `appointment_type`, `photo`, `contact_number`, `email`, `office_address`, `remarks`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 226, 'Juan Dela Cruz', 'Barangay Captain', NULL, 1, '2026-02-09', '2026-10-30', 'Active', 'Elected', NULL, '917 823 6154', NULL, NULL, NULL, '2026-02-23 15:56:32', '2026-03-20 16:20:05', NULL, NULL),
(3, 133, 'Rosa Cruz', 'Kagawad', 'Infrastructure', 2, '2026-03-11', '2026-03-28', 'Active', 'Elected', NULL, '973 523 1621', NULL, NULL, NULL, '2026-03-08 11:03:12', '2026-03-08 11:03:12', NULL, NULL),
(4, 201, 'Oliver Castro', 'Kagawad', 'Peace and Order', 2, '2026-03-03', '2026-12-25', 'Active', 'Elected', NULL, '973 523 1689', NULL, NULL, NULL, '2026-03-08 11:03:35', '2026-03-08 11:03:35', NULL, NULL),
(5, 226, 'Juan Dela Cruz', 'Barangay Administator', NULL, 3, '2026-02-04', '2026-11-28', 'Active', 'Appointed', NULL, '917 823 6154', NULL, NULL, NULL, '2026-03-20 16:20:51', '2026-03-20 16:20:51', NULL, NULL),
(6, 169, 'Hilda Aguilar', 'Kagawad', NULL, 2, '2026-03-11', '2026-03-27', 'Active', 'Elected', NULL, '973 523 1657', NULL, NULL, NULL, '2026-03-25 10:07:59', '2026-03-25 10:07:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blotter_complainants`
--

CREATE TABLE `blotter_complainants` (
  `id` int(11) NOT NULL,
  `blotter_id` int(11) NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` int(11) DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` varchar(255) NOT NULL COMMENT 'Complainant full name',
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `statement` text DEFAULT NULL COMMENT 'Complainant statement',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter complainants';

--
-- Dumping data for table `blotter_complainants`
--

INSERT INTO `blotter_complainants` (`id`, `blotter_id`, `resident_id`, `name`, `contact_number`, `address`, `statement`, `created_at`) VALUES
(1, 7, 165, 'Danica Aguilar', '+639735231653', 'Purok 3, Barangay Sample', NULL, '2026-02-23 15:33:05'),
(2, 7, 167, 'Fiona Aguilar', '+639735231655', 'Purok 4, Barangay Sample', 'VICTIM', '2026-02-23 15:33:05');

-- --------------------------------------------------------

--
-- Table structure for table `blotter_records`
--

CREATE TABLE `blotter_records` (
  `id` int(11) NOT NULL,
  `record_number` varchar(50) NOT NULL COMMENT 'Format: BR-YYYY-XXXXXX',
  `incident_type` varchar(255) NOT NULL COMMENT 'Type of incident (e.g., Theft, Assault, Noise Complaint)',
  `incident_description` text NOT NULL COMMENT 'Detailed description of the incident',
  `incident_date` datetime NOT NULL COMMENT 'Date and time when incident occurred',
  `incident_location` text NOT NULL COMMENT 'Location where incident occurred',
  `date_reported` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date when blotter was reported',
  `reported_by` varchar(255) DEFAULT NULL COMMENT 'Person who reported (if not complainant)',
  `status` enum('Pending','Under Investigation','Resolved','Dismissed') DEFAULT 'Pending' COMMENT 'Current status of the blotter',
  `status_updated_at` datetime DEFAULT NULL COMMENT 'When status was last updated',
  `status_updated_by` int(11) DEFAULT NULL COMMENT 'User ID who updated status',
  `resolution` text DEFAULT NULL COMMENT 'Resolution details if resolved',
  `resolved_date` datetime DEFAULT NULL COMMENT 'Date when case was resolved',
  `resolved_by` int(11) DEFAULT NULL COMMENT 'User ID who resolved the case',
  `remarks` text DEFAULT NULL COMMENT 'Additional remarks or notes',
  `attachments` text DEFAULT NULL COMMENT 'JSON array of attachment file paths',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay blotter records';

--
-- Add new columns for Case Outcome enhancement (BLACKBOXAI)
--
ALTER TABLE `blotter_records` 
ADD COLUMN `case_outcome` VARCHAR(50) DEFAULT NULL COMMENT 'Settled|Scheduled for Mediation|Referred to Police/Court (CFA)|Dismissed' AFTER `resolution`,
ADD COLUMN `mediation_schedule` DATETIME DEFAULT NULL COMMENT 'Mediation date/time if scheduled' AFTER `case_outcome`;

--
-- Add new columns for Case Outcome enhancement (BLACKBOXAI)
--
ALTER TABLE `blotter_records` 
ADD COLUMN `case_outcome` VARCHAR(50) DEFAULT NULL COMMENT 'Settled|Scheduled for Mediation|Referred to Police/Court (CFA)|Dismissed' AFTER `resolution`,
ADD COLUMN `mediation_schedule` DATETIME DEFAULT NULL COMMENT 'Mediation date/time if scheduled' AFTER `case_outcome`;

--
-- Add new columns for Case Outcome enhancement
--
ALTER TABLE `blotter_records` 
ADD COLUMN `case_outcome` VARCHAR(50) DEFAULT NULL COMMENT 'Settled|Scheduled for Mediation|Referred to Police/Court (CFA)|Dismissed' AFTER `resolution`,
ADD COLUMN `mediation_schedule` DATETIME DEFAULT NULL COMMENT 'Mediation date/time if scheduled' AFTER `case_outcome`;

--
-- Dumping data for table `blotter_records`
--

INSERT INTO `blotter_records` (`id`, `record_number`, `incident_type`, `incident_description`, `incident_date`, `incident_location`, `date_reported`, `reported_by`, `status`, `status_updated_at`, `status_updated_by`, `resolution`, `resolved_date`, `resolved_by`, `remarks`, `attachments`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(7, 'BR-2026-000001', 'Noise Complaint', 'MAINGAY', '2026-02-23 23:32:00', 'Taas tulay', '2026-02-23 23:33:05', NULL, 'Pending', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-02-23 15:33:05', '2026-02-23 15:33:05', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blotter_respondents`
--

CREATE TABLE `blotter_respondents` (
  `id` int(11) NOT NULL,
  `blotter_id` int(11) NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` int(11) DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` varchar(255) NOT NULL COMMENT 'Respondent full name',
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `statement` text DEFAULT NULL COMMENT 'Respondent statement',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter respondents';

--
-- Dumping data for table `blotter_respondents`
--

INSERT INTO `blotter_respondents` (`id`, `blotter_id`, `resident_id`, `name`, `contact_number`, `address`, `statement`, `created_at`) VALUES
(11, 7, 160, 'Silas Bautista', '+639735231648', 'Purok 1, Barangay Sample', NULL, '2026-02-23 15:33:05');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_requests`
--

CREATE TABLE `certificate_requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'For Resident ID and Name',
  `certificate_name` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL COMMENT 'Purpose of Request',
  `date_requested` datetime DEFAULT current_timestamp() COMMENT 'Date Request',
  `status` varchar(50) DEFAULT 'Approved',
  `reference_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_requests`
--

INSERT INTO `certificate_requests` (`id`, `resident_id`, `certificate_name`, `purpose`, `date_requested`, `status`, `reference_no`, `created_at`) VALUES
(152, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-08 15:25:00', 'Approved', 'REQ-20260308-8794', '2026-03-08 07:25:00'),
(153, 153, 'Certificate of Good Moral Character', 'EDUCATIONAL', '2026-03-08 18:43:00', 'Approved', 'REQ-20260308-2582', '2026-03-08 10:43:00'),
(154, 166, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-09 20:10:42', 'Approved', 'REQ-20260309-9890', '2026-03-09 12:10:42'),
(155, 166, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-09 20:14:18', 'Approved', 'REQ-20260309-6890', '2026-03-09 12:14:18'),
(156, 166, 'Certificate of Residency', 'Residency Proof', '2026-03-09 20:15:25', 'Approved', 'REQ-20260309-9248', '2026-03-09 12:15:25'),
(157, 165, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-09 23:19:21', 'Approved', 'REQ-20260309-5525', '2026-03-09 15:19:21'),
(158, 165, 'Certificate of Indigency', 'Indigency Assistance', '2026-03-10 00:02:11', 'Approved', 'REQ-20260310-9594', '2026-03-09 16:02:11'),
(159, 165, 'Certificate of Indigency', 'Indigency Assistance', '2026-03-10 00:02:19', 'Approved', 'REQ-20260310-8871', '2026-03-09 16:02:19'),
(160, 165, 'Certificate of Indigency', 'Indigency Assistance', '2026-03-10 00:02:27', 'Approved', 'REQ-20260310-3193', '2026-03-09 16:02:27'),
(161, 165, 'Certificate of Indigency', 'Indigency Assistance', '2026-03-10 00:02:37', 'Approved', 'REQ-20260310-9967', '2026-03-09 16:02:37'),
(162, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-10 00:03:03', 'Approved', 'REQ-20260310-1571', '2026-03-09 16:03:03'),
(163, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-10 00:03:13', 'Approved', 'REQ-20260310-7097', '2026-03-09 16:03:13'),
(164, 165, 'Certificate of Indigency', 'Indigency Assistance', '2026-03-10 00:03:21', 'Approved', 'REQ-20260310-2911', '2026-03-09 16:03:21'),
(165, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-10 00:06:35', 'Approved', 'REQ-20260310-8305', '2026-03-09 16:06:35'),
(166, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-10 00:06:40', 'Approved', 'REQ-20260310-1052', '2026-03-09 16:06:40'),
(167, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-10 00:06:45', 'Approved', 'REQ-20260310-3626', '2026-03-09 16:06:45'),
(168, 166, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-10 00:17:24', 'Approved', 'REQ-20260310-1765', '2026-03-09 16:17:24'),
(169, 165, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-10 00:27:52', 'Approved', 'REQ-20260310-9894', '2026-03-09 16:27:52'),
(170, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-10 00:35:42', 'Approved', 'REQ-20260310-2574', '2026-03-09 16:35:42'),
(171, 165, 'Certificate of Indigency', 'MEDICAL', '2026-03-10 00:44:15', 'Approved', 'REQ-20260310-5461', '2026-03-09 16:44:15'),
(172, 165, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-10 00:44:15', 'Approved', 'REQ-20260310-1936', '2026-03-09 16:44:15'),
(173, 165, 'Certificate of Indigency', 'MEDICAL', '2026-03-10 00:44:44', 'Approved', 'REQ-20260310-6303', '2026-03-09 16:44:44'),
(174, 165, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-10 00:44:45', 'Approved', 'REQ-20260310-2291', '2026-03-09 16:44:45'),
(175, 166, 'Certificate of Low Income', 'Low Income Verification', '2026-03-10 00:45:03', 'Approved', 'REQ-20260310-7265', '2026-03-09 16:45:03'),
(176, 166, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 00:45:04', 'Approved', 'REQ-20260310-7642', '2026-03-09 16:45:04'),
(177, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-10 00:45:59', 'Approved', 'REQ-20260310-6384', '2026-03-09 16:45:59'),
(178, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-10 00:46:01', 'Approved', 'REQ-20260310-2441', '2026-03-09 16:46:01'),
(179, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:06:19', 'Approved', 'REQ-20260310-1594', '2026-03-09 17:06:19'),
(180, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:06:38', 'Approved', 'REQ-20260310-9488', '2026-03-09 17:06:38'),
(181, 167, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:06:47', 'Approved', 'REQ-20260310-8525', '2026-03-09 17:06:47'),
(182, 171, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-10 01:07:07', 'Approved', 'REQ-20260310-5425', '2026-03-09 17:07:07'),
(183, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:11:04', 'Approved', 'REQ-20260310-5233', '2026-03-09 17:11:04'),
(184, 166, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-10 01:11:19', 'Approved', 'REQ-20260310-1248', '2026-03-09 17:11:19'),
(185, 167, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-10 01:11:32', 'Approved', 'REQ-20260310-5872', '2026-03-09 17:11:32'),
(186, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:12:07', 'Approved', 'REQ-20260310-9936', '2026-03-09 17:12:07'),
(187, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:13:52', 'Approved', 'REQ-20260310-4669', '2026-03-09 17:13:52'),
(188, 165, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-10 01:15:56', 'Approved', 'REQ-20260310-6994', '2026-03-09 17:15:56'),
(189, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 01:38:46', 'Approved', 'REQ-20260310-3420', '2026-03-09 17:38:46'),
(190, 165, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-10 01:39:45', 'Approved', 'REQ-20260310-6645', '2026-03-09 17:39:45'),
(191, 165, 'Certificate of Residency', 'FOR EMPLOYMENT', '2026-03-10 01:39:59', 'Approved', 'REQ-20260310-4807', '2026-03-09 17:39:59'),
(192, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-10 01:40:18', 'Approved', 'REQ-20260310-6227', '2026-03-09 17:40:18'),
(193, 165, 'Business Permit', 'WHOLESALE', '2026-03-10 01:40:33', 'Approved', 'REQ-20260310-6771', '2026-03-09 17:40:33'),
(194, 165, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-10 01:40:46', 'Approved', 'REQ-20260310-8394', '2026-03-09 17:40:46'),
(195, 165, 'Certificate of Job Seeker Assistance', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-10 01:41:01', 'Approved', 'REQ-20260310-1430', '2026-03-09 17:41:01'),
(196, 165, 'Certificate of Good Moral Character', 'Good Moral Character Verification', '2026-03-10 01:41:12', 'Approved', 'REQ-20260310-7975', '2026-03-09 17:41:12'),
(197, 165, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-10 01:41:21', 'Approved', 'REQ-20260310-2786', '2026-03-09 17:41:21'),
(198, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-10 01:41:33', 'Approved', 'REQ-20260310-6745', '2026-03-09 17:41:33'),
(199, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-10 01:46:47', 'Approved', 'REQ-20260310-3996', '2026-03-09 17:46:47'),
(200, 165, 'Barangay Clearance', 'Barangay Clearance', '2026-03-10 01:48:31', 'Approved', 'REQ-20260310-3011', '2026-03-09 17:48:31'),
(201, 165, 'Barangay Clearance', 'FOR EMPLOYMENT', '2026-03-10 01:48:39', 'Approved', 'REQ-20260310-3247', '2026-03-09 17:48:39'),
(202, 165, 'Certificate of Job Seeker Assistance', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-10 01:49:05', 'Approved', 'REQ-20260310-4858', '2026-03-09 17:49:05'),
(203, 166, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-10 02:14:46', 'Approved', 'REQ-20260310-7614', '2026-03-09 18:14:46'),
(204, 167, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-10 02:18:40', 'Approved', 'REQ-20260310-8548', '2026-03-09 18:18:40'),
(205, 166, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-13 21:45:01', 'Approved', 'REQ-20260313-2223', '2026-03-13 13:45:01'),
(206, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-14 00:54:41', 'Approved', 'REQ-20260314-3684', '2026-03-13 16:54:41'),
(207, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-14 01:01:16', 'Approved', 'REQ-20260314-7642', '2026-03-13 17:01:16'),
(208, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-14 01:01:27', 'Approved', 'REQ-20260314-5831', '2026-03-13 17:01:27'),
(209, 165, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:03:21', 'Approved', 'REQ-20260314-7234', '2026-03-13 17:03:21'),
(210, 165, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:04:40', 'Approved', 'REQ-20260314-7160', '2026-03-13 17:04:40'),
(211, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:06:57', 'Approved', 'REQ-20260314-2586', '2026-03-13 17:06:57'),
(212, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:07:41', 'Approved', 'REQ-20260314-1617', '2026-03-13 17:07:41'),
(213, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:07:55', 'Approved', 'REQ-20260314-8035', '2026-03-13 17:07:55'),
(214, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:08:13', 'Approved', 'REQ-20260314-9402', '2026-03-13 17:08:13'),
(215, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:08:36', 'Approved', 'REQ-20260314-2260', '2026-03-13 17:08:36'),
(216, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:08:43', 'Approved', 'REQ-20260314-1011', '2026-03-13 17:08:43'),
(217, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:08:56', 'Approved', 'REQ-20260314-7837', '2026-03-13 17:08:56'),
(218, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:09:39', 'Approved', 'REQ-20260314-8140', '2026-03-13 17:09:39'),
(219, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:10:39', 'Approved', 'REQ-20260314-1873', '2026-03-13 17:10:39'),
(220, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 01:11:02', 'Approved', 'REQ-20260314-6877', '2026-03-13 17:11:02'),
(221, 167, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:12:36', 'Approved', 'REQ-20260314-8908', '2026-03-13 17:12:36'),
(222, 167, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:14:18', 'Approved', 'REQ-20260314-6617', '2026-03-13 17:14:18'),
(223, 167, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:14:37', 'Approved', 'REQ-20260314-8245', '2026-03-13 17:14:37'),
(224, 167, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:15:04', 'Approved', 'REQ-20260314-8816', '2026-03-13 17:15:04'),
(225, 167, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-14 01:15:39', 'Approved', 'REQ-20260314-6713', '2026-03-13 17:15:39'),
(226, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-14 01:19:16', 'Approved', 'REQ-20260314-2608', '2026-03-13 17:19:16'),
(227, 165, 'Certificate of Low-Income', 'Low Income Verification', '2026-03-14 01:22:09', 'Approved', 'REQ-20260314-2935', '2026-03-13 17:22:09'),
(228, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:22:58', 'Approved', 'REQ-20260314-5849', '2026-03-13 17:22:58'),
(229, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:24:14', 'Approved', 'REQ-20260314-4865', '2026-03-13 17:24:14'),
(230, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:26:04', 'Approved', 'REQ-20260314-3847', '2026-03-13 17:26:04'),
(231, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:26:24', 'Approved', 'REQ-20260314-9260', '2026-03-13 17:26:24'),
(232, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:26:46', 'Approved', 'REQ-20260314-9369', '2026-03-13 17:26:46'),
(233, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:27:06', 'Approved', 'REQ-20260314-1466', '2026-03-13 17:27:06'),
(234, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:27:48', 'Approved', 'REQ-20260314-8162', '2026-03-13 17:27:48'),
(235, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:29:39', 'Approved', 'REQ-20260314-2878', '2026-03-13 17:29:39'),
(236, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-14 01:32:24', 'Approved', 'REQ-20260314-2843', '2026-03-13 17:32:24'),
(237, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-14 01:33:26', 'Approved', 'REQ-20260314-7643', '2026-03-13 17:33:26'),
(238, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-14 01:34:10', 'Approved', 'REQ-20260314-6683', '2026-03-13 17:34:10'),
(239, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:40:34', 'Approved', 'REQ-20260314-8967', '2026-03-13 17:40:34'),
(240, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:41:12', 'Approved', 'REQ-20260314-9140', '2026-03-13 17:41:12'),
(241, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:41:43', 'Approved', 'REQ-20260314-1139', '2026-03-13 17:41:43'),
(242, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:41:57', 'Approved', 'REQ-20260314-4009', '2026-03-13 17:41:57'),
(243, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:42:25', 'Approved', 'REQ-20260314-6710', '2026-03-13 17:42:25'),
(244, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:43:16', 'Approved', 'REQ-20260314-7064', '2026-03-13 17:43:16'),
(245, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:44:09', 'Approved', 'REQ-20260314-6733', '2026-03-13 17:44:09'),
(246, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:44:38', 'Approved', 'REQ-20260314-7829', '2026-03-13 17:44:38'),
(247, 166, 'Barangay Clearance', 'WORKING PERMIT', '2026-03-14 01:44:49', 'Approved', 'REQ-20260314-4816', '2026-03-13 17:44:49'),
(248, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:46:07', 'Approved', 'REQ-20260314-3061', '2026-03-13 17:46:07'),
(249, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:46:15', 'Approved', 'REQ-20260314-5708', '2026-03-13 17:46:15'),
(250, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:49:48', 'Approved', 'REQ-20260314-8976', '2026-03-13 17:49:48'),
(251, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:50:10', 'Approved', 'REQ-20260314-4271', '2026-03-13 17:50:10'),
(252, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:50:29', 'Approved', 'REQ-20260314-6480', '2026-03-13 17:50:29'),
(253, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:50:42', 'Approved', 'REQ-20260314-8223', '2026-03-13 17:50:42'),
(254, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:50:50', 'Approved', 'REQ-20260314-4225', '2026-03-13 17:50:50'),
(255, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:51:09', 'Approved', 'REQ-20260314-7473', '2026-03-13 17:51:09'),
(256, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:51:23', 'Approved', 'REQ-20260314-6436', '2026-03-13 17:51:23'),
(257, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:51:37', 'Approved', 'REQ-20260314-4967', '2026-03-13 17:51:37'),
(258, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:52:13', 'Approved', 'REQ-20260314-1572', '2026-03-13 17:52:13'),
(259, 165, 'Barangay Business Clearance', 'RETAIL', '2026-03-14 01:52:27', 'Approved', 'REQ-20260314-5760', '2026-03-13 17:52:27'),
(260, 165, 'Business Permit', 'WHOLESALE', '2026-03-14 01:54:46', 'Approved', 'REQ-20260314-2815', '2026-03-13 17:54:46'),
(261, 165, 'Business Permit', 'WHOLESALE', '2026-03-14 01:55:02', 'Approved', 'REQ-20260314-3075', '2026-03-13 17:55:02'),
(262, 165, 'Business Permit', 'WHOLESALE', '2026-03-14 01:55:25', 'Approved', 'REQ-20260314-9896', '2026-03-13 17:55:25'),
(263, 165, 'Business Permit', 'WHOLESALE', '2026-03-14 01:56:01', 'Approved', 'REQ-20260314-8536', '2026-03-13 17:56:01'),
(264, 165, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-14 01:58:51', 'Approved', 'REQ-20260314-6046', '2026-03-13 17:58:51'),
(265, 165, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-14 01:59:26', 'Approved', 'REQ-20260314-5487', '2026-03-13 17:59:26'),
(266, 165, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-14 01:59:42', 'Approved', 'REQ-20260314-2267', '2026-03-13 17:59:42'),
(267, 165, 'Certificate of Job Seeker Assistance', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-14 02:02:00', 'Approved', 'REQ-20260314-7945', '2026-03-13 18:02:00'),
(268, 153, 'Certificate of Good Moral Character', 'DEPED RANKING', '2026-03-14 02:04:05', 'Approved', 'REQ-20260314-2039', '2026-03-13 18:04:05'),
(269, 165, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-14 02:06:49', 'Approved', 'REQ-20260314-8336', '2026-03-13 18:06:49'),
(270, 165, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-14 02:08:01', 'Approved', 'REQ-20260314-1768', '2026-03-13 18:08:01'),
(271, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:08:47', 'Approved', 'REQ-20260314-4930', '2026-03-13 18:08:47'),
(272, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:09:33', 'Approved', 'REQ-20260314-4126', '2026-03-13 18:09:33'),
(273, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:11:39', 'Approved', 'REQ-20260314-8807', '2026-03-13 18:11:39'),
(274, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:12:19', 'Approved', 'REQ-20260314-6497', '2026-03-13 18:12:19'),
(275, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:12:34', 'Approved', 'REQ-20260314-4705', '2026-03-13 18:12:34'),
(276, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-14 02:12:46', 'Approved', 'REQ-20260314-2002', '2026-03-13 18:12:46'),
(277, 153, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 15:03:31', 'Approved', 'REQ-20260314-3236', '2026-03-14 07:03:31'),
(278, 167, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-14 15:04:59', 'Approved', 'REQ-20260314-6848', '2026-03-14 07:04:59'),
(279, 168, 'Certificate of Residency', 'Residency Proof', '2026-03-14 15:09:34', 'Approved', 'REQ-20260314-7435', '2026-03-14 07:09:34'),
(280, 167, 'Certificate of Residency', 'Residency Proof', '2026-03-14 15:10:51', 'Approved', 'REQ-20260314-9876', '2026-03-14 07:10:51'),
(281, 167, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-14 15:11:09', 'Approved', 'REQ-20260314-7290', '2026-03-14 07:11:09'),
(282, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-15 21:47:56', 'Approved', 'REQ-20260315-4816', '2026-03-15 13:47:56'),
(283, 165, 'Certificate of Low-Income', 'MEDICAL', '2026-03-15 23:29:39', 'Approved', 'REQ-20260315-2294', '2026-03-15 15:29:39'),
(284, 165, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-18 17:13:06', 'Approved', 'REQ-20260318-2417', '2026-03-18 09:13:06'),
(285, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-18 17:13:15', 'Approved', 'REQ-20260318-3800', '2026-03-18 09:13:15'),
(286, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-18 17:13:20', 'Approved', 'REQ-20260318-9259', '2026-03-18 09:13:20'),
(287, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-18 17:13:23', 'Approved', 'REQ-20260318-7329', '2026-03-18 09:13:23'),
(288, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-18 17:13:26', 'Approved', 'REQ-20260318-3603', '2026-03-18 09:13:26'),
(289, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-18 17:13:29', 'Approved', 'REQ-20260318-4034', '2026-03-18 09:13:29'),
(290, 165, 'Certificate of Indigency', 'BURIAL Assistance', '2026-03-19 00:26:26', 'Approved', 'REQ-20260319-4171', '2026-03-18 16:26:26'),
(291, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-19 00:26:34', 'Approved', 'REQ-20260319-6034', '2026-03-18 16:26:34'),
(292, 166, 'Certificate of Low-Income', 'SCHOLARSHIP', '2026-03-19 00:26:49', 'Approved', 'REQ-20260319-9590', '2026-03-18 16:26:49'),
(293, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-19 00:27:03', 'Approved', 'REQ-20260319-7493', '2026-03-18 16:27:03'),
(294, 166, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-19 00:31:45', 'Approved', 'REQ-20260319-1025', '2026-03-18 16:31:45'),
(295, 166, 'Certificate of Indigency', 'MEDICAL Assistance', '2026-03-19 00:37:27', 'Approved', 'REQ-20260319-6483', '2026-03-18 16:37:27'),
(296, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-19 00:38:31', 'Approved', 'REQ-20260319-2101', '2026-03-18 16:38:31'),
(297, 165, 'Barangay Clearance', 'Barangay Clearance', '2026-03-19 00:45:26', 'Approved', 'REQ-20260319-8075', '2026-03-18 16:45:26'),
(298, 165, 'Barangay Clearance', 'FOR EMPLOYMENT', '2026-03-19 00:46:12', 'Approved', 'REQ-20260319-7549', '2026-03-18 16:46:12'),
(299, 165, 'Barangay Business Clearance', 'SERVICE', '2026-03-19 00:47:57', 'Approved', 'REQ-20260319-6983', '2026-03-18 16:47:57'),
(300, 165, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-19 00:48:17', 'Approved', 'REQ-20260319-9295', '2026-03-18 16:48:17'),
(301, 165, 'Certificate of Residency', 'Residency Proof', '2026-03-19 00:48:51', 'Approved', 'REQ-20260319-5636', '2026-03-18 16:48:51'),
(302, 165, 'Certificate of Low-Income', 'MEDICAL', '2026-03-19 00:49:04', 'Approved', 'REQ-20260319-9168', '2026-03-18 16:49:04'),
(303, 165, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-19 00:49:23', 'Approved', 'REQ-20260319-9425', '2026-03-18 16:49:23'),
(304, 165, 'Business Permit', 'RETAIL', '2026-03-19 00:49:35', 'Approved', 'REQ-20260319-5328', '2026-03-18 16:49:35'),
(305, 166, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-19 00:50:26', 'Approved', 'REQ-20260319-8540', '2026-03-18 16:50:26'),
(306, 165, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-19 00:50:34', 'Approved', 'REQ-20260319-7130', '2026-03-18 16:50:34'),
(307, 165, 'Certificate of Job Seeker Assistance', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-19 00:50:51', 'Approved', 'REQ-20260319-1308', '2026-03-18 16:50:51'),
(308, 165, 'Certificate of Good Moral Character', 'EDUCATIONAL', '2026-03-19 00:51:13', 'Approved', 'REQ-20260319-3818', '2026-03-18 16:51:13'),
(309, 165, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-19 00:51:32', 'Approved', 'REQ-20260319-4235', '2026-03-18 16:51:32'),
(310, 165, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-19 00:51:46', 'Approved', 'REQ-20260319-7479', '2026-03-18 16:51:46'),
(311, 165, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-19 00:55:24', 'Approved', 'REQ-20260319-2606', '2026-03-18 16:55:24'),
(312, 165, 'Business Permit', 'RETAIL', '2026-03-19 00:57:52', 'Approved', 'REQ-20260319-3158', '2026-03-18 16:57:52'),
(314, 165, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-21 00:08:28', 'Approved', 'REQ-20260321-1293', '2026-03-20 16:08:28'),
(315, 226, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-21 00:17:06', 'Approved', 'REQ-20260321-1675', '2026-03-20 16:17:06'),
(316, 226, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-21 00:21:44', 'Approved', 'REQ-20260321-7297', '2026-03-20 16:21:44'),
(317, 226, 'Certificate of Low-Income', 'MEDICAL', '2026-03-21 00:22:06', 'Approved', 'REQ-20260321-3484', '2026-03-20 16:22:06'),
(318, 226, 'Certificate of Solo Parent', 'Solo Parent Verification', '2026-03-21 00:22:19', 'Approved', 'REQ-20260321-1211', '2026-03-20 16:22:19'),
(319, 226, 'Certificate of Residency', 'BANK PURPOSES', '2026-03-21 00:22:39', 'Approved', 'REQ-20260321-2389', '2026-03-20 16:22:39'),
(320, 226, 'Registration of Birth Certificate', 'Birth Certificate Registration', '2026-03-21 00:22:54', 'Approved', 'REQ-20260321-7526', '2026-03-20 16:22:54'),
(321, 226, 'Barangay Clearance', 'FOR EMPLOYMENT', '2026-03-21 00:23:07', 'Approved', 'REQ-20260321-5020', '2026-03-20 16:23:07'),
(322, 226, 'Barangay Business Clearance', 'WHOLESALE', '2026-03-21 00:23:39', 'Approved', 'REQ-20260321-3185', '2026-03-20 16:23:39'),
(323, 226, 'Barangay Business Clearance', 'WHOLESALE', '2026-03-21 00:24:42', 'Approved', 'REQ-20260321-4163', '2026-03-20 16:24:42'),
(324, 226, 'Barangay Clearance', 'FISHING PERMIT', '2026-03-21 00:25:15', 'Approved', 'REQ-20260321-6933', '2026-03-20 16:25:15'),
(325, 226, 'Barangay Business Clearance', 'WHOLESALE', '2026-03-21 00:26:23', 'Approved', 'REQ-20260321-6448', '2026-03-20 16:26:23'),
(326, 226, 'Barangay Clearance', 'FOR EMPLOYMENT', '2026-03-21 00:26:53', 'Approved', 'REQ-20260321-9616', '2026-03-20 16:26:53'),
(327, 226, 'Business Permit', 'WHOLESALE', '2026-03-21 00:27:18', 'Approved', 'REQ-20260321-8209', '2026-03-20 16:27:18'),
(328, 226, 'Barangay Fishing Clearance', 'Boat Registration', '2026-03-21 00:27:39', 'Approved', 'REQ-20260321-8465', '2026-03-20 16:27:39'),
(329, 226, 'Certificate for Vessel Docking', 'Vessel Docking Certification', '2026-03-21 00:28:16', 'Approved', 'REQ-20260321-8853', '2026-03-20 16:28:16'),
(330, 226, 'Certificate of Job Seeker Assistance', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-21 00:28:28', 'Approved', 'REQ-20260321-8511', '2026-03-20 16:28:28'),
(331, 226, 'Certificate of Good Moral Character', 'EDUCATIONAL', '2026-03-21 00:28:46', 'Approved', 'REQ-20260321-2853', '2026-03-20 16:28:46'),
(332, 226, 'Certificate of Oath of Undertaking', 'First-Time Jobseeker Assistance (RA 11261)', '2026-03-21 00:28:58', 'Approved', 'REQ-20260321-5150', '2026-03-20 16:28:58'),
(335, 228, 'Certificate of Indigency', 'FINANCIAL Assistance', '2026-03-25 17:44:26', 'Approved', 'REQ-20260325-8049', '2026-03-25 09:44:26');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'Foreign key to residents table',
  `contact_name` varchar(255) NOT NULL,
  `relationship` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `priority` int(2) DEFAULT 1 COMMENT 'Contact priority order (1=primary, 2=secondary, etc.)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emergency contacts for residents';

--
-- Dumping data for table `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`id`, `resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`, `created_at`, `updated_at`) VALUES
(3, 2, 'Jose Santos', 'Father', '+63 923 456 7891', 'Purok 3, Barangay Sample, City', 1, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(4, 3, 'Luz Reyes', 'Spouse', '+63 934 567 8902', 'Purok 4, Barangay Sample, City', 1, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(5, 3, 'Pedro Reyes Jr.', 'Son', '+63 934 567 8903', 'Purok 5, Barangay Sample, City', 2, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(7, 5, 'Linda Mendoza', 'Spouse', '+63 956 789 0124', 'Purok 6, Barangay Sample, City', 1, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(8, 6, 'Argel Gonzales Tayson', 'Son', '+639311913120', '343242', 1, '2026-02-12 16:13:45', '2026-02-12 16:13:45'),
(9, 7, 'Argel Gonzales Tayson', 'sdas', '+639311913120', '343242', 1, '2026-02-12 16:24:06', '2026-02-12 16:24:06'),
(13, 8, '4werw', '31231', '324342', NULL, 1, '2026-02-12 17:59:37', '2026-02-12 17:59:37'),
(16, 9, '4werw', '31231', '324342', NULL, 1, '2026-02-12 18:14:31', '2026-02-12 18:14:31'),
(19, 1, 'Maria Dela Cruz', 'Spouse', '+63 912 345 6790', 'Purok 1, Barangay Sample, City', 1, '2026-02-12 18:25:19', '2026-02-12 18:25:19'),
(20, 1, 'Pedro Dela Cruz', 'Father', '+63 912 345 6791', 'Purok 2, Barangay Sample, City', 2, '2026-02-12 18:25:19', '2026-02-12 18:25:19'),
(21, 10, 'Argel Gonzales Tayson', 'Brother', '6393-119-1312', '343242', 1, '2026-02-16 16:43:57', '2026-02-16 16:43:57'),
(22, 4, 'Roberto Cruz', 'Father', '+63 945 678 9013', 'Purok 5, Barangay Sample, City', 1, '2026-02-19 12:57:13', '2026-02-19 12:57:13'),
(24, 11, 'Argel Gonzales Tayson', 'W', '6393-119-1312', '343242', 1, '2026-02-22 11:20:49', '2026-02-22 11:20:49');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household information';

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `household_number`, `household_head_id`, `household_contact`, `address`, `water_source_type`, `toilet_facility_type`, `notes`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(7, 'HH-65161', 163, '972 361 6231', 'Purok 2', 'Level I (Point Spring)', 'P - Pour/Flush toilet connected to septic tank)', '', '2026-02-23 15:25:33', '2026-03-20 15:36:19', NULL, NULL),
(8, 'HH-47553', 226, '972 512 3212', 'Purok 3', 'Level III (Individual Connection)', 'Closed Pit', '', '2026-02-23 15:27:45', '2026-03-25 09:09:40', NULL, NULL),
(9, 'HH-65132', 215, '973 514 2158', 'House No. 219, Purok 2, Street 621', 'Level II (Communal Faucet system or stand post)', 'OP - Overpit Latrine', '', '2026-02-28 17:47:00', '2026-03-02 09:25:51', NULL, NULL),
(11, 'HH-65423', 168, '973 523 1653', 'Purok 2, Barangay Sample', 'Level II (Communal Faucet system or stand post)', 'P - Pour/Flush toilet connected to septic tank)', '', '2026-03-19 18:52:29', '2026-03-25 09:43:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `household_members`
--

CREATE TABLE `household_members` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL COMMENT 'Foreign key to households table',
  `resident_id` int(11) NOT NULL COMMENT 'Foreign key to residents table',
  `relationship_to_head` varchar(100) DEFAULT NULL COMMENT 'Relationship to household head',
  `is_head` tinyint(1) DEFAULT 0 COMMENT '1 if this member is the household head',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Household members';

--
-- Dumping data for table `household_members`
--

INSERT INTO `household_members` (`id`, `household_id`, `resident_id`, `relationship_to_head`, `is_head`, `created_at`) VALUES
(23, 8, 153, 'Aunt', 0, '2026-02-23 15:28:39'),
(24, 8, 156, 'Uncle', 0, '2026-02-23 15:28:39'),
(25, 8, 155, 'Aunt', 0, '2026-02-23 15:28:39'),
(32, 8, 224, 'Uncle', 0, '2026-03-17 08:33:01'),
(39, 8, 162, 'Grandfather', 0, '2026-03-19 06:58:34'),
(41, 8, 213, 'Uncle', 0, '2026-03-19 07:12:24'),
(47, 7, 219, 'Brother', 0, '2026-03-19 19:24:18'),
(48, 8, 225, 'Grandfather', 0, '2026-03-20 15:30:37'),
(49, 7, 221, 'Father', 0, '2026-03-20 15:36:19'),
(51, 8, 227, 'Aunt', 0, '2026-03-20 16:16:09'),
(53, 8, 161, 'Mother', 0, '2026-03-25 09:09:40'),
(68, 11, 197, 'MOther', 0, '2026-03-25 09:43:22'),
(69, 11, 158, 'Child', 0, '2026-03-25 09:43:22');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `resident_id` varchar(20) DEFAULT NULL COMMENT 'Auto-generated resident ID (Format: W-XXXXX)',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Path to resident photo',
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL COMMENT 'Jr., Sr., III, etc.',
  `sex` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(3) DEFAULT NULL COMMENT 'Calculated from date_of_birth',
  `place_of_birth` varchar(255) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `ethnicity` enum('IPS','Non-IPS','') DEFAULT NULL COMMENT 'Indigenous People Status',
  `mobile_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `house_no` varchar(50) DEFAULT NULL,
  `current_address` text NOT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `street_name` varchar(255) DEFAULT NULL,
  `household_no` varchar(50) DEFAULT NULL,
  `household_contact` varchar(20) DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') NOT NULL,
  `spouse_name` varchar(255) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `number_of_children` int(2) DEFAULT 0,
  `household_head` varchar(255) DEFAULT NULL,
  `educational_attainment` varchar(100) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `monthly_income` varchar(50) DEFAULT NULL,
  `fourps_member` enum('Yes','No') DEFAULT 'No' COMMENT '4Ps Beneficiary',
  `fourps_id` varchar(50) DEFAULT NULL COMMENT '4Ps ID Number',
  `voter_status` enum('Yes','No','') DEFAULT NULL,
  `precinct_number` varchar(50) DEFAULT NULL,
  `pwd_status` enum('Yes','No') DEFAULT 'No' COMMENT 'Person with Disability',
  `senior_citizen` enum('Yes','No') DEFAULT 'No',
  `indigent` enum('Yes','No') DEFAULT 'No',
  `philhealth_id` varchar(50) DEFAULT NULL,
  `membership_type` enum('Member','Dependent','None','') DEFAULT NULL,
  `philhealth_category` varchar(100) DEFAULT NULL,
  `age_health_group` varchar(100) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `lmp_date` date DEFAULT NULL COMMENT 'Last Menstrual Period',
  `using_fp_method` enum('Yes','No','') DEFAULT NULL COMMENT 'Family Planning Method',
  `fp_methods_used` varchar(100) DEFAULT NULL COMMENT 'Family Planning Methods',
  `fp_status` varchar(50) DEFAULT NULL COMMENT 'Family Planning Status',
  `water_source_type` varchar(100) DEFAULT NULL,
  `toilet_facility_type` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending' COMMENT 'Resident verification status',
  `verified_by` int(11) DEFAULT NULL COMMENT 'User ID who verified',
  `verified_at` datetime DEFAULT NULL COMMENT 'Verification timestamp',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason if rejected',
  `activity_status` enum('Alive','Deceased') DEFAULT 'Alive' COMMENT 'Resident activity status',
  `status_changed_at` datetime DEFAULT NULL COMMENT 'When status was last changed',
  `status_changed_by` int(11) DEFAULT NULL COMMENT 'User ID who changed status',
  `status_remarks` text DEFAULT NULL COMMENT 'Remarks about status change',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated',
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main residents information table';

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `resident_id`, `photo`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `date_of_birth`, `age`, `place_of_birth`, `religion`, `ethnicity`, `mobile_number`, `email`, `house_no`, `current_address`, `purok`, `street_name`, `household_no`, `household_contact`, `civil_status`, `spouse_name`, `father_name`, `mother_name`, `number_of_children`, `household_head`, `educational_attainment`, `employment_status`, `occupation`, `monthly_income`, `fourps_member`, `fourps_id`, `voter_status`, `precinct_number`, `pwd_status`, `senior_citizen`, `indigent`, `philhealth_id`, `membership_type`, `philhealth_category`, `age_health_group`, `medical_history`, `lmp_date`, `using_fp_method`, `fp_methods_used`, `fp_status`, `water_source_type`, `toilet_facility_type`, `remarks`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `activity_status`, `status_changed_at`, `status_changed_by`, `status_remarks`, `created_at`, `updated_at`, `created_by`, `updated_by`, `guardian_name`, `guardian_relationship`, `guardian_contact`) VALUES
(13, 'W-00013', NULL, 'Maria', 'Mendoza', 'Santos', NULL, 'Female', '1992-02-18', 34, NULL, 'Christian', 'Non-IPS', '973 523 1625', NULL, NULL, 'Purok 3', '4', NULL, NULL, NULL, 'Married', 'Crisanto Santos', 'Gabriel Bautista', 'Flora Castro', 6, NULL, 'Elementary Level', 'Unemployed', 'House Wife', '5000-10000', 'Yes', '43243232', 'No', NULL, 'No', 'No', 'No', '23134837263', 'Member', 'Direct Contributor', 'Adult (20-59 years)', NULL, '2022-06-23', 'No', NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:10:27', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(14, 'W-00014', NULL, 'Juan Miguel', 'Torres', 'Silva', 'Jr.', 'Male', '1991-08-27', 34, NULL, 'Mormon', 'Non-IPS', '973 514 2152', NULL, NULL, 'Purok 4 Taas', '2', NULL, NULL, NULL, 'Single', NULL, 'Oliver Fernandez', 'Paola Gonzales', 0, NULL, 'College Level', 'Employed', 'Bartender', '5000-10000', 'No', NULL, 'Yes', 'TH-3921', 'No', 'No', 'No', '42341214132', 'Member', 'Direct Contributor', 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:12:50', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(116, 'W-00002', NULL, 'Clara', NULL, 'Santos', NULL, 'Female', '1982-04-22', 43, NULL, NULL, NULL, '973 523 1602', NULL, NULL, 'Purok 1, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(117, 'W-00003', NULL, 'Juan', NULL, 'Santos', NULL, 'Male', '2001-08-10', 24, NULL, NULL, NULL, '973 523 1603', NULL, NULL, 'Purok 1, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(118, 'W-00004', NULL, 'Bianca', NULL, 'Santos', NULL, 'Female', '1995-11-05', 30, NULL, NULL, NULL, '973 523 1604', NULL, NULL, 'Purok 2, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(119, 'W-00005', NULL, 'Alistair', NULL, 'Santos', NULL, 'Male', '1975-02-18', 51, NULL, NULL, NULL, '973 523 1605', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(120, 'W-00006', NULL, 'Diana', NULL, 'Santos', NULL, 'Female', '1978-07-30', 47, NULL, NULL, NULL, '973 523 1606', NULL, NULL, 'Purok 2, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(121, 'W-00007', NULL, 'Crisanto', NULL, 'Santos', NULL, 'Male', '1960-12-12', 65, NULL, NULL, NULL, '973 523 1607', NULL, NULL, 'Purok 3, Barangay Sample', '2', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(122, 'W-00008', NULL, 'Elias', NULL, 'Santos', NULL, 'Male', '1990-03-25', 35, NULL, NULL, NULL, '973 523 1608', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(123, 'W-00009', NULL, 'Flora', NULL, 'Santos', NULL, 'Female', '1992-06-14', 33, NULL, NULL, NULL, '973 523 1609', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(124, 'W-00010', NULL, 'Gabriel', NULL, 'Santos', NULL, 'Male', '1988-09-09', 37, NULL, NULL, NULL, '973 523 1610', NULL, NULL, 'Purok 4, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(125, 'W-00011', NULL, 'Hazel', NULL, 'Reyes', NULL, 'Female', '1989-10-10', 36, NULL, NULL, NULL, '973 523 1611', NULL, NULL, 'Purok 4, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(126, 'W-00012', NULL, 'Inigo', NULL, 'Reyes', NULL, 'Male', '2005-01-20', 21, NULL, NULL, NULL, '973 523 1612', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(127, 'W-00015', NULL, 'Luna', NULL, 'Reyes', NULL, 'Female', '1991-12-01', 34, NULL, NULL, NULL, '973 523 1615', NULL, NULL, 'Purok 5, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(128, 'W-00016', NULL, 'Marco', NULL, 'Reyes', NULL, 'Male', '1970-03-14', 55, NULL, NULL, NULL, '973 523 1616', NULL, NULL, 'Purok 1, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(129, 'W-00017', NULL, 'Nina', NULL, 'Reyes', NULL, 'Female', '1973-06-22', 52, NULL, NULL, NULL, '973 523 1617', NULL, NULL, 'Purok 1, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(130, 'W-00018', NULL, 'Oliver', NULL, 'Reyes', NULL, 'Male', '1993-09-18', 32, NULL, NULL, NULL, '973 523 1618', NULL, NULL, 'Purok 1, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', '2026-03-02 23:57:59', 1, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(131, 'W-00019', NULL, 'Paola', NULL, 'Reyes', NULL, 'Female', '1996-02-28', 30, NULL, NULL, NULL, '973 523 1619', NULL, NULL, 'Purok 2, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(132, 'W-00020', NULL, 'Quintin', NULL, 'Reyes', NULL, 'Male', '1982-11-11', 43, NULL, NULL, NULL, '973 523 1620', NULL, NULL, 'Purok 2, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(133, 'W-00021', NULL, 'Rosa', NULL, 'Cruz', NULL, 'Female', '1984-04-04', 41, NULL, NULL, NULL, '973 523 1621', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(134, 'W-00022', NULL, 'Samuel', NULL, 'Cruz', NULL, 'Male', '2000-07-07', 25, NULL, NULL, NULL, '973 523 1622', NULL, NULL, 'Purok 3, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(135, 'W-00023', NULL, 'Tara', NULL, 'Cruz', NULL, 'Female', '1999-10-15', 26, NULL, NULL, NULL, '973 523 1623', NULL, NULL, 'Purok 3, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(136, 'W-00024', NULL, 'Uriel', NULL, 'Cruz', NULL, 'Male', '1976-01-25', 50, NULL, NULL, NULL, '973 523 1624', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(137, 'W-00025', NULL, 'Vera', NULL, 'Cruz', NULL, 'Female', '1979-05-30', 46, NULL, NULL, NULL, '973 523 1625', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(138, 'W-00026', NULL, 'Waldo', NULL, 'Cruz', NULL, 'Male', '1955-08-20', 70, NULL, NULL, NULL, '973 523 1626', NULL, NULL, 'Purok 4, Barangay Sample', '3', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(139, 'W-00027', NULL, 'Xenia', NULL, 'Cruz', NULL, 'Female', '1994-11-12', 31, NULL, NULL, NULL, '973 523 1627', NULL, NULL, 'Purok 4, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(140, 'W-00028', NULL, 'Ysmael', NULL, 'Cruz', NULL, 'Male', '1987-02-14', 39, NULL, NULL, NULL, '973 523 1628', NULL, NULL, 'Purok 5, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(141, 'W-00029', NULL, 'Zara', NULL, 'Cruz', NULL, 'Female', '1989-06-18', 36, NULL, NULL, NULL, '973 523 1629', NULL, NULL, 'Purok 5, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(142, 'W-00030', NULL, 'Anton', NULL, 'Cruz', NULL, 'Male', '2002-09-22', 23, NULL, NULL, NULL, '973 523 1630', NULL, NULL, 'Purok 5, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(143, 'W-00031', NULL, 'Bella', NULL, 'Mendoza', NULL, 'Female', '2003-12-05', 22, NULL, NULL, NULL, '973 523 1631', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(144, 'W-00032', NULL, 'Carlos', NULL, 'Mendoza', NULL, 'Male', '1965-03-08', 61, NULL, NULL, NULL, '973 523 1632', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(145, 'W-00033', NULL, 'Dalia', NULL, 'Mendoza', NULL, 'Female', '1968-06-16', 57, NULL, NULL, NULL, '973 523 1633', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(146, 'W-00034', NULL, 'Enzo', NULL, 'Mendoza', NULL, 'Male', '1992-09-25', 33, NULL, NULL, NULL, '973 523 1634', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(147, 'W-00035', NULL, 'Freya', NULL, 'Mendoza', NULL, 'Female', '1995-01-30', 31, NULL, NULL, NULL, '973 523 1635', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(148, 'W-00036', NULL, 'Gino', NULL, 'Mendoza', NULL, 'Male', '1981-04-14', 44, NULL, NULL, NULL, '973 523 1636', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(149, 'W-00037', NULL, 'Hanna', NULL, 'Mendoza', NULL, 'Female', '1983-07-28', 42, NULL, NULL, NULL, '973 523 1637', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(150, 'W-00038', NULL, 'Ivan', NULL, 'Mendoza', NULL, 'Male', '2004-10-10', 21, NULL, NULL, NULL, '973 523 1638', NULL, NULL, 'Purok 3, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(151, 'W-00039', NULL, 'Julia', NULL, 'Mendoza', NULL, 'Female', '2001-02-15', 25, NULL, NULL, NULL, '973 523 1639', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(152, 'W-00040', NULL, 'Kenzo', NULL, 'Mendoza', NULL, 'Male', '1972-05-22', 53, NULL, NULL, NULL, '973 523 1640', NULL, NULL, 'Purok 4, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(153, 'W-00041', NULL, 'Liana', NULL, 'Bautista', NULL, 'Female', '1975-08-11', 50, NULL, NULL, NULL, '973 523 1641', NULL, NULL, 'Purok 4, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(154, 'W-00042', NULL, 'Mario', NULL, 'Bautista', NULL, 'Male', '1990-11-19', 35, NULL, NULL, NULL, '973 523 1642', NULL, NULL, 'Purok 4, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(155, 'W-00043', NULL, 'Nyla', NULL, 'Bautista', NULL, 'Female', '1993-02-02', 33, NULL, NULL, NULL, '973 523 1643', NULL, NULL, 'Purok 5, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', '2026-03-03 00:44:45', 1, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(156, 'W-00044', NULL, 'Oscar', NULL, 'Bautista', NULL, 'Male', '1986-06-06', 39, NULL, NULL, NULL, '973 523 1644', NULL, NULL, 'Purok 5, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(157, 'W-00045', NULL, 'Pia', NULL, 'Bautista', NULL, 'Female', '1988-09-14', 37, NULL, NULL, NULL, '973 523 1645', NULL, NULL, 'Purok 5, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(158, 'W-00046', NULL, 'Quico', NULL, 'Bautista', NULL, 'Male', '2006-12-25', 19, NULL, NULL, NULL, '973 523 1646', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adolescent (10-19 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(159, 'W-00047', NULL, 'Rina', NULL, 'Bautista', NULL, 'Female', '2000-03-30', 25, NULL, NULL, NULL, '973 523 1647', NULL, NULL, 'Purok 1, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(160, 'W-00048', NULL, 'Silas', NULL, 'Bautista', NULL, 'Male', '1962-07-07', 63, NULL, NULL, NULL, '973 523 1648', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(161, 'W-00049', NULL, 'Tessa', NULL, 'Bautista', NULL, 'Female', '1997-10-18', 28, NULL, NULL, NULL, '973 523 1649', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(162, 'W-00050', NULL, 'Alejandro', NULL, 'Bautista', NULL, 'Male', '1980-01-21', 46, NULL, NULL, NULL, '973 523 1650', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', '2026-03-03 00:40:50', 1, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(163, 'W-00051', NULL, 'Bea', NULL, 'Aguilar', NULL, 'Female', '1982-04-12', 43, NULL, NULL, NULL, '973 523 1651', NULL, NULL, 'Purok 2, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Deceased', '2026-03-20 23:31:56', 1, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(165, 'W-00053', NULL, 'Danica', 'Gonzales', 'Aguilar', NULL, 'Male', '1998-04-24', 27, 'San Felipe', 'Pentecostal', 'IPS', '973 523 1653', NULL, NULL, 'Purok 2, Barangay Sample', '1', 'Barangay Sample', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'No Formal Education', NULL, NULL, NULL, 'No', NULL, 'Yes', NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Deceased', '2026-03-25 18:06:10', 1, NULL, '2026-02-23 15:23:12', '2026-03-25 10:06:10', NULL, NULL, NULL, NULL, NULL),
(166, 'W-00054', NULL, 'Edgar', NULL, 'Aguilar', NULL, 'Male', '1974-01-16', 52, NULL, NULL, NULL, '973 523 1654', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Deceased', '2026-03-25 18:06:01', 1, NULL, '2026-02-23 15:23:12', '2026-03-25 10:06:01', NULL, NULL, NULL, NULL, NULL),
(167, 'W-00055', NULL, 'Fiona', NULL, 'Aguilar', NULL, 'Female', '1977-05-28', 48, NULL, NULL, NULL, '973 523 1655', NULL, NULL, 'Purok 4, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(168, 'W-00056', NULL, 'Gerardo', NULL, 'Aguilar', NULL, 'Male', '2002-08-08', 23, NULL, NULL, NULL, '973 523 1656', NULL, NULL, 'Purok 4, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(169, 'W-00057', NULL, 'Hilda', NULL, 'Aguilar', NULL, 'Female', '2005-11-19', 20, NULL, NULL, NULL, '973 523 1657', NULL, NULL, 'Purok 4, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', '2026-03-03 00:02:44', 1, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(170, 'W-00058', NULL, 'Ian', NULL, 'Aguilar', NULL, 'Male', '1985-02-22', 41, NULL, NULL, NULL, '973 523 1658', NULL, NULL, 'Purok 5, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Deceased', '2026-03-25 17:13:00', 1, NULL, '2026-02-23 15:23:12', '2026-03-25 09:13:00', NULL, NULL, NULL, NULL, NULL),
(171, 'W-00059', NULL, 'Jessa', NULL, 'Aguilar', NULL, 'Female', '1987-06-03', 38, NULL, NULL, NULL, '973 523 1659', NULL, NULL, 'Purok 5, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(172, 'W-00060', NULL, 'Kiko', NULL, 'Aguilar', NULL, 'Male', '1991-09-14', 34, NULL, NULL, NULL, '973 523 1660', NULL, NULL, 'Purok 5, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(173, 'W-00061', NULL, 'Lani', NULL, 'Silva', NULL, 'Female', '1993-12-25', 32, NULL, NULL, NULL, '973 523 1661', NULL, NULL, 'Purok 1, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(174, 'W-00062', NULL, 'Miko', NULL, 'Silva', NULL, 'Male', '1968-04-06', 57, NULL, NULL, NULL, '973 523 1662', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(175, 'W-00063', NULL, 'Nadia', NULL, 'Silva', NULL, 'Female', '1971-07-17', 54, NULL, NULL, NULL, '973 523 1663', NULL, NULL, 'Purok 1, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(176, 'W-00064', NULL, 'Orlan', NULL, 'Silva', NULL, 'Male', '1998-10-28', 27, NULL, NULL, NULL, '973 523 1664', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(177, 'W-00065', NULL, 'Penny', NULL, 'Silva', NULL, 'Female', '2001-01-08', 25, NULL, NULL, NULL, '973 523 1665', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(178, 'W-00066', NULL, 'Ramon', NULL, 'Silva', NULL, 'Male', '1983-05-20', 42, NULL, NULL, NULL, '973 523 1666', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(179, 'W-00067', NULL, 'Sonia', NULL, 'Silva', NULL, 'Female', '1986-08-31', 39, NULL, NULL, NULL, '973 523 1667', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(180, 'W-00068', NULL, 'Timo', NULL, 'Silva', NULL, 'Male', '1959-12-11', 66, NULL, NULL, NULL, '973 523 1668', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(181, 'W-00069', NULL, 'Ursula', NULL, 'Silva', NULL, 'Female', '1995-03-24', 30, NULL, NULL, NULL, '973 523 1669', NULL, NULL, 'Purok 3, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(182, 'W-00070', NULL, 'Vico', NULL, 'Silva', NULL, 'Male', '1997-06-04', 28, NULL, NULL, NULL, '973 523 1670', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(183, 'W-00071', NULL, 'Wendy', NULL, 'Perez', NULL, 'Female', '1976-09-15', 49, NULL, NULL, NULL, '973 523 1671', NULL, NULL, 'Purok 4, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(184, 'W-00072', NULL, 'Xander', NULL, 'Perez', NULL, 'Male', '1979-12-26', 46, NULL, NULL, NULL, '973 523 1672', NULL, NULL, 'Purok 4, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(185, 'W-00073', NULL, 'Yana', NULL, 'Perez', NULL, 'Female', '2004-04-07', 21, NULL, NULL, NULL, '973 523 1673', NULL, NULL, 'Purok 5, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(186, 'W-00074', NULL, 'Zandro', NULL, 'Perez', NULL, 'Male', '2007-07-18', 18, NULL, NULL, NULL, '973 523 1674', NULL, NULL, 'Purok 5, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adolescent (10-19 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(187, 'W-00075', NULL, 'Arthur', NULL, 'Perez', NULL, 'Male', '1981-10-29', 44, NULL, NULL, NULL, '973 523 1675', NULL, NULL, 'Purok 5, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(188, 'W-00076', NULL, 'Brenda', NULL, 'Perez', NULL, 'Female', '1984-01-09', 42, NULL, NULL, NULL, '973 523 1676', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(189, 'W-00077', NULL, 'Carlo', NULL, 'Perez', NULL, 'Male', '1990-05-22', 35, NULL, NULL, NULL, '973 523 1677', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(190, 'W-00078', NULL, 'Daisy', NULL, 'Perez', NULL, 'Female', '1992-08-02', 33, NULL, NULL, NULL, '973 523 1678', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(191, 'W-00079', NULL, 'Emil', NULL, 'Perez', NULL, 'Male', '1967-11-13', 58, NULL, NULL, NULL, '973 523 1679', NULL, NULL, 'Purok 2, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(192, 'W-00080', NULL, 'Flora', NULL, 'Perez', NULL, 'Female', '1970-02-23', 56, NULL, NULL, NULL, '973 523 1680', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(193, 'W-00081', NULL, 'Gardo', NULL, 'Castro', NULL, 'Male', '1999-06-06', 26, NULL, NULL, NULL, '973 523 1681', NULL, NULL, 'Purok 2, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(194, 'W-00082', NULL, 'Helen', NULL, 'Castro', NULL, 'Female', '2002-09-17', 23, NULL, NULL, NULL, '973 523 1682', NULL, NULL, 'Purok 3, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(195, 'W-00083', NULL, 'Ismael', NULL, 'Castro', NULL, 'Male', '1988-12-28', 37, NULL, NULL, NULL, '973 523 1683', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(196, 'W-00084', NULL, 'Jenny', NULL, 'Castro', NULL, 'Female', '1991-03-10', 35, NULL, NULL, NULL, '973 523 1684', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(197, 'W-00085', NULL, 'Kiko', NULL, 'Castro', NULL, 'Male', '1973-06-21', 52, NULL, NULL, NULL, '973 523 1685', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Separated', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(198, 'W-00086', NULL, 'Lea', NULL, 'Castro', NULL, 'Female', '1975-09-01', 50, NULL, NULL, NULL, '973 523 1686', NULL, NULL, 'Purok 4, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(199, 'W-00087', NULL, 'Manny', NULL, 'Castro', NULL, 'Male', '1958-12-12', 67, NULL, NULL, NULL, '973 523 1687', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(200, 'W-00088', NULL, 'Nora', NULL, 'Castro', NULL, 'Female', '1994-03-25', 31, NULL, NULL, NULL, '973 523 1688', NULL, NULL, 'Purok 5, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(201, 'W-00089', NULL, 'Oliver', NULL, 'Castro', NULL, 'Male', '1996-07-06', 29, NULL, NULL, NULL, '973 523 1689', NULL, NULL, 'Purok 5, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(202, 'W-00090', NULL, 'Paolo', NULL, 'Castro', NULL, 'Male', '1982-10-17', 43, NULL, NULL, NULL, '973 523 1690', NULL, NULL, 'Purok 5, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(203, 'W-00091', NULL, 'Quintin', NULL, 'Domingo', NULL, 'Male', '1985-01-27', 41, NULL, NULL, NULL, '973 523 1691', NULL, NULL, 'Purok 1, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(204, 'W-00092', NULL, 'Romy', NULL, 'Domingo', NULL, 'Male', '1964-05-09', 61, NULL, NULL, NULL, '973 523 1692', NULL, NULL, 'Purok 1, Barangay Sample', '4', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Senior Citizen (60+ years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(205, 'W-00093', NULL, 'Sarah', NULL, 'Domingo', NULL, 'Female', '1966-08-20', 59, NULL, NULL, NULL, '973 523 1693', NULL, NULL, 'Purok 1, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(206, 'W-00094', NULL, 'Tomas', NULL, 'Domingo', NULL, 'Male', '1990-11-30', 35, NULL, NULL, NULL, '973 523 1694', NULL, NULL, 'Purok 2, Barangay Sample', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(207, 'W-00095', NULL, 'Ulysses', NULL, 'Domingo', NULL, 'Male', '1993-03-13', 32, NULL, NULL, NULL, '973 523 1695', NULL, NULL, 'Purok 2, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(208, 'W-00096', NULL, 'Victor', NULL, 'Domingo', NULL, 'Male', '1978-06-24', 47, NULL, NULL, NULL, '973 523 1696', NULL, NULL, 'Purok 2, Barangay Sample', '3', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(209, 'W-00097', NULL, 'Waldo', NULL, 'Domingo', NULL, 'Male', '1980-09-04', 45, NULL, NULL, NULL, '973 523 1697', NULL, NULL, 'Purok 3, Barangay Sample', '2', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(210, 'W-00098', NULL, 'Xyriel', NULL, 'Domingo', NULL, 'Female', '2001-12-15', 24, NULL, NULL, NULL, '973 523 1698', NULL, NULL, 'Purok 3, Barangay Sample', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` (`id`, `resident_id`, `photo`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `date_of_birth`, `age`, `place_of_birth`, `religion`, `ethnicity`, `mobile_number`, `email`, `house_no`, `current_address`, `purok`, `street_name`, `household_no`, `household_contact`, `civil_status`, `spouse_name`, `father_name`, `mother_name`, `number_of_children`, `household_head`, `educational_attainment`, `employment_status`, `occupation`, `monthly_income`, `fourps_member`, `fourps_id`, `voter_status`, `precinct_number`, `pwd_status`, `senior_citizen`, `indigent`, `philhealth_id`, `membership_type`, `philhealth_category`, `age_health_group`, `medical_history`, `lmp_date`, `using_fp_method`, `fp_methods_used`, `fp_status`, `water_source_type`, `toilet_facility_type`, `remarks`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `activity_status`, `status_changed_at`, `status_changed_by`, `status_remarks`, `created_at`, `updated_at`, `created_by`, `updated_by`, `guardian_name`, `guardian_relationship`, `guardian_contact`) VALUES
(211, 'W-00099', NULL, 'Yassi', NULL, 'Domingo', NULL, 'Female', '2003-03-27', 22, NULL, NULL, NULL, '973 523 1699', NULL, NULL, 'Purok 3, Barangay Sample', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(212, 'W-00100', NULL, 'Zanjoe', NULL, 'Domingo', NULL, 'Male', '1987-07-08', 38, NULL, NULL, NULL, '973 523 1700', NULL, NULL, 'Purok 4, Barangay Sample', '1', NULL, NULL, NULL, 'Married', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-23 15:23:12', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(213, 'W-00213', NULL, 'Juan Miguela', 'Torres', 'Silvaaaaa', 'Jr.', 'Male', '1991-08-27', 34, NULL, 'Roman Catholic', 'Non-IPS', '973 514 2153', 'jriveraljoriza@gmail.com', NULL, 'House No. 219, Purok 2, Street 621', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'No Formal Education', 'Employed', '2WDW', 'Below 5000', 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-27 17:32:37', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(215, 'W-00215', NULL, 'Juan', 'Torr', 'Silva', 'Jr.', 'Male', '1991-08-27', 34, NULL, 'Christian', 'Non-IPS', '973 514 2158', 'jriveraljorizaa@gmail.com', NULL, 'House No. 219, Purok 2, Street 621', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'Elementary Level', 'Employed', '2WDW', '5000-10000', 'No', NULL, 'No', NULL, 'No', 'No', 'No', '534534534534', 'Member', 'Indirect Contributor', 'Adult (20-59 years)', 'ASADAW', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-28 17:47:00', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(217, 'W-00217', NULL, 'Silvaaaaa', NULL, 'Silvaaaaa', NULL, 'Male', '2026-03-16', 0, NULL, 'Pentecostal', 'IPS', '973 514 2112', NULL, NULL, 'Purok 2, Street 621', '2', NULL, NULL, NULL, 'Single', 'Crisanto Santos', 'Ramo', 'Crist', 0, NULL, 'No Formal Education', 'Unemployed', NULL, '5000-10000', 'Yes', '12-3123-2123', NULL, NULL, 'No', 'No', 'No', '2312-3231-2312', 'Member', 'Direct Contributor', 'Newborn (0-28 days)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-02-28 18:43:37', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(218, 'W-00218', NULL, 'Madwen', NULL, 'Santiagie', NULL, 'Male', '2026-03-16', 0, NULL, 'LDS-Mormons', 'Non-IPS', '972 351 5232', NULL, NULL, 'Purok 1, Mabayuan', '2', NULL, NULL, NULL, 'Single', 'Crisanto Santos', 'Ramo', 'Crist', 0, NULL, 'No Formal Education', 'Employed', '2WDW', NULL, 'Yes', '12-3123-2123', 'No', NULL, 'No', 'No', 'No', '2318-3626-1232', 'Dependent', 'Direct Contributor', 'Infant (29 days - 1 year)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', '2026-03-01 20:35:15', 1, NULL, '2026-03-01 12:14:47', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(219, 'W-00219', NULL, 'Madqos', NULL, 'Santiaa', NULL, 'Male', '2026-03-02', 0, NULL, 'Protestant', 'IPS', '972 351 5212', NULL, NULL, 'Purok 2, Mabayuan', '2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'High School Graduate', 'Employed', '2WDW', NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Newborn (0-28 days)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-02 06:19:07', '2026-03-20 15:28:37', NULL, NULL, NULL, NULL, NULL),
(220, 'W-00220', NULL, 'Madwa', NULL, 'Santiagiew', NULL, 'Male', '2003-08-12', 22, NULL, 'Church of God', 'IPS', '972 351 5342', NULL, NULL, 'Purok 1, Street 62121', '1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-02 08:45:42', '2026-03-20 15:28:37', NULL, NULL, NULL, NULL, NULL),
(221, 'W-00221', NULL, 'Jaymhon', NULL, 'Joriza', NULL, 'Male', '2003-07-22', 22, NULL, 'Roman Catholic', 'Non-IPS', '931 191 3121', 'mamondog0@gmail.com', NULL, 'Purok 2, 343242', '4', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'No Formal Education', 'Employed', 'Student', NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', '2026-03-15 22:33:16', 1, NULL, '2026-03-02 11:49:32', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(222, 'W-00222', NULL, 'Marwen', NULL, 'Dela Cruz', NULL, 'Male', '2000-01-01', 26, NULL, 'Evangelical', 'IPS', '927 361 6236', '09692030757', '21232', 'House No. 21232, Purok 3, Street 312', '1', 'Street 312', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'Yes', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-02 17:09:47', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(223, 'W-00223', NULL, 'Marwenniga', NULL, 'santiago', NULL, 'Male', '2000-01-01', 26, NULL, 'Evangelical', 'IPS', '927 361 6236', NULL, NULL, 'Purok 2, Street 312', '2', 'Street 312', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-02 17:45:05', '2026-03-20 15:28:37', NULL, NULL, NULL, NULL, NULL),
(224, 'W-00224', NULL, 'Jaymhon', 'Gonzales', 'Joriza', NULL, 'Male', '2014-01-09', 12, NULL, 'Born Again', 'IPS', '093 119 1312', 'mamondog0@gmail.com', NULL, 'Purok 1, 343242', '4', '343242', NULL, NULL, 'Single', NULL, 'Ramo', 'Crist', 0, NULL, 'Elementary Level', 'Student', NULL, NULL, 'No', NULL, 'No', NULL, 'Yes', 'No', 'No', NULL, NULL, NULL, 'Adolescent (10-19 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-17 08:33:01', '2026-03-21 16:07:14', NULL, NULL, 'Argel Gonzales Tayson', 'Father', '093 119 1312'),
(225, 'W-00225', NULL, 'Zack', 'Gonzales', 'Tabudlo', NULL, 'Male', '2000-03-09', 26, 'San Marcelino', 'Buddhism', 'Non-IPS', '917 823 6152', NULL, NULL, 'Purok 1, Dyan sa Tabi', '2', 'Dyan sa Tabi', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'Elementary Level', 'Student', 'Student', NULL, 'No', NULL, 'No', NULL, 'No', 'No', 'No', NULL, 'Member', 'Indirect Contributor', 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-20 15:30:37', '2026-03-21 16:07:14', NULL, NULL, NULL, NULL, NULL),
(226, 'W-00226', NULL, 'Juan', NULL, 'Dela Cruz', NULL, 'Male', '2000-01-01', 26, 'San Marcelino', 'Christian', 'Non-IPS', '917 823 6154', NULL, NULL, 'Purok 3, Taas Tulay', '3', 'Taas Tulay', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'College Graduate', 'Employed', 'Student', NULL, 'No', NULL, 'No', NULL, 'No', 'No', 'No', NULL, 'None', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-20 16:10:16', '2026-03-20 16:10:16', NULL, NULL, NULL, NULL, NULL),
(227, 'W-00227', NULL, 'Juan', NULL, 'Dela Cruz', 'Jr.', 'Male', '2016-07-14', 9, 'San Marcelino', 'Roman Catholic', 'Non-IPS', '912 736 1265', NULL, NULL, 'Purok 3, Taas Tulay', '2', 'Taas Tulay', NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'Elementary Level', NULL, NULL, NULL, 'No', NULL, 'No', NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Child (1-9 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Alive', NULL, NULL, NULL, '2026-03-20 16:16:09', '2026-03-21 16:07:14', NULL, NULL, 'Juan Dela Cruz', 'Father', '981 632 5612'),
(228, 'W-00052', NULL, 'Cedric', NULL, 'Aguilar', NULL, 'Male', '1994-07-24', 31, NULL, NULL, NULL, '973 523 1652', NULL, NULL, 'Purok 3, Barangay Sample', '3', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, 'No', 'No', 'No', NULL, NULL, NULL, 'Adult (20-59 years)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, NULL, NULL, 'Deceased', '2026-03-25 18:05:49', 1, NULL, '2026-03-25 09:18:08', '2026-03-25 10:05:49', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#e5e7eb',
  `text_color` varchar(20) DEFAULT '#374151',
  `permissions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `color`, `text_color`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'Full system access and management', '#fef3c7', '#92400e', '{\"perm_office_create\":true,\"perm_office_view\":true,\"perm_office_edit\":true,\"perm_roles_create\":true,\"perm_roles_view\":true,\"perm_roles_edit\":true,\"perm_roles_delete\":true,\"perm_resident_create\":false,\"perm_resident_view\":true,\"perm_resident_edit\":true,\"perm_resident_delete\":true,\"perm_household_create\":false,\"perm_household_view\":true,\"perm_household_edit\":true,\"perm_household_delete\":true,\"perm_officials_create\":false,\"perm_officials_view\":true,\"perm_officials_edit\":true,\"perm_officials_delete\":true}', '2026-02-23 09:34:05', '2026-02-23 09:51:05'),
(5, 'Viewer', 'Read-only access to the system', '#f3f4f6', '#374151', '{\"perm_office_create\":false,\"perm_office_view\":false,\"perm_office_edit\":false,\"perm_roles_create\":false,\"perm_roles_view\":false,\"perm_roles_edit\":false,\"perm_roles_delete\":false,\"perm_resident_create\":false,\"perm_resident_view\":true,\"perm_resident_edit\":false,\"perm_resident_delete\":false,\"perm_household_create\":false,\"perm_household_view\":true,\"perm_household_edit\":false,\"perm_household_delete\":false,\"perm_officials_create\":false,\"perm_officials_view\":true,\"perm_officials_edit\":false,\"perm_officials_delete\":false}', '2026-02-23 09:34:05', '2026-02-23 09:49:06'),
(16, 'View', 'views only', '#fee2e2', '#991b1b', '{\"perm_office_create\":false,\"perm_office_view\":false,\"perm_office_edit\":false,\"perm_office_delete\":false,\"perm_roles_create\":false,\"perm_roles_view\":false,\"perm_roles_edit\":false,\"perm_roles_delete\":false,\"perm_resident_create\":false,\"perm_resident_view\":true,\"perm_resident_edit\":false,\"perm_resident_delete\":false,\"perm_household_create\":false,\"perm_household_view\":false,\"perm_household_edit\":false,\"perm_household_delete\":false,\"perm_officials_create\":true,\"perm_officials_view\":true,\"perm_officials_edit\":true,\"perm_officials_delete\":true}', '2026-02-23 09:45:29', '2026-02-23 11:26:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hashed password',
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Path to user profile image/avatar',
  `role` varchar(100) NOT NULL DEFAULT 'Staff',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System users';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `profile_image`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@barangay.local', 'assets/uploads/avatars/avatar_1_1774428153.png', 'Admin', 'Active', '2026-02-12 15:55:42', '2026-03-25 08:42:33'),
(2, 'jaeyzzzz', '$2y$10$PDyAqESMrf45b/60yW6/Y..FlAn5tIBL.jhKRQp03wIogDTIEzXLq', 'Jaeysz', 'mamondog0@gmail.com', NULL, '', 'Inactive', '2026-02-23 09:46:24', '2026-03-21 16:30:17'),
(4, 'mamon', '$2y$10$NSxg682TCFw0h3e1hsgNbOOl1zDVfycnTV8AebXxentZuomeCrZ5q', 'Jaeysz', 'mamond@gmail.com', NULL, 'Administrator', 'Active', '2026-02-23 10:22:59', '2026-03-05 08:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `created_at`) VALUES
(1, 1, 1, '2026-02-23 09:34:05'),
(9, 2, 16, '2026-02-23 10:14:29'),
(14, 4, 1, '2026-03-05 08:02:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_blotter_complete`
-- (See below for the actual view)
--
CREATE TABLE `vw_blotter_complete` (
`id` int(11)
,`record_number` varchar(50)
,`incident_type` varchar(255)
,`incident_description` text
,`incident_date` datetime
,`incident_location` text
,`date_reported` datetime
,`status` enum('Pending','Under Investigation','Resolved','Dismissed')
,`resolution` text
,`resolved_date` datetime
,`remarks` text
,`created_at` timestamp
,`updated_at` timestamp
,`complainant_count` bigint(21)
,`respondent_count` bigint(21)
,`complainant_names` mediumtext
,`respondent_names` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_blotter_statistics`
-- (See below for the actual view)
--
CREATE TABLE `vw_blotter_statistics` (
`total_records` bigint(21)
,`pending_count` decimal(22,0)
,`under_investigation_count` decimal(22,0)
,`resolved_count` decimal(22,0)
,`dismissed_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pending_residents`
-- (See below for the actual view)
--
CREATE TABLE `vw_pending_residents` (
`id` int(11)
,`full_name` varchar(201)
,`mobile_number` varchar(20)
,`current_address` text
,`verification_status` enum('Pending','Verified','Rejected')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_residents_complete`
-- (See below for the actual view)
--
CREATE TABLE `vw_residents_complete` (
`id` int(11)
,`photo` varchar(255)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`suffix` varchar(20)
,`sex` enum('Male','Female','Other')
,`date_of_birth` date
,`age` int(3)
,`place_of_birth` varchar(255)
,`religion` varchar(100)
,`ethnicity` enum('IPS','Non-IPS','')
,`mobile_number` varchar(20)
,`email` varchar(100)
,`current_address` text
,`household_no` varchar(50)
,`household_contact` varchar(20)
,`purok` varchar(100)
,`civil_status` enum('Single','Married','Widowed','Separated','Divorced')
,`spouse_name` varchar(255)
,`father_name` varchar(255)
,`mother_name` varchar(255)
,`number_of_children` int(2)
,`household_head` varchar(255)
,`educational_attainment` varchar(100)
,`employment_status` varchar(50)
,`occupation` varchar(100)
,`monthly_income` varchar(50)
,`fourps_member` enum('Yes','No')
,`fourps_id` varchar(50)
,`voter_status` enum('Yes','No','')
,`precinct_number` varchar(50)
,`pwd_status` enum('Yes','No')
,`senior_citizen` enum('Yes','No')
,`indigent` enum('Yes','No')
,`philhealth_id` varchar(50)
,`membership_type` enum('Member','Dependent','None','')
,`philhealth_category` varchar(100)
,`age_health_group` varchar(100)
,`medical_history` text
,`lmp_date` date
,`using_fp_method` enum('Yes','No','')
,`fp_methods_used` varchar(100)
,`fp_status` varchar(50)
,`water_source_type` varchar(100)
,`toilet_facility_type` varchar(100)
,`remarks` text
,`verification_status` enum('Pending','Verified','Rejected')
,`verified_by` int(11)
,`verified_at` datetime
,`rejection_reason` text
,`activity_status` enum('Alive','Deceased')
,`status_changed_at` datetime
,`status_changed_by` int(11)
,`status_remarks` text
,`created_at` timestamp
,`updated_at` timestamp
,`created_by` int(11)
,`updated_by` int(11)
,`full_name` varchar(323)
,`calculated_age` bigint(21)
,`emergency_contacts_list` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_resident_statistics`
-- (See below for the actual view)
--
CREATE TABLE `vw_resident_statistics` (
`total_residents` bigint(21)
,`verified_residents` decimal(22,0)
,`pending_residents` decimal(22,0)
,`rejected_residents` decimal(22,0)
,`male_count` decimal(22,0)
,`female_count` decimal(22,0)
,`fourps_members` decimal(22,0)
,`senior_citizens` decimal(22,0)
,`pwd_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_blotter_complete`
--
DROP TABLE IF EXISTS `vw_blotter_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_blotter_complete`  AS SELECT `br`.`id` AS `id`, `br`.`record_number` AS `record_number`, `br`.`incident_type` AS `incident_type`, `br`.`incident_description` AS `incident_description`, `br`.`incident_date` AS `incident_date`, `br`.`incident_location` AS `incident_location`, `br`.`date_reported` AS `date_reported`, `br`.`status` AS `status`, `br`.`resolution` AS `resolution`, `br`.`resolved_date` AS `resolved_date`, `br`.`remarks` AS `remarks`, `br`.`created_at` AS `created_at`, `br`.`updated_at` AS `updated_at`, count(distinct `bc`.`id`) AS `complainant_count`, count(distinct `brd`.`id`) AS `respondent_count`, group_concat(distinct `bc`.`name` order by `bc`.`id` ASC separator ', ') AS `complainant_names`, group_concat(distinct `brd`.`name` order by `brd`.`id` ASC separator ', ') AS `respondent_names` FROM ((`blotter_records` `br` left join `blotter_complainants` `bc` on(`br`.`id` = `bc`.`blotter_id`)) left join `blotter_respondents` `brd` on(`br`.`id` = `brd`.`blotter_id`)) GROUP BY `br`.`id` ORDER BY `br`.`date_reported` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_blotter_statistics`
--
DROP TABLE IF EXISTS `vw_blotter_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_blotter_statistics`  AS SELECT count(0) AS `total_records`, sum(case when `blotter_records`.`status` = 'Pending' then 1 else 0 end) AS `pending_count`, sum(case when `blotter_records`.`status` = 'Under Investigation' then 1 else 0 end) AS `under_investigation_count`, sum(case when `blotter_records`.`status` = 'Resolved' then 1 else 0 end) AS `resolved_count`, sum(case when `blotter_records`.`status` = 'Dismissed' then 1 else 0 end) AS `dismissed_count` FROM `blotter_records` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_pending_residents`
--
DROP TABLE IF EXISTS `vw_pending_residents`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pending_residents`  AS SELECT `r`.`id` AS `id`, concat(`r`.`first_name`,' ',`r`.`last_name`) AS `full_name`, `r`.`mobile_number` AS `mobile_number`, `r`.`current_address` AS `current_address`, `r`.`verification_status` AS `verification_status`, `r`.`created_at` AS `created_at` FROM `residents` AS `r` WHERE `r`.`verification_status` = 'Pending' ORDER BY `r`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_residents_complete`
--
DROP TABLE IF EXISTS `vw_residents_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_residents_complete`  AS SELECT `r`.`id` AS `id`, `r`.`photo` AS `photo`, `r`.`first_name` AS `first_name`, `r`.`middle_name` AS `middle_name`, `r`.`last_name` AS `last_name`, `r`.`suffix` AS `suffix`, `r`.`sex` AS `sex`, `r`.`date_of_birth` AS `date_of_birth`, `r`.`age` AS `age`, `r`.`place_of_birth` AS `place_of_birth`, `r`.`religion` AS `religion`, `r`.`ethnicity` AS `ethnicity`, `r`.`mobile_number` AS `mobile_number`, `r`.`email` AS `email`, `r`.`current_address` AS `current_address`, `r`.`household_no` AS `household_no`, `r`.`household_contact` AS `household_contact`, `r`.`purok` AS `purok`, `r`.`civil_status` AS `civil_status`, `r`.`spouse_name` AS `spouse_name`, `r`.`father_name` AS `father_name`, `r`.`mother_name` AS `mother_name`, `r`.`number_of_children` AS `number_of_children`, `r`.`household_head` AS `household_head`, `r`.`educational_attainment` AS `educational_attainment`, `r`.`employment_status` AS `employment_status`, `r`.`occupation` AS `occupation`, `r`.`monthly_income` AS `monthly_income`, `r`.`fourps_member` AS `fourps_member`, `r`.`fourps_id` AS `fourps_id`, `r`.`voter_status` AS `voter_status`, `r`.`precinct_number` AS `precinct_number`, `r`.`pwd_status` AS `pwd_status`, `r`.`senior_citizen` AS `senior_citizen`, `r`.`indigent` AS `indigent`, `r`.`philhealth_id` AS `philhealth_id`, `r`.`membership_type` AS `membership_type`, `r`.`philhealth_category` AS `philhealth_category`, `r`.`age_health_group` AS `age_health_group`, `r`.`medical_history` AS `medical_history`, `r`.`lmp_date` AS `lmp_date`, `r`.`using_fp_method` AS `using_fp_method`, `r`.`fp_methods_used` AS `fp_methods_used`, `r`.`fp_status` AS `fp_status`, `r`.`water_source_type` AS `water_source_type`, `r`.`toilet_facility_type` AS `toilet_facility_type`, `r`.`remarks` AS `remarks`, `r`.`verification_status` AS `verification_status`, `r`.`verified_by` AS `verified_by`, `r`.`verified_at` AS `verified_at`, `r`.`rejection_reason` AS `rejection_reason`, `r`.`activity_status` AS `activity_status`, `r`.`status_changed_at` AS `status_changed_at`, `r`.`status_changed_by` AS `status_changed_by`, `r`.`status_remarks` AS `status_remarks`, `r`.`created_at` AS `created_at`, `r`.`updated_at` AS `updated_at`, `r`.`created_by` AS `created_by`, `r`.`updated_by` AS `updated_by`, concat(`r`.`first_name`,' ',ifnull(`r`.`middle_name`,''),' ',`r`.`last_name`,' ',ifnull(`r`.`suffix`,'')) AS `full_name`, timestampdiff(YEAR,`r`.`date_of_birth`,curdate()) AS `calculated_age`, group_concat(concat(`ec`.`contact_name`,' (',`ec`.`relationship`,'): ',`ec`.`contact_number`) separator '; ') AS `emergency_contacts_list` FROM (`residents` `r` left join `emergency_contacts` `ec` on(`r`.`id` = `ec`.`resident_id`)) GROUP BY `r`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_resident_statistics`
--
DROP TABLE IF EXISTS `vw_resident_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_resident_statistics`  AS SELECT count(0) AS `total_residents`, sum(case when `residents`.`verification_status` = 'Verified' then 1 else 0 end) AS `verified_residents`, sum(case when `residents`.`verification_status` = 'Pending' then 1 else 0 end) AS `pending_residents`, sum(case when `residents`.`verification_status` = 'Rejected' then 1 else 0 end) AS `rejected_residents`, sum(case when `residents`.`sex` = 'Male' then 1 else 0 end) AS `male_count`, sum(case when `residents`.`sex` = 'Female' then 1 else 0 end) AS `female_count`, sum(case when `residents`.`fourps_member` = 'Yes' then 1 else 0 end) AS `fourps_members`, sum(case when `residents`.`senior_citizen` = 'Yes' then 1 else 0 end) AS `senior_citizens`, sum(case when `residents`.`pwd_status` = 'Yes' then 1 else 0 end) AS `pwd_count` FROM `residents` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive`
--
ALTER TABLE `archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_type` (`archive_type`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `barangay_info`
--
ALTER TABLE `barangay_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barangay_info_user` (`updated_by`);

--
-- Indexes for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_term_dates` (`term_start`,`term_end`),
  ADD KEY `idx_hierarchy_level` (`hierarchy_level`);

--
-- Indexes for table `blotter_complainants`
--
ALTER TABLE `blotter_complainants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blotter_id` (`blotter_id`),
  ADD KEY `idx_resident_id` (`resident_id`);

--
-- Indexes for table `blotter_records`
--
ALTER TABLE `blotter_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_number` (`record_number`),
  ADD UNIQUE KEY `unique_record_number` (`record_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_date_reported` (`date_reported`),
  ADD KEY `idx_incident_type` (`incident_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `blotter_respondents`
--
ALTER TABLE `blotter_respondents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blotter_id` (`blotter_id`),
  ADD KEY `idx_resident_id` (`resident_id`);

--
-- Indexes for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `certificate_id` (`certificate_name`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `household_number` (`household_number`),
  ADD KEY `idx_household_number` (`household_number`),
  ADD KEY `idx_household_head_id` (`household_head_id`);

--
-- Indexes for table `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_household_resident` (`household_id`,`resident_id`),
  ADD KEY `idx_household_id` (`household_id`),
  ADD KEY `idx_resident_id` (`resident_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resident_id` (`resident_id`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_first_name` (`first_name`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_household_no` (`household_no`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_profile_image` (`profile_image`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `archive`
--
ALTER TABLE `archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `blotter_complainants`
--
ALTER TABLE `blotter_complainants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `blotter_records`
--
ALTER TABLE `blotter_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `blotter_respondents`
--
ALTER TABLE `blotter_respondents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  ADD CONSTRAINT `fk_official_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `blotter_complainants`
--
ALTER TABLE `blotter_complainants`
  ADD CONSTRAINT `fk_complainant_blotter` FOREIGN KEY (`blotter_id`) REFERENCES `blotter_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_complainant_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `blotter_respondents`
--
ALTER TABLE `blotter_respondents`
  ADD CONSTRAINT `fk_respondent_blotter` FOREIGN KEY (`blotter_id`) REFERENCES `blotter_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_respondent_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD CONSTRAINT `fk_cert_req_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `fk_household_head` FOREIGN KEY (`household_head_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hm_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
