<?php
// Fix Admin User Permissions
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h2>Fixing Admin User Permissions</h2>\n";
    
    // Start transaction
    $connection->beginTransaction();
    
    // Get admin user ID
    $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        throw new Exception("Admin user not found!");
    }
    
    $adminUserId = $adminUser['user_id'];
    echo "<p>✅ Admin user found (ID: {$adminUserId})</p>\n";
    
    // Get admin role ID
    $stmt = $connection->prepare("SELECT role_id FROM roles WHERE role_name = 'Admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminRole) {
        throw new Exception("Admin role not found!");
    }
    
    $adminRoleId = $adminRole['role_id'];
    echo "<p>✅ Admin role found (ID: {$adminRoleId})</p>\n";
    
    // Ensure admin has the admin role assigned
    $stmt = $connection->prepare("
        INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at, is_primary) 
        VALUES (?, ?, NOW(), TRUE)
    ");
    $stmt->execute([$adminUserId, $adminRoleId]);
    echo "<p>✅ Admin role assignment verified</p>\n";
    
    // Get all permissions
    $stmt = $connection->query("SELECT permission_id FROM permissions WHERE is_active = 1");
    $allPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>✅ Found " . count($allPermissions) . " active permissions</p>\n";
    
    // Grant all permissions to admin role
    $stmt = $connection->prepare("
        INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_at, granted_by) 
        VALUES (?, ?, NOW(), ?)
    ");
    
    $grantedCount = 0;
    foreach ($allPermissions as $permissionId) {
        $stmt->execute([$adminRoleId, $permissionId, $adminUserId]);
        if ($stmt->rowCount() > 0) {
            $grantedCount++;
        }
    }
    
    echo "<p>✅ Granted {$grantedCount} new permissions to admin role</p>\n";
    
    // Verify permissions
    $stmt = $connection->prepare("
        SELECT COUNT(*) as total_permissions
        FROM role_permissions rp 
        WHERE rp.role_id = ?
    ");
    $stmt->execute([$adminRoleId]);
    $totalPermissions = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Admin role now has {$totalPermissions['total_permissions']} total permissions</p>\n";
    
    // Check specific permissions
    $requiredPermissions = ['create_users', 'edit_users', 'delete_users', 'view_users', 'reset_passwords'];
    echo "<h3>Required Permission Check:</h3>\n";
    
    foreach ($requiredPermissions as $permName) {
        $stmt = $connection->prepare("
            SELECT COUNT(*) as has_permission 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = ? AND p.permission_name = ?
        ");
        $stmt->execute([$adminRoleId, $permName]);
        $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hasPermission['has_permission'] > 0) {
            echo "<p>✅ {$permName}</p>\n";
        } else {
            echo "<p style='color: red;'>❌ {$permName} - MISSING!</p>\n";
        }
    }
    
    // Commit transaction
    $connection->commit();
    echo "<p style='color: green; font-weight: bold;'>✅ All permissions fixed successfully!</p>\n";
    
} catch (Exception $e) {
    // Rollback transaction
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
} catch (PDOException $e) {
    // Rollback transaction
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>\n";
}
?>
