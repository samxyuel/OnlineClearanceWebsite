-- Create Missing School Administrator
USE online_clearance_db;

-- Check if School Administrator exists
SELECT 'Checking for School Administrator...' as info;
SELECT s.employee_number, u.first_name, u.last_name, d.designation_name 
FROM staff s 
JOIN users u ON u.user_id = s.user_id 
LEFT JOIN designations d ON d.designation_id = s.designation_id 
WHERE s.employee_number = 'LCA5000P';

-- Create School Administrator if missing
INSERT IGNORE INTO users (user_id, username, password, email, first_name, last_name, middle_name, contact_number, profile_picture, status, must_change_password, can_apply, last_login, password_reset_token, password_reset_expires, created_at, updated_at)
VALUES (NULL, 'LCA5000P', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'schooladmin@system.local', 'School', 'Administrator', NULL, '+63 912 345 6789', NULL, 'active', 0, 1, NULL, NULL, NULL, NOW(), NOW());

-- Get the user_id of the created user
SET @school_admin_user_id = LAST_INSERT_ID();

-- Create School Administrator designation if it doesn't exist
INSERT IGNORE INTO designations (designation_name, description, is_active, created_at, updated_at)
VALUES ('School Administrator', 'School Administrator', 1, NOW(), NOW());

-- Get designation_id
SET @school_admin_designation_id = (SELECT designation_id FROM designations WHERE designation_name = 'School Administrator');

-- Create staff record
INSERT IGNORE INTO staff (user_id, employee_number, designation_id, is_active, created_at, updated_at)
VALUES (@school_admin_user_id, 'LCA5000P', @school_admin_designation_id, 1, NOW(), NOW());

-- Assign School Administrator role
INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at)
VALUES (@school_admin_user_id, (SELECT role_id FROM roles WHERE role_name = 'School Administrator'), NOW());

-- Verify creation
SELECT 'School Administrator Created:' as info;
SELECT s.employee_number, u.first_name, u.last_name, d.designation_name, r.role_name
FROM staff s 
JOIN users u ON u.user_id = s.user_id 
LEFT JOIN designations d ON d.designation_id = s.designation_id
LEFT JOIN user_roles ur ON ur.user_id = u.user_id
LEFT JOIN roles r ON r.role_id = ur.role_id
WHERE s.employee_number = 'LCA5000P';
