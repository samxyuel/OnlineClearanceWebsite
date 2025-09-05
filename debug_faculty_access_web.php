<?php
// Web-accessible debug script to test FacultyManagement.php logic
session_start();
require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "<h2>Faculty Management Access Debug</h2>";

$auth = new Auth();
echo "<p><strong>Is logged in:</strong> " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "</p>";

if (!$auth->isLoggedIn()) {
    echo "<p style='color: red;'>❌ Not logged in - please log in first</p>";
    echo "<p><a href='pages/auth/login.php'>Go to Login</a></p>";
    exit;
}

$userId = (int)$auth->getUserId();
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Test the EXACT same query from FacultyManagement.php
    echo "<h3>Testing Exact Faculty Management Logic</h3>";
    $staffCheck = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ? AND is_active = 1");
    $staffCheck->execute([$userId]);
    $isStaff = (int)$staffCheck->fetchColumn() > 0;
    
    echo "<p><strong>Staff check query result:</strong> $isStaff</p>";
    echo "<p><strong>Is staff (boolean):</strong> " . ($isStaff ? 'YES' : 'NO') . "</p>";
    
    if (!$isStaff) {
        echo "<p style='color: red;'>❌ WOULD RETURN 403 - Access denied. Regular staff access required.</p>";
        echo "<p style='color: red;'>This is why you're getting the 403 error!</p>";
        
        // Let's debug why this is failing
        echo "<h3>Debugging Why Staff Check Fails</h3>";
        
        // Check if user exists in staff table at all
        $anyStaffCheck = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ?");
        $anyStaffCheck->execute([$userId]);
        $anyStaff = (int)$anyStaffCheck->fetchColumn();
        echo "<p><strong>User exists in staff table (any status):</strong> $anyStaff</p>";
        
        // Check active status
        $activeCheck = $pdo->prepare("SELECT is_active FROM staff WHERE user_id = ?");
        $activeCheck->execute([$userId]);
        $activeStatus = $activeCheck->fetchColumn();
        echo "<p><strong>Active status:</strong> " . ($activeStatus === null ? 'NULL' : $activeStatus) . "</p>";
        
        // Check all staff records for this user
        $allStaffCheck = $pdo->prepare("SELECT * FROM staff WHERE user_id = ?");
        $allStaffCheck->execute([$userId]);
        $allStaff = $allStaffCheck->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>All staff records for this user:</strong></p>";
        echo "<ul>";
        foreach($allStaff as $staff) {
            echo "<li>Staff ID: {$staff['staff_id']}, Employee: {$staff['employee_number']}, Active: {$staff['is_active']}</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: green;'>✅ Should allow access - is a staff member</p>";
        
        // Test permission flags
        $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE is_active=1")->fetchColumn() > 0;
        echo "<p><strong>Has active period:</strong> " . ($hasActivePeriod ? 'YES' : 'NO') . "</p>";
        
        $facultySignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations d ON sa.designation_id=d.designation_id WHERE sa.user_id=? AND sa.clearance_type='faculty' AND sa.is_active=1");
        $facultySignatoryCheck->execute([$userId]);
        $hasFacultySignatoryAccess = (int)$facultySignatoryCheck->fetchColumn() > 0;
        echo "<p><strong>Has faculty signatory access:</strong> " . ($hasFacultySignatoryAccess ? 'YES' : 'NO') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='pages/regular-staff/FacultyManagement.php'>Try Faculty Management Page</a></p>";
?>
