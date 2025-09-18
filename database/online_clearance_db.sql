-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 01:58 AM
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
(40, '2024-2025', 1, '2025-09-14 18:09:58', '2025-09-14 18:09:58');

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
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `status` enum('Unapplied','Applied','In Progress','Completed','Rejected') DEFAULT 'Unapplied',
  `applied_at` timestamp NULL DEFAULT NULL COMMENT 'When user first applied',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When all signatories approved',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'When any signatory rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clearance forms with sector-based clearance types (College, Senior High School, Faculty)';

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
(74, NULL, 84, NULL, '2025-09-14', '2025-10-14', NULL, 1, 'active', '2025-09-14 18:13:28', '2025-09-14 19:01:10');

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
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `designation_id` int(11) NOT NULL COMMENT 'Which signatory is required',
  `is_required` tinyint(1) DEFAULT 1,
  `order_sequence` int(11) DEFAULT 0 COMMENT 'Order of appearance in clearance form',
  `is_department_specific` tinyint(1) DEFAULT 0 COMMENT 'TRUE for Program Head',
  `applies_to_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of department IDs for Program Head' CHECK (json_valid(`applies_to_departments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clearance requirements with sector-based clearance types';

--
-- Dumping data for table `clearance_requirements`
--

INSERT INTO `clearance_requirements` (`requirement_id`, `clearance_type`, `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, `created_at`) VALUES
(1, '', 1, 1, 1, 0, NULL, '2025-08-13 19:48:16'),
(2, '', 2, 1, 2, 0, NULL, '2025-08-13 19:48:16'),
(3, '', 3, 1, 3, 0, NULL, '2025-08-13 19:48:16'),
(4, '', 8, 1, 4, 1, '[1,2,3,4,5,6]', '2025-08-13 19:48:16'),
(5, 'Faculty', 1, 1, 1, 0, NULL, '2025-08-13 19:48:16'),
(6, 'Faculty', 2, 1, 2, 0, NULL, '2025-08-13 19:48:16'),
(7, 'Faculty', 8, 1, 3, 1, '[1,2,3,4,5,6]', '2025-08-13 19:48:16'),
(8, '', 3, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(9, '', 2, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(10, '', 1, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(11, '', 7, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(12, '', 5, 1, 0, 0, NULL, '2025-08-14 10:34:01'),
(13, '', 8, 1, 0, 1, '[\"ICT\", \"Business\", \"Engineering\"]', '2025-08-14 10:34:01'),
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
(17, 'Alumni Placement Officer', 'Alumni Placement Officer', 1, '2025-09-01 15:53:23'),
(18, 'Faculty', 'Faculty member designation', 1, '2025-09-14 18:18:25');

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
) ;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`employee_number`, `user_id`, `employment_status`, `department_id`, `created_at`, `updated_at`) VALUES
('LCA2001P', 62, 'Full Time', 44, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
('LCA2002P', 63, 'Full Time', NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
('LCA2003P', 64, 'Part Time', NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
('LCA2004P', 65, 'Part Time - Full Load', 44, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
('LCA2005P', 66, 'Full Time', NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_backup_old_format`
--

CREATE TABLE `faculty_backup_old_format` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee ID format: LCA123P',
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
(3, 'Student', 'Student users applying for clearance', 1, '2025-08-13 19:48:16'),
(4, 'Faculty', 'Faculty members applying for clearance', 1, '2025-08-13 19:48:16'),
(5, 'School Administrator', 'Role for School Administrator', 1, '2025-08-14 04:37:48'),
(6, 'Program Head', 'Role for Program Head', 1, '2025-08-14 04:37:48'),
(7, 'Regular Staff', 'Regular staff members (cashier, librarian, etc.)', 1, '2025-09-14 20:36:20');

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
(1, 1, '2025-08-14 05:22:22', NULL),
(1, 2, '2025-08-14 05:22:22', NULL),
(1, 3, '2025-08-14 05:22:22', NULL),
(1, 4, '2025-08-14 05:22:22', NULL),
(1, 5, '2025-08-14 05:22:22', NULL),
(1, 6, '2025-08-14 05:22:22', NULL),
(1, 7, '2025-08-14 05:22:22', NULL),
(1, 8, '2025-08-14 05:22:22', NULL),
(1, 9, '2025-08-14 05:22:22', NULL),
(1, 10, '2025-08-14 05:22:22', NULL),
(1, 11, '2025-08-14 05:22:22', NULL),
(1, 12, '2025-08-14 05:22:22', NULL),
(1, 13, '2025-08-14 05:22:22', NULL),
(1, 14, '2025-08-14 05:22:22', NULL),
(1, 15, '2025-08-14 05:22:22', NULL),
(1, 16, '2025-08-14 05:22:22', NULL),
(1, 17, '2025-08-14 05:22:22', NULL),
(1, 18, '2025-08-14 05:22:22', NULL),
(1, 19, '2025-08-14 05:22:22', NULL),
(1, 20, '2025-08-14 05:22:22', NULL),
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
('faculty', 0, '2025-09-09 16:49:46', 1, 2, 1, 1),
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
-- Table structure for table `sector_clearance_settings`
--

CREATE TABLE `sector_clearance_settings` (
  `setting_id` int(11) NOT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `include_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if Program Heads should be auto-assigned',
  `required_first_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required first signatory is enabled',
  `required_first_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign first',
  `required_last_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required last signatory is enabled',
  `required_last_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign last',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Settings for each sector clearance type';

--
-- Dumping data for table `sector_clearance_settings`
--

INSERT INTO `sector_clearance_settings` (`setting_id`, `clearance_type`, `include_program_head`, `required_first_enabled`, `required_first_designation_id`, `required_last_enabled`, `required_last_designation_id`, `created_at`, `updated_at`) VALUES
(1, 'College', 1, 0, NULL, 0, NULL, '2025-09-14 14:59:46', '2025-09-14 16:43:06'),
(2, 'Senior High School', 1, 0, NULL, 0, NULL, '2025-09-14 14:59:46', '2025-09-14 16:42:36'),
(3, 'Faculty', 0, 1, 2, 1, 1, '2025-09-14 14:59:46', '2025-09-14 17:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `sector_signatory_assignments`
--

CREATE TABLE `sector_signatory_assignments` (
  `assignment_id` int(11) NOT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Staff member assigned as signatory',
  `designation_id` int(11) NOT NULL COMMENT 'Designation/position of the signatory',
  `is_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this is a Program Head assignment',
  `department_id` int(11) DEFAULT NULL COMMENT 'Specific department for Program Head (NULL for general staff)',
  `is_required_first` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign first',
  `is_required_last` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign last',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'FALSE to temporarily disable assignment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sector-based signatory assignments for clearance forms';

--
-- Dumping data for table `sector_signatory_assignments`
--

INSERT INTO `sector_signatory_assignments` (`assignment_id`, `clearance_type`, `user_id`, `designation_id`, `is_program_head`, `department_id`, `is_required_first`, `is_required_last`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Faculty', 80, 13, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(2, 'Faculty', 79, 12, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(3, 'Faculty', 71, 17, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(4, 'Faculty', 78, 5, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(5, 'Faculty', 74, 2, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(6, 'Faculty', 69, 16, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(7, 'Faculty', 68, 15, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(8, 'Faculty', 67, 14, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(9, 'Faculty', 81, 6, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(10, 'Faculty', 70, 3, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(11, 'Faculty', 76, 4, 0, NULL, 0, 0, 1, '2025-09-14 16:40:49', '2025-09-14 16:40:49'),
(12, 'Faculty', 75, 10, 0, NULL, 0, 0, 1, '2025-09-14 16:40:50', '2025-09-14 16:40:50'),
(13, 'Faculty', 73, 1, 0, NULL, 0, 0, 1, '2025-09-14 16:40:50', '2025-09-14 16:40:50'),
(14, 'Faculty', 77, 11, 0, NULL, 0, 0, 1, '2025-09-14 16:40:50', '2025-09-14 16:40:50'),
(15, 'Faculty', 72, 7, 0, NULL, 0, 0, 1, '2025-09-14 16:40:50', '2025-09-14 16:40:50'),
(16, 'Faculty', 82, 9, 0, NULL, 0, 0, 1, '2025-09-14 16:40:50', '2025-09-14 16:40:50'),
(17, 'Senior High School', 74, 2, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(18, 'Senior High School', 69, 16, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(19, 'Senior High School', 68, 15, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(20, 'Senior High School', 67, 14, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(21, 'Senior High School', 70, 3, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(22, 'Senior High School', 76, 4, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(23, 'Senior High School', 73, 1, 0, NULL, 0, 0, 1, '2025-09-14 16:42:36', '2025-09-14 16:42:36'),
(24, 'College', 74, 2, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(25, 'College', 69, 16, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(26, 'College', 68, 15, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(27, 'College', 67, 14, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(28, 'College', 70, 3, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(29, 'College', 76, 4, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06'),
(30, 'College', 73, 1, 0, NULL, 0, 0, 1, '2025-09-14 16:43:06', '2025-09-14 16:43:06');

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
(84, '1st', 40, 1, 0, '2025-09-14 18:13:28', '2025-09-14 18:13:28'),
(85, '2nd', 40, 1, 0, '2025-09-14 18:13:28', '2025-09-14 18:13:28');

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

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee Number format: LCAXXXXP',
  `user_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `staff_category` enum('Regular Staff','Program Head','School Administrator') NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'For program heads and department-specific staff',
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') DEFAULT NULL COMMENT 'For faculty-staff dual roles',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`employee_number`, `user_id`, `designation_id`, `staff_category`, `department_id`, `employment_status`, `is_active`, `created_at`, `updated_at`) VALUES
('LCA3001P', 83, 8, 'Program Head', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA3002P', 84, 8, 'Program Head', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA3003P', 85, 8, 'Program Head', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4001P', 67, 14, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4002P', 68, 15, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4003P', 69, 16, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4004P', 70, 3, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4005P', 71, 17, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4006P', 72, 7, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4007P', 73, 1, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4008P', 74, 2, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4009P', 75, 10, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4010P', 76, 4, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4011P', 77, 11, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4012P', 78, 5, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4013P', 79, 12, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4014P', 80, 13, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA4015P', 81, 6, 'Regular Staff', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49'),
('LCA5001P', 82, 9, 'School Administrator', NULL, 'Full Time', 1, '2025-09-10 20:30:49', '2025-09-10 20:30:49');

--
-- Triggers `staff`
--
DELIMITER $$
CREATE TRIGGER `staff_bi` BEFORE INSERT ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `staff_bu` BEFORE UPDATE ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `staff_department_assignments`
--

CREATE TABLE `staff_department_assignments` (
  `assignment_id` int(11) NOT NULL,
  `staff_id` varchar(8) NOT NULL,
  `department_id` int(11) NOT NULL,
  `sector_id` int(11) DEFAULT NULL COMMENT 'For sector-wide assignments',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT 'Primary department assignment',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL COMMENT 'User who assigned this',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_department_assignments`
--

INSERT INTO `staff_department_assignments` (`assignment_id`, `staff_id`, `department_id`, `sector_id`, `is_primary`, `assigned_at`, `assigned_by`, `is_active`) VALUES
(8, 'LCA3001P', 44, 1, 1, '2025-09-10 22:07:01', NULL, 1),
(9, 'LCA3001P', 45, 1, 1, '2025-09-10 22:07:01', NULL, 1),
(10, 'LCA3001P', 46, 1, 1, '2025-09-10 22:07:01', NULL, 1),
(11, 'LCA3002P', 47, 2, 2, '2025-09-12 18:59:00', NULL, 1),
(12, 'LCA3002P', 48, 2, 2, '2025-09-12 18:59:25', NULL, 1),
(13, 'LCA3002P', 49, 2, 2, '2025-09-12 18:59:38', NULL, 1),
(14, 'LCA3003P', 50, 3, 3, '2025-09-12 19:01:13', NULL, 1);

--
-- Triggers `staff_department_assignments`
--
DELIMITER $$
CREATE TRIGGER `sda_bi_program_head_only` BEFORE INSERT ON `staff_department_assignments` FOR EACH ROW BEGIN
  DECLARE staff_category VARCHAR(50);
  
  SELECT s.staff_category INTO staff_category
  FROM staff s
  WHERE s.employee_number = NEW.staff_id;
  
  IF staff_category != 'Program Head' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Program Heads can be assigned to departments';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sda_bi_sector_validation` BEFORE INSERT ON `staff_department_assignments` FOR EACH ROW BEGIN
  
  
  
  SET NEW.sector_id = (SELECT sector_id FROM departments WHERE department_id = NEW.department_id);
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
(1, 'admin', '$2y$10$cS1Lk6GOXeKSOmqguvw6lO/kuHy844NX1Kgt8rInKgCn5dgWTdN9K', 'admin@system.local', 'System', 'Administrator', NULL, NULL, NULL, 'active', 0, 1, NULL, NULL, NULL, '2025-09-11 01:29:50', '2025-09-11 01:29:50'),
(60, 'LCA0003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'LCA003P@placeholder.local', 'Sitti', 'Pamaloy', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-08 02:23:59', '2025-09-10 19:44:46'),
(62, 'LCA2001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty1@clearance.com', 'John', 'Doe', 'Michael', '+63 912 345 6789', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
(63, 'LCA2002P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty2@clearance.com', 'Jane', 'Smith', 'Elizabeth', '+63 912 345 6790', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
(64, 'LCA2003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty3@clearance.com', 'Robert', 'Johnson', 'David', '+63 912 345 6791', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
(65, 'LCA2004P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty4@clearance.com', 'Maria', 'Garcia', 'Isabella', '+63 912 345 6792', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
(66, 'LCA2005P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty5@clearance.com', 'James', 'Wilson', 'Alexander', '+63 912 345 6793', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 19:18:45', '2025-09-10 19:44:46'),
(67, 'LCA4001P', '$2y$10$GNCY0EuGvode3KRQHy/oAu2lrBFUkO1fdqCnZgLuDnjDwpNnF5q0O', 'guidance@clearance.com', 'Sarah', 'Guidance', 'Marie', '+63 912 345 6001', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(68, 'LCA4002P', '$2y$10$RLVTXJIplCn/vVEgrhb2TeupyRHaSePWNUFkQAcXD4Q9DbXHdwN7O', 'discipline@clearance.com', 'Michael', 'Discipline', 'James', '+63 912 345 6002', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(69, 'LCA4003P', '$2y$10$2vNxhEOTTnLqb5avi0kUCuZbGXQ6SETGP1jZPWoG8uC2Yux/hH5Mm', 'clinic@clearance.com', 'Dr. Emily', 'Clinic', 'Rose', '+63 912 345 6003', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(70, 'LCA4004P', '$2y$10$tvUPJea1R157uNy9FknkDuMgBbqriMYg6esMHzAVvd/RNaNlbv0Y.', 'librarian@clearance.com', 'David', 'Librarian', 'Paul', '+63 912 345 6004', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(71, 'LCA4005P', '$2y$10$v7m9G/0iwshVM64HC682beXBt51u/jWf/7Shnb/KoCSgaqFrlStfG', 'alumni@clearance.com', 'Lisa', 'Alumni', 'Grace', '+63 912 345 6005', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(72, 'LCA4006P', '$2y$10$vT9fed9nRkpkni8.e3Q0G.FY8dDe.HBZqZGc9RI.oebtm0A9qc9wy', 'sao@clearance.com', 'Robert', 'Sao', 'John', '+63 912 345 6006', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(73, 'LCA4007P', '$2y$10$4.0L/Ho0epu0kAloJdHxy.bCxKGZ.3i9sogdzDrKOUWSjcahcf0d6', 'registrar@clearance.com', 'Maria', 'Registrar', 'Elena', '+63 912 345 6007', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(74, 'LCA4008P', '$2y$10$RYz8pIKar98zRLNPQfKKaOnmIyelQZzGFuSFOtwfd03i1zYen6une', 'cashier@clearance.com', 'John', 'Cashier', 'Mark', '+63 912 345 6008', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(75, 'LCA4009P', '$2y$10$bZyY.OqSRmCVZgubZux7keG106PzbmN0e85zTJFckd1TdPd.Q3Nja', 'pamo@clearance.com', 'Anna', 'Pamo', 'Sophia', '+63 912 345 6009', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(76, 'LCA4010P', '$2y$10$QpiMOFmb4UwvIs.5sSnbsOCb/ySTudOVfNDaM2qg8bB9gj/qKdrRS', 'misit@clearance.com', 'Carlos', 'Misit', 'Luis', '+63 912 345 6010', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(77, 'LCA4011P', '$2y$10$UfHVb9J33XmBTjXB/R1Z1.YYhB5w6LSeKjVF1iOy.pWCr9wLwEqIC', 'pettycash@clearance.com', 'Jennifer', 'Pettycash', 'Ann', '+63 912 345 6011', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(78, 'LCA4012P', '$2y$10$RySlXhc56OnJ3dcGHNAYZOQfPG4D.cJBrfZ0WUEImN4qYHyL0uyVC', 'building@clearance.com', 'Thomas', 'Building', 'Lee', '+63 912 345 6012', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(79, 'LCA4013P', '$2y$10$OI7QAt/gK7hgz9VbYzHiuuVOT6TeHm8edge1aBvwT4btjsk0KqHV2', 'accountant@clearance.com', 'Patricia', 'Accountant', 'Jane', '+63 912 345 6013', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(80, 'LCA4014P', '$2y$10$ar8klFfiAYClcqO1.1H.vuccyt39WSiWoMoySbhv.GtpaH/nR80Cu', 'acadhead@clearance.com', 'William', 'Acadhead', 'Scott', '+63 912 345 6014', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(81, 'LCA4015P', '$2y$10$MmMsr5v9R2txg/CmVUb2Deubc9MQ.nr4oFsuWl/3tc84Ggj3n87M2', 'hr@clearance.com', 'Susan', 'Hr', 'Kim', '+63 912 345 6015', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(82, 'LCA5001P', '$2y$10$9BO6L8v0LnLV5JVkz.5D/Or9kvHRaOnmvcavbtLV/HuDY0JIqQNpS', 'schooladmin@clearance.com', 'Dr. James', 'Schooladmin', 'Wilson', '+63 912 345 7001', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:48'),
(83, 'LCA3001P', '$2y$10$ALcRn0bWTHpYn7kCXGJTsOxShuUzRnbTMTcpc16k7Hig8cUHVX7xG', 'phcollege@clearance.com', 'Dr. Maria', 'Phcollege', 'Santos', '+63 912 345 8001', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(84, 'LCA3002P', '$2y$10$ZVqLqOasEynHanUn.hoF5ufgMMubcZtPKKb/7lPDPpWAHsYvxs4wK', 'phshs@clearance.com', 'Dr. John', 'Phshs', 'Dela Cruz', '+63 912 345 8002', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(85, 'LCA3003P', '$2y$10$TUrs2xJpYn5O7.RIDEpyDeId6k1yv.k5gwWncEOemnwXIZ2fnlTYu', 'phfaculty@clearance.com', 'Dr. Ana', 'Phfaculty', 'Reyes', '+63 912 345 8003', NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-10 20:30:49', '2025-09-13 07:57:47'),
(86, 'LCA5000P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'schooladmin@system.local', 'School', 'Administrator', NULL, '+63 912 345 6789', NULL, 'active', 0, 1, NULL, NULL, NULL, '2025-09-11 01:47:15', '2025-09-11 01:47:15'),
(87, 'LCA1234A', '$2y$10$/oFj4U0XsejXC.AYhLXKguR.QsN3RZsCREeziFUkqtdECG285vZSi', 'john.doe@lca.edu.ph', 'John', 'Doe', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-14 16:02:29', '2025-09-14 16:02:29'),
(88, 'LCA1235B', '$2y$10$yqaXqhqUoYwGZ0pdepHKOO.cK/VBc5ldpu9am/.osoNgC0hwHJJlC', 'jane.smith@lca.edu.ph', 'Jane', 'Smith', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-14 16:02:29', '2025-09-14 16:02:29'),
(89, 'LCA1236C', '$2y$10$BaHnLOGks2jf7cgt5/Y3R.0bUTutrpmswFx69.RzBZJUnP3L61vX6', 'bob.johnson@lca.edu.ph', 'Bob', 'Johnson', NULL, NULL, NULL, 'active', 1, 1, NULL, NULL, NULL, '2025-09-14 16:02:29', '2025-09-14 16:02:29');

-- --------------------------------------------------------

--
-- Table structure for table `users_backup_before_sync`
--

CREATE TABLE `users_backup_before_sync` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `user_type` varchar(7) CHARACTER SET cp850 COLLATE cp850_general_ci NOT NULL,
  `employee_number` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_backup_before_sync`
--

INSERT INTO `users_backup_before_sync` (`user_id`, `username`, `password`, `first_name`, `last_name`, `user_type`, `employee_number`) VALUES
(41, 'LCA101P', '$2y$10$BDV5V24xV8oiDmmklNpzxesa9y.Wgz8Pnc6gKfTwpqGV0jOJXxEKO', 'Test', 'Pamo', 'staff', 'LCA0101P'),
(42, 'LCA102P', '$2y$10$7Zp2nWGYYtEjz5wWqPfLjOeBeqdsT8sNg0ypGvR9DFVDO5HOw/KLO', 'Test', 'Misit', 'staff', 'LCA0102P'),
(43, 'LCA103P', '$2y$10$1/H602BsYCJPDxS8OWt.YeLuda1a4O1LanSV/mdmM43spNbOc5R/C', 'Test', 'Pettycash', 'staff', 'LCA0103P'),
(44, 'LCA104P', '$2y$10$hcP0AdaI8KxOS4SxvHuWRedVp6Hpx3hXRTtNfC5sXnRSxD92Na.Z6', 'Test', 'Bldgadmin', 'staff', 'LCA0104P'),
(45, 'LCA105P', '$2y$10$2LfAS2b/l7A3k9wrooNL6OqU36AEQDM7g4lJITWPFS1a6Z4rU7HfK', 'Test', 'Accountant', 'staff', 'LCA0105P'),
(46, 'LCA106P', '$2y$10$I8NEQLSgUlLmyzF0HOnAhuHQzjrV2ok6oO0KpDjJQ4Jc8bJgnERHu', 'Test', 'Acadhead', 'staff', 'LCA0106P'),
(47, 'LCA107P', '$2y$10$LmVKpBSC8P6AJAcFwKLuQ.1L3ishGOcvlW1MfecxBmGn1zSBb4N3e', 'Test', 'Schooladmin', 'staff', 'LCA0107P'),
(48, 'LCA108P', '$2y$10$1Tw2vrrx2BCyhDC5w2YxzeuamHhnfSIBmlLiM0FFeW8vmiGt7ykma', 'Test', 'Hr', 'staff', 'LCA0108P'),
(49, 'LCA109P', '$2y$10$ly/cDFr8ai8fg55cJoCZd..VVUO9Mk3iqq98LyeYykjh.IYI9Qtii', 'Test', 'Guidance', 'staff', 'LCA0109P'),
(50, 'LCA110P', '$2y$10$EXzxURUos4JwNMXdK4K8cefxqKE0QZF4qBInfvrnCrtOV/viQmU6K', 'Test', 'Discipline', 'staff', 'LCA0110P'),
(51, 'LCA111P', '$2y$10$1cxRUknSFpp7wPXxmzBJOOd83fFs4qlkKMXiITl1sxCLNx.sEz82.', 'Test', 'Clinic', 'staff', 'LCA0111P'),
(52, 'LCA112P', '$2y$10$EkCoek9DMzNCNi664Mo4VeZvJxreLYmEMhaTxcNpghxVZOuUdoDsS', 'Test', 'Librarian', 'staff', 'LCA0112P'),
(53, 'LCA113P', '$2y$10$lDMhZLJ2HCRqc3M/FNKi5uCfPqjQQGtzoW4VVm5PGcPRThMX0eEEW', 'Test', 'Alumni', 'staff', 'LCA0113P'),
(54, 'LCA114P', '$2y$10$zI1tI.IwwH2x5GB1RWV/ZOr1s0ErO0noUWYrgrXo5C9M2/Xj8hQNS', 'Test', 'Sao', 'staff', 'LCA0114P'),
(55, 'LCA115P', '$2y$10$xn3lwydRq9YJ.hqGl4USyubuwg2qi5w1n/R5d3pCxwz79MQSaRov.', 'Test', 'Registrar', 'staff', 'LCA0115P'),
(56, 'LCA116P', '$2y$10$jyj9/xOd5/miSHmHuGnoh.YVbUrY3Wxb8jNquKO9V2BHLrnXIIUMC', 'Test', 'Cashier', 'staff', 'LCA0116P'),
(57, 'PHC101P', '$2y$10$0zgcUI19ptFgkSj1v.qq5uc/S99LWn48pgmKmN3pf9/Vz1NajBARS', 'PH', 'College', 'staff', 'LCA1001P'),
(58, 'PHS101P', '$2y$10$RlT87fTdvYCX5EPMclyEuuq/1jOG6wktUQzcuAF2k17YVauxNWV/m', 'PH', 'Shs', 'staff', 'LCA1003P'),
(59, 'PHF101P', '$2y$10$TWcKKOEYIBQfWeQDnf36KuVNxEm.IGhRVMA5zVX3ic5BwCKQUSuGq', 'PH', 'Faculty', 'staff', 'LCA1002P'),
(60, 'LCA003P', '$2y$10$MZfiGaHKUrYm6jILKsREUO7VWmHMPQ1cxK4VoA8p71ullew5lh1Le', 'Sitti', 'Pamaloy', 'staff', 'LCA0003P'),
(62, 'LCA0001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'faculty', 'LCA0001P'),
(63, 'LCA0002P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'faculty', 'LCA0002P'),
(64, 'LCA0003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert', 'Johnson', 'faculty', 'LCA0003P'),
(65, 'LCA0004P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Garcia', 'faculty', 'LCA0004P'),
(66, 'LCA0005P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Wilson', 'faculty', 'LCA0005P');

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
(442, 60, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-09-08 02:23:59'),
(487, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-09-11 01:31:09'),
(488, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 10:08:12'),
(489, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 18:53:03'),
(490, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 20:03:58'),
(491, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 20:04:17'),
(492, 83, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-09-13 07:58:28'),
(493, 67, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-09-13 07:58:28'),
(494, 73, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-09-13 07:58:28'),
(495, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'unknown', '2025-09-13 07:58:28'),
(496, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 08:08:39'),
(497, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 09:07:12'),
(498, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 11:07:56'),
(499, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 19:23:46'),
(500, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 18:01:25'),
(501, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:11:05'),
(502, 85, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:11:51'),
(503, 85, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:13:55'),
(504, 85, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:13:56'),
(505, 89, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-14 19:31:40'),
(506, 87, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-14 19:31:40'),
(507, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:35:30'),
(508, 87, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:36:18'),
(509, 89, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 19:37:33'),
(510, 1, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-14 20:42:12'),
(511, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 21:10:57'),
(512, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 08:19:49'),
(513, 67, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 08:21:38'),
(514, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 09:51:12'),
(515, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 20:34:44'),
(516, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 20:37:53'),
(517, 82, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 21:23:29'),
(518, 83, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 21:57:23'),
(519, 67, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 23:16:30');

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
(1, 1, '2025-09-11 01:30:25', NULL, 0),
(60, 7, '2025-09-08 02:23:59', NULL, 0),
(62, 4, '2025-09-10 19:18:45', NULL, 1),
(63, 4, '2025-09-10 19:18:45', NULL, 1),
(64, 4, '2025-09-10 19:18:45', NULL, 1),
(65, 4, '2025-09-10 19:18:45', NULL, 1),
(66, 4, '2025-09-10 19:18:45', NULL, 1),
(67, 7, '2025-09-10 20:30:49', NULL, 1),
(68, 7, '2025-09-10 20:30:49', NULL, 1),
(69, 7, '2025-09-10 20:30:49', NULL, 1),
(70, 7, '2025-09-10 20:30:49', NULL, 1),
(71, 7, '2025-09-10 20:30:49', NULL, 1),
(72, 7, '2025-09-10 20:30:49', NULL, 1),
(73, 7, '2025-09-10 20:30:49', NULL, 1),
(74, 7, '2025-09-10 20:30:49', NULL, 1),
(75, 7, '2025-09-10 20:30:49', NULL, 1),
(76, 7, '2025-09-10 20:30:49', NULL, 1),
(77, 7, '2025-09-10 20:30:49', NULL, 1),
(78, 7, '2025-09-10 20:30:49', NULL, 1),
(79, 7, '2025-09-10 20:30:49', NULL, 1),
(80, 7, '2025-09-10 20:30:49', NULL, 1),
(81, 7, '2025-09-10 20:30:49', NULL, 1),
(82, 5, '2025-09-10 20:30:49', NULL, 1),
(83, 6, '2025-09-10 20:30:49', NULL, 1),
(84, 6, '2025-09-10 20:30:49', NULL, 1),
(85, 6, '2025-09-10 20:30:49', NULL, 1),
(87, 6, '2025-09-14 16:02:29', NULL, 0),
(89, 5, '2025-09-14 16:02:29', NULL, 0);

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
  ADD KEY `idx_clearance_signatories_form` (`clearance_form_id`),
  ADD KEY `idx_clearance_signatories_designation` (`designation_id`),
  ADD KEY `idx_clearance_signatories_action` (`action`),
  ADD KEY `idx_clearance_signatories_date` (`date_signed`),
  ADD KEY `idx_clearance_signatories_form_designation` (`clearance_form_id`,`designation_id`),
  ADD KEY `clearance_signatories_ibfk_3` (`actual_user_id`);

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
-- Indexes for table `sector_clearance_settings`
--
ALTER TABLE `sector_clearance_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_sector_setting` (`clearance_type`),
  ADD KEY `idx_sector_settings_type` (`clearance_type`),
  ADD KEY `sector_clearance_settings_ibfk_1` (`required_first_designation_id`),
  ADD KEY `sector_clearance_settings_ibfk_2` (`required_last_designation_id`);

--
-- Indexes for table `sector_signatory_assignments`
--
ALTER TABLE `sector_signatory_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_sector_assignments_type` (`clearance_type`),
  ADD KEY `idx_sector_assignments_user` (`user_id`),
  ADD KEY `idx_sector_assignments_designation` (`designation_id`),
  ADD KEY `idx_sector_assignments_department` (`department_id`),
  ADD KEY `idx_sector_assignments_program_head` (`is_program_head`),
  ADD KEY `idx_sector_assignments_active` (`is_active`),
  ADD KEY `idx_sector_user_designation` (`clearance_type`,`user_id`,`designation_id`),
  ADD KEY `idx_sector_program_head_dept` (`clearance_type`,`is_program_head`,`department_id`);

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
-- Indexes for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_staff_department` (`staff_id`,`department_id`),
  ADD KEY `idx_staff_assignments` (`staff_id`,`is_active`),
  ADD KEY `idx_department_assignments` (`department_id`,`is_active`),
  ADD KEY `idx_sector_assignments` (`sector_id`,`is_active`),
  ADD KEY `fk_sda_assigned_by` (`assigned_by`);

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
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

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
  MODIFY `designation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `sector_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sector_clearance_settings`
--
ALTER TABLE `sector_clearance_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sector_signatory_assignments`
--
ALTER TABLE `sector_signatory_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `signatory_assignments`
--
ALTER TABLE `signatory_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=520;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
  ADD CONSTRAINT `clearance_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
  ADD CONSTRAINT `clearance_signatories_ibfk_3` FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
-- Constraints for table `sector_clearance_settings`
--
ALTER TABLE `sector_clearance_settings`
  ADD CONSTRAINT `sector_clearance_settings_ibfk_1` FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sector_clearance_settings_ibfk_2` FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL;

--
-- Constraints for table `sector_signatory_assignments`
--
ALTER TABLE `sector_signatory_assignments`
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

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
-- Constraints for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  ADD CONSTRAINT `fk_sda_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_sector` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
