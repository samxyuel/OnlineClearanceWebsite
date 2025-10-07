-- =====================================================================
-- Online Clearance System Database Tables - Version 2
-- Database: online_clearance_db_ver2 (must be created manually first)
-- Description: Sector-based clearance period system for Students and Faculty
-- Sectors: Senior High School, College, and Faculty
-- =====================================================================

-- =====================================================================
-- CORE REFERENCE TABLES
-- =====================================================================

-- 1. Sectors table
CREATE TABLE `sectors` (
  `sector_id` int(11) NOT NULL AUTO_INCREMENT,
  `sector_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sector_id`),
  UNIQUE KEY `sector_name` (`sector_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles table
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL COMMENT 'Admin, School Administrator, Program Head, Regular Staff, Students, Faculty',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`),
  KEY `idx_roles_name` (`role_name`),
  KEY `idx_roles_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Designations table
CREATE TABLE `designations` (
  `designation_id` int(11) NOT NULL AUTO_INCREMENT,
  `designation_name` varchar(100) NOT NULL COMMENT 'Registrar, Cashier, Librarian, etc.',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`designation_id`),
  KEY `idx_designations_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Departments table
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(10) DEFAULT NULL,
  `department_type` enum('College','Senior High School','Faculty') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sector_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code` (`department_code`),
  UNIQUE KEY `uq_department_sector` (`department_name`,`sector_id`),
  KEY `idx_departments_type` (`department_type`),
  KEY `idx_departments_active` (`is_active`),
  KEY `idx_sector_id` (`sector_id`),
  CONSTRAINT `fk_departments_sector_id` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Programs table
CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_name` varchar(100) NOT NULL,
  `program_code` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`program_id`),
  UNIQUE KEY `program_code` (`program_code`),
  KEY `idx_programs_department` (`department_id`),
  KEY `idx_programs_active` (`is_active`),
  CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- USER MANAGEMENT TABLES
-- =====================================================================

