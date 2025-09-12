-- =====================================================
-- Faculty Format Update Migration
-- Date: 2025-01-27
-- Purpose: Update faculty employee number format from LCA123P to LCAXXXXP
--         Implement cascade deletion and clean old data
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. BACKUP CURRENT FACULTY DATA (for reference)
-- =====================================================
-- Create backup table of current faculty data
CREATE TABLE IF NOT EXISTS faculty_backup_old_format AS 
SELECT * FROM faculty WHERE employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- =====================================================
-- 2. CLEAN UP OLD FACULTY DATA WITH OLD FORMAT
-- =====================================================

-- First, delete clearance forms and related data for old format faculty
DELETE cf FROM clearance_forms cf
JOIN faculty f ON cf.user_id = f.user_id
WHERE f.employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Delete clearance signatories for old format faculty
DELETE cs FROM clearance_signatories cs
JOIN faculty f ON cs.actual_user_id = f.user_id
WHERE f.employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Delete user activities for old format faculty
DELETE ua FROM user_activities ua
JOIN faculty f ON ua.user_id = f.user_id
WHERE f.employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Delete audit logs for old format faculty (set user_id to NULL to preserve audit trail)
UPDATE audit_logs al
JOIN faculty f ON al.user_id = f.user_id
SET al.user_id = NULL
WHERE f.employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Delete faculty records with old format
DELETE FROM faculty WHERE employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Delete corresponding user records for old format faculty
DELETE u FROM users u
WHERE u.user_id NOT IN (
    SELECT DISTINCT user_id FROM faculty WHERE user_id IS NOT NULL
    UNION
    SELECT DISTINCT user_id FROM staff WHERE user_id IS NOT NULL
    UNION
    SELECT DISTINCT user_id FROM students WHERE user_id IS NOT NULL
);

-- =====================================================
-- 3. UPDATE DATABASE CONSTRAINTS FOR CASCADE DELETION
-- =====================================================

-- Update faculty table foreign key to cascade delete users
ALTER TABLE faculty 
DROP FOREIGN KEY IF EXISTS faculty_ibfk_1;

ALTER TABLE faculty 
ADD CONSTRAINT faculty_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Update users table foreign key constraints for cascade deletion
-- (This ensures that when a user is deleted, all related data is deleted)

-- Update clearance_forms foreign key for cascade deletion
ALTER TABLE clearance_forms 
DROP FOREIGN KEY IF EXISTS clearance_forms_ibfk_1;

ALTER TABLE clearance_forms 
ADD CONSTRAINT clearance_forms_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Update clearance_signatories foreign key for cascade deletion
ALTER TABLE clearance_signatories 
DROP FOREIGN KEY IF EXISTS clearance_signatories_ibfk_3;

