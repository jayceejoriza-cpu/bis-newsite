-- ============================================
-- Sample Data for Barangay Management System
-- ============================================
-- This file contains sample data for testing purposes
-- Run this after setting up the main schema
-- ============================================

USE `bmis`;

-- ============================================
-- Sample Residents
-- ============================================

-- Sample Resident 1: Juan Dela Cruz (Verified)
INSERT INTO `residents` (
    `first_name`, `middle_name`, `last_name`, `sex`, `date_of_birth`, `age`, `religion`, `ethnicity`,
    `mobile_number`, `email`, `current_address`,
    `civil_status`, `spouse_name`, `father_name`, `mother_name`, `number_of_children`,
    `educational_attainment`, `employment_status`, `occupation`, `monthly_income`,
    `fourps_member`, `voter_status`, `precinct_number`,
    `philhealth_id`, `membership_type`, `philhealth_category`,
    `verification_status`, `verified_at`
) VALUES (
    'Juan', 'Santos', 'Dela Cruz', 'Male', '1985-05-15', 39, 'Roman Catholic', 'Non-IPS',
    '+63 912 345 6789', 'juan.delacruz@email.com', 'Purok 1, Barangay Sample, City',
    'Married', 'Maria Dela Cruz', 'Pedro Dela Cruz', 'Rosa Santos', 2,
    'College Graduate', 'Employed', 'Teacher', '20000-30000',
    'No', 'Yes', '0001A',
    '12-345678901-2', 'Member', 'Direct Contributor',
    'Verified', NOW()
);

SET @resident1_id = LAST_INSERT_ID();

-- Emergency contacts for Resident 1
INSERT INTO `emergency_contacts` (`resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`) VALUES
(@resident1_id, 'Maria Dela Cruz', 'Spouse', '+63 912 345 6790', 'Purok 1, Barangay Sample, City', 1),
(@resident1_id, 'Pedro Dela Cruz', 'Father', '+63 912 345 6791', 'Purok 2, Barangay Sample, City', 2);

-- Sample Resident 2: Maria Santos (Pending)
INSERT INTO `residents` (
    `first_name`, `middle_name`, `last_name`, `sex`, `date_of_birth`, `age`, `religion`, `ethnicity`,
    `mobile_number`, `email`, `current_address`,
    `civil_status`, `father_name`, `mother_name`,
    `educational_attainment`, `employment_status`, `occupation`, `monthly_income`,
    `fourps_member`, `fourps_id`, `voter_status`,
    `philhealth_id`, `membership_type`, `philhealth_category`,
    `lmp_date`, `using_fp_method`, `fp_methods_used`, `fp_status`,
    `verification_status`
) VALUES (
    'Maria', 'Lopez', 'Santos', 'Female', '1990-08-20', 34, 'Roman Catholic', 'Non-IPS',
    '+63 923 456 7890', 'maria.santos@email.com', 'Purok 3, Barangay Sample, City',
    'Single', 'Jose Santos', 'Ana Lopez',
    'High School Graduate', 'Self-Employed', 'Sari-sari Store Owner', '10000-20000',
    'Yes', '4PS-2024-001234', 'Yes',
    '12-987654321-1', 'Member', 'Indirect Contributor',
    '2024-01-15', 'Yes', 'Pills', 'Current User',
    'Pending'
);

SET @resident2_id = LAST_INSERT_ID();

-- Emergency contacts for Resident 2
INSERT INTO `emergency_contacts` (`resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`) VALUES
(@resident2_id, 'Jose Santos', 'Father', '+63 923 456 7891', 'Purok 3, Barangay Sample, City', 1);

