-- Add fullname field to barangay_officials table
-- This allows storing official names without requiring a resident_id

ALTER TABLE `barangay_officials` 
ADD COLUMN `fullname` VARCHAR(255) DEFAULT NULL COMMENT 'Full name of the official' AFTER `resident_id`;

-- Update existing records to use resident names if available
UPDATE barangay_officials bo
LEFT JOIN residents r ON bo.resident_id = r.id
SET bo.fullname = CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name, r.suffix)
WHERE bo.resident_id IS NOT NULL AND r.id IS NOT NULL;
