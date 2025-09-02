-- Fix Import Permissions - Correct Role Assignments
-- Remove import_data from School Administrator and add to Program Head

-- Step 1: Remove import_data permission from School Administrator role (Role ID 5)
DELETE FROM role_permissions 
WHERE role_id = 5 
AND permission_id = (
    SELECT permission_id 
    FROM permissions 
    WHERE permission_name = 'import_data'
);

-- Step 2: Add import_data permission to Program Head role (Role ID 6)
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_at) 
SELECT 6, p.permission_id, NOW()
FROM permissions p 
WHERE p.permission_name = 'import_data'
AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp 
    WHERE rp.role_id = 6 AND rp.permission_id = p.permission_id
);

-- Step 3: Verify the corrected setup
SELECT 
    'Corrected Role Permissions' as check_type,
    r.role_name,
    p.permission_name,
    rp.granted_at
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.role_id
JOIN permissions p ON rp.permission_id = p.permission_id
WHERE p.permission_name = 'import_data'
ORDER BY r.role_name;

-- Step 4: Show all roles and their import_data permission status
SELECT 
    r.role_id,
    r.role_name,
    CASE 
        WHEN rp.permission_id IS NOT NULL THEN 'YES'
        ELSE 'NO'
    END as has_import_permission,
    rp.granted_at
FROM roles r
LEFT JOIN role_permissions rp ON r.role_id = rp.role_id 
    AND rp.permission_id = (
        SELECT permission_id 
        FROM permissions 
        WHERE permission_name = 'import_data'
    )
ORDER BY r.role_id;
