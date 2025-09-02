<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Adding missing clearance permissions to Admin role...\n";
    
    // Add the missing permission to permissions table
    $stmt = $connection->prepare("INSERT IGNORE INTO permissions (permission_name, description) VALUES (?, ?)");
    $stmt->execute(['manage_clearance_status', 'Manage clearance signatory status']);
    
    // Get the permission ID
    $stmt = $connection->prepare("SELECT permission_id FROM permissions WHERE permission_name = ?");
    $stmt->execute(['manage_clearance_status']);
    $permission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($permission) {
        // Get Admin role ID
        $stmt = $connection->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->execute(['Admin']);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($role) {
            // Assign permission to Admin role
            $stmt = $connection->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$role['role_id'], $permission['permission_id']]);
            
            echo "✅ Successfully added manage_clearance_status permission to Admin role\n";
        } else {
            echo "❌ Admin role not found\n";
        }
    } else {
        echo "❌ Permission not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
