<?php
// Fix admin user script
echo "<h2>Admin User Fix Script</h2>";

try {
    $db = new PDO("mysql:host=localhost;dbname=online_clearance_db;charset=utf8mb4", "root", "");
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "✅ Admin user already exists (ID: {$existingUser['user_id']})<br>";
        
        // Update password to admin123
        $newPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$newPasswordHash, 'admin']);
        echo "✅ Admin password updated to 'admin123'<br>";
        
        $userId = $existingUser['user_id'];
    } else {
        echo "⚠️ Admin user doesn't exist, creating new one...<br>";
        
        // Create admin user
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@gosti.edu.ph', $passwordHash, 'System', 'Administrator', 'active']);
        
        $userId = $db->lastInsertId();
        echo "✅ Admin user created (ID: {$userId})<br>";
    }
    
    // Check if Admin role exists
    $stmt = $db->prepare("SELECT role_id FROM roles WHERE role_name = ?");
    $stmt->execute(['Admin']);
    $adminRole = $stmt->fetch();
    
    if ($adminRole) {
        echo "✅ Admin role exists (ID: {$adminRole['role_id']})<br>";
        $roleId = $adminRole['role_id'];
    } else {
        echo "⚠️ Admin role doesn't exist, creating new one...<br>";
        
        // Create Admin role
        $stmt = $db->prepare("INSERT INTO roles (role_name, description, is_active, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute(['Admin', 'System Administrator with full access', 1]);
        
        $roleId = $db->lastInsertId();
        echo "✅ Admin role created (ID: {$roleId})<br>";
    }
    
    // Check if user-role relationship exists
    $stmt = $db->prepare("SELECT * FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$userId, $roleId]);
    $userRole = $stmt->fetch();
    
    if ($userRole) {
        echo "✅ User-role relationship already exists<br>";
    } else {
        echo "⚠️ User-role relationship missing, creating...<br>";
        
        // Create user-role relationship
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $roleId]);
        echo "✅ User-role relationship created<br>";
    }
    
    echo "<hr>";
    echo "✅ Admin user setup complete!<br>";
    echo "You can now login with:<br>";
    echo "&nbsp;&nbsp;• Username: <strong>admin</strong><br>";
    echo "&nbsp;&nbsp;• Password: <strong>admin123</strong><br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
