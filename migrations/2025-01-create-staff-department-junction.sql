-- =====================================================
-- Create Staff-Department Junction Table Migration
-- Date: 2025-01-27
-- Purpose: Create junction table for Program Head department assignments
--         Only Program Heads can be assigned to departments
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. CREATE JUNCTION TABLE FOR STAFF-DEPARTMENT ASSIGNMENTS
-- =====================================================

CREATE TABLE `staff_department_assignments` (
  `assignment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `staff_id` VARCHAR(8) NOT NULL,
  `department_id` INT NOT NULL,
  `sector_id` INT NULL COMMENT 'For sector-wide assignments',
  `is_primary` BOOLEAN DEFAULT FALSE COMMENT 'Primary department assignment',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` INT NULL COMMENT 'User who assigned this',
  `is_active` BOOLEAN DEFAULT TRUE,
  
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`employee_number`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sector_id`) REFERENCES `sectors`(`sector_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  
  UNIQUE KEY `unique_staff_department` (`staff_id`, `department_id`),
  INDEX `idx_staff_assignments` (`staff_id`, `is_active`),
  INDEX `idx_department_assignments` (`department_id`, `is_active`),
  INDEX `idx_sector_assignments` (`sector_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. MIGRATE EXISTING PROGRAM HEAD DATA
-- =====================================================

-- Get existing Program Head assignments before removing department_id
INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_at, assigned_by, is_active)
SELECT 
    s.employee_number,
    s.department_id,
    d.sector_id,
    TRUE as is_primary,
    NOW() as assigned_at,
    1 as assigned_by,  -- Admin user
    1 as is_active
FROM staff s
JOIN departments d ON s.department_id = d.department_id
WHERE s.staff_category = 'Program Head' 
AND s.department_id IS NOT NULL;

-- =====================================================
-- 3. REMOVE DEPARTMENT_ID FROM STAFF TABLE
-- =====================================================

-- Remove the department_id column from staff table
ALTER TABLE `staff` DROP COLUMN `department_id`;

-- =====================================================
-- 4. VERIFICATION QUERIES
-- =====================================================

-- Show migrated Program Head assignments
SELECT 
    'Migrated Program Head Assignments' as info,
    sda.staff_id,
    s.staff_category,
    u.first_name,
    u.last_name,
    d.department_name,
    sda.is_primary,
    sda.is_active
FROM staff_department_assignments sda
JOIN staff s ON sda.staff_id = s.employee_number
JOIN users u ON s.user_id = u.user_id
JOIN departments d ON sda.department_id = d.department_id
WHERE s.staff_category = 'Program Head'
ORDER BY sda.staff_id, sda.is_primary DESC;

-- Show current staff table structure
DESCRIBE staff;

-- Show junction table structure
DESCRIBE staff_department_assignments;

-- Count assignments
SELECT 
    'Assignment Counts' as info,
    (SELECT COUNT(*) FROM staff_department_assignments) as total_assignments,
    (SELECT COUNT(*) FROM staff WHERE staff_category = 'Program Head') as program_heads,
    (SELECT COUNT(*) FROM staff WHERE staff_category = 'Regular Staff') as regular_staff,
    (SELECT COUNT(*) FROM staff WHERE staff_category = 'School Administrator') as school_admins;

COMMIT;

-- =====================================================
-- MIGRATION COMPLETED
-- =====================================================
SELECT 'Staff-Department Junction Table Created Successfully!' as message;
SELECT 'Only Program Heads can be assigned to departments' as rule_info;
SELECT 'Regular Staff and School Administrators have no department assignments' as staff_info;
