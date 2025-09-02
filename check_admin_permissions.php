<?php
// Check Admin User Permissions
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h2>Admin User Permissions Check</h2>\n";
    
    // Check admin user
    $stmt = $connection->prepare("
        SELECT u.*, ur.role_id, r.role_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
        LEFT JOIN roles r ON ur.role_id = r.role_id 
        WHERE u.username = 'admin'
    ");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser) {
        echo "<h3>Admin User Info:</h3>\n";
        echo "<pre>" . print_r($adminUser, true) . "</pre>\n";
        
        // Check role permissions
        $stmt = $connection->prepare("
            SELECT p.permission_name, p.description, p.category
            FROM permissions p 
            JOIN role_permissions rp ON p.permission_id = rp.permission_id 
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$adminUser['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Admin Role Permissions:</h3>\n";
        if ($permissions) {
            echo "<table border='1'>\n";
            echo "<tr><th>Permission</th><th>Description</th><th>Category</th></tr>\n";
            foreach ($permissions as $perm) {
                echo "<tr><td>{$perm['permission_name']}</td><td>{$perm['description']}</td><td>{$perm['category']}</td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p style='color: red;'>No permissions found for admin role!</p>\n";
        }
        
        // Check if create_users permission exists
        $stmt = $connection->prepare("SELECT permission_id, permission_name FROM permissions WHERE permission_name = 'create_users'");
        $stmt->execute();
        $createUsersPerm = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Permission Check:</h3>\n";
        if ($createUsersPerm) {
            echo "<p>✅ 'create_users' permission exists (ID: {$createUsersPerm['permission_id']})</p>\n";
            
            // Check if admin has this permission
            $stmt = $connection->prepare("
                SELECT COUNT(*) as has_permission 
                FROM role_permissions rp 
                WHERE rp.role_id = ? AND rp.permission_id = ?
            ");
            $stmt->execute([$adminUser['role_id'], $createUsersPerm['permission_id']]);
            $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($hasPermission['has_permission'] > 0) {
                echo "<p>✅ Admin role has 'create_users' permission</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Admin role MISSING 'create_users' permission</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ 'create_users' permission does not exist in database!</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>Admin user not found!</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>\n";
}
?>
