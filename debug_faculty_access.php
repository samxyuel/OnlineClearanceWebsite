<?php
// Debug script to test the exact same logic as FacultyManagement.php
session_start();
require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "=== FACULTY MANAGEMENT ACCESS DEBUG ===\n";

$auth = new Auth();
echo "Is logged in: " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "\n";

if (!$auth->isLoggedIn()) {
    echo "❌ Not logged in - would redirect to login\n";
    exit;
}

$userId = (int)$auth->getUserId();
echo "User ID: $userId\n";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Test the EXACT same query from FacultyManagement.php
    echo "\n=== TESTING EXACT FACULTY MANAGEMENT LOGIC ===\n";
    $staffCheck = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ? AND is_active = 1");
    $staffCheck->execute([$userId]);
    $isStaff = (int)$staffCheck->fetchColumn() > 0;
    
    echo "Staff check query result: $isStaff\n";
    echo "Is staff (boolean): " . ($isStaff ? 'YES' : 'NO') . "\n";
    
    if (!$isStaff) {
        echo "❌ WOULD RETURN 403 - Access denied. Regular staff access required.\n";
        echo "This is why you're getting the 403 error!\n";
        
        // Let's debug why this is failing
        echo "\n=== DEBUGGING WHY STAFF CHECK FAILS ===\n";
        
        // Check if user exists in staff table at all
        $anyStaffCheck = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ?");
        $anyStaffCheck->execute([$userId]);
        $anyStaff = (int)$anyStaffCheck->fetchColumn();
        echo "User exists in staff table (any status): $anyStaff\n";
        
        // Check active status
        $activeCheck = $pdo->prepare("SELECT is_active FROM staff WHERE user_id = ?");
        $activeCheck->execute([$userId]);
        $activeStatus = $activeCheck->fetchColumn();
        echo "Active status: " . ($activeStatus === null ? 'NULL' : $activeStatus) . "\n";
        
        // Check all staff records for this user
        $allStaffCheck = $pdo->prepare("SELECT * FROM staff WHERE user_id = ?");
        $allStaffCheck->execute([$userId]);
        $allStaff = $allStaffCheck->fetchAll(PDO::FETCH_ASSOC);
        echo "All staff records for this user:\n";
        foreach($allStaff as $staff) {
            echo "  - Staff ID: {$staff['staff_id']}, Employee: {$staff['employee_number']}, Active: {$staff['is_active']}\n";
        }
        
    } else {
        echo "✅ Should allow access - is a staff member\n";
        
        // Test permission flags
        $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE is_active=1")->fetchColumn() > 0;
        echo "Has active period: " . ($hasActivePeriod ? 'YES' : 'NO') . "\n";
        
        $facultySignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations d ON sa.designation_id=d.designation_id WHERE sa.user_id=? AND sa.clearance_type='faculty' AND sa.is_active=1");
        $facultySignatoryCheck->execute([$userId]);
        $hasFacultySignatoryAccess = (int)$facultySignatoryCheck->fetchColumn() > 0;
        echo "Has faculty signatory access: " . ($hasFacultySignatoryAccess ? 'YES' : 'NO') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