-- 6. Users table
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_last_login` (`last_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Staff table
CREATE TABLE `staff` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee Number format: LCAXXXXP',
  `user_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `staff_category` enum('Regular Staff','Program Head','School Administrator') NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'For program heads and department-specific staff',
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') DEFAULT NULL COMMENT 'For faculty-staff dual roles',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_number`),
  UNIQUE KEY `uq_staff_emp` (`employee_number`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_staff_designation` (`designation_id`),
  KEY `idx_staff_category` (`staff_category`),
  KEY `idx_staff_department` (`department_id`),
  KEY `idx_staff_active` (`is_active`),
  KEY `idx_staff_category_department` (`staff_category`,`department_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  CONSTRAINT `staff_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Students table
CREATE TABLE `students` (
  `student_id` varchar(11) NOT NULL COMMENT 'Student number format: 02000288322',
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `sector` enum('College','Senior High School') DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL COMMENT 'e.g., "4/1-1", "3/1-2"',
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_students_program` (`program_id`),
  KEY `idx_students_year_level` (`year_level`),
  KEY `idx_students_sector` (`sector`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Faculty table
CREATE TABLE `faculty` (
  `employee_number` varchar(8) NOT NULL COMMENT 'Employee ID format: LCA123P',
  `user_id` int(11) DEFAULT NULL,
  `employment_status` enum('Full Time','Part Time','Part Time - Full Load') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `sector` enum('College','Senior High School','Faculty') NOT NULL DEFAULT 'Faculty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_number`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_faculty_department` (`department_id`),
  KEY `idx_faculty_status` (`employment_status`),
  KEY `idx_faculty_department_status` (`department_id`,`employment_status`),
  KEY `idx_faculty_sector` (`sector`),
  CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `faculty_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- ACADEMIC MANAGEMENT TABLES
-- =====================================================================

-- 10. Academic Years table
CREATE TABLE `academic_years` (
  `academic_year_id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(9) NOT NULL COMMENT 'Format: 2024-2025',
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`academic_year_id`),
  UNIQUE KEY `year` (`year`),
  KEY `idx_academic_years_active` (`is_active`),
  KEY `idx_academic_years_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Semesters table
CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL AUTO_INCREMENT,
  `semester_name` enum('1st','2nd','Summer') NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Only one can be active at a time',
  `is_generation` tinyint(1) DEFAULT 0 COMMENT 'Active for clearance generation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`semester_id`),
  KEY `idx_semesters_active` (`is_active`),
  KEY `idx_semesters_generation` (`is_generation`),
  KEY `idx_semesters_academic_year` (`academic_year_id`),
  CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- CLEARANCE MANAGEMENT TABLES
-- =====================================================================

-- 12. Clearance Periods table
CREATE TABLE `clearance_periods` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `sector` enum('College','Senior High School','Faculty') NOT NULL,
  `period_name` varchar(100) NOT NULL COMMENT 'e.g., "End of Semester Clearance", "Graduation Clearance"',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL COMMENT 'When the period was manually ended',
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('Planning','Active','Ended','Cancelled') DEFAULT 'Planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`period_id`),
  KEY `idx_clearance_periods_academic_year` (`academic_year_id`),
  KEY `idx_clearance_periods_semester` (`semester_id`),
  KEY `idx_clearance_periods_sector` (`sector`),
  KEY `idx_clearance_periods_active` (`is_active`),
  KEY `idx_clearance_periods_status` (`status`),
  KEY `idx_clearance_periods_dates` (`start_date`,`end_date`),
  CONSTRAINT `clearance_periods_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE,
  CONSTRAINT `clearance_periods_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Sector Signatory Assignments table
CREATE TABLE `sector_signatory_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Staff member assigned as signatory',
  `designation_id` int(11) NOT NULL COMMENT 'Designation/position of the signatory',
  `is_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this is a Program Head assignment',
  `department_id` int(11) DEFAULT NULL COMMENT 'Specific department for Program Head (NULL for general staff)',
  `is_required_first` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign first',
  `is_required_last` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign last',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'FALSE to temporarily disable assignment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `idx_sector_assignments_type` (`clearance_type`),
  KEY `idx_sector_assignments_user` (`user_id`),
  KEY `idx_sector_assignments_designation` (`designation_id`),
  KEY `idx_sector_assignments_department` (`department_id`),
  KEY `idx_sector_assignments_program_head` (`is_program_head`),
  KEY `idx_sector_assignments_active` (`is_active`),
  KEY `idx_sector_user_designation` (`clearance_type`,`user_id`,`designation_id`),
  KEY `idx_sector_program_head_dept` (`clearance_type`,`is_program_head`,`department_id`),
  CONSTRAINT `sector_signatory_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `sector_signatory_assignments_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  CONSTRAINT `sector_signatory_assignments_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Sector Clearance Settings table
CREATE TABLE `sector_clearance_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `include_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if Program Heads should be auto-assigned',
  `required_first_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required first signatory is enabled',
  `required_first_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign first',
  `required_last_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required last signatory is enabled',
  `required_last_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign last',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_sector_setting` (`clearance_type`),
  KEY `idx_sector_settings_type` (`clearance_type`),
  KEY `sector_clearance_settings_ibfk_1` (`required_first_designation_id`),
  KEY `sector_clearance_settings_ibfk_2` (`required_last_designation_id`),
  CONSTRAINT `sector_clearance_settings_ibfk_1` FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL,
  CONSTRAINT `sector_clearance_settings_ibfk_2` FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Clearance Forms table
CREATE TABLE `clearance_forms` (
  `clearance_form_id` varchar(20) NOT NULL COMMENT 'Format: CF-YYYY-XXXXX',
  `user_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `clearance_type` enum('College','Senior High School','Faculty') NOT NULL,
  `status` enum('Unapplied','Pending','Processing','Approved','Rejected') DEFAULT 'Unapplied',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clearance_form_id`),
  UNIQUE KEY `unique_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  KEY `semester_id` (`semester_id`),
  KEY `idx_clearance_forms_user` (`user_id`),
  KEY `idx_clearance_forms_period` (`academic_year_id`,`semester_id`),
  KEY `idx_clearance_forms_status` (`status`),
  KEY `idx_clearance_forms_type` (`clearance_type`),
  KEY `idx_clearance_forms_user_period` (`user_id`,`academic_year_id`,`semester_id`),
  KEY `idx_clearance_form_id` (`clearance_form_id`),
  KEY `idx_clearance_forms_user_academic_semester` (`user_id`,`academic_year_id`,`semester_id`),
  CONSTRAINT `clearance_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `clearance_forms_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE,
  CONSTRAINT `clearance_forms_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Clearance Signatories table
CREATE TABLE `clearance_signatories` (
  `signatory_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_form_id` varchar(20) DEFAULT NULL,
  `actual_user_id` int(11) DEFAULT NULL COMMENT 'Staff member who actually signed (for override)',
  `action` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `rejection_reason_id` int(11) DEFAULT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` text DEFAULT NULL COMMENT 'Additional details for rejection',
  `date_signed` timestamp NULL DEFAULT NULL COMMENT 'When action was taken',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`signatory_id`),
  KEY `idx_clearance_signatories_form` (`clearance_form_id`),
  KEY `idx_clearance_signatories_action` (`action`),
  KEY `idx_clearance_signatories_date` (`date_signed`),
  KEY `idx_clearance_signatories_form_action` (`clearance_form_id`,`action`),
  KEY `clearance_signatories_ibfk_3` (`actual_user_id`),
  CONSTRAINT `clearance_signatories_ibfk_1` FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `clearance_signatories_ibfk_3` FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TRIGGERS
-- =====================================================================

-- Trigger to ensure only one active academic year
DELIMITER $$
CREATE TRIGGER `ensure_single_active_academic_year` BEFORE UPDATE ON `academic_years` FOR EACH ROW BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `academic_years` SET `is_active` = 0 WHERE `academic_year_id` != NEW.academic_year_id;
    END IF;
END$$
DELIMITER ;

-- Trigger to generate clearance form ID
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
END$$
DELIMITER ;

-- Trigger to validate staff employee number format
DELIMITER $$
CREATE TRIGGER `staff_bi` BEFORE INSERT ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `staff_bu` BEFORE UPDATE ON `staff` FOR EACH ROW BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END$$
DELIMITER ;

-- =====================================================================
-- INITIAL DATA
-- =====================================================================

-- Insert sectors
INSERT INTO `sectors` (`sector_id`, `sector_name`) VALUES
(1, 'College'),
(2, 'Senior High School'),
(3, 'Faculty');

-- Insert roles
INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Admin', 'Full system access and control'),
(2, 'School Administrator', 'School administration staff with special capabilities'),
(3, 'Program Head', 'Department program head with special capabilities'),
(4, 'Regular Staff', 'Regular staff members (cashier, librarian, etc.)'),
(5, 'Student', 'Student users applying for clearance'),
(6, 'Faculty', 'Faculty members applying for clearance');

-- Insert designations
INSERT INTO `designations` (`designation_id`, `designation_name`, `description`) VALUES
(1, 'Registrar', 'Registrar office staff'),
(2, 'Cashier', 'Cashier office staff'),
(3, 'Librarian', 'Library staff'),
(4, 'MIS/IT', 'IT and MIS staff'),
(5, 'Building Administrator', 'Building and facilities staff'),
(6, 'HR', 'Human Resources staff'),
(7, 'Student Affairs Officer', 'Student affairs staff'),
(8, 'Program Head', 'Department program head'),
(9, 'School Administrator', 'School administration staff'),
(10, 'PAMO', 'Purchasing and Assets Management Officer'),
(11, 'Petty Cash Custodian', 'Petty Cash Custodian'),
(12, 'Accountant', 'Accounting staff'),
(13, 'Academic Head', 'Academic Head'),
(14, 'Guidance', 'Guidance office'),
(15, 'Disciplinary Officer', 'Disciplinary office'),
(16, 'Clinic', 'Clinic staff'),
(17, 'Alumni Placement Officer', 'Alumni Placement Officer'),
(18, 'Faculty', 'Faculty member designation');

-- Insert departments
INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `department_type`, `sector_id`) VALUES
(1, 'Information & Communication Technology', 'ICT', 'College', 1),
(2, 'Business, Arts, & Science', 'BAS', 'College', 1),
(3, 'Tourism & Hospitality Management', 'THM', 'College', 1),
(4, 'Academic Track', 'ACAD', 'Senior High School', 2),
(5, 'Technological-Vocational Livelihood', 'TVL', 'Senior High School', 2),
(6, 'Home Economics', 'HE', 'Senior High School', 2),
(7, 'General Education', 'GE', 'Faculty', 3);

-- Insert programs
INSERT INTO `programs` (`program_id`, `program_name`, `program_code`, `description`, `department_id`) VALUES
-- College Programs
(1, 'BS in Information Technology', 'BSIT', 'Bachelor of Science in Information Technology', 1),
(2, 'BS in Computer Science', 'BSCS', 'Bachelor of Science in Computer Science', 1),
(3, 'BS in Computer Engineering', 'BSCE', 'Bachelor of Science in Computer Engineering', 1),
(4, 'BS in Hospitality Management', 'BSHM', 'Bachelor of Science in Hospitality Management', 3),
(5, 'BS in Culinary Management', 'BSCM', 'Bachelor of Science in Culinary Management', 3),
(6, 'BS in Tourism Management', 'BSTM', 'Bachelor of Science in Tourism Management', 3),
(7, 'Bachelor of Multimedia Arts', 'BMMA', 'Bachelor of Multimedia Arts', 2),
(8, 'BA in Communication', 'BACOMM', 'Bachelor of Arts in Communication', 2),
(9, 'BS in Business Administration', 'BSBA', 'Bachelor of Science in Business Administration', 2),
(10, 'BS in Accountancy', 'BSA', 'Bachelor of Science in Accountancy', 2),
(11, 'BS in Accounting Information System', 'BSAIS', 'Bachelor of Science in Accounting Information System', 2),
-- Senior High School Programs
(12, 'Accountancy, Business, Management', 'ABM', 'Academic Track - Accountancy, Business, Management', 4),
(13, 'Science, Technology, Engineering, and Mathematics', 'STEM', 'Academic Track - Science, Technology, Engineering, and Mathematics', 4),
(14, 'Humanities and Social Sciences', 'HUMSS', 'Academic Track - Humanities and Social Sciences', 4),
(15, 'General Academic', 'GA', 'Academic Track - General Academic', 4),
(16, 'Digital Arts', 'DIGITAL_AR', 'Technical-Vocational-Livelihood Track - Digital Arts', 5),
(17, 'IT in Mobile App and Web Development', 'IT_MAWD', 'Technical-Vocational-Livelihood Track - IT in Mobile App and Web Development', 5),
(18, 'Tourism Operations', 'TOURISM_OP', 'Technical-Vocational-Livelihood Track - Tourism Operations', 5),
(19, 'Restaurant and Cafe Operations', 'REST_CAFE', 'Technical-Vocational-Livelihood Track - Restaurant and Cafe Operations', 5),
(20, 'Culinary Arts', 'CULINARY', 'Technical-Vocational-Livelihood Track - Culinary Arts', 5);

-- Insert sector clearance settings
INSERT INTO `sector_clearance_settings` (`setting_id`, `clearance_type`, `include_program_head`, `required_first_enabled`, `required_first_designation_id`, `required_last_enabled`, `required_last_designation_id`) VALUES
(1, 'College', 1, 1, 2, 1, 1),
(2, 'Senior High School', 1, 1, 2, 1, 1),
(3, 'Faculty', 1, 1, 2, 1, 1);

-- =====================================================================
-- DEFAULT ADMIN USER
-- =====================================================================

-- Insert default admin user
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `status`, `must_change_password`, `can_apply`) VALUES
(1, 'admin', '$2y$10$cS1Lk6GOXeKSOmqguvw6lO/kuHy844NX1Kgt8rInKgCn5dgWTdN9K', 'admin@system.local', 'System', 'Administrator', 'active', 0, 1);

-- =====================================================================
-- COMPLETION MESSAGE
-- =====================================================================

SELECT 'Tables created successfully in online_clearance_db_ver2!' as Message;
SELECT 'Default admin credentials: username=admin, password=admin123' as Admin_Info;
SELECT 'Remember to update the admin password after first login!' as Security_Note;
