<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Checking clearance_applications table structure:\n";
    $stmt = $connection->query('DESCRIBE clearance_applications');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
