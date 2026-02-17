-- ============================================
-- Archive Table Schema
-- Description: Stores deleted records for restoration
-- ============================================

USE bmis;

-- Drop existing archive table if it exists
DROP TABLE IF EXISTS `archive`;

-- Create archive table with proper structure
CREATE TABLE `archive` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `archive_type` VARCHAR(50) DEFAULT NULL COMMENT 'Type: resident, official, blotter, permit, user',
  `record_id` VARCHAR(50) DEFAULT NULL COMMENT 'Original record identifier',
  `record_data` LONGTEXT DEFAULT NULL COMMENT 'JSON encoded record data',
  `deleted_by` VARCHAR(100) DEFAULT NULL COMMENT 'Username who deleted the record',
  `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when deleted',
  PRIMARY KEY (`id`),
  KEY `idx_archive_type` (`archive_type`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archive table for deleted records';

-- Verify table creation
SELECT 'Archive table created successfully!' AS Status;
DESCRIBE archive;
