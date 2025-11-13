-- =====================================================
-- RESTORE PRIMARY KEYS AND FOREIGN KEYS
-- Online Clearance Database - Complete Constraint Restoration
-- =====================================================
-- This script will restore all primary keys and foreign keys
-- that were lost during the alwaysdata import
-- =====================================================

USE `basedata_db`;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: DROP ALL EXISTING FOREIGN KEYS (if any)
-- =====================================================

-- Drop existing foreign keys (if they exist)
ALTER TABLE `audit_logs` DROP FOREIGN KEY IF EXISTS `audit_logs_ibfk_1`;
ALTER TABLE `bulk_operations` DROP FOREIGN KEY IF EXISTS `bulk_operations_ibfk_1`;
ALTER TABLE `clearance_forms` DROP FOREIGN KEY IF EXISTS `clearance_forms_ibfk_1`;
ALTER TABLE `clearance_forms` DROP FOREIGN KEY IF EXISTS `clearance_forms_ibfk_2`;
ALTER TABLE `clearance_forms` DROP FOREIGN KEY IF EXISTS `clearance_forms_ibfk_3`;
ALTER TABLE `clearance_periods` DROP FOREIGN KEY IF EXISTS `clearance_periods_ibfk_1`;
ALTER TABLE `clearance_periods` DROP FOREIGN KEY IF EXISTS `clearance_periods_ibfk_2`;
ALTER TABLE `clearance_requirements` DROP FOREIGN KEY IF EXISTS `clearance_requirements_ibfk_1`;
ALTER TABLE `clearance_signatories` DROP FOREIGN KEY IF EXISTS `FK_SignatoryRejectionReason`;
ALTER TABLE `clearance_signatories` DROP FOREIGN KEY IF EXISTS `clearance_signatories_ibfk_1`;
ALTER TABLE `clearance_signatories` DROP FOREIGN KEY IF EXISTS `clearance_signatories_ibfk_2`;
ALTER TABLE `clearance_signatories` DROP FOREIGN KEY IF EXISTS `clearance_signatories_ibfk_3`;
ALTER TABLE `clearance_signatories_new` DROP FOREIGN KEY IF EXISTS `fk_clearance_signatories_department`;
ALTER TABLE `clearance_signatories_new` DROP FOREIGN KEY IF EXISTS `fk_clearance_signatories_designation`;
ALTER TABLE `clearance_signatories_new` DROP FOREIGN KEY IF EXISTS `fk_clearance_signatories_period`;
ALTER TABLE `clearance_signatories_new` DROP FOREIGN KEY IF EXISTS `fk_clearance_signatories_staff`;
ALTER TABLE `signatory_actions` DROP FOREIGN KEY IF EXISTS `fk_signatory_actions_form`;
ALTER TABLE `signatory_actions` DROP FOREIGN KEY IF EXISTS `fk_signatory_actions_rejection_reason`;
ALTER TABLE `signatory_actions` DROP FOREIGN KEY IF EXISTS `fk_signatory_actions_signatory`;
ALTER TABLE `data_versions` DROP FOREIGN KEY IF EXISTS `data_versions_ibfk_1`;
ALTER TABLE `data_versions` DROP FOREIGN KEY IF EXISTS `data_versions_ibfk_2`;
ALTER TABLE `departments` DROP FOREIGN KEY IF EXISTS `fk_departments_sector_id`;
ALTER TABLE `faculty` DROP FOREIGN KEY IF EXISTS `faculty_ibfk_1`;
ALTER TABLE `faculty` DROP FOREIGN KEY IF EXISTS `faculty_ibfk_2`;
ALTER TABLE `file_uploads` DROP FOREIGN KEY IF EXISTS `file_uploads_ibfk_1`;
ALTER TABLE `login_sessions` DROP FOREIGN KEY IF EXISTS `login_sessions_ibfk_1`;
ALTER TABLE `operation_logs` DROP FOREIGN KEY IF EXISTS `operation_logs_ibfk_1`;
ALTER TABLE `programs` DROP FOREIGN KEY IF EXISTS `programs_ibfk_1`;
ALTER TABLE `rejection_remarks` DROP FOREIGN KEY IF EXISTS `rejection_remarks_ibfk_1`;
ALTER TABLE `rejection_remarks` DROP FOREIGN KEY IF EXISTS `rejection_remarks_ibfk_2`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `role_permissions_ibfk_1`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `role_permissions_ibfk_2`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `role_permissions_ibfk_3`;
ALTER TABLE `scope_settings` DROP FOREIGN KEY IF EXISTS `fk_scope_settings_first_designation`;
ALTER TABLE `scope_settings` DROP FOREIGN KEY IF EXISTS `fk_scope_settings_last_designation`;
ALTER TABLE `sector_clearance_settings` DROP FOREIGN KEY IF EXISTS `sector_clearance_settings_ibfk_1`;
ALTER TABLE `sector_clearance_settings` DROP FOREIGN KEY IF EXISTS `sector_clearance_settings_ibfk_2`;
ALTER TABLE `sector_signatory_assignments` DROP FOREIGN KEY IF EXISTS `sector_signatory_assignments_ibfk_1`;
ALTER TABLE `sector_signatory_assignments` DROP FOREIGN KEY IF EXISTS `sector_signatory_assignments_ibfk_2`;
ALTER TABLE `sector_signatory_assignments` DROP FOREIGN KEY IF EXISTS `sector_signatory_assignments_ibfk_3`;
ALTER TABLE `semesters` DROP FOREIGN KEY IF EXISTS `semesters_ibfk_1`;
ALTER TABLE `signatory_assignments` DROP FOREIGN KEY IF EXISTS `fk_sa_dept`;
ALTER TABLE `signatory_assignments` DROP FOREIGN KEY IF EXISTS `fk_sa_desig`;
ALTER TABLE `signatory_assignments` DROP FOREIGN KEY IF EXISTS `fk_sa_sector`;
ALTER TABLE `signatory_assignments` DROP FOREIGN KEY IF EXISTS `fk_sa_user`;
ALTER TABLE `staff` DROP FOREIGN KEY IF EXISTS `staff_ibfk_1`;
ALTER TABLE `staff` DROP FOREIGN KEY IF EXISTS `staff_ibfk_2`;
ALTER TABLE `staff` DROP FOREIGN KEY IF EXISTS `staff_ibfk_3`;
ALTER TABLE `staff_designation_assignments` DROP FOREIGN KEY IF EXISTS `fk_sda_assigned_by`;
ALTER TABLE `staff_designation_assignments` DROP FOREIGN KEY IF EXISTS `fk_sda_department`;
ALTER TABLE `staff_designation_assignments` DROP FOREIGN KEY IF EXISTS `fk_sda_sector`;
ALTER TABLE `staff_designation_assignments` DROP FOREIGN KEY IF EXISTS `fk_sda_staff`;
ALTER TABLE `students` DROP FOREIGN KEY IF EXISTS `students_ibfk_1`;
ALTER TABLE `students` DROP FOREIGN KEY IF EXISTS `students_ibfk_2`;
ALTER TABLE `students` DROP FOREIGN KEY IF EXISTS `students_ibfk_3`;
ALTER TABLE `system_settings` DROP FOREIGN KEY IF EXISTS `system_settings_ibfk_1`;
ALTER TABLE `user_activities` DROP FOREIGN KEY IF EXISTS `user_activities_ibfk_1`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_1`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_2`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_3`;

-- =====================================================
-- STEP 2: ADD PRIMARY KEYS (if missing)
-- =====================================================

-- academic_years
-- ALTER TABLE `academic_years` DROP PRIMARY KEY; -- Only if needed
-- ALTER TABLE `academic_years` ADD PRIMARY KEY (`academic_year_id`);

-- audit_logs
ALTER TABLE `audit_logs`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`log_id`);

-- bulk_operations
ALTER TABLE `bulk_operations`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`operation_id`);

-- clearance_forms
ALTER TABLE `clearance_forms`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`clearance_form_id`);

-- clearance_periods
ALTER TABLE `clearance_periods`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`period_id`);

-- clearance_requirements
ALTER TABLE `clearance_requirements`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`requirement_id`);

-- clearance_signatories
ALTER TABLE `clearance_signatories`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`signatory_id`);

-- clearance_signatories_new
ALTER TABLE `clearance_signatories_new`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`signatory_id`);

-- signatory_actions
ALTER TABLE `signatory_actions`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`action_id`);

-- data_versions
ALTER TABLE `data_versions`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`version_id`);

-- departments
ALTER TABLE `departments`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`department_id`);

-- designations
ALTER TABLE `designations`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`designation_id`);

-- faculty
ALTER TABLE `faculty`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`employee_number`);

-- file_uploads
ALTER TABLE `file_uploads`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`file_id`);

-- login_sessions
ALTER TABLE `login_sessions`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`session_id`);

-- operation_logs
ALTER TABLE `operation_logs`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`log_id`);

-- permissions
ALTER TABLE `permissions`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`permission_id`);

-- programs
ALTER TABLE `programs`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`program_id`);

-- rejection_reasons
ALTER TABLE `rejection_reasons`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`reason_id`);

-- rejection_remarks
ALTER TABLE `rejection_remarks`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`remark_id`);

-- roles
ALTER TABLE `roles`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`role_id`);

-- role_permissions
ALTER TABLE `role_permissions`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`role_id`, `permission_id`);

-- scope_settings
ALTER TABLE `scope_settings`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`clearance_type`);

-- sectors
ALTER TABLE `sectors`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`sector_id`);

-- sector_clearance_settings
ALTER TABLE `sector_clearance_settings`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`setting_id`);

-- sector_signatory_assignments
ALTER TABLE `sector_signatory_assignments`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`assignment_id`);

-- semesters
ALTER TABLE `semesters`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`semester_id`);

-- signatory_assignments
ALTER TABLE `signatory_assignments`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`assignment_id`);

-- staff
ALTER TABLE `staff`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`employee_number`);

-- staff_designation_assignments
ALTER TABLE `staff_designation_assignments`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`assignment_id`);

-- students
ALTER TABLE `students`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`student_id`);

-- system_settings
ALTER TABLE `system_settings`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`setting_id`);

-- users
ALTER TABLE `users`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`user_id`);

-- user_activities
ALTER TABLE `user_activities`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`activity_id`);

-- user_roles
ALTER TABLE `user_roles`
  DROP PRIMARY KEY IF EXISTS,
  ADD PRIMARY KEY (`user_id`, `role_id`);

-- =====================================================
-- STEP 3: ADD AUTO_INCREMENT to PRIMARY KEYS
-- =====================================================

ALTER TABLE `academic_years` MODIFY `academic_year_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `audit_logs` MODIFY `log_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `bulk_operations` MODIFY `operation_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `clearance_periods` MODIFY `period_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `clearance_requirements` MODIFY `requirement_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `clearance_signatories` MODIFY `signatory_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `clearance_signatories_new` MODIFY `signatory_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `signatory_actions` MODIFY `action_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `data_versions` MODIFY `version_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `departments` MODIFY `department_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `designations` MODIFY `designation_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `file_uploads` MODIFY `file_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `operation_logs` MODIFY `log_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `permissions` MODIFY `permission_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `programs` MODIFY `program_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `rejection_reasons` MODIFY `reason_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `rejection_remarks` MODIFY `remark_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `roles` MODIFY `role_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `sectors` MODIFY `sector_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `sector_clearance_settings` MODIFY `setting_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `sector_signatory_assignments` MODIFY `assignment_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `semesters` MODIFY `semester_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `signatory_assignments` MODIFY `assignment_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `staff_designation_assignments` MODIFY `assignment_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `system_settings` MODIFY `setting_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `user_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_activities` MODIFY `activity_id` INT NOT NULL AUTO_INCREMENT;

