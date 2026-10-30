-- PARSU-GPass: Online Vehicle Gate Pass Management System
-- Database Schema for MySQL / MariaDB (InnoDB, utf8mb4)
-- Target Database: parsu_gpass

CREATE DATABASE IF NOT EXISTS `parsu_gpass` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `parsu_gpass`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `account_role` ENUM('applicant', 'admin') NOT NULL DEFAULT 'applicant',
  `account_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: applicant_profiles
-- --------------------------------------------------------
DROP TABLE IF EXISTS `applicant_profiles`;
CREATE TABLE `applicant_profiles` (
  `applicant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `applicant_type` ENUM('student', 'employee') NOT NULL,
  `school_id` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) NULL DEFAULT NULL,
  `contact_number` VARCHAR(30) NOT NULL,
  `department` VARCHAR(150) NOT NULL,
  `course_year_section` VARCHAR(150) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_applicant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: vehicles
-- --------------------------------------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `vehicle_id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `vehicle_type` VARCHAR(50) NOT NULL,
  `year_model` YEAR NOT NULL,
  `plate_number` VARCHAR(30) NOT NULL,
  `color` VARCHAR(50) NOT NULL,
  `license_number` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vehicle_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicant_profiles` (`applicant_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `application_id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `application_number` VARCHAR(50) NOT NULL UNIQUE,
  `application_status` ENUM('pending', 'declined', 'resubmitted', 'approved', 'payment_pending', 'payment_verification', 'paid', 'for_release', 'released') NOT NULL DEFAULT 'pending',
  `submitted_at` DATETIME NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_app_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicant_profiles` (`applicant_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ownership_details
-- --------------------------------------------------------
DROP TABLE IF EXISTS `ownership_details`;
CREATE TABLE `ownership_details` (
  `ownership_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL UNIQUE,
  `ownership_category` ENUM('first_owner_own_name', 'first_owner_relative_name', 'secondhand_applicant_buyer', 'secondhand_applicant_not_buyer') NOT NULL,
  `owner_name` VARCHAR(150) NULL DEFAULT NULL,
  `buyer_name` VARCHAR(150) NULL DEFAULT NULL,
  `previous_owner_name` VARCHAR(150) NULL DEFAULT NULL,
  `relationship_to_applicant` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ownership_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: application_signatures
-- --------------------------------------------------------
DROP TABLE IF EXISTS `application_signatures`;
CREATE TABLE `application_signatures` (
  `signature_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `signature_file` VARCHAR(255) NOT NULL,
  `version_number` INT NOT NULL DEFAULT 1,
  `is_current` TINYINT(1) NOT NULL DEFAULT 1,
  `signed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_app_signature_version` (`application_id`, `version_number`),
  CONSTRAINT `fk_signature_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: document_types
-- --------------------------------------------------------
DROP TABLE IF EXISTS `document_types`;
CREATE TABLE `document_types` (
  `document_type_id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_code` VARCHAR(50) NOT NULL UNIQUE,
  `document_name` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: document_requirements
-- --------------------------------------------------------
DROP TABLE IF EXISTS `document_requirements`;
CREATE TABLE `document_requirements` (
  `requirement_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ownership_category` ENUM('first_owner_own_name', 'first_owner_relative_name', 'secondhand_applicant_buyer', 'secondhand_applicant_not_buyer') NOT NULL,
  `document_type_id` INT NOT NULL,
  `subject_role` VARCHAR(100) NOT NULL,
  `display_label` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 1,
  UNIQUE KEY `uk_req_category_doc_role` (`ownership_category`, `document_type_id`, `subject_role`),
  CONSTRAINT `fk_req_doc_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`document_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: application_documents
-- --------------------------------------------------------
DROP TABLE IF EXISTS `application_documents`;
CREATE TABLE `application_documents` (
  `document_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `requirement_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` INT NOT NULL,
  `version_number` INT NOT NULL DEFAULT 1,
  `is_current` TINYINT(1) NOT NULL DEFAULT 1,
  `uploaded_by` INT NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_app_req_version` (`application_id`, `requirement_id`, `version_number`),
  CONSTRAINT `fk_doc_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_req` FOREIGN KEY (`requirement_id`) REFERENCES `document_requirements` (`requirement_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: application_reviews
-- --------------------------------------------------------
DROP TABLE IF EXISTS `application_reviews`;
CREATE TABLE `application_reviews` (
  `review_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `admin_id` INT NOT NULL,
  `review_decision` ENUM('approved', 'declined') NOT NULL,
  `remarks` TEXT NOT NULL,
  `reviewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_review_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: application_status_history
-- --------------------------------------------------------
DROP TABLE IF EXISTS `application_status_history`;
CREATE TABLE `application_status_history` (
  `history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `previous_status` VARCHAR(50) NULL DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `changed_by` INT NULL DEFAULT NULL,
  `remarks` TEXT NULL DEFAULT NULL,
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hist_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `psu_or_number` VARCHAR(100) NOT NULL UNIQUE,
  `receipt_file` VARCHAR(255) NOT NULL,
  `payment_status` ENUM('pending_verification', 'verified', 'rejected') NOT NULL DEFAULT 'pending_verification',
  `verified_by` INT NULL DEFAULT NULL,
  `verification_remarks` TEXT NULL DEFAULT NULL,
  `payment_date` DATE NOT NULL,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pay_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_admin` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: gate_pass_stickers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `gate_pass_stickers`;
CREATE TABLE `gate_pass_stickers` (
  `sticker_id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL UNIQUE,
  `sticker_number` VARCHAR(50) NOT NULL UNIQUE,
  `applicant_type` ENUM('student', 'employee') NOT NULL,
  `issue_date` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `sticker_status` ENUM('for_release', 'released', 'expired') NOT NULL DEFAULT 'for_release',
  `generated_by` INT NOT NULL,
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sticker_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sticker_admin` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sticker_releases
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sticker_releases`;
CREATE TABLE `sticker_releases` (
  `release_id` INT AUTO_INCREMENT PRIMARY KEY,
  `sticker_id` INT NOT NULL UNIQUE,
  `released_by` INT NOT NULL,
  `received_by` VARCHAR(150) NOT NULL,
  `release_remarks` TEXT NULL DEFAULT NULL,
  `released_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rel_sticker` FOREIGN KEY (`sticker_id`) REFERENCES `gate_pass_stickers` (`sticker_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rel_admin` FOREIGN KEY (`released_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Seed Data: Document Types
-- --------------------------------------------------------
INSERT INTO `document_types` (`document_type_id`, `document_code`, `document_name`) VALUES
(1, 'OR', 'Official Receipt (OR)'),
(2, 'CR', 'Certificate of Registration (CR)'),
(3, 'APPLICANT_DL', 'Applicant Driver License'),
(4, 'VEHICLE_OWNER_DL', 'Vehicle Owner Driver License'),
(5, 'AUTHORIZATION_LETTER', 'Notarized Authorization Letter'),
(6, 'DEED_OF_SALE', 'Notarized Deed of Sale'),
(7, 'PREVIOUS_OWNER_DL', 'Previous Owner Driver License'),
(8, 'BUYER_DL', 'Buyer Driver License')
ON DUPLICATE KEY UPDATE `document_name` = VALUES(`document_name`);

-- --------------------------------------------------------
-- Seed Data: Document Requirements
-- --------------------------------------------------------
-- Category 1: first_owner_own_name (3 requirements)
INSERT INTO `document_requirements` (`requirement_id`, `ownership_category`, `document_type_id`, `subject_role`, `display_label`, `sort_order`) VALUES
(1, 'first_owner_own_name', 1, 'applicant', 'Official Receipt (OR)', 1),
(2, 'first_owner_own_name', 2, 'applicant', 'Certificate of Registration (CR)', 2),
(3, 'first_owner_own_name', 3, 'applicant', 'Applicant Driver''s License (with 3 signatures)', 3),

-- Category 2: first_owner_relative_name (5 requirements)
(4, 'first_owner_relative_name', 1, 'vehicle', 'Official Receipt (OR)', 1),
(5, 'first_owner_relative_name', 2, 'vehicle', 'Certificate of Registration (CR)', 2),
(6, 'first_owner_relative_name', 4, 'vehicle_owner', 'Registered Vehicle Owner''s Driver''s License (with 3 signatures)', 3),
(7, 'first_owner_relative_name', 3, 'applicant', 'Applicant Driver''s License (with 3 signatures)', 4),
(8, 'first_owner_relative_name', 5, 'vehicle_owner', 'Notarized Authorization Letter (from the registered owner)', 5),

-- Category 3: secondhand_applicant_buyer (5 requirements)
(9, 'secondhand_applicant_buyer', 1, 'vehicle', 'Official Receipt (OR)', 1),
(10, 'secondhand_applicant_buyer', 2, 'vehicle', 'Certificate of Registration (CR)', 2),
(11, 'secondhand_applicant_buyer', 6, 'transaction', 'Notarized Deed of Sale', 3),
(12, 'secondhand_applicant_buyer', 7, 'previous_owner', 'Previous Registered Owner''s Driver''s License (with 3 signatures)', 4),
(13, 'secondhand_applicant_buyer', 3, 'applicant', 'Applicant Driver''s License (with 3 signatures)', 5),

-- Category 4: secondhand_applicant_not_buyer (7 requirements)
(14, 'secondhand_applicant_not_buyer', 1, 'vehicle', 'Official Receipt (OR)', 1),
(15, 'secondhand_applicant_not_buyer', 2, 'vehicle', 'Certificate of Registration (CR)', 2),
(16, 'secondhand_applicant_not_buyer', 6, 'transaction', 'Notarized Deed of Sale', 3),
(17, 'secondhand_applicant_not_buyer', 7, 'previous_owner', 'Previous Registered Owner''s Driver''s License (with 3 signatures)', 4),
(18, 'secondhand_applicant_not_buyer', 3, 'applicant', 'Applicant Driver''s License (with 3 signatures)', 5),
(19, 'secondhand_applicant_not_buyer', 5, 'buyer', 'Notarized Authorization Letter (from the buyer/current owner)', 6),
(20, 'secondhand_applicant_not_buyer', 8, 'buyer', 'Buyer''s Driver''s License (with 3 signatures)', 7)
ON DUPLICATE KEY UPDATE `display_label` = VALUES(`display_label`), `sort_order` = VALUES(`sort_order`);

-- --------------------------------------------------------
-- Seed Data: Default Accounts
-- Passwords:
-- Admin:    Admin@123456    ($2y$10$gQ0NqQ5Z8qLwzW5gQ7d0he7XW9Q5Z8qLwzW5gQ7d0he7XW9Q5Z8qL - standard BCRYPT)
-- Student:  Student@123456
-- Employee: Employee@123456
-- (Hashes below generated via password_hash('...', PASSWORD_BCRYPT))
-- --------------------------------------------------------

INSERT INTO `users` (`user_id`, `email`, `password`, `account_role`, `account_status`) VALUES
(1, 'admin@parsu.edu.ph', '$2y$10$iEIfN6sbnKWsXNbOwreSler/zEnr8slyTSIeX1pfCkaMRylkmgdDG', 'admin', 'active'),
(2, 'student@parsu.edu.ph', '$2y$10$szgG/vjTCQkQmKlPb0SM7uINBqNCf.nZrUmQq6l6AABzm9vCPgq2G', 'applicant', 'active'),
(3, 'employee@parsu.edu.ph', '$2y$10$Y6hwIKLPStyyTloN9Do3MujYyHaTIDJ0CRW93.Yb.MgmOtwmTb.LK', 'applicant', 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `applicant_profiles` (`applicant_id`, `user_id`, `applicant_type`, `school_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `contact_number`, `department`, `course_year_section`) VALUES
(1, 2, 'student', 'STUD-2024-0012', 'Juan', 'Cruz', 'Dela Cruz', '', '09123456789', 'College of Computer Studies', 'BSIT - 4A'),
(2, 3, 'employee', 'EMP-2018-0481', 'Maria', 'Santos', 'Reyes', '', '09987654321', 'General Services Office', 'Faculty / Staff')
ON DUPLICATE KEY UPDATE `school_id` = VALUES(`school_id`);
