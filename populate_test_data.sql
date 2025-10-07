-- =====================================================================
-- Test Data Population Script
-- Database: ver2_online_clearance_db
-- Purpose: Populate tables with test data for development and testing
-- =====================================================================

USE `ver2_online_clearance_db`;

-- =====================================================================
-- STEP 1: USE EXISTING DEPARTMENTS AND PROGRAMS
-- =====================================================================

-- Note: Using existing departments and programs from the database
-- Departments: ICT (1), BAS (2), THM (3), Academic Track (4), TVL (5), General Education (7)
-- Programs: Various programs already exist in the database

-- =====================================================================
-- STEP 2: INSERT SAMPLE DESIGNATIONS
-- =====================================================================

INSERT INTO `designations` (`designation_name`, `description`, `is_active`) VALUES
('School Administrator', 'Overall school administration', 1),
('Program Head', 'Program and department head', 1),
('Regular Staff', 'General staff member', 1),
('Registrar', 'Student records management', 1),
('Librarian', 'Library services', 1),
('Guidance Counselor', 'Student guidance and counseling', 1),
('IT Support', 'Information technology support', 1),
('Maintenance Staff', 'Facility maintenance', 1);

-- =====================================================================
-- STEP 3: INSERT SAMPLE USERS (STUDENTS)
-- =====================================================================

-- College Students (1st to 4th year, 1 section each)
INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `role_id`, `created_at`) VALUES
-- 1st Year College Students
(101, '02000123456', 'Smith02000123456', 'John', 'Smith', 5, NOW()),
(102, '02000123457', 'Johnson02000123457', 'Jane', 'Johnson', 5, NOW()),
(103, '02000123458', 'Williams02000123458', 'Bob', 'Williams', 5, NOW()),
(104, '02000123459', 'Brown02000123459', 'Alice', 'Brown', 5, NOW()),
(105, '02000123460', 'Jones02000123460', 'Charlie', 'Jones', 5, NOW()),

-- 2nd Year College Students
(106, '02000123461', 'Garcia02000123461', 'David', 'Garcia', 5, NOW()),
(107, '02000123462', 'Miller02000123462', 'Emma', 'Miller', 5, NOW()),
(108, '02000123463', 'Davis02000123463', 'Frank', 'Davis', 5, NOW()),
(109, '02000123464', 'Rodriguez02000123464', 'Grace', 'Rodriguez', 5, NOW()),
(110, '02000123465', 'Martinez02000123465', 'Henry', 'Martinez', 5, NOW()),

-- 3rd Year College Students
(111, '02000123466', 'Hernandez02000123466', 'Ivy', 'Hernandez', 5, NOW()),
(112, '02000123467', 'Lopez02000123467', 'Jack', 'Lopez', 5, NOW()),
(113, '02000123468', 'Gonzalez02000123468', 'Kate', 'Gonzalez', 5, NOW()),
(114, '02000123469', 'Wilson02000123469', 'Leo', 'Wilson', 5, NOW()),
(115, '02000123470', 'Anderson02000123470', 'Mia', 'Anderson', 5, NOW()),

-- 4th Year College Students
(116, '02000123471', 'Thomas02000123471', 'Noah', 'Thomas', 5, NOW()),
(117, '02000123472', 'Taylor02000123472', 'Olivia', 'Taylor', 5, NOW()),
(118, '02000123473', 'Moore02000123473', 'Paul', 'Moore', 5, NOW()),
(119, '02000123474', 'Jackson02000123474', 'Quinn', 'Jackson', 5, NOW()),
(120, '02000123475', 'Martin02000123475', 'Rachel', 'Martin', 5, NOW()),

