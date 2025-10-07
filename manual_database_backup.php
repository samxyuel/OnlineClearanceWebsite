<?php
/**
 * Manual Database Backup - Works when MySQL is down
 * This script attempts to backup database files directly from the file system
 */

echo "Manual Database Backup (File System Method)\n";
echo str_repeat("=", 50) . "\n\n";

$mysql_data_dir = 'C:\\xampp\\mysql\\data';
$backup_dir = 'database_backups_manual';
$timestamp = date('Y-m-d_H-i-s');

// Create backup directory
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

echo "MySQL Data Directory: $mysql_data_dir\n";
echo "Backup Directory: $backup_dir\n";
echo "Timestamp: $timestamp\n\n";

if (!file_exists($mysql_data_dir)) {
    echo "✗ MySQL data directory not found: $mysql_data_dir\n";
    exit(1);
}

// Check for database files
$database_name = 'online_clearance_db';
$db_dir = $mysql_data_dir . '\\' . $database_name;

echo "Looking for database: $database_name\n";

if (file_exists($db_dir)) {
    echo "✓ Found database directory: $db_dir\n";
    
    // Create database backup directory
    $db_backup_dir = $backup_dir . '\\' . $database_name . '_' . $timestamp;
    if (!file_exists($db_backup_dir)) {
        mkdir($db_backup_dir, 0755, true);
    }
    
    // Copy all database files
    $files = scandir($db_dir);
    $copied_files = 0;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $source = $db_dir . '\\' . $file;
            $dest = $db_backup_dir . '\\' . $file;
            
            if (copy($source, $dest)) {
                $copied_files++;
                echo "✓ Copied: $file\n";
            } else {
                echo "✗ Failed to copy: $file\n";
            }
        }
    }
    
    echo "\n✓ Database backup completed!\n";
    echo "✓ Copied $copied_files files\n";
    echo "✓ Backup location: $db_backup_dir\n";
    
    // Create a restore script
    $restore_script = $backup_dir . '\\restore_database.bat';
    $restore_content = '@echo off
echo Restoring database from manual backup...
echo.

echo Stopping MySQL...
net stop mysql 2>nul
taskkill /F /IM mysqld.exe 2>nul

echo.
echo Removing existing database directory...
rmdir /S /Q "C:\xampp\mysql\data\\' . $database_name . '" 2>nul

echo.
echo Restoring database files...
xcopy "' . $db_backup_dir . '" "C:\xampp\mysql\data\\' . $database_name . '" /E /I /Y

echo.
echo Starting MySQL...
net start mysql 2>nul

echo.
echo Database restore completed!
pause
';
    
    file_put_contents($restore_script, $restore_content);
    echo "✓ Created restore script: $restore_script\n";
    
} else {
    echo "⚠ Database directory not found: $db_dir\n";
    echo "This might mean:\n";
    echo "1. The database doesn't exist yet\n";
    echo "2. The database name is different\n";
    echo "3. MySQL data directory structure is different\n\n";
    
    // List available databases
    echo "Available database directories:\n";
    $dirs = scandir($mysql_data_dir);
    foreach ($dirs as $dir) {
        if ($dir != '.' && $dir != '..' && is_dir($mysql_data_dir . '\\' . $dir)) {
            echo "- $dir\n";
        }
    }
}

// Also backup important MySQL system files
echo "\nBacking up MySQL system files...\n";
$system_files = [
    'my.ini',
    'my.cnf',
    'ibdata1',
    'ib_logfile0',
    'ib_logfile1'
];

$system_backup_dir = $backup_dir . '\\mysql_system_' . $timestamp;
if (!file_exists($system_backup_dir)) {
    mkdir($system_backup_dir, 0755, true);
}

foreach ($system_files as $file) {
    $source = $mysql_data_dir . '\\' . $file;
    if (file_exists($source)) {
        $dest = $system_backup_dir . '\\' . $file;
        if (copy($source, $dest)) {
            echo "✓ Backed up system file: $file\n";
        } else {
            echo "✗ Failed to backup: $file\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Manual backup completed!\n";
echo "Backup location: $backup_dir\n";
echo "\nNext steps:\n";
echo "1. Fix MySQL startup issues using fix_mysql_automated.bat\n";
echo "2. If needed, restore database using the restore script\n";
echo "3. Verify everything works with verify_mysql_fix.php\n";
echo str_repeat("=", 50) . "\n";
?>
