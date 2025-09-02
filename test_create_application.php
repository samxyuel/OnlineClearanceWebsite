<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Creating test clearance application...\n";
    
    // Get the first active period
    $stmt = $connection->prepare("SELECT period_id FROM clearance_periods WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$period) {
        echo "❌ No active clearance period found\n";
        exit;
    }
    
    // Get a test user (admin)
    $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Admin user not found\n";
        exit;
    }
    
    // Create test application
    $stmt = $connection->prepare("INSERT INTO clearance_applications (user_id, period_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user['user_id'], $period['period_id']]);
    
    $applicationId = $connection->lastInsertId();
    echo "✅ Created test application with ID: $applicationId\n";
    
    // Create signatory status records
    $stmt = $connection->prepare("
        INSERT INTO clearance_signatory_status (application_id, requirement_id, status)
        SELECT ?, requirement_id, 'pending'
        FROM clearance_requirements
        WHERE clearance_type = 'Student'
    ");
    $stmt->execute([$applicationId]);
    
    echo "✅ Created signatory status records\n";
    echo "You can now test: GET /clearance/status.php?application_id=$applicationId\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
