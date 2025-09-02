-- =====================================================
-- ONLINE CLEARANCE WEBSITE - REFACTORED DATABASE SCHEMA
-- =====================================================
-- This schema addresses all identified issues and user requirements
-- - Consolidated staff management
-- - Simplified role system (4 roles)
-- - Streamlined clearance requirements
-- - Performance optimized
-- =====================================================

-- Create and use database
CREATE DATABASE IF NOT EXISTS `online_clearance_db` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `online_clearance_db`;

-- =====================================================
-- PHASE 1: CORE FOUNDATION (REFACTORED)
-- =====================================================

-- Core user management
CREATE TABLE `users` (
  `user_id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Student number for students, Employee ID for staff/faculty',
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50),
  `contact_number` VARCHAR(20),
  `profile_picture` VARCHAR(255),
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` TIMESTAMP NULL,
  `password_reset_token` VARCHAR(255) NULL COMMENT 'For admin-initiated password resets',
  `password_reset_expires` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_users_username` (`username`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_last_login` (`last_login`)
);

-- Simplified role system (4 main roles)
CREATE TABLE `roles` (
  `role_id` INT PRIMARY KEY AUTO_INCREMENT,
  `role_name` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Admin, Staff, Student, Faculty',
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_roles_name` (`role_name`),
  INDEX `idx_roles_active` (`is_active`)
);

-- Streamlined permissions system
CREATE TABLE `permissions` (
  `permission_id` INT PRIMARY KEY AUTO_INCREMENT,
  `permission_name` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `category` VARCHAR(50) COMMENT 'user_management, clearance_management, reporting, etc.',
  `is_active` BOOLEAN DEFAULT TRUE,
  
  INDEX `idx_permissions_category` (`category`),
  INDEX `idx_permissions_active` (`is_active`)
);

-- Role-permission mapping with inheritance
CREATE TABLE `role_permissions` (
  `role_id` INT,
  `permission_id` INT,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `granted_by` INT COMMENT 'Admin who granted this permission',
  
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
);

-- User-role assignments
CREATE TABLE `user_roles` (
  `user_id` INT,
  `role_id` INT,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` INT COMMENT 'Admin who assigned this role',
  `is_primary` BOOLEAN DEFAULT FALSE COMMENT 'Primary role for the user',
  
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_user_roles_primary` (`user_id`, `is_primary`)
);

-- Academic structure
CREATE TABLE `academic_years` (
  `academic_year_id` INT PRIMARY KEY AUTO_INCREMENT,
  `year` VARCHAR(9) UNIQUE NOT NULL COMMENT 'Format: 2024-2025',
  `is_active` BOOLEAN DEFAULT FALSE COMMENT 'Only one can be active at a time',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_academic_years_active` (`is_active`),
  INDEX `idx_academic_years_year` (`year`)
);

-- Semester management (auto-created when academic year is created)
CREATE TABLE `semesters` (
  `semester_id` INT PRIMARY KEY AUTO_INCREMENT,
  `semester_name` ENUM('1st', '2nd', 'Summer') NOT NULL,
  `academic_year_id` INT,
  `is_active` BOOLEAN DEFAULT FALSE COMMENT 'Only one can be active at a time',
  `is_generation` BOOLEAN DEFAULT FALSE COMMENT 'Active for clearance generation',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`) ON DELETE CASCADE,
  
  INDEX `idx_semesters_active` (`is_active`),
  INDEX `idx_semesters_generation` (`is_generation`),
  UNIQUE KEY `unique_active_semester` (`academic_year_id`, `is_active`)
);

-- Department organization
CREATE TABLE `departments` (
  `department_id` INT PRIMARY KEY AUTO_INCREMENT,
  `department_name` VARCHAR(100) NOT NULL,
  `department_code` VARCHAR(10) UNIQUE,
  `department_type` ENUM('College', 'Senior High School') NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_departments_type` (`department_type`),
  INDEX `idx_departments_active` (`is_active`)
);

-- Program/course management
CREATE TABLE `programs` (
  `program_id` INT PRIMARY KEY AUTO_INCREMENT,
  `program_name` VARCHAR(100) NOT NULL,
  `program_code` VARCHAR(10) UNIQUE,
  `description` TEXT,
  `department_id` INT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  
  INDEX `idx_programs_department` (`department_id`),
  INDEX `idx_programs_active` (`is_active`)
);