-- Senior High School Students (1st and 2nd year only)
(121, '02000123476', 'Lee02000123476', 'Sam', 'Lee', 5, NOW()),
(122, '02000123477', 'Perez02000123477', 'Tina', 'Perez', 5, NOW()),
(123, '02000123478', 'Thompson02000123478', 'Uma', 'Thompson', 5, NOW()),
(124, '02000123479', 'White02000123479', 'Victor', 'White', 5, NOW()),
(125, '02000123480', 'Harris02000123480', 'Wendy', 'Harris', 5, NOW()),
(126, '02000123481', 'Sanchez02000123481', 'Xavier', 'Sanchez', 5, NOW()),
(127, '02000123482', 'Clark02000123482', 'Yara', 'Clark', 5, NOW()),
(128, '02000123483', 'Ramirez02000123483', 'Zoe', 'Ramirez', 5, NOW()),
(129, '02000123484', 'Lewis02000123484', 'Adam', 'Lewis', 5, NOW()),
(130, '02000123485', 'Robinson02000123485', 'Beth', 'Robinson', 5, NOW());

-- =====================================================================
-- STEP 4: INSERT SAMPLE USERS (FACULTY)
-- =====================================================================

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `role_id`, `created_at`) VALUES
-- Faculty Users
(201, 'LCA001P', 'SmithLCA001P', 'John', 'Smith', 6, NOW()),
(202, 'LCA002P', 'JohnsonLCA002P', 'Jane', 'Johnson', 6, NOW()),
(203, 'LCA003P', 'WilliamsLCA003P', 'Bob', 'Williams', 6, NOW()),
(204, 'LCA004P', 'BrownLCA004P', 'Alice', 'Brown', 6, NOW()),
(205, 'LCA005P', 'JonesLCA005P', 'Charlie', 'Jones', 6, NOW()),
(206, 'LCA006P', 'GarciaLCA006P', 'David', 'Garcia', 6, NOW()),
(207, 'LCA007P', 'MillerLCA007P', 'Emma', 'Miller', 6, NOW()),
(208, 'LCA008P', 'DavisLCA008P', 'Frank', 'Davis', 6, NOW()),
(209, 'LCA009P', 'RodriguezLCA009P', 'Grace', 'Rodriguez', 6, NOW()),
(210, 'LCA010P', 'MartinezLCA010P', 'Henry', 'Martinez', 6, NOW()),
(211, 'LCA011P', 'HernandezLCA011P', 'Ivy', 'Hernandez', 6, NOW()),
(212, 'LCA012P', 'LopezLCA012P', 'Jack', 'Lopez', 6, NOW()),
(213, 'LCA013P', 'GonzalezLCA013P', 'Kate', 'Gonzalez', 6, NOW()),
(214, 'LCA014P', 'WilsonLCA014P', 'Leo', 'Wilson', 6, NOW()),
(215, 'LCA015P', 'AndersonLCA015P', 'Mia', 'Anderson', 6, NOW()),
(216, 'LCA016P', 'ThomasLCA016P', 'Noah', 'Thomas', 6, NOW()),
(217, 'LCA017P', 'TaylorLCA017P', 'Olivia', 'Taylor', 6, NOW()),
(218, 'LCA018P', 'MooreLCA018P', 'Paul', 'Moore', 6, NOW()),
(219, 'LCA019P', 'JacksonLCA019P', 'Quinn', 'Jackson', 6, NOW()),
(220, 'LCA020P', 'MartinLCA020P', 'Rachel', 'Martin', 6, NOW()),
(221, 'LCA021P', 'LeeLCA021P', 'Sam', 'Lee', 6, NOW());

-- =====================================================================
-- STEP 5: INSERT SAMPLE USERS (STAFF)
-- =====================================================================

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `role_id`, `created_at`) VALUES
-- Staff Users
(301, 'LCA101P', 'AdminLCA101P', 'Admin', 'User', 1, NOW()),
(302, 'LCA102P', 'SchoolAdminLCA102P', 'School', 'Administrator', 2, NOW()),
(303, 'LCA103P', 'ProgramHeadLCA103P', 'Program', 'Head', 3, NOW()),
(304, 'LCA104P', 'ProgramHeadLCA104P', 'Program', 'Head2', 3, NOW()),
(305, 'LCA105P', 'ProgramHeadLCA105P', 'Program', 'Head3', 3, NOW()),
(306, 'LCA106P', 'RegularStaffLCA106P', 'Regular', 'Staff1', 4, NOW()),
(307, 'LCA107P', 'RegularStaffLCA107P', 'Regular', 'Staff2', 4, NOW()),
(308, 'LCA108P', 'RegularStaffLCA108P', 'Regular', 'Staff3', 4, NOW()),
(309, 'LCA109P', 'RegularStaffLCA109P', 'Regular', 'Staff4', 4, NOW()),
(310, 'LCA110P', 'RegularStaffLCA110P', 'Regular', 'Staff5', 4, NOW()),
(311, 'LCA111P', 'RegularStaffLCA111P', 'Regular', 'Staff6', 4, NOW()),
(312, 'LCA112P', 'RegularStaffLCA112P', 'Regular', 'Staff7', 4, NOW()),
(313, 'LCA113P', 'RegularStaffLCA113P', 'Regular', 'Staff8', 4, NOW());

