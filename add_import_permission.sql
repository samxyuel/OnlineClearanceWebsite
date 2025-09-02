-- Add import_data permission to the system
INSERT INTO permissions (permission_id, permission_name, description, category, is_active) 
VALUES (28, 'import_data', 'Permission to import data from files', 'data_management', 1);

-- Grant import_data permission to Admin role
INSERT INTO role_permissions (role_id, permission_id, granted_at) 
VALUES (1, 28, NOW());

-- Grant import_data permission to School Administrator role
INSERT INTO role_permissions (role_id, permission_id, granted_at) 
VALUES (5, 28, NOW());
