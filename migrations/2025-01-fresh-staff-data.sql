-- =====================================================
-- Fresh Staff Data Migration
-- Date: 2025-01-27
-- Purpose: Clean existing staff data and create fresh seed data
--         Only Program Heads get department assignments
-- =====================================================

START TRANSACTION;

-- =====================================================
-- 1. CLEAN EXISTING STAFF DATA
-- =====================================================

-- Delete existing staff department assignments
DELETE FROM staff_department_assignments;

-- Delete existing staff records
DELETE FROM staff;

-- Delete corresponding user records (cascade will handle related data)
DELETE FROM users WHERE user_id IN (
    SELECT user_id FROM (
        SELECT u.user_id 
        FROM users u 
        WHERE u.username REGEXP '^LCA[0-9]{4}[A-Z]$'
        AND u.user_id NOT IN (
            SELECT user_id FROM faculty
        )
    ) AS temp
);

-- =====================================================
-- 2. CREATE FRESH STAFF SEED DATA
-- =====================================================

-- Insert new staff users (15 Regular Staff + 1 School Administrator + 3 Program Heads = 19 total)
INSERT INTO users (username, password, email, first_name, last_name, middle_name, contact_number, status, created_at) VALUES
-- Regular Staff (15 positions)
('LCA4001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guidance@clearance.com', 'Sarah', 'Guidance', 'Marie', '+63 912 345 6001', 'active', NOW()),
('LCA4002P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'discipline@clearance.com', 'Michael', 'Discipline', 'James', '+63 912 345 6002', 'active', NOW()),
('LCA4003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'clinic@clearance.com', 'Dr. Emily', 'Clinic', 'Rose', '+63 912 345 6003', 'active', NOW()),
('LCA4004P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian@clearance.com', 'David', 'Librarian', 'Paul', '+63 912 345 6004', 'active', NOW()),
('LCA4005P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni@clearance.com', 'Lisa', 'Alumni', 'Grace', '+63 912 345 6005', 'active', NOW()),
('LCA4006P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sao@clearance.com', 'Robert', 'Sao', 'John', '+63 912 345 6006', 'active', NOW()),
('LCA4007P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'registrar@clearance.com', 'Maria', 'Registrar', 'Elena', '+63 912 345 6007', 'active', NOW()),
('LCA4008P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier@clearance.com', 'John', 'Cashier', 'Mark', '+63 912 345 6008', 'active', NOW()),
('LCA4009P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pamo@clearance.com', 'Anna', 'Pamo', 'Sophia', '+63 912 345 6009', 'active', NOW()),
('LCA4010P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'misit@clearance.com', 'Carlos', 'Misit', 'Luis', '+63 912 345 6010', 'active', NOW()),
('LCA4011P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pettycash@clearance.com', 'Jennifer', 'Pettycash', 'Ann', '+63 912 345 6011', 'active', NOW()),
('LCA4012P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'building@clearance.com', 'Thomas', 'Building', 'Lee', '+63 912 345 6012', 'active', NOW()),
('LCA4013P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'accountant@clearance.com', 'Patricia', 'Accountant', 'Jane', '+63 912 345 6013', 'active', NOW()),
('LCA4014P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acadhead@clearance.com', 'William', 'Acadhead', 'Scott', '+63 912 345 6014', 'active', NOW()),
('LCA4015P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr@clearance.com', 'Susan', 'Hr', 'Kim', '+63 912 345 6015', 'active', NOW()),

-- School Administrator (1 position)
('LCA5001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'schooladmin@clearance.com', 'Dr. James', 'Schooladmin', 'Wilson', '+63 912 345 7001', 'active', NOW()),

-- Program Heads (3 positions)
('LCA3001P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phcollege@clearance.com', 'Dr. Maria', 'Phcollege', 'Santos', '+63 912 345 8001', 'active', NOW()),
('LCA3002P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phshs@clearance.com', 'Dr. John', 'Phshs', 'Dela Cruz', '+63 912 345 8002', 'active', NOW()),
('LCA3003P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phfaculty@clearance.com', 'Dr. Ana', 'Phfaculty', 'Reyes', '+63 912 345 8003', 'active', NOW());

-- =====================================================
-- 3. CREATE STAFF RECORDS
-- =====================================================

-- Get designation IDs
SET @desig_guidance = (SELECT designation_id FROM designations WHERE designation_name = 'Guidance' LIMIT 1);
SET @desig_discipline = (SELECT designation_id FROM designations WHERE designation_name = 'Disciplinary Officer' LIMIT 1);
SET @desig_clinic = (SELECT designation_id FROM designations WHERE designation_name = 'Clinic' LIMIT 1);
SET @desig_librarian = (SELECT designation_id FROM designations WHERE designation_name = 'Librarian' LIMIT 1);
SET @desig_alumni = (SELECT designation_id FROM designations WHERE designation_name = 'Alumni Placement Officer' LIMIT 1);
SET @desig_sao = (SELECT designation_id FROM designations WHERE designation_name = 'Student Affairs Officer' LIMIT 1);
SET @desig_registrar = (SELECT designation_id FROM designations WHERE designation_name = 'Registrar' LIMIT 1);
SET @desig_cashier = (SELECT designation_id FROM designations WHERE designation_name = 'Cashier' LIMIT 1);
SET @desig_pamo = (SELECT designation_id FROM designations WHERE designation_name = 'PAMO' LIMIT 1);
SET @desig_misit = (SELECT designation_id FROM designations WHERE designation_name = 'MIS/IT' LIMIT 1);
SET @desig_petty = (SELECT designation_id FROM designations WHERE designation_name = 'Petty Cash Custodian' LIMIT 1);
SET @desig_building = (SELECT designation_id FROM designations WHERE designation_name = 'Building Administrator' LIMIT 1);
SET @desig_accountant = (SELECT designation_id FROM designations WHERE designation_name = 'Accountant' LIMIT 1);
SET @desig_acadhead = (SELECT designation_id FROM designations WHERE designation_name = 'Academic Head' LIMIT 1);
SET @desig_hr = (SELECT designation_id FROM designations WHERE designation_name = 'HR' LIMIT 1);
SET @desig_schooladmin = (SELECT designation_id FROM designations WHERE designation_name = 'School Administrator' LIMIT 1);
SET @desig_programhead = (SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1);

-- Insert Regular Staff records
INSERT INTO staff (employee_number, user_id, designation_id, staff_category, employment_status, is_active, created_at) VALUES
('LCA4001P', (SELECT user_id FROM users WHERE username = 'LCA4001P'), @desig_guidance, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4002P', (SELECT user_id FROM users WHERE username = 'LCA4002P'), @desig_discipline, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4003P', (SELECT user_id FROM users WHERE username = 'LCA4003P'), @desig_clinic, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4004P', (SELECT user_id FROM users WHERE username = 'LCA4004P'), @desig_librarian, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4005P', (SELECT user_id FROM users WHERE username = 'LCA4005P'), @desig_alumni, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4006P', (SELECT user_id FROM users WHERE username = 'LCA4006P'), @desig_sao, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4007P', (SELECT user_id FROM users WHERE username = 'LCA4007P'), @desig_registrar, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4008P', (SELECT user_id FROM users WHERE username = 'LCA4008P'), @desig_cashier, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4009P', (SELECT user_id FROM users WHERE username = 'LCA4009P'), @desig_pamo, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4010P', (SELECT user_id FROM users WHERE username = 'LCA4010P'), @desig_misit, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4011P', (SELECT user_id FROM users WHERE username = 'LCA4011P'), @desig_petty, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4012P', (SELECT user_id FROM users WHERE username = 'LCA4012P'), @desig_building, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4013P', (SELECT user_id FROM users WHERE username = 'LCA4013P'), @desig_accountant, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4014P', (SELECT user_id FROM users WHERE username = 'LCA4014P'), @desig_acadhead, 'Regular Staff', 'Full Time', 1, NOW()),
('LCA4015P', (SELECT user_id FROM users WHERE username = 'LCA4015P'), @desig_hr, 'Regular Staff', 'Full Time', 1, NOW()),

-- School Administrator
('LCA5001P', (SELECT user_id FROM users WHERE username = 'LCA5001P'), @desig_schooladmin, 'School Administrator', 'Full Time', 1, NOW()),

-- Program Heads
('LCA3001P', (SELECT user_id FROM users WHERE username = 'LCA3001P'), @desig_programhead, 'Program Head', 'Full Time', 1, NOW()),
('LCA3002P', (SELECT user_id FROM users WHERE username = 'LCA3002P'), @desig_programhead, 'Program Head', 'Full Time', 1, NOW()),
('LCA3003P', (SELECT user_id FROM users WHERE username = 'LCA3003P'), @desig_programhead, 'Program Head', 'Full Time', 1, NOW());

-- =====================================================
-- 4. ASSIGN ROLES TO USERS
-- =====================================================

-- Get role IDs
SET @role_staff = (SELECT role_id FROM roles WHERE role_name = 'Staff' LIMIT 1);
SET @role_admin = (SELECT role_id FROM roles WHERE role_name = 'Admin' LIMIT 1);

-- Assign Staff role to Regular Staff and School Administrator
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, @role_staff, NOW(), TRUE
FROM users u
WHERE u.username IN (
    'LCA4001P', 'LCA4002P', 'LCA4003P', 'LCA4004P', 'LCA4005P',
    'LCA4006P', 'LCA4007P', 'LCA4008P', 'LCA4009P', 'LCA4010P',
    'LCA4011P', 'LCA4012P', 'LCA4013P', 'LCA4014P', 'LCA4015P',
    'LCA5001P'
);

-- Assign Staff role to Program Heads
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, @role_staff, NOW(), TRUE
FROM users u
WHERE u.username IN ('LCA3001P', 'LCA3002P', 'LCA3003P');

-- =====================================================
-- 5. VERIFICATION QUERIES
-- =====================================================

-- Show all staff data
SELECT 
    'Fresh Staff Data' as info,
    s.employee_number,
    u.first_name,
    u.last_name,
    s.staff_category,
    d.designation_name,
    s.employment_status,
    s.is_active
FROM staff s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN designations d ON s.designation_id = d.designation_id
ORDER BY s.staff_category, s.employee_number;

-- Count by category
SELECT 
    'Staff Count by Category' as info,
    staff_category,
    COUNT(*) as count
FROM staff
GROUP BY staff_category
ORDER BY staff_category;

-- Count total
SELECT 
    'Total Staff Count' as info,
    COUNT(*) as total_staff
FROM staff;

COMMIT;

-- =====================================================
-- MIGRATION COMPLETED
-- =====================================================
SELECT 'Fresh Staff Data Created Successfully!' as message;
SELECT '15 Regular Staff + 1 School Administrator + 3 Program Heads = 19 total' as staff_info;
SELECT 'Only Program Heads will get department assignments in next step' as next_step;
