-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 11:09 AM
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
(10, '2025-2026', 1, '2025-09-01 17:54:25', '2025-09-01 17:54:25');

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
  `status` enum('Unapplied','Applied','In Progress','Completed','Rejected') DEFAULT 'Unapplied',
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
('CF-2025-00001', 15, 10, 24, 'Faculty', 'Applied', NULL, NULL, NULL, '2025-09-01 22:36:53', '2025-09-01 22:39:03');

--
-- Triggers `clearance_forms`
--
DELIMITER $$
CREATE TRIGGER `generate_clearance_form_id` BEFORE INSERT ON `clearance_forms` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    DECLARE year_part CHAR(4);

    SET year_part = YEAR(CURDATE());

    SELECT COALESCE(
             MAX(
               CAST(SUBSTRING_INDEX(clearance_form_id, '-', -1) AS UNSIGNED)
             ),
             0
           ) + 1
      INTO next_id
      FROM clearance_forms
      WHERE clearance_form_id LIKE CONCAT('CF-', year_part, '-%');

    SET NEW.clearance_form_id =
         CONCAT('CF-', year_part, '-', LPAD(next_id,5,'0'));
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
  `ended_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `status` enum('inactive','active','deactivated','ended') NOT NULL DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_periods`
--

INSERT INTO `clearance_periods` (`period_id`, `academic_year_id`, `semester_id`, `period_name`, `start_date`, `end_date`, `ended_at`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(14, 10, 24, NULL, '2025-09-01', '2025-12-31', NULL, 1, 'active', '2025-09-01 21:15:39', '2025-09-01 22:36:53');

--
-- Triggers `clearance_periods`
--
DELIMITER $$
CREATE TRIGGER `enforce_single_active_clearance_period` BEFORE UPDATE ON `clearance_periods` FOR EACH ROW BEGIN
  IF NEW.is_active = 1 THEN
    IF (SELECT COUNT(*) FROM clearance_periods WHERE is_active = 1 AND period_id <> NEW.period_id) > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Another clearance period is already active';
    END IF;
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

--
-- Dumping data for table `clearance_signatories`
--

INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `rejection_reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(150, 'CF-2025-00001', 1, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-02 00:44:36'),
(151, 'CF-2025-00001', 3, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(152, 'CF-2025-00001', 4, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(153, 'CF-2025-00001', 9, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(154, 'CF-2025-00001', 12, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(155, 'CF-2025-00001', 14, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(156, 'CF-2025-00001', 15, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03'),
(157, 'CF-2025-00001', 16, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-09-01 22:39:03', '2025-09-01 22:39:03');

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
  `department_type` enum('College','Senior High School','Faculty') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sector_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `department_type`, `is_active`, `created_at`, `updated_at`, `sector_id`) VALUES
(44, 'Information & Communication Technology', 'ICT', 'College', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 1),
(45, 'Business, Arts, & Science', 'BAS', 'College', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 1),
(46, 'Tourism & Hospitality Management', 'THM', 'College', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 1),
(47, 'Academic Track', 'ACAD', 'Senior High School', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 2),
(48, 'Technological-Vocational Livelihood', 'TVL', 'Senior High School', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 2),
(49, 'Home Economics', 'HE', 'Senior High School', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 2),
(50, 'General Education', 'GE', 'Faculty', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 3);

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
(9, 'School Administrator', 'School administration staff', 1, '2025-08-13 19:48:16'),
(10, 'PAMO', 'Purchasing and Assets Management Officer', 1, '2025-09-01 15:53:23'),
(11, 'Petty Cash Custodian', 'Petty Cash Custodian', 1, '2025-09-01 15:53:23'),
(12, 'Accountant', 'Accounting staff', 1, '2025-09-01 15:53:23'),
(13, 'Academic Head', 'Academic Head', 1, '2025-09-01 15:53:23'),
(14, 'Guidance', 'Guidance office', 1, '2025-09-01 15:53:23'),
(15, 'Disciplinary Officer', 'Disciplinary office', 1, '2025-09-01 15:53:23'),
(16, 'Clinic', 'Clinic staff', 1, '2025-09-01 15:53:23'),
(17, 'Alumni Placement Officer', 'Alumni Placement Officer', 1, '2025-09-01 15:53:23');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee ID format: LCA123P',
  `user_id` int(11) DEFAULT NULL,
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`employee_number`, `user_id`, `employment_status`, `department_id`, `created_at`, `updated_at`) VALUES
('LCA123P', 15, 'Full Time', NULL, '2025-08-23 11:18:12', '2025-08-23 11:18:12'),
('LCA125P', 34, 'Part Time - Full Load', NULL, '2025-08-25 18:20:51', '2025-08-26 07:17:35');

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
(1, 27, '2025-08-14 11:46:03', NULL),
(6, 17, '2025-08-24 17:46:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `scope_settings`
--

CREATE TABLE `scope_settings` (
  `clearance_type` varchar(16) NOT NULL,
  `include_program_head` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `required_first_enabled` tinyint(1) DEFAULT 0,
  `required_first_designation_id` int(11) DEFAULT NULL,
  `required_last_enabled` tinyint(1) DEFAULT 0,
  `required_last_designation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scope_settings`
--

INSERT INTO `scope_settings` (`clearance_type`, `include_program_head`, `updated_at`, `required_first_enabled`, `required_first_designation_id`, `required_last_enabled`, `required_last_designation_id`) VALUES
('faculty', 0, '2025-09-01 18:57:27', 1, 12, 1, 1),
('student', 0, '2025-09-01 18:56:32', 1, 2, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `sector_id` int(11) NOT NULL,
  `sector_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`sector_id`, `sector_name`, `created_at`, `updated_at`) VALUES
