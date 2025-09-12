-- Create Admin User with user_id = 1
-- This script ensures the admin user has user_id = 1

USE online_clearance_db;

-- Step 1: Delete any existing admin user
DELETE FROM user_roles WHERE user_id IN (SELECT user_id FROM users WHERE username = 'admin');
DELETE FROM users WHERE username = 'admin';

-- Step 2: Reset AUTO_INCREMENT to start from 1
ALTER TABLE users AUTO_INCREMENT = 1;

-- Step 3: Create admin role if it doesn't exist
INSERT IGNORE INTO roles (role_name, description, is_active, created_at, updated_at)
VALUES ('admin', 'System Administrator', 1, NOW(), NOW());

-- Step 4: Insert admin user with explicit user_id = 1
INSERT INTO users (user_id, username, password, first_name, last_name, email, status, created_at, updated_at)
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin@system.local', 'active', NOW(), NOW());

-- Step 5: Get admin role ID
SET @admin_role_id = (SELECT role_id FROM roles WHERE role_name = 'admin');

-- Step 6: Assign admin role to admin user
INSERT INTO user_roles (user_id, role_id, assigned_at)
VALUES (1, @admin_role_id, NOW());

-- Step 7: Verify setup
SELECT 'Admin User Created with user_id = 1:' as info;
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.email,
    u.status,
    r.role_name
FROM users u
JOIN user_roles ur ON u.user_id = ur.user_id
JOIN roles r ON ur.role_id = r.role_id
WHERE u.user_id = 1;

-- Show login credentials
SELECT 'Login Credentials:' as info;
SELECT 'Username: admin' as credential;
SELECT 'Password: admin123' as credential;
SELECT 'User ID: 1' as credential;
