-- ============================================
-- Sample Certificates Data
-- ============================================
-- This file contains sample certificate data for testing

USE `bmis`;

-- Insert sample certificates
INSERT INTO `certificates` (`title`, `description`, `fee`, `status`, `template_content`, `created_at`) VALUES
('Barangay Clearance', 'Certificate of Barangay Clearance for various purposes', 0.00, 'Unpublished', NULL, NOW()),
('Certificate of Residency', 'Proof of residency in the barangay', 0.00, 'Unpublished', NULL, NOW()),
('Certificate of Indigency', 'Certificate for indigent residents', 0.00, 'Unpublished', NULL, NOW()),
('Business Permit', 'Barangay business permit certificate', 25.00, 'Unpublished', NULL, NOW()),
('Good Moral Certificate', 'Certificate of good moral character', 0.00, 'Unpublished', NULL, NOW()),
('Certificate of Employment', 'Employment verification certificate', 0.00, 'Published', NULL, NOW());

-- Display inserted records
SELECT * FROM `certificates` ORDER BY `created_at` DESC;
