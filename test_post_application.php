<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Testing POST clearance application creation...\n\n";
    
    // Get a test user (student1)
    $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = 'student1'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Student1 user not found\n";
        exit;
    }
    
    // Get an active clearance period
    $stmt = $connection->prepare("SELECT period_id FROM clearance_periods WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$period) {
        echo "❌ No active clearance period found\n";
        exit;
    }
    
    echo "✅ Found user: student1 (ID: {$user['user_id']})\n";
    echo "✅ Found period: {$period['period_id']}\n";
    
    // Check if application already exists
    $stmt = $connection->prepare("SELECT application_id FROM clearance_applications WHERE user_id = ? AND period_id = ?");
    $stmt->execute([$user['user_id'], $period['period_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "⚠️  Application already exists for this user/period (ID: {$existing['application_id']})\n";
        echo "   This is expected behavior - preventing duplicates\n";
    } else {
        echo "✅ No existing application found - ready to create\n";
    }
    
    echo "\n📋 Test Data Summary:\n";
    echo "- User: student1 (ID: {$user['user_id']})\n";
    echo "- Period: {$period['period_id']}\n";
    echo "- Status: pending\n";
    
    echo "\n🔗 To test via cURL, use this command:\n";
    echo "curl -X POST http://localhost/OnlineClearanceWebsite/api/clearance/applications.php \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'Cookie: PHPSESSID=YOUR_SESSION_ID' \\\n";
    echo "  -d '{\"period_id\": {$period['period_id']}}'\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