-- =====================================================
-- STEP 4: ADD FOREIGN KEYS
-- =====================================================

-- audit_logs foreign keys
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- bulk_operations foreign keys
ALTER TABLE `bulk_operations`
  ADD CONSTRAINT `bulk_operations_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- clearance_forms foreign keys
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_2` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_3` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- clearance_periods foreign keys
ALTER TABLE `clearance_periods`
  ADD CONSTRAINT `clearance_periods_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_periods_ibfk_2` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- clearance_requirements foreign keys
ALTER TABLE `clearance_requirements`
  ADD CONSTRAINT `clearance_requirements_ibfk_1` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE;

-- clearance_signatories foreign keys
ALTER TABLE `clearance_signatories`
  ADD CONSTRAINT `FK_SignatoryRejectionReason` 
  FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`),
  ADD CONSTRAINT `clearance_signatories_ibfk_1` 
  FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_2` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_3` 
  FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- clearance_signatories_new foreign keys
ALTER TABLE `clearance_signatories_new`
  ADD CONSTRAINT `fk_clearance_signatories_department` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clearance_signatories_designation` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_signatories_period` 
  FOREIGN KEY (`clearance_period_id`) REFERENCES `clearance_periods` (`period_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_signatories_staff` 
  FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) 
  ON DELETE CASCADE;

