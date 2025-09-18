<?php
// Test the button manager functionality
echo "Testing Clearance Button Manager...\n\n";

// Test 1: Check if the button manager file exists and is valid
echo "1. Checking button manager file:\n";
$buttonManagerFile = 'assets/js/clearance-button-manager.js';
if (file_exists($buttonManagerFile)) {
    echo "   ✅ Button manager file exists (" . filesize($buttonManagerFile) . " bytes)\n";
    
    // Check for key functions
    $content = file_get_contents($buttonManagerFile);
    $functions = [
        'checkButtonStatus',
        'updateButtonsForUser',
        'updateAllButtons',
        'getTooltipMessage'
    ];
    
    foreach ($functions as $func) {
        if (strpos($content, $func) !== false) {
            echo "   ✅ Function '$func' found\n";
        } else {
            echo "   ❌ Function '$func' missing\n";
        }
    }
} else {
    echo "   ❌ Button manager file missing\n";
}

echo "\n2. Testing Faculty Management pages:\n";
$facultyPages = [
    'pages/admin/FacultyManagement.php',
    'pages/program-head/FacultyManagement.php',
    'pages/regular-staff/FacultyManagement.php',
    'pages/school-administrator/FacultyManagement.php'
];

foreach ($facultyPages as $page) {
    if (file_exists($page)) {
        echo "   ✅ $page exists\n";
        
        // Check if it includes the button manager
        $content = file_get_contents($page);
        if (strpos($content, 'clearance-button-manager.js') !== false) {
            echo "     ✅ Includes button manager script\n";
        } else {
            echo "     ❌ Missing button manager script\n";
        }
        
        // Check for data-faculty-id attributes
        if (strpos($content, 'data-faculty-id') !== false) {
            echo "     ✅ Has data-faculty-id attributes\n";
        } else {
            echo "     ❌ Missing data-faculty-id attributes\n";
        }
        
        // Check for approve/reject buttons
        if (strpos($content, 'approve-btn') !== false && strpos($content, 'reject-btn') !== false) {
            echo "     ✅ Has approve/reject buttons\n";
        } else {
            echo "     ❌ Missing approve/reject buttons\n";
        }
        
        // Check for button manager integration
        if (strpos($content, 'updateAllButtons') !== false) {
            echo "     ✅ Has button manager integration\n";
        } else {
            echo "     ❌ Missing button manager integration\n";
        }
        
    } else {
        echo "   ❌ $page missing\n";
    }
}

echo "\n3. Testing database data for button logic:\n";
try {
    require_once 'includes/config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check for faculty members
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff WHERE designation_id IN (SELECT designation_id FROM designations WHERE designation_name = 'Faculty')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Faculty members: " . $result['count'] . "\n";
    
    // Check for active clearance periods
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clearance_periods WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Active clearance periods: " . $result['count'] . "\n";
    
    // Check for signatory assignments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sector_signatory_assignments WHERE clearance_type = 'Faculty'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Faculty signatory assignments: " . $result['count'] . "\n";
    
    // Check for clearance applications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clearance_applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Clearance applications: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Button Manager Testing Complete!\n";
?>
