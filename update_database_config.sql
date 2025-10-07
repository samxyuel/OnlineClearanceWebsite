-- =====================================================================
-- Database Configuration Update Script
-- Purpose: Update database connection settings for ver2_online_clearance_db
-- =====================================================================

-- This script is for reference only - you'll need to update your PHP config file manually

-- =====================================================================
-- PHP CONFIGURATION UPDATE
-- =====================================================================

/*
Update your database configuration file (usually includes/config/database.php) 
with the following settings:

<?php
$host = 'localhost';
$dbname = 'ver2_online_clearance_db';  // Changed from 'online_clearance_db_ver2'
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

*/

-- =====================================================================
-- VERIFICATION QUERIES
-- =====================================================================

-- Test database connection
USE `ver2_online_clearance_db`;

-- Check if all tables exist
SELECT 'Tables in ver2_online_clearance_db:' as Info;
SHOW TABLES;

-- Check table structures
DESCRIBE users;
DESCRIBE students;
DESCRIBE faculty;
DESCRIBE staff;
DESCRIBE roles;
DESCRIBE sectors;

-- Check relationships
SELECT 'Foreign Key Constraints:' as Info;
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'ver2_online_clearance_db'
AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT 'Database structure verification completed!' as Message;
