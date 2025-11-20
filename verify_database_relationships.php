<?php
/**
 * Database Relationship Verification Script
 * Checks and displays all primary keys and foreign keys in the database
 */

require_once 'includes/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Relationship Verification</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }
            .container {
                max-width: 1400px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 40px;
                text-align: center;
            }
            .header h1 {
                font-size: 2.5em;
                margin-bottom: 10px;
            }
            .header p {
                font-size: 1.2em;
                opacity: 0.9;
            }
            .content {
                padding: 40px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
            .stat-card h3 {
                font-size: 3em;
                margin-bottom: 10px;
            }
            .stat-card p {
                font-size: 1.1em;
                opacity: 0.9;
            }
            .section {
                margin-bottom: 40px;
            }
            .section h2 {
                color: #667eea;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 3px solid #667eea;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                text-align: left;
                font-weight: 600;
            }
            td {
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
            }
            tr:hover {
                background: #f8f9fa;
            }
            .badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.85em;
                font-weight: 600;
            }
            .badge-success {
                background: #d4edda;
                color: #155724;
            }
            .badge-warning {
                background: #fff3cd;
                color: #856404;
            }
            .badge-danger {
                background: #f8d7da;
                color: #721c24;
            }
            .badge-info {
                background: #d1ecf1;
                color: #0c5460;
            }
            .alert {
                padding: 15px 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .alert-success {
                background: #d4edda;
                border-left: 4px solid #28a745;
                color: #155724;
            }
            .alert-danger {
                background: #f8d7da;
                border-left: 4px solid #dc3545;
                color: #721c24;
            }
            .table-wrapper {
                overflow-x: auto;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px 40px;
                text-align: center;
                color: #666;
                border-top: 1px solid #eee;
            }
            code {
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                color: #e83e8c;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔍 Database Relationship Verification</h1>
                <p>Complete analysis of primary keys and foreign key constraints</p>
                <p style="font-size: 0.9em; margin-top: 10px;">Database: <strong><?php echo htmlspecialchars(DB_NAME); ?></strong></p>
            </div>
            
            <div class="content">
                <?php
                
                // Get total tables
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                ");
                $total_tables = $stmt->fetch()['count'];
                
                // Get total foreign keys
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $total_fks = $stmt->fetch()['count'];
                
                // Get tables with foreign keys
                $stmt = $pdo->query("
                    SELECT COUNT(DISTINCT TABLE_NAME) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $tables_with_fks = $stmt->fetch()['count'];
                
                // Get tables without foreign keys
                $tables_without_fks = $total_tables - $tables_with_fks;
                
                ?>
                
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_tables; ?></h3>
                        <p>Total Tables</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_fks; ?></h3>
                        <p>Foreign Keys</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $tables_with_fks; ?></h3>
                        <p>Tables with FKs</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $tables_without_fks; ?></h3>
                        <p>Tables without FKs</p>
                    </div>
                </div>
                
                <?php
                if ($total_fks > 0) {
                    echo '<div class="alert alert-success">';
                    echo '<strong>✅ Database is properly configured!</strong><br>';
                    echo "Found $total_fks foreign key constraints across $tables_with_fks tables.";
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger">';
                    echo '<strong>❌ No foreign keys found!</strong><br>';
                    echo 'Your database is missing relationship constraints. Run <code>restore_database_relationships.php</code> to fix this.';
                    echo '</div>';
                }
                ?>
                
                <!-- All Tables Overview -->
                <div class="section">
                    <h2>📊 Database Tables Overview</h2>
                    <?php
                    $stmt = $pdo->query("
                        SELECT 
                            t.TABLE_NAME,
                            t.TABLE_ROWS,
                            COUNT(DISTINCT k.COLUMN_NAME) as PK_COLUMNS,
                            (SELECT COUNT(*) 
                             FROM information_schema.KEY_COLUMN_USAGE k2
                             WHERE k2.TABLE_SCHEMA = '" . DB_NAME . "'
                               AND k2.TABLE_NAME = t.TABLE_NAME
                               AND k2.REFERENCED_TABLE_NAME IS NOT NULL
                            ) as FK_OUT,
                            (SELECT COUNT(*) 
                             FROM information_schema.KEY_COLUMN_USAGE k3
                             WHERE k3.TABLE_SCHEMA = '" . DB_NAME . "'
                               AND k3.REFERENCED_TABLE_NAME = t.TABLE_NAME
                            ) as FK_IN
                        FROM information_schema.TABLES t
                        LEFT JOIN information_schema.KEY_COLUMN_USAGE k 
                            ON t.TABLE_NAME = k.TABLE_NAME 
                            AND t.TABLE_SCHEMA = k.TABLE_SCHEMA
                            AND k.CONSTRAINT_NAME = 'PRIMARY'
                        WHERE t.TABLE_SCHEMA = '" . DB_NAME . "'
                        GROUP BY t.TABLE_NAME
                        ORDER BY t.TABLE_NAME
                    ");
                    $tables = $stmt->fetchAll();
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>PK Columns</th>
                                    <th>FKs Out</th>
                                    <th>FKs In</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($table['TABLE_NAME']); ?></strong></td>
                                    <td><?php echo number_format($table['TABLE_ROWS']); ?></td>
                                    <td><span class="badge badge-info"><?php echo $table['PK_COLUMNS']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $table['FK_OUT']; ?></span></td>
                                    <td><span class="badge badge-success"><?php echo $table['FK_IN']; ?></span></td>
                                    <td>
                                        <?php if ($table['PK_COLUMNS'] > 0): ?>
                                            <span class="badge badge-success">Has PK</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">No PK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Foreign Key Details -->
                <div class="section">
                    <h2>🔗 Foreign Key Relationships</h2>
                    <?php
                    $stmt = $pdo->query("
                        SELECT 
                            k.TABLE_NAME,
                            k.COLUMN_NAME,
                            k.CONSTRAINT_NAME,
                            k.REFERENCED_TABLE_NAME,
                            k.REFERENCED_COLUMN_NAME,
                            rc.UPDATE_RULE,
                            rc.DELETE_RULE
                        FROM information_schema.KEY_COLUMN_USAGE k
                        JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                            ON k.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                            AND k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                        WHERE k.TABLE_SCHEMA = '" . DB_NAME . "'
                          AND k.REFERENCED_TABLE_NAME IS NOT NULL
                        ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME
                    ");
                    $fks = $stmt->fetchAll();
                    
                    if (count($fks) > 0):
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Column</th>
                                    <th>References</th>
                                    <th>Constraint Name</th>
                                    <th>On Update</th>
                                    <th>On Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fks as $fk): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fk['TABLE_NAME']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($fk['COLUMN_NAME']); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($fk['REFERENCED_TABLE_NAME']); ?></strong>
                                        (<code><?php echo htmlspecialchars($fk['REFERENCED_COLUMN_NAME']); ?></code>)
                                    </td>
                                    <td><small><?php echo htmlspecialchars($fk['CONSTRAINT_NAME']); ?></small></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($fk['UPDATE_RULE']); ?></span></td>
                                    <td>
                                        <?php 
                                        $delete_rule = $fk['DELETE_RULE'];
                                        $badge_class = $delete_rule === 'CASCADE' ? 'badge-danger' : 
                                                      ($delete_rule === 'SET NULL' ? 'badge-warning' : 'badge-info');
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($delete_rule); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>No foreign key constraints found!</strong><br>
                        Run the restoration script to add them.
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Most Referenced Tables -->
                <div class="section">
                    <h2>🌟 Most Referenced Tables (Hub Tables)</h2>
                    <?php
                    $stmt = $pdo->query("
                        SELECT 
                            REFERENCED_TABLE_NAME as table_name,
                            COUNT(*) as reference_count
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                          AND REFERENCED_TABLE_NAME IS NOT NULL
                        GROUP BY REFERENCED_TABLE_NAME
                        ORDER BY reference_count DESC
                        LIMIT 10
                    ");
                    $hub_tables = $stmt->fetchAll();
                    
                    if (count($hub_tables) > 0):
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Table Name</th>
                                    <th>Referenced By</th>
                                    <th>Importance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($hub_tables as $table): 
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $rank++; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($table['table_name']); ?></strong></td>
                                    <td><span class="badge badge-success"><?php echo $table['reference_count']; ?> tables</span></td>
                                    <td>
                                        <?php
                                        $count = $table['reference_count'];
                                        $importance = $count > 10 ? '🔥 Critical' : 
                                                     ($count > 5 ? '⚡ High' : 
                                                     ($count > 2 ? '✨ Medium' : '📌 Low'));
                                        echo $importance;
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tables with Most Foreign Keys -->
                <div class="section">
                    <h2>🔗 Tables with Most Foreign Keys</h2>
                    <?php
                    $stmt = $pdo->query("
                        SELECT 
                            TABLE_NAME,
                            COUNT(*) as fk_count
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                          AND REFERENCED_TABLE_NAME IS NOT NULL
                        GROUP BY TABLE_NAME
                        ORDER BY fk_count DESC
                        LIMIT 10
                    ");
                    $fk_tables = $stmt->fetchAll();
                    
                    if (count($fk_tables) > 0):
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Table Name</th>
                                    <th>Foreign Keys</th>
                                    <th>Complexity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($fk_tables as $table): 
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $rank++; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($table['TABLE_NAME']); ?></strong></td>
                                    <td><span class="badge badge-warning"><?php echo $table['fk_count']; ?> FKs</span></td>
                                    <td>
                                        <?php
                                        $count = $table['fk_count'];
                                        $complexity = $count > 6 ? '⚠️ Very Complex' : 
                                                     ($count > 3 ? '🔸 Complex' : 
                                                     ($count > 1 ? '🔹 Moderate' : '✅ Simple'));
                                        echo $complexity;
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <div class="footer">
                <p><strong>Database Verification Complete</strong></p>
                <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                <p style="margin-top: 10px;">
                    <a href="restore_database_relationships.php" style="color: #667eea; text-decoration: none;">
                        → Restore Missing Relationships
                    </a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>













