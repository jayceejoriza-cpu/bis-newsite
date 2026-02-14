-- ============================================
-- Blotter Records Table
-- Description: Stores barangay blotter entries including complaints, incidents, and case statuses
-- ============================================

USE `bmis`;

-- ============================================
-- Table: blotter_records
-- Description: Main table for blotter records
-- ============================================
CREATE TABLE IF NOT EXISTS `blotter_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Format: BR-YYYY-XXXXXX',
  
  -- Incident Details
  `incident_type` VARCHAR(255) NOT NULL COMMENT 'Type of incident (e.g., Theft, Assault, Noise Complaint)',
  `incident_description` TEXT NOT NULL COMMENT 'Detailed description of the incident',
  `incident_date` DATETIME NOT NULL COMMENT 'Date and time when incident occurred',
  `incident_location` TEXT NOT NULL COMMENT 'Location where incident occurred',
  
  -- Report Details
  `date_reported` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date when blotter was reported',
  `reported_by` VARCHAR(255) DEFAULT NULL COMMENT 'Person who reported (if not complainant)',
  
  -- Status
  `status` ENUM('Pending', 'Under Investigation', 'Resolved', 'Dismissed') DEFAULT 'Pending' COMMENT 'Current status of the blotter',
  `status_updated_at` DATETIME DEFAULT NULL COMMENT 'When status was last updated',
  `status_updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who updated status',
  
  -- Resolution
  `resolution` TEXT DEFAULT NULL COMMENT 'Resolution details if resolved',
  `resolved_date` DATETIME DEFAULT NULL COMMENT 'Date when case was resolved',
  `resolved_by` INT(11) DEFAULT NULL COMMENT 'User ID who resolved the case',
  
  -- Additional Information
  `remarks` TEXT DEFAULT NULL COMMENT 'Additional remarks or notes',
  `attachments` TEXT DEFAULT NULL COMMENT 'JSON array of attachment file paths',
  
  -- Record Metadata
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_record_number` (`record_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_incident_date` (`incident_date`),
  INDEX `idx_date_reported` (`date_reported`),
  INDEX `idx_incident_type` (`incident_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay blotter records';

-- ============================================
-- Table: blotter_complainants
-- Description: Complainants involved in blotter records
-- ============================================
CREATE TABLE IF NOT EXISTS `blotter_complainants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `blotter_id` INT(11) NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` INT(11) DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Complainant full name',
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `statement` TEXT DEFAULT NULL COMMENT 'Complainant statement',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  INDEX `idx_blotter_id` (`blotter_id`),
  INDEX `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_complainant_blotter` 
    FOREIGN KEY (`blotter_id`) 
    REFERENCES `blotter_records` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_complainant_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter complainants';

-- ============================================
-- Table: blotter_respondents
-- Description: Respondents/accused involved in blotter records
-- ============================================
CREATE TABLE IF NOT EXISTS `blotter_respondents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `blotter_id` INT(11) NOT NULL COMMENT 'Foreign key to blotter_records',
  `resident_id` INT(11) DEFAULT NULL COMMENT 'Foreign key to residents table (if resident)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Respondent full name',
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `statement` TEXT DEFAULT NULL COMMENT 'Respondent statement',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  INDEX `idx_blotter_id` (`blotter_id`),
  INDEX `idx_resident_id` (`resident_id`),
  CONSTRAINT `fk_respondent_blotter` 
    FOREIGN KEY (`blotter_id`) 
    REFERENCES `blotter_records` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_respondent_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blotter respondents';

-- ============================================
-- Insert Sample Data
-- ============================================

-- Sample Blotter Records
INSERT INTO `blotter_records` 
(`record_number`, `incident_type`, `incident_description`, `incident_date`, `incident_location`, `date_reported`, `status`, `remarks`) 
VALUES
('BR-2025-000096', 'Type', 'Sample incident description for record 096', '2025-11-03 05:55:00', 'Purok 1, Barangay Sample', '2025-11-03 05:55:00', 'Resolved', 'Case resolved through mediation'),
('BR-2025-000095', 'Incident Type', 'Sample incident description for record 095', '2025-10-30 05:50:00', 'Purok 2, Barangay Sample', '2025-10-30 05:50:00', 'Resolved', 'Parties reached an agreement'),
('BR-2025-000094', 'Incident Type', 'Sample incident description for record 094', '2025-11-03 05:40:00', 'Purok 3, Barangay Sample', '2025-11-03 05:40:00', 'Under Investigation', 'Investigation ongoing');

-- Sample Complainants (using placeholder data)
INSERT INTO `blotter_complainants` 
(`blotter_id`, `name`, `contact_number`, `address`) 
VALUES
(1, 'Juan Dela Cruz', '09123456789', 'Purok 1, Barangay Sample'),
(2, 'Maria Santos', '09234567890', 'Purok 2, Barangay Sample'),
(3, 'Pedro Reyes', '09345678901', 'Purok 3, Barangay Sample');

-- Sample Respondents (using placeholder data)
INSERT INTO `blotter_respondents` 
(`blotter_id`, `name`, `contact_number`, `address`) 
VALUES
(1, 'Jose Garcia', '09456789012', 'Purok 1, Barangay Sample'),
(2, 'Ana Lopez', '09567890123', 'Purok 2, Barangay Sample'),
(3, 'Carlos Mendoza', '09678901234', 'Purok 3, Barangay Sample');

-- ============================================
-- Views for easier data retrieval
-- ============================================

-- View: Complete blotter information with complainants and respondents
CREATE OR REPLACE VIEW `vw_blotter_complete` AS
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

-- View: Blotter statistics
CREATE OR REPLACE VIEW `vw_blotter_statistics` AS
SELECT 
  COUNT(*) AS total_records,
  SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
  SUM(CASE WHEN status = 'Under Investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
  SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
  SUM(CASE WHEN status = 'Dismissed' THEN 1 ELSE 0 END) AS dismissed_count
FROM blotter_records;

-- ============================================
-- End of Blotter Schema
-- ============================================
