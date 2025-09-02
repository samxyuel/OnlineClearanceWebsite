<?php
// Check users in database
echo "<h2>Database Users Check</h2>";

try {
    $db = new PDO("mysql:host=localhost;dbname=online_clearance_db;charset=utf8mb4", "root", "");
    
    // Check users table
    echo "<h3>Users Table:</h3>";
    $stmt = $db->query("SELECT user_id, username, email, status, created_at FROM users LIMIT 10");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No users found in users table<br>";
    } else {
        echo "✅ Found " . count($users) . " users:<br>";
        foreach ($users as $user) {
            echo "&nbsp;&nbsp;• ID: {$user['user_id']} | Username: {$user['username']} | Status: {$user['status']}<br>";
        }
    }
    
    // Check roles table
    echo "<h3>Roles Table:</h3>";
    $stmt = $db->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll();
    
    if (empty($roles)) {
        echo "❌ No roles found in roles table<br>";
    } else {
        echo "✅ Found " . count($roles) . " roles:<br>";
        foreach ($roles as $role) {
            echo "&nbsp;&nbsp;• ID: {$role['role_id']} | Name: {$role['role_name']} | Status: {$role['is_active']}<br>";
        }
    }
    
    // Check user_roles table
    echo "<h3>User Roles Table:</h3>";
    $stmt = $db->query("SELECT ur.*, u.username, r.role_name 
                        FROM user_roles ur 
                        JOIN users u ON ur.user_id = u.user_id 
                        JOIN roles r ON ur.role_id = r.role_id 
                        LIMIT 10");
    $userRoles = $stmt->fetchAll();
    
    if (empty($userRoles)) {
        echo "❌ No user roles found in user_roles table<br>";
    } else {
        echo "✅ Found " . count($userRoles) . " user roles:<br>";
        foreach ($userRoles as $ur) {
            echo "&nbsp;&nbsp;• User: {$ur['username']} | Role: {$ur['role_name']}<br>";
        }
    }
    
    // Check if admin user exists and has correct password
    echo "<h3>Admin User Check:</h3>";
    $stmt = $db->prepare("SELECT u.*, ur.role_id, r.role_name 
                          FROM users u 
                          JOIN user_roles ur ON u.user_id = ur.user_id 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE u.username = ?");
    $stmt->execute(['admin']);
    $adminUser = $stmt->fetch();
    
    if ($adminUser) {
        echo "✅ Admin user found:<br>";
        echo "&nbsp;&nbsp;• Username: {$adminUser['username']}<br>";
        echo "&nbsp;&nbsp;• Role: {$adminUser['role_name']}<br>";
        echo "&nbsp;&nbsp;• Status: {$adminUser['status']}<br>";
        echo "&nbsp;&nbsp;• Password Hash: " . substr($adminUser['password'], 0, 20) . "...<br>";
        
        // Test password verification
        $testPassword = 'admin123';
        if (password_verify($testPassword, $adminUser['password'])) {
            echo "✅ Password 'admin123' is correct!<br>";
        } else {
            echo "❌ Password 'admin123' is incorrect<br>";
            echo "&nbsp;&nbsp;Current hash: " . $adminUser['password'] . "<br>";
            echo "&nbsp;&nbsp;Expected hash for 'admin123': " . password_hash($testPassword, PASSWORD_DEFAULT) . "<br>";
        }
    } else {
        echo "❌ Admin user not found<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Possible Issues:</h3>";
echo "1. Admin user doesn't exist<br>";
echo "2. Admin user exists but password is wrong<br>";
echo "3. Admin user exists but role assignment is missing<br>";
echo "4. Admin user status is not 'active'<br>";
?>
