<?php
/**
 * Safe Database Backup Script for Online Clearance Website
 * This script creates a complete backup of the database before fixing MySQL issues
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'online_clearance_db';

// Create backup directory if it doesn't exist
$backup_dir = 'database_backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Generate timestamp for backup filename
$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . '/online_clearance_db_backup_' . $timestamp . '.sql';

echo "Starting database backup...\n";
echo "Database: $database\n";
echo "Backup file: $backup_file\n\n";

try {
    // Try to connect to MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to MySQL successfully\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    if ($stmt->rowCount() == 0) {
        echo "⚠ Warning: Database '$database' does not exist\n";
        echo "This might be why MySQL is having issues.\n";
    } else {
        echo "✓ Database '$database' exists\n";
    }
    
    // Create mysqldump command
    $mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    
    if (!file_exists($mysqldump_path)) {
        echo "⚠ mysqldump not found at expected path: $mysqldump_path\n";
        echo "Trying alternative paths...\n";
        
        // Try alternative paths
        $alternative_paths = [
            'C:\\xampp\\mysql\\bin\\mysqldump',
            'mysqldump',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysqldump.exe'
        ];
        
        $mysqldump_path = null;
        foreach ($alternative_paths as $path) {
            if (file_exists($path) || shell_exec("where $path 2>nul")) {
                $mysqldump_path = $path;
                break;
            }
        }
        
        if (!$mysqldump_path) {
            throw new Exception("mysqldump not found. Please ensure MySQL/MariaDB is properly installed.");
        }
    }
    
    echo "✓ Found mysqldump at: $mysqldump_path\n";
    
    // Build mysqldump command
    $command = "\"$mysqldump_path\" --host=$host --user=$username";
    if (!empty($password)) {
        $command .= " --password=$password";
    }
    $command .= " --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 $database > \"$backup_file\"";
    
    echo "Executing backup command...\n";
    echo "Command: $command\n\n";
    
    // Execute backup
    $output = [];
    $return_code = 0;
    exec($command . ' 2>&1', $output, $return_code);
    
    if ($return_code === 0) {
        if (file_exists($backup_file) && filesize($backup_file) > 0) {
            $file_size = round(filesize($backup_file) / 1024 / 1024, 2);
            echo "✓ Database backup completed successfully!\n";
            echo "✓ Backup file: $backup_file\n";
            echo "✓ File size: {$file_size} MB\n";
            
            // Also create a compressed backup
            $compressed_file = $backup_file . '.zip';
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($compressed_file, ZipArchive::CREATE) === TRUE) {
                    $zip->addFile($backup_file, basename($backup_file));
                    $zip->close();
                    echo "✓ Compressed backup created: $compressed_file\n";
                }
            }
            
        } else {
            throw new Exception("Backup file was not created or is empty");
        }
    } else {
        throw new Exception("mysqldump failed with return code: $return_code\nOutput: " . implode("\n", $output));
    }
    
} catch (Exception $e) {
    echo "✗ Error during backup: " . $e->getMessage() . "\n";
    
    // Try alternative backup method using PHP
    echo "\nTrying alternative backup method...\n";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "⚠ No tables found in database\n";
        } else {
            echo "Found " . count($tables) . " tables\n";
            
            $backup_content = "-- Database backup created on " . date('Y-m-d H:i:s') . "\n";
            $backup_content .= "-- Database: $database\n\n";
            $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                echo "Backing up table: $table\n";
                
                // Get table structure
                $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                $backup_content .= "-- Table structure for table `$table`\n";
                $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                $backup_content .= $create_table['Create Table'] . ";\n\n";
                
                // Get table data
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $backup_content .= "-- Data for table `$table`\n";
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($pdo) {
                            return $value === null ? 'NULL' : $pdo->quote($value);
                        }, $row);
                        $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $backup_content .= "\n";
                }
            }
            
            $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            file_put_contents($backup_file, $backup_content);
            $file_size = round(filesize($backup_file) / 1024 / 1024, 2);
            
            echo "✓ Alternative backup completed!\n";
            echo "✓ Backup file: $backup_file\n";
            echo "✓ File size: {$file_size} MB\n";
        }
        
    } catch (Exception $e2) {
        echo "✗ Alternative backup also failed: " . $e2->getMessage() . "\n";
        echo "\nRecommendations:\n";
        echo "1. Try starting MySQL manually from XAMPP Control Panel\n";
        echo "2. Check if MySQL service is running in Windows Services\n";
        echo "3. Try restarting your computer\n";
        echo "4. If the issue persists, you may need to reinstall XAMPP\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Backup process completed.\n";
echo "Next steps:\n";
echo "1. If backup was successful, proceed with MySQL fixes\n";
echo "2. If backup failed, try to start MySQL manually first\n";
echo "3. Check the backup file location: $backup_dir/\n";
echo str_repeat("=", 50) . "\n";
?>
