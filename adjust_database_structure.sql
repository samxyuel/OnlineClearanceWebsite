-- =====================================================================
-- Database Structure Adjustments Script
-- Database: ver2_online_clearance_db
-- Purpose: Add role_id to users table and connect tables properly
-- =====================================================================

USE `ver2_online_clearance_db`;

-- =====================================================================
-- STEP 1: ADD role_id COLUMN TO users TABLE
-- =====================================================================

-- Add role_id column to users table
ALTER TABLE `users` 
ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD KEY `idx_users_role` (`role_id`);

-- =====================================================================
-- STEP 2: ADD role_id COLUMN TO students TABLE
-- =====================================================================

-- Add role_id column to students table
ALTER TABLE `students` 
ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD KEY `idx_students_role` (`role_id`);

-- =====================================================================
-- STEP 3: ADD role_id COLUMN TO faculty TABLE
-- =====================================================================

-- Add role_id column to faculty table
ALTER TABLE `faculty` 
ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD KEY `idx_faculty_role` (`role_id`);

-- =====================================================================
-- STEP 4: ADD role_id COLUMN TO staff TABLE
-- =====================================================================

-- Add role_id column to staff table
ALTER TABLE `staff` 
ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD KEY `idx_staff_role` (`role_id`);

-- =====================================================================
-- STEP 5: ADD sector_id COLUMN TO students TABLE
-- =====================================================================

-- Add sector_id column to students table (to properly link to sectors)
ALTER TABLE `students` 
ADD COLUMN `sector_id` int(11) DEFAULT NULL AFTER `sector`,
ADD KEY `idx_students_sector_id` (`sector_id`);

-- =====================================================================
-- STEP 6: ADD sector_id COLUMN TO faculty TABLE
-- =====================================================================

-- Add sector_id column to faculty table (to properly link to sectors)
ALTER TABLE `faculty` 
ADD COLUMN `sector_id` int(11) DEFAULT NULL AFTER `sector`,
ADD KEY `idx_faculty_sector_id` (`sector_id`);

-- =====================================================================
-- STEP 7: ADD FOREIGN KEY CONSTRAINTS
-- =====================================================================

-- Add foreign key constraint for users.role_id
ALTER TABLE `users` 
ADD CONSTRAINT `users_ibfk_role` 
FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key constraint for students.role_id
ALTER TABLE `students` 
ADD CONSTRAINT `students_ibfk_role` 
FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key constraint for faculty.role_id
ALTER TABLE `faculty` 
ADD CONSTRAINT `faculty_ibfk_role` 
FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key constraint for staff.role_id
ALTER TABLE `staff` 
ADD CONSTRAINT `staff_ibfk_role` 
FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key constraint for students.sector_id
ALTER TABLE `students` 
ADD CONSTRAINT `students_ibfk_sector` 
FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key constraint for faculty.sector_id
ALTER TABLE `faculty` 
ADD CONSTRAINT `faculty_ibfk_sector` 
FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================================
-- STEP 8: UPDATE EXISTING DATA WITH PROPER ROLE_IDs
-- =====================================================================

-- Update users table with role_id based on existing data
-- Admin user gets role_id = 1 (Admin)
UPDATE `users` SET `role_id` = 1 WHERE `username` = 'admin';

-- Update students with role_id = 5 (Student)
UPDATE `students` SET `role_id` = 5;

-- Update faculty with role_id = 6 (Faculty)
UPDATE `faculty` SET `role_id` = 6;

-- Update staff with appropriate role_ids based on staff_category
UPDATE `staff` SET `role_id` = 2 WHERE `staff_category` = 'School Administrator';
UPDATE `staff` SET `role_id` = 3 WHERE `staff_category` = 'Program Head';
UPDATE `staff` SET `role_id` = 4 WHERE `staff_category` = 'Regular Staff';

-- =====================================================================
-- STEP 9: UPDATE EXISTING DATA WITH PROPER SECTOR_IDs
-- =====================================================================

-- Update students with sector_id based on their sector enum
UPDATE `students` SET `sector_id` = 1 WHERE `sector` = 'College';
UPDATE `students` SET `sector_id` = 2 WHERE `sector` = 'Senior High School';

-- Update faculty with sector_id = 3 (Faculty)
UPDATE `faculty` SET `sector_id` = 3;

-- =====================================================================
-- STEP 10: ADD USERS TO STAFF, STUDENTS, AND FACULTY TABLES
-- =====================================================================

