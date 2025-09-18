<?php
// Test script for button status API
require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "🧪 Testing Button Status API\n";
echo "============================\n\n";

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        echo "❌ Not logged in. Please log in first.\n";
        exit;
    }
    
    $userId = $auth->getUserId();
    echo "✅ Logged in as user ID: $userId\n\n";
    
    // Test 1: Check term status
    echo "📋 Test 1: Checking term status...\n";
    $url = "http://localhost/OnlineClearanceWebsite/api/clearance/term_status.php";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "✅ Term status API working\n";
        echo "   - Has active term: " . ($data['has_active_term'] ? 'Yes' : 'No') . "\n";
        if ($data['has_active_term']) {
            echo "   - Active period: " . $data['active_period']['period_name'] . "\n";
        }
    } else {
        echo "❌ Term status API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
    
    // Test 2: Check user signatory status
    echo "📋 Test 2: Checking user signatory status...\n";
    $url = "http://localhost/OnlineClearanceWebsite/api/signatories/check_user_status.php?clearance_type=Faculty";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "✅ User signatory status API working\n";
        echo "   - Is signatory: " . ($data['is_signatory'] ? 'Yes' : 'No') . "\n";
        if ($data['is_signatory']) {
            echo "   - Signatory assignments: " . count($data['assignments']) . "\n";
        }
    } else {
        echo "❌ User signatory status API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
    
    // Test 3: Check button status for a faculty member
    echo "📋 Test 3: Checking button status for faculty member...\n";
    
    // First, get a faculty member ID
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE designation_id IN (SELECT designation_id FROM designations WHERE designation_name = 'Faculty') LIMIT 1");
    $stmt->execute();
    $facultyId = $stmt->fetchColumn();
    
    if ($facultyId) {
        echo "   - Testing with faculty ID: $facultyId\n";
        $url = "http://localhost/OnlineClearanceWebsite/api/clearance/button_status.php?faculty_id=$facultyId&clearance_type=Faculty";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✅ Button status API working\n";
            echo "   - Buttons enabled: " . ($data['button_status']['buttons_enabled'] ? 'Yes' : 'No') . "\n";
            echo "   - Conditions met:\n";
            foreach ($data['button_status']['conditions'] as $condition => $met) {
                echo "     - $condition: " . ($met ? 'Yes' : 'No') . "\n";
            }
            if (!empty($data['button_status']['disabled_reasons'])) {
                echo "   - Disabled reasons:\n";
                foreach ($data['button_status']['disabled_reasons'] as $reason) {
                    echo "     - $reason\n";
                }
            }
        } else {
            echo "❌ Button status API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ No faculty members found in database\n";
    }
    echo "\n";
    
    // Test 4: Check application status
    echo "📋 Test 4: Checking application status...\n";
    if ($facultyId) {
        $url = "http://localhost/OnlineClearanceWebsite/api/clearance/application_status.php?faculty_id=$facultyId";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✅ Application status API working\n";
            echo "   - Has application: " . ($data['has_application'] ? 'Yes' : 'No') . "\n";
            if ($data['has_application']) {
                echo "   - Application status: " . $data['application']['status'] . "\n";
            }
        } else {
            echo "❌ Application status API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    }
    echo "\n";
    
    echo "🎉 API Testing Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