-- signatory_actions foreign keys
ALTER TABLE `signatory_actions`
  ADD CONSTRAINT `fk_signatory_actions_form` 
  FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_signatory_actions_rejection_reason` 
  FOREIGN KEY (`rejection_reason_id`) REFERENCES `rejection_reasons` (`reason_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `fk_signatory_actions_signatory` 
  FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories_new` (`signatory_id`) 
  ON DELETE CASCADE;

-- data_versions foreign keys
ALTER TABLE `data_versions`
  ADD CONSTRAINT `data_versions_ibfk_1` 
  FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`file_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `data_versions_ibfk_2` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- departments foreign keys
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_sector_id` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- faculty foreign keys
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `faculty_ibfk_2` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- file_uploads foreign keys
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- login_sessions foreign keys
ALTER TABLE `login_sessions`
  ADD CONSTRAINT `login_sessions_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE;

-- operation_logs foreign keys
ALTER TABLE `operation_logs`
  ADD CONSTRAINT `operation_logs_ibfk_1` 
  FOREIGN KEY (`operation_id`) REFERENCES `bulk_operations` (`operation_id`) 
  ON DELETE CASCADE;

-- programs foreign keys
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- rejection_remarks foreign keys
ALTER TABLE `rejection_remarks`
  ADD CONSTRAINT `rejection_remarks_ibfk_1` 
  FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories` (`signatory_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `rejection_remarks_ibfk_2` 
  FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`) 
  ON DELETE CASCADE;

-- role_permissions foreign keys
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` 
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` 
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_3` 
  FOREIGN KEY (`granted_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- scope_settings foreign keys
ALTER TABLE `scope_settings`
  ADD CONSTRAINT `fk_scope_settings_first_designation` 
  FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scope_settings_last_designation` 
  FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- sector_clearance_settings foreign keys
ALTER TABLE `sector_clearance_settings`
  ADD CONSTRAINT `sector_clearance_settings_ibfk_1` 
  FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `sector_clearance_settings_ibfk_2` 
  FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL;

-- sector_signatory_assignments foreign keys
ALTER TABLE `sector_signatory_assignments`
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_2` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `sector_signatory_assignments_ibfk_3` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- semesters foreign keys
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE;

-- signatory_assignments foreign keys
ALTER TABLE `signatory_assignments`
  ADD CONSTRAINT `fk_sa_dept` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_sa_desig` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`),
  ADD CONSTRAINT `fk_sa_sector` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`),
  ADD CONSTRAINT `fk_sa_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

-- staff foreign keys
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_2` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_3` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- staff_designation_assignments foreign keys
ALTER TABLE `staff_designation_assignments`
  ADD CONSTRAINT `fk_sda_assigned_by` 
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_department` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_sector` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_staff` 
  FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- students foreign keys
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` 
  FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- system_settings foreign keys
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` 
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- user_activities foreign keys
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- user_roles foreign keys
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` 
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` 
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- =====================================================
-- STEP 5: ADD INDEXES FOR PERFORMANCE
-- =====================================================

