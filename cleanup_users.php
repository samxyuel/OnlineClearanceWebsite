<?php
require_once 'includes/config/database.php';

echo "<h2>User Cleanup Script</h2>";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // First, let's see what users we have
    echo "<h3>Current Users in Database</h3>";
    $stmt = $pdo->query("SELECT user_id, username, first_name, last_name, email FROM users ORDER BY user_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Name</th><th>Email</th><th>Action</th></tr>";
    
    $usersToDelete = [];
    
    foreach($users as $user) {
        $username = $user['username'];
        $isAdmin = ($username === 'admin');
        $isProperFormat = preg_match('/^LCA\d+P$|^PHC\d+P$|^PHS\d+P$|^PHF\d+P$/', $username);
        
        $action = '';
        if ($isAdmin) {
            $action = '<span style="color: green;">KEEP (Admin)</span>';
        } elseif ($isProperFormat) {
            $action = '<span style="color: green;">KEEP (Proper Format)</span>';
        } else {
            $action = '<span style="color: red;">DELETE (Invalid Format)</span>';
            $usersToDelete[] = $user['user_id'];
        }
        
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>$action</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show what will be deleted
    if (!empty($usersToDelete)) {
        echo "<h3>Users to be Deleted</h3>";
        echo "<p style='color: red;'>The following users will be deleted:</p>";
        echo "<ul>";
        foreach($usersToDelete as $userId) {
            $user = array_filter($users, function($u) use ($userId) { return $u['user_id'] == $userId; });
            $user = reset($user);
            echo "<li>User ID {$userId}: {$user['username']} ({$user['first_name']} {$user['last_name']})</li>";
        }
        echo "</ul>";
        
        echo "<h3>Deletion Process</h3>";
        echo "<p><strong>⚠️ WARNING:</strong> This will permanently delete the selected users and all their related data!</p>";
        
        if (isset($_POST['confirm_delete'])) {
            echo "<h3>Deleting Users...</h3>";
            
            foreach($usersToDelete as $userId) {
                try {
                    // Delete from user_roles first (foreign key constraint)
                    $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$userId]);
                    
                    // Delete from users table
                    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
                    
                    echo "<p style='color: green;'>✅ Deleted User ID: $userId</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Failed to delete User ID $userId: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<p style='color: green; font-weight: bold;'>✅ Cleanup completed!</p>";
            echo "<p><a href='pages/auth/login.php'>Go to Login</a></p>";
            
        } else {
            echo "<form method='POST'>";
            echo "<input type='hidden' name='confirm_delete' value='1'>";
            echo "<button type='submit' style='background: red; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>CONFIRM DELETE</button>";
            echo "</form>";
        }
    } else {
        echo "<p style='color: green;'>✅ No users need to be deleted. All users have proper format or are admin.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