-- =====================================================================
-- STEP 6: INSERT SAMPLE STUDENTS
-- =====================================================================

INSERT INTO `students` (`student_id`, `user_id`, `role_id`, `program_id`, `sector`, `sector_id`, `section`, `year_level`, `created_at`, `updated_at`) VALUES
-- 1st Year College Students (ICT Department - BSIT, BSCS, BSCE)
('02000123456', 101, 5, 1, 'College', 1, '1/1', '1st Year', NOW(), NOW()), -- BSIT
('02000123457', 102, 5, 2, 'College', 1, '1/1', '1st Year', NOW(), NOW()), -- BSCS
('02000123458', 103, 5, 3, 'College', 1, '1/1', '1st Year', NOW(), NOW()), -- BSCE
('02000123459', 104, 5, 9, 'College', 1, '1/1', '1st Year', NOW(), NOW()), -- BSBA
('02000123460', 105, 5, 10, 'College', 1, '1/1', '1st Year', NOW(), NOW()), -- BSA

-- 2nd Year College Students
('02000123461', 106, 5, 1, 'College', 1, '2/1', '2nd Year', NOW(), NOW()), -- BSIT
('02000123462', 107, 5, 2, 'College', 1, '2/1', '2nd Year', NOW(), NOW()), -- BSCS
('02000123463', 108, 5, 3, 'College', 1, '2/1', '2nd Year', NOW(), NOW()), -- BSCE
('02000123464', 109, 5, 9, 'College', 1, '2/1', '2nd Year', NOW(), NOW()), -- BSBA
('02000123465', 110, 5, 10, 'College', 1, '2/1', '2nd Year', NOW(), NOW()), -- BSA

-- 3rd Year College Students
('02000123466', 111, 5, 1, 'College', 1, '3/1', '3rd Year', NOW(), NOW()), -- BSIT
('02000123467', 112, 5, 2, 'College', 1, '3/1', '3rd Year', NOW(), NOW()), -- BSCS
('02000123468', 113, 5, 3, 'College', 1, '3/1', '3rd Year', NOW(), NOW()), -- BSCE
('02000123469', 114, 5, 9, 'College', 1, '3/1', '3rd Year', NOW(), NOW()), -- BSBA
('02000123470', 115, 5, 10, 'College', 1, '3/1', '3rd Year', NOW(), NOW()), -- BSA

-- 4th Year College Students
('02000123471', 116, 5, 1, 'College', 1, '4/1', '4th Year', NOW(), NOW()), -- BSIT
('02000123472', 117, 5, 2, 'College', 1, '4/1', '4th Year', NOW(), NOW()), -- BSCS
('02000123473', 118, 5, 3, 'College', 1, '4/1', '4th Year', NOW(), NOW()), -- BSCE
('02000123474', 119, 5, 9, 'College', 1, '4/1', '4th Year', NOW(), NOW()), -- BSBA
('02000123475', 120, 5, 10, 'College', 1, '4/1', '4th Year', NOW(), NOW()), -- BSA

