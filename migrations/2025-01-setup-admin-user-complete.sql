-- Complete Admin User Setup with Role System
-- This script creates the admin user with proper role-based authentication

USE online_clearance_db;

-- Step 1: Create admin role if it doesn't exist
INSERT IGNORE INTO roles (role_name, description, is_active, created_at, updated_at)
VALUES ('admin', 'System Administrator', 1, NOW(), NOW());

-- Step 2: Create admin user if it doesn't exist
INSERT IGNORE INTO users (username, password, first_name, last_name, email, status, created_at, updated_at)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin@system.local', 'active', NOW(), NOW());

-- Step 3: Get the IDs
SET @admin_user_id = (SELECT user_id FROM users WHERE username = 'admin');
SET @admin_role_id = (SELECT role_id FROM roles WHERE role_name = 'admin');

-- Step 4: Assign admin role to admin user
INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at)
VALUES (@admin_user_id, @admin_role_id, NOW());

-- Step 5: Verify setup
SELECT 'Admin User Setup Complete:' as info;
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
WHERE u.username = 'admin';

-- Show login credentials
SELECT 'Login Credentials:' as info;
SELECT 'Username: admin' as credential;
SELECT 'Password: admin123' as credential;