-- Users table indexes
ALTER TABLE `users` 
  ADD INDEX IF NOT EXISTS `idx_users_username` (`username`),
  ADD INDEX IF NOT EXISTS `idx_users_email` (`email`),
  ADD INDEX IF NOT EXISTS `idx_users_status` (`status`),
  ADD INDEX IF NOT EXISTS `idx_users_last_login` (`last_login`);

-- Academic years indexes
ALTER TABLE `academic_years`
  ADD INDEX IF NOT EXISTS `idx_academic_years_active` (`is_active`),
  ADD INDEX IF NOT EXISTS `idx_academic_years_year` (`year`);

-- Semesters indexes
ALTER TABLE `semesters`
  ADD INDEX IF NOT EXISTS `idx_semesters_active` (`is_active`),
  ADD INDEX IF NOT EXISTS `idx_semesters_academic_year` (`academic_year_id`);

-- Departments indexes
ALTER TABLE `departments`
  ADD INDEX IF NOT EXISTS `idx_departments_type` (`department_type`),
  ADD INDEX IF NOT EXISTS `idx_departments_active` (`is_active`),
  ADD INDEX IF NOT EXISTS `idx_departments_sector` (`sector_id`);

-- Programs indexes
ALTER TABLE `programs`
  ADD INDEX IF NOT EXISTS `idx_programs_department` (`department_id`),
  ADD INDEX IF NOT EXISTS `idx_programs_active` (`is_active`);