-- Staff designations/positions
CREATE TABLE `designations` (
  `designation_id` INT PRIMARY KEY AUTO_INCREMENT,
  `designation_name` VARCHAR(100) NOT NULL COMMENT 'Registrar, Cashier, Librarian, etc.',
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_designations_active` (`is_active`)
);

-- =====================================================
-- PHASE 2: CLEARANCE MANAGEMENT (REFACTORED)
-- =====================================================

-- Clearance periods (manual control by admin)
CREATE TABLE `clearance_periods` (
  `period_id` INT PRIMARY KEY AUTO_INCREMENT,
  `academic_year_id` INT,
  `semester_id` INT,
  `period_name` VARCHAR(100) COMMENT 'Auto-generated: "2024-2025 1st Semester"',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` BOOLEAN DEFAULT FALSE COMMENT 'Only one can be active at a time',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`) ON DELETE CASCADE,
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`semester_id`) ON DELETE CASCADE,
  
  INDEX `idx_clearance_periods_active` (`is_active`),
  INDEX `idx_clearance_periods_dates` (`start_date`, `end_date`),
  UNIQUE KEY `unique_active_period` (`is_active`)
);

-- Main clearance forms
CREATE TABLE `clearance_forms` (
  `clearance_form_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `academic_year_id` INT,
  `semester_id` INT,
  `clearance_type` ENUM('Student', 'Faculty') NOT NULL,
  `status` ENUM('Unapplied', 'Pending', 'In Progress', 'Completed', 'Rejected') DEFAULT 'Unapplied',
  `applied_at` TIMESTAMP NULL COMMENT 'When user first applied',
  `completed_at` TIMESTAMP NULL COMMENT 'When all signatories approved',
  `rejected_at` TIMESTAMP NULL COMMENT 'When any signatory rejected',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`) ON DELETE CASCADE,
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`semester_id`) ON DELETE CASCADE,
  
  INDEX `idx_clearance_forms_user` (`user_id`),
  INDEX `idx_clearance_forms_period` (`academic_year_id`, `semester_id`),
  INDEX `idx_clearance_forms_status` (`status`),
  INDEX `idx_clearance_forms_type` (`clearance_type`),
  UNIQUE KEY `unique_user_period` (`user_id`, `academic_year_id`, `semester_id`)
);

-- Streamlined clearance requirements
CREATE TABLE `clearance_requirements` (
  `requirement_id` INT PRIMARY KEY AUTO_INCREMENT,
  `clearance_type` ENUM('Student', 'Faculty') NOT NULL,
  `designation_id` INT NOT NULL COMMENT 'Which signatory is required',
  `is_required` BOOLEAN DEFAULT TRUE,
  `order_sequence` INT DEFAULT 0 COMMENT 'Order of appearance in clearance form',
  `is_department_specific` BOOLEAN DEFAULT FALSE COMMENT 'TRUE for Program Head',
  `applies_to_departments` JSON NULL COMMENT 'Array of department IDs for Program Head',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`designation_id`) REFERENCES `designations`(`designation_id`) ON DELETE CASCADE,
  
  INDEX `idx_clearance_requirements_type` (`clearance_type`),
  INDEX `idx_clearance_requirements_order` (`order_sequence`),
  INDEX `idx_clearance_requirements_department_specific` (`is_department_specific`)
);

-- Individual signatory actions
CREATE TABLE `clearance_signatories` (
  `signatory_id` INT PRIMARY KEY AUTO_INCREMENT,
  `clearance_form_id` INT,
  `designation_id` INT,
  `actual_user_id` INT NULL COMMENT 'Staff member who actually signed (for override)',
  `action` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `remarks` TEXT COMMENT 'General remarks',
  `rejection_reason_id` INT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` TEXT COMMENT 'Additional details for rejection',
  `date_signed` TIMESTAMP NULL COMMENT 'When action was taken',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms`(`clearance_form_id`) ON DELETE CASCADE,
  FOREIGN KEY (`designation_id`) REFERENCES `designations`(`designation_id`) ON DELETE CASCADE,
  FOREIGN KEY (`actual_user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_clearance_signatories_form` (`clearance_form_id`),
  INDEX `idx_clearance_signatories_designation` (`designation_id`),
  INDEX `idx_clearance_signatories_action` (`action`),
  INDEX `idx_clearance_signatories_date` (`date_signed`)
);

-- Enhanced rejection system
CREATE TABLE `rejection_reasons` (
  `reason_id` INT PRIMARY KEY AUTO_INCREMENT,
  `reason_name` VARCHAR(100) NOT NULL,
  `reason_category` ENUM('student', 'faculty', 'both') NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_rejection_reasons_category` (`reason_category`),
  INDEX `idx_rejection_reasons_active` (`is_active`)
);

