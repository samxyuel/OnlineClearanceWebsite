-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql-clrbasedata.alwaysdata.net
-- Generation Time: Nov 25, 2025 at 06:53 AM
-- Server version: 10.11.14-MariaDB
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clrbasedata_online`
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ended_at` datetime DEFAULT NULL COMMENT 'Timestamp when the academic year was ended'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`academic_year_id`, `year`, `is_active`, `created_at`, `updated_at`, `ended_at`) VALUES
(55, '2024-2025', 0, '2025-11-24 17:37:08', '2025-11-24 20:28:47', '2025-11-25 04:28:46');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Clearance forms with sector-based clearance types and progress tracking (College, Senior High School, Faculty)';

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
  `sector` enum('College','Senior High School','Faculty') NOT NULL DEFAULT 'College',
  `period_name` varchar(100) DEFAULT NULL COMMENT 'Auto-generated: "2024-2025 1st Semester"',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `status` enum('Not Started','Ongoing','Paused','Closed') NOT NULL DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Clearance periods with sector support (College, Senior High School, Faculty)';

--
-- Dumping data for table `clearance_periods`
--

INSERT INTO `clearance_periods` (`period_id`, `academic_year_id`, `semester_id`, `sector`, `period_name`, `start_date`, `end_date`, `ended_at`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(147, 55, 114, 'College', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 17:37:34', '2025-11-24 20:25:24'),
(148, 55, 114, 'Senior High School', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 17:37:34', '2025-11-24 20:26:50'),
(149, 55, 114, 'Faculty', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 17:37:35', '2025-11-24 20:26:52'),
(150, 55, 115, 'College', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 20:27:39', '2025-11-24 20:28:26'),
(151, 55, 115, 'Senior High School', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 20:27:40', '2025-11-24 20:28:31'),
(152, 55, 115, 'Faculty', NULL, '2025-11-24', '2025-11-24', NULL, 0, 'Closed', '2025-11-24 20:27:41', '2025-11-24 20:28:33');

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
  `applies_to_departments` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Clearance requirements with sector-based clearance types';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearance_signatories`
--

INSERT INTO `clearance_signatories` (`signatory_id`, `clearance_form_id`, `designation_id`, `actual_user_id`, `action`, `remarks`, `reason_id`, `additional_remarks`, `date_signed`, `created_at`, `updated_at`) VALUES
(4927, 'CF-2025-00001', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:46', '2025-11-21 03:39:46'),
(4928, 'CF-2025-00001', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:46', '2025-11-21 03:39:46'),
(4929, 'CF-2025-00001', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:47', '2025-11-21 03:39:47'),
(4930, 'CF-2025-00002', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:48', '2025-11-21 03:39:48'),
(4931, 'CF-2025-00002', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:49', '2025-11-21 03:39:49'),
(4932, 'CF-2025-00002', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:49', '2025-11-21 03:39:49'),
(4933, 'CF-2025-00003', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:51', '2025-11-21 03:39:51'),
(4934, 'CF-2025-00003', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:51', '2025-11-21 03:39:51'),
(4935, 'CF-2025-00003', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:52', '2025-11-21 03:39:52'),
(4936, 'CF-2025-00004', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:54', '2025-11-21 03:39:54'),
(4937, 'CF-2025-00004', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:54', '2025-11-21 03:39:54'),
(4938, 'CF-2025-00004', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:54', '2025-11-21 03:39:54'),
(4939, 'CF-2025-00005', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:56', '2025-11-21 03:39:56'),
(4940, 'CF-2025-00005', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:57', '2025-11-21 03:39:57'),
(4941, 'CF-2025-00005', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:57', '2025-11-21 03:39:57'),
(4942, 'CF-2025-00006', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:39:59', '2025-11-21 03:39:59'),
(4943, 'CF-2025-00006', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:00', '2025-11-21 03:40:00'),
(4944, 'CF-2025-00006', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:00', '2025-11-21 03:40:00'),
(4945, 'CF-2025-00007', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:02', '2025-11-21 03:40:02'),
(4946, 'CF-2025-00007', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:02', '2025-11-21 03:40:02'),
(4947, 'CF-2025-00007', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:03', '2025-11-21 03:40:03'),
(4948, 'CF-2025-00008', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:05', '2025-11-21 03:40:05'),
(4949, 'CF-2025-00008', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:05', '2025-11-21 03:40:05'),
(4950, 'CF-2025-00008', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:06', '2025-11-21 03:40:06'),
(4951, 'CF-2025-00009', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:07', '2025-11-21 03:40:07'),
(4952, 'CF-2025-00009', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:08', '2025-11-21 03:40:08'),
(4953, 'CF-2025-00009', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:08', '2025-11-21 03:40:08'),
(4954, 'CF-2025-00010', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:10', '2025-11-21 03:40:10'),
(4955, 'CF-2025-00010', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:11', '2025-11-21 03:40:11'),
(4956, 'CF-2025-00010', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:11', '2025-11-21 03:40:11'),
(4957, 'CF-2025-00011', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:13', '2025-11-21 03:40:13'),
(4958, 'CF-2025-00011', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:13', '2025-11-21 03:40:13'),
(4959, 'CF-2025-00011', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:14', '2025-11-21 03:40:14'),
(4960, 'CF-2025-00012', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:16', '2025-11-21 03:40:16'),
(4961, 'CF-2025-00012', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:16', '2025-11-21 03:40:16'),
(4962, 'CF-2025-00012', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:17', '2025-11-21 03:40:17'),
(4963, 'CF-2025-00013', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:18', '2025-11-21 03:40:18'),
(4964, 'CF-2025-00013', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:19', '2025-11-21 03:40:19'),
(4965, 'CF-2025-00013', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:19', '2025-11-21 03:40:19'),
(4966, 'CF-2025-00014', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:21', '2025-11-21 03:40:21'),
(4967, 'CF-2025-00014', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:21', '2025-11-21 03:40:21'),
(4968, 'CF-2025-00014', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:22', '2025-11-21 03:40:22'),
(4969, 'CF-2025-00015', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:24', '2025-11-21 03:40:24'),
(4970, 'CF-2025-00015', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:24', '2025-11-21 03:40:24'),
(4971, 'CF-2025-00015', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:25', '2025-11-21 03:40:25'),
(4972, 'CF-2025-00016', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:26', '2025-11-21 03:40:26'),
(4973, 'CF-2025-00016', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:27', '2025-11-21 03:40:27'),
(4974, 'CF-2025-00016', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:27', '2025-11-21 03:40:27'),
(4975, 'CF-2025-00017', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:29', '2025-11-21 03:40:29'),
(4976, 'CF-2025-00017', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:29', '2025-11-21 03:40:29'),
(4977, 'CF-2025-00017', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:30', '2025-11-21 03:40:30'),
(4978, 'CF-2025-00018', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:32', '2025-11-21 03:40:32'),
(4979, 'CF-2025-00018', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:32', '2025-11-21 03:40:32'),
(4980, 'CF-2025-00018', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:32', '2025-11-21 03:40:32'),
(4981, 'CF-2025-00019', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:34', '2025-11-21 03:40:34'),
(4982, 'CF-2025-00019', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:35', '2025-11-21 03:40:35'),
(4983, 'CF-2025-00019', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:35', '2025-11-21 03:40:35'),
(4984, 'CF-2025-00020', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:37', '2025-11-21 03:40:37'),
(4985, 'CF-2025-00020', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:37', '2025-11-21 03:40:37'),
(4986, 'CF-2025-00020', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:38', '2025-11-21 03:40:38'),
(4987, 'CF-2025-00021', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:39', '2025-11-21 03:40:39'),
(4988, 'CF-2025-00021', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:40', '2025-11-21 03:40:40'),
(4989, 'CF-2025-00021', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:40', '2025-11-21 03:40:40'),
(4990, 'CF-2025-00022', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:42', '2025-11-21 03:40:42'),
(4991, 'CF-2025-00022', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:43', '2025-11-21 03:40:43'),
(4992, 'CF-2025-00022', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:43', '2025-11-21 03:40:43'),
(4993, 'CF-2025-00023', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:45', '2025-11-21 03:40:45'),
(4994, 'CF-2025-00023', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:45', '2025-11-21 03:40:45'),
(4995, 'CF-2025-00023', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:46', '2025-11-21 03:40:46'),
(4996, 'CF-2025-00024', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:47', '2025-11-21 03:40:47'),
(4997, 'CF-2025-00024', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:48', '2025-11-21 03:40:48'),
(4998, 'CF-2025-00024', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:48', '2025-11-21 03:40:48'),
(4999, 'CF-2025-00025', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:50', '2025-11-21 03:40:50'),
(5000, 'CF-2025-00025', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:50', '2025-11-21 03:40:50'),
(5001, 'CF-2025-00025', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:51', '2025-11-21 03:40:51'),
(5002, 'CF-2025-00026', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:53', '2025-11-21 03:40:53'),
(5003, 'CF-2025-00026', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:53', '2025-11-21 03:40:53'),
(5004, 'CF-2025-00026', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:54', '2025-11-21 03:40:54'),
(5005, 'CF-2025-00027', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:55', '2025-11-21 03:40:55'),
(5006, 'CF-2025-00027', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:56', '2025-11-21 03:40:56'),
(5007, 'CF-2025-00027', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:56', '2025-11-21 03:40:56'),
(5008, 'CF-2025-00028', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:58', '2025-11-21 03:40:58'),
(5009, 'CF-2025-00028', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:59', '2025-11-21 03:40:59'),
(5010, 'CF-2025-00028', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:40:59', '2025-11-21 03:40:59'),
(5011, 'CF-2025-00029', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:01', '2025-11-21 03:41:01'),
(5012, 'CF-2025-00029', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:01', '2025-11-21 03:41:01'),
(5013, 'CF-2025-00029', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:02', '2025-11-21 03:41:02'),
(5014, 'CF-2025-00030', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:03', '2025-11-21 03:41:03'),
(5015, 'CF-2025-00030', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:04', '2025-11-21 03:41:04'),
(5016, 'CF-2025-00030', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:04', '2025-11-21 03:41:04'),
(5017, 'CF-2025-00031', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:06', '2025-11-21 03:41:06'),
(5018, 'CF-2025-00031', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:07', '2025-11-21 03:41:07'),
(5019, 'CF-2025-00031', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:07', '2025-11-21 03:41:07'),
(5020, 'CF-2025-00032', 2, NULL, 'Pending', NULL, NULL, NULL, NULL, '2025-11-21 03:41:09', '2025-11-21 03:41:55'),
(5021, 'CF-2025-00032', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:09', '2025-11-21 03:41:09'),
(5022, 'CF-2025-00032', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:10', '2025-11-21 03:41:10'),
(5023, 'CF-2025-00033', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:11', '2025-11-21 03:41:11'),
(5024, 'CF-2025-00033', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:12', '2025-11-21 03:41:12'),
(5025, 'CF-2025-00033', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 03:41:12', '2025-11-21 03:41:12'),
(5026, 'CF-2025-00034', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 06:07:01', '2025-11-21 06:07:01'),
(5027, 'CF-2025-00034', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 06:07:02', '2025-11-21 06:07:02'),
(5028, 'CF-2025-00034', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-21 06:07:02', '2025-11-21 06:07:02'),
(5029, 'CF-2025-00035', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:07', '2025-11-24 10:36:07'),
(5030, 'CF-2025-00035', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:07', '2025-11-24 10:36:07'),
(5031, 'CF-2025-00035', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:08', '2025-11-24 10:36:08'),
(5032, 'CF-2025-00035', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:08', '2025-11-24 10:36:08'),
(5033, 'CF-2025-00035', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:08', '2025-11-24 10:36:08'),
(5034, 'CF-2025-00036', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:10', '2025-11-24 10:36:10'),
(5035, 'CF-2025-00036', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:10', '2025-11-24 10:36:10'),
(5036, 'CF-2025-00036', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:11', '2025-11-24 10:36:11'),
(5037, 'CF-2025-00036', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:11', '2025-11-24 10:36:11'),
(5038, 'CF-2025-00036', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:12', '2025-11-24 10:36:12'),
(5039, 'CF-2025-00037', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:13', '2025-11-24 10:36:13'),
(5040, 'CF-2025-00037', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:14', '2025-11-24 10:36:14'),
(5041, 'CF-2025-00037', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:14', '2025-11-24 10:36:14'),
(5042, 'CF-2025-00037', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:14', '2025-11-24 10:36:14'),
(5043, 'CF-2025-00037', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:15', '2025-11-24 10:36:15'),
(5044, 'CF-2025-00038', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:17', '2025-11-24 10:36:17'),
(5045, 'CF-2025-00038', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:17', '2025-11-24 10:36:17'),
(5046, 'CF-2025-00038', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:17', '2025-11-24 10:36:17'),
(5047, 'CF-2025-00038', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:18', '2025-11-24 10:36:18'),
(5048, 'CF-2025-00038', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:18', '2025-11-24 10:36:18'),
(5049, 'CF-2025-00039', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:20', '2025-11-24 10:36:20'),
(5050, 'CF-2025-00039', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:20', '2025-11-24 10:36:20'),
(5051, 'CF-2025-00039', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:21', '2025-11-24 10:36:21'),
(5052, 'CF-2025-00039', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:21', '2025-11-24 10:36:21'),
(5053, 'CF-2025-00039', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:21', '2025-11-24 10:36:21'),
(5054, 'CF-2025-00040', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:23', '2025-11-24 10:36:23'),
(5055, 'CF-2025-00040', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:24', '2025-11-24 10:36:24'),
(5056, 'CF-2025-00040', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:24', '2025-11-24 10:36:24'),
(5057, 'CF-2025-00040', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:24', '2025-11-24 10:36:24'),
(5058, 'CF-2025-00040', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:25', '2025-11-24 10:36:25'),
(5059, 'CF-2025-00041', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:27', '2025-11-24 10:36:27'),
(5060, 'CF-2025-00041', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:27', '2025-11-24 10:36:27'),
(5061, 'CF-2025-00041', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:27', '2025-11-24 10:36:27'),
(5062, 'CF-2025-00041', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:28', '2025-11-24 10:36:28'),
(5063, 'CF-2025-00041', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:28', '2025-11-24 10:36:28'),
(5064, 'CF-2025-00042', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:30', '2025-11-24 10:36:30'),
(5065, 'CF-2025-00042', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:30', '2025-11-24 10:36:30'),
(5066, 'CF-2025-00042', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:30', '2025-11-24 10:36:30'),
(5067, 'CF-2025-00042', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:31', '2025-11-24 10:36:31'),
(5068, 'CF-2025-00042', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:31', '2025-11-24 10:36:31'),
(5069, 'CF-2025-00043', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:33', '2025-11-24 10:36:33'),
(5070, 'CF-2025-00043', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:33', '2025-11-24 10:36:33'),
(5071, 'CF-2025-00043', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:33', '2025-11-24 10:36:33'),
(5072, 'CF-2025-00043', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:34', '2025-11-24 10:36:34'),
(5073, 'CF-2025-00043', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:34', '2025-11-24 10:36:34'),
(5074, 'CF-2025-00044', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:36', '2025-11-24 10:36:36'),
(5075, 'CF-2025-00044', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:36', '2025-11-24 10:36:36'),
(5076, 'CF-2025-00044', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:37', '2025-11-24 10:36:37'),
(5077, 'CF-2025-00044', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:37', '2025-11-24 10:36:37'),
(5078, 'CF-2025-00044', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:38', '2025-11-24 10:36:38'),
(5079, 'CF-2025-00045', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:39', '2025-11-24 10:36:39'),
(5080, 'CF-2025-00045', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:40', '2025-11-24 10:36:40'),
(5081, 'CF-2025-00045', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:40', '2025-11-24 10:36:40'),
(5082, 'CF-2025-00045', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:40', '2025-11-24 10:36:40'),
(5083, 'CF-2025-00045', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:41', '2025-11-24 10:36:41'),
(5084, 'CF-2025-00046', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:43', '2025-11-24 10:36:43'),
(5085, 'CF-2025-00046', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:43', '2025-11-24 10:36:43'),
(5086, 'CF-2025-00046', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:43', '2025-11-24 10:36:43'),
(5087, 'CF-2025-00046', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:44', '2025-11-24 10:36:44'),
(5088, 'CF-2025-00046', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:44', '2025-11-24 10:36:44'),
(5089, 'CF-2025-00047', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:46', '2025-11-24 10:36:46'),
(5090, 'CF-2025-00047', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:46', '2025-11-24 10:36:46'),
(5091, 'CF-2025-00047', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:47', '2025-11-24 10:36:47'),
(5092, 'CF-2025-00047', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:47', '2025-11-24 10:36:47'),
(5093, 'CF-2025-00047', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:48', '2025-11-24 10:36:48'),
(5094, 'CF-2025-00048', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:50', '2025-11-24 10:36:50'),
(5095, 'CF-2025-00048', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:50', '2025-11-24 10:36:50'),
(5096, 'CF-2025-00048', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:50', '2025-11-24 10:36:50'),
(5097, 'CF-2025-00048', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:51', '2025-11-24 10:36:51'),
(5098, 'CF-2025-00048', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:51', '2025-11-24 10:36:51'),
(5099, 'CF-2025-00049', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:53', '2025-11-24 10:36:53'),
(5100, 'CF-2025-00049', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:53', '2025-11-24 10:36:53'),
(5101, 'CF-2025-00049', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:54', '2025-11-24 10:36:54'),
(5102, 'CF-2025-00049', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:54', '2025-11-24 10:36:54'),
(5103, 'CF-2025-00049', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:54', '2025-11-24 10:36:54'),
(5104, 'CF-2025-00050', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:56', '2025-11-24 10:36:56'),
(5105, 'CF-2025-00050', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:56', '2025-11-24 10:36:56'),
(5106, 'CF-2025-00050', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:57', '2025-11-24 10:36:57'),
(5107, 'CF-2025-00050', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:57', '2025-11-24 10:36:57'),
(5108, 'CF-2025-00050', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:58', '2025-11-24 10:36:58'),
(5109, 'CF-2025-00051', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:36:59', '2025-11-24 10:36:59'),
(5110, 'CF-2025-00051', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:00', '2025-11-24 10:37:00'),
(5111, 'CF-2025-00051', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:00', '2025-11-24 10:37:00'),
(5112, 'CF-2025-00051', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:01', '2025-11-24 10:37:01'),
(5113, 'CF-2025-00051', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:01', '2025-11-24 10:37:01'),
(5114, 'CF-2025-00052', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:03', '2025-11-24 10:37:03'),
(5115, 'CF-2025-00052', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:03', '2025-11-24 10:37:03'),
(5116, 'CF-2025-00052', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:03', '2025-11-24 10:37:03'),
(5117, 'CF-2025-00052', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:04', '2025-11-24 10:37:04'),
(5118, 'CF-2025-00052', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:04', '2025-11-24 10:37:04'),
(5119, 'CF-2025-00053', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:06', '2025-11-24 10:37:06'),
(5120, 'CF-2025-00053', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:06', '2025-11-24 10:37:06'),
(5121, 'CF-2025-00053', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:07', '2025-11-24 10:37:07'),
(5122, 'CF-2025-00053', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:07', '2025-11-24 10:37:07'),
(5123, 'CF-2025-00053', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:08', '2025-11-24 10:37:08'),
(5124, 'CF-2025-00054', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:09', '2025-11-24 10:37:09'),
(5125, 'CF-2025-00054', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:10', '2025-11-24 10:37:10'),
(5126, 'CF-2025-00054', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:10', '2025-11-24 10:37:10'),
(5127, 'CF-2025-00054', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:11', '2025-11-24 10:37:11'),
(5128, 'CF-2025-00054', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:11', '2025-11-24 10:37:11'),
(5129, 'CF-2025-00055', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:13', '2025-11-24 10:37:13'),
(5130, 'CF-2025-00055', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:13', '2025-11-24 10:37:13'),
(5131, 'CF-2025-00055', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:13', '2025-11-24 10:37:13'),
(5132, 'CF-2025-00055', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:14', '2025-11-24 10:37:14'),
(5133, 'CF-2025-00055', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:14', '2025-11-24 10:37:14'),
(5134, 'CF-2025-00056', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:16', '2025-11-24 10:37:16'),
(5135, 'CF-2025-00056', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:16', '2025-11-24 10:37:16'),
(5136, 'CF-2025-00056', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:17', '2025-11-24 10:37:17'),
(5137, 'CF-2025-00056', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:17', '2025-11-24 10:37:17'),
(5138, 'CF-2025-00056', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:17', '2025-11-24 10:37:17'),
(5139, 'CF-2025-00057', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:19', '2025-11-24 10:37:19'),
(5140, 'CF-2025-00057', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:19', '2025-11-24 10:37:19'),
(5141, 'CF-2025-00057', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:20', '2025-11-24 10:37:20'),
(5142, 'CF-2025-00057', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:20', '2025-11-24 10:37:20'),
(5143, 'CF-2025-00057', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:21', '2025-11-24 10:37:21'),
(5144, 'CF-2025-00058', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:22', '2025-11-24 10:37:22'),
(5145, 'CF-2025-00058', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:23', '2025-11-24 10:37:23'),
(5146, 'CF-2025-00058', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:23', '2025-11-24 10:37:23'),
(5147, 'CF-2025-00058', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:24', '2025-11-24 10:37:24'),
(5148, 'CF-2025-00058', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:24', '2025-11-24 10:37:24'),
(5149, 'CF-2025-00059', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:26', '2025-11-24 10:37:26'),
(5150, 'CF-2025-00059', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:26', '2025-11-24 10:37:26'),
(5151, 'CF-2025-00059', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:26', '2025-11-24 10:37:26'),
(5152, 'CF-2025-00059', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:27', '2025-11-24 10:37:27'),
(5153, 'CF-2025-00059', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:27', '2025-11-24 10:37:27'),
(5154, 'CF-2025-00060', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:29', '2025-11-24 10:37:29'),
(5155, 'CF-2025-00060', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:29', '2025-11-24 10:37:29'),
(5156, 'CF-2025-00060', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:30', '2025-11-24 10:37:30'),
(5157, 'CF-2025-00060', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:30', '2025-11-24 10:37:30'),
(5158, 'CF-2025-00060', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:30', '2025-11-24 10:37:30'),
(5159, 'CF-2025-00061', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:32', '2025-11-24 10:37:32'),
(5160, 'CF-2025-00061', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:32', '2025-11-24 10:37:32'),
(5161, 'CF-2025-00061', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:33', '2025-11-24 10:37:33'),
(5162, 'CF-2025-00061', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:33', '2025-11-24 10:37:33'),
(5163, 'CF-2025-00061', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:34', '2025-11-24 10:37:34'),
(5164, 'CF-2025-00062', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:36', '2025-11-24 10:37:36'),
(5165, 'CF-2025-00062', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:36', '2025-11-24 10:37:36'),
(5166, 'CF-2025-00062', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:37', '2025-11-24 10:37:37'),
(5167, 'CF-2025-00062', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:37', '2025-11-24 10:37:37'),
(5168, 'CF-2025-00062', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:37', '2025-11-24 10:37:37'),
(5169, 'CF-2025-00063', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:39', '2025-11-24 10:37:39'),
(5170, 'CF-2025-00063', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:40', '2025-11-24 10:37:40'),
(5171, 'CF-2025-00063', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:40', '2025-11-24 10:37:40'),
(5172, 'CF-2025-00063', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:40', '2025-11-24 10:37:40'),
(5173, 'CF-2025-00063', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:41', '2025-11-24 10:37:41'),
(5174, 'CF-2025-00064', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:42', '2025-11-24 10:37:42'),
(5175, 'CF-2025-00064', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:43', '2025-11-24 10:37:43'),
(5176, 'CF-2025-00064', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:43', '2025-11-24 10:37:43'),
(5177, 'CF-2025-00064', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:44', '2025-11-24 10:37:44'),
(5178, 'CF-2025-00064', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:44', '2025-11-24 10:37:44'),
(5179, 'CF-2025-00065', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:45', '2025-11-24 10:37:45'),
(5180, 'CF-2025-00065', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:46', '2025-11-24 10:37:46'),
(5181, 'CF-2025-00065', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:46', '2025-11-24 10:37:46'),
(5182, 'CF-2025-00065', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:47', '2025-11-24 10:37:47'),
(5183, 'CF-2025-00065', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:47', '2025-11-24 10:37:47'),
(5184, 'CF-2025-00066', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:49', '2025-11-24 10:37:49'),
(5185, 'CF-2025-00066', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:49', '2025-11-24 10:37:49'),
(5186, 'CF-2025-00066', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:49', '2025-11-24 10:37:49'),
(5187, 'CF-2025-00066', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:50', '2025-11-24 10:37:50'),
(5188, 'CF-2025-00066', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:50', '2025-11-24 10:37:50'),
(5189, 'CF-2025-00067', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:52', '2025-11-24 10:37:52'),
(5190, 'CF-2025-00067', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:52', '2025-11-24 10:37:52'),
(5191, 'CF-2025-00067', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:53', '2025-11-24 10:37:53'),
(5192, 'CF-2025-00067', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:53', '2025-11-24 10:37:53'),
(5193, 'CF-2025-00067', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:53', '2025-11-24 10:37:53'),
(5194, 'CF-2025-00068', 2, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:55', '2025-11-24 10:37:55'),
(5195, 'CF-2025-00068', 16, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:56', '2025-11-24 10:37:56'),
(5196, 'CF-2025-00068', 3, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:56', '2025-11-24 10:37:56'),
(5197, 'CF-2025-00068', 1, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:56', '2025-11-24 10:37:56'),
(5198, 'CF-2025-00068', 9, NULL, 'Unapplied', NULL, NULL, NULL, NULL, '2025-11-24 10:37:57', '2025-11-24 10:37:57');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('LCA1234P', 271, 'Part Time - Full Load', NULL, 'Faculty', '2025-11-21 05:49:39', '2025-11-21 05:49:39'),
('LCA2001P', 179, 'Full Time', 44, 'Faculty', '2025-10-09 17:39:33', '2025-10-09 18:41:18'),
('LCA2002P', 180, 'Full Time', 46, 'Faculty', '2025-10-09 18:34:49', '2025-10-09 18:45:06'),
('LCA2003P', 181, 'Full Time', 45, 'Faculty', '2025-10-09 18:51:34', '2025-10-09 18:51:34'),
('LCA2004P', 214, 'Part Time - Full Load', 50, 'Faculty', '2025-10-09 17:04:13', '2025-10-24 04:58:59'),
('LCA2031P', 255, 'Part Time', 50, 'Faculty', '2025-10-27 09:52:48', '2025-10-27 09:52:48'),
('LCA2326P', 253, 'Part Time - Full Load', 50, 'Faculty', '2025-10-27 08:16:54', '2025-10-27 09:42:05'),
('LCA2444P', 258, 'Full Time', 47, 'Faculty', '2025-11-13 21:31:46', '2025-11-13 21:31:46'),
('LCA2631P', 254, 'Full Time', 50, 'Faculty', '2025-10-27 09:47:23', '2025-10-27 09:47:23'),
('LCA4623P', 266, 'Full Time', 50, 'Faculty', '2025-11-13 23:39:16', '2025-11-13 23:39:16'),
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
-- Table structure for table `form_distribution_jobs`
--

CREATE TABLE `form_distribution_jobs` (
  `job_id` int(11) NOT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'NULL = entire sector, specific ID = that department only',
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `total_users` int(11) NOT NULL DEFAULT 0,
  `processed_users` int(11) NOT NULL DEFAULT 0,
  `current_batch_start` int(11) NOT NULL DEFAULT 0,
  `forms_created` int(11) NOT NULL DEFAULT 0,
  `forms_skipped` int(11) NOT NULL DEFAULT 0,
  `signatories_assigned` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Queue table for form distribution jobs processed by cron-job.org';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL COMMENT 'Admin, Staff, Student, Faculty',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Settings for each sector clearance type';

--
-- Dumping data for table `sector_clearance_settings`
--

INSERT INTO `sector_clearance_settings` (`setting_id`, `clearance_type`, `include_program_head`, `required_first_enabled`, `required_first_designation_id`, `required_last_enabled`, `required_last_designation_id`, `created_at`, `updated_at`) VALUES
(1, 'College', 0, 1, 2, 1, 1, '2025-09-14 14:59:46', '2025-11-24 10:18:13'),
(2, 'Senior High School', 1, 1, 2, 1, 1, '2025-09-14 14:59:46', '2025-11-14 01:25:55'),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sector-based signatory assignments for clearance forms';

--
-- Dumping data for table `sector_signatory_assignments`
--

INSERT INTO `sector_signatory_assignments` (`assignment_id`, `clearance_type`, `user_id`, `designation_id`, `is_program_head`, `department_id`, `is_required_first`, `is_required_last`, `is_active`, `created_at`, `updated_at`) VALUES
(34, 'College', 179, 8, 1, NULL, 0, 0, 0, '2025-09-18 00:15:09', '2025-10-28 09:17:36'),
(35, 'Senior High School', 180, 8, 1, NULL, 0, 0, 0, '2025-09-18 00:15:09', '2025-11-14 01:25:16'),
(36, 'Faculty', 181, 8, 1, NULL, 0, 0, 0, '2025-09-18 00:15:09', '2025-10-23 22:11:04'),
(37, 'College', 189, 1, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:36'),
(38, 'College', 183, 14, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(39, 'College', 186, 3, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(40, 'College', 185, 16, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(41, 'College', 184, 15, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(42, 'College', 192, 4, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:33'),
(43, 'College', 190, 2, 0, NULL, 0, 0, 0, '2025-09-18 00:16:59', '2025-10-28 09:17:32'),
(44, 'Senior High School', 189, 1, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(45, 'Senior High School', 183, 14, 0, NULL, 0, 0, 0, '2025-09-18 00:17:07', '2025-11-14 01:25:26'),
(46, 'Senior High School', 186, 3, 0, NULL, 0, 0, 0, '2025-09-18 00:17:07', '2025-11-14 01:25:28'),
(47, 'Senior High School', 185, 16, 0, NULL, 0, 0, 1, '2025-09-18 00:17:07', '2025-09-18 00:17:07'),
(48, 'Senior High School', 184, 15, 0, NULL, 0, 0, 0, '2025-09-18 00:17:07', '2025-11-14 01:25:25'),
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
(82, 'College', 246, 9, 0, NULL, 0, 0, 1, '2025-10-28 09:18:58', '2025-10-28 09:18:58'),
(83, 'College', 233, 15, 0, NULL, 0, 0, 0, '2025-11-24 09:59:26', '2025-11-24 10:00:47'),
(84, 'College', 236, 16, 0, NULL, 0, 0, 1, '2025-11-24 09:59:27', '2025-11-24 09:59:27'),
(85, 'College', 235, 3, 0, NULL, 0, 0, 1, '2025-11-24 09:59:28', '2025-11-24 09:59:28');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ended_at` datetime DEFAULT NULL COMMENT 'Timestamp when the semester was ended'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `academic_year_id`, `is_active`, `is_generation`, `created_at`, `updated_at`, `ended_at`) VALUES
(114, '1st', 55, 0, 0, '2025-11-24 17:37:08', '2025-11-24 20:27:01', '2025-11-25 04:27:01'),
(115, '2nd', 55, 0, 0, '2025-11-24 17:37:09', '2025-11-24 20:28:47', '2025-11-25 04:28:46');

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
('LCA1111P', 269, 8, 'Program Head', 49, NULL, 1, '2025-11-14 09:01:59', '2025-11-14 09:01:59'),
('LCA1234P', 271, 13, 'Regular Staff', NULL, 'Part Time - Full Load', 1, '2025-11-21 05:49:37', '2025-11-21 05:49:37'),
('LCA2001P', 179, 8, 'Program Head', 44, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:41:18'),
('LCA2002P', 180, 8, 'Program Head', 46, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:45:06'),
('LCA2003P', 181, 8, 'Program Head', 45, 'Full Time', 1, '2025-09-17 23:58:48', '2025-10-09 18:51:34'),
('LCA2004P', 214, 8, 'Program Head', 50, 'Part Time - Full Load', 1, '2025-10-09 17:04:13', '2025-10-24 04:58:59'),
('LCA2444P', 258, 8, 'Program Head', 47, 'Full Time', 1, '2025-11-13 21:31:46', '2025-11-13 21:31:46'),
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
('LCA4034P', 247, 6, 'Regular Staff', NULL, NULL, 1, '2025-10-23 22:08:05', '2025-10-23 22:08:05'),
('LCA4235P', 261, 12, 'Regular Staff', NULL, NULL, 1, '2025-11-13 22:38:18', '2025-11-13 22:38:18'),
('LCA4324P', 259, 4, 'Regular Staff', NULL, NULL, 1, '2025-11-13 22:25:12', '2025-11-13 23:10:13'),
('LCA4453P', 260, 16, 'Regular Staff', NULL, NULL, 1, '2025-11-13 22:26:55', '2025-11-13 22:26:55');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Students with sector assignment (College, Senior High School)';

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `program_id`, `department_id`, `sector`, `section`, `year_level`, `created_at`, `updated_at`, `retain_year_level_next_year`, `retain_year_level_for_next_year`) VALUES
('02000000090', 272, 2, 44, 'College', '1', '2nd Year', '2025-11-21 06:06:50', '2025-11-24 17:37:09', 0, 0),
('02000100001', 90, 1, 44, 'College', '4/1-1', '2nd Year', '2025-09-16 20:53:58', '2025-11-24 17:37:09', 0, 0),
('02000100002', 91, 1, 44, 'College', '4/1-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:10', 0, 0),
('02000100003', 92, 1, 44, 'College', '3/1-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:10', 0, 0),
('02000100004', 93, 1, 44, 'College', '3/1-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:10', 0, 0),
('02000100005', 94, 1, 44, 'College', '2/1-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:11', 0, 0),
('02000100006', 95, 2, 44, 'College', '4/1-2', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:11', 0, 0),
('02000100007', 96, 2, 44, 'College', '3/1-2', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:11', 0, 0),
('02000100008', 97, 2, 44, 'College', '2/1-2', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:12', 0, 0),
('02000100009', 98, 3, 44, 'College', '4/1-3', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:12', 0, 0),
('02000100010', 99, 3, 44, 'College', '3/1-3', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:12', 0, 0),
('02000100011', 100, 4, 46, 'College', '4/2-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:13', 0, 0),
('02000100012', 101, 4, 46, 'College', '3/2-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:13', 0, 0),
('02000100013', 102, 4, 46, 'College', '2/2-1', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:14', 0, 0),
('02000100014', 103, 5, 46, 'College', '4/2-2', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:14', 0, 0),
('02000100015', 104, 5, 46, 'College', '3/2-2', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:14', 0, 0),
('02000100016', 105, 6, 46, 'College', '4/2-3', '2nd Year', '2025-09-16 20:53:59', '2025-11-24 17:37:15', 0, 0),
('02000100017', 106, 6, 46, 'College', '3/2-3', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:15', 0, 0),
('02000100018', 107, 7, 45, 'College', '4/3-1', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:15', 0, 0),
('02000100019', 108, 7, 45, 'College', '3/3-1', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:16', 0, 0),
('02000100020', 109, 8, 45, 'College', '4/3-2', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:16', 0, 0),
('02000100021', 110, 8, 45, 'College', '3/3-2', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:16', 0, 0),
('02000100022', 111, 9, 45, 'College', '4/3-3', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:17', 0, 0),
('02000100023', 112, 9, 45, 'College', '3/3-3', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:17', 0, 0),
('02000100024', 113, 9, 45, 'College', '2/3-3', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:17', 0, 0),
('02000100025', 114, 10, 45, 'College', '4/3-4', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:18', 0, 0),
('02000100026', 115, 10, 45, 'College', '3/3-4', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:18', 0, 0),
('02000100027', 116, 11, 45, 'College', '4/3-5', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:18', 0, 0),
('02000100028', 117, 11, 45, 'College', '3/3-5', '2nd Year', '2025-09-16 20:54:00', '2025-11-24 17:37:19', 0, 0),
('02000183124', 230, 2, 44, 'College', '4/1-1', '2nd Year', '2025-10-11 18:36:31', '2025-11-24 17:37:19', 0, 0),
('0200018811', 267, 1, 44, 'College', '1', '2nd Year', '2025-11-14 08:44:10', '2025-11-24 17:37:19', 0, 0),
('02000200001', 118, 12, 47, 'Senior High School', '12-ABM-1', '4th Year', '2025-09-16 20:54:00', '2025-11-14 01:24:53', 0, 0),
('02000200002', 119, 12, 47, 'Senior High School', '12-ABM-1', '4th Year', '2025-09-16 20:54:00', '2025-11-14 01:24:53', 0, 0),
('02000200003', 120, 12, 47, 'Senior High School', '12-ABM-2', '4th Year', '2025-09-16 20:54:00', '2025-11-14 01:24:53', 0, 0),
('02000200004', 121, 12, 47, 'Senior High School', '12-ABM-2', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200005', 122, 12, 47, 'Senior High School', '11-ABM-1', '4th Year', '2025-09-16 20:54:01', '2025-11-20 19:10:23', 0, 0),
('02000200006', 123, 13, 47, 'Senior High School', '12-STEM-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200007', 124, 13, 47, 'Senior High School', '12-STEM-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200008', 125, 13, 47, 'Senior High School', '12-STEM-2', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200009', 126, 13, 47, 'Senior High School', '11-STEM-1', '4th Year', '2025-09-16 20:54:01', '2025-11-20 19:10:23', 0, 0),
('02000200010', 127, 13, 47, 'Senior High School', '11-STEM-1', '4th Year', '2025-09-16 20:54:01', '2025-11-20 19:10:24', 0, 0),
('02000200011', 128, 14, 47, 'Senior High School', '12-HUMSS-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200012', 129, 14, 47, 'Senior High School', '12-HUMSS-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200013', 130, 14, 47, 'Senior High School', '12-HUMSS-2', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200014', 131, 14, 47, 'Senior High School', '11-HUMSS-1', '4th Year', '2025-09-16 20:54:01', '2025-11-20 19:10:24', 0, 0),
('02000200015', 132, 15, 47, 'Senior High School', '12-GA-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200016', 133, 15, 47, 'Senior High School', '12-GA-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200017', 134, 15, 47, 'Senior High School', '11-GA-1', '4th Year', '2025-09-16 20:54:01', '2025-11-20 19:10:24', 0, 0),
('02000200018', 144, 16, 48, 'Senior High School', '12-DA-1', '4th Year', '2025-09-16 20:57:54', '2025-11-14 01:24:53', 0, 0),
('02000200019', 145, 16, 48, 'Senior High School', '12-DA-1', '4th Year', '2025-09-16 20:57:54', '2025-11-14 01:24:53', 0, 0),
('02000200020', 146, 16, 48, 'Senior High School', '11-DA-1', '4th Year', '2025-09-16 20:57:54', '2025-11-20 19:10:25', 0, 0),
('02000200021', 135, 17, 48, 'Senior High School', '12-IT-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200022', 136, 17, 48, 'Senior High School', '12-IT-1', '4th Year', '2025-09-16 20:54:01', '2025-11-14 01:24:53', 0, 0),
('02000200023', 137, 17, 48, 'Senior High School', '11-IT-1', '4th Year', '2025-09-16 20:54:02', '2025-11-20 19:10:25', 0, 0),
('02000200024', 147, 18, 48, 'Senior High School', '12-TO-1', '4th Year', '2025-09-16 20:57:55', '2025-11-14 01:24:53', 0, 0),
('02000200025', 148, 18, 48, 'Senior High School', '12-TO-1', '4th Year', '2025-09-16 20:57:55', '2025-11-14 01:24:53', 0, 0),
('02000200026', 149, 18, 48, 'Senior High School', '11-TO-1', '4th Year', '2025-09-16 20:57:55', '2025-11-20 19:10:25', 0, 0),
('02000200027', 138, 19, 48, 'Senior High School', '12-RC-1', '4th Year', '2025-09-16 20:54:02', '2025-11-14 01:24:53', 0, 0),
('02000200028', 139, 19, 48, 'Senior High School', '12-RC-1', '4th Year', '2025-09-16 20:54:02', '2025-11-14 01:24:53', 0, 0),
('02000200029', 140, 19, 48, 'Senior High School', '11-RC-1', '4th Year', '2025-09-16 20:54:02', '2025-11-20 19:10:26', 0, 0),
('02000200030', 141, 20, 48, 'Senior High School', '12-CA-1', '4th Year', '2025-09-16 20:54:02', '2025-11-14 01:24:53', 0, 0),
('02000200031', 142, 20, 48, 'Senior High School', '12-CA-1', '4th Year', '2025-09-16 20:54:02', '2025-11-14 01:24:53', 0, 0),
('02000200032', 143, 20, 48, 'Senior High School', '11-CA-1', '4th Year', '2025-09-16 20:54:02', '2025-11-20 19:10:26', 0, 0),
('02000211322', 268, 13, 47, 'Senior High School', '1', '4th Year', '2025-11-14 08:55:02', '2025-11-24 10:51:41', 0, 0),
('02000250002', 276, 2, 44, 'College', '1', '3rd Year', '2025-11-24 21:11:01', '2025-11-24 21:11:01', 0, 0),
('02000260002', 275, 2, 44, 'College', '4', '1st Year', '2025-11-24 21:11:00', '2025-11-24 21:11:00', 0, 0),
('02000270002', 274, 2, 44, 'College', '3', '2nd Year', '2025-11-24 21:10:59', '2025-11-24 21:10:59', 0, 0),
('02000280002', 273, 2, 44, 'College', '1', '3rd Year', '2025-11-24 21:10:57', '2025-11-24 21:10:57', 0, 0),
('02000284043', 270, 2, 44, 'College', '1', '2nd Year', '2025-11-20 17:31:48', '2025-11-24 17:37:20', 0, 0),
('02000290001', 277, 1, 44, 'College', '1/1-1', '1st Year', '2025-11-24 21:15:14', '2025-11-24 21:15:14', 0, 0),
('02000290002', 256, 2, 44, 'College', '1', '2nd Year', '2025-11-05 06:48:39', '2025-11-24 17:37:20', 0, 0),
('02000290008', 257, 8, 45, 'College', '1', '2nd Year', '2025-11-05 07:56:00', '2025-11-24 17:37:20', 0, 0),
('02000837212', 227, 12, 47, NULL, '1/1-2', '4th Year', '2025-10-11 18:15:04', '2025-11-14 01:24:54', 0, 0);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(257, '02000290008', '$2y$10$HFK9jt0UDEff9HH0BGkEPO7vJP55tRB4UoWtOyGhvV5cvUyOdOuZy', NULL, 'Test', 'Eight', 'College', NULL, 'active', 1, NULL, '2025-11-05 07:56:00', '2025-11-05 07:56:00'),
(258, 'LCA2444P', '$2y$10$LA.TMaBnkm2lvBl5g1Z1P.aNbRhdp2EnwjdDCe/xCTX2cuB8ppkCC', 'LCA2444P@placeholder.local', 'Randal', 'Cake', '', NULL, 'active', 1, NULL, '2025-11-13 21:31:46', '2025-11-13 21:31:46'),
(259, 'LCA4324P', '$2y$10$/v1eEaCG3cNFMS6MA5Y2xetG9eOxWWC8I2SJqFy22WzPiLKwnjjI.', 'LCA4324P@placeholder.local', 'MultiRole', 'Test', 'Staff', NULL, 'active', 1, NULL, '2025-11-13 22:25:12', '2025-11-13 23:10:13'),
(260, 'LCA4453P', '$2y$10$bY0yZz35htY7Cxefpbohx.5voxy5NnI/jQupt8Iijk6b2Zc.6cWPS', 'LCA4453P@placeholder.local', 'MutliRole Staff2', 'Test', '', NULL, 'active', 1, NULL, '2025-11-13 22:26:55', '2025-11-13 22:26:55'),
(261, 'LCA4235P', '$2y$10$6Xi4sWPQtGl7MhqgcskMR.RHRheedufqkKraH6O4qekyGY0DF.y4.', 'LCA4235P@placeholder.local', 'MultiRole Staff3', 'Test', '', NULL, 'active', 1, NULL, '2025-11-13 22:38:18', '2025-11-13 22:38:18'),
(266, 'LCA4623P', '$2y$10$T.DXfGzVBXJHViz7bIIy5uD4xJHjFmkAYoiz5dk1FzVtd/IOD640a', 'LCA4623P@placeholder.local', 'MutliDept Faculty', 'Test', NULL, NULL, 'active', 1, NULL, '2025-11-13 23:39:16', '2025-11-13 23:39:16'),
(267, '0200018811', '$2y$10$UNdcJfhj41pG4Nj1aO.cfOqRfZpwLU4nhsqmVb6CPj8nwGFMLF.sy', 'doy.188311@lucena.sti.edu.ph', 'Bo', 'Doy', 'null', 'null', 'active', 1, NULL, '2025-11-14 08:44:10', '2025-11-14 08:44:10'),
(268, '02000211322', '$2y$10$e80deE01Ae9mMczZRLsyx.2P7bwh9o2Mr1TJnzgtcD7LShAWqyUhu', 'doy.211322@lucena.sti.edu.ph', 'Ba', 'Doy', 'null', 'null', 'active', 1, NULL, '2025-11-14 08:55:02', '2025-11-14 08:55:02'),
(269, 'LCA1111P', '$2y$10$uEmP9bpv1iMHDxBQwcXjeuGa/yFwhxDL6h/PWVIwXyKBPUa2k3SPK', 'LCA1111P@placeholder.local', 'Ha', 'Ha', '', NULL, 'active', 1, NULL, '2025-11-14 09:01:59', '2025-11-14 09:01:59'),
(270, '02000284043', '$2y$12$lNaxHBuXHA4aTZhoEXMgTuwwEHKOCJGDJS9Z3wqmv.nPyFrA7dmsK', 'sanvictores@gmail.com', 'Andrew', 'San Victores', 'null', 'null', 'active', 1, NULL, '2025-11-20 17:31:47', '2025-11-20 17:31:47'),
(271, 'LCA1234P', '$2y$10$ju1pEtDweTGPhRyrMThRC.FWZ7PsDcre2aALNOyYuQvdobAnXIUHq', 'hahaha.lca2222p@lucena.sti.edu.ph', 'Wow', 'Hahaha', '', NULL, 'active', 1, NULL, '2025-11-21 05:49:33', '2025-11-21 05:49:33'),
(272, '02000000090', '$2y$10$uCWyksPK9LQPrgqdQGpii.86FPVnDE/gF54P/ouMZqv22ipw4PT9K', 'bi@gmail.com', 'Bi', 'Bi', 'null', 'null', 'active', 1, NULL, '2025-11-21 06:06:48', '2025-11-21 06:06:48'),
(273, '02000280002', '$2y$12$O.6urd6nGmTpRHeaF9FlhunBn/oyDULSzrkMFAJ52Q3QJnVa8DFja', NULL, 'Test', 'asd', 'xyz', NULL, 'active', 1, NULL, '2025-11-24 21:10:57', '2025-11-24 21:10:57'),
(274, '02000270002', '$2y$12$Exbwfq5Xm0oTRncfxuya1uNXFdR7MQvpgRhbA8bXxCtNJb7mIJ.Um', NULL, 'Test', 'dsa', 'zxc', NULL, 'active', 1, NULL, '2025-11-24 21:10:58', '2025-11-24 21:10:58'),
(275, '02000260002', '$2y$12$0iau35X96STxMvTEznzVleDYPMPbc35PwvANL5cilx8PIOGAedxTi', NULL, 'Test', 'sad', 'vbn', NULL, 'active', 1, NULL, '2025-11-24 21:10:59', '2025-11-24 21:10:59'),
(276, '02000250002', '$2y$12$UvRrpI4bLotXaVbTMm8OzetfrF3p.42ONaumhymhADl.1GQc.YZbm', NULL, 'Test', 'awd', 'cxz', NULL, 'active', 1, NULL, '2025-11-24 21:11:00', '2025-11-24 21:11:00'),
(277, '02000290001', '$2y$12$wq7TA2qEjvcoVAcNbvVsy.rvKca268m0rFN2GrWvXLOyiGtBOHtXm', NULL, 'Test', 'One', 'College', NULL, 'active', 1, NULL, '2025-11-24 21:15:14', '2025-11-24 21:15:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT 'e.g., "Login", "Clearance Apply", "Profile Update"',
  `activity_details` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1189, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-11 15:00:58'),
(1190, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 21:10:19'),
(1191, 258, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 21:31:46'),
(1192, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 21:49:22'),
(1193, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:11:01'),
(1194, 259, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:25:12'),
(1195, 1, 'Staff Registered', '{\"target_user_id\":259,\"employee_id\":\"LCA4324P\",\"designation\":\"MIS\\/IT\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:25:12'),
(1196, 1, 'staff_registered', '{\"employee_id\":\"LCA4324P\",\"name\":null,\"designation\":\"MIS\\/IT\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:25:12'),
(1197, 260, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:26:55'),
(1198, 1, 'Staff Registered', '{\"target_user_id\":260,\"employee_id\":\"LCA4453P\",\"designation\":\"Clinic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:26:55'),
(1199, 1, 'staff_registered', '{\"employee_id\":\"LCA4453P\",\"name\":null,\"designation\":\"Clinic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:26:55'),
(1200, 261, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:38:18'),
(1201, 1, 'Staff Registered', '{\"target_user_id\":261,\"employee_id\":\"LCA4235P\",\"designation\":\"Accountant\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:38:18'),
(1202, 1, 'staff_registered', '{\"employee_id\":\"LCA4235P\",\"name\":null,\"designation\":\"Accountant\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 22:38:18'),
(1203, 259, 'user_updated', '{\"details\":\"User account updated\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 23:10:13'),
(1204, 1, 'Staff Updated', '{\"target_user_id\":259,\"updated_fields\":[\"type\",\"employeeId\",\"lastName\",\"firstName\",\"middleName\",\"staffPosition\",\"editCustomPosition\",\"programHeadCategory\",\"staffEmail\",\"staffContact\",\"facultyEmploymentStatus\",\"facultyEmployeeNumber\",\"first_name\",\"last_name\",\"middle_name\",\"role_id\",\"is_also_faculty\",\"assignedDesignations\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 23:10:13'),
(1205, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 23:12:45'),
(1210, 266, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 23:39:16'),
(1211, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:21:16'),
(1212, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:21:29'),
(1213, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:26:19'),
(1214, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:49:42'),
(1215, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:49:45'),
(1216, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:49:59'),
(1217, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:06:18'),
(1218, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:09'),
(1219, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:13'),
(1220, 94, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100005\",\"student_name\":\"David Davis\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1221, 97, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100008\",\"student_name\":\"Jessica Taylor\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1222, 102, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100013\",\"student_name\":\"James Harris\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1223, 113, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100024\",\"student_name\":\"Susan Hall\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1224, 118, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200001\",\"student_name\":\"Alex Garcia\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1225, 119, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200002\",\"student_name\":\"Bianca Santos\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1226, 120, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200003\",\"student_name\":\"Carlos Cruz\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1227, 121, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200004\",\"student_name\":\"Diana Reyes\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1228, 122, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200005\",\"student_name\":\"Eduardo Mendoza\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1229, 123, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200006\",\"student_name\":\"Fatima Torres\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1230, 124, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200007\",\"student_name\":\"Gabriel Gonzalez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1231, 125, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200008\",\"student_name\":\"Hannah Lopez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1232, 126, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200009\",\"student_name\":\"Ivan Martinez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1233, 127, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200010\",\"student_name\":\"Julia Hernandez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1234, 128, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200011\",\"student_name\":\"Kevin Gutierrez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1235, 129, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200012\",\"student_name\":\"Luna Morales\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1236, 130, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200013\",\"student_name\":\"Miguel Jimenez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1237, 131, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200014\",\"student_name\":\"Nina Ruiz\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1238, 132, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200015\",\"student_name\":\"Oscar Diaz\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1239, 133, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200016\",\"student_name\":\"Paula Moreno\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1240, 134, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200017\",\"student_name\":\"Quentin Alvarez\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1241, 144, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200018\",\"student_name\":\"Rosa Romero\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1242, 145, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200019\",\"student_name\":\"Sebastian Navarro\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1243, 146, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200020\",\"student_name\":\"Teresa Molina\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1244, 135, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200021\",\"student_name\":\"Ulises Ramos\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1245, 136, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200022\",\"student_name\":\"Valentina Herrera\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1246, 137, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200023\",\"student_name\":\"Walter Medina\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1247, 147, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200024\",\"student_name\":\"Ximena Castillo\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1248, 148, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200025\",\"student_name\":\"Yolanda Vargas\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1249, 149, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200026\",\"student_name\":\"Zachary Castro\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1250, 138, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200027\",\"student_name\":\"Adriana Ortega\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1251, 139, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200028\",\"student_name\":\"Bruno Flores\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1252, 140, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200029\",\"student_name\":\"Camila Silva\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1253, 141, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200030\",\"student_name\":\"Diego Vega\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1254, 142, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200031\",\"student_name\":\"Elena Guerrero\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1255, 143, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200032\",\"student_name\":\"Fernando Pena\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:53'),
(1256, 256, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290002\",\"student_name\":\"Test Two\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:54'),
(1257, 257, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290008\",\"student_name\":\"Test Eight\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:54'),
(1258, 227, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000837212\",\"student_name\":\"Liam Rodriguez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":null,\"academic_year\":\"2029-2030\",\"timestamp\":\"2025-11-14 02:24:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:54'),
(1259, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2029-2030\",\"incremented_count\":39,\"retained_count\":0,\"retention_flags_reset\":0,\"timestamp\":\"2025-11-14 02:24:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:24:54'),
(1260, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:26:59'),
(1261, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:27:08'),
(1262, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 02:16:14'),
(1263, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 02:16:41'),
(1264, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00271\",\"signatory_id\":4641}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 02:16:48'),
(1265, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 02:16:57'),
(1266, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 02:17:08'),
(1267, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:02:43'),
(1268, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:02:58'),
(1269, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:03:10'),
(1270, 260, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:29:12'),
(1271, 260, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:32:44'),
(1272, 260, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":2,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:32:49'),
(1273, 260, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:37:27'),
(1274, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:37:33'),
(1275, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00271\",\"signatory_id\":4643}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:37:41'),
(1276, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00271\",\"signatory_id\":4644}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:37:41'),
(1277, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:37:49'),
(1278, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:38:10'),
(1279, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:38:24'),
(1280, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:44:57'),
(1281, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:45:19'),
(1282, 179, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:47:18'),
(1283, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:50:08'),
(1284, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:50:30'),
(1285, 246, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":9,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:06'),
(1286, 246, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":9,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:09'),
(1287, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:19'),
(1288, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:28'),
(1289, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00271\",\"signatory_id\":4642}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:33'),
(1290, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:35'),
(1291, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:13:56'),
(1292, 260, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":1,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:14:22'),
(1293, 260, 'Signatory Action', '{\"target_user_id\":230,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:14:25'),
(1294, 260, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:14:32'),
(1295, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:14:40'),
(1296, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:15:14'),
(1297, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:15:44'),
(1298, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:21:03'),
(1299, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:21:18'),
(1300, 260, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":2,\"affected_user_count\":3,\"affected_rows\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:21:55'),
(1301, 260, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":2,\"affected_user_count\":20,\"affected_rows\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:22:05'),
(1302, 260, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":2,\"affected_user_count\":20,\"affected_rows\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:22:25'),
(1303, 260, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":2,\"affected_user_count\":4,\"affected_rows\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:22:42'),
(1304, 260, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:23:11'),
(1305, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:23:13'),
(1306, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:29:50'),
(1307, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:30:08'),
(1308, 179, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":8,\"affected_user_count\":4,\"affected_rows\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:30:26'),
(1309, 179, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":8,\"affected_user_count\":6,\"affected_rows\":6}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:30:38'),
(1310, 179, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":8,\"affected_user_count\":3,\"affected_rows\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:30:48'),
(1311, 179, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":8,\"affected_user_count\":5,\"affected_rows\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:30:55'),
(1312, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:31:03'),
(1313, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:33:52'),
(1314, 246, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":9,\"affected_user_count\":4,\"affected_rows\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:34:08'),
(1315, 246, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":9,\"affected_user_count\":20,\"affected_rows\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:34:14'),
(1316, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:42:30'),
(1317, 261, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:43:03'),
(1318, 261, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":12,\"affected_user_count\":20,\"affected_rows\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:43:52'),
(1319, 261, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":12,\"affected_user_count\":3,\"affected_rows\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:02'),
(1320, 261, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:06'),
(1321, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:19'),
(1322, 246, 'Signatory Action', '{\"target_user_id\":258,\"designation_id\":9,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:27'),
(1323, 246, 'Signatory Action', '{\"target_user_id\":209,\"designation_id\":9,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:30'),
(1324, 246, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":9,\"affected_user_count\":20,\"affected_rows\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:44:34'),
(1325, 246, 'Bulk Signatory Action', '{\"action\":\"Rejected\",\"designation_id\":9,\"affected_user_count\":2,\"affected_rows\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:55:21'),
(1326, 246, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:55:33'),
(1327, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:55:55'),
(1328, 260, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:56:21'),
(1329, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:32:41'),
(1330, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:34:24'),
(1331, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:40:04'),
(1332, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:40:11'),
(1333, 267, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:45:59'),
(1334, 267, 'Signatory Apply', '{\"form_id\":\"CF-2025-00332\",\"signatory_id\":4918}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:46:07'),
(1335, 231, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:46:37'),
(1336, 267, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:47:26'),
(1337, 231, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":2,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:47:59'),
(1338, 267, 'Signatory Apply', '{\"form_id\":\"CF-2025-00332\",\"signatory_id\":4920}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:48:03'),
(1339, 267, 'Signatory Apply', '{\"form_id\":\"CF-2025-00332\",\"signatory_id\":4921}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:48:04'),
(1340, 179, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:48:29'),
(1341, 179, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:48:45'),
(1342, 182, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:50:08'),
(1343, 182, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":9,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:50:19'),
(1344, 182, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":9,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:50:45'),
(1345, 267, 'Signatory Apply', '{\"form_id\":\"CF-2025-00332\",\"signatory_id\":4919}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:51:07'),
(1346, 232, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:51:51'),
(1347, 232, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":1,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:52:03'),
(1348, 232, 'Signatory Action', '{\"target_user_id\":267,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:53:01'),
(1349, 267, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:55:28'),
(1350, 268, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:55:35'),
(1351, 268, 'Signatory Apply', '{\"form_id\":\"CF-2025-00333\",\"signatory_id\":4922}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 08:58:23'),
(1352, 269, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:01:59'),
(1353, 1, 'Staff Registered', '{\"target_user_id\":269,\"employee_id\":\"LCA1111P\",\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:01:59'),
(1354, 1, 'staff_registered', '{\"employee_id\":\"LCA1111P\",\"name\":null,\"designation\":\"Program Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:01:59'),
(1355, 1, 'program_head_assigned', '{\"user_id\":269,\"department_ids\":[49,51,48],\"transfer\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:01:59'),
(1356, 269, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:03:58'),
(1357, 258, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:04:36'),
(1358, 231, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":2,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:08:11'),
(1359, 231, 'Bulk Signatory Action', '{\"action\":\"Approved\",\"designation_id\":2,\"affected_user_count\":3,\"affected_rows\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:08:30'),
(1360, 268, 'Signatory Apply', '{\"form_id\":\"CF-2025-00333\",\"signatory_id\":4923}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:08:42'),
(1361, 268, 'Signatory Apply', '{\"form_id\":\"CF-2025-00333\",\"signatory_id\":4924}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:08:43'),
(1362, 268, 'Signatory Apply', '{\"form_id\":\"CF-2025-00333\",\"signatory_id\":4926}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:08:45'),
(1363, 258, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":8,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:15:41'),
(1364, 258, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":8,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:16:02'),
(1365, 259, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:17:06'),
(1366, 259, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":4,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:17:22'),
(1367, 234, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:17:54'),
(1368, 234, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":4,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:18:02'),
(1369, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:19:20'),
(1370, 260, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":16,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:19:32'),
(1371, 236, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:20:14'),
(1372, 236, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":16,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:20:21'),
(1373, 268, 'Signatory Apply', '{\"form_id\":\"CF-2025-00333\",\"signatory_id\":4925}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 09:20:57'),
(1374, 232, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":1,\"action\":\"Rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:21:48'),
(1375, 232, 'Signatory Action', '{\"target_user_id\":268,\"designation_id\":1,\"action\":\"Approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:22:01'),
(1376, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 09:57:12'),
(1377, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.59.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 17:29:24'),
(1378, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.8.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 17:29:47'),
(1379, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.88.107', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-20 17:30:44'),
(1380, 1, 'logout', '{\"details\":\"User logged out\"}', '10.1.88.107', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-20 17:32:17'),
(1381, 270, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.88.107', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-20 17:33:14'),
(1382, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.11.73', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 17:37:06'),
(1383, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.30.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 18:12:54'),
(1384, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.81.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 18:32:27'),
(1385, 113, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100024\",\"student_name\":\"Susan Hall\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:22\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:22'),
(1386, 267, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"0200018811\",\"student_name\":\"Bo Doy\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:23\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:23'),
(1387, 122, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200005\",\"student_name\":\"Eduardo Mendoza\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:23\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:23'),
(1388, 126, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200009\",\"student_name\":\"Ivan Martinez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:23\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:23'),
(1389, 127, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200010\",\"student_name\":\"Julia Hernandez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:24\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:24'),
(1390, 131, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200014\",\"student_name\":\"Nina Ruiz\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:24\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:24'),
(1391, 134, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200017\",\"student_name\":\"Quentin Alvarez\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:24\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:24'),
(1392, 146, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200020\",\"student_name\":\"Teresa Molina\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:25\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:25'),
(1393, 137, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200023\",\"student_name\":\"Walter Medina\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:25\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:25'),
(1394, 149, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200026\",\"student_name\":\"Zachary Castro\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:26\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:26'),
(1395, 140, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200029\",\"student_name\":\"Camila Silva\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:26\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:26'),
(1396, 143, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000200032\",\"student_name\":\"Fernando Pena\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:26\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:26'),
(1397, 268, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000211322\",\"student_name\":\"Ba Doy\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:27\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:27'),
(1398, 270, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000284043\",\"student_name\":\"Andrew San Victores\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-20 19:10:27\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:27'),
(1399, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2024-2025\",\"incremented_count\":14,\"retained_count\":0,\"retention_flags_reset\":0,\"timestamp\":\"2025-11-20 19:10:27\"}', '10.1.27.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 19:10:28'),
(1400, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 19:11:37'),
(1401, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 19:31:39'),
(1402, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 19:56:40'),
(1403, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.3.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 21:11:03'),
(1404, 270, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.59.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 21:12:10'),
(1405, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 22:10:36'),
(1406, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 22:10:41'),
(1407, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 22:12:02'),
(1408, 260, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 22:12:20'),
(1409, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 22:26:05'),
(1410, 260, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 23:28:31'),
(1411, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 23:29:01'),
(1412, 179, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 01:21:39'),
(1413, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 01:21:50'),
(1414, 230, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 03:41:22'),
(1415, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 03:41:31'),
(1416, 230, 'Signatory Apply', '{\"form_id\":\"CF-2025-00032\",\"signatory_id\":5020}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 03:41:55'),
(1417, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 03:58:14'),
(1418, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:21:44'),
(1419, 230, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:23:08'),
(1420, 231, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.49.85', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:24:38'),
(1421, 231, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:38:55'),
(1422, 246, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:43:25'),
(1423, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.19.123', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-21 04:44:53'),
(1424, 1, 'logout', '{\"details\":\"User logged out\"}', '10.1.19.123', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-21 04:46:01'),
(1425, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:46:05'),
(1426, 230, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.19.123', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-21 04:46:53'),
(1427, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 04:48:03'),
(1428, 230, 'logout', '{\"details\":\"User logged out\"}', '10.1.30.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-21 05:24:37'),
(1429, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.2.77', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:40:44'),
(1430, 179, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.11.73', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:44:22'),
(1431, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 05:47:06'),
(1432, 271, 'user_created', '{\"details\":\"User account created\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 05:49:36'),
(1433, 1, 'Staff Registered', '{\"target_user_id\":271,\"employee_id\":\"LCA1234P\",\"designation\":\"Academic Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 05:49:41'),
(1434, 1, 'staff_registered', '{\"employee_id\":\"LCA1234P\",\"name\":null,\"designation\":\"Academic Head\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 05:49:44'),
(1435, 271, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 05:50:59'),
(1436, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 06:05:15'),
(1437, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 07:15:19'),
(1438, 230, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.87.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:38:52'),
(1439, 230, 'logout', '{\"details\":\"User logged out\"}', '10.1.11.73', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:41:38');
INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1440, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.11.73', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:41:47'),
(1441, 272, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000000090\",\"student_name\":\"Bi Bi\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2025-2026\",\"timestamp\":\"2025-11-24 09:52:42\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:52:42'),
(1442, 267, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"0200018811\",\"student_name\":\"Bo Doy\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2025-2026\",\"timestamp\":\"2025-11-24 09:52:43\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:52:43'),
(1443, 268, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000211322\",\"student_name\":\"Ba Doy\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2025-2026\",\"timestamp\":\"2025-11-24 09:52:43\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:52:43'),
(1444, 270, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000284043\",\"student_name\":\"Andrew San Victores\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2025-2026\",\"timestamp\":\"2025-11-24 09:52:43\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:52:43'),
(1445, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2025-2026\",\"incremented_count\":4,\"retained_count\":0,\"retention_flags_reset\":0,\"timestamp\":\"2025-11-24 09:52:44\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:52:44'),
(1446, 1, 'logout', '{\"details\":\"User logged out\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:03:44'),
(1447, 230, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:03:56'),
(1448, 230, 'logout', '{\"details\":\"User logged out\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:04:35'),
(1449, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:04:39'),
(1450, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.87.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:10:51'),
(1451, 270, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:13:59'),
(1452, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.50.206', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:26:30'),
(1453, 118, 'logout', '{\"details\":\"User logged out\"}', '10.1.8.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:28:17'),
(1454, 270, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.8.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:28:45'),
(1455, 179, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.19.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:31:21'),
(1456, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:32:31'),
(1457, 179, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:33:20'),
(1458, 270, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:34:26'),
(1459, 270, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:34:35'),
(1460, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:34:50'),
(1461, 270, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:37:35'),
(1462, 270, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:38:29'),
(1463, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:38:47'),
(1464, 270, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:39:10'),
(1465, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:43:19'),
(1466, 270, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.30.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:45:34'),
(1467, 272, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000000090\",\"student_name\":\"Bi Bi\",\"old_year_level\":\"2nd Year\",\"new_year_level\":\"3rd Year\",\"sector\":\"College\",\"academic_year\":\"2026-2027\",\"timestamp\":\"2025-11-24 10:51:41\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:51:41'),
(1468, 267, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"0200018811\",\"student_name\":\"Bo Doy\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2026-2027\",\"timestamp\":\"2025-11-24 10:51:41\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:51:41'),
(1469, 268, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000211322\",\"student_name\":\"Ba Doy\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"Senior High School\",\"academic_year\":\"2026-2027\",\"timestamp\":\"2025-11-24 10:51:41\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:51:41'),
(1470, 270, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000284043\",\"student_name\":\"Andrew San Victores\",\"old_year_level\":\"3rd Year\",\"new_year_level\":\"4th Year\",\"sector\":\"College\",\"academic_year\":\"2026-2027\",\"timestamp\":\"2025-11-24 10:51:42\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:51:42'),
(1471, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2026-2027\",\"incremented_count\":4,\"retained_count\":0,\"retention_flags_reset\":0,\"timestamp\":\"2025-11-24 10:51:42\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 10:51:42'),
(1472, 230, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.87.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 12:34:44'),
(1473, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.6.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 13:31:41'),
(1474, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.50.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 13:41:55'),
(1475, 231, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.63.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 13:50:03'),
(1476, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.81.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:51:19'),
(1477, 179, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.21.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 14:52:53'),
(1478, 179, 'logout', '{\"details\":\"User logged out\"}', '10.1.30.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 15:27:00'),
(1479, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.30.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 15:27:05'),
(1480, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.63.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 15:35:39'),
(1481, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.91.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 15:44:02'),
(1482, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.61.156', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 16:48:05'),
(1483, 272, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000000090\",\"student_name\":\"Bi Bi\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:09\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:09'),
(1484, 90, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100001\",\"student_name\":\"John Doe\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:09\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:09'),
(1485, 91, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100002\",\"student_name\":\"Jane Smith\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:10\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:10'),
(1486, 92, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100003\",\"student_name\":\"Michael Johnson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:10\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:10'),
(1487, 93, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100004\",\"student_name\":\"Sarah Brown\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:10\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:10'),
(1488, 94, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100005\",\"student_name\":\"David Davis\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:11\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:11'),
(1489, 95, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100006\",\"student_name\":\"Emily Wilson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:11\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:11'),
(1490, 96, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100007\",\"student_name\":\"Christopher Moore\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:11\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:12'),
(1491, 97, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100008\",\"student_name\":\"Jessica Taylor\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:12\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:12'),
(1492, 98, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100009\",\"student_name\":\"Matthew Anderson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:12\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:12'),
(1493, 99, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100010\",\"student_name\":\"Ashley Thomas\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:13\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:13'),
(1494, 100, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100011\",\"student_name\":\"Daniel Jackson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:13\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:13'),
(1495, 101, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100012\",\"student_name\":\"Amanda White\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:13\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:13'),
(1496, 102, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100013\",\"student_name\":\"James Harris\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:14\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:14'),
(1497, 103, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100014\",\"student_name\":\"Jennifer Martin\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:14\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:14'),
(1498, 104, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100015\",\"student_name\":\"Robert Thompson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:14\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:14'),
(1499, 105, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100016\",\"student_name\":\"Lisa Garcia\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:15\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:15'),
(1500, 106, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100017\",\"student_name\":\"William Martinez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:15\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:15'),
(1501, 107, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100018\",\"student_name\":\"Michelle Robinson\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:15\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:15'),
(1502, 108, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100019\",\"student_name\":\"Charles Clark\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:16\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:16'),
(1503, 109, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100020\",\"student_name\":\"Patricia Rodriguez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:16\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:16'),
(1504, 110, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100021\",\"student_name\":\"Thomas Lewis\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:16\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:16'),
(1505, 111, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100022\",\"student_name\":\"Barbara Lee\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:17\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:17'),
(1506, 112, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100023\",\"student_name\":\"Richard Walker\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:17\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:17'),
(1507, 113, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100024\",\"student_name\":\"Susan Hall\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:17\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:17'),
(1508, 114, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100025\",\"student_name\":\"Joseph Allen\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:18\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:18'),
(1509, 115, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100026\",\"student_name\":\"Elizabeth Young\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:18\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:18'),
(1510, 116, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100027\",\"student_name\":\"Christopher Hernandez\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:18\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:19'),
(1511, 117, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000100028\",\"student_name\":\"Maria King\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:19\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:19'),
(1512, 230, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000183124\",\"student_name\":\"Leonard Venci Yap\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:19\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:19'),
(1513, 267, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"0200018811\",\"student_name\":\"Bo Doy\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:20\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:20'),
(1514, 270, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000284043\",\"student_name\":\"Andrew San Victores\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:20\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:20'),
(1515, 256, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290002\",\"student_name\":\"Test Two\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:20\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:20'),
(1516, 257, 'year_level_incremented', '{\"action\":\"year_level_incremented\",\"student_id\":\"02000290008\",\"student_name\":\"Test Eight\",\"old_year_level\":\"1st Year\",\"new_year_level\":\"2nd Year\",\"sector\":\"College\",\"academic_year\":\"2024-2025\",\"timestamp\":\"2025-11-24 17:37:21\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:21'),
(1517, 1, 'year_level_bulk_increment', '{\"action\":\"year_level_bulk_increment\",\"academic_year\":\"2024-2025\",\"incremented_count\":34,\"retained_count\":0,\"retention_flags_reset\":0,\"timestamp\":\"2025-11-24 17:37:21\"}', '10.1.53.83', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:37:21'),
(1518, 1, 'login', '{\"details\":\"User logged in successfully\"}', '10.1.2.77', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 17:48:47'),
(1519, 1, 'logout', '{\"details\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 20:25:58'),
(1520, 1, 'login', '{\"details\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 20:26:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_department_assignments`
--

CREATE TABLE `user_department_assignments` (
  `department_assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `sector_id` int(11) DEFAULT NULL COMMENT 'For sector-wide assignments',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Primary department assignment',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_department_assignments`
--

INSERT INTO `user_department_assignments` (`department_assignment_id`, `user_id`, `department_id`, `sector_id`, `is_primary`, `is_active`) VALUES
(28, 266, 50, NULL, 1, 1),
(29, 266, 44, NULL, 0, 1),
(30, 179, 44, NULL, 1, 1),
(31, 180, 46, NULL, 0, 1),
(32, 181, 45, NULL, 0, 1),
(33, 214, 50, NULL, 0, 1),
(34, 258, 47, NULL, 0, 1),
(35, 182, 50, NULL, 0, 1),
(36, 183, 50, NULL, 0, 1),
(37, 184, 50, NULL, 0, 1),
(38, 185, 50, NULL, 0, 1),
(39, 186, 50, NULL, 0, 1),
(40, 187, 50, NULL, 0, 1),
(41, 188, 50, NULL, 0, 1),
(42, 189, 50, NULL, 0, 1),
(43, 190, 50, NULL, 0, 1),
(44, 191, 50, NULL, 0, 1),
(45, 192, 44, NULL, 0, 1),
(46, 193, 50, NULL, 0, 1),
(47, 194, 50, NULL, 0, 1),
(48, 195, 50, NULL, 0, 1),
(49, 196, 50, NULL, 0, 1),
(50, 197, 50, NULL, 0, 1),
(51, 255, 50, NULL, 0, 1),
(52, 253, 50, NULL, 0, 1),
(53, 254, 50, NULL, 0, 1),
(54, 199, 50, NULL, 0, 1),
(55, 200, 50, NULL, 0, 1),
(56, 201, 50, NULL, 0, 1),
(57, 202, 50, NULL, 0, 1),
(58, 203, 50, NULL, 0, 1),
(59, 204, 50, NULL, 0, 1),
(60, 205, 50, NULL, 0, 1),
(61, 206, 50, NULL, 0, 1),
(62, 207, 50, NULL, 0, 1),
(63, 208, 50, NULL, 0, 1),
(64, 209, 50, NULL, 0, 1),
(65, 210, 50, NULL, 0, 1),
(66, 211, 50, NULL, 0, 1),
(67, 212, 50, NULL, 0, 1),
(68, 213, 50, NULL, 0, 1),
(69, 215, 50, NULL, 0, 1),
(70, 238, 50, NULL, 0, 1),
(71, 239, 50, NULL, 0, 1),
(72, 214, 44, NULL, 0, 1),
(73, 255, 44, NULL, 0, 1),
(74, 253, 45, NULL, 0, 1),
(75, 254, 45, NULL, 0, 1),
(76, 266, 45, NULL, 0, 1),
(77, 199, 46, NULL, 0, 1),
(78, 200, 46, NULL, 0, 1),
(79, 201, 46, NULL, 0, 1),
(80, 202, 45, NULL, 0, 1),
(81, 203, 45, NULL, 0, 1),
(82, 204, 44, NULL, 0, 1),
(83, 205, 45, NULL, 0, 1),
(84, 206, 46, NULL, 0, 1),
(85, 207, 44, NULL, 0, 1),
(86, 208, 44, NULL, 0, 1),
(87, 209, 46, NULL, 0, 1),
(88, 210, 44, NULL, 0, 1),
(89, 211, 44, NULL, 0, 1),
(90, 212, 44, NULL, 0, 1),
(91, 213, 45, NULL, 0, 1),
(92, 215, 46, NULL, 0, 1),
(93, 238, 46, NULL, 0, 1),
(94, 239, 46, NULL, 0, 1),
(95, 269, 49, NULL, 1, 1),
(96, 269, 51, NULL, 0, 1),
(97, 269, 48, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_designation_assignments`
--

CREATE TABLE `user_designation_assignments` (
  `designation_assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Primary Designation Assignment',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_designation_assignments`
--

INSERT INTO `user_designation_assignments` (`designation_assignment_id`, `user_id`, `designation_id`, `is_primary`, `is_active`) VALUES
(3, 260, 2, 1, 1),
(4, 260, 1, 0, 1),
(5, 261, 12, 1, 1),
(6, 261, 17, 0, 1),
(7, 261, 14, 0, 1),
(8, 259, 4, 1, 1),
(9, 259, 11, 0, 1),
(10, 179, 8, 1, 1),
(11, 179, 18, 0, 1),
(12, 180, 18, 0, 1),
(13, 181, 18, 0, 1),
(14, 199, 18, 0, 1),
(15, 200, 18, 0, 1),
(16, 201, 18, 0, 1),
(17, 202, 18, 0, 1),
(18, 203, 18, 0, 1),
(19, 204, 18, 0, 1),
(20, 205, 18, 0, 1),
(21, 206, 18, 0, 1),
(22, 207, 18, 0, 1),
(23, 208, 18, 0, 1),
(24, 209, 18, 0, 1),
(25, 210, 18, 0, 1),
(26, 211, 18, 0, 1),
(27, 212, 18, 0, 1),
(28, 213, 18, 0, 1),
(29, 214, 18, 0, 1),
(30, 215, 18, 0, 1),
(31, 238, 18, 0, 1),
(32, 239, 18, 0, 1),
(33, 253, 18, 0, 1),
(34, 254, 18, 0, 1),
(35, 255, 18, 0, 1),
(36, 258, 18, 0, 1),
(37, 266, 18, 0, 1),
(38, 269, 8, 1, 1),
(39, 271, 13, 1, 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(257, 3, '2025-11-05 07:56:00', NULL, 0),
(258, 6, '2025-11-13 21:31:46', NULL, 0),
(259, 7, '2025-11-13 23:10:13', NULL, 0),
(260, 7, '2025-11-13 22:26:55', NULL, 0),
(261, 7, '2025-11-13 22:38:18', NULL, 0),
(266, 4, '2025-11-13 23:39:16', NULL, 0),
(267, 3, '2025-11-14 08:44:10', NULL, 0),
(268, 3, '2025-11-14 08:55:02', NULL, 0),
(269, 6, '2025-11-14 09:01:59', NULL, 0),
(270, 3, '2025-11-20 17:31:47', NULL, 0),
(271, 7, '2025-11-21 05:49:35', NULL, 0),
(272, 3, '2025-11-21 06:06:49', NULL, 0),
(273, 3, '2025-11-24 21:10:57', NULL, 0),
(274, 3, '2025-11-24 21:10:58', NULL, 0),
(275, 3, '2025-11-24 21:10:59', NULL, 0),
(276, 3, '2025-11-24 21:11:00', NULL, 0),
(277, 3, '2025-11-24 21:15:14', NULL, 0);

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
  ADD KEY `idx_academic_years_year` (`year`),
  ADD KEY `idx_academic_years_ended_at` (`ended_at`);

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
-- Indexes for table `form_distribution_jobs`
--
ALTER TABLE `form_distribution_jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_clearance_type` (`clearance_type`,`academic_year_id`,`semester_id`,`department_id`);

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
  ADD KEY `idx_semesters_academic_year` (`academic_year_id`),
  ADD KEY `idx_semesters_ended_at` (`ended_at`);

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
  ADD KEY `idx_students_program_year` (`program_id`,`year_level`),
  ADD KEY `idx_students_sector` (`sector`),
  ADD KEY `idx_students_sector_status` (`sector`);

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
-- Indexes for table `user_department_assignments`
--
ALTER TABLE `user_department_assignments`
  ADD PRIMARY KEY (`department_assignment_id`),
  ADD UNIQUE KEY `unique_staff_department` (`user_id`,`department_id`),
  ADD KEY `idx_staff_assignments` (`user_id`,`is_active`),
  ADD KEY `idx_department_assignments` (`department_id`,`is_active`),
  ADD KEY `idx_sector_assignments` (`sector_id`,`is_active`);

--
-- Indexes for table `user_designation_assignments`
--
ALTER TABLE `user_designation_assignments`
  ADD PRIMARY KEY (`designation_assignment_id`),
  ADD KEY `fk_UDesA_users` (`user_id`),
  ADD KEY `fk_UDesA_` (`designation_id`);

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
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `clearance_periods`
--
ALTER TABLE `clearance_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clearance_signatories`
--
ALTER TABLE `clearance_signatories`
  MODIFY `signatory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5873;

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
-- AUTO_INCREMENT for table `form_distribution_jobs`
--
ALTER TABLE `form_distribution_jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1521;

--
-- AUTO_INCREMENT for table `user_department_assignments`
--
ALTER TABLE `user_department_assignments`
  MODIFY `department_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `user_designation_assignments`
--
ALTER TABLE `user_designation_assignments`
  MODIFY `designation_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

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
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_department_assignments`
--
ALTER TABLE `user_department_assignments`
  ADD CONSTRAINT `fk_UDepA_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_UDepA_sector` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_UDepA_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_designation_assignments`
--
ALTER TABLE `user_designation_assignments`
  ADD CONSTRAINT `fk_UDesA_` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_UDesA_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
