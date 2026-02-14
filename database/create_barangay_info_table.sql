-- ============================================
-- Barangay Information Table
-- Description: Stores barangay configuration and settings
-- ============================================

USE `bmis`;

CREATE TABLE IF NOT EXISTS `barangay_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `province_name` VARCHAR(100) NOT NULL DEFAULT 'Province Name',
  `town_name` VARCHAR(100) NOT NULL DEFAULT 'Town/City Name',
  `barangay_name` VARCHAR(100) NOT NULL DEFAULT 'Barangay Name',
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `dashboard_text` TEXT DEFAULT NULL,
  `municipal_logo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to municipal/city logo',
  `barangay_logo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to barangay logo',
  `dashboard_image` VARCHAR(255) DEFAULT NULL COMMENT 'Path to dashboard background image',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
  
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_barangay_info_user` 
    FOREIGN KEY (`updated_by`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay configuration and settings';

-- Insert default record
INSERT INTO `barangay_info` 
  (`id`, `province_name`, `town_name`, `barangay_name`, `contact_number`, `dashboard_text`) 
VALUES 
  (1, 'Zambales', 'Subic', 'Barangay Wawandue', '09191234567', 'TEST')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- ============================================
-- End of Barangay Info Table
-- ============================================