-- Clearance forms indexes
ALTER TABLE `clearance_forms`
  ADD INDEX IF NOT EXISTS `idx_clearance_forms_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_clearance_forms_period` (`academic_year_id`, `semester_id`),
  ADD INDEX IF NOT EXISTS `idx_clearance_forms_status` (`clearance_form_progress`),
  ADD INDEX IF NOT EXISTS `idx_clearance_forms_type` (`clearance_type`);

-- Clearance signatories indexes
ALTER TABLE `clearance_signatories`
  ADD INDEX IF NOT EXISTS `idx_clearance_signatories_form` (`clearance_form_id`),
  ADD INDEX IF NOT EXISTS `idx_clearance_signatories_designation` (`designation_id`),
  ADD INDEX IF NOT EXISTS `idx_clearance_signatories_action` (`action`),
  ADD INDEX IF NOT EXISTS `idx_clearance_signatories_date` (`date_signed`);

-- Students indexes
ALTER TABLE `students`
  ADD INDEX IF NOT EXISTS `idx_students_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_students_program` (`program_id`),
  ADD INDEX IF NOT EXISTS `idx_students_department` (`department_id`),
  ADD INDEX IF NOT EXISTS `idx_students_year_level` (`year_level`),
  ADD INDEX IF NOT EXISTS `idx_students_status` (`enrollment_status`);

-- Faculty indexes
ALTER TABLE `faculty`
  ADD INDEX IF NOT EXISTS `idx_faculty_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_faculty_department` (`department_id`),
  ADD INDEX IF NOT EXISTS `idx_faculty_status` (`employment_status`);

-- Staff indexes
ALTER TABLE `staff`
  ADD INDEX IF NOT EXISTS `idx_staff_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_staff_designation` (`designation_id`),
  ADD INDEX IF NOT EXISTS `idx_staff_department` (`department_id`);

-- Audit logs indexes
ALTER TABLE `audit_logs`
  ADD INDEX IF NOT EXISTS `idx_audit_logs_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_audit_logs_action` (`action`),
  ADD INDEX IF NOT EXISTS `idx_audit_logs_entity` (`entity_type`, `entity_id`),
  ADD INDEX IF NOT EXISTS `idx_audit_logs_created` (`created_at`);

-- User activities indexes
ALTER TABLE `user_activities`
  ADD INDEX IF NOT EXISTS `idx_user_activities_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_user_activities_type` (`activity_type`),
  ADD INDEX IF NOT EXISTS `idx_user_activities_created` (`created_at`);

-- Login sessions indexes
ALTER TABLE `login_sessions`
  ADD INDEX IF NOT EXISTS `idx_login_sessions_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_login_sessions_active` (`is_active`),
  ADD INDEX IF NOT EXISTS `idx_login_sessions_last_activity` (`last_activity`);

-- =====================================================
-- STEP 6: RE-ENABLE FOREIGN KEY CHECKS
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Show all tables
SELECT 'Database tables:' AS '';
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'basedata_db' 
ORDER BY TABLE_NAME;

-- Show all foreign keys
SELECT 'Foreign key constraints:' AS '';
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'basedata_db' 
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Count foreign keys per table
SELECT 'Foreign keys per table:' AS '';
SELECT 
    TABLE_NAME,
    COUNT(*) as FK_COUNT
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'basedata_db' 
  AND REFERENCED_TABLE_NAME IS NOT NULL
GROUP BY TABLE_NAME
ORDER BY FK_COUNT DESC;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT '=================================================' AS '';
SELECT 'PRIMARY KEYS AND FOREIGN KEYS RESTORED SUCCESSFULLY!' AS '';
SELECT '=================================================' AS '';
SELECT CONCAT('Total Tables: ', COUNT(*)) as SUMMARY 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'basedata_db';

SELECT CONCAT('Total Foreign Keys: ', COUNT(*)) as SUMMARY
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'basedata_db' 
  AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT '=================================================' AS '';
SELECT 'All database relationships have been restored.' AS '';
SELECT 'Your database is now ready for use!' AS '';
SELECT '=================================================' AS '';