-- Senior High School Students (1st and 2nd year only)
-- Academic Track Programs
('02000123476', 121, 5, 12, 'Senior High School', 2, '1/1', '1st Year', NOW(), NOW()), -- ABM
('02000123477', 122, 5, 13, 'Senior High School', 2, '1/1', '1st Year', NOW(), NOW()), -- STEM
('02000123478', 123, 5, 14, 'Senior High School', 2, '1/1', '1st Year', NOW(), NOW()), -- HUMSS
('02000123479', 124, 5, 15, 'Senior High School', 2, '1/1', '1st Year', NOW(), NOW()), -- General Academic
('02000123480', 125, 5, 16, 'Senior High School', 2, '1/1', '1st Year', NOW(), NOW()), -- Digital Arts
('02000123481', 126, 5, 12, 'Senior High School', 2, '2/1', '2nd Year', NOW(), NOW()), -- ABM
('02000123482', 127, 5, 13, 'Senior High School', 2, '2/1', '2nd Year', NOW(), NOW()), -- STEM
('02000123483', 128, 5, 14, 'Senior High School', 2, '2/1', '2nd Year', NOW(), NOW()), -- HUMSS
('02000123484', 129, 5, 15, 'Senior High School', 2, '2/1', '2nd Year', NOW(), NOW()), -- General Academic
('02000123485', 130, 5, 16, 'Senior High School', 2, '2/1', '2nd Year', NOW(), NOW()); -- Digital Arts

-- =====================================================================
-- STEP 7: INSERT SAMPLE FACULTY
-- =====================================================================

INSERT INTO `faculty` (`employee_number`, `user_id`, `role_id`, `employment_status`, `department_id`, `sector`, `sector_id`, `created_at`, `updated_at`) VALUES
-- College Faculty (3 per department, 1 per employment status)
-- ICT Department
('LCA001P', 201, 6, 'Full Time', 1, 'Faculty', 3, NOW(), NOW()),
('LCA002P', 202, 6, 'Part Time', 1, 'Faculty', 3, NOW(), NOW()),
('LCA003P', 203, 6, 'Part Time - Full Load', 1, 'Faculty', 3, NOW(), NOW()),

-- Business, Arts & Science Department
('LCA004P', 204, 6, 'Full Time', 2, 'Faculty', 3, NOW(), NOW()),
('LCA005P', 205, 6, 'Part Time', 2, 'Faculty', 3, NOW(), NOW()),
('LCA006P', 206, 6, 'Part Time - Full Load', 2, 'Faculty', 3, NOW(), NOW()),

-- Tourism & Hospitality Management Department
('LCA007P', 207, 6, 'Full Time', 3, 'Faculty', 3, NOW(), NOW()),
('LCA008P', 208, 6, 'Part Time', 3, 'Faculty', 3, NOW(), NOW()),
('LCA009P', 209, 6, 'Part Time - Full Load', 3, 'Faculty', 3, NOW(), NOW()),

-- Senior High School Faculty
-- Academic Track Department
('LCA010P', 210, 6, 'Full Time', 4, 'Faculty', 3, NOW(), NOW()),
('LCA011P', 211, 6, 'Part Time', 4, 'Faculty', 3, NOW(), NOW()),
('LCA012P', 212, 6, 'Part Time - Full Load', 4, 'Faculty', 3, NOW(), NOW()),

-- TVL Department
('LCA013P', 213, 6, 'Full Time', 5, 'Faculty', 3, NOW(), NOW()),
('LCA014P', 214, 6, 'Part Time', 5, 'Faculty', 3, NOW(), NOW()),
('LCA015P', 215, 6, 'Part Time - Full Load', 5, 'Faculty', 3, NOW(), NOW()),

-- General Education Department
('LCA016P', 216, 6, 'Full Time', 7, 'Faculty', 3, NOW(), NOW()),
('LCA017P', 217, 6, 'Part Time', 7, 'Faculty', 3, NOW(), NOW()),
('LCA018P', 218, 6, 'Part Time - Full Load', 7, 'Faculty', 3, NOW(), NOW()),

-- Additional Faculty
('LCA019P', 219, 6, 'Full Time', 1, 'Faculty', 3, NOW(), NOW()),
('LCA020P', 220, 6, 'Part Time', 2, 'Faculty', 3, NOW(), NOW()),
('LCA021P', 221, 6, 'Part Time - Full Load', 3, 'Faculty', 3, NOW(), NOW());

-- =====================================================================
-- STEP 8: INSERT SAMPLE STAFF
-- =====================================================================