-- Rejection remarks tracking
CREATE TABLE `rejection_remarks` (
  `remark_id` INT PRIMARY KEY AUTO_INCREMENT,
  `signatory_id` INT,
  `reason_id` INT,
  `additional_remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories`(`signatory_id`) ON DELETE CASCADE,
  FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons`(`reason_id`) ON DELETE CASCADE,
  
  INDEX `idx_rejection_remarks_signatory` (`signatory_id`)
);

-- =====================================================
-- PHASE 3: USER TYPE SPECIFIC TABLES (REFACTORED)
-- =====================================================

-- Student management
CREATE TABLE `students` (
  `student_id` VARCHAR(11) PRIMARY KEY COMMENT 'Student number format: 02000288322',
  `user_id` INT UNIQUE,
  `program_id` INT,
  `department_id` INT,
  `section` VARCHAR(20) COMMENT 'e.g., "4/1-1", "3/1-2"',
  `year_level` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year'),
  `enrollment_status` ENUM('Enrolled', 'Graduated', 'Transferred', 'Dropped') DEFAULT 'Enrolled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`program_id`) REFERENCES `programs`(`program_id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  
  INDEX `idx_students_program` (`program_id`),
  INDEX `idx_students_department` (`department_id`),
  INDEX `idx_students_year_level` (`year_level`),
  INDEX `idx_students_status` (`enrollment_status`)
);

-- Faculty management
CREATE TABLE `faculty` (
  `employee_number` VARCHAR(8) PRIMARY KEY COMMENT 'Employee Number format: LCA123P',
  `user_id` INT UNIQUE,
  `employment_status` ENUM('Full Time', 'Part Time', 'Part Time - Full Load') NOT NULL,
  `department_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  
  INDEX `idx_faculty_department` (`department_id`),
  INDEX `idx_faculty_status` (`employment_status`)
);

-- UNIFIED STAFF TABLE (consolidated from 3 separate tables)
CREATE TABLE `staff` (
  `employee_number` VARCHAR(8) PRIMARY KEY COMMENT 'Employee Number format: LCA123P',
  `user_id` INT UNIQUE,
  `designation_id` INT,
  `staff_category` ENUM('Regular Staff', 'Program Head', 'School Administrator') NOT NULL,
  `department_id` INT NULL COMMENT 'For program heads and department-specific staff',
  `employment_status` ENUM('Full Time', 'Part Time', 'Part Time - Full Load') NULL COMMENT 'For faculty-staff dual roles',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`designation_id`) REFERENCES `designations`(`designation_id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  
  INDEX `idx_staff_designation` (`designation_id`),
  INDEX `idx_staff_category` (`staff_category`),
  INDEX `idx_staff_department` (`department_id`),
  INDEX `idx_staff_active` (`is_active`)
);

-- =====================================================
-- PHASE 4: ENHANCED FEATURES & TRACKING
-- =====================================================

-- Comprehensive audit logging
CREATE TABLE `audit_logs` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `action` VARCHAR(255) NOT NULL COMMENT 'e.g., "User Login", "Clearance Approved", "Bulk Operation"',
  `entity_type` VARCHAR(50) COMMENT 'e.g., "User", "Clearance", "Student"',
  `entity_id` INT COMMENT 'ID of the affected record',
  `old_values` JSON COMMENT 'Previous state of the record',
  `new_values` JSON COMMENT 'New state of the record',
  `ip_address` VARCHAR(45) COMMENT 'IPv4 or IPv6 address',
  `user_agent` TEXT COMMENT 'Browser/client information',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_audit_logs_user` (`user_id`),
  INDEX `idx_audit_logs_action` (`action`),
  INDEX `idx_audit_logs_entity` (`entity_type`, `entity_id`),
  INDEX `idx_audit_logs_created` (`created_at`)
);

-- User activity tracking for dashboard
CREATE TABLE `user_activities` (
  `activity_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `activity_type` VARCHAR(50) NOT NULL COMMENT 'e.g., "Login", "Clearance Apply", "Profile Update"',
  `activity_details` JSON COMMENT 'Additional activity information',
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  
  INDEX `idx_user_activities_user` (`user_id`),
  INDEX `idx_user_activities_type` (`activity_type`),
  INDEX `idx_user_activities_created` (`created_at`)
);