ALTER TABLE clearance_signatories 
ADD CONSTRAINT clearance_signatories_ibfk_3 
FOREIGN KEY (actual_user_id) REFERENCES users(user_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Update user_activities foreign key for cascade deletion
ALTER TABLE user_activities 
DROP FOREIGN KEY IF EXISTS user_activities_ibfk_1;

ALTER TABLE user_activities 
ADD CONSTRAINT user_activities_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Update audit_logs to set user_id to NULL (preserve audit trail)
ALTER TABLE audit_logs 
DROP FOREIGN KEY IF EXISTS audit_logs_ibfk_1;

ALTER TABLE audit_logs 
ADD CONSTRAINT audit_logs_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 4. ADD NEW FORMAT VALIDATION CONSTRAINTS
-- =====================================================

-- Add check constraint for new faculty employee number format
ALTER TABLE faculty 
ADD CONSTRAINT chk_faculty_employee_number_format 
CHECK (employee_number REGEXP '^LCA[0-9]{4}[A-Z]$');

-- Add check constraint for new staff employee number format
ALTER TABLE staff 
ADD CONSTRAINT chk_staff_employee_number_format 
CHECK (employee_number REGEXP '^LCA[0-9]{4}[A-Z]$');

-- =====================================================
-- 5. INSERT NEW FACULTY SEED DATA WITH NEW FORMAT
-- =====================================================

-- Insert new faculty users
INSERT INTO users (username, password, email, first_name, last_name, middle_name, contact_number, status, created_at) VALUES
('LCA0001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty1@clearance.com', 'John', 'Doe', 'Michael', '+63 912 345 6789', 'active', NOW()),
('LCA0002P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty2@clearance.com', 'Jane', 'Smith', 'Elizabeth', '+63 912 345 6790', 'active', NOW()),
('LCA0003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty3@clearance.com', 'Robert', 'Johnson', 'David', '+63 912 345 6791', 'active', NOW()),
('LCA0004P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty4@clearance.com', 'Maria', 'Garcia', 'Isabella', '+63 912 345 6792', 'active', NOW()),
('LCA0005P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty5@clearance.com', 'James', 'Wilson', 'Alexander', '+63 912 345 6793', 'active', NOW());

-- Get department IDs for faculty assignment
SET @dept_ict = (SELECT department_id FROM departments WHERE department_name = 'Information & Communication Technology' LIMIT 1);
SET @dept_bas = (SELECT department_id FROM departments WHERE department_name = 'Business & Management, Arts, and Sciences' LIMIT 1);
SET @dept_thm = (SELECT department_id FROM departments WHERE department_name = 'Tourism and Hospitality Management' LIMIT 1);

-- Insert new faculty records
INSERT INTO faculty (employee_number, user_id, employment_status, department_id, created_at) VALUES
('LCA0001P', (SELECT user_id FROM users WHERE username = 'LCA0001P'), 'Full Time', @dept_ict, NOW()),
('LCA0002P', (SELECT user_id FROM users WHERE username = 'LCA0002P'), 'Full Time', @dept_bas, NOW()),
('LCA0003P', (SELECT user_id FROM users WHERE username = 'LCA0003P'), 'Part Time', @dept_thm, NOW()),
('LCA0004P', (SELECT user_id FROM users WHERE username = 'LCA0004P'), 'Part Time - Full Load', @dept_ict, NOW()),
('LCA0005P', (SELECT user_id FROM users WHERE username = 'LCA0005P'), 'Full Time', @dept_bas, NOW());

-- Assign faculty role to new users
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, r.role_id, NOW(), TRUE
FROM users u
CROSS JOIN roles r
WHERE u.username IN ('LCA0001P', 'LCA0002P', 'LCA0003P', 'LCA0004P', 'LCA0005P')
AND r.role_name = 'Faculty';

-- =====================================================
-- 6. VERIFICATION QUERIES
-- =====================================================

-- Show current faculty data
SELECT 
    'Current Faculty Data' as info,
    f.employee_number,
    u.first_name,
    u.last_name,
    f.employment_status,
    d.department_name,
    f.created_at
FROM faculty f
JOIN users u ON f.user_id = u.user_id
LEFT JOIN departments d ON f.department_id = d.department_id
ORDER BY f.employee_number;

-- Show backup data
SELECT 
    'Backup Faculty Data (Old Format)' as info,
    employee_number,
    user_id
FROM faculty_backup_old_format;

-- Verify format constraints
SELECT 
    'Format Validation Test' as info,
    'LCA1234P' as test_format,
    CASE 
        WHEN 'LCA1234P' REGEXP '^LCA[0-9]{4}[A-Z]$' THEN 'VALID'
        ELSE 'INVALID'
    END as validation_result;

COMMIT;

-- =====================================================
-- MIGRATION COMPLETED
-- =====================================================
SELECT 'Faculty Format Update Migration Completed Successfully!' as message;
SELECT 'Old faculty data has been backed up to faculty_backup_old_format table' as backup_info;
SELECT 'New faculty data uses LCAXXXXP format' as format_info;
SELECT 'Cascade deletion is now implemented for faculty' as cascade_info;
