<?php
// Web-based test for authentication fixes
?>
<!DOCTYPE html>
<html>
<head>
    <title>Authentication Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Authentication System Test</h1>
    
    <?php
    // Test 1: Session Management
    echo "<div class='test-section'>";
    echo "<h2>1. Session Management Test</h2>";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "<p class='success'>✓ Session started successfully</p>";
    } else {
        echo "<p class='success'>✓ Session already active</p>";
    }
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
    echo "</div>";
    
    // Test 2: Auth Class
    echo "<div class='test-section'>";
    echo "<h2>2. Auth Class Test</h2>";
    
    require_once 'includes/classes/Auth.php';
    $auth = new Auth();
    
    echo "<p>Is Logged In: " . ($auth->isLoggedIn() ? '<span class="success">YES</span>' : '<span class="warning">NO</span>') . "</p>";
    echo "<p>User ID: " . ($auth->getUserId() ?? '<span class="warning">NULL</span>') . "</p>";
    echo "<p>Role Name: " . ($auth->getRoleName() ?? '<span class="warning">NULL</span>') . "</p>";
    echo "</div>";
    
    // Test 3: Header Component Logic
    echo "<div class='test-section'>";
    echo "<h2>3. Header Component Logic Test</h2>";
    
    $userId = $auth->getUserId();
    $userName = 'Unknown User';
    $userRole = 'Unknown Role';
    
    if ($userId) {
        try {
            require_once 'includes/config/database.php';
            $pdo = Database::getInstance()->getConnection();
            
            // Test the exact query from header component
            $userStmt = $pdo->prepare("
                SELECT u.first_name, u.last_name, u.username, r.role_name 
                FROM users u 
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_primary = 1
                LEFT JOIN roles r ON ur.role_id = r.role_id 
                WHERE u.user_id = ?
            ");
            $userStmt->execute([$userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $userName = trim($userData['first_name'] . ' ' . $userData['last_name']);
                $userRole = $userData['role_name'] ?? 'User';
                echo "<p class='success'>✓ User data retrieved successfully</p>";
                echo "<p>User Name: $userName</p>";
                echo "<p>User Role: $userRole</p>";
            } else {
                echo "<p class='warning'>⚠ No primary role found, trying fallback...</p>";
                
                // Test fallback query
                $userStmt = $pdo->prepare("
                    SELECT u.first_name, u.last_name, u.username, r.role_name 
                    FROM users u 
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.role_id 
                    WHERE u.user_id = ? AND r.role_name IS NOT NULL
                    LIMIT 1
                ");
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    $userName = trim($userData['first_name'] . ' ' . $userData['last_name']);
                    $userRole = $userData['role_name'] ?? 'User';
                    echo "<p class='success'>✓ Fallback user data retrieved successfully</p>";
                    echo "<p>User Name: $userName</p>";
                    echo "<p>User Role: $userRole</p>";
                } else {
                    echo "<p class='error'>✗ No user data found in database</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠ No user ID available - user not logged in</p>";
    }
    echo "</div>";
    
    // Test 4: Staff Validation
    echo "<div class='test-section'>";
    echo "<h2>4. Staff Validation Test</h2>";
    
    if ($userId) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Test staff check
            $staffCheck = $pdo->prepare("
                SELECT COUNT(*) 
                FROM staff s 
                WHERE s.user_id = ? AND s.is_active = 1
            ");
            $staffCheck->execute([$userId]);
            $isStaff = (int)$staffCheck->fetchColumn() > 0;
            
            echo "<p>Is Staff: " . ($isStaff ? '<span class="success">YES</span>' : '<span class="warning">NO</span>') . "</p>";
            
            if (!$isStaff) {
                // Test admin role check
                $roleCheck = $pdo->prepare("
                    SELECT r.role_name 
                    FROM users u 
                    JOIN user_roles ur ON u.user_id = ur.user_id 
                    JOIN roles r ON ur.role_id = r.role_id 
                    WHERE u.user_id = ? AND r.role_name IN ('Admin', 'Program Head', 'School Administrator')
                ");
                $roleCheck->execute([$userId]);
                $hasAdminRole = $roleCheck->fetchColumn();
                
                echo "<p>Has Admin Role: " . ($hasAdminRole ? '<span class="success">YES</span>' : '<span class="warning">NO</span>') . "</p>";
                echo "<p>Access Allowed: " . ($hasAdminRole ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</p>";
            } else {
                echo "<p class='success'>Access Allowed: YES (Staff member)</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Staff validation error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠ Cannot test staff validation - no user ID</p>";
    }
    echo "</div>";
    
    // Test 5: Session Data
    echo "<div class='test-section'>";
    echo "<h2>5. Session Data</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
    ?>
    
    <div class='test-section'>
        <h2>Test Links</h2>
        <p><a href="pages/regular-staff/FacultyManagement.php">Test Regular Staff Faculty Management</a></p>
        <p><a href="pages/admin/FacultyManagement.php">Test Admin Faculty Management</a></p>
        <p><a href="pages/auth/login.php">Login Page</a></p>
    </div>
</body>
</html>
