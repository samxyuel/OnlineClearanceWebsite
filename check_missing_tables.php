<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Checking which tables exist...\n";
    
    $requiredTables = [
        'academic_years',
        'semesters', 
        'clearance_periods',
        'clearance_requirements',
        'clearance_applications',
        'clearance_signatory_status'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $connection->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count rows\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
