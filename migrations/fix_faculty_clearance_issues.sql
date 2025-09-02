-- Fix Faculty Clearance Issues
-- Run this script to resolve the "Clearance Period Closed" problem

-- 1. Fix the clearance period dates (extend end date to make period actually open)
UPDATE `clearance_periods` 
SET `end_date` = '2025-12-31' 
WHERE `period_id` = 14;

-- 2. Create a clearance form for the faculty user (Dr. Jane Smith - user_id 15)
INSERT INTO `clearance_forms` (
    `clearance_form_id`,
    `user_id`,
    `academic_year_id`,
    `semester_id`,
    `clearance_type`,
    `status`,
    `created_at`,
    `updated_at`
) VALUES (
    'CF-2025-00002',
    15,  -- Dr. Jane Smith's user_id
    10,  -- Academic year 2025-2026
    24,  -- 1st semester
    'Faculty',
    'Unapplied',
    NOW(),
    NOW()
);

-- 3. Create clearance signatories for the faculty user based on requirements
INSERT INTO `clearance_signatories` (
    `clearance_form_id`,
    `designation_id`,
    `action`,
    `created_at`,
    `updated_at`
) VALUES 
    ('CF-2025-00002', 1, 'Pending', NOW(), NOW()),   -- Registrar
    ('CF-2025-00002', 2, 'Pending', NOW(), NOW()),   -- Cashier
    ('CF-2025-00002', 3, 'Pending', NOW(), NOW()),   -- Librarian
    ('CF-2025-00002', 8, 'Pending', NOW(), NOW()),   -- Program Head
    ('CF-2025-00002', 12, 'Pending', NOW(), NOW()),  -- Accountant
    ('CF-2025-00002', 9, 'Pending', NOW(), NOW());   -- School Administrator

-- 4. Verify the faculty user has the correct role
-- (This should already exist, but let's make sure)
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `assigned_at`) 
VALUES (15, 4, NOW());  -- Role 4 = Faculty

-- 5. Update the semester to be active for clearance generation
UPDATE `semesters` 
SET `is_generation` = 1 
WHERE `semester_id` = 24;

-- 6. Verify the academic year is active
-- (This should already be active, but let's confirm)
UPDATE `academic_years` 
SET `is_active` = 1 
WHERE `academic_year_id` = 10;

-- 7. Show the current state
SELECT 
    'Current Clearance Period Status' as info,
    cp.period_id,
    cp.start_date,
    cp.end_date,
    cp.is_active,
    cp.status,
    CONCAT(ay.year, ' - ', s.semester_name) as period_text
FROM `clearance_periods` cp
JOIN `academic_years` ay ON cp.academic_year_id = ay.academic_year_id
JOIN `semesters` s ON cp.semester_id = s.semester_id
WHERE cp.period_id = 14;

-- 8. Show faculty user's clearance form
SELECT 
    'Faculty Clearance Form Status' as info,
    cf.clearance_form_id,
    cf.status,
    cf.created_at,
    CONCAT(u.first_name, ' ', u.last_name) as faculty_name
FROM `clearance_forms` cf
JOIN `users` u ON cf.user_id = u.user_id
WHERE cf.user_id = 15 AND cf.clearance_type = 'Faculty';

-- 9. Show faculty user's signatories
SELECT 
    'Faculty Signatories Status' as info,
    cs.clearance_form_id,
    d.designation_name,
    cs.action,
    cs.created_at
FROM `clearance_signatories` cs
JOIN `designations` d ON cs.designation_id = d.designation_id
WHERE cs.clearance_form_id = 'CF-2025-00002'
ORDER BY d.designation_name;