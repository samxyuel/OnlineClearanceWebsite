<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Academic Years table structure:\n";
    $stmt = $pdo->query('DESCRIBE academic_years');
    while($row = $stmt->fetch()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\nClearance Periods table structure:\n";
    $stmt = $pdo->query('DESCRIBE clearance_periods');
    while($row = $stmt->fetch()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\nCurrent data:\n";
    $stmt = $pdo->query('SELECT * FROM academic_years LIMIT 3');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Academic years: " . count($results) . " records\n";
    foreach($results as $row) {
        echo "  " . json_encode($row) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
