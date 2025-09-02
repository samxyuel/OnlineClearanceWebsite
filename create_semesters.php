<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Creating semesters...\n";
    
    // Create semesters
    $semesters = [
        ['semester_name' => '1st Semester', 'is_active' => 1],
        ['semester_name' => '2nd Semester', 'is_active' => 0],
        ['semester_name' => 'Summer', 'is_active' => 0]
    ];
    
    foreach ($semesters as $semester) {
        $stmt = $connection->prepare("
            INSERT INTO semesters (semester_name, is_active)
            VALUES (?, ?)
        ");
        $stmt->execute([$semester['semester_name'], $semester['is_active']]);
        
        $semesterId = $connection->lastInsertId();
        echo "✅ Created semester: {$semester['semester_name']} (ID: $semesterId)\n";
    }
    
    echo "\n✅ All semesters created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
