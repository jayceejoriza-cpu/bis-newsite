-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 12:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Certificate title/name',
  `description` text DEFAULT NULL COMMENT 'Certificate description',
  `fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Certificate fee amount',
  `status` enum('Published','Unpublished') DEFAULT 'Unpublished' COMMENT 'Publication status',
  `template_content` text DEFAULT NULL COMMENT 'Certificate template HTML/content',
  `fields` text DEFAULT NULL COMMENT 'JSON of customizable fields',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate templates';

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `title`, `description`, `fee`, `status`, `template_content`, `fields`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Barangay Clearance', 'Certificate of Barangay Clearance for various purposes', 50.00, 'Published', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(2, 'Certificate of Residency', 'Proof of residency in the barangay', 150.00, 'Published', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(3, 'Certificate of Indigency', 'Certificate for indigent residents', 0.00, 'Published', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(4, 'Business Permit', 'Barangay business permit certificate', 255.00, 'Unpublished', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(5, 'Good Moral Certificate', 'Certificate of good moral character', 100.00, 'Published', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(6, 'Certificate of Employment', 'Employment verification certificate', 200.00, 'Published', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `certificate_requests`
--

CREATE TABLE `certificate_requests` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificate requests from residents';

--
-- Dumping data for table `certificate_requests`
--

INSERT INTO `certificate_requests` (`id`, `reference_no`, `resident_id`, `certificate_id`, `payment_status`, `certificate_fee`, `status`, `purpose`, `field_values`, `generated_certificate_path`, `date_requested`, `date_approved`, `date_completed`, `approved_by`, `completed_by`, `remarks`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'CesgoQfYv1_kAzO7jk', 1, 1, 'Waived', 0.00, 'Approved', 'For employment purposes', NULL, NULL, '2026-01-19 10:30:00', NULL, NULL, NULL, NULL, 'Senior citizen - fee waived', '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(2, 'NmtBEaKSQJalcnIS', 2, 4, 'Unpaid', 255.00, 'Pending', 'For business permit application', NULL, NULL, '2025-11-03 14:15:00', NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(3, 'VuNkEswFmPmzQxYk', 3, 1, 'Unpaid', 50.00, 'Pending', 'For school requirements', NULL, NULL, '2025-11-03 09:45:00', NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(4, 'REF-2026-001', 1, 3, 'Paid', 100.00, 'Completed', 'For travel abroad', NULL, NULL, '2026-01-15 11:20:00', NULL, NULL, NULL, NULL, 'Completed and released', '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(5, 'REF-2026-002', 2, 1, 'Waived', 0.00, 'Approved', 'For medical assistance', NULL, NULL, '2026-01-16 13:30:00', NULL, NULL, NULL, NULL, 'Indigent - fee waived', '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(6, 'REF-2026-003', 3, 2, 'Unpaid', 150.00, 'Pending', 'For loan application', NULL, NULL, '2026-01-17 10:00:00', NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(7, 'REF-2026-004', 1, 1, 'Paid', 50.00, 'Approved', 'For employment', NULL, NULL, '2026-01-18 15:45:00', NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(8, 'REF-2026-005', 2, 3, 'Unpaid', 200.00, 'Pending', 'For visa application', NULL, NULL, '2026-01-19 09:15:00', NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL);

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
(6, 4, 'Roberto Cruz', 'Father', '+63 945 678 9013', 'Purok 5, Barangay Sample, City', 1, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(7, 5, 'Linda Mendoza', 'Spouse', '+63 956 789 0124', 'Purok 6, Barangay Sample, City', 1, '2026-02-12 15:55:48', '2026-02-12 15:55:48'),
(8, 6, 'Argel Gonzales Tayson', 'Son', '+639311913120', '343242', 1, '2026-02-12 16:13:45', '2026-02-12 16:13:45'),
(9, 7, 'Argel Gonzales Tayson', 'sdas', '+639311913120', '343242', 1, '2026-02-12 16:24:06', '2026-02-12 16:24:06'),
(13, 8, '4werw', '31231', '324342', NULL, 1, '2026-02-12 17:59:37', '2026-02-12 17:59:37'),
(16, 9, '4werw', '31231', '324342', NULL, 1, '2026-02-12 18:14:31', '2026-02-12 18:14:31'),
(19, 1, 'Maria Dela Cruz', 'Spouse', '+63 912 345 6790', 'Purok 1, Barangay Sample, City', 1, '2026-02-12 18:25:19', '2026-02-12 18:25:19'),
(20, 1, 'Pedro Dela Cruz', 'Father', '+63 912 345 6791', 'Purok 2, Barangay Sample, City', 2, '2026-02-12 18:25:19', '2026-02-12 18:25:19');

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
  `current_address` text NOT NULL,
  `household_no` varchar(50) DEFAULT NULL,
  `household_contact` varchar(20) DEFAULT NULL,
  `purok` varchar(50) DEFAULT NULL,
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
  `activity_status` enum('Active','Inactive','Deceased') DEFAULT 'Active' COMMENT 'Resident activity status',
  `status_changed_at` datetime DEFAULT NULL COMMENT 'When status was last changed',
  `status_changed_by` int(11) DEFAULT NULL COMMENT 'User ID who changed status',
  `status_remarks` text DEFAULT NULL COMMENT 'Remarks about status change',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main residents information table';

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `resident_id`, `photo`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `date_of_birth`, `age`, `place_of_birth`, `religion`, `ethnicity`, `mobile_number`, `email`, `current_address`, `household_no`, `household_contact`, `purok`, `civil_status`, `spouse_name`, `father_name`, `mother_name`, `number_of_children`, `household_head`, `educational_attainment`, `employment_status`, `occupation`, `monthly_income`, `fourps_member`, `fourps_id`, `voter_status`, `precinct_number`, `pwd_status`, `senior_citizen`, `indigent`, `philhealth_id`, `membership_type`, `philhealth_category`, `age_health_group`, `medical_history`, `lmp_date`, `using_fp_method`, `fp_methods_used`, `fp_status`, `water_source_type`, `toilet_facility_type`, `remarks`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `activity_status`, `status_changed_at`, `status_changed_by`, `status_remarks`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, NULL, 'uploads/residents/resident_1770920719_698e1b0f95a67.jpg', 'Juan', 'Santos', 'Dela Cruz', NULL, 'Male', '1985-05-15', 40, NULL, 'Roman Catholic', 'Non-IPS', '+63 912 345 6789', 'juan.delacruz@email.com', 'Purok 1, Barangay Sample, City', NULL, NULL, NULL, 'Married', 'Maria Dela Cruz', 'Pedro Dela Cruz', 'Rosa Santos', 2, NULL, 'College Graduate', 'Employed', 'Teacher', '20000-30000', 'No', NULL, 'Yes', '0001A', 'No', 'No', 'No', NULL, 'Member', 'Direct Contributor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, '2026-02-12 23:55:48', NULL, 'Deceased', '2026-02-13 02:25:19', NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 18:25:19', NULL, NULL),
(2, NULL, NULL, 'Maria', 'Lopez', 'Santos', NULL, 'Female', '1990-08-20', 34, NULL, 'Roman Catholic', 'Non-IPS', '+63 923 456 7890', 'maria.santos@email.com', 'Purok 3, Barangay Sample, City', NULL, NULL, NULL, 'Single', NULL, 'Jose Santos', 'Ana Lopez', 0, NULL, 'High School Graduate', 'Self-Employed', 'Sari-sari Store Owner', '10000-20000', 'Yes', '4PS-2024-001234', 'Yes', NULL, 'No', 'No', 'No', '12-987654321-1', 'Member', 'Indirect Contributor', NULL, NULL, '2024-01-15', 'Yes', 'Pills', 'Current User', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Active', NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(3, NULL, NULL, 'Pedro', 'Garcia', 'Reyes', 'Sr.', 'Male', '1958-03-10', 66, NULL, 'Roman Catholic', 'Non-IPS', '+63 934 567 8901', NULL, 'Purok 4, Barangay Sample, City', NULL, NULL, NULL, 'Married', 'Luz Reyes', NULL, NULL, 3, NULL, 'Elementary Graduate', 'Retired', 'Former Farmer', NULL, 'No', NULL, 'Yes', '0002B', 'No', 'Yes', 'No', '12-111222333-4', 'Member', 'Direct Contributor', NULL, 'Hypertension, Diabetes', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, '2026-02-12 23:55:48', NULL, 'Active', NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(4, NULL, NULL, 'Ana', NULL, 'Cruz', NULL, 'Female', '1995-11-25', 29, NULL, 'Roman Catholic', 'Non-IPS', '+63 945 678 9012', NULL, 'Purok 5, Barangay Sample, City', NULL, NULL, NULL, 'Single', NULL, 'Roberto Cruz', 'Elena Cruz', 0, NULL, 'College Level', 'Unemployed', NULL, NULL, 'Yes', NULL, 'Yes', NULL, 'Yes', 'No', 'No', '12-555666777-8', 'Dependent', 'Sponsored', NULL, 'Hearing Impairment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Active', NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(5, NULL, NULL, 'Jose', 'Lumad', 'Mendoza', NULL, 'Male', '1988-07-12', 36, NULL, 'Indigenous Beliefs', 'IPS', '+63 956 789 0123', NULL, 'Purok 6, Barangay Sample, City', NULL, NULL, NULL, 'Married', 'Linda Mendoza', NULL, NULL, 4, NULL, 'Elementary Level', 'Self-Employed', 'Farmer', '5000-10000', 'No', NULL, 'Yes', NULL, 'No', 'No', 'No', '12-999888777-6', 'Member', 'Sponsored', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Verified', NULL, '2026-02-12 23:55:48', NULL, 'Active', NULL, NULL, NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(6, 'W-00006', NULL, 'Argel', 'Gonzales', 'Tayson', NULL, 'Female', '2007-07-11', 18, NULL, 'Christian', 'IPS', '+639311913120', 'mamondog0@gmail.com', '343242', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'Elementary Level', 'Employed', '2WDW', 'Below 5000', 'No', NULL, 'No', NULL, 'No', 'No', 'No', '4523324234', 'Member', 'Direct Contributor', 'Infant (29 days - 1 year)', 'asdaw', NULL, 'Yes', 'Pills', NULL, NULL, NULL, 'sdadsa', 'Pending', NULL, NULL, NULL, 'Active', NULL, NULL, NULL, '2026-02-12 16:13:45', '2026-02-12 16:13:45', NULL, NULL),
(7, 'W-00007', NULL, 'Argel', 'Gonzales', 'Tayson', NULL, 'Male', '2026-02-13', 0, NULL, 'Christian', 'IPS', '+639311913120', 'mamondog0@gmail.com', '343242', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'Elementary Level', 'Unemployed', '2WDW', '5000-10000', 'No', NULL, 'No', NULL, 'No', 'No', 'No', NULL, 'Member', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Active', NULL, NULL, NULL, '2026-02-12 16:24:06', '2026-02-12 16:24:06', NULL, NULL),
(8, 'W-00008', NULL, 'Marwen', 'lee', 'santiago', NULL, 'Male', '2026-02-26', 0, NULL, '2323', 'IPS', '81321231', 'imnotlizive@gmail.com', 'dawdada', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'No Formal Education', 'Employed', 'asdawda', 'Below 5000', 'No', NULL, 'No', NULL, 'No', 'No', 'No', '1231231', 'Member', 'Direct Contributor', 'Newborn (0-28 days)', '34234', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Inactive', '2026-02-13 01:59:37', NULL, NULL, '2026-02-12 16:26:00', '2026-02-12 17:59:37', NULL, NULL),
(9, 'W-00009', NULL, 'Marwen', 'lee', 'santiago', NULL, 'Male', '2026-02-26', 0, NULL, '2323', 'IPS', '81321231', 'imnotlizive@gmail.com', 'dawdada', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 0, NULL, 'No Formal Education', 'Unemployed', 'asdawda', '5000-10000', 'No', NULL, 'No', NULL, 'No', 'No', 'No', '23231231', 'Member', 'Indirect Contributor', 'Newborn (0-28 days)', '123123', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rejected', NULL, NULL, 'sasda', 'Inactive', '2026-02-13 02:14:31', NULL, NULL, '2026-02-12 17:56:28', '2026-02-12 18:14:31', NULL, NULL);

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
  `role` enum('Admin','Staff','Viewer') DEFAULT 'Staff',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System users';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@barangay.local', 'Admin', 'Active', '2026-02-12 15:55:42', '2026-02-12 15:55:42');

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
,`purok` varchar(50)
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
,`activity_status` enum('Active','Inactive','Deceased')
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
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `idx_reference_no` (`reference_no`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_certificate_id` (`certificate_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_date_requested` (`date_requested`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD CONSTRAINT `fk_request_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `fk_emergency_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Dumping data for table `blotter_records`
--

INSERT INTO `blotter_records` (`id`, `record_number`, `incident_type`, `incident_description`, `incident_date`, `incident_location`, `date_reported`, `reported_by`, `status`, `status_updated_at`, `status_updated_by`, `resolution`, `resolved_date`, `resolved_by`, `remarks`, `attachments`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'BR-2025-000096', 'Type', 'Sample incident description for record 096', '2025-11-03 05:55:00', 'Purok 1, Barangay Sample', '2025-11-03 05:55:00', NULL, 'Resolved', NULL, NULL, NULL, NULL, NULL, 'Case resolved through mediation', NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(2, 'BR-2025-000095', 'Incident Type', 'Sample incident description for record 095', '2025-10-30 05:50:00', 'Purok 2, Barangay Sample', '2025-10-30 05:50:00', NULL, 'Resolved', NULL, NULL, NULL, NULL, NULL, 'Parties reached an agreement', NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL),
(3, 'BR-2025-000094', 'Incident Type', 'Sample incident description for record 094', '2025-11-03 05:40:00', 'Purok 3, Barangay Sample', '2025-11-03 05:40:00', NULL, 'Under Investigation', NULL, NULL, NULL, NULL, NULL, 'Investigation ongoing', NULL, '2026-02-12 15:55:48', '2026-02-12 15:55:48', NULL, NULL);

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
(1, 1, NULL, 'Juan Dela Cruz', '09123456789', 'Purok 1, Barangay Sample', NULL, '2026-02-12 15:55:48'),
(2, 2, NULL, 'Maria Santos', '09234567890', 'Purok 2, Barangay Sample', NULL, '2026-02-12 15:55:48'),
(3, 3, NULL, 'Pedro Reyes', '09345678901', 'Purok 3, Barangay Sample', NULL, '2026-02-12 15:55:48');

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
(1, 1, NULL, 'Jose Garcia', '09456789012', 'Purok 1, Barangay Sample', NULL, '2026-02-12 15:55:48'),
(2, 2, NULL, 'Ana Lopez', '09567890123', 'Purok 2, Barangay Sample', NULL, '2026-02-12 15:55:48'),
(3, 3, NULL, 'Carlos Mendoza', '09678901234', 'Purok 3, Barangay Sample', NULL, '2026-02-12 15:55:48');

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
-- Structure for view `vw_blotter_complete`
--
DROP TABLE IF EXISTS `vw_blotter_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_blotter_complete` AS
SELECT 
  br.id,
  br.record_number,
  br.incident_type,
  br.incident_description,
  br.incident_date,
  br.incident_location,
  br.date_reported,
  br.status,
  br.resolution,
  br.resolved_date,
  br.remarks,
  br.created_at,
  br.updated_at,
  COUNT(DISTINCT bc.id) AS complainant_count,
  COUNT(DISTINCT brd.id) AS respondent_count,
  GROUP_CONCAT(DISTINCT bc.name ORDER BY bc.id SEPARATOR ', ') AS complainant_names,
  GROUP_CONCAT(DISTINCT brd.name ORDER BY brd.id SEPARATOR ', ') AS respondent_names
FROM blotter_records br
LEFT JOIN blotter_complainants bc ON br.id = bc.blotter_id
LEFT JOIN blotter_respondents brd ON br.id = brd.blotter_id
GROUP BY br.id
ORDER BY br.date_reported DESC;

-- --------------------------------------------------------

--
-- Structure for view `vw_blotter_statistics`
--
DROP TABLE IF EXISTS `vw_blotter_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_blotter_statistics` AS
SELECT 
  COUNT(*) AS total_records,
  SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
  SUM(CASE WHEN status = 'Under Investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
  SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
  SUM(CASE WHEN status = 'Dismissed' THEN 1 ELSE 0 END) AS dismissed_count
FROM blotter_records;

--
-- Indexes for table `blotter_records`
--
ALTER TABLE `blotter_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record_number` (`record_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_date_reported` (`date_reported`),
  ADD KEY `idx_incident_type` (`incident_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `blotter_complainants`
--
ALTER TABLE `blotter_complainants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blotter_id` (`blotter_id`),
  ADD KEY `idx_resident_id` (`resident_id`);

--
-- Indexes for table `blotter_respondents`
--
ALTER TABLE `blotter_respondents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blotter_id` (`blotter_id`),
  ADD KEY `idx_resident_id` (`resident_id`);

--
-- AUTO_INCREMENT for table `blotter_records`
--
ALTER TABLE `blotter_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blotter_complainants`
--
ALTER TABLE `blotter_complainants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blotter_respondents`
--
ALTER TABLE `blotter_respondents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
