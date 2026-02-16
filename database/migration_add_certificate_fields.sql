-- ============================================
-- Migration: Add Dynamic Certificate Fields Support
-- ============================================
-- Description: Adds field_values and generated_certificate_path columns
--              to certificate_requests table for dynamic field support
-- Date: 2024
-- ============================================

USE `bmis`;

-- Add field_values column to store custom field values as JSON
ALTER TABLE `certificate_requests` 
ADD COLUMN `field_values` TEXT DEFAULT NULL COMMENT 'JSON of custom field values from the certificate template' 
AFTER `purpose`;

-- Add generated_certificate_path column to store path to generated PDF
ALTER TABLE `certificate_requests` 
ADD COLUMN `generated_certificate_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to generated certificate PDF' 
AFTER `field_values`;

-- ============================================
-- Verification Query
-- ============================================
-- Run this to verify the columns were added successfully:
-- DESCRIBE certificate_requests;

-- ============================================
-- End of Migration
-- ============================================
