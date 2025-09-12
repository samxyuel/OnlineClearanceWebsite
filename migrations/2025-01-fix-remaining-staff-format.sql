-- =====================================================
-- Fix Remaining Staff Format Migration
-- Date: 2025-01-27
-- Purpose: Fix remaining staff records that don't follow the new format
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. FIX REMAINING STAFF RECORDS
-- =====================================================

-- Fix the LCA003P record (seems to be missing a digit)
UPDATE staff 
SET employee_number = 'LCA0003P'
WHERE employee_number = 'LCA003P';

-- Update corresponding user username
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = 'LCA0003P'
WHERE s.employee_number = 'LCA0003P';

-- Fix Program Head records to follow new format
UPDATE staff 
SET employee_number = 'LCA1001P'
WHERE employee_number = 'PHC101P';

UPDATE staff 
SET employee_number = 'LCA1002P'
WHERE employee_number = 'PHS101P';

UPDATE staff 
SET employee_number = 'LCA1003P'
WHERE employee_number = 'PHF101P';

-- Update corresponding user usernames for Program Heads
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = s.employee_number
WHERE s.employee_number IN ('LCA1001P', 'LCA1002P', 'LCA1003P');

-- =====================================================
-- 2. ADD FORMAT VALIDATION CONSTRAINTS
-- =====================================================

-- Add check constraint for staff employee number format
ALTER TABLE staff 
ADD CONSTRAINT chk_staff_employee_number_format 
CHECK (employee_number REGEXP '^LCA[0-9]{4}[A-Z]$');

-- =====================================================
-- 3. VERIFICATION
-- =====================================================

-- Show all staff data
SELECT 
    'Final Staff Data' as info,
    s.employee_number,
    u.first_name,
    u.last_name,
    s.staff_category,
    d.designation_name
FROM staff s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN designations d ON s.designation_id = d.designation_id
ORDER BY s.employee_number;

-- Show all faculty data
SELECT 
    'Final Faculty Data' as info,
    f.employee_number,
    u.first_name,
    u.last_name,
    f.employment_status,
    d.department_name
FROM faculty f
JOIN users u ON f.user_id = u.user_id
LEFT JOIN departments d ON f.department_id = d.department_id
ORDER BY f.employee_number;

COMMIT;

SELECT 'All Staff and Faculty Format Updates Completed Successfully!' as message;
