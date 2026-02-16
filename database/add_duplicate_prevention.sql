-- ============================================
-- Duplicate Prevention Migration
-- ============================================
-- Description: Add unique constraints and indexes to prevent duplicate residents
-- Created: 2024
-- ============================================

USE `bmis`;

-- ============================================
-- Step 1: Add unique constraint for mobile_number
-- ============================================
-- Check if constraint already exists, if not add it
ALTER TABLE `residents` 
ADD UNIQUE INDEX `idx_unique_mobile` (`mobile_number`);

-- ============================================
-- Step 2: Add unique constraint for email (only for non-null values)
-- ============================================
-- Note: MySQL allows multiple NULL values in unique indexes
-- This will only enforce uniqueness for non-null email addresses
ALTER TABLE `residents` 
ADD UNIQUE INDEX `idx_unique_email` (`email`);

-- ============================================
-- Step 3: Add unique constraint for philhealth_id (only for non-null values)
-- ============================================
-- Note: MySQL allows multiple NULL values in unique indexes
-- This will only enforce uniqueness for non-null Philhealth IDs
ALTER TABLE `residents` 
ADD UNIQUE INDEX `idx_unique_philhealth` (`philhealth_id`);

-- ============================================
-- Step 4: Add composite index for name + date of birth checking
-- ============================================
-- This improves performance when checking for duplicate names with same DOB
ALTER TABLE `residents` 
ADD INDEX `idx_name_dob` (`first_name`, `last_name`, `date_of_birth`);

-- ============================================
-- Step 5: Add index for activity_status to improve queries
-- ============================================
ALTER TABLE `residents` 
ADD INDEX `idx_activity_status` (`activity_status`);

-- ============================================
-- Verification Query
-- ============================================
-- Run this to verify the indexes were created successfully:
-- SHOW INDEXES FROM `residents`;

-- ============================================
-- Rollback Instructions (if needed)
-- ============================================
-- To remove these constraints, run:
-- ALTER TABLE `residents` DROP INDEX `idx_unique_mobile`;
-- ALTER TABLE `residents` DROP INDEX `idx_unique_email`;
-- ALTER TABLE `residents` DROP INDEX `idx_unique_philhealth`;
-- ALTER TABLE `residents` DROP INDEX `idx_name_dob`;
-- ALTER TABLE `residents` DROP INDEX `idx_activity_status`;

-- ============================================
-- Notes
-- ============================================
-- 1. Before running this migration, ensure there are no existing duplicates
-- 2. To find existing duplicates, run:
--
-- -- Find duplicate mobile numbers:
-- SELECT mobile_number, COUNT(*) as count 
-- FROM residents 
-- GROUP BY mobile_number 
-- HAVING count > 1;
--
-- -- Find duplicate emails:
-- SELECT email, COUNT(*) as count 
-- FROM residents 
-- WHERE email IS NOT NULL AND email != ''
-- GROUP BY email 
-- HAVING count > 1;
--
-- -- Find duplicate Philhealth IDs:
-- SELECT philhealth_id, COUNT(*) as count 
-- FROM residents 
-- WHERE philhealth_id IS NOT NULL AND philhealth_id != ''
-- GROUP BY philhealth_id 
-- HAVING count > 1;
--
-- -- Find duplicate name + DOB combinations:
-- SELECT first_name, last_name, date_of_birth, COUNT(*) as count 
-- FROM residents 
-- GROUP BY first_name, last_name, date_of_birth 
-- HAVING count > 1;
--
-- 3. If duplicates exist, clean them up before running this migration
-- ============================================
