-- ============================================
-- Create activity_logs table
-- ============================================
-- This table is used to track user activities in the system
-- Run this SQL if the activity_logs table doesn't exist

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(100) NOT NULL COMMENT 'Username who performed the action',
  `action` VARCHAR(100) NOT NULL COMMENT 'Type of action performed',
  `description` TEXT DEFAULT NULL COMMENT 'Detailed description of the action',
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the action was performed',
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user`),
  INDEX `idx_action` (`action`),
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Activity logs for tracking user actions';

-- ============================================
-- Verify table was created
-- ============================================
SELECT 'activity_logs table created successfully!' AS status;
