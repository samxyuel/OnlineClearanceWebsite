-- =====================================================
-- Fix Staff Format Migration
-- Date: 2025-01-27
-- Purpose: Update existing staff data to new format before applying constraints
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. UPDATE EXISTING STAFF DATA TO NEW FORMAT
-- =====================================================

-- Update staff employee numbers from LCA123P to LCA0123P format
UPDATE staff 
SET employee_number = CONCAT(
    SUBSTRING(employee_number, 1, 3),  -- LCA
    LPAD(SUBSTRING(employee_number, 4, 3), 4, '0'),  -- Pad 3 digits to 4 digits
    SUBSTRING(employee_number, 7, 1)   -- P
)
WHERE employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$';

-- Update corresponding user usernames
UPDATE users u
JOIN staff s ON u.user_id = s.user_id
SET u.username = s.employee_number
WHERE s.employee_number REGEXP '^LCA[0-9]{4}[A-Z]$';

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

-- Show updated staff data
SELECT 
    'Updated Staff Data' as info,
    s.employee_number,
    u.first_name,
    u.last_name,
    s.staff_category,
    d.designation_name
FROM staff s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN designations d ON s.designation_id = d.designation_id
ORDER BY s.employee_number;

COMMIT;

SELECT 'Staff Format Update Completed Successfully!' as message;
