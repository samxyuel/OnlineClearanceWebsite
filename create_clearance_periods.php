<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Creating clearance periods...\n";
    
    // Get the active academic year
    $stmt = $connection->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$year) {
        echo "❌ No active academic year found\n";
        exit;
    }
    
    // Get semesters
    $stmt = $connection->prepare("SELECT semester_id FROM semesters WHERE is_active = 1");
    $stmt->execute();
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($semesters)) {
        echo "❌ No active semesters found\n";
        exit;
    }
    
    // Create clearance periods for each semester
    foreach ($semesters as $semester) {
        $stmt = $connection->prepare("
            INSERT INTO clearance_periods (academic_year_id, semester_id, start_date, end_date, is_active)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)
        ");
        $stmt->execute([$year['academic_year_id'], $semester['semester_id']]);
        
        $periodId = $connection->lastInsertId();
        echo "✅ Created clearance period ID: $periodId\n";
    }
    
    echo "\n✅ All clearance periods created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
