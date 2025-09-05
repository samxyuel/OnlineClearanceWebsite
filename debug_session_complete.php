<?php
session_start();
require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "<h2>Complete Session & Authentication Debug</h2>";

// Check session data
echo "<h3>Session Data</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check Auth class
$auth = new Auth();
echo "<h3>Auth Class Results</h3>";
echo "<p><strong>Is logged in:</strong> " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "</p>";
echo "<p><strong>User ID:</strong> " . ($auth->getUserId() ?? 'NULL') . "</p>";
echo "<p><strong>Role Name:</strong> " . ($auth->getRoleName() ?? 'NULL') . "</p>";

$currentUser = $auth->getCurrentUser();
echo "<p><strong>Current User Data:</strong></p>";
echo "<pre>";
print_r($currentUser);
echo "</pre>";

// Check database connection
try {
    $pdo = Database::getInstance()->getConnection();
    echo "<h3>Database Connection</h3>";
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if current user exists in users table
    if ($auth->isLoggedIn()) {
        $userId = $auth->getUserId();
        echo "<h3>User Database Check</h3>";
        
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color: green;'>✅ User exists in users table</p>";
            echo "<p><strong>Username:</strong> {$user['username']}</p>";
            echo "<p><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</p>";
            echo "<p><strong>Status:</strong> {$user['status']}</p>";
        } else {
            echo "<p style='color: red;'>❌ User NOT found in users table</p>";
        }
        
        // Check if user is in staff table
        echo "<h3>Staff Table Check</h3>";
        $staffStmt = $pdo->prepare("SELECT s.*, d.designation_name FROM staff s LEFT JOIN designations d ON s.designation_id = d.designation_id WHERE s.user_id = ?");
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($staff) {
            echo "<p style='color: green;'>✅ User IS in staff table</p>";
            echo "<p><strong>Employee Number:</strong> {$staff['employee_number']}</p>";
            echo "<p><strong>Position:</strong> {$staff['designation_name']}</p>";
            echo "<p><strong>Active:</strong> " . ($staff['is_active'] ? 'YES' : 'NO') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ User is NOT in staff table</p>";
        }
        
        // Test the exact FacultyManagement.php logic
        echo "<h3>FacultyManagement.php Logic Test</h3>";
        $staffCheck = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ? AND is_active = 1");
        $staffCheck->execute([$userId]);
        $isStaff = (int)$staffCheck->fetchColumn() > 0;
        
        echo "<p><strong>Staff check result:</strong> $isStaff</p>";
        if ($isStaff > 0) {
            echo "<p style='color: green;'>✅ SHOULD HAVE ACCESS to FacultyManagement.php</p>";
        } else {
            echo "<p style='color: red;'>❌ WOULD BE DENIED ACCESS to FacultyManagement.php</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='pages/regular-staff/FacultyManagement.php'>Try Faculty Management Page</a></p>";
echo "<p><a href='pages/auth/logout.php'>Logout</a></p>";
?>
