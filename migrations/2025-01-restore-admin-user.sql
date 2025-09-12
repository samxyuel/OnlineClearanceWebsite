-- Restore Admin User for System Access
-- This script adds back the admin user to the users table

USE online_clearance_db;

-- Check if admin user already exists
SELECT 'Checking for existing admin user...' as info;
SELECT user_id, username, role FROM users WHERE username = 'admin';

-- Insert admin user if it doesn't exist
INSERT INTO users (username, password, role, is_active, created_at, updated_at)
SELECT 'admin', 'admin123', 'admin', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Verify admin user was created
SELECT 'Admin user status:' as info;
SELECT user_id, username, role, is_active, created_at FROM users WHERE username = 'admin';

-- Show total user count
SELECT 'Total users in system:' as info, COUNT(*) as user_count FROM users;
