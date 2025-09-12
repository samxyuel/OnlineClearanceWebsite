-- =====================================================
-- Remove Triggers and Update Format Migration
-- Date: 2025-01-27
-- Purpose: Remove old triggers, update data, and create new triggers
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. DROP OLD TRIGGERS
-- =====================================================

DROP TRIGGER IF EXISTS staff_bi;
DROP TRIGGER IF EXISTS staff_bu;

-- =====================================================
-- 2. DROP OLD CONSTRAINTS
-- =====================================================

ALTER TABLE staff DROP CONSTRAINT IF EXISTS chk_staff_employee_number_format;

-- =====================================================
-- 3. UPDATE ALL STAFF RECORDS TO NEW FORMAT
-- =====================================================

-- Update all staff records to new format
UPDATE staff SET employee_number = 'LCA0003P' WHERE employee_number = 'LCA003P';
UPDATE staff SET employee_number = 'LCA0101P' WHERE employee_number = 'LCA101P';
UPDATE staff SET employee_number = 'LCA0102P' WHERE employee_number = 'LCA102P';
UPDATE staff SET employee_number = 'LCA0103P' WHERE employee_number = 'LCA103P';
UPDATE staff SET employee_number = 'LCA0104P' WHERE employee_number = 'LCA104P';
UPDATE staff SET employee_number = 'LCA0105P' WHERE employee_number = 'LCA105P';
UPDATE staff SET employee_number = 'LCA0106P' WHERE employee_number = 'LCA106P';
UPDATE staff SET employee_number = 'LCA0107P' WHERE employee_number = 'LCA107P';
UPDATE staff SET employee_number = 'LCA0108P' WHERE employee_number = 'LCA108P';
UPDATE staff SET employee_number = 'LCA0109P' WHERE employee_number = 'LCA109P';
UPDATE staff SET employee_number = 'LCA0110P' WHERE employee_number = 'LCA110P';
UPDATE staff SET employee_number = 'LCA0111P' WHERE employee_number = 'LCA111P';
UPDATE staff SET employee_number = 'LCA0112P' WHERE employee_number = 'LCA112P';
UPDATE staff SET employee_number = 'LCA0113P' WHERE employee_number = 'LCA113P';
UPDATE staff SET employee_number = 'LCA0114P' WHERE employee_number = 'LCA114P';
UPDATE staff SET employee_number = 'LCA0115P' WHERE employee_number = 'LCA115P';
UPDATE staff SET employee_number = 'LCA0116P' WHERE employee_number = 'LCA116P';

-- Update Program Head records
UPDATE staff SET employee_number = 'LCA1001P' WHERE employee_number = 'PHC101P';
UPDATE staff SET employee_number = 'LCA1002P' WHERE employee_number = 'PHF101P';
UPDATE staff SET employee_number = 'LCA1003P' WHERE employee_number = 'PHS101P';

-- =====================================================
-- 4. UPDATE CORRESPONDING USER RECORDS
-- =====================================================

-- Update all user usernames to match new staff employee numbers
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = s.employee_number
WHERE s.employee_number REGEXP '^LCA[0-9]{4}[A-Z]$';

-- =====================================================
-- 5. CREATE NEW TRIGGERS WITH CORRECT FORMAT
-- =====================================================

-- Create new BEFORE INSERT trigger
DELIMITER //
CREATE TRIGGER staff_bi
BEFORE INSERT ON staff
FOR EACH ROW
BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END//
DELIMITER ;

-- Create new BEFORE UPDATE trigger
DELIMITER //
CREATE TRIGGER staff_bu
BEFORE UPDATE ON staff
FOR EACH ROW
BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^LCA[0-9]{4}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LCAXXXXP)';
  END IF;
END//
DELIMITER ;

-- =====================================================
-- 6. ADD NEW FORMAT VALIDATION CONSTRAINTS
-- =====================================================

-- Add check constraint for new staff employee number format
ALTER TABLE staff 
ADD CONSTRAINT chk_staff_employee_number_format 
CHECK (employee_number REGEXP '^LCA[0-9]{4}[A-Z]$');

-- =====================================================
-- 7. VERIFICATION
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

-- Count records
SELECT 
    'Record Counts' as info,
    (SELECT COUNT(*) FROM staff) as staff_count,
    (SELECT COUNT(*) FROM faculty) as faculty_count,
    (SELECT COUNT(*) FROM users) as user_count;

COMMIT;

SELECT 'Staff and Faculty Format Update with New Triggers Completed Successfully!' as message;
