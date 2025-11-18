<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Database Relationships</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Relationships Restoration Tool</h1>
        
        <?php
        // Include database configuration
        require_once 'includes/config/database.php';
        
        // Get database connection details from config
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        
        // Initialize variables
        $action = $_GET['action'] ?? 'check';
        $errors = [];
        $warnings = [];
        $success = [];
        
        try {
            // Create PDO connection
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
                $username, 
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            if ($action === 'check') {
                // Check current state
                echo '<div class="info-box">';
                echo '<h3>📊 Current Database Status</h3>';
                echo '<p>Checking your database for missing relationships...</p>';
                echo '</div>';
                
                // Check for existing foreign keys
                $stmt = $pdo->query("
                    SELECT 
                        TABLE_NAME,
                        CONSTRAINT_NAME,
                        REFERENCED_TABLE_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '$dbname' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    ORDER BY TABLE_NAME
                ");
                $existing_fks = $stmt->fetchAll();
                
                // Get all tables
                $stmt = $pdo->query("
                    SELECT TABLE_NAME, TABLE_ROWS 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = '$dbname' 
                    ORDER BY TABLE_NAME
                ");
                $tables = $stmt->fetchAll();
                
                echo '<h2>📋 Database Tables</h2>';
                echo '<table>';
                echo '<tr><th>Table Name</th><th>Approximate Rows</th></tr>';
                foreach ($tables as $table) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($table['TABLE_NAME']) . '</td>';
                    echo '<td>' . number_format($table['TABLE_ROWS']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<h2>🔗 Current Foreign Key Constraints</h2>';
                if (count($existing_fks) > 0) {
                    echo '<p>Found <strong>' . count($existing_fks) . '</strong> existing foreign key constraints:</p>';
                    echo '<table>';
                    echo '<tr><th>Table</th><th>Constraint Name</th><th>References</th></tr>';
                    foreach ($existing_fks as $fk) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($fk['TABLE_NAME']) . '</td>';
                        echo '<td><code>' . htmlspecialchars($fk['CONSTRAINT_NAME']) . '</code></td>';
                        echo '<td>' . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    echo '<div class="warning-box">';
                    echo '<strong>⚠️ Warning:</strong> Some foreign keys already exist. ';
                    echo 'Running the restoration will attempt to drop and recreate them.';
                    echo '</div>';
                } else {
                    echo '<div class="error-box">';
                    echo '<strong>❌ No foreign key constraints found!</strong><br>';
                    echo 'Your database is missing all relationship constraints.';
                    echo '</div>';
                }
                
                // Show action buttons
                echo '<div style="margin-top: 30px;">';
                echo '<h2>🚀 Next Steps</h2>';
                echo '<div class="step">';
                echo '<span class="step-number">1</span>';
                echo '<strong>Backup Your Database</strong><br>';
                echo 'Before proceeding, create a backup using: ';
                echo '<code>php backup_database_safe.php</code>';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<span class="step-number">2</span>';
                echo '<strong>Drop Existing Foreign Keys</strong><br>';
                echo 'Remove any existing foreign keys to start fresh.';
                echo '<br><button class="btn btn-danger" onclick="if(confirm(\'Are you sure? This will drop all existing foreign keys.\')) window.location.href=\'?action=drop\'">Drop Foreign Keys</button>';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<span class="step-number">3</span>';
                echo '<strong>Restore All Relationships</strong><br>';
                echo 'Add all primary keys and foreign keys back to the database.';
                echo '<br><button class="btn btn-success" onclick="if(confirm(\'Are you sure? This will restore all database relationships.\')) window.location.href=\'?action=restore\'">Restore Relationships</button>';
                echo '</div>';
                echo '</div>';
                
            } elseif ($action === 'drop') {
                // Drop existing foreign keys
                echo '<div class="warning-box">';
                echo '<h3>🗑️ Dropping Existing Foreign Keys</h3>';
                echo '</div>';
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Get all foreign keys
                $stmt = $pdo->query("
                    SELECT 
                        TABLE_NAME,
                        CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '$dbname' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $fks = $stmt->fetchAll();
                
                $dropped = 0;
                foreach ($fks as $fk) {
                    try {
                        $sql = "ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
                        $pdo->exec($sql);
                        $dropped++;
                    } catch (Exception $e) {
                        $warnings[] = "Could not drop {$fk['CONSTRAINT_NAME']}: " . $e->getMessage();
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo '<div class="success-box">';
                echo "<strong>✅ Dropped $dropped foreign key constraints</strong>";
                echo '</div>';
                
                if (count($warnings) > 0) {
                    echo '<div class="warning-box">';
                    echo '<strong>Warnings:</strong><ul>';
                    foreach ($warnings as $warning) {
                        echo '<li>' . htmlspecialchars($warning) . '</li>';
                    }
                    echo '</ul></div>';
                }
                
                echo '<p><a href="?action=check"><button class="btn">← Back to Status</button></a></p>';
                echo '<p><button class="btn btn-success" onclick="if(confirm(\'Proceed with restoration?\')) window.location.href=\'?action=restore\'">Continue to Restore →</button></p>';
                
            } elseif ($action === 'restore') {
                // Restore all foreign keys
                echo '<div class="info-box">';
                echo '<h3>🔄 Restoring Database Relationships</h3>';
                echo '<p>This may take a few moments...</p>';
                echo '</div>';
                
                // Read and execute the SQL file
                $sql_file = 'restore_foreign_keys.sql';
                
                if (!file_exists($sql_file)) {
                    throw new Exception("SQL file not found: $sql_file");
                }
                
                $sql_content = file_get_contents($sql_file);
                
                // Split into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql_content)),
                    function($stmt) {
                        return !empty($stmt) && 
                               !preg_match('/^--/', $stmt) && 
                               !preg_match('/^\/\*/', $stmt);
                    }
                );
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                $executed = 0;
                $failed = 0;
                
                foreach ($statements as $statement) {
                    try {
                        // Skip comments and empty statements
                        if (preg_match('/^\s*(--|\/\*|SELECT)/i', $statement)) {
                            continue;
                        }
                        
                        $pdo->exec($statement);
                        $executed++;
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = "Statement failed: " . substr($statement, 0, 100) . "... - Error: " . $e->getMessage();
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Verify restoration
                $stmt = $pdo->query("
                    SELECT COUNT(*) as fk_count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '$dbname' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $result = $stmt->fetch();
                $fk_count = $result['fk_count'];
                
                if ($fk_count > 0) {
                    echo '<div class="success-box">';
                    echo '<h3>✅ Restoration Completed Successfully!</h3>';
                    echo "<p><strong>$executed</strong> statements executed</p>";
                    echo "<p><strong>$fk_count</strong> foreign key constraints restored</p>";
                    if ($failed > 0) {
                        echo "<p><strong>$failed</strong> statements failed (see warnings below)</p>";
                    }
                    echo '</div>';
                } else {
                    echo '<div class="error-box">';
                    echo '<h3>❌ Restoration Failed</h3>';
                    echo '<p>No foreign keys were created. Please check the errors below.</p>';
                    echo '</div>';
                }
                
                if (count($errors) > 0) {
                    echo '<div class="warning-box">';
                    echo '<strong>⚠️ Errors encountered:</strong><ul>';
                    foreach (array_slice($errors, 0, 20) as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    if (count($errors) > 20) {
                        echo '<li><em>... and ' . (count($errors) - 20) . ' more errors</em></li>';
                    }
                    echo '</ul></div>';
                }
                
                // Show final statistics
                $stmt = $pdo->query("
                    SELECT 
                        TABLE_NAME,
                        COUNT(*) as FK_COUNT
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '$dbname' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    GROUP BY TABLE_NAME
                    ORDER BY TABLE_NAME
                ");
                $fk_by_table = $stmt->fetchAll();
                
                echo '<h2>📊 Foreign Keys by Table</h2>';
                echo '<table>';
                echo '<tr><th>Table Name</th><th>Foreign Keys</th></tr>';
                foreach ($fk_by_table as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['TABLE_NAME']) . '</td>';
                    echo '<td><strong>' . $row['FK_COUNT'] . '</strong></td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<p><a href="?action=check"><button class="btn">View Complete Status</button></a></p>';
                echo '<div class="success-box">';
                echo '<strong>🎉 All done!</strong> Your database relationships have been restored.';
                echo '</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error-box">';
            echo '<h3>❌ Database Connection Error</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="error-box">';
            echo '<h3>❌ Error</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee;">
            <h3>📚 Additional Information</h3>
            <ul>
                <li><strong>Database:</strong> <?php echo htmlspecialchars($dbname); ?></li>
                <li><strong>Host:</strong> <?php echo htmlspecialchars($host); ?></li>
                <li><strong>Script Version:</strong> 1.0</li>
            </ul>
            <p><em>This tool restores all primary keys and foreign keys that were lost during database import from alwaysdata.</em></p>
        </div>
    </div>
</body>
</html>