-- Sample Resident 3: Pedro Reyes (Senior Citizen, Verified)
INSERT INTO `residents` (
    `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `date_of_birth`, `age`, `religion`, `ethnicity`,
    `mobile_number`, `current_address`,
    `civil_status`, `spouse_name`, `number_of_children`,
    `educational_attainment`, `employment_status`, `occupation`,
    `voter_status`, `precinct_number`,
    `philhealth_id`, `membership_type`, `philhealth_category`,
    `senior_citizen`, `medical_history`,
    `verification_status`, `verified_at`
) VALUES (
    'Pedro', 'Garcia', 'Reyes', 'Sr.', 'Male', '1958-03-10', 66, 'Roman Catholic', 'Non-IPS',
    '+63 934 567 8901', 'Purok 4, Barangay Sample, City',
    'Married', 'Luz Reyes', 3,
    'Elementary Graduate', 'Retired', 'Former Farmer',
    'Yes', '0002B',
    '12-111222333-4', 'Member', 'Direct Contributor',
    'Yes', 'Hypertension, Diabetes',
    'Verified', NOW()
);

SET @resident3_id = LAST_INSERT_ID();

-- Emergency contacts for Resident 3
INSERT INTO `emergency_contacts` (`resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`) VALUES
(@resident3_id, 'Luz Reyes', 'Spouse', '+63 934 567 8902', 'Purok 4, Barangay Sample, City', 1),
(@resident3_id, 'Pedro Reyes Jr.', 'Son', '+63 934 567 8903', 'Purok 5, Barangay Sample, City', 2);

-- Sample Resident 4: Ana Cruz (PWD, Pending)
INSERT INTO `residents` (
    `first_name`, `last_name`, `sex`, `date_of_birth`, `age`, `religion`, `ethnicity`,
    `mobile_number`, `current_address`,
    `civil_status`, `father_name`, `mother_name`,
    `educational_attainment`, `employment_status`,
    `fourps_member`, `voter_status`,
    `philhealth_id`, `membership_type`, `philhealth_category`,
    `pwd_status`, `medical_history`,
    `verification_status`
) VALUES (
    'Ana', 'Cruz', 'Female', '1995-11-25', 29, 'Roman Catholic', 'Non-IPS',
    '+63 945 678 9012', 'Purok 5, Barangay Sample, City',
    'Single', 'Roberto Cruz', 'Elena Cruz',
    'College Level', 'Unemployed',
    'Yes', 'Yes',
    '12-555666777-8', 'Dependent', 'Sponsored',
    'Yes', 'Hearing Impairment',
    'Pending'
);

SET @resident4_id = LAST_INSERT_ID();

-- Emergency contacts for Resident 4
INSERT INTO `emergency_contacts` (`resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`) VALUES
(@resident4_id, 'Roberto Cruz', 'Father', '+63 945 678 9013', 'Purok 5, Barangay Sample, City', 1);

-- Sample Resident 5: Jose Mendoza (Indigenous People, Verified)
INSERT INTO `residents` (
    `first_name`, `middle_name`, `last_name`, `sex`, `date_of_birth`, `age`, `religion`, `ethnicity`,
    `mobile_number`, `current_address`,
    `civil_status`, `spouse_name`, `number_of_children`,
    `educational_attainment`, `employment_status`, `occupation`, `monthly_income`,
    `voter_status`,
    `philhealth_id`, `membership_type`, `philhealth_category`,
    `verification_status`, `verified_at`
) VALUES (
    'Jose', 'Lumad', 'Mendoza', 'Male', '1988-07-12', 36, 'Indigenous Beliefs', 'IPS',
    '+63 956 789 0123', 'Purok 6, Barangay Sample, City',
    'Married', 'Linda Mendoza', 4,
    'Elementary Level', 'Self-Employed', 'Farmer', '5000-10000',
    'Yes',
    '12-999888777-6', 'Member', 'Sponsored',
    'Verified', NOW()
);

SET @resident5_id = LAST_INSERT_ID();

-- Emergency contacts for Resident 5
INSERT INTO `emergency_contacts` (`resident_id`, `contact_name`, `relationship`, `contact_number`, `address`, `priority`) VALUES
(@resident5_id, 'Linda Mendoza', 'Spouse', '+63 956 789 0124', 'Purok 6, Barangay Sample, City', 1);

-- ============================================
-- Sample Audit Logs
-- ============================================

INSERT INTO `audit_logs` (`resident_id`, `user_id`, `action`, `table_name`, `record_id`, `new_values`, `ip_address`, `created_at`) VALUES
(@resident1_id, 1, 'CREATE', 'residents', @resident1_id, '{"first_name":"Juan","last_name":"Dela Cruz"}', '127.0.0.1', NOW()),
(@resident1_id, 1, 'VERIFY', 'residents', @resident1_id, '{"verification_status":"Verified"}', '127.0.0.1', NOW()),
(@resident2_id, 1, 'CREATE', 'residents', @resident2_id, '{"first_name":"Maria","last_name":"Santos"}', '127.0.0.1', NOW()),
(@resident3_id, 1, 'CREATE', 'residents', @resident3_id, '{"first_name":"Pedro","last_name":"Reyes"}', '127.0.0.1', NOW()),
(@resident3_id, 1, 'VERIFY', 'residents', @resident3_id, '{"verification_status":"Verified"}', '127.0.0.1', NOW());

-- ============================================
-- Display Summary
-- ============================================

SELECT 
    'Sample Data Inserted Successfully!' AS Status,
    COUNT(*) AS Total_Residents,
    SUM(CASE WHEN verification_status = 'Verified' THEN 1 ELSE 0 END) AS Verified,
    SUM(CASE WHEN verification_status = 'Pending' THEN 1 ELSE 0 END) AS Pending
FROM residents;

SELECT 
    'Emergency Contacts' AS Type,
    COUNT(*) AS Total_Contacts
FROM emergency_contacts;

-- ============================================
-- End of Sample Data
-- ============================================
