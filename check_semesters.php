<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Semesters table structure:\n";
    $stmt = $pdo->query('DESCRIBE semesters');
    while($row = $stmt->fetch()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
