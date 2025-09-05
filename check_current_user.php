<?php
session_start();
require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "<h2>Current User Information</h2>";

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo "<p style='color: red;'>Not logged in</p>";
    exit;
}

$userId = (int)$auth->getUserId();
echo "<p><strong>Current User ID:</strong> $userId</p>";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get user details
    $userStmt = $pdo->prepare("SELECT user_id, username, first_name, last_name FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p><strong>Username:</strong> {$user['username']}</p>";
        echo "<p><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</p>";
    }
    
    // Check if this user is in staff table
    $staffStmt = $pdo->prepare("SELECT s.*, d.designation_name FROM staff s LEFT JOIN designations d ON s.designation_id = d.designation_id WHERE s.user_id = ?");
    $staffStmt->execute([$userId]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        echo "<p style='color: green;'><strong>✅ This user IS in staff table</strong></p>";
        echo "<p><strong>Employee Number:</strong> {$staff['employee_number']}</p>";
        echo "<p><strong>Position:</strong> {$staff['designation_name']}</p>";
        echo "<p><strong>Active:</strong> " . ($staff['is_active'] ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ This user is NOT in staff table</strong></p>";
    }
    
    // Show all staff users for reference
    echo "<h3>All Staff Users (for reference):</h3>";
    $allStaffStmt = $pdo->query("SELECT u.user_id, u.username, u.first_name, u.last_name, s.employee_number, d.designation_name FROM users u JOIN staff s ON u.user_id = s.user_id LEFT JOIN designations d ON s.designation_id = d.designation_id ORDER BY u.user_id");
    $allStaff = $allStaffStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Name</th><th>Employee #</th><th>Position</th></tr>";
    foreach($allStaff as $staff) {
        $highlight = ($staff['user_id'] == $userId) ? "style='background-color: yellow;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$staff['user_id']}</td>";
        echo "<td>{$staff['username']}</td>";
        echo "<td>{$staff['first_name']} {$staff['last_name']}</td>";
        echo "<td>{$staff['employee_number']}</td>";
        echo "<td>{$staff['designation_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