INSERT INTO `staff` (`employee_number`, `user_id`, `role_id`, `designation_id`, `staff_category`, `department_id`, `employment_status`, `created_at`, `updated_at`) VALUES
-- Admin
('LCA101P', 301, 1, 1, 'School Administrator', 1, 'Full Time', NOW(), NOW()),

-- School Administrator
('LCA102P', 302, 2, 9, 'School Administrator', 1, 'Full Time', NOW(), NOW()),

-- Program Heads (multiple entries for different departments within same sector)
-- College Sector Program Heads
('LCA103P', 303, 3, 8, 'Program Head', 1, 'Full Time', NOW(), NOW()), -- ICT Department
('LCA104P', 304, 3, 8, 'Program Head', 2, 'Full Time', NOW(), NOW()), -- Business, Arts & Science Department
('LCA105P', 305, 3, 8, 'Program Head', 3, 'Full Time', NOW(), NOW()), -- Tourism & Hospitality Management Department

-- Senior High School Sector Program Heads
('LCA106P', 306, 3, 8, 'Program Head', 4, 'Full Time', NOW(), NOW()), -- Academic Track Department
('LCA107P', 307, 3, 8, 'Program Head', 5, 'Full Time', NOW(), NOW()), -- TVL Department

-- Faculty Sector Program Head
('LCA108P', 308, 3, 8, 'Program Head', 7, 'Full Time', NOW(), NOW()), -- General Education Department

-- Regular Staff (1 per designation)
('LCA109P', 309, 4, 1, 'Regular Staff', 1, 'Full Time', NOW(), NOW()), -- Registrar
('LCA110P', 310, 4, 3, 'Regular Staff', 1, 'Full Time', NOW(), NOW()), -- Librarian
('LCA111P', 311, 4, 14, 'Regular Staff', 1, 'Full Time', NOW(), NOW()), -- Guidance Counselor
('LCA112P', 312, 4, 4, 'Regular Staff', 1, 'Full Time', NOW(), NOW()), -- IT Support
('LCA113P', 313, 4, 5, 'Regular Staff', 1, 'Full Time', NOW(), NOW()); -- Building Administrator

-- =====================================================================
-- STEP 9: INSERT SAMPLE ACADEMIC YEARS
-- =====================================================================

INSERT INTO `academic_years` (`year`, `is_active`, `created_at`, `updated_at`) VALUES
('2024-2025', 1, NOW(), NOW()),
('2023-2024', 0, NOW(), NOW()),
('2022-2023', 0, NOW(), NOW());

-- =====================================================================
-- STEP 10: INSERT SAMPLE SEMESTERS
-- =====================================================================

INSERT INTO `semesters` (`academic_year_id`, `semester_name`, `is_active`, `is_generation`, `created_at`, `updated_at`) VALUES
(1, '1st', 1, 1, NOW(), NOW()),
(1, '2nd', 0, 0, NOW(), NOW()),
(1, 'Summer', 0, 0, NOW(), NOW());

-- =====================================================================
-- STEP 11: INSERT SAMPLE CLEARANCE PERIODS
-- =====================================================================

INSERT INTO `clearance_periods` (`academic_year_id`, `semester_id`, `sector`, `period_name`, `start_date`, `end_date`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'College', 'College Clearance Period 1', '2024-11-01', '2024-12-15', 1, 'Active', NOW(), NOW()),
(1, 1, 'Senior High School', 'Senior High Clearance Period 1', '2024-11-01', '2024-12-15', 1, 'Active', NOW(), NOW()),
(1, 1, 'Faculty', 'Faculty Clearance Period 1', '2024-11-01', '2024-12-15', 1, 'Active', NOW(), NOW());

-- =====================================================================
-- STEP 12: INSERT SAMPLE SECTOR SIGNATORY ASSIGNMENTS
-- =====================================================================

