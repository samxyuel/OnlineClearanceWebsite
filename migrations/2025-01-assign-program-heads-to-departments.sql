-- Assign Program Heads to departments via Junction Table
-- This script assigns Program Heads to their respective departments based on sectors

USE online_clearance_db;

-- Get the staff IDs for Program Heads and School Administrator
-- We'll assign them to departments based on their sectors

-- Assign College Program Head to College departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
JOIN sectors sec ON sec.sector_name = 'College'
JOIN departments d ON d.sector_id = sec.sector_id
WHERE s.staff_category = 'Program Head' AND s.designation_id = (SELECT designation_id FROM designations WHERE designation_name = 'Program Head - College')
LIMIT 1;

-- Assign Senior High School Program Head to SHS departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
JOIN sectors sec ON sec.sector_name = 'Senior High School'
JOIN departments d ON d.sector_id = sec.sector_id
WHERE s.staff_category = 'Program Head' AND s.designation_id = (SELECT designation_id FROM designations WHERE designation_name = 'Program Head - Senior High School')
LIMIT 1;

-- Assign Faculty Program Head to Faculty departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
JOIN sectors sec ON sec.sector_name = 'Faculty'
JOIN departments d ON d.sector_id = sec.sector_id
WHERE s.staff_category = 'Program Head' AND s.designation_id = (SELECT designation_id FROM designations WHERE designation_name = 'Program Head - Faculty')
LIMIT 1;

-- Assign School Administrator to all departments (as they oversee everything)
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    FALSE as is_primary,  -- Not primary since they oversee all
    NULL as assigned_by
FROM staff s
CROSS JOIN departments d
WHERE s.staff_category = 'School Administrator';

-- Verify assignments
SELECT 
    'Program Head Assignments' as info,
    COUNT(*) as assignment_count
FROM staff_department_assignments sda
JOIN staff s ON s.employee_number = sda.staff_id
WHERE s.staff_category = 'Program Head';

SELECT 
    'School Administrator Assignments' as info,
    COUNT(*) as assignment_count
FROM staff_department_assignments sda
JOIN staff s ON s.employee_number = sda.staff_id
WHERE s.staff_category = 'School Administrator';

-- Show detailed assignments
SELECT 
    s.employee_number,
    s.staff_category,
    d.department_name,
    sec.sector_name,
    sda.is_primary
FROM staff_department_assignments sda
JOIN staff s ON s.employee_number = sda.staff_id
JOIN departments d ON d.department_id = sda.department_id
JOIN sectors sec ON sec.sector_id = d.sector_id
ORDER BY s.staff_category, d.department_name;
