<?php
// Test database connection
echo "<h2>Database Connection Test</h2>";

try {
    // Test basic PDO connection
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
    echo "✅ Basic PDO connection successful<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'online_clearance_db'");
    $databaseExists = $stmt->fetch();
    
    if ($databaseExists) {
        echo "✅ Database 'online_clearance_db' exists<br>";
        
        // Try to connect to the specific database
        try {
            $db = new PDO("mysql:host=localhost;dbname=online_clearance_db;charset=utf8mb4", "root", "");
            echo "✅ Connection to 'online_clearance_db' successful<br>";
            
            // Check if tables exist
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "⚠️ Database exists but has no tables<br>";
                echo "You need to import the database schema first!<br>";
            } else {
                echo "✅ Database has " . count($tables) . " tables:<br>";
                foreach ($tables as $table) {
                    echo "&nbsp;&nbsp;• $table<br>";
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Cannot connect to 'online_clearance_db': " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Database 'online_clearance_db' does not exist<br>";
        echo "You need to create the database first!<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Basic PDO connection failed: " . $e->getMessage() . "<br>";
    echo "Check if XAMPP MySQL service is running<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "1. Make sure XAMPP MySQL service is running<br>";
echo "2. Create database: CREATE DATABASE online_clearance_db;<br>";
echo "3. Import the database schema from database_schema_refactored.sql<br>";
echo "4. Test the login again<br>";
?>

