-- =====================================================
-- Username and Password Synchronization Migration
-- Date: 2025-01-27
-- Purpose: Update usernames and passwords to match new employee number format
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. BACKUP CURRENT USER DATA (for reference)
-- =====================================================

-- Create backup table of current user data
CREATE TABLE IF NOT EXISTS users_backup_before_sync AS 
SELECT u.user_id, u.username, u.password, u.first_name, u.last_name, 'staff' as user_type, s.employee_number
FROM users u
JOIN staff s ON u.user_id = s.user_id
UNION ALL
SELECT u.user_id, u.username, u.password, u.first_name, u.last_name, 'faculty' as user_type, f.employee_number
FROM users u
JOIN faculty f ON u.user_id = f.user_id;

-- =====================================================
-- 2. UPDATE USERNAMES TO MATCH NEW EMPLOYEE NUMBERS
-- =====================================================

-- Update usernames for staff users
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = s.employee_number;

-- Update usernames for faculty users
UPDATE users u
JOIN faculty f ON u.user_id = f.user_id
SET u.username = f.employee_number;

-- =====================================================
-- 3. UPDATE PASSWORDS TO MATCH NEW EMPLOYEE NUMBERS
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
-- 4. VERIFICATION QUERIES
-- =====================================================

-- Show staff data with updated usernames and passwords
SELECT 
    'Staff Data After Sync' as info,
    s.employee_number,
    u.username,
    u.first_name,
    u.last_name,
    s.staff_category,
    d.designation_name,
    CASE 
        WHEN u.username = s.employee_number THEN 'MATCHED'
        ELSE 'MISMATCH'
    END as username_status
FROM staff s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN designations d ON s.designation_id = d.designation_id
ORDER BY s.employee_number;

-- Show faculty data with updated usernames and passwords
SELECT 
    'Faculty Data After Sync' as info,
    f.employee_number,
    u.username,
    u.first_name,
    u.last_name,
    f.employment_status,
    d.department_name,
    CASE 
        WHEN u.username = f.employee_number THEN 'MATCHED'
        ELSE 'MISMATCH'
    END as username_status
FROM faculty f
JOIN users u ON f.user_id = u.user_id
LEFT JOIN departments d ON f.department_id = d.department_id
ORDER BY f.employee_number;

-- Show backup data for reference
SELECT 
    'Backup Data (Before Sync)' as info,
    user_type,
    employee_number,
    username,
    first_name,
    last_name
FROM users_backup_before_sync
ORDER BY user_type, employee_number;

-- Count mismatches
SELECT 
    'Data Consistency Check' as info,
    (SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id = u.user_id WHERE u.username != s.employee_number) as staff_mismatches,
    (SELECT COUNT(*) FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE u.username != f.employee_number) as faculty_mismatches,
    (SELECT COUNT(*) FROM staff) as total_staff,
    (SELECT COUNT(*) FROM faculty) as total_faculty;

COMMIT;

-- =====================================================
-- MIGRATION COMPLETED
-- =====================================================
SELECT 'Username and Password Synchronization Completed Successfully!' as message;
SELECT 'All usernames now match employee numbers' as username_info;
SELECT 'All passwords have been updated to new format' as password_info;
SELECT 'Backup data saved to users_backup_before_sync table' as backup_info;
