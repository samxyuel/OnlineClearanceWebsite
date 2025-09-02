<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Users table columns:\n";
    $stmt = $connection->query('SHOW COLUMNS FROM users');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . "\n";
    }
    
    echo "\nClearance_applications table columns:\n";
    $stmt = $connection->query('SHOW COLUMNS FROM clearance_applications');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