INSERT INTO `sector_signatory_assignments` (`clearance_type`, `user_id`, `designation_id`, `is_program_head`, `department_id`, `is_required_first`, `is_required_last`, `is_active`, `created_at`, `updated_at`) VALUES
-- College Sector Assignments
('College', 303, 8, 1, 1, 0, 0, 1, NOW(), NOW()), -- Program Head ICT
('College', 304, 8, 1, 2, 0, 0, 1, NOW(), NOW()), -- Program Head BAS
('College', 305, 8, 1, 3, 0, 0, 1, NOW(), NOW()), -- Program Head THM
('College', 309, 1, 0, 1, 1, 0, 1, NOW(), NOW()), -- Registrar (required first)
('College', 310, 3, 0, 1, 0, 0, 1, NOW(), NOW()), -- Librarian
('College', 311, 14, 0, 1, 0, 0, 1, NOW(), NOW()), -- Guidance Counselor
('College', 312, 4, 0, 1, 0, 0, 1, NOW(), NOW()), -- IT Support
('College', 313, 5, 0, 1, 0, 1, 1, NOW(), NOW()), -- Building Administrator (required last)

-- Senior High School Sector Assignments
('Senior High School', 306, 8, 1, 4, 0, 0, 1, NOW(), NOW()), -- Program Head Academic Track
('Senior High School', 307, 8, 1, 5, 0, 0, 1, NOW(), NOW()), -- Program Head TVL
('Senior High School', 309, 1, 0, 4, 1, 0, 1, NOW(), NOW()), -- Registrar (required first)
('Senior High School', 310, 3, 0, 4, 0, 0, 1, NOW(), NOW()), -- Librarian
('Senior High School', 311, 14, 0, 4, 0, 0, 1, NOW(), NOW()), -- Guidance Counselor
('Senior High School', 312, 4, 0, 4, 0, 0, 1, NOW(), NOW()), -- IT Support
('Senior High School', 313, 5, 0, 4, 0, 1, 1, NOW(), NOW()), -- Building Administrator (required last)

-- Faculty Sector Assignments
('Faculty', 308, 8, 1, 7, 0, 0, 1, NOW(), NOW()), -- Program Head General Education
('Faculty', 309, 1, 0, 7, 1, 0, 1, NOW(), NOW()), -- Registrar (required first)
('Faculty', 310, 3, 0, 7, 0, 0, 1, NOW(), NOW()), -- Librarian
('Faculty', 311, 14, 0, 7, 0, 0, 1, NOW(), NOW()), -- Guidance Counselor
('Faculty', 312, 4, 0, 7, 0, 0, 1, NOW(), NOW()), -- IT Support
('Faculty', 313, 5, 0, 7, 0, 1, 1, NOW(), NOW()); -- Building Administrator (required last)

-- =====================================================================
-- STEP 13: SAMPLE DATA COMPLETE
-- =====================================================================

-- =====================================================================
-- VERIFICATION QUERIES
-- =====================================================================

-- Check total records inserted
SELECT 'Users' as table_name, COUNT(*) as total_records FROM users
UNION ALL
SELECT 'Students', COUNT(*) FROM students
UNION ALL
SELECT 'Faculty', COUNT(*) FROM faculty
UNION ALL
SELECT 'Staff', COUNT(*) FROM staff
UNION ALL
SELECT 'Departments', COUNT(*) FROM departments
UNION ALL
SELECT 'Programs', COUNT(*) FROM programs
UNION ALL
SELECT 'Designations', COUNT(*) FROM designations
UNION ALL
SELECT 'Academic Years', COUNT(*) FROM academic_years
UNION ALL
SELECT 'Semesters', COUNT(*) FROM semesters
UNION ALL
SELECT 'Clearance Periods', COUNT(*) FROM clearance_periods
UNION ALL
SELECT 'Clearance Forms', COUNT(*) FROM clearance_forms
UNION ALL
SELECT 'Clearance Signatories', COUNT(*) FROM clearance_signatories
UNION ALL
SELECT 'Sector Signatory Assignments', COUNT(*) FROM sector_signatory_assignments
UNION ALL
SELECT 'Sector Clearance Settings', COUNT(*) FROM sector_clearance_settings
UNION ALL
SELECT 'User Activities', COUNT(*) FROM user_activities;

-- =====================================================================
-- END OF SCRIPT
-- =====================================================================
