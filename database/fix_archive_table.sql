-- Fix archive table to add AUTO_INCREMENT to id column
-- This will fix the restore button functionality

USE bmis;

-- Drop primary key if exists
ALTER TABLE archive DROP PRIMARY KEY;

-- Modify id column to be AUTO_INCREMENT with PRIMARY KEY
ALTER TABLE archive 
MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Verify the fix
SELECT 'Archive table fixed successfully!' as Status;
SELECT id, archive_type, record_id, deleted_by FROM archive LIMIT 5;
