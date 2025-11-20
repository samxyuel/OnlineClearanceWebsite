-- =====================================================
-- FIX AND RESTORE FOREIGN KEYS
-- This script fixes common issues before adding FKs
-- =====================================================

USE `basedata_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: ENSURE PRIMARY KEYS EXIST
-- =====================================================

-- Make sure users has primary key
ALTER TABLE `users` 
  MODIFY `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`user_id`);

-- Make sure other key tables have primary keys
ALTER TABLE `academic_years` 
  MODIFY `academic_year_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`academic_year_id`);

ALTER TABLE `semesters` 
  MODIFY `semester_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`semester_id`);

ALTER TABLE `departments` 
  MODIFY `department_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`department_id`);

ALTER TABLE `programs` 
  MODIFY `program_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`program_id`);

ALTER TABLE `designations` 
  MODIFY `designation_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`designation_id`);

ALTER TABLE `roles` 
  MODIFY `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`role_id`);

ALTER TABLE `permissions` 
  MODIFY `permission_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`permission_id`);

ALTER TABLE `rejection_reasons` 
  MODIFY `reason_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`reason_id`);

ALTER TABLE `sectors` 
  MODIFY `sector_id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`sector_id`);

-- =====================================================
-- STEP 2: FIX COLUMN TYPES TO MATCH EXACTLY
-- =====================================================

-- Fix students.user_id to match users.user_id
ALTER TABLE `students` 
  MODIFY `user_id` INT(11) NULL;

-- Fix audit_logs.user_id
ALTER TABLE `audit_logs` 
  MODIFY `user_id` INT(11) NULL;

-- Fix faculty.user_id
ALTER TABLE `faculty` 
  MODIFY `user_id` INT(11) NULL;

-- Fix staff.user_id
ALTER TABLE `staff` 
  MODIFY `user_id` INT(11) NULL;

-- Fix clearance_forms columns
ALTER TABLE `clearance_forms` 
  MODIFY `user_id` INT(11) NULL,
  MODIFY `academic_year_id` INT(11) NULL,
  MODIFY `semester_id` INT(11) NULL;

-- Fix other foreign key columns
ALTER TABLE `programs` 
  MODIFY `department_id` INT(11) NULL;

ALTER TABLE `semesters` 
  MODIFY `academic_year_id` INT(11) NULL;

ALTER TABLE `clearance_periods` 
  MODIFY `academic_year_id` INT(11) NULL,
  MODIFY `semester_id` INT(11) NULL;

ALTER TABLE `clearance_signatories` 
  MODIFY `clearance_form_id` VARCHAR(20) NULL,
  MODIFY `designation_id` INT(11) NULL,
  MODIFY `actual_user_id` INT(11) NULL,
  MODIFY `reason_id` INT(11) NULL;

ALTER TABLE `clearance_requirements` 
  MODIFY `designation_id` INT(11) NOT NULL;

-- =====================================================
-- STEP 3: CONVERT TABLES TO INNODB (if not already)
-- =====================================================

-- Try to convert to InnoDB (may fail on some hosting, that's OK)
ALTER TABLE `users` ENGINE=InnoDB;
ALTER TABLE `students` ENGINE=InnoDB;
ALTER TABLE `faculty` ENGINE=InnoDB;
ALTER TABLE `staff` ENGINE=InnoDB;
ALTER TABLE `audit_logs` ENGINE=InnoDB;
ALTER TABLE `clearance_forms` ENGINE=InnoDB;
ALTER TABLE `clearance_signatories` ENGINE=InnoDB;
ALTER TABLE `departments` ENGINE=InnoDB;
ALTER TABLE `programs` ENGINE=InnoDB;
ALTER TABLE `academic_years` ENGINE=InnoDB;
ALTER TABLE `semesters` ENGINE=InnoDB;
ALTER TABLE `designations` ENGINE=InnoDB;
ALTER TABLE `roles` ENGINE=InnoDB;
ALTER TABLE `permissions` ENGINE=InnoDB;
ALTER TABLE `rejection_reasons` ENGINE=InnoDB;
ALTER TABLE `clearance_periods` ENGINE=InnoDB;
ALTER TABLE `clearance_requirements` ENGINE=InnoDB;

-- =====================================================
-- STEP 4: ADD FOREIGN KEYS (Core Relationships First)
-- =====================================================

-- Students to Users
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE;

-- Faculty to Users
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Staff to Users
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE;

-- Programs to Departments
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- Semesters to Academic Years
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE;

-- Students to Programs
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_2` 
  FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) 
  ON DELETE CASCADE;

-- Students to Departments
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_3` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- Faculty to Departments
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_2` 
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) 
  ON DELETE CASCADE;

-- Clearance Forms to Users
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Clearance Forms to Academic Years
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_2` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE;

-- Clearance Forms to Semesters
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_3` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- Clearance Periods
ALTER TABLE `clearance_periods`
  ADD CONSTRAINT `clearance_periods_ibfk_1` 
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_periods_ibfk_2` 
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) 
  ON DELETE CASCADE;

-- Clearance Requirements
ALTER TABLE `clearance_requirements`
  ADD CONSTRAINT `clearance_requirements_ibfk_1` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE;

-- Clearance Signatories
ALTER TABLE `clearance_signatories`
  ADD CONSTRAINT `clearance_signatories_ibfk_1` 
  FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_2` 
  FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) 
  ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_signatories_ibfk_3` 
  FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Audit Logs (may fail if column type doesn't match - that's OK)
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- Done! Check for any errors above.
-- If some FKs failed, run the diagnostic script to see why.