-- User session management
CREATE TABLE `login_sessions` (
  `session_id` VARCHAR(255) PRIMARY KEY COMMENT 'PHP session ID or custom session token',
  `user_id` INT,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  
  INDEX `idx_login_sessions_user` (`user_id`),
  INDEX `idx_login_sessions_active` (`is_active`),
  INDEX `idx_login_sessions_last_activity` (`last_activity`)
);

-- Bulk operations tracking
CREATE TABLE `bulk_operations` (
  `operation_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT COMMENT 'User who initiated the bulk operation',
  `operation_type` VARCHAR(50) NOT NULL COMMENT 'e.g., "Bulk Approve", "Bulk Reject", "Bulk Export"',
  `target_count` INT NOT NULL COMMENT 'Total number of records to process',
  `success_count` INT DEFAULT 0 COMMENT 'Successfully processed records',
  `failure_count` INT DEFAULT 0 COMMENT 'Failed to process records',
  `operation_data` JSON COMMENT 'Details of the operation (targets, parameters)',
  `status` ENUM('In Progress', 'Completed', 'Failed', 'Cancelled') DEFAULT 'In Progress',
  `progress_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT '0.00 to 100.00',
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `error_message` TEXT COMMENT 'Error details if operation failed',
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_bulk_operations_user` (`user_id`),
  INDEX `idx_bulk_operations_type` (`operation_type`),
  INDEX `idx_bulk_operations_status` (`status`),
  INDEX `idx_bulk_operations_started` (`started_at`)
);

-- Detailed operation logs
CREATE TABLE `operation_logs` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `operation_id` INT,
  `target_id` INT COMMENT 'ID of the record being processed',
  `target_type` VARCHAR(50) COMMENT 'Type of record (Student, Faculty, Clearance)',
  `action` VARCHAR(50) COMMENT 'Specific action taken',
  `result` ENUM('Success', 'Failure', 'Skipped') NOT NULL,
  `error_message` TEXT COMMENT 'Error details if failed',
  `processing_time_ms` INT COMMENT 'Time taken to process this record in milliseconds',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`operation_id`) REFERENCES `bulk_operations`(`operation_id`) ON DELETE CASCADE,
  
  INDEX `idx_operation_logs_operation` (`operation_id`),
  INDEX `idx_operation_logs_target` (`target_type`, `target_id`),
  INDEX `idx_operation_logs_result` (`result`)
);

-- =====================================================
-- PHASE 5: SYSTEM CONFIGURATION & OPTIMIZATION
-- =====================================================

-- System configuration settings
CREATE TABLE `system_settings` (
  `setting_id` INT PRIMARY KEY AUTO_INCREMENT,
  `setting_key` VARCHAR(100) UNIQUE NOT NULL COMMENT 'e.g., "clearance_period_duration", "max_bulk_operation_size"',
  `setting_value` TEXT COMMENT 'Value of the setting',
  `setting_type` ENUM('string', 'integer', 'boolean', 'json', 'decimal') DEFAULT 'string',
  `description` TEXT COMMENT 'Human-readable description',
  `is_editable` BOOLEAN DEFAULT TRUE COMMENT 'Whether admins can change this setting',
  `category` VARCHAR(50) COMMENT 'Grouping for settings (security, performance, features)',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT COMMENT 'User who last modified this setting',
  
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_system_settings_category` (`category`),
  INDEX `idx_system_settings_editable` (`is_editable`)
);

-- File management system
CREATE TABLE `file_uploads` (
  `file_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT COMMENT 'User who uploaded the file',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Original filename',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Server file path',
  `file_type` VARCHAR(100) COMMENT 'MIME type',
  `file_size` BIGINT COMMENT 'File size in bytes',
  `file_category` VARCHAR(50) COMMENT 'e.g., "clearance_document", "import_file", "export_file"',
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Whether file is still accessible',
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_file_uploads_user` (`user_id`),
  INDEX `idx_file_uploads_category` (`file_category`),
  INDEX `idx_file_uploads_uploaded` (`uploaded_at`)
);

