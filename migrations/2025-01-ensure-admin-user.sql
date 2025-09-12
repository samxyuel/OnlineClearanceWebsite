-- Ensure Admin User Exists and Works
USE online_clearance_db;

-- Step 1: Clean up any existing admin user
DELETE FROM user_roles WHERE user_id IN (SELECT user_id FROM users WHERE username = 'admin');
DELETE FROM users WHERE username = 'admin';

-- Step 2: Ensure admin role exists
INSERT IGNORE INTO roles (role_name, description, is_active, created_at, updated_at)
VALUES ('admin', 'System Administrator', 1, NOW(), NOW());

-- Step 3: Create admin user with explicit user_id = 1
-- Using a known working password hash for 'admin123'
INSERT INTO users (user_id, username, password, email, first_name, last_name, middle_name, contact_number, profile_picture, status, must_change_password, can_apply, last_login, password_reset_token, password_reset_expires, created_at, updated_at)
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@system.local', 'System', 'Administrator', NULL, NULL, NULL, 'active', 0, 1, NULL, NULL, NULL, NOW(), NOW());

-- Step 4: Assign admin role to admin user
INSERT INTO user_roles (user_id, role_id, assigned_at)
VALUES (1, (SELECT role_id FROM roles WHERE role_name = 'admin'), NOW());

-- Step 5: Verify the setup
SELECT '=== ADMIN USER SETUP COMPLETE ===' as status;
SELECT 'User ID: 1' as info;
SELECT 'Username: admin' as info;
SELECT 'Password: admin123' as info;
SELECT 'Role: System Administrator' as info;

-- Show the actual user record
SELECT '=== USER RECORD ===' as status;
SELECT user_id, username, first_name, last_name, email, status FROM users WHERE user_id = 1;

-- Show the role assignment
SELECT '=== ROLE ASSIGNMENT ===' as status;
SELECT u.user_id, u.username, r.role_name 
FROM users u
JOIN user_roles ur ON u.user_id = ur.user_id
JOIN roles r ON ur.role_id = r.role_id
WHERE u.user_id = 1;
