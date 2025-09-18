<?php
// Comprehensive test of Faculty Management integration
echo "🧪 Testing Faculty Management Integration\n";
echo "==========================================\n\n";

// Test 1: Database Setup
echo "1. 📊 Database Setup Test:\n";
try {
    require_once 'includes/config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check active clearance period
    $stmt = $pdo->query("SELECT * FROM clearance_periods WHERE status = 'active' LIMIT 1");
    $activePeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activePeriod) {
        echo "   ✅ Active clearance period found:\n";
        echo "     - Period ID: " . $activePeriod['period_id'] . "\n";
        echo "     - Start Date: " . $activePeriod['start_date'] . "\n";
        echo "     - End Date: " . $activePeriod['end_date'] . "\n";
    } else {
        echo "   ❌ No active clearance period found\n";
    }
    
    // Check faculty members
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff WHERE designation_id IN (SELECT designation_id FROM designations WHERE designation_name = 'Faculty')");
    $facultyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Faculty members in database: $facultyCount\n";
    
    // Check signatory assignments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sector_signatory_assignments WHERE clearance_type = 'Faculty'");
    $signatoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Faculty signatory assignments: $signatoryCount\n";
    
    // Check clearance applications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clearance_applications");
    $applicationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Clearance applications: $applicationCount\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. 🔧 API Functionality Test:\n";

// Test term status API
echo "   Testing Term Status API...\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/term_status.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "     ✅ Term Status API properly requires authentication (401)\n";
} else {
    echo "     ❌ Term Status API unexpected response: $httpCode\n";
}

// Test button status API
echo "   Testing Button Status API...\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/button_status.php?faculty_id=1&clearance_type=Faculty";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "     ✅ Button Status API properly requires authentication (401)\n";
} else {
    echo "     ❌ Button Status API unexpected response: $httpCode\n";
}

echo "\n3. 📄 Faculty Management Pages Test:\n";

$facultyPages = [
    'Admin' => 'pages/admin/FacultyManagement.php',
    'Program Head' => 'pages/program-head/FacultyManagement.php',
    'Regular Staff' => 'pages/regular-staff/FacultyManagement.php',
    'School Administrator' => 'pages/school-administrator/FacultyManagement.php'
];

foreach ($facultyPages as $role => $page) {
    echo "   Testing $role Faculty Management page...\n";
    
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Check for required components
        $checks = [
            'Button Manager Script' => strpos($content, 'clearance-button-manager.js') !== false,
            'Data Faculty ID' => strpos($content, 'data-faculty-id') !== false,
            'Approve Buttons' => strpos($content, 'approve-btn') !== false,
            'Reject Buttons' => strpos($content, 'reject-btn') !== false,
            'Button Manager Integration' => strpos($content, 'updateAllButtons') !== false,
            'Action Functions' => strpos($content, 'approveFacultyClearance') !== false
        ];
        
        $allPassed = true;
        foreach ($checks as $check => $passed) {
            if ($passed) {
                echo "     ✅ $check\n";
            } else {
                echo "     ❌ $check\n";
                $allPassed = false;
            }
        }
        
        if ($allPassed) {
            echo "     🎉 $role page fully configured!\n";
        }
        
    } else {
        echo "     ❌ Page file missing\n";
    }
}

echo "\n4. 🎯 Button Logic Test:\n";

// Simulate button logic conditions
echo "   Simulating button enable/disable logic...\n";

// Condition 1: Active clearance term
if ($activePeriod) {
    echo "     ✅ Condition 1: Active clearance term exists\n";
} else {
    echo "     ❌ Condition 1: No active clearance term\n";
}

// Condition 2: Signatory assignments exist
if ($signatoryCount > 0) {
    echo "     ✅ Condition 2: Signatory assignments exist ($signatoryCount)\n";
} else {
    echo "     ❌ Condition 2: No signatory assignments\n";
}

// Condition 3: Faculty members exist
if ($facultyCount > 0) {
    echo "     ✅ Condition 3: Faculty members exist ($facultyCount)\n";
} else {
    echo "     ❌ Condition 3: No faculty members\n";
}

// Condition 4: Clearance applications exist (optional)
if ($applicationCount > 0) {
    echo "     ✅ Condition 4: Clearance applications exist ($applicationCount)\n";
} else {
    echo "     ⚠️  Condition 4: No clearance applications (buttons will be disabled until faculty apply)\n";
}

echo "\n5. 🚀 Integration Summary:\n";

$integrationScore = 0;
$totalChecks = 4;

if ($activePeriod) $integrationScore++;
if ($signatoryCount > 0) $integrationScore++;
if ($facultyCount > 0) $integrationScore++;
if ($applicationCount > 0) $integrationScore++;

$percentage = ($integrationScore / $totalChecks) * 100;

echo "   Integration Score: $integrationScore/$totalChecks ($percentage%)\n";

if ($percentage >= 75) {
    echo "   🎉 Faculty Management integration is READY FOR TESTING!\n";
    echo "   \n";
    echo "   📋 Next Steps:\n";
    echo "   1. Log in as different user roles (Admin, Program Head, Regular Staff, School Administrator)\n";
    echo "   2. Navigate to Faculty Management pages\n";
    echo "   3. Verify that Approve/Reject buttons are properly enabled/disabled\n";
    echo "   4. Test button functionality with actual faculty clearance applications\n";
} else {
    echo "   ⚠️  Faculty Management integration needs more setup:\n";
    if (!$activePeriod) echo "   - Create an active clearance period\n";
    if ($signatoryCount == 0) echo "   - Assign signatories to Faculty clearance\n";
    if ($facultyCount == 0) echo "   - Add faculty members to the system\n";
    if ($applicationCount == 0) echo "   - Have faculty members apply for clearance\n";
}

echo "\n🎯 Testing Complete!\n";
?>
