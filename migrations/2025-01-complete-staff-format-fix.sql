-- =====================================================
-- Complete Staff Format Fix Migration
-- Date: 2025-01-27
-- Purpose: Fix ALL staff records to follow LCAXXXXP format
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. DROP EXISTING CONSTRAINT (if exists)
-- =====================================================
ALTER TABLE staff DROP CONSTRAINT IF EXISTS chk_staff_employee_number_format;

-- =====================================================
-- 2. UPDATE ALL STAFF RECORDS TO NEW FORMAT
-- =====================================================

-- Fix LCA003P to LCA0003P
UPDATE staff SET employee_number = 'LCA0003P' WHERE employee_number = 'LCA003P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0003P' WHERE s.employee_number = 'LCA0003P';

-- Update LCA101P to LCA0101P
UPDATE staff SET employee_number = 'LCA0101P' WHERE employee_number = 'LCA101P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0101P' WHERE s.employee_number = 'LCA0101P';

-- Update LCA102P to LCA0102P
UPDATE staff SET employee_number = 'LCA0102P' WHERE employee_number = 'LCA102P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0102P' WHERE s.employee_number = 'LCA0102P';

-- Update LCA103P to LCA0103P
UPDATE staff SET employee_number = 'LCA0103P' WHERE employee_number = 'LCA103P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0103P' WHERE s.employee_number = 'LCA0103P';

-- Update LCA104P to LCA0104P
UPDATE staff SET employee_number = 'LCA0104P' WHERE employee_number = 'LCA104P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0104P' WHERE s.employee_number = 'LCA0104P';

-- Update LCA105P to LCA0105P
UPDATE staff SET employee_number = 'LCA0105P' WHERE employee_number = 'LCA105P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0105P' WHERE s.employee_number = 'LCA0105P';

-- Update LCA106P to LCA0106P
UPDATE staff SET employee_number = 'LCA0106P' WHERE employee_number = 'LCA106P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0106P' WHERE s.employee_number = 'LCA0106P';

-- Update LCA107P to LCA0107P
UPDATE staff SET employee_number = 'LCA0107P' WHERE employee_number = 'LCA107P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0107P' WHERE s.employee_number = 'LCA0107P';

-- Update LCA108P to LCA0108P
UPDATE staff SET employee_number = 'LCA0108P' WHERE employee_number = 'LCA108P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0108P' WHERE s.employee_number = 'LCA0108P';

-- Update LCA109P to LCA0109P
UPDATE staff SET employee_number = 'LCA0109P' WHERE employee_number = 'LCA109P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0109P' WHERE s.employee_number = 'LCA0109P';

-- Update LCA110P to LCA0110P
UPDATE staff SET employee_number = 'LCA0110P' WHERE employee_number = 'LCA110P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0110P' WHERE s.employee_number = 'LCA0110P';

-- Update LCA111P to LCA0111P
UPDATE staff SET employee_number = 'LCA0111P' WHERE employee_number = 'LCA111P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0111P' WHERE s.employee_number = 'LCA0111P';

-- Update LCA112P to LCA0112P
UPDATE staff SET employee_number = 'LCA0112P' WHERE employee_number = 'LCA112P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0112P' WHERE s.employee_number = 'LCA0112P';

-- Update LCA113P to LCA0113P
UPDATE staff SET employee_number = 'LCA0113P' WHERE employee_number = 'LCA113P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0113P' WHERE s.employee_number = 'LCA0113P';

-- Update LCA114P to LCA0114P
UPDATE staff SET employee_number = 'LCA0114P' WHERE employee_number = 'LCA114P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0114P' WHERE s.employee_number = 'LCA0114P';

-- Update LCA115P to LCA0115P
UPDATE staff SET employee_number = 'LCA0115P' WHERE employee_number = 'LCA115P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0115P' WHERE s.employee_number = 'LCA0115P';

-- Update LCA116P to LCA0116P
UPDATE staff SET employee_number = 'LCA0116P' WHERE employee_number = 'LCA116P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA0116P' WHERE s.employee_number = 'LCA0116P';

-- Update Program Head records
UPDATE staff SET employee_number = 'LCA1001P' WHERE employee_number = 'PHC101P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA1001P' WHERE s.employee_number = 'LCA1001P';

UPDATE staff SET employee_number = 'LCA1002P' WHERE employee_number = 'PHF101P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA1002P' WHERE s.employee_number = 'LCA1002P';

UPDATE staff SET employee_number = 'LCA1003P' WHERE employee_number = 'PHS101P';
UPDATE users u JOIN staff s ON u.user_id = s.user_id SET u.username = 'LCA1003P' WHERE s.employee_number = 'LCA1003P';

-- =====================================================
-- 3. ADD FORMAT VALIDATION CONSTRAINTS
-- =====================================================

-- Add check constraint for staff employee number format
ALTER TABLE staff 
ADD CONSTRAINT chk_staff_employee_number_format 
CHECK (employee_number REGEXP '^LCA[0-9]{4}[A-Z]$');

-- =====================================================
-- 4. VERIFICATION
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

-- Test format validation
SELECT 
    'Format Validation Test' as info,
    'LCA1234P' as test_format,
    CASE 
        WHEN 'LCA1234P' REGEXP '^LCA[0-9]{4}[A-Z]$' THEN 'VALID'
        ELSE 'INVALID'
    END as validation_result;

COMMIT;

SELECT 'Complete Staff and Faculty Format Update Completed Successfully!' as message;
