<?php
require_once 'includes/config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$stmt = $pdo->query('DESCRIBE users');
echo "Users table columns:\n";
while($row = $stmt->fetch()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
