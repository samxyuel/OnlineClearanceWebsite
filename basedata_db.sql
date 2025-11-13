-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql-basedata.alwaysdata.net
-- Generation Time: Nov 12, 2025 at 10:45 AM
-- Server version: 10.11.14-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `basedata_db`
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
(47, '2026-2027', 0, '2025-09-17 23:44:51', '2025-10-27 07:40:13'),
(48, '2027-2028', 0, '2025-10-27 07:40:13', '2025-11-07 21:50:18'),
(50, '2028-2029', 1, '2025-11-07 22:05:15', '2025-11-07 22:05:15');

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
-- Table structure for table `clearance_forms`
--

CREATE TABLE `clearance_forms` (
  `clearance_form_id` varchar(20) NOT NULL COMMENT 'Format: CF-YYYY-XXXXX',
  `user_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `clearance_form_progress` enum('unapplied','in-progress','complete') NOT NULL DEFAULT 'unapplied' COMMENT 'Clearance Form Progress: unapplied, in-progress, complete',
  `applied_at` timestamp NULL DEFAULT NULL COMMENT 'When user first applied',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When all signatories approved',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'When any signatory rejected',
  `grace_period_ends` timestamp NULL DEFAULT NULL COMMENT 'End of grace period for final status update',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clearance forms with sector-based clearance types and progress tracking (College, Senior High School, Faculty)';

--
-- Dumping data for table `clearance_forms`
--

INSERT INTO `clearance_forms` (`clearance_form_id`, `user_id`, `academic_year_id`, `semester_id`, `clearance_type`, `clearance_form_progress`, `applied_at`, `completed_at`, `rejected_at`, `grace_period_ends`, `created_at`, `updated_at`) VALUES
('CF-2025-00001', 114, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:27:18', '2025-09-18 00:27:18'),
('CF-2025-00002', 134, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:27:28', '2025-09-18 00:27:28'),
('CF-2025-00003', 98, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00004', 93, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00005', 108, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00006', 94, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00007', 90, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00008', 105, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00009', 113, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00010', 102, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00011', 116, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00012', 100, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00013', 92, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00014', 117, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00015', 111, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00016', 110, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00017', 103, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00018', 106, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00019', 96, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00020', 107, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00021', 109, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00022', 91, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00023', 97, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00024', 99, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00025', 104, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00026', 112, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00027', 101, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00028', 95, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00029', 115, 47, 98, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00030', 147, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00031', 149, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00032', 120, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00033', 132, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00034', 139, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00035', 118, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00036', 124, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00037', 142, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00038', 128, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00039', 127, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00040', 136, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
('CF-2025-00041', 130, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00042', 125, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00043', 126, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00044', 137, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00045', 122, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00046', 146, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00047', 129, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00048', 133, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00049', 145, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00050', 138, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00051', 143, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00052', 135, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00053', 121, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00054', 144, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00055', 131, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00056', 119, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00057', 140, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00058', 123, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00059', 148, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00060', 141, 47, 98, 'Senior High School', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00061', 209, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00062', 213, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00063', 207, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00064', 201, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00065', 204, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00066', 205, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00067', 211, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00068', 203, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00069', 202, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00070', 210, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00071', 199, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00072', 212, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00073', 200, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00074', 206, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00075', 208, 47, 98, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-09-18 00:40:13', '2025-09-18 00:40:13'),
('CF-2025-00076', 114, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00077', 98, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00078', 93, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00079', 108, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00080', 94, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00081', 90, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00082', 105, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00083', 113, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00084', 102, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00085', 116, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00086', 100, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00087', 92, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00088', 117, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00089', 111, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00090', 110, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00091', 103, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00092', 106, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00093', 96, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00094', 107, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00095', 109, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00096', 91, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00097', 97, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00098', 99, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00099', 104, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00100', 112, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00101', 101, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00102', 95, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00103', 230, 47, 99, 'College', 'complete', '2025-10-21 16:17:18', NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-23 20:44:23'),
('CF-2025-00104', 115, 47, 99, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-18 19:37:51'),
('CF-2025-00105', 209, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00106', 213, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00107', 207, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00108', 201, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00109', 204, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00110', 205, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00111', 211, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00112', 203, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00113', 202, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00114', 210, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00115', 215, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00116', 199, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00117', 212, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00118', 200, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00119', 238, 47, 99, 'Faculty', 'in-progress', '2025-10-24 02:17:45', NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-24 02:17:45'),
('CF-2025-00120', 206, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00121', 208, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 22:13:03'),
('CF-2025-00122', 181, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 22:23:41'),
('CF-2025-00123', 239, 47, 99, 'Faculty', 'in-progress', '2025-10-24 03:06:04', NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-24 05:42:23'),
('CF-2025-00124', 180, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 22:23:41'),
('CF-2025-00125', 179, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 22:23:41'),
('CF-2025-00126', 214, 47, 99, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 22:23:41'),
('CF-2025-00127', 114, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00128', 98, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00129', 93, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00130', 108, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00131', 94, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00132', 90, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00133', 105, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00134', 113, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00135', 102, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00136', 116, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00137', 100, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00138', 92, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00139', 117, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00140', 111, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00141', 110, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00142', 103, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00143', 106, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00144', 96, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00145', 107, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
('CF-2025-00146', 109, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00147', 91, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00148', 97, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00149', 99, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00150', 104, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00151', 112, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00152', 101, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00153', 95, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00154', 230, 48, 100, 'College', 'complete', '2025-10-27 08:05:23', '2025-10-27 10:29:54', NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 10:29:54'),
('CF-2025-00155', 115, 48, 100, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
('CF-2025-00156', 209, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00157', 181, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00158', 213, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00159', 207, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00160', 201, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00161', 253, 48, 100, 'Faculty', 'in-progress', '2025-10-27 08:27:39', NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 10:09:33'),
('CF-2025-00162', 204, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00163', 205, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00164', 211, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00165', 239, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00166', 203, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00167', 202, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00168', 210, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00169', 215, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00170', 180, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00171', 199, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00172', 212, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00173', 179, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00174', 200, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00175', 238, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00176', 206, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00177', 208, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00178', 214, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
('CF-2025-00179', 254, 48, 100, 'Faculty', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-27 09:49:39', '2025-10-27 09:49:39'),
('CF-2025-00180', 255, 48, 100, 'Faculty', 'complete', '2025-10-27 09:54:04', '2025-10-27 10:14:47', NULL, NULL, '2025-10-27 09:53:36', '2025-10-27 10:14:47'),
('CF-2025-00181', 114, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00182', 98, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00183', 93, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00184', 108, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00185', 94, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00186', 90, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00187', 105, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00188', 113, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00189', 102, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00190', 116, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00191', 100, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00192', 92, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00193', 117, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00194', 111, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00195', 110, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00196', 103, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00197', 106, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00198', 96, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00199', 107, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00200', 109, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00201', 91, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00202', 97, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00203', 99, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00204', 104, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00205', 112, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00206', 101, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00207', 95, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00208', 230, 48, 101, 'College', 'in-progress', '2025-10-28 09:19:22', NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-11-01 12:14:58'),
('CF-2025-00209', 115, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
('CF-2025-00210', 256, 48, 101, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-05 06:52:48', '2025-11-05 06:52:48'),
('CF-2025-00211', 114, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00212', 98, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00213', 93, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00214', 108, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00215', 94, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00216', 90, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00217', 257, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00218', 105, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00219', 113, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00220', 102, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00221', 116, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00222', 100, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00223', 92, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00224', 117, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00225', 111, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00226', 110, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00227', 103, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00228', 106, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00229', 96, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00230', 107, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00231', 109, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00232', 91, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00233', 97, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00234', 99, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00235', 104, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00236', 256, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00237', 112, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00238', 101, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00239', 95, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00240', 230, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
('CF-2025-00241', 115, 50, 104, 'College', 'unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37');

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
-- Table structure for table `clearance_forms_with_sector`
--

CREATE TABLE `clearance_forms_with_sector` (
  `clearance_form_id` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') DEFAULT NULL,
  `status` enum('Unapplied','Pending','Processing','Approved','Rejected') DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `grace_period_ends` timestamp NULL DEFAULT NULL,
  `academic_year` varchar(9) DEFAULT NULL,
  `semester_name` enum('1st','2nd','Summer') DEFAULT NULL,
  `clearance_period_id` int(11) DEFAULT NULL,
  `sector` enum('College','Senior High School','Faculty') DEFAULT NULL,
  `period_status` enum('Not Started','Ongoing','Paused','Closed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_periods`
--

CREATE TABLE `clearance_periods` (
  `period_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `sector` enum('College','Senior High School','Faculty') NOT NULL DEFAULT 'College',
  `period_name` varchar(100) DEFAULT NULL COMMENT 'Auto-generated: "2024-2025 1st Semester"',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `status` enum('Not Started','Ongoing','Paused','Closed') NOT NULL DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clearance periods with sector support (College, Senior High School, Faculty)';

--
-- Dumping data for table `clearance_periods`
--

INSERT INTO `clearance_periods` (`period_id`, `academic_year_id`, `semester_id`, `sector`, `period_name`, `start_date`, `end_date`, `ended_at`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(108, 47, 98, 'College', NULL, '2025-09-26', '2025-09-26', NULL, 0, 'Closed', '2025-09-17 23:44:55', '2025-09-26 03:09:32'),
(109, 47, 98, 'Senior High School', NULL, '2025-09-26', '2025-09-26', NULL, 0, 'Closed', '2025-09-17 23:44:55', '2025-09-26 03:09:34'),
(110, 47, 98, 'Faculty', NULL, '2025-09-26', '2025-09-26', NULL, 0, 'Closed', '2025-09-17 23:44:55', '2025-09-26 03:09:35'),
(111, 47, 99, 'College', NULL, '2025-10-23', '2025-10-27', NULL, 0, 'Closed', '2025-10-18 13:48:25', '2025-10-27 05:52:19'),
(112, 47, 99, 'Senior High School', NULL, '2025-10-27', '2025-10-27', NULL, 0, 'Closed', '2025-10-18 13:48:25', '2025-10-27 07:39:36'),
(113, 47, 99, 'Faculty', NULL, '2025-10-23', '2025-10-27', NULL, 0, 'Closed', '2025-10-18 13:48:25', '2025-10-27 05:56:05'),
(114, 48, 100, 'College', NULL, '2025-10-27', '2025-10-28', NULL, 0, 'Closed', '2025-10-27 08:03:48', '2025-10-28 09:16:47'),
(115, 48, 100, 'Senior High School', NULL, '2025-10-27', '2025-10-27', NULL, 0, 'Closed', '2025-10-27 08:03:48', '2025-10-27 10:34:09'),
(116, 48, 100, 'Faculty', NULL, '2025-10-27', '2025-10-28', NULL, 0, 'Closed', '2025-10-27 08:03:48', '2025-10-28 09:16:50'),
(117, 48, 101, 'College', NULL, '2025-11-05', '2025-11-05', NULL, 0, 'Closed', '2025-10-28 09:16:59', '2025-11-05 10:03:18'),
(118, 48, 101, 'Senior High School', NULL, '2025-11-05', '2025-11-05', NULL, 0, 'Closed', '2025-10-28 09:16:59', '2025-11-05 10:03:20'),
(119, 48, 101, 'Faculty', NULL, '2025-11-05', '2025-11-05', NULL, 0, 'Closed', '2025-10-28 09:16:59', '2025-11-05 10:03:23'),
(120, 50, 104, 'College', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:18:31', '2025-11-07 22:32:20'),
(121, 50, 104, 'Senior High School', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:18:31', '2025-11-07 22:32:22'),
(122, 50, 104, 'Faculty', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:18:31', '2025-11-07 22:32:23'),
(123, 50, 105, 'College', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:32:30', '2025-11-07 22:32:31'),
(124, 50, 105, 'Senior High School', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:32:30', '2025-11-07 22:32:33'),
(125, 50, 105, 'Faculty', NULL, '2025-11-07', '2025-11-08', NULL, 0, 'Closed', '2025-11-07 22:32:30', '2025-11-07 22:32:34');

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
  `action` enum('Unapplied','Pending','Approved','Rejected') DEFAULT 'Unapplied',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `reason_id` int(11) DEFAULT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` text DEFAULT NULL COMMENT 'Additional details for rejection',
  `date_signed` timestamp NULL DEFAULT NULL COMMENT 'When action was taken',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clearance_signatories`
--

INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(2623, 'CF-2025-00001', 2, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2624, 'CF-2025-00001', 16, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2625, 'CF-2025-00001', 15, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2626, 'CF-2025-00001', 14, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2627, 'CF-2025-00001', 3, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2628, 'CF-2025-00001', 4, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2629, 'CF-2025-00001', 8, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2630, 'CF-2025-00001', 1, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2631, 'CF-2025-00002', 2, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2632, 'CF-2025-00002', 16, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2633, 'CF-2025-00002', 15, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2634, 'CF-2025-00002', 14, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2635, 'CF-2025-00002', 3, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2636, 'CF-2025-00002', 4, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2637, 'CF-2025-00002', 8, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2638, 'CF-2025-00002', 1, NULL, '', NULL, NULL, NULL, NULL, '2025-09-18 00:40:12', '2025-09-18 00:40:12'),
(2639, 'CF-2025-00003', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2640, 'CF-2025-00003', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2641, 'CF-2025-00003', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2642, 'CF-2025-00003', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2643, 'CF-2025-00003', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2644, 'CF-2025-00003', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2645, 'CF-2025-00003', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2646, 'CF-2025-00003', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2647, 'CF-2025-00004', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2648, 'CF-2025-00004', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2649, 'CF-2025-00004', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2650, 'CF-2025-00004', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2651, 'CF-2025-00004', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2652, 'CF-2025-00004', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2653, 'CF-2025-00004', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2654, 'CF-2025-00004', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2655, 'CF-2025-00005', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2656, 'CF-2025-00005', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2657, 'CF-2025-00005', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2658, 'CF-2025-00005', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2659, 'CF-2025-00005', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2660, 'CF-2025-00005', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2661, 'CF-2025-00005', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2662, 'CF-2025-00005', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2663, 'CF-2025-00006', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2664, 'CF-2025-00006', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2665, 'CF-2025-00006', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2666, 'CF-2025-00006', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2667, 'CF-2025-00006', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2668, 'CF-2025-00006', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2669, 'CF-2025-00006', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2670, 'CF-2025-00006', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2671, 'CF-2025-00007', 2, 190, 'Pending', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:34:30'),
(2672, 'CF-2025-00007', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2673, 'CF-2025-00007', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2674, 'CF-2025-00007', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2675, 'CF-2025-00007', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2676, 'CF-2025-00007', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2677, 'CF-2025-00007', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2678, 'CF-2025-00007', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2679, 'CF-2025-00008', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2680, 'CF-2025-00008', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2681, 'CF-2025-00008', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2682, 'CF-2025-00008', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2683, 'CF-2025-00008', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2684, 'CF-2025-00008', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2685, 'CF-2025-00008', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2686, 'CF-2025-00008', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2687, 'CF-2025-00009', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2688, 'CF-2025-00009', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2689, 'CF-2025-00009', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2690, 'CF-2025-00009', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2691, 'CF-2025-00009', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2692, 'CF-2025-00009', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2693, 'CF-2025-00009', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2694, 'CF-2025-00009', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2695, 'CF-2025-00010', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2696, 'CF-2025-00010', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2697, 'CF-2025-00010', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2698, 'CF-2025-00010', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2699, 'CF-2025-00010', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2700, 'CF-2025-00010', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2701, 'CF-2025-00010', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2702, 'CF-2025-00010', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2703, 'CF-2025-00011', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2704, 'CF-2025-00011', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2705, 'CF-2025-00011', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2706, 'CF-2025-00011', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2707, 'CF-2025-00011', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2708, 'CF-2025-00011', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2709, 'CF-2025-00011', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2710, 'CF-2025-00011', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2711, 'CF-2025-00012', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2712, 'CF-2025-00012', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2713, 'CF-2025-00012', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2714, 'CF-2025-00012', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2715, 'CF-2025-00012', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2716, 'CF-2025-00012', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2717, 'CF-2025-00012', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2718, 'CF-2025-00012', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2719, 'CF-2025-00013', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2720, 'CF-2025-00013', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2721, 'CF-2025-00013', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2722, 'CF-2025-00013', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2723, 'CF-2025-00013', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2724, 'CF-2025-00013', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2725, 'CF-2025-00013', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2726, 'CF-2025-00013', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2727, 'CF-2025-00014', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2728, 'CF-2025-00014', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2729, 'CF-2025-00014', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2730, 'CF-2025-00014', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2731, 'CF-2025-00014', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2732, 'CF-2025-00014', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2733, 'CF-2025-00014', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:18', '2025-09-18 01:16:18'),
(2734, 'CF-2025-00014', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2735, 'CF-2025-00015', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2736, 'CF-2025-00015', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2737, 'CF-2025-00015', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2738, 'CF-2025-00015', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2739, 'CF-2025-00015', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2740, 'CF-2025-00015', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2741, 'CF-2025-00015', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2742, 'CF-2025-00015', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2743, 'CF-2025-00016', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2744, 'CF-2025-00016', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2745, 'CF-2025-00016', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2746, 'CF-2025-00016', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2747, 'CF-2025-00016', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2748, 'CF-2025-00016', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2749, 'CF-2025-00016', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2750, 'CF-2025-00016', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2751, 'CF-2025-00017', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2752, 'CF-2025-00017', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2753, 'CF-2025-00017', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2754, 'CF-2025-00017', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2755, 'CF-2025-00017', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2756, 'CF-2025-00017', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2757, 'CF-2025-00017', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2758, 'CF-2025-00017', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2759, 'CF-2025-00018', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2760, 'CF-2025-00018', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2761, 'CF-2025-00018', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2762, 'CF-2025-00018', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2763, 'CF-2025-00018', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2764, 'CF-2025-00018', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2765, 'CF-2025-00018', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2766, 'CF-2025-00018', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2767, 'CF-2025-00019', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2768, 'CF-2025-00019', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2769, 'CF-2025-00019', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2770, 'CF-2025-00019', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2771, 'CF-2025-00019', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2772, 'CF-2025-00019', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2773, 'CF-2025-00019', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2774, 'CF-2025-00019', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2775, 'CF-2025-00020', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2776, 'CF-2025-00020', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2777, 'CF-2025-00020', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2778, 'CF-2025-00020', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2779, 'CF-2025-00020', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2780, 'CF-2025-00020', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2781, 'CF-2025-00020', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2782, 'CF-2025-00020', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2783, 'CF-2025-00021', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2784, 'CF-2025-00021', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2785, 'CF-2025-00021', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2786, 'CF-2025-00021', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2787, 'CF-2025-00021', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2788, 'CF-2025-00021', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2789, 'CF-2025-00021', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2790, 'CF-2025-00021', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2791, 'CF-2025-00022', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2792, 'CF-2025-00022', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2793, 'CF-2025-00022', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2794, 'CF-2025-00022', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2795, 'CF-2025-00022', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2796, 'CF-2025-00022', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2797, 'CF-2025-00022', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2798, 'CF-2025-00022', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2799, 'CF-2025-00023', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2800, 'CF-2025-00023', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2801, 'CF-2025-00023', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2802, 'CF-2025-00023', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2803, 'CF-2025-00023', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2804, 'CF-2025-00023', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2805, 'CF-2025-00023', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2806, 'CF-2025-00023', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2807, 'CF-2025-00024', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2808, 'CF-2025-00024', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2809, 'CF-2025-00024', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2810, 'CF-2025-00024', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2811, 'CF-2025-00024', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2812, 'CF-2025-00024', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2813, 'CF-2025-00024', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2814, 'CF-2025-00024', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2815, 'CF-2025-00025', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2816, 'CF-2025-00025', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2817, 'CF-2025-00025', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2818, 'CF-2025-00025', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2819, 'CF-2025-00025', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2820, 'CF-2025-00025', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2821, 'CF-2025-00025', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2822, 'CF-2025-00025', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2823, 'CF-2025-00026', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2824, 'CF-2025-00026', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2825, 'CF-2025-00026', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2826, 'CF-2025-00026', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2827, 'CF-2025-00026', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2828, 'CF-2025-00026', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2829, 'CF-2025-00026', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2830, 'CF-2025-00026', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2831, 'CF-2025-00027', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2832, 'CF-2025-00027', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2833, 'CF-2025-00027', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2834, 'CF-2025-00027', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2835, 'CF-2025-00027', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2836, 'CF-2025-00027', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2837, 'CF-2025-00027', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2838, 'CF-2025-00027', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2839, 'CF-2025-00028', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2840, 'CF-2025-00028', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2841, 'CF-2025-00028', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2842, 'CF-2025-00028', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2843, 'CF-2025-00028', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2844, 'CF-2025-00028', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2845, 'CF-2025-00028', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2846, 'CF-2025-00028', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2847, 'CF-2025-00029', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2848, 'CF-2025-00029', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2849, 'CF-2025-00029', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2850, 'CF-2025-00029', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2851, 'CF-2025-00029', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2852, 'CF-2025-00029', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2853, 'CF-2025-00029', 8, 179, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2854, 'CF-2025-00029', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2855, 'CF-2025-00030', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2856, 'CF-2025-00030', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2857, 'CF-2025-00030', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2858, 'CF-2025-00030', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2859, 'CF-2025-00030', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2860, 'CF-2025-00030', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2861, 'CF-2025-00030', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2862, 'CF-2025-00030', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2863, 'CF-2025-00031', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2864, 'CF-2025-00031', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2865, 'CF-2025-00031', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2866, 'CF-2025-00031', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2867, 'CF-2025-00031', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2868, 'CF-2025-00031', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2869, 'CF-2025-00031', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2870, 'CF-2025-00031', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2871, 'CF-2025-00032', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2872, 'CF-2025-00032', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2873, 'CF-2025-00032', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2874, 'CF-2025-00032', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2875, 'CF-2025-00032', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2876, 'CF-2025-00032', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2877, 'CF-2025-00032', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2878, 'CF-2025-00032', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2879, 'CF-2025-00033', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2880, 'CF-2025-00033', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2881, 'CF-2025-00033', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2882, 'CF-2025-00033', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2883, 'CF-2025-00033', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2884, 'CF-2025-00033', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2885, 'CF-2025-00033', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2886, 'CF-2025-00033', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2887, 'CF-2025-00034', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2888, 'CF-2025-00034', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2889, 'CF-2025-00034', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2890, 'CF-2025-00034', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2891, 'CF-2025-00034', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2892, 'CF-2025-00034', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2893, 'CF-2025-00034', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2894, 'CF-2025-00034', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2895, 'CF-2025-00035', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2896, 'CF-2025-00035', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2897, 'CF-2025-00035', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2898, 'CF-2025-00035', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2899, 'CF-2025-00035', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2900, 'CF-2025-00035', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2901, 'CF-2025-00035', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2902, 'CF-2025-00035', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2903, 'CF-2025-00036', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2904, 'CF-2025-00036', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2905, 'CF-2025-00036', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2906, 'CF-2025-00036', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2907, 'CF-2025-00036', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2908, 'CF-2025-00036', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2909, 'CF-2025-00036', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2910, 'CF-2025-00036', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2911, 'CF-2025-00037', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2912, 'CF-2025-00037', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2913, 'CF-2025-00037', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2914, 'CF-2025-00037', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2915, 'CF-2025-00037', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2916, 'CF-2025-00037', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2917, 'CF-2025-00037', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2918, 'CF-2025-00037', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2919, 'CF-2025-00038', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2920, 'CF-2025-00038', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2921, 'CF-2025-00038', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2922, 'CF-2025-00038', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2923, 'CF-2025-00038', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2924, 'CF-2025-00038', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2925, 'CF-2025-00038', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2926, 'CF-2025-00038', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2927, 'CF-2025-00039', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2928, 'CF-2025-00039', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2929, 'CF-2025-00039', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2930, 'CF-2025-00039', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2931, 'CF-2025-00039', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2932, 'CF-2025-00039', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2933, 'CF-2025-00039', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2934, 'CF-2025-00039', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2935, 'CF-2025-00040', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2936, 'CF-2025-00040', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2937, 'CF-2025-00040', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2938, 'CF-2025-00040', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2939, 'CF-2025-00040', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2940, 'CF-2025-00040', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2941, 'CF-2025-00040', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2942, 'CF-2025-00040', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2943, 'CF-2025-00041', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2944, 'CF-2025-00041', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2945, 'CF-2025-00041', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2946, 'CF-2025-00041', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2947, 'CF-2025-00041', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2948, 'CF-2025-00041', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2949, 'CF-2025-00041', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2950, 'CF-2025-00041', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2951, 'CF-2025-00042', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2952, 'CF-2025-00042', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2953, 'CF-2025-00042', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2954, 'CF-2025-00042', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2955, 'CF-2025-00042', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2956, 'CF-2025-00042', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2957, 'CF-2025-00042', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2958, 'CF-2025-00042', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2959, 'CF-2025-00043', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2960, 'CF-2025-00043', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2961, 'CF-2025-00043', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2962, 'CF-2025-00043', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2963, 'CF-2025-00043', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2964, 'CF-2025-00043', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2965, 'CF-2025-00043', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2966, 'CF-2025-00043', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2967, 'CF-2025-00044', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2968, 'CF-2025-00044', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2969, 'CF-2025-00044', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2970, 'CF-2025-00044', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2971, 'CF-2025-00044', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2972, 'CF-2025-00044', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2973, 'CF-2025-00044', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2974, 'CF-2025-00044', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2975, 'CF-2025-00045', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2976, 'CF-2025-00045', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2977, 'CF-2025-00045', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2978, 'CF-2025-00045', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2979, 'CF-2025-00045', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2980, 'CF-2025-00045', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2981, 'CF-2025-00045', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2982, 'CF-2025-00045', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2983, 'CF-2025-00046', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2984, 'CF-2025-00046', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2985, 'CF-2025-00046', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2986, 'CF-2025-00046', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2987, 'CF-2025-00046', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2988, 'CF-2025-00046', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2989, 'CF-2025-00046', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2990, 'CF-2025-00046', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2991, 'CF-2025-00047', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2992, 'CF-2025-00047', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2993, 'CF-2025-00047', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2994, 'CF-2025-00047', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2995, 'CF-2025-00047', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2996, 'CF-2025-00047', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2997, 'CF-2025-00047', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2998, 'CF-2025-00047', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(2999, 'CF-2025-00048', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(3000, 'CF-2025-00048', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(3001, 'CF-2025-00048', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:19', '2025-09-18 01:16:19'),
(3002, 'CF-2025-00048', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3003, 'CF-2025-00048', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3004, 'CF-2025-00048', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3005, 'CF-2025-00048', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3006, 'CF-2025-00048', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3007, 'CF-2025-00049', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3008, 'CF-2025-00049', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3009, 'CF-2025-00049', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3010, 'CF-2025-00049', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3011, 'CF-2025-00049', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3012, 'CF-2025-00049', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3013, 'CF-2025-00049', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3014, 'CF-2025-00049', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3015, 'CF-2025-00050', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3016, 'CF-2025-00050', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3017, 'CF-2025-00050', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3018, 'CF-2025-00050', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3019, 'CF-2025-00050', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3020, 'CF-2025-00050', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3021, 'CF-2025-00050', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3022, 'CF-2025-00050', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3023, 'CF-2025-00051', 2, 190, 'Pending', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:37:23'),
(3024, 'CF-2025-00051', 16, 185, 'Pending', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 02:52:31'),
(3025, 'CF-2025-00051', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3026, 'CF-2025-00051', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3027, 'CF-2025-00051', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3028, 'CF-2025-00051', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3029, 'CF-2025-00051', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3030, 'CF-2025-00051', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3031, 'CF-2025-00052', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3032, 'CF-2025-00052', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3033, 'CF-2025-00052', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3034, 'CF-2025-00052', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3035, 'CF-2025-00052', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3036, 'CF-2025-00052', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3037, 'CF-2025-00052', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3038, 'CF-2025-00052', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3039, 'CF-2025-00053', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3040, 'CF-2025-00053', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3041, 'CF-2025-00053', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3042, 'CF-2025-00053', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3043, 'CF-2025-00053', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3044, 'CF-2025-00053', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3045, 'CF-2025-00053', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3046, 'CF-2025-00053', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3047, 'CF-2025-00054', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3048, 'CF-2025-00054', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3049, 'CF-2025-00054', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3050, 'CF-2025-00054', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3051, 'CF-2025-00054', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3052, 'CF-2025-00054', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3053, 'CF-2025-00054', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3054, 'CF-2025-00054', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3055, 'CF-2025-00055', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3056, 'CF-2025-00055', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3057, 'CF-2025-00055', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3058, 'CF-2025-00055', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3059, 'CF-2025-00055', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3060, 'CF-2025-00055', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3061, 'CF-2025-00055', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3062, 'CF-2025-00055', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3063, 'CF-2025-00056', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3064, 'CF-2025-00056', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3065, 'CF-2025-00056', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3066, 'CF-2025-00056', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3067, 'CF-2025-00056', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3068, 'CF-2025-00056', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3069, 'CF-2025-00056', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3070, 'CF-2025-00056', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3071, 'CF-2025-00057', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3072, 'CF-2025-00057', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3073, 'CF-2025-00057', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3074, 'CF-2025-00057', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3075, 'CF-2025-00057', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3076, 'CF-2025-00057', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3077, 'CF-2025-00057', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3078, 'CF-2025-00057', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3079, 'CF-2025-00058', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3080, 'CF-2025-00058', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3081, 'CF-2025-00058', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3082, 'CF-2025-00058', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3083, 'CF-2025-00058', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3084, 'CF-2025-00058', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3085, 'CF-2025-00058', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3086, 'CF-2025-00058', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3087, 'CF-2025-00059', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3088, 'CF-2025-00059', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3089, 'CF-2025-00059', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3090, 'CF-2025-00059', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3091, 'CF-2025-00059', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3092, 'CF-2025-00059', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3093, 'CF-2025-00059', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3094, 'CF-2025-00059', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20');
INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(3095, 'CF-2025-00060', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3096, 'CF-2025-00060', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3097, 'CF-2025-00060', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3098, 'CF-2025-00060', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3099, 'CF-2025-00060', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3100, 'CF-2025-00060', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3101, 'CF-2025-00060', 8, 180, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3102, 'CF-2025-00060', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3103, 'CF-2025-00061', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3104, 'CF-2025-00061', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3105, 'CF-2025-00061', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3106, 'CF-2025-00061', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3107, 'CF-2025-00061', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3108, 'CF-2025-00061', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3109, 'CF-2025-00061', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3110, 'CF-2025-00061', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3111, 'CF-2025-00061', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3112, 'CF-2025-00061', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3113, 'CF-2025-00061', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3114, 'CF-2025-00061', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3115, 'CF-2025-00061', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3116, 'CF-2025-00061', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3117, 'CF-2025-00061', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3118, 'CF-2025-00061', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3119, 'CF-2025-00061', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3120, 'CF-2025-00062', 13, 196, 'Unapplied', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-10-23 23:02:43'),
(3121, 'CF-2025-00062', 12, 195, 'Unapplied', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-10-23 23:02:43'),
(3122, 'CF-2025-00062', 17, 187, 'Unapplied', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-10-23 23:02:43'),
(3123, 'CF-2025-00062', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3124, 'CF-2025-00062', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3125, 'CF-2025-00062', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3126, 'CF-2025-00062', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3127, 'CF-2025-00062', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3128, 'CF-2025-00062', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3129, 'CF-2025-00062', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3130, 'CF-2025-00062', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3131, 'CF-2025-00062', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3132, 'CF-2025-00062', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3133, 'CF-2025-00062', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3134, 'CF-2025-00062', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3135, 'CF-2025-00062', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3136, 'CF-2025-00062', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3137, 'CF-2025-00063', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3138, 'CF-2025-00063', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3139, 'CF-2025-00063', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3140, 'CF-2025-00063', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3141, 'CF-2025-00063', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3142, 'CF-2025-00063', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3143, 'CF-2025-00063', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3144, 'CF-2025-00063', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3145, 'CF-2025-00063', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3146, 'CF-2025-00063', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3147, 'CF-2025-00063', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3148, 'CF-2025-00063', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3149, 'CF-2025-00063', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3150, 'CF-2025-00063', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3151, 'CF-2025-00063', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3152, 'CF-2025-00063', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3153, 'CF-2025-00063', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3154, 'CF-2025-00064', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3155, 'CF-2025-00064', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3156, 'CF-2025-00064', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3157, 'CF-2025-00064', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3158, 'CF-2025-00064', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3159, 'CF-2025-00064', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3160, 'CF-2025-00064', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3161, 'CF-2025-00064', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3162, 'CF-2025-00064', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3163, 'CF-2025-00064', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3164, 'CF-2025-00064', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3165, 'CF-2025-00064', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3166, 'CF-2025-00064', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3167, 'CF-2025-00064', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3168, 'CF-2025-00064', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3169, 'CF-2025-00064', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3170, 'CF-2025-00064', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3171, 'CF-2025-00065', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3172, 'CF-2025-00065', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3173, 'CF-2025-00065', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3174, 'CF-2025-00065', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3175, 'CF-2025-00065', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3176, 'CF-2025-00065', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3177, 'CF-2025-00065', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3178, 'CF-2025-00065', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3179, 'CF-2025-00065', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3180, 'CF-2025-00065', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3181, 'CF-2025-00065', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3182, 'CF-2025-00065', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3183, 'CF-2025-00065', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3184, 'CF-2025-00065', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3185, 'CF-2025-00065', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3186, 'CF-2025-00065', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3187, 'CF-2025-00065', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3188, 'CF-2025-00066', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3189, 'CF-2025-00066', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3190, 'CF-2025-00066', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3191, 'CF-2025-00066', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3192, 'CF-2025-00066', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3193, 'CF-2025-00066', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3194, 'CF-2025-00066', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3195, 'CF-2025-00066', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3196, 'CF-2025-00066', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3197, 'CF-2025-00066', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3198, 'CF-2025-00066', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3199, 'CF-2025-00066', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3200, 'CF-2025-00066', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3201, 'CF-2025-00066', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3202, 'CF-2025-00066', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3203, 'CF-2025-00066', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3204, 'CF-2025-00066', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3205, 'CF-2025-00067', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3206, 'CF-2025-00067', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3207, 'CF-2025-00067', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3208, 'CF-2025-00067', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3209, 'CF-2025-00067', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3210, 'CF-2025-00067', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3211, 'CF-2025-00067', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3212, 'CF-2025-00067', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3213, 'CF-2025-00067', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3214, 'CF-2025-00067', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3215, 'CF-2025-00067', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3216, 'CF-2025-00067', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3217, 'CF-2025-00067', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3218, 'CF-2025-00067', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3219, 'CF-2025-00067', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3220, 'CF-2025-00067', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3221, 'CF-2025-00067', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3222, 'CF-2025-00068', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3223, 'CF-2025-00068', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3224, 'CF-2025-00068', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3225, 'CF-2025-00068', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3226, 'CF-2025-00068', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3227, 'CF-2025-00068', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3228, 'CF-2025-00068', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3229, 'CF-2025-00068', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3230, 'CF-2025-00068', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3231, 'CF-2025-00068', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3232, 'CF-2025-00068', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3233, 'CF-2025-00068', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3234, 'CF-2025-00068', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3235, 'CF-2025-00068', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3236, 'CF-2025-00068', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3237, 'CF-2025-00068', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3238, 'CF-2025-00068', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3239, 'CF-2025-00069', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3240, 'CF-2025-00069', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3241, 'CF-2025-00069', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3242, 'CF-2025-00069', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3243, 'CF-2025-00069', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3244, 'CF-2025-00069', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3245, 'CF-2025-00069', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3246, 'CF-2025-00069', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3247, 'CF-2025-00069', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3248, 'CF-2025-00069', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3249, 'CF-2025-00069', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3250, 'CF-2025-00069', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3251, 'CF-2025-00069', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3252, 'CF-2025-00069', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3253, 'CF-2025-00069', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3254, 'CF-2025-00069', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3255, 'CF-2025-00069', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3256, 'CF-2025-00070', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3257, 'CF-2025-00070', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3258, 'CF-2025-00070', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3259, 'CF-2025-00070', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3260, 'CF-2025-00070', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3261, 'CF-2025-00070', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3262, 'CF-2025-00070', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3263, 'CF-2025-00070', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3264, 'CF-2025-00070', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3265, 'CF-2025-00070', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3266, 'CF-2025-00070', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3267, 'CF-2025-00070', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:20', '2025-09-18 01:16:20'),
(3268, 'CF-2025-00070', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3269, 'CF-2025-00070', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3270, 'CF-2025-00070', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3271, 'CF-2025-00070', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3272, 'CF-2025-00070', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3273, 'CF-2025-00071', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3274, 'CF-2025-00071', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3275, 'CF-2025-00071', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3276, 'CF-2025-00071', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3277, 'CF-2025-00071', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3278, 'CF-2025-00071', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3279, 'CF-2025-00071', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3280, 'CF-2025-00071', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3281, 'CF-2025-00071', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3282, 'CF-2025-00071', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3283, 'CF-2025-00071', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3284, 'CF-2025-00071', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3285, 'CF-2025-00071', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3286, 'CF-2025-00071', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3287, 'CF-2025-00071', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3288, 'CF-2025-00071', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3289, 'CF-2025-00071', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3290, 'CF-2025-00072', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3291, 'CF-2025-00072', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3292, 'CF-2025-00072', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3293, 'CF-2025-00072', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3294, 'CF-2025-00072', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3295, 'CF-2025-00072', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3296, 'CF-2025-00072', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3297, 'CF-2025-00072', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3298, 'CF-2025-00072', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3299, 'CF-2025-00072', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3300, 'CF-2025-00072', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3301, 'CF-2025-00072', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3302, 'CF-2025-00072', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3303, 'CF-2025-00072', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3304, 'CF-2025-00072', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3305, 'CF-2025-00072', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3306, 'CF-2025-00072', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3307, 'CF-2025-00073', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3308, 'CF-2025-00073', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3309, 'CF-2025-00073', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3310, 'CF-2025-00073', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3311, 'CF-2025-00073', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3312, 'CF-2025-00073', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3313, 'CF-2025-00073', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3314, 'CF-2025-00073', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3315, 'CF-2025-00073', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3316, 'CF-2025-00073', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3317, 'CF-2025-00073', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3318, 'CF-2025-00073', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3319, 'CF-2025-00073', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3320, 'CF-2025-00073', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3321, 'CF-2025-00073', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3322, 'CF-2025-00073', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3323, 'CF-2025-00073', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3324, 'CF-2025-00074', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3325, 'CF-2025-00074', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3326, 'CF-2025-00074', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3327, 'CF-2025-00074', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3328, 'CF-2025-00074', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3329, 'CF-2025-00074', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3330, 'CF-2025-00074', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3331, 'CF-2025-00074', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3332, 'CF-2025-00074', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3333, 'CF-2025-00074', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3334, 'CF-2025-00074', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3335, 'CF-2025-00074', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3336, 'CF-2025-00074', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3337, 'CF-2025-00074', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3338, 'CF-2025-00074', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3339, 'CF-2025-00074', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3340, 'CF-2025-00074', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3341, 'CF-2025-00075', 13, 196, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3342, 'CF-2025-00075', 12, 195, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3343, 'CF-2025-00075', 17, 187, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3344, 'CF-2025-00075', 5, 194, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3345, 'CF-2025-00075', 2, 190, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3346, 'CF-2025-00075', 16, 185, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3347, 'CF-2025-00075', 15, 184, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3348, 'CF-2025-00075', 14, 183, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3349, 'CF-2025-00075', 6, 197, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3350, 'CF-2025-00075', 3, 186, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3351, 'CF-2025-00075', 4, 192, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3352, 'CF-2025-00075', 10, 191, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3353, 'CF-2025-00075', 11, 193, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3354, 'CF-2025-00075', 8, 181, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3355, 'CF-2025-00075', 1, 189, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3356, 'CF-2025-00075', 9, 182, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3357, 'CF-2025-00075', 7, 188, '', NULL, NULL, NULL, NULL, '2025-09-18 01:16:21', '2025-09-18 01:16:21'),
(3358, 'CF-2025-00076', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3359, 'CF-2025-00076', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3360, 'CF-2025-00076', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3361, 'CF-2025-00076', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3362, 'CF-2025-00076', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3363, 'CF-2025-00076', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3364, 'CF-2025-00076', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3365, 'CF-2025-00076', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3366, 'CF-2025-00077', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3367, 'CF-2025-00077', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3368, 'CF-2025-00077', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3369, 'CF-2025-00077', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3370, 'CF-2025-00077', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3371, 'CF-2025-00077', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3372, 'CF-2025-00077', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3373, 'CF-2025-00077', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3374, 'CF-2025-00078', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3375, 'CF-2025-00078', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3376, 'CF-2025-00078', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3377, 'CF-2025-00078', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3378, 'CF-2025-00078', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3379, 'CF-2025-00078', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3380, 'CF-2025-00078', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3381, 'CF-2025-00078', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3382, 'CF-2025-00079', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3383, 'CF-2025-00079', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3384, 'CF-2025-00079', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3385, 'CF-2025-00079', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3386, 'CF-2025-00079', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3387, 'CF-2025-00079', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3388, 'CF-2025-00079', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3389, 'CF-2025-00079', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3390, 'CF-2025-00080', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3391, 'CF-2025-00080', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3392, 'CF-2025-00080', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3393, 'CF-2025-00080', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3394, 'CF-2025-00080', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3395, 'CF-2025-00080', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3396, 'CF-2025-00080', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3397, 'CF-2025-00080', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3398, 'CF-2025-00081', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3399, 'CF-2025-00081', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3400, 'CF-2025-00081', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3401, 'CF-2025-00081', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3402, 'CF-2025-00081', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3403, 'CF-2025-00081', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3404, 'CF-2025-00081', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3405, 'CF-2025-00081', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3406, 'CF-2025-00082', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3407, 'CF-2025-00082', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3408, 'CF-2025-00082', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3409, 'CF-2025-00082', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3410, 'CF-2025-00082', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3411, 'CF-2025-00082', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3412, 'CF-2025-00082', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3413, 'CF-2025-00082', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3414, 'CF-2025-00083', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3415, 'CF-2025-00083', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3416, 'CF-2025-00083', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3417, 'CF-2025-00083', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3418, 'CF-2025-00083', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3419, 'CF-2025-00083', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3420, 'CF-2025-00083', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3421, 'CF-2025-00083', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3422, 'CF-2025-00084', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3423, 'CF-2025-00084', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3424, 'CF-2025-00084', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3425, 'CF-2025-00084', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3426, 'CF-2025-00084', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3427, 'CF-2025-00084', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3428, 'CF-2025-00084', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3429, 'CF-2025-00084', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3430, 'CF-2025-00085', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3431, 'CF-2025-00085', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3432, 'CF-2025-00085', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3433, 'CF-2025-00085', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3434, 'CF-2025-00085', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3435, 'CF-2025-00085', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3436, 'CF-2025-00085', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3437, 'CF-2025-00085', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3438, 'CF-2025-00086', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3439, 'CF-2025-00086', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3440, 'CF-2025-00086', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3441, 'CF-2025-00086', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3442, 'CF-2025-00086', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3443, 'CF-2025-00086', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3444, 'CF-2025-00086', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3445, 'CF-2025-00086', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3446, 'CF-2025-00087', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3447, 'CF-2025-00087', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3448, 'CF-2025-00087', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3449, 'CF-2025-00087', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3450, 'CF-2025-00087', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3451, 'CF-2025-00087', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3452, 'CF-2025-00087', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3453, 'CF-2025-00087', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3454, 'CF-2025-00088', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3455, 'CF-2025-00088', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3456, 'CF-2025-00088', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3457, 'CF-2025-00088', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3458, 'CF-2025-00088', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3459, 'CF-2025-00088', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3460, 'CF-2025-00088', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3461, 'CF-2025-00088', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3462, 'CF-2025-00089', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3463, 'CF-2025-00089', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3464, 'CF-2025-00089', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3465, 'CF-2025-00089', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3466, 'CF-2025-00089', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3467, 'CF-2025-00089', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3468, 'CF-2025-00089', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3469, 'CF-2025-00089', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3470, 'CF-2025-00090', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3471, 'CF-2025-00090', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3472, 'CF-2025-00090', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3473, 'CF-2025-00090', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3474, 'CF-2025-00090', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3475, 'CF-2025-00090', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3476, 'CF-2025-00090', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3477, 'CF-2025-00090', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3478, 'CF-2025-00091', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3479, 'CF-2025-00091', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3480, 'CF-2025-00091', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3481, 'CF-2025-00091', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3482, 'CF-2025-00091', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3483, 'CF-2025-00091', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3484, 'CF-2025-00091', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3485, 'CF-2025-00091', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3486, 'CF-2025-00092', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3487, 'CF-2025-00092', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3488, 'CF-2025-00092', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3489, 'CF-2025-00092', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3490, 'CF-2025-00092', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3491, 'CF-2025-00092', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3492, 'CF-2025-00092', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3493, 'CF-2025-00092', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3494, 'CF-2025-00093', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3495, 'CF-2025-00093', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3496, 'CF-2025-00093', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3497, 'CF-2025-00093', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3498, 'CF-2025-00093', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3499, 'CF-2025-00093', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3500, 'CF-2025-00093', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3501, 'CF-2025-00093', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3502, 'CF-2025-00094', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3503, 'CF-2025-00094', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3504, 'CF-2025-00094', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3505, 'CF-2025-00094', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3506, 'CF-2025-00094', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3507, 'CF-2025-00094', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3508, 'CF-2025-00094', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3509, 'CF-2025-00094', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3510, 'CF-2025-00095', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3511, 'CF-2025-00095', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3512, 'CF-2025-00095', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3513, 'CF-2025-00095', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3514, 'CF-2025-00095', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3515, 'CF-2025-00095', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3516, 'CF-2025-00095', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3517, 'CF-2025-00095', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3518, 'CF-2025-00096', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3519, 'CF-2025-00096', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3520, 'CF-2025-00096', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3521, 'CF-2025-00096', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3522, 'CF-2025-00096', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3523, 'CF-2025-00096', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3524, 'CF-2025-00096', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3525, 'CF-2025-00096', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3526, 'CF-2025-00097', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3527, 'CF-2025-00097', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3528, 'CF-2025-00097', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3529, 'CF-2025-00097', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3530, 'CF-2025-00097', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3531, 'CF-2025-00097', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3532, 'CF-2025-00097', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3533, 'CF-2025-00097', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3534, 'CF-2025-00098', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3535, 'CF-2025-00098', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3536, 'CF-2025-00098', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3537, 'CF-2025-00098', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3538, 'CF-2025-00098', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3539, 'CF-2025-00098', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3540, 'CF-2025-00098', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3541, 'CF-2025-00098', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3542, 'CF-2025-00099', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3543, 'CF-2025-00099', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3544, 'CF-2025-00099', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3545, 'CF-2025-00099', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3546, 'CF-2025-00099', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3547, 'CF-2025-00099', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19');
INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(3548, 'CF-2025-00099', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3549, 'CF-2025-00099', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3550, 'CF-2025-00100', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3551, 'CF-2025-00100', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3552, 'CF-2025-00100', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3553, 'CF-2025-00100', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3554, 'CF-2025-00100', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3555, 'CF-2025-00100', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3556, 'CF-2025-00100', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3557, 'CF-2025-00100', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3558, 'CF-2025-00101', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3559, 'CF-2025-00101', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3560, 'CF-2025-00101', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3561, 'CF-2025-00101', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3562, 'CF-2025-00101', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3563, 'CF-2025-00101', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3564, 'CF-2025-00101', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3565, 'CF-2025-00101', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3566, 'CF-2025-00102', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3567, 'CF-2025-00102', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3568, 'CF-2025-00102', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3569, 'CF-2025-00102', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3570, 'CF-2025-00102', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3571, 'CF-2025-00102', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3572, 'CF-2025-00102', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3573, 'CF-2025-00102', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3574, 'CF-2025-00103', 2, 231, 'Approved', NULL, NULL, NULL, '2025-10-23 08:51:56', '2025-10-18 19:37:51', '2025-10-23 08:51:56'),
(3575, 'CF-2025-00103', 16, 236, 'Approved', NULL, NULL, NULL, '2025-10-23 20:40:17', '2025-10-18 19:37:51', '2025-10-23 20:40:17'),
(3576, 'CF-2025-00103', 15, 233, 'Approved', NULL, NULL, NULL, '2025-10-23 20:40:41', '2025-10-18 19:37:51', '2025-10-23 20:40:41'),
(3577, 'CF-2025-00103', 14, 237, 'Approved', NULL, NULL, NULL, '2025-10-23 20:42:12', '2025-10-18 19:37:51', '2025-10-23 20:42:12'),
(3578, 'CF-2025-00103', 3, 235, 'Approved', NULL, NULL, NULL, '2025-10-23 20:39:53', '2025-10-18 19:37:51', '2025-10-23 20:39:53'),
(3579, 'CF-2025-00103', 4, 234, 'Approved', NULL, NULL, NULL, '2025-10-23 20:39:22', '2025-10-18 19:37:51', '2025-10-23 20:39:22'),
(3580, 'CF-2025-00103', 8, 179, 'Approved', 'Approved by Program Head', NULL, NULL, '2025-10-23 20:29:41', '2025-10-18 19:37:51', '2025-10-23 20:29:41'),
(3581, 'CF-2025-00103', 1, 232, 'Approved', NULL, NULL, NULL, '2025-10-23 20:44:23', '2025-10-18 19:37:51', '2025-10-23 20:44:23'),
(3582, 'CF-2025-00104', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3583, 'CF-2025-00104', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3584, 'CF-2025-00104', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3585, 'CF-2025-00104', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3586, 'CF-2025-00104', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3587, 'CF-2025-00104', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3588, 'CF-2025-00104', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3589, 'CF-2025-00104', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-18 19:37:51', '2025-10-21 13:38:19'),
(3590, 'CF-2025-00105', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3591, 'CF-2025-00105', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3592, 'CF-2025-00105', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3593, 'CF-2025-00105', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3594, 'CF-2025-00105', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3595, 'CF-2025-00105', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3596, 'CF-2025-00105', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3597, 'CF-2025-00105', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3598, 'CF-2025-00105', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3599, 'CF-2025-00105', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3600, 'CF-2025-00105', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3601, 'CF-2025-00105', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3603, 'CF-2025-00105', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3604, 'CF-2025-00106', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3605, 'CF-2025-00106', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3606, 'CF-2025-00106', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3607, 'CF-2025-00106', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3608, 'CF-2025-00106', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3609, 'CF-2025-00106', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3610, 'CF-2025-00106', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3611, 'CF-2025-00106', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3612, 'CF-2025-00106', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3613, 'CF-2025-00106', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3614, 'CF-2025-00106', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3615, 'CF-2025-00106', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3617, 'CF-2025-00106', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3618, 'CF-2025-00107', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3619, 'CF-2025-00107', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3620, 'CF-2025-00107', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3621, 'CF-2025-00107', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3622, 'CF-2025-00107', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3623, 'CF-2025-00107', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3624, 'CF-2025-00107', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3625, 'CF-2025-00107', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3626, 'CF-2025-00107', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3627, 'CF-2025-00107', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3628, 'CF-2025-00107', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3629, 'CF-2025-00107', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3631, 'CF-2025-00107', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3632, 'CF-2025-00108', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3633, 'CF-2025-00108', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3634, 'CF-2025-00108', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3635, 'CF-2025-00108', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3636, 'CF-2025-00108', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3637, 'CF-2025-00108', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3638, 'CF-2025-00108', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3639, 'CF-2025-00108', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3640, 'CF-2025-00108', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3641, 'CF-2025-00108', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3642, 'CF-2025-00108', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3643, 'CF-2025-00108', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3645, 'CF-2025-00108', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3646, 'CF-2025-00109', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3647, 'CF-2025-00109', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3648, 'CF-2025-00109', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3649, 'CF-2025-00109', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3650, 'CF-2025-00109', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3651, 'CF-2025-00109', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3652, 'CF-2025-00109', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3653, 'CF-2025-00109', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3654, 'CF-2025-00109', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3655, 'CF-2025-00109', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3656, 'CF-2025-00109', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3657, 'CF-2025-00109', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3659, 'CF-2025-00109', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3660, 'CF-2025-00110', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3661, 'CF-2025-00110', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3662, 'CF-2025-00110', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3663, 'CF-2025-00110', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3664, 'CF-2025-00110', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3665, 'CF-2025-00110', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3666, 'CF-2025-00110', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3667, 'CF-2025-00110', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3668, 'CF-2025-00110', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3669, 'CF-2025-00110', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3670, 'CF-2025-00110', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3671, 'CF-2025-00110', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3673, 'CF-2025-00110', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3674, 'CF-2025-00111', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3675, 'CF-2025-00111', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3676, 'CF-2025-00111', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3677, 'CF-2025-00111', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3678, 'CF-2025-00111', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3679, 'CF-2025-00111', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3680, 'CF-2025-00111', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3681, 'CF-2025-00111', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3682, 'CF-2025-00111', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3683, 'CF-2025-00111', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3684, 'CF-2025-00111', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3685, 'CF-2025-00111', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3687, 'CF-2025-00111', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3688, 'CF-2025-00112', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3689, 'CF-2025-00112', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3690, 'CF-2025-00112', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3691, 'CF-2025-00112', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3692, 'CF-2025-00112', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3693, 'CF-2025-00112', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3694, 'CF-2025-00112', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3695, 'CF-2025-00112', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3696, 'CF-2025-00112', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3697, 'CF-2025-00112', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3698, 'CF-2025-00112', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3699, 'CF-2025-00112', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3701, 'CF-2025-00112', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3702, 'CF-2025-00113', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3703, 'CF-2025-00113', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3704, 'CF-2025-00113', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3705, 'CF-2025-00113', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3706, 'CF-2025-00113', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3707, 'CF-2025-00113', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3708, 'CF-2025-00113', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3709, 'CF-2025-00113', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3710, 'CF-2025-00113', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3711, 'CF-2025-00113', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3712, 'CF-2025-00113', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3713, 'CF-2025-00113', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3715, 'CF-2025-00113', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3716, 'CF-2025-00114', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3717, 'CF-2025-00114', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3718, 'CF-2025-00114', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3719, 'CF-2025-00114', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3720, 'CF-2025-00114', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3721, 'CF-2025-00114', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3722, 'CF-2025-00114', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3723, 'CF-2025-00114', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3724, 'CF-2025-00114', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3725, 'CF-2025-00114', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3726, 'CF-2025-00114', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3727, 'CF-2025-00114', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3729, 'CF-2025-00114', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3730, 'CF-2025-00115', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3731, 'CF-2025-00115', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3732, 'CF-2025-00115', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3733, 'CF-2025-00115', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3734, 'CF-2025-00115', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3735, 'CF-2025-00115', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3736, 'CF-2025-00115', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3737, 'CF-2025-00115', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3738, 'CF-2025-00115', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3739, 'CF-2025-00115', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3740, 'CF-2025-00115', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3741, 'CF-2025-00115', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3743, 'CF-2025-00115', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3744, 'CF-2025-00116', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3745, 'CF-2025-00116', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3746, 'CF-2025-00116', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3747, 'CF-2025-00116', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3748, 'CF-2025-00116', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3749, 'CF-2025-00116', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3750, 'CF-2025-00116', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3751, 'CF-2025-00116', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3752, 'CF-2025-00116', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3753, 'CF-2025-00116', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3754, 'CF-2025-00116', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3755, 'CF-2025-00116', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3757, 'CF-2025-00116', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3758, 'CF-2025-00117', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3759, 'CF-2025-00117', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3760, 'CF-2025-00117', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3761, 'CF-2025-00117', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3762, 'CF-2025-00117', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3763, 'CF-2025-00117', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3764, 'CF-2025-00117', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3765, 'CF-2025-00117', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3766, 'CF-2025-00117', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3767, 'CF-2025-00117', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3768, 'CF-2025-00117', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3769, 'CF-2025-00117', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3771, 'CF-2025-00117', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3772, 'CF-2025-00118', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3773, 'CF-2025-00118', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3774, 'CF-2025-00118', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3775, 'CF-2025-00118', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3776, 'CF-2025-00118', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3777, 'CF-2025-00118', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3778, 'CF-2025-00118', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3779, 'CF-2025-00118', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3780, 'CF-2025-00118', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3781, 'CF-2025-00118', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3782, 'CF-2025-00118', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3783, 'CF-2025-00118', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3785, 'CF-2025-00118', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3786, 'CF-2025-00119', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3787, 'CF-2025-00119', 12, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-24 02:17:45'),
(3788, 'CF-2025-00119', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3789, 'CF-2025-00119', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3790, 'CF-2025-00119', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3791, 'CF-2025-00119', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3792, 'CF-2025-00119', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3793, 'CF-2025-00119', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3794, 'CF-2025-00119', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3795, 'CF-2025-00119', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3796, 'CF-2025-00119', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3797, 'CF-2025-00119', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3799, 'CF-2025-00119', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3800, 'CF-2025-00120', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3801, 'CF-2025-00120', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3802, 'CF-2025-00120', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3803, 'CF-2025-00120', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3804, 'CF-2025-00120', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3805, 'CF-2025-00120', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3806, 'CF-2025-00120', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3807, 'CF-2025-00120', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3808, 'CF-2025-00120', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3809, 'CF-2025-00120', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3810, 'CF-2025-00120', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3811, 'CF-2025-00120', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3813, 'CF-2025-00120', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3814, 'CF-2025-00121', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3815, 'CF-2025-00121', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3816, 'CF-2025-00121', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3817, 'CF-2025-00121', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3818, 'CF-2025-00121', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3819, 'CF-2025-00121', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3820, 'CF-2025-00121', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3821, 'CF-2025-00121', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3822, 'CF-2025-00121', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3823, 'CF-2025-00121', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3824, 'CF-2025-00121', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3825, 'CF-2025-00121', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3827, 'CF-2025-00121', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:13:03', '2025-10-23 23:02:43'),
(3828, 'CF-2025-00122', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3829, 'CF-2025-00122', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3830, 'CF-2025-00122', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3831, 'CF-2025-00122', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3832, 'CF-2025-00122', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3833, 'CF-2025-00122', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3834, 'CF-2025-00122', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3835, 'CF-2025-00122', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3836, 'CF-2025-00122', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3837, 'CF-2025-00122', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3838, 'CF-2025-00122', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3839, 'CF-2025-00122', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3841, 'CF-2025-00122', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3842, 'CF-2025-00123', 13, 244, 'Approved', 'Approved by Academic Head', NULL, NULL, '2025-10-24 04:56:50', '2025-10-23 22:23:41', '2025-10-24 04:56:50'),
(3843, 'CF-2025-00123', 12, 245, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-24 04:50:44', '2025-10-23 22:23:41', '2025-10-24 04:50:44'),
(3844, 'CF-2025-00123', 5, 241, 'Approved', 'Approved by Building Administrator', NULL, NULL, '2025-10-24 04:56:23', '2025-10-23 22:23:41', '2025-10-24 04:56:23'),
(3845, 'CF-2025-00123', 15, 233, 'Approved', 'Approved by Disciplinary Officer', NULL, NULL, '2025-10-24 04:55:23', '2025-10-23 22:23:41', '2025-10-24 04:55:23'),
(3846, 'CF-2025-00123', 14, 237, 'Approved', 'Approved by Guidance', NULL, NULL, '2025-10-24 04:55:55', '2025-10-23 22:23:41', '2025-10-24 04:55:55'),
(3847, 'CF-2025-00123', 6, 247, 'Approved', 'Approved by HR', NULL, NULL, '2025-10-24 04:57:59', '2025-10-23 22:23:41', '2025-10-24 04:57:59'),
(3848, 'CF-2025-00123', 3, 235, 'Approved', 'Approved by Librarian', NULL, NULL, '2025-10-24 04:55:04', '2025-10-23 22:23:41', '2025-10-24 04:55:04'),
(3849, 'CF-2025-00123', 4, 234, 'Approved', 'Approved by MIS/IT', NULL, NULL, '2025-10-24 04:54:33', '2025-10-23 22:23:41', '2025-10-24 04:54:33'),
(3850, 'CF-2025-00123', 10, 240, 'Approved', 'Approved by PAMO', NULL, NULL, '2025-10-24 05:42:23', '2025-10-23 22:23:41', '2025-10-24 05:42:23'),
(3851, 'CF-2025-00123', 11, 242, 'Approved', 'Approved by Petty Cash Custodian', NULL, NULL, '2025-10-24 04:56:09', '2025-10-23 22:23:41', '2025-10-24 04:56:09'),
(3852, 'CF-2025-00123', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3853, 'CF-2025-00123', 9, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-24 04:53:09'),
(3855, 'CF-2025-00123', 7, 243, 'Approved', 'Approved by Student Affairs Officer', NULL, NULL, '2025-10-24 04:56:36', '2025-10-23 22:23:41', '2025-10-24 04:56:36'),
(3856, 'CF-2025-00124', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3857, 'CF-2025-00124', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3858, 'CF-2025-00124', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3859, 'CF-2025-00124', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3860, 'CF-2025-00124', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3861, 'CF-2025-00124', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3862, 'CF-2025-00124', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3863, 'CF-2025-00124', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3864, 'CF-2025-00124', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3865, 'CF-2025-00124', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3866, 'CF-2025-00124', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3867, 'CF-2025-00124', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3869, 'CF-2025-00124', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3870, 'CF-2025-00125', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3871, 'CF-2025-00125', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3872, 'CF-2025-00125', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3873, 'CF-2025-00125', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3874, 'CF-2025-00125', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3875, 'CF-2025-00125', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3876, 'CF-2025-00125', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3877, 'CF-2025-00125', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3878, 'CF-2025-00125', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3879, 'CF-2025-00125', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3880, 'CF-2025-00125', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3881, 'CF-2025-00125', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3883, 'CF-2025-00125', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3884, 'CF-2025-00126', 13, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3885, 'CF-2025-00126', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3886, 'CF-2025-00126', 5, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3887, 'CF-2025-00126', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3888, 'CF-2025-00126', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3889, 'CF-2025-00126', 6, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3890, 'CF-2025-00126', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3891, 'CF-2025-00126', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3892, 'CF-2025-00126', 10, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3893, 'CF-2025-00126', 11, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3894, 'CF-2025-00126', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3895, 'CF-2025-00126', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3897, 'CF-2025-00126', 7, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:23:41', '2025-10-23 23:02:43'),
(3898, 'CF-2025-00105', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3899, 'CF-2025-00122', 8, 181, 'Approved', NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3900, 'CF-2025-00106', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3901, 'CF-2025-00107', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3902, 'CF-2025-00108', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3903, 'CF-2025-00109', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3904, 'CF-2025-00110', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3905, 'CF-2025-00111', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3906, 'CF-2025-00123', 8, 214, 'Approved', NULL, NULL, NULL, '2025-10-24 05:39:10', '2025-10-23 22:54:17', '2025-10-24 05:39:10'),
(3907, 'CF-2025-00112', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3908, 'CF-2025-00113', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3909, 'CF-2025-00114', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3910, 'CF-2025-00115', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3911, 'CF-2025-00124', 8, 180, 'Approved', NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3912, 'CF-2025-00116', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3913, 'CF-2025-00117', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3914, 'CF-2025-00125', 8, 179, 'Approved', NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3915, 'CF-2025-00118', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3916, 'CF-2025-00119', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3917, 'CF-2025-00120', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3918, 'CF-2025-00121', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3919, 'CF-2025-00126', 8, 214, 'Approved', NULL, NULL, NULL, '2025-10-23 22:54:17', '2025-10-23 22:54:17', '2025-10-23 22:54:17'),
(3920, 'CF-2025-00127', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3921, 'CF-2025-00127', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3922, 'CF-2025-00127', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3923, 'CF-2025-00127', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3924, 'CF-2025-00127', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3925, 'CF-2025-00127', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3926, 'CF-2025-00127', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3927, 'CF-2025-00127', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3928, 'CF-2025-00127', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3929, 'CF-2025-00128', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3930, 'CF-2025-00128', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3931, 'CF-2025-00128', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3932, 'CF-2025-00128', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3933, 'CF-2025-00128', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3934, 'CF-2025-00128', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3935, 'CF-2025-00128', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3936, 'CF-2025-00128', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3937, 'CF-2025-00128', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3938, 'CF-2025-00129', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3939, 'CF-2025-00129', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3940, 'CF-2025-00129', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3941, 'CF-2025-00129', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3942, 'CF-2025-00129', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3943, 'CF-2025-00129', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3944, 'CF-2025-00129', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3945, 'CF-2025-00129', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3946, 'CF-2025-00129', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3947, 'CF-2025-00130', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3948, 'CF-2025-00130', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3949, 'CF-2025-00130', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3950, 'CF-2025-00130', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3951, 'CF-2025-00130', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3952, 'CF-2025-00130', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3953, 'CF-2025-00130', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3954, 'CF-2025-00130', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3955, 'CF-2025-00130', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3956, 'CF-2025-00131', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3957, 'CF-2025-00131', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3958, 'CF-2025-00131', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3959, 'CF-2025-00131', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3960, 'CF-2025-00131', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3961, 'CF-2025-00131', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3962, 'CF-2025-00131', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3963, 'CF-2025-00131', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3964, 'CF-2025-00131', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3965, 'CF-2025-00132', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3966, 'CF-2025-00132', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3967, 'CF-2025-00132', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3968, 'CF-2025-00132', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3969, 'CF-2025-00132', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3970, 'CF-2025-00132', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3971, 'CF-2025-00132', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3972, 'CF-2025-00132', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3973, 'CF-2025-00132', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3974, 'CF-2025-00133', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3975, 'CF-2025-00133', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3976, 'CF-2025-00133', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3977, 'CF-2025-00133', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3978, 'CF-2025-00133', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3979, 'CF-2025-00133', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3980, 'CF-2025-00133', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3981, 'CF-2025-00133', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3982, 'CF-2025-00133', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3983, 'CF-2025-00134', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3984, 'CF-2025-00134', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3985, 'CF-2025-00134', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3986, 'CF-2025-00134', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3987, 'CF-2025-00134', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3988, 'CF-2025-00134', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3989, 'CF-2025-00134', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3990, 'CF-2025-00134', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3991, 'CF-2025-00134', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3992, 'CF-2025-00135', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3993, 'CF-2025-00135', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3994, 'CF-2025-00135', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3995, 'CF-2025-00135', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52');
INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(3996, 'CF-2025-00135', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3997, 'CF-2025-00135', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3998, 'CF-2025-00135', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(3999, 'CF-2025-00135', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4000, 'CF-2025-00135', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4001, 'CF-2025-00136', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4002, 'CF-2025-00136', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4003, 'CF-2025-00136', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4004, 'CF-2025-00136', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4005, 'CF-2025-00136', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4006, 'CF-2025-00136', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4007, 'CF-2025-00136', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4008, 'CF-2025-00136', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4009, 'CF-2025-00136', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4010, 'CF-2025-00137', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4011, 'CF-2025-00137', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4012, 'CF-2025-00137', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4013, 'CF-2025-00137', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4014, 'CF-2025-00137', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4015, 'CF-2025-00137', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4016, 'CF-2025-00137', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4017, 'CF-2025-00137', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4018, 'CF-2025-00137', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4019, 'CF-2025-00138', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4020, 'CF-2025-00138', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4021, 'CF-2025-00138', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4022, 'CF-2025-00138', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4023, 'CF-2025-00138', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4024, 'CF-2025-00138', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4025, 'CF-2025-00138', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4026, 'CF-2025-00138', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4027, 'CF-2025-00138', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4028, 'CF-2025-00139', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4029, 'CF-2025-00139', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4030, 'CF-2025-00139', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4031, 'CF-2025-00139', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4032, 'CF-2025-00139', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4033, 'CF-2025-00139', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4034, 'CF-2025-00139', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4035, 'CF-2025-00139', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4036, 'CF-2025-00139', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4037, 'CF-2025-00140', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4038, 'CF-2025-00140', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4039, 'CF-2025-00140', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4040, 'CF-2025-00140', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4041, 'CF-2025-00140', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4042, 'CF-2025-00140', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4043, 'CF-2025-00140', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4044, 'CF-2025-00140', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4045, 'CF-2025-00140', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4046, 'CF-2025-00141', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4047, 'CF-2025-00141', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4048, 'CF-2025-00141', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4049, 'CF-2025-00141', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4050, 'CF-2025-00141', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4051, 'CF-2025-00141', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4052, 'CF-2025-00141', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4053, 'CF-2025-00141', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4054, 'CF-2025-00141', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4055, 'CF-2025-00142', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4056, 'CF-2025-00142', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4057, 'CF-2025-00142', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4058, 'CF-2025-00142', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4059, 'CF-2025-00142', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4060, 'CF-2025-00142', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4061, 'CF-2025-00142', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4062, 'CF-2025-00142', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4063, 'CF-2025-00142', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4064, 'CF-2025-00143', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4065, 'CF-2025-00143', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4066, 'CF-2025-00143', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4067, 'CF-2025-00143', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4068, 'CF-2025-00143', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4069, 'CF-2025-00143', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4070, 'CF-2025-00143', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4071, 'CF-2025-00143', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4072, 'CF-2025-00143', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4073, 'CF-2025-00144', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4074, 'CF-2025-00144', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4075, 'CF-2025-00144', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4076, 'CF-2025-00144', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4077, 'CF-2025-00144', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4078, 'CF-2025-00144', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4079, 'CF-2025-00144', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4080, 'CF-2025-00144', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4081, 'CF-2025-00144', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4082, 'CF-2025-00145', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4083, 'CF-2025-00145', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4084, 'CF-2025-00145', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4085, 'CF-2025-00145', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4086, 'CF-2025-00145', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4087, 'CF-2025-00145', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4088, 'CF-2025-00145', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:52', '2025-10-27 08:03:52'),
(4089, 'CF-2025-00145', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4090, 'CF-2025-00145', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4091, 'CF-2025-00146', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4092, 'CF-2025-00146', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4093, 'CF-2025-00146', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4094, 'CF-2025-00146', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4095, 'CF-2025-00146', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4096, 'CF-2025-00146', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4097, 'CF-2025-00146', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4098, 'CF-2025-00146', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4099, 'CF-2025-00146', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4100, 'CF-2025-00147', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4101, 'CF-2025-00147', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4102, 'CF-2025-00147', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4103, 'CF-2025-00147', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4104, 'CF-2025-00147', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4105, 'CF-2025-00147', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4106, 'CF-2025-00147', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4107, 'CF-2025-00147', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4108, 'CF-2025-00147', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4109, 'CF-2025-00148', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4110, 'CF-2025-00148', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4111, 'CF-2025-00148', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4112, 'CF-2025-00148', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4113, 'CF-2025-00148', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4114, 'CF-2025-00148', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4115, 'CF-2025-00148', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4116, 'CF-2025-00148', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4117, 'CF-2025-00148', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4118, 'CF-2025-00149', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4119, 'CF-2025-00149', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4120, 'CF-2025-00149', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4121, 'CF-2025-00149', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4122, 'CF-2025-00149', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4123, 'CF-2025-00149', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4124, 'CF-2025-00149', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4125, 'CF-2025-00149', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4126, 'CF-2025-00149', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4127, 'CF-2025-00150', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4128, 'CF-2025-00150', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4129, 'CF-2025-00150', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4130, 'CF-2025-00150', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4131, 'CF-2025-00150', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4132, 'CF-2025-00150', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4133, 'CF-2025-00150', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4134, 'CF-2025-00150', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4135, 'CF-2025-00150', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4136, 'CF-2025-00151', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4137, 'CF-2025-00151', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4138, 'CF-2025-00151', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4139, 'CF-2025-00151', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4140, 'CF-2025-00151', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4141, 'CF-2025-00151', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4142, 'CF-2025-00151', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4143, 'CF-2025-00151', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4144, 'CF-2025-00151', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4145, 'CF-2025-00152', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4146, 'CF-2025-00152', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4147, 'CF-2025-00152', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4148, 'CF-2025-00152', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4149, 'CF-2025-00152', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4150, 'CF-2025-00152', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4151, 'CF-2025-00152', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4152, 'CF-2025-00152', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4153, 'CF-2025-00152', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4154, 'CF-2025-00153', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4155, 'CF-2025-00153', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4156, 'CF-2025-00153', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4157, 'CF-2025-00153', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4158, 'CF-2025-00153', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4159, 'CF-2025-00153', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4160, 'CF-2025-00153', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4161, 'CF-2025-00153', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4162, 'CF-2025-00153', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4163, 'CF-2025-00154', 2, 231, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:22:22', '2025-10-27 08:03:53', '2025-10-27 10:22:22'),
(4164, 'CF-2025-00154', 16, 236, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:23:56', '2025-10-27 08:03:53', '2025-10-27 10:23:56'),
(4165, 'CF-2025-00154', 15, 233, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:24:32', '2025-10-27 08:03:53', '2025-10-27 10:24:32'),
(4166, 'CF-2025-00154', 14, 237, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:25:16', '2025-10-27 08:03:53', '2025-10-27 10:25:16'),
(4167, 'CF-2025-00154', 3, 235, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:26:43', '2025-10-27 08:03:53', '2025-10-27 10:26:43'),
(4168, 'CF-2025-00154', 4, 234, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:27:49', '2025-10-27 08:03:53', '2025-10-27 10:27:49'),
(4169, 'CF-2025-00154', 8, 179, 'Approved', 'Approved by Program Head', NULL, NULL, '2025-10-27 10:28:57', '2025-10-27 08:03:53', '2025-10-27 10:28:57'),
(4170, 'CF-2025-00154', 1, 232, 'Approved', 'Approved by Staff', NULL, NULL, '2025-10-27 10:29:54', '2025-10-27 08:03:53', '2025-10-27 10:29:54'),
(4171, 'CF-2025-00154', 8, 179, 'Approved', 'Approved by Program Head', NULL, NULL, '2025-10-27 10:28:57', '2025-10-27 08:03:53', '2025-10-27 10:28:57'),
(4172, 'CF-2025-00155', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4173, 'CF-2025-00155', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4174, 'CF-2025-00155', 15, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4175, 'CF-2025-00155', 14, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4176, 'CF-2025-00155', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4177, 'CF-2025-00155', 4, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4178, 'CF-2025-00155', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4179, 'CF-2025-00155', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4180, 'CF-2025-00155', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:03:53', '2025-10-27 08:03:53'),
(4181, 'CF-2025-00156', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4182, 'CF-2025-00156', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4183, 'CF-2025-00156', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4184, 'CF-2025-00156', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4185, 'CF-2025-00157', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4186, 'CF-2025-00157', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4187, 'CF-2025-00157', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4188, 'CF-2025-00157', 8, 181, 'Approved', NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4189, 'CF-2025-00158', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4190, 'CF-2025-00158', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4191, 'CF-2025-00158', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4192, 'CF-2025-00158', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4193, 'CF-2025-00159', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4194, 'CF-2025-00159', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4195, 'CF-2025-00159', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4196, 'CF-2025-00159', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4197, 'CF-2025-00160', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4198, 'CF-2025-00160', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4199, 'CF-2025-00160', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4200, 'CF-2025-00160', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4201, 'CF-2025-00161', 12, 195, 'Approved', 'Approved by Accountant', NULL, NULL, '2025-10-27 09:02:47', '2025-10-27 08:23:12', '2025-10-27 09:02:47'),
(4202, 'CF-2025-00161', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4203, 'CF-2025-00161', 9, 182, 'Approved', NULL, NULL, NULL, '2025-10-27 10:09:33', '2025-10-27 08:23:12', '2025-10-27 10:09:33'),
(4204, 'CF-2025-00161', 8, NULL, 'Rejected', NULL, 12, 'Rejected', NULL, '2025-10-27 08:23:12', '2025-10-27 10:00:16'),
(4205, 'CF-2025-00162', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4206, 'CF-2025-00162', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4207, 'CF-2025-00162', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4208, 'CF-2025-00162', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4209, 'CF-2025-00163', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4210, 'CF-2025-00163', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4211, 'CF-2025-00163', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4212, 'CF-2025-00163', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4213, 'CF-2025-00164', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4214, 'CF-2025-00164', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4215, 'CF-2025-00164', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4216, 'CF-2025-00164', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4217, 'CF-2025-00165', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4218, 'CF-2025-00165', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4219, 'CF-2025-00165', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4220, 'CF-2025-00165', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4221, 'CF-2025-00166', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4222, 'CF-2025-00166', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4223, 'CF-2025-00166', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4224, 'CF-2025-00166', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4225, 'CF-2025-00167', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4226, 'CF-2025-00167', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4227, 'CF-2025-00167', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4228, 'CF-2025-00167', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4229, 'CF-2025-00168', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4230, 'CF-2025-00168', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4231, 'CF-2025-00168', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4232, 'CF-2025-00168', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4233, 'CF-2025-00169', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4234, 'CF-2025-00169', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4235, 'CF-2025-00169', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4236, 'CF-2025-00169', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4237, 'CF-2025-00170', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4238, 'CF-2025-00170', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4239, 'CF-2025-00170', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4240, 'CF-2025-00170', 8, 180, 'Approved', NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4241, 'CF-2025-00171', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4242, 'CF-2025-00171', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4243, 'CF-2025-00171', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4244, 'CF-2025-00171', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4245, 'CF-2025-00172', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4246, 'CF-2025-00172', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4247, 'CF-2025-00172', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4248, 'CF-2025-00172', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4249, 'CF-2025-00173', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4250, 'CF-2025-00173', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4251, 'CF-2025-00173', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4252, 'CF-2025-00173', 8, 179, 'Approved', NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4253, 'CF-2025-00174', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4254, 'CF-2025-00174', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4255, 'CF-2025-00174', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4256, 'CF-2025-00174', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4257, 'CF-2025-00175', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4258, 'CF-2025-00175', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4259, 'CF-2025-00175', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4260, 'CF-2025-00175', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4261, 'CF-2025-00176', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4262, 'CF-2025-00176', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4263, 'CF-2025-00176', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4264, 'CF-2025-00176', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4265, 'CF-2025-00177', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4266, 'CF-2025-00177', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4267, 'CF-2025-00177', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4268, 'CF-2025-00177', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4269, 'CF-2025-00178', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4270, 'CF-2025-00178', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4271, 'CF-2025-00178', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4272, 'CF-2025-00178', 8, 214, 'Approved', NULL, NULL, NULL, '2025-10-27 08:23:12', '2025-10-27 08:23:12', '2025-10-27 08:23:12'),
(4273, 'CF-2025-00179', 12, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 09:49:39', '2025-10-27 09:49:39'),
(4274, 'CF-2025-00179', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 09:49:39', '2025-10-27 09:49:39'),
(4275, 'CF-2025-00179', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 09:49:39', '2025-10-27 09:49:39'),
(4276, 'CF-2025-00179', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-27 09:49:39', '2025-10-27 09:49:39'),
(4277, 'CF-2025-00180', 12, 195, 'Approved', 'Approved by Accountant', NULL, NULL, '2025-10-27 09:58:46', '2025-10-27 09:53:36', '2025-10-27 09:58:46'),
(4278, 'CF-2025-00180', 1, 232, 'Approved', 'Approved by Registrar', NULL, NULL, '2025-10-27 10:14:47', '2025-10-27 09:53:36', '2025-10-27 10:14:47'),
(4279, 'CF-2025-00180', 9, 182, 'Approved', NULL, NULL, NULL, '2025-10-27 10:09:28', '2025-10-27 09:53:36', '2025-10-27 10:09:28'),
(4280, 'CF-2025-00180', 8, 214, 'Approved', NULL, NULL, NULL, '2025-10-27 10:06:26', '2025-10-27 09:53:36', '2025-10-27 10:06:26'),
(4281, 'CF-2025-00181', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4282, 'CF-2025-00181', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4283, 'CF-2025-00181', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4284, 'CF-2025-00181', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4285, 'CF-2025-00182', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4286, 'CF-2025-00182', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4287, 'CF-2025-00182', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4288, 'CF-2025-00182', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4289, 'CF-2025-00183', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4290, 'CF-2025-00183', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4291, 'CF-2025-00183', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4292, 'CF-2025-00183', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4293, 'CF-2025-00184', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4294, 'CF-2025-00184', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4295, 'CF-2025-00184', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4296, 'CF-2025-00184', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4297, 'CF-2025-00185', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4298, 'CF-2025-00185', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4299, 'CF-2025-00185', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4300, 'CF-2025-00185', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4301, 'CF-2025-00186', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4302, 'CF-2025-00186', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4303, 'CF-2025-00186', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4304, 'CF-2025-00186', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4305, 'CF-2025-00187', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4306, 'CF-2025-00187', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4307, 'CF-2025-00187', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4308, 'CF-2025-00187', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4309, 'CF-2025-00188', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4310, 'CF-2025-00188', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4311, 'CF-2025-00188', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4312, 'CF-2025-00188', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4313, 'CF-2025-00189', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4314, 'CF-2025-00189', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4315, 'CF-2025-00189', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4316, 'CF-2025-00189', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4317, 'CF-2025-00190', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4318, 'CF-2025-00190', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4319, 'CF-2025-00190', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4320, 'CF-2025-00190', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4321, 'CF-2025-00191', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4322, 'CF-2025-00191', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4323, 'CF-2025-00191', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4324, 'CF-2025-00191', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4325, 'CF-2025-00192', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4326, 'CF-2025-00192', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4327, 'CF-2025-00192', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4328, 'CF-2025-00192', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4329, 'CF-2025-00193', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4330, 'CF-2025-00193', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4331, 'CF-2025-00193', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4332, 'CF-2025-00193', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4333, 'CF-2025-00194', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4334, 'CF-2025-00194', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4335, 'CF-2025-00194', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4336, 'CF-2025-00194', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4337, 'CF-2025-00195', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4338, 'CF-2025-00195', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4339, 'CF-2025-00195', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4340, 'CF-2025-00195', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4341, 'CF-2025-00196', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4342, 'CF-2025-00196', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4343, 'CF-2025-00196', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4344, 'CF-2025-00196', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4345, 'CF-2025-00197', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4346, 'CF-2025-00197', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4347, 'CF-2025-00197', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4348, 'CF-2025-00197', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4349, 'CF-2025-00198', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4350, 'CF-2025-00198', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4351, 'CF-2025-00198', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4352, 'CF-2025-00198', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4353, 'CF-2025-00199', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4354, 'CF-2025-00199', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4355, 'CF-2025-00199', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4356, 'CF-2025-00199', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4357, 'CF-2025-00200', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4358, 'CF-2025-00200', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4359, 'CF-2025-00200', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4360, 'CF-2025-00200', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4361, 'CF-2025-00201', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4362, 'CF-2025-00201', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4363, 'CF-2025-00201', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4364, 'CF-2025-00201', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4365, 'CF-2025-00202', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4366, 'CF-2025-00202', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4367, 'CF-2025-00202', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4368, 'CF-2025-00202', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4369, 'CF-2025-00203', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4370, 'CF-2025-00203', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4371, 'CF-2025-00203', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4372, 'CF-2025-00203', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4373, 'CF-2025-00204', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4374, 'CF-2025-00204', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4375, 'CF-2025-00204', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4376, 'CF-2025-00204', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4377, 'CF-2025-00205', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4378, 'CF-2025-00205', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4379, 'CF-2025-00205', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4380, 'CF-2025-00205', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4381, 'CF-2025-00206', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4382, 'CF-2025-00206', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4383, 'CF-2025-00206', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4384, 'CF-2025-00206', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4385, 'CF-2025-00207', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4386, 'CF-2025-00207', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4387, 'CF-2025-00207', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4388, 'CF-2025-00207', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4389, 'CF-2025-00208', 2, NULL, 'Approved', NULL, NULL, 'bobo ka', NULL, '2025-10-28 09:19:09', '2025-11-01 12:18:21'),
(4390, 'CF-2025-00208', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4391, 'CF-2025-00208', 9, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-11-01 12:18:43'),
(4392, 'CF-2025-00208', 8, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-11-01 12:18:44'),
(4393, 'CF-2025-00209', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4394, 'CF-2025-00209', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4395, 'CF-2025-00209', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4396, 'CF-2025-00209', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-10-28 09:19:09', '2025-10-28 09:19:09'),
(4397, 'CF-2025-00210', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-05 06:52:48', '2025-11-05 06:52:48'),
(4398, 'CF-2025-00210', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-05 06:52:48', '2025-11-05 06:52:48'),
(4399, 'CF-2025-00210', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-05 06:52:48', '2025-11-05 06:52:48'),
(4400, 'CF-2025-00210', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-05 06:52:48', '2025-11-05 06:52:48'),
(4401, 'CF-2025-00211', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4402, 'CF-2025-00211', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4403, 'CF-2025-00211', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4404, 'CF-2025-00211', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4405, 'CF-2025-00212', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4406, 'CF-2025-00212', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4407, 'CF-2025-00212', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4408, 'CF-2025-00212', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4409, 'CF-2025-00213', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4410, 'CF-2025-00213', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4411, 'CF-2025-00213', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4412, 'CF-2025-00213', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4413, 'CF-2025-00214', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4414, 'CF-2025-00214', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4415, 'CF-2025-00214', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4416, 'CF-2025-00214', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4417, 'CF-2025-00215', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4418, 'CF-2025-00215', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4419, 'CF-2025-00215', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4420, 'CF-2025-00215', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4421, 'CF-2025-00216', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4422, 'CF-2025-00216', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37');
INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(4423, 'CF-2025-00216', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4424, 'CF-2025-00216', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4425, 'CF-2025-00217', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4426, 'CF-2025-00217', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4427, 'CF-2025-00217', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4428, 'CF-2025-00217', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4429, 'CF-2025-00218', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4430, 'CF-2025-00218', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4431, 'CF-2025-00218', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4432, 'CF-2025-00218', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4433, 'CF-2025-00219', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4434, 'CF-2025-00219', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4435, 'CF-2025-00219', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4436, 'CF-2025-00219', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4437, 'CF-2025-00220', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4438, 'CF-2025-00220', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4439, 'CF-2025-00220', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4440, 'CF-2025-00220', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4441, 'CF-2025-00221', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4442, 'CF-2025-00221', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4443, 'CF-2025-00221', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4444, 'CF-2025-00221', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4445, 'CF-2025-00222', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4446, 'CF-2025-00222', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4447, 'CF-2025-00222', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4448, 'CF-2025-00222', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4449, 'CF-2025-00223', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4450, 'CF-2025-00223', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4451, 'CF-2025-00223', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4452, 'CF-2025-00223', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4453, 'CF-2025-00224', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4454, 'CF-2025-00224', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4455, 'CF-2025-00224', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4456, 'CF-2025-00224', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4457, 'CF-2025-00225', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4458, 'CF-2025-00225', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4459, 'CF-2025-00225', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4460, 'CF-2025-00225', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4461, 'CF-2025-00226', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4462, 'CF-2025-00226', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4463, 'CF-2025-00226', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4464, 'CF-2025-00226', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4465, 'CF-2025-00227', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4466, 'CF-2025-00227', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4467, 'CF-2025-00227', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4468, 'CF-2025-00227', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4469, 'CF-2025-00228', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4470, 'CF-2025-00228', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4471, 'CF-2025-00228', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4472, 'CF-2025-00228', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4473, 'CF-2025-00229', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4474, 'CF-2025-00229', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4475, 'CF-2025-00229', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4476, 'CF-2025-00229', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4477, 'CF-2025-00230', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4478, 'CF-2025-00230', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4479, 'CF-2025-00230', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4480, 'CF-2025-00230', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4481, 'CF-2025-00231', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4482, 'CF-2025-00231', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4483, 'CF-2025-00231', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4484, 'CF-2025-00231', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4485, 'CF-2025-00232', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4486, 'CF-2025-00232', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4487, 'CF-2025-00232', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4488, 'CF-2025-00232', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4489, 'CF-2025-00233', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4490, 'CF-2025-00233', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4491, 'CF-2025-00233', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4492, 'CF-2025-00233', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4493, 'CF-2025-00234', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4494, 'CF-2025-00234', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4495, 'CF-2025-00234', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4496, 'CF-2025-00234', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4497, 'CF-2025-00235', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4498, 'CF-2025-00235', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4499, 'CF-2025-00235', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4500, 'CF-2025-00235', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4501, 'CF-2025-00236', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4502, 'CF-2025-00236', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4503, 'CF-2025-00236', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4504, 'CF-2025-00236', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4505, 'CF-2025-00237', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4506, 'CF-2025-00237', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4507, 'CF-2025-00237', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4508, 'CF-2025-00237', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4509, 'CF-2025-00238', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4510, 'CF-2025-00238', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4511, 'CF-2025-00238', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4512, 'CF-2025-00238', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4513, 'CF-2025-00239', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4514, 'CF-2025-00239', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4515, 'CF-2025-00239', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4516, 'CF-2025-00239', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4517, 'CF-2025-00240', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4518, 'CF-2025-00240', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4519, 'CF-2025-00240', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4520, 'CF-2025-00240', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4521, 'CF-2025-00241', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4522, 'CF-2025-00241', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4523, 'CF-2025-00241', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37'),
(4524, 'CF-2025-00241', 8, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-07 22:18:37', '2025-11-07 22:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `clearance_signatories_new`
--

CREATE TABLE `clearance_signatories_new` (
  `signatory_id` int(11) NOT NULL,
  `staff_id` varchar(8) NOT NULL COMMENT 'Employee number from staff table',
  `clearance_period_id` int(11) NOT NULL COMMENT 'FK to clearance_periods',
  `designation_id` int(11) NOT NULL COMMENT 'FK to designations',
  `department_id` int(11) DEFAULT NULL COMMENT 'For department-specific signatories',
  `is_required_first` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign first',
  `is_required_last` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign last',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'FALSE to temporarily disable assignment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sector-based signatory assignments for clearance periods';

-- --------------------------------------------------------

--
-- Table structure for table `clearance_signatory_actions`
--

CREATE TABLE `clearance_signatory_actions` (
  `action_id` int(11) NOT NULL,
  `clearance_form_id` varchar(20) NOT NULL COMMENT 'FK to clearance_forms',
  `signatory_id` int(11) NOT NULL COMMENT 'FK to clearance_signatories_new',
  `action` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `rejection_reason_id` int(11) DEFAULT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` text DEFAULT NULL COMMENT 'Additional details for rejection',
  `date_signed` timestamp NULL DEFAULT NULL COMMENT 'When action was taken',
  `grace_period_ends` timestamp NULL DEFAULT NULL COMMENT 'End of 5-minute grace period',
  `is_undone` tinyint(1) DEFAULT 0 COMMENT 'TRUE if action was undone during grace period',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual signatory actions with grace period support';

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
(50, 'General Education', 'GE', 'Faculty', 1, '2025-09-01 16:17:12', '2025-09-01 16:26:20', 3),
(51, 'Technical-Vocational-Livelihood Track', NULL, NULL, 1, '2025-09-17 17:41:12', '2025-09-17 17:41:12', 2);

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
  `sector` enum('College','Senior High School','Faculty') NOT NULL DEFAULT 'Faculty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`employee_number`, `user_id`, `employment_status`, `department_id`, `sector`, `created_at`, `updated_at`) VALUES
('LCA2001P', 179, 'Full Time', 44, 'Faculty', '2025-10-09 17:39:33', '2025-10-09 18:41:18'),
('LCA2002P', 180, 'Full Time', 46, 'Faculty', '2025-10-09 18:34:49', '2025-10-09 18:45:06'),
('LCA2003P', 181, 'Full Time', 45, 'Faculty', '2025-10-09 18:51:34', '2025-10-09 18:51:34'),
('LCA2004P', 214, 'Part Time - Full Load', 50, 'Faculty', '2025-10-09 17:04:13', '2025-10-24 04:58:59'),
('LCA2031P', 255, 'Part Time', 50, 'Faculty', '2025-10-27 09:52:48', '2025-10-27 09:52:48'),
('LCA2326P', 253, 'Part Time - Full Load', 50, 'Faculty', '2025-10-27 08:16:54', '2025-10-27 09:42:05'),
('LCA2631P', 254, 'Full Time', 50, 'Faculty', '2025-10-27 09:47:23', '2025-10-27 09:47:23'),
('LCA5001P', 199, 'Full Time', 50, 'Faculty', '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
('LCA5002P', 200, 'Full Time', 50, 'Faculty', '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
('LCA5003P', 201, 'Full Time', 50, 'Faculty', '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
('LCA5004P', 202, 'Full Time', 50, 'Faculty', '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
('LCA5005P', 203, 'Full Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5006P', 204, 'Part Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5007P', 205, 'Part Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5008P', 206, 'Part Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5009P', 207, 'Part Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5010P', 208, 'Part Time', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5011P', 209, 'Part Time - Full Load', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5012P', 210, 'Part Time - Full Load', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5013P', 211, 'Part Time - Full Load', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5014P', 212, 'Part Time - Full Load', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5015P', 213, 'Part Time - Full Load', 50, 'Faculty', '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
('LCA5020P', 215, 'Part Time', 50, 'Faculty', '2025-10-11 16:49:28', '2025-10-23 21:09:11'),
('LCA5030P', 238, 'Full Time', 50, 'Faculty', '2025-10-23 21:06:41', '2025-10-23 21:09:11'),
('LCA5031P', 239, 'Part Time - Full Load', 50, 'Faculty', '2025-10-23 21:31:16', '2025-10-23 22:13:37');

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

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `program_code`, `description`, `department_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'BS in Information Technology', 'BSIT', 'Bachelor of Science in Information Technology', 44, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(2, 'BS in Computer Science', 'BSCS', 'Bachelor of Science in Computer Science', 44, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(3, 'BS in Computer Engineering', 'BSCE', 'Bachelor of Science in Computer Engineering', 44, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(4, 'BS in Hospitality Management', 'BSHM', 'Bachelor of Science in Hospitality Management', 46, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(5, 'BS in Culinary Management', 'BSCM', 'Bachelor of Science in Culinary Management', 46, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(6, 'BS in Tourism Management', 'BSTM', 'Bachelor of Science in Tourism Management', 46, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(7, 'Bachelor of Multimedia Arts', 'BMMA', 'Bachelor of Multimedia Arts', 45, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(8, 'BA in Communication', 'BACOMM', 'Bachelor of Arts in Communication', 45, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(9, 'BS in Business Administration', 'BSBA', 'Bachelor of Science in Business Administration', 45, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(10, 'BS in Accountancy', 'BSA', 'Bachelor of Science in Accountancy', 45, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(11, 'BS in Accounting Information System', 'BSAIS', 'Bachelor of Science in Accounting Information System', 45, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(12, 'Accountancy, Business, Management', 'ABM', 'Academic Track - Accountancy, Business, Management', 47, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(13, 'Science, Technology, Engineering, and Mathematics', 'STEM', 'Academic Track - Science, Technology, Engineering, and Mathematics', 47, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(14, 'Humanities and Social Sciences', 'HUMSS', 'Academic Track - Humanities and Social Sciences', 47, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(15, 'General Academic', 'GA', 'Academic Track - General Academic', 47, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(16, 'Digital Arts', 'DIGITAL_AR', 'Technical-Vocational-Livelihood Track - Digital Arts', 48, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(17, 'IT in Mobile App and Web Development', 'IT_MAWD', 'Technical-Vocational-Livelihood Track - IT in Mobile App and Web Development', 48, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(18, 'Tourism Operations', 'TOURISM_OP', 'Technical-Vocational-Livelihood Track - Tourism Operations', 48, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(19, 'Restaurant and Cafe Operations', 'REST_CAFE', 'Technical-Vocational-Livelihood Track - Restaurant and Cafe Operations', 48, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(20, 'Culinary Arts', 'CULINARY', 'Technical-Vocational-Livelihood Track - Culinary Arts', 48, 1, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(21, 'Digital Arts', 'Digital Ar', NULL, 51, 1, '2025-09-17 17:41:12', '2025-09-17 17:41:12'),
(22, 'IT in Mobile App and Web Development', 'IT in Mobi', NULL, 51, 1, '2025-09-17 17:41:12', '2025-09-17 17:41:12'),
(23, 'Tourism Operations', 'Tourism Op', NULL, 51, 1, '2025-09-17 17:41:12', '2025-09-17 17:41:12'),
(24, 'Restaurant and Cafe Operations', 'Restaurant', NULL, 51, 1, '2025-09-17 17:41:12', '2025-09-17 17:41:12'),
(25, 'Culinary Arts', 'Culinary A', NULL, 51, 1, '2025-09-17 17:41:13', '2025-09-17 17:41:13');

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
(1, 'College', 1, 1, 2, 1, 1, '2025-09-14 14:59:46', '2025-10-28 09:19:04'),
(2, 'Senior High School', 0, 1, 2, 1, 1, '2025-09-14 14:59:46', '2025-09-18 00:18:10'),
(3, 'Faculty', 1, 1, 12, 1, 1, '2025-09-14 14:59:46', '2025-10-27 08:23:08');

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
(34, 'College', 179, 8, 1, NULL, 0, 0, 0, '2025-09-18 00:15:09', '2025-10-28 09:17:36'),
(35, 'Senior High School', 180, 8, 1, NULL, 0, 0, 1, '2025-09-18 00:15:09', '2025-09-18 00:15:09'),
(36, 'Faculty', 181, 8, 1, NULL, 0, 0, 0, '2025-09-18 00:15:09', '2025-10-23 22:11:04'),
(37, 'College', 189, 1, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:36'),
(38, 'College', 183, 14, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(39, 'College', 186, 3, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(40, 'College', 185, 16, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(41, 'College', 184, 15, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(42, 'College', 192, 4, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(43, 'College', 190, 2, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(44, 'Senior High School', 189, 1, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(45, 'Senior High School', 183, 14, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(46, 'Senior High School', 186, 3, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(47, 'Senior High School', 185, 16, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(48, 'Senior High School', 184, 15, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(49, 'Senior High School', 192, 4, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(50, 'Senior High School', 190, 2, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(51, 'Faculty', 189, 1, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:01'),
(52, 'Faculty', 183, 14, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:22'),
(53, 'Faculty', 186, 3, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:19'),
(54, 'Faculty', 185, 16, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:28'),
(55, 'Faculty', 184, 15, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:26'),
(56, 'Faculty', 192, 4, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:16'),
(57, 'Faculty', 190, 2, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:29'),
(58, 'Faculty', 196, 13, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:37'),
(59, 'Faculty', 194, 5, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:31'),
(60, 'Faculty', 188, 7, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:10:41'),
(61, 'Faculty', 187, 17, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:34'),
(62, 'Faculty', 193, 11, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:13'),
(63, 'Faculty', 197, 6, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:20'),
(64, 'Faculty', 191, 10, 0, NULL, 0, 0, 0, '2025-09-18 00:17:43', '2025-10-23 22:11:15'),
(65, 'Faculty', 195, 12, 0, NULL, 0, 0, 1, '2025-09-18 00:17:43', '2025-10-27 08:22:51'),
(66, 'Faculty', 182, 9, 0, NULL, 0, 0, 1, '2025-09-18 00:17:43', '2025-10-27 08:22:51'),
(67, 'Faculty', 240, 10, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:18'),
(68, 'Faculty', 233, 15, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:06'),
(69, 'Faculty', 243, 7, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:31'),
(70, 'Faculty', 246, 9, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:24'),
(71, 'Faculty', 235, 3, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:10'),
(72, 'Faculty', 234, 4, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:17'),
(73, 'Faculty', 242, 11, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:19'),
(74, 'Faculty', 241, 5, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:03'),
(75, 'Faculty', 232, 1, 0, NULL, 0, 0, 1, '2025-10-23 22:10:34', '2025-10-27 08:22:51'),
(76, 'Faculty', 244, 13, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:01'),
(77, 'Faculty', 237, 14, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:08'),
(78, 'Faculty', 247, 6, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:19:09'),
(79, 'Faculty', 245, 12, 0, NULL, 0, 0, 0, '2025-10-23 22:10:34', '2025-10-27 08:22:01'),
(80, 'College', 231, 2, 0, NULL, 0, 0, 1, '2025-10-28 09:18:58', '2025-10-28 09:18:58'),
(81, 'College', 232, 1, 0, NULL, 0, 0, 1, '2025-10-28 09:18:58', '2025-10-28 09:18:58'),
(82, 'College', 246, 9, 0, NULL, 0, 0, 1, '2025-10-28 09:18:58', '2025-10-28 09:18:58');

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
(98, '1st', 47, 0, 0, '2025-09-17 23:44:51', '2025-10-18 13:47:39'),
(99, '2nd', 47, 0, 0, '2025-09-17 23:44:51', '2025-10-27 07:39:59'),
(100, '1st', 48, 0, 0, '2025-10-27 07:40:14', '2025-10-28 09:16:58'),
(101, '2nd', 48, 0, 0, '2025-10-27 07:40:14', '2025-11-05 10:03:27'),
(104, '1st', 50, 0, 0, '2025-11-07 22:05:15', '2025-11-10 17:19:33'),
(105, '2nd', 50, 0, 0, '2025-11-07 22:05:15', '2025-11-07 22:32:38');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`employee_number`, `user_id`, `designation_id`, `staff_category`, `department_id`, `employment_status`, `is_active`, `created_at`, `updated_at`) VALUES
('LCA2001P', 179, 8, 'Program Head', 44, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:41:18'),
('LCA2002P', 180, 8, 'Program Head', 46, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:45:06'),
('LCA2003P', 181, 8, 'Program Head', 45, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:51:34'),
('LCA2004P', 214, 8, 'Program Head', 50, 'Part Time - Full Load', 1, '2025-10-09 17:04:13', '2025-10-24 04:58:59'),
('LCA3001P', 182, 9, 'School Administrator', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4001P', 183, 14, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4002P', 184, 15, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4003P', 185, 16, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4004P', 186, 3, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4005P', 187, 17, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4006P', 188, 7, 'Regular Staff', 50, 'Full Time', 1, '2025-09-18 00:00:07', '2025-09-18 00:00:07'),
('LCA4007P', 189, 1, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4008P', 190, 2, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4009P', 191, 10, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
('LCA4010P', 192, 4, 'Regular Staff', 44, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4011P', 193, 11, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4012P', 194, 5, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4013P', 195, 12, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4014P', 196, 13, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4015P', 197, 6, 'Regular Staff', 50, 'Full Time', 1, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
('LCA4020P', 231, 2, 'Regular Staff', NULL, NULL, 1, '2025-10-21 16:35:46', '2025-10-21 16:35:46'),
('LCA4021P', 232, 1, 'Regular Staff', NULL, NULL, 1, '2025-10-23 09:05:40', '2025-10-23 09:05:40'),
('LCA4022P', 233, 15, 'Regular Staff', NULL, NULL, 1, '2025-10-23 09:06:57', '2025-10-23 09:06:57'),
('LCA4023P', 234, 4, 'Regular Staff', NULL, NULL, 1, '2025-10-23 09:07:49', '2025-10-23 09:07:49'),
('LCA4024P', 235, 3, 'Regular Staff', NULL, NULL, 1, '2025-10-23 09:08:57', '2025-10-23 09:08:57'),
('LCA4025P', 236, 16, 'Regular Staff', NULL, NULL, 1, '2025-10-23 09:10:07', '2025-10-23 09:10:07'),
('LCA4026P', 237, 14, 'Regular Staff', NULL, NULL, 1, '2025-10-23 20:38:37', '2025-10-23 20:38:37'),
('LCA4027P', 240, 10, 'Regular Staff', NULL, NULL, 1, '2025-10-23 21:36:17', '2025-10-23 21:36:17'),
('LCA4028P', 241, 5, 'Regular Staff', NULL, NULL, 1, '2025-10-23 21:47:35', '2025-10-23 21:50:38'),
('LCA4029P', 242, 11, 'Regular Staff', NULL, NULL, 1, '2025-10-23 21:58:55', '2025-10-23 22:00:42'),
('LCA4030P', 243, 7, 'Regular Staff', NULL, NULL, 1, '2025-10-23 22:03:11', '2025-10-23 22:03:11'),
('LCA4031P', 244, 13, 'Regular Staff', NULL, NULL, 1, '2025-10-23 22:04:30', '2025-10-23 22:04:30'),
('LCA4032P', 245, 12, 'Regular Staff', NULL, NULL, 1, '2025-10-23 22:05:52', '2025-10-23 22:05:52'),
('LCA4033P', 246, 9, 'School Administrator', NULL, NULL, 1, '2025-10-23 22:07:15', '2025-10-23 22:07:15'),
('LCA4034P', 247, 6, 'Regular Staff', NULL, NULL, 1, '2025-10-23 22:08:05', '2025-10-23 22:08:05');

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
  `sector` enum('College','Senior High School') DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL COMMENT 'e.g., "4/1-1", "3/1-2"',
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `retain_year_level_next_year` tinyint(1) DEFAULT 0,
  `retain_year_level_for_next_year` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Students with sector assignment (College, Senior High School)';

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `program_id`, `department_id`, `sector`, `section`, `year_level`, `created_at`, `updated_at`, `retain_year_level_next_year`, `retain_year_level_for_next_year`) VALUES
('02000100001', 90, 1, 44, 'College', '4/1-1', '4th Year', '2025-09-16 20:53:58', '2025-11-07 21:50:18', 0, 0),
('02000100002', 91, 1, 44, 'College', '4/1-1', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100003', 92, 1, 44, 'College', '3/1-1', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100004', 93, 1, 44, 'College', '3/1-1', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100005', 94, 1, 44, 'College', '2/1-1', '3rd Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100006', 95, 2, 44, 'College', '4/1-2', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100007', 96, 2, 44, 'College', '3/1-2', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100008', 97, 2, 44, 'College', '2/1-2', '3rd Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100009', 98, 3, 44, 'College', '4/1-3', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100010', 99, 3, 44, 'College', '3/1-3', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100011', 100, 4, 46, 'College', '4/2-1', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100012', 101, 4, 46, 'College', '3/2-1', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100013', 102, 4, 46, 'College', '2/2-1', '3rd Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100014', 103, 5, 46, 'College', '4/2-2', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100015', 104, 5, 46, 'College', '3/2-2', '4th Year', '2025-09-16 20:53:59', '2025-11-07 22:05:15', 0, 0),
('02000100016', 105, 6, 46, 'College', '4/2-3', '4th Year', '2025-09-16 20:53:59', '2025-11-07 21:50:18', 0, 0),
('02000100017', 106, 6, 46, 'College', '3/2-3', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100018', 107, 7, 45, 'College', '4/3-1', '4th Year', '2025-09-16 20:54:00', '2025-11-07 21:50:18', 0, 0),
('02000100019', 108, 7, 45, 'College', '3/3-1', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100020', 109, 8, 45, 'College', '4/3-2', '4th Year', '2025-09-16 20:54:00', '2025-11-07 21:50:18', 0, 0),
('02000100021', 110, 8, 45, 'College', '3/3-2', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100022', 111, 9, 45, 'College', '4/3-3', '4th Year', '2025-09-16 20:54:00', '2025-11-07 21:50:18', 0, 0),
('02000100023', 112, 9, 45, 'College', '3/3-3', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100024', 113, 9, 45, 'College', '2/3-3', '2nd Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100025', 114, 10, 45, 'College', '4/3-4', '4th Year', '2025-09-16 20:54:00', '2025-11-07 21:50:18', 0, 0),
('02000100026', 115, 10, 45, 'College', '3/3-4', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000100027', 116, 11, 45, 'College', '4/3-5', '4th Year', '2025-09-16 20:54:00', '2025-11-07 21:50:18', 0, 0),
('02000100028', 117, 11, 45, 'College', '3/3-5', '4th Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000183124', 230, 2, 44, 'College', '4/1-1', '4th Year', '2025-10-11 18:36:31', '2025-11-07 21:50:18', 0, 0),
('02000200001', 118, 12, 47, 'Senior High School', '12-ABM-1', '3rd Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000200002', 119, 12, 47, 'Senior High School', '12-ABM-1', '3rd Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000200003', 120, 12, 47, 'Senior High School', '12-ABM-2', '3rd Year', '2025-09-16 20:54:00', '2025-11-07 22:05:15', 0, 0),
('02000200004', 121, 12, 47, 'Senior High School', '12-ABM-2', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200005', 122, 12, 47, 'Senior High School', '11-ABM-1', '2nd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200006', 123, 13, 47, 'Senior High School', '12-STEM-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200007', 124, 13, 47, 'Senior High School', '12-STEM-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200008', 125, 13, 47, 'Senior High School', '12-STEM-2', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200009', 126, 13, 47, 'Senior High School', '11-STEM-1', '2nd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200010', 127, 13, 47, 'Senior High School', '11-STEM-1', '2nd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200011', 128, 14, 47, 'Senior High School', '12-HUMSS-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200012', 129, 14, 47, 'Senior High School', '12-HUMSS-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200013', 130, 14, 47, 'Senior High School', '12-HUMSS-2', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200014', 131, 14, 47, 'Senior High School', '11-HUMSS-1', '2nd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200015', 132, 15, 47, 'Senior High School', '12-GA-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200016', 133, 15, 47, 'Senior High School', '12-GA-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200017', 134, 15, 47, 'Senior High School', '11-GA-1', '2nd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200018', 144, 16, 48, 'Senior High School', '12-DA-1', '3rd Year', '2025-09-16 20:57:54', '2025-11-07 22:05:15', 0, 0),
('02000200019', 145, 16, 48, 'Senior High School', '12-DA-1', '3rd Year', '2025-09-16 20:57:54', '2025-11-07 22:05:15', 0, 0),
('02000200020', 146, 16, 48, 'Senior High School', '11-DA-1', '2nd Year', '2025-09-16 20:57:54', '2025-11-07 22:05:15', 0, 0),
('02000200021', 135, 17, 48, 'Senior High School', '12-IT-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200022', 136, 17, 48, 'Senior High School', '12-IT-1', '3rd Year', '2025-09-16 20:54:01', '2025-11-07 22:05:15', 0, 0),
('02000200023', 137, 17, 48, 'Senior High School', '11-IT-1', '2nd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200024', 147, 18, 48, 'Senior High School', '12-TO-1', '3rd Year', '2025-09-16 20:57:55', '2025-11-07 22:05:15', 0, 0),
('02000200025', 148, 18, 48, 'Senior High School', '12-TO-1', '3rd Year', '2025-09-16 20:57:55', '2025-11-07 22:05:15', 0, 0),
('02000200026', 149, 18, 48, 'Senior High School', '11-TO-1', '2nd Year', '2025-09-16 20:57:55', '2025-11-07 22:05:15', 0, 0),
('02000200027', 138, 19, 48, 'Senior High School', '12-RC-1', '3rd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200028', 139, 19, 48, 'Senior High School', '12-RC-1', '3rd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200029', 140, 19, 48, 'Senior High School', '11-RC-1', '2nd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200030', 141, 20, 48, 'Senior High School', '12-CA-1', '3rd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200031', 142, 20, 48, 'Senior High School', '12-CA-1', '3rd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000200032', 143, 20, 48, 'Senior High School', '11-CA-1', '2nd Year', '2025-09-16 20:54:02', '2025-11-07 22:05:15', 0, 0),
('02000290002', 256, 2, 44, 'College', '1', '3rd Year', '2025-11-05 06:48:39', '2025-11-07 22:05:15', 0, 0),
('02000290008', 257, 8, 45, 'College', '1', '3rd Year', '2025-11-05 07:56:00', '2025-11-07 22:05:15', 0, 0),
('02000837212', 227, 12, 47, NULL, '1/1-2', '3rd Year', '2025-10-11 18:15:04', '2025-11-07 22:05:15', 0, 0);

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
  `account_status` enum('active','inactive','graduated','resigned') DEFAULT 'active',
  `can_apply` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `middle_name`, `contact_number`, `account_status`, `can_apply`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$cS1Lk6GOXeKSOmqguvw6lO/kuHy844NX1Kgt8rInKgCn5dgWTdN9K', 'admin@system.local', 'System', 'Administrator', NULL, NULL, 'active', 1, NULL, '2025-09-11 01:29:50', '2025-09-11 01:29:50'),
(90, '02000100001', '$2y$10$0JvXWFOLhK2VVCJ.bpdPuugWHlehGmS437zR4xkPj7v0/4Z/pHNUa', 'john.doe@student.lca.edu.ph', 'John', 'Doe', 'Smith', NULL, 'active', 1, NULL, '2025-09-16 20:53:58', '2025-09-16 20:53:58'),
(91, '02000100002', '$2y$10$QLdybFn6XuCYYFvdQL5YJeGQmCEr6zh7frxz3b3yz9PqgTgwf2tFe', 'jane.smith@student.lca.edu.ph', 'Jane', 'Smith', 'Johnson', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(92, '02000100003', '$2y$10$bi3xloG513KyOhMwB/UF/ObOpqaCXNCM95WDKtWhcuUN76LHIGhKG', 'michael.johnson@student.lca.edu.ph', 'Michael', 'Johnson', 'Brown', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(93, '02000100004', '$2y$10$3nol8uBAnAGu5uV9Xv2qyOxCkrY8W36omz..YzNHsG5e1bo0COnLm', 'sarah.brown@student.lca.edu.ph', 'Sarah', 'Brown', 'Davis', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(94, '02000100005', '$2y$10$RC7O9Gyc9KpEKYtH7pY8g.NK/WUqxzHAY3c49iW6IDXk1J2R/WGYC', 'david.davis@student.lca.edu.ph', 'David', 'Davis', 'Wilson', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(95, '02000100006', '$2y$10$wmAjbUCf1QnRJ4rPhF3Fg.ESvv7O3i/f9Uaez8mh2iQsVcHQCJ8wO', 'emily.wilson@student.lca.edu.ph', 'Emily', 'Wilson', 'Moore', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(96, '02000100007', '$2y$10$rMH.VLiBCrWQ8Aex5hxRL.vqoqHwzHkfeccQRn47vcniNFvvSXXTe', 'christopher.moore@student.lca.edu.ph', 'Christopher', 'Moore', 'Taylor', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(97, '02000100008', '$2y$10$Te6xq2PcBbNx6sOxSkWwqeufmH/ehYGHgs3xq34vUjbZWHU4CNL32', 'jessica.taylor@student.lca.edu.ph', 'Jessica', 'Taylor', 'Anderson', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(98, '02000100009', '$2y$10$yH0H5aT3UDzb4JwJ9lsCnuDufU9ssw2BiVzNtjQhhM1s9Slxi1hVS', 'matthew.anderson@student.lca.edu.ph', 'Matthew', 'Anderson', 'Thomas', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(99, '02000100010', '$2y$10$sct4vF9tcET0lQuzqomrHeXqsYHGdgkJ.tX54dib1mMnH/rebYySu', 'ashley.thomas@student.lca.edu.ph', 'Ashley', 'Thomas', 'Jackson', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(100, '02000100011', '$2y$10$xsafv0G6sRUDytKGzBn5nuDgh7tp0NYYmuk.SdM03uRg3NT/QO15y', 'daniel.jackson@student.lca.edu.ph', 'Daniel', 'Jackson', 'White', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(101, '02000100012', '$2y$10$8PnLQN4iZrunmTG2twB8/e3K8N70zfKNX1jqkqNX.rYNaubBUceUi', 'amanda.white@student.lca.edu.ph', 'Amanda', 'White', 'Harris', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(102, '02000100013', '$2y$10$7h26c0Yxia/62v38bJ4sZ.IaCiz8yhG3kaT/qQk7gORMvnBZVuDje', 'james.harris@student.lca.edu.ph', 'James', 'Harris', 'Martin', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(103, '02000100014', '$2y$10$VDH1uRht30hSHe6JbMCtHOX993nHNvVVr5KG8w0SIimqHMupDNHgK', 'jennifer.martin@student.lca.edu.ph', 'Jennifer', 'Martin', 'Thompson', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(104, '02000100015', '$2y$10$SGDLCKxcYmBQj8Y1GFu76uKL0m6HX4o6CX5BmTElmU0RhM4aFDYIC', 'robert.thompson@student.lca.edu.ph', 'Robert', 'Thompson', 'Garcia', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(105, '02000100016', '$2y$10$QFEetPmnMOiiUyY714MP2OWiy9BJksUV6q4eWWjKD6mFw.xS1NuiG', 'lisa.garcia@student.lca.edu.ph', 'Lisa', 'Garcia', 'Martinez', NULL, 'active', 1, NULL, '2025-09-16 20:53:59', '2025-09-16 20:53:59'),
(106, '02000100017', '$2y$10$ctcerDpbnxGMSGKjE3F2ZOGe74km2.fH55DOnrH3bjl7hIrTxn.Yq', 'william.martinez@student.lca.edu.ph', 'William', 'Martinez', 'Robinson', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(107, '02000100018', '$2y$10$41a1GMw5Ugp1Inp5ebzmweCVQ5f2jq4JpPUz.VRpiqm3MXFbKvfPu', 'michelle.robinson@student.lca.edu.ph', 'Michelle', 'Robinson', 'Clark', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(108, '02000100019', '$2y$10$HpRCz1cM0sOwrMQntcKVAeRuSTfaLUocJANhJN0bewEgesWjRjHl2', 'charles.clark@student.lca.edu.ph', 'Charles', 'Clark', 'Rodriguez', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(109, '02000100020', '$2y$10$XdeQq2ODXLvqWOg4UdCvCekY.VO9IZWHVnuvWdVZxb7/IWR0Z9YsK', 'patricia.rodriguez@student.lca.edu.ph', 'Patricia', 'Rodriguez', 'Lewis', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(110, '02000100021', '$2y$10$cX2XeF2HkmpdkHftgOF9/O4qNUJM74xSD9YxiMHu422MdHcYF2nQq', 'thomas.lewis@student.lca.edu.ph', 'Thomas', 'Lewis', 'Lee', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(111, '02000100022', '$2y$10$s/1BVftNfydRJAyN9IQ4H.T4HrfoYvSxGgwEGj7.aWS4LFVpOKdJ.', 'barbara.lee@student.lca.edu.ph', 'Barbara', 'Lee', 'Walker', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(112, '02000100023', '$2y$10$WqkUDGy2oTw9NMgnzQcjq.cfS6klvWMfenLjq1F4aOmnPIunBVq3m', 'richard.walker@student.lca.edu.ph', 'Richard', 'Walker', 'Hall', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(113, '02000100024', '$2y$10$KPmRbMHnE3HziFURoI4iM.WfF/Xbw/0o6wyc16SgqQF50X9i9eDfq', 'susan.hall@student.lca.edu.ph', 'Susan', 'Hall', 'Allen', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(114, '02000100025', '$2y$10$EYqlaykX8e/4Qa4oM2amH.WXi7z96xDzfIEkBdIQiZoIWx5rd7bFC', 'joseph.allen@student.lca.edu.ph', 'Joseph', 'Allen', 'Young', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(115, '02000100026', '$2y$10$S0lXY7ij8fI9ilu2x8RSxOvAftUTnXFK50N115SaaZIBDoIJQpd8y', 'elizabeth.young@student.lca.edu.ph', 'Elizabeth', 'Young', 'Hernandez', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(116, '02000100027', '$2y$10$WHzWepaKtMdixnpOikbkxel9sTggGzgf5JPRb9JALQtUeAwtoLGQa', 'christopher.hernandez@student.lca.edu.ph', 'Christopher', 'Hernandez', 'King', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(117, '02000100028', '$2y$10$IYBNuVp0fBW0a/5mxNu.hulODoAhqMqkqemqI9Tiy0zCVHh3ao1Fu', 'maria.king@student.lca.edu.ph', 'Maria', 'King', 'Wright', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(118, '02000200001', '$2y$10$WSnskeahY51B31YKia1T1.D.QihnLHN8bhJlAhiFDRDEVUeSznkdC', 'alex.garcia@student.lca.edu.ph', 'Alex', 'Garcia', 'Santos', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(119, '02000200002', '$2y$10$zt87hV.4x8G6TF59J61UmOW9NzkaDtpe4Wc7mOqUqJ3x5VPGZtfIK', 'bianca.santos@student.lca.edu.ph', 'Bianca', 'Santos', 'Cruz', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(120, '02000200003', '$2y$10$v15VmKpJ71FbuuWjXBL9UuqsqmpToyhy8Trw0cuaRIH5G7ZBRFC4u', 'carlos.cruz@student.lca.edu.ph', 'Carlos', 'Cruz', 'Reyes', NULL, 'active', 1, NULL, '2025-09-16 20:54:00', '2025-09-16 20:54:00'),
(121, '02000200004', '$2y$10$0yJOzmSJNBS/PLCCoCfmfehsJAHuiwrPq0dPyJJAbBk1mD.SzHFvG', 'diana.reyes@student.lca.edu.ph', 'Diana', 'Reyes', 'Mendoza', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(122, '02000200005', '$2y$10$HbZhskNIRRJMBX7htBIcx..PvoLGTn/Q4w8OngTrYi46UK74u/r8m', 'eduardo.mendoza@student.lca.edu.ph', 'Eduardo', 'Mendoza', 'Torres', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(123, '02000200006', '$2y$10$UZe8eBGSAgPk5i9sps6Mj.deZPuJKzi3bpyNP0tH3xGdX2TpUiUl.', 'fatima.torres@student.lca.edu.ph', 'Fatima', 'Torres', 'Gonzalez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(124, '02000200007', '$2y$10$0Wd6QtSmrv7eXf8YMn/K1.YCkcMiOWFarSdgoUt4dsi1E8Rpk.6W.', 'gabriel.gonzalez@student.lca.edu.ph', 'Gabriel', 'Gonzalez', 'Lopez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(125, '02000200008', '$2y$10$I/nBscEjKjUEsbCXAKFfkOIXPolkGrBHTvGKEaa6vjN7deT2WGExG', 'hannah.lopez@student.lca.edu.ph', 'Hannah', 'Lopez', 'Martinez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(126, '02000200009', '$2y$10$RY1Ai6ccmBZ7.P5.PzlfsO.BqYc8Lh2WjxuSID22x4SzNTljDWEs.', 'ivan.martinez@student.lca.edu.ph', 'Ivan', 'Martinez', 'Hernandez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(127, '02000200010', '$2y$10$y4brHxbwUxElNHQflqDuyeVLdL3Kj7bxDsUTTtrF8kYBlTchchz9W', 'julia.hernandez@student.lca.edu.ph', 'Julia', 'Hernandez', 'Gutierrez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(128, '02000200011', '$2y$10$2Oo3br2.z0L9IN2Oc6HnouxN/nvUNdeb3k0kFTdfT1AdfjXvMru26', 'kevin.gutierrez@student.lca.edu.ph', 'Kevin', 'Gutierrez', 'Morales', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(129, '02000200012', '$2y$10$I80LUTo/emx14AmCV2NQHOAB6czND0hHH7IZSRVgsWedkAVvWc3RS', 'luna.morales@student.lca.edu.ph', 'Luna', 'Morales', 'Jimenez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(130, '02000200013', '$2y$10$09zwLDO9K55BU9WA8sKdGeLMkJsmvJhgBtq9Rn./OeZYsgO1HZd1y', 'miguel.jimenez@student.lca.edu.ph', 'Miguel', 'Jimenez', 'Ruiz', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(131, '02000200014', '$2y$10$A3Vw2N.olVifSLuUUAm/e.x90xq/J2Y5A2Uc2IpLy6/wBABipHygO', 'nina.ruiz@student.lca.edu.ph', 'Nina', 'Ruiz', 'Diaz', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(132, '02000200015', '$2y$10$9ZM3v8JmETRaoCpQwdXy6erg6kbrx.EWl38X5bmcW7gK3tp9ZpLFW', 'oscar.diaz@student.lca.edu.ph', 'Oscar', 'Diaz', 'Moreno', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(133, '02000200016', '$2y$10$1.AStyETC9sNuVxDTVtlJ.XBFtTulghLvqt0RWBVNWvC1vcfnmwJW', 'paula.moreno@student.lca.edu.ph', 'Paula', 'Moreno', 'Alvarez', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(134, '02000200017', '$2y$10$18J3g.MFQVqzZIReox4FzuyNQtTsxuvJ5gw/qSftyw.tU75hKusJa', 'quentin.alvarez@student.lca.edu.ph', 'Quentin', 'Alvarez', 'Romero', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(135, '02000200021', '$2y$10$G.TT9DB3qyoM8MkryfsznuQ68XFtJ86hqh48y8MYU35KnAQ10WaXq', 'ulises.ramos@student.lca.edu.ph', 'Ulises', 'Ramos', 'Herrera', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(136, '02000200022', '$2y$10$5.jjpTAltVAIDc3BNanrRuUKeIV1BjV0nzVqxYqiKMmtqf3UD8kZG', 'valentina.herrera@student.lca.edu.ph', 'Valentina', 'Herrera', 'Medina', NULL, 'active', 1, NULL, '2025-09-16 20:54:01', '2025-09-16 20:54:01'),
(137, '02000200023', '$2y$10$QeTX90ITGMrFApgd2CVrJeamokH9W2muMyqySefI/d2fjyZOkYje2', 'walter.medina@student.lca.edu.ph', 'Walter', 'Medina', 'Castillo', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(138, '02000200027', '$2y$10$bnMmrdEpTmpVZRQ6MptmxO.f26uh/uO.jUl6qgTVQx3So0F5wH4T2', 'adriana.ortega@student.lca.edu.ph', 'Adriana', 'Ortega', 'Flores', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(139, '02000200028', '$2y$10$K7Cu2PpcCbJxXMGjc7u0UOqLDjWBb6nJqrEUnN5CfOs53Xq3nmkeG', 'bruno.flores@student.lca.edu.ph', 'Bruno', 'Flores', 'Silva', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(140, '02000200029', '$2y$10$U8keh0HV4XtFtsXNL3QATu6IcXtrrjdea6cnhU3lVzRVcnwn2SFYa', 'camila.silva@student.lca.edu.ph', 'Camila', 'Silva', 'Vega', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(141, '02000200030', '$2y$10$D3oAV2kIPLelxQD9TkENE.sDpzYmbDZKfTCzVkdegKU4PwUeeTJNy', 'diego.vega@student.lca.edu.ph', 'Diego', 'Vega', 'Guerrero', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-11-07 10:07:53'),
(142, '02000200031', '$2y$10$GgS2lMlv2P79uQWH2PaAX.GDq/yElixjGm03ZDKy8LRk9LScw0pLe', 'elena.guerrero@student.lca.edu.ph', 'Elena', 'Guerrero', 'Pena', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(143, '02000200032', '$2y$10$Xp9eN9ovAQA7urOAndsW.ulFi.j8J5dKEKfXAYjdsnzqMSjogXQq.', 'fernando.pena@student.lca.edu.ph', 'Fernando', 'Pena', 'Rios', NULL, 'active', 1, NULL, '2025-09-16 20:54:02', '2025-09-16 20:54:02'),
(144, '02000200018', '$2y$10$/B7ydmNuX0NO/C49kGaWiubFHoJQoLFAuW3Hw/7BO8v3KDPjlY6Q6', 'rosa.romero@student.lca.edu.ph', 'Rosa', 'Romero', 'Navarro', NULL, 'active', 1, NULL, '2025-09-16 20:57:54', '2025-09-16 20:57:54'),
(145, '02000200019', '$2y$10$0a0r0P9sgGTfF6YSPEKP0.jrrrPQLw2IQUapPpZiZufeYFnBxGCK2', 'sebastian.navarro@student.lca.edu.ph', 'Sebastian', 'Navarro', 'Molina', NULL, 'active', 1, NULL, '2025-09-16 20:57:54', '2025-09-16 20:57:54'),
(146, '02000200020', '$2y$10$RmUPFiFkW2FhY9Ps0JeMcu3UEQPRQ1rtKvc/mMvVW998QvPrdhnhy', 'teresa.molina@student.lca.edu.ph', 'Teresa', 'Molina', 'Ramos', NULL, 'active', 1, NULL, '2025-09-16 20:57:54', '2025-09-16 20:57:54'),
(147, '02000200024', '$2y$10$qu2puffDrqMVrm5FOQt/K.PLicdsqNlipZ8kTk0naOpGr3MKFaxpK', 'ximena.castillo@student.lca.edu.ph', 'Ximena', 'Castillo', 'Vargas', NULL, 'active', 1, NULL, '2025-09-16 20:57:55', '2025-09-16 20:57:55'),
(148, '02000200025', '$2y$10$Aa3kZtafpck1jzAHPmorvOR2P1apIsXhA3yVu1DHEhwZBThHLRUYS', 'yolanda.vargas@student.lca.edu.ph', 'Yolanda', 'Vargas', 'Castro', NULL, 'active', 1, NULL, '2025-09-16 20:57:55', '2025-11-07 10:08:00'),
(149, '02000200026', '$2y$10$LhKyv6PWCTX/7m.B0ghtcezj5.Uv5r2ylTyxe4li427qqbGVM5KS.', 'zachary.castro@student.lca.edu.ph', 'Zachary', 'Castro', 'Ortega', NULL, 'active', 1, NULL, '2025-09-16 20:57:55', '2025-09-16 20:57:55'),
(179, 'LCA2001P', '$2y$10$UjtEpndyi4/RDolTV2RuD.oepMh1Ga4YzDkv1WqU5Mj4Ksh8GHQ0O', 'maria.santos@lca.edu.ph', 'Dr.', 'Santos', 'Maria', '+63 9223334444', 'active', 1, NULL, '2025-09-17 23:58:48', '2025-10-23 11:53:07'),
(180, 'LCA2002P', '$2y$10$ZvRlS7AC1Gmi7SnvZtcQXuZqIkhkZYmVem5iBmvDuNMCj3O9DA2ZG', 'carlos.reyes@lca.edu.ph', 'Prof.', 'Reyes', 'Carlos', '+63 9326549876', 'active', 1, NULL, '2025-09-17 23:58:48', '2025-10-23 18:21:14'),
(181, 'LCA2003P', '$2y$10$8x9UA/ovOeR3nsKcczLVxuGa1FQAfyK6Sbq3XuevaqRsseBeZ9cBi', 'ana.cruz@lca.edu.ph', 'Dr.', 'Cruz', 'Ana', '+63 9009998888', 'active', 1, NULL, '2025-09-17 23:58:48', '2025-10-23 18:21:38'),
(182, 'LCA3001P', '$2y$10$d4.YUdPeUGz9TUKEkhWCTO/tPgwu7n6D4F0JOXukh6NNUpMz12oPa', 'atty. roberto.mendoza@lca.edu.ph', 'Atty. Roberto', 'Mendoza', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-10-27 10:08:06'),
(183, 'LCA4001P', '$2y$10$xGObE7bzkwQyAGubQQjkkukjwIQ1Lbx6eWdFh9Uqzg/G8WgF53Vra', 'ms. patricia.garcia@lca.edu.ph', 'Ms. Patricia', 'Garcia', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(184, 'LCA4002P', '$2y$10$58Eo1QAbtjf7RKXLMCv3oO/51cwLieH1nqCc6oeGzpwkHt1sOSF5K', 'mr. jose.martinez@lca.edu.ph', 'Mr. Jose', 'Martinez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(185, 'LCA4003P', '$2y$10$8dATBUJlheZCCMwgtYvZnuBt8TUIn.GKpnP6fIXesfrRx0qcKlLhW', 'dr. carmen.lopez@lca.edu.ph', 'Dr. Carmen', 'Lopez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(186, 'LCA4004P', '$2y$10$iBnvkgBs8iDzdUhxpFLWnu0TjRmuPvYmtUSyV/RS231qdSqWYdQiW', 'ms. elena.gonzalez@lca.edu.ph', 'Ms. Elena', 'Gonzalez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(187, 'LCA4005P', '$2y$10$uWtDnhiGP.g75bJBbvx6LeQEasmAJ/uCanaMUZhfQWPTP4nQQ2062', 'mr. fernando.hernandez@lca.edu.ph', 'Mr. Fernando', 'Hernandez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(188, 'LCA4006P', '$2y$10$GTpa.4mpZR/vx38Xl2xbtOKVeJ/Azy..1l/2/y0zH3rH586o8aUhS', 'ms. isabel.torres@lca.edu.ph', 'Ms. Isabel', 'Torres', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(189, 'LCA4007P', '$2y$10$I59DVr1xA2wGxRNEKpAs/OiTvDr6ISEfCQ8s3LOCn66jQx347GSwi', 'mr. antonio.flores@lca.edu.ph', 'Mr. Antonio', 'Flores', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(190, 'LCA4008P', '$2y$10$uD.alnpd6hVhJ0s5SWiviO4mkai5WETLy2SbuyT.uCldrAemB9vU6', 'ms. rosa.vargas@lca.edu.ph', 'Ms. Rosa', 'Vargas', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-10-21 16:31:39'),
(191, 'LCA4009P', '$2y$10$gdQO3nJeGj7bO4o2YpJSYefjz.OnKKeH7xxyAoXPzqi8Ynhc1/zHS', 'mr. miguel.castillo@lca.edu.ph', 'Mr. Miguel', 'Castillo', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:48', '2025-09-17 23:58:48'),
(192, 'LCA4010P', '$2y$10$ruuIuIRyXJIUVCQvcnsmleJauwp2VxS3EA3hFwwjGOjQ48XXWiHFG', 'mr. luis.morales@lca.edu.ph', 'Mr. Luis', 'Morales', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
(193, 'LCA4011P', '$2y$10$GDkXc8cQEZ.e6OZt1Hbbqe2LttNEP1TWyRFYeM2I4.xnq73Pri87y', 'ms. sofia.jimenez@lca.edu.ph', 'Ms. Sofia', 'Jimenez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
(194, 'LCA4012P', '$2y$10$iF74G9t6NiuZTomNEPp8OeWNo6J3mIeUK3.EqAfxzI4vdb2NixvG.', 'mr. diego.ruiz@lca.edu.ph', 'Mr. Diego', 'Ruiz', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
(195, 'LCA4013P', '$2y$10$BSiLDr1EU6qtAwoC6f5cDuKG3kcgQzkTQ28CkKnAKw7nUsy6GutFu', 'ms. gabriela.diaz@lca.edu.ph', 'Ms. Gabriela', 'Diaz', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-10-27 08:49:00'),
(196, 'LCA4014P', '$2y$10$EclhutZDSphO2i9L9cAbreQteaPVljODCv7a9CazcS4e6uz4C1fkq', 'dr. rafael.moreno@lca.edu.ph', 'Dr. Rafael', 'Moreno', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
(197, 'LCA4015P', '$2y$10$8EKSZnUfISzV2lJEX8xnM.HHpRvE7zA1FhBWozeeQUPzHQuBL4j9e', 'ms. valeria.alvarez@lca.edu.ph', 'Ms. Valeria', 'Alvarez', NULL, NULL, 'active', 1, NULL, '2025-09-17 23:58:49', '2025-09-17 23:58:49'),
(199, 'LCA5001P', '$2y$10$pk/4B3gl8yObHlcpWdGXj.LgSuWb7W6qXOi0FAmoz6r7Rg7dZLUue', 'dr. elena.rodriguez@lca.edu.ph', 'Dr. Elena', 'Rodriguez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
(200, 'LCA5002P', '$2y$10$s5tneYnXDkMx08C2yCfEteo9YPJDoF6JNUPc6NBn71kBp1KZyJ8DK', 'prof. miguel.santos@lca.edu.ph', 'Prof. Miguel', 'Santos', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
(201, 'LCA5003P', '$2y$10$4e.Ewv3tnclPPoyYESEJ1e/EdBrljZTaeKDXS3mGosYYJYGEKa046', 'dr. carmen.garcia@lca.edu.ph', 'Dr. Carmen', 'Garcia', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
(202, 'LCA5004P', '$2y$10$ciHjak50DmSdlU4KkV0F7evc36C3ruAcj5gUv7KFYLBrWlg.s7vbS', 'prof. antonio.martinez@lca.edu.ph', 'Prof. Antonio', 'Martinez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:41', '2025-09-18 00:25:41'),
(203, 'LCA5005P', '$2y$10$0zO00rUseBBAcOvdO/s3UOOiVRkh87pPhQBnGB1TXTyRUo1dyH1fm', 'dr. isabel.lopez@lca.edu.ph', 'Dr. Isabel', 'Lopez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(204, 'LCA5006P', '$2y$10$B/1E.VY2iqVsBAm.hN4wt.0tHoA63FMKdoGK.xpmpdc98y4Stxgbi', 'ms. patricia.gonzalez@lca.edu.ph', 'Ms. Patricia', 'Gonzalez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(205, 'LCA5007P', '$2y$10$QWuq/EfnQLjuvMd2y/o9y..9RB2gqg/ULjxjPn77Kn84cYlzwBXIm', 'mr. roberto.hernandez@lca.edu.ph', 'Mr. Roberto', 'Hernandez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(206, 'LCA5008P', '$2y$10$FdLLWpHETj6u9qfMBvms0.rzRYhRQ.TOCmFSZqrcfdr/Sp0w8wbgW', 'dr. sofia.torres@lca.edu.ph', 'Dr. Sofia', 'Torres', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(207, 'LCA5009P', '$2y$10$dtXiBE52hdnDVSelbVnTxuefXKZETwAMQ/pUffLtMsFPGQYCMu19W', 'prof. diego.flores@lca.edu.ph', 'Prof. Diego', 'Flores', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(208, 'LCA5010P', '$2y$10$K1BlfrG0iU3jo5GuySzuS.mv3FuNItvzczYt88dCVcaKKpECLLpJK', 'ms. gabriela.vargas@lca.edu.ph', 'Ms. Gabriela', 'Vargas', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(209, 'LCA5011P', '$2y$10$/rQVZgT0Q8sLI1qdeDJmtukVrHIU8NuhHQvAGHpxtpMFTOIadvnE.', 'dr. rafael.castillo@lca.edu.ph', 'Dr. Rafael', 'Castillo', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(210, 'LCA5012P', '$2y$10$uxpl2pO3QeM0U5AlRRwuTeRlXNdhZ0V2.Tdaiy17iTgzrkdsDuHPG', 'prof. valeria.morales@lca.edu.ph', 'Prof. Valeria', 'Morales', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(211, 'LCA5013P', '$2y$10$LXs0HXr/NN8Q2wqgmzQCDO1LpiOqtBRmpeuEpn4NpmbbwdT2EuIFu', 'dr. luis.jimenez@lca.edu.ph', 'Dr. Luis', 'Jimenez', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(212, 'LCA5014P', '$2y$10$lAP5V7cVAiEtFvHDMXaMNuuQ8L5CdhS0JgViZ0LyFcmdz7K5qtyvO', 'ms. andrea.ruiz@lca.edu.ph', 'Ms. Andrea', 'Ruiz', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(213, 'LCA5015P', '$2y$10$kTE78taeOvMKVcuAXybs.e.NZxsPGfamR/RpKOzCdmf.FFINagQne', 'prof. carlos.diaz@lca.edu.ph', 'Prof. Carlos', 'Diaz', NULL, NULL, 'active', 1, NULL, '2025-09-18 00:25:42', '2025-09-18 00:25:42'),
(214, 'LCA2004P', '$2y$10$iZFRHeiB3p9r8A/3Fg0xmegkx5Vuvcc3qkIf8Pl8JUU3WBCDRA8yu', 'wendell.lca2004p@lucena.sti.edu.ph', 'Wright', 'Wendell', '', '+63 9434245887', 'active', 1, NULL, '2025-10-09 17:04:13', '2025-10-24 04:59:54'),
(215, 'LCA5020P', '$2y$10$HT3wjn81Ta3nJQqOfB1D1e7S95UHf5ue0j686LBiLqcMULHmvS6da', 'noah.lca5020p@lucena.sti.edu.ph', 'William', 'Noah', NULL, NULL, 'active', 1, NULL, '2025-10-11 16:49:28', '2025-10-11 16:49:28'),
(227, '02000837212', '$2y$10$shjTREhkfCqbFIWtEM57z.9tZYPmLItJTU/9r709DETxfTnKRbJTW', 'rodriguez.837212@lucena.sti.edu.ph', 'Liam', 'Rodriguez', '', NULL, 'active', 1, NULL, '2025-10-11 18:15:04', '2025-10-11 18:15:04'),
(230, '02000183124', '$2y$10$2l345PniIZW/rihTzGEgC.euNcOM6WJS94R2fg09SuyPVNAlK8S26', 'yap.183124@lucena.sti.edu.ph', 'Leonard Venci', 'Yap', '', NULL, 'active', 1, NULL, '2025-10-11 18:36:31', '2025-10-11 18:36:31'),
(231, 'LCA4020P', '$2y$10$yQ55TzcHSjLi1NYDXxE20.rPzATS.UEkBQGyHNwERI1IF98HbsPKm', 'eubion.LCA4020P@lucena.sti.edu.ph', 'Agnes Theresa', 'Eubion', 'A', NULL, 'active', 1, NULL, '2025-10-21 16:35:46', '2025-10-21 16:35:46'),
(232, 'LCA4021P', '$2y$10$mKZ22LSPVT8ibEBqqP03SOF1zk2hXi6DSKC.CqWqspBptTFMlyM6.', 'Gabito.LCA4021P@lucena.sti.edu.ph', 'Camille Mae', 'Gabito', '', NULL, 'active', 1, NULL, '2025-10-23 09:05:40', '2025-10-23 09:05:40'),
(233, 'LCA4022P', '$2y$10$zwCRlNyh2OU1kBsumt3Ne.tfQp2GUc70lrN4jjjPn24cleds0xt5e', 'Bamba.LCA4022P@lucena.sti.edu.ph', 'Lalaine Dhel', 'Bamba', '', NULL, 'active', 1, NULL, '2025-10-23 09:06:57', '2025-10-23 09:06:57'),
(234, 'LCA4023P', '$2y$10$i8T0b/AmQgU3y2JMlCJMs.VKoYHaAb5n00zKdXLvz4.SwgB0ZeKUG', 'Diaz.LCA4023P@lucena.sti.edu.ph', 'Vicente', 'Diaz', 'A.', NULL, 'active', 1, NULL, '2025-10-23 09:07:49', '2025-10-23 09:07:49'),
(235, 'LCA4024P', '$2y$10$K2LUWZVhJ8eBHbKVqtrAO.CreJ5GTPsgTG8jGp9aoXL05DxxZ0m02', 'Destreza.LCA4024P@lucena.sti.edu.ph', 'Rozel Mae', 'Destreza', '', NULL, 'active', 1, NULL, '2025-10-23 09:08:57', '2025-10-23 09:08:57'),
(236, 'LCA4025P', '$2y$10$ntIrp3HDEPrFfUFWPt4bK.brIizrfXVhQuzqqkpylmdYc2om/tnnS', 'Cabaluna.LCA4025P@lucena.sti.edu.ph', 'Angelo', 'Cabaluna', '', NULL, 'active', 1, NULL, '2025-10-23 09:10:07', '2025-10-23 09:10:07'),
(237, 'LCA4026P', '$2y$10$bp4iOQWPx8ZVqDwqtnoo.Odn0qFkVevkywwUJpX3OfZFxvFhosYLK', 'Martinez.LCA4026P@lucena.sti.edu.ph', 'Jan Lorenz', 'Martinez', '', NULL, 'active', 1, NULL, '2025-10-23 20:38:37', '2025-10-23 20:38:37'),
(238, 'LCA5030P', '$2y$10$1/8ma3xo6iqslExhI5GSUe736By.9CVpDNis9LtVK.YXtj9eu/BIy', 'LCA5030P@placeholder.local', 'John Kristoffer', 'Tibor', NULL, NULL, 'active', 1, NULL, '2025-10-23 21:06:41', '2025-11-09 04:47:18'),
(239, 'LCA5031P', '$2y$10$XyXdYTY7AdK66uOA/blRJe/zXiWjYMM9Nn0yqh1L9SzarjlAykH7y', 'LCA5031P@placeholder.local', 'Marka', 'Lee', NULL, NULL, 'active', 1, NULL, '2025-10-23 21:31:16', '2025-10-23 21:31:16'),
(240, 'LCA4027P', '$2y$10$/XYShBEFlT0dzlmnsioSnuFHucrUt5yYMWbIlvHoqX7ApdINckXUe', 'LCA4027P@placeholder.local', 'Jason', 'Amparo', '', NULL, 'active', 1, NULL, '2025-10-23 21:36:17', '2025-10-23 21:36:17'),
(241, 'LCA4028P', '$2y$10$.xcWyt8.h3TF40w0pXmZSeqqKuXH3.ZKoIxkvuekxaH1WgvaUUPIa', 'LCA4028P@placeholder.local', 'Ruel', 'Fernandez', '', NULL, 'active', 1, NULL, '2025-10-23 21:47:35', '2025-10-23 21:47:35'),
(242, 'LCA4029P', '$2y$10$gPCRapa/p80aTXqVAMxMg.nZiKHKGdpGJm4CI2/s5YFjkV1AW9Xla', 'LCA4029P@placeholder.local', 'Vicente', 'Diaz', '', NULL, 'active', 1, NULL, '2025-10-23 21:58:55', '2025-10-23 21:58:55'),
(243, 'LCA4030P', '$2y$10$saZ0EIpnL0LYBRqa05KZK.dzxBefE.qF96ljEowPX3m6KF1Zk.PyC', 'LCA4030P@placeholder.local', 'John Lenard', 'Casino', '', NULL, 'active', 1, NULL, '2025-10-23 22:03:11', '2025-10-23 22:03:11'),
(244, 'LCA4031P', '$2y$10$CvZyrzUqjRr976v2WvLNVetPyJE0SZcgvdgbaMSe6RgEkzVKEJ9ny', 'LCA4031P@placeholder.local', 'Nino', 'Magarao', 'V.', NULL, 'active', 1, NULL, '2025-10-23 22:04:30', '2025-10-23 22:04:30'),
(245, 'LCA4032P', '$2y$10$nEEmO6rHlizGxX.n7KT0weR3nMPGFeugJQUqTVPzPJUn8UnDhAIQi', 'LCA4032P@placeholder.local', 'Melody', 'Tadena', '', NULL, 'active', 1, NULL, '2025-10-23 22:05:52', '2025-10-23 22:05:52'),
(246, 'LCA4033P', '$2y$10$/sL1jyXXdeFxS6R9WJ7Df.Q6lc42aJe8L7zbh2UXNsvhaFbgDjZ9O', 'LCA4033P@placeholder.local', 'Katherine', 'De Torres', '', NULL, 'active', 1, NULL, '2025-10-23 22:07:15', '2025-10-23 22:07:15'),
(247, 'LCA4034P', '$2y$10$IR/tpBrLCL.YV/RvtC.ETOWWOXfkk/K86epPiK7YZoahRAypbf6Ua', 'LCA4034P@placeholder.local', 'Lilet', 'Ricalde', '', NULL, 'active', 1, NULL, '2025-10-23 22:08:05', '2025-10-23 22:08:05'),
(253, 'LCA2326P', '$2y$10$/exET3afc5f6kHkFQjsMyOZgUDklWWUOrJawYOm6KnbXvWDAmcnf6', 'sammir.glorioso@lucena.sti.edu.ph', 'Sammir', 'Glorioso', NULL, NULL, 'resigned', 1, NULL, '2025-10-27 08:16:54', '2025-11-09 04:47:18'),
(254, 'LCA2631P', '$2y$10$Hh5XlTDvaDTtrJezV3OHvulObA7lKHASTmUM8WxZtl5FEKmU2kYB6', 'LCA2631P@placeholder.local', 'Palo', 'Man', NULL, NULL, 'active', 1, NULL, '2025-10-27 09:47:23', '2025-10-27 09:47:23'),
(255, 'LCA2031P', '$2y$10$sZ5NT4QREvxiYrklDK2vbO3en2V2.hkw/eAG5QJ5TbPl1KB4zwxf2', 'LCA2031P@placeholder.local', 'One', 'Luck', NULL, NULL, 'active', 1, NULL, '2025-10-27 09:52:48', '2025-10-27 09:52:48'),
(256, '02000290002', '$2y$10$Q0mqDaO2hubhKqXWWqktyueGgXl2TSmuiVfQiOL3eDOIH4nB9o8em', NULL, 'Test', 'Two', 'College', NULL, 'active', 1, NULL, '2025-11-05 06:48:39', '2025-11-05 08:01:23'),
(257, '02000290008', '$2y$10$HFK9jt0UDEff9HH0BGkEPO7vJP55tRB4UoWtOyGhvV5cvUyOdOuZy', NULL, 'Test', 'Eight', 'College', NULL, 'active', 1, NULL, '2025-11-05 07:56:00', '2025-11-05 07:56:00');

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
(487, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-09-11 01:31:09'),
(488, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 10:08:12'),
(489, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 18:53:03'),
(490, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 20:03:58'),
(491, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-12 20:04:17'),
(497, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 09:07:12'),
(499, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 19:23:46'),
(510, 1, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-14 20:42:12'),
(514, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 09:51:12'),
(515, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-15 20:34:44'),
(516, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 20:37:53'),
(520, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 14:25:26'),
(521, 90, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 21:09:50'),
(522, 95, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 21:09:50'),
(523, 118, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 21:09:50'),
(524, 123, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 21:09:51'),
(525, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 21:10:21'),
(526, 90, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 22:20:15'),
(527, 1, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-16 22:38:23'),
(528, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 01:48:00'),
(529, 114, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 03:18:53'),
(530, 112, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 08:06:08'),
(531, 98, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 08:07:49'),
(532, 116, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 17:33:26'),
(533, 137, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 18:08:19'),
(534, 137, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 18:27:35'),
(535, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 19:03:27'),
(536, 134, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 19:04:59'),
(537, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 20:24:48'),
(538, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 20:33:47'),
(539, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 22:59:08'),
(540, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 23:03:11'),
(541, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 23:09:32'),
(542, 91, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 23:10:16'),
(546, 199, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:26:16'),
(547, 114, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:28:17'),
(548, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:29:21'),
(549, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:33:18'),
(550, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:47:22'),
(551, 213, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:48:49'),
(552, 212, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 00:56:17'),
(553, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 01:17:53'),
(554, 213, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 01:19:27'),
(555, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 01:25:55'),
(556, 143, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 01:37:17'),
(557, 213, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:33:00'),
(558, 114, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-18 06:34:08'),
(559, 114, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:50:32'),
(560, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:17:29'),
(561, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 22:01:48'),
(562, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-19 11:36:27'),
(563, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-20 11:17:24'),
(564, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 13:18:56'),
(565, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-20 17:27:16'),
(566, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 17:39:07'),
(567, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 16:11:12'),
(568, 90, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 16:57:51'),
(569, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 20:47:03'),
(570, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-22 15:15:16'),
(571, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 15:46:50'),
(572, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 20:56:00'),
(573, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-22 21:50:52'),
(574, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-23 20:49:57'),
(575, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 20:52:01'),
(576, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 01:27:57'),
(577, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 03:24:59'),
(578, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 03:26:36'),
(579, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 15:30:10'),
(580, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-25 21:33:00'),
(581, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 03:11:34'),
(582, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 03:17:31'),
(583, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 03:18:23'),
(584, 1, 'login', '{\"details\":\"User logged in successfully\"}', 'unknown', 'unknown', '2025-09-27 00:33:55'),
(585, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 00:34:52'),
(586, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-27 00:37:23'),
(587, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-27 01:26:44'),
(588, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 01:27:44'),
(589, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-27 04:51:12'),
(590, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:49:35'),
(591, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:55:13'),
(592, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-28 02:27:13'),
(593, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-29 21:12:34'),
(594, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-29 21:13:32'),
(595, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 16:56:15'),
(596, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 17:09:42'),
(597, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-06 11:44:31'),
(598, 214, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:04:13'),
(599, 1, 'Staff Registered', '{\"target_user_id\":214,\"employee_id\":\"LCA2004P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:04:13'),
(600, 1, 'staff_registered', '{\"employee_id\":\"LCA2004P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:04:13'),
(601, 1, 'program_head_assigned', '{\"user_id\":214,\"department_ids\":[45],\"transfer\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:04:13'),
(602, 179, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:39:33'),
(603, 1, 'Staff Updated', '{\"target_user_id\":179,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"first_name\",\"last_name\",\"middle_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:39:33'),
(604, 180, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:34:49'),
(605, 1, 'Staff Updated', '{\"target_user_id\":180,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"first_name\",\"last_name\",\"middle_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:34:49'),
(606, 180, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:37:41'),
(607, 1, 'Staff Updated', '{\"target_user_id\":180,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:37:41'),
(608, 179, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:41:18'),
(609, 1, 'Staff Updated', '{\"target_user_id\":179,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:41:18'),
(610, 180, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:45:06'),
(611, 1, 'Staff Updated', '{\"target_user_id\":180,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:45:06'),
(612, 181, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:51:34'),
(613, 1, 'Staff Updated', '{\"target_user_id\":181,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"middle_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:51:34'),
(614, 214, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:52:34'),
(615, 1, 'Staff Updated', '{\"target_user_id\":214,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:52:34'),
(616, 214, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:54:28'),
(617, 1, 'Staff Updated', '{\"target_user_id\":214,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"staffStatus\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 18:54:28'),
(618, 215, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 16:49:28'),
(630, 227, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 18:15:04'),
(633, 230, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 18:36:31'),
(634, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-18 13:25:14'),
(635, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-18 20:22:53'),
(636, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 05:29:38'),
(637, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 05:36:55'),
(638, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 05:38:14'),
(639, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 06:07:37'),
(640, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:00:09'),
(641, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:00:31'),
(642, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 08:05:33'),
(643, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 08:06:48'),
(644, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 08:08:02'),
(645, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 13:53:53'),
(646, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 13:54:52'),
(647, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 13:56:11'),
(648, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3574}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:17:18'),
(649, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:32:16'),
(650, 231, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:35:46'),
(651, 1, 'Staff Registered', '{\"target_user_id\":231,\"employee_id\":\"LCA4020P\",\"designation\":\"Cashier\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:35:46'),
(652, 1, 'staff_registered', '{\"employee_id\":\"LCA4020P\",\"name\":null,\"designation\":\"Cashier\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:35:46'),
(653, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 16:53:14'),
(654, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 18:43:00'),
(655, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:37:01'),
(656, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:51:37'),
(657, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:51:56'),
(658, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:52:53'),
(659, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3575}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:31'),
(660, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3576}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:31'),
(661, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3577}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:33'),
(662, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3578}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:36'),
(663, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3579}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:38'),
(664, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3580}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 08:59:44'),
(665, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:01:34'),
(666, 232, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:05:40'),
(667, 1, 'Staff Registered', '{\"target_user_id\":232,\"employee_id\":\"LCA4021P\",\"designation\":\"Registrar\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:05:40'),
(668, 1, 'staff_registered', '{\"employee_id\":\"LCA4021P\",\"name\":null,\"designation\":\"Registrar\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:05:40'),
(669, 233, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:06:57'),
(670, 1, 'Staff Registered', '{\"target_user_id\":233,\"employee_id\":\"LCA4022P\",\"designation\":\"Disciplinary Officer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:06:57'),
(671, 1, 'staff_registered', '{\"employee_id\":\"LCA4022P\",\"name\":null,\"designation\":\"Disciplinary Officer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:06:57'),
(672, 234, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:07:49'),
(673, 1, 'Staff Registered', '{\"target_user_id\":234,\"employee_id\":\"LCA4023P\",\"designation\":\"MIS\\/IT\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:07:49'),
(674, 1, 'staff_registered', '{\"employee_id\":\"LCA4023P\",\"name\":null,\"designation\":\"MIS\\/IT\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:07:49'),
(675, 235, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:08:57'),
(676, 1, 'Staff Registered', '{\"target_user_id\":235,\"employee_id\":\"LCA4024P\",\"designation\":\"Librarian\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:08:57'),
(677, 1, 'staff_registered', '{\"employee_id\":\"LCA4024P\",\"name\":null,\"designation\":\"Librarian\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:08:57'),
(678, 236, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:10:07'),
(679, 1, 'Staff Registered', '{\"target_user_id\":236,\"employee_id\":\"LCA4025P\",\"designation\":\"Clinic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:10:07'),
(680, 1, 'staff_registered', '{\"employee_id\":\"LCA4025P\",\"name\":null,\"designation\":\"Clinic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 09:10:07'),
(681, 179, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 10:16:01'),
(682, 179, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 10:44:46'),
(683, 179, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 10:47:02'),
(684, 179, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:42:46'),
(685, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:52:08'),
(686, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:52:51'),
(687, 179, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:53:07'),
(688, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:53:14'),
(689, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 11:53:37'),
(690, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 15:43:53'),
(691, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:15:48'),
(692, 180, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:21:14'),
(693, 181, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:21:38'),
(694, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:21:51'),
(695, 180, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:22:01'),
(696, 180, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:23:33'),
(697, 181, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:23:44'),
(698, 181, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:24:07'),
(699, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:24:22'),
(700, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:56:27'),
(701, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:56:46'),
(702, 231, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:57:04'),
(703, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 18:58:05'),
(704, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:13:36'),
(705, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:14:06'),
(706, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:14:09'),
(707, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:14:21'),
(708, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:14:30'),
(709, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:16:16'),
(710, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 19:16:39'),
(711, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:17:44'),
(712, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:29:41'),
(713, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:30:08'),
(714, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:30:18'),
(715, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:31:40'),
(716, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:31:43'),
(717, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:35:36'),
(718, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:35:47'),
(719, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:36:38'),
(720, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:36:41'),
(721, 237, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:38:37'),
(722, 1, 'Staff Registered', '{\"target_user_id\":237,\"employee_id\":\"LCA4026P\",\"designation\":\"Guidance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:38:37'),
(723, 1, 'staff_registered', '{\"employee_id\":\"LCA4026P\",\"name\":null,\"designation\":\"Guidance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:38:37'),
(724, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:38:44'),
(725, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:11'),
(726, 234, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":4,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:22'),
(727, 234, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:32'),
(728, 235, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:44'),
(729, 235, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":3,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:53'),
(730, 235, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:39:58'),
(731, 236, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:09'),
(732, 236, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":16,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:17'),
(733, 236, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:25'),
(734, 233, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:35');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(735, 233, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":15,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:41'),
(736, 233, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:44'),
(737, 237, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:40:59'),
(738, 237, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":14,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:42:12'),
(739, 237, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:42:38'),
(740, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:42:54'),
(741, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00103\",\"signatory_id\":3581}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:43:25'),
(742, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:43:33'),
(743, 232, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:43:50'),
(744, 232, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:44:23'),
(745, 232, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:48:45'),
(746, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:49:03'),
(747, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:52:08'),
(748, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 20:52:12'),
(749, 238, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:06:41'),
(750, 238, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:23:00'),
(751, 239, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:31:16'),
(752, 240, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:36:17'),
(753, 1, 'Staff Registered', '{\"target_user_id\":240,\"employee_id\":\"LCA4027P\",\"designation\":\"PAMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:36:17'),
(754, 1, 'staff_registered', '{\"employee_id\":\"LCA4027P\",\"name\":null,\"designation\":\"PAMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:36:17'),
(755, 241, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:47:35'),
(756, 1, 'Staff Registered', '{\"target_user_id\":241,\"employee_id\":\"LCA4028P\",\"designation\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:47:35'),
(757, 1, 'staff_registered', '{\"employee_id\":\"LCA4028P\",\"name\":null,\"designation\":\"Building Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:47:35'),
(758, 242, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:58:55'),
(759, 1, 'Staff Registered', '{\"target_user_id\":242,\"employee_id\":\"LCA4029P\",\"designation\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:58:55'),
(760, 1, 'staff_registered', '{\"employee_id\":\"LCA4029P\",\"name\":null,\"designation\":\"Petty Cash Custodian\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 21:58:55'),
(761, 243, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:03:11'),
(762, 1, 'Staff Registered', '{\"target_user_id\":243,\"employee_id\":\"LCA4030P\",\"designation\":\"Student Affairs Officer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:03:11'),
(763, 1, 'staff_registered', '{\"employee_id\":\"LCA4030P\",\"name\":null,\"designation\":\"Student Affairs Officer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:03:11'),
(764, 244, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:04:30'),
(765, 1, 'Staff Registered', '{\"target_user_id\":244,\"employee_id\":\"LCA4031P\",\"designation\":\"Academic Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:04:30'),
(766, 1, 'staff_registered', '{\"employee_id\":\"LCA4031P\",\"name\":null,\"designation\":\"Academic Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:04:30'),
(767, 245, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:05:52'),
(768, 1, 'Staff Registered', '{\"target_user_id\":245,\"employee_id\":\"LCA4032P\",\"designation\":\"Accountant\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:05:52'),
(769, 1, 'staff_registered', '{\"employee_id\":\"LCA4032P\",\"name\":null,\"designation\":\"Accountant\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:05:52'),
(770, 246, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:07:15'),
(771, 1, 'Staff Registered', '{\"target_user_id\":246,\"employee_id\":\"LCA4033P\",\"designation\":\"School Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:07:15'),
(772, 1, 'staff_registered', '{\"employee_id\":\"LCA4033P\",\"name\":null,\"designation\":\"School Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:07:15'),
(773, 247, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:08:05'),
(774, 1, 'Staff Registered', '{\"target_user_id\":247,\"employee_id\":\"LCA4034P\",\"designation\":\"HR\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:08:05'),
(775, 1, 'staff_registered', '{\"employee_id\":\"LCA4034P\",\"name\":null,\"designation\":\"HR\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:08:05'),
(776, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:08:19'),
(777, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 22:08:23'),
(778, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 23:03:42'),
(779, 238, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 23:03:59'),
(780, 238, 'Signatory Apply', '{\"form_id\":\"CF-2025-00119\",\"signatory_id\":3787}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 02:17:45'),
(781, 238, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 02:18:19'),
(782, 245, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 02:18:41'),
(783, 245, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 03:05:42'),
(784, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 03:06:00'),
(785, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3843}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 03:06:04'),
(786, 239, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 03:06:07'),
(787, 245, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 03:06:19'),
(788, 245, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":12,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:47:55'),
(789, 245, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":12,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:50:44'),
(790, 245, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:52:37'),
(791, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:52:52'),
(792, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3842}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:04'),
(793, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3844}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:05'),
(794, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3845}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:06'),
(795, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3846}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:06'),
(796, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3847}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:07'),
(797, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3848}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:07'),
(798, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3849}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:08'),
(799, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3850}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:08'),
(800, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3851}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:09'),
(801, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3853}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:09'),
(802, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3855}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:10'),
(803, 239, 'Signatory Apply', '{\"form_id\":\"CF-2025-00123\",\"signatory_id\":3906}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:11'),
(804, 239, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:34'),
(805, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:53:53'),
(806, 234, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":4,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:54:33'),
(807, 234, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:54:35'),
(808, 235, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:54:49'),
(809, 235, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":3,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:04'),
(810, 235, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:06'),
(811, 233, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:16'),
(812, 233, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":15,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:23'),
(813, 233, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:38'),
(814, 237, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:44'),
(815, 237, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":14,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:55'),
(816, 237, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:55:56'),
(817, 242, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:00'),
(818, 242, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":11,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:09'),
(819, 242, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:10'),
(820, 241, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:15'),
(821, 241, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":5,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:23'),
(822, 241, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:24'),
(823, 243, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:28'),
(824, 243, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":7,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:36'),
(825, 243, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:37'),
(826, 244, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:42'),
(827, 244, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":13,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:50'),
(828, 244, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:56:54'),
(829, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:57:17'),
(830, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:57:49'),
(831, 247, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:57:53'),
(832, 247, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":6,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:57:59'),
(833, 247, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:00'),
(834, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:09'),
(835, 239, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:23'),
(836, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:27'),
(837, 214, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:59'),
(838, 1, 'Staff Updated', '{\"target_user_id\":214,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"assignedDepartments[]\",\"staffEmail\",\"staffContact\",\"isAlsoFaculty\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"assignedDepartments\",\"first_name\",\"last_name\",\"role_id\",\"is_also_faculty\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:58:59'),
(839, 214, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:59:54'),
(840, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:00:49'),
(841, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:01:06'),
(842, 214, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:39:10'),
(843, 214, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:40:48'),
(844, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:41:10'),
(845, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:42:09'),
(846, 240, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:42:17'),
(847, 240, 'Signatory Action', '{\"target_user_id\":239,\"designation_id\":10,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:42:23'),
(848, 240, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:42:28'),
(849, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:42:49'),
(850, 239, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:43:06'),
(851, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:43:11'),
(852, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:57:01'),
(853, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:21:59'),
(854, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 13:00:29'),
(855, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 06:53:24'),
(856, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:13:17'),
(857, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:55:25'),
(858, 236, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:56:05'),
(859, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 18:05:50'),
(860, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 18:32:36'),
(861, 238, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 18:38:04'),
(862, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 05:00:54'),
(863, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 05:43:03'),
(864, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 05:55:49'),
(865, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 06:33:29'),
(866, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 06:41:43'),
(867, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:01:35'),
(868, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 08:05:14'),
(869, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4163}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 08:05:23'),
(870, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:08:00'),
(871, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:09:31'),
(872, 253, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 08:16:54'),
(873, 253, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 08:17:28'),
(874, 253, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 08:27:17'),
(875, 253, 'Signatory Apply', '{\"form_id\":\"CF-2025-00161\",\"signatory_id\":4201}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 08:27:39'),
(876, 195, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:34:17'),
(877, 195, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:36:01'),
(878, 195, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:48:42'),
(879, 195, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 08:49:00'),
(880, 195, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:57:57'),
(881, 195, 'Signatory Action', '{\"target_user_id\":253,\"designation_id\":12,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 08:59:12'),
(882, 195, 'Signatory Action', '{\"target_user_id\":253,\"designation_id\":12,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:02:47'),
(883, 253, 'Signatory Apply', '{\"form_id\":\"CF-2025-00161\",\"signatory_id\":4203}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:03:14'),
(884, 253, 'Signatory Apply', '{\"form_id\":\"CF-2025-00161\",\"signatory_id\":4204}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:03:17'),
(885, 195, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:03:28'),
(886, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:08:52'),
(887, 182, 'Signatory Action', '{\"target_user_id\":253,\"designation_id\":9,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:12:39'),
(888, 182, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:29:27'),
(889, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:29:52'),
(890, 254, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 09:47:23'),
(891, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 09:48:08'),
(892, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 09:48:14'),
(893, 253, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:48:25'),
(894, 254, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:48:46'),
(895, 255, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 09:52:48'),
(896, 254, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:52:54'),
(897, 255, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:53:06'),
(898, 255, 'Signatory Apply', '{\"form_id\":\"CF-2025-00180\",\"signatory_id\":4277}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:54:04'),
(899, 214, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:54:29'),
(900, 255, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:55:03'),
(901, 255, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:56:12'),
(902, 245, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:56:40'),
(903, 245, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:57:00'),
(904, 195, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:57:32'),
(905, 195, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":12,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:57:58'),
(906, 195, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":12,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:58:46'),
(907, 255, 'Signatory Apply', '{\"form_id\":\"CF-2025-00180\",\"signatory_id\":4279}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:59:08'),
(908, 255, 'Signatory Apply', '{\"form_id\":\"CF-2025-00180\",\"signatory_id\":4280}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:59:10'),
(909, 255, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 09:59:14'),
(910, 195, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:59:20'),
(911, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 09:59:38'),
(912, 214, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:00:00'),
(913, 214, 'Signatory Action', '{\"target_user_id\":253,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:00:16'),
(914, 255, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:05:57'),
(915, 214, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:06:26'),
(916, 214, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:06:52'),
(917, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:06:59'),
(918, 182, 'password_reset', '{\"details\":\"Password reset by administrator\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-27 10:08:06'),
(919, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:08:30'),
(920, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:08:40'),
(921, 182, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":9,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:09:12'),
(922, 182, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":9,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:09:28'),
(923, 182, 'Signatory Action', '{\"target_user_id\":253,\"designation_id\":9,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:09:33'),
(924, 255, 'Signatory Apply', '{\"form_id\":\"CF-2025-00180\",\"signatory_id\":4278}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:09:45'),
(925, 182, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:14:14'),
(926, 232, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:14:32'),
(927, 232, 'Signatory Action', '{\"target_user_id\":255,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:14:47'),
(928, 255, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:17:20'),
(929, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:17:28'),
(930, 232, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:18:26'),
(931, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:19:30'),
(932, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:21:38'),
(933, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:22:13'),
(934, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:22:22'),
(935, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4164}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:30'),
(936, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4165}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:31'),
(937, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4166}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:34'),
(938, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4169}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:35'),
(939, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4168}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:39'),
(940, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4171}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:43'),
(941, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4167}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:22:44'),
(942, 231, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:22:51'),
(943, 236, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:23:39'),
(944, 236, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":16,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:23:56'),
(945, 236, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:24:01'),
(946, 233, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:24:19');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(947, 233, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":15,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:24:32'),
(948, 233, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:24:49'),
(949, 237, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:25:00'),
(950, 237, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":14,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:25:16'),
(951, 237, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:25:19'),
(952, 235, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:26:26'),
(953, 235, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":3,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:26:43'),
(954, 235, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:26:44'),
(955, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:27:05'),
(956, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:27:13'),
(957, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:27:30'),
(958, 234, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":4,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:27:49'),
(959, 234, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:28:01'),
(960, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:28:34'),
(961, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:28:49'),
(962, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:28:53'),
(963, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:28:57'),
(964, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:29:02'),
(965, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00154\",\"signatory_id\":4170}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 10:29:08'),
(966, 232, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:29:31'),
(967, 232, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 10:29:54'),
(968, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 11:11:01'),
(969, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 11:14:14'),
(970, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 11:37:57'),
(971, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-27 15:26:22'),
(972, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-27 16:38:47'),
(973, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 00:21:49'),
(974, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 06:59:30'),
(975, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 09:07:17'),
(976, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 09:14:40'),
(977, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 09:15:37'),
(978, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 09:15:50'),
(979, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00208\",\"signatory_id\":4389}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 09:19:22'),
(980, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 09:40:53'),
(981, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 09:49:26'),
(982, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 10:02:01'),
(983, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 10:21:17'),
(984, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 10:42:50'),
(985, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 12:30:39'),
(986, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 17:05:28'),
(987, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 20:12:40'),
(988, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 21:10:32'),
(989, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 21:53:21'),
(990, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 21:56:43'),
(991, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 22:00:56'),
(992, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-28 22:02:57'),
(993, 239, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 22:33:41'),
(994, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 22:33:58'),
(995, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 22:37:08'),
(996, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 09:22:10'),
(997, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 10:58:55'),
(998, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-29 11:08:34'),
(999, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-29 12:25:44'),
(1000, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 12:26:58'),
(1001, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-29 21:46:26'),
(1002, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-29 22:07:04'),
(1003, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-30 02:48:10'),
(1004, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-30 03:58:42'),
(1005, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-30 04:00:48'),
(1006, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 04:01:46'),
(1007, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-30 04:06:16'),
(1008, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-30 04:11:18'),
(1009, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-30 10:07:51'),
(1010, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-30 10:08:24'),
(1011, 232, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 10:08:53'),
(1012, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-31 10:25:24'),
(1013, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 11:03:44'),
(1014, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-31 11:33:19'),
(1015, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-31 16:57:51'),
(1016, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-31 16:58:24'),
(1017, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-01 03:31:56'),
(1018, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-01 09:26:58'),
(1019, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-01 10:30:15'),
(1020, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 12:10:01'),
(1021, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-01 12:12:50'),
(1022, 231, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-01 12:14:58'),
(1023, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00208\",\"signatory_id\":4391}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-01 12:18:43'),
(1024, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00208\",\"signatory_id\":4392}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-01 12:18:44'),
(1025, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 10:42:17'),
(1026, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-02 14:58:48'),
(1027, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-03 06:27:22'),
(1028, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 06:28:00'),
(1029, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-03 07:28:45'),
(1030, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-03 08:50:48'),
(1031, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 09:05:51'),
(1032, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 10:13:41'),
(1033, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 10:15:19'),
(1034, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 11:41:54'),
(1035, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 11:43:08'),
(1036, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 11:50:04'),
(1037, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 16:37:45'),
(1038, 214, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:18:34'),
(1039, 214, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:23:42'),
(1040, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:24:02'),
(1041, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 06:05:19'),
(1042, 256, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-05 06:52:24'),
(1043, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 00:03:09'),
(1044, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 14:55:57'),
(1045, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 03:53:43'),
(1046, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 08:47:55'),
(1047, 141, 'graduation_status_updated', '{\"action\":\"graduated\",\"student_id\":\"02000200030\",\"student_name\":\"Diego Vega\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:02:11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:02:11'),
(1048, 148, 'graduation_status_updated', '{\"action\":\"graduated\",\"student_id\":\"02000200025\",\"student_name\":\"Yolanda Vargas\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:02:11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:02:11'),
(1049, 1, 'bulk_graduation_update', '{\"action\":\"graduate\",\"student_count\":2,\"student_ids\":[141,148],\"timestamp\":\"2025-11-07 11:02:11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:02:11'),
(1050, 90, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100001\",\"student_name\":\"John Doe\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1051, 91, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100002\",\"student_name\":\"Jane Smith\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1052, 92, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100003\",\"student_name\":\"Michael Johnson\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1053, 93, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100004\",\"student_name\":\"Sarah Brown\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1054, 94, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100005\",\"student_name\":\"David Davis\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1055, 95, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100006\",\"student_name\":\"Emily Wilson\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1056, 96, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100007\",\"student_name\":\"Christopher Moore\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1057, 97, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100008\",\"student_name\":\"Jessica Taylor\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1058, 98, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100009\",\"student_name\":\"Matthew Anderson\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1059, 99, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100010\",\"student_name\":\"Ashley Thomas\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1060, 100, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100011\",\"student_name\":\"Daniel Jackson\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1061, 101, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100012\",\"student_name\":\"Amanda White\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1062, 102, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100013\",\"student_name\":\"James Harris\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1063, 103, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100014\",\"student_name\":\"Jennifer Martin\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1064, 104, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100015\",\"student_name\":\"Robert Thompson\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1065, 105, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100016\",\"student_name\":\"Lisa Garcia\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1066, 106, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100017\",\"student_name\":\"William Martinez\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1067, 107, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100018\",\"student_name\":\"Michelle Robinson\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1068, 108, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100019\",\"student_name\":\"Charles Clark\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1069, 109, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100020\",\"student_name\":\"Patricia Rodriguez\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1070, 110, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100021\",\"student_name\":\"Thomas Lewis\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1071, 111, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100022\",\"student_name\":\"Barbara Lee\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1072, 112, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100023\",\"student_name\":\"Richard Walker\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1073, 113, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100024\",\"student_name\":\"Susan Hall\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1074, 114, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100025\",\"student_name\":\"Joseph Allen\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1075, 115, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100026\",\"student_name\":\"Elizabeth Young\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1076, 116, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100027\",\"student_name\":\"Christopher Hernandez\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1077, 117, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100028\",\"student_name\":\"Maria King\",\"year_level\":\"3rd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1078, 230, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000183124\",\"student_name\":\"Leonard Venci Yap\",\"year_level\":\"4th Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1079, 118, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200001\",\"student_name\":\"Alex Garcia\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1080, 119, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200002\",\"student_name\":\"Bianca Santos\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1081, 120, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200003\",\"student_name\":\"Carlos Cruz\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1082, 121, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200004\",\"student_name\":\"Diana Reyes\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1083, 122, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200005\",\"student_name\":\"Eduardo Mendoza\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1084, 123, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200006\",\"student_name\":\"Fatima Torres\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1085, 124, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200007\",\"student_name\":\"Gabriel Gonzalez\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1086, 125, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200008\",\"student_name\":\"Hannah Lopez\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1087, 126, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200009\",\"student_name\":\"Ivan Martinez\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1088, 127, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200010\",\"student_name\":\"Julia Hernandez\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1089, 128, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200011\",\"student_name\":\"Kevin Gutierrez\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1090, 129, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200012\",\"student_name\":\"Luna Morales\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1091, 130, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200013\",\"student_name\":\"Miguel Jimenez\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1092, 131, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200014\",\"student_name\":\"Nina Ruiz\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1093, 132, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200015\",\"student_name\":\"Oscar Diaz\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1094, 133, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200016\",\"student_name\":\"Paula Moreno\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1095, 134, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200017\",\"student_name\":\"Quentin Alvarez\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1096, 144, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200018\",\"student_name\":\"Rosa Romero\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1097, 145, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200019\",\"student_name\":\"Sebastian Navarro\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1098, 146, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200020\",\"student_name\":\"Teresa Molina\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1099, 135, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200021\",\"student_name\":\"Ulises Ramos\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1100, 136, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200022\",\"student_name\":\"Valentina Herrera\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1101, 137, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200023\",\"student_name\":\"Walter Medina\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1102, 147, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200024\",\"student_name\":\"Ximena Castillo\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1103, 148, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200025\",\"student_name\":\"Yolanda Vargas\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1104, 149, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200026\",\"student_name\":\"Zachary Castro\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1105, 138, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200027\",\"student_name\":\"Adriana Ortega\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1106, 139, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200028\",\"student_name\":\"Bruno Flores\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1107, 140, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200029\",\"student_name\":\"Camila Silva\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1108, 141, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200030\",\"student_name\":\"Diego Vega\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1109, 142, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200031\",\"student_name\":\"Elena Guerrero\",\"year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1110, 143, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000200032\",\"student_name\":\"Fernando Pena\",\"year_level\":\"1st Year\",\"sector\":\"Senior High School\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1111, 256, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000290002\",\"student_name\":\"Test Two\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1112, 257, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000290008\",\"student_name\":\"Test Eight\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1113, 1, 'bulk_retention_update', '{\"action\":\"retention_selected\",\"student_count\":63,\"student_ids\":[147,149,148,144,145,146,137,143,138,139,140,141,142,136,135,134,133,121,122,123,124,125,126,127,128,129,130,131,132,120,119,118,257,256,230,107,108,109,110,111,112,113,114,115,116,117,106,105,104,91,92,93,94,95,96,97,98,99,100,101,102,103,90],\"timestamp\":\"2025-11-07 11:54:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 10:54:06'),
(1114, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 19:52:05'),
(1115, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 21:45:26'),
(1116, 227, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000837212\",\"student_name\":\"Liam Rodriguez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":null,\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 22:50:18\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 21:50:18');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1117, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2028-2029\",\"incremented_count\":1,\"retained_count\":63,\"retention_flags_reset\":63,\"timestamp\":\"2025-11-07 22:50:18\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 21:50:18'),
(1118, 113, 'year_level_retention_set', '{\"action\":\"retention_selected\",\"student_id\":\"02000100024\",\"student_name\":\"Susan Hall\",\"year_level\":\"2nd Year\",\"sector\":\"College\",\"updated_by\":1,\"timestamp\":\"2025-11-07 23:05:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:03'),
(1119, 1, 'bulk_retention_update', '{\"action\":\"retention_selected\",\"student_count\":1,\"student_ids\":[\"113\"],\"timestamp\":\"2025-11-07 23:05:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:03'),
(1120, 92, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100003\",\"student_name\":\"Michael Johnson\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1121, 93, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100004\",\"student_name\":\"Sarah Brown\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1122, 94, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100005\",\"student_name\":\"David Davis\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1123, 96, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100007\",\"student_name\":\"Christopher Moore\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1124, 97, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100008\",\"student_name\":\"Jessica Taylor\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1125, 99, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100010\",\"student_name\":\"Ashley Thomas\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1126, 101, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100012\",\"student_name\":\"Amanda White\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1127, 102, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100013\",\"student_name\":\"James Harris\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1128, 104, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100015\",\"student_name\":\"Robert Thompson\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1129, 106, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100017\",\"student_name\":\"William Martinez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1130, 108, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100019\",\"student_name\":\"Charles Clark\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1131, 110, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100021\",\"student_name\":\"Thomas Lewis\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1132, 112, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100023\",\"student_name\":\"Richard Walker\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1133, 115, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100026\",\"student_name\":\"Elizabeth Young\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1134, 117, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100028\",\"student_name\":\"Maria King\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1135, 118, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200001\",\"student_name\":\"Alex Garcia\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1136, 119, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200002\",\"student_name\":\"Bianca Santos\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1137, 120, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200003\",\"student_name\":\"Carlos Cruz\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1138, 121, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200004\",\"student_name\":\"Diana Reyes\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1139, 122, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200005\",\"student_name\":\"Eduardo Mendoza\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1140, 123, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200006\",\"student_name\":\"Fatima Torres\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1141, 124, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200007\",\"student_name\":\"Gabriel Gonzalez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1142, 125, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200008\",\"student_name\":\"Hannah Lopez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1143, 126, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200009\",\"student_name\":\"Ivan Martinez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1144, 127, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200010\",\"student_name\":\"Julia Hernandez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1145, 128, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200011\",\"student_name\":\"Kevin Gutierrez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1146, 129, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200012\",\"student_name\":\"Luna Morales\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1147, 130, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200013\",\"student_name\":\"Miguel Jimenez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1148, 131, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200014\",\"student_name\":\"Nina Ruiz\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1149, 132, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200015\",\"student_name\":\"Oscar Diaz\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1150, 133, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200016\",\"student_name\":\"Paula Moreno\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1151, 134, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200017\",\"student_name\":\"Quentin Alvarez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1152, 144, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200018\",\"student_name\":\"Rosa Romero\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1153, 145, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200019\",\"student_name\":\"Sebastian Navarro\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1154, 146, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200020\",\"student_name\":\"Teresa Molina\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1155, 135, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200021\",\"student_name\":\"Ulises Ramos\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1156, 136, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200022\",\"student_name\":\"Valentina Herrera\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1157, 137, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200023\",\"student_name\":\"Walter Medina\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1158, 147, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200024\",\"student_name\":\"Ximena Castillo\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1159, 148, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200025\",\"student_name\":\"Yolanda Vargas\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1160, 149, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200026\",\"student_name\":\"Zachary Castro\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1161, 138, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200027\",\"student_name\":\"Adriana Ortega\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1162, 139, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200028\",\"student_name\":\"Bruno Flores\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1163, 140, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200029\",\"student_name\":\"Camila Silva\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1164, 141, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200030\",\"student_name\":\"Diego Vega\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1165, 142, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200031\",\"student_name\":\"Elena Guerrero\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1166, 143, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200032\",\"student_name\":\"Fernando Pena\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1167, 256, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290002\",\"student_name\":\"Test Two\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1168, 257, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290008\",\"student_name\":\"Test Eight\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1169, 227, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000837212\",\"student_name\":\"Liam Rodriguez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":null,\"academic_year\":\"2028-2029\",\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1170, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2028-2029\",\"incremented_count\":50,\"retained_count\":1,\"retention_flags_reset\":1,\"timestamp\":\"2025-11-07 23:05:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 22:05:15'),
(1171, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 16:44:09'),
(1172, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 12:54:56'),
(1173, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 14:00:34'),
(1174, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 14:02:43'),
(1175, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 14:40:13'),
(1176, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 15:28:55'),
(1177, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:15:18'),
(1178, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:19:14'),
(1179, 231, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:24:38'),
(1180, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:25:16'),
(1181, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-10 17:13:58'),
(1182, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-10 17:20:50'),
(1183, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-10 21:19:24'),
(1184, 239, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 07:04:30'),
(1185, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 07:04:57'),
(1186, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:58:40'),
(1187, 180, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:59:14'),
(1188, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:00:22'),
(1189, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-11 15:00:58');

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
(90, 3, '2025-09-16 21:08:37', NULL, 0),
(91, 3, '2025-09-16 21:08:37', NULL, 0),
(92, 3, '2025-09-16 21:08:37', NULL, 0),
(93, 3, '2025-09-16 21:08:37', NULL, 0),
(94, 3, '2025-09-16 21:08:37', NULL, 0),
(95, 3, '2025-09-16 21:08:37', NULL, 0),
(96, 3, '2025-09-16 21:08:37', NULL, 0),
(97, 3, '2025-09-16 21:08:37', NULL, 0),
(98, 3, '2025-09-16 21:08:37', NULL, 0),
(99, 3, '2025-09-16 21:08:37', NULL, 0),
(100, 3, '2025-09-16 21:09:15', NULL, 0),
(101, 3, '2025-09-16 21:09:15', NULL, 0),
(102, 3, '2025-09-16 21:09:15', NULL, 0),
(103, 3, '2025-09-16 21:09:15', NULL, 0),
(104, 3, '2025-09-16 21:09:15', NULL, 0),
(105, 3, '2025-09-16 21:09:15', NULL, 0),
(106, 3, '2025-09-16 21:09:15', NULL, 0),
(107, 3, '2025-09-16 21:09:15', NULL, 0),
(108, 3, '2025-09-16 21:09:15', NULL, 0),
(109, 3, '2025-09-16 21:09:15', NULL, 0),
(110, 3, '2025-09-16 21:09:15', NULL, 0),
(111, 3, '2025-09-16 21:09:15', NULL, 0),
(112, 3, '2025-09-16 21:09:15', NULL, 0),
(113, 3, '2025-09-16 21:09:15', NULL, 0),
(114, 3, '2025-09-16 21:09:15', NULL, 0),
(115, 3, '2025-09-16 21:09:15', NULL, 0),
(116, 3, '2025-09-16 21:09:15', NULL, 0),
(117, 3, '2025-09-16 21:09:15', NULL, 0),
(118, 3, '2025-09-16 21:09:15', NULL, 0),
(119, 3, '2025-09-16 21:09:15', NULL, 0),
(120, 3, '2025-09-16 21:09:15', NULL, 0),
(121, 3, '2025-09-16 21:09:15', NULL, 0),
(122, 3, '2025-09-16 21:09:15', NULL, 0),
(123, 3, '2025-09-16 21:09:15', NULL, 0),
(124, 3, '2025-09-16 21:09:15', NULL, 0),
(125, 3, '2025-09-16 21:09:15', NULL, 0),
(126, 3, '2025-09-16 21:09:15', NULL, 0),
(127, 3, '2025-09-16 21:09:15', NULL, 0),
(128, 3, '2025-09-16 21:09:15', NULL, 0),
(129, 3, '2025-09-16 21:09:15', NULL, 0),
(130, 3, '2025-09-16 21:09:15', NULL, 0),
(131, 3, '2025-09-16 21:09:15', NULL, 0),
(132, 3, '2025-09-16 21:09:15', NULL, 0),
(133, 3, '2025-09-16 21:09:15', NULL, 0),
(134, 3, '2025-09-16 21:09:15', NULL, 0),
(135, 3, '2025-09-16 21:09:15', NULL, 0),
(136, 3, '2025-09-16 21:09:15', NULL, 0),
(137, 3, '2025-09-16 21:09:15', NULL, 0),
(138, 3, '2025-09-16 21:09:15', NULL, 0),
(139, 3, '2025-09-16 21:09:15', NULL, 0),
(140, 3, '2025-09-16 21:09:15', NULL, 0),
(141, 3, '2025-09-16 21:09:15', NULL, 0),
(142, 3, '2025-09-16 21:09:15', NULL, 0),
(143, 3, '2025-09-16 21:09:15', NULL, 0),
(144, 3, '2025-09-16 21:09:15', NULL, 0),
(145, 3, '2025-09-16 21:09:15', NULL, 0),
(146, 3, '2025-09-16 21:09:15', NULL, 0),
(147, 3, '2025-09-16 21:09:15', NULL, 0),
(148, 3, '2025-09-16 21:09:15', NULL, 0),
(149, 3, '2025-09-16 21:09:15', NULL, 0),
(179, 6, '2025-10-09 18:41:18', NULL, 0),
(180, 6, '2025-10-09 18:45:06', NULL, 0),
(181, 6, '2025-10-09 18:51:34', NULL, 0),
(182, 5, '2025-09-17 23:58:48', NULL, 0),
(188, 7, '2025-09-18 00:00:08', NULL, 0),
(195, 7, '2025-10-27 08:57:30', NULL, 0),
(199, 4, '2025-09-18 00:25:41', NULL, 0),
(200, 4, '2025-09-18 00:25:41', NULL, 0),
(201, 4, '2025-09-18 00:25:41', NULL, 0),
(202, 4, '2025-09-18 00:25:41', NULL, 0),
(203, 4, '2025-09-18 00:25:42', NULL, 0),
(204, 4, '2025-09-18 00:25:42', NULL, 0),
(205, 4, '2025-09-18 00:25:42', NULL, 0),
(206, 4, '2025-09-18 00:25:42', NULL, 0),
(207, 4, '2025-09-18 00:25:42', NULL, 0),
(208, 4, '2025-09-18 00:25:42', NULL, 0),
(209, 4, '2025-09-18 00:25:42', NULL, 0),
(210, 4, '2025-09-18 00:25:42', NULL, 0),
(211, 4, '2025-09-18 00:25:42', NULL, 0),
(212, 4, '2025-09-18 00:25:42', NULL, 0),
(213, 4, '2025-09-18 00:25:42', NULL, 0),
(214, 6, '2025-10-24 04:58:59', NULL, 0),
(215, 4, '2025-10-11 16:49:28', NULL, 0),
(227, 3, '2025-10-11 18:15:04', NULL, 0),
(230, 3, '2025-10-11 18:36:31', NULL, 0),
(231, 7, '2025-10-21 16:35:46', NULL, 0),
(232, 7, '2025-10-23 09:05:40', NULL, 0),
(233, 7, '2025-10-23 09:06:57', NULL, 0),
(234, 7, '2025-10-23 09:07:49', NULL, 0),
(235, 7, '2025-10-23 09:08:57', NULL, 0),
(236, 7, '2025-10-23 09:10:07', NULL, 0),
(237, 7, '2025-10-23 20:38:37', NULL, 0),
(238, 4, '2025-10-23 21:06:41', NULL, 0),
(239, 4, '2025-10-23 21:31:16', NULL, 0),
(240, 7, '2025-10-23 21:36:17', NULL, 0),
(241, 7, '2025-10-23 21:47:35', NULL, 0),
(242, 7, '2025-10-23 21:58:55', NULL, 0),
(243, 7, '2025-10-23 22:03:11', NULL, 0),
(244, 7, '2025-10-23 22:04:30', NULL, 0),
(245, 7, '2025-10-23 22:05:52', NULL, 0),
(246, 5, '2025-10-23 22:07:15', NULL, 0),
(247, 7, '2025-10-23 22:08:05', NULL, 0),
(253, 4, '2025-10-27 08:16:54', NULL, 0),
(254, 4, '2025-10-27 09:47:23', NULL, 0),
(255, 4, '2025-10-27 09:52:48', NULL, 0),
(256, 3, '2025-11-05 06:48:39', NULL, 0),
(257, 3, '2025-11-05 07:56:00', NULL, 0);

-- --------------------------------------------------------

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
-- Indexes for table `clearance_forms`
--
ALTER TABLE `clearance_forms`
  ADD PRIMARY KEY (`clearance_form_id`),
  ADD UNIQUE KEY `unique_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `idx_clearance_forms_user` (`user_id`),
  ADD KEY `idx_clearance_forms_period` (`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_forms_type` (`clearance_type`),
  ADD KEY `idx_clearance_forms_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_form_id` (`clearance_form_id`),
  ADD KEY `idx_clearance_forms_user_academic_semester` (`user_id`,`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_form_progress` (`clearance_form_progress`);

--
-- Indexes for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `idx_clearance_periods_active` (`is_active`),
  ADD KEY `idx_clearance_periods_dates` (`start_date`,`end_date`),
  ADD KEY `idx_clearance_periods_sector` (`sector`),
  ADD KEY `idx_clearance_periods_academic_semester` (`academic_year_id`,`semester_id`),
  ADD KEY `idx_clearance_periods_sector_status` (`sector`,`status`);

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
  ADD KEY `clearance_signatories_ibfk_3` (`actual_user_id`),
  ADD KEY `idx_clearance_signatories_form_action` (`clearance_form_id`,`action`),
  ADD KEY `FK_SignatoryRejectionReason` (`reason_id`);

--
-- Indexes for table `clearance_signatories_new`
--
ALTER TABLE `clearance_signatories_new`
  ADD PRIMARY KEY (`signatory_id`),
  ADD KEY `idx_clearance_signatories_staff` (`staff_id`),
  ADD KEY `idx_clearance_signatories_period` (`clearance_period_id`),
  ADD KEY `idx_clearance_signatories_designation` (`designation_id`),
  ADD KEY `idx_clearance_signatories_department` (`department_id`),
  ADD KEY `idx_clearance_signatories_active` (`is_active`);

--
-- Indexes for table `clearance_signatory_actions`
--
ALTER TABLE `clearance_signatory_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `idx_signatory_actions_form` (`clearance_form_id`),
  ADD KEY `idx_signatory_actions_signatory` (`signatory_id`),
  ADD KEY `idx_signatory_actions_action` (`action`),
  ADD KEY `idx_signatory_actions_grace_period` (`grace_period_ends`),
  ADD KEY `fk_signatory_actions_rejection_reason` (`rejection_reason_id`);

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
  ADD KEY `idx_faculty_department_status` (`department_id`,`employment_status`),
  ADD KEY `idx_faculty_sector` (`sector`);

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
  ADD KEY `idx_students_program_year` (`program_id`,`year_level`),
  ADD KEY `idx_students_sector` (`sector`),
  ADD KEY `idx_students_sector_status` (`sector`);

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
  ADD KEY `idx_users_status` (`account_status`),
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
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
-- AUTO_INCREMENT for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4525;

--
-- AUTO_INCREMENT for table `clearance_signatories_new`
--
ALTER TABLE `clearance_signatories_new`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clearance_signatory_actions`
--
ALTER TABLE `clearance_signatory_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_versions`
--
ALTER TABLE `data_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `signatory_assignments`
--
ALTER TABLE `signatory_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1190;

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
  ADD CONSTRAINT `FK_SignatoryRejectionReason` FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`),
  ADD CONSTRAINT `clearance_signatories_ibfk_1` FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_3` FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `clearance_signatories_new`
--
ALTER TABLE `clearance_signatories_new`
  ADD CONSTRAINT `fk_clearance_signatories_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clearance_signatories_designation` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_signatories_period` FOREIGN KEY (`clearance_period_id`) REFERENCES `clearance_periods` (`period_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_signatories_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_signatory_actions`
--
ALTER TABLE `clearance_signatory_actions`
  ADD CONSTRAINT `fk_signatory_actions_form` FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_signatory_actions_rejection_reason` FOREIGN KEY (`rejection_reason_id`) REFERENCES `rejection_reasons` (`reason_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_signatory_actions_signatory` FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories_new` (`signatory_id`) ON DELETE CASCADE;

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