(1, 'College', '2025-08-31 01:33:32', '2025-09-02 00:00:58'),
(2, 'Senior High School', '2025-08-31 01:33:32', '2025-09-02 00:00:58'),
(3, 'Faculty', '2025-08-31 01:33:32', '2025-09-02 00:00:58');

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
(24, '1st', 10, 0, 0, '2025-09-01 17:54:25', '2025-09-01 17:54:25'),
(25, '2nd', 10, 0, 0, '2025-09-01 17:54:25', '2025-09-01 17:54:25');

-- --------------------------------------------------------

--
-- Table structure for table `signatory_assignments`
--

CREATE TABLE `signatory_assignments` (
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `clearance_type` enum('student','faculty') DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `signatory_assignments`
--

INSERT INTO `signatory_assignments` (`assignment_id`, `user_id`, `designation_id`, `clearance_type`, `department_id`, `sector_id`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 57, 8, NULL, 44, 1, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(4, 57, 8, NULL, 45, 1, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(5, 57, 8, NULL, 46, 1, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(6, 58, 8, NULL, 47, 2, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(7, 58, 8, NULL, 48, 2, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(8, 58, 8, NULL, 49, 2, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(9, 59, 8, NULL, 50, 3, 1, '2025-09-02 00:26:42', '2025-09-02 00:26:42'),
(10, 56, 2, 'student', NULL, NULL, 1, '2025-09-02 00:38:05', '2025-09-02 00:38:05'),
(11, 56, 2, 'faculty', NULL, NULL, 0, '2025-09-02 00:38:12', '2025-09-02 03:49:37'),
(12, 51, 16, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(13, 50, 15, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(14, 49, 14, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(15, 52, 3, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(16, 42, 4, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(17, 55, 1, 'student', NULL, NULL, 1, '2025-09-02 01:54:59', '2025-09-02 01:54:59'),
(18, 51, 16, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(19, 50, 15, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(20, 49, 14, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(21, 52, 3, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(22, 42, 4, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(23, 55, 1, 'faculty', NULL, NULL, 1, '2025-09-02 01:59:32', '2025-09-02 01:59:32'),
(24, 47, 9, 'faculty', NULL, NULL, 1, '2025-09-02 02:00:09', '2025-09-02 02:00:09'),
(25, 45, 12, 'faculty', NULL, NULL, 1, '2025-09-02 02:57:01', '2025-09-02 02:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee ID format: LCA123P',
  `user_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `staff_category` enum('Regular Staff','Program Head','School Administrator') NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'For program heads and department-specific staff',
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') DEFAULT NULL COMMENT 'For faculty-staff dual roles',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`employee_number`, `user_id`, `designation_id`, `staff_category`, `department_id`, `employment_status`, `is_active`, `created_at`, `updated_at`) VALUES
('LCA101P', 41, 10, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA102P', 42, 4, 'Regular Staff', NULL, NULL, 0, '2025-09-01 16:17:12', '2025-09-01 17:59:48'),
('LCA103P', 43, 11, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA104P', 44, 5, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA105P', 45, 12, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA106P', 46, 13, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA107P', 47, 9, 'School Administrator', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA108P', 48, 6, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA109P', 49, 14, 'Regular Staff', NULL, NULL, 0, '2025-09-01 16:17:12', '2025-09-01 17:59:36'),
('LCA110P', 50, 15, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:12', '2025-09-01 16:17:12'),
('LCA111P', 51, 16, 'Regular Staff', NULL, NULL, 0, '2025-09-01 16:17:13', '2025-09-01 19:34:30'),
('LCA112P', 52, 3, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('LCA113P', 53, 17, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('LCA114P', 54, 7, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('LCA115P', 55, 1, 'Regular Staff', NULL, NULL, 0, '2025-09-01 16:17:13', '2025-09-01 17:59:47'),
('LCA116P', 56, 2, 'Regular Staff', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('PHC101P', 57, 8, 'Program Head', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('PHF101P', 59, 8, 'Program Head', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13'),
('PHS101P', 58, 8, 'Program Head', NULL, NULL, 1, '2025-09-01 16:17:13', '2025-09-01 16:17:13');

--
-- Triggers `staff`
--
DELIMITER $$
CREATE TRIGGER `staff_bi` BEFORE INSERT ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LLLDDDL)';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `staff_bu` BEFORE UPDATE ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LLLDDDL)';
  END IF;
END
$$
DELIMITER ;

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
  `email` varchar(255) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `can_apply` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL COMMENT 'For admin-initiated password resets',
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `middle_name`, `contact_number`, `profile_picture`, `status`, `must_change_password`, `can_apply`, `last_login`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$oYW.w7Cc6IolC.V.5YgDN.w95otXDlxDiTTFjyHNDCqsRWsR1Z9wO', 'admin@gosti.edu.ph', 'System', 'Administrator', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 00:05:29', '2025-08-28 17:34:13'),
(2, 'schooladmin', '$2y$10$c4CmCPtvAP.BE5lN2Uxha.s6kN8W4Oe92aLKQUYz6o7wEfhrClRPm', 'schooladmin@gosti.edu.ph', 'Dr. Robert', 'Johnson', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(3, 'programhead', '$2y$10$2fCDqcOdv9bv5EL7Hex1TuzZ8e7BUmotg6YnQtlJQ4ipRUZ/h93Ry', 'programhead@gosti.edu.ph', 'Prof. Maria', 'Santos', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(4, 'faculty1', '$2y$10$QytX2m4BtqEuQ3km3lgcc.Xem/O.CkhVcNFktJb5qacv0S6cBi9Ee', 'faculty1@gosti.edu.ph', 'Prof. Juan', 'Dela Cruz', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(5, 'student1', '$2y$10$/9dAN4tNhs1kMFXqM8aCAupk1J3ou4GBb65W5Y8By9lO9aHx3hcd.', 'student1@gosti.edu.ph', 'Zinzu Chan', 'Lee', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 04:37:49', '2025-08-14 04:37:49'),
(6, 'testuser', '$2y$10$v0Q1u8ovMeM3rwuWwNNkwu359zq2YXLj7FJ4QLNvReWorieVSqkmG', 'test@example.com', 'Test', 'User', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 05:23:54', '2025-08-14 05:23:54'),
(7, 'testuser2', '$2y$10$KfTgd/gvYOAUpf8DKP8dsuod7bGZLhjffon9qNV6Jg53l9mmBi9Ba', 'test2@example.com', 'Test2', 'User2', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-14 05:33:56', '2025-08-14 05:33:56'),
(15, 'LCA123P', '$2y$10$rtH12Zf61cvI5XuINsNBhOxZnklO7964fxENPwqlniMTj5MIAcWGi', 'LCA123P@placeholder.local', 'Jograd', 'Manampalo', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-23 11:18:12', '2025-08-28 17:29:14'),
(33, 'LCA124P', '$2y$10$13.9w2KEQwzCSp/y1nkBROP9/l6oIaBUnYiBzqPGsHJaySe6Z777W', 'juan.updated@example.com', 'Juan', 'Dela Cruz', 'Santos', '', NULL, '', 1, 1, NULL, NULL, NULL, '2025-08-25 18:20:51', '2025-08-26 09:03:13'),
(34, 'LCA125P', '$2y$10$3.YIzuBS4OuQXa0UmOww6eyEBwfjn7TbyV5aw/0B2dTJzEpev8NQO', '', 'Ana', 'Rodriguez', '', '', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-08-25 18:20:51', '2025-08-26 07:32:33'),
(41, 'LCA101P', '$2y$10$BDV5V24xV8oiDmmklNpzxesa9y.Wgz8Pnc6gKfTwpqGV0jOJXxEKO', 'pamo@gosti.seed', 'Test', 'Pamo', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(42, 'LCA102P', '$2y$10$7Zp2nWGYYtEjz5wWqPfLjOeBeqdsT8sNg0ypGvR9DFVDO5HOw/KLO', 'misit@gosti.seed', 'Test', 'Misit', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(43, 'LCA103P', '$2y$10$1/H602BsYCJPDxS8OWt.YeLuda1a4O1LanSV/mdmM43spNbOc5R/C', 'pettycash@gosti.seed', 'Test', 'Pettycash', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(44, 'LCA104P', '$2y$10$hcP0AdaI8KxOS4SxvHuWRedVp6Hpx3hXRTtNfC5sXnRSxD92Na.Z6', 'bldgadmin@gosti.seed', 'Test', 'Bldgadmin', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(45, 'LCA105P', '$2y$10$2LfAS2b/l7A3k9wrooNL6OqU36AEQDM7g4lJITWPFS1a6Z4rU7HfK', 'accountant@gosti.seed', 'Test', 'Accountant', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(46, 'LCA106P', '$2y$10$I8NEQLSgUlLmyzF0HOnAhuHQzjrV2ok6oO0KpDjJQ4Jc8bJgnERHu', 'acadhead@gosti.seed', 'Test', 'Acadhead', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(47, 'LCA107P', '$2y$10$LmVKpBSC8P6AJAcFwKLuQ.1L3ishGOcvlW1MfecxBmGn1zSBb4N3e', 'schooladmin@gosti.seed', 'Test', 'Schooladmin', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(48, 'LCA108P', '$2y$10$1Tw2vrrx2BCyhDC5w2YxzeuamHhnfSIBmlLiM0FFeW8vmiGt7ykma', 'hr@gosti.seed', 'Test', 'Hr', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(49, 'LCA109P', '$2y$10$ly/cDFr8ai8fg55cJoCZd..VVUO9Mk3iqq98LyeYykjh.IYI9Qtii', 'guidance@gosti.seed', 'Test', 'Guidance', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(50, 'LCA110P', '$2y$10$EXzxURUos4JwNMXdK4K8cefxqKE0QZF4qBInfvrnCrtOV/viQmU6K', 'discipline@gosti.seed', 'Test', 'Discipline', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(51, 'LCA111P', '$2y$10$1cxRUknSFpp7wPXxmzBJOOd83fFs4qlkKMXiITl1sxCLNx.sEz82.', 'clinic@gosti.seed', 'Test', 'Clinic', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:04'),
(52, 'LCA112P', '$2y$10$EkCoek9DMzNCNi664Mo4VeZvJxreLYmEMhaTxcNpghxVZOuUdoDsS', 'librarian@gosti.seed', 'Test', 'Librarian', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(53, 'LCA113P', '$2y$10$lDMhZLJ2HCRqc3M/FNKi5uCfPqjQQGtzoW4VVm5PGcPRThMX0eEEW', 'alumni@gosti.seed', 'Test', 'Alumni', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(54, 'LCA114P', '$2y$10$zI1tI.IwwH2x5GB1RWV/ZOr1s0ErO0noUWYrgrXo5C9M2/Xj8hQNS', 'sao@gosti.seed', 'Test', 'Sao', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(55, 'LCA115P', '$2y$10$xn3lwydRq9YJ.hqGl4USyubuwg2qi5w1n/R5d3pCxwz79MQSaRov.', 'registrar@gosti.seed', 'Test', 'Registrar', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(56, 'LCA116P', '$2y$10$jyj9/xOd5/miSHmHuGnoh.YVbUrY3Wxb8jNquKO9V2BHLrnXIIUMC', 'cashier@gosti.seed', 'Test', 'Cashier', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(57, 'PHC101P', '$2y$10$0zgcUI19ptFgkSj1v.qq5uc/S99LWn48pgmKmN3pf9/Vz1NajBARS', 'ph_college@gosti.seed', 'PH', 'College', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(58, 'PHS101P', '$2y$10$RlT87fTdvYCX5EPMclyEuuq/1jOG6wktUQzcuAF2k17YVauxNWV/m', 'ph_shs@gosti.seed', 'PH', 'Shs', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05'),
(59, 'PHF101P', '$2y$10$TWcKKOEYIBQfWeQDnf36KuVNxEm.IGhRVMA5zVX3ic5BwCKQUSuGq', 'ph_faculty@gosti.seed', 'PH', 'Faculty', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-01 16:17:12', '2025-09-01 17:29:05');

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
(38, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 17:13:13'),
(40, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 10:05:59'),
(41, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 10:27:30'),
(42, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-16 10:38:31'),
(43, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 10:46:17'),
(44, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 10:55:59'),
(45, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 11:26:11'),
(46, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 11:28:11'),
(47, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 11:28:39'),
(48, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 11:31:39'),
(49, 5, 'Global Apply', '{\"academic_year_id\":1,\"semester_id\":4}', '::1', NULL, '2025-08-16 11:31:39'),
(50, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-16 11:34:41'),
(51, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 12:52:41'),
(52, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 13:11:22'),
(53, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 13:28:33'),
(54, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 13:35:00'),
(55, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 04:48:11'),
(56, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 05:03:54'),
(57, 4, 'Global Apply', '{\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 05:03:59'),
(58, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-17 05:54:41'),
(59, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-17 05:54:44'),
(60, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 05:57:33'),
(61, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:03:02'),
(62, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:04:03'),
(63, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:04:04'),
(64, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-17 06:04:56'),
(65, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', NULL, '2025-08-17 06:04:56'),
(66, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', NULL, '2025-08-17 06:04:56'),
(67, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', NULL, '2025-08-17 06:04:56'),
(68, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-17 06:09:12'),
(69, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', NULL, '2025-08-17 06:09:12'),
(70, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', NULL, '2025-08-17 06:09:12'),
(71, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', NULL, '2025-08-17 06:09:12'),
(72, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-17 06:09:13'),
(73, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', NULL, '2025-08-17 06:09:13'),
(74, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', NULL, '2025-08-17 06:09:13'),
(75, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', NULL, '2025-08-17 06:09:13'),
(76, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:17:36'),
(77, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-17 06:18:33'),
(78, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', NULL, '2025-08-17 06:18:33'),
(79, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', NULL, '2025-08-17 06:18:33'),
(80, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', NULL, '2025-08-17 06:18:33'),
(81, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-17 06:18:35'),
(82, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', NULL, '2025-08-17 06:18:35'),
(83, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', NULL, '2025-08-17 06:18:35'),
(84, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', NULL, '2025-08-17 06:18:35'),
(85, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:22:52'),
(86, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:59:09'),
(87, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 06:59:39'),
(88, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:00:08'),
(89, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:06:58'),
(90, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:10:11'),
(91, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:10:59'),
(92, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:18:53'),
(93, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:25:48'),
(94, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:26:03'),
(95, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:27:54'),
(96, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:29:33'),
(97, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-17 07:44:55'),
(98, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 07:55:26'),
(99, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-17 07:56:17'),
(100, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:00:31'),
(101, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:02:30'),
(102, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:05:15'),
(103, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:12:10'),
(104, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:16:58'),
(105, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:21:45'),
(106, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:25:14'),
(107, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 08:30:25'),
(108, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 16:04:01'),
(109, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 17:01:36'),
(110, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 17:09:54'),
(111, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 19:10:46'),
(112, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 15:08:58'),
(113, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 15:09:07'),
(114, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 15:28:03'),
(115, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 15:28:06'),
(116, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 15:28:07'),
(117, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:19:10'),
(118, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:19:44'),
(119, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:19:53'),
(120, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:19:55'),
(121, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:19:56'),
(122, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:24:09'),
(123, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:24:25'),
(124, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:24:28'),
(125, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:24:30'),
(126, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 08:24:32'),
(127, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:01:52'),
(128, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:02:01'),
(129, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:09:19'),
(130, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:09:31'),
(131, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:30:38'),
(132, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 09:41:55'),
(133, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 10:06:22'),
(134, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 10:07:16'),
(135, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:16:04'),
(136, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:16:29'),
(137, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:30'),
(138, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:31'),
(139, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:33'),
(140, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:34'),
(141, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:40'),
(142, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:41'),
(143, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:21:42'),
(144, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 11:37:38'),
(145, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 11:52:13'),
(146, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 11:52:15'),
(147, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 11:52:17'),
(148, 4, 'Signatory Apply', '{\"form_id\":\"CF-2025-21474\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 11:52:22'),
(149, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:52:34'),
(150, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:52:35'),
(151, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:52:37'),
(152, 5, 'Signatory Apply', '{\"form_id\":\"CF-2025-21475\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 11:52:38'),
(153, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 12:00:41'),
(154, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 12:00:53'),
(155, 4, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21474\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-19 12:04:49'),
(156, 5, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21475\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-19 12:05:38'),
(157, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-19 12:25:35'),
(158, 5, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21475\",\"academic_year_id\":1,\"semester_id\":4}', '::1', NULL, '2025-08-19 12:25:35'),
(159, 4, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-08-19 12:25:35'),
(160, 4, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21474\",\"academic_year_id\":1,\"semester_id\":4}', '::1', NULL, '2025-08-19 12:25:35'),
(161, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 16:44:40'),
(168, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 06:31:22'),
(174, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-21 07:20:02'),
(199, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 08:31:20'),
(210, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:54:12'),
(220, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 07:04:59'),
(230, 15, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-23 11:18:12'),
(231, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-23 11:18:36'),
(232, 15, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21476\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-23 11:23:50'),
(233, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-23 11:24:16'),
(234, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-23 11:24:31'),
(235, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 02:58:51'),
(236, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 14:49:06'),
(237, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-24 14:49:36'),
(238, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 16:22:38'),
(239, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 16:22:43'),
(240, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-25 07:35:45'),
(241, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 08:09:01'),
(242, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 08:09:56'),
(243, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 15:24:53'),
(244, 34, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-25 18:23:00'),
(245, 34, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 07:15:07'),
(246, 34, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 07:16:10'),
(247, 34, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 07:20:13'),
(248, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-26 07:29:28'),
(249, 15, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21476\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-26 07:29:31'),
(250, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 07:29:57'),
(251, 34, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 07:32:33'),
(252, 33, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 09:01:38'),
(253, 33, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 09:01:50'),
(254, 33, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-26 09:02:06'),
(255, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 17:48:50'),
(256, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 17:26:58'),
(257, 34, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 17:27:28'),
(258, 34, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21477\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 17:27:34'),
(259, 15, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 17:29:14'),
(260, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 17:29:24'),
(261, 15, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-21476\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 17:29:28'),
(262, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 17:39:50'),
(263, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 17:54:47'),
(264, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 18:07:07'),
(265, 1, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-00001\",\"academic_year_id\":1,\"semester_id\":4}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 18:08:27'),
(266, 1, 'Signatory Apply', '{\"form_id\":\"CF-2025-00001\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 18:09:51'),
(267, 1, 'Signatory Apply', '{\"form_id\":\"CF-2025-00001\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 18:12:04'),
(268, 1, 'Signatory Apply', '{\"form_id\":\"CF-2025-00001\",\"designation_id\":2}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.4768', '2025-08-28 18:21:51'),
(269, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":1,\"semester_id\":4,\"period_id\":2}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-28 19:00:32'),
(270, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 20:19:47'),
(271, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":6,\"semester_id\":16,\"period_id\":6}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-28 21:46:05'),
(272, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":6,\"semester_id\":16,\"period_id\":6}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-28 21:46:14'),
(273, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 06:19:05'),
(274, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":6,\"semester_id\":16,\"period_id\":6}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 06:28:29'),
(275, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-29 06:53:53'),
(276, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":6,\"semester_id\":16,\"period_id\":6}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 07:19:21'),
(277, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":7,\"semester_id\":18,\"period_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 07:29:45'),
(278, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 05:35:20'),
(279, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 05:35:30'),
(280, 3, 'reset_clearance_period_by_id', '{\"academic_year_id\":8,\"semester_id\":20,\"period_id\":10}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 05:54:35'),
(281, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 16:50:20'),
(282, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 20:02:07'),
(284, 3, 'Staff Registered', '{\"target_user_id\":35,\"employee_id\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:00:38'),
(285, 3, 'staff_registered', '{\"employee_id\":\"ABC123P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:00:38'),
(286, 3, 'Bulk Signatory Assign', '{\"results\":[{\"user_id\":35,\"status\":\"inserted\",\"employee_id\":\"EMP00004\"},{\"user_id\":35,\"status\":\"updated\",\"employee_id\":\"EMP00004\"},{\"user_id\":35,\"status\":\"updated\",\"employee_id\":\"EMP00004\"}]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:00:38'),
(287, 3, 'program_head_assigned', '{\"user_id\":35,\"department_ids\":[38,37,39],\"transfer\":true}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:00:38'),
(289, 3, 'Staff Registered', '{\"target_user_id\":36,\"employee_id\":\"ABC123P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:52:14'),
(290, 3, 'staff_registered', '{\"employee_id\":\"ABC123P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:52:14'),
(291, 3, 'Bulk Signatory Assign', '{\"results\":[{\"user_id\":36,\"status\":\"updated\",\"employee_id\":\"ABC123P\"}]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:52:14'),
(292, 3, 'program_head_assigned', '{\"user_id\":36,\"department_ids\":[43],\"transfer\":true}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 08:52:14'),
(295, 3, 'Staff Registered', '{\"target_user_id\":37,\"employee_id\":\"ABC123P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:01:04'),
(296, 3, 'staff_registered', '{\"employee_id\":\"ABC123P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:01:04'),
(297, 3, 'Bulk Signatory Assign', '{\"results\":[{\"user_id\":37,\"status\":\"updated\",\"employee_id\":\"ABC123P\"}]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:01:05'),
(298, 3, 'program_head_assigned', '{\"user_id\":37,\"department_ids\":[43],\"transfer\":true}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:01:05'),
(301, 3, 'Staff Registered', '{\"target_user_id\":38,\"employee_id\":\"ABC123P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:43:42'),
(302, 3, 'Bulk Signatory Assign', '{\"results\":[{\"user_id\":38,\"status\":\"updated\",\"employee_id\":\"ABC123P\"}]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:43:42'),
(303, 3, 'staff_registered', '{\"employee_id\":\"ABC123P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:43:42'),
(304, 3, 'program_head_assigned', '{\"user_id\":38,\"department_ids\":[43],\"transfer\":true}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 09:43:42'),
(308, 3, 'Staff Registered', '{\"target_user_id\":39,\"employee_id\":\"ABC234P\",\"designation\":\"Cashier\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 10:28:55'),
(309, 3, 'staff_registered', '{\"employee_id\":\"ABC234P\",\"name\":null,\"designation\":\"Cashier\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 10:28:55'),
(310, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-31 11:29:36'),
(311, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 17:37:35');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(312, 3, 'Scope Settings Update', '{\"clearance_type\":\"student\",\"include_program_head\":0}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-08-31 18:14:18'),
(313, 3, 'Scope Settings Update', '{\"clearance_type\":\"student\",\"include_program_head\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:40:55'),
(314, 3, 'Signatory Assign (Scope)', '{\"user_id\":39,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:40:55'),
(315, 3, 'Signatory Unassign', '{\"target_user_id\":39,\"employee_id\":\"ABC234P\",\"designation\":\"Cashier\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:41:03'),
(317, 3, 'Staff Registered', '{\"target_user_id\":40,\"employee_id\":\"QWE123P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:52:17'),
(318, 3, 'staff_registered', '{\"employee_id\":\"QWE123P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:52:17'),
(319, 3, 'Bulk Signatory Assign', '{\"results\":[{\"user_id\":40,\"status\":\"updated\",\"employee_id\":\"QWE123P\"},{\"user_id\":40,\"status\":\"updated\",\"employee_id\":\"QWE123P\"},{\"user_id\":40,\"status\":\"updated\",\"employee_id\":\"QWE123P\"}]}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:52:17'),
(320, 3, 'program_head_assigned', '{\"user_id\":40,\"department_ids\":[38,37,39],\"transfer\":true}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 06:52:17'),
(321, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 09:31:32'),
(322, 3, 'Scope Settings Update', '{\"clearance_type\":\"student\",\"include_program_head\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 16:38:05'),
(323, 3, 'Signatory Assign (Scope)', '{\"user_id\":56,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 16:38:05'),
(324, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 16:38:12'),
(325, 3, 'Signatory Assign (Scope)', '{\"user_id\":56,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 16:38:12'),
(326, 57, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-01 17:29:47'),
(327, 3, 'Scope Settings Update', '{\"clearance_type\":\"student\",\"include_program_head\":1,\"required_first_enabled\":0,\"required_first_designation_id\":null,\"required_last_enabled\":0,\"required_last_designation_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(328, 3, 'Signatory Assign (Scope)', '{\"user_id\":51,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(329, 3, 'Signatory Assign (Scope)', '{\"user_id\":50,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(330, 3, 'Signatory Assign (Scope)', '{\"user_id\":49,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(331, 3, 'Signatory Assign (Scope)', '{\"user_id\":52,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(332, 3, 'Signatory Assign (Scope)', '{\"user_id\":42,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(333, 3, 'Signatory Assign (Scope)', '{\"user_id\":55,\"clearance_type\":\"student\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:54:59'),
(334, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":1,\"required_first_enabled\":0,\"required_first_designation_id\":null,\"required_last_enabled\":0,\"required_last_designation_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(335, 3, 'Signatory Assign (Scope)', '{\"user_id\":51,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(336, 3, 'Signatory Assign (Scope)', '{\"user_id\":50,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(337, 3, 'Signatory Assign (Scope)', '{\"user_id\":49,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(338, 3, 'Signatory Assign (Scope)', '{\"user_id\":52,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(339, 3, 'Signatory Assign (Scope)', '{\"user_id\":42,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(340, 3, 'Signatory Assign (Scope)', '{\"user_id\":55,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:32'),
(341, 3, 'Signatory Unassign', '{\"target_user_id\":49,\"employee_id\":\"LCA109P\",\"designation\":\"Guidance\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:36'),
(342, 3, 'Signatory Unassign', '{\"target_user_id\":55,\"employee_id\":\"LCA115P\",\"designation\":\"Registrar\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:47'),
(343, 3, 'Signatory Unassign', '{\"target_user_id\":42,\"employee_id\":\"LCA102P\",\"designation\":\"MIS\\/IT\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 17:59:48'),
(344, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":1,\"required_first_enabled\":0,\"required_first_designation_id\":null,\"required_last_enabled\":0,\"required_last_designation_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:00:09'),
(345, 3, 'Signatory Assign (Scope)', '{\"user_id\":47,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:00:09'),
(346, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-01 18:29:40'),
(347, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-01 18:30:09'),
(348, 3, 'Scope Settings Update', '{\"clearance_type\":\"student\",\"include_program_head\":0,\"required_first_enabled\":1,\"required_first_designation_id\":2,\"required_last_enabled\":1,\"required_last_designation_id\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:56:32'),
(349, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":1,\"required_first_enabled\":0,\"required_first_designation_id\":null,\"required_last_enabled\":0,\"required_last_designation_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:57:01'),
(350, 3, 'Signatory Assign (Scope)', '{\"user_id\":45,\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:57:01'),
(351, 3, 'Scope Settings Update', '{\"clearance_type\":\"faculty\",\"include_program_head\":0,\"required_first_enabled\":1,\"required_first_designation_id\":12,\"required_last_enabled\":1,\"required_last_designation_id\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 18:57:27'),
(352, 3, 'Signatory Unassign', '{\"target_user_id\":51,\"employee_id\":\"LCA111P\",\"designation\":\"Clinic\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 19:34:30'),
(353, 3, 'Signatory Assignment Remove', '{\"target_user_id\":56,\"assignment_id\":11,\"designation\":\"Cashier\",\"clearance_type\":\"faculty\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-01 19:49:37'),
(354, 5, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-01 19:52:53'),
(355, 15, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-01 21:16:33'),
(356, 15, 'Global Apply', '{\"clearance_form_id\":\"CF-2025-00001\",\"academic_year_id\":10,\"semester_id\":24}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-01 22:39:03'),
(357, 15, 'Signatory Apply', '{\"form_id\":\"CF-2025-00001\",\"designation_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-02 00:44:36');

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
(7, 3, '2025-08-14 05:33:56', NULL, 0),
(15, 4, '2025-08-23 11:18:12', NULL, 0),
(33, 4, '2025-08-25 18:20:51', NULL, 0),
(34, 4, '2025-08-25 18:20:51', NULL, 0),
(41, 2, '2025-09-01 16:17:12', NULL, 0),
(42, 2, '2025-09-01 16:17:12', NULL, 0),
(43, 2, '2025-09-01 16:17:12', NULL, 0),
(44, 2, '2025-09-01 16:17:12', NULL, 0),
(45, 2, '2025-09-01 16:17:12', NULL, 0),
(46, 2, '2025-09-01 16:17:12', NULL, 0),
(47, 2, '2025-09-01 16:17:12', NULL, 0),
(47, 5, '2025-09-01 16:17:12', NULL, 0),
(48, 2, '2025-09-01 16:17:12', NULL, 0),
(49, 2, '2025-09-01 16:17:12', NULL, 0),
(50, 2, '2025-09-01 16:17:12', NULL, 0),
(51, 2, '2025-09-01 16:17:12', NULL, 0),
(52, 2, '2025-09-01 16:17:12', NULL, 0),
(53, 2, '2025-09-01 16:17:12', NULL, 0),
(54, 2, '2025-09-01 16:17:12', NULL, 0),
(55, 2, '2025-09-01 16:17:12', NULL, 0),
(56, 2, '2025-09-01 16:17:12', NULL, 0),
(57, 6, '2025-09-01 16:17:12', NULL, 0),
(58, 6, '2025-09-01 16:17:12', NULL, 0),
(59, 6, '2025-09-01 16:17:12', NULL, 0);

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
  ADD UNIQUE KEY `uq_department_sector` (`department_name`,`sector_id`),
  ADD KEY `idx_departments_type` (`department_type`),
  ADD KEY `idx_departments_active` (`is_active`),
  ADD KEY `idx_sector_id` (`sector_id`);

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
-- Indexes for table `scope_settings`
--
ALTER TABLE `scope_settings`
  ADD PRIMARY KEY (`clearance_type`),
  ADD KEY `fk_scope_settings_first_designation` (`required_first_designation_id`),
  ADD KEY `fk_scope_settings_last_designation` (`required_last_designation_id`),
  ADD KEY `idx_required_first` (`required_first_enabled`,`required_first_designation_id`),
  ADD KEY `idx_required_last` (`required_last_enabled`,`required_last_designation_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`sector_id`),
  ADD UNIQUE KEY `sector_name` (`sector_name`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD KEY `idx_semesters_active` (`is_active`),
  ADD KEY `idx_semesters_generation` (`is_generation`),
  ADD KEY `idx_semesters_academic_year` (`academic_year_id`);

--
-- Indexes for table `signatory_assignments`
--
ALTER TABLE `signatory_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_sa_ph` (`department_id`,`designation_id`),
  ADD UNIQUE KEY `uq_sa_scope` (`user_id`,`clearance_type`,`designation_id`),
  ADD KEY `fk_sa_desig` (`designation_id`),
  ADD KEY `fk_sa_sector` (`sector_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`employee_number`),
  ADD UNIQUE KEY `uq_staff_emp` (`employee_number`),
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
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `clearance_signatory_status`
--
ALTER TABLE `clearance_signatory_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_versions`
--
ALTER TABLE `data_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `designation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `sector_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `signatory_assignments`
--
ALTER TABLE `signatory_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

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
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_sector_id` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `scope_settings`
--
ALTER TABLE `scope_settings`
  ADD CONSTRAINT `fk_scope_settings_first_designation` FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scope_settings_last_designation` FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE;

--
-- Constraints for table `signatory_assignments`
--
ALTER TABLE `signatory_assignments`
  ADD CONSTRAINT `fk_sa_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_sa_desig` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`),
  ADD CONSTRAINT `fk_sa_sector` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`),
  ADD CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
