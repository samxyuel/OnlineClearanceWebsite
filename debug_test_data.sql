-- =====================================================================
-- Debug Test Data Script
-- Database: ver2_online_clearance_db
-- Purpose: Debug why students, faculty, and staff tables aren't populating
-- =====================================================================

USE `ver2_online_clearance_db`;

-- =====================================================================
-- STEP 1: CHECK EXISTING DATA
-- =====================================================================

-- Check if required tables have data
SELECT 'Programs' as table_name, COUNT(*) as count FROM programs
UNION ALL
SELECT 'Departments', COUNT(*) FROM departments  
UNION ALL
SELECT 'Designations', COUNT(*) FROM designations
UNION ALL
SELECT 'Sectors', COUNT(*) FROM sectors
UNION ALL
SELECT 'Users', COUNT(*) FROM users;

-- =====================================================================
-- STEP 2: CHECK SPECIFIC FOREIGN KEY VALUES
-- =====================================================================

-- Check if specific programs exist
SELECT program_id, program_name FROM programs WHERE program_id IN (1,2,3,9,10,12,13,14,15,16);

-- Check if specific departments exist
SELECT department_id, department_name FROM departments WHERE department_id IN (1,2,3,4,5,7);

-- Check if specific designations exist
SELECT designation_id, designation_name FROM designations;

-- =====================================================================
-- STEP 3: TRY INSERTING ONE RECORD AT A TIME
-- =====================================================================

-- Try inserting one student
INSERT INTO `students` (`user_id`, `program_id`, `sector_id`, `section`, `year_level`, `created_at`, `updated_at`) 
VALUES (101, 1, 1, '1', '1st', NOW(), NOW());

-- Try inserting one faculty
INSERT INTO `faculty` (`employee_number`, `user_id`, `employment_status`, `department_id`, `sector_id`, `created_at`, `updated_at`) 
VALUES ('LCA001P', 201, 'Full Time', 1, 3, NOW(), NOW());

-- Try inserting one staff
INSERT INTO `staff` (`employee_number`, `user_id`, `designation_id`, `department_id`, `sector_id`, `created_at`, `updated_at`) 
VALUES ('LCA101P', 301, 1, 1, 1, NOW(), NOW());

-- =====================================================================
-- STEP 4: VERIFY INSERTS
-- =====================================================================

-- Check if the test records were inserted
SELECT 'Students' as table_name, COUNT(*) as count FROM students
UNION ALL
SELECT 'Faculty', COUNT(*) FROM faculty
UNION ALL  
SELECT 'Staff', COUNT(*) FROM staff;
