-- Check existing permissions to see what's already in the system
SELECT permission_id, permission_name, description, category, is_active 
FROM permissions 
WHERE permission_name LIKE '%import%' OR permission_name LIKE '%data%'
ORDER BY permission_id;

-- Check if import_data permission already exists
SELECT COUNT(*) as import_data_exists 
FROM permissions 
WHERE permission_name = 'import_data';

-- Check current role_permissions for import-related permissions
SELECT rp.role_id, r.role_name, p.permission_name, rp.granted_at
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.role_id
JOIN permissions p ON rp.permission_id = p.permission_id
WHERE p.permission_name LIKE '%import%' OR p.permission_name LIKE '%data%'
ORDER BY r.role_name, p.permission_name;
