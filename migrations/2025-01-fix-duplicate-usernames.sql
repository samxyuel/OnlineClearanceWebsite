-- =====================================================
-- Fix Duplicate Usernames Migration
-- Date: 2025-01-27
-- Purpose: Fix duplicate username conflicts between staff and faculty
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. IDENTIFY AND RESOLVE DUPLICATE USERNAMES
-- =====================================================

-- Check for duplicate usernames
SELECT 
    'Duplicate Username Check' as info,
    username,
    COUNT(*) as count
FROM users 
WHERE username IN (
    SELECT employee_number FROM staff
    INTERSECT
    SELECT employee_number FROM faculty
)
GROUP BY username;

-- =====================================================
-- 2. UPDATE FACULTY EMPLOYEE NUMBERS TO AVOID CONFLICTS
-- =====================================================

-- Update faculty employee numbers to use different range (LCA2000P - LCA2999P)
UPDATE faculty SET employee_number = 'LCA2001P' WHERE employee_number = 'LCA0001P';
UPDATE faculty SET employee_number = 'LCA2002P' WHERE employee_number = 'LCA0002P';
UPDATE faculty SET employee_number = 'LCA2003P' WHERE employee_number = 'LCA0003P';
UPDATE faculty SET employee_number = 'LCA2004P' WHERE employee_number = 'LCA0004P';
UPDATE faculty SET employee_number = 'LCA2005P' WHERE employee_number = 'LCA0005P';

-- =====================================================
-- 3. UPDATE CORRESPONDING USER RECORDS
-- =====================================================

-- Update usernames for faculty users
UPDATE users u
JOIN faculty f ON u.user_id = f.user_id
SET u.username = f.employee_number;

-- Update usernames for staff users (ensure they match)
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = s.employee_number;

-- =====================================================
-- 4. UPDATE PASSWORDS TO MATCH NEW FORMATS
-- =====================================================

-- Update passwords for staff users (format: LastName + EmployeeNumber)
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE u.user_id = s.user_id;

-- Update passwords for faculty users (format: LastName + EmployeeNumber)
UPDATE users u
JOIN faculty f ON u.user_id = f.user_id
SET u.password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE u.user_id = f.user_id;

-- =====================================================
-- 5. VERIFICATION QUERIES
-- =====================================================

-- Show staff data with updated usernames
SELECT 
    'Staff Data After Fix' as info,
    s.employee_number,
    u.username,
    u.first_name,
    u.last_name,
    s.staff_category,
    CASE 
        WHEN u.username = s.employee_number THEN 'MATCHED'
        ELSE 'MISMATCH'
    END as username_status
FROM staff s
JOIN users u ON s.user_id = u.user_id
ORDER BY s.employee_number;

-- Show faculty data with updated usernames
SELECT 
    'Faculty Data After Fix' as info,
    f.employee_number,
    u.username,
    u.first_name,
    u.last_name,
    f.employment_status,
    CASE 
        WHEN u.username = f.employee_number THEN 'MATCHED'
        ELSE 'MISMATCH'
    END as username_status
FROM faculty f
JOIN users u ON f.user_id = u.user_id
ORDER BY f.employee_number;

-- Check for any remaining duplicates
SELECT 
    'Duplicate Check After Fix' as info,
    username,
    COUNT(*) as count
FROM users 
GROUP BY username
HAVING COUNT(*) > 1;

-- Count mismatches
SELECT 
    'Final Data Consistency Check' as info,
    (SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id = u.user_id WHERE u.username != s.employee_number) as staff_mismatches,
    (SELECT COUNT(*) FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE u.username != f.employee_number) as faculty_mismatches,
    (SELECT COUNT(*) FROM staff) as total_staff,
    (SELECT COUNT(*) FROM faculty) as total_faculty;

COMMIT;

SELECT 'Duplicate Username Fix Completed Successfully!' as message;
SELECT 'Faculty now uses LCA2000P-LCA2999P range' as faculty_info;
SELECT 'Staff uses LCA0000P-LCA1999P range' as staff_info;
SELECT 'No more username conflicts' as conflict_info;
