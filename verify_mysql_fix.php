<?php
/**
 * MySQL Fix Verification Script
 */

echo "Verifying MySQL fix...\n";
echo str_repeat("=", 30) . "\n\n";

try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ MySQL connection successful\n";
    
    // Check databases
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($databases) . " databases\n";
    
    // Check if our database exists
    if (in_array("online_clearance_db", $databases)) {
        echo "✓ online_clearance_db database exists\n";
        
        // Check tables
        $pdo->exec("USE online_clearance_db");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Found " . count($tables) . " tables in online_clearance_db\n";
        
    } else {
        echo "⚠ online_clearance_db database not found\n";
    }
    
    echo "\n✓ MySQL fix verification completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ MySQL connection failed: " . $e->getMessage() . "\n";
    echo "\nThe fix may not have worked. Try the manual steps.\n";
}
?>