-- =====================================================
-- RESTORE FOREIGN KEYS - ALWAYSDATA VERSION
-- Online Clearance Database - Foreign Key Restoration
-- NO VERIFICATION QUERIES (for restricted hosting)
-- =====================================================

-- CHANGE THIS TO YOUR ALWAYSDATA DATABASE NAME!
USE `basedata_db`;

-- Disable foreign key checks temporarily for clean execution
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- ADD ALL FOREIGN KEYS
-- =====================================================

-- 1. audit_logs
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. bulk_operations
ALTER TABLE `bulk_operations`
  ADD CONSTRAINT `bulk_operations_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- 3. clearance_forms
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_2` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_3` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- 4. clearance_periods
ALTER TABLE `clearance_periods`
  ADD CONSTRAINT `clearance_periods_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_periods_ibfk_2` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- 5. clearance_requirements
ALTER TABLE `clearance_requirements`
  ADD CONSTRAINT `clearance_requirements_ibfk_1` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE;

-- 6. clearance_signatories
ALTER TABLE `clearance_signatories`
  ADD CONSTRAINT `FK_SignatoryRejectionReason` 
  FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`),
  ADD CONSTRAINT `clearance_signatories_ibfk_1` 
  FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_2` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_3` 
  FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- 7. clearance_signatories_new
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

-- 8. signatory_actions
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

-- 9. data_versions
ALTER TABLE `data_versions`
  ADD CONSTRAINT `data_versions_ibfk_1` 
  FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`file_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `data_versions_ibfk_2` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- 10. departments
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_sector_id` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- 11. faculty
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `faculty_ibfk_2` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- 12. file_uploads
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- 13. login_sessions
ALTER TABLE `login_sessions`
  ADD CONSTRAINT `login_sessions_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE;

-- 14. operation_logs
ALTER TABLE `operation_logs`
  ADD CONSTRAINT `operation_logs_ibfk_1` 
  FOREIGN KEY (`operation_id`) REFERENCES `bulk_operations` (`operation_id`) 
  ON DELETE CASCADE;

-- 15. programs
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- 16. rejection_remarks
ALTER TABLE `rejection_remarks`
  ADD CONSTRAINT `rejection_remarks_ibfk_1` 
  FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories` (`signatory_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `rejection_remarks_ibfk_2` 
  FOREIGN KEY (`reason_id`) REFERENCES `rejection_reasons` (`reason_id`) 
  ON DELETE CASCADE;

-- 17. role_permissions
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

-- 18. scope_settings
ALTER TABLE `scope_settings`
  ADD CONSTRAINT `fk_scope_settings_first_designation` 
  FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scope_settings_last_designation` 
  FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- 19. sector_clearance_settings
ALTER TABLE `sector_clearance_settings`
  ADD CONSTRAINT `sector_clearance_settings_ibfk_1` 
  FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL,
  ADD CONSTRAINT `sector_clearance_settings_ibfk_2` 
  FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE SET NULL;

-- 20. sector_signatory_assignments
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

-- 21. semesters
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE;

-- 22. signatory_assignments
ALTER TABLE `signatory_assignments`
  ADD CONSTRAINT `fk_sa_dept` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_sa_desig` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`),
  ADD CONSTRAINT `fk_sa_sector` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`),
  ADD CONSTRAINT `fk_sa_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

-- 23. staff
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

-- 24. staff_designation_assignments
ALTER TABLE `staff_designation_assignments`
  ADD CONSTRAINT `fk_sda_assigned_by` 
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_department` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_sector` 
  FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sda_staff` 
  FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- 25. students
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

-- 26. system_settings
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` 
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL;

-- 27. user_activities
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- 28. user_roles
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

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Done! Foreign keys have been restored.
-- Check phpMyAdmin > Your Table > Structure > Relation View to verify.








