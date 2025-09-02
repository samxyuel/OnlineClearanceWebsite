<?php
// Fix Admin User Permissions for Clearance Management
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h2>🔧 Fixing Admin User Permissions for Clearance Management</h2>\n";
    
    // Start transaction
    $connection->beginTransaction();
    
    // 1. Check if admin user exists
    $stmt = $connection->prepare("SELECT user_id, username FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        echo "<p style='color: red;'>❌ Admin user not found!</p>\n";
        exit;
    }
    
    echo "<p>✅ Found admin user: {$adminUser['username']} (ID: {$adminUser['user_id']})</p>\n";
    
    // 2. Check if Admin role exists
    $stmt = $connection->prepare("SELECT role_id, role_name FROM roles WHERE role_name = 'Admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminRole) {
        echo "<p style='color: red;'>❌ Admin role not found!</p>\n";
        exit;
    }
    
    echo "<p>✅ Found Admin role: {$adminRole['role_name']} (ID: {$adminRole['role_id']})</p>\n";
    
    // 3. Check if admin user has Admin role assigned
    $stmt = $connection->prepare("SELECT * FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$adminUser['user_id'], $adminRole['role_id']]);
    $userRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userRole) {
        echo "<p style='color: orange;'>⚠️ Admin user doesn't have Admin role assigned. Adding...</p>\n";
        
        $stmt = $connection->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
        $stmt->execute([$adminUser['user_id'], $adminRole['role_id']]);
        
        echo "<p style='color: green;'>✅ Admin role assigned to admin user</p>\n";
    } else {
        echo "<p>✅ Admin user already has Admin role assigned</p>\n";
    }
    
    // 4. Add clearance management permissions to Admin role
    $clearancePermissions = [
        'manage_clearance_periods',
        'manage_clearance_requirements',
        'manage_clearance_applications',
        'view_clearance_status',
        'sign_clearance',
        'manage_clearance_settings'
    ];
    
    echo "<h3>📋 Adding Clearance Management Permissions</h3>\n";
    
    foreach ($clearancePermissions as $permission) {
        // Check if permission exists
        $stmt = $connection->prepare("SELECT permission_id FROM permissions WHERE permission_name = ?");
        $stmt->execute([$permission]);
        $permissionExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$permissionExists) {
            echo "<p style='color: orange;'>⚠️ Permission '{$permission}' doesn't exist. Creating...</p>\n";
            
            $stmt = $connection->prepare("INSERT INTO permissions (permission_name, description, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$permission, "Permission to {$permission}"]);
            
            $permissionId = $connection->lastInsertId();
            echo "<p style='color: green;'>✅ Created permission '{$permission}' (ID: {$permissionId})</p>\n";
        } else {
            $permissionId = $permissionExists['permission_id'];
            echo "<p>✅ Permission '{$permission}' already exists (ID: {$permissionId})</p>\n";
        }
        
        // Check if Admin role already has this permission
        $stmt = $connection->prepare("SELECT * FROM role_permissions WHERE role_id = ? AND permission_id = ?");
        $stmt->execute([$adminRole['role_id'], $permissionId]);
        $rolePermission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rolePermission) {
            echo "<p style='color: orange;'>⚠️ Adding '{$permission}' to Admin role...</p>\n";
            
            $stmt = $connection->prepare("INSERT INTO role_permissions (role_id, permission_id, granted_at) VALUES (?, ?, NOW())");
            $stmt->execute([$adminRole['role_id'], $permissionId]);
            
            echo "<p style='color: green;'>✅ Permission '{$permission}' granted to Admin role</p>\n";
        } else {
            echo "<p>✅ Admin role already has '{$permission}' permission</p>\n";
        }
    }
    
    // 5. Verify all permissions are now assigned
    echo "<h3>🔍 Verifying Permissions</h3>\n";
    
    $stmt = $connection->prepare("
        SELECT p.permission_name, p.description
        FROM permissions p
        JOIN role_permissions rp ON p.permission_id = rp.permission_id
        WHERE rp.role_id = ? AND p.is_active = 1
        ORDER BY p.permission_name
    ");
    $stmt->execute([$adminRole['role_id']]);
    $assignedPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total permissions assigned to Admin role:</strong> " . count($assignedPermissions) . "</p>\n";
    
    if (count($assignedPermissions) > 0) {
        echo "<ul>\n";
        foreach ($assignedPermissions as $perm) {
            echo "<li><strong>{$perm['permission_name']}</strong>: {$perm['description']}</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Commit transaction
    $connection->commit();
    
    echo "<p style='color: green; font-weight: bold;'>🎉 All clearance management permissions fixed successfully!</p>\n";
    echo "<p>Admin user now has all necessary permissions to manage clearance operations.</p>\n";
    
} catch (Exception $e) {
    if ($connection->inTransaction()) { 
        $connection->rollBack(); 
    }
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
} catch (PDOException $e) {
    if ($connection->inTransaction()) { 
        $connection->rollBack(); 
    }
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>\n";
}
?>
