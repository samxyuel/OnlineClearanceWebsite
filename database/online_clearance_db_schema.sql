-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 07:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_clearance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `academic_year_id` int(11) NOT NULL,
  `year` varchar(9) NOT NULL COMMENT 'Format: 2024-2025',
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`academic_year_id`, `year`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2024-2025', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16');

--
-- Triggers `academic_years`
--
DELIMITER $$
CREATE TRIGGER `ensure_single_active_academic_year` BEFORE UPDATE ON `academic_years` FOR EACH ROW BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `academic_years` SET `is_active` = 0 WHERE `academic_year_id` != NEW.academic_year_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL COMMENT 'e.g., "User Login", "Clearance Approved", "Bulk Operation"',
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'e.g., "User", "Clearance", "Student"',
  `entity_id` int(11) DEFAULT NULL COMMENT 'ID of the affected record',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Previous state of the record' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New state of the record' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/client information',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_operations`
--

CREATE TABLE `bulk_operations` (
  `operation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who initiated the bulk operation',
  `operation_type` varchar(50) NOT NULL COMMENT 'e.g., "Bulk Approve", "Bulk Reject", "Bulk Export"',
  `target_count` int(11) NOT NULL COMMENT 'Total number of records to process',
  `success_count` int(11) DEFAULT 0 COMMENT 'Successfully processed records',
  `failure_count` int(11) DEFAULT 0 COMMENT 'Failed to process records',
  `operation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details of the operation (targets, parameters)' CHECK (json_valid(`operation_data`)),
  `status` enum('In Progress','Completed','Failed','Cancelled') DEFAULT 'In Progress',
  `progress_percentage` decimal(5,2) DEFAULT 0.00 COMMENT '0.00 to 100.00',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL COMMENT 'Error details if operation failed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_applications`
--

CREATE TABLE `clearance_applications` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `status` enum('pending','in-progress','completed','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_applications`
--

INSERT INTO `clearance_applications` (`application_id`, `user_id`, `period_id`, `status`, `applied_at`, `completed_at`, `updated_at`) VALUES
(1, 1, 2, 'pending', '2025-08-15 04:01:37', NULL, '2025-08-15 04:01:37');

-- --------------------------------------------------------

--
-- Table structure for table `clearance_forms`
--

CREATE TABLE `clearance_forms` (
  `clearance_form_id` varchar(20) NOT NULL COMMENT 'Format: CF-YYYY-XXXXX',
  `user_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `clearance_type` enum('Student','Faculty') NOT NULL,
  `status` enum('Unapplied','Pending','In Progress','Completed','Rejected') DEFAULT 'Unapplied',
  `applied_at` timestamp NULL DEFAULT NULL COMMENT 'When user first applied',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When all signatories approved',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'When any signatory rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_forms`
--

INSERT INTO `clearance_forms` (`clearance_form_id`, `user_id`, `academic_year_id`, `semester_id`, `clearance_type`, `status`, `applied_at`, `completed_at`, `rejected_at`, `created_at`, `updated_at`) VALUES
('CF-2025-00001', 1, 1, 4, 'Student', 'Unapplied', NULL, NULL, NULL, '2025-08-15 11:20:43', '2025-08-15 11:20:43');

--
-- Triggers `clearance_forms`
--
DELIMITER $$
CREATE TRIGGER `generate_clearance_form_id` BEFORE INSERT ON `clearance_forms` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    DECLARE year_part VARCHAR(4);
    
    -- Get current year
    SET year_part = YEAR(CURDATE());
    
    -- Get next ID for this year
    SELECT COALESCE(MAX(CAST(SUBSTRING(clearance_form_id, 8) AS UNSIGNED)), 0) + 1
    INTO next_id
    FROM clearance_forms 
    WHERE clearance_form_id LIKE CONCAT('CF-', year_part, '-%');
    
    -- Set the clearance_form_id
    SET NEW.clearance_form_id = CONCAT('CF-', year_part, '-', LPAD(next_id, 5, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_periods`
--

CREATE TABLE `clearance_periods` (
  `period_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `period_name` varchar(100) DEFAULT NULL COMMENT 'Auto-generated: "2024-2025 1st Semester"',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_periods`
--

INSERT INTO `clearance_periods` (`period_id`, `academic_year_id`, `semester_id`, `period_name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 4, NULL, '2025-08-16', '2025-09-14', 1, '2025-08-15 03:45:16', '2025-08-15 03:45:16');

--
-- Triggers `clearance_periods`
--
DELIMITER $$
CREATE TRIGGER `ensure_single_active_clearance_period` BEFORE UPDATE ON `clearance_periods` FOR EACH ROW BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `clearance_periods` SET `is_active` = 0 WHERE `period_id` != NEW.period_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_requirements`
--

CREATE TABLE `clearance_requirements` (
  `requirement_id` int(11) NOT NULL,
  `clearance_type` enum('Student','Faculty') NOT NULL,
  `designation_id` int(11) NOT NULL COMMENT 'Which signatory is required',
  `is_required` tinyint(1) DEFAULT 1,
  `order_sequence` int(11) DEFAULT 0 COMMENT 'Order of appearance in clearance form',
  `is_department_specific` tinyint(1) DEFAULT 0 COMMENT 'TRUE for Program Head',
  `applies_to_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of department IDs for Program Head' CHECK (json_valid(`applies_to_departments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_requirements`
--

INSERT INTO `clearance_requirements` (`requirement_id`, `clearance_type`, `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, `created_at`) VALUES
(1, 'Student', 1, 1, 1, 0, NULL, '2025-08-13 19:48:16'),
(2, 'Student', 2, 1, 2, 0, NULL, '2025-08-13 19:48:16'),
(3, 'Student', 3, 1, 3, 0, NULL, '2025-08-13 19:48:16'),
(4, 'Student', 8, 1, 4, 1, '[1,2,3,4,5,6]', '2025-08-13 19:48:16'),
(5, 'Faculty', 1, 1, 1, 0, NULL, '2025-08-13 19:48:16'),
(6, 'Faculty', 2, 1, 2, 0, NULL, '2025-08-13 19:48:16'),
(7, 'Faculty', 8, 1, 3, 1, '[1,2,3,4,5,6]', '2025-08-13 19:48:16'),
(8, 'Student', 3, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(9, 'Student', 2, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(10, 'Student', 1, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(11, 'Student', 7, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(12, 'Student', 5, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(13, 'Student', 8, 1, 0, 1, '[\"ICT\", \"Business\", \"Engineering\"]', '2025-08-14 10:34:01'),
(14, 'Faculty', 3, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(15, 'Faculty', 2, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(16, 'Faculty', 1, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(17, 'Faculty', 7, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(18, 'Faculty', 5, 1, 0, 0, NULL, '2025-08-14 10:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `clearance_signatories`
--

CREATE TABLE `clearance_signatories` (
  `signatory_id` int(11) NOT NULL,
  `clearance_form_id` varchar(20) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `actual_user_id` int(11) DEFAULT NULL COMMENT 'Staff member who actually signed (for override)',
  `action` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `rejection_reason_id` int(11) DEFAULT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` text DEFAULT NULL COMMENT 'Additional details for rejection',
  `date_signed` timestamp NULL DEFAULT NULL COMMENT 'When action was taken',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_signatory_status`
--

CREATE TABLE `clearance_signatory_status` (
  `status_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `signed_by_user_id` int(11) DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_signatory_status`
--

INSERT INTO `clearance_signatory_status` (`status_id`, `application_id`, `requirement_id`, `status`, `signed_by_user_id`, `signed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(2, 1, 2, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(3, 1, 3, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(4, 1, 4, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(5, 1, 8, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(6, 1, 9, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(7, 1, 10, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(8, 1, 11, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(9, 1, 12, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37'),
(10, 1, 13, 'pending', NULL, NULL, '2025-08-15 04:01:37', '2025-08-15 04:01:37');

-- --------------------------------------------------------

--
-- Table structure for table `data_versions`
--

CREATE TABLE `data_versions` (
  `version_id` int(11) NOT NULL,
  `data_type` varchar(50) NOT NULL COMMENT 'e.g., "students", "faculty", "departments"',
  `operation_type` enum('import','export','backup','restore') NOT NULL,
  `file_id` int(11) DEFAULT NULL COMMENT 'Associated file if applicable',
  `user_id` int(11) DEFAULT NULL COMMENT 'User who performed the operation',
  `record_count` int(11) DEFAULT NULL COMMENT 'Number of records processed',
  `operation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details of the operation' CHECK (json_valid(`operation_details`)),
  `status` enum('In Progress','Completed','Failed') DEFAULT 'In Progress',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL COMMENT 'Error details if failed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(10) DEFAULT NULL,
  `department_type` enum('College','Senior High School') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `department_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Information & Communication Technology', 'ICT', 'College', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(2, 'Business & Management, Arts, and Sciences', 'BAS', 'College', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(3, 'Tourism and Hospitality Management', 'THM', 'College', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(4, 'Academic Track', 'ACAD', 'Senior High School', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(5, 'Technical-Vocational Livelihood Track', 'TVL', 'Senior High School', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(6, 'Home Economics', 'HE', 'Senior High School', 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `designation_id` int(11) NOT NULL,
  `designation_name` varchar(100) NOT NULL COMMENT 'Registrar, Cashier, Librarian, etc.',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`designation_id`, `designation_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Registrar', 'Registrar office staff', 1, '2025-08-13 19:48:16'),
(2, 'Cashier', 'Cashier office staff', 1, '2025-08-13 19:48:16'),
(3, 'Librarian', 'Library staff', 1, '2025-08-13 19:48:16'),
(4, 'MIS/IT', 'IT and MIS staff', 1, '2025-08-13 19:48:16'),
(5, 'Building Administrator', 'Building and facilities staff', 1, '2025-08-13 19:48:16'),
(6, 'HR', 'Human Resources staff', 1, '2025-08-13 19:48:16'),
(7, 'Student Affairs Officer', 'Student affairs staff', 1, '2025-08-13 19:48:16'),
(8, 'Program Head', 'Department program head', 1, '2025-08-13 19:48:16'),
(9, 'School Administrator', 'School administration staff', 1, '2025-08-13 19:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee Number format: LCA123P',
  `user_id` int(11) DEFAULT NULL,
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `file_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who uploaded the file',
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename',
  `file_path` varchar(500) NOT NULL COMMENT 'Server file path',
  `file_type` varchar(100) DEFAULT NULL COMMENT 'MIME type',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `file_category` varchar(50) DEFAULT NULL COMMENT 'e.g., "clearance_document", "import_file", "export_file"',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether file is still accessible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_sessions`
--

CREATE TABLE `login_sessions` (
  `session_id` varchar(255) NOT NULL COMMENT 'PHP session ID or custom session token',
  `user_id` int(11) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `operation_logs`
--

CREATE TABLE `operation_logs` (
  `log_id` int(11) NOT NULL,
  `operation_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL COMMENT 'ID of the record being processed',
  `target_type` varchar(50) DEFAULT NULL COMMENT 'Type of record (Student, Faculty, Clearance)',
  `action` varchar(50) DEFAULT NULL COMMENT 'Specific action taken',
  `result` enum('Success','Failure','Skipped') NOT NULL,
  `error_message` text DEFAULT NULL COMMENT 'Error details if failed',
  `processing_time_ms` int(11) DEFAULT NULL COMMENT 'Time taken to process this record in milliseconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL COMMENT 'user_management, clearance_management, reporting, etc.',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`, `category`, `is_active`) VALUES
(1, 'view_users', 'View user information', 'user_management', 1),
(2, 'create_users', 'Create new users', 'user_management', 1),
(3, 'edit_users', 'Edit user information', 'user_management', 1),
(4, 'delete_users', 'Delete users', 'user_management', 1),
(5, 'reset_passwords', 'Reset user passwords', 'user_management', 1),
(6, 'view_clearance', 'View clearance information', 'clearance_management', 1),
(7, 'edit_clearance', 'Edit clearance details', 'clearance_management', 1),
(8, 'approve_clearance', 'Approve clearance requests', 'clearance_management', 1),
(9, 'reject_clearance', 'Reject clearance requests', 'clearance_management', 1),
(10, 'override_approval', 'Override signatory approvals', 'clearance_management', 1),
(11, 'manage_academic_years', 'Manage academic years', 'academic_management', 1),
(12, 'manage_semesters', 'Manage semesters', 'academic_management', 1),
(13, 'manage_departments', 'Manage departments', 'academic_management', 1),
(14, 'manage_programs', 'Manage programs/courses', 'academic_management', 1),
(15, 'view_reports', 'View system reports', 'reporting', 1),
(16, 'export_data', 'Export data to various formats', 'reporting', 1),
(17, 'import_data', 'Import data from files', 'reporting', 1),
(18, 'manage_system_settings', 'Manage system configuration', 'system_admin', 1),
(19, 'view_audit_logs', 'View system audit logs', 'system_admin', 1),
(20, 'manage_roles', 'Manage user roles and permissions', 'system_admin', 1),
(21, 'manage_clearance_periods', 'Permission to manage_clearance_periods', NULL, 1),
(22, 'manage_clearance_requirements', 'Permission to manage_clearance_requirements', NULL, 1),
(23, 'manage_clearance_applications', 'Permission to manage_clearance_applications', NULL, 1),
(24, 'view_clearance_status', 'Permission to view_clearance_status', NULL, 1),
(25, 'sign_clearance', 'Permission to sign_clearance', NULL, 1),
(26, 'manage_clearance_settings', 'Permission to manage_clearance_settings', NULL, 1),
(27, 'manage_clearance_status', 'Manage clearance signatory status', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_code` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `program_code`, `description`, `department_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'BS in Information Technology', 'BSIT', 'Bachelor of Science in Information Technology', 1, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(2, 'BS in Computer Science', 'BSCS', 'Bachelor of Science in Computer Science', 1, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(3, 'BS in Computer Engineering', 'BSCpE', 'Bachelor of Science in Computer Engineering', 1, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(4, 'BS in Business Administration', 'BSBA', 'Bachelor of Science in Business Administration', 2, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(5, 'BS in Accountancy', 'BSA', 'Bachelor of Science in Accountancy', 2, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(6, 'BS in Accounting Information System', 'BSAIS', 'Bachelor of Science in Accounting Information System', 2, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(7, 'Bachelor of Multimedia Arts', 'BMMA', 'Bachelor of Multimedia Arts', 2, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(8, 'BA in Communication', 'BAC', 'Bachelor of Arts in Communication', 2, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(9, 'BS in Hospitality Management', 'BSHM', 'Bachelor of Science in Hospitality Management', 3, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(10, 'BS in Culinary Management', 'BSCM', 'Bachelor of Science in Culinary Management', 3, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(11, 'BS in Tourism Management', 'BSTM', 'Bachelor of Science in Tourism Management', 3, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(12, 'ABM', 'ABM', 'Accountancy, Business, Management', 4, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(13, 'STEM', 'STEM', 'Science, Technology, Engineering, and Mathematics', 4, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(14, 'HUMSS', 'HUMSS', 'Humanities and Social Sciences', 4, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(15, 'GA', 'GA', 'General Academic', 4, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(16, 'Digital Arts', 'DIGARTS', 'Digital Arts', 5, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(17, 'IT in Mobile app and Web development', 'MAWD', 'IT in Mobile app and Web development', 5, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(18, 'Tourism Operations', 'TOURISM', 'Tourism Operations', 6, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(19, 'Restaurant and Cafe Operations', 'RESTCAFE', 'Restaurant and Cafe Operations', 6, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16'),
(20, 'Culinary Arts', 'CULINARY', 'Culinary Arts', 6, 1, '2025-08-13 19:48:16', '2025-08-13 19:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `rejection_reasons`
--

CREATE TABLE `rejection_reasons` (
  `reason_id` int(11) NOT NULL,
  `reason_name` varchar(100) NOT NULL,
  `reason_category` enum('student','faculty','both') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rejection_reasons`
--

INSERT INTO `rejection_reasons` (`reason_id`, `reason_name`, `reason_category`, `is_active`, `created_at`) VALUES
(1, 'Incomplete Documents', 'student', 1, '2025-08-13 19:48:16'),
(2, 'Unpaid Fees', 'student', 1, '2025-08-13 19:48:16'),
(3, 'Academic Requirements Not Met', 'student', 1, '2025-08-13 19:48:16'),
(4, 'Disciplinary Issues', 'student', 1, '2025-08-13 19:48:16'),
(5, 'Missing Clearance Items', 'student', 1, '2025-08-13 19:48:16'),
(6, 'Incomplete Documents', 'faculty', 1, '2025-08-13 19:48:16'),
(7, 'Unpaid Obligations', 'faculty', 1, '2025-08-13 19:48:16'),
(8, 'Employment Requirements Not Met', 'faculty', 1, '2025-08-13 19:48:16'),
(9, 'Disciplinary Issues', 'faculty', 1, '2025-08-13 19:48:16'),
(10, 'Missing Clearance Items', 'faculty', 1, '2025-08-13 19:48:16'),
(11, 'Contract/Employment Issues', 'faculty', 1, '2025-08-13 19:48:16'),
(12, 'Other', 'both', 1, '2025-08-13 19:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `rejection_remarks`
--

CREATE TABLE `rejection_remarks` (
  `remark_id` int(11) NOT NULL,
  `signatory_id` int(11) DEFAULT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `additional_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL COMMENT 'Admin, Staff, Student, Faculty',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'Full system access and control', 1, '2025-08-13 19:48:16'),
(2, 'Staff', 'All staff members (cashier, librarian, program head, school admin)', 1, '2025-08-13 19:48:16'),
(3, 'Student', 'Student users applying for clearance', 1, '2025-08-13 19:48:16'),
(4, 'Faculty', 'Faculty members applying for clearance', 1, '2025-08-13 19:48:16'),
(5, 'School Administrator', 'Role for School Administrator', 1, '2025-08-14 04:37:48'),
(6, 'Program Head', 'Role for Program Head', 1, '2025-08-14 04:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) DEFAULT NULL COMMENT 'Admin who granted this permission'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `granted_at`, `granted_by`) VALUES
(1, 1, '2025-08-14 05:22:22', 1),
(1, 2, '2025-08-14 05:22:22', 1),
(1, 3, '2025-08-14 05:22:22', 1),
(1, 4, '2025-08-14 05:22:22', 1),
(1, 5, '2025-08-14 05:22:22', 1),
(1, 6, '2025-08-14 05:22:22', 1),
(1, 7, '2025-08-14 05:22:22', 1),
(1, 8, '2025-08-14 05:22:22', 1),
(1, 9, '2025-08-14 05:22:22', 1),
(1, 10, '2025-08-14 05:22:22', 1),
(1, 11, '2025-08-14 05:22:22', 1),
(1, 12, '2025-08-14 05:22:22', 1),
(1, 13, '2025-08-14 05:22:22', 1),
(1, 14, '2025-08-14 05:22:22', 1),
(1, 15, '2025-08-14 05:22:22', 1),
(1, 16, '2025-08-14 05:22:22', 1),
(1, 17, '2025-08-14 05:22:22', 1),
(1, 18, '2025-08-14 05:22:22', 1),
(1, 19, '2025-08-14 05:22:22', 1),
(1, 20, '2025-08-14 05:22:22', 1),
(1, 21, '2025-08-14 09:05:57', NULL),
(1, 22, '2025-08-14 09:05:57', NULL),
(1, 23, '2025-08-14 09:05:57', NULL),
(1, 24, '2025-08-14 09:05:57', NULL),
(1, 25, '2025-08-14 09:05:57', NULL),
(1, 26, '2025-08-14 09:05:57', NULL),
(1, 27, '2025-08-14 11:46:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` enum('1st','2nd','Summer') NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `is_generation` tinyint(1) DEFAULT 0 COMMENT 'Active for clearance generation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `academic_year_id`, `is_active`, `is_generation`, `created_at`, `updated_at`) VALUES
(4, '', 1, 1, 0, '2025-08-15 03:37:21', '2025-08-15 11:18:43'),
(5, '', NULL, 0, 0, '2025-08-15 03:37:22', '2025-08-15 03:37:22'),
(6, 'Summer', NULL, 0, 0, '2025-08-15 03:37:22', '2025-08-15 03:37:22');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee Number format: LCA123P',
  `user_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `staff_category` enum('Regular Staff','Program Head','School Administrator') NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'For program heads and department-specific staff',
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') DEFAULT NULL COMMENT 'For faculty-staff dual roles',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(11) NOT NULL COMMENT 'Student number format: 02000288322',
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL COMMENT 'e.g., "4/1-1", "3/1-2"',
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT NULL,
  `enrollment_status` enum('Enrolled','Graduated','Transferred','Dropped') DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL COMMENT 'e.g., "clearance_period_duration", "max_bulk_operation_size"',
  `setting_value` text DEFAULT NULL COMMENT 'Value of the setting',
  `setting_type` enum('string','integer','boolean','json','decimal') DEFAULT 'string',
  `description` text DEFAULT NULL COMMENT 'Human-readable description',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT 'Whether admins can change this setting',
  `category` varchar(50) DEFAULT NULL COMMENT 'Grouping for settings (security, performance, features)',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who last modified this setting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_at`, `updated_by`) VALUES
(1, 'max_bulk_operation_size', '500', 'integer', 'Maximum number of records that can be processed in a single bulk operation', 1, 'performance', '2025-08-13 19:48:16', NULL),
(2, 'clearance_period_duration_days', '30', 'integer', 'Default duration of clearance periods in days', 1, 'features', '2025-08-13 19:48:16', NULL),
(3, 'audit_log_retention_days', '365', 'integer', 'Number of days to retain audit logs', 1, 'security', '2025-08-13 19:48:16', NULL),
(4, 'file_upload_max_size_mb', '10', 'integer', 'Maximum file upload size in megabytes', 1, 'security', '2025-08-13 19:48:16', NULL),
(5, 'session_timeout_minutes', '480', 'integer', 'User session timeout in minutes (8 hours)', 1, 'security', '2025-08-13 19:48:16', NULL),
(6, 'enable_email_notifications', 'false', 'boolean', 'Enable email notifications for system events', 1, 'features', '2025-08-13 19:48:16', NULL),
(7, 'enable_sms_notifications', 'false', 'boolean', 'Enable SMS notifications for system events', 1, 'features', '2025-08-13 19:48:16', NULL),
(8, 'maintenance_mode', 'false', 'boolean', 'Enable maintenance mode for system updates', 1, 'system', '2025-08-13 19:48:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'Student number for students, Employee ID for staff/faculty',
  `password` varchar(255) NOT NULL COMMENT 'Hashed password',
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL COMMENT 'For admin-initiated password resets',
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `middle_name`, `contact_number`, `profile_picture`, `status`, `last_login`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$MJzbYK1lT6ijvH9E83ZGq.zjWoW.fHT0o9ZzfywwvkeQe54TgLUL.', 'admin@gosti.edu.ph', 'System', 'Administrator', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 00:05:29', '2025-08-14 00:05:29'),
(2, 'schooladmin', '$2y$10$c4CmCPtvAP.BE5lN2Uxha.s6kN8W4Oe92aLKQUYz6o7wEfhrClRPm', 'schooladmin@gosti.edu.ph', 'Dr. Robert', 'Johnson', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(3, 'programhead', '$2y$10$2fCDqcOdv9bv5EL7Hex1TuzZ8e7BUmotg6YnQtlJQ4ipRUZ/h93Ry', 'programhead@gosti.edu.ph', 'Prof. Maria', 'Santos', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(4, 'faculty1', '$2y$10$QytX2m4BtqEuQ3km3lgcc.Xem/O.CkhVcNFktJb5qacv0S6cBi9Ee', 'faculty1@gosti.edu.ph', 'Prof. Juan', 'Dela Cruz', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(5, 'student1', '$2y$10$/9dAN4tNhs1kMFXqM8aCAupk1J3ou4GBb65W5Y8By9lO9aHx3hcd.', 'student1@gosti.edu.ph', 'Zinzu Chan', 'Lee', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(6, 'testuser', '$2y$10$v0Q1u8ovMeM3rwuWwNNkwu359zq2YXLj7FJ4QLNvReWorieVSqkmG', 'test@example.com', 'Test', 'User', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 05:23:54', '2025-08-14 05:23:54'),
(7, 'testuser2', '$2y$10$KfTgd/gvYOAUpf8DKP8dsuod7bGZLhjffon9qNV6Jg53l9mmBi9Ba', 'test2@example.com', 'Test2', 'User2', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-08-14 05:33:56', '2025-08-14 05:33:56');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT 'e.g., "Login", "Clearance Apply", "Profile Update"',
  `activity_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional activity information' CHECK (json_valid(`activity_details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 00:06:42'),
(2, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 00:08:30'),
(3, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:39:06'),
(4, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:41:12'),
(5, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:41:32'),
(6, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:41:52'),
(7, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:42:15'),
(8, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:45:17'),
(9, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:48:47'),
(10, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:07:59'),
(11, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:08:21'),
(12, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:08:50'),
(13, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:10:29'),
(14, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:10:59'),
(15, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:19:55'),
(16, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:23:40'),
(17, 6, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:23:54'),
(18, 7, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-14 05:33:56'),
(19, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-14 08:03:58'),
(20, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-14 08:07:09'),
(21, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-14 10:41:56'),
(22, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-14 11:03:28'),
(23, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-14 11:46:16'),
(24, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-15 04:05:38'),
(25, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 05:18:05'),
(26, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 05:23:53'),
(27, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:24:29'),
(28, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:24:40'),
(29, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:25:05'),
(30, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:25:24'),
(31, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:28:24'),
(32, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:31:04'),
(33, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:31:25'),
(34, 2, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:31:50'),
(35, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:32:09'),
(36, 3, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 05:50:36'),
(37, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-15 06:20:39'),
(38, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 17:13:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL COMMENT 'Admin who assigned this role',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT 'Primary role for the user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`, `assigned_by`, `is_primary`) VALUES
(1, 1, '2025-08-14 00:05:29', NULL, 0),
(2, 5, '2025-08-14 04:37:49', NULL, 0),
(3, 6, '2025-08-14 04:37:49', NULL, 0),
(4, 4, '2025-08-14 04:37:49', NULL, 0),
(5, 3, '2025-08-14 04:37:49', NULL, 0),
(6, 1, '2025-08-14 05:23:54', NULL, 0),
(7, 3, '2025-08-14 05:33:56', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`academic_year_id`),
  ADD UNIQUE KEY `year` (`year`),
  ADD KEY `idx_academic_years_active` (`is_active`),
  ADD KEY `idx_academic_years_year` (`year`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_action` (`action`),
  ADD KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_logs_created` (`created_at`),
  ADD KEY `idx_audit_logs_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  ADD PRIMARY KEY (`operation_id`),
  ADD KEY `idx_bulk_operations_user` (`user_id`),
  ADD KEY `idx_bulk_operations_type` (`operation_type`),
  ADD KEY `idx_bulk_operations_status` (`status`),
  ADD KEY `idx_bulk_operations_started` (`started_at`),
  ADD KEY `idx_bulk_operations_user_status` (`user_id`,`status`);

--
-- Indexes for table `clearance_applications`
--
ALTER TABLE `clearance_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `unique_user_period` (`user_id`,`period_id`),
  ADD KEY `period_id` (`period_id`);

--
-- Indexes for table `clearance_forms`
--
ALTER TABLE `clearance_forms`
  ADD PRIMARY KEY (`clearance_form_id`),
  ADD UNIQUE KEY `unique_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `idx_clearance_forms_user` (`user_id`),
  ADD KEY `idx_clearance_forms_period` (`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_forms_status` (`status`),
  ADD KEY `idx_clearance_forms_type` (`clearance_type`),
  ADD KEY `idx_clearance_forms_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_form_id` (`clearance_form_id`);

--
-- Indexes for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `unique_active_period` (`is_active`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `idx_clearance_periods_active` (`is_active`),
  ADD KEY `idx_clearance_periods_dates` (`start_date`,`end_date`);

--
-- Indexes for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `designation_id` (`designation_id`),
  ADD KEY `idx_clearance_requirements_type` (`clearance_type`),
  ADD KEY `idx_clearance_requirements_order` (`order_sequence`),
  ADD KEY `idx_clearance_requirements_department_specific` (`is_department_specific`);

--
-- Indexes for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  ADD PRIMARY KEY (`signatory_id`),
  ADD KEY `actual_user_id` (`actual_user_id`),
  ADD KEY `idx_clearance_signatories_form` (`clearance_form_id`),
  ADD KEY `idx_clearance_signatories_designation` (`designation_id`),
  ADD KEY `idx_clearance_signatories_action` (`action`),
  ADD KEY `idx_clearance_signatories_date` (`date_signed`),
  ADD KEY `idx_clearance_signatories_form_designation` (`clearance_form_id`,`designation_id`);

--
-- Indexes for table `clearance_signatory_status`
--
ALTER TABLE `clearance_signatory_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `unique_application_requirement` (`application_id`,`requirement_id`),
  ADD KEY `requirement_id` (`requirement_id`),
  ADD KEY `signed_by_user_id` (`signed_by_user_id`);

--
-- Indexes for table `data_versions`
--
ALTER TABLE `data_versions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_data_versions_type` (`data_type`),
  ADD KEY `idx_data_versions_operation` (`operation_type`),
  ADD KEY `idx_data_versions_status` (`status`),
  ADD KEY `idx_data_versions_started` (`started_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `idx_departments_type` (`department_type`),
  ADD KEY `idx_departments_active` (`is_active`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`designation_id`),
  ADD KEY `idx_designations_active` (`is_active`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`employee_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_faculty_department` (`department_id`),
  ADD KEY `idx_faculty_status` (`employment_status`),
  ADD KEY `idx_faculty_department_status` (`department_id`,`employment_status`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_file_uploads_user` (`user_id`),
  ADD KEY `idx_file_uploads_category` (`file_category`),
  ADD KEY `idx_file_uploads_uploaded` (`uploaded_at`),
  ADD KEY `idx_file_uploads_category_active` (`file_category`,`is_active`);

--
-- Indexes for table `login_sessions`
--
ALTER TABLE `login_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_login_sessions_user` (`user_id`),
  ADD KEY `idx_login_sessions_active` (`is_active`),
  ADD KEY `idx_login_sessions_last_activity` (`last_activity`);

--
-- Indexes for table `operation_logs`
--
ALTER TABLE `operation_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_operation_logs_operation` (`operation_id`),
  ADD KEY `idx_operation_logs_target` (`target_type`,`target_id`),
  ADD KEY `idx_operation_logs_result` (`result`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`),
  ADD KEY `idx_permissions_category` (`category`),
  ADD KEY `idx_permissions_active` (`is_active`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_programs_department` (`department_id`),
  ADD KEY `idx_programs_active` (`is_active`);

--
-- Indexes for table `rejection_reasons`
--
ALTER TABLE `rejection_reasons`
  ADD PRIMARY KEY (`reason_id`),
  ADD KEY `idx_rejection_reasons_category` (`reason_category`),
  ADD KEY `idx_rejection_reasons_active` (`is_active`);

--
-- Indexes for table `rejection_remarks`
--
ALTER TABLE `rejection_remarks`
  ADD PRIMARY KEY (`remark_id`),
  ADD KEY `reason_id` (`reason_id`),
  ADD KEY `idx_rejection_remarks_signatory` (`signatory_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_roles_name` (`role_name`),
  ADD KEY `idx_roles_active` (`is_active`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD UNIQUE KEY `unique_active_semester` (`academic_year_id`,`is_active`),
  ADD KEY `idx_semesters_active` (`is_active`),
  ADD KEY `idx_semesters_generation` (`is_generation`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`employee_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_staff_designation` (`designation_id`),
  ADD KEY `idx_staff_category` (`staff_category`),
  ADD KEY `idx_staff_department` (`department_id`),
  ADD KEY `idx_staff_active` (`is_active`),
  ADD KEY `idx_staff_category_department` (`staff_category`,`department_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_students_program` (`program_id`),
  ADD KEY `idx_students_department` (`department_id`),
  ADD KEY `idx_students_year_level` (`year_level`),
  ADD KEY `idx_students_status` (`enrollment_status`),
  ADD KEY `idx_students_program_year` (`program_id`,`year_level`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_system_settings_category` (`category`),
  ADD KEY `idx_system_settings_editable` (`is_editable`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_last_login` (`last_login`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_user_activities_user` (`user_id`),
  ADD KEY `idx_user_activities_type` (`activity_type`),
  ADD KEY `idx_user_activities_created` (`created_at`),
  ADD KEY `idx_user_activities_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_user_roles_primary` (`user_id`,`is_primary`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  MODIFY `operation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_applications`
--
ALTER TABLE `clearance_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_signatory_status`
--
ALTER TABLE `clearance_signatory_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `data_versions`
--
ALTER TABLE `data_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `designation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `operation_logs`
--
ALTER TABLE `operation_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rejection_reasons`
--
ALTER TABLE `rejection_reasons`
  MODIFY `reason_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `rejection_remarks`
--
ALTER TABLE `rejection_remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  ADD CONSTRAINT `bulk_operations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `clearance_applications`
--
ALTER TABLE `clearance_applications`
  ADD CONSTRAINT `clearance_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `clearance_applications_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `clearance_periods` (`period_id`);

--
-- Constraints for table `clearance_forms`
--
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  ADD CONSTRAINT `clearance_periods_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_periods_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  ADD CONSTRAINT `clearance_requirements_ibfk_1` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  ADD CONSTRAINT `clearance_signatories_ibfk_1` FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_3` FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `clearance_signatory_status`
--
ALTER TABLE `clearance_signatory_status`
  ADD CONSTRAINT `clearance_signatory_status_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `clearance_applications` (`application_id`),
  ADD CONSTRAINT `clearance_signatory_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `clearance_requirements` (`requirement_id`),
  ADD CONSTRAINT `clearance_signatory_status_ibfk_3` FOREIGN KEY (`signed_by_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `data_versions`
--
ALTER TABLE `data_versions`
  ADD CONSTRAINT `data_versions_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`file_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `data_versions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `login_sessions`
--
ALTER TABLE `login_sessions`
  ADD CONSTRAINT `login_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `operation_logs`
--
ALTER TABLE `operation_logs`
  ADD CONSTRAINT `operation_logs_ibfk_1` FOREIGN KEY (`operation_id`) REFERENCES `bulk_operations` (`operation_id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `rejection_remarks`
--
ALTER TABLE `rejection_remarks`
  ADD CONSTRAINT `rejection_remarks_ibfk_1` FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories` (`signatory_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rejection_remarks_ibfk_2` FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