-- Data versioning and import/export tracking
CREATE TABLE `data_versions` (
  `version_id` INT PRIMARY KEY AUTO_INCREMENT,
  `data_type` VARCHAR(50) NOT NULL COMMENT 'e.g., "students", "faculty", "departments"',
  `operation_type` ENUM('import', 'export', 'backup', 'restore') NOT NULL,
  `file_id` INT NULL COMMENT 'Associated file if applicable',
  `user_id` INT COMMENT 'User who performed the operation',
  `record_count` INT COMMENT 'Number of records processed',
  `operation_details` JSON COMMENT 'Details of the operation',
  `status` ENUM('In Progress', 'Completed', 'Failed') DEFAULT 'In Progress',
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `error_message` TEXT COMMENT 'Error details if failed',
  
  FOREIGN KEY (`file_id`) REFERENCES `file_uploads`(`file_id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  INDEX `idx_data_versions_type` (`data_type`),
  INDEX `idx_data_versions_operation` (`operation_type`),
  INDEX `idx_data_versions_status` (`status`),
  INDEX `idx_data_versions_started` (`started_at`)
);

-- =====================================================
-- SAMPLE DATA POPULATION (REFACTORED)
-- =====================================================

-- Insert simplified roles (4 main roles)
INSERT INTO `roles` (`role_name`, `description`) VALUES
('Admin', 'Full system access and control'),
('Staff', 'All staff members (cashier, librarian, program head, school admin)'),
('Student', 'Student users applying for clearance'),
('Faculty', 'Faculty members applying for clearance');

-- Insert streamlined permissions
INSERT INTO `permissions` (`permission_name`, `description`, `category`) VALUES
-- User Management
('view_users', 'View user information', 'user_management'),
('create_users', 'Create new users', 'user_management'),
('edit_users', 'Edit user information', 'user_management'),
('delete_users', 'Delete users', 'user_management'),
('reset_passwords', 'Reset user passwords', 'user_management'),

-- Clearance Management
('view_clearance', 'View clearance information', 'clearance_management'),
('edit_clearance', 'Edit clearance details', 'clearance_management'),
('approve_clearance', 'Approve clearance requests', 'clearance_management'),
('reject_clearance', 'Reject clearance requests', 'clearance_management'),
('override_approval', 'Override signatory approvals', 'clearance_management'),

-- Academic Management
('manage_academic_years', 'Manage academic years', 'academic_management'),
('manage_semesters', 'Manage semesters', 'academic_management'),
('manage_departments', 'Manage departments', 'academic_management'),
('manage_programs', 'Manage programs/courses', 'academic_management'),

-- Reporting and Export
('view_reports', 'View system reports', 'reporting'),
('export_data', 'Export data to various formats', 'reporting'),
('import_data', 'Import data from files', 'reporting'),

-- System Administration
('manage_system_settings', 'Manage system configuration', 'system_admin'),
('view_audit_logs', 'View system audit logs', 'system_admin'),
('manage_roles', 'Manage user roles and permissions', 'system_admin');

-- Insert default academic year
INSERT INTO `academic_years` (`year`, `is_active`) VALUES ('2024-2025', TRUE);

-- Insert default semesters (auto-created when academic year is created)
INSERT INTO `semesters` (`semester_name`, `academic_year_id`, `is_active`) VALUES
('1st', 1, TRUE),
('2nd', 1, FALSE),
('Summer', 1, FALSE);

-- Insert default departments
INSERT INTO `departments` (`department_name`, `department_code`, `department_type`) VALUES
('Information & Communication Technology', 'ICT', 'College'),
('Business & Management, Arts, and Sciences', 'BAS', 'College'),
('Tourism and Hospitality Management', 'THM', 'College'),
('Academic Track', 'ACAD', 'Senior High School'),
('Technical-Vocational Livelihood Track', 'TVL', 'Senior High School'),
('Home Economics', 'HE', 'Senior High School');

-- Insert default programs
INSERT INTO `programs` (`program_name`, `program_code`, `description`, `department_id`) VALUES
-- ICT Department
('BS in Information Technology', 'BSIT', 'Bachelor of Science in Information Technology', 1),
('BS in Computer Science', 'BSCS', 'Bachelor of Science in Computer Science', 1),
('BS in Computer Engineering', 'BSCpE', 'Bachelor of Science in Computer Engineering', 1),

-- BAS Department
('BS in Business Administration', 'BSBA', 'Bachelor of Science in Business Administration', 2),
('BS in Accountancy', 'BSA', 'Bachelor of Science in Accountancy', 2),
('BS in Accounting Information System', 'BSAIS', 'Bachelor of Science in Accounting Information System', 2),
('Bachelor of Multimedia Arts', 'BMMA', 'Bachelor of Multimedia Arts', 2),
('BA in Communication', 'BAC', 'Bachelor of Arts in Communication', 2),

-- THM Department
('BS in Hospitality Management', 'BSHM', 'Bachelor of Science in Hospitality Management', 3),
('BS in Culinary Management', 'BSCM', 'Bachelor of Science in Culinary Management', 3),
('BS in Tourism Management', 'BSTM', 'Bachelor of Science in Tourism Management', 3),

-- Senior High School
('ABM', 'ABM', 'Accountancy, Business, Management', 4),
('STEM', 'STEM', 'Science, Technology, Engineering, and Mathematics', 4),
('HUMSS', 'HUMSS', 'Humanities and Social Sciences', 4),
('GA', 'GA', 'General Academic', 4),
('Digital Arts', 'DIGARTS', 'Digital Arts', 5),
('IT in Mobile app and Web development', 'MAWD', 'IT in Mobile app and Web development', 5),
('Tourism Operations', 'TOURISM', 'Tourism Operations', 6),
('Restaurant and Cafe Operations', 'RESTCAFE', 'Restaurant and Cafe Operations', 6),
('Culinary Arts', 'CULINARY', 'Culinary Arts', 6);

-- Insert default designations
INSERT INTO `designations` (`designation_name`, `description`) VALUES
('Registrar', 'Registrar office staff'),
('Cashier', 'Cashier office staff'),
('Librarian', 'Library staff'),
('MIS/IT', 'IT and MIS staff'),
('Building Administrator', 'Building and facilities staff'),
('HR', 'Human Resources staff'),
('Student Affairs Officer', 'Student affairs staff'),
('Program Head', 'Department program head'),
('School Administrator', 'School administration staff');

-- Insert default rejection reasons
INSERT INTO `rejection_reasons` (`reason_name`, `reason_category`) VALUES
-- Student-specific reasons
('Incomplete Documents', 'student'),
('Unpaid Fees', 'student'),
('Academic Requirements Not Met', 'student'),
('Disciplinary Issues', 'student'),
('Missing Clearance Items', 'student'),

-- Faculty-specific reasons
('Incomplete Documents', 'faculty'),
('Unpaid Obligations', 'faculty'),
('Employment Requirements Not Met', 'faculty'),
('Disciplinary Issues', 'faculty'),
('Missing Clearance Items', 'faculty'),
('Contract/Employment Issues', 'faculty'),

-- General reasons
('Other', 'both');

-- Insert streamlined clearance requirements
INSERT INTO `clearance_requirements` (`clearance_type`, `designation_id`, `order_sequence`, `is_department_specific`, `applies_to_departments`) VALUES
-- Student Clearance Requirements
('Student', 1, 1, FALSE, NULL), -- Registrar (all departments)
('Student', 2, 2, FALSE, NULL), -- Cashier (all departments)
('Student', 3, 3, FALSE, NULL), -- Librarian (all departments)
('Student', 8, 4, TRUE, '[1,2,3,4,5,6]'), -- Program Head (department-specific)

-- Faculty Clearance Requirements
('Faculty', 1, 1, FALSE, NULL), -- Registrar (all departments)
('Faculty', 2, 2, FALSE, NULL), -- Cashier (all departments)
('Faculty', 8, 3, TRUE, '[1,2,3,4,5,6]'); -- Program Head (department-specific)

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('max_bulk_operation_size', '500', 'integer', 'Maximum number of records that can be processed in a single bulk operation', 'performance'),
('clearance_period_duration_days', '30', 'integer', 'Default duration of clearance periods in days', 'features'),
('audit_log_retention_days', '365', 'integer', 'Number of days to retain audit logs', 'security'),
('file_upload_max_size_mb', '10', 'integer', 'Maximum file upload size in megabytes', 'security'),
('session_timeout_minutes', '480', 'integer', 'User session timeout in minutes (8 hours)', 'security'),
('enable_email_notifications', 'false', 'boolean', 'Enable email notifications for system events', 'features'),
('enable_sms_notifications', 'false', 'boolean', 'Enable SMS notifications for system events', 'features'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode for system updates', 'system');

-- =====================================================
-- PERFORMANCE OPTIMIZATION INDEXES (REFACTORED)
-- =====================================================

-- Optimized indexes for better performance
CREATE INDEX `idx_clearance_forms_user_period` ON `clearance_forms` (`user_id`, `academic_year_id`, `semester_id`);
CREATE INDEX `idx_clearance_signatories_form_designation` ON `clearance_signatories` (`clearance_form_id`, `designation_id`);
CREATE INDEX `idx_students_program_year` ON `students` (`program_id`, `year_level`);
CREATE INDEX `idx_faculty_department_status` ON `faculty` (`department_id`, `employment_status`);
CREATE INDEX `idx_staff_category_department` ON `staff` (`staff_category`, `department_id`);
CREATE INDEX `idx_audit_logs_user_created` ON `audit_logs` (`user_id`, `created_at`);
CREATE INDEX `idx_user_activities_user_created` ON `user_activities` (`user_id`, `created_at`);
CREATE INDEX `idx_bulk_operations_user_status` ON `bulk_operations` (`user_id`, `status`);
CREATE INDEX `idx_file_uploads_category_active` ON `file_uploads` (`file_category`, `is_active`);

-- =====================================================
-- DATABASE CONSTRAINTS AND TRIGGERS (REFACTORED)
-- =====================================================

-- Ensure only one active academic year
DELIMITER //
CREATE TRIGGER `ensure_single_active_academic_year` 
BEFORE UPDATE ON `academic_years`
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `academic_years` SET `is_active` = 0 WHERE `academic_year_id` != NEW.academic_year_id;
    END IF;
END//
DELIMITER ;

-- Ensure only one active semester per academic year
DELIMITER //
CREATE TRIGGER `ensure_single_active_semester` 
BEFORE UPDATE ON `semesters`
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `semesters` SET `is_active` = 0 WHERE `academic_year_id` = NEW.academic_year_id AND `semester_id` != NEW.semester_id;
    END IF;
END//
DELIMITER ;

-- Ensure only one active clearance period
DELIMITER //
CREATE TRIGGER `ensure_single_active_clearance_period` 
BEFORE UPDATE ON `clearance_periods`
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE `clearance_periods` SET `is_active` = 0 WHERE `period_id` != NEW.period_id;
    END IF;
END//
DELIMITER ;

-- Auto-update clearance form status based on signatories
DELIMITER //
CREATE TRIGGER `update_clearance_form_status` 
AFTER UPDATE ON `clearance_signatories`
FOR EACH ROW
BEGIN
    DECLARE total_signatories INT;
    DECLARE approved_count INT;
    DECLARE rejected_count INT;
    DECLARE form_id INT;
    
    SET form_id = NEW.clearance_form_id;
    
    -- Count total signatories for this form
    SELECT COUNT(*) INTO total_signatories 
    FROM clearance_signatories 
    WHERE clearance_form_id = form_id;
    
    -- Count approved signatories
    SELECT COUNT(*) INTO approved_count 
    FROM clearance_signatories 
    WHERE clearance_form_id = form_id AND action = 'Approved';
    
    -- Count rejected signatories
    SELECT COUNT(*) INTO rejected_count 
    FROM clearance_signatories 
    WHERE clearance_form_id = form_id AND action = 'Rejected';
    
    -- Update form status
    IF rejected_count > 0 THEN
        UPDATE clearance_forms SET status = 'Rejected', rejected_at = NOW() WHERE clearance_form_id = form_id;
    ELSEIF approved_count = total_signatories THEN
        UPDATE clearance_forms SET status = 'Completed', completed_at = NOW() WHERE clearance_form_id = form_id;
    ELSEIF approved_count > 0 OR rejected_count = 0 THEN
        UPDATE clearance_forms SET status = 'In Progress' WHERE clearance_form_id = form_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- SAMPLE ADMIN USER CREATION
-- =====================================================

-- Create default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `email`, `first_name`, `last_name`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clearance.com', 'System', 'Administrator', 'active');

-- Assign admin role
INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_by`, `is_primary`) VALUES (1, 1, 1, TRUE);

-- Grant all permissions to admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `granted_by`) 
SELECT 1, permission_id, 1 FROM permissions;

-- =====================================================
-- SCHEMA COMPLETION
-- =====================================================

-- Display completion message
SELECT 'Online Clearance Website REFACTORED Database Schema Created Successfully!' AS message;
SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'online_clearance_db';
SELECT 'Database is ready for Phase 1 implementation' AS next_step;
SELECT 'Key improvements: Unified staff table, 4 roles, streamlined clearance requirements' AS improvements;