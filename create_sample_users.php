<?php
// Create Sample Users for Testing
echo "<h1>👥 Creating Sample Users</h1>";

try {
    $db = new PDO("mysql:host=localhost;dbname=online_clearance_db;charset=utf8mb4", "root", "");
    
    // Sample users data
    $sampleUsers = [
        [
            'username' => 'schooladmin',
            'email' => 'schooladmin@gosti.edu.ph',
            'password' => 'admin123',
            'first_name' => 'Dr. Robert',
            'last_name' => 'Johnson',
            'role_name' => 'School Administrator'
        ],
        [
            'username' => 'programhead',
            'email' => 'programhead@gosti.edu.ph',
            'password' => 'admin123',
            'first_name' => 'Prof. Maria',
            'last_name' => 'Santos',
            'role_name' => 'Program Head'
        ],
        [
            'username' => 'faculty1',
            'email' => 'faculty1@gosti.edu.ph',
            'password' => 'faculty123',
            'first_name' => 'Prof. Juan',
            'last_name' => 'Dela Cruz',
            'role_name' => 'Faculty'
        ],
        [
            'username' => 'student1',
            'email' => 'student1@gosti.edu.ph',
            'password' => 'student123',
            'first_name' => 'Zinzu Chan',
            'last_name' => 'Lee',
            'role_name' => 'Student'
        ]
    ];
    
    echo "<h2>📋 Sample Users to Create:</h2>";
    echo "<ul>";
    foreach ($sampleUsers as $user) {
        echo "<li><strong>{$user['username']}</strong> - {$user['first_name']} {$user['last_name']} ({$user['role_name']})</li>";
    }
    echo "</ul>";
    
    // Get or create roles
    $roles = [];
    $stmt = $db->query("SELECT role_id, role_name FROM roles");
    $existingRoles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "<h2>🔍 Existing Roles:</h2>";
    foreach ($existingRoles as $roleId => $roleName) {
        echo "• ID {$roleId}: {$roleName}<br>";
        $roles[$roleName] = $roleId;
    }
    
    // Create missing roles if needed
    $requiredRoles = ['School Administrator', 'Program Head', 'Faculty', 'Student'];
    foreach ($requiredRoles as $roleName) {
        if (!isset($roles[$roleName])) {
            $stmt = $db->prepare("INSERT INTO roles (role_name, description, is_active, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$roleName, "Role for {$roleName}", 1]);
            $roleId = $db->lastInsertId();
            $roles[$roleName] = $roleId;
            echo "✅ Created role: {$roleName} (ID: {$roleId})<br>";
        }
    }
    
    echo "<hr>";
    echo "<h2>👤 Creating Users:</h2>";
    
    foreach ($sampleUsers as $userData) {
        // Check if user already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$userData['username']]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            echo "⚠️ User <strong>{$userData['username']}</strong> already exists (ID: {$existingUser['user_id']})<br>";
            continue;
        }
        
        // Create user
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['first_name'],
            $userData['last_name'],
            'active'
        ]);
        
        $userId = $db->lastInsertId();
        
        // Assign role
        $roleId = $roles[$userData['role_name']];
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $roleId]);
        
        echo "✅ Created user <strong>{$userData['username']}</strong> (ID: {$userId}) with role {$userData['role_name']}<br>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 Login Credentials:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th><th>Purpose</th></tr>";
    foreach ($sampleUsers as $user) {
        echo "<tr>";
        echo "<td><code>{$user['username']}</code></td>";
        echo "<td><code>{$user['password']}</code></td>";
        echo "<td>{$user['role_name']}</td>";
        echo "<td>Testing {$user['role_name']} functionality</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>🚀 Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Test login with these sample users</li>";
    echo "<li>Test the User Management APIs</li>";
    echo "<li>Create additional users as needed</li>";
    echo "<li>Move to Phase 3: Clearance Management APIs</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
