<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Fixing semester names...\n";
    
    // Update the empty semester name
    $stmt = $connection->prepare("UPDATE semesters SET semester_name = '1st Semester' WHERE semester_name = '' AND is_active = 1");
    $stmt->execute();
    
    echo "✅ Fixed semester names\n";
    
    // Show current semesters
    $stmt = $connection->query('SELECT * FROM semesters');
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent semesters:\n";
    foreach ($semesters as $semester) {
        echo "- {$semester['semester_name']} (Active: " . ($semester['is_active'] ? 'Yes' : 'No') . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
