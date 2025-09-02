-- Setup Import Permissions (Corrected Version)
-- This script checks if permissions exist before adding them

-- Step 1: Check if import_data permission exists, if not, add it
INSERT IGNORE INTO permissions (permission_id, permission_name, description, category, is_active) 
VALUES (28, 'import_data', 'Permission to import data from files', 'data_management', 1);

-- Step 2: Grant import_data permission to Admin role (if not already granted)
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_at) 
SELECT 1, p.permission_id, NOW()
FROM permissions p 
WHERE p.permission_name = 'import_data'
AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp 
    WHERE rp.role_id = 1 AND rp.permission_id = p.permission_id
);

-- Step 3: Grant import_data permission to School Administrator role (if not already granted)
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_at) 
SELECT 5, p.permission_id, NOW()
FROM permissions p 
WHERE p.permission_name = 'import_data'
AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp 
    WHERE rp.role_id = 5 AND rp.permission_id = p.permission_id
);

-- Step 4: Verify the setup
SELECT 
    'Permission Check' as check_type,
    permission_id,
    permission_name,
    description,
    category,
    is_active
FROM permissions 
WHERE permission_name = 'import_data';

SELECT 
    'Role Permission Check' as check_type,
    r.role_name,
    p.permission_name,
    rp.granted_at
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.role_id
JOIN permissions p ON rp.permission_id = p.permission_id
WHERE p.permission_name = 'import_data'
ORDER BY r.role_name;
