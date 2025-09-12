-- Final Admin User Creation
USE online_clearance_db;

-- Delete any existing admin user
DELETE FROM user_roles WHERE user_id IN (SELECT user_id FROM users WHERE username = 'admin');
DELETE FROM users WHERE username = 'admin';

-- Reset auto increment
ALTER TABLE users AUTO_INCREMENT = 1;

-- Create admin role
INSERT IGNORE INTO roles (role_name, description, is_active, created_at, updated_at)
VALUES ('admin', 'System Administrator', 1, NOW(), NOW());

-- Create admin user with user_id = 1
INSERT INTO users (user_id, username, password, email, first_name, last_name, middle_name, contact_number, profile_picture, status, must_change_password, can_apply, last_login, password_reset_token, password_reset_expires, created_at, updated_at)
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@system.local', 'System', 'Administrator', NULL, NULL, NULL, 'active', 0, 1, NULL, NULL, NULL, NOW(), NOW());

-- Assign admin role
INSERT INTO user_roles (user_id, role_id, assigned_at)
VALUES (1, (SELECT role_id FROM roles WHERE role_name = 'admin'), NOW());

-- Verify
SELECT 'Admin User Created:' as info;
SELECT user_id, username, first_name, last_name, status FROM users WHERE user_id = 1;
SELECT 'Login: admin / admin123' as credentials;
