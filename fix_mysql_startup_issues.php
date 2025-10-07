<?php
/**
 * MySQL Startup Issues Fix Script
 * This script addresses the common XAMPP MySQL startup problems
 */

echo "MySQL Startup Issues Fix Script\n";
echo str_repeat("=", 50) . "\n\n";

// Check if we're running as administrator
function isAdmin() {
    if (function_exists('shell_exec')) {
        $output = shell_exec('net session 2>&1');
        return strpos($output, 'Access is denied') === false;
    }
    return false;
}

echo "Checking system status...\n";

// Check if MySQL is running
$mysql_running = false;
$output = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul');
if (strpos($output, 'mysqld.exe') !== false) {
    $mysql_running = true;
    echo "✓ MySQL is currently running\n";
} else {
    echo "⚠ MySQL is not running\n";
}

// Check XAMPP paths
$xampp_paths = [
    'C:\\xampp\\mysql\\data',
    'C:\\xampp\\mysql\\bin',
    'C:\\xampp\\apache\\bin'
];

echo "\nChecking XAMPP installation...\n";
foreach ($xampp_paths as $path) {
    if (file_exists($path)) {
        echo "✓ Found: $path\n";
    } else {
        echo "✗ Missing: $path\n";
    }
}

// Check for problematic files
echo "\nChecking for problematic files...\n";
$problematic_files = [
    'C:\\xampp\\mysql\\data\\aria_log.00000001',
    'C:\\xampp\\mysql\\data\\aria_log.00000002',
    'C:\\xampp\\mysql\\data\\aria_log.00000003'
];

foreach ($problematic_files as $file) {
    if (file_exists($file)) {
        echo "⚠ Found problematic file: $file\n";
    }
}

// Create fix commands
echo "\n" . str_repeat("=", 50) . "\n";
echo "FIX INSTRUCTIONS:\n";
echo str_repeat("=", 50) . "\n\n";

echo "STEP 1: Stop MySQL Service\n";
echo "1. Open XAMPP Control Panel\n";
echo "2. Click 'Stop' next to MySQL\n";
echo "3. Wait for it to fully stop\n\n";

echo "STEP 2: Delete Problematic Aria Log Files\n";
echo "Run these commands in Command Prompt as Administrator:\n\n";

foreach ($problematic_files as $file) {
    if (file_exists($file)) {
        echo "del \"$file\"\n";
    }
}

echo "\nSTEP 3: Fix phpMyAdmin Database Issues\n";
echo "Run these commands in Command Prompt as Administrator:\n\n";

echo "cd C:\\xampp\\mysql\\bin\n";
echo "mysql_upgrade.exe --force\n\n";

echo "STEP 4: Alternative Fix - Reset phpMyAdmin Database\n";
echo "If the above doesn't work, run:\n\n";
echo "mysql.exe -u root -e \"DROP DATABASE IF EXISTS phpmyadmin;\"\n";
echo "mysql.exe -u root -e \"CREATE DATABASE phpmyadmin;\"\n";
echo "mysql.exe -u root phpmyadmin < C:\\xampp\\phpmyadmin\\sql\\create_tables.sql\n\n";

echo "STEP 5: Start MySQL\n";
echo "1. Go back to XAMPP Control Panel\n";
echo "2. Click 'Start' next to MySQL\n";
echo "3. Check the logs for any remaining errors\n\n";

// Create automated fix script
$fix_script = 'fix_mysql_automated.bat';
$bat_content = '@echo off
echo Fixing MySQL startup issues...
echo.

echo Stopping MySQL service...
net stop mysql 2>nul
taskkill /F /IM mysqld.exe 2>nul

echo.
echo Deleting problematic Aria log files...
del "C:\xampp\mysql\data\aria_log.*" 2>nul

echo.
echo Running MySQL upgrade...
cd /d "C:\xampp\mysql\bin"
mysql_upgrade.exe --force

echo.
echo Starting MySQL service...
net start mysql 2>nul

echo.
echo Fix completed. Check XAMPP Control Panel for MySQL status.
pause
';

file_put_contents($fix_script, $bat_content);
echo "✓ Created automated fix script: $fix_script\n";
echo "  Run this as Administrator to automatically fix the issues\n\n";

// Create manual verification script
$verify_script = 'verify_mysql_fix.php';
$verify_content = '<?php
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
?>';

file_put_contents($verify_script, $verify_content);
echo "✓ Created verification script: $verify_script\n";
echo "  Run this after applying the fix to verify it worked\n\n";

echo str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo str_repeat("=", 50) . "\n";
echo "1. First, run: backup_database_safe.php (to backup your data)\n";
echo "2. Then run: $fix_script (as Administrator)\n";
echo "3. Finally, run: $verify_script (to verify the fix)\n";
echo "\nIf the automated fix doesn't work, follow the manual steps above.\n";
echo str_repeat("=", 50) . "\n";
?>
