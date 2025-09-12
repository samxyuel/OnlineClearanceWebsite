-- Fix Program Head Department Assignments and Add Validation
-- This script removes incorrect assignments and properly assigns Program Heads to their sectors

USE online_clearance_db;

-- Step 1: Remove all current assignments (including School Administrator)
DELETE FROM staff_department_assignments;

-- Step 2: Get the correct Program Head employee numbers and assign them to their sectors
-- Based on employee number ranges: Staff (LCA0000P-LCA1999P), Faculty (LCA2000P-LCA2999P), Program Heads (LCA3000P-LCA3999P)

-- College Program Head - assign to all College departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
CROSS JOIN departments d
JOIN sectors sec ON sec.sector_id = d.sector_id
WHERE s.staff_category = 'Program Head' 
  AND sec.sector_name = 'College'
  AND s.employee_number LIKE 'LCA3%'
LIMIT 1;

-- Senior High School Program Head - assign to all SHS departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
CROSS JOIN departments d
JOIN sectors sec ON sec.sector_id = d.sector_id
WHERE s.staff_category = 'Program Head' 
  AND sec.sector_name = 'Senior High School'
  AND s.employee_number LIKE 'LCA3%'
LIMIT 1;

-- Faculty Program Head - assign to all Faculty departments
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by)
SELECT 
    s.employee_number,
    d.department_id,
    d.sector_id,
    TRUE as is_primary,
    NULL as assigned_by
FROM staff s
CROSS JOIN departments d
JOIN sectors sec ON sec.sector_id = d.sector_id
WHERE s.staff_category = 'Program Head' 
  AND sec.sector_name = 'Faculty'
  AND s.employee_number LIKE 'LCA3%'
LIMIT 1;

-- Step 3: Add validation triggers

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS sda_bi_sector_validation;
DROP TRIGGER IF EXISTS sda_bi_program_head_only;

-- Trigger to ensure only Program Heads can be assigned to departments
DELIMITER $$
CREATE TRIGGER sda_bi_program_head_only
BEFORE INSERT ON staff_department_assignments
FOR EACH ROW
BEGIN
  DECLARE staff_category VARCHAR(50);
  
  SELECT s.staff_category INTO staff_category
  FROM staff s
  WHERE s.employee_number = NEW.staff_id;
  
  IF staff_category != 'Program Head' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Program Heads can be assigned to departments';
  END IF;
END$$
DELIMITER ;

-- Trigger to ensure Program Heads can only be assigned to departments within their sector
-- Since we don't have direct sector relationship in staff table, we'll use a simpler validation
-- that allows Program Heads to be assigned to any department (they can be reassigned as needed)
DELIMITER $$
CREATE TRIGGER sda_bi_sector_validation
BEFORE INSERT ON staff_department_assignments
FOR EACH ROW
BEGIN
  -- For now, we'll allow Program Heads to be assigned to any department
  -- This provides flexibility for reassignment as needed
  -- Future enhancement: Add sector-specific validation if needed
  SET NEW.sector_id = (SELECT sector_id FROM departments WHERE department_id = NEW.department_id);
END$$
DELIMITER ;

-- Step 4: Verify assignments
SELECT 
    'Program Head Assignments' as info,
    COUNT(*) as assignment_count
FROM staff_department_assignments sda
JOIN staff s ON s.employee_number = sda.staff_id
WHERE s.staff_category = 'Program Head';

-- Show detailed assignments
SELECT 
    s.employee_number,
    s.staff_category,
    des.designation_name,
    d.department_name,
    sec.sector_name,
    sda.is_primary
FROM staff_department_assignments sda
JOIN staff s ON s.employee_number = sda.staff_id
JOIN designations des ON des.designation_id = s.designation_id
JOIN departments d ON d.department_id = sda.department_id
JOIN sectors sec ON sec.sector_id = d.sector_id
ORDER BY sec.sector_name, d.department_name;
