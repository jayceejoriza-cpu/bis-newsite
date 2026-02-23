<?php
/**
 * Setup Certificate Tables
 * Drops and recreates certificates and certificate_requests tables
 * Run this by accessing: http://localhost/bis-newsite/bis-newsite/database/setup_certificate_tables.php
 */
require_once '../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS `certificate_requests`;
DROP TABLE IF EXISTS `certificates`;

-- Create certificates table
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Published','Unpublished') DEFAULT 'Published',
  `template_content` varchar(255) DEFAULT NULL COMMENT 'Path to PDF template',
  `fields` longtext DEFAULT NULL COMMENT 'JSON field configuration',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create certificate_requests table
CREATE TABLE `certificate_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL COMMENT 'For Resident ID and Name',
  `certificate_id` int(11) NOT NULL COMMENT 'For Certificate Name',
  `purpose` varchar(255) DEFAULT NULL COMMENT 'Purpose of Request',
  `date_requested` datetime DEFAULT current_timestamp() COMMENT 'Date Request',
  `status` varchar(50) DEFAULT 'Approved',
  `reference_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  KEY `certificate_id` (`certificate_id`),
  CONSTRAINT `fk_cert_req_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_req_cert` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
";

if ($conn->multi_query($sql)) {
    echo "Tables `certificates` and `certificate_requests` created successfully.";
} else {
    echo "Error creating tables: " . $conn->error;
}

$conn->close();
?>