-- Create users for staff members (if they don't exist)
-- Note: This assumes you have staff data but no corresponding users
-- You may need to adjust this based on your actual data

-- Example: Create a sample staff user (adjust as needed)
INSERT IGNORE INTO `users` (`username`, `password`, `email`, `first_name`, `last_name`, `status`, `must_change_password`, `can_apply`, `role_id`) VALUES
('LCA1001P', '$2y$10$cS1Lk6GOXeKSOmqguvw6lO/kuHy844NX1Kgt8rInKgCn5dgWTdN9K', 'admin@lca.edu.ph', 'System', 'Administrator', 'active', 0, 1, 2);

-- Link the admin user to staff table if not already linked
INSERT IGNORE INTO `staff` (`employee_number`, `user_id`, `designation_id`, `staff_category`, `employment_status`, `is_active`, `role_id`) VALUES
('LCA1001P', (SELECT user_id FROM users WHERE username = 'LCA1001P'), 9, 'School Administrator', 'Full Time', 1, 2);

-- =====================================================================
-- STEP 11: VERIFY DATA INTEGRITY
-- =====================================================================

-- Check if all users have role_id
SELECT 'Users without role_id:' as Check_Type, COUNT(*) as Count 
FROM users WHERE role_id IS NULL;

-- Check if all students have role_id
SELECT 'Students without role_id:' as Check_Type, COUNT(*) as Count 
FROM students WHERE role_id IS NULL;

-- Check if all faculty have role_id
SELECT 'Faculty without role_id:' as Check_Type, COUNT(*) as Count 
FROM faculty WHERE role_id IS NULL;

-- Check if all staff have role_id
SELECT 'Staff without role_id:' as Check_Type, COUNT(*) as Count 
FROM staff WHERE role_id IS NULL;

-- Check if all students have sector_id
SELECT 'Students without sector_id:' as Check_Type, COUNT(*) as Count 
FROM students WHERE sector_id IS NULL;

-- Check if all faculty have sector_id
SELECT 'Faculty without sector_id:' as Check_Type, COUNT(*) as Count 
FROM faculty WHERE sector_id IS NULL;

-- =====================================================================
-- STEP 12: CREATE USEFUL VIEWS FOR EASY ACCESS
-- =====================================================================

-- Create a view for all users with their roles
CREATE OR REPLACE VIEW `user_roles_view` AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.email,
    u.status,
    r.role_name,
    r.description as role_description
FROM users u
LEFT JOIN roles r ON u.role_id = r.role_id;

-- Create a view for students with their roles and sectors
CREATE OR REPLACE VIEW `student_details_view` AS
SELECT 
    s.student_id,
    s.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.email,
    s.program_id,
    p.program_name,
    s.section,
    s.year_level,
    s.sector,
    s.sector_id,
    sec.sector_name,
    r.role_name,
    d.department_name
FROM students s
LEFT JOIN users u ON s.user_id = u.user_id
LEFT JOIN programs p ON s.program_id = p.program_id
LEFT JOIN sectors sec ON s.sector_id = sec.sector_id
LEFT JOIN roles r ON s.role_id = r.role_id
LEFT JOIN departments d ON p.department_id = d.department_id;

-- Create a view for faculty with their roles and sectors
CREATE OR REPLACE VIEW `faculty_details_view` AS
SELECT 
    f.employee_number,
    f.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.email,
    f.employment_status,
    f.sector,
    f.sector_id,
    sec.sector_name,
    r.role_name,
    d.department_name
FROM faculty f
LEFT JOIN users u ON f.user_id = u.user_id
LEFT JOIN sectors sec ON f.sector_id = sec.sector_id
LEFT JOIN roles r ON f.role_id = r.role_id
LEFT JOIN departments d ON f.department_id = d.department_id;

-- Create a view for staff with their roles
CREATE OR REPLACE VIEW `staff_details_view` AS
SELECT 
    s.employee_number,
    s.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.email,
    s.staff_category,
    s.employment_status,
    r.role_name,
    des.designation_name,
    d.department_name
FROM staff s
LEFT JOIN users u ON s.user_id = u.user_id
LEFT JOIN roles r ON s.role_id = r.role_id
LEFT JOIN designations des ON s.designation_id = des.designation_id
LEFT JOIN departments d ON s.department_id = d.department_id;

-- =====================================================================
-- COMPLETION MESSAGE
-- =====================================================================

SELECT 'Database structure adjustments completed successfully!' as Message;
SELECT 'All tables now have proper role_id and sector_id connections' as Status;
SELECT 'Views created for easy data access' as Views_Info;
SELECT 'Run the verification queries above to check data integrity' as Next_Step;
