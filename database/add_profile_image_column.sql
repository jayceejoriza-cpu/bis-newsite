-- ============================================
-- Add profile_image column to users table
-- ============================================
-- This migration adds support for custom profile images/avatars

USE `bmis`;

-- Add profile_image column to users table
ALTER TABLE `users` 
ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL COMMENT 'Path to user profile image/avatar' 
AFTER `email`;

-- Create index for faster queries
CREATE INDEX `idx_profile_image` ON `users` (`profile_image`);

-- ============================================
-- Note: Run this SQL in your database to add the profile_image column
-- ============================================
